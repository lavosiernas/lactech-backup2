<?php
/**
 * SafeNode - Alert Manager
 * Sistema de gerenciamento de alertas e notificações
 */

class AlertManager {
    private $db;
    
    // Tipos de alerta
    const TYPE_CRITICAL_THREAT = 'critical_threat';
    const TYPE_SUSPICIOUS_IP = 'suspicious_ip';
    const TYPE_PERFORMANCE_ISSUE = 'performance_issue';
    const TYPE_SECURITY_RECOMMENDATION = 'security_recommendation';
    
    // Severidades
    const SEVERITY_CRITICAL = 'critical';
    const SEVERITY_HIGH = 'high';
    const SEVERITY_MEDIUM = 'medium';
    const SEVERITY_LOW = 'low';
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Cria um novo alerta
     */
    public function createAlert($siteId, $alertType, $severity, $title, $message, $data = null) {
        if (!$this->db || !$siteId) {
            return false;
        }
        
        // Verificar se deve criar alerta baseado nas preferências
        $shouldAlert = $this->shouldAlert($siteId, $alertType, $data);
        if (!$shouldAlert) {
            return false;
        }
        
        try {
            $stmt = $this->db->prepare("
                INSERT INTO safenode_alerts 
                (site_id, alert_type, severity, title, message, data, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $jsonData = $data ? json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
            
            $stmt->execute([
                $siteId,
                $alertType,
                $severity,
                $title,
                $message,
                $jsonData
            ]);
            
            $alertId = $this->db->lastInsertId();
            
            // Enviar email se configurado
            $preferences = $this->getPreferences($siteId, $alertType);
            if ($preferences && $preferences['email_enabled']) {
                $this->sendEmailAlert($alertId, $siteId, $alertType, $severity, $title, $message);
            }
            
            return $alertId;
        } catch (PDOException $e) {
            error_log("SafeNode AlertManager Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica se deve criar alerta baseado em preferências e threshold
     */
    private function shouldAlert($siteId, $alertType, $data) {
        $preferences = $this->getPreferences($siteId, $alertType);
        
        if (!$preferences || !$preferences['dashboard_enabled']) {
            return false; // Alerta desabilitado
        }
        
        // Se não tem threshold, alerta sempre
        if (!$preferences['threshold']) {
            return true;
        }
        
        // Verificar threshold baseado no tipo de alerta
        if ($alertType === self::TYPE_SUSPICIOUS_IP && isset($data['attempt_count'])) {
            return $data['attempt_count'] >= $preferences['threshold'];
        }
        
        if ($alertType === self::TYPE_CRITICAL_THREAT) {
            return true; // Ameaças críticas sempre alertam
        }
        
        return true;
    }
    
    /**
     * Busca preferências de alerta
     */
    public function getPreferences($siteId, $alertType) {
        if (!$this->db || !$siteId) {
            return null;
        }
        
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM safenode_alert_preferences 
                WHERE site_id = ? AND alert_type = ?
            ");
            $stmt->execute([$siteId, $alertType]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("SafeNode AlertManager Preferences Error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Envia email de alerta
     */
    private function sendEmailAlert($alertId, $siteId, $alertType, $severity, $title, $message) {
        if (!$this->db) {
            return false;
        }
        
        try {
            // Buscar email do usuário do site
            $stmt = $this->db->prepare("
                SELECT u.email, s.domain 
                FROM safenode_sites s
                INNER JOIN safenode_users u ON s.user_id = u.id
                WHERE s.id = ?
            ");
            $stmt->execute([$siteId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result || !$result['email']) {
                return false;
            }
            
            $userEmail = $result['email'];
            $siteDomain = $result['domain'];
            
            // Preparar email
            $subject = "[SafeNode] {$title} - {$siteDomain}";
            $body = $this->prepareEmailBody($siteDomain, $severity, $title, $message);
            
            // Enviar email (usar função mail ou PHPMailer se disponível)
            $headers = "From: SafeNode <noreply@safenode.com>\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            
            $sent = mail($userEmail, $subject, $body, $headers);
            
            // Marcar como enviado
            if ($sent) {
                $stmt = $this->db->prepare("
                    UPDATE safenode_alerts 
                    SET email_sent = 1 
                    WHERE id = ?
                ");
                $stmt->execute([$alertId]);
            }
            
            return $sent;
        } catch (PDOException $e) {
            error_log("SafeNode AlertManager Email Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Prepara corpo do email
     */
    private function prepareEmailBody($siteDomain, $severity, $title, $message) {
        $severityColors = [
            'critical' => '#dc2626',
            'high' => '#ea580c',
            'medium' => '#f59e0b',
            'low' => '#3b82f6'
        ];
        $color = $severityColors[$severity] ?? '#6b7280';
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: {$color}; color: white; padding: 20px; border-radius: 8px 8px 0 0; }
                .content { background: #f9fafb; padding: 20px; border-radius: 0 0 8px 8px; }
                .alert-box { background: white; border-left: 4px solid {$color}; padding: 15px; margin: 20px 0; }
                .footer { text-align: center; color: #6b7280; font-size: 12px; margin-top: 20px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>SafeNode - Alerta de Segurança</h2>
                    <p style='margin: 0;'>Site: {$siteDomain}</p>
                </div>
                <div class='content'>
                    <div class='alert-box'>
                        <h3 style='margin-top: 0;'>{$title}</h3>
                        <p>{$message}</p>
                    </div>
                    <p><a href='https://safenode.com/dashboard' style='background: {$color}; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block;'>Ver no Dashboard</a></p>
                </div>
                <div class='footer'>
                    <p>SafeNode - Sistema de Proteção Web</p>
                    <p>Você recebeu este email porque está configurado para receber alertas deste tipo.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Marca alerta como lido
     */
    public function markAsRead($alertId, $userId) {
        if (!$this->db || !$alertId) {
            return false;
        }
        
        try {
            // Verificar que o alerta pertence ao usuário
            $stmt = $this->db->prepare("
                UPDATE safenode_alerts a
                INNER JOIN safenode_sites s ON a.site_id = s.id
                SET a.read = 1
                WHERE a.id = ? AND s.user_id = ?
            ");
            return $stmt->execute([$alertId, $userId]);
        } catch (PDOException $e) {
            error_log("SafeNode AlertManager Mark Read Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Busca alertas não lidos
     */
    public function getUnreadCount($siteId = null, $userId = null) {
        if (!$this->db) {
            return 0;
        }
        
        try {
            $sql = "
                SELECT COUNT(*) as count
                FROM safenode_alerts a
                INNER JOIN safenode_sites s ON a.site_id = s.id
                WHERE a.read = 0
            ";
            
            $params = [];
            
            if ($siteId) {
                $sql .= " AND a.site_id = ?";
                $params[] = $siteId;
            }
            
            if ($userId) {
                $sql .= " AND s.user_id = ?";
                $params[] = $userId;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return (int)($result['count'] ?? 0);
        } catch (PDOException $e) {
            error_log("SafeNode AlertManager Unread Count Error: " . $e->getMessage());
            return 0;
        }
    }
}

