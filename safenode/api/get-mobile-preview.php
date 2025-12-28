<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Diretório de templates temporários
$tempDir = __DIR__ . '/../temp/previews/';

// Obter token
$token = isset($_GET['token']) ? $_GET['token'] : '';

if (empty($token)) {
    echo json_encode(['success' => false, 'message' => 'Token não fornecido']);
    exit;
}

// Validar token (apenas caracteres alfanuméricos)
if (!preg_match('/^[a-f0-9]{32}$/', $token)) {
    echo json_encode(['success' => false, 'message' => 'Token inválido']);
    exit;
}

$filename = $tempDir . $token . '.json';

// Verificar se arquivo existe
if (!file_exists($filename)) {
    echo json_encode([
        'success' => false,
        'expired' => true,
        'message' => 'Este link expirou ou não existe mais.'
    ]);
    exit;
}

// Ler dados do arquivo
$fileContent = file_get_contents($filename);
$templateData = json_decode($fileContent, true);

if (!$templateData) {
    echo json_encode(['success' => false, 'message' => 'Erro ao ler template']);
    exit;
}

// Verificar se expirou
$now = time();
if (isset($templateData['expires_at']) && $templateData['expires_at'] < $now) {
    // Remover arquivo expirado
    unlink($filename);
    
    echo json_encode([
        'success' => false,
        'expired' => true,
        'message' => 'Este link expirou. Os links de preview são válidos por 1 hora.'
    ]);
    exit;
}

// Retornar HTML do template
echo json_encode([
    'success' => true,
    'html' => $templateData['html'] ?? '',
    'created_at' => $templateData['created_at'] ?? time(),
    'updated_at' => $templateData['updated_at'] ?? $templateData['created_at'] ?? time()
]);
?>


