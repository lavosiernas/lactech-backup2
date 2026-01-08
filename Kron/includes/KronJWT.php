<?php
/**
 * KRON - Gerenciador de JWT
 * Gera e valida tokens JWT para comunicação entre sistemas
 */

require_once __DIR__ . '/config.php';

class KronJWT
{
    private $secretKey;
    private $algorithm = 'HS256';
    
    public function __construct()
    {
        // Chave secreta deve estar em variável de ambiente ou config
        $this->secretKey = getenv('KRON_JWT_SECRET') ?: 'kron_jwt_secret_change_in_production_' . date('Y');
    }
    
    /**
     * Gera token JWT
     */
    public function generate($payload, $expiration = null)
    {
        $header = [
            'alg' => $this->algorithm,
            'typ' => 'JWT'
        ];
        
        // Adicionar timestamps padrão
        $payload['iat'] = time();
        if ($expiration) {
            $payload['exp'] = is_numeric($expiration) ? $expiration : strtotime($expiration);
        }
        
        $headerEncoded = $this->base64UrlEncode(json_encode($header));
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload));
        
        $signature = $this->sign($headerEncoded . '.' . $payloadEncoded);
        $signatureEncoded = $this->base64UrlEncode($signature);
        
        return $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;
    }
    
    /**
     * Valida e decodifica token JWT
     */
    public function validate($token)
    {
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            return ['valid' => false, 'error' => 'Token inválido'];
        }
        
        [$headerEncoded, $payloadEncoded, $signatureEncoded] = $parts;
        
        // Verificar assinatura
        $signature = $this->base64UrlDecode($signatureEncoded);
        $expectedSignature = $this->sign($headerEncoded . '.' . $payloadEncoded);
        
        if (!hash_equals($expectedSignature, $signature)) {
            return ['valid' => false, 'error' => 'Assinatura inválida'];
        }
        
        // Decodificar payload
        $payload = json_decode($this->base64UrlDecode($payloadEncoded), true);
        
        if (!$payload) {
            return ['valid' => false, 'error' => 'Payload inválido'];
        }
        
        // Verificar expiração
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return ['valid' => false, 'error' => 'Token expirado'];
        }
        
        return [
            'valid' => true,
            'payload' => $payload
        ];
    }
    
    /**
     * Gera token de sistema
     */
    public function generateSystemToken($systemId, $systemName, $scopes = [], $expiration = null)
    {
        $payload = [
            'iss' => 'kronx.sbs',
            'sub' => 'system_token',
            'system_id' => $systemId,
            'system_name' => $systemName,
            'scopes' => $scopes
        ];
        
        return $this->generate($payload, $expiration);
    }
    
    /**
     * Valida token de sistema
     */
    public function validateSystemToken($token)
    {
        $result = $this->validate($token);
        
        if (!$result['valid']) {
            return $result;
        }
        
        $payload = $result['payload'];
        
        // Verificar se é token de sistema
        if ($payload['sub'] !== 'system_token' || $payload['iss'] !== 'kronx.sbs') {
            return ['valid' => false, 'error' => 'Token não é de sistema'];
        }
        
        return [
            'valid' => true,
            'system_id' => $payload['system_id'] ?? null,
            'system_name' => $payload['system_name'] ?? null,
            'scopes' => $payload['scopes'] ?? []
        ];
    }
    
    /**
     * Verifica se token tem escopo necessário
     */
    public function hasScope($token, $requiredScope)
    {
        $result = $this->validateSystemToken($token);
        
        if (!$result['valid']) {
            return false;
        }
        
        $scopes = $result['scopes'] ?? [];
        
        // Verificar escopo exato ou wildcard
        return in_array($requiredScope, $scopes) || in_array('*', $scopes);
    }
    
    /**
     * Assina dados
     */
    private function sign($data)
    {
        return hash_hmac('sha256', $data, $this->secretKey, true);
    }
    
    /**
     * Codifica em Base64 URL-safe
     */
    private function base64UrlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Decodifica Base64 URL-safe
     */
    private function base64UrlDecode($data)
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}

