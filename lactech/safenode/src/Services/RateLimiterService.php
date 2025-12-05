<?php
/**
 * SafeNode - Rate Limiter Service
 * Serviço refatorado seguindo PSR-12
 */

namespace SafeNode\Services;

class RateLimiterService
{
    private $db;
    
    public function __construct($database)
    {
        $this->db = $database;
    }
    
    /**
     * Verifica se um IP excedeu o limite de requisições
     *
     * @param string $ipAddress Endereço IP a verificar
     * @param string|null $endpoint Endpoint específico (opcional)
     * @return array Array com informações sobre o rate limit
     */
    public function checkRateLimit(string $ipAddress, ?string $endpoint = null): array
    {
        if (!$this->db) {
            return ['allowed' => true, 'remaining' => 999];
        }
        
        try {
            $limits = $this->getActiveLimits();
            
            if (empty($limits)) {
                return ['allowed' => true, 'remaining' => 999];
            }
            
            foreach ($limits as $limit) {
                $window = (int)$limit['time_window'];
                $maxRequests = (int)$limit['max_requests'];
                
                $currentRequests = $this->countRequestsInWindow($ipAddress, $window);
                
                if ($currentRequests >= $maxRequests) {
                    $this->recordViolation($ipAddress, $limit['id']);
                    
                    return [
                        'allowed' => false,
                        'remaining' => 0,
                        'limit' => $maxRequests,
                        'window' => $window,
                        'reset_at' => date('Y-m-d H:i:s', strtotime("+{$window} seconds"))
                    ];
                }
            }
            
            return $this->calculateRemainingRequests($ipAddress, $limits[0]);
        } catch (\PDOException $e) {
            error_log("RateLimiterService Error: " . $e->getMessage());
            return ['allowed' => true, 'remaining' => 999];
        }
    }
    
    /**
     * Busca limites ativos
     *
     * @return array
     */
    private function getActiveLimits(): array
    {
        $stmt = $this->db->query("
            SELECT * FROM safenode_rate_limits 
            WHERE is_active = 1 
            ORDER BY priority DESC
        ");
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    /**
     * Conta requisições em uma janela de tempo
     *
     * @param string $ipAddress
     * @param int $window Janela em segundos
     * @return int
     */
    private function countRequestsInWindow(string $ipAddress, int $window): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM safenode_security_logs 
            WHERE ip_address = ? 
            AND created_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)
        ");
        $stmt->execute([$ipAddress, $window]);
        $result = $stmt->fetch();
        
        return (int)($result['count'] ?? 0);
    }
    
    /**
     * Calcula requisições restantes
     *
     * @param string $ipAddress
     * @param array $limit
     * @return array
     */
    private function calculateRemainingRequests(string $ipAddress, array $limit): array
    {
        $window = (int)$limit['time_window'];
        $maxRequests = (int)$limit['max_requests'];
        $currentRequests = $this->countRequestsInWindow($ipAddress, $window);
        
        return [
            'allowed' => true,
            'remaining' => max(0, $maxRequests - $currentRequests),
            'limit' => $maxRequests,
            'window' => $window
        ];
    }
    
    /**
     * Registra violação de rate limit
     *
     * @param string $ipAddress
     * @param int $limitId
     * @return void
     */
    private function recordViolation(string $ipAddress, int $limitId): void
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO safenode_rate_limits_violations 
                (ip_address, rate_limit_id, created_at) 
                VALUES (?, ?, NOW())
            ");
            $stmt->execute([$ipAddress, $limitId]);
        } catch (\PDOException $e) {
            error_log("RateLimiterService Violation Error: " . $e->getMessage());
        }
    }
}

