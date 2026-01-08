<?php
/**
 * KRON - Middleware de Autenticação
 * Verifica se usuário está autenticado e tem permissões
 */

session_start();

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/KronRBAC.php';
require_once __DIR__ . '/helpers.php';

/**
 * Verifica se usuário está autenticado
 */
function requireAuth() {
    if (!isset($_SESSION['kron_logged_in']) || $_SESSION['kron_logged_in'] !== true) {
        redirect('login.php');
    }
}

/**
 * Verifica se usuário tem permissão
 */
function requirePermission($permissionName) {
    requireAuth();
    
    $userId = $_SESSION['kron_user_id'] ?? null;
    if (!$userId) {
        redirect('login.php');
    }
    
    $rbac = new KronRBAC();
    if (!$rbac->hasPermission($userId, $permissionName)) {
        http_response_code(403);
        die('Acesso negado. Você não tem permissão para acessar este recurso.');
    }
}

/**
 * Verifica se usuário tem acesso a sistema+setor
 */
function requireSystemSectorAccess($systemId, $sectorId = null) {
    requireAuth();
    
    $userId = $_SESSION['kron_user_id'] ?? null;
    if (!$userId) {
        redirect('login.php');
    }
    
    $rbac = new KronRBAC();
    if (!$rbac->hasSystemSectorAccess($userId, $systemId, $sectorId)) {
        http_response_code(403);
        die('Acesso negado. Você não tem acesso a este sistema/setor.');
    }
}

/**
 * Obtém dados do usuário logado
 */
function getCurrentUser() {
    if (!isset($_SESSION['kron_logged_in']) || $_SESSION['kron_logged_in'] !== true) {
        return null;
    }
    
    return [
        'id' => $_SESSION['kron_user_id'] ?? null,
        'email' => $_SESSION['kron_user_email'] ?? null,
        'name' => $_SESSION['kron_user_name'] ?? null,
        'avatar' => $_SESSION['kron_user_avatar'] ?? null
    ];
}

/**
 * Obtém permissões do usuário logado
 */
function getCurrentUserPermissions() {
    $user = getCurrentUser();
    if (!$user) {
        return [];
    }
    
    $rbac = new KronRBAC();
    return $rbac->getUserPermissions($user['id']);
}

/**
 * Verifica se usuário é CEO
 */
function isCEO() {
    $user = getCurrentUser();
    if (!$user) {
        return false;
    }
    
    $rbac = new KronRBAC();
    return $rbac->isCEO($user['id']);
}

