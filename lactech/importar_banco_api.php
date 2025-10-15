<?php
// API para importar banco de dados (retorna JSON)
header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);
set_time_limit(300);

$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'lactech_lgmato';

try {
    $conn = new mysqli($host, $user, $pass);
    
    if ($conn->connect_error) {
        throw new Exception('Erro de conexão: ' . $conn->connect_error);
    }
    
    // Criar banco se não existir
    $sql = "CREATE DATABASE IF NOT EXISTS $db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    $conn->query($sql);
    $conn->select_db($db);
    
    // Ler arquivo SQL
    $sqlFile = __DIR__ . '/banco_mysql_completo.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception('Arquivo SQL não encontrado');
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Remover comentários
    $sql = preg_replace('/^--.*$/m', '', $sql);
    
    // Remover DELIMITER (não funciona via mysqli)
    $sql = preg_replace('/DELIMITER\s+\/\//i', '', $sql);
    $sql = preg_replace('/DELIMITER\s+;/i', '', $sql);
    $sql = str_replace('//', ';', $sql);
    
    // Dividir em comandos
    $commands = explode(';', $sql);
    
    $success = 0;
    $errors = 0;
    $skipped = 0;
    
    foreach ($commands as $command) {
        $command = trim($command);
        
        if (empty($command)) {
            continue;
        }
        
        // Pular comandos vazios ou inválidos
        if (strlen($command) < 10) {
            continue;
        }
        
        // Tentar executar
        if ($conn->query($command)) {
            $success++;
        } else {
            $error = $conn->error;
            
            // Ignorar erros conhecidos (já existe, duplicado, etc)
            if (strpos($error, 'already exists') !== false || 
                strpos($error, 'Duplicate column') !== false ||
                strpos($error, 'Duplicate key') !== false ||
                strpos($error, 'check the manual') !== false) {
                $skipped++;
            } else {
                $errors++;
            }
        }
    }
    
    // Contar tabelas e views
    $result = $conn->query("SHOW TABLES");
    $tables = [];
    while ($row = $result->fetch_array()) {
        $tables[] = $row[0];
    }
    
    $views = 0;
    foreach ($tables as $table) {
        if (strpos($table, 'v_') === 0) {
            $views++;
        }
    }
    
    $conn->close();
    
    echo json_encode([
        'success' => true,
        'success_count' => $success,
        'errors' => $errors,
        'skipped' => $skipped,
        'tables' => count($tables),
        'views' => $views,
        'message' => 'Banco importado com sucesso!'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>

