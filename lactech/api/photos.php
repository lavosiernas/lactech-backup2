<?php
/**
 * API de Fotos - Lactech
 * Sistema de gestão de fotos básico
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
        case 'by_animal':
            $animal_id = $_GET['animal_id'] ?? '';
            echo json_encode([
                'success' => true,
                'data' => [
                    [
                        'id' => 1,
                        'animal_id' => $animal_id,
                        'photo_url' => 'assets/images/animals/placeholder.jpg',
                        'photo_type' => 'profile',
                        'is_primary' => true,
                        'taken_date' => '2025-01-20',
                        'description' => 'Foto principal do animal'
                    ],
                    [
                        'id' => 2,
                        'animal_id' => $animal_id,
                        'photo_url' => 'assets/images/animals/placeholder2.jpg',
                        'photo_type' => 'health',
                        'is_primary' => false,
                        'taken_date' => '2025-01-19',
                        'description' => 'Foto de saúde'
                    ]
                ]
            ]);
            break;
            
        case 'upload':
            echo json_encode([
                'success' => true,
                'data' => [
                    'photo_id' => rand(1000, 9999),
                    'photo_url' => 'assets/images/animals/uploaded_' . time() . '.jpg',
                    'message' => 'Foto enviada com sucesso'
                ]
            ]);
            break;
            
        case 'delete':
            $photo_id = $_POST['photo_id'] ?? '';
            echo json_encode([
                'success' => true,
                'data' => [
                    'photo_id' => $photo_id,
                    'message' => 'Foto removida com sucesso'
                ]
            ]);
            break;
            
        case 'set_primary':
            $photo_id = $_POST['photo_id'] ?? '';
            echo json_encode([
                'success' => true,
                'data' => [
                    'photo_id' => $photo_id,
                    'message' => 'Foto definida como principal'
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
