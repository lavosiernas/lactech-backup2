<?php
/**
 * Exportação para Excel (CSV formatado)
 */

require_once __DIR__ . '/../includes/config_login.php';
require_once __DIR__ . '/../includes/Database.class.php';

session_start();
if (!isLoggedIn()) {
    die('Não autenticado');
}

$db = Database::getInstance();
$conn = $db->getConnection();
$farm_id = $_SESSION['farm_id'] ?? 1;

$report_type = $_GET['report_type'] ?? '';
$date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$filters = json_decode($_GET['filters'] ?? '[]', true);

// Validar e normalizar datas
$date_from = date('Y-m-d', strtotime($date_from));
$date_to = date('Y-m-d', strtotime($date_to));

// Função para escapar CSV
function escapeCSV($value) {
    if (is_null($value)) return '';
    $value = str_replace('"', '""', $value);
    if (strpos($value, ',') !== false || strpos($value, '"') !== false || strpos($value, "\n") !== false) {
        return '"' . $value . '"';
    }
    return $value;
}

// Headers para Excel
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="relatorio_' . $report_type . '_' . date('Y-m-d') . '.csv"');
header('Pragma: no-cache');
header('Expires: 0');

// BOM para UTF-8 (Excel)
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');

switch ($report_type) {
    case 'production':
        fputcsv($output, ['Data', 'Animais', 'Volume Total (L)', 'Média (L)', 'Gordura (%)', 'Proteína (%)', 'Células Somáticas'], ';');
        
        $stmt = $conn->prepare("
            SELECT 
                DATE(mp.production_date) as date,
                COUNT(DISTINCT mp.animal_id) as animals_count,
                SUM(mp.volume) as total_volume,
                AVG(mp.volume) as avg_volume,
                AVG(mp.fat_content) as avg_fat,
                AVG(mp.protein_content) as avg_protein,
                AVG(mp.somatic_cells) as avg_somatic_cells
            FROM milk_production mp
            WHERE mp.farm_id = ? 
            AND DATE(mp.production_date) BETWEEN ? AND ?
            GROUP BY DATE(mp.production_date)
            ORDER BY date DESC
        ");
        $stmt->execute([$farm_id, $date_from, $date_to]);
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [
                date('d/m/Y', strtotime($row['date'])),
                $row['animals_count'],
                number_format($row['total_volume'], 2, ',', '.'),
                number_format($row['avg_volume'], 2, ',', '.'),
                number_format($row['avg_fat'], 2, ',', '.'),
                number_format($row['avg_protein'], 2, ',', '.'),
                number_format($row['avg_somatic_cells'], 0, ',', '.')
            ], ';');
        }
        break;
        
    case 'animals':
        fputcsv($output, ['Número', 'Nome', 'Raça', 'Status', 'Status Saúde', 'Status Reprodutivo', 'Data Nascimento', 'Grupo'], ';');
        
        $where = ["a.farm_id = ?"];
        $params = [$farm_id];
        
        if (!empty($filters['status'])) {
            $where[] = "a.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['breed'])) {
            $where[] = "a.breed = ?";
            $params[] = $filters['breed'];
        }
        
        $whereClause = implode(' AND ', $where);
        
        $stmt = $conn->prepare("
            SELECT 
                a.animal_number,
                a.name,
                a.breed,
                a.status,
                a.health_status,
                a.reproductive_status,
                a.birth_date,
                ag.group_name
            FROM animals a
            LEFT JOIN animal_groups ag ON a.current_group_id = ag.id
            WHERE $whereClause
            ORDER BY a.animal_number ASC
        ");
        $stmt->execute($params);
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [
                $row['animal_number'],
                $row['name'] ?? '',
                $row['breed'] ?? '',
                $row['status'] ?? '',
                $row['health_status'] ?? '',
                $row['reproductive_status'] ?? '',
                $row['birth_date'] ? date('d/m/Y', strtotime($row['birth_date'])) : '',
                $row['group_name'] ?? 'Sem grupo'
            ], ';');
        }
        break;
        
    case 'health':
        fputcsv($output, ['Data', 'Animal', 'Tipo', 'Descrição', 'Medicamento', 'Dosagem', 'Custo (R$)', 'Próxima Data', 'Veterinário'], ';');
        
        $stmt = $conn->prepare("
            SELECT 
                hr.record_date,
                CONCAT(a.animal_number, ' - ', COALESCE(a.name, '')) as animal,
                hr.record_type,
                hr.description,
                hr.medication,
                hr.dosage,
                hr.cost,
                hr.next_date,
                hr.veterinarian
            FROM health_records hr
            LEFT JOIN animals a ON hr.animal_id = a.id
            WHERE hr.farm_id = ? 
            AND DATE(hr.record_date) >= ? 
            AND DATE(hr.record_date) <= ?
            ORDER BY hr.record_date DESC
        ");
        $stmt->execute([$farm_id, $date_from, $date_to]);
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [
                date('d/m/Y', strtotime($row['record_date'])),
                $row['animal'],
                $row['record_type'],
                $row['description'],
                $row['medication'] ?? '',
                $row['dosage'] ?? '',
                $row['cost'] ? number_format($row['cost'], 2, ',', '.') : '',
                $row['next_date'] ? date('d/m/Y', strtotime($row['next_date'])) : '',
                $row['veterinarian'] ?? ''
            ], ';');
        }
        break;
        
    case 'reproduction':
        fputcsv($output, ['Tipo', 'Data', 'Animal', 'Touro', 'Resultado', 'Observações'], ';');
        
        // Inseminações
        $stmt = $conn->prepare("
            SELECT 
                'Inseminação' as tipo,
                i.insemination_date as data,
                CONCAT(a.animal_number, ' - ', COALESCE(a.name, '')) as animal,
                COALESCE(b.name, '') as touro,
                COALESCE(i.pregnancy_result, 'pendente') as resultado,
                i.notes as observacoes
            FROM inseminations i
            LEFT JOIN animals a ON i.animal_id = a.id
            LEFT JOIN bulls b ON i.bull_id = b.id
            WHERE i.farm_id = ? 
            AND DATE(i.insemination_date) >= ? 
            AND DATE(i.insemination_date) <= ?
        ");
        $stmt->execute([$farm_id, $date_from, $date_to]);
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [
                $row['tipo'],
                date('d/m/Y', strtotime($row['data'])),
                $row['animal'],
                $row['touro'],
                $row['resultado'] ?? '',
                $row['observacoes'] ?? ''
            ], ';');
        }
        
        // Partos
        $stmt = $conn->prepare("
            SELECT 
                'Parto' as tipo,
                b.birth_date as data,
                CONCAT(a.animal_number, ' - ', COALESCE(a.name, '')) as animal,
                '' as touro,
                CONCAT('Sexo: ', b.calf_gender, ', Peso: ', b.calf_weight, 'kg') as resultado,
                b.notes as observacoes
            FROM births b
            LEFT JOIN animals a ON b.animal_id = a.id
            WHERE b.farm_id = ? 
            AND DATE(b.birth_date) >= ? 
            AND DATE(b.birth_date) <= ?
        ");
        $stmt->execute([$farm_id, $date_from, $date_to]);
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [
                $row['tipo'],
                date('d/m/Y', strtotime($row['data'])),
                $row['animal'],
                $row['touro'],
                $row['resultado'] ?? '',
                $row['observacoes'] ?? ''
            ], ';');
        }
        break;
        
    case 'feeding':
        fputcsv($output, ['Data', 'Animal', 'Turno', 'Concentrado (kg)', 'Volumoso (kg)', 'Silagem (kg)', 'Feno (kg)', 'Custo Total (R$)'], ';');
        
        $stmt = $conn->prepare("
            SELECT 
                fr.feed_date,
                CONCAT(a.animal_number, ' - ', COALESCE(a.name, '')) as animal,
                fr.shift,
                fr.concentrate_kg,
                fr.roughage_kg,
                fr.silage_kg,
                fr.hay_kg,
                fr.total_cost
            FROM feed_records fr
            LEFT JOIN animals a ON fr.animal_id = a.id
            WHERE fr.farm_id = ? 
            AND DATE(fr.feed_date) >= ? 
            AND DATE(fr.feed_date) <= ?
            ORDER BY fr.feed_date DESC
        ");
        $stmt->execute([$farm_id, $date_from, $date_to]);
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [
                date('d/m/Y', strtotime($row['feed_date'])),
                $row['animal'],
                $row['shift'],
                number_format($row['concentrate_kg'], 2, ',', '.'),
                number_format($row['roughage_kg'], 2, ',', '.'),
                number_format($row['silage_kg'], 2, ',', '.'),
                number_format($row['hay_kg'], 2, ',', '.'),
                number_format($row['total_cost'], 2, ',', '.')
            ], ';');
        }
        break;
        
    case 'summary':
        // Cabeçalho do resumo
        fputcsv($output, ['Categoria', 'Indicador', 'Valor'], ';');
        fputcsv($output, [], ';'); // Linha em branco
        
        // Produção
        $stmt = $conn->prepare("
            SELECT 
                SUM(volume) as total_volume,
                AVG(volume) as avg_volume,
                COUNT(DISTINCT animal_id) as animals_count
            FROM milk_production
            WHERE farm_id = ? 
            AND production_date BETWEEN ? AND ?
        ");
        $stmt->execute([$farm_id, $date_from, $date_to]);
        $production = $stmt->fetch(PDO::FETCH_ASSOC);
        
        fputcsv($output, ['Produção de Leite', 'Volume Total (L)', number_format($production['total_volume'] ?? 0, 2, ',', '.')], ';');
        fputcsv($output, ['Produção de Leite', 'Média por Animal (L)', number_format($production['avg_volume'] ?? 0, 2, ',', '.')], ';');
        fputcsv($output, ['Produção de Leite', 'Animais em Produção', $production['animals_count'] ?? 0], ';');
        fputcsv($output, [], ';'); // Linha em branco
        
        // Animais
        $stmt = $conn->prepare("
            SELECT 
                COUNT(*) as total,
                COUNT(CASE WHEN status = 'Lactante' THEN 1 END) as lactating,
                COUNT(CASE WHEN status = 'Seca' THEN 1 END) as dry,
                COUNT(CASE WHEN health_status = 'doente' THEN 1 END) as sick
            FROM animals
            WHERE farm_id = ? AND (is_active = 1 OR is_active IS NULL)
        ");
        $stmt->execute([$farm_id]);
        $animals = $stmt->fetch(PDO::FETCH_ASSOC);
        
        fputcsv($output, ['Rebanho', 'Total de Animais', $animals['total'] ?? 0], ';');
        fputcsv($output, ['Rebanho', 'Lactantes', $animals['lactating'] ?? 0], ';');
        fputcsv($output, ['Rebanho', 'Secas', $animals['dry'] ?? 0], ';');
        fputcsv($output, ['Rebanho', 'Doentes', $animals['sick'] ?? 0], ';');
        fputcsv($output, [], ';'); // Linha em branco
        
        // Saúde
        $stmt = $conn->prepare("
            SELECT 
                COUNT(*) as total_records,
                SUM(cost) as total_cost
            FROM health_records
            WHERE farm_id = ? 
            AND DATE(record_date) >= ? 
            AND DATE(record_date) <= ?
        ");
        $stmt->execute([$farm_id, $date_from, $date_to]);
        $health = $stmt->fetch(PDO::FETCH_ASSOC);
        
        fputcsv($output, ['Saúde', 'Total de Registros', $health['total_records'] ?? 0], ';');
        fputcsv($output, ['Saúde', 'Custo Total (R$)', number_format($health['total_cost'] ?? 0, 2, ',', '.')], ';');
        fputcsv($output, [], ';'); // Linha em branco
        
        // Reprodutivo
        $stmt = $conn->prepare("
            SELECT 
                COUNT(*) as total_inseminations,
                COUNT(CASE WHEN pregnancy_result = 'prenha' THEN 1 END) as positive_pregnancies
            FROM inseminations
            WHERE farm_id = ? 
            AND DATE(insemination_date) >= ? 
            AND DATE(insemination_date) <= ?
        ");
        $stmt->execute([$farm_id, $date_from, $date_to]);
        $reproduction = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stmt = $conn->prepare("
            SELECT COUNT(id) as total_births 
            FROM births 
            WHERE farm_id = ? 
            AND DATE(birth_date) >= ? 
            AND DATE(birth_date) <= ?
        ");
        $stmt->execute([$farm_id, $date_from, $date_to]);
        $birth_summary = $stmt->fetch(PDO::FETCH_ASSOC);
        
        fputcsv($output, ['Reprodutivo', 'Total de Inseminações', $reproduction['total_inseminations'] ?? 0], ';');
        fputcsv($output, ['Reprodutivo', 'Prenhezes Positivas', $reproduction['positive_pregnancies'] ?? 0], ';');
        fputcsv($output, ['Reprodutivo', 'Total de Partos', $birth_summary['total_births'] ?? 0], ';');
        fputcsv($output, [], ';'); // Linha em branco
        
        // Alimentação
        $stmt = $conn->prepare("
            SELECT 
                SUM(total_cost) as total_cost,
                COUNT(DISTINCT animal_id) as animals_fed
            FROM feed_records
            WHERE farm_id = ? 
            AND DATE(feed_date) >= ? 
            AND DATE(feed_date) <= ?
        ");
        $stmt->execute([$farm_id, $date_from, $date_to]);
        $feeding = $stmt->fetch(PDO::FETCH_ASSOC);
        
        fputcsv($output, ['Alimentação', 'Custo Total (R$)', number_format($feeding['total_cost'] ?? 0, 2, ',', '.')], ';');
        fputcsv($output, ['Alimentação', 'Animais Alimentados', $feeding['animals_fed'] ?? 0], ';');
        break;
}

fclose($output);
exit;

