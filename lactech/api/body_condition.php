<?php
/**
 * API: Body Condition Score (BCS)
 * Avaliação de condição corporal dos animais
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
                // Listar todas avaliações
                $limit = $_GET['limit'] ?? 100;
                
                $stmt = $db->query("
                    SELECT 
                        bcs.*,
                        a.animal_number,
                        a.name as animal_name,
                        a.breed,
                        a.status,
                        u.name as evaluated_by_name
                    FROM body_condition_scores bcs
                    LEFT JOIN animals a ON bcs.animal_id = a.id
                    LEFT JOIN users u ON bcs.evaluated_by = u.id
                    ORDER BY bcs.evaluation_date DESC, bcs.created_at DESC
                    LIMIT ?
                ", [$limit]);
                sendResponse($stmt->fetchAll(PDO::FETCH_ASSOC));
                break;
                
            case 'by_animal':
                $animal_id = $_GET['animal_id'] ?? null;
                if (!$animal_id) sendResponse(null, 'ID do animal não fornecido');
                
                $stmt = $db->query("
                    SELECT bcs.*, u.name as evaluated_by_name
                    FROM body_condition_scores bcs
                    LEFT JOIN users u ON bcs.evaluated_by = u.id
                    WHERE bcs.animal_id = ?
                    ORDER BY bcs.evaluation_date DESC
                ", [$animal_id]);
                sendResponse($stmt->fetchAll(PDO::FETCH_ASSOC));
                break;
                
            case 'latest':
                // BCS mais recente de cada animal
                $stmt = $db->query("
                    SELECT 
                        a.id as animal_id,
                        a.animal_number,
                        a.name as animal_name,
                        a.breed,
                        a.status,
                        bcs.score as latest_bcs,
                        bcs.evaluation_date,
                        bcs.weight_kg,
                        CASE 
                            WHEN bcs.score < 2.0 THEN 'critical'
                            WHEN bcs.score < 2.5 THEN 'low'
                            WHEN bcs.score < 3.5 THEN 'ideal'
                            WHEN bcs.score < 4.0 THEN 'high'
                            ELSE 'very_high'
                        END as bcs_status,
                        DATEDIFF(CURDATE(), bcs.evaluation_date) as days_since_eval
                    FROM animals a
                    INNER JOIN body_condition_scores bcs ON a.id = bcs.animal_id
                    INNER JOIN (
                        SELECT animal_id, MAX(evaluation_date) as max_date
                        FROM body_condition_scores
                        GROUP BY animal_id
                    ) latest ON bcs.animal_id = latest.animal_id 
                        AND bcs.evaluation_date = latest.max_date
                    WHERE a.is_active = 1
                    ORDER BY bcs.score ASC, a.animal_number
                ");
                sendResponse($stmt->fetchAll(PDO::FETCH_ASSOC));
                break;
                
            case 'low_bcs':
                // Animais com BCS baixo (< 2.5)
                $threshold = $_GET['threshold'] ?? 2.5;
                
                $stmt = $db->query("
                    SELECT 
                        a.id as animal_id,
                        a.animal_number,
                        a.name as animal_name,
                        a.breed,
                        a.status,
                        bcs.score,
                        bcs.evaluation_date,
                        bcs.weight_kg,
                        DATEDIFF(CURDATE(), bcs.evaluation_date) as days_since_eval
                    FROM animals a
                    INNER JOIN body_condition_scores bcs ON a.id = bcs.animal_id
                    INNER JOIN (
                        SELECT animal_id, MAX(evaluation_date) as max_date
                        FROM body_condition_scores
                        GROUP BY animal_id
                    ) latest ON bcs.animal_id = latest.animal_id 
                        AND bcs.evaluation_date = latest.max_date
                    WHERE a.is_active = 1 AND bcs.score < ?
                    ORDER BY bcs.score ASC
                ", [$threshold]);
                sendResponse($stmt->fetchAll(PDO::FETCH_ASSOC));
                break;
                
            case 'stats':
                // Estatísticas gerais de BCS
                $stmt = $db->query("
                    SELECT 
                        COUNT(DISTINCT a.id) as total_animals,
                        AVG(bcs.score) as avg_bcs,
                        MIN(bcs.score) as min_bcs,
                        MAX(bcs.score) as max_bcs,
                        SUM(CASE WHEN bcs.score < 2.5 THEN 1 ELSE 0 END) as low_bcs_count,
                        SUM(CASE WHEN bcs.score >= 2.5 AND bcs.score < 3.5 THEN 1 ELSE 0 END) as ideal_bcs_count,
                        SUM(CASE WHEN bcs.score >= 3.5 THEN 1 ELSE 0 END) as high_bcs_count
                    FROM animals a
                    INNER JOIN body_condition_scores bcs ON a.id = bcs.animal_id
                    INNER JOIN (
                        SELECT animal_id, MAX(evaluation_date) as max_date
                        FROM body_condition_scores
                        GROUP BY animal_id
                    ) latest ON bcs.animal_id = latest.animal_id 
                        AND bcs.evaluation_date = latest.max_date
                    WHERE a.is_active = 1
                ");
                sendResponse($stmt->fetch(PDO::FETCH_ASSOC));
                break;
                
            default:
                sendResponse(null, 'Ação não especificada');
        }
    }
    
    // POST - Criar avaliação
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) $input = $_POST;
        
        // Validações
        if (empty($input['animal_id'])) sendResponse(null, 'ID do animal obrigatório');
        if (empty($input['score'])) sendResponse(null, 'Score obrigatório');
        if (empty($input['evaluation_date'])) sendResponse(null, 'Data de avaliação obrigatória');
        
        // Validar score (1.0 a 5.0)
        $score = floatval($input['score']);
        if ($score < 1.0 || $score > 5.0) {
            sendResponse(null, 'Score deve estar entre 1.0 e 5.0');
        }
        
        // Inserir
        $stmt = $db->query("
            INSERT INTO body_condition_scores (
                animal_id, score, evaluation_date, evaluation_method,
                lactation_stage, weight_kg, height_cm, body_measurements,
                photo_url, notes, evaluated_by, farm_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
        ", [
            $input['animal_id'],
            $score,
            $input['evaluation_date'],
            $input['evaluation_method'] ?? 'visual',
            $input['lactation_stage'] ?? null,
            $input['weight_kg'] ?? null,
            $input['height_cm'] ?? null,
            isset($input['body_measurements']) ? json_encode($input['body_measurements']) : null,
            $input['photo_url'] ?? null,
            $input['notes'] ?? null,
            $_SESSION['user_id'] ?? 1
        ]);
        
        $id = $db->getConnection()->lastInsertId();
        
        // Retornar dados + recomendação
        $recommendation = '';
        if ($score < 2.0) {
            $recommendation = 'CRÍTICO: Animal necessita atenção imediata! Aumentar alimentação e verificar saúde.';
        } elseif ($score < 2.5) {
            $recommendation = 'BAIXO: Aumentar concentrado e volumoso de qualidade. Monitorar semanalmente.';
        } elseif ($score >= 2.5 && $score <= 3.5) {
            $recommendation = 'IDEAL: Manter protocolo nutricional atual.';
        } elseif ($score > 3.5 && $score <= 4.0) {
            $recommendation = 'ALTO: Considerar reduzir concentrado se não estiver em lactação.';
        } else {
            $recommendation = 'MUITO ALTO: Risco de problemas metabólicos. Ajustar dieta.';
        }
        
        sendResponse([
            'id' => $id,
            'message' => 'BCS registrado com sucesso',
            'score' => $score,
            'recommendation' => $recommendation
        ]);
    }
    
    // PUT - Atualizar
    if ($method === 'PUT') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $id = $input['id'] ?? null;
        if (!$id) sendResponse(null, 'ID não fornecido');
        
        $updates = [];
        $values = [];
        
        $allowed = ['score', 'evaluation_date', 'evaluation_method', 'lactation_stage', 
                    'weight_kg', 'height_cm', 'photo_url', 'notes'];
        foreach ($allowed as $field) {
            if (isset($input[$field])) {
                $updates[] = "$field = ?";
                $values[] = $input[$field];
            }
        }
        
        if (empty($updates)) sendResponse(null, 'Nenhum campo para atualizar');
        
        $values[] = $id;
        $db->query("
            UPDATE body_condition_scores 
            SET " . implode(', ', $updates) . "
            WHERE id = ?
        ", $values);
        
        sendResponse(['message' => 'BCS atualizado']);
    }
    
    // DELETE - Remover
    if ($method === 'DELETE') {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? $_GET['id'] ?? null;
        
        if (!$id) sendResponse(null, 'ID não fornecido');
        
        $db->query("DELETE FROM body_condition_scores WHERE id = ?", [$id]);
        sendResponse(['message' => 'BCS removido']);
    }
    
} catch (Exception $e) {
    error_log("Erro API BCS: " . $e->getMessage());
    sendResponse(null, 'Erro: ' . $e->getMessage(), 500);
}

