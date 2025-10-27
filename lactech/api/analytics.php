<?php
/**
 * API de Analytics - Lactech
 * Fallback para funcionalidades de análise
 */

// Configurações de segurança
error_reporting(0);
ini_set('display_errors', 0);

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
    echo json_encode(['success' => false, 'error' => 'Acesso negado']);
    exit;
}

// Verificar se Database.class.php existe
$dbPath = __DIR__ . '/../includes/Database.class.php';
if (!file_exists($dbPath)) {
    echo json_encode(['success' => false, 'error' => 'Erro no servidor: Database.class.php não encontrado']);
    exit;
}

require_once $dbPath;

try {
    $db = Database::getInstance();
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    switch ($action) {
        case 'get_efficiency_score':
            // Fallback para score de eficiência
            echo json_encode([
                'success' => true,
                'data' => [
                    'efficiency_score' => 85.5,
                    'production_efficiency' => 78.2,
                    'reproductive_efficiency' => 92.1,
                    'health_efficiency' => 88.7
                ]
            ]);
            break;
            
        case 'get_dashboard_summary':
            // Fallback para resumo executivo
            echo json_encode([
                'success' => true,
                'data' => [
                    'total_animals' => 15,
                    'lactating_cows' => 8,
                    'daily_production' => 450.5,
                    'avg_quality' => 89.2
                ]
            ]);
            break;
            
        case 'get_performance_indicators':
            // Fallback para indicadores de performance
            echo json_encode([
                'success' => true,
                'data' => [
                    'milk_production_trend' => 'up',
                    'quality_trend' => 'stable',
                    'reproductive_rate' => 75.5,
                    'health_index' => 88.3
                ]
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Ação não encontrada']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Erro interno: ' . $e->getMessage()]);
}
?>
