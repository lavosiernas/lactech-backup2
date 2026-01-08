<?php
/**
 * KRON API v1 - Resultado de Comando
 * Endpoint para sistemas confirmarem execução de comandos
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-System-Name, X-System-Version');

require_once __DIR__ . '/../../../../includes/config.php';
require_once __DIR__ . '/../../../../includes/KronSystemManager.php';
require_once __DIR__ . '/../../../../includes/KronCommandManager.php';
require_once __DIR__ . '/../../../../includes/KronJWT.php';

// Verificar método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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
if (!$jwt->hasScope($token, 'commands:write')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => ['code' => 'INSUFFICIENT_SCOPE', 'message' => 'Escopo insuficiente']]);
    exit;
}

// Obter dados do POST
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => ['code' => 'INVALID_DATA', 'message' => 'Dados inválidos']]);
    exit;
}

$commandId = $input['command_id'] ?? '';
$status = $input['status'] ?? '';
$result = $input['result'] ?? null;
$error = $input['error'] ?? null;
$executedAt = $input['executed_at'] ?? date('c');
$executionTimeMs = $input['execution_time_ms'] ?? null;

if (empty($commandId) || empty($status)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => ['code' => 'INVALID_DATA', 'message' => 'Dados incompletos']]);
    exit;
}

// Validar status
$validStatuses = ['success', 'failed', 'partial'];
if (!in_array($status, $validStatuses)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => ['code' => 'INVALID_DATA', 'message' => 'Status inválido']]);
    exit;
}

try {
    $commandManager = new KronCommandManager();
    
    $success = $commandManager->recordCommandResult(
        $commandId,
        $status,
        $result,
        $error,
        $executionTimeMs
    );
    
    if (!$success) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => ['code' => 'COMMAND_NOT_FOUND', 'message' => 'Comando não encontrado']]);
        exit;
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Resultado registrado',
        'timestamp' => date('c')
    ]);
    
} catch (Exception $e) {
    error_log("KRON Command Result API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => ['code' => 'INTERNAL_ERROR', 'message' => 'Erro interno']]);
}

