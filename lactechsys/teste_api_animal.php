<?php
// Teste da API de animais
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/Database.class.php';

echo "<h2>Teste da API de Animais</h2>";

try {
    $db = Database::getInstance();
    echo "<p>✅ Conexão com banco OK</p>";
    
    // Teste 1: Verificar se a tabela animals existe
    $stmt = $db->query("SHOW TABLES LIKE 'animals'");
    $table = $stmt->fetch();
    if ($table) {
        echo "<p>✅ Tabela 'animals' existe</p>";
    } else {
        echo "<p>❌ Tabela 'animals' NÃO existe</p>";
        exit;
    }
    
    // Teste 2: Verificar estrutura da tabela
    $stmt = $db->query("DESCRIBE animals");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>✅ Colunas da tabela animals:</p><ul>";
    foreach ($columns as $column) {
        echo "<li>{$column['Field']} - {$column['Type']} - {$column['Null']} - {$column['Key']}</li>";
    }
    echo "</ul>";
    
    // Teste 3: Verificar dados existentes
    $stmt = $db->query("SELECT COUNT(*) as total FROM animals");
    $count = $stmt->fetch();
    echo "<p>✅ Total de animais no banco: {$count['total']}</p>";
    
    // Teste 4: Testar inserção
    $testData = [
        'animal_number' => 'TESTE001',
        'animal_name' => 'Animal Teste',
        'birth_date' => '2023-01-01',
        'breed' => 'Holandês',
        'gender' => 'femea',
        'status' => 'Bezerra',
        'origin' => 'Nascido na Fazenda',
        'father_id' => null,
        'mother_id' => null,
        'notes' => 'Teste de inserção'
    ];
    
    echo "<p>🧪 Testando inserção com dados:</p><pre>" . json_encode($testData, JSON_PRETTY_PRINT) . "</pre>";
    
    $result = $db->createAnimal($testData);
    
    if ($result['success']) {
        echo "<p>✅ Inserção bem-sucedida! ID: {$result['id']}</p>";
        
        // Limpar o teste
        $db->query("DELETE FROM animals WHERE id = ?", [$result['id']]);
        echo "<p>🧹 Animal de teste removido</p>";
    } else {
        echo "<p>❌ Erro na inserção: {$result['error']}</p>";
    }
    
    // Teste 5: Testar busca
    $animals = $db->getAllAnimals();
    echo "<p>✅ Busca de animais funcionando: " . count($animals) . " animais encontrados</p>";
    
} catch (Exception $e) {
    echo "<p>❌ Erro: " . $e->getMessage() . "</p>";
}
?>
