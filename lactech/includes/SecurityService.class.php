<?php
/**
 * Security Service - LACTECH
 * Serviço de segurança para OTP, verificação de e-mail e auditoria
 */

require_once __DIR__ . '/Database.class.php';
require_once __DIR__ . '/EmailService.class.php';

class SecurityService {
    private $db;
    private $emailService;
    private static $instance = null;
    
    // Configurações OTP
    const OTP_LENGTH = 6;
    const OTP_EXPIRY_MINUTES = 5;
    const MAX_OTP_ATTEMPTS = 5;
    
    private function __construct() {
        $this->db = Database::getInstance();
        $this->emailService = EmailService::getInstance();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Gerar código OTP
     * Se não fornecer email, busca automaticamente da conta Google vinculada ou do usuário
     */
    public function generateOTP($userId, $action, $email = null) {
        try {
            $pdo = $this->db->getConnection();
            
            // Se não forneceu email, buscar da conta Google vinculada primeiro
            if (!$email) {
                $stmt = $pdo->prepare("
                    SELECT email FROM google_accounts 
                    WHERE user_id = :user_id 
                    AND (unlinked_at IS NULL OR unlinked_at = '')
                    ORDER BY linked_at DESC
                    LIMIT 1
                ");
                $stmt->execute([':user_id' => $userId]);
                $googleAccount = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($googleAccount && !empty($googleAccount['email'])) {
                    // Usar email da conta Google vinculada (já verificado pelo Google)
                    $email = $googleAccount['email'];
                    error_log("🔍 OTP - Usando email da conta Google vinculada: {$email}");
                } else {
                    // Fallback: buscar email do usuário no sistema
                    $user = $this->db->query("SELECT email FROM users WHERE id = ?", [$userId]);
                    $email = $user[0]['email'] ?? null;
                    
                    if (!$email) {
                        return [
                            'success' => false,
                            'error' => 'E-mail não encontrado. Vincule uma conta Google ou cadastre um e-mail.',
                            'requires_google_linked' => true
                        ];
                    }
                    error_log("🔍 OTP - Usando email do sistema: {$email}");
                }
            }
            
            // Validar email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return [
                    'success' => false,
                    'error' => 'E-mail inválido'
                ];
            }
            
            // Gerar código de 6 dígitos
            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            
            // Expira em 5 minutos
            $expiresAt = date('Y-m-d H:i:s', strtotime('+' . self::OTP_EXPIRY_MINUTES . ' minutes'));
            
            // Obter IP e User Agent
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            
            // Salvar OTP no banco
            $stmt = $pdo->prepare("
                INSERT INTO otp_codes (user_id, code, action, expires_at, ip_address, user_agent)
                VALUES (:user_id, :code, :action, :expires_at, :ip_address, :user_agent)
            ");
            
            $stmt->execute([
                ':user_id' => $userId,
                ':code' => $code,
                ':action' => $action,
                ':expires_at' => $expiresAt,
                ':ip_address' => $ipAddress,
                ':user_agent' => $userAgent
            ]);
            
            // Enviar OTP por e-mail
            $user = $this->db->query("SELECT name FROM users WHERE id = ?", [$userId]);
            $userName = $user[0]['name'] ?? '';
            
            $emailResult = $this->emailService->sendOTPEmail($email, $code, $action, $userName);
            
            if (!$emailResult['success']) {
                error_log("⚠️ Erro ao enviar OTP por email: " . ($emailResult['error'] ?? 'Erro desconhecido'));
                // Não falhar completamente, apenas logar o erro
            }
            
            // Log de auditoria
            $this->logSecurityAction($userId, 'otp_generated', "OTP gerado para ação: {$action} - Email: {$email}", true, [
                'action' => $action,
                'email' => $email,
                'expires_at' => $expiresAt
            ]);
            
            return [
                'success' => true,
                'code' => $code, // Em produção, não retornar o código, apenas enviar por e-mail
                'expires_at' => $expiresAt,
                'email_sent_to' => $email,
                'message' => 'Código OTP gerado e enviado por e-mail para ' . $email
            ];
        } catch (Exception $e) {
            error_log("Erro ao gerar OTP: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Erro ao gerar código de verificação: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Validar código OTP
     */
    public function validateOTP($userId, $code, $action) {
        try {
            $pdo = $this->db->getConnection();
            
            // Limpar código (remover espaços e garantir string)
            $code = trim((string)$code);
            $code = preg_replace('/\s+/', '', $code); // Remover espaços
            
            if (empty($code) || strlen($code) != self::OTP_LENGTH) {
                return [
                    'success' => false,
                    'error' => 'Código inválido'
                ];
            }
            
            // Buscar OTP válido (verificar se ainda não expirou com margem de segurança)
            $stmt = $pdo->prepare("
                SELECT id, code, expires_at, is_used, created_at
                FROM otp_codes
                WHERE user_id = :user_id 
                AND code = :code
                AND action = :action
                AND is_used = 0
                AND expires_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)
                ORDER BY created_at DESC
                LIMIT 1
            ");
            
            $stmt->execute([
                ':user_id' => $userId,
                ':code' => $code,
                ':action' => $action
            ]);
            
            $otp = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$otp) {
                // Verificar se existe mas expirou ou foi usado
                $checkStmt = $pdo->prepare("
                    SELECT id, expires_at, is_used, created_at
                    FROM otp_codes
                    WHERE user_id = :user_id 
                    AND code = :code
                    AND action = :action
                    ORDER BY created_at DESC
                    LIMIT 1
                ");
                
                $checkStmt->execute([
                    ':user_id' => $userId,
                    ':code' => $code,
                    ':action' => $action
                ]);
                
                $existingOtp = $checkStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($existingOtp) {
                    if ($existingOtp['is_used'] == 1) {
                        return [
                            'success' => false,
                            'error' => 'Código já foi utilizado'
                        ];
                    }
                    
                    // Verificar expiração com mais detalhes
                    $expiresAt = new DateTime($existingOtp['expires_at']);
                    $now = new DateTime();
                    
                    if ($now > $expiresAt) {
                        return [
                            'success' => false,
                            'error' => 'Código expirado'
                        ];
                    }
                }
                
                // Log de tentativa inválida
                $this->logSecurityAction($userId, 'otp_validation_failed', "Tentativa de validação OTP inválida para ação: {$action}", false, [
                    'action' => $action,
                    'code_provided' => substr($code, 0, 2) . '****' // Não logar código completo
                ]);
                
                return [
                    'success' => false,
                    'error' => 'Código inválido ou expirado'
                ];
            }
            
            // Verificar novamente a expiração com PHP para garantir
            $expiresAt = new DateTime($otp['expires_at']);
            $now = new DateTime();
            
            if ($now > $expiresAt) {
                return [
                    'success' => false,
                    'error' => 'Código expirado'
                ];
            }
            
            // Marcar OTP como usado
            $updateStmt = $pdo->prepare("
                UPDATE otp_codes 
                SET is_used = 1, used_at = NOW() 
                WHERE id = :id
            ");
            $updateStmt->execute([':id' => $otp['id']]);
            
            // Log de auditoria
            $this->logSecurityAction($userId, 'otp_validated', "OTP validado com sucesso para ação: {$action}", true, [
                'action' => $action
            ]);
            
            return [
                'success' => true,
                'message' => 'Código validado com sucesso'
            ];
        } catch (Exception $e) {
            error_log("Erro ao validar OTP: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return [
                'success' => false,
                'error' => 'Erro ao validar código'
            ];
        }
    }
    
    /**
     * Verificar se o e-mail do usuário está verificado
     */
    public function isEmailVerified($userId) {
        try {
            $result = $this->db->query("
                SELECT email_verified 
                FROM users 
                WHERE id = ?
            ", [$userId]);
            
            return !empty($result) && $result[0]['email_verified'] == 1;
        } catch (Exception $e) {
            error_log("Erro ao verificar e-mail: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Solicitar verificação de e-mail
     */
    public function requestEmailVerification($userId, $email) {
        try {
            $pdo = $this->db->getConnection();
            
            // Gerar token de verificação
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
            
            // Salvar solicitação de verificação
            $stmt = $pdo->prepare("
                INSERT INTO email_verifications (user_id, email, verification_token, expires_at)
                VALUES (:user_id, :email, :token, :expires_at)
            ");
            
            $stmt->execute([
                ':user_id' => $userId,
                ':email' => $email,
                ':token' => $token,
                ':expires_at' => $expiresAt
            ]);
            
            // Enviar e-mail de verificação
            $user = $this->db->query("SELECT name FROM users WHERE id = ?", [$userId]);
            $userName = $user[0]['name'] ?? '';
            $this->emailService->sendVerificationEmail($email, $token, $userName);
            
            // Log de auditoria
            $this->logSecurityAction($userId, 'email_verification_requested', "Solicitação de verificação de e-mail: {$email}", true);
            
            return [
                'success' => true,
                'message' => 'E-mail de verificação enviado'
            ];
        } catch (Exception $e) {
            error_log("Erro ao solicitar verificação: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Erro ao enviar e-mail de verificação'
            ];
        }
    }
    
    /**
     * Verificar e-mail com token
     */
    public function verifyEmail($token) {
        try {
            $pdo = $this->db->getConnection();
            
            // Buscar verificação pendente
            $stmt = $pdo->prepare("
                SELECT ev.*, u.id as user_id
                FROM email_verifications ev
                JOIN users u ON ev.user_id = u.id
                WHERE ev.verification_token = :token
                AND ev.is_verified = 0
                AND ev.expires_at > NOW()
                ORDER BY ev.created_at DESC
                LIMIT 1
            ");
            
            $stmt->execute([':token' => $token]);
            $verification = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$verification) {
                return [
                    'success' => false,
                    'error' => 'Token inválido ou expirado'
                ];
            }
            
            // Marcar como verificado
            $updateStmt = $pdo->prepare("
                UPDATE email_verifications 
                SET is_verified = 1, verified_at = NOW()
                WHERE id = :id
            ");
            $updateStmt->execute([':id' => $verification['id']]);
            
            // Atualizar usuário
            $userStmt = $pdo->prepare("
                UPDATE users 
                SET email_verified = 1, email_verified_at = NOW(), email = :email
                WHERE id = :user_id
            ");
            $userStmt->execute([
                ':user_id' => $verification['user_id'],
                ':email' => $verification['email']
            ]);
            
            // Log de auditoria
            $this->logSecurityAction($verification['user_id'], 'email_verified', "E-mail verificado: {$verification['email']}", true);
            
            // Enviar notificação de segurança
            $this->emailService->sendSecurityNotification(
                $verification['email'],
                'email_verified',
                'Seu endereço de e-mail foi verificado com sucesso'
            );
            
            return [
                'success' => true,
                'message' => 'E-mail verificado com sucesso'
            ];
        } catch (Exception $e) {
            error_log("Erro ao verificar e-mail: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Erro ao verificar e-mail'
            ];
        }
    }
    
    /**
     * Alterar senha com OTP
     */
    public function changePasswordWithOTP($userId, $newPassword, $otpCode) {
        try {
            // Verificar se tem conta Google vinculada
            $pdo = $this->db->getConnection();
            $stmt = $pdo->prepare("
                SELECT email FROM google_accounts 
                WHERE user_id = :user_id 
                AND (unlinked_at IS NULL OR unlinked_at = '')
                LIMIT 1
            ");
            $stmt->execute([':user_id' => $userId]);
            $googleAccount = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Se não tem Google vinculado, verificar se e-mail está verificado
            if (!$googleAccount && !$this->isEmailVerified($userId)) {
                return [
                    'success' => false,
                    'error' => 'E-mail não verificado. Verifique seu e-mail antes de alterar a senha.'
                ];
            }
            
            // Validar OTP
            $otpValidation = $this->validateOTP($userId, $otpCode, 'password_change');
            if (!$otpValidation['success']) {
                return $otpValidation;
            }
            
            // Hash da nova senha
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            
            // Atualizar senha
            $pdo = $this->db->getConnection();
            $stmt = $pdo->prepare("
                UPDATE users 
                SET password = :password, 
                    password_changed_at = NOW(),
                    password_change_required = 0
                WHERE id = :user_id
            ");
            
            $stmt->execute([
                ':password' => $passwordHash,
                ':user_id' => $userId
            ]);
            
            // Encerrar todas as sessões ativas (exceto a atual)
            $this->invalidateOtherSessions($userId);
            
            // Buscar e-mail do usuário para notificação
            $user = $this->db->query("SELECT email, name FROM users WHERE id = ?", [$userId]);
            $userEmail = $user[0]['email'] ?? '';
            $userName = $user[0]['name'] ?? '';
            
            // Enviar notificação de segurança
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
            $this->emailService->sendSecurityNotification(
                $userEmail,
                'password_changed',
                "Sua senha foi alterada com sucesso. Se você não realizou esta alteração, entre em contato imediatamente.",
                $ipAddress
            );
            
            // Log de auditoria
            $this->logSecurityAction($userId, 'password_changed', 'Senha alterada com sucesso', true, [
                'ip_address' => $ipAddress
            ]);
            
            return [
                'success' => true,
                'message' => 'Senha alterada com sucesso. Todas as sessões foram encerradas por segurança.'
            ];
        } catch (Exception $e) {
            error_log("Erro ao alterar senha: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Erro ao alterar senha'
            ];
        }
    }
    
    /**
     * Invalidar outras sessões do usuário
     */
    private function invalidateOtherSessions($userId) {
        try {
            $pdo = $this->db->getConnection();
            
            // Atualizar último login para forçar reautenticação
            $stmt = $pdo->prepare("
                UPDATE user_sessions 
                SET is_active = 0, expires_at = NOW()
                WHERE user_id = :user_id 
                AND id != :current_session_id
            ");
            
            // Se não houver sessão atual, invalidar todas
            $currentSessionId = $_SESSION['session_id'] ?? 0;
            $stmt->execute([
                ':user_id' => $userId,
                ':current_session_id' => $currentSessionId
            ]);
            
        } catch (Exception $e) {
            error_log("Erro ao invalidar sessões: " . $e->getMessage());
        }
    }
    
    /**
     * Log de ações de segurança
     */
    public function logSecurityAction($userId, $action, $description, $success = true, $metadata = null) {
        try {
            $pdo = $this->db->getConnection();
            
            $stmt = $pdo->prepare("
                INSERT INTO security_audit_log (
                    user_id, action, description, ip_address, user_agent, 
                    success, error_message, metadata
                ) VALUES (
                    :user_id, :action, :description, :ip_address, :user_agent,
                    :success, :error_message, :metadata
                )
            ");
            
            $stmt->execute([
                ':user_id' => $userId,
                ':action' => $action,
                ':description' => $description,
                ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                ':success' => $success ? 1 : 0,
                ':error_message' => $success ? null : $description,
                ':metadata' => $metadata ? json_encode($metadata) : null
            ]);
            
        } catch (Exception $e) {
            error_log("Erro ao registrar log de segurança: " . $e->getMessage());
        }
    }
    
    /**
     * Obter histórico de segurança do usuário
     */
    public function getSecurityHistory($userId, $limit = 50) {
        try {
            $result = $this->db->query("
                SELECT 
                    action, description, ip_address, success, 
                    created_at, metadata
                FROM security_audit_log
                WHERE user_id = ?
                ORDER BY created_at DESC
                LIMIT ?
            ", [$userId, $limit]);
            
            return [
                'success' => true,
                'data' => $result
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Erro ao buscar histórico'
            ];
        }
    }
}

