<?php
/**
 * SafeNode - Router e Proteção de URLs
 * Sistema de rotas protegidas com tokens de sessão
 */

class SafeNodeRouter {
    private static $routes = [];
    private static $initialized = false;
    
    /**
     * Inicializa o sistema de rotas
     */
    public static function init() {
        if (self::$initialized) return;
        
        // Verificar se está logado (sem iniciar sessão se já estiver ativa)
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $isLoggedIn = isset($_SESSION['safenode_logged_in']) && $_SESSION['safenode_logged_in'] === true;
        
        if (!$isLoggedIn) {
            // Se não estiver logado, redireciona para login
            if (!strpos($_SERVER['REQUEST_URI'], 'login.php') && 
                !strpos($_SERVER['REQUEST_URI'], 'register.php') &&
                !strpos($_SERVER['REQUEST_URI'], 'verify-otp.php') &&
                !strpos($_SERVER['REQUEST_URI'], 'index.php')) {
                header('Location: login.php');
                exit;
            }
            return;
        }
        
        // Gerar token de sessão se não existir
        if (!isset($_SESSION['safenode_url_token'])) {
            $_SESSION['safenode_url_token'] = self::generateToken();
        }
        
        // Definir rotas protegidas
        self::$routes = [
            'dashboard' => 'dashboard.php',
            'sites' => 'sites.php',
            'logs' => 'logs.php',
            'blocked' => 'blocked.php',
            'settings' => 'settings.php'
        ];
        
        self::$initialized = true;
    }
    
    /**
     * Gera token único para proteção de URL
     */
    private static function generateToken() {
        return bin2hex(random_bytes(16)) . time();
    }
    
    /**
     * Valida token da URL
     */
    public static function validateToken($token) {
        if (!isset($_SESSION['safenode_url_token'])) {
            return false;
        }
        return hash_equals($_SESSION['safenode_url_token'], $token);
    }
    
    /**
     * Gera URL protegida
     */
    public static function url($route, $params = []) {
        self::init();
        
        if (!isset(self::$routes[$route])) {
            // Se não for rota protegida, retornar arquivo direto
            return $route . '.php';
        }
        
        // Verificar se está logado
        if (!isset($_SESSION['safenode_logged_in']) || $_SESSION['safenode_logged_in'] !== true) {
            return $route . '.php';
        }
        
        $token = $_SESSION['safenode_url_token'] ?? self::generateToken();
        if (!isset($_SESSION['safenode_url_token'])) {
            $_SESSION['safenode_url_token'] = $token;
        }
        
        $tokenHash = substr(md5($token . $route . session_id()), 0, 8);
        $timestamp = time();
        
        // Gerar ID único baseado em hash
        $uniqueId = substr(md5($token . $route . $timestamp . session_id()), 0, 12);
        
        // URL formatada: safenode-[hash]-[id]-[timestamp]
        $protectedUrl = "safenode-{$tokenHash}-{$uniqueId}-{$timestamp}";
        
        // Armazenar mapeamento na sessão
        if (!isset($_SESSION['safenode_url_map'])) {
            $_SESSION['safenode_url_map'] = [];
        }
        
        // Limpar mapeamentos expirados (manter apenas últimos 50)
        if (isset($_SESSION['safenode_url_map']) && count($_SESSION['safenode_url_map']) > 50) {
            $expired = array_filter($_SESSION['safenode_url_map'], function($map) {
                return isset($map['expires']) && $map['expires'] < time();
            });
            foreach (array_keys($expired) as $key) {
                unset($_SESSION['safenode_url_map'][$key]);
            }
        }
        
        $_SESSION['safenode_url_map'][$protectedUrl] = [
            'file' => self::$routes[$route],
            'params' => $params,
            'expires' => time() + 3600, // Expira em 1 hora
            'session_id' => session_id() // Validar sessão
        ];
        
        return $protectedUrl;
    }
    
    /**
     * Processa requisição e redireciona para arquivo correto
     */
    public static function dispatch() {
        self::init();
        
        $requestUri = $_SERVER['REQUEST_URI'];
        $path = parse_url($requestUri, PHP_URL_PATH);
        $path = ltrim($path, '/');
        
        // Se for uma rota protegida
        if (preg_match('/^safenode-([a-f0-9]{8})-([a-f0-9]{12})-(\d+)$/', $path, $matches)) {
            $tokenHash = $matches[1];
            $uniqueId = $matches[2];
            $timestamp = $matches[3];
            $fullPath = $path;
            
            // Verificar se existe na sessão
            if (isset($_SESSION['safenode_url_map'][$fullPath])) {
                $mapping = $_SESSION['safenode_url_map'][$fullPath];
                
                // Verificar expiração
                if ($mapping['expires'] < time()) {
                    unset($_SESSION['safenode_url_map'][$fullPath]);
                    header('Location: login.php');
                    exit;
                }
                
                // Limpar parâmetros GET da URL original
                $file = $mapping['file'];
                $params = $mapping['params'];
                
                // Adicionar parâmetros à URL se houver
                if (!empty($params)) {
                    $queryString = http_build_query($params);
                    $file .= '?' . $queryString;
                }
                
                // Incluir arquivo
                if (file_exists(__DIR__ . '/../' . $mapping['file'])) {
                    // Limpar output buffer
                    ob_clean();
                    require_once __DIR__ . '/../' . $mapping['file'];
                    exit;
                }
            }
        }
        
        // Se não for rota protegida e estiver logado, permitir acesso direto
        // (para compatibilidade durante transição)
        return false;
    }
    
    /**
     * Obtém token atual da sessão
     */
    public static function getToken() {
        self::init();
        return $_SESSION['safenode_url_token'] ?? '';
    }
}

