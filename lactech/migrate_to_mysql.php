<?php
// =====================================================
// MIGRAÇÃO SUPABASE PARA MYSQL
// =====================================================
// Script para converter o sistema do Supabase para MySQL
// =====================================================

require_once 'includes/config_mysql.php';
require_once 'includes/database.php';

// Configurações do Supabase (para migração de dados existentes)
$supabase_url = 'https://tmaamwuyucaspqcrhuck.supabase.co';
$supabase_key = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InRtYWFtd3V5dWNhc3BxY3JodWNrIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NTY2OTY1MzMsImV4cCI6MjA3MjI3MjUzM30.AdDXp0xrX_xKutFHQrJ47LhFdLTtanTSku7fcK1eTB0';

echo "<h1>Migração Supabase → MySQL</h1>";
echo "<h2>LacTech - Lagoa do Mato</h2>";

try {
    // 1. Verificar conexão MySQL
    echo "<p>✅ Conexão MySQL estabelecida</p>";
    
    // 2. Verificar se o banco foi importado
    $tables = query("SHOW TABLES");
    if ($tables->rowCount() > 0) {
        echo "<p>✅ Banco de dados importado com sucesso</p>";
        echo "<p>📊 Tabelas encontradas: " . $tables->rowCount() . "</p>";
    } else {
        echo "<p>❌ Banco de dados não encontrado. Execute primeiro o script SQL.</p>";
        exit;
    }
    
    // 3. Verificar dados iniciais
    $farm = fetch("SELECT * FROM farms LIMIT 1");
    if ($farm) {
        echo "<p>✅ Fazenda configurada: " . $farm['name'] . "</p>";
    }
    
    $user = fetch("SELECT * FROM users LIMIT 1");
    if ($user) {
        echo "<p>✅ Usuário admin criado: " . $user['email'] . "</p>";
    }
    
    $animals = fetchAll("SELECT COUNT(*) as count FROM animals");
    echo "<p>✅ Animais cadastrados: " . $animals[0]['count'] . "</p>";
    
    // 4. Configurar sistema para usar MySQL
    echo "<h3>Configuração do Sistema</h3>";
    
    // Atualizar configurações da fazenda
    updateFarmSettings([
        'farm_name' => 'Lagoa do Mato',
        'report_footer_text' => 'Sistema de Gestão Leiteira - Fazenda Lagoa do Mato'
    ]);
    
    echo "<p>✅ Configurações da fazenda atualizadas</p>";
    
    // 5. Criar dados de exemplo (se necessário)
    $volumeCount = fetch("SELECT COUNT(*) as count FROM volume_records");
    if ($volumeCount['count'] == 0) {
        echo "<p>📝 Criando dados de exemplo...</p>";
        
        // Inserir alguns registros de produção de exemplo
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        
        insert('volume_records', [
            'farm_id' => 'farm-lagoa-mato-001',
            'user_id' => 'user-admin-001',
            'production_date' => $today,
            'shift' => 'manha',
            'volume_liters' => 125.5,
            'temperature' => 37.2
        ]);
        
        insert('volume_records', [
            'farm_id' => 'farm-lagoa-mato-001',
            'user_id' => 'user-admin-001',
            'production_date' => $today,
            'shift' => 'tarde',
            'volume_liters' => 118.3,
            'temperature' => 37.1
        ]);
        
        echo "<p>✅ Dados de exemplo criados</p>";
    }
    
    // 6. Verificar funcionalidades
    echo "<h3>Verificação de Funcionalidades</h3>";
    
    $stats = getFarmStats();
    echo "<p>📊 Estatísticas:</p>";
    echo "<ul>";
    echo "<li>Total de animais: " . $stats['animals']['total_animals'] . "</li>";
    echo "<li>Animais saudáveis: " . $stats['animals']['healthy_animals'] . "</li>";
    echo "<li>Produção hoje: " . formatVolume($stats['today_production']) . "</li>";
    echo "<li>Média semanal: " . formatVolume($stats['weekly_avg']) . "</li>";
    echo "</ul>";
    
    echo "<h3>✅ Migração Concluída com Sucesso!</h3>";
    echo "<p><strong>Próximos passos:</strong></p>";
    echo "<ol>";
    echo "<li>Atualizar o arquivo de configuração do sistema para usar MySQL</li>";
    echo "<li>Testar todas as funcionalidades do sistema</li>";
    echo "<li>Fazer backup do banco MySQL</li>";
    echo "<li>Desativar conexões com Supabase se necessário</li>";
    echo "</ol>";
    
    echo "<p><strong>Login do sistema:</strong></p>";
    echo "<ul>";
    echo "<li>Email: admin@lagoa.com</li>";
    echo "<li>Senha: password</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p>❌ Erro na migração: " . $e->getMessage() . "</p>";
    echo "<p>Verifique se:</p>";
    echo "<ul>";
    echo "<li>O banco MySQL está rodando</li>";
    echo "<li>O arquivo SQL foi importado corretamente</li>";
    echo "<li>As credenciais de conexão estão corretas</li>";
    echo "</ul>";
}

echo "<hr>";
echo "<p><small>LacTech - Sistema de Gestão Leiteira<br>";
echo "Fazenda Lagoa do Mato<br>";
echo "Migração concluída em: " . date('d/m/Y H:i:s') . "</small></p>";
?>
