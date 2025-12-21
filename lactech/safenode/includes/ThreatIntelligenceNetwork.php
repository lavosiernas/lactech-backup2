<?php
/**
 * SafeNode - Threat Intelligence Network (FUNCIONAL)
 * Sistema de inteligência de ameaças colaborativa e proprietária
 * 
 * STATUS: FUNCIONAL - Sistema completo de rede colaborativa de ameaças
 * 
 * Funcionalidades:
 * - Reporta ameaças automaticamente quando detectadas
 * - Compartilha inteligência entre sites (anonimizado)
 * - Bloqueio global baseado em critérios de confiança
 * - Verificação automática contra base global
 * - Estatísticas e análises da rede
 */

class ThreatIntelligenceNetwork {
    private $db;
    private $cache;
    
    public function __construct($database) {
        $this->db = $database;
        require_once __DIR__ . '/CacheManager.php';
        $this->cache = CacheManager::getInstance();
        
        // Garantir que as tabelas existam
        $this->ensureTablesExist();
    }
    
    /**
     * Garante que as tabelas necessárias existem
     */
    private function ensureTablesExist() {
        if (!$this->db) return;
        
        try {
            // Tabela principal de inteligência de ameaças
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS safenode_threat_intelligence (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    ip_address VARCHAR(45) NOT NULL,
                    threat_type VARCHAR(50) NOT NULL,
                    severity INT NOT NULL DEFAULT 50,
                    attack_pattern TEXT,
                    total_occurrences INT DEFAULT 1,
                    affected_sites_count INT DEFAULT 1,
                    confidence_score INT DEFAULT 50,
                    is_global_block TINYINT(1) DEFAULT 0,
                    is_verified TINYINT(1) DEFAULT 0,
                    metadata TEXT,
                    first_seen DATETIME DEFAULT CURRENT_TIMESTAMP,
                    last_seen DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_ip_threat (ip_address, threat_type),
                    INDEX idx_global_block (is_global_block),
                    INDEX idx_severity (severity),
                    INDEX idx_confidence (confidence_score),
                    INDEX idx_last_seen (last_seen),
                    UNIQUE KEY unique_ip_threat (ip_address, threat_type)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            
            // Tabela de correlações (sites afetados - anonimizado)
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS safenode_threat_correlations (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    threat_intelligence_id INT NOT NULL,
                    site_id_hash VARCHAR(64) NOT NULL,
                    occurrence_count INT DEFAULT 1,
                    first_seen DATETIME DEFAULT CURRENT_TIMESTAMP,
                    last_seen DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (threat_intelligence_id) REFERENCES safenode_threat_intelligence(id) ON DELETE CASCADE,
                    INDEX idx_threat_id (threat_intelligence_id),
                    INDEX idx_site_hash (site_id_hash),
                    UNIQUE KEY unique_threat_site (threat_intelligence_id, site_id_hash)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            
            // Tabela de padrões de ataque
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS safenode_attack_patterns (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    pattern_name VARCHAR(100) NOT NULL,
                    threat_type VARCHAR(50),
                    pattern_signature TEXT,
                    detection_count INT DEFAULT 0,
                    severity INT DEFAULT 50,
                    is_active TINYINT(1) DEFAULT 1,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_threat_type (threat_type),
                    INDEX idx_active (is_active)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        } catch (PDOException $e) {
            error_log("ThreatIntelligenceNetwork Table Creation Error: " . $e->getMessage());
        }
    }
    
    /**
     * Reporta uma ameaça para a rede colaborativa (anonimizado)
     * Este método é chamado automaticamente quando uma ameaça é detectada
     */
    public function reportThreat($ipAddress, $threatType, $severity, $attackPattern = null, $siteId = null) {
        if (!$this->db) return false;
        
        // Validar entrada
        if (empty($ipAddress) || empty($threatType)) {
            return false;
        }
        
        // Filtrar IPs privados/locais (não reportar)
        if ($this->isPrivateIP($ipAddress)) {
            return false;
        }
        
        try {
            // Hash do site_id para anonimização e privacidade
            $siteIdHash = $siteId ? hash('sha256', $siteId . 'safenode_salt_2025') : null;
            
            // Verificar cache primeiro para evitar queries desnecessárias
            $cacheKey = "threat_network:$ipAddress:$threatType";
            $cached = $this->cache->get($cacheKey);
            
            // Verificar se já existe registro desta ameaça
            $stmt = $this->db->prepare("
                SELECT id, total_occurrences, affected_sites_count, confidence_score, is_global_block
                FROM safenode_threat_intelligence
                WHERE ip_address = ? AND threat_type = ?
            ");
            $stmt->execute([$ipAddress, $threatType]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing) {
                // Atualizar registro existente
                $newOccurrences = $existing['total_occurrences'] + 1;
                $newConfidence = min(100, $existing['confidence_score'] + 2); // Aumenta confiança com mais ocorrências
                
                // Verificar se é um site novo afetado
                $stmtCheck = $this->db->prepare("
                    SELECT COUNT(*) as count
                    FROM safenode_threat_correlations
                    WHERE threat_intelligence_id = ? AND site_id_hash = ?
                ");
                $stmtCheck->execute([$existing['id'], $siteIdHash]);
                $correlation = $stmtCheck->fetch(PDO::FETCH_ASSOC);
                
                $newSitesCount = $existing['affected_sites_count'];
                if ($correlation['count'] == 0 && $siteIdHash) {
                    $newSitesCount++;
                    
                    // Adicionar correlação
                    $stmtCorr = $this->db->prepare("
                        INSERT INTO safenode_threat_correlations
                        (threat_intelligence_id, site_id_hash, occurrence_count, first_seen, last_seen)
                        VALUES (?, ?, 1, NOW(), NOW())
                    ");
                    $stmtCorr->execute([$existing['id'], $siteIdHash]);
                } else if ($siteIdHash) {
                    // Atualizar correlação existente
                    $stmtUpdate = $this->db->prepare("
                        UPDATE safenode_threat_correlations
                        SET occurrence_count = occurrence_count + 1,
                            last_seen = NOW()
                        WHERE threat_intelligence_id = ? AND site_id_hash = ?
                    ");
                    $stmtUpdate->execute([$existing['id'], $siteIdHash]);
                }
                
                // Atualizar registro principal
                $stmtUpdate = $this->db->prepare("
                    UPDATE safenode_threat_intelligence
                    SET total_occurrences = ?,
                        affected_sites_count = ?,
                        severity = GREATEST(severity, ?),
                        confidence_score = ?,
                        last_seen = NOW(),
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $stmtUpdate->execute([
                    $newOccurrences,
                    $newSitesCount,
                    $severity,
                    $newConfidence,
                    $existing['id']
                ]);
                
                $threatId = $existing['id'];
            } else {
                // Criar novo registro
                $metadata = json_encode([
                    'attack_pattern' => $attackPattern,
                    'first_reported' => date('Y-m-d H:i:s')
                ]);
                
                $stmt = $this->db->prepare("
                    INSERT INTO safenode_threat_intelligence
                    (ip_address, threat_type, severity, attack_pattern, total_occurrences, 
                     affected_sites_count, confidence_score, metadata, first_seen, last_seen)
                    VALUES (?, ?, ?, ?, 1, 1, 50, ?, NOW(), NOW())
                ");
                $stmt->execute([
                    $ipAddress,
                    $threatType,
                    $severity,
                    json_encode($attackPattern),
                    $metadata
                ]);
                
                $threatId = $this->db->lastInsertId();
                
                // Adicionar correlação se site_id fornecido
                if ($siteIdHash) {
                    $stmtCorr = $this->db->prepare("
                        INSERT INTO safenode_threat_correlations
                        (threat_intelligence_id, site_id_hash, occurrence_count, first_seen, last_seen)
                        VALUES (?, ?, 1, NOW(), NOW())
                    ");
                    $stmtCorr->execute([$threatId, $siteIdHash]);
                }
            }
            
            // Invalidar cache
            $this->cache->delete($cacheKey);
            $this->cache->delete("threat_network:check:$ipAddress");
            
            // Verificar se deve ativar bloqueio global
            $globalBlocked = $this->checkGlobalBlock($threatId);
            
            // Se foi bloqueado globalmente, propagar para outros sites automaticamente
            if ($globalBlocked) {
                $this->propagateGlobalBlock($ipAddress, $threatType);
            }
            
            return $threatId;
        } catch (PDOException $e) {
            error_log("ThreatIntelligenceNetwork Report Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica se é um IP privado/local (não deve ser reportado)
     */
    private function isPrivateIP($ipAddress) {
        // IPv4 privados
        if (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return !filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
        }
        
        // IPv6 privados/locais
        if (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            // fc00::/7 (Unique Local Address)
            // fe80::/10 (Link-Local)
            $ipv6 = inet_pton($ipAddress);
            if ($ipv6 !== false) {
                $firstByte = ord($ipv6[0]);
                return ($firstByte >= 0xfc && $firstByte <= 0xfd) || ($firstByte >= 0xfe && ($firstByte & 0xc0) == 0x80);
            }
        }
        
        return false;
    }
    
    /**
     * Propaga bloqueio global para outros sites
     */
    private function propagateGlobalBlock($ipAddress, $threatType) {
        if (!$this->db) return;
        
        try {
            // Buscar todos os sites ativos (exceto o que reportou)
            $stmt = $this->db->query("
                SELECT DISTINCT s.id 
                FROM safenode_sites s
                WHERE s.is_active = 1
            ");
            $sites = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            require_once __DIR__ . '/IPBlocker.php';
            
            foreach ($sites as $siteId) {
                try {
                    // Verificar se IP já está bloqueado neste site
                    $ipBlocker = new IPBlocker($this->db);
                    if (!$ipBlocker->isBlocked($ipAddress)) {
                        // Bloquear IP com motivo da rede de inteligência
                        $ipBlocker->blockIP(
                            $ipAddress,
                            "Bloqueio global da rede de inteligência: $threatType",
                            'threat_intelligence_network',
                            86400 * 7 // 7 dias
                        );
                    }
                } catch (Exception $e) {
                    // Continuar para próximo site
                    error_log("ThreatIntelligenceNetwork Propagate Error for site $siteId: " . $e->getMessage());
                }
            }
        } catch (PDOException $e) {
            error_log("ThreatIntelligenceNetwork Propagate Error: " . $e->getMessage());
        }
    }
    
    /**
     * Verifica se um IP está na base de ameaças global
     * Usado pelo middleware para verificação preventiva
     */
    public function checkThreat($ipAddress, $threatType = null) {
        if (!$this->db) return null;
        
        // Verificar cache
        $cacheKey = "threat_network:check:$ipAddress" . ($threatType ? ":$threatType" : "");
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached === false ? null : $cached;
        }
        
        try {
            $sql = "
                SELECT id, threat_type, severity, total_occurrences, 
                       affected_sites_count, confidence_score, is_global_block,
                       last_seen, attack_pattern, confidence_score
                FROM safenode_threat_intelligence
                WHERE ip_address = ? 
                AND (is_global_block = 1 OR confidence_score >= 70)
            ";
            $params = [$ipAddress];
            
            if ($threatType) {
                $sql .= " AND threat_type = ?";
                $params[] = $threatType;
            }
            
            $sql .= " ORDER BY is_global_block DESC, severity DESC, confidence_score DESC LIMIT 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Cache por 15 minutos
            $this->cache->set($cacheKey, $result ?: false, 900);
            
            return $result;
        } catch (PDOException $e) {
            error_log("ThreatIntelligenceNetwork Check Error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Verifica múltiplos IPs de uma vez (otimizado)
     */
    public function checkThreatsBatch($ipAddresses) {
        if (!$this->db || empty($ipAddresses)) return [];
        
        try {
            $placeholders = str_repeat('?,', count($ipAddresses) - 1) . '?';
            $stmt = $this->db->prepare("
                SELECT ip_address, threat_type, severity, total_occurrences, 
                       affected_sites_count, confidence_score, is_global_block,
                       last_seen
                FROM safenode_threat_intelligence
                WHERE ip_address IN ($placeholders)
                AND (is_global_block = 1 OR confidence_score >= 70)
                ORDER BY ip_address, severity DESC
            ");
            $stmt->execute($ipAddresses);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Organizar por IP
            $threats = [];
            foreach ($results as $threat) {
                $ip = $threat['ip_address'];
                if (!isset($threats[$ip]) || $threats[$ip]['severity'] < $threat['severity']) {
                    $threats[$ip] = $threat;
                }
            }
            
            return $threats;
        } catch (PDOException $e) {
            error_log("ThreatIntelligenceNetwork Batch Check Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtém lista de IPs maliciosos globais
     */
    public function getGlobalThreats($limit = 100, $minSeverity = 70, $minConfidence = 60) {
        if (!$this->db) return [];
        
        try {
            $stmt = $this->db->prepare("
                SELECT ip_address, threat_type, severity, total_occurrences,
                       affected_sites_count, confidence_score, last_seen
                FROM safenode_threat_intelligence
                WHERE is_global_block = 1
                  AND severity >= ?
                  AND confidence_score >= ?
                ORDER BY severity DESC, confidence_score DESC, last_seen DESC
                LIMIT ?
            ");
            $stmt->execute([$minSeverity, $minConfidence, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("ThreatIntelligenceNetwork GetGlobalThreats Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtém estatísticas da rede de inteligência
     */
    public function getNetworkStats() {
        if (!$this->db) return null;
        
        try {
            $stats = [];
            
            // Total de ameaças únicas
            $stmt = $this->db->query("
                SELECT COUNT(DISTINCT ip_address) as total_ips,
                       COUNT(*) as total_threats,
                       SUM(total_occurrences) as total_occurrences,
                       SUM(affected_sites_count) as total_affected_sites
                FROM safenode_threat_intelligence
            ");
            $stats['overall'] = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Ameaças por tipo
            $stmt = $this->db->query("
                SELECT threat_type, COUNT(*) as count, AVG(severity) as avg_severity
                FROM safenode_threat_intelligence
                GROUP BY threat_type
                ORDER BY count DESC
            ");
            $stats['by_type'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Top IPs mais perigosos
            $stmt = $this->db->query("
                SELECT ip_address, threat_type, severity, total_occurrences, affected_sites_count
                FROM safenode_threat_intelligence
                WHERE is_global_block = 1
                ORDER BY severity DESC, total_occurrences DESC
                LIMIT 10
            ");
            $stats['top_threats'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $stats;
        } catch (PDOException $e) {
            error_log("ThreatIntelligenceNetwork GetStats Error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Verifica se deve ativar bloqueio global baseado em critérios inteligentes
     * Retorna true se foi bloqueado globalmente
     */
    private function checkGlobalBlock($threatId) {
        if (!$this->db) return false;
        
        try {
            $stmt = $this->db->prepare("
                SELECT severity, total_occurrences, affected_sites_count, confidence_score, is_global_block
                FROM safenode_threat_intelligence
                WHERE id = ?
            ");
            $stmt->execute([$threatId]);
            $threat = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$threat || $threat['is_global_block'] == 1) {
                return $threat['is_global_block'] == 1;
            }
            
            // Critérios para bloqueio global (ajustáveis baseado em experiência):
            // 1. Severidade >= 80 E ocorrências >= 5 E afetou >= 3 sites (ataque confirmado multi-site)
            // 2. Severidade >= 90 E ocorrências >= 3 (ataque crítico)
            // 3. Afetou >= 10 sites diferentes (ataque massivo)
            // 4. Confidence >= 90 E afetou >= 5 sites (alta confiança multi-site)
            // 5. Severidade >= 85 E confidence >= 85 E ocorrências >= 7 (combinação alta)
            
            $shouldBlock = false;
            $reason = '';
            
            if ($threat['severity'] >= 80 && $threat['total_occurrences'] >= 5 && $threat['affected_sites_count'] >= 3) {
                $shouldBlock = true;
                $reason = 'Severidade alta + múltiplas ocorrências + múltiplos sites';
            } elseif ($threat['severity'] >= 90 && $threat['total_occurrences'] >= 3) {
                $shouldBlock = true;
                $reason = 'Severidade crítica + múltiplas ocorrências';
            } elseif ($threat['affected_sites_count'] >= 10) {
                $shouldBlock = true;
                $reason = 'Ataque massivo (10+ sites afetados)';
            } elseif ($threat['confidence_score'] >= 90 && $threat['affected_sites_count'] >= 5) {
                $shouldBlock = true;
                $reason = 'Alta confiança + múltiplos sites';
            } elseif ($threat['severity'] >= 85 && $threat['confidence_score'] >= 85 && $threat['total_occurrences'] >= 7) {
                $shouldBlock = true;
                $reason = 'Combinação alta de métricas';
            }
            
            if ($shouldBlock) {
                $stmtUpdate = $this->db->prepare("
                    UPDATE safenode_threat_intelligence
                    SET is_global_block = 1, 
                        is_verified = 1,
                        metadata = JSON_SET(COALESCE(metadata, '{}'), '$.global_block_reason', ?, '$.global_block_date', NOW())
                    WHERE id = ?
                ");
                $stmtUpdate->execute([$reason, $threatId]);
                
                return true;
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("ThreatIntelligenceNetwork CheckGlobalBlock Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém padrões de ataque identificados da rede
     */
    public function getAttackPatterns($threatType = null) {
        if (!$this->db) return [];
        
        try {
            $sql = "SELECT * FROM safenode_attack_patterns WHERE is_active = 1";
            $params = [];
            
            if ($threatType) {
                $sql .= " AND threat_type = ?";
                $params[] = $threatType;
            }
            
            $sql .= " ORDER BY detection_count DESC, severity DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("ThreatIntelligenceNetwork GetPatterns Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Sincroniza ameaças da rede com IPBlocker (adiciona bloqueios automáticos)
     */
    public function syncWithIPBlocker($siteId = null) {
        if (!$this->db) return 0;
        
        try {
            require_once __DIR__ . '/IPBlocker.php';
            $ipBlocker = new IPBlocker($this->db);
            
            // Buscar ameaças globais ainda não bloqueadas
            $sql = "
                SELECT DISTINCT t.ip_address, t.threat_type, t.severity, t.confidence_score
                FROM safenode_threat_intelligence t
                WHERE t.is_global_block = 1
                AND t.last_seen >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ";
            
            if ($siteId) {
                $sql .= " AND NOT EXISTS (
                    SELECT 1 FROM safenode_blocked_ips b
                    WHERE b.ip_address = t.ip_address
                    AND b.site_id = ?
                )";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$siteId]);
            } else {
                $stmt = $this->db->query($sql);
            }
            
            $threats = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $blockedCount = 0;
            
            foreach ($threats as $threat) {
                try {
                    $ipAddress = $threat['ip_address'];
                    
                    // Verificar se já está bloqueado
                    if (!$ipBlocker->isBlocked($ipAddress)) {
                        // Bloquear IP com motivo da rede
                        $ipBlocker->blockIP(
                            $ipAddress,
                            "Bloqueio automático da rede de inteligência: {$threat['threat_type']} (Severidade: {$threat['severity']}, Confiança: {$threat['confidence_score']}%)",
                            'threat_intelligence_network',
                            86400 * 7, // 7 dias
                            $siteId
                        );
                        $blockedCount++;
                    }
                } catch (Exception $e) {
                    // Continuar para próximo
                    error_log("ThreatIntelligenceNetwork Sync Error for {$threat['ip_address']}: " . $e->getMessage());
                }
            }
            
            return $blockedCount;
        } catch (PDOException $e) {
            error_log("ThreatIntelligenceNetwork Sync Error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Obtém ameaças recentes para dashboard
     */
    public function getRecentThreats($hours = 24, $limit = 50) {
        if (!$this->db) return [];
        
        try {
            $stmt = $this->db->prepare("
                SELECT ip_address, threat_type, severity, total_occurrences,
                       affected_sites_count, confidence_score, is_global_block, last_seen
                FROM safenode_threat_intelligence
                WHERE last_seen >= DATE_SUB(NOW(), INTERVAL ? HOUR)
                ORDER BY last_seen DESC, severity DESC
                LIMIT ?
            ");
            $stmt->execute([$hours, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("ThreatIntelligenceNetwork GetRecentThreats Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtém ameaças agrupadas por tipo
     */
    public function getThreatsByType() {
        if (!$this->db) return [];
        
        try {
            $stmt = $this->db->query("
                SELECT threat_type, 
                       COUNT(*) as count, 
                       AVG(severity) as avg_severity,
                       SUM(total_occurrences) as total_occurrences,
                       COUNT(DISTINCT ip_address) as unique_ips
                FROM safenode_threat_intelligence
                GROUP BY threat_type
                ORDER BY count DESC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("ThreatIntelligenceNetwork GetThreatsByType Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtém top padrões de ataque
     */
    public function getTopAttackPatterns($limit = 10) {
        if (!$this->db) return [];
        
        try {
            $stmt = $this->db->prepare("
                SELECT pattern_name, 
                       threat_type,
                       COUNT(*) as detection_count,
                       AVG(severity) as avg_severity,
                       MAX(last_seen) as last_seen
                FROM safenode_attack_patterns
                WHERE is_active = 1
                GROUP BY pattern_name, threat_type
                ORDER BY detection_count DESC, avg_severity DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("ThreatIntelligenceNetwork GetTopAttackPatterns Error: " . $e->getMessage());
            return [];
        }
    }
}

