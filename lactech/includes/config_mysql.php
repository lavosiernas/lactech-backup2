<?php
// Prevenir múltiplas inclusões - se as constantes principais já existem, não executar novamente
if (!defined('CONFIG_MYSQL_LOADED')) {
    // Marcar que este arquivo foi processado
    define('CONFIG_MYSQL_LOADED', true);
    
    // Carregar variáveis de ambiente (se o loader existir)
    $envLoaderPath = __DIR__ . '/env_loader.php';
    if (file_exists($envLoaderPath)) {
        require_once $envLoaderPath;
    }
    
    // Função auxiliar para obter variável de ambiente com fallback
    if (!function_exists('getEnvValue')) {
        function getEnvValue($key, $default = null) {
            if (function_exists('env')) {
                return env($key, $default);
            }
            $value = getenv($key);
            if ($value === false) {
                $value = $_ENV[$key] ?? $_SERVER[$key] ?? null;
            }
            return $value !== null ? $value : $default;
        }
    }
    
    // =====================================================
    // DETECÇÃO AUTOMÁTICA DE AMBIENTE (LOCAL OU PRODUÇÃO)
    // =====================================================
    
    // Detectar se está em localhost (só se ainda não foi definido)
    if (!isset($isLocal)) {
        $serverName = $_SERVER['SERVER_NAME'] ?? '';
        $httpHost = $_SERVER['HTTP_HOST'] ?? '';
        $serverAddr = $_SERVER['SERVER_ADDR'] ?? '';
        
        // Detectar ambiente local de várias formas
        $isLocal = (
            in_array($serverName, ['localhost', '127.0.0.1', '::1']) ||
            in_array($httpHost, ['localhost', '127.0.0.1', 'localhost:80', 'localhost:8080', '127.0.0.1:80', '127.0.0.1:8080']) ||
            strpos($serverName, '192.168.') === 0 ||
            strpos($httpHost, 'localhost') !== false ||
            strpos($httpHost, '127.0.0.1') !== false ||
            strpos($serverAddr, '127.0.0.1') === 0 ||
            strpos($serverAddr, '::1') === 0 ||
            // Verificar se está em xampp (comum no Windows)
            strpos($_SERVER['DOCUMENT_ROOT'] ?? '', 'xampp') !== false ||
            strpos($_SERVER['DOCUMENT_ROOT'] ?? '', 'htdocs') !== false
        );
        
        // Log para debug (remover em produção)
        error_log("🔍 Detecção de Ambiente - SERVER_NAME: {$serverName}, HTTP_HOST: {$httpHost}, SERVER_ADDR: {$serverAddr}, isLocal: " . ($isLocal ? 'SIM' : 'NÃO'));
    }
    
    // Detectar URL base automaticamente (só se ainda não foi definida)
    if (!function_exists('getBaseUrl')) {
        function getBaseUrl() {
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $script = $_SERVER['SCRIPT_NAME'] ?? '';
            $path = str_replace('\\', '/', dirname($script));
            
            // Remover index.php ou qualquer arquivo do final
            $path = rtrim($path, '/') . '/';
            
            return $protocol . '://' . $host . $path;
        }
    }
    
    // =====================================================
    // CONFIGURAÇÕES DO BANCO DE DADOS
    // =====================================================
    
    if ($isLocal) {
        // AMBIENTE LOCAL (XAMPP/WAMP)
        // Usar variáveis de ambiente se disponíveis, senão usar valores padrão
        if (!defined('DB_HOST')) define('DB_HOST', getEnvValue('DB_HOST_LOCAL', 'localhost'));
        if (!defined('DB_NAME')) define('DB_NAME', getEnvValue('DB_NAME_LOCAL', 'lactech_lgmato'));
        if (!defined('DB_USER')) define('DB_USER', getEnvValue('DB_USER_LOCAL', 'root'));
        if (!defined('DB_PASS')) define('DB_PASS', getEnvValue('DB_PASS_LOCAL', ''));
        if (!defined('BASE_URL')) define('BASE_URL', getBaseUrl()); // Detecta automaticamente
        if (!defined('ENVIRONMENT')) define('ENVIRONMENT', 'LOCAL');
    } else {
        // AMBIENTE DE PRODUÇÃO (HOSPEDAGEM)
        // Usar APENAS variáveis de ambiente - SEM fallback com credenciais hardcoded
        // Aceitar tanto DB_HOST_PROD quanto DB_HOST (sem sufixo)
        $dbHost = getEnvValue('DB_HOST_PROD') ?: getEnvValue('DB_HOST');
        $dbName = getEnvValue('DB_NAME_PROD') ?: getEnvValue('DB_NAME');
        $dbUser = getEnvValue('DB_USER_PROD') ?: getEnvValue('DB_USER');
        $dbPass = getEnvValue('DB_PASS_PROD') ?: getEnvValue('DB_PASS');
        $baseUrl = getEnvValue('BASE_URL_PROD') ?: getEnvValue('BASE_URL');
        
        // Validar que todas as variáveis necessárias estão definidas
        if (empty($dbHost) || empty($dbName) || empty($dbUser)) {
            // Criar mensagem de erro amigável
            $errorMessage = 'Configuração do banco de dados não encontrada.';
            $instructions = 'Por favor, verifique o arquivo .env.production na raiz do projeto com as credenciais do banco de dados.';
            
            // Debug: Verificar quais variáveis estão disponíveis
            $debugInfo = '';
            $availableVars = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS', 'DB_HOST_PROD', 'DB_NAME_PROD', 'DB_USER_PROD', 'DB_PASS_PROD'];
            $foundVars = [];
            foreach ($availableVars as $var) {
                $val = getEnvValue($var);
                if (!empty($val)) {
                    $foundVars[] = $var . '=' . (strlen($val) > 20 ? substr($val, 0, 20) . '...' : $val);
                }
            }
            if (!empty($foundVars)) {
                $debugInfo = '<p><strong>Variáveis encontradas:</strong> ' . implode(', ', $foundVars) . '</p>';
            }
            
            // Se não estiver em produção (mostrar detalhes), mostrar erro detalhado
            // Em produção, mostrar página de erro amigável
            if (headers_sent() === false) {
                http_response_code(500);
                header('Content-Type: text/html; charset=utf-8');
            }
            
            // Exibir página de erro amigável
            echo '<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erro de Configuração - LacTech</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .error-container {
            background: white;
            border-radius: 8px;
            padding: 40px;
            max-width: 600px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #dc2626;
            margin-top: 0;
        }
        .error-code {
            background: #fef2f2;
            border-left: 4px solid #dc2626;
            padding: 15px;
            margin: 20px 0;
        }
        .instructions {
            background: #f0f9ff;
            border-left: 4px solid #0284c7;
            padding: 15px;
            margin: 20px 0;
        }
        code {
            background: #f3f4f6;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
        .env-example {
            background: #1f2937;
            color: #f9fafb;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            overflow-x: auto;
            font-family: monospace;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <h1>⚠️ Erro de Configuração</h1>
        <div class="error-code">
            <strong>' . htmlspecialchars($errorMessage) . '</strong>
        </div>
        <div class="instructions">
            <h2>Como resolver:</h2>
            <p>' . htmlspecialchars($instructions) . '</p>
            <ol>
                <li>Acesse o painel de controle da sua hospedagem (cPanel, FTP, etc.)</li>
                <li>Navegue até a pasta raiz do projeto (onde está o arquivo <code>index.php</code>)</li>
                <li>Crie um arquivo chamado <code>.env</code> (com o ponto no início)</li>
                <li>Adicione o seguinte conteúdo (substitua pelos seus dados reais):</li>
            </ol>
            <div class="env-example">
# Opção 1: Com sufixo _PROD (recomendado)<br>
DB_HOST_PROD=localhost<br>
DB_NAME_PROD=seu_banco_producao<br>
DB_USER_PROD=seu_usuario_producao<br>
DB_PASS_PROD=sua_senha_producao<br>
<br>
# Opção 2: Sem sufixo (também aceito)<br>
DB_HOST=localhost<br>
DB_NAME=seu_banco_producao<br>
DB_USER=seu_usuario_producao<br>
DB_PASS=sua_senha_producao<br>
<br>
# Google OAuth<br>
GOOGLE_CLIENT_ID=seu_google_client_id<br>
GOOGLE_CLIENT_SECRET=seu_google_client_secret<br>
GOOGLE_REDIRECT_URI=https://seu-dominio.com/google-callback.php<br>
GOOGLE_LOGIN_REDIRECT_URI=https://seu-dominio.com/google-login-callback.php<br>
<br>
# URL Base<br>
BASE_URL_PROD=https://seu-dominio.com/<br>
# ou<br>
BASE_URL=https://seu-dominio.com/
            </div>
            ' . $debugInfo . '
            <p><strong>Importante:</strong> Certifique-se de que o arquivo <code>.env</code> tenha permissões de leitura corretas (geralmente 644).</p>
        </div>
        <p style="color: #6b7280; font-size: 14px; margin-top: 30px;">
            Se você já criou o arquivo .env e ainda está vendo este erro, verifique:
            <ul>
                <li>O arquivo está na raiz do projeto (mesma pasta que index.php)</li>
                <li>O nome do arquivo está correto (começa com ponto: <code>.env</code>)</li>
                <li>As variáveis estão preenchidas com os valores corretos</li>
                <li>Não há espaços antes ou depois dos sinais de igual (=)</li>
            </ul>
        </p>
    </div>
</body>
</html>';
            exit;
        }
        
        if (!defined('DB_HOST')) define('DB_HOST', $dbHost);
        if (!defined('DB_NAME')) define('DB_NAME', $dbName);
        if (!defined('DB_USER')) define('DB_USER', $dbUser);
        if (!defined('DB_PASS')) define('DB_PASS', $dbPass ?: ''); // Senha pode ser vazia
        if (!defined('BASE_URL')) define('BASE_URL', $baseUrl ?: 'https://lactechsys.com/');
        if (!defined('ENVIRONMENT')) define('ENVIRONMENT', 'PRODUCTION');
    }
    
    if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');
    if (!defined('APP_NAME')) define('APP_NAME', 'LacTech - Lagoa do Mato');
    if (!defined('APP_VERSION')) define('APP_VERSION', '2.0.0');
    if (!defined('FARM_NAME')) define('FARM_NAME', 'Lagoa do Mato');
    if (!defined('LOGIN_URL')) define('LOGIN_URL', 'inicio-login.php');
    if (!defined('DASHBOARD_URL')) define('DASHBOARD_URL', 'gerente-completo.php');
    
    // Configurações de sessão (ANTES de iniciar a sessão - só se ainda não foi iniciada)
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        if (defined('ENVIRONMENT') && ENVIRONMENT === 'LOCAL') {
            ini_set('session.cookie_secure', 0); // HTTP local
        } else {
            ini_set('session.cookie_secure', 1); // HTTPS em produção
        }
        session_start();
    }
}

// Configurações de erro (sempre ocultar em endpoints para não quebrar JSON)
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Timezone
date_default_timezone_set('America/Sao_Paulo');

// Configurações de upload (só definir se ainda não foram definidas)
if (!defined('UPLOAD_MAX_SIZE')) define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB
if (!defined('ALLOWED_EXTENSIONS')) define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']);

// Configurações de relatórios (só definir se ainda não foram definidas)
if (!defined('REPORT_COMPANY_NAME')) define('REPORT_COMPANY_NAME', 'Lagoa do Mato');
if (!defined('REPORT_COMPANY_ADDRESS')) define('REPORT_COMPANY_ADDRESS', 'São Paulo, SP');
if (!defined('REPORT_COMPANY_PHONE')) define('REPORT_COMPANY_PHONE', '(11) 99999-9999');

// Configurações de backup (só definir se ainda não foram definidas)
if (!defined('BACKUP_ENABLED')) define('BACKUP_ENABLED', true);
if (!defined('BACKUP_PATH')) define('BACKUP_PATH', '../backups/');

// Configurações de cache (só definir se ainda não foram definidas)
if (!defined('CACHE_ENABLED')) define('CACHE_ENABLED', true);
if (!defined('CACHE_TIME')) define('CACHE_TIME', 3600); // 1 hora

// Configurações de segurança (só definir se ainda não foram definidas)
if (!defined('PASSWORD_MIN_LENGTH')) define('PASSWORD_MIN_LENGTH', 6);
if (!defined('SESSION_TIMEOUT')) define('SESSION_TIMEOUT', 3600); // 1 hora

// Configurações específicas da fazenda (só definir se ainda não foram definidas)
if (!defined('DAILY_MILKING_SHIFTS')) define('DAILY_MILKING_SHIFTS', ['manha', 'tarde', 'noite']);
if (!defined('USER_ROLES')) define('USER_ROLES', ['proprietario', 'gerente', 'funcionario']);
if (!defined('ANIMAL_BREEDS')) define('ANIMAL_BREEDS', ['Holandesa', 'Gir', 'Girolanda', 'Jersey', 'Pardo Suíço', 'Simental', 'Outras']);
if (!defined('ANIMAL_STATUS')) define('ANIMAL_STATUS', ['Lactante', 'Seco', 'Novilha', 'Vaca', 'Bezerra', 'Bezerro']);
if (!defined('HEALTH_STATUS')) define('HEALTH_STATUS', ['saudavel', 'doente', 'tratamento', 'quarentena']);
if (!defined('TREATMENT_TYPES')) define('TREATMENT_TYPES', ['Medicamento', 'Vacinação', 'Vermifugação', 'Suplementação', 'Cirurgia', 'Outros']);
if (!defined('FINANCIAL_TYPES')) define('FINANCIAL_TYPES', ['receita', 'despesa']);
if (!defined('PAYMENT_METHODS')) define('PAYMENT_METHODS', ['dinheiro', 'cartao', 'transferencia', 'cheque', 'pix']);

// Funções auxiliares (só definir se ainda não foram definidas)
if (!function_exists('getConfig')) {
    function getConfig($key, $default = null) {
        return defined($key) ? constant($key) : $default;
    }
}

if (!function_exists('isDevelopment')) {
    function isDevelopment() {
        return $_SERVER['SERVER_NAME'] === 'localhost' || 
               strpos($_SERVER['SERVER_NAME'], '127.0.0.1') !== false ||
               strpos($_SERVER['SERVER_NAME'], '192.168.') !== false;
    }
}

if (!function_exists('redirect')) {
    function redirect($url) {
        header("Location: $url");
        exit;
    }
}

if (!function_exists('sanitize')) {
    function sanitize($input) {
        if (is_array($input)) {
            return array_map('sanitize', $input);
        }
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('isValidEmail')) {
    function isValidEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

if (!function_exists('generateToken')) {
    function generateToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
}

if (!function_exists('logError')) {
    function logError($message, $context = []) {
        $logMessage = date('Y-m-d H:i:s') . " - $message";
        
        if (!empty($context)) {
            $logMessage .= " - Context: " . json_encode($context);
        }
        
        error_log($logMessage);
    }
}

if (!function_exists('isAjax')) {
    function isAjax() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }
}

if (!function_exists('jsonResponse')) {
    function jsonResponse($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}

if (!function_exists('formatDate')) {
    function formatDate($date, $format = 'd/m/Y') {
        if (empty($date)) return '';
        
        $timestamp = is_string($date) ? strtotime($date) : $date;
        return date($format, $timestamp);
    }
}

if (!function_exists('formatCurrency')) {
    function formatCurrency($value, $currency = 'R$') {
        if (empty($value)) return $currency . ' 0,00';
        
        return $currency . ' ' . number_format($value, 2, ',', '.');
    }
}

if (!function_exists('formatVolume')) {
    function formatVolume($volume) {
        if (empty($volume)) return '0,00 L';
        
        return number_format($volume, 2, ',', '.') . ' L';
    }
}

// Configurações de notificação (só definir se ainda não foram definidas)
if (!function_exists('setNotification')) {
    function setNotification($message, $type = 'info') {
        $_SESSION['notification'] = [
            'message' => $message,
            'type' => $type
        ];
    }
}

if (!function_exists('getNotification')) {
    function getNotification() {
        if (isset($_SESSION['notification'])) {
            $notification = $_SESSION['notification'];
            unset($_SESSION['notification']);
            return $notification;
        }
        return null;
    }
}

if (!function_exists('setSuccessNotification')) {
    function setSuccessNotification($message) {
        setNotification($message, 'success');
    }
}

if (!function_exists('setErrorNotification')) {
    function setErrorNotification($message) {
        setNotification($message, 'error');
    }
}

if (!function_exists('setWarningNotification')) {
    function setWarningNotification($message) {
        setNotification($message, 'warning');
    }
}

if (!function_exists('setInfoNotification')) {
    function setInfoNotification($message) {
        setNotification($message, 'info');
    }
}
?>
