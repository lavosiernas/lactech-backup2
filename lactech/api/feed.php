<?php
/**
 * API de Alimentação - Lactech
 * Sistema de controle de alimentação básico
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
        case 'daily_summary':
            $date = $_GET['date'] ?? date('Y-m-d');
            echo json_encode([
                'success' => true,
                'data' => [
                    'date' => $date,
                    'total_concentrate' => 125.5,
                    'total_roughage' => 89.2,
                    'total_silage' => 45.8,
                    'total_animals_fed' => 15,
                    'avg_concentrate_per_animal' => 8.4
                ]
            ]);
            break;
            
        case 'stats':
            $days = $_GET['days'] ?? 30;
            echo json_encode([
                'success' => true,
                'data' => [
                    'period_days' => $days,
                    'total_concentrate' => 3750.5,
                    'total_roughage' => 2676.0,
                    'avg_daily_concentrate' => 125.0,
                    'avg_daily_roughage' => 89.2,
                    'cost_per_kg_concentrate' => 1.80,
                    'cost_per_kg_roughage' => 0.50
                ]
            ]);
            break;
            
        case 'list':
            $date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-7 days'));
            echo json_encode([
                'success' => true,
                'data' => [
                    [
                        'id' => 1,
                        'animal_id' => 1,
                        'animal_name' => 'Bella',
                        'feed_date' => $date_from,
                        'concentrate_kg' => 8.5,
                        'roughage_kg' => 6.2,
                        'silage_kg' => 3.0
                    ],
                    [
                        'id' => 2,
                        'animal_id' => 2,
                        'animal_name' => 'Luna',
                        'feed_date' => $date_from,
                        'concentrate_kg' => 7.8,
                        'roughage_kg' => 5.5,
                        'silage_kg' => 2.8
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
