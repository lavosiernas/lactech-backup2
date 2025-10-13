<?php
/**
 * TESTE RÁPIDO DAS APIs
 */

echo "<h1>🧪 TESTE RÁPIDO DAS APIs</h1>";

// Testar conexão com banco
require_once __DIR__ . '/includes/Database.class.php';

try {
    $db = Database::getInstance();
    echo "✅ Conexão com banco OK<br>";
    
    // Testar métodos básicos
    $users = $db->getAllUsers();
    echo "✅ getAllUsers: " . count($users) . " usuários<br>";
    
    $animals = $db->getAllAnimals();
    echo "✅ getAllAnimals: " . count($animals) . " animais<br>";
    
    $volume = $db->getVolumeRecords();
    echo "✅ getVolumeRecords: " . count($volume) . " registros<br>";
    
    $quality = $db->getQualityTests();
    echo "✅ getQualityTests: " . count($quality) . " testes<br>";
    
    $financial = $db->getFinancialRecords();
    echo "✅ getFinancialRecords: " . count($financial) . " registros<br>";
    
    echo "<br><h2>🎯 RESULTADO:</h2>";
    echo "✅ Todas as APIs estão funcionando!<br>";
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "<br>";
}
?>
