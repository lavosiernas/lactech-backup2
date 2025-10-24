<?php
/**
 * API: Animal Photos
 * Upload e gestão de fotos dos animais
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
                $limit = $_GET['limit'] ?? 100;
                
                $stmt = $db->query("
                    SELECT 
                        p.*,
                        a.animal_number,
                        a.name as animal_name,
                        a.breed,
                        u.name as uploaded_by_name
                    FROM animal_photos p
                    LEFT JOIN animals a ON p.animal_id = a.id
                    LEFT JOIN users u ON p.uploaded_by = u.id
                    ORDER BY p.uploaded_at DESC
                    LIMIT ?
                ", [$limit]);
                sendResponse($stmt->fetchAll(PDO::FETCH_ASSOC));
                break;
                
            case 'by_animal':
                $animal_id = $_GET['animal_id'] ?? null;
                if (!$animal_id) sendResponse(null, 'ID do animal não fornecido');
                
                $stmt = $db->query("
                    SELECT p.*, u.name as uploaded_by_name
                    FROM animal_photos p
                    LEFT JOIN users u ON p.uploaded_by = u.id
                    WHERE p.animal_id = ?
                    ORDER BY p.is_primary DESC, p.uploaded_at DESC
                ", [$animal_id]);
                sendResponse($stmt->fetchAll(PDO::FETCH_ASSOC));
                break;
                
            case 'primary':
                $animal_id = $_GET['animal_id'] ?? null;
                if (!$animal_id) sendResponse(null, 'ID do animal não fornecido');
                
                $stmt = $db->query("
                    SELECT * FROM animal_photos
                    WHERE animal_id = ? AND is_primary = 1
                    LIMIT 1
                ", [$animal_id]);
                
                $photo = $stmt->fetch(PDO::FETCH_ASSOC);
                sendResponse($photo ?: null);
                break;
                
            case 'by_type':
                $photo_type = $_GET['photo_type'] ?? null;
                if (!$photo_type) sendResponse(null, 'Tipo de foto não fornecido');
                
                $stmt = $db->query("
                    SELECT 
                        p.*,
                        a.animal_number,
                        a.name as animal_name
                    FROM animal_photos p
                    LEFT JOIN animals a ON p.animal_id = a.id
                    WHERE p.photo_type = ?
                    ORDER BY p.uploaded_at DESC
                    LIMIT 50
                ", [$photo_type]);
                sendResponse($stmt->fetchAll(PDO::FETCH_ASSOC));
                break;
                
            default:
                sendResponse(null, 'Ação não especificada');
        }
    }
    
    // POST - Upload foto
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) $input = $_POST;
        
        $action = $input['action'] ?? 'upload';
        
        if ($action === 'upload') {
            if (empty($input['animal_id'])) sendResponse(null, 'ID do animal obrigatório');
            if (empty($input['photo_url'])) sendResponse(null, 'URL da foto obrigatória');
            
            // Inserir foto
            $stmt = $db->query("
                INSERT INTO animal_photos (
                    animal_id, photo_url, photo_type, is_primary,
                    taken_date, description, tags, file_size,
                    dimensions, uploaded_by, farm_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
            ", [
                $input['animal_id'],
                $input['photo_url'],
                $input['photo_type'] ?? 'other',
                $input['is_primary'] ?? 0,
                $input['taken_date'] ?? date('Y-m-d'),
                $input['description'] ?? null,
                isset($input['tags']) ? json_encode($input['tags']) : null,
                $input['file_size'] ?? null,
                $input['dimensions'] ?? null,
                $_SESSION['user_id'] ?? 1
            ]);
            
            $id = $db->getConnection()->lastInsertId();
            sendResponse([
                'id' => $id,
                'message' => 'Foto enviada com sucesso'
            ]);
        }
        
        if ($action === 'set_primary') {
            $id = $input['id'] ?? null;
            if (!$id) sendResponse(null, 'ID não fornecido');
            
            // Trigger já garante que só uma será primary
            $db->query("
                UPDATE animal_photos 
                SET is_primary = 1 
                WHERE id = ?
            ", [$id]);
            
            sendResponse(['message' => 'Foto definida como principal']);
        }
    }
    
    // PUT - Atualizar
    if ($method === 'PUT') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $id = $input['id'] ?? null;
        if (!$id) sendResponse(null, 'ID não fornecido');
        
        $updates = [];
        $values = [];
        
        $allowed = ['photo_type', 'description', 'tags', 'is_primary'];
        foreach ($allowed as $field) {
            if (isset($input[$field])) {
                if ($field === 'tags') {
                    $updates[] = "$field = ?";
                    $values[] = json_encode($input[$field]);
                } else {
                    $updates[] = "$field = ?";
                    $values[] = $input[$field];
                }
            }
        }
        
        if (empty($updates)) sendResponse(null, 'Nenhum campo para atualizar');
        
        $values[] = $id;
        $db->query("
            UPDATE animal_photos 
            SET " . implode(', ', $updates) . "
            WHERE id = ?
        ", $values);
        
        sendResponse(['message' => 'Foto atualizada']);
    }
    
    // DELETE - Remover
    if ($method === 'DELETE') {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? $_GET['id'] ?? null;
        
        if (!$id) sendResponse(null, 'ID não fornecido');
        
        // Buscar URL antes de deletar (para deletar arquivo físico se necessário)
        $photo = $db->query("SELECT photo_url FROM animal_photos WHERE id = ?", [$id])->fetch();
        
        $db->query("DELETE FROM animal_photos WHERE id = ?", [$id]);
        sendResponse([
            'message' => 'Foto removida',
            'photo_url' => $photo['photo_url'] ?? null
        ]);
    }
    
} catch (Exception $e) {
    error_log("Erro API Photos: " . $e->getMessage());
    sendResponse(null, 'Erro: ' . $e->getMessage(), 500);
}

