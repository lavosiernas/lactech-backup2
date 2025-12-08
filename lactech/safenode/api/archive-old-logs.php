<?php
/**
 * SafeNode - Archive Old Logs
 * Script para arquivar logs antigos (>90 dias)
 * 
 * Deve ser executado via cron mensalmente:
 * 0 2 1 * * php /caminho/para/safenode/api/archive-old-logs.php
 */

set_time_limit(0);

// Carregar configuração
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/init.php';

$db = getSafeNodeDatabase();

if (!$db) {
    error_log("SafeNode Archive: Erro ao conectar ao banco de dados");
    exit(1);
}

try {
    $archiveDays = 90; // Arquivar logs com mais de 90 dias
    $cutoffDate = date('Y-m-d', strtotime("-$archiveDays days"));
    
    error_log("SafeNode Archive: Iniciando arquivamento de logs anteriores a $cutoffDate");
    
    // Verificar se tabela de arquivo existe, se não criar
    try {
        $db->query("SELECT 1 FROM safenode_security_logs_archive LIMIT 1");
    } catch (PDOException $e) {
        // Criar tabela de arquivo
        $db->exec("
            CREATE TABLE safenode_security_logs_archive (
                LIKE safenode_security_logs
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        error_log("SafeNode Archive: Tabela de arquivo criada");
    }
    
    // Contar quantos registros serão arquivados
    $stmt = $db->prepare("
        SELECT COUNT(*) as total 
        FROM safenode_security_logs 
        WHERE created_at < ?
    ");
    $stmt->execute([$cutoffDate]);
    $result = $stmt->fetch();
    $totalToArchive = (int)($result['total'] ?? 0);
    
    if ($totalToArchive == 0) {
        error_log("SafeNode Archive: Nenhum log para arquivar");
        exit(0);
    }
    
    error_log("SafeNode Archive: Encontrados $totalToArchive logs para arquivar");
    
    // Arquivar em lotes de 1000 para não sobrecarregar
    $batchSize = 1000;
    $archived = 0;
    $offset = 0;
    
    while ($offset < $totalToArchive) {
        // Copiar lote para tabela de arquivo
        $db->beginTransaction();
        
        try {
            $stmt = $db->prepare("
                INSERT INTO safenode_security_logs_archive
                SELECT * FROM safenode_security_logs
                WHERE created_at < ?
                ORDER BY id
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$cutoffDate, $batchSize, $offset]);
            $inserted = $stmt->rowCount();
            
            if ($inserted > 0) {
                // Deletar do original
                $stmt = $db->prepare("
                    DELETE FROM safenode_security_logs
                    WHERE created_at < ?
                    ORDER BY id
                    LIMIT ?
                ");
                $stmt->execute([$cutoffDate, $inserted]);
                
                $archived += $inserted;
                $offset += $batchSize;
                
                $db->commit();
                
                error_log("SafeNode Archive: Arquivados $archived de $totalToArchive logs");
            } else {
                $db->rollBack();
                break;
            }
        } catch (Exception $e) {
            $db->rollBack();
            error_log("SafeNode Archive Error: " . $e->getMessage());
            throw $e;
        }
    }
    
    // Otimizar tabela após deletar
    error_log("SafeNode Archive: Otimizando tabela...");
    $db->exec("OPTIMIZE TABLE safenode_security_logs");
    
    error_log("SafeNode Archive: Concluído! $archived logs arquivados");
    
    exit(0);
} catch (Exception $e) {
    error_log("SafeNode Archive Fatal Error: " . $e->getMessage());
    exit(1);
}



