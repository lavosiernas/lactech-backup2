<?php
/**
 * SafeNode - API Base Controller
 * Controller base para API RESTful
 */

class BaseController {
    protected $db;
    protected $userId;
    protected $siteId;
    
    public function __construct($database) {
        $this->db = $database;
        $this->authenticate();
    }
    
    /**
     * Autentica requisição via API Key ou JWT
     */
    protected function authenticate() {
        // Verificar API Key no header
        $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? $_GET['api_key'] ?? null;
        
        if ($apiKey) {
            $this->authenticateByApiKey($apiKey);
            return;
        }
        
        // Verificar JWT no header
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $this->authenticateByJWT($matches[1]);
            return;
        }
        
        // Verificar sessão (para requisições do próprio dashboard)
        if (isset($_SESSION['safenode_logged_in']) && $_SESSION['safenode_logged_in'] === true) {
            $this->userId = $_SESSION['safenode_user_id'] ?? null;
            $this->siteId = $_SESSION['view_site_id'] ?? null;
            return;
        }
        
        $this->sendError('Não autorizado', 401);
    }
    
    /**
     * Autentica por API Key
     */
    protected function authenticateByApiKey($apiKey) {
        try {
            require_once __DIR__ . '/../../includes/HVAPIKeyManager.php';
            $keyData = HVAPIKeyManager::validateKey($apiKey);
            
            if ($keyData && $keyData['is_active']) {
                $this->userId = $keyData['user_id'] ?? null;
                return;
            }
        } catch (Exception $e) {
            // Ignorar
        }
        
        $this->sendError('API Key inválida', 401);
    }
    
    /**
     * Autentica por JWT (simplificado)
     */
    protected function authenticateByJWT($token) {
        // Em produção, usar biblioteca JWT real (firebase/php-jwt)
        // Por enquanto, validação básica
        
        try {
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                throw new Exception('Token inválido');
            }
            
            $payload = json_decode(base64_decode($parts[1]), true);
            
            if (!$payload || !isset($payload['user_id'])) {
                throw new Exception('Payload inválido');
            }
            
            // Verificar expiração
            if (isset($payload['exp']) && $payload['exp'] < time()) {
                throw new Exception('Token expirado');
            }
            
            $this->userId = $payload['user_id'];
            $this->siteId = $payload['site_id'] ?? null;
            
        } catch (Exception $e) {
            $this->sendError('Token inválido', 401);
        }
    }
    
    /**
     * Envia resposta JSON
     */
    protected function sendResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
    
    /**
     * Envia erro
     */
    protected function sendError($message, $statusCode = 400) {
        $this->sendResponse([
            'success' => false,
            'error' => $message
        ], $statusCode);
    }
    
    /**
     * Valida parâmetros obrigatórios
     */
    protected function validateRequired($params, $required) {
        $missing = [];
        foreach ($required as $field) {
            if (!isset($params[$field]) || $params[$field] === '') {
                $missing[] = $field;
            }
        }
        
        if (!empty($missing)) {
            $this->sendError('Campos obrigatórios faltando: ' . implode(', ', $missing), 400);
        }
    }
}



