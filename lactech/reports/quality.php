<?php
// Sistema MySQL - Lagoa Do Mato
session_start();

// Verificar autenticação básica
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header('Location: ../inicio-login.php');
    exit;
}

$user = [
    'id' => $_SESSION['user_id'],
    'role' => $_SESSION['user_role'],
    'farm_id' => 1 // Lagoa Do Mato
];

// Verificar permissões
$allowedRoles = ['gerente', 'funcionario'];
if (!in_array($user['role'], $allowedRoles)) {
    header('Location: ../index.php');
    exit;
}

try {
    // Obter parâmetros
    $startDate = sanitizeInput($_GET['start_date'] ?? date('Y-m-01'));
    $endDate = sanitizeInput($_GET['end_date'] ?? date('Y-m-d'));
    $isPreview = isset($_GET['preview']) && $_GET['preview'] === '1';
    
    // Validar datas
    if (!validateDate($startDate) || !validateDate($endDate)) {
        throw new Exception('Datas inválidas');
    }
    
    if ($startDate > $endDate) {
        throw new Exception('Data inicial não pode ser maior que a data final');
    }
    
    // Buscar dados do relatório de qualidade
    $stmt = $pdo->prepare("
        SELECT 
            q.test_date,
            q.fat_percentage,
            q.protein_percentage,
            q.somatic_cell_count,
            q.total_bacterial_count,
            q.temperature,
            q.ph_level,
            q.created_at,
            a.name as animal_name
        FROM quality_tests q
        LEFT JOIN animals a ON q.animal_id = a.id
        WHERE q.farm_id = ? 
        AND q.test_date BETWEEN ? AND ?
        ORDER BY q.test_date DESC, q.created_at DESC
    ");
    
    $stmt->execute([$user['farm_id'], $startDate, $endDate]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($data)) {
        throw new Exception('Nenhum teste de qualidade encontrado para o período selecionado');
    }
    
    // Gerar PDF
    $pdfGenerator = new PDFGenerator();
    $pdfGenerator->generateQualityReport($data, $isPreview);
    
} catch (Exception $e) {
    setNotification('Erro ao gerar relatório: ' . $e->getMessage(), 'error');
    redirect(DASHBOARD_URL);
}

function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}
?>













