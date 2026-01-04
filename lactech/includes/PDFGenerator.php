<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

class PDFGenerator {
    private $pdf;
    private $pageWidth;
    private $pageHeight;
    private $margin;
    private $yPosition;
    private $systemLogo;
    private $farmLogo;
    private $farmName;
    
    public function __construct() {
        // Incluir FPDF se não estiver incluído
        if (!class_exists('FPDF')) {
            require_once __DIR__ . '/../vendor/fpdf/fpdf.php';
        }
        
        $this->pdf = new FPDF('P', 'mm', 'A4');
        $this->pageWidth = 210; // A4 width in mm
        $this->pageHeight = 297; // A4 height in mm
        $this->margin = 20;
        $this->yPosition = $this->margin;
        
        $this->loadLogos();
    }
    
    private function loadLogos() {
        // Logo do sistema (arquivo local)
        $logoPath = __DIR__ . '/../assets/img/lactech-logo.png';
        if (file_exists($logoPath)) {
            $this->systemLogo = $logoPath;
        } else {
            $this->systemLogo = null;
        }
        
        // Carregar logo e nome da fazenda do banco
        $this->loadFarmSettings();
    }
    
    private function loadFarmSettings() {
        try {
            // Obter farm_id do usuário atual
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                return;
            }
            
            // Buscar configurações do usuário atual
            $stmt = $pdo->prepare("
                SELECT report_farm_logo_base64, report_farm_name, farm_id 
                FROM users 
                WHERE id = ?
            ");
            $stmt->execute([$userId]);
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($userData && $userData['report_farm_logo_base64']) {
                $this->farmLogo = $userData['report_farm_logo_base64'];
                $this->farmName = $userData['report_farm_name'] ?? null;
                return;
            }
            
            // Se não tem farm_id, não pode buscar configurações do gerente
            if (!$userData['farm_id']) {
                return;
            }
            
            // Buscar configurações do gerente da fazenda como fallback
            $stmt = $pdo->prepare("
                SELECT report_farm_logo_base64, report_farm_name 
                FROM users 
                WHERE farm_id = ? AND role = 'gerente' 
                AND report_farm_logo_base64 IS NOT NULL
                LIMIT 1
            ");
            $stmt->execute([$userData['farm_id']]);
            $managerData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($managerData) {
                $this->farmLogo = $managerData['report_farm_logo_base64'];
                $this->farmName = $managerData['report_farm_name'] ?? null;
            }
            
        } catch (Exception $e) {
            error_log("Erro ao carregar configurações da fazenda: " . $e->getMessage());
        }
    }
    
    public function generateVolumeReport($data, $isPreview = false) {
        $this->pdf->AddPage();
        
        // Cabeçalho
        $this->addHeader('RELATÓRIO DE VOLUME');
        
        // Resumo
        $totalVolume = array_sum(array_column($data, 'volume_liters'));
        $avgVolume = count($data) > 0 ? $totalVolume / count($data) : 0;
        
        $this->addSummary([
            'Volume Total: ' . number_format($totalVolume, 2) . ' L',
            'Média por Registro: ' . number_format($avgVolume, 2) . ' L'
        ]);
        
        // Tabela de dados
        $headers = ['Data', 'Hora', 'Volume (L)', 'Turno', 'Observações'];
        $widths = [30, 25, 25, 25, 65];
        $aligns = ['C', 'C', 'C', 'C', 'L'];
        
        $this->addTable($headers, $widths, $aligns, $data, function($record) {
            return [
                date('d/m/Y', strtotime($record['production_date'])),
                date('H:i', strtotime($record['created_at'])),
                number_format($record['volume_liters'], 2),
                $record['shift'] ?? '',
                $record['observations'] ?? ''
            ];
        });
        
        // Rodapé
        $this->addFooter();
        
        // Marca d'água se for prévia
        if ($isPreview) {
            $this->addWatermark('PRÉVIA');
        }
        
        // Nome do arquivo
        $filename = 'relatorio_volume_' . date('Y-m-d') . '.pdf';
        $this->pdf->Output('D', $filename);
    }
    
    public function generateQualityReport($data, $isPreview = false) {
        $this->pdf->AddPage();
        
        // Cabeçalho
        $this->addHeader('RELATÓRIO DE QUALIDADE');
        
        // Resumo
        $avgFat = array_sum(array_column($data, 'fat_percentage')) / count($data);
        $avgProtein = array_sum(array_column($data, 'protein_percentage')) / count($data);
        $avgSCC = array_sum(array_column($data, 'somatic_cell_count')) / count($data);
        $avgCBT = array_sum(array_column($data, 'total_bacterial_count')) / count($data);
        
        $this->addSummary([
            'Média de Gordura: ' . number_format($avgFat, 2) . '%',
            'Média de Proteína: ' . number_format($avgProtein, 2) . '%',
            'Média de CCS: ' . number_format($avgSCC, 0) . ' cél/mL',
            'Média de CBT: ' . number_format($avgCBT, 0) . ' UFC/mL'
        ]);
        
        // Tabela de dados
        $headers = ['Data', 'Gordura (%)', 'Proteína (%)', 'CCS', 'CBT'];
        $widths = [35, 30, 30, 30, 30];
        $aligns = ['C', 'C', 'C', 'C', 'C'];
        
        $this->addTable($headers, $widths, $aligns, $data, function($record) {
            return [
                date('d/m/Y', strtotime($record['test_date'])),
                number_format($record['fat_percentage'], 2),
                number_format($record['protein_percentage'], 2),
                number_format($record['somatic_cell_count'], 0),
                number_format($record['total_bacterial_count'], 0)
            ];
        });
        
        // Rodapé
        $this->addFooter();
        
        // Marca d'água se for prévia
        if ($isPreview) {
            $this->addWatermark('PRÉVIA');
        }
        
        // Nome do arquivo
        $filename = 'relatorio_qualidade_' . date('Y-m-d') . '.pdf';
        $this->pdf->Output('D', $filename);
    }
    
    public function generatePaymentsReport($data, $isPreview = false) {
        $this->pdf->AddPage();
        
        // Cabeçalho
        $this->addHeader('RELATÓRIO DE PAGAMENTOS');
        
        // Resumo
        $totalGross = array_sum(array_column($data, 'amount'));
        $totalNet = $totalGross; // Assumindo que não há descontos
        
        $this->addSummary([
            'Valor Bruto Total: R$ ' . number_format($totalGross, 2),
            'Valor Líquido Total: R$ ' . number_format($totalNet, 2)
        ]);
        
        // Tabela de dados
        $headers = ['Data', 'Descrição', 'Categoria', 'Valor', 'Status'];
        $widths = [30, 40, 30, 30, 25];
        $aligns = ['C', 'L', 'C', 'R', 'C'];
        
        $this->addTable($headers, $widths, $aligns, $data, function($record) {
            return [
                date('d/m/Y', strtotime($record['record_date'] ?? $record['created_at'])),
                substr($record['description'] ?? 'Receita', 0, 20),
                $record['category'] ?? 'venda_leite',
                'R$ ' . number_format($record['amount'], 2),
                'Realizado'
            ];
        });
        
        // Rodapé
        $this->addFooter();
        
        // Marca d'água se for prévia
        if ($isPreview) {
            $this->addWatermark('PRÉVIA');
        }
        
        // Nome do arquivo
        $filename = 'relatorio_financeiro_' . date('Y-m-d') . '.pdf';
        $this->pdf->Output('D', $filename);
    }
    
    private function addHeader($title) {
        // Logo da fazenda (canto superior direito)
        if ($this->farmLogo) {
            // Converter base64 para arquivo temporário
            $tempFile = $this->base64ToTempFile($this->farmLogo);
            if ($tempFile) {
                $this->pdf->Image($tempFile, $this->pageWidth - 50, 15, 30, 30);
                unlink($tempFile); // Limpar arquivo temporário
            }
        }
        
        // Marca d'água (logo do sistema transparente no centro)
        if ($this->systemLogo && file_exists($this->systemLogo)) {
            try {
                $this->pdf->SetAlpha(0.05);
                $watermarkSize = 220;
                $watermarkX = ($this->pageWidth - $watermarkSize) / 2;
                $watermarkY = ($this->pageHeight - $watermarkSize) / 2;
                $this->pdf->Image($this->systemLogo, $watermarkX, $watermarkY, $watermarkSize, $watermarkSize);
                $this->pdf->SetAlpha(1);
            } catch (Exception $e) {
                error_log("Erro ao adicionar marca d'água: " . $e->getMessage());
            }
        }
        
        $this->yPosition = 30;
        
        $this->pdf->SetFont('Arial', 'B', 18);
        $titleText = $this->farmName ? $title . ' - ' . $this->farmName : $title;
        $this->pdf->SetXY($this->margin, $this->yPosition);
        $this->pdf->Cell(0, 10, $titleText, 0, 1, 'L');
        $this->yPosition += 15;
    }
    
    private function addSummary($items) {
        $this->pdf->SetFont('Arial', 'B', 14);
        $this->pdf->SetXY($this->margin, $this->yPosition);
        $this->pdf->Cell(0, 10, 'RESUMO', 0, 1, 'L');
        $this->yPosition += 8;
        
        $this->pdf->SetFont('Arial', '', 12);
        foreach ($items as $item) {
            $this->pdf->SetXY($this->margin, $this->yPosition);
            $this->pdf->Cell(0, 8, $item, 0, 1, 'L');
            $this->yPosition += 6;
        }
        $this->yPosition += 10;
    }
    
    private function addTable($headers, $widths, $aligns, $data, $callback) {
        $this->pdf->SetFont('Arial', 'B', 14);
        $this->pdf->SetXY($this->margin, $this->yPosition);
        $this->pdf->Cell(0, 10, 'DETALHAMENTO DOS REGISTROS', 0, 1, 'L');
        $this->yPosition += 12;
        
        // Cabeçalho da tabela
        $this->pdf->SetFont('Arial', 'B', 10);
        $xPosition = $this->margin;
        
        foreach ($headers as $index => $header) {
            $this->pdf->SetXY($xPosition, $this->yPosition);
            $this->pdf->Cell($widths[$index], 8, $header, 1, 0, 'C');
            $xPosition += $widths[$index];
        }
        $this->yPosition += 8;
        
        // Linha separadora
        $this->pdf->Line($this->margin, $this->yPosition, $this->pageWidth - $this->margin, $this->yPosition);
        $this->yPosition += 5;
        
        // Dados da tabela
        $this->pdf->SetFont('Arial', '', 10);
        
        foreach ($data as $record) {
            // Verificar se precisa de nova página
            if ($this->yPosition > $this->pageHeight - 30) {
                $this->pdf->AddPage();
                $this->yPosition = $this->margin;
            }
            
            $rowData = $callback($record);
            $xPosition = $this->margin;
            
            foreach ($rowData as $index => $cell) {
                $this->pdf->SetXY($xPosition, $this->yPosition);
                $this->pdf->Cell($widths[$index], 6, $cell, 0, 0, $aligns[$index]);
                $xPosition += $widths[$index];
            }
            $this->yPosition += 6;
        }
        $this->yPosition += 15;
    }
    
    private function addFooter() {
        $footerY = $this->pageHeight - 25;
        
        // Linha decorativa
        $this->pdf->SetDrawColor(203, 213, 225);
        $this->pdf->Line($this->margin, $footerY - 5, $this->pageWidth - $this->margin, $footerY - 5);
        
        // Logo menor do sistema
        if ($this->systemLogo && file_exists($this->systemLogo)) {
            try {
                $this->pdf->Image($this->systemLogo, $this->margin, $footerY, 8, 8);
            } catch (Exception $e) {
                error_log("Erro ao adicionar logo no footer: " . $e->getMessage());
            }
        }
        
        $this->pdf->SetFont('Arial', '', 8);
        $this->pdf->SetXY($this->margin + 12, $footerY + 2);
        $this->pdf->Cell(0, 4, 'Sistema de Gestão Leiteira', 0, 0, 'L');
        
        $this->pdf->SetFont('Arial', 'B', 8);
        $this->pdf->SetXY($this->margin + 12, $footerY + 6);
        $this->pdf->Cell(0, 4, 'LacTech', 0, 0, 'L');
        
        // Data/hora da geração no canto direito
        $this->pdf->SetFont('Arial', 'I', 7);
        $this->pdf->SetXY(0, $footerY + 4);
        $this->pdf->Cell($this->pageWidth - $this->margin, 4, 'Gerado em: ' . date('d/m/Y H:i:s'), 0, 0, 'R');
    }
    
    private function addWatermark($text) {
        $this->pdf->SetFont('Arial', 'B', 50);
        $this->pdf->SetTextColor(255, 0, 0);
        $this->pdf->SetAlpha(0.3);
        
        // Rotacionar texto
        $this->pdf->StartTransform();
        $this->pdf->Rotate(45, $this->pageWidth / 2, $this->pageHeight / 2);
        $this->pdf->SetXY($this->pageWidth / 2, $this->pageHeight / 2);
        $this->pdf->Cell(0, 20, $text, 0, 0, 'C');
        $this->pdf->StopTransform();
        
        $this->pdf->SetAlpha(1);
        $this->pdf->SetTextColor(0, 0, 0);
    }
    
    private function base64ToTempFile($base64Data) {
        try {
            // Remover prefixo data:image/...;base64, se existir
            if (strpos($base64Data, ',') !== false) {
                $base64Data = explode(',', $base64Data)[1];
            }
            
            $tempFile = tempnam(sys_get_temp_dir(), 'pdf_logo_');
            file_put_contents($tempFile, base64_decode($base64Data));
            return $tempFile;
        } catch (Exception $e) {
            error_log("Erro ao converter base64 para arquivo temporário: " . $e->getMessage());
            return false;
        }
    }
    
    private function downloadImageToTemp($url) {
        try {
            $tempFile = tempnam(sys_get_temp_dir(), 'pdf_logo_');
            $imageData = file_get_contents($url);
            file_put_contents($tempFile, $imageData);
            return $tempFile;
        } catch (Exception $e) {
            error_log("Erro ao baixar imagem: " . $e->getMessage());
            return false;
        }
    }
}
?>
















