<?php
/**
 * Google Login Callback Handler
 * Processa o retorno do Google após autorização para LOGIN
 */

// Iniciar sessão ANTES de qualquer coisa
if (session_status() === PHP_SESSION_NONE) {
    session_name('PHPSESSID');
    
    $isLocal = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1', '::1']) ||
               strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false;
    
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', $isLocal ? 0 : 1);
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.cookie_path', '/');
    
    session_start();
}

// Incluir dependências
require_once __DIR__ . '/includes/config_mysql.php';
require_once __DIR__ . '/includes/config_login.php';
require_once __DIR__ . '/includes/Database.class.php';
require_once __DIR__ . '/includes/SecurityService.class.php';

// Carregar configurações Google
$googleConfigFile = __DIR__ . '/includes/config_google.php';
if (!file_exists($googleConfigFile)) {
    die('Configurações do Google não encontradas');
}
require_once $googleConfigFile;

// Verificar se há código de autorização
$code = $_GET['code'] ?? null;
$state = $_GET['state'] ?? null;
$error = $_GET['error'] ?? null;

if ($error) {
    // Log do erro para debug
    error_log("❌ Google Login Callback - Erro recebido: $error");
    error_log("❌ Google Login Callback - GET params: " . print_r($_GET, true));
    
    // Mapear erros comuns para mensagens mais claras
    $errorMessages = [
        'access_denied' => 'Acesso negado. Você cancelou a autorização ou não concedeu as permissões necessárias.',
        'invalid_request' => 'Solicitação inválida. O redirect_uri pode não estar registrado no Google Console.',
        'invalid_client' => 'Cliente inválido. Verifique as credenciais OAuth no Google Console.',
        'unauthorized_client' => 'Cliente não autorizado. Verifique se o redirect_uri está correto.',
        'unsupported_response_type' => 'Tipo de resposta não suportado.',
        'invalid_scope' => 'Escopo inválido. Verifique os escopos solicitados.',
        'server_error' => 'Erro no servidor do Google. Tente novamente mais tarde.',
        'temporarily_unavailable' => 'Serviço temporariamente indisponível. Tente novamente mais tarde.'
    ];
    
    $errorMessage = $errorMessages[$error] ?? "Erro de autorização: $error";
    
    // Mensagem específica para redirect_uri
    if ($error === 'invalid_request' || $error === 'unauthorized_client') {
        $errorMessage .= "\n\n⚠️ Certifique-se de que o redirect_uri está registrado no Google Console:\nhttps://lactechsys.com/google-login-callback.php";
    }
    
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Erro - Login Google</title>
    </head>
    <body>
        <script>
            if (window.opener) {
                window.opener.postMessage({
                    type: 'google_login_error',
                    message: '<?php echo addslashes($errorMessage); ?>',
                    error_code: '<?php echo addslashes($error); ?>'
                }, window.location.origin);
                window.close();
            } else {
                window.location.href = '/inicio-login.php?google_error=<?php echo urlencode($error); ?>&error_description=<?php echo urlencode($errorMessage); ?>';
            }
        </script>
        <p>Erro ao processar autorização. Redirecionando...</p>
        <p><?php echo htmlspecialchars($errorMessage); ?></p>
    </body>
    </html>
    <?php
    exit;
}

if (!$code || !$state) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Erro - Login Google</title>
    </head>
    <body>
        <script>
            if (window.opener) {
                window.opener.postMessage({
                    type: 'google_login_error',
                    message: 'Parâmetros de autorização não recebidos'
                }, window.location.origin);
                window.close();
            } else {
                window.location.href = '/inicio-login.php?google_error=missing_parameters';
            }
        </script>
        <p>Erro ao processar. Redirecionando...</p>
    </body>
    </html>
    <?php
    exit;
}

// Verificar state (CSRF protection)
if (!isset($_SESSION['google_login_state']) || $_SESSION['google_login_state'] !== $state) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Erro - Login Google</title>
    </head>
    <body>
        <script>
            if (window.opener) {
                window.opener.postMessage({
                    type: 'google_login_error',
                    message: 'Erro de segurança: state inválido'
                }, window.location.origin);
                window.close();
            } else {
                window.location.href = '/inicio-login.php?google_error=invalid_state';
            }
        </script>
        <p>Erro de segurança. Redirecionando...</p>
    </body>
    </html>
    <?php
    exit;
}

try {
    // Trocar código de autorização por token de acesso
    $tokenUrl = 'https://oauth2.googleapis.com/token';
    
    $isLocal = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1', '::1']) ||
               strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false;
    
    if (defined('GOOGLE_LOGIN_REDIRECT_URI') && !$isLocal) {
        $redirectUri = GOOGLE_LOGIN_REDIRECT_URI;
    } elseif (defined('GOOGLE_REDIRECT_URI') && !$isLocal) {
        $redirectUri = str_replace('google-callback.php', 'google-login-callback.php', GOOGLE_REDIRECT_URI);
    } else {
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $redirectUri = $protocol . '://' . $host . dirname($_SERVER['SCRIPT_NAME']) . '/google-login-callback.php';
    }
    
    $tokenData = [
        'code' => $code,
        'client_id' => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri' => $redirectUri,
        'grant_type' => 'authorization_code'
    ];
    
    $ch = curl_init($tokenUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($tokenData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    
    $tokenResponse = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        error_log("Erro ao obter token: HTTP $httpCode - $tokenResponse");
        throw new Exception('Erro ao obter token de acesso');
    }
    
    $tokenData = json_decode($tokenResponse, true);
    
    if (!isset($tokenData['access_token'])) {
        throw new Exception('Token não encontrado na resposta');
    }
    
    // Obter informações do usuário Google
    $userInfoUrl = 'https://www.googleapis.com/oauth2/v2/userinfo?access_token=' . urlencode($tokenData['access_token']);
    
    $ch = curl_init($userInfoUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $userInfoResponse = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        throw new Exception('Erro ao obter informações do usuário');
    }
    
    $userInfo = json_decode($userInfoResponse, true);
    
    if (!$userInfo || !isset($userInfo['id']) || empty($userInfo['email'])) {
        throw new Exception('Informações do usuário inválidas');
    }
    
    $googleId = $userInfo['id'];
    $googleEmail = $userInfo['email'];
    $googleName = $userInfo['name'] ?? $userInfo['email'];
    $googlePicture = $userInfo['picture'] ?? null;
    
    // IMPORTANTE: Login com Google só funciona se a conta já estiver vinculada
    // Não criar conta automaticamente - usuário deve vincular primeiro no perfil
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Verificar se a conta Google está vinculada a algum usuário
    $stmt = $pdo->prepare("
        SELECT ga.*, u.id as user_id, u.name, u.email, u.role, u.farm_id
        FROM google_accounts ga
        INNER JOIN users u ON ga.user_id = u.id
        WHERE ga.google_id = :google_id 
        AND ga.email = :email
        AND (ga.unlinked_at IS NULL OR ga.unlinked_at = '')
        LIMIT 1
    ");
    $stmt->execute([
        ':google_id' => $googleId,
        ':email' => $googleEmail
    ]);
    $googleAccount = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$googleAccount) {
        // Conta Google não está vinculada - mostrar erro
        $errorMessage = 'Esta conta Google não está vinculada à sua conta LacTech. Por favor, faça login normalmente e vincule sua conta Google no perfil primeiro.';
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Conta não vinculada - Login Google</title>
            <meta charset="UTF-8">
        </head>
        <body>
            <script>
                (function() {
                    if (window.opener && !window.opener.closed) {
                        try {
                            window.opener.postMessage({
                                type: 'google_login_error',
                                message: '<?php echo addslashes($errorMessage); ?>',
                                error_code: 'account_not_linked',
                                requires_linking: true
                            }, window.location.origin);
                            
                            // Fechar popup imediatamente
                            setTimeout(function() {
                                window.close();
                            }, 100);
                        } catch (e) {
                            console.error('Erro ao comunicar com parent:', e);
                            window.location.href = '/inicio-login.php?google_error=account_not_linked&message=<?php echo urlencode($errorMessage); ?>';
                        }
                    } else {
                        window.location.href = '/inicio-login.php?google_error=account_not_linked&message=<?php echo urlencode($errorMessage); ?>';
                    }
                })();
            </script>
        </body>
        </html>
        <?php
        exit;
    }
    
    // Conta Google está vinculada - fazer login
    $userId = $googleAccount['user_id'];
    $existingUser = [
        'id' => $googleAccount['user_id'],
        'name' => $googleAccount['name'],
        'email' => $googleAccount['email'],
        'role' => $googleAccount['role'],
        'farm_id' => $googleAccount['farm_id']
    ];
    
    // Atualizar dados da conta Google vinculada (último login)
    $stmt = $pdo->prepare("
        UPDATE google_accounts 
        SET google_id = :google_id, 
            email = :email, 
            name = :name, 
            picture = :picture,
            last_login_at = NOW()
        WHERE id = :id
    ");
    $stmt->execute([
        ':id' => $googleAccount['id'],
        ':google_id' => $googleId,
        ':email' => $googleEmail,
        ':name' => $googleName,
        ':picture' => $googlePicture
    ]);
    
    // Criar sessão do usuário
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_email'] = $existingUser['email'];
    $_SESSION['user_name'] = $existingUser['name'];
    $_SESSION['user_role'] = $existingUser['role'];
    $_SESSION['farm_id'] = $existingUser['farm_id'];
    $_SESSION['logged_in'] = true;
    $_SESSION['login_time'] = time();
    $_SESSION['login_method'] = 'google'; // Marcar que foi login via Google
    
    if ($googlePicture) {
        $_SESSION['profile_photo'] = $googlePicture;
    }
    
    // Determinar redirect baseado no role
    $role = $existingUser['role'];
    switch ($role) {
        case 'proprietario':
        case 'owner':
            $redirectUrl = '/proprietario.php';
            break;
        case 'gerente':
        case 'manager':
            $redirectUrl = '/gerente-completo.php';
            break;
        case 'funcionario':
        case 'employee':
        default:
            $redirectUrl = '/funcionario.php';
            break;
    }
    
    // Limpar state da sessão
    unset($_SESSION['google_login_state']);
    
    // Forçar escrita da sessão
    session_write_close();
    
    // SEMPRE usar JavaScript para detectar se é popup (mais confiável que parâmetros GET)
    // Isso garante que funcione tanto em popup quanto em redirecionamento direto
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Login Google - Sucesso</title>
        <meta charset="UTF-8">
    </head>
    <body>
        <script>
            (function() {
                // SEMPRE verificar window.opener primeiro (mais confiável)
                if (window.opener && !window.opener.closed) {
                    try {
                        // Enviar mensagem para o parent (página de login)
                        window.opener.postMessage({
                            type: 'google_login_success',
                            message: 'Login com Google realizado com sucesso!',
                            redirect: '<?php echo $redirectUrl; ?>'
                        }, window.location.origin);
                        
                        // Fechar popup imediatamente
                        setTimeout(function() {
                            window.close();
                        }, 100);
                    } catch (e) {
                        console.error('Erro ao comunicar com parent:', e);
                        // Se falhar, fazer redirect normal
                        window.location.replace('<?php echo $redirectUrl; ?>');
                    }
                } else {
                    // Não é popup ou popup fechado - redirecionar diretamente
                    window.location.replace('<?php echo $redirectUrl; ?>');
                }
            })();
        </script>
    </body>
    </html>
    <?php
    
} catch (Exception $e) {
    error_log("Erro no callback Google Login: " . $e->getMessage());
    
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Erro - Login Google</title>
    </head>
    <body>
        <script>
            (function() {
                if (window.opener && !window.opener.closed) {
                    try {
                        window.opener.postMessage({
                            type: 'google_login_error',
                            message: 'Erro ao fazer login com Google: <?php echo addslashes($e->getMessage()); ?>',
                            error_code: '<?php echo method_exists($e, "getCode") ? $e->getCode() : "unknown"; ?>'
                        }, window.location.origin);
                        
                        // Fechar popup imediatamente
                        setTimeout(function() {
                            window.close();
                        }, 100);
                    } catch (e) {
                        console.error('Erro ao comunicar com parent:', e);
                        window.location.replace('/inicio-login.php?google_error=callback_error');
                    }
                } else {
                    window.location.replace('/inicio-login.php?google_error=callback_error');
                }
            })();
        </script>
    </body>
    </html>
    <?php
    exit;
}
?>

