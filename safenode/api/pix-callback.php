<?php
/**
 * SafeNode - Pix Callback (Webhook)
 * Este arquivo recebe a notificação do banco de que o Pix foi pago.
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/PixManager.php';

$db = getSafeNodeDatabase();
$pixManager = new PixManager($db);

// --- MODO SIMULAÇÃO (APENAS PARA DESENVOLVIMENTO) ---
if (isset($_GET['simulated_txid'])) {
    $txid = $_GET['simulated_txid'];
    $pixManager->completePayment($txid);
    echo json_encode(['status' => 'success', 'message' => 'Simulated payment confirmed']);
    exit;
}

// --- MODO PRODUÇÃO (WEBHOOK EFI PAY) ---
// O EFI Pay envia um POST JSON com os dados do Pix
$postdata = file_get_contents("php://input");
$data = json_decode($postdata, true);

if (!$data || !isset($data['pix'])) {
    http_response_code(400);
    exit;
}

// Processar cada Pix recebido no array
foreach ($data['pix'] as $pix) {
    if (isset($pix['txid'])) {
        $txid = $pix['txid'];
        
        // Finalizar o pagamento e fazer upgrade da conta
        $pixManager->completePayment($txid);
    }
}

// O banco espera um HTTP 200 para confirmar o recebimento do webhook
http_response_code(200);
echo json_encode(['status' => 'received']);
