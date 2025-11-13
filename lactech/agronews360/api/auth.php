<?php
/**
 * API de Autenticação - AgroNews360
 * Login integrado com Lactech (credenciais ou Google)
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/Database.class.php';
require_once __DIR__ . '/../includes/LactechIntegration.class.php';

$action = $_GET['action'] ?? $_POST['action'] ?? json_decode(file_get_contents('php://input'), true)['action'] ?? '';

try {
    switch ($action) {
        case 'login':
            handleLogin();
            break;
            
        case 'logout':
            handleLogout();
            break;
            
        case 'check_session':
            checkSession();
            break;
            
        case 'get_google_auth_url':
            getGoogleAuthUrl();
            break;
            
        case 'google_callback':
            handleGoogleCallback();
            break;
            
        case 'google_callback_lactech':
            handleGoogleCallbackLactech();
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Ação não encontrada']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

/**
 * Login com email/senha (verifica no Lactech)
 */
function handleLogin() {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'error' => 'Email e senha são obrigatórios']);
        return;
    }
    
    $integration = new LactechIntegration();
    
    if (!$integration->isLactechConnected()) {
        echo json_encode(['success' => false, 'error' => 'Sistema Lactech não está disponível']);
        return;
    }
    
    // Verificar credenciais no banco do Lactech
    try {
        $lactechDb = $integration->getLactechConnection();
        
        // Buscar usuário no Lactech
        $stmt = $lactechDb->prepare("
            SELECT u.*, f.name as farm_name 
            FROM users u
            LEFT JOIN farms f ON u.farm_id = f.id
            WHERE u.email = ? AND u.is_active = 1
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            echo json_encode(['success' => false, 'error' => 'Email ou senha incorretos']);
            return;
        }
        
        // Verificar senha
        if (!password_verify($password, $user['password'])) {
            echo json_encode(['success' => false, 'error' => 'Email ou senha incorretos']);
            return;
        }
        
        // Criar/atualizar usuário no AgroNews
        $agronewsDb = Database::getInstance();
        $pdo = $agronewsDb->getConnection();
        
        // Verificar se já existe
        $existing = $agronewsDb->query(
            "SELECT id FROM users WHERE lactech_user_id = ? OR email = ?",
            [$user['id'], $email]
        );
        
        if (empty($existing)) {
            // Criar usuário no AgroNews
            $stmt = $pdo->prepare("
                INSERT INTO users (name, email, password, role, is_active, lactech_user_id) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            // Todos os usuários são tratados igualmente (sem distinção de admin)
            $defaultRole = 'viewer';
            $stmt->execute([
                $user['name'],
                $user['email'],
                $user['password'], // Manter mesma senha (hash)
                $defaultRole,
                $user['is_active'],
                $user['id']
            ]);
            
            $agronewsUserId = $pdo->lastInsertId();
        } else {
            // Atualizar usuário
            $agronewsUserId = $existing[0]['id'];
            $stmt = $pdo->prepare("
                UPDATE users 
                SET name = ?, email = ?, role = ?, is_active = ?, lactech_user_id = ?
                WHERE id = ?
            ");
            
            // Todos os usuários são tratados igualmente (sem distinção de admin)
            $defaultRole = 'viewer';
            $stmt->execute([
                $user['name'],
                $user['email'],
                $defaultRole,
                $user['is_active'],
                $user['id'],
                $agronewsUserId
            ]);
        }
        
        // Criar sessão
        $_SESSION['agronews_user_id'] = $agronewsUserId;
        $_SESSION['agronews_user_email'] = $user['email'];
        $_SESSION['agronews_user_name'] = $user['name'];
        $_SESSION['agronews_user_role'] = 'viewer'; // Role padrão para todos
        $_SESSION['agronews_lactech_user_id'] = $user['id'];
        $_SESSION['agronews_farm_id'] = $user['farm_id'] ?? null;
        $_SESSION['agronews_farm_name'] = $user['farm_name'] ?? null;
        $_SESSION['agronews_logged_in'] = true;
        
        echo json_encode([
            'success' => true,
            'user' => [
                'id' => $agronewsUserId,
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => 'viewer', // Role padrão para todos
                'farm_name' => $user['farm_name'] ?? null
            ],
            'redirect' => 'index.php'
        ]);
        
    } catch (Exception $e) {
        error_log("Erro no login: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Erro ao fazer login']);
    }
}

/**
 * Login com Google (AgroNews360 - Independente, mas sincroniza com Lactech se necessário)
 */
function handleGoogleCallback() {
    $code = $_GET['code'] ?? '';
    $state = $_GET['state'] ?? '';
    
    if (empty($code)) {
        header('Location: ../login.php?error_message=' . urlencode('Código de autorização não recebido'));
        exit;
    }
    
    // Verificar state (CSRF protection)
    if (!isset($_SESSION['google_login_state']) || $_SESSION['google_login_state'] !== $state) {
        header('Location: ../login.php?error_message=' . urlencode('Estado de segurança inválido'));
        exit;
    }
    unset($_SESSION['google_login_state']); // Limpar state após uso
    
    // Carregar configuração do Google do AgroNews360 (independente)
    $googleConfigPath = __DIR__ . '/../includes/config_google.php';
    if (!file_exists($googleConfigPath)) {
        header('Location: ../login.php?error_message=' . urlencode('Configuração do Google não encontrada'));
        exit;
    }
    
    require_once $googleConfigPath;
    
    if (empty(GOOGLE_CLIENT_ID) || empty(GOOGLE_CLIENT_SECRET)) {
        header('Location: ../login.php?error_message=' . urlencode('Credenciais do Google não configuradas'));
        exit;
    }
    
    // URL de redirecionamento (usar a definida no config)
    $redirectUri = defined('GOOGLE_REDIRECT_URI') ? GOOGLE_REDIRECT_URI : 
                   ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'https') . 
                   '://' . ($_SERVER['HTTP_HOST'] ?? 'lactechsys.com') . 
                   '/agronews360/api/auth.php?action=google_callback';
    
    // Trocar código por token
    $tokenUrl = 'https://oauth2.googleapis.com/token';
    $tokenData = [
        'code' => $code,
        'client_id' => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri' => $redirectUri,
        'grant_type' => 'authorization_code'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $tokenUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($tokenData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    $tokenResponse = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        $errorData = json_decode($tokenResponse, true);
        $errorMsg = $errorData['error_description'] ?? $errorData['error'] ?? 'Erro ao obter token do Google';
        error_log("Erro ao obter token: HTTP $httpCode - " . $tokenResponse);
        header('Location: ../login.php?error_message=' . urlencode($errorMsg));
        exit;
    }
    
    $tokenData = json_decode($tokenResponse, true);
    $accessToken = $tokenData['access_token'] ?? null;
    
    if (!$accessToken) {
        $errorMsg = isset($tokenData['error_description']) ? $tokenData['error_description'] : 'Token de acesso não recebido';
        error_log("Token não recebido: " . json_encode($tokenData));
        header('Location: ../login.php?error_message=' . urlencode($errorMsg));
        exit;
    }
    
    // Buscar informações do usuário no Google
    $userInfoUrl = 'https://www.googleapis.com/oauth2/v3/userinfo';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $userInfoUrl);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer {$accessToken}"]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $userInfoResponse = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        header('Location: ../login.php?error_message=' . urlencode('Erro ao obter informações do usuário Google'));
        exit;
    }
    
    $googleUser = json_decode($userInfoResponse, true);
    $googleEmail = $googleUser['email'] ?? null;
    $googleId = $googleUser['sub'] ?? null; // 'sub' é o ID único do Google
    $googleName = $googleUser['name'] ?? $googleUser['email'] ?? 'Usuário Google';
    $googlePicture = $googleUser['picture'] ?? null;
    
    if (!$googleEmail || !$googleId) {
        header('Location: ../login.php?error_message=' . urlencode('Informações essenciais do Google não recebidas'));
        exit;
    }
    
    try {
        // Criar/atualizar usuário no AgroNews360 (INDEPENDENTE)
        $agronewsDb = Database::getInstance();
        $pdo = $agronewsDb->getConnection();
        
        // Verificar se já existe usuário com esse Google ID ou email
        $existing = $agronewsDb->query(
            "SELECT id, lactech_user_id FROM users WHERE google_id = ? OR email = ?",
            [$googleId, $googleEmail]
        );
        
        $lactechUserId = null;
        
        // Se não existe, tentar sincronizar com Lactech (opcional - ecossistema)
        if (empty($existing)) {
            $integration = new LactechIntegration();
            if ($integration->isLactechConnected()) {
                try {
                    $lactechDb = $integration->getLactechConnection();
                    $stmt = $lactechDb->prepare("SELECT id, name, email, role, is_active FROM users WHERE email = ? AND is_active = 1");
                    $stmt->execute([$googleEmail]);
                    $lactechUser = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($lactechUser) {
                        $lactechUserId = $lactechUser['id'];
                    }
                } catch (Exception $e) {
                    // Se falhar, continua sem sincronização
                    error_log("Erro ao sincronizar com Lactech: " . $e->getMessage());
                }
            }
        } else {
            $lactechUserId = $existing[0]['lactech_user_id'] ?? null;
        }
        
        if (empty($existing)) {
            // Criar novo usuário
            $stmt = $pdo->prepare("
                INSERT INTO users (name, email, password, role, is_active, google_id, google_picture, lactech_user_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $tempPassword = password_hash('google_' . time() . $googleId, PASSWORD_DEFAULT);
            $defaultRole = 'viewer'; // Role padrão para novos usuários Google
            
            $stmt->execute([
                $googleName,
                $googleEmail,
                $tempPassword,
                $defaultRole,
                1, // is_active
                $googleId,
                $googlePicture,
                $lactechUserId
            ]);
            
            $agronewsUserId = $pdo->lastInsertId();
        } else {
            // Atualizar usuário existente
            $agronewsUserId = $existing[0]['id'];
            $stmt = $pdo->prepare("
                UPDATE users 
                SET name = ?, email = ?, google_id = ?, google_picture = ?, lactech_user_id = COALESCE(?, lactech_user_id)
                WHERE id = ?
            ");
            
            $stmt->execute([
                $googleName,
                $googleEmail,
                $googleId,
                $googlePicture,
                $lactechUserId,
                $agronewsUserId
            ]);
        }
        
        // Buscar dados completos do usuário
        $userData = $agronewsDb->query("SELECT * FROM users WHERE id = ?", [$agronewsUserId]);
        $user = $userData[0] ?? null;
        
        if (!$user) {
            header('Location: ../login.php?error_message=' . urlencode('Erro ao criar/atualizar usuário'));
            exit;
        }
        
        // Criar sessão
        $_SESSION['agronews_user_id'] = $agronewsUserId;
        $_SESSION['agronews_user_email'] = $user['email'];
        $_SESSION['agronews_user_name'] = $user['name'];
        $_SESSION['agronews_user_role'] = 'viewer'; // Role padrão para todos
        $_SESSION['agronews_google_id'] = $googleId;
        $_SESSION['agronews_google_picture'] = $googlePicture;
        if ($lactechUserId) {
            $_SESSION['agronews_lactech_user_id'] = $lactechUserId;
        }
        $_SESSION['agronews_logged_in'] = true;
        
        // Redirecionar
        header('Location: ../index.php?success_message=' . urlencode('Login com Google realizado com sucesso!'));
        exit;
        
    } catch (Exception $e) {
        error_log("Erro no login Google: " . $e->getMessage());
        header('Location: ../login.php?error_message=' . urlencode('Erro ao fazer login com Google: ' . $e->getMessage()));
        exit;
    }
}

/**
 * Logout
 */
function handleLogout() {
    // Limpar todas as variáveis de sessão do AgroNews
    unset($_SESSION['agronews_user_id']);
    unset($_SESSION['agronews_user_email']);
    unset($_SESSION['agronews_user_name']);
    unset($_SESSION['agronews_user_role']);
    unset($_SESSION['agronews_lactech_user_id']);
    unset($_SESSION['agronews_farm_id']);
    unset($_SESSION['agronews_farm_name']);
    unset($_SESSION['agronews_logged_in']);
    unset($_SESSION['agronews_google_id']);
    unset($_SESSION['agronews_google_picture']);
    
    // Destruir sessão
    session_destroy();
    
    // Redirecionar para a página inicial com mensagem de sucesso
    header('Location: ../index.php?logout_success=1');
    exit;
}

/**
 * Verificar sessão
 */
function checkSession() {
    if (isset($_SESSION['agronews_logged_in']) && $_SESSION['agronews_logged_in']) {
        echo json_encode([
            'success' => true,
            'logged_in' => true,
            'user' => [
                'id' => $_SESSION['agronews_user_id'] ?? null,
                'name' => $_SESSION['agronews_user_name'] ?? null,
                'email' => $_SESSION['agronews_user_email'] ?? null,
                'role' => $_SESSION['agronews_user_role'] ?? null
            ]
        ]);
    } else {
        echo json_encode(['success' => true, 'logged_in' => false]);
    }
}

/**
 * Obter URL de autenticação do Google
 */
function getGoogleAuthUrl() {
    // Determinar tipo de login (agronews ou lactech)
    $type = $_GET['type'] ?? 'agronews';
    
    // Carregar configuração do Google baseado no tipo
    if ($type === 'lactech') {
        // Usar configuração do Lactech
        $googleConfigPath = __DIR__ . '/../../includes/config_google.php';
        $redirectAction = 'google_callback_lactech';
    } else {
        // Usar configuração do AgroNews
        $googleConfigPath = __DIR__ . '/../includes/config_google.php';
        $redirectAction = 'google_callback';
    }
    
    if (!file_exists($googleConfigPath)) {
        echo json_encode(['success' => false, 'error' => 'Configuração do Google não encontrada']);
        return;
    }
    
    require_once $googleConfigPath;
    
    if (!defined('GOOGLE_CLIENT_ID') || !defined('GOOGLE_CLIENT_SECRET')) {
        echo json_encode(['success' => false, 'error' => 'Credenciais do Google não configuradas']);
        return;
    }
    
    // Detectar ambiente
    $isLocal = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1', '::1']) ||
               strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false;
    
    if ($isLocal) {
        echo json_encode([
            'success' => false,
            'error' => 'Google OAuth não funciona em ambiente local (HTTP/localhost). Use HTTPS.',
            'local_detected' => true
        ]);
        return;
    }
    
    // URL de redirecionamento
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'https';
    $host = $_SERVER['HTTP_HOST'] ?? 'lactechsys.com';
    
    if ($type === 'lactech') {
        // Para login do Lactech, redirecionar para callback do Lactech
        $redirectUri = $protocol . '://' . $host . '/google-login-callback.php';
        // Marcar na sessão que veio do AgroNews
        $_SESSION['google_login_from_agronews'] = true;
    } else {
        // Para login do AgroNews, usar callback do AgroNews
        $redirectUri = defined('GOOGLE_REDIRECT_URI') ? GOOGLE_REDIRECT_URI : 
                       $protocol . '://' . $host . '/agronews360/api/auth.php?action=google_callback';
    }
    
    $scope = defined('GOOGLE_SCOPES') ? GOOGLE_SCOPES : 'email profile openid';
    $state = bin2hex(random_bytes(16));
    
    // Salvar state e tipo na sessão
    $_SESSION['google_login_state'] = $state;
    $_SESSION['google_login_type'] = $type;
    
    // URL de autorização
    $authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
        'client_id' => GOOGLE_CLIENT_ID,
        'redirect_uri' => $redirectUri,
        'response_type' => 'code',
        'scope' => $scope,
        'state' => $state,
        'prompt' => 'select_account consent',
        'access_type' => 'online'
    ]);
    
    echo json_encode([
        'success' => true,
        'auth_url' => $authUrl
    ]);
}

/**
 * Callback do Google Login para Lactech
 * Redireciona para o sistema do Lactech após login bem-sucedido
 */
function handleGoogleCallbackLactech() {
    // Verificar se veio do callback do Google
    $code = $_GET['code'] ?? '';
    $state = $_GET['state'] ?? '';
    
    if (empty($code)) {
        header('Location: ../login.php?error_message=' . urlencode('Código de autorização não recebido'));
        exit;
    }
    
    // Verificar state (CSRF protection)
    if (!isset($_SESSION['google_login_state']) || $_SESSION['google_login_state'] !== $state) {
        header('Location: ../login.php?error_message=' . urlencode('Estado de segurança inválido'));
        exit;
    }
    
    // Marcar na sessão que veio do AgroNews
    $_SESSION['google_login_from_agronews'] = true;
    
    // Redirecionar para o callback do Lactech com o código
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'https';
    $host = $_SERVER['HTTP_HOST'] ?? 'lactechsys.com';
    $redirectUrl = $protocol . '://' . $host . '/google-login-callback.php?code=' . urlencode($code) . '&state=' . urlencode($state);
    
    header('Location: ' . $redirectUrl);
    exit;
}

// Função removida - todos os usuários são tratados igualmente no AgroNews
// O sistema é alimentado pela web, então não precisa de roles diferentes

