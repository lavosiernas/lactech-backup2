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
    private $confidenceScore = 0;
    private $detectedThreats = [];
    private $contextFactors = [];
    
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
     * Analisa uma requisição e detecta ameaças (SISTEMA MELHORADO)
     */
    public function analyzeRequest($requestUri, $requestMethod, $headers = [], $body = '') {
        $this->threatScore = 0;
        $this->confidenceScore = 0;
        $this->detectedThreats = [];
        $this->contextFactors = [];
        
        // 1. Análise de Contexto (ajusta sensibilidade)
        $this->analyzeContext($requestUri, $requestMethod, $headers);
        
        // 2. Analisar URI (múltiplas camadas de decodificação)
        $decodedUri = urldecode($requestUri);
        $doubleDecodedUri = urldecode($decodedUri);
        
        $this->scanData($requestUri, 'uri', $requestMethod);
        if ($decodedUri !== $requestUri) {
            $this->scanData($decodedUri, 'uri_decoded', $requestMethod);
        }
        if ($doubleDecodedUri !== $decodedUri && $doubleDecodedUri !== $requestUri) {
            $this->scanData($doubleDecodedUri, 'uri_double_decoded', $requestMethod);
        }
        
        // 3. Analisar Body (com contexto)
        if (!empty($body)) {
            $this->scanData($body, 'body', $requestMethod);
        }
        
        // 4. Analisar Headers Específicos
        $userAgent = $headers['User-Agent'] ?? $_SERVER['HTTP_USER_AGENT'] ?? '';
        if (!empty($userAgent)) {
            $this->analyzeUserAgent($userAgent);
        }
        
        // 5. Análise de Padrões Combinados (múltiplas ameaças = mais grave)
        $this->analyzeCombinedPatterns($requestUri, $body);
        
        // 6. Calcular Confidence Score baseado em múltiplos fatores
        $this->calculateConfidenceScore();
        
        // 7. Ajustar Threat Score baseado em contexto e confidence
        $this->adjustThreatScoreByContext();
        
        // Normalizar scores (máximo 100)
        $this->threatScore = min(100, $this->threatScore);
        $this->confidenceScore = min(100, $this->confidenceScore);
        
        return [
            'is_threat' => $this->threatScore >= 50,
            'threat_score' => (int)$this->threatScore,
            'confidence_score' => (int)$this->confidenceScore,
            'threat_type' => $this->getPrimaryThreatType(),
            'detected_threats' => $this->detectedThreats,
            'context_factors' => $this->contextFactors
        ];
    }
    
    /**
     * Analisa contexto da requisição para ajustar sensibilidade
     */
    private function analyzeContext($requestUri, $requestMethod, $headers) {
        // Métodos HTTP suspeitos
        if (in_array(strtoupper($requestMethod), ['PUT', 'DELETE', 'PATCH', 'TRACE', 'OPTIONS'])) {
            $this->contextFactors['suspicious_method'] = true;
            $this->threatScore += 10; // Penalidade por método suspeito
        }
        
        // URIs suspeitas (admin, config, etc)
        $suspiciousPaths = ['/admin', '/config', '/.env', '/wp-admin', '/phpmyadmin', '/.git'];
        foreach ($suspiciousPaths as $path) {
            if (stripos($requestUri, $path) !== false) {
                $this->contextFactors['suspicious_path'] = true;
                $this->threatScore += 15;
                break;
            }
        }
        
        // Headers suspeitos ou ausentes
        if (empty($headers['Accept']) || empty($headers['Accept-Language'])) {
            $this->contextFactors['missing_headers'] = true;
            $this->threatScore += 5;
        }
        
        // Tamanho excessivo de URI ou Body
        if (strlen($requestUri) > 2000) {
            $this->contextFactors['oversized_uri'] = true;
            $this->threatScore += 10;
        }
    }
    
    /**
     * Analisa User-Agent com mais precisão
     */
    private function analyzeUserAgent($userAgent) {
        if (isset($this->threatPatterns['user_agent'])) {
            foreach ($this->threatPatterns['user_agent'] as $patternData) {
                if (preg_match($patternData['pattern'], $userAgent)) {
                    $this->addThreat('bad_bot', $patternData['severity'], 'User-Agent malicioso: ' . substr($userAgent, 0, 50));
                    $this->confidenceScore += 20; // User-Agent malicioso aumenta confidence
                }
            }
        }
        
        // User-Agent vazio ou muito curto
        if (strlen($userAgent) < 10) {
            $this->contextFactors['suspicious_user_agent'] = true;
            $this->threatScore += 5;
        }
    }
    
    /**
     * Analisa padrões combinados (múltiplas ameaças = mais grave)
     */
    private function analyzeCombinedPatterns($requestUri, $body) {
        $threatTypesFound = [];
        foreach ($this->detectedThreats as $threat) {
            $threatTypesFound[$threat['type']] = ($threatTypesFound[$threat['type']] ?? 0) + 1;
        }
        
        // Se encontrou múltiplos tipos de ameaça, aumenta score
        $uniqueThreatTypes = count($threatTypesFound);
        if ($uniqueThreatTypes > 1) {
            $bonus = ($uniqueThreatTypes - 1) * 15; // +15 por tipo adicional
            $this->threatScore += min(30, $bonus); // Máximo +30
            $this->contextFactors['multiple_threat_types'] = $uniqueThreatTypes;
        }
        
        // Se encontrou múltiplas ocorrências do mesmo padrão
        foreach ($threatTypesFound as $type => $count) {
            if ($count > 1) {
                $this->threatScore += ($count - 1) * 5; // +5 por ocorrência adicional
                $this->contextFactors['repeated_patterns'][$type] = $count;
            }
        }
    }
    
    /**
     * Calcula Confidence Score baseado em múltiplos fatores
     */
    private function calculateConfidenceScore() {
        $confidence = 0;
        
        // Base: número de padrões detectados
        $confidence += count($this->detectedThreats) * 10;
        
        // Severidade dos padrões
        foreach ($this->detectedThreats as $threat) {
            if ($threat['severity'] >= 80) {
                $confidence += 15; // Padrões de alta severidade
            } elseif ($threat['severity'] >= 60) {
                $confidence += 10;
            } else {
                $confidence += 5;
            }
        }
        
        // Fatores de contexto aumentam confidence
        if (!empty($this->contextFactors['suspicious_method'])) {
            $confidence += 10;
        }
        if (!empty($this->contextFactors['suspicious_path'])) {
            $confidence += 15;
        }
        if (!empty($this->contextFactors['multiple_threat_types'])) {
            $confidence += 20;
        }
        
        $this->confidenceScore = min(100, $confidence);
    }
    
    /**
     * Ajusta Threat Score baseado em contexto e confidence
     */
    private function adjustThreatScoreByContext() {
        // Se confidence é alto, aumenta threat score
        if ($this->confidenceScore >= 70) {
            $this->threatScore *= 1.1; // +10% se confidence alto
        } elseif ($this->confidenceScore < 30) {
            $this->threatScore *= 0.9; // -10% se confidence baixo (possível falso positivo)
        }
    }
    
    /**
     * Retorna o tipo de ameaça primário (mais grave)
     */
    private function getPrimaryThreatType() {
        if (empty($this->detectedThreats)) {
            return null;
        }
        
        // Ordenar por severidade
        usort($this->detectedThreats, function($a, $b) {
            return $b['severity'] - $a['severity'];
        });
        
        return $this->detectedThreats[0]['type'];
    }

    /**
     * Helper para scanear string (MELHORADO com contexto)
     */
    private function scanData($data, $context, $requestMethod = 'GET') {
        foreach ($this->threatPatterns as $threatType => $patterns) {
            if ($threatType === 'user_agent') continue; // Already handled
            
            foreach ($patterns as $patternData) {
                try {
                    if (preg_match($patternData['pattern'], $data)) {
                        // Ajustar severidade baseado no contexto
                        $adjustedSeverity = $this->adjustSeverityByContext(
                            $patternData['severity'], 
                            $threatType, 
                            $context, 
                            $requestMethod
                        );
                        
                        $this->addThreat($threatType, $adjustedSeverity, $patternData['pattern'], $context);
                        
                        // Se achou uma ameaça grave deste tipo, aumenta confidence
                        if ($adjustedSeverity >= 80) {
                            $this->confidenceScore += 10;
                        }
                    }
                } catch (Exception $e) {
                    // Ignore regex errors
                    error_log("SafeNode ThreatDetector Regex Error: " . $e->getMessage());
                }
            }
        }
    }
    
    /**
     * Ajusta severidade baseado no contexto da detecção
     */
    private function adjustSeverityByContext($baseSeverity, $threatType, $context, $requestMethod) {
        $severity = $baseSeverity;
        
        // Aumentar severidade se encontrado em body (POST/PUT)
        if ($context === 'body' && in_array(strtoupper($requestMethod), ['POST', 'PUT', 'PATCH'])) {
            $severity += 5;
        }
        
        // Aumentar severidade se encontrado em URI decodificada (tentativa de ofuscação)
        if ($context === 'uri_decoded' || $context === 'uri_double_decoded') {
            $severity += 10; // Tentativa de ofuscação = mais grave
        }
        
        // Aumentar severidade para RCE e Command Injection
        if (in_array($threatType, ['rce_php', 'command_injection'])) {
            $severity += 5;
        }
        
        return min(100, $severity);
    }

    private function addThreat($type, $severity, $pattern, $context = 'unknown') {
        // Usar média ponderada ao invés de soma simples (evita scores muito altos)
        $weight = 0.7; // 70% do novo, 30% do acumulado
        $currentScore = $this->threatScore > 0 ? $this->threatScore : 0;
        $this->threatScore = ($currentScore * (1 - $weight)) + ($severity * $weight);
        
        $this->detectedThreats[] = [
            'type' => $type,
            'severity' => $severity,
            'pattern' => substr($pattern, 0, 100), // Limitar tamanho
            'context' => $context,
            'detected_at' => microtime(true)
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
