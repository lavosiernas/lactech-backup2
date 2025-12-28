<?php
/**
 * Página: Relatórios
 * Subpágina do Mais Opções
 */

require_once __DIR__ . '/../includes/config_login.php';
require_once __DIR__ . '/../includes/Database.class.php';

if (!isLoggedIn() || ($_SESSION['user_role'] !== 'gerente' && $_SESSION['user_role'] !== 'manager')) {
    http_response_code(403);
    die('Acesso negado');
}

// Buscar dados
try {
    $db = Database::getInstance();
    $production_result = $db->query("
        SELECT SUM(volume) as total_volume
        FROM milk_production 
        WHERE production_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    ");
    $total_production = $production_result[0]['total_volume'] ?? 0;
    $avg_daily_production = $total_production / 7;
    
    $animals = $db->getAllAnimals();
    $lactating_cows = count(array_filter($animals, function($a) { 
        return ($a['status'] ?? '') === 'Lactante'; 
    }));
    
    $milk_data = $db->query("
        SELECT 
            DATE(production_date) as date,
            SUM(volume) as daily_volume,
            AVG(fat_content) as avg_fat,
            AVG(protein_content) as avg_protein,
            AVG(somatic_cells) as avg_somatic_cells
        FROM milk_production 
        WHERE production_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY DATE(production_date)
        ORDER BY date DESC
        LIMIT 7
    ");
} catch (Exception $e) {
    $total_production = 0;
    $avg_daily_production = 0;
    $lactating_cows = 0;
    $milk_data = [];
}

$v = time();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios - LacTech</title>
    <?php if (file_exists(__DIR__ . '/../assets/css/tailwind.min.css')): ?>
        <link rel="stylesheet" href="../assets/css/tailwind.min.css">
    <?php else: ?>
        <script src="https://cdn.tailwindcss.com"></script>
    <?php endif; ?>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen bg-white">
        <!-- Header -->
        <div class="flex items-center justify-between p-6 bg-white sticky top-0 z-10 shadow-sm border-b border-gray-200">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center shadow-lg">
                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Relatórios</h2>
                    <p class="text-sm text-gray-600">Análises e dados do sistema</p>
                </div>
            </div>
            <button onclick="window.parent.postMessage({type: 'closeModal'}, '*')" class="w-10 h-10 flex items-center justify-center hover:bg-gray-100 rounded-xl transition-colors">
                <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <!-- Content -->
        <div class="p-6">
            <!-- Mensagem de desenvolvimento -->
            <div class="flex flex-col items-center justify-center py-16 px-6 text-center text-gray-600">
                <div class="w-16 h-16 bg-gray-100 rounded-2xl flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5V7.5m7.5-3v3M4.5 18.75V9a4.5 4.5 0 014.5-4.5h6a4.5 4.5 0 014.5 4.5v9.75M4.5 18.75h15" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-800 mb-2">Estamos em desenvolvimento</h3>
                <p class="text-sm text-gray-500 max-w-md">Função disponível em breve. Nossa equipe está finalizando os relatórios para entregar insights completos e confiáveis.</p>
            </div>
        </div>
    </div>
</body>
</html>

