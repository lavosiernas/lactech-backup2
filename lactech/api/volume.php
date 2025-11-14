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
            // Contar total de registros
            $totalRecords = $db->query("
                SELECT COUNT(*) as total 
                FROM volume_records 
                WHERE farm_id = 1
            ");
            
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
                'total_records' => (int)($totalRecords[0]['total'] ?? 0),
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
            // Buscar todos os registros de volume (geral + por vaca)
            $volumeRecords = $db->query("
                SELECT 
                    vr.id,
                    vr.record_date as record_date,
                    vr.shift,
                    vr.total_volume,
                    vr.total_animals,
                    vr.average_per_animal,
                    vr.notes,
                    u.name as recorded_by_name,
                    vr.created_at,
                    'general' as record_type,
                    NULL as animal_id,
                    NULL as animal_name
                FROM volume_records vr
                LEFT JOIN users u ON vr.recorded_by = u.id
                WHERE vr.farm_id = 1
                
                UNION ALL
                
                SELECT 
                    mp.id,
                    mp.production_date as record_date,
                    mp.shift,
                    mp.volume as total_volume,
                    1 as total_animals,
                    mp.volume as average_per_animal,
                    mp.notes,
                    u2.name as recorded_by_name,
                    mp.created_at,
                    'individual' as record_type,
                    mp.animal_id,
                    a.animal_number as animal_name
                FROM milk_production mp
                LEFT JOIN users u2 ON mp.recorded_by = u2.id
                LEFT JOIN animals a ON mp.animal_id = a.id
                WHERE mp.farm_id = 1
                
                ORDER BY record_date DESC, created_at DESC
                LIMIT 50
            ");
            
            $data = array_map(function($row) {
                // Log para debug - verificar se o ID está vindo do banco
                if (!isset($row['id']) || $row['id'] == 0 || $row['id'] === null) {
                    error_log("AVISO: Registro sem ID válido na query get_all: " . json_encode($row));
                }
                
                return [
                    'id' => isset($row['id']) && $row['id'] > 0 ? (int)$row['id'] : 0,
                    'record_date' => $row['record_date'],
                    'shift' => $row['shift'],
                    'total_volume' => (float)$row['total_volume'],
                    'total_animals' => (int)$row['total_animals'],
                    'average_per_animal' => (float)$row['average_per_animal'],
                    'notes' => $row['notes'],
                    'recorded_by_name' => $row['recorded_by_name'],
                    'created_at' => $row['created_at'],
                    'record_type' => $row['record_type'] ?? 'general',
                    'animal_id' => isset($row['animal_id']) ? (int)$row['animal_id'] : null,
                    'animal_name' => $row['animal_name'] ?? null
                ];
            }, $volumeRecords);
            
            // Log para debug
            error_log("DEBUG volume.php get_all - Total de registros: " . count($data));
            if (count($data) > 0) {
                error_log("DEBUG volume.php get_all - Primeiro registro: " . json_encode($data[0]));
            }
            
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
            
            // Primeiro tentar buscar em volume_records (registro geral)
            $rows = $db->query("
                SELECT 
                    id, 
                    record_date as date, 
                    shift, 
                    total_volume, 
                    total_animals, 
                    average_per_animal, 
                    notes, 
                    created_at,
                    'general' as record_type,
                    NULL as animal_id,
                    NULL as animal_name
                FROM volume_records 
                WHERE id = ? AND farm_id = 1
            ", [$id]);
            
            // Se não encontrou, buscar em milk_production (registro individual por vaca)
            if (empty($rows)) {
                $rows = $db->query("
                    SELECT 
                        mp.id, 
                        mp.production_date as date, 
                        mp.shift, 
                        mp.volume as total_volume, 
                        mp.temperature,
                        1 as total_animals, 
                        mp.volume as average_per_animal, 
                        mp.notes, 
                        mp.created_at,
                        'individual' as record_type,
                        mp.animal_id,
                        a.animal_number,
                        a.name as animal_name,
                        a.breed as animal_breed,
                        a.birth_date as animal_birth_date,
                        a.status as animal_status,
                        a.gender as animal_gender,
                        DATEDIFF(CURDATE(), a.birth_date) as animal_age_days
                    FROM milk_production mp
                    LEFT JOIN animals a ON mp.animal_id = a.id
                    WHERE mp.id = ? AND mp.farm_id = 1
                ", [$id]);
            }
            
            if (empty($rows)) { sendJSONResponse(null, 'Registro não encontrado'); }
            
            $r = $rows[0];
            $data = [
                'id' => (int)$r['id'],
                'date' => $r['date'],
                'shift' => $r['shift'],
                'total_volume' => (float)$r['total_volume'],
                'total_animals' => (int)$r['total_animals'],
                'average_per_animal' => (float)$r['average_per_animal'],
                'notes' => $r['notes'],
                'created_at' => $r['created_at'],
                'record_type' => $r['record_type'] ?? 'general',
                'animal_id' => isset($r['animal_id']) ? (int)$r['animal_id'] : null,
                'animal_name' => $r['animal_name'] ?? null,
                'animal_number' => $r['animal_number'] ?? null,
                'animal_breed' => $r['animal_breed'] ?? null,
                'animal_birth_date' => $r['animal_birth_date'] ?? null,
                'animal_age_days' => isset($r['animal_age_days']) ? (int)$r['animal_age_days'] : null,
                'animal_status' => $r['animal_status'] ?? null,
                'animal_gender' => $r['animal_gender'] ?? null,
                'temperature' => isset($r['temperature']) ? (float)$r['temperature'] : null
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
            // Temperatura média por dia (últimos 30 dias) - buscar apenas de milk_production
            // (volume_records não tem coluna temperature)
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
            
            $data = [
                'labels' => array_map(function($r){ return $r['date']; }, $rows),
                'data' => array_map(function($r){ return (float)($r['avg_temp'] ?? 0); }, $rows)
            ];
            sendJSONResponse($data);
            break;
            
        case 'delete':
            // Excluir registro de volume (geral ou individual por vaca)
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $id = isset($input['id']) ? (int)$input['id'] : 0;
            
            if ($id <= 0) {
                sendJSONResponse(null, 'ID inválido');
                break;
            }
            
            // Primeiro tentar encontrar em volume_records (registro geral)
            $existing = $db->query("SELECT id FROM volume_records WHERE id = ? AND farm_id = 1", [$id]);
            $tableName = 'volume_records';
            
            // Se não encontrou, buscar em milk_production (registro individual por vaca)
            if (empty($existing)) {
                $existing = $db->query("SELECT id FROM milk_production WHERE id = ? AND farm_id = 1", [$id]);
                $tableName = 'milk_production';
            }
            
            if (empty($existing)) {
                sendJSONResponse(null, 'Registro não encontrado');
                break;
            }
            
            // Excluir registro da tabela correta
            if ($tableName === 'volume_records') {
                $db->query("DELETE FROM volume_records WHERE id = ? AND farm_id = 1", [$id]);
            } else {
                $db->query("DELETE FROM milk_production WHERE id = ? AND farm_id = 1", [$id]);
            }
            
            sendJSONResponse(['message' => 'Registro excluído com sucesso', 'id' => $id]);
            break;
            
        default:
            sendJSONResponse(null, 'Ação não encontrada');
    }
    
} catch (Exception $e) {
    sendJSONResponse(null, 'Erro interno: ' . $e->getMessage());
}
?>