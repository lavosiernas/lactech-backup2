<?php
/**
 * SafeNode - Threat Detector
 * Sistema de detecção de ameaças em tempo real
 */

class ThreatDetector {
    private $db;
    private $threatPatterns;
    private $threatScore = 0;
    private $detectedThreats = [];
    
    public function __construct($database) {
        $this->db = $database;
        $this->loadThreatPatterns();
    }
    
    /**
     * Carrega padrões de ameaça do banco de dados
     */
    private function loadThreatPatterns() {
        if (!$this->db) return;
        
        try {
            $stmt = $this->db->query("SELECT pattern, threat_type, severity FROM safenode_threat_patterns WHERE is_active = 1");
            $patterns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $this->threatPatterns = [];
            foreach ($patterns as $pattern) {
                $this->threatPatterns[$pattern['threat_type']][] = [
                    'pattern' => $pattern['pattern'],
                    'severity' => (int)$pattern['severity']
                ];
            }
        } catch (PDOException $e) {
            error_log("SafeNode ThreatDetector Error: " . $e->getMessage());
            $this->loadDefaultPatterns();
        }
    }
    
    /**
     * Carrega padrões padrão se o banco não tiver
     */
    private function loadDefaultPatterns() {
        $this->threatPatterns = [
            'sql_injection' => [
                ['pattern' => '/(\b(union|select|insert|update|delete|drop|create|alter|exec|execute)\b.*\b(from|into|where|table|database)\b)/i', 'severity' => 80],
                ['pattern' => '/(\b(or|and)\s+\d+\s*=\s*\d+)/i', 'severity' => 70],
                ['pattern' => '/(\b(or|and)\s+[\'"]?\d+[\'"]?\s*=\s*[\'"]?\d+)/i', 'severity' => 70],
                ['pattern' => '/(\b(union|select).*from.*information_schema)/i', 'severity' => 90],
                ['pattern' => '/(\b(union|select).*from.*users)/i', 'severity' => 85],
                ['pattern' => '/(\'\s*(or|and)\s*[\'"]?\d+[\'"]?\s*=\s*[\'"]?\d+)/i', 'severity' => 75],
                ['pattern' => '/(;\s*(drop|delete|truncate|alter))/i', 'severity' => 85],
            ],
            'xss' => [
                ['pattern' => '/(<script[^>]*>.*?<\/script>)/is', 'severity' => 80],
                ['pattern' => '/(javascript:)/i', 'severity' => 70],
                ['pattern' => '/(on\w+\s*=\s*[\'"][^\'"]*[\'"])/i', 'severity' => 75],
                ['pattern' => '/(<iframe[^>]*>)/i', 'severity' => 70],
                ['pattern' => '/(<img[^>]*onerror)/i', 'severity' => 75],
                ['pattern' => '/(eval\s*\()/i', 'severity' => 80],
                ['pattern' => '/(expression\s*\()/i', 'severity' => 70],
            ],
            'path_traversal' => [
                ['pattern' => '/(\.\.\/|\.\.\\\\)/i', 'severity' => 60],
                ['pattern' => '/(\/etc\/passwd|\/etc\/shadow)/i', 'severity' => 80],
                ['pattern' => '/(\/proc\/self\/environ)/i', 'severity' => 70],
                ['pattern' => '/(\.\.%2f|\.\.%5c)/i', 'severity' => 65],
                ['pattern' => '/(\.\.%252f|\.\.%255c)/i', 'severity' => 65],
            ],
            'command_injection' => [
                ['pattern' => '/(;\s*(rm|ls|cat|pwd|whoami|id|uname|ps|kill))/i', 'severity' => 75],
                ['pattern' => '/(\|\s*(rm|ls|cat|pwd|whoami|id|uname|ps|kill))/i', 'severity' => 75],
                ['pattern' => '/(`[^`]*`)/i', 'severity' => 70],
                ['pattern' => '/(\$\([^)]*\))/i', 'severity' => 70],
                ['pattern' => '/(\b(ping|nc|netcat|curl|wget)\b)/i', 'severity' => 60],
            ],
            'brute_force' => [
                // Detectado por padrão de requisições, não por conteúdo
                ['pattern' => '/(login|admin|password|auth)/i', 'severity' => 30],
            ],
        ];
    }
    
    /**
     * Analisa uma requisição e detecta ameaças
     */
    public function analyzeRequest($requestUri, $requestMethod, $headers = [], $body = '') {
        $this->threatScore = 0;
        $this->detectedThreats = [];
        
        // Combinar todos os dados da requisição para análise
        $dataToAnalyze = strtolower($requestUri . ' ' . $requestMethod . ' ' . implode(' ', $headers) . ' ' . $body);
        
        // Verificar cada tipo de ameaça
        foreach ($this->threatPatterns as $threatType => $patterns) {
            foreach ($patterns as $patternData) {
                if (preg_match($patternData['pattern'], $dataToAnalyze)) {
                    $this->threatScore += $patternData['severity'];
                    $this->detectedThreats[] = [
                        'type' => $threatType,
                        'severity' => $patternData['severity'],
                        'pattern' => $patternData['pattern']
                    ];
                    
                    // Não contar o mesmo padrão múltiplas vezes
                    break;
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

