<?php
// Script para download seguro do APK
$apkPath = __DIR__ . '/lactechapp/LacTech.apk';
$apkName = 'LacTech-v2.2.0.apk';

// Verificar se o arquivo existe
if (!file_exists($apkPath)) {
    http_response_code(404);
    die('APK não encontrado.');
}

// Headers para download seguro
header('Content-Type: application/vnd.android.package-archive');
header('Content-Disposition: attachment; filename="' . $apkName . '"');
header('Content-Length: ' . filesize($apkPath));
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Prevenir XSS e injection
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Forçar download (não abrir no navegador)
header('Content-Transfer-Encoding: binary');

// Ler e enviar o arquivo
$handle = fopen($apkPath, 'rb');
if ($handle === false) {
    http_response_code(500);
    die('Erro ao abrir o arquivo.');
}

// Enviar o arquivo em chunks (melhor para arquivos grandes)
while (!feof($handle)) {
    echo fread($handle, 8192); // 8KB por vez
    flush();
}

fclose($handle);
exit;
?>



