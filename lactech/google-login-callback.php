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

// Verificar se veio do AgroNews (via parâmetro na sessão ou referer)
$fromAgronews = isset($_SESSION['google_login_from_agronews']) || 
                (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'agronews360') !== false);

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
    
    // Se não houver foto do Google, usar logo do sistema como padrão
    if (empty($googlePicture)) {
        $googlePicture = 'https://i.postimg.cc/vmrkgDcB/lactech.png';
    }
    
    // IMPORTANTE: Login com Google só funciona se a conta já estiver vinculada
    // Não criar conta automaticamente - usuário deve vincular primeiro no perfil
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // PRIMEIRO: Verificar se a conta Google foi desvinculada (unlinked_at não é NULL)
    // Se foi desvinculada, mostrar mensagem específica imediatamente
    $stmt = $pdo->prepare("
        SELECT ga.*, u.id as user_id, u.name, u.email, u.role, u.farm_id, u.is_active
        FROM google_accounts ga
        INNER JOIN users u ON ga.user_id = u.id
        WHERE (ga.google_id = :google_id OR ga.email = :email)
        AND ga.unlinked_at IS NOT NULL 
        AND ga.unlinked_at != ''
        LIMIT 1
    ");
    $stmt->execute([
        ':google_id' => $googleId,
        ':email' => $googleEmail
    ]);
    $unlinkedAccount = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($unlinkedAccount) {
        // Conta foi desvinculada - mostrar erro específico
        error_log("🚫 Tentativa de login com Google desvinculado - Google ID: $googleId, Email: $googleEmail, IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        $errorMessage = 'Esta conta Google foi desvinculada da sua conta LacTech. Para fazer login com Google novamente, faça login normalmente com seu email e senha e vincule sua conta Google no perfil.';
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Conta desvinculada - Login Google</title>
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
                                error_code: 'account_unlinked',
                                requires_linking: true
                            }, window.location.origin);
                            
                            setTimeout(function() {
                                window.close();
                            }, 100);
                        } catch (e) {
                            console.error('Erro ao comunicar com parent:', e);
                            window.location.href = '/inicio-login.php?google_error=account_unlinked&message=<?php echo urlencode($errorMessage); ?>';
                        }
                    } else {
                        window.location.href = '/inicio-login.php?google_error=account_unlinked&message=<?php echo urlencode($errorMessage); ?>';
                    }
                })();
            </script>
        </body>
        </html>
        <?php
        exit;
    }
    
    // SEGUNDO: Verificar se a conta Google está vinculada a algum usuário ATIVO
    // Validação rigorosa: verifica google_id, email, se não foi desvinculada E se o usuário está ativo
    $stmt = $pdo->prepare("
        SELECT ga.*, u.id as user_id, u.name, u.email, u.role, u.farm_id, u.is_active
        FROM google_accounts ga
        INNER JOIN users u ON ga.user_id = u.id
        WHERE ga.google_id = :google_id 
        AND (ga.unlinked_at IS NULL OR ga.unlinked_at = '')
        AND u.is_active = 1
        LIMIT 1
    ");
    $stmt->execute([
        ':google_id' => $googleId
    ]);
    $googleAccount = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Se não encontrou por google_id, verificar também por email (para garantir)
    if (!$googleAccount) {
        $stmt = $pdo->prepare("
            SELECT ga.*, u.id as user_id, u.name, u.email, u.role, u.farm_id, u.is_active
            FROM google_accounts ga
            INNER JOIN users u ON ga.user_id = u.id
            WHERE ga.email = :email
            AND (ga.unlinked_at IS NULL OR ga.unlinked_at = '')
            AND u.is_active = 1
            LIMIT 1
        ");
        $stmt->execute([
            ':email' => $googleEmail
        ]);
        $googleAccount = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Log para auditoria de segurança
    if (!$googleAccount) {
        error_log("🚫 Tentativa de login com Google não vinculado - Google ID: $googleId, Email: $googleEmail, IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    }
    
    if (!$googleAccount) {
        // Conta Google não está vinculada - mostrar erro
        $errorMessage = 'Esta conta Google não está vinculada à sua conta LacTech. Por favor, faça login normalmente com seu email e senha e vincule sua conta Google no perfil primeiro.';
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
    
    // Verificação adicional: garantir que o usuário ainda está ativo no banco
    // IMPORTANTE: Buscar também profile_photo para preservar foto existente
    $stmt = $pdo->prepare("
        SELECT id, name, email, role, farm_id, is_active, profile_photo 
        FROM users 
        WHERE id = :user_id 
        AND is_active = 1
        LIMIT 1
    ");
    $stmt->execute([':user_id' => $googleAccount['user_id']]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$userData) {
        // Usuário foi desativado ou não existe mais - bloquear login
        error_log("🚫 Tentativa de login com Google para usuário inativo ou inexistente - User ID: {$googleAccount['user_id']}, Google ID: $googleId, Email: $googleEmail");
        $errorMessage = 'Conta não disponível. Por favor, entre em contato com o administrador.';
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Conta não disponível - Login Google</title>
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
                                error_code: 'account_inactive'
                            }, window.location.origin);
                            setTimeout(function() {
                                window.close();
                            }, 100);
                        } catch (e) {
                            console.error('Erro ao comunicar com parent:', e);
                            window.location.href = '/inicio-login.php?google_error=account_inactive';
                        }
                    } else {
                        window.location.href = '/inicio-login.php?google_error=account_inactive';
                    }
                })();
            </script>
        </body>
        </html>
        <?php
        exit;
    }
    
    // Conta Google está vinculada e usuário está ativo - fazer login
    $userId = $userData['id'];
    $existingUser = [
        'id' => $userData['id'],
        'name' => $userData['name'],
        'email' => $userData['email'],
        'role' => $userData['role'],
        'farm_id' => $userData['farm_id']
    ];
    
    // IMPORTANTE: Preservar foto de perfil existente do usuário
    // Se o usuário já tem uma foto de perfil, usar ela (não substituir pela foto do Google)
    $existingProfilePhoto = $userData['profile_photo'] ?? null;
    $profilePhotoToUse = null;
    
    if (!empty($existingProfilePhoto)) {
        // Usuário já tem foto de perfil - preservar e usar ela
        $profilePhotoToUse = $existingProfilePhoto;
    } else {
        // Usuário não tem foto de perfil - usar foto do Google (ou logo padrão)
        $profilePhotoToUse = $googlePicture;
        
        // Salvar foto do Google no banco apenas se não houver foto existente
        if ($googlePicture) {
            $updatePhotoStmt = $pdo->prepare("UPDATE users SET profile_photo = ? WHERE id = ?");
            $updatePhotoStmt->execute([$googlePicture, $userId]);
        }
    }
    
    // Atualizar dados da conta Google vinculada (último login)
    // Atualizar a tabela google_accounts com a foto do Google, mas não substituir a foto do usuário
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
    
    // Usar a foto preservada (existente ou Google se não houver)
    $_SESSION['profile_photo'] = $profilePhotoToUse;
    
    // Determinar redirect baseado no role e origem
    // Se veio do AgroNews, redirecionar de volta para lá após criar sessão
    if ($fromAgronews) {
        // Criar sessão no AgroNews também
        require_once __DIR__ . '/agronews360/includes/Database.class.php';
        require_once __DIR__ . '/agronews360/includes/LactechIntegration.class.php';
        
        try {
            // Carregar Database do AgroNews
            require_once __DIR__ . '/agronews360/includes/config_mysql.php';
            require_once __DIR__ . '/agronews360/includes/Database.class.php';
            
            $agronewsDb = Database::getInstance();
            $pdo = $agronewsDb->getConnection();
            
            // Todos os usuários são tratados igualmente no AgroNews (sem distinção de admin)
            $defaultRole = 'viewer';
            
            // Verificar se já existe usuário no AgroNews
            $stmt = $pdo->prepare("SELECT id FROM users WHERE lactech_user_id = ? OR email = ?");
            $stmt->execute([$existingUser['id'], $existingUser['email']]);
            $agronewsUser = $stmt->fetch();
            
            if (!$agronewsUser) {
                // Criar usuário no AgroNews se não existir
                $stmt = $pdo->prepare("
                    INSERT INTO users (name, email, password, role, is_active, lactech_user_id) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $existingUser['name'],
                    $existingUser['email'],
                    $existingUser['password'] ?? null,
                    $defaultRole,
                    $existingUser['is_active'] ?? 1,
                    $existingUser['id']
                ]);
                $agronewsUserId = $pdo->lastInsertId();
            } else {
                $agronewsUserId = $agronewsUser['id'];
                // Atualizar role para garantir que está como 'viewer'
                $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
                $stmt->execute([$defaultRole, $agronewsUserId]);
            }
            
            // Criar sessão do AgroNews
            $_SESSION['agronews_user_id'] = $agronewsUserId;
            $_SESSION['agronews_user_email'] = $existingUser['email'];
            $_SESSION['agronews_user_name'] = $existingUser['name'];
            $_SESSION['agronews_user_role'] = $defaultRole;
            $_SESSION['agronews_lactech_user_id'] = $existingUser['id'];
            $_SESSION['agronews_farm_id'] = $existingUser['farm_id'] ?? null;
            $_SESSION['agronews_farm_name'] = $existingUser['farm_name'] ?? null;
            $_SESSION['agronews_logged_in'] = true;
            
        } catch (Exception $e) {
            error_log("Erro ao criar sessão no AgroNews: " . $e->getMessage());
        }
        
        // Redirecionar de volta para o AgroNews
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'lactechsys.com';
        $redirectUrl = $protocol . '://' . $host . '/agronews360/index.php?login_success=1';
    } else {
        // Redirecionamento normal do Lactech
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

