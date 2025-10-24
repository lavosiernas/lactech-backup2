<?php
/**
 * API: Transponders/RFID
 * Gestão de chips RFID e identificação eletrônica
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
                // Listar todos transponders
                $active_only = isset($_GET['active_only']) ? (bool)$_GET['active_only'] : true;
                $where = $active_only ? "WHERE t.is_active = 1" : "";
                
                $stmt = $db->query("
                    SELECT 
                        t.*,
                        a.animal_number,
                        a.name as animal_name,
                        a.breed,
                        u.name as recorded_by_name,
                        DATEDIFF(CURDATE(), t.activation_date) as days_active
                    FROM animal_transponders t
                    LEFT JOIN animals a ON t.animal_id = a.id
                    LEFT JOIN users u ON t.recorded_by = u.id
                    $where
                    ORDER BY t.created_at DESC
                ");
                sendResponse($stmt->fetchAll(PDO::FETCH_ASSOC));
                break;
                
            case 'by_animal':
                $animal_id = $_GET['animal_id'] ?? null;
                if (!$animal_id) sendResponse(null, 'ID do animal não fornecido');
                
                $stmt = $db->query("
                    SELECT t.*, u.name as recorded_by_name
                    FROM animal_transponders t
                    LEFT JOIN users u ON t.recorded_by = u.id
                    WHERE t.animal_id = ?
                    ORDER BY t.is_active DESC, t.activation_date DESC
                ", [$animal_id]);
                sendResponse($stmt->fetchAll(PDO::FETCH_ASSOC));
                break;
                
            case 'search':
                $code = $_GET['code'] ?? null;
                if (!$code) sendResponse(null, 'Código do transponder não fornecido');
                
                $stmt = $db->query("
                    SELECT 
                        t.*,
                        a.animal_number,
                        a.name as animal_name,
                        a.breed,
                        a.status,
                        a.current_group_id,
                        g.group_name
                    FROM animal_transponders t
                    LEFT JOIN animals a ON t.animal_id = a.id
                    LEFT JOIN animal_groups g ON a.current_group_id = g.id
                    WHERE t.transponder_code = ? AND t.is_active = 1
                ", [$code]);
                
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$result) sendResponse(null, 'Transponder não encontrado');
                
                // Registrar leitura
                $db->query("
                    INSERT INTO transponder_readings 
                    (transponder_id, reading_date, location, farm_id)
                    VALUES (?, NOW(), 'API', 1)
                ", [$result['id']]);
                
                sendResponse($result);
                break;
                
            case 'readings':
                $transponder_id = $_GET['transponder_id'] ?? null;
                if (!$transponder_id) sendResponse(null, 'ID do transponder não fornecido');
                
                $stmt = $db->query("
                    SELECT *
                    FROM transponder_readings
                    WHERE transponder_id = ?
                    ORDER BY reading_date DESC
                    LIMIT 100
                ", [$transponder_id]);
                sendResponse($stmt->fetchAll(PDO::FETCH_ASSOC));
                break;
                
            default:
                sendResponse(null, 'Ação não especificada');
        }
    }
    
    // POST - Criar
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) $input = $_POST;
        
        $action = $input['action'] ?? 'create';
        
        if ($action === 'create') {
            // Validações
            if (empty($input['animal_id'])) sendResponse(null, 'ID do animal obrigatório');
            if (empty($input['transponder_code'])) sendResponse(null, 'Código do transponder obrigatório');
            if (empty($input['activation_date'])) sendResponse(null, 'Data de ativação obrigatória');
            
            // Verificar se código já existe
            $existing = $db->query("
                SELECT id FROM animal_transponders 
                WHERE transponder_code = ?
            ", [$input['transponder_code']])->fetch();
            
            if ($existing) sendResponse(null, 'Código de transponder já cadastrado');
            
            // Inserir
            $stmt = $db->query("
                INSERT INTO animal_transponders (
                    animal_id, transponder_code, transponder_type, manufacturer,
                    activation_date, location, notes, recorded_by, farm_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
            ", [
                $input['animal_id'],
                $input['transponder_code'],
                $input['transponder_type'] ?? 'rfid',
                $input['manufacturer'] ?? null,
                $input['activation_date'],
                $input['location'] ?? 'ear_left',
                $input['notes'] ?? null,
                $_SESSION['user_id'] ?? 1
            ]);
            
            $id = $db->getConnection()->lastInsertId();
            sendResponse(['id' => $id, 'message' => 'Transponder cadastrado com sucesso']);
        }
        
        if ($action === 'deactivate') {
            $id = $input['id'] ?? null;
            if (!$id) sendResponse(null, 'ID não fornecido');
            
            $db->query("
                UPDATE animal_transponders 
                SET is_active = 0, deactivation_date = CURDATE()
                WHERE id = ?
            ", [$id]);
            
            sendResponse(['message' => 'Transponder desativado']);
        }
    }
    
    // PUT - Atualizar
    if ($method === 'PUT') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $id = $input['id'] ?? null;
        if (!$id) sendResponse(null, 'ID não fornecido');
        
        $updates = [];
        $values = [];
        
        $allowed = ['manufacturer', 'location', 'notes', 'is_active'];
        foreach ($allowed as $field) {
            if (isset($input[$field])) {
                $updates[] = "$field = ?";
                $values[] = $input[$field];
            }
        }
        
        if (empty($updates)) sendResponse(null, 'Nenhum campo para atualizar');
        
        $values[] = $id;
        $db->query("
            UPDATE animal_transponders 
            SET " . implode(', ', $updates) . "
            WHERE id = ?
        ", $values);
        
        sendResponse(['message' => 'Transponder atualizado']);
    }
    
    // DELETE - Remover
    if ($method === 'DELETE') {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? $_GET['id'] ?? null;
        
        if (!$id) sendResponse(null, 'ID não fornecido');
        
        $db->query("DELETE FROM animal_transponders WHERE id = ?", [$id]);
        sendResponse(['message' => 'Transponder removido']);
    }
    
} catch (Exception $e) {
    error_log("Erro API Transponders: " . $e->getMessage());
    sendResponse(null, 'Erro: ' . $e->getMessage(), 500);
}

