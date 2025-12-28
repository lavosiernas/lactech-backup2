<?php
/**
 * SafeNode - Structured Logger
 * Sistema de logging estruturado em JSON
 * 
 * Suporta:
 * - Formato JSON padronizado
 * - Metadados ricos
 * - Integração com ELK Stack
 */

class StructuredLogger {
    private $db;
    private $logFile;
    private $enableFileLogging = true;
    
    public function __construct($database, $logFile = null) {
        $this->db = $database;
        $this->logFile = $logFile ?: __DIR__ . '/../../logs/safenode.log';
        
        // Criar diretório de logs se não existir
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
    }
    
    /**
     * Registra log estruturado
     * 
     * @param string $level Nível do log (info, warning, error, critical)
     * @param string $message Mensagem
     * @param array $context Contexto adicional
     * @return bool Sucesso
     */
    public function log($level, $message, $context = []) {
        $logEntry = [
            '@timestamp' => date('c'), // ISO 8601
            'level' => $level,
            'message' => $message,
            'service' => 'safenode',
            'version' => '1.0',
            'context' => $context
        ];
        
        // Adicionar metadados padrão
        $logEntry['metadata'] = [
            'request_id' => $this->getRequestId(),
            'session_id' => session_id() ?: null,
            'user_id' => $_SESSION['safenode_user_id'] ?? null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'request_uri' => $_SERVER['REQUEST_URI'] ?? null,
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? null,
            'server_name' => $_SERVER['SERVER_NAME'] ?? null
        ];
        
        // Serializar para JSON
        $json = json_encode($logEntry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        
        // Escrever em arquivo
        if ($this->enableFileLogging) {
            @file_put_contents($this->logFile, $json . "\n", FILE_APPEND | LOCK_EX);
        }
        
        // Também salvar no banco (opcional)
        if ($this->db) {
            $this->saveToDatabase($logEntry);
        }
        
        return true;
    }
    
    /**
     * Salva log no banco de dados
     */
    private function saveToDatabase($logEntry) {
        try {
            $this->ensureTableExists();
            
            $stmt = $this->db->prepare("
                INSERT INTO safenode_structured_logs 
                (level, message, log_data, created_at) 
                VALUES (?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $logEntry['level'],
                $logEntry['message'],
                json_encode($logEntry)
            ]);
        } catch (PDOException $e) {
            // Ignorar erros silenciosamente
        }
    }
    
    /**
     * Gera request ID único
     */
    private function getRequestId() {
        if (!isset($_SERVER['SAFENODE_REQUEST_ID'])) {
            $_SERVER['SAFENODE_REQUEST_ID'] = bin2hex(random_bytes(16));
        }
        return $_SERVER['SAFENODE_REQUEST_ID'];
    }
    
    /**
     * Garante que tabela existe
     */
    private function ensureTableExists() {
        try {
            $this->db->query("SELECT 1 FROM safenode_structured_logs LIMIT 1");
        } catch (PDOException $e) {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS safenode_structured_logs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    level VARCHAR(20) NOT NULL,
                    message VARCHAR(500),
                    log_data JSON,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_level (level),
                    INDEX idx_created (created_at),
                    INDEX idx_message (message(100))
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }
    }
    
    /**
     * Exporta logs para formato Syslog
     */
    public function exportSyslog($startDate, $endDate, $outputFile = null) {
        if (!$this->db) return false;
        
        try {
            $stmt = $this->db->prepare("
                SELECT log_data FROM safenode_structured_logs
                WHERE created_at >= ? AND created_at <= ?
                ORDER BY created_at
            ");
            $stmt->execute([$startDate, $endDate]);
            $logs = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $syslog = '';
            foreach ($logs as $logJson) {
                $log = json_decode($logJson, true);
                if ($log) {
                    $priority = $this->getSyslogPriority($log['level']);
                    $timestamp = date('M d H:i:s', strtotime($log['@timestamp']));
                    $hostname = $log['metadata']['server_name'] ?? 'safenode';
                    $message = $log['message'];
                    
                    $syslog .= "<$priority>$timestamp $hostname safenode: $message\n";
                }
            }
            
            if ($outputFile) {
                file_put_contents($outputFile, $syslog);
            }
            
            return $syslog;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Exporta logs para formato CEF (Common Event Format)
     */
    public function exportCEF($startDate, $endDate, $outputFile = null) {
        if (!$this->db) return false;
        
        try {
            $stmt = $this->db->prepare("
                SELECT log_data FROM safenode_structured_logs
                WHERE created_at >= ? AND created_at <= ?
                ORDER BY created_at
            ");
            $stmt->execute([$startDate, $endDate]);
            $logs = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $cef = '';
            foreach ($logs as $logJson) {
                $log = json_decode($logJson, true);
                if ($log) {
                    $cefEntry = sprintf(
                        "CEF:0|SafeNode|SafeNode|1.0|%s|%s|%s|",
                        $this->getCEFEventId($log['level']),
                        $log['message'],
                        $this->getCEFSeverity($log['level'])
                    );
                    
                    // Adicionar extensões
                    $extensions = [];
                    if (isset($log['metadata']['ip_address'])) {
                        $extensions[] = 'src=' . $log['metadata']['ip_address'];
                    }
                    if (isset($log['metadata']['request_uri'])) {
                        $extensions[] = 'request=' . $log['metadata']['request_uri'];
                    }
                    if (isset($log['context']['threat_score'])) {
                        $extensions[] = 'threatScore=' . $log['context']['threat_score'];
                    }
                    
                    $cef .= $cefEntry . implode(' ', $extensions) . "\n";
                }
            }
            
            if ($outputFile) {
                file_put_contents($outputFile, $cef);
            }
            
            return $cef;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Obtém prioridade Syslog
     */
    private function getSyslogPriority($level) {
        $priorities = [
            'info' => 6,
            'warning' => 4,
            'error' => 3,
            'critical' => 2
        ];
        return $priorities[$level] ?? 6;
    }
    
    /**
     * Obtém event ID CEF
     */
    private function getCEFEventId($level) {
        return strtoupper($level);
    }
    
    /**
     * Obtém severidade CEF
     */
    private function getCEFSeverity($level) {
        $severities = [
            'info' => 3,
            'warning' => 5,
            'error' => 7,
            'critical' => 9
        ];
        return $severities[$level] ?? 3;
    }
}








