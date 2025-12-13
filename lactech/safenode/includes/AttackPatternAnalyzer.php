<?php
/**
 * SafeNode - Attack Pattern Analyzer
 * Análise de padrões de ataque em tempo real
 * Detecta ataques coordenados e padrões suspeitos
 */

class AttackPatternAnalyzer {
    private $db;
    private $cache;
    
    public function __construct($database) {
        $this->db = $database;
        require_once __DIR__ . '/CacheManager.php';
        $this->cache = CacheManager::getInstance();
    }
    
    /**
     * Analisa padrões de ataque em tempo real
     * 
     * @param int $timeWindow Janela de tempo em segundos (padrão: 300 = 5 minutos)
     * @return array Padrões detectados
     */
    public function analyzeAttackPatterns($timeWindow = 300) {
        if (!$this->db) return [];
        
        $patterns = [
            'coordinated_attack' => $this->detectCoordinatedAttack($timeWindow),
            'reconnaissance' => $this->detectReconnaissance($timeWindow),
            'distributed_ddos' => $this->detectDistributedDDoS($timeWindow),
            'privilege_escalation' => $this->detectPrivilegeEscalation($timeWindow),
            'targeted_attack' => $this->detectTargetedAttack($timeWindow)
        ];
        
        return array_filter($patterns, function($pattern) {
            return $pattern !== null && $pattern['detected'] === true;
        });
    }
    
    /**
     * Detecta ataque coordenado (múltiplos IPs atacando mesmo endpoint)
     */
    private function detectCoordinatedAttack($timeWindow) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    request_uri,
                    COUNT(DISTINCT ip_address) as unique_attackers,
                    COUNT(*) as total_attacks,
                    AVG(threat_score) as avg_threat_score,
                    MAX(threat_score) as max_threat_score
                FROM safenode_security_logs
                WHERE action_taken = 'blocked'
                AND threat_score >= 50
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)
                GROUP BY request_uri
                HAVING unique_attackers >= 5 AND total_attacks >= 20
                ORDER BY total_attacks DESC
                LIMIT 10
            ");
            $stmt->execute([$timeWindow]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($results)) {
                $mostAttacked = $results[0];
                return [
                    'detected' => true,
                    'type' => 'coordinated_attack',
                    'severity' => 'high',
                    'target' => $mostAttacked['request_uri'],
                    'unique_attackers' => (int)$mostAttacked['unique_attackers'],
                    'total_attacks' => (int)$mostAttacked['total_attacks'],
                    'avg_threat_score' => round((float)$mostAttacked['avg_threat_score'], 2),
                    'description' => "Múltiplos IPs ({$mostAttacked['unique_attackers']}) atacando o mesmo endpoint"
                ];
            }
        } catch (PDOException $e) {
            error_log("SafeNode AttackPattern Coordinated Error: " . $e->getMessage());
        }
        
        return ['detected' => false];
    }
    
    /**
     * Detecta reconhecimento (sequência de requisições suspeitas)
     */
    private function detectReconnaissance($timeWindow) {
        try {
            // Padrões de reconhecimento: múltiplas requisições a endpoints diferentes do mesmo IP
            $stmt = $this->db->prepare("
                SELECT 
                    ip_address,
                    COUNT(DISTINCT request_uri) as unique_endpoints,
                    COUNT(*) as total_requests,
                    GROUP_CONCAT(DISTINCT request_uri ORDER BY request_uri SEPARATOR ', ') as endpoints
                FROM safenode_security_logs
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)
                AND request_uri NOT LIKE '%/api/%'
                AND request_uri NOT LIKE '%.css'
                AND request_uri NOT LIKE '%.js'
                AND request_uri NOT LIKE '%.png'
                AND request_uri NOT LIKE '%.jpg'
                GROUP BY ip_address
                HAVING unique_endpoints >= 10 AND total_requests >= 15
                ORDER BY unique_endpoints DESC
                LIMIT 10
            ");
            $stmt->execute([$timeWindow]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($results)) {
                $recon = $results[0];
                return [
                    'detected' => true,
                    'type' => 'reconnaissance',
                    'severity' => 'medium',
                    'ip_address' => $recon['ip_address'],
                    'unique_endpoints' => (int)$recon['unique_endpoints'],
                    'total_requests' => (int)$recon['total_requests'],
                    'description' => "IP {$recon['ip_address']} acessou {$recon['unique_endpoints']} endpoints diferentes (possível reconhecimento)"
                ];
            }
        } catch (PDOException $e) {
            error_log("SafeNode AttackPattern Reconnaissance Error: " . $e->getMessage());
        }
        
        return ['detected' => false];
    }
    
    /**
     * Detecta DDoS distribuído (baixa intensidade)
     */
    private function detectDistributedDDoS($timeWindow) {
        try {
            // Múltiplos IPs fazendo muitas requisições em pouco tempo
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(DISTINCT ip_address) as unique_ips,
                    COUNT(*) as total_requests,
                    COUNT(DISTINCT request_uri) as unique_endpoints,
                    AVG(threat_score) as avg_threat_score
                FROM safenode_security_logs
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)
                GROUP BY DATE_FORMAT(created_at, '%Y-%m-%d %H:%i')
                HAVING unique_ips >= 20 AND total_requests >= 100
                ORDER BY total_requests DESC
                LIMIT 1
            ");
            $stmt->execute([$timeWindow]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result && (int)$result['total_requests'] > 0) {
                return [
                    'detected' => true,
                    'type' => 'distributed_ddos',
                    'severity' => 'high',
                    'unique_ips' => (int)$result['unique_ips'],
                    'total_requests' => (int)$result['total_requests'],
                    'requests_per_second' => round((int)$result['total_requests'] / $timeWindow, 2),
                    'description' => "Possível DDoS distribuído: {$result['unique_ips']} IPs, {$result['total_requests']} requisições"
                ];
            }
        } catch (PDOException $e) {
            error_log("SafeNode AttackPattern DDoS Error: " . $e->getMessage());
        }
        
        return ['detected' => false];
    }
    
    /**
     * Detecta escalação de privilégios (tentativas progressivas)
     */
    private function detectPrivilegeEscalation($timeWindow) {
        try {
            // IP tentando acessar endpoints administrativos progressivamente
            $adminPaths = ['admin', 'login', 'wp-admin', 'phpmyadmin', 'config', 'backup'];
            
            $stmt = $this->db->prepare("
                SELECT 
                    ip_address,
                    COUNT(DISTINCT CASE 
                        WHEN request_uri LIKE '%admin%' OR 
                             request_uri LIKE '%login%' OR 
                             request_uri LIKE '%wp-admin%' OR
                             request_uri LIKE '%phpmyadmin%' OR
                             request_uri LIKE '%config%' OR
                             request_uri LIKE '%backup%'
                        THEN request_uri 
                    END) as admin_endpoints_attempted,
                    COUNT(*) as total_attempts,
                    MAX(threat_score) as max_threat_score
                FROM safenode_security_logs
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)
                AND (request_uri LIKE '%admin%' OR 
                     request_uri LIKE '%login%' OR 
                     request_uri LIKE '%wp-admin%' OR
                     request_uri LIKE '%phpmyadmin%' OR
                     request_uri LIKE '%config%' OR
                     request_uri LIKE '%backup%')
                GROUP BY ip_address
                HAVING admin_endpoints_attempted >= 3 AND total_attempts >= 5
                ORDER BY admin_endpoints_attempted DESC
                LIMIT 10
            ");
            $stmt->execute([$timeWindow]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($results)) {
                $escalation = $results[0];
                return [
                    'detected' => true,
                    'type' => 'privilege_escalation',
                    'severity' => 'high',
                    'ip_address' => $escalation['ip_address'],
                    'admin_endpoints' => (int)$escalation['admin_endpoints_attempted'],
                    'total_attempts' => (int)$escalation['total_attempts'],
                    'max_threat_score' => (int)$escalation['max_threat_score'],
                    'description' => "IP {$escalation['ip_address']} tentou acessar {$escalation['admin_endpoints_attempted']} endpoints administrativos diferentes"
                ];
            }
        } catch (PDOException $e) {
            error_log("SafeNode AttackPattern Escalation Error: " . $e->getMessage());
        }
        
        return ['detected' => false];
    }
    
    /**
     * Detecta ataque direcionado (mesmo IP, múltiplos tipos de ameaça)
     */
    private function detectTargetedAttack($timeWindow) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    ip_address,
                    COUNT(DISTINCT threat_type) as threat_types_count,
                    COUNT(*) as total_attacks,
                    GROUP_CONCAT(DISTINCT threat_type ORDER BY threat_type SEPARATOR ', ') as threat_types,
                    AVG(threat_score) as avg_threat_score,
                    MAX(threat_score) as max_threat_score
                FROM safenode_security_logs
                WHERE action_taken = 'blocked'
                AND threat_type IS NOT NULL
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)
                GROUP BY ip_address
                HAVING threat_types_count >= 3 AND total_attacks >= 10
                ORDER BY threat_types_count DESC, total_attacks DESC
                LIMIT 10
            ");
            $stmt->execute([$timeWindow]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($results)) {
                $targeted = $results[0];
                return [
                    'detected' => true,
                    'type' => 'targeted_attack',
                    'severity' => 'high',
                    'ip_address' => $targeted['ip_address'],
                    'threat_types_count' => (int)$targeted['threat_types_count'],
                    'total_attacks' => (int)$targeted['total_attacks'],
                    'threat_types' => $targeted['threat_types'],
                    'avg_threat_score' => round((float)$targeted['avg_threat_score'], 2),
                    'description' => "IP {$targeted['ip_address']} executou {$targeted['threat_types_count']} tipos diferentes de ataque"
                ];
            }
        } catch (PDOException $e) {
            error_log("SafeNode AttackPattern Targeted Error: " . $e->getMessage());
        }
        
        return ['detected' => false];
    }
    
    /**
     * Obtém estatísticas de padrões de ataque
     */
    public function getPatternStats($days = 7) {
        if (!$this->db) return [];
        
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    DATE(created_at) as date,
                    COUNT(DISTINCT ip_address) as unique_attackers,
                    COUNT(*) as total_attacks,
                    COUNT(DISTINCT threat_type) as unique_threat_types
                FROM safenode_security_logs
                WHERE action_taken = 'blocked'
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY DATE(created_at)
                ORDER BY date DESC
            ");
            $stmt->execute([$days]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            return [];
        }
    }
}






