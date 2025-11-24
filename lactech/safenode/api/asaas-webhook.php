<?php
/**
 * SafeNode - Webhook Asaas
 * Recebe notificações de pagamentos da Asaas
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/AsaasAPI.php';

header('Content-Type: application/json; charset=utf-8');

// Log da requisição (para debug)
$logData = [
    'timestamp' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD'],
    'headers' => getallheaders(),
    'body' => file_get_contents('php://input')
];

error_log("Asaas Webhook: " . json_encode($logData));

try {
    $pdo = getSafeNodeDatabase();
    if (!$pdo) {
        throw new Exception('Erro ao conectar ao banco de dados');
    }
    
    // Ler dados do webhook
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Dados inválidos']);
        exit;
    }
    
    // Verificar tipo de evento
    $event = $input['event'] ?? null;
    $payment = $input['payment'] ?? null;
    
    if (!$event || !$payment) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Evento ou pagamento não encontrado']);
        exit;
    }
    
    $paymentId = $payment['id'] ?? null;
    if (!$paymentId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID do pagamento não encontrado']);
        exit;
    }
    
    // Buscar transação no banco
    $asaasAPI = new AsaasAPI($pdo);
    $transaction = $asaasAPI->getTransactionByAsaasId($paymentId);
    
    if (!$transaction) {
        // Se não encontrar, pode ser um pagamento criado externamente
        // Tentar criar registro básico
        $stmt = $pdo->prepare("
            INSERT INTO safenode_payments (
                asaas_payment_id, asaas_customer_id, amount, 
                billing_type, status, due_date, description, 
                external_reference, metadata, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
                status = VALUES(status),
                updated_at = NOW(),
                metadata = VALUES(metadata)
        ");
        
        $metadata = json_encode($payment);
        $stmt->execute([
            $paymentId,
            $payment['customer'] ?? null,
            $payment['value'] ?? 0,
            $payment['billingType'] ?? null,
            $payment['status'] ?? 'PENDING',
            $payment['dueDate'] ?? null,
            $payment['description'] ?? null,
            $payment['externalReference'] ?? null,
            $metadata
        ]);
        
        $transaction = $asaasAPI->getTransactionByAsaasId($paymentId);
    }
    
    // Atualizar status da transação
    $status = $payment['status'] ?? 'PENDING';
    $paidDate = null;
    
    if (in_array($status, ['RECEIVED', 'CONFIRMED'])) {
        $paidDate = $payment['paymentDate'] ?? date('Y-m-d H:i:s');
        
        // Atualizar data de pagamento
        $stmt = $pdo->prepare("
            UPDATE safenode_payments 
            SET paid_date = ? 
            WHERE asaas_payment_id = ?
        ");
        $stmt->execute([$paidDate, $paymentId]);
    }
    
    // Atualizar status
    $asaasAPI->updateTransactionStatus($paymentId, $status, [
        'event' => $event,
        'payment' => $payment,
        'updated_at' => date('Y-m-d H:i:s')
    ]);
    
    // Log do evento
    error_log("Asaas Webhook: Pagamento $paymentId atualizado para status $status");
    
    // Resposta de sucesso
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Webhook processado com sucesso'
    ]);
    
} catch (Exception $e) {
    error_log("Asaas Webhook Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}


