<?php
/**
 * SafeNode - Quarantine System
 * Sistema de quarentena inteligente para IPs suspeitos
 * 
 * Estado intermediário entre permitido e bloqueado:
 * - IPs suspeitos são colocados em quarentena
 * - Monitoramento mais profundo
 * - Análise antes de bloquear permanentemente
 */

class QuarantineSystem {
    private $db;
    private $cache;
    
    public function __construct($database) {
        $this->db = $database;
        require_once __DIR__ . '/CacheManager.php';
        $this->cache = CacheManager::getInstance();
        $this->ensureTableExists();
    }
    
    /**
     * Adiciona IP à quarentena
     * 
     * @param string $ipAddress IP a colocar em quarentena
     * @param string $reason Motivo da quarentena
     * @param int $threatScore Score de ameaça
     * @param string $threatType Tipo de ameaça
     * @param int $duration Duração em segundos (padrão: 1 hora)
     * @return bool Sucesso
     */
    public function addToQuarantine($ipAddress, $reason, $threatScore, $threatType = null, $duration = 3600) {
        if (!$this->db) return false;
        
        try {
            // Verificar se já está em quarentena
            $stmt = $this->db->prepare("
                SELECT id FROM safenode_quarantine 
                WHERE ip_address = ? 
                AND status = 'active'
            ");
            $stmt->execute([$ipAddress]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                // Atualizar quarentena existente
                $stmt = $this->db->prepare("
                    UPDATE safenode_quarantine 
                    SET reason = ?, threat_score = ?, threat_type = ?,
                        expires_at = DATE_ADD(NOW(), INTERVAL ? SECOND),
                        updated_at = NOW(),
                        violation_count = violation_count + 1
                    WHERE id = ?
                ");
                $stmt->execute([$reason, $threatScore, $threatType, $duration, $existing['id']]);
            } else {
                // Criar nova quarentena
                $stmt = $this->db->prepare("
                    INSERT INTO safenode_quarantine 
                    (ip_address, reason, threat_score, threat_type, expires_at, status, created_at) 
                    VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL ? SECOND), 'active', NOW())
                ");
                $stmt->execute([$ipAddress, $reason, $threatScore, $threatType, $duration]);
            }
            
            // Invalidar cache
            $this->cache->delete("quarantine:$ipAddress");
            
            return true;
        } catch (PDOException $e) {
            error_log("SafeNode Quarantine Add Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica se IP está em quarentena
     * 
     * @param string $ipAddress IP a verificar
     * @return array|null Dados da quarentena ou null
     */
    public function isInQuarantine($ipAddress) {
        if (!$this->db) return null;
        
        // Verificar cache primeiro
        $cacheKey = "quarantine:$ipAddress";
        $cached = $this->cache->get($cacheKey);
        
        if ($cached !== null) {
            return $cached === false ? null : $cached;
        }
        
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM safenode_quarantine 
                WHERE ip_address = ? 
                AND status = 'active'
                AND (expires_at IS NULL OR expires_at > NOW())
            ");
            $stmt->execute([$ipAddress]);
            $quarantine = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($quarantine) {
                // Salvar no cache (TTL: 5 minutos)
                $this->cache->set($cacheKey, $quarantine, 300);
                return $quarantine;
            } else {
                // Salvar false no cache para indicar que não está em quarentena
                $this->cache->set($cacheKey, false, 300);
                return null;
            }
        } catch (PDOException $e) {
            return null;
        }
    }
    
    /**
     * Processa requisição de IP em quarentena
     * 
     * @param string $ipAddress IP em quarentena
     * @param array $requestData Dados da requisição
     * @return array Resultado do processamento
     */
    public function processQuarantinedRequest($ipAddress, $requestData) {
        $quarantine = $this->isInQuarantine($ipAddress);
        
        if (!$quarantine) {
            return ['action' => 'allow', 'reason' => 'not_quarantined'];
        }
        
        // Registrar atividade do IP em quarentena
        $this->logQuarantineActivity($ipAddress, $requestData);
        
        // Análise mais profunda
        $analysis = $this->analyzeQuarantinedIP($ipAddress, $quarantine);
        
        // Decidir ação baseado na análise
        if ($analysis['should_block']) {
            // Confirmado malicioso - bloquear permanentemente
            require_once __DIR__ . '/IPBlocker.php';
            $ipBlocker = new IPBlocker($this->db);
            $ipBlocker->blockIP($ipAddress, "Confirmado malicioso após quarentena", $quarantine['threat_type'], 86400 * 7); // 7 dias
            
            $this->releaseFromQuarantine($ipAddress, 'blocked');
            
            return ['action' => 'block', 'reason' => 'confirmed_malicious'];
        } elseif ($analysis['should_release']) {
            // Falso positivo - liberar
            $this->releaseFromQuarantine($ipAddress, 'false_positive');
            
            return ['action' => 'allow', 'reason' => 'false_positive'];
        } else {
            // Manter em quarentena - aplicar challenge
            return [
                'action' => 'challenge',
                'reason' => 'quarantined',
                'challenge_level' => $this->determineChallengeLevel($quarantine)
            ];
        }
    }
    
    /**
     * Analisa IP em quarentena
     */
    private function analyzeQuarantinedIP($ipAddress, $quarantine) {
        $shouldBlock = false;
        $shouldRelease = false;
        
        try {
            // Contar violações durante quarentena
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as violations 
                FROM safenode_quarantine_activity 
                WHERE ip_address = ? 
                AND quarantine_id = ?
                AND threat_score >= 50
                AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ");
            $stmt->execute([$ipAddress, $quarantine['id']]);
            $result = $stmt->fetch();
            $violations = (int)($result['violations'] ?? 0);
            
            // Se mais de 5 violações em 1 hora = confirmado malicioso
            if ($violations >= 5) {
                $shouldBlock = true;
            }
            
            // Contar requisições legítimas
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as legitimate 
                FROM safenode_quarantine_activity 
                WHERE ip_address = ? 
                AND quarantine_id = ?
                AND threat_score < 30
                AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ");
            $stmt->execute([$ipAddress, $quarantine['id']]);
            $result = $stmt->fetch();
            $legitimate = (int)($result['legitimate'] ?? 0);
            
            // Se mais de 10 requisições legítimas = possível falso positivo
            if ($legitimate >= 10 && $violations === 0) {
                $shouldRelease = true;
            }
            
            // Verificar tempo em quarentena
            $timeInQuarantine = time() - strtotime($quarantine['created_at']);
            if ($timeInQuarantine > 3600 && $violations === 0) {
                // 1 hora sem violações = possível falso positivo
                $shouldRelease = true;
            }
            
        } catch (PDOException $e) {
            error_log("SafeNode Quarantine Analysis Error: " . $e->getMessage());
        }
        
        return [
            'should_block' => $shouldBlock,
            'should_release' => $shouldRelease,
            'violations' => $violations ?? 0,
            'legitimate' => $legitimate ?? 0
        ];
    }
    
    /**
     * Determina nível de challenge para IP em quarentena
     */
    private function determineChallengeLevel($quarantine) {
        $threatScore = (int)($quarantine['threat_score'] ?? 50);
        $violations = (int)($quarantine['violation_count'] ?? 0);
        
        // Aumentar nível de challenge baseado em violações
        if ($violations >= 3) {
            return 4; // reCAPTCHA v3
        } elseif ($violations >= 2) {
            return 3; // CAPTCHA visual
        } elseif ($threatScore >= 70) {
            return 2; // Challenge matemático
        } else {
            return 1; // JavaScript
        }
    }
    
    /**
     * Registra atividade de IP em quarentena
     */
    private function logQuarantineActivity($ipAddress, $requestData) {
        try {
            $quarantine = $this->isInQuarantine($ipAddress);
            if (!$quarantine) return;
            
            $stmt = $this->db->prepare("
                INSERT INTO safenode_quarantine_activity 
                (quarantine_id, ip_address, request_uri, threat_score, threat_type, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $quarantine['id'],
                $ipAddress,
                $requestData['request_uri'] ?? '/',
                $requestData['threat_score'] ?? 0,
                $requestData['threat_type'] ?? null
            ]);
        } catch (PDOException $e) {
            error_log("SafeNode Quarantine Activity Log Error: " . $e->getMessage());
        }
    }
    
    /**
     * Libera IP da quarentena
     */
    public function releaseFromQuarantine($ipAddress, $reason = 'manual') {
        if (!$this->db) return false;
        
        try {
            $stmt = $this->db->prepare("
                UPDATE safenode_quarantine 
                SET status = 'released', 
                    release_reason = ?,
                    released_at = NOW()
                WHERE ip_address = ? 
                AND status = 'active'
            ");
            $stmt->execute([$reason, $ipAddress]);
            
            // Invalidar cache
            $this->cache->delete("quarantine:$ipAddress");
            
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Obtém estatísticas de quarentena
     */
    public function getStats($siteId = null) {
        if (!$this->db) return [];
        
        try {
            $sql = "
                SELECT 
                    COUNT(*) as total_quarantined,
                    COUNT(CASE WHEN status = 'active' THEN 1 END) as active,
                    COUNT(CASE WHEN status = 'released' THEN 1 END) as released,
                    COUNT(CASE WHEN status = 'blocked' THEN 1 END) as blocked,
                    AVG(threat_score) as avg_threat_score
                FROM safenode_quarantine
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            ";
            
            if ($siteId) {
                // Filtrar por site se necessário (requer join com logs)
            }
            
            $stmt = $this->db->query($sql);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Garante que tabelas existem
     */
    private function ensureTableExists() {
        if (!$this->db) return;
        
        try {
            $this->db->query("SELECT 1 FROM safenode_quarantine LIMIT 1");
        } catch (PDOException $e) {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS safenode_quarantine (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    ip_address VARCHAR(45) NOT NULL,
                    reason VARCHAR(500),
                    threat_score INT DEFAULT 0,
                    threat_type VARCHAR(50),
                    violation_count INT DEFAULT 0,
                    status ENUM('active', 'released', 'blocked') DEFAULT 'active',
                    release_reason VARCHAR(100),
                    expires_at DATETIME,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    released_at DATETIME DEFAULT NULL,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_ip (ip_address),
                    INDEX idx_status (status),
                    INDEX idx_expires (expires_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }
        
        try {
            $this->db->query("SELECT 1 FROM safenode_quarantine_activity LIMIT 1");
        } catch (PDOException $e) {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS safenode_quarantine_activity (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    quarantine_id INT NOT NULL,
                    ip_address VARCHAR(45) NOT NULL,
                    request_uri VARCHAR(500),
                    threat_score INT DEFAULT 0,
                    threat_type VARCHAR(50),
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_quarantine (quarantine_id),
                    INDEX idx_ip (ip_address),
                    INDEX idx_created (created_at),
                    FOREIGN KEY (quarantine_id) REFERENCES safenode_quarantine(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }
    }
}





