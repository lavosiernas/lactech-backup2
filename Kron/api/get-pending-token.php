<?php
/**
 * KRON API - Obter Token Pendente
 */

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';

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

// Obter sistema
$systemName = $_GET['system_name'] ?? $_POST['system_name'] ?? '';

if (!in_array($systemName, ['safenode', 'lactech'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Sistema inválido']);
    exit;
}

try {
    $pdo = getKronDatabase();
    
    // Buscar token pendente válido
    $stmt = $pdo->prepare("
        SELECT token, expires_at, 
               TIMESTAMPDIFF(SECOND, NOW(), expires_at) as expires_in
        FROM kron_connection_tokens 
        WHERE kron_user_id = ? 
        AND system_name = ? 
        AND status = 'pending'
        AND expires_at > NOW()
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$kronUserId, $systemName]);
    $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($tokenData && $tokenData['expires_in'] > 0) {
        echo json_encode([
            'success' => true,
            'has_pending' => true,
            'token' => $tokenData['token'],
            'expires_at' => $tokenData['expires_at'],
            'expires_in' => (int)$tokenData['expires_in']
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'has_pending' => false
        ]);
    }
    
} catch (Exception $e) {
    error_log("KRON Get Pending Token Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erro interno']);
}

