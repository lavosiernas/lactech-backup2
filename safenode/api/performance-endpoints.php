<?php
/**
 * SafeNode - API de Endpoints mais Lentos
 * Retorna lista de endpoints com pior performance
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
    error_log("SafeNode Performance Endpoints Error: " . $e->getMessage());
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
        'error' => 'Erro ao conectar ao banco de dados',
        'data' => []
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
$timeframe = $_GET['timeframe'] ?? '24h';
$limit = min((int)($_GET['limit'] ?? 20), 50);

// Calcular período
$whereTime = '';
switch ($timeframe) {
    case '7d':
        $whereTime = "AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        break;
    case '30d':
        $whereTime = "AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        break;
    default: // 24h
        $whereTime = "AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
}

try {
    // Preparar filtro de site/usuário
    $siteFilter = '';
    $params = [];
    
    if ($currentSiteId > 0) {
        $siteFilter = "AND site_id = ?";
        $params[] = $currentSiteId;
    } elseif ($userId) {
        $siteFilter = "AND site_id IN (SELECT id FROM safenode_sites WHERE user_id = ?)";
        $params[] = $userId;
    }
    
    // Verificar se tabela existe (tentando uma query simples)
    $tableExists = false;
    try {
        $stmt = $db->query("SELECT 1 FROM safenode_performance_logs LIMIT 1");
        $tableExists = true;
    } catch (PDOException $e) {
        // Tabela não existe ou erro de acesso
        $tableExists = false;
    }
    
    if (!$tableExists) {
        ob_clean();
        echo json_encode([
            'success' => true,
            'timestamp' => time(),
            'timeframe' => $timeframe,
            'data' => [
                'endpoints' => [],
                'message' => 'Tabela de performance ainda não foi criada. Execute o SQL da Fase 2.'
            ]
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        ob_end_flush();
        exit;
    }
    
    // Buscar endpoints mais lentos
    $sqlEndpoints = "SELECT 
        endpoint,
        request_method,
        COUNT(*) as request_count,
        AVG(response_time) as avg_response_time,
        MIN(response_time) as min_response_time,
        MAX(response_time) as max_response_time,
        SUM(CASE WHEN response_time > 1000 THEN 1 ELSE 0 END) as slow_count,
        AVG(memory_usage) as avg_memory_usage
        FROM safenode_performance_logs 
        WHERE 1=1
        " . (!empty($whereTime) ? $whereTime : '') . "
        " . (!empty($siteFilter) ? $siteFilter : '') . "
        GROUP BY endpoint, request_method
        HAVING request_count >= 3
        ORDER BY avg_response_time DESC
        LIMIT ?";
    
    $paramsEndpoints = array_merge($params, [$limit]);
    $stmtEndpoints = $db->prepare($sqlEndpoints);
    if ($stmtEndpoints === false) {
        throw new Exception("Erro ao preparar query: " . implode(", ", $db->errorInfo()));
    }
    $stmtEndpoints->execute($paramsEndpoints);
    $endpoints = $stmtEndpoints->fetchAll(PDO::FETCH_ASSOC);
    
    // Processar e classificar endpoints
    $endpointList = [];
    foreach ($endpoints as $endpoint) {
        $avgTime = (float)$endpoint['avg_response_time'];
        $slowCount = (int)$endpoint['slow_count'];
        $totalCount = (int)$endpoint['request_count'];
        
        // Classificar severidade
        $severity = 'low';
        if ($avgTime > 3000 || ($slowCount / max($totalCount, 1)) > 0.5) {
            $severity = 'critical';
        } elseif ($avgTime > 2000 || ($slowCount / max($totalCount, 1)) > 0.3) {
            $severity = 'high';
        } elseif ($avgTime > 1000 || ($slowCount / max($totalCount, 1)) > 0.1) {
            $severity = 'medium';
        }
        
        $endpointList[] = [
            'endpoint' => $endpoint['endpoint'],
            'method' => $endpoint['request_method'],
            'avg_response_time' => round($avgTime, 2),
            'min_response_time' => (int)$endpoint['min_response_time'],
            'max_response_time' => (int)$endpoint['max_response_time'],
            'request_count' => $totalCount,
            'slow_count' => $slowCount,
            'slow_ratio' => round(($slowCount / max($totalCount, 1)) * 100, 2),
            'avg_memory_usage_mb' => round((float)($endpoint['avg_memory_usage'] ?? 0) / 1048576, 2),
            'severity' => $severity
        ];
    }
    
    ob_clean();
    echo json_encode([
        'success' => true,
        'timestamp' => time(),
        'timeframe' => $timeframe,
        'data' => [
            'endpoints' => $endpointList,
            'total_endpoints' => count($endpointList)
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    ob_end_flush();
    
} catch (PDOException $e) {
    ob_clean();
    error_log("SafeNode Performance Endpoints DB Error: " . $e->getMessage());
    error_log("SQL: " . ($sqlEndpoints ?? 'N/A'));
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao buscar dados',
        'debug' => (defined('DEBUG_MODE') && DEBUG_MODE) ? $e->getMessage() : null
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    ob_end_flush();
} catch (Exception $e) {
    ob_clean();
    error_log("SafeNode Performance Endpoints Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao processar dados',
        'debug' => (defined('DEBUG_MODE') && DEBUG_MODE) ? $e->getMessage() : null
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    ob_end_flush();
}

