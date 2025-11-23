<?php
/**
 * SafeNode - API para Ações Perigosas
 * Endpoint para processar ações críticas (encerrar sessões, excluir conta)
 */

session_start();

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/SecurityHelpers.php';
require_once __DIR__ . '/../includes/SessionManager.php';
require_once __DIR__ . '/../includes/ActivityLogger.php';
require_once __DIR__ . '/../includes/EmailService.php';

// Verificar se está logado
if (!isset($_SESSION['safenode_logged_in']) || $_SESSION['safenode_logged_in'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Não autenticado']);
    exit;
}

$db = getSafeNodeDatabase();
$userId = $_SESSION['safenode_user_id'] ?? null;
$action = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método não permitido']);
    exit;
}

if (!CSRFProtection::validate()) {
    echo json_encode(['success' => false, 'error' => 'Token de segurança inválido']);
    exit;
}

try {
    // STEP 1: Verificar senha e enviar código OTP
    if (isset($_POST['step']) && $_POST['step'] === 'request_code') {
        $password = $_POST['password'] ?? '';
        
        if (empty($password)) {
            echo json_encode(['success' => false, 'error' => 'Senha é obrigatória']);
            exit;
        }
        
        // Buscar senha do usuário
        $stmt = $db->prepare("SELECT password_hash, email FROM safenode_users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user || !password_verify($password, $user['password_hash'])) {
            echo json_encode(['success' => false, 'error' => 'Senha incorreta']);
            exit;
        }
        
        // Gerar código OTP
        $otpCode = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        
        // Salvar OTP no banco
        $stmt = $db->prepare("
            INSERT INTO safenode_otp_codes 
            (user_id, email, otp_code, action, expires_at, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $userId,
            $user['email'],
            $otpCode,
            $action === 'terminate_sessions' ? 'terminate_sessions' : 'delete_account',
            $expiresAt
        ]);
        
        // Enviar e-mail
        $emailService = new SafeNodeEmailService();
        $emailResult = $emailService->sendSecurityCode($user['email'], $otpCode, $action);
        
        if ($emailResult['success']) {
            $_SESSION['dangerous_action_pending'] = $action;
            echo json_encode(['success' => true, 'message' => 'Código enviado para seu e-mail']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Erro ao enviar código. Tente novamente.']);
        }
        exit;
    }
    
    // STEP 2: Verificar código OTP e executar ação
    if (isset($_POST['step']) && $_POST['step'] === 'verify_code') {
        $otpCode = $_POST['otp_code'] ?? '';
        $pendingAction = $_SESSION['dangerous_action_pending'] ?? '';
        
        if (empty($otpCode) || empty($pendingAction)) {
            echo json_encode(['success' => false, 'error' => 'Código inválido']);
            exit;
        }
        
        // Buscar e-mail do usuário
        $stmt = $db->prepare("SELECT email FROM safenode_users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            echo json_encode(['success' => false, 'error' => 'Usuário não encontrado']);
            exit;
        }
        
        // Verificar código OTP
        $actionType = $pendingAction === 'terminate_sessions' ? 'terminate_sessions' : 'delete_account';
        $stmt = $db->prepare("
            SELECT id FROM safenode_otp_codes 
            WHERE user_id = ? AND email = ? AND otp_code = ? 
            AND action = ? AND verified = 0 AND expires_at > NOW() 
            ORDER BY created_at DESC LIMIT 1
        ");
        $stmt->execute([$userId, $user['email'], $otpCode, $actionType]);
        $otpRecord = $stmt->fetch();
        
        if (!$otpRecord) {
            echo json_encode(['success' => false, 'error' => 'Código inválido ou expirado']);
            exit;
        }
        
        // Marcar OTP como verificado
        $stmt = $db->prepare("UPDATE safenode_otp_codes SET verified = 1, verified_at = NOW() WHERE id = ?");
        $stmt->execute([$otpRecord['id']]);
        
        // Executar ação
        $activityLogger = new ActivityLogger($db);
        
        if ($pendingAction === 'terminate_sessions') {
            // Encerrar todas as sessões exceto a atual
            $sessionManager = new SessionManager($db);
            $currentToken = $_SESSION['safenode_session_token'] ?? null;
            
            if ($sessionManager->terminateAllSessions($userId, $currentToken)) {
                $activityLogger->logSessionTerminated($userId, null, true);
                unset($_SESSION['dangerous_action_pending']);
                
                // Limpar todas as sessões PHP exceto a atual
                $keepVars = ['safenode_logged_in', 'safenode_username', 'safenode_user_id', 
                            'safenode_user_email', 'safenode_user_full_name', 'safenode_user_role'];
                foreach ($_SESSION as $key => $value) {
                    if (!in_array($key, $keepVars)) {
                        unset($_SESSION[$key]);
                    }
                }
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Todas as sessões foram encerradas com sucesso!',
                    'redirect' => 'sessions.php'
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Erro ao encerrar sessões']);
            }
        } elseif ($pendingAction === 'delete_account') {
            // Excluir conta permanentemente
            // IMPORTANTE: Esta é uma ação IRREVERSÍVEL
            // Aqui você pode adicionar lógica adicional antes de excluir
            
            // Registrar atividade antes de excluir
            $activityLogger->log(
                $userId,
                'account_deleted',
                'Conta excluída permanentemente',
                'success'
            );
            
            // Excluir todas as sessões primeiro (evita erro de FK)
            $stmt = $db->prepare("DELETE FROM safenode_user_sessions WHERE user_id = ?");
            $stmt->execute([$userId]);
            
            // Excluir usuário (CASCADE deve deletar relacionamentos)
            $stmt = $db->prepare("DELETE FROM safenode_users WHERE id = ?");
            $stmt->execute([$userId]);
            
            // Destruir sessão
            session_destroy();
            
            unset($_SESSION['dangerous_action_pending']);
            
            echo json_encode([
                'success' => true,
                'message' => 'Conta excluída permanentemente.',
                'redirect' => 'index.php'
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Ação inválida']);
        }
        exit;
    }
    
    echo json_encode(['success' => false, 'error' => 'Etapa inválida']);
    
} catch (Exception $e) {
    error_log("DangerousAction Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erro ao processar ação']);
}


