<?php
// API FINANCIAL - CONECTADA AO BANCO REAL
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Incluir configuração do banco
require_once '../includes/Database.class.php';

try {
    $db = Database::getInstance();
    $action = $_GET['action'] ?? $_POST['action'] ?? 'get_dashboard_data';
    
    switch ($action) {
        case 'get_dashboard_data':
            // Receita total
            $totalRevenue = $db->query("
                SELECT COALESCE(SUM(amount), 0) as total_revenue 
                FROM financial_records 
                WHERE type = 'receita' AND farm_id = 1
            ");
            
            // Receita mensal
            $monthlyRevenue = $db->query("
                SELECT COALESCE(SUM(amount), 0) as monthly_revenue 
                FROM financial_records 
                WHERE type = 'receita' AND farm_id = 1 
                AND MONTH(record_date) = MONTH(CURDATE()) 
                AND YEAR(record_date) = YEAR(CURDATE())
            ");
            
            // Despesas mensais
            $monthlyExpenses = $db->query("
                SELECT COALESCE(SUM(amount), 0) as expenses 
                FROM financial_records 
                WHERE type = 'despesa' AND farm_id = 1 
                AND MONTH(record_date) = MONTH(CURDATE()) 
                AND YEAR(record_date) = YEAR(CURDATE())
            ");
            
            // Lucro mensal
            $profit = $monthlyRevenue[0]['monthly_revenue'] - $monthlyExpenses[0]['expenses'];
            $profitMargin = $monthlyRevenue[0]['monthly_revenue'] > 0 ? 
                ($profit / $monthlyRevenue[0]['monthly_revenue']) * 100 : 0;
            
            // Dados do gráfico (últimos 6 meses)
            $chartData = $db->query("
                SELECT 
                    DATE_FORMAT(record_date, '%Y-%m') as month,
                    SUM(CASE WHEN type = 'receita' THEN amount ELSE 0 END) as revenue,
                    SUM(CASE WHEN type = 'despesa' THEN amount ELSE 0 END) as expenses
                FROM financial_records 
                WHERE farm_id = 1 
                AND record_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                GROUP BY DATE_FORMAT(record_date, '%Y-%m')
                ORDER BY month ASC
            ");
            
            $data = [
                'success' => true,
                'data' => [
                    'total_revenue' => (float)$totalRevenue[0]['total_revenue'],
                    'monthly_revenue' => (float)$monthlyRevenue[0]['monthly_revenue'],
                    'expenses' => (float)$monthlyExpenses[0]['expenses'],
                    'profit' => (float)$profit,
                    'profit_margin' => (float)$profitMargin,
                    'chart_data' => [
                        'labels' => array_map(function($row) { 
                            return date('M', strtotime($row['month'] . '-01')); 
                        }, $chartData),
                        'revenue' => array_map(function($row) { return (float)$row['revenue']; }, $chartData),
                        'expenses' => array_map(function($row) { return (float)$row['expenses']; }, $chartData),
                        'profit' => array_map(function($row) { 
                            return (float)($row['revenue'] - $row['expenses']); 
                        }, $chartData)
                    ]
                ],
                'timestamp' => date('Y-m-d H:i:s')
            ];
            break;
            
        case 'get_stats':
            // Estatísticas financeiras
            $stats = $db->query("
                SELECT 
                    COUNT(*) as total_transactions,
                    COALESCE(AVG(amount), 0) as average_transaction,
                    SUM(CASE WHEN type = 'receita' AND MONTH(record_date) = MONTH(CURDATE()) THEN amount ELSE 0 END) as current_month_income,
                    SUM(CASE WHEN type = 'receita' AND MONTH(record_date) = MONTH(CURDATE()) - 1 THEN amount ELSE 0 END) as last_month_income
                FROM financial_records 
                WHERE farm_id = 1
            ");
            
            $currentIncome = $stats[0]['current_month_income'];
            $lastIncome = $stats[0]['last_month_income'];
            $monthlyGrowth = $lastIncome > 0 ? (($currentIncome - $lastIncome) / $lastIncome) * 100 : 0;
            
            $data = [
                'success' => true,
                'data' => [
                    'total_transactions' => (int)$stats[0]['total_transactions'],
                    'average_transaction' => (float)$stats[0]['average_transaction'],
                    'monthly_growth' => (float)$monthlyGrowth
                ],
                'timestamp' => date('Y-m-d H:i:s')
            ];
            break;
            
        case 'get_reports':
            // Relatórios financeiros
            $reports = $db->query("
                SELECT 
                    fr.id,
                    fr.description as name,
                    DATE_FORMAT(fr.record_date, '%Y-%m') as period,
                    fr.amount as total,
                    fr.type,
                    fr.created_at
                FROM financial_records fr
                WHERE fr.farm_id = 1
                ORDER BY fr.record_date DESC
                LIMIT 20
            ");
            
            $data = [
                'success' => true,
                'data' => [
                    'reports' => array_map(function($row) {
                        return [
                            'id' => (int)$row['id'],
                            'name' => $row['name'],
                            'period' => $row['period'],
                            'total' => (float)$row['total'],
                            'type' => $row['type'],
                            'created_at' => $row['created_at']
                        ];
                    }, $reports)
                ],
                'timestamp' => date('Y-m-d H:i:s')
            ];
            break;
            
        case 'get_by_id':
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            if ($id <= 0) {
                $data = [
                    'success' => false,
                    'error' => 'ID inválido'
                ];
                break;
            }
            
            $rows = $db->query("SELECT * FROM financial_records WHERE id = ? AND farm_id = 1", [$id]);
            if (empty($rows)) {
                $data = [
                    'success' => false,
                    'error' => 'Registro não encontrado'
                ];
                break;
            }
            
            $r = $rows[0];
            $data = [
                'success' => true,
                'data' => [
                    'id' => (int)$r['id'],
                    'record_date' => $r['record_date'],
                    'type' => $r['type'],
                    'description' => $r['description'] ?? null,
                    'amount' => (float)$r['amount'],
                    'category' => $r['category'] ?? null,
                    'created_at' => $r['created_at'] ?? null
                ],
                'timestamp' => date('Y-m-d H:i:s')
            ];
            break;
            
        case 'delete':
            // Excluir registro financeiro
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $id = isset($input['id']) ? (int)$input['id'] : 0;
            
            if ($id <= 0) {
                $data = [
                    'success' => false,
                    'error' => 'ID inválido'
                ];
                break;
            }
            
            // Verificar se o registro existe
            $existing = $db->query("SELECT id FROM financial_records WHERE id = ? AND farm_id = 1", [$id]);
            if (empty($existing)) {
                $data = [
                    'success' => false,
                    'error' => 'Registro não encontrado'
                ];
                break;
            }
            
            // Excluir registro
            $db->query("DELETE FROM financial_records WHERE id = ? AND farm_id = 1", [$id]);
            
            $data = [
                'success' => true,
                'data' => [
                    'message' => 'Registro excluído com sucesso',
                    'id' => $id
                ],
                'timestamp' => date('Y-m-d H:i:s')
            ];
            break;
            
        default:
            $data = [
                'success' => false,
                'error' => 'Ação não encontrada',
                'available_actions' => ['get_dashboard_data', 'get_stats', 'get_reports', 'get_by_id', 'delete']
            ];
    }
    
} catch (Exception $e) {
    $data = [
        'success' => false,
        'error' => 'Erro interno: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ];
}

echo json_encode($data, JSON_UNESCAPED_UNICODE);
?>
