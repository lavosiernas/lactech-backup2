<?php
// API de estatísticas simplificada

// Desabilitar exibição de erros em produção
error_reporting(0);
ini_set('display_errors', 0);

// Iniciar sessão se não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Sempre retornar JSON
header('Content-Type: application/json');

// Carregar Database.class.php
$dbPath = __DIR__ . '/../includes/Database.class.php';
if (!file_exists($dbPath)) {
    echo json_encode(['success' => false, 'error' => 'Database class não encontrada']);
    exit;
}
require_once $dbPath;

$db = Database::getInstance();

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'get_farm_stats':
                $stats = $db->getDashboardStats();
                echo json_encode(['success' => true, 'data' => $stats]);
                break;
                
            default:
                echo json_encode(['success' => false, 'error' => 'Ação não especificada']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Método não permitido']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>