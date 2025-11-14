<?php
/**
 * API de Alertas de Saúde
 * Retorna alertas de mastite, vacinação e medicamentos do banco de dados
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../includes/Database.class.php';

try {
    $db = Database::getInstance();
    $action = $_GET['action'] ?? 'get_alerts';
    
    switch ($action) {
        case 'get_alerts':
            // Buscar alertas de mastite (usando health_alerts com alert_type = 'medicamento' ou 'outros')
            // Nota: O banco não tem tipo específico 'mastite', então buscamos em 'medicamento' ou 'outros'
            $mastitisAlerts = $db->query("
                SELECT 
                    ha.id,
                    ha.animal_id,
                    ha.alert_type,
                    ha.alert_message as message,
                    'high' as severity,
                    ha.created_at,
                    a.animal_number,
                    a.name as animal_name,
                    a.breed
                FROM health_alerts ha
                LEFT JOIN animals a ON ha.animal_id = a.id
                WHERE ha.farm_id = 1 
                AND ha.is_resolved = 0
                AND (ha.alert_type = 'medicamento' OR ha.alert_type = 'outros')
                AND (ha.alert_message LIKE '%mastite%' OR ha.alert_message LIKE '%mastitis%')
                ORDER BY ha.created_at DESC
                LIMIT 10
            ");
            
            // Buscar alertas de vacinação pendentes (usando health_records com record_type = 'Vacinação')
            $vaccinationAlerts = $db->query("
                SELECT 
                    hr.id,
                    hr.animal_id,
                    hr.medication as vaccine_name,
                    hr.next_date as due_date,
                    DATEDIFF(hr.next_date, CURDATE()) as days_remaining,
                    a.animal_number,
                    a.name as animal_name
                FROM health_records hr
                LEFT JOIN animals a ON hr.animal_id = a.id
                WHERE hr.farm_id = 1
                AND hr.record_type = 'Vacinação'
                AND hr.next_date IS NOT NULL
                AND hr.next_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                AND hr.next_date >= CURDATE()
                ORDER BY hr.next_date ASC
                LIMIT 10
            ");
            
            // Buscar medicamentos com estoque baixo (usando medications - nome correto da tabela)
            $medicineAlerts = $db->query("
                SELECT 
                    m.id,
                    m.name as medicine_name,
                    m.stock_quantity as current_stock,
                    m.min_stock as minimum_stock,
                    (m.stock_quantity - m.min_stock) as remaining_doses
                FROM medications m
                WHERE m.farm_id = 1
                AND m.is_active = 1
                AND m.stock_quantity <= m.min_stock
                ORDER BY (m.stock_quantity - m.min_stock) ASC
                LIMIT 10
            ");
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'mastitis' => $mastitisAlerts ?? [],
                    'vaccinations' => $vaccinationAlerts ?? [],
                    'medicines' => $medicineAlerts ?? []
                ]
            ]);
            break;
            
        case 'get_mastitis_alerts':
            $alerts = $db->query("
                SELECT 
                    ha.id,
                    ha.animal_id,
                    ha.alert_message as message,
                    'high' as severity,
                    ha.created_at,
                    a.animal_number,
                    a.name as animal_name
                FROM health_alerts ha
                LEFT JOIN animals a ON ha.animal_id = a.id
                WHERE ha.farm_id = 1 
                AND ha.is_resolved = 0
                AND (ha.alert_type = 'medicamento' OR ha.alert_type = 'outros')
                AND (ha.alert_message LIKE '%mastite%' OR ha.alert_message LIKE '%mastitis%')
                ORDER BY ha.created_at DESC
                LIMIT 20
            ");
            
            echo json_encode([
                'success' => true,
                'data' => $alerts ?? []
            ]);
            break;
            
        case 'get_vaccination_alerts':
            $alerts = $db->query("
                SELECT 
                    hr.id,
                    hr.animal_id,
                    hr.medication as vaccine_name,
                    hr.next_date as due_date,
                    DATEDIFF(hr.next_date, CURDATE()) as days_remaining,
                    a.animal_number,
                    a.name as animal_name
                FROM health_records hr
                LEFT JOIN animals a ON hr.animal_id = a.id
                WHERE hr.farm_id = 1
                AND hr.record_type = 'Vacinação'
                AND hr.next_date IS NOT NULL
                AND hr.next_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                AND hr.next_date >= CURDATE()
                ORDER BY hr.next_date ASC
                LIMIT 20
            ");
            
            echo json_encode([
                'success' => true,
                'data' => $alerts ?? []
            ]);
            break;
            
        case 'get_medicine_alerts':
            $alerts = $db->query("
                SELECT 
                    m.id,
                    m.name as medicine_name,
                    m.stock_quantity as current_stock,
                    m.min_stock as minimum_stock,
                    (m.stock_quantity - m.min_stock) as remaining_doses
                FROM medications m
                WHERE m.farm_id = 1
                AND m.is_active = 1
                AND m.stock_quantity <= m.min_stock
                ORDER BY (m.stock_quantity - m.min_stock) ASC
                LIMIT 20
            ");
            
            echo json_encode([
                'success' => true,
                'data' => $alerts ?? []
            ]);
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'error' => 'Ação inválida'
            ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Erro no servidor: ' . $e->getMessage()
    ]);
}
?>

