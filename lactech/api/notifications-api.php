<?php
/**
 * API de Notificações - Simples e Funcional
 * Retorna notificações do banco de dados
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Iniciar sessão se não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/Database.class.php';

try {
    $db = Database::getInstance();
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? $_POST['action'] ?? 'get';
    $userId = $_SESSION['user_id'] ?? null;
    
    if (!$userId) {
        echo json_encode([
            'success' => false,
            'error' => 'Usuário não autenticado'
        ]);
        exit;
    }
    
    switch ($action) {
        case 'get':
        default:
            // Buscar notificações do usuário
            $unreadOnly = isset($_GET['unread_only']) && $_GET['unread_only'] === 'true';
            $limit = (int)($_GET['limit'] ?? 50);
            
            $sql = "
                SELECT 
                    id, title, message, link, type, notification_type, 
                    priority, is_read, created_at, related_table, related_id
                FROM notifications 
                WHERE (user_id = :user_id OR user_id IS NULL)
                AND farm_id = 1
            ";
            
            $params = [':user_id' => $userId];
            
            if ($unreadOnly) {
                $sql .= " AND is_read = 0";
            }
            
            $sql .= " ORDER BY created_at DESC LIMIT :limit";
            
            $stmt = $db->getConnection()->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Contar não lidas
            $countSql = "
                SELECT COUNT(*) as unread_count 
                FROM notifications 
                WHERE (user_id = :user_id OR user_id IS NULL)
                AND farm_id = 1
                AND is_read = 0
            ";
            $countStmt = $db->getConnection()->prepare($countSql);
            $countStmt->bindValue(':user_id', $userId);
            $countStmt->execute();
            $unreadCount = $countStmt->fetch(PDO::FETCH_ASSOC)['unread_count'] ?? 0;
            
            echo json_encode([
                'success' => true,
                'notifications' => $notifications,
                'unread_count' => (int)$unreadCount
            ]);
            break;
            
        case 'mark_read':
            $notificationId = $_POST['id'] ?? $_GET['id'] ?? null;
            
            if (!$notificationId) {
                echo json_encode([
                    'success' => false,
                    'error' => 'ID da notificação não fornecido'
                ]);
                exit;
            }
            
            $stmt = $db->getConnection()->prepare("
                UPDATE notifications 
                SET is_read = 1, read_date = NOW() 
                WHERE id = :id AND (user_id = :user_id OR user_id IS NULL)
            ");
            $stmt->execute([
                ':id' => $notificationId,
                ':user_id' => $userId
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Notificação marcada como lida'
            ]);
            break;
            
        case 'mark_all_read':
            $stmt = $db->getConnection()->prepare("
                UPDATE notifications 
                SET is_read = 1, read_date = NOW() 
                WHERE (user_id = :user_id OR user_id IS NULL)
                AND farm_id = 1
                AND is_read = 0
            ");
            $stmt->execute([':user_id' => $userId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Todas as notificações foram marcadas como lidas'
            ]);
            break;
            
        case 'delete':
            $notificationId = $_POST['id'] ?? $_GET['id'] ?? null;
            
            if (!$notificationId) {
                echo json_encode([
                    'success' => false,
                    'error' => 'ID da notificação não fornecido'
                ]);
                exit;
            }
            
            $stmt = $db->getConnection()->prepare("
                DELETE FROM notifications 
                WHERE id = :id AND (user_id = :user_id OR user_id IS NULL)
            ");
            $stmt->execute([
                ':id' => $notificationId,
                ':user_id' => $userId
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Notificação removida'
            ]);
            break;
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Erro no servidor: ' . $e->getMessage()
    ]);
}
?>










