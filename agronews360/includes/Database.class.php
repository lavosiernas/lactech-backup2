<?php
/**
 * CLASSE DE BANCO DE DADOS - AGRO NEWS 360
 * Sistema robusto e profissional
 */

require_once __DIR__ . '/config_mysql.php';

class Database {
    private static $instance = null;
    private $pdo = null;
    private $lastError = null;
    private $queryCache = [];
    
    const CACHE_TTL = 300; // 5 minutos
    
    /**
     * Construtor privado (Singleton)
     */
    private function __construct() {
        $this->connect();
    }
    
    /**
     * Estabelecer conexão com o banco
     */
    private function connect() {
        try {
            $host = defined('DB_HOST') ? DB_HOST : 'localhost';
            $db   = defined('DB_NAME') ? DB_NAME : 'agronews';
            $user = defined('DB_USER') ? DB_USER : 'root';
            $pass = defined('DB_PASS') ? DB_PASS : '';
            $charset = defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4';

            $dsn = "mysql:host={$host};dbname={$db};charset={$charset}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            // Criar conexão PDO
            $this->pdo = new PDO($dsn, $user, $pass, $options);
            
            // Configurar timezone do MySQL
            $timezone = new DateTimeZone(date_default_timezone_get());
            $now = new DateTime('now', $timezone);
            $offset = $timezone->getOffset($now);
            $hours = floor(abs($offset) / 3600);
            $minutes = floor((abs($offset) % 3600) / 60);
            $sign = $offset >= 0 ? '+' : '-';
            $mysqlTimezone = sprintf('%s%02d:%02d', $sign, $hours, $minutes);
            $this->pdo->exec("SET time_zone = '" . $mysqlTimezone . "'");
        } catch (PDOException $e) {
            $this->lastError = "Erro de conexão PDO: " . $e->getMessage();
            error_log("Database connection error: " . $this->lastError);
            throw new Exception("Não foi possível conectar ao banco de dados '{$db}'. Verifique se o banco existe e as credenciais estão corretas.");
        } catch (Throwable $e) {
            $this->lastError = "Erro de conexão: " . $e->getMessage();
            error_log("Database connection error: " . $this->lastError);
            throw new Exception($this->lastError);
        }
    }
    
    /**
     * Obter instância única (Singleton)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Obter conexão PDO
     */
    public function getConnection() {
        try {
            $this->pdo->query('SELECT 1');
        } catch (PDOException $e) {
            $this->connect();
        }
        
        return $this->pdo;
    }
    
    /**
     * Obter último erro
     */
    public function getLastError() {
        return $this->lastError;
    }
    
    /**
     * Executar query com tratamento de erro e cache
     */
    public function query($sql, $params = [], $useCache = false, $cacheKey = null) {
        // Gerar chave de cache se não fornecida
        if ($useCache && $cacheKey === null) {
            $cacheKey = md5($sql . serialize($params));
        }
        
        // Verificar cache primeiro
        if ($useCache && isset($this->queryCache[$cacheKey])) {
            $cached = $this->queryCache[$cacheKey];
            if (time() - $cached['timestamp'] < self::CACHE_TTL) {
                return $cached['data'];
            } else {
                unset($this->queryCache[$cacheKey]);
            }
        }
        
        try {
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute($params);
            
            // Buscar todos os resultados como array associativo
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Armazenar no cache se solicitado
            if ($useCache) {
                $this->queryCache[$cacheKey] = [
                    'data' => $results,
                    'timestamp' => time()
                ];
            }
            
            return $results;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            error_log("Erro na query SQL: {$sql} - {$e->getMessage()}");
            throw $e;
        }
    }
    
    /**
     * Limpar cache de queries
     */
    public function clearCache() {
        $this->queryCache = [];
    }
    
    /**
     * Destructor - fechar conexão
     */
    public function __destruct() {
        $this->pdo = null;
    }
}

