<?php
/**
 * KRON - Gerenciador de Sistemas
 * Gerencia sistemas governados
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/KronJWT.php';

class KronSystemManager
{
    private $pdo;
    private $jwt;
    
    public function __construct()
    {
        $this->pdo = getKronDatabase();
        $this->jwt = new KronJWT();
    }
    
    /**
     * Obtém sistema por nome
     */
    public function getSystemByName($systemName)
    {
        if (!$this->pdo) {
            return null;
        }
        
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM kron_systems WHERE name = ?
            ");
            $stmt->execute([$systemName]);
            return $stmt->fetch();
            
        } catch (PDOException $e) {
            error_log("KRON System Manager Error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Obtém sistema por ID
     */
    public function getSystemById($systemId)
    {
        if (!$this->pdo) {
            return null;
        }
        
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM kron_systems WHERE id = ?
            ");
            $stmt->execute([$systemId]);
            return $stmt->fetch();
            
        } catch (PDOException $e) {
            error_log("KRON System Manager Error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Lista todos os sistemas
     */
    public function listSystems($status = null)
    {
        if (!$this->pdo) {
            return [];
        }
        
        try {
            $sql = "SELECT * FROM kron_systems";
            $params = [];
            
            if ($status) {
                $sql .= " WHERE status = ?";
                $params[] = $status;
            }
            
            $sql .= " ORDER BY name ASC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("KRON System Manager Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Gera token de sistema
     */
    public function generateSystemToken($systemId, $scopes = [], $expiration = null)
    {
        if (!$this->pdo) {
            return null;
        }
        
        try {
            $system = $this->getSystemById($systemId);
            
            if (!$system) {
                return null;
            }
            
            // Gerar token JWT
            $token = $this->jwt->generateSystemToken(
                $systemId,
                $system['name'],
                $scopes ?: ['*'], // Se não especificado, acesso total
                $expiration
            );
            
            // Hash do token para armazenar
            $tokenHash = hash('sha256', $token);
            
            // Calcular expiração
            $expiresAt = null;
            if ($expiration) {
                $expiresAt = is_numeric($expiration) 
                    ? date('Y-m-d H:i:s', $expiration)
                    : $expiration;
            }
            
            // Salvar token no banco
            $stmt = $this->pdo->prepare("
                INSERT INTO kron_system_tokens 
                (system_id, token_hash, scopes, expires_at, is_active)
                VALUES (?, ?, ?, ?, 1)
            ");
            
            $scopesJson = json_encode($scopes ?: ['*']);
            $stmt->execute([$systemId, $tokenHash, $scopesJson, $expiresAt]);
            
            // Atualizar token no sistema
            $stmt = $this->pdo->prepare("
                UPDATE kron_systems 
                SET system_token = ?, token_expires_at = ?
                WHERE id = ?
            ");
            $stmt->execute([$tokenHash, $expiresAt, $systemId]);
            
            return $token;
            
        } catch (PDOException $e) {
            error_log("KRON System Manager Error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Valida token de sistema
     */
    public function validateSystemToken($token)
    {
        // Validar JWT
        $result = $this->jwt->validateSystemToken($token);
        
        if (!$result['valid']) {
            return $result;
        }
        
        // Verificar se token está ativo no banco
        $tokenHash = hash('sha256', $token);
        
        if (!$this->pdo) {
            return ['valid' => false, 'error' => 'Database error'];
        }
        
        try {
            $stmt = $this->pdo->prepare("
                SELECT st.*, s.name as system_name, s.status as system_status
                FROM kron_system_tokens st
                INNER JOIN kron_systems s ON st.system_id = s.id
                WHERE st.token_hash = ? AND st.is_active = 1
            ");
            
            $stmt->execute([$tokenHash]);
            $tokenData = $stmt->fetch();
            
            if (!$tokenData) {
                return ['valid' => false, 'error' => 'Token não encontrado ou inativo'];
            }
            
            // Verificar se sistema está ativo
            if ($tokenData['system_status'] !== 'active') {
                return ['valid' => false, 'error' => 'Sistema inativo'];
            }
            
            // Verificar expiração
            if ($tokenData['expires_at'] && strtotime($tokenData['expires_at']) < time()) {
                return ['valid' => false, 'error' => 'Token expirado'];
            }
            
            // Atualizar último uso
            $stmt = $this->pdo->prepare("
                UPDATE kron_system_tokens 
                SET last_used_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$tokenData['id']]);
            
            return [
                'valid' => true,
                'system_id' => $tokenData['system_id'],
                'system_name' => $tokenData['system_name'],
                'scopes' => json_decode($tokenData['scopes'] ?? '[]', true)
            ];
            
        } catch (PDOException $e) {
            error_log("KRON System Manager Error: " . $e->getMessage());
            return ['valid' => false, 'error' => 'Database error'];
        }
    }
    
    /**
     * Cria novo sistema
     */
    public function createSystem($name, $displayName, $description = null, $apiUrl = null)
    {
        if (!$this->pdo) {
            return null;
        }
        
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO kron_systems (name, display_name, description, api_url, status)
                VALUES (?, ?, ?, ?, 'active')
            ");
            
            $stmt->execute([$name, $displayName, $description, $apiUrl]);
            
            return $this->pdo->lastInsertId();
            
        } catch (PDOException $e) {
            error_log("KRON System Manager Error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Atualiza sistema
     */
    public function updateSystem($systemId, $data)
    {
        if (!$this->pdo) {
            return false;
        }
        
        try {
            $allowedFields = ['display_name', 'description', 'api_url', 'status', 'version'];
            $updates = [];
            $params = [];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updates[] = "$field = ?";
                    $params[] = $data[$field];
                }
            }
            
            if (empty($updates)) {
                return false;
            }
            
            $params[] = $systemId;
            
            $sql = "UPDATE kron_systems SET " . implode(', ', $updates) . " WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            
            return $stmt->execute($params);
            
        } catch (PDOException $e) {
            error_log("KRON System Manager Error: " . $e->getMessage());
            return false;
        }
    }
}



