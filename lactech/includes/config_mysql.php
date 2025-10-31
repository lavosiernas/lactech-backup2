<?php
// =====================================================
// DETECÇÃO AUTOMÁTICA DE AMBIENTE (LOCAL OU PRODUÇÃO)
// =====================================================

// Detectar se está em localhost
$isLocal = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1', '::1']) ||
           strpos($_SERVER['SERVER_NAME'] ?? '', '192.168.') === 0 ||
           strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false;

// Detectar URL base automaticamente
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $path = str_replace('\\', '/', dirname($script));
    
    // Remover index.php ou qualquer arquivo do final
    $path = rtrim($path, '/') . '/';
    
    return $protocol . '://' . $host . $path;
}

// =====================================================
// CONFIGURAÇÕES DO BANCO DE DADOS
// =====================================================

if ($isLocal) {
    // AMBIENTE LOCAL (XAMPP/WAMP)
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'lactech_lgmato'); // Banco local (conforme dump .sql)
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('BASE_URL', getBaseUrl()); // Detecta automaticamente
    ini_set('session.cookie_secure', 0); // HTTP local
    define('ENVIRONMENT', 'LOCAL');
} else {
    // AMBIENTE DE PRODUÇÃO (HOSPEDAGEM)
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'u311882628_lactech_lgmato'); // Banco hospedagem
    define('DB_USER', 'u311882628_xandriaAgro');
    define('DB_PASS', 'Lavosier0012!');
    define('BASE_URL', 'https://lactechsys.com/');
    ini_set('session.cookie_secure', 1); // HTTPS em produção
    define('ENVIRONMENT', 'PRODUCTION');
}

define('DB_CHARSET', 'utf8mb4');
define('APP_NAME', 'LacTech - Lagoa do Mato');
define('APP_VERSION', '2.0.0');
define('FARM_NAME', 'Lagoa do Mato');
define('LOGIN_URL', 'inicio-login.php');
define('DASHBOARD_URL', 'gerente-completo.php');
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1); 

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configurações de erro (sempre ocultar em endpoints para não quebrar JSON)
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Timezone
date_default_timezone_set('America/Sao_Paulo');

// Configurações de upload
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']);

// Configurações de relatórios
define('REPORT_COMPANY_NAME', 'Lagoa do Mato');
define('REPORT_COMPANY_ADDRESS', 'São Paulo, SP');
define('REPORT_COMPANY_PHONE', '(11) 99999-9999');

// Configurações de backup
define('BACKUP_ENABLED', true);
define('BACKUP_PATH', '../backups/');

// Configurações de cache
define('CACHE_ENABLED', true);
define('CACHE_TIME', 3600); // 1 hora

// Configurações de segurança
define('PASSWORD_MIN_LENGTH', 6);
define('SESSION_TIMEOUT', 3600); // 1 hora

// Configurações específicas da fazenda
define('DAILY_MILKING_SHIFTS', ['manha', 'tarde', 'noite']);
// Configurações específicas da fazenda
define('USER_ROLES', ['proprietario', 'gerente', 'funcionario']);
define('ANIMAL_BREEDS', ['Holandesa', 'Gir', 'Girolanda', 'Jersey', 'Pardo Suíço', 'Simental', 'Outras']);
define('ANIMAL_STATUS', ['Lactante', 'Seco', 'Novilha', 'Vaca', 'Bezerra', 'Bezerro']);
define('HEALTH_STATUS', ['saudavel', 'doente', 'tratamento', 'quarentena']);
define('TREATMENT_TYPES', ['Medicamento', 'Vacinação', 'Vermifugação', 'Suplementação', 'Cirurgia', 'Outros']);
define('FINANCIAL_TYPES', ['receita', 'despesa']);
define('PAYMENT_METHODS', ['dinheiro', 'cartao', 'transferencia', 'cheque', 'pix']);

// Função para obter configuração
function getConfig($key, $default = null) {
    return defined($key) ? constant($key) : $default;
}

// Função para verificar se está em desenvolvimento
function isDevelopment() {
    return $_SERVER['SERVER_NAME'] === 'localhost' || 
           strpos($_SERVER['SERVER_NAME'], '127.0.0.1') !== false ||
           strpos($_SERVER['SERVER_NAME'], '192.168.') !== false;
}

// Função para redirecionar
function redirect($url) {
    header("Location: $url");
    exit;
}

// Função para sanitizar entrada
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Função para validar email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Função para gerar token
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

function logError($message, $context = []) {
    $logMessage = date('Y-m-d H:i:s') . " - $message";
    
    if (!empty($context)) {
        $logMessage .= " - Context: " . json_encode($context);
    }
    
    error_log($logMessage);
}

function isAjax() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function formatDate($date, $format = 'd/m/Y') {
    if (empty($date)) return '';
    
    $timestamp = is_string($date) ? strtotime($date) : $date;
    return date($format, $timestamp);
}

function formatCurrency($value, $currency = 'R$') {
    if (empty($value)) return $currency . ' 0,00';
    
    return $currency . ' ' . number_format($value, 2, ',', '.');
}

function formatVolume($volume) {
    if (empty($volume)) return '0,00 L';
    
    return number_format($volume, 2, ',', '.') . ' L';
}

// Configurações de notificação
function setNotification($message, $type = 'info') {
    $_SESSION['notification'] = [
        'message' => $message,
        'type' => $type
    ];
}

function getNotification() {
    if (isset($_SESSION['notification'])) {
        $notification = $_SESSION['notification'];
        unset($_SESSION['notification']);
        return $notification;
    }
    return null;
}

function setSuccessNotification($message) {
    setNotification($message, 'success');
}

function setErrorNotification($message) {
    setNotification($message, 'error');
}

function setWarningNotification($message) {
    setNotification($message, 'warning');
}

function setInfoNotification($message) {
    setNotification($message, 'info');
}
?>
