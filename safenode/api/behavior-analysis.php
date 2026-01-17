<?php
/**
 * SafeNode - API de Análise de Comportamento Anormal
 * Detecta IPs com comportamento suspeito (scanners, brute force, etc)
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
    error_log("SafeNode Behavior Analysis Error: " . $e->getMessage());
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
$timeframe = $_GET['timeframe'] ?? '24h'; // 24h, 7d
$limit = min((int)($_GET['limit'] ?? 20), 100);

// Calcular período
$whereTime = '';
$intervalHours = 24;
switch ($timeframe) {
    case '7d':
        $whereTime = "AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        $intervalHours = 168; // 7 dias
        break;
    default: // 24h
        $whereTime = "AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
        $intervalHours = 24;
}

try {
    // Preparar filtro de site/usuário
    $siteFilter = '';
    $params = [];
    
    if ($currentSiteId > 0) {
        $siteFilter = "AND site_id = ?";
        $params[] = $currentSiteId;
    } elseif ($userId) {
        $siteFilter = "AND (
            site_id IN (SELECT id FROM safenode_sites WHERE user_id = ?)
            OR api_key_id IN (SELECT id FROM safenode_hv_api_keys WHERE user_id = ?)
        )";
        $params[] = $userId;
        $params[] = $userId;
    }
    
    // Buscar IPs com comportamento suspeito
    // Critérios:
    // 1. Muitos endpoints diferentes em pouco tempo (scanner)
    // 2. Muitas tentativas bloqueadas (brute force)
    // 3. Padrão de requisições anormal
    // 4. User-Agent suspeito
    
    // Aumentar limite do GROUP_CONCAT temporariamente
    try {
        $db->exec("SET SESSION group_concat_max_len = 1000000");
    } catch (PDOException $e) {
        // Ignorar se não conseguir alterar
        error_log("Warning: Não foi possível alterar group_concat_max_len: " . $e->getMessage());
    }
    
    $sqlBehavior = "SELECT 
        ip_address,
        country_code,
        COUNT(*) as total_requests,
        COUNT(DISTINCT COALESCE(request_uri, '')) as unique_endpoints,
        COUNT(DISTINCT DATE_FORMAT(created_at, '%Y-%m-%d %H')) as active_hours,
        SUM(CASE WHEN event_type = 'bot_blocked' THEN 1 ELSE 0 END) as blocked_count,
        SUM(CASE WHEN event_type = 'human_validated' THEN 1 ELSE 0 END) as validated_count,
        GROUP_CONCAT(DISTINCT SUBSTRING_INDEX(SUBSTRING_INDEX(COALESCE(request_uri, ''), '?', 1), '#', 1) SEPARATOR ', ') as endpoints_list,
        MIN(created_at) as first_seen,
        MAX(created_at) as last_seen,
        GROUP_CONCAT(DISTINCT COALESCE(user_agent, '') SEPARATOR ' | ') as user_agents
        FROM safenode_human_verification_logs 
        WHERE 1=1
        " . (!empty($whereTime) ? $whereTime : '') . "
        " . (!empty($siteFilter) ? $siteFilter : '') . "
        GROUP BY ip_address, country_code
        HAVING total_requests > 10
        ORDER BY unique_endpoints DESC, blocked_count DESC, total_requests DESC
        LIMIT ?";
    
    $paramsBehavior = array_merge($params, [$limit]);
    $stmtBehavior = $db->prepare($sqlBehavior);
    if ($stmtBehavior === false) {
        $errorInfo = $db->errorInfo();
        error_log("Erro ao preparar query behavior: " . print_r($errorInfo, true));
        throw new Exception("Erro ao preparar query: " . implode(", ", $errorInfo ?? ['Erro desconhecido']));
    }
    $stmtBehavior->execute($paramsBehavior);
    $behaviors = $stmtBehavior->fetchAll(PDO::FETCH_ASSOC);
    
    // Calcular scores de risco e classificar comportamentos
    $anomalies = [];
    
    foreach ($behaviors as $behavior) {
        $totalRequests = (int)$behavior['total_requests'];
        $uniqueEndpoints = (int)$behavior['unique_endpoints'];
        $blockedCount = (int)$behavior['blocked_count'];
        $activeHours = (int)$behavior['active_hours'];
        
        // Calcular score de risco (0-100)
        $riskScore = 0;
        $behaviorTypes = [];
        
        // Scanner: Muitos endpoints diferentes
        $endpointRatio = $uniqueEndpoints / max($totalRequests, 1);
        if ($uniqueEndpoints > 20 || $endpointRatio > 0.5) {
            $riskScore += 30;
            $behaviorTypes[] = 'scanner';
        }
        
        // Brute Force: Muitos bloqueios
        $blockedRatio = $blockedCount / max($totalRequests, 1);
        if ($blockedCount > 10 || $blockedRatio > 0.7) {
            $riskScore += 35;
            $behaviorTypes[] = 'brute_force';
        }
        
        // Reconhecimento: Muitas requisições em pouco tempo
        $requestsPerHour = $totalRequests / max($activeHours, 1);
        if ($requestsPerHour > 50) {
            $riskScore += 20;
            $behaviorTypes[] = 'reconnaissance';
        }
        
        // User-Agent suspeito
        $userAgents = strtolower($behavior['user_agents'] ?? '');
        if (preg_match('/(bot|crawler|spider|scanner|python|curl|wget|sqlmap|nikto|acunetix)/i', $userAgents)) {
            $riskScore += 15;
            $behaviorTypes[] = 'suspicious_user_agent';
        }
        
        // Limitar score
        $riskScore = min(100, $riskScore);
        
        // Determinar severidade
        $severity = 'low';
        if ($riskScore >= 70) {
            $severity = 'high';
        } elseif ($riskScore >= 40) {
            $severity = 'medium';
        }
        
        // Filtrar apenas anomalias relevantes (score >= 40)
        if ($riskScore >= 40) {
            $endpointsList = [];
            if (!empty($behavior['endpoints_list'])) {
                $endpointsList = explode(', ', $behavior['endpoints_list']);
                $endpointsList = array_filter($endpointsList, fn($e) => !empty(trim($e))); // Remover vazios
                $endpointsList = array_slice($endpointsList, 0, 10); // Limitar a 10 endpoints
            }
            
            $anomalies[] = [
                'ip_address' => $behavior['ip_address'],
                'country' => $behavior['country_code'],
                'risk_score' => $riskScore,
                'severity' => $severity,
                'behavior_types' => $behaviorTypes,
                'stats' => [
                    'total_requests' => $totalRequests,
                    'unique_endpoints' => $uniqueEndpoints,
                    'blocked_count' => $blockedCount,
                    'validated_count' => (int)$behavior['validated_count'],
                    'requests_per_hour' => round($requestsPerHour, 2),
                    'endpoint_ratio' => round($endpointRatio * 100, 2),
                    'blocked_ratio' => round($blockedRatio * 100, 2)
                ],
                'endpoints_sample' => $endpointsList,
                'user_agents' => $behavior['user_agents'] ?? '',
                'first_seen' => $behavior['first_seen'],
                'last_seen' => $behavior['last_seen'],
                'activity_window_hours' => $activeHours
            ];
        }
    }
    
    // Ordenar por score de risco
    usort($anomalies, function($a, $b) {
        return $b['risk_score'] - $a['risk_score'];
    });
    
    // Estatísticas gerais
    $totalAnomalies = count($anomalies);
    $highRiskCount = count(array_filter($anomalies, fn($a) => $a['severity'] === 'high'));
    $mediumRiskCount = count(array_filter($anomalies, fn($a) => $a['severity'] === 'medium'));
    
    // Padrões mais comuns
    $patternCounts = [];
    foreach ($anomalies as $anomaly) {
        foreach ($anomaly['behavior_types'] as $type) {
            if (!isset($patternCounts[$type])) {
                $patternCounts[$type] = 0;
            }
            $patternCounts[$type]++;
        }
    }
    arsort($patternCounts);
    
    // Heatmap de atividade (por hora) - Simplificado para evitar problemas com subquery
    $heatmapData = [];
    try {
        // Buscar IPs suspeitos primeiro (top 20)
        $topIPs = [];
        foreach ($anomalies as $anomaly) {
            $topIPs[] = $anomaly['ip_address'];
            if (count($topIPs) >= 20) break;
        }
        
        if (!empty($topIPs)) {
            $placeholders = implode(',', array_fill(0, count($topIPs), '?'));
            $sqlHeatmap = "SELECT 
                DATE_FORMAT(created_at, '%Y-%m-%d %H:00') as hour,
                ip_address,
                COUNT(*) as request_count
                FROM safenode_human_verification_logs 
                WHERE ip_address IN ($placeholders)
                AND 1=1
                " . (!empty($whereTime) ? $whereTime : '') . "
                " . (!empty($siteFilter) ? $siteFilter : '') . "
                GROUP BY hour, ip_address
                ORDER BY hour DESC, request_count DESC";
            
            $paramsHeatmap = array_merge($topIPs, $params);
            $stmtHeatmap = $db->prepare($sqlHeatmap);
            if ($stmtHeatmap !== false) {
                $stmtHeatmap->execute($paramsHeatmap);
                $heatmapData = $stmtHeatmap->fetchAll(PDO::FETCH_ASSOC);
            }
        }
    } catch (Exception $e) {
        error_log("SafeNode Behavior Analysis Heatmap Error: " . $e->getMessage());
        $heatmapData = []; // Continuar mesmo se heatmap falhar
    }
    
    // Processar heatmap
    $heatmap = [];
    foreach ($heatmapData as $row) {
        $hour = $row['hour'];
        if (!isset($heatmap[$hour])) {
            $heatmap[$hour] = [];
        }
        $heatmap[$hour][] = [
            'ip' => $row['ip_address'],
            'count' => (int)$row['request_count']
        ];
    }
    
    ob_clean();
    echo json_encode([
        'success' => true,
        'timestamp' => time(),
        'timeframe' => $timeframe,
        'data' => [
            'anomalies' => $anomalies,
            'stats' => [
                'total_anomalies' => $totalAnomalies,
                'high_risk_count' => $highRiskCount,
                'medium_risk_count' => $mediumRiskCount,
                'pattern_counts' => $patternCounts
            ],
            'heatmap' => $heatmap
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    ob_end_flush();
    
} catch (PDOException $e) {
    ob_clean();
    error_log("SafeNode Behavior Analysis DB Error: " . $e->getMessage());
    error_log("SQL: " . ($sqlBehavior ?? 'N/A'));
    error_log("Params: " . print_r($paramsBehavior ?? [], true));
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao buscar dados',
        'debug' => (defined('DEBUG_MODE') && DEBUG_MODE) ? $e->getMessage() : null
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    ob_end_flush();
} catch (Exception $e) {
    ob_clean();
    error_log("SafeNode Behavior Analysis Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao processar dados',
        'debug' => (defined('DEBUG_MODE') && DEBUG_MODE) ? $e->getMessage() : null
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    ob_end_flush();
}

