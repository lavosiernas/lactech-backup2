<?php
// =====================================================
// TESTE DE CONEXÃO MYSQL - LAGOA DO MATO
// =====================================================

require_once 'includes/config_mysql.php';
require_once 'includes/database.php';

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste MySQL - LacTech</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold text-gray-800 mb-8">Teste de Conexão MySQL - LacTech</h1>
        
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-semibold mb-4">Status da Conexão</h2>
            
            <?php
            try {
                // Testar conexão
                $pdo = getDB()->getConnection();
                echo '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">';
                echo '✅ <strong>Conexão MySQL:</strong> Sucesso!';
                echo '</div>';
                
                // Testar tabelas
                $tables = query("SHOW TABLES");
                $tableCount = $tables->rowCount();
                
                echo '<div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded mb-4">';
                echo '📊 <strong>Tabelas encontradas:</strong> ' . $tableCount;
                echo '</div>';
                
                // Testar dados
                $farm = fetch("SELECT * FROM farms LIMIT 1");
                if ($farm) {
                    echo '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">';
                    echo '🏢 <strong>Fazenda:</strong> ' . $farm['name'];
                    echo '</div>';
                }
                
                $user = fetch("SELECT * FROM users LIMIT 1");
                if ($user) {
                    echo '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">';
                    echo '👤 <strong>Usuário admin:</strong> ' . $user['email'] . ' (' . $user['role'] . ')';
                    echo '</div>';
                }
                
                $animals = fetchAll("SELECT COUNT(*) as count FROM animals");
                echo '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">';
                echo '🐄 <strong>Animais cadastrados:</strong> ' . $animals[0]['count'];
                echo '</div>';
                
                // Testar estatísticas
                $stats = getFarmStats();
                echo '<div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded mb-4">';
                echo '📈 <strong>Estatísticas:</strong> Sistema funcionando';
                echo '</div>';
                
            } catch (Exception $e) {
                echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">';
                echo '❌ <strong>Erro:</strong> ' . $e->getMessage();
                echo '</div>';
            }
            ?>
            
            <div class="mt-6">
                <h3 class="text-lg font-semibold mb-3">Ações</h3>
                <div class="flex space-x-4">
                    <a href="login.php" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        Ir para Login
                    </a>
                    <a href="gerente.php" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                        Ir para Gerente
                    </a>
                    <a href="migrate_to_mysql.php" class="bg-purple-500 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded">
                        Executar Migração
                    </a>
                </div>
            </div>
            
            <div class="mt-8">
                <h3 class="text-lg font-semibold mb-3">Informações do Sistema</h3>
                <div class="bg-gray-50 p-4 rounded">
                    <p><strong>Banco:</strong> lactech_lagoa_mato</p>
                    <p><strong>Fazenda:</strong> Lagoa do Mato</p>
                    <p><strong>Tipos de usuários:</strong> proprietario, gerente, funcionario</p>
                    <p><strong>Login padrão:</strong> admin@lagoa.com / password</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
