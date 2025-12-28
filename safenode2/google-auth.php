<?php
/**
 * SafeNode - Google OAuth Initiator
 */

session_start();

require_once __DIR__ . '/includes/GoogleOAuth.php';

$action = $_GET['action'] ?? 'login'; // 'login' ou 'register'

// Validar action
if (!in_array($action, ['login', 'register'])) {
    $action = 'login';
}

try {
    $googleOAuth = new GoogleOAuth();
    
    // Gerar URL de autenticação com state para identificar a ação
    $authUrl = $googleOAuth->getAuthUrl($action);
    
    // Redirecionar para o Google
    header('Location: ' . $authUrl);
    exit;
    
} catch (Exception $e) {
    error_log("SafeNode Google Auth Error: " . $e->getMessage());
    $_SESSION['google_error'] = 'Erro ao iniciar autenticação com Google.';
    header('Location: ' . ($action === 'register' ? 'register.php' : 'login.php'));
    exit;
}


