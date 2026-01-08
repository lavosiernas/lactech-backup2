<?php
/**
 * KRON - Gerenciador de Comandos
 * Gerencia comandos enviados aos sistemas governados
 */

require_once __DIR__ . '/config.php';

class KronCommandManager
{
    private $pdo;
    
    public function __construct()
    {
        $this->pdo = getKronDatabase();
    }
    
    /**
     * Cria novo comando
     */
    public function createCommand($systemId, $type, $parameters = [], $priority = 'normal', $createdBy = null)
    {
        if (!$this->pdo) {
            return null;
        }
        
        try {
            // Gerar ID único do comando
            $commandId = 'cmd_' . time() . '_' . bin2hex(random_bytes(4));
            
            $stmt = $this->pdo->prepare("
                INSERT INTO kron_commands 
                (command_id, system_id, type, parameters, priority, status, created_by)
                VALUES (?, ?, ?, ?, ?, 'pending', ?)
            ");
            
            $parametersJson = json_encode($parameters);
            $stmt->execute([$commandId, $systemId, $type, $parametersJson, $priority, $createdBy]);
            
            return [
                'id' => $this->pdo->lastInsertId(),
                'command_id' => $commandId
            ];
            
        } catch (PDOException $e) {
            error_log("KRON Command Manager Error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Obtém comandos pendentes de um sistema
     */
    public function getPendingCommands($systemId, $limit = 10)
    {
        if (!$this->pdo) {
            return [];
        }
        
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM kron_commands
                WHERE system_id = ? AND status IN ('pending', 'queued')
                ORDER BY 
                    CASE priority
                        WHEN 'critical' THEN 1
                        WHEN 'high' THEN 2
                        WHEN 'normal' THEN 3
                        WHEN 'low' THEN 4
                    END,
                    created_at ASC
                LIMIT ?
            ");
            
            $stmt->execute([$systemId, $limit]);
            $commands = $stmt->fetchAll();
            
            // Decodificar parâmetros
            foreach ($commands as &$command) {
                $command['parameters'] = json_decode($command['parameters'] ?? '{}', true);
            }
            
            return $commands;
            
        } catch (PDOException $e) {
            error_log("KRON Command Manager Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Marca comando como em execução
     */
    public function markCommandAsExecuting($commandId)
    {
        if (!$this->pdo) {
            return false;
        }
        
        try {
            $stmt = $this->pdo->prepare("
                UPDATE kron_commands 
                SET status = 'executing', executed_at = NOW()
                WHERE command_id = ? AND status IN ('pending', 'queued')
            ");
            
            return $stmt->execute([$commandId]);
            
        } catch (PDOException $e) {
            error_log("KRON Command Manager Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Registra resultado de comando
     */
    public function recordCommandResult($commandId, $status, $resultData = null, $error = null, $executionTimeMs = null)
    {
        if (!$this->pdo) {
            return false;
        }
        
        try {
            // Obter ID do comando
            $stmt = $this->pdo->prepare("
                SELECT id FROM kron_commands WHERE command_id = ?
            ");
            $stmt->execute([$commandId]);
            $command = $stmt->fetch();
            
            if (!$command) {
                return false;
            }
            
            $commandDbId = $command['id'];
            
            // Atualizar status do comando
            $commandStatus = ($status === 'success') ? 'completed' : 'failed';
            $stmt = $this->pdo->prepare("
                UPDATE kron_commands 
                SET status = ?, completed_at = NOW(), error_message = ?
                WHERE id = ?
            ");
            $stmt->execute([$commandStatus, $error, $commandDbId]);
            
            // Inserir resultado
            $stmt = $this->pdo->prepare("
                INSERT INTO kron_command_results 
                (command_id, status, result_data, error, execution_time_ms)
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $resultDataJson = $resultData ? json_encode($resultData) : null;
            return $stmt->execute([$commandDbId, $status, $resultDataJson, $error, $executionTimeMs]);
            
        } catch (PDOException $e) {
            error_log("KRON Command Manager Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém histórico de comandos
     */
    public function getCommandHistory($systemId = null, $limit = 50)
    {
        if (!$this->pdo) {
            return [];
        }
        
        try {
            $sql = "
                SELECT c.*, s.name as system_name, s.display_name as system_display_name
                FROM kron_commands c
                INNER JOIN kron_systems s ON c.system_id = s.id
            ";
            
            $params = [];
            
            if ($systemId) {
                $sql .= " WHERE c.system_id = ?";
                $params[] = $systemId;
            }
            
            $sql .= " ORDER BY c.created_at DESC LIMIT ?";
            $params[] = $limit;
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $commands = $stmt->fetchAll();
            
            // Decodificar parâmetros
            foreach ($commands as &$command) {
                $command['parameters'] = json_decode($command['parameters'] ?? '{}', true);
            }
            
            return $commands;
            
        } catch (PDOException $e) {
            error_log("KRON Command Manager Error: " . $e->getMessage());
            return [];
        }
    }
}

