<?php
/**
 * KRON - Google OAuth Callback Handler
 */

session_start();

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/GoogleOAuth.php';

$error = 'Erro ao autenticar com Google. Tente novamente.';

// Verificar se há código de autorização
if (!isset($_GET['code'])) {
    $_SESSION['google_error'] = $error;
    header('Location: login.php');
    exit;
}

$code = $_GET['code'];

// Recuperar state da URL ou da sessão (backup)
$state = $_GET['state'] ?? $_SESSION['google_oauth_action'] ?? 'login';

// Limpar da sessão após usar
unset($_SESSION['google_oauth_action']);

// Validar state
if (!in_array($state, ['login', 'register'])) {
    $state = 'login'; // Default seguro
}

try {
    $googleOAuth = new GoogleOAuth();
    
    // Trocar código por token de acesso
    $tokenData = $googleOAuth->getAccessToken($code);
    
    if (!$tokenData || !isset($tokenData['access_token'])) {
        throw new Exception('Não foi possível obter o token de acesso');
    }
    
    // Obter informações do usuário
    $userInfo = $googleOAuth->getUserInfo($tokenData['access_token']);
    
    if (!$userInfo || !isset($userInfo['email'])) {
        throw new Exception('Não foi possível obter informações do usuário');
    }
    
    $email = $userInfo['email'];
    $googleId = $userInfo['id'];
    $name = $userInfo['name'] ?? '';
    $picture = $userInfo['picture'] ?? null;
    $emailVerified = $userInfo['verified_email'] ?? false;
    
    // Conectar ao banco de dados
    $pdo = getKronDatabase();
    
    if (!$pdo) {
        throw new Exception('Erro ao conectar ao banco de dados');
    }
    
    // Verificar se usuário já existe pelo email ou google_id
    $stmt = $pdo->prepare("
        SELECT id, email, name, is_active, google_id, password 
        FROM kron_users 
        WHERE email = ? OR google_id = ?
    ");
    $stmt->execute([$email, $googleId]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Usuário já existe - fazer login
        
        // Atualizar google_id e foto se ainda não tiver
        if (empty($user['google_id'])) {
            $updateStmt = $pdo->prepare("
                UPDATE kron_users 
                SET google_id = ?, email_verified = 1, email_verified_at = NOW(), 
                    avatar_url = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            $updateStmt->execute([$googleId, $picture, $user['id']]);
        } else {
            // Atualizar foto do Google (pode ter mudado)
            $updateStmt = $pdo->prepare("
                UPDATE kron_users 
                SET avatar_url = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            $updateStmt->execute([$picture, $user['id']]);
        }
        
        // Verificar se conta está ativa
        if (!$user['is_active']) {
            $_SESSION['google_error'] = 'Sua conta está inativa. Entre em contato com o administrador.';
            header('Location: login.php');
            exit;
        }
        
        // Atualizar último login
        $updateStmt = $pdo->prepare("UPDATE kron_users SET last_login = NOW() WHERE id = ?");
        $updateStmt->execute([$user['id']]);
        
        // Criar sessão
        $sessionToken = bin2hex(random_bytes(32));
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        $stmt = $pdo->prepare("
            INSERT INTO kron_user_sessions (user_id, session_token, ip_address, user_agent, expires_at) 
            VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 30 DAY))
        ");
        $stmt->execute([$user['id'], $sessionToken, $ipAddress, $userAgent]);
        
        // Criar sessão PHP
        $_SESSION['kron_logged_in'] = true;
        $_SESSION['kron_user_id'] = $user['id'];
        $_SESSION['kron_user_email'] = $user['email'];
        $_SESSION['kron_user_name'] = $user['name'];
        $_SESSION['kron_session_token'] = $sessionToken;
        
        header('Location: dashboard/index.php');
        exit;
        
    } else {
        // Usuário não existe no banco
        
        // Se veio do login, redirecionar para cadastro
        if ($state === 'login') {
            $_SESSION['google_error'] = 'Conta não encontrada. Por favor, cadastre-se primeiro.';
            header('Location: register.php');
            exit;
        }
        
        // Se veio do register, criar novo usuário
        // Se state não for 'register', ainda assim criar (pode ser que o state não tenha vindo)
        
        // Criar novo usuário
        $stmt = $pdo->prepare("
            INSERT INTO kron_users 
            (email, password, name, google_id, avatar_url, is_active, email_verified, email_verified_at) 
            VALUES (?, ?, ?, ?, ?, 1, 1, NOW())
        ");
        
        // Senha aleatória (não será usada pois login é via Google)
        $randomPassword = bin2hex(random_bytes(32));
        $passwordHash = password_hash($randomPassword, PASSWORD_DEFAULT);
        
        try {
            $stmt->execute([
                $email,
                $passwordHash,
                $name,
                $googleId,
                $picture
            ]);
            
            $userId = $pdo->lastInsertId();
            
            if (!$userId) {
                throw new Exception('Erro ao criar usuário no banco de dados');
            }
        } catch (PDOException $e) {
            error_log("KRON Google Callback - Erro ao criar usuário: " . $e->getMessage());
            $_SESSION['google_error'] = 'Erro ao criar conta. Tente novamente.';
            header('Location: register.php');
            exit;
        }
        
        // Criar sessão
        $sessionToken = bin2hex(random_bytes(32));
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        $stmt = $pdo->prepare("
            INSERT INTO kron_user_sessions (user_id, session_token, ip_address, user_agent, expires_at) 
            VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 30 DAY))
        ");
        $stmt->execute([$userId, $sessionToken, $ipAddress, $userAgent]);
        
        // Criar sessão PHP
        $_SESSION['kron_logged_in'] = true;
        $_SESSION['kron_user_id'] = $userId;
        $_SESSION['kron_user_email'] = $email;
        $_SESSION['kron_user_name'] = $name;
        $_SESSION['kron_session_token'] = $sessionToken;
        
        header('Location: dashboard/index.php');
        exit;
    }
    
} catch (Exception $e) {
    error_log("KRON Google Callback Error: " . $e->getMessage());
    $_SESSION['google_error'] = $e->getMessage();
    header('Location: ' . ($state === 'register' ? 'register.php' : 'login.php'));
    exit;
}

