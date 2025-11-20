<?php
/**
 * SafeNode - Rate Limiter
 * Sistema de controle de taxa de requisições
 */

class RateLimiter {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Verifica se um IP excedeu o limite de requisições
     */
    public function checkRateLimit($ipAddress, $endpoint = null) {
        if (!$this->db) return ['allowed' => true, 'remaining' => 999];
        
        try {
            // Buscar configurações de rate limit
            $stmt = $this->db->query("
                SELECT * FROM safenode_rate_limits 
                WHERE is_active = 1 
                ORDER BY priority DESC
            ");
            $limits = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($limits)) {
                return ['allowed' => true, 'remaining' => 999];
            }
            
            // Verificar cada limite
            foreach ($limits as $limit) {
                $window = (int)$limit['time_window'];
                $maxRequests = (int)$limit['max_requests'];
                
                // Contar requisições no período
                $stmt = $this->db->prepare("
                    SELECT COUNT(*) as count 
                    FROM safenode_security_logs 
                    WHERE ip_address = ? 
                    AND created_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)
                ");
                $stmt->execute([$ipAddress, $window]);
                $result = $stmt->fetch();
                $currentRequests = (int)($result['count'] ?? 0);
                
                if ($currentRequests >= $maxRequests) {
                    // Registrar violação
                    $this->recordViolation($ipAddress, $limit['id']);
                    
                    return [
                        'allowed' => false,
                        'remaining' => 0,
                        'limit' => $maxRequests,
                        'window' => $window,
                        'reset_at' => date('Y-m-d H:i:s', strtotime("+$window seconds"))
                    ];
                }
            }
            
            // Calcular requisições restantes
            $firstLimit = $limits[0];
            $window = (int)$firstLimit['time_window'];
            $maxRequests = (int)$firstLimit['max_requests'];
            
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count 
                FROM safenode_security_logs 
                WHERE ip_address = ? 
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)
            ");
            $stmt->execute([$ipAddress, $window]);
            $result = $stmt->fetch();
            $currentRequests = (int)($result['count'] ?? 0);
            
            return [
                'allowed' => true,
                'remaining' => max(0, $maxRequests - $currentRequests),
                'limit' => $maxRequests,
                'window' => $window
            ];
        } catch (PDOException $e) {
            error_log("SafeNode RateLimiter Error: " . $e->getMessage());
            return ['allowed' => true, 'remaining' => 999];
        }
    }
    
    /**
     * Registra violação de rate limit
     */
    private function recordViolation($ipAddress, $limitId) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO safenode_rate_limits_violations 
                (ip_address, rate_limit_id, created_at) 
                VALUES (?, ?, NOW())
            ");
            $stmt->execute([$ipAddress, $limitId]);
        } catch (PDOException $e) {
            error_log("SafeNode RateLimiter Violation Error: " . $e->getMessage());
        }
    }
}

