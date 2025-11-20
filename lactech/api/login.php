<?php
/**
 * API LOGIN SIMPLES - LACTECH
 */

header('Content-Type: application/json; charset=utf-8');

// Carregar configuração
require_once __DIR__ . '/../includes/config_login.php';

// Verificar método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método não permitido']);
    exit;
}

// Obter dados
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';
$turnstileToken = $input['turnstile_token'] ?? '';

// Validações básicas
if (empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Email e senha são obrigatórios']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Email inválido']);
    exit;
}

// Validar Cloudflare Turnstile se estiver configurado
if (defined('TURNSTILE_SECRET_KEY') && !empty(TURNSTILE_SECRET_KEY)) {
    if (empty($turnstileToken)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Por favor, complete a verificação de segurança.']);
        exit;
    }
    
    // Validar token com Cloudflare
    $verifyUrl = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
    $verifyData = [
        'secret' => TURNSTILE_SECRET_KEY,
        'response' => $turnstileToken,
        'remoteip' => $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? ''
    ];
    
    $ch = curl_init($verifyUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($verifyData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $verifyResponse = curl_exec($ch);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        error_log("Erro ao validar Turnstile: " . $curlError);
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Erro ao validar verificação de segurança. Tente novamente.']);
        exit;
    }
    
    $verifyResult = json_decode($verifyResponse, true);
    
    if (!$verifyResult || !isset($verifyResult['success']) || !$verifyResult['success']) {
        $errorCodes = $verifyResult['error-codes'] ?? [];
        error_log("Turnstile validation failed: " . json_encode($errorCodes));
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Verificação de segurança falhou. Tente novamente.']);
        exit;
    }
}

// Verificar conexão com banco antes de tentar login
$db = getDatabase();
if (!$db) {
    http_response_code(500);
    $errorMsg = 'Erro de conexão com banco de dados';
    if (defined('ENVIRONMENT') && ENVIRONMENT === 'LOCAL') {
        $errorMsg .= '. Verifique se o MySQL está rodando e se o banco "' . DB_NAME . '" existe.';
    }
    echo json_encode([
        'success' => false,
        'error' => $errorMsg
    ]);
    exit;
}

$result = loginUser($email, $password);

if ($result['success']) {
    $redirect = 'gerente-completo.php'; 
    switch ($result['user']['role']) {
        case 'proprietario':
            $redirect = 'proprietario.php';
            break;
        case 'gerente':
            $redirect = 'gerente-completo.php';
            break;
        case 'funcionario':
        default:
            $redirect = 'funcionario.php';
            break;
    }
    
    echo json_encode([
        'success' => true,
        'user' => $result['user'],
        'redirect' => $redirect
    ]);
} else {
    $isConnectionError = strpos($result['error'] ?? '', 'conexão') !== false || 
                         strpos($result['error'] ?? '', 'banco') !== false;
    
    http_response_code($isConnectionError ? 500 : 401);
    echo json_encode([
        'success' => false,
        'error' => $result['error']
    ]);
}
?>
