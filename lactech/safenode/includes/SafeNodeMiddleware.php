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
    private static $securityLevel = 'medium';
    private static $visitorCountry = null;
    private static $geoAllowOnly = false;
    private static $securityLevel = 'medium';
    
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
        
        self::loadSiteSecurityLevel();
        
        // Obter IP do cliente
        $ipAddress = self::getClientIP();
        
        // Carregar componentes
        require_once __DIR__ . '/IPBlocker.php';
        require_once __DIR__ . '/RateLimiter.php';
        require_once __DIR__ . '/ThreatDetector.php';
        require_once __DIR__ . '/SecurityLogger.php';
        require_once __DIR__ . '/CloudflareAPI.php';
        require_once __DIR__ . '/Settings.php';
        require_once __DIR__ . '/BrowserIntegrity.php'; // Novo componente
        
        $ipBlocker = new IPBlocker(self::$db);
        $rateLimiter = new RateLimiter(self::$db);
        $threatDetector = new ThreatDetector(self::$db);
        $logger = new SecurityLogger(self::$db);
        $browserIntegrity = new BrowserIntegrity(self::$db);
        
        // 0. Security Headers (Blindagem do Cliente)
        self::sendSecurityHeaders();
        
        // 1. Verificar whitelist
        if ($ipBlocker->isWhitelisted($ipAddress)) {
            $logger->log($ipAddress, $_SERVER['REQUEST_URI'] ?? '/', $_SERVER['REQUEST_METHOD'] ?? 'GET', 'allowed', null, 0, $_SERVER['HTTP_USER_AGENT'] ?? null, $_SERVER['HTTP_REFERER'] ?? null, self::$siteId, null, self::$visitorCountry);
            return; // Permite requisição
        }
        
        self::$visitorCountry = self::detectCountryCode($ipAddress);
        if (self::$visitorCountry) {
            self::enforceGeoBlocking(self::$visitorCountry, $ipAddress);
        }

        $fwResult = self::applyFirewallRules($ipAddress);
        if ($fwResult === 'blocked') {
            // applyFirewallRules já chamou blockRequest
            return;
        }
        
        // 2. Verificar se IP está bloqueado
        if ($ipBlocker->isBlocked($ipAddress)) {
            $logger->log($ipAddress, $_SERVER['REQUEST_URI'] ?? '/', $_SERVER['REQUEST_METHOD'] ?? 'GET', 'blocked', 'ip_blocked', 100, $_SERVER['HTTP_USER_AGENT'] ?? null, $_SERVER['HTTP_REFERER'] ?? null, self::$siteId, null, self::$visitorCountry);
            self::blockRequest("IP bloqueado pelo SafeNode", 'ip_blocked');
        }

        // 3. BROWSER INTEGRITY CHECK (Estilo Cloudflare)
        // Verifica se o navegador é legítimo antes de processar qualquer outra coisa pesada
        // Isso evita que bots de scraping consumam recursos do servidor
        $browserIntegrity->check(self::$securityLevel === 'under_attack');
        
        // 4. Verificar rate limit
        $rateLimitCheck = $rateLimiter->checkRateLimit($ipAddress);
        if (!$rateLimitCheck['allowed']) {
            // Bloquear por rate limit
            $ipBlocker->blockIP($ipAddress, "Rate limit excedido", 'rate_limit', 3600);
            $logger->log($ipAddress, $_SERVER['REQUEST_URI'] ?? '/', $_SERVER['REQUEST_METHOD'] ?? 'GET', 'blocked', 'rate_limit', 60, $_SERVER['HTTP_USER_AGENT'] ?? null, $_SERVER['HTTP_REFERER'] ?? null, self::$siteId, null, self::$visitorCountry);
            self::blockRequest("Muitas requisições. Tente novamente mais tarde.", 'rate_limit');
        }
        
        // 5. Honeypots de rota: URLs isca comuns
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $lowerUri   = strtolower(parse_url($requestUri, PHP_URL_PATH) ?? '/');
        $honeypotPaths = [
            '/wp-admin', '/wp-login.php', '/xmlrpc.php',
            '/phpmyadmin', '/phpinfo.php', '/admin.php', '/cpanel'
        ];
        foreach ($honeypotPaths as $hp) {
            if (strpos($lowerUri, $hp) === 0) {
                $ipBlocker->blockIP($ipAddress, "Acesso a rota honeypot ($hp)", 'honeypot', 86400);
                $logger->log($ipAddress, $requestUri, $_SERVER['REQUEST_METHOD'] ?? 'GET', 'blocked', 'honeypot', 95, $_SERVER['HTTP_USER_AGENT'] ?? null, $_SERVER['HTTP_REFERER'] ?? null, self::$siteId, null, self::$visitorCountry);
                self::blockRequest("Acesso negado por segurança (rota protegida).", 'honeypot');
            }
        }
        
        // 6. Analisar requisição para ameaças
        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $headers = self::getHeaders();
        $body = file_get_contents('php://input');
        
        $threatAnalysis = $threatDetector->analyzeRequest($requestUri, $requestMethod, $headers, $body);
        
        // 7. Verificar brute force
        if (stripos($requestUri, 'login') !== false || stripos($requestUri, 'auth') !== false) {
            if ($threatDetector->detectBruteForce($ipAddress, $requestUri)) {
                $threatAnalysis['is_threat'] = true;
                $threatAnalysis['threat_type'] = 'brute_force';
                $threatAnalysis['threat_score'] = 80;
            }
        }
        
        // 8. Verificar DDoS
        if ($threatDetector->detectDDoS($ipAddress)) {
            $threatAnalysis['is_threat'] = true;
            $threatAnalysis['threat_type'] = 'ddos';
            $threatAnalysis['threat_score'] = 90;
        }
        
        // 9. Processar resultado com sensibilidade por site
        // Ajustar thresholds conforme nível
        $blockThreshold = 70;
        $criticalThreshold = 85;
        switch (self::$securityLevel) {
            case 'low':
                $blockThreshold = 80;
                $criticalThreshold = 95;
                break;
            case 'medium':
                $blockThreshold = 70;
                $criticalThreshold = 85;
                break;
            case 'high':
                $blockThreshold = 60;
                $criticalThreshold = 80;
                break;
            case 'under_attack':
                $blockThreshold = 40;
                $criticalThreshold = 70;
                break;
        }

        // 10. Processar resultado
        if ($threatAnalysis['is_threat'] && $threatAnalysis['threat_score'] >= $blockThreshold) {
            // Bloquear IP com duração proporcional à gravidade e ao nível de segurança
            $blockDuration = $threatAnalysis['threat_score'] >= $criticalThreshold ? 86400 : 3600; // 24h ou 1h
            $ipBlocker->blockIP($ipAddress, "Ameaça detectada: " . $threatAnalysis['threat_type'], $threatAnalysis['threat_type'], $blockDuration);
            
            // Enviar para Cloudflare se configurado
            self::sendToCloudflare($ipAddress, $threatAnalysis['threat_type']);
            
            // Registrar log
            $logger->log($ipAddress, $requestUri, $requestMethod, 'blocked', $threatAnalysis['threat_type'], $threatAnalysis['threat_score'], $_SERVER['HTTP_USER_AGENT'] ?? null, $_SERVER['HTTP_REFERER'] ?? null, self::$siteId, null, self::$visitorCountry);
            
            // Bloquear requisição
            self::blockRequest("Acesso negado por segurança", $threatAnalysis['threat_type']);
        } else {
            // Permitir e registrar
            $responseTime = round((microtime(true) - self::$startTime) * 1000, 2);
            $logger->log($ipAddress, $requestUri, $requestMethod, 'allowed', null, 0, $_SERVER['HTTP_USER_AGENT'] ?? null, $_SERVER['HTTP_REFERER'] ?? null, self::$siteId, $responseTime, self::$visitorCountry);
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
     * Envia headers de segurança HTTP
     */
    private static function sendSecurityHeaders() {
        // HSTS - Força HTTPS por 1 ano
        header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
        
        // Proteção contra Clickjacking
        header("X-Frame-Options: SAMEORIGIN");
        
        // Proteção contra MIME Sniffing
        header("X-Content-Type-Options: nosniff");
        
        // Proteção XSS do navegador
        header("X-XSS-Protection: 1; mode=block");
        
        // Referrer Policy (Privacidade)
        header("Referrer-Policy: strict-origin-when-cross-origin");
        
        // Content Security Policy (Básico - permite scripts do próprio domínio e CDNs comuns)
        // Nota: Isso pode ser ajustado se quebrar scripts do site
        // header("Content-Security-Policy: default-src 'self' https: 'unsafe-inline' 'unsafe-eval'; img-src 'self' https: data:;");
    }

    /**
     * Bloqueia a requisição
     */
    private static function blockRequest($message = "Acesso negado", $reason = 'blocked') {
        http_response_code(403);
        
        $blockMessage = $message;
        $blockReason = $reason;
        $siteDomain = self::$siteDomain ?? ($_SERVER['HTTP_HOST'] ?? 'SafeNode');
        $ipAddress = self::getClientIP();
        $rayId = strtoupper(substr(bin2hex(random_bytes(8)), 0, 16));
        
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        if (stripos($accept, 'text/html') !== false) {
            require __DIR__ . '/../blocked_page.php';
            exit;
        }
        
        header('Content-Type: application/json');
        echo json_encode([
            'error' => true,
            'message' => $message,
            'reason' => $reason,
            'code' => 'SAFENODE_BLOCKED',
            'ray_id' => $rayId
        ]);
        exit;
    }
    
    private static function loadSiteSecurityLevel() {
        self::$securityLevel = 'medium';
        self::$geoAllowOnly = false;
        if (!self::$siteId) {
            return;
        }
        try {
            $stmt = self::$db->prepare("SELECT security_level, geo_allow_only FROM safenode_sites WHERE id = ?");
            $stmt->execute([self::$siteId]);
            $row = $stmt->fetch();
            if ($row && !empty($row['security_level'])) {
                self::$securityLevel = $row['security_level'];
                self::$geoAllowOnly = !empty($row['geo_allow_only']);
            }
        } catch (PDOException $e) {
            self::$securityLevel = 'medium';
            self::$geoAllowOnly = false;
        }
    }
    
    private static function detectCountryCode($ipAddress) {
        $headerKeys = [
            'HTTP_CF_IPCOUNTRY',
            'HTTP_X_COUNTRY_CODE',
            'HTTP_GEOIP_COUNTRY_CODE',
            'GEOIP_COUNTRY_CODE'
        ];
        foreach ($headerKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $code = strtoupper(substr(trim($_SERVER[$key]), 0, 2));
                if (preg_match('/^[A-Z]{2}$/', $code)) {
                    $_SERVER['SAFENODE_COUNTRY_CODE'] = $code;
                    return $code;
                }
            }
        }
        return null;
    }
    
    private static function enforceGeoBlocking($countryCode, $ipAddress) {
        if (!self::$siteId || !self::$db) {
            return;
        }
        $rule = null;
        try {
            if ($countryCode) {
                $stmt = self::$db->prepare("SELECT action FROM safenode_site_geo_rules WHERE site_id = ? AND country_code = ?");
                $stmt->execute([self::$siteId, $countryCode]);
                $rule = $stmt->fetch();
            }
            if ($rule && $rule['action'] === 'block') {
                self::blockRequest("Acesso indisponível em sua região ({$countryCode})", 'geo_block');
            }
            if ($rule && $rule['action'] === 'allow') {
                return;
            }
            if (self::$geoAllowOnly) {
                self::blockRequest("Acesso restrito aos países autorizados", 'geo_allow_only');
            }
        } catch (PDOException $e) {
            error_log("SafeNode GeoBlocking Error: " . $e->getMessage());
        }
    }

    private static function applyFirewallRules($ipAddress) {
        if (!self::$siteId || !self::$db) {
            return 'none';
        }
        try {
            $stmt = self::$db->prepare("SELECT * FROM safenode_firewall_rules WHERE site_id = ? AND is_active = 1 ORDER BY priority DESC, id ASC");
            $stmt->execute([self::$siteId]);
            $rules = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("SafeNode Firewall Rules Error: " . $e->getMessage());
            return 'none';
        }

        if (!$rules) {
            return 'none';
        }

        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $country = self::$visitorCountry;

        foreach ($rules as $rule) {
            $match = false;
            $value = $rule['match_value'];
            switch ($rule['match_type']) {
                case 'path_prefix':
                    if ($value !== '' && strpos($path, $value) === 0) {
                        $match = true;
                    }
                    break;
                case 'ip':
                    if ($value !== '' && $ipAddress === $value) {
                        $match = true;
                    }
                    break;
                case 'country':
                    if ($value !== '' && $country && strtoupper($country) === strtoupper($value)) {
                        $match = true;
                    }
                    break;
                case 'user_agent':
                    if ($value !== '' && stripos($ua, $value) !== false) {
                        $match = true;
                    }
                    break;
            }

            if (!$match) {
                continue;
            }

            $action = $rule['action'] ?? 'block';
            if ($action === 'block') {
                self::blockRequest("Acesso negado por regra personalizada", 'fw_block');
                return 'blocked';
            }
            if ($action === 'allow') {
                return 'allow';
            }
            if ($action === 'log') {
                try {
                    $logger = new SecurityLogger(self::$db);
                    $logger->log(
                        $ipAddress,
                        $_SERVER['REQUEST_URI'] ?? '/',
                        $_SERVER['REQUEST_METHOD'] ?? 'GET',
                        'logged',
                        'fw_log',
                        10,
                        $_SERVER['HTTP_USER_AGENT'] ?? null,
                        $_SERVER['HTTP_REFERER'] ?? null,
                        self::$siteId,
                        null,
                        self::$visitorCountry
                    );
                } catch (\Throwable $e) {
                    // ignore
                }
                return 'log';
            }
        }

        return 'none';
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
