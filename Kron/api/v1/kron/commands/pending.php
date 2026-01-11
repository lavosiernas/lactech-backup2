<?php
/**
 * KRON API v1 - Comandos Pendentes
 * Endpoint para sistemas consultarem comandos pendentes
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-System-Name, X-System-Version');

require_once __DIR__ . '/../../../../includes/config.php';
require_once __DIR__ . '/../../../../includes/KronSystemManager.php';
require_once __DIR__ . '/../../../../includes/KronCommandManager.php';
require_once __DIR__ . '/../../../../includes/KronJWT.php';

// Verificar método
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => ['code' => 'METHOD_NOT_ALLOWED', 'message' => 'Método não permitido']]);
    exit;
}

// Obter token de autenticação
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$token = null;

if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
    $token = $matches[1];
}

if (!$token) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => ['code' => 'INVALID_TOKEN', 'message' => 'Token não fornecido']]);
    exit;
}

// Validar token
$systemManager = new KronSystemManager();
$validation = $systemManager->validateSystemToken($token);

if (!$validation['valid']) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => ['code' => 'INVALID_TOKEN', 'message' => $validation['error'] ?? 'Token inválido']]);
    exit;
}

// Verificar escopo
$jwt = new KronJWT();
if (!$jwt->hasScope($token, 'commands:read')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => ['code' => 'INSUFFICIENT_SCOPE', 'message' => 'Escopo insuficiente']]);
    exit;
}

try {
    $systemId = $validation['system_id'];
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    
    if ($limit > 100) {
        $limit = 100; // Máximo
    }
    
    $commandManager = new KronCommandManager();
    $commands = $commandManager->getPendingCommands($systemId, $limit);
    
    // Formatar resposta
    $formattedCommands = [];
    foreach ($commands as $command) {
        $formattedCommands[] = [
            'command_id' => $command['command_id'],
            'type' => $command['type'],
            'parameters' => $command['parameters'],
            'priority' => $command['priority'],
            'created_at' => $command['created_at']
        ];
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'commands' => $formattedCommands,
        'count' => count($formattedCommands)
    ]);
    
} catch (Exception $e) {
    error_log("KRON Commands API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => ['code' => 'INTERNAL_ERROR', 'message' => 'Erro interno']]);
}



