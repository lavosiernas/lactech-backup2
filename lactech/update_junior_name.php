<?php
/**
 * Script para atualizar o nome de "Junior Silva" para "Junior Alves"
 * Execute este arquivo no navegador ou via linha de comando
 */

// Iniciar sessão se necessário
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Carregar configuração do banco
require_once __DIR__ . '/includes/config_mysql.php';
require_once __DIR__ . '/includes/Database.class.php';

try {
    $db = Database::getInstance();
    
    // Atualizar nome do usuário
    $sql = "UPDATE users 
            SET name = 'Junior Alves', 
                updated_at = NOW()
            WHERE name = 'Junior Silva' 
               OR (email = 'Junior@lactech.com' AND name LIKE 'Junior%')";
    
    $result = $db->query($sql);
    
    // Verificar se foi atualizado
    $user = $db->query("
        SELECT id, name, email, updated_at 
        FROM users 
        WHERE email = 'Junior@lactech.com'
    ");
    
    if (!empty($user)) {
        $userData = $user[0];
        echo json_encode([
            'success' => true,
            'message' => 'Nome atualizado com sucesso!',
            'user' => [
                'id' => $userData['id'],
                'name' => $userData['name'],
                'email' => $userData['email'],
                'updated_at' => $userData['updated_at']
            ]
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Usuário não encontrado'
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao atualizar: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
?>































