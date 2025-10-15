<?php
// API para Sistema de Reprodução Avançado

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
            case 'get_active_pregnancies':
                $pregnancies = $db->getActivePregnancies();
                sendResponse($pregnancies);
                break;
                
            case 'get_maternity_alerts':
                $alerts = $db->getActiveMaternityAlerts();
                sendResponse($alerts);
                break;
                
            case 'get_reproductive_performance':
                $performance = $db->getReproductivePerformance();
                sendResponse($performance);
                break;
                
            case 'get_heat_cycles':
                $animal_id = $_GET['animal_id'] ?? null;
                if (!$animal_id) sendResponse(null, 'Animal ID não fornecido');
                $cycles = $db->getHeatCyclesByAnimal($animal_id);
                sendResponse($cycles);
                break;
                
            case 'get_pregnancy_indicators':
                $indicators = $db->getPregnancyIndicators();
                sendResponse($indicators);
                break;
                
            default:
                sendResponse(null, 'Ação não especificada');
        }
    }
    
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
        
        switch ($action) {
            case 'confirm_pregnancy':
                $result = $db->confirmPregnancy($input);
                sendResponse($result);
                break;
                
            case 'record_birth':
                $result = $db->recordBirth($input);
                sendResponse($result);
                break;
                
            case 'record_heat_cycle':
                $result = $db->recordHeatCycle($input);
                sendResponse($result);
                break;
                
            case 'resolve_maternity_alert':
                $id = $input['id'] ?? null;
                if (!$id) sendResponse(null, 'ID não fornecido');
                $result = $db->resolveMaternityAlert($id);
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

