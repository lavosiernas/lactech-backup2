<?php

session_start();

require_once __DIR__ . '/includes/SecurityHelpers.php';
SecurityHeaders::apply();

if (!isset($_SESSION['safenode_logged_in']) || $_SESSION['safenode_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/Settings.php';
require_once __DIR__ . '/includes/Alert.php';

$db = getSafeNodeDatabase();
$currentSiteId = $_SESSION['view_site_id'] ?? 0;
$selectedSite = null;
$dashboardFlash = $_SESSION['safenode_dashboard_message'] ?? '';
$dashboardFlashType = $_SESSION['safenode_dashboard_message_type'] ?? 'success';
unset($_SESSION['safenode_dashboard_message'], $_SESSION['safenode_dashboard_message_type']);

if ($db && $currentSiteId > 0) {
    try {
        $userId = $_SESSION['safenode_user_id'] ?? null;
        $stmt = $db->prepare("SELECT * FROM safenode_sites WHERE id = ? AND user_id = ?");
        $stmt->execute([$currentSiteId, $userId]);
        $selectedSite = $stmt->fetch();
    } catch (PDOException $e) {
        $selectedSite = null;
    }
}

if ($db && $currentSiteId > 0 && $selectedSite && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_under_attack'])) {
    $newLevel = $selectedSite['security_level'] === 'under_attack' ? 'high' : 'under_attack';
    try {
        $stmt = $db->prepare("UPDATE safenode_sites SET security_level = ? WHERE id = ?");
        $stmt->execute([$newLevel, $currentSiteId]);
        $_SESSION['safenode_dashboard_message'] = $newLevel === 'under_attack'
            ? 'Modo "Sob Ataque" ativado para este site.'
            : 'Modo "Sob Ataque" desativado.';
        $_SESSION['safenode_dashboard_message_type'] = $newLevel === 'under_attack' ? 'warning' : 'success';
    } catch (PDOException $e) {
        $_SESSION['safenode_dashboard_message'] = 'Não foi possível atualizar o modo de proteção.';
        $_SESSION['safenode_dashboard_message_type'] = 'error';
    }
    header('Location: dashboard.php');
    exit;
}

$hasSites = false;
if ($db) {
    try {
        $userId = $_SESSION['safenode_user_id'] ?? null;
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM safenode_sites WHERE is_active = 1 AND user_id = ?");
        $stmt->execute([$userId]);
        $sitesResult = $stmt->fetch();
        $hasSites = ($sitesResult['total'] ?? 0) > 0;
        } catch (PDOException $e) {
    $hasSites = false;
}
}
?>
<!DOCTYPE html>
<html lang="pt-BR" class="dark h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | SafeNode</title>
    <link rel="icon" type="image/png" href="assets/img/logos (6).png">
    <link rel="shortcut icon" type="image/png" href="assets/img/logos (6).png">
    <link rel="apple-touch-icon" href="assets/img/logos (6).png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
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
                        },
                        accent: {
                            DEFAULT: '#ffffff',
                            light: '#ffffff',
                            dark: '#ffffff',
                            glow: 'rgba(255, 255, 255, 0.15)',
                        }
                    }
                }
            }
        }
    </script>
    
    <!-- Redesign completo com estilo premium Figma/Framer -->
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
            --accent: #ffffff;
            --accent-glow: rgba(255, 255, 255, 0.2);
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
        }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { 
            background: rgba(255,255,255,0.1); 
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.15); }
        
        /* Glassmorphism Effect */
        .glass {
            background: rgba(10, 10, 10, 0.7);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--border-subtle);
        }
        
        .glass-light {
            background: rgba(255, 255, 255, 0.02);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
        
        /* Sidebar */
        .sidebar {
            background: linear-gradient(180deg, var(--bg-secondary) 0%, var(--bg-primary) 100%);
            border-right: 1px solid var(--border-subtle);
            position: relative;
        }
        
        .sidebar::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 1px;
            height: 100%;
            background: linear-gradient(180deg, transparent 0%, var(--accent-glow) 50%, transparent 100%);
            opacity: 0.5;
        }
        
        /* Navigation Item */
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
        }
        
        .nav-item:hover::before {
            opacity: 0.5;
        }
        
        .nav-item.active {
            color: var(--accent);
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
            background: var(--accent);
            border-radius: 0 4px 4px 0;
            box-shadow: 0 0 20px var(--accent-glow);
        }
        
        /* Stat Card */
        .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border-subtle);
            border-radius: 16px;
            padding: 16px;
            position: relative;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        @media (min-width: 640px) {
            .stat-card {
                padding: 24px;
            }
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent 0%, rgba(255,255,255,0.1) 50%, transparent 100%);
        }
        
        .stat-card:hover {
            border-color: var(--border-light);
            transform: translateY(-4px);
            box-shadow: 0 20px 40px -20px rgba(0,0,0,0.5), 0 0 60px -30px var(--accent-glow);
        }
        
        .stat-card .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            position: relative;
        }
        
        .stat-card .stat-icon::after {
            content: '';
            position: absolute;
            inset: -2px;
            border-radius: 16px;
            background: inherit;
            opacity: 0.3;
            filter: blur(10px);
        }
        
        /* Chart Card */
        .chart-card {
            background: var(--bg-card);
            border: 1px solid var(--border-subtle);
            border-radius: 20px;
            padding: 28px;
            position: relative;
            overflow: hidden;
        }
        
        .chart-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle at center, rgba(255, 255, 255, 0.03) 0%, transparent 50%);
            pointer-events: none;
        }
        
        /* Table */
        .table-card {
            background: var(--bg-card);
            border: 1px solid var(--border-subtle);
            border-radius: 20px;
            overflow: hidden;
        }
        
        .table-header {
            background: linear-gradient(180deg, var(--bg-tertiary) 0%, var(--bg-card) 100%);
            border-bottom: 1px solid var(--border-subtle);
        }
        
        .table-row {
            border-bottom: 1px solid var(--border-subtle);
            transition: all 0.2s;
        }
        
        .table-row:hover {
            background: rgba(255,255,255,0.02);
        }
        
        .table-row:last-child {
            border-bottom: none;
        }
        
        /* Status Indicators */
        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            position: relative;
        }
        
        .status-dot::after {
            content: '';
            position: absolute;
            inset: -3px;
            border-radius: 50%;
            background: inherit;
            opacity: 0.3;
            animation: pulse-ring 2s infinite;
        }
        
        .status-good { 
            background: #ffffff; 
            box-shadow: 0 0 15px rgba(255,255,255,0.5);
        }
        .status-moderate { 
            background: #f59e0b; 
            box-shadow: 0 0 15px rgba(245,158,11,0.5);
        }
        .status-bad { 
            background: #ef4444; 
            box-shadow: 0 0 15px rgba(239,68,68,0.5);
        }
        .status-unavailable { 
            background: #6b7280;
        }
        
        /* Packet Loss Bar */
        .packet-bar {
            height: 6px;
            border-radius: 10px;
            background: rgba(255,255,255,0.05);
            overflow: hidden;
            position: relative;
        }
        
        .packet-fill {
            height: 100%;
            border-radius: 10px;
            transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }
        
        .packet-fill::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, transparent 0%, rgba(255,255,255,0.3) 50%, transparent 100%);
            animation: shimmer 2s infinite;
        }
        
        /* Sparkline */
        .sparkline-container {
            width: 100px;
            height: 35px;
        }
        
        /* Upgrade Card */
        .upgrade-card {
            background: linear-gradient(135deg, rgba(255,255,255,0.15) 0%, rgba(255,255,255,0.1) 50%, rgba(0,0,0,0.3) 100%);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 20px;
            padding: 24px;
            position: relative;
            overflow: hidden;
        }
        
        .upgrade-card::before {
            content: '';
            position: absolute;
            top: -100%;
            left: -100%;
            width: 300%;
            height: 300%;
            background: radial-gradient(circle at 30% 30%, rgba(255,255,255,0.1) 0%, transparent 40%);
            animation: rotate-gradient 10s linear infinite;
        }
        
        .upgrade-card::after {
            content: '';
            position: absolute;
            bottom: 0;
            right: 0;
            width: 150px;
            height: 150px;
            background: radial-gradient(circle at bottom right, rgba(255,255,255,0.2) 0%, transparent 60%);
        }
        
        /* Buttons */
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
        
        .btn-ghost {
            background: rgba(255,255,255,0.03);
            border: 1px solid var(--border-subtle);
            color: var(--text-secondary);
            font-weight: 500;
            padding: 10px 18px;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-ghost:hover {
            background: rgba(255,255,255,0.06);
            border-color: var(--border-light);
            color: var(--text-primary);
        }
        
        /* Search Input */
        .search-input {
            background: rgba(255,255,255,0.03);
            border: 1px solid var(--border-subtle);
            border-radius: 12px;
            padding: 12px 18px 12px 44px;
            color: var(--text-primary);
            width: 240px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-size: 14px;
        }
        
        .search-input::placeholder {
            color: var(--text-muted);
        }
        
        .search-input:focus {
            outline: none;
            border-color: rgba(255, 255, 255, 0.3);
            box-shadow: 0 0 0 4px rgba(255, 255, 255, 0.1);
            width: 280px;
        }
        
        /* Period Buttons */
        .period-btn {
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-muted);
            background: transparent;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .period-btn:hover {
            color: var(--text-secondary);
            background: rgba(255,255,255,0.03);
        }
        
        .period-btn.active {
            background: rgba(255,255,255,0.08);
            color: var(--text-primary);
        }
        
        /* Quick Link Cards */
        .quick-link {
            background: var(--bg-card);
            border: 1px solid var(--border-subtle);
            border-radius: 16px;
            padding: 20px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            display: block;
        }
        
        .quick-link:hover {
            transform: translateY(-4px) scale(1.02);
            border-color: var(--border-light);
        }
        
        .quick-link .icon-wrapper {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }
        
        .quick-link:hover .icon-wrapper {
            transform: scale(1.1);
        }
        
        /* Event Log Item */
        .event-item {
            background: rgba(255,255,255,0.02);
            border: 1px solid transparent;
            border-radius: 12px;
            padding: 14px;
            transition: all 0.2s;
        }
        
        .event-item:hover {
            background: rgba(255,255,255,0.04);
            border-color: var(--border-subtle);
        }
        
        /* Animations */
        @keyframes pulse-ring {
            0% { transform: scale(1); opacity: 0.3; }
            50% { transform: scale(1.5); opacity: 0; }
            100% { transform: scale(1); opacity: 0.3; }
        }
        
        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        
        @keyframes rotate-gradient {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        @keyframes glow {
            0%, 100% { box-shadow: 0 0 20px rgba(255,255,255,0.3); }
            50% { box-shadow: 0 0 40px rgba(255,255,255,0.5); }
        }
        
        .animate-float {
            animation: float 6s ease-in-out infinite;
        }
        
        .animate-glow {
            animation: glow 3s ease-in-out infinite;
        }
        
        /* Notification Panel */
        .notification-panel {
            background: var(--bg-secondary);
            border-left: 1px solid var(--border-subtle);
        }
        
        /* Badge */
        .badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.3px;
        }
        
        .badge-danger {
            background: rgba(239,68,68,0.15);
            color: #f87171;
        }
        
        .badge-success {
            background: rgba(255,255,255,0.15);
            color: #ffffff;
        }
        
        .badge-warning {
            background: rgba(245,158,11,0.15);
            color: #fbbf24;
        }
        
        /* Checkbox Custom */
        input[type="checkbox"] {
            appearance: none;
            width: 18px;
            height: 18px;
            border: 2px solid var(--border-light);
            border-radius: 5px;
            background: transparent;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        input[type="checkbox"]:checked {
            background: var(--accent);
            border-color: var(--accent);
        }
        
        input[type="checkbox"]:checked::after {
            content: '✓';
            display: flex;
            align-items: center;
            justify-content: center;
            color: #000;
            font-size: 12px;
            font-weight: bold;
        }
        
        /* Tooltip */
        .tooltip {
            position: relative;
        }
        
        .tooltip::after {
            content: attr(data-tip);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%) translateY(-8px);
            padding: 6px 12px;
            background: var(--bg-tertiary);
            border: 1px solid var(--border-light);
            border-radius: 8px;
            font-size: 12px;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: all 0.2s;
        }
        
        .tooltip:hover::after {
            opacity: 1;
            transform: translateX(-50%) translateY(-4px);
        }
        
        /* Loading Skeleton */
        .skeleton {
            background: linear-gradient(90deg, var(--bg-tertiary) 25%, var(--bg-hover) 50%, var(--bg-tertiary) 75%);
            background-size: 200% 100%;
            animation: skeleton-loading 1.5s infinite;
            border-radius: 8px;
        }
        
        @keyframes skeleton-loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        
        [x-cloak] { display: none !important; }
    </style>
</head>
<body x-data="{ notificationsOpen: false, sidebarOpen: false }" class="h-full overflow-hidden flex">

    <!-- Sidebar -->
    <aside class="sidebar w-72 h-full flex-shrink-0 flex flex-col hidden lg:flex">
        <!-- Logo -->
        <div class="p-6 border-b border-white/5">
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2">
                    <img src="assets/img/logos (6).png" alt="SafeNode Logo" class="w-8 h-8 object-contain">
                    <div>
                        <h1 class="font-bold text-white text-xl tracking-tight">SafeNode</h1>
                        <p class="text-xs text-zinc-500 font-medium">Security Platform</p>
                    </div>
                </div>
                <button class="ml-auto text-zinc-600 hover:text-zinc-400 transition-colors">
                    <i data-lucide="chevrons-left" class="w-5 h-5"></i>
                </button>
            </div>
        </div>
        
        <!-- Navigation -->
        <nav class="flex-1 p-5 space-y-2 overflow-y-auto">
            <p class="text-xs font-semibold text-zinc-600 uppercase tracking-wider mb-4 px-3">Menu Principal</p>
            
            <a href="dashboard.php" class="nav-item active">
                <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
                <span class="font-medium">Home</span>
            </a>
            <a href="sites.php" class="nav-item">
                <i data-lucide="globe" class="w-5 h-5"></i>
                <span class="font-medium">Gerenciar Sites</span>
            </a>
            <a href="security-analytics.php" class="nav-item">
                <i data-lucide="activity" class="w-5 h-5"></i>
                <span class="font-medium">Network</span>
            </a>
            <a href="behavior-analysis.php" class="nav-item">
                <i data-lucide="cpu" class="w-5 h-5"></i>
                <span class="font-medium">Kubernetes</span>
            </a>
            <a href="logs.php" class="nav-item">
                <i data-lucide="compass" class="w-5 h-5"></i>
                <span class="font-medium">Explorar</span>
            </a>
            <a href="suspicious-ips.php" class="nav-item">
                <i data-lucide="bar-chart-3" class="w-5 h-5"></i>
                <span class="font-medium">Analisar</span>
            </a>
            <a href="attacked-targets.php" class="nav-item">
                <i data-lucide="users-2" class="w-5 h-5"></i>
                <span class="font-medium">Grupos</span>
            </a>
            
            <div class="pt-6 mt-6 border-t border-white/5">
                <p class="text-xs font-semibold text-zinc-600 uppercase tracking-wider mb-4 px-3">Sistema</p>
                <a href="human-verification.php" class="nav-item">
                    <i data-lucide="shield-check" class="w-5 h-5"></i>
                    <span class="font-medium">Verificação Humana</span>
                </a>
                <a href="recaptcha.php" class="nav-item">
                    <i data-lucide="shield" class="w-5 h-5"></i>
                    <span class="font-medium">reCAPTCHA</span>
                </a>
                <a href="settings.php" class="nav-item">
                    <i data-lucide="settings-2" class="w-5 h-5"></i>
                    <span class="font-medium">Configurações</span>
                </a>
                <a href="help.php" class="nav-item">
                    <i data-lucide="life-buoy" class="w-5 h-5"></i>
                    <span class="font-medium">Ajuda</span>
                </a>
            </div>
        </nav>
        
        <!-- Upgrade Card -->
        <div class="p-5">
            <div class="upgrade-card">
                <h3 class="font-semibold text-white text-sm mb-3">Ativar Pro</h3>
                <button class="w-full btn-primary py-2.5 text-sm">
                    Upgrade Agora
                </button>
            </div>
        </div>
    </aside>

    <!-- Mobile Sidebar Overlay -->
    <div x-show="sidebarOpen" 
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="sidebarOpen = false"
         class="fixed inset-0 bg-black/80 z-40 lg:hidden"
         x-cloak
         style="display: none;"></div>

    <!-- Mobile Sidebar -->
    <aside x-show="sidebarOpen"
           x-transition:enter="transition ease-out duration-300 transform"
           x-transition:enter-start="-translate-x-full"
           x-transition:enter-end="translate-x-0"
           x-transition:leave="transition ease-in duration-300 transform"
           x-transition:leave-start="translate-x-0"
           x-transition:leave-end="-translate-x-full"
           @click.away="sidebarOpen = false"
           class="fixed inset-y-0 left-0 w-72 sidebar h-full flex flex-col z-50 lg:hidden overflow-y-auto"
           x-cloak
           style="display: none;">
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
                <button @click="sidebarOpen = false" class="text-zinc-600 hover:text-zinc-400 transition-colors flex-shrink-0">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
        </div>
        
        <!-- Navigation -->
        <nav class="flex-1 p-4 space-y-1 overflow-y-auto overflow-x-hidden">
            <p class="text-xs font-semibold text-zinc-600 uppercase tracking-wider mb-3 px-3 whitespace-nowrap">Menu Principal</p>
            
            <a href="dashboard.php" class="nav-item active" @click="sidebarOpen = false">
                <i data-lucide="layout-dashboard" class="w-5 h-5 flex-shrink-0"></i>
                <span class="font-medium whitespace-nowrap">Home</span>
            </a>
            <a href="sites.php" class="nav-item" @click="sidebarOpen = false">
                <i data-lucide="globe" class="w-5 h-5 flex-shrink-0"></i>
                <span class="font-medium whitespace-nowrap">Gerenciar Sites</span>
            </a>
            <a href="security-analytics.php" class="nav-item" @click="sidebarOpen = false">
                <i data-lucide="activity" class="w-5 h-5 flex-shrink-0"></i>
                <span class="font-medium whitespace-nowrap">Network</span>
            </a>
            <a href="behavior-analysis.php" class="nav-item" @click="sidebarOpen = false">
                <i data-lucide="cpu" class="w-5 h-5 flex-shrink-0"></i>
                <span class="font-medium whitespace-nowrap">Kubernetes</span>
            </a>
            <a href="logs.php" class="nav-item" @click="sidebarOpen = false">
                <i data-lucide="compass" class="w-5 h-5 flex-shrink-0"></i>
                <span class="font-medium whitespace-nowrap">Explorar</span>
            </a>
            <a href="suspicious-ips.php" class="nav-item" @click="sidebarOpen = false">
                <i data-lucide="bar-chart-3" class="w-5 h-5 flex-shrink-0"></i>
                <span class="font-medium whitespace-nowrap">Analisar</span>
            </a>
            <a href="attacked-targets.php" class="nav-item" @click="sidebarOpen = false">
                <i data-lucide="users-2" class="w-5 h-5 flex-shrink-0"></i>
                <span class="font-medium whitespace-nowrap">Grupos</span>
            </a>
            
            <div class="pt-4 mt-4 border-t border-white/5">
                <p class="text-xs font-semibold text-zinc-600 uppercase tracking-wider mb-3 px-3 whitespace-nowrap">Sistema</p>
                <a href="human-verification.php" class="nav-item" @click="sidebarOpen = false">
                    <i data-lucide="shield-check" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="font-medium whitespace-nowrap">Verificação Humana</span>
                </a>
                <a href="recaptcha.php" class="nav-item" @click="sidebarOpen = false">
                    <i data-lucide="shield" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="font-medium whitespace-nowrap">reCAPTCHA</span>
                </a>
                <a href="settings.php" class="nav-item" @click="sidebarOpen = false">
                    <i data-lucide="settings-2" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="font-medium whitespace-nowrap">Configurações</span>
                </a>
                <a href="help.php" class="nav-item" @click="sidebarOpen = false">
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

    <!-- Main Content -->
    <main class="flex-1 flex flex-col h-full overflow-hidden bg-dark-950">
        <!-- Header -->
        <header class="h-20 bg-dark-900/50 backdrop-blur-xl border-b border-white/5 px-8 flex items-center justify-between flex-shrink-0">
            <div class="flex items-center gap-6">
                <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden text-zinc-400 hover:text-white transition-colors">
                    <i data-lucide="menu" class="w-6 h-6"></i>
                </button>
                <div>
                    <h2 class="text-2xl font-bold text-white tracking-tight">Dashboard</h2>
                    <?php if ($currentSiteId > 0 && $selectedSite): ?>
                    <p class="text-sm text-zinc-500 font-mono mt-0.5"><?php echo htmlspecialchars($selectedSite['name'] ?? ''); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <!-- Search -->
                <div class="relative hidden md:block">
                    <i data-lucide="search" class="w-4 h-4 absolute left-4 top-1/2 -translate-y-1/2 text-zinc-500"></i>
                    <input type="text" placeholder="Buscar..." class="search-input">
                </div>
                
                <!-- Add New -->
                <button class="btn-primary hidden sm:flex items-center gap-2">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    <span>Adicionar</span>
                </button>
                
                <!-- Notifications -->
                <button @click="notificationsOpen = !notificationsOpen" class="relative p-3 text-zinc-400 hover:text-white hover:bg-white/5 rounded-xl transition-all">
                    <i data-lucide="bell" class="w-5 h-5"></i>
                    <span class="absolute top-2 right-2 w-2.5 h-2.5 bg-white rounded-full border-2 border-dark-900 animate-pulse"></span>
                </button>
                
                <!-- Profile -->
                <button onclick="window.location.href='profile.php'" class="flex items-center gap-3 p-2 hover:bg-white/5 rounded-xl transition-all group">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-orange-400 to-rose-500 flex items-center justify-center text-white font-bold text-sm shadow-lg group-hover:scale-105 transition-transform">
                        <?php echo strtoupper(substr($_SESSION['safenode_username'] ?? 'U', 0, 1)); ?>
                </div>
                    </button>
            </div>
        </header>

        <!-- Scrollable Content -->
        <div class="flex-1 overflow-y-auto p-8">
            <?php if (!empty($dashboardFlash)): ?>
            <div class="mb-8 glass rounded-2xl p-5 <?php echo $dashboardFlashType === 'warning' ? 'border-amber-500/30' : ($dashboardFlashType === 'error' ? 'border-red-500/30' : 'border-white/30'); ?> flex items-center gap-4">
                <div class="w-10 h-10 rounded-xl <?php echo $dashboardFlashType === 'warning' ? 'bg-amber-500/20' : ($dashboardFlashType === 'error' ? 'bg-red-500/20' : 'bg-white/20'); ?> flex items-center justify-center">
                    <i data-lucide="<?php echo $dashboardFlashType === 'error' ? 'alert-triangle' : ($dashboardFlashType === 'warning' ? 'shield-alert' : 'check-circle-2'); ?>" class="w-5 h-5 <?php echo $dashboardFlashType === 'warning' ? 'text-amber-400' : ($dashboardFlashType === 'error' ? 'text-red-400' : 'text-white'); ?>"></i>
                </div>
                <p class="font-medium <?php echo $dashboardFlashType === 'warning' ? 'text-amber-200' : ($dashboardFlashType === 'error' ? 'text-red-200' : 'text-white'); ?>"><?php echo htmlspecialchars($dashboardFlash); ?></p>
            </div>
                <?php endif; ?>
            
            <!-- Stats Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-5 mb-8">
                <!-- Total Requests -->
                <div class="stat-card group">
                    <div class="flex items-center justify-between mb-1">
                        <p class="text-xs sm:text-sm font-medium text-zinc-400">Total de Requisições</p>
                        <button class="text-zinc-600 hover:text-zinc-400 opacity-0 group-hover:opacity-100 transition-opacity hidden sm:block">
                            <i data-lucide="more-vertical" class="w-4 h-4"></i>
                        </button>
                    </div>
                    <div class="flex items-end justify-between mt-2 sm:mt-4">
                        <p id="total-requests" class="text-2xl sm:text-4xl font-bold text-white tracking-tight">-</p>
                        <span id="requests-change" class="text-xs sm:text-sm font-semibold text-white bg-white/10 px-2 py-0.5 sm:px-2.5 sm:py-1 rounded-lg"></span>
                    </div>
                    <p class="text-xs text-zinc-600 mt-2 sm:mt-3">comparado a ontem</p>
                </div>

                <!-- Blocked -->
                <div class="stat-card group">
                    <div class="flex items-center justify-between mb-1">
                        <p class="text-xs sm:text-sm font-medium text-zinc-400">Requisições Bloqueadas</p>
                        <button class="text-zinc-600 hover:text-zinc-400 opacity-0 group-hover:opacity-100 transition-opacity hidden sm:block">
                            <i data-lucide="more-vertical" class="w-4 h-4"></i>
                        </button>
                    </div>
                    <div class="flex items-end justify-between mt-2 sm:mt-4">
                        <p id="blocked-requests" class="text-2xl sm:text-4xl font-bold text-white tracking-tight">-</p>
                        <span id="blocked-change" class="text-xs sm:text-sm font-semibold text-red-400 bg-red-500/10 px-2 py-0.5 sm:px-2.5 sm:py-1 rounded-lg"></span>
                    </div>
                    <p class="text-xs text-zinc-600 mt-2 sm:mt-3">Taxa: <span id="block-rate" class="text-red-400 font-medium">-</span>%</p>
                </div>
                
                <!-- Unique IPs -->
                <div class="stat-card group">
                    <div class="flex items-center justify-between mb-1">
                        <p class="text-xs sm:text-sm font-medium text-zinc-400">Visitantes Únicos</p>
                        <button class="text-zinc-600 hover:text-zinc-400 opacity-0 group-hover:opacity-100 transition-opacity hidden sm:block">
                            <i data-lucide="more-vertical" class="w-4 h-4"></i>
                        </button>
                    </div>
                    <div class="flex items-end justify-between mt-2 sm:mt-4">
                        <p id="unique-ips" class="text-2xl sm:text-4xl font-bold text-white tracking-tight">-</p>
                        <span id="ips-change" class="text-xs sm:text-sm font-semibold text-white bg-white/10 px-2 py-0.5 sm:px-2.5 sm:py-1 rounded-lg"></span>
                    </div>
                    <p class="text-xs text-zinc-600 mt-2 sm:mt-3">últimas 24h</p>
                </div>
                
                <!-- Active Blocks -->
                <div class="stat-card group">
                    <div class="flex items-center justify-between mb-1">
                        <p class="text-xs sm:text-sm font-medium text-zinc-400">IPs Bloqueados</p>
                        <button class="text-zinc-600 hover:text-zinc-400 opacity-0 group-hover:opacity-100 transition-opacity hidden sm:block">
                            <i data-lucide="more-vertical" class="w-4 h-4"></i>
                        </button>
                    </div>
                    <div class="flex items-end justify-between mt-2 sm:mt-4">
                        <p id="active-blocks" class="text-2xl sm:text-4xl font-bold text-white tracking-tight">-</p>
                        <span class="text-xs sm:text-sm font-semibold text-amber-400 bg-amber-500/10 px-2 py-0.5 sm:px-2.5 sm:py-1 rounded-lg">ativos</span>
                    </div>
                    <p class="text-xs text-zinc-600 mt-2 sm:mt-3">últimos 7 dias</p>
                </div>
            </div>
            
            <!-- Charts Row -->
            <div class="grid grid-cols-1 lg:grid-cols-5 gap-6 mb-8">
                <!-- Entities Overview (Donut Chart) -->
                <div class="lg:col-span-2 chart-card">
                    <div class="flex items-center justify-between mb-8">
                        <h3 class="text-lg font-semibold text-white">Visão Geral de Ameaças</h3>
                        <button class="text-zinc-600 hover:text-zinc-400 transition-colors">
                            <i data-lucide="more-vertical" class="w-4 h-4"></i>
                        </button>
                    </div>
                    <div class="flex items-center justify-center">
                        <div class="relative" style="width: 220px; height: 220px;">
                            <canvas id="entitiesChart" width="220" height="220"></canvas>
                            <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                                <span id="total-score" class="text-5xl font-bold text-white">-</span>
                                <span class="text-xs text-zinc-500 font-medium mt-1">Total Score</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center justify-center gap-8 mt-8">
                        <div class="flex items-center gap-2.5">
                            <span class="w-3 h-3 rounded-full bg-white"></span>
                            <span class="text-sm text-zinc-400">Good</span>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <span class="w-3 h-3 rounded-full bg-amber-500"></span>
                            <span class="text-sm text-zinc-400">Moderate</span>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <span class="w-3 h-3 rounded-full bg-violet-500"></span>
                            <span class="text-sm text-zinc-400">Bad</span>
                        </div>
                    </div>
                </div>
                
                <!-- Network Anomalies (Bar Chart) -->
                <div class="lg:col-span-3 chart-card">
                    <div class="flex items-center justify-between mb-8">
                        <h3 class="text-lg font-semibold text-white">Anomalias de Rede</h3>
                        <div class="flex items-center gap-1 bg-white/5 rounded-xl p-1.5">
                            <button class="period-btn" data-period="1W">1S</button>
                            <button class="period-btn active" data-period="1M">1M</button>
                            <button class="period-btn" data-period="1Y">1A</button>
                        </div>
                    </div>
                    <div class="relative" style="height: 200px;">
                        <canvas id="anomaliesChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Network Devices Table -->
            <div class="table-card mb-8">
                <div class="table-header p-4 sm:p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <h3 class="text-lg font-semibold text-white">Dispositivos de Rede</h3>
                    <div class="flex items-center gap-2 sm:gap-3 w-full sm:w-auto">
                        <div class="relative flex-1 sm:flex-initial">
                            <i data-lucide="search" class="w-4 h-4 absolute left-3 sm:left-4 top-1/2 -translate-y-1/2 text-zinc-500"></i>
                            <input type="text" id="device-search" placeholder="Buscar por nome" class="bg-white/5 border border-white/10 rounded-xl py-2 sm:py-2.5 pl-9 sm:pl-11 pr-3 sm:pr-4 text-sm text-white placeholder-zinc-500 focus:outline-none focus:border-white/30 focus:ring-2 focus:ring-white/10 w-full sm:w-56 transition-all">
                        </div>
                        <button id="device-search-btn" class="btn-ghost flex items-center gap-1.5 sm:gap-2 text-xs sm:text-sm whitespace-nowrap">
                            <span class="hidden sm:inline">Buscar</span>
                            <i data-lucide="search" class="w-4 h-4 sm:hidden"></i>
                        </button>
                        <button id="device-filter-btn" class="btn-ghost flex items-center gap-1.5 sm:gap-2 text-xs sm:text-sm whitespace-nowrap">
                            <i data-lucide="sliders-horizontal" class="w-4 h-4"></i>
                            <span class="hidden sm:inline">Filtrar</span>
                        </button>
                    </div>
                </div>
                
                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-xs text-zinc-500 uppercase tracking-wider border-b border-white/5">
                                <th class="px-3 sm:px-6 py-3 sm:py-4 font-semibold">Health</th>
                                <th class="px-3 sm:px-6 py-3 sm:py-4 font-semibold">Nome</th>
                                <th class="px-3 sm:px-6 py-3 sm:py-4 font-semibold">Tipo</th>
                                <th class="px-3 sm:px-6 py-3 sm:py-4 font-semibold">Origem</th>
                                <th class="px-3 sm:px-6 py-3 sm:py-4 font-semibold">Response Time</th>
                                <th class="px-3 sm:px-6 py-3 sm:py-4 font-semibold">Packet Loss</th>
                                <th class="px-3 sm:px-6 py-3 sm:py-4 font-semibold">Ação</th>
                            </tr>
                        </thead>
                        <tbody id="devices-table">
                            <tr class="table-row">
                                <td colspan="7" class="px-6 py-12 text-center text-zinc-500">
                                    <div class="flex flex-col items-center">
                                        <div class="w-12 h-12 rounded-2xl bg-white/5 flex items-center justify-center mb-3">
                                            <i data-lucide="loader-2" class="w-6 h-6 animate-spin"></i>
                                        </div>
                                        <p class="text-sm font-medium">Carregando dispositivos...</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Threat Analysis Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Top Blocked IPs -->
                <div class="chart-card">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-semibold text-white">Top IPs Bloqueados</h3>
                        <button class="text-zinc-600 hover:text-zinc-400 transition-colors">
                            <i data-lucide="external-link" class="w-4 h-4"></i>
                </button>
            </div>
                    <div id="top-blocked-ips" class="space-y-3">
                        <div class="text-center py-10 text-zinc-500">
                            <div class="w-12 h-12 rounded-2xl bg-white/5 flex items-center justify-center mx-auto mb-3">
                                <i data-lucide="loader-2" class="w-6 h-6 animate-spin"></i>
                            </div>
                            <p class="text-sm font-medium">Carregando...</p>
                        </div>
                    </div>
                </div>
                
                <!-- Top Countries -->
                <div class="chart-card">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-semibold text-white">Top Países</h3>
                        <button class="text-zinc-600 hover:text-zinc-400 transition-colors">
                            <i data-lucide="external-link" class="w-4 h-4"></i>
                        </button>
                    </div>
                    <div id="top-countries" class="space-y-3">
                        <div class="text-center py-10 text-zinc-500">
                            <div class="w-12 h-12 rounded-2xl bg-white/5 flex items-center justify-center mx-auto mb-3">
                                <i data-lucide="loader-2" class="w-6 h-6 animate-spin"></i>
                    </div>
                            <p class="text-sm font-medium">Carregando...</p>
                </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Links -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
                <a href="behavior-analysis.php" class="quick-link group" style="--accent-color: #a855f7;">
                    <div class="flex items-center gap-4 mb-3">
                        <div class="icon-wrapper bg-violet-500/15">
                            <i data-lucide="brain" class="w-5 h-5 text-violet-400"></i>
                            </div>
                        <h3 class="text-sm font-semibold text-white group-hover:text-violet-400 transition-colors">Análise Comportamental</h3>
                        </div>
                    <p class="text-xs text-zinc-500 leading-relaxed">IPs com comportamento suspeito</p>
                </a>
                
                <a href="security-analytics.php" class="quick-link group" style="--accent-color: #f59e0b;">
                    <div class="flex items-center gap-4 mb-3">
                        <div class="icon-wrapper bg-amber-500/15">
                            <i data-lucide="lightbulb" class="w-5 h-5 text-amber-400"></i>
                                            </div>
                        <h3 class="text-sm font-semibold text-white group-hover:text-amber-400 transition-colors">Security Analytics</h3>
                                        </div>
                    <p class="text-xs text-zinc-500 leading-relaxed">Análises avançadas e insights</p>
                </a>
                
                <a href="suspicious-ips.php" class="quick-link group" style="--accent-color: #ef4444;">
                    <div class="flex items-center gap-4 mb-3">
                        <div class="icon-wrapper bg-red-500/15">
                            <i data-lucide="alert-octagon" class="w-5 h-5 text-red-400"></i>
                        </div>
                        <h3 class="text-sm font-semibold text-white group-hover:text-red-400 transition-colors">IPs Suspeitos</h3>
                    </div>
                    <p class="text-xs text-zinc-500 leading-relaxed">IPs com múltiplos ataques</p>
                </a>
                
                <a href="attacked-targets.php" class="quick-link group" style="--accent-color: #f97316;">
                    <div class="flex items-center gap-4 mb-3">
                        <div class="icon-wrapper bg-orange-500/15">
                            <i data-lucide="target" class="w-5 h-5 text-orange-400"></i>
                        </div>
                        <h3 class="text-sm font-semibold text-white group-hover:text-orange-400 transition-colors">Alvos Atacados</h3>
                    </div>
                    <p class="text-xs text-zinc-500 leading-relaxed">URIs mais visadas</p>
                </a>
            </div>
            
            <!-- Recent Events -->
            <div class="chart-card">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-white">Eventos Recentes</h3>
                    <span id="last-update" class="text-xs text-zinc-500 font-mono bg-white/5 px-3 py-1.5 rounded-lg"></span>
                </div>
                <div id="recent-logs" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div class="text-center py-10 text-zinc-500 col-span-2">
                        <div class="w-12 h-12 rounded-2xl bg-white/5 flex items-center justify-center mx-auto mb-3">
                            <i data-lucide="loader-2" class="w-6 h-6 animate-spin"></i>
                        </div>
                        <p class="text-sm font-medium">Carregando...</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Notifications Panel -->
    <div x-show="notificationsOpen" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="translate-x-full opacity-0"
         x-transition:enter-end="translate-x-0 opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="translate-x-0 opacity-100"
         x-transition:leave-end="translate-x-full opacity-0"
         @click.away="notificationsOpen = false"
         class="notification-panel fixed right-0 top-0 h-full w-96 bg-dark-900 z-50 shadow-2xl"
         x-cloak>
        <div class="flex flex-col h-full">
            <div class="p-6 border-b border-white/5 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-white/15 flex items-center justify-center">
                        <i data-lucide="bell" class="w-6 h-6 text-white"></i>
                    </div>
                <div>
                        <h3 class="font-bold text-white text-lg">Notificações</h3>
                        <p class="text-xs text-zinc-500">Alertas e eventos</p>
                    </div>
                </div>
                <button @click="notificationsOpen = false" class="p-2.5 text-zinc-400 hover:text-white hover:bg-white/5 rounded-xl transition-all">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto p-5">
                <div class="text-center py-16">
                    <div class="w-20 h-20 bg-white/5 rounded-3xl flex items-center justify-center mx-auto mb-5">
                        <i data-lucide="bell-off" class="w-10 h-10 text-zinc-600"></i>
                    </div>
                    <p class="text-sm text-zinc-400 font-medium">Nenhuma notificação</p>
                    <p class="text-xs text-zinc-600 mt-1">Você será notificado de novos eventos</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Initialize Lucide Icons
        lucide.createIcons();

        // Charts
        let entitiesChart = null;
        let anomaliesChart = null;
        
        // Initialize Entities Chart (Donut)
        function initEntitiesChart() {
            const canvas = document.getElementById('entitiesChart');
            if (!canvas) {
                console.error('Canvas entitiesChart não encontrado');
                return;
            }
            
            // Verificar se Chart.js está carregado (pode ser Chart ou Chart.Chart dependendo da versão)
            const ChartLib = window.Chart || (window.Chart && window.Chart.Chart) || Chart;
            if (typeof ChartLib === 'undefined' || !ChartLib) {
                console.error('Chart.js não está carregado. Tentando novamente...');
                setTimeout(initEntitiesChart, 200);
                return;
            }
            
            // Destruir gráfico existente se houver
            if (entitiesChart) {
                try {
                    entitiesChart.destroy();
                } catch (e) {
                    console.warn('Erro ao destruir gráfico anterior:', e);
                }
                entitiesChart = null;
            }
            
            const ctx = canvas.getContext('2d');
            if (!ctx) {
                console.error('Não foi possível obter contexto 2D do canvas');
                return;
            }
            
            // Create gradient for better visual effect
            const gradient1 = ctx.createLinearGradient(0, 0, 0, 220);
            gradient1.addColorStop(0, '#ffffff');
            gradient1.addColorStop(1, '#e5e5e5');
            
            const gradient2 = ctx.createLinearGradient(0, 0, 0, 220);
            gradient2.addColorStop(0, '#f59e0b');
            gradient2.addColorStop(1, '#d97706');
            
            const gradient3 = ctx.createLinearGradient(0, 0, 0, 220);
            gradient3.addColorStop(0, '#a855f7');
            gradient3.addColorStop(1, '#7c3aed');
            
            try {
                entitiesChart = new ChartLib(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Good', 'Moderate', 'Bad'],
                        datasets: [{
                            data: [65, 25, 10],
                            backgroundColor: [gradient1, gradient2, gradient3],
                            borderWidth: 0,
                            cutout: '78%',
                            borderRadius: 6,
                            spacing: 4
                        }]
                    },
                    options: {
                    responsive: false,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { 
                            display: false 
                        },
                        tooltip: {
                            enabled: true,
                            backgroundColor: '#0f0f0f',
                            titleColor: '#fff',
                            bodyColor: '#a1a1aa',
                            borderColor: 'rgba(255,255,255,0.1)',
                            borderWidth: 1,
                            padding: 14,
                            cornerRadius: 12,
                            displayColors: true,
                            boxWidth: 10,
                            boxHeight: 10,
                            boxPadding: 4,
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed || 0;
                                    return label + ': ' + value;
                                }
                            }
                        }
                    },
                    animation: {
                        animateRotate: true,
                        animateScale: true,
                        duration: 1000,
                        easing: 'easeOutQuart'
                    },
                    interaction: {
                        intersect: false
                    }
                }
                });
                console.log('Gráfico entitiesChart inicializado com sucesso');
            } catch (error) {
                console.error('Erro ao criar gráfico entitiesChart:', error);
            }
        }
        
        // Initialize Anomalies Chart (Bar)
        function initAnomaliesChart() {
            const canvas = document.getElementById('anomaliesChart');
            if (!canvas) return;
            
            const ctx = canvas.getContext('2d');
            
            // Dados iniciais
            const initialData = [120, 190, 80, 150, 200, 380, 320, 280, 180, 220, 160];
            const maxValue = Math.max(...initialData, 0);
            const yAxisMax = Math.ceil((maxValue * 1.2) / 100) * 100; // Arredondar para múltiplo de 100
            
            // Create gradient for highlighted bars
            const highlightGradient = ctx.createLinearGradient(0, 0, 0, 200);
            highlightGradient.addColorStop(0, '#ffffff');
            highlightGradient.addColorStop(1, '#e5e5e5');
            
            anomaliesChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov'],
                    datasets: [{
                        data: initialData,
                        backgroundColor: function(context) {
                            const index = context.dataIndex;
                            if (index === 5 || index === 6) {
                                return highlightGradient;
                            }
                            return 'rgba(255,255,255,0.05)';
                        },
                        borderRadius: 6,
                        borderSkipped: false,
                        hoverBackgroundColor: highlightGradient,
                        barThickness: 24,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    aspectRatio: 4,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#0f0f0f',
                            titleColor: '#fff',
                            bodyColor: '#a1a1aa',
                            borderColor: 'rgba(255,255,255,0.1)',
                            borderWidth: 1,
                            padding: 14,
                            cornerRadius: 12,
                            displayColors: false,
                            callbacks: {
                                label: function(context) {
                                    return context.raw + ' anomalias';
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { 
                                color: '#52525b',
                                font: { size: 11, weight: 500 }
                            },
                            border: { display: false }
                        },
                        y: {
                            beginAtZero: true,
                            suggestedMax: yAxisMax,
                            max: yAxisMax,
                            grid: { 
                                color: 'rgba(255,255,255,0.03)',
                                drawBorder: false
                            },
                            ticks: { 
                                color: '#52525b',
                                font: { size: 11 },
                                padding: 10,
                                stepSize: Math.max(50, Math.ceil(yAxisMax / 5)),
                                maxTicksLimit: 6
                            },
                            border: { display: false }
                        }
                    },
                    animation: {
                        duration: 1000,
                        easing: 'easeOutQuart'
                    }
                }
            });
        }
        
        // Função para verificar se Chart.js está carregado
        function waitForChartJS(callback, maxAttempts = 30) {
            let attempts = 0;
            const checkChart = setInterval(function() {
                attempts++;
                const ChartLib = window.Chart || (window.Chart && window.Chart.Chart);
                if (ChartLib && typeof ChartLib !== 'undefined') {
                    clearInterval(checkChart);
                    callback();
                } else if (attempts >= maxAttempts) {
                    clearInterval(checkChart);
                    console.error('Chart.js não foi carregado após ' + (maxAttempts * 100) + 'ms');
                    // Tentar mesmo assim se Chart existir
                    if (typeof Chart !== 'undefined' || typeof window.Chart !== 'undefined') {
                        callback();
                    }
                }
            }, 100);
        }
        
        // Initialize Charts quando DOM estiver pronto e Chart.js carregado
        function initializeAllCharts() {
            const canvas = document.getElementById('entitiesChart');
            if (!canvas) {
                console.error('Canvas entitiesChart não encontrado no DOM');
                // Tentar novamente após um delay
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', function() {
                        setTimeout(initializeAllCharts, 300);
                    });
                } else {
                    setTimeout(initializeAllCharts, 300);
                }
                return;
            }
            
            // Esperar Chart.js estar disponível
            waitForChartJS(function() {
                try {
                    console.log('Inicializando gráficos...');
                    initEntitiesChart();
                    initAnomaliesChart();
                } catch (error) {
                    console.error('Erro ao inicializar gráficos:', error);
                }
            });
        }
        
        // Inicializar gráficos quando o script carregar
        // Aguardar um pouco para garantir que tudo está carregado
        setTimeout(function() {
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() {
                    setTimeout(initializeAllCharts, 200);
                });
            } else {
                // DOM já está pronto
                initializeAllCharts();
            }
        }, 300);
        
        // Dashboard Data Functions
        let dashboardData = null;
        let filteredDevicesData = null;
        let currentSearchTerm = '';
        let currentFilter = 'all';
        
        function formatNumber(num) {
            if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
            if (num >= 1000) return (num / 1000).toFixed(1) + 'K';
            return new Intl.NumberFormat('pt-BR').format(num);
        }
        
        function formatPercent(num) {
            return num >= 0 ? `+${num.toFixed(1)}%` : `${num.toFixed(1)}%`;
        }
        
        function formatThreatType(type) {
            const types = {
                'sql_injection': 'SQL Injection',
                'xss': 'XSS',
                'brute_force': 'Brute Force',
                'ddos': 'DDoS',
                'rate_limit': 'Rate Limit',
                'path_traversal': 'Path Traversal'
            };
            return types[type] || type;
        }
        
        async function fetchDashboardStats() {
            try {
                const response = await fetch('api/dashboard-stats.php');
                
                // Verificar se a resposta é OK
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                // Tentar fazer parse do JSON
                let result;
                const text = await response.text();
                try {
                    result = JSON.parse(text);
                } catch (parseError) {
                    console.error('Erro ao fazer parse do JSON:', parseError);
                    console.error('Resposta recebida:', text.substring(0, 500));
                    throw new Error('Resposta inválida do servidor (não é JSON válido)');
                }
                
                // Verificar se há erros na resposta
                if (!result.success) {
                    console.error('Erro na API:', result.error || 'Erro desconhecido');
                    if (result.debug) {
                        console.error('Debug info:', result.debug);
                    }
                    return;
                }
                
                if (result.data) {
                    dashboardData = result.data;
                    updateDashboard();
                } else {
                    console.warn('Resposta sem dados:', result);
                }
            } catch (error) {
                console.error('Erro ao buscar estatísticas:', error);
                console.error('Stack trace:', error.stack);
                
                // Mostrar erro visível no dashboard se houver elemento para isso
                const errorMsg = document.getElementById('dashboard-error');
                if (errorMsg) {
                    errorMsg.textContent = `Erro ao carregar dados: ${error.message}`;
                    errorMsg.classList.remove('hidden');
                }
            }
        }
        
        function updateDashboard() {
            if (!dashboardData) return;
            
            const today = dashboardData.today || {};
            const last24h = dashboardData.last24h || {};
            const changes = dashboardData.changes || {};
            
            // Update stat cards with animation
            animateValue('total-requests', today.total_requests || 0);
            animateValue('blocked-requests', today.blocked || 0);
            animateValue('unique-ips', last24h.unique_ips || 0);
            animateValue('active-blocks', dashboardData.active_blocks || 0);
            
            // Block rate
            const blockRate = today.total_requests > 0 
                ? ((today.blocked / today.total_requests) * 100).toFixed(2)
                : 0;
            document.getElementById('block-rate').textContent = blockRate;
            
            // Changes
            const requestsChange = changes.requests || 0;
            document.getElementById('requests-change').innerHTML = formatPercent(requestsChange);
            document.getElementById('requests-change').className = `text-sm font-semibold ${requestsChange >= 0 ? 'text-white bg-white/10' : 'text-red-400 bg-red-500/10'} px-2.5 py-1 rounded-lg`;
            
            const blockedChange = changes.blocked || 0;
            document.getElementById('blocked-change').innerHTML = formatPercent(blockedChange);
            document.getElementById('blocked-change').className = `text-sm font-semibold ${blockedChange >= 0 ? 'text-red-400 bg-red-500/10' : 'text-white bg-white/10'} px-2.5 py-1 rounded-lg`;
            
            const ipsChange = changes.unique_ips || 0;
            document.getElementById('ips-change').innerHTML = formatPercent(ipsChange);
            document.getElementById('ips-change').className = `text-sm font-semibold ${ipsChange >= 0 ? 'text-white bg-white/10' : 'text-red-400 bg-red-500/10'} px-2.5 py-1 rounded-lg`;
            
            // Update donut chart
            const threats = today.threats || {};
            const totalRequests = today.total_requests || 0;
            const blockedCount = today.blocked || 0;
            const goodCount = Math.max(totalRequests - blockedCount, 0);
            const moderateCount = (threats.rate_limit || 0) + (threats.brute_force || 0);
            const badCount = (threats.sql_injection || 0) + (threats.xss || 0) + (threats.ddos || 0) + (threats.path_traversal || 0) + (threats.command_injection || 0);
            
            // Calcular dados do gráfico
            // Se não houver dados, usar valores padrão para visualização (100% Good)
            let chartData;
            if (totalRequests > 0) {
                chartData = [
                    Math.max(goodCount, 0), 
                    Math.max(moderateCount, 0), 
                    Math.max(badCount, 0)
                ];
                // Garantir que a soma não seja zero para evitar gráfico vazio
                const sum = chartData[0] + chartData[1] + chartData[2];
                if (sum === 0) {
                    chartData = [100, 0, 0];
                }
            } else {
                // Valores padrão quando não há dados
                chartData = [100, 0, 0];
            }
            
            // Atualizar ou criar o gráfico
            if (entitiesChart) {
                try {
                    entitiesChart.data.datasets[0].data = chartData;
                    entitiesChart.update('active');
                } catch (error) {
                    console.error('Erro ao atualizar entitiesChart:', error);
                    // Tentar recriar o gráfico
                    entitiesChart = null;
                    initEntitiesChart();
                    if (entitiesChart) {
                        entitiesChart.data.datasets[0].data = chartData;
                        entitiesChart.update('active');
                    }
                }
            } else {
                // Tentar reinicializar o gráfico se não existir
                console.warn('entitiesChart não existe, tentando reinicializar...');
                initEntitiesChart();
                if (entitiesChart) {
                    setTimeout(() => {
                        entitiesChart.data.datasets[0].data = chartData;
                        entitiesChart.update('active');
                    }, 100);
                }
            }
            
            // Total score
            const threatAnalysis = today.threat_analysis || {};
            const totalScore = Math.round(100 - (threatAnalysis.avg_threat_score || 0) * 10);
            animateValue('total-score', Math.max(totalScore, 0));
            
            // Top IPs
            const topIPs = dashboardData.top_blocked_ips || [];
            const topIPsContainer = document.getElementById('top-blocked-ips');
            if (topIPs.length > 0) {
                topIPsContainer.innerHTML = topIPs.slice(0, 5).map(ip => `
                    <div class="flex items-center justify-between p-4 rounded-xl bg-white/[0.02] hover:bg-white/[0.04] border border-transparent hover:border-white/5 transition-all cursor-pointer group">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-xl bg-red-500/10 flex items-center justify-center">
                                <i data-lucide="globe" class="w-5 h-5 text-red-400"></i>
                    </div>
                            <div>
                                <p class="text-sm font-mono text-white group-hover:text-red-400 transition-colors">${ip.ip_address}</p>
                                <p class="text-xs text-zinc-600">${ip.block_count} bloqueios</p>
            </div>
        </div>
                        <span class="badge badge-danger">${ip.threat_types_count || 0} tipos</span>
    </div>
                `).join('');
            } else {
                topIPsContainer.innerHTML = '<p class="text-center py-8 text-zinc-600 text-sm">Nenhum IP bloqueado</p>';
            }
            
            // Top Countries
            const topCountries = dashboardData.top_countries || [];
            const topCountriesContainer = document.getElementById('top-countries');
            if (topCountries.length > 0) {
                topCountriesContainer.innerHTML = topCountries.slice(0, 5).map(country => `
                    <div class="flex items-center justify-between p-4 rounded-xl bg-white/[0.02] hover:bg-white/[0.04] border border-transparent hover:border-white/5 transition-all cursor-pointer group">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center">
                                <span class="text-sm font-bold text-white">${country.country_code || '??'}</span>
                            </div>
                            <div>
                                <p class="text-sm text-white font-medium">${formatNumber(country.total_requests)} requisições</p>
                                <p class="text-xs text-zinc-600">${formatNumber(country.blocked_requests)} bloqueadas</p>
                            </div>
                        </div>
                        <span class="text-sm font-semibold ${country.blocked_percent > 50 ? 'text-red-400' : 'text-zinc-500'}">${country.blocked_percent}%</span>
                    </div>
                `).join('');
            } else {
                topCountriesContainer.innerHTML = '<p class="text-center py-8 text-zinc-600 text-sm">Nenhum dado disponível</p>';
            }
            
            // Recent logs
            const recentLogs = dashboardData.event_logs || dashboardData.recent_logs || [];
            const recentLogsContainer = document.getElementById('recent-logs');
            if (recentLogs.length > 0) {
                recentLogsContainer.innerHTML = recentLogs.slice(0, 8).map(log => {
                    const actionIcon = log.action_taken === 'blocked' ? 'shield-off' : 'check-circle-2';
                    const actionColor = log.action_taken === 'blocked' ? 'text-red-400 bg-red-500/10' : 'text-white bg-white/10';
                    const date = new Date(log.created_at || new Date());
                    const timeStr = date.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
                    return `
                        <div class="event-item flex items-center gap-4">
                            <div class="w-9 h-9 rounded-xl ${actionColor} flex items-center justify-center flex-shrink-0">
                                <i data-lucide="${actionIcon}" class="w-4 h-4"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-mono text-white truncate">${log.ip_address || 'N/A'}</p>
                                <p class="text-xs text-zinc-600 truncate">${log.request_uri || '/'}</p>
                            </div>
                            <span class="text-xs text-zinc-500 font-mono flex-shrink-0">${timeStr}</span>
                        </div>
                    `;
                }).join('');
                lucide.createIcons();
            } else {
                recentLogsContainer.innerHTML = '<p class="text-center py-8 text-zinc-600 text-sm col-span-2">Nenhum evento recente</p>';
            }
            
            // Reset filters and update devices table
            filteredDevicesData = null;
            currentSearchTerm = '';
            currentFilter = 'all';
            updateDevicesTable();
            
            // Update timestamp
            document.getElementById('last-update').textContent = `Atualizado: ${new Date().toLocaleTimeString('pt-BR')}`;
            
            lucide.createIcons();
        }
        
        function animateValue(elementId, targetValue) {
            const element = document.getElementById(elementId);
            const startValue = parseInt(element.textContent.replace(/\D/g, '')) || 0;
            const duration = 500;
            const startTime = performance.now();
            
            function update(currentTime) {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                const easeOutQuart = 1 - Math.pow(1 - progress, 4);
                const currentValue = Math.round(startValue + (targetValue - startValue) * easeOutQuart);
                element.textContent = formatNumber(currentValue);
                
                if (progress < 1) {
                    requestAnimationFrame(update);
                }
            }
            
            requestAnimationFrame(update);
        }
        
        function filterAndSearchDevices() {
            const topIPs = dashboardData?.top_blocked_ips || [];
            let filtered = [...topIPs];
            
            // Aplicar busca
            if (currentSearchTerm.trim()) {
                const searchLower = currentSearchTerm.toLowerCase();
                filtered = filtered.filter(ip => 
                    ip.ip_address?.toLowerCase().includes(searchLower) ||
                    ip.country_code?.toLowerCase().includes(searchLower) ||
                    (ip.threat_types_count?.toString().includes(searchLower))
                );
            }
            
            // Aplicar filtro de health
            if (currentFilter !== 'all') {
                filtered = filtered.filter(ip => {
                    const healthStatus = ip.avg_threat_score > 7 ? 'bad' : ip.avg_threat_score > 4 ? 'moderate' : ip.avg_threat_score > 2 ? 'unavailable' : 'good';
                    return healthStatus === currentFilter;
                });
            }
            
            filteredDevicesData = filtered;
            updateDevicesTable();
        }
        
        function updateDevicesTable() {
            const topIPs = filteredDevicesData !== null ? filteredDevicesData : (dashboardData?.top_blocked_ips || []);
            const tbody = document.getElementById('devices-table');
            
            if (topIPs.length > 0) {
                tbody.innerHTML = topIPs.slice(0, 10).map((ip, index) => {
                    const healthStatus = ip.avg_threat_score > 7 ? 'bad' : ip.avg_threat_score > 4 ? 'moderate' : ip.avg_threat_score > 2 ? 'unavailable' : 'good';
                    const healthLabel = healthStatus === 'good' ? 'Good' : healthStatus === 'moderate' ? 'Moderate' : healthStatus === 'bad' ? 'Bad' : 'Unavailable';
                    const packetLoss = Math.min(Math.round((ip.avg_threat_score || 0) * 10), 100);
                    const packetColor = packetLoss > 50 ? 'bg-gradient-to-r from-red-500 to-red-600' : packetLoss > 25 ? 'bg-gradient-to-r from-amber-500 to-amber-600' : 'bg-white';
                    
                    return `
                        <tr class="table-row group">
                            <td class="px-3 sm:px-6 py-3 sm:py-5">
                                <div class="flex items-center gap-2 sm:gap-4">
                                    <input type="checkbox" class="cursor-pointer">
                                    <span class="status-dot status-${healthStatus}"></span>
                                    <span class="text-xs sm:text-sm text-white font-medium">${healthLabel}</span>
                                </div>
                            </td>
                            <td class="px-3 sm:px-6 py-3 sm:py-5">
                                <span class="text-xs sm:text-sm font-mono text-white break-all">${ip.ip_address}</span>
                            </td>
                            <td class="px-3 sm:px-6 py-3 sm:py-5">
                                <span class="text-xs sm:text-sm text-zinc-400">${ip.threat_types_count || 1} tipos</span>
                            </td>
                            <td class="px-3 sm:px-6 py-3 sm:py-5">
                                <span class="text-xs sm:text-sm text-zinc-400">${ip.country_code || 'Unknown'}</span>
                            </td>
                            <td class="px-3 sm:px-6 py-3 sm:py-5">
                                <div class="sparkline-container hidden sm:block">
                                    <canvas id="sparkline-${index}" width="100" height="35"></canvas>
                                </div>
                                <span class="text-xs sm:hidden text-zinc-400">-</span>
                            </td>
                            <td class="px-3 sm:px-6 py-3 sm:py-5">
                                <div class="flex items-center gap-2 sm:gap-4">
                                    <div class="packet-bar w-16 sm:w-28">
                                        <div class="packet-fill ${packetColor}" style="width: ${packetLoss}%"></div>
                                    </div>
                                    <span class="text-xs sm:text-sm text-zinc-400 font-medium">${packetLoss}%</span>
                                </div>
                            </td>
                            <td class="px-3 sm:px-6 py-3 sm:py-5">
                                <button class="text-zinc-600 hover:text-white transition-colors opacity-0 group-hover:opacity-100">
                                    <i data-lucide="more-vertical" class="w-4 h-4"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                }).join('');
                
                // Draw sparklines with improved style
                topIPs.slice(0, 10).forEach((ip, index) => {
                    const canvas = document.getElementById(`sparkline-${index}`);
                    if (canvas) {
                        const ctx = canvas.getContext('2d');
                        const data = Array.from({length: 12}, () => Math.random() * 20 + 5);
                        
                        // Create gradient
                        const gradient = ctx.createLinearGradient(0, 0, 100, 0);
                        gradient.addColorStop(0, 'rgba(255, 255, 255, 0.3)');
                        gradient.addColorStop(1, '#ffffff');
                        
                        ctx.strokeStyle = gradient;
                        ctx.lineWidth = 2;
                        ctx.lineCap = 'round';
                        ctx.lineJoin = 'round';
                        ctx.beginPath();
                        
                        data.forEach((val, i) => {
                            const x = (i / (data.length - 1)) * 100;
                            const y = 35 - (val / 25) * 30;
                            if (i === 0) ctx.moveTo(x, y);
                            else ctx.lineTo(x, y);
                        });
                        
                        ctx.stroke();
                    }
                });
                
                lucide.createIcons();
            } else {
                const message = (currentSearchTerm || currentFilter !== 'all') 
                    ? 'Nenhum dispositivo encontrado com os filtros aplicados' 
                    : 'Carregando dispositivos...';
                const icon = (currentSearchTerm || currentFilter !== 'all') ? 'search-x' : 'loader-2';
                const animate = (currentSearchTerm || currentFilter !== 'all') ? '' : 'animate-spin';
                
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" class="px-3 sm:px-6 py-12 text-center text-zinc-500">
                            <div class="flex flex-col items-center">
                                <div class="w-16 h-16 bg-white/5 rounded-2xl flex items-center justify-center mb-4">
                                    <i data-lucide="${icon}" class="w-8 h-8 ${animate}"></i>
                                </div>
                                <p class="text-sm font-medium">${message}</p>
                                ${(currentSearchTerm || currentFilter !== 'all') ? 
                                    '<button class="mt-4 text-xs text-white/60 hover:text-white underline" id="clear-filters-btn">Limpar filtros</button>' 
                                    : ''}
                            </div>
                        </td>
                    </tr>
                `;
                lucide.createIcons();
                
                // Botão para limpar filtros
                const clearFiltersBtn = document.getElementById('clear-filters-btn');
                if (clearFiltersBtn) {
                    clearFiltersBtn.addEventListener('click', function() {
                        currentSearchTerm = '';
                        currentFilter = 'all';
                        if (deviceSearchInput) deviceSearchInput.value = '';
                        filterAndSearchDevices();
                    });
                }
            }
        }
        
        // Period buttons
        document.querySelectorAll('.period-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.period-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
            });
        });
        
        // Device search and filter functionality
        const deviceSearchInput = document.getElementById('device-search');
        const deviceSearchBtn = document.getElementById('device-search-btn');
        const deviceFilterBtn = document.getElementById('device-filter-btn');
        
        if (deviceSearchInput) {
            deviceSearchInput.addEventListener('input', function() {
                currentSearchTerm = this.value;
                filterAndSearchDevices();
            });
            
            deviceSearchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    filterAndSearchDevices();
                }
            });
        }
        
        if (deviceSearchBtn) {
            deviceSearchBtn.addEventListener('click', function() {
                filterAndSearchDevices();
            });
        }
        
        if (deviceFilterBtn) {
            deviceFilterBtn.addEventListener('click', function() {
                // Modal simples de filtro
                const filters = [
                    { value: 'all', label: 'Todos' },
                    { value: 'good', label: 'Good' },
                    { value: 'moderate', label: 'Moderate' },
                    { value: 'bad', label: 'Bad' },
                    { value: 'unavailable', label: 'Unavailable' }
                ];
                
                // Criar overlay de modal
                const modal = document.createElement('div');
                modal.className = 'fixed inset-0 bg-black/80 z-50 flex items-center justify-center';
                modal.innerHTML = `
                    <div class="bg-dark-900 border border-white/10 rounded-2xl p-6 max-w-md w-full mx-4">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-xl font-bold text-white">Filtrar por Health</h3>
                            <button class="text-zinc-400 hover:text-white transition-colors" id="close-filter-modal">
                                <i data-lucide="x" class="w-5 h-5"></i>
                            </button>
                        </div>
                        <div class="space-y-2">
                            ${filters.map(filter => `
                                <button class="w-full text-left px-4 py-3 rounded-xl transition-all ${
                                    currentFilter === filter.value 
                                        ? 'bg-white/10 text-white border border-white/20' 
                                        : 'bg-white/5 text-zinc-400 hover:bg-white/10 hover:text-white border border-transparent'
                                }" data-filter="${filter.value}">
                                    ${filter.label}
                                </button>
                            `).join('')}
                        </div>
                    </div>
                `;
                
                document.body.appendChild(modal);
                lucide.createIcons();
                
                // Event listeners
                modal.querySelectorAll('[data-filter]').forEach(btn => {
                    btn.addEventListener('click', function() {
                        currentFilter = this.dataset.filter;
                        filterAndSearchDevices();
                        document.body.removeChild(modal);
                    });
                });
                
                modal.querySelector('#close-filter-modal').addEventListener('click', function() {
                    document.body.removeChild(modal);
                });
                
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        document.body.removeChild(modal);
                    }
                });
            });
        }
        
        // Initial load
        fetchDashboardStats();
        
        // Auto refresh every 3 seconds
        setInterval(fetchDashboardStats, 3000);
    </script>
</body>
</html>
