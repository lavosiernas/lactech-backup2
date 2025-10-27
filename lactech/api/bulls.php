<?php
/**
 * API de Touros - Lactech
 * Sistema de touros e inseminação básico
 */

// Configurações de segurança
error_reporting(0);
ini_set('display_errors', 0);

// Headers para JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Tratar requisições OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar autenticação
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Acesso negado']);
    exit;
}

try {
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    switch ($action) {
        case 'list':
            $limit = $_GET['limit'] ?? 10;
            echo json_encode([
                'success' => true,
                'data' => [
                    [
                        'id' => 1,
                        'bull_number' => 'B001',
                        'name' => 'Touro Elite',
                        'breed' => 'Holandês',
                        'birth_date' => '2018-12-01',
                        'source' => 'proprio',
                        'genetic_value' => 'Alto valor genético',
                        'is_active' => true,
                        'total_inseminations' => 25,
                        'pregnancy_rate' => 72.0
                    ],
                    [
                        'id' => 2,
                        'bull_number' => 'B002',
                        'name' => 'Inseminação Premium',
                        'breed' => 'Gir',
                        'birth_date' => '2017-06-15',
                        'source' => 'inseminacao',
                        'genetic_value' => 'Sêmen importado',
                        'is_active' => true,
                        'total_inseminations' => 20,
                        'pregnancy_rate' => 75.0
                    ]
                ]
            ]);
            break;
            
        case 'get':
            $id = $_GET['id'] ?? '';
            echo json_encode([
                'success' => true,
                'data' => [
                    'id' => $id,
                    'bull_number' => 'B00' . $id,
                    'name' => 'Touro ' . $id,
                    'breed' => 'Holandês',
                    'birth_date' => '2018-12-01',
                    'source' => 'proprio',
                    'genetic_value' => 'Alto valor genético',
                    'is_active' => true
                ]
            ]);
            break;
            
        case 'create':
            echo json_encode([
                'success' => true,
                'data' => [
                    'bull_id' => rand(1000, 9999),
                    'message' => 'Touro criado com sucesso'
                ]
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Ação não encontrada']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Erro interno: ' . $e->getMessage()]);
}
?>
