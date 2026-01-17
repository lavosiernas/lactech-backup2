<?php
/**
 * SafeNode - API de Detecção de Ameaças
 * Retorna ameaças detectadas (SQL Injection, XSS, etc) e estatísticas
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
    error_log("SafeNode Threat Detection Error: " . $e->getMessage());
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

// Parâmetros da requisição
$timeframe = $_GET['timeframe'] ?? '24h'; // 24h, 7d, 30d
$limit = min((int)($_GET['limit'] ?? 50), 200); // Máximo 200

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
        $siteFilter = "AND (
            site_id IN (SELECT id FROM safenode_sites WHERE user_id = ?)
            OR api_key_id IN (SELECT id FROM safenode_hv_api_keys WHERE user_id = ?)
        )";
        $params[] = $userId;
        $params[] = $userId;
    }
    
    // Função para detectar tipo de ameaça do reason e request_uri
    if (!function_exists('detectThreatType')) {
        function detectThreatType($reason, $requestUri) {
        $reason = strtolower($reason ?? '');
        $uri = strtolower($requestUri ?? '');
        $data = $reason . ' ' . $uri;
        
        // SQL Injection
        if (preg_match('/(sql|union|select|insert|update|delete|drop|truncate|information_schema|benchmark|sleep|load_file)/i', $data) ||
            preg_match('/[\'"]\s*(or|and)\s*\d+\s*=\s*\d+/i', $data) ||
            preg_match('/(--|\#|\/\*)/', $data)) {
            return 'sql_injection';
        }
        
        // XSS
        if (preg_match('/(<script|javascript:|onerror=|onload=|onclick=|<iframe|eval\s*\(|base64_decode)/i', $data)) {
            return 'xss';
        }
        
        // Command Injection
        if (preg_match('/(;\s*(ping|nslookup|whoami|uname|ls|cat|wget|curl|nc|bash|sh)\b|\$\(|\`)/i', $data)) {
            return 'command_injection';
        }
        
        // Path Traversal
        if (preg_match('/(\.\.\/|\.\.\\\\|\/etc\/passwd|\/etc\/shadow|\/windows\/win\.ini)/i', $data)) {
            return 'path_traversal';
        }
        
        // RCE PHP
        if (preg_match('/(php:\/\/|eval\s*\(|assert\s*\(|passthru\s*\(|exec\s*\(|system\s*\()/i', $data)) {
            return 'rce_php';
        }
        
        // Brute Force
        if (preg_match('/(brute|force|login|password|auth)/i', $reason)) {
            return 'brute_force';
        }
        
        // Bot/Scanner
        if (preg_match('/(bot|scanner|spider|crawler|sqlmap|nikto|acunetix)/i', $data)) {
            return 'bot_scanner';
        }
        
        return 'unknown';
        }
    }
    
    // Buscar logs bloqueados com reason (possíveis ameaças)
    $sqlThreats = "SELECT 
        id,
        site_id,
        api_key_id,
        ip_address,
        event_type,
        request_uri,
        request_method,
        user_agent,
        country_code,
        reason,
        created_at
        FROM safenode_human_verification_logs 
        WHERE event_type = 'bot_blocked'
        " . (!empty($whereTime) ? $whereTime : '') . "
        " . (!empty($siteFilter) ? $siteFilter : '') . "
        ORDER BY created_at DESC 
        LIMIT ?";
    
    $paramsThreats = array_merge($params, [$limit]);
    $stmtThreats = $db->prepare($sqlThreats);
    if ($stmtThreats === false) {
        throw new Exception("Erro ao preparar query: " . implode(", ", $db->errorInfo()));
    }
    $stmtThreats->execute($paramsThreats);
    $logs = $stmtThreats->fetchAll(PDO::FETCH_ASSOC);
    
    // Processar e classificar ameaças
    $threats = [];
    $threatStats = [
        'sql_injection' => 0,
        'xss' => 0,
        'command_injection' => 0,
        'path_traversal' => 0,
        'rce_php' => 0,
        'brute_force' => 0,
        'bot_scanner' => 0,
        'unknown' => 0
    ];
    
    $threatTimeline = []; // Por hora
    
    foreach ($logs as $log) {
        $reason = $log['reason'] ?? '';
        $requestUri = $log['request_uri'] ?? '';
        // Se não há reason, tentar detectar pelo request_uri
        if (empty($reason) && !empty($requestUri)) {
            $reason = $requestUri; // Usar URI para detecção se não houver reason
        }
        $threatType = detectThreatType($reason, $requestUri);
        $threatStats[$threatType]++;
        
        $hour = date('H', strtotime($log['created_at']));
        if (!isset($threatTimeline[$hour])) {
            $threatTimeline[$hour] = [
                'hour' => $hour,
                'total' => 0,
                'by_type' => []
            ];
        }
        $threatTimeline[$hour]['total']++;
        if (!isset($threatTimeline[$hour]['by_type'][$threatType])) {
            $threatTimeline[$hour]['by_type'][$threatType] = 0;
        }
        $threatTimeline[$hour]['by_type'][$threatType]++;
        
        // Extrair endpoint da URI
        $endpoint = '/';
        if (!empty($log['request_uri'])) {
            $parsed = parse_url($log['request_uri']);
            $endpoint = $parsed['path'] ?? '/';
        }
        
        $threats[] = [
            'id' => (int)$log['id'],
            'ip_address' => $log['ip_address'],
            'type' => $threatType,
            'endpoint' => $endpoint,
            'full_uri' => $log['request_uri'],
            'method' => $log['request_method'],
            'country' => $log['country_code'],
            'reason' => $log['reason'] ?? '',
            'user_agent' => $log['user_agent'],
            'timestamp' => $log['created_at'],
            'severity' => $threatType === 'rce_php' || $threatType === 'sql_injection' ? 'high' : 
                         ($threatType === 'xss' || $threatType === 'command_injection' ? 'medium' : 'low')
        ];
    }
    
    // Ordenar timeline por hora
    ksort($threatTimeline);
    $threatTimeline = array_values($threatTimeline);
    
    // Estatísticas por endpoint (mais atacados)
    $sqlEndpoints = "SELECT 
        SUBSTRING_INDEX(SUBSTRING_INDEX(request_uri, '?', 1), '#', 1) as endpoint,
        COUNT(*) as attack_count,
        COUNT(DISTINCT ip_address) as unique_ips
        FROM safenode_human_verification_logs 
        WHERE event_type = 'bot_blocked' 
        AND request_uri IS NOT NULL
        " . (!empty($whereTime) ? $whereTime : '') . "
        " . (!empty($siteFilter) ? $siteFilter : '') . "
        GROUP BY endpoint
        ORDER BY attack_count DESC
        LIMIT 10";
    
    $stmtEndpoints = $db->prepare($sqlEndpoints);
    if (!empty($params)) {
        $stmtEndpoints->execute($params);
    } else {
        $stmtEndpoints->execute();
    }
    $topEndpoints = $stmtEndpoints->fetchAll(PDO::FETCH_ASSOC);
    
    // Estatísticas por IP (mais agressivos)
    $sqlIPs = "SELECT 
        ip_address,
        country_code,
        COUNT(*) as attack_count,
        COUNT(DISTINCT request_uri) as endpoints_attacked,
        GROUP_CONCAT(DISTINCT COALESCE(reason, request_uri) SEPARATOR ', ') as attack_types
        FROM safenode_human_verification_logs 
        WHERE event_type = 'bot_blocked'
        " . (!empty($whereTime) ? $whereTime : '') . "
        " . (!empty($siteFilter) ? $siteFilter : '') . "
        GROUP BY ip_address, country_code
        ORDER BY attack_count DESC
        LIMIT 10";
    
    $stmtIPs = $db->prepare($sqlIPs);
    if (!empty($params)) {
        $stmtIPs->execute($params);
    } else {
        $stmtIPs->execute();
    }
    $topIPs = $stmtIPs->fetchAll(PDO::FETCH_ASSOC);
    
    ob_clean();
    echo json_encode([
        'success' => true,
        'timestamp' => time(),
        'timeframe' => $timeframe,
        'data' => [
            'threats' => $threats,
            'stats' => [
                'total_threats' => count($threats),
                'by_type' => $threatStats,
                'top_endpoints' => $topEndpoints,
                'top_ips' => $topIPs
            ],
            'timeline' => $threatTimeline
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    ob_end_flush();
    
} catch (PDOException $e) {
    ob_clean();
    error_log("SafeNode Threat Detection DB Error: " . $e->getMessage());
    error_log("SQL: " . ($sqlThreats ?? 'N/A'));
    error_log("Params: " . print_r($paramsThreats ?? [], true));
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao buscar dados',
        'debug' => (defined('DEBUG_MODE') && DEBUG_MODE) ? $e->getMessage() : null
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    ob_end_flush();
} catch (Exception $e) {
    ob_clean();
    error_log("SafeNode Threat Detection Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao processar dados',
        'debug' => (defined('DEBUG_MODE') && DEBUG_MODE) ? $e->getMessage() : null
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    ob_end_flush();
}

