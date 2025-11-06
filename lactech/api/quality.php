<?php
// API QUALITY - CONECTADA AO BANCO REAL
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Desabilitar exibição de erros que podem quebrar o JSON
error_reporting(0);
ini_set('display_errors', 0);

// Incluir configuração do banco
require_once '../includes/Database.class.php';

// Função para enviar resposta JSON válida
function sendJSONResponse($data = null, $error = null) {
    $response = [
        'success' => $error === null,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    if ($error !== null) {
        $response['error'] = $error;
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $db = Database::getInstance();
    $action = $_GET['action'] ?? $_POST['action'] ?? 'get_stats';
    
    switch ($action) {
        case 'get_stats':
            // Médias dos últimos 30 dias
            $averages30Days = $db->query("
                SELECT 
                    COALESCE(AVG(fat_content), 0) as avg_fat,
                    COALESCE(AVG(protein_content), 0) as avg_protein,
                    COALESCE(AVG(somatic_cells), 0) as avg_ccs,
                    COALESCE(AVG(bacteria_count), 0) as avg_cbt,
                    COUNT(*) as total_tests
                FROM quality_tests 
                WHERE test_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND farm_id = 1
            ");
            
            // Tendência semanal
            $weeklyTrend = $db->query("
                SELECT 
                    DATE(test_date) as test_date,
                    AVG(fat_content) as avg_fat,
                    AVG(protein_content) as avg_protein,
                    AVG(somatic_cells) as avg_ccs
                FROM quality_tests 
                WHERE test_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND farm_id = 1
                GROUP BY DATE(test_date)
                ORDER BY test_date ASC
            ");
            
            // Distribuição por tipo de teste
            $testTypeDistribution = $db->query("
                SELECT 
                    test_type,
                    COUNT(*) as count
                FROM quality_tests 
                WHERE test_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND farm_id = 1
                GROUP BY test_type
            ");
            
            $data = [
                'averages_30_days' => [
                    'avg_fat' => (float)$averages30Days[0]['avg_fat'],
                    'avg_protein' => (float)$averages30Days[0]['avg_protein'],
                    'avg_ccs' => (float)$averages30Days[0]['avg_ccs'],
                    'avg_cbt' => (float)$averages30Days[0]['avg_cbt'],
                    'total_tests' => (int)$averages30Days[0]['total_tests']
                ],
                'weekly_trend' => array_map(function($row) {
                    return [
                        'test_date' => $row['test_date'],
                        'avg_fat' => (float)$row['avg_fat'],
                        'avg_protein' => (float)$row['avg_protein'],
                        'avg_ccs' => (float)$row['avg_ccs']
                    ];
                }, $weeklyTrend),
                'test_type_distribution' => array_map(function($row) {
                    return [
                        'test_type' => $row['test_type'],
                        'count' => (int)$row['count']
                    ];
                }, $testTypeDistribution)
            ];
            sendJSONResponse($data);
            break;
            
        case 'get_dashboard_data':
            // Resumo para dashboard
            $summary = $db->query("
                SELECT 
                    COALESCE(AVG(fat_content), 0) as avg_fat,
                    COALESCE(AVG(protein_content), 0) as avg_protein,
                    COALESCE(AVG(somatic_cells), 0) as avg_ccs,
                    COUNT(*) as total_tests
                FROM quality_tests 
                WHERE test_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND farm_id = 1
            ");
            
            // Gráfico de tendência (últimos 7 dias)
            $trendChart = $db->query("
                SELECT 
                    DATE(test_date) as date,
                    AVG(fat_content) as fat,
                    AVG(protein_content) as protein,
                    AVG(somatic_cells) as ccs
                FROM quality_tests 
                WHERE test_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND farm_id = 1
                GROUP BY DATE(test_date)
                ORDER BY test_date ASC
            ");
            
            // Se não houver dados em quality_tests, buscar de milk_production
            if (empty($trendChart)) {
                $trendChart = $db->query("
                    SELECT 
                        DATE(production_date) as date,
                        AVG(fat_content) as fat,
                        AVG(protein_content) as protein,
                        AVG(somatic_cells) as ccs
                    FROM milk_production 
                    WHERE production_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) 
                    AND farm_id = 1
                    AND (fat_content IS NOT NULL OR protein_content IS NOT NULL OR somatic_cells IS NOT NULL)
                    GROUP BY DATE(production_date)
                    ORDER BY production_date ASC
                ");
            }
            
            $data = [
                'summary' => [
                    'avg_fat' => (float)$summary[0]['avg_fat'],
                    'avg_protein' => (float)$summary[0]['avg_protein'],
                    'avg_ccs' => (float)$summary[0]['avg_ccs'],
                    'total_tests' => (int)$summary[0]['total_tests']
                ],
                'trend_chart' => array_map(function($row) {
                    return [
                        'date' => $row['date'],
                        'fat' => (float)$row['fat'],
                        'protein' => (float)$row['protein'],
                        'ccs' => (float)$row['ccs']
                    ];
                }, $trendChart)
            ];
            sendJSONResponse($data);
            break;
            
        case 'select':
            // Buscar todos os testes de qualidade
            $qualityTests = $db->query("
                SELECT 
                    qt.id,
                    qt.test_date,
                    qt.test_type,
                    qt.fat_content,
                    qt.protein_content,
                    qt.somatic_cells,
                    qt.bacteria_count,
                    qt.antibiotics,
                    qt.laboratory,
                    qt.cost,
                    qt.created_at,
                    u.name as recorded_by_name,
                    f.name as producer_name
                FROM quality_tests qt
                LEFT JOIN users u ON qt.recorded_by = u.id
                LEFT JOIN farms f ON qt.farm_id = f.id
                WHERE qt.farm_id = 1
                ORDER BY qt.test_date DESC, qt.created_at DESC
                LIMIT 50
            ");
            
            $data = array_map(function($row) {
                return [
                    'id' => (int)$row['id'],
                    'test_date' => $row['test_date'],
                    'test_type' => $row['test_type'],
                    'fat_content' => (float)$row['fat_content'],
                    'protein_content' => (float)$row['protein_content'],
                    'somatic_cells' => (int)$row['somatic_cells'],
                    'bacteria_count' => (int)$row['bacteria_count'],
                    'antibiotics' => $row['antibiotics'],
                    'laboratory' => $row['laboratory'],
                    'cost' => (float)$row['cost'],
                    'created_at' => $row['created_at'],
                    'recorded_by_name' => $row['recorded_by_name'],
                    'producer_name' => $row['producer_name']
                ];
            }, $qualityTests);
            
            sendJSONResponse($data);
            break;
            
        case 'test':
            sendJSONResponse(['status' => 'online', 'timestamp' => date('Y-m-d H:i:s')]);
            break;
            
        default:
            sendJSONResponse(null, 'Ação não encontrada');
    }
    
} catch (Exception $e) {
    sendJSONResponse(null, 'Erro interno: ' . $e->getMessage());
}
?>

