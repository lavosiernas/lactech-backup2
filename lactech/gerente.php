<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Gerente - Sistema Leiteiro</title>
    
    <!-- PWA Meta Tags -->
    <meta name="description" content="Painel do Gerente - Sistema completo para gestão de produção leiteira, controle de qualidade e relatórios">
    <meta name="theme-color" content="#166534">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="LacTech Gerente">
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
    
    <!-- Critical CSS - inline for fastest loading -->
    <style>
        /* Critical styles inline to prevent FOUC */
        * { box-sizing: border-box; }
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
        /* .loading-screen { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: #000; display: flex; flex-direction: column; align-items: center; justify-content: center; z-index: 9999; } REMOVIDO - usando apenas modal HTML */
        .main-content { opacity: 0; transition: opacity 0.5s ease-in; }
        .main-content.loaded { opacity: 1; }
        .header-logo { width: 40px; height: 40px; object-fit: contain; }
        .header-logo-container { display: flex; align-items: center; justify-content: center; }
        
        /* Loading Screen Styles */
        .loading-screen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: white !important;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 99999;
            transition: opacity 0.5s ease-out;
        }
        
        /* Esconder tela de carregamento por padrão após carregamento */
        .loading-screen.hidden {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
            pointer-events: none !important;
        }
        
        .loading-screen.fade-out {
            opacity: 0;
            pointer-events: none;
        }
        
        .loading-content {
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
        }
        
        .loading-logo {
            width: 120px;
            height: 120px;
            margin: 0 auto 2rem auto;
            display: block;
        }
        
        .loading-text {
            color: #166534;
            font-size: 2rem;
            font-weight: 700;
            margin: 0 auto 0.5rem auto;
            text-align: center;
            width: 100%;
        }
        
        .loading-subtext {
            color: #6b7280;
            font-size: 1rem;
            text-align: center;
            margin: 0 auto 3rem auto;
            width: 100%;
        }
        
        .loading-progress {
            width: 300px;
            height: 8px;
            background: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
            margin: 0 auto 2rem auto;
        }
        
        .loading-progress-bar {
            height: 100%;
            background: #16a34a;
            border-radius: 4px;
            animation: progress 4s ease-in-out infinite;
        }
        
        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 3px solid #e5e7eb;
            border-top: 3px solid #16a34a;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto;
            display: block;
        }
        
        /* CRITICAL FIX: Prevent FOUC (Flash of Unstyled Content) - Modal */
        #profileModal {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
            pointer-events: none !important;
            z-index: -1 !important;
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
            height: 100% !important;
            background: transparent !important;
            transition: none !important;
            animation: none !important;
        }
        
        /* Only allow modal to show when explicitly enabled - PROPER MODAL STYLES */
        #profileModal.modal-enabled {
            display: flex !important;
            visibility: visible !important;
            opacity: 1 !important;
            pointer-events: auto !important;
            z-index: 9999 !important;
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
            height: 100% !important;
            background: rgba(0, 0, 0, 0.5) !important;
            align-items: center !important;
            justify-content: center !important;
            transition: opacity 0.3s ease-in-out !important;
        }
        
        /* Modal content positioning - FULLSCREEN for profile modal */
        #profileModal.modal-enabled .modal-content {
            background: white !important;
            border-radius: 0 !important;
            box-shadow: none !important;
            max-width: 100vw !important;
            max-height: 100vh !important;
            width: 100vw !important;
            height: 100vh !important;
            overflow-y: auto !important;
            position: relative !important;
            margin: 0 !important;
        }
        
        /* Block all modal content until enabled */
        #profileModal:not(.modal-enabled) * {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
        }
        
        /* Fix user modals - make them responsive and fullscreen - PREVENT FLASH */
        #addUserModal,
        #editUserModal,
        #deleteUserModal {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
            background: rgba(0, 0, 0, 0.5) !important;
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
            align-items: center !important;
            justify-content: center !important;
            z-index: -1 !important;
            pointer-events: none !important;
            transition: none !important;
        }
        
        #addUserModal.show,
        #editUserModal.show,
        #deleteUserModal.show {
            display: flex !important;
            visibility: visible !important;
            opacity: 1 !important;
            z-index: 9999 !important;
            pointer-events: auto !important;
            transition: opacity 0.3s ease-in-out !important;
        }
        
        /* User modal content - responsive */
        #addUserModal .modal-content,
        #editUserModal .modal-content,
        #deleteUserModal .modal-content {
            background: white !important;
            border-radius: 12px !important;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
            max-width: 95vw !important;
            max-height: 95vh !important;
            width: 100% !important;
            min-width: 400px !important;
            overflow-y: auto !important;
            position: relative !important;
            margin: 20px !important;
        }
        
        /* Mobile responsive */
        @media (max-width: 768px) {
            #addUserModal .modal-content,
            #editUserModal .modal-content,
            #deleteUserModal .modal-content {
                width: 95vw !important;
                max-width: 95vw !important;
                margin: 10px !important;
                border-radius: 8px !important;
            }
        }
        
        @keyframes progress {
            0% { width: 0%; }
            50% { width: 70%; }
            100% { width: 100%; }
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
    
    <!-- Preload critical resources -->
    <link rel="preload" href="assets/css/critical.css" as="style">
    <link rel="preload" href="assets/js/performance-optimizer.js" as="script">
    <link rel="preload" href="assets/js/config_mysql.js" as="script">
    
    <!-- Load critical CSS immediately -->
    <link rel="stylesheet" href="assets/css/critical.css">
    
    <!-- Tailwind CSS CDN - optimized -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <script>
        // Configurar Tailwind ANTES de carregar outros scripts
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'forest': {
                            50: '#f0f9f0', 100: '#dcf2dc', 200: '#bce5bc', 300: '#8dd18d',
                            400: '#5bb85b', 500: '#369e36', 600: '#2a7f2a', 700: '#236523',
                            800: '#1f511f', 900: '#1a431a',
                        }
                    }
                }
            }
        }
    </script>
    
    <!-- ==================== SCRIPTS EXTERNOS ==================== -->
    <!-- Chart.js - Carregar primeiro para gráficos -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- ==================== SCRIPTS LOCAIS - ORDEM CRÍTICA ==================== -->
    <!-- 1. Performance e Configuração (BASE) -->
    <script src="assets/js/performance-optimizer.js"></script>
    <script src="assets/js/config_mysql.js"></script>
    <script src="assets/js/console-guard.js"></script>
    
    <!-- 2. Sistema de Modais (DEPENDÊNCIA DO GERENTE.JS) -->
    <script src="assets/js/modal-system.js"></script>
    <script src="assets/js/native-notifications.js"></script>
    
    <!-- 3. Gerenciamento Offline -->
    <script src="assets/js/offline-manager.js"></script>
    <script src="assets/js/offline-sync.js"></script>
    
    <!-- 4. Utilitários -->
    <script src="assets/js/pdf-generator.js"></script>
    
    <style>
        /* CRITICAL: Prevenir bugs de inicializaçãoo */
        
        /* Body e conteúdo principal sempre visíveis */
        html, body {
            visibility: visible !important;
            opacity: 1 !important;
        }
        
        body {
            display: block !important;
        }
        
        /* APENAS modals HTML estáticos começam escondidos */
        #profileModal,
        #moreModal,
        #managerPhotoChoiceModal,
        #managerCameraModal,
        #contactsModal,
        .modal.hidden,
        .fullscreen-modal.hidden {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
        }
        
        /* Sistema de loading REMOVIDO - sem tela de carregamento */
        
        /* Quando modal está com classe 'show', mostrar */
        .fullscreen-modal.show {
            display: flex !important;
            visibility: visible !important;
            opacity: 1 !important;
        }
        
        /* Quando modal NÃO tem classe 'hidden', ele pode ser mostrado via JS */
        .fullscreen-modal:not(.hidden).show {
            display: flex !important;
        }
    </style>

</head>
<!-- Loading Screen Implemented -->

<body class="gradient-mesh antialiased transition-colors duration-300 main-content" id="mainBody" data-non-critical>

    <!-- Loading Screen -->
    <div id="loadingScreen" class="loading-screen">
        <div class="loading-content">
            <img src="assets/img/lactech-logo.png" alt="LacTech Logo" class="loading-logo">
            <div class="loading-text">LacTech</div>
            <div class="loading-subtext">Sistema de Gestão Leiteira</div>
            <div class="loading-progress">
                <div class="loading-progress-bar"></div>
            </div>
            <div class="loading-spinner"></div>
        </div>
    </div>

    <!-- Header -->
    <header class="gradient-forest shadow-xl sticky top-0 z-40 border-b border-forest-800 header-compact">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-14 sm:h-16">
                <div class="flex items-center space-x-3">
                    <div class="header-logo-container">
                        <img src="assets/img/lactech-logo.png" alt="LacTech Logo" class="header-logo">
                    </div>
                    <div>
                        <h1 class="text-lg font-bold text-white tracking-tight">PAINEL DO GERENTE</h1>
                        <p class="text-xs text-forest-200" id="farmNameHeader">Carregando...</p>
                    </div>
                </div>

                <!-- Desktop Navigation -->
                <nav class="hidden md:flex space-x-1">
                    <button class="nav-item active relative px-3 py-2 text-sm font-semibold text-white hover:text-forest-200 transition-all rounded-lg" data-tab="dashboard">
                        Dashboard
                    </button>
                    <button class="nav-item relative px-3 py-2 text-sm font-semibold text-white hover:text-forest-200 transition-all rounded-lg" data-tab="volume">
                        Volume
                    </button>
                    <button class="nav-item relative px-3 py-2 text-sm font-semibold text-white hover:text-forest-200 transition-all rounded-lg" data-tab="quality">
                        Qualidade
                    </button>
                    <button class="nav-item relative px-3 py-2 text-sm font-semibold text-white hover:text-forest-200 transition-all rounded-lg" data-tab="payments">
                        Financeiro
                    </button>
                    <button class="nav-item relative px-3 py-2 text-sm font-semibold text-white hover:text-forest-200 transition-all rounded-lg" data-tab="users">
                        Usuários
                    </button>
                    <button onclick="openMoreModal()" class="relative px-3 py-2 text-sm font-semibold text-white hover:text-forest-200 transition-all rounded-lg flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.5 12.5c0 .828-.672 1.5-1.5 1.5s-1.5-.672-1.5-1.5.672-1.5 1.5-1.5 1.5.672 1.5 1.5zM18.5 12.5c0 .828-.672 1.5-1.5 1.5s-1.5-.672-1.5-1.5.672-1.5 1.5-1.5 1.5.672 1.5 1.5zM12 20c-2.5 0-4.5-2-4.5-4.5S9.5 11 12 11s4.5 2 4.5 4.5S14.5 20 12 20zM12 8c-1.5 0-2.5-1-2.5-2.5S10.5 3 12 3s2.5 1 2.5 2.5S13.5 8 12 8z"/>
                        </svg>
                        <span>MAIS</span>
                    </button>
                </nav>

                <div class="flex items-center space-x-4">
                    <!-- Botão de Notificações -->
                    <button onclick="openNotificationsModal()" class="relative p-2 text-white hover:text-forest-200 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-5 5v-5zM9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span id="notificationCounter" class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center hidden">0</span>
                        <!-- Indicador de tempo real -->
                        <div id="realTimeIndicator" class="absolute -bottom-1 -right-1 w-3 h-3 bg-green-500 rounded-full animate-pulse hidden" title="Sistema de atualização automática ativo"></div>
                    </button>
                    
                    
                    
                    <button onclick="openProfileModalManually()" class="flex items-center space-x-3 text-white hover:text-forest-200 p-2 rounded-lg transition-all" id="profileButton">
                        <div class="relative w-8 h-8">
                            <!-- Foto do usuário -->
                            <img id="headerProfilePhoto" src="" alt="Foto de Perfil" class="w-8 h-8 object-cover rounded-full hidden">
                            <!-- ícone padrão -->
                            <div id="headerProfileIcon" class="w-8 h-8 bg-whitebg-opacity-20 rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="text-left hidden sm:block">
                            <div class="text-sm font-semibold" id="managerName">Carregando...</div>
                            <div class="text-xs text-forest-200">Gerente</div>
                        </div>
                    </button>
                </div>
            </div>
        </div>
    </header>
    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-2 sm:px-4 lg:px-6 xl:px-8 py-4 sm:py-6 lg:py-8 pb-16 sm:pb-20 md:pb-4">
        
        <!-- Dashboard Tab -->
        <div id="dashboard-tab" class="tab-content">
            <!-- Welcome Section -->
            <div class="gradient-forest rounded-2xl p-4 sm:p-6 mb-4 sm:mb-6 text-white shadow-xl relative overflow-hidden">
                <div class="relative z-10">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-xl sm:text-2xl font-bold mb-1 sm:mb-2">Bem-vindo, <span id="managerWelcome">Carregando...</span>!</h2>
                            <p class="text-forest-200 text-sm sm:text-base font-medium mb-2 sm:mb-3">Painel de controle gerencial</p>
                            <div class="flex items-center space-x-2 sm:space-x-4">
                                <div class="text-xs font-medium">última atualização: <span id="lastUpdate">Agora</span></div>
                            </div>
                        </div>
                        <div class="hidden sm:block">
                            <div class="w-12 h-12 sm:w-16 sm:h-16 bg-whitebg-opacity-20 rounded-2xl flex items-center justify-center backdrop-blur-sm">
                                <svg class="w-6 h-6 sm:w-8 sm:h-8" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Key Metrics -->
            <div class="grid grid-cols-2 gap-3 sm:gap-4 mb-4 sm:mb-6 grid-compact">
                <div class="metric-card rounded-2xl p-3 sm:p-4 text-center card-compact">
                    <div class="w-10 h-10 sm:w-12 sm:h-12 bg-blue-500 rounded-xl flex items-center justify-center mx-auto mb-2 sm:mb-3 shadow-lg">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
                        </svg>
                    </div>
                    <div class="metric-value font-bold text-slate-900 mb-1" id="todayVolume">-- L</div>
                    <div class="metric-label text-slate-500 font-medium">Volume Hoje</div>
                    <div class="metric-label text-slate-600 font-semibold mt-1">Litros</div>
                </div>
                
                <div class="metric-card rounded-2xl p-3 sm:p-4 text-center card-compact">
                    <div class="w-10 h-10 sm:w-12 sm:h-12 bg-green-500 rounded-xl flex items-center justify-center mx-auto mb-2 sm:mb-3 shadow-lg">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="metric-value font-bold text-slate-900 mb-1" id="qualityAverage">--%</div>
                    <div class="metric-label text-slate-500 font-medium">Qualidade Média</div>
                    <div class="metric-label text-slate-600 font-semibold mt-1">Hoje</div>
                </div>
                
                <div class="metric-card rounded-2xl p-3 sm:p-4 text-center card-compact">
                    <div class="w-10 h-10 sm:w-12 sm:h-12 bg-purple-500 rounded-xl flex items-center justify-center mx-auto mb-2 sm:mb-3 shadow-lg">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                    </div>
                    <div class="metric-value font-bold text-slate-900 mb-1" id="pendingPayments">R$ --</div>
                    <div class="metric-label text-slate-500 font-medium">Pagamentos Pendentes</div>
                    <div class="metric-label text-slate-600 font-semibold mt-1">Este Mês</div>
                </div>
                
                <div class="metric-card rounded-2xl p-3 sm:p-4 text-center card-compact">
                    <div class="w-10 h-10 sm:w-12 sm:h-12 bg-orange-500 rounded-xl flex items-center justify-center mx-auto mb-2 sm:mb-3 shadow-lg">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <div class="metric-value font-bold text-slate-900 mb-1" id="activeUsers">--</div>
                    <div class="metric-label text-slate-500 font-medium">Usuários Ativos</div>
                    <div class="metric-label text-slate-600 font-semibold mt-1">Sistema</div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6 mb-4 sm:mb-6">
                <!-- Volume Chart -->
                <div class="data-card rounded-2xl p-4 sm:p-6 card-compact">
                    <h3 class="card-title font-bold text-slate-900 mb-3 sm:mb-4">Volume Semanal</h3>
                    <div class="chart-container">
                        <canvas id="volumeChart"></canvas>
                    </div>
                </div>

                <!-- Weekly Production Chart -->
                <div class="data-card rounded-2xl p-6">
                    <h3 class="text-lg font-bold text-slate-900 mb-4">Produção dos últimos 7 Dias</h3>
                    <div class="chart-container">
                        <canvas id="dashboardWeeklyChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Temperature Chart Section -->
            <div class="grid grid-cols-1 gap-6 mb-6">
                <div class="data-card rounded-2xl p-6">
                    <h3 class="text-lg font-bold text-slate-900 mb-4">Controle de Temperatura</h3>
                    <div class="chart-container">
                        <canvas id="temperatureChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Monthly Production Chart -->
            <div class="data-card rounded-2xl p-6 mb-6">
                <h3 class="text-lg font-bold text-slate-900 mb-4">Volume de Produção do Mês</h3>
                <div class="h-64 relative">
                    <canvas id="monthlyProductionChart" width="800" height="256"></canvas>
                </div>
            </div>

            <!-- Recent Activities -->
            <div class="data-card rounded-2xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-slate-900">Atividades Recentes</h3>
                    <button onclick="showTab('volume')" class="text-forest-600 hover:text-forest-700 font-semibold text-sm">Ver Tudo</button>
                </div>
                <div class="space-y-3" id="recentActivities">
                    <div class="text-center py-8">
                        <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <p class="text-gray-500 text-sm">Nenhuma atividade recente</p>
                        <p class="text-gray-400 text-xs">Registros aparecerão aqui</p>
                    </div>
                </div>
            </div>

        </div>

        <!-- Volume Tab -->
        <div id="volume-tab" class="tab-content hidden">
            <div class="space-y-6">
                <!-- Volume Header -->
                <div class="data-card rounded-2xl p-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-6 gap-4">
                        <div>
                            <h2 class="text-xl md:text-2xl font-bold text-slate-900 mb-1">Controle de Volume</h2>
            <p class="text-slate-600 text-sm">Monitore a produção de leite em tempo real</p>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                            <select id="volumePeriod" class="px-4 py-2 border border-slate-200 rounded-lg text-sm focus:border-forest-500 focus:outline-none bg-white">
                                <option value="today">Hoje</option>
                                <option value="week">Esta Semana</option>
                                <option value="month">Este Mês</option>
                            </select>
                            <button onclick="showAddVolumeModal()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:shadow-lg transition-all font-semibold text-sm">
                                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                + Volume
                            </button>
                            <button onclick="showVolumeByCowModal()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:shadow-lg transition-all font-semibold text-sm">
                                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                                </svg>
                                Por Vaca
                            </button>
                            <button onclick="exportVolumeReport()" class="px-4 py-2 bg-forest-600 text-white rounded-lg hover:shadow-lg transition-all font-semibold text-sm">
                                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-4-4m4 4l4-4m3 8H5a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v1"></path>
                                </svg>
                                Exportar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Volume Metrics -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="metric-card rounded-2xl p-4 text-center">
                        <div class="w-12 h-12 bg-blue-500 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
                            </svg>
                        </div>
                        <div class="text-xl font-bold text-slate-900 mb-1" id="volumeToday">-- L</div>
                    <div class="text-xs text-slate-500 font-medium">Hoje</div>
                    <div class="text-xs text-slate-600 font-semibold mt-1">Total</div>
                    </div>
                    
                    <div class="metric-card rounded-2xl p-4 text-center">
                        <div class="w-12 h-12 bg-green-500 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <div class="text-xl font-bold text-slate-900 mb-1" id="volumeWeekAvg">-- L</div>
                    <div class="text-xs text-slate-500 font-medium">Média Semanal</div>
                    <div class="text-xs text-slate-600 font-semibold mt-1">Por Dia</div>
                    </div>
                    
                    <div class="metric-card rounded-2xl p-4 text-center">
                        <div class="w-12 h-12 bg-purple-500 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                            </svg>
                        </div>
                        <div class="text-xl font-bold text-slate-900 mb-1" id="volumeGrowth">+0%</div>
                    <div class="text-xs text-slate-500 font-medium">Crescimento</div>
                    <div class="text-xs text-slate-600 font-semibold mt-1">vs. Semana Anterior</div>
                    </div>
                    
                    <div class="metric-card rounded-2xl p-4 text-center">
                        <div class="w-12 h-12 bg-orange-500 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="text-lg font-bold text-slate-900 mb-1" id="lastCollection">--/--/---- - --:--</div>
                    <div class="text-xs text-slate-500 font-medium">última Coleta</div>
                    <div class="text-xs text-slate-600 font-semibold mt-1">Data e Hora</div>
                    </div>
                </div>

                <!-- Volume Charts -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Daily Production Chart -->
                    <div class="data-card rounded-2xl p-6">
                        <h3 class="text-lg font-bold text-slate-900 mb-4">Produção Diária</h3>
                        <div class="chart-container">
                            <canvas id="dailyVolumeChart"></canvas>
                        </div>
                    </div>

                    <!-- Weekly Production Chart -->
                    <div class="data-card rounded-2xl p-6">
                        <h3 class="text-lg font-bold text-slate-900 mb-4">Produção Semanal</h3>
                        <div class="chart-container">
                            <canvas id="weeklyVolumeChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Weekly Summary Chart (only visible on Sundays) -->
                <div id="weeklySummaryChartContainer" class="data-card rounded-2xl p-6" style="display: none;">
                    <h3 class="text-lg font-bold text-slate-900 mb-4">Resumo Semanal</h3>
                    <div class="chart-container">
                        <canvas id="weeklySummaryChart"></canvas>
                    </div>
                </div>

                <!-- Monthly Volume Chart (only visible on last day of month) -->
                <div id="monthlyVolumeChartContainer" class="data-card rounded-2xl p-6" style="display: none;">
                    <h3 class="text-lg font-bold text-slate-900 mb-4">Produção Mensal</h3>
                    <div class="chart-container">
                        <canvas id="monthlyVolumeChart"></canvas>
                    </div>
                </div>

                <!-- Volume Records -->
                <div class="data-card rounded-2xl p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-slate-900">Registros de Volume</h3>
                        <button onclick="addVolumeRecord()" class="px-4 py-2 bg-forest-600 text-white rounded-lg hover:shadow-lg transition-all font-semibold text-sm">
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Novo Registro
                        </button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th class="text-left py-3 px-4 font-semibold text-slate-900">Data/Hora</th>
                                    <th class="text-left py-3 px-4 font-semibold text-slate-900">Volume (L)</th>
                                    <th class="text-left py-3 px-4 font-semibold text-slate-900">Funcionário</th>
                                    <th class="text-left py-3 px-4 font-semibold text-slate-900">Observações</th>
                                    <th class="text-left py-3 px-4 font-semibold text-slate-900">Açãoes</th>
                                </tr>
                            </thead>
                            <tbody id="volumeRecords">
                                <tr>
                                    <td colspan="5" class="text-center py-8 text-gray-500">
                                        Nenhum registro encontrado
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quality Tab -->
        <div id="quality-tab" class="tab-content hidden">
            <div class="space-y-6">
                <!-- Quality Header -->
                <div class="data-card rounded-2xl p-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-6 gap-4">
                        <div>
                            <h2 class="text-xl md:text-2xl font-bold text-slate-900 mb-1">Controle de Qualidade</h2>
            <p class="text-slate-600 text-sm">Monitore os parâmetros de qualidade do leite</p>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                            <select id="qualityPeriod" class="px-4 py-2 border border-slate-200 rounded-lg text-sm focus:border-forest-500 focus:outline-none bg-white">
                                <option value="today">Hoje</option>
                                <option value="week">Esta Semana</option>
                                <option value="month">Este Mês</option>
                            </select>
                            <button onclick="showAddQualityModal()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:shadow-lg transition-all font-semibold text-sm">
                                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                + Qualidade
                            </button>
                            <button onclick="exportQualityReport()" class="px-4 py-2 bg-forest-600 text-white rounded-lg hover:shadow-lg transition-all font-semibold text-sm">
                                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                Relatário
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Quality Indicators -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="data-card rounded-2xl p-4">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="font-semibold text-slate-900">Gordura</h4>
                            <span class="text-lg font-bold text-slate-900" id="fatContent">-</span>
                        </div>
                        <div class="quality-indicator">
                            <div class="quality-bar" id="fatQualityBar" style="width: 0%"></div>
                        </div>
                        <p class="text-xs text-slate-500 mt-2" id="fatQualityText">-</p>
                    </div>

                    <div class="data-card rounded-2xl p-4">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="font-semibold text-slate-900">Proteína</h4>
                            <span class="text-lg font-bold text-slate-900" id="proteinContent">-</span>
                        </div>
                        <div class="quality-indicator">
                            <div class="quality-bar" id="proteinQualityBar" style="width: 0%"></div>
                        </div>
                        <p class="text-xs text-slate-500 mt-2" id="proteinQualityText">-</p>
                    </div>

                    <div class="data-card rounded-2xl p-4">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="font-semibold text-slate-900">CCS</h4>
                            <span class="text-lg font-bold text-slate-900" id="sccCount">-</span>
                        </div>
                        <div class="quality-indicator">
                            <div class="quality-bar" id="sccQualityBar" style="width: 0%"></div>
                        </div>
                        <p class="text-xs text-slate-500 mt-2" id="sccQualityText">-</p>
                    </div>

                    <div class="data-card rounded-2xl p-4">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="font-semibold text-slate-900">CBT</h4>
                            <span class="text-lg font-bold text-slate-900" id="tbc">-</span>
                        </div>
                        <div class="quality-indicator">
                            <div class="quality-bar" id="tbcQualityBar" style="width: 0%"></div>
                        </div>
                        <p class="text-xs text-slate-500 mt-2" id="tbcQualityText">-</p>
                    </div>
                </div>
                <!-- Quality Trends -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="data-card rounded-2xl p-6">
                        <h3 class="text-lg font-bold text-slate-900 mb-4">Tendência de Qualidade</h3>
                        <div class="chart-container">
                            <canvas id="qualityTrendChart"></canvas>
                        </div>
                    </div>

                    <div class="data-card rounded-2xl p-6">
                        <h3 class="text-lg font-bold text-slate-900 mb-4">Distribuiçãoo de Qualidade</h3>
                        <div class="chart-container">
                            <canvas id="qualityDistributionChart"></canvas>
                        </div>
                    </div>

                    <div class="data-card rounded-2xl p-6">
                        <h3 class="text-lg font-bold text-slate-900 mb-4">Qualidade Semanal</h3>
                        <div class="chart-container">
                            <canvas id="qualityChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Quality Tests -->
                <div class="data-card rounded-2xl p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-slate-900">Testes de Qualidade</h3>
                        <button onclick="addQualityTest()" class="px-4 py-2 bg-forest-600 text-white rounded-lg hover:shadow-lg transition-all font-semibold text-sm">
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Novo Teste
                        </button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th class="text-left py-3 px-4 font-semibold text-slate-900">Data</th>
                                    <th class="text-left py-3 px-4 font-semibold text-slate-900">Gordura</th>
                                    <th class="text-left py-3 px-4 font-semibold text-slate-900">Proteína</th>
                                    <th class="text-left py-3 px-4 font-semibold text-slate-900">CCS</th>
                                    <th class="text-left py-3 px-4 font-semibold text-slate-900">CBT</th>
                                    <th class="text-left py-3 px-4 font-semibold text-slate-900">Laboratário</th>
                                    <th class="text-left py-3 px-4 font-semibold text-slate-900">Observações</th>
                                    <th class="text-left py-3 px-4 font-semibold text-slate-900">Status</th>
                                    <th class="text-left py-3 px-4 font-semibold text-slate-900">Açãoes</th>
                                </tr>
                            </thead>
                            <tbody id="qualityTests">
                                <tr>
                                    <td colspan="9" class="text-center py-8 text-gray-500">
                                        Nenhum teste encontrado
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Financial Records Tab -->
        <div id="payments-tab" class="tab-content hidden">
            <div class="space-y-6">
                <!-- Financial Records Header -->
                <div class="data-card rounded-2xl p-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-6 gap-4">
                        <div>
                            <h2 class="text-xl md:text-2xl font-bold text-slate-900 mb-1">Controle de Vendas</h2>
                <p class="text-slate-600 text-sm">Gerencie vendas de leite e recebimentos</p>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                            <select id="paymentPeriod" class="px-4 py-2 border border-slate-200 rounded-lg text-sm focus:border-forest-500 focus:outline-none bg-white">
                                <option value="month">Este Mês</option>
                                <option value="quarter">Trimestre</option>
                                <option value="year">Ano</option>
                            </select>
                            <button onclick="generatePaymentsReport()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:shadow-lg transition-all font-semibold text-sm">
                                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                Relatário
                            </button>
                            <button onclick="addPayment()" class="px-4 py-2 bg-forest-600 text-white rounded-lg hover:shadow-lg transition-all font-semibold text-sm">
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Nova Venda
                    </button>
                        </div>
                    </div>
                </div>

                <!-- Payment Summary -->
                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div class="data-card rounded-2xl p-6 text-center">
                        <div class="w-16 h-16 bg-green-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="text-2xl font-bold text-slate-900 mb-2" id="paidAmount">-</div>
                        <div class="text-sm text-slate-600 font-medium">Vendas Realizadas</div>
                        <div class="text-xs text-green-600 font-semibold mt-2">Este Mês</div>
                    </div>

                    <div class="data-card rounded-2xl p-6 text-center">
                        <div class="w-16 h-16 bg-yellow-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="text-2xl font-bold text-slate-900 mb-2" id="pendingAmount">-</div>
                        <div class="text-sm text-slate-600 font-medium">Vendas Pendentes</div>
                        <div class="text-xs text-yellow-600 font-semibold mt-2">A Vencer</div>
                    </div>

                    <div class="data-card rounded-2xl p-6 text-center">
                        <div class="w-16 h-16 bg-red-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                        </div>
                        <div class="text-2xl font-bold text-slate-900 mb-2" id="overdueAmount">-</div>
                        <div class="text-sm text-slate-600 font-medium">Vendas Atrasadas</div>
                        <div class="text-xs text-red-600 font-semibold mt-2">Vencidos</div>
                    </div>
                </div>

                <!-- Payment Chart -->
                <div class="data-card rounded-2xl p-6">
                    <h3 class="text-lg font-bold text-slate-900 mb-4">Fluxo de Vendas</h3>
                    <div class="chart-container">
                        <canvas id="paymentsChart"></canvas>
                    </div>
                </div>
                <!-- Payments List -->
                <div class="data-card rounded-2xl p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-slate-900">Lista de Vendas</h3>
                        <div class="flex gap-2">
                            <select id="paymentFilter" class="px-3 py-2 border border-slate-200 rounded-lg text-sm focus:border-forest-500 focus:outline-none bg-white">
                                <option value="all">Todos</option>
                                <option value="paid">Pagos</option>
                                <option value="pending">Pendentes</option>
                                <option value="overdue">Atrasados</option>
                            </select>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th class="text-left py-3 px-4 font-semibold text-slate-900">Beneficiário</th>
                                    <th class="text-left py-3 px-4 font-semibold text-slate-900">Tipo</th>
                                    <th class="text-left py-3 px-4 font-semibold text-slate-900">Valor</th>
                                    <th class="text-left py-3 px-4 font-semibold text-slate-900">Vencimento</th>
                                    <th class="text-left py-3 px-4 font-semibold text-slate-900">Status</th>
                                    <th class="text-left py-3 px-4 font-semibold text-slate-900">Açãoes</th>
                                </tr>
                            </thead>
                            <tbody id="paymentsList">
                                <tr>
                                    <td colspan="6" class="text-center py-8 text-gray-500">
                                        Nenhuma venda encontrada
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Users Tab -->
        <div id="users-tab" class="tab-content hidden users-section">
            <div class="space-y-6 sm:space-y-8">
                <!-- Users Header -->
                <div class="data-card rounded-2xl p-4 sm:p-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-4 sm:mb-6 gap-3 sm:gap-4">
                        <div>
                            <h2 class="text-lg sm:text-xl md:text-2xl font-bold text-slate-900 mb-1">Gestão de Usuários</h2>
                            <p class="text-slate-600 text-xs sm:text-sm">Gerencie funcionários e suas permissões</p>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-2 sm:gap-3 w-full sm:w-auto">
                            <select id="userFilter" class="px-3 sm:px-4 py-2 border border-slate-200 rounded-lg text-sm focus:border-forest-500 focus:outline-none bg-white">
                                <option value="all">Todos os usuários</option>
                                <option value="funcionario">Funcionários</option>
                                <option value="veterinario">Veterinários</option>
                                <option value="proprietario">Proprietários</option>
                            </select>
                            <button onclick="addUser()" class="px-3 sm:px-4 py-2 bg-forest-600 text-white rounded-lg hover:shadow-lg transition-all font-semibold text-sm">
                                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Adicionar Usuário
                            </button>
                        </div>
                    </div>
                </div>

                <!-- User Stats -->
                <div class="grid grid-cols-2 gap-4 sm:gap-6">
                    <div class="data-card rounded-2xl p-4 sm:p-6 text-center metric-card-responsive">
                        <div class="w-10 h-10 sm:w-12 sm:h-12 bg-blue-500 rounded-xl flex items-center justify-center mx-auto mb-2 sm:mb-3 shadow-lg">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                        <div class="text-lg sm:text-xl font-bold text-slate-900 mb-1" id="totalUsers">--</div>
                        <div class="text-xs text-slate-500 font-medium">Total</div>
                        <div class="text-xs text-slate-600 font-semibold mt-1">Usuários</div>
                    </div>
                    
                    <div class="data-card rounded-2xl p-4 sm:p-6 text-center metric-card-responsive">
                        <div class="w-10 h-10 sm:w-12 sm:h-12 bg-green-500 rounded-xl flex items-center justify-center mx-auto mb-2 sm:mb-3 shadow-lg">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                        <div class="text-lg sm:text-xl font-bold text-slate-900 mb-1" id="employeesCount">--</div>
                        <div class="text-xs text-slate-500 font-medium">Funcionários</div>
                        <div class="text-xs text-slate-600 font-semibold mt-1">Ativos</div>
                    </div>
                    
                    <div class="data-card rounded-2xl p-4 sm:p-6 text-center metric-card-responsive">
                        <div class="w-10 h-10 sm:w-12 sm:h-12 bg-purple-500 rounded-xl flex items-center justify-center mx-auto mb-2 sm:mb-3 shadow-lg">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                            </svg>
                        </div>
                        <div class="text-lg sm:text-xl font-bold text-slate-900 mb-1" id="veterinariansCount">--</div>
                        <div class="text-xs text-slate-500 font-medium">Veterinários</div>
                        <div class="text-xs text-slate-600 font-semibold mt-1">Ativos</div>
                    </div>
                    
                    <div class="data-card rounded-2xl p-4 sm:p-6 text-center metric-card-responsive">
                        <div class="w-10 h-10 sm:w-12 sm:h-12 bg-orange-500 rounded-xl flex items-center justify-center mx-auto mb-2 sm:mb-3 shadow-lg">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                            </svg>
                        </div>
                        <div class="text-lg sm:text-xl font-bold text-slate-900 mb-1" id="managersCount">--</div>
                        <div class="text-xs text-slate-500 font-medium">Gerentes</div>
                        <div class="text-xs text-slate-600 font-semibold mt-1">Ativos</div>
                    </div>
                </div>

                <!-- Users List -->
                <div class="data-card rounded-2xl p-4 sm:p-6">
                    <h3 class="text-base sm:text-lg font-bold text-slate-900 mb-4 sm:mb-6">Lista de Usuários</h3>
                    <div class="space-y-4 sm:space-y-6" id="usersList">
                        <div class="text-center py-8 sm:py-12">
                            <div class="w-16 h-16 sm:w-20 sm:h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4 sm:mb-6">
                                <svg class="w-8 h-8 sm:w-10 sm:h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                            </div>
                            <h3 class="text-base sm:text-lg font-semibold text-gray-900mb-2">Nenhum Usuário Cadastrado</h3>
                            <p class="text-gray-600mb-3 sm:mb-4 text-sm">Adicione usuários para gerenciar sua equipe</p>
                            <button onclick="addUser()" class="px-4 sm:px-6 py-2 sm:py-3 gradient-forest text-white font-semibold rounded-xl hover:shadow-lg transition-all text-sm">
                                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Adicionar Primeiro Usuário
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Enhanced Mobile Navigation -->
    <nav class="fixed bottom-0 left-0 right-0 md:hidden mobile-nav-enhanced shadow-2xl z-40">
        <div class="grid grid-cols-6 gap-1 p-2">
            <button class="mobile-nav-item active flex flex-col items-center py-2 px-1 transition-all" data-tab="dashboard">
                <svg class="w-4 h-4 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V5a2 2 0 012-2h4a2 2 0 012 2v2"></path>
                </svg>
                <span class="text-xs font-semibold">Dashboard</span>
            </button>
            <button class="mobile-nav-item flex flex-col items-center py-2 px-1 transition-all" data-tab="volume">
                <svg class="w-4 h-4 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
                            </svg>
                <span class="text-xs font-semibold">Volume</span>
            </button>
            <button class="mobile-nav-item flex flex-col items-center py-2 px-1 transition-all" data-tab="quality">
                <svg class="w-4 h-4 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span class="text-xs font-semibold">Qualidade</span>
            </button>
            <button class="mobile-nav-item flex flex-col items-center py-2 px-1 transition-all" data-tab="payments">
                <svg class="w-4 h-4 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                </svg>
                <span class="text-xs font-semibold">Financeiro</span>
            </button>
            <button class="mobile-nav-item flex flex-col items-center py-2 px-1 transition-all" data-tab="users">
                <svg class="w-4 h-4 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"></path>
                </svg>
                <span class="text-xs font-semibold">Usuários</span>
            </button>
            <button class="mobile-nav-item flex flex-col items-center py-2 px-1 transition-all" onclick="openMoreModal()">
                <svg class="w-4 h-4 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.5 12.5c0 .828-.672 1.5-1.5 1.5s-1.5-.672-1.5-1.5.672-1.5 1.5-1.5 1.5.672 1.5 1.5zM18.5 12.5c0 .828-.672 1.5-1.5 1.5s-1.5-.672-1.5-1.5.672-1.5 1.5-1.5 1.5.672 1.5 1.5zM12 20c-2.5 0-4.5-2-4.5-4.5S9.5 11 12 11s4.5 2 4.5 4.5S14.5 20 12 20zM12 8c-1.5 0-2.5-1-2.5-2.5S10.5 3 12 3s2.5 1 2.5 2.5S13.5 8 12 8z"/>
                </svg>
                <span class="text-xs font-semibold">Mais</span>
            </button>
        </div>
    </nav>
    
    <!-- Animals Management Tab -->
    <div id="animals-tab" class="tab-content hidden animals-section">
        <div class="space-y-6 sm:space-y-8">
            <!-- Animals Header -->
            <div class="data-card rounded-2xl p-4 sm:p-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-4 sm:mb-6 gap-3 sm:gap-4">
                    <div>
                        <h2 class="text-lg sm:text-xl md:text-2xl font-bold text-slate-900 mb-1">Gestão de Animais</h2>
                        <p class="text-slate-600 text-xs sm:text-sm">Gerencie o rebanho da Lagoa do Mato</p>
                    </div>
                    <div class="flex flex-col sm:flex-row gap-2 sm:gap-3 w-full sm:w-auto">
                        <button onclick="openAddAnimalModal()" class="px-3 sm:px-4 py-2 bg-forest-600 text-white rounded-lg hover:shadow-lg transition-all font-semibold text-sm">
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Adicionar Animal
                        </button>
                    </div>
                </div>
            </div>

            <!-- Animal Stats -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 sm:gap-6">
                <div class="data-card rounded-2xl p-4 sm:p-6 text-center metric-card-responsive">
                    <div class="w-10 h-10 sm:w-12 sm:h-12 bg-green-500 rounded-xl flex items-center justify-center mx-auto mb-2 sm:mb-3 shadow-lg">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                        </svg>
                    </div>
                    <div class="text-lg sm:text-xl font-bold text-slate-900 mb-1" id="totalAnimals">--</div>
                    <div class="text-xs text-slate-500 font-medium">Total</div>
                    <div class="text-xs text-slate-600 font-semibold mt-1">Animais</div>
                </div>
                
                <div class="data-card rounded-2xl p-4 sm:p-6 text-center metric-card-responsive">
                    <div class="w-10 h-10 sm:w-12 sm:h-12 bg-blue-500 rounded-xl flex items-center justify-center mx-auto mb-2 sm:mb-3 shadow-lg">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="text-lg sm:text-xl font-bold text-slate-900 mb-1" id="healthyAnimals">--</div>
                    <div class="text-xs text-slate-500 font-medium">Saudáveis</div>
                    <div class="text-xs text-slate-600 font-semibold mt-1">Animais</div>
                </div>
                
                <div class="data-card rounded-2xl p-4 sm:p-6 text-center metric-card-responsive">
                    <div class="w-10 h-10 sm:w-12 sm:h-12 bg-yellow-500 rounded-xl flex items-center justify-center mx-auto mb-2 sm:mb-3 shadow-lg">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                    </div>
                    <div class="text-lg sm:text-xl font-bold text-slate-900 mb-1" id="warningAnimals">--</div>
                    <div class="text-xs text-slate-500 font-medium">Em Tratamento</div>
                    <div class="text-xs text-slate-600 font-semibold mt-1">Animais</div>
                </div>
                
                <div class="data-card rounded-2xl p-4 sm:p-6 text-center metric-card-responsive">
                    <div class="w-10 h-10 sm:w-12 sm:h-12 bg-red-500 rounded-xl flex items-center justify-center mx-auto mb-2 sm:mb-3 shadow-lg">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="text-lg sm:text-xl font-bold text-slate-900 mb-1" id="criticalAnimals">--</div>
                    <div class="text-xs text-slate-500 font-medium">Doentes</div>
                    <div class="text-xs text-slate-600 font-semibold mt-1">Animais</div>
                </div>
            </div>

            <!-- Animals List -->
            <div class="data-card rounded-2xl p-4 sm:p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-slate-900">Lista de Animais</h3>
                    <div class="flex gap-2">
                        <button onclick="openAddTreatmentModal()" class="px-3 py-1.5 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 transition-all">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Tratamento
                        </button>
                        <button onclick="openAddInseminationModal()" class="px-3 py-1.5 bg-purple-600 text-white rounded-lg text-sm hover:bg-purple-700 transition-all">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Inseminaçãoo
                        </button>
                    </div>
                </div>
                <div id="animalsList" class="space-y-3">
                    <div class="text-center py-8 text-gray-500">
                        <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                        </svg>
                        <p>Carregando animais...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Reports Tab -->
    <div id="reports-tab" class="tab-content hidden">
        <div class="px-4 sm:px-6 lg:px-8 py-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Configurações de Relatórios -->
            <div class="bg-gradient-to-br from-white to-slate-50 rounded-2xl p-8 border border-slate-200 shadow-lg">
                <!-- Header da Seção -->
                <div class="flex items-center mb-6">
                    <div class="p-3 bg-gradient-to-br from-green-500 to-green-600 rounded-xl mr-4">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        </div>
                    <div>
                        <h3 class="text-xl font-bold text-slate-900">Configurações de Relatórios</h3>
                        <p class="text-sm text-slate-600">Personalize a aparência dos seus relatórios</p>
                    </div>
                    </div>
                    
                <div class="space-y-6">
                    <!-- Nome da Fazenda -->
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-slate-700 flex items-center">
                            <svg class="w-4 h-4 mr-2 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V5a2 2 0 012-2h4a2 2 0 012 2v2"></path>
                            </svg>
                            Nome da Fazenda
                        </label>
                        <input type="text" id="reportFarmNameTab" class="w-full px-4 py-3 border border-slate-300 rounded-xl focus:border-green-500 focus:ring-2 focus:ring-green-100 focus:outline-none bg-whiteshadow-sm transition-all" placeholder="Digite o nome da fazenda">
                </div>

                    <!-- Upload da Logo da Fazenda -->
                    <div class="space-y-3">
                        <label class="block text-sm font-semibold text-slate-700 flex items-center">
                            <svg class="w-4 h-4 mr-2 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            Logo da Fazenda
                        </label>
                        
                        <div class="bg-whiterounded-xl p-4 border border-slate-200 shadow-sm">
                            <div class="flex items-center space-x-4">
                                <div class="relative">
                                    <!-- Preview da logo -->
                                    <div id="farmLogoPreviewTab" class="w-20 h-20 border-2 border-dashed border-green-300 rounded-xl flex items-center justify-center bg-green-50 hidden">
                                        <img id="farmLogoImageTab" src="" alt="Logo da Fazenda" class="w-full h-full object-cover rounded-xl">
                        </div>
                                    <!-- Placeholder quando não há logo -->
                                    <div id="farmLogoPlaceholderTab" class="w-20 h-20 border-2 border-dashed border-slate-300 rounded-xl flex items-center justify-center bg-slate-50">
                                        <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                    </div>
                </div>
                                <div class="flex-1 space-y-2">
                                    <div class="flex flex-wrap gap-2">
                                        <button onclick="document.getElementById('farmLogoUploadTab').click()" class="flex items-center px-4 py-2 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-medium rounded-lg transition-all text-sm shadow-md hover:shadow-lg">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                            </svg>
                                            Escolher Logo
                                        </button>
                                        <button id="removeFarmLogoTab" onclick="removeFarmLogoTab()" class="flex items-center px-4 py-2 bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white font-medium rounded-lg transition-all text-sm shadow-md hover:shadow-lg hidden">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                            Remover
                                        </button>
                    </div>
                                    <input type="file" id="farmLogoUploadTab" accept="image/*" class="hidden" onchange="handleFarmLogoUploadTab(event)">
                                    <p class="text-xs text-slate-500 flex items-center">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        PNG, JPG, JPEG (máx. 2MB) - Preferência: formato quadrado
                                    </p>
                </div>
                            </div>
                    </div>
                </div>

                    <!-- Botão Salvar -->
                    <div class="pt-4 border-t border-slate-200">
                        <button onclick="saveReportSettingsTab()" class="w-full sm:w-auto flex items-center justify-center px-8 py-3 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-0.5">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Salvar Configurações
                        </button>
                </div>
            </div>
        </div>

            <!-- Estatísticas Rápidas -->
            <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-2xl p-4 sm:p-6 lg:p-8 border border-green-200 shadow-lg relative overflow-hidden">
                <!-- Background Pattern -->
                <div class="absolute inset-0 opacity-5">
                    <div class="absolute inset-0 bg-gradient-to-br from-green-500 to-emerald-600"></div>
                    <svg class="absolute top-0 right-0 w-20 sm:w-24 lg:w-32 h-20 sm:h-24 lg:h-32 transform translate-x-4 sm:translate-x-6 lg:translate-x-8 -translate-y-4 sm:-translate-y-6 lg:-translate-y-8" fill="currentColor" viewBox="0 0 100 100">
                        <circle cx="50" cy="50" r="35"/>
                    </svg>
                    <svg class="absolute bottom-0 left-0 w-16 sm:w-20 lg:w-24 h-16 sm:h-20 lg:h-24 transform -translate-x-2 sm:-translate-x-3 lg:-translate-x-4 translate-y-2 sm:translate-y-3 lg:translate-y-4" fill="currentColor" viewBox="0 0 100 100">
                        <rect x="30" y="30" width="40" height="40" rx="8"/>
                    </svg>
                        </div>
                
                <!-- Header da Seção -->
                <div class="relative flex flex-col sm:flex-row items-start sm:items-center mb-4 sm:mb-6">
                    <div class="p-2 sm:p-3 bg-gradient-to-br from-green-500 to-emerald-600 rounded-lg sm:rounded-xl mr-0 sm:mr-4 mb-3 sm:mb-0">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                        </div>
                    <div>
                        <h3 class="text-lg sm:text-xl font-bold text-slate-900">Resumo de Produção</h3>
                        <p class="text-xs sm:text-sm text-slate-600">Indicadores em tempo real</p>
                    </div>
                </div>

                <div class="relative space-y-3 sm:space-y-4">
                    <!-- Produção de Hoje - Destaque -->
                    <div class="bg-white/80 backdrop-blur-sm rounded-xl p-3 sm:p-4 border border-white/50 shadow-sm hover:shadow-md transition-all duration-300">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-3 sm:space-y-0">
                            <div class="flex items-center space-x-2 sm:space-x-3">
                                <div class="p-1.5 sm:p-2 bg-gradient-to-br from-green-400 to-green-500 rounded-lg flex-shrink-0">
                                    <svg class="w-4 h-4 sm:w-5 sm:h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                        </div>
                                <div>
                                    <p class="text-sm sm:text-base font-medium text-slate-600">Produção de Hoje</p>
                                    <p class="text-xs text-slate-500">Volume registrado</p>
                        </div>
                    </div>
                            <div class="text-left sm:text-right">
                                <p class="text-xl sm:text-2xl font-bold text-green-600" id="reportTodayVolume">0.0 L</p>
                                <p class="text-xs text-slate-500">litros</p>
                        </div>
                        </div>
                    </div>

                    <!-- Outras Métricas -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 sm:gap-3">
                        <div class="bg-white/60 backdrop-blur-sm rounded-lg p-2 sm:p-3 border border-white/30 hover:bg-white/80 transition-all duration-300">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-2 sm:space-y-0">
                                <div class="flex items-center space-x-2">
                                    <div class="p-1 sm:p-1.5 bg-blue-100 rounded-md flex-shrink-0">
                                        <svg class="w-3 h-3 sm:w-4 sm:h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path>
                                        </svg>
                        </div>
                                    <span class="text-xs sm:text-sm font-medium text-slate-700">Média Semanal</span>
                        </div>
                                <span class="text-sm sm:text-base font-bold text-blue-600" id="reportWeekAverage">0.0 L</span>
                            </div>
                    </div>

                        <div class="bg-white/60 backdrop-blur-sm rounded-lg p-2 sm:p-3 border border-white/30 hover:bg-white/80 transition-all duration-300">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-2 sm:space-y-0">
                                <div class="flex items-center space-x-2">
                                    <div class="p-1 sm:p-1.5 bg-emerald-100 rounded-md flex-shrink-0">
                                        <svg class="w-3 h-3 sm:w-4 sm:h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                        </div>
                                    <span class="text-xs sm:text-sm font-medium text-slate-700">Total do Mês</span>
                        </div>
                                <span class="text-sm sm:text-base font-bold text-emerald-600" id="reportMonthTotal">0.0 L</span>
                            </div>
                    </div>

                        <div class="bg-white/60 backdrop-blur-sm rounded-lg p-2 sm:p-3 border border-white/30 hover:bg-white/80 transition-all duration-300">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-2 sm:space-y-0">
                                <div class="flex items-center space-x-2">
                                    <div class="p-1 sm:p-1.5 bg-orange-100 rounded-md flex-shrink-0">
                                        <svg class="w-3 h-3 sm:w-4 sm:h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                        </div>
                                    <span class="text-xs sm:text-sm font-medium text-slate-700">Registros</span>
                    </div>
                                <span class="text-sm sm:text-base font-bold text-orange-600" id="reportMonthRecords">0</span>
                        </div>
                    </div>

                        <div class="bg-white/60 backdrop-blur-sm rounded-lg p-2 sm:p-3 border border-white/30 hover:bg-white/80 transition-all duration-300">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-2 sm:space-y-0">
                                <div class="flex items-center space-x-2">
                                    <div class="p-1 sm:p-1.5 bg-teal-100 rounded-md flex-shrink-0">
                                        <svg class="w-3 h-3 sm:w-4 sm:h-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                        </svg>
                        </div>
                                    <span class="text-xs sm:text-sm font-medium text-slate-700">Funcionários</span>
                    </div>
                                <span class="text-sm sm:text-base font-bold text-teal-600" id="reportActiveEmployees">0</span>
                </div>
                    </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Exportar Relatórios -->
        <div class="bg-gradient-to-br from-white to-slate-50 rounded-2xl p-8 border border-slate-200 shadow-lg">
            <!-- Header da Seção -->
            <div class="flex items-center mb-8">
                <div class="p-3 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl mr-4">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.207A1 1 0 013 6.5V4z"></path>
                                </svg>
                        </div>
                <div>
                    <h3 class="text-xl font-bold text-slate-900">Exportar Relatórios</h3>
                    <p class="text-sm text-slate-600">Gere relatórios personalizados da produção</p>
                    </div>
                </div>

            <!-- Filtros de Data - Design Melhorado -->
            <div class="bg-whiterounded-xl p-6 border border-slate-200 shadow-sm mb-8">
                <h4 class="text-lg font-semibold text-slate-800 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.207A1 1 0 013 6.5V4z"></path>
                    </svg>
                    Filtros de Relatário
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-slate-700">Data Inicial</label>
                        <div class="relative">
                            <input type="date" id="reportStartDate" class="w-full px-4 py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all bg-whiteshadow-sm">
                            <svg class="absolute right-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-slate-700">Data Final</label>
                        <div class="relative">
                            <input type="date" id="reportEndDate" class="w-full px-4 py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all bg-whiteshadow-sm">
                            <svg class="absolute right-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-slate-700">Funcionário</label>
                        <div class="relative">
                            <select id="reportEmployee" class="w-full px-4 py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all bg-whiteshadow-sm appearance-none">
                                <option value="">Todos os funcionários</option>
                            </select>
                            <svg class="absolute right-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                    </div>
                    </div>
                </div>

            <!-- Botões de Exportação - Design Responsivo -->
            <!-- Botões de Exportação - Design Simples -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
                <!-- PDF -->
                <button onclick="exportPDFReport()" class="bg-red-600 hover:bg-red-700 text-white rounded-lg p-4 transition-colors duration-200 shadow-md">
                    <div class="flex flex-col items-center space-y-2">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <div class="text-center">
                            <h4 class="font-bold">Exportar PDF</h4>
                            <p class="text-sm opacity-90">Relatário completo formatado</p>
                        </div>
                    </div>
                </button>
                
                <!-- Excel -->
                <button onclick="exportExcelReport()" class="bg-green-600 hover:bg-green-700 text-white rounded-lg p-4 transition-colors duration-200 shadow-md">
                    <div class="flex flex-col items-center space-y-2">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <div class="text-center">
                            <h4 class="font-bold">Exportar Excel</h4>
                            <p class="text-sm opacity-90">Planilha para análise</p>
                        </div>
                    </div>
                </button>
                
                <!-- Prévia -->
                <button onclick="previewReportTab()" class="bg-purple-600 hover:bg-purple-700 text-white rounded-lg p-4 transition-colors duration-200 shadow-md">
                    <div class="flex flex-col items-center space-y-2">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                        <div class="text-center">
                            <h4 class="font-bold">Visualizar Prévia</h4>
                            <p class="text-sm opacity-90">Amostra do relatório</p>
                        </div>
                    </div>
                </button>
                        </div>
                    </div>
                </div>
                
                <!-- Espaço adicional para rolagem -->
                <div class="h-32"></div>
            </div>
        </div>
    <!-- Modal de Adicionar Usuário -->
    <div id="addUserModal" class="fullscreen-modal" style="display: none !important; visibility: hidden !important; opacity: 0 !important; z-index: -1 !important; pointer-events: none !important;">
        <div class="modal-content">
            <!-- Header do Modal -->
            <div class="sticky top-0 bg-whiteborder-b border-gray-200px-6 py-4 z-10">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-bold text-gray-900">Adicionar Novo Usuário</h2>
                    <button onclick="closeAddUserModal()" class="p-2 hover:bg-gray-100rounded-lg transition-all">
                        <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            
            <!-- Conteúdo do Modal -->
            <div class="p-6 space-y-6">
                <!-- Formulário de Adicionar Usuário -->
                <div class="bg-whiterounded-2xl p-6 shadow-sm border border-gray-200">
                    <h4 class="text-lg font-semibold text-gray-900mb-4">Informações do Usuário</h4>
                    <form id="addUserFormModal" class="space-y-4">
                        <div class="form-floating">
                            <input type="text" id="userName" name="name" class="w-full px-3 py-4 border border-slate-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none" placeholder=" " required>
                            <label for="userName" class="text-slate-600">Nome Completo *</label>
                        </div>
                                                
                        <!-- Email Preview -->
                        <div class="bg-gray-50 border border-gray-200rounded-xl p-4">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-gray-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V5a2 2 0 00-2-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <div>
                                    <h5 class="text-sm font-semibold text-gray-900mb-1">Email será gerado automaticamente:</h5>
                                    <p class="text-sm text-gray-600" id="emailPreview">Digite o nome para ver o email</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-floating">
                                <input type="tel" id="userWhatsapp" name="whatsapp" class="w-full px-3 py-4 border border-slate-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none" placeholder=" " required>
                                <label for="userWhatsapp" class="text-slate-600">WhatsApp *</label>
                            </div>
                            <div class="form-floating">
                                <select id="userRole" name="role" class="w-full px-3 py-4 border border-slate-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none" required onchange="togglePhotoSection();">
                                    <option value="">Selecione o cargo</option>
                                    <option value="funcionario">Funcionário</option>
                                    <option value="veterinario">Veterinário</option>
                                </select>
                                <label for="userRole" class="text-slate-600">Cargo *</label>
                            </div>
                        </div>
                        
                        <!-- Seção de Foto de Perfil para Add User (funcionários e veterinários) -->
                        <div id="addPhotoSection" class="hidden" style="display: none !important;">
                            <label class="block text-sm font-semibold text-gray-800 mb-2">Foto de Perfil (Opcional)</label>
                            <div class="flex items-center space-x-4">
                                <div class="relative">
                                    <img id="profilePreview" src="" alt="Preview da foto" class="w-16 h-16 rounded-xl object-cover hidden" style="display: none;">
                                    <div id="profilePlaceholder" class="w-16 h-16 bg-gray-100 rounded-xl flex items-center justify-center">
                                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div>
                                    <button type="button" onclick="addPhotoToNewUser()" class="px-4 py-2 bg-forest-600 hover:bg-forest-700 text-white font-medium rounded-lg transition-all text-sm">
                                        Escolher Foto
                                    </button>
                                    <input type="file" id="profilePhotoInput" name="profilePhoto" accept="image/*" class="hidden" onchange="previewProfilePhoto(this)">
                                    <p class="text-xs text-gray-500 mt-1">Formatos: JPG, PNG (máx. 2MB) - Opcional</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-floating relative">
                            <input type="password" id="userPassword" name="password" class="w-full px-3 py-4 pr-12 border border-slate-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none" placeholder=" " required>
                            <label for="userPassword" class="text-slate-600">Senha *</label>
                            <button type="button" onclick="toggleUserPasswordVisibility('userPassword', 'userPasswordToggle')" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700focus:outline-none" id="userPasswordToggle">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </button>
                        </div>
                        
                        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-blue-500 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <div>
                                    <h5 class="text-sm font-semibold text-blue-900 mb-1">Informações Importantes</h5>
                                    <p class="text-sm text-blue-700">• Como gerente, você pode criar perfis de funcionários e veterinários</p>
                                    <p class="text-sm text-blue-700">• O usuário receberá as credenciais por WhatsApp</p>
                                    <p class="text-sm text-blue-700">• O email será gerado automaticamente baseado no nome da fazenda</p>
                                    <p class="text-sm text-blue-700">• Recomenda-se que o usuário altere a senha no primeiro acesso</p>
                                    <p class="text-sm text-blue-700">• O acesso será ativado imediatamente após a criação</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex justify-end space-x-3 pt-4">
                            <button type="button" onclick="closeAddUserModal()" class="px-6 py-3 border border-gray-300text-gray-700font-semibold rounded-xl hover:bg-gray-50transition-all">
                                Cancelar
                            </button>
                            <button type="submit" id="createUserBtn" class="px-6 py-3 gradient-forest text-white font-semibold rounded-xl hover:shadow-lg transition-all">
                                <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Criar Usuário
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Editar Usuário -->
    <div id="editUserModal" class="fullscreen-modal">
        <div class="modal-content">
            <!-- Header do Modal -->
            <div class="sticky top-0 bg-whiteborder-b border-gray-200px-6 py-4 z-10">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-bold text-gray-900">Editar Usuário</h2>
                    <button onclick="closeEditUserModal()" class="p-2 hover:bg-gray-100rounded-lg transition-all">
                        <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            
            <!-- Conteúdo do Modal -->
            <div class="p-6 space-y-6">
                <!-- Formulário de Editar Usuário -->
                <div class="bg-whiterounded-2xl p-6 shadow-sm border border-gray-200">
                    <h4 class="text-lg font-semibold text-gray-900mb-4">Informações do Usuário</h4>
                    <form id="editUserFormModal" class="space-y-4">
                        <input type="hidden" id="editUserId" name="id">
                        
                        <div class="form-floating">
                            <input type="text" id="editUserName" name="name" class="w-full px-3 py-4 border border-slate-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none" placeholder=" " required>
                            <label for="editUserName" class="text-slate-600">Nome Completo *</label>
                        </div>
                                                
                        <div class="form-floating">
                            <input type="email" id="editUserEmail" name="email" class="w-full px-3 py-4 border border-slate-200 rounded-xl bg-gray-100 text-gray-500" placeholder=" " readonly>
                            <label for="editUserEmail" class="text-slate-600">Email (n�o pode ser alterado)</label>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-floating">
                                <input type="tel" id="editUserWhatsapp" name="whatsapp" class="w-full px-3 py-4 border border-slate-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none" placeholder=" " required>
                                <label for="editUserWhatsapp" class="text-slate-600">WhatsApp *</label>
                            </div>
                            <div class="form-floating">
                                <select id="editUserRole" name="role" class="w-full px-3 py-4 border border-slate-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none" required>
                                    <option value="">Selecione o cargo</option>
                                    <option value="funcionario">Funcionário</option>
                                    <option value="veterinario">Veterinário</option>
                                </select>
                                <label for="editUserRole" class="text-slate-600">Cargo *</label>
                            </div>
                        </div>
                        
                        <!-- Seção de Foto de Perfil para Edit User -->
                        <div id="editPhotoSection" class="hidden">
                            <label class="block text-sm font-semibold text-gray-800 mb-2">Foto de Perfil</label>
                            <div class="flex items-center space-x-4">
                                <div class="relative">
                                    <img id="editProfilePreview" src="" alt="Preview da foto" class="w-16 h-16 rounded-xl object-cover hidden">
                                    <div id="editProfilePlaceholder" class="w-16 h-16 bg-gray-100 rounded-xl flex items-center justify-center">
                                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div>
                                    <button type="button" onclick="addPhotoToEditUser()" class="px-4 py-2 bg-forest-600 hover:bg-forest-700 text-white font-medium rounded-lg transition-all text-sm">
                                        Alterar Foto
                                    </button>
                                    <input type="file" id="editProfilePhoto" name="profilePhoto" accept="image/*" class="hidden" onchange="previewEditProfilePhoto(this)">
                                    <p class="text-xs text-gray-500 mt-1">Formatos: JPG, PNG (máx. 2MB)</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-floating relative">
                            <input type="password" id="editUserPassword" name="password" class="w-full px-3 py-4 pr-12 border border-slate-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none" placeholder=" ">
                            <label for="editUserPassword" class="text-slate-600">Nova Senha (deixe em branco para manter a atual)</label>
                            <button type="button" onclick="toggleUserPasswordVisibility('editUserPassword', 'editUserPasswordToggle')" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700focus:outline-none" id="editUserPasswordToggle">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </button>
                        </div>
                        
                        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-blue-500 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <div>
                                    <h5 class="text-sm font-semibold text-blue-900 mb-1">Informações sobre Ediçãoo</h5>
                                    <p class="text-sm text-blue-700">• O email não pode ser alterado após a criação</p>
                                    <p class="text-sm text-blue-700">• Deixe o campo senha em branco para manter a senha atual</p>
                                    <p class="text-sm text-blue-700">• As alterações serão aplicadas imediatamente</p>
                                    <p class="text-sm text-blue-700">• O usuário será notificado sobre as mudan�as por WhatsApp</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex justify-end space-x-3 pt-4">
                            <button type="button" onclick="closeEditUserModal()" class="px-6 py-3 border border-gray-300text-gray-700font-semibold rounded-xl hover:bg-gray-50transition-all">
                                Cancelar
                            </button>
                            <button type="submit" class="px-6 py-3 gradient-forest text-white font-semibold rounded-xl hover:shadow-lg transition-all">
                                <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Salvar Alterações
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>



    <!-- Modal de Escolha de Foto Novo -->
    <div id="photoChoiceModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-[99999]" style="display: none;">
        <div class="bg-whiterounded-2xl p-6 max-w-sm w-full mx-4 shadow-xl">
            <div class="text-center mb-6">
                <h3 class="text-xl font-bold text-gray-800 mb-2">Adicionar Foto</h3>
                <p class="text-gray-600text-sm">Escolha como adicionar a foto do funcionário</p>
            </div> 
    
            <div class="space-y-3">
                <button onclick="selectFromGallery()" class="w-full p-4 bg-blue-500 hover:bg-blue-600 text-white rounded-xl transition-colors flex items-center justify-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"> 
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg> 
                    <span>Galeria</span>
                </button> 
                
                <button onclick="takePhoto()" class="w-full p-4 bg-green-500 hover:bg-green-600 text-white rounded-xl transition-colors flex items-center justify-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"> 
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0118.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg> 
                    <span>C�mera</span>
                </button> 
            </div> 
    
            <button onclick="closePhotoChoiceModal()" class="w-full mt-4 p-3 border border-gray-300text-gray-700rounded-xl hover:bg-gray-50transition-colors">
                Cancelar 
            </button> 
        </div> 
    </div>

    <!-- Modal da C�mera -->
    <div id="cameraModal" class="fixed inset-0 bg-black z-[10001] hidden">
        <div class="relative w-full h-full bg-black overflow-hidden">
            <!-- Header Minimalista -->
            <div class="absolute top-0 left-0 right-0 z-20 p-6">
                <div class="flex items-center justify-between">
                    <!-- Logo LacTech -->
                    <div class="flex items-center space-x-3">
                        <img src="assets/img/lactech-logo.png" alt="LacTech" class="w-8 h-8 rounded-lg">
                        <div class="text-white">
                            <div class="text-sm font-semibold">LacTech</div>
                            <div class="text-xs opacity-70">Verificaçãoo Facial</div>
                        </div>
                    </div>
                    
                    <!-- Botão Fechar -->
                    <button onclick="closeCamera()" class="p-2 bg-black/30 backdrop-blur-md rounded-full hover:bg-black/50 transition-all">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Video da C�mera -->
            <video id="cameraVideo" class="w-full h-full object-cover" autoplay playsinline></video>
            
            <!-- Overlay de Verificaçãoo Facial -->
            <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                <!-- C�rculo de Foco Principal -->
                <div id="focusCircle" class="relative">
                    <!-- C�rculo Externo Animado -->
                    <div class="w-80 h-80 border-2 border-white/40 rounded-full flex items-center justify-center animate-pulse">
                        <!-- C�rculo Interno -->
                        <div class="w-72 h-72 border-2 border-white/60 rounded-full flex items-center justify-center">
                            <!-- C�rculo de Foco -->
                            <div class="w-64 h-64 border-3 border-white/80 rounded-full flex items-center justify-center">
                                <!-- Indicador de Foco -->
                                <div id="focusIndicator" class="w-56 h-56 border-2 border-green-400 rounded-full flex items-center justify-center opacity-0 transition-opacity duration-500">
                                    <div class="w-48 h-48 border border-green-300 rounded-full"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Instruçãoes -->
                    <div class="absolute -bottom-16 left-1/2 transform -translate-x-1/2 text-center">
                        <div id="focusText" class="text-white text-lg font-medium mb-2">Posicione o rosto no centro</div>
                        <div id="focusTimer" class="text-white/70 text-sm hidden">Focando em <span id="timerCount">3</span>s...</div>
                    </div>
                </div>
            </div>

            <!-- Controles Minimalistas -->
            <div class="absolute bottom-0 left-0 right-0 z-20 p-8">
                <div class="flex items-center justify-center space-x-12">
                    <!-- Botão Trocar C�mera -->
                    <button onclick="switchCamera()" class="p-4 bg-black/30 backdrop-blur-md rounded-full hover:bg-black/50 transition-all">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                    </button>
                    
                    <!-- Botão de Captura -->
                    <button id="captureBtn" onclick="startFaceVerification()" class="p-6 bg-whiterounded-full hover:bg-gray-100transition-all shadow-2xl transform hover:scale-105">
                        <div class="w-20 h-20 border-4 border-gray-200rounded-full flex items-center justify-center">
                            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center">
                                <svg class="w-8 h-8 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                            </div>
                        </div>
                    </button>
                    
                    <!-- Botão Cancelar -->
                    <button onclick="closeCamera()" class="p-4 bg-black/30 backdrop-blur-md rounded-full hover:bg-black/50 transition-all">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Canvas para Captura -->
            <canvas id="cameraCanvas" class="hidden"></canvas>
            
            <!-- Tela de Carregamento da Foto -->
            <div id="photoProcessingScreen" class="absolute inset-0 bg-black bg-opacity-90 flex items-center justify-center z-30 hidden">
                <div class="text-center text-white">
                    <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-white mx-auto mb-4"></div>
                    <h3 class="text-xl font-semibold mb-2">Processando foto...</h3>
                    <p class="text-white/70">Aguarde enquanto processamos sua imagem</p>
                </div>
            </div>
        </div>
    </div>

<!-- Modal de Perfil do Gerente -->
    <div id="profileModal" class="fullscreen-modal hidden" style="display: none !important; visibility: hidden !important; opacity: 0 !important;">
        <div class="modal-content overflow-y-auto">
            <!-- Header do Modal -->
            <div id="profileModalHeader" class="sticky top-0 left-0 right-0 bg-whiteborder-b border-gray-200px-4 sm:px-6 py-4 z-20 transition-transform duration-300 ease-in-out -mx-4 sm:mx-0">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-bold text-gray-900">Perfil do Gerente</h2>
                    <button onclick="closeProfileModal()" class="p-2 hover:bg-gray-100rounded-lg transition-all">
                        <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            
            <!-- Conteúdo do Perfil -->
            <div class="p-4 sm:p-6 pb-8">
                <div class="flex items-center justify-between mb-6">
                                            <div class="flex items-center space-x-4">
                            <div class="relative">
                                <!-- Foto do usuário -->
                                <img id="modalProfilePhoto" src="" alt="Foto de Perfil" class="w-16 h-16 object-cover rounded-2xl shadow-lg hidden">
                                <!-- ícone padrão -->
                                <div id="modalProfileIcon" class="w-16 h-16 gradient-forest rounded-2xl flex items-center justify-center shadow-lg">
                                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                </div>
                            </div>
                        <div>
                            <h2 class="text-2xl font-bold text-slate-900" id="profileName">Carregando...</h2>
                            <p class="text-slate-600 text-base">Gerente</p>
                            <p class="text-sm text-slate-500" id="profileFarmName">Carregando...</p>
                        </div>
                    </div>
                </div>
                
                <!-- Informações Pessoais -->
                <div class="bg-whiterounded-2xl p-4 sm:p-6 border border-gray-200mt-4">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-lg font-semibold text-gray-900">Informações Pessoais</h4>
                        <button id="editProfileBtn" onclick="toggleProfileEdit()" class="px-4 py-2 text-sm bg-forest-600 hover:bg-forest-700 text-white font-medium rounded-lg transition-all">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                            Editar
                        </button>
                    </div>
                    
                    <!-- Modo Visualizaçãoo -->
                    <div id="profileViewMode" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-800 mb-1">Nome Completo</label>
                            <p class="text-gray-900font-semibold text-base" id="profileFullName">Carregando...</p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-800 mb-1">Email</label>
                            <p class="text-gray-900font-semibold text-base" id="profileEmail2">Carregando...</p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-800 mb-1">WhatsApp</label>
                            <p class="text-gray-900font-semibold text-base" id="profileWhatsApp">Carregando...</p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-800 mb-1">Cargo</label>
                            <p class="text-gray-900font-semibold text-base">Gerente</p>
                        </div>
                    </div>
                    
                    <!-- Modo Ediçãoo -->
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
                                    <input type="text" value="Gerente" class="w-full px-4 py-3 border border-slate-200 rounded-xl bg-gray-100 text-gray-500" readonly>
                                </div>
                            </div>
                            
                            </div>
                            
                            <!-- Seção de Foto de Perfil do Gerente -->
                            <div class="border-t border-gray-200pt-6 mt-6">
                                <h5 class="text-lg font-semibold text-gray-900mb-4">Foto de Perfil</h5>
                                <div class="flex flex-col space-y-3">
                                    <div class="flex items-center space-x-3">
                                        <button type="button" onclick="openManagerPhotoModal()" class="px-4 py-2 bg-forest-600 hover:bg-forest-700 text-white font-medium rounded-lg transition-all text-sm">
                                            Mudar Foto
                                        </button>
                                        <button type="button" onclick="removeManagerPhoto()" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-all text-sm">
                                            Remover Foto
                                        </button>
                                    </div>
                                    <input type="file" id="managerProfilePhotoInput" name="managerProfilePhoto" accept="image/*" class="hidden" onchange="handleManagerGallerySelection(this)">
                                    <p class="text-xs text-gray-500">Formatos: JPG, PNG (máx. 2MB)</p>
                                </div>
                            </div>
                            
                            <div id="profileEditButtons" class="flex justify-end space-x-3 pt-4 hidden">
                                <button type="button" onclick="cancelProfileEdit()" class="px-6 py-3 border border-gray-300text-gray-700font-semibold rounded-xl hover:bg-gray-50transition-all">
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
                <div class="bg-whiterounded-2xl p-6 border border-gray-200mx-4 mb-8">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-lg font-semibold text-gray-900mb-2">Seguran�a da Conta</h4>
                            <p class="text-sm text-gray-600">Altere sua senha para manter sua conta segura</p>
                        </div>
                        <button onclick="window.location.href='alterar-senha.php'" class="px-6 py-3 gradient-forest text-white font-semibold rounded-xl hover:shadow-lg transition-all">
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                            </svg>
                            Alterar Senha
                        </button>
                    </div>
                </div>
                
                <!-- Criaçãoo de Contas Secund�rias -->
                <div class="bg-whiterounded-2xl p-4 sm:p-8 border border-gray-200mx-0 sm:mx-4 mb-8">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-lg font-semibold text-gray-900">Contas Secund�rias</h4>
                        <button onclick="toggleSecondaryAccountForm()" class="px-4 py-2 text-sm bg-forest-600 hover:bg-forest-700 text-white font-medium rounded-lg transition-all">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Nova Conta
                        </button>
                    </div>
                    
                    <p class="text-sm text-gray-600mb-4">Crie contas de funcionário ou veterinário usando seus dados como base</p>
                    
                    <!-- Lista de Contas Secund�rias -->
                    <div id="secondaryAccountsList" class="space-y-3 mb-4 overflow-hidden">
                        <!-- Contas serão carregadas aqui -->
                    </div>
                    
                    <!-- Formulário de Nova Conta -->
                    <div id="secondaryAccountForm" class="hidden border-t border-gray-200pt-4">
                        <form id="createSecondaryAccountForm" class="space-y-4">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-800 mb-2">Tipo de Conta</label>
                                    <select name="account_type" required class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none bg-white" onchange="checkExistingSecondaryAccount(this.value)">
                                        <option value="">Selecione o tipo</option>
                                        <option value="funcionario">Funcionário</option>
                                        <option value="veterinario">Veterinário</option>
                                    </select>
                                    <div id="existingAccountMessage" class="mt-2 text-sm hidden"></div>
                                </div>
                                
                                <!-- Campos ocultos com dados da conta prim�ria -->
                                <input type="hidden" name="email" id="secondaryAccountEmail">
                                <input type="hidden" name="name" id="secondaryAccountName">
                                <input type="hidden" name="whatsapp" id="secondaryAccountWhatsApp">
                                
                                <div class="bg-green-50 border border-green-200 rounded-xl p-4">
                                    <div class="flex items-start">
                                        <svg class="w-5 h-5 text-green-600 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                <div>
                                            <h5 class="text-sm font-semibold text-green-800 mb-1">Dados Autom�ticos</h5>
                                            <p class="text-sm text-green-700">A nova conta usar� automaticamente seus dados da conta principal:</p>
                                            <ul class="text-sm text-green-700 mt-2 space-y-1">
                                                <li>� <strong>Email:</strong> <span id="displayEmail">-</span></li>
                                                <li>� <strong>Nome:</strong> <span id="displayName">-</span></li>
                                                <li>� <strong>WhatsApp:</strong> <span id="displayWhatsApp">-</span></li>
                                            </ul>
                                </div>
                                </div>
                                </div>
                            </div>
                            
                            <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                                <div class="flex items-start">
                                    <svg class="w-5 h-5 text-blue-600 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <div>
                                        <h5 class="text-sm font-semibold text-blue-800 mb-1">Contas Secund�rias - Para Voc� Mesmo</h5>
                                        <ul class="text-sm text-blue-700 space-y-1">
                                            <li>� Crie contas para você mesmo (ex: veterinário, funcionário)</li>
                                            <li>� Use o mesmo email da sua conta principal</li>
                                            <li>� �til para fazendas menores onde você faz m�ltiplas funçãoes</li>
                                            <li>� Acesso r�pido entre diferentes perfis</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex justify-end space-x-3">
                                <button type="button" onclick="cancelSecondaryAccountForm()" class="px-6 py-3 border border-gray-300text-gray-700font-semibold rounded-xl hover:bg-gray-50transition-all">
                                    Cancelar
                                </button>
                                <button type="submit" class="px-6 py-3 gradient-forest text-white font-semibold rounded-xl hover:shadow-lg transition-all">
                                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                    Criar Conta
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Sair do Sistema -->
                <div class="bg-whiterounded-2xl p-4 sm:p-8 border border-red-200 mx-0 sm:mx-4 mb-8">
                    <h4 class="text-lg font-semibold text-red-900 mb-4">Zona de Perigo</h4>
                    <div class="flex items-center justify-between">
                        <div>
                            <h5 class="font-medium text-red-900">Sair do Sistema</h5>
                            <p class="text-sm text-red-600">Encerrar sua sess�o atual</p>
                        </div>
                        <button onclick="signOut()" class="px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-xl transition-all">
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

    <!-- Modal de C�mera do Gerente -->
    <div id="managerCameraModal" class="fullscreen-modal hidden" style="display: none;">
        <div class="modal-content bg-black">
            <!-- Header com Logo -->
            <div class="absolute top-0 left-0 right-0 z-30 p-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <img src="https://i.postimg.cc/vmrkgDcB/lactech.png" alt="LacTech" class="w-8 h-8">
                        <span class="text-white font-semibold">LacTech</span>
                    </div>
                    <button onclick="closeManagerCamera()" class="p-2 bg-black/30 backdrop-blur-md rounded-full hover:bg-black/50 transition-all">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Video da C�mera -->
            <video id="managerCameraVideo" class="w-full h-full object-cover" autoplay playsinline></video>
            
            <!-- Overlay de Verificaçãoo Facial -->
            <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                <div id="managerFocusCircle" class="relative">
                    <div id="managerFaceCircle" class="w-80 h-80 border-4 border-red-500 rounded-full flex items-center justify-center animate-pulse transition-colors duration-300">
                        <div class="w-72 h-72 border-2 border-white/60 rounded-full flex items-center justify-center">
                            <div class="w-64 h-64 border-3 border-white/80 rounded-full flex items-center justify-center">
                                <div id="managerFocusIndicator" class="w-56 h-56 border-2 border-green-400 rounded-full flex items-center justify-center opacity-0 transition-opacity duration-500">
                                    <div class="w-48 h-48 border border-green-300 rounded-full"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="absolute -bottom-16 left-1/2 transform -translate-x-1/2 text-center">
                        <div id="managerFocusText" class="text-white text-lg font-medium mb-2">Posicione o rosto no centro</div>
                        <div id="managerFaceStatus" class="text-white/70 text-sm mb-2">Centralizando rosto...</div>
                        <div id="managerFocusTimer" class="text-white/70 text-sm hidden">Focando em <span id="managerTimerCount">3</span>s...</div>
                    </div>
                </div>
            </div>
            
            <!-- Aviso de Centralizaçãoo -->
            <div id="managerFaceWarning" class="absolute top-20 left-1/2 transform -translate-x-1/2 bg-red-500 text-white px-4 py-2 rounded-lg text-sm font-medium opacity-0 transition-opacity duration-300 pointer-events-none">
                Centralize o rosto no c�rculo para tirar a foto
            </div>

            <!-- Controles -->
            <div class="absolute bottom-0 left-0 right-0 z-20 p-8">
                <div class="flex items-center justify-center space-x-12">
                    <button onclick="switchManagerCamera()" class="p-4 bg-black/30 backdrop-blur-md rounded-full hover:bg-black/50 transition-all">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                    </button>
                    
                    <button id="managerCaptureBtn" onclick="captureManagerPhoto()" class="p-6 bg-white/50 rounded-full transition-all shadow-2xl cursor-not-allowed opacity-50" disabled>
                        <div class="w-20 h-20 border-4 border-gray-200rounded-full flex items-center justify-center">
                            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                            </div>
                        </div>
                    </button>
                    
                    <button onclick="closeManagerCamera()" class="p-4 bg-black/30 backdrop-blur-md rounded-full hover:bg-black/50 transition-all">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Canvas para Captura -->
            <canvas id="managerCameraCanvas" class="hidden"></canvas>
            
            <!-- Tela de Carregamento -->
            <div id="managerPhotoProcessingScreen" class="absolute inset-0 bg-black bg-opacity-90 flex items-center justify-center z-30 hidden">
                <div class="text-center text-white">
                    <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-white mx-auto mb-4"></div>
                    <h3 class="text-xl font-semibold mb-2">Processando foto...</h3>
                    <p class="text-white/70">Aguarde enquanto processamos sua imagem</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Notification Toast -->
    <div id="notificationToast" class="notification-toast">
        <div class="bg-white   rounded-lg shadow-lg border border-gray-200   p-4 max-w-sm">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-900  " id="toastMessage">Sucesso!</p>
                </div>
                <div class="ml-auto pl-3">
                    <button onclick="hideNotification()" class="text-gray-400 hover:text-gray-600  :text-gray-300">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>


    <!-- <link href="assets/css/loading-screen.css" rel="stylesheet"> DESABILITADO - usando apenas modal de carregamento -->
    <!-- <link href="assets/css/offline-loading.css" rel="stylesheet"> --> <!-- Desabilitado -->
    <link href="assets/css/weather-modal.css" rel="stylesheet">
    <link href="assets/css/native-notifications.css" rel="stylesheet">
    <link href="assets/css/quality-modal.css" rel="stylesheet">
    
    <!-- Responsividade Customizada -->
    <style>
        /* Gradiente de texto estilo Xandria Store */
        .gradient-text {
            background: linear-gradient(135deg, #01875f, #10b981);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        /* Card hover effect estilo Xandria Store */
        .card-hover {
            transition: all 0.3s ease;
        }
        
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        
        .dark .card-hover:hover {
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.6);
        }
        
        /* Responsividade para telas muito pequenas */
        @media (max-width: 360px) {
            .text-xs { font-size: 0.65rem !important; line-height: 0.9rem !important; }
            .text-sm { font-size: 0.75rem !important; line-height: 1rem !important; }
            .text-base { font-size: 0.8rem !important; line-height: 1.1rem !important; }
            .text-lg { font-size: 0.9rem !important; line-height: 1.2rem !important; }
            .text-xl { font-size: 1rem !important; line-height: 1.3rem !important; }
            .text-2xl { font-size: 1.1rem !important; line-height: 1.4rem !important; }
            
            /* Espa�amentos reduzidos */
            .p-2 { padding: 0.3rem !important; }
            .p-3 { padding: 0.5rem !important; }
            .p-4 { padding: 0.6rem !important; }
            .p-6 { padding: 0.8rem !important; }
            .px-3 { padding-left: 0.5rem !important; padding-right: 0.5rem !important; }
            .px-4 { padding-left: 0.6rem !important; padding-right: 0.6rem !important; }
            .px-6 { padding-left: 0.8rem !important; padding-right: 0.8rem !important; }
            .py-2 { padding-top: 0.3rem !important; padding-bottom: 0.3rem !important; }
            .py-3 { padding-top: 0.5rem !important; padding-bottom: 0.5rem !important; }
            .py-4 { padding-top: 0.6rem !important; padding-bottom: 0.6rem !important; }
            
            /* Margens reduzidas */
            .m-2 { margin: 0.3rem !important; }
            .m-3 { margin: 0.5rem !important; }
            .m-4 { margin: 0.6rem !important; }
            .mb-2 { margin-bottom: 0.3rem !important; }
            .mb-3 { margin-bottom: 0.5rem !important; }
            .mb-4 { margin-bottom: 0.6rem !important; }
            .mb-6 { margin-bottom: 0.8rem !important; }
            .mt-2 { margin-top: 0.3rem !important; }
            .mt-3 { margin-top: 0.5rem !important; }
            .mt-4 { margin-top: 0.6rem !important; }
            
            /* Grid gaps reduzidos */
            .gap-2 { gap: 0.3rem !important; }
            .gap-3 { gap: 0.5rem !important; }
            .gap-4 { gap: 0.6rem !important; }
            .gap-6 { gap: 0.8rem !important; }
            
            /* Espa�amentos entre elementos */
            .space-y-2 > * + * { margin-top: 0.3rem !important; }
            .space-y-3 > * + * { margin-top: 0.5rem !important; }
            .space-y-4 > * + * { margin-top: 0.6rem !important; }
            .space-y-6 > * + * { margin-top: 0.8rem !important; }
            
            /* Botões menores */
            .btn-sm { padding: 0.3rem 0.6rem !important; font-size: 0.7rem !important; }
            .btn-md { padding: 0.5rem 0.8rem !important; font-size: 0.8rem !important; }
            
            /* Cards mais compactos */
            .card-compact { padding: 0.6rem !important; }
            .card-compact .text-lg { font-size: 0.85rem !important; }
            .card-compact .text-xl { font-size: 0.95rem !important; }
            
            /* Headers mais compactos */
            .header-compact { padding: 0.5rem 0.8rem !important; }
            .header-compact .text-xl { font-size: 0.9rem !important; }
            .header-compact .text-2xl { font-size: 1rem !important; }
        }
        
        /* Responsividade para telas pequenas */
        @media (max-width: 480px) {
            .text-xs { font-size: 0.7rem !important; line-height: 0.95rem !important; }
            .text-sm { font-size: 0.8rem !important; line-height: 1.05rem !important; }
            .text-base { font-size: 0.85rem !important; line-height: 1.15rem !important; }
            .text-lg { font-size: 0.95rem !important; line-height: 1.25rem !important; }
            .text-xl { font-size: 1.05rem !important; line-height: 1.35rem !important; }
            .text-2xl { font-size: 1.15rem !important; line-height: 1.45rem !important; }
            
            /* Espa�amentos moderados */
            .p-2 { padding: 0.4rem !important; }
            .p-3 { padding: 0.6rem !important; }
            .p-4 { padding: 0.7rem !important; }
            .p-6 { padding: 0.9rem !important; }
            .px-3 { padding-left: 0.6rem !important; padding-right: 0.6rem !important; }
            .px-4 { padding-left: 0.7rem !important; padding-right: 0.7rem !important; }
            .px-6 { padding-left: 0.9rem !important; padding-right: 0.9rem !important; }
            .py-2 { padding-top: 0.4rem !important; padding-bottom: 0.4rem !important; }
            .py-3 { padding-top: 0.6rem !important; padding-bottom: 0.6rem !important; }
            .py-4 { padding-top: 0.7rem !important; padding-bottom: 0.7rem !important; }
            
            /* Margens moderadas */
            .m-2 { margin: 0.4rem !important; }
            .m-3 { margin: 0.6rem !important; }
            .m-4 { margin: 0.7rem !important; }
            .mb-2 { margin-bottom: 0.4rem !important; }
            .mb-3 { margin-bottom: 0.6rem !important; }
            .mb-4 { margin-bottom: 0.7rem !important; }
            .mb-6 { margin-bottom: 0.9rem !important; }
            .mt-2 { margin-top: 0.4rem !important; }
            .mt-3 { margin-top: 0.6rem !important; }
            .mt-4 { margin-top: 0.7rem !important; }
            
            /* Grid gaps moderados */
            .gap-2 { gap: 0.4rem !important; }
            .gap-3 { gap: 0.6rem !important; }
            .gap-4 { gap: 0.7rem !important; }
            .gap-6 { gap: 0.9rem !important; }
            
            /* Espa�amentos entre elementos */
            .space-y-2 > * + * { margin-top: 0.4rem !important; }
            .space-y-3 > * + * { margin-top: 0.6rem !important; }
            .space-y-4 > * + * { margin-top: 0.7rem !important; }
            .space-y-6 > * + * { margin-top: 0.9rem !important; }
        }
        
        /* Prevenir quebra de linha em textos importantes */
        .no-wrap { white-space: nowrap !important; }
        .text-ellipsis { overflow: hidden !important; text-overflow: ellipsis !important; white-space: nowrap !important; }
        
        /* Ajustes espec�ficos para elementos que quebram */
        .metric-value { font-size: clamp(0.8rem, 2.5vw, 1.25rem) !important; }
        .metric-label { font-size: clamp(0.6rem, 2vw, 0.875rem) !important; }
        .card-title { font-size: clamp(0.75rem, 2.2vw, 1.125rem) !important; }
        .button-text { font-size: clamp(0.7rem, 2vw, 0.875rem) !important; }
        
        /* Melhorar espa�amento em grids */
        .grid-compact { gap: 0.5rem !important; }
        .grid-compact > * { padding: 0.5rem !important; }
        
        /* Ajustes para modais em telas pequenas */
        @media (max-width: 480px) {
            .modal-content { padding: 0.8rem !important; }
            .modal-title { font-size: 1rem !important; }
            .modal-body { font-size: 0.85rem !important; }
            .modal-button { padding: 0.5rem 0.8rem !important; font-size: 0.8rem !important; }
        }
        
        @media (max-width: 360px) {
            .modal-content { padding: 0.6rem !important; }
            .modal-title { font-size: 0.9rem !important; }
            .modal-body { font-size: 0.75rem !important; }
            .modal-button { padding: 0.4rem 0.6rem !important; font-size: 0.7rem !important; }
        }
        
        /* Ajustes espec�ficos para cards de dados */
        @media (max-width: 480px) {
            .data-card { padding: 0.8rem !important; }
            .data-card h3 { font-size: 0.9rem !important; margin-bottom: 0.6rem !important; }
            .data-card .text-lg { font-size: 0.85rem !important; }
            .data-card .text-xl { font-size: 0.95rem !important; }
            .data-card .text-2xl { font-size: 1.05rem !important; }
        }
        
        @media (max-width: 360px) {
            .data-card { padding: 0.6rem !important; }
            .data-card h3 { font-size: 0.8rem !important; margin-bottom: 0.5rem !important; }
            .data-card .text-lg { font-size: 0.75rem !important; }
            .data-card .text-xl { font-size: 0.85rem !important; }
            .data-card .text-2xl { font-size: 0.95rem !important; }
        }
        
        /* Ajustes para bot�es em telas pequenas */
        @media (max-width: 480px) {
            .btn-primary, .btn-secondary, .btn-success, .btn-danger { 
                padding: 0.4rem 0.8rem !important; 
                font-size: 0.8rem !important; 
            }
            .btn-sm { 
                padding: 0.3rem 0.6rem !important; 
                font-size: 0.7rem !important; 
            }
        }
        
        @media (max-width: 360px) {
            .btn-primary, .btn-secondary, .btn-success, .btn-danger { 
                padding: 0.3rem 0.6rem !important; 
                font-size: 0.7rem !important; 
            }
            .btn-sm { 
                padding: 0.25rem 0.5rem !important; 
                font-size: 0.65rem !important; 
            }
        }
        
        /* Ajustes para tabelas em telas pequenas */
        @media (max-width: 480px) {
            .table-responsive { font-size: 0.75rem !important; }
            .table-responsive th, .table-responsive td { 
                padding: 0.4rem 0.3rem !important; 
            }
        }
        
        @media (max-width: 360px) {
            .table-responsive { font-size: 0.7rem !important; }
            .table-responsive th, .table-responsive td { 
                padding: 0.3rem 0.2rem !important; 
            }
        }
        
        /* Ajustes para inputs e formulários */
        @media (max-width: 480px) {
            input, select, textarea { 
                font-size: 0.85rem !important; 
                padding: 0.4rem 0.6rem !important; 
            }
            label { font-size: 0.8rem !important; }
        }
        
        @media (max-width: 360px) {
            input, select, textarea { 
                font-size: 0.8rem !important; 
                padding: 0.3rem 0.5rem !important; 
            }
            label { font-size: 0.75rem !important; }
        }
        
        /* Ajustes espec�ficos para modal de logout */
        @media (max-width: 480px) {
            #logoutConfirmationModal .bg-white,
            #logoutConfirmationModal .bg-white{
                margin: 1rem !important;
                padding: 1.5rem !important;
            }
            
            #logoutConfirmationModal h3 {
                font-size: 1.1rem !important;
            }
            
            #logoutConfirmationModal p {
                font-size: 0.9rem !important;
            }
            
            #logoutConfirmationModal .w-16 {
                width: 3rem !important;
                height: 3rem !important;
            }
            
            #logoutConfirmationModal .w-8 {
                width: 1.5rem !important;
                height: 1.5rem !important;
            }
        }
        
        /* Garantir que os bot�es de ediçãoo sejam ocultados corretamente */
        #profileEditButtons.hidden {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
            pointer-events: none !important;
        }
        
        /* For�ar ocultaçãoo dos bot�es quando não há mudan�as */
        #profileEditButtons[style*="display: none"] {
            visibility: hidden !important;
            opacity: 0 !important;
            pointer-events: none !important;
        }
        
        /* Garantir que as telas de processamento sejam ocultadas */
        #photoProcessingScreen.hidden,
        #managerPhotoProcessingScreen.hidden {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
            pointer-events: none !important;
        }
        
        /* For�ar ocultaçãoo das telas de processamento */
        #photoProcessingScreen[style*="display: none"],
        #managerPhotoProcessingScreen[style*="display: none"] {
            visibility: hidden !important;
            opacity: 0 !important;
            pointer-events: none !important;
        }
        
        /* Garantir que o modal de foto do gerente seja exibido corretamente */
        #managerPhotoChoiceModal[style*="display: flex"] {
            visibility: visible !important;
            opacity: 1 !important;
            pointer-events: auto !important;
        }
        
        #managerPhotoChoiceModal.flex {
            display: flex !important;
            visibility: visible !important;
            opacity: 1 !important;
            pointer-events: auto !important;
        }
        
        /* Garantir que o modal MAIS seja exibido corretamente */
        #moreModal[style*="display: block"] {
            visibility: visible !important;
            opacity: 1 !important;
            pointer-events: auto !important;
            background-color: white !important;
        }
        
        #moreModal:not(.hidden) {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            pointer-events: auto !important;
            background-color: white !important;
        }
        
        
        /* Melhorar aparência dos apps no modal estilo mobile */
        #moreModal .app-item {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
            padding: 8px;
            border-radius: 12px;
        }
        
        #moreModal .app-item:hover {
            background-color: rgba(0, 0, 0, 0.05) !important;
            transform: translateY(-2px) !important;
        }
        
        
        #moreModal .app-item:active {
            transform: scale(0.95) !important;
        }
        
        
        /* Garantir que o ícone de perfil seja ocultado quando h� foto */
        #headerProfileIcon.hidden,
        #modalProfileIcon.hidden,
        #managerProfilePlaceholder.hidden {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
            pointer-events: none !important;
        }
        
        /* Garantir que a foto de perfil seja exibida */
        #headerProfilePhoto:not(.hidden),
        #modalProfilePhoto:not(.hidden),
        #managerProfilePreview:not(.hidden) {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            pointer-events: auto !important;
        }
        
        /* For�ar fechamento do modal de foto do gerente */
        #managerPhotoChoiceModal.hidden {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
            pointer-events: none !important;
            position: fixed !important;
            z-index: -1 !important;
        }
        
        #managerPhotoChoiceModal[style*="display: none"] {
            visibility: hidden !important;
            opacity: 0 !important;
            pointer-events: none !important;
            position: fixed !important;
            z-index: -1 !important;
        }
        
        /* Garantir que o modal de foto do gerente seja exibido corretamente */
        #managerPhotoChoiceModal.flex {
            display: flex !important;
            visibility: visible !important;
            opacity: 1 !important;
            pointer-events: auto !important;
            position: fixed !important;
            z-index: 999999 !important;
        }
        
        #managerPhotoChoiceModal[style*="display: flex"] {
            visibility: visible !important;
            opacity: 1 !important;
            pointer-events: auto !important;
            position: fixed !important;
            z-index: 999999 !important;
        }
        
        /* Corrigir overflow nas contas secund�rias */
        #secondaryAccountsList {
            overflow-x: hidden;
            overflow-y: auto;
            width: 100%;
        }
        
        #secondaryAccountsList > div {
            max-width: 100%;
            overflow: hidden;
            width: 100%;
        }
        
        /* Corrigir centralizaçãoo no desktop */
        @media (min-width: 640px) {
            #secondaryAccountsList {
                display: flex;
                flex-direction: column;
                align-items: stretch;
                width: 100%;
            }
            
            #secondaryAccountsList > div {
                width: 100%;
                max-width: none;
                flex: 1;
            }
        }
        
        /* Garantir que o container das contas secund�rias ocupe toda a largura */
        .bg-white.rounded-2xl.p-4.sm\\:p-8.border.border-gray-200.mx-0.sm\\:mx-4.mb-8 {
            width: 100%;
        }
        
        /* ==================== ANIMAçãoO DE CONEX�O COM SERVIDOR ==================== */
        /* From Uiverse.io by Juanes200122 */ 
        #svg_svg {
            zoom: 0.3;
        }
        .estrobo_animation {
            animation:
                floatAndBounce 4s infinite ease-in-out,
                strobe 0.8s infinite;
        }

        .estrobo_animationV2 {
            animation:
                floatAndBounce 4s infinite ease-in-out,
                strobev2 0.8s infinite;
        }

        #float_server {
            animation: floatAndBounce 4s infinite ease-in-out;
        }

        @keyframes floatAndBounce {
            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-20px);
            }
        }

        @keyframes strobe {
            0%,
            50%,
            100% {
                fill: #17e300;
            }

            25%,
            75% {
                fill: #17e300b4;
            }
        }

        @keyframes strobev2 {
            0%,
            50%,
            100% {
                fill: rgb(255, 95, 74);
            }

            25%,
            75% {
                fill: rgb(16, 53, 115);
            }
        }

        /* Animaci�n de los colores del gradiente */
        @keyframes animateGradient {
            0% {
                stop-color: #313f8773;
            }

            50% {
                stop-color: #040d3a;
            }

            100% {
                stop-color: #313f8773;
            }
        }

        /* Animaci�n aplicada a los puntos del gradiente */
        #paint13_linear_163_1030 stop {
            animation: animateGradient 4s infinite alternate;
        }
        
        /* Garantir que os textos longos sejam truncados */
        .truncate {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        /* Animações do header din�mico - Global */
        #profileModalHeader {
            transition: transform 0.3s ease-in-out, opacity 0.3s ease-in-out !important;
        }
        
        /* Responsividade para telas pequenas */
        @media (max-width: 640px) {
            #secondaryAccountsList > div {
                padding: 0.75rem;
            }
            
            #secondaryAccountsList .flex-col {
                gap: 0.5rem;
            }
            
            /* Modal de perfil ocupar toda a tela no mobile */
            #profileModal {
                width: 100vw;
                height: 100vh;
                max-width: none;
                max-height: none;
                border-radius: 0;
                margin: 0;
            }
            
            #profileModal .modal-content {
                width: 100%;
                height: 100%;
                border-radius: 0;
                margin: 0;
            }
            
            /* Header do modal de perfil */
            #profileModalHeader {
                border-radius: 0;
                margin: 0;
                width: 100vw;
                margin-left: -1rem;
                margin-right: -1rem;
            }
            
            @media (min-width: 640px) {
                #profileModalHeader {
                    width: 100%;
                    margin-left: 0;
                    margin-right: 0;
                }
            }
            
            /* Conteúdo do modal responsivo - padding removido para evitar conflito com sticky header */
            
            /* Animações do header din�mico */
            #profileModalHeader {
                transition: transform 0.3s ease-in-out, opacity 0.3s ease-in-out !important;
            }
        }
        
        @media (max-width: 360px) {
            #logoutConfirmationModal .bg-white,
            #logoutConfirmationModal .bg-white{
                margin: 0.5rem !important;
                padding: 1rem !important;
            }
            
            #logoutConfirmationModal h3 {
                font-size: 1rem !important;
            }
            
            #logoutConfirmationModal p {
                font-size: 0.85rem !important;
            }
            
            #logoutConfirmationModal .w-16 {
                width: 2.5rem !important;
                height: 2.5rem !important;
            }
            
            #logoutConfirmationModal .w-8 {
                width: 1.25rem !important;
                height: 1.25rem !important;
            }
        }
        
        /* Animaçãoo dos tr�s pontinhos para indicador de digitando */
        .typing-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background-color: #9CA3AF;
            animation: typing 1.4s infinite ease-in-out;
        }
        
        .typing-dot:nth-child(1) {
            animation-delay: -0.32s;
        }
        
        .typing-dot:nth-child(2) {
            animation-delay: -0.16s;
        }
        
        @keyframes typing {
            0%, 80%, 100% {
                transform: scale(0.8);
                opacity: 0.5;
            }
            40% {
                transform: scale(1);
                opacity: 1;
            }
        }
    </style>
    
    
<!-- Estilos personalizados para animações premium da seçãoo de relatórios -->
    <style>
        /* Animações customizadas */
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-6px); }
        }
        
        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        
        .animate-float {
            animation: float 4s ease-in-out infinite;
        }
        
        /* Efeito shimmer nos bot�es */
        .btn-shimmer {
            position: relative;
            overflow: hidden;
        }
        
        .btn-shimmer::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                90deg,
                transparent,
                rgba(255, 255, 255, 0.4),
                transparent
            );
            transition: left 0.6s ease-in-out;
        }
        
        .btn-shimmer:hover::before {
            left: 100%;
        }
        
        /* Efeito de vidro melhorado */
        .glass-effect {
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }
        
        /* Sombras coloridas personalizadas */
        .shadow-red-glow {
            box-shadow: 0 10px 40px rgba(239, 68, 68, 0.3);
        }
        
        .shadow-green-glow {
            box-shadow: 0 10px 40px rgba(34, 197, 94, 0.3);
        }
        
        .shadow-purple-glow {
            box-shadow: 0 10px 40px rgba(147, 51, 234, 0.3);
        }
        
        /* Efeito de part�culas flutuantes */
        .floating-particles::before,
        .floating-particles::after {
            content: '';
            position: absolute;
            width: 4px;
            height: 4px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            animation: float 3s ease-in-out infinite;
        }
        
        .floating-particles::before {
            top: 20%;
            right: 15%;
            animation-delay: -1s;
        }
        
        .floating-particles::after {
            bottom: 25%;
            left: 10%;
            animation-delay: -2s;
        }
    </style>

        
    
<!-- Script fix_data_sync_complete.js removido para evitar conflitos -->

    <link rel="icon" href="https://i.postimg.cc/vmrkgDcB/lactech.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>

        

        
        /* Efeito de foco nos bot�es */ 
        button:focus { 
            outline: none; 
        } 
        
        /* Classes Tailwind para focus-visible */ 
        .focus-visible:focus { 
            --tw-ring-offset-shadow: var(--tw-ring-inset) 0 0 0 var(--tw-ring-offset-width) var(--tw-ring-offset-color); 
            --tw-ring-shadow: var(--tw-ring-inset) 0 0 0 calc(2px + var(--tw-ring-offset-width)) var(--tw-ring-color); 
            box-shadow: var(--tw-ring-offset-shadow), var(--tw-ring-shadow), var(--tw-shadow, 0 0 #0000); 
            --tw-ring-color: rgba(134, 209, 134, 0.5); 
            --tw-ring-offset-width: 2px; 
        } 
        
        /* Classe para background do ícone */ 
        .bg-forest-100 { 
            background-color: #f0f9f0; 
        } 
        
        /* Definiçãoo de cores personalizadas para o ring */
        :root {
            --forest-300: #86d186;
        }
        
        /* Animações da C�mera */
        @keyframes focusPulse {
            0%, 100% { transform: scale(1); opacity: 0.4; }
            50% { transform: scale(1.05); opacity: 0.8; }
        }
        
        @keyframes focusSuccess {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        .focus-pulse {
            animation: focusPulse 2s ease-in-out infinite;
        }
        
        .focus-success {
            animation: focusSuccess 0.5s ease-in-out;
        }
        
        /* Estilos da c�mera */
        .camera-overlay {
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
        
        .camera-button {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .camera-button:hover {
            transform: scale(1.05);
        }
        
        /* Modal de Foto */
        #photoModal.show #photoModalContent {
            transform: scale(1);
            opacity: 1;
        }
        

    </style>
    
<!-- ========== HTML MODALS - MOVIDOS PARA FORA DO SCRIPT ========== -->
    
    <!-- Modal de Escolha de Foto do Gerente (FORA do modal de perfil) -->
    <div id="managerPhotoChoiceModal" class="fixed inset-0 bg-black bg-opacity-50 items-center justify-center z-[999999] hidden" style="display: none !important; visibility: hidden !important; opacity: 0 !important; pointer-events: none !important;">
        <div class="bg-whiterounded-2xl p-6 w-full max-w-md mx-4">
            <div class="flex items-center justify-between mb-6">
                                            <h2 class="text-xl font-bold text-gray-900">Mudar Foto de Perfil</h2>
                <button onclick="closeManagerPhotoModal()" class="p-2 hover:bg-gray-100rounded-lg transition-all">
                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <div>
                <div class="text-center space-y-6">
                    <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto">
                        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900mb-2">Como você quer adicionar sua foto?</h3>
                        <p class="text-gray-600">Escolha entre tirar uma foto ou selecionar da galeria</p>
                    </div>
                    
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <button onclick="openManagerCamera()" class="flex items-center justify-center space-x-3 px-6 py-4 bg-forest-600 hover:bg-forest-700 text-white font-medium rounded-xl transition-all">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            <span>Tirar Foto</span>
                        </button>
                        
                        <button onclick="selectManagerFromGallery()" class="flex items-center justify-center space-x-3 px-6 py-4 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-xl transition-all">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <span>Galeria</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div id="moreModal" class="fixed inset-0 bg-white z-[99999] hidden">
        <div class="w-full h-full overflow-y-auto">
        
        <!-- Header melhorado -->
        <div class="flex items-center justify-between p-6 border-b border-gray-200 bg-white sticky top-0 z-10 shadow-sm more-modal-header">
                <div class="flex items-center space-x-4">
                <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-green-600 rounded-2xl flex items-center justify-center shadow-lg">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                        </svg>
                    </div>
                    <div>
                    <h2 class="text-xl font-bold text-gray-900">Mais Opções</h2>
                    <p class="text-sm text-gray-600">Acesse ferramentas e recursos</p>
                    </div>
                </div>
            <button onclick="closeMoreModal()" class="w-10 h-10 flex items-center justify-center hover:bg-gray-100 rounded-xl transition-colors">
                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
        <!-- Conteúdo melhorado -->
        <div class="p-6">
            <div class="max-w-2xl mx-auto">
                <!-- Seção: Ferramentas Principais -->
                <div class="mb-8">
                    <h3 class="text-lg font-semibold text-gray-900mb-4 flex items-center">
                        <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
                        </svg>
                        Ferramentas Principais
                    </h3>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    <!-- Relatórios -->
                        <div class="app-item bg-white border-2 border-gray-200 rounded-2xl p-4 cursor-pointer shadow-sm hover:shadow-md transition-all duration-200" onclick="openReportsTab()">
                            <div class="flex flex-col items-center text-center space-y-3">
                                <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl flex items-center justify-center shadow-lg">
                                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <div>
                                    <p class="font-semibold text-gray-900text-sm">Relatórios</p>
                                    <p class="text-xs text-gray-600">Análises e dados</p>
                            </div>
                        </div>
                    </div>
                    
                        <!-- Gestão de Rebanho -->
                        <div class="app-item bg-white border-2 border-gray-200 rounded-2xl p-4 cursor-pointer shadow-sm hover:shadow-md transition-all duration-200" onclick="showAnimalManagement()">
                            <div class="flex flex-col items-center text-center space-y-3">
                                <div class="w-16 h-16 bg-gradient-to-br from-emerald-600 to-teal-600 rounded-2xl flex items-center justify-center shadow-lg">
                                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900text-sm">Gestão de Rebanho</p>
                                    <p class="text-xs text-gray-600">Animais e IA</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Gestão Sanitária -->
                        <div class="app-item bg-white border-2 border-gray-200 rounded-2xl p-4 cursor-pointer shadow-sm hover:shadow-md transition-all duration-200" onclick="showHealthManagement()">
                            <div class="flex flex-col items-center text-center space-y-3">
                                <div class="w-16 h-16 bg-gradient-to-br from-green-600 to-emerald-700 rounded-2xl flex items-center justify-center shadow-lg">
                                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900text-sm">Gestão Sanitária</p>
                                    <p class="text-xs text-gray-600">Saúde e vacinas</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Reprodução -->
                        <div class="app-item bg-white border-2 border-gray-200 rounded-2xl p-4 cursor-pointer shadow-sm hover:shadow-md transition-all duration-200" onclick="showReproductionManagement()">
                            <div class="flex flex-col items-center text-center space-y-3">
                                <div class="w-16 h-16 bg-gradient-to-br from-teal-600 to-cyan-600 rounded-2xl flex items-center justify-center shadow-lg">
                                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900text-sm">Reprodução</p>
                                    <p class="text-xs text-gray-600">Prenhez e DPP</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Dashboard Analítico -->
                        <div class="app-item bg-white border-2 border-gray-200 rounded-2xl p-4 cursor-pointer shadow-sm hover:shadow-md transition-all duration-200" onclick="showAnalyticsDashboard()">
                            <div class="flex flex-col items-center text-center space-y-3">
                                <div class="w-16 h-16 bg-gradient-to-br from-slate-600 to-slate-700 rounded-2xl flex items-center justify-center shadow-lg">
                                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900text-sm">Dashboard Analítico</p>
                                    <p class="text-xs text-gray-600">Indicadores e KPIs</p>
                                </div>
                            </div>
                        </div>
                    
                        <!-- Suporte -->
                        <div class="app-item bg-white border-2 border-gray-200 rounded-2xl p-4 cursor-pointer shadow-sm hover:shadow-md transition-all duration-200" onclick="openSupportHub()">
                            <div class="flex flex-col items-center text-center space-y-3">
                                <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl flex items-center justify-center shadow-lg">
                                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div>
                                    <p class="font-semibold text-gray-900 text-sm">Suporte</p>
                                    <p class="text-xs text-gray-600">Ajuda e contato</p>
                            </div>
                        </div>
                    </div>
                    
                        <!-- Xandria Store -->
                        <div class="app-item bg-white border-2 border-gray-200 rounded-2xl p-4 cursor-pointer shadow-sm hover:shadow-md transition-all duration-200" onclick="openXandriaStore()">
                            <div class="flex flex-col items-center text-center space-y-3">
                                <div class="w-16 h-16 bg-gradient-to-br from-white to-white rounded-2xl flex items-center justify-center shadow-lg">
                                    <img id="xandriaStoreIcon" src="https://i.postimg.cc/W17q41wM/lactechpreta.png" alt="Xandria Store" class="w-12 h-12 rounded-xl">
                            </div>
                            <div>
                                    <p class="font-semibold text-gray-900 text-sm">Xandria Store</p>
                                    <p class="text-xs text-gray-600">Apps e sistemas</p>
                            </div>
                        </div>
                        </div>
                        </div>
                    </div>
                    
                <!-- Seção: Utilitários -->
                <div class="mb-8">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <svg class="w-5 h-5 text-blue-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"></path>
                        </svg>
                        Utilitários
                    </h3>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                        
                        <!-- Contatos -->
                        <div class="app-item bg-white border-2 border-gray-200 rounded-2xl p-4 cursor-pointer shadow-sm hover:shadow-md transition-all duration-200" onclick="openContactsModal()">
                            <div class="flex flex-col items-center text-center space-y-3">
                                <div class="w-16 h-16 bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-2xl flex items-center justify-center shadow-lg">
                                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900 text-sm">Contatos</p>
                                    <p class="text-xs text-gray-600">Lista telefonica</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Controle de Novilhas -->
                        <div class="app-item bg-white border-2 border-gray-200 rounded-2xl p-4 cursor-pointer shadow-sm hover:shadow-md transition-all duration-200" onclick="openHeiferManagement()">
                            <div class="flex flex-col items-center text-center space-y-3">
                                <div class="w-16 h-16 bg-gradient-to-br from-orange-500 to-red-500 rounded-2xl flex items-center justify-center shadow-lg">
                                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.5 12.5c0 .828-.672 1.5-1.5 1.5s-1.5-.672-1.5-1.5.672-1.5 1.5-1.5 1.5.672 1.5 1.5zM18.5 12.5c0 .828-.672 1.5-1.5 1.5s-1.5-.672-1.5-1.5.672-1.5 1.5-1.5 1.5.672 1.5 1.5zM12 20c-2.5 0-4.5-2-4.5-4.5S9.5 11 12 11s4.5 2 4.5 4.5S14.5 20 12 20zM12 8c-1.5 0-2.5-1-2.5-2.5S10.5 3 12 3s2.5 1 2.5 2.5S13.5 8 12 8z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900 text-sm">Controle de Novilhas</p>
                                    <p class="text-xs text-gray-600">Custos de criação</p>
                                </div>
                            </div>
                        </div>
                    </div>
                        </div>
                    </div>
                    
        </div>
    </div>

    <!-- Modal de Contatos - Full Screen -->
    <div id="contactsModal" class="fixed inset-0 bg-whitez-[99999] hidden flex flex-col" style="display: none;">
        <!-- Header -->
        <div class="flex-shrink-0 p-4 sm:p-6 bg-gradient-to-br from-indigo-500 via-indigo-600 to-indigo-700 text-white shadow-2xl">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-whitebg-opacity-20 rounded-2xl flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                            </div>
                            <div>
                        <h1 class="text-2xl font-bold">Lista Telef�nica</h1>
                        <p class="text-indigo-100 text-sm">Gerencie contatos da fazenda</p>
                            </div>
                        </div>
                <div class="flex items-center space-x-3">
                    <button onclick="openContactForm()" class="px-4 py-2 bg-whitebg-opacity-20 hover:bg-opacity-30 rounded-xl transition-colors flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        <span>Adicionar</span>
                    </button>
                    <button onclick="closeContactsModal()" class="p-2 hover:bg-whitehover:bg-opacity-20 rounded-xl transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                        </div>
                        </div>
                    </div>
                    
        <!-- Conteúdo -->
        <div class="flex-1 overflow-y-auto p-4 sm:p-6 bg-gray-50">
            <div class="max-w-4xl mx-auto">
                <div id="contactsList" class="space-y-4">
                    <!-- Contatos serão carregados aqui -->
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Formulário de Contato -->
    <div id="contactFormModal" class="fixed inset-0 bg-black bg-opacity-50 z-[99999] hidden flex items-center justify-center" style="display: none;">
        <div class="bg-whiterounded-2xl p-6 shadow-2xl max-w-md w-full mx-4">
            <div class="flex items-center justify-between mb-6">
                <h3 id="contactFormTitle" class="text-xl font-bold text-gray-900">Adicionar Contato</h3>
                <button onclick="closeContactForm()" class="p-2 hover:bg-gray-100rounded-xl transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                </button>
                            </div>

            <form id="contactForm" onsubmit="saveContact(event)">
                <div class="space-y-4">
                    <!-- Nome -->
                            <div>
                        <label class="block text-sm font-semibold text-gray-700mb-2">Nome *</label>
                        <input type="text" name="name" id="contactName" required
                               class="w-full px-4 py-3 border border-gray-300rounded-xl focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 focus:outline-none"
                               placeholder="Nome do contato">
                            </div>

                    <!-- Telefone -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700mb-2">Telefone *</label>
                        <input type="tel" name="phone" id="contactPhone" required
                               class="w-full px-4 py-3 border border-gray-300rounded-xl focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 focus:outline-none"
                               placeholder="(11) 99999-9999">
                        </div>

                    <!-- Email -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700mb-2">Email</label>
                        <input type="email" name="email" id="contactEmail"
                               class="w-full px-4 py-3 border border-gray-300rounded-xl focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 focus:outline-none"
                               placeholder="email@exemplo.com">
                        </div>

                    <!-- Categoria -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700mb-2">Categoria *</label>
                        <select name="category" id="contactCategory" required
                                class="w-full px-4 py-3 border border-gray-300rounded-xl focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 focus:outline-none">
                            <option value="">Selecione uma categoria</option>
                            <option value="Distribuidora">Distribuidora</option>
                            <option value="Comprador">Comprador</option>
                            <option value="Fornecedor">Fornecedor</option>
                            <option value="Veterinário">Veterinário</option>
                            <option value="T�cnico">T�cnico</option>
                            <option value="Transportadora">Transportadora</option>
                            <option value="Banco">Banco</option>
                            <option value="Seguro">Seguro</option>
                            <option value="Outros">Outros</option>
                        </select>
                        </div>
                    <!-- Observa��es -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700mb-2">Observa��es</label>
                        <textarea name="notes" id="contactNotes" rows="3"
                                  class="w-full px-4 py-3 border border-gray-300rounded-xl focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 focus:outline-none"
                                  placeholder="Informa��es adicionais..."></textarea>
                    </div>
                </div>

                <!-- Bot�es -->
                <div class="flex space-x-3 mt-6">
                    <button type="button" onclick="closeContactForm()"
                            class="flex-1 px-4 py-3 border border-gray-300text-gray-700rounded-xl hover:bg-gray-50transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" id="contactFormSubmit"
                            class="flex-1 px-4 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl transition-colors">
                        Adicionar
                    </button>
            </div>
            </form>
        </div>
    </div>
    
    <!-- Modal de Solicita��es de Senha - Full Screen -->
    <div id="passwordRequestsModal" class="fixed inset-0 bg-whitebg-whitez-[99999] hidden flex flex-col" style="display: none !important;">
        <!-- Header -->
        <div class="flex-shrink-0 p-4 sm:p-6 bg-gradient-to-br from-forest-500 via-forest-600 to-forest-700 text-white shadow-2xl">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3 sm:space-x-6">
                        <div class="w-12 h-12 sm:w-16 sm:h-16 bg-whitebg-opacity-20 backdrop-blur-sm rounded-2xl sm:rounded-3xl flex items-center justify-center shadow-xl">
                            <svg class="w-6 h-6 sm:w-8 sm:h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-lg sm:text-xl font-bold mb-1">Solicita��es de Senha</h2>
                            <p class="text-forest-100 text-xs sm:text-sm">Gerencie solicita��es de altera��o e redefini��o de senha</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <!-- Bot�o de Hist�rico -->
                        <button onclick="openPasswordHistoryModal()" class="w-10 h-10 sm:w-12 sm:h-12 lg:w-14 lg:h-14 flex items-center justify-center hover:bg-whitehover:bg-opacity-20 rounded-xl sm:rounded-2xl transition-all duration-200" title="Ver hist�rico de solicita��es">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6 lg:w-8 lg:h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </button>
                        <!-- Bot�o de Fechar -->
                        <button onclick="closePasswordRequestsModal()" class="w-10 h-10 sm:w-12 sm:h-12 lg:w-14 lg:h-14 flex items-center justify-center hover:bg-whitehover:bg-opacity-20 rounded-xl sm:rounded-2xl transition-all duration-200">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6 lg:w-8 lg:h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
        </div>
        
        <!-- Conte�do -->
        <div class="flex-1 overflow-y-auto p-4 sm:p-6">
                <!-- Filtros e Estat�sticas -->
                <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-8">
                    <!-- Estat�sticas -->
                    <div class="lg:col-span-3 grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="bg-gradient-to-br from-amber-50 to-amber-100 rounded-2xl p-4 border border-amber-200">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-amber-500 rounded-xl flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-2xl font-bold text-amber-800" id="pendingCount">0</p>
                                    <p class="text-sm text-amber-600">Pendentes</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-2xl p-4 border border-green-200">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-green-500 rounded-xl flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-2xl font-bold text-green-800" id="approvedCount">0</p>
                                    <p class="text-sm text-green-600">Aprovadas</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-gradient-to-br from-red-50 to-red-100 rounded-2xl p-4 border border-red-200">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-red-500 rounded-xl flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-2xl font-bold text-red-800" id="rejectedCount">0</p>
                                    <p class="text-sm text-red-600">Rejeitadas</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Filtros -->
                    <div class="space-y-4">
                        <select id="passwordRequestFilter" class="w-full px-4 py-3 border border-gray-200rounded-xl text-sm focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none bg-whitetext-gray-900">
                            <option value="all">Todas as solicita��es</option>
                            <option value="pending">Pendentes</option>
                            <option value="approved">Aprovadas</option>
                            <option value="rejected">Rejeitadas</option>
                        </select>
                        <button onclick="refreshPasswordRequests()" id="refreshPasswordRequestsBtn" class="w-full px-4 py-3 bg-gradient-to-r from-forest-500 to-forest-600 text-white rounded-xl hover:from-forest-600 hover:to-forest-700 transition-all font-semibold text-sm shadow-lg hover:shadow-xl">
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            Atualizar
                        </button>
                    </div>
                </div>
                
                <!-- Lista de Solicita��es -->
                <div class="bg-whiterounded-2xl border border-gray-200overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200bg-gray-50">
                        <h3 class="text-lg font-semibold text-gray-900">Lista de Solicita��es</h3>
                        <p class="text-sm text-gray-600">Gerencie todas as solicita��es de altera��o de senha</p>
                    </div>
                    
                    <div class="p-6">
                        <div class="space-y-4" id="passwordRequestsList">
                            <!-- Solicita��es ser�o carregadas aqui -->
                        </div>
                        
                        <!-- Estado vazio -->
                        <div id="emptyPasswordRequests" class="text-center py-16 hidden">
                            <div class="w-20 h-20 bg-gradient-to-br from-forest-100 to-forest-200 rounded-full flex items-center justify-center mx-auto mb-6">
                                <svg class="w-10 h-10 text-forest-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-900mb-3">Nenhuma Solicita��o</h3>
                            <p class="text-gray-600mb-6">N�o h� solicita��es de senha no momento.</p>
                            <div class="w-32 h-1 bg-gradient-to-r from-forest-400 to-forest-600 rounded-full mx-auto"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal de Detalhes da Solicita��o -->
    <div id="passwordRequestDetailsModal" class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center z-[99999] hidden" style="display: none !important;">
        <div class="bg-whitebg-whiterounded-2xl shadow-2xl max-w-2xl w-full mx-4">
            <!-- Header -->
            <div class="flex items-center justify-between p-6 border-b border-gray-200border-gray-200">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">Detalhes da Solicita��o</h2>
                        <p class="text-sm text-gray-600">Analise e tome uma decis�o</p>
                    </div>
                </div>
                <button onclick="closePasswordRequestDetailsModal()" class="w-10 h-10 flex items-center justify-center hover:bg-gray-100hover:bg-gray-50rounded-xl transition-colors">
                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Conte�do -->
            <div class="p-6 space-y-6">
                <!-- Informa��es do usu�rio -->
                <div class="bg-gray-50  rounded-xl p-4">
                    <h3 class="font-semibold text-gray-900 mb-3">Informa��es do Usu�rio</h3>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-600">Nome:</span>
                            <span class="font-medium text-gray-900 ml-2" id="requestUserName">-</span>
                        </div>
                        <div>
                            <span class="text-gray-600">Email:</span>
                            <span class="font-medium text-gray-900 ml-2" id="requestUserEmail">-</span>
                        </div>
                        <div>
                            <span class="text-gray-600">Cargo:</span>
                            <span class="font-medium text-gray-900 ml-2" id="requestUserRole">-</span>
                        </div>
                        <div>
                            <span class="text-gray-600">Data da Solicita��o:</span>
                            <span class="font-medium text-gray-900 ml-2" id="requestDate">-</span>
                        </div>
                    </div>
                </div>
                
                <!-- Detalhes da solicita��o -->
                <div class="bg-gray-50  rounded-xl p-4">
                    <h3 class="font-semibold text-gray-900 mb-3">Detalhes da Solicita��o</h3>
                    <div class="space-y-3">
                        <div>
                            <span class="text-gray-600">Tipo:</span>
                            <span class="font-medium text-gray-900 ml-2" id="requestType">-</span>
                        </div>
                        <div>
                            <span class="text-gray-600">Motivo:</span>
                            <span class="font-medium text-gray-900 ml-2" id="requestReason">-</span>
                        </div>
                        <div>
                            <span class="text-gray-600">Observa��es:</span>
                            <span class="font-medium text-gray-900 ml-2" id="requestNotes">-</span>
                        </div>
                    </div>
                </div>
                
                <!-- A��es -->
                <div class="flex flex-col sm:flex-row gap-3 pt-4 border-t border-gray-200border-gray-200">
                    <button onclick="approvePasswordRequest()" class="flex-1 px-6 py-3 bg-green-500 text-white rounded-xl hover:bg-green-600 transition-all font-semibold">
                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Aprovar
                    </button>
                    <button onclick="rejectPasswordRequest()" class="flex-1 px-6 py-3 bg-red-500 text-white rounded-xl hover:bg-red-600 transition-all font-semibold">
                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        Rejeitar
                    </button>
                    <button onclick="closePasswordRequestDetailsModal()" class="px-6 py-3 border border-gray-300border-gray-300text-gray-700 rounded-xl hover:bg-gray-50hover:bg-gray-50transition-all font-semibold">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal de Hist�rico de Solicita��es de Senha -->
    <div id="passwordHistoryModal" class="fixed inset-0 bg-whitebg-whitez-[99999] hidden flex flex-col" style="display: none !important;">
        <!-- Header -->
        <div class="flex-shrink-0 p-4 sm:p-6 bg-gradient-to-br from-forest-500 via-forest-600 to-forest-700 text-white shadow-2xl">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3 sm:space-x-6">
                    <div class="w-12 h-12 sm:w-16 sm:h-16 bg-whitebg-opacity-20 backdrop-blur-sm rounded-2xl sm:rounded-3xl flex items-center justify-center shadow-xl">
                        <svg class="w-6 h-6 sm:w-8 sm:h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg sm:text-xl font-bold mb-1">Hist�rico de Solicita��es</h2>
                        <p class="text-forest-100 text-xs sm:text-sm">Visualize todas as solicita��es processadas nos �ltimos 30 dias</p>
                    </div>
                </div>
                <button onclick="closePasswordHistoryModal()" class="w-10 h-10 sm:w-12 sm:h-12 lg:w-14 lg:h-14 flex items-center justify-center hover:bg-whitehover:bg-opacity-20 rounded-xl sm:rounded-2xl transition-all duration-200">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 lg:w-8 lg:h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
        
        <!-- Conte�do -->
        <div class="flex-1 overflow-y-auto p-4 sm:p-6">
            <!-- Filtros -->
            <div class="mb-6">
                <div class="flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between">
                    <div class="flex flex-col sm:flex-row gap-4">
                        <select id="historyStatusFilter" class="px-4 py-2 border border-gray-300rounded-lg bg-whitetext-gray-900focus:ring-2 focus:ring-forest-500 focus:border-transparent">
                            <option value="">Todos os status</option>
                            <option value="approved">Aprovadas</option>
                            <option value="rejected">Rejeitadas</option>
                            <option value="pending">Pendentes</option>
                        </select>
                        <select id="historyDateFilter" class="px-4 py-2 border border-gray-300rounded-lg bg-whitetext-gray-900focus:ring-2 focus:ring-forest-500 focus:border-transparent">
                            <option value="7">�ltimos 7 dias</option>
                            <option value="15">�ltimos 15 dias</option>
                            <option value="30" selected>�ltimos 30 dias</option>
                            <option value="90">�ltimos 90 dias</option>
                        </select>
                    </div>
                    <button onclick="loadPasswordHistory()" id="refreshPasswordHistoryBtn" class="px-4 py-2 bg-forest-500 hover:bg-forest-600 text-white rounded-lg transition-colors duration-200 flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        <span>Atualizar</span>
                    </button>
                </div>
            </div>
            
            <!-- Lista de Hist�rico -->
            <div id="passwordHistoryList" class="space-y-4">
                <!-- Loading -->
                <div id="historyLoading" class="flex items-center justify-center py-12">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-forest-500"></div>
                    <span class="ml-3 text-gray-600">Carregando hist�rico...</span>
                </div>
                
                <!-- Empty State -->
                <div id="emptyHistory" class="hidden text-center py-12">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900mb-2">Nenhum hist�rico encontrado</h3>
                    <p class="text-gray-500">N�o h� solicita��es no per�odo selecionado</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Sidebar de Notifica��es -->
    <div id="notificationsModal" class="fixed inset-0 z-[99999] hidden transition-all duration-300">
        <!-- Overlay -->
        <div class="absolute inset-0 bg-black bg-opacity-50 backdrop-blur-sm" onclick="closeNotificationsModal()"></div>
        
        <!-- Sidebar -->
        <div class="absolute right-0 top-0 h-full w-full max-w-md bg-whiteshadow-2xl transform transition-transform duration-300 translate-x-full flex flex-col" id="notificationsModalContent">
            <!-- Header -->
            <div class="flex-shrink-0 p-6 bg-whiteborder-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 bg-forest-100 rounded-2xl flex items-center justify-center">
                            <svg class="w-7 h-7 text-forest-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-5 5v-5zM9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-black">Notifica��es</h3>
                            <p class="text-gray-600text-sm">Solicita��es e alertas do sistema</p>
                        </div>
                    </div>
                    <button onclick="closeNotificationsModal()" class="w-10 h-10 hover:bg-gray-100rounded-xl flex items-center justify-center transition-all">
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
                        <p class="text-gray-600">N�o h� notifica��es pendentes no momento</p>
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="flex-shrink-0 p-6 bg-whiteborder-t border-gray-200">
                <button onclick="closeNotificationsModal()" class="w-full px-4 py-3 bg-forest-600 hover:bg-forest-700 text-white rounded-xl transition-all font-medium shadow-lg hover:shadow-xl">
                    Fechar
                </button>
            </div>
        </div>
    </div>
    
    
<!-- ========== MAIS HTML MODALS ========== -->

    <!-- Modal PWA Full Screen - Estilo Xandria Store -->
    <div id="pwaModal" class="fixed inset-0 bg-gray-50 dark:bg-play-dark z-[9999] hidden dark">
        <div class="w-full h-full flex flex-col overflow-y-auto">
            <!-- Header do Modal - Estilo Xandria Store -->
            <header class="bg-whitedark:bg-play-dark no-border sticky top-0 z-50 backdrop-blur-sm bg-opacity-95 dark:bg-opacity-95">
                <div class="flex items-center justify-between px-6 py-4">
                    <div class="flex items-center space-x-4">
                        <div class="w-10 h-10 flex items-center justify-center">
                            <img src="assets/img/xandria-preta.png" alt="LacTech" class="w-8 h-8">
                        </div>
                        <div>
                            <h1 class="text-xl font-bold tracking-tight text-black dark:text-white">LacTech - Gerente</h1>
                            <p class="text-xs text-gray-500 dark:text-gray-400 -mt-1">Sistema Agropecu�rio</p>
                        </div>
                    </div>
                    <button onclick="closePWAModal()" class="w-10 h-10 hover:bg-gray-100dark:hover:bg-gray-800 rounded-xl flex items-center justify-center transition-colors">
                        <svg class="w-6 h-6 text-gray-600dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
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

            <!-- Conte�do do Modal -->
            <div class="flex-1 px-6 pb-6">
                <div class="max-w-4xl mx-auto">
                    <!-- Status da Instala��o - Layout Melhorado -->
                    <div class="bg-gradient-to-br from-green-50 to-emerald-100 dark:from-green-900/30 dark:to-emerald-900/30 rounded-2xl p-6 mb-6 border border-green-200 dark:border-green-800">
                        <div class="flex flex-col sm:flex-row items-center space-y-4 sm:space-y-0 sm:space-x-4 mb-4">
                            <div class="w-14 h-14 bg-gradient-to-br from-green-500 to-green-600 rounded-2xl flex items-center justify-center shadow-lg">
                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"></path>
                                </svg>
                            </div>
                            <div class="text-center sm:text-left">
                                <h3 class="text-lg font-bold text-gray-900dark:text-white">Instalar App LacTech</h3>
                                <p class="text-sm text-gray-600dark:text-gray-400">Acesso r�pido ao sistema</p>
                            </div>
                        </div>
                        
                        <div id="pwaStatus" class="space-y-3">
                            <!-- Status ser� preenchido dinamicamente -->
                        </div>
                    </div>

                    <!-- Informa��es do Sistema - Layout 2 Colunas -->
                    <div class="grid grid-cols-2 gap-4 mb-8">
                        <!-- Acesso Offline -->
                        <div class="bg-whitedark:bg-play-card rounded-2xl p-4 border border-gray-200dark:border-gray-700 hover:shadow-lg transition-all duration-300">
                            <div class="flex flex-col items-center text-center space-y-3">
                                <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-2xl flex items-center justify-center shadow-lg">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-900dark:text-white text-sm">Acesso Offline</h4>
                                    <p class="text-gray-600dark:text-gray-400 text-xs mt-1">Funcione sem internet</p>
                                </div>
                            </div>
                        </div>

                        <!-- Acesso R�pido -->
                        <div class="bg-whitedark:bg-play-card rounded-2xl p-4 border border-gray-200dark:border-gray-700 hover:shadow-lg transition-all duration-300">
                            <div class="flex flex-col items-center text-center space-y-3">
                                <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-2xl flex items-center justify-center shadow-lg">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-900dark:text-white text-sm">Acesso R�pido</h4>
                                    <p class="text-gray-600dark:text-gray-400 text-xs mt-1">Da tela inicial</p>
                                </div>
                            </div>
                        </div>

                        <!-- Interface Nativa -->
                        <div class="bg-whitedark:bg-play-card rounded-2xl p-4 border border-gray-200dark:border-gray-700 hover:shadow-lg transition-all duration-300">
                            <div class="flex flex-col items-center text-center space-y-3">
                                <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-2xl flex items-center justify-center shadow-lg">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-900dark:text-white text-sm">Interface Nativa</h4>
                                    <p class="text-gray-600dark:text-gray-400 text-xs mt-1">Experi�ncia nativa</p>
                                </div>
                            </div>
                        </div>

                        <!-- Seguran�a -->
                        <div class="bg-whitedark:bg-play-card rounded-2xl p-4 border border-gray-200dark:border-gray-700 hover:shadow-lg transition-all duration-300">
                            <div class="flex flex-col items-center text-center space-y-3">
                                <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-2xl flex items-center justify-center shadow-lg">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-900dark:text-white text-sm">Seguran�a</h4>
                                    <p class="text-gray-600dark:text-gray-400 text-xs mt-1">Dados protegidos</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bot�es de A��o - Layout Melhorado -->
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
                        
                        <button onclick="closePWAModal()" class="w-full px-6 py-3 bg-gray-100 dark:bg-gray-800 text-gray-700dark:text-gray-300 font-medium rounded-xl hover:bg-gray-200 dark:hover:bg-gray-700 transition-all border border-gray-200dark:border-gray-600">
                            Fechar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal de Relat�rios Full Screen -->
    <div id="reportsModal" class="fixed inset-0 bg-whitez-[99999] hidden flex flex-col" style="display: none;">
        <!-- Header -->
        <div class="flex-shrink-0 p-4 sm:p-6 bg-gradient-to-br from-forest-500 via-forest-600 to-forest-700 text-white shadow-2xl">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-whitebg-opacity-20 rounded-2xl flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold">Relat�rios</h1>
                        <p class="text-forest-100 text-sm">Gere relat�rios personalizados da sua fazenda</p>
                    </div>
                </div>
                <button onclick="closeReportsModal()" class="p-2 hover:bg-whitehover:bg-opacity-20 rounded-xl transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Conte�do -->
        <div class="flex-1 overflow-y-auto p-4 sm:p-6 bg-gray-50">
            <div class="max-w-6xl mx-auto">
                <!-- Grid de Relat�rios -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    
                    <!-- Relat�rio de Volume -->
                    <div class="bg-whiterounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 border border-gray-100">
                        <div class="flex items-center mb-4">
                            <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center mr-4">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-900">Relat�rio de Volume</h3>
                                <p class="text-sm text-gray-600">Produ��o de leite</p>
                            </div>
                        </div>
                        <p class="text-gray-600text-sm mb-4">Relat�rio detalhado da produ��o de leite com volumes, datas e funcion�rios respons�veis.</p>
                        <button onclick="generateVolumeReport()" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-xl transition-colors">
                            Gerar PDF
                        </button>
                    </div>

                    <!-- Relat�rio de Qualidade -->
                    <div class="bg-whiterounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 border border-gray-100">
                        <div class="flex items-center mb-4">
                            <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center mr-4">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-900">Relat�rio de Qualidade</h3>
                                <p class="text-sm text-gray-600">Testes de qualidade</p>
                            </div>
                        </div>
                        <p class="text-gray-600text-sm mb-4">An�lise de gordura, prote�na, CCS e CBT do leite produzido.</p>
                        <button onclick="generateQualityReport()" class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-4 rounded-xl transition-colors">
                            Gerar PDF
                        </button>
                    </div>

                    <!-- Relat�rio Financeiro -->
                    <div class="bg-whiterounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 border border-gray-100">
                        <div class="flex items-center mb-4">
                            <div class="w-12 h-12 bg-yellow-100 rounded-xl flex items-center justify-center mr-4">
                                <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-900">Relat�rio Financeiro</h3>
                                <p class="text-sm text-gray-600">Pagamentos e receitas</p>
                            </div>
                        </div>
                        <p class="text-gray-600text-sm mb-4">Relat�rio de pagamentos, receitas e movimenta��o financeira da fazenda.</p>
                        <button onclick="generateFinancialReport()" class="w-full bg-yellow-600 hover:bg-yellow-700 text-white font-semibold py-3 px-4 rounded-xl transition-colors">
                            Gerar PDF
                        </button>
                    </div>


                    <!-- Relat�rio Personalizado -->
                    <div class="bg-whiterounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 border border-gray-100">
                        <div class="flex items-center mb-4">
                            <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center mr-4">
                                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-900">Relat�rio Personalizado</h3>
                                <p class="text-sm text-gray-600">Crie seu pr�prio relat�rio</p>
                            </div>
                        </div>
                        <p class="text-gray-600text-sm mb-4">Crie relat�rios personalizados com os dados que voc� precisa.</p>
                        <button onclick="openCustomReportModal()" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 px-4 rounded-xl transition-colors">
                            Configurar
                        </button>
                    </div>

                </div>

                <!-- Informa��es Adicionais -->
                <div class="mt-8 bg-whiterounded-2xl p-6 shadow-lg border border-gray-100">
                    <h3 class="text-lg font-bold text-gray-900mb-4">Informa��es sobre os Relat�rios</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h4 class="font-semibold text-gray-800 mb-2">?? Dados Inclu�dos</h4>
                            <ul class="text-sm text-gray-600space-y-1">
                                <li>� �ltimos 100 registros</li>
                                <li>� Logos da fazenda e sistema</li>
                                <li>� Resumos estat�sticos</li>
                                <li>� Tabelas detalhadas</li>
                            </ul>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-800 mb-2">?? Configura��es</h4>
                            <ul class="text-sm text-gray-600space-y-1">
                                <li>� Formato PDF profissional</li>
                                <li>� Marca d'�gua personalizada</li>
                                <li>� Download autom�tico</li>
                                <li>� Compat�vel com impress�o</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Relat�rio Personalizado -->
    <div id="customReportModal" class="fixed inset-0 bg-whitez-[99999] hidden flex flex-col" style="display: none;">
        <!-- Header -->
        <div class="flex-shrink-0 p-4 sm:p-6 bg-gradient-to-br from-indigo-500 via-indigo-600 to-indigo-700 text-white shadow-2xl">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-whitebg-opacity-20 rounded-2xl flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold">Relat�rio Personalizado</h1>
                        <p class="text-indigo-100 text-sm">Configure seu relat�rio com logo personalizada</p>
                    </div>
                </div>
                <button onclick="closeCustomReportModal()" class="p-2 hover:bg-whitehover:bg-opacity-20 rounded-xl transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Conte�do -->
        <div class="flex-1 overflow-y-auto p-4 sm:p-6 bg-gray-50">
            <div class="max-w-4xl mx-auto">
                <div class="bg-whiterounded-2xl p-6 shadow-lg border border-gray-100">
                    <h3 class="text-xl font-bold text-gray-900mb-6">Configura��es do Relat�rio</h3>
                    
                    <!-- Nome da Fazenda -->
                    <div class="mb-6">
                        <label class="block text-sm font-semibold text-gray-700mb-2">
                            Nome da Fazenda
                        </label>
                        <input type="text" id="customReportFarmName" 
                               class="w-full px-4 py-3 border border-gray-300rounded-xl focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 focus:outline-none bg-whiteshadow-sm transition-all" 
                               placeholder="Digite o nome da fazenda">
                    </div>

                    <!-- Upload da Logo -->
                    <div class="mb-6">
                        <label class="block text-sm font-semibold text-gray-700mb-3">
                            Logo da Fazenda
                        </label>
                        
                        <!-- Preview da Logo -->
                        <div id="customReportLogoPreview" class="hidden mb-4">
                            <div class="relative inline-block">
                                <img id="customReportLogoImage" src="" alt="Logo da Fazenda" 
                                     class="w-32 h-32 object-contain border-2 border-gray-200rounded-xl shadow-sm">
                                <button id="removeCustomReportLogo" onclick="removeCustomReportLogo()" 
                                        class="absolute -top-2 -right-2 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center hover:bg-red-600 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Placeholder -->
                        <div id="customReportLogoPlaceholder" class="border-2 border-dashed border-gray-300rounded-xl p-8 text-center hover:border-indigo-400 transition-colors">
                            <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <p class="text-gray-500 text-sm mb-4">Clique para fazer upload da logo da fazenda</p>
                            <button onclick="document.getElementById('customReportLogoUpload').click()" 
                                    class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
                                Selecionar Imagem
                            </button>
                            <input type="file" id="customReportLogoUpload" accept="image/*" class="hidden" onchange="handleCustomReportLogoUpload(event)">
                        </div>
                        
                        <p class="text-xs text-gray-500 mt-2">
                            Formatos aceitos: JPG, PNG, GIF. Tamanho m�ximo: 2MB
                        </p>
                    </div>

                    <!-- Informa��es do Relat�rio -->
                    <div class="mb-6 p-4 bg-blue-50 rounded-xl">
                        <h4 class="font-semibold text-blue-900 mb-2">?? Sobre o Relat�rio Personalizado</h4>
                        <ul class="text-sm text-blue-800 space-y-1">
                            <li>� Inclui dados de produ��o dos �ltimos 50 registros</li>
                            <li>� Logo da fazenda aparece no cabe�alho e como marca d'�gua</li>
                            <li>� Nome da fazenda personalizado no t�tulo</li>
                            <li>� Formato PDF profissional pronto para impress�o</li>
                        </ul>
                    </div>

                    <!-- Bot�es de A��o -->
                    <div class="flex flex-col sm:flex-row gap-4">
                        <button onclick="saveCustomReportSettings()" 
                                class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 px-6 rounded-xl transition-colors">
                            Salvar Configura��es
                        </button>
                        <button onclick="generateCustomReport()" 
                                class="flex-1 bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-6 rounded-xl transition-colors">
                            Gerar Relat�rio
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Loading Personalizado -->
    <div id="customLoadingModal" class="fixed inset-0 bg-black bg-opacity-50 z-[99999] hidden flex items-center justify-center" style="display: none;">
        <div class="bg-whiterounded-2xl p-8 shadow-2xl max-w-sm w-full mx-4">
            <div class="text-center">
                <!-- �cone de Loading -->
                <div id="customLoadingIcon" class="flex justify-center mb-4">
                    <svg class="w-8 h-8 text-indigo-600 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                </div>
                
                <!-- Mensagem -->
                <h3 id="customLoadingMessage" class="text-lg font-semibold text-gray-900mb-2">
                    Salvando configura��es...
                </h3>
                
                <!-- Barra de Progresso -->
                <div class="w-full bg-gray-200 rounded-full h-2 mb-4">
                    <div class="bg-indigo-600 h-2 rounded-full animate-pulse" style="width: 100%"></div>
                </div>
                
                <!-- Texto de Ajuda -->
                <p class="text-sm text-gray-500">
                    Por favor, aguarde...
                </p>
            </div>
        </div>
    </div>

    <!-- Modal Adicionar Animal - Removido completamente (usando modal din�mico) -->

    <!-- Modal Adicionar Tratamento -->
    <div id="addTreatmentModal" class="modal hidden">
        <div class="modal-content">
            <div class="sticky top-0 bg-whiteborder-b border-gray-200px-6 py-4 z-10">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Registrar Tratamento</h3>
                            <p class="text-sm text-gray-500">Registre um tratamento para um animal</p>
                        </div>
                    </div>
                    <button onclick="closeModal('addTreatmentModal')" class="p-2 hover:bg-gray-100rounded-xl transition-colors">
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            
            <div class="p-6">
                <form id="addTreatmentForm" class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700mb-2">Animal</label>
                        <select name="animal_id" required class="w-full px-3 py-2 border border-gray-300rounded-lg focus:border-blue-500 focus:outline-none">
                            <option value="">Selecione o animal</option>
                        </select>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700mb-2">Tipo de Tratamento</label>
                            <select name="treatment_type" required class="w-full px-3 py-2 border border-gray-300rounded-lg focus:border-blue-500 focus:outline-none">
                                <option value="">Selecione o tipo</option>
                                <option value="Medicamento">Medicamento</option>
                                <option value="Vacina��o">Vacina��o</option>
                                <option value="Vermifuga��o">Vermifuga��o</option>
                                <option value="Suplementa��o">Suplementa��o</option>
                                <option value="Cirurgia">Cirurgia</option>
                                <option value="Outros">Outros</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700mb-2">Medicamento</label>
                            <input type="text" name="medication" class="w-full px-3 py-2 border border-gray-300rounded-lg focus:border-blue-500 focus:outline-none" placeholder="Nome do medicamento">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700mb-2">Data do Tratamento</label>
                        <input type="date" name="treatment_date" required class="w-full px-3 py-2 border border-gray-300rounded-lg focus:border-blue-500 focus:outline-none">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700mb-2">Observa��es</label>
                        <textarea name="observations" rows="3" class="w-full px-3 py-2 border border-gray-300rounded-lg focus:border-blue-500 focus:outline-none" placeholder="Observa��es sobre o tratamento"></textarea>
                    </div>
                    
                    <div class="flex justify-end space-x-3 pt-4">
                        <button type="button" onclick="closeModal('addTreatmentModal')" class="px-4 py-2 border border-gray-300text-gray-700rounded-lg hover:bg-gray-50transition-colors">
                            Cancelar
                        </button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            Registrar Tratamento
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Adicionar Insemina��o -->
    <div id="addInseminationModal" class="modal hidden">
        <div class="modal-content">
            <div class="sticky top-0 bg-whiteborder-b border-gray-200px-6 py-4 z-10">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-purple-100 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Registrar Insemina��o</h3>
                            <p class="text-sm text-gray-500">Registre uma insemina��o artificial</p>
                        </div>
                    </div>
                    <button onclick="closeModal('addInseminationModal')" class="p-2 hover:bg-gray-100rounded-xl transition-colors">
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            
            <div class="p-6">
                <form id="addInseminationForm" class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700mb-2">Animal</label>
                        <select name="animal_id" required class="w-full px-3 py-2 border border-gray-300rounded-lg focus:border-purple-500 focus:outline-none">
                            <option value="">Selecione o animal</option>
                        </select>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700mb-2">Data da Insemina��o</label>
                            <input type="date" name="insemination_date" required class="w-full px-3 py-2 border border-gray-300rounded-lg focus:border-purple-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700mb-2">Lote do S�men</label>
                            <input type="text" name="semen_batch" required class="w-full px-3 py-2 border border-gray-300rounded-lg focus:border-purple-500 focus:outline-none" placeholder="N�mero do lote">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700mb-2">T�cnico Respons�vel</label>
                        <input type="text" name="technician" class="w-full px-3 py-2 border border-gray-300rounded-lg focus:border-purple-500 focus:outline-none" placeholder="Nome do t�cnico">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700mb-2">Observa��es</label>
                        <textarea name="observations" rows="3" class="w-full px-3 py-2 border border-gray-300rounded-lg focus:border-purple-500 focus:outline-none" placeholder="Observa��es sobre a insemina��o"></textarea>
                    </div>
                    
                    <div class="flex justify-end space-x-3 pt-4">
                        <button type="button" onclick="closeModal('addInseminationModal')" class="px-4 py-2 border border-gray-300text-gray-700rounded-lg hover:bg-gray-50transition-colors">
                            Cancelar
                        </button>
                        <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                            Registrar Insemina��o
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Registro Individual por Vaca -->
    <div id="individualVolumeModal" class="modal hidden">
        <div class="modal-content">
            <div class="sticky top-0 bg-whiteborder-b border-gray-200px-6 py-4 z-10">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Registro Individual por Vaca</h3>
                            <p class="text-sm text-gray-500">Registre a produ��o de cada animal individualmente</p>
                        </div>
                    </div>
                    <button onclick="closeModal('individualVolumeModal')" class="p-2 hover:bg-gray-100rounded-xl transition-colors">
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            
            <div class="p-6">
                <form id="individualVolumeForm" class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700mb-2">Vaca</label>
                        <select name="animal_id" required class="w-full px-3 py-2 border border-gray-300rounded-lg focus:border-blue-500 focus:outline-none">
                            <option value="">Selecione a vaca</option>
                        </select>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700mb-2">Data da Ordenha</label>
                            <input type="date" name="production_date" required class="w-full px-3 py-2 border border-gray-300rounded-lg focus:border-blue-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700mb-2">Hor�rio</label>
                            <select name="milking_time" required class="w-full px-3 py-2 border border-gray-300rounded-lg focus:border-blue-500 focus:outline-none">
                                <option value="">Selecione o hor�rio</option>
                                <option value="manha">Manh� (05:00-07:00)</option>
                                <option value="tarde">Tarde (15:00-17:00)</option>
                                <option value="noite">Noite (19:00-21:00)</option>
                            </select>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700mb-2">Volume Produzido (Litros)</label>
                        <input type="number" name="volume" step="0.1" min="0" required class="w-full px-3 py-2 border border-gray-300rounded-lg focus:border-blue-500 focus:outline-none" placeholder="Ex: 25.5">
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700mb-2">Respons�vel pela Ordenha</label>
                            <input type="text" name="milker" class="w-full px-3 py-2 border border-gray-300rounded-lg focus:border-blue-500 focus:outline-none" placeholder="Nome do respons�vel">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700mb-2">Temperatura do Leite (�C)</label>
                            <input type="number" name="temperature" step="0.1" class="w-full px-3 py-2 border border-gray-300rounded-lg focus:border-blue-500 focus:outline-none" placeholder="Ex: 37.5">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700mb-2">Observa��es</label>
                        <textarea name="observations" rows="3" class="w-full px-3 py-2 border border-gray-300rounded-lg focus:border-blue-500 focus:outline-none" placeholder="Observa��es sobre a ordenha (ex: comportamento do animal, problemas, etc.)"></textarea>
                    </div>
                    
                    <div class="flex justify-end space-x-3 pt-4">
                        <button type="button" onclick="closeModal('individualVolumeModal')" class="px-4 py-2 border border-gray-300text-gray-700rounded-lg hover:bg-gray-50transition-colors">
                            Cancelar
                        </button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            Registrar Produ��o
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<!-- ==================== SCRIPT PRINCIPAL ==================== -->
<!-- GERENTE.JS - DEVE SER CARREGADO POR ÚLTIMO (após todas as dependências) -->
<script src="assets/js/gerente.js"></script>

</body>
</html>
