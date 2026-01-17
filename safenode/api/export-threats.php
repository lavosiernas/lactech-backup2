<?php
/**
 * SafeNode - API de Exportação de Ameaças
 * Exporta ameaças detectadas em CSV ou JSON
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ob_start();

session_start();

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
    error_log("SafeNode Export Threats Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erro ao carregar configurações']);
    ob_end_flush();
    exit;
}

$db = getSafeNodeDatabase();
$currentSiteId = $_SESSION['view_site_id'] ?? 0;
$userId = $_SESSION['safenode_user_id'] ?? null;

if (!$db) {
    ob_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erro ao conectar ao banco de dados']);
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
$format = strtolower($_GET['format'] ?? 'csv');
$threatType = $_GET['threat_type'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$limit = min((int)($_GET['limit'] ?? 10000), 50000);

// Função para detectar tipo de ameaça (mesma do threat-detection.php)
if (!function_exists('detectThreatType')) {
    function detectThreatType($reason, $requestUri) {
        $reason = strtolower($reason ?? '');
        $uri = strtolower($requestUri ?? '');
        $data = $reason . ' ' . $uri;
        
        if (preg_match('/(union.*select|select.*from|insert.*into|delete.*from|drop.*table|exec|execute)/i', $data)) {
            return 'SQL Injection';
        }
        if (preg_match('/(<script|javascript:|onerror=|onclick=|eval\(|document\.cookie)/i', $data)) {
            return 'XSS';
        }
        if (preg_match('/(\.\.\/|\.\.\\\\|etc\/passwd|boot\.ini|windows\/system32)/i', $data)) {
            return 'Path Traversal';
        }
        if (preg_match('/(system\(|exec\(|shell_exec|passthru|proc_open)/i', $data)) {
            return 'Command Injection';
        }
        if (preg_match('/(phpinfo|eval\(|base64_decode|assert\(|preg_replace.*\/e)/i', $data)) {
            return 'RCE PHP';
        }
        if (preg_match('/(admin|login|wp-login|phpmyadmin).*(password|passwd|pwd)/i', $data)) {
            return 'Brute Force';
        }
        if (preg_match('/(scanner|bot|crawler|spider|curl|wget|python|java)/i', $data)) {
            return 'Bot/Scanner';
        }
        return 'Unknown';
    }
}

// Construir query
$where = [];
$params = [];

if ($currentSiteId > 0) {
    $where[] = "site_id = ?";
    $params[] = $currentSiteId;
} elseif ($userId) {
    $where[] = "(site_id IN (SELECT id FROM safenode_sites WHERE user_id = ?) OR api_key_id IN (SELECT id FROM safenode_hv_api_keys WHERE user_id = ?))";
    $params[] = $userId;
    $params[] = $userId;
}

$where[] = "event_type = 'bot_blocked'";

if ($dateFrom) {
    $where[] = "DATE(created_at) >= ?";
    $params[] = $dateFrom;
}

if ($dateTo) {
    $where[] = "DATE(created_at) <= ?";
    $params[] = $dateTo;
}

$whereClause = "WHERE " . implode(" AND ", $where);

try {
    // Buscar ameaças
    $sql = "SELECT 
        id, site_id, api_key_id, ip_address, event_type, request_uri, request_method, 
        user_agent, country_code, COALESCE(reason, request_uri) as reason_or_uri, created_at
        FROM safenode_human_verification_logs 
        $whereClause 
        ORDER BY created_at DESC 
        LIMIT ?";
    
    $params[] = $limit;
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $threats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Classificar ameaças
    $classifiedThreats = [];
    foreach ($threats as $threat) {
        $threatTypeDetected = detectThreatType($threat['reason_or_uri'], $threat['request_uri']);
        
        // Filtrar por tipo se especificado
        if ($threatType && strtolower($threatTypeDetected) !== strtolower($threatType)) {
            continue;
        }
        
        $classifiedThreats[] = [
            'original' => $threat,
            'threat_type' => $threatTypeDetected
        ];
    }
    
    if ($format === 'json') {
        ob_clean();
        header('Content-Type: application/json; charset=UTF-8');
        header('Content-Disposition: attachment; filename="safenode-threats-' . date('Y-m-d') . '.json"');
        
        $exportData = [];
        foreach ($classifiedThreats as $item) {
            $t = $item['original'];
            $exportData[] = [
                'id' => (int)$t['id'],
                'data_hora' => $t['created_at'],
                'ip' => $t['ip_address'],
                'tipo_ameaca' => $item['threat_type'],
                'endpoint' => $t['request_uri'],
                'metodo' => $t['request_method'],
                'pais' => $t['country_code'],
                'user_agent' => $t['user_agent'],
                'motivo' => $t['reason_or_uri']
            ];
        }
        
        echo json_encode([
            'success' => true,
            'export_date' => date('Y-m-d H:i:s'),
            'total_records' => count($exportData),
            'filters' => [
                'threat_type' => $threatType,
                'date_from' => $dateFrom,
                'date_to' => $dateTo
            ],
            'data' => $exportData
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        ob_end_flush();
        exit;
    } else {
        // CSV
        ob_clean();
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="safenode-threats-' . date('Y-m-d') . '.csv"');
        
        echo "\xEF\xBB\xBF";
        
        $headers = ['ID', 'Data/Hora', 'IP', 'Tipo de Ameaça', 'Endpoint', 'Método', 'País', 'User Agent', 'Motivo'];
        $output = fopen('php://output', 'w');
        fputcsv($output, $headers, ';');
        
        foreach ($classifiedThreats as $item) {
            $t = $item['original'];
            fputcsv($output, [
                $t['id'],
                $t['created_at'],
                $t['ip_address'],
                $item['threat_type'],
                $t['request_uri'],
                $t['request_method'],
                $t['country_code'] ?? '',
                $t['user_agent'] ?? '',
                $t['reason_or_uri']
            ], ';');
        }
        
        fclose($output);
        ob_end_flush();
        exit;
    }
    
} catch (PDOException $e) {
    ob_clean();
    error_log("SafeNode Export Threats DB Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao exportar dados',
        'debug' => (defined('DEBUG_MODE') && DEBUG_MODE) ? $e->getMessage() : null
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    ob_end_flush();
} catch (Exception $e) {
    ob_clean();
    error_log("SafeNode Export Threats Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao processar exportação',
        'debug' => (defined('DEBUG_MODE') && DEBUG_MODE) ? $e->getMessage() : null
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    ob_end_flush();
}

