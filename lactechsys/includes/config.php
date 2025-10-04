<?php
// Configurações do sistema LacTech
define('SUPABASE_URL', 'https://tmaamwuyucaspqcrhuck.supabase.co');
define('SUPABASE_ANON_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InRtYWFtd3V5dWNhc3BxY3JodWNrIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NTY2OTY1MzMsImV4cCI6MjA3MjI3MjUzM30.AdDXp0xrX_xKutFHQrJ47LhFdLTtanTSku7fcK1eTB0');

// Configurações de sessão
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Mude para 1 em produção com HTTPS

// Timezone
date_default_timezone_set('America/Sao_Paulo');

// Configurações de erro (desenvolvimento)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configurações de upload
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB
define('UPLOAD_PATH', __DIR__ . '/../uploads/');

// URLs do sistema
define('BASE_URL', 'http://localhost/lactechsys/');
define('LOGIN_URL', BASE_URL . 'login.php');
define('DASHBOARD_URL', BASE_URL . 'dashboard.php');
?>
