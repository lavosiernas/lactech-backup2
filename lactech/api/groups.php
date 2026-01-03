<?php
/**
 * API de Grupos de Animais - Lactech
 * Sistema completo de grupos/lotes
 */

// Configurações de segurança
error_reporting(0);
ini_set('display_errors', 0);

// Headers para JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Tratar requisições OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar autenticação
if (!isset($_SESSION['user_id'])) {
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
        // LISTAR GRUPOS
        // ==========================================
        case 'list':
            $stmt = $conn->prepare("
                SELECT 
                    g.*,
                    COUNT(DISTINCT a.id) as actual_count
                FROM animal_groups g
                LEFT JOIN animals a ON a.current_group_id = g.id AND a.is_active = 1 AND a.farm_id = ?
                WHERE g.farm_id = ? AND g.is_active = 1
                GROUP BY g.id
                ORDER BY g.group_type, g.group_name
            ");
            $stmt->execute([$farm_id, $farm_id]);
            $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Atualizar current_count no banco
            foreach ($groups as $group) {
                $updateStmt = $conn->prepare("
                    UPDATE animal_groups 
                    SET current_count = ? 
                    WHERE id = ?
                ");
                $updateStmt->execute([$group['actual_count'], $group['id']]);
                $group['current_count'] = $group['actual_count'];
            }
            
            sendResponse($groups);
            break;
            
        // ==========================================
        // OBTER GRUPO POR ID
        // ==========================================
        case 'get':
            $id = $_GET['id'] ?? $input['id'] ?? null;
            if (!$id) {
                sendResponse(null, 'ID não fornecido', 400);
            }
            
            $stmt = $conn->prepare("
                SELECT 
                    g.*,
                    COUNT(DISTINCT a.id) as actual_count
                FROM animal_groups g
                LEFT JOIN animals a ON a.current_group_id = g.id AND a.is_active = 1 AND a.farm_id = ?
                WHERE g.id = ? AND g.farm_id = ?
                GROUP BY g.id
            ");
            $stmt->execute([$farm_id, $id, $farm_id]);
            $group = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$group) {
                sendResponse(null, 'Grupo não encontrado', 404);
            }
            
            $group['current_count'] = $group['actual_count'];
            unset($group['actual_count']);
            
            sendResponse($group);
            break;
            
        // ==========================================
        // CRIAR GRUPO
        // ==========================================
        case 'create':
            $data = $input;
            
            $required = ['group_name', 'group_type'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    sendResponse(null, "Campo obrigatório: {$field}", 400);
                }
            }
            
            $stmt = $conn->prepare("
                INSERT INTO animal_groups 
                (group_name, group_code, group_type, description, location, capacity, 
                 feed_protocol, milking_order, color_code, farm_id, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['group_name'],
                $data['group_code'] ?? null,
                $data['group_type'],
                $data['description'] ?? null,
                $data['location'] ?? null,
                !empty($data['capacity']) ? (int)$data['capacity'] : null,
                $data['feed_protocol'] ?? null,
                !empty($data['milking_order']) ? (int)$data['milking_order'] : null,
                $data['color_code'] ?? '#6B7280',
                $farm_id,
                $user_id
            ]);
            
            $group_id = $conn->lastInsertId();
            
            // Se houver animais para adicionar, movê-los para o grupo
            if (!empty($data['animal_ids']) && is_array($data['animal_ids'])) {
                $animal_ids = array_map('intval', $data['animal_ids']);
                $animal_ids = array_filter($animal_ids);
                
                if (!empty($animal_ids)) {
                    $placeholders = implode(',', array_fill(0, count($animal_ids), '?'));
                    $stmt = $conn->prepare("
                        UPDATE animals 
                        SET current_group_id = ? 
                        WHERE id IN ($placeholders) AND farm_id = ? AND is_active = 1
                    ");
                    $params = array_merge([$group_id], $animal_ids, [$farm_id]);
                    $stmt->execute($params);
                }
            }
            
            sendResponse(['id' => $group_id, 'message' => 'Grupo criado com sucesso']);
            break;
            
        // ==========================================
        // ATUALIZAR GRUPO
        // ==========================================
        case 'update':
            $id = $input['id'] ?? null;
            if (!$id) {
                sendResponse(null, 'ID não fornecido', 400);
            }
            
            // Verificar se existe
            $checkStmt = $conn->prepare("SELECT id FROM animal_groups WHERE id = ? AND farm_id = ?");
            $checkStmt->execute([$id, $farm_id]);
            if (!$checkStmt->fetch()) {
                sendResponse(null, 'Grupo não encontrado', 404);
            }
            
            $data = $input;
            unset($data['id']);
            
            $updateFields = [];
            $updateParams = [];
            
            $allowedFields = ['group_name', 'group_code', 'group_type', 'description', 
                             'location', 'capacity', 'feed_protocol', 'milking_order', 'color_code', 'is_active'];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateFields[] = "{$field} = ?";
                    if ($field === 'capacity' || $field === 'milking_order' || $field === 'is_active') {
                        $updateParams[] = !empty($data[$field]) ? (int)$data[$field] : null;
                    } else {
                        $updateParams[] = $data[$field] === '' ? null : $data[$field];
                    }
                }
            }
            
            if (empty($updateFields)) {
                sendResponse(null, 'Nenhum campo para atualizar', 400);
            }
            
            $updateParams[] = $id;
            $updateParams[] = $farm_id;
            
            $sql = "UPDATE animal_groups SET " . implode(', ', $updateFields) . " WHERE id = ? AND farm_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute($updateParams);
            
            // Se houver animais para atualizar, movê-los para o grupo
            if (isset($data['animal_ids']) && is_array($data['animal_ids'])) {
                $animal_ids = array_map('intval', $data['animal_ids']);
                $animal_ids = array_filter($animal_ids);
                
                if (!empty($animal_ids)) {
                    $placeholders = implode(',', array_fill(0, count($animal_ids), '?'));
                    
                    // Mover animais selecionados para este grupo
                    $stmt = $conn->prepare("
                        UPDATE animals 
                        SET current_group_id = ? 
                        WHERE id IN ($placeholders) AND farm_id = ? AND is_active = 1
                    ");
                    $params = array_merge([$id], $animal_ids, [$farm_id]);
                    $stmt->execute($params);
                }
            }
            
            sendResponse(['id' => $id, 'message' => 'Grupo atualizado com sucesso']);
            break;
            
        // ==========================================
        // DELETAR GRUPO
        // ==========================================
        case 'delete':
            $id = $_GET['id'] ?? $input['id'] ?? null;
            if (!$id) {
                sendResponse(null, 'ID não fornecido', 400);
            }
            
            // Verificar se tem animais no grupo
            $checkStmt = $conn->prepare("
                SELECT COUNT(*) as count 
                FROM animals 
                WHERE current_group_id = ? AND is_active = 1
            ");
            $checkStmt->execute([$id]);
            $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                sendResponse(null, 'Não é possível excluir grupo com animais. Remova os animais primeiro.', 400);
            }
            
            // Soft delete
            $stmt = $conn->prepare("UPDATE animal_groups SET is_active = 0 WHERE id = ? AND farm_id = ?");
            $stmt->execute([$id, $farm_id]);
            
            sendResponse(['id' => $id, 'message' => 'Grupo excluído com sucesso']);
            break;
            
        // ==========================================
        // LISTAR ANIMAIS DO GRUPO
        // ==========================================
        case 'animals':
            $group_id = $_GET['group_id'] ?? null;
            if (!$group_id) {
                sendResponse(null, 'ID do grupo não fornecido', 400);
            }
            
            $stmt = $conn->prepare("
                SELECT 
                    a.id,
                    a.animal_number,
                    a.name,
                    a.breed,
                    a.gender,
                    a.birth_date,
                    a.status,
                    a.health_status,
                    a.reproductive_status,
                    DATEDIFF(CURDATE(), a.birth_date) as age_days
                FROM animals a
                WHERE a.current_group_id = ? AND a.farm_id = ? AND a.is_active = 1
                ORDER BY a.animal_number
            ");
            $stmt->execute([$group_id, $farm_id]);
            $animals = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            sendResponse($animals);
            break;
            
        // ==========================================
        // MOVER ANIMAIS ENTRE GRUPOS
        // ==========================================
        case 'move_animals':
            $data = $input;
            
            if (empty($data['animal_ids']) || !is_array($data['animal_ids'])) {
                sendResponse(null, 'Lista de animais não fornecida', 400);
            }
            
            $target_group_id = $data['target_group_id'] ?? null;
            if ($target_group_id === null) {
                sendResponse(null, 'ID do grupo de destino não fornecido', 400);
            }
            
            // Verificar se grupo existe
            $checkStmt = $conn->prepare("SELECT id FROM animal_groups WHERE id = ? AND farm_id = ? AND is_active = 1");
            $checkStmt->execute([$target_group_id, $farm_id]);
            if (!$checkStmt->fetch()) {
                sendResponse(null, 'Grupo de destino não encontrado', 404);
            }
            
            // Verificar capacidade se houver
            if ($target_group_id) {
                $capStmt = $conn->prepare("SELECT capacity, current_count FROM animal_groups WHERE id = ?");
                $capStmt->execute([$target_group_id]);
                $groupInfo = $capStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($groupInfo['capacity'] && ($groupInfo['current_count'] + count($data['animal_ids'])) > $groupInfo['capacity']) {
                    sendResponse(null, 'Capacidade do grupo excedida', 400);
                }
            }
            
            $moved = [];
            $errors = [];
            
            foreach ($data['animal_ids'] as $animal_id) {
                try {
                    $stmt = $conn->prepare("
                        UPDATE animals 
                        SET current_group_id = ? 
                        WHERE id = ? AND farm_id = ? AND is_active = 1
                    ");
                    $stmt->execute([$target_group_id, $animal_id, $farm_id]);
                    
                    if ($stmt->rowCount() > 0) {
                        $moved[] = $animal_id;
                    } else {
                        $errors[] = "Animal {$animal_id} não encontrado ou não pode ser movido";
                    }
                } catch (Exception $e) {
                    $errors[] = "Erro ao mover animal {$animal_id}: " . $e->getMessage();
                }
            }
            
            // Atualizar contadores dos grupos
            $updateCountStmt = $conn->prepare("
                UPDATE animal_groups 
                SET current_count = (
                    SELECT COUNT(*) 
                    FROM animals 
                    WHERE current_group_id = animal_groups.id AND is_active = 1 AND farm_id = ?
                )
                WHERE farm_id = ?
            ");
            $updateCountStmt->execute([$farm_id, $farm_id]);
            
            sendResponse([
                'moved' => $moved,
                'errors' => $errors,
                'message' => count($moved) . ' animal(is) movido(s) com sucesso'
            ]);
            break;
            
        // ==========================================
        // REMOVER ANIMAIS DO GRUPO
        // ==========================================
        case 'remove_animals':
            $data = $input;
            
            if (empty($data['animal_ids']) || !is_array($data['animal_ids'])) {
                sendResponse(null, 'Lista de animais não fornecida', 400);
            }
            
            $removed = [];
            foreach ($data['animal_ids'] as $animal_id) {
                try {
                    $stmt = $conn->prepare("
                        UPDATE animals 
                        SET current_group_id = NULL 
                        WHERE id = ? AND farm_id = ? AND is_active = 1
                    ");
                    $stmt->execute([$animal_id, $farm_id]);
                    
                    if ($stmt->rowCount() > 0) {
                        $removed[] = $animal_id;
                    }
                } catch (Exception $e) {
                    // Ignorar erros individuais
                }
            }
            
            // Atualizar contadores
            $updateCountStmt = $conn->prepare("
                UPDATE animal_groups 
                SET current_count = (
                    SELECT COUNT(*) 
                    FROM animals 
                    WHERE current_group_id = animal_groups.id AND is_active = 1 AND farm_id = ?
                )
                WHERE farm_id = ?
            ");
            $updateCountStmt->execute([$farm_id, $farm_id]);
            
            sendResponse([
                'removed' => $removed,
                'message' => count($removed) . ' animal(is) removido(s) do grupo'
            ]);
            break;
            
        // ==========================================
        // LISTAR ANIMAIS SEM GRUPO
        // ==========================================
        case 'animals_without_group':
            $stmt = $conn->prepare("
                SELECT 
                    a.id,
                    a.animal_number,
                    a.name,
                    a.breed,
                    a.gender,
                    a.birth_date,
                    a.status,
                    a.health_status,
                    a.reproductive_status,
                    DATEDIFF(CURDATE(), a.birth_date) as age_days
                FROM animals a
                WHERE (a.current_group_id IS NULL OR a.current_group_id = 0) 
                AND a.farm_id = ? AND a.is_active = 1
                ORDER BY a.animal_number
            ");
            $stmt->execute([$farm_id]);
            $animals = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            sendResponse($animals);
            break;
            
        // ==========================================
        // OBTER ANIMAIS DO GRUPO COM PESOS
        // ==========================================
        case 'animals_with_weights':
            $group_id = $_GET['group_id'] ?? null;
            if (!$group_id) {
                sendResponse(null, 'ID do grupo não fornecido', 400);
            }
            
            $stmt = $conn->prepare("
                SELECT 
                    a.id,
                    a.animal_number,
                    a.name,
                    a.breed,
                    a.gender,
                    a.birth_date,
                    a.status,
                    a.health_status,
                    a.reproductive_status,
                    DATEDIFF(CURDATE(), a.birth_date) as age_days,
                    (
                        SELECT weight_kg 
                        FROM animal_weights 
                        WHERE animal_id = a.id AND farm_id = ?
                        ORDER BY weighing_date DESC, id DESC 
                        LIMIT 1
                    ) as weight_kg,
                    (
                        SELECT weighing_date 
                        FROM animal_weights 
                        WHERE animal_id = a.id AND farm_id = ?
                        ORDER BY weighing_date DESC, id DESC 
                        LIMIT 1
                    ) as last_weighing_date
                FROM animals a
                WHERE a.current_group_id = ? AND a.farm_id = ? AND a.is_active = 1
                ORDER BY a.animal_number
            ");
            $stmt->execute([$farm_id, $farm_id, $group_id, $farm_id]);
            $animals = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            sendResponse($animals);
            break;
            
        // ==========================================
        // SALVAR PESO DO ANIMAL
        // ==========================================
        case 'save_animal_weight':
            $data = $input;
            
            if (empty($data['animal_id'])) {
                sendResponse(null, 'animal_id é obrigatório', 400);
            }
            
            if (!isset($data['weight_kg']) || $data['weight_kg'] === '' || $data['weight_kg'] === null) {
                sendResponse(null, 'weight_kg é obrigatório', 400);
            }
            
            $weight_kg = floatval($data['weight_kg']);
            if ($weight_kg <= 0) {
                sendResponse(null, 'weight_kg deve ser maior que zero', 400);
            }
            
            $weighing_date = $data['weighing_date'] ?? date('Y-m-d');
            $weighing_type = $data['weighing_type'] ?? 'normal';
            $notes = $data['notes'] ?? null;
            
            // Verificar se animal existe e pertence à fazenda
            $checkStmt = $conn->prepare("SELECT id FROM animals WHERE id = ? AND farm_id = ? AND is_active = 1");
            $checkStmt->execute([$data['animal_id'], $farm_id]);
            if (!$checkStmt->fetch()) {
                sendResponse(null, 'Animal não encontrado', 404);
            }
            
            // Inserir peso na tabela animal_weights
            $stmt = $conn->prepare("
                INSERT INTO animal_weights 
                (animal_id, weight_kg, weighing_date, weighing_type, notes, recorded_by, farm_id)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['animal_id'],
                $weight_kg,
                $weighing_date,
                $weighing_type,
                $notes,
                $user_id,
                $farm_id
            ]);
            
            $weight_id = $conn->lastInsertId();
            
            sendResponse([
                'id' => $weight_id,
                'animal_id' => $data['animal_id'],
                'weight_kg' => $weight_kg,
                'weighing_date' => $weighing_date,
                'message' => 'Peso registrado com sucesso'
            ]);
            break;
            
        default:
            sendResponse(null, 'Ação não encontrada', 404);
    }
    
} catch (Exception $e) {
    sendResponse(null, 'Erro interno: ' . $e->getMessage(), 500);
}
?>
