<?php
// API para volume_records

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
                $data = $db->getVolumeRecords();
                sendResponse($data);
                break;
                
            case 'get_individual':
                // Buscar registros individuais por vaca
                $animal_id = $_GET['animal_id'] ?? null;
                $date_from = $_GET['date_from'] ?? null;
                $date_to = $_GET['date_to'] ?? null;
                $data = $db->getMilkProductionRecords($animal_id, $date_from, $date_to);
                sendResponse($data);
                break;
                
            case 'get_by_id':
                $id = $_GET['id'] ?? null;
                if (!$id) sendResponse(null, 'ID não fornecido');
                $data = $db->getVolumeRecordById($id);
                sendResponse($data);
                break;
                
            case 'get_by_date':
                $date_from = $_GET['date_from'] ?? null;
                $date_to = $_GET['date_to'] ?? null;
                $data = $db->getVolumeRecordsByDate($date_from, $date_to);
                sendResponse($data);
                break;
                
            default:
                sendResponse(null, 'Ação não especificada');
        }
    }
    
    // POST: Adicionar novo registro
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
        
        if ($action === 'insert') {
            unset($input['action']);
            $data = $db->addVolumeRecord($input);
            sendResponse($data);
        } elseif ($action === 'delete') {
            $id = $input['id'] ?? null;
            if (!$id) {
                sendResponse(null, 'ID não fornecido');
            }
            $data = $db->deleteVolumeRecord($id);
            sendResponse($data);
        } else {
            sendResponse(null, 'Ação inválida');
        }
    }
    
} catch (Exception $e) {
    sendResponse(null, $e->getMessage());
}
?>