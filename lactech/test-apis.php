<?php
// TESTE RÁPIDO DAS APIs - Verificar se estão retornando JSON válido
header('Content-Type: text/html; charset=utf-8');

echo "<h1>🧪 TESTE RÁPIDO DAS APIs</h1>";
echo "<style>body{font-family:Arial;margin:20px;} .success{color:green;} .error{color:red;} .test{margin:10px 0;padding:10px;border:1px solid #ccc;}</style>";

$apis = [
    'Volume API' => 'api/volume.php?action=get_stats',
    'Quality API' => 'api/quality.php?action=get_stats',
    'Financial API' => 'api/financial.php?action=get_dashboard_data',
    'Users API' => 'api/users.php?action=select',
    'Manager API' => 'api/manager.php?action=get_dashboard_stats'
];

foreach ($apis as $name => $url) {
    echo "<div class='test'>";
    echo "<h3>🔍 Testando: $name</h3>";
    echo "<p><strong>URL:</strong> $url</p>";
    
    try {
        $context = stream_context_create([
            'http' => [
                'timeout' => 5,
                'method' => 'GET'
            ]
        ]);
        
        $response = file_get_contents($url, false, $context);
        
        if ($response === false) {
            echo "<p class='error'>❌ Erro: Não foi possível acessar a API</p>";
        } else {
            // Verificar se é JSON válido
            $json = json_decode($response, true);
            
            if (json_last_error() === JSON_ERROR_NONE) {
                echo "<p class='success'>✅ JSON válido!</p>";
                echo "<p><strong>Resposta:</strong> " . substr($response, 0, 200) . "...</p>";
            } else {
                echo "<p class='error'>❌ JSON inválido!</p>";
                echo "<p><strong>Erro:</strong> " . json_last_error_msg() . "</p>";
                echo "<p><strong>Resposta bruta:</strong> " . htmlspecialchars(substr($response, 0, 200)) . "...</p>";
            }
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ Exceção: " . $e->getMessage() . "</p>";
    }
    
    echo "</div>";
}

echo "<h2>🎯 RESUMO</h2>";
echo "<p>Se todas as APIs estão retornando JSON válido, o problema dos erros de 'JSON inválido' deve estar resolvido!</p>";
?>
