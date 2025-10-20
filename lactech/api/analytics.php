<?php
// API para Dashboard Analítico

// Header ANTES de qualquer output
header('Content-Type: application/json');

error_reporting(0);
ini_set('display_errors', 0);

// Buffer de saída
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

$dbPath = __DIR__ . '/../includes/Database.class.php';
if (!file_exists($dbPath)) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Database class não encontrada']);
    exit;
}

require_once $dbPath;

// Limpar buffer
ob_clean();

function sendResponse($data = null, $error = null) {
    $response = ['success' => $error === null];
    if ($data !== null) {
        foreach ($data as $key => $value) {
            $response[$key] = $value;
        }
    }
    if ($error !== null) $response['error'] = $error;
    echo json_encode($response);
    exit;
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $farmId = 1;
    
    if ($method === 'GET') {
        $action = $_GET['action'] ?? '';
    
    switch ($action) {
            case 'get_dashboard':
                $dashboard = [];
                
                // Total de animais
                $stmt = $conn->prepare("SELECT COUNT(*) as total FROM animals WHERE is_active = 1 AND farm_id = ?");
                $stmt->execute([$farmId]);
                $dashboard['total_animals'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                
                // Produção de leite hoje
                $stmt = $conn->prepare("
                    SELECT COALESCE(SUM(total_volume), 0) as total 
                    FROM volume_records 
                    WHERE record_date = CURDATE() AND farm_id = ?
                ");
                $stmt->execute([$farmId]);
                $dashboard['milk_today'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                
                // Total de prenhes ativas
                $stmt = $conn->prepare("SELECT COUNT(*) as total FROM pregnancy_controls WHERE farm_id = ?");
                $stmt->execute([$farmId]);
                $dashboard['total_pregnant'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                
                // Animais saudáveis
                $stmt = $conn->prepare("
                    SELECT COUNT(*) as total 
                    FROM animals 
                    WHERE health_status = 'saudavel' AND is_active = 1 AND farm_id = ?
                ");
                $stmt->execute([$farmId]);
                $dashboard['healthy_animals'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                
                // Alertas pendentes
                $stmt = $conn->prepare("
                    SELECT COUNT(*) as total 
                    FROM health_alerts 
                    WHERE is_resolved = 0 AND farm_id = ?
                ");
                $stmt->execute([$farmId]);
                $dashboard['pending_alerts'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                
                // Distribuição por status
                $stmt = $conn->prepare("
                    SELECT status, COUNT(*) as count 
                    FROM animals 
                    WHERE is_active = 1 AND farm_id = ?
                    GROUP BY status
                    ORDER BY count DESC
                ");
                $stmt->execute([$farmId]);
                $dashboard['status_distribution'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Distribuição de saúde
                $stmt = $conn->prepare("
                    SELECT health_status, COUNT(*) as count 
                    FROM animals 
                    WHERE is_active = 1 AND farm_id = ?
                    GROUP BY health_status
                    ORDER BY count DESC
                ");
                $stmt->execute([$farmId]);
                $dashboard['health_distribution'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Distribuição reprodutiva
                $stmt = $conn->prepare("
                    SELECT reproductive_status, COUNT(*) as count 
                    FROM animals 
                    WHERE is_active = 1 AND gender = 'femea' AND farm_id = ?
                    GROUP BY reproductive_status
                    ORDER BY count DESC
                ");
                $stmt->execute([$farmId]);
                $dashboard['reproductive_distribution'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Produção dos últimos 7 dias
                $stmt = $conn->prepare("
                    SELECT record_date, SUM(total_volume) as total_volume
                    FROM volume_records
                    WHERE record_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND farm_id = ?
                    GROUP BY record_date
                    ORDER BY record_date DESC
                    LIMIT 7
                ");
                $stmt->execute([$farmId]);
                $dashboard['production_7days'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Atividades recentes (últimas ações em cada tabela)
                $activities = [];
                
                // Animais criados recentemente
                $stmt = $conn->prepare("
                    SELECT 'animal' as type, CONCAT('Animal ', animal_number, ' adicionado') as description, created_at
                    FROM animals 
                    WHERE farm_id = ?
                    ORDER BY created_at DESC
                    LIMIT 3
                ");
                $stmt->execute([$farmId]);
                $activities = array_merge($activities, $stmt->fetchAll(PDO::FETCH_ASSOC));
                
                // Registros de saúde recentes
                $stmt = $conn->prepare("
                    SELECT 'health' as type, CONCAT(record_type, ' - ', LEFT(description, 50)) as description, created_at
                    FROM health_records 
                    WHERE farm_id = ?
                    ORDER BY created_at DESC
                    LIMIT 3
                ");
                $stmt->execute([$farmId]);
                $activities = array_merge($activities, $stmt->fetchAll(PDO::FETCH_ASSOC));
                
                // Inseminações recentes
                $stmt = $conn->prepare("
                    SELECT 'reproduction' as type, CONCAT('Inseminação registrada') as description, created_at
                    FROM inseminations 
                    WHERE farm_id = ?
                    ORDER BY created_at DESC
                    LIMIT 2
                ");
                $stmt->execute([$farmId]);
                $activities = array_merge($activities, $stmt->fetchAll(PDO::FETCH_ASSOC));
                
                // Ordenar atividades por data
                usort($activities, function($a, $b) {
                    return strtotime($b['created_at']) - strtotime($a['created_at']);
                });
                
                $dashboard['recent_activities'] = array_slice($activities, 0, 10);
                
                sendResponse($dashboard);
                break;
            
        default:
                sendResponse(null, 'Ação não especificada');
        }
    }
    
} catch (Exception $e) {
    sendResponse(null, $e->getMessage());
}
?>
