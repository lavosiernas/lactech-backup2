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
        
        // Carregar componentes core
        require_once __DIR__ . '/IPBlocker.php';
        require_once __DIR__ . '/Settings.php';
        require_once __DIR__ . '/HumanVerification.php';
        require_once __DIR__ . '/PerformanceMonitor.php';
        require_once __DIR__ . '/AlertManager.php';
        
        // Inicializar monitor de performance
        $performanceMonitor = new PerformanceMonitor(self::$db, self::$siteId);
        $performanceMonitor->start($_SERVER['REQUEST_URI'] ?? '/', $_SERVER['REQUEST_METHOD'] ?? 'GET');
        
        // Registrar para salvar performance no final da requisição
        register_shutdown_function(function() use ($performanceMonitor) {
            $performanceMonitor->end();
        });
        
        $ipBlocker = new IPBlocker(self::$db);
        
        // 0. Security Headers
        self::sendSecurityHeaders();
        
        // 1. Verificar whitelist (IPBlocker)
        if ($ipBlocker->isWhitelisted($ipAddress)) {
            self::logHumanVerification($ipAddress, 'allowed', 'whitelisted');
            return; // Permite requisição
        }
        
        self::$visitorCountry = self::detectCountryCode($ipAddress);
        
        // 2. Verificar se IP está bloqueado
        if ($ipBlocker->isBlocked($ipAddress)) {
            self::logHumanVerification($ipAddress, 'blocked', 'ip_blocked');
            
            // Verificar se deve criar alerta para IP bloqueado recorrente
            if (self::$siteId) {
                try {
                    $stmt = self::$db->prepare("
                        SELECT COUNT(*) as block_count 
                        FROM safenode_human_verification_logs 
                        WHERE site_id = ? 
                        AND ip_address = ? 
                        AND event_type = 'bot_blocked' 
                        AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
                    ");
                    $stmt->execute([self::$siteId, $ipAddress]);
                    $result = $stmt->fetch();
                    $blockCount = (int)($result['block_count'] ?? 0);
                    
                    // Alertar se IP bloqueado 5+ vezes na última hora
                    if ($blockCount >= 5) {
                        $alertManager = new AlertManager(self::$db);
                        $alertManager->createAlert(
                            self::$siteId,
                            AlertManager::TYPE_SUSPICIOUS_IP,
                            AlertManager::SEVERITY_HIGH,
                            "IP bloqueado recorrente",
                            "IP {$ipAddress} foi bloqueado {$blockCount} vezes na última hora",
                            ['ip' => $ipAddress, 'attempt_count' => $blockCount]
                        );
                    }
                } catch (PDOException $e) {
                    // Ignorar erro de alerta
                }
            }
            
            self::blockRequest("IP bloqueado pelo SafeNode", 'ip_blocked');
        }
        
        // 3. Verificar regras de firewall
        $fwResult = self::applyFirewallRules($ipAddress);
        if ($fwResult === 'blocked') {
            return;
        }
        
        // 4. Honeypots básicos: URLs isca comuns
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $lowerUri = strtolower(parse_url($requestUri, PHP_URL_PATH) ?? '/');
        $honeypotPaths = [
            '/wp-admin', '/wp-login.php', '/xmlrpc.php',
            '/phpmyadmin', '/phpinfo.php', '/admin.php', '/cpanel'
        ];
        foreach ($honeypotPaths as $hp) {
            if (strpos($lowerUri, $hp) === 0) {
                $ipBlocker->blockIP($ipAddress, "Acesso a rota honeypot ($hp)", 'honeypot', 86400);
                self::logHumanVerification($ipAddress, 'blocked', 'honeypot');
                
                // Criar alerta para acesso a honeypot
                if (self::$siteId) {
                    $alertManager = new AlertManager(self::$db);
                    $alertManager->createAlert(
                        self::$siteId,
                        AlertManager::TYPE_CRITICAL_THREAT,
                        AlertManager::SEVERITY_HIGH,
                        "Tentativa de acesso a rota protegida",
                        "IP {$ipAddress} tentou acessar rota honeypot: {$hp}",
                        ['ip' => $ipAddress, 'endpoint' => $hp, 'type' => 'honeypot']
                    );
                }
                
                self::blockRequest("Acesso negado por segurança (rota protegida).", 'honeypot');
            }
        }
        
        // 5. Verificação Humana - Verificar se precisa de desafio
        // Ignorar se for a própria página de desafio
        if (strpos($lowerUri, '/challenge-page.php') === false && 
            strpos($lowerUri, 'challenge-page.php') === false) {
            
            // Verificar se IP precisa de desafio
            if (self::needsHumanChallenge($ipAddress)) {
                // Verificar se já passou no desafio (sessão)
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                
                $challengeVerified = $_SESSION['safenode_challenge_verified'] ?? false;
                $challengeVerifiedIP = $_SESSION['safenode_challenge_verified_ip'] ?? '';
                $challengeVerifiedTime = $_SESSION['safenode_challenge_verified_time'] ?? 0;
                
                // Verificar se verificação ainda é válida (válida por 1 hora)
                $isValid = $challengeVerified && 
                          $challengeVerifiedIP === $ipAddress && 
                          (time() - $challengeVerifiedTime) < 3600;
                
                if (!$isValid) {
                    // Mostrar página de desafio
                    self::logHumanVerification($ipAddress, 'challenged', 'human_challenge');
                    self::showChallengePage();
                    return;
                }
            }
        }
        
        // 6. Verificação Humana - Registrar acesso permitido
        self::logHumanVerification($ipAddress, 'allowed', 'human_verified');
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
                // Registrar log de firewall
                self::logHumanVerification($ipAddress, 'allowed', 'fw_log');
                return 'log';
            }
        }

        return 'none';
    }
    
    /**
     * Verifica se um IP precisa de desafio humano
     */
    private static function needsHumanChallenge($ipAddress) {
        if (!self::$db || !self::$siteId) return false;
        
        try {
            // Verificar se IP tem histórico de falhas recentes (últimas 24h)
            $stmt = self::$db->prepare("
                SELECT COUNT(*) as fail_count 
                FROM safenode_human_verification_logs 
                WHERE site_id = ? 
                AND ip_address = ? 
                AND event_type = 'bot_blocked' 
                AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");
            $stmt->execute([self::$siteId, $ipAddress]);
            $result = $stmt->fetch();
            
            // Se teve 2+ falhas nas últimas 24h, precisa de desafio
            if ($result && (int)$result['fail_count'] >= 2) {
                return true;
            }
            
            // Verificar se IP nunca passou por desafio (primeira visita suspeita)
            $stmt = self::$db->prepare("
                SELECT COUNT(*) as total_visits 
                FROM safenode_human_verification_logs 
                WHERE site_id = ? 
                AND ip_address = ? 
                AND event_type IN ('human_validated', 'access_allowed')
            ");
            $stmt->execute([self::$siteId, $ipAddress]);
            $result = $stmt->fetch();
            
            // Se nunca visitou antes, mostrar desafio (modo conservador)
            // Pode ser ajustado para ser menos agressivo
            if ($result && (int)$result['total_visits'] === 0) {
                // Apenas 30% das primeiras visitas precisam de desafio (para não irritar usuários legítimos)
                return (rand(1, 100) <= 30);
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("SafeNode Challenge Check Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mostra página de desafio
     */
    private static function showChallengePage() {
        // Salvar URL original na sessão
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $_SESSION['safenode_challenge_original_url'] = $_SERVER['REQUEST_URI'] ?? '/';
        
        // Redirecionar para página de desafio
        $challengeUrl = __DIR__ . '/../challenge-page.php';
        if (file_exists($challengeUrl)) {
            require $challengeUrl;
            exit;
        } else {
            // Fallback: bloquear se página não existir
            self::blockRequest("Verificação de segurança necessária", 'challenge_required');
        }
    }
    
    /**
     * Registra evento de verificação humana no banco de dados
     */
    private static function logHumanVerification($ipAddress, $action, $reason) {
        if (!self::$db || !self::$siteId) return;
        
        try {
            $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
            $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            $referer = $_SERVER['HTTP_REFERER'] ?? null;
            
            // Mapear action para event_type
            $eventType = 'access_allowed';
            if ($action === 'blocked') {
                $eventType = 'bot_blocked';
            } elseif ($action === 'challenged') {
                $eventType = 'challenge_shown';
            } elseif ($action === 'allowed' && $reason === 'human_verified') {
                $eventType = 'human_validated';
            }
            
            $stmt = self::$db->prepare("
                INSERT INTO safenode_human_verification_logs 
                (site_id, ip_address, event_type, request_uri, request_method, user_agent, referer, country_code, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                self::$siteId,
                $ipAddress,
                $eventType,
                $requestUri,
                $requestMethod,
                $userAgent,
                $referer,
                self::$visitorCountry
            ]);
        } catch (PDOException $e) {
            error_log("SafeNode Human Verification Log Error: " . $e->getMessage());
        }
    }
}
