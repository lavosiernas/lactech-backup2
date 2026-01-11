<?php
/**
 * SafeNode - Google OAuth Callback Handler
 */

session_start();

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/GoogleOAuth.php';
// 2FA removido - require_once __DIR__ . '/includes/TwoFactorAuth.php';

$error = 'Erro ao autenticar com Google. Tente novamente.';

// Verificar se há erro do Google
if (isset($_GET['error'])) {
    $googleError = $_GET['error'] ?? 'unknown_error';
    $errorDescription = $_GET['error_description'] ?? 'Erro desconhecido';
    
    error_log("SafeNode Google OAuth Error: $googleError - $errorDescription");
    
    // Erro comum: redirect_uri_mismatch significa que a URL não está registrada no Google Console
    if ($googleError === 'redirect_uri_mismatch') {
        $_SESSION['google_error'] = 'A URL de callback não está configurada corretamente no Google Console. Entre em contato com o administrador.';
    } else {
        $_SESSION['google_error'] = 'Erro ao autenticar com Google: ' . htmlspecialchars($errorDescription);
    }
    
    header('Location: login.php');
    exit;
}

// Verificar se há código de autorização
if (!isset($_GET['code'])) {
    error_log("SafeNode Google OAuth: Código de autorização não recebido. GET params: " . json_encode($_GET));
    $_SESSION['google_error'] = $error;
    header('Location: login.php');
    exit;
}

$code = $_GET['code'];
$state = $_GET['state'] ?? 'login'; // 'login' ou 'register'

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
    $pdo = getSafeNodeDatabase();
    
    if (!$pdo) {
        throw new Exception('Erro ao conectar ao banco de dados');
    }
    
    // Verificar se usuário já existe pelo email ou google_id
    $stmt = $pdo->prepare("
        SELECT id, username, email, full_name, role, is_active, google_id 
        FROM safenode_users 
        WHERE email = ? OR google_id = ?
    ");
    $stmt->execute([$email, $googleId]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Usuário já existe - fazer login
        
        // Atualizar google_id e foto se ainda não tiver
        if (empty($user['google_id'])) {
            $updateStmt = $pdo->prepare("UPDATE safenode_users SET google_id = ?, email_verified = 1, email_verified_at = NOW(), avatar_url = ?, avatar_updated_at = NOW() WHERE id = ?");
            $updateStmt->execute([$googleId, $picture, $user['id']]);
        } else {
            // Atualizar foto do Google (pode ter mudado)
            $updateStmt = $pdo->prepare("UPDATE safenode_users SET avatar_url = ?, avatar_updated_at = NOW() WHERE id = ?");
            $updateStmt->execute([$picture, $user['id']]);
        }
        
        // Verificar se conta está ativa
        if (!$user['is_active']) {
            $_SESSION['google_error'] = 'Sua conta está inativa. Entre em contato com o administrador.';
            header('Location: login.php');
            exit;
        }
        
        // Atualizar último login
        $updateStmt = $pdo->prepare("UPDATE safenode_users SET last_login = NOW() WHERE id = ?");
        $updateStmt->execute([$user['id']]);
        
        // 2FA removido - criar sessão normalmente
        $_SESSION['safenode_logged_in'] = true;
        $_SESSION['safenode_username'] = $user['username'];
        $_SESSION['safenode_user_id'] = $user['id'];
        $_SESSION['safenode_user_email'] = $user['email'];
        $_SESSION['safenode_user_full_name'] = $user['full_name'];
        $_SESSION['safenode_user_role'] = $user['role'];
        $_SESSION['safenode_avatar_url'] = $picture;
        $_SESSION['show_update_modal'] = true; // Mostrar modal de atualização
        
        header('Location: dashboard.php');
        exit;
        
    } else {
        // Usuário não existe - criar novo cadastro
        
        // Gerar username a partir do email
        $username = explode('@', $email)[0];
        $baseUsername = preg_replace('/[^a-zA-Z0-9]/', '', $username);
        $finalUsername = $baseUsername;
        
        // Verificar se username já existe e adicionar número se necessário
        $counter = 1;
        while (true) {
            $checkStmt = $pdo->prepare("SELECT id FROM safenode_users WHERE username = ?");
            $checkStmt->execute([$finalUsername]);
            if (!$checkStmt->fetch()) {
                break;
            }
            $finalUsername = $baseUsername . $counter;
            $counter++;
        }
        
        // Criar usuário
        $stmt = $pdo->prepare("
            INSERT INTO safenode_users 
            (username, email, password_hash, full_name, role, is_active, email_verified, email_verified_at, google_id, avatar_url, avatar_updated_at) 
            VALUES (?, ?, ?, ?, 'user', 1, 1, NOW(), ?, ?, NOW())
        ");
        
        // Senha aleatória (não será usada pois login é via Google)
        $randomPassword = bin2hex(random_bytes(32));
        $passwordHash = password_hash($randomPassword, PASSWORD_DEFAULT);
        
        $stmt->execute([
            $finalUsername,
            $email,
            $passwordHash,
            $name,
            $googleId,
            $picture
        ]);
        
        $userId = $pdo->lastInsertId();
        
        // Novo usuário não tem 2FA ativado, então faz login direto
        $_SESSION['safenode_logged_in'] = true;
        $_SESSION['safenode_username'] = $finalUsername;
        $_SESSION['safenode_user_id'] = $userId;
        $_SESSION['safenode_email'] = $email;
        $_SESSION['safenode_user_email'] = $email;
        $_SESSION['safenode_full_name'] = $name;
        $_SESSION['safenode_user_full_name'] = $name;
        $_SESSION['safenode_user_role'] = 'user';
        $_SESSION['safenode_avatar_url'] = $picture;
        $_SESSION['show_update_modal'] = true; // Mostrar modal de atualização
        
        header('Location: dashboard.php');
        exit;
    }
    
} catch (Exception $e) {
    error_log("SafeNode Google OAuth Error: " . $e->getMessage());
    $_SESSION['google_error'] = $error;
    header('Location: ' . ($state === 'register' ? 'register.php' : 'login.php'));
    exit;
}

