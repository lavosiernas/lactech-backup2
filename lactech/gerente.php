<?php
/**
 * Dashboard Gerente - LacTech
 * Requer autenticação e papel de gerente
 */

// Incluir configuração e iniciar sessão
require_once __DIR__ . '/includes/config_login.php';

// Verificar se está logado
if (!isLoggedIn()) {
    // Não está logado, redirecionar para index
    header("Location: index.php", true, 302);
    exit();
}

// Verificar se tem o papel correto
if ($_SESSION['user_role'] !== 'gerente' && $_SESSION['user_role'] !== 'manager') {
    // Papel incorreto, redirecionar para o dashboard correto
    switch ($_SESSION['user_role']) {
        case 'proprietario':
        case 'owner':
            header("Location: proprietario.php", true, 302);
            exit();
        case 'veterinario':
        case 'veterinarian':
            header("Location: veterinario.php", true, 302);
            exit();
        case 'funcionario':
        case 'employee':
            header("Location: funcionario.php", true, 302);
            exit();
        default:
            // Papel desconhecido, fazer logout
            session_destroy();
            header("Location: index.php", true, 302);
            exit();
    }
}

// Usuário autenticado e com papel correto, continuar...
?>
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
        .fullscreen-modal:not(.modal-enabled):not(.show) {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
            pointer-events: none !important;
            z-index: -1 !important;
        }
        
        /* REMOVIDO - Não bloquear conteúdo do modal */
        
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
        #moreModal,
        #managerPhotoChoiceModal,
        #managerCameraModal,
        #contactsModal,
        #notificationsModal,
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
        
        /* CORREÇÃO: Garantir que modais funcionem corretamente */
        .fullscreen-modal, .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 99999;
        }
        
        /* Modais visíveis (sem classe hidden) */
        .fullscreen-modal:not(.hidden), .modal:not(.hidden) {
            display: flex !important;
            align-items: center;
            justify-content: center;
        }
        
        /* Fundo escuro para modais */
        .fullscreen-modal:not(.hidden)::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: -1;
        }
        
        /* Modal content */
        .modal-content {
            position: relative;
            background: white;
            border-radius: 12px;
            max-width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            z-index: 1;
        }
        
        /* Garantir que modais com bg-white apareçam */
        [id*="Modal"].bg-white:not(.hidden) {
            background-color: white !important;
        }
        
        /* Garantir que modais com backdrop apareçam corretamente */
        [id*="Modal"].bg-black.bg-opacity-50:not(.hidden) {
            background-color: rgba(0, 0, 0, 0.5) !important;
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
                    
                    
                    
                    <button onclick="openProfileOverlay()" class="flex items-center space-x-3 text-white hover:text-forest-200 p-2 rounded-lg transition-all" id="profileButton">
                        <div class="relative w-8 h-8">
                            <!-- Foto do usuário -->
                            <img id="headerProfilePhoto" src="" alt="Foto de Perfil" class="w-8 h-8 object-cover rounded-full hidden">
                            <!-- ícone padrão -->
                            <div id="headerProfileIcon" class="w-8 h-8 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
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
    <div id="addUserModal" class="fullscreen-modal hidden">
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
    <div id="editUserModal" class="fullscreen-modal hidden">
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
                                    <p class="text-sm text-blue-700">• O usuário será notificado sobre as mudanças por WhatsApp</p>
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
    <div id="photoChoiceModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-[99999]">
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

    <!-- Modal de C�mera do Gerente -->
    <div id="managerCameraModal" class="fullscreen-modal hidden">
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
        
        /* Garantir que o modal de notificações seja exibido corretamente */
        #notificationsModal.flex {
            display: flex !important;
            visibility: visible !important;
            opacity: 1 !important;
            pointer-events: auto !important;
            position: fixed !important;
            z-index: 99999 !important;
        }
        
        #notificationsModal.hidden {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
            pointer-events: none !important;
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
        
        /* Responsividade para telas pequenas */
        @media (max-width: 640px) {
            #secondaryAccountsList > div {
                padding: 0.75rem;
            }
            
            #secondaryAccountsList .flex-col {
                gap: 0.5rem;
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
    <div id="managerPhotoChoiceModal" class="fixed inset-0 bg-black bg-opacity-50 items-center justify-center z-[999999] hidden">
        <div class="bg-white rounded-2xl p-6 w-full max-w-md mx-4">
            <div class="flex items-center justify-between mb-6">
                                            <h2 class="text-xl font-bold text-gray-900">Mudar Foto de Perfil</h2>
                <button onclick="closeManagerPhotoModal()" class="p-2 hover:bg-gray-100 rounded-lg transition-all">
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
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Como você quer adicionar sua foto?</h3>
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
    <div id="contactsModal" class="fixed inset-0 bg-white z-[99999] hidden flex flex-col">
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
    <div id="contactFormModal" class="fixed inset-0 bg-black bg-opacity-50 z-[99999] hidden flex items-center justify-center">
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
    
    <!-- Modal de Solicitações de Senha - Full Screen -->
    <div id="passwordRequestsModal" class="fixed inset-0 bg-white z-[99999] hidden flex flex-col">
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
                            <h2 class="text-lg sm:text-xl font-bold mb-1">Solicitações de Senha</h2>
                            <p class="text-forest-100 text-xs sm:text-sm">Gerencie solicitações de alteração e redefinição de senha</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <!-- Bot�o de Hist�rico -->
                        <button onclick="openPasswordHistoryModal()" class="w-10 h-10 sm:w-12 sm:h-12 lg:w-14 lg:h-14 flex items-center justify-center hover:bg-white hover:bg-opacity-20 rounded-xl sm:rounded-2xl transition-all duration-200" title="Ver histórico de solicitações">
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
                        <select id="passwordRequestFilter" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none bg-white text-gray-900">
                            <option value="all">Todas as solicitações</option>
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
                
                <!-- Lista de Solicitações -->
                <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                        <h3 class="text-lg font-semibold text-gray-900">Lista de Solicitações</h3>
                        <p class="text-sm text-gray-600">Gerencie todas as solicitações de alteração de senha</p>
                    </div>
                    
                    <div class="p-6">
                        <div class="space-y-4" id="passwordRequestsList">
                            <!-- Solicitações serão carregadas aqui -->
                        </div>
                        
                        <!-- Estado vazio -->
                        <div id="emptyPasswordRequests" class="text-center py-16 hidden">
                            <div class="w-20 h-20 bg-gradient-to-br from-forest-100 to-forest-200 rounded-full flex items-center justify-center mx-auto mb-6">
                                <svg class="w-10 h-10 text-forest-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-900 mb-3">Nenhuma Solicitação</h3>
                            <p class="text-gray-600 mb-6">Não há solicitações de senha no momento.</p>
                            <div class="w-32 h-1 bg-gradient-to-r from-forest-400 to-forest-600 rounded-full mx-auto"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal de Detalhes da Solicita��o -->
    <div id="passwordRequestDetailsModal" class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center z-[99999] hidden">
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
    
    <!-- Modal de Histórico de Solicitações de Senha -->
    <div id="passwordHistoryModal" class="fixed inset-0 bg-white z-[99999] hidden flex flex-col">
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
                        <h2 class="text-lg sm:text-xl font-bold mb-1">Histórico de Solicitações</h2>
                        <p class="text-forest-100 text-xs sm:text-sm">Visualize todas as solicitações processadas nos últimos 30 dias</p>
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
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Nenhum histórico encontrado</h3>
                    <p class="text-gray-500">Não há solicitações no período selecionado</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Sidebar de Notificações -->
    <div id="notificationsModal" class="fixed inset-0 z-[99999] hidden flex-col transition-all duration-300">
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
    <div id="reportsModal" class="fixed inset-0 bg-white z-[99999] hidden flex flex-col">
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
    <div id="customReportModal" class="fixed inset-0 bg-white z-[99999] hidden flex flex-col">
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
    <div id="customLoadingModal" class="fixed inset-0 bg-black bg-opacity-50 z-[99999] hidden flex items-center justify-center">
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

<!-- Script para esconder a tela de carregamento -->
<script>
    // Esconder tela de carregamento quando a página carregar
    window.addEventListener('load', function() {
        const loadingScreen = document.getElementById('loadingScreen');
        const mainBody = document.getElementById('mainBody');
        
        if (loadingScreen) {
            // Adicionar fade-out
            loadingScreen.classList.add('fade-out');
            
            // Remover após animação
            setTimeout(function() {
                loadingScreen.classList.add('hidden');
                if (mainBody) {
                    mainBody.classList.add('loaded');
                }
            }, 500);
        }
    });
    
    // Garantir que esconda mesmo se houver erro
    setTimeout(function() {
        const loadingScreen = document.getElementById('loadingScreen');
        if (loadingScreen && !loadingScreen.classList.contains('hidden')) {
            loadingScreen.classList.add('fade-out');
            setTimeout(function() {
                loadingScreen.classList.add('hidden');
                const mainBody = document.getElementById('mainBody');
                if (mainBody) {
                    mainBody.classList.add('loaded');
                }
            }, 500);
        }
    }, 5000); // Forçar esconder após 5 segundos no máximo
</script>

<!-- Script de controle de modais -->
<script>
    // Funções globais para controle de modais
    window.openModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            console.log('✅ Modal aberto:', modalId);
        } else {
            console.error('❌ Modal não encontrado:', modalId);
        }
    };

    window.closeModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            modal.style.display = 'none';
            document.body.style.overflow = '';
            console.log('✅ Modal fechado:', modalId);
        }
    };

    // Funções específicas para modais com nomes customizados
    window.closeAddUserModal = function() {
        closeModal('addUserModal');
    };

    window.closeEditUserModal = function() {
        closeModal('editUserModal');
    };

    // window.closeProfileModal está definido em gerente.js
    
    // FUNÇÕES DO OVERLAY DE PERFIL (SEM RECARREGAMENTO)
    window.openProfileOverlay = function() {
        console.log('🔵 ABRINDO OVERLAY DE PERFIL...');
        
        const overlay = document.getElementById('profileOverlay');
        if (!overlay) {
            console.error('❌ Overlay não encontrado!');
            return;
        }
        
        // Mostrar overlay com animação
        overlay.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        
        // Carregar dados do usuário
        loadProfileOverlayData();
        
        console.log('✅ Overlay aberto com sucesso!');
    };

    window.closeProfileOverlay = function() {
        console.log('🔴 FECHANDO OVERLAY DE PERFIL...');
        
        const overlay = document.getElementById('profileOverlay');
        if (overlay) {
            overlay.classList.add('hidden');
            document.body.style.overflow = '';
            
            console.log('✅ Overlay fechado com sucesso!');
        }
    };

    function loadProfileOverlayData() {
        try {
            console.log('📊 Carregando dados do usuário no overlay...');
            
            // Buscar dados do usuário
            const userData = localStorage.getItem('user_data') || 
                            sessionStorage.getItem('user_data') || 
                            localStorage.getItem('userData') || 
                            sessionStorage.getItem('userData');
            
            if (userData) {
                const user = JSON.parse(userData);
                console.log('👤 Dados encontrados:', user);
                
                // Atualizar nome no header
                const nameElement = document.getElementById('overlayProfileName');
                if (nameElement) {
                    nameElement.textContent = user.name || user.nome || 'Usuário';
                }
                
                // Atualizar cargo
                const roleElement = document.getElementById('overlayProfileRole');
                if (roleElement) {
                    roleElement.textContent = user.role || user.cargo || 'Gerente';
                }
                
                // Atualizar fazenda
                const farmElement = document.getElementById('overlayProfileFarmName');
                if (farmElement) {
                    farmElement.textContent = user.farm_name || user.fazenda || 'Fazenda';
                }
                
                // Atualizar nome completo
                const fullNameElement = document.getElementById('overlayProfileFullName');
                if (fullNameElement) {
                    fullNameElement.textContent = user.name || user.nome || 'Usuário';
                }
                
                // Atualizar email
                const emailElement = document.getElementById('overlayProfileEmail');
                if (emailElement) {
                    emailElement.textContent = user.email || 'Não informado';
                }
                
                // Atualizar WhatsApp
                const whatsappElement = document.getElementById('overlayProfileWhatsApp');
                if (whatsappElement) {
                    whatsappElement.textContent = user.whatsapp || user.phone || 'Não informado';
                }
                
                // Atualizar foto
                if (user.profile_photo_url && user.profile_photo_url !== 'null' && user.profile_photo_url !== '') {
                    const photoElement = document.getElementById('overlayProfilePhoto');
                    const iconElement = document.getElementById('overlayProfileIcon');
                    
                    if (photoElement && iconElement) {
                        photoElement.src = user.profile_photo_url;
                        photoElement.classList.remove('hidden');
                        iconElement.classList.add('hidden');
                    }
                } else {
                    // Garantir que o ícone está visível
                    const photoElement = document.getElementById('overlayProfilePhoto');
                    const iconElement = document.getElementById('overlayProfileIcon');
                    
                    if (photoElement && iconElement) {
                        photoElement.classList.add('hidden');
                        iconElement.classList.remove('hidden');
                    }
                }
                
                console.log('✅ Dados carregados no overlay com sucesso!');
            } else {
                console.log('⚠️ Nenhum dado de usuário encontrado');
            }
            
        } catch (error) {
            console.error('❌ Erro ao carregar dados no overlay:', error);
        }
    }

    // Fechar overlay com tecla ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const profileOverlay = document.getElementById('profileOverlay');
            const animalOverlay = document.getElementById('animalOverlay');
            const healthOverlay = document.getElementById('healthOverlay');
            const reproductionOverlay = document.getElementById('reproductionOverlay');
            const analyticsOverlay = document.getElementById('analyticsOverlay');
            
            if (profileOverlay && !profileOverlay.classList.contains('hidden')) {
                closeProfileOverlay();
            } else if (animalOverlay && !animalOverlay.classList.contains('hidden')) {
                closeAnimalOverlay();
            } else if (healthOverlay && !healthOverlay.classList.contains('hidden')) {
                closeHealthOverlay();
            } else if (reproductionOverlay && !reproductionOverlay.classList.contains('hidden')) {
                closeReproductionOverlay();
            } else if (analyticsOverlay && !analyticsOverlay.classList.contains('hidden')) {
                closeAnalyticsOverlay();
            }
        }
    });

    // ==================== OVERLAYS PARA GESTÃO ====================
    
    // Cache de animais
    let animalsCache = [];
    let animalsFilteredCache = [];
    
    // Gestão de Rebanho
    window.showAnimalManagement = function() {
        console.log('🐄 Abrindo Gestão de Rebanho...');
        
        const overlay = document.getElementById('animalOverlay');
        if (!overlay) {
            console.error('❌ Overlay de Gestão de Rebanho não encontrado!');
            return;
        }
        
        overlay.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        
        // Carregar dados dos animais
        loadAnimalsData();
        
        console.log('✅ Overlay de Gestão de Rebanho aberto!');
    };
    
    // Carregar dados dos animais
    async function loadAnimalsData() {
        try {
            console.log('📡 Carregando animais...');
            
            const response = await fetch('api/animals.php?action=get_all');
            
            console.log('🔍 Status HTTP:', response.status);
            console.log('🔍 Status Text:', response.statusText);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const text = await response.text();
            console.log('📄 Resposta bruta:', text);
            
            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                console.error('❌ Erro ao fazer parse do JSON:', e);
                throw new Error('Resposta inválida da API: ' + text.substring(0, 100));
            }
            
            console.log('📦 Resposta da API:', data);
            
            if (data.success && data.data) {
                animalsCache = data.data;
                animalsFilteredCache = data.data;
                
                console.log(`✅ ${data.data.length} animais carregados!`);
                
                // Atualizar estatísticas
                updateAnimalStats(data.data);
                
                // Renderizar tabela
                renderAnimalsTable(data.data);
            } else {
                throw new Error(data.error || 'Nenhum dado retornado pela API');
            }
        } catch (error) {
            console.error('❌ Erro ao carregar animais:', error);
            console.error('Stack:', error.stack);
            
            const tbody = document.getElementById('animalTableBody');
            if (tbody) {
                const errorMsg = error.message || 'Erro desconhecido';
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" class="px-3 py-8 text-center text-red-500">
                            <div class="flex flex-col items-center space-y-2">
                                <svg class="w-12 h-12 text-red-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <p class="text-sm font-medium">Erro ao carregar animais</p>
                                <p class="text-xs">${errorMsg}</p>
                                <button onclick="loadAnimalsData()" class="mt-2 px-3 py-1 bg-red-600 hover:bg-red-700 text-white text-xs rounded-lg">
                                    Tentar Novamente
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            }
            
            // Resetar contadores
            document.getElementById('animalTotalCount').textContent = '0';
            document.getElementById('animalLactantCount').textContent = '0';
            document.getElementById('animalSecaCount').textContent = '0';
            document.getElementById('animalNovilhaCount').textContent = '0';
        }
    }
    
    // Atualizar estatísticas
    function updateAnimalStats(animals) {
        if (!animals || !Array.isArray(animals)) {
            console.warn('⚠️ updateAnimalStats: animals inválido', animals);
            return;
        }
        
        const total = animals.length;
        const lactantes = animals.filter(a => a.status === 'Lactante').length;
        const secas = animals.filter(a => a.status === 'Seco').length;
        const novilhas = animals.filter(a => a.status === 'Novilha').length;
        
        const totalEl = document.getElementById('animalTotalCount');
        const lactantEl = document.getElementById('animalLactantCount');
        const secaEl = document.getElementById('animalSecaCount');
        const novilhaEl = document.getElementById('animalNovilhaCount');
        
        if (totalEl) totalEl.textContent = total;
        if (lactantEl) lactantEl.textContent = lactantes;
        if (secaEl) secaEl.textContent = secas;
        if (novilhaEl) novilhaEl.textContent = novilhas;
        
        console.log(`📊 Stats: Total=${total}, Lactantes=${lactantes}, Secas=${secas}, Novilhas=${novilhas}`);
    }
    
    // Renderizar tabela de animais
    function renderAnimalsTable(animals) {
        const tbody = document.getElementById('animalTableBody');
        if (!tbody) return;
        
        if (animals.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="px-3 py-8 text-center text-gray-500">
                        <div class="flex flex-col items-center space-y-2">
                            <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                            </svg>
                            <p class="text-sm">Nenhum animal encontrado</p>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }
        
        tbody.innerHTML = animals.map(animal => {
            const age = calculateAge(animal.birth_date);
            const statusColor = getStatusColor(animal.status);
            
            return `
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-3 py-2 font-medium text-gray-900">${animal.animal_number}</td>
                    <td class="px-3 py-2 text-gray-700">${animal.name || '-'}</td>
                    <td class="px-3 py-2 text-gray-600">${animal.breed}</td>
                    <td class="px-3 py-2">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ${statusColor}">
                            ${animal.status}
                        </span>
                    </td>
                    <td class="px-3 py-2 text-gray-600">${age}</td>
                    <td class="px-3 py-2">
                        <button onclick="viewAnimalDetails(${animal.id})" class="text-emerald-600 hover:text-emerald-700 font-medium text-xs">
                            Ver Detalhes
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    }
    
    // Calcular idade
    function calculateAge(birthDate) {
        if (!birthDate) return '-';
        
        const birth = new Date(birthDate);
        const today = new Date();
        const diffTime = Math.abs(today - birth);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        
        if (diffDays < 30) {
            return `${diffDays}d`;
        } else if (diffDays < 365) {
            const months = Math.floor(diffDays / 30);
            return `${months}m`;
        } else {
            const years = Math.floor(diffDays / 365);
            const months = Math.floor((diffDays % 365) / 30);
            return `${years}a ${months}m`;
        }
    }
    
    // Cor do status
    function getStatusColor(status) {
        const colors = {
            'Lactante': 'bg-blue-100 text-blue-800',
            'Seco': 'bg-amber-100 text-amber-800',
            'Novilha': 'bg-purple-100 text-purple-800',
            'Vaca': 'bg-green-100 text-green-800',
            'Bezerra': 'bg-pink-100 text-pink-800',
            'Bezerro': 'bg-indigo-100 text-indigo-800',
            'Touro': 'bg-gray-100 text-gray-800'
        };
        return colors[status] || 'bg-gray-100 text-gray-800';
    }
    
    // Buscar animais
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('animalSearchInput');
        const filterStatus = document.getElementById('animalFilterStatus');
        
        if (searchInput) {
            searchInput.addEventListener('input', filterAnimals);
        }
        
        if (filterStatus) {
            filterStatus.addEventListener('change', filterAnimals);
        }
    });
    
    function filterAnimals() {
        const searchTerm = document.getElementById('animalSearchInput')?.value.toLowerCase() || '';
        const statusFilter = document.getElementById('animalFilterStatus')?.value || '';
        
        let filtered = animalsCache;
        
        // Filtrar por busca
        if (searchTerm) {
            filtered = filtered.filter(animal => 
                animal.animal_number.toLowerCase().includes(searchTerm) ||
                (animal.name && animal.name.toLowerCase().includes(searchTerm))
            );
        }
        
        // Filtrar por status
        if (statusFilter) {
            filtered = filtered.filter(animal => animal.status === statusFilter);
        }
        
        animalsFilteredCache = filtered;
        renderAnimalsTable(filtered);
    }
    
    // Mostrar formulário de adicionar animal
    window.showAddAnimalForm = function() {
        const modal = document.createElement('div');
        modal.id = 'addAnimalModal';
        modal.className = 'fixed inset-0 bg-black bg-opacity-60 z-[999999] flex items-center justify-center p-4';
        modal.innerHTML = `
            <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-hidden">
                <!-- Header -->
                <div class="bg-gradient-to-r from-emerald-600 to-teal-600 px-4 py-3 flex items-center justify-between">
                    <h3 class="text-base font-bold text-white">Adicionar Novo Animal</h3>
                    <button onclick="closeAddAnimalModal()" class="text-white hover:bg-white hover:bg-opacity-20 p-1 rounded transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                
                <!-- Form -->
                <form id="addAnimalForm" class="p-4 overflow-y-auto max-h-[calc(90vh-120px)]">
                    <div class="space-y-3">
                        <!-- Número do Animal -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Número do Animal *</label>
                            <input type="text" name="animal_number" required class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent" placeholder="Ex: V001, B001">
                        </div>
                        
                        <!-- Nome -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Nome</label>
                            <input type="text" name="name" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent" placeholder="Nome do animal (opcional)">
                        </div>
                        
                        <!-- Raça e Sexo -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Raça *</label>
                                <input type="text" name="breed" required class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent" placeholder="Ex: Holandesa">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Sexo *</label>
                                <select name="gender" required class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                                    <option value="">Selecione</option>
                                    <option value="femea">Fêmea</option>
                                    <option value="macho">Macho</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Data de Nascimento e Peso -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Data de Nascimento *</label>
                                <input type="date" name="birth_date" required class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Peso ao Nascer (kg)</label>
                                <input type="number" name="birth_weight" step="0.01" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent" placeholder="Ex: 35.5">
                            </div>
                        </div>
                        
                        <!-- Status -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Status *</label>
                            <select name="status" required class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                                <option value="">Selecione</option>
                                <option value="Lactante">Lactante</option>
                                <option value="Seco">Seca</option>
                                <option value="Novilha">Novilha</option>
                                <option value="Vaca">Vaca</option>
                                <option value="Bezerra">Bezerra</option>
                                <option value="Bezerro">Bezerro</option>
                                <option value="Touro">Touro</option>
                            </select>
                        </div>
                        
                        <!-- Status de Saúde e Reprodutivo -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Status de Saúde</label>
                                <select name="health_status" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                                    <option value="saudavel">Saudável</option>
                                    <option value="doente">Doente</option>
                                    <option value="tratamento">Em Tratamento</option>
                                    <option value="quarentena">Quarentena</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Status Reprodutivo</label>
                                <select name="reproductive_status" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                                    <option value="vazia">Vazia</option>
                                    <option value="prenha">Prenha</option>
                                    <option value="lactante">Lactante</option>
                                    <option value="seca">Seca</option>
                                    <option value="outros">Outros</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Pedigree (Genealogia) -->
                        <div class="border-t border-gray-200 pt-3 mt-2">
                            <h4 class="text-sm font-semibold text-gray-900 mb-3 flex items-center">
                                <svg class="w-4 h-4 mr-1.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                </svg>
                                Pedigree (Genealogia)
                            </h4>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Pai</label>
                                    <select name="father_id" id="addFatherSelect" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                                        <option value="">Nenhum / Desconhecido</option>
                                        <option disabled>Carregando...</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Mãe</label>
                                    <select name="mother_id" id="addMotherSelect" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                                        <option value="">Nenhuma / Desconhecida</option>
                                        <option disabled>Carregando...</option>
                                    </select>
                                </div>
                            </div>
                            <p class="text-xs text-gray-500 mt-2 flex items-center">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                </svg>
                                Opcional: Selecione o pai e/ou mãe se conhecidos
                            </p>
                        </div>
                        
                        <!-- Observações -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Observações</label>
                            <textarea name="notes" rows="3" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent resize-none" placeholder="Informações adicionais sobre o animal"></textarea>
                        </div>
                    </div>
                </form>
                
                <!-- Footer -->
                <div class="border-t border-gray-200 px-4 py-3 flex items-center justify-end space-x-2 bg-gray-50">
                    <button type="button" onclick="closeAddAnimalModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        Cancelar
                    </button>
                    <button type="button" onclick="submitAddAnimal()" class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-colors flex items-center space-x-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>Adicionar Animal</span>
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Carregar lista de animais para pedigree
        loadPedigreeOptions();
        
        // Fechar ao clicar fora
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeAddAnimalModal();
            }
        });
    };
    
    // Carregar opções de pedigree (pai e mãe)
    function loadPedigreeOptions() {
        const fatherSelect = document.getElementById('addFatherSelect');
        const motherSelect = document.getElementById('addMotherSelect');
        
        if (!fatherSelect || !motherSelect) return;
        
        // Filtrar animais do cache
        const males = animalsCache.filter(a => a.gender === 'macho');
        const females = animalsCache.filter(a => a.gender === 'femea');
        
        // Preencher select de Pai (machos)
        fatherSelect.innerHTML = '<option value="">Nenhum / Desconhecido</option>';
        males.forEach(animal => {
            const option = document.createElement('option');
            option.value = animal.id;
            option.textContent = `${animal.animal_number} - ${animal.name || 'Sem nome'} (${animal.breed})`;
            fatherSelect.appendChild(option);
        });
        
        // Preencher select de Mãe (fêmeas)
        motherSelect.innerHTML = '<option value="">Nenhuma / Desconhecida</option>';
        females.forEach(animal => {
            const option = document.createElement('option');
            option.value = animal.id;
            option.textContent = `${animal.animal_number} - ${animal.name || 'Sem nome'} (${animal.breed})`;
            motherSelect.appendChild(option);
        });
        
        console.log(`📋 Pedigree: ${males.length} machos e ${females.length} fêmeas disponíveis`);
    }
    
    // Fechar modal de adicionar animal
    window.closeAddAnimalModal = function() {
        const modal = document.getElementById('addAnimalModal');
        if (modal) modal.remove();
    };
    
    // Submeter formulário de adicionar animal
    window.submitAddAnimal = async function() {
        const form = document.getElementById('addAnimalForm');
        if (!form) return;
        
        // Validar formulário
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }
        
        // Coletar dados
        const formData = new FormData(form);
        const animalData = {
            action: 'insert',
            animal_number: formData.get('animal_number'),
            name: formData.get('name') || null,
            breed: formData.get('breed'),
            gender: formData.get('gender'),
            birth_date: formData.get('birth_date'),
            birth_weight: formData.get('birth_weight') || null,
            father_id: formData.get('father_id') || null,
            mother_id: formData.get('mother_id') || null,
            status: formData.get('status'),
            health_status: formData.get('health_status') || 'saudavel',
            reproductive_status: formData.get('reproductive_status') || 'vazia',
            notes: formData.get('notes') || null,
            farm_id: 1,
            is_active: 1
        };
        
        console.log('📤 Enviando animal:', animalData);
        
        // Desabilitar botão
        const submitBtn = event.target;
        const originalContent = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = `
            <svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            <span>Salvando...</span>
        `;
        
        try {
            const response = await fetch('api/animals.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(animalData)
            });
            
            const data = await response.json();
            console.log('📦 Resposta da API:', data);
            
            if (data.success) {
                console.log('✅ Animal adicionado com sucesso!');
                
                // Mostrar mensagem de sucesso
                showSuccessMessage('Animal adicionado com sucesso!');
                
                // Fechar modal
                closeAddAnimalModal();
                
                // Recarregar lista
                await loadAnimalsData();
            } else {
                throw new Error(data.error || 'Erro ao adicionar animal');
            }
        } catch (error) {
            console.error('❌ Erro ao adicionar animal:', error);
            alert('Erro ao adicionar animal: ' + error.message);
            
            // Restaurar botão
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalContent;
        }
    };
    
    // Mostrar mensagem de sucesso
    function showSuccessMessage(message) {
        const toast = document.createElement('div');
        toast.className = 'fixed top-4 right-4 z-[9999999] bg-emerald-600 text-white px-4 py-3 rounded-lg shadow-lg flex items-center space-x-2 animate-fade-in';
        toast.innerHTML = `
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <span class="font-medium">${message}</span>
        `;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.classList.add('opacity-0', 'transition-opacity', 'duration-300');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
    
    // Ver detalhes do animal
    window.viewAnimalDetails = async function(animalId) {
        console.log('🔍 Abrindo detalhes do animal:', animalId);
        
        // Buscar animal no cache
        const animal = animalsCache.find(a => a.id === animalId);
        if (!animal) {
            alert('Animal não encontrado!');
            return;
        }
        
        console.log('📦 Dados do animal:', animal);
        
        // Calcular idade detalhada
        const ageDetails = calculateDetailedAge(animal.birth_date);
        
        // Criar modal
        const modal = document.createElement('div');
        modal.id = 'animalDetailsModal';
        modal.className = 'fixed inset-0 bg-black bg-opacity-60 z-[999999] flex items-center justify-center p-4';
        modal.innerHTML = `
            <div class="bg-white rounded-xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-hidden">
                <!-- Header -->
                <div class="bg-gradient-to-r from-emerald-600 to-teal-600 px-4 py-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-white">${animal.name || animal.animal_number}</h3>
                            <p class="text-sm text-emerald-100">Código: ${animal.animal_number}</p>
                        </div>
                        <button onclick="closeAnimalDetailsModal()" class="text-white hover:bg-white hover:bg-opacity-20 p-1.5 rounded transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <!-- Conteúdo -->
                <div class="p-4 overflow-y-auto max-h-[calc(90vh-140px)]">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        
                        <!-- Informações Básicas -->
                        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                            <h4 class="text-sm font-bold text-gray-900 mb-3 flex items-center">
                                <svg class="w-4 h-4 mr-1.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Informações Básicas
                            </h4>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-xs font-medium text-gray-500">Número:</span>
                                    <span class="text-sm font-semibold text-gray-900">${animal.animal_number}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-xs font-medium text-gray-500">Nome:</span>
                                    <span class="text-sm font-semibold text-gray-900">${animal.name || '-'}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-xs font-medium text-gray-500">Raça:</span>
                                    <span class="text-sm font-semibold text-gray-900">${animal.breed}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-xs font-medium text-gray-500">Sexo:</span>
                                    <span class="text-sm font-semibold text-gray-900">${animal.gender === 'femea' ? 'Fêmea' : 'Macho'}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-xs font-medium text-gray-500">Data de Nascimento:</span>
                                    <span class="text-sm font-semibold text-gray-900">${formatDate(animal.birth_date)}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-xs font-medium text-gray-500">Idade:</span>
                                    <span class="text-sm font-semibold text-gray-900">${ageDetails}</span>
                                </div>
                                ${animal.birth_weight ? `
                                <div class="flex justify-between">
                                    <span class="text-xs font-medium text-gray-500">Peso ao Nascer:</span>
                                    <span class="text-sm font-semibold text-gray-900">${animal.birth_weight} kg</span>
                                </div>
                                ` : ''}
                            </div>
                        </div>
                        
                        <!-- Status -->
                        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                            <h4 class="text-sm font-bold text-gray-900 mb-3 flex items-center">
                                <svg class="w-4 h-4 mr-1.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Status
                            </h4>
                            <div class="space-y-2">
                                <div class="flex justify-between items-center">
                                    <span class="text-xs font-medium text-gray-500">Status Atual:</span>
                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-semibold ${getStatusColor(animal.status)}">
                                        ${animal.status}
                                    </span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-xs font-medium text-gray-500">Saúde:</span>
                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-semibold ${getHealthStatusColor(animal.health_status)}">
                                        ${translateHealthStatus(animal.health_status)}
                                    </span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-xs font-medium text-gray-500">Reprodutivo:</span>
                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-semibold ${getReproductiveStatusColor(animal.reproductive_status)}">
                                        ${translateReproductiveStatus(animal.reproductive_status)}
                                    </span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-xs font-medium text-gray-500">Ativo:</span>
                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-semibold ${animal.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                                        ${animal.is_active ? 'Sim' : 'Não'}
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Genealogia -->
                        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                            <h4 class="text-sm font-bold text-gray-900 mb-3 flex items-center">
                                <svg class="w-4 h-4 mr-1.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                </svg>
                                Genealogia
                            </h4>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-xs font-medium text-gray-500">Pai:</span>
                                    <span class="text-sm font-semibold text-gray-900">${animal.father_name || '-'}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-xs font-medium text-gray-500">Mãe:</span>
                                    <span class="text-sm font-semibold text-gray-900">${animal.mother_name || '-'}</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Observações -->
                        ${animal.notes ? `
                        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                            <h4 class="text-sm font-bold text-gray-900 mb-3 flex items-center">
                                <svg class="w-4 h-4 mr-1.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                Observações
                            </h4>
                            <p class="text-sm text-gray-700 leading-relaxed">${animal.notes}</p>
                        </div>
                        ` : ''}
                        
                        <!-- Datas de Registro -->
                        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                            <h4 class="text-sm font-bold text-gray-900 mb-3 flex items-center">
                                <svg class="w-4 h-4 mr-1.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                Datas de Registro
                            </h4>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-xs font-medium text-gray-500">Cadastrado em:</span>
                                    <span class="text-sm text-gray-900">${formatDateTime(animal.created_at)}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-xs font-medium text-gray-500">Última atualização:</span>
                                    <span class="text-sm text-gray-900">${formatDateTime(animal.updated_at)}</span>
                                </div>
                            </div>
                        </div>
                        
                    </div>
                </div>
                
                <!-- Footer -->
                <div class="border-t border-gray-200 px-4 py-3 flex items-center justify-between bg-gray-50">
                    <button onclick="editAnimal(${animalId})" class="px-4 py-2 text-sm font-medium text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg hover:bg-emerald-100 transition-colors flex items-center space-x-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        <span>Editar</span>
                    </button>
                    <button onclick="closeAnimalDetailsModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        Fechar
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Fechar ao clicar fora
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeAnimalDetailsModal();
            }
        });
    };
    
    // Fechar modal de detalhes
    window.closeAnimalDetailsModal = function() {
        const modal = document.getElementById('animalDetailsModal');
        if (modal) modal.remove();
    };
    
    // Funções auxiliares
    function calculateDetailedAge(birthDate) {
        if (!birthDate) return '-';
        
        const birth = new Date(birthDate);
        const today = new Date();
        const diffTime = Math.abs(today - birth);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        
        if (diffDays < 30) {
            return `${diffDays} dias`;
        } else if (diffDays < 365) {
            const months = Math.floor(diffDays / 30);
            const days = diffDays % 30;
            return `${months} ${months === 1 ? 'mês' : 'meses'}${days > 0 ? ' e ' + days + ' dias' : ''}`;
        } else {
            const years = Math.floor(diffDays / 365);
            const months = Math.floor((diffDays % 365) / 30);
            return `${years} ${years === 1 ? 'ano' : 'anos'}${months > 0 ? ' e ' + months + ' meses' : ''}`;
        }
    }
    
    function formatDate(dateString) {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleDateString('pt-BR');
    }
    
    function formatDateTime(dateString) {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleDateString('pt-BR') + ' ' + date.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
    }
    
    function getHealthStatusColor(status) {
        const colors = {
            'saudavel': 'bg-green-100 text-green-800',
            'doente': 'bg-red-100 text-red-800',
            'tratamento': 'bg-yellow-100 text-yellow-800',
            'quarentena': 'bg-orange-100 text-orange-800'
        };
        return colors[status] || 'bg-gray-100 text-gray-800';
    }
    
    function getReproductiveStatusColor(status) {
        const colors = {
            'vazia': 'bg-gray-100 text-gray-800',
            'prenha': 'bg-purple-100 text-purple-800',
            'lactante': 'bg-blue-100 text-blue-800',
            'seca': 'bg-amber-100 text-amber-800',
            'outros': 'bg-gray-100 text-gray-800'
        };
        return colors[status] || 'bg-gray-100 text-gray-800';
    }
    
    function translateHealthStatus(status) {
        const translations = {
            'saudavel': 'Saudável',
            'doente': 'Doente',
            'tratamento': 'Em Tratamento',
            'quarentena': 'Quarentena'
        };
        return translations[status] || status;
    }
    
    function translateReproductiveStatus(status) {
        const translations = {
            'vazia': 'Vazia',
            'prenha': 'Prenha',
            'lactante': 'Lactante',
            'seca': 'Seca',
            'outros': 'Outros'
        };
        return translations[status] || status;
    }
    
    // Editar animal
    window.editAnimal = async function(animalId) {
        console.log('✏️ Editando animal:', animalId);
        
        // Fechar modal de detalhes
        closeAnimalDetailsModal();
        
        // Buscar animal no cache
        const animal = animalsCache.find(a => a.id === animalId);
        if (!animal) {
            alert('Animal não encontrado!');
            return;
        }
        
        console.log('📦 Dados do animal para edição:', animal);
        
        // Criar modal de edição
        const modal = document.createElement('div');
        modal.id = 'editAnimalModal';
        modal.className = 'fixed inset-0 bg-black bg-opacity-60 z-[999999] flex items-center justify-center p-4';
        modal.innerHTML = `
            <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-hidden">
                <!-- Header -->
                <div class="bg-gradient-to-r from-emerald-600 to-teal-600 px-4 py-3 flex items-center justify-between">
                    <h3 class="text-base font-bold text-white">Editar Animal: ${animal.animal_number}</h3>
                    <button onclick="closeEditAnimalModal()" class="text-white hover:bg-white hover:bg-opacity-20 p-1 rounded transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                
                <!-- Form -->
                <form id="editAnimalForm" class="p-4 overflow-y-auto max-h-[calc(90vh-120px)]">
                    <input type="hidden" name="id" value="${animal.id}">
                    
                    <div class="space-y-3">
                        <!-- Número do Animal -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Número do Animal *</label>
                            <input type="text" name="animal_number" required value="${animal.animal_number}" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                        </div>
                        
                        <!-- Nome -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Nome</label>
                            <input type="text" name="name" value="${animal.name || ''}" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent" placeholder="Nome do animal (opcional)">
                        </div>
                        
                        <!-- Raça e Sexo -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Raça *</label>
                                <input type="text" name="breed" required value="${animal.breed}" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Sexo *</label>
                                <select name="gender" required class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                                    <option value="femea" ${animal.gender === 'femea' ? 'selected' : ''}>Fêmea</option>
                                    <option value="macho" ${animal.gender === 'macho' ? 'selected' : ''}>Macho</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Data de Nascimento e Peso -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Data de Nascimento *</label>
                                <input type="date" name="birth_date" required value="${animal.birth_date}" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Peso ao Nascer (kg)</label>
                                <input type="number" name="birth_weight" step="0.01" value="${animal.birth_weight || ''}" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                            </div>
                        </div>
                        
                        <!-- Status -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Status *</label>
                            <select name="status" required class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                                <option value="Lactante" ${animal.status === 'Lactante' ? 'selected' : ''}>Lactante</option>
                                <option value="Seco" ${animal.status === 'Seco' ? 'selected' : ''}>Seca</option>
                                <option value="Novilha" ${animal.status === 'Novilha' ? 'selected' : ''}>Novilha</option>
                                <option value="Vaca" ${animal.status === 'Vaca' ? 'selected' : ''}>Vaca</option>
                                <option value="Bezerra" ${animal.status === 'Bezerra' ? 'selected' : ''}>Bezerra</option>
                                <option value="Bezerro" ${animal.status === 'Bezerro' ? 'selected' : ''}>Bezerro</option>
                                <option value="Touro" ${animal.status === 'Touro' ? 'selected' : ''}>Touro</option>
                            </select>
                        </div>
                        
                        <!-- Status de Saúde e Reprodutivo -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Status de Saúde</label>
                                <select name="health_status" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                                    <option value="saudavel" ${animal.health_status === 'saudavel' ? 'selected' : ''}>Saudável</option>
                                    <option value="doente" ${animal.health_status === 'doente' ? 'selected' : ''}>Doente</option>
                                    <option value="tratamento" ${animal.health_status === 'tratamento' ? 'selected' : ''}>Em Tratamento</option>
                                    <option value="quarentena" ${animal.health_status === 'quarentena' ? 'selected' : ''}>Quarentena</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Status Reprodutivo</label>
                                <select name="reproductive_status" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                                    <option value="vazia" ${animal.reproductive_status === 'vazia' ? 'selected' : ''}>Vazia</option>
                                    <option value="prenha" ${animal.reproductive_status === 'prenha' ? 'selected' : ''}>Prenha</option>
                                    <option value="lactante" ${animal.reproductive_status === 'lactante' ? 'selected' : ''}>Lactante</option>
                                    <option value="seca" ${animal.reproductive_status === 'seca' ? 'selected' : ''}>Seca</option>
                                    <option value="outros" ${animal.reproductive_status === 'outros' ? 'selected' : ''}>Outros</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Pedigree (Genealogia) -->
                        <div class="border-t border-gray-200 pt-3 mt-2">
                            <h4 class="text-sm font-semibold text-gray-900 mb-3 flex items-center">
                                <svg class="w-4 h-4 mr-1.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                </svg>
                                Pedigree (Genealogia)
                            </h4>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Pai</label>
                                    <select name="father_id" id="editFatherSelect" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                                        <option value="">Nenhum / Desconhecido</option>
                                        <option disabled>Carregando...</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Mãe</label>
                                    <select name="mother_id" id="editMotherSelect" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                                        <option value="">Nenhuma / Desconhecida</option>
                                        <option disabled>Carregando...</option>
                                    </select>
                                </div>
                            </div>
                            <p class="text-xs text-gray-500 mt-2 flex items-center">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                </svg>
                                Opcional: Selecione o pai e/ou mãe se conhecidos
                            </p>
                        </div>
                        
                        <!-- Observações -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Observações</label>
                            <textarea name="notes" rows="3" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent resize-none">${animal.notes || ''}</textarea>
                        </div>
                    </div>
                </form>
                
                <!-- Footer -->
                <div class="border-t border-gray-200 px-4 py-3 flex items-center justify-between bg-gray-50">
                    <button type="button" onclick="deleteAnimal(${animalId})" class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors flex items-center space-x-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        <span>Excluir</span>
                    </button>
                    <div class="flex items-center space-x-2">
                        <button type="button" onclick="closeEditAnimalModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                            Cancelar
                        </button>
                        <button type="button" onclick="submitEditAnimal()" class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-colors flex items-center space-x-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span>Salvar Alterações</span>
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Carregar opções de pedigree
        loadEditPedigreeOptions(animal.father_id, animal.mother_id);
        
        // Fechar ao clicar fora
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeEditAnimalModal();
            }
        });
    };
    
    // Carregar opções de pedigree no modal de edição
    function loadEditPedigreeOptions(currentFatherId, currentMotherId) {
        const fatherSelect = document.getElementById('editFatherSelect');
        const motherSelect = document.getElementById('editMotherSelect');
        
        if (!fatherSelect || !motherSelect) return;
        
        // Filtrar animais do cache
        const males = animalsCache.filter(a => a.gender === 'macho');
        const females = animalsCache.filter(a => a.gender === 'femea');
        
        // Preencher select de Pai (machos)
        fatherSelect.innerHTML = '<option value="">Nenhum / Desconhecido</option>';
        males.forEach(animal => {
            const option = document.createElement('option');
            option.value = animal.id;
            option.textContent = `${animal.animal_number} - ${animal.name || 'Sem nome'} (${animal.breed})`;
            if (animal.id == currentFatherId) option.selected = true;
            fatherSelect.appendChild(option);
        });
        
        // Preencher select de Mãe (fêmeas)
        motherSelect.innerHTML = '<option value="">Nenhuma / Desconhecida</option>';
        females.forEach(animal => {
            const option = document.createElement('option');
            option.value = animal.id;
            option.textContent = `${animal.animal_number} - ${animal.name || 'Sem nome'} (${animal.breed})`;
            if (animal.id == currentMotherId) option.selected = true;
            motherSelect.appendChild(option);
        });
        
        console.log(`📋 Pedigree (edit): ${males.length} machos e ${females.length} fêmeas disponíveis`);
    }
    
    // Fechar modal de edição
    window.closeEditAnimalModal = function() {
        const modal = document.getElementById('editAnimalModal');
        if (modal) modal.remove();
    };
    
    // Submeter edição do animal
    window.submitEditAnimal = async function() {
        const form = document.getElementById('editAnimalForm');
        if (!form) return;
        
        // Validar formulário
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }
        
        // Coletar dados
        const formData = new FormData(form);
        const animalData = {
            action: 'update',
            id: formData.get('id'),
            animal_number: formData.get('animal_number'),
            name: formData.get('name') || null,
            breed: formData.get('breed'),
            gender: formData.get('gender'),
            birth_date: formData.get('birth_date'),
            birth_weight: formData.get('birth_weight') || null,
            father_id: formData.get('father_id') || null,
            mother_id: formData.get('mother_id') || null,
            status: formData.get('status'),
            health_status: formData.get('health_status'),
            reproductive_status: formData.get('reproductive_status'),
            notes: formData.get('notes') || null
        };
        
        console.log('📤 Atualizando animal:', animalData);
        
        // Desabilitar botão
        const submitBtn = event.target;
        const originalContent = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = `
            <svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            <span>Salvando...</span>
        `;
        
        try {
            const response = await fetch('api/animals.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(animalData)
            });
            
            const data = await response.json();
            console.log('📦 Resposta da API:', data);
            
            if (data.success) {
                console.log('✅ Animal atualizado com sucesso!');
                
                // Mostrar mensagem de sucesso
                showSuccessMessage('Animal atualizado com sucesso!');
                
                // Fechar modal
                closeEditAnimalModal();
                
                // Recarregar lista
                await loadAnimalsData();
            } else {
                throw new Error(data.error || 'Erro ao atualizar animal');
            }
        } catch (error) {
            console.error('❌ Erro ao atualizar animal:', error);
            alert('Erro ao atualizar animal: ' + error.message);
            
            // Restaurar botão
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalContent;
        }
    };
    
    // Excluir animal
    window.deleteAnimal = async function(animalId) {
        console.log('🗑️ Solicitando exclusão do animal:', animalId);
        
        // Buscar animal no cache
        const animal = animalsCache.find(a => a.id === animalId);
        if (!animal) {
            alert('Animal não encontrado!');
            return;
        }
        
        // Criar modal de confirmação
        const confirmModal = document.createElement('div');
        confirmModal.id = 'confirmDeleteModal';
        confirmModal.className = 'fixed inset-0 bg-black bg-opacity-60 z-[9999999] flex items-center justify-center p-4';
        confirmModal.innerHTML = `
            <div class="bg-white rounded-xl shadow-2xl max-w-md w-full">
                <!-- Header -->
                <div class="bg-red-600 px-4 py-3">
                    <h3 class="text-base font-bold text-white">Confirmar Exclusão</h3>
                </div>
                
                <!-- Conteúdo -->
                <div class="p-4">
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0 w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h4 class="text-sm font-semibold text-gray-900 mb-2">Tem certeza que deseja excluir este animal?</h4>
                            <div class="bg-gray-50 rounded-lg p-3 mb-3 border border-gray-200">
                                <p class="text-xs text-gray-500 mb-1">Animal:</p>
                                <p class="text-sm font-semibold text-gray-900">${animal.animal_number} - ${animal.name || 'Sem nome'}</p>
                                <p class="text-xs text-gray-600 mt-1">${animal.breed} | ${animal.status}</p>
                            </div>
                            <p class="text-xs text-red-600 font-medium">⚠️ Esta ação não pode ser desfeita!</p>
                        </div>
                    </div>
                </div>
                
                <!-- Footer -->
                <div class="border-t border-gray-200 px-4 py-3 flex items-center justify-end space-x-2 bg-gray-50">
                    <button type="button" onclick="closeConfirmDeleteModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        Cancelar
                    </button>
                    <button type="button" onclick="confirmDeleteAnimal(${animalId})" class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors flex items-center space-x-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        <span>Sim, Excluir</span>
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(confirmModal);
        
        // Fechar ao clicar fora
        confirmModal.addEventListener('click', function(e) {
            if (e.target === confirmModal) {
                closeConfirmDeleteModal();
            }
        });
    };
    
    // Fechar modal de confirmação
    window.closeConfirmDeleteModal = function() {
        const modal = document.getElementById('confirmDeleteModal');
        if (modal) modal.remove();
    };
    
    // Confirmar exclusão do animal
    window.confirmDeleteAnimal = async function(animalId) {
        console.log('🗑️ Excluindo animal:', animalId);
        
        // Desabilitar botão
        const submitBtn = event.target;
        const originalContent = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = `
            <svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            <span>Excluindo...</span>
        `;
        
        try {
            const response = await fetch('api/animals.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'delete',
                    id: animalId
                })
            });
            
            const data = await response.json();
            console.log('📦 Resposta da API:', data);
            
            if (data.success) {
                console.log('✅ Animal excluído com sucesso!');
                
                // Mostrar mensagem de sucesso
                showSuccessMessage('Animal excluído com sucesso!');
                
                // Fechar modais
                closeConfirmDeleteModal();
                closeEditAnimalModal();
                
                // Recarregar lista
                await loadAnimalsData();
            } else {
                throw new Error(data.error || 'Erro ao excluir animal');
            }
        } catch (error) {
            console.error('❌ Erro ao excluir animal:', error);
            alert('Erro ao excluir animal: ' + error.message);
            
            // Restaurar botão
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalContent;
        }
    };

    window.closeAnimalOverlay = function() {
        console.log('🔴 Fechando Gestão de Rebanho...');
        
        const overlay = document.getElementById('animalOverlay');
        if (overlay) {
            overlay.classList.add('hidden');
            document.body.style.overflow = '';
            
            console.log('✅ Overlay de Gestão de Rebanho fechado!');
        }
    };

    // Cache de dados de saúde
    let healthRecordsCache = [];
    let medicationsCache = [];
    let healthAlertsCache = [];
    
    // Gestão Sanitária
    window.showHealthManagement = function() {
        console.log('🏥 Abrindo Gestão Sanitária...');
        
        const overlay = document.getElementById('healthOverlay');
        if (!overlay) {
            console.error('❌ Overlay de Gestão Sanitária não encontrado!');
            return;
        }
        
        overlay.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        
        // Carregar dados
        loadHealthData();
        
        console.log('✅ Overlay de Gestão Sanitária aberto!');
    };
    
    // Carregar dados de saúde
    async function loadHealthData() {
        try {
            console.log('📡 Carregando dados de saúde...');
            
            // Carregar cada API individualmente para melhor tratamento de erro
            let recordsData = { success: false, data: [] };
            let medsData = { success: false, data: [] };
            let alertsData = { success: false, data: [] };
            
            // Registros de Saúde
            try {
                const recordsRes = await fetch('api/health_records.php?action=get_all');
                const recordsText = await recordsRes.text();
                console.log('📄 Health Records Response (primeiros 500 chars):', recordsText.substring(0, 500));
                console.log('📄 Health Records Response (completa):', recordsText);
                
                if (recordsText.trim().startsWith('<!DOCTYPE') || recordsText.trim().startsWith('<br')) {
                    console.error('❌ API retornou HTML (erro PHP):', recordsText);
                    throw new Error('API retornou HTML ao invés de JSON');
                }
                
                recordsData = JSON.parse(recordsText);
            } catch (e) {
                console.warn('⚠️ Erro ao carregar health_records:', e.message);
                // Continuar mesmo com erro
                recordsData = { success: true, data: [] };
            }
            
            // Medicamentos
            try {
                const medsRes = await fetch('api/medications.php?action=get_all');
                const medsText = await medsRes.text();
                console.log('📄 Medications Response:', medsText.substring(0, 200));
                medsData = JSON.parse(medsText);
            } catch (e) {
                console.warn('⚠️ Erro ao carregar medications:', e.message);
            }
            
            // Alertas
            try {
                const alertsRes = await fetch('api/health_alerts.php?action=get_all');
                const alertsText = await alertsRes.text();
                console.log('📄 Health Alerts Response:', alertsText.substring(0, 200));
                alertsData = JSON.parse(alertsText);
            } catch (e) {
                console.warn('⚠️ Erro ao carregar health_alerts:', e.message);
            }
            
            console.log('📦 Registros:', recordsData);
            console.log('📦 Medicamentos:', medsData);
            console.log('📦 Alertas:', alertsData);
            
            // Atualizar caches
            healthRecordsCache = recordsData.success && recordsData.data ? recordsData.data : [];
            medicationsCache = medsData.success && medsData.data ? medsData.data : [];
            healthAlertsCache = alertsData.success && alertsData.data ? alertsData.data : [];
            
            // Atualizar estatísticas
            updateHealthStats();
            
            // Renderizar tab ativa
            renderHealthRecordsTable();
            
            console.log(`✅ Saúde: ${healthRecordsCache.length} registros, ${medicationsCache.length} medicamentos, ${healthAlertsCache.length} alertas`);
        } catch (error) {
            console.error('❌ Erro ao carregar dados de saúde:', error);
            
            // Resetar estatísticas em caso de erro
            document.getElementById('healthTotalCount').textContent = '0';
            document.getElementById('healthMedsCount').textContent = '0';
            document.getElementById('healthVacCount').textContent = '0';
            document.getElementById('healthAlertsCount').textContent = '0';
        }
    }
    
    // Atualizar estatísticas
    function updateHealthStats() {
        const totalRecords = healthRecordsCache.length;
        const totalMeds = medicationsCache.length;
        const totalVac = healthRecordsCache.filter(r => r.record_type === 'Vacinação').length;
        const totalAlerts = healthAlertsCache.filter(a => !a.is_resolved).length;
        
        document.getElementById('healthTotalCount').textContent = totalRecords;
        document.getElementById('healthMedsCount').textContent = totalMeds;
        document.getElementById('healthVacCount').textContent = totalVac;
        document.getElementById('healthAlertsCount').textContent = totalAlerts;
        
        console.log(`📊 Stats Saúde: ${totalRecords} registros, ${totalMeds} medicamentos, ${totalVac} vacinações, ${totalAlerts} alertas`);
    }
    
    // Trocar tab
    window.switchHealthTab = function(tabName) {
        // Atualizar botões
        document.querySelectorAll('.health-tab').forEach(btn => {
            if (btn.dataset.tab === tabName) {
                btn.classList.remove('border-transparent', 'text-gray-500');
                btn.classList.add('border-red-600', 'text-red-600');
            } else {
                btn.classList.remove('border-red-600', 'text-red-600');
                btn.classList.add('border-transparent', 'text-gray-500');
            }
        });
        
        // Mostrar conteúdo correspondente
        document.querySelectorAll('.health-tab-content').forEach(content => {
            content.classList.add('hidden');
        });
        
        const targetContent = document.getElementById(`healthTab${tabName.charAt(0).toUpperCase() + tabName.slice(1)}`);
        if (targetContent) {
            targetContent.classList.remove('hidden');
        }
        
        // Carregar dados da tab
        if (tabName === 'records') {
            renderHealthRecordsTable();
        } else if (tabName === 'medications') {
            renderMedicationsTable();
        } else if (tabName === 'alerts') {
            renderHealthAlerts();
        }
    };
    
    // Renderizar tabela de registros de saúde
    function renderHealthRecordsTable() {
        const tbody = document.getElementById('healthRecordsTableBody');
        if (!tbody) return;
        
        if (healthRecordsCache.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="px-3 py-8 text-center text-gray-500">
                        <p class="text-sm">Nenhum registro de saúde encontrado</p>
                    </td>
                </tr>
            `;
            return;
        }
        
        tbody.innerHTML = healthRecordsCache.slice(0, 10).map(record => `
            <tr class="hover:bg-gray-50">
                <td class="px-3 py-2 text-gray-700">${formatDate(record.record_date)}</td>
                <td class="px-3 py-2 font-medium text-gray-900">${record.animal_number || '-'}</td>
                <td class="px-3 py-2">
                    <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium ${getRecordTypeColor(record.record_type)}">
                        ${record.record_type}
                    </span>
                </td>
                <td class="px-3 py-2 text-gray-600">${record.description.substring(0, 50)}...</td>
                <td class="px-3 py-2">
                    <button onclick="viewHealthRecordDetails(${record.id})" class="text-red-600 hover:text-red-700 text-xs font-medium">
                        Ver
                    </button>
                </td>
            </tr>
        `).join('');
    }
    
    // Renderizar tabela de medicamentos
    function renderMedicationsTable() {
        const tbody = document.getElementById('medicationsTableBody');
        if (!tbody) return;
        
        if (medicationsCache.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="px-3 py-8 text-center text-gray-500">
                        <p class="text-sm">Nenhum medicamento cadastrado</p>
                    </td>
                </tr>
            `;
            return;
        }
        
        tbody.innerHTML = medicationsCache.map(med => `
            <tr class="hover:bg-gray-50">
                <td class="px-3 py-2 font-medium text-gray-900">${med.name}</td>
                <td class="px-3 py-2 text-gray-600 capitalize">${med.type.replace('_', ' ')}</td>
                <td class="px-3 py-2">
                    <span class="text-sm ${getStockColor(med.stock_quantity, med.min_stock)}">
                        ${med.stock_quantity} ${med.unit}
                    </span>
                </td>
                <td class="px-3 py-2 text-gray-600">${med.expiry_date ? formatDate(med.expiry_date) : '-'}</td>
                <td class="px-3 py-2">
                    <button onclick="viewMedicationDetails(${med.id})" class="text-blue-600 hover:text-blue-700 text-xs font-medium">
                        Ver
                    </button>
                </td>
            </tr>
        `).join('');
    }
    
    // Renderizar alertas de saúde
    function renderHealthAlerts() {
        const container = document.getElementById('healthAlertsContainer');
        if (!container) return;
        
        const activeAlerts = healthAlertsCache.filter(a => !a.is_resolved);
        
        if (activeAlerts.length === 0) {
            container.innerHTML = `
                <div class="text-center text-gray-500 py-8">
                    <svg class="w-12 h-12 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-sm font-medium">Nenhum alerta pendente</p>
                    <p class="text-xs">Todos os animais estão com a saúde em dia!</p>
                </div>
            `;
            return;
        }
        
        container.innerHTML = activeAlerts.map(alert => `
            <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 flex items-start space-x-3">
                <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div class="flex-1">
                    <p class="text-sm font-semibold text-gray-900">${alert.alert_message}</p>
                    <p class="text-xs text-gray-600 mt-1">Animal: ${alert.animal_number || 'N/A'} | Data: ${formatDate(alert.alert_date)}</p>
                </div>
                <button onclick="resolveHealthAlert(${alert.id})" class="text-xs text-amber-600 hover:text-amber-700 font-medium">
                    Resolver
                </button>
            </div>
        `).join('');
    }
    
    // Funções auxiliares
    function getRecordTypeColor(type) {
        const colors = {
            'Medicamento': 'bg-blue-100 text-blue-800',
            'Vacinação': 'bg-green-100 text-green-800',
            'Vermifugação': 'bg-purple-100 text-purple-800',
            'Suplementação': 'bg-yellow-100 text-yellow-800',
            'Cirurgia': 'bg-red-100 text-red-800',
            'Consulta': 'bg-indigo-100 text-indigo-800',
            'Outros': 'bg-gray-100 text-gray-800'
        };
        return colors[type] || 'bg-gray-100 text-gray-800';
    }
    
    function getStockColor(current, min) {
        if (current <= 0) return 'text-red-600 font-semibold';
        if (current <= min * 0.5) return 'text-orange-600 font-semibold';
        if (current <= min) return 'text-amber-600 font-semibold';
        return 'text-gray-900';
    }
    
    // ==================== MEDICAMENTOS ====================
    
    // Ver detalhes do medicamento
    window.viewMedicationDetails = function(medId) {
        console.log('💊 Abrindo detalhes do medicamento:', medId);
        
        const med = medicationsCache.find(m => m.id === medId);
        if (!med) {
            alert('Medicamento não encontrado!');
            return;
        }
        
        const stockStatus = getStockStatusText(med.stock_quantity, med.min_stock);
        const stockColor = getStockColor(med.stock_quantity, med.min_stock);
        
        const modal = document.createElement('div');
        modal.id = 'medicationDetailsModal';
        modal.className = 'fixed inset-0 bg-black bg-opacity-60 z-[999999] flex items-center justify-center p-4';
        modal.innerHTML = `
            <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-hidden">
                <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-4 py-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-white">${med.name}</h3>
                            <p class="text-sm text-blue-100 capitalize">${med.type.replace('_', ' ')}</p>
                        </div>
                        <button onclick="closeMedicationDetailsModal()" class="text-white hover:bg-white hover:bg-opacity-20 p-1.5 rounded">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <div class="p-4 overflow-y-auto max-h-[calc(90vh-140px)]">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        
                        <!-- Informações Gerais -->
                        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                            <h4 class="text-sm font-bold text-gray-900 mb-3 flex items-center">
                                <svg class="w-4 h-4 mr-1.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Informações
                            </h4>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-xs font-medium text-gray-500">Nome:</span>
                                    <span class="text-sm font-semibold text-gray-900">${med.name}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-xs font-medium text-gray-500">Tipo:</span>
                                    <span class="text-sm font-semibold text-gray-900 capitalize">${med.type.replace('_', ' ')}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-xs font-medium text-gray-500">Fornecedor:</span>
                                    <span class="text-sm font-semibold text-gray-900">${med.supplier || '-'}</span>
                                </div>
                                ${med.description ? `
                                <div class="col-span-2 pt-2 border-t border-gray-200">
                                    <span class="text-xs font-medium text-gray-500">Descrição:</span>
                                    <p class="text-sm text-gray-700 mt-1">${med.description}</p>
                                </div>
                                ` : ''}
                            </div>
                        </div>
                        
                        <!-- Estoque -->
                        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                            <h4 class="text-sm font-bold text-gray-900 mb-3 flex items-center">
                                <svg class="w-4 h-4 mr-1.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                                Estoque
                            </h4>
                            <div class="space-y-2">
                                <div class="flex justify-between items-center">
                                    <span class="text-xs font-medium text-gray-500">Quantidade:</span>
                                    <span class="text-sm font-bold ${stockColor}">${med.stock_quantity} ${med.unit}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-xs font-medium text-gray-500">Estoque Mínimo:</span>
                                    <span class="text-sm text-gray-900">${med.min_stock} ${med.unit}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-xs font-medium text-gray-500">Status:</span>
                                    <span class="text-xs font-semibold ${stockColor}">${stockStatus}</span>
                                </div>
                                ${med.unit_price ? `
                                <div class="flex justify-between">
                                    <span class="text-xs font-medium text-gray-500">Preço Unit.:</span>
                                    <span class="text-sm text-gray-900">R$ ${parseFloat(med.unit_price).toFixed(2)}</span>
                                </div>
                                ` : ''}
                                ${med.expiry_date ? `
                                <div class="flex justify-between">
                                    <span class="text-xs font-medium text-gray-500">Validade:</span>
                                    <span class="text-sm text-gray-900">${formatDate(med.expiry_date)}</span>
                                </div>
                                ` : ''}
                            </div>
                        </div>
                        
                    </div>
                </div>
                
                <div class="border-t border-gray-200 px-4 py-3 flex items-center justify-between bg-gray-50">
                    <button onclick="editMedication(${medId})" class="px-4 py-2 text-sm font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 transition-colors flex items-center space-x-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        <span>Editar</span>
                    </button>
                    <button onclick="closeMedicationDetailsModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                        Fechar
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        modal.addEventListener('click', function(e) {
            if (e.target === modal) closeMedicationDetailsModal();
        });
    };
    
    window.closeMedicationDetailsModal = function() {
        const modal = document.getElementById('medicationDetailsModal');
        if (modal) modal.remove();
    };
    
    function getStockStatusText(current, min) {
        if (current <= 0) return '🔴 SEM ESTOQUE';
        if (current <= min * 0.5) return '🟠 CRÍTICO';
        if (current <= min) return '🟡 BAIXO';
        return '🟢 NORMAL';
    }
    
    // ==================== NOVO MEDICAMENTO ====================
    
    window.showAddMedicationForm = function() {
        const modal = document.createElement('div');
        modal.id = 'addMedicationModal';
        modal.className = 'fixed inset-0 bg-black bg-opacity-60 z-[999999] flex items-center justify-center p-4';
        modal.innerHTML = `
            <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-hidden">
                <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-4 py-3 flex items-center justify-between">
                    <h3 class="text-base font-bold text-white">Adicionar Novo Medicamento</h3>
                    <button onclick="closeAddMedicationModal()" class="text-white hover:bg-white hover:bg-opacity-20 p-1 rounded">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                
                <form id="addMedicationForm" class="p-4 overflow-y-auto max-h-[calc(90vh-120px)]">
                    <div class="space-y-3">
                        <!-- Nome -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Nome do Medicamento *</label>
                            <input type="text" name="name" required class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Ex: Penicilina">
                        </div>
                        
                        <!-- Tipo e Unidade -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Tipo *</label>
                                <select name="type" required class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <option value="">Selecione</option>
                                    <option value="antibiotico">Antibiótico</option>
                                    <option value="antiinflamatorio">Anti-inflamatório</option>
                                    <option value="vitamina">Vitamina</option>
                                    <option value="vermifugo">Vermífugo</option>
                                    <option value="vacina">Vacina</option>
                                    <option value="outros">Outros</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Unidade *</label>
                                <select name="unit" required class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <option value="ml">ml</option>
                                    <option value="mg">mg</option>
                                    <option value="g">g</option>
                                    <option value="unidade">unidade</option>
                                    <option value="dose">dose</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Estoque -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Quantidade em Estoque *</label>
                                <input type="number" name="stock_quantity" required step="0.01" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="Ex: 500">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Estoque Mínimo *</label>
                                <input type="number" name="min_stock" required step="0.01" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="Ex: 100">
                            </div>
                        </div>
                        
                        <!-- Preço e Validade -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Preço Unitário (R$)</label>
                                <input type="number" name="unit_price" step="0.01" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="Ex: 15.50">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Data de Validade</label>
                                <input type="date" name="expiry_date" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                        
                        <!-- Fornecedor -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Fornecedor</label>
                            <input type="text" name="supplier" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="Ex: VetCorp">
                        </div>
                        
                        <!-- Descrição -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Descrição</label>
                            <textarea name="description" rows="3" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 resize-none" placeholder="Informações adicionais sobre o medicamento"></textarea>
                        </div>
                    </div>
                </form>
                
                <div class="border-t border-gray-200 px-4 py-3 flex items-center justify-end space-x-2 bg-gray-50">
                    <button onclick="closeAddMedicationModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button onclick="submitAddMedication()" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg flex items-center space-x-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>Adicionar Medicamento</span>
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        modal.addEventListener('click', function(e) {
            if (e.target === modal) closeAddMedicationModal();
        });
    };
    
    window.closeAddMedicationModal = function() {
        const modal = document.getElementById('addMedicationModal');
        if (modal) modal.remove();
    };
    
    window.submitAddMedication = async function() {
        const form = document.getElementById('addMedicationForm');
        if (!form || !form.checkValidity()) {
            form?.reportValidity();
            return;
        }
        
        const formData = new FormData(form);
        const medicationData = {
            action: 'insert',
            name: formData.get('name'),
            type: formData.get('type'),
            unit: formData.get('unit'),
            stock_quantity: formData.get('stock_quantity'),
            min_stock: formData.get('min_stock'),
            unit_price: formData.get('unit_price') || null,
            expiry_date: formData.get('expiry_date') || null,
            supplier: formData.get('supplier') || null,
            description: formData.get('description') || null,
            farm_id: 1,
            is_active: 1
        };
        
        console.log('📤 Adicionando medicamento:', medicationData);
        
        const submitBtn = event.target;
        const originalContent = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg><span>Salvando...</span>';
        
        try {
            const response = await fetch('api/medications.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(medicationData)
            });
            
            const text = await response.text();
            console.log('📄 Resposta medications:', text.substring(0, 500));
            
            const data = JSON.parse(text);
            
            if (data.success) {
                showSuccessMessage('Medicamento adicionado com sucesso!');
                closeAddMedicationModal();
                await loadHealthData();
                switchHealthTab('medications');
            } else {
                throw new Error(data.error || 'Erro ao adicionar medicamento');
            }
        } catch (error) {
            console.error('❌ Erro:', error);
            alert('Erro ao adicionar medicamento: ' + error.message);
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalContent;
        }
    };
    
    // ==================== REGISTRO DE SAÚDE ====================
    
    window.showAddHealthRecordForm = async function() {
        console.log('📋 Abrindo formulário de registro de saúde...');
        
        // Verificar se temos animais no cache
        if (animalsCache.length === 0) {
            console.log('⏳ Carregando animais primeiro...');
            try {
                const response = await fetch('api/animals.php?action=get_all');
                const data = await response.json();
                if (data.success && data.data) {
                    animalsCache = data.data;
                    console.log(`✅ ${animalsCache.length} animais carregados!`);
                }
            } catch (error) {
                console.error('❌ Erro ao carregar animais:', error);
                alert('Erro ao carregar lista de animais. Tente novamente.');
                return;
            }
        }
        
        const modal = document.createElement('div');
        modal.id = 'addHealthRecordModal';
        modal.className = 'fixed inset-0 bg-black bg-opacity-60 z-[999999] flex items-center justify-center p-4';
        modal.innerHTML = `
            <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-hidden">
                <div class="bg-gradient-to-r from-red-600 to-pink-600 px-4 py-3 flex items-center justify-between">
                    <h3 class="text-base font-bold text-white">Novo Registro de Saúde</h3>
                    <button onclick="closeAddHealthRecordModal()" class="text-white hover:bg-white hover:bg-opacity-20 p-1 rounded">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                
                <form id="addHealthRecordForm" class="p-4 overflow-y-auto max-h-[calc(90vh-120px)]">
                    <div class="space-y-3">
                        <!-- Animal e Data -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Animal *</label>
                                <select name="animal_id" id="healthRecordAnimalSelect" required class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500">
                                    <option value="">Selecione o animal</option>
                                    ${animalsCache.map(animal => `
                                        <option value="${animal.id}">${animal.animal_number} - ${animal.name || 'Sem nome'}</option>
                                    `).join('')}
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Data do Registro *</label>
                                <input type="date" name="record_date" required value="${new Date().toISOString().split('T')[0]}" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500">
                            </div>
                        </div>
                        
                        <!-- Tipo de Registro -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Tipo de Registro *</label>
                            <select name="record_type" required class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500">
                                <option value="">Selecione</option>
                                <option value="Medicamento">Medicamento</option>
                                <option value="Vacinação">Vacinação</option>
                                <option value="Vermifugação">Vermifugação</option>
                                <option value="Suplementação">Suplementação</option>
                                <option value="Cirurgia">Cirurgia</option>
                                <option value="Consulta">Consulta</option>
                                <option value="Outros">Outros</option>
                            </select>
                        </div>
                        
                        <!-- Descrição -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Descrição *</label>
                            <textarea name="description" required rows="3" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 resize-none" placeholder="Descreva o procedimento realizado"></textarea>
                        </div>
                        
                        <!-- Medicamento e Dosagem -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Medicamento</label>
                                <input type="text" name="medication" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500" placeholder="Nome do medicamento">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Dosagem</label>
                                <input type="text" name="dosage" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500" placeholder="Ex: 10ml">
                            </div>
                        </div>
                        
                        <!-- Custo e Próxima Data -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Custo (R$)</label>
                                <input type="number" name="cost" step="0.01" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500" placeholder="Ex: 25.50">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Próxima Aplicação</label>
                                <input type="date" name="next_date" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500">
                            </div>
                        </div>
                        
                        <!-- Veterinário -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Veterinário Responsável</label>
                            <input type="text" name="veterinarian" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500" placeholder="Nome do veterinário">
                        </div>
                    </div>
                </form>
                
                <div class="border-t border-gray-200 px-4 py-3 flex items-center justify-end space-x-2 bg-gray-50">
                    <button onclick="closeAddHealthRecordModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button onclick="submitAddHealthRecord()" class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg flex items-center space-x-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>Adicionar Registro</span>
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Fechar ao clicar fora
        modal.addEventListener('click', function(e) {
            if (e.target === modal) closeAddHealthRecordModal();
        });
    };
    
    window.closeAddHealthRecordModal = function() {
        const modal = document.getElementById('addHealthRecordModal');
        if (modal) modal.remove();
    };
    
    window.submitAddHealthRecord = async function() {
        const form = document.getElementById('addHealthRecordForm');
        if (!form || !form.checkValidity()) {
            form?.reportValidity();
            return;
        }
        
        const formData = new FormData(form);
        const recordData = {
            action: 'insert',
            animal_id: formData.get('animal_id'),
            record_date: formData.get('record_date'),
            record_type: formData.get('record_type'),
            description: formData.get('description'),
            medication: formData.get('medication') || null,
            dosage: formData.get('dosage') || null,
            cost: formData.get('cost') || null,
            next_date: formData.get('next_date') || null,
            veterinarian: formData.get('veterinarian') || null,
            farm_id: 1
        };
        
        console.log('📤 Adicionando registro de saúde:', recordData);
        
        const submitBtn = event.target;
        const originalContent = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg><span>Salvando...</span>';
        
        try {
            const response = await fetch('api/health_records.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(recordData)
            });
            
            const text = await response.text();
            console.log('📄 Resposta health_records:', text.substring(0, 500));
            
            const data = JSON.parse(text);
            
            if (data.success) {
                showSuccessMessage('Registro de saúde adicionado com sucesso!');
                closeAddHealthRecordModal();
                await loadHealthData();
                switchHealthTab('records');
            } else {
                throw new Error(data.error || 'Erro ao adicionar registro');
            }
        } catch (error) {
            console.error('❌ Erro:', error);
            alert('Erro ao adicionar registro: ' + error.message);
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalContent;
        }
    };
    
    // Funções placeholder
    window.viewHealthRecordDetails = function(id) {
        alert('Detalhes do registro ' + id + ' em desenvolvimento');
    };
    
    window.editMedication = function(id) {
        alert('Editar medicamento ' + id + ' em desenvolvimento');
    };
    
    window.resolveHealthAlert = function(id) {
        alert('Resolver alerta ' + id + ' em desenvolvimento');
    };

    window.closeHealthOverlay = function() {
        console.log('🔴 Fechando Gestão Sanitária...');
        
        const overlay = document.getElementById('healthOverlay');
        if (overlay) {
            overlay.classList.add('hidden');
            document.body.style.overflow = '';
            
            console.log('✅ Overlay de Gestão Sanitária fechado!');
        }
    };

    // Cache de dados de reprodução
    let pregnanciesCache = [];
    let inseminationsCache = [];
    let birthsCache = [];
    
    // Reprodução
    window.showReproductionManagement = function() {
        console.log('💕 Abrindo Sistema de Reprodução...');
        
        const overlay = document.getElementById('reproductionOverlay');
        if (!overlay) {
            console.error('❌ Overlay de Reprodução não encontrado!');
            return;
        }
        
        overlay.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        
        // Carregar dados
        loadReproductionData();
        
        console.log('✅ Overlay de Reprodução aberto!');
    };
    
    // Carregar dados de reprodução
    async function loadReproductionData() {
        try {
            console.log('📡 Carregando dados de reprodução...');
            
            // Carregar dados da API de reprodução
            const response = await fetch('api/reproduction.php?action=get_all');
            const text = await response.text();
            console.log('📄 Reproduction Response:', text.substring(0, 300));
            
            const data = JSON.parse(text);
            
            if (data.success) {
                pregnanciesCache = data.pregnancies || [];
                inseminationsCache = data.inseminations || [];
                birthsCache = data.births || [];
                
                console.log(`✅ Reprodução: ${pregnanciesCache.length} prenhes, ${inseminationsCache.length} IAs, ${birthsCache.length} nascimentos`);
                
                // Atualizar estatísticas
                updateReproStats();
                
                // Renderizar tab ativa
                renderPregnanciesTable();
            }
        } catch (error) {
            console.error('❌ Erro ao carregar reprodução:', error);
            
            // Resetar stats
            document.getElementById('reproPregnantCount').textContent = '0';
            document.getElementById('reproInsemCount').textContent = '0';
            document.getElementById('reproBirthsCount').textContent = '0';
            document.getElementById('reproBirthsTotalCount').textContent = '0';
        }
    }
    
    // Atualizar estatísticas
    function updateReproStats() {
        const totalPregnant = pregnanciesCache.length;
        const totalInsem = inseminationsCache.length;
        const birthsNext30 = pregnanciesCache.filter(p => {
            if (!p.expected_birth) return false;
            const dpp = new Date(p.expected_birth);
            const today = new Date();
            const diffDays = Math.ceil((dpp - today) / (1000 * 60 * 60 * 24));
            return diffDays > 0 && diffDays <= 30;
        }).length;
        const totalBirths = birthsCache.length;
        
        document.getElementById('reproPregnantCount').textContent = totalPregnant;
        document.getElementById('reproInsemCount').textContent = totalInsem;
        document.getElementById('reproBirthsCount').textContent = birthsNext30;
        document.getElementById('reproBirthsTotalCount').textContent = totalBirths;
    }
    
    // Trocar tab de reprodução
    window.switchReproTab = function(tabName) {
        // Atualizar botões
        document.querySelectorAll('.repro-tab').forEach(btn => {
            if (btn.dataset.tab === tabName) {
                btn.classList.remove('border-transparent', 'text-gray-500');
                btn.classList.add('border-purple-600', 'text-purple-600');
            } else {
                btn.classList.remove('border-purple-600', 'text-purple-600');
                btn.classList.add('border-transparent', 'text-gray-500');
            }
        });
        
        // Mostrar conteúdo
        document.querySelectorAll('.repro-tab-content').forEach(content => {
            content.classList.add('hidden');
        });
        
        const targetContent = document.getElementById(`reproTab${tabName.charAt(0).toUpperCase() + tabName.slice(1)}`);
        if (targetContent) {
            targetContent.classList.remove('hidden');
        }
        
        // Renderizar dados
        if (tabName === 'pregnancies') {
            renderPregnanciesTable();
        } else if (tabName === 'inseminations') {
            renderInseminationsTable();
        } else if (tabName === 'births') {
            renderBirthsTable();
        }
    };
    
    // Renderizar tabela de prenhes
    function renderPregnanciesTable() {
        const tbody = document.getElementById('pregnanciesTableBody');
        if (!tbody) return;
        
        if (pregnanciesCache.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="px-3 py-8 text-center text-gray-500">
                        <p class="text-sm">Nenhuma prenhez ativa no momento</p>
                    </td>
                </tr>
            `;
            return;
        }
        
        tbody.innerHTML = pregnanciesCache.map(preg => {
            const daysToB = calculateDaysToBirth(preg.expected_birth);
            const stageColor = getPregnancyStageColor(preg.pregnancy_stage);
            
            return `
                <tr class="hover:bg-gray-50">
                    <td class="px-3 py-2 font-medium text-gray-900">${preg.animal_number || '-'}</td>
                    <td class="px-3 py-2 text-gray-700">${formatDate(preg.pregnancy_date)}</td>
                    <td class="px-3 py-2 text-gray-700">${formatDate(preg.expected_birth)}</td>
                    <td class="px-3 py-2 text-gray-600">${daysToB}</td>
                    <td class="px-3 py-2">
                        <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium ${stageColor}">
                            ${translateStage(preg.pregnancy_stage)}
                        </span>
                    </td>
                    <td class="px-3 py-2">
                        <button onclick="viewPregnancyDetails(${preg.id})" class="text-purple-600 hover:text-purple-700 text-xs font-medium">
                            Ver
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    }
    
    // Renderizar inseminações
    function renderInseminationsTable() {
        const tbody = document.getElementById('inseminationsTableBody');
        if (!tbody) return;
        
        if (inseminationsCache.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="px-3 py-8 text-center text-gray-500">
                        <p class="text-sm">Nenhuma inseminação registrada</p>
                    </td>
                </tr>
            `;
            return;
        }
        
        tbody.innerHTML = inseminationsCache.map(insem => `
            <tr class="hover:bg-gray-50">
                <td class="px-3 py-2 text-gray-700">${formatDate(insem.insemination_date)}</td>
                <td class="px-3 py-2 font-medium text-gray-900">${insem.animal_number || '-'}</td>
                <td class="px-3 py-2 text-gray-600">${insem.bull_name || '-'}</td>
                <td class="px-3 py-2 text-gray-600 capitalize">${insem.insemination_type.replace('_', ' ')}</td>
                <td class="px-3 py-2">
                    <button onclick="viewInseminationDetails(${insem.id})" class="text-pink-600 hover:text-pink-700 text-xs font-medium">
                        Ver
                    </button>
                </td>
            </tr>
        `).join('');
    }
    
    // Renderizar nascimentos
    function renderBirthsTable() {
        const tbody = document.getElementById('birthsTableBody');
        if (!tbody) return;
        
        if (birthsCache.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="px-3 py-8 text-center text-gray-500">
                        <p class="text-sm">Nenhum nascimento registrado</p>
                    </td>
                </tr>
            `;
            return;
        }
        
        tbody.innerHTML = birthsCache.map(birth => `
            <tr class="hover:bg-gray-50">
                <td class="px-3 py-2 text-gray-700">${formatDate(birth.birth_date)}</td>
                <td class="px-3 py-2 font-medium text-gray-900">${birth.mother_number || '-'}</td>
                <td class="px-3 py-2 text-gray-700">${birth.calf_number || 'N/A'}</td>
                <td class="px-3 py-2 text-gray-600">${birth.calf_gender === 'femea' ? 'Fêmea' : 'Macho'}</td>
                <td class="px-3 py-2 text-gray-600">${birth.calf_weight ? birth.calf_weight + ' kg' : '-'}</td>
                <td class="px-3 py-2">
                    <button onclick="viewBirthDetails(${birth.id})" class="text-green-600 hover:text-green-700 text-xs font-medium">
                        Ver
                    </button>
                </td>
            </tr>
        `).join('');
    }
    
    // Funções auxiliares
    function calculateDaysToBirth(dppDate) {
        if (!dppDate) return '-';
        const dpp = new Date(dppDate);
        const today = new Date();
        const diffTime = dpp - today;
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        
        if (diffDays < 0) return 'Atrasado';
        if (diffDays === 0) return 'HOJE';
        return `${diffDays}d`;
    }
    
    function getPregnancyStageColor(stage) {
        const colors = {
            'inicial': 'bg-blue-100 text-blue-800',
            'meio': 'bg-purple-100 text-purple-800',
            'final': 'bg-orange-100 text-orange-800',
            'pre-parto': 'bg-red-100 text-red-800'
        };
        return colors[stage] || 'bg-gray-100 text-gray-800';
    }
    
    function translateStage(stage) {
        const translations = {
            'inicial': 'Inicial',
            'meio': 'Meio',
            'final': 'Final',
            'pre-parto': 'Pré-Parto'
        };
        return translations[stage] || stage;
    }
    
    // ==================== NOVA INSEMINAÇÃO ====================
    
    window.showAddInseminationForm = async function() {
        console.log('💉 Abrindo formulário de inseminação...');
        
        // Carregar animais se necessário
        if (animalsCache.length === 0) {
            const response = await fetch('api/animals.php?action=get_all');
            const data = await response.json();
            if (data.success && data.data) animalsCache = data.data;
        }
        
        // Carregar touros
        const bullsRes = await fetch('api/bulls.php?action=get_all');
        const bullsData = await bullsRes.json();
        const bulls = bullsData.success && bullsData.data ? bullsData.data : [];
        
        const modal = document.createElement('div');
        modal.id = 'addInseminationModal';
        modal.className = 'fixed inset-0 bg-black bg-opacity-60 z-[999999] flex items-center justify-center p-4';
        modal.innerHTML = `
            <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-hidden">
                <div class="bg-gradient-to-r from-pink-600 to-rose-600 px-4 py-3 flex items-center justify-between">
                    <h3 class="text-base font-bold text-white">Nova Inseminação</h3>
                    <button onclick="closeAddInseminationModal()" class="text-white hover:bg-white hover:bg-opacity-20 p-1 rounded">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                
                <form id="addInseminationForm" class="p-4 overflow-y-auto max-h-[calc(90vh-120px)]">
                    <div class="space-y-3">
                        <!-- Animal e Data -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Animal (Fêmea) *</label>
                                <select name="animal_id" required class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500">
                                    <option value="">Selecione</option>
                                    ${animalsCache.filter(a => a.gender === 'femea').map(a => `
                                        <option value="${a.id}">${a.animal_number} - ${a.name || 'Sem nome'}</option>
                                    `).join('')}
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Data da Inseminação *</label>
                                <input type="date" name="insemination_date" required value="${new Date().toISOString().split('T')[0]}" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500">
                            </div>
                        </div>
                        
                        <!-- Touro e Tipo -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Touro/Sêmen</label>
                                <select name="bull_id" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500">
                                    <option value="">Nenhum</option>
                                    ${bulls.map(b => `
                                        <option value="${b.id}">${b.bull_number} - ${b.name || 'Sem nome'}</option>
                                    `).join('')}
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Tipo de Inseminação *</label>
                                <select name="insemination_type" required class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500">
                                    <option value="inseminacao_artificial">Inseminação Artificial</option>
                                    <option value="natural">Natural</option>
                                    <option value="transferencia_embriao">Transferência de Embrião</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Técnico -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Técnico Responsável</label>
                            <input type="text" name="technician" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500" placeholder="Nome do técnico">
                        </div>
                        
                        <!-- Observações -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Observações</label>
                            <textarea name="notes" rows="3" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 resize-none" placeholder="Informações adicionais"></textarea>
                        </div>
                        
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                            <p class="text-xs text-blue-800">
                                ℹ️ Após salvar, será criado automaticamente um controle de prenhez com DPP calculado (280 dias).
                            </p>
                        </div>
                    </div>
                </form>
                
                <div class="border-t border-gray-200 px-4 py-3 flex items-center justify-end space-x-2 bg-gray-50">
                    <button onclick="closeAddInseminationModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button onclick="submitAddInsemination()" class="px-4 py-2 text-sm font-medium text-white bg-pink-600 hover:bg-pink-700 rounded-lg flex items-center space-x-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>Registrar Inseminação</span>
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        modal.addEventListener('click', e => { if (e.target === modal) closeAddInseminationModal(); });
    };
    
    window.closeAddInseminationModal = function() {
        const modal = document.getElementById('addInseminationModal');
        if (modal) modal.remove();
    };
    
    window.submitAddInsemination = async function() {
        const form = document.getElementById('addInseminationForm');
        if (!form || !form.checkValidity()) {
            form?.reportValidity();
            return;
        }
        
        const formData = new FormData(form);
        const insemData = {
            action: 'add_insemination',
            animal_id: formData.get('animal_id'),
            bull_id: formData.get('bull_id') || null,
            insemination_date: formData.get('insemination_date'),
            insemination_type: formData.get('insemination_type'),
            technician: formData.get('technician') || null,
            notes: formData.get('notes') || null,
            farm_id: 1
        };
        
        console.log('📤 Registrando inseminação:', insemData);
        
        const submitBtn = event.target;
        const originalContent = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg><span>Salvando...</span>';
        
        try {
            const response = await fetch('api/reproduction.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(insemData)
            });
            
            const data = await response.json();
            
            if (data.success) {
                showSuccessMessage('Inseminação registrada! Prenhez criada automaticamente.');
                closeAddInseminationModal();
                await loadReproductionData();
                switchReproTab('inseminations');
            } else {
                throw new Error(data.error || 'Erro ao registrar inseminação');
            }
        } catch (error) {
            console.error('❌ Erro:', error);
            alert('Erro: ' + error.message);
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalContent;
        }
    };
    
    // ==================== NOVO NASCIMENTO ====================
    
    window.showAddBirthForm = async function() {
        console.log('👶 Abrindo formulário de nascimento...');
        
        // Carregar animais fêmeas
        if (animalsCache.length === 0) {
            const response = await fetch('api/animals.php?action=get_all');
            const data = await response.json();
            if (data.success && data.data) animalsCache = data.data;
        }
        
        const modal = document.createElement('div');
        modal.id = 'addBirthModal';
        modal.className = 'fixed inset-0 bg-black bg-opacity-60 z-[999999] flex items-center justify-center p-4';
        modal.innerHTML = `
            <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-hidden">
                <div class="bg-gradient-to-r from-green-600 to-emerald-600 px-4 py-3 flex items-center justify-between">
                    <h3 class="text-base font-bold text-white">Novo Nascimento</h3>
                    <button onclick="closeAddBirthModal()" class="text-white hover:bg-white hover:bg-opacity-20 p-1 rounded">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                
                <form id="addBirthForm" class="p-4 overflow-y-auto max-h-[calc(90vh-120px)]">
                    <div class="space-y-3">
                        <!-- Mãe e Data -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Mãe *</label>
                                <select name="animal_id" required class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                                    <option value="">Selecione</option>
                                    ${animalsCache.filter(a => a.gender === 'femea').map(a => `
                                        <option value="${a.id}">${a.animal_number} - ${a.name || 'Sem nome'}</option>
                                    `).join('')}
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Data do Nascimento *</label>
                                <input type="date" name="birth_date" required value="${new Date().toISOString().split('T')[0]}" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                            </div>
                        </div>
                        
                        <!-- Tipo de Parto -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Tipo de Parto *</label>
                            <select name="birth_type" required class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                                <option value="normal">Normal</option>
                                <option value="assistido">Assistido</option>
                                <option value="cesariana">Cesariana</option>
                                <option value="complicado">Complicado</option>
                            </select>
                        </div>
                        
                        <!-- Dados do Bezerro -->
                        <div class="border-t border-gray-200 pt-3">
                            <h4 class="text-sm font-semibold text-gray-900 mb-3">Dados do Bezerro</h4>
                            
                            <div class="grid grid-cols-2 gap-3 mb-3">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Número do Bezerro</label>
                                    <input type="text" name="calf_number" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500" placeholder="Ex: BZ001">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Sexo</label>
                                    <select name="calf_gender" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                                        <option value="">Selecione</option>
                                        <option value="femea">Fêmea</option>
                                        <option value="macho">Macho</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Peso (kg)</label>
                                    <input type="number" name="calf_weight" step="0.01" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500" placeholder="Ex: 35.5">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Raça</label>
                                    <input type="text" name="calf_breed" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500" placeholder="Ex: Holandesa">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Status -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Status da Mãe *</label>
                                <select name="mother_status" required class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                                    <option value="boa">Boa</option>
                                    <option value="problemas">Com Problemas</option>
                                    <option value="obito">Óbito</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Status do Bezerro *</label>
                                <select name="calf_status" required class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                                    <option value="vivo">Vivo</option>
                                    <option value="morto">Morto</option>
                                    <option value="deformado">Deformado</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Observações -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Observações</label>
                            <textarea name="notes" rows="3" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 resize-none" placeholder="Informações sobre o parto"></textarea>
                        </div>
                    </div>
                </form>
                
                <div class="border-t border-gray-200 px-4 py-3 flex items-center justify-end space-x-2 bg-gray-50">
                    <button onclick="closeAddBirthModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button onclick="submitAddBirth()" class="px-4 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg flex items-center space-x-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>Registrar Nascimento</span>
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        modal.addEventListener('click', e => { if (e.target === modal) closeAddBirthModal(); });
    };
    
    window.closeAddBirthModal = function() {
        const modal = document.getElementById('addBirthModal');
        if (modal) modal.remove();
    };
    
    window.submitAddBirth = async function() {
        const form = document.getElementById('addBirthForm');
        if (!form || !form.checkValidity()) {
            form?.reportValidity();
            return;
        }
        
        const formData = new FormData(form);
        const birthData = {
            action: 'add_birth',
            animal_id: formData.get('animal_id'),
            birth_date: formData.get('birth_date'),
            birth_type: formData.get('birth_type'),
            calf_number: formData.get('calf_number') || null,
            calf_gender: formData.get('calf_gender') || null,
            calf_weight: formData.get('calf_weight') || null,
            calf_breed: formData.get('calf_breed') || null,
            mother_status: formData.get('mother_status'),
            calf_status: formData.get('calf_status'),
            notes: formData.get('notes') || null,
            farm_id: 1
        };
        
        console.log('📤 Registrando nascimento:', birthData);
        
        const submitBtn = event.target;
        const originalContent = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg><span>Salvando...</span>';
        
        try {
            const response = await fetch('api/reproduction.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(birthData)
            });
            
            const data = await response.json();
            
            if (data.success) {
                showSuccessMessage('Nascimento registrado com sucesso!');
                closeAddBirthModal();
                await loadReproductionData();
                switchReproTab('births');
            } else {
                throw new Error(data.error || 'Erro ao registrar nascimento');
            }
        } catch (error) {
            console.error('❌ Erro:', error);
            alert('Erro: ' + error.message);
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalContent;
        }
    };
    
    // ==================== NOVA PRENHEZ ====================
    
    window.showAddPregnancyForm = async function() {
        console.log('🤰 Abrindo formulário de prenhez...');
        
        // Carregar animais se necessário
        if (animalsCache.length === 0) {
            const response = await fetch('api/animals.php?action=get_all');
            const data = await response.json();
            if (data.success && data.data) animalsCache = data.data;
        }
        
        const modal = document.createElement('div');
        modal.id = 'addPregnancyModal';
        modal.className = 'fixed inset-0 bg-black bg-opacity-60 z-[999999] flex items-center justify-center p-4';
        modal.innerHTML = `
            <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-hidden">
                <div class="bg-gradient-to-r from-purple-600 to-pink-600 px-4 py-3 flex items-center justify-between">
                    <h3 class="text-base font-bold text-white">Adicionar Prenhez Manualmente</h3>
                    <button onclick="closeAddPregnancyModal()" class="text-white hover:bg-white hover:bg-opacity-20 p-1 rounded">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                
                <form id="addPregnancyForm" class="p-4 overflow-y-auto max-h-[calc(90vh-120px)]">
                    <div class="space-y-3">
                        <!-- Animal e Data da Prenhez -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Animal (Fêmea) *</label>
                                <select name="animal_id" required class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                                    <option value="">Selecione</option>
                                    ${animalsCache.filter(a => a.gender === 'femea').map(a => `
                                        <option value="${a.id}">${a.animal_number} - ${a.name || 'Sem nome'}</option>
                                    `).join('')}
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Data da Prenhez *</label>
                                <input type="date" name="pregnancy_date" required value="${new Date().toISOString().split('T')[0]}" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                            </div>
                        </div>
                        
                        <!-- DPP e Fase -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Data Prevista do Parto (DPP) *</label>
                                <input type="date" name="expected_birth" required id="pregnancyDPP" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Fase da Gestação *</label>
                                <select name="pregnancy_stage" required class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                                    <option value="inicial">Inicial (0-90 dias)</option>
                                    <option value="meio">Meio (90-180 dias)</option>
                                    <option value="final">Final (180-260 dias)</option>
                                    <option value="pre-parto">Pré-Parto (>260 dias)</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Ultrassom -->
                        <div class="border-t border-gray-200 pt-3 mt-2">
                            <h4 class="text-sm font-semibold text-gray-900 mb-3">Ultrassom (Opcional)</h4>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Data do Ultrassom</label>
                                    <input type="date" name="ultrasound_date" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Resultado</label>
                                    <select name="ultrasound_result" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                                        <option value="">Não realizado</option>
                                        <option value="positivo">Positivo</option>
                                        <option value="negativo">Negativo</option>
                                        <option value="indefinido">Indefinido</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Observações -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Observações</label>
                            <textarea name="notes" rows="3" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 resize-none" placeholder="Informações sobre a prenhez"></textarea>
                        </div>
                        
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                            <p class="text-xs text-blue-800">
                                💡 Dica: Ao selecionar a data da prenhez, o DPP será calculado automaticamente (+280 dias)
                            </p>
                        </div>
                    </div>
                </form>
                
                <div class="border-t border-gray-200 px-4 py-3 flex items-center justify-end space-x-2 bg-gray-50">
                    <button onclick="closeAddPregnancyModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button onclick="submitAddPregnancy()" class="px-4 py-2 text-sm font-medium text-white bg-purple-600 hover:bg-purple-700 rounded-lg flex items-center space-x-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>Adicionar Prenhez</span>
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Auto-calcular DPP ao mudar data da prenhez
        const pregnancyDateInput = modal.querySelector('input[name="pregnancy_date"]');
        const dppInput = modal.querySelector('#pregnancyDPP');
        
        pregnancyDateInput.addEventListener('change', function() {
            const date = new Date(this.value);
            date.setDate(date.getDate() + 280);
            dppInput.value = date.toISOString().split('T')[0];
        });
        
        // Calcular DPP inicial
        if (pregnancyDateInput.value) {
            const date = new Date(pregnancyDateInput.value);
            date.setDate(date.getDate() + 280);
            dppInput.value = date.toISOString().split('T')[0];
        }
        
        modal.addEventListener('click', e => { if (e.target === modal) closeAddPregnancyModal(); });
    };
    
    window.closeAddPregnancyModal = function() {
        const modal = document.getElementById('addPregnancyModal');
        if (modal) modal.remove();
    };
    
    window.submitAddPregnancy = async function() {
        const form = document.getElementById('addPregnancyForm');
        if (!form || !form.checkValidity()) {
            form?.reportValidity();
            return;
        }
        
        const formData = new FormData(form);
        const pregnancyData = {
            action: 'add_pregnancy',
            animal_id: formData.get('animal_id'),
            pregnancy_date: formData.get('pregnancy_date'),
            expected_birth: formData.get('expected_birth'),
            pregnancy_stage: formData.get('pregnancy_stage'),
            ultrasound_date: formData.get('ultrasound_date') || null,
            ultrasound_result: formData.get('ultrasound_result') || null,
            notes: formData.get('notes') || null,
            farm_id: 1
        };
        
        console.log('📤 Adicionando prenhez:', pregnancyData);
        
        const submitBtn = event.target;
        const originalContent = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg><span>Salvando...</span>';
        
        try {
            const response = await fetch('api/reproduction.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(pregnancyData)
            });
            
            const data = await response.json();
            
            if (data.success) {
                showSuccessMessage('Prenhez adicionada com sucesso!');
                closeAddPregnancyModal();
                await loadReproductionData();
                switchReproTab('pregnancies');
            } else {
                throw new Error(data.error || 'Erro ao adicionar prenhez');
            }
        } catch (error) {
            console.error('❌ Erro:', error);
            alert('Erro: ' + error.message);
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalContent;
        }
    };
    
    window.viewPregnancyDetails = function(id) {
        alert('Detalhes da prenhez ' + id + ' em desenvolvimento');
    };
    
    window.viewInseminationDetails = function(id) {
        alert('Detalhes da IA ' + id + ' em desenvolvimento');
    };
    
    window.viewBirthDetails = function(id) {
        alert('Detalhes do nascimento ' + id + ' em desenvolvimento');
    };

    window.closeReproductionOverlay = function() {
        console.log('🔴 Fechando Sistema de Reprodução...');
        
        const overlay = document.getElementById('reproductionOverlay');
        if (overlay) {
            overlay.classList.add('hidden');
            document.body.style.overflow = '';
            
            console.log('✅ Overlay de Reprodução fechado!');
        }
    };

    // Dashboard Analítico
    window.showAnalyticsDashboard = function() {
        console.log('📊 Abrindo Dashboard Analítico...');
        
        const overlay = document.getElementById('analyticsOverlay');
        if (!overlay) {
            console.error('❌ Overlay de Dashboard Analítico não encontrado!');
            return;
        }
        
        overlay.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        
        // Carregar dados analíticos
        loadAnalyticsData();
        
        console.log('✅ Overlay de Dashboard Analítico aberto!');
    };
    
    // Carregar dados analíticos
    async function loadAnalyticsData() {
        try {
            console.log('📡 Carregando dados analíticos...');
            
            // Buscar estatísticas gerais da API
            const response = await fetch('api/analytics.php?action=get_dashboard');
            const text = await response.text();
            console.log('📄 Analytics Response:', text.substring(0, 300));
            
            const data = JSON.parse(text);
            
            if (data.success) {
                console.log('📦 Dados analíticos:', data);
                
                // Atualizar cards de visão geral
                document.getElementById('dashTotalAnimals').textContent = data.total_animals || 0;
                document.getElementById('dashTotalMilk').textContent = (data.milk_today || 0).toFixed(1);
                document.getElementById('dashPregnant').textContent = data.total_pregnant || 0;
                document.getElementById('dashHealthy').textContent = data.healthy_animals || 0;
                document.getElementById('dashAlerts').textContent = data.pending_alerts || 0;
                
                // Renderizar distribuições
                renderStatusDistribution(data.status_distribution || []);
                renderHealthDistribution(data.health_distribution || []);
                renderReproductiveDistribution(data.reproductive_distribution || []);
                renderProductionChart(data.production_7days || []);
                renderRecentActivities(data.recent_activities || []);
                
                console.log('✅ Dashboard analítico carregado!');
            }
        } catch (error) {
            console.error('❌ Erro ao carregar analytics:', error);
            
            // Valores padrão em caso de erro
            document.getElementById('dashTotalAnimals').textContent = '0';
            document.getElementById('dashTotalMilk').textContent = '0';
            document.getElementById('dashPregnant').textContent = '0';
            document.getElementById('dashHealthy').textContent = '0';
            document.getElementById('dashAlerts').textContent = '0';
        }
    }
    
    // Renderizar distribuição por status
    function renderStatusDistribution(distribution) {
        const container = document.getElementById('statusDistribution');
        if (!container || distribution.length === 0) {
            container.innerHTML = '<p class="text-xs text-gray-500 text-center py-2">Sem dados</p>';
            return;
        }
        
        const total = distribution.reduce((sum, item) => sum + parseInt(item.count), 0);
        
        container.innerHTML = distribution.map(item => {
            const percentage = total > 0 ? (item.count / total * 100).toFixed(1) : 0;
            const statusColor = getStatusColor(item.status);
            
            return `
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2 flex-1">
                        <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium ${statusColor}">
                            ${item.status}
                        </span>
                        <div class="flex-1 bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full" style="width: ${percentage}%"></div>
                        </div>
                    </div>
                    <span class="text-sm font-bold text-gray-900 ml-2">${item.count}</span>
                </div>
            `;
        }).join('');
    }
    
    // Renderizar distribuição de saúde
    function renderHealthDistribution(distribution) {
        const container = document.getElementById('healthDistribution');
        if (!container || distribution.length === 0) {
            container.innerHTML = '<p class="text-xs text-gray-500 text-center py-2">Sem dados</p>';
            return;
        }
        
        container.innerHTML = distribution.map(item => {
            const healthColor = getHealthStatusColor(item.health_status);
            
            return `
                <div class="flex items-center justify-between">
                    <span class="inline-flex px-2 py-1 rounded text-xs font-medium ${healthColor}">
                        ${translateHealthStatus(item.health_status)}
                    </span>
                    <span class="text-sm font-bold text-gray-900">${item.count}</span>
                </div>
            `;
        }).join('');
    }
    
    // Renderizar distribuição reprodutiva
    function renderReproductiveDistribution(distribution) {
        const container = document.getElementById('reproductiveDistribution');
        if (!container || distribution.length === 0) {
            container.innerHTML = '<p class="text-xs text-gray-500 text-center py-2">Sem dados</p>';
            return;
        }
        
        container.innerHTML = distribution.map(item => {
            const reproColor = getReproductiveStatusColor(item.reproductive_status);
            
            return `
                <div class="flex items-center justify-between">
                    <span class="inline-flex px-2 py-1 rounded text-xs font-medium ${reproColor}">
                        ${translateReproductiveStatus(item.reproductive_status)}
                    </span>
                    <span class="text-sm font-bold text-gray-900">${item.count}</span>
                </div>
            `;
        }).join('');
    }
    
    // Renderizar gráfico de produção (simplificado)
    function renderProductionChart(production) {
        const container = document.getElementById('productionChart');
        if (!container || production.length === 0) {
            container.innerHTML = '<p class="text-xs text-gray-500 text-center py-2">Sem dados de produção</p>';
            return;
        }
        
        const maxVolume = Math.max(...production.map(p => parseFloat(p.total_volume || 0)));
        
        container.innerHTML = production.map(item => {
            const percentage = maxVolume > 0 ? (item.total_volume / maxVolume * 100) : 0;
            
            return `
                <div class="space-y-1">
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-gray-600">${formatDate(item.record_date)}</span>
                        <span class="font-bold text-gray-900">${parseFloat(item.total_volume).toFixed(1)}L</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-blue-600 h-2 rounded-full transition-all" style="width: ${percentage}%"></div>
                    </div>
                </div>
            `;
        }).join('');
    }
    
    // Renderizar atividades recentes
    function renderRecentActivities(activities) {
        const container = document.getElementById('recentActivities');
        if (!container || activities.length === 0) {
            container.innerHTML = '<p class="text-xs text-gray-500 text-center py-4">Nenhuma atividade recente</p>';
            return;
        }
        
        container.innerHTML = `
            <div class="space-y-2">
                ${activities.slice(0, 10).map(activity => `
                    <div class="flex items-start space-x-3 p-2 hover:bg-gray-50 rounded-lg">
                        <div class="flex-shrink-0 w-8 h-8 ${getActivityColor(activity.type)} rounded-lg flex items-center justify-center">
                            ${getActivityIcon(activity.type)}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-gray-900 font-medium">${activity.description}</p>
                            <p class="text-xs text-gray-500">${formatDateTime(activity.created_at)}</p>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
    }
    
    function getActivityColor(type) {
        const colors = {
            'animal': 'bg-emerald-100',
            'health': 'bg-red-100',
            'reproduction': 'bg-purple-100',
            'production': 'bg-blue-100'
        };
        return colors[type] || 'bg-gray-100';
    }
    
    function getActivityIcon(type) {
        const icons = {
            'animal': '<svg class="w-4 h-4 text-emerald-600" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z"/></svg>',
            'health': '<svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>',
            'reproduction': '<svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>',
            'production': '<svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>'
        };
        return icons[type] || '<svg class="w-4 h-4 text-gray-600" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/></svg>';
    };

    window.closeAnalyticsOverlay = function() {
        console.log('🔴 Fechando Dashboard Analítico...');
        
        const overlay = document.getElementById('analyticsOverlay');
        if (overlay) {
            overlay.classList.add('hidden');
            document.body.style.overflow = '';
            
            console.log('✅ Overlay de Dashboard Analítico fechado!');
        }
    };

    // ==================== CONTROLE DE NOVILHAS ====================
    
    let heiferCostsCache = [];
    
    window.openHeiferManagement = function() {
        console.log('🐮 Abrindo Controle de Novilhas...');
        
        const overlay = document.getElementById('heiferOverlay');
        if (!overlay) {
            console.error('❌ Overlay de Novilhas não encontrado!');
            return;
        }
        
        overlay.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        
        // Carregar dados
        loadHeiferData();
        
        console.log('✅ Overlay de Novilhas aberto!');
    };
    
    window.closeHeiferOverlay = function() {
        console.log('🔴 Fechando Controle de Novilhas...');
        
        const overlay = document.getElementById('heiferOverlay');
        if (overlay) {
            overlay.classList.add('hidden');
            document.body.style.overflow = '';
            
            console.log('✅ Overlay de Novilhas fechado!');
        }
    };
    
    // Carregar dados de novilhas
    async function loadHeiferData() {
        try {
            console.log('📡 Carregando dados de novilhas...');
            
            // Carregar novilhas dos animais
            if (animalsCache.length === 0) {
                const response = await fetch('api/animals.php?action=get_all');
                const data = await response.json();
                if (data.success && data.data) animalsCache = data.data;
            }
            
            // Carregar custos de novilhas
            const costsRes = await fetch('api/heifer_costs.php?action=get_all');
            const costsData = await costsRes.json();
            
            console.log('📦 Custos:', costsData);
            
            heiferCostsCache = costsData.success && costsData.data ? costsData.data : [];
            
            // Filtrar apenas novilhas
            const heifers = animalsCache.filter(a => a.status === 'Novilha');
            
            // Calcular estatísticas
            updateHeiferStats(heifers);
            
            // Renderizar tabela
            renderHeiferTable(heifers);
            
            console.log(`✅ ${heifers.length} novilhas carregadas!`);
        } catch (error) {
            console.error('❌ Erro ao carregar novilhas:', error);
            
            document.getElementById('heiferTotalCount').textContent = '0';
            document.getElementById('heiferAvgCost').textContent = 'R$ 0';
            document.getElementById('heiferAvgAge').textContent = '0m';
            document.getElementById('heiferTotalCost').textContent = 'R$ 0';
        }
    }
    
    // Atualizar estatísticas
    function updateHeiferStats(heifers) {
        const total = heifers.length;
        
        // Calcular custos totais por animal
        const costsPerAnimal = {};
        heiferCostsCache.forEach(cost => {
            if (!costsPerAnimal[cost.animal_id]) {
                costsPerAnimal[cost.animal_id] = 0;
            }
            costsPerAnimal[cost.animal_id] += parseFloat(cost.cost_amount || 0);
        });
        
        const totalCost = Object.values(costsPerAnimal).reduce((sum, cost) => sum + cost, 0);
        const avgCost = total > 0 ? totalCost / total : 0;
        
        // Calcular idade média
        let totalAge = 0;
        heifers.forEach(h => {
            if (h.birth_date) {
                const birth = new Date(h.birth_date);
                const today = new Date();
                const diffDays = Math.ceil((today - birth) / (1000 * 60 * 60 * 24));
                totalAge += diffDays;
            }
        });
        const avgAge = total > 0 ? Math.floor(totalAge / total / 30) : 0; // em meses
        
        document.getElementById('heiferTotalCount').textContent = total;
        document.getElementById('heiferAvgCost').textContent = `R$ ${avgCost.toFixed(2)}`;
        document.getElementById('heiferAvgAge').textContent = `${avgAge}m`;
        document.getElementById('heiferTotalCost').textContent = `R$ ${totalCost.toFixed(2)}`;
        
        console.log(`📊 Stats Novilhas: Total=${total}, CustoMédio=R$${avgCost.toFixed(2)}, IdadeMédia=${avgAge}m`);
    }
    
    // Renderizar tabela de novilhas
    function renderHeiferTable(heifers) {
        const tbody = document.getElementById('heiferTableBody');
        if (!tbody) return;
        
        if (heifers.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="px-3 py-8 text-center text-gray-500">
                        <p class="text-sm">Nenhuma novilha encontrada</p>
                    </td>
                </tr>
            `;
            return;
        }
        
        // Calcular custos por animal
        const costsPerAnimal = {};
        heiferCostsCache.forEach(cost => {
            if (!costsPerAnimal[cost.animal_id]) {
                costsPerAnimal[cost.animal_id] = 0;
            }
            costsPerAnimal[cost.animal_id] += parseFloat(cost.cost_amount || 0);
        });
        
        tbody.innerHTML = heifers.map(heifer => {
            const age = calculateAge(heifer.birth_date);
            const totalCost = costsPerAnimal[heifer.id] || 0;
            
            return `
                <tr class="hover:bg-gray-50">
                    <td class="px-3 py-2 font-medium text-gray-900">${heifer.animal_number} - ${heifer.name || 'Sem nome'}</td>
                    <td class="px-3 py-2 text-gray-600">${age}</td>
                    <td class="px-3 py-2 text-gray-600">${heifer.breed}</td>
                    <td class="px-3 py-2 font-semibold ${totalCost > 0 ? 'text-red-600' : 'text-gray-400'}">
                        R$ ${totalCost.toFixed(2)}
                    </td>
                    <td class="px-3 py-2">
                        <button onclick="viewHeiferDetails(${heifer.id})" class="text-orange-600 hover:text-orange-700 text-xs font-medium">
                            Ver Custos
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    }
    
    // Ver detalhes e custos de uma novilha
    window.viewHeiferDetails = function(animalId) {
        const heifer = animalsCache.find(a => a.id === animalId);
        if (!heifer) {
            alert('Novilha não encontrada!');
            return;
        }
        
        const costs = heiferCostsCache.filter(c => c.animal_id == animalId);
        const totalCost = costs.reduce((sum, c) => sum + parseFloat(c.cost_amount || 0), 0);
        
        // Calcular idade
        const age = calculateAge(heifer.birth_date);
        
        // Agrupar custos por categoria
        const costsByCategory = {};
        costs.forEach(cost => {
            if (!costsByCategory[cost.cost_category]) {
                costsByCategory[cost.cost_category] = {
                    total: 0,
                    count: 0
                };
            }
            costsByCategory[cost.cost_category].total += parseFloat(cost.cost_amount || 0);
            costsByCategory[cost.cost_category].count++;
        });
        
        // Ícones por categoria
        const categoryIcons = {
            'Alimentação': '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>',
            'Medicamentos': '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>',
            'Vacinas': '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>',
            'Manejo': '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>',
            'Transporte': '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>',
            'Outros': '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>'
        };
        
        const modal = document.createElement('div');
        modal.id = 'heiferCostsModal';
        modal.className = 'fixed inset-0 bg-black bg-opacity-60 z-[999999] flex items-center justify-center p-4';
        modal.innerHTML = `
            <div class="bg-white rounded-xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-hidden">
                <!-- Header -->
                <div class="bg-gradient-to-r from-orange-600 to-red-600 px-4 py-3 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold text-white">${heifer.animal_number} - ${heifer.name || 'Sem nome'}</h3>
                        <p class="text-xs text-orange-100">${heifer.breed} • ${age}</p>
                    </div>
                    <button onclick="closeHeiferCostsModal()" class="text-white hover:bg-white hover:bg-opacity-20 p-1 rounded">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                
                <!-- Resumo -->
                <div class="p-4 bg-gray-50 border-b border-gray-200">
                    <div class="grid grid-cols-3 gap-3">
                        <div class="bg-white rounded-lg p-3 text-center">
                            <p class="text-xs text-gray-500 mb-1">Total de Custos</p>
                            <p class="text-lg font-bold text-orange-600">R$ ${totalCost.toFixed(2)}</p>
                        </div>
                        <div class="bg-white rounded-lg p-3 text-center">
                            <p class="text-xs text-gray-500 mb-1">Registros</p>
                            <p class="text-lg font-bold text-gray-900">${costs.length}</p>
                        </div>
                        <div class="bg-white rounded-lg p-3 text-center">
                            <p class="text-xs text-gray-500 mb-1">Custo/Dia</p>
                            <p class="text-lg font-bold text-blue-600">R$ ${costs.length > 0 ? (totalCost / Math.max(1, calculateAgeDays(heifer.birth_date))).toFixed(2) : '0.00'}</p>
                        </div>
                    </div>
                </div>
                
                <!-- Custos por Categoria -->
                <div class="p-4 border-b border-gray-200">
                    <h4 class="text-sm font-semibold text-gray-900 mb-3">Custos por Categoria</h4>
                    <div class="grid grid-cols-2 gap-2">
                        ${Object.keys(costsByCategory).map(category => `
                            <div class="bg-gray-50 rounded-lg p-2 flex items-center space-x-2">
                                <div class="w-8 h-8 bg-orange-100 rounded-lg flex items-center justify-center text-orange-600 flex-shrink-0">
                                    ${categoryIcons[category] || categoryIcons['Outros']}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-medium text-gray-900">${category}</p>
                                    <p class="text-xs text-gray-500">${costsByCategory[category].count}x - R$ ${costsByCategory[category].total.toFixed(2)}</p>
                                </div>
                            </div>
                        `).join('')}
                        ${Object.keys(costsByCategory).length === 0 ? '<p class="col-span-2 text-center text-sm text-gray-500 py-4">Nenhum custo registrado</p>' : ''}
                    </div>
                </div>
                
                <!-- Lista de Custos -->
                <div class="p-4 overflow-y-auto max-h-[calc(90vh-400px)]">
                    <h4 class="text-sm font-semibold text-gray-900 mb-3">Histórico de Custos</h4>
                    <div class="space-y-2">
                        ${costs.length > 0 ? costs.sort((a, b) => new Date(b.cost_date) - new Date(a.cost_date)).map(cost => `
                            <div class="bg-gray-50 rounded-lg p-3 hover:bg-gray-100 transition-colors">
                                <div class="flex items-start justify-between">
                                    <div class="flex items-start space-x-3 flex-1">
                                        <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center text-orange-600 flex-shrink-0">
                                            ${categoryIcons[cost.cost_category] || categoryIcons['Outros']}
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center space-x-2 mb-1">
                                                <span class="px-2 py-0.5 text-xs font-medium bg-orange-100 text-orange-700 rounded">${cost.cost_category}</span>
                                                <span class="text-xs text-gray-500">${formatDate(cost.cost_date)}</span>
                                            </div>
                                            <p class="text-sm font-medium text-gray-900 mb-1">${cost.description}</p>
                                            <p class="text-xs text-gray-500">Registrado por: Usuário #${cost.recorded_by}</p>
                                        </div>
                                    </div>
                                    <div class="text-right flex-shrink-0 ml-3">
                                        <p class="text-base font-bold text-red-600">R$ ${parseFloat(cost.cost_amount).toFixed(2)}</p>
                                        <button onclick="deleteHeiferCost(${cost.id})" class="text-xs text-red-600 hover:text-red-700 mt-1">
                                            Excluir
                                        </button>
                                    </div>
                                </div>
                            </div>
                        `).join('') : '<p class="text-center text-sm text-gray-500 py-8">Nenhum custo registrado para esta novilha</p>'}
                    </div>
                </div>
                
                <!-- Footer -->
                <div class="border-t border-gray-200 px-4 py-3 flex items-center justify-between bg-gray-50">
                    <button onclick="closeHeiferCostsModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                        Fechar
                    </button>
                    <button onclick="closeHeiferCostsModal(); showAddHeiferCostFormForAnimal(${animalId})" class="px-4 py-2 text-sm font-medium text-white bg-orange-600 hover:bg-orange-700 rounded-lg flex items-center space-x-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span>Adicionar Custo</span>
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        modal.addEventListener('click', e => { if (e.target === modal) closeHeiferCostsModal(); });
    };
    
    window.closeHeiferCostsModal = function() {
        const modal = document.getElementById('heiferCostsModal');
        if (modal) modal.remove();
    };
    
    // Calcular idade em dias
    function calculateAgeDays(birthDate) {
        if (!birthDate) return 0;
        const birth = new Date(birthDate);
        const today = new Date();
        return Math.ceil((today - birth) / (1000 * 60 * 60 * 24));
    }
    
    // Excluir custo
    window.deleteHeiferCost = async function(costId) {
        if (!confirm('Tem certeza que deseja excluir este custo?')) return;
        
        try {
            const response = await fetch('api/heifer_costs.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'delete', id: costId })
            });
            
            const data = await response.json();
            
            if (data.success) {
                showSuccessMessage('Custo excluído com sucesso!');
                closeHeiferCostsModal();
                await loadHeiferData();
            } else {
                throw new Error(data.error || 'Erro ao excluir custo');
            }
        } catch (error) {
            console.error('❌ Erro:', error);
            alert('Erro ao excluir: ' + error.message);
        }
    };
    
    // Adicionar custo para animal específico
    window.showAddHeiferCostFormForAnimal = async function(preSelectedAnimalId) {
        await showAddHeiferCostForm();
        
        // Pre-selecionar o animal
        setTimeout(() => {
            const select = document.querySelector('#addHeiferCostForm select[name="animal_id"]');
            if (select && preSelectedAnimalId) {
                select.value = preSelectedAnimalId;
            }
        }, 100);
    };
    
    // Adicionar custo de novilha
    window.showAddHeiferCostForm = async function() {
        console.log('💰 Abrindo formulário de custo...');
        
        // Carregar novilhas
        if (animalsCache.length === 0) {
            const response = await fetch('api/animals.php?action=get_all');
            const data = await response.json();
            if (data.success && data.data) animalsCache = data.data;
        }
        
        const heifers = animalsCache.filter(a => a.status === 'Novilha');
        
        const modal = document.createElement('div');
        modal.id = 'addHeiferCostModal';
        modal.className = 'fixed inset-0 bg-black bg-opacity-60 z-[999999] flex items-center justify-center p-4';
        modal.innerHTML = `
            <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-hidden">
                <div class="bg-gradient-to-r from-orange-600 to-red-600 px-4 py-3 flex items-center justify-between">
                    <h3 class="text-base font-bold text-white">Adicionar Custo de Criação</h3>
                    <button onclick="closeAddHeiferCostModal()" class="text-white hover:bg-white hover:bg-opacity-20 p-1 rounded">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                
                <form id="addHeiferCostForm" class="p-4 overflow-y-auto max-h-[calc(90vh-120px)]">
                    <div class="space-y-3">
                        <!-- Novilha e Data -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Novilha *</label>
                                <select name="animal_id" required class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500">
                                    <option value="">Selecione</option>
                                    ${heifers.map(h => `
                                        <option value="${h.id}">${h.animal_number} - ${h.name || 'Sem nome'}</option>
                                    `).join('')}
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Data do Custo *</label>
                                <input type="date" name="cost_date" required value="${new Date().toISOString().split('T')[0]}" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500">
                            </div>
                        </div>
                        
                        <!-- Categoria e Valor -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Categoria *</label>
                                <select name="cost_category" required class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500">
                                    <option value="">Selecione</option>
                                    <option value="Alimentação">Alimentação</option>
                                    <option value="Medicamentos">Medicamentos</option>
                                    <option value="Vacinas">Vacinas</option>
                                    <option value="Manejo">Manejo</option>
                                    <option value="Transporte">Transporte</option>
                                    <option value="Outros">Outros</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Valor (R$) *</label>
                                <input type="number" name="cost_amount" required step="0.01" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500" placeholder="Ex: 150.00">
                            </div>
                        </div>
                        
                        <!-- Descrição -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Descrição *</label>
                            <textarea name="description" required rows="3" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 resize-none" placeholder="Descreva o custo (ex: Ração concentrada 50kg)"></textarea>
                        </div>
                    </div>
                </form>
                
                <div class="border-t border-gray-200 px-4 py-3 flex items-center justify-end space-x-2 bg-gray-50">
                    <button onclick="closeAddHeiferCostModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button onclick="submitAddHeiferCost()" class="px-4 py-2 text-sm font-medium text-white bg-orange-600 hover:bg-orange-700 rounded-lg flex items-center space-x-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>Adicionar Custo</span>
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        modal.addEventListener('click', e => { if (e.target === modal) closeAddHeiferCostModal(); });
    };
    
    window.closeAddHeiferCostModal = function() {
        const modal = document.getElementById('addHeiferCostModal');
        if (modal) modal.remove();
    };
    
    window.submitAddHeiferCost = async function() {
        const form = document.getElementById('addHeiferCostForm');
        if (!form || !form.checkValidity()) {
            form?.reportValidity();
            return;
        }
        
        const formData = new FormData(form);
        const costData = {
            action: 'insert',
            animal_id: formData.get('animal_id'),
            cost_date: formData.get('cost_date'),
            cost_category: formData.get('cost_category'),
            cost_amount: formData.get('cost_amount'),
            description: formData.get('description'),
            farm_id: 1
        };
        
        console.log('📤 Adicionando custo:', costData);
        
        const submitBtn = event.target;
        const originalContent = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg><span>Salvando...</span>';
        
        try {
            const response = await fetch('api/heifer_costs.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(costData)
            });
            
            const data = await response.json();
            
            if (data.success) {
                showSuccessMessage('Custo adicionado com sucesso!');
                closeAddHeiferCostModal();
                await loadHeiferData();
            } else {
                throw new Error(data.error || 'Erro ao adicionar custo');
            }
        } catch (error) {
            console.error('❌ Erro:', error);
            alert('Erro: ' + error.message);
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalContent;
        }
    };


    window.closeManagerPhotoModal = function() {
        closeModal('managerPhotoChoiceModal');
    };

    // ==================== MODAL DE CONFIRMAÇÃO DE LOGOUT ====================
    
    window.showLogoutConfirmation = function() {
        console.log('🚪 Abrindo modal de confirmação de logout...');
        
        const modal = document.getElementById('logoutConfirmModal');
        if (!modal) {
            console.error('❌ Modal de logout não encontrado!');
            return;
        }
        
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        
        console.log('✅ Modal de confirmação aberto!');
    };
    
    window.closeLogoutConfirmation = function() {
        console.log('❌ Fechando modal de confirmação...');
        
        const modal = document.getElementById('logoutConfirmModal');
        if (modal) {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
            
            console.log('✅ Modal fechado!');
        }
    };
    
    window.confirmLogout = function() {
        console.log('👋 Usuário confirmou logout...');
        
        // Fechar o modal
        closeLogoutConfirmation();
        
        // Mostrar mensagem de saída
        const loadingMsg = document.createElement('div');
        loadingMsg.id = 'logoutLoadingScreen';
        loadingMsg.className = 'fixed inset-0 bg-black bg-opacity-80 z-[9999999] flex items-center justify-center';
        loadingMsg.innerHTML = `
            <div class="bg-white rounded-xl p-6 text-center max-w-sm">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-red-600 mx-auto mb-4"></div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Saindo do sistema...</h3>
                <p class="text-sm text-gray-600">Aguarde um momento</p>
            </div>
        `;
        document.body.appendChild(loadingMsg);
        
        console.log('🔄 Executando logout...');
        
        // Executar logout imediatamente
        setTimeout(() => {
            try {
                console.log('🧹 Limpando dados locais...');
                
                // Limpar dados locais
                try {
                    localStorage.clear();
                    sessionStorage.clear();
                } catch (e) {
                    console.warn('Erro ao limpar storage:', e);
                }
                
                console.log('✅ Dados limpos!');
                console.log('🔄 Redirecionando para logout.php...');
                
                // Redirecionar para logout.php que vai destruir a sessão PHP
                window.location.replace('logout.php');
                
            } catch (error) {
                console.error('❌ Erro ao fazer logout:', error);
                
                // Forçar redirecionamento direto para index.php
                window.location.href = 'logout.php';
            }
        }, 500);
    };
    
    // Fechar modal com ESC
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            const modal = document.getElementById('logoutConfirmModal');
            if (modal && !modal.classList.contains('hidden')) {
                closeLogoutConfirmation();
            }
        }
    });

    window.closeContactForm = function() {
        closeModal('contactFormModal');
    };

    window.closeNotificationsModal = function() {
        const modal = document.getElementById('notificationsModal');
        const content = document.getElementById('notificationsModalContent');
        if (content) {
            content.classList.remove('translate-x-0');
            content.classList.add('translate-x-full');
        }
        setTimeout(() => {
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.body.style.overflow = '';
            }
        }, 300);
    };

    window.openNotificationsModal = function() {
        const modal = document.getElementById('notificationsModal');
        const content = document.getElementById('notificationsModalContent');
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';
            setTimeout(() => {
                if (content) {
                    content.classList.remove('translate-x-full');
                    content.classList.add('translate-x-0');
                }
            }, 10);
        }
    };

    // Fechar modais ao pressionar ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            // Encontrar modal aberto
            const openModals = document.querySelectorAll('.fullscreen-modal:not(.hidden), .modal:not(.hidden), [id*="Modal"]:not(.hidden)');
            openModals.forEach(modal => {
                if (modal.id) {
                    closeModal(modal.id);
                }
            });
        }
    });

    console.log('✅ Sistema de modais carregado');
</script>

<!-- OVERLAY DE PERFIL - FULLSCREEN (SEM RECARREGAMENTO) -->
<div id="profileOverlay" class="fixed inset-0 bg-black bg-opacity-60 z-[99999] hidden transition-all duration-300 backdrop-blur-sm">
    <div class="w-full h-full bg-gradient-to-br from-gray-50 to-gray-100 flex flex-col transform transition-transform duration-300" id="profileOverlayContent">
        <!-- Header Simples -->
        <div class="bg-white border-b border-gray-200">
            <div class="flex items-center justify-between p-3 sm:p-4">
                <button onclick="closeProfileOverlay()" class="group p-1.5 sm:p-2 hover:bg-gray-100 rounded-lg transition-all duration-200 flex items-center space-x-1.5 text-gray-600 hover:text-gray-900">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    <span class="hidden sm:inline text-xs font-medium">Voltar</span>
                </button>
                <h2 class="text-base sm:text-lg font-bold text-gray-900">Meu Perfil</h2>
                <button onclick="closeProfileOverlay()" class="p-1.5 sm:p-2 hover:bg-gray-100 rounded-lg transition-all duration-200 text-gray-600 hover:text-gray-900">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
        
        <!-- Conteúdo -->
        <div class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
            <div class="max-w-5xl mx-auto space-y-4 sm:space-y-6">
                <!-- Informações do Usuário -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="p-3 sm:p-4">
                        <div class="flex items-center space-x-3">
                            <div class="relative">
                                <img id="overlayProfilePhoto" src="" alt="Foto de Perfil" class="w-12 h-12 sm:w-14 sm:h-14 object-cover rounded-lg shadow-sm hidden border border-gray-200">
                                <div id="overlayProfileIcon" class="w-12 h-12 sm:w-14 sm:h-14 bg-gray-100 rounded-lg flex items-center justify-center shadow-sm border border-gray-200">
                                    <svg class="w-6 h-6 sm:w-7 sm:h-7 text-gray-500" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="flex-1">
                                <h1 class="text-base sm:text-lg font-semibold text-gray-900" id="overlayProfileName">Carregando...</h1>
                                <div class="flex items-center space-x-2 mt-0.5">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800" id="overlayProfileRole">
                                        Carregando...
                                    </span>
                                    <span class="text-xs text-gray-500" id="overlayProfileFarmName">Carregando...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Informações Pessoais -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="bg-gradient-to-r from-gray-50 to-white px-3 sm:px-4 py-2.5 border-b border-gray-200">
                        <h3 class="text-sm sm:text-base font-bold text-gray-900 flex items-center">
                            <svg class="w-4 h-4 mr-1.5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            Informações Pessoais
                        </h3>
                    </div>
                    <div class="p-3 sm:p-4">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
                            <div class="group">
                                <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">Nome Completo</label>
                                <div class="flex items-center space-x-2 p-2.5 bg-gray-50 rounded-lg group-hover:bg-gray-100 transition-colors">
                                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    <p class="text-gray-900 font-medium text-sm" id="overlayProfileFullName">Carregando...</p>
                                </div>
                            </div>
                            <div class="group">
                                <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">Email</label>
                                <div class="flex items-center space-x-2 p-2.5 bg-gray-50 rounded-lg group-hover:bg-gray-100 transition-colors">
                                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path>
                                    </svg>
                                    <p class="text-gray-900 text-sm break-all" id="overlayProfileEmail">Carregando...</p>
                                </div>
                            </div>
                            <div class="group">
                                <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">WhatsApp</label>
                                <div class="flex items-center space-x-2 p-2.5 bg-gray-50 rounded-lg group-hover:bg-gray-100 transition-colors">
                                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                                    </svg>
                                    <p class="text-gray-900 text-sm" id="overlayProfileWhatsApp">Carregando...</p>
                                </div>
                            </div>
                            <div class="group">
                                <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wide">Cargo</label>
                                <div class="flex items-center space-x-2 p-2.5 bg-gradient-to-r from-green-50 to-green-100 rounded-lg border border-green-200">
                                    <svg class="w-4 h-4 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                    </svg>
                                    <p class="text-green-900 font-semibold text-sm">Gerente</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Ações Rápidas -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="bg-gradient-to-r from-gray-50 to-white px-3 sm:px-4 py-2.5 border-b border-gray-200">
                        <h3 class="text-sm sm:text-base font-bold text-gray-900 flex items-center">
                            <svg class="w-4 h-4 mr-1.5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                            Ações Rápidas
                        </h3>
                    </div>
                    <div class="p-3 sm:p-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <button onclick="closeProfileOverlay(); setTimeout(() => openManagerPhotoModal(), 300)" class="group flex items-center space-x-2.5 p-3 bg-gradient-to-br from-green-50 to-green-100 hover:from-green-100 hover:to-green-200 border border-green-200 rounded-lg transition-all duration-200">
                                <div class="flex-shrink-0 w-9 h-9 bg-green-600 bg-opacity-10 rounded-lg flex items-center justify-center group-hover:bg-opacity-20 transition-all">
                                    <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                                <div class="text-left flex-1">
                                    <p class="font-semibold text-gray-900 text-sm">Alterar Foto</p>
                                    <p class="text-xs text-gray-600">Atualizar foto de perfil</p>
                                </div>
                            </button>
                            
                            <button onclick="window.location.href='alterar-senha.php'" class="group flex items-center space-x-2.5 p-3 bg-gradient-to-br from-gray-50 to-gray-100 hover:from-gray-100 hover:to-gray-200 border border-gray-200 rounded-lg transition-all duration-200">
                                <div class="flex-shrink-0 w-9 h-9 bg-gray-600 bg-opacity-10 rounded-lg flex items-center justify-center group-hover:bg-opacity-20 transition-all">
                                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                                    </svg>
                                </div>
                                <div class="text-left flex-1">
                                    <p class="font-semibold text-gray-900 text-sm">Alterar Senha</p>
                                    <p class="text-xs text-gray-600">Manter conta segura</p>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Estatísticas -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="bg-gradient-to-r from-gray-50 to-white px-3 sm:px-4 py-2.5 border-b border-gray-200">
                        <h3 class="text-sm sm:text-base font-bold text-gray-900 flex items-center">
                            <svg class="w-4 h-4 mr-1.5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                            Estatísticas da Conta
                        </h3>
                    </div>
                    <div class="p-3 sm:p-4">
                        <div class="grid grid-cols-3 gap-2 sm:gap-3">
                            <div class="bg-gradient-to-br from-green-50 to-white p-3 rounded-lg border border-green-200">
                                <div class="flex flex-col items-center text-center">
                                    <div class="w-10 h-10 bg-green-600 bg-opacity-10 rounded-full flex items-center justify-center mb-2">
                                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                    <p class="text-base sm:text-lg font-bold text-green-600">Ativo</p>
                                    <p class="text-xs text-gray-600">Status</p>
                                </div>
                            </div>
                            
                            <div class="bg-gradient-to-br from-gray-50 to-white p-3 rounded-lg border border-gray-200">
                                <div class="flex flex-col items-center text-center">
                                    <div class="w-10 h-10 bg-gray-600 bg-opacity-10 rounded-full flex items-center justify-center mb-2">
                                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                    <p class="text-base sm:text-lg font-bold text-gray-900">Hoje</p>
                                    <p class="text-xs text-gray-600">Acesso</p>
                                </div>
                            </div>
                            
                            <div class="bg-gradient-to-br from-gray-50 to-white p-3 rounded-lg border border-gray-200">
                                <div class="flex flex-col items-center text-center">
                                    <div class="w-10 h-10 bg-gray-600 bg-opacity-10 rounded-full flex items-center justify-center mb-2">
                                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                    <p class="text-base sm:text-lg font-bold text-gray-900">Gerente</p>
                                    <p class="text-xs text-gray-600">Nível</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Botão Sair -->
                <div class="bg-gradient-to-br from-red-50 to-white rounded-xl shadow-sm border border-red-200 overflow-hidden">
                    <button onclick="showLogoutConfirmation()" class="group w-full flex items-center justify-between p-3.5 sm:p-4 hover:bg-gradient-to-br hover:from-red-100 hover:to-red-50 transition-all duration-200">
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0 w-10 h-10 bg-red-600 bg-opacity-10 rounded-lg flex items-center justify-center group-hover:bg-opacity-20 transition-all">
                                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                </svg>
                            </div>
                            <div class="text-left">
                                <p class="font-bold text-red-900 text-sm sm:text-base">Sair do Sistema</p>
                                <p class="text-xs text-red-600">Encerrar sessão</p>
                            </div>
                        </div>
                        <svg class="w-4 h-4 text-red-600 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- OVERLAY GESTÃO DE REBANHO -->
<div id="animalOverlay" class="fixed inset-0 bg-black bg-opacity-60 z-[99999] hidden transition-all duration-300 backdrop-blur-sm">
    <div class="w-full h-full bg-gradient-to-br from-gray-50 to-gray-100 flex flex-col transform transition-transform duration-300">
        <!-- Header -->
        <div class="bg-white border-b border-gray-200">
            <div class="flex items-center justify-between p-3 sm:p-4">
                <button onclick="closeAnimalOverlay()" class="group p-1.5 sm:p-2 hover:bg-gray-100 rounded-lg transition-all duration-200 flex items-center space-x-1.5 text-gray-600 hover:text-gray-900">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    <span class="hidden sm:inline text-xs font-medium">Voltar</span>
                </button>
                <h2 class="text-base sm:text-lg font-bold text-gray-900">Gestão de Rebanho</h2>
                <button onclick="closeAnimalOverlay()" class="p-1.5 sm:p-2 hover:bg-gray-100 rounded-lg transition-all duration-200 text-gray-600 hover:text-gray-900">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
        
        <!-- Conteúdo -->
        <div class="flex-1 overflow-y-auto p-4 sm:p-6">
            <div class="max-w-6xl mx-auto space-y-4">
                
                <!-- Cards de Estatísticas -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                    <!-- Total de Animais -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-3">
                        <div class="flex items-center space-x-2">
                            <div class="w-10 h-10 bg-emerald-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-emerald-600" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs text-gray-500">Total</p>
                                <p class="text-lg font-bold text-gray-900" id="animalTotalCount">-</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Lactantes -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-3">
                        <div class="flex items-center space-x-2">
                            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs text-gray-500">Lactantes</p>
                                <p class="text-lg font-bold text-blue-600" id="animalLactantCount">-</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Secas -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-3">
                        <div class="flex items-center space-x-2">
                            <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs text-gray-500">Secas</p>
                                <p class="text-lg font-bold text-amber-600" id="animalSecaCount">-</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Novilhas -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-3">
                        <div class="flex items-center space-x-2">
                            <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs text-gray-500">Novilhas</p>
                                <p class="text-lg font-bold text-purple-600" id="animalNovilhaCount">-</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Barra de Ações -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <button onclick="showAddAnimalForm()" class="flex items-center space-x-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            <span>Adicionar Animal</span>
                        </button>
                        
                        <div class="flex-1 min-w-[200px] max-w-md">
                            <input type="text" id="animalSearchInput" placeholder="Buscar por número ou nome..." class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                        </div>
                        
                        <select id="animalFilterStatus" class="px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500">
                            <option value="">Todos Status</option>
                            <option value="Lactante">Lactante</option>
                            <option value="Seco">Seca</option>
                            <option value="Novilha">Novilha</option>
                            <option value="Vaca">Vaca</option>
                            <option value="Bezerra">Bezerra</option>
                            <option value="Bezerro">Bezerro</option>
                            <option value="Touro">Touro</option>
                        </select>
                    </div>
                </div>
                
                <!-- Lista de Animais -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="p-3 border-b border-gray-200">
                        <h3 class="text-sm font-semibold text-gray-900">Animais do Rebanho</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600">Número</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600">Nome</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600">Raça</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600">Status</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600">Idade</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="animalTableBody" class="divide-y divide-gray-200">
                                <tr>
                                    <td colspan="6" class="px-3 py-8 text-center text-gray-500">
                                        <div class="flex flex-col items-center space-y-2">
                                            <svg class="w-12 h-12 text-gray-300 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                            </svg>
                                            <p class="text-sm">Carregando animais...</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
</div>

<!-- OVERLAY GESTÃO SANITÁRIA -->
<div id="healthOverlay" class="fixed inset-0 bg-black bg-opacity-60 z-[99999] hidden transition-all duration-300 backdrop-blur-sm">
    <div class="w-full h-full bg-gradient-to-br from-gray-50 to-gray-100 flex flex-col transform transition-transform duration-300">
        <!-- Header -->
        <div class="bg-white border-b border-gray-200">
            <div class="flex items-center justify-between p-3 sm:p-4">
                <button onclick="closeHealthOverlay()" class="group p-1.5 sm:p-2 hover:bg-gray-100 rounded-lg transition-all duration-200 flex items-center space-x-1.5 text-gray-600 hover:text-gray-900">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    <span class="hidden sm:inline text-xs font-medium">Voltar</span>
                </button>
                <h2 class="text-base sm:text-lg font-bold text-gray-900">Gestão Sanitária</h2>
                <button onclick="closeHealthOverlay()" class="p-1.5 sm:p-2 hover:bg-gray-100 rounded-lg transition-all duration-200 text-gray-600 hover:text-gray-900">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
        
        <!-- Conteúdo -->
        <div class="flex-1 overflow-y-auto p-4 sm:p-6">
            <div class="max-w-6xl mx-auto space-y-4">
                
                <!-- Cards de Estatísticas -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                    <!-- Total de Registros -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-3">
                        <div class="flex items-center space-x-2">
                            <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs text-gray-500">Registros</p>
                                <p class="text-lg font-bold text-gray-900" id="healthTotalCount">-</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Medicamentos -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-3">
                        <div class="flex items-center space-x-2">
                            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs text-gray-500">Medicamentos</p>
                                <p class="text-lg font-bold text-blue-600" id="healthMedsCount">-</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Vacinações -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-3">
                        <div class="flex items-center space-x-2">
                            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs text-gray-500">Vacinações</p>
                                <p class="text-lg font-bold text-green-600" id="healthVacCount">-</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Alertas -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-3">
                        <div class="flex items-center space-x-2">
                            <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs text-gray-500">Alertas</p>
                                <p class="text-lg font-bold text-amber-600" id="healthAlertsCount">-</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Tabs de Navegação -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="border-b border-gray-200">
                        <nav class="flex space-x-4 px-4" id="healthTabs">
                            <button onclick="switchHealthTab('records')" class="health-tab py-3 px-2 text-sm font-medium border-b-2 border-red-600 text-red-600" data-tab="records">
                                Registros de Saúde
                            </button>
                            <button onclick="switchHealthTab('medications')" class="health-tab py-3 px-2 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700" data-tab="medications">
                                Medicamentos
                            </button>
                            <button onclick="switchHealthTab('alerts')" class="health-tab py-3 px-2 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700" data-tab="alerts">
                                Alertas
                            </button>
                        </nav>
                    </div>
                    
                    <!-- Tab Content: Registros de Saúde -->
                    <div id="healthTabRecords" class="health-tab-content p-4">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-sm font-semibold text-gray-900">Registros de Saúde</h3>
                            <button onclick="showAddHealthRecordForm()" class="flex items-center space-x-1 px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-xs font-medium rounded-lg">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                <span>Novo Registro</span>
                            </button>
                        </div>
                        
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600">Data</th>
                                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600">Animal</th>
                                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600">Tipo</th>
                                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600">Descrição</th>
                                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600">Ações</th>
                                    </tr>
                                </thead>
                                <tbody id="healthRecordsTableBody" class="divide-y divide-gray-200">
                                    <tr>
                                        <td colspan="5" class="px-3 py-8 text-center text-gray-500">
                                            <div class="flex flex-col items-center space-y-2">
                                                <svg class="w-12 h-12 text-gray-300 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                                </svg>
                                                <p class="text-sm">Carregando registros...</p>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Tab Content: Medicamentos (hidden initially) -->
                    <div id="healthTabMedications" class="health-tab-content p-4 hidden">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-sm font-semibold text-gray-900">Estoque de Medicamentos</h3>
                            <button onclick="showAddMedicationForm()" class="flex items-center space-x-1 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-lg">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                <span>Novo Medicamento</span>
                            </button>
                        </div>
                        
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600">Nome</th>
                                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600">Tipo</th>
                                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600">Estoque</th>
                                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600">Validade</th>
                                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600">Ações</th>
                                    </tr>
                                </thead>
                                <tbody id="medicationsTableBody" class="divide-y divide-gray-200">
                                    <tr>
                                        <td colspan="5" class="px-3 py-8 text-center text-gray-500">
                                            <p class="text-sm">Carregando medicamentos...</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Tab Content: Alertas (hidden initially) -->
                    <div id="healthTabAlerts" class="health-tab-content p-4 hidden">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-sm font-semibold text-gray-900">Alertas de Saúde</h3>
                        </div>
                        
                        <div id="healthAlertsContainer" class="space-y-2">
                            <div class="text-center text-gray-500 py-8">
                                <p class="text-sm">Carregando alertas...</p>
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
</div>

<!-- OVERLAY REPRODUÇÃO -->
<div id="reproductionOverlay" class="fixed inset-0 bg-black bg-opacity-60 z-[99999] hidden transition-all duration-300 backdrop-blur-sm">
    <div class="w-full h-full bg-gradient-to-br from-gray-50 to-gray-100 flex flex-col transform transition-transform duration-300">
        <!-- Header -->
        <div class="bg-white border-b border-gray-200">
            <div class="flex items-center justify-between p-3 sm:p-4">
                <button onclick="closeReproductionOverlay()" class="group p-1.5 sm:p-2 hover:bg-gray-100 rounded-lg transition-all duration-200 flex items-center space-x-1.5 text-gray-600 hover:text-gray-900">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    <span class="hidden sm:inline text-xs font-medium">Voltar</span>
                </button>
                <h2 class="text-base sm:text-lg font-bold text-gray-900">Sistema de Reprodução</h2>
                <button onclick="closeReproductionOverlay()" class="p-1.5 sm:p-2 hover:bg-gray-100 rounded-lg transition-all duration-200 text-gray-600 hover:text-gray-900">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
        
        <!-- Conteúdo -->
        <div class="flex-1 overflow-y-auto p-4 sm:p-6">
            <div class="max-w-6xl mx-auto space-y-4">
                
                <!-- Cards de Estatísticas -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                    <!-- Prenhes Ativas -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-3">
                        <div class="flex items-center space-x-2">
                            <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs text-gray-500">Prenhes</p>
                                <p class="text-lg font-bold text-purple-600" id="reproPregnantCount">-</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Inseminações -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-3">
                        <div class="flex items-center space-x-2">
                            <div class="w-10 h-10 bg-pink-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs text-gray-500">Inseminações</p>
                                <p class="text-lg font-bold text-pink-600" id="reproInsemCount">-</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Partos Próximos -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-3">
                        <div class="flex items-center space-x-2">
                            <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs text-gray-500">Partos 30d</p>
                                <p class="text-lg font-bold text-amber-600" id="reproBirthsCount">-</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Nascimentos -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-3">
                        <div class="flex items-center space-x-2">
                            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs text-gray-500">Nascimentos</p>
                                <p class="text-lg font-bold text-green-600" id="reproBirthsTotalCount">-</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Tabs de Navegação -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="border-b border-gray-200">
                        <nav class="flex space-x-4 px-4" id="reproTabs">
                            <button onclick="switchReproTab('pregnancies')" class="repro-tab py-3 px-2 text-sm font-medium border-b-2 border-purple-600 text-purple-600" data-tab="pregnancies">
                                Prenhes Ativas
                            </button>
                            <button onclick="switchReproTab('inseminations')" class="repro-tab py-3 px-2 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700" data-tab="inseminations">
                                Inseminações
                            </button>
                            <button onclick="switchReproTab('births')" class="repro-tab py-3 px-2 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700" data-tab="births">
                                Nascimentos
                            </button>
                        </nav>
                    </div>
                    
                    <!-- Tab: Prenhes Ativas -->
                    <div id="reproTabPregnancies" class="repro-tab-content p-4">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-sm font-semibold text-gray-900">Controle de Prenhes</h3>
                            <button onclick="showAddPregnancyForm()" class="flex items-center space-x-1 px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white text-xs font-medium rounded-lg">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                <span>Nova Prenhez</span>
                            </button>
                        </div>
                        
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600">Animal</th>
                                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600">Data IA</th>
                                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600">DPP</th>
                                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600">Dias</th>
                                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600">Fase</th>
                                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600">Ações</th>
                                    </tr>
                                </thead>
                                <tbody id="pregnanciesTableBody" class="divide-y divide-gray-200">
                                    <tr>
                                        <td colspan="6" class="px-3 py-8 text-center text-gray-500">
                                            <div class="flex flex-col items-center space-y-2">
                                                <svg class="w-12 h-12 text-gray-300 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                                </svg>
                                                <p class="text-sm">Carregando prenhes...</p>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Tab: Inseminações -->
                    <div id="reproTabInseminations" class="repro-tab-content p-4 hidden">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-sm font-semibold text-gray-900">Histórico de Inseminações</h3>
                            <button onclick="showAddInseminationForm()" class="flex items-center space-x-1 px-3 py-1.5 bg-pink-600 hover:bg-pink-700 text-white text-xs font-medium rounded-lg">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                <span>Nova Inseminação</span>
                            </button>
                        </div>
                        
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600">Data</th>
                                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600">Animal</th>
                                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600">Touro</th>
                                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600">Tipo</th>
                                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600">Ações</th>
                                    </tr>
                                </thead>
                                <tbody id="inseminationsTableBody" class="divide-y divide-gray-200">
                                    <tr>
                                        <td colspan="5" class="px-3 py-8 text-center text-gray-500">
                                            <p class="text-sm">Carregando inseminações...</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Tab: Nascimentos -->
                    <div id="reproTabBirths" class="repro-tab-content p-4 hidden">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-sm font-semibold text-gray-900">Registro de Nascimentos</h3>
                            <button onclick="showAddBirthForm()" class="flex items-center space-x-1 px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white text-xs font-medium rounded-lg">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                <span>Novo Nascimento</span>
                            </button>
                        </div>
                        
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600">Data</th>
                                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600">Mãe</th>
                                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600">Bezerro</th>
                                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600">Sexo</th>
                                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600">Peso</th>
                                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600">Ações</th>
                                    </tr>
                                </thead>
                                <tbody id="birthsTableBody" class="divide-y divide-gray-200">
                                    <tr>
                                        <td colspan="6" class="px-3 py-8 text-center text-gray-500">
                                            <p class="text-sm">Carregando nascimentos...</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
</div>

<!-- OVERLAY DASHBOARD ANALÍTICO -->
<div id="analyticsOverlay" class="fixed inset-0 bg-black bg-opacity-60 z-[99999] hidden transition-all duration-300 backdrop-blur-sm">
    <div class="w-full h-full bg-gradient-to-br from-gray-50 to-gray-100 flex flex-col transform transition-transform duration-300">
        <!-- Header -->
        <div class="bg-white border-b border-gray-200">
            <div class="flex items-center justify-between p-3 sm:p-4">
                <button onclick="closeAnalyticsOverlay()" class="group p-1.5 sm:p-2 hover:bg-gray-100 rounded-lg transition-all duration-200 flex items-center space-x-1.5 text-gray-600 hover:text-gray-900">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    <span class="hidden sm:inline text-xs font-medium">Voltar</span>
                </button>
                <h2 class="text-base sm:text-lg font-bold text-gray-900">Dashboard Analítico</h2>
                <button onclick="closeAnalyticsOverlay()" class="p-1.5 sm:p-2 hover:bg-gray-100 rounded-lg transition-all duration-200 text-gray-600 hover:text-gray-900">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
        
        <!-- Conteúdo -->
        <div class="flex-1 overflow-y-auto p-4 sm:p-6">
            <div class="max-w-7xl mx-auto space-y-4">
                
                <!-- Cards de Visão Geral -->
                <div class="grid grid-cols-2 lg:grid-cols-5 gap-3">
                    <!-- Total de Animais -->
                    <div class="bg-gradient-to-br from-emerald-50 to-teal-50 rounded-lg shadow-sm border border-emerald-200 p-3">
                        <div class="flex items-center justify-between mb-2">
                            <div class="w-10 h-10 bg-emerald-600 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z"/>
                                </svg>
                            </div>
                        </div>
                        <p class="text-2xl font-bold text-emerald-900" id="dashTotalAnimals">-</p>
                        <p class="text-xs text-emerald-700 font-medium">Total de Animais</p>
                    </div>
                    
                    <!-- Produção de Leite -->
                    <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-lg shadow-sm border border-blue-200 p-3">
                        <div class="flex items-center justify-between mb-2">
                            <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                </svg>
                            </div>
                        </div>
                        <p class="text-2xl font-bold text-blue-900" id="dashTotalMilk">-</p>
                        <p class="text-xs text-blue-700 font-medium">Litros Hoje</p>
                    </div>
                    
                    <!-- Prenhes -->
                    <div class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-lg shadow-sm border border-purple-200 p-3">
                        <div class="flex items-center justify-between mb-2">
                            <div class="w-10 h-10 bg-purple-600 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                </svg>
                            </div>
                        </div>
                        <p class="text-2xl font-bold text-purple-900" id="dashPregnant">-</p>
                        <p class="text-xs text-purple-700 font-medium">Prenhes Ativas</p>
                    </div>
                    
                    <!-- Saúde -->
                    <div class="bg-gradient-to-br from-red-50 to-pink-50 rounded-lg shadow-sm border border-red-200 p-3">
                        <div class="flex items-center justify-between mb-2">
                            <div class="w-10 h-10 bg-red-600 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                </svg>
                            </div>
                        </div>
                        <p class="text-2xl font-bold text-red-900" id="dashHealthy">-</p>
                        <p class="text-xs text-red-700 font-medium">Animais Saudáveis</p>
                    </div>
                    
                    <!-- Alertas -->
                    <div class="bg-gradient-to-br from-amber-50 to-orange-50 rounded-lg shadow-sm border border-amber-200 p-3">
                        <div class="flex items-center justify-between mb-2">
                            <div class="w-10 h-10 bg-amber-600 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                            </div>
                        </div>
                        <p class="text-2xl font-bold text-amber-900" id="dashAlerts">-</p>
                        <p class="text-xs text-amber-700 font-medium">Alertas Pendentes</p>
                    </div>
                </div>
                
                <!-- Gráficos e Análises -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    
                    <!-- Distribuição por Status -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                        <h3 class="text-sm font-bold text-gray-900 mb-3 flex items-center">
                            <svg class="w-4 h-4 mr-1.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/>
                            </svg>
                            Distribuição por Status
                        </h3>
                        <div id="statusDistribution" class="space-y-2">
                            <p class="text-xs text-gray-500 text-center py-4">Carregando...</p>
                        </div>
                    </div>
                    
                    <!-- Produção dos Últimos 7 Dias -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                        <h3 class="text-sm font-bold text-gray-900 mb-3 flex items-center">
                            <svg class="w-4 h-4 mr-1.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
                            </svg>
                            Produção de Leite (7 dias)
                        </h3>
                        <div id="productionChart" class="space-y-2">
                            <p class="text-xs text-gray-500 text-center py-4">Carregando...</p>
                        </div>
                    </div>
                    
                    <!-- Saúde do Rebanho -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                        <h3 class="text-sm font-bold text-gray-900 mb-3 flex items-center">
                            <svg class="w-4 h-4 mr-1.5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                            Status de Saúde
                        </h3>
                        <div id="healthDistribution" class="space-y-2">
                            <p class="text-xs text-gray-500 text-center py-4">Carregando...</p>
                        </div>
                    </div>
                    
                    <!-- Controle Reprodutivo -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                        <h3 class="text-sm font-bold text-gray-900 mb-3 flex items-center">
                            <svg class="w-4 h-4 mr-1.5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                            Status Reprodutivo
                        </h3>
                        <div id="reproductiveDistribution" class="space-y-2">
                            <p class="text-xs text-gray-500 text-center py-4">Carregando...</p>
                        </div>
                    </div>
                    
                </div>
                
                <!-- Resumo de Atividades Recentes -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="p-3 border-b border-gray-200">
                        <h3 class="text-sm font-semibold text-gray-900">Atividades Recentes</h3>
                    </div>
                    <div id="recentActivities" class="p-4">
                        <p class="text-xs text-gray-500 text-center py-4">Carregando atividades...</p>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
</div>

<!-- MODAL DE CONFIRMAÇÃO DE LOGOUT -->
<div id="logoutConfirmModal" class="fixed inset-0 bg-black bg-opacity-60 z-[999999] hidden flex items-center justify-center p-4" onclick="if(event.target === this) closeLogoutConfirmation()">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full overflow-hidden transform transition-all">
        <!-- Header -->
        <div class="bg-gradient-to-r from-red-600 to-red-700 px-6 py-4 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-white">Confirmar Saída</h3>
            </div>
            <button onclick="closeLogoutConfirmation()" class="text-white hover:bg-white hover:bg-opacity-20 p-1.5 rounded-lg transition-all">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        
        <!-- Conteúdo -->
        <div class="p-6">
            <div class="flex items-start space-x-4 mb-6">
                <div class="flex-shrink-0 w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h4 class="text-lg font-semibold text-gray-900 mb-2">Deseja realmente sair?</h4>
                    <p class="text-sm text-gray-600 leading-relaxed">Você será desconectado do sistema e precisará fazer login novamente para acessar.</p>
                </div>
            </div>
            
            <!-- Info adicional -->
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                <div class="flex items-start space-x-3">
                    <svg class="w-5 h-5 text-red-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-sm text-red-800">Certifique-se de que salvou todas as alterações antes de sair.</p>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="bg-gray-50 px-6 py-4 flex items-center justify-end space-x-3">
            <button onclick="closeLogoutConfirmation()" class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-all">
                Cancelar
            </button>
            <button onclick="confirmLogout()" class="px-5 py-2.5 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-all flex items-center space-x-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                <span>Sim, Sair</span>
            </button>
        </div>
    </div>
</div>

<!-- OVERLAY CONTROLE DE NOVILHAS -->
<div id="heiferOverlay" class="fixed inset-0 bg-black bg-opacity-60 z-[99999] hidden transition-all duration-300 backdrop-blur-sm">
    <div class="w-full h-full bg-gradient-to-br from-gray-50 to-gray-100 flex flex-col transform transition-transform duration-300">
        <!-- Header -->
        <div class="bg-white border-b border-gray-200">
            <div class="flex items-center justify-between p-3 sm:p-4">
                <button onclick="closeHeiferOverlay()" class="group p-1.5 sm:p-2 hover:bg-gray-100 rounded-lg transition-all duration-200 flex items-center space-x-1.5 text-gray-600 hover:text-gray-900">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    <span class="hidden sm:inline text-xs font-medium">Voltar</span>
                </button>
                <h2 class="text-base sm:text-lg font-bold text-gray-900">Controle de Novilhas</h2>
                <button onclick="closeHeiferOverlay()" class="p-1.5 sm:p-2 hover:bg-gray-100 rounded-lg transition-all duration-200 text-gray-600 hover:text-gray-900">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
        
        <!-- Conteúdo -->
        <div class="flex-1 overflow-y-auto p-4 sm:p-6">
            <div class="max-w-6xl mx-auto space-y-4">
                
                <!-- Cards de Estatísticas -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                    <!-- Total de Novilhas -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-3">
                        <div class="flex items-center space-x-2">
                            <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs text-gray-500">Total</p>
                                <p class="text-lg font-bold text-orange-600" id="heiferTotalCount">-</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Custo Médio -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-3">
                        <div class="flex items-center space-x-2">
                            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs text-gray-500">Custo Médio</p>
                                <p class="text-lg font-bold text-green-600" id="heiferAvgCost">-</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Idade Média -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-3">
                        <div class="flex items-center space-x-2">
                            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs text-gray-500">Idade Média</p>
                                <p class="text-lg font-bold text-blue-600" id="heiferAvgAge">-</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Custo Total -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-3">
                        <div class="flex items-center space-x-2">
                            <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs text-gray-500">Custo Total</p>
                                <p class="text-lg font-bold text-red-600" id="heiferTotalCost">-</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Tabela de Novilhas -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="p-3 border-b border-gray-200 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-900">Novilhas e Custos de Criação</h3>
                        <button onclick="showAddHeiferCostForm()" class="flex items-center space-x-1 px-3 py-1.5 bg-orange-600 hover:bg-orange-700 text-white text-xs font-medium rounded-lg">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            <span>Adicionar Custo</span>
                        </button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600">Novilha</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600">Idade</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600">Raça</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600">Custo Total</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="heiferTableBody" class="divide-y divide-gray-200">
                                <tr>
                                    <td colspan="5" class="px-3 py-8 text-center text-gray-500">
                                        <div class="flex flex-col items-center space-y-2">
                                            <svg class="w-12 h-12 text-gray-300 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                            </svg>
                                            <p class="text-sm">Carregando novilhas...</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
</div>

</body>
</html>
