<?php
// API para atividades recentes

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
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if ($method === 'GET') {
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'get_recent':
            case 'select':
                // Retornar atividades recentes simuladas
                $activities = [
                    [
                        'id' => 1,
                        'type' => 'volume',
                        'description' => 'Coleta de leite registrada',
                        'date' => date('Y-m-d H:i:s'),
                        'user' => 'Sistema'
                    ],
                    [
                        'id' => 2,
                        'type' => 'quality',
                        'description' => 'Teste de qualidade realizado',
                        'date' => date('Y-m-d H:i:s', strtotime('-1 hour')),
                        'user' => 'Sistema'
                    ]
                ];
                
                echo json_encode(['success' => true, 'data' => $activities]);
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
