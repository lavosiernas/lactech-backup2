<?php
/**
 * SafeNode - API de Exportação de Logs
 * Exporta logs em CSV ou JSON
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
    error_log("SafeNode Export Logs Error: " . $e->getMessage());
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
$format = strtolower($_GET['format'] ?? 'csv'); // csv ou json
$eventType = $_GET['event_type'] ?? '';
$ipAddress = $_GET['ip'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$limit = min((int)($_GET['limit'] ?? 10000), 50000); // Máximo 50k registros

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

if ($eventType) {
    $actionMap = [
        'humano_validado' => ['human_validated', 'access_allowed'],
        'bot_bloqueado' => ['bot_blocked'],
        'acesso_permitido' => ['access_allowed']
    ];
    
    if (isset($actionMap[$eventType])) {
        $placeholders = implode(',', array_fill(0, count($actionMap[$eventType]), '?'));
        $where[] = "event_type IN ($placeholders)";
        $params = array_merge($params, $actionMap[$eventType]);
    }
}

if ($ipAddress) {
    $where[] = "ip_address LIKE ?";
    $params[] = "%$ipAddress%";
}

if ($dateFrom) {
    $where[] = "DATE(created_at) >= ?";
    $params[] = $dateFrom;
}

if ($dateTo) {
    $where[] = "DATE(created_at) <= ?";
    $params[] = $dateTo;
}

$whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

try {
    // Buscar logs
    $sql = "SELECT 
        id, site_id, api_key_id, ip_address, event_type, request_uri, request_method, 
        user_agent, referer, country_code, reason, created_at
        FROM safenode_human_verification_logs 
        $whereClause 
        ORDER BY created_at DESC 
        LIMIT ?";
    
    $params[] = $limit;
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Mapear tipos de evento para labels
    $eventTypeLabels = [
        'bot_blocked' => 'Bot Bloqueado',
        'human_validated' => 'Humano Validado',
        'access_allowed' => 'Acesso Permitido',
        'challenge_shown' => 'Desafio Mostrado'
    ];
    
    if ($format === 'json') {
        ob_clean();
        header('Content-Type: application/json; charset=UTF-8');
        header('Content-Disposition: attachment; filename="safenode-logs-' . date('Y-m-d') . '.json"');
        
        $exportData = [];
        foreach ($logs as $log) {
            $exportData[] = [
                'id' => (int)$log['id'],
                'data_hora' => $log['created_at'],
                'ip' => $log['ip_address'],
                'tipo_evento' => $eventTypeLabels[$log['event_type']] ?? $log['event_type'],
                'endpoint' => $log['request_uri'],
                'metodo' => $log['request_method'],
                'pais' => $log['country_code'],
                'user_agent' => $log['user_agent'],
                'referer' => $log['referer'],
                'motivo' => $log['reason']
            ];
        }
        
        echo json_encode([
            'success' => true,
            'export_date' => date('Y-m-d H:i:s'),
            'total_records' => count($exportData),
            'filters' => [
                'event_type' => $eventType,
                'ip' => $ipAddress,
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
        header('Content-Disposition: attachment; filename="safenode-logs-' . date('Y-m-d') . '.csv"');
        
        // BOM para UTF-8 (Excel)
        echo "\xEF\xBB\xBF";
        
        // Cabeçalhos
        $headers = ['ID', 'Data/Hora', 'IP', 'Tipo de Evento', 'Endpoint', 'Método', 'País', 'User Agent', 'Referer', 'Motivo'];
        $output = fopen('php://output', 'w');
        fputcsv($output, $headers, ';');
        
        // Dados
        foreach ($logs as $log) {
            fputcsv($output, [
                $log['id'],
                $log['created_at'],
                $log['ip_address'],
                $eventTypeLabels[$log['event_type']] ?? $log['event_type'],
                $log['request_uri'],
                $log['request_method'],
                $log['country_code'] ?? '',
                $log['user_agent'] ?? '',
                $log['referer'] ?? '',
                $log['reason'] ?? ''
            ], ';');
        }
        
        fclose($output);
        ob_end_flush();
        exit;
    }
    
} catch (PDOException $e) {
    ob_clean();
    error_log("SafeNode Export Logs DB Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao exportar dados',
        'debug' => (defined('DEBUG_MODE') && DEBUG_MODE) ? $e->getMessage() : null
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    ob_end_flush();
} catch (Exception $e) {
    ob_clean();
    error_log("SafeNode Export Logs Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao processar exportação',
        'debug' => (defined('DEBUG_MODE') && DEBUG_MODE) ? $e->getMessage() : null
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    ob_end_flush();
}

