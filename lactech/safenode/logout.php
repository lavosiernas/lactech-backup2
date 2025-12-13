<?php
/**
 * SafeNode - Logout Simples
 * Desconecta o usuário sem necessidade de código
 */

session_start();

// Registrar logout no log de atividades (se possível)
if (isset($_SESSION['safenode_user_id'])) {
    require_once __DIR__ . '/includes/config.php';
    require_once __DIR__ . '/includes/init.php';
    
    try {
        $db = getSafeNodeDatabase();
        if ($db) {
            $userId = $_SESSION['safenode_user_id'];
            $stmt = $db->prepare("INSERT INTO safenode_activity_logs (user_id, action, details, ip_address, user_agent) VALUES (?, 'logout', 'Logout simples realizado', ?, ?)");
            $stmt->execute([
                $userId,
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
        }
    } catch (Exception $e) {
        // Ignorar erros de log, apenas fazer logout
        error_log("SafeNode Logout Log Error: " . $e->getMessage());
    }
}

// Destruir todas as variáveis de sessão do SafeNode
$_SESSION = array();

// Destruir o cookie de sessão
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destruir a sessão
session_destroy();

// Limpar qualquer cache
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Redirecionar para a página de login do SafeNode
header("Location: login.php");
exit;
?>







