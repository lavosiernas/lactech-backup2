<?php
// API para Criação de Animais
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$dbPath = __DIR__ . '/../../includes/Database.class.php';
if (!file_exists($dbPath)) {
    echo json_encode(['success' => false, 'error' => 'Database class não encontrada']);
    exit;
}

require_once $dbPath;

function sendResponse($data = null, $error = null, $status = 200) {
    http_response_code($status);
    $response = ['success' => $error === null];
    if ($data !== null) $response['data'] = $data;
    if ($error !== null) $response['error'] = $error;
    echo json_encode($response);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(null, 'Método não permitido', 405);
    }

    $db = Database::getInstance();
    
    // Obter dados do POST
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Se não conseguir decodificar JSON, tentar $_POST
    if (!$input && !empty($_POST)) {
        $input = $_POST;
    }
    
    if (!$input) {
        sendResponse(null, 'Dados não fornecidos');
    }

    // Validar campos obrigatórios
    $requiredFields = ['animal_number', 'breed', 'gender', 'birth_date'];
    foreach ($requiredFields as $field) {
        if (empty($input[$field])) {
            sendResponse(null, "Campo obrigatório: {$field}");
        }
    }

    // Validar enum values
    $validGenders = ['femea', 'macho'];
    if (!in_array($input['gender'], $validGenders)) {
        sendResponse(null, 'Sexo deve ser "femea" ou "macho"');
    }

    $validStatuses = ['Lactante', 'Seco', 'Novilha', 'Vaca', 'Bezerra', 'Bezerro', 'Touro'];
    if (!empty($input['status']) && !in_array($input['status'], $validStatuses)) {
        sendResponse(null, 'Status inválido');
    }

    $validHealthStatuses = ['saudavel', 'doente', 'tratamento', 'quarentena'];
    if (!empty($input['health_status']) && !in_array($input['health_status'], $validHealthStatuses)) {
        sendResponse(null, 'Status de saúde inválido');
    }

    $validReproductiveStatuses = ['vazia', 'prenha', 'lactante', 'seca', 'outros'];
    if (!empty($input['reproductive_status']) && !in_array($input['reproductive_status'], $validReproductiveStatuses)) {
        sendResponse(null, 'Status reprodutivo inválido');
    }

    // Verificar se o número do animal já existe
    $existingAnimal = $db->query("SELECT id FROM animals WHERE animal_number = ? AND farm_id = ?", 
        [$input['animal_number'], Database::FARM_ID])->fetch();
    
    if ($existingAnimal) {
        sendResponse(null, 'Número do animal já existe');
    }

    // Preparar dados para inserção
    $animalData = [
        'animal_number' => trim($input['animal_number']),
        'name' => !empty($input['name']) ? trim($input['name']) : null,
        'breed' => trim($input['breed']),
        'gender' => $input['gender'],
        'birth_date' => $input['birth_date'],
        'birth_weight' => !empty($input['birth_weight']) ? floatval($input['birth_weight']) : null,
        'father_id' => !empty($input['father_id']) ? intval($input['father_id']) : null,
        'mother_id' => !empty($input['mother_id']) ? intval($input['mother_id']) : null,
        'status' => $input['status'] ?? 'Bezerra',
        'health_status' => $input['health_status'] ?? 'saudavel',
        'reproductive_status' => $input['reproductive_status'] ?? 'vazia',
        'entry_date' => $input['entry_date'] ?? date('Y-m-d'),
        'notes' => !empty($input['notes']) ? trim($input['notes']) : null
    ];

    // Inserir animal
    $result = $db->createAnimal($animalData);

    if ($result['success']) {
        sendResponse($result, null);
    } else {
        sendResponse(null, $result['error']);
    }

} catch (Exception $e) {
    error_log("Erro ao criar animal: " . $e->getMessage());
    sendResponse(null, 'Erro interno do servidor: ' . $e->getMessage(), 500);
}
?>
