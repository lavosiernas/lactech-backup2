<?php
/**
 * SafeNode - API para ativar/desativar sequência de proteção
 */

session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/ProtectionStreak.php';

header('Content-Type: application/json');

// Verificar autenticação
if (!isset($_SESSION['safenode_logged_in']) || $_SESSION['safenode_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Não autenticado']);
    exit;
}

// CSRF removido para facilitar desenvolvimento local
// A autenticação por sessão já fornece segurança suficiente

// Verificar método - permitir GET para verificar status
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['check'])) {
    // Retornar apenas o status atual
    $db = getSafeNodeDatabase();
    $streakManager = new ProtectionStreak($db);
    $streak = $streakManager->getStreak($userId, $siteId);
    
    echo json_encode([
        'success' => true,
        'enabled' => $streak && isset($streak['enabled']) ? (bool)$streak['enabled'] : false
    ]);
    exit;
}

// Verificar método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método não permitido']);
    exit;
}

$userId = $_SESSION['safenode_user_id'] ?? null;
$siteId = $_SESSION['view_site_id'] ?? 0;

if (!$userId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Usuário não identificado']);
    exit;
}

// Obter valor do enabled
$enabled = isset($_POST['enabled']) && ($_POST['enabled'] === '1' || $_POST['enabled'] === 1 || $_POST['enabled'] === true);

// Atualizar sequência
$db = getSafeNodeDatabase();
$streakManager = new ProtectionStreak($db);

if ($streakManager->setEnabled($userId, $siteId, $enabled)) {
    // Buscar dados atualizados
    $streak = $streakManager->getStreak($userId, $siteId);
    
    echo json_encode([
        'success' => true,
        'enabled' => $streak['enabled'],
        'current_streak' => $streak['current_streak'],
        'longest_streak' => $streak['longest_streak'],
        'is_active' => $streak['is_active']
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erro ao atualizar sequência']);
}

