<?php
/**
 * API para recuperar template HTML salvo da sessão
 */

session_start();
header('Content-Type: application/json');

// Verificar se está logado
if (!isset($_SESSION['safenode_logged_in']) || $_SESSION['safenode_logged_in'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit;
}

// Retornar template salvo (se houver)
$htmlTemplate = $_SESSION['safefig_import_template'] ?? '';

echo json_encode([
    'success' => true,
    'html_template' => $htmlTemplate
]);





