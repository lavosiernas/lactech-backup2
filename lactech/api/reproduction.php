<?php
/**
 * API: Gestão Reprodutiva - Lactech
 * Sistema completo de gestão do ciclo reprodutivo
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

error_reporting(0);
ini_set('display_errors', 0);

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Acesso negado']);
    exit;
}

require_once __DIR__ . '/../includes/Database.class.php';

function sendResponse($data = null, $error = null, $statusCode = 200) {
    http_response_code($statusCode);
    $response = ['success' => $error === null];
    if ($data !== null) $response['data'] = $data;
    if ($error !== null) $response['error'] = $error;
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    $farm_id = $_SESSION['farm_id'] ?? 1;
    $user_id = $_SESSION['user_id'];
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    
    switch ($action) {
        // ==========================================
        // DASHBOARD - Indicadores Reprodutivos
        // ==========================================
        case 'dashboard':
            $stats = [];
            
            // Total de prenhezes ativas
            $stmt = $conn->prepare("
                SELECT COUNT(DISTINCT pc.id) as count
                FROM pregnancy_controls pc
                INNER JOIN animals a ON pc.animal_id = a.id
                WHERE a.is_active = 1 AND a.farm_id = ?
                AND pc.expected_birth >= CURDATE()
            ");
            $stmt->execute([$farm_id]);
            $stats['pregnancies'] = (int)($stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);
            
            // Taxa de concepção (últimos 6 meses)
            $stmt = $conn->prepare("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN pregnancy_result = 'prenha' THEN 1 ELSE 0 END) as successful
                FROM inseminations
                WHERE farm_id = ?
                AND insemination_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                AND pregnancy_result IN ('prenha', 'vazia')
            ");
            $stmt->execute([$farm_id]);
            $conception = $stmt->fetch(PDO::FETCH_ASSOC);
            $total = (int)($conception['total'] ?? 0);
            $successful = (int)($conception['successful'] ?? 0);
            $stats['conception_rate'] = $total > 0 ? round(($successful / $total) * 100, 1) : 0;
            
            // Intervalo Entre Partos (IEP) médio
            $stmt = $conn->prepare("
                SELECT AVG(DATEDIFF(b2.birth_date, b1.birth_date)) as avg_iep
                FROM births b1
                INNER JOIN births b2 ON b1.animal_id = b2.animal_id
                WHERE b1.farm_id = ?
                AND b2.birth_date > b1.birth_date
                AND b1.birth_date >= DATE_SUB(CURDATE(), INTERVAL 2 YEAR)
            ");
            $stmt->execute([$farm_id]);
            $iep = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['avg_iep'] = round((float)($iep['avg_iep'] ?? 0), 0);
            
            // Idade ao primeiro parto (média)
            $stmt = $conn->prepare("
                SELECT AVG(DATEDIFF(b.birth_date, a.birth_date)) / 30.44 as avg_first_calving
                FROM births b
                INNER JOIN animals a ON b.animal_id = a.id
                WHERE b.farm_id = ?
                AND b.birth_date >= DATE_SUB(CURDATE(), INTERVAL 2 YEAR)
                AND (
                    SELECT COUNT(*) FROM births b2 
                    WHERE b2.animal_id = a.id AND b2.birth_date < b.birth_date
                ) = 0
            ");
            $stmt->execute([$farm_id]);
            $firstCalving = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['avg_first_calving'] = round((float)($firstCalving['avg_first_calving'] ?? 0), 1);
            
            // Inseminações pendentes de teste
            $stmt = $conn->prepare("
                SELECT COUNT(*) as count
                FROM inseminations
                WHERE farm_id = ?
                AND pregnancy_result = 'pendente'
                AND DATEDIFF(CURDATE(), insemination_date) BETWEEN 21 AND 60
            ");
            $stmt->execute([$farm_id]);
            $stats['pending_tests'] = (int)($stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);
            
            // Partos esperados (30 dias)
            $stmt = $conn->prepare("
                SELECT COUNT(*) as count
                FROM pregnancy_controls pc
                INNER JOIN animals a ON pc.animal_id = a.id
                WHERE a.is_active = 1 AND a.farm_id = ?
                AND pc.expected_birth BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
            ");
            $stmt->execute([$farm_id]);
            $stats['expected_calvings_30d'] = (int)($stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);
            
            // Cios previstos (7 dias)
            $stmt = $conn->prepare("
                SELECT COUNT(*) as count
                FROM heat_cycles hc
                INNER JOIN animals a ON hc.animal_id = a.id
                WHERE a.is_active = 1 AND a.farm_id = ?
                AND hc.heat_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
            ");
            $stmt->execute([$farm_id]);
            $stats['expected_heats_7d'] = (int)($stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);
            
            sendResponse($stats);
            break;
            
        // ==========================================
        // INSEMINAÇÕES
        // ==========================================
        case 'inseminations_list':
            $limit = (int)($_GET['limit'] ?? 50);
            $offset = (int)($_GET['offset'] ?? 0);
            $animal_id = $_GET['animal_id'] ?? null;
            $bull_id = $_GET['bull_id'] ?? null;
            $status = $_GET['status'] ?? null;
            
            $where = ["i.farm_id = ?"];
            $params = [$farm_id];
            
            if ($animal_id) {
                $where[] = "i.animal_id = ?";
                $params[] = $animal_id;
            }
            if ($bull_id) {
                $where[] = "i.bull_id = ?";
                $params[] = $bull_id;
            }
            if ($status) {
                $where[] = "i.pregnancy_result = ?";
                $params[] = $status;
            }
            
            $stmt = $conn->prepare("
                SELECT 
                    i.*,
                    a.animal_number,
                    a.name as animal_name,
                    a.breed as animal_breed,
                    b.name as bull_name,
                    b.breed as bull_breed,
                    u.name as recorded_by_name,
                    DATEDIFF(CURDATE(), i.insemination_date) as days_since,
                    CASE 
                        WHEN i.pregnancy_result = 'pendente' AND DATEDIFF(CURDATE(), i.insemination_date) >= 21 
                        THEN 'ready_for_test'
                        WHEN i.pregnancy_result = 'pendente' 
                        THEN 'waiting'
                        ELSE 'completed'
                    END as test_status
                FROM inseminations i
                LEFT JOIN animals a ON i.animal_id = a.id
                LEFT JOIN bulls b ON i.bull_id = b.id
                LEFT JOIN users u ON i.recorded_by = u.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY i.insemination_date DESC, i.created_at DESC
                LIMIT ? OFFSET ?
            ");
            $params[] = $limit;
            $params[] = $offset;
            $stmt->execute($params);
            $inseminations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Contar total
            $stmt = $conn->prepare("
                SELECT COUNT(*) as total
                FROM inseminations i
                WHERE " . implode(' AND ', $where) . "
            ");
            $stmt->execute(array_slice($params, 0, -2));
            $total = (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
            
            sendResponse([
                'inseminations' => $inseminations,
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset
            ]);
            break;
            
        case 'insemination_get':
            $id = $_GET['id'] ?? null;
            if (!$id) {
                sendResponse(null, 'ID não fornecido', 400);
            }
            
            $stmt = $conn->prepare("
                SELECT 
                    i.*,
                    a.animal_number,
                    a.name as animal_name,
                    a.breed as animal_breed,
                    b.name as bull_name,
                    b.breed as bull_breed,
                    u.name as recorded_by_name
                FROM inseminations i
                LEFT JOIN animals a ON i.animal_id = a.id
                LEFT JOIN bulls b ON i.bull_id = b.id
                LEFT JOIN users u ON i.recorded_by = u.id
                WHERE i.id = ? AND i.farm_id = ?
            ");
            $stmt->execute([$id, $farm_id]);
            $insemination = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$insemination) {
                sendResponse(null, 'Inseminação não encontrada', 404);
            }
            
            sendResponse($insemination);
            break;
            
        case 'insemination_create':
            if ($method !== 'POST') {
                sendResponse(null, 'Método não permitido', 405);
            }
            
            $required = ['animal_id', 'insemination_date'];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    sendResponse(null, "Campo obrigatório: $field", 400);
                }
            }
            
            // Calcular data esperada de parto
            $inseminationDate = new DateTime($input['insemination_date']);
            $expectedCalvingDate = $inseminationDate->modify('+280 days')->format('Y-m-d');
            
            $stmt = $conn->prepare("
                INSERT INTO inseminations (
                    animal_id, bull_id, insemination_date, insemination_time,
                    insemination_type, technician, technician_name, technician_license,
                    semen_batch, semen_expiry_date, semen_straw_number, insemination_method,
                    pregnancy_result, pregnancy_check_method, expected_calving_date,
                    cost, notes, recorded_by, farm_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                (int)$input['animal_id'],
                !empty($input['bull_id']) ? (int)$input['bull_id'] : null,
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
                !empty($input['cost']) ? (float)$input['cost'] : null,
                $input['notes'] ?? null,
                $user_id,
                $farm_id
            ]);
            
            $id = $conn->lastInsertId();
            sendResponse(['id' => $id, 'message' => 'Inseminação registrada com sucesso']);
            break;
            
        case 'insemination_update':
            if ($method !== 'POST' && $method !== 'PUT') {
                sendResponse(null, 'Método não permitido', 405);
            }
            
            $id = $input['id'] ?? $_GET['id'] ?? null;
            if (!$id) {
                sendResponse(null, 'ID não fornecido', 400);
            }
            
            $allowedFields = [
                'bull_id', 'insemination_date', 'insemination_time', 'insemination_type',
                'technician', 'technician_name', 'technician_license', 'semen_batch',
                'semen_expiry_date', 'semen_straw_number', 'insemination_method',
                'pregnancy_check_date', 'pregnancy_result', 'pregnancy_check_method',
                'expected_calving_date', 'actual_calving_date', 'calving_result',
                'calf_sex', 'calf_weight', 'complications', 'cost', 'notes'
            ];
            
            $updates = [];
            $params = [];
            
            foreach ($allowedFields as $field) {
                if (isset($input[$field])) {
                    $updates[] = "$field = ?";
                    $params[] = $input[$field];
                }
            }
            
            if (empty($updates)) {
                sendResponse(null, 'Nenhum campo para atualizar', 400);
            }
            
            // Recalcular expected_calving_date se insemination_date mudou
            if (isset($input['insemination_date'])) {
                $inseminationDate = new DateTime($input['insemination_date']);
                $expectedCalvingDate = $inseminationDate->modify('+280 days')->format('Y-m-d');
                $updates[] = "expected_calving_date = ?";
                $params[] = $expectedCalvingDate;
            }
            
            $params[] = $id;
            $params[] = $farm_id;
            
            $stmt = $conn->prepare("
                UPDATE inseminations 
                SET " . implode(', ', $updates) . ", updated_at = NOW()
                WHERE id = ? AND farm_id = ?
            ");
            $stmt->execute($params);
            
            sendResponse(['id' => $id, 'message' => 'Inseminação atualizada com sucesso']);
            break;
            
        case 'insemination_delete':
            if ($method !== 'DELETE' && $method !== 'POST') {
                sendResponse(null, 'Método não permitido', 405);
            }
            
            $id = $input['id'] ?? $_GET['id'] ?? null;
            if (!$id) {
                sendResponse(null, 'ID não fornecido', 400);
            }
            
            $stmt = $conn->prepare("DELETE FROM inseminations WHERE id = ? AND farm_id = ?");
            $stmt->execute([$id, $farm_id]);
            
            sendResponse(['id' => $id, 'message' => 'Inseminação excluída com sucesso']);
            break;
            
        // ==========================================
        // CONTROLES DE PRENHEZ
        // ==========================================
        case 'pregnancies_list':
            $limit = (int)($_GET['limit'] ?? 50);
            $offset = (int)($_GET['offset'] ?? 0);
            $animal_id = $_GET['animal_id'] ?? null;
            $stage = $_GET['stage'] ?? null;
            
            $where = ["pc.farm_id = ?"];
            $params = [$farm_id];
            
            if ($animal_id) {
                $where[] = "pc.animal_id = ?";
                $params[] = $animal_id;
            }
            if ($stage) {
                $where[] = "pc.pregnancy_stage = ?";
                $params[] = $stage;
            }
            
            $stmt = $conn->prepare("
                SELECT 
                    pc.*,
                    a.animal_number,
                    a.name as animal_name,
                    a.breed as animal_breed,
                    i.insemination_date,
                    i.bull_id,
                    b.name as bull_name,
                    u.name as recorded_by_name,
                    DATEDIFF(pc.expected_birth, CURDATE()) as days_until_birth
                FROM pregnancy_controls pc
                LEFT JOIN animals a ON pc.animal_id = a.id
                LEFT JOIN inseminations i ON pc.insemination_id = i.id
                LEFT JOIN bulls b ON i.bull_id = b.id
                LEFT JOIN users u ON pc.recorded_by = u.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY pc.expected_birth ASC, pc.created_at DESC
                LIMIT ? OFFSET ?
            ");
            $params[] = $limit;
            $params[] = $offset;
            $stmt->execute($params);
            $pregnancies = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            sendResponse([
                'pregnancies' => $pregnancies,
                'total' => count($pregnancies),
                'limit' => $limit,
                'offset' => $offset
            ]);
            break;
            
        case 'pregnancy_create':
            if ($method !== 'POST') {
                sendResponse(null, 'Método não permitido', 405);
            }
            
            $required = ['animal_id', 'pregnancy_date'];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    sendResponse(null, "Campo obrigatório: $field", 400);
                }
            }
            
            // Calcular data esperada de parto
            $pregnancyDate = new DateTime($input['pregnancy_date']);
            $expectedBirth = $pregnancyDate->modify('+280 days')->format('Y-m-d');
            
            $stmt = $conn->prepare("
                INSERT INTO pregnancy_controls (
                    animal_id, insemination_id, pregnancy_date, expected_birth,
                    pregnancy_stage, ultrasound_date, ultrasound_result, notes,
                    recorded_by, farm_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                (int)$input['animal_id'],
                !empty($input['insemination_id']) ? (int)$input['insemination_id'] : null,
                $input['pregnancy_date'],
                $expectedBirth,
                $input['pregnancy_stage'] ?? 'inicial',
                $input['ultrasound_date'] ?? null,
                $input['ultrasound_result'] ?? null,
                $input['notes'] ?? null,
                $user_id,
                $farm_id
            ]);
            
            $id = $conn->lastInsertId();
            
            // Atualizar inseminação se fornecido
            if (!empty($input['insemination_id'])) {
                $stmt = $conn->prepare("
                    UPDATE inseminations 
                    SET pregnancy_result = ?, pregnancy_check_date = ?
                    WHERE id = ? AND farm_id = ?
                ");
                $result = $input['ultrasound_result'] === 'positivo' ? 'prenha' : 
                         ($input['ultrasound_result'] === 'negativo' ? 'vazia' : 'pendente');
                $stmt->execute([$result, $input['pregnancy_date'], $input['insemination_id'], $farm_id]);
            }
            
            sendResponse(['id' => $id, 'message' => 'Controle de prenhez registrado com sucesso']);
            break;
            
        // ==========================================
        // CIOS
        // ==========================================
        case 'heats_list':
            $limit = (int)($_GET['limit'] ?? 50);
            $offset = (int)($_GET['offset'] ?? 0);
            $animal_id = $_GET['animal_id'] ?? null;
            $date_from = $_GET['date_from'] ?? null;
            $date_to = $_GET['date_to'] ?? null;
            
            $where = ["hc.farm_id = ?"];
            $params = [$farm_id];
            
            if ($animal_id) {
                $where[] = "hc.animal_id = ?";
                $params[] = $animal_id;
            }
            if ($date_from) {
                $where[] = "hc.heat_date >= ?";
                $params[] = $date_from;
            }
            if ($date_to) {
                $where[] = "hc.heat_date <= ?";
                $params[] = $date_to;
            }
            
            $stmt = $conn->prepare("
                SELECT 
                    hc.*,
                    a.animal_number,
                    a.name as animal_name,
                    a.breed as animal_breed,
                    u.name as recorded_by_name,
                    DATEDIFF(CURDATE(), hc.heat_date) as days_ago
                FROM heat_cycles hc
                LEFT JOIN animals a ON hc.animal_id = a.id
                LEFT JOIN users u ON hc.recorded_by = u.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY hc.heat_date DESC, hc.created_at DESC
                LIMIT ? OFFSET ?
            ");
            $params[] = $limit;
            $params[] = $offset;
            $stmt->execute($params);
            $heats = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            sendResponse([
                'heats' => $heats,
                'total' => count($heats),
                'limit' => $limit,
                'offset' => $offset
            ]);
            break;
            
        case 'heat_create':
            if ($method !== 'POST') {
                sendResponse(null, 'Método não permitido', 405);
            }
            
            $required = ['animal_id', 'heat_date'];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    sendResponse(null, "Campo obrigatório: $field", 400);
                }
            }
            
            $stmt = $conn->prepare("
                INSERT INTO heat_cycles (
                    animal_id, heat_date, heat_intensity, insemination_planned, notes,
                    recorded_by, farm_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                (int)$input['animal_id'],
                $input['heat_date'],
                $input['heat_intensity'] ?? 'moderado',
                isset($input['insemination_planned']) ? (int)$input['insemination_planned'] : 0,
                $input['notes'] ?? null,
                $user_id,
                $farm_id
            ]);
            
            $id = $conn->lastInsertId();
            sendResponse(['id' => $id, 'message' => 'Cio registrado com sucesso']);
            break;
            
        // ==========================================
        // PARTOS
        // ==========================================
        case 'births_list':
            $limit = (int)($_GET['limit'] ?? 50);
            $offset = (int)($_GET['offset'] ?? 0);
            $animal_id = $_GET['animal_id'] ?? null;
            
            $where = ["b.farm_id = ?"];
            $params = [$farm_id];
            
            if ($animal_id) {
                $where[] = "b.animal_id = ?";
                $params[] = $animal_id;
            }
            
            $stmt = $conn->prepare("
                SELECT 
                    b.*,
                    a.animal_number,
                    a.name as animal_name,
                    a.breed as animal_breed,
                    u.name as recorded_by_name,
                    DATEDIFF(CURDATE(), b.birth_date) as days_ago
                FROM births b
                LEFT JOIN animals a ON b.animal_id = a.id
                LEFT JOIN users u ON b.recorded_by = u.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY b.birth_date DESC, b.created_at DESC
                LIMIT ? OFFSET ?
            ");
            $params[] = $limit;
            $params[] = $offset;
            $stmt->execute($params);
            $births = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            sendResponse([
                'births' => $births,
                'total' => count($births),
                'limit' => $limit,
                'offset' => $offset
            ]);
            break;
            
        case 'birth_create':
            if ($method !== 'POST') {
                sendResponse(null, 'Método não permitido', 405);
            }
            
            $required = ['animal_id', 'birth_date'];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    sendResponse(null, "Campo obrigatório: $field", 400);
                }
            }
            
            $stmt = $conn->prepare("
                INSERT INTO births (
                    animal_id, pregnancy_id, birth_date, birth_time, birth_type,
                    calf_number, calf_gender, calf_weight, calf_breed,
                    mother_status, calf_status, notes, recorded_by, farm_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                (int)$input['animal_id'],
                !empty($input['pregnancy_id']) ? (int)$input['pregnancy_id'] : null,
                $input['birth_date'],
                $input['birth_time'] ?? null,
                $input['birth_type'] ?? null,
                $input['calf_number'] ?? null,
                $input['calf_gender'] ?? null,
                !empty($input['calf_weight']) ? (float)$input['calf_weight'] : null,
                $input['calf_breed'] ?? null,
                $input['mother_status'] ?? 'boa',
                $input['calf_status'] ?? 'vivo',
                $input['notes'] ?? null,
                $user_id,
                $farm_id
            ]);
            
            $id = $conn->lastInsertId();
            
            // Atualizar inseminação se fornecido
            if (!empty($input['insemination_id'])) {
                $stmt = $conn->prepare("
                    UPDATE inseminations 
                    SET actual_calving_date = ?, calving_result = ?, calf_sex = ?, calf_weight = ?
                    WHERE id = ? AND farm_id = ?
                ");
                $stmt->execute([
                    $input['birth_date'],
                    $input['calf_status'] ?? 'vivo',
                    $input['calf_gender'] ?? null,
                    !empty($input['calf_weight']) ? (float)$input['calf_weight'] : null,
                    $input['insemination_id'],
                    $farm_id
                ]);
            }
            
            sendResponse(['id' => $id, 'message' => 'Parto registrado com sucesso']);
            break;
            
        // ==========================================
        // ANIMAIS PARA DROPDOWN
        // ==========================================
        case 'animals':
            $stmt = $conn->prepare("
                SELECT id, animal_number, name, breed, reproductive_status
                FROM animals
                WHERE is_active = 1 AND farm_id = ?
                ORDER BY animal_number ASC, name ASC
            ");
            $stmt->execute([$farm_id]);
            $animals = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            sendResponse($animals);
            break;
            
        case 'bulls':
            $stmt = $conn->prepare("
                SELECT id, bull_number, name, breed, status
                FROM bulls
                WHERE is_active = 1 AND is_breeding_active = 1 AND farm_id = ?
                ORDER BY name ASC
            ");
            $stmt->execute([$farm_id]);
            $bulls = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            sendResponse($bulls);
            break;
            
        default:
            sendResponse(null, 'Ação não encontrada', 404);
    }
    
} catch (Exception $e) {
    sendResponse(null, 'Erro interno: ' . $e->getMessage(), 500);
}
?>

