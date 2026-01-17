<?php
/**
 * SafeNode - Gerenciamento de Verificação Humana
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
require_once __DIR__ . '/includes/HVAPIKeyManager.php';

$db = getSafeNodeDatabase();
$userId = $_SESSION['safenode_user_id'] ?? null;

if (!$userId) {
    header('Location: login.php');
    exit;
}

$pageTitle = 'Verificação Humana';
$currentPage = 'human-verification';
$message = '';
$messageType = '';

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'generate') {
        $name = $_POST['name'] ?? 'Verificação Humana';
        $allowedDomains = trim($_POST['allowed_domains'] ?? '');
        $rateLimit = !empty($_POST['rate_limit']) ? (int)$_POST['rate_limit'] : 60;
        $maxTokenAge = !empty($_POST['max_token_age']) ? (int)$_POST['max_token_age'] : 3600;
        
        // Validar rate limit (mínimo 10, máximo 1000)
        $rateLimit = max(10, min(1000, $rateLimit));
        
        // Validar max token age (mínimo 300s = 5min, máximo 86400s = 24h)
        $maxTokenAge = max(300, min(86400, $maxTokenAge));
        
        $result = HVAPIKeyManager::generateKey($userId, $name, $allowedDomains ?: null, $rateLimit, $maxTokenAge);
        if ($result) {
            $message = 'API key gerada com sucesso!';
            $messageType = 'success';
        } else {
            $message = 'Erro ao gerar API key.';
            $messageType = 'error';
        }
    } elseif ($action === 'deactivate') {
        $keyId = (int)($_POST['key_id'] ?? 0);
        if (HVAPIKeyManager::deactivateKey($keyId, $userId)) {
            $message = 'API key desativada com sucesso.';
            $messageType = 'success';
        } else {
            $message = 'Erro ao desativar API key.';
            $messageType = 'error';
        }
    } elseif ($action === 'activate') {
        $keyId = (int)($_POST['key_id'] ?? 0);
        if (HVAPIKeyManager::activateKey($keyId, $userId)) {
            $message = 'API key ativada com sucesso.';
            $messageType = 'success';
        } else {
            $message = 'Erro ao ativar API key.';
            $messageType = 'error';
        }
    } elseif ($action === 'delete') {
        $keyId = (int)($_POST['key_id'] ?? 0);
        if (HVAPIKeyManager::deleteKey($keyId, $userId)) {
            $message = 'API key deletada com sucesso.';
            $messageType = 'success';
        } else {
            $message = 'Erro ao deletar API key.';
            $messageType = 'error';
        }
    }
}

// Obter API keys do usuário
$apiKeys = HVAPIKeyManager::getUserKeys($userId);

// Obter URL base (detecta automaticamente se é produção ou desenvolvimento)
$baseUrl = getSafeNodeBaseUrl();

// Função helper para gerar URLs
if (!function_exists('getSafeNodeUrl')) {
    function getSafeNodeUrl($route, $siteId = null) {
        $pagePath = strpos($route, '.php') !== false ? $route : $route . '.php';
        return $pagePath;
    }
}

?>
<!DOCTYPE html>
<html lang="pt-BR" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> | SafeNode</title>
    <link rel="icon" type="image/png" href="assets/img/logos (6).png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="includes/theme-styles.css">
    <script src="includes/theme-toggle.js"></script>
    
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
        
        body {
            background-color: var(--bg-primary);
            color: var(--text-primary);
            font-family: 'Inter', sans-serif;
            font-size: 0.92em;
            -webkit-font-smoothing: antialiased;
        }
        
        :root:not(.dark) body {
            color: #1a1a1a;
        }
        
        /* Melhorar contraste de textos no modo claro */
        :root:not(.dark) p,
        :root:not(.dark) span,
        :root:not(.dark) div {
            color: inherit;
        }
        
        /* Garantir que labels e textos descritivos tenham bom contraste */
        :root:not(.dark) label {
            color: #374151 !important;
            font-weight: 600;
        }
        
        :root:not(.dark) .text-gray-600 {
            color: #4b5563 !important;
        }
        
        :root:not(.dark) .text-gray-500 {
            color: #6b7280 !important;
        }
        
        /* Forçar cores corretas em títulos e textos importantes no modo claro */
        :root:not(.dark) h1,
        :root:not(.dark) h2,
        :root:not(.dark) h3,
        :root:not(.dark) h4 {
            color: #111827 !important;
        }
        
        :root:not(.dark) .text-gray-900 {
            color: #111827 !important;
        }
        
        :root:not(.dark) .text-gray-800 {
            color: #1f2937 !important;
        }
        
        :root:not(.dark) .text-gray-700 {
            color: #374151 !important;
        }
        
        .glass {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--border-subtle);
        }
        
        /* Garantir que textos dentro de .glass sejam visíveis no modo claro */
        :root:not(.dark) .glass {
            color: #1a1a1a;
        }
        
        :root:not(.dark) .glass h1,
        :root:not(.dark) .glass h2,
        :root:not(.dark) .glass h3,
        :root:not(.dark) .glass h4,
        :root:not(.dark) .glass h5,
        :root:not(.dark) .glass h6 {
            color: #111827 !important;
        }
        
        :root:not(.dark) .glass p,
        :root:not(.dark) .glass span,
        :root:not(.dark) .glass div,
        :root:not(.dark) .glass label {
            color: #374151 !important;
        }
        
        /* Sobrescrever classes Tailwind dentro de .glass no modo claro */
        :root:not(.dark) .glass .text-gray-900,
        :root:not(.dark) .glass .text-white {
            color: #111827 !important;
        }
        
        :root:not(.dark) .glass .text-gray-800 {
            color: #1f2937 !important;
        }
        
        :root:not(.dark) .glass .text-gray-700 {
            color: #374151 !important;
        }
        
        :root:not(.dark) .glass .text-gray-600 {
            color: #4b5563 !important;
        }
        
        :root:not(.dark) .glass .text-gray-500 {
            color: #6b7280 !important;
        }
        
        .code-block {
            background: var(--bg-card);
            border: 1px solid var(--border-subtle);
            border-radius: 8px;
            padding: 16px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 13px;
            overflow-x: auto;
            color: var(--text-primary);
        }
        
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
            position: relative;
            overflow: hidden;
            text-decoration: none;
        }
        
        .nav-item::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, var(--accent-glow) 0%, transparent 100%);
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .nav-item:hover {
            color: var(--text-primary);
            background: var(--bg-hover);
        }
        
        :root:not(.dark) .nav-item:hover {
            background: #f1f3f5;
            color: #000000;
        }
        
        .nav-item:hover::before {
            opacity: 0.5;
        }
        
        :root:not(.dark) .nav-item:hover::before {
            opacity: 0.3;
        }
        
        .nav-item.active {
            color: var(--accent);
            background: linear-gradient(90deg, var(--gradient-overlay) 0%, transparent 100%);
        }
        
        :root:not(.dark) .nav-item.active {
            background: linear-gradient(90deg, rgba(0, 0, 0, 0.08) 0%, transparent 100%);
            color: #000000;
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
            background: var(--accent);
            border-radius: 0 4px 4px 0;
            box-shadow: 0 0 20px var(--accent-glow);
        }
        
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
            
            div[x-show*="sidebarOpen"].fixed {
                position: fixed !important;
                z-index: 60 !important;
                pointer-events: auto !important;
            }
        }
    </style>
</head>
<body class="h-full" x-data="{ sidebarOpen: false, sidebarCollapsed: false }">
    <div class="flex h-full">
        <?php
    // Código da Sidebar
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $useProtectedUrls = false;
    if (isset($_SESSION['safenode_logged_in']) && $_SESSION['safenode_logged_in'] === true) {
        require_once __DIR__ . '/includes/Router.php';
        SafeNodeRouter::init();
        $useProtectedUrls = true;
    }

    if (!function_exists('getSafeNodeUrl')) {
        function getSafeNodeUrl($route, $siteId = null) {
            $pagePath = strpos($route, '.php') !== false ? $route : $route . '.php';
            return $pagePath;
        }
    }

    $currentPage = basename($_SERVER['PHP_SELF'], '.php');
    if (isset($_GET['route'])) {
        $currentPage = 'dashboard';
    }
    ?>
        <!-- Sidebar Desktop -->
        <aside class="hidden lg:flex flex-col sidebar h-full flex-shrink-0 transition-all duration-300" 
               :class="sidebarCollapsed ? 'w-20' : 'w-72'">
            <div class="p-4 border-b border-gray-200 dark:border-white/5 flex-shrink-0 relative">
                <div class="flex items-center" :class="sidebarCollapsed ? 'justify-center flex-col gap-3' : 'justify-between'">
                    <div class="flex items-center gap-3" :class="sidebarCollapsed ? 'justify-center' : ''">
                        <div class="relative">
                            <img src="assets/img/safe-claro.png" alt="SafeNode Logo" class="w-8 h-8 object-contain flex-shrink-0 dark:hidden">
                            <img src="assets/img/logos (6).png" alt="SafeNode Logo" class="w-8 h-8 object-contain flex-shrink-0 hidden dark:block">
                        </div>
                        <div x-show="!sidebarCollapsed" 
                             x-transition:enter="transition ease-out duration-200" 
                             x-transition:enter-start="opacity-0 -translate-x-2" 
                             x-transition:enter-end="opacity-100 translate-x-0" 
                             x-transition:leave="transition ease-in duration-150" 
                             x-transition:leave-start="opacity-100 translate-x-0" 
                             x-transition:leave-end="opacity-0 -translate-x-2" 
                             class="overflow-hidden whitespace-nowrap">
                            <h1 class="font-bold text-gray-900 dark:text-white text-xl tracking-tight">SafeNode</h1>
                            <p class="text-xs text-gray-500 dark:text-zinc-500 font-medium">Security Platform</p>
                        </div>
                    </div>
                    <button @click="sidebarCollapsed = !sidebarCollapsed; setTimeout(() => lucide.createIcons(), 50)" 
                            class="text-zinc-600 hover:text-zinc-400 transition-colors flex-shrink-0" 
                            :class="sidebarCollapsed ? 'mt-2' : ''">
                        <i :data-lucide="sidebarCollapsed ? 'chevrons-right' : 'chevrons-left'" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>
            
            <nav class="flex-1 overflow-y-auto p-4 space-y-1" style="scrollbar-width: thin; scrollbar-color: var(--scrollbar-thumb) transparent;">
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
                   :title="sidebarCollapsed ? 'Sites' : ''">
                    <i data-lucide="globe" class="w-5 h-5 flex-shrink-0"></i>
                    <span x-show="!sidebarCollapsed" 
                          x-transition:enter="transition ease-out duration-200" 
                          x-transition:enter-start="opacity-0 -translate-x-2" 
                          x-transition:enter-end="opacity-100 translate-x-0" 
                          x-transition:leave="transition ease-in duration-150" 
                          x-transition:leave-start="opacity-100 translate-x-0" 
                          x-transition:leave-end="opacity-0 -translate-x-2" 
                          class="font-medium whitespace-nowrap">Sites</span>
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
                <a href="<?php echo getSafeNodeUrl('logs'); ?>" 
                   class="nav-item <?php echo $currentPage == 'logs' ? 'active' : ''; ?>" 
                   :class="sidebarCollapsed ? 'justify-center px-2' : ''" 
                   :title="sidebarCollapsed ? 'Logs' : ''">
                    <i data-lucide="file-text" class="w-5 h-5 flex-shrink-0"></i>
                    <span x-show="!sidebarCollapsed" 
                          x-transition:enter="transition ease-out duration-200" 
                          x-transition:enter-start="opacity-0 -translate-x-2" 
                          x-transition:enter-end="opacity-100 translate-x-0" 
                          x-transition:leave="transition ease-in duration-150" 
                          x-transition:leave-start="opacity-100 translate-x-0" 
                          x-transition:leave-end="opacity-0 -translate-x-2" 
                          class="font-medium whitespace-nowrap">Logs</span>
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
                
                <div class="pt-4 mt-4 border-t border-gray-200 dark:border-white/5">
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
        </aside>
        
        <!-- Sidebar Mobile -->
        <aside x-show="sidebarOpen" 
               @click.away="sidebarOpen = false"
               x-transition:enter="transition-transform duration-300 ease-out"
               x-transition:enter-start="-translate-x-full"
               x-transition:enter-end="translate-x-0"
               x-transition:leave="transition-transform duration-300 ease-in"
               x-transition:leave-start="translate-x-0"
               x-transition:leave-end="-translate-x-full"
               class="lg:hidden fixed inset-y-0 left-0 z-50 w-72 sidebar overflow-y-auto">
            <div class="p-4 border-b border-gray-200 dark:border-white/5 flex-shrink-0 relative">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <img src="assets/img/safe-claro.png" alt="SafeNode Logo" class="w-8 h-8 object-contain flex-shrink-0 dark:hidden">
                        <img src="assets/img/logos (6).png" alt="SafeNode Logo" class="w-8 h-8 object-contain flex-shrink-0 hidden dark:block">
                        <div class="overflow-hidden whitespace-nowrap">
                            <h1 class="font-bold text-gray-900 dark:text-white text-xl tracking-tight">SafeNode</h1>
                            <p class="text-xs text-gray-500 dark:text-zinc-500 font-medium">Security Platform</p>
                        </div>
                    </div>
                    <button @click="sidebarOpen = false; $dispatch('safenode-sidebar-toggle', { isOpen: false })" class="text-gray-500 dark:text-zinc-600 hover:text-gray-700 dark:hover:text-zinc-400 transition-colors flex-shrink-0">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>
            
            <nav class="flex-1 overflow-y-auto p-4 space-y-1">
                <a href="<?php echo getSafeNodeUrl('dashboard'); ?>" class="nav-item <?php echo $currentPage == 'dashboard' ? 'active' : ''; ?>" @click="sidebarOpen = false">
                    <i data-lucide="layout-dashboard" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="font-medium whitespace-nowrap">Dashboard</span>
                </a>
                <a href="<?php echo getSafeNodeUrl('sites'); ?>" class="nav-item <?php echo $currentPage == 'sites' ? 'active' : ''; ?>" @click="sidebarOpen = false">
                    <i data-lucide="globe" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="font-medium whitespace-nowrap">Sites</span>
                </a>
                <a href="<?php echo getSafeNodeUrl('human-verification'); ?>" class="nav-item <?php echo $currentPage == 'human-verification' ? 'active' : ''; ?>" @click="sidebarOpen = false">
                    <i data-lucide="shield-check" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="font-medium whitespace-nowrap">Verificação Humana</span>
                </a>
                <a href="<?php echo getSafeNodeUrl('logs'); ?>" class="nav-item <?php echo $currentPage == 'logs' ? 'active' : ''; ?>" @click="sidebarOpen = false">
                    <i data-lucide="file-text" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="font-medium whitespace-nowrap">Logs</span>
                </a>
                <a href="<?php echo getSafeNodeUrl('suspicious-ips'); ?>" class="nav-item <?php echo $currentPage == 'suspicious-ips' ? 'active' : ''; ?>" @click="sidebarOpen = false">
                    <i data-lucide="alert-octagon" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="font-medium whitespace-nowrap">IPs Suspeitos</span>
                </a>
                
                <p class="text-xs font-semibold text-gray-600 dark:text-zinc-500 uppercase tracking-wider mb-3 px-3 whitespace-nowrap mt-4 pt-4 border-t border-gray-200 dark:border-white/5">Segurança</p>
                
                <a href="<?php echo getSafeNodeUrl('threat-analysis'); ?>" class="nav-item <?php echo $currentPage == 'threat-analysis' ? 'active' : ''; ?>" @click="sidebarOpen = false">
                    <i data-lucide="shield-alert" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="font-medium whitespace-nowrap">Ameaças</span>
                </a>
                <a href="<?php echo getSafeNodeUrl('behavior-analysis'); ?>" class="nav-item <?php echo $currentPage == 'behavior-analysis' ? 'active' : ''; ?>" @click="sidebarOpen = false">
                    <i data-lucide="activity" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="font-medium whitespace-nowrap">Comportamento</span>
                </a>
                <a href="<?php echo getSafeNodeUrl('security-recommendations'); ?>" class="nav-item <?php echo $currentPage == 'security-recommendations' ? 'active' : ''; ?>" @click="sidebarOpen = false">
                    <i data-lucide="lightbulb" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="font-medium whitespace-nowrap">Recomendações</span>
                </a>
                
                <div class="pt-4 mt-4 border-t border-gray-200 dark:border-white/5">
                    <a href="<?php echo getSafeNodeUrl('help'); ?>" class="nav-item <?php echo $currentPage == 'help' ? 'active' : ''; ?>" @click="sidebarOpen = false">
                        <i data-lucide="life-buoy" class="w-5 h-5 flex-shrink-0"></i>
                        <span class="font-medium whitespace-nowrap">Ajuda</span>
                    </a>
                </div>
            </nav>
        </aside>
        
        <!-- Overlay Mobile -->
        <div x-show="sidebarOpen" 
             @click="sidebarOpen = false"
             x-transition:enter="transition-opacity duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="lg:hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-40"
             style="display: none;"></div>
        
        <!-- Main Content -->
        <main class="flex-1 flex flex-col h-full overflow-hidden bg-white dark:bg-dark-950">
            <!-- Header -->
            <header class="min-h-20 bg-white/80 dark:bg-dark-900/50 backdrop-blur-xl border-b border-gray-200 dark:border-white/5 px-4 md:px-8 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 flex-shrink-0">
                <div class="flex items-center gap-3 md:gap-6 min-w-0 flex-1">
                    <button data-sidebar-toggle @click="sidebarOpen = !sidebarOpen" class="lg:hidden text-gray-500 dark:text-zinc-400 hover:text-gray-900 dark:hover:text-white transition-colors flex-shrink-0">
                        <i data-lucide="menu" class="w-6 h-6"></i>
                    </button>
                    <div class="min-w-0 flex-1">
                        <h2 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white tracking-tight break-words"><?php echo $pageTitle; ?></h2>
                        <p class="text-xs sm:text-sm text-gray-700 dark:text-zinc-500 mt-0.5 break-words">Gerencie suas API keys e integre verificação humana</p>
                    </div>
                </div>
            </header>

            <!-- Scrollable Content -->
            <div class="flex-1 overflow-y-auto p-4 md:p-8">
                <div class="max-w-6xl mx-auto space-y-6">
                    <!-- Mensagem -->
                    <?php if ($message): ?>
                    <div class="glass rounded-xl p-4 <?php echo $messageType === 'success' ? 'border-green-500/30 bg-green-500/10' : 'border-red-500/30 bg-red-500/10'; ?>">
                        <p class="text-gray-900 dark:text-white"><?php echo htmlspecialchars($message); ?></p>
                    </div>
                    <?php endif; ?>

                    <!-- Gerar Nova API Key -->
                    <div class="glass rounded-2xl p-4 md:p-6">
                        <h2 class="text-lg md:text-xl font-semibold text-gray-900 dark:text-white mb-4">Gerar Nova API Key</h2>
                        <form method="POST" class="space-y-4">
                            <input type="hidden" name="action" value="generate">
                            
                            <div>
                                <label class="block text-sm font-semibold text-gray-800 dark:text-zinc-400 mb-2">Nome da API Key</label>
                                <input type="text" name="name" placeholder="Verificação Humana" value="Verificação Humana" 
                                    class="w-full px-4 py-2 bg-gray-50 dark:bg-white/5 border border-gray-300 dark:border-white/10 rounded-xl text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-zinc-500 focus:border-gray-400 dark:focus:border-white/30 focus:outline-none focus:ring-2 focus:ring-gray-200 dark:focus:ring-white/10">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-semibold text-gray-800 dark:text-zinc-400 mb-2">Domínios Permitidos (opcional)</label>
                                <input type="text" name="allowed_domains" placeholder="exemplo.com, www.exemplo.com" 
                                    class="w-full px-4 py-2 bg-gray-50 dark:bg-white/5 border border-gray-300 dark:border-white/10 rounded-xl text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-zinc-500 focus:border-gray-400 dark:focus:border-white/30 focus:outline-none focus:ring-2 focus:ring-gray-200 dark:focus:ring-white/10">
                                <p class="text-xs text-gray-700 dark:text-zinc-500 mt-1">Separe múltiplos domínios por vírgula. Deixe vazio para permitir qualquer domínio.</p>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-800 dark:text-zinc-400 mb-2">Rate Limit (req/min)</label>
                                    <input type="number" name="rate_limit" value="60" min="10" max="1000" 
                                        class="w-full px-4 py-2 bg-gray-50 dark:bg-white/5 border border-gray-300 dark:border-white/10 rounded-xl text-gray-900 dark:text-white focus:border-gray-400 dark:focus:border-white/30 focus:outline-none focus:ring-2 focus:ring-gray-200 dark:focus:ring-white/10">
                                    <p class="text-xs text-gray-700 dark:text-zinc-500 mt-1">Máximo de requisições por minuto (10-1000)</p>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-800 dark:text-zinc-400 mb-2">Idade Máxima do Token (segundos)</label>
                                    <input type="number" name="max_token_age" value="3600" min="300" max="86400" 
                                        class="w-full px-4 py-2 bg-gray-50 dark:bg-white/5 border border-gray-300 dark:border-white/10 rounded-xl text-gray-900 dark:text-white focus:border-gray-400 dark:focus:border-white/30 focus:outline-none focus:ring-2 focus:ring-gray-200 dark:focus:ring-white/10">
                                    <p class="text-xs text-gray-700 dark:text-zinc-500 mt-1">Tempo de expiração do token (300-86400s)</p>
                                </div>
                            </div>
                            
                            <button type="submit" class="w-full md:w-auto px-6 py-2.5 bg-gray-900 dark:bg-white text-white dark:text-black rounded-xl font-semibold hover:bg-gray-800 dark:hover:bg-white/90 transition-colors">
                                Gerar API Key
                            </button>
                        </form>
                    </div>

                    <!-- Lista de API Keys -->
                    <div class="glass rounded-2xl p-4 md:p-6">
                        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-4 md:mb-6">
                            <h2 class="text-lg md:text-xl font-semibold text-gray-900 dark:text-white">Suas API Keys</h2>
                            <a href="api-monitor.php" class="w-full sm:w-auto px-4 py-2 bg-gray-100 dark:bg-white/10 text-gray-900 dark:text-white rounded-lg hover:bg-gray-200 dark:hover:bg-white/20 transition-colors text-sm flex items-center justify-center gap-2 whitespace-nowrap">
                                <i data-lucide="bar-chart-2" class="w-4 h-4"></i>
                                Monitoramento
                            </a>
                        </div>
                        
                        <?php if (empty($apiKeys)): ?>
                            <p class="text-gray-700 dark:text-zinc-500 text-center py-8">Nenhuma API key criada ainda. Gere uma acima para começar.</p>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($apiKeys as $key): ?>
                                <div class="bg-gray-50 dark:bg-dark-900/50 rounded-xl p-4 md:p-6 border border-gray-200 dark:border-white/5">
                                    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4 mb-4">
                                        <div class="flex-1 min-w-0">
                                            <div class="flex flex-wrap items-center gap-2 md:gap-3 mb-2">
                                                <h3 class="text-gray-900 dark:text-white font-semibold text-base md:text-lg break-words"><?php echo htmlspecialchars($key['name']); ?></h3>
                                                <?php if ($key['is_active']): ?>
                                                    <span class="px-2 py-1 bg-green-500/20 text-green-600 dark:text-green-400 text-xs rounded-lg whitespace-nowrap">Ativa</span>
                                                <?php else: ?>
                                                    <span class="px-2 py-1 bg-gray-500/20 text-gray-600 dark:text-zinc-400 text-xs rounded-lg whitespace-nowrap">Inativa</span>
                                                <?php endif; ?>
                                            </div>
                                            <p class="text-sm text-gray-700 dark:text-zinc-500 mb-1">
                                                Criada em: <?php 
                                                    $date = new DateTime($key['created_at'], new DateTimeZone('UTC'));
                                                    $date->setTimezone(new DateTimeZone('America/Sao_Paulo'));
                                                    echo $date->format('d/m/Y H:i');
                                                ?>
                                            </p>
                                            <?php if ($key['last_used_at']): ?>
                                                <p class="text-sm text-gray-700 dark:text-zinc-500 mb-1">
                                                    Último uso: <?php 
                                                        $date = new DateTime($key['last_used_at'], new DateTimeZone('UTC'));
                                                        $date->setTimezone(new DateTimeZone('America/Sao_Paulo'));
                                                        echo $date->format('d/m/Y H:i');
                                                    ?>
                                                </p>
                                            <?php endif; ?>
                                            <p class="text-sm text-gray-700 dark:text-zinc-500 mb-1">
                                                Usos: <?php echo number_format($key['usage_count']); ?>
                                            </p>
                                            <?php if (!empty($key['allowed_domains'])): ?>
                                                <p class="text-sm text-gray-700 dark:text-zinc-400 mb-1 break-words">
                                                    <i data-lucide="globe" class="w-3 h-3 inline"></i> 
                                                    Domínios: <?php echo htmlspecialchars($key['allowed_domains']); ?>
                                                </p>
                                            <?php endif; ?>
                                            <p class="text-sm text-gray-700 dark:text-zinc-400 break-words">
                                                <i data-lucide="gauge" class="w-3 h-3 inline"></i> 
                                                Rate Limit: <?php echo (int)($key['rate_limit_per_minute'] ?? 60); ?> req/min • 
                                                Token expira em: <?php echo (int)($key['max_token_age'] ?? 3600); ?>s
                                            </p>
                                        </div>
                                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 md:ml-4 md:flex-shrink-0">
                                            <a href="api-monitor.php?key_id=<?php echo $key['id']; ?>" class="px-4 py-2 bg-blue-600/20 text-blue-600 dark:text-blue-400 rounded-lg hover:bg-blue-600/30 transition-colors text-sm flex items-center justify-center gap-2 whitespace-nowrap">
                                                <i data-lucide="bar-chart-2" class="w-3 h-3"></i>
                                                Monitorar
                                            </a>
                                            <?php if ($key['is_active']): ?>
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="action" value="deactivate">
                                                    <input type="hidden" name="key_id" value="<?php echo $key['id']; ?>">
                                                    <button type="submit" class="w-full sm:w-auto px-4 py-2 bg-gray-200 dark:bg-zinc-800 text-gray-700 dark:text-zinc-300 rounded-lg hover:bg-gray-300 dark:hover:bg-zinc-700 transition-colors text-sm whitespace-nowrap">
                                                        Desativar
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="action" value="activate">
                                                    <input type="hidden" name="key_id" value="<?php echo $key['id']; ?>">
                                                    <button type="submit" class="w-full sm:w-auto px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm whitespace-nowrap">
                                                        Ativar
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            <form method="POST" class="inline" onsubmit="return confirm('Tem certeza que deseja deletar esta API key?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="key_id" value="<?php echo $key['id']; ?>">
                                                <button type="submit" class="w-full sm:w-auto px-4 py-2 bg-red-600/20 text-red-600 dark:text-red-400 rounded-lg hover:bg-red-600/30 transition-colors text-sm whitespace-nowrap">
                                                    Deletar
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    
                                    <!-- Código de Integração -->
                                    <?php if ($key['is_active']): ?>
                                    <div class="mt-4 pt-4 border-t border-gray-200 dark:border-white/5">
                                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Código de Integração</h4>
                                        <div class="code-block mb-3 overflow-x-auto">
                                            <?php 
                                            $embedCode = HVAPIKeyManager::generateEmbedCode($key['api_key'], $baseUrl);
                                            echo htmlspecialchars($embedCode);
                                            ?>
                                        </div>
                                        <button 
                                            onclick="copyCode(this)" 
                                            data-code="<?php echo htmlspecialchars($embedCode, ENT_QUOTES); ?>"
                                            class="w-full sm:w-auto px-4 py-2 bg-gray-100 dark:bg-white/10 text-gray-900 dark:text-white rounded-lg hover:bg-gray-200 dark:hover:bg-white/20 transition-colors text-sm"
                                        >
                                            Copiar Código
                                        </button>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        SafeNodeTheme.init();
        lucide.createIcons();
        
        function copyCode(button) {
            const code = button.getAttribute('data-code');
            navigator.clipboard.writeText(code).then(() => {
                const originalText = button.textContent;
                button.textContent = 'Copiado!';
                button.classList.add('bg-green-600/20', 'text-green-400');
                setTimeout(() => {
                    button.textContent = originalText;
                    button.classList.remove('bg-green-600/20', 'text-green-400');
                }, 2000);
            });
        }
    </script>
    
    <!-- Security Scripts -->
    <script src="includes/security-scripts.js"></script>
</body>
</html>
