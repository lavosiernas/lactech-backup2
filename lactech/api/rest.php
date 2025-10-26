<?php
/**
 * API REST Moderna - Lactech
 * Sistema unificado para todas as operações da fazenda
 */

// Configurações de segurança
error_reporting(0);
ini_set('display_errors', 0);

// Headers CORS e JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Tratar requisições OPTIONS (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar se Database.class.php existe
$dbPath = __DIR__ . '/../includes/Database.class.php';
if (!file_exists($dbPath)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro no servidor: Database.class.php não encontrado',
        'code' => 'DATABASE_NOT_FOUND'
    ]);
    exit;
}

require_once $dbPath;

/**
 * Classe para gerenciar respostas da API
 */
class ApiResponse {
    public static function success($data = null, $message = 'Sucesso', $code = 200) {
        http_response_code($code);
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('c')
        ]);
        exit;
    }
    
    public static function error($message = 'Erro', $code = 400, $details = null) {
        http_response_code($code);
        echo json_encode([
            'success' => false,
            'error' => $message,
            'details' => $details,
            'code' => $code,
            'timestamp' => date('c')
        ]);
        exit;
    }
    
    public static function unauthorized($message = 'Acesso negado') {
        self::error($message, 401);
    }
    
    public static function forbidden($message = 'Permissão negada') {
        self::error($message, 403);
    }
    
    public static function notFound($message = 'Recurso não encontrado') {
        self::error($message, 404);
    }
    
    public static function serverError($message = 'Erro interno do servidor') {
        self::error($message, 500);
    }
}

/**
 * Classe para autenticação
 */
class Auth {
    public static function checkAuth() {
        if (!isset($_SESSION['user_id'])) {
            ApiResponse::unauthorized('Sessão não encontrada');
        }
        return $_SESSION['user_id'];
    }
    
    public static function checkRole($requiredRole) {
        $userId = self::checkAuth();
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== $requiredRole) {
            ApiResponse::forbidden('Permissão insuficiente');
        }
        return $userId;
    }
}

/**
 * Classe para validação de dados
 */
class Validator {
    public static function required($data, $fields) {
        $missing = [];
        foreach ($fields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $missing[] = $field;
            }
        }
        if (!empty($missing)) {
            ApiResponse::error('Campos obrigatórios: ' . implode(', ', $missing));
        }
    }
    
    public static function email($email) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            ApiResponse::error('Email inválido');
        }
    }
    
    public static function numeric($value, $field) {
        if (!is_numeric($value)) {
            ApiResponse::error("Campo '$field' deve ser numérico");
        }
    }
}

/**
 * Classe para gerenciar requisições
 */
class Request {
    public static function getMethod() {
        return $_SERVER['REQUEST_METHOD'];
    }
    
    public static function getData() {
        $method = self::getMethod();
        
        if ($method === 'GET') {
            return $_GET;
        }
        
        // Para POST, PUT, DELETE
        $input = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            ApiResponse::error('JSON inválido: ' . json_last_error_msg());
        }
        
        return $input ?: $_POST;
    }
    
    public static function getParam($name, $default = null) {
        $data = self::getData();
        return $data[$name] ?? $default;
    }
}

try {
    $db = Database::getInstance();
    $method = Request::getMethod();
    $data = Request::getData();
    
    // Extrair endpoint da URL
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $pathParts = explode('/', trim($path, '/'));
    $endpoint = end($pathParts);
    
    // Roteamento baseado no endpoint
    switch ($endpoint) {
        case 'password-requests':
            require_once __DIR__ . '/endpoints/password-requests.php';
            break;
            
        case 'notifications':
            require_once __DIR__ . '/endpoints/notifications.php';
            break;
            
        case 'dashboard':
            require_once __DIR__ . '/endpoints/dashboard.php';
            break;
            
        case 'users':
            require_once __DIR__ . '/endpoints/users.php';
            break;
            
        case 'volume':
            require_once __DIR__ . '/endpoints/volume.php';
            break;
            
        case 'quality':
            require_once __DIR__ . '/endpoints/quality.php';
            break;
            
        case 'financial':
            require_once __DIR__ . '/endpoints/financial.php';
            break;
            
        default:
            ApiResponse::notFound('Endpoint não encontrado: ' . $endpoint);
    }
    
} catch (Exception $e) {
    ApiResponse::serverError('Erro interno: ' . $e->getMessage());
}
?>

