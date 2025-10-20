<?php
// API para Alertas Sanitários

// Header ANTES de qualquer output
header('Content-Type: application/json');

error_reporting(0);
ini_set('display_errors', 0);

// Buffer de saída
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

$dbPath = __DIR__ . '/../includes/Database.class.php';
if (!file_exists($dbPath)) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Database class não encontrada']);
    exit;
}

require_once $dbPath;

// Limpar buffer
ob_clean();

function sendResponse($data = null, $error = null) {
    $response = ['success' => $error === null];
    if ($data !== null) $response['data'] = $data;
    if ($error !== null) $response['error'] = $error;
    echo json_encode($response);
    exit;
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    
    if ($method === 'GET') {
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'select':
            case 'get_all':
            case 'get_active':
                // Query direta usando PDO
                $stmt = $conn->prepare("
                    SELECT ha.*, a.animal_number, a.name as animal_name 
                    FROM health_alerts ha
                    LEFT JOIN animals a ON ha.animal_id = a.id
                    WHERE ha.farm_id = 1
                    ORDER BY ha.alert_date DESC, ha.created_at DESC
                ");
                $stmt->execute();
                $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
                sendResponse($alerts);
                break;
                
            case 'get_by_animal':
                $animal_id = $_GET['animal_id'] ?? null;
                if (!$animal_id) sendResponse(null, 'Animal ID não fornecido');
                $alerts = $db->getHealthAlertsByAnimal($animal_id);
                sendResponse($alerts);
                break;
                
            case 'get_by_type':
                $type = $_GET['type'] ?? null;
                if (!$type) sendResponse(null, 'Tipo não fornecido');
                $alerts = $db->getHealthAlertsByType($type);
                sendResponse($alerts);
                break;
                
            default:
                sendResponse(null, 'Ação não especificada');
        }
    }
    
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
        
        switch ($action) {
            case 'resolve':
                $id = $input['id'] ?? null;
                if (!$id) sendResponse(null, 'ID não fornecido');
                $result = $db->resolveHealthAlert($id);
                sendResponse($result);
                break;
                
            case 'create':
                $result = $db->createHealthAlert($input);
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

