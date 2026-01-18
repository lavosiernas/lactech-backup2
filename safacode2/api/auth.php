<?php
/**
 * SafeCode IDE - Authentication API
 * Endpoints: /api/auth.php?action=login|register|logout|me
 */

require_once __DIR__ . '/config.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'register':
        handleRegister();
        break;
    case 'login':
        handleLogin();
        break;
    case 'logout':
        handleLogout();
        break;
    case 'me':
        handleGetUser();
        break;
    default:
        jsonResponse(['success' => false, 'error' => 'Ação inválida'], 400);
}

/**
 * Registrar novo usuário
 */
function handleRegister() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['success' => false, 'error' => 'Método não permitido'], 405);
    }
    
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';
    $name = trim($input['name'] ?? '');
    
    // Validações
    if (empty($email) || empty($password) || empty($name)) {
        jsonResponse(['success' => false, 'error' => 'Todos os campos são obrigatórios'], 400);
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(['success' => false, 'error' => 'Email inválido'], 400);
    }
    
    if (strlen($password) < 6) {
        jsonResponse(['success' => false, 'error' => 'A senha deve ter pelo menos 6 caracteres'], 400);
    }
    
    $db = getDatabase();
    if (!$db) {
        jsonResponse(['success' => false, 'error' => 'Erro de conexão com banco de dados'], 500);
    }
    
    // Verificar se email já existe
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        jsonResponse(['success' => false, 'error' => 'Este email já está cadastrado'], 409);
    }
    
    // Criar usuário
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare("INSERT INTO users (email, password_hash, name) VALUES (?, ?, ?)");
    
    try {
        $stmt->execute([$email, $passwordHash, $name]);
        $userId = $db->lastInsertId();
        
        // Gerar token
        $token = generateToken($userId, $email);
        
        // Retornar dados do usuário
        jsonResponse([
            'success' => true,
            'token' => $token,
            'user' => [
                'id' => $userId,
                'email' => $email,
                'name' => $name,
            ]
        ]);
    } catch (PDOException $e) {
        jsonResponse(['success' => false, 'error' => 'Erro ao criar usuário'], 500);
    }
}

/**
 * Login
 */
function handleLogin() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['success' => false, 'error' => 'Método não permitido'], 405);
    }
    
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';
    
    // Validações
    if (empty($email) || empty($password)) {
        jsonResponse(['success' => false, 'error' => 'Email e senha são obrigatórios'], 400);
    }
    
    $db = getDatabase();
    if (!$db) {
        jsonResponse(['success' => false, 'error' => 'Erro de conexão com banco de dados'], 500);
    }
    
    // Buscar usuário
    $stmt = $db->prepare("SELECT id, email, password_hash, name, avatar_url, is_active FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        jsonResponse(['success' => false, 'error' => 'Email ou senha incorretos'], 401);
    }
    
    if (!$user['is_active']) {
        jsonResponse(['success' => false, 'error' => 'Conta desativada'], 403);
    }
    
    // Verificar senha
    if (!password_verify($password, $user['password_hash'])) {
        jsonResponse(['success' => false, 'error' => 'Email ou senha incorretos'], 401);
    }
    
    // Atualizar último login
    $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    $stmt->execute([$user['id']]);
    
    // Gerar token
    $token = generateToken($user['id'], $user['email']);
    
    // Retornar dados do usuário
    jsonResponse([
        'success' => true,
        'token' => $token,
        'user' => [
            'id' => $user['id'],
            'email' => $user['email'],
            'name' => $user['name'],
            'avatar_url' => $user['avatar_url'],
        ]
    ]);
}

/**
 * Logout
 */
function handleLogout() {
    // Com JWT stateless, logout é apenas remover token do cliente
    jsonResponse(['success' => true, 'message' => 'Logout realizado com sucesso']);
}

/**
 * Obter dados do usuário atual
 */
function handleGetUser() {
    $token = getAuthToken();
    
    if (!$token) {
        jsonResponse(['success' => false, 'error' => 'Token não fornecido'], 401);
    }
    
    $payload = verifyToken($token);
    
    if (!$payload) {
        jsonResponse(['success' => false, 'error' => 'Token inválido ou expirado'], 401);
    }
    
    $db = getDatabase();
    if (!$db) {
        jsonResponse(['success' => false, 'error' => 'Erro de conexão com banco de dados'], 500);
    }
    
    $stmt = $db->prepare("SELECT id, email, name, avatar_url, created_at, last_login FROM users WHERE id = ? AND is_active = 1");
    $stmt->execute([$payload['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user) {
        jsonResponse(['success' => false, 'error' => 'Usuário não encontrado'], 404);
    }
    
    jsonResponse([
        'success' => true,
        'user' => $user
    ]);
}

