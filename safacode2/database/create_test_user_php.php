<?php
/**
 * SafeCode IDE - Script PHP para criar usuário de teste
 * Execute este arquivo via navegador ou linha de comando
 * URL: http://localhost/safecode/database/create_test_user_php.php
 */

require_once __DIR__ . '/../api/config.php';

// Dados do usuário de teste
$email = 'teste@safecode.test';
$password = 'teste123';
$name = 'Usuário Teste';

echo "<h2>SafeCode IDE - Criar Usuário de Teste</h2>";

$db = getDatabase();

if (!$db) {
    die("<p style='color: red;'>❌ Erro ao conectar ao banco de dados!</p>");
}

try {
    // Verificar se usuário já existe
    $stmt = $db->prepare("SELECT id, email, name FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $existingUser = $stmt->fetch();
    
    if ($existingUser) {
        echo "<p style='color: orange;'>⚠️ Usuário já existe!</p>";
        echo "<p><strong>ID:</strong> {$existingUser['id']}</p>";
        echo "<p><strong>Email:</strong> {$existingUser['email']}</p>";
        echo "<p><strong>Nome:</strong> {$existingUser['name']}</p>";
        echo "<p>Você pode fazer login com:</p>";
        echo "<ul>";
        echo "<li><strong>Email:</strong> {$email}</li>";
        echo "<li><strong>Senha:</strong> {$password}</li>";
        echo "</ul>";
    } else {
        // Criar hash da senha
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        // Inserir usuário
        $stmt = $db->prepare("
            INSERT INTO users (email, password_hash, name, provider, is_active)
            VALUES (?, ?, ?, 'email', TRUE)
        ");
        $stmt->execute([$email, $passwordHash, $name]);
        
        $userId = $db->lastInsertId();
        
        echo "<p style='color: green;'>✅ Usuário criado com sucesso!</p>";
        echo "<p><strong>ID:</strong> {$userId}</p>";
        echo "<p><strong>Email:</strong> {$email}</p>";
        echo "<p><strong>Nome:</strong> {$name}</p>";
        echo "<hr>";
        echo "<h3>Credenciais de Login:</h3>";
        echo "<ul>";
        echo "<li><strong>Email:</strong> {$email}</li>";
        echo "<li><strong>Senha:</strong> {$password}</li>";
        echo "</ul>";
        
        // Criar configurações padrão do usuário
        $defaultEditor = json_encode([
            'fontSize' => 14,
            'fontFamily' => 'Monaco, Consolas, monospace',
            'theme' => 'dark',
            'wordWrap' => true,
            'lineNumbers' => true,
            'tabSize' => 2,
            'minimap' => true,
            'autoSave' => true
        ]);
        
        $defaultIDE = json_encode([
            'sidebarOpen' => true,
            'terminalOpen' => false,
            'previewOpen' => true
        ]);
        
        $stmt = $db->prepare("
            INSERT INTO user_settings (user_id, editor_settings, ide_settings, keybindings, extensions)
            VALUES (?, ?, ?, '[]', '[]')
        ");
        $stmt->execute([$userId, $defaultEditor, $defaultIDE]);
        
        echo "<p style='color: green;'>✅ Configurações padrão criadas!</p>";
    }
    
    echo "<hr>";
    echo "<p><a href='/safecode/login'>→ Ir para página de login</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Erro: " . htmlspecialchars($e->getMessage()) . "</p>";
}

?>

