<?php
/**
 * SafeNode - API de Análise Comportamental Detalhada
 * Retorna análises comportamentais completas do BehaviorAnalyzer
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

ob_start();

session_start();

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

if (!isset($_SESSION['safenode_logged_in']) || $_SESSION['safenode_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit;
}

try {
    require_once __DIR__ . '/../includes/config.php';
    require_once __DIR__ . '/../includes/init.php';
} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    error_log("SafeNode Includes Error: " . $e->getMessage());
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

// Parâmetros da requisição
$ipAddress = $_GET['ip'] ?? null;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
$riskLevel = $_GET['risk_level'] ?? null;
$timeWindow = isset($_GET['time_window']) ? (int)$_GET['time_window'] : 3600;

if (!$db) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Erro ao conectar ao banco de dados',
        'data' => []
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    ob_end_flush();
    exit;
}

// Verificar que o site pertence ao usuário logado
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

try {
    require_once __DIR__ . '/../includes/BehaviorAnalyzer.php';
    $behaviorAnalyzer = new BehaviorAnalyzer($db);
    
    $response = [
        'success' => true,
        'data' => [],
        'meta' => [
            'site_id' => $currentSiteId,
            'time_window' => $timeWindow,
            'total' => 0
        ]
    ];
    
    // Se IP específico foi solicitado, retornar análise detalhada
    if ($ipAddress && filter_var($ipAddress, FILTER_VALIDATE_IP)) {
        $behavior = $behaviorAnalyzer->analyzeIPBehavior($ipAddress, $timeWindow);
        
        // Buscar estatísticas básicas do IP
        $siteFilter = $currentSiteId > 0 ? "AND site_id = ?" : "";
        $params = $currentSiteId > 0 ? [$ipAddress, $currentSiteId] : [$ipAddress];
        
        $sql = "
            SELECT 
                ip_address,
                COUNT(*) as total_requests,
                SUM(CASE WHEN action_taken = 'blocked' THEN 1 ELSE 0 END) as blocked_count,
                AVG(threat_score) as avg_threat_score,
                MAX(threat_score) as max_threat_score,
                COUNT(DISTINCT threat_type) as unique_threat_types,
                COUNT(DISTINCT request_uri) as unique_uris,
                COUNT(DISTINCT user_agent) as unique_user_agents,
                MIN(created_at) as first_seen,
                MAX(created_at) as last_seen,
                COUNT(DISTINCT DATE(created_at)) as active_days
            FROM safenode_security_logs
            WHERE ip_address = ?
            $siteFilter
            AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $response['data'] = [
            'ip_address' => $ipAddress,
            'stats' => $stats ?: null,
            'behavior' => $behavior,
            'behaviors_detail' => $behavior['behaviors'] ?? []
        ];
        
        $response['meta']['total'] = 1;
    } else {
        // Listar IPs com análise comportamental
        $sql = "
            SELECT 
                ip_address,
                COUNT(*) as total_requests,
                SUM(CASE WHEN action_taken = 'blocked' THEN 1 ELSE 0 END) as blocked_count,
                AVG(threat_score) as avg_threat_score,
                MAX(threat_score) as max_threat_score,
                COUNT(DISTINCT threat_type) as unique_threat_types,
                COUNT(DISTINCT request_uri) as unique_uris,
                MAX(created_at) as last_seen
            FROM safenode_security_logs
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ";
        
        $params = [];
        if ($currentSiteId > 0) {
            $sql .= " AND site_id = ?";
            $params[] = $currentSiteId;
        } elseif ($userId) {
            $sql .= " AND site_id IN (SELECT id FROM safenode_sites WHERE user_id = ?)";
            $params[] = $userId;
        }
        
        $sql .= " GROUP BY ip_address 
                  HAVING blocked_count > 0 OR avg_threat_score > 20
                  ORDER BY blocked_count DESC, avg_threat_score DESC 
                  LIMIT ?";
        $params[] = $limit;
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $ips = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $results = [];
        foreach ($ips as $ip) {
            $behavior = $behaviorAnalyzer->analyzeIPBehavior($ip['ip_address'], $timeWindow);
            
            // Filtrar por nível de risco se especificado
            if ($riskLevel && $behavior['risk_level'] !== $riskLevel) {
                continue;
            }
            
            $results[] = [
                'ip_address' => $ip['ip_address'],
                'total_requests' => (int)$ip['total_requests'],
                'blocked_count' => (int)$ip['blocked_count'],
                'avg_threat_score' => round((float)$ip['avg_threat_score'], 2),
                'max_threat_score' => (int)$ip['max_threat_score'],
                'unique_threat_types' => (int)$ip['unique_threat_types'],
                'unique_uris' => (int)$ip['unique_uris'],
                'last_seen' => $ip['last_seen'],
                'behavior_risk_level' => $behavior['risk_level'],
                'behavior_risk_score' => $behavior['risk_score'],
                'anomaly_count' => count($behavior['anomalies'] ?? []),
                'confidence' => $behavior['confidence'] ?? 0,
                'behaviors_summary' => [
                    'frequency' => $behavior['behaviors']['frequency']['is_anomaly'] ?? false,
                    'uri_patterns' => $behavior['behaviors']['uri_patterns']['is_anomaly'] ?? false,
                    'user_agents' => $behavior['behaviors']['user_agents']['is_anomaly'] ?? false,
                    'time_patterns' => $behavior['behaviors']['time_patterns']['is_anomaly'] ?? false,
                    'error_rate' => $behavior['behaviors']['error_rate']['is_anomaly'] ?? false,
                    'action_sequences' => $behavior['behaviors']['action_sequences']['is_anomaly'] ?? false,
                    'navigation_pattern' => $behavior['behaviors']['navigation_pattern']['is_anomaly'] ?? false
                ]
            ];
        }
        
        $response['data'] = $results;
        $response['meta']['total'] = count($results);
    }
    
    ob_clean();
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
} catch (PDOException $e) {
    ob_clean();
    http_response_code(500);
    error_log("SafeNode Behavior Analysis API Error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'Erro ao buscar análise comportamental',
        'debug' => (defined('DEBUG_MODE') && DEBUG_MODE) ? $e->getMessage() : null
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    error_log("SafeNode Behavior Analysis API Error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'Erro ao processar requisição',
        'debug' => (defined('DEBUG_MODE') && DEBUG_MODE) ? $e->getMessage() : null
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

ob_end_flush();
?>

