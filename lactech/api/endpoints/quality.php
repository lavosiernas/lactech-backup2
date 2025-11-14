<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    require_once '../../includes/Database.class.php';
    
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // 1. Qualidade geral dos últimos 7 dias
    $stmt = $pdo->prepare("
        SELECT 
            COALESCE(AVG(fat_content), 0) as avg_fat,
            COALESCE(AVG(protein_content), 0) as avg_protein,
            COALESCE(AVG(somatic_cells), 0) as avg_scc,
            COUNT(*) as total_tests
        FROM milk_production 
        WHERE production_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        AND farm_id = 1
        AND (fat_content IS NOT NULL OR protein_content IS NOT NULL OR somatic_cells IS NOT NULL)
    ");
    $stmt->execute();
    $overall = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // 2. Gráfico de qualidade dos últimos 30 dias
    $stmt = $pdo->prepare("
        SELECT 
            production_date,
            AVG(fat_content) as avg_fat,
            AVG(protein_content) as avg_protein,
            AVG(somatic_cells) as avg_scc
        FROM milk_production 
        WHERE production_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
        AND production_date <= CURDATE() 
        AND farm_id = 1
        AND (fat_content IS NOT NULL OR protein_content IS NOT NULL OR somatic_cells IS NOT NULL)
        GROUP BY production_date
        ORDER BY production_date ASC
    ");
    $stmt->execute();
    $chart = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 3. Qualidade por animal
    $stmt = $pdo->prepare("
        SELECT 
            a.animal_number,
            a.name,
            AVG(mp.fat_content) as avg_fat,
            AVG(mp.protein_content) as avg_protein,
            AVG(mp.somatic_cells) as avg_scc,
            COUNT(mp.id) as test_count
        FROM animals a
        LEFT JOIN milk_production mp ON a.id = mp.animal_id 
            AND mp.production_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            AND mp.production_date <= CURDATE()
            AND (mp.fat_content IS NOT NULL OR mp.protein_content IS NOT NULL OR mp.somatic_cells IS NOT NULL)
        WHERE a.farm_id = 1 AND a.is_active = 1
        GROUP BY a.id, a.animal_number, a.name
        HAVING test_count > 0
        ORDER BY avg_fat DESC
        LIMIT 10
    ");
    $stmt->execute();
    $byAnimal = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $qualityData = [
        'overall' => $overall,
        'chart' => $chart,
        'by_animal' => $byAnimal
    ];
    
    echo json_encode([
        'success' => true,
        'data' => $qualityData
    ]);
            
        } catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>