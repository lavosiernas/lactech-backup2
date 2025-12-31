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
        
        /* Modal de Alimentação Full Screen */
        #modal-feeding .modal-content {
            max-width: 100vw !important;
            max-height: 100vh !important;
            width: 100vw !important;
            height: 100vh !important;
            margin: 0 !important;
            border-radius: 0 !important;
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
                <span class="text-gray-700 font-semibold">Voltar</span>
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
                        <div class="app-item bg-white border border-gray-200 rounded-xl p-3 cursor-pointer shadow-sm" onclick="openBullsModal()">
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
                        <div class="app-item bg-white border border-gray-200 rounded-xl p-3 cursor-pointer shadow-sm" onclick="openHeiferOverlay()">
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
                <div class="flex flex-col items-center justify-center py-16 px-6 text-center text-gray-600">
                    <div class="w-16 h-16 bg-gray-100 rounded-2xl flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5V7.5m7.5-3v3M4.5 18.75V9a4.5 4.5 0 014.5-4.5h6a4.5 4.5 0 014.5 4.5v9.75M4.5 18.75h15" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Estamos em desenvolvimento</h3>
                    <p class="text-sm text-gray-500 max-w-md">Função disponível em breve. Nossa equipe está finalizando os relatórios para entregar insights completos e confiáveis.</p>
                </div>
                <!--
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                    <div class="p-5 bg-white border border-blue-200 rounded-2xl shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex items-center justify-between mb-3">
                            <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h18v4a2 2 0 01-2 2H5a2 2 0 01-2-2V3zM3 13h18v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4z"/>
                                </svg>
                            </div>
                            <span class="text-xs font-semibold text-blue-500 uppercase tracking-wide">Total 7 dias</span>
                        </div>
                        <p class="text-2xl font-bold text-blue-600 mb-1"><?php echo number_format($total_production, 0); ?>L</p>
                        <p class="text-xs text-gray-500">Volume produzido na última semana</p>
                    </div>
                    <div class="p-5 bg-white border border-green-200 rounded-2xl shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex items-center justify-between mb-3">
                            <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/>
                                </svg>
                            </div>
                            <span class="text-xs font-semibold text-green-500 uppercase tracking-wide">Média diária</span>
                        </div>
                        <p class="text-2xl font-bold text-green-600 mb-1"><?php echo number_format($avg_daily_production, 1); ?>L</p>
                        <p class="text-xs text-gray-500">Produção média por dia</p>
                    </div>
                    <div class="p-5 bg-white border border-orange-200 rounded-2xl shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex items-center justify-between mb-3">
                            <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            <span class="text-xs font-semibold text-orange-500 uppercase tracking-wide">Animais ativos</span>
                        </div>
                        <p class="text-2xl font-bold text-orange-600 mb-1"><?php echo $lactating_cows; ?></p>
                        <p class="text-xs text-gray-500">Animais em lactação</p>
                    </div>
                    <div class="p-5 bg-white border border-purple-200 rounded-2xl shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex items-center justify-between mb-3">
                            <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <span class="text-xs font-semibold text-purple-500 uppercase tracking-wide">Por animal</span>
                        </div>
                        <p class="text-2xl font-bold text-purple-600 mb-1"><?php echo $lactating_cows > 0 ? number_format($avg_daily_production / max(1, $lactating_cows), 1) : '0'; ?>L</p>
                        <p class="text-xs text-gray-500">Média diária por lactante</p>
                    </div>
                </div>

                <!-- Dados de Qualidade -->
                <?php if(!empty($milk_data)): ?>
                <div class="mb-8 hidden">
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
                <div class="mb-8 hidden">
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
                <div class="bg-gray-50 rounded-xl p-6 hidden">
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
                <!-- Indicadores do Rebanho - Remodelado -->
                <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-2xl p-5 border border-gray-200">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                        <svg class="w-5 h-5 text-gray-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        Resumo do Rebanho
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="p-4 bg-white rounded-xl border border-gray-200 shadow-sm hover:shadow-md transition-shadow">
                            <div class="flex items-center justify-between mb-3">
                                <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center">
                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                </div>
                                <span class="text-xs font-semibold text-blue-500 uppercase tracking-wide">Total</span>
                            </div>
                            <p class="text-2xl font-bold text-blue-600 mb-1"><?php echo $total_animals; ?></p>
                            <p class="text-xs text-gray-500">Animais cadastrados</p>
                        </div>
                        <div class="p-4 bg-white rounded-xl border border-gray-200 shadow-sm hover:shadow-md transition-shadow">
                            <div class="flex items-center justify-between mb-3">
                                <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center">
                                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <span class="text-xs font-semibold text-green-500 uppercase tracking-wide">Lactantes</span>
                            </div>
                            <p class="text-2xl font-bold text-green-600 mb-1"><?php echo $lactating_cows; ?></p>
                            <p class="text-xs text-gray-500">Animais em produção</p>
                        </div>
                        <div class="p-4 bg-white rounded-xl border border-gray-200 shadow-sm hover:shadow-md transition-shadow">
                            <div class="flex items-center justify-between mb-3">
                                <div class="w-10 h-10 bg-pink-100 rounded-xl flex items-center justify-center">
                                    <svg class="w-5 h-5 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <span class="text-xs font-semibold text-pink-500 uppercase tracking-wide">Prenhes</span>
                            </div>
                            <p class="text-2xl font-bold text-pink-600 mb-1"><?php echo $pregnant_cows; ?></p>
                            <p class="text-xs text-gray-500">Gestação confirmada</p>
                        </div>
                        <div class="p-4 bg-white rounded-xl border border-gray-200 shadow-sm hover:shadow-md transition-shadow">
                            <div class="flex items-center justify-between mb-3">
                                <div class="w-10 h-10 bg-orange-100 rounded-xl flex items-center justify-center">
                                    <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <span class="text-xs font-semibold text-orange-500 uppercase tracking-wide">Média</span>
                            </div>
                            <p class="text-2xl font-bold text-orange-600 mb-1"><?php echo number_format($avg_daily_production, 1); ?>L</p>
                            <p class="text-xs text-gray-500">Produção média/dia</p>
                        </div>
                    </div>
                </div>

                <!-- Busca Rápida - Mobile Optimized -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                        <svg class="w-5 h-5 text-gray-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        Busca Rápida
                    </h3>
                    <div class="space-y-3">
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </span>
                            <input type="text" id="searchAnimal" placeholder="Nome ou número do animal..." class="w-full pl-11 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 text-sm transition-all">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <select id="filterStatus" class="px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 text-sm bg-white transition-all">
                                <option value="">Todos status</option>
                                <option value="Lactante">Lactante</option>
                                <option value="Seco">Seco</option>
                                <option value="Prenha">Prenha</option>
                                <option value="Novilha">Novilha</option>
                                <option value="Touro">Touro</option>
                            </select>
                            <select id="filterBreed" class="px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 text-sm bg-white transition-all">
                                <option value="">Todas raças</option>
                                <option value="Holandesa">Holandesa</option>
                                <option value="Gir">Gir</option>
                                <option value="Girolando">Girolando</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Lista de Animais - Cards Remodelados -->
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
                        <?php foreach($animals as $animal):
                            $status_lower = strtolower($animal['status'] ?? '');
                            $animal_image = './assets/video/vaca.png';
                            if (strpos($status_lower, 'touro') !== false || strpos($status_lower, 'boi') !== false) {
                                $animal_image = './assets/video/touro.png';
                            } elseif (strpos($status_lower, 'bezerro') !== false || strpos($status_lower, 'bezerra') !== false || strpos($status_lower, 'bezzero') !== false || strpos($status_lower, 'bezzera') !== false) {
                                $animal_image = './assets/video/bezzero.png';
                            }
                            $badge_class = 'bg-gray-100 text-gray-800';
                            if (strpos($status_lower, 'lactante') !== false || strpos($status_lower, 'vaca') !== false) {
                                $badge_class = 'bg-green-100 text-green-700';
                            } elseif (strpos($status_lower, 'touro') !== false || strpos($status_lower, 'boi') !== false) {
                                $badge_class = 'bg-red-100 text-red-700';
                            }
                        ?>
                        <div class="animal-card p-4 bg-gradient-to-br from-gray-50 to-white rounded-xl border border-gray-200 hover:shadow-md transition-all cursor-pointer" 
                             data-name="<?php echo htmlspecialchars(strtolower($animal['name'] ?? '')); ?>"
                             data-number="<?php echo htmlspecialchars(strtolower($animal['animal_number'] ?? '')); ?>"
                             data-status="<?php echo htmlspecialchars($animal['status'] ?? ''); ?>"
                             data-breed="<?php echo htmlspecialchars(strtolower($animal['breed'] ?? '')); ?>"
                             data-id="<?php echo $animal['id'] ?? ''; ?>"
                             data-animal-number="<?php echo htmlspecialchars($animal['animal_number'] ?? ''); ?>">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center space-x-3">
                                    <div class="w-16 h-16 rounded-xl overflow-hidden bg-gray-100 border border-gray-200 flex items-center justify-center shadow-sm">
                                        <img src="<?php echo $animal_image; ?>" alt="<?php echo htmlspecialchars($animal['status'] ?? 'Animal'); ?>" class="w-full h-full object-contain p-1">
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900 text-sm"><?php echo htmlspecialchars($animal['name'] ?? 'Sem nome'); ?></p>
                                        <p class="text-xs text-gray-600 mt-0.5">#<?php echo htmlspecialchars($animal['animal_number']); ?> • <?php echo htmlspecialchars($animal['breed']); ?></p>
                                        <p class="text-xs text-gray-500 mt-0.5"><?php echo $animal['age_months']; ?> meses</p>
                                    </div>
                                </div>
                                <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $badge_class; ?>">
                                    <?php echo htmlspecialchars($animal['status'] ?? ''); ?>
                                </span>
                            </div>
                            <div class="flex space-x-2 pt-3 border-t border-gray-200">
                                <button onclick="showPedigreeModal(<?php echo $animal['id']; ?>)" class="flex-1 px-3 py-2 bg-gray-100 text-gray-700 text-xs font-medium rounded-lg hover:bg-gray-200 transition-colors">
                                    Pedigree
                                </button>
                                <button onclick="editAnimalModal(<?php echo $animal['id']; ?>)" class="flex-1 px-3 py-2 bg-gray-100 text-gray-700 text-xs font-medium rounded-lg hover:bg-gray-200 transition-colors">
                                    Editar
                                </button>
                                <button onclick="viewAnimalModal(<?php echo $animal['id']; ?>)" class="flex-1 px-3 py-2 bg-green-100 text-green-700 text-xs font-medium rounded-lg hover:bg-green-200 transition-colors">
                                    Ver Detalhes
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Ações Rápidas -->
                <div class="bg-gradient-to-br from-gray-50 to-white rounded-2xl p-5 border border-gray-200 mb-8">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                        <svg class="w-5 h-5 text-gray-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        Ações Rápidas
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <button onclick="openHealthForm()" class="flex items-center space-x-3 p-4 bg-white rounded-xl border border-gray-200 hover:border-green-300 hover:shadow-md transition-all group">
                            <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center group-hover:bg-green-200 transition-colors">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="text-left flex-1">
                                <p class="font-semibold text-gray-900 text-sm">Registrar Saúde</p>
                                <p class="text-xs text-gray-600">Vacinas e medicamentos</p>
                            </div>
                        </button>
                        <button onclick="openReproductionForm()" class="flex items-center space-x-3 p-4 bg-white rounded-xl border border-gray-200 hover:border-pink-300 hover:shadow-md transition-all group">
                            <div class="w-12 h-12 bg-pink-100 rounded-xl flex items-center justify-center group-hover:bg-pink-200 transition-colors">
                                <svg class="w-6 h-6 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="text-left flex-1">
                                <p class="font-semibold text-gray-900 text-sm">Registrar Reprodução</p>
                                <p class="text-xs text-gray-600">Cios e inseminações</p>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Lista de Animais do Rebanho -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200">
                    <div class="p-6 border-b border-gray-200 flex items-center justify-between">
                        <h3 class="text-lg font-bold text-gray-900 flex items-center">
                            <svg class="w-5 h-5 text-gray-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                            </svg>
                            Rebanho Completo (<?php echo count($animals); ?> animais)
                        </h3>
                        <button onclick="openAddAnimalModal()" class="flex items-center space-x-2 px-4 py-2 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-xl hover:from-green-600 hover:to-green-700 transition-all text-sm shadow-md hover:shadow-lg">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                            </svg>
                            <span>Adicionar</span>
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
                                <?php foreach($animals as $animal):
                                    $status_lower = strtolower($animal['status'] ?? '');
                                    $table_image = './assets/video/vaca.png';
                                    if (strpos($status_lower, 'touro') !== false || strpos($status_lower, 'boi') !== false) {
                                        $table_image = './assets/video/touro.png';
                                    } elseif (strpos($status_lower, 'bezerro') !== false || strpos($status_lower, 'bezerra') !== false || strpos($status_lower, 'bezzero') !== false || strpos($status_lower, 'bezzera') !== false) {
                                        $table_image = './assets/video/bezzero.png';
                                    }
                                    $row_badge = 'bg-gray-100 text-gray-800';
                                    if (strpos($status_lower, 'lactante') !== false || strpos($status_lower, 'vaca') !== false) {
                                        $row_badge = 'bg-green-100 text-green-700';
                                    } elseif (strpos($status_lower, 'touro') !== false || strpos($status_lower, 'boi') !== false) {
                                        $row_badge = 'bg-red-100 text-red-700';
                                    } elseif (strpos($status_lower, 'novilha') !== false) {
                                        $row_badge = 'bg-yellow-100 text-yellow-700';
                                    }
                                ?>
                                <tr class="animal-table-row hover:bg-gray-50 transition-colors"
                                    data-name="<?php echo htmlspecialchars(strtolower($animal['name'] ?? '')); ?>"
                                    data-number="<?php echo htmlspecialchars(strtolower($animal['animal_number'] ?? '')); ?>"
                                    data-status="<?php echo htmlspecialchars($animal['status'] ?? ''); ?>"
                                    data-breed="<?php echo htmlspecialchars(strtolower($animal['breed'] ?? '')); ?>"
                                    data-id="<?php echo $animal['id'] ?? ''; ?>">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="w-12 h-12 rounded-xl overflow-hidden bg-gray-100 border border-gray-200 flex items-center justify-center mr-4">
                                                <img src="<?php echo $table_image; ?>" alt="<?php echo htmlspecialchars($animal['status'] ?? 'Animal'); ?>" class="w-full h-full object-contain p-1">
                                            </div>
                                            <div>
                                                <div class="text-sm font-semibold text-gray-900">
                                                    <?php echo htmlspecialchars($animal['name'] ?? 'Sem nome'); ?>
                                                </div>
                                                <div class="text-xs text-gray-500 mt-0.5">
                                                    #<?php echo htmlspecialchars($animal['animal_number']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full <?php echo $row_badge; ?>">
                                            <?php echo htmlspecialchars($animal['status'] ?? ''); ?>
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
                                        <button onclick="showPedigreeModal(<?php echo $animal['id']; ?>)" class="text-green-600 hover:text-green-800 font-medium">
                                            Ver Pedigree
                                        </button>
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
                        <!-- Alertas de vacinação carregados dinamicamente do banco de dados -->
                        <div id="vaccination-alerts-container" class="space-y-3">
                            <!-- Os alertas de vacinação serão carregados aqui via JavaScript -->
                        </div>
                        
                        <!-- Alertas carregados dinamicamente do banco de dados -->
                        <div id="mastitis-alerts-container" class="space-y-3">
                            <!-- Os alertas serão carregados aqui via JavaScript -->
                        </div>
                        
                        <div id="medicine-alerts-container" class="space-y-3">
                            <!-- Os alertas de medicamentos serão carregados aqui via JavaScript -->
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
                        <!-- Alertas reprodutivos carregados dinamicamente do banco de dados -->
                        <div id="reproductive-alerts-container" class="space-y-3">
                            <!-- Os alertas serão carregados aqui via JavaScript -->
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
        <div class="modal-content" style="max-width: 100vw !important; max-height: 100vh !important; width: 100vw !important; height: 100vh !important; margin: 0 !important; border-radius: 0 !important; overflow-y: auto; padding: 1.5rem;">
            <div class="flex items-center justify-between mb-6 pb-4 border-b-2 border-gray-200">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-lime-500 to-lime-600 rounded-xl flex items-center justify-center shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">Controle de Alimentação</h2>
                        <p class="text-sm text-gray-500">Gerencie os registros de alimentação do rebanho</p>
                    </div>
                </div>
                <button onclick="closeModal('feeding')" class="w-10 h-10 flex items-center justify-center hover:bg-gray-100 rounded-xl transition-colors">
                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Resumo Diário -->
            <div class="mb-6 grid grid-cols-1 md:grid-cols-4 gap-4" id="feeding-daily-summary">
                <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-4 border border-green-200">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-green-700">Concentrado</span>
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                    </div>
                    <p class="text-2xl font-bold text-green-900" id="summary-concentrate">0 kg</p>
                    <p class="text-xs text-green-600 mt-1">Hoje</p>
                </div>
                <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-4 border border-blue-200">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-blue-700">Volumoso</span>
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path>
                        </svg>
                    </div>
                    <p class="text-2xl font-bold text-blue-900" id="summary-roughage">0 kg</p>
                    <p class="text-xs text-blue-600 mt-1">Hoje</p>
                </div>
                <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-xl p-4 border border-yellow-200">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-yellow-700">Silagem</span>
                        <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <p class="text-2xl font-bold text-yellow-900" id="summary-silage">0 kg</p>
                    <p class="text-xs text-yellow-600 mt-1">Hoje</p>
                </div>
                <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl p-4 border border-purple-200">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-purple-700">Animais</span>
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <p class="text-2xl font-bold text-purple-900" id="summary-animals">0</p>
                    <p class="text-xs text-purple-600 mt-1">Alimentados hoje</p>
                </div>
            </div>

            <!-- Filtros e Ações -->
            <div class="mb-6 flex flex-col md:flex-row gap-4 items-start md:items-center justify-between">
                <div class="flex flex-wrap gap-3">
                    <input type="date" id="feeding-filter-date-from" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-lime-500 focus:border-transparent" value="<?php echo date('Y-m-d', strtotime('-7 days')); ?>">
                    <input type="date" id="feeding-filter-date-to" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-lime-500 focus:border-transparent" value="<?php echo date('Y-m-d'); ?>">
                    <select id="feeding-filter-animal" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-lime-500 focus:border-transparent">
                        <option value="">Todos os animais</option>
                    </select>
                    <button onclick="loadFeedingRecords()" class="px-4 py-2 bg-lime-600 text-white rounded-lg hover:bg-lime-700 transition-colors font-medium">
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Atualizar
                    </button>
                </div>
                <button onclick="openFeedingForm()" class="px-5 py-2 bg-gradient-to-r from-lime-600 to-lime-700 text-white rounded-lg hover:from-lime-700 hover:to-lime-800 transition-all font-medium shadow-md">
                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Novo Registro
                </button>
            </div>

            <!-- Lista de Registros -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Data</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Animal</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Turno</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-700 uppercase">Concentrado (kg)</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-700 uppercase">Volumoso (kg)</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-700 uppercase">Silagem (kg)</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-700 uppercase">Feno (kg)</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-700 uppercase">Custo</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 uppercase">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="feeding-records-list" class="divide-y divide-gray-200">
                            <tr>
                                <td colspan="9" class="px-4 py-8 text-center text-gray-500">
                                    <div class="flex flex-col items-center">
                                        <svg class="w-12 h-12 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                        </svg>
                                        <p>Carregando registros...</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Formulário de Alimentação -->
    <div id="modal-feeding-form" class="modal">
        <div class="modal-content" style="max-width: 700px;">
            <div class="flex items-center justify-between mb-6 pb-4 border-b-2 border-gray-200">
                <h2 class="text-xl font-bold text-gray-900" id="feeding-form-title">Novo Registro de Alimentação</h2>
                <button onclick="closeFeedingForm()" class="w-8 h-8 flex items-center justify-center hover:bg-gray-100 rounded-lg transition-colors">
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <form id="feeding-form" class="space-y-4">
                <input type="hidden" id="feeding-form-id" name="id">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Animal *</label>
                        <select id="feeding-form-animal" name="animal_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-lime-500 focus:border-transparent">
                            <option value="">Selecione o animal</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Data *</label>
                        <input type="date" id="feeding-form-date" name="feed_date" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-lime-500 focus:border-transparent" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Turno *</label>
                        <select id="feeding-form-shift" name="shift" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-lime-500 focus:border-transparent">
                            <option value="unico">Único</option>
                            <option value="manha">Manhã</option>
                            <option value="tarde">Tarde</option>
                            <option value="noite">Noite</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Ração</label>
                        <input type="text" id="feeding-form-type" name="feed_type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-lime-500 focus:border-transparent" placeholder="Ex: Concentrado, Ração">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Concentrado (kg)</label>
                        <input type="number" id="feeding-form-concentrate" name="concentrate_kg" step="0.01" min="0" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-lime-500 focus:border-transparent" placeholder="0.00">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Volumoso (kg)</label>
                        <input type="number" id="feeding-form-roughage" name="roughage_kg" step="0.01" min="0" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-lime-500 focus:border-transparent" placeholder="0.00">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Silagem (kg)</label>
                        <input type="number" id="feeding-form-silage" name="silage_kg" step="0.01" min="0" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-lime-500 focus:border-transparent" placeholder="0.00">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Feno (kg)</label>
                        <input type="number" id="feeding-form-hay" name="hay_kg" step="0.01" min="0" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-lime-500 focus:border-transparent" placeholder="0.00">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Marca</label>
                        <input type="text" id="feeding-form-brand" name="feed_brand" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-lime-500 focus:border-transparent" placeholder="Marca da ração">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">% Proteína</label>
                        <input type="number" id="feeding-form-protein" name="protein_percentage" step="0.01" min="0" max="100" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-lime-500 focus:border-transparent" placeholder="0.00">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Custo por kg (R$)</label>
                        <input type="number" id="feeding-form-cost" name="cost_per_kg" step="0.01" min="0" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-lime-500 focus:border-transparent" placeholder="0.00">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Observações</label>
                    <textarea id="feeding-form-notes" name="notes" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-lime-500 focus:border-transparent" placeholder="Observações adicionais..."></textarea>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
                    <button type="button" onclick="closeFeedingForm()" class="px-5 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium">
                        Cancelar
                    </button>
                    <button type="submit" class="px-5 py-2 bg-gradient-to-r from-lime-600 to-lime-700 text-white rounded-lg hover:from-lime-700 hover:to-lime-800 transition-all font-medium shadow-md">
                        Salvar Registro
                    </button>
                </div>
            </form>
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

    <!-- Modal customizado para mensagens e confirmações -->
    <div id="customMessageModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 500px;">
            <div class="flex items-center justify-between mb-4">
                <h3 id="customMessageTitle" class="text-xl font-bold text-gray-900"></h3>
                <button onclick="closeCustomMessage()" class="w-8 h-8 flex items-center justify-center hover:bg-gray-100 rounded-lg transition-colors">
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <p id="customMessageText" class="text-gray-700 mb-6"></p>
            <div id="customMessageButtons" class="flex justify-end space-x-3"></div>
        </div>
    </div>

    <script>
        // JavaScript otimizado para carregamento rápido
        (function() {
            'use strict';
            
            let currentModal = null;
            let customMessageCallback = null;
            
            // Função para mostrar mensagem customizada
            window.showCustomMessage = function(title, message, type = 'info') {
                const modal = document.getElementById('customMessageModal');
                const titleEl = document.getElementById('customMessageTitle');
                const textEl = document.getElementById('customMessageText');
                const buttonsEl = document.getElementById('customMessageButtons');
                
                titleEl.textContent = title;
                textEl.textContent = message;
                
                // Definir cor baseada no tipo
                let bgColor = 'bg-indigo-600';
                if (type === 'success') bgColor = 'bg-green-600';
                else if (type === 'error') bgColor = 'bg-red-600';
                else if (type === 'warning') bgColor = 'bg-yellow-600';
                
                buttonsEl.innerHTML = `
                    <button onclick="closeCustomMessage()" class="px-6 py-2 ${bgColor} text-white rounded-lg hover:opacity-90 transition-colors">
                        OK
                    </button>
                `;
                
                modal.style.display = 'flex';
                modal.classList.add('show');
                document.body.style.overflow = 'hidden';
            };
            
            // Função para mostrar confirmação customizada
            window.showCustomConfirm = function(title, message, onConfirm, onCancel = null) {
                const modal = document.getElementById('customMessageModal');
                const titleEl = document.getElementById('customMessageTitle');
                const textEl = document.getElementById('customMessageText');
                const buttonsEl = document.getElementById('customMessageButtons');
                
                titleEl.textContent = title;
                textEl.textContent = message;
                
                customMessageCallback = { onConfirm, onCancel };
                
                buttonsEl.innerHTML = `
                    <button onclick="handleCustomConfirm(false)" class="px-6 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                        Cancelar
                    </button>
                    <button onclick="handleCustomConfirm(true)" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
                        Confirmar
                    </button>
                `;
                
                modal.style.display = 'flex';
                modal.classList.add('show');
                document.body.style.overflow = 'hidden';
            };
            
            // Função para fechar mensagem customizada
            window.closeCustomMessage = function() {
                const modal = document.getElementById('customMessageModal');
                modal.style.display = 'none';
                modal.classList.remove('show');
                document.body.style.overflow = '';
                customMessageCallback = null;
            };
            
            // Função para lidar com confirmação
            window.handleCustomConfirm = function(confirmed) {
                if (customMessageCallback) {
                    if (confirmed && customMessageCallback.onConfirm) {
                        customMessageCallback.onConfirm();
                    } else if (!confirmed && customMessageCallback.onCancel) {
                        customMessageCallback.onCancel();
                    }
                }
                closeCustomMessage();
            };
            
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
            
            // Função para abrir overlay de novilhas
            // Esta função será sobrescrita pela função do heifer-overlay.html se ela estiver carregada
            window.openHeiferOverlay = function() {
                // Primeiro, tentar usar a função original se existir
                // (guardamos uma referência antes de sobrescrever)
                const overlay = document.getElementById('heiferOverlay');
                if (!overlay) {
                    console.error('Overlay heiferOverlay não encontrado no DOM!');
                    alert('Erro: Sistema de Controle de Novilhas não encontrado. Verifique se o arquivo está carregado corretamente.');
                    return;
                }
                
                // Abrir o overlay
                overlay.classList.remove('hidden');
                overlay.style.display = 'flex';
                document.body.style.overflow = 'hidden';
                console.log('Overlay de novilhas aberto');
                
                // Tentar carregar dados se as funções existirem (do heifer-overlay.html)
                if (typeof window.loadHeiferDashboard === 'function') {
                    window.loadHeiferDashboard();
                } else if (typeof loadHeiferDashboard === 'function') {
                    loadHeiferDashboard();
                }
                
                if (typeof window.loadHeifersTable === 'function') {
                    window.loadHeifersTable();
                } else if (typeof loadHeifersTable === 'function') {
                    loadHeifersTable();
                }
            };
            
            // Carregar alertas quando os modais forem abertos
            document.addEventListener('DOMContentLoaded', function() {
                // Carregar alertas quando o modal de saúde for aberto
                const healthModal = document.getElementById('modal-health');
                if (healthModal) {
                    const observer = new MutationObserver(function(mutations) {
                        mutations.forEach(function(mutation) {
                            if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                                if (healthModal.classList.contains('show')) {
                                    loadHealthAlerts();
                                }
                            }
                        });
                    });
                    observer.observe(healthModal, { attributes: true, attributeFilter: ['class'] });
                }
                
                // Carregar alertas quando o modal de reprodução for aberto
                const reproductionModal = document.getElementById('modal-reproduction');
                if (reproductionModal) {
                    const observer = new MutationObserver(function(mutations) {
                        mutations.forEach(function(mutation) {
                            if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                                if (reproductionModal.classList.contains('show')) {
                                    loadReproductiveAlerts();
                                }
                            }
                        });
                    });
                    observer.observe(reproductionModal, { attributes: true, attributeFilter: ['class'] });
                }
            });
            
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
                        showCustomMessage('Pedigree', 'Esta funcionalidade será implementada em breve.', 'info');
                    } else {
                        showCustomMessage('Erro', 'Erro ao carregar pedigree do animal.', 'error');
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    showCustomMessage('Pedigree', 'Esta funcionalidade será implementada em breve.', 'info');
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
                        showCustomMessage('Detalhes do Animal', info, 'info');
                    } else {
                        showCustomMessage('Erro', 'Erro ao carregar dados do animal.', 'error');
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    showCustomMessage('Visualizar Animal', 'Esta funcionalidade será implementada em breve.', 'info');
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

        function openVaccinationForm() {
            openFormModal('healthFormModal');
            // Preencher o tipo de registro como vacinação
            const recordTypeSelect = document.querySelector('#healthFormModal select[name="record_type"]');
            if (recordTypeSelect) {
                recordTypeSelect.value = 'Vacinação';
            }
        }

        // Carregar alertas de saúde do banco de dados
        async function loadHealthAlerts() {
            try {
                const response = await fetch('./api/health_alerts.php?action=get_alerts');
                const result = await response.json();
                
                if (result.success && result.data) {
                    // Carregar alertas de mastite
                    const mastitisContainer = document.getElementById('mastitis-alerts-container');
                    if (mastitisContainer && result.data.mastitis) {
                        mastitisContainer.innerHTML = result.data.mastitis.map(alert => `
                            <div class="p-3 bg-yellow-50 rounded-lg border-l-4 border-yellow-400">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <p class="font-medium text-yellow-800 text-sm">Mastite Ativa</p>
                                        <p class="text-xs text-yellow-600 mt-1">Vaca #${alert.animal_number || alert.animal_id} - ${alert.message || 'Quarto posterior'}</p>
                                    </div>
                                    <button onclick="treatMastitis(${alert.animal_id})" class="ml-3 px-3 py-1 bg-yellow-600 text-white text-xs rounded-lg hover:bg-yellow-700 transition-colors">
                                        Tratar
                                    </button>
                                </div>
                            </div>
                        `).join('');
                    }
                    
                    // Carregar alertas de vacinação
                    const vaccinationContainer = document.getElementById('vaccination-alerts-container');
                    if (vaccinationContainer && result.data.vaccinations) {
                        vaccinationContainer.innerHTML = result.data.vaccinations.map(alert => {
                            const daysRemaining = alert.days_remaining || 0;
                            const isOverdue = daysRemaining < 0;
                            return `
                                <div class="p-3 ${isOverdue ? 'bg-red-50 border-red-400' : 'bg-orange-50 border-orange-400'} rounded-lg border-l-4">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <p class="font-medium ${isOverdue ? 'text-red-800' : 'text-orange-800'} text-sm">${isOverdue ? 'Vacina Atrasada' : 'Vacinação Pendente'}</p>
                                            <p class="text-xs ${isOverdue ? 'text-red-600' : 'text-orange-600'} mt-1">${alert.vaccine_name} - Vaca #${alert.animal_number || alert.animal_id}</p>
                                            <p class="text-xs ${isOverdue ? 'text-red-500' : 'text-orange-500'} mt-1">${isOverdue ? `Venceu há ${Math.abs(daysRemaining)} dias` : `${daysRemaining} dias restantes`}</p>
                                        </div>
                                        <button onclick="scheduleVaccination(${alert.id}, '${alert.vaccine_name}')" class="ml-3 px-3 py-1 ${isOverdue ? 'bg-red-600 hover:bg-red-700' : 'bg-orange-600 hover:bg-orange-700'} text-white text-xs rounded-lg transition-colors">
                                            Aplicar
                                        </button>
                                    </div>
                                </div>
                            `;
                        }).join('');
                    }
                    
                    // Carregar alertas de medicamentos
                    const medicineContainer = document.getElementById('medicine-alerts-container');
                    if (medicineContainer && result.data.medicines) {
                        medicineContainer.innerHTML = result.data.medicines.map(alert => `
                            <div class="p-3 bg-orange-50 rounded-lg border-l-4 border-orange-400">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <p class="font-medium text-orange-800 text-sm">Medicamento Baixo</p>
                                        <p class="text-xs text-orange-600 mt-1">${alert.medicine_name} - ${alert.remaining_doses || 0} doses restantes</p>
                                    </div>
                                    <button onclick="reorderMedicine(${alert.id}, '${alert.medicine_name}')" class="ml-3 px-3 py-1 bg-orange-600 text-white text-xs rounded-lg hover:bg-orange-700 transition-colors">
                                        Repor
                                    </button>
                                </div>
                            </div>
                        `).join('');
                    }
                }
            } catch (error) {
                console.error('Erro ao carregar alertas de saúde:', error);
            }
        }
        
        // Carregar alertas reprodutivos do banco de dados
        async function loadReproductiveAlerts() {
            try {
                const response = await fetch('./api/reproductive_alerts.php?action=get_alerts');
                const result = await response.json();
                
                if (result.success && result.data) {
                    const container = document.getElementById('reproductive-alerts-container');
                    if (!container) return;
                    
                    let html = '';
                    
                    // Alertas de parto iminente
                    if (result.data.births && result.data.births.length > 0) {
                        html += result.data.births.map(alert => {
                            const daysRemaining = alert.days_remaining || 0;
                            const birthDate = new Date(alert.expected_birth).toLocaleDateString('pt-BR');
                            return `
                                <div class="p-3 bg-red-50 rounded-lg border-l-4 border-red-400">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <p class="font-medium text-red-800 text-sm">Parto Iminente</p>
                                            <p class="text-xs text-red-600 mt-1">Vaca #${alert.animal_number || alert.animal_id} - DPP: ${birthDate}</p>
                                            <p class="text-xs text-red-500 mt-1">${daysRemaining} dias restantes</p>
                                        </div>
                                        <button onclick="prepareForBirth(${alert.animal_id})" class="ml-3 px-3 py-1 bg-red-600 text-white text-xs rounded-lg hover:bg-red-700 transition-colors">
                                            Preparar
                                        </button>
                                    </div>
                                </div>
                            `;
                        }).join('');
                    }
                    
                    // Alertas de teste de prenhez
                    if (result.data.pregnancy_tests && result.data.pregnancy_tests.length > 0) {
                        html += result.data.pregnancy_tests.map(alert => `
                            <div class="p-3 bg-yellow-50 rounded-lg border-l-4 border-yellow-400">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <p class="font-medium text-yellow-800 text-sm">Exame de Prenhez</p>
                                        <p class="text-xs text-yellow-600 mt-1">Vaca #${alert.animal_number || alert.animal_id} - ${alert.days_since_ia || 30} dias pós-IA</p>
                                    </div>
                                    <button onclick="schedulePregnancyTest(${alert.animal_id}, ${alert.insemination_id})" class="ml-3 px-3 py-1 bg-yellow-600 text-white text-xs rounded-lg hover:bg-yellow-700 transition-colors">
                                        Agendar
                                    </button>
                                </div>
                            </div>
                        `).join('');
                    }
                    
                    // Alertas de retorno ao cio
                    if (result.data.estrus && result.data.estrus.length > 0) {
                        html += result.data.estrus.map(alert => `
                            <div class="p-3 bg-orange-50 rounded-lg border-l-4 border-orange-400">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <p class="font-medium text-orange-800 text-sm">Retorno ao Cio</p>
                                        <p class="text-xs text-orange-600 mt-1">Vaca #${alert.animal_number || alert.animal_id} - ${alert.days_postpartum || 45} dias pós-parto</p>
                                    </div>
                                    <button onclick="monitorEstrus(${alert.animal_id})" class="ml-3 px-3 py-1 bg-orange-600 text-white text-xs rounded-lg hover:bg-orange-700 transition-colors">
                                        Monitorar
                                    </button>
                                </div>
                            </div>
                        `).join('');
                    }
                    
                    container.innerHTML = html || '<p class="text-sm text-gray-500 text-center py-4">Nenhum alerta reprodutivo no momento.</p>';
                }
            } catch (error) {
                console.error('Erro ao carregar alertas reprodutivos:', error);
            }
        }
        
        function prepareForBirth(animalId) {
            // Abrir modal de preparação para parto
            showCustomConfirm(
                'Preparar para Parto',
                `Deseja preparar para o parto do animal #${animalId}?`,
                function() {
                    openBirthForm();
                }
            );
        }

        function schedulePregnancyTest(animalId, inseminationId = null) {
            // Abrir modal de agendamento de teste de prenhez
            openPregnancyTestForm();
            // Ou implementar lógica específica com animalId e inseminationId
        }

        function monitorEstrus(animalId) {
            // Abrir modal de monitoramento de cio
            showCustomConfirm(
                'Monitorar Cio',
                `Deseja iniciar o monitoramento de cio para o animal #${animalId}?`,
                function() {
                    // Implementar lógica de monitoramento
                    // Pode abrir um modal específico ou registrar no banco
                    showCustomMessage('Sucesso', 'Monitoramento de cio iniciado.', 'success');
                }
            );
        }
        
        function treatMastitis(animalId) {
            // Abrir modal de tratamento de mastite
            showCustomConfirm(
                'Tratar Mastite',
                `Deseja iniciar o tratamento de mastite para o animal #${animalId}?`,
                function() {
                    openHealthForm();
                }
            );
        }
        
        function scheduleVaccination(vaccinationId, vaccineName) {
            // Abrir modal de agendamento de vacinação
            openVaccinationForm();
            // Ou implementar lógica específica com vaccinationId e vaccineName
        }
        
        function reorderMedicine(medicineId, medicineName) {
            // Abrir modal de reposição de medicamento
            showCustomConfirm(
                'Repor Medicamento',
                `Deseja repor o estoque do medicamento ${medicineName}?`,
                function() {
                    // Implementar lógica de reposição
                    // Pode abrir um modal específico ou registrar no banco
                    showCustomMessage('Sucesso', `Reposição do medicamento ${medicineName} registrada.`, 'success');
                }
            );
        }

        function viewReproductiveHistory(animalId) {
            // Buscar histórico reprodutivo do animal
            fetch(`api/animals.php?action=get_reproductive_history&animal_id=${animalId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data && data.data.length > 0) {
                        // Criar modal com histórico formatado
                        const history = data.data.map(h => 
                            `• ${h.type}: ${h.date} - ${h.description || ''}`
                        ).join('\n');
                        showCustomMessage(
                            `Histórico Reprodutivo - Animal #${animalId}`,
                            history,
                            'info'
                        );
                    } else {
                        showCustomMessage(
                            'Histórico Reprodutivo',
                            'Nenhum histórico reprodutivo encontrado para este animal.',
                            'info'
                        );
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    showCustomMessage(
                        'Erro',
                        'Erro ao carregar histórico reprodutivo.',
                        'error'
                    );
                });
        }

        function inseminateNow(animalId) {
            // Abrir formulário de inseminação
            openInseminationForm();
            // Ou implementar lógica específica com animalId
        }

        // Funções para Gestão de Sêmen e Embriões
        function openSemenStockForm() {
            showCustomMessage(
                'Gestão de Estoque de Sêmen',
                'Esta funcionalidade será implementada em breve.',
                'info'
            );
            // Implementar formulário de estoque
        }

        function openEmbryoTransferForm() {
            showCustomMessage(
                'Transferência de Embriões',
                'Esta funcionalidade será implementada em breve.',
                'info'
            );
            // Implementar formulário de TE
        }

        // Funções para IATF
        function openIATFProtocolForm() {
            showCustomMessage(
                'Protocolo IATF',
                'Esta funcionalidade será implementada em breve.',
                'info'
            );
            // Implementar formulário de protocolo
        }

        function viewIATFSchedule() {
            showCustomMessage(
                'Cronograma IATF',
                'Esta funcionalidade será implementada em breve.',
                'info'
            );
            // Implementar visualização de cronograma
        }

        // Funções para Relatórios Avançados
        function generateReproductiveReport(type) {
            const messages = {
                'efficiency': 'Gerando relatório de eficiência reprodutiva...',
                'bulls': 'Gerando relatório de desempenho por touro...',
                'calendar': 'Gerando calendário reprodutivo...',
                'genetic': 'Gerando análise genética...',
                'costs': 'Gerando análise de custos reprodutivos...',
                'custom': 'Abrindo criador de relatório personalizado...'
            };
            
            const message = messages[type] || 'Tipo de relatório não reconhecido';
            showCustomMessage('Relatório Reprodutivo', message, 'info');
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
                    showCustomMessage('Sucesso', 'Cuidado de saúde registrado com sucesso!', 'success');
                    closeFormModal('healthFormModal');
                } else {
                    showCustomMessage('Erro', 'Erro ao registrar cuidado: ' + (data.message || data.error || 'Erro desconhecido'), 'error');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                showCustomMessage('Erro', 'Erro ao registrar cuidado de saúde', 'error');
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
                    showCustomMessage('Sucesso', 'Registros de reprodução registrados com sucesso!', 'success');
                    closeFormModal('reproductionFormModal');
                } else {
                    showCustomMessage('Erro', 'Erro ao registrar reprodução: ' + (data.message || data.error || 'Erro desconhecido'), 'error');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                showCustomMessage('Erro', 'Erro ao registrar reprodução', 'error');
            });
        });

        // ============================================
        // FUNCIONALIDADE DE ALIMENTAÇÃO
        // ============================================

        let feedingAnimals = [];
        let currentFeedingEditId = null;

        // Inicializar quando o DOM estiver pronto
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM carregado, inicializando sistema de alimentação...');
            
            const feedingModal = document.getElementById('modal-feeding');
            if (feedingModal) {
                console.log('Modal de alimentação encontrado');
                
                // Observar quando o modal é aberto
                let lastInitTime = 0;
                const observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        const isVisible = feedingModal.classList.contains('show') || 
                                         (feedingModal.style.display && feedingModal.style.display !== 'none');
                        if (isVisible) {
                            const now = Date.now();
                            // Evitar múltiplas inicializações em menos de 1 segundo
                            if (now - lastInitTime > 1000) {
                                lastInitTime = now;
                                console.log('🔄 [FEED] Modal de alimentação aberto, inicializando...');
                                setTimeout(function() {
                                    initFeedingModal();
                                }, 200);
                            }
                        }
                    });
                });
                
                observer.observe(feedingModal, {
                    attributes: true,
                    attributeFilter: ['class', 'style'],
                    childList: false,
                    subtree: false
                });
                
                // Também verificar imediatamente se o modal já está aberto
                if (feedingModal.classList.contains('show')) {
                    console.log('🔄 [FEED] Modal já está aberto, inicializando...');
                    setTimeout(function() {
                        initFeedingModal();
                    }, 100);
                }
                
                // Também verificar quando o modal é aberto via openSubModal
                const originalOpenSubModal = window.openSubModal;
                if (originalOpenSubModal) {
                    window.openSubModal = function(modalName) {
                        originalOpenSubModal(modalName);
                        if (modalName === 'feeding') {
                            setTimeout(function() {
                                console.log('🔄 [FEED] Modal aberto via openSubModal, inicializando...');
                                initFeedingModal();
                            }, 300);
                        }
                    };
                } else {
                    // Se não existir, criar uma função que observa mudanças no modal
                    console.log('⚠️ [FEED] openSubModal não encontrado, usando observer apenas');
                }
            } else {
                console.error('Modal de alimentação NÃO encontrado!');
            }
            
            // Garantir que o formulário está pronto
            const feedingForm = document.getElementById('feeding-form');
            if (feedingForm) {
                console.log('Formulário de alimentação encontrado');
            } else {
                console.error('Formulário de alimentação NÃO encontrado!');
            }
        });

        // ============================================
        // DECLARAÇÕES DE FUNÇÕES DE ALIMENTAÇÃO
        // ============================================

        // Carregar lista de animais
        function loadFeedingAnimals() {
            console.log('🔄 [FEED] Carregando animais da tabela animals...');
            return fetch('api/feed.php?action=animals')
                .then(response => {
                    console.log('📡 [FEED] Status da resposta:', response.status, response.statusText);
                    if (!response.ok) {
                        throw new Error('HTTP ' + response.status + ': ' + response.statusText);
                    }
                    return response.text().then(text => {
                        console.log('📦 [FEED] Resposta bruta:', text.substring(0, 500));
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            console.error('❌ [FEED] Erro ao fazer parse do JSON:', e);
                            throw new Error('Resposta inválida do servidor');
                        }
                    });
                })
                .then(data => {
                    console.log('📦 [FEED] Dados parseados:', data);
                    
                    // Extrair array de animais
                    let animals = [];
                    if (data && data.success !== false) {
                        // Pode vir em data.data ou diretamente em data
                        animals = data.data || (Array.isArray(data) ? data : []);
                    } else if (data && data.error) {
                        console.error('❌ [FEED] Erro na API:', data.error);
                        showCustomMessage('Erro', data.error, 'error');
                        return null;
                    }
                    
                    // Garantir que é um array
                    feedingAnimals = Array.isArray(animals) ? animals : [];
                    console.log('🐄 [FEED] Total de animais processados:', feedingAnimals.length);
                    
                    // Preencher select do formulário
                    const selectForm = document.getElementById('feeding-form-animal');
                    if (selectForm) {
                        selectForm.innerHTML = '<option value="">Selecione o animal</option>';
                        if (feedingAnimals.length > 0) {
                            feedingAnimals.forEach(animal => {
                                const option = document.createElement('option');
                                option.value = animal.id || animal.ID;
                                const num = animal.animal_number || animal.animalNumber || '';
                                const name = animal.name || '';
                                const displayName = (num + ' ' + name).trim() || `Animal #${animal.id || animal.ID}`;
                                option.textContent = displayName;
                                selectForm.appendChild(option);
                            });
                            console.log('✅ [FEED] Select do formulário preenchido com', feedingAnimals.length, 'animais');
                        } else {
                            console.warn('⚠️ [FEED] Nenhum animal encontrado para preencher o select');
                            selectForm.innerHTML = '<option value="">Nenhum animal encontrado</option>';
                        }
                    } else {
                        console.error('❌ [FEED] Elemento #feeding-form-animal não encontrado no DOM!');
                    }
                    
                    // Preencher select do filtro
                    const selectFilter = document.getElementById('feeding-filter-animal');
                    if (selectFilter) {
                        const currentValue = selectFilter.value;
                        selectFilter.innerHTML = '<option value="">Todos os animais</option>';
                        if (feedingAnimals.length > 0) {
                            feedingAnimals.forEach(animal => {
                                const option = document.createElement('option');
                                option.value = animal.id || animal.ID;
                                const num = animal.animal_number || animal.animalNumber || '';
                                const name = animal.name || '';
                                const displayName = (num + ' ' + name).trim() || `Animal #${animal.id || animal.ID}`;
                                option.textContent = displayName;
                                selectFilter.appendChild(option);
                            });
                            selectFilter.value = currentValue;
                            console.log('✅ [FEED] Select do filtro preenchido com', feedingAnimals.length, 'animais');
                        }
                    } else {
                        console.error('❌ [FEED] Elemento #feeding-filter-animal não encontrado no DOM!');
                    }
                    
                    if (feedingAnimals.length === 0) {
                        console.warn('⚠️ [FEED] AVISO: Nenhum animal foi carregado!');
                        showCustomMessage('Aviso', 'Nenhum animal encontrado no banco de dados. Cadastre animais primeiro.', 'warning');
                    }
                    
                    return { success: true, data: feedingAnimals };
                })
                .catch(error => {
                    console.error('❌ [FEED] Erro ao carregar animais:', error);
                    showCustomMessage('Erro', 'Erro ao carregar lista de animais: ' + error.message, 'error');
                    return null;
                });
        }

        // Carregar registros de alimentação
        function loadFeedingRecords() {
            const dateFrom = document.getElementById('feeding-filter-date-from')?.value || '';
            const dateTo = document.getElementById('feeding-filter-date-to')?.value || '';
            const animalId = document.getElementById('feeding-filter-animal')?.value || '';
            
            let url = `api/feed.php?action=list&date_from=${dateFrom}&date_to=${dateTo}`;
            if (animalId) {
                url += `&animal_id=${animalId}`;
            }
            
            const tbody = document.getElementById('feeding-records-list');
            if (tbody) {
                tbody.innerHTML = '<tr><td colspan="9" class="px-4 py-8 text-center text-gray-500">Carregando...</td></tr>';
            }
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data) {
                        displayFeedingRecords(data.data);
                    } else {
                        if (tbody) {
                            tbody.innerHTML = '<tr><td colspan="9" class="px-4 py-8 text-center text-gray-500">Nenhum registro encontrado</td></tr>';
                        }
                    }
                })
                .catch(error => {
                    console.error('Erro ao carregar registros:', error);
                    if (tbody) {
                        tbody.innerHTML = '<tr><td colspan="9" class="px-4 py-8 text-center text-red-500">Erro ao carregar registros</td></tr>';
                    }
                });
        }

        // Exibir registros na tabela
        function displayFeedingRecords(records) {
            const tbody = document.getElementById('feeding-records-list');
            if (!tbody) return;
            
            if (records.length === 0) {
                tbody.innerHTML = '<tr><td colspan="9" class="px-4 py-8 text-center text-gray-500">Nenhum registro encontrado</td></tr>';
                return;
            }
            
            tbody.innerHTML = records.map(record => {
                const date = new Date(record.feed_date).toLocaleDateString('pt-BR');
                const animalName = record.animal_name || record.animal_number || `Animal #${record.animal_id}`;
                const shiftNames = {
                    'manha': 'Manhã',
                    'tarde': 'Tarde',
                    'noite': 'Noite',
                    'unico': 'Único'
                };
                const shift = shiftNames[record.shift] || record.shift;
                const cost = record.total_cost ? `R$ ${parseFloat(record.total_cost).toFixed(2).replace('.', ',')}` : '-';
                
                return `
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm text-gray-900">${date}</td>
                        <td class="px-4 py-3 text-sm text-gray-900">${animalName}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">${shift}</td>
                        <td class="px-4 py-3 text-sm text-right text-gray-900">${parseFloat(record.concentrate_kg || 0).toFixed(2).replace('.', ',')}</td>
                        <td class="px-4 py-3 text-sm text-right text-gray-900">${parseFloat(record.roughage_kg || 0).toFixed(2).replace('.', ',')}</td>
                        <td class="px-4 py-3 text-sm text-right text-gray-900">${parseFloat(record.silage_kg || 0).toFixed(2).replace('.', ',')}</td>
                        <td class="px-4 py-3 text-sm text-right text-gray-900">${parseFloat(record.hay_kg || 0).toFixed(2).replace('.', ',')}</td>
                        <td class="px-4 py-3 text-sm text-right text-gray-900 font-medium">${cost}</td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <button onclick="editFeedingRecord(${record.id})" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Editar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>
                                <button onclick="deleteFeedingRecord(${record.id})" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Excluir">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        // Carregar resumo diário
        function loadFeedingDailySummary() {
            const today = new Date().toISOString().split('T')[0];
            fetch(`api/feed.php?action=daily_summary&date=${today}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data) {
                        const summary = data.data;
                        document.getElementById('summary-concentrate').textContent = `${parseFloat(summary.total_concentrate || 0).toFixed(1).replace('.', ',')} kg`;
                        document.getElementById('summary-roughage').textContent = `${parseFloat(summary.total_roughage || 0).toFixed(1).replace('.', ',')} kg`;
                        document.getElementById('summary-silage').textContent = `${parseFloat(summary.total_silage || 0).toFixed(1).replace('.', ',')} kg`;
                        document.getElementById('summary-animals').textContent = summary.total_animals_fed || 0;
                    }
                })
                .catch(error => {
                    console.error('Erro ao carregar resumo:', error);
                });
        }

        // Abrir formulário de alimentação
        function openFeedingForm(recordId = null) {
            console.log('Abrindo formulário de alimentação, recordId:', recordId);
            currentFeedingEditId = recordId;
            const form = document.getElementById('modal-feeding-form');
            const title = document.getElementById('feeding-form-title');
            
            if (!form) {
                console.error('Modal de formulário não encontrado!');
                showCustomMessage('Erro', 'Modal de formulário não encontrado', 'error');
                return;
            }
            
            // Garantir que os animais estão carregados antes de abrir
            if (feedingAnimals.length === 0) {
                console.log('Carregando animais antes de abrir formulário...');
                loadFeedingAnimals().then(() => {
                    openForm();
                }).catch(() => {
                    showCustomMessage('Erro', 'Erro ao carregar animais. Tente novamente.', 'error');
                });
            } else {
                openForm();
            }
            
            function openForm() {
                if (recordId) {
                    if (title) title.textContent = 'Editar Registro de Alimentação';
                    loadFeedingRecord(recordId);
                } else {
                    if (title) title.textContent = 'Novo Registro de Alimentação';
                    resetFeedingForm();
                }
                // Usar a classe 'show' que faz display: flex conforme o CSS
                form.style.display = '';
                form.classList.add('show');
                document.body.style.overflow = 'hidden';
                console.log('Formulário aberto com sucesso');
            }
        }

        // Fechar formulário
        function closeFeedingForm() {
            const form = document.getElementById('modal-feeding-form');
            if (form) {
                form.classList.remove('show');
                document.body.style.overflow = '';
                resetFeedingForm();
                currentFeedingEditId = null;
            }
        }

        // Resetar formulário
        function resetFeedingForm() {
            document.getElementById('feeding-form').reset();
            document.getElementById('feeding-form-id').value = '';
            document.getElementById('feeding-form-date').value = new Date().toISOString().split('T')[0];
        }

        // Carregar registro para edição
        function loadFeedingRecord(id) {
            fetch(`api/feed.php?action=get&id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data) {
                        const record = data.data;
                        document.getElementById('feeding-form-id').value = record.id;
                        document.getElementById('feeding-form-animal').value = record.animal_id;
                        document.getElementById('feeding-form-date').value = record.feed_date;
                        document.getElementById('feeding-form-shift').value = record.shift;
                        document.getElementById('feeding-form-type').value = record.feed_type || '';
                        document.getElementById('feeding-form-concentrate').value = record.concentrate_kg || '';
                        document.getElementById('feeding-form-roughage').value = record.roughage_kg || '';
                        document.getElementById('feeding-form-silage').value = record.silage_kg || '';
                        document.getElementById('feeding-form-hay').value = record.hay_kg || '';
                        document.getElementById('feeding-form-brand').value = record.feed_brand || '';
                        document.getElementById('feeding-form-protein').value = record.protein_percentage || '';
                        document.getElementById('feeding-form-cost').value = record.cost_per_kg || '';
                        document.getElementById('feeding-form-notes').value = record.notes || '';
                    }
                })
                .catch(error => {
                    console.error('Erro ao carregar registro:', error);
                    showCustomMessage('Erro', 'Erro ao carregar registro', 'error');
                });
        }

        // Editar registro
        function editFeedingRecord(id) {
            openFeedingForm(id);
        }

        // Deletar registro
        function deleteFeedingRecord(id) {
            showCustomConfirm(
                'Confirmar Exclusão',
                'Tem certeza que deseja excluir este registro de alimentação?',
                function() {
                    fetch('api/feed.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'delete',
                            id: id
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showCustomMessage('Sucesso', 'Registro excluído com sucesso!', 'success');
                            loadFeedingRecords();
                            loadFeedingDailySummary();
                        } else {
                            showCustomMessage('Erro', data.error || 'Erro ao excluir registro', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Erro:', error);
                        showCustomMessage('Erro', 'Erro ao excluir registro', 'error');
                    });
                }
            );
        }

        // Submeter formulário
        const feedingFormElement = document.getElementById('feeding-form');
        if (feedingFormElement) {
            feedingFormElement.addEventListener('submit', function(e) {
                e.preventDefault();
                
                console.log('Submetendo formulário de alimentação...');
                
                const animalId = document.getElementById('feeding-form-animal')?.value;
                if (!animalId) {
                    showCustomMessage('Erro', 'Por favor, selecione um animal', 'error');
                    return;
                }
                
                const formData = {
                    action: currentFeedingEditId ? 'update' : 'create',
                    animal_id: animalId,
                    feed_date: document.getElementById('feeding-form-date')?.value || new Date().toISOString().split('T')[0],
                    shift: document.getElementById('feeding-form-shift')?.value || 'unico',
                    feed_type: document.getElementById('feeding-form-type')?.value || null,
                    concentrate_kg: document.getElementById('feeding-form-concentrate')?.value || 0,
                    roughage_kg: document.getElementById('feeding-form-roughage')?.value || 0,
                    silage_kg: document.getElementById('feeding-form-silage')?.value || 0,
                    hay_kg: document.getElementById('feeding-form-hay')?.value || 0,
                    feed_brand: document.getElementById('feeding-form-brand')?.value || null,
                    protein_percentage: document.getElementById('feeding-form-protein')?.value || null,
                    cost_per_kg: document.getElementById('feeding-form-cost')?.value || null,
                    notes: document.getElementById('feeding-form-notes')?.value || null
                };
                
                if (currentFeedingEditId) {
                    formData.id = currentFeedingEditId;
                }
                
                console.log('Dados do formulário:', formData);
                
                fetch('api/feed.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(formData)
                })
                .then(response => {
                    console.log('Resposta recebida:', response);
                    return response.json();
                })
                .then(data => {
                    console.log('Dados recebidos:', data);
                    if (data.success) {
                        showCustomMessage('Sucesso', currentFeedingEditId ? 'Registro atualizado com sucesso!' : 'Registro criado com sucesso!', 'success');
                        closeFeedingForm();
                        loadFeedingRecords();
                        loadFeedingDailySummary();
                    } else {
                        showCustomMessage('Erro', data.error || 'Erro ao salvar registro', 'error');
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    showCustomMessage('Erro', 'Erro ao salvar registro: ' + error.message, 'error');
                });
            });
        }

        // Fechar modal de formulário ao clicar fora
        document.getElementById('modal-feeding-form')?.addEventListener('click', function(e) {
            if (e.target === this) {
                closeFeedingForm();
            }
        });

        // Inicializar modal quando carregar
        function initFeedingModal() {
            console.log('🔄 [FEED] Inicializando modal de alimentação...');
            // Carregar animais primeiro, depois os registros
            loadFeedingAnimals().then((result) => {
                console.log('✅ [FEED] Animais carregados:', feedingAnimals.length);
                loadFeedingRecords();
                loadFeedingDailySummary();
            }).catch(error => {
                console.error('❌ [FEED] Erro ao carregar animais:', error);
                // Mesmo com erro, tentar carregar registros
                loadFeedingRecords();
                loadFeedingDailySummary();
            });
        }

        // Tornar todas as funções globais - executar após todas as declarações
        (function() {
            // Aguardar um pouco para garantir que todas as funções foram declaradas
            setTimeout(function() {
                try {
                    if (typeof openFeedingForm === 'function') {
                        window.openFeedingForm = openFeedingForm;
                    }
                    if (typeof closeFeedingForm === 'function') {
                        window.closeFeedingForm = closeFeedingForm;
                    }
                    if (typeof loadFeedingRecords === 'function') {
                        window.loadFeedingRecords = loadFeedingRecords;
                    }
                    if (typeof editFeedingRecord === 'function') {
                        window.editFeedingRecord = editFeedingRecord;
                    }
                    if (typeof deleteFeedingRecord === 'function') {
                        window.deleteFeedingRecord = deleteFeedingRecord;
                    }
                    if (typeof initFeedingModal === 'function') {
                        window.initFeedingModal = initFeedingModal;
                    }
                    if (typeof loadFeedingAnimals === 'function') {
                        window.loadFeedingAnimals = loadFeedingAnimals;
                    }
                    console.log('✅ Funções de alimentação expostas globalmente');
                } catch(e) {
                    console.error('Erro ao expor funções:', e);
                }
            }, 500);
        })();
    </script>
    
    <!-- Script adicional para garantir que as funções estejam disponíveis -->
    <script>
        // Garantir que openFeedingForm está disponível globalmente - versão de fallback
        window.openFeedingForm = window.openFeedingForm || function(recordId) {
            console.log('🔵 openFeedingForm chamado (fallback), recordId:', recordId);
            
            // Tentar encontrar a função no escopo
            if (typeof openFeedingForm === 'function') {
                return openFeedingForm(recordId);
            }
            
            // Se não encontrar, tentar abrir o modal diretamente
            const formModal = document.getElementById('modal-feeding-form');
            if (formModal) {
                console.log('Abrindo modal diretamente...');
                formModal.style.display = '';
                formModal.classList.add('show');
                document.body.style.overflow = 'hidden';
                
                // Carregar animais se necessário
                if (typeof loadFeedingAnimals === 'function') {
                    loadFeedingAnimals();
                } else if (typeof window.loadFeedingAnimals === 'function') {
                    window.loadFeedingAnimals();
                } else {
                    // Fazer fetch direto
                    fetch('api/feed.php?action=animals')
                        .then(r => r.json())
                        .then(data => {
                            if (data.success && data.data) {
                                const select = document.getElementById('feeding-form-animal');
                                if (select) {
                                    select.innerHTML = '<option value="">Selecione o animal</option>';
                                    data.data.forEach(animal => {
                                        const opt = document.createElement('option');
                                        opt.value = animal.id;
                                        opt.textContent = `${animal.animal_number || ''} ${animal.name || ''}`.trim();
                                        select.appendChild(opt);
                                    });
                                }
                            }
                        });
                }
            } else {
                alert('Erro: Modal de formulário não encontrado!');
            }
        };
        
        // Testar se o modal existe
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🔍 Verificando elementos de alimentação...');
            const modal = document.getElementById('modal-feeding');
            const formModal = document.getElementById('modal-feeding-form');
            const form = document.getElementById('feeding-form');
            
            console.log('Modal principal:', modal ? '✅ Encontrado' : '❌ Não encontrado');
            console.log('Modal formulário:', formModal ? '✅ Encontrado' : '❌ Não encontrado');
            console.log('Formulário:', form ? '✅ Encontrado' : '❌ Não encontrado');
            
            if (!modal) {
                console.error('❌ CRÍTICO: Modal de alimentação não encontrado no DOM!');
            }
            if (!formModal) {
                console.error('❌ CRÍTICO: Modal de formulário não encontrado no DOM!');
            }
            if (!form) {
                console.error('❌ CRÍTICO: Formulário não encontrado no DOM!');
            }
            
            // Verificar se as funções estão disponíveis
            console.log('openFeedingForm disponível:', typeof window.openFeedingForm);
            console.log('loadFeedingRecords disponível:', typeof window.loadFeedingRecords);
        });
    </script>
</body>
</html>