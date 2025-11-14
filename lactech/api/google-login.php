<?php
/**
 * API de Login com Google - LACTECH
 * Gerencia login secundário via Google OAuth
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Iniciar sessão se não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/Database.class.php';
require_once __DIR__ . '/../includes/SecurityService.class.php';

try {
    $db = Database::getInstance();
    $security = SecurityService::getInstance();
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    switch ($action) {
        // ==================== INICIAR LOGIN (OAuth URL) ====================
        case 'get_auth_url':
            // Carregar configurações Google
            $googleConfigFile = __DIR__ . '/../includes/config_google.php';
            if (!file_exists($googleConfigFile)) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Configurações do Google não encontradas'
                ]);
                exit;
            }
            
            require_once $googleConfigFile;
            
            // Verificar se as constantes estão definidas
            if (!defined('GOOGLE_CLIENT_ID') || !defined('GOOGLE_CLIENT_SECRET')) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Credenciais do Google não configuradas'
                ]);
                exit;
            }
            
            $clientId = GOOGLE_CLIENT_ID;
            
            // Detectar ambiente
            $isLocal = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1', '::1']) ||
                       strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false ||
                       strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false;
            
            // IMPORTANTE: Google OAuth NÃO funciona com HTTP/localhost
            // Se estiver em localhost, retornar erro explicativo
            if ($isLocal) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Google OAuth não funciona em ambiente local (HTTP/localhost). O Google exige HTTPS por questões de segurança.',
                    'solutions' => [
                        '1. Use um túnel HTTPS (ngrok, Cloudflare Tunnel, etc.)',
                        '2. Teste diretamente em produção (https://lactechsys.com)',
                        '3. Configure localhost no Google Console (limitado)'
                    ],
                    'local_detected' => true
                ]);
                exit;
            }
            
            // URL de redirecionamento para login (diferente do callback de vinculação)
            if (defined('GOOGLE_LOGIN_REDIRECT_URI')) {
                // Usar callback de login específico configurado
                $redirectUri = GOOGLE_LOGIN_REDIRECT_URI;
            } elseif (defined('GOOGLE_REDIRECT_URI')) {
                // Fallback: usar o redirect_uri padrão substituindo o nome
                $redirectUri = str_replace('google-callback.php', 'google-login-callback.php', GOOGLE_REDIRECT_URI);
            } else {
                // Fallback final: construir baseado no servidor
                $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'https'; // Forçar HTTPS
                $host = $_SERVER['HTTP_HOST'] ?? 'lactechsys.com';
                $redirectUri = $protocol . '://' . $host . '/google-login-callback.php';
            }
            
            $scope = defined('GOOGLE_SCOPES') ? GOOGLE_SCOPES : 'email profile';
            $state = bin2hex(random_bytes(16)); // CSRF protection
            
            // Salvar state na sessão (sem user_id, pois ainda não está logado)
            $_SESSION['google_login_state'] = $state;
            
            // URL de autorização
            $authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
                'client_id' => $clientId,
                'redirect_uri' => $redirectUri,
                'response_type' => 'code',
                'scope' => $scope,
                'state' => $state,
                'prompt' => 'select_account consent', // Permite escolher conta secundária
                'access_type' => 'online'
            ]);
            
            // Log para debug
            error_log("🔍 Google Login - redirect_uri usado: $redirectUri");
            error_log("🔍 Google Login - auth_url gerada: " . substr($authUrl, 0, 200) . "...");
            
            echo json_encode([
                'success' => true,
                'auth_url' => $authUrl,
                'debug' => [
                    'redirect_uri' => $redirectUri,
                    'expected_uri' => 'https://lactechsys.com/google-login-callback.php',
                    'message' => 'Certifique-se de que este URI está registrado no Google Console'
                ]
            ]);
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'error' => 'Ação inválida'
            ]);
            break;
    }
    
} catch (Exception $e) {
    error_log("Erro na API Google Login: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Erro no servidor: ' . $e->getMessage()
    ]);
}
?>

