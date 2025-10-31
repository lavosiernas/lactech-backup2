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
    
    // 2. Produção de leite hoje
    $stmt = $pdo->prepare("
        SELECT 
            COALESCE(SUM(volume), 0) as today_volume,
            COUNT(DISTINCT animal_id) as milking_animals
        FROM milk_production 
        WHERE production_date = CURDATE() AND farm_id = 1
    ");
    $stmt->execute();
    $todayProduction = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // 3. Produção de leite da semana
    $stmt = $pdo->prepare("
        SELECT 
            COALESCE(SUM(volume), 0) as week_volume,
            COALESCE(AVG(volume), 0) as avg_daily_volume
        FROM milk_production 
        WHERE production_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) 
        AND production_date <= CURDATE() 
        AND farm_id = 1
    ");
    $stmt->execute();
    $weekProduction = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // 4. Produção de leite do mês
    $stmt = $pdo->prepare("
        SELECT 
            COALESCE(SUM(volume), 0) as month_volume,
            COALESCE(AVG(volume), 0) as avg_daily_volume
        FROM milk_production 
        WHERE production_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
        AND production_date <= CURDATE() 
        AND farm_id = 1
    ");
    $stmt->execute();
    $monthProduction = $stmt->fetch(PDO::FETCH_ASSOC);
    
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
    
    // 8. Atividades recentes
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
    
    // 9. Gráfico de produção dos últimos 30 dias
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
    $productionChart = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
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