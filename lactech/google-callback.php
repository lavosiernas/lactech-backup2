<?php
/**
 * Google OAuth Callback Handler
 * Processa o retorno do Google após autorização
 */

// SOLUÇÃO BASEADA EM CASOS REAIS: Usar output buffering para garantir que headers sejam enviados
// Isso garante que cookies de sessão sejam enviados ANTES de qualquer redirect
ob_start();

// IMPORTANTE: Iniciar sessão ANTES de incluir qualquer coisa
// Isso garante que a sessão seja mantida corretamente mesmo quando o Google redireciona
if (session_status() === PHP_SESSION_NONE) {
    // Configurar cookies de sessão corretamente antes de iniciar
    // Usar o mesmo nome de sessão que o sistema principal usa
    session_name('PHPSESSID');
    
    // Detectar se está em localhost
    $isLocal = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1', '::1']) ||
               strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false;
    
    // Usar as mesmas configurações do config_login.php para manter compatibilidade
    // IMPORTANTE: Para OAuth callbacks, SameSite=Lax é necessário para redirecionamentos
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', $isLocal ? 0 : 1); // HTTPS em produção, HTTP em local
    ini_set('session.cookie_samesite', 'Lax'); // Lax permite cookies em redirecionamentos do mesmo domínio
    ini_set('session.cookie_path', '/'); // Caminho raiz para funcionar em todo o site
    
    // Tentar recuperar o cookie de sessão existente se houver
    if (isset($_COOKIE[session_name()])) {
        session_id($_COOKIE[session_name()]);
    }
    
    // Iniciar sessão sem incluir config_login.php que pode verificar sessão e redirecionar
    session_start();
    
    // Debug: Log da sessão (remover em produção)
    error_log("🔍 Google Callback - Sessão iniciada. ID: " . session_id());
    error_log("🔍 Google Callback - user_id: " . ($_SESSION['user_id'] ?? 'NULL'));
    error_log("🔍 Google Callback - logged_in: " . ($_SESSION['logged_in'] ?? 'NULL'));
}

// Importante: Manter a sessão PHP ativa durante todo o processo
// Não destruir ou limpar a sessão aqui
// NÃO incluir config_login.php aqui para evitar verificação de sessão que redireciona

// Incluir apenas o que é necessário para o callback funcionar
require_once __DIR__ . '/includes/config_mysql.php';
require_once __DIR__ . '/includes/config_login.php'; // Para usar safeRedirect
require_once __DIR__ . '/includes/Database.class.php';
require_once __DIR__ . '/includes/SecurityService.class.php';

// Carregar configurações Google
$googleConfigFile = __DIR__ . '/includes/config_google.php';
if (!file_exists($googleConfigFile)) {
    // Tentar carregar do JSON se config_google.php não existir
    $jsonFile = __DIR__ . '/api/client_secret_563053705449-hurd35dp6n644skh4qocmaf8i82u1u1f.apps.googleusercontent.com.json';
    if (file_exists($jsonFile)) {
        $jsonData = json_decode(file_get_contents($jsonFile), true);
        if (isset($jsonData['web'])) {
            define('GOOGLE_CLIENT_ID', $jsonData['web']['client_id']);
            define('GOOGLE_CLIENT_SECRET', $jsonData['web']['client_secret']);
            $redirectUris = $jsonData['web']['redirect_uris'] ?? [];
            define('GOOGLE_REDIRECT_URI', !empty($redirectUris) ? $redirectUris[0] : 'https://lactechsys.com/google-callback.php');
            define('GOOGLE_SCOPES', 'email profile');
        }
    } else {
        die('Configurações do Google não encontradas. Por favor, configure config_google.php');
    }
} else {
    require_once $googleConfigFile;
}

// Verificar se há código de autorização
$code = $_GET['code'] ?? null;
$state = $_GET['state'] ?? null;
$error = $_GET['error'] ?? null;

if ($error) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Erro - Vinculação Google</title>
    </head>
    <body>
        <script>
            if (window.opener) {
                window.opener.postMessage({
                    type: 'google_oauth_error',
                    message: 'Erro ao vincular conta Google: <?php echo addslashes($error); ?>'
                }, window.location.origin);
                window.close();
            } else {
                window.location.href = '/gerente-completo.php?google_error=<?php echo urlencode($error); ?>';
            }
        </script>
        <p>Erro ao processar autorização. Redirecionando...</p>
    </body>
    </html>
    <?php
    exit;
}

if (!$code || !$state) {
    safeRedirect('/gerente-completo.php?google_error=missing_parameters');
}

// Verificar state (CSRF protection)
if (!isset($_SESSION['google_oauth_state']) || $_SESSION['google_oauth_state'] !== $state) {
    safeRedirect('/gerente-completo.php?google_error=invalid_state');
}

// IMPORTANTE: Usar sempre a sessão principal do usuário (não OAuth)
// O Google é apenas para OTPs, não para login
// Se o usuário clicou em "Vincular Google", ele DEVE estar logado na sessão principal
$userId = $_SESSION['user_id'] ?? null;

if (!$userId || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Se não houver sessão ativa, mas o usuário iniciou o processo de vinculação,
    // melhor retornar erro no popup em vez de redirecionar (evitar loops)
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Erro - Vinculação Google</title>
    </head>
    <body>
        <script>
            if (window.opener) {
                // Se for popup, enviar mensagem de erro para o parent
                window.opener.postMessage({
                    type: 'google_oauth_error',
                    message: 'Erro: Sessão expirada. Por favor, faça login novamente e tente vincular o Google novamente.'
                }, window.location.origin);
                window.close();
            } else {
                // Se não for popup, redirecionar para gerente-completo (se realmente não houver sessão, ele vai redirecionar para login)
                window.location.replace('/gerente-completo.php?google_error=session_required');
            }
        </script>
        <p>Erro: Sessão não encontrada. Redirecionando...</p>
    </body>
    </html>
    <?php
    exit;
}

try {
    // Trocar código de autorização por token de acesso
    $tokenUrl = 'https://oauth2.googleapis.com/token';
    
    // Usar a URI configurada ou detectar automaticamente
    if (defined('GOOGLE_REDIRECT_URI')) {
        $redirectUri = GOOGLE_REDIRECT_URI;
    } else {
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $redirectUri = $protocol . '://' . $host . dirname($_SERVER['SCRIPT_NAME']) . '/google-callback.php';
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
        safeRedirect('/gerente-completo.php?google_error=token_error');
    }
    
    $tokenData = json_decode($tokenResponse, true);
    
    if (!isset($tokenData['access_token'])) {
        error_log("Token não encontrado na resposta: " . $tokenResponse);
        safeRedirect('/gerente-completo.php?google_error=no_token');
    }
    
    // Obter informações do usuário Google
    $userInfoUrl = 'https://www.googleapis.com/oauth2/v2/userinfo?access_token=' . urlencode($tokenData['access_token']);
    
    $ch = curl_init($userInfoUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $userInfoResponse = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        error_log("Erro ao obter informações do usuário: HTTP $httpCode");
        safeRedirect('/gerente-completo.php?google_error=user_info_error');
    }
    
    $userInfo = json_decode($userInfoResponse, true);
    
    if (!$userInfo || !isset($userInfo['id'])) {
        error_log("Informações do usuário inválidas: " . $userInfoResponse);
        safeRedirect('/gerente-completo.php?google_error=invalid_user_info');
    }
    
    // Validar se o email está presente
    if (empty($userInfo['email'])) {
        error_log("Email não encontrado nas informações do Google: " . $userInfoResponse);
        safeRedirect('/gerente-completo.php?google_error=email_not_found');
    }
    
    // Vincular conta Google ao usuário
    $db = Database::getInstance();
    $security = SecurityService::getInstance();
    $pdo = $db->getConnection();
    
    // Verificar se já existe vinculação ativa
    $stmt = $pdo->prepare("
        SELECT id FROM google_accounts 
        WHERE user_id = :user_id 
        AND (unlinked_at IS NULL OR unlinked_at = '')
    ");
    $stmt->execute([':user_id' => $userId]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        // Se não houver foto do Google, usar logo do sistema como padrão
        $pictureToUse = $userInfo['picture'] ?? null;
        if (empty($pictureToUse)) {
            $pictureToUse = 'https://i.postimg.cc/vmrkgDcB/lactech.png';
        }
        
        // Atualizar vinculação existente
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
            ':id' => $existing['id'],
            ':google_id' => $userInfo['id'],
            ':email' => $userInfo['email'],
            ':name' => $userInfo['name'] ?? null,
            ':picture' => $pictureToUse
        ]);
    } else {
        // Verificar se o Google ID já está vinculado a outra conta
        $stmt = $pdo->prepare("
            SELECT id FROM google_accounts 
            WHERE google_id = :google_id 
            AND (unlinked_at IS NULL OR unlinked_at = '')
        ");
        $stmt->execute([':google_id' => $userInfo['id']]);
        $existingGoogle = $stmt->fetch();
        
        if ($existingGoogle) {
            safeRedirect('/gerente-completo.php?google_error=already_linked');
        }
        
        // Se não houver foto do Google, usar logo do sistema como padrão
        $pictureToUse = $userInfo['picture'] ?? null;
        if (empty($pictureToUse)) {
            $pictureToUse = 'https://i.postimg.cc/vmrkgDcB/lactech.png';
        }
        
        // Verificar se já existe um registro com este google_id (mesmo que desvinculado)
        $checkStmt = $pdo->prepare("
            SELECT id, user_id FROM google_accounts 
            WHERE google_id = :google_id
            LIMIT 1
        ");
        $checkStmt->execute([':google_id' => $userInfo['id']]);
        $existingRecord = $checkStmt->fetch();
        
        if ($existingRecord) {
            // Se o registro existe e pertence ao mesmo usuário, atualizar
            if ($existingRecord['user_id'] == $userId) {
                $stmt = $pdo->prepare("
                    UPDATE google_accounts 
                    SET email = :email, 
                        name = :name, 
                        picture = :picture, 
                        is_primary = 1, 
                        linked_at = NOW(),
                        unlinked_at = NULL
                    WHERE google_id = :google_id 
                    AND user_id = :user_id
                ");
                
                $stmt->execute([
                    ':google_id' => $userInfo['id'],
                    ':user_id' => $userId,
                    ':email' => $userInfo['email'],
                    ':name' => $userInfo['name'] ?? null,
                    ':picture' => $pictureToUse
                ]);
            } else {
                // Se pertence a outro usuário, erro
                ob_end_clean();
                ?>
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Erro - Vinculação Google</title>
                </head>
                <body>
                    <script>
                        if (window.opener) {
                            window.opener.postMessage({
                                type: 'google_oauth_error',
                                message: 'Erro: Esta conta Google já está vinculada a outro usuário'
                            }, window.location.origin);
                            window.close();
                        } else {
                            window.location.href = '/gerente-completo.php?google_error=' + encodeURIComponent('Esta conta Google já está vinculada a outro usuário');
                        }
                    </script>
                    <p>Esta conta Google já está vinculada a outro usuário. Redirecionando...</p>
                </body>
                </html>
                <?php
                exit;
            }
        } else {
            // Não existe registro, inserir novo
            $stmt = $pdo->prepare("
                INSERT INTO google_accounts (user_id, google_id, email, name, picture, is_primary, linked_at)
                VALUES (:user_id, :google_id, :email, :name, :picture, 1, NOW())
            ");
            $stmt->execute([
                ':user_id' => $userId,
                ':google_id' => $userInfo['id'],
                ':email' => $userInfo['email'],
                ':name' => $userInfo['name'] ?? null,
                ':picture' => $pictureToUse
            ]);
        }
    }
    
    // Atualizar e-mail do usuário se não tiver
    $userStmt = $pdo->prepare("UPDATE users SET email = :email WHERE id = :user_id AND (email IS NULL OR email = '')");
    $userStmt->execute([':email' => $userInfo['email'], ':user_id' => $userId]);
    
    // Log de auditoria
    $security->logSecurityAction($userId, 'google_linked', "Conta Google vinculada via OAuth: {$userInfo['email']}", true, [
        'google_id' => $userInfo['id'],
        'email' => $userInfo['email']
    ]);
    
    // Enviar notificação
    $security->emailService->sendSecurityNotification(
        $userInfo['email'],
        'google_linked',
        'Sua conta Google foi vinculada com sucesso. Você agora pode receber códigos OTP por e-mail.'
    );
    
    // Limpar apenas o state da sessão OAuth (manter sessão principal do usuário)
    // A sessão principal do usuário já está ativa (verificada no início)
    unset($_SESSION['google_oauth_state']);
    unset($_SESSION['google_oauth_user_id']);
    
    // IMPORTANTE: Garantir que a sessão principal está completa e correta antes de redirecionar
    // Precisamos ter TODOS os dados de sessão necessários para que gerente-completo.php não redirecione
    
    // Buscar todos os dados do usuário do banco para garantir sessão completa
    $pdo = $db->getConnection();
    $userStmt = $pdo->prepare("
        SELECT id, name, email, role, farm_id, profile_photo 
        FROM users 
        WHERE id = :user_id 
        LIMIT 1
    ");
    $userStmt->execute([':user_id' => $userId]);
    $userData = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($userData) {
        // Garantir TODOS os dados de sessão necessários
        $_SESSION['user_id'] = $userData['id'];
        $_SESSION['user_name'] = $userData['name'];
        $_SESSION['user_email'] = $userData['email'];
        $_SESSION['user_role'] = $userData['role'];
        $_SESSION['farm_id'] = $userData['farm_id'];
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
        
        // Se não houver foto de perfil, usar logo do sistema como padrão
        if (!empty($userData['profile_photo'])) {
            $_SESSION['profile_photo'] = $userData['profile_photo'];
        } else {
            // Usar logo do sistema como foto de perfil padrão
            $_SESSION['profile_photo'] = 'https://i.postimg.cc/vmrkgDcB/lactech.png';
            
            // Salvar no banco também
            $updatePhotoStmt = $pdo->prepare("UPDATE users SET profile_photo = ? WHERE id = ?");
            $updatePhotoStmt->execute(['https://i.postimg.cc/vmrkgDcB/lactech.png', $userId]);
        }
        
        // SOLUÇÃO BASEADA EM CASOS REAIS: Marcar na sessão que veio do callback
        // Isso permite que gerente-completo.php saiba que veio do Google e dê tempo para a sessão ser recuperada
        $_SESSION['from_google_callback'] = true;
        $_SESSION['google_callback_time'] = time();
        
        // IMPORTANTE: Não chamar setcookie manualmente aqui!
        // O PHP já gerencia o cookie de sessão automaticamente
        // Chamar setcookie manualmente pode causar conflitos e perder a sessão
        
        // Forçar escrita da sessão antes de redirecionar
        // Usar session_write_close() SOMENTE aqui, no final, ANTES do redirect
        // Isso garante que a sessão seja salva no servidor antes do redirecionamento
        session_write_close();
        
        // Debug: Log antes de redirecionar (remover em produção)
        error_log("✅ Google Callback - Sessão completa garantida. user_id: $userId, role: " . ($_SESSION['user_role'] ?? 'NULL'));
    } else {
        error_log("❌ ERRO: Usuário não encontrado no banco! user_id: $userId");
    }
    
    // Debug: Log antes de redirecionar (remover em produção)
    error_log("✅ Google Callback - Vinculação concluída. user_id: $userId, role: " . ($_SESSION['user_role'] ?? 'NULL'));
    
    // IMPORTANTE: O Google é vinculado como conta secundária apenas para OTPs
    // O login continua sendo o login padrão do sistema
    
    // Sempre usar JavaScript para garantir que funcione tanto em popup quanto fora
    // Isso evita problemas de sessão e garante que o redirecionamento funcione
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Vinculação Google - Sucesso</title>
        <meta charset="UTF-8">
    </head>
    <body>
        <script>
            (function() {
                // SEMPRE verificar window.opener primeiro (mais confiável)
                if (window.opener && !window.opener.closed) {
                    try {
                        // Aguardar um pouco para garantir que a página principal está pronta
                        setTimeout(function() {
                            // Enviar mensagem para o parent
                            window.opener.postMessage({
                                type: 'google_oauth_success',
                                message: 'Conta Google vinculada com sucesso! Você pode receber códigos OTP por e-mail.'
                            }, window.location.origin);
                            
                            // Fechar popup após enviar mensagem
                            setTimeout(function() {
                                window.close();
                            }, 200);
                        }, 100);
                    } catch (e) {
                        console.error('Erro ao comunicar com parent:', e);
                        // Se falhar, fazer redirect normal
                        window.location.replace('/gerente-completo.php?google_linked=success');
                    }
                } else {
                    // Não é popup ou popup fechado - redirecionar normalmente
                    window.location.replace('/gerente-completo.php?google_linked=success');
                }
            })();
        </script>
        <div style="display: flex; align-items: center; justify-content: center; height: 100vh; background: white; font-family: Arial, sans-serif;">
            <div style="text-align: center;">
                <p style="color: #16a34a; font-size: 18px; font-weight: bold;">Conta Google vinculada com sucesso!</p>
                <p style="color: #666; font-size: 14px;">Fechando...</p>
            </div>
        </div>
    </body>
    </html>
    <?php
    
} catch (Exception $e) {
    error_log("Erro no callback Google: " . $e->getMessage());
    
    // Sempre tentar voltar para gerente-completo.php (mesmo em erro)
    // O usuário deve estar logado para acessar essa funcionalidade
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Erro - Vinculação Google</title>
    </head>
    <body>
        <script>
            (function() {
                if (window.opener && !window.opener.closed) {
                    try {
                        // Se for popup, enviar mensagem de erro para o parent
                        window.opener.postMessage({
                            type: 'google_oauth_error',
                            message: 'Erro ao vincular conta Google: <?php echo addslashes($e->getMessage()); ?>'
                        }, window.location.origin);
                        
                        // Fechar popup imediatamente
                        setTimeout(function() {
                            window.close();
                        }, 100);
                    } catch (e) {
                        console.error('Erro ao comunicar com parent:', e);
                        window.location.replace('/gerente-completo.php?google_error=callback_error');
                    }
                } else {
                    // Se não for popup, sempre voltar para gerente-completo.php
                    window.location.replace('/gerente-completo.php?google_error=callback_error');
                }
            })();
        </script>
    </body>
    </html>
    <?php
    exit;
}
?>

