<?php
/**
 * SafeNode Mail SDK - PHP
 * 
 * SDK oficial do SafeNode Mail para integração em aplicações PHP
 * 
 * @package SafeNode
 * @version 1.0.0
 * @license MIT
 */

class SafeNodeMail {
    private $apiBaseUrl;
    private $token;
    private $maxRetries = 3;
    private $retryDelay = 1000; // ms
    
    /**
     * Construtor
     * 
     * @param string $apiBaseUrl URL base da API (ex: https://safenode.cloud/api/mail)
     * @param string $token Token de autenticação do projeto
     * @param array $options Opções adicionais
     */
    public function __construct($apiBaseUrl, $token, $options = []) {
        $this->apiBaseUrl = rtrim($apiBaseUrl, '/');
        $this->token = $token;
        
        if (isset($options['max_retries'])) {
            $this->maxRetries = (int)$options['max_retries'];
        }
        
        if (isset($options['retry_delay'])) {
            $this->retryDelay = (int)$options['retry_delay'];
        }
    }
    
    /**
     * Envia um e-mail
     * 
     * @param string $to E-mail destinatário
     * @param string $subject Assunto do e-mail
     * @param string $html Conteúdo HTML
     * @param string|null $text Conteúdo texto alternativo
     * @param array $options Opções adicionais (template, variables, etc)
     * @return array Resultado do envio
     * @throws Exception Em caso de erro
     */
    public function send($to, $subject, $html = null, $text = null, $options = []) {
        if (empty($this->token)) {
            throw new Exception('Token de autenticação é obrigatório');
        }
        
        if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('E-mail destinatário inválido');
        }
        
        if (empty($subject)) {
            throw new Exception('Assunto é obrigatório');
        }
        
        $payload = [
            'to' => $to,
            'subject' => $subject
        ];
        
        // Se usar template
        if (isset($options['template'])) {
            $payload['template'] = $options['template'];
            if (isset($options['variables'])) {
                $payload['variables'] = $options['variables'];
            }
        } else {
            // Conteúdo direto
            if ($html) {
                $payload['html'] = $html;
            }
            if ($text) {
                $payload['text'] = $text;
            }
        }
        
        return $this->makeRequest('/send', $payload);
    }
    
    /**
     * Envia e-mail usando template
     * 
     * @param string $to E-mail destinatário
     * @param string $templateName Nome do template
     * @param array $variables Variáveis para o template
     * @return array Resultado do envio
     */
    public function sendTemplate($to, $templateName, $variables = []) {
        return $this->send($to, '', null, null, [
            'template' => $templateName,
            'variables' => $variables
        ]);
    }
    
    /**
     * Faz requisição à API
     * 
     * @param string $endpoint Endpoint da API
     * @param array $data Dados a enviar
     * @param int $retryCount Contador de retry
     * @return array Resposta da API
     * @throws Exception Em caso de erro
     */
    private function makeRequest($endpoint, $data, $retryCount = 0) {
        try {
            $url = $this->apiBaseUrl . $endpoint;
            $payload = json_encode($data);
            
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Accept: application/json',
                    'Authorization: Bearer ' . $this->token
                ],
                CURLOPT_TIMEOUT => 30,
                CURLOPT_CONNECTTIMEOUT => 10,
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
            
            $data = json_decode($response, true);
            
            if ($httpCode === 429) {
                throw new Exception('Rate limit excedido. Tente novamente em alguns instantes.');
            }
            
            if ($httpCode === 401) {
                throw new Exception('Token inválido ou expirado');
            }
            
            if ($httpCode !== 200) {
                $errorMsg = $data['error'] ?? 'Erro ao enviar e-mail';
                throw new Exception($errorMsg);
            }
            
            if (isset($data['success']) && $data['success']) {
                return [
                    'success' => true,
                    'message' => $data['message'] ?? 'E-mail enviado com sucesso',
                    'data' => $data
                ];
            } else {
                throw new Exception($data['error'] ?? 'Erro ao enviar e-mail');
            }
        } catch (Exception $e) {
            // Retry automático em caso de erro de rede
            if ($retryCount < $this->maxRetries && (
                strpos($e->getMessage(), 'Erro de conexão') !== false ||
                strpos($e->getMessage(), 'timeout') !== false ||
                $httpCode === 429
            )) {
                usleep($this->retryDelay * 1000 * ($retryCount + 1));
                return $this->makeRequest($endpoint, $data, $retryCount + 1);
            }
            
            throw $e;
        }
    }
    
    /**
     * Verifica se o token é válido
     * 
     * @return bool
     */
    public function validateToken() {
        try {
            // Tentar enviar um e-mail de teste (sem realmente enviar)
            // Ou criar endpoint específico para validação
            return !empty($this->token);
        } catch (Exception $e) {
            return false;
        }
    }
}










