<?php
/**
 * KRON - Logout
 */

session_start();

require_once __DIR__ . '/includes/config.php';

// Se estiver logado, remover sessão do banco
if (isset($_SESSION['kron_user_id']) && isset($_SESSION['kron_session_token'])) {
    $pdo = getKronDatabase();
    
    if ($pdo) {
        $stmt = $pdo->prepare("
            DELETE FROM kron_user_sessions 
            WHERE user_id = ? AND session_token = ?
        ");
        $stmt->execute([$_SESSION['kron_user_id'], $_SESSION['kron_session_token']]);
    }
}

// Destruir sessão PHP
$_SESSION = [];
session_destroy();

// Redirecionar para login
header('Location: login.php');
exit;

