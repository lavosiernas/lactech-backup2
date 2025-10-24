<?php
/**
 * API: Notifications System
 * Sistema completo de notificações push
 */

ob_start();
ob_clean();

error_reporting(E_ALL);
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
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
    $user_id = $_SESSION['user_id'] ?? null;
    
    // GET - Listar notificações
    if ($method === 'GET') {
        $action = $_GET['action'] ?? 'list';
        
        switch ($action) {
            case 'list':
                if (!$user_id) sendResponse(null, 'Usuário não autenticado', 401);
                
                $limit = $_GET['limit'] ?? 50;
                $unread_only = isset($_GET['unread_only']) ? (bool)$_GET['unread_only'] : false;
                
                $where = "WHERE n.user_id = ?";
                $params = [$user_id];
                
                if ($unread_only) {
                    $where .= " AND n.is_read = 0";
                }
                
                $stmt = $db->query("
                    SELECT *
                    FROM notifications n
                    $where
                    ORDER BY n.priority DESC, n.created_at DESC
                    LIMIT ?
                ", array_merge($params, [$limit]));
                
                sendResponse($stmt->fetchAll(PDO::FETCH_ASSOC));
                break;
                
            case 'unread_count':
                if (!$user_id) sendResponse(null, 'Usuário não autenticado', 401);
                
                $stmt = $db->query("
                    SELECT COUNT(*) as count
                    FROM notifications
                    WHERE user_id = ? AND is_read = 0
                ", [$user_id]);
                
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                sendResponse(['count' => $result['count']]);
                break;
                
            case 'urgent':
                if (!$user_id) sendResponse(null, 'Usuário não autenticado', 401);
                
                $stmt = $db->query("
                    SELECT *
                    FROM notifications
                    WHERE user_id = ? 
                      AND is_read = 0 
                      AND priority IN ('urgent', 'critical')
                    ORDER BY priority DESC, created_at DESC
                    LIMIT 10
                ", [$user_id]);
                
                sendResponse($stmt->fetchAll(PDO::FETCH_ASSOC));
                break;
                
            default:
                sendResponse(null, 'Ação não especificada');
        }
    }
    
    // POST - Criar notificação
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) $input = $_POST;
        
        $action = $input['action'] ?? 'create';
        
        if ($action === 'create') {
            // Criar notificação para usuário(s)
            $user_ids = $input['user_ids'] ?? [$user_id];
            $title = $input['title'] ?? '';
            $message = $input['message'] ?? '';
            $notification_type = $input['notification_type'] ?? 'info';
            $priority = $input['priority'] ?? 'medium';
            
            if (empty($title) || empty($message)) {
                sendResponse(null, 'Título e mensagem obrigatórios');
            }
            
            $created_count = 0;
            foreach ($user_ids as $uid) {
                try {
                    $db->query("
                        INSERT INTO notifications (
                            user_id, title, message, type, notification_type,
                            priority, link, farm_id
                        ) VALUES (?, ?, ?, 'info', ?, ?, ?, 1)
                    ", [
                        $uid,
                        $title,
                        $message,
                        $notification_type,
                        $priority,
                        $input['link'] ?? null
                    ]);
                    $created_count++;
                } catch (Exception $e) {
                    error_log("Erro ao criar notificação para user $uid: " . $e->getMessage());
                }
            }
            
            sendResponse([
                'message' => "$created_count notificações criadas",
                'count' => $created_count
            ]);
        }
        
        if ($action === 'mark_read') {
            $id = $input['id'] ?? null;
            if (!$id) sendResponse(null, 'ID não fornecido');
            
            $db->query("
                UPDATE notifications 
                SET is_read = 1, read_date = NOW()
                WHERE id = ? AND user_id = ?
            ", [$id, $user_id]);
            
            sendResponse(['message' => 'Notificação marcada como lida']);
        }
        
        if ($action === 'mark_all_read') {
            $db->query("
                UPDATE notifications 
                SET is_read = 1, read_date = NOW()
                WHERE user_id = ? AND is_read = 0
            ", [$user_id]);
            
            sendResponse(['message' => 'Todas notificações marcadas como lidas']);
        }
        
        if ($action === 'send_push') {
            // Enviar push notification real
            // Aqui você implementaria integração com servidor de push
            // Por exemplo: Firebase Cloud Messaging, OneSignal, etc
            
            sendResponse(['message' => 'Push notification enviada (simulação)']);
        }
    }
    
    // DELETE - Remover notificação
    if ($method === 'DELETE') {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? $_GET['id'] ?? null;
        
        if (!$id) sendResponse(null, 'ID não fornecido');
        if (!$user_id) sendResponse(null, 'Usuário não autenticado', 401);
        
        $db->query("
            DELETE FROM notifications 
            WHERE id = ? AND user_id = ?
        ", [$id, $user_id]);
        
        sendResponse(['message' => 'Notificação removida']);
    }
    
} catch (Exception $e) {
    error_log("Erro API Notifications: " . $e->getMessage());
    sendResponse(null, 'Erro: ' . $e->getMessage(), 500);
}

