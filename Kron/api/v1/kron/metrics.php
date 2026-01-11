<?php
/**
 * KRON API v1 - Receber Métricas
 * Endpoint para sistemas governados enviarem métricas
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
if (!$jwt->hasScope($token, 'metrics:write')) {
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
$timestamp = $input['timestamp'] ?? date('c');
$metrics = $input['metrics'] ?? [];

if (empty($systemName) || empty($metrics) || !is_array($metrics)) {
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
    
    // Processar cada métrica
    foreach ($metrics as $metric) {
        $metricType = $metric['type'] ?? null;
        $metricValue = $metric['value'] ?? null;
        $metadata = $metric['metadata'] ?? null;
        
        if (!$metricType || $metricValue === null) {
            continue; // Pular métricas inválidas
        }
        
        // Converter timestamp para data
        $metricDate = date('Y-m-d', strtotime($timestamp));
        $metricHour = date('H', strtotime($timestamp));
        
        // Inserir métrica
        $stmt = $pdo->prepare("
            INSERT INTO kron_metrics 
            (system_id, metric_type, metric_value, metric_date, metric_hour, metadata)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $metadataJson = $metadata ? json_encode($metadata) : null;
        $stmt->execute([$systemId, $metricType, $metricValue, $metricDate, $metricHour, $metadataJson]);
        
        $receivedCount++;
    }
    
    // Log de auditoria
    $stmt = $pdo->prepare("
        INSERT INTO kron_audit_logs 
        (user_id, action, entity_type, entity_id, metadata, ip_address, user_agent)
        VALUES (NULL, 'metrics.received', 'system', ?, ?, ?, ?)
    ");
    
    $auditMetadata = json_encode([
        'system_name' => $systemName,
        'metrics_count' => $receivedCount,
        'timestamp' => $timestamp
    ]);
    
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    $stmt->execute([$systemId, $auditMetadata, $ipAddress, $userAgent]);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Métricas recebidas',
        'received_count' => $receivedCount,
        'timestamp' => date('c')
    ]);
    
} catch (Exception $e) {
    error_log("KRON Metrics API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => ['code' => 'INTERNAL_ERROR', 'message' => 'Erro interno']]);
}



