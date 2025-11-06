<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
// Evitar que avisos HTML corrompam a resposta JSON
error_reporting(0);
ini_set('display_errors', 0);

try {
    require_once '../../includes/Database.class.php';
    
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // 1. Total de animais ativos
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_animals,
            SUM(CASE WHEN status = 'Lactante' THEN 1 ELSE 0 END) as lactating_cows,
            SUM(CASE WHEN status = 'Seca' THEN 1 ELSE 0 END) as dry_cows,
            SUM(CASE WHEN status = 'Novilha' THEN 1 ELSE 0 END) as heifers,
            SUM(CASE WHEN status = 'Bezerra' THEN 1 ELSE 0 END) as calves
        FROM animals 
        WHERE is_active = 1 AND farm_id = 1
    ");
    $stmt->execute();
    $animals = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // 2. Produção de leite hoje - usar volume_records + milk_production
    $stmt = $pdo->prepare("
        SELECT 
            COALESCE(SUM(total_volume), 0) as today_volume,
            COUNT(*) as total_records
        FROM volume_records 
        WHERE DATE(record_date) = CURDATE() AND farm_id = 1
    ");
    $stmt->execute();
    $todayRecords = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Buscar de milk_production também
    $stmt = $pdo->prepare("
        SELECT 
            COALESCE(SUM(volume), 0) as today_volume,
            COUNT(DISTINCT animal_id) as milking_animals
        FROM milk_production 
        WHERE production_date = CURDATE() AND farm_id = 1
    ");
    $stmt->execute();
    $todayMilk = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Combinar dados
    $todayProduction = [
        'today_volume' => (float)($todayRecords['today_volume'] ?? 0) + (float)($todayMilk['today_volume'] ?? 0),
        'total_records' => (int)($todayRecords['total_records'] ?? 0),
        'milking_animals' => (int)($todayMilk['milking_animals'] ?? 0)
    ];
    
    // 3. Produção de leite da semana - usar volume_records + milk_production
    $stmt = $pdo->prepare("
        SELECT 
            COALESCE(SUM(total_volume), 0) as week_volume,
            COUNT(DISTINCT DATE(record_date)) as days_with_data
        FROM volume_records 
        WHERE DATE(record_date) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) 
        AND DATE(record_date) <= CURDATE() 
        AND farm_id = 1
    ");
    $stmt->execute();
    $weekRecords = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Buscar de milk_production também
    $stmt = $pdo->prepare("
        SELECT 
            COALESCE(SUM(volume), 0) as week_volume,
            COUNT(DISTINCT production_date) as days_with_data
        FROM milk_production 
        WHERE production_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) 
        AND production_date <= CURDATE() 
        AND farm_id = 1
    ");
    $stmt->execute();
    $weekMilk = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Combinar dados
    $totalWeekVolume = (float)($weekRecords['week_volume'] ?? 0) + (float)($weekMilk['week_volume'] ?? 0);
    $totalDays = max((int)($weekRecords['days_with_data'] ?? 0), (int)($weekMilk['days_with_data'] ?? 0), 1);
    
    $weekProduction = [
        'week_volume' => $totalWeekVolume,
        'avg_daily_volume' => $totalWeekVolume / $totalDays
    ];
    
    // 4. Produção de leite do mês - usar volume_records + milk_production
    $stmt = $pdo->prepare("
        SELECT 
            COALESCE(SUM(total_volume), 0) as month_volume,
            COUNT(DISTINCT DATE(record_date)) as days_with_data
        FROM volume_records 
        WHERE DATE(record_date) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
        AND DATE(record_date) <= CURDATE() 
        AND farm_id = 1
    ");
    $stmt->execute();
    $monthRecords = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Buscar de milk_production também
    $stmt = $pdo->prepare("
        SELECT 
            COALESCE(SUM(volume), 0) as month_volume,
            COUNT(DISTINCT production_date) as days_with_data
        FROM milk_production 
        WHERE production_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
        AND production_date <= CURDATE() 
        AND farm_id = 1
    ");
    $stmt->execute();
    $monthMilk = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Combinar dados
    $totalMonthVolume = (float)($monthRecords['month_volume'] ?? 0) + (float)($monthMilk['month_volume'] ?? 0);
    $totalMonthDays = max((int)($monthRecords['days_with_data'] ?? 0), (int)($monthMilk['days_with_data'] ?? 0), 1);
    
    $monthProduction = [
        'month_volume' => $totalMonthVolume,
        'avg_daily_volume' => $totalMonthVolume / $totalMonthDays
    ];
    
    // 5. Qualidade do leite (último teste)
    $stmt = $pdo->prepare("
        SELECT 
            COALESCE(AVG(fat_content), 0) as avg_fat,
            COALESCE(AVG(protein_content), 0) as avg_protein,
            COALESCE(AVG(somatic_cells), 0) as avg_scc
        FROM milk_production 
        WHERE production_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        AND farm_id = 1
    ");
    $stmt->execute();
    $quality = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // 6. Receita do mês (se existir tabela financeira)
    $stmt = $pdo->prepare("
        SELECT 
            COALESCE(SUM(amount), 0) as month_revenue
        FROM financial_records 
        WHERE type = 'receita' 
        AND record_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
        AND record_date <= CURDATE() 
        AND farm_id = 1
    ");
    $stmt->execute();
    $revenue = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // 7. Despesas do mês (se existir tabela financeira)
    $stmt = $pdo->prepare("
        SELECT 
            COALESCE(SUM(amount), 0) as month_expenses
        FROM financial_records 
        WHERE type = 'despesa' 
        AND record_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
        AND record_date <= CURDATE() 
        AND farm_id = 1
    ");
    $stmt->execute();
    $expenses = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // 8. Atividades recentes - usar volume_records
    $stmt = $pdo->prepare("
        SELECT 
            'volume_record' as type,
            CONCAT('Volume: ', FORMAT(total_volume, 1), 'L') as description,
            DATE(record_date) as date,
            CONCAT(total_animals, ' animais') as animal
        FROM volume_records 
        WHERE farm_id = 1 
        ORDER BY record_date DESC, id DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $recentActivities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Se não houver dados, usar milk_production como fallback
    if (empty($recentActivities)) {
        $stmt = $pdo->prepare("
            SELECT 
                'milk_production' as type,
                CONCAT('Produção: ', FORMAT(volume, 1), 'L') as description,
                production_date as date,
                CONCAT('Vaca ', animal_id) as animal
            FROM milk_production 
            WHERE farm_id = 1 
            ORDER BY production_date DESC, id DESC 
            LIMIT 5
        ");
        $stmt->execute();
        $recentActivities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // 9. Gráfico de produção dos últimos 30 dias - usar volume_records + milk_production
    $stmt = $pdo->prepare("
        SELECT 
            DATE(record_date) as production_date,
            SUM(total_volume) as daily_volume
        FROM volume_records 
        WHERE DATE(record_date) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
        AND DATE(record_date) <= CURDATE() 
        AND farm_id = 1
        GROUP BY DATE(record_date)
        ORDER BY production_date ASC
    ");
    $stmt->execute();
    $chartRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Buscar de milk_production também
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
    $chartMilk = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Combinar dados de ambas as tabelas
    $chartMap = [];
    
    // Adicionar dados de volume_records
    foreach ($chartRecords as $record) {
        $date = $record['production_date'];
        $chartMap[$date] = ($chartMap[$date] ?? 0) + (float)$record['daily_volume'];
    }
    
    // Adicionar dados de milk_production
    foreach ($chartMilk as $record) {
        $date = $record['production_date'];
        $chartMap[$date] = ($chartMap[$date] ?? 0) + (float)$record['daily_volume'];
    }
    
    // Converter para array ordenado
    $productionChart = [];
    foreach ($chartMap as $date => $volume) {
        $productionChart[] = [
            'production_date' => $date,
            'daily_volume' => $volume
        ];
    }
    
    // Ordenar por data
    usort($productionChart, function($a, $b) {
        return strcmp($a['production_date'], $b['production_date']);
    });
    
    // 10. Alertas importantes (se existir tabela de alertas)
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_alerts
        FROM health_alerts 
        WHERE is_resolved = 0 AND farm_id = 1
    ");
    $stmt->execute();
    $alerts = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $dashboardData = [
        'animals' => $animals,
        'today_production' => $todayProduction,
        'week_production' => $weekProduction,
        'month_production' => $monthProduction,
        'quality' => $quality,
        'revenue' => $revenue,
        'expenses' => $expenses,
        'production_chart' => $productionChart,
        'recent_activities' => $recentActivities,
        'alerts' => $alerts
    ];
    
    echo json_encode([
        'success' => true,
        'data' => $dashboardData
    ]);
    
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>