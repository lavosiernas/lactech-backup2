<?php
// API para users

// Desabilitar exibição de erros em produção
error_reporting(0);
ini_set('display_errors', 0);

// Iniciar sessão se não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Sempre retornar JSON
header('Content-Type: application/json');

// Carregar Database.class.php
$dbPath = __DIR__ . '/../includes/Database.class.php';
if (!file_exists($dbPath)) {
    echo json_encode(['success' => false, 'error' => 'Database class não encontrada']);
    exit;
}
require_once $dbPath;

$db = Database::getInstance();

// Função helper para enviar respostas JSON
function sendResponse($data = null, $error = null) {
    $response = ['success' => $error === null];
    if ($data !== null) $response['data'] = $data;
    if ($error !== null) $response['error'] = $error;
    echo json_encode($response);
    exit;
}

try {
    // GET: Buscar dados
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if ($method === 'GET') {
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'get_all':
            case 'select':
                $data = $db->getAllUsers();
                sendResponse($data);
                break;
                
            case 'get_by_id':
                $id = $_GET['id'] ?? null;
                if (!$id) sendResponse(null, 'ID não fornecido');
                $data = $db->getUserById($id);
                sendResponse($data);
                break;
                
            case 'get_active':
                $data = $db->getActiveUsers();
                sendResponse($data);
                break;
                
            default:
                sendResponse(null, 'Ação não especificada');
        }
    }
    
    // POST: Operações com usuários
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
        
        switch ($action) {
            case 'create':
                unset($input['action']);
                $data = $db->createUser($input);
                sendResponse($data);
                break;
                
            case 'update':
                $id = $input['id'] ?? null;
                if (!$id) sendResponse(null, 'ID não fornecido');
                unset($input['action'], $input['id']);
                $data = $db->updateUser($id, $input);
                sendResponse($data);
                break;
                
            default:
                sendResponse(null, 'Ação inválida');
        }
    }
    
} catch (Exception $e) {
    sendResponse(null, $e->getMessage());
}
?>