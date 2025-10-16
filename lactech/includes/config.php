<?php
/**
 * SISTEMA DE CONFIGURAÇÃO UNIFICADO - LACTECH
 * Configuração inteligente para Local e Produção
 * Versão: 2.0.0
 */

// =====================================================
// DETECÇÃO AUTOMÁTICA DE AMBIENTE
// =====================================================

class EnvironmentDetector {
    public static function detect() {
        $serverName = $_SERVER['SERVER_NAME'] ?? '';
        $httpHost = $_SERVER['HTTP_HOST'] ?? '';
        
        // Lista de indicadores de ambiente local
        $localIndicators = [
            'localhost',
            '127.0.0.1',
            '::1',
            '192.168.',
            '10.0.',
            '172.16.',
            '172.17.',
            '172.18.',
            '172.19.',
            '172.20.',
            '172.21.',
            '172.22.',
            '172.23.',
            '172.24.',
            '172.25.',
            '172.26.',
            '172.27.',
            '172.28.',
            '172.29.',
            '172.30.',
            '172.31.'
        ];
        
        foreach ($localIndicators as $indicator) {
            if (strpos($serverName, $indicator) === 0 || strpos($httpHost, $indicator) === 0) {
                return 'local';
            }
        }
        
        return 'production';
    }
    
    public static function getEnvironment() {
        return self::detect();
    }
}

// =====================================================
// CONFIGURAÇÕES POR AMBIENTE
// =====================================================

class DatabaseConfig {
    private static $configs = [
        'local' => [
            'host' => 'localhost',
            'database' => 'lactech_lgmato',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8mb4',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ]
        ],
        'production' => [
            'host' => 'localhost',
            'database' => 'u311882628_lactech_lgmato',
            'username' => 'u311882628_xandriaAgro',
            'password' => 'Lavosier0012!',
            'charset' => 'utf8mb4',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ]
        ]
    ];
    
    public static function getConfig($environment = null) {
        if ($environment === null) {
            $environment = EnvironmentDetector::getEnvironment();
        }
        
        if (!isset(self::$configs[$environment])) {
            throw new Exception("Ambiente '{$environment}' não configurado");
        }
        
        return self::$configs[$environment];
    }
    
    public static function getCurrentEnvironment() {
        return EnvironmentDetector::getEnvironment();
    }
}

// =====================================================
// CONFIGURAÇÕES GERAIS DA APLICAÇÃO
// =====================================================

class AppConfig {
    public static function getBaseUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        $path = str_replace('\\', '/', dirname($script));
        $path = rtrim($path, '/') . '/';
        
        return $protocol . '://' . $host . $path;
    }
    
    public static function isSecure() {
        $environment = EnvironmentDetector::getEnvironment();
        return $environment === 'production';
    }
    
    public static function isDebugMode() {
        $environment = EnvironmentDetector::getEnvironment();
        return $environment === 'local';
    }
}

// =====================================================
// CONSTANTES GLOBAIS
// =====================================================

// Ambiente
define('ENVIRONMENT', EnvironmentDetector::getEnvironment());
define('IS_LOCAL', ENVIRONMENT === 'local');
define('IS_PRODUCTION', ENVIRONMENT === 'production');

// Banco de dados
$dbConfig = DatabaseConfig::getConfig();
define('DB_HOST', $dbConfig['host']);
define('DB_NAME', $dbConfig['database']);
define('DB_USER', $dbConfig['username']);
define('DB_PASS', $dbConfig['password']);
define('DB_CHARSET', $dbConfig['charset']);

// URLs
define('BASE_URL', AppConfig::getBaseUrl());
define('LOGIN_URL', 'login.php');
define('DASHBOARD_URL', 'gerente.php');

// Configurações da aplicação
define('APP_NAME', 'LacTech - Lagoa do Mato');
define('APP_VERSION', '2.0.0');
define('FARM_NAME', 'Lagoa do Mato');
define('FARM_ID', 1);

// Configurações de sessão
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', AppConfig::isSecure() ? 1 : 0);

// Configurações de erro
if (AppConfig::isDebugMode()) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Timezone
date_default_timezone_set('America/Sao_Paulo');

// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// =====================================================
// FUNÇÕES UTILITÁRIAS
// =====================================================

function getEnvironment() {
    return EnvironmentDetector::getEnvironment();
}

function isLocal() {
    return IS_LOCAL;
}

function isProduction() {
    return IS_PRODUCTION;
}

function getBaseUrl() {
    return BASE_URL;
}

function logError($message, $context = []) {
    $logMessage = date('Y-m-d H:i:s') . " - $message";
    
    if (!empty($context)) {
        $logMessage .= " - Context: " . json_encode($context);
    }
    
    error_log($logMessage);
}

function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// =====================================================
// CONFIGURAÇÕES ESPECÍFICAS DA FAZENDA
// =====================================================

define('USER_ROLES', ['proprietario', 'gerente', 'funcionario', 'veterinario']);
define('ANIMAL_BREEDS', ['Holandesa', 'Gir', 'Girolanda', 'Jersey', 'Pardo Suíço', 'Simental', 'Outras']);
define('ANIMAL_STATUS', ['Lactante', 'Seco', 'Novilha', 'Vaca', 'Bezerra', 'Bezerro']);
define('HEALTH_STATUS', ['saudavel', 'doente', 'tratamento', 'quarentena']);
define('TREATMENT_TYPES', ['Medicamento', 'Vacinação', 'Vermifugação', 'Suplementação', 'Cirurgia', 'Outros']);
define('FINANCIAL_TYPES', ['receita', 'despesa']);
define('PAYMENT_METHODS', ['dinheiro', 'cartao', 'transferencia', 'cheque', 'pix']);

// =====================================================
// LOG DE INICIALIZAÇÃO
// =====================================================

if (AppConfig::isDebugMode()) {
    error_log("LacTech System iniciado - Ambiente: " . ENVIRONMENT . " - Base URL: " . BASE_URL);
}
?>
