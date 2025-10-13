<?php
// API para Gestão de Medicamentos

error_reporting(0);
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

$dbPath = __DIR__ . '/../includes/Database.class.php';
if (!file_exists($dbPath)) {
    echo json_encode(['success' => false, 'error' => 'Database class não encontrada']);
    exit;
}

require_once $dbPath;

function sendResponse($data = null, $error = null) {
    $response = ['success' => $error === null];
    if ($data !== null) $response['data'] = $data;
    if ($error !== null) $response['error'] = $error;
    echo json_encode($response);
    exit;
}

try {
    $db = Database::getInstance();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    
    if ($method === 'GET') {
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'select':
            case 'get_all':
                $medications = $db->getAllMedications();
                sendResponse($medications);
                break;
                
            case 'get_by_id':
                $id = $_GET['id'] ?? null;
                if (!$id) sendResponse(null, 'ID não fornecido');
                $medication = $db->getMedicationById($id);
                sendResponse($medication);
                break;
                
            case 'get_low_stock':
                $medications = $db->getLowStockMedications();
                sendResponse($medications);
                break;
                
            case 'get_expiring':
                $medications = $db->getExpiringMedications();
                sendResponse($medications);
                break;
                
            default:
                sendResponse(null, 'Ação não especificada');
        }
    }
    
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
        
        switch ($action) {
            case 'insert':
                $result = $db->createMedication($input);
                sendResponse($result);
                break;
                
            case 'update':
                $id = $input['id'] ?? null;
                if (!$id) sendResponse(null, 'ID não fornecido');
                unset($input['id']);
                $result = $db->updateMedication($id, $input);
                sendResponse($result);
                break;
                
            case 'delete':
                $id = $input['id'] ?? null;
                if (!$id) sendResponse(null, 'ID não fornecido');
                $result = $db->deleteMedication($id);
                sendResponse($result);
                break;
                
            default:
                sendResponse(null, 'Ação não especificada');
        }
    }
    
} catch (Exception $e) {
    sendResponse(null, $e->getMessage());
}
?>

