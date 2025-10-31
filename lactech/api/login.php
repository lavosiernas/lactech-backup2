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

// Fazer login
$result = loginUser($email, $password);

if ($result['success']) {
    // Determinar página de redirecionamento
    $redirect = 'gerente-completo.php'; // padrão
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
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => $result['error']
    ]);
}
?>
