<?php
/**
 * SafeNode - Anomaly Detector
 */

session_start();
require_once __DIR__ . '/includes/SecurityHelpers.php';
SecurityHeaders::apply();

if (!isset($_SESSION['safenode_logged_in']) || $_SESSION['safenode_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/AnomalyDetector.php';

$pageTitle = 'Anomaly Detector';
$currentSiteId = $_SESSION['view_site_id'] ?? 0;
$userId = $_SESSION['safenode_user_id'] ?? null;

$db = getSafeNodeDatabase();
$detector = new AnomalyDetector($db);

// Parâmetros
$searchIP = $_GET['ip'] ?? '';
$timeWindow = isset($_GET['window']) ? (int)$_GET['window'] : 3600;

// Detectar anomalias globais
$globalAnomalies = $detector->detectGlobalAnomalies($currentSiteId, $timeWindow, 50);
$recentAnomalies = $detector->getRecentAnomalies($currentSiteId, 24, 20);
$stats = $detector->getAnomalyStats($currentSiteId, 7);
$anomalyTypes = $detector->getAnomalyTypes($currentSiteId, 7);

// Se IP específico foi pesquisado
$ipAnomalies = null;
$patternAnomalies = [];
if (!empty($searchIP) && filter_var($searchIP, FILTER_VALIDATE_IP)) {
    $ipAnomalies = $detector->detectAnomalies($searchIP, $timeWindow);
    $patternAnomalies = $detector->detectPatternAnomalies($searchIP, $timeWindow);
}

?>
<!DOCTYPE html>
<html lang="pt-BR" class="dark h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> | SafeNode</title>
    <link rel="icon" type="image/png" href="assets/img/logos (6).png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <?php require_once __DIR__ . '/includes/skeleton-loader.php'; echo skeletonLoaderCSS(); ?>
    
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        mono: ['JetBrains Mono', 'monospace'],
                    },
                    colors: {
                        dark: {
                            950: '#030303',
                            900: '#050505',
                            850: '#080808',
                            800: '#0a0a0a',
                            700: '#0f0f0f',
                            600: '#141414',
                            500: '#1a1a1a',
                            400: '#222222',
                        }
                    }
                }
            }
        }
    </script>
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        :root {
            --bg-primary: #030303;
            --bg-secondary: #080808;
            --bg-tertiary: #0f0f0f;
            --bg-card: #0a0a0a;
            --bg-hover: #111111;
            --border-subtle: rgba(255,255,255,0.04);
            --border-light: rgba(255,255,255,0.08);
            --text-primary: #ffffff;
            --text-secondary: #a1a1aa;
            --text-muted: #52525b;
        }
        
        body {
            background-color: var(--bg-primary);
            color: var(--text-secondary);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            font-size: 0.92em;
        }
        
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { 
            background: rgba(255,255,255,0.1); 
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.15); }
        
        .sidebar {
            background: linear-gradient(180deg, var(--bg-secondary) 0%, var(--bg-primary) 100%);
            border-right: 1px solid var(--border-subtle);
            position: relative;
        }
        
        .nav-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 12px 16px;
            border-radius: 12px;
            color: var(--text-muted);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            text-decoration: none;
        }
        
        .nav-item:hover {
            background: rgba(255,255,255,0.05);
            color: var(--text-primary);
        }
        
        .nav-item.active {
            background: rgba(255,255,255,0.1);
            color: var(--text-primary);
        }
        
        .upgrade-card {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 16px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #ffffff 0%, #e5e5e5 100%);
            color: #000;
            font-weight: 600;
            padding: 12px 24px;
            border-radius: 12px;
            border: none;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        .btn-primary::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.2) 0%, transparent 50%);
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px -10px rgba(255, 255, 255, 0.5);
        }
        
        .btn-primary:hover::before {
            opacity: 1;
        }
    </style>
</head>
<body class="h-full" x-data="{ sidebarOpen: false, loading: true }" x-init="setTimeout(() => loading = false, 500)">
    <div class="flex h-full">
        <?php
    // Código da Sidebar (copiado de includes/sidebar.php)
    // Iniciar sessão se necessário
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Carregar Router se estiver logado
    $useProtectedUrls = false;
    if (isset($_SESSION['safenode_logged_in']) && $_SESSION['safenode_logged_in'] === true) {
        require_once __DIR__ . '/includes/Router.php';
        SafeNodeRouter::init();
        $useProtectedUrls = true;
    }

    // Função helper para gerar URLs (sem token)
    if (!function_exists('getSafeNodeUrl')) {
        function getSafeNodeUrl($route, $siteId = null) {
            $pagePath = strpos($route, '.php') !== false ? $route : $route . '.php';
            return $pagePath;
        }
    }

    // Detectar página atual
    $currentPage = basename($_SERVER['PHP_SELF'], '.php');
    if (isset($_GET['route'])) {
        $currentPage = 'dashboard'; // Ajustar conforme necessário
    }

    // Buscar sequência de proteção
    $protectionStreak = null;
    if (isset($_SESSION['safenode_logged_in']) && $_SESSION['safenode_logged_in'] === true) {
        $userId = $_SESSION['safenode_user_id'] ?? null;
        $siteId = $_SESSION['view_site_id'] ?? 0;
        
        if ($userId) {
            require_once __DIR__ . '/includes/ProtectionStreak.php';
            $streakManager = new ProtectionStreak();
            $protectionStreak = $streakManager->getStreak($userId, $siteId);
        }
    }
    ?>
    <style>
        .nav-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 12px 16px;
            border-radius: 12px;
            color: #52525b;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            position: relative;
            overflow: hidden;
            text-decoration: none;
        }
        
        .nav-item::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, rgba(255, 255, 255, 0.2) 0%, transparent 100%);
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .nav-item:hover {
            color: #ffffff;
        }
        
        .nav-item:hover::before {
            opacity: 0.5;
        }
        
        .nav-item.active {
            color: #ffffff;
            background: linear-gradient(90deg, rgba(255, 255, 255, 0.1) 0%, transparent 100%);
        }
        
        .nav-item.active::before {
            opacity: 1;
        }
        
        .nav-item.active::after {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 3px;
            height: 24px;
            background: #ffffff;
            border-radius: 0 4px 4px 0;
            box-shadow: 0 0 20px rgba(255, 255, 255, 0.2);
        }
        
        .sidebar {
            background: linear-gradient(180deg, #080808 0%, #030303 100%);
            border-right: 1px solid rgba(255,255,255,0.04);
            position: relative;
        }
        
        /* Garantir que sidebar mobile sobreponha completamente sem comprimir interface */
        @media (max-width: 1023px) {
            aside[x-show*="sidebarOpen"] {
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                right: auto !important;
                bottom: 0 !important;
                width: 18rem !important;
                max-width: 18rem !important;
                min-width: 18rem !important;
                z-index: 70 !important;
                transform: translateX(-100%) !important;
                will-change: transform;
                margin: 0 !important;
                padding: 0 !important;
            }
            
            aside[x-show*="sidebarOpen"][x-show="true"],
            aside[x-show*="sidebarOpen"]:not([style*="translateX(-100%)"]) {
                transform: translateX(0) !important;
            }
            
            /* Garantir que o overlay também sobreponha */
            div[x-show*="sidebarOpen"].fixed {
                position: fixed !important;
                z-index: 60 !important;
                pointer-events: auto !important;
            }
            
            /* Garantir que o conteúdo principal não seja afetado */
            main.flex-1,
            main[class*="flex-1"] {
                width: 100% !important;
                max-width: 100% !important;
                margin-left: 0 !important;
                padding-left: 0 !important;
                transition: none !important;
            }
            
            /* Container principal não deve ser afetado */
            div.flex.h-full > main {
                width: 100% !important;
                flex: 1 1 100% !important;
                min-width: 0 !important;
            }
        }
        
        .sidebar::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 1px;
            height: 100%;
            background: linear-gradient(180deg, transparent 0%, rgba(255, 255, 255, 0.2) 50%, transparent 100%);
            opacity: 0.5;
        }
        
        .upgrade-card {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 16px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #ffffff 0%, #e5e5e5 100%);
            color: #000;
            font-weight: 600;
            padding: 12px 24px;
            border-radius: 12px;
            border: none;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            width: 100%;
        }
        
        .btn-primary::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.2) 0%, transparent 50%);
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(255,255,255,0.2);
        }
        
        .btn-primary:hover::before {
            opacity: 1;
        }
        
        /* CSS para x-cloak - esconder elementos antes do Alpine.js carregar */
        [x-cloak] { 
            display: none !important; 
        }
        
        /* Garantir que sidebar mobile apareça quando sidebarOpen for true */
        /* Alpine.js remove x-cloak quando x-show é true, mas garantimos com CSS também */
        aside[x-show*="sidebarOpen"]:not([x-cloak]),
        aside[x-show*="sidebarOpen"][style*="display: flex"] {
            display: flex !important;
        }
        
        /* Override x-cloak quando sidebar está aberta via JavaScript */
        aside.mobile-sidebar-open {
            display: flex !important;
            x-cloak: none;
        }
    </style>
    <!-- Sidebar Component -->
    <aside id="safenode-sidebar" x-data="{ sidebarCollapsed: false }" 
           :class="sidebarCollapsed ? 'w-20' : 'w-72'" 
           class="sidebar h-full flex-shrink-0 flex flex-col hidden lg:flex transition-all duration-300 ease-in-out overflow-hidden">
        <!-- Logo -->
        <div class="p-4 border-b border-white/5 flex-shrink-0 relative">
            <div class="flex items-center" :class="sidebarCollapsed ? 'justify-center flex-col gap-3' : 'justify-between'">
                <div class="flex items-center gap-3" :class="sidebarCollapsed ? 'justify-center' : ''">
                    <div class="relative">
                        <img src="assets/img/logos (6).png" alt="SafeNode Logo" class="w-8 h-8 object-contain flex-shrink-0">
                        <?php if ($protectionStreak && $protectionStreak['enabled'] && $protectionStreak['is_active']): ?>
                        <!-- Badge de Sequência (Foguinho) -->
                        <div class="absolute -top-1 -right-1 bg-gradient-to-br from-orange-500 to-red-600 rounded-full p-1 shadow-lg border-2 border-dark-900" 
                             x-data="{ showTooltip: false }"
                             @mouseenter="showTooltip = true"
                             @mouseleave="showTooltip = false">
                            <i data-lucide="flame" class="w-3 h-3 text-white"></i>
                            <!-- Tooltip -->
                            <div x-show="showTooltip" 
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 scale-95"
                                 x-transition:enter-end="opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-150"
                                 x-transition:leave-start="opacity-100 scale-100"
                                 x-transition:leave-end="opacity-0 scale-95"
                                 class="absolute left-1/2 -translate-x-1/2 bottom-full mb-2 px-3 py-2 bg-dark-800 border border-white/10 rounded-lg shadow-xl whitespace-nowrap z-50"
                                 style="display: none;">
                                <div class="text-xs font-semibold text-white mb-1">Sequência de Proteção</div>
                                <div class="text-sm font-bold text-orange-400"><?php echo $protectionStreak['current_streak']; ?> dias</div>
                                <?php if ($protectionStreak['longest_streak'] > $protectionStreak['current_streak']): ?>
                                <div class="text-xs text-zinc-400 mt-1">Recorde: <?php echo $protectionStreak['longest_streak']; ?> dias</div>
                                <?php endif; ?>
                                <div class="absolute left-1/2 -translate-x-1/2 top-full w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-white/10"></div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div x-show="!sidebarCollapsed" 
                         x-transition:enter="transition ease-out duration-200" 
                         x-transition:enter-start="opacity-0 -translate-x-2" 
                         x-transition:enter-end="opacity-100 translate-x-0" 
                         x-transition:leave="transition ease-in duration-150" 
                         x-transition:leave-start="opacity-100 translate-x-0" 
                         x-transition:leave-end="opacity-0 -translate-x-2" 
                         class="overflow-hidden whitespace-nowrap">
                        <h1 class="font-bold text-white text-xl tracking-tight">SafeNode</h1>
                        <p class="text-xs text-zinc-500 font-medium">Security Platform</p>
                    </div>
                </div>
                <button @click="sidebarCollapsed = !sidebarCollapsed; setTimeout(() => lucide.createIcons(), 50)" 
                        class="text-zinc-600 hover:text-zinc-400 transition-colors flex-shrink-0" 
                        :class="sidebarCollapsed ? 'mt-2' : ''">
                    <i :data-lucide="sidebarCollapsed ? 'chevrons-right' : 'chevrons-left'" class="w-5 h-5"></i>
                </button>
            </div>
        </div>
        
        <!-- Navigation -->
        <nav class="flex-1 p-4 space-y-1 overflow-y-auto overflow-x-hidden">
            <p x-show="!sidebarCollapsed" 
               x-transition:enter="transition ease-out duration-200" 
               x-transition:enter-start="opacity-0" 
               x-transition:enter-end="opacity-100" 
               x-transition:leave="transition ease-in duration-150" 
               x-transition:leave-start="opacity-100" 
               x-transition:leave-end="opacity-0" 
               class="text-xs font-semibold text-zinc-600 uppercase tracking-wider mb-3 px-3 whitespace-nowrap">Principal</p>
            
            <a href="<?php echo getSafeNodeUrl('dashboard'); ?>" 
               class="nav-item <?php echo $currentPage == 'dashboard' ? 'active' : ''; ?>" 
               :class="sidebarCollapsed ? 'justify-center px-2' : ''" 
               :title="sidebarCollapsed ? 'Dashboard' : ''">
                <i data-lucide="layout-dashboard" class="w-5 h-5 flex-shrink-0"></i>
                <span x-show="!sidebarCollapsed" 
                      x-transition:enter="transition ease-out duration-200" 
                      x-transition:enter-start="opacity-0 -translate-x-2" 
                      x-transition:enter-end="opacity-100 translate-x-0" 
                      x-transition:leave="transition ease-in duration-150" 
                      x-transition:leave-start="opacity-100 translate-x-0" 
                      x-transition:leave-end="opacity-0 -translate-x-2" 
                      class="font-medium whitespace-nowrap">Dashboard</span>
            </a>
            <a href="<?php echo getSafeNodeUrl('sites'); ?>" 
               class="nav-item <?php echo $currentPage == 'sites' ? 'active' : ''; ?>" 
               :class="sidebarCollapsed ? 'justify-center px-2' : ''" 
               :title="sidebarCollapsed ? 'Gerenciar Sites' : ''">
                <i data-lucide="globe" class="w-5 h-5 flex-shrink-0"></i>
                <span x-show="!sidebarCollapsed" 
                      x-transition:enter="transition ease-out duration-200" 
                      x-transition:enter-start="opacity-0 -translate-x-2" 
                      x-transition:enter-end="opacity-100 translate-x-0" 
                      x-transition:leave="transition ease-in duration-150" 
                      x-transition:leave-start="opacity-100 translate-x-0" 
                      x-transition:leave-end="opacity-0 -translate-x-2" 
                      class="font-medium whitespace-nowrap">Gerenciar Sites</span>
            </a>
            <a href="<?php echo getSafeNodeUrl('mail'); ?>" 
               class="nav-item <?php echo $currentPage == 'mail' ? 'active' : ''; ?>" 
               :class="sidebarCollapsed ? 'justify-center px-2' : ''" 
               :title="sidebarCollapsed ? 'Mail' : ''">
                <i data-lucide="mail" class="w-5 h-5 flex-shrink-0"></i>
                <span x-show="!sidebarCollapsed" 
                      x-transition:enter="transition ease-out duration-200" 
                      x-transition:enter-start="opacity-0 -translate-x-2" 
                      x-transition:enter-end="opacity-100 translate-x-0" 
                      x-transition:leave="transition ease-in duration-150" 
                      x-transition:leave-start="opacity-100 translate-x-0" 
                      x-transition:leave-end="opacity-0 -translate-x-2" 
                      class="font-medium whitespace-nowrap">Mail</span>
            </a>
            
            <div class="pt-4 mt-4 border-t border-white/5">
                <p x-show="!sidebarCollapsed" 
                   x-transition:enter="transition ease-out duration-200" 
                   x-transition:enter-start="opacity-0" 
                   x-transition:enter-end="opacity-100" 
                   x-transition:leave="transition ease-in duration-150" 
                   x-transition:leave-start="opacity-100" 
                   x-transition:leave-end="opacity-0" 
                   class="text-xs font-semibold text-zinc-600 uppercase tracking-wider mb-3 px-3 whitespace-nowrap">Análises</p>
                <a href="<?php echo getSafeNodeUrl('logs'); ?>" 
                   class="nav-item <?php echo $currentPage == 'logs' ? 'active' : ''; ?>" 
                   :class="sidebarCollapsed ? 'justify-center px-2' : ''" 
                   :title="sidebarCollapsed ? 'Explorar Logs' : ''">
                    <i data-lucide="file-text" class="w-5 h-5 flex-shrink-0"></i>
                    <span x-show="!sidebarCollapsed" 
                          x-transition:enter="transition ease-out duration-200" 
                          x-transition:enter-start="opacity-0 -translate-x-2" 
                          x-transition:enter-end="opacity-100 translate-x-0" 
                          x-transition:leave="transition ease-in duration-150" 
                          x-transition:leave-start="opacity-100 translate-x-0" 
                          x-transition:leave-end="opacity-0 -translate-x-2" 
                          class="font-medium whitespace-nowrap">Explorar Logs</span>
                </a>
                <a href="<?php echo getSafeNodeUrl('behavior-analysis'); ?>" 
                   class="nav-item <?php echo $currentPage == 'behavior-analysis' ? 'active' : ''; ?>" 
                   :class="sidebarCollapsed ? 'justify-center px-2' : ''" 
                   :title="sidebarCollapsed ? 'Comportamental' : ''">
                    <i data-lucide="brain" class="w-5 h-5 flex-shrink-0"></i>
                    <span x-show="!sidebarCollapsed" 
                          x-transition:enter="transition ease-out duration-200" 
                          x-transition:enter-start="opacity-0 -translate-x-2" 
                          x-transition:enter-end="opacity-100 translate-x-0" 
                          x-transition:leave="transition ease-in duration-150" 
                          x-transition:leave-start="opacity-100 translate-x-0" 
                          x-transition:leave-end="opacity-0 -translate-x-2" 
                          class="font-medium whitespace-nowrap">Comportamental</span>
                </a>
                <a href="<?php echo getSafeNodeUrl('security-analytics'); ?>" 
                   class="nav-item <?php echo $currentPage == 'security-analytics' ? 'active' : ''; ?>" 
                   :class="sidebarCollapsed ? 'justify-center px-2' : ''" 
                   :title="sidebarCollapsed ? 'Analytics' : ''">
                    <i data-lucide="lightbulb" class="w-5 h-5 flex-shrink-0"></i>
                    <span x-show="!sidebarCollapsed" 
                          x-transition:enter="transition ease-out duration-200" 
                          x-transition:enter-start="opacity-0 -translate-x-2" 
                          x-transition:enter-end="opacity-100 translate-x-0" 
                          x-transition:leave="transition ease-in duration-150" 
                          x-transition:leave-start="opacity-100 translate-x-0" 
                          x-transition:leave-end="opacity-0 -translate-x-2" 
                          class="font-medium whitespace-nowrap">Analytics</span>
                </a>
                <a href="<?php echo getSafeNodeUrl('suspicious-ips'); ?>" 
                   class="nav-item <?php echo $currentPage == 'suspicious-ips' ? 'active' : ''; ?>" 
                   :class="sidebarCollapsed ? 'justify-center px-2' : ''" 
                   :title="sidebarCollapsed ? 'IPs Suspeitos' : ''">
                    <i data-lucide="alert-octagon" class="w-5 h-5 flex-shrink-0"></i>
                    <span x-show="!sidebarCollapsed" 
                          x-transition:enter="transition ease-out duration-200" 
                          x-transition:enter-start="opacity-0 -translate-x-2" 
                          x-transition:enter-end="opacity-100 translate-x-0" 
                          x-transition:leave="transition ease-in duration-150" 
                          x-transition:leave-start="opacity-100 translate-x-0" 
                          x-transition:leave-end="opacity-0 -translate-x-2" 
                          class="font-medium whitespace-nowrap">IPs Suspeitos</span>
                </a>
                <a href="<?php echo getSafeNodeUrl('attacked-targets'); ?>" 
                   class="nav-item <?php echo $currentPage == 'attacked-targets' ? 'active' : ''; ?>" 
                   :class="sidebarCollapsed ? 'justify-center px-2' : ''" 
                   :title="sidebarCollapsed ? 'Alvos Atacados' : ''">
                    <i data-lucide="target" class="w-5 h-5 flex-shrink-0"></i>
                    <span x-show="!sidebarCollapsed" 
                          x-transition:enter="transition ease-out duration-200" 
                          x-transition:enter-start="opacity-0 -translate-x-2" 
                          x-transition:enter-end="opacity-100 translate-x-0" 
                          x-transition:leave="transition ease-in duration-150" 
                          x-transition:leave-start="opacity-100 translate-x-0" 
                          x-transition:leave-end="opacity-0 -translate-x-2" 
                          class="font-medium whitespace-nowrap">Alvos Atacados</span>
                </a>
            </div>
            
            <div class="pt-4 mt-4 border-t border-white/5">
                <p x-show="!sidebarCollapsed" 
                   x-transition:enter="transition ease-out duration-200" 
                   x-transition:enter-start="opacity-0" 
                   x-transition:enter-end="opacity-100" 
                   x-transition:leave="transition ease-in duration-150" 
                   x-transition:leave-start="opacity-100" 
                   x-transition:leave-end="opacity-0" 
                   class="text-xs font-semibold text-zinc-600 uppercase tracking-wider mb-3 px-3 whitespace-nowrap">Inteligência</p>
                <a href="<?php echo getSafeNodeUrl('threat-intelligence'); ?>" 
                   class="nav-item <?php echo $currentPage == 'threat-intelligence' ? 'active' : ''; ?>" 
                   :class="sidebarCollapsed ? 'justify-center px-2' : ''" 
                   :title="sidebarCollapsed ? 'Threat Intelligence' : ''">
                    <i data-lucide="shield-alert" class="w-5 h-5 flex-shrink-0"></i>
                    <span x-show="!sidebarCollapsed" 
                          x-transition:enter="transition ease-out duration-200" 
                          x-transition:enter-start="opacity-0 -translate-x-2" 
                          x-transition:enter-end="opacity-100 translate-x-0" 
                          x-transition:leave="transition ease-in duration-150" 
                          x-transition:leave-start="opacity-100 translate-x-0" 
                          x-transition:leave-end="opacity-0 -translate-x-2" 
                          class="font-medium whitespace-nowrap">Threat Intelligence</span>
                </a>
                <a href="<?php echo getSafeNodeUrl('security-advisor'); ?>" 
                   class="nav-item <?php echo $currentPage == 'security-advisor' ? 'active' : ''; ?>" 
                   :class="sidebarCollapsed ? 'justify-center px-2' : ''" 
                   :title="sidebarCollapsed ? 'Security Advisor' : ''">
                    <i data-lucide="shield-check" class="w-5 h-5 flex-shrink-0"></i>
                    <span x-show="!sidebarCollapsed" 
                          x-transition:enter="transition ease-out duration-200" 
                          x-transition:enter-start="opacity-0 -translate-x-2" 
                          x-transition:enter-end="opacity-100 translate-x-0" 
                          x-transition:leave="transition ease-in duration-150" 
                          x-transition:leave-start="opacity-100 translate-x-0" 
                          x-transition:leave-end="opacity-0 -translate-x-2" 
                          class="font-medium whitespace-nowrap">Security Advisor</span>
                </a>
                <a href="<?php echo getSafeNodeUrl('vulnerability-scanner'); ?>" 
                   class="nav-item <?php echo $currentPage == 'vulnerability-scanner' ? 'active' : ''; ?>" 
                   :class="sidebarCollapsed ? 'justify-center px-2' : ''" 
                   :title="sidebarCollapsed ? 'Vulnerability Scanner' : ''">
                    <i data-lucide="scan-search" class="w-5 h-5 flex-shrink-0"></i>
                    <span x-show="!sidebarCollapsed" 
                          x-transition:enter="transition ease-out duration-200" 
                          x-transition:enter-start="opacity-0 -translate-x-2" 
                          x-transition:enter-end="opacity-100 translate-x-0" 
                          x-transition:leave="transition ease-in duration-150" 
                          x-transition:leave-start="opacity-100 translate-x-0" 
                          x-transition:leave-end="opacity-0 -translate-x-2" 
                          class="font-medium whitespace-nowrap">Vulnerability Scanner</span>
                </a>
                <a href="<?php echo getSafeNodeUrl('anomaly-detector'); ?>" 
                   class="nav-item <?php echo $currentPage == 'anomaly-detector' ? 'active' : ''; ?>" 
                   :class="sidebarCollapsed ? 'justify-center px-2' : ''" 
                   :title="sidebarCollapsed ? 'Anomaly Detector' : ''">
                    <i data-lucide="radar" class="w-5 h-5 flex-shrink-0"></i>
                    <span x-show="!sidebarCollapsed" 
                          x-transition:enter="transition ease-out duration-200" 
                          x-transition:enter-start="opacity-0 -translate-x-2" 
                          x-transition:enter-end="opacity-100 translate-x-0" 
                          x-transition:leave="transition ease-in duration-150" 
                          x-transition:leave-start="opacity-100 translate-x-0" 
                          x-transition:leave-end="opacity-0 -translate-x-2" 
                          class="font-medium whitespace-nowrap">Anomaly Detector</span>
                </a>
                <a href="<?php echo getSafeNodeUrl('endpoint-protection'); ?>" 
                   class="nav-item <?php echo $currentPage == 'endpoint-protection' ? 'active' : ''; ?>" 
                   :class="sidebarCollapsed ? 'justify-center px-2' : ''" 
                   :title="sidebarCollapsed ? 'Proteção por Endpoint' : ''">
                    <i data-lucide="route" class="w-5 h-5 flex-shrink-0"></i>
                    <span x-show="!sidebarCollapsed" 
                          x-transition:enter="transition ease-out duration-200" 
                          x-transition:enter-start="opacity-0 -translate-x-2" 
                          x-transition:enter-end="opacity-100 translate-x-0" 
                          x-transition:leave="transition ease-in duration-150" 
                          x-transition:leave-start="opacity-100 translate-x-0" 
                          x-transition:leave-end="opacity-0 -translate-x-2" 
                          class="font-medium whitespace-nowrap">Proteção por Endpoint</span>
                </a>
                <a href="<?php echo getSafeNodeUrl('security-tests'); ?>" 
                   class="nav-item <?php echo $currentPage == 'security-tests' ? 'active' : ''; ?>" 
                   :class="sidebarCollapsed ? 'justify-center px-2' : ''" 
                   :title="sidebarCollapsed ? 'Testes de Segurança' : ''">
                    <i data-lucide="test-tube" class="w-5 h-5 flex-shrink-0"></i>
                    <span x-show="!sidebarCollapsed" 
                          x-transition:enter="transition ease-out duration-200" 
                          x-transition:enter-start="opacity-0 -translate-x-2" 
                          x-transition:enter-end="opacity-100 translate-x-0" 
                          x-transition:leave="transition ease-in duration-150" 
                          x-transition:leave-start="opacity-100 translate-x-0" 
                          x-transition:leave-end="opacity-0 -translate-x-2" 
                          class="font-medium whitespace-nowrap">Testes de Segurança</span>
                </a>
            </div>
            
            <div class="pt-4 mt-4 border-t border-white/5">
                <p x-show="!sidebarCollapsed" 
                   x-transition:enter="transition ease-out duration-200" 
                   x-transition:enter-start="opacity-0" 
                   x-transition:enter-end="opacity-100" 
                   x-transition:leave="transition ease-in duration-150" 
                   x-transition:leave-start="opacity-100" 
                   x-transition:leave-end="opacity-0" 
                   class="text-xs font-semibold text-zinc-600 uppercase tracking-wider mb-3 px-3 whitespace-nowrap">Sistema</p>
                <a href="<?php echo getSafeNodeUrl('updates'); ?>" 
                   class="nav-item <?php echo $currentPage == 'updates' ? 'active' : ''; ?>" 
                   :class="sidebarCollapsed ? 'justify-center px-2' : ''" 
                   :title="sidebarCollapsed ? 'Atualizações' : ''">
                    <i data-lucide="sparkles" class="w-5 h-5 flex-shrink-0"></i>
                    <span x-show="!sidebarCollapsed" 
                          x-transition:enter="transition ease-out duration-200" 
                          x-transition:enter-start="opacity-0 -translate-x-2" 
                          x-transition:enter-end="opacity-100 translate-x-0" 
                          x-transition:leave="transition ease-in duration-150" 
                          x-transition:leave-start="opacity-100 translate-x-0" 
                          x-transition:leave-end="opacity-0 -translate-x-2" 
                          class="font-medium whitespace-nowrap">Atualizações</span>
                </a>
                <a href="<?php echo getSafeNodeUrl('human-verification'); ?>" 
                   class="nav-item <?php echo $currentPage == 'human-verification' ? 'active' : ''; ?>" 
                   :class="sidebarCollapsed ? 'justify-center px-2' : ''" 
                   :title="sidebarCollapsed ? 'Verificação Humana' : ''">
                    <i data-lucide="shield-check" class="w-5 h-5 flex-shrink-0"></i>
                    <span x-show="!sidebarCollapsed" 
                          x-transition:enter="transition ease-out duration-200" 
                          x-transition:enter-start="opacity-0 -translate-x-2" 
                          x-transition:enter-end="opacity-100 translate-x-0" 
                          x-transition:leave="transition ease-in duration-150" 
                          x-transition:leave-start="opacity-100 translate-x-0" 
                          x-transition:leave-end="opacity-0 -translate-x-2" 
                          class="font-medium whitespace-nowrap">Verificação Humana</span>
                </a>
                <a href="<?php echo getSafeNodeUrl('settings'); ?>" 
                   class="nav-item <?php echo $currentPage == 'settings' ? 'active' : ''; ?>" 
                   :class="sidebarCollapsed ? 'justify-center px-2' : ''" 
                   :title="sidebarCollapsed ? 'Configurações' : ''">
                    <i data-lucide="settings-2" class="w-5 h-5 flex-shrink-0"></i>
                    <span x-show="!sidebarCollapsed" 
                          x-transition:enter="transition ease-out duration-200" 
                          x-transition:enter-start="opacity-0 -translate-x-2" 
                          x-transition:enter-end="opacity-100 translate-x-0" 
                          x-transition:leave="transition ease-in duration-150" 
                          x-transition:leave-start="opacity-100 translate-x-0" 
                          x-transition:leave-end="opacity-0 -translate-x-2" 
                          class="font-medium whitespace-nowrap">Configurações</span>
                </a>
                <a href="<?php echo getSafeNodeUrl('help'); ?>" 
                   class="nav-item <?php echo $currentPage == 'help' ? 'active' : ''; ?>" 
                   :class="sidebarCollapsed ? 'justify-center px-2' : ''" 
                   :title="sidebarCollapsed ? 'Ajuda' : ''">
                    <i data-lucide="life-buoy" class="w-5 h-5 flex-shrink-0"></i>
                    <span x-show="!sidebarCollapsed" 
                          x-transition:enter="transition ease-out duration-200" 
                          x-transition:enter-start="opacity-0 -translate-x-2" 
                          x-transition:enter-end="opacity-100 translate-x-0" 
                          x-transition:leave="transition ease-in duration-150" 
                          x-transition:leave-start="opacity-100 translate-x-0" 
                          x-transition:leave-end="opacity-0 -translate-x-2" 
                          class="font-medium whitespace-nowrap">Ajuda</span>
                </a>
            </div>
        </nav>
        
        <!-- Upgrade Card -->
        <div class="p-4 flex-shrink-0" 
             x-show="!sidebarCollapsed" 
             x-transition:enter="transition ease-out duration-200" 
             x-transition:enter-start="opacity-0 translate-y-2" 
             x-transition:enter-end="opacity-100 translate-y-0" 
             x-transition:leave="transition ease-in duration-150" 
             x-transition:leave-start="opacity-100 translate-y-0" 
             x-transition:leave-end="opacity-0 translate-y-2">
            <div class="upgrade-card">
                <h3 class="font-semibold text-white text-sm mb-3">Ativar Pro</h3>
                <button class="w-full btn-primary py-2.5 text-sm">
                    Upgrade Agora
                </button>
            </div>
        </div>
    </aside>

    <!-- Backdrop para mobile (controlado por JavaScript antigo - manter para compatibilidade) -->
    <div id="safenode-sidebar-backdrop" class="fixed inset-0 bg-black/60 z-40 hidden lg:hidden"></div>

    <!-- Modal de confirmação de saída -->
    <div id="safenode-logout-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 backdrop-blur-sm">
        <div class="w-full max-w-sm mx-4 rounded-2xl bg-zinc-950 border border-white/10 p-6 shadow-2xl">
            <h3 class="text-lg font-bold text-white mb-2">Deseja realmente sair?</h3>
            <p class="text-sm text-zinc-400 mb-6">Você será desconectado do painel SafeNode e precisará fazer login novamente para acessar o sistema.</p>
            <div class="flex gap-3 justify-end">
                <button type="button" data-logout-cancel class="px-4 py-2 rounded-xl bg-zinc-900 text-zinc-300 hover:bg-zinc-800 text-sm font-semibold transition-all">
                    Cancelar
                </button>
                <button type="button" data-logout-confirm class="px-4 py-2 rounded-xl bg-red-600 text-white hover:bg-red-700 text-sm font-semibold transition-all">
                    Sair do sistema
                </button>
            </div>
        </div>
    </div>

    <script>
    (function() {
        const sidebar = document.getElementById('safenode-sidebar');
        const backdrop = document.getElementById('safenode-sidebar-backdrop');
        const logoutModal = document.getElementById('safenode-logout-modal');
        
        // Variáveis para gesto de swipe
        let touchStartX = 0;
        let touchStartY = 0;
        let touchEndX = 0;
        let touchEndY = 0;
        let isSwiping = false;
        let sidebarElement = null;
        
        // Função para atualizar sidebarOpen no Alpine.js
        function updateAlpineSidebarState(isOpen) {
            // Buscar o elemento body que geralmente tem x-data com sidebarOpen
            const bodyElement = document.body;
            if (bodyElement && window.Alpine && typeof Alpine !== 'undefined') {
                try {
                    // Aguardar Alpine estar inicializado
                    if (document.readyState === 'loading') {
                        document.addEventListener('DOMContentLoaded', function() {
                            setTimeout(() => updateAlpineSidebarState(isOpen), 100);
                        });
                        return;
                    }
                    
                    // Tentar acessar o estado do Alpine.js
                    const bodyData = Alpine.$data(bodyElement);
                    if (bodyData && typeof bodyData.sidebarOpen !== 'undefined') {
                        bodyData.sidebarOpen = isOpen;
                        return;
                    }
                } catch (e) {
                    console.warn('Não foi possível atualizar estado Alpine diretamente:', e);
                }
            }
            
            // Fallback: disparar evento customizado que o Alpine.js pode escutar
            window.dispatchEvent(new CustomEvent('safenode-sidebar-toggle', { 
                detail: { isOpen: isOpen } 
            }));
        }

        function openSidebar() {
            if (sidebar) {
                sidebar.classList.remove('-translate-x-full');
            }
            if (backdrop) {
                backdrop.classList.remove('hidden');
            }
            
            // Encontrar e mostrar sidebar mobile
            const mobileSidebar = document.querySelector('aside.mobile-sidebar, aside[x-show*="sidebarOpen"]');
            if (mobileSidebar) {
                mobileSidebar.style.display = 'flex';
                mobileSidebar.style.transform = 'translateX(0)';
                mobileSidebar.classList.add('mobile-sidebar-open');
                mobileSidebar.removeAttribute('x-cloak');
            }
            
            updateAlpineSidebarState(true);
        }

        function closeSidebar() {
            if (sidebar) {
                sidebar.classList.add('-translate-x-full');
            }
            if (backdrop) {
                backdrop.classList.add('hidden');
            }
            
            // Esconder sidebar mobile
            const mobileSidebar = document.querySelector('aside.mobile-sidebar, aside[x-show*="sidebarOpen"]');
            if (mobileSidebar) {
                mobileSidebar.style.display = 'none';
                mobileSidebar.style.transform = 'translateX(-100%)';
                mobileSidebar.classList.remove('mobile-sidebar-open');
                mobileSidebar.setAttribute('x-cloak', '');
            }
            
            updateAlpineSidebarState(false);
        }

        function toggleSidebar() {
            // Verificar estado atual via Alpine.js primeiro
            let isCurrentlyOpen = false;
            
            // Tentar encontrar o estado atual
            const bodyElement = document.body;
            if (bodyElement && window.Alpine) {
                try {
                    const bodyData = Alpine.$data(bodyElement);
                    if (bodyData && typeof bodyData.sidebarOpen !== 'undefined') {
                        isCurrentlyOpen = bodyData.sidebarOpen;
                    }
                } catch (e) {
                    // Se não conseguir, verificar via classe CSS
                    if (sidebar) {
                        isCurrentlyOpen = !sidebar.classList.contains('-translate-x-full');
                    }
                }
            } else if (sidebar) {
                isCurrentlyOpen = !sidebar.classList.contains('-translate-x-full');
            }
            
            if (isCurrentlyOpen) {
                closeSidebar();
            } else {
                openSidebar();
            }
        }

        document.addEventListener('click', function(e) {
            const toggleBtn = e.target.closest('[data-sidebar-toggle]');
            if (toggleBtn) {
                e.preventDefault();
                e.stopPropagation();
                // Aguardar um tick para garantir que Alpine.js está pronto
                setTimeout(() => {
                    toggleSidebar();
                }, 10);
                return;
            }

            const logoutBtn = e.target.closest('[data-logout-trigger]');
            if (logoutBtn && logoutModal) {
                e.preventDefault();
                logoutModal.classList.remove('hidden');
                logoutModal.classList.add('flex');
            }
        });
        
        // Escutar eventos customizados para sincronizar com Alpine.js
        window.addEventListener('safenode-sidebar-toggle', function(e) {
            const isOpen = e.detail.isOpen;
            if (sidebar) {
                if (isOpen) {
                    sidebar.classList.remove('-translate-x-full');
                } else {
                    sidebar.classList.add('-translate-x-full');
                }
            }
            // Sincronizar backdrop antigo também
            if (backdrop) {
                if (isOpen) {
                    backdrop.classList.remove('hidden');
                } else {
                    backdrop.classList.add('hidden');
                }
            }
            
            // Sincronizar sidebar mobile
            const mobileSidebar = document.querySelector('aside.mobile-sidebar, aside[x-show*="sidebarOpen"]');
            if (mobileSidebar) {
                if (isOpen) {
                    mobileSidebar.style.display = 'flex';
                    mobileSidebar.style.transform = 'translateX(0)';
                    mobileSidebar.classList.add('mobile-sidebar-open');
                    mobileSidebar.removeAttribute('x-cloak');
                } else {
                    mobileSidebar.style.display = 'none';
                    mobileSidebar.style.transform = 'translateX(-100%)';
                    mobileSidebar.classList.remove('mobile-sidebar-open');
                    mobileSidebar.setAttribute('x-cloak', '');
                }
            }
        });
        
        // Observar mudanças no estado do Alpine.js para sincronizar backdrop
        function syncBackdropWithAlpine() {
            const bodyElement = document.body;
            if (bodyElement && window.Alpine && typeof Alpine !== 'undefined') {
                try {
                    const bodyData = Alpine.$data(bodyElement);
                    if (bodyData && typeof bodyData.sidebarOpen !== 'undefined') {
                        const isOpen = bodyData.sidebarOpen;
                        if (backdrop) {
                            if (isOpen) {
                                backdrop.classList.remove('hidden');
                            } else {
                                backdrop.classList.add('hidden');
                            }
                        }
                    }
                } catch (e) {
                    // Ignorar erros
                }
            }
        }
        
        // Verificar estado periodicamente e quando Alpine inicializar
        if (window.Alpine && typeof Alpine !== 'undefined') {
            // Aguardar Alpine estar pronto
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() {
                    setTimeout(function() {
                        setInterval(syncBackdropWithAlpine, 100);
                        syncBackdropWithAlpine();
                    }, 200);
                });
            } else {
                setTimeout(function() {
                    setInterval(syncBackdropWithAlpine, 100);
                    syncBackdropWithAlpine();
                }, 200);
            }
        }
        
        // Garantir que backdrop seja escondido quando sidebar fechar via qualquer método
        function ensureBackdropHidden() {
            if (backdrop) {
                const bodyElement = document.body;
                let shouldBeVisible = false;
                
                // Verificar estado do Alpine.js
                if (bodyElement && window.Alpine && typeof Alpine !== 'undefined') {
                    try {
                        const bodyData = Alpine.$data(bodyElement);
                        if (bodyData && typeof bodyData.sidebarOpen !== 'undefined') {
                            shouldBeVisible = bodyData.sidebarOpen;
                        }
                    } catch (e) {
                        // Ignorar
                    }
                }
                
                // Se não deveria estar visível, esconder
                if (!shouldBeVisible) {
                    backdrop.classList.add('hidden');
                }
            }
        }
        
        // Executar verificação periodicamente
        setInterval(ensureBackdropHidden, 200);
        
        // Escutar mudanças do Alpine.js também
        if (window.Alpine && typeof Alpine !== 'undefined') {
            // Aguardar Alpine estar pronto
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() {
                    setupAlpineListener();
                });
            } else {
                setupAlpineListener();
            }
        }
        
        function setupAlpineListener() {
            // Observar mudanças no body quando sidebarOpen mudar
            const bodyElement = document.body;
            if (bodyElement && window.Alpine) {
                try {
                    // Usar MutationObserver para detectar mudanças no atributo x-data
                    // ou simplesmente escutar eventos do Alpine
                    const observer = new MutationObserver(function() {
                        // Verificar se sidebar precisa ser atualizada
                        try {
                            const bodyData = Alpine.$data(bodyElement);
                            if (bodyData && typeof bodyData.sidebarOpen !== 'undefined') {
                                const isOpen = bodyData.sidebarOpen;
                                if (sidebar) {
                                    if (isOpen && sidebar.classList.contains('-translate-x-full')) {
                                        sidebar.classList.remove('-translate-x-full');
                                    } else if (!isOpen && !sidebar.classList.contains('-translate-x-full')) {
                                        sidebar.classList.add('-translate-x-full');
                                    }
                                }
                            }
                        } catch (e) {
                            // Ignorar erros
                        }
                    });
                    
                    // Observar mudanças no body
                    observer.observe(bodyElement, {
                        attributes: true,
                        attributeFilter: ['x-data']
                    });
                } catch (e) {
                    console.warn('Não foi possível configurar observer Alpine:', e);
                }
            }
        }

        if (logoutModal) {
            const cancelBtn = logoutModal.querySelector('[data-logout-cancel]');
            const confirmBtn = logoutModal.querySelector('[data-logout-confirm]');

            if (cancelBtn) {
                cancelBtn.addEventListener('click', function() {
                    logoutModal.classList.add('hidden');
                    logoutModal.classList.remove('flex');
                });
            }

            if (confirmBtn) {
                confirmBtn.addEventListener('click', function() {
                    window.location.href = 'login.php?logout=1';
                });
            }

            logoutModal.addEventListener('click', function(e) {
                if (e.target === logoutModal) {
                    logoutModal.classList.add('hidden');
                    logoutModal.classList.remove('flex');
                }
            });
        }

        if (backdrop) {
            backdrop.addEventListener('click', closeSidebar);
        }

        window.addEventListener('resize', function() {
            if (window.innerWidth >= 768) {
                sidebar.classList.remove('-translate-x-full');
                if (backdrop) backdrop.classList.add('hidden');
            } else {
                sidebar.classList.add('-translate-x-full');
            }
        });
        
        // Detecção de gesto swipe para abrir sidebar
        function initSwipeGesture() {
            const bodyElement = document.body;
            let swipeThreshold = 30; // Distância mínima para considerar swipe (reduzido)
            let minSwipeDistance = 50; // Distância mínima para abrir sidebar (reduzido)
            let maxVerticalDistance = 50; // Máxima distância vertical permitida
            let isDraggingSidebar = false; // Flag para arrastar sidebar diretamente
            let wasSidebarPartiallyVisible = false; // Flag para saber se sidebar estava parcialmente visível
            
            bodyElement.addEventListener('touchstart', function(e) {
                if (window.innerWidth >= 1024) return; // Só no mobile
                
                const touchX = e.touches[0].clientX;
                const touchY = e.touches[0].clientY;
                
                // Encontrar elemento da sidebar mobile
                sidebarElement = document.querySelector('aside[x-show*="sidebarOpen"]');
                
                if (!sidebarElement) return;
                
                // Verificar se a sidebar já estava parcialmente visível no início
                const sidebarRect = sidebarElement.getBoundingClientRect();
                wasSidebarPartiallyVisible = sidebarRect.left > -288 && sidebarRect.left < 0;
                
                // Verificar se o toque está na sidebar (quando ela está parcialmente visível)
                const isTouchingSidebar = touchX >= sidebarRect.left && touchX <= sidebarRect.right &&
                                         touchY >= sidebarRect.top && touchY <= sidebarRect.bottom;
                
                // Se estiver tocando na sidebar ou na borda esquerda (primeiros 20px)
                if (isTouchingSidebar || touchX <= 20) {
                    touchStartX = touchX;
                    touchStartY = touchY;
                    isSwiping = true;
                    isDraggingSidebar = isTouchingSidebar;
                }
            }, { passive: true });
            
            bodyElement.addEventListener('touchmove', function(e) {
                if (!isSwiping) return;
                
                touchEndX = e.touches[0].clientX;
                touchEndY = e.touches[0].clientY;
                
                const deltaX = touchEndX - touchStartX;
                const deltaY = Math.abs(touchEndY - touchStartY);
                
                // Se o movimento vertical for muito grande, cancelar swipe
                if (deltaY > maxVerticalDistance) {
                    isSwiping = false;
                    return;
                }
                
                // Se estiver arrastando para a direita, mostrar preview da sidebar
                if (deltaX > 0 && sidebarElement) {
                    const sidebarWidth = 288; // w-72 = 18rem = 288px
                    let currentProgress = 0;
                    
                    // Se estiver arrastando a sidebar diretamente, calcular progresso baseado na posição atual
                    if (isDraggingSidebar) {
                        const sidebarRect = sidebarElement.getBoundingClientRect();
                        const currentTranslate = sidebarRect.left;
                        const currentProgressValue = Math.max(0, (currentTranslate + sidebarWidth) / sidebarWidth);
                        currentProgress = Math.min(1, currentProgressValue + (deltaX / sidebarWidth));
                    } else {
                        // Se estiver arrastando da borda, calcular progresso normalmente
                        currentProgress = Math.min(deltaX / sidebarWidth, 1);
                    }
                    
                    sidebarElement.style.transform = `translateX(${-100 + (currentProgress * 100)}%)`;
                    sidebarElement.style.transition = 'none';
                    sidebarElement.style.display = 'flex';
                    sidebarElement.removeAttribute('x-cloak');
                    
                    // Mostrar backdrop com opacidade proporcional
                    if (backdrop) {
                        backdrop.style.opacity = (currentProgress * 0.8).toString();
                        backdrop.classList.remove('hidden');
                    }
                }
            }, { passive: true });
            
            bodyElement.addEventListener('touchend', function(e) {
                if (!isSwiping) return;
                
                const deltaX = touchEndX - touchStartX;
                const deltaY = Math.abs(touchEndY - touchStartY);
                
                // Se arrastou para a direita (abrir)
                if (deltaX > 0) {
                    // Se a sidebar já estava parcialmente visível quando começou o swipe, qualquer movimento para a direita abre completamente
                    if (wasSidebarPartiallyVisible || isDraggingSidebar) {
                        openSidebar();
                    } else if (deltaX > swipeThreshold && deltaY < maxVerticalDistance) {
                        // Se arrastou mais que o threshold mínimo, abrir completamente
                        openSidebar();
                    } else {
                        // Se não arrastou o suficiente, fechar
                        if (sidebarElement) {
                            sidebarElement.style.transition = '';
                            sidebarElement.style.transform = 'translateX(-100%) !important';
                        }
                        if (backdrop) {
                            backdrop.style.opacity = '';
                            backdrop.classList.add('hidden');
                        }
                    }
                } else {
                    // Se arrastou para a esquerda (fechar), fechar normalmente
                    if (sidebarElement) {
                        sidebarElement.style.transition = '';
                        sidebarElement.style.transform = 'translateX(-100%) !important';
                    }
                    if (backdrop) {
                        backdrop.style.opacity = '';
                        backdrop.classList.add('hidden');
                    }
                }
                
                isSwiping = false;
                isDraggingSidebar = false;
                wasSidebarPartiallyVisible = false;
                touchStartX = 0;
                touchStartY = 0;
                touchEndX = 0;
                touchEndY = 0;
            }, { passive: true });
            
            bodyElement.addEventListener('touchcancel', function(e) {
                // Resetar estado se o toque for cancelado
                if (sidebarElement) {
                    sidebarElement.style.transition = '';
                    sidebarElement.style.transform = 'translateX(-100%) !important';
                }
                if (backdrop) {
                    backdrop.style.opacity = '';
                    backdrop.classList.add('hidden');
                }
                isSwiping = false;
                isDraggingSidebar = false;
                wasSidebarPartiallyVisible = false;
            }, { passive: true });
        }
        
        // Inicializar gesto de swipe quando DOM estiver pronto
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(initSwipeGesture, 100);
            });
        } else {
            setTimeout(initSwipeGesture, 100);
        }
    })();
    </script>

    <!-- Mobile Sidebar Overlay -->
    <div x-show="sidebarOpen" 
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="sidebarOpen = false; $dispatch('safenode-sidebar-toggle', { isOpen: false })"
         @safenode-sidebar-toggle.window="sidebarOpen = $event.detail.isOpen"
         class="fixed inset-0 bg-black/80 z-[60] lg:hidden"
         style="pointer-events: auto;"
         x-cloak></div>

    <!-- Mobile Sidebar -->
    <aside x-show="sidebarOpen"
           x-init="$watch('sidebarOpen', value => { 
               if (value) { 
                   $el.style.display = 'flex'; 
                   $el.removeAttribute('x-cloak');
               } else {
                   $el.style.display = 'none';
               }
           })"
           x-transition:enter="transition ease-out duration-300 transform"
           x-transition:enter-start="-translate-x-full"
           x-transition:enter-end="translate-x-0"
           x-transition:leave="transition ease-in duration-300 transform"
           x-transition:leave-start="translate-x-0"
           x-transition:leave-end="-translate-x-full"
           @click.away="sidebarOpen = false; $dispatch('safenode-sidebar-toggle', { isOpen: false })"
           @safenode-sidebar-toggle.window="sidebarOpen = $event.detail.isOpen"
           class="fixed inset-y-0 left-0 w-72 sidebar h-full flex flex-col z-[70] lg:hidden overflow-y-auto mobile-sidebar"
           style="position: fixed !important; transform: translateX(-100%); will-change: transform;"
           x-cloak>
        <!-- Logo -->
        <div class="p-4 border-b border-white/5 flex-shrink-0 relative">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <img src="assets/img/logos (6).png" alt="SafeNode Logo" class="w-8 h-8 object-contain flex-shrink-0">
                    <div class="overflow-hidden whitespace-nowrap">
                        <h1 class="font-bold text-white text-xl tracking-tight">SafeNode</h1>
                        <p class="text-xs text-zinc-500 font-medium">Security Platform</p>
                    </div>
                </div>
                <button @click="sidebarOpen = false; $dispatch('safenode-sidebar-toggle', { isOpen: false })" class="text-zinc-600 hover:text-zinc-400 transition-colors flex-shrink-0">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
        </div>
        
        <!-- Navigation -->
        <nav class="flex-1 p-4 space-y-1 overflow-y-auto overflow-x-hidden">
            <p class="text-xs font-semibold text-zinc-600 uppercase tracking-wider mb-3 px-3 whitespace-nowrap">Principal</p>
            
            <a href="<?php echo getSafeNodeUrl('dashboard'); ?>" class="nav-item <?php echo $currentPage == 'dashboard' ? 'active' : ''; ?>" @click="sidebarOpen = false">
                <i data-lucide="layout-dashboard" class="w-5 h-5 flex-shrink-0"></i>
                <span class="font-medium whitespace-nowrap">Dashboard</span>
            </a>
            <a href="<?php echo getSafeNodeUrl('sites'); ?>" class="nav-item <?php echo $currentPage == 'sites' ? 'active' : ''; ?>" @click="sidebarOpen = false">
                <i data-lucide="globe" class="w-5 h-5 flex-shrink-0"></i>
                <span class="font-medium whitespace-nowrap">Gerenciar Sites</span>
            </a>
            <a href="<?php echo getSafeNodeUrl('mail'); ?>" class="nav-item <?php echo $currentPage == 'mail' ? 'active' : ''; ?>" @click="sidebarOpen = false">
                <i data-lucide="mail" class="w-5 h-5 flex-shrink-0"></i>
                <span class="font-medium whitespace-nowrap">Mail</span>
            </a>
            
            <div class="pt-4 mt-4 border-t border-white/5">
                <p class="text-xs font-semibold text-zinc-600 uppercase tracking-wider mb-3 px-3 whitespace-nowrap">Análises</p>
                <a href="<?php echo getSafeNodeUrl('logs'); ?>" class="nav-item <?php echo $currentPage == 'logs' ? 'active' : ''; ?>" @click="sidebarOpen = false">
                    <i data-lucide="file-text" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="font-medium whitespace-nowrap">Explorar Logs</span>
                </a>
                <a href="<?php echo getSafeNodeUrl('behavior-analysis'); ?>" class="nav-item <?php echo $currentPage == 'behavior-analysis' ? 'active' : ''; ?>" @click="sidebarOpen = false">
                    <i data-lucide="brain" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="font-medium whitespace-nowrap">Comportamental</span>
                </a>
                <a href="<?php echo getSafeNodeUrl('security-analytics'); ?>" class="nav-item <?php echo $currentPage == 'security-analytics' ? 'active' : ''; ?>" @click="sidebarOpen = false">
                    <i data-lucide="lightbulb" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="font-medium whitespace-nowrap">Analytics</span>
                </a>
                <a href="<?php echo getSafeNodeUrl('suspicious-ips'); ?>" class="nav-item <?php echo $currentPage == 'suspicious-ips' ? 'active' : ''; ?>" @click="sidebarOpen = false">
                    <i data-lucide="alert-octagon" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="font-medium whitespace-nowrap">IPs Suspeitos</span>
                </a>
                <a href="<?php echo getSafeNodeUrl('attacked-targets'); ?>" class="nav-item <?php echo $currentPage == 'attacked-targets' ? 'active' : ''; ?>" @click="sidebarOpen = false">
                    <i data-lucide="target" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="font-medium whitespace-nowrap">Alvos Atacados</span>
                </a>
            </div>
            
            <div class="pt-4 mt-4 border-t border-white/5">
                <p class="text-xs font-semibold text-zinc-600 uppercase tracking-wider mb-3 px-3 whitespace-nowrap">Inteligência</p>
                <a href="<?php echo getSafeNodeUrl('threat-intelligence'); ?>" class="nav-item <?php echo $currentPage == 'threat-intelligence' ? 'active' : ''; ?>" @click="sidebarOpen = false">
                    <i data-lucide="shield-alert" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="font-medium whitespace-nowrap">Threat Intelligence</span>
                </a>
                <a href="<?php echo getSafeNodeUrl('security-advisor'); ?>" class="nav-item <?php echo $currentPage == 'security-advisor' ? 'active' : ''; ?>" @click="sidebarOpen = false">
                    <i data-lucide="shield-check" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="font-medium whitespace-nowrap">Security Advisor</span>
                </a>
                <a href="<?php echo getSafeNodeUrl('vulnerability-scanner'); ?>" class="nav-item <?php echo $currentPage == 'vulnerability-scanner' ? 'active' : ''; ?>" @click="sidebarOpen = false">
                    <i data-lucide="scan-search" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="font-medium whitespace-nowrap">Vulnerability Scanner</span>
                </a>
                <a href="<?php echo getSafeNodeUrl('anomaly-detector'); ?>" class="nav-item <?php echo $currentPage == 'anomaly-detector' ? 'active' : ''; ?>" @click="sidebarOpen = false">
                    <i data-lucide="radar" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="font-medium whitespace-nowrap">Anomaly Detector</span>
                </a>
                <a href="<?php echo getSafeNodeUrl('endpoint-protection'); ?>" class="nav-item <?php echo $currentPage == 'endpoint-protection' ? 'active' : ''; ?>" @click="sidebarOpen = false">
                    <i data-lucide="route" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="font-medium whitespace-nowrap">Proteção por Endpoint</span>
                </a>
                <a href="<?php echo getSafeNodeUrl('security-tests'); ?>" class="nav-item <?php echo $currentPage == 'security-tests' ? 'active' : ''; ?>" @click="sidebarOpen = false">
                    <i data-lucide="test-tube" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="font-medium whitespace-nowrap">Testes de Segurança</span>
                </a>
            </div>
            
            <div class="pt-4 mt-4 border-t border-white/5">
                <p class="text-xs font-semibold text-zinc-600 uppercase tracking-wider mb-3 px-3 whitespace-nowrap">Sistema</p>
                <a href="<?php echo getSafeNodeUrl('updates'); ?>" class="nav-item <?php echo $currentPage == 'updates' ? 'active' : ''; ?>" @click="sidebarOpen = false">
                    <i data-lucide="sparkles" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="font-medium whitespace-nowrap">Atualizações</span>
                </a>
                <a href="<?php echo getSafeNodeUrl('human-verification'); ?>" class="nav-item <?php echo $currentPage == 'human-verification' ? 'active' : ''; ?>" @click="sidebarOpen = false">
                    <i data-lucide="shield-check" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="font-medium whitespace-nowrap">Verificação Humana</span>
                </a>
                <a href="<?php echo getSafeNodeUrl('settings'); ?>" class="nav-item <?php echo $currentPage == 'settings' ? 'active' : ''; ?>" @click="sidebarOpen = false">
                    <i data-lucide="settings-2" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="font-medium whitespace-nowrap">Configurações</span>
                </a>
                <a href="<?php echo getSafeNodeUrl('help'); ?>" class="nav-item <?php echo $currentPage == 'help' ? 'active' : ''; ?>" @click="sidebarOpen = false">
                    <i data-lucide="life-buoy" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="font-medium whitespace-nowrap">Ajuda</span>
                </a>
            </div>
        </nav>
        
        <!-- Upgrade Card -->
        <div class="p-4 flex-shrink-0">
            <div class="upgrade-card">
                <h3 class="font-semibold text-white text-sm mb-3">Ativar Pro</h3>
                <button class="w-full btn-primary py-2.5 text-sm">
                    Upgrade Agora
                </button>
            </div>
        </div>
    </aside>

    <script>
    // Inicializar ícones do Lucide (incluindo o foguinho)
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
    </script>

        
    <!-- Main Content -->
    <main class="flex-1 flex flex-col h-full overflow-hidden bg-dark-950">
        <!-- Header -->
        <header class="h-20 bg-dark-900/50 backdrop-blur-xl border-b border-white/5 px-8 flex items-center justify-between flex-shrink-0">
            <div class="flex items-center gap-6">
                <button data-sidebar-toggle class="lg:hidden text-zinc-400 hover:text-white transition-colors">
                    <i data-lucide="menu" class="w-6 h-6"></i>
                </button>
                <div>
                    <h2 class="text-2xl font-bold text-white tracking-tight"><?php echo $pageTitle; ?></h2>
                </div>
            </div>
        </header>

        <!-- Content -->
        <div class="flex-1 overflow-y-auto p-8">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-white mb-2">Anomaly Detector</h1>
                <p class="text-zinc-400">Detecção de anomalias comportamentais usando análise estatística avançada</p>
            </div>
            
            <!-- Estatísticas -->
            <div x-show="loading" class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <div class="skeleton-card">
                    <div class="flex items-center justify-between mb-2">
                        <div class="skeleton-line" style="width: 100px; height: 14px;"></div>
                        <div class="skeleton-circle" style="width: 20px; height: 20px;"></div>
                    </div>
                    <div class="skeleton-line" style="width: 60px; height: 32px; margin-top: 8px;"></div>
                </div>
                <div class="skeleton-card">
                    <div class="flex items-center justify-between mb-2">
                        <div class="skeleton-line" style="width: 120px; height: 14px;"></div>
                        <div class="skeleton-circle" style="width: 20px; height: 20px;"></div>
                    </div>
                    <div class="skeleton-line" style="width: 60px; height: 32px; margin-top: 8px;"></div>
                </div>
                <div class="skeleton-card">
                    <div class="flex items-center justify-between mb-2">
                        <div class="skeleton-line" style="width: 80px; height: 14px;"></div>
                        <div class="skeleton-circle" style="width: 20px; height: 20px;"></div>
                    </div>
                    <div class="skeleton-line" style="width: 50px; height: 32px; margin-top: 8px;"></div>
                </div>
                <div class="skeleton-card">
                    <div class="flex items-center justify-between mb-2">
                        <div class="skeleton-line" style="width: 90px; height: 14px;"></div>
                        <div class="skeleton-circle" style="width: 20px; height: 20px;"></div>
                    </div>
                    <div class="skeleton-line" style="width: 50px; height: 32px; margin-top: 8px;"></div>
                </div>
            </div>
            <?php if ($stats): ?>
            <div x-show="!loading" class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <div class="bg-dark-800 border border-white/10 rounded-xl p-6">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-zinc-400 text-sm">IPs Escaneados</span>
                        <i data-lucide="network" class="w-5 h-5 text-blue-400"></i>
                    </div>
                    <div class="text-2xl font-bold text-white"><?php echo number_format($stats['total_ips_scanned'] ?? 0); ?></div>
                </div>
                <div class="bg-dark-800 border border-white/10 rounded-xl p-6">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-zinc-400 text-sm">Anomalias Detectadas</span>
                        <i data-lucide="alert-triangle" class="w-5 h-5 text-orange-400"></i>
                    </div>
                    <div class="text-2xl font-bold text-white"><?php echo number_format($stats['total_anomalies'] ?? 0); ?></div>
                </div>
                <div class="bg-dark-800 border border-white/10 rounded-xl p-6">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-zinc-400 text-sm">Score Médio</span>
                        <i data-lucide="activity" class="w-5 h-5 text-yellow-400"></i>
                    </div>
                    <div class="text-2xl font-bold text-white"><?php echo round($stats['avg_anomaly_score'] ?? 0, 1); ?></div>
                </div>
                <div class="bg-dark-800 border border-white/10 rounded-xl p-6">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-zinc-400 text-sm">Score Máximo</span>
                        <i data-lucide="trending-up" class="w-5 h-5 text-red-400"></i>
                    </div>
                    <div class="text-2xl font-bold text-white"><?php echo round($stats['max_anomaly_score'] ?? 0, 1); ?></div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Busca de IP -->
            <div class="bg-dark-800 border border-white/10 rounded-xl p-6 mb-6">
                <h2 class="text-lg font-bold text-white mb-4">Verificar IP</h2>
                <form method="GET" class="flex gap-4">
                    <input 
                        type="text" 
                        name="ip" 
                        value="<?php echo htmlspecialchars($searchIP); ?>"
                        placeholder="Digite um endereço IP para analisar..."
                        class="flex-1 px-4 py-3 bg-dark-700 border border-white/10 rounded-lg text-white placeholder-zinc-500 focus:outline-none focus:border-white/30 transition-colors font-mono"
                    >
                    <select name="window" class="px-4 py-3 bg-dark-700 border border-white/10 rounded-lg text-white focus:outline-none focus:border-white/30">
                        <option value="3600" <?php echo $timeWindow === 3600 ? 'selected' : ''; ?>>Última hora</option>
                        <option value="7200" <?php echo $timeWindow === 7200 ? 'selected' : ''; ?>>Últimas 2 horas</option>
                        <option value="21600" <?php echo $timeWindow === 21600 ? 'selected' : ''; ?>>Últimas 6 horas</option>
                        <option value="86400" <?php echo $timeWindow === 86400 ? 'selected' : ''; ?>>Últimas 24 horas</option>
                    </select>
                    <button type="submit" class="px-6 py-3 bg-white text-black rounded-lg font-semibold hover:bg-zinc-200 transition-colors">
                        <i data-lucide="search" class="w-4 h-4 inline-block mr-2"></i>
                        Analisar
                    </button>
                </form>
                
                <?php if ($ipAnomalies): 
                    $score = (int)($ipAnomalies['anomaly_score'] ?? 0);
                    $scoreColor = $score >= 70 ? 'text-red-400' : ($score >= 40 ? 'text-yellow-400' : 'text-green-400');
                    $scoreBg = $score >= 70 ? 'bg-red-500/10 border-red-500/30' : ($score >= 40 ? 'bg-yellow-500/10 border-yellow-500/30' : 'bg-green-500/10 border-green-500/30');
                ?>
                <div class="mt-6 pt-6 border-t border-white/10">
                    <h3 class="text-lg font-bold text-white mb-4">Análise de Anomalias: <?php echo htmlspecialchars($searchIP); ?></h3>
                    
                    <div class="mb-6 <?php echo $scoreBg; ?> border rounded-lg p-6">
                        <div class="flex items-center justify-between mb-2">
                            <div>
                                <div class="text-zinc-400 text-sm mb-1">Score de Anomalia</div>
                                <div class="text-4xl font-bold <?php echo $scoreColor; ?>"><?php echo $score; ?>/100</div>
                            </div>
                            <div class="text-right">
                                <div class="text-zinc-400 text-sm mb-1">Status</div>
                                <div class="text-xl font-bold <?php echo $ipAnomalies['is_anomaly'] ? 'text-red-400' : 'text-green-400'; ?>">
                                    <?php echo $ipAnomalies['is_anomaly'] ? 'Anomalia Detectada' : 'Normal'; ?>
                                </div>
                            </div>
                        </div>
                        <div class="w-full bg-dark-700 rounded-full h-3 mt-4">
                            <div class="<?php echo $score >= 70 ? 'bg-red-500' : ($score >= 40 ? 'bg-yellow-500' : 'bg-green-500'); ?> h-3 rounded-full transition-all duration-500" style="width: <?php echo $score; ?>%"></div>
                        </div>
                    </div>
                    
                    <?php if (!empty($ipAnomalies['anomalies']) || !empty($patternAnomalies)): ?>
                    <div class="space-y-4">
                        <h4 class="text-md font-semibold text-white">Anomalias Detectadas:</h4>
                        <?php foreach (array_merge($ipAnomalies['anomalies'] ?? [], $patternAnomalies) as $anomaly): 
                            $severityBadge = $anomaly['severity'] === 'high' ? 'bg-red-500/20 text-red-400 border-red-500/30' : 
                                            ($anomaly['severity'] === 'medium' ? 'bg-orange-500/20 text-orange-400 border-orange-500/30' : 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30');
                        ?>
                        <div class="p-4 rounded-lg bg-dark-700 border border-white/5">
                            <div class="flex items-start justify-between mb-2">
                                <div class="flex-1">
                                    <div class="text-sm font-medium text-white mb-1 capitalize">
                                        <?php echo str_replace('_', ' ', $anomaly['type'] ?? 'anomalia'); ?>
                                    </div>
                                    <div class="text-xs text-zinc-400">
                                        <?php echo htmlspecialchars($anomaly['description'] ?? ''); ?>
                                    </div>
                                    <?php if (isset($anomaly['z_score'])): ?>
                                    <div class="text-xs text-zinc-500 mt-1">Z-Score: <?php echo $anomaly['z_score']; ?></div>
                                    <?php endif; ?>
                                    <?php if (isset($anomaly['count'])): ?>
                                    <div class="text-xs text-zinc-500 mt-1">Ocorrências: <?php echo $anomaly['count']; ?></div>
                                    <?php endif; ?>
                                </div>
                                <span class="px-2 py-1 text-xs font-bold border rounded <?php echo $severityBadge; ?> ml-3">
                                    <?php echo ucfirst($anomaly['severity'] ?? 'medium'); ?>
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-8">
                        <i data-lucide="check-circle" class="w-16 h-16 mx-auto mb-3 text-green-400 opacity-50"></i>
                        <p class="text-zinc-400">Nenhuma anomalia detectada para este IP</p>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Anomalias Globais -->
                <div class="lg:col-span-2 bg-dark-800 border border-white/10 rounded-xl p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-xl font-bold text-white">Anomalias Detectadas</h2>
                        <span class="text-sm text-zinc-400"><?php echo count($globalAnomalies); ?> anomalia(s)</span>
                    </div>
                    <!-- Skeleton para lista de anomalias -->
                    <div x-show="loading" class="space-y-3">
                        <?php for ($i = 0; $i < 5; $i++): ?>
                        <div class="skeleton-card">
                            <div class="flex items-start justify-between mb-2">
                                <div class="flex-1">
                                    <div class="skeleton-line" style="width: 150px; height: 16px; margin-bottom: 8px;"></div>
                                    <div class="skeleton-line" style="width: 200px; height: 12px; margin-bottom: 4px;"></div>
                                    <div class="skeleton-line" style="width: 120px; height: 12px;"></div>
                                </div>
                                <div class="skeleton-line" style="width: 40px; height: 24px;"></div>
                            </div>
                        </div>
                        <?php endfor; ?>
                    </div>
                    <div x-show="!loading" class="space-y-3">
                        <?php if (empty($globalAnomalies)): ?>
                        <div class="text-center py-12">
                            <i data-lucide="check-circle" class="w-16 h-16 mx-auto mb-3 text-green-400 opacity-50"></i>
                            <p class="text-zinc-400 font-medium mb-1">Nenhuma anomalia detectada</p>
                            <p class="text-zinc-500 text-sm">O comportamento está dentro dos padrões normais</p>
                        </div>
                        <?php else: ?>
                        <?php foreach ($globalAnomalies as $anomaly): 
                            $score = (int)($anomaly['anomaly_score'] ?? 0);
                            $scoreColor = $score >= 70 ? 'text-red-400' : ($score >= 40 ? 'text-yellow-400' : 'text-zinc-400');
                        ?>
                        <div class="p-4 rounded-lg bg-dark-700 border border-white/5 hover:border-white/10 transition-colors cursor-pointer" onclick="window.location.href='?ip=<?php echo urlencode($anomaly['ip_address']); ?>'">
                            <div class="flex items-start justify-between mb-2">
                                <div class="flex-1">
                                    <div class="font-mono text-sm text-white mb-1"><?php echo htmlspecialchars($anomaly['ip_address']); ?></div>
                                    <div class="text-xs text-zinc-400">
                                        <?php echo count($anomaly['anomalies'] ?? []); ?> tipo(s) de anomalia detectado(s)
                                    </div>
                                    <?php if (!empty($anomaly['anomalies'])): ?>
                                    <div class="mt-2 space-y-1">
                                        <?php foreach (array_slice($anomaly['anomalies'], 0, 2) as $anom): ?>
                                        <div class="text-xs text-zinc-500">
                                            • <?php echo htmlspecialchars($anom['description'] ?? ''); ?>
                                        </div>
                                        <?php endforeach; ?>
                                        <?php if (count($anomaly['anomalies']) > 2): ?>
                                        <div class="text-xs text-zinc-600">... e mais <?php echo count($anomaly['anomalies']) - 2; ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="text-right ml-4">
                                    <div class="text-xl font-bold <?php echo $scoreColor; ?>"><?php echo $score; ?></div>
                                    <div class="text-xs text-zinc-500">score</div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Skeleton para Tipos de Anomalias -->
                    <div x-show="loading" class="bg-dark-800 border border-white/10 rounded-xl p-6">
                        <div class="skeleton-line" style="width: 180px; height: 20px; margin-bottom: 16px;"></div>
                        <div class="space-y-3">
                            <?php for ($i = 0; $i < 4; $i++): ?>
                            <div class="flex items-center justify-between">
                                <div class="skeleton-line" style="width: 120px; height: 16px;"></div>
                                <div class="skeleton-line" style="width: 30px; height: 20px;"></div>
                            </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                    
                    <!-- Tipos de Anomalias -->
                    <?php if (!empty($anomalyTypes)): ?>
                    <div x-show="!loading" class="bg-dark-800 border border-white/10 rounded-xl p-6">
                        <h3 class="text-lg font-bold text-white mb-4">Tipos de Anomalias (7 dias)</h3>
                        <div class="space-y-3">
                            <?php foreach ($anomalyTypes as $type => $count): ?>
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <div class="text-sm text-white font-medium capitalize">
                                        <?php echo str_replace('_', ' ', $type); ?>
                                    </div>
                                </div>
                                <div class="text-lg font-bold text-zinc-300"><?php echo $count; ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Skeleton para Anomalias Recentes -->
                    <div x-show="loading" class="bg-dark-800 border border-white/10 rounded-xl p-6">
                        <div class="skeleton-line" style="width: 160px; height: 20px; margin-bottom: 16px;"></div>
                        <div class="space-y-3">
                            <?php for ($i = 0; $i < 5; $i++): ?>
                            <div class="p-3 rounded-lg bg-dark-700 border border-white/5">
                                <div class="flex items-center justify-between mb-1">
                                    <div class="skeleton-line" style="width: 120px; height: 14px;"></div>
                                    <div class="skeleton-line" style="width: 30px; height: 16px;"></div>
                                </div>
                                <div class="skeleton-line" style="width: 80px; height: 12px;"></div>
                            </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                    
                    <!-- Anomalias Recentes -->
                    <?php if (!empty($recentAnomalies)): ?>
                    <div x-show="!loading" class="bg-dark-800 border border-white/10 rounded-xl p-6">
                        <h3 class="text-lg font-bold text-white mb-4">Anomalias Recentes (24h)</h3>
                        <div class="space-y-3">
                            <?php foreach (array_slice($recentAnomalies, 0, 5) as $anomaly): ?>
                            <div class="p-3 rounded-lg bg-dark-700 border border-white/5">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="font-mono text-sm text-white"><?php echo htmlspecialchars($anomaly['ip_address']); ?></span>
                                    <span class="px-2 py-0.5 text-xs font-bold <?php echo $anomaly['anomaly_score'] >= 70 ? 'bg-red-500/20 text-red-400' : 'bg-yellow-500/20 text-yellow-400'; ?> rounded">
                                        <?php echo round($anomaly['anomaly_score']); ?>
                                    </span>
                                </div>
                                <div class="text-xs text-zinc-400">
                                    <?php echo count($anomaly['anomalies'] ?? []); ?> anomalia(s)
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    </div>
    <script>lucide.createIcons();    </script>
    
    <!-- Floating Flame Component -->
    <?php include __DIR__ . '/includes/floating-flame.php'; ?>
</body>
</html>

