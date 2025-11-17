<?php
/**
 * Script para criar tabela volume_records faltante no banco de dados
 * Execute este arquivo via navegador: https://lactechsys.com/api/fix-database.php
 */

header('Content-Type: text/html; charset=utf-8');

// Verificar autenticação (opcional - remover em produção se necessário)
// session_start();
// if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'gerente') {
//     die('Acesso negado. Apenas gerentes podem executar este script.');
// }

require_once __DIR__ . '/../includes/Database.class.php';
require_once __DIR__ . '/../includes/config_mysql.php';

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Correção do Banco de Dados</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: green; padding: 10px; background: #f0fdf4; border: 1px solid green; border-radius: 5px; margin: 10px 0; }
        .error { color: red; padding: 10px; background: #fef2f2; border: 1px solid red; border-radius: 5px; margin: 10px 0; }
        .info { color: blue; padding: 10px; background: #eff6ff; border: 1px solid blue; border-radius: 5px; margin: 10px 0; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🔧 Correção do Banco de Dados</h1>
    <h2>Criando tabela volume_records...</h2>
";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // SQL para criar a tabela volume_records
    $sql = "
    CREATE TABLE IF NOT EXISTS `volume_records` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `record_date` date NOT NULL COMMENT 'Data do registro',
      `shift` enum('manha','tarde','noite') NOT NULL COMMENT 'Turno da coleta',
      `total_volume` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Volume total coletado (litros)',
      `total_animals` int(11) DEFAULT 0 COMMENT 'Número de animais ordenhados',
      `average_per_animal` decimal(10,2) DEFAULT NULL COMMENT 'Média por animal (litros)',
      `notes` text DEFAULT NULL COMMENT 'Observações sobre a coleta',
      `recorded_by` int(11) DEFAULT NULL COMMENT 'ID do usuário que registrou',
      `farm_id` int(11) NOT NULL DEFAULT 1 COMMENT 'ID da fazenda',
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Data de criação',
      `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Data de atualização',
      PRIMARY KEY (`id`),
      KEY `idx_farm_id` (`farm_id`),
      KEY `idx_record_date` (`record_date`),
      KEY `idx_shift` (`shift`),
      KEY `idx_recorded_by` (`recorded_by`),
      KEY `idx_farm_date` (`farm_id`, `record_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registros de volume de leite coletado na fazenda';
    ";
    
    // Tentar criar a tabela
    $pdo->exec($sql);
    
    echo "<div class='success'>✅ Tabela volume_records criada com sucesso!</div>";
    
    // Verificar se a tabela foi criada
    $stmt = $pdo->query("SHOW TABLES LIKE 'volume_records'");
    if ($stmt->rowCount() > 0) {
        echo "<div class='success'>✅ Verificação: Tabela volume_records existe no banco de dados.</div>";
        
        // Verificar estrutura
        $stmt = $pdo->query("DESCRIBE volume_records");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<div class='info'>📋 Colunas criadas: " . count($columns) . "</div>";
        echo "<pre>";
        foreach ($columns as $col) {
            echo "{$col['Field']} - {$col['Type']}";
            if ($col['Null'] === 'NO') echo " NOT NULL";
            if ($col['Key'] === 'PRI') echo " PRIMARY KEY";
            if ($col['Key'] === 'MUL') echo " INDEX";
            if ($col['Extra']) echo " {$col['Extra']}";
            echo "\n";
        }
        echo "</pre>";
        
        // Verificar índices
        $stmt = $pdo->query("SHOW INDEX FROM volume_records");
        $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($indexes) > 0) {
            echo "<div class='info'>📋 Índices criados: " . count($indexes) . "</div>";
        }
        
    } else {
        echo "<div class='error'>❌ Erro: Tabela não foi criada.</div>";
    }
    
    // Verificar foreign keys (opcional - pode falhar se users ou farms não existirem)
    try {
        $pdo->exec("
            ALTER TABLE `volume_records` 
            ADD CONSTRAINT `fk_volume_records_user` 
            FOREIGN KEY (`recorded_by`) 
            REFERENCES `users` (`id`) 
            ON DELETE SET NULL 
            ON UPDATE CASCADE
        ");
        echo "<div class='success'>✅ Foreign key para users criada.</div>";
    } catch (PDOException $e) {
        echo "<div class='info'>ℹ️ Foreign key para users não criada (pode já existir ou tabela users não existe): " . $e->getMessage() . "</div>";
    }
    
    try {
        $pdo->exec("
            ALTER TABLE `volume_records` 
            ADD CONSTRAINT `fk_volume_records_farm` 
            FOREIGN KEY (`farm_id`) 
            REFERENCES `farms` (`id`) 
            ON DELETE CASCADE 
            ON UPDATE CASCADE
        ");
        echo "<div class='success'>✅ Foreign key para farms criada.</div>";
    } catch (PDOException $e) {
        echo "<div class='info'>ℹ️ Foreign key para farms não criada (pode já existir ou tabela farms não existe): " . $e->getMessage() . "</div>";
    }
    
    echo "<div class='success'><strong>✅ Processo concluído com sucesso!</strong></div>";
    echo "<p><a href='../gerente-completo.php'>← Voltar para o Gerente</a></p>";
    
} catch (PDOException $e) {
    // Se a tabela já existe, apenas informar
    if (strpos($e->getMessage(), 'already exists') !== false || 
        strpos($e->getMessage(), 'Duplicate table') !== false ||
        $e->getCode() == '42S01') {
        echo "<div class='info'>ℹ️ Tabela volume_records já existe no banco de dados.</div>";
        echo "<p><a href='../gerente-completo.php'>← Voltar para o Gerente</a></p>";
    } else {
        echo "<div class='error'>❌ Erro ao criar tabela: " . htmlspecialchars($e->getMessage()) . "</div>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Erro: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</body></html>";
?>
























