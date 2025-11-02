<?php
/**
 * CLASSE DATABASE BÁSICA - LACTECH
 * Classe simples para conexão com banco
 * Detecta automaticamente ambiente local ou produção
 */

// SEMPRE carregar config_mysql.php PRIMEIRO para detectar ambiente
require_once __DIR__ . '/config_mysql.php';

// NÃO carregar config.php aqui - ele pode sobrescrever as constantes corretas
// Se config_mysql.php foi carregado corretamente, as constantes já estarão definidas

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $charset;
    private $pdo;

    public function __construct() {
        // Usar constantes definidas (detectam automaticamente local vs produção)
        $this->host = defined('DB_HOST') ? DB_HOST : 'localhost';
        $this->db_name = defined('DB_NAME') ? DB_NAME : 'lactech_lgmato';
        $this->username = defined('DB_USER') ? DB_USER : 'root';
        $this->password = defined('DB_PASS') ? DB_PASS : '';
        $this->charset = defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4';
        
        $this->connect();
    }

    private function connect() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset={$this->charset}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            error_log("Erro de conexão Database: " . $e->getMessage() . " | Host: {$this->host} | DB: {$this->db_name} | User: {$this->username}");
            throw new PDOException("Erro de conexão: " . $e->getMessage(), (int)$e->getCode());
        }
    }

    public function getConnection() {
        return $this->pdo;
    }

    // Métodos para operações comuns
    public function query($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetch($sql, $params = []) {
        return $this->query($sql, $params)->fetch();
    }

    public function fetchAll($sql, $params = []) {
        return $this->query($sql, $params)->fetchAll();
    }

    public function insert($table, $data) {
        $columns = implode(',', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $stmt = $this->pdo->prepare($sql);
        
        foreach ($data as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        
        $stmt->execute();
        return $this->pdo->lastInsertId();
    }

    public function update($table, $data, $where, $whereParams = []) {
        $setClause = [];
        foreach ($data as $key => $value) {
            $setClause[] = "{$key} = :{$key}";
        }
        $setClause = implode(', ', $setClause);
        
        $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";
        $stmt = $this->pdo->prepare($sql);
        
        foreach ($data as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        
        foreach ($whereParams as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        
        return $stmt->execute();
    }

    public function delete($table, $where, $whereParams = []) {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $stmt = $this->pdo->prepare($sql);
        
        foreach ($whereParams as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        
        return $stmt->execute();
    }
}
?>
