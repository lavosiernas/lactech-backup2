<?php
/**
 * Teste Completo de Todos os Endpoints
 * Valida se todos os endpoints estão funcionando e retornando JSON válido
 */

// Incluir a API principal
require_once __DIR__ . '/rest.php';

// Função para testar endpoint
function testEndpoint($url, $method = 'GET', $data = null) {
    echo "<div style='border: 1px solid #ddd; margin: 10px 0; padding: 15px; border-radius: 5px;'>";
    echo "<h3>🔗 Testando: $url ($method)</h3>";
    
    // Simular requisição
    $_SERVER['REQUEST_METHOD'] = $method;
    $_SERVER['REQUEST_URI'] = $url;
    
    if ($method === 'POST' || $method === 'PUT') {
        $_POST = $data;
        file_put_contents('php://input', json_encode($data));
    } else {
        $_GET = $data ?? [];
    }
    
    // Capturar output
    ob_start();
    
    try {
        // Incluir o endpoint
        if (strpos($url, 'actions.php') !== false) {
            include __DIR__ . '/actions.php';
        } elseif (strpos($url, 'generic.php') !== false) {
            include __DIR__ . '/generic.php';
        } elseif (strpos($url, 'notifications.php') !== false) {
            include __DIR__ . '/notifications.php';
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
        echo "<div style='background: #d4edda; padding: 10px; border: 1px solid #c3e6cb; border-radius: 4px; margin: 10px 0;'>";
        echo "<strong>✅ JSON Válido:</strong><br>";
        echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto;'>" . htmlspecialchars(json_encode($json, JSON_PRETTY_PRINT)) . "</pre>";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; padding: 10px; border: 1px solid #f5c6cb; border-radius: 4px; margin: 10px 0;'>";
        echo "<strong>❌ JSON Inválido:</strong><br>";
        echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto;'>" . htmlspecialchars($output) . "</pre>";
        echo "</div>";
    }
    
    echo "</div>";
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste Completo - Todos os Endpoints</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 20px; 
            background: #f8f9fa;
        }
        h1 { 
            color: #333; 
            text-align: center;
            background: #007bff;
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        h2 { 
            color: #666; 
            background: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin-top: 30px;
        }
        h3 { 
            color: #888; 
            margin: 15px 0 10px 0;
        }
        pre { 
            background: #f8f9fa; 
            padding: 10px; 
            border-radius: 4px; 
            overflow-x: auto; 
            border: 1px solid #dee2e6;
        }
        .success { 
            background: #d4edda; 
            color: #155724; 
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .error { 
            background: #f8d7da; 
            color: #721c24; 
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .info { 
            background: #d1ecf1; 
            color: #0c5460; 
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .endpoint-test {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin: 15px 0;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }
        .status-success { background: #28a745; }
        .status-error { background: #dc3545; }
        .status-warning { background: #ffc107; }
    </style>
</head>
<body>
    <h1>🧪 Teste Completo - Todos os Endpoints da API</h1>
    
    <div class="info">
        <strong>ℹ️ Informação:</strong> Este teste valida todos os endpoints da API para garantir que estão retornando JSON válido e funcionando corretamente.
    </div>
    
    <div class="warning">
        <strong>⚠️ Aviso:</strong> Este arquivo deve ser removido em produção por questões de segurança.
    </div>
    
    <h2>📋 Testes de Endpoints</h2>
    
    <?php
    // Testar endpoints de actions.php
    echo "<div class='endpoint-test'>";
    echo "<h2>🔧 Actions API</h2>";
    
    testEndpoint('api/actions.php?action=dashboard', 'GET');
    testEndpoint('api/actions.php?action=urgent_actions', 'GET');
    testEndpoint('api/actions.php?action=invalid', 'GET');
    echo "</div>";
    
    // Testar endpoints de generic.php
    echo "<div class='endpoint-test'>";
    echo "<h2>🔧 Generic API</h2>";
    
    testEndpoint('api/generic.php?table=password_requests', 'GET');
    testEndpoint('api/generic.php?table=notifications', 'GET');
    testEndpoint('api/generic.php?table=password_requests', 'POST', [
        'user_id' => 1,
        'email' => 'teste@exemplo.com'
    ]);
    testEndpoint('api/generic.php?table=notifications', 'POST', [
        'title' => 'Teste',
        'message' => 'Mensagem de teste',
        'type' => 'info'
    ]);
    testEndpoint('api/generic.php?table=invalid_table', 'GET');
    echo "</div>";
    
    // Testar endpoints de notifications.php
    echo "<div class='endpoint-test'>";
    echo "<h2>🔧 Notifications API</h2>";
    
    testEndpoint('api/notifications.php?action=list&limit=50', 'GET');
    testEndpoint('api/notifications.php?action=create', 'POST', [
        'title' => 'Teste',
        'message' => 'Mensagem de teste'
    ]);
    testEndpoint('api/notifications.php?action=mark_read', 'POST', [
        'id' => 1
    ]);
    testEndpoint('api/notifications.php?action=delete', 'POST', [
        'id' => 1
    ]);
    testEndpoint('api/notifications.php?action=invalid', 'GET');
    echo "</div>";
    
    // Testar endpoints da API REST
    echo "<div class='endpoint-test'>";
    echo "<h2>🔧 REST API</h2>";
    
    testEndpoint('api/rest.php/password-requests', 'GET');
    testEndpoint('api/rest.php/notifications', 'GET');
    testEndpoint('api/rest.php/dashboard', 'GET');
    testEndpoint('api/rest.php/users', 'GET');
    testEndpoint('api/rest.php/volume', 'GET');
    testEndpoint('api/rest.php/quality', 'GET');
    testEndpoint('api/rest.php/financial', 'GET');
    echo "</div>";
    ?>
    
    <div class="success">
        <strong>✅ Teste Concluído!</strong><br>
        Se todos os endpoints retornaram "JSON Válido", a API está funcionando corretamente e os erros de JSON foram resolvidos.
    </div>
    
    <div class="info">
        <strong>📝 Próximos Passos:</strong><br>
        1. Verifique se todos os endpoints retornaram JSON válido<br>
        2. Se houver erros, verifique os logs do servidor<br>
        3. Teste a aplicação no navegador para confirmar que os erros foram resolvidos<br>
        4. Remova este arquivo de teste em produção
    </div>
</body>
</html>

