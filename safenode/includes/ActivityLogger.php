<?php
/**
 * SafeNode - Activity Logger
 * Registra ações e atividades dos usuários
 */

class ActivityLogger
{
    private $db;
    
    public function __construct($database)
    {
        $this->db = $database;
    }
    
    /**
     * Registrar atividade
     */
    public function log($userId, $action, $description = '', $status = 'success', $metadata = null)
    {
        try {
            $ipAddress = $this->getClientIP();
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
            $deviceInfo = $this->parseUserAgent($userAgent);
            
            $stmt = $this->db->prepare("
                INSERT INTO safenode_activity_log 
                (user_id, action, description, ip_address, user_agent, device_type, browser, os, metadata, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $metadataJson = $metadata ? json_encode($metadata) : null;
            
            $stmt->execute([
                $userId,
                $action,
                $description,
                $ipAddress,
                $userAgent,
                $deviceInfo['device_type'],
                $deviceInfo['browser'],
                $deviceInfo['os'],
                $metadataJson,
                $status
            ]);
            
            return true;
        } catch (PDOException $e) {
            error_log("ActivityLogger - Log Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Registrar login bem-sucedido
     */
    public function logLogin($userId, $method = 'password')
    {
        return $this->log(
            $userId,
            'login',
            "Login realizado via {$method}",
            'success',
            ['method' => $method]
        );
    }
    
    /**
     * Registrar tentativa de login falhada
     */
    public function logFailedLogin($userId, $reason = '')
    {
        return $this->log(
            $userId,
            'login_failed',
            "Tentativa de login falhada" . ($reason ? ": {$reason}" : ''),
            'failed'
        );
    }
    
    /**
     * Registrar logout
     */
    public function logLogout($userId)
    {
        return $this->log(
            $userId,
            'logout',
            'Usuário saiu do sistema',
            'success'
        );
    }
    
    /**
     * Registrar alteração de senha
     */
    public function logPasswordChange($userId, $method = 'current_password')
    {
        return $this->log(
            $userId,
            'password_change',
            "Senha alterada via {$method}",
            'success',
            ['method' => $method]
        );
    }
    
    /**
     * Registrar atualização de perfil
     */
    public function logProfileUpdate($userId, $fields = [])
    {
        return $this->log(
            $userId,
            'profile_update',
            'Perfil atualizado',
            'success',
            ['fields' => $fields]
        );
    }
    
    /**
     * Registrar encerramento de sessão
     */
    public function logSessionTerminated($userId, $sessionId, $terminatedAll = false)
    {
        $description = $terminatedAll ? 'Todas as sessões foram encerradas' : 'Sessão específica encerrada';
        return $this->log(
            $userId,
            'session_terminated',
            $description,
            'success',
            ['session_id' => $sessionId, 'all' => $terminatedAll]
        );
    }
    
    /**
     * Obter histórico de atividades do usuário
     */
    public function getUserActivities($userId, $limit = 50, $offset = 0)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT id, action, description, ip_address, device_type, browser, os, 
                       status, created_at
                FROM safenode_activity_log 
                WHERE user_id = ?
                ORDER BY created_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$userId, $limit, $offset]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("ActivityLogger - Get Activities Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Contar total de atividades do usuário
     */
    public function countUserActivities($userId)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as total
                FROM safenode_activity_log 
                WHERE user_id = ?
            ");
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            return $result['total'] ?? 0;
        } catch (PDOException $e) {
            error_log("ActivityLogger - Count Error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Limpar atividades antigas (mais de 90 dias)
     */
    public function cleanOldActivities($days = 90)
    {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM safenode_activity_log 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            $stmt->execute([$days]);
            return true;
        } catch (PDOException $e) {
            error_log("ActivityLogger - Clean Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obter IP real do cliente
     */
    private function getClientIP()
    {
        $ipHeaders = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR'
        ];
        
        foreach ($ipHeaders as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                return $ip;
            }
        }
        
        return '0.0.0.0';
    }
    
    /**
     * Parse do User Agent
     */
    private function parseUserAgent($userAgent)
    {
        $info = [
            'device_type' => 'desktop',
            'browser' => 'Unknown',
            'os' => 'Unknown'
        ];
        
        if (preg_match('/(tablet|ipad|playbook)|(android(?!.*(mobi|opera mini)))/i', $userAgent)) {
            $info['device_type'] = 'tablet';
        } elseif (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|android|iemobile)/i', $userAgent)) {
            $info['device_type'] = 'mobile';
        }
        
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
    
    /**
     * Traduzir ação para português
     */
    public static function translateAction($action)
    {
        $translations = [
            'login' => 'Login',
            'login_failed' => 'Tentativa de Login',
            'logout' => 'Logout',
            'password_change' => 'Alteração de Senha',
            'profile_update' => 'Atualização de Perfil',
            'session_terminated' => 'Sessão Encerrada',
            'site_created' => 'Site Criado',
            'site_updated' => 'Site Atualizado',
            'site_deleted' => 'Site Removido',
            'settings_updated' => 'Configurações Alteradas'
        ];
        
        return $translations[$action] ?? ucfirst($action);
    }
    
    /**
     * Obter ícone da ação
     */
    public static function getActionIcon($action)
    {
        $icons = [
            'login' => 'log-in',
            'login_failed' => 'shield-alert',
            'logout' => 'log-out',
            'password_change' => 'key',
            'profile_update' => 'user-circle',
            'session_terminated' => 'x-circle',
            'site_created' => 'plus-circle',
            'site_updated' => 'edit',
            'site_deleted' => 'trash-2',
            'settings_updated' => 'settings'
        ];
        
        return $icons[$action] ?? 'activity';
    }
    
    /**
     * Obter cor da ação
     */
    public static function getActionColor($status)
    {
        $colors = [
            'success' => 'text-emerald-400',
            'failed' => 'text-red-400',
            'warning' => 'text-yellow-400'
        ];
        
        return $colors[$status] ?? 'text-zinc-400';
    }
}


