<?php
/**
 * API de Atividades - Lactech
 * Endpoint para atividades do sistema
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
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            if ($action === 'select') {
                // Listar atividades recentes
                $limit = $_GET['limit'] ?? 50;
                $farmId = $_GET['farm_id'] ?? 1;
                
                $activities = [
                    [
                        'id' => 1,
                        'type' => 'system',
                        'message' => 'Sistema inicializado',
                        'timestamp' => date('c'),
                        'user_id' => $_SESSION['user_id'],
                        'farm_id' => $farmId
                    ],
                    [
                        'id' => 2,
                        'type' => 'volume',
                        'message' => 'Registro de volume adicionado',
                        'timestamp' => date('c', strtotime('-1 hour')),
                        'user_id' => $_SESSION['user_id'],
                        'farm_id' => $farmId
                    ],
                    [
                        'id' => 3,
                        'type' => 'quality',
                        'message' => 'Teste de qualidade realizado',
                        'timestamp' => date('c', strtotime('-2 hours')),
                        'user_id' => $_SESSION['user_id'],
                        'farm_id' => $farmId
                    ]
                ];
                
                echo json_encode([
                    'success' => true,
                    'data' => $activities
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Ação inválida']);
            }
            break;
            
        case 'POST':
            // Criar atividade
            $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            
            $activity = [
                'id' => time(),
                'type' => $data['type'] ?? 'system',
                'message' => $data['message'] ?? 'Nova atividade',
                'timestamp' => date('c'),
                'user_id' => $_SESSION['user_id'],
                'farm_id' => $data['farm_id'] ?? 1
            ];
            
            echo json_encode([
                'success' => true,
                'data' => $activity
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Método não permitido']);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Erro no servidor: ' . $e->getMessage()
    ]);
}
?>

