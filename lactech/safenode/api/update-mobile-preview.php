<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Diretório para armazenar templates temporários
$tempDir = __DIR__ . '/../temp/previews/';

// Ler dados do POST
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['token']) || !isset($data['html'])) {
    echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
    exit;
}

$token = $data['token'];
$filename = $tempDir . $token . '.json';

// Verificar se o arquivo existe
if (!file_exists($filename)) {
    echo json_encode(['success' => false, 'message' => 'Token não encontrado']);
    exit;
}

// Ler dados existentes
$templateData = json_decode(file_get_contents($filename), true);

if (!$templateData) {
    echo json_encode(['success' => false, 'message' => 'Erro ao ler dados']);
    exit;
}

// Verificar se expirou
if (isset($templateData['expires_at']) && $templateData['expires_at'] < time()) {
    unlink($filename);
    echo json_encode(['success' => false, 'message' => 'Token expirado', 'expired' => true]);
    exit;
}

// Atualizar HTML e timestamp
$templateData['html'] = $data['html'];
$templateData['updated_at'] = time();

// Salvar atualização
file_put_contents($filename, json_encode($templateData));

echo json_encode([
    'success' => true,
    'message' => 'Preview atualizado com sucesso'
]);






