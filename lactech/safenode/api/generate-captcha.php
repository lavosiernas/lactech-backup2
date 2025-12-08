<?php
/**
 * SafeNode - Generate CAPTCHA Image
 * Gera imagem CAPTCHA para desafios visuais
 */

session_start();

// Verificar se código foi fornecido
$code = $_GET['code'] ?? '';
$challengeId = $_GET['id'] ?? '';

if (empty($code) || empty($challengeId)) {
    http_response_code(400);
    exit;
}

// Verificar se challenge existe na sessão
$sessionKey = 'safenode_challenge_' . $challengeId;
if (!isset($_SESSION[$sessionKey])) {
    http_response_code(404);
    exit;
}

// Criar imagem CAPTCHA
$width = 200;
$height = 60;
$image = imagecreatetruecolor($width, $height);

// Cores
$bgColor = imagecolorallocate($image, 10, 10, 10);
$textColor = imagecolorallocate($image, 255, 255, 255);
$lineColor = imagecolorallocate($image, 100, 100, 100);
$noiseColor = imagecolorallocate($image, 150, 150, 150);

// Preencher fundo
imagefill($image, 0, 0, $bgColor);

// Adicionar ruído (linhas)
for ($i = 0; $i < 5; $i++) {
    imageline($image, rand(0, $width), rand(0, $height), rand(0, $width), rand(0, $height), $lineColor);
}

// Adicionar pontos de ruído
for ($i = 0; $i < 100; $i++) {
    imagesetpixel($image, rand(0, $width), rand(0, $height), $noiseColor);
}

// Adicionar texto
$fontSize = 24;
$x = 30;
$y = 40;

// Usar font built-in (ou carregar TTF se disponível)
for ($i = 0; $i < strlen($code); $i++) {
    $char = $code[$i];
    $angle = rand(-15, 15);
    imagestring($image, 5, $x + ($i * 30), $y + rand(-5, 5), $char, $textColor);
}

// Enviar imagem
header('Content-Type: image/png');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

imagepng($image);
imagedestroy($image);


