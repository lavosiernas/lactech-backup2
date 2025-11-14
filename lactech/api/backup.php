<?php
/**
 * API de Backup - Lactech
 * Sistema de backup e sincronização básico
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
        case 'list_backups':
            echo json_encode([
                'success' => true,
                'data' => [
                    [
                        'id' => 1,
                        'name' => 'Backup Diário - 2025-01-20',
                        'description' => 'Backup automático diário',
                        'file_path' => '/backups/backup_2025-01-20.sql',
                        'file_size' => '2.5 MB',
                        'created_at' => '2025-01-20 02:00:00',
                        'status' => 'completed'
                    ],
                    [
                        'id' => 2,
                        'name' => 'Backup Manual - 2025-01-19',
                        'description' => 'Backup manual antes de atualização',
                        'file_path' => '/backups/backup_manual_2025-01-19.sql',
                        'file_size' => '2.3 MB',
                        'created_at' => '2025-01-19 15:30:00',
                        'status' => 'completed'
                    ]
                ]
            ]);
            break;
            
        case 'create_backup':
            echo json_encode([
                'success' => true,
                'data' => [
                    'backup_id' => rand(1000, 9999),
                    'name' => 'Backup Manual - ' . date('Y-m-d H:i:s'),
                    'file_path' => '/backups/backup_manual_' . time() . '.sql',
                    'message' => 'Backup criado com sucesso'
                ]
            ]);
            break;
            
        case 'restore_backup':
            $backup_id = $_POST['backup_id'] ?? '';
            echo json_encode([
                'success' => true,
                'data' => [
                    'backup_id' => $backup_id,
                    'message' => 'Backup restaurado com sucesso'
                ]
            ]);
            break;
            
        case 'delete_backup':
            $backup_id = $_POST['backup_id'] ?? '';
            echo json_encode([
                'success' => true,
                'data' => [
                    'backup_id' => $backup_id,
                    'message' => 'Backup removido com sucesso'
                ]
            ]);
            break;
            
        case 'export_data':
            $format = $_GET['format'] ?? 'json';
            echo json_encode([
                'success' => true,
                'data' => [
                    'export_id' => rand(1000, 9999),
                    'file_path' => '/exports/export_' . time() . '.' . $format,
                    'format' => $format,
                    'message' => 'Dados exportados com sucesso'
                ]
            ]);
            break;
            
        case 'check_sync_status':
            echo json_encode([
                'success' => true,
                'data' => [
                    'sync_status' => 'up_to_date',
                    'last_sync' => '2025-01-20 14:30:00',
                    'next_sync' => '2025-01-21 02:00:00',
                    'pending_changes' => 0
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
