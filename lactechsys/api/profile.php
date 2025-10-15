<?php
// API para perfil e foto do usuário

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
            case 'get_photo':
            case 'select':
                // Retornar foto padrão ou URL da foto do usuário
                $photoData = [
                    'photo_url' => 'assets/img/default-avatar.png',
                    'has_photo' => false
                ];
                
                echo json_encode(['success' => true, 'data' => $photoData]);
                break;
                
            case 'get_profile':
                // Retornar dados do perfil
                $userId = $_SESSION['user_id'] ?? 1;
                $user = $db->getUserById($userId);
                
                if ($user) {
                    echo json_encode(['success' => true, 'data' => $user]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Usuário não encontrado']);
                }
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
