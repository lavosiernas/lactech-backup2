<?php
/**
 * Teste de Conexão com Banco de Dados
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Teste de Conexão - LacTech</h2>";
echo "<pre>";

echo "🔍 Verificando configurações...\n\n";

// Teste 1: Verificar se config existe
if (!file_exists('includes/config_mysql.php')) {
    die("❌ ERRO: includes/config_mysql.php não encontrado!\n");
}
echo "✅ config_mysql.php encontrado\n";

// Carregar configurações
require_once 'includes/config_mysql.php';

echo "✅ Configurações carregadas\n\n";

// Mostrar configurações (sem senha)
echo "📋 CONFIGURAÇÕES ATUAIS:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Host: " . DB_HOST . "\n";
echo "Banco: " . DB_NAME . "\n";
echo "Usuário: " . DB_USER . "\n";
echo "Senha: " . (DB_PASS ? str_repeat('*', strlen(DB_PASS)) : '(vazia)') . "\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Teste 2: Tentar conectar
echo "🔌 Testando conexão com MySQL...\n";

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception("Erro de conexão: " . $conn->connect_error);
    }
    
    echo "✅ Conexão estabelecida com sucesso!\n\n";
    
    // Teste 3: Verificar tabelas
    echo "📊 Verificando tabelas...\n";
    $result = $conn->query("SHOW TABLES");
    
    if ($result) {
        $tables = [];
        while ($row = $result->fetch_array()) {
            $tables[] = $row[0];
        }
        
        echo "✅ Total de tabelas: " . count($tables) . "\n\n";
        
        // Verificar se tabela users existe
        if (in_array('users', $tables)) {
            echo "✅ Tabela 'users' encontrada\n";
            
            // Contar usuários
            $result = $conn->query("SELECT COUNT(*) as total FROM users");
            $count = $result->fetch_assoc();
            echo "✅ Total de usuários: " . $count['total'] . "\n\n";
            
            // Listar usuários
            $result = $conn->query("SELECT id, name, email, role, is_active FROM users");
            echo "👥 USUÁRIOS CADASTRADOS:\n";
            echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
            while ($user = $result->fetch_assoc()) {
                echo "ID: {$user['id']}\n";
                echo "Nome: {$user['name']}\n";
                echo "Email: {$user['email']}\n";
                echo "Role: {$user['role']}\n";
                echo "Ativo: " . ($user['is_active'] ? 'Sim' : 'Não') . "\n";
                echo "---\n";
            }
            
        } else {
            echo "❌ Tabela 'users' NÃO encontrada\n";
            echo "⚠️  Você precisa importar o banco primeiro!\n";
            echo "→ Acesse: configurar_sistema.php\n\n";
        }
        
        echo "\n📋 Todas as tabelas:\n";
        echo implode(", ", $tables) . "\n";
        
    } else {
        echo "❌ Erro ao listar tabelas: " . $conn->error . "\n";
    }
    
    $conn->close();
    
    echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "✅ TESTE CONCLUÍDO COM SUCESSO!\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    echo "🔄 Próximo passo: resetar_senhas.php\n";
    
} catch (Exception $e) {
    echo "\n❌ ERRO DE CONEXÃO!\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Mensagem: " . $e->getMessage() . "\n\n";
    
    echo "💡 POSSÍVEIS CAUSAS:\n";
    echo "1. Banco de dados não existe ou nome incorreto\n";
    echo "2. Usuário ou senha incorretos\n";
    echo "3. MySQL não está rodando\n";
    echo "4. Permissões do usuário insuficientes\n\n";
    
    echo "🔧 SOLUÇÕES:\n";
    echo "1. Verifique no painel da Hostinger:\n";
    echo "   - Banco de Dados → MySQL Databases\n";
    echo "   - Confirme nome, usuário e senha\n\n";
    echo "2. Edite includes/config_mysql.php com as credenciais corretas\n\n";
    echo "3. Certifique-se que o banco foi criado no painel\n\n";
}

echo "</pre>";

echo '<div style="margin-top: 20px;">';
echo '<a href="configurar_sistema.php" style="background: #10b981; color: white; padding: 10px 20px; text-decoration: none; border-radius: 8px; display: inline-block; font-weight: bold;">→ Configurar Sistema</a>';
echo '</div>';
?>

