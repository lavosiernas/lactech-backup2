<?php
/**
 * Configuração MySQL - Exemplo
 * 
 * ⚠️ ATENÇÃO: Este é um arquivo de exemplo.
 * Copie para config_mysql.php e preencha com seus dados reais.
 * NUNCA commite o arquivo config_mysql.php com dados sensíveis.
 */

// Carregar variáveis de ambiente
require_once __DIR__ . '/env_loader.php';

// Detectar ambiente automaticamente
$isLocal = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1', '::1']) ||
           strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false;

if ($isLocal) {
    // AMBIENTE LOCAL (XAMPP/WAMP)
    // Usar variáveis de ambiente ou valores padrão
    if (!defined('DB_HOST')) define('DB_HOST', env('DB_HOST_LOCAL', 'localhost'));
    if (!defined('DB_NAME')) define('DB_NAME', env('DB_NAME_LOCAL', 'lactech_lgmato'));
    if (!defined('DB_USER')) define('DB_USER', env('DB_USER_LOCAL', 'root'));
    if (!defined('DB_PASS')) define('DB_PASS', env('DB_PASS_LOCAL', ''));
    if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');
    if (!defined('BASE_URL')) define('BASE_URL', getBaseUrl()); // Detecta automaticamente
    if (!defined('ENVIRONMENT')) define('ENVIRONMENT', 'LOCAL');
} else {
    // AMBIENTE DE PRODUÇÃO (HOSPEDAGEM)
    // Usar APENAS variáveis de ambiente - SEM fallback com credenciais hardcoded
    $dbHost = env('DB_HOST_PROD');
    $dbName = env('DB_NAME_PROD');
    $dbUser = env('DB_USER_PROD');
    $dbPass = env('DB_PASS_PROD');
    $baseUrl = env('BASE_URL_PROD');
    
    // Validar que todas as variáveis necessárias estão definidas
    if (empty($dbHost) || empty($dbName) || empty($dbUser)) {
        throw new Exception(
            'Configuração do banco de dados de produção não encontrada. ' .
            'Por favor, configure as variáveis de ambiente DB_HOST_PROD, DB_NAME_PROD, DB_USER_PROD e DB_PASS_PROD no arquivo .env'
        );
    }
    
    if (!defined('DB_HOST')) define('DB_HOST', $dbHost);
    if (!defined('DB_NAME')) define('DB_NAME', $dbName);
    if (!defined('DB_USER')) define('DB_USER', $dbUser);
    if (!defined('DB_PASS')) define('DB_PASS', $dbPass ?: '');
    if (!defined('BASE_URL')) define('BASE_URL', $baseUrl ?: 'https://seu-dominio.com/');
    if (!defined('ENVIRONMENT')) define('ENVIRONMENT', 'PRODUCTION');
}

// Função para obter URL base automaticamente
if (!function_exists('getBaseUrl')) {
    function getBaseUrl() {
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $script = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        return $protocol . '://' . $host . ($script !== '/' ? $script : '') . '/';
    }
}

// ... resto do código ...






