<?php
// API para Registros de Saúde

// IMPORTANTE: Header ANTES de qualquer output
header('Content-Type: application/json');

// Suprimir todos os erros e warnings
error_reporting(0);
ini_set('display_errors', 0);

// Buffer de saída para capturar qualquer output indesejado
ob_start();

// Iniciar sessão se ainda não foi iniciada
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

// Limpar buffer antes de retornar JSON
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
                $stmt = $conn->prepare("
                    SELECT hr.*, a.animal_number, a.name as animal_name 
                    FROM health_records hr
                    LEFT JOIN animals a ON hr.animal_id = a.id
                    WHERE hr.farm_id = 1
                    ORDER BY hr.record_date DESC, hr.created_at DESC
                ");
                $stmt->execute();
                $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
                sendResponse($records);
                break;
                
            case 'get_by_animal':
                $animal_id = $_GET['animal_id'] ?? null;
                if (!$animal_id) sendResponse(null, 'Animal ID não fornecido');
                
                $stmt = $conn->prepare("
                    SELECT hr.*, a.animal_number, a.name as animal_name 
                    FROM health_records hr
                    LEFT JOIN animals a ON hr.animal_id = a.id
                    WHERE hr.animal_id = ? AND hr.farm_id = 1
                    ORDER BY hr.record_date DESC
                ");
                $stmt->execute([$animal_id]);
                $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
                sendResponse($records);
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
                // Inserir registro de saúde
                $stmt = $conn->prepare("
                    INSERT INTO health_records (
                        animal_id, record_date, record_type, description, 
                        medication, dosage, cost, next_date, veterinarian, 
                        recorded_by, farm_id
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $input['animal_id'],
                    $input['record_date'],
                    $input['record_type'],
                    $input['description'],
                    $input['medication'] ?? null,
                    $input['dosage'] ?? null,
                    $input['cost'] ?? null,
                    $input['next_date'] ?? null,
                    $input['veterinarian'] ?? null,
                    $_SESSION['user_id'] ?? 1,
                    $input['farm_id'] ?? 1
                ]);
                sendResponse(['id' => $conn->lastInsertId()]);
                break;
                
            case 'update':
                $id = $input['id'] ?? null;
                if (!$id) sendResponse(null, 'ID não fornecido');
                
                $fields = [];
                $values = [];
                
                if (isset($input['record_date'])) { $fields[] = 'record_date = ?'; $values[] = $input['record_date']; }
                if (isset($input['record_type'])) { $fields[] = 'record_type = ?'; $values[] = $input['record_type']; }
                if (isset($input['description'])) { $fields[] = 'description = ?'; $values[] = $input['description']; }
                if (isset($input['medication'])) { $fields[] = 'medication = ?'; $values[] = $input['medication']; }
                if (isset($input['dosage'])) { $fields[] = 'dosage = ?'; $values[] = $input['dosage']; }
                if (isset($input['cost'])) { $fields[] = 'cost = ?'; $values[] = $input['cost']; }
                if (isset($input['next_date'])) { $fields[] = 'next_date = ?'; $values[] = $input['next_date']; }
                if (isset($input['veterinarian'])) { $fields[] = 'veterinarian = ?'; $values[] = $input['veterinarian']; }
                
                $values[] = $id;
                
                if (empty($fields)) sendResponse(null, 'Nenhum campo para atualizar');
                
                $stmt = $conn->prepare("UPDATE health_records SET " . implode(', ', $fields) . " WHERE id = ?");
                $stmt->execute($values);
                sendResponse(['updated' => true]);
                break;
                
            case 'delete':
                $id = $input['id'] ?? null;
                if (!$id) sendResponse(null, 'ID não fornecido');
                
                $stmt = $conn->prepare("DELETE FROM health_records WHERE id = ?");
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

