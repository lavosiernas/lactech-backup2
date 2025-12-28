<?php
/**
 * SafeNode - Alert System
 * Sistema de alertas inteligentes para eventos críticos
 * 
 * Suporta:
 * - Email
 * - Webhook
 * - Integração futura: SMS, Telegram
 */

class AlertSystem {
    private $db;
    private $cache;
    
    // Thresholds padrão
    const THRESHOLD_CRITICAL = 90;  // threat_score >= 90
    const THRESHOLD_HIGH = 70;       // threat_score >= 70
    const THRESHOLD_DDOS = 100;      // requisições/minuto
    
    public function __construct($database) {
        $this->db = $database;
        require_once __DIR__ . '/CacheManager.php';
        $this->cache = CacheManager::getInstance();
        $this->ensureTableExists();
    }
    
    /**
     * Envia alerta baseado em evento
     * 
     * @param string $eventType Tipo de evento
     * @param array $data Dados do evento
     * @param int $severity Severidade (1-5, 5 = crítico)
     * @return bool Sucesso
     */
    public function sendAlert($eventType, $data, $severity = 3) {
        if (!$this->db) return false;
        
        // Verificar se alerta deve ser enviado (rate limiting)
        if (!$this->shouldSendAlert($eventType, $severity)) {
            return false;
        }
        
        // Obter configurações de alerta do usuário/site
        $alertConfigs = $this->getAlertConfigs($data['site_id'] ?? null, $data['user_id'] ?? null);
        
        $sent = false;
        
        foreach ($alertConfigs as $config) {
            if ($severity < $config['min_severity']) {
                continue; // Severidade insuficiente
            }
            
            // Verificar se tipo de evento está habilitado
            if (!in_array($eventType, explode(',', $config['event_types']))) {
                continue;
            }
            
            switch ($config['channel']) {
                case 'email':
                    $sent = $this->sendEmailAlert($config, $eventType, $data, $severity) || $sent;
                    break;
                    
                case 'webhook':
                    $sent = $this->sendWebhookAlert($config, $eventType, $data, $severity) || $sent;
                    break;
                    
                case 'sms':
                    // Implementar no futuro
                    break;
                    
                case 'telegram':
                    // Implementar no futuro
                    break;
            }
        }
        
        // Registrar alerta no banco
        if ($sent) {
            $this->logAlert($eventType, $data, $severity, $alertConfigs);
        }
        
        return $sent;
    }
    
    /**
     * Verifica se alerta deve ser enviado (rate limiting)
     */
    private function shouldSendAlert($eventType, $severity) {
        $cacheKey = "alert_rate_limit:$eventType:$severity";
        $count = $this->cache->increment($cacheKey, 1, 300); // TTL: 5 minutos
        
        // Limites por severidade
        $limits = [
            5 => 1,   // Crítico: máximo 1 por 5 minutos
            4 => 3,   // Alto: máximo 3 por 5 minutos
            3 => 10,  // Médio: máximo 10 por 5 minutos
            2 => 20,  // Baixo: máximo 20 por 5 minutos
            1 => 50   // Info: máximo 50 por 5 minutos
        ];
        
        $limit = $limits[$severity] ?? 10;
        
        return $count <= $limit;
    }
    
    /**
     * Obtém configurações de alerta
     */
    private function getAlertConfigs($siteId = null, $userId = null) {
        $cacheKey = "alert_configs:site:$siteId:user:$userId";
        $cached = $this->cache->get($cacheKey);
        
        if ($cached !== null) {
            return $cached;
        }
        
        try {
            $sql = "SELECT * FROM safenode_alert_configs WHERE is_active = 1";
            $params = [];
            
            if ($siteId) {
                $sql .= " AND (site_id = ? OR site_id IS NULL)";
                $params[] = $siteId;
            }
            
            if ($userId) {
                $sql .= " AND (user_id = ? OR user_id IS NULL)";
                $params[] = $userId;
            }
            
            $sql .= " ORDER BY priority DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $configs = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            
            // Cache por 30 minutos
            $this->cache->set($cacheKey, $configs, 1800);
            
            return $configs;
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Envia alerta por email
     */
    private function sendEmailAlert($config, $eventType, $data, $severity) {
        $to = $config['email_address'] ?? '';
        if (empty($to)) return false;
        
        $subject = $this->getEmailSubject($eventType, $severity);
        $body = $this->getEmailBody($eventType, $data, $severity);
        
        $headers = [
            'From: SafeNode <noreply@safenode.cloud>',
            'Reply-To: noreply@safenode.cloud',
            'X-Mailer: SafeNode Alert System',
            'Content-Type: text/html; charset=UTF-8'
        ];
        
        return mail($to, $subject, $body, implode("\r\n", $headers));
    }
    
    /**
     * Envia alerta via webhook
     */
    private function sendWebhookAlert($config, $eventType, $data, $severity) {
        $webhookUrl = $config['webhook_url'] ?? '';
        if (empty($webhookUrl)) return false;
        
        $payload = [
            'event_type' => $eventType,
            'severity' => $severity,
            'timestamp' => date('c'),
            'data' => $data
        ];
        
        $ch = curl_init($webhookUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'User-Agent: SafeNode-AlertSystem/1.0'
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_CONNECTTIMEOUT => 3
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $httpCode >= 200 && $httpCode < 300;
    }
    
    /**
     * Gera assunto do email
     */
    private function getEmailSubject($eventType, $severity) {
        $severityLabels = [
            5 => '[CRÍTICO]',
            4 => '[ALTO]',
            3 => '[MÉDIO]',
            2 => '[BAIXO]',
            1 => '[INFO]'
        ];
        
        $labels = [
            'threat_detected' => 'Ameaça Detectada',
            'ddos_detected' => 'Ataque DDoS Detectado',
            'brute_force' => 'Tentativa de Brute Force',
            'ip_blocked' => 'IP Bloqueado',
            'rate_limit_exceeded' => 'Rate Limit Excedido',
            'honeypot_triggered' => 'Honeypot Ativado',
            'suspicious_behavior' => 'Comportamento Suspeito'
        ];
        
        $label = $labels[$eventType] ?? 'Evento de Segurança';
        $severityLabel = $severityLabels[$severity] ?? '[MÉDIO]';
        
        return "$severityLabel SafeNode: $label";
    }
    
    /**
     * Gera corpo do email
     */
    private function getEmailBody($eventType, $data, $severity) {
        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #dc2626; color: white; padding: 20px; text-align: center; }
                .content { background: #f9fafb; padding: 20px; border: 1px solid #e5e7eb; }
                .footer { text-align: center; color: #6b7280; font-size: 12px; margin-top: 20px; }
                .data-table { width: 100%; border-collapse: collapse; margin: 15px 0; }
                .data-table td { padding: 8px; border-bottom: 1px solid #e5e7eb; }
                .data-table td:first-child { font-weight: bold; width: 150px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🚨 Alerta SafeNode</h1>
                </div>
                <div class='content'>
                    <h2>{$this->getEmailSubject($eventType, $severity)}</h2>
                    <p>Um evento de segurança foi detectado no SafeNode:</p>
                    
                    <table class='data-table'>
        ";
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value);
            }
            $html .= "<tr><td>" . htmlspecialchars(ucfirst(str_replace('_', ' ', $key))) . "</td><td>" . htmlspecialchars($value) . "</td></tr>";
        }
        
        $html .= "
                    </table>
                    
                    <p><strong>Timestamp:</strong> " . date('d/m/Y H:i:s') . "</p>
                    <p><strong>Severidade:</strong> $severity/5</p>
                </div>
                <div class='footer'>
                    <p>Este é um alerta automático do SafeNode Security System</p>
                    <p>Para desabilitar alertas, acesse o painel de configurações</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        return $html;
    }
    
    /**
     * Registra alerta no banco
     */
    private function logAlert($eventType, $data, $severity, $configs) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO safenode_alerts 
                (event_type, severity, data, channels, created_at) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            
            $channels = implode(',', array_column($configs, 'channel'));
            
            $stmt->execute([
                $eventType,
                $severity,
                json_encode($data),
                $channels
            ]);
        } catch (PDOException $e) {
            error_log("SafeNode Alert Log Error: " . $e->getMessage());
        }
    }
    
    /**
     * Garante que tabelas existem
     */
    private function ensureTableExists() {
        if (!$this->db) return;
        
        try {
            $this->db->query("SELECT 1 FROM safenode_alert_configs LIMIT 1");
        } catch (PDOException $e) {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS safenode_alert_configs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT DEFAULT NULL,
                    site_id INT DEFAULT NULL,
                    channel ENUM('email', 'webhook', 'sms', 'telegram') NOT NULL,
                    email_address VARCHAR(255) DEFAULT NULL,
                    webhook_url VARCHAR(500) DEFAULT NULL,
                    event_types VARCHAR(500) NOT NULL COMMENT 'Comma-separated list',
                    min_severity INT DEFAULT 3,
                    priority INT DEFAULT 0,
                    is_active TINYINT(1) DEFAULT 1,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_user (user_id),
                    INDEX idx_site (site_id),
                    INDEX idx_active (is_active)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }
        
        try {
            $this->db->query("SELECT 1 FROM safenode_alerts LIMIT 1");
        } catch (PDOException $e) {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS safenode_alerts (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    event_type VARCHAR(50) NOT NULL,
                    severity INT NOT NULL,
                    data TEXT,
                    channels VARCHAR(100),
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_event (event_type),
                    INDEX idx_severity (severity),
                    INDEX idx_created (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }
    }
}








