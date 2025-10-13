<?php
// Helper para operações comuns de banco de dados
require_once '../includes/config_mysql.php';
require_once '../includes/database.php';

class DatabaseHelper {
    private $db;
    
    public function __construct() {
        $this->db = getDBConnection();
    }
    
    // SELECT genérico
    public function select($table, $columns = '*', $where = [], $orderBy = '', $limit = 0) {
        try {
            $sql = "SELECT $columns FROM $table";
            
            if (!empty($where)) {
                $conditions = [];
                foreach ($where as $key => $value) {
                    if (is_array($value)) {
                        // Operadores especiais: gte, lte, gt, lt, neq
                        $operator = $value['op'] ?? '=';
                        $conditions[] = "$key $operator ?";
                    } else {
                        $conditions[] = "$key = ?";
                    }
                }
                $sql .= " WHERE " . implode(' AND ', $conditions);
            }
            
            if ($orderBy) {
                $sql .= " ORDER BY $orderBy";
            }
            
            if ($limit > 0) {
                $sql .= " LIMIT $limit";
            }
            
            $stmt = $this->db->prepare($sql);
            
            // Bind parameters
            $params = [];
            foreach ($where as $value) {
                if (is_array($value)) {
                    $params[] = $value['value'];
                } else {
                    $params[] = $value;
                }
            }
            
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Erro ao buscar dados: " . $e->getMessage());
        }
    }
    
    // SELECT único registro
    public function selectOne($table, $columns = '*', $where = []) {
        $result = $this->select($table, $columns, $where, '', 1);
        return $result ? $result[0] : null;
    }
    
    // INSERT
    public function insert($table, $data) {
        try {
            $columns = array_keys($data);
            $placeholders = array_fill(0, count($columns), '?');
            
            $sql = "INSERT INTO $table (" . implode(', ', $columns) . ") 
                    VALUES (" . implode(', ', $placeholders) . ")";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array_values($data));
            
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            throw new Exception("Erro ao inserir dados: " . $e->getMessage());
        }
    }
    
    // UPDATE
    public function update($table, $data, $where) {
        try {
            $setClauses = [];
            foreach (array_keys($data) as $key) {
                $setClauses[] = "$key = ?";
            }
            
            $whereClauses = [];
            foreach (array_keys($where) as $key) {
                $whereClauses[] = "$key = ?";
            }
            
            $sql = "UPDATE $table SET " . implode(', ', $setClauses) . 
                   " WHERE " . implode(' AND ', $whereClauses);
            
            $stmt = $this->db->prepare($sql);
            $params = array_merge(array_values($data), array_values($where));
            $stmt->execute($params);
            
            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new Exception("Erro ao atualizar dados: " . $e->getMessage());
        }
    }
    
    // DELETE
    public function delete($table, $where) {
        try {
            $whereClauses = [];
            foreach (array_keys($where) as $key) {
                $whereClauses[] = "$key = ?";
            }
            
            $sql = "DELETE FROM $table WHERE " . implode(' AND ', $whereClauses);
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array_values($where));
            
            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new Exception("Erro ao deletar dados: " . $e->getMessage());
        }
    }
    
    // Executar query customizada
    public function query($sql, $params = []) {
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Erro ao executar query: " . $e->getMessage());
        }
    }
}

// Função helper para retornar resposta JSON no formato Supabase
function sendResponse($data = null, $error = null) {
    header('Content-Type: application/json');
    echo json_encode([
        'data' => $data,
        'error' => $error
    ]);
    exit;
}
?>




