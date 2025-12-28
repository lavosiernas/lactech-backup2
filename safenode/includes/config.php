<?php
/**
 * SafeNode - Configuração do Banco de Dados
 * Banco de Dados: safend
 */

// Carregar configuração base do sistema
// Tentar múltiplos caminhos possíveis
$configPaths = [
    __DIR__ . '/../../includes/config_login.php',  // Se safenode está em lactech/safenode
    __DIR__ . '/../../../includes/config_login.php', // Se está em outra estrutura
    dirname(dirname(dirname(__DIR__))) . '/includes/config_login.php' // Caminho absoluto
];

$configLoaded = false;
foreach ($configPaths as $configPath) {
    if (file_exists($configPath)) {
        try {
            require_once $configPath;
            $configLoaded = true;
            break;
        } catch (Throwable $e) {
            // Se der erro ao carregar, continuar tentando outros caminhos
            error_log("SafeNode: Erro ao carregar $configPath: " . $e->getMessage());
            continue;
        }
    }
}

// Se não encontrou, tentar carregar apenas o necessário
if (!$configLoaded) {
    // Definir valores padrão se não encontrar o config_login.php
    if (!function_exists('getEnvValue')) {
        function getEnvValue($key, $default = null) {
            $value = getenv($key);
            if ($value === false) {
                $value = $_ENV[$key] ?? $_SERVER[$key] ?? null;
            }
            return $value !== null ? $value : $default;
        }
    }
    
    // Tentar carregar variáveis de ambiente diretamente
    $envLoaderPath = dirname(dirname(dirname(__DIR__))) . '/includes/env_loader.php';
    if (file_exists($envLoaderPath)) {
        require_once $envLoaderPath;
    }
}

// Configurações específicas do SafeNode
// Timezone - Configurar para horário de Brasília
if (!date_default_timezone_get() || date_default_timezone_get() === 'UTC') {
    date_default_timezone_set('America/Sao_Paulo');
}

// Detectar se está em produção (Hostinger) de forma mais robusta
$serverName = $_SERVER['SERVER_NAME'] ?? '';
$httpHost = $_SERVER['HTTP_HOST'] ?? '';
$isProduction = !in_array($serverName, ['localhost', '127.0.0.1', '::1']) &&
                strpos($httpHost, 'localhost') === false &&
                strpos($httpHost, '127.0.0.1') === false &&
                strpos($serverName, '192.168.') !== 0;

/**
 * Detecta a URL base do SafeNode automaticamente
 * Em produção: usa apenas o domínio (ex: https://safenode.cloud)
 * Em desenvolvimento: usa o caminho completo (ex: http://localhost/GitHub/lactech-backup2/lactech/safenode)
 */
if (!function_exists('getSafeNodeBaseUrl')) {
    function getSafeNodeBaseUrl(): string {
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        
        // Detectar se está em produção
        $isProduction = !in_array($host, ['localhost', '127.0.0.1', '::1']) &&
                        strpos($host, 'localhost') === false &&
                        strpos($host, '127.0.0.1') === false &&
                        strpos($host, '192.168.') !== 0;
        
        // Detectar caminho do script atual
        $scriptPath = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        
        if ($isProduction) {
            // Em produção: usar o caminho detectado automaticamente
            // Se o script está em /safenode/human-verification.php
            // A base será https://safenode.cloud/safenode
            // Se o script está na raiz, será https://safenode.cloud
            return $protocol . '://' . $host . $scriptPath;
        } else {
            // Em desenvolvimento: usar caminho completo
            // Ex: http://localhost/GitHub/lactech-backup2/lactech/safenode
            return $protocol . '://' . $host . $scriptPath;
        }
    }
}

if ($isProduction) {
    // PRODUÇÃO - Hostinger
    if (!defined('SAFENODE_DB_NAME')) {
        define('SAFENODE_DB_NAME', 'u311882628_safend');
    }
    if (!defined('SAFENODE_DB_HOST')) {
        define('SAFENODE_DB_HOST', defined('DB_HOST') ? DB_HOST : 'localhost');
    }
    if (!defined('SAFENODE_DB_USER')) {
        define('SAFENODE_DB_USER', 'u311882628_Kron');
    }
    if (!defined('SAFENODE_DB_PASS')) {
        define('SAFENODE_DB_PASS', 'Lavosier0012!');
    }
} else {
    // LOCAL - XAMPP/WAMP
    if (!defined('SAFENODE_DB_NAME')) {
        define('SAFENODE_DB_NAME', 'safend');
    }
    if (!defined('SAFENODE_DB_HOST')) {
        define('SAFENODE_DB_HOST', defined('DB_HOST') ? DB_HOST : 'localhost');
    }
    if (!defined('SAFENODE_DB_USER')) {
        define('SAFENODE_DB_USER', defined('DB_USER') ? DB_USER : 'root');
    }
    if (!defined('SAFENODE_DB_PASS')) {
        define('SAFENODE_DB_PASS', defined('DB_PASS') ? DB_PASS : '');
    }
}

// Garantir que todas as constantes estão definidas
if (!defined('SAFENODE_DB_NAME')) define('SAFENODE_DB_NAME', 'safend');
if (!defined('SAFENODE_DB_HOST')) define('SAFENODE_DB_HOST', 'localhost');
if (!defined('SAFENODE_DB_USER')) define('SAFENODE_DB_USER', 'root');
if (!defined('SAFENODE_DB_PASS')) define('SAFENODE_DB_PASS', '');

if (!defined('SAFENODE_DB_CHARSET')) {
    define('SAFENODE_DB_CHARSET', 'utf8mb4');
}

// Cloudflare API Token (opcional)
if (!defined('CLOUDFLARE_API_TOKEN')) {
    // Tentar carregar de variável de ambiente
    $cfToken = getEnvValue('CLOUDFLARE_API_TOKEN', null);
    if ($cfToken) {
        define('CLOUDFLARE_API_TOKEN', $cfToken);
    } else {
        // Token configurado diretamente (produção)
        // Para usar variável de ambiente, defina CLOUDFLARE_API_TOKEN no .env
        define('CLOUDFLARE_API_TOKEN', 'tx7-jws6RGLrfntu6DzR9dUTnz4br7zJ1c21oICi');
    }
}

/**
 * Conectar ao banco de dados SafeNode (safend)
 */
function getSafeNodeDatabase() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            // Garantir que as constantes estão definidas
            $host = defined('SAFENODE_DB_HOST') ? SAFENODE_DB_HOST : 'localhost';
            $dbname = defined('SAFENODE_DB_NAME') ? SAFENODE_DB_NAME : 'safend';
            $user = defined('SAFENODE_DB_USER') ? SAFENODE_DB_USER : 'root';
            $pass = defined('SAFENODE_DB_PASS') ? SAFENODE_DB_PASS : '';
            $charset = defined('SAFENODE_DB_CHARSET') ? SAFENODE_DB_CHARSET : 'utf8mb4';
            
            $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => 5
            ]);
        } catch (PDOException $e) {
            $errorMsg = $e->getMessage();
            error_log("SafeNode DB Error: " . $errorMsg);
            
            // Em desenvolvimento, mostrar erro detalhado
            if (defined('ENVIRONMENT') && ENVIRONMENT === 'LOCAL') {
                error_log("SafeNode DB Details - Host: $host, DB: $dbname, User: $user");
            }
            
            return false;
        }
    }
    
    return $pdo;
}

/**
 * Verificar se o banco safend existe
 */
function safenodeDatabaseExists() {
    try {
        $host = defined('SAFENODE_DB_HOST') ? SAFENODE_DB_HOST : 'localhost';
        $user = defined('SAFENODE_DB_USER') ? SAFENODE_DB_USER : 'root';
        $pass = defined('SAFENODE_DB_PASS') ? SAFENODE_DB_PASS : '';
        $charset = defined('SAFENODE_DB_CHARSET') ? SAFENODE_DB_CHARSET : 'utf8mb4';
        $dbName = defined('SAFENODE_DB_NAME') ? SAFENODE_DB_NAME : 'safend';
        
        $pdo = new PDO(
            "mysql:host=$host;charset=$charset",
            $user,
            $pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        // Verificar se o banco existe (com ou sem prefixo)
        $stmt = $pdo->query("SHOW DATABASES LIKE '$dbName'");
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("SafeNode DB Check Error: " . $e->getMessage());
        return false;
    }
}

