<?php
/**
 * API de Segurança - LACTECH
 * Endpoint para gerenciar ações de segurança: OTP, verificação de e-mail, alteração de senha
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
    $userId = $_SESSION['user_id'] ?? null;
    
    if (!$userId) {
        echo json_encode([
            'success' => false,
            'error' => 'Usuário não autenticado'
        ]);
        exit;
    }
    
    switch ($action) {
        // ==================== VERIFICAÇÃO DE E-MAIL ====================
        case 'request_email_verification':
            $email = $_POST['email'] ?? $_GET['email'] ?? null;
            
            if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode([
                    'success' => false,
                    'error' => 'E-mail inválido'
                ]);
                exit;
            }
            
            $result = $security->requestEmailVerification($userId, $email);
            echo json_encode($result);
            break;
            
        case 'verify_email':
            $token = $_POST['token'] ?? $_GET['token'] ?? null;
            
            if (!$token) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Token não fornecido'
                ]);
                exit;
            }
            
            $result = $security->verifyEmail($token);
            echo json_encode($result);
            break;
            
        // ==================== OTP (ONE-TIME PASSWORD) ====================
        case 'generate_otp':
            $otpAction = $_POST['action_type'] ?? $_POST['action'] ?? null;
            $email = $_POST['email'] ?? null;
            
            if (!$otpAction) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Tipo de ação não especificado'
                ]);
                exit;
            }
            
            // O generateOTP agora busca automaticamente o email da conta Google vinculada
            // Se não tiver Google vinculado, usa o email do sistema
            // Não precisa passar email aqui, deixar o SecurityService decidir
            $result = $security->generateOTP($userId, $otpAction, $email);
            // Em produção, não retornar o código, apenas confirmar o envio
            if ($result['success']) {
                unset($result['code']);
            }
            echo json_encode($result);
            break;
            
        case 'validate_otp':
            $code = $_POST['code'] ?? $_GET['code'] ?? null;
            $otpAction = $_POST['action_type'] ?? $_POST['action'] ?? null;
            
            if (!$code || strlen($code) !== 6) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Código OTP inválido'
                ]);
                exit;
            }
            
            if (!$otpAction) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Tipo de ação não especificado'
                ]);
                exit;
            }
            
            $result = $security->validateOTP($userId, $code, $otpAction);
            echo json_encode($result);
            break;
            
        // ==================== ALTERAÇÃO DE SENHA ====================
        case 'change_password':
            $newPassword = $_POST['new_password'] ?? null;
            $confirmPassword = $_POST['confirm_password'] ?? null;
            $otpCode = $_POST['otp_code'] ?? null;
            
            // Validações
            if (!$newPassword || strlen($newPassword) < 6) {
                echo json_encode([
                    'success' => false,
                    'error' => 'A senha deve ter pelo menos 6 caracteres'
                ]);
                exit;
            }
            
            if ($newPassword !== $confirmPassword) {
                echo json_encode([
                    'success' => false,
                    'error' => 'As senhas não coincidem'
                ]);
                exit;
            }
            
            if (!$otpCode) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Código OTP é obrigatório para alteração de senha'
                ]);
                exit;
            }
            
            $result = $security->changePasswordWithOTP($userId, $newPassword, $otpCode);
            echo json_encode($result);
            break;
            
        // ==================== HISTÓRICO DE SEGURANÇA ====================
        case 'get_security_history':
            $limit = (int)($_GET['limit'] ?? 50);
            $result = $security->getSecurityHistory($userId, $limit);
            echo json_encode($result);
            break;
            
        // ==================== STATUS DE VERIFICAÇÃO ====================
        case 'get_verification_status':
            $isVerified = $security->isEmailVerified($userId);
            $user = $db->query("SELECT email, email_verified, email_verified_at FROM users WHERE id = ?", [$userId]);
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'email' => $user[0]['email'] ?? null,
                    'email_verified' => $isVerified,
                    'email_verified_at' => $user[0]['email_verified_at'] ?? null
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
    error_log("Erro na API de segurança: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Erro no servidor: ' . $e->getMessage()
    ]);
}
?>

