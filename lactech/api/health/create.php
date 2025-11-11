<?php
// API para criar registros de saúde

ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$dbPath = __DIR__ . '/../includes/Database.class.php';
if (!file_exists($dbPath)) {
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => 'Database class não encontrada'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once $dbPath;

function sendResponse($data = null, $error = null) {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    $response = ['success' => $error === null];
    if ($data !== null) {
        $response['data'] = $data;
    }
    if ($error !== null) {
        $response['error'] = $error;
    }
    
    header_remove();
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(200);
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

try {
    $db = Database::getInstance();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    
    if ($method !== 'POST') {
        sendResponse(null, 'Método não permitido');
    }
    
    // Obter dados do formulário
    $input = [];
    if ($_SERVER['CONTENT_TYPE'] === 'application/json') {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
    } else {
        $input = $_POST;
    }
    
    // Validar campos obrigatórios
    $required = ['animal_id', 'record_date', 'record_type'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            sendResponse(null, "Campo '{$field}' é obrigatório");
        }
    }
    
    // Obter ID do usuário logado
    $recorded_by = $_SESSION['user_id'] ?? 1;
    
    // Preparar dados para inserção
    $data = [
        'animal_id' => intval($input['animal_id']),
        'record_date' => $input['record_date'],
        'record_type' => $input['record_type'],
        'description' => $input['description'] ?? '',
        'medication' => $input['medication'] ?? null,
        'dosage' => $input['dosage'] ?? null,
        'cost' => isset($input['cost']) ? floatval($input['cost']) : null,
        'next_date' => $input['next_date'] ?? null,
        'veterinarian' => $input['veterinarian'] ?? null,
        'recorded_by' => $recorded_by,
        'farm_id' => 1
    ];
    
    // Inserir no banco de dados
    $pdo = $db->getConnection();
    $stmt = $pdo->prepare("
        INSERT INTO health_records (
            animal_id, record_date, record_type, description, medication, 
            dosage, cost, next_date, veterinarian, recorded_by, farm_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $data['animal_id'],
        $data['record_date'],
        $data['record_type'],
        $data['description'],
        $data['medication'],
        $data['dosage'],
        $data['cost'],
        $data['next_date'],
        $data['veterinarian'],
        $data['recorded_by'],
        $data['farm_id']
    ]);
    
    $recordId = $pdo->lastInsertId();
    
    sendResponse([
        'id' => $recordId,
        'message' => 'Registro de saúde criado com sucesso'
    ]);
    
} catch (Exception $e) {
    error_log("Erro na API health/create.php: " . $e->getMessage());
    sendResponse(null, 'Erro ao criar registro de saúde: ' . $e->getMessage());
} catch (Error $e) {
    error_log("Erro fatal na API health/create.php: " . $e->getMessage());
    sendResponse(null, 'Erro interno: ' . $e->getMessage());
}
?>

