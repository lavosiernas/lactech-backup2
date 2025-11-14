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
$allowedRoles = ['gerente', 'proprietario'];
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
    
    // Buscar dados do relatório financeiro
    $stmt = $pdo->prepare("
        SELECT 
            fr.record_date,
            fr.created_at,
            fr.amount,
            fr.description,
            fr.category,
            fr.type,
            fr.status,
            fr.payment_method,
            fr.reference_number
        FROM financial_records fr
        WHERE fr.farm_id = ? 
        AND fr.record_date BETWEEN ? AND ?
        AND fr.type = 'revenue'
        ORDER BY fr.record_date DESC, fr.created_at DESC
    ");
    
    $stmt->execute([$user['farm_id'], $startDate, $endDate]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($data)) {
        throw new Exception('Nenhum registro financeiro encontrado para o período selecionado');
    }
    
    // Gerar PDF
    $pdfGenerator = new PDFGenerator();
    $pdfGenerator->generatePaymentsReport($data, $isPreview);
    
} catch (Exception $e) {
    setNotification('Erro ao gerar relatório: ' . $e->getMessage(), 'error');
    redirect(DASHBOARD_URL);
}

function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}
?>













