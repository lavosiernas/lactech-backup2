<?php
/**
 * Página: Mais Opções
 * Subpágina do Dashboard Gerente - Menu principal de ferramentas
 */

// Incluir configuração e autenticação
require_once __DIR__ . '/../includes/config_login.php';
require_once __DIR__ . '/../includes/Database.class.php';

// Verificar autenticação
if (!isLoggedIn()) {
    http_response_code(401);
    die(json_encode(['error' => 'Não autenticado']));
}

// Verificar papel de gerente
if ($_SESSION['user_role'] !== 'gerente' && $_SESSION['user_role'] !== 'manager') {
    http_response_code(403);
    die(json_encode(['error' => 'Acesso negado']));
}

// Buscar dados para o modal Mais Opções
try {
    $db = Database::getInstance();
           
    // Buscar dados dos animais com cálculo de idade em meses
    $animals_raw = $db->getAllAnimals();
    $animals = array_map(function($animal) {
        $age_days = $animal['age_days'] ?? 0;
        $animal['age_months'] = floor($age_days / 30);
        return $animal;
    }, $animals_raw);

    // Buscar dados de produção de leite (últimos 30 dias, limitado a 7 registros)
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
    
    // Calcular estatísticas gerais
    $total_animals = count($animals);
    $lactating_cows = count(array_filter($animals, function($a) { 
        return ($a['status'] ?? '') === 'Lactante'; 
    }));
    $pregnant_cows = count(array_filter($animals, function($a) { 
        return ($a['reproductive_status'] ?? '') === 'prenha'; 
    }));

    // Calcular produção total dos últimos 7 dias
    $production_result = $db->query("
        SELECT SUM(volume) as total_volume
        FROM milk_production 
        WHERE production_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    ");
    $total_production = $production_result[0]['total_volume'] ?? 0;
    $avg_daily_production = $total_production / 7;
    
} catch (Exception $e) {
    error_log("Erro ao buscar dados para Mais Opções: " . $e->getMessage());
    $animals = [];
    $milk_data = [];
    $total_animals = 0;
    $lactating_cows = 0;
    $pregnant_cows = 0;
    $total_production = 0;
    $avg_daily_production = 0;
}

$v = time();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mais Opções - LacTech</title>
    
    <!-- Tailwind CSS -->
    <?php if (file_exists(__DIR__ . '/../assets/css/tailwind.min.css')): ?>
        <link rel="stylesheet" href="../assets/css/tailwind.min.css">
    <?php else: ?>
        <script src="https://cdn.tailwindcss.com"></script>
    <?php endif; ?>
    
    <style>
        .app-item {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .app-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="w-full h-full bg-white overflow-y-auto">
        <!-- Header -->
        <div class="flex items-center justify-between p-6 bg-white sticky top-0 z-10 shadow-sm border-b border-gray-200">
            <div class="flex items-center space-x-4">
                <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-xl flex items-center justify-center shadow-lg">
                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M19.5 6c-1.3 0-2.5.8-3 2-.5-1.2-1.7-2-3-2s-2.5.8-3 2c-.5-1.2-1.7-2-3-2C5.5 6 4 7.5 4 9.5c0 1.3.7 2.4 1.7 3.1-.4.6-.7 1.3-.7 2.1 0 2.2 1.8 4 4 4h6c2.2 0 4-1.8 4-4 0-.8-.3-1.5-.7-2.1 1-.7 1.7-1.8 1.7-3.1 0-2-1.5-3.5-3.5-3.5zM9 16c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm6 0c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Mais Opções</h2>
                    <p class="text-sm text-gray-600">Acesse ferramentas e recursos do sistema</p>
                </div>
            </div>
            <button onclick="window.parent.postMessage({type: 'closeModal'}, '*')" class="flex items-center space-x-2 px-6 py-3 bg-gradient-to-r from-gray-100 to-gray-200 hover:from-gray-200 hover:to-gray-300 rounded-xl transition-all duration-200 shadow-sm">
                <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                <span class="text-gray-700 font-semibold">Voltar</span>
            </button>
        </div>
        
        <!-- Content -->
        <div class="p-8">
            <div class="max-w-7xl mx-auto">
                <!-- Ferramentas Principais -->
                <div class="mb-10">
                    <h3 class="text-lg font-bold text-gray-800 mb-5 flex items-center">
                        <div class="w-8 h-8 bg-green-500 rounded-lg flex items-center justify-center mr-3">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </div>
                        Ferramentas Principais
                    </h3>
                    <div class="grid grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
                        <!-- Relatórios -->
                        <div class="app-item bg-white border border-gray-200 rounded-xl p-3 cursor-pointer shadow-sm" onclick="openSubPage('relatorios')">
                            <div class="flex flex-col items-center text-center space-y-2">
                                <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center">
                                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900 text-xs">Relatórios</p>
                                    <p class="text-[10px] text-gray-600 mt-0.5">Análises e dados</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Gestão de Rebanho -->
                        <div class="app-item bg-white border border-gray-200 rounded-xl p-3 cursor-pointer shadow-sm" onclick="openSubPage('gestao-rebanho')">
                            <div class="flex flex-col items-center text-center space-y-2">
                                <div class="w-12 h-12 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-xl flex items-center justify-center">
                                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M19.5 6c-1.3 0-2.5.8-3 2-.5-1.2-1.7-2-3-2s-2.5.8-3 2c-.5-1.2-1.7-2-3-2C5.5 6 4 7.5 4 9.5c0 1.3.7 2.4 1.7 3.1-.4.6-.7 1.3-.7 2.1 0 2.2 1.8 4 4 4h6c2.2 0 4-1.8 4-4 0-.8-.3-1.5-.7-2.1 1-.7 1.7-1.8 1.7-3.1 0-2-1.5-3.5-3.5-3.5zM9 16c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm6 0c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900 text-xs">Gestão de Rebanho</p>
                                    <p class="text-[10px] text-gray-600 mt-0.5">Animais e IA</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Gestão Sanitária -->
                        <div class="app-item bg-white border border-gray-200 rounded-xl p-3 cursor-pointer shadow-sm" onclick="openSubPage('gestao-sanitaria')">
                            <div class="flex flex-col items-center text-center space-y-2">
                                <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900 text-xs">Gestão Sanitária</p>
                                    <p class="text-[10px] text-gray-600 mt-0.5">Saúde e vacinas</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Reprodução -->
                        <div class="app-item bg-white border border-gray-200 rounded-xl p-3 cursor-pointer shadow-sm" onclick="openSubPage('reproducao')">
                            <div class="flex flex-col items-center text-center space-y-2">
                                <div class="w-12 h-12 bg-gradient-to-br from-pink-500 to-rose-600 rounded-xl flex items-center justify-center">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900 text-xs">Reprodução</p>
                                    <p class="text-[10px] text-gray-600 mt-0.5">Prenhez e DPP</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Dashboard Analítico -->
                        <div class="app-item bg-white border border-gray-200 rounded-xl p-3 cursor-pointer shadow-sm" onclick="openSubPage('dashboard-analitico')">
                            <div class="flex flex-col items-center text-center space-y-2">
                                <div class="w-12 h-12 bg-gradient-to-br from-slate-600 to-slate-700 rounded-xl flex items-center justify-center">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900 text-xs">Dashboard Analítico</p>
                                    <p class="text-[10px] text-gray-600 mt-0.5">Indicadores e KPIs</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Central de Ações -->
                        <div class="app-item bg-white border border-gray-200 rounded-xl p-3 cursor-pointer shadow-sm" onclick="openSubPage('central-acoes')">
                            <div class="flex flex-col items-center text-center space-y-2">
                                <div class="w-12 h-12 bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl flex items-center justify-center">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900 text-xs">Central de Ações</p>
                                    <p class="text-[10px] text-gray-600 mt-0.5">Tarefas prioritárias</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Grupos e Lotes -->
                        <div class="app-item bg-white border border-gray-200 rounded-xl p-3 cursor-pointer shadow-sm" onclick="openSubPage('grupos-lotes')">
                            <div class="flex flex-col items-center text-center space-y-2">
                                <div class="w-12 h-12 bg-gradient-to-br from-violet-500 to-violet-600 rounded-xl flex items-center justify-center">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900 text-xs">Grupos e Lotes</p>
                                    <p class="text-[10px] text-gray-600 mt-0.5">Organização</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Suporte -->
                        <div class="app-item bg-white border border-gray-200 rounded-xl p-3 cursor-pointer shadow-sm" onclick="openSubPage('suporte')">
                            <div class="flex flex-col items-center text-center space-y-2">
                                <div class="w-12 h-12 bg-gradient-to-br from-red-500 to-red-600 rounded-xl flex items-center justify-center">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900 text-xs">Suporte</p>
                                    <p class="text-[10px] text-gray-600 mt-0.5">Ajuda e contato</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- AgroNews360 -->
                        <a href="../agronews360/auto-login.php" target="_blank" class="app-item bg-white border border-gray-200 rounded-xl p-3 cursor-pointer shadow-sm hover:shadow-md transition-shadow block">
                            <div class="flex flex-col items-center text-center space-y-2">
                                <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900 text-xs">AgroNews360</p>
                                    <p class="text-[10px] text-gray-600 mt-0.5">Notícias do agronegócio</p>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
                
                <!-- Utilitários -->
                <div class="mb-10">
                    <h3 class="text-lg font-bold text-gray-800 mb-5 flex items-center">
                        <div class="w-8 h-8 bg-indigo-500 rounded-lg flex items-center justify-center mr-3">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"></path>
                            </svg>
                        </div>
                        Utilitários
                    </h3>
                    <div class="grid grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
                        <!-- Alimentação -->
                        <div class="app-item bg-white border border-gray-200 rounded-xl p-3 cursor-pointer shadow-sm" onclick="openSubPage('alimentacao')">
                            <div class="flex flex-col items-center text-center space-y-2">
                                <div class="w-12 h-12 bg-gradient-to-br from-lime-500 to-lime-600 rounded-xl flex items-center justify-center">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900 text-xs">Alimentação</p>
                                    <p class="text-[10px] text-gray-600 mt-0.5">Concentrado e ração</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Sistema de Touros -->
                        <div class="app-item bg-white border border-gray-200 rounded-xl p-3 cursor-pointer shadow-sm hover:shadow-md transition-shadow" onclick="openSubPage('sistema-touros')">
                            <div class="flex flex-col items-center text-center space-y-2">
                                <div class="w-12 h-12 bg-gradient-to-br from-red-600 to-red-700 rounded-xl flex items-center justify-center">
                                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M19.5 6c-1.3 0-2.5.8-3 2-.5-1.2-1.7-2-3-2s-2.5.8-3 2c-.5-1.2-1.7-2-3-2C5.5 6 4 7.5 4 9.5c0 1.3.7 2.4 1.7 3.1-.4.6-.7 1.3-.7 2.1 0 2.2 1.8 4 4 4h6c2.2 0 4-1.8 4-4 0-.8-.3-1.5-.7-2.1 1-.7 1.7-1.8 1.7-3.1 0-2-1.5-3.5-3.5-3.5zM9 16c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm6 0c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2z"/>
                                        <circle cx="12" cy="8" r="2" fill="white"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900 text-xs">Sistema de Touros</p>
                                    <p class="text-[10px] text-gray-600 mt-0.5">Touros e inseminações</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Controle de Novilhas -->
                        <div class="app-item bg-white border border-gray-200 rounded-xl p-3 cursor-pointer shadow-sm" onclick="openSubPage('controle-novilhas')">
                            <div class="flex flex-col items-center text-center space-y-2">
                                <div class="w-12 h-12 bg-gradient-to-br from-fuchsia-500 to-fuchsia-600 rounded-xl flex items-center justify-center">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900 text-xs">Controle de Novilhas</p>
                                    <p class="text-[10px] text-gray-600 mt-0.5">Custos de criação</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openSubPage(page) {
            window.parent.postMessage({
                type: 'openModal',
                page: page,
                fullscreen: true
            }, '*');
        }
    </script>
</body>
</html>

