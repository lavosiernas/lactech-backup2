<?php
/**
 * SafeNode - Advanced WAF (Web Application Firewall)
 * Sistema de regras personalizadas avançadas
 * 
 * Suporta:
 * - Sintaxe similar a ModSecurity
 * - Regex complexo
 * - Condições múltiplas (AND/OR)
 * - Ações: block, allow, challenge, log, redirect
 */

class AdvancedWAF {
    private $db;
    private $cache;
    private $rules = [];
    
    public function __construct($database) {
        $this->db = $database;
        require_once __DIR__ . '/CacheManager.php';
        $this->cache = CacheManager::getInstance();
        $this->loadRules();
    }
    
    /**
     * Carrega regras do banco de dados
     */
    private function loadRules() {
        $cacheKey = 'waf_rules';
        $cached = $this->cache->get($cacheKey);
        
        if ($cached !== null) {
            $this->rules = $cached;
            return;
        }
        
        if (!$this->db) {
            $this->rules = $this->getDefaultRules();
            return;
        }
        
        try {
            $this->ensureTableExists();
            
            $stmt = $this->db->query("
                SELECT * FROM safenode_waf_rules 
                WHERE is_active = 1 
                ORDER BY priority DESC, id ASC
            ");
            $this->rules = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            
            // Se não há regras, usar padrões
            if (empty($this->rules)) {
                $this->rules = $this->getDefaultRules();
            }
            
            // Cache por 30 minutos
            $this->cache->set($cacheKey, $this->rules, 1800);
        } catch (PDOException $e) {
            $this->rules = $this->getDefaultRules();
        }
    }
    
    /**
     * Avalia requisição contra regras WAF
     * 
     * @param string $ipAddress IP do cliente
     * @param string $requestUri URI da requisição
     * @param string $requestMethod Método HTTP
     * @param array $headers Headers HTTP
     * @param string $body Body da requisição
     * @return array Resultado da avaliação
     */
    public function evaluate($ipAddress, $requestUri, $requestMethod, $headers = [], $body = '') {
        foreach ($this->rules as $rule) {
            $match = $this->evaluateRule($rule, $ipAddress, $requestUri, $requestMethod, $headers, $body);
            
            if ($match) {
                return [
                    'matched' => true,
                    'rule_id' => $rule['id'] ?? null,
                    'rule_name' => $rule['name'] ?? 'Unknown',
                    'action' => $rule['action'] ?? 'block',
                    'severity' => (int)($rule['severity'] ?? 50),
                    'message' => $rule['message'] ?? 'Regra WAF violada'
                ];
            }
        }
        
        return ['matched' => false];
    }
    
    /**
     * Avalia regra individual
     */
    private function evaluateRule($rule, $ipAddress, $requestUri, $requestMethod, $headers, $body) {
        $conditions = json_decode($rule['conditions'] ?? '[]', true) ?: [];
        
        if (empty($conditions)) {
            return false;
        }
        
        $operator = strtoupper($rule['operator'] ?? 'AND');
        $matches = [];
        
        foreach ($conditions as $condition) {
            $field = $condition['field'] ?? '';
            $operator_cond = $condition['operator'] ?? 'equals';
            $value = $condition['value'] ?? '';
            
            $match = false;
            
            switch ($field) {
                case 'uri':
                    $match = $this->matchString($requestUri, $operator_cond, $value);
                    break;
                    
                case 'method':
                    $match = $this->matchString($requestMethod, $operator_cond, $value);
                    break;
                    
                case 'ip':
                    $match = $this->matchString($ipAddress, $operator_cond, $value);
                    break;
                    
                case 'user_agent':
                    $ua = $headers['User-Agent'] ?? $_SERVER['HTTP_USER_AGENT'] ?? '';
                    $match = $this->matchString($ua, $operator_cond, $value);
                    break;
                    
                case 'body':
                    $match = $this->matchString($body, $operator_cond, $value);
                    break;
                    
                case 'header':
                    $headerName = $condition['header_name'] ?? '';
                    $headerValue = $headers[$headerName] ?? $_SERVER['HTTP_' . strtoupper(str_replace('-', '_', $headerName))] ?? '';
                    $match = $this->matchString($headerValue, $operator_cond, $value);
                    break;
            }
            
            $matches[] = $match;
        }
        
        // Aplicar operador lógico
        if ($operator === 'AND') {
            return !in_array(false, $matches);
        } else { // OR
            return in_array(true, $matches);
        }
    }
    
    /**
     * Compara string com operador
     */
    private function matchString($subject, $operator, $pattern) {
        switch ($operator) {
            case 'equals':
                return $subject === $pattern;
                
            case 'contains':
                return stripos($subject, $pattern) !== false;
                
            case 'starts_with':
                return stripos($subject, $pattern) === 0;
                
            case 'ends_with':
                return stripos(strrev($subject), strrev($pattern)) === 0;
                
            case 'regex':
                return @preg_match($pattern, $subject) === 1;
                
            case 'not_contains':
                return stripos($subject, $pattern) === false;
                
            default:
                return false;
        }
    }
    
    /**
     * Retorna regras padrão (OWASP Top 10)
     */
    private function getDefaultRules() {
        return [
            [
                'id' => 'default_sql_injection',
                'name' => 'SQL Injection Detection',
                'action' => 'block',
                'severity' => 90,
                'operator' => 'OR',
                'conditions' => json_encode([
                    ['field' => 'uri', 'operator' => 'regex', 'value' => '/(\b(or|and)\s+[\'"]?\d+[\'"]?\s*=\s*[\'"]?\d+)/i'],
                    ['field' => 'body', 'operator' => 'regex', 'value' => '/(\bunion\s+(all\s+)?(select|insert|update|delete)\b)/i'],
                    ['field' => 'uri', 'operator' => 'regex', 'value' => '/(--[^\r\n]*)|(\#[^\r\n]*)/']
                ]),
                'message' => 'Tentativa de SQL Injection detectada'
            ],
            [
                'id' => 'default_xss',
                'name' => 'XSS Detection',
                'action' => 'block',
                'severity' => 85,
                'operator' => 'OR',
                'conditions' => json_encode([
                    ['field' => 'uri', 'operator' => 'regex', 'value' => '/(<script[^>]*>.*?<\/script>)/is'],
                    ['field' => 'body', 'operator' => 'regex', 'value' => '/(javascript:)|(vbscript:)/i'],
                    ['field' => 'uri', 'operator' => 'regex', 'value' => '/(\bon\w+\s*=\s*[\'"][^\'"]*[\'"])/i']
                ]),
                'message' => 'Tentativa de XSS detectada'
            ],
            [
                'id' => 'default_path_traversal',
                'name' => 'Path Traversal Detection',
                'action' => 'block',
                'severity' => 80,
                'operator' => 'OR',
                'conditions' => json_encode([
                    ['field' => 'uri', 'operator' => 'contains', 'value' => '../'],
                    ['field' => 'uri', 'operator' => 'contains', 'value' => '..\\'],
                    ['field' => 'uri', 'operator' => 'regex', 'value' => '/(\/etc\/passwd|\/etc\/shadow)/i']
                ]),
                'message' => 'Tentativa de Path Traversal detectada'
            ]
        ];
    }
    
    /**
     * Garante que tabela existe
     */
    private function ensureTableExists() {
        try {
            $this->db->query("SELECT 1 FROM safenode_waf_rules LIMIT 1");
        } catch (PDOException $e) {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS safenode_waf_rules (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(100) NOT NULL,
                    description TEXT,
                    action ENUM('block', 'allow', 'challenge', 'log', 'redirect') DEFAULT 'block',
                    severity INT DEFAULT 50,
                    operator ENUM('AND', 'OR') DEFAULT 'AND',
                    conditions JSON,
                    priority INT DEFAULT 0,
                    is_active TINYINT(1) DEFAULT 1,
                    message VARCHAR(500),
                    redirect_url VARCHAR(500),
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_active (is_active, priority)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            
            // Inserir regras padrão
            foreach ($this->getDefaultRules() as $rule) {
                $stmt = $this->db->prepare("
                    INSERT INTO safenode_waf_rules 
                    (name, action, severity, operator, conditions, message, is_active) 
                    VALUES (?, ?, ?, ?, ?, ?, 1)
                ");
                $stmt->execute([
                    $rule['name'],
                    $rule['action'],
                    $rule['severity'],
                    $rule['operator'],
                    $rule['conditions'],
                    $rule['message']
                ]);
            }
        }
    }
    
    /**
     * Cria nova regra
     */
    public function createRule($name, $action, $conditions, $operator = 'AND', $severity = 50, $message = null) {
        if (!$this->db) return false;
        
        try {
            $this->ensureTableExists();
            
            $stmt = $this->db->prepare("
                INSERT INTO safenode_waf_rules 
                (name, action, severity, operator, conditions, message, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, 1)
            ");
            
            $result = $stmt->execute([
                $name,
                $action,
                $severity,
                $operator,
                json_encode($conditions),
                $message ?: "Regra WAF: $name"
            ]);
            
            // Invalidar cache
            $this->cache->delete('waf_rules');
            $this->loadRules();
            
            return $result;
        } catch (PDOException $e) {
            error_log("SafeNode WAF Create Rule Error: " . $e->getMessage());
            return false;
        }
    }
}








