<?php
/**
 * Arquivo de teste para validar os endpoints da API REST
 * Acesse: /api/test-endpoints.php
 */

// Incluir a API principal
require_once __DIR__ . '/rest.php';

// Função para testar endpoints
function testEndpoint($endpoint, $method = 'GET', $data = []) {
    echo "<h3>Testando: $endpoint ($method)</h3>";
    
    // Simular requisição
    $_SERVER['REQUEST_METHOD'] = $method;
    $_SERVER['REQUEST_URI'] = "/api/rest.php/$endpoint";
    
    if ($method === 'POST' || $method === 'PUT') {
        $_POST = $data;
        file_put_contents('php://input', json_encode($data));
    } else {
        $_GET = $data;
    }
    
    // Capturar output
    ob_start();
    
    try {
        // Incluir o endpoint
        $endpointFile = __DIR__ . "/endpoints/$endpoint.php";
        if (file_exists($endpointFile)) {
            include $endpointFile;
        } else {
            echo json_encode(['success' => false, 'error' => 'Endpoint não encontrado']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    
    $output = ob_get_clean();
    
    // Tentar decodificar JSON
    $json = json_decode($output, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "<div style='background: #d4edda; padding: 10px; border: 1px solid #c3e6cb; border-radius: 4px;'>";
        echo "<strong>✅ JSON Válido:</strong><br>";
        echo "<pre>" . htmlspecialchars(json_encode($json, JSON_PRETTY_PRINT)) . "</pre>";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; padding: 10px; border: 1px solid #f5c6cb; border-radius: 4px;'>";
        echo "<strong>❌ JSON Inválido:</strong><br>";
        echo "<pre>" . htmlspecialchars($output) . "</pre>";
        echo "</div>";
    }
    
    echo "<hr>";
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste de Endpoints - API REST Lactech</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { color: #333; }
        h2 { color: #666; }
        h3 { color: #888; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .info { background: #d1ecf1; color: #0c5460; }
    </style>
</head>
<body>
    <h1>🧪 Teste de Endpoints - API REST Lactech</h1>
    
    <div class="info" style="padding: 15px; border-radius: 4px; margin-bottom: 20px;">
        <strong>ℹ️ Informação:</strong> Este arquivo testa todos os endpoints da API REST para verificar se estão retornando JSON válido.
    </div>
    
    <h2>📋 Testes de Endpoints</h2>
    
    <?php
    // Testar cada endpoint
    $endpoints = [
        'password-requests' => [
            'GET' => ['limit' => 10],
            'POST' => ['email' => 'teste@exemplo.com']
        ],
        'notifications' => [
            'GET' => ['limit' => 10],
            'POST' => ['title' => 'Teste', 'message' => 'Mensagem de teste']
        ],
        'dashboard' => [
            'GET' => []
        ],
        'users' => [
            'GET' => ['limit' => 10],
            'POST' => ['name' => 'Teste', 'email' => 'teste@exemplo.com', 'password' => '123456', 'role' => 'funcionario']
        ],
        'volume' => [
            'GET' => ['limit' => 10],
            'POST' => ['volume' => 100, 'collection_date' => date('Y-m-d')]
        ],
        'quality' => [
            'GET' => ['limit' => 10],
            'POST' => ['test_date' => date('Y-m-d')]
        ],
        'financial' => [
            'GET' => ['limit' => 10],
            'POST' => ['type' => 'income', 'amount' => 1000, 'description' => 'Teste']
        ]
    ];
    
    foreach ($endpoints as $endpoint => $methods) {
        echo "<h2>🔗 Endpoint: $endpoint</h2>";
        
        foreach ($methods as $method => $data) {
            testEndpoint($endpoint, $method, $data);
        }
    }
    ?>
    
    <div class="success" style="padding: 15px; border-radius: 4px; margin-top: 20px;">
        <strong>✅ Teste Concluído!</strong><br>
        Se todos os endpoints retornaram "JSON Válido", a API está funcionando corretamente.
    </div>
    
    <div class="info" style="padding: 15px; border-radius: 4px; margin-top: 20px;">
        <strong>📝 Nota:</strong> Este arquivo deve ser removido em produção por questões de segurança.
    </div>
</body>
</html>

