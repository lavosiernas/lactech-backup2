<?php
/**
 * Script para criar tabelas faltantes no banco de dados
 * Execute este script uma vez para criar a tabela volume_records
 */

require_once __DIR__ . '/Database.class.php';
require_once __DIR__ . '/config_mysql.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "🔧 Criando tabela volume_records...\n";
    
    // Ler o SQL de criação da tabela
    $sqlFile = __DIR__ . '/migrations/create_volume_records_table.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("Arquivo SQL não encontrado: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Remover CREATE TABLE IF NOT EXISTS para usar apenas CREATE TABLE
    $sql = str_replace('CREATE TABLE IF NOT EXISTS', 'CREATE TABLE', $sql);
    
    // Executar o SQL
    $pdo->exec($sql);
    
    echo "✅ Tabela volume_records criada com sucesso!\n";
    
    // Verificar se a tabela foi criada
    $stmt = $pdo->query("SHOW TABLES LIKE 'volume_records'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Verificação: Tabela volume_records existe no banco de dados.\n";
        
        // Verificar estrutura
        $stmt = $pdo->query("DESCRIBE volume_records");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "📋 Colunas criadas: " . count($columns) . "\n";
        foreach ($columns as $col) {
            echo "   - {$col['Field']} ({$col['Type']})\n";
        }
    } else {
        echo "❌ Erro: Tabela não foi criada.\n";
    }
    
} catch (PDOException $e) {
    // Se a tabela já existe, apenas informar
    if (strpos($e->getMessage(), 'already exists') !== false || 
        strpos($e->getMessage(), 'Duplicate table') !== false) {
        echo "ℹ️  Tabela volume_records já existe no banco de dados.\n";
    } else {
        echo "❌ Erro ao criar tabela: " . $e->getMessage() . "\n";
        echo "💡 Tentando criar com IF NOT EXISTS...\n";
        
        // Tentar criar com IF NOT EXISTS
        try {
            $sql = file_get_contents($sqlFile);
            $pdo->exec($sql);
            echo "✅ Tabela criada com sucesso usando IF NOT EXISTS!\n";
        } catch (PDOException $e2) {
            echo "❌ Erro persistente: " . $e2->getMessage() . "\n";
            exit(1);
        }
    }
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n✅ Processo concluído!\n";
?>

