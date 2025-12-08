<?php
/**
 * SafeNode - Security Service
 * Serviço para lógica de negócio relacionada à segurança
 */

namespace SafeNode\Services;

use SafeNode\Models\SiteModel;

class SecurityService
{
    private $db;
    private $siteModel;
    private $rateLimiter;
    private $ipBlocker;
    
    public function __construct($database)
    {
        $this->db = $database;
        $this->siteModel = new SiteModel($database);
        
        require_once __DIR__ . '/../../includes/RateLimiter.php';
        require_once __DIR__ . '/../../includes/IPBlocker.php';
        
        $this->rateLimiter = new \RateLimiter($database);
        $this->ipBlocker = new \IPBlocker($database);
    }
    
    /**
     * Verifica se uma requisição deve ser bloqueada
     */
    public function shouldBlockRequest(string $ipAddress, ?string $endpoint = null): array
    {
        // Verificar whitelist primeiro
        if ($this->ipBlocker->isWhitelisted($ipAddress)) {
            return [
                'blocked' => false,
                'reason' => 'whitelisted'
            ];
        }
        
        // Verificar se IP está bloqueado
        if ($this->ipBlocker->isBlocked($ipAddress)) {
            return [
                'blocked' => true,
                'reason' => 'ip_blocked'
            ];
        }
        
        // Verificar rate limit
        $rateLimit = $this->rateLimiter->checkRateLimit($ipAddress, $endpoint);
        if (!$rateLimit['allowed']) {
            return [
                'blocked' => true,
                'reason' => 'rate_limit_exceeded',
                'details' => $rateLimit
            ];
        }
        
        return [
            'blocked' => false,
            'reason' => 'allowed'
        ];
    }
    
    /**
     * Processa uma requisição e decide a ação
     */
    public function processRequest(string $ipAddress, string $userAgent, ?string $endpoint = null): array
    {
        $blockCheck = $this->shouldBlockRequest($ipAddress, $endpoint);
        
        if ($blockCheck['blocked']) {
            // Registrar bloqueio
            $this->logSecurityEvent($ipAddress, $userAgent, $blockCheck['reason'], 'blocked');
            
            return [
                'action' => 'block',
                'reason' => $blockCheck['reason'],
                'details' => $blockCheck['details'] ?? null
            ];
        }
        
        // Registrar requisição permitida
        $this->logSecurityEvent($ipAddress, $userAgent, 'allowed', 'allowed');
        
        return [
            'action' => 'allow',
            'rateLimit' => $this->rateLimiter->checkRateLimit($ipAddress, $endpoint)
        ];
    }
    
    /**
     * Registra evento de segurança
     */
    private function logSecurityEvent(string $ipAddress, string $userAgent, string $reason, string $action): void
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO safenode_security_logs 
                (ip_address, user_agent, threat_type, action_taken, created_at) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$ipAddress, $userAgent, $reason, $action]);
        } catch (\PDOException $e) {
            error_log("SecurityService::logSecurityEvent Error: " . $e->getMessage());
        }
    }
}



