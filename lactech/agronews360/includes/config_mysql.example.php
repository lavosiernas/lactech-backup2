<?php
/**
 * CONFIGURAÇÃO DE BANCO DE DADOS - AGRO NEWS 360 (EXEMPLO)
 * 
 * ⚠️ ATENÇÃO: Este é um arquivo de exemplo.
 * Copie este arquivo para config_mysql.php e configure as credenciais.
 * NUNCA commite o arquivo config_mysql.php com dados sensíveis.
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
    if (!defined('DB_HOST')) define('DB_HOST', getEnvValue('AGRONEWS_DB_HOST_LOCAL', 'localhost'));
    if (!defined('DB_NAME')) define('DB_NAME', getEnvValue('AGRONEWS_DB_NAME_LOCAL', 'agronews'));
    if (!defined('DB_USER')) define('DB_USER', getEnvValue('AGRONEWS_DB_USER_LOCAL', 'root'));
    if (!defined('DB_PASS')) define('DB_PASS', getEnvValue('AGRONEWS_DB_PASS_LOCAL', ''));
    if (!defined('ENVIRONMENT')) define('ENVIRONMENT', 'LOCAL');
} else {
    // AMBIENTE DE PRODUÇÃO (HOSPEDAGEM)
    // Usar variáveis de ambiente se disponíveis, senão usar valores padrão (fallback)
    // ⚠️ SUBSTITUIR COM SUAS CREDENCIAIS REAIS
    if (!defined('DB_HOST')) define('DB_HOST', getEnvValue('AGRONEWS_DB_HOST_PROD', 'localhost'));
    if (!defined('DB_NAME')) define('DB_NAME', getEnvValue('AGRONEWS_DB_NAME_PROD', 'u311882628_agronews'));
    if (!defined('DB_USER')) define('DB_USER', getEnvValue('AGRONEWS_DB_USER_PROD', 'u311882628_agro360'));
    if (!defined('DB_PASS')) define('DB_PASS', getEnvValue('AGRONEWS_DB_PASS_PROD', 'SUA_SENHA_AQUI'));
    if (!defined('ENVIRONMENT')) define('ENVIRONMENT', 'PRODUCTION');
}

if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

// =====================================================
// CONFIGURAÇÕES GERAIS
// =====================================================

// Timezone
date_default_timezone_set('America/Sao_Paulo');

// Configurações de erro baseadas no ambiente
if (defined('ENVIRONMENT') && ENVIRONMENT === 'LOCAL') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

ini_set('log_errors', 1);

