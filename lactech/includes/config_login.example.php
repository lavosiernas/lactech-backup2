<?php
/**
 * Configuração de Login - Exemplo
 * 
 * ⚠️ ATENÇÃO: Este é um arquivo de exemplo.
 * Copie para config_login.php e preencha com seus dados reais.
 * NUNCA commite o arquivo config_login.php com dados sensíveis.
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
    if (!defined('BASE_URL')) define('BASE_URL', getBaseUrl()); // Detecta automaticamente
    if (!defined('ENVIRONMENT')) define('ENVIRONMENT', 'LOCAL');
} else {
    // AMBIENTE DE PRODUÇÃO (HOSPEDAGEM)
    // Usar variáveis de ambiente ou valores padrão
    if (!defined('DB_HOST')) define('DB_HOST', env('DB_HOST_PROD', 'localhost'));
    if (!defined('DB_NAME')) define('DB_NAME', env('DB_NAME_PROD', 'u311882628_lactech_lgmato'));
    if (!defined('DB_USER')) define('DB_USER', env('DB_USER_PROD', 'u311882628_xandriaAgro'));
    if (!defined('DB_PASS')) define('DB_PASS', env('DB_PASS_PROD', ''));
    if (!defined('BASE_URL')) define('BASE_URL', env('BASE_URL_PROD', 'https://lactechsys.com/'));
    if (!defined('ENVIRONMENT')) define('ENVIRONMENT', 'PRODUCTION');
}

// ... resto do código ...







