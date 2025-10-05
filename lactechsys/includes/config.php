<?php
// =====================================================
// CONFIGURAÇÃO SUPABASE - LACTECH
// =====================================================

// Configurações do Supabase
define('SUPABASE_URL', 'https://tmaamwuyucaspqcrhuck.supabase.co');
define('SUPABASE_ANON_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InRtYWFtd3V5dWNhc3BxY3JodWNrIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NTY2OTY1MzMsImV4cCI6MjA3MjI3MjUzM30.AdDXp0xrX_xKutFHQrJ47LhFdLTtanTSku7fcK1eTB0');

// Configuração da Fazenda Fixa
define('FARM_ID', '550e8400-e29b-41d4-a716-446655440000'); // UUID válido para Lagoa do Mato
define('FARM_NAME', 'Lagoa do Mato');

// URLs do sistema
define('DASHBOARD_URL', 'inicio.php');
define('LOGIN_URL', 'login.php');

// Configurações gerais
define('APP_NAME', 'LacTech - Lagoa do Mato');
define('APP_VERSION', '1.0.0');

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
?>
