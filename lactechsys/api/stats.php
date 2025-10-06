<?php
// =====================================================
// API DE ESTATÍSTICAS - MYSQL
// =====================================================
// Estatísticas da fazenda Lagoa do Mato
// =====================================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Permitir requisições OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../includes/config_mysql.php';
require_once '../includes/database.php';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Obter estatísticas da fazenda
        $stats = getFarmStats();
        
        echo json_encode([
            'success' => true,
            'data' => $stats
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Método não permitido'
        ]);
    }
} catch (Exception $e) {
    error_log('Erro na API de estatísticas: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor'
    ]);
}
?>
