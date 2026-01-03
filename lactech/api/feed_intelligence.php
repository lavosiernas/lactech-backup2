<?php
/**
 * API de Inteligência de Alimentação
 * Cálculos ideais, comparações e análise de alimentação
 */

// Configurações de segurança
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Headers para JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Tratar requisições OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar autenticação
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Acesso negado'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__ . '/../includes/FeedingIntelligence.class.php';

function sendResponse($data = null, $error = null, $statusCode = 200) {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    $response = ['success' => $error === null];
    if ($data !== null) {
        $response['data'] = $data;
    }
    if ($error !== null) {
        $response['error'] = $error;
    }
    
    header_remove();
    header('Content-Type: application/json; charset=utf-8');
    http_response_code($statusCode);
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

try {
    $farm_id = $_SESSION['farm_id'] ?? 1;
    $fi = new FeedingIntelligence($farm_id);
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    // Ler dados JSON se for POST
    $input = [];
    if ($method === 'POST') {
        $rawInput = file_get_contents('php://input');
        if (!empty($rawInput)) {
            $input = json_decode($rawInput, true) ?? [];
        }
        $input = array_merge($_POST, $input);
    }
    
    switch ($action) {
        case 'calculate_ideal_animal':
            // Calcular alimentação ideal para um animal
            $animal_id = $_GET['animal_id'] ?? $input['animal_id'] ?? null;
            $date = $_GET['date'] ?? $input['date'] ?? date('Y-m-d');
            
            if (!$animal_id) {
                sendResponse(null, 'animal_id é obrigatório', 400);
            }
            
            $result = $fi->calculateIdealFeedForAnimal($animal_id, $date);
            sendResponse($result['success'] ? $result : null, $result['success'] ? null : $result['error']);
            break;
            
        case 'calculate_ideal_group':
            // Calcular alimentação ideal para um grupo
            $group_id = $_GET['group_id'] ?? $input['group_id'] ?? null;
            $date = $_GET['date'] ?? $input['date'] ?? date('Y-m-d');
            
            if (!$group_id) {
                sendResponse(null, 'group_id é obrigatório', 400);
            }
            
            $result = $fi->calculateIdealFeedForGroup($group_id, $date);
            sendResponse($result['success'] ? $result : null, $result['success'] ? null : $result['error']);
            break;
            
        case 'compare':
            // Comparar alimentação real vs ideal
            $feed_record_id = $_GET['feed_record_id'] ?? $input['feed_record_id'] ?? null;
            
            if (!$feed_record_id) {
                sendResponse(null, 'feed_record_id é obrigatório', 400);
            }
            
            $result = $fi->compareRealVsIdeal($feed_record_id);
            sendResponse($result['success'] ? $result : null, $result['success'] ? null : $result['error']);
            break;
            
        case 'get_animal_weight':
            // Obter peso de um animal
            $animal_id = $_GET['animal_id'] ?? $input['animal_id'] ?? null;
            
            if (!$animal_id) {
                sendResponse(null, 'animal_id é obrigatório', 400);
            }
            
            $weight = $fi->getAnimalWeight($animal_id);
            sendResponse($weight);
            break;
            
        case 'get_group_average_weight':
            // Obter peso médio de um grupo
            $group_id = $_GET['group_id'] ?? $input['group_id'] ?? null;
            
            if (!$group_id) {
                sendResponse(null, 'group_id é obrigatório', 400);
            }
            
            $weight = $fi->getGroupAverageWeight($group_id);
            sendResponse($weight);
            break;
            
        case 'register_group_weight':
            // Registrar peso do lote diretamente
            $group_id = $input['group_id'] ?? null;
            $avg_weight_kg = isset($input['avg_weight_kg']) ? floatval($input['avg_weight_kg']) : null;
            $animal_count = isset($input['animal_count']) ? intval($input['animal_count']) : null;
            $weighing_date = $input['weighing_date'] ?? date('Y-m-d');
            $notes = $input['notes'] ?? null;
            
            if (!$group_id) {
                sendResponse(null, 'group_id é obrigatório', 400);
            }
            if ($avg_weight_kg === null || $avg_weight_kg <= 0) {
                sendResponse(null, 'avg_weight_kg é obrigatório e deve ser maior que zero', 400);
            }
            if ($animal_count === null || $animal_count <= 0) {
                sendResponse(null, 'animal_count é obrigatório e deve ser maior que zero', 400);
            }
            
            $result = $fi->registerGroupWeight($group_id, $avg_weight_kg, $animal_count, $weighing_date, $notes);
            sendResponse($result['success'] ? $result : null, $result['success'] ? null : $result['error']);
            break;
            
        default:
            sendResponse(null, 'Ação não encontrada', 404);
    }
    
} catch (Exception $e) {
    error_log("Erro na API de inteligência de alimentação: " . $e->getMessage());
    sendResponse(null, 'Erro interno: ' . $e->getMessage(), 500);
}
?>

