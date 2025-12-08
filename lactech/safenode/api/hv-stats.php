<?php
/**
 * SafeNode Human Verification - API de Estatísticas
 * Endpoint para obter estatísticas em tempo real via AJAX
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/HVAPIKeyManager.php';

header('Content-Type: application/json; charset=utf-8');

// Verificar autenticação
session_start();
if (!isset($_SESSION['safenode_logged_in']) || $_SESSION['safenode_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Não autenticado'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = $_SESSION['safenode_user_id'] ?? null;
if (!$userId) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Usuário não identificado'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Obter parâmetros
$apiKeyId = isset($_GET['key_id']) ? (int)$_GET['key_id'] : null;
$period = $_GET['period'] ?? '24h';

// Validar período
$validPeriods = ['1h', '24h', '7d', '30d'];
if (!in_array($period, $validPeriods)) {
    $period = '24h';
}

if (!$apiKeyId) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'API key ID é obrigatório'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Validar que a API key pertence ao usuário
$apiKeys = HVAPIKeyManager::getUserKeys($userId);
$keyExists = false;
foreach ($apiKeys as $key) {
    if ($key['id'] === $apiKeyId) {
        $keyExists = true;
        break;
    }
}

if (!$keyExists) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'API key não encontrada ou sem permissão'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Obter estatísticas
$stats = HVAPIKeyManager::getAllStats($apiKeyId, $userId, $period);

echo json_encode([
    'success' => true,
    'data' => $stats,
    'period' => $period,
    'timestamp' => time()
], JSON_UNESCAPED_UNICODE);








