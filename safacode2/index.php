<?php
/**
 * SafeCode IDE - Entry Point
 * Serve o index.html da pasta dist para produção
 */

// Caminho para o index.html buildado
$distIndexPath = __DIR__ . '/dist/index.html';

// Se o arquivo dist/index.html existe, servir ele
if (file_exists($distIndexPath)) {
    // Ler o conteúdo do index.html buildado
    $html = file_get_contents($distIndexPath);
    
    // Servir o HTML
    header('Content-Type: text/html; charset=UTF-8');
    echo $html;
    exit;
}

// Se não existe, mostrar erro
http_response_code(404);
?>
<!DOCTYPE html>
<html>
<head>
    <title>SafeCode IDE - Build Not Found</title>
    <meta charset="UTF-8">
</head>
<body>
    <h1>Build não encontrado</h1>
    <p>Execute <code>npm run build</code> para gerar os arquivos de produção.</p>
</body>
</html>

