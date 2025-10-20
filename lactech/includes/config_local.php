<?php
// =====================================================
// CONFIGURAÇÃO MYSQL - DESENVOLVIMENTO LOCAL
// =====================================================
// Use este arquivo para testar localmente (XAMPP)
// =====================================================

// Configurações do banco de dados - LOCAL (XAMPP)
define('DB_HOST', 'localhost');
define('DB_NAME', 'lactech_lgmato');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Configurações da aplicação
define('APP_NAME', 'LacTech - Lagoa do Mato');
define('APP_VERSION', '2.0.0');
define('FARM_NAME', 'Lagoa do Mato');

// URLs do sistema - LOCAL
define('BASE_URL', 'http://localhost/GitHub/lactech-backup2/lactech/');
define('LOGIN_URL', 'login.php');
define('DASHBOARD_URL', 'gerente.php');

// Configurações de sessão
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // HTTP em desenvolvimento

// Iniciar sessão se não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configurações de erro - ATIVADO EM DESENVOLVIMENTO
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('America/Sao_Paulo');

// =====================================================
// INSTRUÇÕES:
// =====================================================
/*

PARA TESTAR LOCALMENTE (XAMPP):
1. Renomeie config_mysql.php para config_mysql_production.php (backup)
2. Renomeie este arquivo (config_local.php) para config_mysql.php
3. Crie o banco "lactech_lgmato" no phpMyAdmin local
4. Importe o banco_mysql_completo.sql
5. Execute resetar_senhas.php para criar usuários de teste
6. Teste em http://localhost/...

PARA FAZER DEPLOY (HOSTINGER):
1. Renomeie config_mysql.php para config_local.php (backup)
2. Renomeie config_mysql_production.php para config_mysql.php
3. Faça upload para a Hostinger
4. O banco já estará com as credenciais corretas

*/
?>

