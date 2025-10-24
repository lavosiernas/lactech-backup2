<?php
/**
 * API: Animal Groups
 * Gestão de grupos/lotes de animais
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../includes/Database.class.php';

function sendResponse($data = null, $error = null, $status = 200) {
    http_response_code($status);
    echo json_encode([
        'success' => $error === null,
        'data' => $data,
        'error' => $error
    ]);
    exit;
}

try {
    $db = Database::getInstance();
    $method = $_SERVER['REQUEST_METHOD'];
    
    // GET - Listar/Buscar
    if ($method === 'GET') {
        $action = $_GET['action'] ?? 'list';
        
        switch ($action) {
            case 'list':
                $active_only = isset($_GET['active_only']) ? (bool)$_GET['active_only'] : true;
                $where = $active_only ? "WHERE g.is_active = 1" : "";
                
                $stmt = $db->query("
                    SELECT 
                        g.*,
                        u.name as created_by_name,
                        COUNT(DISTINCT a.id) as actual_animal_count,
                        GROUP_CONCAT(DISTINCT a.animal_number ORDER BY a.animal_number SEPARATOR ', ') as animal_numbers
                    FROM animal_groups g
                    LEFT JOIN users u ON g.created_by = u.id
                    LEFT JOIN animals a ON a.current_group_id = g.id AND a.is_active = 1
                    $where
                    GROUP BY g.id
                    ORDER BY g.group_type, g.group_name
                ");
                sendResponse($stmt->fetchAll(PDO::FETCH_ASSOC));
                break;
                
            case 'by_id':
                $id = $_GET['id'] ?? null;
                if (!$id) sendResponse(null, 'ID não fornecido');
                
                $stmt = $db->query("
                    SELECT 
                        g.*,
                        u.name as created_by_name
                    FROM animal_groups g
                    LEFT JOIN users u ON g.created_by = u.id
                    WHERE g.id = ?
                ", [$id]);
                
                $group = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$group) sendResponse(null, 'Grupo não encontrado');
                
                // Buscar animais do grupo
                $stmt = $db->query("
                    SELECT 
                        a.id,
                        a.animal_number,
                        a.name,
                        a.breed,
                        a.status,
                        a.gender,
                        DATEDIFF(CURDATE(), a.birth_date) as age_days
                    FROM animals a
                    WHERE a.current_group_id = ? AND a.is_active = 1
                    ORDER BY a.animal_number
                ", [$id]);
                
                $group['animals'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                sendResponse($group);
                break;
                
            case 'movements':
                $animal_id = $_GET['animal_id'] ?? null;
                $group_id = $_GET['group_id'] ?? null;
                
                $where = [];
                $params = [];
                
                if ($animal_id) {
                    $where[] = "gm.animal_id = ?";
                    $params[] = $animal_id;
                }
                if ($group_id) {
                    $where[] = "(gm.from_group_id = ? OR gm.to_group_id = ?)";
                    $params[] = $group_id;
                    $params[] = $group_id;
                }
                
                $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
                
                $stmt = $db->query("
                    SELECT 
                        gm.*,
                        a.animal_number,
                        a.name as animal_name,
                        gf.group_name as from_group_name,
                        gt.group_name as to_group_name,
                        u.name as moved_by_name
                    FROM group_movements gm
                    LEFT JOIN animals a ON gm.animal_id = a.id
                    LEFT JOIN animal_groups gf ON gm.from_group_id = gf.id
                    LEFT JOIN animal_groups gt ON gm.to_group_id = gt.id
                    LEFT JOIN users u ON gm.moved_by = u.id
                    $whereClause
                    ORDER BY gm.movement_date DESC, gm.created_at DESC
                    LIMIT 100
                ", $params);
                sendResponse($stmt->fetchAll(PDO::FETCH_ASSOC));
                break;
                
            default:
                sendResponse(null, 'Ação não especificada');
        }
    }
    
    // POST - Criar grupo ou mover animal
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) $input = $_POST;
        
        $action = $input['action'] ?? 'create_group';
        
        if ($action === 'create_group') {
            if (empty($input['group_name'])) sendResponse(null, 'Nome do grupo obrigatório');
            if (empty($input['group_type'])) sendResponse(null, 'Tipo do grupo obrigatório');
            
            $stmt = $db->query("
                INSERT INTO animal_groups (
                    group_name, group_code, group_type, description,
                    location, capacity, feed_protocol, milking_order,
                    color_code, farm_id, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)
            ", [
                $input['group_name'],
                $input['group_code'] ?? null,
                $input['group_type'],
                $input['description'] ?? null,
                $input['location'] ?? null,
                $input['capacity'] ?? null,
                $input['feed_protocol'] ?? null,
                $input['milking_order'] ?? null,
                $input['color_code'] ?? '#6B7280',
                $_SESSION['user_id'] ?? 1
            ]);
            
            $id = $db->getConnection()->lastInsertId();
            sendResponse(['id' => $id, 'message' => 'Grupo criado com sucesso']);
        }
        
        if ($action === 'move_animal') {
            if (empty($input['animal_id'])) sendResponse(null, 'ID do animal obrigatório');
            if (empty($input['to_group_id'])) sendResponse(null, 'Grupo de destino obrigatório');
            
            // Obter grupo atual
            $current = $db->query("
                SELECT current_group_id FROM animals WHERE id = ?
            ", [$input['animal_id']])->fetch();
            
            // Registrar movimentação
            $db->query("
                INSERT INTO group_movements (
                    animal_id, from_group_id, to_group_id, movement_date,
                    movement_time, reason, notes, moved_by, farm_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
            ", [
                $input['animal_id'],
                $current['current_group_id'] ?? null,
                $input['to_group_id'],
                $input['movement_date'] ?? date('Y-m-d'),
                $input['movement_time'] ?? date('H:i:s'),
                $input['reason'] ?? null,
                $input['notes'] ?? null,
                $_SESSION['user_id'] ?? 1
            ]);
            
            // Atualizar animal
            $db->query("
                UPDATE animals 
                SET current_group_id = ?
                WHERE id = ?
            ", [$input['to_group_id'], $input['animal_id']]);
            
            sendResponse(['message' => 'Animal movido com sucesso']);
        }
        
        if ($action === 'move_multiple') {
            $animal_ids = $input['animal_ids'] ?? [];
            $to_group_id = $input['to_group_id'] ?? null;
            
            if (empty($animal_ids)) sendResponse(null, 'IDs dos animais não fornecidos');
            if (!$to_group_id) sendResponse(null, 'Grupo de destino obrigatório');
            
            $moved = 0;
            foreach ($animal_ids as $animal_id) {
                try {
                    $current = $db->query("SELECT current_group_id FROM animals WHERE id = ?", [$animal_id])->fetch();
                    
                    $db->query("
                        INSERT INTO group_movements (animal_id, from_group_id, to_group_id, movement_date, moved_by, farm_id)
                        VALUES (?, ?, ?, ?, ?, 1)
                    ", [$animal_id, $current['current_group_id'], $to_group_id, date('Y-m-d'), $_SESSION['user_id'] ?? 1]);
                    
                    $db->query("UPDATE animals SET current_group_id = ? WHERE id = ?", [$to_group_id, $animal_id]);
                    $moved++;
                } catch (Exception $e) {
                    error_log("Erro movendo animal $animal_id: " . $e->getMessage());
                }
            }
            
            sendResponse(['message' => "$moved animais movidos com sucesso", 'moved' => $moved]);
        }
    }
    
    // PUT - Atualizar grupo
    if ($method === 'PUT') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $id = $input['id'] ?? null;
        if (!$id) sendResponse(null, 'ID não fornecido');
        
        $updates = [];
        $values = [];
        
        $allowed = ['group_name', 'group_code', 'description', 'location', 'capacity',
                    'feed_protocol', 'milking_order', 'color_code', 'is_active'];
        
        foreach ($allowed as $field) {
            if (isset($input[$field])) {
                $updates[] = "$field = ?";
                $values[] = $input[$field];
            }
        }
        
        if (empty($updates)) sendResponse(null, 'Nenhum campo para atualizar');
        
        $values[] = $id;
        $db->query("
            UPDATE animal_groups 
            SET " . implode(', ', $updates) . "
            WHERE id = ?
        ", $values);
        
        sendResponse(['message' => 'Grupo atualizado']);
    }
    
    // DELETE - Remover grupo
    if ($method === 'DELETE') {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? $_GET['id'] ?? null;
        
        if (!$id) sendResponse(null, 'ID não fornecido');
        
        // Verificar se tem animais
        $count = $db->query("
            SELECT COUNT(*) as count FROM animals WHERE current_group_id = ?
        ", [$id])->fetch();
        
        if ($count['count'] > 0) {
            sendResponse(null, 'Não é possível deletar grupo com animais. Mova os animais primeiro.');
        }
        
        $db->query("DELETE FROM animal_groups WHERE id = ?", [$id]);
        sendResponse(['message' => 'Grupo removido']);
    }
    
} catch (Exception $e) {
    error_log("Erro API Groups: " . $e->getMessage());
    sendResponse(null, 'Erro: ' . $e->getMessage(), 500);
}

