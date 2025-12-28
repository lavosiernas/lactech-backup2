<?php
/**
 * SafeNode - Security Helpers
 * Classes para proteção contra CSRF, XSS e outras vulnerabilidades
 */

/**
 * CSRF Protection
 * Protege contra Cross-Site Request Forgery
 */
class CSRFProtection {
    private static $tokenName = 'safenode_csrf_token';
    private static $tokenTime = 'safenode_csrf_time';
    
    /**
     * Gerar token CSRF e armazenar na sessão
     */
    public static function generateToken(): string {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $token = bin2hex(random_bytes(32));
        $_SESSION[self::$tokenName] = $token;
        $_SESSION[self::$tokenTime] = time();
        
        return $token;
    }
    
    /**
     * Obter token CSRF atual (ou gerar novo se não existir)
     */
    public static function getToken(): string {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (empty($_SESSION[self::$tokenName])) {
            return self::generateToken();
        }
        
        return $_SESSION[self::$tokenName];
    }
    
    /**
     * Validar token CSRF
     */
    public static function validateToken(string $token): bool {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (empty($token)) {
            error_log("CSRFProtection: Token vazio recebido");
            return false;
        }
        
        if (empty($_SESSION[self::$tokenName])) {
            error_log("CSRFProtection: Token não existe na sessão");
            return false;
        }
        
        // Verificar se o token expirou (2 horas)
        $tokenTime = $_SESSION[self::$tokenTime] ?? 0;
        if ((time() - $tokenTime) > 7200) {
            error_log("CSRFProtection: Token expirado");
            self::clearToken();
            return false;
        }
        
        $isValid = hash_equals($_SESSION[self::$tokenName], $token);
        if (!$isValid) {
            error_log("CSRFProtection: Token não corresponde");
        }
        
        return $isValid;
    }
    
    /**
     * Gerar campo hidden de token para formulário
     */
    public static function getTokenField(): string {
        $token = self::getToken();
        return '<input type="hidden" name="' . self::$tokenName . '" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
    
    /**
     * Limpar token da sessão
     */
    public static function clearToken(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        unset($_SESSION[self::$tokenName]);
        unset($_SESSION[self::$tokenTime]);
    }
    
    /**
     * Verificar e validar token de POST
     */
    public static function validate(): bool {
        $token = $_POST[self::$tokenName] ?? '';
        return self::validateToken($token);
    }
}

/**
 * XSS Protection
 * Protege contra Cross-Site Scripting
 */
class XSSProtection {
    /**
     * Escapar string para output HTML seguro
     */
    public static function escape(?string $string): string {
        if ($string === null) {
            return '';
        }
        return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    /**
     * Escapar para uso em atributos HTML
     */
    public static function escapeAttr(?string $string): string {
        if ($string === null) {
            return '';
        }
        return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    /**
     * Escapar para uso em JavaScript
     */
    public static function escapeJS(?string $string): string {
        if ($string === null) {
            return '';
        }
        return json_encode($string, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }
    
    /**
     * Escapar para uso em URLs
     */
    public static function escapeURL(?string $string): string {
        if ($string === null) {
            return '';
        }
        return urlencode($string);
    }
    
    /**
     * Sanitizar string removendo tags HTML
     */
    public static function sanitize(?string $string): string {
        if ($string === null) {
            return '';
        }
        return strip_tags(trim($string));
    }
    
    /**
     * Sanitizar array recursivamente
     */
    public static function sanitizeArray(array $array): array {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = self::sanitizeArray($value);
            } else {
                $array[$key] = self::sanitize($value);
            }
        }
        return $array;
    }
}

/**
 * Input Validation
 * Validação robusta de inputs
 */
class InputValidator {
    /**
     * Validar email
     */
    public static function email(string $email): bool {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validar URL
     */
    public static function url(string $url): bool {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
    
    /**
     * Validar domínio
     */
    public static function domain(string $domain): bool {
        // Remove protocolo e www se houver
        $domain = preg_replace('/^https?:\/\//', '', $domain);
        $domain = preg_replace('/^www\./', '', $domain);
        $domain = rtrim($domain, '/');
        
        return preg_match('/^([a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,}$/', $domain) === 1;
    }
    
    /**
     * Validar string (tamanho mínimo e máximo)
     */
    public static function string(string $value, int $min = 1, int $max = 255): bool {
        $length = mb_strlen($value, 'UTF-8');
        return $length >= $min && $length <= $max;
    }
    
    /**
     * Validar número inteiro
     */
    public static function integer($value): bool {
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }
    
    /**
     * Validar número inteiro positivo
     */
    public static function positiveInteger($value): bool {
        return self::integer($value) && (int)$value > 0;
    }
    
    /**
     * Validar username (alfanumérico, underscore, hífen)
     */
    public static function username(string $username): bool {
        return preg_match('/^[a-zA-Z0-9_-]{3,30}$/', $username) === 1;
    }
    
    /**
     * Validar senha forte
     */
    public static function strongPassword(string $password): bool {
        // Mínimo 8 caracteres, com letras maiúsculas, minúsculas, números e caracteres especiais
        return strlen($password) >= 8 
            && preg_match('/[A-Z]/', $password) === 1  // Pelo menos 1 maiúscula
            && preg_match('/[a-z]/', $password) === 1  // Pelo menos 1 minúscula
            && preg_match('/[0-9]/', $password) === 1  // Pelo menos 1 número
            && preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password) === 1; // Pelo menos 1 caractere especial
    }
    
    /**
     * Verificar se email é temporário/descartável
     * Retorna true se for email temporário (bloquear), false se não for
     * SafeNode - Proteção rigorosa contra emails temporários
     */
    public static function isTemporaryEmail(string $email): bool {
        // Extrair domínio do email
        $parts = explode('@', strtolower(trim($email)));
        if (count($parts) !== 2) {
            return false; // Email inválido, será capturado pela validação de email
        }
        
        $domain = $parts[1];
        $fullDomain = $domain;
        
        // Extrair domínio base (sem subdomínios) para verificação mais ampla
        $domainParts = explode('.', $domain);
        $baseDomain = count($domainParts) >= 2 ? $domainParts[count($domainParts) - 2] . '.' . $domainParts[count($domainParts) - 1] : $domain;
        
        // Lista EXPANDIDA de domínios temporários conhecidos (CENTENAS de domínios)
        $temporaryDomains = [
            // Domínios principais mencionados
            'tmail.link', 'tmails.net', 'tmpmail.org', 'tmpmail.net', 'tmpmail.com',
            'tempmail.com', 'tempmail.org', 'tempmail.net', 'tempmail.io', 'tempmail.co',
            'tempmailo.com', 'tempmailer.com', 'tempmailer.de', 'tempmailer.ru',
            'temp-mail.org', 'temp-mail.io', 'temp-mail.ru', 'temp-mail.net', 'temp-mail.com',
            'tempail.com', 'tempr.email', 'tempmail.de', 'tempmail.pro',
            
            // Guerrilla Mail e variações
            'guerrillamail.com', 'guerrillamailblock.com', 'guerrillamail.org', 
            'guerrillamail.net', 'guerrillamail.biz', 'guerrillamail.info',
            'sharklasers.com', 'grr.la', 'pokemail.net', 'spam4.me',
            
            // 10MinuteMail e similares
            '10minutemail.com', '10minutemail.net', '10minutemail.org', '10minutemail.co.uk',
            '10minutemail.de', '10minutemail.es', '10minutemail.fr', '10minutemail.it',
            '20minutemail.com', '30minutemail.com', '60minutemail.com',
            
            // Mailinator
            'mailinator.com', 'mailinator.net', 'mailinator.org', 'mailinator.us',
            
            // YOPmail
            'yopmail.com', 'yopmail.fr', 'yopmail.net',
            
            // Mohmal
            'mohmal.com', 'mohmal.in', 'mohmal.im',
            
            // 1secmail
            '1secmail.com', '1secmail.org', '1secmail.net',
            
            // Trashmail
            'trashmail.com', 'trashmail.net', 'trashmail.org', 'trash-mail.com',
            'trashymail.com', 'throwaway.email', 'throwawaymail.com',
            
            // Dispostable
            'dispostable.com', 'disposablemail.com', 'disposable.com',
            
            // Maildrop
            'maildrop.cc', 'maildrop.io',
            
            // Outros serviços conhecidos
            'fakeinbox.com', 'fake-mail.com', 'fakeinbox.net', 'fakebox.eu',
            'emailondeck.com', 'emailondeck.net',
            'getnada.com', 'getairmail.com', 'getairmail.net',
            'mailnesia.com', 'mailnesia.net',
            'anonymbox.com', 'anonymbox.net',
            'fftube.com', // Email temporário detectado pelo usuário
            'meltmail.com', 'melt.li',
            'mox.do', 'mox.do',
            'mintemail.com',
            'mypacks.net', 'mypacks.info',
            'mintemail.com', 'mintemail.net',
            'mytrashmail.com',
            'nospam.ze.tc', 'now.im', 'now.mefound.com',
            'objectmail.com',
            'obobbo.com',
            'odaymail.com',
            'odnorazovoe.ru',
            'one-time.email',
            'onewaymail.com',
            'online.ms',
            'owlpic.com',
            'pancakemail.com',
            'pookmail.com',
            'proxymail.eu',
            'putthisinyourspamdatabase.com',
            'quickinbox.com',
            'rcpt.at',
            'recode.me',
            'recursor.net',
            'regbypass.com',
            'regbypass.comsafe-mail.net',
            'safetypost.de',
            'safetymail.info',
            'safetypost.de',
            'safetypost.de',
            'saynotospams.com',
            'selfdestructingmail.com',
            'sendspamhere.com',
            'sharklasers.com',
            'shiftmail.com',
            'shortmail.net',
            'sibmail.com',
            'sinnlos-mail.de',
            'slapsfromlastnight.com',
            'slaskpost.se',
            'smellfear.com',
            'smellrear.com',
            'snakemail.com',
            'sneakemail.com',
            'sofort-mail.de',
            'sogetthis.com',
            'soodonims.com',
            'spam.la',
            'spamavert.com',
            'spambob.com',
            'spambob.net',
            'spambob.org',
            'spambog.com',
            'spambog.de',
            'spambog.ru',
            'spambox.info',
            'spambox.us',
            'spamday.com',
            'spamex.com',
            'spamfree24.org',
            'spamfree24.de',
            'spamfree24.eu',
            'spamfree24.net',
            'spamfree24.com',
            'spamgourmet.com',
            'spamgourmet.net',
            'spamgourmet.org',
            'spamherelots.com',
            'spamhereplease.com',
            'spamhole.com',
            'spamify.com',
            'spamkill.info',
            'spaml.com',
            'spaml.de',
            'spammotel.com',
            'spamobox.com',
            'spamoff.de',
            'spamslicer.com',
            'spamspot.com',
            'spamthis.co.uk',
            'spamthisplease.com',
            'speed.1s.fr',
            'stuffmail.de',
            'super-auswahl.de',
            'supergreatmail.com',
            'supermailer.jp',
            'superrito.com',
            'tagyourself.com',
            'teewars.org',
            'teleworm.us',
            'tempalias.com',
            'tempe-mail.com',
            'tempinbox.co.uk',
            'tempinbox.com',
            'tempmail.it',
            'tempomail.fr',
            'temporaryemail.net',
            'temporaryemailaddress.com',
            'temporary-mail.net',
            'temporarymailaddress.com',
            'thanksnospam.info',
            'thankyou2010.com',
            'thisisnotmyrealemail.com',
            'throwawaymailaddress.com',
            'tilien.com',
            'tmail.ws',
            'tmailinator.com',
            'toiea.com',
            'tradermail.info',
            'trash-amil.com',
            'trash-mail.at',
            'trash-mail.com',
            'trash-mail.de',
            'trashemail.de',
            'trashymail.com',
            'turual.com',
            'twinmail.de',
            'tyldd.com',
            'uggsrock.com',
            'umail.net',
            'uroid.com',
            'us.af',
            'venompen.com',
            'veryrealemail.com',
            'viditag.com',
            'viewcastmedia.com',
            'viewcastmedia.net',
            'viewcastmedia.org',
            'webemail.me',
            'webm4il.info',
            'wh4f.org',
            'whyspam.me',
            'willselfdestruct.com',
            'winemaven.info',
            'wronghead.com',
            'wuzup.net',
            'wuzupmail.net',
            'xagloo.com',
            'xemaps.com',
            'xents.com',
            'xmaily.com',
            'xoxy.net',
            'yapped.net',
            'yeah.net',
            'yep.it',
            'yogamaven.com',
            'yopmail.com',
            'yopmail.fr',
            'yopmail.net',
            'youmailr.com',
            'ypmail.webnastya.ru',
            'zippymail.info',
            'zoemail.org',
            'zoemail.net',
            'cuvox.de', 'einrot.com', 'fleckens.hu', 'gustr.com', 'jourrapide.com',
            'rhyta.com', 'superrito.com', 'teleworm.us', 'dayrep.com', 'armyspy.com',
            'emailfake.com', 'emailfake.ml', 'fake-mail.net', 'fakemail.net',
            'mintemail.com', 'minuteinbox.com', 'mohmal.com', 'mytrashmail.com',
            'spambox.us', 'throwawaymail.com', 'trashmail.com', 'trashmail.net'
        ];
        
        // Verificação via APIs EXTERNAS PRIMEIRO (mais confiáveis)
        // Ordem: 1. DeBounce, 2. Disify, 3. throwaway.cloud
        // Se todas falharem, usa lista local como último fallback
        
        $apis = [
            [
                'name' => 'DeBounce',
                'url' => "https://disposable.debounce.io/?email=" . urlencode($email),
                'check' => function($response, $data) {
                    // DeBounce retorna "true" ou "false" como texto simples
                    return trim(strtolower($response)) === 'true';
                }
            ],
            [
                'name' => 'Disify',
                'url' => "https://www.disify.com/api/email/" . urlencode($email),
                'check' => function($response, $data) {
                    // Disify retorna JSON com campo "disposable"
                    return isset($data['disposable']) && $data['disposable'] === true;
                }
            ],
            [
                'name' => 'throwaway.cloud',
                'url' => "https://api.throwaway.cloud/v1/check?email=" . urlencode($email),
                'check' => function($response, $data) {
                    // throwaway.cloud retorna JSON
                    return isset($data['disposable']) && $data['disposable'] === true
                        || (isset($data['is_disposable']) && $data['is_disposable'] === true);
                }
            ]
        ];
        
        foreach ($apis as $api) {
            try {
                $context = stream_context_create([
                    'http' => [
                        'method' => 'GET',
                        'timeout' => 3, // Timeout curto para não bloquear
                        'ignore_errors' => true,
                        'user_agent' => 'SafeNode/1.0',
                        'header' => "Accept: application/json\r\n"
                    ]
                ]);
                
                $response = @file_get_contents($api['url'], false, $context);
                if ($response !== false) {
                    $data = json_decode($response, true);
                    if ($api['check']($response, $data ?: [])) {
                        error_log("SafeNode: Email temporário detectado (API {$api['name']}): $domain");
                        return true;
                    }
                }
            } catch (Exception $e) {
                // Se esta API falhar, continua para próxima
                error_log("SafeNode: Erro ao verificar via API {$api['name']}: " . $e->getMessage());
                continue;
            }
        }
        
        // ========== VERIFICAÇÃO 2: LISTA LOCAL (último fallback) ==========
        // Se todas as APIs falharam, usa a lista local como backup
        
        // Verificar domínio completo na lista
        if (in_array($fullDomain, $temporaryDomains)) {
            error_log("SafeNode: Email temporário detectado (lista local - completo): $fullDomain");
            return true;
        }
        
        // Verificar domínio base (sem subdomínios)
        if (in_array($baseDomain, $temporaryDomains)) {
            error_log("SafeNode: Email temporário detectado (lista local - base): $baseDomain");
            return true;
        }
        
        // Verificar se o domínio contém algum dos domínios temporários (para subdomínios)
        foreach ($temporaryDomains as $tempDomain) {
            if (strpos($fullDomain, $tempDomain) !== false || strpos($tempDomain, $fullDomain) !== false) {
                error_log("SafeNode: Email temporário detectado (subdomínio/match): $fullDomain contém $tempDomain");
                return true;
            }
        }
        
        // Verificação por palavras-chave suspeitas (detecção por padrões)
        $suspiciousPatterns = [
            'temp', 'tmp', 'fake', 'throw', 'disposable', 'trash', 'spam', 
            'nada', 'mohmal', 'guerrilla', 'minute', 'tmail', 'tempmail',
            'throwaway', 'trashmail', 'fakeinbox', 'mailinator', 'yopmail',
            '1sec', 'anonym', 'dispos', 'meltmail', 'mintemail', 'tube', 'ff'
        ];
        
        // Verificar se o domínio contém padrões suspeitos
        $domainLower = strtolower($domain);
        foreach ($suspiciousPatterns as $pattern) {
            if (strpos($domainLower, $pattern) !== false) {
                // Verificação adicional: alguns domínios legítimos podem conter essas palavras
                // Mas para segurança, vamos bloquear e o usuário pode usar outro email se for legítimo
                $knownLegitimate = ['template', 'temptations', 'temper', 'temporal', 'temporary-storage'];
                $isLegitimate = false;
                foreach ($knownLegitimate as $legit) {
                    if (strpos($domainLower, $legit) !== false) {
                        $isLegitimate = true;
                        break;
                    }
                }
                if (!$isLegitimate) {
                    error_log("SafeNode: Email temporário detectado (padrão suspeito): $domain contém '$pattern'");
                    return true;
                }
            }
        }
        
        // Se chegou até aqui, passou por todas as verificações
        return false; // Email não é temporário conhecido
    }
    
    /**
     * Validar email e verificar se não é temporário
     * Retorna true se email é válido E não é temporário
     */
    public static function emailNotTemporary(string $email): bool {
        // Primeiro validar formato do email
        if (!self::email($email)) {
            return false;
        }
        
        // Depois verificar se não é temporário
        return !self::isTemporaryEmail($email);
    }
}

/**
 * Security Headers
 * Headers HTTP de segurança
 */
class SecurityHeaders {
    /**
     * Aplicar todos os headers de segurança
     */
    public static function apply(): void {
        // Prevenir XSS
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('X-XSS-Protection: 1; mode=block');
        
        // Content Security Policy (básico) - mais permissivo para desenvolvimento
        $csp = "default-src 'self' https: http:; " .
               "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.tailwindcss.com https://unpkg.com https://cdn.jsdelivr.net https://accounts.google.com https://www.google.com http://localhost; " .
               "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.tailwindcss.com https://cdn.jsdelivr.net http://fonts.googleapis.com; " .
               "style-src-elem 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.tailwindcss.com https://cdn.jsdelivr.net http://fonts.googleapis.com; " .
               "font-src 'self' data: https://fonts.gstatic.com http://fonts.gstatic.com blob:; " .
               "img-src 'self' data: https: http: blob:; " .
               "connect-src 'self' https://accounts.google.com https://unpkg.com https://cdn.jsdelivr.net http://localhost https://api.qrserver.com;";
        header("Content-Security-Policy: $csp");
        
        // HSTS - Force HTTPS (apenas em produção)
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
        
        // Referrer Policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Permissions Policy
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    }
    
    /**
     * Aplicar headers apenas para APIs
     */
    public static function applyAPI(): void {
        header('Content-Type: application/json; charset=UTF-8');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
    }
}

/**
 * Function helpers globais
 */
if (!function_exists('h')) {
    /**
     * Helper para escape rápido
     */
    function h(?string $string): string {
        return XSSProtection::escape($string);
    }
}

if (!function_exists('csrf_field')) {
    /**
     * Helper para campo CSRF
     */
    function csrf_field(): string {
        return CSRFProtection::getTokenField();
    }
}

if (!function_exists('csrf_token')) {
    /**
     * Helper para obter token CSRF
     */
    function csrf_token(): string {
        return CSRFProtection::getToken();
    }
}



