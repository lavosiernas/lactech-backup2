<?php
/**
 * SafeCode IDE - OAuth Handlers
 * Endpoints para Google e GitHub OAuth
 */

require_once __DIR__ . '/config.php';
session_start();

// Configuração temporária do GitHub OAuth - Mova para variáveis de ambiente em produção
if (!getenv('GITHUB_CLIENT_ID')) {
    putenv('GITHUB_CLIENT_ID=Ov23li5aH4Ep6CFMq5Kn');
    putenv('GITHUB_CLIENT_SECRET=4f1efa1a8a9734b07c2f9072d410bd695fbffe9f');
}

// Configuração temporária do Google OAuth - Mova para variáveis de ambiente em produção
if (!getenv('GOOGLE_CLIENT_ID')) {
    putenv('GOOGLE_CLIENT_ID=563053705449-rlev4bkd3rvsjnttj2j2oq9qv8a3c2hi.apps.googleusercontent.com');
    putenv('GOOGLE_CLIENT_SECRET=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');
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'google':
        handleGoogleAuth();
        break;
    case 'github':
        handleGitHubAuth();
        break;
    case 'callback':
        handleOAuthCallback();
        break;
    default:
        jsonResponse(['success' => false, 'error' => 'Ação inválida'], 400);
}

/**
 * Iniciar autenticação Google
 */
function handleGoogleAuth() {
    // Configurações do Google OAuth (você precisa configurar isso)
    $clientId = getenv('GOOGLE_CLIENT_ID') ?: '';
    $redirectUri = getCurrentUrl() . '/safecode/api/oauth.php?action=callback&provider=google';
    
    if (empty($clientId)) {
        jsonResponse([
            'success' => false, 
            'error' => 'Google OAuth não configurado. Configure GOOGLE_CLIENT_ID no ambiente.'
        ], 500);
    }
    
    $params = [
        'client_id' => $clientId,
        'redirect_uri' => $redirectUri,
        'response_type' => 'code',
        'scope' => 'openid email profile',
        'access_type' => 'offline',
        'prompt' => 'consent'
    ];
    
    $authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
    
    $_SESSION['oauth_state'] = bin2hex(random_bytes(16));
    $_SESSION['oauth_provider'] = 'google';
    
    header('Location: ' . $authUrl);
    exit;
}

/**
 * Iniciar autenticação GitHub
 */
function handleGitHubAuth() {
    // Configurações do GitHub OAuth (você precisa configurar isso)
    $clientId = getenv('GITHUB_CLIENT_ID') ?: '';
    $redirectUri = getCurrentUrl() . '/safecode/api/oauth.php?action=callback&provider=github';
    
    if (empty($clientId)) {
        jsonResponse([
            'success' => false, 
            'error' => 'GitHub OAuth não configurado. Configure GITHUB_CLIENT_ID no ambiente.'
        ], 500);
    }
    
    $state = bin2hex(random_bytes(16));
    $_SESSION['oauth_state'] = $state;
    $_SESSION['oauth_provider'] = 'github';
    
    $params = [
        'client_id' => $clientId,
        'redirect_uri' => $redirectUri,
        'scope' => 'user:email',
        'state' => $state
    ];
    
    $authUrl = 'https://github.com/login/oauth/authorize?' . http_build_query($params);
    
    header('Location: ' . $authUrl);
    exit;
}

/**
 * Processar callback do OAuth
 */
function handleOAuthCallback() {
    $provider = $_GET['provider'] ?? '';
    $code = $_GET['code'] ?? '';
    $state = $_GET['state'] ?? '';
    
    if (empty($provider) || empty($code)) {
        jsonResponse(['success' => false, 'error' => 'Parâmetros inválidos'], 400);
    }
    
    // Verificar state (GitHub)
    if ($provider === 'github' && (!isset($_SESSION['oauth_state']) || $state !== $_SESSION['oauth_state'])) {
        jsonResponse(['success' => false, 'error' => 'State inválido'], 400);
    }
    
    try {
        if ($provider === 'google') {
            $userData = handleGoogleCallback($code);
        } elseif ($provider === 'github') {
            $userData = handleGitHubCallback($code);
        } else {
            jsonResponse(['success' => false, 'error' => 'Provider inválido'], 400);
        }
        
        // Criar ou atualizar usuário
        $db = getDatabase();
        if (!$db) {
            jsonResponse(['success' => false, 'error' => 'Erro de conexão com banco de dados'], 500);
        }
        
        // Buscar ou criar usuário
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? OR (provider = ? AND provider_id = ?)");
        $stmt->execute([$userData['email'], $provider, $userData['id']]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Atualizar usuário existente
            $stmt = $db->prepare("
                UPDATE users 
                SET name = ?, avatar_url = ?, provider = ?, provider_id = ?, last_login = NOW(), updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([
                $userData['name'],
                $userData['avatar'] ?? null,
                $provider,
                $userData['id'],
                $user['id']
            ]);
            $userId = $user['id'];
        } else {
            // Criar novo usuário
            $stmt = $db->prepare("
                INSERT INTO users (email, name, avatar_url, provider, provider_id, is_active) 
                VALUES (?, ?, ?, ?, ?, 1)
            ");
            $stmt->execute([
                $userData['email'],
                $userData['name'],
                $userData['avatar'] ?? null,
                $provider,
                $userData['id']
            ]);
            $userId = $db->lastInsertId();
        }
        
        // Buscar dados atualizados
        $stmt = $db->prepare("SELECT id, email, name, avatar_url, provider FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        // Gerar token
        $token = generateToken($userId, $user['email']);
        
        // Limpar sessão
        unset($_SESSION['oauth_state']);
        unset($_SESSION['oauth_provider']);
        
        // Redirecionar para o frontend com token
        $frontendUrl = getCurrentUrl() . '/safecode/oauth-callback?token=' . urlencode($token) . '&user=' . urlencode(json_encode($user));
        header('Location: ' . $frontendUrl);
        exit;
        
    } catch (Exception $e) {
        error_log("OAuth callback error: " . $e->getMessage());
        jsonResponse(['success' => false, 'error' => 'Erro ao processar autenticação'], 500);
    }
}

/**
 * Processar callback do Google
 */
function handleGoogleCallback($code) {
    $clientId = getenv('GOOGLE_CLIENT_ID') ?: '';
    $clientSecret = getenv('GOOGLE_CLIENT_SECRET') ?: '';
    $redirectUri = getCurrentUrl() . '/safecode/api/oauth.php?action=callback&provider=google';
    
    // Trocar código por token
    $tokenUrl = 'https://oauth2.googleapis.com/token';
    $tokenData = [
        'code' => $code,
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'redirect_uri' => $redirectUri,
        'grant_type' => 'authorization_code'
    ];
    
    $ch = curl_init($tokenUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($tokenData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $tokenResponse = json_decode($response, true);
    
    if (!isset($tokenResponse['access_token'])) {
        throw new Exception('Falha ao obter token do Google');
    }
    
    // Obter dados do usuário
    $userUrl = 'https://www.googleapis.com/oauth2/v2/userinfo?access_token=' . $tokenResponse['access_token'];
    $userResponse = file_get_contents($userUrl);
    $userData = json_decode($userResponse, true);
    
    return [
        'id' => $userData['id'],
        'email' => $userData['email'],
        'name' => $userData['name'],
        'avatar' => $userData['picture'] ?? null
    ];
}

/**
 * Processar callback do GitHub
 */
function handleGitHubCallback($code) {
    $clientId = getenv('GITHUB_CLIENT_ID') ?: '';
    $clientSecret = getenv('GITHUB_CLIENT_SECRET') ?: '';
    $redirectUri = getCurrentUrl() . '/safecode/api/oauth.php?action=callback&provider=github';
    
    // Trocar código por token
    $tokenUrl = 'https://github.com/login/oauth/access_token';
    $tokenData = [
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'code' => $code,
        'redirect_uri' => $redirectUri
    ];
    
    $ch = curl_init($tokenUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($tokenData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $tokenResponse = json_decode($response, true);
    
    if (!isset($tokenResponse['access_token'])) {
        throw new Exception('Falha ao obter token do GitHub');
    }
    
    // Obter dados do usuário
    $userUrl = 'https://api.github.com/user';
    $ch = curl_init($userUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: token ' . $tokenResponse['access_token'],
        'User-Agent: SafeCode-IDE'
    ]);
    
    $userResponse = curl_exec($ch);
    curl_close($ch);
    
    $userData = json_decode($userResponse, true);
    
    // Obter email (pode estar privado)
    $email = $userData['email'] ?? null;
    if (!$email) {
        $emailUrl = 'https://api.github.com/user/emails';
        $ch = curl_init($emailUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: token ' . $tokenResponse['access_token'],
            'User-Agent: SafeCode-IDE'
        ]);
        $emailResponse = curl_exec($ch);
        curl_close($ch);
        $emails = json_decode($emailResponse, true);
        if (is_array($emails) && count($emails) > 0) {
            $email = $emails[0]['email'] ?? null;
        }
    }
    
    return [
        'id' => (string)$userData['id'],
        'email' => $email ?: $userData['login'] . '@github.local',
        'name' => $userData['name'] ?? $userData['login'],
        'avatar' => $userData['avatar_url'] ?? null
    ];
}

/**
 * Obter URL atual
 */
function getCurrentUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $protocol . '://' . $host;
}

