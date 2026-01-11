<?php
/**
 * SafeNode - Check Pix Status API
 */

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['safenode_logged_in'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/PixManager.php';

$txid = $_GET['txid'] ?? null;
if (!$txid) {
    echo json_encode(['error' => 'Missing txid']);
    exit;
}

$db = getSafeNodeDatabase();
$pixManager = new PixManager($db);

$status = $pixManager->checkStatus($txid);

echo json_encode([
    'status' => $status,
    'txid' => $txid
]);
