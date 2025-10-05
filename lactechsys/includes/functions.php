<?php
require_once 'config.php';

/**
 * Redireciona para uma URL
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Formata data para exibição
 */
function formatDate($date, $format = 'd/m/Y') {
    if (empty($date)) return '';
    
    $timestamp = is_string($date) ? strtotime($date) : $date;
    return date($format, $timestamp);
}

/**
 * Formata data e hora para exibição
 */
function formatDateTime($datetime, $format = 'd/m/Y H:i') {
    if (empty($datetime)) return '';
    
    $timestamp = is_string($datetime) ? strtotime($datetime) : $datetime;
    return date($format, $timestamp);
}

/**
 * Formata valor monetário
 */
function formatCurrency($value, $currency = 'R$') {
    if (empty($value)) return $currency . ' 0,00';
    
    return $currency . ' ' . number_format($value, 2, ',', '.');
}

/**
 * Formata volume (litros)
 */
function formatVolume($volume) {
    if (empty($volume)) return '0,00 L';
    
    return number_format($volume, 2, ',', '.') . ' L';
}

/**
 * Sanitiza entrada de dados
 */
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Valida email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Gera hash de senha
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verifica senha
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Gera token aleatório
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Converte string para slug
 */
function slugify($text) {
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim($text, '-');
    return $text;
}

/**
 * Calcula diferença entre datas
 */
function dateDiff($date1, $date2) {
    $date1 = is_string($date1) ? new DateTime($date1) : $date1;
    $date2 = is_string($date2) ? new DateTime($date2) : $date2;
    
    return $date1->diff($date2);
}

/**
 * Adiciona dias a uma data
 */
function addDays($date, $days) {
    $date = is_string($date) ? new DateTime($date) : $date;
    $date->add(new DateInterval("P{$days}D"));
    return $date;
}

/**
 * Obtém início do dia
 */
function startOfDay($date = null) {
    $date = $date ? new DateTime($date) : new DateTime();
    $date->setTime(0, 0, 0);
    return $date;
}

/**
 * Obtém fim do dia
 */
function endOfDay($date = null) {
    $date = $date ? new DateTime($date) : new DateTime();
    $date->setTime(23, 59, 59);
    return $date;
}

/**
 * Obtém início da semana
 */
function startOfWeek($date = null) {
    $date = $date ? new DateTime($date) : new DateTime();
    $date->modify('monday this week');
    $date->setTime(0, 0, 0);
    return $date;
}

/**
 * Obtém fim da semana
 */
function endOfWeek($date = null) {
    $date = $date ? new DateTime($date) : new DateTime();
    $date->modify('sunday this week');
    $date->setTime(23, 59, 59);
    return $date;
}

/**
 * Obtém início do mês
 */
function startOfMonth($date = null) {
    $date = $date ? new DateTime($date) : new DateTime();
    $date->modify('first day of this month');
    $date->setTime(0, 0, 0);
    return $date;
}

/**
 * Obtém fim do mês
 */
function endOfMonth($date = null) {
    $date = $date ? new DateTime($date) : new DateTime();
    $date->modify('last day of this month');
    $date->setTime(23, 59, 59);
    return $date;
}

/**
 * Calcula média de array
 */
function arrayAverage($array) {
    if (empty($array)) return 0;
    return array_sum($array) / count($array);
}

/**
 * Calcula mediana de array
 */
function arrayMedian($array) {
    if (empty($array)) return 0;
    
    sort($array);
    $count = count($array);
    $middle = floor($count / 2);
    
    if ($count % 2 == 0) {
        return ($array[$middle - 1] + $array[$middle]) / 2;
    } else {
        return $array[$middle];
    }
}

/**
 * Obtém valor de array com fallback
 */
function arrayGet($array, $key, $default = null) {
    return isset($array[$key]) ? $array[$key] : $default;
}

/**
 * Converte bytes para formato legível
 */
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

/**
 * Log de erro personalizado
 */
function logError($message, $context = []) {
    $logMessage = date('Y-m-d H:i:s') . " - $message";
    
    if (!empty($context)) {
        $logMessage .= " - Context: " . json_encode($context);
    }
    
    error_log($logMessage);
}

/**
 * Verifica se é requisição AJAX
 */
function isAjax() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

/**
 * Retorna resposta JSON
 */
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Obtém IP do cliente
 */
function getClientIP() {
    $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    
    foreach ($ipKeys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                    return $ip;
                }
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Sanitiza entrada de dados (alias para sanitize)
 */
function sanitizeInput($input) {
    return sanitize($input);
}

/**
 * Obtém notificação da sessão
 */
function getNotification() {
    if (isset($_SESSION['notification'])) {
        $notification = $_SESSION['notification'];
        unset($_SESSION['notification']); // Remove após ler
        return $notification;
    }
    return null;
}

/**
 * Define notificação na sessão
 */
function setNotification($message, $type = 'info') {
    $_SESSION['notification'] = [
        'message' => $message,
        'type' => $type
    ];
}

/**
 * Define notificação de sucesso
 */
function setSuccessNotification($message) {
    setNotification($message, 'success');
}

/**
 * Define notificação de erro
 */
function setErrorNotification($message) {
    setNotification($message, 'error');
}

/**
 * Define notificação de aviso
 */
function setWarningNotification($message) {
    setNotification($message, 'warning');
}

/**
 * Define notificação de informação
 */
function setInfoNotification($message) {
    setNotification($message, 'info');
}
?>
