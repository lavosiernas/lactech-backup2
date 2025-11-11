<?php
/**
 * API de Alertas Reprodutivos
 * Retorna alertas de parto, teste de prenhez e cio do banco de dados
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
            // Buscar partos iminentes (próximos 7 dias)
            $birthAlerts = $db->query("
                SELECT 
                    pc.id,
                    pc.animal_id,
                    pc.expected_birth,
                    DATEDIFF(pc.expected_birth, CURDATE()) as days_remaining,
                    a.animal_number,
                    a.name as animal_name,
                    a.breed
                FROM pregnancy_controls pc
                LEFT JOIN animals a ON pc.animal_id = a.id
                WHERE pc.farm_id = 1
                AND pc.expected_birth >= CURDATE()
                AND pc.expected_birth <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                ORDER BY pc.expected_birth ASC
                LIMIT 10
            ");
            
            // Buscar animais que precisam de teste de prenhez (30 dias pós-IA)
            // Nota: pregnancy_controls não tem campo is_confirmed, usar ultrasound_result = 'positivo' como confirmação
            $pregnancyTestAlerts = $db->query("
                SELECT 
                    i.id as insemination_id,
                    i.animal_id,
                    i.insemination_date,
                    DATEDIFF(CURDATE(), i.insemination_date) as days_since_ia,
                    a.animal_number,
                    a.name as animal_name,
                    a.breed
                FROM inseminations i
                LEFT JOIN animals a ON i.animal_id = a.id
                WHERE i.farm_id = 1
                AND DATEDIFF(CURDATE(), i.insemination_date) BETWEEN 25 AND 35
                AND i.pregnancy_result = 'pendente'
                AND NOT EXISTS (
                    SELECT 1 FROM pregnancy_controls pc 
                    WHERE pc.insemination_id = i.id 
                    AND pc.ultrasound_result = 'positivo'
                )
                ORDER BY i.insemination_date DESC
                LIMIT 10
            ");
            
            // Buscar animais que devem retornar ao cio (45 dias pós-parto)
            $estrusAlerts = $db->query("
                SELECT 
                    b.id as birth_id,
                    b.animal_id,
                    b.birth_date,
                    DATEDIFF(CURDATE(), b.birth_date) as days_postpartum,
                    a.animal_number,
                    a.name as animal_name,
                    a.breed
                FROM births b
                LEFT JOIN animals a ON b.animal_id = a.id
                WHERE b.farm_id = 1
                AND DATEDIFF(CURDATE(), b.birth_date) BETWEEN 40 AND 50
                AND NOT EXISTS (
                    SELECT 1 FROM inseminations i 
                    WHERE i.animal_id = b.animal_id 
                    AND i.insemination_date > b.birth_date
                )
                ORDER BY b.birth_date DESC
                LIMIT 10
            ");
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'births' => $birthAlerts ?? [],
                    'pregnancy_tests' => $pregnancyTestAlerts ?? [],
                    'estrus' => $estrusAlerts ?? []
                ]
            ]);
            break;
            
        case 'get_birth_alerts':
            $alerts = $db->query("
                SELECT 
                    pc.id,
                    pc.animal_id,
                    pc.expected_birth,
                    DATEDIFF(pc.expected_birth, CURDATE()) as days_remaining,
                    a.animal_number,
                    a.name as animal_name
                FROM pregnancy_controls pc
                LEFT JOIN animals a ON pc.animal_id = a.id
                WHERE pc.farm_id = 1
                AND pc.expected_birth >= CURDATE()
                AND pc.expected_birth <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                ORDER BY pc.expected_birth ASC
                LIMIT 20
            ");
            
            echo json_encode([
                'success' => true,
                'data' => $alerts ?? []
            ]);
            break;
            
        case 'get_pregnancy_test_alerts':
            $alerts = $db->query("
                SELECT 
                    i.id as insemination_id,
                    i.animal_id,
                    i.insemination_date,
                    DATEDIFF(CURDATE(), i.insemination_date) as days_since_ia,
                    a.animal_number,
                    a.name as animal_name
                FROM inseminations i
                LEFT JOIN animals a ON i.animal_id = a.id
                WHERE i.farm_id = 1
                AND DATEDIFF(CURDATE(), i.insemination_date) BETWEEN 25 AND 35
                AND i.pregnancy_result = 'pendente'
                AND NOT EXISTS (
                    SELECT 1 FROM pregnancy_controls pc 
                    WHERE pc.insemination_id = i.id 
                    AND pc.ultrasound_result = 'positivo'
                )
                ORDER BY i.insemination_date DESC
                LIMIT 20
            ");
            
            echo json_encode([
                'success' => true,
                'data' => $alerts ?? []
            ]);
            break;
            
        case 'get_estrus_alerts':
            $alerts = $db->query("
                SELECT 
                    b.id as birth_id,
                    b.animal_id,
                    b.birth_date,
                    DATEDIFF(CURDATE(), b.birth_date) as days_postpartum,
                    a.animal_number,
                    a.name as animal_name
                FROM births b
                LEFT JOIN animals a ON b.animal_id = a.id
                WHERE b.farm_id = 1
                AND DATEDIFF(CURDATE(), b.birth_date) BETWEEN 40 AND 50
                AND NOT EXISTS (
                    SELECT 1 FROM inseminations i 
                    WHERE i.animal_id = b.animal_id 
                    AND i.insemination_date > b.birth_date
                )
                ORDER BY b.birth_date DESC
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

