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

if ($isLocal) {
    // AMBIENTE LOCAL (XAMPP/WAMP)
    if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
    if (!defined('DB_NAME')) define('DB_NAME', 'lactech_lgmato'); // Banco local (conforme dump .sql)
    if (!defined('DB_USER')) define('DB_USER', 'root');
    if (!defined('DB_PASS')) define('DB_PASS', '');
    if (!defined('BASE_URL')) define('BASE_URL', getBaseUrl()); // Detecta automaticamente
    if (!defined('ENVIRONMENT')) define('ENVIRONMENT', 'LOCAL');
} else {
    // AMBIENTE DE PRODUÇÃO (HOSPEDAGEM)
    if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
    if (!defined('DB_NAME')) define('DB_NAME', 'u311882628_lactech_lgmato'); // Banco hospedagem
    if (!defined('DB_USER')) define('DB_USER', 'u311882628_xandriaAgro');
    if (!defined('DB_PASS')) define('DB_PASS', 'Lavosier0012!');
    if (!defined('BASE_URL')) define('BASE_URL', 'https://lactechsys.com/');
    if (!defined('ENVIRONMENT')) define('ENVIRONMENT', 'PRODUCTION');
}

if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

// Configurações da aplicação
if (!defined('APP_NAME')) define('APP_NAME', 'LacTech - Lagoa do Mato');
if (!defined('APP_VERSION')) define('APP_VERSION', '2.0.0');
if (!defined('FARM_NAME')) define('FARM_NAME', 'Lagoa do Mato');
if (!defined('FARM_ID')) define('FARM_ID', 1);

// URLs do sistema
if (!defined('LOGIN_URL')) define('LOGIN_URL', 'inicio-login.php');
if (!defined('DASHBOARD_URL')) define('DASHBOARD_URL', 'gerente-completo.php');

// Configurações de sessão (antes de iniciar a sessão)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    if (defined('ENVIRONMENT') && ENVIRONMENT === 'LOCAL') {
        ini_set('session.cookie_secure', 0); // HTTP local
    } else {
        ini_set('session.cookie_secure', 1); // HTTPS em produção
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
        
        // Renovar o cookie de sessão para durar 1 ano (permanente)
        setcookie(session_name(), session_id(), time() + 31536000, '/');
        
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

// Função para corrigir URL automaticamente (adiciona .php se necessário)
// Esta função detecta páginas do sistema e garante que tenham a extensão correta
function fixRedirectUrl($url) {
    // Se já tem .php, retornar como está
    if (strpos($url, '.php') !== false) {
        return $url;
    }
    
    // Lista de páginas do sistema que podem ter .php
    $systemPages = [
        'inicio-login',
        'gerente-completo',
        'proprietario',
        'funcionario',
        'index',
        'google-callback',
        'sistema-touros',
        'sistema-touros-detalhes',
        'acesso-bloqueado'
    ];
    
    // Extrair nome da página (sem query string)
    $parsedUrl = parse_url($url);
    $path = $parsedUrl['path'] ?? $url;
    $query = isset($parsedUrl['query']) ? '?' . $parsedUrl['query'] : '';
    $fragment = isset($parsedUrl['fragment']) ? '#' . $parsedUrl['fragment'] : '';
    
    // Remover barra inicial se existir e extrair nome do arquivo
    $path = ltrim($path, '/');
    $fileName = basename($path);
    
    // Verificar se é uma página do sistema
    $isSystemPage = false;
    foreach ($systemPages as $page) {
        if ($fileName === $page || strpos($fileName, $page) === 0) {
            $isSystemPage = true;
            break;
        }
    }
    
    // Se é página do sistema e não tem .php, adicionar
    if ($isSystemPage && strpos($fileName, '.php') === false) {
        $directory = dirname($path);
        if ($directory === '.' || $directory === '/') {
            $fixedPath = $fileName . '.php';
        } else {
            $fixedPath = $directory . '/' . $fileName . '.php';
        }
        
        // Manter estrutura completa se tinha caminho
        if (strpos($url, '/') === 0) {
            $fixedPath = '/' . $fixedPath;
        }
        
        return $fixedPath . $query . $fragment;
    }
    
    // Se não é página do sistema ou já tem extensão, retornar original
    return $url;
}

// Função helper para redirecionamento seguro
function safeRedirect($url, $statusCode = 302) {
    $fixedUrl = fixRedirectUrl($url);
    header("Location: $fixedUrl", true, $statusCode);
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
