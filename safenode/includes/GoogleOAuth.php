<?php
/**
 * SafeNode - Google OAuth Integration
 */

class GoogleOAuth
{
    private $clientId;
    private $clientSecret;
    private $redirectUri;
    
    public function __construct()
    {
        // Configurações do Google OAuth - LacTech Project
        $this->clientId = '563053705449-sivcmt98k6150nnd6vj277b4jt10u1n7.apps.googleusercontent.com';
        $this->clientSecret = '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';
        
        // Detectar ambiente e construir URL de callback corretamente
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        
        // Detectar se é localhost
        $isLocal = (
            strpos($host, 'localhost') !== false || 
            strpos($host, '127.0.0.1') !== false ||
            strpos($host, '::1') !== false ||
            strpos($host, '192.168.') === 0 ||
            $host === 'localhost' ||
            $host === '127.0.0.1'
        );
        
        // Se estiver em produção (safenode.cloud), usar a URL exata configurada no Google Console
        if (strpos($host, 'safenode.cloud') !== false) {
            // Em produção, usar exatamente a URL configurada no Google Console
            // https://safenode.cloud/google-callback.php (na raiz do domínio)
            $this->redirectUri = 'https://safenode.cloud/google-callback.php';
        } elseif ($isLocal) {
            // Localhost - detectar caminho do script
            $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
            $scriptPath = dirname($scriptName);
            $scriptPath = rtrim($scriptPath, '/');
            
            // Se o scriptPath estiver vazio ou for apenas '/', significa que está na raiz
            if (empty($scriptPath) || $scriptPath === '/') {
                $scriptPath = '';
            }
            
            // Exemplo: http://localhost/safenode/google-callback.php
            $this->redirectUri = $protocol . '://' . $host . $scriptPath . '/google-callback.php';
        } else {
            // Produção genérica - detectar caminho do script
            $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
            $scriptPath = dirname($scriptName);
            $scriptPath = rtrim($scriptPath, '/');
            
            // Se o scriptPath estiver vazio ou for apenas '/', significa que está na raiz
            if (empty($scriptPath) || $scriptPath === '/') {
                $scriptPath = '';
            }
            
            // Produção genérica - usar HTTPS
            $this->redirectUri = 'https://' . $host . $scriptPath . '/google-callback.php';
        }
        
        // Garantir que não há espaços ou caracteres extras
        $this->redirectUri = trim($this->redirectUri);
        
        // Log para debug
        error_log("SafeNode Google OAuth - Host: $host");
        error_log("SafeNode Google OAuth - Protocol: $protocol");
        error_log("SafeNode Google OAuth - IsLocal: " . ($isLocal ? 'true' : 'false'));
        error_log("SafeNode Google OAuth - redirect_uri configurado: " . $this->redirectUri);
    }
    
    /**
     * Gera a URL de autenticação do Google
     */
    public function getAuthUrl($state = null)
    {
        $params = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => 'email profile',
            'access_type' => 'online',
            'prompt' => 'select_account'
        ];
        
        if ($state) {
            $params['state'] = $state;
        }
        
        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
    }
    
    /**
     * Troca o código de autorização por um token de acesso
     */
    public function getAccessToken($code)
    {
        $tokenUrl = 'https://oauth2.googleapis.com/token';
        
        $params = [
            'code' => $code,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUri,
            'grant_type' => 'authorization_code'
        ];
        
        $ch = curl_init($tokenUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            error_log("SafeNode Google OAuth - Erro ao obter token.");
            error_log("HTTP Code: $httpCode");
            error_log("Response: $response");
            error_log("CURL Error: $curlError");
            error_log("Redirect URI usado: " . $this->redirectUri);
            return null;
        }
        
        $tokenData = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("SafeNode Google OAuth - Erro ao decodificar JSON: " . json_last_error_msg());
            error_log("Response raw: $response");
            return null;
        }
        
        return $tokenData;
    }
    
    /**
     * Obtém informações do usuário do Google
     */
    public function getUserInfo($accessToken)
    {
        $userInfoUrl = 'https://www.googleapis.com/oauth2/v2/userinfo';
        
        $ch = curl_init($userInfoUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            error_log("SafeNode Google OAuth - Erro ao obter informações do usuário. HTTP Code: $httpCode");
            return null;
        }
        
        return json_decode($response, true);
    }
}

