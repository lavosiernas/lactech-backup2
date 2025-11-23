<?php
/**
 * SafeNode - Cloudflare API Integration
 * Integração com API do Cloudflare para regras de firewall e DNS
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
     * Verifica o token e retorna detalhes do usuário/conta
     */
    public function verifyToken() {
        return $this->makeRequest('user/tokens/verify', 'GET');
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
     * Obtém detalhes da Zona (Site)
     */
    public function getZoneDetails($zoneId) {
        if (!$this->apiToken || !$zoneId) {
            return ['success' => false, 'error' => 'API Token ou Zone ID não configurado'];
        }
        return $this->makeRequest("zones/$zoneId", 'GET');
    }

    /**
     * Lista registros DNS
     */
    public function listDNSRecords($zoneId, $type = null, $name = null, $page = 1, $perPage = 50) {
        if (!$this->apiToken || !$zoneId) {
            return ['success' => false, 'error' => 'API Token ou Zone ID não configurado'];
        }

        $query = [
            'page' => $page,
            'per_page' => $perPage
        ];

        if ($type) $query['type'] = $type;
        if ($name) $query['name'] = $name;

        $queryString = http_build_query($query);
        return $this->makeRequest("zones/$zoneId/dns_records?$queryString", 'GET');
    }

    /**
     * Cria um registro DNS
     */
    public function createDNSRecord($zoneId, $type, $name, $content, $ttl = 1, $proxied = true) {
        if (!$this->apiToken || !$zoneId) {
            return ['success' => false, 'error' => 'API Token ou Zone ID não configurado'];
        }

        $data = [
            'type' => $type,
            'name' => $name,
            'content' => $content,
            'ttl' => (int)$ttl,
            'proxied' => (bool)$proxied
        ];

        return $this->makeRequest("zones/$zoneId/dns_records", 'POST', $data);
    }

    /**
     * Atualiza um registro DNS
     */
    public function updateDNSRecord($zoneId, $recordId, $type, $name, $content, $ttl = 1, $proxied = true) {
        if (!$this->apiToken || !$zoneId) {
            return ['success' => false, 'error' => 'API Token ou Zone ID não configurado'];
        }

        $data = [
            'type' => $type,
            'name' => $name,
            'content' => $content,
            'ttl' => (int)$ttl,
            'proxied' => (bool)$proxied
        ];

        return $this->makeRequest("zones/$zoneId/dns_records/$recordId", 'PUT', $data);
    }

    /**
     * Remove um registro DNS
     */
    public function deleteDNSRecord($zoneId, $recordId) {
        if (!$this->apiToken || !$zoneId) {
            return ['success' => false, 'error' => 'API Token ou Zone ID não configurado'];
        }

        return $this->makeRequest("zones/$zoneId/dns_records/$recordId", 'DELETE');
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
            CURLOPT_TIMEOUT => 30
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
