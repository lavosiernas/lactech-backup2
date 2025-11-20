<?php
/**
 * SafeNode - Cloudflare API Integration
 * Integração com API do Cloudflare para regras de firewall
 */

class CloudflareAPI {
    private $apiToken;
    private $apiUrl = 'https://api.cloudflare.com/client/v4';
    
    public function __construct($apiToken = null) {
        // Prioridade: parâmetro > constante > variável de ambiente
        $this->apiToken = $apiToken 
            ?? (defined('CLOUDFLARE_API_TOKEN') ? CLOUDFLARE_API_TOKEN : null)
            ?? $_ENV['CLOUDFLARE_API_TOKEN'] 
            ?? getenv('CLOUDFLARE_API_TOKEN')
            ?? null;
    }
    
    /**
     * Cria uma regra de firewall no Cloudflare
     */
    public function createFirewallRule($zoneId, $ipAddress, $action = 'block', $description = 'SafeNode Auto-Block') {
        if (!$this->apiToken || !$zoneId) {
            return ['success' => false, 'error' => 'API Token ou Zone ID não configurado'];
        }
        
        $rule = [
            'action' => $action,
            'priority' => 1000,
            'paused' => false,
            'description' => $description,
            'filter' => [
                'expression' => "(ip.src eq $ipAddress)",
                'paused' => false
            ]
        ];
        
        return $this->makeRequest("zones/$zoneId/firewall/rules", 'POST', $rule);
    }
    
    /**
     * Remove uma regra de firewall do Cloudflare
     */
    public function deleteFirewallRule($zoneId, $ruleId) {
        if (!$this->apiToken || !$zoneId) {
            return ['success' => false, 'error' => 'API Token ou Zone ID não configurado'];
        }
        
        return $this->makeRequest("zones/$zoneId/firewall/rules/$ruleId", 'DELETE');
    }
    
    /**
     * Lista regras de firewall
     */
    public function listFirewallRules($zoneId) {
        if (!$this->apiToken || !$zoneId) {
            return ['success' => false, 'error' => 'API Token ou Zone ID não configurado'];
        }
        
        return $this->makeRequest("zones/$zoneId/firewall/rules", 'GET');
    }
    
    /**
     * Faz requisição à API do Cloudflare
     */
    private function makeRequest($endpoint, $method = 'GET', $data = null) {
        $url = $this->apiUrl . '/' . $endpoint;
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiToken,
                'Content-Type: application/json'
            ],
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => 10
        ]);
        
        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return ['success' => false, 'error' => $error];
        }
        
        $decoded = json_decode($response, true);
        
        return [
            'success' => $httpCode >= 200 && $httpCode < 300,
            'http_code' => $httpCode,
            'data' => $decoded,
            'error' => $decoded['errors'] ?? null
        ];
    }
}

