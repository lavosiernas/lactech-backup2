<?php
/**
 * SafeNode - API de Alertas
 * Retorna alertas e notificações do sistema
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ob_start();

session_start();

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

if (!isset($_SESSION['safenode_logged_in']) || $_SESSION['safenode_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit;
}

try {
    require_once __DIR__ . '/../includes/config.php';
    require_once __DIR__ . '/../includes/init.php';
    require_once __DIR__ . '/../includes/AlertManager.php';
} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    error_log("SafeNode Alerts Error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'Erro ao carregar configurações'
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    ob_end_flush();
    exit;
}

$db = getSafeNodeDatabase();
$currentSiteId = $_SESSION['view_site_id'] ?? 0;
$userId = $_SESSION['safenode_user_id'] ?? null;

if (!$db) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Erro ao conectar ao banco de dados'
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    ob_end_flush();
    exit;
}

// Verificar que o site pertence ao usuário
if ($currentSiteId > 0) {
    try {
        $stmt = $db->prepare("SELECT id FROM safenode_sites WHERE id = ? AND user_id = ?");
        $stmt->execute([$currentSiteId, $userId]);
        if (!$stmt->fetch()) {
            $currentSiteId = 0;
        }
    } catch (PDOException $e) {
        $currentSiteId = 0;
    }
}

// Parâmetros
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$limit = min((int)($_GET['limit'] ?? 50), 100);
$unreadOnly = isset($_GET['unread']) && $_GET['unread'] === '1';

try {
    if ($method === 'POST') {
        // Marcar como lido
        $input = json_decode(file_get_contents('php://input'), true);
        $alertId = (int)($input['alert_id'] ?? 0);
        
        if ($alertId > 0 && $userId) {
            $alertManager = new AlertManager($db);
            $success = $alertManager->markAsRead($alertId, $userId);
            
            ob_clean();
            echo json_encode([
                'success' => $success
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            ob_end_flush();
            exit;
        }
    }
    
    // Buscar alertas
    $sql = "
        SELECT 
            a.*,
            s.domain as site_domain
        FROM safenode_alerts a
        INNER JOIN safenode_sites s ON a.site_id = s.id
        WHERE 1=1
    ";
    
    $params = [];
    
    if ($currentSiteId > 0) {
        $sql .= " AND a.site_id = ?";
        $params[] = $currentSiteId;
    } elseif ($userId) {
        $sql .= " AND s.user_id = ?";
        $params[] = $userId;
    }
    
    if ($unreadOnly) {
        $sql .= " AND a.read = 0";
    }
    
    $sql .= " ORDER BY a.created_at DESC LIMIT ?";
    $params[] = $limit;
    
    $stmt = $db->prepare($sql);
    if ($stmt === false) {
        throw new Exception("Erro ao preparar query");
    }
    $stmt->execute($params);
    $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Processar alertas
    $alertList = [];
    foreach ($alerts as $alert) {
        $data = null;
        if ($alert['data']) {
            $data = json_decode($alert['data'], true);
        }
        
        $alertList[] = [
            'id' => (int)$alert['id'],
            'site_id' => (int)$alert['site_id'],
            'site_domain' => $alert['site_domain'],
            'alert_type' => $alert['alert_type'],
            'severity' => $alert['severity'],
            'title' => $alert['title'],
            'message' => $alert['message'],
            'data' => $data,
            'read' => (bool)$alert['read'],
            'email_sent' => (bool)$alert['email_sent'],
            'created_at' => $alert['created_at']
        ];
    }
    
    // Buscar contagem de não lidos
    $alertManager = new AlertManager($db);
    $unreadCount = $alertManager->getUnreadCount($currentSiteId, $userId);
    
    ob_clean();
    echo json_encode([
        'success' => true,
        'timestamp' => time(),
        'data' => [
            'alerts' => $alertList,
            'unread_count' => $unreadCount,
            'total' => count($alertList)
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    ob_end_flush();
    
} catch (PDOException $e) {
    ob_clean();
    error_log("SafeNode Alerts DB Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao buscar dados',
        'debug' => (defined('DEBUG_MODE') && DEBUG_MODE) ? $e->getMessage() : null
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    ob_end_flush();
} catch (Exception $e) {
    ob_clean();
    error_log("SafeNode Alerts Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao processar dados',
        'debug' => (defined('DEBUG_MODE') && DEBUG_MODE) ? $e->getMessage() : null
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    ob_end_flush();
}

