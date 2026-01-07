<?php
/**
 * KRON - Teste de Conexão com Banco de Dados
 */

require_once __DIR__ . '/includes/config.php';

echo "<h2>Teste de Conexão - KRON</h2>";

// Testar conexão
$pdo = getKronDatabase();

if (!$pdo) {
    echo "<p style='color: red;'>❌ ERRO: Não foi possível conectar ao banco de dados</p>";
    echo "<p>Verifique as configurações em <code>includes/config.php</code></p>";
    exit;
}

echo "<p style='color: green;'>✅ Conexão com banco de dados estabelecida!</p>";

// Verificar se o banco existe
try {
    $stmt = $pdo->query("SELECT DATABASE() as db");
    $db = $stmt->fetch();
    echo "<p><strong>Banco de dados atual:</strong> " . ($db['db'] ?? 'Nenhum') . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro ao verificar banco: " . $e->getMessage() . "</p>";
}

// Verificar se a tabela existe
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'kron_users'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✅ Tabela <code>kron_users</code> existe</p>";
        
        // Verificar usuários
        $stmt = $pdo->query("SELECT id, email, name, is_active, email_verified FROM kron_users");
        $users = $stmt->fetchAll();
        
        echo "<h3>Usuários cadastrados:</h3>";
        if (count($users) > 0) {
            echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>Email</th><th>Nome</th><th>Ativo</th><th>Email Verificado</th></tr>";
            foreach ($users as $user) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($user['id']) . "</td>";
                echo "<td>" . htmlspecialchars($user['email']) . "</td>";
                echo "<td>" . htmlspecialchars($user['name']) . "</td>";
                echo "<td>" . ($user['is_active'] ? 'Sim' : 'Não') . "</td>";
                echo "<td>" . ($user['email_verified'] ? 'Sim' : 'Não') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color: orange;'>⚠️ Nenhum usuário cadastrado</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Tabela <code>kron_users</code> não existe</p>";
        echo "<p>Execute o script <code>database/create_kron_ecosystem.sql</code></p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro ao verificar tabela: " . $e->getMessage() . "</p>";
}

// Testar hash da senha
echo "<h3>Teste de Hash da Senha:</h3>";
$testPassword = 'admin123';
$testHash = '$2y$10$98zWMIufXE/lFi5t07.Wc.x0G86AaTsN9mzpMGbhUX0WIqKVtv/qi';

if (password_verify($testPassword, $testHash)) {
    echo "<p style='color: green;'>✅ Hash da senha está correto!</p>";
} else {
    echo "<p style='color: red;'>❌ Hash da senha está incorreto!</p>";
}

// Verificar hash do admin no banco
try {
    $stmt = $pdo->prepare("SELECT email, password FROM kron_users WHERE email = ?");
    $stmt->execute(['admin@kron.sbs']);
    $admin = $stmt->fetch();
    
    if ($admin) {
        echo "<h3>Verificação do Admin:</h3>";
        echo "<p><strong>Email:</strong> " . htmlspecialchars($admin['email']) . "</p>";
        echo "<p><strong>Hash no banco:</strong> " . substr($admin['password'], 0, 30) . "...</p>";
        
        if (password_verify('admin123', $admin['password'])) {
            echo "<p style='color: green;'>✅ Senha do admin está correta no banco!</p>";
        } else {
            echo "<p style='color: red;'>❌ Senha do admin está incorreta no banco!</p>";
            echo "<p>Execute o script <code>database/fix_admin_password.sql</code> para corrigir</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠️ Usuário admin não encontrado</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro ao verificar admin: " . $e->getMessage() . "</p>";
}

?>

