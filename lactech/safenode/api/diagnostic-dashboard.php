<?php
/**
 * SafeNode - Diagnóstico de Conexão do Dashboard
 * Verifica problemas de conexão e estrutura do banco de dados
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Verificar se está logado
if (!isset($_SESSION['safenode_logged_in']) || $_SESSION['safenode_logged_in'] !== true) {
    die('Erro: Não está logado. <a href="../login.php">Fazer login</a>');
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/init.php';

$db = getSafeNodeDatabase();
$currentSiteId = $_SESSION['view_site_id'] ?? 0;
$userId = $_SESSION['safenode_user_id'] ?? null;

$diagnostics = [];
$errors = [];

// 1. Verificar conexão com banco
$diagnostics['database_connection'] = [
    'status' => $db ? 'OK' : 'ERRO',
    'message' => $db ? 'Conexão estabelecida com sucesso' : 'Erro ao conectar ao banco de dados'
];

if (!$db) {
    $errors[] = 'Não foi possível conectar ao banco de dados';
    die(json_encode(['errors' => $errors, 'diagnostics' => $diagnostics], JSON_PRETTY_PRINT));
}

// 2. Verificar tabelas necessárias
$requiredTables = [
    'safenode_security_logs',
    'safenode_sites',
    'safenode_incidents',
    'safenode_users',
    'safenode_ip_reputation'
];

$diagnostics['tables'] = [];
foreach ($requiredTables as $table) {
    try {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        $exists = $stmt->rowCount() > 0;
        
        if (!$exists) {
            $errors[] = "Tabela '$table' não existe";
        }
        
        $diagnostics['tables'][$table] = [
            'exists' => $exists,
            'status' => $exists ? 'OK' : 'ERRO'
        ];
        
        if ($exists) {
            // Verificar estrutura da tabela
            $stmt = $db->query("DESCRIBE $table");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $diagnostics['tables'][$table]['columns'] = $columns;
            $diagnostics['tables'][$table]['column_count'] = count($columns);
        }
    } catch (PDOException $e) {
        $errors[] = "Erro ao verificar tabela '$table': " . $e->getMessage();
        $diagnostics['tables'][$table] = [
            'exists' => false,
            'status' => 'ERRO',
            'error' => $e->getMessage()
        ];
    }
}

// 3. Verificar colunas essenciais da tabela safenode_security_logs
$requiredColumns = [
    'id',
    'ip_address',
    'request_uri',
    'threat_type',
    'threat_score',
    'action_taken',
    'site_id',
    'country_code',
    'created_at'
];

$diagnostics['security_logs_columns'] = [];
try {
    if (in_array('safenode_security_logs', $requiredTables) && $diagnostics['tables']['safenode_security_logs']['exists']) {
        $stmt = $db->query("DESCRIBE safenode_security_logs");
        $existingColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($requiredColumns as $col) {
            $exists = in_array($col, $existingColumns);
            $diagnostics['security_logs_columns'][$col] = [
                'exists' => $exists,
                'status' => $exists ? 'OK' : 'ERRO'
            ];
            
            if (!$exists) {
                $errors[] = "Coluna '$col' não existe na tabela 'safenode_security_logs'";
            }
        }
    }
} catch (PDOException $e) {
    $errors[] = "Erro ao verificar colunas: " . $e->getMessage();
}

// 4. Verificar dados na tabela safenode_security_logs
$diagnostics['data_counts'] = [];
try {
    // Total de registros
    $stmt = $db->query("SELECT COUNT(*) as total FROM safenode_security_logs");
    $totalLogs = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    $diagnostics['data_counts']['total_logs'] = (int)$totalLogs;
    
    // Registros hoje
    $stmt = $db->query("SELECT COUNT(*) as total FROM safenode_security_logs WHERE DATE(created_at) = CURDATE()");
    $todayLogs = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    $diagnostics['data_counts']['today_logs'] = (int)$todayLogs;
    
    // Últimas 24 horas
    $stmt = $db->query("SELECT COUNT(*) as total FROM safenode_security_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    $last24hLogs = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    $diagnostics['data_counts']['last24h_logs'] = (int)$last24hLogs;
    
    // Registros com site_id
    $stmt = $db->query("SELECT COUNT(*) as total FROM safenode_security_logs WHERE site_id IS NOT NULL");
    $logsWithSite = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    $diagnostics['data_counts']['logs_with_site_id'] = (int)$logsWithSite;
    
    if ($totalLogs == 0) {
        $errors[] = "A tabela 'safenode_security_logs' está vazia - não há dados para exibir no dashboard";
    }
    
} catch (PDOException $e) {
    $errors[] = "Erro ao contar registros: " . $e->getMessage();
    $diagnostics['data_counts']['error'] = $e->getMessage();
}

// 5. Verificar sites do usuário
$diagnostics['user_sites'] = [];
try {
    if ($userId) {
        $stmt = $db->prepare("SELECT id, domain, is_active FROM safenode_sites WHERE user_id = ?");
        $stmt->execute([$userId]);
        $sites = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $diagnostics['user_sites']['count'] = count($sites);
        $diagnostics['user_sites']['sites'] = $sites;
        $diagnostics['user_sites']['current_site_id'] = $currentSiteId;
        
        if (count($sites) == 0) {
            $errors[] = "O usuário não possui sites cadastrados";
        }
        
        if ($currentSiteId > 0) {
            $siteExists = false;
            foreach ($sites as $site) {
                if ($site['id'] == $currentSiteId) {
                    $siteExists = true;
                    break;
                }
            }
            if (!$siteExists) {
                $errors[] = "O site_id selecionado ($currentSiteId) não pertence ao usuário ou não existe";
            }
        }
    }
} catch (PDOException $e) {
    $errors[] = "Erro ao verificar sites do usuário: " . $e->getMessage();
    $diagnostics['user_sites']['error'] = $e->getMessage();
}

// 6. Testar queries específicas do dashboard
$diagnostics['query_tests'] = [];

// Query 1: Estatísticas do dia
try {
    $sql = "SELECT COUNT(*) as total_requests,
            SUM(CASE WHEN action_taken = 'blocked' THEN 1 ELSE 0 END) as blocked_requests
            FROM safenode_security_logs 
            WHERE DATE(created_at) = CURDATE()";
    
    if ($currentSiteId > 0) {
        $sql .= " AND site_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$currentSiteId]);
    } else {
        $stmt = $db->query($sql);
    }
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $diagnostics['query_tests']['today_stats'] = [
        'status' => 'OK',
        'result' => $result
    ];
} catch (PDOException $e) {
    $errors[] = "Erro na query de estatísticas do dia: " . $e->getMessage();
    $diagnostics['query_tests']['today_stats'] = [
        'status' => 'ERRO',
        'error' => $e->getMessage()
    ];
}

// Query 2: View v_safenode_active_blocks
try {
    $stmt = $db->query("SELECT COUNT(*) as total FROM v_safenode_active_blocks");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $diagnostics['query_tests']['active_blocks_view'] = [
        'status' => 'OK',
        'result' => $result
    ];
} catch (PDOException $e) {
    $errors[] = "Erro ao acessar view 'v_safenode_active_blocks': " . $e->getMessage();
    $diagnostics['query_tests']['active_blocks_view'] = [
        'status' => 'ERRO',
        'error' => $e->getMessage(),
        'note' => 'A view pode não existir - será usado fallback'
    ];
}

// 7. Verificar se há registros com site_id NULL quando deveria ter
try {
    $stmt = $db->query("SELECT COUNT(*) as total FROM safenode_security_logs WHERE site_id IS NULL");
    $nullSiteCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    $diagnostics['data_quality']['logs_with_null_site_id'] = (int)$nullSiteCount;
    
    if ($nullSiteCount > 0 && $totalLogs > 0) {
        $percentage = round(($nullSiteCount / $totalLogs) * 100, 2);
        if ($percentage > 50) {
            $errors[] = "Aviso: $percentage% dos registros não têm site_id - isso pode afetar o filtro de sites";
        }
    }
} catch (PDOException $e) {
    $diagnostics['data_quality']['error'] = $e->getMessage();
}

// Resumo
$diagnostics['summary'] = [
    'total_errors' => count($errors),
    'has_data' => ($diagnostics['data_counts']['total_logs'] ?? 0) > 0,
    'has_sites' => ($diagnostics['user_sites']['count'] ?? 0) > 0,
    'database_connected' => $db ? true : false,
    'all_tables_exist' => !in_array(false, array_column($diagnostics['tables'], 'exists'))
];

header('Content-Type: application/json; charset=UTF-8');
echo json_encode([
    'diagnostics' => $diagnostics,
    'errors' => $errors,
    'warnings' => [],
    'timestamp' => date('Y-m-d H:i:s')
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);





