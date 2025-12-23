<?php
/**
 * SafeNode Mail - API de Envio de E-mails
 * POST /api/mail/send
 * 
 * Headers:
 * Authorization: Bearer {token}
 * 
 * Body (JSON ou FormData):
 * {
 *   "to": "destinatario@email.com",
 *   "subject": "Assunto do e-mail",
 *   "html": "<h1>Conteúdo HTML</h1>",
 *   "text": "Conteúdo texto (opcional)",
 *   "template": "nome-do-template (opcional)",
 *   "variables": {
 *     "nome": "João",
 *     "codigo": "123456"
 *   }
 * }
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Apenas POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método não permitido']);
    exit;
}

// Carregar dependências
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/MailService.php';

// Obter token do header Authorization
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$token = '';

if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
    $token = $matches[1];
} else {
    // Tentar obter do POST também
    $token = $_POST['token'] ?? $_GET['token'] ?? '';
}

if (empty($token)) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Token de autenticação não fornecido',
        'error_code' => 'MISSING_TOKEN'
    ]);
    exit;
}

// Obter dados do corpo da requisição
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Se não for JSON, tentar FormData
if (!$data) {
    $data = $_POST;
}

// Validar campos obrigatórios
$to = $data['to'] ?? '';
$subject = $data['subject'] ?? '';
$html = $data['html'] ?? $data['html_content'] ?? '';
$text = $data['text'] ?? $data['text_content'] ?? null;
$templateName = $data['template'] ?? $data['template_name'] ?? null;
$variables = $data['variables'] ?? [];

if (empty($to) || empty($subject)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Campos obrigatórios: to, subject',
        'error_code' => 'MISSING_FIELDS'
    ]);
    exit;
}

// Conectar ao banco
$db = getSafeNodeDatabase();
if (!$db) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao conectar ao banco de dados',
        'error_code' => 'DATABASE_ERROR'
    ]);
    exit;
}

// Carregar projeto pelo token
$mailService = new MailService($db);
if (!$mailService->loadProjectByToken($token)) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Token inválido ou projeto inativo',
        'error_code' => 'INVALID_TOKEN'
    ]);
    exit;
}

// Se usar template, carregar e processar
if ($templateName) {
    $template = $mailService->loadTemplate($templateName);
    if ($template) {
        $subject = $mailService->processTemplate($template['subject'], $variables);
        $html = $mailService->processTemplate($template['html_content'], $variables);
        if ($template['text_content']) {
            $text = $mailService->processTemplate($template['text_content'], $variables);
        }
    } else {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Template não encontrado',
            'error_code' => 'TEMPLATE_NOT_FOUND'
        ]);
        exit;
    }
} else if (!empty($html)) {
    // Processar variáveis no HTML mesmo sem template
    $html = $mailService->processTemplate($html, $variables);
    if ($text) {
        $text = $mailService->processTemplate($text, $variables);
    }
}

// Enviar e-mail
$result = $mailService->send($to, $subject, $html, $text, $templateName);

// Retornar resposta
if ($result['success']) {
    http_response_code(200);
} else {
    // Códigos de erro específicos
    $errorCode = $result['error_code'] ?? 'UNKNOWN_ERROR';
    if ($errorCode === 'LIMIT_EXCEEDED') {
        http_response_code(429); // Too Many Requests
    } else if ($errorCode === 'INVALID_EMAIL') {
        http_response_code(400); // Bad Request
    } else {
        http_response_code(500); // Internal Server Error
    }
}

echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);




