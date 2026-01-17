<?php
/**
 * SafeNode - API de Estatísticas de Performance
 * Retorna estatísticas gerais de performance
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
    error_log("SafeNode Performance Stats Error: " . $e->getMessage());
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
$timeframe = $_GET['timeframe'] ?? '24h'; // 24h, 7d, 30d

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
        // Se tabela não existe, retornar dados vazios mas sucesso
        ob_clean();
        echo json_encode([
            'success' => true,
            'timestamp' => time(),
            'timeframe' => $timeframe,
            'data' => [
                'avg_response_time' => 0,
                'total_requests' => 0,
                'slow_requests' => 0,
                'performance_score' => 100,
                'by_hour' => [],
                'message' => 'Tabela de performance ainda não foi criada. Execute o SQL da Fase 2.'
            ]
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        ob_end_flush();
        exit;
    }
    
    // Estatísticas gerais
    $sqlStats = "SELECT 
        COUNT(*) as total_requests,
        AVG(response_time) as avg_response_time,
        MIN(response_time) as min_response_time,
        MAX(response_time) as max_response_time,
        0 as p95_response_time,
        SUM(CASE WHEN response_time > 1000 THEN 1 ELSE 0 END) as slow_requests,
        SUM(CASE WHEN response_time > 3000 THEN 1 ELSE 0 END) as very_slow_requests,
        AVG(memory_usage) as avg_memory_usage
        FROM safenode_performance_logs 
        WHERE 1=1
        " . (!empty($whereTime) ? $whereTime : '') . "
        " . (!empty($siteFilter) ? $siteFilter : '') . "";
    
    $stmtStats = $db->prepare($sqlStats);
    if ($stmtStats === false) {
        throw new Exception("Erro ao preparar query: " . implode(", ", $db->errorInfo()));
    }
    if (!empty($params)) {
        $stmtStats->execute($params);
    } else {
        $stmtStats->execute();
    }
    $stats = $stmtStats->fetch(PDO::FETCH_ASSOC);
    
    // Calcular performance score (0-100)
    // Baseado em: tempo médio de resposta
    $avgResponseTime = (float)($stats['avg_response_time'] ?? 0);
    $performanceScore = 100;
    if ($avgResponseTime > 3000) {
        $performanceScore = 20; // Muito lento
    } elseif ($avgResponseTime > 2000) {
        $performanceScore = 40; // Lento
    } elseif ($avgResponseTime > 1000) {
        $performanceScore = 60; // Médio
    } elseif ($avgResponseTime > 500) {
        $performanceScore = 80; // Bom
    }
    
    // Ajustar score baseado em requisições lentas
    $totalRequests = (int)($stats['total_requests'] ?? 0);
    $slowRequests = (int)($stats['slow_requests'] ?? 0);
    if ($totalRequests > 0) {
        $slowRatio = $slowRequests / $totalRequests;
        if ($slowRatio > 0.3) {
            $performanceScore = max(0, $performanceScore - 30); // Penalizar muito se >30% são lentas
        } elseif ($slowRatio > 0.1) {
            $performanceScore = max(0, $performanceScore - 15); // Penalizar se >10% são lentas
        }
    }
    
    // Estatísticas por hora
    $sqlHourly = "SELECT 
        DATE_FORMAT(created_at, '%H:00') as hour,
        AVG(response_time) as avg_response_time,
        COUNT(*) as request_count,
        SUM(CASE WHEN response_time > 1000 THEN 1 ELSE 0 END) as slow_count
        FROM safenode_performance_logs 
        WHERE 1=1
        " . (!empty($whereTime) ? $whereTime : '') . "
        " . (!empty($siteFilter) ? $siteFilter : '') . "
        GROUP BY hour
        ORDER BY hour ASC";
    
    $stmtHourly = $db->prepare($sqlHourly);
    if (!empty($params)) {
        $stmtHourly->execute($params);
    } else {
        $stmtHourly->execute();
    }
    $hourlyData = $stmtHourly->fetchAll(PDO::FETCH_ASSOC);
    
    // Processar dados por hora
    $byHour = [];
    foreach ($hourlyData as $row) {
        $byHour[] = [
            'hour' => $row['hour'],
            'avg_response_time' => round((float)$row['avg_response_time'], 2),
            'request_count' => (int)$row['request_count'],
            'slow_count' => (int)$row['slow_count']
        ];
    }
    
    ob_clean();
    echo json_encode([
        'success' => true,
        'timestamp' => time(),
        'timeframe' => $timeframe,
        'data' => [
            'avg_response_time' => round($avgResponseTime, 2),
            'min_response_time' => (int)($stats['min_response_time'] ?? 0),
            'max_response_time' => (int)($stats['max_response_time'] ?? 0),
            'p95_response_time' => (int)($stats['p95_response_time'] ?? 0),
            'total_requests' => $totalRequests,
            'slow_requests' => $slowRequests,
            'very_slow_requests' => (int)($stats['very_slow_requests'] ?? 0),
            'avg_memory_usage' => round((float)($stats['avg_memory_usage'] ?? 0) / 1048576, 2), // MB
            'performance_score' => round($performanceScore, 0),
            'by_hour' => $byHour
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    ob_end_flush();
    
} catch (PDOException $e) {
    ob_clean();
    error_log("SafeNode Performance Stats DB Error: " . $e->getMessage());
    error_log("SQL: " . ($sqlStats ?? 'N/A'));
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao buscar dados',
        'debug' => (defined('DEBUG_MODE') && DEBUG_MODE) ? $e->getMessage() : null
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    ob_end_flush();
} catch (Exception $e) {
    ob_clean();
    error_log("SafeNode Performance Stats Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao processar dados',
        'debug' => (defined('DEBUG_MODE') && DEBUG_MODE) ? $e->getMessage() : null
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    ob_end_flush();
}

