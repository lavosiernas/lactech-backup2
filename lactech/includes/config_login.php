<?php
/**
 * CONFIGURAÇÃO LOGIN - LACTECH
 * Configuração unificada do sistema com detecção automática de ambiente
 */

// =====================================================
// DETECÇÃO AUTOMÁTICA DE AMBIENTE (LOCAL OU PRODUÇÃO)
// =====================================================

// Detectar se está em localhost
if (!isset($isLocal)) {
    $isLocal = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1', '::1']) ||
               strpos($_SERVER['SERVER_NAME'] ?? '', '192.168.') === 0 ||
               strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false;
}

// Detectar URL base automaticamente
if (!function_exists('getBaseUrl')) {
    function getBaseUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        $path = str_replace('\\', '/', dirname($script));
        
        // Remover index.php ou qualquer arquivo do final
        $path = rtrim($path, '/') . '/';
        
        return $protocol . '://' . $host . $path;
    }
}

// =====================================================
// CONFIGURAÇÕES DO BANCO DE DADOS
// =====================================================

// Carregar variáveis de ambiente (se o loader existir)
$envLoaderPath = __DIR__ . '/env_loader.php';
if (file_exists($envLoaderPath)) {
    require_once $envLoaderPath;
}

// Função auxiliar para obter variável de ambiente com fallback
if (!function_exists('getEnvValue')) {
    function getEnvValue($key, $default = null) {
        if (function_exists('env')) {
            return env($key, $default);
        }
        $value = getenv($key);
        if ($value === false) {
            $value = $_ENV[$key] ?? $_SERVER[$key] ?? null;
        }
        return $value !== null ? $value : $default;
    }
}

if ($isLocal) {
    // AMBIENTE LOCAL (XAMPP/WAMP)
    // Usar variáveis de ambiente se disponíveis, senão usar valores padrão
    if (!defined('DB_HOST')) define('DB_HOST', getEnvValue('DB_HOST_LOCAL', 'localhost'));
    if (!defined('DB_NAME')) define('DB_NAME', getEnvValue('DB_NAME_LOCAL', 'lactech_lgmato'));
    if (!defined('DB_USER')) define('DB_USER', getEnvValue('DB_USER_LOCAL', 'root'));
    if (!defined('DB_PASS')) define('DB_PASS', getEnvValue('DB_PASS_LOCAL', ''));
    if (!defined('BASE_URL')) define('BASE_URL', getBaseUrl()); // Detecta automaticamente
    if (!defined('ENVIRONMENT')) define('ENVIRONMENT', 'LOCAL');
} else {
    // AMBIENTE DE PRODUÇÃO (HOSPEDAGEM)
    // Usar variáveis de ambiente se disponíveis, senão usar valores padrão (fallback)
    if (!defined('DB_HOST')) define('DB_HOST', getEnvValue('DB_HOST_PROD', 'localhost'));
    if (!defined('DB_NAME')) define('DB_NAME', getEnvValue('DB_NAME_PROD', 'u311882628_lactech_lgmato'));
    if (!defined('DB_USER')) define('DB_USER', getEnvValue('DB_USER_PROD', 'u311882628_xandriaAgro'));
    if (!defined('DB_PASS')) define('DB_PASS', getEnvValue('DB_PASS_PROD', 'Lavosier0012!'));
    if (!defined('BASE_URL')) define('BASE_URL', getEnvValue('BASE_URL_PROD', 'https://lactechsys.com/'));
    if (!defined('ENVIRONMENT')) define('ENVIRONMENT', 'PRODUCTION');
}

if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

// Configurações da aplicação
if (!defined('APP_NAME')) define('APP_NAME', 'LacTech - Lagoa do Mato');
if (!defined('APP_VERSION')) define('APP_VERSION', '2.0.0');
if (!defined('FARM_NAME')) define('FARM_NAME', 'Lagoa do Mato');
if (!defined('FARM_ID')) define('FARM_ID', 1);
if (!defined('SESSION_COOKIE_LIFETIME')) define('SESSION_COOKIE_LIFETIME', 60 * 60 * 24 * 30); // 30 dias

// URLs do sistema
if (!defined('LOGIN_URL')) define('LOGIN_URL', 'inicio-login.php');
if (!defined('DASHBOARD_URL')) define('DASHBOARD_URL', 'gerente-completo.php');

// Configurações do Cloudflare Turnstile
// Sempre buscar do .env primeiro (sem valores padrão hardcoded por segurança)
if (!defined('TURNSTILE_SITE_KEY')) define('TURNSTILE_SITE_KEY', getEnvValue('TURNSTILE_SITE_KEY', ''));
if (!defined('TURNSTILE_SECRET_KEY')) define('TURNSTILE_SECRET_KEY', getEnvValue('TURNSTILE_SECRET_KEY', ''));
// IDs opcionais (não necessários para funcionamento do Turnstile, apenas para referência)
if (!defined('CLOUDFLARE_ZONE_ID')) define('CLOUDFLARE_ZONE_ID', getEnvValue('CLOUDFLARE_ZONE_ID', ''));
if (!defined('CLOUDFLARE_ACCOUNT_ID')) define('CLOUDFLARE_ACCOUNT_ID', getEnvValue('CLOUDFLARE_ACCOUNT_ID', ''));

// Configurações de sessão (antes de iniciar a sessão)
if (session_status() === PHP_SESSION_NONE) {
    $isSecure = defined('ENVIRONMENT') && ENVIRONMENT !== 'LOCAL';

    ini_set('session.cookie_lifetime', SESSION_COOKIE_LIFETIME);
    ini_set('session.gc_maxlifetime', SESSION_COOKIE_LIFETIME);
    ini_set('session.gc_probability', 1);
    ini_set('session.gc_divisor', 100);
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', $isSecure ? 1 : 0);
    ini_set('session.cookie_samesite', 'Lax');

    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params([
            'lifetime' => SESSION_COOKIE_LIFETIME,
            'path' => '/',
            'secure' => $isSecure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    } else {
        session_set_cookie_params(SESSION_COOKIE_LIFETIME, '/', '', $isSecure, true);
    }

    session_start();
}

// Configurações de erro baseadas no ambiente
if (defined('ENVIRONMENT') && ENVIRONMENT === 'LOCAL') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Timezone
date_default_timezone_set('America/Sao_Paulo');

// Função para conectar ao banco
function getDatabase() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            if (!defined('DB_HOST') || !defined('DB_NAME') || !defined('DB_USER') || !defined('DB_PASS')) {
                throw new PDOException("Configurações do banco de dados não definidas");
            }
            
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_FOUND_ROWS => true
            ]);
        } catch (PDOException $e) {
            $errorMsg = "Erro de conexão com banco de dados: " . $e->getMessage();
            error_log($errorMsg);
            
            // Em ambiente local, mostrar erro mais detalhado
            if (defined('ENVIRONMENT') && ENVIRONMENT === 'LOCAL') {
                error_log("Detalhes do erro (LOCAL): Host=" . DB_HOST . ", DB=" . DB_NAME . ", User=" . DB_USER);
            }
            
            return false;
        }
    }
    
    return $pdo;
}

// Função para fazer login
function loginUser($email, $password) {
    $db = getDatabase();
    if (!$db) {
        return ['success' => false, 'error' => 'Erro de conexão com banco'];
    }
    
    try {
        $stmt = $db->prepare("SELECT id, name, email, password, role, farm_id, profile_photo, password_changed_at, password_change_required FROM users WHERE email = ? AND is_active = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return ['success' => false, 'error' => 'Usuário não encontrado'];
        }
        
        if (!password_verify($password, $user['password'])) {
            return ['success' => false, 'error' => 'Senha incorreta'];
        }
        
        // Atualizar último login
        $updateStmt = $db->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
        $updateStmt->execute([$user['id']]);
        
        // Criar sessão
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['farm_id'] = $user['farm_id'];
        $_SESSION['profile_photo'] = $user['profile_photo'];
        $_SESSION['password_change_required'] = $user['password_change_required'];
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
        $_SESSION['session_expires_at'] = time() + SESSION_COOKIE_LIFETIME;

        session_regenerate_id(true);

        $cookieExpires = time() + SESSION_COOKIE_LIFETIME;
        $cookieSecure = defined('ENVIRONMENT') && ENVIRONMENT !== 'LOCAL';

        if (PHP_VERSION_ID >= 70300) {
            setcookie(session_name(), session_id(), [
                'expires' => $cookieExpires,
                'path' => '/',
                'secure' => $cookieSecure,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        } else {
            setcookie(session_name(), session_id(), $cookieExpires, '/', '', $cookieSecure, true);
        }
        
        // Remover senha da resposta
        unset($user['password']);
        
        return ['success' => true, 'user' => $user];
        
    } catch (PDOException $e) {
        error_log("Erro no login: " . $e->getMessage());
        return ['success' => false, 'error' => 'Erro interno'];
    }
}

// Função para verificar se está logado
function isLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

// Função para redirecionamento seguro (sem modificação de URL)
// A URL é usada exatamente como fornecida, sem modificações
function safeRedirect($url, $statusCode = 302) {
    header("Location: $url", true, $statusCode);
    exit();
}

// Função para fazer logout
function logoutUser() {
    session_destroy();
    return true;
}

// Função para alterar senha
function changePassword($userId, $currentPassword, $newPassword) {
    $db = getDatabase();
    if (!$db) {
        return ['success' => false, 'error' => 'Erro de conexão com banco'];
    }
    
    try {
        // Verificar senha atual
        $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return ['success' => false, 'error' => 'Usuário não encontrado'];
        }
        
        if (!password_verify($currentPassword, $user['password'])) {
            return ['success' => false, 'error' => 'Senha atual incorreta'];
        }
        
        // Atualizar senha
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $updateStmt = $db->prepare("UPDATE users SET password = ?, password_changed_at = CURRENT_TIMESTAMP, password_change_required = 0 WHERE id = ?");
        $updateStmt->execute([$hashedPassword, $userId]);
        
        // Registrar na tabela de auditoria se existir
        try {
            $ipAddress = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? null;
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            
            // Verificar se a tabela existe
            $checkStmt = $db->prepare("SHOW TABLES LIKE 'security_audit_log'");
            $checkStmt->execute();
            $tableExists = $checkStmt->rowCount() > 0;
            
            if ($tableExists) {
                $auditStmt = $db->prepare("
                    INSERT INTO security_audit_log (
                        user_id, action, description, ip_address, user_agent, 
                        success, metadata
                    ) VALUES (
                        ?, 'password_changed', 'Senha alterada com sucesso', 
                        ?, ?, 1, NULL
                    )
                ");
                $auditStmt->execute([$userId, $ipAddress, $userAgent]);
            }
        } catch (Exception $e) {
            // Se a tabela não existir, apenas logar o erro mas não falhar
            error_log("Aviso: Não foi possível registrar na tabela de auditoria: " . $e->getMessage());
        }
        
        return ['success' => true, 'message' => 'Senha alterada com sucesso'];
        
    } catch (PDOException $e) {
        error_log("Erro ao alterar senha: " . $e->getMessage());
        return ['success' => false, 'error' => 'Erro interno'];
    }
}

// Função para atualizar foto de perfil
function updateProfilePhoto($userId, $photoPath) {
    $db = getDatabase();
    if (!$db) {
        return ['success' => false, 'error' => 'Erro de conexão com banco'];
    }
    
    try {
        $stmt = $db->prepare("UPDATE users SET profile_photo = ? WHERE id = ?");
        $stmt->execute([$photoPath, $userId]);
        
        // Atualizar sessão
        $_SESSION['profile_photo'] = $photoPath;
        
        return ['success' => true, 'message' => 'Foto de perfil atualizada', 'photo_path' => $photoPath];
        
    } catch (PDOException $e) {
        error_log("Erro ao atualizar foto: " . $e->getMessage());
        return ['success' => false, 'error' => 'Erro interno'];
    }
}

// Função para obter dados do usuário
function getUserData($userId) {
    $db = getDatabase();
    if (!$db) {
        return ['success' => false, 'error' => 'Erro de conexão com banco'];
    }
    
    try {
        $stmt = $db->prepare("SELECT id, name, email, role, farm_id, cpf, phone, address, hire_date, salary, profile_photo, password_changed_at, password_change_required, is_active, last_login, created_at FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return ['success' => false, 'error' => 'Usuário não encontrado'];
        }
        
        return ['success' => true, 'user' => $user];
        
    } catch (PDOException $e) {
        error_log("Erro ao obter dados do usuário: " . $e->getMessage());
        return ['success' => false, 'error' => 'Erro interno'];
    }
}
?>
