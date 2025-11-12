<?php
/**
 * Configurações Google OAuth - AGRO NEWS 360
 * 
 * ⚠️ ATENÇÃO: Este arquivo contém informações sensíveis.
 * NUNCA commite este arquivo no repositório (adicione ao .gitignore)
 * 
 * Este é um sistema INDEPENDENTE do Lactech, mas mantém integração
 * para sincronização de usuários quando necessário.
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

// ==========================================
// CREDENCIAIS GOOGLE OAUTH - AGRO NEWS 360
// ==========================================

// Client ID do Google OAuth (AgroNews360)
// Obter do Google Cloud Console: https://console.cloud.google.com/
// Criar um novo projeto OAuth 2.0 Client ID para o AgroNews360
define('GOOGLE_CLIENT_ID', getEnvValue('AGRONEWS_GOOGLE_CLIENT_ID', ''));

// Client Secret do Google OAuth (AgroNews360)
define('GOOGLE_CLIENT_SECRET', getEnvValue('AGRONEWS_GOOGLE_CLIENT_SECRET', ''));

// Redirect URI para o callback do Google OAuth
// Configurar no Google Cloud Console: Authorized redirect URIs
// Exemplo: https://agronews360.online/agronews360/api/auth.php?action=google_callback
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'agronews360.online';
$basePath = str_replace('\\', '/', dirname(dirname(__DIR__)));
$basePath = str_replace($_SERVER['DOCUMENT_ROOT'] ?? '', '', $basePath);
$basePath = trim($basePath, '/');

// Se estiver na mesma hospedagem que o Lactech, ajustar o caminho
if (strpos($basePath, 'agronews360') !== false) {
    $redirectPath = '/agronews360/api/auth.php?action=google_callback';
} else {
    $redirectPath = '/api/auth.php?action=google_callback';
}

define('GOOGLE_REDIRECT_URI', $protocol . '://' . $host . $redirectPath);

// Scopes do Google OAuth
define('GOOGLE_SCOPES', 'email profile');

// ==========================================
// VALIDAÇÃO
// ==========================================

if (empty(GOOGLE_CLIENT_ID) || empty(GOOGLE_CLIENT_SECRET)) {
    // Em desenvolvimento, pode deixar vazio, mas em produção deve estar configurado
    if (defined('ENVIRONMENT') && ENVIRONMENT === 'PRODUCTION') {
        error_log('⚠️ AgroNews360: GOOGLE_CLIENT_ID ou GOOGLE_CLIENT_SECRET não configurados!');
    }
}






