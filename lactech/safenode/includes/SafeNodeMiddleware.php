<?php
/**
 * SafeNode - Middleware de Segurança
 * Arquivo principal que deve ser incluído no início dos sites protegidos
 * 
 * USO: Inclua este arquivo no início do seu site:
 * require_once '/caminho/para/safenode/includes/SafeNodeMiddleware.php';
 * SafeNodeMiddleware::protect();
 */

class SafeNodeMiddleware {
    private static $db;
    private static $siteId;
    private static $siteDomain;
    private static $startTime;
    
    /**
     * Inicializa a proteção do SafeNode
     */
    public static function protect() {
        self::$startTime = microtime(true);
        
        // Carregar configuração
        require_once __DIR__ . '/config.php';
        self::$db = getSafeNodeDatabase();
        
        if (!self::$db) {
            // Se não conseguir conectar, permite a requisição mas loga o erro
            error_log("SafeNode: Não foi possível conectar ao banco de dados");
            return;
        }
        
        // Identificar site
        self::identifySite();
        
        // Verificar se o site está ativo
        if (!self::isSiteActive()) {
            return;
        }
        
        // Obter IP do cliente
        $ipAddress = self::getClientIP();
        
        // Carregar componentes
        require_once __DIR__ . '/IPBlocker.php';
        require_once __DIR__ . '/RateLimiter.php';
        require_once __DIR__ . '/ThreatDetector.php';
        require_once __DIR__ . '/SecurityLogger.php';
        require_once __DIR__ . '/CloudflareAPI.php';
        
        $ipBlocker = new IPBlocker(self::$db);
        $rateLimiter = new RateLimiter(self::$db);
        $threatDetector = new ThreatDetector(self::$db);
        $logger = new SecurityLogger(self::$db);
        
        // 1. Verificar whitelist
        if ($ipBlocker->isWhitelisted($ipAddress)) {
            $logger->log($ipAddress, $_SERVER['REQUEST_URI'] ?? '/', $_SERVER['REQUEST_METHOD'] ?? 'GET', 'allowed', null, 0, $_SERVER['HTTP_USER_AGENT'] ?? null, $_SERVER['HTTP_REFERER'] ?? null, self::$siteId);
            return; // Permite requisição
        }
        
        // 2. Verificar se IP está bloqueado
        if ($ipBlocker->isBlocked($ipAddress)) {
            $logger->log($ipAddress, $_SERVER['REQUEST_URI'] ?? '/', $_SERVER['REQUEST_METHOD'] ?? 'GET', 'blocked', 'ip_blocked', 100, $_SERVER['HTTP_USER_AGENT'] ?? null, $_SERVER['HTTP_REFERER'] ?? null, self::$siteId);
            self::blockRequest("IP bloqueado pelo SafeNode");
        }
        
        // 3. Verificar rate limit
        $rateLimitCheck = $rateLimiter->checkRateLimit($ipAddress);
        if (!$rateLimitCheck['allowed']) {
            // Bloquear por rate limit
            $ipBlocker->blockIP($ipAddress, "Rate limit excedido", 'rate_limit', 3600);
            $logger->log($ipAddress, $_SERVER['REQUEST_URI'] ?? '/', $_SERVER['REQUEST_METHOD'] ?? 'GET', 'blocked', 'rate_limit', 60, $_SERVER['HTTP_USER_AGENT'] ?? null, $_SERVER['HTTP_REFERER'] ?? null, self::$siteId);
            self::blockRequest("Muitas requisições. Tente novamente mais tarde.");
        }
        
        // 4. Analisar requisição para ameaças
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $headers = self::getHeaders();
        $body = file_get_contents('php://input');
        
        $threatAnalysis = $threatDetector->analyzeRequest($requestUri, $requestMethod, $headers, $body);
        
        // 5. Verificar brute force
        if (stripos($requestUri, 'login') !== false || stripos($requestUri, 'auth') !== false) {
            if ($threatDetector->detectBruteForce($ipAddress, $requestUri)) {
                $threatAnalysis['is_threat'] = true;
                $threatAnalysis['threat_type'] = 'brute_force';
                $threatAnalysis['threat_score'] = 80;
            }
        }
        
        // 6. Verificar DDoS
        if ($threatDetector->detectDDoS($ipAddress)) {
            $threatAnalysis['is_threat'] = true;
            $threatAnalysis['threat_type'] = 'ddos';
            $threatAnalysis['threat_score'] = 90;
        }
        
        // 7. Processar resultado
        if ($threatAnalysis['is_threat']) {
            // Bloquear IP
            $blockDuration = $threatAnalysis['threat_score'] >= 80 ? 86400 : 3600; // 24h ou 1h
            $ipBlocker->blockIP($ipAddress, "Ameaça detectada: " . $threatAnalysis['threat_type'], $threatAnalysis['threat_type'], $blockDuration);
            
            // Enviar para Cloudflare se configurado
            self::sendToCloudflare($ipAddress, $threatAnalysis['threat_type']);
            
            // Registrar log
            $logger->log($ipAddress, $requestUri, $requestMethod, 'blocked', $threatAnalysis['threat_type'], $threatAnalysis['threat_score'], $_SERVER['HTTP_USER_AGENT'] ?? null, $_SERVER['HTTP_REFERER'] ?? null, self::$siteId);
            
            // Bloquear requisição
            self::blockRequest("Acesso negado por segurança");
        } else {
            // Permitir e registrar
            $responseTime = round((microtime(true) - self::$startTime) * 1000, 2);
            $logger->log($ipAddress, $requestUri, $requestMethod, 'allowed', null, 0, $_SERVER['HTTP_USER_AGENT'] ?? null, $_SERVER['HTTP_REFERER'] ?? null, self::$siteId, $responseTime);
        }
    }
    
    /**
     * Identifica o site baseado no domínio
     */
    private static function identifySite() {
        $domain = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';
        $domain = preg_replace('/^www\./', '', $domain);
        
        try {
            $stmt = self::$db->prepare("SELECT id, domain FROM safenode_sites WHERE domain = ? AND is_active = 1");
            $stmt->execute([$domain]);
            $site = $stmt->fetch();
            
            if ($site) {
                self::$siteId = $site['id'];
                self::$siteDomain = $site['domain'];
            }
        } catch (PDOException $e) {
            error_log("SafeNode Site Identification Error: " . $e->getMessage());
        }
    }
    
    /**
     * Verifica se o site está ativo
     */
    private static function isSiteActive() {
        if (!self::$siteId) return false;
        
        try {
            $stmt = self::$db->prepare("SELECT is_active FROM safenode_sites WHERE id = ?");
            $stmt->execute([self::$siteId]);
            $site = $stmt->fetch();
            
            return $site && $site['is_active'] == 1;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Obtém IP real do cliente
     */
    private static function getClientIP() {
        $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Obtém headers da requisição
     */
    private static function getHeaders() {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $headers[] = $key . ': ' . $value;
            }
        }
        return $headers;
    }
    
    /**
     * Bloqueia a requisição
     */
    private static function blockRequest($message = "Acesso negado") {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode([
            'error' => true,
            'message' => $message,
            'code' => 'SAFENODE_BLOCKED'
        ]);
        exit;
    }
    
    /**
     * Envia regra para Cloudflare
     */
    private static function sendToCloudflare($ipAddress, $threatType) {
        if (!self::$siteId) return;
        
        try {
            $stmt = self::$db->prepare("SELECT cloudflare_zone_id FROM safenode_sites WHERE id = ? AND cloudflare_zone_id IS NOT NULL");
            $stmt->execute([self::$siteId]);
            $site = $stmt->fetch();
            
            if ($site && $site['cloudflare_zone_id']) {
                $cloudflare = new CloudflareAPI();
                $cloudflare->createFirewallRule(
                    $site['cloudflare_zone_id'],
                    $ipAddress,
                    'block',
                    "SafeNode Auto-Block: $threatType"
                );
            }
        } catch (Exception $e) {
            error_log("SafeNode Cloudflare Integration Error: " . $e->getMessage());
        }
    }
}

