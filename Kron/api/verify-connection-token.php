<?php
/**
 * KRON API - Validar Token de Conexão
 * Esta API é chamada pelos sistemas destino (SafeNode/LacTech)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/KronConnectionManager.php';

// Verificar método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['valid' => false, 'error' => 'Método não permitido']);
    exit;
}

// Obter dados do POST
$input = json_decode(file_get_contents('php://input'), true);

$token = $input['token'] ?? $_POST['token'] ?? '';
$systemName = $input['system_name'] ?? $_POST['system_name'] ?? '';
$systemUserId = $input['system_user_id'] ?? $_POST['system_user_id'] ?? null;
$systemUserEmail = $input['system_user_email'] ?? $_POST['system_user_email'] ?? '';

if (empty($token) || empty($systemName) || !$systemUserId || empty($systemUserEmail)) {
    http_response_code(400);
    echo json_encode(['valid' => false, 'error' => 'Dados incompletos']);
    exit;
}

if (!in_array($systemName, ['safenode', 'lactech'])) {
    http_response_code(400);
    echo json_encode(['valid' => false, 'error' => 'Sistema inválido']);
    exit;
}

try {
    $connectionManager = new KronConnectionManager();
    $result = $connectionManager->verifyConnectionToken($token, $systemName, $systemUserId, $systemUserEmail);
    
    if ($result['valid']) {
        http_response_code(200);
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
    
} catch (Exception $e) {
    error_log("KRON Verify Token Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['valid' => false, 'error' => 'Erro interno']);
}

