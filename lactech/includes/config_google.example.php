<?php
/**
 * Configurações Google OAuth - Exemplo
 * 
 * ⚠️ ATENÇÃO: Este é um arquivo de exemplo.
 * NUNCA commite o arquivo config_google.php com dados sensíveis.
 */

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

// Client ID do Google OAuth
// Usar APENAS variável de ambiente - SEM fallback com credenciais hardcoded
$googleClientId = getEnvValue('GOOGLE_CLIENT_ID');
if (empty($googleClientId)) {
    throw new Exception(
        'GOOGLE_CLIENT_ID não configurado. ' .
        'Por favor, configure a variável GOOGLE_CLIENT_ID no arquivo .env'
    );
}
define('GOOGLE_CLIENT_ID', $googleClientId);

// Client Secret do Google OAuth
// ⚠️ MANTENHA ESTE VALOR SECRETO
// Usar APENAS variável de ambiente - SEM fallback com credenciais hardcoded
$googleClientSecret = getEnvValue('GOOGLE_CLIENT_SECRET');
if (empty($googleClientSecret)) {
    throw new Exception(
        'GOOGLE_CLIENT_SECRET não configurado. ' .
        'Por favor, configure a variável GOOGLE_CLIENT_SECRET no arquivo .env'
    );
}
define('GOOGLE_CLIENT_SECRET', $googleClientSecret);

// URL de redirecionamento (deve ser exatamente igual ao configurado no Google Console)
// Usar para vinculação de conta (quando já está logado)
define('GOOGLE_REDIRECT_URI', getEnvValue('GOOGLE_REDIRECT_URI', 'https://seu-dominio.com/google-callback.php'));

// URL de redirecionamento para login (quando não está logado)
define('GOOGLE_LOGIN_REDIRECT_URI', getEnvValue('GOOGLE_LOGIN_REDIRECT_URI', 'https://seu-dominio.com/google-login-callback.php'));

// Escopos necessários (email e profile para vincular conta)
define('GOOGLE_SCOPES', getEnvValue('GOOGLE_SCOPES', 'email profile'));

?>






