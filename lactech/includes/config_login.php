<?php
/**
 * CONFIGURAÇÃO LOGIN - LACTECH
 * Configuração unificada do sistema
 */

// Configurações do banco de dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'lactech_lgmato');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Configurações da aplicação
define('APP_NAME', 'LacTech - Lagoa do Mato');
define('APP_VERSION', '2.0.0');
define('FARM_NAME', 'Lagoa do Mato');
define('FARM_ID', 1);

// URLs do sistema
define('BASE_URL', 'http://localhost/GitHub/lactech-backup2/lactech/');
define('LOGIN_URL', 'inicio-login.php');
define('DASHBOARD_URL', 'gerente.php');

// Configurações de sessão
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // HTTP em desenvolvimento

// Iniciar sessão se não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configurações de erro - ATIVADO EM DESENVOLVIMENTO
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('America/Sao_Paulo');

// Função para conectar ao banco
function getDatabase() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_FOUND_ROWS => true
            ]);
        } catch (PDOException $e) {
            error_log("Erro de conexão: " . $e->getMessage());
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
