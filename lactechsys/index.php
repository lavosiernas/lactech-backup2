<?php
/**
 * Página inicial - Redirecionamento inteligente
 * Detecta automaticamente se está em localhost ou produção
 */

// Detectar se está em localhost
$isLocal = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1', '::1']) ||
           strpos($_SERVER['SERVER_NAME'] ?? '', '192.168.') === 0 ||
           strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false;

// Detectar protocolo atual
$currentProtocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

if ($isLocal && $currentProtocol === 'https') {
    // REDIRECIONAR DE HTTPS PARA HTTP EM LOCALHOST
    $httpUrl = 'http://' . $host . $_SERVER['REQUEST_URI'];
    header("Location: $httpUrl", true, 301);
    exit();
}

// Se chegou até aqui, redirecionar para login
header("Location: login.php", true, 302);
exit();
?>