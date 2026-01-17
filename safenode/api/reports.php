<?php
/**
 * SafeNode - API de Relatórios
 * Gera relatórios mensais de segurança e performance
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
    error_log("SafeNode Reports Error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'Erro ao carregar configurações'
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    ob_end_flush();
    exit;
}

try {
    $db = getSafeNodeDatabase();
} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Erro ao conectar ao banco de dados',
        'debug' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    ob_end_flush();
    exit;
}

$currentSiteId = $_SESSION['view_site_id'] ?? 0;
$userId = $_SESSION['safenode_user_id'] ?? null;

if (!$db) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Erro ao conectar ao banco de dados',
        'debug' => 'getSafeNodeDatabase() retornou null'
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
$period = $_GET['period'] ?? 'month'; // month, week, custom
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

// Calcular período
$whereTime = '';
switch ($period) {
    case 'week':
        $whereTime = "AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        break;
    case 'month':
        $whereTime = "AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        break;
    case 'custom':
        if ($dateFrom) {
            $whereTime .= " AND DATE(created_at) >= ?";
        }
        if ($dateTo) {
            $whereTime .= " AND DATE(created_at) <= ?";
        }
        break;
    default:
        $whereTime = "AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
}

try {
    // Verificar se a coluna api_key_id existe na tabela
    $hasApiKeyId = false;
    try {
        $checkStmt = $db->query("SHOW COLUMNS FROM safenode_human_verification_logs LIKE 'api_key_id'");
        $hasApiKeyId = $checkStmt->rowCount() > 0;
        error_log("SafeNode Reports: api_key_id existe? " . ($hasApiKeyId ? 'SIM' : 'NÃO'));
    } catch (PDOException $e) {
        // Se não conseguir verificar, assumir que não existe
        $hasApiKeyId = false;
        error_log("SafeNode Reports: Erro ao verificar api_key_id: " . $e->getMessage());
    }
    
    // Preparar filtro de site/usuário
    $siteFilter = '';
    $params = [];
    
    if ($currentSiteId > 0) {
        $siteFilter = "AND site_id = ?";
        $params[] = $currentSiteId;
    } elseif ($userId) {
        // Usar apenas site_id (api_key_id pode não existir na tabela)
        $siteFilter = "AND site_id IN (SELECT id FROM safenode_sites WHERE user_id = ?)";
        $params[] = $userId;
    } else {
        // Sem site e sem userId, retornar vazio
        ob_clean();
        echo json_encode([
            'success' => true,
            'timestamp' => time(),
            'period' => $period,
            'data' => [
                'security' => [
                    'total_events' => 0,
                    'bots_blocked' => 0,
                    'humans_validated' => 0,
                    'unique_ips' => 0,
                    'active_days' => 0
                ],
                'top_blocked_ips' => [],
                'performance' => null,
                'alerts' => null
            ]
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        ob_end_flush();
        exit;
    }
    
    // Estatísticas de segurança
    $sqlSecurity = "SELECT 
        COUNT(*) as total_events,
        SUM(CASE WHEN event_type = 'bot_blocked' THEN 1 ELSE 0 END) as bots_blocked,
        SUM(CASE WHEN event_type = 'human_validated' THEN 1 ELSE 0 END) as humans_validated,
        COUNT(DISTINCT ip_address) as unique_ips,
        COUNT(DISTINCT DATE(created_at)) as active_days
        FROM safenode_human_verification_logs 
        WHERE 1=1";
    
    if (!empty($whereTime)) {
        $sqlSecurity .= " " . $whereTime;
    }
    if (!empty($siteFilter)) {
        $sqlSecurity .= " " . $siteFilter;
    }
    
    $paramsSecurity = [];
    if (!empty($params)) {
        $paramsSecurity = array_merge($paramsSecurity, $params);
    }
    if ($period === 'custom') {
        if ($dateFrom) {
            $sqlSecurity .= " AND DATE(created_at) >= ?";
            $paramsSecurity[] = $dateFrom;
        }
        if ($dateTo) {
            $sqlSecurity .= " AND DATE(created_at) <= ?";
            $paramsSecurity[] = $dateTo;
        }
    }
    
    try {
        $stmtSecurity = $db->prepare($sqlSecurity);
        if ($stmtSecurity === false) {
            throw new Exception("Erro ao preparar query de segurança: " . implode(", ", $db->errorInfo()));
        }
        $stmtSecurity->execute($paramsSecurity);
        $securityStats = $stmtSecurity->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erro ao executar query de segurança: " . $e->getMessage());
        error_log("SQL: " . $sqlSecurity);
        error_log("Params: " . print_r($paramsSecurity, true));
        throw $e;
    }
    
    // Top IPs bloqueados
    $sqlTopIPs = "SELECT 
        ip_address, 
        COUNT(*) as block_count,
        MAX(created_at) as last_seen
        FROM safenode_human_verification_logs 
        WHERE event_type = 'bot_blocked'";
    
    if (!empty($whereTime)) {
        $sqlTopIPs .= " " . $whereTime;
    }
    if (!empty($siteFilter)) {
        $sqlTopIPs .= " " . $siteFilter;
    }
    
    $sqlTopIPs .= " GROUP BY ip_address ORDER BY block_count DESC LIMIT 10";
    
    $paramsTopIPs = [];
    if (!empty($params)) {
        $paramsTopIPs = array_merge($paramsTopIPs, $params);
    }
    if ($period === 'custom') {
        if ($dateFrom) {
            $sqlTopIPs .= " AND DATE(created_at) >= ?";
            $paramsTopIPs[] = $dateFrom;
        }
        if ($dateTo) {
            $sqlTopIPs .= " AND DATE(created_at) <= ?";
            $paramsTopIPs[] = $dateTo;
        }
    }
    
    try {
        $stmtTopIPs = $db->prepare($sqlTopIPs);
        if ($stmtTopIPs === false) {
            throw new Exception("Erro ao preparar query de top IPs: " . implode(", ", $db->errorInfo()));
        }
        $stmtTopIPs->execute($paramsTopIPs);
        $topIPs = $stmtTopIPs->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erro ao executar query de top IPs: " . $e->getMessage());
        error_log("SQL: " . $sqlTopIPs);
        error_log("Params: " . print_r($paramsTopIPs, true));
        throw $e;
    }
    
    // Estatísticas de performance (se tabela existe)
    $performanceStats = null;
    try {
        $stmt = $db->query("SELECT 1 FROM safenode_performance_logs LIMIT 1");
        $perfTableExists = true;
    } catch (PDOException $e) {
        $perfTableExists = false;
    }
    
    if ($perfTableExists) {
        $sqlPerformance = "SELECT 
            COUNT(*) as total_requests,
            AVG(response_time) as avg_response_time,
            MAX(response_time) as max_response_time,
            SUM(CASE WHEN response_time > 1000 THEN 1 ELSE 0 END) as slow_requests
            FROM safenode_performance_logs 
            WHERE 1=1";
        
        if (!empty($whereTime)) {
            $sqlPerformance .= " " . $whereTime;
        }
        if (!empty($siteFilter)) {
            $sqlPerformance .= " " . $siteFilter;
        }
        
        $paramsPerformance = [];
        if (!empty($params)) {
            $paramsPerformance = array_merge($paramsPerformance, $params);
        }
        if ($period === 'custom') {
            if ($dateFrom) {
                $sqlPerformance .= " AND DATE(created_at) >= ?";
                $paramsPerformance[] = $dateFrom;
            }
            if ($dateTo) {
                $sqlPerformance .= " AND DATE(created_at) <= ?";
                $paramsPerformance[] = $dateTo;
            }
        }
        
        $stmtPerformance = $db->prepare($sqlPerformance);
        if ($stmtPerformance === false) {
            error_log("Erro ao preparar query de performance");
            $performanceStats = null;
        } else {
            $stmtPerformance->execute($paramsPerformance);
            $performanceStats = $stmtPerformance->fetch(PDO::FETCH_ASSOC);
        }
    }
    
    // Alertas (se tabela existe)
    $alertsStats = null;
    try {
        $stmt = $db->query("SELECT 1 FROM safenode_alerts LIMIT 1");
        $alertsTableExists = true;
    } catch (PDOException $e) {
        $alertsTableExists = false;
    }
    
    if ($alertsTableExists && ($currentSiteId > 0 || $userId)) {
        $sqlAlerts = "SELECT 
            COUNT(*) as total_alerts,
            SUM(CASE WHEN severity = 'critical' THEN 1 ELSE 0 END) as critical_alerts,
            SUM(CASE WHEN severity = 'high' THEN 1 ELSE 0 END) as high_alerts
            FROM safenode_alerts a
            INNER JOIN safenode_sites s ON a.site_id = s.id
            WHERE 1=1";
        
        $paramsAlerts = [];
        
        if ($currentSiteId > 0) {
            $sqlAlerts .= " AND a.site_id = ?";
            $paramsAlerts[] = $currentSiteId;
        } elseif ($userId) {
            $sqlAlerts .= " AND s.user_id = ?";
            $paramsAlerts[] = $userId;
        }
        
        if (!empty($whereTime)) {
            $sqlAlerts .= " " . str_replace('created_at', 'a.created_at', $whereTime);
        }
        
        if ($period === 'custom') {
            if ($dateFrom) {
                $sqlAlerts .= " AND DATE(a.created_at) >= ?";
                $paramsAlerts[] = $dateFrom;
            }
            if ($dateTo) {
                $sqlAlerts .= " AND DATE(a.created_at) <= ?";
                $paramsAlerts[] = $dateTo;
            }
        }
        
        // Executar query
        try {
            $stmtAlerts = $db->prepare($sqlAlerts);
            if ($stmtAlerts !== false) {
                $stmtAlerts->execute($paramsAlerts);
                $alertsStats = $stmtAlerts->fetch(PDO::FETCH_ASSOC);
            } else {
                $alertsStats = null;
            }
        } catch (PDOException $e) {
            error_log("Erro ao buscar alertas: " . $e->getMessage());
            $alertsStats = null;
        }
    }
    
    ob_clean();
    echo json_encode([
        'success' => true,
        'timestamp' => time(),
        'period' => $period,
        'date_from' => $dateFrom,
        'date_to' => $dateTo,
        'data' => [
            'security' => [
                'total_events' => (int)($securityStats['total_events'] ?? 0),
                'bots_blocked' => (int)($securityStats['bots_blocked'] ?? 0),
                'humans_validated' => (int)($securityStats['humans_validated'] ?? 0),
                'unique_ips' => (int)($securityStats['unique_ips'] ?? 0),
                'active_days' => (int)($securityStats['active_days'] ?? 0)
            ],
            'top_blocked_ips' => is_array($topIPs) ? array_map(function($ip) {
                return [
                    'ip' => $ip['ip_address'],
                    'block_count' => (int)$ip['block_count'],
                    'last_seen' => $ip['last_seen']
                ];
            }, $topIPs) : [],
            'performance' => $performanceStats ? [
                'total_requests' => (int)($performanceStats['total_requests'] ?? 0),
                'avg_response_time' => round((float)($performanceStats['avg_response_time'] ?? 0), 2),
                'max_response_time' => (int)($performanceStats['max_response_time'] ?? 0),
                'slow_requests' => (int)($performanceStats['slow_requests'] ?? 0)
            ] : null,
            'alerts' => $alertsStats ? [
                'total_alerts' => (int)($alertsStats['total_alerts'] ?? 0),
                'critical_alerts' => (int)($alertsStats['critical_alerts'] ?? 0),
                'high_alerts' => (int)($alertsStats['high_alerts'] ?? 0)
            ] : null
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    ob_end_flush();
    
} catch (PDOException $e) {
    ob_clean();
    $errorMsg = $e->getMessage();
    $errorFile = $e->getFile();
    $errorLine = $e->getLine();
    error_log("SafeNode Reports DB Error: " . $errorMsg);
    error_log("SQL: " . ($sqlSecurity ?? 'N/A'));
    error_log("Params: " . print_r($paramsSecurity ?? [], true));
    error_log("Trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao gerar relatório',
        'debug' => $errorMsg . " | SQL: " . substr($sqlSecurity ?? 'N/A', 0, 200),
        'file' => basename($errorFile),
        'line' => $errorLine
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    ob_end_flush();
    exit;
} catch (Exception $e) {
    ob_clean();
    $errorMsg = $e->getMessage();
    $errorFile = $e->getFile();
    $errorLine = $e->getLine();
    error_log("SafeNode Reports Error: " . $errorMsg);
    error_log("Trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao processar relatório',
        'debug' => $errorMsg,
        'file' => basename($errorFile),
        'line' => $errorLine
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    ob_end_flush();
    exit;
} catch (Throwable $e) {
    ob_clean();
    $errorMsg = $e->getMessage();
    $errorFile = $e->getFile();
    $errorLine = $e->getLine();
    error_log("SafeNode Reports Fatal Error: " . $errorMsg);
    error_log("Trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro fatal ao processar relatório',
        'debug' => $errorMsg,
        'file' => basename($errorFile),
        'line' => $errorLine
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    ob_end_flush();
    exit;
}

