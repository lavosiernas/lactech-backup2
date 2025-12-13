<?php
/**
 * SafeNode - Weekly Backup Script
 * Cria backup semanal completo
 * 
 * Executar via cron: 0 3 * * 0 php /caminho/safenode/api/backup-weekly.php
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
    
    // Criar backup completo
    $result = $backupManager->createFullBackup();
    
    if ($result['success']) {
        error_log("SafeNode Backup: Backup completo criado - {$result['filename']} ({$result['size']} bytes)");
        
        // Opcional: Enviar para cloud storage (S3, etc)
        // $backupManager->uploadToCloud($result['filepath']);
    } else {
        error_log("SafeNode Backup: Erro ao criar backup completo - " . ($result['error'] ?? 'Unknown'));
    }
    
    exit(0);
} catch (Exception $e) {
    error_log("SafeNode Backup Error: " . $e->getMessage());
    exit(1);
}






