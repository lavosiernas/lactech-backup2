<?php
/**
 * EXEMPLO DE CONFIGURAÇÃO DE ACESSO AO BANCO DO LACTECH - AGRO NEWS 360
 * Copie este arquivo para config_lactech_db.php e configure as credenciais
 */

// =====================================================
// DETECÇÃO AUTOMÁTICA DE AMBIENTE (LOCAL OU PRODUÇÃO)
// =====================================================

// Detectar se está em localhost
if (!isset($isLocal)) {
    $isLocal = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1', '::1']) ||
               strpos($_SERVER['SERVER_NAME'] ?? '', '192.168.') === 0 ||
               strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false;
}

// =====================================================
// CONFIGURAÇÕES DO BANCO DE DADOS DO LACTECH
// =====================================================

if ($isLocal) {
    // AMBIENTE LOCAL (XAMPP/WAMP)
    if (!defined('LACTECH_DB_HOST')) define('LACTECH_DB_HOST', 'localhost');
    if (!defined('LACTECH_DB_NAME')) define('LACTECH_DB_NAME', 'lactech_lgmato');
    if (!defined('LACTECH_DB_USER')) define('LACTECH_DB_USER', 'root');
    if (!defined('LACTECH_DB_PASS')) define('LACTECH_DB_PASS', '');
} else {
    // AMBIENTE DE PRODUÇÃO (HOSPEDAGEM)
    if (!defined('LACTECH_DB_HOST')) define('LACTECH_DB_HOST', 'localhost');
    if (!defined('LACTECH_DB_NAME')) define('LACTECH_DB_NAME', 'u311882628_lactech_lgmato');
    if (!defined('LACTECH_DB_USER')) define('LACTECH_DB_USER', 'u311882628_xandriaAgro');
    if (!defined('LACTECH_DB_PASS')) define('LACTECH_DB_PASS', 'SUA_SENHA_AQUI');
}

if (!defined('LACTECH_DB_CHARSET')) define('LACTECH_DB_CHARSET', 'utf8mb4');

