<?php
/**
 * API: Action Lists
 * Dashboard de ações pendentes e listas inteligentes
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../includes/Database.class.php';

function sendResponse($data = null, $error = null, $status = 200) {
    http_response_code($status);
    echo json_encode([
        'success' => $error === null,
        'data' => $data,
        'error' => $error
    ]);
    exit;
}

try {
    $db = Database::getInstance();
    $method = $_SERVER['REQUEST_METHOD'];
    
    // GET - Listar ações
    if ($method === 'GET') {
        $action = $_GET['action'] ?? 'dashboard';
        
        switch ($action) {
            case 'dashboard':
                // Dashboard completo de ações pendentes
                $stmt = $db->query("
                    SELECT * FROM v_pending_actions_summary
                ");
                $summary = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Buscar detalhes de cada tipo
                $details = [];
                
                // 1. Cio esperado
                $stmt = $db->query("
                    SELECT 
                        a.id,
                        a.animal_number,
                        a.name,
                        a.breed,
                        ap.predicted_date,
                        ap.confidence_score,
                        DATEDIFF(ap.predicted_date, CURDATE()) as days_until
                    FROM ai_predictions ap
                    INNER JOIN animals a ON ap.animal_id = a.id
                    WHERE ap.prediction_type = 'heat'
                      AND ap.predicted_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                      AND ap.actual_date IS NULL
                      AND a.is_active = 1
                    ORDER BY ap.predicted_date ASC
                ");
                $details['heat_expected'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // 2. Partos próximos
                $stmt = $db->query("
                    SELECT 
                        a.id,
                        a.animal_number,
                        a.name,
                        a.breed,
                        pc.expected_birth,
                        pc.pregnancy_stage,
                        DATEDIFF(pc.expected_birth, CURDATE()) as days_until,
                        CASE 
                            WHEN DATEDIFF(pc.expected_birth, CURDATE()) <= 7 THEN 'urgent'
                            WHEN DATEDIFF(pc.expected_birth, CURDATE()) <= 15 THEN 'high'
                            ELSE 'medium'
                        END as priority
                    FROM pregnancy_controls pc
                    INNER JOIN animals a ON pc.animal_id = a.id
                    WHERE pc.expected_birth BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                      AND a.is_active = 1
                    ORDER BY pc.expected_birth ASC
                ");
                $details['calving_soon'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // 3. BCS baixo
                $stmt = $db->query("
                    SELECT 
                        a.id,
                        a.animal_number,
                        a.name,
                        a.breed,
                        a.status,
                        bcs.score,
                        bcs.evaluation_date,
                        DATEDIFF(CURDATE(), bcs.evaluation_date) as days_since_eval,
                        CASE 
                            WHEN bcs.score < 2.0 THEN 'critical'
                            WHEN bcs.score < 2.5 THEN 'high'
                            ELSE 'medium'
                        END as priority
                    FROM animals a
                    INNER JOIN body_condition_scores bcs ON a.id = bcs.animal_id
                    INNER JOIN (
                        SELECT animal_id, MAX(evaluation_date) as max_date
                        FROM body_condition_scores
                        GROUP BY animal_id
                    ) latest ON bcs.animal_id = latest.animal_id 
                        AND bcs.evaluation_date = latest.max_date
                    WHERE bcs.score < 2.5 AND a.is_active = 1
                    ORDER BY bcs.score ASC
                ");
                $details['low_bcs'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // 4. Tratamentos pendentes
                $stmt = $db->query("
                    SELECT 
                        a.id,
                        a.animal_number,
                        a.name,
                        a.breed,
                        hr.record_type,
                        hr.medication,
                        hr.next_date,
                        DATEDIFF(hr.next_date, CURDATE()) as days_until,
                        CASE 
                            WHEN hr.next_date < CURDATE() THEN 'overdue'
                            WHEN DATEDIFF(hr.next_date, CURDATE()) <= 2 THEN 'urgent'
                            WHEN DATEDIFF(hr.next_date, CURDATE()) <= 7 THEN 'high'
                            ELSE 'medium'
                        END as priority
                    FROM health_records hr
                    INNER JOIN animals a ON hr.animal_id = a.id
                    WHERE hr.next_date IS NOT NULL
                      AND hr.next_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                      AND a.is_active = 1
                    ORDER BY hr.next_date ASC
                ");
                $details['medication_due'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // 5. Animais sem avaliação de BCS recente (>30 dias)
                $stmt = $db->query("
                    SELECT 
                        a.id,
                        a.animal_number,
                        a.name,
                        a.breed,
                        a.status,
                        MAX(bcs.evaluation_date) as last_evaluation,
                        DATEDIFF(CURDATE(), MAX(bcs.evaluation_date)) as days_since_eval
                    FROM animals a
                    LEFT JOIN body_condition_scores bcs ON a.id = bcs.animal_id
                    WHERE a.is_active = 1
                      AND a.status IN ('Lactante', 'Vaca', 'Novilha')
                    GROUP BY a.id
                    HAVING last_evaluation IS NULL OR days_since_eval > 30
                    ORDER BY days_since_eval DESC NULLS FIRST
                    LIMIT 50
                ");
                $details['bcs_check_needed'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                sendResponse([
                    'summary' => $summary,
                    'details' => $details
                ]);
                break;
                
            case 'by_priority':
                $priority = $_GET['priority'] ?? 'high';
                
                $stmt = $db->query("
                    SELECT 
                        alc.*,
                        a.animal_number,
                        a.name as animal_name,
                        a.breed,
                        a.status
                    FROM action_lists_cache alc
                    LEFT JOIN animals a ON alc.animal_id = a.id
                    WHERE alc.priority = ? AND alc.is_completed = 0
                    ORDER BY alc.action_date ASC
                ", [$priority]);
                sendResponse($stmt->fetchAll(PDO::FETCH_ASSOC));
                break;
                
            case 'refresh':
                // Atualizar cache
                $stmt = $db->query("CALL refresh_action_lists()");
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                sendResponse($result);
                break;
                
            default:
                sendResponse(null, 'Ação não especificada');
        }
    }
    
    // POST - Completar ação
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) $input = $_POST;
        
        $action = $input['action'] ?? 'complete';
        
        if ($action === 'complete') {
            $id = $input['id'] ?? null;
            if (!$id) sendResponse(null, 'ID não fornecido');
            
            $db->query("
                UPDATE action_lists_cache 
                SET is_completed = 1, completed_at = NOW()
                WHERE id = ?
            ", [$id]);
            
            sendResponse(['message' => 'Ação marcada como completa']);
        }
        
        if ($action === 'snooze') {
            $id = $input['id'] ?? null;
            $days = $input['days'] ?? 1;
            
            if (!$id) sendResponse(null, 'ID não fornecido');
            
            $db->query("
                UPDATE action_lists_cache 
                SET action_date = DATE_ADD(action_date, INTERVAL ? DAY),
                    days_until = days_until + ?
                WHERE id = ?
            ", [$days, $days, $id]);
            
            sendResponse(['message' => "Ação adiada por $days dias"]);
        }
    }
    
} catch (Exception $e) {
    error_log("Erro API Actions: " . $e->getMessage());
    sendResponse(null, 'Erro: ' . $e->getMessage(), 500);
}

