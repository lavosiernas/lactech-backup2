<?php
/**
 * API de Touros - Sistema Completo
 * Gerencia cadastro, coberturas, sêmen, histórico sanitário e relatórios
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

error_reporting(0);
ini_set('display_errors', 0);

// Tratar OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar autenticação
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Acesso negado']);
    exit;
}

require_once __DIR__ . '/../includes/Database.class.php';

function sendJSONResponse($data = null, $error = null, $statusCode = 200) {
    http_response_code($statusCode);
    $response = [
        'success' => $error === null,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    if ($error !== null) {
        $response['error'] = $error;
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

try {
    $db = Database::getInstance();
    $farm_id = $_SESSION['farm_id'] ?? 1;
    $user_id = $_SESSION['user_id'];
    
    $method = $_SERVER['REQUEST_METHOD'];
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $_GET['action'] ?? $input['action'] ?? '';
    
    // ============================================================
    // 1. CADASTRO E IDENTIFICAÇÃO
    // ============================================================
    
    if ($action === 'list' || ($method === 'GET' && empty($action))) {
        // Listar touros com filtros
        $filters = [];
        $params = [];
        
        $breed = $_GET['breed'] ?? null;
        $status = $_GET['status'] ?? null;
        $search = $_GET['search'] ?? null;
        $limit = (int)($_GET['limit'] ?? 50);
        $offset = (int)($_GET['offset'] ?? 0);
        
        $sql = "SELECT 
                    b.*,
                    TIMESTAMPDIFF(YEAR, b.birth_date, CURDATE()) AS age,
                    (SELECT weight FROM bull_body_condition WHERE bull_id = b.id ORDER BY record_date DESC LIMIT 1) AS current_weight,
                    (SELECT body_score FROM bull_body_condition WHERE bull_id = b.id ORDER BY record_date DESC LIMIT 1) AS current_body_score,
                    COUNT(DISTINCT i.id) AS total_inseminations,
                    COUNT(DISTINCT CASE WHEN i.pregnancy_result = 'prenha' THEN i.id END) AS successful_inseminations,
                    COUNT(DISTINCT c.id) AS total_coatings,
                    COUNT(DISTINCT CASE WHEN c.result = 'prenhez' THEN c.id END) AS successful_coatings,
                    COUNT(DISTINCT o.offspring_id) AS total_offspring
                FROM bulls b
                LEFT JOIN inseminations i ON i.bull_id = b.id AND i.farm_id = b.farm_id
                LEFT JOIN bull_coatings c ON c.bull_id = b.id AND c.farm_id = b.farm_id
                LEFT JOIN bull_offspring o ON o.bull_id = b.id AND o.farm_id = b.farm_id
                WHERE b.farm_id = ?";
        
        $params[] = $farm_id;
        
        if ($breed) {
            $sql .= " AND b.breed = ?";
            $params[] = $breed;
        }
        
        if ($status) {
            $sql .= " AND b.status = ?";
            $params[] = $status;
        }
        
        if ($search) {
            $sql .= " AND (b.bull_number LIKE ? OR b.name LIKE ? OR b.earring_number LIKE ? OR b.rfid_code LIKE ?)";
            $searchParam = "%{$search}%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
        }
        
        $sql .= " GROUP BY b.id ORDER BY b.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $bulls = $db->query($sql, $params);
        
        // Calcular eficiência
        foreach ($bulls as &$bull) {
            $total_services = (int)$bull['total_inseminations'] + (int)$bull['total_coatings'];
            $total_successful = (int)$bull['successful_inseminations'] + (int)$bull['successful_coatings'];
            $bull['efficiency_rate'] = $total_services > 0 ? round(($total_successful / $total_services) * 100, 2) : 0;
        }
        
        // Contar total
        $countSql = "SELECT COUNT(DISTINCT b.id) as total FROM bulls b WHERE b.farm_id = ?";
        $countParams = [$farm_id];
        
        if ($breed) {
            $countSql .= " AND b.breed = ?";
            $countParams[] = $breed;
        }
        if ($status) {
            $countSql .= " AND b.status = ?";
            $countParams[] = $status;
        }
        if ($search) {
            $countSql .= " AND (b.bull_number LIKE ? OR b.name LIKE ? OR b.earring_number LIKE ? OR b.rfid_code LIKE ?)";
            $searchParam = "%{$search}%";
            $countParams[] = $searchParam;
            $countParams[] = $searchParam;
            $countParams[] = $searchParam;
            $countParams[] = $searchParam;
        }
        
        $totalResult = $db->query($countSql, $countParams);
        $total = $totalResult[0]['total'] ?? 0;
        
        sendJSONResponse([
            'bulls' => $bulls,
            'pagination' => [
                'total' => (int)$total,
                'limit' => $limit,
                'offset' => $offset,
                'pages' => ceil($total / $limit)
            ]
        ]);
    }
    
    if ($action === 'get') {
        $id = (int)($_GET['id'] ?? $input['id'] ?? 0);
        if ($id <= 0) {
            sendJSONResponse(null, 'ID inválido', 400);
        }
        
        $sql = "SELECT 
                    b.*,
                    TIMESTAMPDIFF(YEAR, b.birth_date, CURDATE()) AS age,
                    COUNT(DISTINCT i.id) AS total_inseminations,
                    COUNT(DISTINCT CASE WHEN i.pregnancy_result = 'prenha' THEN i.id END) AS successful_inseminations,
                    COUNT(DISTINCT c.id) AS total_coatings,
                    COUNT(DISTINCT CASE WHEN c.result = 'prenhez' THEN c.id END) AS successful_coatings,
                    COUNT(DISTINCT o.offspring_id) AS total_offspring
                FROM bulls b
                LEFT JOIN inseminations i ON i.bull_id = b.id AND i.farm_id = b.farm_id
                LEFT JOIN bull_coatings c ON c.bull_id = b.id AND c.farm_id = b.farm_id
                LEFT JOIN bull_offspring o ON o.bull_id = b.id AND o.farm_id = b.farm_id
                WHERE b.id = ? AND b.farm_id = ?
                GROUP BY b.id";
        
        $result = $db->query($sql, [$id, $farm_id]);
        
        if (empty($result)) {
            sendJSONResponse(null, 'Touro não encontrado', 404);
        }
        
        $bull = $result[0];
        
        // Buscar peso atual (último registro de peso/escore)
        $latestWeight = $db->query(
            "SELECT weight FROM bull_body_condition WHERE bull_id = ? ORDER BY record_date DESC LIMIT 1",
            [$id]
        );
        if (!empty($latestWeight)) {
            $bull['current_weight'] = $latestWeight[0]['weight'];
        } else {
            $bull['current_weight'] = $bull['weight'] ?? null;
        }
        
        // Buscar histórico de peso/escore
        $bodyHistory = $db->query(
            "SELECT * FROM bull_body_condition WHERE bull_id = ? ORDER BY record_date DESC LIMIT 12",
            [$id]
        );
        $bull['body_condition_history'] = $bodyHistory;
        
        // Buscar documentos
        $documents = $db->query(
            "SELECT * FROM bull_documents WHERE bull_id = ? AND farm_id = ? ORDER BY created_at DESC",
            [$id, $farm_id]
        );
        $bull['documents'] = $documents ? $documents : [];
        
        // Garantir que arrays vazios sejam retornados ao invés de null
        if (!isset($bull['body_condition_history'])) {
            $bull['body_condition_history'] = [];
        }
        
        sendJSONResponse($bull);
    }
    
    if ($action === 'create' && $method === 'POST') {
        $data = $input;
        
        // Validações obrigatórias
        $required = ['bull_number', 'breed', 'birth_date'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                sendJSONResponse(null, "Campo obrigatório: {$field}", 400);
            }
        }
        
        // Verificar se número já existe
        $exists = $db->query(
            "SELECT id FROM bulls WHERE bull_number = ? AND farm_id = ?",
            [$data['bull_number'], $farm_id]
        );
        if (!empty($exists)) {
            sendJSONResponse(null, 'Número de touro já existe', 400);
        }
        
        // Preparar dados
        $insertData = [
            'bull_number' => sanitizeInput($data['bull_number']),
            'name' => sanitizeInput($data['name'] ?? null),
            'breed' => sanitizeInput($data['breed']),
            'birth_date' => $data['birth_date'],
            'rfid_code' => sanitizeInput($data['rfid_code'] ?? null),
            'earring_number' => sanitizeInput($data['earring_number'] ?? null),
            'weight' => !empty($data['weight']) ? (float)$data['weight'] : null,
            'body_score' => !empty($data['body_score']) ? (float)$data['body_score'] : null,
            'status' => $data['status'] ?? 'ativo',
            'source' => $data['source'] ?? 'proprio',
            'genetic_code' => sanitizeInput($data['genetic_code'] ?? null),
            'sire' => sanitizeInput($data['sire'] ?? null),
            'dam' => sanitizeInput($data['dam'] ?? null),
            'grandsire_father' => sanitizeInput($data['grandsire_father'] ?? null),
            'granddam_father' => sanitizeInput($data['granddam_father'] ?? null),
            'grandsire_mother' => sanitizeInput($data['grandsire_mother'] ?? null),
            'granddam_mother' => sanitizeInput($data['granddam_mother'] ?? null),
            'genetic_merit' => !empty($data['genetic_merit']) ? (float)$data['genetic_merit'] : null,
            'milk_production_index' => !empty($data['milk_production_index']) ? (float)$data['milk_production_index'] : null,
            'fat_production_index' => !empty($data['fat_production_index']) ? (float)$data['fat_production_index'] : null,
            'protein_production_index' => !empty($data['protein_production_index']) ? (float)$data['protein_production_index'] : null,
            'fertility_index' => !empty($data['fertility_index']) ? (float)$data['fertility_index'] : null,
            'health_index' => !empty($data['health_index']) ? (float)$data['health_index'] : null,
            'genetic_evaluation' => sanitizeInput($data['genetic_evaluation'] ?? null),
            'behavior_notes' => sanitizeInput($data['behavior_notes'] ?? null),
            'aptitude_notes' => sanitizeInput($data['aptitude_notes'] ?? null),
            'notes' => sanitizeInput($data['notes'] ?? null),
            'location' => sanitizeInput($data['location'] ?? null),
            'is_breeding_active' => isset($data['is_breeding_active']) ? (int)$data['is_breeding_active'] : 1,
            'farm_id' => $farm_id
        ];
        
        // Remover campos null
        $insertData = array_filter($insertData, function($v) {
            return $v !== null && $v !== '';
        });
        
        $columns = implode(',', array_keys($insertData));
        $placeholders = ':' . implode(', :', array_keys($insertData));
        
        $sql = "INSERT INTO bulls ({$columns}) VALUES ({$placeholders})";
        $stmt = $db->getConnection()->prepare($sql);
        $stmt->execute($insertData);
        
        $bull_id = $db->getConnection()->lastInsertId();
        
        // Se peso/escore foram fornecidos, registrar
        if (!empty($data['weight']) || !empty($data['body_score'])) {
            $bodyData = [
                'bull_id' => $bull_id,
                'record_date' => date('Y-m-d'),
                'weight' => !empty($data['weight']) ? (float)$data['weight'] : null,
                'body_score' => !empty($data['body_score']) ? (float)$data['body_score'] : 1.0,
                'recorded_by' => $user_id,
                'farm_id' => $farm_id
            ];
            $db->insert('bull_body_condition', $bodyData);
        }
        
        sendJSONResponse(['id' => $bull_id, 'message' => 'Touro cadastrado com sucesso']);
    }
    
    if ($action === 'update' && $method === 'PUT') {
        $id = (int)($input['id'] ?? 0);
        if ($id <= 0) {
            sendJSONResponse(null, 'ID inválido', 400);
        }
        
        // Verificar se existe
        $exists = $db->query("SELECT id FROM bulls WHERE id = ? AND farm_id = ?", [$id, $farm_id]);
        if (empty($exists)) {
            sendJSONResponse(null, 'Touro não encontrado', 404);
        }
        
        $data = $input;
        unset($data['id']);
        
        // Preparar dados de atualização
        $updateFields = [];
        $updateParams = [];
        
        $allowedFields = [
            'name', 'breed', 'birth_date', 'rfid_code', 'earring_number', 'weight', 'body_score',
            'status', 'source', 'genetic_code', 'sire', 'dam', 'grandsire_father', 'granddam_father',
            'grandsire_mother', 'granddam_mother', 'genetic_merit', 'milk_production_index',
            'fat_production_index', 'protein_production_index', 'fertility_index', 'health_index',
            'genetic_evaluation', 'behavior_notes', 'aptitude_notes', 'notes', 'location',
            'is_breeding_active', 'photo_url'
        ];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $value = $data[$field];
                if ($value === '' || $value === null) {
                    $updateFields[] = "{$field} = NULL";
                } else {
                    $updateFields[] = "{$field} = ?";
                    if (is_numeric($value) && $field !== 'birth_date') {
                        $updateParams[] = (float)$value;
                    } else {
                        $updateParams[] = sanitizeInput($value);
                    }
                }
            }
        }
        
        if (empty($updateFields)) {
            sendJSONResponse(null, 'Nenhum campo para atualizar', 400);
        }
        
        $updateParams[] = $id;
        $updateParams[] = $farm_id;
        
        $sql = "UPDATE bulls SET " . implode(', ', $updateFields) . ", updated_at = CURRENT_TIMESTAMP WHERE id = ? AND farm_id = ?";
        $db->query($sql, $updateParams);
        
        sendJSONResponse(['id' => $id, 'message' => 'Touro atualizado com sucesso']);
    }
    
    if ($action === 'delete' && $method === 'DELETE') {
        $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
        if ($id <= 0) {
            sendJSONResponse(null, 'ID inválido', 400);
        }
        
        // Verificar se existe
        $exists = $db->query("SELECT id FROM bulls WHERE id = ? AND farm_id = ?", [$id, $farm_id]);
        if (empty($exists)) {
            sendJSONResponse(null, 'Touro não encontrado', 404);
        }
        
        // Soft delete
        $db->query("UPDATE bulls SET is_active = 0, status = 'descartado' WHERE id = ? AND farm_id = ?", [$id, $farm_id]);
        
        sendJSONResponse(['id' => $id, 'message' => 'Touro removido com sucesso']);
    }
    
    // ============================================================
    // 2. CONTROLE REPRODUTIVO - COBERTURAS NATURAIS
    // ============================================================
    
    if ($action === 'coatings_list') {
        $bull_id = $_GET['bull_id'] ?? null;
        
        $sql = "SELECT 
                    c.*,
                    a.animal_number,
                    a.name as cow_name,
                    u.name as technician_name
                FROM bull_coatings c
                LEFT JOIN animals a ON a.id = c.cow_id
                LEFT JOIN users u ON u.id = c.technician_id
                WHERE c.farm_id = ?";
        
        $params = [$farm_id];
        
        if ($bull_id) {
            $sql .= " AND c.bull_id = ?";
            $params[] = (int)$bull_id;
        }
        
        $sql .= " ORDER BY c.coating_date DESC LIMIT 100";
        
        $coatings = $db->query($sql, $params);
        sendJSONResponse(['coatings' => $coatings]);
    }
    
    if ($action === 'coating_create' && $method === 'POST') {
        $data = $input;
        
        $required = ['bull_id', 'cow_id', 'coating_date'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                sendJSONResponse(null, "Campo obrigatório: {$field}", 400);
            }
        }
        
        $insertData = [
            'bull_id' => (int)$data['bull_id'],
            'cow_id' => (int)$data['cow_id'],
            'coating_date' => $data['coating_date'],
            'coating_time' => $data['coating_time'] ?? null,
            'coating_type' => $data['coating_type'] ?? 'natural',
            'result' => $data['result'] ?? 'pendente',
            'pregnancy_check_date' => $data['pregnancy_check_date'] ?? null,
            'pregnancy_check_method' => $data['pregnancy_check_method'] ?? null,
            'technician_id' => !empty($data['technician_id']) ? (int)$data['technician_id'] : null,
            'technician_name' => sanitizeInput($data['technician_name'] ?? null),
            'notes' => sanitizeInput($data['notes'] ?? null),
            'farm_id' => $farm_id,
            'recorded_by' => $user_id
        ];
        
        $insertData = array_filter($insertData, function($v) {
            return $v !== null && $v !== '';
        });
        
        $columns = implode(',', array_keys($insertData));
        $placeholders = ':' . implode(', :', array_keys($insertData));
        
        $sql = "INSERT INTO bull_coatings ({$columns}) VALUES ({$placeholders})";
        $stmt = $db->getConnection()->prepare($sql);
        $stmt->execute($insertData);
        
        $coating_id = $db->getConnection()->lastInsertId();
        
        sendJSONResponse(['id' => $coating_id, 'message' => 'Cobertura registrada com sucesso']);
    }
    
    if ($action === 'coating_update' && $method === 'PUT') {
        $id = (int)($input['id'] ?? 0);
        if ($id <= 0) {
            sendJSONResponse(null, 'ID inválido', 400);
        }
        
        $data = $input;
        unset($data['id']);
        
        $updateFields = [];
        $updateParams = [];
        
        $allowedFields = ['coating_date', 'coating_time', 'coating_type', 'result', 
                         'pregnancy_check_date', 'pregnancy_check_method', 'technician_id',
                         'technician_name', 'notes'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $value = $data[$field];
                if ($value === '' || $value === null) {
                    $updateFields[] = "{$field} = NULL";
                } else {
                    $updateFields[] = "{$field} = ?";
                    $updateParams[] = is_numeric($value) && $field !== 'coating_date' && $field !== 'pregnancy_check_date' ? $value : sanitizeInput($value);
                }
            }
        }
        
        if (empty($updateFields)) {
            sendJSONResponse(null, 'Nenhum campo para atualizar', 400);
        }
        
        $updateParams[] = $id;
        $updateParams[] = $farm_id;
        
        $sql = "UPDATE bull_coatings SET " . implode(', ', $updateFields) . ", updated_at = CURRENT_TIMESTAMP WHERE id = ? AND farm_id = ?";
        $db->query($sql, $updateParams);
        
        sendJSONResponse(['id' => $id, 'message' => 'Cobertura atualizada com sucesso']);
    }
    
    // ============================================================
    // 3. GESTÃO DE SÊMEN
    // ============================================================
    
    if ($action === 'semen_list') {
        $bull_id = $_GET['bull_id'] ?? null;
        
        $sql = "SELECT 
                    s.*,
                    b.bull_number,
                    b.name as bull_name,
                    (s.straws_available + s.straws_used) as total_straws,
                    CASE 
                        WHEN s.expiry_date < CURDATE() THEN 'vencido'
                        WHEN s.expiry_date < DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'proximo_vencimento'
                        ELSE 'valido'
                    END as validity_status,
                    DATEDIFF(s.expiry_date, CURDATE()) as days_until_expiry
                FROM semen_catalog s
                LEFT JOIN bulls b ON b.id = s.bull_id
                WHERE s.farm_id = ?";
        
        $params = [$farm_id];
        
        if ($bull_id) {
            $sql .= " AND s.bull_id = ?";
            $params[] = (int)$bull_id;
        }
        
        $sql .= " ORDER BY s.expiry_date ASC";
        
        $semen = $db->query($sql, $params);
        sendJSONResponse(['semen' => $semen]);
    }
    
    if ($action === 'semen_create' && $method === 'POST') {
        $data = $input;
        
        $required = ['bull_id', 'batch_number', 'production_date', 'expiry_date', 'straws_available', 'price_per_straw'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                sendJSONResponse(null, "Campo obrigatório: {$field}", 400);
            }
        }
        
        $insertData = [
            'bull_id' => (int)$data['bull_id'],
            'batch_number' => sanitizeInput($data['batch_number']),
            'straw_code' => sanitizeInput($data['straw_code'] ?? null),
            'production_date' => $data['production_date'],
            'collection_date' => $data['collection_date'] ?? $data['production_date'],
            'expiry_date' => $data['expiry_date'],
            'straws_available' => (int)$data['straws_available'],
            'straws_used' => 0,
            'price_per_straw' => (float)$data['price_per_straw'],
            'supplier' => sanitizeInput($data['supplier'] ?? null),
            'storage_location' => sanitizeInput($data['storage_location'] ?? null),
            'destination' => sanitizeInput($data['destination'] ?? null),
            'quality_grade' => $data['quality_grade'] ?? 'A',
            'motility' => !empty($data['motility']) ? (float)$data['motility'] : null,
            'volume' => !empty($data['volume']) ? (float)$data['volume'] : null,
            'concentration' => !empty($data['concentration']) ? (float)$data['concentration'] : null,
            'genetic_tests' => sanitizeInput($data['genetic_tests'] ?? null),
            'notes' => sanitizeInput($data['notes'] ?? null),
            'farm_id' => $farm_id
        ];
        
        $insertData = array_filter($insertData, function($v) {
            return $v !== null && $v !== '';
        });
        
        $columns = implode(',', array_keys($insertData));
        $placeholders = ':' . implode(', :', array_keys($insertData));
        
        $sql = "INSERT INTO semen_catalog ({$columns}) VALUES ({$placeholders})";
        $stmt = $db->getConnection()->prepare($sql);
        $stmt->execute($insertData);
        
        $semen_id = $db->getConnection()->lastInsertId();
        
        // Registrar movimentação de entrada
        $movementData = [
            'semen_id' => $semen_id,
            'movement_type' => 'entrada',
            'movement_date' => date('Y-m-d'),
            'quantity' => (int)$data['straws_available'],
            'destination' => sanitizeInput($data['storage_location'] ?? null),
            'reason' => 'Cadastro inicial',
            'recorded_by' => $user_id,
            'farm_id' => $farm_id
        ];
        $db->insert('semen_movements', $movementData);
        
        sendJSONResponse(['id' => $semen_id, 'message' => 'Sêmen cadastrado com sucesso']);
    }
    
    if ($action === 'semen_movements') {
        $semen_id = $_GET['semen_id'] ?? null;
        
        $sql = "SELECT 
                    m.*,
                    s.batch_number,
                    a.animal_number,
                    a.name as animal_name
                FROM semen_movements m
                LEFT JOIN semen_catalog s ON s.id = m.semen_id
                LEFT JOIN animals a ON a.id = m.animal_id
                WHERE m.farm_id = ?";
        
        $params = [$farm_id];
        
        if ($semen_id) {
            $sql .= " AND m.semen_id = ?";
            $params[] = (int)$semen_id;
        }
        
        $sql .= " ORDER BY m.movement_date DESC, m.created_at DESC LIMIT 100";
        
        $movements = $db->query($sql, $params);
        sendJSONResponse(['movements' => $movements]);
    }
    
    // ============================================================
    // 4. HISTÓRICO SANITÁRIO E PESO/ESCORE
    // ============================================================
    
    if ($action === 'health_records') {
        $bull_id = $_GET['bull_id'] ?? null;
        if (!$bull_id) {
            sendJSONResponse(null, 'bull_id é obrigatório', 400);
        }
        
        $records = $db->query(
            "SELECT * FROM bull_health_records WHERE bull_id = ? AND farm_id = ? ORDER BY record_date DESC",
            [(int)$bull_id, $farm_id]
        );
        
        sendJSONResponse(['records' => $records]);
    }
    
    if ($action === 'health_record_create' && $method === 'POST') {
        $data = $input;
        
        $required = ['bull_id', 'record_date', 'record_type', 'record_name'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                sendJSONResponse(null, "Campo obrigatório: {$field}", 400);
            }
        }
        
        $insertData = [
            'bull_id' => (int)$data['bull_id'],
            'record_date' => $data['record_date'],
            'record_type' => $data['record_type'],
            'record_name' => sanitizeInput($data['record_name']),
            'veterinarian_name' => sanitizeInput($data['veterinarian_name'] ?? null),
            'veterinarian_license' => sanitizeInput($data['veterinarian_license'] ?? null),
            'results' => sanitizeInput($data['results'] ?? null),
            'medication_name' => sanitizeInput($data['medication_name'] ?? null),
            'medication_dosage' => sanitizeInput($data['medication_dosage'] ?? null),
            'medication_period' => sanitizeInput($data['medication_period'] ?? null),
            'next_due_date' => $data['next_due_date'] ?? null,
            'cost' => !empty($data['cost']) ? (float)$data['cost'] : null,
            'notes' => sanitizeInput($data['notes'] ?? null),
            'attachments' => !empty($data['attachments']) ? json_encode($data['attachments']) : null,
            'farm_id' => $farm_id,
            'recorded_by' => $user_id
        ];
        
        $insertData = array_filter($insertData, function($v) {
            return $v !== null && $v !== '';
        });
        
        $columns = implode(',', array_keys($insertData));
        $placeholders = ':' . implode(', :', array_keys($insertData));
        
        $sql = "INSERT INTO bull_health_records ({$columns}) VALUES ({$placeholders})";
        $stmt = $db->getConnection()->prepare($sql);
        $stmt->execute($insertData);
        
        $record_id = $db->getConnection()->lastInsertId();
        
        sendJSONResponse(['id' => $record_id, 'message' => 'Registro sanitário criado com sucesso']);
    }
    
    if ($action === 'body_condition_create' && $method === 'POST') {
        $data = $input;
        
        $required = ['bull_id', 'record_date', 'weight', 'body_score'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                sendJSONResponse(null, "Campo obrigatório: {$field}", 400);
            }
        }
        
        $insertData = [
            'bull_id' => (int)$data['bull_id'],
            'record_date' => $data['record_date'],
            'weight' => (float)$data['weight'],
            'body_score' => (float)$data['body_score'],
            'body_score_notes' => sanitizeInput($data['body_score_notes'] ?? null),
            'recorded_by' => $user_id,
            'farm_id' => $farm_id
        ];
        
        $db->insert('bull_body_condition', $insertData);
        
        sendJSONResponse(['message' => 'Registro de peso/escore criado com sucesso']);
    }
    
    // ============================================================
    // 5. DOCUMENTOS E ANEXOS
    // ============================================================
    
    if ($action === 'documents_list') {
        $bull_id = $_GET['bull_id'] ?? null;
        if (!$bull_id) {
            sendJSONResponse(null, 'bull_id é obrigatório', 400);
        }
        
        $documents = $db->query(
            "SELECT * FROM bull_documents WHERE bull_id = ? AND farm_id = ? ORDER BY created_at DESC",
            [(int)$bull_id, $farm_id]
        );
        
        sendJSONResponse(['documents' => $documents]);
    }
    
    if ($action === 'document_create' && $method === 'POST') {
        $bull_id = $_POST['bull_id'] ?? null;
        if (!$bull_id) {
            sendJSONResponse(null, 'bull_id é obrigatório', 400);
        }
        
        $document_type = $_POST['document_type'] ?? null;
        $document_name = $_POST['document_name'] ?? null;
        
        if (!$document_type || !$document_name) {
            sendJSONResponse(null, 'document_type e document_name são obrigatórios', 400);
        }
        
        // Processar upload do arquivo
        $uploadDir = __DIR__ . '/../uploads/bull_documents/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $filePath = null;
        $fileName = null;
        $fileSize = null;
        $mimeType = null;
        
        if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['file'];
            $fileName = basename($file['name']);
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowedExts = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
            
            if (!in_array($fileExt, $allowedExts)) {
                sendJSONResponse(null, 'Tipo de arquivo não permitido. Use: PDF, JPG, PNG, DOC, DOCX', 400);
            }
            
            $newFileName = 'bull_' . $bull_id . '_' . time() . '_' . $fileName;
            $filePath = $uploadDir . $newFileName;
            
            if (!move_uploaded_file($file['tmp_name'], $filePath)) {
                sendJSONResponse(null, 'Erro ao fazer upload do arquivo', 500);
            }
            
            $fileSize = $file['size'];
            $mimeType = $file['type'];
            $filePath = 'uploads/bull_documents/' . $newFileName;
        } else {
            sendJSONResponse(null, 'Arquivo não foi enviado ou houve erro no upload', 400);
        }
        
        $insertData = [
            'bull_id' => (int)$bull_id,
            'document_type' => sanitizeInput($document_type),
            'document_name' => sanitizeInput($document_name),
            'file_path' => $filePath,
            'file_size' => $fileSize,
            'mime_type' => $mimeType,
            'description' => sanitizeInput($_POST['description'] ?? null),
            'issue_date' => $_POST['issue_date'] ?? null,
            'expiry_date' => $_POST['expiry_date'] ?? null,
            'farm_id' => $farm_id,
            'uploaded_by' => $user_id
        ];
        
        $insertData = array_filter($insertData, function($v) {
            return $v !== null && $v !== '';
        });
        
        $columns = implode(',', array_keys($insertData));
        $placeholders = ':' . implode(', :', array_keys($insertData));
        
        $sql = "INSERT INTO bull_documents ({$columns}) VALUES ({$placeholders})";
        $stmt = $db->getConnection()->prepare($sql);
        $stmt->execute($insertData);
        
        $document_id = $db->getConnection()->lastInsertId();
        
        sendJSONResponse(['id' => $document_id, 'message' => 'Documento salvo com sucesso']);
    }
    
    if ($action === 'document_delete' && $method === 'DELETE') {
        $id = (int)($input['id'] ?? 0);
        if ($id <= 0) {
            sendJSONResponse(null, 'ID inválido', 400);
        }
        
        // Buscar documento para deletar arquivo
        $document = $db->query(
            "SELECT file_path FROM bull_documents WHERE id = ? AND farm_id = ?",
            [$id, $farm_id]
        );
        
        if (!empty($document) && isset($document[0]['file_path'])) {
            $filePath = __DIR__ . '/../' . $document[0]['file_path'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
        
        $db->query("DELETE FROM bull_documents WHERE id = ? AND farm_id = ?", [$id, $farm_id]);
        
        sendJSONResponse(['id' => $id, 'message' => 'Documento removido com sucesso']);
    }
    
    // ============================================================
    // 6. RELATÓRIOS E ANÁLISES
    // ============================================================
    
    if ($action === 'statistics') {
        $bull_id = $_GET['bull_id'] ?? null;
        
        if ($bull_id) {
            // Estatísticas de um touro específico
            $sql = "SELECT * FROM v_bull_statistics_complete WHERE id = ? AND farm_id = ?";
            $result = $db->query($sql, [(int)$bull_id, $farm_id]);
            
            if (empty($result)) {
                sendJSONResponse(null, 'Touro não encontrado', 404);
            }
            
            $stats = $result[0];
            
            // Calcular eficiência geral
            $total_services = (int)($stats['total_services'] ?? 0);
            $total_successful = (int)($stats['total_successful'] ?? 0);
            $stats['overall_efficiency'] = $total_services > 0 ? round(($total_successful / $total_services) * 100, 2) : 0;
            
            // Calcular sêmen disponível para este touro
            $semenStats = $db->query(
                "SELECT SUM(straws_available) as total_available
                FROM semen_catalog
                WHERE bull_id = ? AND farm_id = ?",
                [(int)$bull_id, $farm_id]
            );
            $stats['semen_straws_available'] = (int)($semenStats[0]['total_available'] ?? 0);
            
            sendJSONResponse($stats);
        } else {
            // Estatísticas gerais
            $sql = "SELECT 
                        COUNT(*) as total_bulls,
                        COUNT(CASE WHEN status = 'ativo' THEN 1 END) as active_bulls,
                        COUNT(CASE WHEN status = 'em_reproducao' THEN 1 END) as breeding_bulls,
                        SUM(total_services) as total_services_all,
                        SUM(total_successful) as total_successful_all,
                        AVG(CASE WHEN total_services > 0 THEN (total_successful / total_services) * 100 ELSE 0 END) as avg_efficiency,
                        SUM(total_offspring) as total_offspring_all
                    FROM v_bull_statistics_complete
                    WHERE farm_id = ?";
            
            $result = $db->query($sql, [$farm_id]);
            $stats = $result[0] ?? [];
            
            // Sêmen disponível
            $semenStats = $db->query(
                "SELECT 
                    COUNT(*) as total_batches,
                    SUM(straws_available) as total_available,
                    SUM(straws_used) as total_used,
                    COUNT(CASE WHEN expiry_date < CURDATE() THEN 1 END) as expired,
                    COUNT(CASE WHEN expiry_date < DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND expiry_date >= CURDATE() THEN 1 END) as expiring_soon
                FROM semen_catalog
                WHERE farm_id = ?",
                [$farm_id]
            );
            
            $stats['semen'] = $semenStats[0] ?? [];
            
            sendJSONResponse($stats);
        }
    }
    
    if ($action === 'ranking') {
        $limit = (int)($_GET['limit'] ?? 10);
        
        $ranking = $db->query(
            "SELECT * FROM v_bull_efficiency_ranking LIMIT ?",
            [$limit]
        );
        
        sendJSONResponse(['ranking' => $ranking]);
    }
    
    if ($action === 'offspring') {
        $bull_id = $_GET['bull_id'] ?? null;
        if (!$bull_id) {
            sendJSONResponse(null, 'bull_id é obrigatório', 400);
        }
        
        $offspring = $db->query(
            "SELECT 
                o.*,
                a.animal_number,
                a.name as animal_name,
                a.breed,
                a.birth_date,
                a.status,
                a.gender
            FROM bull_offspring o
            LEFT JOIN animals a ON a.id = o.offspring_id
            WHERE o.bull_id = ? AND o.farm_id = ?
            ORDER BY o.birth_date DESC",
            [(int)$bull_id, $farm_id]
        );
        
        sendJSONResponse(['offspring' => $offspring]);
    }
    
    if ($action === 'alerts') {
        // Alertas de validade de sêmen
        $semenAlerts = $db->query(
            "SELECT 
                s.*,
                b.bull_number,
                b.name as bull_name,
                DATEDIFF(s.expiry_date, CURDATE()) as days_until_expiry
            FROM semen_catalog s
            LEFT JOIN bulls b ON b.id = s.bull_id
            WHERE s.farm_id = ? 
            AND s.straws_available > 0
            AND (
                s.expiry_date < CURDATE() 
                OR s.expiry_date < DATE_ADD(CURDATE(), INTERVAL 30 DAY)
            )
            ORDER BY s.expiry_date ASC",
            [$farm_id]
        );
        
        // Alertas de baixa eficiência
        $lowEfficiency = $db->query(
            "SELECT * FROM v_bull_statistics_complete 
            WHERE farm_id = ? 
            AND status IN ('ativo', 'em_reproducao')
            AND total_services >= 10
            AND CASE 
                WHEN total_services > 0 
                THEN (total_successful / total_services) * 100 
                ELSE 0 
            END < 50
            ORDER BY 
                CASE 
                    WHEN total_services > 0 
                    THEN (total_successful / total_services) * 100 
                    ELSE 0 
                END ASC",
            [$farm_id]
        );
        
        sendJSONResponse([
            'semen_expiry' => $semenAlerts,
            'low_efficiency' => $lowEfficiency
        ]);
    }
    
    // Ação não encontrada
    sendJSONResponse(null, 'Ação não encontrada', 404);
    
} catch (Exception $e) {
    error_log("Erro na API de touros: " . $e->getMessage());
    sendJSONResponse(null, 'Erro interno: ' . $e->getMessage(), 500);
}
