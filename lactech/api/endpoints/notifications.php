<?php
/**
 * Endpoint para gerenciar notificações
 * GET /api/rest.php/notifications - Listar notificações
 * POST /api/rest.php/notifications - Criar notificação
 * PUT /api/rest.php/notifications - Marcar como lida
 * DELETE /api/rest.php/notifications - Deletar notificação
 */

$method = Request::getMethod();

switch ($method) {
    case 'GET':
        // Listar notificações
        $userId = Auth::checkAuth();
        
        $limit = Request::getParam('limit', 50);
        $unreadOnly = Request::getParam('unread_only', false);
        $type = Request::getParam('type', null);
        
        try {
            $query = "SELECT 
                n.id,
                n.title,
                n.message,
                n.type,
                n.priority,
                n.is_read,
                n.created_at,
                n.read_at,
                u.name as created_by_name
                FROM notifications n
                LEFT JOIN users u ON n.created_by = u.id
                WHERE n.user_id = ? OR n.user_id IS NULL";
            
            $params = [$userId];
            
            if ($unreadOnly) {
                $query .= " AND n.is_read = 0";
            }
            
            if ($type) {
                $query .= " AND n.type = ?";
                $params[] = $type;
            }
            
            $query .= " ORDER BY n.created_at DESC LIMIT ?";
            $params[] = (int)$limit;
            
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Contar notificações não lidas
            $stmt = $db->prepare("SELECT COUNT(*) as unread_count FROM notifications WHERE (user_id = ? OR user_id IS NULL) AND is_read = 0");
            $stmt->execute([$userId]);
            $unreadCount = $stmt->fetch(PDO::FETCH_ASSOC)['unread_count'];
            
            ApiResponse::success([
                'notifications' => $notifications,
                'unread_count' => (int)$unreadCount
            ], 'Notificações carregadas com sucesso');
            
        } catch (Exception $e) {
            ApiResponse::serverError('Erro ao buscar notificações: ' . $e->getMessage());
        }
        break;
        
    case 'POST':
        // Criar nova notificação
        $userId = Auth::checkAuth();
        
        Validator::required($data, ['title', 'message']);
        
        $type = $data['type'] ?? 'info';
        $priority = $data['priority'] ?? 'normal';
        $targetUserId = $data['target_user_id'] ?? null;
        
        try {
            $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type, priority, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$targetUserId, $data['title'], $data['message'], $type, $priority, $userId]);
            
            $notificationId = $db->lastInsertId();
            
            ApiResponse::success([
                'id' => $notificationId,
                'title' => $data['title'],
                'message' => $data['message'],
                'type' => $type,
                'priority' => $priority
            ], 'Notificação criada com sucesso', 201);
            
        } catch (Exception $e) {
            ApiResponse::serverError('Erro ao criar notificação: ' . $e->getMessage());
        }
        break;
        
    case 'PUT':
        // Marcar notificação como lida
        $userId = Auth::checkAuth();
        
        $notificationId = Request::getParam('id');
        if (!$notificationId) {
            ApiResponse::error('ID da notificação é obrigatório');
        }
        
        try {
            $stmt = $db->prepare("UPDATE notifications SET is_read = 1, read_at = NOW() WHERE id = ? AND (user_id = ? OR user_id IS NULL)");
            $stmt->execute([$notificationId, $userId]);
            
            if ($stmt->rowCount() === 0) {
                ApiResponse::notFound('Notificação não encontrada');
            }
            
            ApiResponse::success(null, 'Notificação marcada como lida');
            
        } catch (Exception $e) {
            ApiResponse::serverError('Erro ao atualizar notificação: ' . $e->getMessage());
        }
        break;
        
    case 'DELETE':
        // Deletar notificação
        $userId = Auth::checkAuth();
        
        $notificationId = Request::getParam('id');
        if (!$notificationId) {
            ApiResponse::error('ID da notificação é obrigatório');
        }
        
        try {
            $stmt = $db->prepare("DELETE FROM notifications WHERE id = ? AND (user_id = ? OR user_id IS NULL)");
            $stmt->execute([$notificationId, $userId]);
            
            if ($stmt->rowCount() === 0) {
                ApiResponse::notFound('Notificação não encontrada');
            }
            
            ApiResponse::success(null, 'Notificação removida com sucesso');
            
        } catch (Exception $e) {
            ApiResponse::serverError('Erro ao remover notificação: ' . $e->getMessage());
        }
        break;
        
    default:
        ApiResponse::error('Método não permitido', 405);
}
?>

