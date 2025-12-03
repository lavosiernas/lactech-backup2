<?php
/**
 * SafeNode Human Verification SDK - Validate Endpoint
 * 
 * Endpoint para validar a verificação humana
 * Recebe token e flag de JavaScript e valida a requisição
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/HVAPIKeyManager.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Apenas POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Método não permitido'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Obter dados do POST
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$tokenPost = $input['token'] ?? '';
$noncePost = $input['nonce'] ?? '';
$jsFlag = $input['js_enabled'] ?? '';
$apiKey = $input['api_key'] ?? $_SERVER['HTTP_X_API_KEY'] ?? '';
$ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$origin = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? '';

// Validar API key
if (empty($apiKey)) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'API key é obrigatória'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$keyData = HVAPIKeyManager::validateKey($apiKey, $origin);
if (!$keyData) {
    HVAPIKeyManager::logAttempt(null, $ipAddress, $userAgent, $origin, 'failed', 'API key inválida');
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'API key inválida ou inativa'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Verificar rate limit
$rateLimit = HVAPIKeyManager::checkRateLimit($keyData['id'], $ipAddress);
if (!$rateLimit['allowed']) {
    HVAPIKeyManager::logAttempt($keyData['id'], $ipAddress, $userAgent, $origin, 'failed', 'Rate limit excedido');
    http_response_code(429);
    header('X-RateLimit-Limit: ' . $rateLimit['limit']);
    header('X-RateLimit-Remaining: 0');
    header('X-RateLimit-Reset: ' . strtotime($rateLimit['reset_at']));
    echo json_encode([
        'success' => false,
        'error' => 'Limite de requisições excedido. Tente novamente em alguns instantes.',
        'rate_limit' => $rateLimit
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Criar sessão específica para esta API key
$sessionId = 'safenode_hv_' . md5($apiKey . $_SERVER['REMOTE_ADDR']);
session_id($sessionId);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar JavaScript primeiro (obrigatório)
if ($jsFlag !== '1') {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'É necessário habilitar JavaScript para fazer esta ação com segurança.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$tokenSession = $_SESSION['safenode_hv_token'] ?? null;
$nonceSession = $_SESSION['safenode_hv_nonce'] ?? null;
$timeSession = $_SESSION['safenode_hv_time'] ?? 0;
$ipSession = $_SESSION['safenode_hv_ip'] ?? '';
$userAgentSession = $_SESSION['safenode_hv_user_agent'] ?? '';
$maxTokenAge = (int)($_SESSION['safenode_hv_max_age'] ?? $keyData['max_token_age'] ?? 3600);

// Regras básicas de segurança
$now = time();
$minElapsed = 1;             // mínimo de 1s entre carregar página e enviar
$maxElapsed = $maxTokenAge;  // desafio expira conforme configurado (padrão: 1 hora)

// Verificar se a sessão pertence à API key correta
if (($_SESSION['safenode_hv_api_key'] ?? '') !== $apiKey) {
    // Sessão não corresponde à API key, criar nova
    $_SESSION['safenode_hv_token'] = bin2hex(random_bytes(32));
    $_SESSION['safenode_hv_nonce'] = bin2hex(random_bytes(16));
    $_SESSION['safenode_hv_time'] = time();
    $_SESSION['safenode_hv_ip'] = $ipAddress;
    $_SESSION['safenode_hv_user_agent'] = $userAgent;
    $_SESSION['safenode_hv_api_key'] = $apiKey;
    $_SESSION['safenode_hv_max_age'] = $maxTokenAge;
    $tokenSession = $_SESSION['safenode_hv_token'];
    $nonceSession = $_SESSION['safenode_hv_nonce'];
    $timeSession = $_SESSION['safenode_hv_time'];
} else {
    // Se não existe sessão ou expirou, recria automaticamente
    $sessionExpired = false;
    if (!$tokenSession || !$timeSession) {
        $_SESSION['safenode_hv_token'] = bin2hex(random_bytes(32));
        $_SESSION['safenode_hv_nonce'] = bin2hex(random_bytes(16));
        $_SESSION['safenode_hv_time'] = time();
        $_SESSION['safenode_hv_ip'] = $ipAddress;
        $_SESSION['safenode_hv_user_agent'] = $userAgent;
        $_SESSION['safenode_hv_max_age'] = $maxTokenAge;
        $tokenSession = $_SESSION['safenode_hv_token'];
        $nonceSession = $_SESSION['safenode_hv_nonce'];
        $timeSession = $_SESSION['safenode_hv_time'];
    } else {
        $elapsed = $now - (int)$timeSession;
        if ($elapsed > $maxElapsed) {
            $sessionExpired = true;
            $_SESSION['safenode_hv_token'] = bin2hex(random_bytes(32));
            $_SESSION['safenode_hv_nonce'] = bin2hex(random_bytes(16));
            $_SESSION['safenode_hv_time'] = time();
            $_SESSION['safenode_hv_ip'] = $ipAddress;
            $_SESSION['safenode_hv_user_agent'] = $userAgent;
            $tokenSession = $_SESSION['safenode_hv_token'];
            $nonceSession = $_SESSION['safenode_hv_nonce'];
            $timeSession = $_SESSION['safenode_hv_time'];
        }
    }
}

// Validar nonce para prevenir replay attacks
if (!empty($noncePost) && !empty($nonceSession)) {
    if (!hash_equals($nonceSession, $noncePost)) {
        HVAPIKeyManager::logAttempt($keyData['id'], $ipAddress, $userAgent, $origin, 'suspicious', 'Nonce inválido');
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Token inválido. Recarregue a página.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    // Marcar nonce como usado (prevenir reuso)
    unset($_SESSION['safenode_hv_nonce']);
}

// Validar User Agent (proteção adicional)
if (!empty($userAgentSession) && $userAgentSession !== $userAgent && !$sessionExpired) {
    HVAPIKeyManager::logAttempt($keyData['id'], $ipAddress, $userAgent, $origin, 'suspicious', 'User Agent alterado');
    // Não bloquear, apenas logar (pode ser navegador atualizado)
}

// Se a sessão foi recriada, aceita se JS está habilitado
if ($sessionExpired || !hash_equals($tokenSession, (string)$tokenPost)) {
    if ($jsFlag === '1') {
        // Aceita se JavaScript está habilitado (sessão foi recriada)
        echo json_encode([
            'success' => true,
            'valid' => true,
            'message' => 'Verificação válida'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// Verificar tempo mínimo (proteção contra bots)
$elapsed = $now - (int)$timeSession;
if ($elapsed < $minElapsed) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Verificação muito rápida. Aguarde alguns segundos e tente novamente.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Verificação leve de IP
$currentIp = $_SERVER['REMOTE_ADDR'] ?? '';
if ($ipSession && $currentIp && $ipSession !== $currentIp && !$sessionExpired) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Mudança de rede detectada. Recarregue a página por segurança.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Validação bem-sucedida
HVAPIKeyManager::logAttempt($keyData['id'], $ipAddress, $userAgent, $origin, 'validate');
echo json_encode([
    'success' => true,
    'valid' => true,
    'message' => 'Verificação válida'
], JSON_UNESCAPED_UNICODE);

