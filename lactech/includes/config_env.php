<?php
/**
 * CONFIGURAÇÃO SEGURA COM VARIÁVEIS DE AMBIENTE
 * Carrega configurações do arquivo .env
 */

// Função para carregar variáveis de ambiente
function loadEnv($path) {
    if (!file_exists($path)) {
        return false;
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) {
            continue; // Pula comentários
        }
        
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Remove aspas se existirem
            if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                $value = substr($value, 1, -1);
            }
            
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
    
    return true;
}

// Detectar ambiente
$isLocal = (
    strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false ||
    strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false
);

// Carregar arquivo .env baseado no ambiente
$envFile = $isLocal ? '.env.local' : '.env.production';
$envPath = __DIR__ . '/../' . $envFile;

// Configurações seguras hardcoded (protegidas)
if ($isLocal) {
    // Configurações para desenvolvimento local
    $_ENV['DB_HOST'] = 'localhost';
    $_ENV['DB_NAME'] = 'lactech_lgmato';
    $_ENV['DB_USER'] = 'root';
    $_ENV['DB_PASS'] = '';
    $_ENV['BASE_URL'] = 'http://localhost/GitHub/lactech-backup2/lactech/';
    $_ENV['APP_ENV'] = 'local';
    $_ENV['APP_SECRET'] = 'lactech_local_secret_2024_secure_key_' . hash('sha256', 'local_key_2024');
    $_ENV['DEBUG'] = 'true';
    $_ENV['DISPLAY_ERRORS'] = 'true';
} else {
    // Configurações para produção
    $_ENV['DB_HOST'] = 'localhost';
    $_ENV['DB_NAME'] = 'u311882628_lactech_lgmato';
    $_ENV['DB_USER'] = 'u311882628_xandriaAgro';
    $_ENV['DB_PASS'] = 'Lavosier0012!';
    $_ENV['BASE_URL'] = 'https://lactechsys.com/';
    $_ENV['APP_ENV'] = 'production';
    $_ENV['APP_SECRET'] = 'lactech_production_secret_2024_secure_key_' . hash('sha256', 'production_key_2024');
    $_ENV['DEBUG'] = 'false';
    $_ENV['DISPLAY_ERRORS'] = 'false';
}

// Definir constantes a partir das variáveis de ambiente
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'lactech_lgmato');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');
define('BASE_URL', $_ENV['BASE_URL'] ?? 'http://localhost/');
define('APP_ENV', $_ENV['APP_ENV'] ?? 'local');

// Configurações da aplicação
define('APP_NAME', $_ENV['APP_NAME'] ?? 'LacTech');
define('FARM_NAME', $_ENV['FARM_NAME'] ?? 'Lagoa do Mato');
define('FARM_ID', $_ENV['FARM_ID'] ?? 1);

// Configurações de segurança
define('APP_SECRET', $_ENV['APP_SECRET'] ?? 'default_secret_key');
define('SESSION_LIFETIME', $_ENV['SESSION_LIFETIME'] ?? 31536000);
define('SESSION_COOKIE_SECURE', $_ENV['SESSION_COOKIE_SECURE'] ?? $isLocal ? 0 : 1);
define('SESSION_COOKIE_HTTPONLY', $_ENV['SESSION_COOKIE_HTTPONLY'] ?? 1);

// Configurações de desenvolvimento
define('DEBUG', $_ENV['DEBUG'] ?? $isLocal);
define('DISPLAY_ERRORS', $_ENV['DISPLAY_ERRORS'] ?? $isLocal);
define('TIMEZONE', $_ENV['TIMEZONE'] ?? 'America/Sao_Paulo');

// Configurações de log
define('LOG_LEVEL', $_ENV['LOG_LEVEL'] ?? ($isLocal ? 'debug' : 'info'));
define('LOG_FILE', $_ENV['LOG_FILE'] ?? 'logs/app.log');

// Configurações de upload
define('UPLOAD_MAX_SIZE', $_ENV['UPLOAD_MAX_SIZE'] ?? 10485760);
define('UPLOAD_ALLOWED_TYPES', $_ENV['UPLOAD_ALLOWED_TYPES'] ?? 'jpg,jpeg,png,gif,pdf,doc,docx');

// Configurações de sessão
ini_set('session.cookie_httponly', SESSION_COOKIE_HTTPONLY);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', SESSION_COOKIE_SECURE);
ini_set('session.cookie_lifetime', SESSION_LIFETIME);
ini_set('session.gc_maxlifetime', SESSION_LIFETIME);

// Configurações de erro
if (DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', DISPLAY_ERRORS ? 1 : 0);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Timezone
date_default_timezone_set(TIMEZONE);

// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Função para obter configuração
function getConfig($key, $default = null) {
    return $_ENV[$key] ?? $default;
}

// Função para verificar se está em produção
function isProduction() {
    return APP_ENV === 'production';
}

// Função para verificar se está em desenvolvimento
function isDevelopment() {
    return APP_ENV === 'local' || APP_ENV === 'development';
}
?>
