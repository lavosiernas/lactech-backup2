<?php
/**
 * SafeNode Human Verification SDK - Init Endpoint
 * 
 * Endpoint para inicializar o desafio de verificação humana
 * Retorna um token único que deve ser usado no formulário
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/HVAPIKeyManager.php';

// CORS: Configurar headers antes de qualquer output
$origin = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? '';

// Função para validar e retornar origin válido
function getValidOrigin($origin) {
    if (empty($origin)) {
        return null;
    }
    
    // Validar origin (remover path, manter apenas protocolo + domínio)
    $parsedOrigin = parse_url($origin);
    if ($parsedOrigin && isset($parsedOrigin['scheme']) && isset($parsedOrigin['host'])) {
        $validOrigin = $parsedOrigin['scheme'] . '://' . $parsedOrigin['host'];
        if (isset($parsedOrigin['port'])) {
            $validOrigin .= ':' . $parsedOrigin['port'];
        }
        return $validOrigin;
    }
    
    return null;
}

// Configurar CORS headers - permitir qualquer origem em desenvolvimento
$validOrigin = getValidOrigin($origin);
if ($validOrigin) {
    header('Access-Control-Allow-Origin: ' . $validOrigin);
} else {
    // Em desenvolvimento, permitir qualquer origem
    // Em produção, isso deve ser mais restritivo
    header('Access-Control-Allow-Origin: *');
}

header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept, X-API-Key, x-api-key');
header('Access-Control-Max-Age: 86400'); // Cache preflight por 24 horas

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(200);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

// Obter API key
$apiKey = $_GET['api_key'] ?? $_POST['api_key'] ?? $_SERVER['HTTP_X_API_KEY'] ?? '';
$ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

// Debug (apenas em desenvolvimento)
$isLocal = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1', '::1']) || 
           strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false ||
           strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false;
if ($isLocal || (defined('ENVIRONMENT') && ENVIRONMENT === 'LOCAL')) {
    error_log("SafeNode Init Debug:");
    error_log("  API Key: " . ($apiKey ? substr($apiKey, 0, 10) . '...' : 'não fornecida'));
    error_log("  Origin: " . ($origin ?: 'não fornecido'));
    error_log("  IP: $ipAddress");
    error_log("  User Agent: " . substr($userAgent, 0, 50));
}

if (empty($apiKey)) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'API key é obrigatória'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Validar API key com origem
$keyData = HVAPIKeyManager::validateKey($apiKey, $origin);
if (!$keyData) {
    $errorMsg = 'API key inválida ou inativa';
    if (!empty($origin)) {
        $errorMsg .= ' (verifique se o domínio está permitido)';
    }
    HVAPIKeyManager::logAttempt(null, $ipAddress, $userAgent, $origin, 'failed', 'API key inválida');
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => $errorMsg,
        'debug' => ($isLocal || (defined('ENVIRONMENT') && ENVIRONMENT === 'LOCAL')) ? [
            'origin' => $origin,
            'api_key_prefix' => substr($apiKey, 0, 10)
        ] : null
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

// Log de tentativa bem-sucedida
HVAPIKeyManager::logAttempt($keyData['id'], $ipAddress, $userAgent, $origin, 'init');

// Criar sessão específica para esta API key
$sessionId = 'safenode_hv_' . md5($apiKey . $_SERVER['REMOTE_ADDR']);
session_id($sessionId);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Obter max_token_age da API key (padrão: 1 hora)
$maxTokenAge = (int)($keyData['max_token_age'] ?? 3600);

// Gerar token único com nonce para prevenir replay attacks
if (empty($_SESSION['safenode_hv_token'])) {
    $_SESSION['safenode_hv_token'] = bin2hex(random_bytes(32));
    $_SESSION['safenode_hv_nonce'] = bin2hex(random_bytes(16));
    $_SESSION['safenode_hv_time'] = time();
    $_SESSION['safenode_hv_ip'] = $ipAddress;
    $_SESSION['safenode_hv_user_agent'] = $userAgent;
    $_SESSION['safenode_hv_api_key'] = $apiKey;
    $_SESSION['safenode_hv_max_age'] = $maxTokenAge;
}

// Retornar token e nonce
echo json_encode([
    'success' => true,
    'token' => $_SESSION['safenode_hv_token'],
    'nonce' => $_SESSION['safenode_hv_nonce'] ?? '',
    'timestamp' => $_SESSION['safenode_hv_time'],
    'max_age' => $maxTokenAge
], JSON_UNESCAPED_UNICODE);

