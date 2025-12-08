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
        
        // Obter IP do cliente (funciona com Cloudflare Proxy)
        $ipAddress = self::getClientIP();
        
        // Registrar requisição mesmo se proxy estiver ativo
        // Isso garante que temos logs próprios
        
        // Carregar componentes
        require_once __DIR__ . '/IPBlocker.php';
        require_once __DIR__ . '/RateLimiter.php';
        require_once __DIR__ . '/ThreatDetector.php';
        require_once __DIR__ . '/SecurityLogger.php';
        require_once __DIR__ . '/CloudflareAPI.php';
        require_once __DIR__ . '/Settings.php';
        require_once __DIR__ . '/BrowserIntegrity.php';
        require_once __DIR__ . '/IPReputationManager.php'; // Sistema de reputação próprio
        require_once __DIR__ . '/BehaviorAnalyzer.php'; // Análise comportamental
        require_once __DIR__ . '/LogQueue.php'; // Fila de logs assíncrona
        require_once __DIR__ . '/AdvancedHoneypot.php'; // Honeypots avançados
        require_once __DIR__ . '/QuarantineSystem.php'; // Sistema de quarentena
        require_once __DIR__ . '/AlertSystem.php'; // Sistema de alertas
        require_once __DIR__ . '/AdvancedWAF.php'; // WAF Avançado
        
        $ipBlocker = new IPBlocker(self::$db);
        $advancedWAF = new AdvancedWAF(self::$db); // WAF Avançado
        $rateLimiter = new RateLimiter(self::$db);
        $threatDetector = new ThreatDetector(self::$db);
        $logger = new SecurityLogger(self::$db);
        $browserIntegrity = new BrowserIntegrity(self::$db);
        $ipReputation = new IPReputationManager(self::$db); // Gerenciador de reputação
        $behaviorAnalyzer = new BehaviorAnalyzer(self::$db); // Analisador comportamental
        $logQueue = new LogQueue(self::$db); // Fila de logs assíncrona
        $honeypot = new AdvancedHoneypot(self::$db); // Honeypots avançados
        $quarantine = new QuarantineSystem(self::$db); // Sistema de quarentena
        $alertSystem = new AlertSystem(self::$db); // Sistema de alertas
        
        // 0. Security Headers (Blindagem do Cliente)
        self::sendSecurityHeaders();
        
        // 1. Verificar whitelist (IPBlocker)
        if ($ipBlocker->isWhitelisted($ipAddress)) {
            $logger->log($ipAddress, $_SERVER['REQUEST_URI'] ?? '/', $_SERVER['REQUEST_METHOD'] ?? 'GET', 'allowed', null, 0, $_SERVER['HTTP_USER_AGENT'] ?? null, $_SERVER['HTTP_REFERER'] ?? null, self::$siteId, null, self::$visitorCountry);
            return; // Permite requisição
        }
        
        self::$visitorCountry = self::detectCountryCode($ipAddress);
        
        // 1.1 Verificar reputação própria (SISTEMA INDEPENDENTE)
        if ($ipReputation->isWhitelisted($ipAddress)) {
            $logger->log($ipAddress, $_SERVER['REQUEST_URI'] ?? '/', $_SERVER['REQUEST_METHOD'] ?? 'GET', 'allowed', null, 0, $_SERVER['HTTP_USER_AGENT'] ?? null, $_SERVER['HTTP_REFERER'] ?? null, self::$siteId, null, self::$visitorCountry);
            $ipReputation->updateReputation($ipAddress, 'allowed', 0, null, self::$visitorCountry);
            return; // Permite requisição
        }
        
        if ($ipReputation->isBlacklisted($ipAddress)) {
            $logger->log($ipAddress, $_SERVER['REQUEST_URI'] ?? '/', $_SERVER['REQUEST_METHOD'] ?? 'GET', 'blocked', 'ip_reputation_blacklist', 100, $_SERVER['HTTP_USER_AGENT'] ?? null, $_SERVER['HTTP_REFERER'] ?? null, self::$siteId, null, self::$visitorCountry);
            self::blockRequest("IP bloqueado por reputação", 'ip_reputation_blacklist');
        }
        
        // Verificar trust_score baixo (ajustar threshold baseado no nível de segurança)
        $trustScore = $ipReputation->getTrustScore($ipAddress);
        $trustThreshold = self::$securityLevel === 'under_attack' ? 40 : (self::$securityLevel === 'high' ? 30 : 20);
        if ($trustScore < $trustThreshold && $trustScore > 0) {
            // IP com baixa reputação - aplicar challenge ou rate limit mais agressivo
            $rateLimitCheck = $rateLimiter->checkRateLimit($ipAddress, true); // Modo agressivo
            if (!$rateLimitCheck['allowed']) {
                $ipBlocker->blockIP($ipAddress, "Baixa reputação + rate limit", 'low_reputation', 1800);
                $logger->log($ipAddress, $_SERVER['REQUEST_URI'] ?? '/', $_SERVER['REQUEST_METHOD'] ?? 'GET', 'blocked', 'low_reputation', 50, $_SERVER['HTTP_USER_AGENT'] ?? null, $_SERVER['HTTP_REFERER'] ?? null, self::$siteId, null, self::$visitorCountry);
                $ipReputation->updateReputation($ipAddress, 'blocked', 50, 'low_reputation', self::$visitorCountry);
                self::blockRequest("Acesso negado por segurança", 'low_reputation');
            }
        }
        
        if (self::$visitorCountry) {
            self::enforceGeoBlocking(self::$visitorCountry, $ipAddress);
        }

        $fwResult = self::applyFirewallRules($ipAddress);
        if ($fwResult === 'blocked') {
            // applyFirewallRules já chamou blockRequest
            return;
        }
        
        // 1.5. Verificar regras WAF avançadas (ANTES de verificar IP bloqueado)
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $wafResult = $advancedWAF->evaluate(
            $ipAddress,
            $requestUri,
            $requestMethod,
            getallheaders(),
            file_get_contents('php://input')
        );
        
        if ($wafResult['matched']) {
            $wafSeverity = $wafResult['severity'] ?? 50;
            
            if ($wafResult['action'] === 'block' || $wafSeverity >= 70) {
                $ipBlocker->blockIP($ipAddress, $wafResult['message'], 'waf_rule', 3600);
                $logger->log($ipAddress, $requestUri, $requestMethod, 'blocked', 'waf_rule', $wafSeverity, $_SERVER['HTTP_USER_AGENT'] ?? null, $_SERVER['HTTP_REFERER'] ?? null, self::$siteId, null, self::$visitorCountry);
                self::blockRequest($wafResult['message'], 'waf_rule');
            } elseif ($wafResult['action'] === 'challenge') {
                require_once __DIR__ . '/DynamicChallenge.php';
                $challenge = new DynamicChallenge(self::$db);
                $challengeData = $challenge->generateChallenge($wafSeverity);
                $logger->log($ipAddress, $requestUri, $requestMethod, 'challenged', 'waf_rule', $wafSeverity, $_SERVER['HTTP_USER_AGENT'] ?? null, $_SERVER['HTTP_REFERER'] ?? null, self::$siteId, null, self::$visitorCountry);
                self::blockRequest("Verificação de segurança necessária", 'waf_challenge');
            } else {
                $logger->log($ipAddress, $requestUri, $requestMethod, 'allowed', 'waf_rule', $wafSeverity, $_SERVER['HTTP_USER_AGENT'] ?? null, $_SERVER['HTTP_REFERER'] ?? null, self::$siteId, null, self::$visitorCountry);
            }
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
        
        // 5. Honeypots avançados: verificar acesso a honeypots dinâmicos
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $honeypotAccess = $honeypot->checkHoneypotAccess($requestUri, $ipAddress);
        if ($honeypotAccess) {
            // Bot detectado via honeypot - bloquear imediatamente
            $ipBlocker->blockIP($ipAddress, "Bot detectado via honeypot", 'honeypot_bot', 86400);
            $logger->log($ipAddress, $requestUri, $_SERVER['REQUEST_METHOD'] ?? 'GET', 'blocked', 'honeypot_bot', 100, $_SERVER['HTTP_USER_AGENT'] ?? null, $_SERVER['HTTP_REFERER'] ?? null, self::$siteId, null, self::$visitorCountry);
            self::blockRequest("Acesso negado por segurança.", 'honeypot_bot');
        }
        
        // 5.1 Honeypots de rota: URLs isca comuns (mantido para compatibilidade)
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
        
        // 6. Análise Comportamental (SISTEMA PRÓPRIO)
        $behaviorAnalysis = $behaviorAnalyzer->analyzeIPBehavior($ipAddress, 3600);
        if ($behaviorAnalysis['risk_level'] === 'critical' || $behaviorAnalysis['risk_level'] === 'high') {
            // Comportamento suspeito detectado - aumentar sensibilidade
            $behaviorRiskScore = $behaviorAnalysis['risk_score'] ?? 0;
            if ($behaviorRiskScore >= 70) {
                // Comportamento muito suspeito - bloquear diretamente
                $ipBlocker->blockIP($ipAddress, "Comportamento suspeito detectado", 'suspicious_behavior', 7200);
                $logger->log($ipAddress, $requestUri, $_SERVER['REQUEST_METHOD'] ?? 'GET', 'blocked', 'suspicious_behavior', $behaviorRiskScore, $_SERVER['HTTP_USER_AGENT'] ?? null, $_SERVER['HTTP_REFERER'] ?? null, self::$siteId, null, self::$visitorCountry);
                $ipReputation->updateReputation($ipAddress, 'blocked', $behaviorRiskScore, 'suspicious_behavior', self::$visitorCountry);
                self::blockRequest("Acesso negado por comportamento suspeito", 'suspicious_behavior');
            }
        }
        
        // 7. Analisar requisição para ameaças
        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $headers = self::getHeaders();
        $body = file_get_contents('php://input');
        
        $threatAnalysis = $threatDetector->analyzeRequest($requestUri, $requestMethod, $headers, $body);
        
        // Ajustar threat_score baseado em análise comportamental
        if ($behaviorAnalysis['risk_score'] > 50) {
            $threatAnalysis['threat_score'] = min(100, $threatAnalysis['threat_score'] + ($behaviorAnalysis['risk_score'] * 0.2));
        }
        
        // 8. Verificar brute force
        if (stripos($requestUri, 'login') !== false || stripos($requestUri, 'auth') !== false) {
            if ($threatDetector->detectBruteForce($ipAddress, $requestUri)) {
                $threatAnalysis['is_threat'] = true;
                $threatAnalysis['threat_type'] = 'brute_force';
                $threatAnalysis['threat_score'] = max(80, $threatAnalysis['threat_score']);
            }
        }
        
        // 9. Verificar DDoS
        if ($threatDetector->detectDDoS($ipAddress)) {
            $threatAnalysis['is_threat'] = true;
            $threatAnalysis['threat_type'] = 'ddos';
            $threatAnalysis['threat_score'] = max(90, $threatAnalysis['threat_score']);
        }
        
        // 10. Processar resultado com sensibilidade por site (com confidence score)
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
        
        // Processar resultado (com confidence score)
        $confidenceScore = $threatAnalysis['confidence_score'] ?? 0;
        $threatScore = $threatAnalysis['threat_score'] ?? 0;
        
        // Ajustar threshold baseado em confidence (se confidence baixo, precisa de score maior)
        $adjustedThreshold = $blockThreshold;
        if ($confidenceScore < 50) {
            $adjustedThreshold += 10; // Aumenta threshold se confidence baixo
        } elseif ($confidenceScore >= 80) {
            $adjustedThreshold -= 10; // Reduz threshold se confidence alto
        }
        $adjustedThreshold = max(40, min(90, $adjustedThreshold)); // Limitar entre 40-90
        
        // Verificar se IP está em quarentena
        $quarantineData = $quarantine->isInQuarantine($ipAddress);
        if ($quarantineData) {
            // Processar requisição de IP em quarentena
            $quarantineResult = $quarantine->processQuarantinedRequest($ipAddress, [
                'request_uri' => $requestUri,
                'threat_score' => $threatScore,
                'threat_type' => $threatAnalysis['threat_type'] ?? null
            ]);
            
            if ($quarantineResult['action'] === 'block') {
                // Confirmado malicioso - já foi bloqueado pelo sistema de quarentena
                $logger->log($ipAddress, $requestUri, $requestMethod, 'blocked', $threatAnalysis['threat_type'] ?? null, $threatScore, $_SERVER['HTTP_USER_AGENT'] ?? null, $_SERVER['HTTP_REFERER'] ?? null, self::$siteId, null, self::$visitorCountry, $confidenceScore);
                self::blockRequest("Acesso negado por segurança", $threatAnalysis['threat_type'] ?? 'unknown');
            } elseif ($quarantineResult['action'] === 'challenge') {
                // Aplicar challenge baseado no nível
                require_once __DIR__ . '/DynamicChallenge.php';
                $challenge = new DynamicChallenge(self::$db);
                $challengeData = $challenge->generateChallenge($quarantineResult['challenge_level']);
                // Challenge será aplicado na resposta (implementar no blockRequest ou retornar challenge)
                $logger->log($ipAddress, $requestUri, $requestMethod, 'challenged', $threatAnalysis['threat_type'] ?? null, $threatScore, $_SERVER['HTTP_USER_AGENT'] ?? null, $_SERVER['HTTP_REFERER'] ?? null, self::$siteId, null, self::$visitorCountry, $confidenceScore);
                // Por enquanto, bloquear com mensagem de challenge
                self::blockRequest("Verificação de segurança necessária", 'quarantine_challenge');
            }
            // Se action === 'allow', continuar normalmente
        }
        
        if ($threatAnalysis['is_threat'] && $threatScore >= $adjustedThreshold) {
            // Se threat_score está em faixa intermediária (50-70), colocar em quarentena ao invés de bloquear
            if ($threatScore >= 50 && $threatScore < 70 && !$quarantineData) {
                // Colocar em quarentena
                $quarantine->addToQuarantine(
                    $ipAddress,
                    "Ameaça suspeita detectada",
                    $threatScore,
                    $threatAnalysis['threat_type'] ?? null,
                    3600 // 1 hora
                );
                
                // Enviar alerta
                $alertSystem->sendAlert('suspicious_behavior', [
                    'ip_address' => $ipAddress,
                    'threat_score' => $threatScore,
                    'threat_type' => $threatAnalysis['threat_type'] ?? 'unknown',
                    'site_id' => self::$siteId
                ], 3); // Severidade média
                
                $logger->log($ipAddress, $requestUri, $requestMethod, 'challenged', $threatAnalysis['threat_type'] ?? null, $threatScore, $_SERVER['HTTP_USER_AGENT'] ?? null, $_SERVER['HTTP_REFERER'] ?? null, self::$siteId, null, self::$visitorCountry, $confidenceScore);
                self::blockRequest("Verificação de segurança necessária", 'quarantine');
            } else {
                // Bloquear IP com duração proporcional à gravidade e ao nível de segurança
                $blockDuration = $threatScore >= $criticalThreshold ? 86400 : 3600; // 24h ou 1h
                $ipBlocker->blockIP($ipAddress, "Ameaça detectada: " . ($threatAnalysis['threat_type'] ?? 'unknown'), $threatAnalysis['threat_type'] ?? 'unknown', $blockDuration);
                
                // Enviar alerta crítico
                $alertSystem->sendAlert('threat_detected', [
                    'ip_address' => $ipAddress,
                    'threat_score' => $threatScore,
                    'threat_type' => $threatAnalysis['threat_type'] ?? 'unknown',
                    'site_id' => self::$siteId,
                    'action' => 'blocked'
                ], 5); // Severidade crítica
                
                // Enviar para Cloudflare se configurado (OPCIONAL - não bloqueia se falhar)
                self::sendToCloudflare($ipAddress, $threatAnalysis['threat_type'] ?? 'unknown');
                
                // Registrar log SÍNCRONO (bloqueios devem ser logados imediatamente)
                $logger->log($ipAddress, $requestUri, $requestMethod, 'blocked', $threatAnalysis['threat_type'] ?? null, $threatScore, $_SERVER['HTTP_USER_AGENT'] ?? null, $_SERVER['HTTP_REFERER'] ?? null, self::$siteId, null, self::$visitorCountry, $confidenceScore);
                
                // Atualizar reputação (SISTEMA PRÓPRIO)
                $ipReputation->updateReputation($ipAddress, 'blocked', $threatScore, $threatAnalysis['threat_type'] ?? null, self::$visitorCountry);
                
                // Bloquear requisição
                self::blockRequest("Acesso negado por segurança", $threatAnalysis['threat_type'] ?? 'unknown');
            }
        } else {
            // Permitir e registrar ASSÍNCRONO (não bloqueia a requisição)
            $responseTime = round((microtime(true) - self::$startTime) * 1000, 2);
            $actionTaken = $threatAnalysis['is_threat'] && $threatScore >= 30 ? 'challenged' : 'allowed';
            
            // Adicionar à fila assíncrona (muito mais rápido)
            $logQueue->enqueue([
                'ip_address' => $ipAddress,
                'request_uri' => $requestUri,
                'request_method' => $requestMethod,
                'action_taken' => $actionTaken,
                'threat_type' => $threatAnalysis['threat_type'] ?? null,
                'threat_score' => $threatScore,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'referer' => $_SERVER['HTTP_REFERER'] ?? null,
                'site_id' => self::$siteId,
                'response_time' => $responseTime,
                'country_code' => self::$visitorCountry,
                'confidence_score' => $confidenceScore
            ]);
            
            // Atualizar reputação (SISTEMA PRÓPRIO) - pode ser assíncrono também no futuro
            $ipReputation->updateReputation($ipAddress, $actionTaken, $threatScore, $threatAnalysis['threat_type'] ?? null, self::$visitorCountry);
        }
    }
    
    /**
     * Identifica o site baseado no domínio
     * Funciona mesmo com Cloudflare Proxy (usa headers do Cloudflare)
     */
    private static function identifySite() {
        // Quando Cloudflare Proxy está ativo, usar header CF-Connecting-IP e Host
        $domain = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';
        
        // Se Cloudflare está ativo, pode vir no header CF-Host
        if (isset($_SERVER['HTTP_CF_HOST'])) {
            $domain = $_SERVER['HTTP_CF_HOST'];
        }
        
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
