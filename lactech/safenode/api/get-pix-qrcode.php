<?php
/**
 * SafeNode - API: Buscar QR Code PIX
 */

session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/SecurityHelpers.php';
require_once __DIR__ . '/../includes/AsaasAPI.php';

SecurityHeaders::apply();
header('Content-Type: application/json; charset=utf-8');

// Verificar autenticação
if (!isset($_SESSION['safenode_logged_in']) || $_SESSION['safenode_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Não autenticado']);
    exit;
}

try {
    $pdo = getSafeNodeDatabase();
    if (!$pdo) {
        throw new Exception('Erro ao conectar ao banco de dados');
    }
    
    $paymentId = $_GET['payment_id'] ?? null;
    if (!$paymentId) {
        throw new Exception('ID do pagamento não fornecido');
    }
    
    // Verificar se o pagamento pertence ao usuário
    $userId = $_SESSION['safenode_user_id'] ?? null;
    $stmt = $pdo->prepare("SELECT * FROM safenode_payments WHERE asaas_payment_id = ? AND user_id = ?");
    $stmt->execute([$paymentId, $userId]);
    $payment = $stmt->fetch();
    
    if (!$payment) {
        throw new Exception('Pagamento não encontrado ou não pertence ao usuário');
    }
    
    // Buscar QR Code na Asaas
    $asaasAPI = new AsaasAPI($pdo);
    $result = $asaasAPI->getPixQrCode($paymentId);
    
    if (!$result['success']) {
        throw new Exception($result['error']);
    }
    
    echo json_encode([
        'success' => true,
        'data' => $result['data']
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}





/**
 * SafeNode - API: Buscar QR Code PIX
 */

session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/SecurityHelpers.php';
require_once __DIR__ . '/../includes/AsaasAPI.php';

SecurityHeaders::apply();
header('Content-Type: application/json; charset=utf-8');

// Verificar autenticação
if (!isset($_SESSION['safenode_logged_in']) || $_SESSION['safenode_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Não autenticado']);
    exit;
}

try {
    $pdo = getSafeNodeDatabase();
    if (!$pdo) {
        throw new Exception('Erro ao conectar ao banco de dados');
    }
    
    $paymentId = $_GET['payment_id'] ?? null;
    if (!$paymentId) {
        throw new Exception('ID do pagamento não fornecido');
    }
    
    // Verificar se o pagamento pertence ao usuário
    $userId = $_SESSION['safenode_user_id'] ?? null;
    $stmt = $pdo->prepare("SELECT * FROM safenode_payments WHERE asaas_payment_id = ? AND user_id = ?");
    $stmt->execute([$paymentId, $userId]);
    $payment = $stmt->fetch();
    
    if (!$payment) {
        throw new Exception('Pagamento não encontrado ou não pertence ao usuário');
    }
    
    // Buscar QR Code na Asaas
    $asaasAPI = new AsaasAPI($pdo);
    $result = $asaasAPI->getPixQrCode($paymentId);
    
    if (!$result['success']) {
        throw new Exception($result['error']);
    }
    
    echo json_encode([
        'success' => true,
        'data' => $result['data']
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}







