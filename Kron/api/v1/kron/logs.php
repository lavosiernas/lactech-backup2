<?php
/**
 * KRON API v1 - Receber Logs
 * Endpoint para sistemas governados enviarem logs
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-System-Name, X-System-Version');

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/KronSystemManager.php';
require_once __DIR__ . '/../../../includes/KronJWT.php';

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
if (!$jwt->hasScope($token, 'logs:write')) {
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

$systemName = $input['system_name'] ?? '';
$logs = $input['logs'] ?? [];

if (empty($systemName) || empty($logs) || !is_array($logs)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => ['code' => 'INVALID_DATA', 'message' => 'Dados incompletos']]);
    exit;
}

// Verificar se sistema corresponde ao token
if ($validation['system_name'] !== $systemName) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => ['code' => 'SYSTEM_MISMATCH', 'message' => 'Sistema não corresponde ao token']]);
    exit;
}

try {
    $pdo = getKronDatabase();
    
    if (!$pdo) {
        throw new Exception('Database error');
    }
    
    $systemId = $validation['system_id'];
    $receivedCount = 0;
    
    // Processar cada log
    foreach ($logs as $log) {
        $level = $log['level'] ?? 'info';
        $message = $log['message'] ?? '';
        $context = $log['context'] ?? null;
        $stackTrace = $log['stack_trace'] ?? null;
        
        if (empty($message)) {
            continue; // Pular logs sem mensagem
        }
        
        // Validar nível
        $validLevels = ['debug', 'info', 'warning', 'error', 'critical'];
        if (!in_array($level, $validLevels)) {
            $level = 'info';
        }
        
        // Inserir log
        $stmt = $pdo->prepare("
            INSERT INTO kron_system_logs 
            (system_id, level, message, context, stack_trace)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $contextJson = $context ? json_encode($context) : null;
        $stmt->execute([$systemId, $level, $message, $contextJson, $stackTrace]);
        
        $receivedCount++;
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Logs recebidos',
        'received_count' => $receivedCount,
        'timestamp' => date('c')
    ]);
    
} catch (Exception $e) {
    error_log("KRON Logs API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => ['code' => 'INTERNAL_ERROR', 'message' => 'Erro interno']]);
}

