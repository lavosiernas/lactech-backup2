<?php
/**
 * LacTech - Conector KRON
 */

class KronConnector
{
    private $kronApiUrl;
    
    public function __construct()
    {
        // Detectar ambiente
        $isLocal = (isset($_SERVER['HTTP_HOST']) && 
                    (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || 
                     strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false));
        
        if ($isLocal) {
            $this->kronApiUrl = 'http://localhost/lactech/kron/api';
        } else {
            $this->kronApiUrl = 'https://kron.sbs/api';
        }
    }
    
    /**
     * Conecta com KRON usando token
     */
    public function connectWithToken($token, $lactechUserId, $lactechUserEmail)
    {
        $url = $this->kronApiUrl . '/verify-connection-token.php';
        
        $data = [
            'token' => $token,
            'system_name' => 'lactech',
            'system_user_id' => $lactechUserId,
            'system_user_email' => $lactechUserEmail
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        // Verificar erros de curl
        if ($response === false || !empty($curlError)) {
            error_log("KRON Connection cURL Error: " . $curlError);
            return ['valid' => false, 'error' => 'Erro de conexão: ' . ($curlError ?: 'Não foi possível conectar ao servidor KRON')];
        }
        
        // Verificar código HTTP
        if ($httpCode !== 200) {
            error_log("KRON Connection HTTP Error: Code $httpCode - Response: " . substr($response, 0, 200));
            
            // Tentar decodificar resposta para obter mensagem de erro específica
            $errorData = json_decode($response, true);
            if ($errorData && isset($errorData['error'])) {
                return ['valid' => false, 'error' => $errorData['error']];
            }
            
            return ['valid' => false, 'error' => 'Erro ao conectar com KRON (HTTP ' . $httpCode . ')'];
        }
        
        // Decodificar resposta JSON
        $result = json_decode($response, true);
        
        if ($result === null) {
            error_log("KRON Connection JSON Error: " . json_last_error_msg() . " - Response: " . substr($response, 0, 200));
            return ['valid' => false, 'error' => 'Resposta inválida do servidor KRON'];
        }
        
        // Garantir que sempre retorna 'valid'
        if (!isset($result['valid'])) {
            $result['valid'] = false;
            $result['error'] = $result['error'] ?? 'Resposta inválida do servidor';
        }
        
        return $result;
    }
    
    /**
     * Verifica status da conexão
     */
    public function getConnectionStatus($lactechUserId, $db)
    {
        try {
            $stmt = $db->prepare("
                SELECT kron_user_id, kron_connection_token, kron_connected_at 
                FROM users 
                WHERE id = ?
            ");
            $stmt->execute([$lactechUserId]);
            $result = $stmt->fetch();
            
            if ($result && $result['kron_user_id']) {
                return [
                    'connected' => true,
                    'kron_user_id' => $result['kron_user_id'],
                    'connected_at' => $result['kron_connected_at']
                ];
            }
            
            return ['connected' => false];
            
        } catch (PDOException $e) {
            error_log("KronConnector Error: " . $e->getMessage());
            return ['connected' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Salva conexão no banco LacTech
     */
    public function saveConnection($lactechUserId, $kronUserId, $connectionToken, $db)
    {
        try {
            $stmt = $db->prepare("
                UPDATE users 
                SET kron_user_id = ?, 
                    kron_connection_token = ?, 
                    kron_connected_at = NOW() 
                WHERE id = ?
            ");
            
            $stmt->execute([$kronUserId, $connectionToken, $lactechUserId]);
            
            return true;
            
        } catch (PDOException $e) {
            error_log("KronConnector Save Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Desconecta do KRON
     */
    public function disconnect($lactechUserId, $db)
    {
        try {
            $stmt = $db->prepare("
                UPDATE users 
                SET kron_user_id = NULL, 
                    kron_connection_token = NULL, 
                    kron_connected_at = NULL 
                WHERE id = ?
            ");
            
            $stmt->execute([$lactechUserId]);
            
            return true;
            
        } catch (PDOException $e) {
            error_log("KronConnector Disconnect Error: " . $e->getMessage());
            return false;
        }
    }
}

