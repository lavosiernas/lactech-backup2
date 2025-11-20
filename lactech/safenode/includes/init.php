<?php
/**
 * SafeNode - Inicialização
 * Carrega Router e proteção de URLs quando logado
 */

// Iniciar sessão apenas se não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar se está logado e inicializar Router
if (isset($_SESSION['safenode_logged_in']) && $_SESSION['safenode_logged_in'] === true) {
    require_once __DIR__ . '/Router.php';
    SafeNodeRouter::init();
}

