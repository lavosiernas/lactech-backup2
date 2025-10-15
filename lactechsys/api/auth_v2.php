<?php
/**
 * API DE AUTENTICAÇÃO V2 - LACTECH
 * Sistema robusto e profissional
 * Versão: 2.0.0
 */

// Configurações de erro
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Headers de segurança
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');

// Iniciar sessão se não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Carregar configurações e Database
try {
    require_once __DIR__ . '/../includes/config.php';
    require_once __DIR__ . '/../includes/Database.class.php';
} catch (Exception $e) {
    jsonResponse([
        'success' => false,
        'error' => 'Erro de configuração: ' . $e->getMessage()
    ], 500);
}

// Verificar método HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse([
        'success' => false,
        'error' => 'Método não permitido'
    ], 405);
}

// Obter dados da requisição
$input = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
} else {
    $input = $_GET;
}

$action = $input['action'] ?? '';

// Log da requisição (apenas em debug)
if (AppConfig::isDebugMode()) {
    error_log("Auth API - Ação: {$action} - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'N/A'));
}

try {
    $db = Database::getInstance();
    
    switch ($action) {
        // ==================== LOGIN ====================
        case 'login':
            $email = trim($input['email'] ?? '');
            $password = $input['password'] ?? '';
            
            // Validações
            if (empty($email)) {
                jsonResponse([
                    'success' => false,
                    'error' => 'Email é obrigatório'
                ], 400);
            }
            
            if (empty($password)) {
                jsonResponse([
                    'success' => false,
                    'error' => 'Senha é obrigatória'
                ], 400);
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                jsonResponse([
                    'success' => false,
                    'error' => 'Email inválido'
                ], 400);
            }
            
            // Tentar login
            $result = $db->login($email, $password);
            
            if ($result['success']) {
                // Criar sessão
                $_SESSION['user_id'] = $result['user']['id'];
                $_SESSION['user_email'] = $result['user']['email'];
                $_SESSION['user_name'] = $result['user']['name'];
                $_SESSION['user_role'] = $result['user']['role'];
                $_SESSION['farm_id'] = $result['user']['farm_id'];
                $_SESSION['logged_in'] = true;
                $_SESSION['login_time'] = time();
                
                // Adicionar informações extras
                $result['user']['farm_name'] = $result['user']['farm_name'] ?? 'Lagoa do Mato';
                $result['redirect'] = getRedirectByRole($result['user']['role']);
                
                jsonResponse([
                    'success' => true,
                    'user' => $result['user'],
                    'redirect' => $result['redirect'],
                    'session_id' => session_id()
                ]);
            } else {
                jsonResponse([
                    'success' => false,
                    'error' => $result['error']
                ], 401);
            }
            break;
            
        // ==================== LOGOUT ====================
        case 'logout':
            // Destruir sessão
            session_destroy();
            
            jsonResponse([
                'success' => true,
                'message' => 'Logout realizado com sucesso'
            ]);
            break;
            
        // ==================== VERIFICAR SESSÃO ====================
        case 'check_session':
            if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
                // Verificar se a sessão não expirou (1 hora)
                $sessionTimeout = 3600; // 1 hora
                if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > $sessionTimeout) {
                    session_destroy();
                    jsonResponse([
                        'success' => true,
                        'logged_in' => false,
                        'message' => 'Sessão expirada'
                    ]);
                }
                
                // Buscar dados atualizados do usuário
                $user = $db->getUser($_SESSION['user_id']);
                
                if ($user) {
                    jsonResponse([
                        'success' => true,
                        'logged_in' => true,
                        'user' => $user
                    ]);
                } else {
                    session_destroy();
                    jsonResponse([
                        'success' => true,
                        'logged_in' => false,
                        'message' => 'Usuário não encontrado'
                    ]);
                }
            } else {
                jsonResponse([
                    'success' => true,
                    'logged_in' => false
                ]);
            }
            break;
            
        // ==================== OBTER USUÁRIO ATUAL ====================
        case 'get_current_user':
            if (!isset($_SESSION['user_id'])) {
                jsonResponse([
                    'success' => false,
                    'error' => 'Não autenticado'
                ], 401);
            }
            
            $user = $db->getUser($_SESSION['user_id']);
            
            if (!$user) {
                session_destroy();
                jsonResponse([
                    'success' => false,
                    'error' => 'Usuário não encontrado'
                ], 404);
            }
            
            jsonResponse([
                'success' => true,
                'user' => $user
            ]);
            break;
            
        // ==================== REGISTRO ====================
        case 'register':
            $name = trim($input['name'] ?? '');
            $email = trim($input['email'] ?? '');
            $password = $input['password'] ?? '';
            $role = $input['role'] ?? 'funcionario';
            $cpf = $input['cpf'] ?? null;
            $phone = $input['phone'] ?? null;
            
            // Validações
            if (empty($name) || empty($email) || empty($password)) {
                jsonResponse([
                    'success' => false,
                    'error' => 'Nome, email e senha são obrigatórios'
                ], 400);
            }
            
            if (strlen($password) < 6) {
                jsonResponse([
                    'success' => false,
                    'error' => 'Senha deve ter no mínimo 6 caracteres'
                ], 400);
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                jsonResponse([
                    'success' => false,
                    'error' => 'Email inválido'
                ], 400);
            }
            
            if (!in_array($role, USER_ROLES)) {
                jsonResponse([
                    'success' => false,
                    'error' => 'Role inválido'
                ], 400);
            }
            
            $result = $db->createUser([
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'role' => $role,
                'cpf' => $cpf,
                'phone' => $phone
            ]);
            
            jsonResponse($result, $result['success'] ? 201 : 400);
            break;
            
        // ==================== ALTERAR SENHA ====================
        case 'change_password':
            if (!isset($_SESSION['user_id'])) {
                jsonResponse([
                    'success' => false,
                    'error' => 'Não autenticado'
                ], 401);
            }
            
            $currentPassword = $input['current_password'] ?? '';
            $newPassword = $input['new_password'] ?? '';
            
            if (empty($currentPassword) || empty($newPassword)) {
                jsonResponse([
                    'success' => false,
                    'error' => 'Preencha todos os campos'
                ], 400);
            }
            
            if (strlen($newPassword) < 6) {
                jsonResponse([
                    'success' => false,
                    'error' => 'Nova senha deve ter no mínimo 6 caracteres'
                ], 400);
            }
            
            // Verificar senha atual
            $user = $db->getUser($_SESSION['user_id']);
            if (!$user) {
                jsonResponse([
                    'success' => false,
                    'error' => 'Usuário não encontrado'
                ], 404);
            }
            
            // Buscar senha atual do banco
            $stmt = $db->getConnection()->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $hashedPassword = $stmt->fetchColumn();
            
            if (!password_verify($currentPassword, $hashedPassword)) {
                jsonResponse([
                    'success' => false,
                    'error' => 'Senha atual incorreta'
                ], 400);
            }
            
            // Atualizar senha
            $result = $db->updateUser($_SESSION['user_id'], [
                'password' => $newPassword
            ]);
            
            jsonResponse($result, $result['success'] ? 200 : 400);
            break;
            
        // ==================== TESTE DE CONEXÃO ====================
        case 'test_connection':
            $result = $db->testConnection();
            jsonResponse($result, $result['success'] ? 200 : 500);
            break;
            
        // ==================== ESTATÍSTICAS ====================
        case 'stats':
            if (!isset($_SESSION['user_id'])) {
                jsonResponse([
                    'success' => false,
                    'error' => 'Não autenticado'
                ], 401);
            }
            
            $stats = $db->getSystemStats();
            
            jsonResponse([
                'success' => true,
                'stats' => $stats,
                'environment' => ENVIRONMENT,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            break;
            
        default:
            jsonResponse([
                'success' => false,
                'error' => 'Ação inválida',
                'available_actions' => [
                    'login', 'logout', 'check_session', 'get_current_user',
                    'register', 'change_password', 'test_connection', 'stats'
                ]
            ], 400);
    }
    
} catch (Exception $e) {
    logError("Erro na API de autenticação", [
        'action' => $action,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    
    jsonResponse([
        'success' => false,
        'error' => 'Erro interno do servidor'
    ], 500);
}

/**
 * Determinar página de redirecionamento baseado no role
 */
function getRedirectByRole($role) {
    switch ($role) {
        case 'gerente':
            return 'gerente.php';
        case 'proprietario':
            return 'proprietario.php';
        case 'veterinario':
            return 'veterinario.php';
        case 'funcionario':
        default:
            return 'funcionario.php';
    }
}
?>
