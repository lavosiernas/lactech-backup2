<?php
/**
 * API para upload e gerenciamento da logo da fazenda para relatórios
 */

require_once __DIR__ . '/../includes/config_login.php';
require_once __DIR__ . '/../includes/Database.class.php';

session_start();
if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Não autenticado']);
    exit;
}

function sendResponse($data = null, $error = null, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => $error === null,
        'data' => $data,
        'error' => $error
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    $farm_id = $_SESSION['farm_id'] ?? 1;
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    switch ($action) {
        case 'upload':
            if ($method !== 'POST') {
                sendResponse(null, 'Método não permitido', 405);
            }
            
            if (!isset($_FILES['logo']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
                sendResponse(null, 'Erro no upload do arquivo', 400);
            }
            
            $file = $_FILES['logo'];
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
            $maxSize = 5 * 1024 * 1024; // 5MB
            
            if (!in_array($file['type'], $allowedTypes)) {
                sendResponse(null, 'Tipo de arquivo não permitido. Use JPG, JPEG ou PNG.', 400);
            }
            
            if ($file['size'] > $maxSize) {
                sendResponse(null, 'Arquivo muito grande. Tamanho máximo: 5MB.', 400);
            }
            
            // Criar diretório se não existir
            $uploadDir = __DIR__ . '/../assets/img/farm_logos/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Processar imagem
            $originalPath = $file['tmp_name'];
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $isJpeg = in_array($extension, ['jpg', 'jpeg']);
            
            // Nome do arquivo
            $filename = 'farm_' . $farm_id . '_logo.png';
            $filepath = $uploadDir . $filename;
            
            // Se for JPG/JPEG, remover fundo e converter para PNG
            if ($isJpeg) {
                $processed = processImageRemoveBackground($originalPath, $filepath);
                if (!$processed) {
                    sendResponse(null, 'Erro ao processar imagem', 500);
                }
            } else {
                // Se já for PNG, apenas copiar
                if (!copy($originalPath, $filepath)) {
                    sendResponse(null, 'Erro ao salvar arquivo', 500);
                }
            }
            
            // Salvar caminho no banco de dados
            $logoPath = 'assets/img/farm_logos/' . $filename;
            $stmt = $conn->prepare("UPDATE farms SET logo_path = ? WHERE id = ?");
            $stmt->execute([$logoPath, $farm_id]);
            
            // Remover logo antiga se existir
            $stmt = $conn->prepare("SELECT logo_path FROM farms WHERE id = ?");
            $stmt->execute([$farm_id]);
            $oldLogo = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($oldLogo && $oldLogo['logo_path'] && $oldLogo['logo_path'] !== $logoPath) {
                $oldPath = __DIR__ . '/../' . $oldLogo['logo_path'];
                if (file_exists($oldPath)) {
                    @unlink($oldPath);
                }
            }
            
            sendResponse(['logo_path' => $logoPath, 'message' => 'Logo enviada com sucesso']);
            break;
            
        case 'get':
            // Retornar logo atual
            $stmt = $conn->prepare("SELECT logo_path FROM farms WHERE id = ?");
            $stmt->execute([$farm_id]);
            $farm = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $logoPath = null;
            if ($farm && !empty($farm['logo_path'])) {
                $fullPath = __DIR__ . '/../' . $farm['logo_path'];
                if (file_exists($fullPath)) {
                    $logoPath = $farm['logo_path'];
                }
            }
            
            sendResponse(['logo_path' => $logoPath]);
            break;
            
        case 'delete':
            // Remover logo
            $stmt = $conn->prepare("SELECT logo_path FROM farms WHERE id = ?");
            $stmt->execute([$farm_id]);
            $farm = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($farm && !empty($farm['logo_path'])) {
                $filepath = __DIR__ . '/../' . $farm['logo_path'];
                if (file_exists($filepath)) {
                    @unlink($filepath);
                }
            }
            
            $stmt = $conn->prepare("UPDATE farms SET logo_path = NULL WHERE id = ?");
            $stmt->execute([$farm_id]);
            
            sendResponse(['message' => 'Logo removida com sucesso']);
            break;
            
        default:
            sendResponse(null, 'Ação não reconhecida', 400);
            break;
    }
    
} catch (Exception $e) {
    error_log("Farm Logo API Error: " . $e->getMessage());
    sendResponse(null, 'Erro interno: ' . $e->getMessage(), 500);
}

/**
 * Processa imagem JPG/JPEG removendo fundo branco/claro e converte para PNG
 */
function processImageRemoveBackground($inputPath, $outputPath) {
    if (!function_exists('imagecreatefromjpeg') || !function_exists('imagecreatetruecolor')) {
        // Se GD não estiver disponível, apenas copiar e retornar false
        return false;
    }
    
    // Carregar imagem
    $image = @imagecreatefromjpeg($inputPath);
    if (!$image) {
        return false;
    }
    
    $width = imagesx($image);
    $height = imagesy($image);
    
    // Criar nova imagem com transparência
    $png = imagecreatetruecolor($width, $height);
    imagealphablending($png, false);
    imagesavealpha($png, true);
    
    // Cor transparente
    $transparent = imagecolorallocatealpha($png, 0, 0, 0, 127);
    imagefill($png, 0, 0, $transparent);
    
    // Threshold para considerar como fundo (valores altos = branco/claro)
    // Valores de 0-255, onde 255 é branco puro
    $threshold = 240; // Ajustar conforme necessário (maior = mais tolerante)
    
    // Processar cada pixel
    for ($x = 0; $x < $width; $x++) {
        for ($y = 0; $y < $height; $y++) {
            $rgb = imagecolorat($image, $x, $y);
            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8) & 0xFF;
            $b = $rgb & 0xFF;
            
            // Calcular luminosidade
            $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b);
            
            // Se for muito claro (fundo), tornar transparente
            if ($luminance >= $threshold) {
                imagesetpixel($png, $x, $y, $transparent);
            } else {
                // Manter pixel original
                $color = imagecolorallocate($png, $r, $g, $b);
                imagesetpixel($png, $x, $y, $color);
            }
        }
    }
    
    // Salvar como PNG
    $result = imagepng($png, $outputPath, 9); // 9 = máxima compressão
    imagedestroy($image);
    imagedestroy($png);
    
    return $result;
}















