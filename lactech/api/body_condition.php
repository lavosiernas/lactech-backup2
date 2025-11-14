<?php
/**
 * API de Body Condition Score - Lactech
 * Sistema BCS básico
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
        case 'stats':
            // Estatísticas de BCS
            echo json_encode([
                'success' => true,
                'data' => [
                    'total_evaluations' => 15,
                    'avg_score' => 3.2,
                    'low_bcs_count' => 2,
                    'optimal_bcs_count' => 10,
                    'high_bcs_count' => 3
                ]
            ]);
            break;
            
        case 'latest':
            // Últimas avaliações
            echo json_encode([
                'success' => true,
                'data' => [
                    [
                        'id' => 1,
                        'animal_id' => 1,
                        'animal_name' => 'Bella',
                        'score' => 3.5,
                        'evaluation_date' => '2025-01-20',
                        'evaluated_by' => 'Dr. João'
                    ],
                    [
                        'id' => 2,
                        'animal_id' => 2,
                        'animal_name' => 'Luna',
                        'score' => 2.8,
                        'evaluation_date' => '2025-01-19',
                        'evaluated_by' => 'Dr. João'
                    ]
                ]
            ]);
            break;
            
        case 'by_animal':
            $animal_id = $_GET['animal_id'] ?? '';
            echo json_encode([
                'success' => true,
                'data' => [
                    [
                        'id' => 1,
                        'animal_id' => $animal_id,
                        'score' => 3.2,
                        'evaluation_date' => '2025-01-20',
                        'evaluated_by' => 'Dr. João'
                    ]
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
