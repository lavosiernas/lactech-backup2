<?php
/**
 * SafeNode reCAPTCHA - Init Endpoint
 * 
 * Gera um challenge token para o cliente iniciar o reCAPTCHA
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/SafeNodeReCAPTCHA.php';

// CORS
$origin = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? '';
$validOrigin = null;

if (!empty($origin)) {
    $parsedOrigin = parse_url($origin);
    if ($parsedOrigin && isset($parsedOrigin['scheme']) && isset($parsedOrigin['host'])) {
        $validOrigin = $parsedOrigin['scheme'] . '://' . $parsedOrigin['host'];
        if (isset($parsedOrigin['port'])) {
            $validOrigin .= ':' . $parsedOrigin['port'];
        }
    }
}

if ($validOrigin) {
    header('Access-Control-Allow-Origin: ' . $validOrigin);
} else {
    header('Access-Control-Allow-Origin: *');
}

header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept, X-API-Key, x-api-key');
header('Access-Control-Max-Age: 86400');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Método não permitido'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$apiKey = $input['api_key'] ?? $_SERVER['HTTP_X_API_KEY'] ?? '';
$remoteIp = $_SERVER['REMOTE_ADDR'] ?? '';

if (empty($apiKey)) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'API key é obrigatória'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    SafeNodeReCAPTCHA::init();
    $result = SafeNodeReCAPTCHA::generateChallenge($apiKey, $remoteIp);

    if (!$result['success']) {
        http_response_code(400);
    }

    echo json_encode($result, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro interno: ' . $e->getMessage(),
        'debug' => defined('ENVIRONMENT') && ENVIRONMENT === 'LOCAL' ? $e->getTraceAsString() : null
    ], JSON_UNESCAPED_UNICODE);
    error_log("SafeNode reCAPTCHA Init Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
}

