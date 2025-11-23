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
        
        // URL de callback
        // PRODUÇÃO: https://safenode.cloud/google-callback.php
        // LOCAL (dev): http://localhost/google-callback.php (adicionar no Google Console também)
        
        // Detectar ambiente
        $isLocal = (isset($_SERVER['HTTP_HOST']) && 
                    (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || 
                     strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false));
        
        if ($isLocal) {
            $this->redirectUri = 'http://localhost/google-callback.php';
        } else {
            $this->redirectUri = 'https://safenode.cloud/google-callback.php';
        }
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

