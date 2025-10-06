<?php
// =====================================================
// CONFIGURAÇÃO MYSQL - LAGOA DO MATO
// =====================================================
// Configurações específicas para MySQL/PHPMyAdmin
// =====================================================

// Configurações do banco de dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'lactech_lagoa_mato');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Configurações da aplicação
define('APP_NAME', 'LacTech - Lagoa do Mato');
define('APP_VERSION', '2.0.0');
define('FARM_NAME', 'Lagoa do Mato');

// URLs do sistema
define('BASE_URL', 'http://localhost/lactechsys/');
define('LOGIN_URL', 'login.php');
define('DASHBOARD_URL', 'gerente.php');

// Configurações de sessão
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Mude para 1 em produção com HTTPS

// Iniciar sessão se não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configurações de erro (desabilitar em produção)
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

// Função para obter URL base
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $path = dirname($_SERVER['SCRIPT_NAME']);
    
    return $protocol . '://' . $host . $path . '/';
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

// Função para log de erro
function logError($message, $context = []) {
    $logMessage = date('Y-m-d H:i:s') . " - $message";
    
    if (!empty($context)) {
        $logMessage .= " - Context: " . json_encode($context);
    }
    
    error_log($logMessage);
}

// Função para verificar se é requisição AJAX
function isAjax() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

// Função para retornar resposta JSON
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Função para formatar data
function formatDate($date, $format = 'd/m/Y') {
    if (empty($date)) return '';
    
    $timestamp = is_string($date) ? strtotime($date) : $date;
    return date($format, $timestamp);
}

// Função para formatar valor monetário
function formatCurrency($value, $currency = 'R$') {
    if (empty($value)) return $currency . ' 0,00';
    
    return $currency . ' ' . number_format($value, 2, ',', '.');
}

// Função para formatar volume
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
