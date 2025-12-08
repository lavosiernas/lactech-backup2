<?php
/**
 * SafeNode - SIEM Exporter
 * Exportação de logs para sistemas SIEM
 * 
 * Formatos suportados:
 * - Syslog
 * - CEF (Common Event Format)
 * - JSON (para ELK Stack)
 */

class SIEMExporter {
    private $db;
    private $structuredLogger;
    
    public function __construct($database) {
        $this->db = $database;
        require_once __DIR__ . '/StructuredLogger.php';
        $this->structuredLogger = new StructuredLogger($database);
    }
    
    /**
     * Exporta logs para Syslog
     * 
     * @param string $syslogServer Servidor Syslog (host:port)
     * @param int $days Número de dias para exportar
     * @return bool Sucesso
     */
    public function exportToSyslog($syslogServer, $days = 1) {
        if (!$this->db) return false;
        
        try {
            list($host, $port) = explode(':', $syslogServer . ':514');
            $port = (int)$port ?: 514;
            
            $socket = @fsockopen('udp://' . $host, $port, $errno, $errstr, 5);
            if (!$socket) {
                error_log("SafeNode SIEM: Erro ao conectar Syslog - $errstr");
                return false;
            }
            
            $stmt = $this->db->prepare("
                SELECT * FROM safenode_security_logs
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                ORDER BY created_at
            ");
            $stmt->execute([$days]);
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $sent = 0;
            foreach ($logs as $log) {
                $syslogMessage = $this->formatSyslog($log);
                @fwrite($socket, $syslogMessage);
                $sent++;
            }
            
            fclose($socket);
            
            return $sent > 0;
        } catch (Exception $e) {
            error_log("SafeNode SIEM Export Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Exporta logs para formato CEF
     */
    public function exportToCEF($outputFile, $days = 1) {
        return $this->structuredLogger->exportCEF(
            date('Y-m-d', strtotime("-$days days")),
            date('Y-m-d'),
            $outputFile
        );
    }
    
    /**
     * Exporta logs para formato Syslog
     */
    public function exportToSyslogFile($outputFile, $days = 1) {
        return $this->structuredLogger->exportSyslog(
            date('Y-m-d', strtotime("-$days days")),
            date('Y-m-d'),
            $outputFile
        );
    }
    
    /**
     * Exporta logs para JSON (ELK Stack)
     */
    public function exportToJSON($outputFile, $days = 1) {
        if (!$this->db) return false;
        
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM safenode_security_logs
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                ORDER BY created_at
            ");
            $stmt->execute([$days]);
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $json = [];
            foreach ($logs as $log) {
                $json[] = [
                    '@timestamp' => $log['created_at'],
                    'level' => $log['action_taken'] === 'blocked' ? 'error' : 'info',
                    'message' => "Security event: {$log['action_taken']}",
                    'source' => [
                        'ip_address' => $log['ip_address'],
                        'request_uri' => $log['request_uri'],
                        'threat_type' => $log['threat_type'],
                        'threat_score' => (int)($log['threat_score'] ?? 0)
                    ]
                ];
            }
            
            file_put_contents($outputFile, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Formata log para Syslog
     */
    private function formatSyslog($log) {
        $priority = $log['action_taken'] === 'blocked' ? 3 : 6; // error : info
        $timestamp = date('M d H:i:s', strtotime($log['created_at']));
        $hostname = $_SERVER['SERVER_NAME'] ?? 'safenode';
        $message = sprintf(
            "SafeNode: %s from %s - %s (threat_score: %d)",
            $log['action_taken'],
            $log['ip_address'],
            $log['request_uri'],
            $log['threat_score'] ?? 0
        );
        
        return "<$priority>$timestamp $hostname safenode: $message\n";
    }
    
    /**
     * Envia logs em tempo real para SIEM (webhook)
     */
    public function sendToWebhook($webhookUrl, $log) {
        $payload = [
            '@timestamp' => date('c'),
            'source' => 'safenode',
            'event' => [
                'action' => $log['action_taken'],
                'ip_address' => $log['ip_address'],
                'request_uri' => $log['request_uri'],
                'threat_type' => $log['threat_type'],
                'threat_score' => (int)($log['threat_score'] ?? 0)
            ]
        ];
        
        $ch = curl_init($webhookUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'User-Agent: SafeNode-SIEM/1.0'
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $httpCode >= 200 && $httpCode < 300;
    }
}



