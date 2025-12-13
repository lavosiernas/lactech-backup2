<?php
/**
 * SafeNode - Advanced Honeypot System
 * Sistema de honeypots dinâmicos para detectar bots e scrapers
 * 
 * Honeypots:
 * - Links invisíveis em páginas
 * - Campos de formulário ocultos
 * - Endpoints de API falsos
 */

class AdvancedHoneypot {
    private $db;
    private $cache;
    
    public function __construct($database) {
        $this->db = $database;
        require_once __DIR__ . '/CacheManager.php';
        $this->cache = CacheManager::getInstance();
        $this->ensureTableExists();
    }
    
    /**
     * Gera HTML de honeypot para incluir na página
     * 
     * @param string $type Tipo de honeypot ('link', 'form_field', 'both')
     * @return string HTML do honeypot
     */
    public function generateHoneypotHTML($type = 'both') {
        $honeypotId = bin2hex(random_bytes(8));
        $honeypotUrl = $this->generateFakeEndpoint($honeypotId);
        
        $html = '';
        
        // Link invisível
        if ($type === 'link' || $type === 'both') {
            $html .= $this->generateInvisibleLink($honeypotId, $honeypotUrl);
        }
        
        // Campo de formulário oculto
        if ($type === 'form_field' || $type === 'both') {
            $html .= $this->generateHiddenField($honeypotId);
        }
        
        // Salvar honeypot no cache/banco
        $this->registerHoneypot($honeypotId, $honeypotUrl);
        
        return $html;
    }
    
    /**
     * Gera link invisível (CSS: display:none)
     */
    private function generateInvisibleLink($honeypotId, $url) {
        // Gerar nome atrativo para bots
        $attractiveNames = [
            'admin', 'login', 'wp-admin', 'phpmyadmin', 'config',
            'backup', 'database', 'api', 'secret', 'private'
        ];
        $linkName = $attractiveNames[array_rand($attractiveNames)];
        
        return "
        <div style='display:none !important; visibility:hidden !important; position:absolute !important; left:-9999px !important;'>
            <a href='{$url}' id='honeypot-link-{$honeypotId}' data-honeypot='{$honeypotId}'>
                {$linkName}
            </a>
        </div>
        ";
    }
    
    /**
     * Gera campo de formulário oculto
     */
    private function generateHiddenField($honeypotId) {
        // Nome de campo que bots geralmente preenchem
        $fieldNames = [
            'email', 'phone', 'website', 'url', 'comment',
            'message', 'description', 'notes'
        ];
        $fieldName = $fieldNames[array_rand($fieldNames)];
        
        return "
        <div style='display:none !important; visibility:hidden !important; position:absolute !important; left:-9999px !important;'>
            <label for='honeypot-{$honeypotId}'>Leave this field empty</label>
            <input type='text' 
                   id='honeypot-{$honeypotId}' 
                   name='{$fieldName}' 
                   value='' 
                   autocomplete='off'
                   tabindex='-1'
                   data-honeypot='{$honeypotId}'>
        </div>
        ";
    }
    
    /**
     * Gera endpoint falso aleatório
     */
    private function generateFakeEndpoint($honeypotId) {
        $baseUrl = getSafeNodeBaseUrl();
        $fakePaths = [
            'wp-admin', 'phpmyadmin', 'admin.php', 'config.php',
            'backup.sql', 'database.php', 'api/secret', 'private',
            'login.php', 'auth.php', '.env', '.git/config'
        ];
        $fakePath = $fakePaths[array_rand($fakePaths)];
        
        return $baseUrl . '/' . $fakePath . '?id=' . $honeypotId;
    }
    
    /**
     * Registra honeypot no sistema
     */
    private function registerHoneypot($honeypotId, $url) {
        // Salvar no cache (TTL: 1 hora)
        $this->cache->set("honeypot:$honeypotId", [
            'url' => $url,
            'created_at' => time()
        ], 3600);
        
        // Salvar no banco também
        if ($this->db) {
            try {
                $stmt = $this->db->prepare("
                    INSERT INTO safenode_honeypots 
                    (honeypot_id, url, created_at) 
                    VALUES (?, ?, NOW())
                    ON DUPLICATE KEY UPDATE created_at = NOW()
                ");
                $stmt->execute([$honeypotId, $url]);
            } catch (PDOException $e) {
                // Ignorar erros silenciosamente
            }
        }
    }
    
    /**
     * Verifica se requisição é acesso a honeypot
     * 
     * @param string $requestUri URI da requisição
     * @param string $ipAddress IP do cliente
     * @return array|null Dados do honeypot se detectado, null caso contrário
     */
    public function checkHoneypotAccess($requestUri, $ipAddress) {
        // Extrair honeypot_id da URL
        if (preg_match('/[?&]id=([a-f0-9]{16})/', $requestUri, $matches)) {
            $honeypotId = $matches[1];
            
            // Verificar no cache primeiro
            $honeypot = $this->cache->get("honeypot:$honeypotId");
            
            if ($honeypot === null && $this->db) {
                // Verificar no banco
                try {
                    $stmt = $this->db->prepare("
                        SELECT * FROM safenode_honeypots 
                        WHERE honeypot_id = ? 
                        AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
                    ");
                    $stmt->execute([$honeypotId]);
                    $result = $stmt->fetch();
                    
                    if ($result) {
                        $honeypot = [
                            'url' => $result['url'],
                            'created_at' => strtotime($result['created_at'])
                        ];
                    }
                } catch (PDOException $e) {
                    // Ignorar
                }
            }
            
            if ($honeypot) {
                // Registrar acesso ao honeypot
                $this->recordHoneypotAccess($honeypotId, $ipAddress, $requestUri);
                
                return [
                    'honeypot_id' => $honeypotId,
                    'url' => $honeypot['url'],
                    'ip_address' => $ipAddress,
                    'is_bot' => true
                ];
            }
        }
        
        // Verificar campos de formulário preenchidos (POST)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            foreach ($_POST as $key => $value) {
                if (!empty($value) && preg_match('/honeypot-([a-f0-9]{16})/', $key, $matches)) {
                    $honeypotId = $matches[1];
                    $this->recordHoneypotAccess($honeypotId, $ipAddress, 'POST:' . $key);
                    
                    return [
                        'honeypot_id' => $honeypotId,
                        'ip_address' => $ipAddress,
                        'field' => $key,
                        'is_bot' => true
                    ];
                }
            }
        }
        
        return null;
    }
    
    /**
     * Registra acesso ao honeypot
     */
    private function recordHoneypotAccess($honeypotId, $ipAddress, $requestUri) {
        if (!$this->db) return;
        
        try {
            $stmt = $this->db->prepare("
                INSERT INTO safenode_honeypot_access 
                (honeypot_id, ip_address, request_uri, created_at) 
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$honeypotId, $ipAddress, $requestUri]);
            
            // Marcar IP como bot
            require_once __DIR__ . '/IPBlocker.php';
            $ipBlocker = new IPBlocker($this->db);
            $ipBlocker->blockIP($ipAddress, "Acesso a honeypot detectado", 'honeypot_bot', 86400); // Bloquear por 24h
            
        } catch (PDOException $e) {
            error_log("SafeNode Honeypot Record Error: " . $e->getMessage());
        }
    }
    
    /**
     * Garante que tabelas existem
     */
    private function ensureTableExists() {
        if (!$this->db) return;
        
        try {
            // Tabela de honeypots
            $this->db->query("SELECT 1 FROM safenode_honeypots LIMIT 1");
        } catch (PDOException $e) {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS safenode_honeypots (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    honeypot_id VARCHAR(16) NOT NULL UNIQUE,
                    url VARCHAR(500) NOT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_id (honeypot_id),
                    INDEX idx_created (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }
        
        try {
            // Tabela de acessos a honeypots
            $this->db->query("SELECT 1 FROM safenode_honeypot_access LIMIT 1");
        } catch (PDOException $e) {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS safenode_honeypot_access (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    honeypot_id VARCHAR(16) NOT NULL,
                    ip_address VARCHAR(45) NOT NULL,
                    request_uri VARCHAR(500),
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_honeypot (honeypot_id),
                    INDEX idx_ip (ip_address),
                    INDEX idx_created (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }
    }
    
    /**
     * Obtém estatísticas de honeypots
     */
    public function getStats($siteId = null, $days = 7) {
        if (!$this->db) return [];
        
        try {
            $sql = "
                SELECT 
                    COUNT(*) as total_accesses,
                    COUNT(DISTINCT ip_address) as unique_bots,
                    DATE(created_at) as date
                FROM safenode_honeypot_access
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY DATE(created_at)
                ORDER BY date DESC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$days]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            return [];
        }
    }
}






