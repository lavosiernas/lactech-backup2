<?php
/**
 * SafeNode - System Logger
 * Classe para capturar e salvar logs de erros do sistema no banco de dados
 */

class SystemLogger {
    private static $instance = null;
    private $db = null;
    
    private function __construct() {
        try {
            require_once __DIR__ . '/config.php';
            $this->db = getSafeNodeDatabase();
            
            // Garantir que a tabela existe
            $this->ensureTableExists();
        } catch (Exception $e) {
            // Falha silenciosa - não bloquear o sistema
            error_log("SystemLogger: Erro ao inicializar: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Garantir que a tabela de logs existe
     */
    private function ensureTableExists() {
        if (!$this->db) return;
        
        try {
            $this->db->query("SELECT 1 FROM safenode_system_logs LIMIT 1");
        } catch (PDOException $e) {
            // Tabela não existe, criar
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS `safenode_system_logs` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `level` enum('info','warning','error','critical','debug') DEFAULT 'error',
                  `message` text NOT NULL,
                  `context` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
                  `file` varchar(500) DEFAULT NULL,
                  `line` int(11) DEFAULT NULL,
                  `function` varchar(255) DEFAULT NULL,
                  `trace` text DEFAULT NULL,
                  `user_id` int(11) DEFAULT NULL,
                  `ip_address` varchar(45) DEFAULT NULL,
                  `user_agent` text DEFAULT NULL,
                  `request_uri` varchar(500) DEFAULT NULL,
                  `request_method` varchar(10) DEFAULT NULL,
                  `session_id` varchar(255) DEFAULT NULL,
                  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                  PRIMARY KEY (`id`),
                  KEY `idx_level` (`level`),
                  KEY `idx_created` (`created_at`),
                  KEY `idx_user_id` (`user_id`),
                  KEY `idx_message` (`message`(100)),
                  KEY `idx_file` (`file`(100)),
                  FULLTEXT KEY `ft_message` (`message`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }
    }
    
    /**
     * Log de erro
     */
    public function error($message, $context = [], $exception = null) {
        return $this->log('error', $message, $context, $exception);
    }
    
    /**
     * Log crítico
     */
    public function critical($message, $context = [], $exception = null) {
        return $this->log('critical', $message, $context, $exception);
    }
    
    /**
     * Log de aviso
     */
    public function warning($message, $context = []) {
        return $this->log('warning', $message, $context);
    }
    
    /**
     * Log de informação
     */
    public function info($message, $context = []) {
        return $this->log('info', $message, $context);
    }
    
    /**
     * Log de debug
     */
    public function debug($message, $context = []) {
        return $this->log('debug', $message, $context);
    }
    
    /**
     * Método principal de logging
     */
    public function log($level, $message, $context = [], $exception = null) {
        if (!$this->db) {
            // Fallback para error_log se não houver banco
            error_log("SafeNode [{$level}]: {$message}");
            return false;
        }
        
        try {
            // Preparar dados
            $file = null;
            $line = null;
            $function = null;
            $trace = null;
            
            if ($exception instanceof Exception || $exception instanceof Error) {
                $file = $exception->getFile();
                $line = $exception->getLine();
                $trace = $exception->getTraceAsString();
            } else {
                // Obter informações do debug_backtrace
                $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
                if (!empty($backtrace)) {
                    // Pular esta função e a função que chamou log()
                    $caller = $backtrace[1] ?? null;
                    if ($caller) {
                        $file = $caller['file'] ?? null;
                        $line = $caller['line'] ?? null;
                        $function = ($caller['class'] ?? '') . ($caller['type'] ?? '') . ($caller['function'] ?? '');
                    }
                }
            }
            
            // Contexto adicional
            $contextData = array_merge($context, [
                'timestamp' => date('c'),
                'memory_usage' => memory_get_usage(true),
                'memory_peak' => memory_get_peak_usage(true)
            ]);
            
            // Obter informações da requisição
            $userId = $_SESSION['safenode_user_id'] ?? null;
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            $requestUri = $_SERVER['REQUEST_URI'] ?? null;
            $requestMethod = $_SERVER['REQUEST_METHOD'] ?? null;
            $sessionId = session_id() ?: null;
            
            // Inserir no banco
            $stmt = $this->db->prepare("
                INSERT INTO safenode_system_logs 
                (level, message, context, file, line, function, trace, user_id, ip_address, user_agent, request_uri, request_method, session_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $level,
                $message,
                json_encode($contextData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                $file,
                $line,
                $function,
                $trace,
                $userId,
                $ipAddress,
                $userAgent,
                $requestUri,
                $requestMethod,
                $sessionId
            ]);
            
            // Também registrar no error_log padrão
            error_log("SafeNode [{$level}]: {$message}" . ($file ? " in {$file}:{$line}" : ""));
            
            return true;
        } catch (Exception $e) {
            // Falha silenciosa - não bloquear o sistema
            error_log("SystemLogger: Erro ao salvar log: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Capturar exceções não tratadas
     */
    public static function registerErrorHandler() {
        set_error_handler(function($errno, $errstr, $errfile, $errline) {
            $logger = self::getInstance();
            
            $level = 'error';
            if ($errno & (E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR | E_PARSE)) {
                $level = 'critical';
            } elseif ($errno & (E_WARNING | E_CORE_WARNING | E_COMPILE_WARNING)) {
                $level = 'warning';
            }
            
            $logger->log($level, $errstr, [
                'errno' => $errno,
                'error_type' => self::getErrorType($errno)
            ], new ErrorException($errstr, 0, $errno, $errfile, $errline));
            
            // Retornar false para permitir que o tratamento padrão continue
            return false;
        });
        
        set_exception_handler(function($exception) {
            $logger = self::getInstance();
            $logger->critical(
                "Uncaught exception: " . $exception->getMessage(),
                [
                    'exception_class' => get_class($exception),
                    'exception_code' => $exception->getCode()
                ],
                $exception
            );
        });
    }
    
    /**
     * Obter nome do tipo de erro
     */
    private static function getErrorType($errno) {
        $types = [
            E_ERROR => 'E_ERROR',
            E_WARNING => 'E_WARNING',
            E_PARSE => 'E_PARSE',
            E_NOTICE => 'E_NOTICE',
            E_CORE_ERROR => 'E_CORE_ERROR',
            E_CORE_WARNING => 'E_CORE_WARNING',
            E_COMPILE_ERROR => 'E_COMPILE_ERROR',
            E_COMPILE_WARNING => 'E_COMPILE_WARNING',
            E_USER_ERROR => 'E_USER_ERROR',
            E_USER_WARNING => 'E_USER_WARNING',
            E_USER_NOTICE => 'E_USER_NOTICE',
            E_STRICT => 'E_STRICT',
            E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
            E_DEPRECATED => 'E_DEPRECATED',
            E_USER_DEPRECATED => 'E_USER_DEPRECATED'
        ];
        
        return $types[$errno] ?? "Unknown ($errno)";
    }
}

