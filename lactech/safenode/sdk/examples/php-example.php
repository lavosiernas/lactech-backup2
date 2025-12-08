<?php
/**
 * Exemplo de uso do SDK PHP do SafeNode
 */

require_once __DIR__ . '/../php/SafeNodeHV.php';

// Inicializar SDK
$safenode = new SafeNodeHV('https://safenode.cloud/api/sdk', 'sua-api-key-aqui');

try {
    // Inicializar na página (carregamento)
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $safenode->init();
        echo "SDK inicializado! Token: " . substr($safenode->getToken(), 0, 16) . "...";
    }
    
    // Validar ao enviar formulário
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $result = $safenode->validate();
        
        if ($result['valid']) {
            // Processar formulário com segurança
            echo "Formulário validado e processado com sucesso!";
        } else {
            echo "Validação falhou: " . ($result['message'] ?? 'Erro desconhecido');
        }
    }
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
?>



