<?php
/**
 * API: Central de Ações - Lactech
 * Sistema completo de gestão de ações pendentes e alertas
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

error_reporting(0);
ini_set('display_errors', 0);

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Acesso negado']);
    exit;
}

require_once __DIR__ . '/../includes/Database.class.php';

function sendResponse($data = null, $error = null, $statusCode = 200) {
    http_response_code($statusCode);
    $response = ['success' => $error === null];
    if ($data !== null) $response['data'] = $data;
    if ($error !== null) $response['error'] = $error;
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    $farm_id = $_SESSION['farm_id'] ?? 1;
    $user_id = $_SESSION['user_id'];
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    
    switch ($action) {
        // ==========================================
        // DASHBOARD - Resumo de ações
        // ==========================================
        case 'dashboard':
            $summary = [
                'urgent' => 0,
                'pending' => 0,
                'monitoring' => 0,
                'total' => 0
            ];
            
            // Usar view se existir, senão calcular manualmente
            try {
                $stmt = $conn->prepare("SELECT * FROM v_pending_actions_summary");
                $stmt->execute();
                $actions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                // Se view não existir, calcular manualmente
                $actions = [];
                
                // Cio previsto (7 dias)
                $stmt = $conn->prepare("
                    SELECT COUNT(*) as count
                    FROM heat_cycles hc
                    INNER JOIN animals a ON hc.animal_id = a.id
                    WHERE a.is_active = 1 AND a.farm_id = ?
                    AND hc.heat_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                ");
                $stmt->execute([$farm_id]);
                $heatCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
                if ($heatCount > 0) {
                    $actions[] = [
                        'action_type' => 'heat_expected',
                        'count' => $heatCount,
                        'description' => 'Cio previsto (7 dias)'
                    ];
                }
                
                // Partos próximos (30 dias)
                $stmt = $conn->prepare("
                    SELECT COUNT(*) as count
                    FROM pregnancy_controls pc
                    INNER JOIN animals a ON pc.animal_id = a.id
                    WHERE a.is_active = 1 AND a.farm_id = ?
                    AND pc.expected_birth BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                ");
                $stmt->execute([$farm_id]);
                $calvingCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
                if ($calvingCount > 0) {
                    $actions[] = [
                        'action_type' => 'calving_soon',
                        'count' => $calvingCount,
                        'description' => 'Partos próximos (30 dias)'
                    ];
                }
                
                // BCS baixo
                $stmt = $conn->prepare("
                    SELECT COUNT(DISTINCT bcs.animal_id) as count
                    FROM body_condition_scores bcs
                    INNER JOIN (
                        SELECT animal_id, MAX(evaluation_date) as max_date
                        FROM body_condition_scores
                        GROUP BY animal_id
                    ) latest ON bcs.animal_id = latest.animal_id AND bcs.evaluation_date = latest.max_date
                    INNER JOIN animals a ON bcs.animal_id = a.id
                    WHERE bcs.score < 2.5 AND a.is_active = 1 AND a.farm_id = ?
                ");
                $stmt->execute([$farm_id]);
                $bcsCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
                if ($bcsCount > 0) {
                    $actions[] = [
                        'action_type' => 'low_bcs',
                        'count' => $bcsCount,
                        'description' => 'BCS baixo (< 2.5)'
                    ];
                }
            }
            
            // Vacinações pendentes
            $stmt = $conn->prepare("
                SELECT COUNT(DISTINCT a.id) as count
                FROM health_alerts ha
                INNER JOIN animals a ON ha.animal_id = a.id
                WHERE a.is_active = 1 AND a.farm_id = ?
                AND ha.alert_type = 'vacina'
                AND ha.is_resolved = 0
                AND ha.alert_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY)
            ");
            $stmt->execute([$farm_id]);
            $vaccinationCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
            if ($vaccinationCount > 0) {
                $actions[] = [
                    'action_type' => 'vaccination',
                    'count' => $vaccinationCount,
                    'description' => 'Vacinações pendentes'
                ];
            }
            
            // Vermifugações pendentes
            $stmt = $conn->prepare("
                SELECT COUNT(DISTINCT a.id) as count
                FROM health_alerts ha
                INNER JOIN animals a ON ha.animal_id = a.id
                WHERE a.is_active = 1 AND a.farm_id = ?
                AND ha.alert_type = 'vermifugo'
                AND ha.is_resolved = 0
                AND ha.alert_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY)
            ");
            $stmt->execute([$farm_id]);
            $dewormingCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
            if ($dewormingCount > 0) {
                $actions[] = [
                    'action_type' => 'deworming',
                    'count' => $dewormingCount,
                    'description' => 'Vermifugações pendentes'
                ];
            }
            
            // Classificar por prioridade
            foreach ($actions as $act) {
                $summary['total'] += $act['count'];
                if (in_array($act['action_type'], ['calving_soon', 'low_bcs'])) {
                    $summary['urgent'] += $act['count'];
                } elseif (in_array($act['action_type'], ['vaccination', 'deworming'])) {
                    $summary['pending'] += $act['count'];
                } else {
                    $summary['monitoring'] += $act['count'];
                }
            }
            
            sendResponse([
                'summary' => $summary,
                'actions' => $actions
            ]);
            break;
            
        // ==========================================
        // LISTAR AÇÕES PRIORITÁRIAS
        // ==========================================
        case 'priority_actions':
            $priorityActions = [];
            
            // Vacinações
            $stmt = $conn->prepare("
                SELECT 
                    a.id as animal_id,
                    a.animal_number,
                    a.name as animal_name,
                    ha.alert_date,
                    ha.alert_message,
                    DATEDIFF(ha.alert_date, CURDATE()) as days_until
                FROM health_alerts ha
                INNER JOIN animals a ON ha.animal_id = a.id
                WHERE a.is_active = 1 AND a.farm_id = ?
                AND ha.alert_type = 'vacina'
                AND ha.is_resolved = 0
                AND ha.alert_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY)
                ORDER BY ha.alert_date ASC
                LIMIT 20
            ");
            $stmt->execute([$farm_id]);
            $vaccinations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($vaccinations) > 0) {
                $priorityActions[] = [
                    'type' => 'vaccination',
                    'title' => 'Vacinação Aftosa',
                    'count' => count($vaccinations),
                    'message' => count($vaccinations) . ' animais - Vence em 90 dias',
                    'priority' => 'high',
                    'animals' => array_slice($vaccinations, 0, 5)
                ];
            }
            
            // Vermifugações
            $stmt = $conn->prepare("
                SELECT 
                    a.id as animal_id,
                    a.animal_number,
                    a.name as animal_name,
                    ha.alert_date,
                    ha.alert_message,
                    DATEDIFF(ha.alert_date, CURDATE()) as days_until
                FROM health_alerts ha
                INNER JOIN animals a ON ha.animal_id = a.id
                WHERE a.is_active = 1 AND a.farm_id = ?
                AND ha.alert_type = 'vermifugo'
                AND ha.is_resolved = 0
                AND ha.alert_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY)
                ORDER BY ha.alert_date ASC
                LIMIT 20
            ");
            $stmt->execute([$farm_id]);
            $dewormings = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($dewormings) > 0) {
                $priorityActions[] = [
                    'type' => 'deworming',
                    'title' => 'Vermifugação',
                    'count' => count($dewormings),
                    'message' => count($dewormings) . ' animais - Vence em 90 dias',
                    'priority' => 'medium',
                    'animals' => array_slice($dewormings, 0, 5)
                ];
            }
            
            // Partos esperados
            $stmt = $conn->prepare("
                SELECT 
                    a.id as animal_id,
                    a.animal_number,
                    a.name as animal_name,
                    pc.expected_birth,
                    DATEDIFF(pc.expected_birth, CURDATE()) as days_until
                FROM pregnancy_controls pc
                INNER JOIN animals a ON pc.animal_id = a.id
                WHERE a.is_active = 1 AND a.farm_id = ?
                AND pc.expected_birth BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 280 DAY)
                ORDER BY pc.expected_birth ASC
                LIMIT 20
            ");
            $stmt->execute([$farm_id]);
            $calvings = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($calvings) > 0) {
                $priorityActions[] = [
                    'type' => 'calving',
                    'title' => 'Partos Esperados',
                    'count' => count($calvings),
                    'message' => count($calvings) . ' animais - Próximos 280 dias',
                    'priority' => 'high',
                    'animals' => array_slice($calvings, 0, 5)
                ];
            }
            
            sendResponse($priorityActions);
            break;
            
        // ==========================================
        // LISTAR NOTIFICAÇÕES
        // ==========================================
        case 'notifications':
            $limit = (int)($_GET['limit'] ?? 50);
            $offset = (int)($_GET['offset'] ?? 0);
            
            $stmt = $conn->prepare("
                SELECT 
                    n.*,
                    u.name as user_name
                FROM notifications n
                LEFT JOIN users u ON n.user_id = u.id
                WHERE (n.user_id = ? OR n.user_id IS NULL)
                AND n.farm_id = ?
                AND (n.expires_at IS NULL OR n.expires_at > NOW())
                ORDER BY n.created_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$user_id, $farm_id, $limit, $offset]);
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Contar não lidas
            $stmt = $conn->prepare("
                SELECT COUNT(*) as count
                FROM notifications
                WHERE (user_id = ? OR user_id IS NULL)
                AND farm_id = ?
                AND is_read = 0
                AND (expires_at IS NULL OR expires_at > NOW())
            ");
            $stmt->execute([$user_id, $farm_id]);
            $unreadCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
            
            sendResponse([
                'notifications' => $notifications,
                'unread_count' => $unreadCount
            ]);
            break;
            
        // ==========================================
        // MARCAR NOTIFICAÇÃO COMO LIDA
        // ==========================================
        case 'mark_notification_read':
            $id = $input['id'] ?? $_GET['id'] ?? null;
            if (!$id) {
                sendResponse(null, 'ID não fornecido', 400);
            }
            
            $stmt = $conn->prepare("
                UPDATE notifications 
                SET is_read = 1, read_date = NOW()
                WHERE id = ? AND (user_id = ? OR user_id IS NULL) AND farm_id = ?
            ");
            $stmt->execute([$id, $user_id, $farm_id]);
            
            sendResponse(['id' => $id, 'message' => 'Notificação marcada como lida']);
            break;
            
        // ==========================================
        // MARCAR TODAS COMO LIDAS
        // ==========================================
        case 'mark_all_read':
            $stmt = $conn->prepare("
                UPDATE notifications 
                SET is_read = 1, read_date = NOW()
                WHERE (user_id = ? OR user_id IS NULL) AND farm_id = ? AND is_read = 0
            ");
            $stmt->execute([$user_id, $farm_id]);
            
            sendResponse(['message' => 'Todas as notificações foram marcadas como lidas']);
            break;
            
        // ==========================================
        // RESOLVER ALERTA DE SAÚDE
        // ==========================================
        case 'resolve_health_alert':
            $id = $input['id'] ?? $_GET['id'] ?? null;
            if (!$id) {
                sendResponse(null, 'ID não fornecido', 400);
            }
            
            $stmt = $conn->prepare("
                UPDATE health_alerts 
                SET is_resolved = 1, resolved_date = CURDATE()
                WHERE id = ? AND farm_id = ?
            ");
            $stmt->execute([$id, $farm_id]);
            
            sendResponse(['id' => $id, 'message' => 'Alerta resolvido']);
            break;
            
        // ==========================================
        // DETALHES DE AÇÃO
        // ==========================================
        case 'action_details':
            $type = $_GET['type'] ?? $input['type'] ?? null;
            if (!$type) {
                sendResponse(null, 'Tipo de ação não fornecido', 400);
            }
            
            $details = [];
            
            switch ($type) {
                case 'vaccination':
                    $stmt = $conn->prepare("
                        SELECT 
                            a.id,
                            a.animal_number,
                            a.name,
                            a.breed,
                            ha.alert_date,
                            ha.alert_message,
                            DATEDIFF(ha.alert_date, CURDATE()) as days_until
                        FROM health_alerts ha
                        INNER JOIN animals a ON ha.animal_id = a.id
                        WHERE a.is_active = 1 AND a.farm_id = ?
                        AND ha.alert_type = 'vacina'
                        AND ha.is_resolved = 0
                        ORDER BY ha.alert_date ASC
                    ");
                    $stmt->execute([$farm_id]);
                    $details = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    break;
                    
                case 'deworming':
                    $stmt = $conn->prepare("
                        SELECT 
                            a.id,
                            a.animal_number,
                            a.name,
                            a.breed,
                            ha.alert_date,
                            ha.alert_message,
                            DATEDIFF(ha.alert_date, CURDATE()) as days_until
                        FROM health_alerts ha
                        INNER JOIN animals a ON ha.animal_id = a.id
                        WHERE a.is_active = 1 AND a.farm_id = ?
                        AND ha.alert_type = 'vermifugo'
                        AND ha.is_resolved = 0
                        ORDER BY ha.alert_date ASC
                    ");
                    $stmt->execute([$farm_id]);
                    $details = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    break;
                    
                case 'calving':
                    $stmt = $conn->prepare("
                        SELECT 
                            a.id,
                            a.animal_number,
                            a.name,
                            a.breed,
                            pc.expected_birth,
                            DATEDIFF(pc.expected_birth, CURDATE()) as days_until,
                            pc.pregnancy_stage
                        FROM pregnancy_controls pc
                        INNER JOIN animals a ON pc.animal_id = a.id
                        WHERE a.is_active = 1 AND a.farm_id = ?
                        AND pc.expected_birth >= CURDATE()
                        ORDER BY pc.expected_birth ASC
                    ");
                    $stmt->execute([$farm_id]);
                    $details = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    break;
                    
                case 'heat_expected':
                    $stmt = $conn->prepare("
                        SELECT 
                            a.id,
                            a.animal_number,
                            a.name,
                            a.breed,
                            hc.heat_date,
                            DATEDIFF(hc.heat_date, CURDATE()) as days_until,
                            hc.heat_intensity
                        FROM heat_cycles hc
                        INNER JOIN animals a ON hc.animal_id = a.id
                        WHERE a.is_active = 1 AND a.farm_id = ?
                        AND hc.heat_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                        ORDER BY hc.heat_date ASC
                    ");
                    $stmt->execute([$farm_id]);
                    $details = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    break;
                    
                case 'low_bcs':
                    $stmt = $conn->prepare("
                        SELECT 
                            a.id,
                            a.animal_number,
                            a.name,
                            a.breed,
                            bcs.score,
                            bcs.evaluation_date
                        FROM body_condition_scores bcs
                        INNER JOIN (
                            SELECT animal_id, MAX(evaluation_date) as max_date
                            FROM body_condition_scores
                            GROUP BY animal_id
                        ) latest ON bcs.animal_id = latest.animal_id AND bcs.evaluation_date = latest.max_date
                        INNER JOIN animals a ON bcs.animal_id = a.id
                        WHERE bcs.score < 2.5 AND a.is_active = 1 AND a.farm_id = ?
                        ORDER BY bcs.score ASC
                    ");
                    $stmt->execute([$farm_id]);
                    $details = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    break;
            }
            
            sendResponse($details);
            break;
            
        default:
            sendResponse(null, 'Ação não encontrada', 404);
    }
    
} catch (Exception $e) {
    sendResponse(null, 'Erro interno: ' . $e->getMessage(), 500);
}
?>

