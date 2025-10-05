<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/PDFGenerator.php';

$auth = new Auth();

// Verificar autenticação
$auth->requireLogin();
$auth->require2FA();

$user = $auth->getCurrentUser();

// Verificar permissões (gerente, funcionário ou veterinário podem gerar relatórios)
$allowedRoles = ['gerente', 'funcionario', 'veterinario'];
if (!in_array($user['role'], $allowedRoles)) {
    setNotification('Você não tem permissão para gerar relatórios', 'error');
    redirect(DASHBOARD_URL);
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
    
    // Buscar dados do relatório
    $stmt = $pdo->prepare("
        SELECT 
            p.production_date,
            p.created_at,
            p.volume_liters,
            p.shift,
            p.observations,
            a.animal_id,
            a.name as animal_name
        FROM production_records p
        LEFT JOIN animals a ON p.animal_id = a.id
        WHERE p.farm_id = ? 
        AND p.production_date BETWEEN ? AND ?
        ORDER BY p.production_date DESC, p.created_at DESC
    ");
    
    $stmt->execute([$user['farm_id'], $startDate, $endDate]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($data)) {
        throw new Exception('Nenhum registro encontrado para o período selecionado');
    }
    
    // Gerar PDF
    $pdfGenerator = new PDFGenerator();
    $pdfGenerator->generateVolumeReport($data, $isPreview);
    
} catch (Exception $e) {
    setNotification('Erro ao gerar relatório: ' . $e->getMessage(), 'error');
    redirect(DASHBOARD_URL);
}

function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}
?>



