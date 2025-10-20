<?php
// API para Sistema de Reprodução

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
    if ($data !== null) {
        foreach ($data as $key => $value) {
            $response[$key] = $value;
        }
    }
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
            case 'get_all':
                // Buscar prenhes ativas
                $stmt = $conn->prepare("
                    SELECT pc.*, a.animal_number, a.name as animal_name,
                           DATEDIFF(pc.expected_birth, CURDATE()) as days_to_birth
                    FROM pregnancy_controls pc
                    LEFT JOIN animals a ON pc.animal_id = a.id
                    WHERE pc.farm_id = 1
                    ORDER BY pc.expected_birth ASC
                ");
                $stmt->execute();
                $pregnancies = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Buscar inseminações
                $stmt = $conn->prepare("
                    SELECT i.*, a.animal_number, a.name as animal_name,
                           b.name as bull_name, b.bull_number
                    FROM inseminations i
                    LEFT JOIN animals a ON i.animal_id = a.id
                    LEFT JOIN bulls b ON i.bull_id = b.id
                    WHERE i.farm_id = 1
                    ORDER BY i.insemination_date DESC
                ");
                $stmt->execute();
                $inseminations = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Buscar nascimentos
                $stmt = $conn->prepare("
                    SELECT b.*, a.animal_number as mother_number, a.name as mother_name
                    FROM births b
                    LEFT JOIN animals a ON b.animal_id = a.id
                    WHERE b.farm_id = 1
                    ORDER BY b.birth_date DESC
                ");
                $stmt->execute();
                $births = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                sendResponse([
                    'pregnancies' => $pregnancies,
                    'inseminations' => $inseminations,
                    'births' => $births
                ]);
                break;
                
            default:
                sendResponse(null, 'Ação não especificada');
        }
    }
    
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
        
        switch ($action) {
            case 'add_insemination':
                $stmt = $conn->prepare("
                    INSERT INTO inseminations (
                        animal_id, bull_id, insemination_date, insemination_type,
                        technician, notes, recorded_by, farm_id
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $input['animal_id'],
                    $input['bull_id'] ?? null,
                    $input['insemination_date'],
                    $input['insemination_type'] ?? 'inseminacao_artificial',
                    $input['technician'] ?? null,
                    $input['notes'] ?? null,
                    $_SESSION['user_id'] ?? 1,
                    $input['farm_id'] ?? 1
                ]);
                sendResponse(['id' => $conn->lastInsertId()]);
                break;
                
            case 'add_pregnancy':
                $stmt = $conn->prepare("
                    INSERT INTO pregnancy_controls (
                        animal_id, pregnancy_date, expected_birth, pregnancy_stage,
                        ultrasound_date, ultrasound_result, notes, recorded_by, farm_id
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $input['animal_id'],
                    $input['pregnancy_date'],
                    $input['expected_birth'],
                    $input['pregnancy_stage'] ?? 'inicial',
                    $input['ultrasound_date'] ?? null,
                    $input['ultrasound_result'] ?? null,
                    $input['notes'] ?? null,
                    $_SESSION['user_id'] ?? 1,
                    $input['farm_id'] ?? 1
                ]);
                sendResponse(['id' => $conn->lastInsertId()]);
                break;
                
            case 'add_birth':
                $stmt = $conn->prepare("
                    INSERT INTO births (
                        animal_id, birth_date, birth_type, calf_number, calf_gender,
                        calf_weight, calf_breed, mother_status, calf_status,
                        notes, recorded_by, farm_id
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $input['animal_id'],
                    $input['birth_date'],
                    $input['birth_type'] ?? 'normal',
                    $input['calf_number'] ?? null,
                    $input['calf_gender'] ?? null,
                    $input['calf_weight'] ?? null,
                    $input['calf_breed'] ?? null,
                    $input['mother_status'] ?? 'boa',
                    $input['calf_status'] ?? 'vivo',
                    $input['notes'] ?? null,
                    $_SESSION['user_id'] ?? 1,
                    $input['farm_id'] ?? 1
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
