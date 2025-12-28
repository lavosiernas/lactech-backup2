<?php
/**
 * API: Gestão de Rebanho - Lactech
 * Sistema completo de gestão de animais do rebanho
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
        // DASHBOARD - Estatísticas do Rebanho
        // ==========================================
        case 'dashboard':
            $stats = [];
            
            // Total de animais ativos
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM animals WHERE is_active = 1 AND farm_id = ?");
            $stmt->execute([$farm_id]);
            $stats['total_animals'] = (int)($stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);
            
            // Animais lactantes
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM animals WHERE is_active = 1 AND farm_id = ? AND status = 'Lactante'");
            $stmt->execute([$farm_id]);
            $stats['lactating'] = (int)($stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);
            
            // Animais prenhes
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM animals WHERE is_active = 1 AND farm_id = ? AND reproductive_status = 'prenha'");
            $stmt->execute([$farm_id]);
            $stats['pregnant'] = (int)($stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);
            
            // Animais secos
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM animals WHERE is_active = 1 AND farm_id = ? AND status = 'Seco'");
            $stmt->execute([$farm_id]);
            $stats['dry'] = (int)($stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);
            
            // Novilhas
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM animals WHERE is_active = 1 AND farm_id = ? AND status = 'Novilha'");
            $stmt->execute([$farm_id]);
            $stats['heifers'] = (int)($stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);
            
            // Produção média diária (últimos 7 dias)
            $stmt = $conn->prepare("
                SELECT AVG(volume) as avg_volume
                FROM milk_production
                WHERE production_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                AND farm_id = ?
            ");
            $stmt->execute([$farm_id]);
            $production = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['avg_daily_production'] = round((float)($production['avg_volume'] ?? 0), 2);
            
            // Distribuição por raça
            $stmt = $conn->prepare("
                SELECT breed, COUNT(*) as count
                FROM animals
                WHERE is_active = 1 AND farm_id = ?
                GROUP BY breed
                ORDER BY count DESC
            ");
            $stmt->execute([$farm_id]);
            $stats['breed_distribution'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Distribuição por status de saúde
            $stmt = $conn->prepare("
                SELECT health_status, COUNT(*) as count
                FROM animals
                WHERE is_active = 1 AND farm_id = ?
                GROUP BY health_status
            ");
            $stmt->execute([$farm_id]);
            $stats['health_distribution'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            sendResponse($stats);
            break;
            
        // ==========================================
        // LISTAR ANIMAIS
        // ==========================================
        case 'animals_list':
            $limit = (int)($_GET['limit'] ?? 50);
            $offset = (int)($_GET['offset'] ?? 0);
            $search = $_GET['search'] ?? '';
            $status = $_GET['status'] ?? '';
            $breed = $_GET['breed'] ?? '';
            $health_status = $_GET['health_status'] ?? '';
            $reproductive_status = $_GET['reproductive_status'] ?? '';
            
            $where = ["a.is_active = 1", "a.farm_id = ?"];
            $params = [$farm_id];
            
            if ($search) {
                $where[] = "(a.name LIKE ? OR a.animal_number LIKE ?)";
                $searchTerm = "%$search%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            if ($status) {
                $where[] = "a.status = ?";
                $params[] = $status;
            }
            if ($breed) {
                $where[] = "a.breed = ?";
                $params[] = $breed;
            }
            if ($health_status) {
                $where[] = "a.health_status = ?";
                $params[] = $health_status;
            }
            if ($reproductive_status) {
                $where[] = "a.reproductive_status = ?";
                $params[] = $reproductive_status;
            }
            
            $stmt = $conn->prepare("
                SELECT 
                    a.*,
                    DATEDIFF(CURDATE(), a.birth_date) as age_days,
                    FLOOR(DATEDIFF(CURDATE(), a.birth_date) / 30.44) as age_months,
                    g.group_name,
                    g.group_type,
                    g.color_code as group_color,
                    f.name as father_name,
                    m.name as mother_name
                FROM animals a
                LEFT JOIN animal_groups g ON a.current_group_id = g.id
                LEFT JOIN animals f ON a.father_id = f.id
                LEFT JOIN animals m ON a.mother_id = m.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY a.animal_number ASC, a.name ASC
                LIMIT ? OFFSET ?
            ");
            $params[] = $limit;
            $params[] = $offset;
            $stmt->execute($params);
            $animals = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Contar total
            $stmt = $conn->prepare("
                SELECT COUNT(*) as total
                FROM animals a
                WHERE " . implode(' AND ', $where) . "
            ");
            $stmt->execute(array_slice($params, 0, -2));
            $total = (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
            
            sendResponse([
                'animals' => $animals,
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset
            ]);
            break;
            
        // ==========================================
        // OBTER ANIMAL ESPECÍFICO
        // ==========================================
        case 'animal_get':
            $id = $_GET['id'] ?? null;
            if (!$id) {
                sendResponse(null, 'ID não fornecido', 400);
            }
            
            $stmt = $conn->prepare("
                SELECT 
                    a.*,
                    DATEDIFF(CURDATE(), a.birth_date) as age_days,
                    FLOOR(DATEDIFF(CURDATE(), a.birth_date) / 30.44) as age_months,
                    g.group_name,
                    g.group_type,
                    g.color_code as group_color,
                    f.name as father_name,
                    f.animal_number as father_number,
                    m.name as mother_name,
                    m.animal_number as mother_number
                FROM animals a
                LEFT JOIN animal_groups g ON a.current_group_id = g.id
                LEFT JOIN animals f ON a.father_id = f.id
                LEFT JOIN animals m ON a.mother_id = m.id
                WHERE a.id = ? AND a.farm_id = ?
            ");
            $stmt->execute([$id, $farm_id]);
            $animal = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$animal) {
                sendResponse(null, 'Animal não encontrado', 404);
            }
            
            // Buscar último BCS
            $stmt = $conn->prepare("
                SELECT score, evaluation_date, notes
                FROM body_condition_scores
                WHERE animal_id = ?
                ORDER BY evaluation_date DESC
                LIMIT 1
            ");
            $stmt->execute([$id]);
            $animal['latest_bcs'] = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Buscar última produção
            $stmt = $conn->prepare("
                SELECT volume, production_date
                FROM milk_production
                WHERE animal_id = ?
                ORDER BY production_date DESC
                LIMIT 1
            ");
            $stmt->execute([$id]);
            $animal['latest_production'] = $stmt->fetch(PDO::FETCH_ASSOC);
            
            sendResponse($animal);
            break;
            
        // ==========================================
        // HISTÓRICO DE SAÚDE
        // ==========================================
        case 'health_history':
            $animal_id = $_GET['animal_id'] ?? null;
            if (!$animal_id) {
                sendResponse(null, 'ID do animal não fornecido', 400);
            }
            
            $limit = (int)($_GET['limit'] ?? 50);
            
            $stmt = $conn->prepare("
                SELECT 
                    hr.*,
                    u.name as recorded_by_name
                FROM health_records hr
                LEFT JOIN users u ON hr.recorded_by = u.id
                WHERE hr.animal_id = ? AND hr.farm_id = ?
                ORDER BY hr.record_date DESC, hr.created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$animal_id, $farm_id, $limit]);
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            sendResponse($records);
            break;
            
        // ==========================================
        // HISTÓRICO DE BCS
        // ==========================================
        case 'bcs_history':
            $animal_id = $_GET['animal_id'] ?? null;
            if (!$animal_id) {
                sendResponse(null, 'ID do animal não fornecido', 400);
            }
            
            $limit = (int)($_GET['limit'] ?? 20);
            
            $stmt = $conn->prepare("
                SELECT 
                    bcs.*,
                    u.name as recorded_by_name
                FROM body_condition_scores bcs
                LEFT JOIN users u ON bcs.recorded_by = u.id
                WHERE bcs.animal_id = ? AND bcs.farm_id = ?
                ORDER BY bcs.evaluation_date DESC, bcs.created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$animal_id, $farm_id, $limit]);
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            sendResponse($records);
            break;
            
        // ==========================================
        // HISTÓRICO DE PRODUÇÃO
        // ==========================================
        case 'production_history':
            $animal_id = $_GET['animal_id'] ?? null;
            if (!$animal_id) {
                sendResponse(null, 'ID do animal não fornecido', 400);
            }
            
            $limit = (int)($_GET['limit'] ?? 30);
            $date_from = $_GET['date_from'] ?? null;
            $date_to = $_GET['date_to'] ?? null;
            
            $where = ["mp.animal_id = ?", "mp.farm_id = ?"];
            $params = [$animal_id, $farm_id];
            
            if ($date_from) {
                $where[] = "mp.production_date >= ?";
                $params[] = $date_from;
            }
            if ($date_to) {
                $where[] = "mp.production_date <= ?";
                $params[] = $date_to;
            }
            
            $stmt = $conn->prepare("
                SELECT 
                    mp.*,
                    u.name as recorded_by_name
                FROM milk_production mp
                LEFT JOIN users u ON mp.recorded_by = u.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY mp.production_date DESC, mp.created_at DESC
                LIMIT ?
            ");
            $params[] = $limit;
            $stmt->execute($params);
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            sendResponse($records);
            break;
            
        // ==========================================
        // PEDIGREE
        // ==========================================
        case 'pedigree':
            $animal_id = $_GET['animal_id'] ?? null;
            if (!$animal_id) {
                sendResponse(null, 'ID do animal não fornecido', 400);
            }
            
            $stmt = $conn->prepare("
                SELECT 
                    pr.*,
                    a.animal_number as related_animal_number,
                    a.name as related_animal_name,
                    a.breed as related_animal_breed
                FROM pedigree_records pr
                LEFT JOIN animals a ON pr.related_animal_id = a.id
                WHERE pr.animal_id = ? AND pr.farm_id = ?
                ORDER BY pr.generation ASC, pr.position ASC
            ");
            $stmt->execute([$animal_id, $farm_id]);
            $pedigree = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            sendResponse($pedigree);
            break;
            
        // ==========================================
        // CRIAR/ATUALIZAR ANIMAL
        // ==========================================
        case 'animal_create':
        case 'animal_update':
            if ($method !== 'POST' && $method !== 'PUT') {
                sendResponse(null, 'Método não permitido', 405);
            }
            
            $isUpdate = $action === 'animal_update';
            $id = $isUpdate ? ($input['id'] ?? $_GET['id'] ?? null) : null;
            
            if ($isUpdate && !$id) {
                sendResponse(null, 'ID não fornecido', 400);
            }
            
            $required = ['animal_number', 'breed', 'gender', 'birth_date'];
            foreach ($required as $field) {
                if (!$isUpdate && empty($input[$field])) {
                    sendResponse(null, "Campo obrigatório: $field", 400);
                }
            }
            
            if ($isUpdate) {
                // Atualizar
                $allowedFields = [
                    'animal_number', 'name', 'breed', 'gender', 'birth_date', 'birth_weight',
                    'father_id', 'mother_id', 'status', 'current_group_id', 'health_status',
                    'reproductive_status', 'entry_date', 'exit_date', 'exit_reason', 'notes'
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
                
                $params[] = $id;
                $params[] = $farm_id;
                
                $stmt = $conn->prepare("
                    UPDATE animals 
                    SET " . implode(', ', $updates) . ", updated_at = NOW()
                    WHERE id = ? AND farm_id = ?
                ");
                $stmt->execute($params);
                
                sendResponse(['id' => $id, 'message' => 'Animal atualizado com sucesso']);
            } else {
                // Criar
                $stmt = $conn->prepare("
                    INSERT INTO animals (
                        animal_number, name, breed, gender, birth_date, birth_weight,
                        father_id, mother_id, status, current_group_id, health_status,
                        reproductive_status, entry_date, exit_date, exit_reason, notes,
                        farm_id, is_active
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
                ");
                
                $stmt->execute([
                    $input['animal_number'],
                    $input['name'] ?? null,
                    $input['breed'],
                    $input['gender'],
                    $input['birth_date'],
                    !empty($input['birth_weight']) ? (float)$input['birth_weight'] : null,
                    !empty($input['father_id']) ? (int)$input['father_id'] : null,
                    !empty($input['mother_id']) ? (int)$input['mother_id'] : null,
                    $input['status'] ?? 'Bezerra',
                    !empty($input['current_group_id']) ? (int)$input['current_group_id'] : null,
                    $input['health_status'] ?? 'saudavel',
                    $input['reproductive_status'] ?? 'vazia',
                    $input['entry_date'] ?? null,
                    $input['exit_date'] ?? null,
                    $input['exit_reason'] ?? null,
                    $input['notes'] ?? null,
                    $farm_id
                ]);
                
                $id = $conn->lastInsertId();
                sendResponse(['id' => $id, 'message' => 'Animal criado com sucesso']);
            }
            break;
            
        // ==========================================
        // REGISTRAR BCS
        // ==========================================
        case 'bcs_create':
            if ($method !== 'POST') {
                sendResponse(null, 'Método não permitido', 405);
            }
            
            $required = ['animal_id', 'score', 'evaluation_date'];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    sendResponse(null, "Campo obrigatório: $field", 400);
                }
            }
            
            $stmt = $conn->prepare("
                INSERT INTO body_condition_scores (
                    animal_id, score, evaluation_date, notes, recorded_by, farm_id
                ) VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                (int)$input['animal_id'],
                (float)$input['score'],
                $input['evaluation_date'],
                $input['notes'] ?? null,
                $user_id,
                $farm_id
            ]);
            
            $id = $conn->lastInsertId();
            sendResponse(['id' => $id, 'message' => 'BCS registrado com sucesso']);
            break;
            
        // ==========================================
        // REGISTRAR SAÚDE
        // ==========================================
        case 'health_create':
            if ($method !== 'POST') {
                sendResponse(null, 'Método não permitido', 405);
            }
            
            $required = ['animal_id', 'record_date', 'record_type', 'description'];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    sendResponse(null, "Campo obrigatório: $field", 400);
                }
            }
            
            $stmt = $conn->prepare("
                INSERT INTO health_records (
                    animal_id, record_date, record_type, description, medication, dosage,
                    cost, next_date, veterinarian, notes, recorded_by, farm_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                (int)$input['animal_id'],
                $input['record_date'],
                $input['record_type'],
                $input['description'],
                $input['medication'] ?? null,
                $input['dosage'] ?? null,
                !empty($input['cost']) ? (float)$input['cost'] : null,
                $input['next_date'] ?? null,
                $input['veterinarian'] ?? null,
                $input['notes'] ?? null,
                $user_id,
                $farm_id
            ]);
            
            $id = $conn->lastInsertId();
            
            // Atualizar health_status do animal se necessário
            if (isset($input['update_health_status'])) {
                $newStatus = $input['update_health_status'];
                $stmt = $conn->prepare("UPDATE animals SET health_status = ? WHERE id = ? AND farm_id = ?");
                $stmt->execute([$newStatus, $input['animal_id'], $farm_id]);
            }
            
            sendResponse(['id' => $id, 'message' => 'Registro de saúde criado com sucesso']);
            break;
            
        // ==========================================
        // LISTAR RAÇAS
        // ==========================================
        case 'breeds':
            $stmt = $conn->prepare("
                SELECT DISTINCT breed
                FROM animals
                WHERE is_active = 1 AND farm_id = ?
                ORDER BY breed ASC
            ");
            $stmt->execute([$farm_id]);
            $breeds = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            sendResponse($breeds);
            break;
            
        // ==========================================
        // LISTAR GRUPOS
        // ==========================================
        case 'groups':
            $stmt = $conn->prepare("
                SELECT id, group_name, group_code, group_type, color_code
                FROM animal_groups
                WHERE is_active = 1 AND farm_id = ?
                ORDER BY group_name ASC
            ");
            $stmt->execute([$farm_id]);
            $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            sendResponse($groups);
            break;
            
        // ==========================================
        // CRIAR/ATUALIZAR PEDIGREE
        // ==========================================
        case 'pedigree_create':
        case 'pedigree_update':
            if ($method !== 'POST' && $method !== 'PUT') {
                sendResponse(null, 'Método não permitido', 405);
            }
            
            $animal_id = $input['animal_id'] ?? $_GET['animal_id'] ?? null;
            if (!$animal_id) {
                sendResponse(null, 'ID do animal não fornecido', 400);
            }
            
            $required = ['generation', 'position'];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    sendResponse(null, "Campo obrigatório: $field", 400);
                }
            }
            
            // Verificar se já existe registro para esta posição
            $stmt = $conn->prepare("
                SELECT id FROM pedigree_records 
                WHERE animal_id = ? AND generation = ? AND position = ? AND farm_id = ?
            ");
            $stmt->execute([$animal_id, $input['generation'], $input['position'], $farm_id]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing) {
                // Atualizar
                $stmt = $conn->prepare("
                    UPDATE pedigree_records 
                    SET related_animal_id = ?, animal_number = ?, animal_name = ?, breed = ?, notes = ?, updated_at = NOW()
                    WHERE id = ? AND farm_id = ?
                ");
                
                $stmt->execute([
                    !empty($input['related_animal_id']) ? (int)$input['related_animal_id'] : null,
                    $input['animal_number'] ?? null,
                    $input['animal_name'] ?? null,
                    $input['breed'] ?? null,
                    $input['notes'] ?? null,
                    $existing['id'],
                    $farm_id
                ]);
                
                sendResponse(['id' => $existing['id'], 'message' => 'Pedigree atualizado com sucesso']);
            } else {
                // Criar
                $stmt = $conn->prepare("
                    INSERT INTO pedigree_records (
                        animal_id, generation, position, related_animal_id,
                        animal_number, animal_name, breed, notes, farm_id
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    (int)$animal_id,
                    (int)$input['generation'],
                    $input['position'],
                    !empty($input['related_animal_id']) ? (int)$input['related_animal_id'] : null,
                    $input['animal_number'] ?? null,
                    $input['animal_name'] ?? null,
                    $input['breed'] ?? null,
                    $input['notes'] ?? null,
                    $farm_id
                ]);
                
                $id = $conn->lastInsertId();
                sendResponse(['id' => $id, 'message' => 'Pedigree criado com sucesso']);
            }
            break;
            
        // ==========================================
        // DELETAR PEDIGREE
        // ==========================================
        case 'pedigree_delete':
            if ($method !== 'DELETE' && $method !== 'POST') {
                sendResponse(null, 'Método não permitido', 405);
            }
            
            $id = $input['id'] ?? $_GET['id'] ?? null;
            if (!$id) {
                sendResponse(null, 'ID não fornecido', 400);
            }
            
            $stmt = $conn->prepare("DELETE FROM pedigree_records WHERE id = ? AND farm_id = ?");
            $stmt->execute([$id, $farm_id]);
            
            sendResponse(['id' => $id, 'message' => 'Registro de pedigree excluído com sucesso']);
            break;
            
        default:
            sendResponse(null, 'Ação não encontrada', 404);
    }
    
} catch (Exception $e) {
    sendResponse(null, 'Erro interno: ' . $e->getMessage(), 500);
}
?>

