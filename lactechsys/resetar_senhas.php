<?php
/**
 * Script para resetar senhas dos usuários
 * Execute este arquivo uma única vez para converter senhas para bcrypt
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Resetando Senhas dos Usuários</h2>";
echo "<pre>";

echo "🔄 Iniciando processo...\n\n";

// Verificar se Database.class.php existe
if (!file_exists('includes/Database.class.php')) {
    die("❌ ERRO: Arquivo Database.class.php não encontrado!\n");
}

echo "✅ Database.class.php encontrado\n";

try {
    require_once 'includes/Database.class.php';
    echo "✅ Database.class.php carregado\n\n";
    
    $db = Database::getInstance();
    echo "✅ Conexão com banco estabelecida\n\n";
    
    // Senhas padrão para resetar
    $usuarios = [
        [
            'email' => 'Junior@lactech.com',
            'senha' => 'gerente123',
            'nome' => 'Junior',
            'role' => 'gerente'
        ],
        [
            'email' => 'fernando@lactech.com',
            'senha' => 'proprietario123',
            'nome' => 'Fernando',
            'role' => 'proprietario'
        ]
    ];
    
    echo "🔐 Resetando senhas...\n\n";
    
    foreach ($usuarios as $user) {
        // Gerar hash bcrypt da senha
        $passwordHash = password_hash($user['senha'], PASSWORD_DEFAULT);
        
        // Verificar se o usuário existe
        $stmt = $db->getConnection()->prepare("SELECT id, email FROM users WHERE email = ?");
        $stmt->execute([$user['email']]);
        $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existingUser) {
            // Atualizar senha existente
            $updateStmt = $db->getConnection()->prepare("
                UPDATE users 
                SET password = ?, 
                    role = ?,
                    is_active = 1,
                    updated_at = NOW()
                WHERE email = ?
            ");
            
            if ($updateStmt->execute([$passwordHash, $user['role'], $user['email']])) {
                echo "✅ Senha atualizada para: {$user['email']}\n";
                echo "   Nova senha: {$user['senha']}\n";
                echo "   Hash bcrypt: " . substr($passwordHash, 0, 30) . "...\n\n";
            } else {
                echo "❌ Erro ao atualizar {$user['email']}\n\n";
            }
        } else {
            echo "⚠️  Usuário não encontrado: {$user['email']}\n";
            echo "   Criando novo usuário...\n";
            
            // Criar novo usuário
            $insertStmt = $db->getConnection()->prepare("
                INSERT INTO users (name, email, password, role, farm_id, is_active, created_at, updated_at)
                VALUES (?, ?, ?, ?, 1, 1, NOW(), NOW())
            ");
            
            if ($insertStmt->execute([$user['nome'], $user['email'], $passwordHash, $user['role']])) {
                echo "✅ Usuário criado: {$user['email']}\n";
                echo "   Senha: {$user['senha']}\n\n";
            } else {
                echo "❌ Erro ao criar usuário {$user['email']}\n\n";
            }
        }
    }
    
    echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "✅ PROCESSO CONCLUÍDO!\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    echo "📋 CREDENCIAIS DE LOGIN:\n\n";
    foreach ($usuarios as $user) {
        echo "Email: {$user['email']}\n";
        echo "Senha: {$user['senha']}\n";
        echo "Role: {$user['role']}\n";
        echo "---\n";
    }
    
    echo "\n🔒 IMPORTANTE:\n";
    echo "1. Teste o login com essas credenciais\n";
    echo "2. Altere as senhas após o primeiro acesso\n";
    echo "3. APAGUE este arquivo (resetar_senhas.php) por segurança!\n\n";
    
    echo "🌐 Acesse: https://lactechsys.com/\n\n";
    
} catch (PDOException $e) {
    echo "❌ ERRO DE BANCO DE DADOS: " . $e->getMessage() . "\n\n";
    echo "Código do erro: " . $e->getCode() . "\n";
    echo "Arquivo: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
    
    echo "💡 POSSÍVEIS SOLUÇÕES:\n";
    echo "1. Verifique se o banco 'u311882628_lactech_lgmato' existe\n";
    echo "2. Verifique as credenciais em includes/config_mysql.php\n";
    echo "3. Verifique se a tabela 'users' existe no banco\n";
    echo "4. Importe o banco_mysql_completo.sql primeiro\n";
    
} catch (Exception $e) {
    echo "❌ ERRO GERAL: " . $e->getMessage() . "\n\n";
    echo "Detalhes técnicos:\n";
    echo $e->getTraceAsString();
}

echo "</pre>";

echo '<div style="margin-top: 20px; padding: 15px; background: #f0f9ff; border: 2px solid #0ea5e9; border-radius: 8px;">';
echo '<strong>🔧 Debug Rápido:</strong><br>';
echo '<a href="testar_conexao.php" style="color: #0ea5e9; text-decoration: underline;">→ Testar Conexão com Banco</a><br>';
echo '<a href="configurar_sistema.php" style="color: #10b981; text-decoration: underline;">→ Importar Banco de Dados</a>';
echo '</div>';
?>

