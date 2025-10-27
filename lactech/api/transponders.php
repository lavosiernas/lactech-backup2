<?php
/**
 * API de Transponders - Lactech
 * Sistema RFID básico
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
            // Fallback para lista de transponders
            echo json_encode([
                'success' => true,
                'data' => [
                    [
                        'id' => 1,
                        'transponder_code' => 'RF001',
                        'animal_id' => 1,
                        'animal_name' => 'Bella',
                        'is_active' => true
                    ],
                    [
                        'id' => 2,
                        'transponder_code' => 'RF002',
                        'animal_id' => 2,
                        'animal_name' => 'Luna',
                        'is_active' => true
                    ]
                ]
            ]);
            break;
            
        case 'search':
            $code = $_GET['code'] ?? '';
            echo json_encode([
                'success' => true,
                'data' => [
                    'transponder_code' => $code,
                    'animal_id' => 1,
                    'animal_name' => 'Animal encontrado',
                    'is_active' => true
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
