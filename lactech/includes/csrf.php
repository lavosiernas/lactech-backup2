<?php
/**
 * Sistema de Proteção CSRF - LACTECH
 * Gera e valida tokens CSRF para formulários críticos
 */

// Garantir que a sessão está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Gerar ou obter token CSRF da sessão
 */
function getCsrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Gerar campo hidden para formulário HTML
 */
function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Validar token CSRF
 * @param string|null $token Token a ser validado (se null, busca em $_POST ou $_GET)
 * @return bool True se válido, false caso contrário
 */
function validateCsrfToken($token = null) {
    if ($token === null) {
        // Buscar token em POST ou GET
        $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? null;
    }
    
    if ($token === null || !isset($_SESSION['csrf_token'])) {
        return false;
    }
    
    // Comparação segura para evitar timing attacks
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Validar token CSRF e retornar erro se inválido
 * Útil para APIs e endpoints
 */
function requireCsrfToken() {
    if (!validateCsrfToken()) {
        http_response_code(403);
        if (isAjax() || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Token CSRF inválido ou ausente'
            ]);
        } else {
            die('Token CSRF inválido ou ausente. Por favor, recarregue a página e tente novamente.');
        }
        exit;
    }
}

/**
 * Regenerar token CSRF (útil após uso ou logout)
 */
function regenerateCsrfToken() {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}

/**
 * Verificar se é requisição AJAX
 */
if (!function_exists('isAjax')) {
    function isAjax() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }
}

