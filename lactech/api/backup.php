<?php
// Limpar qualquer output anterior
ob_start();
ob_clean();

// Configurar headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Desabilitar exibição de erros para evitar quebra do JSON
ini_set('display_errors', 0);
error_reporting(0);

try {
    // Verificar se os arquivos existem
    $config_path = '../includes/config.php';
    $database_path = '../includes/Database.class.php';
    
    if (!file_exists($config_path)) {
        throw new Exception('Arquivo config.php não encontrado em: ' . $config_path);
    }
    
    if (!file_exists($database_path)) {
        throw new Exception('Arquivo Database.class.php não encontrado em: ' . $database_path);
    }
    
    require_once $config_path;
    require_once $database_path;

    $db = new Database();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erro de configuração: ' . $e->getMessage()]);
    exit;
}

try {
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create_backup':
            createBackup($db);
            break;
            
        case 'list_backups':
            listBackups($db);
            break;
            
        case 'restore_backup':
            restoreBackup($db);
            break;
            
        case 'delete_backup':
            deleteBackup($db);
            break;
            
        case 'sync_data':
            syncData($db);
            break;
            
        case 'export_data':
            exportData($db);
            break;
            
        case 'import_data':
            importData($db);
            break;
            
        case 'check_sync_status':
            checkSyncStatus($db);
            break;
            
        default:
            throw new Exception('Ação não reconhecida');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function createBackup($db) {
    try {
        $backup_name = $_POST['name'] ?? 'Backup_' . date('Y-m-d_H-i-s');
        $description = $_POST['description'] ?? '';
        $include_photos = $_POST['include_photos'] ?? false;
        
        // Criar diretório de backup se não existir
        $backup_dir = '../backups/';
        if (!is_dir($backup_dir)) {
            if (!mkdir($backup_dir, 0755, true)) {
                throw new Exception('Não foi possível criar o diretório de backup');
            }
        }
        
        $backup_file = $backup_dir . $backup_name . '.sql';
    
    // Criar backup do banco
    $tables = [
        'animals', 'inseminations', 'health_records', 'medications',
        'notifications', 'animal_transponders', 'body_condition_scores',
        'feed_records', 'animal_groups', 'group_movements', 'animal_photos',
        'ai_predictions', 'action_lists_cache', 'user_preferences'
    ];
    
    $sql_content = "-- Backup criado em: " . date('Y-m-d H:i:s') . "\n";
    $sql_content .= "-- Descrição: " . $description . "\n\n";
    
    foreach ($tables as $table) {
        $sql_content .= "-- Estrutura da tabela $table\n";
        $sql_content .= "DROP TABLE IF EXISTS `$table`;\n";
        
        $create_table = $db->query("SHOW CREATE TABLE `$table`");
        if ($create_table && $create_table->num_rows > 0) {
            $row = $create_table->fetch_assoc();
            $sql_content .= $row['Create Table'] . ";\n\n";
        }
        
        $sql_content .= "-- Dados da tabela $table\n";
        $data = $db->query("SELECT * FROM `$table`");
        if ($data && $data->num_rows > 0) {
            while ($row = $data->fetch_assoc()) {
                $columns = implode('`, `', array_keys($row));
                $values = implode("', '", array_map('addslashes', array_values($row)));
                $sql_content .= "INSERT INTO `$table` (`$columns`) VALUES ('$values');\n";
            }
        }
        $sql_content .= "\n";
    }
    
        // Salvar arquivo
        if (!file_put_contents($backup_file, $sql_content)) {
            throw new Exception('Não foi possível salvar o arquivo de backup');
        }
        
        // Tentar registrar no banco se a tabela existir
        try {
            $tableCheck = $db->query("SHOW TABLES LIKE 'backup_records'");
            if ($tableCheck && $tableCheck->num_rows > 0) {
                $db->query("INSERT INTO backup_records (name, description, file_path, created_at, file_size) VALUES (?, ?, ?, NOW(), ?)", 
                    [$backup_name, $description, $backup_file, filesize($backup_file)]);
            }
        } catch (Exception $e) {
            // Ignorar erro de tabela, backup foi criado com sucesso
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Backup criado com sucesso',
            'backup' => [
                'name' => $backup_name,
                'file' => $backup_file,
                'size' => filesize($backup_file)
            ]
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Erro ao criar backup: ' . $e->getMessage()
        ]);
    }
}

function listBackups($db) {
    try {
        // Primeiro, verificar se o diretório de backups existe
        $backup_dir = '../backups/';
        if (!is_dir($backup_dir)) {
            echo json_encode([
                'success' => true, 
                'backups' => [],
                'message' => 'Diretório de backups não encontrado. Será criado automaticamente no primeiro backup.'
            ]);
            return;
        }
        
        // Listar arquivos de backup do diretório
        $backup_files = glob($backup_dir . '*.sql');
        $result = [];
        
        foreach ($backup_files as $file) {
            $file_info = [
                'id' => basename($file, '.sql'),
                'name' => basename($file, '.sql'),
                'description' => 'Backup automático',
                'file_path' => $file,
                'created_at' => date('Y-m-d H:i:s', filemtime($file)),
                'file_size' => filesize($file),
                'exists' => true
            ];
            $result[] = $file_info;
        }
        
        // Ordenar por data de criação (mais recente primeiro)
        usort($result, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        echo json_encode(['success' => true, 'backups' => $result]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false, 
            'error' => 'Erro ao listar backups: ' . $e->getMessage(),
            'backups' => []
        ]);
    }
}

function restoreBackup($db) {
    $backup_id = $_POST['backup_id'];
    
    $backup = $db->query("SELECT * FROM backup_records WHERE id = ?", [$backup_id])->fetch_assoc();
    if (!$backup) {
        throw new Exception('Backup não encontrado');
    }
    
    if (!file_exists($backup['file_path'])) {
        throw new Exception('Arquivo de backup não encontrado');
    }
    
    // Ler e executar SQL do backup
    $sql_content = file_get_contents($backup['file_path']);
    $statements = explode(';', $sql_content);
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement) && !str_starts_with($statement, '--')) {
            $db->query($statement);
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Backup restaurado com sucesso'
    ]);
}

function deleteBackup($db) {
    $backup_id = $_POST['backup_id'];
    
    $backup = $db->query("SELECT * FROM backup_records WHERE id = ?", [$backup_id])->fetch_assoc();
    if (!$backup) {
        throw new Exception('Backup não encontrado');
    }
    
    // Deletar arquivo
    if (file_exists($backup['file_path'])) {
        unlink($backup['file_path']);
    }
    
    // Deletar registro
    $db->query("DELETE FROM backup_records WHERE id = ?", [$backup_id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Backup deletado com sucesso'
    ]);
}

function syncData($db) {
    $last_sync = $_POST['last_sync'] ?? '1970-01-01 00:00:00';
    
    // Buscar dados modificados desde a última sincronização
    $tables = [
        'animals', 'inseminations', 'health_records', 'medications',
        'notifications', 'animal_transponders', 'body_condition_scores',
        'feed_records', 'animal_groups', 'group_movements', 'animal_photos',
        'ai_predictions', 'action_lists_cache', 'user_preferences'
    ];
    
    $sync_data = [];
    
    foreach ($tables as $table) {
        $data = $db->query("SELECT * FROM `$table` WHERE updated_at > ? OR created_at > ?", 
            [$last_sync, $last_sync]);
        
        $sync_data[$table] = [];
        while ($row = $data->fetch_assoc()) {
            $sync_data[$table][] = $row;
        }
    }
    
    echo json_encode([
        'success' => true,
        'sync_data' => $sync_data,
        'sync_timestamp' => date('Y-m-d H:i:s')
    ]);
}

function exportData($db) {
    $format = $_GET['format'] ?? 'json';
    $tables = $_GET['tables'] ?? '';
    
    if (empty($tables)) {
        $tables = ['animals', 'inseminations', 'health_records', 'medications'];
    } else {
        $tables = explode(',', $tables);
    }
    
    $export_data = [];
    
    foreach ($tables as $table) {
        $data = $db->query("SELECT * FROM `$table`");
        $export_data[$table] = [];
        
        while ($row = $data->fetch_assoc()) {
            $export_data[$table][] = $row;
        }
    }
    
    if ($format === 'csv') {
        // Exportar como CSV
        $csv_content = '';
        foreach ($export_data as $table => $rows) {
            $csv_content .= "Table: $table\n";
            if (!empty($rows)) {
                $csv_content .= implode(',', array_keys($rows[0])) . "\n";
                foreach ($rows as $row) {
                    $csv_content .= implode(',', array_values($row)) . "\n";
                }
            }
            $csv_content .= "\n";
        }
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="lactech_export.csv"');
        echo $csv_content;
    } else {
        // Exportar como JSON
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="lactech_export.json"');
        echo json_encode($export_data, JSON_PRETTY_PRINT);
    }
}

function importData($db) {
    $format = $_POST['format'] ?? 'json';
    $data = $_POST['data'] ?? '';
    
    if (empty($data)) {
        throw new Exception('Dados não fornecidos');
    }
    
    if ($format === 'json') {
        $import_data = json_decode($data, true);
    } else {
        throw new Exception('Formato não suportado');
    }
    
    $imported_tables = [];
    
    foreach ($import_data as $table => $rows) {
        if (!empty($rows)) {
            // Limpar tabela existente
            $db->query("DELETE FROM `$table`");
            
            // Inserir novos dados
            foreach ($rows as $row) {
                $columns = implode('`, `', array_keys($row));
                $placeholders = implode(', ', array_fill(0, count($row), '?'));
                $values = array_values($row);
                
                $db->query("INSERT INTO `$table` (`$columns`) VALUES ($placeholders)", $values);
            }
            
            $imported_tables[] = $table;
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Dados importados com sucesso',
        'imported_tables' => $imported_tables
    ]);
}

function checkSyncStatus($db) {
    // Verificar status de sincronização
    $last_sync = $db->query("SELECT MAX(updated_at) as last_update FROM (
        SELECT updated_at FROM animals
        UNION ALL SELECT updated_at FROM inseminations
        UNION ALL SELECT updated_at FROM health_records
        UNION ALL SELECT updated_at FROM medications
    ) as all_tables")->fetch_assoc();
    
    $sync_status = [
        'last_sync' => $last_sync['last_update'] ?? 'Nunca',
        'tables_count' => 0,
        'total_records' => 0,
        'sync_health' => 'good'
    ];
    
    // Contar registros
    $tables = ['animals', 'inseminations', 'health_records', 'medications'];
    foreach ($tables as $table) {
        $count = $db->query("SELECT COUNT(*) as count FROM `$table`")->fetch_assoc();
        $sync_status['total_records'] += $count['count'];
        $sync_status['tables_count']++;
    }
    
    echo json_encode([
        'success' => true,
        'sync_status' => $sync_status
    ]);
}
?>
