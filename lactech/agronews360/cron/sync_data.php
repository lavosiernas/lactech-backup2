<?php
/**
 * Script de Sincronização Automática
 * Execute este script via cron job para atualizar dados automaticamente
 * 
 * Exemplo de cron (executar a cada 6 horas):
 * 0 */6 * * * /usr/bin/php /caminho/para/agronews360/cron/sync_data.php
 */

// Definir timezone
date_default_timezone_set('America/Sao_Paulo');

// Caminho base
define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/includes/Database.class.php';
require_once BASE_PATH . '/api/external_apis.php';

echo "[" . date('Y-m-d H:i:s') . "] Iniciando sincronização de dados...\n";

try {
    $db = Database::getInstance();
    
    // Sincronizar notícias
    echo "[" . date('Y-m-d H:i:s') . "] Buscando notícias...\n";
    $newsResult = fetchAgroNews($db, 20);
    echo "[" . date('Y-m-d H:i:s') . "] Notícias: " . ($newsResult['success'] ? $newsResult['count'] . " novas" : "Erro") . "\n";
    
    // Sincronizar clima
    echo "[" . date('Y-m-d H:i:s') . "] Buscando dados climáticos...\n";
    $weatherResult = fetchWeatherData($db);
    echo "[" . date('Y-m-d H:i:s') . "] Clima: " . ($weatherResult['success'] ? "OK" : "Erro") . "\n";
    
    // Sincronizar cotações
    echo "[" . date('Y-m-d H:i:s') . "] Buscando cotações...\n";
    $quotationsResult = fetchQuotations($db);
    echo "[" . date('Y-m-d H:i:s') . "] Cotações: " . ($quotationsResult['success'] ? $quotationsResult['count'] . " atualizadas" : "Erro") . "\n";
    
    echo "[" . date('Y-m-d H:i:s') . "] Sincronização concluída!\n";
    
} catch (Exception $e) {
    echo "[" . date('Y-m-d H:i:s') . "] ERRO: " . $e->getMessage() . "\n";
    exit(1);
}

exit(0);






