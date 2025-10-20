<?php
// API para Controle de Custos de Novilhas

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
                $stmt = $conn->prepare("
                    SELECT hc.*, a.animal_number, a.name as animal_name
                    FROM heifer_costs hc
                    LEFT JOIN animals a ON hc.animal_id = a.id
                    WHERE hc.farm_id = 1
                    ORDER BY hc.cost_date DESC
                ");
                $stmt->execute();
                $costs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                sendResponse($costs);
                break;
                
            case 'get_by_animal':
                $animalId = $_GET['animal_id'] ?? null;
                if (!$animalId) sendResponse(null, 'Animal ID não fornecido');
                
                $stmt = $conn->prepare("
                    SELECT hc.*, a.animal_number, a.name as animal_name
                    FROM heifer_costs hc
                    LEFT JOIN animals a ON hc.animal_id = a.id
                    WHERE hc.animal_id = ? AND hc.farm_id = 1
                    ORDER BY hc.cost_date DESC
                ");
                $stmt->execute([$animalId]);
                $costs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                sendResponse($costs);
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
                $stmt = $conn->prepare("
                    INSERT INTO heifer_costs (
                        animal_id, cost_date, cost_category, cost_amount,
                        description, recorded_by, farm_id
                    ) VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $input['animal_id'],
                    $input['cost_date'],
                    $input['cost_category'],
                    $input['cost_amount'],
                    $input['description'],
                    $_SESSION['user_id'] ?? 1,
                    $input['farm_id'] ?? 1
                ]);
                sendResponse(['id' => $conn->lastInsertId()]);
                break;
                
            case 'update':
                $id = $input['id'] ?? null;
                if (!$id) sendResponse(null, 'ID não fornecido');
                
                $stmt = $conn->prepare("
                    UPDATE heifer_costs 
                    SET cost_date = ?, cost_category = ?, cost_amount = ?, description = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $input['cost_date'],
                    $input['cost_category'],
                    $input['cost_amount'],
                    $input['description'],
                    $id
                ]);
                sendResponse(['updated' => true]);
                break;
                
            case 'delete':
                $id = $input['id'] ?? null;
                if (!$id) sendResponse(null, 'ID não fornecido');
                
                $stmt = $conn->prepare("DELETE FROM heifer_costs WHERE id = ?");
                $stmt->execute([$id]);
                sendResponse(['deleted' => true]);
                break;
                
            default:
                sendResponse(null, 'Ação não especificada');
        }
    }
    
} catch (Exception $e) {
    sendResponse(null, $e->getMessage());
}
?>

