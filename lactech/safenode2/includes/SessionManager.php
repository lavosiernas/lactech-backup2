<?php
/**
 * SafeNode - Session Manager
 * Gerencia sessões ativas dos usuários
 */

class SessionManager
{
    private $db;
    
    public function __construct($database)
    {
        $this->db = $database;
    }
    
    /**
     * Criar nova sessão
     */
    public function createSession($userId, $isCurrent = false)
    {
        try {
            $sessionToken = bin2hex(random_bytes(32));
            $ipAddress = $this->getClientIP();
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
            $deviceInfo = $this->parseUserAgent($userAgent);
            $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));
            
            $stmt = $this->db->prepare("
                INSERT INTO safenode_user_sessions 
                (user_id, session_token, ip_address, user_agent, device_type, browser, os, is_current, expires_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $userId,
                $sessionToken,
                $ipAddress,
                $userAgent,
                $deviceInfo['device_type'],
                $deviceInfo['browser'],
                $deviceInfo['os'],
                $isCurrent ? 1 : 0,
                $expiresAt
            ]);
            
            // Salvar token na sessão PHP
            $_SESSION['safenode_session_token'] = $sessionToken;
            
            return $sessionToken;
        } catch (PDOException $e) {
            error_log("SessionManager - Create Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Atualizar última atividade da sessão
     */
    public function updateActivity($sessionToken)
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE safenode_user_sessions 
                SET last_activity = CURRENT_TIMESTAMP 
                WHERE session_token = ?
            ");
            $stmt->execute([$sessionToken]);
            return true;
        } catch (PDOException $e) {
            error_log("SessionManager - Update Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Listar sessões ativas do usuário
     */
    public function getUserSessions($userId)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT id, session_token, ip_address, device_type, browser, os, 
                       is_current, last_activity, created_at, expires_at
                FROM safenode_user_sessions 
                WHERE user_id = ? AND expires_at > NOW()
                ORDER BY is_current DESC, last_activity DESC
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("SessionManager - Get Sessions Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Encerrar sessão específica
     */
    public function terminateSession($userId, $sessionToken)
    {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM safenode_user_sessions 
                WHERE user_id = ? AND session_token = ?
            ");
            $stmt->execute([$userId, $sessionToken]);
            return true;
        } catch (PDOException $e) {
            error_log("SessionManager - Terminate Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Encerrar todas as sessões do usuário (exceto a atual)
     */
    public function terminateAllSessions($userId, $exceptToken = null)
    {
        try {
            if ($exceptToken) {
                $stmt = $this->db->prepare("
                    DELETE FROM safenode_user_sessions 
                    WHERE user_id = ? AND session_token != ?
                ");
                $stmt->execute([$userId, $exceptToken]);
            } else {
                $stmt = $this->db->prepare("
                    DELETE FROM safenode_user_sessions 
                    WHERE user_id = ?
                ");
                $stmt->execute([$userId]);
            }
            return true;
        } catch (PDOException $e) {
            error_log("SessionManager - Terminate All Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Limpar sessões expiradas
     */
    public function cleanExpiredSessions()
    {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM safenode_user_sessions 
                WHERE expires_at < NOW()
            ");
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            error_log("SessionManager - Clean Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obter IP real do cliente
     */
    private function getClientIP()
    {
        $ipHeaders = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR'
        ];
        
        foreach ($ipHeaders as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                // Se tiver múltiplos IPs, pegar o primeiro
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                return $ip;
            }
        }
        
        return '0.0.0.0';
    }
    
    /**
     * Parse do User Agent para extrair informações
     */
    private function parseUserAgent($userAgent)
    {
        $info = [
            'device_type' => 'desktop',
            'browser' => 'Unknown',
            'os' => 'Unknown'
        ];
        
        // Detectar tipo de dispositivo
        if (preg_match('/(tablet|ipad|playbook)|(android(?!.*(mobi|opera mini)))/i', $userAgent)) {
            $info['device_type'] = 'tablet';
        } elseif (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|android|iemobile)/i', $userAgent)) {
            $info['device_type'] = 'mobile';
        }
        
        // Detectar navegador
        if (preg_match('/MSIE/i', $userAgent) || preg_match('/Trident/i', $userAgent)) {
            $info['browser'] = 'Internet Explorer';
        } elseif (preg_match('/Edge/i', $userAgent)) {
            $info['browser'] = 'Edge';
        } elseif (preg_match('/Firefox/i', $userAgent)) {
            $info['browser'] = 'Firefox';
        } elseif (preg_match('/Chrome/i', $userAgent)) {
            $info['browser'] = 'Chrome';
        } elseif (preg_match('/Safari/i', $userAgent)) {
            $info['browser'] = 'Safari';
        } elseif (preg_match('/Opera/i', $userAgent) || preg_match('/OPR/i', $userAgent)) {
            $info['browser'] = 'Opera';
        }
        
        // Detectar sistema operacional
        if (preg_match('/linux/i', $userAgent)) {
            $info['os'] = 'Linux';
        } elseif (preg_match('/macintosh|mac os x/i', $userAgent)) {
            $info['os'] = 'Mac OS';
        } elseif (preg_match('/windows|win32/i', $userAgent)) {
            $info['os'] = 'Windows';
        } elseif (preg_match('/android/i', $userAgent)) {
            $info['os'] = 'Android';
        } elseif (preg_match('/iphone|ipad|ipod/i', $userAgent)) {
            $info['os'] = 'iOS';
        }
        
        return $info;
    }
}


