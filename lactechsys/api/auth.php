<?php
// =====================================================
// API DE AUTENTICAÇÃO - MYSQL
// =====================================================
// Sistema de login para MySQL/Lagoa do Mato
// =====================================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Permitir requisições OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../includes/config_mysql.php';
require_once '../includes/database.php';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Obter dados do POST
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['email']) || !isset($input['password'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Email e senha são obrigatórios'
            ]);
            exit;
        }
        
        $email = sanitize($input['email']);
        $password = $input['password'];
        
        // Verificar credenciais
        $user = verifyLogin($email, $password);
        
        if ($user) {
            // Gerar token simples (em produção, use JWT)
            $token = md5($user['id'] . time());
            
            // Remover senha dos dados retornados
            unset($user['password']);
            
            echo json_encode([
                'success' => true,
                'user' => $user,
                'token' => $token,
                'message' => 'Login realizado com sucesso'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Email ou senha incorretos'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Método não permitido'
        ]);
    }
} catch (Exception $e) {
    error_log('Erro na API de autenticação: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor'
    ]);
}
?>
