<?php
/**
 * Error Handler - Lactech
 * Tratamento centralizado de erros da API
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

// Função para retornar erro JSON
function returnJsonError($message, $code = 400) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'error' => $message,
        'code' => $code,
        'timestamp' => date('c')
    ]);
    exit;
}

// Função para retornar sucesso JSON
function returnJsonSuccess($data = null, $message = 'Sucesso') {
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('c')
    ]);
    exit;
}

// Verificar se há erros PHP
if (error_get_last()) {
    $error = error_get_last();
    returnJsonError('Erro interno do servidor: ' . $error['message'], 500);
}

// Verificar se a requisição é válida
if (!isset($_GET['endpoint'])) {
    returnJsonError('Endpoint não especificado', 400);
}

$endpoint = $_GET['endpoint'];

// Roteamento de endpoints
switch ($endpoint) {
    case 'notifications':
        handleNotifications();
        break;
        
    case 'urgent_actions':
        handleUrgentActions();
        break;
        
    case 'volume':
        handleVolume();
        break;
        
    case 'quality':
        handleQuality();
        break;
        
    case 'activities':
        handleActivities();
        break;
        
    default:
        returnJsonError('Endpoint não encontrado: ' . $endpoint, 404);
}

/**
 * Tratar notificações
 */
function handleNotifications() {
    $limit = $_GET['limit'] ?? 50;
    
    // Simular dados de notificação
    $notifications = [
        [
            'id' => 1,
            'title' => 'Sistema Inicializado',
            'message' => 'Sistema carregado com sucesso',
            'type' => 'info',
            'priority' => 'normal',
            'is_read' => false,
            'created_at' => date('c')
        ],
        [
            'id' => 2,
            'title' => 'Limpeza Concluída',
            'message' => 'Sistema otimizado e limpo',
            'type' => 'success',
            'priority' => 'normal',
            'is_read' => false,
            'created_at' => date('c')
        ]
    ];
    
    returnJsonSuccess([
        'notifications' => $notifications,
        'unread_count' => count($notifications)
    ], 'Notificações carregadas com sucesso');
}

/**
 * Tratar ações urgentes
 */
function handleUrgentActions() {
    $actions = [
        [
            'type' => 'system_cleanup',
            'message' => 'Sistema otimizado e limpo',
            'priority' => 'low'
        ]
    ];
    
    returnJsonSuccess($actions, 'Ações urgentes verificadas');
}

/**
 * Tratar volume
 */
function handleVolume() {
    $volumeData = [
        'volume_today' => 0,
        'volume_month' => 0,
        'records' => []
    ];
    
    returnJsonSuccess($volumeData, 'Dados de volume carregados');
}

/**
 * Tratar qualidade
 */
function handleQuality() {
    $qualityData = [
        'tests' => [],
        'averages' => [
            'fat' => 0,
            'protein' => 0
        ]
    ];
    
    returnJsonSuccess($qualityData, 'Dados de qualidade carregados');
}

/**
 * Tratar atividades
 */
function handleActivities() {
    $activities = [
        [
            'id' => 1,
            'type' => 'system',
            'message' => 'Sistema inicializado',
            'timestamp' => date('c')
        ]
    ];
    
    returnJsonSuccess($activities, 'Atividades carregadas');
}
?>

