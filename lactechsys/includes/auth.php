<?php
require_once 'config.php';
require_once 'database.php';
require_once 'security.php';

class Auth {
    private $db;
    private $security;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->security = Security::getInstance();
        
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Set security headers
        $this->security->setSecurityHeaders();
        
        // Secure session
        if (!$this->security->secureSession()) {
            header('Location: ' . LOGIN_URL);
            exit;
        }
    }
    
    public function login($email, $password) {
        try {
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            
            // Rate limiting
            if (!$this->security->checkRateLimit($ip, 'login')) {
                $this->security->logSecurityEvent('rate_limit_exceeded', ['ip' => $ip, 'email' => $email]);
                return ['success' => false, 'message' => 'Muitas tentativas de login. Tente novamente em 15 minutos.'];
            }
            
            // Buscar usuário no banco
            $users = $this->db->select('users', ['email' => $email]);
            
            if (empty($users)) {
                $this->security->incrementRateLimit($ip, 'login');
                $this->security->logSecurityEvent('login_failed', ['reason' => 'user_not_found', 'email' => $email, 'ip' => $ip]);
                return ['success' => false, 'message' => 'Usuário não encontrado'];
            }
            
            $user = $users[0];
            
            // Verificar senha
            if (password_verify($password, $user['password'])) {
                // Verificar 2FA se habilitado
                if (!empty($user['2fa_secret'])) {
                    $_SESSION['pending_2fa'] = $user['id'];
                    return ['success' => true, 'requires_2fa' => true, 'user' => $user];
                }
                
                // Criar sessão
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['farm_id'] = $user['farm_id'];
                $_SESSION['login_time'] = time();
                
                // Atualizar último login
                $this->db->update('users', [
                    'last_login' => date('Y-m-d H:i:s'),
                    'last_ip' => $ip
                ], ['id' => $user['id']]);
                
                // Log successful login
                $this->security->logSecurityEvent('login_success', ['user_id' => $user['id'], 'ip' => $ip]);
                
                return ['success' => true, 'user' => $user];
            } else {
                $this->security->incrementRateLimit($ip, 'login');
                $this->security->logSecurityEvent('login_failed', ['reason' => 'wrong_password', 'email' => $email, 'ip' => $ip]);
                return ['success' => false, 'message' => 'Senha incorreta'];
            }
        } catch (Exception $e) {
            $this->security->logSecurityEvent('login_error', ['error' => $e->getMessage(), 'email' => $email]);
            return ['success' => false, 'message' => 'Erro no sistema: ' . $e->getMessage()];
        }
    }
    
    public function logout() {
        session_destroy();
        return ['success' => true];
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        try {
            $users = $this->db->select('users', ['id' => $_SESSION['user_id']]);
            return !empty($users) ? $users[0] : null;
        } catch (Exception $e) {
            return null;
        }
    }
    
    public function hasRole($role) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        return $_SESSION['user_role'] === $role;
    }
    
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: ' . LOGIN_URL);
            exit;
        }
    }
    
    public function require2FA() {
        if (isset($_SESSION['pending_2fa'])) {
            header('Location: 2fa-verify.php');
            exit;
        }
    }
    
    public function requireRole($role) {
        $this->requireLogin();
        
        if (!$this->hasRole($role)) {
            header('Location: acesso-bloqueado.php');
            exit;
        }
    }
    
    public function changePassword($userId, $currentPassword, $newPassword) {
        try {
            // Validar força da senha
            $passwordErrors = $this->security->validatePasswordStrength($newPassword);
            if (!empty($passwordErrors)) {
                return ['success' => false, 'message' => implode(', ', $passwordErrors)];
            }
            
            $users = $this->db->select('users', ['id' => $userId]);
            
            if (empty($users)) {
                return ['success' => false, 'message' => 'Usuário não encontrado'];
            }
            
            $user = $users[0];
            
            if (!password_verify($currentPassword, $user['password'])) {
                $this->security->logSecurityEvent('password_change_failed', ['reason' => 'wrong_current_password', 'user_id' => $userId]);
                return ['success' => false, 'message' => 'Senha atual incorreta'];
            }
            
            $hashedPassword = password_hash($newPassword, PASSWORD_ARGON2ID);
            
            $result = $this->db->update('users', [
                'password' => $hashedPassword,
                'updated_at' => date('Y-m-d H:i:s')
            ], ['id' => $userId]);
            
            $this->security->logSecurityEvent('password_changed', ['user_id' => $userId]);
            
            return ['success' => true, 'message' => 'Senha alterada com sucesso'];
        } catch (Exception $e) {
            $this->security->logSecurityEvent('password_change_error', ['error' => $e->getMessage(), 'user_id' => $userId]);
            return ['success' => false, 'message' => 'Erro no sistema: ' . $e->getMessage()];
        }
    }
    
    public function verify2FA($code) {
        if (!isset($_SESSION['pending_2fa'])) {
            return ['success' => false, 'message' => 'Sessão 2FA não encontrada'];
        }
        
        $userId = $_SESSION['pending_2fa'];
        $users = $this->db->select('users', ['id' => $userId]);
        
        if (empty($users)) {
            return ['success' => false, 'message' => 'Usuário não encontrado'];
        }
        
        $user = $users[0];
        
        if ($this->security->verify2FACode($user['2fa_secret'], $code)) {
            // 2FA válido, completar login
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['farm_id'] = $user['farm_id'];
            $_SESSION['login_time'] = time();
            
            unset($_SESSION['pending_2fa']);
            
            $this->security->logSecurityEvent('2fa_success', ['user_id' => $userId]);
            
            return ['success' => true, 'user' => $user];
        } else {
            $this->security->logSecurityEvent('2fa_failed', ['user_id' => $userId]);
            return ['success' => false, 'message' => 'Código 2FA inválido'];
        }
    }
}
?>
