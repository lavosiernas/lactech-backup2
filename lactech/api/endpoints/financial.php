<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    require_once '../../includes/Database.class.php';
    
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // 1. Resumo financeiro do mês
    $stmt = $pdo->prepare("
        SELECT 
            COALESCE(SUM(CASE WHEN type = 'receita' THEN amount ELSE 0 END), 0) as total_revenue,
            COALESCE(SUM(CASE WHEN type = 'despesa' THEN amount ELSE 0 END), 0) as total_expenses,
            COALESCE(SUM(CASE WHEN type = 'receita' THEN amount ELSE -amount END), 0) as net_profit
        FROM financial_records 
        WHERE record_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
        AND record_date <= CURDATE() 
        AND farm_id = 1
    ");
    $stmt->execute();
    $monthlySummary = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // 2. Gráfico de fluxo de caixa dos últimos 30 dias
    $stmt = $pdo->prepare("
        SELECT 
            record_date,
            SUM(CASE WHEN type = 'receita' THEN amount ELSE 0 END) as daily_revenue,
            SUM(CASE WHEN type = 'despesa' THEN amount ELSE 0 END) as daily_expenses
        FROM financial_records 
        WHERE record_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
        AND record_date <= CURDATE() 
        AND farm_id = 1
        GROUP BY record_date
        ORDER BY record_date ASC
    ");
    $stmt->execute();
    $cashFlowChart = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 3. Registros financeiros recentes
    $stmt = $pdo->prepare("
        SELECT 
            id,
            record_date,
            type,
            description,
            amount,
            created_at
        FROM financial_records 
        WHERE farm_id = 1
        ORDER BY record_date DESC, created_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    $recentRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $financialData = [
        'monthly_summary' => $monthlySummary,
        'cash_flow_chart' => $cashFlowChart,
        'recent_records' => $recentRecords
    ];
    
    echo json_encode([
        'success' => true,
        'data' => $financialData
    ]);
            
        } catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>