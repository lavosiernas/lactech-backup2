<?php
// =====================================================
// CONFIGURAÇÃO MYSQL - PRODUÇÃO (HOSTINGER)
// =====================================================
// Use este arquivo em produção na Hostinger
// Renomeie para config_mysql.php ou substitua o conteúdo
// =====================================================

// Configurações do banco de dados - PRODUÇÃO
define('DB_HOST', 'localhost');  // Geralmente 'localhost' na Hostinger
define('DB_NAME', 'lactech_lgmato');
define('DB_USER', 'SEU_USUARIO_AQUI');  // ⚠️ ALTERAR: Usuário do banco na Hostinger
define('DB_PASS', 'SUA_SENHA_AQUI');  
define('DB_CHARSET', 'utf8mb4');

// Configurações da aplicação
define('APP_NAME', 'LacTech - Lagoa do Mato');
define('APP_VERSION', '2.0.0');
define('FARM_NAME', 'Lagoa do Mato');

// URLs do sistema - ALTERAR PARA SEU DOMÍNIO
define('BASE_URL', 'https://seudominio.com/lactechsys/');
define('LOGIN_URL', 'login.php');
define('DASHBOARD_URL', 'gerente.php');

// Configurações de sessão
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1); // HTTPS habilitado em produção

// Iniciar sessão se não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configurações de erro - DESABILITAR ERROS EM PRODUÇÃO
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php-errors.log');

// Timezone
date_default_timezone_set('America/Sao_Paulo');

// Headers de segurança
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Configuração PDO (alternativa ao mysqli)
if (!defined('PDO_DSN')) {
    define('PDO_DSN', 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET);
}

// Função auxiliar para conexão
function getDBConnection() {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            error_log('Erro de conexão MySQL: ' . $conn->connect_error);
            die('Erro ao conectar ao banco de dados. Tente novamente mais tarde.');
        }
        
        $conn->set_charset(DB_CHARSET);
        return $conn;
        
    } catch (Exception $e) {
        error_log('Exceção na conexão MySQL: ' . $e->getMessage());
        die('Erro ao conectar ao banco de dados. Tente novamente mais tarde.');
    }
}

// =====================================================
// INSTRUÇÕES PARA CONFIGURAR NA HOSTINGER:
// =====================================================
/*

1. CRIAR O BANCO DE DADOS:
   - Acesse o painel da Hostinger
   - Vá em "Banco de Dados MySQL"
   - Crie um novo banco: lactech_lgmato
   - Anote o usuário e senha fornecidos

2. CONFIGURAR ESTE ARQUIVO:
   - Altere DB_USER para o usuário fornecido
   - Altere DB_PASS para a senha fornecida
   - Altere BASE_URL para seu domínio real

3. SUBSTITUIR config_mysql.php:
   - Faça backup do config_mysql.php atual
   - Copie este arquivo para config_mysql.php
   - Ou substitua o conteúdo

4. IMPORTAR BANCO DE DADOS:
   - Use o phpMyAdmin da Hostinger
   - Selecione o banco lactech_lgmato
   - Importe o arquivo banco_mysql_completo.sql
   - Aguarde a conclusão (pode demorar 1-2 minutos)

5. TESTAR:
   - Acesse seudominio.com/lactechsys/
   - Faça login
   - Teste as funcionalidades

6. SEGURANÇA (IMPORTANTE):
   - Altere as senhas dos usuários no banco
   - Configure SSL/HTTPS (geralmente gratuito na Hostinger)
   - Mantenha os arquivos de log seguros
   - Não exponha este arquivo publicamente

SUPORTE:
Se precisar da senha do banco que você configurou,
você consegue visualizar no painel da Hostinger em:
Painel → Banco de Dados MySQL → Ver Senha

*/
?>

