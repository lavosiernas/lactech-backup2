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
        
        // Detectar o caminho base do projeto a partir do script atual
        // Se o script está em safenode2/google-auth.php, o callback deve estar em safenode2/google-callback.php
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $scriptPath = dirname($scriptName);
        $scriptPath = rtrim($scriptPath, '/');
        
        // Se o scriptPath estiver vazio ou for apenas '/', significa que está na raiz
        // Caso contrário, usar o caminho detectado
        if (empty($scriptPath) || $scriptPath === '/') {
            $scriptPath = '';
        }
        
        // Se estiver em produção (safenode.cloud), usar HTTPS e caminho correto
        if (strpos($host, 'safenode.cloud') !== false) {
            // Em produção, verificar se precisa do subdiretório
            // Se o script está em /safenode2/, usar /safenode2/google-callback.php
            // Se está na raiz, usar /google-callback.php
            $this->redirectUri = 'https://safenode.cloud' . $scriptPath . '/google-callback.php';
        } elseif ($isLocal) {
            // Localhost - usar HTTP e caminho completo
            // Exemplo: http://localhost/safenode2/google-callback.php
            $this->redirectUri = $protocol . '://' . $host . $scriptPath . '/google-callback.php';
        } else {
            // Produção genérica - usar HTTPS
            $this->redirectUri = 'https://' . $host . $scriptPath . '/google-callback.php';
        }
        
        // Garantir que não há espaços ou caracteres extras
        $this->redirectUri = trim($this->redirectUri);
        
        // Log para debug (remover em produção se necessário)
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
        curl_close($ch);
        
        if ($httpCode !== 200) {
            return null;
        }
        
        return json_decode($response, true);
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
            return null;
        }
        
        return json_decode($response, true);
    }
}

