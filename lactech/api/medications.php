<?php
// API para Gestão de Medicamentos

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
                // Query direta usando PDO
                $stmt = $conn->prepare("SELECT * FROM medications WHERE is_active = 1 ORDER BY name");
                $stmt->execute();
                $medications = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                // Insert usando PDO
                $stmt = $conn->prepare("
                    INSERT INTO medications (
                        name, type, description, unit, stock_quantity, min_stock,
                        unit_price, expiry_date, supplier, farm_id, is_active
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $input['name'],
                    $input['type'],
                    $input['description'] ?? null,
                    $input['unit'],
                    $input['stock_quantity'],
                    $input['min_stock'],
                    $input['unit_price'] ?? null,
                    $input['expiry_date'] ?? null,
                    $input['supplier'] ?? null,
                    $input['farm_id'] ?? 1,
                    $input['is_active'] ?? 1
                ]);
                sendResponse(['id' => $conn->lastInsertId()]);
                break;
                
            case 'update':
                $id = $input['id'] ?? null;
                if (!$id) sendResponse(null, 'ID não fornecido');
                
                $fields = [];
                $values = [];
                
                if (isset($input['name'])) { $fields[] = 'name = ?'; $values[] = $input['name']; }
                if (isset($input['type'])) { $fields[] = 'type = ?'; $values[] = $input['type']; }
                if (isset($input['description'])) { $fields[] = 'description = ?'; $values[] = $input['description']; }
                if (isset($input['unit'])) { $fields[] = 'unit = ?'; $values[] = $input['unit']; }
                if (isset($input['stock_quantity'])) { $fields[] = 'stock_quantity = ?'; $values[] = $input['stock_quantity']; }
                if (isset($input['min_stock'])) { $fields[] = 'min_stock = ?'; $values[] = $input['min_stock']; }
                if (isset($input['unit_price'])) { $fields[] = 'unit_price = ?'; $values[] = $input['unit_price']; }
                if (isset($input['expiry_date'])) { $fields[] = 'expiry_date = ?'; $values[] = $input['expiry_date']; }
                if (isset($input['supplier'])) { $fields[] = 'supplier = ?'; $values[] = $input['supplier']; }
                
                $values[] = $id;
                
                if (empty($fields)) sendResponse(null, 'Nenhum campo para atualizar');
                
                $stmt = $conn->prepare("UPDATE medications SET " . implode(', ', $fields) . " WHERE id = ?");
                $stmt->execute($values);
                sendResponse(['updated' => true]);
                break;
                
            case 'delete':
                $id = $input['id'] ?? null;
                if (!$id) sendResponse(null, 'ID não fornecido');
                
                $stmt = $conn->prepare("UPDATE medications SET is_active = 0 WHERE id = ?");
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

