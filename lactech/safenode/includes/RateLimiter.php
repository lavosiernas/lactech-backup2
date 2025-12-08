<?php
/**
 * SafeNode - Rate Limiter
 * Sistema de controle de taxa de requisições
 */

class RateLimiter {
    private $db;
    private $cache;
    
    public function __construct($database) {
        $this->db = $database;
        require_once __DIR__ . '/CacheManager.php';
        $this->cache = CacheManager::getInstance();
    }
    
    /**
     * Verifica se um IP excedeu o limite de requisições (COM CACHE)
     */
    public function checkRateLimit($ipAddress, $endpoint = null) {
        if (!$this->db) return ['allowed' => true, 'remaining' => 999];
        
        try {
            // Buscar configurações de rate limit (cachear por 30 minutos)
            $cacheKey = "rate_limits:config";
            $limits = $this->cache->get($cacheKey);
            
            if ($limits === null) {
                $stmt = $this->db->query("
                    SELECT * FROM safenode_rate_limits 
                    WHERE is_active = 1 
                    ORDER BY priority DESC
                ");
                $limits = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Salvar no cache
                $this->cache->set($cacheKey, $limits, CacheManager::TTL_SITE_CONFIG);
            }
            
            if (empty($limits)) {
                return ['allowed' => true, 'remaining' => 999];
            }
            
            // Verificar cada limite usando cache para contadores
            foreach ($limits as $limit) {
                $window = (int)$limit['time_window'];
                $maxRequests = (int)$limit['max_requests'];
                
                // Usar cache para contador (muito mais rápido que query no banco)
                $counterKey = "rate_limit:{$ipAddress}:{$limit['id']}";
                $currentRequests = $this->cache->increment($counterKey, 1, $window);
                
                // Se é a primeira requisição neste período, o increment retorna 1
                // Se já existe, incrementa e mantém TTL
                
                if ($currentRequests >= $maxRequests) {
                    // Registrar violação (assíncrono seria melhor, mas manter síncrono por enquanto)
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
            
            // Calcular requisições restantes do primeiro limite
            $firstLimit = $limits[0];
            $window = (int)$firstLimit['time_window'];
            $maxRequests = (int)$firstLimit['max_requests'];
            $counterKey = "rate_limit:{$ipAddress}:{$firstLimit['id']}";
            $currentRequests = $this->cache->get($counterKey) ?: 0;
            
            return [
                'allowed' => true,
                'remaining' => max(0, $maxRequests - $currentRequests),
                'limit' => $maxRequests,
                'window' => $window
            ];
        } catch (PDOException $e) {
            error_log("SafeNode RateLimiter Error: " . $e->getMessage());
            return ['allowed' => true, 'remaining' => 999];
        } catch (Exception $e) {
            error_log("SafeNode RateLimiter Cache Error: " . $e->getMessage());
            // Fallback: usar método antigo sem cache
            return $this->checkRateLimitFallback($ipAddress);
        }
    }
    
    /**
     * Fallback para verificação de rate limit sem cache (método antigo)
     */
    private function checkRateLimitFallback($ipAddress) {
        try {
            $stmt = $this->db->query("
                SELECT * FROM safenode_rate_limits 
                WHERE is_active = 1 
                ORDER BY priority DESC
            ");
            $limits = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($limits)) {
                return ['allowed' => true, 'remaining' => 999];
            }
            
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
            
            if ($currentRequests >= $maxRequests) {
                return [
                    'allowed' => false,
                    'remaining' => 0,
                    'limit' => $maxRequests,
                    'window' => $window
                ];
            }
            
            return [
                'allowed' => true,
                'remaining' => max(0, $maxRequests - $currentRequests),
                'limit' => $maxRequests,
                'window' => $window
            ];
        } catch (PDOException $e) {
            error_log("SafeNode RateLimiter Fallback Error: " . $e->getMessage());
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




