<?php
/**
 * KRON - Gerenciador de Conexões
 */

require_once __DIR__ . '/config.php';

class KronConnectionManager
{
    private $pdo;
    private $secretKey;
    
    public function __construct()
    {
        $this->pdo = getKronDatabase();
        // Chave secreta para hash (deve estar em config ou env)
        $this->secretKey = 'kron_secret_key_change_in_production_' . date('Y');
    }
    
    /**
     * Gera token temporário de conexão
     */
    public function generateConnectionToken($kronUserId, $systemName)
    {
        if (!$this->pdo) {
            return null;
        }
        
        try {
            // Gerar token único
            $token = bin2hex(random_bytes(32)); // 64 caracteres
            
            // Calcular hash de validação
            $hash = hash_hmac('sha256', $token . $kronUserId, $this->secretKey);
            
            // Expira em 5 minutos
            $expiresAt = date('Y-m-d H:i:s', strtotime('+5 minutes'));
            
            // Salvar no banco
            $stmt = $this->pdo->prepare("
                INSERT INTO kron_connection_tokens 
                (token, kron_user_id, system_name, status, expires_at) 
                VALUES (?, ?, ?, 'pending', ?)
            ");
            
            $stmt->execute([$token, $kronUserId, $systemName, $expiresAt]);
            
            return [
                'token' => $token,
                'hash' => $hash,
                'expires_at' => $expiresAt,
                'expires_in' => 300 // 5 minutos em segundos
            ];
            
        } catch (PDOException $e) {
            error_log("KRON Connection Manager Error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Valida token de conexão
     */
    public function verifyConnectionToken($token, $systemName, $systemUserId, $systemUserEmail)
    {
        if (!$this->pdo) {
            return ['valid' => false, 'error' => 'Database error'];
        }
        
        try {
            // Buscar token
            $stmt = $this->pdo->prepare("
                SELECT t.*, u.email as kron_user_email, u.name as kron_user_name
                FROM kron_connection_tokens t
                INNER JOIN kron_users u ON t.kron_user_id = u.id
                WHERE t.token = ? AND t.system_name = ? AND t.status = 'pending'
            ");
            
            $stmt->execute([$token, $systemName]);
            $tokenData = $stmt->fetch();
            
            if (!$tokenData) {
                $this->logConnectionAttempt($token, $systemName, 'invalid', 'Token não encontrado');
                return ['valid' => false, 'error' => 'Token inválido'];
            }
            
            // Verificar expiração
            if (strtotime($tokenData['expires_at']) < time()) {
                // Marcar como expirado
                $stmt = $this->pdo->prepare("
                    UPDATE kron_connection_tokens SET status = 'expired' WHERE id = ?
                ");
                $stmt->execute([$tokenData['id']]);
                
                $this->logConnectionAttempt($token, $systemName, 'expired', 'Token expirado');
                return ['valid' => false, 'error' => 'Token expirado'];
            }
            
            // Verificar hash
            $expectedHash = hash_hmac('sha256', $token . $tokenData['kron_user_id'], $this->secretKey);
            
            // Marcar token como usado
            $stmt = $this->pdo->prepare("
                UPDATE kron_connection_tokens 
                SET status = 'used', used_at = NOW(), system_user_id = ?
                WHERE id = ?
            ");
            $stmt->execute([$systemUserId, $tokenData['id']]);
            
            // Criar ou atualizar conexão
            $connectionToken = $this->createConnection($tokenData['kron_user_id'], $systemName, $systemUserId, $systemUserEmail);
            
            if ($connectionToken) {
                $this->logConnectionAttempt($token, $systemName, 'success');
                
                return [
                    'valid' => true,
                    'kron_user_id' => $tokenData['kron_user_id'],
                    'kron_user_email' => $tokenData['kron_user_email'],
                    'kron_user_name' => $tokenData['kron_user_name'],
                    'connection_token' => $connectionToken
                ];
            } else {
                $this->logConnectionAttempt($token, $systemName, 'failed', 'Erro ao criar conexão');
                return ['valid' => false, 'error' => 'Erro ao criar conexão'];
            }
            
        } catch (PDOException $e) {
            error_log("KRON Connection Manager Error: " . $e->getMessage());
            $this->logConnectionAttempt($token, $systemName, 'failed', $e->getMessage());
            return ['valid' => false, 'error' => 'Database error'];
        }
    }
    
    /**
     * Cria ou atualiza conexão permanente
     */
    private function createConnection($kronUserId, $systemName, $systemUserId, $systemUserEmail)
    {
        try {
            // Gerar token permanente (JWT simples)
            $connectionToken = bin2hex(random_bytes(32));
            
            // Verificar se conexão já existe
            $stmt = $this->pdo->prepare("
                SELECT id FROM kron_user_connections 
                WHERE kron_user_id = ? AND system_name = ? AND system_user_id = ?
            ");
            $stmt->execute([$kronUserId, $systemName, $systemUserId]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                // Atualizar conexão existente
                $stmt = $this->pdo->prepare("
                    UPDATE kron_user_connections 
                    SET connection_token = ?, is_active = 1, last_sync_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$connectionToken, $existing['id']]);
            } else {
                // Criar nova conexão
                $stmt = $this->pdo->prepare("
                    INSERT INTO kron_user_connections 
                    (kron_user_id, system_name, system_user_id, system_user_email, connection_token, is_active) 
                    VALUES (?, ?, ?, ?, ?, 1)
                ");
                $stmt->execute([$kronUserId, $systemName, $systemUserId, $systemUserEmail, $connectionToken]);
            }
            
            return $connectionToken;
            
        } catch (PDOException $e) {
            error_log("KRON Create Connection Error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Log de tentativa de conexão
     */
    private function logConnectionAttempt($token, $systemName, $status, $errorMessage = null)
    {
        if (!$this->pdo) return;
        
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO kron_connection_logs 
                (token, system_name, ip_address, user_agent, status, error_message) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
            
            $stmt->execute([$token, $systemName, $ipAddress, $userAgent, $status, $errorMessage]);
            
        } catch (PDOException $e) {
            error_log("KRON Log Error: " . $e->getMessage());
        }
    }
    
    /**
     * Lista conexões do usuário
     */
    public function getUserConnections($kronUserId)
    {
        if (!$this->pdo) {
            return [];
        }
        
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM kron_user_connections 
                WHERE kron_user_id = ? 
                ORDER BY connected_at DESC
            ");
            
            $stmt->execute([$kronUserId]);
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("KRON Get Connections Error: " . $e->getMessage());
            return [];
        }
    }
}

