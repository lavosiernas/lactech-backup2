<?php
/**
 * Configurações Google OAuth - Exemplo
 * 
 * ⚠️ ATENÇÃO: Este é um arquivo de exemplo.
 * Copie para config_google.php e preencha com seus dados reais.
 * NUNCA commite o arquivo config_google.php com dados sensíveis.
 */

// Carregar variáveis de ambiente
require_once __DIR__ . '/env_loader.php';

// Client ID do Google OAuth
// Obter de variável de ambiente ou usar valor padrão
define('GOOGLE_CLIENT_ID', env('GOOGLE_CLIENT_ID', 'seu_client_id_aqui.apps.googleusercontent.com'));

// Client Secret do Google OAuth
// ⚠️ MANTENHA ESTE VALOR SECRETO
define('GOOGLE_CLIENT_SECRET', env('GOOGLE_CLIENT_SECRET', 'seu_client_secret_aqui'));

// URL de redirecionamento (deve ser exatamente igual ao configurado no Google Console)
// Usar para vinculação de conta (quando já está logado)
define('GOOGLE_REDIRECT_URI', env('GOOGLE_REDIRECT_URI', 'https://lactechsys.com/google-callback.php'));

// URL de redirecionamento para login (quando não está logado)
define('GOOGLE_LOGIN_REDIRECT_URI', env('GOOGLE_LOGIN_REDIRECT_URI', 'https://lactechsys.com/google-login-callback.php'));

// Escopos necessários (email e profile para vincular conta)
define('GOOGLE_SCOPES', env('GOOGLE_SCOPES', 'email profile'));

?>


