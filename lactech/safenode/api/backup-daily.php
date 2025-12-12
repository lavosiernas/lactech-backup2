<?php
/**
 * SafeNode - Daily Backup Script
 * Cria backup diário incremental
 * 
 * Executar via cron: 0 2 * * * php /caminho/safenode/api/backup-daily.php
 */

set_time_limit(0);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/init.php';

$db = getSafeNodeDatabase();

if (!$db) {
    error_log("SafeNode Backup: Erro ao conectar ao banco");
    exit(1);
}

try {
    require_once __DIR__ . '/../includes/BackupManager.php';
    $backupManager = new BackupManager($db);
    
    // Criar backup incremental (desde último backup)
    $lastBackupFile = $backupManager->listBackups()[0]['filename'] ?? null;
    $sinceDate = date('Y-m-d 00:00:00'); // Desde início do dia
    
    if ($lastBackupFile) {
        // Usar data do último backup
        preg_match('/(\d{4}-\d{2}-\d{2})/', $lastBackupFile, $matches);
        if (isset($matches[1])) {
            $sinceDate = $matches[1] . ' 00:00:00';
        }
    }
    
    $result = $backupManager->createIncrementalBackup($sinceDate);
    
    if ($result['success']) {
        error_log("SafeNode Backup: Backup incremental criado - {$result['filename']}");
    } else {
        error_log("SafeNode Backup: Erro ao criar backup - " . ($result['error'] ?? 'Unknown'));
    }
    
    // Limpar backups antigos (>30 dias)
    $deleted = $backupManager->cleanOldBackups(30);
    if ($deleted > 0) {
        error_log("SafeNode Backup: $deleted backups antigos removidos");
    }
    
    exit(0);
} catch (Exception $e) {
    error_log("SafeNode Backup Error: " . $e->getMessage());
    exit(1);
}





