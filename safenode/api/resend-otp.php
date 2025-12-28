<?php
/**
 * API para reenviar código OTP
 * Suporta: registro de conta e reset de senha
 */

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/HumanVerification.php';
require_once __DIR__ . '/../includes/EmailService.php';
require_once __DIR__ . '/../includes/EmailSender.php';

$response = ['success' => false, 'error' => ''];

try {
    // Validar método
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $response['error'] = 'Método não permitido';
        echo json_encode($response);
        exit;
    }
    
    // Validar verificação humana
    $hvError = '';
    if (!SafeNodeHumanVerification::validateRequest($_POST, $hvError)) {
        $response['error'] = $hvError ?: 'Falha na verificação de segurança';
        echo json_encode($response);
        exit;
    }
    
    // Obter tipo de OTP
    $type = $_POST['type'] ?? '';
    
    if ($type === 'registration') {
        // Reenviar OTP de registro
        if (!isset($_SESSION['safenode_register_data']) || !isset($_SESSION['safenode_register_data']['email'])) {
            $response['error'] = 'Dados de registro não encontrados';
            echo json_encode($response);
            exit;
        }
        
        $registerData = $_SESSION['safenode_register_data'];
        $userEmail = $registerData['email'];
        
        $pdo = getSafeNodeDatabase();
        if (!$pdo) {
            $response['error'] = 'Erro ao conectar ao banco de dados';
            echo json_encode($response);
            exit;
        }
        
        // Gerar novo código OTP
        $otpCode = str_pad(strval(rand(100000, 999999)), 6, '0', STR_PAD_LEFT);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        
        // Invalidar códigos anteriores
        $stmt = $pdo->prepare("UPDATE safenode_otp_codes SET verified = 1 WHERE email = ? AND action = 'email_verification' AND verified = 0 AND user_id IS NULL");
        $stmt->execute([$userEmail]);
        
        // Salvar novo código
        $stmt = $pdo->prepare("INSERT INTO safenode_otp_codes (user_id, email, otp_code, action, expires_at) VALUES (NULL, ?, ?, 'email_verification', ?)");
        $stmt->execute([$userEmail, $otpCode, $expiresAt]);
        
        // Enviar email
        $emailService = SafeNodeEmailService::getInstance();
        $emailResult = $emailService->sendRegistrationOTP($userEmail, $otpCode, $registerData['full_name'] ?? $registerData['username']);
        
        if ($emailResult['success']) {
            $response['success'] = true;
            $response['message'] = 'Novo código enviado para seu email!';
        } else {
            $response['error'] = 'Erro ao enviar código. Tente novamente.';
        }
        
    } elseif ($type === 'password_reset') {
        // Reenviar OTP de reset de senha
        if (!isset($_SESSION['reset_email_for_otp'])) {
            $response['error'] = 'Email não encontrado na sessão';
            echo json_encode($response);
            exit;
        }
        
        $userEmail = $_SESSION['reset_email_for_otp'];
        
        $db = getSafeNodeDatabase();
        if (!$db) {
            $response['error'] = 'Erro ao conectar ao banco de dados';
            echo json_encode($response);
            exit;
        }
        
        // Buscar usuário
        $stmt = $db->prepare("SELECT id, username, full_name FROM safenode_users WHERE email = ?");
        $stmt->execute([$userEmail]);
        $user = $stmt->fetch();
        
        if (!$user) {
            $response['error'] = 'Usuário não encontrado';
            echo json_encode($response);
            exit;
        }
        
        // Verificar se conta está vinculada ao Google
        $stmt = $db->prepare("SELECT google_id FROM safenode_users WHERE email = ?");
        $stmt->execute([$userEmail]);
        $userCheck = $stmt->fetch();
        
        if ($userCheck && !empty($userCheck['google_id'])) {
            $response['error'] = 'Esta conta está vinculada ao Google. Para alterar sua senha, utilize a opção do Google.';
            echo json_encode($response);
            exit;
        }
        
        // Gerar novo código OTP
        $otpCode = str_pad(strval(random_int(100000, 999999)), 6, '0', STR_PAD_LEFT);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        
        // Invalidar códigos anteriores para este email
        $stmt = $db->prepare("UPDATE safenode_password_reset_otp SET used_at = NOW() WHERE email = ? AND used_at IS NULL");
        $stmt->execute([$userEmail]);
        
        // Salvar novo código
        $stmt = $db->prepare("
            INSERT INTO safenode_password_reset_otp (user_id, email, otp_code, expires_at, ip_address, attempts, max_attempts) 
            VALUES (?, ?, ?, ?, ?, 0, 5)
        ");
        $stmt->execute([
            $user['id'],
            $userEmail,
            $otpCode,
            $expiresAt,
            $ipAddress
        ]);
        
        // Enviar email
        $emailSender = new EmailSender();
        $username = !empty($user['full_name']) ? $user['full_name'] : $user['username'];
        $emailSent = $emailSender->sendPasswordResetOTP($userEmail, $otpCode, $username);
        
        $response['success'] = true;
        $response['message'] = 'Novo código enviado para seu email!';
        
    } else {
        $response['error'] = 'Tipo de OTP inválido';
    }
    
} catch (Exception $e) {
    error_log("Resend OTP Error: " . $e->getMessage());
    $response['error'] = 'Erro ao reenviar código. Tente novamente.';
}

echo json_encode($response);

