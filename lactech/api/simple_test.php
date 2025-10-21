<?php
/**
 * TESTE SUPER SIMPLES - Sem dependências
 */

// Limpar qualquer output
ob_start();
ob_clean();

header('Content-Type: application/json');

try {
    // Conectar direto ao MySQL
    $host = 'localhost';
    $dbname = 'lactech_lgmato';
    $user = 'root';
    $pass = '';
    
    $conn = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Query super simples
    $sql = "SELECT COUNT(*) as total FROM animals";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'total_animals' => $result['total'],
        'message' => 'Conexão OK!'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
