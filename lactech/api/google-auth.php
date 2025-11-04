<?php
/**
 * API de Autenticação Google - LACTECH
 * Gerencia vinculação e desvinculação de contas Google
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
    
    // Garantir que a tabela google_accounts existe
    try {
        $pdo = $db->getConnection();
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS google_accounts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                google_id VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                name VARCHAR(255) NULL,
                picture TEXT NULL,
                is_primary TINYINT(1) DEFAULT 1,
                linked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                last_login_at DATETIME NULL,
                unlinked_at DATETIME NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_google_id (google_id),
                INDEX idx_email (email),
                INDEX idx_unlinked (unlinked_at),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    } catch (Exception $e) {
        error_log("Erro ao criar tabela google_accounts: " . $e->getMessage());
        // Continuar mesmo se der erro (pode ser que já exista)
    }
    
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    $userId = $_SESSION['user_id'] ?? null;
    
    if (!$userId) {
        echo json_encode([
            'success' => false,
            'error' => 'Usuário não autenticado'
        ]);
        exit;
    }
    
    switch ($action) {
        // ==================== STATUS DA VINCULAÇÃO ====================
        case 'get_status':
            $pdo = $db->getConnection();
            $stmt = $pdo->prepare("
                SELECT ga.*, u.email as user_email
                FROM google_accounts ga
                INNER JOIN users u ON ga.user_id = u.id
                WHERE u.id = :user_id 
                AND (ga.unlinked_at IS NULL OR ga.unlinked_at = '')
                ORDER BY ga.linked_at DESC
                LIMIT 1
            ");
            $stmt->execute([':user_id' => $userId]);
            $googleAccount = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Se não encontrou mas há vinculação (verificar sem INNER JOIN)
            if (!$googleAccount) {
                $stmt2 = $pdo->prepare("
                    SELECT ga.*
                    FROM google_accounts ga
                    WHERE ga.user_id = :user_id 
                    AND (ga.unlinked_at IS NULL OR ga.unlinked_at = '')
                    ORDER BY ga.linked_at DESC
                    LIMIT 1
                ");
                $stmt2->execute([':user_id' => $userId]);
                $googleAccount = $stmt2->fetch(PDO::FETCH_ASSOC);
            }
            
            echo json_encode([
                'success' => true,
                'linked' => !empty($googleAccount),
                'data' => $googleAccount ? [
                    'google_id' => $googleAccount['google_id'] ?? null,
                    'email' => $googleAccount['email'] ?? null,
                    'name' => $googleAccount['name'] ?? null,
                    'picture' => $googleAccount['picture'] ?? null,
                    'linked_at' => $googleAccount['linked_at'] ?? null
                ] : null,
                'user_email' => $googleAccount['user_email'] ?? null
            ]);
            break;
            
        // ==================== INICIAR VINCULAÇÃO (OAuth URL) ====================
        case 'get_auth_url':
            // Carregar configurações Google
            $googleConfigFile = __DIR__ . '/../includes/config_google.php';
            if (file_exists($googleConfigFile)) {
                require_once $googleConfigFile;
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'Configurações do Google não encontradas. Por favor, configure config_google.php'
                ]);
                exit;
            }
            
            // Verificar se as constantes estão definidas
            if (!defined('GOOGLE_CLIENT_ID') || !defined('GOOGLE_CLIENT_SECRET')) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Credenciais do Google não configuradas. Verifique config_google.php'
                ]);
                exit;
            }
            
            $clientId = GOOGLE_CLIENT_ID;
            
            // IMPORTANTE: Detectar ambiente e usar redirect_uri correto
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
            
            // Usar a URI configurada em produção
            if (defined('GOOGLE_REDIRECT_URI')) {
                // Em produção, usar a URI configurada no Google Console
                $redirectUri = GOOGLE_REDIRECT_URI;
            } else {
                // Fallback: construir baseado no servidor (forçar HTTPS)
                $protocol = 'https'; // Sempre HTTPS em produção
                $host = $_SERVER['HTTP_HOST'] ?? 'lactechsys.com';
                $redirectUri = $protocol . '://' . $host . '/google-callback.php';
            }
            
            $scope = defined('GOOGLE_SCOPES') ? GOOGLE_SCOPES : 'email profile';
            $state = bin2hex(random_bytes(16)); // CSRF protection
            
            // Salvar state na sessão
            $_SESSION['google_oauth_state'] = $state;
            $_SESSION['google_oauth_user_id'] = $userId;
            
            // IMPORTANTE: Validar que a redirect_uri está correta
            if ($redirectUri !== 'https://lactechsys.com/google-callback.php') {
                error_log("⚠️ AVISO: redirect_uri não está correto: $redirectUri");
            }
            
            // Log para debug (remover em produção)
            error_log("🔍 Google Auth - redirect_uri: $redirectUri");
            error_log("🔍 Google Auth - client_id: $clientId");
            
            // IMPORTANTE: Não usar access_type=offline pois não precisamos de refresh_token
            // Estamos apenas vinculando a conta Google para receber OTPs por e-mail
            // Usar prompt=select_account para permitir escolher conta secundária
            $authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
                'client_id' => $clientId,
                'redirect_uri' => $redirectUri,
                'response_type' => 'code',
                'scope' => $scope,
                'state' => $state,
                'prompt' => 'select_account consent', // Permite escolher conta secundária
                'access_type' => 'online' // Não precisamos de refresh token
            ]);
            
            // Log da URL completa gerada (remover em produção)
            error_log("🔍 Google Auth - auth_url gerada: " . substr($authUrl, 0, 200) . "...");
            
            echo json_encode([
                'success' => true,
                'auth_url' => $authUrl,
                'debug' => [
                    'redirect_uri' => $redirectUri,
                    'client_id' => $clientId
                ]
            ]);
            break;
            
        // ==================== VINCULAR CONTA ====================
        case 'link_account':
            $googleId = $_POST['google_id'] ?? null;
            $email = $_POST['email'] ?? null;
            $name = $_POST['name'] ?? null;
            $picture = $_POST['picture'] ?? null;
            
            if (!$googleId || !$email) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Dados do Google incompletos'
                ]);
                exit;
            }
            
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
                echo json_encode([
                    'success' => false,
                    'error' => 'Já existe uma conta Google vinculada'
                ]);
                exit;
            }
            
            // Verificar se o Google ID já está vinculado a outra conta
            $stmt = $pdo->prepare("
                SELECT id FROM google_accounts 
                WHERE google_id = :google_id 
                AND (unlinked_at IS NULL OR unlinked_at = '')
            ");
            $stmt->execute([':google_id' => $googleId]);
            $existingGoogle = $stmt->fetch();
            
            if ($existingGoogle) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Esta conta Google já está vinculada a outro usuário'
                ]);
                exit;
            }
            
            // Vincular conta
            $stmt = $pdo->prepare("
                INSERT INTO google_accounts (user_id, google_id, email, name, picture, is_primary, linked_at)
                VALUES (:user_id, :google_id, :email, :name, :picture, 1, NOW())
            ");
            
            $stmt->execute([
                ':user_id' => $userId,
                ':google_id' => $googleId,
                ':email' => $email,
                ':name' => $name,
                ':picture' => $picture
            ]);
            
            // Atualizar e-mail do usuário se não tiver
            $userStmt = $pdo->prepare("UPDATE users SET email = :email WHERE id = :user_id AND (email IS NULL OR email = '')");
            $userStmt->execute([':email' => $email, ':user_id' => $userId]);
            
            // Log de auditoria
            $security->logSecurityAction($userId, 'google_linked', "Conta Google vinculada: {$email}", true, [
                'google_id' => $googleId,
                'email' => $email
            ]);
            
            // Enviar notificação
            $security->emailService->sendSecurityNotification(
                $email,
                'google_linked',
                'Sua conta Google foi vinculada com sucesso. Você agora pode receber códigos OTP por e-mail.'
            );
            
            echo json_encode([
                'success' => true,
                'message' => 'Conta Google vinculada com sucesso'
            ]);
            break;
            
        // ==================== DESVINCULAR CONTA ====================
        case 'unlink_account':
            // Exigir OTP e senha atual para desvincular
            $otpCode = $_POST['otp_code'] ?? null;
            $currentPassword = $_POST['current_password'] ?? null;
            
            if (!$otpCode) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Código OTP é obrigatório para desvincular conta Google'
                ]);
                exit;
            }
            
            // Validar OTP
            $otpValidation = $security->validateOTP($userId, $otpCode, 'google_unlink');
            if (!$otpValidation['success']) {
                echo json_encode($otpValidation);
                exit;
            }
            
            // Verificar senha atual (opcional, mas recomendado)
            if ($currentPassword) {
                $user = $db->query("SELECT password FROM users WHERE id = ?", [$userId]);
                if (empty($user) || !password_verify($currentPassword, $user[0]['password'])) {
                    echo json_encode([
                        'success' => false,
                        'error' => 'Senha atual incorreta'
                    ]);
                    exit;
                }
            }
            
            // Desvincular
            $pdo = $db->getConnection();
            $stmt = $pdo->prepare("
                UPDATE google_accounts 
                SET unlinked_at = NOW()
                WHERE user_id = :user_id 
                AND (unlinked_at IS NULL OR unlinked_at = '')
            ");
            
            $stmt->execute([':user_id' => $userId]);
            
            // Buscar e-mail da conta Google para notificação
            $stmt = $pdo->prepare("
                SELECT email FROM google_accounts 
                WHERE user_id = :user_id 
                ORDER BY unlinked_at DESC
                LIMIT 1
            ");
            $stmt->execute([':user_id' => $userId]);
            $googleEmail = $stmt->fetch(PDO::FETCH_ASSOC)['email'] ?? null;
            
            // Log de auditoria
            $security->logSecurityAction($userId, 'google_unlinked', "Conta Google desvinculada", true, [
                'email' => $googleEmail
            ]);
            
            // Enviar notificação se houver e-mail
            if ($googleEmail) {
                $security->emailService->sendSecurityNotification(
                    $googleEmail,
                    'google_unlinked',
                    'Sua conta Google foi desvinculada. Você não poderá mais alterar sua senha até vincular uma nova conta Google.'
                );
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Conta Google desvinculada com sucesso'
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
    error_log("Erro na API Google Auth: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Erro no servidor: ' . $e->getMessage()
    ]);
}
?>

