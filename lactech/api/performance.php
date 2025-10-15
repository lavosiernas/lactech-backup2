<?php
// API para otimização de performance do banco de dados
error_reporting(0);
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header('Cache-Control: public, max-age=300'); // Cache por 5 minutos

$dbPath = __DIR__ . '/../includes/Database.class.php';
if (!file_exists($dbPath)) {
    echo json_encode(['success' => false, 'error' => 'Database class não encontrada']);
    exit;
}

require_once $dbPath;

function sendResponse($data = null, $error = null, $status = 200) {
    http_response_code($status);
    $response = ['success' => $error === null];
    if ($data !== null) $response['data'] = $data;
    if ($error !== null) $response['error'] = $error;
    echo json_encode($response);
    exit;
}

try {
    $db = Database::getInstance();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    
    if ($method === 'GET') {
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'dashboard_stats':
                // Cache dashboard stats
                $cacheKey = 'dashboard_stats_' . date('Y-m-d-H');
                $cached = apcu_fetch($cacheKey);
                
                if ($cached !== false) {
                    sendResponse($cached);
                }
                
                $stats = [
                    'total_animals' => $db->query("SELECT COUNT(*) as count FROM animals WHERE is_active = 1")->fetch()['count'],
                    'total_production' => $db->query("SELECT SUM(volume) as total FROM milk_production WHERE production_date = CURDATE()")->fetch()['total'] ?? 0,
                    'active_pregnancies' => $db->query("SELECT COUNT(*) as count FROM pregnancy_controls WHERE expected_birth >= CURDATE()")->fetch()['count'],
                    'health_alerts' => $db->query("SELECT COUNT(*) as count FROM health_alerts WHERE is_resolved = 0")->fetch()['count']
                ];
                
                // Cache for 1 hour
                apcu_store($cacheKey, $stats, 3600);
                sendResponse($stats);
                break;
                
            case 'recent_activities':
                // Optimized query with LIMIT
                $activities = $db->query("
                    SELECT 
                        'production' as type,
                        CONCAT('Produção: ', FORMAT(volume, 2), 'L') as description,
                        production_date as date,
                        recorded_by
                    FROM milk_production 
                    WHERE production_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                    ORDER BY production_date DESC, created_at DESC
                    LIMIT 10
                ")->fetchAll();
                
                sendResponse($activities);
                break;
                
            case 'animals_summary':
                // Cached animals summary
                $cacheKey = 'animals_summary_' . date('Y-m-d');
                $cached = apcu_fetch($cacheKey);
                
                if ($cached !== false) {
                    sendResponse($cached);
                }
                
                $summary = $db->query("
                    SELECT 
                        status,
                        COUNT(*) as count
                    FROM animals 
                    WHERE is_active = 1 
                    GROUP BY status
                ")->fetchAll();
                
                // Cache for 24 hours
                apcu_store($cacheKey, $summary, 86400);
                sendResponse($summary);
                break;
                
            case 'production_trends':
                // Optimized production trends
                $trends = $db->query("
                    SELECT 
                        DATE(production_date) as date,
                        SUM(volume) as total_volume,
                        COUNT(DISTINCT animal_id) as animals_count,
                        AVG(volume) as avg_per_animal
                    FROM milk_production 
                    WHERE production_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                    GROUP BY DATE(production_date)
                    ORDER BY date DESC
                ")->fetchAll();
                
                sendResponse($trends);
                break;
                
            default:
                sendResponse(null, 'Ação não especificada');
        }
    }
    
} catch (Exception $e) {
    error_log("Performance API Error: " . $e->getMessage());
    sendResponse(null, 'Erro interno do servidor', 500);
}
?>
