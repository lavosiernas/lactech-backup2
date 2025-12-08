<?php
/**
 * SafeNode - Backup Manager
 * Sistema de backup automatizado e Disaster Recovery
 */

class BackupManager {
    private $db;
    private $backupDir;
    
    public function __construct($database, $backupDir = null) {
        $this->db = $database;
        $this->backupDir = $backupDir ?: __DIR__ . '/../../backups';
        
        if (!is_dir($this->backupDir)) {
            @mkdir($this->backupDir, 0755, true);
        }
    }
    
    /**
     * Cria backup completo do banco de dados
     */
    public function createFullBackup() {
        $timestamp = date('Y-m-d_H-i-s');
        $filename = "safenode_full_backup_$timestamp.sql";
        $filepath = $this->backupDir . '/' . $filename;
        
        $host = defined('SAFENODE_DB_HOST') ? SAFENODE_DB_HOST : 'localhost';
        $user = defined('SAFENODE_DB_USER') ? SAFENODE_DB_USER : 'root';
        $pass = defined('SAFENODE_DB_PASS') ? SAFENODE_DB_PASS : '';
        $dbname = defined('SAFENODE_DB_NAME') ? SAFENODE_DB_NAME : 'safend';
        
        // Usar mysqldump se disponível
        $command = sprintf(
            'mysqldump -h %s -u %s %s %s > %s 2>&1',
            escapeshellarg($host),
            escapeshellarg($user),
            !empty($pass) ? '-p' . escapeshellarg($pass) : '',
            escapeshellarg($dbname),
            escapeshellarg($filepath)
        );
        
        exec($command, $output, $returnCode);
        
        if ($returnCode === 0 && file_exists($filepath) && filesize($filepath) > 0) {
            // Comprimir backup
            $this->compressBackup($filepath);
            
            return [
                'success' => true,
                'filename' => $filename,
                'filepath' => $filepath,
                'size' => filesize($filepath)
            ];
        }
        
        return [
            'success' => false,
            'error' => 'Erro ao criar backup'
        ];
    }
    
    /**
     * Cria backup incremental (apenas dados novos/modificados)
     */
    public function createIncrementalBackup($sinceDate) {
        $timestamp = date('Y-m-d_H-i-s');
        $filename = "safenode_incremental_backup_$timestamp.sql";
        $filepath = $this->backupDir . '/' . $filename;
        
        try {
            $sql = "-- Backup Incremental SafeNode\n";
            $sql .= "-- Data: $sinceDate até " . date('Y-m-d H:i:s') . "\n\n";
            
            // Backup de logs novos
            $stmt = $this->db->prepare("
                SELECT * FROM safenode_security_logs
                WHERE created_at >= ?
                ORDER BY id
            ");
            $stmt->execute([$sinceDate]);
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($logs)) {
                $sql .= "INSERT INTO safenode_security_logs VALUES\n";
                $values = [];
                foreach ($logs as $log) {
                    $values[] = "(" . implode(',', array_map(function($v) {
                        return $this->db->quote($v);
                    }, $log)) . ")";
                }
                $sql .= implode(",\n", $values) . ";\n\n";
            }
            
            // Backup de IPs bloqueados novos
            $stmt = $this->db->prepare("
                SELECT * FROM safenode_blocked_ips
                WHERE created_at >= ? OR updated_at >= ?
            ");
            $stmt->execute([$sinceDate, $sinceDate]);
            $blocked = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($blocked)) {
                $sql .= "INSERT INTO safenode_blocked_ips VALUES\n";
                $values = [];
                foreach ($blocked as $ip) {
                    $values[] = "(" . implode(',', array_map(function($v) {
                        return $this->db->quote($v);
                    }, $ip)) . ")";
                }
                $sql .= implode(",\n", $values) . ";\n\n";
            }
            
            file_put_contents($filepath, $sql);
            
            return [
                'success' => true,
                'filename' => $filename,
                'filepath' => $filepath,
                'size' => filesize($filepath)
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Comprime backup
     */
    private function compressBackup($filepath) {
        if (function_exists('gzencode')) {
            $compressed = gzencode(file_get_contents($filepath), 9);
            file_put_contents($filepath . '.gz', $compressed);
            @unlink($filepath); // Remover arquivo não comprimido
        }
    }
    
    /**
     * Restaura backup
     */
    public function restoreBackup($backupFile) {
        $filepath = $this->backupDir . '/' . $backupFile;
        
        if (!file_exists($filepath)) {
            return ['success' => false, 'error' => 'Arquivo de backup não encontrado'];
        }
        
        // Descomprimir se necessário
        if (substr($filepath, -3) === '.gz') {
            $filepath = $this->decompressBackup($filepath);
        }
        
        $host = defined('SAFENODE_DB_HOST') ? SAFENODE_DB_HOST : 'localhost';
        $user = defined('SAFENODE_DB_USER') ? SAFENODE_DB_USER : 'root';
        $pass = defined('SAFENODE_DB_PASS') ? SAFENODE_DB_PASS : '';
        $dbname = defined('SAFENODE_DB_NAME') ? SAFENODE_DB_NAME : 'safend';
        
        $command = sprintf(
            'mysql -h %s -u %s %s %s < %s 2>&1',
            escapeshellarg($host),
            escapeshellarg($user),
            !empty($pass) ? '-p' . escapeshellarg($pass) : '',
            escapeshellarg($dbname),
            escapeshellarg($filepath)
        );
        
        exec($command, $output, $returnCode);
        
        if ($returnCode === 0) {
            return ['success' => true, 'message' => 'Backup restaurado com sucesso'];
        }
        
        return ['success' => false, 'error' => 'Erro ao restaurar backup'];
    }
    
    /**
     * Descomprime backup
     */
    private function decompressBackup($filepath) {
        $decompressed = gzdecode(file_get_contents($filepath));
        $newPath = str_replace('.gz', '', $filepath);
        file_put_contents($newPath, $decompressed);
        return $newPath;
    }
    
    /**
     * Lista backups disponíveis
     */
    public function listBackups() {
        $backups = [];
        $files = glob($this->backupDir . '/safenode_*_backup_*.sql*');
        
        foreach ($files as $file) {
            $backups[] = [
                'filename' => basename($file),
                'size' => filesize($file),
                'created_at' => date('Y-m-d H:i:s', filemtime($file)),
                'type' => strpos($file, 'full') !== false ? 'full' : 'incremental'
            ];
        }
        
        usort($backups, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        return $backups;
    }
    
    /**
     * Limpa backups antigos
     */
    public function cleanOldBackups($keepDays = 30) {
        $cutoffDate = date('Y-m-d', strtotime("-$keepDays days"));
        $files = glob($this->backupDir . '/safenode_*_backup_*.sql*');
        $deleted = 0;
        
        foreach ($files as $file) {
            if (filemtime($file) < strtotime($cutoffDate)) {
                @unlink($file);
                $deleted++;
            }
        }
        
        return $deleted;
    }
}



