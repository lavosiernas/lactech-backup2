<?php
/**
 * SafeNode - API para fechar modal de atualização
 * Remove a flag de sessão que indica que o modal deve ser mostrado
 */

session_start();
header('Content-Type: application/json; charset=UTF-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Verificar se está logado
if (!isset($_SESSION['safenode_logged_in']) || $_SESSION['safenode_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Não autorizado'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Remover flag da sessão
unset($_SESSION['show_update_modal']);

header('Content-Type: application/json');
echo json_encode(['success' => true]);
?>

