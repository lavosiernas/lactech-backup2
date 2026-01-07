<?php
/**
 * KRON API - Cancelar Token de Conexão
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

// Verificar método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método não permitido']);
    exit;
}

// Obter token
$token = $_POST['token'] ?? '';

if (empty($token)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Token não fornecido']);
    exit;
}

try {
    $pdo = getKronDatabase();
    
    // Verificar se o token pertence ao usuário
    $stmt = $pdo->prepare("
        SELECT id, status FROM kron_connection_tokens 
        WHERE token = ? AND kron_user_id = ? AND status = 'pending'
    ");
    $stmt->execute([$token, $kronUserId]);
    $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$tokenData) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Token não encontrado ou já foi usado']);
        exit;
    }
    
    // Marcar token como cancelado (usando status 'expired' ou criando um novo status)
    // Por enquanto, vamos usar 'expired' para cancelar
    $stmt = $pdo->prepare("
        UPDATE kron_connection_tokens 
        SET status = 'expired' 
        WHERE id = ?
    ");
    $stmt->execute([$tokenData['id']]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Token cancelado com sucesso'
    ]);
    
} catch (Exception $e) {
    error_log("KRON Cancel Token Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erro interno']);
}

