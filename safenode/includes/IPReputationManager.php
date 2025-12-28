<?php
/**
 * SafeNode - IP Reputation Manager
 * Sistema de gerenciamento de reputação de IPs próprio e independente
 * Não depende de serviços externos, usa apenas análise própria
 */

class IPReputationManager {
    private $db;
    private $cache;
    
    public function __construct($database) {
        $this->db = $database;
        require_once __DIR__ . '/CacheManager.php';
        $this->cache = CacheManager::getInstance();
        $this->ensureTableExists();
    }
    
    /**
     * Garante que a tabela de reputação existe
     */
    private function ensureTableExists() {
        if (!$this->db) return;
        
        try {
            // Verificar se a tabela existe
            $this->db->query("SELECT 1 FROM safenode_ip_reputation LIMIT 1");
        } catch (PDOException $e) {
            // Criar tabela se não existir
            try {
                $this->db->exec("
                    CREATE TABLE IF NOT EXISTS safenode_ip_reputation (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        ip_address VARCHAR(45) NOT NULL UNIQUE,
                        trust_score INT DEFAULT 50 COMMENT '0-100, 0=muito suspeito, 100=muito confiável',
                        total_requests INT DEFAULT 0,
                        blocked_requests INT DEFAULT 0,
                        allowed_requests INT DEFAULT 0,
                        challenged_requests INT DEFAULT 0,
                        first_seen DATETIME DEFAULT CURRENT_TIMESTAMP,
                        last_seen DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        country_code CHAR(2) DEFAULT NULL,
                        is_whitelisted TINYINT(1) DEFAULT 0,
                        is_blacklisted TINYINT(1) DEFAULT 0,
                        threat_score_avg DECIMAL(5,2) DEFAULT 0.00,
                        threat_score_max INT DEFAULT 0,
                        last_threat_type VARCHAR(50) DEFAULT NULL,
                        notes TEXT DEFAULT NULL,
                        INDEX idx_ip (ip_address),
                        INDEX idx_trust_score (trust_score),
                        INDEX idx_last_seen (last_seen),
                        INDEX idx_country (country_code)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
            } catch (PDOException $createErr) {
                error_log("SafeNode IPReputationManager: Erro ao criar tabela - " . $createErr->getMessage());
            }
        }
    }
    
    /**
     * Obtém ou cria registro de reputação para um IP
     */
    public function getOrCreate($ipAddress, $countryCode = null) {
        if (!$this->db) return null;
        
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM safenode_ip_reputation 
                WHERE ip_address = ?
            ");
            $stmt->execute([$ipAddress]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$record) {
                // Criar novo registro
                $stmt = $this->db->prepare("
                    INSERT INTO safenode_ip_reputation 
                    (ip_address, trust_score, country_code, first_seen, last_seen)
                    VALUES (?, 50, ?, NOW(), NOW())
                ");
                $stmt->execute([$ipAddress, $countryCode]);
                
                // Buscar o registro criado
                $stmt = $this->db->prepare("
                    SELECT * FROM safenode_ip_reputation 
                    WHERE ip_address = ?
                ");
                $stmt->execute([$ipAddress]);
                $record = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                // Atualizar last_seen
                $stmt = $this->db->prepare("
                    UPDATE safenode_ip_reputation 
                    SET last_seen = NOW() 
                    WHERE ip_address = ?
                ");
                $stmt->execute([$ipAddress]);
                
                // Atualizar country_code se não tiver
                if (!$record['country_code'] && $countryCode) {
                    $stmt = $this->db->prepare("
                        UPDATE safenode_ip_reputation 
                        SET country_code = ? 
                        WHERE ip_address = ?
                    ");
                    $stmt->execute([$countryCode, $ipAddress]);
                    $record['country_code'] = $countryCode;
                }
            }
            
            return $record;
        } catch (PDOException $e) {
            error_log("SafeNode IPReputationManager Error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Atualiza a reputação de um IP baseado em um evento
     */
    public function updateReputation($ipAddress, $actionTaken, $threatScore = 0, $threatType = null, $countryCode = null) {
        if (!$this->db) return false;
        
        try {
            // Obter registro atual
            $record = $this->getOrCreate($ipAddress, $countryCode);
            if (!$record) return false;
            
            // Calcular novos valores
            $totalRequests = (int)$record['total_requests'] + 1;
            $blockedRequests = (int)$record['blocked_requests'];
            $allowedRequests = (int)$record['allowed_requests'];
            $challengedRequests = (int)$record['challenged_requests'];
            
            // Atualizar contadores baseado na ação
            switch ($actionTaken) {
                case 'blocked':
                    $blockedRequests++;
                    break;
                case 'allowed':
                    $allowedRequests++;
                    break;
                case 'challenged':
                    $challengedRequests++;
                    break;
            }
            
            // Calcular novo trust_score
            $trustScore = $this->calculateTrustScore(
                $totalRequests,
                $blockedRequests,
                $allowedRequests,
                $challengedRequests,
                $threatScore,
                (int)$record['trust_score']
            );
            
            // Calcular média de threat_score
            $threatScoreAvg = $this->calculateThreatScoreAvg(
                (float)$record['threat_score_avg'],
                $totalRequests - 1,
                $threatScore
            );
            
            $threatScoreMax = max((int)$record['threat_score_max'], (int)$threatScore);
            
            // Atualizar registro
            $stmt = $this->db->prepare("
                UPDATE safenode_ip_reputation 
                SET 
                    trust_score = ?,
                    total_requests = ?,
                    blocked_requests = ?,
                    allowed_requests = ?,
                    challenged_requests = ?,
                    threat_score_avg = ?,
                    threat_score_max = ?,
                    last_threat_type = ?,
                    last_seen = NOW()
                WHERE ip_address = ?
            ");
            
            $stmt->execute([
                $trustScore,
                $totalRequests,
                $blockedRequests,
                $allowedRequests,
                $challengedRequests,
                $threatScoreAvg,
                $threatScoreMax,
                $threatType,
                $ipAddress
            ]);
            
            // Invalidar cache de reputação
            $this->cache->delete("ip_reputation:trust_score:$ipAddress");
            $this->cache->delete("ip_reputation:whitelist:$ipAddress");
            $this->cache->delete("ip_reputation:blacklist:$ipAddress");
            
            return true;
        } catch (PDOException $e) {
            error_log("SafeNode IPReputationManager Update Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Calcula trust_score baseado em histórico
     * 0-100: 0 = muito suspeito, 100 = muito confiável
     */
    private function calculateTrustScore($totalRequests, $blockedRequests, $allowedRequests, $challengedRequests, $currentThreatScore, $previousTrustScore) {
        // Se é o primeiro request, começar com score neutro baseado no threat_score atual
        if ($totalRequests == 1) {
            return max(0, 100 - $currentThreatScore);
        }
        
        // Calcular taxa de bloqueio
        $blockRate = $totalRequests > 0 ? ($blockedRequests / $totalRequests) * 100 : 0;
        $allowRate = $totalRequests > 0 ? ($allowedRequests / $totalRequests) * 100 : 0;
        
        // Base score: inversamente proporcional à taxa de bloqueio
        $baseScore = 100 - ($blockRate * 1.5); // Penaliza mais bloqueios
        
        // Ajustar baseado no threat_score atual
        $threatPenalty = $currentThreatScore * 0.3; // Penalidade por ameaça atual
        $baseScore -= $threatPenalty;
        
        // Bônus por requisições permitidas
        $allowBonus = $allowRate * 0.2;
        $baseScore += $allowBonus;
        
        // Penalidade por desafios (indica comportamento suspeito)
        $challengePenalty = ($challengedRequests / max($totalRequests, 1)) * 20;
        $baseScore -= $challengePenalty;
        
        // Aplicar média ponderada com score anterior (70% novo, 30% anterior)
        // Isso evita mudanças muito bruscas
        $finalScore = ($baseScore * 0.7) + ($previousTrustScore * 0.3);
        
        // Limitar entre 0 e 100
        return max(0, min(100, round($finalScore)));
    }
    
    /**
     * Calcula média de threat_score
     */
    private function calculateThreatScoreAvg($currentAvg, $previousCount, $newScore) {
        if ($previousCount == 0) {
            return (float)$newScore;
        }
        
        // Média ponderada
        $total = $previousCount + 1;
        $newAvg = (($currentAvg * $previousCount) + $newScore) / $total;
        
        return round($newAvg, 2);
    }
    
    /**
     * Obtém trust_score de um IP (COM CACHE)
     */
    public function getTrustScore($ipAddress) {
        // Verificar cache primeiro
        $cacheKey = "ip_reputation:trust_score:$ipAddress";
        $cached = $this->cache->get($cacheKey);
        
        if ($cached !== null) {
            return (int)$cached;
        }
        
        // Se não está no cache, buscar no banco
        $record = $this->getOrCreate($ipAddress);
        $trustScore = $record ? (int)$record['trust_score'] : 50; // Default neutro
        
        // Salvar no cache (TTL de 15 minutos)
        $this->cache->set($cacheKey, $trustScore, CacheManager::TTL_IP_REPUTATION);
        
        return $trustScore;
    }
    
    /**
     * Verifica se IP está na whitelist (COM CACHE)
     */
    public function isWhitelisted($ipAddress) {
        if (!$this->db) return false;
        
        // Verificar cache primeiro
        $cacheKey = "ip_reputation:whitelist:$ipAddress";
        $cached = $this->cache->get($cacheKey);
        
        if ($cached !== null) {
            return (bool)$cached;
        }
        
        // Se não está no cache, buscar no banco
        try {
            $stmt = $this->db->prepare("
                SELECT is_whitelisted FROM safenode_ip_reputation 
                WHERE ip_address = ?
            ");
            $stmt->execute([$ipAddress]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $isWhitelisted = $result && (bool)$result['is_whitelisted'];
            
            // Salvar no cache
            $this->cache->set($cacheKey, $isWhitelisted, CacheManager::TTL_IP_REPUTATION);
            
            return $isWhitelisted;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Verifica se IP está na blacklist (COM CACHE)
     */
    public function isBlacklisted($ipAddress) {
        if (!$this->db) return false;
        
        // Verificar cache primeiro
        $cacheKey = "ip_reputation:blacklist:$ipAddress";
        $cached = $this->cache->get($cacheKey);
        
        if ($cached !== null) {
            return (bool)$cached;
        }
        
        // Se não está no cache, buscar no banco
        try {
            $stmt = $this->db->prepare("
                SELECT is_blacklisted FROM safenode_ip_reputation 
                WHERE ip_address = ?
            ");
            $stmt->execute([$ipAddress]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $isBlacklisted = $result && (bool)$result['is_blacklisted'];
            
            // Salvar no cache
            $this->cache->set($cacheKey, $isBlacklisted, CacheManager::TTL_IP_REPUTATION);
            
            return $isBlacklisted;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Adiciona IP à whitelist
     */
    public function addToWhitelist($ipAddress) {
        if (!$this->db) return false;
        
        try {
            $this->getOrCreate($ipAddress); // Garantir que existe
            
            $stmt = $this->db->prepare("
                UPDATE safenode_ip_reputation 
                SET is_whitelisted = 1, trust_score = 100 
                WHERE ip_address = ?
            ");
            return $stmt->execute([$ipAddress]);
        } catch (PDOException $e) {
            error_log("SafeNode IPReputationManager Whitelist Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Adiciona IP à blacklist
     */
    public function addToBlacklist($ipAddress, $notes = null) {
        if (!$this->db) return false;
        
        try {
            $this->getOrCreate($ipAddress); // Garantir que existe
            
            $stmt = $this->db->prepare("
                UPDATE safenode_ip_reputation 
                SET is_blacklisted = 1, trust_score = 0, notes = ?
                WHERE ip_address = ?
            ");
            return $stmt->execute([$notes, $ipAddress]);
        } catch (PDOException $e) {
            error_log("SafeNode IPReputationManager Blacklist Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Remove IP da whitelist
     */
    public function removeFromWhitelist($ipAddress) {
        if (!$this->db) return false;
        
        try {
            $stmt = $this->db->prepare("
                UPDATE safenode_ip_reputation 
                SET is_whitelisted = 0 
                WHERE ip_address = ?
            ");
            return $stmt->execute([$ipAddress]);
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Remove IP da blacklist
     */
    public function removeFromBlacklist($ipAddress) {
        if (!$this->db) return false;
        
        try {
            $stmt = $this->db->prepare("
                UPDATE safenode_ip_reputation 
                SET is_blacklisted = 0 
                WHERE ip_address = ?
            ");
            return $stmt->execute([$ipAddress]);
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Obtém estatísticas de reputação para dashboard
     */
    public function getReputationStats($siteId = null, $userId = null) {
        if (!$this->db) return [];
        
        try {
            $sql = "
                SELECT 
                    COUNT(*) as total_ips,
                    AVG(trust_score) as avg_trust_score,
                    COUNT(CASE WHEN trust_score < 30 THEN 1 END) as low_trust_count,
                    COUNT(CASE WHEN trust_score >= 70 THEN 1 END) as high_trust_count,
                    COUNT(CASE WHEN is_blacklisted = 1 THEN 1 END) as blacklisted_count,
                    COUNT(CASE WHEN is_whitelisted = 1 THEN 1 END) as whitelisted_count
                FROM safenode_ip_reputation
                WHERE last_seen >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ";
            
            // Se houver site_id, filtrar por IPs que apareceram nos logs desse site
            if ($siteId) {
                $sql = "
                    SELECT 
                        COUNT(DISTINCT r.ip_address) as total_ips,
                        AVG(r.trust_score) as avg_trust_score,
                        COUNT(CASE WHEN r.trust_score < 30 THEN 1 END) as low_trust_count,
                        COUNT(CASE WHEN r.trust_score >= 70 THEN 1 END) as high_trust_count,
                        COUNT(CASE WHEN r.is_blacklisted = 1 THEN 1 END) as blacklisted_count,
                        COUNT(CASE WHEN r.is_whitelisted = 1 THEN 1 END) as whitelisted_count
                    FROM safenode_ip_reputation r
                    INNER JOIN safenode_security_logs l ON l.ip_address = r.ip_address
                    WHERE l.site_id = ?
                    AND r.last_seen >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                ";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$siteId]);
            } elseif ($userId) {
                $sql = "
                    SELECT 
                        COUNT(DISTINCT r.ip_address) as total_ips,
                        AVG(r.trust_score) as avg_trust_score,
                        COUNT(CASE WHEN r.trust_score < 30 THEN 1 END) as low_trust_count,
                        COUNT(CASE WHEN r.trust_score >= 70 THEN 1 END) as high_trust_count,
                        COUNT(CASE WHEN r.is_blacklisted = 1 THEN 1 END) as blacklisted_count,
                        COUNT(CASE WHEN r.is_whitelisted = 1 THEN 1 END) as whitelisted_count
                    FROM safenode_ip_reputation r
                    INNER JOIN safenode_security_logs l ON l.ip_address = r.ip_address
                    INNER JOIN safenode_sites s ON s.id = l.site_id
                    WHERE s.user_id = ?
                    AND r.last_seen >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                ";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$userId]);
            } else {
                $stmt = $this->db->query($sql);
            }
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: [];
        } catch (PDOException $e) {
            error_log("SafeNode IPReputationManager Stats Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtém IPs com baixa reputação
     */
    public function getLowReputationIPs($limit = 10, $siteId = null) {
        if (!$this->db) return [];
        
        try {
            $sql = "
                SELECT * FROM safenode_ip_reputation
                WHERE trust_score < 30
                AND is_blacklisted = 0
                ORDER BY trust_score ASC, last_seen DESC
                LIMIT ?
            ";
            
            if ($siteId) {
                $sql = "
                    SELECT DISTINCT r.* FROM safenode_ip_reputation r
                    INNER JOIN safenode_security_logs l ON l.ip_address = r.ip_address
                    WHERE r.trust_score < 30
                    AND r.is_blacklisted = 0
                    AND l.site_id = ?
                    ORDER BY r.trust_score ASC, r.last_seen DESC
                    LIMIT ?
                ";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$siteId, $limit]);
            } else {
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$limit]);
            }
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log("SafeNode IPReputationManager Low Reputation Error: " . $e->getMessage());
            return [];
        }
    }
}





