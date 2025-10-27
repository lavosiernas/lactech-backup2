<?php
/**
 * API de Grupos de Animais - Lactech
 * Sistema de grupos/lotes básico
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
            // Lista de grupos
            echo json_encode([
                'success' => true,
                'data' => [
                    [
                        'id' => 1,
                        'group_name' => 'Lactantes Alta Produção',
                        'group_code' => 'LAC-A',
                        'group_type' => 'lactante',
                        'current_count' => 5,
                        'capacity' => 20,
                        'color_code' => '#10B981'
                    ],
                    [
                        'id' => 2,
                        'group_name' => 'Vacas Secas',
                        'group_code' => 'SECO',
                        'group_type' => 'seco',
                        'current_count' => 3,
                        'capacity' => 15,
                        'color_code' => '#F59E0B'
                    ],
                    [
                        'id' => 3,
                        'group_name' => 'Novilhas',
                        'group_code' => 'NOV',
                        'group_type' => 'novilha',
                        'current_count' => 4,
                        'capacity' => 10,
                        'color_code' => '#3B82F6'
                    ]
                ]
            ]);
            break;
            
        case 'by_id':
            $id = $_GET['id'] ?? '';
            echo json_encode([
                'success' => true,
                'data' => [
                    'id' => $id,
                    'group_name' => 'Grupo encontrado',
                    'group_code' => 'GRP-' . $id,
                    'group_type' => 'lactante',
                    'current_count' => 5,
                    'capacity' => 20
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
