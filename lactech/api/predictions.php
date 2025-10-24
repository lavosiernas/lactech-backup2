<?php
/**
 * API: AI Predictions
 * Previsões de IA para eventos e métricas
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
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
    
    // GET - Listar/Buscar
    if ($method === 'GET') {
        $action = $_GET['action'] ?? 'list';
        
        switch ($action) {
            case 'list':
                $prediction_type = $_GET['type'] ?? null;
                $where = $prediction_type ? "WHERE ap.prediction_type = ?" : "";
                $params = $prediction_type ? [$prediction_type] : [];
                
                $stmt = $db->query("
                    SELECT 
                        ap.*,
                        a.animal_number,
                        a.name as animal_name,
                        a.breed,
                        a.status,
                        DATEDIFF(ap.predicted_date, CURDATE()) as days_until,
                        CASE 
                            WHEN ap.predicted_date < CURDATE() THEN 'overdue'
                            WHEN DATEDIFF(ap.predicted_date, CURDATE()) <= 3 THEN 'imminent'
                            WHEN DATEDIFF(ap.predicted_date, CURDATE()) <= 7 THEN 'soon'
                            ELSE 'future'
                        END as time_status
                    FROM ai_predictions ap
                    LEFT JOIN animals a ON ap.animal_id = a.id
                    $where
                    ORDER BY ap.predicted_date ASC
                    LIMIT 100
                ", $params);
                sendResponse($stmt->fetchAll(PDO::FETCH_ASSOC));
                break;
                
            case 'by_animal':
                $animal_id = $_GET['animal_id'] ?? null;
                if (!$animal_id) sendResponse(null, 'ID do animal não fornecido');
                
                $stmt = $db->query("
                    SELECT *
                    FROM ai_predictions
                    WHERE animal_id = ?
                    ORDER BY predicted_date DESC
                ", [$animal_id]);
                sendResponse($stmt->fetchAll(PDO::FETCH_ASSOC));
                break;
                
            case 'upcoming':
                // Previsões dos próximos 7 dias
                $days = $_GET['days'] ?? 7;
                
                $stmt = $db->query("
                    SELECT 
                        ap.*,
                        a.animal_number,
                        a.name as animal_name,
                        a.breed,
                        DATEDIFF(ap.predicted_date, CURDATE()) as days_until
                    FROM ai_predictions ap
                    LEFT JOIN animals a ON ap.animal_id = a.id
                    WHERE ap.predicted_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
                      AND ap.actual_date IS NULL
                    ORDER BY ap.predicted_date ASC, ap.confidence_score DESC
                ", [$days]);
                sendResponse($stmt->fetchAll(PDO::FETCH_ASSOC));
                break;
                
            case 'accuracy':
                // Estatísticas de precisão das previsões
                $stmt = $db->query("
                    SELECT 
                        prediction_type,
                        COUNT(*) as total_predictions,
                        SUM(CASE WHEN was_accurate = 1 THEN 1 ELSE 0 END) as accurate_count,
                        (SUM(CASE WHEN was_accurate = 1 THEN 1 ELSE 0 END) / COUNT(*) * 100) as accuracy_percentage,
                        AVG(confidence_score) as avg_confidence,
                        AVG(error_margin) as avg_error_margin
                    FROM ai_predictions
                    WHERE actual_date IS NOT NULL
                    GROUP BY prediction_type
                ");
                sendResponse($stmt->fetchAll(PDO::FETCH_ASSOC));
                break;
                
            case 'predict_heat':
                // Executar procedure para prever cio
                $animal_id = $_GET['animal_id'] ?? null;
                if (!$animal_id) sendResponse(null, 'ID do animal não fornecido');
                
                $stmt = $db->query("CALL predict_next_heat(?)", [$animal_id]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                sendResponse($result);
                break;
                
            default:
                sendResponse(null, 'Ação não especificada');
        }
    }
    
    // POST - Criar previsão manual
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) $input = $_POST;
        
        $action = $input['action'] ?? 'create';
        
        if ($action === 'create') {
            if (empty($input['prediction_type'])) sendResponse(null, 'Tipo de previsão obrigatório');
            if (empty($input['predicted_date'])) sendResponse(null, 'Data prevista obrigatória');
            
            $stmt = $db->query("
                INSERT INTO ai_predictions (
                    animal_id, prediction_type, predicted_date, predicted_value,
                    confidence_score, algorithm_version, input_data,
                    notes, farm_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
            ", [
                $input['animal_id'] ?? null,
                $input['prediction_type'],
                $input['predicted_date'],
                $input['predicted_value'] ?? null,
                $input['confidence_score'] ?? 50.0,
                $input['algorithm_version'] ?? 'manual',
                isset($input['input_data']) ? json_encode($input['input_data']) : null,
                $input['notes'] ?? null
            ]);
            
            $id = $db->getConnection()->lastInsertId();
            sendResponse(['id' => $id, 'message' => 'Previsão criada']);
        }
        
        if ($action === 'validate') {
            // Validar previsão com resultado real
            $id = $input['id'] ?? null;
            if (!$id) sendResponse(null, 'ID não fornecido');
            if (empty($input['actual_date'])) sendResponse(null, 'Data real obrigatória');
            
            // Calcular precisão
            $prediction = $db->query("SELECT * FROM ai_predictions WHERE id = ?", [$id])->fetch();
            
            $error_margin = abs((strtotime($input['actual_date']) - strtotime($prediction['predicted_date'])) / 86400);
            $was_accurate = $error_margin <= 3; // Preciso se diferença <= 3 dias
            
            $db->query("
                UPDATE ai_predictions 
                SET actual_date = ?, 
                    actual_value = ?,
                    was_accurate = ?,
                    error_margin = ?
                WHERE id = ?
            ", [
                $input['actual_date'],
                $input['actual_value'] ?? null,
                $was_accurate,
                $error_margin,
                $id
            ]);
            
            sendResponse([
                'message' => 'Previsão validada',
                'was_accurate' => $was_accurate,
                'error_margin' => $error_margin
            ]);
        }
    }
    
    // DELETE - Remover
    if ($method === 'DELETE') {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? $_GET['id'] ?? null;
        
        if (!$id) sendResponse(null, 'ID não fornecido');
        
        $db->query("DELETE FROM ai_predictions WHERE id = ?", [$id]);
        sendResponse(['message' => 'Previsão removida']);
    }
    
} catch (Exception $e) {
    error_log("Erro API Predictions: " . $e->getMessage());
    sendResponse(null, 'Erro: ' . $e->getMessage(), 500);
}

