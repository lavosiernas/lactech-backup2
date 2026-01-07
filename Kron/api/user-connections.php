<?php
/**
 * KRON API - Listar Conexões do Usuário
 */

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/KronConnectionManager.php';

// Verificar se está logado
if (!isset($_SESSION['kron_logged_in']) || $_SESSION['kron_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Não autenticado']);
    exit;
}

$kronUserId = $_SESSION['kron_user_id'] ?? null;

if (!$kronUserId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Usuário não identificado']);
    exit;
}

try {
    $connectionManager = new KronConnectionManager();
    $connections = $connectionManager->getUserConnections($kronUserId);
    
    echo json_encode([
        'success' => true,
        'connections' => $connections
    ]);
    
} catch (Exception $e) {
    error_log("KRON Get Connections Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erro interno']);
}

