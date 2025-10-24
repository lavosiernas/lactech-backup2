<?php
/**
 * API: User Preferences
 * Preferências e configurações de usuário
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
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
    $user_id = $_SESSION['user_id'] ?? null;
    
    if (!$user_id && $method !== 'GET') {
        sendResponse(null, 'Usuário não autenticado', 401);
    }
    
    // GET - Listar preferências
    if ($method === 'GET') {
        $action = $_GET['action'] ?? 'get_all';
        $target_user_id = $_GET['user_id'] ?? $user_id;
        
        switch ($action) {
            case 'get_all':
                if (!$target_user_id) sendResponse(null, 'ID do usuário não fornecido');
                
                $stmt = $db->query("
                    SELECT *
                    FROM user_preferences
                    WHERE user_id = ?
                    ORDER BY category, preference_key
                ", [$target_user_id]);
                
                $prefs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Converter para objeto chave-valor
                $preferences = [];
                foreach ($prefs as $pref) {
                    $value = $pref['preference_value'];
                    
                    // Converter tipos
                    if ($pref['data_type'] === 'boolean') {
                        $value = $value === 'true' || $value === '1';
                    } elseif ($pref['data_type'] === 'number') {
                        $value = is_numeric($value) ? floatval($value) : $value;
                    } elseif ($pref['data_type'] === 'json') {
                        $value = json_decode($value, true);
                    }
                    
                    $preferences[$pref['preference_key']] = $value;
                }
                
                sendResponse($preferences);
                break;
                
            case 'get':
                $key = $_GET['key'] ?? null;
                if (!$key) sendResponse(null, 'Chave não fornecida');
                if (!$target_user_id) sendResponse(null, 'ID do usuário não fornecido');
                
                $stmt = $db->query("
                    SELECT preference_value, data_type
                    FROM user_preferences
                    WHERE user_id = ? AND preference_key = ?
                ", [$target_user_id, $key]);
                
                $pref = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$pref) sendResponse(null, 'Preferência não encontrada');
                
                $value = $pref['preference_value'];
                if ($pref['data_type'] === 'boolean') {
                    $value = $value === 'true' || $value === '1';
                } elseif ($pref['data_type'] === 'number') {
                    $value = floatval($value);
                } elseif ($pref['data_type'] === 'json') {
                    $value = json_decode($value, true);
                }
                
                sendResponse(['value' => $value]);
                break;
                
            case 'by_category':
                $category = $_GET['category'] ?? null;
                if (!$category) sendResponse(null, 'Categoria não fornecida');
                if (!$target_user_id) sendResponse(null, 'ID do usuário não fornecido');
                
                $stmt = $db->query("
                    SELECT *
                    FROM user_preferences
                    WHERE user_id = ? AND category = ?
                    ORDER BY preference_key
                ", [$target_user_id, $category]);
                
                sendResponse($stmt->fetchAll(PDO::FETCH_ASSOC));
                break;
                
            default:
                sendResponse(null, 'Ação não especificada');
        }
    }
    
    // POST - Criar/Atualizar preferência
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) $input = $_POST;
        
        $action = $input['action'] ?? 'set';
        
        if ($action === 'set') {
            if (empty($input['preference_key'])) sendResponse(null, 'Chave obrigatória');
            if (!isset($input['preference_value'])) sendResponse(null, 'Valor obrigatório');
            
            $key = $input['preference_key'];
            $value = $input['preference_value'];
            $data_type = $input['data_type'] ?? 'string';
            $category = $input['category'] ?? 'other';
            
            // Converter valor para string
            if ($data_type === 'boolean') {
                $value = $value ? 'true' : 'false';
            } elseif ($data_type === 'json') {
                $value = json_encode($value);
            } else {
                $value = strval($value);
            }
            
            // Inserir ou atualizar
            $stmt = $db->query("
                INSERT INTO user_preferences (user_id, preference_key, preference_value, data_type, category)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    preference_value = VALUES(preference_value),
                    data_type = VALUES(data_type),
                    category = VALUES(category)
            ", [$user_id, $key, $value, $data_type, $category]);
            
            sendResponse(['message' => 'Preferência salva']);
        }
        
        if ($action === 'set_multiple') {
            $preferences = $input['preferences'] ?? [];
            if (empty($preferences)) sendResponse(null, 'Preferências não fornecidas');
            
            $saved = 0;
            foreach ($preferences as $key => $value) {
                try {
                    $data_type = is_bool($value) ? 'boolean' : (is_numeric($value) ? 'number' : 'string');
                    $str_value = is_bool($value) ? ($value ? 'true' : 'false') : strval($value);
                    
                    $db->query("
                        INSERT INTO user_preferences (user_id, preference_key, preference_value, data_type)
                        VALUES (?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE preference_value = VALUES(preference_value)
                    ", [$user_id, $key, $str_value, $data_type]);
                    
                    $saved++;
                } catch (Exception $e) {
                    error_log("Erro salvando preferência $key: " . $e->getMessage());
                }
            }
            
            sendResponse(['message' => "$saved preferências salvas", 'saved' => $saved]);
        }
        
        if ($action === 'reset') {
            $db->query("DELETE FROM user_preferences WHERE user_id = ?", [$user_id]);
            
            // Inserir padrões
            $db->query("
                INSERT INTO user_preferences (user_id, preference_key, preference_value, data_type, category)
                VALUES (?, 'notifications_enabled', 'true', 'boolean', 'notifications')
            ", [$user_id]);
            
            sendResponse(['message' => 'Preferências resetadas para padrão']);
        }
    }
    
} catch (Exception $e) {
    error_log("Erro API Preferences: " . $e->getMessage());
    sendResponse(null, 'Erro: ' . $e->getMessage(), 500);
}

