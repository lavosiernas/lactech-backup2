<?php
/**
 * KRON - Gerador de QR Code com Logo
 */

require_once __DIR__ . '/config.php';

class KronQRGenerator
{
    private $logoPath;
    
    public function __construct()
    {
        // Caminho do logo da KRON
        $this->logoPath = __DIR__ . '/../asset/kron.png';
    }
    
    /**
     * Gera QR Code (padrão, sem logo)
     */
    public function generateWithLogo($data, $size = 400)
    {
        // Gerar QR Code básico usando API (sem logo)
        return $this->generateQRCodeURL($data, $size);
    }
    
    /**
     * Gera URL do QR Code usando API
     */
    private function generateQRCodeURL($data, $size)
    {
        // Validar dados
        if (empty($data)) {
            throw new Exception('Dados do QR Code não podem estar vazios');
        }
        
        // Validar tamanho (mínimo 100, máximo 1000)
        $size = max(100, min(1000, (int)$size));
        
        // Usar API do QR Server (gratuita e confiável)
        $encodedData = urlencode($data);
        $errorCorrection = 'H'; // High error correction (permite até 30% de dano)
        
        $url = "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data={$encodedData}&ecc={$errorCorrection}&format=png";
        
        // Verificar se a URL é válida
        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            throw new Exception('Erro ao gerar URL do QR Code');
        }
        
        return $url;
    }
    
    /**
     * Mescla logo no centro do QR Code (estilo Telegram)
     */
    private function mergeLogo($qrImageUrl, $qrSize)
    {
        // Baixar imagem do QR Code
        $qrImage = @imagecreatefromstring(file_get_contents($qrImageUrl));
        
        if (!$qrImage) {
            return $qrImageUrl; // Retornar URL original se falhar
        }
        
        // Carregar logo
        $logo = @imagecreatefrompng($this->logoPath);
        
        if (!$logo) {
            return $qrImageUrl; // Retornar URL original se falhar
        }
        
        // Redimensionar logo para 15% do QR Code (estilo Telegram)
        $logoSize = (int)($qrSize * 0.15);
        $logoResized = imagescale($logo, $logoSize, $logoSize, IMG_BICUBIC);
        
        // Calcular posição central
        $logoX = (int)(($qrSize - $logoSize) / 2);
        $logoY = (int)(($qrSize - $logoSize) / 2);
        
        // Criar fundo branco arredondado para o logo (estilo Telegram)
        $whiteBgSize = $logoSize + 24; // 12px de padding de cada lado
        $whiteBgX = (int)(($qrSize - $whiteBgSize) / 2);
        $whiteBgY = (int)(($qrSize - $whiteBgSize) / 2);
        
        // Criar imagem para o fundo branco arredondado
        $whiteBg = imagecreatetruecolor($whiteBgSize, $whiteBgSize);
        imagealphablending($whiteBg, false);
        imagesavealpha($whiteBg, true);
        $transparent = imagecolorallocatealpha($whiteBg, 0, 0, 0, 127);
        imagefill($whiteBg, 0, 0, $transparent);
        
        // Desenhar círculo branco
        $white = imagecolorallocate($whiteBg, 255, 255, 255);
        $radius = $whiteBgSize / 2;
        imagefilledellipse($whiteBg, $radius, $radius, $whiteBgSize, $whiteBgSize, $white);
        
        // Mesclar fundo branco no QR Code
        imagealphablending($whiteBg, true);
        imagecopymerge($qrImage, $whiteBg, $whiteBgX, $whiteBgY, 0, 0, $whiteBgSize, $whiteBgSize, 100);
        
        // Mesclar logo no centro (com transparência preservada)
        imagealphablending($qrImage, true);
        imagesavealpha($qrImage, true);
        imagecopymerge($qrImage, $logoResized, $logoX, $logoY, 0, 0, $logoSize, $logoSize, 100);
        
        // Converter para base64
        ob_start();
        imagepng($qrImage);
        $imageData = ob_get_clean();
        
        // Limpar memória
        imagedestroy($qrImage);
        imagedestroy($logo);
        imagedestroy($logoResized);
        imagedestroy($whiteBg);
        
        return 'data:image/png;base64,' . base64_encode($imageData);
    }
    
    /**
     * Gera QR Code usando API (fallback)
     */
    private function generateWithAPI($data, $size)
    {
        return $this->generateQRCodeURL($data, $size);
    }
}

