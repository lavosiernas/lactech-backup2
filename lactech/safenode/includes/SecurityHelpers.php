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
        
        if (empty($_SESSION[self::$tokenName])) {
            return false;
        }
        
        // Verificar se o token expirou (2 horas)
        $tokenTime = $_SESSION[self::$tokenTime] ?? 0;
        if ((time() - $tokenTime) > 7200) {
            self::clearToken();
            return false;
        }
        
        return hash_equals($_SESSION[self::$tokenName], $token);
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
        // Mínimo 8 caracteres, pelo menos 1 letra, 1 número
        return strlen($password) >= 8 
            && preg_match('/[a-zA-Z]/', $password) === 1 
            && preg_match('/[0-9]/', $password) === 1;
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
               "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.tailwindcss.com http://fonts.googleapis.com; " .
               "style-src-elem 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.tailwindcss.com http://fonts.googleapis.com; " .
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

