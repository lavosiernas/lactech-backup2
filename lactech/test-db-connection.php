<?php
// TESTE DE CONEXÃO COM BANCO
header('Content-Type: application/json; charset=utf-8');

try {
    require_once 'includes/Database.class.php';
    
    $db = Database::getInstance();
    
    // Teste simples
    $result = $db->query("SELECT COUNT(*) as total FROM volume_records WHERE farm_id = 1");
    
    echo json_encode([
        'success' => true,
        'message' => 'Conexão com banco OK',
        'total_records' => $result[0]['total'],
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>
