<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Diretório para armazenar templates temporários
$tempDir = __DIR__ . '/../temp/previews/';
if (!file_exists($tempDir)) {
    @mkdir($tempDir, 0755, true);
}

// Criar arquivo .htaccess para proteger o diretório
$htaccessFile = $tempDir . '.htaccess';
if (!file_exists($htaccessFile)) {
    file_put_contents($htaccessFile, "Deny from all\n");
}

// Ler dados do POST
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['html'])) {
    echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
    exit;
}

// Gerar token único
$token = bin2hex(random_bytes(16));

// Criar arquivo temporário com expiração de 1 hora
$filename = $tempDir . $token . '.json';
$templateData = [
    'html' => $data['html'],
    'frameId' => $data['frameId'] ?? null,
    'created_at' => time(),
    'updated_at' => time(),
    'expires_at' => time() + 3600 // 1 hora
];

file_put_contents($filename, json_encode($templateData));

// Limpar arquivos expirados (manutenção)
cleanExpiredPreviews($tempDir);

echo json_encode([
    'success' => true,
    'token' => $token
]);

// Função para limpar previews expirados
function cleanExpiredPreviews($dir) {
    $files = glob($dir . '*.json');
    $now = time();
    
    foreach ($files as $file) {
        $data = json_decode(file_get_contents($file), true);
        if ($data && isset($data['expires_at']) && $data['expires_at'] < $now) {
            unlink($file);
        }
    }
}
?>

