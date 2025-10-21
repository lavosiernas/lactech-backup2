<?php
/**
 * TEST API - Verificar se consegue conectar e retornar dados
 */

// Limpar qualquer output anterior
ob_start();
ob_clean();

header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

try {
    // Conectar direto sem includes problemáticos
    require_once '../includes/config.php';
    
    $conn = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Query super simples
    $sql = "SELECT 
        id,
        animal_number,
        name,
        birth_date,
        status,
        DATEDIFF(CURDATE(), birth_date) as age_days
    FROM animals
    WHERE (status = 'Novilha' OR status = 'Bezerra' OR status = 'Bezerro')
    AND is_active = 1
    LIMIT 10";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $heifers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Adicionar campos calculados
    foreach ($heifers as &$h) {
        $h['age_months'] = floor($h['age_days'] / 30);
        $h['ear_tag'] = $h['animal_number'];
        $h['category'] = $h['status'];
        $h['total_cost'] = 0;
        $h['total_records'] = 0;
        
        // Calcular fase
        $days = $h['age_days'];
        if ($days <= 60) $h['current_phase'] = 'Aleitamento';
        else if ($days <= 90) $h['current_phase'] = 'Transição/Desmame';
        else if ($days <= 180) $h['current_phase'] = 'Recria Inicial';
        else if ($days <= 365) $h['current_phase'] = 'Recria Intermediária';
        else if ($days <= 540) $h['current_phase'] = 'Crescimento/Desenvolvimento';
        else if ($days <= 780) $h['current_phase'] = 'Pré-parto';
        else $h['current_phase'] = 'Adulta';
    }
    
    echo json_encode([
        'success' => true,
        'data' => $heifers,
        'count' => count($heifers),
        'message' => 'API funcionando!'
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'line' => $e->getLine(),
        'file' => basename($e->getFile())
    ], JSON_UNESCAPED_UNICODE);
}
?>

