<?php
// API para Gestão de Touros

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
                    SELECT * FROM bulls 
                    WHERE is_active = 1 AND farm_id = 1 
                    ORDER BY bull_number
                ");
                $stmt->execute();
                $bulls = $stmt->fetchAll(PDO::FETCH_ASSOC);
                sendResponse($bulls);
                break;
                
            case 'get_by_id':
                $id = $_GET['id'] ?? null;
                if (!$id) sendResponse(null, 'ID não fornecido');
                
                $stmt = $conn->prepare("SELECT * FROM bulls WHERE id = ?");
                $stmt->execute([$id]);
                $bull = $stmt->fetch(PDO::FETCH_ASSOC);
                sendResponse($bull);
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
                    INSERT INTO bulls (
                        bull_number, name, breed, birth_date, source,
                        genetic_value, notes, farm_id, is_active
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $input['bull_number'],
                    $input['name'] ?? null,
                    $input['breed'],
                    $input['birth_date'],
                    $input['source'] ?? 'inseminacao',
                    $input['genetic_value'] ?? null,
                    $input['notes'] ?? null,
                    $input['farm_id'] ?? 1,
                    $input['is_active'] ?? 1
                ]);
                sendResponse(['id' => $conn->lastInsertId()]);
                break;
                
            default:
                sendResponse(null, 'Ação não especificada');
        }
    }
    
} catch (Exception $e) {
    sendResponse(null, $e->getMessage());
}
?>

