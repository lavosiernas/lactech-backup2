<?php
/**
 * KRON API v1 - Receber Alertas
 * Endpoint para sistemas governados dispararem alertas críticos
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
if (!$jwt->hasScope($token, 'alerts:write')) {
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
$alertType = $input['alert_type'] ?? '';
$severity = $input['severity'] ?? 'info';
$title = $input['title'] ?? '';
$message = $input['message'] ?? '';
$metadata = $input['metadata'] ?? null;
$timestamp = $input['timestamp'] ?? date('c');

if (empty($systemName) || empty($alertType) || empty($title) || empty($message)) {
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

// Validar severidade
$validSeverities = ['info', 'warning', 'error', 'critical'];
if (!in_array($severity, $validSeverities)) {
    $severity = 'info';
}

try {
    $pdo = getKronDatabase();
    
    if (!$pdo) {
        throw new Exception('Database error');
    }
    
    $systemId = $validation['system_id'];
    
    // Gerar ID único do alerta
    $alertId = 'alert_' . time() . '_' . bin2hex(random_bytes(4));
    
    // Criar notificação para usuários com acesso ao sistema
    // Buscar usuários que têm acesso ao sistema
    $stmt = $pdo->prepare("
        SELECT DISTINCT u.id as user_id
        FROM kron_users u
        INNER JOIN kron_user_system_sector uss ON u.id = uss.user_id
        WHERE uss.system_id = ? AND uss.is_active = 1 AND u.is_active = 1
    ");
    $stmt->execute([$systemId]);
    $users = $stmt->fetchAll();
    
    $notifiedCount = 0;
    
    foreach ($users as $user) {
        // Criar notificação
        $notifStmt = $pdo->prepare("
            INSERT INTO kron_notifications 
            (kron_user_id, system_name, type, title, message, metadata)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $notifMetadata = json_encode([
            'alert_id' => $alertId,
            'alert_type' => $alertType,
            'severity' => $severity,
            'metadata' => $metadata,
            'timestamp' => $timestamp
        ]);
        
        $notifStmt->execute([
            $user['user_id'],
            $systemName,
            'system_alert',
            $title,
            $message,
            $notifMetadata
        ]);
        
        $notifiedCount++;
    }
    
    // Log de auditoria
    $auditStmt = $pdo->prepare("
        INSERT INTO kron_audit_logs 
        (user_id, action, entity_type, entity_id, metadata, ip_address, user_agent)
        VALUES (NULL, 'alert.received', 'system', ?, ?, ?, ?)
    ");
    
    $auditMetadata = json_encode([
        'alert_id' => $alertId,
        'alert_type' => $alertType,
        'severity' => $severity,
        'system_name' => $systemName,
        'notified_users' => $notifiedCount
    ]);
    
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    $auditStmt->execute([$systemId, $auditMetadata, $ipAddress, $userAgent]);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'alert_id' => $alertId,
        'notified_users' => $notifiedCount,
        'timestamp' => date('c')
    ]);
    
} catch (Exception $e) {
    error_log("KRON Alerts API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => ['code' => 'INTERNAL_ERROR', 'message' => 'Erro interno']]);
}



