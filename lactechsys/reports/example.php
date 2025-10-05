<?php
/**
 * Exemplo de uso dos relatórios em PDF
 * Este arquivo demonstra como usar a classe PDFGenerator
 */

require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/PDFGenerator.php';

$auth = new Auth();

// Verificar autenticação
$auth->requireLogin();
$auth->require2FA();

$user = $auth->getCurrentUser();

// Verificar permissões
$allowedRoles = ['gerente', 'funcionario', 'veterinario', 'proprietario'];
if (!in_array($user['role'], $allowedRoles)) {
    die('Você não tem permissão para acessar relatórios');
}

try {
    // Dados de exemplo para o relatório de volume
    $volumeData = [
        [
            'production_date' => '2024-01-15',
            'created_at' => '2024-01-15 08:30:00',
            'volume_liters' => 25.5,
            'shift' => 'Manhã',
            'observations' => 'Produção normal'
        ],
        [
            'production_date' => '2024-01-15',
            'created_at' => '2024-01-15 16:30:00',
            'volume_liters' => 23.8,
            'shift' => 'Tarde',
            'observations' => 'Leve redução'
        ],
        [
            'production_date' => '2024-01-16',
            'created_at' => '2024-01-16 08:30:00',
            'volume_liters' => 26.2,
            'shift' => 'Manhã',
            'observations' => 'Boa produção'
        ]
    ];
    
    // Dados de exemplo para o relatório de qualidade
    $qualityData = [
        [
            'test_date' => '2024-01-15',
            'fat_percentage' => 3.8,
            'protein_percentage' => 3.2,
            'somatic_cell_count' => 250000,
            'total_bacterial_count' => 45000
        ],
        [
            'test_date' => '2024-01-16',
            'fat_percentage' => 3.9,
            'protein_percentage' => 3.3,
            'somatic_cell_count' => 230000,
            'total_bacterial_count' => 42000
        ]
    ];
    
    // Dados de exemplo para o relatório de pagamentos
    $paymentsData = [
        [
            'record_date' => '2024-01-15',
            'created_at' => '2024-01-15 10:00:00',
            'amount' => 1250.00,
            'description' => 'Venda de leite - Lote 001',
            'category' => 'venda_leite'
        ],
        [
            'record_date' => '2024-01-16',
            'created_at' => '2024-01-16 10:00:00',
            'amount' => 1180.00,
            'description' => 'Venda de leite - Lote 002',
            'category' => 'venda_leite'
        ]
    ];
    
    // Determinar qual relatório gerar baseado no parâmetro
    $reportType = $_GET['type'] ?? 'volume';
    
    $pdfGenerator = new PDFGenerator();
    
    switch ($reportType) {
        case 'volume':
            $pdfGenerator->generateVolumeReport($volumeData, false);
            break;
            
        case 'quality':
            $pdfGenerator->generateQualityReport($qualityData, false);
            break;
            
        case 'payments':
            if (in_array($user['role'], ['gerente', 'proprietario'])) {
                $pdfGenerator->generatePaymentsReport($paymentsData, false);
            } else {
                die('Você não tem permissão para gerar relatórios financeiros');
            }
            break;
            
        default:
            die('Tipo de relatório inválido');
    }
    
} catch (Exception $e) {
    die('Erro ao gerar relatório: ' . $e->getMessage());
}
?>
