<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Funcionário - Sistema Leiteiro</title>
    
    <!-- PWA Meta Tags -->
    <meta name="description" content="Painel do Funcionário - Sistema completo para gestão de produção leiteira, controle de qualidade e relatórios">
    <meta name="theme-color" content="#166534">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="LacTech Funcionário">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="msapplication-TileColor" content="#166534">
    <meta name="msapplication-config" content="/browserconfig.xml">
    
    <!-- PWA Icons -->
    <link rel="icon" href="https://i.postimg.cc/vmrkgDcB/lactech.png" type="image/x-icon">
    <link rel="apple-touch-icon" href="https://i.postimg.cc/vmrkgDcB/lactech.png">
    <link rel="apple-touch-icon" sizes="72x72" href="https://i.postimg.cc/vmrkgDcB/lactech.png">
    <link rel="apple-touch-icon" sizes="96x96" href="https://i.postimg.cc/vmrkgDcB/lactech.png">
    <link rel="apple-touch-icon" sizes="128x128" href="https://i.postimg.cc/vmrkgDcB/lactech.png">
    <link rel="apple-touch-icon" sizes="144x144" href="https://i.postimg.cc/vmrkgDcB/lactech.png">
    <link rel="apple-touch-icon" sizes="152x152" href="https://i.postimg.cc/vmrkgDcB/lactech.png">
    <link rel="apple-touch-icon" sizes="192x192" href="https://i.postimg.cc/vmrkgDcB/lactech.png">
    <link rel="apple-touch-icon" sizes="384x384" href="https://i.postimg.cc/vmrkgDcB/lactech.png">
    <link rel="apple-touch-icon" sizes="512x512" href="https://i.postimg.cc/vmrkgDcB/lactech.png">
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="manifest.json">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2.39.0/dist/umd/supabase.min.js"></script>
    <script src="assets/js/config.js"></script>
    <script src="assets/js/chat-sync-service.js"></script>
    <script src="assets/js/loading-screen.js"></script>
    <script src="assets/js/api.js"></script>
    <script src="assets/js/modal-system.js"></script>
    <script src="assets/js/offline-manager.js"></script>
    <script src="assets/js/offline-loading.js"></script>
    <script src="assets/js/ecosystem-manager.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="assets/css/loading-screen.css" rel="stylesheet">
    <link href="assets/css/offline-loading.css" rel="stylesheet">
    <link href="assets/css/ecosystem.css" rel="stylesheet">
    
    <style>
        /* Gradiente de texto estilo Xandria Store */
        .gradient-text {
            background: linear-gradient(135deg, #01875f, #10b981);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        /* CSS REMOVIDO - USANDO APENAS TAILWIND */
        
        /* Card hover effect estilo Xandria Store */
        .card-hover {
            transition: all 0.3s ease;
        }
        
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        
        /* Sistema de Abas */
        .tab-content {
            display: none;
            animation: fadeIn 0.3s ease-in-out;
        }

        .tab-content.active {
            display: block;
        }

        .tab-button {
            color: #6b7280;
            background: transparent;
        }

        .tab-button:hover {
            color: #10b981;
            background: rgba(16, 185, 129, 0.1);
        }

        .tab-button.active {
            color: white;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }

        /* Botões de aba no header */
        .header-tab-button {
            color: rgba(255, 255, 255, 0.8);
            background: transparent;
        }

        .header-tab-button:hover {
            color: white;
            background: rgba(255, 255, 255, 0.1);
        }

        .header-tab-button.active {
            color: white;
            background: rgba(255, 255, 255, 0.2);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .mobile-tab-btn {
            color: #6b7280;
        }

        .mobile-tab-btn:hover {
            color: #10b981;
            background: rgba(16, 185, 129, 0.1);
        }

        .mobile-tab-btn.active {
            color: white;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }

        /* Animação de fade in para as abas */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Ajuste de padding para mobile com navegação */
        @media (max-width: 1024px) {
            body {
                padding-bottom: 80px;
            }
        }
        
    </style>
</head>
<body class="gradient-mesh antialiased">

    <!-- Header -->
    <header class="gradient-forest shadow-xl sticky top-0 z-40 border-b border-forest-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center space-x-3">
                    <div class="header-logo-container">
                        <img src="assets/img/lactech-logo.png" alt="LacTech Logo" class="header-logo">
                    </div>
                    <div>
                        <h1 class="text-lg font-bold text-white tracking-tight">PAINEL DO FUNCIONÁRIO</h1>
                        <p class="text-xs text-white" id="farmNameHeader">Carregando fazenda...</p>
                    </div>
                </div>

                <!-- Navegação por Abas -->
                <div class="flex items-center space-x-1">
                    <button onclick="switchToTab('dashboard')" 
                            class="header-tab-button active px-3 py-2 rounded-lg text-sm font-medium transition-all duration-300 flex items-center space-x-2"
                            data-tab="dashboard">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                        </svg>
                        <span>Dashboard</span>
                    </button>
                    
                    <button onclick="switchToTab('register')" 
                            class="header-tab-button px-3 py-2 rounded-lg text-sm font-medium transition-all duration-300 flex items-center space-x-2"
                            data-tab="register">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        <span>Registrar</span>
                    </button>
                    
                    <button onclick="switchToTab('history')" 
                            class="header-tab-button px-3 py-2 rounded-lg text-sm font-medium transition-all duration-300 flex items-center space-x-2"
                            data-tab="history">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>Histórico</span>
                    </button>
                </div>

                <div class="flex items-center space-x-4">
                    <!-- Botão de retorno à conta do gerente (apenas para contas secundárias) -->
                    <div id="returnToManagerBtn" class="hidden">
                        <button onclick="returnToManagerAccount()" class="flex items-center space-x-2 text-white hover:text-forest-200 p-2 rounded-lg transition-all bg-white bg-opacity-10 hover:bg-opacity-20">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            <span class="text-sm font-medium">Voltar ao Gerente</span>
                        </button>
                    </div>
                    
                    <!-- Botão de Notificações -->
                    <button onclick="openNotificationsModal()" class="relative p-2 text-white hover:text-forest-200 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-5 5v-5zM9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span id="notificationCounter" class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center hidden">0</span>
                        <!-- Indicador de tempo real -->
                        <div id="realTimeIndicator" class="absolute -bottom-1 -right-1 w-3 h-3 bg-green-500 rounded-full animate-pulse hidden" title="Sistema de atualização automática ativo"></div>
                    </button>
                    
                    <!-- Botão Xandria Store -->
                    <button onclick="openXandriaStore()" class="p-2 text-white hover:text-forest-200 transition-colors" title="Acessar Xandria Store">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"></path>
                        </svg>
                    </button>

                    <!-- Botão Chat -->
                    <button onclick="openChatModal()" class="p-2 text-white hover:text-forest-200 transition-colors" title="Chat da Fazenda">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                        </svg>
                    </button>
                    
                    
                    <button onclick="openProfileModal()" class="flex items-center space-x-3 text-white hover:text-forest-200 p-2 rounded-lg transition-all">
                        <div class="relative w-8 h-8">
                            <img id="headerProfilePhoto" src="" alt="Foto de Perfil" class="w-full h-full object-cover rounded-full hidden">
                            <div id="headerProfileIcon" class="w-8 h-8 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            </div>
                        </div>
                        <div class="text-left hidden sm:block">
                            <div class="text-sm font-semibold" id="employeeName">Funcionário</div>
                            <div class="text-xs text-forest-200">Funcionário</div>
                        </div>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 pb-24 md:pb-8">
        
        <!-- Dashboard Tab -->
        <div id="dashboard-tab" class="tab-content active">
            <!-- Welcome Section -->
            <div class="gradient-forest rounded-2xl p-6 mb-6 text-white shadow-xl relative overflow-hidden">
                <div class="relative z-10">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-2xl font-bold mb-2">Olá, <span id="employeeWelcome">Funcionário</span>!</h2>
                            <p class="text-forest-200 text-base font-medium mb-3">Registre a produção diária de leite</p>
                            <div class="flex items-center space-x-4">
                                <div class="text-xs font-medium">Última atualização: <span id="currentDateTime">Agora</span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="data-card rounded-2xl p-4 text-center">
                    <div class="w-12 h-12 gradient-forest rounded-xl flex items-center justify-center mx-auto mb-3 shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                        </svg>
                    </div>
                    <div class="text-xl font-bold text-slate-900 mb-1" id="todayVolume">0.0 L</div>
                    <div class="text-xs text-slate-500 font-medium">Hoje</div>
                    <div class="text-xs text-slate-600 font-semibold mt-1">Volume registrado</div>
                </div>
                
                <div class="data-card rounded-2xl p-4 text-center">
                    <div class="w-12 h-12 bg-blue-500 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <div class="text-xl font-bold text-slate-900 mb-1" id="weekAverage">0.0 L</div>
                    <div class="text-xs text-slate-500 font-medium">Média Semanal</div>
                    <div class="text-xs text-slate-600 font-semibold mt-1">Últimos 7 dias</div>
                </div>
                
                <div class="data-card rounded-2xl p-4 text-center">
                    <div class="w-12 h-12 bg-green-500 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="text-xl font-bold text-slate-900 mb-1" id="todayRecords">0</div>
                    <div class="text-xs text-slate-500 font-medium">Registros</div>
                    <div class="text-xs text-slate-600 font-semibold mt-1">Hoje</div>
                </div>
                
                <div class="data-card rounded-2xl p-4 text-center">
                    <div class="w-12 h-12 bg-purple-500 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                    </div>
                    <div class="text-xl font-bold text-slate-900 mb-1" id="bestDay">0.0 L</div>
                    <div class="text-xs text-slate-500 font-medium">Melhor Dia</div>
                    <div class="text-xs text-slate-600 font-semibold mt-1">Este mês</div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Recent Activity -->
                <div class="data-card rounded-2xl p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-slate-900">Atividades Recentes</h3>
                        <!-- Botão de teste para tempo real -->
                        <button id="testRealtimeBtn" onclick="testRealtimeUpdate()" 
                                class="px-3 py-1 bg-green-100 text-green-700 text-xs rounded-lg hover:bg-green-200 transition-all">
                            🧪 Testar Tempo Real
                        </button>
                    </div>
                    <div class="space-y-3" id="activityList">
                        <div class="text-center py-8">
                            <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <p class="text-gray-500 text-sm">Carregando atividades...</p>
                        </div>
                    </div>
                </div>

                <!-- Production Chart -->
                <div class="data-card rounded-2xl p-6 mb-6">
                    <h3 class="text-lg font-bold text-slate-900 mb-4">Produção dos Últimos 7 Dias</h3>
                    <div class="h-32 relative">
                        <canvas id="productionChart" width="400" height="128"></canvas>
                        </div>
                        </div>
                    </div>

        </div>

        <!-- Registrar Tab -->
        <div id="register-tab" class="tab-content">
            <!-- Welcome Section -->
            <div class="gradient-forest rounded-2xl p-6 mb-6 text-white shadow-xl relative overflow-hidden">
                <div class="relative z-10">
                    <div class="flex items-center space-x-4">
                        <div class="bg-white bg-opacity-20 p-4 rounded-2xl backdrop-blur-sm">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold mb-2">Registrar Produção</h2>
                            <p class="text-forest-200 text-base font-medium">Registre a produção diária de leite da fazenda</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Formulário -->
            <div class="data-card rounded-2xl p-6">
                <form id="newProductionForm" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Volume -->
                        <div>
                            <label for="newVolume" class="block text-sm font-semibold text-gray-700 mb-2">
                                Volume de Leite (Litros) *
                            </label>
                            <div class="relative">
                                <input type="number" 
                                       id="newVolume" 
                                       name="volume" 
                                       step="0.1" 
                                       min="0" 
                                       max="10000" 
                                       required
                                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none bg-white text-gray-900" 
                                       placeholder="Ex: 150.5">
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 text-sm font-medium">L</span>
                                </div>
                            </div>
                        </div>

                        <!-- Data -->
                        <div>
                            <label for="newProductionDate" class="block text-sm font-semibold text-gray-700 mb-2">
                                Data da Produção *
                            </label>
                            <input type="date" 
                                   id="newProductionDate" 
                                   name="productionDate" 
                                   required
                                   class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none bg-white text-gray-900">
                        </div>

                        <!-- Temperatura -->
                        <div>
                            <label for="newTemperature" class="block text-sm font-semibold text-gray-700 mb-2">
                                Temperatura (°C)
                            </label>
                            <div class="relative">
                                <input type="number" 
                                       id="newTemperature" 
                                       name="temperature" 
                                       step="0.1" 
                                       min="0" 
                                       max="50"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none bg-white text-gray-900" 
                                       placeholder="Ex: 4.2">
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 text-sm font-medium">°C</span>
                                </div>
                            </div>
                        </div>

                        <!-- Observações -->
                        <div>
                            <label for="newNotes" class="block text-sm font-semibold text-gray-700 mb-2">
                                Observações
                            </label>
                            <textarea id="newNotes" 
                                      name="notes" 
                                      rows="3"
                                      class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none bg-white text-gray-900 resize-none"
                                      placeholder="Observações sobre a produção (opcional)"></textarea>
                        </div>
                    </div>

                    <!-- Botões -->
                    <div class="flex flex-col sm:flex-row gap-4 pt-6 border-t border-gray-200">
                        <button type="button" 
                                onclick="clearNewForm()"
                                class="flex-1 sm:flex-none px-6 py-3 border border-gray-300 text-gray-700 font-semibold rounded-xl hover:bg-gray-50 transition-all flex items-center justify-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                            Limpar
                        </button>
                        
                        <button type="submit" 
                                class="flex-1 px-8 py-3 gradient-forest text-white font-semibold rounded-xl hover:shadow-lg transition-all flex items-center justify-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Registrar Produção
                        </button>
                    </div>
                </form>
            </div>
        </div>

        </div>

        <!-- Histórico Tab -->
        <div id="history-tab" class="tab-content">
            <!-- Welcome Section -->
            <div class="gradient-forest rounded-2xl p-6 mb-6 text-white shadow-xl relative overflow-hidden">
                <div class="relative z-10">
                    <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                        <div class="flex items-center space-x-4">
                            <div class="bg-white bg-opacity-20 p-4 rounded-2xl backdrop-blur-sm">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold mb-2">Histórico de Produção</h2>
                                <p class="text-forest-200 text-base font-medium">Visualize todos os registros de produção</p>
                            </div>
                        </div>
                        
                        <!-- Filtros -->
                        <div class="flex flex-col sm:flex-row items-center gap-4">
                                <select id="historyFilter" class="px-4 py-3 border-2 border-white/30 rounded-xl text-gray-900 placeholder-white/70 bg-white/90 backdrop-blur-sm focus:border-white focus:ring-4 focus:ring-white/20 focus:outline-none">
                                <option value="all">Todos os registros</option>
                                <option value="today">Hoje</option>
                                <option value="week">Esta semana</option>
                                <option value="month">Este mês</option>
                            </select>
                            <button onclick="exportHistory()" 
                                    class="px-6 py-3 bg-white/20 text-white font-semibold rounded-xl hover:bg-white/30 transition-all duration-300 backdrop-blur-sm flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-4-4m4 4l4-4m3 8H5a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v1"></path>
                                </svg>
                                Exportar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lista de Registros -->
            <div class="data-card rounded-2xl p-6">
                <div id="historyList" class="min-h-96">
                    <!-- Os registros serão carregados aqui via JavaScript -->
                    <div class="flex flex-col items-center justify-center py-12 text-gray-500">
                        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-forest-600 mx-auto mb-4"></div>
                        <p class="text-lg font-semibold">Carregando histórico...</p>
                        <p class="text-sm text-gray-400 mt-1">Por favor, aguarde</p>
                    </div>
                </div>
            </div>
        </div>

        </div>



    </main>


    <!-- Navegação Mobile -->
    <nav class="lg:hidden fixed bottom-0 left-0 right-0 z-50 bg-white/95 backdrop-blur-md border-t border-gray-200/50">
        <div class="flex items-center justify-around px-4 py-2">
            <button onclick="switchToTab('dashboard')" 
                    class="mobile-tab-btn active flex flex-col items-center py-2 px-3 rounded-xl transition-all duration-300"
                    data-tab="dashboard">
                <svg class="w-5 h-5 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                </svg>
                <span class="text-xs font-medium">Dashboard</span>
            </button>
            
            <button onclick="switchToTab('register')" 
                    class="mobile-tab-btn flex flex-col items-center py-2 px-3 rounded-xl transition-all duration-300"
                    data-tab="register">
                <svg class="w-5 h-5 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                <span class="text-xs font-medium">Registrar</span>
            </button>
            
            <button onclick="switchToTab('history')" 
                    class="mobile-tab-btn flex flex-col items-center py-2 px-3 rounded-xl transition-all duration-300"
                    data-tab="history">
                <svg class="w-5 h-5 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span class="text-xs font-medium">Histórico</span>
            </button>
        </div>
    </nav>

    <!-- Sidebar de Notificações -->
    <div id="notificationsModal" class="fixed inset-0 z-[99999] hidden transition-all duration-300">
        <!-- Overlay -->
        <div class="absolute inset-0 bg-black bg-opacity-50 backdrop-blur-sm" onclick="closeNotificationsModal()"></div>
        
        <!-- Sidebar -->
        <div class="absolute right-0 top-0 h-full w-full max-w-md bg-white shadow-2xl transform transition-transform duration-300 translate-x-full flex flex-col" id="notificationsModalContent">
            <!-- Header -->
            <div class="flex-shrink-0 p-6 bg-white border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 bg-forest-100 rounded-2xl flex items-center justify-center">
                            <svg class="w-7 h-7 text-forest-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-5 5v-5zM9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-black">Notificações</h3>
                            <p class="text-gray-600 text-sm">Solicitações e alertas do sistema</p>
                        </div>
                    </div>
                    <button onclick="closeNotificationsModal()" class="w-10 h-10 hover:bg-gray-100 rounded-xl flex items-center justify-center transition-all">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            
            <!-- Content -->
            <div class="flex-1 overflow-y-auto p-6">
                <div id="notificationsList" class="space-y-3">
                    <!-- Estado vazio -->
                    <div class="text-center py-12">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-black mb-2">Tudo em dia!</h3>
                        <p class="text-gray-600">Não há notificações pendentes no momento</p>
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="flex-shrink-0 p-6 bg-white border-t border-gray-200">
                <button onclick="closeNotificationsModal()" class="w-full px-4 py-3 bg-forest-600 hover:bg-forest-700 text-white rounded-xl transition-all font-medium shadow-lg hover:shadow-xl">
                    Fechar
                </button>
            </div>
        </div>
    </div>

    <!-- Modal de Perfil do Funcionário -->
    <div id="profileModal" class="fullscreen-modal">
        <div class="modal-content">
            <!-- Header do Modal -->
            <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 z-10">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-bold text-gray-900">Perfil do Funcionário</h2>
                    <button onclick="closeProfileModal()" class="p-2 hover:bg-gray-100 rounded-lg transition-all">
                        <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            
            <!-- Conteúdo do Perfil -->
            <div class="p-6 space-y-6">
                <!-- Header do Perfil -->
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center space-x-4">
                        <div class="relative">
                            <!-- Foto do usuário -->
                            <img id="modalProfilePhoto" src="" alt="Foto de Perfil" class="w-16 h-16 object-cover rounded-2xl shadow-lg hidden">
                            <!-- Ícone padrão -->
                            <div id="modalProfileIcon" class="w-16 h-16 gradient-forest rounded-2xl flex items-center justify-center shadow-lg">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            </div>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-slate-900" id="profileName">Carregando...</h2>
                            <p class="text-slate-600 text-base">Funcionário</p>
                            <p class="text-sm text-slate-500" id="profileFarmName">Carregando...</p>
                        </div>
                    </div>
                        <div>
                        <input type="file" id="photoUpload" accept="image/*" class="hidden" onchange="uploadPhoto(this)">
                        <button onclick="document.getElementById('photoUpload').click()" class="px-4 py-2 bg-forest-500 text-white rounded-lg text-sm hover:bg-forest-600">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            Alterar Foto
                        </button>
                    </div>
                </div>
                
                <!-- Informações Pessoais -->
                <div class="bg-white rounded-2xl p-6 border border-gray-200">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-lg font-semibold text-gray-900">Informações Pessoais</h4>
                        <button id="editProfileBtn" onclick="toggleProfileEdit()" class="px-4 py-2 text-sm bg-forest-600 hover:bg-forest-700 text-white font-medium rounded-lg transition-all">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                            Editar
                        </button>
                    </div>
                    
                    <!-- Modo Visualização -->
                    <div id="profileViewMode" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-800 mb-1">Nome Completo</label>
                            <p class="text-gray-900 font-semibold text-base" id="profileFullName">Carregando...</p>
                                </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-800 mb-1">Email</label>
                            <p class="text-gray-900 font-semibold text-base" id="profileEmail2">Carregando...</p>
                            </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-800 mb-1">WhatsApp</label>
                            <p class="text-gray-900 font-semibold text-base" id="profileWhatsApp">Não informado</p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-800 mb-1">Cargo</label>
                            <p class="text-gray-900 font-semibold text-base">Funcionário</p>
                        </div>
                    </div>
                    
                    <!-- Modo Edição -->
                    <div id="profileEditMode" class="hidden">
                        <form id="updateProfileForm" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-800 mb-2">Nome Completo</label>
                                    <input type="text" id="editProfileName" name="name" class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none bg-white" placeholder="Digite seu nome completo">
                                        </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-800 mb-2">Email</label>
                                    <input type="email" id="editProfileEmail" name="email" class="w-full px-4 py-3 border border-slate-200 rounded-xl bg-gray-100 text-gray-500" readonly placeholder="Email não pode ser alterado">
                                    </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-800 mb-2">WhatsApp</label>
                                    <input type="tel" id="editProfileWhatsApp" name="whatsapp" class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none bg-white" placeholder="(00) 00000-0000">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-800 mb-2">Cargo</label>
                                    <input type="text" value="Funcionário" class="w-full px-4 py-3 border border-slate-200 rounded-xl bg-gray-100 text-gray-500" readonly>
                                </div>
                            </div>
                            
                            <div class="flex justify-end space-x-3 pt-4">
                                <button type="button" onclick="cancelProfileEdit()" class="px-6 py-3 border border-gray-300 text-gray-700 font-semibold rounded-xl hover:bg-gray-50 transition-all">
                                    Cancelar
                                </button>
                                <button type="submit" class="px-6 py-3 gradient-forest text-white font-semibold rounded-xl hover:shadow-lg transition-all">
                                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    Salvar Alterações
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Alterar Senha -->
                <div class="bg-white rounded-2xl p-6 border border-gray-200">
                    <h4 class="text-lg font-semibold text-gray-900 mb-4">Alterar Senha</h4>
                    <form id="changePasswordForm" class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Senha Atual</label>
                            <input type="password" required name="current_password" class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none bg-white" placeholder="Digite sua senha atual">
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">Nova Senha</label>
                                <input type="password" required name="new_password" class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none bg-white" placeholder="Nova senha">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">Confirmar Nova Senha</label>
                                <input type="password" required name="confirm_password" class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none bg-white" placeholder="Confirme a nova senha">
                            </div>
                        </div>
                        <button type="submit" class="px-6 py-3 gradient-forest text-white font-semibold rounded-xl hover:shadow-lg transition-all">
                            Alterar Senha
                        </button>
                    </form>
                </div>
                
                <!-- Minhas Solicitações de Senha -->
                <div class="bg-white rounded-2xl p-6 border border-gray-200">
                    <h4 class="text-lg font-semibold text-black mb-4">Minhas Solicitações de Senha</h4>
                    <div class="flex items-center justify-between">
                        <div>
                            <h5 class="font-medium text-black">Gerenciar Solicitações</h5>
                            <p class="text-sm text-gray-600">Visualize e cancele suas solicitações de alteração de senha</p>
                        </div>
                        <button onclick="openMyPasswordRequests()" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition-all">
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                            </svg>
                            Ver Solicitações
                        </button>
                    </div>
                </div>
                
                <!-- Sair do Sistema -->
                <div class="bg-white rounded-2xl p-6 border border-red-200">
                    <h4 class="text-lg font-semibold text-red-900 mb-4">Zona de Perigo</h4>
                    <div class="flex items-center justify-between">
                        <div>
                            <h5 class="font-medium text-red-900">Sair do Sistema</h5>
                            <p class="text-sm text-red-600">Encerrar sua sessão atual</p>
                        </div>
                        <button onclick="logout()" class="px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-xl transition-all">
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                            </svg>
                            Sair
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Chat - Full Screen -->
    <div id="chatModal" class="fixed inset-0 bg-white z-[99999] hidden flex" style="display: none;">
        <!-- Coluna Esquerda - Lista de Funcionários -->
        <div class="w-80 bg-white border-r border-gray-200 flex flex-col hidden lg:flex" id="chatSidebar">
            <!-- Header -->
            <div class="p-4 border-b border-gray-200">
                <div class="flex items-center justify-between mb-3">
                    <h1 class="text-xl font-bold text-gray-900">Chat</h1>
                    <button onclick="closeChatModal()" class="p-1.5 hover:bg-gray-100 rounded-lg transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <!-- Barra de Pesquisa -->
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <input 
                        type="text" 
                        id="chatSearchInput"
                        placeholder="Pesquisar funcionários..."
                        class="w-full pl-9 pr-3 py-2.5 bg-gray-50 rounded-lg border-0 focus:ring-2 focus:ring-green-500 focus:bg-white transition-colors text-sm"
                        onkeyup="searchEmployees(event)"
                    >
                </div>
            </div>

            <!-- Lista de Funcionários -->
            <div class="flex-1 overflow-y-auto">
                <div class="p-3">
                    <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Funcionários Online</h3>
                    <div id="onlineEmployees" class="flex space-x-2 mb-4 overflow-x-auto pb-2">
                        <!-- Funcionários online serão carregados aqui -->
                    </div>

                    <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Conversas</h3>
                    <div id="employeesList" class="space-y-1">
                        <!-- Lista de funcionários será carregada aqui -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Coluna Direita - Conversa -->
        <div class="flex-1 flex flex-col">
            <!-- Header da Conversa -->
            <div id="chatHeader" class="p-4 border-b border-gray-200 bg-white hidden">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <!-- Botão para mostrar sidebar em mobile -->
                        <button onclick="toggleChatSidebar()" class="lg:hidden p-2 hover:bg-gray-100 rounded-lg transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"></path>
                            </svg>
                        </button>
                        <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-green-600 rounded-full flex items-center justify-center">
                            <span class="text-white font-semibold text-sm" id="selectedEmployeeInitial">?</span>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900" id="selectedEmployeeName">Selecione um funcionário</h3>
                            <p class="text-sm text-gray-500" id="selectedEmployeeStatus">Para começar uma conversa</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button onclick="startVideoCall()" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors" title="Vídeo chamada">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                            </svg>
                        </button>
                        <button onclick="startAudioCall()" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors" title="Ligar">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                            </svg>
                        </button>
                        <button onclick="exitChat()" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors" title="Fechar chat">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Área de Mensagens -->
            <div class="flex-1 overflow-y-auto bg-gray-50 p-4">
                <!-- UI Inicial - Mostrar quando nenhum funcionário estiver selecionado -->
                <div id="initialChatUI" class="flex flex-col items-center justify-center h-full min-h-96">
                    <div class="text-center max-w-md mx-auto">
                        <!-- Imagem de fundo responsiva -->
                        <div class="mb-6">
                            <img src="assets/img/fundo.png" 
                                 alt="Selecione um usuário" 
                                 class="w-full max-w-xs mx-auto"
                                 style="aspect-ratio: 1080/1350; object-fit: cover;">
                        </div>
                        
                        <!-- Texto de instrução -->
                        <h2 class="text-2xl font-bold text-gray-800 mb-2">
                            Selecione um usuário
                        </h2>
                        <p class="text-gray-600 text-lg">
                            para iniciar uma conversa
                        </p>
                        
                        <!-- Ícone decorativo -->
                        <div class="mt-6">
                            <svg class="w-12 h-12 text-gray-400 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
                
                <!-- Chat Messages - Oculto inicialmente -->
                <div id="chatMessages" class="space-y-4 hidden">
                    <!-- Mensagens serão carregadas aqui -->
                </div>
            </div>

            <!-- Input de Mensagem -->
            <div id="chatInputArea" class="p-4 bg-white border-t border-gray-200 hidden">
                <!-- Área de Gravação de Áudio (inicialmente oculta) -->
                <div id="audioRecordingArea" class="hidden mb-4 p-6 bg-gradient-to-r from-red-50 to-pink-50 border border-red-200 rounded-2xl shadow-lg transform transition-all duration-300 ease-out" style="transform: scale(0.95); opacity: 0;">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <!-- Animação de gravação melhorada -->
                            <div class="relative">
                                <div class="w-12 h-12 bg-red-500 rounded-full flex items-center justify-center">
                                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3z"/>
                                        <path d="M17 11c0 2.76-2.24 5-5 5s-5-2.24-5-5H5c0 3.53 2.61 6.43 6 6.92V21h2v-3.08c3.39-.49 6-3.39 6-6.92h-2z"/>
                                    </svg>
                                </div>
                                <!-- Ondas sonoras animadas -->
                                <div class="absolute -inset-2">
                                    <div class="w-16 h-16 border-2 border-red-300 rounded-full animate-ping opacity-20"></div>
                                </div>
                                <div class="absolute -inset-4">
                                    <div class="w-20 h-20 border-2 border-red-200 rounded-full animate-ping opacity-10" style="animation-delay: 0.5s"></div>
                                </div>
                            </div>
                            <div>
                                <div class="flex items-center space-x-2">
                                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                                    </svg>
                                    <p class="text-red-700 font-semibold text-lg">Gravando áudio</p>
                                </div>
                                <p class="text-red-600 text-sm font-mono" id="recordingDuration">00:00</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-3">
                            <button onclick="cancelAudioRecording()" class="p-3 text-red-600 hover:text-red-800 hover:bg-red-100 rounded-xl transition-all duration-200 border border-red-200 hover:border-red-300" title="Cancelar gravação">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                            <button onclick="stopAudioRecording()" class="p-4 bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white rounded-xl transition-all duration-200 shadow-lg hover:shadow-xl transform hover:scale-105" title="Enviar áudio">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
                <!-- Picker de Emojis (inicialmente oculto) -->
                <div id="emojiPicker" class="hidden mb-3 p-3 bg-gray-50 rounded-lg border">
                    <!-- Categorias de Emojis -->
                    <div class="flex space-x-2 mb-3 border-b border-gray-200">
                        <button class="emoji-category-btn px-3 py-1 text-sm rounded-lg bg-green-100 text-green-700" onclick="showEmojiCategory('faces')">😀</button>
                        <button class="emoji-category-btn px-3 py-1 text-sm rounded-lg hover:bg-gray-200" onclick="showEmojiCategory('gestures')">👋</button>
                        <button class="emoji-category-btn px-3 py-1 text-sm rounded-lg hover:bg-gray-200" onclick="showEmojiCategory('objects')">📱</button>
                        <button class="emoji-category-btn px-3 py-1 text-sm rounded-lg hover:bg-gray-200" onclick="showEmojiCategory('nature')">🌱</button>
                        <button class="emoji-category-btn px-3 py-1 text-sm rounded-lg hover:bg-gray-200" onclick="showEmojiCategory('food')">🍎</button>
                        <button class="emoji-category-btn px-3 py-1 text-sm rounded-lg hover:bg-gray-200" onclick="showEmojiCategory('activities')">⚽</button>
                        <button class="emoji-category-btn px-3 py-1 text-sm rounded-lg hover:bg-gray-200" onclick="showEmojiCategory('travel')">🚗</button>
                        <button class="emoji-category-btn px-3 py-1 text-sm rounded-lg hover:bg-gray-200" onclick="showEmojiCategory('symbols')">❤️</button>
                    </div>
                    
                    <!-- Emojis por categoria -->
                    <div id="emojiFaces" class="emoji-category grid grid-cols-8 gap-2 max-h-40 overflow-y-auto">
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('😀')">😀</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('😃')">😃</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('😄')">😄</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('😁')">😁</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('😆')">😆</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('😅')">😅</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('😂')">😂</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('🤣')">🤣</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('😊')">😊</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('😇')">😇</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('🙂')">🙂</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('🙃')">🙃</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('😉')">😉</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('😌')">😌</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('😍')">😍</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('🥰')">🥰</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('😘')">😘</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('😗')">😗</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('😙')">😙</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('😚')">😚</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('😋')">😋</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('😛')">😛</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('😝')">😝</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('😜')">😜</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('🤪')">🤪</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('🤨')">🤨</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('🧐')">🧐</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('🤓')">🤓</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('😎')">😎</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('🤩')">🤩</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('🥳')">🥳</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('😏')">😏</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('😒')">😒</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('😞')">😞</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('😔')">😔</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('😟')">😟</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('😕')">😕</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('🙁')">🙁</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('☹️')">☹️</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('😣')">😣</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('😖')">😖</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('😫')">😫</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('😩')">😩</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('🥺')">🥺</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('😢')">😢</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('😭')">😭</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('😤')">😤</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('😠')">😠</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('😡')">😡</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('🤬')">🤬</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('🤯')">🤯</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('😳')">😳</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('🥵')">🥵</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('🥶')">🥶</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('😱')">😱</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('😨')">😨</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('😰')">😰</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('😥')">😥</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('😓')">😓</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('🤗')">🤗</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('🤔')">🤔</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('🤭')">🤭</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('🤫')">🤫</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('🤥')">🤥</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('😶')">😶</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('😐')">😐</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('😑')">😑</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('😬')">😬</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('🙄')">🙄</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('😯')">😯</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('😦')">😦</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('😧')">😧</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('😮')">😮</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('😲')">😲</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('🥱')">🥱</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('😴')">😴</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('🤤')">🤤</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('😪')">😪</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('😵')">😵</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('🤐')">🤐</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('🥴')">🥴</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('🤢')">🤢</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('🤮')">🤮</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('🤧')">🤧</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('😷')">😷</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('🤒')">🤒</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('🤕')">🤕</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('🤑')">🤑</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('🤠')">🤠</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('😈')">😈</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('👿')">👿</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('👹')">👹</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('👺')">👺</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('🤡')">🤡</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('💩')">💩</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('👻')">👻</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('💀')">💀</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('☠️')">☠️</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('👽')">👽</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('👾')">👾</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('🤖')">🤖</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('🎃')">🎃</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('😺')">😺</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('😸')">😸</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('😹')">😹</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('😻')">😻</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('😼')">😼</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('😽')">😽</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('🙀')">🙀</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('😿')">😿</button>
                        <button class="emoji-btn p-2 hover:bg-gray-200 rounded text-lg" onclick="insertEmoji('😾')">😾</button>
                    </div>
                    
                    <!-- Outras categorias serão carregadas dinamicamente -->
                    <div id="emojiGestures" class="emoji-category hidden grid grid-cols-8 gap-2 max-h-40 overflow-y-auto"></div>
                    <div id="emojiObjects" class="emoji-category hidden grid grid-cols-8 gap-2 max-h-40 overflow-y-auto"></div>
                    <div id="emojiNature" class="emoji-category hidden grid grid-cols-8 gap-2 max-h-40 overflow-y-auto"></div>
                    <div id="emojiFood" class="emoji-category hidden grid grid-cols-8 gap-2 max-h-40 overflow-y-auto"></div>
                    <div id="emojiActivities" class="emoji-category hidden grid grid-cols-8 gap-2 max-h-40 overflow-y-auto"></div>
                    <div id="emojiTravel" class="emoji-category hidden grid grid-cols-8 gap-2 max-h-40 overflow-y-auto"></div>
                    <div id="emojiSymbols" class="emoji-category hidden grid grid-cols-8 gap-2 max-h-40 overflow-y-auto"></div>
                </div>
                
                <div class="flex items-center space-x-3">
                    <!-- Input de arquivo oculto -->
                    <input type="file" id="fileInput" class="hidden" accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.txt" onchange="handleFileSelect(event)">
                    
                    <button onclick="toggleFileInput()" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors" title="Anexar arquivo">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                        </svg>
                    </button>
                    <button onclick="toggleEmojiPicker()" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors" title="Emoji">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </button>
                    <button id="audioRecordBtn" onclick="toggleAudioRecording()" class="p-3 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-xl transition-all duration-200 border border-transparent hover:border-red-200" title="Gravar áudio">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                        </svg>
                    </button>
                    <input 
                        type="text" 
                        id="chatMessageInput"
                        placeholder="Escreva uma mensagem..."
                        class="flex-1 px-4 py-3 bg-gray-100 rounded-xl border-0 focus:ring-2 focus:ring-green-500 focus:bg-white transition-colors"
                        onkeypress="handleChatKeyPress(event)"
                        disabled
                    >
                    <button 
                        onclick="sendChatMessageLocal()"
                        class="p-3 bg-green-600 hover:bg-green-700 text-white rounded-xl transition-colors"
                        disabled
                        id="sendMessageBtn"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // ==================== CACHE SYSTEM ====================
        const CacheManager = {
            cache: new Map(),
            userData: null,
            farmData: null,
            lastUserFetch: 0,
            lastFarmFetch: 0,
            CACHE_DURATION: 5 * 60 * 1000, // 5 minutos
            
            // Cache de dados do usuário
            async getUserData(forceRefresh = false) {
                const now = Date.now();
                if (!forceRefresh && this.userData && (now - this.lastUserFetch) < this.CACHE_DURATION) {
                    console.log('📋 Usando dados do usuário do cache');
                    return this.userData;
                }
                
                console.log('🔄 Buscando dados do usuário no Supabase');
                const supabase = createSupabaseClient();
                const { data: { user } } = await supabase.auth.getUser();
                
                if (user) {
                    const { data: userData } = await supabase
                        .from('users')
                        .select('id, name, email, role, farm_id, profile_photo_url, is_active')
                        .eq('id', user.id)
                        .single();
                    
                    this.userData = { ...user, ...userData };
                    this.lastUserFetch = now;
                    console.log('✅ Dados do usuário cacheados');
                }
                
                return this.userData;
            },
            
            // Cache de dados da fazenda
            async getFarmData(forceRefresh = false) {
                const now = Date.now();
                if (!forceRefresh && this.farmData && (now - this.lastFarmFetch) < this.CACHE_DURATION) {
                    console.log('📋 Usando dados da fazenda do cache');
                    return this.farmData;
                }
                
                console.log('🔄 Buscando dados da fazenda no Supabase');
                const userData = await this.getUserData();
                if (userData?.farm_id) {
                    const supabase = createSupabaseClient();
                    const { data: farmData } = await supabase
                        .from('farms')
                        .select('id, name')
                        .eq('id', userData.farm_id)
                        .single();
                    
                    this.farmData = farmData;
                    this.lastFarmFetch = now;
                    console.log('✅ Dados da fazenda cacheados');
                }
                
                return this.farmData;
            },
            
            // Cache genérico
            set(key, data, ttl = this.CACHE_DURATION) {
                this.cache.set(key, {
                    data,
                    timestamp: Date.now(),
                    ttl
                });
            },
            
            get(key) {
                const item = this.cache.get(key);
                if (!item) return null;
                
                const now = Date.now();
                if (now - item.timestamp > item.ttl) {
                    this.cache.delete(key);
                    return null;
                }
                
                return item.data;
            },
            
            // Cache para dados de volume
            async getVolumeData(farmId, dateRange, forceRefresh = false) {
                const cacheKey = `volume_${farmId}_${dateRange}`;
                const cachedData = this.get(cacheKey);
                
                if (!forceRefresh && cachedData) {
                    console.log('📋 Usando dados de volume do cache:', cacheKey);
                    return cachedData;
                }
                
                console.log('🔄 Buscando dados de volume no Supabase:', cacheKey);
                const supabase = createSupabaseClient();
                
                let query = supabase
                    .from('volume_records')
                    .select('volume_liters, production_date')
                    .eq('farm_id', farmId);
                
                // Aplicar filtro de data se especificado
                if (dateRange === 'today') {
                    const today = new Date().toISOString().split('T')[0];
                    query = query.eq('production_date', today);
                } else if (dateRange === 'week') {
                    const sevenDaysAgo = new Date();
                    sevenDaysAgo.setDate(sevenDaysAgo.getDate() - 6);
                    query = query.gte('production_date', sevenDaysAgo.toISOString().split('T')[0]);
                } else if (dateRange === 'month') {
                    const firstDayOfMonth = new Date();
                    firstDayOfMonth.setDate(1);
                    query = query.gte('production_date', firstDayOfMonth.toISOString().split('T')[0]);
                }
                
                const { data, error } = await query.order('production_date', { ascending: true });
                
                if (error) {
                    console.error('❌ Erro ao buscar dados de volume:', error);
                    return null;
                }
                
                // Cachear por 2 minutos (dados de volume mudam mais frequentemente)
                this.set(cacheKey, data, 2 * 60 * 1000);
                console.log('✅ Dados de volume cacheados:', cacheKey);
                
                return data;
            },
            
            // Limpar cache específico
            clear(key) {
                if (key) {
                    this.cache.delete(key);
                } else {
                    this.cache.clear();
                    this.userData = null;
                    this.farmData = null;
                }
            },
            
            // Invalidar cache de dados críticos
            invalidateUserData() {
                this.userData = null;
                this.farmData = null;
                this.lastUserFetch = 0;
                this.lastFarmFetch = 0;
            }
        };
        
        console.log('=== FUNCIONÁRIO COMPLETO INICIADO ===');
        
        // Global variables
        let currentUser = null;
        let currentFarmId = null;
        let currentFarmName = null;
        let allProductionHistory = [];
        
        // Função simples para criar cliente Supabase
        // Função para obter cliente Supabase (evita múltiplas instâncias)
        function createSupabaseClient() {
            // Primeiro, tentar usar a instância do config.js se disponível
            if (window.LacTechAPI && window.LacTechAPI.supabase) {
                return window.LacTechAPI.supabase;
            }
            
            // Se não estiver disponível, usar a função global do config.js
            if (window.getSupabaseClient) {
                return window.getSupabaseClient();
            }
            
            // Fallback: criar instância local apenas se necessário
            const supabaseUrl = 'https://tmaamwuyucaspqcrhuck.supabase.co';
            const supabaseKey = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InRtYWFtd3V5dWNhc3BxY3JodWNrIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NTY2OTY1MzMsImV4cCI6MjA3MjI3MjUzM30.AdDXp0xrX_xKutFHQrJ47LhFdLTtanTSku7fcK1eTB0';
            
            return window.supabase.createClient(supabaseUrl, supabaseKey);
        }
        
        // Função para obter cliente Supabase (compatibilidade com outras funções)
        async function getSupabaseClient() {
            return createSupabaseClient();
        }
        
        // Função para log debug
        function log(message) {
            console.log(`[FUNCIONÁRIO] ${message}`);
        }

        log('Script carregado!');

        // ==================== TAB NAVIGATION ====================
        // Event listener para o formulário moderno
        function initFormHandlers() {
            const modernForm = document.getElementById('newProductionForm');
            if (modernForm) {
                modernForm.addEventListener('submit', handleNewProductionSubmit);
            }
            
            // Definir data atual como padrão no formulário
            const today = new Date().toISOString().split('T')[0];
            const dateInput = document.getElementById('newProductionDate');
            if (dateInput) {
                dateInput.value = today;
            }
        }

        // ==================== SISTEMA DE ABAS MODERNO ====================
        
        let currentTab = 'dashboard';

        // Função principal de troca de abas
        function switchToTab(tabName) {
            if (currentTab === tabName) return;
            
            console.log(`🔄 Mudando para aba: ${tabName}`);
            
            // Esconder todas as abas
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Mostrar a aba selecionada
            const targetTab = document.getElementById(tabName + '-tab');
            if (targetTab) {
                targetTab.classList.add('active');
                currentTab = tabName;
                
                // Atualizar indicadores de navegação
                updateTabIndicators(tabName);
                    
                    // Carregar dados específicos da aba
                loadTabData(tabName);
                
                console.log(`✅ Aba ${tabName} ativada!`);
            } else {
                console.error(`❌ Aba ${tabName} não encontrada!`);
            }
        }

        // Atualizar indicadores de navegação
        function updateTabIndicators(tabName) {
            // Atualizar botões do header
            document.querySelectorAll('.header-tab-button').forEach(btn => {
                btn.classList.remove('active');
                if (btn.getAttribute('data-tab') === tabName) {
                    btn.classList.add('active');
                }
            });
            
            // Atualizar botões desktop (se existirem)
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('active');
                if (btn.getAttribute('data-tab') === tabName) {
                    btn.classList.add('active');
                }
            });
            
            // Atualizar botões mobile
            document.querySelectorAll('.mobile-tab-btn').forEach(btn => {
                btn.classList.remove('active');
                if (btn.getAttribute('data-tab') === tabName) {
                    btn.classList.add('active');
                }
            });
        }
                    
                    // Carregar dados específicos da aba
        function loadTabData(tabName) {
            switch(tabName) {
                case 'dashboard':
                    // Carregar dados do dashboard
                    loadDashboardIndicators();
                    loadRecentActivity();
                    loadProductionChart();
                    break;
                case 'register':
                    // Focar no formulário
                    setTimeout(() => {
                        const volumeInput = document.getElementById('newVolume');
                        if (volumeInput) {
                            volumeInput.focus();
                        }
                    }, 300);
                    break;
                case 'history':
                    // Carregar dados do histórico
                        loadHistoryData();
                    break;
            }
        }

        // Inicializar sistema de abas
        function initTabSystem() {
            // Definir aba inicial
            updateTabIndicators('dashboard');
            
            // Carregar dados da aba inicial
            loadTabData('dashboard');
            
            console.log('✅ Sistema de abas inicializado!');
        }


        // ==================== CHAT FUNCTIONS ====================
        
        // Abrir modal de chat
        // Variável para canal de real-time do chat
        let chatRealtimeChannel = null;
        
        // Configurar real-time para chat
        function setupChatRealtime(farmId) {
            try {
                console.log('🔔 Configurando real-time para chat da fazenda:', farmId);
                
                // Desconectar canal anterior se existir
                if (chatRealtimeChannel) {
                    disconnectRealtime(chatRealtimeChannel);
                    chatRealtimeChannel = null;
                }
                
                // Configurar novo canal
                chatRealtimeChannel = setupRealtimeChat(farmId, async (newMessage) => {
                    console.log('📨 Nova mensagem recebida via real-time:', newMessage);
                    
                    // Obter usuário atual
                    const supabase = await getSupabaseClient();
                    const { data: { user } } = await supabase.auth.getUser();
                    
                    if (!user) return;
                    
                    // Verificar se a mensagem é para o usuário atual
                    const isForCurrentUser = newMessage.sender_id === user.id || newMessage.receiver_id === user.id;
                    
                    if (isForCurrentUser) {
                        console.log('🔄 Mensagem para usuário atual detectada');
                        
                        // Se há funcionário selecionado e a mensagem é com ele
                        if (window.selectedEmployee && 
                            (newMessage.sender_id === window.selectedEmployee.id || 
                             newMessage.receiver_id === window.selectedEmployee.id)) {
                            
                            console.log('🔄 Atualizando mensagens para funcionário selecionado:', window.selectedEmployee.name);
                            // Recarregar mensagens para o funcionário selecionado
                            await loadChatMessages(window.selectedEmployee.id);
                        }
                        
                        // Atualizar lista de funcionários para mostrar notificação
                        loadEmployees();
                    }
                });
                
                console.log('✅ Real-time do chat configurado com sucesso');
                
            } catch (error) {
                console.error('❌ Erro ao configurar real-time do chat:', error);
            }
        }
        
        async function openChatModal() {
            const modal = document.getElementById('chatModal');
            if (modal) {
                modal.classList.remove('hidden');
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
                
                // Configurar listener de scroll do chat
                setTimeout(() => {
                    setupChatScrollListener();
                }, 100);
                
                // Atualizar status online do usuário atual
                try {
                    const supabase = await getSupabaseClient();
                    const { data: { user } } = await supabase.auth.getUser();
                    if (user) {
                        await updateUserLastLogin(user.id);
                        
                        // Buscar farm_id para configurar real-time
                        const { data: userData } = await supabase
                            .from('users')
                            .select('farm_id')
                            .eq('id', user.id)
                            .single();
                        
                        if (userData?.farm_id) {
                            // Configurar real-time para chat
                            setupChatRealtime(userData.farm_id);
                        }
                    }
                } catch (error) {
                    console.error('Erro ao atualizar status online:', error);
                }
                
                loadEmployees();
            }
        }

        // Fechar modal de chat
        function closeChatModal() {
            const modal = document.getElementById('chatModal');
            if (modal) {
                modal.classList.add('hidden');
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
                
                // Desconectar real-time do chat
                if (chatRealtimeChannel) {
                    disconnectRealtime(chatRealtimeChannel);
                    chatRealtimeChannel = null;
                    console.log('🔌 Real-time do chat desconectado');
                }
            }
        }

        // Carregar funcionários da fazenda (apenas gerente para funcionários)
        async function loadEmployees() {
            try {
                console.log('🔄 Carregando gerente...');
                
                const supabase = await getSupabaseClient();
                const { data: { user } } = await supabase.auth.getUser();
                
                if (!user) {
                    console.error('❌ Usuário não autenticado');
                    return;
                }

                console.log('👤 Usuário autenticado:', user.email);

                // Buscar farm_id do usuário
                const { data: userData, error: userError } = await supabase
                    .from('users')
                    .select('farm_id')
                    .eq('id', user.id)
                    .single();

                if (userError) {
                    console.error('❌ Erro ao buscar farm_id:', userError);
                    return;
                }

                if (!userData?.farm_id) {
                    console.error('❌ Farm ID não encontrado');
                    return;
                }

                console.log('🏢 Farm ID:', userData.farm_id);

                // Usar o serviço de sincronização para buscar funcionários
                const employees = await getFarmUsers(userData.farm_id);
                console.log('👥 Usuários encontrados:', employees.length);
                
                // Filtrar apenas o gerente para funcionários
                const managerOnly = employees.filter(emp => emp.role === 'gerente');
                console.log('👨‍💼 Gerente encontrado:', managerOnly.length);
                
                displayEmployees(managerOnly);
            } catch (error) {
                console.error('❌ Erro ao carregar gerente:', error);
                showNotification('Erro ao carregar gerente: ' + error.message, 'error');
            }
        }

        // Exibir funcionários na lista
        function displayEmployees(employees) {
            console.log('📋 Exibindo gerente:', employees);
            
            const employeesList = document.getElementById('employeesList');
            const onlineEmployees = document.getElementById('onlineEmployees');
            
            if (!employeesList) {
                console.error('❌ Elemento employeesList não encontrado');
                return;
            }
            
            if (!onlineEmployees) {
                console.error('❌ Elemento onlineEmployees não encontrado');
                return;
            }

            console.log('✅ Elementos encontrados, limpando listas...');
            employeesList.innerHTML = '';
            onlineEmployees.innerHTML = '';

            employees.forEach(employee => {
                const isOnline = isEmployeeOnline(employee);
                const initial = employee.name.charAt(0).toUpperCase();
                const userColor = generateUserColor(employee.name);
                
                // Verificar se tem foto de perfil
                const hasPhoto = employee.profile_photo_url && employee.profile_photo_url.trim() !== '';
                
                // Gerar avatar (foto ou letra) para lista principal
                let mainAvatarHtml;
                if (hasPhoto) {
                    mainAvatarHtml = `
                        <img src="${employee.profile_photo_url}?t=${Date.now()}" 
                             alt="Foto de ${employee.name}" 
                             class="w-10 h-10 rounded-full object-cover"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                             onload="this.nextElementSibling.style.display='none';">
                        <div class="w-10 h-10 bg-gradient-to-br ${userColor} rounded-full flex items-center justify-center" style="display: flex;">
                            <span class="text-white font-semibold text-sm">${initial}</span>
                        </div>
                    `;
                } else {
                    mainAvatarHtml = `
                        <div class="w-10 h-10 bg-gradient-to-br ${userColor} rounded-full flex items-center justify-center">
                            <span class="text-white font-semibold text-sm">${initial}</span>
                        </div>
                    `;
                }
                
                // Item da lista principal
                const employeeItem = document.createElement('div');
                employeeItem.className = 'flex items-center space-x-3 p-2.5 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors';
                employeeItem.onclick = () => selectEmployee(employee);
                
                employeeItem.innerHTML = `
                    <div class="relative">
                        ${mainAvatarHtml}
                        ${isOnline ? '<div class="absolute -bottom-0.5 -right-0.5 w-3 h-3 bg-green-500 border-2 border-white rounded-full"></div>' : ''}
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="font-medium text-gray-900 truncate text-sm">${employee.name}</h4>
                        <p class="text-xs text-gray-500 truncate">${employee.role}</p>
                    </div>
                    <div class="text-xs text-gray-400">
                        ${isOnline ? 'Online' : formatLastSeen(employee.last_login)}
                    </div>
                `;
                
                employeesList.appendChild(employeeItem);

                // Funcionário online (se estiver online)
                if (isOnline) {
                    const onlineItem = document.createElement('div');
                    onlineItem.className = 'flex flex-col items-center space-y-1 cursor-pointer';
                    onlineItem.onclick = () => selectEmployee(employee);
                    
                    // Gerar avatar (foto ou letra) para seção online
                    let onlineAvatarHtml;
                    if (hasPhoto) {
                        onlineAvatarHtml = `
                            <img src="${employee.profile_photo_url}?t=${Date.now()}" 
                                 alt="Foto de ${employee.name}" 
                                 class="w-10 h-10 rounded-full object-cover"
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                 onload="this.nextElementSibling.style.display='none';">
                            <div class="w-10 h-10 bg-gradient-to-br ${userColor} rounded-full flex items-center justify-center" style="display: flex;">
                                <span class="text-white font-semibold text-xs">${initial}</span>
                            </div>
                        `;
                    } else {
                        onlineAvatarHtml = `
                            <div class="w-10 h-10 bg-gradient-to-br ${userColor} rounded-full flex items-center justify-center">
                                <span class="text-white font-semibold text-xs">${initial}</span>
                            </div>
                        `;
                    }
                    
                    onlineItem.innerHTML = `
                        <div class="relative">
                            ${onlineAvatarHtml}
                            <div class="absolute -bottom-0.5 -right-0.5 w-3 h-3 bg-green-500 border-2 border-white rounded-full"></div>
                        </div>
                        <span class="text-xs text-gray-600 text-center max-w-16 truncate">${employee.name}</span>
                    `;
                    
                    onlineEmployees.appendChild(onlineItem);
                }
            });
            
            console.log('✅ Gerente exibido com sucesso!');
            console.log('📊 Total de gerentes:', employees.length);
            console.log('🟢 Gerentes online:', document.querySelectorAll('#onlineEmployees > div').length);
        }

        // Verificar se funcionário está online
        function isEmployeeOnline(employee) {
            // Verificar se o objeto employee existe
            if (!employee) {
                console.warn('⚠️ Employee object is null or undefined');
                return false;
            }
            
            // Usar a coluna is_online se disponível, senão usar last_login
            if (employee.is_online !== undefined && employee.is_online !== null) {
                return employee.is_online;
            }
            
            // Fallback para last_login
            if (!employee.last_login) return false;
            const now = new Date();
            const loginTime = new Date(employee.last_login);
            const diffMinutes = (now - loginTime) / (1000 * 60);
            return diffMinutes < 15; // Considera online se fez login nos últimos 15 minutos
        }

        // Formatar última vez visto
        function formatLastSeen(lastLogin) {
            if (!lastLogin) return 'Nunca';
            
            try {
                const now = new Date();
                const loginTime = new Date(lastLogin);
                
                // Verificar se a data é válida
                if (isNaN(loginTime.getTime())) {
                    return 'Data inválida';
                }
                
                const diffMinutes = (now - loginTime) / (1000 * 60);
                
                if (diffMinutes < 60) return 'Há ' + Math.floor(diffMinutes) + 'min';
                if (diffMinutes < 1440) return 'Há ' + Math.floor(diffMinutes / 60) + 'h';
                return 'Há ' + Math.floor(diffMinutes / 1440) + ' dias';
            } catch (error) {
                console.error('Erro ao formatar lastLogin:', error);
                return 'Erro';
            }
        }

        // Selecionar funcionário para conversa
        function selectEmployee(employee) {
            // Verificar se o employee existe
            if (!employee) {
                console.error('❌ Employee object is null or undefined');
                return;
            }
            
            window.selectedEmployee = employee;
            
            // Verificar se os elementos existem antes de usar
            const nameElement = document.getElementById('selectedEmployeeName');
            const initialElement = document.getElementById('selectedEmployeeInitial');
            const statusElement = document.getElementById('selectedEmployeeStatus');
            const messageInput = document.getElementById('chatMessageInput');
            const sendBtn = document.getElementById('sendMessageBtn');
            
            if (nameElement) nameElement.textContent = employee.name || 'Nome não disponível';
            if (statusElement) statusElement.textContent = isEmployeeOnline(employee) ? 'Online' : 'Offline';
            
            // Atualizar avatar no header com foto de perfil ou inicial colorida
            if (initialElement) {
                const avatarContainer = initialElement.parentElement;
                if (avatarContainer) {
                    // Limpar conteúdo anterior
                    avatarContainer.innerHTML = '';
                    
                    if (employee.profile_photo_url) {
                        // Usar foto de perfil
                        const img = document.createElement('img');
                        img.src = employee.profile_photo_url;
                        img.alt = employee.name || 'Avatar';
                        img.className = 'w-10 h-10 rounded-full object-cover';
                        img.onerror = () => {
                            // Fallback para inicial colorida se a imagem falhar
                            const userColor = generateUserColor(employee.name);
                            const senderInitial = (employee.name || '?').charAt(0).toUpperCase();
                            avatarContainer.className = `w-10 h-10 bg-gradient-to-br ${userColor} rounded-full flex items-center justify-center`;
                            avatarContainer.innerHTML = `<span class="text-white font-semibold text-sm">${senderInitial}</span>`;
                        };
                        avatarContainer.appendChild(img);
                    } else {
                        // Usar inicial colorida
                        const userColor = generateUserColor(employee.name);
                        const senderInitial = (employee.name || '?').charAt(0).toUpperCase();
                        avatarContainer.className = `w-10 h-10 bg-gradient-to-br ${userColor} rounded-full flex items-center justify-center`;
                        avatarContainer.innerHTML = `<span class="text-white font-semibold text-sm">${senderInitial}</span>`;
                    }
                }
            }
            
            if (messageInput) messageInput.disabled = false;
            if (sendBtn) sendBtn.disabled = false;
            
            // Mostrar interface do chat e ocultar UI inicial
            const initialUI = document.getElementById('initialChatUI');
            const chatMessages = document.getElementById('chatMessages');
            const chatInputArea = document.getElementById('chatInputArea');
            const chatHeader = document.getElementById('chatHeader');
            
            if (initialUI) initialUI.classList.add('hidden');
            if (chatMessages) chatMessages.classList.remove('hidden');
            if (chatInputArea) chatInputArea.classList.remove('hidden');
            if (chatHeader) chatHeader.classList.remove('hidden');
            
            // Carregar mensagens com este funcionário
            if (employee.id) {
                loadChatMessages(employee.id);
            } else {
                console.error('❌ Employee ID is missing');
            }
        }

        // Sair do chat e voltar para UI inicial
        function exitChat() {
            // Limpar funcionário selecionado
            window.selectedEmployee = null;
            
            // Ocultar interface do chat e mostrar UI inicial
            const initialUI = document.getElementById('initialChatUI');
            const chatMessages = document.getElementById('chatMessages');
            const chatInputArea = document.getElementById('chatInputArea');
            const chatHeader = document.getElementById('chatHeader');
            
            if (initialUI) initialUI.classList.remove('hidden');
            if (chatMessages) chatMessages.classList.add('hidden');
            if (chatInputArea) chatInputArea.classList.add('hidden');
            if (chatHeader) chatHeader.classList.add('hidden');
            
            // Limpar mensagens do chat
            if (chatMessages) {
                chatMessages.innerHTML = '';
            }
            
            // Limpar input de mensagem
            const messageInput = document.getElementById('chatMessageInput');
            if (messageInput) {
                messageInput.value = '';
                messageInput.disabled = true;
            }
            
            // Desabilitar botão de envio
            const sendBtn = document.getElementById('sendMessageBtn');
            if (sendBtn) {
                sendBtn.disabled = true;
            }
            
            // Ocultar picker de emojis se estiver aberto
            const emojiPicker = document.getElementById('emojiPicker');
            if (emojiPicker) {
                emojiPicker.classList.add('hidden');
            }
            
            // Resetar avatar e nome no header (se ainda estiver visível)
            const nameElement = document.getElementById('selectedEmployeeName');
            const initialElement = document.getElementById('selectedEmployeeInitial');
            const statusElement = document.getElementById('selectedEmployeeStatus');
            
            if (nameElement) nameElement.textContent = 'Selecione um funcionário';
            if (statusElement) statusElement.textContent = 'Para começar uma conversa';
            if (initialElement) {
                const avatarContainer = initialElement.parentElement;
                if (avatarContainer) {
                    avatarContainer.className = 'w-10 h-10 bg-gradient-to-br from-green-500 to-green-600 rounded-full flex items-center justify-center';
                    avatarContainer.innerHTML = '<span class="text-white font-semibold text-sm">?</span>';
                }
            }
        }

        // Carregar mensagens do chat com funcionário específico
        async function loadChatMessages(employeeId = null) {
            try {
                const supabase = await getSupabaseClient();
                const { data: { user } } = await supabase.auth.getUser();
                
                if (!user) return;

                // Definir currentUser globalmente
                window.currentUser = user;

                // Buscar farm_id do usuário
                const { data: userData } = await supabase
                    .from('users')
                    .select('farm_id')
                    .eq('id', user.id)
                    .single();

                if (!userData?.farm_id) return;

                // Usar o serviço de sincronização para buscar mensagens
                console.log('📨 Buscando mensagens para:', { farmId: userData.farm_id, userId: user.id, employeeId });
                const messages = await getChatMessages(userData.farm_id, user.id, employeeId);
                console.log('📨 Mensagens encontradas:', messages?.length || 0);
                
                displayChatMessages(messages);
            } catch (error) {
                console.error('Erro ao carregar chat:', error);
            }
        }

        // Função para gerar cor baseada no nome do usuário
        function generateUserColor(name) {
            if (!name) return '#6B7280'; // Cor cinza padrão
            
            // Array de cores disponíveis
            const colors = [
                '#10B981', // Verde
                '#3B82F6', // Azul
                '#8B5CF6', // Roxo
                '#EC4899', // Rosa
                '#EF4444', // Vermelho
                '#F59E0B', // Amarelo
                '#6366F1', // Índigo
                '#14B8A6', // Teal
                '#F97316', // Laranja
                '#06B6D4'  // Ciano
            ];
            
            // Gerar índice baseado no nome
            let hash = 0;
            for (let i = 0; i < name.length; i++) {
                hash = name.charCodeAt(i) + ((hash << 5) - hash);
            }
            
            return colors[Math.abs(hash) % colors.length];
        }

        // Cache para elementos de mensagem
        let lastMessageCount = 0;
        let isUserAtBottom = true;
        let newMessageIndicator = null;

        // Função para verificar se usuário está no final do chat
        function checkIfUserAtBottom() {
            const chatContainer = document.getElementById('chatMessages');
            if (!chatContainer) return true;
            
            const threshold = 100; // pixels do final
            const isAtBottom = chatContainer.scrollTop + chatContainer.clientHeight >= chatContainer.scrollHeight - threshold;
            isUserAtBottom = isAtBottom;
            return isAtBottom;
        }

        // Função para scroll suave para o final
        function scrollToBottom(smooth = true) {
            const chatContainer = document.getElementById('chatMessages');
            if (!chatContainer) {
                console.log('❌ Container de chat não encontrado para scroll');
                return;
            }
            
            console.log('📜 Fazendo scroll para o final:', {
                scrollHeight: chatContainer.scrollHeight,
                clientHeight: chatContainer.clientHeight,
                scrollTop: chatContainer.scrollTop
            });
            
            // Forçar scroll imediato primeiro
            chatContainer.scrollTop = chatContainer.scrollHeight;
            
            // Depois aplicar scroll suave se solicitado
            if (smooth) {
                setTimeout(() => {
                    chatContainer.scrollTo({
                        top: chatContainer.scrollHeight,
                        behavior: 'smooth'
                    });
                }, 50);
            }
            
            // Atualizar status de posição
            setTimeout(() => {
                isUserAtBottom = true;
                hideNewMessageIndicator();
            }, 100);
        }

        // Função para mostrar indicador de nova mensagem
        function showNewMessageIndicator() {
            if (newMessageIndicator) return; // Já existe
            
            const chatContainer = document.getElementById('chatMessages');
            if (!chatContainer) return;
            
            newMessageIndicator = document.createElement('div');
            newMessageIndicator.className = 'fixed bottom-20 right-4 bg-green-500 text-white px-4 py-2 rounded-full shadow-lg cursor-pointer z-50 flex items-center space-x-2 animate-bounce';
            newMessageIndicator.innerHTML = `
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                </svg>
                <span class="text-sm font-medium">Nova mensagem</span>
            `;
            
            newMessageIndicator.onclick = () => {
                scrollToBottom();
                hideNewMessageIndicator();
            };
            
            document.body.appendChild(newMessageIndicator);
            
            // Auto-hide após 5 segundos
            setTimeout(() => {
                hideNewMessageIndicator();
            }, 5000);
        }

        // Função para esconder indicador de nova mensagem
        function hideNewMessageIndicator() {
            if (newMessageIndicator) {
                newMessageIndicator.remove();
                newMessageIndicator = null;
            }
        }

        // Cache para status de leitura das mensagens
        let messageReadStatus = new Map();

        // Função para determinar status de leitura da mensagem
        function getReadStatus(message) {
            const messageId = message.id || `${message.created_at}_${message.sender_id}`;
            
            // Verificar se já temos status armazenado
            if (messageReadStatus.has(messageId)) {
                return messageReadStatus.get(messageId);
            }
            
            // Verificar se o destinatário está online
            const isRecipientOnline = window.selectedEmployee && isEmployeeOnline(window.selectedEmployee);
            
            // Simular status baseado no tempo da mensagem e se destinatário está online
            const messageTime = new Date(message.created_at);
            const now = new Date();
            const timeDiff = (now - messageTime) / 1000; // diferença em segundos
            
            let statusHtml;
            
            if (timeDiff < 1) {
                // Mensagem muito recente - apenas enviada (um verificado cinza)
                statusHtml = '<svg class="w-4 h-3 text-gray-400" fill="currentColor" viewBox="0 0 16 12"><path d="M6.5 9.5L3 6l1.4-1.4L6.5 6.7l5.1-5.1L13 2.8l-6.5 6.7z"></path></svg>';
            } else if (isRecipientOnline && timeDiff > 2) {
                // Destinatário online e tempo suficiente - mensagem lida (dois verificados azuis)
                statusHtml = `
                    <div class="relative w-5 h-3">
                        <svg class="absolute w-4 h-3 text-blue-500" fill="currentColor" viewBox="0 0 16 12">
                            <path d="M6.5 9.5L3 6l1.4-1.4L6.5 6.7l5.1-5.1L13 2.8l-6.5 6.7z"></path>
                        </svg>
                        <svg class="absolute w-4 h-3 text-blue-500" fill="currentColor" viewBox="0 0 16 12" style="left: 4px;">
                            <path d="M6.5 9.5L3 6l1.4-1.4L6.5 6.7l5.1-5.1L13 2.8l-6.5 6.7z"></path>
                        </svg>
                    </div>
                `;
            } else if (isRecipientOnline) {
                // Destinatário online - mensagem entregue (dois verificados cinza)
                statusHtml = `
                    <div class="relative w-5 h-3">
                        <svg class="absolute w-4 h-3 text-gray-400" fill="currentColor" viewBox="0 0 16 12">
                            <path d="M6.5 9.5L3 6l1.4-1.4L6.5 6.7l5.1-5.1L13 2.8l-6.5 6.7z"></path>
                        </svg>
                        <svg class="absolute w-4 h-3 text-gray-400" fill="currentColor" viewBox="0 0 16 12" style="left: 4px;">
                            <path d="M6.5 9.5L3 6l1.4-1.4L6.5 6.7l5.1-5.1L13 2.8l-6.5 6.7z"></path>
                        </svg>
                    </div>
                `;
            } else {
                // Destinatário offline - apenas enviada (um verificado cinza)
                statusHtml = '<svg class="w-4 h-3 text-gray-400" fill="currentColor" viewBox="0 0 16 12"><path d="M6.5 9.5L3 6l1.4-1.4L6.5 6.7l5.1-5.1L13 2.8l-6.5 6.7z"></path></svg>';
            }
            
            // Armazenar status para evitar recálculo
            messageReadStatus.set(messageId, statusHtml);
            
            // Simular progressão do status ao longo do tempo
            if (timeDiff < 2 && isRecipientOnline) {
                setTimeout(() => {
                    updateMessageReadStatus(messageId, 'delivered');
                }, 2000 - (timeDiff * 1000));
            }
            
            if (timeDiff < 5 && isRecipientOnline) {
                setTimeout(() => {
                    updateMessageReadStatus(messageId, 'read');
                }, 5000 - (timeDiff * 1000));
            }
            
            return statusHtml;
        }

        // Função para atualizar status de leitura de uma mensagem específica
        function updateMessageReadStatus(messageId, status) {
            let statusHtml;
            
            if (status === 'delivered') {
                // Dois verificados cinza
                statusHtml = `
                    <div class="relative w-5 h-3">
                        <svg class="absolute w-4 h-3 text-gray-400" fill="currentColor" viewBox="0 0 16 12">
                            <path d="M6.5 9.5L3 6l1.4-1.4L6.5 6.7l5.1-5.1L13 2.8l-6.5 6.7z"></path>
                        </svg>
                        <svg class="absolute w-4 h-3 text-gray-400" fill="currentColor" viewBox="0 0 16 12" style="left: 4px;">
                            <path d="M6.5 9.5L3 6l1.4-1.4L6.5 6.7l5.1-5.1L13 2.8l-6.5 6.7z"></path>
                        </svg>
                    </div>
                `;
            } else if (status === 'read') {
                // Dois verificados azuis
                statusHtml = `
                    <div class="relative w-5 h-3">
                        <svg class="absolute w-4 h-3 text-blue-500" fill="currentColor" viewBox="0 0 16 12">
                            <path d="M6.5 9.5L3 6l1.4-1.4L6.5 6.7l5.1-5.1L13 2.8l-6.5 6.7z"></path>
                        </svg>
                        <svg class="absolute w-4 h-3 text-blue-500" fill="currentColor" viewBox="0 0 16 12" style="left: 4px;">
                            <path d="M6.5 9.5L3 6l1.4-1.4L6.5 6.7l5.1-5.1L13 2.8l-6.5 6.7z"></path>
                        </svg>
                    </div>
                `;
            }
            
            if (statusHtml) {
                messageReadStatus.set(messageId, statusHtml);
                // Atualizar visualmente no chat
                updateReadStatusInChat(messageId, statusHtml);
            }
        }

        // Função para atualizar status de leitura visualmente no chat
        function updateReadStatusInChat(messageId, statusHtml) {
            const chatContainer = document.getElementById('chatMessages');
            if (!chatContainer) return;
            
            // Encontrar a mensagem específica e atualizar seu status
            const messages = chatContainer.querySelectorAll('[data-message-id]');
            messages.forEach(messageElement => {
                if (messageElement.getAttribute('data-message-id') === messageId) {
                    const readStatusElement = messageElement.querySelector('.read-status');
                    if (readStatusElement) {
                        readStatusElement.innerHTML = statusHtml;
                    }
                }
            });
        }

        // Exibir mensagens no chat
        function displayChatMessages(messages) {
            const chatContainer = document.getElementById('chatMessages');
            if (!chatContainer) return;

            // Verificar se usuário está no final antes de atualizar
            const wasAtBottom = checkIfUserAtBottom();
            const hadMessages = lastMessageCount > 0;
            const hasNewMessages = messages.length > lastMessageCount;

            // Verificar se precisa atualizar (evitar recarregamento desnecessário)
            if (messages.length === lastMessageCount && messages.length > 0 && !hasNewMessages) {
                console.log('📨 Mesmo número de mensagens, evitando recarregamento');
                return;
            }

            chatContainer.innerHTML = '';
            lastMessageCount = messages.length;

            if (messages.length === 0) {
                chatContainer.innerHTML = `
                    <div class="text-center py-12">
                        <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                        </svg>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Nenhuma mensagem ainda</h3>
                        <p class="text-gray-500">Seja o primeiro a enviar uma mensagem!</p>
                    </div>
                `;
                return;
            }

            messages.forEach(message => {
                // Verificar se é uma mensagem de chamada
                if (message.call_data) {
                    console.log('=== MENSAGEM DE CHAMADA DETECTADA ===');
                    console.log('Message:', message);
                    console.log('Call data:', message.call_data);
                    
                    // Processar mensagem de chamada
                    try {
                    handleCallMessage(message);
                    } catch (error) {
                        console.error('Erro ao processar mensagem de chamada:', error);
                    }
                    
                    return; // Não exibir mensagem de chamada no chat
                }
                
                // Não exibir mensagens vazias
                if (!message.message || message.message.trim() === '') {
                    return;
                }
                
                const messageDiv = document.createElement('div');
                const isCurrentUser = message.sender_id === (window.currentUser?.id || '');
                const messageTime = new Date(message.created_at).toLocaleTimeString('pt-BR', { 
                    hour: '2-digit', 
                    minute: '2-digit' 
                });

                messageDiv.className = `flex ${isCurrentUser ? 'justify-end' : 'justify-start'} mb-4`;
                
                // Usar sender_name se disponível, senão usar 'Usuário'
                const senderName = message.sender_name || 'Usuário';
                const senderInitial = senderName.charAt(0).toUpperCase();
                
                // Verificar se tem foto de perfil
                const hasPhoto = message.sender_photo && message.sender_photo.trim() !== '';
                const userColor = generateUserColor(senderName);
                
                // Gerar avatar (foto ou letra) - sem timestamp para evitar recarregamento
                let avatarHtml;
                if (hasPhoto) {
                    avatarHtml = `
                        <img src="${message.sender_photo}" 
                             alt="Foto de ${senderName}" 
                             class="w-8 h-8 rounded-full object-cover flex-shrink-0"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                             onload="this.nextElementSibling.style.display='none';">
                        <div class="w-8 h-8 bg-gradient-to-br ${userColor} rounded-full flex items-center justify-center flex-shrink-0" style="display: flex;">
                            <span class="text-white font-semibold text-xs">${senderInitial}</span>
                        </div>
                    `;
                } else {
                    avatarHtml = `
                        <div class="w-8 h-8 bg-gradient-to-br ${userColor} rounded-full flex items-center justify-center flex-shrink-0">
                            <span class="text-white font-semibold text-xs">${senderInitial}</span>
                        </div>
                    `;
                }
                
                // Gerar ícones de verificação para mensagens do usuário atual
                let readReceiptHtml = '';
                if (isCurrentUser) {
                    const readStatus = getReadStatus(message);
                    readReceiptHtml = `
                        <div class="flex items-center space-x-1 mt-1">
                            ${readStatus}
                        </div>
                    `;
                }

                const messageId = message.id || `${message.created_at}_${message.sender_id}`;
                
                messageDiv.setAttribute('data-message-id', messageId);
                messageDiv.innerHTML = `
                    <div class="max-w-xs lg:max-w-md">
                        <div class="flex items-end space-x-2 ${isCurrentUser ? 'flex-row-reverse space-x-reverse' : ''}">
                            <div class="relative">
                                ${avatarHtml}
                            </div>
                            <div class="flex flex-col ${isCurrentUser ? 'items-end' : 'items-start'}">
                                <div class="px-4 py-2 rounded-2xl ${isCurrentUser ? 'bg-green-500 text-white' : 'bg-white text-gray-900'} shadow-sm">
                                    <p class="text-sm">${message.message}</p>
                                </div>
                                <div class="flex items-center space-x-1 mt-1">
                                    <span class="text-xs text-gray-500">${messageTime}</span>
                                    <div class="read-status">${readReceiptHtml}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                chatContainer.appendChild(messageDiv);
            });

            // Lógica de scroll inteligente
            setTimeout(() => {
                console.log('📨 Verificando scroll:', { 
                    wasAtBottom, 
                    hadMessages, 
                    hasNewMessages,
                    messageCount: messages.length,
                    lastCount: lastMessageCount
                });
                
                if (wasAtBottom || !hadMessages || hasNewMessages) {
                    // Usuário estava no final, é primeira carga, ou há mensagens novas - scroll automático
                    console.log('📨 Fazendo scroll automático');
                    scrollToBottom(true);
                } else if (hasNewMessages && !wasAtBottom) {
                    // Há mensagens novas e usuário não está no final - mostrar indicador
                    console.log('📨 Mostrando indicador de nova mensagem');
                    showNewMessageIndicator();
                }
            }, 200); // Aumentado para 200ms para garantir que o DOM foi atualizado
        }

        // Enviar mensagem
        async function sendChatMessageLocal() {
            const messageInput = document.getElementById('chatMessageInput');
            const message = messageInput.value.trim();
            
            if (!message || !window.selectedEmployee) return;

            try {
                const supabase = await getSupabaseClient();
                const { data: { user } } = await supabase.auth.getUser();
                
                if (!user) return;

                // Buscar farm_id do usuário
                const { data: userData } = await supabase
                    .from('users')
                    .select('farm_id')
                    .eq('id', user.id)
                    .single();

                if (!userData?.farm_id) return;

                // Usar o serviço de sincronização para enviar mensagem
                await sendChatMessage({
                    farm_id: userData.farm_id,
                    sender_id: user.id,
                    receiver_id: window.selectedEmployee.id,
                    message: message
                });

                // Limpar input
                messageInput.value = '';
                
                // As mensagens serão atualizadas automaticamente via real-time
                console.log('✅ Mensagem enviada, aguardando atualização via real-time...');
                
                // Fazer scroll para o final após enviar mensagem
                setTimeout(() => {
                    scrollToBottom(true);
                }, 100);
                
            } catch (error) {
                console.error('Erro ao enviar mensagem:', error);
                showNotification('Erro ao enviar mensagem', 'error');
            }
        }

        // Enviar mensagem com Enter
        function handleChatKeyPress(event) {
            if (event.key === 'Enter') {
                sendChatMessageLocal();
            }
        }

        // ==================== FUNÇÕES DE EMOJI E CLIPES ====================
        
        // Toggle do picker de emojis
        function toggleEmojiPicker() {
            const emojiPicker = document.getElementById('emojiPicker');
            if (emojiPicker) {
                emojiPicker.classList.toggle('hidden');
            }
        }

        // Inserir emoji no input
        function insertEmoji(emoji) {
            const messageInput = document.getElementById('chatMessageInput');
            if (messageInput) {
                const currentValue = messageInput.value;
                const cursorPos = messageInput.selectionStart;
                const newValue = currentValue.slice(0, cursorPos) + emoji + currentValue.slice(cursorPos);
                messageInput.value = newValue;
                
                // Reposicionar cursor após o emoji
                messageInput.setSelectionRange(cursorPos + emoji.length, cursorPos + emoji.length);
                messageInput.focus();
                
                // Esconder picker de emojis
                toggleEmojiPicker();
            }
        }

        // Toggle do input de arquivo
        function toggleFileInput() {
            const fileInput = document.getElementById('fileInput');
            if (fileInput) {
                fileInput.click();
            }
        }

        // Lidar com seleção de arquivo
        function handleFileSelect(event) {
            const file = event.target.files[0];
            if (!file) return;

            // Verificar tamanho do arquivo (máximo 10MB)
            const maxSize = 10 * 1024 * 1024; // 10MB
            if (file.size > maxSize) {
                showNotification('Arquivo muito grande. Máximo permitido: 10MB', 'error');
                return;
            }

            // Verificar tipo de arquivo
            const allowedTypes = [
                'image/jpeg', 'image/png', 'image/gif', 'image/webp',
                'video/mp4', 'video/webm', 'video/ogg',
                'audio/mp3', 'audio/wav', 'audio/ogg',
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'text/plain'
            ];

            if (!allowedTypes.includes(file.type)) {
                showNotification('Tipo de arquivo não suportado', 'error');
                return;
            }

            // Enviar arquivo
            sendFileMessage(file);
        }

        // Enviar mensagem com arquivo
        async function sendFileMessage(file) {
            if (!window.selectedEmployee) {
                showNotification('Selecione um funcionário primeiro', 'error');
                return;
            }

            try {
                const supabase = await getSupabaseClient();
                const { data: { user } } = await supabase.auth.getUser();
                
                if (!user) return;

                // Buscar farm_id do usuário
                const { data: userData } = await supabase
                    .from('users')
                    .select('farm_id')
                    .eq('id', user.id)
                    .single();

                if (!userData?.farm_id) return;

                // Mostrar loading
                showNotification('Enviando arquivo...', 'info');

                // Garantir que o bucket chat-files existe
                const bucketExists = await ensureBucketExists(supabase, 'chat-files');
                const bucketName = bucketExists ? 'chat-files' : 'files';

                // Upload do arquivo para Supabase Storage
                const fileExt = file.name.split('.').pop();
                const fileName = `${Date.now()}_${Math.random().toString(36).substring(2)}.${fileExt}`;
                const filePath = `${userData.farm_id}/${fileName}`;

                const { data: uploadData, error: uploadError } = await supabase.storage
                    .from(bucketName)
                    .upload(filePath, file);

                if (uploadError) {
                    console.error('Erro no upload:', uploadError);
                    showNotification('Erro ao enviar arquivo', 'error');
                    return;
                }

                // Obter URL pública do arquivo
                const { data: { publicUrl } } = supabase.storage
                    .from(bucketName)
                    .getPublicUrl(filePath);

                // Criar mensagem com arquivo
                const fileMessage = {
                    type: getFileType(file.type),
                    name: file.name,
                    size: file.size,
                    url: publicUrl
                };

                // Enviar mensagem
                await sendChatMessage({
                    farm_id: userData.farm_id,
                    sender_id: user.id,
                    receiver_id: window.selectedEmployee.id,
                    message: `📎 ${file.name}`,
                    file_data: fileMessage
                });

                showNotification('Arquivo enviado com sucesso!', 'success');
                
                // Limpar input de arquivo
                const fileInput = document.getElementById('fileInput');
                if (fileInput) {
                    fileInput.value = '';
                }

            } catch (error) {
                console.error('Erro ao enviar arquivo:', error);
                showNotification('Erro ao enviar arquivo', 'error');
            }
        }

        // Determinar tipo de arquivo
        function getFileType(mimeType) {
            if (mimeType.startsWith('image/')) return 'image';
            if (mimeType.startsWith('video/')) return 'video';
            if (mimeType.startsWith('audio/')) return 'audio';
            if (mimeType === 'application/pdf') return 'pdf';
            if (mimeType.includes('word') || mimeType.includes('document')) return 'document';
            return 'file';
        }

        // Esconder picker de emojis ao clicar fora
        document.addEventListener('click', function(event) {
            const emojiPicker = document.getElementById('emojiPicker');
            const emojiButton = event.target.closest('[onclick="toggleEmojiPicker()"]');
            
            if (emojiPicker && !emojiPicker.contains(event.target) && !emojiButton) {
                emojiPicker.classList.add('hidden');
            }
        });

        // ==================== FUNÇÕES DE CATEGORIAS DE EMOJIS ====================
        
        // Mostrar categoria de emojis
        function showEmojiCategory(category) {
            // Esconder todas as categorias
            const categories = document.querySelectorAll('.emoji-category');
            categories.forEach(cat => cat.classList.add('hidden'));
            
            // Mostrar categoria selecionada
            const selectedCategory = document.getElementById('emoji' + category.charAt(0).toUpperCase() + category.slice(1));
            if (selectedCategory) {
                selectedCategory.classList.remove('hidden');
                
                // Carregar emojis se ainda não foram carregados
                if (selectedCategory.children.length === 0) {
                    loadEmojiCategory(category);
                }
            }
            
            // Atualizar botões de categoria
            const categoryBtns = document.querySelectorAll('.emoji-category-btn');
            categoryBtns.forEach(btn => {
                btn.classList.remove('bg-green-100', 'text-green-700');
                btn.classList.add('hover:bg-gray-200');
            });
            
            // Destacar botão selecionado
            const selectedBtn = event.target;
            selectedBtn.classList.add('bg-green-100', 'text-green-700');
            selectedBtn.classList.remove('hover:bg-gray-200');
        }

        // Carregar emojis por categoria
        function loadEmojiCategory(category) {
            const container = document.getElementById('emoji' + category.charAt(0).toUpperCase() + category.slice(1));
            if (!container) return;

            const emojis = {
                gestures: ['👋', '🤚', '🖐️', '✋', '🖖', '👌', '🤏', '✌️', '🤞', '🤟', '🤘', '🤙', '👈', '👉', '👆', '🖕', '👇', '☝️', '👍', '👎', '👊', '✊', '🤛', '🤜', '👏', '🙌', '👐', '🤲', '🤝', '🙏', '✍️', '💅', '🤳', '💪', '🦾', '🦿', '🦵', '🦶', '👂', '🦻', '👃', '🧠', '🦷', '🦴', '👀', '👁️', '👅', '👄', '💋', '🩸'],
                objects: ['📱', '📲', '☎️', '📞', '📟', '📠', '🔋', '🔌', '💻', '🖥️', '🖨️', '⌨️', '🖱️', '🖲️', '💽', '💾', '💿', '📀', '🧮', '🎥', '📷', '📸', '📹', '📼', '🔍', '🔎', '🕯️', '💡', '🔦', '🏮', '🪔', '📔', '📕', '📖', '📗', '📘', '📙', '📚', '📓', '📒', '📃', '📜', '📄', '📰', '🗞️', '📑', '🔖', '🏷️', '💰', '💴', '💵', '💶', '💷', '💸', '💳', '🧾', '💎', '⚖️', '🧰', '🔧', '🔨', '⚒️', '🛠️', '⛏️', '🔩', '⚙️', '🧱', '⛓️', '🧲', '🔫', '💣', '🧨', '🪓', '🔪', '🗡️', '⚔️', '🛡️', '🚬', '⚰️', '🪦', '⚱️', '🏺', '🔮', '📿', '🧿', '💈', '⚗️', '🔭', '🔬', '🕳️', '🩹', '🩺', '💊', '💉', '🧬', '🦠', '🧫', '🧪', '🌡️', '🧹', '🧺', '🧻', '🚽', '🚰', '🚿', '🛁', '🛀', '🧴', '🧷', '🧸', '🧵', '🧶', '🪡', '🪢', '🪣', '🪤', '🪥', '🪦', '🪧', '🪨', '🪩', '🪪', '🪫', '🪬', '🪭', '🪮', '🪯', '🪰', '🪱', '🪲', '🪳', '🪴', '🪵', '🪶', '🪷', '🪸', '🪹', '🪺', '🪻', '🪼', '🪽', '🪾', '🪿', '🫀', '🫁', '🫂', '🫃', '🫄', '🫅', '🫆', '🫇', '🫈', '🫉', '🫊', '🫋', '🫌', '🫍', '🫎', '🫏', '🫐', '🫑', '🫒', '🫓', '🫔', '🫕', '🫖', '🫗', '🫘', '🫙', '🫚', '🫛', '🫜', '🫝', '🫞', '🫟', '🫠', '🫡', '🫢', '🫣', '🫤', '🫥', '🫦', '🫧', '🫨', '🫩', '🫪', '🫫', '🫬', '🫭', '🫮', '🫯', '🫰', '🫱', '🫲', '🫳', '🫴', '🫵', '🫶', '🫷', '🫸', '🫹', '🫺', '🫻', '🫼', '🫽', '🫾', '🫿']
            };

            if (emojis[category]) {
                emojis[category].forEach(emoji => {
                    const button = document.createElement('button');
                    button.className = 'emoji-btn p-2 hover:bg-gray-200 rounded text-lg';
                    button.textContent = emoji;
                    button.onclick = () => insertEmoji(emoji);
                    container.appendChild(button);
                });
            }
        }

        // ==================== FUNÇÕES DE STORAGE ====================
        
        // Função para garantir que o bucket existe
        async function ensureBucketExists(supabase, bucketName) {
            try {
                // Tentar listar o bucket para verificar se existe
                const { data, error } = await supabase.storage.listBuckets();
                
                if (error) {
                    console.error('Erro ao listar buckets:', error);
                    return false;
                }
                
                const bucketExists = data.some(bucket => bucket.name === bucketName);
                
                if (!bucketExists) {
                    console.log(`Bucket ${bucketName} não existe, tentando criar...`);
                    const { data: createData, error: createError } = await supabase.storage.createBucket(bucketName, {
                        public: true,
                        allowedMimeTypes: ['audio/*', 'image/*', 'video/*', 'application/pdf', 'text/*'],
                        fileSizeLimit: 10485760 // 10MB
                    });
                    
                    if (createError) {
                        console.error(`Erro ao criar bucket ${bucketName}:`, createError);
                        return false;
                    }
                    
                    console.log(`Bucket ${bucketName} criado com sucesso`);
                }
                
                return true;
            } catch (error) {
                console.error('Erro ao verificar/criar bucket:', error);
                return false;
            }
        }

        // ==================== FUNÇÕES DE GRAVAÇÃO DE ÁUDIO ====================
        
        let mediaRecorder = null;
        let audioChunks = [];
        let isRecording = false;
        let recordingStartTime = null;
        let recordingTimer = null;

        // Toggle gravação de áudio
        async function toggleAudioRecording() {
            if (!isRecording) {
                await startAudioRecording();
            } else {
                stopAudioRecording();
            }
        }

        // Iniciar gravação de áudio
        async function startAudioRecording() {
            try {
                console.log('=== INICIANDO GRAVAÇÃO DE ÁUDIO ===');
                
                // Verificar se há usuário selecionado
                if (!window.selectedEmployee) {
                    showNotification('Selecione um funcionário primeiro', 'error');
                    return;
                }

                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                mediaRecorder = new MediaRecorder(stream);
                audioChunks = [];

                mediaRecorder.ondataavailable = event => {
                    audioChunks.push(event.data);
                };

                mediaRecorder.onstop = () => {
                    const audioBlob = new Blob(audioChunks, { type: 'audio/wav' });
                    sendAudioMessage(audioBlob);
                    stream.getTracks().forEach(track => track.stop());
                };

                mediaRecorder.start();
                isRecording = true;
                recordingStartTime = Date.now();
                
                // Mostrar área de gravação e esconder input de texto
                showAudioRecordingUI();
                
                // Iniciar timer de duração
                startRecordingTimer();
                
                showNotification('Gravação iniciada', 'info');

            } catch (error) {
                console.error('Erro ao acessar microfone:', error);
                showNotification('Erro ao acessar microfone: ' + error.message, 'error');
            }
        }

        // Parar gravação de áudio
        function stopAudioRecording() {
            if (mediaRecorder && isRecording) {
                console.log('=== PARANDO GRAVAÇÃO DE ÁUDIO ===');
                
                mediaRecorder.stop();
                isRecording = false;
                
                // Parar timer
                if (recordingTimer) {
                    clearInterval(recordingTimer);
                    recordingTimer = null;
                }
                
                // Esconder área de gravação e mostrar input de texto
                hideAudioRecordingUI();
                
                showNotification('Processando áudio...', 'info');
            }
        }

        // Cancelar gravação de áudio
        function cancelAudioRecording() {
            if (mediaRecorder && isRecording) {
                console.log('=== CANCELANDO GRAVAÇÃO DE ÁUDIO ===');
                
                mediaRecorder.stop();
                isRecording = false;
                
                // Parar timer
                if (recordingTimer) {
                    clearInterval(recordingTimer);
                    recordingTimer = null;
                }
                
                // Esconder área de gravação e mostrar input de texto
                hideAudioRecordingUI();
                
                showNotification('Gravação cancelada', 'info');
            }
        }

        // Mostrar interface de gravação
        function showAudioRecordingUI() {
            const audioRecordingArea = document.getElementById('audioRecordingArea');
            const chatMessageInput = document.getElementById('chatMessageInput');
            const sendMessageBtn = document.getElementById('sendMessageBtn');
            const audioRecordBtn = document.getElementById('audioRecordBtn');
            
            // Mostrar área de gravação com animação
            if (audioRecordingArea) {
                audioRecordingArea.classList.remove('hidden');
                // Adicionar animação de entrada
                setTimeout(() => {
                    audioRecordingArea.style.transform = 'scale(1)';
                    audioRecordingArea.style.opacity = '1';
                }, 10);
            }
            
            // Esconder input de texto e botão de envio
            if (chatMessageInput) {
                chatMessageInput.style.display = 'none';
            }
            if (sendMessageBtn) {
                sendMessageBtn.style.display = 'none';
            }
            
            // Atualizar botão de gravação
            if (audioRecordBtn) {
                audioRecordBtn.classList.add('bg-red-100', 'text-red-600', 'border-red-300');
                audioRecordBtn.classList.remove('text-gray-400', 'border-transparent');
            }
        }

        // Esconder interface de gravação
        function hideAudioRecordingUI() {
            const audioRecordingArea = document.getElementById('audioRecordingArea');
            const chatMessageInput = document.getElementById('chatMessageInput');
            const sendMessageBtn = document.getElementById('sendMessageBtn');
            const audioRecordBtn = document.getElementById('audioRecordBtn');
            
            // Esconder área de gravação com animação
            if (audioRecordingArea) {
                audioRecordingArea.style.transform = 'scale(0.95)';
                audioRecordingArea.style.opacity = '0';
                setTimeout(() => {
                    audioRecordingArea.classList.add('hidden');
                }, 200);
            }
            
            // Mostrar input de texto e botão de envio
            if (chatMessageInput) {
                chatMessageInput.style.display = 'block';
            }
            if (sendMessageBtn) {
                sendMessageBtn.style.display = 'block';
            }
            
            // Restaurar botão de gravação
            if (audioRecordBtn) {
                audioRecordBtn.classList.remove('bg-red-100', 'text-red-600', 'border-red-300');
                audioRecordBtn.classList.add('text-gray-400', 'border-transparent');
            }
        }

        // Iniciar timer de gravação
        function startRecordingTimer() {
            recordingTimer = setInterval(() => {
                if (recordingStartTime) {
                    const duration = Date.now() - recordingStartTime;
                    const minutes = Math.floor(duration / 60000);
                    const seconds = Math.floor((duration % 60000) / 1000);
                    const durationText = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                    
                    const durationElement = document.getElementById('recordingDuration');
                    if (durationElement) {
                        durationElement.textContent = durationText;
                    }
                }
            }, 1000);
        }

        // Enviar mensagem de áudio
        async function sendAudioMessage(audioBlob) {
            if (!window.selectedEmployee) {
                showNotification('Selecione um funcionário primeiro', 'error');
                return;
            }

            try {
                console.log('=== ENVIANDO MENSAGEM DE ÁUDIO ===');
                console.log('Tamanho do áudio:', audioBlob.size, 'bytes');
                
                const supabase = await getSupabaseClient();
                const { data: { user } } = await supabase.auth.getUser();
                
                if (!user) return;

                // Buscar farm_id do usuário
                const { data: userData } = await supabase
                    .from('users')
                    .select('farm_id')
                    .eq('id', user.id)
                    .single();

                if (!userData?.farm_id) return;

                // Mostrar loading
                showNotification('Enviando áudio...', 'info');

                // Garantir que o bucket chat-files existe
                const bucketExists = await ensureBucketExists(supabase, 'chat-files');
                const bucketName = bucketExists ? 'chat-files' : 'files';

                // Upload do áudio para Supabase Storage
                const fileName = `audio_${Date.now()}_${Math.random().toString(36).substring(2)}.wav`;
                const filePath = `${userData.farm_id}/${fileName}`;

                const { data: uploadData, error: uploadError } = await supabase.storage
                    .from(bucketName)
                    .upload(filePath, audioBlob);

                if (uploadError) {
                    console.error('Erro no upload:', uploadError);
                    showNotification('Erro ao enviar áudio: ' + uploadError.message, 'error');
                    return;
                }

                // Obter URL pública do áudio
                const { data: { publicUrl } } = supabase.storage
                    .from(bucketName)
                    .getPublicUrl(filePath);

                // Criar mensagem com áudio
                const audioMessage = {
                    type: 'audio',
                    name: 'Mensagem de voz',
                    size: audioBlob.size,
                    url: publicUrl
                };

                // Enviar mensagem
                const messageResult = await sendChatMessage({
                    farm_id: userData.farm_id,
                    sender_id: user.id,
                    receiver_id: window.selectedEmployee.id,
                    message: '🎵 Mensagem de voz',
                    file_data: audioMessage
                });

                console.log('Mensagem de áudio enviada:', messageResult);
                showNotification('Áudio enviado com sucesso!', 'success');

            } catch (error) {
                console.error('Erro ao enviar áudio:', error);
                showNotification('Erro ao enviar áudio: ' + error.message, 'error');
            }
        }

        // ==================== SISTEMA DE CHAMADAS (WebRTC) ====================
        
        let peerConnection = null;
        let localStream = null;
        let remoteStream = null;
        let callState = 'idle'; // idle, calling, ringing, connected, ended
        let callType = 'video'; // video, audio
        let callStartTime = null;
        let callDurationInterval = null;
        let isMuted = false;
        let currentCallId = null;

        // Configuração do WebRTC
        const rtcConfig = {
            iceServers: [
                { urls: 'stun:stun.l.google.com:19302' },
                { urls: 'stun:stun1.l.google.com:19302' }
            ]
        };

        // Iniciar videochamada
        async function startVideoCall() {
            if (!window.selectedEmployee) {
                showNotification('Selecione um funcionário primeiro', 'error');
                return;
            }

            callType = 'video';
            await initiateCall();
        }

        // Iniciar ligação de áudio
        async function startAudioCall() {
            if (!window.selectedEmployee) {
                showNotification('Selecione um funcionário primeiro', 'error');
                return;
            }

            callType = 'audio';
            await initiateCall();
        }

        // Iniciar chamada
        async function initiateCall() {
            try {
                console.log('=== INICIANDO CHAMADA ===');
                console.log('Tipo da chamada:', callType);
                console.log('Usuário selecionado:', window.selectedEmployee);
                
                // Verificar se há usuário selecionado
                if (!window.selectedEmployee) {
                    console.error('Nenhum usuário selecionado para chamada');
                    showNotification('Selecione um usuário para iniciar a chamada', 'error');
                    return;
                }
                
                // Mostrar indicador de ligação IMEDIATAMENTE
                const callMessage = `📞 ${callType === 'video' ? 'Videochamada' : 'Ligação'} iniciada`;
                showNotification(callMessage, 'info');
                
                // Mostrar modal IMEDIATAMENTE - SEMPRE
                console.log('Configurando interface...');
                setupCallInterface('outgoing');
                console.log('Mostrando modal...');
                showCallModal();
                
                // Forçar modal a aparecer mesmo se houver erro
                setTimeout(() => {
                    const modal = document.getElementById('callModal');
                    if (modal && modal.classList.contains('hidden')) {
                        console.log('FORÇANDO MODAL A APARECER...');
                        modal.classList.remove('hidden');
                        modal.style.cssText = `
                            display: flex !important;
                            visibility: visible !important;
                            opacity: 1 !important;
                            z-index: 9999 !important;
                            position: fixed !important;
                            top: 0 !important;
                            left: 0 !important;
                            right: 0 !important;
                            bottom: 0 !important;
                            background-color: rgba(0, 0, 0, 0.9) !important;
                        `;
                    }
                }, 50);
                
                // Obter stream de mídia
                const constraints = {
                    video: callType === 'video',
                    audio: true
                };

                localStream = await navigator.mediaDevices.getUserMedia(constraints);
                console.log('Stream de mídia obtido');
                
                // Atualizar interface com stream
                updateCallInterfaceWithStream();
                
                // Criar peer connection
                peerConnection = new RTCPeerConnection(rtcConfig);
                
                // Adicionar stream local
                localStream.getTracks().forEach(track => {
                    peerConnection.addTrack(track, localStream);
                });

                // Configurar eventos
                setupPeerConnectionEvents();

                // Criar oferta
                const offer = await peerConnection.createOffer();
                await peerConnection.setLocalDescription(offer);

                // Enviar oferta via chat
                await sendCallOffer(offer);

                callState = 'calling';
                updateCallStatus('Chamando...');

            } catch (error) {
                console.error('Erro ao iniciar chamada:', error);
                showNotification('Erro ao iniciar chamada: ' + error.message, 'error');
                
                // Garantir que o modal apareça mesmo com erro
                const modal = document.getElementById('callModal');
                if (modal) {
                    modal.classList.remove('hidden');
                    modal.style.cssText = `
                        display: flex !important;
                        visibility: visible !important;
                        opacity: 1 !important;
                        z-index: 9999 !important;
                        position: fixed !important;
                        top: 0 !important;
                        left: 0 !important;
                        right: 0 !important;
                        bottom: 0 !important;
                        background-color: rgba(0, 0, 0, 0.9) !important;
                    `;
                    console.log('Modal forçado a aparecer após erro');
                }
                
                // Não encerrar a chamada imediatamente, deixar o usuário decidir
                // endCall();
            }
        }

        // Configurar eventos do peer connection
        function setupPeerConnectionEvents() {
            console.log('=== CONFIGURANDO EVENTOS DO PEER CONNECTION ===');
            
            peerConnection.onicecandidate = (event) => {
                if (event.candidate) {
                    console.log('Candidato ICE gerado:', event.candidate);
                    // Enviar candidato ICE via chat
                    sendIceCandidate(event.candidate);
                }
            };

            peerConnection.ontrack = (event) => {
                console.log('Stream remoto recebido:', event.streams[0]);
                remoteStream = event.streams[0];
                const remoteVideo = document.getElementById('remoteVideo');
                if (remoteVideo) {
                    remoteVideo.srcObject = remoteStream;
                    console.log('Vídeo remoto configurado');
                }
            };

            peerConnection.onconnectionstatechange = () => {
                console.log('Estado da conexão:', peerConnection.connectionState);
                if (peerConnection.connectionState === 'connected') {
                    console.log('Conexão estabelecida!');
                    setupCallInterface('connected');
                    callState = 'connected';
                    updateCallStatus('Conectado');
                    startCallTimer();
                }
            };
        }

        // Configurar interface da chamada
        function setupCallInterface(type) {
            console.log('=== CONFIGURANDO INTERFACE ===');
            console.log('Tipo:', type);
            
            const modal = document.getElementById('callModal');
            console.log('Modal encontrado:', !!modal);
            const avatar = document.getElementById('callAvatar');
            const userName = document.getElementById('callUserName');
            const status = document.getElementById('callStatus');
            const mainAvatar = document.getElementById('callMainAvatar');
            const mainUserName = document.getElementById('callMainUserName');
            const mainStatus = document.getElementById('callMainStatus');
            const localVideo = document.getElementById('localVideo');
            const localVideoContainer = document.getElementById('localVideoContainer');
            const remoteVideoContainer = document.getElementById('remoteVideoContainer');
            const acceptBtn = document.getElementById('acceptCallBtn');
            const rejectBtn = document.getElementById('rejectCallBtn');
            const muteBtn = document.getElementById('muteBtn');
            const cameraBtn = document.getElementById('cameraBtn');
            const endBtn = document.getElementById('endCallBtn');
            const startBtn = document.getElementById('startCallBtn');
            const callDuration = document.getElementById('callDuration');

            // Configurar avatar e nome
            if (window.selectedEmployee) {
                const employee = window.selectedEmployee;
                const employeeName = employee.name || 'Funcionário';
                const employeeInitial = (employeeName || 'F').charAt(0).toUpperCase();
                const userColor = generateUserColor(employee.id);
                
                // Header
                if (userName) userName.textContent = employeeName;
                
                // Avatar do header
                if (avatar) {
                if (employee.profile_picture) {
                    avatar.innerHTML = `<img src="${employee.profile_picture}" alt="${employeeName}" class="w-full h-full rounded-full object-cover">`;
                } else {
                    avatar.style.backgroundColor = userColor;
                    avatar.textContent = employeeInitial;
                    }
                }
                
                // Avatar principal
                if (mainAvatar) {
                if (employee.profile_picture) {
                    mainAvatar.innerHTML = `<img src="${employee.profile_picture}" alt="${employeeName}" class="w-full h-full rounded-full object-cover">`;
                } else {
                    mainAvatar.style.backgroundColor = userColor;
                    mainAvatar.textContent = employeeInitial;
                    }
                }
                
                if (mainUserName) mainUserName.textContent = employeeName;
            }

            // Configurar vídeo local
            if (localStream && callType === 'video') {
                localVideo.srcObject = localStream;
                localVideoContainer.classList.remove('hidden');
            }

            // Mostrar/ocultar elementos baseado no tipo
            if (type === 'outgoing') {
                if (acceptBtn) acceptBtn.classList.add('hidden');
                if (rejectBtn) rejectBtn.classList.add('hidden');
                if (startBtn) startBtn.classList.remove('hidden');
                if (endBtn) endBtn.classList.remove('hidden');
                if (muteBtn) muteBtn.classList.add('hidden');
                if (cameraBtn) cameraBtn.classList.add('hidden');
                if (status) status.textContent = 'Chamando...';
                if (mainStatus) mainStatus.textContent = 'Chamando...';
            } else if (type === 'incoming') {
                if (acceptBtn) acceptBtn.classList.remove('hidden');
                if (rejectBtn) rejectBtn.classList.remove('hidden');
                if (startBtn) startBtn.classList.add('hidden');
                if (endBtn) endBtn.classList.add('hidden');
                if (muteBtn) muteBtn.classList.add('hidden');
                if (cameraBtn) cameraBtn.classList.add('hidden');
                if (status) status.textContent = 'Chamada recebida';
                if (mainStatus) mainStatus.textContent = 'Chamada recebida';
            } else if (type === 'connected') {
                if (acceptBtn) acceptBtn.classList.add('hidden');
                if (rejectBtn) rejectBtn.classList.add('hidden');
                if (startBtn) startBtn.classList.add('hidden');
                if (endBtn) endBtn.classList.remove('hidden');
                if (muteBtn) muteBtn.classList.remove('hidden');
                if (cameraBtn) {
                if (callType === 'video') {
                    cameraBtn.classList.remove('hidden');
                } else {
                    cameraBtn.classList.add('hidden');
                }
                }
                if (status) status.textContent = 'Conectado';
                if (mainStatus) mainStatus.textContent = 'Conectado';
                if (callDuration) callDuration.classList.remove('hidden');
            }
        }

        // Mostrar modal de chamada
        function showCallModal() {
            console.log('=== MOSTRANDO MODAL ===');
            const modal = document.getElementById('callModal');
            console.log('Modal encontrado:', !!modal);
            
            if (modal) {
                console.log('Classes do modal antes:', modal.className);
                
                // Forçar remoção da classe hidden
                modal.classList.remove('hidden');
                
                // Garantir que o modal seja visível com estilos inline
                modal.style.cssText = `
                    display: flex !important;
                    visibility: visible !important;
                    opacity: 1 !important;
                    z-index: 9999 !important;
                    position: fixed !important;
                    top: 0 !important;
                    left: 0 !important;
                    right: 0 !important;
                    bottom: 0 !important;
                    background-color: rgba(0, 0, 0, 0.9) !important;
                `;
                
                console.log('Classes do modal depois:', modal.className);
                console.log('Modal de chamada mostrado com sucesso');
                
                // Verificar se realmente está visível
                setTimeout(() => {
                    const isVisible = !modal.classList.contains('hidden') && 
                                    modal.style.display !== 'none' && 
                                    modal.style.visibility !== 'hidden';
                    console.log('Modal realmente visível:', isVisible);
                    if (!isVisible) {
                        console.error('ERRO: Modal não está visível, forçando exibição...');
                        modal.style.cssText = `
                            display: flex !important;
                            visibility: visible !important;
                            opacity: 1 !important;
                            z-index: 9999 !important;
                            position: fixed !important;
                            top: 0 !important;
                            left: 0 !important;
                            right: 0 !important;
                            bottom: 0 !important;
                            background-color: rgba(0, 0, 0, 0.9) !important;
                        `;
                        modal.classList.remove('hidden');
                    }
                }, 100);
                
            } else {
                console.error('ERRO: Modal de chamada não encontrado');
            }
        }

        // Esconder modal de chamada
        function hideCallModal() {
            const modal = document.getElementById('callModal');
            if (modal) {
            modal.classList.add('hidden');
                modal.style.cssText = '';
            }
        }

        // Atualizar status da chamada
        function updateCallStatus(status) {
            const statusElement = document.getElementById('callStatus');
            if (statusElement) {
                statusElement.textContent = status;
            }
        }

        // Aceitar chamada
        async function acceptCall() {
            try {
                // Mostrar notificação de chamada aceita
                showNotification('✅ Chamada aceita', 'success');
                
                // Obter stream de mídia
                const constraints = {
                    video: callType === 'video',
                    audio: true
                };

                localStream = await navigator.mediaDevices.getUserMedia(constraints);
                
                // Configurar peer connection
                peerConnection = new RTCPeerConnection(rtcConfig);
                localStream.getTracks().forEach(track => {
                    peerConnection.addTrack(track, localStream);
                });

                setupPeerConnectionEvents();

                // Configurar interface
                setupCallInterface('connected');
                callState = 'connected';
                updateCallStatus('Conectado');
                startCallTimer();

                // Enviar resposta de aceitação
                await sendCallAnswer();

            } catch (error) {
                console.error('Erro ao aceitar chamada:', error);
                showNotification('Erro ao aceitar chamada: ' + error.message, 'error');
                endCall();
            }
        }

        // Rejeitar chamada
        function rejectCall() {
            showNotification('❌ Chamada rejeitada', 'info');
            sendCallRejection();
            endCall();
        }

        // Encerrar chamada
        function endCall() {
            callState = 'ended';
            
            // Parar streams
            if (localStream) {
                localStream.getTracks().forEach(track => track.stop());
                localStream = null;
            }

            // Fechar peer connection
            if (peerConnection) {
                peerConnection.close();
                peerConnection = null;
            }

            // Parar timer
            if (callDurationInterval) {
                clearInterval(callDurationInterval);
                callDurationInterval = null;
            }

            // Esconder modal
            hideCallModal();

            // Resetar estado
            callStartTime = null;
            isMuted = false;
            currentCallId = null;

            // Enviar notificação de fim de chamada
            sendCallEnd();
            
            // Mostrar notificação de chamada encerrada
            showNotification('📞 Chamada encerrada', 'info');
        }

        // Alternar mute
        function toggleMute() {
            if (localStream) {
                const audioTrack = localStream.getAudioTracks()[0];
                if (audioTrack) {
                    audioTrack.enabled = !audioTrack.enabled;
                    isMuted = !audioTrack.enabled;
                    
                    const muteIcon = document.getElementById('muteIcon');
                    const muteBtn = document.getElementById('muteBtn');
                    
                    if (isMuted) {
                        muteIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" clip-rule="evenodd"></path><path stroke-linecap="round" stroke-linejoin="round" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2"></path>';
                        muteBtn.classList.remove('bg-white');
                        muteBtn.classList.add('bg-red-500', 'text-white');
                    } else {
                        muteIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path>';
                        muteBtn.classList.remove('bg-red-500', 'text-white');
                        muteBtn.classList.add('bg-white', 'text-gray-800');
                    }
                }
            }
        }

        // Alternar câmera
        function toggleCamera() {
            if (localStream) {
                const videoTrack = localStream.getVideoTracks()[0];
                if (videoTrack) {
                    videoTrack.enabled = !videoTrack.enabled;
                    const isCameraOff = !videoTrack.enabled;
                    
                    const cameraIcon = document.getElementById('cameraIcon');
                    const cameraBtn = document.getElementById('cameraBtn');
                    const localVideoContainer = document.getElementById('localVideoContainer');
                    
                    if (isCameraOff) {
                        cameraIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>';
                        cameraBtn.classList.remove('bg-white');
                        cameraBtn.classList.add('bg-red-500', 'text-white');
                        if (localVideoContainer) localVideoContainer.classList.add('hidden');
                    } else {
                        cameraIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>';
                        cameraBtn.classList.remove('bg-red-500', 'text-white');
                        cameraBtn.classList.add('bg-white', 'text-gray-800');
                        if (localVideoContainer && callType === 'video') localVideoContainer.classList.remove('hidden');
                    }
                }
            }
        }

        // Atualizar interface com stream de mídia
        function updateCallInterfaceWithStream() {
            const localVideo = document.getElementById('localVideo');
            const localVideoContainer = document.getElementById('localVideoContainer');
            const remoteVideoContainer = document.getElementById('remoteVideoContainer');
            const callAvatarContainer = document.getElementById('callAvatarContainer');
            
            console.log('=== ATUALIZANDO INTERFACE COM STREAM ===');
            console.log('Local stream:', !!localStream);
            console.log('Call type:', callType);
            
            if (localStream) {
                // Configurar vídeo local
                if (localVideo) {
                    localVideo.srcObject = localStream;
                    console.log('Vídeo local configurado');
                }
                
                // Mostrar vídeo local se for chamada de vídeo
                if (callType === 'video' && localVideoContainer) {
                    localVideoContainer.classList.remove('hidden');
                    console.log('Container de vídeo local mostrado');
                }
                
                // Se for chamada de vídeo, mostrar fundo de vídeo
                if (callType === 'video') {
                    if (callAvatarContainer) {
                        callAvatarContainer.classList.add('hidden');
                        console.log('Avatar escondido para vídeo');
                    }
                    if (remoteVideoContainer) {
                        remoteVideoContainer.classList.remove('hidden');
                        // Por enquanto, mostrar vídeo local como principal
                        const remoteVideo = document.getElementById('remoteVideo');
                        if (remoteVideo) {
                            remoteVideo.srcObject = localStream;
                            console.log('Vídeo remoto configurado com stream local');
                        }
                    }
                }
            }
        }

        // Iniciar timer da chamada
        function startCallTimer() {
            console.log('=== INICIANDO TIMER DA CHAMADA ===');
            callStartTime = Date.now();
            callDurationInterval = setInterval(() => {
                const duration = Date.now() - callStartTime;
                const minutes = Math.floor(duration / 60000);
                const seconds = Math.floor((duration % 60000) / 1000);
                const durationText = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                
                const durationElement = document.getElementById('callDuration');
                if (durationElement) {
                    durationElement.textContent = durationText;
                }
            }, 1000);

            // Mostrar informações da chamada
            const callInfo = document.getElementById('callInfo');
            if (callInfo) {
                callInfo.classList.remove('hidden');
            }
            
            console.log('Timer da chamada iniciado');
        }

        // Enviar oferta de chamada via chat
        async function sendCallOffer(offer) {
            try {
                console.log('=== ENVIANDO OFERTA DE CHAMADA ===');
                console.log('Offer:', offer);
                console.log('Usuário selecionado:', window.selectedEmployee);
                
                const supabase = await getSupabaseClient();
                const { data: { user } } = await supabase.auth.getUser();
                
                if (!user) return;

                const { data: userData } = await supabase
                    .from('users')
                    .select('farm_id')
                    .eq('id', user.id)
                    .single();

                if (!userData?.farm_id) return;

                currentCallId = `call_${Date.now()}_${Math.random().toString(36).substring(2)}`;
                console.log('ID da chamada gerado:', currentCallId);

                const callData = {
                    type: 'call_offer',
                    callId: currentCallId,
                    callType: callType,
                    offer: offer,
                    timestamp: new Date().toISOString()
                };

                const messageResult = await sendChatMessage({
                    farm_id: userData.farm_id,
                    sender_id: user.id,
                    receiver_id: window.selectedEmployee.id,
                    message: `📞 ${callType === 'video' ? 'Videochamada' : 'Ligação'} iniciada`,
                    call_data: callData
                });
                
                console.log('Mensagem de chamada enviada:', messageResult);

            } catch (error) {
                console.error('Erro ao enviar oferta de chamada:', error);
            }
        }

        // Enviar resposta de chamada
        async function sendCallAnswer() {
            try {
                const supabase = await getSupabaseClient();
                const { data: { user } } = await supabase.auth.getUser();
                
                if (!user) return;

                const { data: userData } = await supabase
                    .from('users')
                    .select('farm_id')
                    .eq('id', user.id)
                    .single();

                if (!userData?.farm_id) return;

                const answer = await peerConnection.createAnswer();
                await peerConnection.setLocalDescription(answer);

                const callData = {
                    type: 'call_answer',
                    callId: currentCallId,
                    answer: answer,
                    timestamp: new Date().toISOString()
                };

                const messageResult = await sendChatMessage({
                    farm_id: userData.farm_id,
                    sender_id: user.id,
                    receiver_id: window.selectedEmployee.id,
                    message: `✅ Chamada aceita`,
                    call_data: callData
                });
                
                console.log('Mensagem de aceitação enviada:', messageResult);

            } catch (error) {
                console.error('Erro ao enviar resposta de chamada:', error);
            }
        }

        // Enviar rejeição de chamada
        async function sendCallRejection() {
            try {
                const supabase = await getSupabaseClient();
                const { data: { user } } = await supabase.auth.getUser();
                
                if (!user) return;

                const { data: userData } = await supabase
                    .from('users')
                    .select('farm_id')
                    .eq('id', user.id)
                    .single();

                if (!userData?.farm_id) return;

                const callData = {
                    type: 'call_reject',
                    callId: currentCallId,
                    timestamp: new Date().toISOString()
                };

                const messageResult = await sendChatMessage({
                    farm_id: userData.farm_id,
                    sender_id: user.id,
                    receiver_id: window.selectedEmployee.id,
                    message: `❌ Chamada rejeitada`,
                    call_data: callData
                });
                
                console.log('Mensagem de rejeição enviada:', messageResult);

            } catch (error) {
                console.error('Erro ao enviar rejeição de chamada:', error);
            }
        }

        // Enviar fim de chamada
        async function sendCallEnd() {
            try {
                const supabase = await getSupabaseClient();
                const { data: { user } } = await supabase.auth.getUser();
                
                if (!user) return;

                const { data: userData } = await supabase
                    .from('users')
                    .select('farm_id')
                    .eq('id', user.id)
                    .single();

                if (!userData?.farm_id) return;

                const callData = {
                    type: 'call_end',
                    callId: currentCallId,
                    timestamp: new Date().toISOString()
                };

                const messageResult = await sendChatMessage({
                    farm_id: userData.farm_id,
                    sender_id: user.id,
                    receiver_id: window.selectedEmployee.id,
                    message: `📞 Chamada encerrada`,
                    call_data: callData
                });
                
                console.log('Mensagem de fim de chamada enviada:', messageResult);

            } catch (error) {
                console.error('Erro ao enviar fim de chamada:', error);
            }
        }

        // Enviar candidato ICE
        async function sendIceCandidate(candidate) {
            try {
                const supabase = await getSupabaseClient();
                const { data: { user } } = await supabase.auth.getUser();
                
                if (!user) return;

                const { data: userData } = await supabase
                    .from('users')
                    .select('farm_id')
                    .eq('id', user.id)
                    .single();

                if (!userData?.farm_id) return;

                const callData = {
                    type: 'ice_candidate',
                    callId: currentCallId,
                    candidate: candidate,
                    timestamp: new Date().toISOString()
                };

                const messageResult = await sendChatMessage({
                    farm_id: userData.farm_id,
                    sender_id: user.id,
                    receiver_id: window.selectedEmployee.id,
                    message: '', // Mensagem vazia para não aparecer no chat
                    call_data: callData
                });
                
                console.log('Mensagem de candidato ICE enviada:', messageResult);

            } catch (error) {
                console.error('Erro ao enviar candidato ICE:', error);
            }
        }

        // Processar mensagens de chamada recebidas
        function handleCallMessage(message) {
            if (!message.call_data) return;

            const callData = message.call_data;
            const { type, callId, callType: msgCallType } = callData;
            
            console.log('=== MENSAGEM DE CHAMADA RECEBIDA ===');
            console.log('Tipo:', type);
            console.log('Call ID:', callId);
            console.log('Call Type:', msgCallType);
            console.log('Call Data:', callData);

            switch (type) {
                case 'call_offer':
                    console.log('Processando call_offer...');
                    handleIncomingCall(callData);
                    break;
                case 'call_answer':
                    console.log('Processando call_answer...');
                    handleCallAnswer(callData);
                    break;
                case 'call_reject':
                    console.log('Processando call_reject...');
                    handleCallRejection(callData);
                    break;
                case 'call_end':
                    console.log('Processando call_end...');
                    handleCallEnd(callData);
                    break;
                case 'ice_candidate':
                    console.log('Processando ice_candidate...');
                    handleIceCandidate(callData);
                    break;
                default:
                    console.log('Tipo de mensagem de chamada não reconhecido:', type);
            }
        }

        // Lidar com chamada recebida
        async function handleIncomingCall(callData) {
            console.log('=== CHAMADA INCOMING RECEBIDA ===');
            console.log('CallData:', callData);
            
            currentCallId = callData.callId;
            callType = callData.callType;
            
            // Mostrar indicador de chamada recebida
            const incomingMessage = `📞 ${callType === 'video' ? 'Videochamada' : 'Ligação'} recebida`;
            showNotification(incomingMessage, 'info');
            
            console.log('Configurando interface incoming...');
            setupCallInterface('incoming');
            console.log('Mostrando modal incoming...');
            showCallModal();
            
            // Forçar modal a aparecer
            setTimeout(() => {
                const modal = document.getElementById('callModal');
                if (modal && modal.classList.contains('hidden')) {
                    console.log('FORÇANDO MODAL INCOMING A APARECER...');
                    modal.classList.remove('hidden');
                    modal.style.cssText = `
                        display: flex !important;
                        visibility: visible !important;
                        opacity: 1 !important;
                        z-index: 9999 !important;
                        position: fixed !important;
                        top: 0 !important;
                        left: 0 !important;
                        right: 0 !important;
                        bottom: 0 !important;
                        background-color: rgba(0, 0, 0, 0.9) !important;
                    `;
                }
            }, 50);
            
            // Criar peer connection
            peerConnection = new RTCPeerConnection(rtcConfig);
            setupPeerConnectionEvents();
            
            // Definir descrição remota
            await peerConnection.setRemoteDescription(callData.offer);
            
            // Processar candidatos ICE pendentes
            if (peerConnection.pendingIceCandidates) {
                console.log('🔄 Processando candidatos ICE pendentes...');
                for (const candidate of peerConnection.pendingIceCandidates) {
                    try {
                        await peerConnection.addIceCandidate(candidate);
                        console.log('✅ Candidato ICE pendente adicionado');
                    } catch (error) {
                        console.error('❌ Erro ao adicionar candidato ICE pendente:', error);
                    }
                }
                peerConnection.pendingIceCandidates = [];
            }
        }

        // Lidar com resposta de chamada
        async function handleCallAnswer(callData) {
            if (peerConnection && callData.answer) {
                await peerConnection.setRemoteDescription(callData.answer);
                
                // Mostrar notificação de chamada aceita
                showNotification('✅ Chamada aceita', 'success');
                
                // Processar candidatos ICE pendentes
                if (peerConnection.pendingIceCandidates) {
                    console.log('🔄 Processando candidatos ICE pendentes...');
                    for (const candidate of peerConnection.pendingIceCandidates) {
                        try {
                            await peerConnection.addIceCandidate(candidate);
                            console.log('✅ Candidato ICE pendente adicionado');
                        } catch (error) {
                            console.error('❌ Erro ao adicionar candidato ICE pendente:', error);
                        }
                    }
                    peerConnection.pendingIceCandidates = [];
                }
            }
        }

        // Lidar com rejeição de chamada
        function handleCallRejection(callData) {
            showNotification('❌ Chamada rejeitada', 'info');
            endCall();
        }

        // Lidar com fim de chamada
        function handleCallEnd(callData) {
            showNotification('📞 Chamada encerrada', 'info');
            endCall();
        }

        // Lidar com candidato ICE
        async function handleIceCandidate(callData) {
            if (peerConnection && callData.candidate) {
                try {
                    // Verificar se a descrição remota está definida
                    if (peerConnection.remoteDescription) {
                        await peerConnection.addIceCandidate(callData.candidate);
                        console.log('✅ Candidato ICE adicionado com sucesso');
                    } else {
                        console.log('⏳ Aguardando descrição remota para adicionar candidato ICE');
                        // Armazenar candidato para adicionar depois
                        if (!peerConnection.pendingIceCandidates) {
                            peerConnection.pendingIceCandidates = [];
                        }
                        peerConnection.pendingIceCandidates.push(callData.candidate);
                    }
                } catch (error) {
                    console.error('❌ Erro ao adicionar candidato ICE:', error);
                }
            }
        }

        // Adicionar listener para scroll do chat
        function setupChatScrollListener() {
            const chatContainer = document.getElementById('chatMessages');
            if (!chatContainer) return;
            
            chatContainer.addEventListener('scroll', () => {
                checkIfUserAtBottom();
                
                // Se usuário chegou ao final, esconder indicador
                if (isUserAtBottom) {
                    hideNewMessageIndicator();
                }
            });
        }

        // Pesquisar funcionários
        function searchEmployees(event) {
            const searchTerm = event.target.value.toLowerCase();
            const employeeItems = document.querySelectorAll('#employeesList > div');
            
            employeeItems.forEach(item => {
                const name = item.querySelector('h4').textContent.toLowerCase();
                const role = item.querySelector('p').textContent.toLowerCase();
                
                if (name.includes(searchTerm) || role.includes(searchTerm)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        // Toggle sidebar em mobile
        function toggleChatSidebar() {
            const sidebar = document.getElementById('chatSidebar');
            if (sidebar) {
                sidebar.classList.toggle('hidden');
                sidebar.classList.toggle('flex');
            }
        }

        // ==================== PROFILE FUNCTIONS ====================
        function openProfileModal() {
            document.getElementById('profileModal').classList.add('show');
            loadProfileData();
        }

        function closeProfileModal() {
            document.getElementById('profileModal').classList.remove('show');
        }

        function toggleProfileEdit() {
            const viewMode = document.getElementById('profileViewMode');
            const editMode = document.getElementById('profileEditMode');
            const editBtn = document.getElementById('editProfileBtn');
            
            if (editMode.classList.contains('hidden')) {
                // Entrar em modo de edição
                viewMode.classList.add('hidden');
                editMode.classList.remove('hidden');
                editBtn.innerHTML = `
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    Cancelar
                `;
                editBtn.onclick = cancelProfileEdit;
                
                // Preencher campos de edição
                document.getElementById('editProfileName').value = currentUser.name || '';
                document.getElementById('editProfileEmail').value = currentUser.email || '';
                document.getElementById('editProfileWhatsApp').value = currentUser.whatsapp || '';
            }
        }

        function cancelProfileEdit() {
            const viewMode = document.getElementById('profileViewMode');
            const editMode = document.getElementById('profileEditMode');
            const editBtn = document.getElementById('editProfileBtn');
            
            viewMode.classList.remove('hidden');
            editMode.classList.add('hidden');
            editBtn.innerHTML = `
                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
                Editar
            `;
            editBtn.onclick = toggleProfileEdit;
        }

        async function loadProfileData() {
            if (currentUser) {
                // Atualizar todos os elementos do modal
                document.getElementById('profileName').textContent = currentUser.name || 'Nome não informado';
                document.getElementById('profileFarmName').textContent = currentFarmName || 'Fazenda não informada';
                document.getElementById('profileFullName').textContent = currentUser.name || 'Nome não informado';
                document.getElementById('profileEmail2').textContent = currentUser.email || 'Email não informado';
                
                // Buscar dados extras do usuário (WhatsApp) se existir
                try {
                    const supabase = createSupabaseClient();
                    const { data: userData } = await supabase
                    .from('users')
                        .select('whatsapp')
                        .eq('id', currentUser.id)
                    .single();
                
                    if (userData && userData.whatsapp) {
                        document.getElementById('profileWhatsApp').textContent = userData.whatsapp;
                        currentUser.whatsapp = userData.whatsapp;
                }
            } catch (error) {
                    log('Erro ao carregar WhatsApp: ' + error.message);
                }
                
                // Load profile photo
                await loadProfilePhoto();
            }
        }

        async function loadProfilePhoto() {
            try {
                // Verificar se o usuário está logado
                if (!currentUser || !currentUser.id) {
                    log('Usuário não logado, pulando carregamento de foto');
                    return;
                }
                
                log('Tentando carregar foto de perfil para usuário: ' + currentUser.id);
                
                const supabase = createSupabaseClient();
                
                // Buscar dados atualizados do usuário incluindo a foto
                const { data: userData, error: userError } = await supabase
                    .from('users')
                    .select('profile_photo_url')
                    .eq('id', currentUser.id)
                    .single();
                
                if (userError) {
                    log('Erro ao buscar dados do usuário: ' + userError.message);
                    return;
                }
                
                // Verificar se há URL da foto
                if (!userData.profile_photo_url || userData.profile_photo_url.trim() === '') {
                    log('Usuário não possui foto de perfil');
                    return;
                }
                
                log('URL da foto encontrada');
                
                // Update all profile images
                const headerPhoto = document.getElementById('headerProfilePhoto');
                const modalPhoto = document.getElementById('modalProfilePhoto');
                const headerIcon = document.getElementById('headerProfileIcon');
                const modalIcon = document.getElementById('modalProfileIcon');
                
                if (headerPhoto && headerIcon) {
                    headerPhoto.src = userData.profile_photo_url + '?t=' + Date.now();
                        headerPhoto.classList.remove('hidden');
                        headerIcon.classList.add('hidden');
                    
                    // Adicionar tratamento de erro para a imagem
                    headerPhoto.onerror = function() {
                        log('Erro ao carregar foto no header, usando ícone');
                        headerPhoto.style.display = 'none';
                        headerIcon.classList.remove('hidden');
                    };
                    
                    headerPhoto.onload = function() {
                        log('Foto carregada com sucesso no header');
                        headerIcon.classList.add('hidden');
                    };
                }
                
                if (modalPhoto && modalIcon) {
                    modalPhoto.src = userData.profile_photo_url + '?t=' + Date.now();
                        modalPhoto.classList.remove('hidden');
                        modalIcon.classList.add('hidden');
                    
                    // Adicionar tratamento de erro para a imagem
                    modalPhoto.onerror = function() {
                        log('Erro ao carregar foto no modal, usando ícone');
                        modalPhoto.style.display = 'none';
                        modalIcon.classList.remove('hidden');
                    };
                    
                    modalPhoto.onload = function() {
                        log('Foto carregada com sucesso no modal');
                        modalIcon.classList.add('hidden');
                    };
                }
                
                log('Foto de perfil carregada com sucesso');
                
            } catch (error) {
                log('Erro geral ao carregar foto de perfil: ' + error.message);
                // Não mostrar erro para o usuário, apenas usar ícone padrão
            }
        }

        async function uploadPhoto(input) {
            if (!input.files || !input.files[0]) return;
            
            const file = input.files[0];
            
            // Validate file type and size
            if (!file.type.startsWith('image/')) {
                showNotification('Por favor, selecione apenas arquivos de imagem.', 'error');
                return;
            }
            
            if (file.size > 5 * 1024 * 1024) { // 5MB
                showNotification('A imagem deve ter no máximo 5MB.', 'error');
                return;
            }
            
            try {
                log('Fazendo upload da foto...');
                
                const supabase = createSupabaseClient();
                
                // Verificar se o usuário está logado
                if (!currentUser || !currentUser.id) {
                    showNotification('Erro: Usuário não autenticado', 'error');
                    return;
                }
                
                // Buscar farm_id do usuário
                const { data: userData, error: userError } = await supabase
                    .from('users')
                    .select('farm_id')
                    .eq('id', currentUser.id)
                    .single();
                
                if (userError || !userData?.farm_id) {
                    showNotification('Erro: Dados do usuário não encontrados', 'error');
                    return;
                }
                
                // Criar nome único para o arquivo
                const fileExt = file.name.split('.').pop();
                const timestamp = Date.now();
                const randomId = Math.random().toString(36).substr(2, 9);
                const fileName = `user_${currentUser.id}_${timestamp}_${randomId}.${fileExt}`;
                const filePath = `farm_${userData.farm_id}/${fileName}`;
                
                log('Fazendo upload para: ' + filePath);
                
                // Upload to storage
                const { data, error } = await supabase.storage
                    .from('profile-photos')
                    .upload(filePath, file, {
                        cacheControl: '3600',
                        upsert: false
                    });
                
                if (error) {
                    // Se o erro for relacionado ao bucket não existir
                    if (error.message.includes('bucket') || error.message.includes('not found')) {
                        log('Bucket profile-photos não encontrado');
                        showNotification('Erro: Armazenamento de fotos não configurado. Entre em contato com o administrador.', 'error');
                        return;
                    }
                    throw error;
                }
                
                // Gerar URL pública
                const { data: { publicUrl } } = supabase.storage
                    .from('profile-photos')
                    .getPublicUrl(filePath);
                
                log('URL pública gerada: ' + publicUrl);
                
                // Salvar URL no banco de dados
                const { error: updateError } = await supabase
                    .from('users')
                    .update({ profile_photo_url: publicUrl })
                    .eq('id', currentUser.id);
                
                if (updateError) {
                    log('Erro ao salvar URL no banco: ' + updateError.message);
                    showNotification('Erro ao salvar foto no perfil', 'error');
                    return;
                }
                
                log('Foto enviada e salva com sucesso!');
                showNotification('Foto enviada com sucesso!', 'success');
                await loadProfilePhoto();

            } catch (error) {
                log('Erro no upload: ' + error.message);
                showNotification('Erro ao fazer upload da foto: ' + error.message, 'error');
            }
        }

        async function handleUpdateProfile(event) {
            event.preventDefault();
            log('Atualizando perfil...');
            
            try {
                const supabase = createSupabaseClient();
                const formData = new FormData(event.target);
                const name = formData.get('name');
                const whatsapp = formData.get('whatsapp');
                
                // Atualizar dados do usuário
                const { error } = await supabase
                    .from('users')
                    .update({
                        name: name,
                        whatsapp: whatsapp
                    })
                    .eq('id', currentUser.id);
                
                if (error) {
                    throw error;
                }
                
                // Atualizar variáveis locais
                currentUser.name = name;
                currentUser.whatsapp = whatsapp;
                
                // Atualizar display
                const firstName = name.split(' ')[0];
                document.getElementById('employeeName').textContent = firstName;
                document.getElementById('employeeWelcome').textContent = firstName;
                
                // Recarregar dados do modal
                await loadProfileData();
                
                // Voltar para modo de visualização
                cancelProfileEdit();
                
                log('Perfil atualizado com sucesso!');
                window.showAlert('Perfil atualizado com sucesso!', { type: 'success', title: 'Sucesso!' });

            } catch (error) {
                log('ERRO ao atualizar perfil: ' + error.message);
                window.showAlert('Erro ao atualizar perfil: ' + error.message, { type: 'error', title: 'Erro!' });
            }
        }

        async function handleChangePassword(event) {
            event.preventDefault();
            log('Alterando senha...');
            
            try {
                const supabase = createSupabaseClient();
                const formData = new FormData(event.target);
            const currentPassword = formData.get('current_password');
            const newPassword = formData.get('new_password');
            const confirmPassword = formData.get('confirm_password');

                // Validar senhas
            if (newPassword !== confirmPassword) {
                    window.showAlert('As senhas não coincidem!', { type: 'error', title: 'Erro de Validação' });
                return;
            }

                if (newPassword.length < 6) {
                    window.showAlert('A nova senha deve ter pelo menos 6 caracteres!', { type: 'error', title: 'Erro de Validação' });
                    return;
                }
                
                // Alterar senha no Supabase
                const { error } = await supabase.auth.updateUser({
                    password: newPassword
                });

                if (error) {
                    throw error;
                }
                
                log('Senha alterada com sucesso!');
                window.showAlert('Senha alterada com sucesso!', { type: 'success', title: 'Sucesso!' });
                
                // Limpar formulário
                event.target.reset();

            } catch (error) {
                log('ERRO ao alterar senha: ' + error.message);
                window.showAlert('Erro ao alterar senha: ' + error.message, { type: 'error', title: 'Erro!' });
            }
        }

        // Função para limpar completamente a sessão
        function clearUserSession() {
            log('🧹 Limpando sessão do usuário...');
            
            // Limpar atualizações em tempo real
            cleanupRealtimeUpdates();
            
            // Limpar dados locais
            currentUser = null;
            currentFarmId = null;
            currentFarmName = null;
            allProductionHistory = [];
            
            // Limpar localStorage
            localStorage.removeItem('userData');
            localStorage.removeItem('userSession');
            localStorage.removeItem('farmData');
            
            // Limpar sessionStorage
            sessionStorage.removeItem('userData');
            sessionStorage.removeItem('userSession');
            sessionStorage.removeItem('farmData');
            sessionStorage.removeItem('redirectCount');
            
            // Limpar cookies relacionados
            document.cookie.split(";").forEach(function(c) { 
                document.cookie = c.replace(/^ +/, "").replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/"); 
            });
            
            log('✅ Sessão limpa com sucesso');
        }

        // Função para gerenciar redirecionamentos
        function safeRedirect(url) {
            const currentCount = parseInt(sessionStorage.getItem('redirectCount') || '0');
            sessionStorage.setItem('redirectCount', (currentCount + 1).toString());
            
            log(`🔄 Redirecionando para ${url} (tentativa ${currentCount + 1})`);
            window.location.replace(url);
        }

        // Função para testar atualizações em tempo real
        async function testRealtimeUpdate() {
            try {
                log('🧪 Testando atualizações em tempo real...');
                
                const supabase = createSupabaseClient();
                
                // Criar um registro de teste
                const testData = {
                    farm_id: currentFarmId,
                    volume_liters: Math.random() * 10 + 5, // Volume aleatório entre 5-15L
                    production_date: new Date().toISOString().split('T')[0],
                    temperature: Math.random() * 5 + 15, // Temperatura aleatória entre 15-20°C
                    notes: 'Teste de tempo real - ' + new Date().toLocaleTimeString()
                };
                
                const { data, error } = await supabase
                    .from('volume_records')
                    .insert([testData])
                    .select();
                
                if (error) {
                    throw error;
                }
                
                log('✅ Dados de teste inseridos:', data);
                showNotification('Teste de tempo real executado! Verifique as atualizações automáticas.', 'success');
                
                // Remover o registro de teste após 5 segundos
                setTimeout(async () => {
                    try {
                        if (data && data[0]) {
                            await supabase
                                .from('volume_records')
                                .delete()
                                .eq('id', data[0].id);
                            
                            log('🧹 Registro de teste removido');
                        }
                    } catch (deleteError) {
                        log('❌ Erro ao remover registro de teste:', deleteError.message);
                    }
                }, 5000);
                
            } catch (error) {
                log('❌ Erro no teste de tempo real:', error.message);
                showNotification('Erro no teste de tempo real: ' + error.message, 'error');
            }
        }


        // Função para configurar atualizações em tempo real
        function setupRealtimeUpdates() {
            try {
                log('🔌 Configurando atualizações em tempo real...');
                
                const supabase = createSupabaseClient();
                
                // 1. Escutar mudanças na tabela volume_records (produção)
                const volumeSubscription = supabase
                    .channel('volume_records_changes')
                    .on(
                        'postgres_changes',
                        {
                            event: '*', // INSERT, UPDATE, DELETE
                            schema: 'public',
                            table: 'volume_records',
                            filter: `farm_id=eq.${currentFarmId}`
                        },
                        async (payload) => {
                            log('📊 Mudança detectada em volume_records:', payload.eventType);
                            
                            // Atualizar apenas os componentes necessários
                            switch (payload.eventType) {
                                case 'INSERT':
                                    await handleNewProduction(payload.new);
                                    break;
                                case 'UPDATE':
                                    await handleProductionUpdate(payload.new, payload.old);
                                    break;
                                case 'DELETE':
                                    await handleProductionDelete(payload.old);
                                    break;
                            }
                        }
                    )
                    .subscribe();

                // 2. Escutar mudanças na tabela users (perfil)
                const userSubscription = supabase
                    .channel('user_profile_changes')
                    .on(
                        'postgres_changes',
                        {
                            event: 'UPDATE',
                            schema: 'public',
                            table: 'users',
                            filter: `id=eq.${currentUser.id}`
                        },
                        async (payload) => {
                            log('👤 Mudança detectada no perfil do usuário');
                            await handleProfileUpdate(payload.new);
                        }
                    )
                    .subscribe();

                // 3. Escutar mudanças na tabela farms (nome da fazenda)
                const farmSubscription = supabase
                    .channel('farm_changes')
                    .on(
                        'postgres_changes',
                        {
                            event: 'UPDATE',
                            schema: 'public',
                            table: 'farms',
                            filter: `id=eq.${currentFarmId}`
                        },
                        async (payload) => {
                            log('🏭 Mudança detectada na fazenda');
                            await handleFarmUpdate(payload.new);
                        }
                    )
                    .subscribe();

                // Armazenar referências das subscriptions
                realtimeSubscriptions = [
                    volumeSubscription,
                    userSubscription,
                    farmSubscription
                ];

                log('✅ Atualizações em tempo real configuradas com sucesso!');
                
                // Mostrar indicador visual
                const indicator = document.getElementById('realtimeIndicator');
                if (indicator) {
                    indicator.classList.remove('hidden');
                }
                
            } catch (error) {
                log('❌ Erro ao configurar atualizações em tempo real:', error.message);
            }
        }

        // Função para limpar todas as subscriptions
        function cleanupRealtimeUpdates() {
            try {
                log('🧹 Limpando atualizações em tempo real...');
                
                realtimeSubscriptions.forEach(subscription => {
                    if (subscription && subscription.unsubscribe) {
                        subscription.unsubscribe();
                    }
                });
                
                realtimeSubscriptions = [];
                
                // Esconder indicador visual
                const indicator = document.getElementById('realtimeIndicator');
                if (indicator) {
                    indicator.classList.add('hidden');
                }
                
                log('✅ Atualizações em tempo real limpas');
                
            } catch (error) {
                log('❌ Erro ao limpar atualizações em tempo real:', error.message);
            }
        }

        // Handlers para mudanças em tempo real
        async function handleNewProduction(newProduction) {
            try {
                log('🆕 Nova produção detectada:', newProduction);
                
                // Atualizar indicadores do dashboard
                await loadDashboardIndicators();
                
                // Atualizar atividades recentes
                await loadRecentActivity();
                
                // Atualizar gráfico de produção
                await loadProductionChart();
                
                // Carregar histórico
                    await loadHistory();
                
                // Mostrar notificação
                showNotification(`Nova produção registrada: ${newProduction.volume_liters}L`, 'success');
                
            } catch (error) {
                log('❌ Erro ao processar nova produção:', error.message);
            }
        }

        async function handleProductionUpdate(newProduction, oldProduction) {
            try {
                log('🔄 Produção atualizada:', { old: oldProduction, new: newProduction });
                
                // Atualizar indicadores do dashboard
                await loadDashboardIndicators();
                
                // Atualizar atividades recentes
                await loadRecentActivity();
                
                // Atualizar gráfico de produção
                await loadProductionChart();
                
                // Carregar histórico
                    await loadHistory();
                
                // Mostrar notificação
                showNotification('Produção atualizada com sucesso!', 'info');
                
            } catch (error) {
                log('❌ Erro ao processar atualização de produção:', error.message);
            }
        }

        async function handleProductionDelete(deletedProduction) {
            try {
                log('🗑️ Produção deletada:', deletedProduction);
                
                // Atualizar indicadores do dashboard
                await loadDashboardIndicators();
                
                // Atualizar atividades recentes
                await loadRecentActivity();
                
                // Atualizar gráfico de produção
                await loadProductionChart();
                
                // Carregar histórico
                    await loadHistory();
                
                // Mostrar notificação
                showNotification('Produção removida com sucesso!', 'info');
                
            } catch (error) {
                log('❌ Erro ao processar remoção de produção:', error.message);
            }
        }

        async function handleProfileUpdate(updatedUser) {
            try {
                log('👤 Perfil atualizado:', updatedUser);
                
                // Atualizar dados locais
                if (updatedUser.name) {
                    currentUser.name = updatedUser.name;
                    
                    // Atualizar elementos da interface
                    const firstName = updatedUser.name.split(' ')[0];
                    const employeeName = document.getElementById('employeeName');
                    const employeeWelcome = document.getElementById('employeeWelcome');
                    
                    if (employeeName) employeeName.textContent = firstName;
                    if (employeeWelcome) employeeWelcome.textContent = firstName;
                }
                
                // Atualizar foto de perfil se mudou
                if (updatedUser.profile_photo_url) {
                    await loadProfilePhoto();
                }
                
                // Mostrar notificação
                showNotification('Perfil atualizado com sucesso!', 'success');
                
            } catch (error) {
                log('❌ Erro ao processar atualização de perfil:', error.message);
            }
        }

        async function handleFarmUpdate(updatedFarm) {
            try {
                log('🏭 Fazenda atualizada:', updatedFarm);
                
                // Atualizar dados locais
                if (updatedFarm.name) {
                    currentFarmName = updatedFarm.name;
                    
                    // Atualizar elementos da interface
                    const farmNameHeader = document.getElementById('farmNameHeader');
                    if (farmNameHeader) farmNameHeader.textContent = updatedFarm.name;
                }
                
                // Mostrar notificação
                showNotification('Dados da fazenda atualizados!', 'info');
                
            } catch (error) {
                log('❌ Erro ao processar atualização da fazenda:', error.message);
            }
        }

        async function logout() {
            try {
                log('🚪 Iniciando logout...');
                
                // Limpar atualizações em tempo real
                cleanupRealtimeUpdates();
                
                // Limpar sessão
                clearUserSession();
                
                // Fazer logout no Supabase
                const supabase = createSupabaseClient();
                await supabase.auth.signOut();
                
                log('✅ Logout realizado com sucesso');
                safeRedirect('login.php');
                
            } catch (error) {
                log('❌ Erro no logout: ' + error.message);
                cleanupRealtimeUpdates();
                clearUserSession();
                safeRedirect('login.php');
            }
        }

        // ==================== DATA LOADING ====================
        async function carregarDados() {
            log('Iniciando carregamento de dados...');
            
            try {
                // Usar cliente Supabase global
                const supabase = createSupabaseClient();
                
                // Usar cache para dados do usuário
                const userData = await CacheManager.getUserData();
                
                if (!userData) {
                    log('❌ Usuário não autenticado no Supabase');
                    // Limpar dados de sessão locais
                    clearUserSession();
                    
                    showNotification('Sessão expirada. Redirecionando para login...', 'error');
                    setTimeout(() => {
                        safeRedirect('login.php');
                    }, 2000);
                    return;
                }

                currentUser = userData;
                log('Usuário autenticado com sucesso');
                
                // Verificar se o usuário está bloqueado
                if (!userData.is_active) {
                    log('❌ Usuário bloqueado');
                    showNotification('Conta bloqueada. Redirecionando...', 'error');
                    setTimeout(() => {
                        safeRedirect('acesso-bloqueado.php');
                    }, 2000);
                    return;
                }
                
                currentFarmId = userData.farm_id;
                currentUser.name = userData.name;
                log('Dados do usuário carregados com sucesso');
                
                // Usar cache para dados da fazenda
                const farmData = await CacheManager.getFarmData();
                
                if (farmData) {
                    currentFarmName = farmData.name;
                    document.getElementById('farmNameHeader').textContent = farmData.name;
                    log('Nome da fazenda: ' + farmData.name);
                } else {
                    log('Erro ao buscar fazenda: Não encontrada');
                }
                
                // Atualizar nome
                const nomeElement = document.getElementById('employeeName');
                const welcomeElement = document.getElementById('employeeWelcome');
                
                if (userData.name) {
                    const firstName = userData.name.split(' ')[0];
                    if (nomeElement) nomeElement.textContent = firstName;
                    if (welcomeElement) welcomeElement.textContent = firstName;
                    log('Nome atualizado: ' + firstName);
                }
                
                // Carregar dados do dashboard
                await loadDashboardIndicators();
                await loadRecentActivity();
                await loadProductionChart();
                
                // Carregar histórico
                await loadHistory();
                
                // Carregar notificações
                await loadNotifications();
                
                // Carregar foto de perfil
                await loadProfilePhoto();
                
                // Atualizar data/hora
                updateDateTime();
                
                // Definir data atual no formulário
                setCurrentDate();
                
                
                log('✅ Carregamento de dados concluído com SUCESSO!');

            } catch (error) {
                log('❌ ERRO GERAL: ' + error.message);
                console.error('Erro completo:', error);
                
                // Em caso de erro, limpar dados e redirecionar
                clearUserSession();
                
                setTimeout(() => {
                    safeRedirect('login.php');
                }, 2000);
            }
        }

        async function loadDashboardIndicators() {
            log('Carregando indicadores do dashboard...');
            
            try {
                const supabase = createSupabaseClient();
                const hoje = new Date().toISOString().split('T')[0];
                
                // Volume de hoje
                if (!currentFarmId) {
                    console.warn('⚠️ currentFarmId não está definido, pulando carregamento de indicadores');
                    return;
                }
                
                const { data: producaoHoje } = await supabase
                    .from('volume_records')
                    .select('volume_liters')
                    .eq('farm_id', currentFarmId)
                    .eq('production_date', hoje);
                
                let volumeHoje = 0;
                let registrosHoje = 0;
                
                if (producaoHoje) {
                    volumeHoje = producaoHoje.reduce((sum, item) => sum + parseFloat(item.volume_liters || 0), 0);
                    registrosHoje = producaoHoje.length;
                }
                
                // Média semanal (últimos 7 dias)
                const seteDiasAtras = new Date();
                seteDiasAtras.setDate(seteDiasAtras.getDate() - 6);
                
                const { data: producaoSemana } = await supabase
                    .from('volume_records')
                    .select('volume_liters, production_date')
                    .eq('farm_id', currentFarmId)
                    .gte('production_date', seteDiasAtras.toISOString().split('T')[0]);
                
                let mediaSemana = 0;
                if (producaoSemana && producaoSemana.length > 0) {
                    // Agrupar por data para média diária
                    const volumesPorDia = {};
                    producaoSemana.forEach(item => {
                        if (!volumesPorDia[item.production_date]) {
                            volumesPorDia[item.production_date] = 0;
                        }
                        volumesPorDia[item.production_date] += parseFloat(item.volume_liters || 0);
                    });
                    
                    const totalDias = Object.keys(volumesPorDia).length;
                    const totalVolume = Object.values(volumesPorDia).reduce((sum, vol) => sum + vol, 0);
                    mediaSemana = totalDias > 0 ? totalVolume / totalDias : 0;
                }
                
                // Melhor dia do mês
                const inicioMes = new Date();
                inicioMes.setDate(1);
                
                const { data: producaoMes } = await supabase
                    .from('volume_records')
                    .select('volume_liters, production_date')
                    .eq('farm_id', currentFarmId)
                    .gte('production_date', inicioMes.toISOString().split('T')[0]);
                
                let melhorDia = 0;
                if (producaoMes) {
                    const volumesPorDia = {};
                    producaoMes.forEach(item => {
                        if (!volumesPorDia[item.production_date]) {
                            volumesPorDia[item.production_date] = 0;
                        }
                        volumesPorDia[item.production_date] += parseFloat(item.volume_liters || 0);
                    });
                    melhorDia = Math.max(...Object.values(volumesPorDia), 0);
                }
                
                // Atualizar elementos
                document.getElementById('todayVolume').textContent = volumeHoje.toFixed(1) + ' L';
                document.getElementById('todayRecords').textContent = registrosHoje;
                document.getElementById('weekAverage').textContent = mediaSemana.toFixed(1) + ' L';
                document.getElementById('bestDay').textContent = melhorDia.toFixed(1) + ' L';
                
                log('Indicadores atualizados');
                
            } catch (error) {
                log('ERRO ao carregar indicadores: ' + error.message);
            }
        }

        async function loadRecentActivity() {
            log('Carregando atividades recentes...');
            
            try {
                const supabase = createSupabaseClient();
                if (!currentFarmId) {
                    console.warn('⚠️ currentFarmId não está definido, pulando carregamento de atividades');
                    return;
                }
                
                const { data: atividades } = await supabase
                    .from('volume_records')
                    .select('volume_liters, production_date, created_at')
                    .eq('farm_id', currentFarmId)
                    .order('created_at', { ascending: false })
                    .limit(5);
                
                const activityList = document.getElementById('activityList');
                if (activityList) {
                    if (!atividades || atividades.length === 0) {
                        activityList.innerHTML = `
                            <div class="text-center py-8">
                                <p class="text-gray-500 text-sm">Nenhuma atividade recente</p>
                                <p class="text-gray-400 text-xs">Registre dados para ver o histórico</p>
                    </div>
                `;
                    } else {
                        activityList.innerHTML = atividades.map(ativ => {
                            const data = new Date(ativ.created_at);
                            const tempo = data.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
                            const dataProducao = new Date(ativ.production_date).toLocaleDateString('pt-BR');
                
                return `
                                <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-b-0">
                            <div>
                                        <p class="text-sm font-medium text-gray-900">${ativ.volume_liters}L - ${dataProducao}</p>
                                        <p class="text-xs text-gray-500">${tempo}</p>
                        </div>
                    </div>
                `;
            }).join('');
                    }
                    log('Atividades carregadas');
                }
                
            } catch (error) {
                log('ERRO ao carregar atividades: ' + error.message);
            }
        }

        // Load dashboard weekly production chart (last 7 days) - COPIADO DO GERENTE
        async function loadProductionChart() {
            try {
                console.log('🔄 Carregando gráfico de produção semanal...');
                
                // Verificar se Chart.js está disponível
                if (typeof Chart === 'undefined') {
                    console.error('❌ Chart.js não está disponível! Tentando novamente em 2 segundos...');
                    setTimeout(() => {
                        loadProductionChart();
                    }, 2000);
                    return;
                }
                
                const supabase = createSupabaseClient();
                if (!supabase) {
                    console.error('❌ Supabase não disponível');
                    return;
                }

                // Usar cache para dados do usuário
                const userData = await CacheManager.getUserData();
                
                if (!userData?.farm_id) {
                    console.error('❌ Farm ID não encontrado');
                    return;
                }

                console.log('🏡 Farm ID:', userData.farm_id);

                // Get last 7 days of data
                const endDate = new Date();
                const startDate = new Date();
                startDate.setDate(startDate.getDate() - 6);

                console.log('📅 Período:', startDate.toISOString().split('T')[0], 'até', endDate.toISOString().split('T')[0]);

                // Usar cache para dados de volume semanal
                const productionData = await CacheManager.getVolumeData(userData.farm_id, 'week');

                if (!productionData) {
                    console.error('❌ Erro ao buscar dados de produção');
                    return;
                }

                console.log('📊 Dados de produção encontrados:', productionData?.length || 0, 'registros');

                // Group by date and sum volumes
                const dailyProduction = {};
                const labels = [];
                
                // Initialize all days with 0
                for (let i = 0; i < 7; i++) {
                    const date = new Date(startDate);
                    date.setDate(date.getDate() + i);
                    const dateStr = date.toISOString().split('T')[0];
                    const dayName = date.toLocaleDateString('pt-BR', { weekday: 'short' });
                    labels.push(dayName);
                    dailyProduction[dateStr] = 0;
                }

                // Sum production by date
                if (productionData && productionData.length > 0) {
                    productionData.forEach(record => {
                        if (dailyProduction.hasOwnProperty(record.production_date)) {
                            dailyProduction[record.production_date] += record.volume_liters || 0;
                        }
                    });
                }
                
                const data = Object.values(dailyProduction);
                console.log('📈 Dados processados:', { labels, data });

                // Update chart
                if (window.productionChart && window.productionChart.data) {
                    console.log('✅ Atualizando gráfico...');
                    window.productionChart.data.labels = labels;
                    if (window.productionChart.data.datasets && window.productionChart.data.datasets[0]) {
                    window.productionChart.data.datasets[0].data = data;
                    }
                    window.productionChart.update();
                    console.log('✅ Gráfico atualizado com sucesso');
                } else {
                    console.log('🔄 Gráfico não encontrado, criando novo...');
                    // Tentar reinicializar o gráfico
                    const productionCtx = document.getElementById('productionChart');
                    if (productionCtx) {
                        console.log('✅ Canvas encontrado, criando gráfico...');
                        window.productionChart = new Chart(productionCtx, {
                            type: 'bar',
                        data: {
                            labels: labels,
                            datasets: [{
                                    label: 'Produção (L)',
                                    data: data,
                                    backgroundColor: '#5bb85b',
                                    borderRadius: 8
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                    legend: {
                                        display: false
                                    }
                            },
                            scales: {
                                y: {
                                        beginAtZero: true
                                }
                            }
                        }
                    });
                        console.log('✅ Gráfico reinicializado com sucesso');
                    } else {
                        console.error('❌ Canvas productionChart não encontrado');
                    }
                }
                
            } catch (error) {
                console.error('❌ Erro ao carregar gráfico de produção:', error);
                log('ERRO ao carregar gráfico: ' + error.message);
                showNotification('Erro ao carregar gráfico de produção', 'error');
            }
        }


        // ==================== FORM FUNCTIONS ====================
        function setCurrentDate() {
            const today = new Date().toISOString().split('T')[0];
            const dateInput = document.getElementById('productionDate');
            if (dateInput) {
                dateInput.value = today;
            }
        }

        async function resetForm() {
            // Mostrar confirmação antes de limpar
            const confirmed = await showConfirmDialog('Tem certeza que deseja limpar o formulário? Todos os dados serão perdidos.', 'clear');
            if (confirmed) {
                clearForm();
            }
        }

        function clearForm() {
                document.getElementById('productionForm').reset();
                setCurrentDate();
                showNotification('Formulário limpo com sucesso!', 'success');
        }

        async function registerProduction(event) {
            event.preventDefault();
            log('Registrando produção...');
            
            try {
                const supabase = createSupabaseClient();
                
                // Validar se o usuário está logado
                if (!currentUser || !currentUser.id) {
                    showNotification('Erro: Usuário não autenticado', 'error');
                    return;
                }
                
                // Validar se farm_id está disponível
                if (!currentFarmId) {
                    showNotification('Erro: ID da fazenda não encontrado', 'error');
                    return;
                }
                
                const formData = new FormData(event.target);
                
                // Validações dos campos
                const volume = parseFloat(formData.get('volume'));
                const productionDate = formData.get('productionDate');
                
                if (!volume || volume <= 0) {
                    showNotification('Por favor, insira um volume válido', 'error');
                    return;
                }
                
                if (!productionDate) {
                    showNotification('Por favor, selecione uma data', 'error');
                    return;
                }
                
                const productionData = {
                    farm_id: currentFarmId,
                    volume_liters: volume,
                    production_date: productionDate,
                    temperature: formData.get('temperature') ? parseFloat(formData.get('temperature')) : null,
                    notes: formData.get('notes') || null
                };
                
                log('Dados para inserir: ' + JSON.stringify(productionData));
                
                const { data, error } = await supabase
                    .from('volume_records')
                    .insert([productionData])
                    .select();
                
                if (error) {
                    log('ERRO ao registrar: ' + error.message);
                    log('Detalhes do erro: ' + JSON.stringify(error));
                    showNotification('Erro ao registrar produção: ' + error.message, 'error');
                    return;
                }
                
                log('Produção registrada com sucesso!');
                showNotification('Produção registrada com sucesso!', 'success');
                
                // Enviar notificação para o gerente
                await sendNotificationToManager(volume, productionDate, currentUser.name);
                
                // Clear form and reload data
                clearForm();
                await loadDashboardIndicators();
                await loadRecentActivity();
                await loadProductionChart();
                
                // Switch to dashboard
                switchToTab('dashboard');
                
            } catch (error) {
                log('ERRO GERAL no registro: ' + error.message);
                log('Stack trace: ' + error.stack);
                showNotification('Erro ao registrar produção: ' + error.message, 'error');
            }
        }

        // ==================== NOTIFICATION FUNCTIONS ====================
        async function sendNotificationToManager(volume, productionDate, employeeName) {
            try {
                log('📤 Enviando notificação para o gerente...');
                
                const supabase = createSupabaseClient();
                
                // Buscar o gerente da fazenda
                const { data: managerData, error: managerError } = await supabase
                    .from('users')
                    .select('id, name, email')
                    .eq('farm_id', currentFarmId)
                    .eq('role', 'manager')
                    .single();
                
                if (managerError || !managerData) {
                    log('⚠️ Gerente não encontrado para a fazenda: ' + (managerError?.message || 'Nenhum gerente encontrado'));
                    return;
                }
                
                log('👨‍💼 Gerente encontrado: ' + managerData.name);
                
                // Criar notificação
                const notificationData = {
                    user_id: managerData.id,
                    farm_id: currentFarmId,
                    type: 'production_registered',
                    title: 'Nova Produção Registrada',
                    message: `${employeeName} registrou ${volume}L de produção para ${productionDate}`,
                    data: {
                        volume: volume,
                        production_date: productionDate,
                        employee_name: employeeName,
                        farm_id: currentFarmId
                    },
                    is_read: false,
                    created_at: new Date().toISOString()
                };
                
                // Inserir notificação no banco
                const { data: notification, error: notificationError } = await supabase
                    .from('notifications')
                    .insert([notificationData])
                    .select();
                
                if (notificationError) {
                    log('❌ Erro ao criar notificação: ' + notificationError.message);
                    return;
                }
                
                log('✅ Notificação enviada com sucesso para o gerente!');
                
                // Opcional: Enviar notificação push se o gerente estiver online
                await sendRealtimeNotification(managerData.id, notificationData);
                
            } catch (error) {
                log('❌ Erro ao enviar notificação: ' + error.message);
            }
        }
        
        async function sendRealtimeNotification(managerId, notificationData) {
            try {
                const supabase = createSupabaseClient();
                
                // Enviar notificação em tempo real via Supabase Realtime
                const { error } = await supabase
                    .channel('notifications')
                    .send({
                        type: 'broadcast',
                        event: 'new_notification',
                        payload: {
                            user_id: managerId,
                            notification: notificationData
                        }
                    });
                
                if (error) {
                    log('⚠️ Erro ao enviar notificação em tempo real: ' + error.message);
                } else {
                    log('📡 Notificação em tempo real enviada!');
                }
                
            } catch (error) {
                log('❌ Erro na notificação em tempo real: ' + error.message);
            }
        }

        // ==================== HISTORY FUNCTIONS ====================
        async function loadHistory() {
            log('Carregando histórico...');
            
            try {
                const supabase = createSupabaseClient();
                
                // Verificar se farm_id está disponível
                if (!currentFarmId) {
                    log('ERRO: currentFarmId não está disponível');
                    showNotification('Erro: ID da fazenda não encontrado', 'error');
                    return;
                }
                
                log('Buscando histórico para farm_id: ' + currentFarmId);
                
                const { data: historico, error } = await supabase
                    .from('volume_records')
                    .select('*')
                    .eq('farm_id', currentFarmId)
                    .order('created_at', { ascending: false })
                    .limit(100);
                
                if (error) {
                    log('ERRO na consulta: ' + error.message);
                    showNotification('Erro ao carregar histórico: ' + error.message, 'error');
                    return;
                }
                
                allProductionHistory = historico || [];
                log('Histórico carregado: ' + allProductionHistory.length + ' registros');
                
                displayHistory(allProductionHistory);
                
            } catch (error) {
                log('ERRO ao carregar histórico: ' + error.message);
                showNotification('Erro ao carregar histórico: ' + error.message, 'error');
            }
        }

        function displayHistory(historico) {
            const tbody = document.getElementById('historyTableBody');
            if (tbody) {
                if (!historico || historico.length === 0) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="5" class="text-center py-8 text-gray-500">
                                Nenhum registro encontrado
                            </td>
                        </tr>
                    `;
                } else {
                    tbody.innerHTML = historico.map(item => {
                        const data = new Date(item.production_date).toLocaleDateString('pt-BR');
                        const hora = new Date(item.created_at).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
                        
                        return `
                            <tr class="border-b border-slate-100">
                                <td class="py-3 px-4">${data}</td>
                                <td class="py-3 px-4">${hora}</td>
                                <td class="py-3 px-4">${item.volume_liters} L</td>
                                <td class="py-3 px-4">${item.temperature ? item.temperature + '°C' : '-'}</td>
                                <td class="py-3 px-4">${item.notes || '-'}</td>
                                <td class="py-3 px-4">
                                    <button onclick="deleteProduction('${item.id}')" class="text-red-500 hover:text-red-700 text-sm flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                        Excluir
                                    </button>
                                </td>
                            </tr>
                        `;
                    }).join('');
                }
            }
        }

        async function filterHistory() {
            try {
                log('Filtrando histórico...');
                
                const filterDateElement = document.getElementById('filterDate');
                if (!filterDateElement) {
                    log('ERRO: Elemento filterDate não encontrado');
                    showNotification('Erro ao filtrar', 'error');
                    return;
                }
                
                const filterDate = filterDateElement.value;
                log('Data do filtro: ' + filterDate);
                
                if (!filterDate) {
                    log('Nenhuma data selecionada, exibindo todos os registros');
                    displayHistory(allProductionHistory);
                    return;
                }
                
                // Verificar se allProductionHistory tem dados
                if (!allProductionHistory || allProductionHistory.length === 0) {
                    log('Aviso: allProductionHistory está vazio, recarregando dados...');
                    await loadHistory();
                    return;
                }
                
                const filtered = allProductionHistory.filter(item => item.production_date === filterDate);
                log('Registros filtrados: ' + filtered.length + ' de ' + allProductionHistory.length);
                
                displayHistory(filtered);
                
            } catch (error) {
                log('ERRO ao filtrar histórico: ' + error.message);
                showNotification('Erro ao filtrar: ' + error.message, 'error');
            }
        }



        async function deleteProduction(id) {
            const confirmed = await showConfirmDialog('Tem certeza que deseja excluir este registro?', 'delete');
            if (!confirmed) {
                return;
            }
    
            try {
                const supabase = createSupabaseClient();
                log(`Excluindo registro: ${id}`);
                
                const { error } = await supabase
                    .from('volume_records')
                    .delete()
                    .eq('id', id);
                
                if (error) {
                    throw error;
                }
                
                log('Registro excluído com sucesso!');
                showNotification('Registro excluído com sucesso!', 'success');
                
                // Reload data
                await loadHistory();
                await loadDashboardIndicators();
                await loadRecentActivity();
                await loadProductionChart();
        
                } catch (error) {
                log('ERRO ao excluir: ' + error.message);
                showNotification('Erro ao excluir registro: ' + error.message, 'error');
            }
        }

        // Função para mostrar diálogo de confirmação estilizado
        function showConfirmDialog(message, type = 'delete') {
            return new Promise((resolve) => {
                // Criar overlay
                const overlay = document.createElement('div');
                overlay.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4';
                overlay.id = 'confirmDialog';
                
                // Determinar ícone e cores baseado no tipo
                let iconBg, iconColor, title, confirmText, confirmBg;
                if (type === 'delete') {
                    iconBg = 'bg-red-100';
                    iconColor = 'text-red-600';
                    title = 'Confirmar Exclusão';
                    confirmText = 'Excluir';
                    confirmBg = 'bg-red-600 hover:bg-red-700';
                } else {
                    iconBg = 'bg-yellow-100';
                    iconColor = 'text-yellow-600';
                    title = 'Confirmar Limpeza';
                    confirmText = 'Limpar';
                    confirmBg = 'bg-yellow-600 hover:bg-yellow-700';
                }
                
                // Criar modal
                const modal = document.createElement('div');
                modal.className = 'bg-white rounded-lg shadow-xl max-w-md w-full mx-4 transform transition-all duration-300 scale-95 opacity-0';
                
                modal.innerHTML = `
                    <div class="p-6">
                        <!-- Ícone de alerta -->
                        <div class="flex items-center justify-center mb-4">
                            <div class="${iconBg} p-3 rounded-full">
                                <svg class="w-8 h-8 ${iconColor}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                </svg>
                            </div>
                        </div>
                        
                        <!-- Título -->
                        <h3 class="text-lg font-semibold text-gray-900 text-center mb-2">
                            ${title}
                        </h3>
                        
                        <!-- Mensagem -->
                        <p class="text-gray-600 text-center mb-6">
                            ${message}
                        </p>
                        
                        <!-- Botões -->
                        <div class="flex space-x-3">
                            <button id="confirmCancel" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 font-medium rounded-md hover:bg-gray-50 transition-colors">
                                Cancelar
                            </button>
                            <button id="confirmAction" class="flex-1 px-4 py-2 ${confirmBg} text-white font-medium rounded-md transition-colors">
                                ${confirmText}
                            </button>
                        </div>
                    </div>
                `;
                
                overlay.appendChild(modal);
                document.body.appendChild(overlay);
                
                // Animar entrada
                requestAnimationFrame(() => {
                    modal.style.transform = 'scale(1)';
                    modal.style.opacity = '1';
                });
                
                // Event listeners
                document.getElementById('confirmCancel').addEventListener('click', () => {
                    closeConfirmDialog();
                    resolve(false);
                });
                
                document.getElementById('confirmAction').addEventListener('click', () => {
                    closeConfirmDialog();
                    resolve(true);
                });
                
                // Fechar ao clicar no overlay
                overlay.addEventListener('click', (e) => {
                    if (e.target === overlay) {
                        closeConfirmDialog();
                        resolve(false);
                    }
                });
            });
        }
        
        function closeConfirmDialog() {
            const dialog = document.getElementById('confirmDialog');
            if (dialog) {
                const modal = dialog.querySelector('div');
                modal.style.transform = 'scale(0.95)';
                modal.style.opacity = '0';
                setTimeout(() => {
                    dialog.remove();
                }, 300);
            }
        }

        // Função para mostrar notificações
        function showNotification(message, type = 'info') {
            // Create notification element
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 p-4 rounded-xl shadow-2xl z-50 max-w-sm transform transition-all duration-300 border-l-4`;
            
            // Definir cores baseadas no tipo
            let bgColor, textColor, borderColor, iconSvg;
            switch(type) {
                case 'error':
                    bgColor = 'bg-red-50';
                    textColor = 'text-red-800';
                    borderColor = 'border-red-500';
                    iconSvg = `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>`;
                    break;
                case 'warning':
                    bgColor = 'bg-yellow-50';
                    textColor = 'text-yellow-800';
                    borderColor = 'border-yellow-500';
                    iconSvg = `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>`;
                    break;
                case 'success':
                    bgColor = 'bg-green-50';
                    textColor = 'text-green-800';
                    borderColor = 'border-green-500';
                    iconSvg = `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>`;
                    break;
                default:
                    bgColor = 'bg-blue-50';
                    textColor = 'text-blue-800';
                    borderColor = 'border-blue-500';
                    iconSvg = `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>`;
            }
            
            notification.className += ` ${bgColor} ${textColor} ${borderColor}`;
            
            // Criar conteúdo
            notification.innerHTML = `
                <div class="flex items-start space-x-3">
                    <div class="flex-shrink-0 mt-0.5">${iconSvg}</div>
                    <div class="flex-1">
                        <p class="font-medium text-sm">${message}</p>
                    </div>
                    <button onclick="this.parentElement.parentElement.remove()" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            `;
            
            // Adicionar animação de entrada
            notification.style.transform = 'translateX(100%)';
            notification.style.opacity = '0';
            
            // Add to page
            document.body.appendChild(notification);
            
            // Animar entrada
            requestAnimationFrame(() => {
                notification.style.transform = 'translateX(0)';
                notification.style.opacity = '1';
            });
            
            // Remove after 5 seconds with animation
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.style.transform = 'translateX(100%)';
                    notification.style.opacity = '0';
                    setTimeout(() => {
                        if (notification.parentNode) {
                            notification.parentNode.removeChild(notification);
                        }
                    }, 300);
                }
            }, 5000);
        }

        // ==================== FUNÇÕES DE NOTIFICAÇÕES ====================
        
        // Abrir sidebar de notificações
        function openNotificationsModal() {
            const modal = document.getElementById('notificationsModal');
            const content = document.getElementById('notificationsModalContent');
            
            if (modal && content) {
                modal.classList.remove('hidden');
                setTimeout(() => {
                    content.classList.remove('translate-x-full');
                    content.classList.add('translate-x-0');
                }, 10);
                
                // Carregar notificações
                loadNotifications();
            }
        }
        
        // Fechar sidebar de notificações
        function closeNotificationsModal() {
            const modal = document.getElementById('notificationsModal');
            const content = document.getElementById('notificationsModalContent');
            
            if (modal && content) {
                content.classList.remove('translate-x-0');
                content.classList.add('translate-x-full');
                setTimeout(() => {
                    modal.classList.add('hidden');
                }, 300);
            }
        }
        
        // Carregar notificações
        async function loadNotifications() {
            try {
                const supabase = createSupabaseClient();
                const { data: { user } } = await supabase.auth.getUser();
                
                if (!user) return;
                
                // Buscar solicitações de alteração de senha pendentes
                const { data: requests, error } = await supabase
                    .from('password_requests')
                    .select('*')
                    .eq('user_id', user.id)
                    .eq('status', 'pending')
                    .order('created_at', { ascending: false });
                
                if (error) {
                    console.error('Erro ao carregar notificações:', error);
                    return;
                }
                
                const notificationsList = document.getElementById('notificationsList');
                if (!notificationsList) return;
                
                if (requests && requests.length > 0) {
                    notificationsList.innerHTML = requests.map(request => `
                        <div class="bg-gradient-to-r from-yellow-50 to-orange-50 border border-yellow-200 rounded-xl p-3 hover:shadow-lg transition-all duration-200 hover:scale-[1.01]">
                            <div class="flex items-start space-x-3">
                                <div class="w-8 h-8 bg-gradient-to-br from-yellow-500 to-orange-500 rounded-lg flex items-center justify-center flex-shrink-0 shadow-lg">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between mb-1">
                                        <h4 class="text-sm font-semibold text-gray-900">Solicitação de Alteração de Senha</h4>
                                        <span class="text-xs text-yellow-600 font-medium bg-yellow-100 px-2 py-1 rounded-full">Pendente</span>
                                    </div>
                                    <p class="text-xs text-gray-600 mb-2">Sua solicitação está sendo analisada pelo administrador.</p>
                                    <div class="text-xs text-gray-500">
                                        ${new Date(request.created_at).toLocaleDateString('pt-BR')} às ${new Date(request.created_at).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' })}
                                    </div>
                                </div>
                            </div>
                        </div>
                    `).join('');
                } else {
                    notificationsList.innerHTML = `
                        <div class="text-center py-12">
                            <div class="w-20 h-20 bg-gradient-to-br from-gray-100 to-gray-200 rounded-full flex items-center justify-center mx-auto mb-6">
                                <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Tudo em dia!</h3>
                            <p class="text-gray-600">Não há notificações pendentes no momento</p>
                        </div>
                    `;
                }
                
                // Atualizar contador
                updateNotificationCounter(requests ? requests.length : 0);
                
            } catch (error) {
                console.error('Erro ao carregar notificações:', error);
            }
        }
        
        // Atualizar contador de notificações
        function updateNotificationCounter(count) {
            const counter = document.getElementById('notificationCounter');
            if (counter) {
                if (count > 0) {
                    counter.textContent = count;
                    counter.classList.remove('hidden');
                } else {
                    counter.classList.add('hidden');
                }
            }
        }

        // ==================== UTILITY FUNCTIONS ====================
        

        
        // Função para testar conexão com Supabase
        async function testSupabaseConnection() {
            try {
                log('Testando conexão com Supabase...');
                
                // Usar cliente Supabase global
                const supabase = createSupabaseClient();
                
                // Teste simples de conexão
                const { data, error } = await supabase
                    .from('volume_records')
                    .select('count')
                    .limit(1);
                
                if (error) {
                    log('ERRO na conexão Supabase: ' + error.message);
                    showNotification('Erro de conexão: ' + error.message, 'error');
                    return false;
                }
                
                log('Conexão Supabase OK');
                return true;
        
    } catch (error) {
                log('ERRO ao testar conexão: ' + error.message);
                showNotification('Erro ao conectar com o servidor', 'error');
                return false;
            }
        }
        
        function updateDateTime() {
            const agora = new Date();
            const dataHora = agora.toLocaleString('pt-BR', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            
            const dateTimeElement = document.getElementById('currentDateTime');
            if (dateTimeElement) {
                dateTimeElement.textContent = dataHora;
            }
        }

        // Update time every minute
        setInterval(updateDateTime, 60000);

        // ==================== INITIALIZATION ====================
        document.addEventListener('DOMContentLoaded', async function() {
            // Flag para evitar múltiplas inicializações
            if (window.pageInitialized) {
                log('⚠️ Página já inicializada, ignorando...');
                return;
            }
            
            window.pageInitialized = true;
            log('🚀 Inicializando página do funcionário...');
            
            // Verificar se não estamos em um loop de redirecionamento
            const redirectCount = sessionStorage.getItem('redirectCount') || 0;
            if (redirectCount > 3) {
                log('❌ Muitas tentativas de redirecionamento, limpando sessão');
                clearUserSession();
                sessionStorage.removeItem('redirectCount');
                safeRedirect('login.php');
            return;
        }
        
            try {
                // Inicializar componentes
                initFormHandlers();
                setupFormHandlers();
                initTabSystem();
                
                // As abas já estão criadas no HTML com CSS puro
                console.log('✅ Abas já criadas no HTML com CSS puro');
                
                // Garantir que a aba dashboard esteja visível
                const dashboardTab = document.getElementById('dashboard-tab');
                if (dashboardTab) {
                    dashboardTab.classList.remove('hidden');
                    log('✅ Aba dashboard ativada');
                }
                
                // Aguardar um pouco e carregar dados
                setTimeout(async function() {
                    log('Timeout concluído - carregando dados...');
                    await carregarDados();
                    
                    // Sistema inicializado com sucesso
                    setTimeout(() => {
                        console.log('✅ Sistema inicializado com sucesso');
                    }, 1000);
                }, 2000);
                
            } catch (error) {
                log('❌ ERRO na inicialização: ' + error.message);
                showNotification('Erro ao inicializar sistema', 'error');
            }
        });
        
        async function initializeSystem() {
            try {
                log('Inicializando sistema...');
            
            // Initialize form handlers
            initFormHandlers();
            
            // Set up form handlers
                setupFormHandlers();
            
            // Initialize tab system
            initTabSystem();
                
                // Aguardar um pouco e carregar dados
                setTimeout(async function() {
                    log('Timeout concluído - carregando dados...');
                    await carregarDados();
                }, 2000);
                
            } catch (error) {
                log('❌ ERRO na inicialização: ' + error.message);
                showNotification('Erro ao inicializar sistema', 'error');
            }
        }

        function setupFormHandlers() {
            // Formulário antigo (manter compatibilidade)
            const productionForm = document.getElementById('productionForm');
            if (productionForm) {
                productionForm.addEventListener('submit', registerProduction);
                log('Formulário de produção antigo configurado');
            }
            
            // NOVO formulário de produção
            const newProductionForm = document.getElementById('newProductionForm');
            if (newProductionForm) {
                newProductionForm.addEventListener('submit', handleNewProductionSubmit);
                log('✅ Novo formulário de produção configurado');
                
                // Definir data atual
                const dateInput = document.getElementById('newProductionDate');
                if (dateInput) {
                    dateInput.value = new Date().toISOString().split('T')[0];
                }
            } else {
                log('❌ ERRO: Novo formulário de produção não encontrado');
            }
            
            // Configurar filtro do histórico
            const historyFilter = document.getElementById('historyFilter');
            if (historyFilter) {
                historyFilter.addEventListener('change', function() {
                    loadHistoryData();
                });
                log('✅ Filtro do histórico configurado');
            }
            
            const updateProfileForm = document.getElementById('updateProfileForm');
            if (updateProfileForm) {
                updateProfileForm.addEventListener('submit', handleUpdateProfile);
            }
            
            const changePasswordForm = document.getElementById('changePasswordForm');
            if (changePasswordForm) {
                changePasswordForm.addEventListener('submit', handleChangePassword);
            }
        }

        // ==================== FUNÇÕES ANTIGAS REMOVIDAS ====================
        // As abas agora são criadas diretamente no HTML com CSS puro

        // Função removida - abas agora são criadas no HTML

        // ==================== FUNÇÕES DAS ABAS CSS PURAS ====================
        
        // Funções de loading
        function showLoading(message = 'Carregando...') {
            let loadingElement = document.getElementById('loadingOverlay');
            if (!loadingElement) {
                loadingElement = document.createElement('div');
                loadingElement.id = 'loadingOverlay';
                loadingElement.style.cssText = `
                    position: fixed;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: rgba(0, 0, 0, 0.5);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 9999;
                `;
                loadingElement.innerHTML = `
                    <div style="background: white; border-radius: 20px; padding: 30px; max-width: 300px; text-align: center; box-shadow: 0 20px 40px rgba(0,0,0,0.3);">
                        <div style="width: 48px; height: 48px; border: 4px solid #e5e7eb; border-top: 4px solid #10b981; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 20px;"></div>
                        <p style="color: #374151; font-weight: 500; margin: 0;">${message}</p>
                    </div>
                `;
                document.body.appendChild(loadingElement);
            }
        }

        function hideLoading() {
            const loadingElement = document.getElementById('loadingOverlay');
            if (loadingElement) {
                loadingElement.remove();
            }
        }
        
        // Limpar formulário JS
        // ==================== FUNÇÕES DO FORMULÁRIO DE REGISTRO ====================
        
        // Limpar formulário novo
        function clearNewForm() {
            const form = document.getElementById('newProductionForm');
            if (form) {
                form.reset();
                // Redefinir data atual
                const dateInput = document.getElementById('newProductionDate');
                if (dateInput) {
                    dateInput.value = new Date().toISOString().split('T')[0];
                }
                showNotification('Formulário limpo com sucesso!', 'success');
            }
        }
        
        // Submeter formulário novo de produção
        async function handleNewProductionSubmit(event) {
            event.preventDefault();
            
            try {
                showLoading('Registrando produção...');
                
                const formData = new FormData(event.target);
                const volume = parseFloat(formData.get('volume'));
                const productionDate = formData.get('productionDate');
                const temperature = formData.get('temperature') ? parseFloat(formData.get('temperature')) : null;
                const notes = formData.get('notes') || null;

                // Validações
                if (!volume || volume <= 0) {
                    showNotification('Por favor, insira um volume válido', 'error');
                    return;
                }

                if (!productionDate) {
                    showNotification('Por favor, selecione uma data', 'error');
                    return;
                }

                if (!currentUser || !currentUser.id) {
                    showNotification('Erro: Usuário não autenticado', 'error');
                    return;
                }

                if (!currentFarmId) {
                    showNotification('Erro: ID da fazenda não encontrado', 'error');
                    return;
                }

                const supabase = await getSupabaseClient();
                const productionData = {
                    farm_id: currentFarmId,
                    user_id: currentUser.id,
                    volume_liters: volume,
                    production_date: productionDate,
                    temperature: temperature,
                    notes: notes,
                    created_at: new Date().toISOString()
                };

                const { data, error } = await supabase
                    .from('volume_records')
                    .insert([productionData])
                    .select();

                if (error) {
                    console.error('Erro ao inserir produção:', error);
                    showNotification('Erro ao registrar produção: ' + error.message, 'error');
                    return;
                }

                showNotification('Produção registrada com sucesso!', 'success');
                clearNewForm();
                
                // Recarregar dados do histórico
                    loadHistoryData();

            } catch (error) {
                console.error('Erro ao processar formulário:', error);
                showNotification('Erro interno: ' + error.message, 'error');
            } finally {
                hideLoading();
            }
        }
        
        // ==================== FUNÇÕES DO HISTÓRICO ====================
        
        // Carregar dados do histórico
        async function loadHistoryData() {
            try {
                showLoading('Carregando histórico...');
                
                const supabase = await getSupabaseClient();
                if (!supabase) {
                    throw new Error('Supabase não disponível');
                }
                
                const { data, error } = await supabase
                    .from('volume_records')
                    .select('*')
                    .eq('farm_id', currentFarmId)
                    .order('created_at', { ascending: false });

                if (error) {
                    console.error('Erro ao carregar histórico:', error);
                    showNotification('Erro ao carregar histórico: ' + error.message, 'error');
                    return;
                }

                displayHistoryData(data || []);

            } catch (error) {
                console.error('Erro ao carregar histórico:', error);
                showNotification('Erro ao carregar histórico: ' + error.message, 'error');
            } finally {
                hideLoading();
            }
        }
        
        // Exibir dados do histórico
        function displayHistoryData(records) {
            const historyList = document.getElementById('historyList');
            if (!historyList) return;

            if (!records || records.length === 0) {
                historyList.innerHTML = `
                    <div class="history-loading">
                        <div style="width: 48px; height: 48px; background: #e5e7eb; border-radius: 50%; margin: 0 auto 20px; display: flex; align-items: center; justify-content: center;">
                            <svg style="width: 24px; height: 24px; color: #9ca3af;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <p style="color: #6b7280; font-size: 16px; font-weight: 500; margin: 0;">Nenhum registro encontrado</p>
                    </div>
                `;
                return;
            }

            const recordsHtml = records.map(record => {
                const date = new Date(record.production_date + 'T00:00:00').toLocaleDateString('pt-BR');
                const time = new Date(record.created_at).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
                const volume = record.volume_liters || 0;
                const temperature = record.temperature ? `${record.temperature}°C` : 'N/A';
                const notes = record.notes || 'Sem observações';

                return `
                    <div style="background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; margin-bottom: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px;">
                            <div>
                                <h3 style="font-size: 18px; font-weight: 600; color: #374151; margin: 0 0 5px 0;">${date}</h3>
                                <p style="font-size: 14px; color: #6b7280; margin: 0;">${time}</p>
                            </div>
                            <div style="text-align: right;">
                                <p style="font-size: 24px; font-weight: bold; color: #10b981; margin: 0;">${volume}L</p>
                                <p style="font-size: 12px; color: #6b7280; margin: 0;">Volume</p>
                            </div>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                            <div>
                                <p style="font-size: 12px; color: #6b7280; margin: 0 0 5px 0; text-transform: uppercase; font-weight: 500;">Temperatura</p>
                                <p style="font-size: 14px; color: #374151; margin: 0;">${temperature}</p>
                            </div>
                            <div>
                                <p style="font-size: 12px; color: #6b7280; margin: 0 0 5px 0; text-transform: uppercase; font-weight: 500;">Observações</p>
                                <p style="font-size: 14px; color: #374151; margin: 0;">${notes}</p>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');

            historyList.innerHTML = recordsHtml;
        }
        
        // Exportar histórico
        function exportHistory() {
            showNotification('Funcionalidade de exportação em desenvolvimento', 'info');
        }

        // Submeter formulário JS de produção
        async function handleJsProductionSubmit(event) {
            event.preventDefault();
            
            try {
                const formData = new FormData(event.target);
                const volume = parseFloat(formData.get('volume'));
                const productionDate = formData.get('productionDate');
                const temperature = formData.get('temperature') ? parseFloat(formData.get('temperature')) : null;
                const notes = formData.get('notes') || null;

                // Validações
                if (!volume || volume <= 0) {
                    showNotification('Por favor, insira um volume válido', 'error');
                    return;
                }

                if (!productionDate) {
                    showNotification('Por favor, selecione uma data', 'error');
                    return;
                }

                if (!currentUser || !currentUser.id) {
                    showNotification('Erro: Usuário não autenticado', 'error');
                    return;
                }

                if (!currentFarmId) {
                    showNotification('Erro: ID da fazenda não encontrado', 'error');
                    return;
                }

                // Mostrar loading
                const submitBtn = event.target.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<svg class="w-4 h-4 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>Salvando...';
                submitBtn.disabled = true;

                const supabase = createSupabaseClient();
                const productionData = {
                    farm_id: currentFarmId,
                    volume_liters: volume,
                    production_date: productionDate,
                    temperature: temperature,
                    notes: notes
                };

                const { data, error } = await supabase
                    .from('volume_records')
                    .insert([productionData])
                    .select();

                if (error) throw error;

                // Sucesso
                showNotification(`Produção registrada com sucesso: ${volume}L`, 'success');
                clearNewForm();
                
                // Atualizar dashboard se estiver visível
                await loadDashboardIndicators();
                await loadRecentActivity();
                await loadProductionChart();
                
                // Carregar histórico
                    await loadJsHistory();

                // Restaurar botão
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;

            } catch (error) {
                console.error('❌ Erro ao registrar produção:', error);
                showNotification('Erro ao registrar produção: ' + error.message, 'error');
                
                // Restaurar botão
                const submitBtn = event.target.querySelector('button[type="submit"]');
                submitBtn.innerHTML = '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>Registrar Produção';
                submitBtn.disabled = false;
            }
        }

        // Carregar histórico JS
        async function loadJsHistory() {
            try {
                if (!currentFarmId) {
                    console.log('❌ Farm ID não disponível para carregar histórico');
                    return;
                }

                const supabase = createSupabaseClient();
                const { data: history, error } = await supabase
                    .from('volume_records')
                    .select('*')
                    .eq('farm_id', currentFarmId)
                    .order('created_at', { ascending: false })
                    .limit(100);

                if (error) throw error;

                displayJsHistory(history || []);
                updateJsHistoryStats(history || []);

            } catch (error) {
                console.error('❌ Erro ao carregar histórico:', error);
                showNotification('Erro ao carregar histórico', 'error');
            }
        }

        // Exibir histórico JS
        function displayJsHistory(history) {
            const tbody = document.getElementById('jsHistoryTableBody');
            if (!tbody) return;

            if (!history || history.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center py-12 text-gray-500">
                            <div class="flex flex-col items-center">
                                <svg class="w-12 h-12 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                                <p class="text-lg font-medium">Nenhum registro encontrado</p>
                                <p class="text-sm text-gray-400">Registre a primeira produção para começar</p>
                            </div>
                        </td>
                    </tr>
                `;
                return;
            }

            tbody.innerHTML = history.map(record => {
                const date = new Date(record.production_date);
                const createdAt = new Date(record.created_at);
                
                return `
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="py-4 px-6 text-sm font-medium text-gray-900">
                            ${date.toLocaleDateString('pt-BR')}
                        </td>
                        <td class="py-4 px-6 text-sm text-gray-600">
                            ${createdAt.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' })}
                        </td>
                        <td class="py-4 px-6 text-sm font-semibold text-blue-600">
                            ${record.volume_liters}L
                        </td>
                        <td class="py-4 px-6 text-sm text-gray-600">
                            ${record.temperature ? record.temperature + '°C' : '-'}
                        </td>
                        <td class="py-4 px-6 text-sm text-gray-600 max-w-xs truncate">
                            ${record.notes || '-'}
                        </td>
                        <td class="py-4 px-6 text-sm">
                            <div class="flex items-center gap-2">
                                <button onclick="viewJsRecord('${record.id}')" 
                                        class="text-blue-600 hover:text-blue-800 font-medium text-sm">
                                    Ver
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        // Atualizar estatísticas do histórico JS
        function updateJsHistoryStats(history) {
            const totalRecords = history.length;
            const totalVolume = history.reduce((sum, record) => sum + (record.volume_liters || 0), 0);
            const averageVolume = totalRecords > 0 ? (totalVolume / totalRecords) : 0;

            // Atualizar elementos da UI
            const totalRecordsEl = document.getElementById('jsTotalRecords');
            const totalVolumeEl = document.getElementById('jsTotalVolume');
            const averageVolumeEl = document.getElementById('jsAverageVolume');

            if (totalRecordsEl) totalRecordsEl.textContent = totalRecords;
            if (totalVolumeEl) totalVolumeEl.textContent = totalVolume.toFixed(1) + 'L';
            if (averageVolumeEl) averageVolumeEl.textContent = averageVolume.toFixed(1) + 'L';
        }

        // Filtrar histórico JS por data
        function filterJsHistory() {
            const dateInput = document.getElementById('jsFilterDate');
            if (!dateInput || !dateInput.value) {
                showNotification('Por favor, selecione uma data para filtrar', 'warning');
                return;
            }

            // Implementar filtro (por simplicidade, recarregar com filtro)
            loadJsHistory();
        }

        // Limpar filtro JS
        function clearJsFilter() {
            const dateInput = document.getElementById('jsFilterDate');
            if (dateInput) {
                dateInput.value = '';
            }
            loadJsHistory();
        }

        // Ver detalhes de um registro JS
        function viewJsRecord(recordId) {
            console.log('Ver registro:', recordId);
            // Implementar modal ou detalhes
            showNotification('Funcionalidade em desenvolvimento', 'info');
        }

        // Funções de teste para as abas




        // ==================== FUNÇÕES MODERNAS PARA REGISTRO ====================
        
        // Limpar formulário moderno
        function clearModernForm() {
            document.getElementById('modernVolume').value = '';
            document.getElementById('modernProductionDate').value = '';
            document.getElementById('modernTemperature').value = '';
            document.getElementById('modernNotes').value = '';
            
            // Definir data atual como padrão
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('modernProductionDate').value = today;
        }
        
        // Enviar produção moderna
        async function handleModernProductionSubmit(event) {
            event.preventDefault();
            
            const formData = new FormData(event.target);
            const volume = parseFloat(formData.get('volume'));
            const productionDate = formData.get('productionDate');
            const temperature = formData.get('temperature') ? parseFloat(formData.get('temperature')) : null;
            const notes = formData.get('notes');
            
            if (!volume || !productionDate) {
                showNotification('Por favor, preencha todos os campos obrigatórios.', 'error');
                return;
            }
            
            try {
                showLoading('Registrando produção...');
                
                const { data, error } = await supabase
                    .from('volume_records')
                    .insert([{
                        volume: volume,
                        production_date: productionDate,
                        temperature: temperature,
                        notes: notes,
                        farm_id: currentFarmId,
                        created_at: new Date().toISOString()
                    }]);
                
                if (error) throw error;
                
                showNotification('Produção registrada com sucesso!', 'success');
                clearModernForm();
                
                // Atualizar estatísticas do histórico
                    await loadHistoryData();
                
            } catch (error) {
                console.error('Erro ao registrar produção:', error);
                showNotification('Erro ao registrar produção. Tente novamente.', 'error');
            } finally {
                hideLoading();
            }
        }
        
        // ==================== FUNÇÕES MODERNAS PARA HISTÓRICO ====================
        
        // Carregar histórico moderno
        async function loadModernHistory() {
            try {
                // Verificar se showLoading existe, senão criar uma versão local
                if (typeof showLoading === 'function') {
                showLoading('Carregando histórico...');
                } else {
                    console.log('Carregando histórico...');
                }
                
                const supabase = await getSupabaseClient();
                if (!supabase) {
                    throw new Error('Supabase não disponível');
                }
                
                const { data, error } = await supabase
                    .from('volume_records')
                    .select('*')
                    .eq('farm_id', currentFarmId)
                    .order('production_date', { ascending: false });
                
                if (error) throw error;
                
                displayModernHistory(data || []);
                updateModernHistoryStats(data || []);
                
            } catch (error) {
                console.error('Erro ao carregar histórico:', error);
                if (typeof showNotification === 'function') {
                showNotification('Erro ao carregar histórico.', 'error');
                }
            } finally {
                // Verificar se hideLoading existe, senão criar uma versão local
                if (typeof hideLoading === 'function') {
                hideLoading();
                } else {
                    const loadingElement = document.getElementById('loadingOverlay');
                    if (loadingElement) {
                        loadingElement.remove();
                    }
                }
            }
        }
        
        // Exibir histórico moderno
        function displayModernHistory(records) {
            const tbody = document.getElementById('modernHistoryTableBody');
            if (!tbody) {
                console.warn('Elemento modernHistoryTableBody não encontrado');
                return;
            }
            
            if (!records || records.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center py-16 text-gray-500">
                            <div class="flex flex-col items-center">
                                <svg class="w-16 h-16 text-gray-300 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                                <p class="text-xl font-semibold">Nenhum registro encontrado</p>
                                <p class="text-sm text-gray-400 mt-2">Comece registrando uma produção</p>
                            </div>
                        </td>
                    </tr>
                `;
                return;
            }
            
            tbody.innerHTML = records.map(record => {
                const date = new Date(record.production_date);
                const formattedDate = date.toLocaleDateString('pt-BR');
                const formattedTime = new Date(record.created_at).toLocaleTimeString('pt-BR', { 
                    hour: '2-digit', 
                    minute: '2-digit' 
                });
                
                return `
                    <tr class="hover:bg-gray-50 transition-colors duration-200">
                        <td class="py-6 px-8">
                            <div class="flex items-center space-x-3">
                                <div class="bg-blue-100 p-2 rounded-lg">
                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                                <span class="font-semibold text-gray-900">${formattedDate}</span>
                            </div>
                        </td>
                        <td class="py-6 px-8 text-gray-600">${formattedTime}</td>
                        <td class="py-6 px-8">
                            <div class="flex items-center space-x-2">
                                <span class="font-bold text-lg text-gray-900">${record.volume}</span>
                                <span class="text-sm text-gray-500">L</span>
                            </div>
                        </td>
                        <td class="py-6 px-8">
                            ${record.temperature ? `
                                <div class="flex items-center space-x-2">
                                    <span class="font-semibold text-gray-900">${record.temperature}</span>
                                    <span class="text-sm text-gray-500">°C</span>
                                </div>
                            ` : '<span class="text-gray-400">-</span>'}
                        </td>
                        <td class="py-6 px-8">
                            <div class="max-w-xs">
                                <p class="text-gray-700 truncate">${record.notes || '-'}</p>
                            </div>
                        </td>
                        <td class="py-6 px-8">
                            <button onclick="viewModernRecord('${record.id}')" 
                                    class="bg-blue-100 text-blue-600 px-4 py-2 rounded-xl hover:bg-blue-200 transition-colors duration-200 font-semibold">
                                Ver Detalhes
                            </button>
                        </td>
                    </tr>
                `;
            }).join('');
        }
        
        // Atualizar estatísticas modernas
        function updateModernHistoryStats(records) {
            if (!records || !Array.isArray(records)) {
                console.warn('Records inválidos para updateModernHistoryStats');
                return;
            }
            
            const totalRecords = records.length;
            const totalVolume = records.reduce((sum, record) => sum + (record.volume || 0), 0);
            const averageVolume = totalRecords > 0 ? (totalVolume / totalRecords).toFixed(1) : 0;
            
            // Verificar se os elementos existem antes de atualizar
            const modernTotalRecords = document.getElementById('modernTotalRecords');
            const modernTotalVolume = document.getElementById('modernTotalVolume');
            const modernAverageVolume = document.getElementById('modernAverageVolume');
            
            if (modernTotalRecords) modernTotalRecords.textContent = totalRecords;
            if (modernTotalVolume) modernTotalVolume.textContent = `${totalVolume.toFixed(1)}L`;
            if (modernAverageVolume) modernAverageVolume.textContent = `${averageVolume}L`;
        }
        
        // Filtrar histórico moderno
        function filterModernHistory() {
            const filterDateElement = document.getElementById('modernFilterDate');
            if (!filterDateElement) {
                console.warn('Elemento modernFilterDate não encontrado');
                return;
            }
            
            const filterDate = filterDateElement.value;
            if (!filterDate) {
                if (typeof showNotification === 'function') {
                showNotification('Selecione uma data para filtrar.', 'warning');
                }
                return;
            }
            
            // Implementar filtro por data
            if (typeof loadHistoryData === 'function') {
            loadHistoryData().then(() => {
                // Filtrar apenas registros da data selecionada
                const allRows = document.querySelectorAll('#modernHistoryTableBody tr');
                allRows.forEach(row => {
                    const dateCell = row.querySelector('td:first-child span');
                    if (dateCell) {
                            try {
                        const rowDate = new Date(dateCell.textContent.split('/').reverse().join('-'));
                        const filterDateObj = new Date(filterDate);
                        
                        if (rowDate.toDateString() !== filterDateObj.toDateString()) {
                            row.style.display = 'none';
                        } else {
                            row.style.display = '';
                                }
                            } catch (error) {
                                console.warn('Erro ao processar data da linha:', error);
                        }
                    }
                });
                }).catch(error => {
                    console.error('Erro ao carregar dados para filtro:', error);
            });
            }
        }
        
        // Limpar filtro moderno
        function clearModernFilter() {
            const filterDateElement = document.getElementById('modernFilterDate');
            if (filterDateElement) {
                filterDateElement.value = '';
            }
            
            if (typeof loadHistoryData === 'function') {
            loadHistoryData();
            }
        }
        
        // Ver detalhes do registro moderno
        function viewModernRecord(recordId) {
            // Implementar modal de detalhes
            if (typeof showNotification === 'function') {
            showNotification('Funcionalidade de detalhes será implementada em breve.', 'info');
            } else {
                console.log('Funcionalidade de detalhes será implementada em breve.');
            }
        }
        

        log('=== SCRIPT FUNCIONÁRIO COMPLETO CARREGADO ===');
        
                  // Função para retornar à conta do gerente
          async function returnToManagerAccount() {
            const confirmed = await window.showConfirm('Deseja retornar à sua conta de gerente?\n\nVocê será redirecionado para o painel do gerente.', {
            title: 'Retornar ao Gerente',
            type: 'question',
            confirmText: 'Sim, Retornar',
            cancelText: 'Cancelar'
        });
            
            if (confirmed) {
                // Limpar dados da conta secundária
                sessionStorage.removeItem('currentSecondaryAccount');
                
                // Redirecionar para o painel do gerente
                window.location.replace('gerente.php');
            }
        }
        
        // Verificar se é conta secundária e mostrar botão de retorno
        function checkSecondaryAccount() {
            const secondaryAccount = sessionStorage.getItem('currentSecondaryAccount');
            const returnBtn = document.getElementById('returnToManagerBtn');
            
            if (secondaryAccount && returnBtn) {
                try {
                    const accountData = JSON.parse(secondaryAccount);
                    if (accountData.isSecondary) {
                        returnBtn.classList.remove('hidden');
                        console.log('✅ Conta secundária detectada - botão de retorno ativado');
                    }
                } catch (error) {
                    console.error('Erro ao verificar conta secundária:', error);
                }
            }
        }
        
        // Executar verificação quando a página carregar
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(checkSecondaryAccount, 1000);
        });
        
                    // App Version Display
        document.addEventListener('DOMContentLoaded', function() {
            // Adiciona versão do app no perfil do usuário
            const appVersion = '1.0.0';
            
            // Função para adicionar versão em elementos de perfil
            function addVersionToProfile() {
                const profileElements = document.querySelectorAll('.user-profile, .profile-info, .user-info');
                profileElements.forEach(element => {
                    if (!element.querySelector('.app-version')) {
                        const versionDiv = document.createElement('div');
                        versionDiv.className = 'app-version text-xs text-gray-500 mt-2';
                        versionDiv.innerHTML = `App v${appVersion}`;
                        element.appendChild(versionDiv);
                    }
                });
                
                // Adicionar no footer se existir
                const footer = document.querySelector('footer, .footer');
                if (footer && !footer.querySelector('.app-version')) {
                    const versionDiv = document.createElement('div');
                    versionDiv.className = 'app-version text-xs text-gray-500 text-center mt-4';
                    versionDiv.innerHTML = `LacTech v${appVersion}`;
                    footer.appendChild(versionDiv);
                }
            }
            
            // Função para adicionar versão no modal de perfil
            function addVersionToProfileModal() {
                const profileModal = document.getElementById('profileModal');
                if (profileModal && !profileModal.querySelector('.app-version')) {
                    const versionDiv = document.createElement('div');
                    versionDiv.className = 'app-version text-xs text-gray-500 text-center mt-4 p-4 border-t border-gray-200';
                    versionDiv.innerHTML = `LacTech v${appVersion}`;
                    profileModal.querySelector('.modal-content').appendChild(versionDiv);
                }
            }
            
            // Executar após carregamento
            setTimeout(addVersionToProfile, 1000);
            
            // Adicionar versão quando o modal de perfil for aberto
            const originalOpenProfileModal = window.openProfileModal;
            window.openProfileModal = function() {
                if (originalOpenProfileModal) {
                    originalOpenProfileModal();
                }
                setTimeout(addVersionToProfileModal, 100);
            };
        });
    </script>

    <!-- Modal Full Screen de Minhas Solicitações de Senha -->
    <div id="myPasswordRequestsModal" class="fixed inset-0 bg-white z-[99999] hidden flex flex-col" style="display: none !important;">
        <!-- Header -->
        <div class="flex-shrink-0 p-4 sm:p-6 bg-white border-b border-gray-200 shadow-lg">
                <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3 sm:space-x-6">
                    <div class="w-12 h-12 sm:w-16 sm:h-16 bg-forest-100 rounded-2xl sm:rounded-3xl flex items-center justify-center shadow-sm">
                        <svg class="w-6 h-6 sm:w-8 sm:h-8 text-forest-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div>
                        <h2 class="text-lg sm:text-xl font-bold mb-1 text-black">Minhas Solicitações</h2>
                        <p class="text-gray-600 text-xs sm:text-sm">Histórico de suas solicitações de alteração de senha</p>
                        </div>
                    </div>
                <button onclick="closeMyPasswordRequestsModal()" class="w-10 h-10 sm:w-12 sm:h-12 lg:w-14 lg:h-14 flex items-center justify-center hover:bg-gray-100 rounded-xl sm:rounded-2xl transition-all duration-200">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 lg:w-8 lg:h-8 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            
            <!-- Conteúdo -->
        <div class="flex-1 overflow-y-auto p-4 sm:p-6">
            <!-- Filtros e Ações -->
            <div class="mb-6">
                <div class="flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between">
                    <div class="flex flex-col sm:flex-row gap-4">
                        <select id="myRequestsStatusFilter" class="px-4 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-forest-500 focus:border-transparent">
                            <option value="">Todos os status</option>
                            <option value="approved">Aprovadas</option>
                            <option value="rejected">Rejeitadas</option>
                            <option value="pending">Pendentes</option>
                        </select>
                        <select id="myRequestsDateFilter" class="px-4 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-forest-500 focus:border-transparent">
                            <option value="7">Últimos 7 dias</option>
                            <option value="15">Últimos 15 dias</option>
                            <option value="30" selected>Últimos 30 dias</option>
                            <option value="90">Últimos 90 dias</option>
                        </select>
                        </div>
                    <div class="flex items-center space-x-3">
                        <button onclick="loadMyPasswordRequests()" id="refreshMyRequestsBtn" class="px-4 py-2 bg-forest-500 hover:bg-forest-600 text-white rounded-lg transition-colors duration-200 flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            <span>Atualizar</span>
                        </button>
                        <button onclick="window.open('solicitar-alteracao-senha.php', '_blank')" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors duration-200 flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span>Nova Solicitação</span>
                    </button>
                    </div>
                </div>
                </div>
                
                <!-- Lista de Solicitações -->
            <div id="myPasswordRequestsList" class="space-y-4">
                <!-- Loading -->
                <div id="myRequestsLoading" class="flex items-center justify-center py-12">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-forest-500"></div>
                    <span class="ml-3 text-gray-600">Carregando solicitações...</span>
                </div>
                
                <!-- Empty State -->
                <div id="emptyMyPasswordRequests" class="hidden text-center py-12">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Nenhuma solicitação encontrada</h3>
                    <p class="text-gray-500 mb-6">Você ainda não fez nenhuma solicitação de alteração de senha</p>
                    <button onclick="window.open('solicitar-alteracao-senha.php', '_blank')" class="px-6 py-3 bg-forest-600 hover:bg-forest-700 text-white font-medium rounded-lg transition-colors flex items-center space-x-2 mx-auto">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span>Nova Solicitação</span>
                    </button>
                </div>
                </div>
            </div>
    </div>

    <!-- Script para funcionalidades de solicitações de senha -->
    <script>
        // Funções para gerenciar solicitações de senha do usuário
        async function openMyPasswordRequests() {
            const modal = document.getElementById('myPasswordRequestsModal');
            if (modal) {
                modal.classList.remove('hidden');
                modal.style.display = 'flex';
                modal.style.visibility = 'visible';
                modal.style.opacity = '1';
                modal.style.pointerEvents = 'auto';
                modal.style.zIndex = '99999';
                
                // Carregar solicitações
                await loadMyPasswordRequests();
            }
        }
        
        function closeMyPasswordRequestsModal() {
            const modal = document.getElementById('myPasswordRequestsModal');
            if (modal) {
                modal.classList.add('hidden');
                modal.style.display = 'none';
                modal.style.visibility = 'hidden';
                modal.style.opacity = '0';
                modal.style.pointerEvents = 'none';
                modal.style.zIndex = '-1';
            }
        }
        
        // Carregar solicitações do usuário atual
        async function loadMyPasswordRequests() {
            try {
                const btn = document.getElementById('refreshMyRequestsBtn');
                const loadingElement = document.getElementById('myRequestsLoading');
                const emptyElement = document.getElementById('emptyMyPasswordRequests');
                
                if (btn) {
                    btn.disabled = true;
                    btn.innerHTML = `
                        <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
                        <span>Atualizando...</span>
                    `;
                }
                
                if (loadingElement) loadingElement.classList.remove('hidden');
                if (emptyElement) emptyElement.classList.add('hidden');
                
                const supabase = createSupabaseClient();
                const { data: { user } } = await supabase.auth.getUser();
                
                if (!user) {
                    showNotification('Usuário não autenticado', 'error');
                    return;
                }
                
                // Obter filtros
                const statusFilter = document.getElementById('myRequestsStatusFilter')?.value || '';
                const daysFilter = parseInt(document.getElementById('myRequestsDateFilter')?.value || '30');
                
                // Calcular data limite
                const dateLimit = new Date();
                dateLimit.setDate(dateLimit.getDate() - daysFilter);
                
                console.log('📋 Carregando minhas solicitações...');
                
                // Construir query com filtros
                let query = supabase
                    .from('password_requests')
                    .select('*')
                    .eq('user_id', user.id)
                    .gte('created_at', dateLimit.toISOString())
                    .order('created_at', { ascending: false });
                
                if (statusFilter) {
                    query = query.eq('status', statusFilter);
                }
                
                const { data: requests, error } = await query;
                
                if (error) {
                    console.error('Erro ao buscar solicitações:', error);
                    showNotification('Erro ao carregar solicitações', 'error');
                    return;
                }
                
                // Ocultar loading
                if (loadingElement) loadingElement.classList.add('hidden');
                
                console.log('📋 Solicitações carregadas:', requests?.length || 0);
                displayMyPasswordRequests(requests || []);
                
            } catch (error) {
                console.error('❌ Erro ao carregar solicitações:', error);
                showNotification('Erro ao carregar solicitações', 'error');
                const loadingElement = document.getElementById('myRequestsLoading');
                if (loadingElement) loadingElement.classList.add('hidden');
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = `
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        <span>Atualizar</span>
                    `;
                }
            }
        }
        
        // Exibir solicitações do usuário
        function displayMyPasswordRequests(requests) {
            const listContainer = document.getElementById('myPasswordRequestsList');
            const emptyState = document.getElementById('emptyMyPasswordRequests');
            const loadingElement = document.getElementById('myRequestsLoading');
            
            if (!listContainer || !emptyState) return;
            
            // Ocultar loading
            if (loadingElement) loadingElement.classList.add('hidden');
            
            if (requests.length === 0) {
                listContainer.innerHTML = '';
                emptyState.classList.remove('hidden');
                return;
            }
            
            emptyState.classList.add('hidden');
            
            listContainer.innerHTML = requests.map(request => createMyRequestCard(request)).join('');
        }
        
        // Função para criar card de solicitação do usuário
        function createMyRequestCard(request) {
            const statusColors = {
                'pending': { bg: 'bg-yellow-50', border: 'border-yellow-200', text: 'text-yellow-800', icon: '⏳' },
                'approved': { bg: 'bg-green-50', border: 'border-green-200', text: 'text-green-800', icon: '✅' },
                'rejected': { bg: 'bg-red-50', border: 'border-red-200', text: 'text-red-800', icon: '❌' }
            };
            
            const status = statusColors[request.status] || statusColors['pending'];
            const createdDate = new Date(request.created_at).toLocaleDateString('pt-BR', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
                
                return `
                <div class="bg-white rounded-xl border ${status.border} shadow-sm hover:shadow-md transition-all duration-200">
                    <div class="p-4 sm:p-6">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center space-x-3 mb-3">
                                    <div class="w-8 h-8 ${status.bg} rounded-lg flex items-center justify-center">
                                        <span class="text-sm">${status.icon}</span>
                                </div>
                                <div>
                                        <h3 class="font-semibold text-gray-900">Solicitação de Alteração de Senha</h3>
                                        <p class="text-sm text-gray-500">${createdDate}</p>
                                </div>
                            </div>
                            
                                <div class="space-y-2">
                                    <div class="flex items-center space-x-2">
                                        <span class="text-sm font-medium text-gray-700">Status:</span>
                                        <span class="px-2 py-1 text-xs font-medium rounded-full ${status.bg} ${status.text}">
                                            ${request.status === 'pending' ? 'Pendente' : 
                                              request.status === 'approved' ? 'Aprovada' : 'Rejeitada'}
                                        </span>
                        </div>
                        
                                    ${request.reason ? `
                                        <div class="flex items-start space-x-2">
                                            <span class="text-sm font-medium text-gray-700">Motivo:</span>
                                            <span class="text-sm text-gray-600">${request.reason}</span>
                                </div>
                            ` : ''}
                            
                                    ${request.admin_notes ? `
                                        <div class="flex items-start space-x-2">
                                            <span class="text-sm font-medium text-gray-700">Observações:</span>
                                            <span class="text-sm text-gray-600">${request.admin_notes}</span>
                                </div>
                            ` : ''}
                                </div>
                            </div>
                            
                            <div class="flex flex-col items-end space-y-2">
                                ${request.status === 'pending' ? `
                                    <button onclick="cancelMyRequest('${request.id}')" 
                                            class="px-3 py-1 text-xs font-medium text-red-600 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors">
                                        Cancelar
                                    </button>
                            ` : ''}
                            </div>
                        </div>
                        </div>
                    </div>
                `;
        }
        
        // Função para cancelar solicitação
        async function cancelMyRequest(requestId) {
            if (!confirm('Tem certeza que deseja cancelar esta solicitação?')) return;
            
            try {
                const supabase = createSupabaseClient();
                const { error } = await supabase
                    .from('password_requests')
                    .update({ status: 'cancelled' })
                    .eq('id', requestId);
                
                if (error) throw error;
                
                showNotification('Solicitação cancelada com sucesso', 'success');
                await loadMyPasswordRequests();
            } catch (error) {
                console.error('Erro ao cancelar solicitação:', error);
                showNotification('Erro ao cancelar solicitação', 'error');
            }
        }
        
        // Adicionar event listeners para filtros
        document.addEventListener('DOMContentLoaded', function() {
            const statusFilter = document.getElementById('myRequestsStatusFilter');
            const dateFilter = document.getElementById('myRequestsDateFilter');
            
            if (statusFilter) {
                statusFilter.addEventListener('change', loadMyPasswordRequests);
            }
            
            if (dateFilter) {
                dateFilter.addEventListener('change', loadMyPasswordRequests);
            }
        });
        
        // Função para abrir modal PWA
        function openXandriaStore() {
            openPWAModal();
        }
        
        // ==================== SISTEMA PWA ====================
        
        let deferredPrompt;
        const CURRENT_VERSION = '2.0.0';
        
        // Capturar o evento beforeinstallprompt
        window.addEventListener('beforeinstallprompt', (e) => {
            console.log('PWA pode ser instalada');
            e.preventDefault();
            deferredPrompt = e;
            checkPWAStatus();
        });
        
        // Função para verificar status da PWA
        async function checkPWAStatus() {
            const isInstalled = await isPWAInstalled();
            const installedVersion = localStorage.getItem('pwa_version');
            
            console.log('PWA Status:', { 
                isInstalled, 
                installedVersion, 
                currentVersion: CURRENT_VERSION,
                displayMode: window.matchMedia('(display-mode: standalone)').matches
            });
            
            if (isInstalled) {
                showUninstallButton();
            } else if (deferredPrompt) {
                showInstallButton();
            }
        }
        
        // Verificar se PWA está instalada
        async function isPWAInstalled() {
            const isStandalone = window.matchMedia('(display-mode: standalone)').matches;
            const installData = localStorage.getItem('pwa_version');
            const hasValidInstallData = installData && JSON.parse(installData).version === CURRENT_VERSION;
            
            return isStandalone || hasValidInstallData;
        }
        
        // Função para instalar a PWA
        async function installPWA() {
            if (!deferredPrompt) {
                showNotification('App já está instalado ou não pode ser instalado', 'error');
                return;
            }
            
            try {
                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;
                
                if (outcome === 'accepted') {
                    console.log('Usuário aceitou a instalação');
                    
                    setTimeout(async () => {
                        const isReallyInstalled = await checkRealInstallation();
                        
                        if (isReallyInstalled) {
                            showNotification('App instalado com sucesso!', 'success');
                            
                            const installData = {
                                version: CURRENT_VERSION,
                                timestamp: Date.now(),
                                userInfo: getCurrentUserInfo(),
                                url: window.location.href
                            };
                            localStorage.setItem('pwa_version', JSON.stringify(installData));
                            
                            showUninstallButton();
                        } else {
                            showNotification('Erro: App não foi instalado corretamente', 'error');
                        }
                    }, 2000);
                    
                } else {
                    showNotification('Instalação cancelada', 'info');
                }
                
            } catch (error) {
                console.error('Erro durante instalação:', error);
                showNotification('Erro durante instalação: ' + error.message, 'error');
            }
            
            deferredPrompt = null;
        }
        
        // Verificar se realmente instalou
        async function checkRealInstallation() {
            const isStandalone = window.matchMedia('(display-mode: standalone)').matches;
            const hasServiceWorker = 'serviceWorker' in navigator && 
                await navigator.serviceWorker.getRegistration() !== null;
            
            return isStandalone;
        }
        
        // Obter informações do usuário atual
        function getCurrentUserInfo() {
            const currentUser = JSON.parse(localStorage.getItem('currentUser') || 'null');
            return {
                role: 'funcionario',
                userId: currentUser?.id || 'unknown',
                userName: currentUser?.name || 'unknown',
                farmId: currentUser?.farm_id || 'unknown',
                timestamp: Date.now()
            };
        }
        
        // Função para desinstalar a PWA
        async function uninstallPWA() {
            if (confirm('Tem certeza que deseja desinstalar o app?')) {
                try {
                    localStorage.removeItem('pwa_version');
                    
                    if ('serviceWorker' in navigator) {
                        const registrations = await navigator.serviceWorker.getRegistrations();
                        for (let registration of registrations) {
                            await registration.unregister();
                        }
                    }
                    
                    showNotification('App desinstalado com sucesso!', 'success');
                    showInstallButton();
                
            } catch (error) {
                    console.error('Erro ao desinstalar:', error);
                    showNotification('Erro ao desinstalar app', 'error');
                }
            }
        }
        
        // Funções para mostrar/esconder botões
        function showInstallButton() {
            document.getElementById('pwaInstallButton')?.classList.remove('hidden');
            document.getElementById('pwaUninstallButton')?.classList.add('hidden');
        }
        
        function showUninstallButton() {
            document.getElementById('pwaInstallButton')?.classList.add('hidden');
            document.getElementById('pwaUninstallButton')?.classList.remove('hidden');
        }
        
        // Detectar se o app já está instalado
        window.addEventListener('appinstalled', () => {
            console.log('PWA foi instalada');
            showUninstallButton();
        });
        
        // Registrar service worker
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then((registration) => {
                        console.log('SW registrado: ', registration);
                    })
                    .catch((registrationError) => {
                        console.log('SW falhou: ', registrationError);
                    });
            });
        }
        
        // ==================== FUNÇÕES DO MODAL PWA ====================
        
        // Abrir modal PWA
        function openPWAModal() {
            const modal = document.getElementById('pwaModal');
            modal.classList.remove('hidden');
            updatePWAStatusInModal();
        }
        
        // Fechar modal PWA
        function closePWAModal() {
            const modal = document.getElementById('pwaModal');
            modal.classList.add('hidden');
        }
        
        // Atualizar status PWA no modal
        async function updatePWAStatusInModal() {
            const statusDiv = document.getElementById('pwaStatus');
            const installButton = document.getElementById('modalInstallButton');
            const uninstallButton = document.getElementById('modalUninstallButton');
            
            const isInstalled = await isPWAInstalled();
            const installData = localStorage.getItem('pwa_version');
            const currentUser = JSON.parse(localStorage.getItem('currentUser') || '{}');
            
            if (isInstalled) {
                statusDiv.innerHTML = `
                    <div class="flex items-center space-x-3 p-4 bg-green-100 dark:bg-green-900/20 rounded-xl">
                        <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-semibold text-green-800 dark:text-green-200">App Instalado</h4>
                            <p class="text-green-600 dark:text-green-300 text-sm">LacTech está instalado e pronto para uso</p>
                        </div>
                    </div>
                `;
                installButton.classList.add('hidden');
                uninstallButton.classList.remove('hidden');
            } else if (deferredPrompt) {
                statusDiv.innerHTML = `
                    <div class="flex items-center space-x-3 p-4 bg-green-100 dark:bg-green-900/20 rounded-xl">
                        <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"></path>
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-semibold text-green-800 dark:text-green-200">Pronto para Instalar</h4>
                            <p class="text-green-600 dark:text-green-300 text-sm">Clique em "Instalar App" para adicionar à tela inicial</p>
                        </div>
                    </div>
                `;
                installButton.classList.remove('hidden');
                uninstallButton.classList.add('hidden');
            } else {
                statusDiv.innerHTML = `
                    <div class="flex items-center space-x-3 p-4 bg-gray-100 dark:bg-gray-800 rounded-xl">
                        <div class="w-8 h-8 bg-gray-500 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-800 dark:text-gray-200">Instalação Indisponível</h4>
                            <p class="text-gray-600 dark:text-gray-300 text-sm">Use o navegador para instalar o app</p>
                        </div>
                    </div>
                `;
                installButton.classList.add('hidden');
                uninstallButton.classList.add('hidden');
            }
        }
        
        // Instalar PWA do modal
        async function installPWAFromModal() {
            await installPWA();
            setTimeout(() => {
                updatePWAStatusInModal();
            }, 3000);
        }
        
        // Desinstalar PWA do modal
        async function uninstallPWAFromModal() {
            await uninstallPWA();
            setTimeout(() => {
                updatePWAStatusInModal();
            }, 1000);
        }
        
        // Tornar funções globais
        window.openMyPasswordRequests = openMyPasswordRequests;
        window.closeMyPasswordRequestsModal = closeMyPasswordRequestsModal;
        window.cancelMyRequest = cancelMyRequest;
        window.openXandriaStore = openXandriaStore;
        window.installPWA = installPWA;
        window.uninstallPWA = uninstallPWA;
        window.openPWAModal = openPWAModal;
        window.closePWAModal = closePWAModal;
        window.installPWAFromModal = installPWAFromModal;
        window.uninstallPWAFromModal = uninstallPWAFromModal;
        
        // Sistema de verificação de logout automático por alteração de senha
        async function checkPasswordChangeLogout() {
            try {
                // Verificar se createSupabaseClient está disponível
                if (typeof createSupabaseClient !== 'function') {
                    console.log('createSupabaseClient não disponível ainda, aguardando...');
                    return;
                }
                
                const supabase = createSupabaseClient();
                const { data: { user } } = await supabase.auth.getUser();
                
                if (!user) return;
                
                // Verificar sinais de logout no localStorage
                const signals = JSON.parse(localStorage.getItem('password_change_signals') || '[]');
                const userSignal = signals.find(signal => signal.userId === user.id);
                
                if (userSignal) {
                    // Remover sinal do localStorage
                    const updatedSignals = signals.filter(signal => signal.userId !== user.id);
                    localStorage.setItem('password_change_signals', JSON.stringify(updatedSignals));
                    
                    // Descriptografar senha se necessário
                    let displayPassword = userSignal.newPassword;
                    if (userSignal.encrypted) {
                        displayPassword = await decryptPassword(userSignal.newPassword);
                    }
                    
                    // Mostrar notificação e fazer logout
                    showPasswordChangeNotification(displayPassword);
                    
                    // Fazer logout após 5 segundos
                    setTimeout(async () => {
                        await supabase.auth.signOut();
                        window.location.href = 'login.php';
                    }, 5000);
                }
                
            } catch (error) {
                console.error('Erro ao verificar logout automático:', error);
            }
        }
        
        // Mostrar notificação de alteração de senha
        function showPasswordChangeNotification(newPassword) {
            // Criar modal de notificação
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black bg-opacity-75 backdrop-blur-sm flex items-center justify-center z-[99999]';
            modal.innerHTML = `
                <div class="bg-white dark:bg-black rounded-2xl shadow-2xl max-w-md w-full mx-4 p-6">
                    <div class="text-center">
                        <div class="w-16 h-16 bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-3">Senha Alterada com Sucesso!</h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-4">
                            Sua senha foi alterada pelo gerente. Por segurança, você será deslogado automaticamente em <span id="countdown">5</span> segundos.
                        </p>
                        <div class="bg-green-50 border border-green-200 rounded-lg p-3 mb-4">
                            <p class="text-sm text-green-800">
                                <strong>Nova senha:</strong> <span class="font-mono">${newPassword}</span>
                            </p>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Use a nova senha para fazer login novamente.
                        </p>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            // Contador regressivo
            let countdown = 5;
            const countdownElement = document.getElementById('countdown');
            const interval = setInterval(() => {
                countdown--;
                if (countdownElement) {
                    countdownElement.textContent = countdown;
                }
                if (countdown <= 0) {
                    clearInterval(interval);
                }
            }, 1000);
        }
        
        // Sistema otimizado de verificação de logout automático
        let logoutCheckInterval = null;
        let lastLogoutCheck = 0;
        const LOGOUT_CHECK_INTERVAL = 30000; // 30 segundos em vez de 10
        
        function startLogoutChecker() {
            // Evitar múltiplos intervalos
            if (logoutCheckInterval) {
                clearInterval(logoutCheckInterval);
            }
            
            logoutCheckInterval = setInterval(async () => {
                const now = Date.now();
                // Evitar verificações muito frequentes
                if (now - lastLogoutCheck < LOGOUT_CHECK_INTERVAL) {
                    return;
                }
                lastLogoutCheck = now;
                
                await checkPasswordChangeLogout();
            }, LOGOUT_CHECK_INTERVAL);
        }
        
        function stopLogoutChecker() {
            if (logoutCheckInterval) {
                clearInterval(logoutCheckInterval);
                logoutCheckInterval = null;
            }
        }
        
        // Verificar imediatamente ao carregar a página
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(checkPasswordChangeLogout, 2000);
            startLogoutChecker();
        });
        
        // Limpar intervalos quando a página for descarregada
        window.addEventListener('beforeunload', () => {
            stopLogoutChecker();
        });
        
        // Sistema de criptografia simples para senhas
        async function decryptPassword(encryptedPassword) {
            try {
                if (encryptedPassword.startsWith('enc_')) {
                    const encrypted = encryptedPassword.substring(4);
                    // Para SHA-256, não podemos descriptografar, então retornamos o hash
                    // Em um sistema real, você usaria criptografia simétrica
                    return encrypted;
                }
                return encryptedPassword;
            } catch (error) {
                console.error('Erro ao descriptografar senha:', error);
                return encryptedPassword;
            }
        }
        
        // Tornar função global
        window.decryptPassword = decryptPassword;
    </script>
    
    <!-- Chart.js carregado no final para garantir disponibilidade -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Verificar se Chart.js foi carregado e inicializar gráficos
        window.addEventListener('load', function() {
            if (typeof Chart === 'undefined') {
                console.error('❌ Chart.js não foi carregado!');
            } else {
                console.log('✅ Chart.js carregado com sucesso!');
                
                // Inicializar gráfico de produção
                const productionCtx = document.getElementById('productionChart');
                if (productionCtx) {
                    console.log('✅ Inicializando gráfico productionChart...');
                    window.productionChart = new Chart(productionCtx, {
                        type: 'bar',
                        data: {
                            labels: [],
                            datasets: [{
                                label: 'Produção (L)',
                                data: [],
                                backgroundColor: '#5bb85b',
                                borderRadius: 8
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            }
                        }
                    });
                    console.log('✅ Gráfico productionChart inicializado com sucesso');
                } else {
                    console.error('❌ Elemento productionChart não encontrado no DOM');
                }
            }
            
            // Verificar se a dashboard está funcionando
            const dashboardTab = document.getElementById('dashboard-tab');
            const registerTab = document.getElementById('register-tab');
            const historyTab = document.getElementById('history-tab');
            
            console.log('📋 Verificação da dashboard:');
            console.log('- Dashboard:', dashboardTab ? '✅' : '❌');
            console.log('- Register:', registerTab ? '✅' : '❌');
            console.log('- History:', historyTab ? '✅' : '❌');
            
            // Verificar classes CSS das abas
            if (dashboardTab) {
                console.log('Dashboard classes:', dashboardTab.className);
            }
            if (registerTab) {
                console.log('Register classes:', registerTab.className);
            }
            if (historyTab) {
                console.log('History classes:', historyTab.className);
            }
            
            // FORÇAR que a aba dashboard esteja visível
            console.log('🚀 FORÇANDO aba dashboard visível...');
            switchToTab('dashboard');
            
        });
        
        
    </script>

    <!-- Modal PWA Full Screen - Estilo Xandria Store -->
    <div id="pwaModal" class="fixed inset-0 bg-gray-50 z-[9999] hidden">
        <div class="w-full h-full flex flex-col overflow-y-auto">
            <!-- Header do Modal - Estilo Xandria Store -->
            <header class="bg-white no-border sticky top-0 z-50 backdrop-blur-sm bg-opacity-95">
                <div class="flex items-center justify-between px-6 py-4">
                    <div class="flex items-center space-x-4">
                        <div class="w-10 h-10 flex items-center justify-center">
                            <img src="assets/img/xandria-preta.png" alt="LacTech" class="w-8 h-8">
                        </div>
                        <div>
                            <h1 class="text-xl font-bold tracking-tight text-black">LacTech - Funcionário</h1>
                            <p class="text-xs text-gray-500 -mt-1">Sistema Agropecuário</p>
                        </div>
                    </div>
                    <button onclick="closePWAModal()" class="w-10 h-10 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-xl flex items-center justify-center transition-colors">
                        <svg class="w-6 h-6 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </header>

            <!-- Featured Banner -->
            <div class="px-6 py-6">
                <div class="max-w-4xl mx-auto">
                    <div class="rounded-3xl overflow-hidden shadow-xl card-hover cursor-pointer">
                        <img src="https://i.postimg.cc/7LcySj3K/agroneg-cio.png" alt="Banner LacTech" class="w-full h-48 sm:h-56 md:h-64 lg:h-72 object-cover">
                    </div>
                </div>
            </div>

            <!-- Conteúdo do Modal -->
            <div class="flex-1 px-6 pb-6">
                <div class="max-w-4xl mx-auto">
                    <!-- Status da Instalação - Layout Melhorado -->
                    <div class="bg-gradient-to-br from-green-50 to-emerald-100 dark:from-green-900/30 dark:to-emerald-900/30 rounded-2xl p-6 mb-6 border border-green-200 dark:border-green-800">
                        <div class="flex flex-col sm:flex-row items-center space-y-4 sm:space-y-0 sm:space-x-4 mb-4">
                            <div class="w-14 h-14 bg-gradient-to-br from-green-500 to-green-600 rounded-2xl flex items-center justify-center shadow-lg">
                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"></path>
                                </svg>
                            </div>
                            <div class="text-center sm:text-left">
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Instalar App LacTech</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Acesso rápido ao sistema</p>
                            </div>
                        </div>
                        
                        <div id="pwaStatus" class="space-y-3">
                            <!-- Status será preenchido dinamicamente -->
                        </div>
                    </div>

                    <!-- Informações do Sistema - Layout 2 Colunas -->
                    <div class="grid grid-cols-2 gap-4 mb-8">
                        <!-- Controle de Produção -->
                        <div class="bg-white dark:bg-play-card rounded-2xl p-4 border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-all duration-300">
                            <div class="flex flex-col items-center text-center space-y-3">
                                <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-2xl flex items-center justify-center shadow-lg">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-900 dark:text-white text-sm">Controle de Produção</h4>
                                    <p class="text-gray-600 dark:text-gray-400 text-xs mt-1">Registre volumes</p>
                                </div>
                            </div>
                        </div>

                        <!-- Acesso Rápido -->
                        <div class="bg-white dark:bg-play-card rounded-2xl p-4 border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-all duration-300">
                            <div class="flex flex-col items-center text-center space-y-3">
                                <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-2xl flex items-center justify-center shadow-lg">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-900 dark:text-white text-sm">Acesso Rápido</h4>
                                    <p class="text-gray-600 dark:text-gray-400 text-xs mt-1">Da tela inicial</p>
                                </div>
                            </div>
                        </div>

                        <!-- Relatórios -->
                        <div class="bg-white dark:bg-play-card rounded-2xl p-4 border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-all duration-300">
                            <div class="flex flex-col items-center text-center space-y-3">
                                <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-2xl flex items-center justify-center shadow-lg">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-900 dark:text-white text-sm">Relatórios</h4>
                                    <p class="text-gray-600 dark:text-gray-400 text-xs mt-1">Gráficos e dados</p>
                                </div>
                            </div>
                        </div>

                        <!-- Interface Nativa -->
                        <div class="bg-white dark:bg-play-card rounded-2xl p-4 border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-all duration-300">
                            <div class="flex flex-col items-center text-center space-y-3">
                                <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-2xl flex items-center justify-center shadow-lg">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-900 dark:text-white text-sm">Interface Nativa</h4>
                                    <p class="text-gray-600 dark:text-gray-400 text-xs mt-1">Experiência nativa</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Botões de Ação - Layout Melhorado -->
                    <div class="flex flex-col gap-3">
                        <button id="modalInstallButton" onclick="installPWAFromModal()" class="w-full px-6 py-4 bg-gradient-to-r from-green-500 to-green-600 text-white font-semibold rounded-xl hover:from-green-600 hover:to-green-700 transition-all shadow-lg hidden">
                            <div class="flex items-center justify-center space-x-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"></path>
                                </svg>
                                <span>Instalar App</span>
                            </div>
                        </button>
                        
                        <button id="modalUninstallButton" onclick="uninstallPWAFromModal()" class="w-full px-6 py-4 bg-gradient-to-r from-gray-500 to-gray-600 text-white font-semibold rounded-xl hover:from-gray-600 hover:to-gray-700 transition-all shadow-lg hidden">
                            <div class="flex items-center justify-center space-x-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"></path>
                                </svg>
                                <span>Desinstalar App</span>
                            </div>
                        </button>
                        
                        <button onclick="closePWAModal()" class="w-full px-6 py-3 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-medium rounded-xl hover:bg-gray-200 dark:hover:bg-gray-700 transition-all border border-gray-200 dark:border-gray-600">
                            Fechar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Chamada -->
    <div id="callModal" class="hidden fixed inset-0 bg-black bg-opacity-90 flex items-center justify-center z-[9999]">
        <div class="bg-gray-900 rounded-2xl w-full max-w-4xl mx-4 h-96 md:h-[500px] flex flex-col">
            <!-- Header da Chamada -->
            <div class="flex items-center justify-between p-6 border-b border-gray-700">
                <div class="flex items-center space-x-3">
                    <div id="callAvatar" class="w-12 h-12 rounded-full bg-gray-600 flex items-center justify-center">
                        <!-- Avatar será inserido aqui -->
                    </div>
                    <div>
                        <h3 id="callUserName" class="text-white font-semibold text-lg">Nome do Usuário</h3>
                        <p id="callStatus" class="text-gray-400 text-sm">Conectando...</p>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <button class="p-2 text-gray-400 hover:text-white hover:bg-gray-700 rounded-full transition-colors" title="Configurações">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </button>
                    <button class="p-2 text-gray-400 hover:text-white hover:bg-gray-700 rounded-full transition-colors" title="Participantes">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                    </button>
                    <button class="p-2 text-gray-400 hover:text-white hover:bg-gray-700 rounded-full transition-colors" title="Tela cheia">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Área Principal da Chamada -->
            <div class="flex-1 relative bg-gray-800 rounded-b-2xl overflow-hidden">
                <!-- Vídeo Remoto (principal) -->
                <div id="remoteVideoContainer" class="hidden absolute inset-0">
                    <video id="remoteVideo" autoplay class="w-full h-full object-cover"></video>
                </div>

                <!-- Avatar/Status quando sem vídeo -->
                <div id="callAvatarContainer" class="absolute inset-0 flex flex-col items-center justify-center">
                    <div id="callMainAvatar" class="w-32 h-32 md:w-40 md:h-40 rounded-full bg-gray-600 flex items-center justify-center mb-6">
                        <!-- Avatar principal será inserido aqui -->
                    </div>
                    <h2 id="callMainUserName" class="text-white text-2xl md:text-3xl font-semibold mb-2">Nome do Usuário</h2>
                    <p id="callMainStatus" class="text-gray-400 text-lg">Conectando...</p>
                    <div id="callDuration" class="text-gray-500 text-sm mt-4 hidden">00:00</div>
                </div>

                <!-- Vídeo Local (pequeno) -->
                <div id="localVideoContainer" class="hidden absolute top-4 right-4 w-32 h-24 md:w-40 md:h-30 rounded-lg overflow-hidden bg-gray-700">
                    <video id="localVideo" autoplay muted class="w-full h-full object-cover"></video>
                </div>
            </div>

            <!-- Controles da Chamada -->
            <div class="p-6 bg-gray-900 rounded-b-2xl">
                <div class="flex justify-center items-center space-x-4">
                    <!-- Aceitar Chamada -->
                    <button id="acceptCallBtn" onclick="acceptCall()" class="hidden p-4 bg-green-500 hover:bg-green-600 text-white rounded-full transition-colors shadow-lg">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </button>

                    <!-- Rejeitar Chamada -->
                    <button id="rejectCallBtn" onclick="rejectCall()" class="hidden p-4 bg-red-500 hover:bg-red-600 text-white rounded-full transition-colors shadow-lg">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>

                    <!-- Toggle Câmera -->
                    <button id="cameraBtn" onclick="toggleCamera()" class="hidden p-4 bg-white hover:bg-gray-100 text-gray-800 rounded-full transition-colors shadow-lg">
                        <svg id="cameraIcon" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </button>

                    <!-- Toggle Microfone -->
                    <button id="muteBtn" onclick="toggleMute()" class="hidden p-4 bg-white hover:bg-gray-100 text-gray-800 rounded-full transition-colors shadow-lg">
                        <svg id="muteIcon" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path>
                        </svg>
                    </button>

                    <!-- Encerrar Chamada -->
                    <button id="endCallBtn" onclick="endCall()" class="hidden p-4 bg-red-500 hover:bg-red-600 text-white rounded-full transition-colors shadow-lg">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 8l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M3 3l1.5 1.5L21 21l-1.5-1.5L3 3z"></path>
                        </svg>
                    </button>

                    <!-- Ligar -->
                    <button id="startCallBtn" onclick="initiateCall()" class="hidden p-4 bg-green-500 hover:bg-green-600 text-white rounded-full transition-colors shadow-lg">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

</body>
</html>