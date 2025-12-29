<?php
/**
 * Exportação para PDF usando FPDF
 * Design moderno e profissional com interface melhorada
 */

require_once __DIR__ . '/../includes/config_login.php';
require_once __DIR__ . '/../includes/Database.class.php';

session_start();
if (!isLoggedIn()) {
    die('Não autenticado');
}

// Carregar FPDF
$autoload_path = __DIR__ . '/../vendor/autoload.php';
$fpdf_path = __DIR__ . '/../vendor/setasign/fpdf/fpdf.php';

$fpdf_loaded = false;

if (file_exists($autoload_path)) {
    require_once $autoload_path;
    if (class_exists('FPDF')) {
        $fpdf_loaded = true;
    } else {
        if (file_exists($fpdf_path)) {
            require_once $fpdf_path;
            $fpdf_loaded = true;
        }
    }
} elseif (file_exists($fpdf_path)) {
    require_once $fpdf_path;
    $fpdf_loaded = true;
} else {
    $fpdf_path = __DIR__ . '/../vendor/fpdf/fpdf.php';
    if (file_exists($fpdf_path)) {
        require_once $fpdf_path;
        $fpdf_loaded = true;
    }
}

if (!$fpdf_loaded || !class_exists('FPDF')) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Erro - FPDF</title>';
    echo '<style>body{font-family:Arial;padding:20px;background:#f5f5f5;}';
    echo '.error{background:white;padding:20px;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1);max-width:600px;margin:50px auto;}';
    echo 'h1{color:#d32f2f;margin-top:0;}code{background:#f5f5f5;padding:2px 6px;border-radius:3px;}</style>';
    echo '</head><body><div class="error">';
    echo '<h1>Erro: FPDF não encontrado</h1>';
    echo '<p>Para gerar PDFs, é necessário instalar a biblioteca FPDF.</p>';
    echo '<p>Execute o seguinte comando no diretório <code>lactech</code>:</p>';
    echo '<pre style="background:#f5f5f5;padding:10px;border-radius:4px;overflow-x:auto;">composer require setasign/fpdf</pre>';
    echo '</div></body></html>';
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();
$farm_id = $_SESSION['farm_id'] ?? 1;

// Buscar nome da fazenda
$farm_name = 'Fazenda';
try {
    $stmt = $conn->prepare("SELECT name FROM farms WHERE id = ?");
    $stmt->execute([$farm_id]);
    $farm_data = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($farm_data && !empty($farm_data['name'])) {
        $farm_name = $farm_data['name'];
    }
} catch (Exception $e) {
    // Usar nome padrão se houver erro
    $farm_name = 'Fazenda';
}

$report_type = $_GET['report_type'] ?? '';
$date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$filters = json_decode($_GET['filters'] ?? '[]', true);

// Caminho da logo
$logo_path = __DIR__ . '/../assets/img/lactech-logo.png';

// Função melhorada para converter UTF-8
function utf8ToIso($text) {
    if (empty($text)) return '';
    
    if (function_exists('iconv')) {
        $converted = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $text);
        if ($converted !== false && $converted !== '') {
            return $converted;
        }
    }
    
    if (function_exists('mb_convert_encoding')) {
        $converted = @mb_convert_encoding($text, 'ISO-8859-1', 'UTF-8');
        if ($converted !== false && $converted !== '') {
            return $converted;
        }
    }
    
    $map = [
        'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a', 'ä' => 'a',
        'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
        'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
        'ó' => 'o', 'ò' => 'o', 'õ' => 'o', 'ô' => 'o', 'ö' => 'o',
        'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
        'ç' => 'c', 'ñ' => 'n',
        'Á' => 'A', 'À' => 'A', 'Ã' => 'A', 'Â' => 'A', 'Ä' => 'A',
        'É' => 'E', 'È' => 'E', 'Ê' => 'E', 'Ë' => 'E',
        'Í' => 'I', 'Ì' => 'I', 'Î' => 'I', 'Ï' => 'I',
        'Ó' => 'O', 'Ò' => 'O', 'Õ' => 'O', 'Ô' => 'O', 'Ö' => 'O',
        'Ú' => 'U', 'Ù' => 'U', 'Û' => 'U', 'Ü' => 'U',
        'Ç' => 'C', 'Ñ' => 'N'
    ];
    
    return strtr($text, $map);
}

// Classe PDF com design moderno e profissional
class PDF extends FPDF {
    private $rowNum = 0;
    private $logoPath = '';
    private $farmName = '';
    private $primaryColor = [16, 185, 129]; // Verde esmeralda moderno
    private $darkColor = [5, 150, 105]; // Verde escuro
    private $lightColor = [236, 253, 245]; // Verde muito claro
    private $textColor = [31, 41, 55]; // Cinza escuro
    private $borderColor = [229, 231, 235]; // Cinza claro
    
    // Métodos públicos para acessar cores
    public function getPrimaryColor() { return $this->primaryColor; }
    public function getDarkColor() { return $this->darkColor; }
    public function getLightColor() { return $this->lightColor; }
    
    function __construct($logoPath = '', $farmName = '') {
        parent::__construct('P', 'mm', 'A4');
        $this->logoPath = $logoPath;
        $this->farmName = $farmName;
        $this->SetMargins(15, 20, 15);
    }
    
    function Header() {
        // Header completo apenas na primeira página
        if ($this->PageNo() == 1) {
            // Fundo branco limpo
            $this->SetFillColor(255, 255, 255);
            $this->Rect(0, 0, 210, 45, 'F');
            
            // Barra verde superior
            $this->SetFillColor(16, 185, 129);
            $this->Rect(0, 0, 210, 6, 'F');
            
            // Posicionamento inicial
            $x_left = 15;
            $y_top = 12;
            
            // Nome da Fazenda - em preto, grande
            if (!empty($this->farmName)) {
                $this->SetXY($x_left, $y_top);
                $this->SetTextColor(0, 0, 0); // Preto
                $this->SetFont('Arial', 'B', 24);
                $this->Cell(0, 10, utf8ToIso($this->farmName), 0, 0, 'L');
            }
            
            // LacTech - abaixo do nome da fazenda
            $this->SetXY($x_left, $y_top + 11);
            $this->SetFont('Arial', '', 10);
            $this->SetTextColor(75, 85, 99); // Cinza escuro
            $this->Cell(0, 6, utf8ToIso('LacTech - Sistema de Gestao Leiteira'), 0, 1, 'L');
            
            // Container lado direito - informações do relatório
            $x_right = 120;
            
            // Data de geração
            $this->SetXY($x_right, $y_top + 4);
            $this->SetFont('Arial', '', 9);
            $this->SetTextColor(107, 114, 128);
            $this->Cell(75, 5, utf8ToIso('Gerado em: ') . date('d/m/Y H:i'), 0, 0, 'R');
            
            // Linha separadora verde
            $this->SetDrawColor(16, 185, 129);
            $this->SetLineWidth(0.5);
            $this->Line(15, 40, 195, 40);
            
            $this->SetTextColor(0, 0, 0);
            $this->SetY(47);
        } else {
            // Páginas seguintes - apenas linha separadora sutil
            $this->SetDrawColor(229, 231, 235);
            $this->SetLineWidth(0.3);
            $this->Line(15, 10, 195, 10);
            $this->SetY(15);
        }
    }
    
    function Footer() {
        // Footer removido
    }
    
    function TableHeader($header, $w) {
        $this->rowNum = 0;
        $this->SetY($this->GetY() + 2);
        
        // Cabeçalho com gradiente
        $this->SetFillColor($this->primaryColor[0], $this->primaryColor[1], $this->primaryColor[2]);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 10);
        $this->SetDrawColor($this->darkColor[0], $this->darkColor[1], $this->darkColor[2]);
        $this->SetLineWidth(0.4);
        
        $totalWidth = array_sum($w);
        $startX = $this->GetX();
        
        for ($i = 0; $i < count($header); $i++) {
            $this->Cell($w[$i], 10, utf8ToIso($header[$i]), 1, 0, 'C', true);
        }
        $this->Ln();
        
        $this->SetTextColor(0, 0, 0);
        $this->SetFont('Arial', '', 9);
    }
    
    function TableRow($data, $w, $fill = false) {
        $this->rowNum++;
        
        // Linhas alternadas com cores suaves
        if ($this->rowNum % 2 == 0) {
            $this->SetFillColor($this->lightColor[0], $this->lightColor[1], $this->lightColor[2]);
            $fillRow = true;
        } else {
            $this->SetFillColor(255, 255, 255);
            $fillRow = false;
        }
        
        $this->SetDrawColor($this->borderColor[0], $this->borderColor[1], $this->borderColor[2]);
        $this->SetLineWidth(0.2);
        $this->SetFont('Arial', '', 8); // Fonte menor para caber mais texto
        
        $startX = $this->GetX();
        $startY = $this->GetY();
        $maxHeight = 6;
        
        // Calcular altura máxima necessária para todas as células
        $cellTexts = [];
        foreach ($data as $i => $cell) {
            $text = utf8ToIso($cell);
            $textWidth = $this->GetStringWidth($text);
            
            if ($textWidth > $w[$i] - 2) {
                // Quebrar texto manualmente
                $words = explode(' ', $text);
                $lines = [];
                $currentLine = '';
                
                foreach ($words as $word) {
                    $testLine = ($currentLine ? $currentLine . ' ' : '') . $word;
                    if ($this->GetStringWidth($testLine) > $w[$i] - 2) {
                        if ($currentLine) {
                            $lines[] = $currentLine;
                            $currentLine = $word;
                        } else {
                            // Palavra muito longa
                            $lines[] = $word;
                            $currentLine = '';
                        }
                    } else {
                        $currentLine = $testLine;
                    }
                }
                if ($currentLine) {
                    $lines[] = $currentLine;
                }
                
                $cellHeight = count($lines) * 3.5;
                if ($cellHeight > $maxHeight) {
                    $maxHeight = $cellHeight;
                }
                $cellTexts[$i] = $lines;
            } else {
                $cellTexts[$i] = [$text];
            }
        }
        
        // Desenhar células
        $x = $startX;
        foreach ($data as $i => $cell) {
            $lines = $cellTexts[$i];
            
            // Desenhar fundo da célula
            $this->SetXY($x, $startY);
            $this->Cell($w[$i], $maxHeight, '', 'LR', 0, 'L', $fillRow);
            
            // Desenhar texto linha por linha
            $y = $startY + 1;
            foreach ($lines as $line) {
                $this->SetXY($x + 1, $y);
                $this->Cell($w[$i] - 2, 3.5, $line, 0, 0, 'L');
                $y += 3.5;
            }
            
            $x += $w[$i];
        }
        
        // Linha inferior
        $this->SetDrawColor($this->borderColor[0], $this->borderColor[1], $this->borderColor[2]);
        $this->Line($startX, $startY + $maxHeight, $startX + array_sum($w), $startY + $maxHeight);
        
        $this->SetY($startY + $maxHeight);
    }
    
    function SectionTitle($title) {
        $this->Ln(6);
        
        // Barra lateral verde
        $this->SetFillColor($this->primaryColor[0], $this->primaryColor[1], $this->primaryColor[2]);
        $this->Rect(15, $this->GetY(), 5, 12, 'F');
        
        // Fundo do título
        $this->SetFillColor(249, 250, 251);
        $this->SetDrawColor($this->borderColor[0], $this->borderColor[1], $this->borderColor[2]);
        $this->Rect(20, $this->GetY(), 175, 12, 'DF');
        
        // Texto do título
        $this->SetFont('Arial', 'B', 14);
        $this->SetTextColor($this->darkColor[0], $this->darkColor[1], $this->darkColor[2]);
        $this->SetXY(25, $this->GetY() + 3);
        $this->Cell(0, 8, utf8ToIso($title), 0, 1, 'L');
        
        $this->SetTextColor(0, 0, 0);
        $this->SetY($this->GetY() + 2);
    }
    
    function InfoCard($label, $value, $x, $y, $w = 60, $h = 20) {
        // Fundo do card com borda
        $this->SetFillColor(255, 255, 255);
        $this->SetDrawColor($this->borderColor[0], $this->borderColor[1], $this->borderColor[2]);
        $this->SetLineWidth(0.3);
        $this->Rect($x, $y, $w, $h, 'DF');
        
        // Barra superior verde
        $this->SetFillColor($this->primaryColor[0], $this->primaryColor[1], $this->primaryColor[2]);
        $this->Rect($x, $y, $w, 4, 'F');
        
        // Label
        $this->SetXY($x + 5, $y + 6);
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(107, 114, 128);
        $this->Cell($w - 10, 5, utf8ToIso($label), 0, 1, 'L');
        
        // Valor
        $this->SetXY($x + 5, $y + 12);
        $this->SetFont('Arial', 'B', 12);
        $this->SetTextColor($this->darkColor[0], $this->darkColor[1], $this->darkColor[2]);
        $this->Cell($w - 10, 7, utf8ToIso($value), 0, 0, 'L');
    }
}

$pdf = new PDF($logo_path, $farm_name);
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetAutoPageBreak(true, 25);

// Título do relatório com design moderno
$report_titles = [
    'production' => 'Relatorio de Producao de Leite',
    'animals' => 'Relatorio de Rebanho',
    'health' => 'Relatorio Sanitario',
    'reproduction' => 'Relatorio Reprodutivo',
    'feeding' => 'Relatorio de Alimentacao',
    'summary' => 'Resumo Geral'
];

$report_title = $report_titles[$report_type] ?? 'Relatorio';

// Título principal com destaque
$pdf->SetFont('Arial', 'B', 22);
$pdf->SetTextColor(5, 150, 105); // Verde escuro direto
$pdf->Cell(0, 12, utf8ToIso($report_title), 0, 1, 'C');

// Linha decorativa
$pdf->SetDrawColor(16, 185, 129); // Verde principal direto
$pdf->SetLineWidth(0.8);
$pdf->Line(60, $pdf->GetY(), 150, $pdf->GetY());
$pdf->Ln(5);

// Período com estilo
$pdf->SetFont('Arial', '', 11);
$pdf->SetTextColor(75, 85, 99);
$pdf->Cell(0, 7, utf8ToIso('Periodo: ') . date('d/m/Y', strtotime($date_from)) . utf8ToIso(' a ') . date('d/m/Y', strtotime($date_to)), 0, 1, 'C');
$pdf->SetTextColor(0, 0, 0);
$pdf->Ln(8);

switch ($report_type) {
    case 'production':
        // Resumo em cards
        $stmt = $conn->prepare("
            SELECT 
                COUNT(DISTINCT mp.animal_id) as total_animals,
                SUM(mp.volume) as total_volume,
                AVG(mp.volume) as avg_volume,
                AVG(mp.fat_content) as avg_fat,
                AVG(mp.protein_content) as avg_protein
            FROM milk_production mp
            WHERE mp.farm_id = ? 
            AND DATE(mp.production_date) BETWEEN ? AND ?
        ");
        $stmt->execute([$farm_id, $date_from, $date_to]);
        $summary = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $y = $pdf->GetY();
        $pdf->InfoCard('Total de Animais', number_format($summary['total_animals'], 0, ',', '.'), 15, $y, 40, 18);
        $pdf->InfoCard('Volume Total', number_format($summary['total_volume'], 2, ',', '.') . ' L', 60, $y, 40, 18);
        $pdf->InfoCard('Media por Animal', number_format($summary['avg_volume'], 2, ',', '.') . ' L', 105, $y, 40, 18);
        $pdf->InfoCard('Gordura Media', number_format($summary['avg_fat'], 2, ',', '.') . '%', 150, $y, 40, 18);
        
        $pdf->SetY($y + 25);
        
        $pdf->SectionTitle('Producao Diaria Detalhada');
        
        $header = ['Data', 'Animais', 'Volume (L)', 'Media (L)', 'Gordura (%)', 'Proteina (%)'];
        $w = [32, 25, 32, 32, 32, 37];
        $pdf->TableHeader($header, $w);
        
        $stmt = $conn->prepare("
            SELECT 
                DATE(mp.production_date) as date,
                COUNT(DISTINCT mp.animal_id) as animals_count,
                SUM(mp.volume) as total_volume,
                AVG(mp.volume) as avg_volume,
                AVG(mp.fat_content) as avg_fat,
                AVG(mp.protein_content) as avg_protein
            FROM milk_production mp
            WHERE mp.farm_id = ? 
            AND DATE(mp.production_date) BETWEEN ? AND ?
            GROUP BY DATE(mp.production_date)
            ORDER BY date DESC
            LIMIT 50
        ");
        $stmt->execute([$farm_id, $date_from, $date_to]);
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $pdf->TableRow([
                date('d/m/Y', strtotime($row['date'])),
                number_format($row['animals_count'], 0, ',', '.'),
                number_format($row['total_volume'], 2, ',', '.'),
                number_format($row['avg_volume'], 2, ',', '.'),
                number_format($row['avg_fat'], 2, ',', '.'),
                number_format($row['avg_protein'], 2, ',', '.')
            ], $w);
        }
        break;
        
    case 'animals':
        $pdf->SectionTitle('Listagem de Animais');
        
        $header = ['Numero', 'Nome', 'Raca', 'Status', 'Saude', 'Reprodutivo'];
        // Ajustar larguras: total = 180mm (210mm - 30mm margens)
        // Distribuindo melhor o espaço para evitar cortes
        $w = [22, 35, 28, 22, 22, 51];
        $pdf->TableHeader($header, $w);
        
        $where = ["a.farm_id = ? AND (a.is_active = 1 OR a.is_active IS NULL)"];
        $params = [$farm_id];
        
        if (!empty($filters['status'])) {
            $where[] = "a.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['breed'])) {
            $where[] = "a.breed LIKE ?";
            $params[] = '%' . $filters['breed'] . '%';
        }
        
        $whereClause = implode(' AND ', $where);
        
        $stmt = $conn->prepare("
            SELECT 
                a.animal_number,
                a.name,
                a.breed,
                a.status,
                a.health_status,
                a.reproductive_status
            FROM animals a
            WHERE $whereClause
            ORDER BY a.animal_number ASC
            LIMIT 200
        ");
        $stmt->execute($params);
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $pdf->TableRow([
                $row['animal_number'] ?? '-',
                $row['name'] ?? '-',
                $row['breed'] ?? '-',
                $row['status'] ?? '-',
                $row['health_status'] ?? '-',
                $row['reproductive_status'] ?? '-'
            ], $w);
        }
        break;
        
    case 'health':
        $pdf->SectionTitle('Registros Sanitarios');
        
        $header = ['Data', 'Animal', 'Tipo', 'Descricao', 'Medicamento'];
        $w = [28, 45, 32, 50, 35];
        $pdf->TableHeader($header, $w);
        
        $stmt = $conn->prepare("
            SELECT 
                hr.record_date,
                CONCAT(a.animal_number, ' - ', COALESCE(a.name, '')) as animal,
                hr.record_type,
                hr.description,
                COALESCE(hr.medication, '') as medication
            FROM health_records hr
            LEFT JOIN animals a ON hr.animal_id = a.id
            WHERE hr.farm_id = ? 
            AND hr.record_date BETWEEN ? AND ?
            ORDER BY hr.record_date DESC
            LIMIT 100
        ");
        $stmt->execute([$farm_id, $date_from, $date_to]);
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $pdf->TableRow([
                date('d/m/Y', strtotime($row['record_date'])),
                mb_substr($row['animal'], 0, 25),
                $row['record_type'],
                mb_substr($row['description'], 0, 30),
                mb_substr($row['medication'], 0, 20)
            ], $w);
        }
        break;
        
    case 'reproduction':
        $pdf->SectionTitle('Registros Reprodutivos');
        
        $header = ['Data', 'Animal', 'Tipo', 'Resultado'];
        $w = [32, 55, 45, 58];
        $pdf->TableHeader($header, $w);
        
        $stmt = $conn->prepare("
            SELECT 
                i.insemination_date as data,
                CONCAT(a.animal_number, ' - ', COALESCE(a.name, '')) as animal,
                'Inseminacao' as tipo,
                COALESCE(i.result, 'Pendente') as resultado
            FROM inseminations i
            LEFT JOIN animals a ON i.animal_id = a.id
            WHERE i.farm_id = ? 
            AND i.insemination_date BETWEEN ? AND ?
            LIMIT 50
        ");
        $stmt->execute([$farm_id, $date_from, $date_to]);
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $pdf->TableRow([
                date('d/m/Y', strtotime($row['data'])),
                mb_substr($row['animal'], 0, 30),
                $row['tipo'],
                mb_substr($row['resultado'], 0, 35)
            ], $w);
        }
        break;
        
    case 'feeding':
        $pdf->SectionTitle('Registros de Alimentacao');
        
        $header = ['Data', 'Animal', 'Concentrado (kg)', 'Volumoso (kg)', 'Custo (R$)'];
        $w = [32, 55, 38, 38, 27];
        $pdf->TableHeader($header, $w);
        
        $stmt = $conn->prepare("
            SELECT 
                fr.feed_date,
                CONCAT(a.animal_number, ' - ', COALESCE(a.name, '')) as animal,
                fr.concentrate_kg,
                fr.roughage_kg,
                fr.total_cost
            FROM feed_records fr
            LEFT JOIN animals a ON fr.animal_id = a.id
            WHERE fr.farm_id = ? 
            AND fr.feed_date BETWEEN ? AND ?
            ORDER BY fr.feed_date DESC
            LIMIT 100
        ");
        $stmt->execute([$farm_id, $date_from, $date_to]);
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $pdf->TableRow([
                date('d/m/Y', strtotime($row['feed_date'])),
                mb_substr($row['animal'], 0, 30),
                number_format($row['concentrate_kg'], 2, ',', '.'),
                number_format($row['roughage_kg'], 2, ',', '.'),
                'R$ ' . number_format($row['total_cost'], 2, ',', '.')
            ], $w);
        }
        break;
}

$pdf->Output('D', 'relatorio_' . $report_type . '_' . date('Y-m-d') . '.pdf');
exit;
