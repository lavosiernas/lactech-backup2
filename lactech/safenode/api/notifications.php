<?php
/**
 * SafeNode - API de Notificações
 * Retorna notificações e alertas do sistema
 */

session_start();
header('Content-Type: application/json; charset=UTF-8');

if (!isset($_SESSION['safenode_logged_in']) || $_SESSION['safenode_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit;
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/init.php';

$db = getSafeNodeDatabase();
$userId = $_SESSION['safenode_user_id'] ?? null;
$currentSiteId = $_SESSION['view_site_id'] ?? 0;

if (!$db || !$userId) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erro ao conectar']);
    exit;
}

$action = $_GET['action'] ?? 'list';
$unreadOnly = isset($_GET['unread']) && $_GET['unread'] == '1';
$limit = min((int)($_GET['limit'] ?? 20), 100);

try {
    if ($action === 'count' || $unreadOnly) {
        // Contar notificações não lidas
        $sql = "SELECT COUNT(*) as count FROM safenode_security_logs WHERE 1=1";
        $params = [];
        
        if ($currentSiteId > 0) {
            $sql .= " AND site_id = ?";
            $params[] = $currentSiteId;
        } else {
            $sql .= " AND site_id IN (SELECT id FROM safenode_sites WHERE user_id = ?)";
            $params[] = $userId;
        }
        
        // Apenas ameaças críticas ou bloqueios recentes (últimas 24h)
        $sql .= " AND (threat_score >= 70 OR action_taken = 'blocked') 
                 AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                 AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $count = (int)($result['count'] ?? 0);
        
        echo json_encode([
            'success' => true,
            'count' => $count
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Listar notificações
    $sql = "SELECT 
                id,
                ip_address,
                request_uri,
                action_taken,
                threat_type,
                threat_score,
                created_at,
                CASE 
                    WHEN threat_score >= 70 THEN 'threat'
                    WHEN action_taken = 'blocked' THEN 'blocked'
                    WHEN threat_score >= 50 THEN 'warning'
                    ELSE 'info'
                END as type,
                0 as is_read
            FROM safenode_security_logs 
            WHERE 1=1";
    
    $params = [];
    
    if ($currentSiteId > 0) {
        $sql .= " AND site_id = ?";
        $params[] = $currentSiteId;
    } else {
        $sql .= " AND site_id IN (SELECT id FROM safenode_sites WHERE user_id = ?)";
        $params[] = $userId;
    }
    
    // Apenas eventos importantes (últimas 24h)
    $sql .= " AND (threat_score >= 50 OR action_taken = 'blocked') 
             AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
    
    $sql .= " ORDER BY created_at DESC LIMIT ?";
    $params[] = $limit;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $notifications = array_map(function($log) {
        $threatType = $log['threat_type'] ?? 'Ameaça desconhecida';
        $threatScore = (int)($log['threat_score'] ?? 0);
        
        $title = $threatScore >= 70 
            ? 'Ameaça Crítica Detectada'
            : ($log['action_taken'] === 'blocked' 
                ? 'IP Bloqueado'
                : 'Ameaça Detectada');
        
        $message = $threatScore >= 70
            ? "IP {$log['ip_address']} - {$threatType} (Score: {$threatScore})"
            : ($log['action_taken'] === 'blocked'
                ? "IP {$log['ip_address']} foi bloqueado"
                : "Ameaça do tipo {$threatType} detectada de {$log['ip_address']}");
        
        return [
            'id' => (int)$log['id'],
            'type' => $log['type'],
            'title' => $title,
            'message' => $message,
            'ip_address' => $log['ip_address'],
            'threat_type' => $threatType,
            'threat_score' => $threatScore,
            'created_at' => $log['created_at'],
            'is_read' => false
        ];
    }, $logs);
    
    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'count' => count($notifications)
    ], JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    error_log("SafeNode Notifications API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao buscar notificações'
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    error_log("SafeNode Notifications API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao processar requisição'
    ], JSON_UNESCAPED_UNICODE);
}
?>




