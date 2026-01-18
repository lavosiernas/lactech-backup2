<?php
/**
 * SafeCode IDE - Test Connection
 * Testa se a API está funcionando
 * URL: http://localhost/safecode/api/test_connection.php
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$result = [
    'success' => true,
    'message' => 'API está funcionando!',
    'timestamp' => date('Y-m-d H:i:s'),
    'server' => [
        'php_version' => PHP_VERSION,
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'request_method' => $_SERVER['REQUEST_METHOD'],
        'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
    ],
    'config' => [
        'api_base' => '/safecode/api',
        'files' => [
            'config_exists' => file_exists(__DIR__ . '/config.php'),
            'auth_exists' => file_exists(__DIR__ . '/auth.php'),
        ]
    ]
];

// Testar conexão com banco de dados
if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
    
    try {
        $db = getDatabase();
        if ($db) {
            $result['database'] = [
                'connected' => true,
                'database' => DB_NAME,
            ];
        } else {
            $result['database'] = [
                'connected' => false,
                'error' => 'getDatabase() retornou null'
            ];
        }
    } catch (Exception $e) {
        $result['database'] = [
            'connected' => false,
            'error' => $e->getMessage()
        ];
    }
} else {
    $result['database'] = [
        'connected' => false,
        'error' => 'config.php não encontrado'
    ];
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

