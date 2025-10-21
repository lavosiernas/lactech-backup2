<?php
/**
 * API DE DEBUG - Mostrar TODOS os animais
 */

header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

try {
    require_once '../includes/config.php';
    
    $conn = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Mostrar TODOS os animais
    $sql = "SELECT 
        id,
        animal_number,
        name,
        birth_date,
        status,
        is_active,
        DATEDIFF(CURDATE(), birth_date) as age_days
    FROM animals
    ORDER BY id DESC
    LIMIT 20";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $animals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $animals,
        'count' => count($animals),
        'message' => 'Todos os animais'
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'line' => $e->getLine()
    ], JSON_UNESCAPED_UNICODE);
}
?>
