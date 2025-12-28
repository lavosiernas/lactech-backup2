<?php
/**
 * SafeNode - Security Token Manager
 * Gera e valida tokens de segurança para URLs
 */

class SecurityToken {
    private $db;
    private $secretKey;
    
    public function __construct($database = null) {
        $this->db = $database ?: getSafeNodeDatabase();
        
        // Chave secreta para assinatura dos tokens
        // Em produção, isso deve vir de variável de ambiente
        $this->secretKey = $_ENV['SAFENODE_TOKEN_SECRET'] ?? 'safenode_secret_key_change_in_production_' . md5(__DIR__);
    }
    
    /**
     * Gera um token de segurança para o usuário
     * Formato: {user_id}:{site_id}:{timestamp}:{hash}
     */
    public function generateToken($userId, $siteId = 0) {
        if (!$userId) {
            return null;
        }
        
        $timestamp = time();
        $data = [
            'user_id' => (int)$userId,
            'site_id' => (int)$siteId,
            'timestamp' => $timestamp
        ];
        
        // Criar hash de segurança
        $payload = base64_encode(json_encode($data));
        $signature = hash_hmac('sha256', $payload, $this->secretKey);
        
        // Token final: payload.signature (formato mais seguro)
        $token = $payload . '.' . substr($signature, 0, 32);
        
        // Codificar para URL (remover caracteres problemáticos)
        return rtrim(strtr(base64_encode($token), '+/', '-_'), '=');
    }
    
    /**
     * Valida um token de segurança
     */
    public function validateToken($token) {
        if (empty($token)) {
            return false;
        }
        
        try {
            // Decodificar token
            $decoded = base64_decode(strtr($token, '-_', '+/') . str_repeat('=', (4 - strlen($token) % 4) % 4));
            
            if (!$decoded) {
                return false;
            }
            
            // Separar payload e assinatura
            $parts = explode('.', $decoded);
            if (count($parts) !== 2) {
                return false;
            }
            
            list($payload, $signature) = $parts;
            
            // Verificar assinatura
            $expectedSignature = substr(hash_hmac('sha256', $payload, $this->secretKey), 0, 32);
            if (!hash_equals($expectedSignature, $signature)) {
                return false;
            }
            
            // Decodificar payload
            $data = json_decode(base64_decode($payload), true);
            if (!$data || !isset($data['user_id'])) {
                return false;
            }
            
            // Verificar expiração (tokens válidos por 24 horas)
            $maxAge = 24 * 60 * 60; // 24 horas
            if (isset($data['timestamp']) && (time() - $data['timestamp']) > $maxAge) {
                return false;
            }
            
            return $data;
            
        } catch (Exception $e) {
            error_log("Erro ao validar token: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Gera URL segura com token
     */
    public function generateSecureUrl($page, $userId, $siteId = 0) {
        $token = $this->generateToken($userId, $siteId);
        if (!$token) {
            return $page;
        }
        
        // Se a página já tem extensão, manter, senão adicionar .php
        $pagePath = strpos($page, '.php') !== false ? $page : $page . '.php';
        
        return $pagePath . '?token=' . $token;
    }
    
    /**
     * Valida token da URL atual e retorna dados do usuário
     */
    public function validateCurrentRequest() {
        $token = $_GET['token'] ?? null;
        
        if (!$token) {
            return false;
        }
        
        $data = $this->validateToken($token);
        
        if (!$data) {
            return false;
        }
        
        // Verificar se o usuário da sessão corresponde ao token
        $sessionUserId = $_SESSION['safenode_user_id'] ?? null;
        if ($sessionUserId && (int)$sessionUserId !== (int)$data['user_id']) {
            return false;
        }
        
        return $data;
    }
    
    /**
     * Atualiza token com novo site_id
     */
    public function updateTokenSite($currentToken, $newSiteId) {
        $data = $this->validateToken($currentToken);
        if (!$data) {
            return null;
        }
        
        return $this->generateToken($data['user_id'], $newSiteId);
    }
}

