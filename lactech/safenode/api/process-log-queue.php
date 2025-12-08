<?php
/**
 * SafeNode - Process Log Queue
 * Worker para processar fila de logs assíncronos
 * 
 * Deve ser executado via cron a cada 1-5 minutos:
 * */1 * * * * php /caminho/para/safenode/api/process-log-queue.php
 */

// Desabilitar limite de tempo de execução
set_time_limit(0);

// Carregar configuração
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/init.php';

$db = getSafeNodeDatabase();

if (!$db) {
    error_log("SafeNode LogQueue Worker: Erro ao conectar ao banco de dados");
    exit(1);
}

try {
    require_once __DIR__ . '/../includes/LogQueue.php';
    $logQueue = new LogQueue($db);
    
    // Processar logs da fila
    $processed = $logQueue->process(200); // Processar até 200 logs por execução
    
    if ($processed > 0) {
        error_log("SafeNode LogQueue Worker: Processados $processed logs");
    }
    
    // Verificar tamanho da fila
    $queueSize = $logQueue->getQueueSize();
    if ($queueSize > 5000) {
        error_log("SafeNode LogQueue Worker: AVISO - Fila muito grande ($queueSize itens). Considere aumentar frequência do worker.");
    }
    
    exit(0);
} catch (Exception $e) {
    error_log("SafeNode LogQueue Worker Error: " . $e->getMessage());
    exit(1);
}


