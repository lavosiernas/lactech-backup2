<?php
/**
 * SafeNode Human Verification SDK - PHP
 * 
 * SDK oficial do SafeNode para integração em aplicações PHP
 * 
 * @package SafeNode
 * @version 1.0.0
 * @license MIT
 */

class SafeNodeHV {
    private $apiBaseUrl;
    private $apiKey;
    private $token = null;
    private $nonce = null;
    private $initialized = false;
    private $maxRetries = 3;
    private $retryDelay = 1000; // ms
    private $tokenMaxAge = 3600; // segundos
    private $initTime = null;
    
    /**
     * Construtor
     * 
     * @param string $apiBaseUrl URL base da API (ex: https://safenode.cloud/api/sdk)
     * @param string $apiKey Chave de API do SafeNode
     * @param array $options Opções adicionais
     */
    public function __construct($apiBaseUrl, $apiKey, $options = []) {
        $this->apiBaseUrl = rtrim($apiBaseUrl, '/');
        $this->apiKey = $apiKey;
        
        if (isset($options['max_retries'])) {
            $this->maxRetries = (int)$options['max_retries'];
        }
        
        if (isset($options['retry_delay'])) {
            $this->retryDelay = (int)$options['retry_delay'];
        }
        
        if (isset($options['token_max_age'])) {
            $this->tokenMaxAge = (int)$options['token_max_age'];
        }
    }
    
    /**
     * Inicializa o SDK e obtém o token de verificação
     * 
     * @param int $retryCount Contador interno para retry
     * @return bool True se inicializado com sucesso
     * @throws Exception Em caso de erro
     */
    public function init($retryCount = 0) {
        if (empty($this->apiKey)) {
            throw new Exception('API key é obrigatória');
        }
        
        try {
            $url = $this->apiBaseUrl . '/init.php?api_key=' . urlencode($this->apiKey);
            
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json',
                    'X-API-Key: ' . $this->apiKey
                ],
                CURLOPT_TIMEOUT => 10,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_FOLLOWLOCATION => true
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                throw new Exception('Erro de conexão: ' . $error);
            }
            
            if ($httpCode === 429) {
                throw new Exception('Rate limit excedido. Tente novamente em alguns instantes.');
            }
            
            if ($httpCode !== 200) {
                $errorData = json_decode($response, true) ?? [];
                throw new Exception($errorData['error'] ?? 'Erro ao inicializar verificação');
            }
            
            $data = json_decode($response, true);
            
            if (isset($data['success']) && $data['success'] && isset($data['token'])) {
                $this->token = $data['token'];
                $this->nonce = $data['nonce'] ?? '';
                $this->tokenMaxAge = ($data['max_age'] ?? 3600);
                $this->initTime = time();
                $this->initialized = true;
                
                return true;
            } else {
                throw new Exception('Token não recebido');
            }
        } catch (Exception $e) {
            // Retry automático em caso de erro de rede
            if ($retryCount < $this->maxRetries && (
                strpos($e->getMessage(), 'Erro de conexão') !== false ||
                strpos($e->getMessage(), 'timeout') !== false
            )) {
                usleep($this->retryDelay * 1000 * ($retryCount + 1));
                return $this->init($retryCount + 1);
            }
            
            $this->initialized = false;
            throw $e;
        }
    }
    
    /**
     * Verifica se o token ainda é válido
     * 
     * @return bool
     */
    private function isTokenValid() {
        if (!$this->initTime || !$this->token) {
            return false;
        }
        
        $age = time() - $this->initTime;
        return $age < $this->tokenMaxAge;
    }
    
    /**
     * Valida a verificação humana
     * 
     * @param int $retryCount Contador interno para retry
     * @return array Resultado da validação
     * @throws Exception Em caso de erro
     */
    public function validate($retryCount = 0) {
        if (!$this->initialized || !$this->token) {
            if ($this->token && !$this->isTokenValid()) {
                $this->init();
            } else {
                throw new Exception('SDK não inicializado. Chame init() primeiro.');
            }
        }
        
        if (!$this->isTokenValid()) {
            $this->init();
        }
        
        try {
            $payload = json_encode([
                'token' => $this->token,
                'nonce' => $this->nonce ?? '',
                'js_enabled' => '1',
                'api_key' => $this->apiKey
            ]);
            
            $ch = curl_init($this->apiBaseUrl . '/validate.php');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Accept: application/json',
                    'X-API-Key: ' . $this->apiKey
                ],
                CURLOPT_TIMEOUT => 10,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_SSL_VERIFYPEER => true
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                throw new Exception('Erro de conexão: ' . $error);
            }
            
            $data = json_decode($response, true);
            
            if ($httpCode === 200 && isset($data['success']) && $data['success']) {
                return [
                    'valid' => true,
                    'message' => $data['message'] ?? 'Verificação válida'
                ];
            } else {
                throw new Exception($data['error'] ?? 'Validação falhou');
            }
        } catch (Exception $e) {
            if ($retryCount < $this->maxRetries) {
                usleep($this->retryDelay * 1000 * ($retryCount + 1));
                return $this->validate($retryCount + 1);
            }
            
            throw $e;
        }
    }
    
    /**
     * Verifica se o SDK está inicializado
     * 
     * @return bool
     */
    public function isInitialized() {
        return $this->initialized;
    }
    
    /**
     * Obtém o token atual
     * 
     * @return string|null
     */
    public function getToken() {
        return $this->token;
    }
}







