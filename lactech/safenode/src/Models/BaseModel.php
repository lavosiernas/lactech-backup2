<?php
/**
 * SafeNode - Base Model
 * Classe base para todos os models
 */

namespace SafeNode\Models;

class BaseModel
{
    protected $db;
    protected $table;
    
    public function __construct($database)
    {
        $this->db = $database;
    }
    
    /**
     * Busca um registro por ID
     */
    public function findById(int $id): ?array
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch() ?: null;
        } catch (\PDOException $e) {
            error_log("BaseModel::findById Error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Busca todos os registros
     */
    public function findAll(array $conditions = []): array
    {
        try {
            $sql = "SELECT * FROM {$this->table}";
            $params = [];
            
            if (!empty($conditions)) {
                $where = [];
                foreach ($conditions as $key => $value) {
                    $where[] = "{$key} = ?";
                    $params[] = $value;
                }
                $sql .= " WHERE " . implode(" AND ", $where);
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log("BaseModel::findAll Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Cria um novo registro
     */
    public function create(array $data): ?int
    {
        try {
            $fields = array_keys($data);
            $placeholders = array_fill(0, count($fields), '?');
            
            $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") 
                    VALUES (" . implode(', ', $placeholders) . ")";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array_values($data));
            
            return (int)$this->db->lastInsertId();
        } catch (\PDOException $e) {
            error_log("BaseModel::create Error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Atualiza um registro
     */
    public function update(int $id, array $data): bool
    {
        try {
            $fields = [];
            $params = [];
            
            foreach ($data as $key => $value) {
                $fields[] = "{$key} = ?";
                $params[] = $value;
            }
            
            $params[] = $id;
            
            $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            
            return $stmt->execute($params);
        } catch (\PDOException $e) {
            error_log("BaseModel::update Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Deleta um registro
     */
    public function delete(int $id): bool
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (\PDOException $e) {
            error_log("BaseModel::delete Error: " . $e->getMessage());
            return false;
        }
    }
}









