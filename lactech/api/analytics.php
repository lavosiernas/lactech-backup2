<?php
header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

// Iniciar sessão se não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Usuário não autenticado']);
    exit;
}

// Incluir Database class
$dbPath = __DIR__ . '/../includes/Database.class.php';
if (!file_exists($dbPath)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database class não encontrada']);
    exit;
}
require_once $dbPath;

// Função para enviar resposta JSON
function sendResponse($success, $data = null, $error = null) {
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'error' => $error
    ]);
    exit;
}

try {
    $db = Database::getInstance();
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    switch ($action) {
        case 'get_dashboard_summary':
            $summary = $db->getDashboardSummary(1);
            sendResponse(true, $summary);
            
        case 'get_performance_indicators':
            $indicators = $db->getPerformanceIndicators(1);
            sendResponse(true, $indicators);
            
        case 'get_production_trends':
            $days = intval($_GET['days'] ?? 30);
            $trends = $db->getProductionTrends(1, $days);
            sendResponse(true, $trends);
            
        case 'get_reproductive_analysis':
            $analysis = $db->getReproductiveAnalysis(1);
            sendResponse(true, $analysis);
            
        case 'get_health_analysis':
            $analysis = $db->getHealthAnalysis(1);
            sendResponse(true, $analysis);
            
        case 'get_financial_analysis':
            $analysis = $db->getFinancialAnalysis(1);
            sendResponse(true, $analysis);
            
        case 'get_performance_alerts':
            $alerts = $db->getPerformanceAlerts(1);
            sendResponse(true, $alerts);
            
        case 'resolve_performance_alert':
            $alertId = intval($_POST['id'] ?? 0);
            $notes = $_POST['notes'] ?? '';
            
            if ($alertId <= 0) {
                sendResponse(false, null, 'ID do alerta inválido');
            }
            
            $result = $db->resolvePerformanceAlert($alertId, $_SESSION['user_id'], $notes);
            sendResponse($result, null, $result ? null : 'Erro ao resolver alerta');
            
        case 'update_management_indicators':
            $indicators = json_decode($_POST['indicators'] ?? '[]', true);
            
            if (empty($indicators)) {
                sendResponse(false, null, 'Nenhum indicador fornecido');
            }
            
            $result = $db->updateManagementIndicators(1, $indicators);
            sendResponse($result, null, $result ? null : 'Erro ao atualizar indicadores');
            
        case 'get_comparative_analysis':
            $period = $_GET['period'] ?? 'monthly';
            $comparison = $db->getComparativeAnalysis(1, $period);
            sendResponse(true, $comparison);
            
        case 'get_efficiency_score':
            $score = $db->getFarmEfficiencyScore(1);
            sendResponse(true, $score);
            
        default:
            sendResponse(false, null, 'Ação inválida');
    }
    
} catch (Exception $e) {
    sendResponse(false, null, 'Erro interno: ' . $e->getMessage());
}
?>

