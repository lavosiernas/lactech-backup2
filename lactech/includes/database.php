<?php
// =====================================================
// CONFIGURAÇÃO DE BANCO DE DADOS - LAGOA DO MATO
// =====================================================
// Conexão MySQL para PHPMyAdmin
// =====================================================

class Database {
    private $host = 'localhost';
    private $db_name = 'lactech_lagoa_mato';
    private $username = 'root';
    private $password = '';
    private $charset = 'utf8mb4';
    private $pdo;

    public function __construct() {
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
            throw new PDOException($e->getMessage(), (int)$e->getCode());
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
            $stmt->bindValue(":{$key}", $value);
        }
        
        $stmt->execute();
        return $this->pdo->lastInsertId();
    }

    public function update($table, $data, $where, $whereParams = []) {
        $setParts = [];
        foreach (array_keys($data) as $key) {
            $setParts[] = "{$key} = :{$key}";
        }
        $setClause = implode(', ', $setParts);
        
        $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";
        $stmt = $this->pdo->prepare($sql);
        
        foreach ($data as $key => $value) {
            $stmt->bindValue(":{$key}", $value);
        }
        
        foreach ($whereParams as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        return $stmt->execute();
    }

    public function delete($table, $where, $whereParams = []) {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $stmt = $this->pdo->prepare($sql);
        
        foreach ($whereParams as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        return $stmt->execute();
    }

    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }

    public function commit() {
        return $this->pdo->commit();
    }

    public function rollback() {
        return $this->pdo->rollback();
    }
}

// Instância global do banco
$db = new Database();

// Funções auxiliares para facilitar o uso
function getDB() {
    global $db;
    return $db;
}

function query($sql, $params = []) {
    return getDB()->query($sql, $params);
}

function fetch($sql, $params = []) {
    return getDB()->fetch($sql, $params);
}

function fetchAll($sql, $params = []) {
    return getDB()->fetchAll($sql, $params);
}

function insert($table, $data) {
    return getDB()->insert($table, $data);
}

function update($table, $data, $where, $whereParams = []) {
    return getDB()->update($table, $data, $where, $whereParams);
}

function delete($table, $where, $whereParams = []) {
    return getDB()->delete($table, $where, $whereParams);
}

// Funções específicas para o sistema Lagoa do Mato

/**
 * Obter estatísticas da fazenda
 */
function getFarmStats() {
    $stats = [];
    
    // Estatísticas de animais
    $animalStats = fetch("
        SELECT 
            COUNT(*) as total_animals,
            SUM(CASE WHEN productive_status = 'Lactante' THEN 1 ELSE 0 END) as lactating_cows,
            SUM(CASE WHEN health_status = 'Saudável' THEN 1 ELSE 0 END) as healthy_animals,
            SUM(CASE WHEN health_status = 'Em Tratamento' THEN 1 ELSE 0 END) as under_treatment,
            SUM(CASE WHEN health_status = 'Doente' THEN 1 ELSE 0 END) as sick_animals
        FROM animals 
        WHERE is_active = TRUE
    ");
    
    $stats['animals'] = $animalStats;
    
    // Produção de hoje
    $todayProduction = fetch("
        SELECT SUM(volume_liters) as total_liters
        FROM volume_records 
        WHERE production_date = CURDATE()
    ");
    
    $stats['today_production'] = $todayProduction['total_liters'] ?? 0;
    
    // Média semanal
    $weeklyAvg = fetch("
        SELECT AVG(daily_total) as weekly_avg
        FROM (
            SELECT production_date, SUM(volume_liters) as daily_total
            FROM volume_records 
            WHERE production_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            GROUP BY production_date
        ) as daily_totals
    ");
    
    $stats['weekly_avg'] = round($weeklyAvg['weekly_avg'] ?? 0, 2);
    
    return $stats;
}

/**
 * Obter lista de animais
 */
function getAnimals($activeOnly = true) {
    $sql = "SELECT * FROM animals";
    $params = [];
    
    if ($activeOnly) {
        $sql .= " WHERE is_active = TRUE";
    }
    
    $sql .= " ORDER BY name";
    
    return fetchAll($sql, $params);
}

/**
 * Obter produção diária
 */
function getDailyProduction($date = null) {
    $date = $date ?: date('Y-m-d');
    
    return fetch("
        SELECT 
            SUM(CASE WHEN shift = 'manha' THEN volume_liters ELSE 0 END) as manha_liters,
            SUM(CASE WHEN shift = 'tarde' THEN volume_liters ELSE 0 END) as tarde_liters,
            SUM(CASE WHEN shift = 'noite' THEN volume_liters ELSE 0 END) as noite_liters,
            SUM(volume_liters) as total_liters
        FROM volume_records 
        WHERE production_date = :date
    ", ['date' => $date]);
}

/**
 * Obter configurações da fazenda
 */
function getFarmSettings() {
    return fetch("SELECT * FROM farm_settings LIMIT 1");
}

/**
 * Atualizar configurações da fazenda
 */
function updateFarmSettings($data) {
    $existing = getFarmSettings();
    
    if ($existing) {
        return update('farm_settings', $data, 'id = :id', [':id' => $existing['id']]);
    } else {
        return insert('farm_settings', $data);
    }
}

/**
 * Verificar se usuário existe
 */
function getUserByEmail($email) {
    return fetch("SELECT * FROM users WHERE email = :email", ['email' => $email]);
}

/**
 * Criar novo usuário
 */
function createUser($data) {
    // Hash da senha
    if (isset($data['password'])) {
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
    }
    
    return insert('users', $data);
}

/**
 * Verificar login
 */
function verifyLogin($email, $password) {
    $user = getUserByEmail($email);
    
    if ($user && password_verify($password, $user['password'])) {
        return $user;
    }
    
    return false;
}

/**
 * Registrar produção de leite
 */
function registerMilkProduction($data) {
    return insert('volume_records', $data);
}

/**
 * Registrar produção individual por vaca
 */
function registerIndividualMilkProduction($data) {
    return insert('individual_milk_production', $data);
}

/**
 * Registrar teste de qualidade
 */
function registerQualityTest($data) {
    return insert('quality_tests', $data);
}

/**
 * Registrar tratamento
 */
function registerTreatment($data) {
    return insert('treatments', $data);
}

/**
 * Registrar inseminação
 */
function registerInsemination($data) {
    return insert('artificial_inseminations', $data);
}

/**
 * Registrar animal
 */
function registerAnimal($data) {
    return insert('animals', $data);
}

/**
 * Registrar registro financeiro
 */
function registerFinancialRecord($data) {
    return insert('financial_records', $data);
}
?>
