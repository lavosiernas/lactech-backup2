<?php
require_once 'config.php';

// Funções utilitárias do sistema

function formatDate($date, $format = 'd/m/Y') {
    if (empty($date)) return '';
    return date($format, strtotime($date));
}

function formatDateTime($date, $format = 'd/m/Y H:i') {
    if (empty($date)) return '';
    return date($format, strtotime($date));
}

function formatCurrency($value) {
    return 'R$ ' . number_format($value, 2, ',', '.');
}

function formatVolume($value) {
    return number_format($value, 2, ',', '.') . ' L';
}

function formatTemperature($value) {
    return number_format($value, 1, ',', '.') . '°C';
}

function formatPercentage($value) {
    return number_format($value, 1, ',', '.') . '%';
}

function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validateCNPJ($cnpj) {
    $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
    
    if (strlen($cnpj) != 14) return false;
    
    // Verificar se todos os dígitos são iguais
    if (preg_match('/(\d)\1{13}/', $cnpj)) return false;
    
    // Validar CNPJ
    $tamanho = strlen($cnpj) - 2;
    $numeros = substr($cnpj, 0, $tamanho);
    $digitos = substr($cnpj, $tamanho);
    $soma = 0;
    $pos = $tamanho - 7;
    
    for ($i = $tamanho; $i >= 1; $i--) {
        $soma += $numeros[$tamanho - $i] * $pos--;
        if ($pos < 2) $pos = 9;
    }
    
    $resultado = $soma % 11 < 2 ? 0 : 11 - $soma % 11;
    if ($resultado != $digitos[0]) return false;
    
    $tamanho = $tamanho + 1;
    $numeros = substr($cnpj, 0, $tamanho);
    $soma = 0;
    $pos = $tamanho - 7;
    
    for ($i = $tamanho; $i >= 1; $i--) {
        $soma += $numeros[$tamanho - $i] * $pos--;
        if ($pos < 2) $pos = 9;
    }
    
    $resultado = $soma % 11 < 2 ? 0 : 11 - $soma % 11;
    return $resultado == $digitos[1];
}

function generateRandomPassword($length = 8) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $password = '';
    
    for ($i = 0; $i < $length; $i++) {
        $password .= $characters[rand(0, strlen($characters) - 1)];
    }
    
    return $password;
}

function sendEmail($to, $subject, $message, $headers = '') {
    // Implementação básica de envio de email
    // Em produção, use PHPMailer ou similar
    $defaultHeaders = "From: noreply@lactech.com\r\n";
    $defaultHeaders .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    $fullHeaders = $headers ? $headers : $defaultHeaders;
    
    return mail($to, $subject, $message, $fullHeaders);
}

function showNotification($message, $type = 'info') {
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

function redirect($url) {
    header('Location: ' . $url);
    exit;
}

function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function uploadFile($file, $destination = '') {
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return ['success' => false, 'message' => 'Nenhum arquivo enviado'];
    }
    
    if ($file['size'] > UPLOAD_MAX_SIZE) {
        return ['success' => false, 'message' => 'Arquivo muito grande'];
    }
    
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'message' => 'Tipo de arquivo não permitido'];
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $extension;
    $filepath = UPLOAD_PATH . $destination . $filename;
    
    if (!is_dir(dirname($filepath))) {
        mkdir(dirname($filepath), 0755, true);
    }
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filename' => $filename, 'path' => $filepath];
    } else {
        return ['success' => false, 'message' => 'Erro ao fazer upload'];
    }
}
?>
