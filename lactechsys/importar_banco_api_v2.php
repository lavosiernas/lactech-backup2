<?php
// API v2 para importar banco de dados (com suporte a DELIMITER)
header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);
set_time_limit(300);

$host = 'localhost';
$user = 'u311882628_xandriaAgro';
$pass = 'Lavosier0012!';
$db = 'u311882628_lactech_lgmato';

// Verificar se é modo fresh (limpar tudo)
$freshMode = isset($_GET['fresh']) && $_GET['fresh'] == '1';

try {
    $conn = new mysqli($host, $user, $pass);
    
    if ($conn->connect_error) {
        throw new Exception('Erro de conexão: ' . $conn->connect_error);
    }
    
    // Se modo fresh, dropar e recriar o banco
    if ($freshMode) {
        $conn->query("DROP DATABASE IF EXISTS $db");
        $conn->query("CREATE DATABASE $db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $conn->select_db($db);
    } else {
        // Criar banco se não existir
        $sql = "CREATE DATABASE IF NOT EXISTS $db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
        $conn->query($sql);
        $conn->select_db($db);
        
        // Dropar todos os triggers existentes para evitar conflitos
        $result = $conn->query("SELECT TRIGGER_NAME FROM information_schema.triggers WHERE trigger_schema = '$db'");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $conn->query("DROP TRIGGER IF EXISTS " . $row['TRIGGER_NAME']);
            }
        }
    }
    
    // Ler arquivo SQL
    $sqlFile = __DIR__ . '/banco_mysql_completo.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception('Arquivo SQL não encontrado');
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Processar o SQL
    $success = 0;
    $errors = 0;
    $skipped = 0;
    
    // Separar comandos considerando DELIMITER
    $statements = [];
    $currentStatement = '';
    $delimiter = ';';
    
    $lines = explode("\n", $sql);
    
    foreach ($lines as $line) {
        $line = trim($line);
        
        // Pular comentários e linhas vazias
        if (empty($line) || substr($line, 0, 2) === '--') {
            continue;
        }
        
        // Detectar mudança de DELIMITER
        if (preg_match('/^DELIMITER\s+(.+)$/i', $line, $matches)) {
            $delimiter = trim($matches[1]);
            continue;
        }
        
        $currentStatement .= $line . ' ';
        
        // Verificar se chegou ao fim do comando
        if (substr(rtrim($line), -strlen($delimiter)) === $delimiter) {
            // Remover o delimiter do final
            $currentStatement = substr(trim($currentStatement), 0, -strlen($delimiter));
            
            if (!empty($currentStatement)) {
                $statements[] = $currentStatement;
            }
            
            $currentStatement = '';
        }
    }
    
    // Adicionar último statement se houver
    if (!empty($currentStatement)) {
        $statements[] = trim($currentStatement);
    }
    
    // Executar cada comando
    foreach ($statements as $statement) {
        $statement = trim($statement);
        
        if (empty($statement) || strlen($statement) < 10) {
            continue;
        }
        
        // Pular USE database se já selecionado
        if (stripos($statement, 'USE ') === 0) {
            $success++;
            continue;
        }
        
        // Pular CREATE DATABASE se já criado
        if (stripos($statement, 'CREATE DATABASE') === 0) {
            $success++;
            continue;
        }
        
        // Converter INSERT em INSERT IGNORE para evitar duplicados
        if (stripos($statement, 'INSERT INTO') === 0) {
            $statement = preg_replace('/^INSERT INTO/i', 'INSERT IGNORE INTO', $statement);
        }
        
        // Tentar executar
        $result = $conn->query($statement);
        
        if ($result) {
            $success++;
        } else {
            $error = $conn->error;
            
            // Ignorar erros conhecidos (tabelas/triggers que já existem, duplicados, etc)
            if (strpos($error, 'already exists') !== false || 
                strpos($error, 'Duplicate column') !== false ||
                strpos($error, 'Duplicate key') !== false ||
                strpos($error, 'Duplicate entry') !== false ||
                strpos($error, "Can't DROP") !== false ||
                strpos($error, "doesn't exist") !== false) {
                $skipped++;
                $success++; // Contar como sucesso também
            } else {
                $errors++;
                // Log do erro para debug (descomentar se necessário)
                // error_log("SQL Error: $error\nStatement: " . substr($statement, 0, 100));
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
    $realTables = 0;
    foreach ($tables as $table) {
        if (strpos($table, 'v_') === 0) {
            $views++;
        } else {
            $realTables++;
        }
    }
    
    // Contar triggers
    $result = $conn->query("SELECT COUNT(*) as cnt FROM information_schema.triggers WHERE trigger_schema = '$db'");
    $triggers = 0;
    if ($row = $result->fetch_assoc()) {
        $triggers = $row['cnt'];
    }
    
    $conn->close();
    
    echo json_encode([
        'success' => true,
        'success_count' => $success,
        'errors' => $errors,
        'skipped' => $skipped,
        'tables' => $realTables,
        'views' => $views,
        'triggers' => $triggers,
        'total_objects' => count($tables),
        'fresh_mode' => $freshMode,
        'message' => $freshMode ? 'Banco criado do zero!' : 'Banco importado com sucesso!'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>

