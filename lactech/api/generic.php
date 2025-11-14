<?php
/**
 * API Genérica - Lactech
 * Endpoint genérico para operações CRUD básicas
 */

// Configurações de segurança
error_reporting(0);
ini_set('display_errors', 0);

// Headers para JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
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
    $table = $_GET['table'] ?? $_POST['table'] ?? '';
    $method = $_SERVER['REQUEST_METHOD'];
    
    // Validar tabela
    $allowedTables = [
        'password_requests',
        'notifications',
        'chat_messages',
        'chat-files',
        'profile-photos',
        'quality_records'
    ];
    
    if (!in_array($table, $allowedTables)) {
        echo json_encode(['success' => false, 'error' => 'Tabela não permitida']);
        exit;
    }
    
    switch ($method) {
        case 'GET':
            // Listar registros
            $limit = $_GET['limit'] ?? 50;
            $offset = $_GET['offset'] ?? 0;
            
            $query = "SELECT * FROM $table ORDER BY created_at DESC LIMIT ? OFFSET ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$limit, $offset]);
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $records
            ]);
            break;
            
        case 'POST':
            // Criar registro
            $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            
            if ($table === 'password_requests') {
                // Criar solicitação de senha
                $stmt = $db->prepare("INSERT INTO password_requests (user_id, token, status, expires_at, created_at) VALUES (?, ?, 'pending', ?, NOW())");
                $token = bin2hex(random_bytes(32));
                $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
                $stmt->execute([$data['user_id'], $token, $expiresAt]);
                
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'id' => $db->lastInsertId(),
                        'token' => $token,
                        'expires_at' => $expiresAt
                    ]
                ]);
            } elseif ($table === 'notifications') {
                // Criar notificação
                $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type, priority, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([
                    $data['user_id'] ?? null,
                    $data['title'],
                    $data['message'],
                    $data['type'] ?? 'info',
                    $data['priority'] ?? 'normal',
                    $_SESSION['user_id']
                ]);
                
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'id' => $db->lastInsertId(),
                        'title' => $data['title'],
                        'message' => $data['message']
                    ]
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Operação não suportada para esta tabela']);
            }
            break;
            
        case 'PUT':
            // Atualizar registro
            $data = json_decode(file_get_contents('php://input'), true);
            $id = $data['id'] ?? null;
            
            if (!$id) {
                echo json_encode(['success' => false, 'error' => 'ID é obrigatório']);
                exit;
            }
            
            if ($table === 'password_requests') {
                $stmt = $db->prepare("UPDATE password_requests SET status = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$data['status'], $id]);
            } elseif ($table === 'notifications') {
                $stmt = $db->prepare("UPDATE notifications SET is_read = 1, read_at = NOW() WHERE id = ?");
                $stmt->execute([$id]);
            }
            
            echo json_encode(['success' => true, 'message' => 'Registro atualizado']);
            break;
            
        case 'DELETE':
            // Deletar registro
            $id = $_GET['id'] ?? $_POST['id'] ?? null;
            
            if (!$id) {
                echo json_encode(['success' => false, 'error' => 'ID é obrigatório']);
                exit;
            }
            
            $stmt = $db->prepare("DELETE FROM $table WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode(['success' => true, 'message' => 'Registro removido']);
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

