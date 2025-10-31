<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    require_once '../../includes/Database.class.php';
    
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // 1. Volume de hoje
    $stmt = $pdo->prepare("
        SELECT 
            COALESCE(SUM(volume), 0) as total_volume,
            COUNT(DISTINCT animal_id) as milking_animals,
            COALESCE(AVG(volume), 0) as avg_per_animal
        FROM milk_production 
        WHERE production_date = CURDATE() AND farm_id = 1
    ");
    $stmt->execute();
    $today = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // 2. Volume da semana
    $stmt = $pdo->prepare("
        SELECT 
            COALESCE(SUM(volume), 0) as total_volume,
            COALESCE(AVG(volume), 0) as avg_daily_volume
        FROM milk_production 
        WHERE production_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) 
        AND production_date <= CURDATE() 
        AND farm_id = 1
    ");
    $stmt->execute();
    $week = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // 3. Volume do mês
    $stmt = $pdo->prepare("
        SELECT 
            COALESCE(SUM(volume), 0) as total_volume,
            COALESCE(AVG(volume), 0) as avg_daily_volume
        FROM milk_production 
        WHERE production_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
        AND production_date <= CURDATE() 
        AND farm_id = 1
    ");
    $stmt->execute();
    $month = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // 4. Gráfico de volume dos últimos 30 dias
    $stmt = $pdo->prepare("
        SELECT 
            production_date,
            SUM(volume) as daily_volume
        FROM milk_production 
        WHERE production_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
        AND production_date <= CURDATE() 
        AND farm_id = 1
        GROUP BY production_date
        ORDER BY production_date ASC
    ");
    $stmt->execute();
    $chart = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 5. Top produtores
    $stmt = $pdo->prepare("
        SELECT 
            a.animal_number,
            a.name,
            COALESCE(SUM(mp.volume), 0) as total_volume,
            COUNT(mp.id) as production_days
        FROM animals a
        LEFT JOIN milk_production mp ON a.id = mp.animal_id 
            AND mp.production_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            AND mp.production_date <= CURDATE()
        WHERE a.farm_id = 1 AND a.is_active = 1
        GROUP BY a.id, a.animal_number, a.name
        ORDER BY total_volume DESC
        LIMIT 10
    ");
    $stmt->execute();
    $topProducers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $volumeData = [
        'today' => $today,
        'week' => $week,
        'month' => $month,
        'chart' => $chart,
        'top_producers' => $topProducers
    ];
    
    echo json_encode([
        'success' => true,
        'data' => $volumeData
    ]);
    
        } catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>