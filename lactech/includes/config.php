<?php
/**
 * CONFIGURAÇÃO PRINCIPAL - LACTECH
 * Arquivo de configuração unificado
 * 
 * NOTA: Este arquivo só define constantes se elas ainda não existirem.
 * A detecção de ambiente deve ser feita pelo config_mysql.php
 */

// Se config_mysql.php já foi carregado, usar as constantes dele
// Caso contrário, usar valores padrão (produção - será sobrescrito se estiver em local)
if (!defined('DB_HOST')) {
    // Tentar carregar config_mysql.php primeiro para detecção automática
    $configMysqlPath = __DIR__ . '/config_mysql.php';
    if (file_exists($configMysqlPath)) {
        require_once $configMysqlPath;
    }
    
    // Se ainda não foram definidas após carregar config_mysql.php,
    // significa que as variáveis de ambiente não foram configuradas
    if (!defined('DB_HOST')) {
        // Tentar carregar variáveis de ambiente diretamente
        require_once __DIR__ . '/env_loader.php';
        
        // Carregar do .env se disponível
        $envPath = __DIR__ . '/../.env';
        if (file_exists($envPath)) {
            // env_loader já foi carregado acima
        }
        
        // Se ainda não definidas, usar valores do ambiente (sem fallback hardcoded)
        if (!defined('DB_HOST')) {
            // Já foi tratado no config_mysql.php, mas se chegou aqui, mostrar erro
            // (O erro já deve ter sido exibido pelo config_mysql.php, mas garantir)
            if (headers_sent() === false) {
                http_response_code(500);
                header('Content-Type: text/html; charset=utf-8');
            }
            echo '<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erro de Configuração - LacTech</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .error-container { background: white; border-radius: 8px; padding: 40px; max-width: 600px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #dc2626; margin-top: 0; }
        .error-code { background: #fef2f2; border-left: 4px solid #dc2626; padding: 15px; margin: 20px 0; }
        .instructions { background: #f0f9ff; border-left: 4px solid #0284c7; padding: 15px; margin: 20px 0; }
        code { background: #f3f4f6; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
        .env-example { background: #1f2937; color: #f9fafb; padding: 15px; border-radius: 5px; margin: 15px 0; overflow-x: auto; font-family: monospace; font-size: 12px; }
    </style>
</head>
<body>
    <div class="error-container">
        <h1>⚠️ Erro de Configuração</h1>
        <div class="error-code"><strong>Configuração do banco de dados não encontrada.</strong></div>
        <div class="instructions">
            <h2>Como resolver:</h2>
            <p>Por favor, crie um arquivo .env na raiz do projeto com as credenciais do banco de dados.</p>
            <ol>
                <li>Acesse o painel de controle da sua hospedagem (cPanel, FTP, etc.)</li>
                <li>Navegue até a pasta raiz do projeto (onde está o arquivo index.php)</li>
                <li>Crie um arquivo chamado .env (com o ponto no início)</li>
                <li>Adicione o conteúdo conforme instruções na página de erro principal.</li>
            </ol>
        </div>
    </div>
</body>
</html>';
            exit;
        }
    }
}

// Configurações da aplicação
define('APP_NAME', 'LacTech - Lagoa do Mato');
define('APP_VERSION', '2.0.0');
define('FARM_NAME', 'Lagoa do Mato');
define('FARM_ID', 1);

// URLs do sistema
define('BASE_URL', 'https://lactechsys.com/');
define('LOGIN_URL', 'inicio-login.php');
define('DASHBOARD_URL', 'gerente-completo.php');

// Configurações de sessão
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1); // HTTPS em produção

// Iniciar sessão se não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configurações de erro - DESABILITADO EM PRODUÇÃO
error_reporting(0);
ini_set('display_errors', 0);

// Timezone
date_default_timezone_set('America/Sao_Paulo');

// Função para conectar ao banco
function getDatabase() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
            ];
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Erro de conexão: " . $e->getMessage());
            return null;
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

// Função para obter usuário atual
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $db = getDatabase();
    if (!$db) {
        return null;
    }
    
    try {
        $stmt = $db->prepare("SELECT id, name, email, role, farm_id, profile_photo FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Erro ao buscar usuário: " . $e->getMessage());
        return null;
    }
}
?>
