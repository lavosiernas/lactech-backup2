<?php
/**
 * Script para forçar HTTP em localhost
 * Use este arquivo quando o .htaccess não estiver funcionando
 */

// Detectar se está em localhost
$isLocal = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1', '::1']) ||
           strpos($_SERVER['SERVER_NAME'] ?? '', '192.168.') === 0 ||
           strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false;

// Se não for localhost, não fazer nada
if (!$isLocal) {
    header("Location: login.php");
    exit();
}

// Se for HTTPS em localhost, redirecionar para HTTP
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    $httpUrl = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    header("Location: $httpUrl", true, 301);
    exit();
}

// Se chegou até aqui, está em HTTP localhost - redirecionar para login
header("Location: login.php", true, 302);
exit();
?>

