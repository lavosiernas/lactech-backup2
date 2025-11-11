<?php
/**
 * CONFIGURAÇÃO DE BANCO DE DADOS - AGRO NEWS 360
 * Sistema independente com banco de dados próprio
 */

// Detectar se está em localhost
$isLocal = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1', '::1']) ||
           strpos($_SERVER['SERVER_NAME'] ?? '', '192.168.') === 0 ||
           strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false;

// Configurações do banco de dados
if ($isLocal) {
    // Configuração LOCAL
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'agronews');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_CHARSET', 'utf8mb4');
} else {
    // Configuração PRODUÇÃO (agronews360.online)
    // ATENÇÃO: Atualizar essas credenciais com os dados reais do servidor
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'agronews');
    define('DB_USER', 'agronews_user');
    define('DB_PASS', 'sua_senha_aqui');
    define('DB_CHARSET', 'utf8mb4');
}

// Timezone
date_default_timezone_set('America/Sao_Paulo');

// Configurações de erro (sempre desabilitar display_errors para APIs)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

