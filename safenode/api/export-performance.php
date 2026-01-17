<?php
/**
 * SafeNode - API de Exportação de Performance
 * Exporta dados de performance em CSV ou JSON
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
    error_log("SafeNode Export Performance Error: " . $e->getMessage());
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
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$limit = min((int)($_GET['limit'] ?? 10000), 50000);

// Verificar se tabela existe
$tableExists = false;
try {
    $stmt = $db->query("SELECT 1 FROM safenode_performance_logs LIMIT 1");
    $tableExists = true;
} catch (PDOException $e) {
    $tableExists = false;
}

if (!$tableExists) {
    ob_clean();
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Tabela de performance não existe. Execute o SQL da Fase 2.']);
    ob_end_flush();
    exit;
}

// Construir query
$where = [];
$params = [];

if ($currentSiteId > 0) {
    $where[] = "site_id = ?";
    $params[] = $currentSiteId;
} elseif ($userId) {
    $where[] = "site_id IN (SELECT id FROM safenode_sites WHERE user_id = ?)";
    $params[] = $userId;
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
    // Buscar dados de performance
    $sql = "SELECT 
        id, site_id, endpoint, response_time, memory_usage, request_method, created_at
        FROM safenode_performance_logs 
        $whereClause 
        ORDER BY created_at DESC 
        LIMIT ?";
    
    $params[] = $limit;
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $performanceData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($format === 'json') {
        ob_clean();
        header('Content-Type: application/json; charset=UTF-8');
        header('Content-Disposition: attachment; filename="safenode-performance-' . date('Y-m-d') . '.json"');
        
        $exportData = [];
        foreach ($performanceData as $perf) {
            $exportData[] = [
                'id' => (int)$perf['id'],
                'data_hora' => $perf['created_at'],
                'endpoint' => $perf['endpoint'],
                'tempo_resposta_ms' => (int)$perf['response_time'],
                'uso_memoria_mb' => round((int)$perf['memory_usage'] / 1048576, 2),
                'metodo' => $perf['request_method']
            ];
        }
        
        echo json_encode([
            'success' => true,
            'export_date' => date('Y-m-d H:i:s'),
            'total_records' => count($exportData),
            'filters' => [
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
        header('Content-Disposition: attachment; filename="safenode-performance-' . date('Y-m-d') . '.csv"');
        
        echo "\xEF\xBB\xBF";
        
        $headers = ['ID', 'Data/Hora', 'Endpoint', 'Tempo Resposta (ms)', 'Uso Memória (MB)', 'Método'];
        $output = fopen('php://output', 'w');
        fputcsv($output, $headers, ';');
        
        foreach ($performanceData as $perf) {
            fputcsv($output, [
                $perf['id'],
                $perf['created_at'],
                $perf['endpoint'],
                $perf['response_time'],
                round((int)$perf['memory_usage'] / 1048576, 2),
                $perf['request_method']
            ], ';');
        }
        
        fclose($output);
        ob_end_flush();
        exit;
    }
    
} catch (PDOException $e) {
    ob_clean();
    error_log("SafeNode Export Performance DB Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao exportar dados',
        'debug' => (defined('DEBUG_MODE') && DEBUG_MODE) ? $e->getMessage() : null
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    ob_end_flush();
} catch (Exception $e) {
    ob_clean();
    error_log("SafeNode Export Performance Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao processar exportação',
        'debug' => (defined('DEBUG_MODE') && DEBUG_MODE) ? $e->getMessage() : null
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    ob_end_flush();
}

