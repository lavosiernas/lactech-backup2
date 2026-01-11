<?php
/**
 * KRON API v1 - Health Check
 * Endpoint para verificar status do Kron
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once __DIR__ . '/../../../includes/config.php';

// Verificar método
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método não permitido']);
    exit;
}

try {
    // Verificar conexão com banco
    $pdo = getKronDatabase();
    $dbStatus = $pdo ? 'connected' : 'disconnected';
    
    // Versão do sistema
    $version = '1.0.0';
    
    // Status geral
    $status = ($dbStatus === 'connected') ? 'healthy' : 'degraded';
    
    http_response_code(200);
    echo json_encode([
        'status' => $status,
        'version' => $version,
        'database' => $dbStatus,
        'timestamp' => date('c')
    ]);
    
} catch (Exception $e) {
    http_response_code(503);
    echo json_encode([
        'status' => 'unhealthy',
        'version' => '1.0.0',
        'error' => 'Erro interno',
        'timestamp' => date('c')
    ]);
}



