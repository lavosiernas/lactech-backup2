<?php
// Incluir configuração do banco
require_once 'config_mysql.php';

// Conectar ao banco de dados
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Buscar dados reais dos animais
    $stmt = $pdo->query("
        SELECT a.*, 
               DATEDIFF(CURDATE(), a.birth_date) as age_days,
               FLOOR(DATEDIFF(CURDATE(), a.birth_date) / 30) as age_months
        FROM animals a 
        WHERE a.is_active = 1 
        ORDER BY a.animal_number
    ");
    $animals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Buscar dados de produção de leite
    $stmt = $pdo->query("
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
    $milk_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calcular estatísticas gerais
    $total_animals = count($animals);
    $lactating_cows = count(array_filter($animals, function($a) { return $a['status'] === 'Lactante'; }));
    $pregnant_cows = count(array_filter($animals, function($a) { return $a['reproductive_status'] === 'prenha'; }));
    
    // Calcular produção total dos últimos 7 dias
    $stmt = $pdo->query("
        SELECT SUM(volume) as total_volume
        FROM milk_production 
        WHERE production_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    ");
    $total_production = $stmt->fetch(PDO::FETCH_ASSOC)['total_volume'] ?? 0;
    $avg_daily_production = $total_production / 7;
    
} catch (PDOException $e) {
    // Fallback para dados simulados em caso de erro
    $animals = [];
    $milk_data = [];
    $total_animals = 0;
    $lactating_cows = 0;
    $pregnant_cows = 0;
    $avg_daily_production = 0;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LACTECH - Mais Opções</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* CSS otimizado para carregamento rápido */
        * {
            box-sizing: border-box;
        }
        
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f9fafb;
            overflow-x: hidden;
        }
        
        /* Preloader simples */
        .preloader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #f9fafb;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            transition: opacity 0.3s ease;
        }
        
        .preloader.hidden {
            opacity: 0;
            pointer-events: none;
        }
        
        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #e5e7eb;
            border-top: 4px solid #10b981;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .app-item {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .app-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        }

        /* Modal Styles otimizados */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 99998;
            display: none;
            opacity: 0;
            transition: opacity 0.2s ease;
        }

        .modal.show {
            display: flex;
            opacity: 1;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: white;
            width: 100%;
            height: 100vh;
            overflow-y: auto;
            position: relative;
        }
        
        /* Otimizações para mobile */
        @media (max-width: 768px) {
            .modal-content {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Preloader -->
    <div class="preloader" id="preloader">
        <div class="spinner"></div>
    </div>
    
    <div class="w-full min-h-screen overflow-y-auto">
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
            <button onclick="goBackToDashboard()" class="flex items-center space-x-2 px-6 py-3 bg-gradient-to-r from-gray-100 to-gray-200 hover:from-gray-200 hover:to-gray-300 rounded-xl transition-all duration-200 shadow-sm">
                <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                <span class="text-gray-700 font-semibold">Voltar ao Dashboard</span>
            </button>
        </div>
        
        <!-- Content -->
        <div class="p-8">
            <div class="max-w-7xl mx-auto">
                <!-- Added all 11 buttons for Ferramentas Principais -->
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
                        <div class="app-item bg-white border border-gray-200 rounded-xl p-3 cursor-pointer shadow-sm" onclick="openModal('reports')">
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
                        <div class="app-item bg-white border border-gray-200 rounded-xl p-3 cursor-pointer shadow-sm" onclick="openModal('animals')">
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
                        <div class="app-item bg-white border border-gray-200 rounded-xl p-3 cursor-pointer shadow-sm" onclick="openModal('health')">
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
                        <div class="app-item bg-white border border-gray-200 rounded-xl p-3 cursor-pointer shadow-sm" onclick="openModal('reproduction')">
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
                        <div class="app-item bg-white border border-gray-200 rounded-xl p-3 cursor-pointer shadow-sm" onclick="openModal('analytics')">
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
                        <div class="app-item bg-white border border-gray-200 rounded-xl p-3 cursor-pointer shadow-sm" onclick="openModal('actions')">
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
                        
                        <!-- Sistema RFID -->
                        <div class="app-item bg-white border border-gray-200 rounded-xl p-3 cursor-pointer shadow-sm" onclick="openModal('rfid')">
                            <div class="flex flex-col items-center text-center space-y-2">
                                <div class="w-12 h-12 bg-gradient-to-br from-cyan-500 to-cyan-600 rounded-xl flex items-center justify-center">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900 text-xs">Sistema RFID</p>
                                    <p class="text-[10px] text-gray-600 mt-0.5">Transponders</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Condição Corporal -->
                        <div class="app-item bg-white border border-gray-200 rounded-xl p-3 cursor-pointer shadow-sm" onclick="openModal('bcs')">
                            <div class="flex flex-col items-center text-center space-y-2">
                                <div class="w-12 h-12 bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl flex items-center justify-center">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900 text-xs">Condição Corporal</p>
                                    <p class="text-[10px] text-gray-600 mt-0.5">Avaliação BCS</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Grupos e Lotes -->
                        <div class="app-item bg-white border border-gray-200 rounded-xl p-3 cursor-pointer shadow-sm" onclick="openModal('groups')">
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
                        
                        <!-- Insights de IA -->
                        <div class="app-item bg-white border border-gray-200 rounded-xl p-3 cursor-pointer shadow-sm" onclick="openModal('ai')">
                            <div class="flex flex-col items-center text-center space-y-2">
                                <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900 text-xs">Insights de IA</p>
                                    <p class="text-[10px] text-gray-600 mt-0.5">Previsões</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Suporte -->
                        <div class="app-item bg-white border border-gray-200 rounded-xl p-3 cursor-pointer shadow-sm" onclick="openModal('support')">
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
                    </div>
                </div>
                
                <!-- Added all 4 buttons for Utilitários -->
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
                        <!-- Contatos -->
                        <!-- Removed Contatos button as requested -->
                        
                        <!-- Alimentação -->
                        <div class="app-item bg-white border border-gray-200 rounded-xl p-3 cursor-pointer shadow-sm" onclick="openModal('feeding')">
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
                        <div class="app-item bg-white border border-gray-200 rounded-xl p-3 cursor-pointer shadow-sm" onclick="openModal('bulls')">
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
                        <div class="app-item bg-white border border-gray-200 rounded-xl p-3 cursor-pointer shadow-sm" onclick="openModal('heifers')">
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

    <!-- Added modals for all 15 new buttons -->
    <!-- Modals -->
    <!-- Modal Relatórios -->
    <div id="modal-reports" class="modal">
        <div class="modal-content">
            <div class="flex items-center justify-between mb-4 pb-4 border-b border-gray-200">
                <h2 class="text-xl font-bold text-gray-900">Relatórios</h2>
                <button onclick="closeModal('reports')" class="w-8 h-8 flex items-center justify-center hover:bg-gray-100 rounded-lg transition-colors">
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="text-gray-700">
                <!-- Estatísticas de Produção -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                    <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-4 text-center border border-blue-200">
                        <div class="text-3xl font-bold text-blue-600 mb-1"><?php echo number_format($total_production, 0); ?>L</div>
                        <div class="text-sm text-blue-700 font-medium">Produção 7 dias</div>
                    </div>
                    <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-4 text-center border border-green-200">
                        <div class="text-3xl font-bold text-green-600 mb-1"><?php echo number_format($avg_daily_production, 1); ?>L</div>
                        <div class="text-sm text-green-700 font-medium">Média Diária</div>
                    </div>
                    <div class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-xl p-4 text-center border border-orange-200">
                        <div class="text-3xl font-bold text-orange-600 mb-1"><?php echo $lactating_cows; ?></div>
                        <div class="text-sm text-orange-700 font-medium">Animais Ativos</div>
                    </div>
                    <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl p-4 text-center border border-purple-200">
                        <div class="text-3xl font-bold text-purple-600 mb-1"><?php echo $lactating_cows > 0 ? number_format($avg_daily_production / $lactating_cows, 1) : '0'; ?>L</div>
                        <div class="text-sm text-purple-700 font-medium">Por Animal</div>
                    </div>
                </div>

                <!-- Dados de Qualidade -->
                <?php if(!empty($milk_data)): ?>
                <div class="mb-8">
                    <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                        <svg class="w-5 h-5 text-gray-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                        </svg>
                        Qualidade do Leite (Últimos 7 dias)
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-white border border-gray-200 rounded-xl p-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-gray-700">Gordura</span>
                                <span class="text-lg font-bold text-green-600"><?php echo number_format(array_sum(array_column($milk_data, 'avg_fat')) / count($milk_data), 1); ?>%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-green-500 h-2 rounded-full" style="width: <?php echo (array_sum(array_column($milk_data, 'avg_fat')) / count($milk_data)) * 25; ?>%"></div>
                            </div>
                        </div>
                        <div class="bg-white border border-gray-200 rounded-xl p-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-gray-700">Proteína</span>
                                <span class="text-lg font-bold text-blue-600"><?php echo number_format(array_sum(array_column($milk_data, 'avg_protein')) / count($milk_data), 1); ?>%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-blue-500 h-2 rounded-full" style="width: <?php echo (array_sum(array_column($milk_data, 'avg_protein')) / count($milk_data)) * 30; ?>%"></div>
                            </div>
                        </div>
                        <div class="bg-white border border-gray-200 rounded-xl p-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-gray-700">Células Somáticas</span>
                                <span class="text-lg font-bold text-orange-600"><?php echo number_format(array_sum(array_column($milk_data, 'avg_somatic_cells')) / count($milk_data) / 1000, 0); ?>K</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-orange-500 h-2 rounded-full" style="width: <?php echo min(100, (array_sum(array_column($milk_data, 'avg_somatic_cells')) / count($milk_data)) / 500); ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Produção Recente -->
                <?php if(!empty($milk_data)): ?>
                <div class="mb-8">
                    <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                        <svg class="w-5 h-5 text-gray-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        Produção dos Últimos 7 Dias
                    </h3>
                    <div class="space-y-2">
                        <?php foreach($milk_data as $day): ?>
                        <div class="bg-white border border-gray-200 rounded-xl p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-medium text-gray-900"><?php echo date('d/m/Y', strtotime($day['date'])); ?></p>
                                    <p class="text-sm text-gray-600"><?php echo number_format($day['daily_volume'], 1); ?>L produzidos</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-lg font-bold text-blue-600"><?php echo number_format($day['daily_volume'], 0); ?>L</p>
                                    <p class="text-xs text-gray-500">Gordura: <?php echo number_format($day['avg_fat'], 1); ?>%</p>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Relatórios Disponíveis -->
                <div class="bg-gray-50 rounded-xl p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                        <svg class="w-5 h-5 text-gray-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2H4zm0 2h12v8H4V6z" clip-rule="evenodd"></path>
                        </svg>
                        Relatórios Disponíveis
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="flex items-center space-x-3 p-3 bg-white rounded-lg">
                            <svg class="w-5 h-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-sm font-medium text-gray-700">Produção Diária</span>
                        </div>
                        <div class="flex items-center space-x-3 p-3 bg-white rounded-lg">
                            <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-sm font-medium text-gray-700">Análise Financeira</span>
                        </div>
                        <div class="flex items-center space-x-3 p-3 bg-white rounded-lg">
                            <svg class="w-5 h-5 text-purple-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-sm font-medium text-gray-700">Indicadores KPI</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Gestão de Rebanho -->
    <div id="modal-animals" class="modal">
        <div class="modal-content">
            <div class="flex items-center justify-between mb-6 pb-4 border-b-2 border-gray-200 px-4">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M19.5 6c-1.3 0-2.5.8-3 2-.5-1.2-1.7-2-3-2s-2.5.8-3 2c-.5-1.2-1.7-2-3-2C5.5 6 4 7.5 4 9.5c0 1.3.7 2.4 1.7 3.1-.4.6-.7 1.3-.7 2.1 0 2.2 1.8 4 4 4h6c2.2 0 4-1.8 4-4 0-.8-.3-1.5-.7-2.1 1-.7 1.7-1.8 1.7-3.1 0-2-1.5-3.5-3.5-3.5z"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">Gestão de Rebanho</h2>
                        <p class="text-sm text-gray-600">Lista completa e pedigree dos animais</p>
                    </div>
                </div>
                <button onclick="closeSubModal('animals')" class="w-10 h-10 flex items-center justify-center hover:bg-gray-100 rounded-xl transition-colors">
                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="space-y-4 px-6 pb-6 overflow-y-auto max-h-[calc(100vh-200px)]">
                <!-- Indicadores do Rebanho - Mobile First -->
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-4">
                    <h3 class="text-base font-bold text-gray-900 mb-3">Resumo do Rebanho</h3>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="text-center p-3 bg-white rounded-lg shadow-sm">
                            <p class="text-xl font-bold text-blue-600"><?php echo $total_animals; ?></p>
                            <p class="text-xs text-gray-600">Total</p>
                        </div>
                        <div class="text-center p-3 bg-white rounded-lg shadow-sm">
                            <p class="text-xl font-bold text-green-600"><?php echo $lactating_cows; ?></p>
                            <p class="text-xs text-gray-600">Lactantes</p>
                        </div>
                        <div class="text-center p-3 bg-white rounded-lg shadow-sm">
                            <p class="text-xl font-bold text-pink-600"><?php echo $pregnant_cows; ?></p>
                            <p class="text-xs text-gray-600">Prenhezes</p>
                        </div>
                        <div class="text-center p-3 bg-white rounded-lg shadow-sm">
                            <p class="text-xl font-bold text-orange-600"><?php echo number_format($avg_daily_production, 1); ?>L</p>
                            <p class="text-xs text-gray-600">Produção/Dia</p>
                        </div>
                    </div>
                </div>

                <!-- Busca Rápida - Mobile Optimized -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                    <h3 class="text-base font-bold text-gray-900 mb-3">Busca Rápida</h3>
                    <div class="space-y-3">
                        <input type="text" id="searchAnimal" placeholder="Nome ou número do animal..." class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                        <div class="grid grid-cols-2 gap-2">
                            <select id="filterStatus" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm">
                                <option value="">Todos status</option>
                                <option value="Lactante">Lactante</option>
                                <option value="Seco">Seco</option>
                                <option value="Prenha">Prenha</option>
                                <option value="Novilha">Novilha</option>
                                <option value="Touro">Touro</option>
                            </select>
                            <select id="filterBreed" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm">
                                <option value="">Todas raças</option>
                                <option value="Holandesa">Holandesa</option>
                                <option value="Gir">Gir</option>
                                <option value="Girolando">Girolando</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Lista de Animais - Mobile Cards -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-base font-bold text-gray-900">Lista de Animais</h3>
                        <button onclick="openAddAnimalModal()" class="flex items-center space-x-2 px-4 py-2 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-lg hover:from-green-600 hover:to-green-700 transition-all text-sm shadow-md">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
                            </svg>
                            <span>Adicionar Animal</span>
                        </button>
                    </div>
                    <div id="animalsListContainer" class="space-y-3">
                        <?php foreach($animals as $animal): ?>
                        <div class="animal-card p-3 bg-gray-50 rounded-lg border border-gray-200" 
                             data-name="<?php echo htmlspecialchars(strtolower($animal['name'] ?? '')); ?>"
                             data-number="<?php echo htmlspecialchars(strtolower($animal['animal_number'] ?? '')); ?>"
                             data-status="<?php echo htmlspecialchars($animal['status'] ?? ''); ?>"
                             data-breed="<?php echo htmlspecialchars(strtolower($animal['breed'] ?? '')); ?>"
                             data-id="<?php echo $animal['id'] ?? ''; ?>"
                             data-animal-number="<?php echo htmlspecialchars($animal['animal_number'] ?? ''); ?>">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 bg-gradient-to-br from-<?php echo $animal['status'] === 'Lactante' ? 'green' : ($animal['status'] === 'Touro' ? 'red' : 'blue'); ?>-500 to-<?php echo $animal['status'] === 'Lactante' ? 'green' : ($animal['status'] === 'Touro' ? 'red' : 'blue'); ?>-600 rounded-lg flex items-center justify-center">
                                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M19.5 6c-1.3 0-2.5.8-3 2-.5-1.2-1.7-2-3-2s-2.5.8-3 2c-.5-1.2-1.7-2-3-2C5.5 6 4 7.5 4 9.5c0 1.3.7 2.4 1.7 3.1-.4.6-.7 1.3-.7 2.1 0 2.2 1.8 4 4 4h6c2.2 0 4-1.8 4-4 0-.8-.3-1.5-.7-2.1 1-.7 1.7-1.8 1.7-3.1 0-2-1.5-3.5-3.5-3.5z"/>
                                        </svg>
                                    </div>
                                    <div class="flex-1">
                                        <p class="font-medium text-gray-900 text-sm"><?php echo htmlspecialchars($animal['name'] ?? 'Sem nome'); ?></p>
                                        <p class="text-xs text-gray-600"><?php echo htmlspecialchars($animal['animal_number']); ?> - <?php echo htmlspecialchars($animal['breed']); ?></p>
                                        <p class="text-xs text-gray-500"><?php echo $animal['age_months']; ?> meses</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="px-2 py-1 bg-<?php echo $animal['status'] === 'Lactante' ? 'green' : ($animal['status'] === 'Touro' ? 'red' : 'blue'); ?>-100 text-<?php echo $animal['status'] === 'Lactante' ? 'green' : ($animal['status'] === 'Touro' ? 'red' : 'blue'); ?>-800 text-xs rounded-full">
                                        <?php echo htmlspecialchars($animal['status']); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="mt-2 flex space-x-2">
                                <button onclick="showPedigreeModal(<?php echo $animal['id']; ?>)" class="flex-1 px-3 py-1 bg-blue-100 text-blue-700 text-xs rounded-lg hover:bg-blue-200 transition-colors">
                                    Ver Pedigree
                                </button>
                                <button onclick="editAnimalModal(<?php echo $animal['id']; ?>)" class="flex-1 px-3 py-1 bg-gray-100 text-gray-700 text-xs rounded-lg hover:bg-gray-200 transition-colors">
                                    Editar
                                </button>
                                <button onclick="viewAnimalModal(<?php echo $animal['id']; ?>)" class="flex-1 px-3 py-1 bg-green-100 text-green-700 text-xs rounded-lg hover:bg-green-200 transition-colors">
                                    Ver
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Ações Rápidas -->
                <div class="bg-gray-50 rounded-xl p-6 mb-8">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                        <svg class="w-5 h-5 text-gray-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"></path>
                        </svg>
                        Ações Rápidas
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <button onclick="openHealthForm()" class="flex items-center space-x-3 p-4 bg-white rounded-lg border border-gray-200 hover:bg-blue-50 hover:border-blue-300 transition-all">
                            <svg class="w-6 h-6 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"></path>
                            </svg>
                            <div class="text-left">
                                <p class="font-medium text-gray-900">Registrar Saúde</p>
                                <p class="text-sm text-gray-600">Vacinas e medicamentos</p>
                            </div>
                        </button>
                        <button onclick="openReproductionForm()" class="flex items-center space-x-3 p-4 bg-white rounded-lg border border-gray-200 hover:bg-pink-50 hover:border-pink-300 transition-all">
                            <svg class="w-6 h-6 text-pink-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"></path>
                            </svg>
                            <div class="text-left">
                                <p class="font-medium text-gray-900">Registrar Reprodução</p>
                                <p class="text-sm text-gray-600">Cios e inseminações</p>
                            </div>
                        </button>
                        <button onclick="openRFIDForm()" class="flex items-center space-x-3 p-4 bg-white rounded-lg border border-gray-200 hover:bg-orange-50 hover:border-orange-300 transition-all">
                            <svg class="w-6 h-6 text-orange-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"></path>
                            </svg>
                            <div class="text-left">
                                <p class="font-medium text-gray-900">Cadastrar RFID</p>
                                <p class="text-sm text-gray-600">Transponders</p>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Lista de Animais do Rebanho -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                    <div class="p-6 border-b border-gray-200 flex items-center justify-between">
                        <h3 class="text-lg font-bold text-gray-900 flex items-center">
                            <svg class="w-5 h-5 text-gray-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                            </svg>
                            Rebanho Completo (<?php echo count($animals); ?> animais)
                        </h3>
                        <button onclick="openAddAnimalModal()" class="flex items-center space-x-2 px-4 py-2 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-lg hover:from-green-600 hover:to-green-700 transition-all text-sm shadow-md">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
                            </svg>
                            <span>Adicionar Animal</span>
                        </button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Animal</th>
                                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Raça</th>
                                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Idade</th>
                                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pedigree</th>
                                    <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach($animals as $animal): ?>
                                <tr class="animal-table-row hover:bg-gray-50"
                                    data-name="<?php echo htmlspecialchars(strtolower($animal['name'] ?? '')); ?>"
                                    data-number="<?php echo htmlspecialchars(strtolower($animal['animal_number'] ?? '')); ?>"
                                    data-status="<?php echo htmlspecialchars($animal['status'] ?? ''); ?>"
                                    data-breed="<?php echo htmlspecialchars(strtolower($animal['breed'] ?? '')); ?>"
                                    data-id="<?php echo $animal['id'] ?? ''; ?>">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-green-600 rounded-full flex items-center justify-center text-white font-bold text-sm">
                                                <?php echo strtoupper(substr($animal['name'] ?? 'A', 0, 1)); ?>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($animal['name'] ?? 'Sem nome'); ?>
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    #<?php echo htmlspecialchars($animal['animal_number']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                            <?php 
                                            $status = $animal['status'] ?? '';
                                            if($status === 'Lactante') echo 'bg-green-100 text-green-800';
                                            elseif($status === 'Vaca') echo 'bg-blue-100 text-blue-800';
                                            elseif($status === 'Novilha') echo 'bg-yellow-100 text-yellow-800';
                                            elseif($status === 'Bezerra' || $status === 'Bezerro') echo 'bg-purple-100 text-purple-800';
                                            else echo 'bg-gray-100 text-gray-800';
                                            ?>">
                                            <?php echo htmlspecialchars($status); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($animal['breed'] ?? 'N/A'); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php 
                                        if(isset($animal['birth_date']) && $animal['birth_date']) {
                                            $birthDate = new DateTime($animal['birth_date']);
                                            $today = new DateTime();
                                            $age = $today->diff($birthDate);
                                            echo $age->y . ' anos, ' . $age->m . ' meses';
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <div class="flex items-center space-x-2">
                                            <button onclick="showPedigreeModal(<?php echo $animal['id']; ?>)" class="text-green-600 hover:text-green-800 font-medium">
                                                Ver Pedigree
                                            </button>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <button onclick="editAnimalModal(<?php echo $animal['id']; ?>)" class="text-blue-600 hover:text-blue-800">
                                                Editar
                                            </button>
                                            <button onclick="viewAnimalModal(<?php echo $animal['id']; ?>)" class="text-green-600 hover:text-green-800">
                                                Ver
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Gestão Sanitária -->
    <div id="modal-health" class="modal">
        <div class="modal-content">
            <div class="flex items-center justify-between mb-6 pb-4 border-b-2 border-gray-200">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-green-600 rounded-xl flex items-center justify-center shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">Gestão Sanitária</h2>
                        <p class="text-sm text-gray-600">Controle de saúde e bem-estar do rebanho</p>
                    </div>
                </div>
                <button onclick="closeModal('health')" class="w-10 h-10 flex items-center justify-center hover:bg-gray-100 rounded-xl transition-colors">
                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <div class="space-y-4 px-2">
                <!-- Indicadores Sanitários - Mobile First -->
                <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl p-4">
                    <h3 class="text-base font-bold text-gray-900 mb-3">Indicadores Sanitários</h3>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="text-center p-3 bg-white rounded-lg shadow-sm">
                            <p class="text-xl font-bold text-green-600">2.1%</p>
                            <p class="text-xs text-gray-600">Mortalidade</p>
                        </div>
                        <div class="text-center p-3 bg-white rounded-lg shadow-sm">
                            <p class="text-xl font-bold text-blue-600">5</p>
                            <p class="text-xs text-gray-600">Casos Mastite</p>
                        </div>
                        <div class="text-center p-3 bg-white rounded-lg shadow-sm">
                            <p class="text-xl font-bold text-purple-600">95%</p>
                            <p class="text-xs text-gray-600">Vacinação OK</p>
                        </div>
                        <div class="text-center p-3 bg-white rounded-lg shadow-sm">
                            <p class="text-xl font-bold text-orange-600">3</p>
                            <p class="text-xs text-gray-600">Doentes</p>
                        </div>
                    </div>
                </div>

                <!-- Ações Essenciais - Mobile Optimized -->
                <div class="grid grid-cols-1 gap-3">
                    <button onclick="openHealthForm()" class="flex items-center p-4 bg-white rounded-xl border border-gray-200 hover:bg-green-50 hover:border-green-300 transition-all">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mr-4">
                            <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="flex-1 text-left">
                            <p class="font-medium text-gray-900">Registrar Doença</p>
                            <p class="text-sm text-gray-600">Diagnóstico e Tratamento</p>
                        </div>
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>
                    
                    <button onclick="openVaccinationForm()" class="flex items-center p-4 bg-white rounded-xl border border-gray-200 hover:bg-blue-50 hover:border-blue-300 transition-all">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mr-4">
                            <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="flex-1 text-left">
                            <p class="font-medium text-gray-900">Aplicar Vacina</p>
                            <p class="text-sm text-gray-600">Controle de Vacinação</p>
                        </div>
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>
                    
                    <button onclick="openMastitisForm()" class="flex items-center p-4 bg-white rounded-xl border border-gray-200 hover:bg-pink-50 hover:border-pink-300 transition-all">
                        <div class="w-12 h-12 bg-pink-100 rounded-lg flex items-center justify-center mr-4">
                            <svg class="w-6 h-6 text-pink-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="flex-1 text-left">
                            <p class="font-medium text-gray-900">Controle Mastite</p>
                            <p class="text-sm text-gray-600">Teste e Tratamento</p>
                        </div>
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>
                </div>

                <!-- Alertas Sanitários - Mobile Stack -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                    <h3 class="text-base font-bold text-gray-900 mb-3">Alertas Sanitários</h3>
                    <div class="space-y-3">
                        <div class="p-3 bg-red-50 rounded-lg border-l-4 border-red-400">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <p class="font-medium text-red-800 text-sm">Vacina Atrasada</p>
                                    <p class="text-xs text-red-600 mt-1">Aftosa - 15 animais</p>
                                    <p class="text-xs text-red-500 mt-1">Venceu há 5 dias</p>
                                </div>
                                <button onclick="scheduleVaccination('aftosa')" class="ml-3 px-3 py-1 bg-red-600 text-white text-xs rounded-lg hover:bg-red-700 transition-colors">
                                    Aplicar
                                </button>
                            </div>
                        </div>
                        
                        <div class="p-3 bg-yellow-50 rounded-lg border-l-4 border-yellow-400">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <p class="font-medium text-yellow-800 text-sm">Mastite Ativa</p>
                                    <p class="text-xs text-yellow-600 mt-1">Vaca #123 - Quarto posterior</p>
                                </div>
                                <button onclick="treatMastitis('123')" class="ml-3 px-3 py-1 bg-yellow-600 text-white text-xs rounded-lg hover:bg-yellow-700 transition-colors">
                                    Tratar
                                </button>
                            </div>
                        </div>
                        
                        <div class="p-3 bg-orange-50 rounded-lg border-l-4 border-orange-400">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <p class="font-medium text-orange-800 text-sm">Medicamento Baixo</p>
                                    <p class="text-xs text-orange-600 mt-1">Penicilina - 2 doses restantes</p>
                                </div>
                                <button onclick="reorderMedicine('penicilina')" class="ml-3 px-3 py-1 bg-orange-600 text-white text-xs rounded-lg hover:bg-orange-700 transition-colors">
                                    Repor
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Controle de Vacinação - Mobile Cards -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                    <h3 class="text-base font-bold text-gray-900 mb-3">Próximas Vacinações</h3>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div>
                                <p class="text-sm font-medium text-gray-700">Aftosa (Maio)</p>
                                <p class="text-xs text-gray-600">230 animais</p>
                            </div>
                            <span class="text-lg font-bold text-blue-600">15 dias</span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div>
                                <p class="text-sm font-medium text-gray-700">Brucelose</p>
                                <p class="text-xs text-gray-600">45 novilhas</p>
                            </div>
                            <span class="text-lg font-bold text-green-600">30 dias</span>
                        </div>
                    </div>
                </div>

                <!-- Biossegurança - Mobile List -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                    <h3 class="text-base font-bold text-gray-900 mb-3">Biossegurança</h3>
                    <div class="space-y-2">
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div class="flex-1">
                                <p class="font-medium text-gray-900 text-sm">Quarentena Ativa</p>
                                <p class="text-xs text-gray-600">2 animais novos</p>
                            </div>
                            <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded-full">7 dias</span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div class="flex-1">
                                <p class="font-medium text-gray-900 text-sm">Limpeza Ordenha</p>
                                <p class="text-xs text-gray-600">Última: hoje 14:00</p>
                            </div>
                            <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">OK</span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div class="flex-1">
                                <p class="font-medium text-gray-900 text-sm">Controle Acesso</p>
                                <p class="text-xs text-gray-600">Visitas registradas</p>
                            </div>
                            <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">Ativo</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Reprodução -->
    <div id="modal-reproduction" class="modal">
        <div class="modal-content">
            <div class="flex items-center justify-between mb-6 pb-4 border-b-2 border-gray-200">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-pink-500 to-pink-600 rounded-xl flex items-center justify-center shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">Gestão Reprodutiva</h2>
                        <p class="text-sm text-gray-600">Controle completo do ciclo reprodutivo</p>
                    </div>
                </div>
                <button onclick="closeModal('reproduction')" class="w-10 h-10 flex items-center justify-center hover:bg-gray-100 rounded-xl transition-colors">
                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <div class="space-y-4 px-2">
                <!-- Indicadores Essenciais - Mobile First -->
                <div class="bg-gradient-to-r from-pink-50 to-purple-50 rounded-xl p-4">
                    <h3 class="text-base font-bold text-gray-900 mb-3">Indicadores Reprodutivos</h3>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="text-center p-3 bg-white rounded-lg shadow-sm">
                            <p class="text-xl font-bold text-pink-600">42</p>
                            <p class="text-xs text-gray-600">Prenhezes</p>
                        </div>
                        <div class="text-center p-3 bg-white rounded-lg shadow-sm">
                            <p class="text-xl font-bold text-green-600">78%</p>
                            <p class="text-xs text-gray-600">Concepção</p>
                        </div>
                        <div class="text-center p-3 bg-white rounded-lg shadow-sm">
                            <p class="text-xl font-bold text-blue-600">395</p>
                            <p class="text-xs text-gray-600">IEP (dias)</p>
                        </div>
                        <div class="text-center p-3 bg-white rounded-lg shadow-sm">
                            <p class="text-xl font-bold text-orange-600">24</p>
                            <p class="text-xs text-gray-600">1º Parto</p>
                        </div>
                    </div>
                </div>

                <!-- Ações Essenciais - Mobile Optimized -->
                <div class="grid grid-cols-1 gap-3">
                    <button onclick="openInseminationForm()" class="flex items-center p-4 bg-white rounded-xl border border-gray-200 hover:bg-purple-50 hover:border-purple-300 transition-all">
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mr-4">
                            <svg class="w-6 h-6 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="flex-1 text-left">
                            <p class="font-medium text-gray-900">Inseminar</p>
                            <p class="text-sm text-gray-600">IA ou Monta Natural</p>
                        </div>
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>
                    
                    <button onclick="openPregnancyTestForm()" class="flex items-center p-4 bg-white rounded-xl border border-gray-200 hover:bg-green-50 hover:border-green-300 transition-all">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mr-4">
                            <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="flex-1 text-left">
                            <p class="font-medium text-gray-900">Teste Prenhez</p>
                            <p class="text-sm text-gray-600">Diagnóstico de Gestação</p>
                        </div>
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>
                    
                    <button onclick="openBirthForm()" class="flex items-center p-4 bg-white rounded-xl border border-gray-200 hover:bg-blue-50 hover:border-blue-300 transition-all">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mr-4">
                            <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="flex-1 text-left">
                            <p class="font-medium text-gray-900">Registrar Parto</p>
                            <p class="text-sm text-gray-600">Nascimento e Dados</p>
                        </div>
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>
                </div>

                <!-- Alertas Críticos - Mobile Stack -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                    <h3 class="text-base font-bold text-gray-900 mb-3">Alertas Críticos</h3>
                    <div class="space-y-3">
                        <div class="p-3 bg-red-50 rounded-lg border-l-4 border-red-400">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <p class="font-medium text-red-800 text-sm">Parto Iminente</p>
                                    <p class="text-xs text-red-600 mt-1">Vaca #123 - DPP: 08/10/2025</p>
                                    <p class="text-xs text-red-500 mt-1">2 dias restantes</p>
                                </div>
                                <button onclick="prepareForBirth('123')" class="ml-3 px-3 py-1 bg-red-600 text-white text-xs rounded-lg hover:bg-red-700 transition-colors">
                                    Preparar
                                </button>
                            </div>
                        </div>
                        
                        <div class="p-3 bg-yellow-50 rounded-lg border-l-4 border-yellow-400">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <p class="font-medium text-yellow-800 text-sm">Exame de Prenhez</p>
                                    <p class="text-xs text-yellow-600 mt-1">Vaca #456 - 30 dias pós-IA</p>
                                </div>
                                <button onclick="schedulePregnancyTest('456')" class="ml-3 px-3 py-1 bg-yellow-600 text-white text-xs rounded-lg hover:bg-yellow-700 transition-colors">
                                    Agendar
                                </button>
                            </div>
                        </div>
                        
                        <div class="p-3 bg-orange-50 rounded-lg border-l-4 border-orange-400">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <p class="font-medium text-orange-800 text-sm">Retorno ao Cio</p>
                                    <p class="text-xs text-orange-600 mt-1">Vaca #789 - 45 dias pós-parto</p>
                                </div>
                                <button onclick="monitorEstrus('789')" class="ml-3 px-3 py-1 bg-orange-600 text-white text-xs rounded-lg hover:bg-orange-700 transition-colors">
                                    Monitorar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Controle de Novilhas - Mobile Cards -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                    <h3 class="text-base font-bold text-gray-900 mb-3">Controle de Novilhas</h3>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div>
                                <p class="text-sm font-medium text-gray-700">Prontas para IA</p>
                                <p class="text-xs text-gray-600">Idade: 14-16 meses</p>
                            </div>
                            <span class="text-2xl font-bold text-green-600">8</span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div>
                                <p class="text-sm font-medium text-gray-700">Primeira IA</p>
                                <p class="text-xs text-gray-600">Últimos 30 dias</p>
                            </div>
                            <span class="text-2xl font-bold text-blue-600">12</span>
                        </div>
                    </div>
                </div>

                <!-- Histórico Essencial - Mobile List -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                    <h3 class="text-base font-bold text-gray-900 mb-3">Eventos Recentes</h3>
                    <div class="space-y-2">
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div class="flex-1">
                                <p class="font-medium text-gray-900 text-sm">Vaca #123</p>
                                <p class="text-xs text-gray-600">Inseminação - 15/01/2025</p>
                            </div>
                            <span class="px-2 py-1 bg-pink-100 text-pink-800 text-xs rounded-full">Prenha</span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div class="flex-1">
                                <p class="font-medium text-gray-900 text-sm">Vaca #456</p>
                                <p class="text-xs text-gray-600">Parto - 10/01/2025</p>
                            </div>
                            <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">Pariu</span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div class="flex-1">
                                <p class="font-medium text-gray-900 text-sm">Vaca #789</p>
                                <p class="text-xs text-gray-600">Cio - 20/01/2025</p>
                            </div>
                            <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded-full">Aguardando IA</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Dashboard Analítico -->
    <div id="modal-analytics" class="modal">
        <div class="modal-content">
            <div class="flex items-center justify-between mb-6 pb-4 border-b-2 border-gray-200">
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
                <button onclick="closeModal('analytics')" class="w-10 h-10 flex items-center justify-center hover:bg-gray-100 rounded-xl transition-colors">
                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <div class="space-y-4 px-2">
                <!-- KPIs Principais - Mobile First -->
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

                <!-- Qualidade do Leite - Mobile Cards -->
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

                <!-- Análises Disponíveis - Mobile List -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                    <h3 class="text-base font-bold text-gray-900 mb-3">Análises Disponíveis</h3>
                    <div class="space-y-2">
                        <button onclick="openProductionChart()" class="w-full flex items-center p-3 bg-gray-50 rounded-lg hover:bg-blue-50 hover:border-blue-300 transition-all border border-gray-200">
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
                        
                        <button onclick="openHistoricalComparison()" class="w-full flex items-center p-3 bg-gray-50 rounded-lg hover:bg-green-50 hover:border-green-300 transition-all border border-gray-200">
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
                        
                        <button onclick="openEfficiencyMetrics()" class="w-full flex items-center p-3 bg-gray-50 rounded-lg hover:bg-orange-50 hover:border-orange-300 transition-all border border-gray-200">
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
    </div>

    <!-- Modal Central de Ações -->
    <div id="modal-actions" class="modal">
        <div class="modal-content">
            <div class="flex items-center justify-between mb-6 pb-4 border-b-2 border-gray-200">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl flex items-center justify-center shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">Central de Ações</h2>
                        <p class="text-sm text-gray-600">Tarefas prioritárias e alertas importantes</p>
                    </div>
                </div>
                <button onclick="closeModal('actions')" class="w-10 h-10 flex items-center justify-center hover:bg-gray-100 rounded-xl transition-colors">
                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="space-y-4 px-2">
                <!-- Resumo de Alertas - Mobile First -->
                <div class="bg-gradient-to-r from-orange-50 to-red-50 rounded-xl p-4">
                    <h3 class="text-base font-bold text-gray-900 mb-3">Resumo de Alertas</h3>
                    <div class="grid grid-cols-3 gap-3">
                        <div class="text-center p-3 bg-white rounded-lg shadow-sm">
                            <p class="text-xl font-bold text-red-600">2</p>
                            <p class="text-xs text-gray-600">Urgentes</p>
                        </div>
                        <div class="text-center p-3 bg-white rounded-lg shadow-sm">
                            <p class="text-xl font-bold text-yellow-600">3</p>
                            <p class="text-xs text-gray-600">Pendentes</p>
                        </div>
                        <div class="text-center p-3 bg-white rounded-lg shadow-sm">
                            <p class="text-xl font-bold text-blue-600">4</p>
                            <p class="text-xs text-gray-600">Monitorar</p>
                        </div>
                    </div>
                </div>

                <!-- Ações Prioritárias - Mobile Stack -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                    <h3 class="text-base font-bold text-gray-900 mb-3">Ações Prioritárias</h3>
                    <div class="space-y-3">
                        <div class="p-3 bg-red-50 rounded-lg border-l-4 border-red-400">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <p class="font-medium text-red-800 text-sm">Vacinação Aftosa</p>
                                    <p class="text-xs text-red-600 mt-1">3 animais - Vence em 90 dias</p>
                                </div>
                                <button onclick="scheduleVaccination('aftosa')" class="ml-3 px-3 py-1 bg-red-600 text-white text-xs rounded-lg hover:bg-red-700 transition-colors">
                                    Agendar
                                </button>
                            </div>
                        </div>
                        
                        <div class="p-3 bg-yellow-50 rounded-lg border-l-4 border-yellow-400">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <p class="font-medium text-yellow-800 text-sm">Vermifugação</p>
                                    <p class="text-xs text-yellow-600 mt-1">3 animais - Vence em 90 dias</p>
                                </div>
                                <button onclick="scheduleDeworming()" class="ml-3 px-3 py-1 bg-yellow-600 text-white text-xs rounded-lg hover:bg-yellow-700 transition-colors">
                                    Agendar
                                </button>
                            </div>
                        </div>
                        
                        <div class="p-3 bg-blue-50 rounded-lg border-l-4 border-blue-400">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <p class="font-medium text-blue-800 text-sm">Partos Esperados</p>
                                    <p class="text-xs text-blue-600 mt-1">4 animais - Próximos 280 dias</p>
                                </div>
                                <button onclick="monitorBirths()" class="ml-3 px-3 py-1 bg-blue-600 text-white text-xs rounded-lg hover:bg-blue-700 transition-colors">
                                    Monitorar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ações Rápidas - Mobile List -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                    <h3 class="text-base font-bold text-gray-900 mb-3">Ações Rápidas</h3>
                    <div class="space-y-2">
                        <button onclick="openTaskList()" class="w-full flex items-center p-3 bg-gray-50 rounded-lg hover:bg-orange-50 hover:border-orange-300 transition-all border border-gray-200">
                            <svg class="w-5 h-5 text-orange-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                            </svg>
                            <div class="flex-1 text-left">
                                <p class="font-medium text-gray-900 text-sm">Lista de Tarefas</p>
                                <p class="text-xs text-gray-600">Tarefas prioritárias</p>
                            </div>
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </button>
                        
                        <button onclick="openNotifications()" class="w-full flex items-center p-3 bg-gray-50 rounded-lg hover:bg-blue-50 hover:border-blue-300 transition-all border border-gray-200">
                            <svg class="w-5 h-5 text-blue-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                            </svg>
                            <div class="flex-1 text-left">
                                <p class="font-medium text-gray-900 text-sm">Notificações</p>
                                <p class="text-xs text-gray-600">Alertas em tempo real</p>
                            </div>
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Sistema RFID -->
    <div id="modal-rfid" class="modal">
        <div class="modal-content">
            <div class="flex items-center justify-between mb-6 pb-4 border-b-2 border-gray-200">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-xl flex items-center justify-center shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">Sistema RFID</h2>
                        <p class="text-sm text-gray-600">Controle e monitoramento de transponders</p>
                    </div>
                </div>
                <button onclick="closeModal('rfid')" class="w-10 h-10 flex items-center justify-center hover:bg-gray-100 rounded-xl transition-colors">
                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="space-y-4 px-2">
                <!-- Estatísticas RFID - Mobile First -->
                <div class="bg-gradient-to-r from-indigo-50 to-blue-50 rounded-xl p-4">
                    <h3 class="text-base font-bold text-gray-900 mb-3">Estatísticas RFID</h3>
                    <div class="grid grid-cols-3 gap-3">
                        <div class="text-center p-3 bg-white rounded-lg shadow-sm">
                            <p class="text-xl font-bold text-blue-600">2</p>
                            <p class="text-xs text-gray-600">Transponders</p>
                        </div>
                        <div class="text-center p-3 bg-white rounded-lg shadow-sm">
                            <p class="text-xl font-bold text-green-600">100%</p>
                            <p class="text-xs text-gray-600">Ativos</p>
                        </div>
                        <div class="text-center p-3 bg-white rounded-lg shadow-sm">
                            <p class="text-xl font-bold text-purple-600">13</p>
                            <p class="text-xs text-gray-600">Animais</p>
                        </div>
                    </div>
                </div>

                <!-- Transponders Ativos - Mobile Cards -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                    <h3 class="text-base font-bold text-gray-900 mb-3">Transponders Ativos</h3>
                    <div class="space-y-2">
                        <div class="p-3 bg-gray-50 rounded-lg">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 bg-blue-500 rounded-lg flex items-center justify-center">
                                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-medium">Transponder 1</p>
                                        <p class="text-sm text-gray-600">Ativo - Última leitura: 10/01/2025</p>
                                    </div>
                                </div>
                                <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">Ativo</span>
                            </div>
                        </div>
                        <div class="p-3 bg-gray-50 rounded-lg">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 bg-blue-500 rounded-lg flex items-center justify-center">
                                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-medium">Transponder 2</p>
                                        <p class="text-sm text-gray-600">Ativo - Última leitura: 10/01/2025</p>
                                    </div>
                                </div>
                                <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">Ativo</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mb-4">
                    <h3 class="font-semibold text-lg mb-2">Estatísticas RFID</h3>
                    <div class="grid grid-cols-3 gap-4">
                        <div class="text-center p-3 bg-blue-50 rounded-lg">
                            <p class="text-xl font-bold text-blue-600">2</p>
                            <p class="text-sm text-gray-600">Transponders</p>
                        </div>
                        <div class="text-center p-3 bg-green-50 rounded-lg">
                            <p class="text-xl font-bold text-green-600">100%</p>
                            <p class="text-sm text-gray-600">Ativos</p>
                        </div>
                        <div class="text-center p-3 bg-purple-50 rounded-lg">
                            <p class="text-xl font-bold text-purple-600">13</p>
                            <p class="text-sm text-gray-600">Animais</p>
                        </div>
                    </div>
                </div>
                
                <div class="mb-4">
                    <h3 class="font-semibold text-lg mb-2">Funcionalidades</h3>
                    <ul class="space-y-2">
                        <li class="p-2 bg-gray-50 rounded flex items-center">
                            <svg class="w-4 h-4 text-blue-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            Identificação automática de animais
                        </li>
                        <li class="p-2 bg-gray-50 rounded flex items-center">
                            <svg class="w-4 h-4 text-blue-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            Controle de acesso automatizado
                        </li>
                        <li class="p-2 bg-gray-50 rounded flex items-center">
                            <svg class="w-4 h-4 text-blue-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            Histórico de movimentação
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Condição Corporal -->
    <div id="modal-bcs" class="modal">
        <div class="modal-content">
            <div class="flex items-center justify-between mb-4 pb-4 border-b border-gray-200">
                <h2 class="text-xl font-bold text-gray-900">Condição Corporal</h2>
                <button onclick="closeModal('bcs')" class="w-8 h-8 flex items-center justify-center hover:bg-gray-100 rounded-lg transition-colors">
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="text-gray-700">
                <div class="mb-4">
                    <h3 class="font-semibold text-lg mb-2">Avaliações BCS Recentes</h3>
                    <div class="space-y-2">
                        <div class="p-3 bg-gray-50 rounded-lg">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 bg-green-500 rounded-lg flex items-center justify-center">
                                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-medium">Luna (V002)</p>
                                        <p class="text-sm text-gray-600">BCS: 3.5 - Avaliação: 10/01/2025</p>
                                    </div>
                                </div>
                                <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">Ideal</span>
                            </div>
                        </div>
                        <div class="p-3 bg-gray-50 rounded-lg">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 bg-yellow-500 rounded-lg flex items-center justify-center">
                                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-medium">Estrela (V004)</p>
                                        <p class="text-sm text-gray-600">BCS: 2.8 - Avaliação: 10/01/2025</p>
                                    </div>
                                </div>
                                <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded-full">Atenção</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mb-4">
                    <h3 class="font-semibold text-lg mb-2">Escala BCS</h3>
                    <div class="grid grid-cols-5 gap-2">
                        <div class="text-center p-2 bg-red-50 rounded">
                            <p class="text-lg font-bold text-red-600">1</p>
                            <p class="text-xs text-gray-600">Muito Magra</p>
                        </div>
                        <div class="text-center p-2 bg-orange-50 rounded">
                            <p class="text-lg font-bold text-orange-600">2</p>
                            <p class="text-xs text-gray-600">Magra</p>
                        </div>
                        <div class="text-center p-2 bg-green-50 rounded">
                            <p class="text-lg font-bold text-green-600">3</p>
                            <p class="text-xs text-gray-600">Ideal</p>
                        </div>
                        <div class="text-center p-2 bg-yellow-50 rounded">
                            <p class="text-lg font-bold text-yellow-600">4</p>
                            <p class="text-xs text-gray-600">Gorda</p>
                        </div>
                        <div class="text-center p-2 bg-red-50 rounded">
                            <p class="text-lg font-bold text-red-600">5</p>
                            <p class="text-xs text-gray-600">Muito Gorda</p>
                        </div>
                    </div>
                </div>
                
                <div class="mb-4">
                    <h3 class="font-semibold text-lg mb-2">Funcionalidades</h3>
                    <ul class="space-y-2">
                        <li class="p-2 bg-gray-50 rounded flex items-center">
                            <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            Registro de avaliações BCS
                        </li>
                        <li class="p-2 bg-gray-50 rounded flex items-center">
                            <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            Histórico de condição corporal
                        </li>
                        <li class="p-2 bg-gray-50 rounded flex items-center">
                            <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            Recomendações nutricionais
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Grupos e Lotes -->
    <div id="modal-groups" class="modal">
        <div class="modal-content">
            <div class="flex items-center justify-between mb-4 pb-4 border-b border-gray-200">
                <h2 class="text-xl font-bold text-gray-900">Grupos e Lotes</h2>
                <button onclick="closeModal('groups')" class="w-8 h-8 flex items-center justify-center hover:bg-gray-100 rounded-lg transition-colors">
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="text-gray-700">
                <div class="mb-4">
                    <h3 class="font-semibold text-lg mb-2">Grupos Ativos</h3>
                    <div class="space-y-2">
                        <div class="p-3 bg-gray-50 rounded-lg">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 bg-blue-500 rounded-lg flex items-center justify-center">
                                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M16 4c0-1.11.89-2 2-2s2 .89 2 2-.89 2-2 2-2-.89-2-2zm4 18v-6h2.5l-2.54-7.63A1.5 1.5 0 0 0 18.54 8H16c-.8 0-1.54.37-2.01.99L12 11l-1.99-2.01A2.5 2.5 0 0 0 8 8H5.46c-.8 0-1.54.37-2.01.99L1 15.5V22h2v-6h2.5l2.54-7.63A1.5 1.5 0 0 1 9.46 8H12c.8 0 1.54.37 2.01.99L16 11l1.99-2.01A2.5 2.5 0 0 1 20 8h2.5l-2.54 7.63A1.5 1.5 0 0 1 18.54 16H16v6h4z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-medium">Grupo Produção</p>
                                        <p class="text-sm text-gray-600">8 animais - Lactação ativa</p>
                                    </div>
                                </div>
                                <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">Ativo</span>
                            </div>
                        </div>
                        <div class="p-3 bg-gray-50 rounded-lg">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 bg-orange-500 rounded-lg flex items-center justify-center">
                                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M16 4c0-1.11.89-2 2-2s2 .89 2 2-.89 2-2 2-2-.89-2-2zm4 18v-6h2.5l-2.54-7.63A1.5 1.5 0 0 0 18.54 8H16c-.8 0-1.54.37-2.01.99L12 11l-1.99-2.01A2.5 2.5 0 0 0 8 8H5.46c-.8 0-1.54.37-2.01.99L1 15.5V22h2v-6h2.5l2.54-7.63A1.5 1.5 0 0 1 9.46 8H12c.8 0 1.54.37 2.01.99L16 11l1.99-2.01A2.5 2.5 0 0 1 20 8h2.5l-2.54 7.63A1.5 1.5 0 0 1 18.54 16H16v6h4z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-medium">Grupo Secas</p>
                                        <p class="text-sm text-gray-600">3 animais - Período seco</p>
                                    </div>
                                </div>
                                <span class="px-2 py-1 bg-orange-100 text-orange-800 text-xs rounded-full">Secas</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mb-4">
                    <h3 class="font-semibold text-lg mb-2">Funcionalidades</h3>
                    <ul class="space-y-2">
                        <li class="p-2 bg-gray-50 rounded flex items-center">
                            <svg class="w-4 h-4 text-blue-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            Criação e gestão de grupos
                        </li>
                        <li class="p-2 bg-gray-50 rounded flex items-center">
                            <svg class="w-4 h-4 text-blue-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            Organização por lotes
                        </li>
                        <li class="p-2 bg-gray-50 rounded flex items-center">
                            <svg class="w-4 h-4 text-blue-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            Movimentação entre grupos
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Insights de IA -->
    <div id="modal-ai" class="modal">
        <div class="modal-content">
            <div class="flex items-center justify-between mb-4 pb-4 border-b border-gray-200">
                <h2 class="text-xl font-bold text-gray-900">Insights de IA</h2>
                <button onclick="closeModal('ai')" class="w-8 h-8 flex items-center justify-center hover:bg-gray-100 rounded-lg transition-colors">
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="text-gray-700">
                <div class="mb-4">
                    <h3 class="font-semibold text-lg mb-2">Previsões IA</h3>
                    <div class="space-y-3">
                        <div class="p-4 bg-blue-50 rounded-lg border-l-4 border-blue-400">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-medium text-blue-800">Produção Prevista</p>
                                    <p class="text-sm text-blue-600">Próximos 30 dias: 17.430L</p>
                                </div>
                                <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">+5%</span>
                            </div>
                        </div>
                        <div class="p-4 bg-green-50 rounded-lg border-l-4 border-green-400">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-medium text-green-800">Detecção de Anomalia</p>
                                    <p class="text-sm text-green-600">Estrela (V004) - Produção abaixo da média</p>
                                </div>
                                <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">Monitorar</span>
                            </div>
                        </div>
                        <div class="p-4 bg-purple-50 rounded-lg border-l-4 border-purple-400">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-medium text-purple-800">Recomendação IA</p>
                                    <p class="text-sm text-purple-600">Ajustar alimentação do Grupo Produção</p>
                                </div>
                                <span class="px-2 py-1 bg-purple-100 text-purple-800 text-xs rounded-full">Sugestão</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mb-4">
                    <h3 class="font-semibold text-lg mb-2">Funcionalidades IA</h3>
                    <ul class="space-y-2">
                        <li class="p-2 bg-gray-50 rounded flex items-center">
                            <svg class="w-4 h-4 text-purple-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            Previsão de produção com IA
                        </li>
                        <li class="p-2 bg-gray-50 rounded flex items-center">
                            <svg class="w-4 h-4 text-purple-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            Detecção automática de anomalias
                        </li>
                        <li class="p-2 bg-gray-50 rounded flex items-center">
                            <svg class="w-4 h-4 text-purple-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            Recomendações inteligentes
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Suporte -->
    <div id="modal-support" class="modal">
        <div class="modal-content">
            <div class="flex items-center justify-between mb-4 pb-4 border-b border-gray-200">
                <h2 class="text-xl font-bold text-gray-900">Suporte</h2>
                <button onclick="closeModal('support')" class="w-8 h-8 flex items-center justify-center hover:bg-gray-100 rounded-lg transition-colors">
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="text-gray-700">
                <div class="mb-4">
                    <h3 class="font-semibold text-lg mb-2">Contato Suporte</h3>
                    <div class="space-y-3">
                        <div class="p-4 bg-blue-50 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-medium">Email</p>
                                    <p class="text-sm text-gray-600">suporte@lactech.com.br</p>
                                </div>
                            </div>
                        </div>
                        <div class="p-4 bg-green-50 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-medium">Telefone</p>
                                    <p class="text-sm text-gray-600">(11) 98765-4321</p>
                                </div>
                            </div>
                        </div>
                        <div class="p-4 bg-orange-50 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-orange-500 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-medium">Horário</p>
                                    <p class="text-sm text-gray-600">Seg-Sex, 8h às 18h</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mb-4">
                    <h3 class="font-semibold text-lg mb-2">Recursos de Ajuda</h3>
                    <ul class="space-y-2">
                        <li class="p-2 bg-gray-50 rounded flex items-center">
                            <svg class="w-4 h-4 text-blue-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            Central de ajuda online
                        </li>
                        <li class="p-2 bg-gray-50 rounded flex items-center">
                            <svg class="w-4 h-4 text-blue-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            Tutoriais em vídeo
                        </li>
                        <li class="p-2 bg-gray-50 rounded flex items-center">
                            <svg class="w-4 h-4 text-blue-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            Suporte técnico especializado
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Alimentação -->
    <div id="modal-feeding" class="modal">
        <div class="modal-content">
            <div class="flex items-center justify-between mb-4 pb-4 border-b border-gray-200">
                <h2 class="text-xl font-bold text-gray-900">Alimentação</h2>
                <button onclick="closeModal('feeding')" class="w-8 h-8 flex items-center justify-center hover:bg-gray-100 rounded-lg transition-colors">
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="text-gray-700">
                <div class="mb-4">
                    <h3 class="font-semibold text-lg mb-2">Controle de Alimentação</h3>
                    <div class="space-y-2">
                        <div class="p-3 bg-gray-50 rounded-lg">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 bg-green-500 rounded-lg flex items-center justify-center">
                                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-medium">Concentrado</p>
                                        <p class="text-sm text-gray-600">Estoque: 2.500kg - Custo: R$ 1.250</p>
                                    </div>
                                </div>
                                <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">Estoque OK</span>
                            </div>
                        </div>
                        <div class="p-3 bg-gray-50 rounded-lg">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 bg-yellow-500 rounded-lg flex items-center justify-center">
                                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-medium">Ração</p>
                                        <p class="text-sm text-gray-600">Estoque: 1.200kg - Custo: R$ 600</p>
                                    </div>
                                </div>
                                <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded-full">Baixo</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mb-4">
                    <h3 class="font-semibold text-lg mb-2">Consumo Diário</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="text-center p-3 bg-green-50 rounded-lg">
                            <p class="text-xl font-bold text-green-600">50kg</p>
                            <p class="text-sm text-gray-600">Concentrado</p>
                        </div>
                        <div class="text-center p-3 bg-blue-50 rounded-lg">
                            <p class="text-xl font-bold text-blue-600">30kg</p>
                            <p class="text-sm text-gray-600">Ração</p>
                        </div>
                    </div>
                </div>
                
                <div class="mb-4">
                    <h3 class="font-semibold text-lg mb-2">Funcionalidades</h3>
                    <ul class="space-y-2">
                        <li class="p-2 bg-gray-50 rounded flex items-center">
                            <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            Registro de alimentação diária
                        </li>
                        <li class="p-2 bg-gray-50 rounded flex items-center">
                            <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            Controle de estoque
                        </li>
                        <li class="p-2 bg-gray-50 rounded flex items-center">
                            <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            Cálculo de custos
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Sistema de Touros -->
    <div id="modal-bulls" class="modal">
        <div class="modal-content">
            <div class="flex items-center justify-between mb-4 pb-4 border-b border-gray-200">
                <h2 class="text-xl font-bold text-gray-900">Sistema de Touros</h2>
                <button onclick="closeModal('bulls')" class="w-8 h-8 flex items-center justify-center hover:bg-gray-100 rounded-lg transition-colors">
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="text-gray-700">
                <div class="mb-4">
                    <h3 class="font-semibold text-lg mb-2">Touros Cadastrados</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div class="p-3 bg-gray-50 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-red-600 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M19.5 6c-1.3 0-2.5.8-3 2-.5-1.2-1.7-2-3-2s-2.5.8-3 2c-.5-1.2-1.7-2-3-2C5.5 6 4 7.5 4 9.5c0 1.3.7 2.4 1.7 3.1-.4.6-.7 1.3-.7 2.1 0 2.2 1.8 4 4 4h6c2.2 0 4-1.8 4-4 0-.8-.3-1.5-.7-2.1 1-.7 1.7-1.8 1.7-3.1 0-2-1.5-3.5-3.5-3.5z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-medium">Touro Elite (B001)</p>
                                    <p class="text-sm text-gray-600">Holandês - Ativo</p>
                                </div>
                            </div>
                        </div>
                        <div class="p-3 bg-gray-50 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-red-600 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M19.5 6c-1.3 0-2.5.8-3 2-.5-1.2-1.7-2-3-2s-2.5.8-3 2c-.5-1.2-1.7-2-3-2C5.5 6 4 7.5 4 9.5c0 1.3.7 2.4 1.7 3.1-.4.6-.7 1.3-.7 2.1 0 2.2 1.8 4 4 4h6c2.2 0 4-1.8 4-4 0-.8-.3-1.5-.7-2.1 1-.7 1.7-1.8 1.7-3.1 0-2-1.5-3.5-3.5-3.5z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-medium">Francisco (223)</p>
                                    <p class="text-sm text-gray-600">Girolando - Ativo</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mb-4">
                    <h3 class="font-semibold text-lg mb-2">Estatísticas</h3>
                    <div class="grid grid-cols-3 gap-4">
                        <div class="text-center p-3 bg-blue-50 rounded-lg">
                            <p class="text-2xl font-bold text-blue-600">5</p>
                            <p class="text-sm text-gray-600">Total de Touros</p>
                        </div>
                        <div class="text-center p-3 bg-green-50 rounded-lg">
                            <p class="text-2xl font-bold text-green-600">25</p>
                            <p class="text-sm text-gray-600">Inseminações</p>
                        </div>
                        <div class="text-center p-3 bg-orange-50 rounded-lg">
                            <p class="text-2xl font-bold text-orange-600">72%</p>
                            <p class="text-sm text-gray-600">Taxa de Prenhez</p>
                        </div>
                    </div>
                </div>
                
                <div class="mb-4">
                    <h3 class="font-semibold text-lg mb-2">Funcionalidades</h3>
                    <ul class="space-y-2">
                        <li class="p-2 bg-gray-50 rounded flex items-center">
                            <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            Cadastro de touros com dados genéticos
                        </li>
                        <li class="p-2 bg-gray-50 rounded flex items-center">
                            <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            Controle de performance reprodutiva
                        </li>
                        <li class="p-2 bg-gray-50 rounded flex items-center">
                            <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            Gestão de sêmen e catálogo
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Controle de Novilhas -->
    <div id="modal-heifers" class="modal">
        <div class="modal-content">
            <div class="flex items-center justify-between mb-6 pb-4 border-b-2 border-gray-200 px-4">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-fuchsia-500 to-fuchsia-600 rounded-xl flex items-center justify-center shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">Controle de Novilhas</h2>
                        <p class="text-sm text-gray-600">Gestão de custos do nascimento aos 26 meses</p>
                    </div>
                </div>
                <button onclick="closeSubModal('heifers')" class="w-10 h-10 flex items-center justify-center hover:bg-gray-100 rounded-xl transition-colors">
                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Dashboard de Estatísticas -->
            <div class="px-6 pb-6 overflow-y-auto max-h-[calc(100vh-200px)]">
                <!-- Cards de Estatísticas -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-4 border border-blue-200">
                        <div class="text-2xl font-bold text-blue-600 mb-1" id="heifer-total-count">0</div>
                        <div class="text-xs text-blue-700 font-medium">Total Novilhas</div>
                    </div>
                    <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-4 border border-green-200">
                        <div class="text-2xl font-bold text-green-600 mb-1" id="heifer-total-cost">R$ 0</div>
                        <div class="text-xs text-green-700 font-medium">Investimento Total</div>
                    </div>
                    <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl p-4 border border-purple-200">
                        <div class="text-2xl font-bold text-purple-600 mb-1" id="heifer-avg-cost">R$ 0</div>
                        <div class="text-xs text-purple-700 font-medium">Custo Médio/Animal</div>
                    </div>
                    <div class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-xl p-4 border border-orange-200">
                        <div class="text-2xl font-bold text-orange-600 mb-1" id="heifer-avg-monthly">R$ 0</div>
                        <div class="text-xs text-orange-700 font-medium">Custo Médio/Mês</div>
                    </div>
                </div>

                <!-- Fases por Quantidade -->
                <div class="bg-white rounded-xl p-4 border border-gray-200 mb-6">
                    <h3 class="font-bold text-lg text-gray-900 mb-4">Distribuição por Fase</h3>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3" id="heifer-phases-stats">
                        <!-- Preenchido via JavaScript -->
                    </div>
                </div>

                <!-- Botões de Ação -->
                <div class="flex flex-wrap gap-3 mb-6">
                    <button onclick="openHeiferCostForm()" class="flex items-center space-x-2 px-4 py-3 bg-gradient-to-r from-fuchsia-500 to-fuchsia-600 text-white rounded-xl hover:from-fuchsia-600 hover:to-fuchsia-700 transition-all shadow-md">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span>Registrar Custo</span>
                    </button>
                    <button onclick="openHeiferDailyConsumptionForm()" class="flex items-center space-x-2 px-4 py-3 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-xl hover:from-green-600 hover:to-green-700 transition-all shadow-md">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <span>Registrar Consumo Diário</span>
                    </button>
                    <button onclick="loadHeiferReports()" class="flex items-center space-x-2 px-4 py-3 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl hover:from-blue-600 hover:to-blue-700 transition-all shadow-md">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <span>Relatórios</span>
                    </button>
                </div>

                <!-- Lista de Novilhas -->
                <div class="bg-white rounded-xl border border-gray-200 p-4">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-bold text-lg text-gray-900">Novilhas Cadastradas</h3>
                        <div class="flex items-center space-x-2">
                            <input type="text" id="heifer-search" placeholder="Buscar novilha..." class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-fuchsia-500 focus:border-transparent">
                        </div>
                    </div>
                    <div id="heifers-list" class="space-y-3">
                        <!-- Lista preenchida via JavaScript -->
                        <div class="text-center py-8 text-gray-500">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-fuchsia-600 mx-auto mb-2"></div>
                            <p>Carregando novilhas...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Registrar Custo de Novilha -->
    <div id="modal-heifer-cost" class="modal">
        <div class="modal-content">
            <div class="flex items-center justify-between mb-6 pb-4 border-b-2 border-gray-200 px-4">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-fuchsia-500 to-fuchsia-600 rounded-xl flex items-center justify-center shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900">Registrar Custo de Novilha</h2>
                </div>
                <button onclick="closeSubModal('heifer-cost')" class="w-10 h-10 flex items-center justify-center hover:bg-gray-100 rounded-xl transition-colors">
                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <form id="heiferCostForm" class="px-6 pb-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Novilha</label>
                        <select name="animal_id" id="heifer-cost-animal" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-fuchsia-500 focus:border-transparent">
                            <option value="">Selecione a novilha</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Data do Custo</label>
                        <input type="date" name="cost_date" required value="<?php echo date('Y-m-d'); ?>" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-fuchsia-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Categoria</label>
                        <select name="cost_category" id="heifer-cost-category" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-fuchsia-500 focus:border-transparent">
                            <option value="">Selecione a categoria</option>
                            <option value="Alimentação">Alimentação</option>
                            <option value="Medicamentos">Medicamentos</option>
                            <option value="Vacinas">Vacinas</option>
                            <option value="Manejo">Manejo</option>
                            <option value="Transporte">Transporte</option>
                            <option value="Outros">Outros</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Alimento/Item</label>
                        <select name="category_id" id="heifer-cost-item-type" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-fuchsia-500 focus:border-transparent">
                            <option value="">Selecione o tipo</option>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Apenas para categoria Alimentação</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Quantidade</label>
                        <input type="number" name="quantity" id="heifer-cost-quantity" step="0.001" min="0" required value="1" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-fuchsia-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Unidade</label>
                        <select name="unit" id="heifer-cost-unit" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-fuchsia-500 focus:border-transparent">
                            <option value="Litros">Litros</option>
                            <option value="Kg">Kg</option>
                            <option value="Dias">Dias</option>
                            <option value="Unidade">Unidade</option>
                            <option value="Hora">Hora</option>
                            <option value="Mês">Mês</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Preço Unitário (R$)</label>
                        <input type="number" name="unit_price" id="heifer-cost-unit-price" step="0.01" min="0" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-fuchsia-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Total (R$)</label>
                        <input type="number" name="cost_amount" id="heifer-cost-total" step="0.01" min="0" readonly class="w-full px-4 py-3 border border-gray-300 rounded-xl bg-gray-50">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Descrição</label>
                    <textarea name="description" rows="3" required placeholder="Ex: Leite sucedâneo diário - 6 litros por dia durante fase de aleitamento" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-fuchsia-500 focus:border-transparent"></textarea>
                </div>
                <div id="heifer-cost-message" class="hidden"></div>
                <div class="flex justify-end space-x-4">
                    <button type="button" onclick="closeSubModal('heifer-cost')" class="px-6 py-3 border border-gray-300 rounded-xl text-gray-700 hover:bg-gray-50 transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" class="px-6 py-3 bg-gradient-to-r from-fuchsia-500 to-fuchsia-600 text-white rounded-xl hover:from-fuchsia-600 hover:to-fuchsia-700 transition-all shadow-md">
                        Registrar Custo
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Registrar Consumo Diário -->
    <div id="modal-heifer-consumption" class="modal">
        <div class="modal-content">
            <div class="flex items-center justify-between mb-6 pb-4 border-b-2 border-gray-200 px-4">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-green-600 rounded-xl flex items-center justify-center shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900">Registrar Consumo Diário</h2>
                </div>
                <button onclick="closeSubModal('heifer-consumption')" class="w-10 h-10 flex items-center justify-center hover:bg-gray-100 rounded-xl transition-colors">
                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <form id="heiferConsumptionForm" class="px-6 pb-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Novilha</label>
                        <select name="animal_id" id="heifer-consumption-animal" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            <option value="">Selecione a novilha</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Data</label>
                        <input type="date" name="consumption_date" required value="<?php echo date('Y-m-d'); ?>" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Leite/Sucedâneo (Litros)</label>
                        <input type="number" name="milk_liters" step="0.01" min="0" value="0" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Concentrado (kg)</label>
                        <input type="number" name="concentrate_kg" step="0.01" min="0" value="0" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Volumoso (kg)</label>
                        <input type="number" name="roughage_kg" step="0.01" min="0" value="0" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Peso Atual (kg)</label>
                        <input type="number" name="weight_kg" step="0.01" min="0" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Observações</label>
                    <textarea name="notes" rows="3" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent"></textarea>
                </div>
                <div id="heifer-consumption-message" class="hidden"></div>
                <div class="flex justify-end space-x-4">
                    <button type="button" onclick="closeSubModal('heifer-consumption')" class="px-6 py-3 border border-gray-300 rounded-xl text-gray-700 hover:bg-gray-50 transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" class="px-6 py-3 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-xl hover:from-green-600 hover:to-green-700 transition-all shadow-md">
                        Registrar Consumo
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Detalhes da Novilha -->
    <div id="modal-heifer-details" class="modal">
        <div class="modal-content" style="max-width: 900px; max-height: 90vh; overflow-y: auto;">
            <div class="flex items-center justify-between mb-6 pb-4 border-b-2 border-gray-200 px-4">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900" id="heifer-details-title">Detalhes da Novilha</h2>
                        <p class="text-sm text-gray-500" id="heifer-details-subtitle">Informações completas</p>
                    </div>
                </div>
                <button onclick="closeSubModal('heifer-details')" class="w-10 h-10 flex items-center justify-center hover:bg-gray-100 rounded-xl transition-colors">
                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <div class="px-6 pb-6 space-y-6">
                <!-- Informações Básicas -->
                <div class="bg-gray-50 rounded-xl p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Informações Básicas</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-600 mb-1">Nome</p>
                            <p class="font-semibold text-gray-900" id="heifer-detail-name">-</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600 mb-1">Número/Ear Tag</p>
                            <p class="font-semibold text-gray-900" id="heifer-detail-ear-tag">-</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600 mb-1">Data de Nascimento</p>
                            <p class="font-semibold text-gray-900" id="heifer-detail-birth-date">-</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600 mb-1">Idade</p>
                            <p class="font-semibold text-gray-900" id="heifer-detail-age">-</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600 mb-1">Fase Atual</p>
                            <p class="font-semibold text-gray-900" id="heifer-detail-phase">-</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600 mb-1">Status</p>
                            <p class="font-semibold text-gray-900" id="heifer-detail-status">-</p>
                        </div>
                    </div>
                </div>

                <!-- Resumo de Custos -->
                <div class="bg-gradient-to-r from-green-50 to-green-100 rounded-xl p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Resumo de Custos</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-white rounded-lg p-4">
                            <p class="text-sm text-gray-600 mb-1">Custo Total</p>
                            <p class="text-2xl font-bold text-green-600" id="heifer-detail-total-cost">R$ 0,00</p>
                        </div>
                        <div class="bg-white rounded-lg p-4">
                            <p class="text-sm text-gray-600 mb-1">Registros</p>
                            <p class="text-2xl font-bold text-blue-600" id="heifer-detail-total-records">0</p>
                        </div>
                        <div class="bg-white rounded-lg p-4">
                            <p class="text-sm text-gray-600 mb-1">Custo Médio/Dia</p>
                            <p class="text-2xl font-bold text-purple-600" id="heifer-detail-avg-daily">R$ 0,00</p>
                        </div>
                    </div>
                </div>

                <!-- Custos por Categoria -->
                <div>
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Custos por Categoria</h3>
                    <div id="heifer-detail-categories" class="space-y-2">
                        <!-- Será preenchido via JavaScript -->
                    </div>
                </div>

                <!-- Custos por Fase -->
                <div>
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Custos por Fase</h3>
                    <div id="heifer-detail-phases" class="space-y-2">
                        <!-- Será preenchido via JavaScript -->
                    </div>
                </div>

                <!-- Últimos Registros -->
                <div>
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Últimos Registros de Custos</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Categoria</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Valor</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Descrição</th>
                                </tr>
                            </thead>
                            <tbody id="heifer-detail-recent-costs" class="bg-white divide-y divide-gray-200">
                                <tr>
                                    <td colspan="4" class="px-4 py-8 text-center text-gray-500">Carregando...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Adicionar Animal -->
    <div id="modal-add-animal" class="modal">
        <div class="modal-content" style="max-width: 800px; max-height: 90vh; overflow-y: auto;">
            <div class="flex items-center justify-between mb-6 pb-4 border-b-2 border-gray-200 px-4">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-green-600 rounded-xl flex items-center justify-center shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">Adicionar Novo Animal</h2>
                        <p class="text-sm text-gray-500">Cadastre um novo animal no rebanho</p>
                    </div>
                </div>
                <button onclick="closeSubModal('add-animal')" class="w-10 h-10 flex items-center justify-center hover:bg-gray-100 rounded-xl transition-colors">
                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <form id="addAnimalForm" class="px-6 pb-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Número do Animal *</label>
                        <input type="text" name="animal_number" id="animal-number" required 
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent"
                               placeholder="Ex: 001, 002, etc">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nome</label>
                        <input type="text" name="name" id="animal-name" 
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent"
                               placeholder="Nome do animal">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Raça *</label>
                        <select name="breed" id="animal-breed" required 
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            <option value="">Selecione a raça</option>
                            <option value="Holandesa">Holandesa</option>
                            <option value="Gir">Gir</option>
                            <option value="Girolando">Girolando</option>
                            <option value="Jersey">Jersey</option>
                            <option value="Guernsey">Guernsey</option>
                            <option value="Pardo Suíço">Pardo Suíço</option>
                            <option value="Outra">Outra</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Sexo *</label>
                        <select name="gender" id="animal-gender" required 
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            <option value="">Selecione o sexo</option>
                            <option value="femea">Fêmea</option>
                            <option value="macho">Macho</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Data de Nascimento *</label>
                        <input type="date" name="birth_date" id="animal-birth-date" required 
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent"
                               max="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status *</label>
                        <select name="status" id="animal-status" required 
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            <option value="">Selecione o status</option>
                            <option value="Lactante">Lactante</option>
                            <option value="Seco">Seco</option>
                            <option value="Prenha">Prenha</option>
                            <option value="Novilha">Novilha</option>
                            <option value="Bezerra">Bezerra</option>
                            <option value="Bezerro">Bezerro</option>
                            <option value="Touro">Touro</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Peso (kg)</label>
                        <input type="number" step="0.01" name="weight" id="animal-weight" min="0" 
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent"
                               placeholder="Peso atual">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Código RFID</label>
                        <input type="text" name="rfid_code" id="animal-rfid" 
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent"
                               placeholder="Código do transponder">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Pai (Sire)</label>
                    <input type="text" name="sire" id="animal-sire" 
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent"
                           placeholder="Nome ou código do pai">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Mãe (Dam)</label>
                    <input type="text" name="dam" id="animal-dam" 
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent"
                           placeholder="Nome ou código da mãe">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Observações</label>
                    <textarea name="notes" id="animal-notes" rows="3" 
                              class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent"
                              placeholder="Observações sobre o animal"></textarea>
                </div>
                
                <div id="add-animal-message" class="hidden"></div>
                
                <div class="flex justify-end space-x-4">
                    <button type="button" onclick="closeSubModal('add-animal')" class="px-6 py-3 border border-gray-300 rounded-xl text-gray-700 hover:bg-gray-50 transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" class="px-6 py-3 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-xl hover:from-green-600 hover:to-green-700 transition-all shadow-md">
                        Cadastrar Animal
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Formulário de Registro de Saúde -->
    <div id="healthFormModal" class="modal">
        <div class="modal-content">
            <div class="flex items-center justify-between mb-6 pb-4 border-b-2 border-gray-200">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900">Registrar Cuidado de Saúde</h2>
                </div>
                <button onclick="closeFormModal('healthFormModal')" class="w-10 h-10 flex items-center justify-center hover:bg-gray-100 rounded-xl transition-colors">
                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <form id="healthForm" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Animal</label>
                        <select name="animal_id" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Selecione o animal</option>
                            <?php foreach($animals as $animal): ?>
                            <option value="<?php echo $animal['id']; ?>"><?php echo htmlspecialchars($animal['name'] ?? 'Sem nome'); ?> (<?php echo htmlspecialchars($animal['animal_number']); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Cuidado</label>
                        <select name="care_type" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Selecione o tipo</option>
                            <option value="vacina">Vacinação</option>
                            <option value="vermifugacao">Vermifugação</option>
                            <option value="medicamento">Medicamento</option>
                            <option value="exame">Exame</option>
                            <option value="cirurgia">Cirurgia</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Data do Cuidado</label>
                        <input type="date" name="care_date" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Produto/Medicamento</label>
                        <input type="text" name="product" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Dosagem</label>
                        <input type="text" name="dosage" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Próxima Aplicação</label>
                        <input type="date" name="next_date" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Observações</label>
                    <textarea name="notes" rows="3" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                </div>
                <div class="flex justify-end space-x-4">
                    <button type="button" onclick="closeFormModal('healthFormModal')" class="px-6 py-3 border border-gray-300 rounded-xl text-gray-700 hover:bg-gray-50 transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" class="px-6 py-3 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors">
                        Registrar Cuidado
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Formulário de Registro de Reprodução -->
    <div id="reproductionFormModal" class="modal">
        <div class="modal-content">
            <div class="flex items-center justify-between mb-6 pb-4 border-b-2 border-gray-200">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-pink-500 to-pink-600 rounded-xl flex items-center justify-center shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900">Registrar Reprodução</h2>
                </div>
                <button onclick="closeFormModal('reproductionFormModal')" class="w-10 h-10 flex items-center justify-center hover:bg-gray-100 rounded-xl transition-colors">
                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <form id="reproductionForm" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Animal</label>
                        <select name="animal_id" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                            <option value="">Selecione o animal</option>
                            <?php foreach($animals as $animal): ?>
                                <?php if($animal['gender'] === 'femea'): ?>
                                <option value="<?php echo $animal['id']; ?>"><?php echo htmlspecialchars($animal['name'] ?? 'Sem nome'); ?> (<?php echo htmlspecialchars($animal['animal_number']); ?>)</option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Evento</label>
                        <select name="event_type" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                            <option value="">Selecione o tipo</option>
                            <option value="cio">Cio</option>
                            <option value="inseminacao">Inseminação</option>
                            <option value="cobertura">Cobertura Natural</option>
                            <option value="diagnostico">Diagnóstico de Gestação</option>
                            <option value="parto">Parto</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Data do Evento</label>
                        <input type="date" name="event_date" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Touro/Reprodutor</label>
                        <input type="text" name="bull" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Método</label>
                        <select name="method" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                            <option value="">Selecione o método</option>
                            <option value="ia">Inseminação Artificial</option>
                            <option value="monta">Monta Natural</option>
                            <option value="fiv">FIV</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Resultado</label>
                        <select name="result" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                            <option value="">Selecione o resultado</option>
                            <option value="prenha">Prenha</option>
                            <option value="vazia">Vazia</option>
                            <option value="pendente">Pendente</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Observações</label>
                    <textarea name="notes" rows="3" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-pink-500 focus:border-transparent"></textarea>
                </div>
                <div class="flex justify-end space-x-4">
                    <button type="button" onclick="closeFormModal('reproductionFormModal')" class="px-6 py-3 border border-gray-300 rounded-xl text-gray-700 hover:bg-gray-50 transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" class="px-6 py-3 bg-pink-600 text-white rounded-xl hover:bg-pink-700 transition-colors">
                        Registrar Reprodução
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Formulário de Cadastro RFID -->
    <div id="rfidFormModal" class="modal">
        <div class="modal-content">
            <div class="flex items-center justify-between mb-6 pb-4 border-b-2 border-gray-200">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl flex items-center justify-center shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900">Cadastrar Transponder RFID</h2>
                </div>
                <button onclick="closeFormModal('rfidFormModal')" class="w-10 h-10 flex items-center justify-center hover:bg-gray-100 rounded-xl transition-colors">
                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <form id="rfidForm" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Animal</label>
                        <select name="animal_id" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                            <option value="">Selecione o animal</option>
                            <?php foreach($animals as $animal): ?>
                            <option value="<?php echo $animal['id']; ?>"><?php echo htmlspecialchars($animal['name'] ?? 'Sem nome'); ?> (<?php echo htmlspecialchars($animal['animal_number']); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Número do Transponder</label>
                        <input type="text" name="transponder_number" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Transponder</label>
                        <select name="transponder_type" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                            <option value="">Selecione o tipo</option>
                            <option value="bolus">Bolus</option>
                            <option value="brinco">Brinco</option>
                            <option value="injetavel">Injetável</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Data de Instalação</label>
                        <input type="date" name="installation_date" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select name="status" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                            <option value="">Selecione o status</option>
                            <option value="ativo">Ativo</option>
                            <option value="inativo">Inativo</option>
                            <option value="perdido">Perdido</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Localização</label>
                        <input type="text" name="location" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Observações</label>
                    <textarea name="notes" rows="3" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-transparent"></textarea>
                </div>
                <div class="flex justify-end space-x-4">
                    <button type="button" onclick="closeFormModal('rfidFormModal')" class="px-6 py-3 border border-gray-300 rounded-xl text-gray-700 hover:bg-gray-50 transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" class="px-6 py-3 bg-orange-600 text-white rounded-xl hover:bg-orange-700 transition-colors">
                        Cadastrar RFID
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Formulário de Condição Corporal -->
    <div id="bcsFormModal" class="modal">
        <div class="modal-content">
            <div class="flex items-center justify-between mb-6 pb-4 border-b-2 border-gray-200">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-xl flex items-center justify-center shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900">Registrar Condição Corporal</h2>
                </div>
                <button onclick="closeFormModal('bcsFormModal')" class="w-10 h-10 flex items-center justify-center hover:bg-gray-100 rounded-xl transition-colors">
                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <form id="bcsForm" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Animal</label>
                        <select name="animal_id" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            <option value="">Selecione o animal</option>
                            <?php foreach($animals as $animal): ?>
                            <option value="<?php echo $animal['id']; ?>"><?php echo htmlspecialchars($animal['name'] ?? 'Sem nome'); ?> (<?php echo htmlspecialchars($animal['animal_number']); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Data da Avaliação</label>
                        <input type="date" name="evaluation_date" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Escala BCS (1-5)</label>
                        <select name="bcs_score" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            <option value="">Selecione a escala</option>
                            <option value="1">1 - Muito Magra</option>
                            <option value="2">2 - Magra</option>
                            <option value="3">3 - Ideal</option>
                            <option value="4">4 - Gorda</option>
                            <option value="5">5 - Muito Gorda</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Peso Atual (kg)</label>
                        <input type="number" step="0.1" name="current_weight" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Avaliador</label>
                        <input type="text" name="evaluator" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status Reprodutivo</label>
                        <select name="reproductive_status" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            <option value="">Selecione o status</option>
                            <option value="vazia">Vazia</option>
                            <option value="prenha">Prenha</option>
                            <option value="lactante">Lactante</option>
                            <option value="seca">Seca</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Observações</label>
                    <textarea name="notes" rows="3" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent"></textarea>
                </div>
                <div class="flex justify-end space-x-4">
                    <button type="button" onclick="closeFormModal('bcsFormModal')" class="px-6 py-3 border border-gray-300 rounded-xl text-gray-700 hover:bg-gray-50 transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" class="px-6 py-3 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition-colors">
                        Registrar BCS
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // JavaScript otimizado para carregamento rápido
        (function() {
            'use strict';
            
            let currentModal = null;
            
            // Sistema de cache e navegação otimizada
            const CacheManager = {
                // Salvar estado da página
                savePageState: function() {
                    const pageState = {
                        timestamp: Date.now(),
                        scrollPosition: window.pageYOffset,
                        activeModal: currentModal ? currentModal.id : null,
                        url: window.location.href
                    };
                    localStorage.setItem('lactech_page_state', JSON.stringify(pageState));
                },
                
                // Restaurar estado da página
                restorePageState: function() {
                    const savedState = localStorage.getItem('lactech_page_state');
                    if (savedState) {
                        const state = JSON.parse(savedState);
                        // Verificar se o estado não é muito antigo (5 minutos)
                        if (Date.now() - state.timestamp < 300000) {
                            return state;
                        }
                    }
                    return null;
                },
                
                // Limpar cache
                clearCache: function() {
                    localStorage.removeItem('lactech_page_state');
                }
            };
            
            // Função para voltar ao dashboard sem recarregar
            function goBackToDashboard() {
                // Salvar estado atual
                CacheManager.savePageState();
                
                // Usar history API se disponível
                if (window.history.length > 1) {
                    window.history.back();
                } else {
                    // Fallback para redirecionamento
                    window.location.href = '../gerente-completo.php';
                }
            }
            
            function openModal(modalName) {
                if (currentModal) {
                    currentModal.classList.remove('show');
                }
                
                const modal = document.getElementById('modal-' + modalName);
                if (modal) {
                    modal.classList.add('show');
                    currentModal = modal;
                    document.body.style.overflow = 'hidden';
                }
            }

            function closeModal(modalName) {
                const modal = modalName ? document.getElementById('modal-' + modalName) : currentModal;
                if (modal) {
                    modal.classList.remove('show');
                    currentModal = null;
                    document.body.style.overflow = '';
                }
            }

            // Event listeners otimizados
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('modal')) {
                    closeModal();
                }
            });

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && currentModal) {
                    closeModal();
                }
            });

            // Expor funções globalmente
            window.openModal = openModal;
            window.closeModal = closeModal;
            window.goBackToDashboard = goBackToDashboard;
            
            // Sistema de detecção de cache
            function checkForCachedState() {
                const cachedState = CacheManager.restorePageState();
                if (cachedState) {
                    // Página foi restaurada do cache - esconder preloader imediatamente
                    const preloader = document.getElementById('preloader');
                    if (preloader) {
                        preloader.style.display = 'none';
                    }
                    
                    // Restaurar posição de scroll
                    if (cachedState.scrollPosition) {
                        window.scrollTo(0, cachedState.scrollPosition);
                    }
                    
                    // Restaurar modal ativo se houver
                    if (cachedState.activeModal) {
                        setTimeout(() => {
                            openModal(cachedState.activeModal.replace('modal-', ''));
                        }, 100);
                    }
                    
                    return true;
                }
                return false;
            }
            
            // Esconder preloader quando a página carregar
            window.addEventListener('load', function() {
                // Verificar se há cache primeiro
                if (!checkForCachedState()) {
                    // Não há cache - mostrar preloader normal
                    const preloader = document.getElementById('preloader');
                    if (preloader) {
                        setTimeout(() => {
                            preloader.classList.add('hidden');
                        }, 300);
                    }
                }
            });
            
            // Salvar estado antes de sair da página
            window.addEventListener('beforeunload', function() {
                CacheManager.savePageState();
            });
            
            // Salvar estado periodicamente (a cada 30 segundos)
            setInterval(function() {
                CacheManager.savePageState();
            }, 30000);
        })();

        function openFormModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('show');
                document.body.style.overflow = 'hidden';
            }
        }

        function closeFormModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove('show');
                document.body.style.overflow = '';
                // Limpar formulário
                const form = modal.querySelector('form');
                if (form) {
                    form.reset();
                }
            }
        }

        // Funções para Gestão de Rebanho
        function showPedigreeModal(animalId) {
            // Buscar dados do pedigree do animal
            fetch(`api/animals.php?action=get_pedigree&animal_id=${animalId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data) {
                        // Criar modal de pedigree (simplificado por enquanto)
                        alert('Pedigree do animal ID: ' + animalId + '\n\nEsta funcionalidade será implementada em breve.');
                    } else {
                        alert('Erro ao carregar pedigree do animal.');
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Pedigree do animal ID: ' + animalId + '\n\nEsta funcionalidade será implementada em breve.');
                });
        }

        function editAnimalModal(animalId) {
            // Redirecionar ou abrir modal de edição
            window.location.href = `edit-animal.php?id=${animalId}`;
            // Alternativa: abrir modal de edição
            // alert('Abrindo edição do animal ID: ' + animalId);
        }

        function viewAnimalModal(animalId) {
            // Buscar dados detalhados do animal
            fetch(`api/animals.php?action=get_by_id&id=${animalId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data) {
                        const animal = data.data;
                        // Criar modal de visualização (simplificado por enquanto)
                        const info = `Nome: ${animal.name || 'N/A'}\n` +
                                   `Número: ${animal.animal_number || 'N/A'}\n` +
                                   `Raça: ${animal.breed || 'N/A'}\n` +
                                   `Status: ${animal.status || 'N/A'}\n` +
                                   `Data de Nascimento: ${animal.birth_date || 'N/A'}`;
                        alert('Detalhes do Animal:\n\n' + info);
                    } else {
                        alert('Erro ao carregar dados do animal.');
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Visualizando animal ID: ' + animalId);
                });
        }
        
        // Sistema de busca e filtros para Gestão de Rebanho
        function initAnimalSearchAndFilters() {
            const searchInput = document.getElementById('searchAnimal');
            const filterStatus = document.getElementById('filterStatus');
            const filterBreed = document.getElementById('filterBreed');
            
            if (!searchInput || !filterStatus || !filterBreed) {
                return; // Elementos ainda não carregados
            }
            
            function filterAnimals() {
                const searchTerm = (searchInput.value || '').toLowerCase().trim();
                const statusFilter = filterStatus.value || '';
                const breedFilter = (filterBreed.value || '').toLowerCase();
                
                // Filtrar cards
                const cards = document.querySelectorAll('#animalsListContainer .animal-card');
                cards.forEach(card => {
                    const name = (card.dataset.name || '').toLowerCase();
                    const number = (card.dataset.number || '').toLowerCase();
                    const status = card.dataset.status || '';
                    const breed = (card.dataset.breed || '').toLowerCase();
                    
                    const matchesSearch = !searchTerm || name.includes(searchTerm) || number.includes(searchTerm);
                    const matchesStatus = !statusFilter || status === statusFilter;
                    const matchesBreed = !breedFilter || breed.includes(breedFilter);
                    
                    if (matchesSearch && matchesStatus && matchesBreed) {
                        card.style.display = '';
                    } else {
                        card.style.display = 'none';
                    }
                });
                
                // Filtrar linhas da tabela
                const rows = document.querySelectorAll('.animal-table-row');
                rows.forEach(row => {
                    const name = (row.dataset.name || '').toLowerCase();
                    const number = (row.dataset.number || '').toLowerCase();
                    const status = row.dataset.status || '';
                    const breed = (row.dataset.breed || '').toLowerCase();
                    
                    const matchesSearch = !searchTerm || name.includes(searchTerm) || number.includes(searchTerm);
                    const matchesStatus = !statusFilter || status === statusFilter;
                    const matchesBreed = !breedFilter || breed.includes(breedFilter);
                    
                    if (matchesSearch && matchesStatus && matchesBreed) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }
            
            // Adicionar event listeners
            searchInput.addEventListener('input', filterAnimals);
            filterStatus.addEventListener('change', filterAnimals);
            filterBreed.addEventListener('change', filterAnimals);
        }
        
        // Inicializar busca e filtros quando o modal for aberto
        document.addEventListener('DOMContentLoaded', function() {
            // Tentar inicializar imediatamente
            setTimeout(initAnimalSearchAndFilters, 100);
            
            // Observar quando o modal de animais for aberto
            const animalsModal = document.getElementById('modal-animals');
            if (animalsModal) {
                // Usar MutationObserver para detectar quando o modal é mostrado
                const observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                            const isVisible = animalsModal.classList.contains('show');
                            if (isVisible) {
                                setTimeout(initAnimalSearchAndFilters, 100);
                            }
                        }
                    });
                });
                
                observer.observe(animalsModal, {
                    attributes: true,
                    attributeFilter: ['class']
                });
            }
        });

        // Funções para outros modais
        function openHealthForm() {
            openFormModal('healthFormModal');
        }

        function openReproductionForm() {
            openFormModal('reproductionFormModal');
        }

        function openRFIDForm() {
            openFormModal('rfidFormModal');
        }

        function openBCSForm() {
            openFormModal('bcsFormModal');
        }

        // Funções específicas para Reprodução
        function openInseminationForm() {
            openFormModal('inseminationFormModal');
        }

        function openPregnancyTestForm() {
            openFormModal('pregnancyTestFormModal');
        }

        function openBirthForm() {
            openFormModal('birthFormModal');
        }

        function prepareForBirth(animalId) {
            alert('Preparando para parto do animal ' + animalId + '...');
            // Implementar lógica de preparação para parto
        }

        function schedulePregnancyTest(animalId) {
            alert('Agendando teste de prenhez para o animal ' + animalId + '...');
            // Implementar agendamento de teste
        }

        function monitorEstrus(animalId) {
            alert('Iniciando monitoramento de cio para o animal ' + animalId + '...');
            // Implementar monitoramento de cio
        }

        function viewReproductiveHistory(animalId) {
            alert('Visualizando histórico reprodutivo do animal ' + animalId + '...');
            // Implementar visualização de histórico
        }

        function inseminateNow(animalId) {
            alert('Iniciando inseminação do animal ' + animalId + '...');
            // Implementar inseminação imediata
        }

        // Funções para Gestão de Sêmen e Embriões
        function openSemenStockForm() {
            alert('Abrindo formulário de gestão de estoque de sêmen...');
            // Implementar formulário de estoque
        }

        function openEmbryoTransferForm() {
            alert('Abrindo formulário de transferência de embriões...');
            // Implementar formulário de TE
        }

        // Funções para IATF
        function openIATFProtocolForm() {
            alert('Abrindo formulário de protocolo IATF...');
            // Implementar formulário de protocolo
        }

        function viewIATFSchedule() {
            alert('Visualizando cronograma IATF...');
            // Implementar visualização de cronograma
        }

        // Funções para Relatórios Avançados
        function generateReproductiveReport(type) {
            switch(type) {
                case 'efficiency':
                    alert('Gerando relatório de eficiência reprodutiva...');
                    break;
                case 'bulls':
                    alert('Gerando relatório de desempenho por touro...');
                    break;
                case 'calendar':
                    alert('Gerando calendário reprodutivo...');
                    break;
                case 'genetic':
                    alert('Gerando análise genética...');
                    break;
                case 'costs':
                    alert('Gerando análise de custos reprodutivos...');
                    break;
                case 'custom':
                    alert('Abrindo criador de relatório personalizado...');
                    break;
                default:
                    alert('Tipo de relatório não reconhecido');
            }
            // Implementar geração de relatórios
        }

        // Fechar modal ao clicar fora
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('show');
                    document.body.style.overflow = '';
                }
            });
        });

        // Fechar modal com ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal.show').forEach(modal => {
                    modal.classList.remove('show');
                    document.body.style.overflow = '';
                });
            }
        });

        // Submissão dos formulários específicos de cada modal

        document.getElementById('healthForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('api/health/create.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Cuidado de saúde registrado com sucesso!');
                    closeFormModal('healthFormModal');
                } else {
                    alert('Erro ao registrar cuidado: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao registrar cuidado de saúde');
            });
        });

        document.getElementById('reproductionForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('api/reproduction/create.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Reprodução registrada com sucesso!');
                    closeFormModal('reproductionFormModal');
                } else {
                    alert('Erro ao registrar reprodução: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao registrar reprodução');
            });
        });

        document.getElementById('rfidForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('api/transponders/create.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Transponder RFID cadastrado com sucesso!');
                    closeFormModal('rfidFormModal');
                } else {
                    alert('Erro ao cadastrar RFID: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao cadastrar RFID');
            });
        });

        document.getElementById('bcsForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('api/body_condition/create.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Condição corporal registrada com sucesso!');
                    closeFormModal('bcsFormModal');
                } else {
                    alert('Erro ao registrar BCS: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao registrar condição corporal');
            });
        });
    </script>
</body>
</html>
