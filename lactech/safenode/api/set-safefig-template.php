<?php
/**
 * API para salvar template HTML e dados do projeto na sessão para importação no safefig.php
 */

session_start();
header('Content-Type: application/json');

// Verificar se está logado
if (!isset($_SESSION['safenode_logged_in']) || $_SESSION['safenode_logged_in'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit;
}

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método não permitido']);
    exit;
}

// Obter HTML do template (pode estar vazio - será criado na IDE)
$htmlTemplate = $_POST['html_template'] ?? '';

// Sanitizar e salvar na sessão (mesmo se estiver vazio)
require_once __DIR__ . '/../includes/SecurityHelpers.php';
if (!empty($htmlTemplate)) {
    $htmlTemplate = XSSProtection::sanitize($htmlTemplate);
} else {
    // Template vazio é permitido - usuário criará na IDE
    $htmlTemplate = '';
}

// Salvar template na sessão
$_SESSION['safefig_import_template'] = $htmlTemplate;

// Obter e salvar dados do projeto (se fornecidos)
$projectId = $_POST['project_id'] ?? 'temp_' . time() . '_' . uniqid();
$editorType = $_POST['editor_type'] ?? 'ide';

$projectData = [
    'project_id' => $projectId,
    'project_name' => $_POST['project_name'] ?? '',
    'sender_email' => $_POST['sender_email'] ?? '',
    'sender_name' => $_POST['sender_name'] ?? '',
    'email_function' => $_POST['email_function'] ?? '',
    'editor_type' => $editorType,
    'created_at' => date('Y-m-d H:i:s')
];

// Sanitizar dados do projeto
foreach ($projectData as $key => $value) {
    if ($key !== 'created_at') {
        $projectData[$key] = XSSProtection::sanitize($value);
    }
}

// Salvar dados do projeto na sessão usando o project_id como chave
$_SESSION['safefig_project_data'] = $projectData;
$_SESSION['safefig_current_project_id'] = $projectId;

echo json_encode([
    'success' => true, 
    'message' => 'Template e dados do projeto salvos com sucesso',
    'project_id' => $projectId
]);




