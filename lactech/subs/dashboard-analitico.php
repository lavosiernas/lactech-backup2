<?php
/**
 * Página: Dashboard Analítico
 * Subpágina do Mais Opções
 */

require_once __DIR__ . '/../includes/config_login.php';
require_once __DIR__ . '/../includes/Database.class.php';

if (!isLoggedIn() || ($_SESSION['user_role'] !== 'gerente' && $_SESSION['user_role'] !== 'manager')) {
    http_response_code(403);
    die('Acesso negado');
}

$v = time();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Analítico - LacTech</title>
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
                <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg">
                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M3 3v18h18V3H3zm16 16H5V5h14v14zM7 7h10v2H7V7zm0 4h10v2H7v-2zm0 4h7v2H7v-2z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Dashboard Analítico</h2>
                    <p class="text-sm text-gray-600">Métricas e indicadores de performance</p>
                </div>
            </div>
            <button onclick="window.parent.postMessage({type: 'closeModal'}, '*')" class="w-10 h-10 flex items-center justify-center hover:bg-gray-100 rounded-xl transition-colors">
                <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <!-- Content -->
        <div class="space-y-4 px-2 p-6">
            <!-- KPIs Principais -->
            <div class="bg-gradient-to-r from-purple-50 to-indigo-50 rounded-xl p-4">
                <h3 class="text-base font-bold text-gray-900 mb-3">KPIs Principais</h3>
                <div class="grid grid-cols-2 gap-3">
                    <div class="text-center p-3 bg-white rounded-lg shadow-sm">
                        <p class="text-xl font-bold text-blue-600">581L</p>
                        <p class="text-xs text-gray-600">Produção/Dia</p>
                    </div>
                    <div class="text-center p-3 bg-white rounded-lg shadow-sm">
                        <p class="text-xl font-bold text-green-600">29L</p>
                        <p class="text-xs text-gray-600">Média/Animal</p>
                    </div>
                    <div class="text-center p-3 bg-white rounded-lg shadow-sm">
                        <p class="text-xl font-bold text-orange-600">72%</p>
                        <p class="text-xs text-gray-600">Taxa Prenhez</p>
                    </div>
                    <div class="text-center p-3 bg-white rounded-lg shadow-sm">
                        <p class="text-xl font-bold text-purple-600">13</p>
                        <p class="text-xs text-gray-600">Total Animais</p>
                    </div>
                </div>
            </div>

            <!-- Qualidade do Leite -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <h3 class="text-base font-bold text-gray-900 mb-3">Qualidade do Leite</h3>
                <div class="space-y-3">
                    <div class="p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-gray-700">Gordura</span>
                            <span class="text-lg font-bold text-green-600">3.8%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-green-500 h-2 rounded-full" style="width: 76%"></div>
                        </div>
                    </div>
                    <div class="p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-gray-700">Proteína</span>
                            <span class="text-lg font-bold text-blue-600">3.2%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-500 h-2 rounded-full" style="width: 64%"></div>
                        </div>
                    </div>
                    <div class="p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-gray-700">Células Somáticas</span>
                            <span class="text-lg font-bold text-orange-600">250K</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-orange-500 h-2 rounded-full" style="width: 50%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Análises Disponíveis -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <h3 class="text-base font-bold text-gray-900 mb-3">Análises Disponíveis</h3>
                <div class="space-y-2">
                    <button onclick="window.parent.postMessage({type: 'openModal', page: 'graficos-producao'}, '*')" class="w-full flex items-center p-3 bg-gray-50 rounded-lg hover:bg-blue-50 hover:border-blue-300 transition-all border border-gray-200">
                        <svg class="w-5 h-5 text-blue-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3 3v18h18V3H3zm16 16H5V5h14v14zM7 7h10v2H7V7zm0 4h10v2H7v-2zm0 4h7v2H7v-2z" clip-rule="evenodd"></path>
                        </svg>
                        <div class="flex-1 text-left">
                            <p class="font-medium text-gray-900 text-sm">Gráficos de Produção</p>
                            <p class="text-xs text-gray-600">Análise por período</p>
                        </div>
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>
                    
                    <button onclick="window.parent.postMessage({type: 'openModal', page: 'comparativos-historicos'}, '*')" class="w-full flex items-center p-3 bg-gray-50 rounded-lg hover:bg-green-50 hover:border-green-300 transition-all border border-gray-200">
                        <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3 3v18h18V3H3zm16 16H5V5h14v14zM7 7h10v2H7V7zm0 4h10v2H7v-2zm0 4h7v2H7v-2z" clip-rule="evenodd"></path>
                        </svg>
                        <div class="flex-1 text-left">
                            <p class="font-medium text-gray-900 text-sm">Comparativos Históricos</p>
                            <p class="text-xs text-gray-600">Evolução temporal</p>
                        </div>
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>
                    
                    <button onclick="window.parent.postMessage({type: 'openModal', page: 'metricas-eficiencia'}, '*')" class="w-full flex items-center p-3 bg-gray-50 rounded-lg hover:bg-orange-50 hover:border-orange-300 transition-all border border-gray-200">
                        <svg class="w-5 h-5 text-orange-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3 3v18h18V3H3zm16 16H5V5h14v14zM7 7h10v2H7V7zm0 4h10v2H7v-2zm0 4h7v2H7v-2z" clip-rule="evenodd"></path>
                        </svg>
                        <div class="flex-1 text-left">
                            <p class="font-medium text-gray-900 text-sm">Métricas de Eficiência</p>
                            <p class="text-xs text-gray-600">Performance do rebanho</p>
                        </div>
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

