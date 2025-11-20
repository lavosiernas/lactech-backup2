<?php
/**
 * Arquivo de teste para diagnosticar erro 500
 */

// Ativar exibição de erros
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Teste SafeNode - Passo 1</h1>";
echo "<p>✅ PHP está funcionando!</p>";

echo "<h2>Teste 2: Verificar caminhos</h2>";
echo "<p>__DIR__: " . __DIR__ . "</p>";
echo "<p>SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'não definido') . "</p>";
echo "<p>DOCUMENT_ROOT: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'não definido') . "</p>";

echo "<h2>Teste 3: Verificar arquivo de config</h2>";
$configPath = __DIR__ . '/includes/config.php';
if (file_exists($configPath)) {
    echo "<p>✅ Arquivo config.php existe: $configPath</p>";
    try {
        require_once $configPath;
        echo "<p>✅ Arquivo config.php carregado com sucesso!</p>";
    } catch (Exception $e) {
        echo "<p>❌ Erro ao carregar config.php: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>❌ Arquivo config.php NÃO existe: $configPath</p>";
}

echo "<h2>Teste 4: Verificar config_login.php</h2>";
$configLoginPath = __DIR__ . '/../../includes/config_login.php';
if (file_exists($configLoginPath)) {
    echo "<p>✅ Arquivo config_login.php existe: $configLoginPath</p>";
} else {
    echo "<p>❌ Arquivo config_login.php NÃO existe: $configLoginPath</p>";
    echo "<p>Tentando caminhos alternativos...</p>";
    
    // Tentar outros caminhos
    $alternatives = [
        __DIR__ . '/../includes/config_login.php',
        __DIR__ . '/../../lactech/includes/config_login.php',
        dirname(dirname(__DIR__)) . '/includes/config_login.php'
    ];
    
    foreach ($alternatives as $alt) {
        if (file_exists($alt)) {
            echo "<p>✅ Encontrado em: $alt</p>";
            break;
        }
    }
}

echo "<h2>Teste 5: Verificar banco de dados</h2>";
if (function_exists('getSafeNodeDatabase')) {
    echo "<p>✅ Função getSafeNodeDatabase existe</p>";
    try {
        $db = getSafeNodeDatabase();
        if ($db) {
            echo "<p>✅ Conexão com banco de dados OK!</p>";
        } else {
            echo "<p>❌ Erro ao conectar ao banco de dados</p>";
        }
    } catch (Exception $e) {
        echo "<p>❌ Erro: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>❌ Função getSafeNodeDatabase NÃO existe</p>";
}

echo "<h2>Teste 6: Verificar sessão</h2>";
session_start();
echo "<p>✅ Sessão iniciada</p>";

echo "<hr>";
echo "<p><strong>Se você vê esta mensagem, o PHP está funcionando!</strong></p>";
echo "<p>Acesse este arquivo: safenode.cloud/test.php</p>";

