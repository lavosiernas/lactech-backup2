<?php
/**
 * Script para verificar se a tabela safenode_survey_admin existe e criar/atualizar se necessário
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/init.php';

$db = getSafeNodeDatabase();

if (!$db) {
    die("❌ Erro ao conectar ao banco de dados\n");
}

echo "✅ Conectado ao banco de dados\n\n";

// Verificar se a tabela existe
try {
    $stmt = $db->query("SHOW TABLES LIKE 'safenode_survey_admin'");
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        echo "❌ Tabela safenode_survey_admin NÃO existe\n";
        echo "📋 Execute o SQL: admin-login-table.sql\n\n";
    } else {
        echo "✅ Tabela safenode_survey_admin existe\n\n";
        
        // Verificar se tem usuário admin
        $stmt = $db->prepare("SELECT id, username, email, password_hash FROM safenode_survey_admin WHERE username = ?");
        $stmt->execute(['admin']);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($admin) {
            echo "✅ Usuário admin encontrado:\n";
            echo "   ID: " . $admin['id'] . "\n";
            echo "   Username: " . $admin['username'] . "\n";
            echo "   Email: " . ($admin['email'] ?? 'N/A') . "\n";
            echo "   Hash (primeiros 30 chars): " . substr($admin['password_hash'], 0, 30) . "...\n\n";
            
            // Testar senha
            $testPassword = 'lnassfnd017852';
            $passwordMatch = password_verify($testPassword, $admin['password_hash']);
            
            echo "🔐 Teste de senha:\n";
            echo "   Senha testada: " . $testPassword . "\n";
            echo "   Hash corresponde? " . ($passwordMatch ? "✅ SIM" : "❌ NÃO") . "\n";
            
            if (!$passwordMatch) {
                echo "\n⚠️  O hash não corresponde à senha. Vamos gerar um novo hash:\n\n";
                
                $newHash = password_hash($testPassword, PASSWORD_BCRYPT);
                echo "Novo hash: " . $newHash . "\n\n";
                
                echo "SQL para atualizar:\n";
                echo "UPDATE safenode_survey_admin SET password_hash = '" . $newHash . "' WHERE username = 'admin';\n\n";
            }
        } else {
            echo "❌ Usuário admin NÃO encontrado\n";
            echo "📋 Execute o SQL: admin-login-table.sql\n\n";
        }
    }
} catch (PDOException $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}


