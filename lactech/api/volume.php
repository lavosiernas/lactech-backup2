<?php
// API VOLUME - CONECTADA AO BANCO REAL
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
            // Volume de hoje
            $todayVolume = $db->query("
                SELECT COALESCE(SUM(total_volume), 0) as total_today 
                FROM volume_records 
                WHERE record_date = CURDATE() AND farm_id = 1
            ");
            
            // Volume da semana
            $weekVolume = $db->query("
                SELECT COALESCE(SUM(total_volume), 0) as total_week 
                FROM volume_records 
                WHERE record_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND farm_id = 1
            ");
            
            // Volume do mês
            $monthVolume = $db->query("
                SELECT COALESCE(SUM(total_volume), 0) as total_month 
                FROM volume_records 
                WHERE record_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND farm_id = 1
            ");
            
            // Média por vaca
            $avgPerCow = $db->query("
                SELECT COALESCE(AVG(average_per_animal), 0) as avg_per_cow 
                FROM volume_records 
                WHERE record_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND farm_id = 1
            ");
            
            // Top produtores (últimos 7 dias)
            $topProducers = $db->query("
                SELECT 
                    a.id,
                    a.name,
                    COALESCE(AVG(mp.volume), 0) as volume
                FROM animals a
                LEFT JOIN milk_production mp ON a.id = mp.animal_id 
                    AND mp.production_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                WHERE a.farm_id = 1 AND a.status = 'Lactante'
                GROUP BY a.id, a.name
                ORDER BY volume DESC
                LIMIT 3
            ");
            
            // Registros recentes
            $recentRecords = $db->query("
                SELECT 
                    vr.id,
                    vr.total_volume as volume,
                    vr.record_date as date,
                    vr.shift
                FROM volume_records vr
                WHERE vr.farm_id = 1
                ORDER BY vr.record_date DESC, vr.created_at DESC
                LIMIT 5
            ");
            
            $data = [
                'total_today' => (float)$todayVolume[0]['total_today'],
                'total_week' => (float)$weekVolume[0]['total_week'],
                'total_month' => (float)$monthVolume[0]['total_month'],
                'avg_per_cow' => (float)$avgPerCow[0]['avg_per_cow'],
                'top_producers' => array_map(function($row) {
                    return [
                        'id' => (int)$row['id'],
                        'name' => $row['name'],
                        'volume' => (float)$row['volume']
                    ];
                }, $topProducers),
                'recent_records' => array_map(function($row) {
                    return [
                        'id' => (int)$row['id'],
                        'volume' => (float)$row['volume'],
                        'date' => $row['date'],
                        'shift' => $row['shift']
                    ];
                }, $recentRecords)
            ];
            sendJSONResponse($data);
            break;
            
        case 'get_dashboard_data':
            // Produção diária (últimos 7 dias)
            $dailyProduction = $db->query("
                SELECT 
                    DATE(record_date) as date,
                    SUM(total_volume) as total_volume
                FROM volume_records 
                WHERE record_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND farm_id = 1
                GROUP BY DATE(record_date)
                ORDER BY record_date ASC
            ");
            
            // Tendência semanal
            $weeklyTrend = $db->query("
                SELECT 
                    DAYNAME(record_date) as day_name,
                    SUM(total_volume) as total_volume
                FROM volume_records 
                WHERE record_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND farm_id = 1
                GROUP BY DAYNAME(record_date)
                ORDER BY record_date ASC
            ");
            
            // Resumo mensal
            $monthlySummary = $db->query("
                SELECT 
                    COALESCE(SUM(total_volume), 0) as total,
                    COALESCE(AVG(total_volume), 0) as average,
                    COUNT(DISTINCT DATE(record_date)) as days_with_data
                FROM volume_records 
                WHERE record_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND farm_id = 1
            ");
            
            // Performance das vacas
            $cowPerformance = $db->query("
                SELECT 
                    COUNT(*) as total_cows,
                    SUM(CASE WHEN status = 'Lactante' THEN 1 ELSE 0 END) as active_cows,
                    COALESCE(AVG(average_per_animal), 0) as avg_production
                FROM animals a
                LEFT JOIN volume_records vr ON DATE(vr.record_date) = CURDATE() AND vr.farm_id = a.farm_id
                WHERE a.farm_id = 1 AND a.is_active = 1
            ");
            
            $data = [
                'daily_production' => [
                    'labels' => array_map(function($row) { return date('d/m', strtotime($row['date'])); }, $dailyProduction),
                    'data' => array_map(function($row) { return (float)$row['total_volume']; }, $dailyProduction)
                ],
                'weekly_trend' => [
                    'labels' => array_map(function($row) { return $row['day_name']; }, $weeklyTrend),
                    'data' => array_map(function($row) { return (float)$row['total_volume']; }, $weeklyTrend)
                ],
                'monthly_summary' => [
                    'total' => (float)$monthlySummary[0]['total'],
                    'average' => (float)$monthlySummary[0]['average'],
                    'days_with_data' => (int)$monthlySummary[0]['days_with_data']
                ],
                'cow_performance' => [
                    'total_cows' => (int)$cowPerformance[0]['total_cows'],
                    'active_cows' => (int)$cowPerformance[0]['active_cows'],
                    'avg_production' => (float)$cowPerformance[0]['avg_production']
                ]
            ];
            sendJSONResponse($data);
            break;
            
        case 'get_all':
        case 'select':
            // Buscar todos os registros de volume
            $volumeRecords = $db->query("
                SELECT 
                    vr.id,
                    vr.record_date,
                    vr.shift,
                    vr.total_volume,
                    vr.total_animals,
                    vr.average_per_animal,
                    vr.notes,
                    u.name as recorded_by_name,
                    vr.created_at
                FROM volume_records vr
                LEFT JOIN users u ON vr.recorded_by = u.id
                WHERE vr.farm_id = 1
                ORDER BY vr.record_date DESC, vr.created_at DESC
                LIMIT 50
            ");
            
            $data = array_map(function($row) {
                return [
                    'id' => (int)$row['id'],
                    'record_date' => $row['record_date'],
                    'shift' => $row['shift'],
                    'total_volume' => (float)$row['total_volume'],
                    'total_animals' => (int)$row['total_animals'],
                    'average_per_animal' => (float)$row['average_per_animal'],
                    'notes' => $row['notes'],
                    'recorded_by_name' => $row['recorded_by_name'],
                    'created_at' => $row['created_at']
                ];
            }, $volumeRecords);
            sendJSONResponse($data);
            break;
            
        case 'get_individual':
            $animal_id = isset($_GET['animal_id']) ? (int)$_GET['animal_id'] : 0;
            if ($animal_id <= 0) {
                sendJSONResponse(null, 'animal_id inválido');
            }
            $rows = $db->query("SELECT production_date as date, volume, quality_score FROM milk_production WHERE animal_id = ? AND farm_id = 1 ORDER BY production_date DESC, id DESC LIMIT 30", [$animal_id]);
            $data = [
                'animal_id' => $animal_id,
                'records' => array_map(function($r){
                    return [
                        'date' => $r['date'],
                        'volume' => (float)$r['volume'],
                        'quality' => isset($r['quality_score']) ? (float)$r['quality_score'] : null
                    ];
                }, $rows)
            ];
            sendJSONResponse($data);
            break;
            
        case 'get_by_id':
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            if ($id <= 0) { sendJSONResponse(null, 'id inválido'); }
            $rows = $db->query("SELECT id, record_date as date, shift, total_volume, total_animals, average_per_animal, notes FROM volume_records WHERE id = ? AND farm_id = 1", [$id]);
            if (empty($rows)) { sendJSONResponse(null, 'Registro não encontrado'); }
            $r = $rows[0];
            $data = [
                'id' => (int)$r['id'],
                'date' => $r['date'],
                'shift' => $r['shift'],
                'total_volume' => (float)$r['total_volume'],
                'total_animals' => (int)$r['total_animals'],
                'average_per_animal' => (float)$r['average_per_animal'],
                'notes' => $r['notes']
            ];
            sendJSONResponse($data);
            break;
            
        case 'get_by_date':
            $date_from = $_GET['date_from'] ?? null;
            $date_to = $_GET['date_to'] ?? null;
            if (!$date_from || !$date_to) { sendJSONResponse(null, 'Parâmetros date_from e date_to são obrigatórios'); }
            $rows = $db->query("SELECT id, record_date, shift, total_volume, total_animals, average_per_animal FROM volume_records WHERE record_date BETWEEN ? AND ? AND farm_id = 1 ORDER BY record_date ASC", [$date_from, $date_to]);
            $data = [
                'date_from' => $date_from,
                'date_to' => $date_to,
                'records' => array_map(function($r){
                    return [
                        'id' => (int)$r['id'],
                        'date' => $r['record_date'],
                        'shift' => $r['shift'],
                        'total_volume' => (float)$r['total_volume'],
                        'total_animals' => (int)$r['total_animals'],
                        'average_per_animal' => (float)$r['average_per_animal'],
                    ];
                }, $rows)
            ];
            sendJSONResponse($data);
            break;
            
        case 'test':
            sendJSONResponse(['status' => 'online', 'timestamp' => date('Y-m-d H:i:s')]);
            break;

        case 'get_temperature':
            // Temperatura média por dia (últimos 30 dias) - usar volume_records
            $rows = $db->query("
                SELECT 
                    DATE(record_date) as date, 
                    AVG(temperature) as avg_temp 
                FROM volume_records 
                WHERE DATE(record_date) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
                AND DATE(record_date) <= CURDATE() 
                AND farm_id = 1 
                AND temperature IS NOT NULL 
                GROUP BY DATE(record_date) 
                ORDER BY date ASC
            ");
            
            // Se não houver dados, usar milk_production como fallback
            if (empty($rows)) {
                $rows = $db->query("
                    SELECT 
                        production_date as date, 
                        AVG(temperature) as avg_temp 
                    FROM milk_production 
                    WHERE production_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
                    AND production_date <= CURDATE() 
                    AND farm_id = 1 
                    AND temperature IS NOT NULL 
                    GROUP BY production_date 
                    ORDER BY production_date ASC
                ");
            }
            
            $data = [
                'labels' => array_map(function($r){ return $r['date']; }, $rows),
                'data' => array_map(function($r){ return (float)($r['avg_temp'] ?? 0); }, $rows)
            ];
            sendJSONResponse($data);
            break;
            
        case 'delete':
            // Excluir registro de volume
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $id = isset($input['id']) ? (int)$input['id'] : 0;
            
            if ($id <= 0) {
                sendJSONResponse(null, 'ID inválido');
                break;
            }
            
            // Verificar se o registro existe
            $existing = $db->query("SELECT id FROM volume_records WHERE id = ? AND farm_id = 1", [$id]);
            if (empty($existing)) {
                sendJSONResponse(null, 'Registro não encontrado');
                break;
            }
            
            // Excluir registro
            $db->query("DELETE FROM volume_records WHERE id = ? AND farm_id = 1", [$id]);
            
            sendJSONResponse(['message' => 'Registro excluído com sucesso', 'id' => $id]);
            break;
            
        default:
            sendJSONResponse(null, 'Ação não encontrada');
    }
    
} catch (Exception $e) {
    sendJSONResponse(null, 'Erro interno: ' . $e->getMessage());
}
?>