<?php
/**
 * SafeNode - Threat Detector
 * Sistema de detecção de ameaças em tempo real
 * Baseado em padrões OWASP CRS (Core Rule Set) simplificados para PHP
 */

class ThreatDetector {
    private $db;
    private $threatPatterns;
    private $threatScore = 0;
    private $detectedThreats = [];
    
    public function __construct($database) {
        $this->db = $database;
        $this->loadDefaultPatterns(); // Carrega padrões robustos primeiro
        $this->loadDbPatterns(); // Sobrescreve/adiciona do banco se houver
    }
    
    /**
     * Carrega padrões do banco de dados (opcional)
     */
    private function loadDbPatterns() {
        if (!$this->db) return;
        
        try {
            $stmt = $this->db->query("SELECT pattern, threat_type, severity FROM safenode_threat_patterns WHERE is_active = 1");
            $patterns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($patterns as $pattern) {
                $this->threatPatterns[$pattern['threat_type']][] = [
                    'pattern' => $pattern['pattern'],
                    'severity' => (int)$pattern['severity']
                ];
            }
        } catch (PDOException $e) {
            // Silently fail to defaults
        }
    }
    
    /**
     * Define padrões de ameaça avançados (OWASP Inspired)
     */
    private function loadDefaultPatterns() {
        $this->threatPatterns = [
            'sql_injection' => [
                // Tautologies & Logic
                ['pattern' => '/(\b(or|and)\s+[\'"]?\d+[\'"]?\s*=\s*[\'"]?\d+)/i', 'severity' => 70],
                ['pattern' => '/(\'\s*(or|and)\s*[\'"]?\d+[\'"]?\s*=\s*[\'"]?\d+)/i', 'severity' => 75],
                ['pattern' => '/(--[^\r\n]*)|(\#[^\r\n]*)/', 'severity' => 80], // Comments
                ['pattern' => '/(\/\*.*\*\/)/', 'severity' => 80], // Inline comments

                // Union Based
                ['pattern' => '/(\bunion\s+(all\s+)?(select|insert|update|delete)\b)/i', 'severity' => 90],
                
                // Schema Information
                ['pattern' => '/(information_schema|table_schema|table_name)/i', 'severity' => 85],
                
                // Dangerous Functions
                ['pattern' => '/(\b(load_file|benchmark|sleep|pg_sleep|waitfor\s+delay)\b)/i', 'severity' => 85],
                ['pattern' => '/(char\s*\(\s*\d)/i', 'severity' => 60], // char(x) obfuscation
                ['pattern' => '/(concat\s*\()/i', 'severity' => 60],
                
                // Stacked Queries
                ['pattern' => '/(;\s*(drop|delete|truncate|alter|create)\s+\b)/i', 'severity' => 90],
            ],
            'xss' => [
                // Script tags
                ['pattern' => '/(<script[^>]*>.*?<\/script>)/is', 'severity' => 90],
                ['pattern' => '/(<script)/i', 'severity' => 80],
                
                // Event Handlers
                ['pattern' => '/(\bon\w+\s*=\s*[\'"][^\'"]*[\'"])/i', 'severity' => 75],
                ['pattern' => '/(javascript:)/i', 'severity' => 75],
                ['pattern' => '/(vbscript:)/i', 'severity' => 75],
                ['pattern' => '/(data:text\/html)/i', 'severity' => 80],
                
                // Dangerous Tags
                ['pattern' => '/(<(iframe|object|embed|applet|meta|base|form)[^>]*>)/i', 'severity' => 70],
                ['pattern' => '/(<img[^>]+onerror)/i', 'severity' => 80],
                ['pattern' => '/(<svg[^>]+onload)/i', 'severity' => 80],
            ],
            'rce_php' => [
                // PHP Injection / Wrappers
                ['pattern' => '/(php:\/\/filter|php:\/\/input|file:\/\/)/i', 'severity' => 90],
                ['pattern' => '/(eval\s*\(|assert\s*\(|passthru\s*\(|exec\s*\(|system\s*\(|shell_exec\s*\(|popen\s*\(|proc_open\s*\()/i', 'severity' => 95],
                ['pattern' => '/(base64_decode\s*\()/i', 'severity' => 60], // Suspicious if in params
                ['pattern' => '/(GLOBALS|REQUEST)/', 'severity' => 50],
            ],
            'path_traversal' => [
                ['pattern' => '/(\.\.\/|\.\.\\\\)/', 'severity' => 70],
                ['pattern' => '/(\/etc\/passwd|\/etc\/shadow|\/etc\/hosts|\/windows\/win.ini)/i', 'severity' => 90],
                ['pattern' => '/(\/proc\/self\/environ)/i', 'severity' => 80],
                ['pattern' => '/(\.\.%2f|\.\.%5c)/i', 'severity' => 70], // Encoded
            ],
            'command_injection' => [
                // Shell operators followed by commands
                ['pattern' => '/([;&|`]\s*(ping|nslookup|whoami|uname|id|ls|cat|wget|curl|nc|netcat|bash|sh)\b)/i', 'severity' => 85],
                ['pattern' => '/(\$\(.*\))/i', 'severity' => 80], // $(cmd)
            ],
            'user_agent' => [
                // Bad Bots
                ['pattern' => '/(acunetix|sqlmap|nikto|metasploit|nessus|nmap|havij|dirbuster)/i', 'severity' => 100],
                ['pattern' => '/(python-requests|curl|wget|libwww-perl)/i', 'severity' => 40], // Low severity, legit sometimes
            ]
        ];
    }
    
    /**
     * Analisa uma requisição e detecta ameaças
     */
    public function analyzeRequest($requestUri, $requestMethod, $headers = [], $body = '') {
        $this->threatScore = 0;
        $this->detectedThreats = [];
        
        // 1. Analisar URI (decode first to catch encoded attacks)
        $decodedUri = urldecode($requestUri);
        $this->scanData($requestUri, 'uri');
        if ($decodedUri !== $requestUri) {
            $this->scanData($decodedUri, 'uri_decoded');
        }
        
        // 2. Analisar Body
        if (!empty($body)) {
            $this->scanData($body, 'body');
        }
        
        // 3. Analisar Headers Específicos
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if (!empty($userAgent)) {
            // Checar User-Agent contra regras específicas
            if (isset($this->threatPatterns['user_agent'])) {
                foreach ($this->threatPatterns['user_agent'] as $patternData) {
                    if (preg_match($patternData['pattern'], $userAgent)) {
                        $this->addThreat('bad_bot', $patternData['severity'], 'User-Agent malicioso');
                    }
                }
            }
        }
        
        // Normalizar score (máximo 100)
        $this->threatScore = min(100, $this->threatScore);
        
        return [
            'is_threat' => $this->threatScore >= 50,
            'threat_score' => $this->threatScore,
            'threat_type' => !empty($this->detectedThreats) ? $this->detectedThreats[0]['type'] : null,
            'detected_threats' => $this->detectedThreats
        ];
    }

    /**
     * Helper para scanear string
     */
    private function scanData($data, $context) {
        foreach ($this->threatPatterns as $threatType => $patterns) {
            if ($threatType === 'user_agent') continue; // Already handled
            
            foreach ($patterns as $patternData) {
                try {
                    if (preg_match($patternData['pattern'], $data)) {
                        $this->addThreat($threatType, $patternData['severity'], $patternData['pattern']);
                        // Se achou uma ameaça grave deste tipo, pula para o próximo tipo
                        if ($patternData['severity'] >= 80) break; 
                    }
                } catch (Exception $e) {
                    // Ignore regex errors
                }
            }
        }
    }

    private function addThreat($type, $severity, $pattern) {
        $this->threatScore += $severity;
        $this->detectedThreats[] = [
            'type' => $type,
            'severity' => $severity,
            'pattern' => $pattern
        ];
    }
    
    /**
     * Detecta tentativas de brute force baseado em frequência
     */
    public function detectBruteForce($ipAddress, $requestUri, $timeWindow = 300) {
        if (!$this->db) return false;
        
        try {
            // Contar tentativas de login nos últimos 5 minutos
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as attempts 
                FROM safenode_security_logs 
                WHERE ip_address = ? 
                AND request_uri LIKE ? 
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)
                AND action_taken = 'blocked'
            ");
            
            $loginPattern = '%login%';
            $stmt->execute([$ipAddress, $loginPattern, $timeWindow]);
            $result = $stmt->fetch();
            
            // Se mais de 5 tentativas bloqueadas em 5 minutos = brute force
            return ($result['attempts'] ?? 0) >= 5;
        } catch (PDOException $e) {
            error_log("SafeNode BruteForce Detection Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Detecta DDoS baseado em volume de requisições
     */
    public function detectDDoS($ipAddress, $timeWindow = 60) {
        if (!$this->db) return false;
        
        try {
            // Contar requisições nos últimos 60 segundos
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as requests 
                FROM safenode_security_logs 
                WHERE ip_address = ? 
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)
            ");
            
            $stmt->execute([$ipAddress, $timeWindow]);
            $result = $stmt->fetch();
            
            // Se mais de 100 requisições em 1 minuto = possível DDoS
            return ($result['requests'] ?? 0) >= 100;
        } catch (PDOException $e) {
            error_log("SafeNode DDoS Detection Error: " . $e->getMessage());
            return false;
        }
    }
}
