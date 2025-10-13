<?php
// API genérica simplificada

// Desabilitar exibição de erros em produção
error_reporting(0);
ini_set('display_errors', 0);

// Iniciar sessão se não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Sempre retornar JSON
header('Content-Type: application/json');

// Carregar Database.class.php
$dbPath = __DIR__ . '/../includes/Database.class.php';
if (!file_exists($dbPath)) {
    echo json_encode(['success' => false, 'error' => 'Database class não encontrada']);
    exit;
}
require_once $dbPath;

$db = Database::getInstance();

// Função helper para enviar respostas JSON
function sendResponse($data = null, $error = null) {
    $response = ['success' => $error === null];
    if ($data !== null) $response['data'] = $data;
    if ($error !== null) $response['error'] = $error;
    echo json_encode($response);
    exit;
}

try {
    // Por enquanto, apenas retornar sucesso
    sendResponse(['message' => 'API genérica funcionando']);
    
} catch (Exception $e) {
    sendResponse(null, $e->getMessage());
}
?>