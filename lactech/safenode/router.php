<?php
/**
 * SafeNode - Router Principal
 * Processa rotas protegidas e redireciona para arquivos corretos
 */

// Iniciar sessão apenas se não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar se está logado
if (!isset($_SESSION['safenode_logged_in']) || $_SESSION['safenode_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/includes/Router.php';

// Obter parâmetros da URL
$route = $_GET['route'] ?? '';
$id = $_GET['id'] ?? '';
$timestamp = $_GET['ts'] ?? '';

// Reconstruir caminho completo
$fullPath = "safenode-{$route}-{$id}-{$timestamp}";

// Debug: verificar mapeamentos na sessão
if (defined('ENVIRONMENT') && ENVIRONMENT === 'LOCAL') {
    error_log("SafeNode Router Debug:");
    error_log("  Full Path: $fullPath");
    error_log("  Route: $route, ID: $id, TS: $timestamp");
    error_log("  Session ID: " . session_id());
    error_log("  URL Map Keys: " . (isset($_SESSION['safenode_url_map']) ? implode(', ', array_keys($_SESSION['safenode_url_map'])) : 'Nenhum'));
}

// Verificar se existe na sessão
if (isset($_SESSION['safenode_url_map'][$fullPath])) {
    $mapping = $_SESSION['safenode_url_map'][$fullPath];
    
    // Verificar expiração
    if ($mapping['expires'] < time()) {
        unset($_SESSION['safenode_url_map'][$fullPath]);
        header('Location: login.php');
        exit;
    }
    
    // Validar sessão
    if (isset($mapping['session_id']) && $mapping['session_id'] !== session_id()) {
        unset($_SESSION['safenode_url_map'][$fullPath]);
        header('Location: login.php');
        exit;
    }
    
    $file = $mapping['file'];
    $params = $mapping['params'] ?? [];
    
    // Adicionar parâmetros GET se houver
    if (!empty($params)) {
        foreach ($params as $key => $value) {
            $_GET[$key] = $value;
        }
    }
    
    // Preservar outros parâmetros GET da URL
    foreach ($_GET as $key => $value) {
        if (!in_array($key, ['route', 'id', 'ts'])) {
            $params[$key] = $value;
        }
    }
    
    // Incluir arquivo
    $filePath = __DIR__ . '/' . $file;
    if (file_exists($filePath)) {
        // Limpar qualquer output anterior
        if (ob_get_level()) {
            ob_clean();
        }
        require_once $filePath;
        exit;
    } else {
        // Log do erro para debug
        error_log("SafeNode Router: Arquivo não encontrado: $filePath");
        error_log("SafeNode Router: Mapeamento: " . print_r($mapping, true));
    }
}

// Se não encontrou, redirecionar para login
error_log("SafeNode Router: Rota não encontrada na sessão. Path: $fullPath");
header('Location: login.php');
exit;

