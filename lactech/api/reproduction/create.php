<?php
// API para criar registros de reprodução

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
    
    // Obter tipo de registro
    $recordType = $input['record_type'] ?? 'insemination';
    
    // Obter ID do usuário logado
    $recorded_by = $_SESSION['user_id'] ?? 1;
    
    $pdo = $db->getConnection();
    
    switch ($recordType) {
        case 'insemination':
            // Validar campos obrigatórios para inseminação
            if (empty($input['animal_id']) || empty($input['insemination_date'])) {
                sendResponse(null, 'Campos obrigatórios: animal_id, insemination_date');
            }
            
            // Calcular data esperada de parto (280 dias após inseminação)
            $inseminationDate = new DateTime($input['insemination_date']);
            $expectedCalvingDate = $inseminationDate->modify('+280 days')->format('Y-m-d');
            
            $stmt = $pdo->prepare("
                INSERT INTO inseminations (
                    animal_id, bull_id, insemination_date, insemination_time,
                    insemination_type, technician, technician_name, technician_license,
                    semen_batch, semen_expiry_date, semen_straw_number, insemination_method,
                    pregnancy_result, pregnancy_check_method, expected_calving_date,
                    cost, notes, recorded_by, farm_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                intval($input['animal_id']),
                isset($input['bull_id']) ? intval($input['bull_id']) : null,
                $input['insemination_date'],
                $input['insemination_time'] ?? null,
                $input['insemination_type'] ?? 'inseminacao_artificial',
                $input['technician'] ?? null,
                $input['technician_name'] ?? null,
                $input['technician_license'] ?? null,
                $input['semen_batch'] ?? null,
                $input['semen_expiry_date'] ?? null,
                $input['semen_straw_number'] ?? null,
                $input['insemination_method'] ?? 'IA',
                'pendente',
                $input['pregnancy_check_method'] ?? 'palpacao',
                $expectedCalvingDate,
                isset($input['cost']) ? floatval($input['cost']) : null,
                $input['notes'] ?? null,
                $recorded_by,
                1
            ]);
            
            $recordId = $pdo->lastInsertId();
            sendResponse([
                'id' => $recordId,
                'type' => 'insemination',
                'message' => 'Inseminação registrada com sucesso'
            ]);
            break;
            
        case 'pregnancy_test':
            // Validar campos obrigatórios para teste de prenhez
            if (empty($input['animal_id']) || empty($input['pregnancy_date'])) {
                sendResponse(null, 'Campos obrigatórios: animal_id, pregnancy_date');
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO pregnancy_controls (
                    animal_id, insemination_id, pregnancy_date, expected_birth,
                    pregnancy_stage, ultrasound_date, ultrasound_result, notes,
                    recorded_by, farm_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            // Calcular data esperada de parto (280 dias após prenhez)
            $pregnancyDate = new DateTime($input['pregnancy_date']);
            $expectedBirth = $pregnancyDate->modify('+280 days')->format('Y-m-d');
            
            $stmt->execute([
                intval($input['animal_id']),
                isset($input['insemination_id']) ? intval($input['insemination_id']) : null,
                $input['pregnancy_date'],
                $expectedBirth,
                $input['pregnancy_stage'] ?? 'inicial',
                $input['ultrasound_date'] ?? null,
                $input['ultrasound_result'] ?? null,
                $input['notes'] ?? null,
                $recorded_by,
                1
            ]);
            
            $recordId = $pdo->lastInsertId();
            sendResponse([
                'id' => $recordId,
                'type' => 'pregnancy_test',
                'message' => 'Teste de prenhez registrado com sucesso'
            ]);
            break;
            
        case 'birth':
            // Validar campos obrigatórios para parto
            if (empty($input['animal_id']) || empty($input['birth_date'])) {
                sendResponse(null, 'Campos obrigatórios: animal_id, birth_date');
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO births (
                    animal_id, pregnancy_id, birth_date, birth_time,
                    calf_sex, calf_weight, complications, notes,
                    recorded_by, farm_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                intval($input['animal_id']),
                isset($input['pregnancy_id']) ? intval($input['pregnancy_id']) : null,
                $input['birth_date'],
                $input['birth_time'] ?? null,
                $input['calf_sex'] ?? null,
                isset($input['calf_weight']) ? floatval($input['calf_weight']) : null,
                $input['complications'] ?? null,
                $input['notes'] ?? null,
                $recorded_by,
                1
            ]);
            
            $recordId = $pdo->lastInsertId();
            sendResponse([
                'id' => $recordId,
                'type' => 'birth',
                'message' => 'Parto registrado com sucesso'
            ]);
            break;
            
        default:
            sendResponse(null, 'Tipo de registro não reconhecido');
    }
    
} catch (Exception $e) {
    error_log("Erro na API reproduction/create.php: " . $e->getMessage());
    sendResponse(null, 'Erro ao criar registro de reprodução: ' . $e->getMessage());
} catch (Error $e) {
    error_log("Erro fatal na API reproduction/create.php: " . $e->getMessage());
    sendResponse(null, 'Erro interno: ' . $e->getMessage());
}
?>






