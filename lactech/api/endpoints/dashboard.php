<?php
/**
 * Endpoint para dashboard
 * GET /api/rest.php/dashboard - Obter estatísticas do dashboard
 */

$method = Request::getMethod();

switch ($method) {
    case 'GET':
        $userId = Auth::checkAuth();
        
        try {
            // Estatísticas básicas
            $stats = [];
            
            // Total de usuários
            $stmt = $db->prepare("SELECT COUNT(*) as total FROM users WHERE active = 1");
            $stmt->execute();
            $stats['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Volume de leite hoje
            $stmt = $db->prepare("SELECT COALESCE(SUM(volume), 0) as total FROM volume_records WHERE DATE(collection_date) = CURDATE()");
            $stmt->execute();
            $stats['volume_today'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Volume de leite este mês
            $stmt = $db->prepare("SELECT COALESCE(SUM(volume), 0) as total FROM volume_records WHERE MONTH(collection_date) = MONTH(CURDATE()) AND YEAR(collection_date) = YEAR(CURDATE())");
            $stmt->execute();
            $stats['volume_month'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Testes de qualidade pendentes
            $stmt = $db->prepare("SELECT COUNT(*) as total FROM quality_tests WHERE status = 'pending'");
            $stmt->execute();
            $stats['pending_quality_tests'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Notificações não lidas
            $stmt = $db->prepare("SELECT COUNT(*) as total FROM notifications WHERE (user_id = ? OR user_id IS NULL) AND is_read = 0");
            $stmt->execute([$userId]);
            $stats['unread_notifications'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Solicitações de senha pendentes
            $stmt = $db->prepare("SELECT COUNT(*) as total FROM password_requests WHERE status = 'pending'");
            $stmt->execute();
            $stats['pending_password_requests'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            ApiResponse::success($stats, 'Estatísticas carregadas com sucesso');
            
        } catch (Exception $e) {
            ApiResponse::serverError('Erro ao carregar estatísticas: ' . $e->getMessage());
        }
        break;
        
    default:
        ApiResponse::error('Método não permitido', 405);
}
?>

