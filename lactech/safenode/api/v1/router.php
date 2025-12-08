<?php
/**
 * SafeNode - API Router
 * Roteador para API RESTful v1
 */

session_start();

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/init.php';

$db = getSafeNodeDatabase();

if (!$db) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
    exit;
}

// Obter método e path
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = str_replace('/safenode/api/v1', '', $path);
$path = trim($path, '/');
$segments = explode('/', $path);

// Roteamento
$resource = $segments[0] ?? '';

switch ($resource) {
    case 'logs':
        require_once __DIR__ . '/LogsController.php';
        $controller = new LogsController($db);
        
        if ($method === 'GET') {
            if (isset($segments[1])) {
                $controller->show((int)$segments[1]);
            } else {
                $controller->index();
            }
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
        }
        break;
        
    case 'ips':
        require_once __DIR__ . '/IPsController.php';
        $controller = new IPsController($db);
        
        if ($method === 'GET') {
            $controller->index();
        } elseif ($method === 'POST') {
            $controller->block();
        } elseif ($method === 'DELETE' && isset($segments[1])) {
            $controller->unblock($segments[1]);
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
        }
        break;
        
    case 'stats':
        require_once __DIR__ . '/StatsController.php';
        $controller = new StatsController($db);
        
        if ($method === 'GET') {
            $controller->index();
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
        }
        break;
        
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Resource not found']);
}



