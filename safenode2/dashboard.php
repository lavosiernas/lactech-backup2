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
            font-size: 0.92em;
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
            border-radius: 12px;
            padding: 16px;
            position: relative;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        @media (min-width: 640px) {
            .stat-card {
                border-radius: 14px;
                padding: 20px;
            }
        }
        
        @media (min-width: 1024px) {
            .stat-card {
                border-radius: 16px;
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
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 16px;
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
        
        /* Toast Notifications */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 12px;
            max-width: 400px;
        }
        
        .toast {
            background: var(--bg-card);
            border: 1px solid var(--border-subtle);
            border-radius: 12px;
            padding: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.5);
            display: flex;
            align-items: start;
            gap: 12px;
            animation: slideInRight 0.3s ease-out;
            position: relative;
            overflow: hidden;
        }
        
        .toast::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: var(--toast-color, #ffffff);
        }
        
        .toast.success { --toast-color: #22c55e; }
        .toast.error { --toast-color: #ef4444; }
        .toast.warning { --toast-color: #f59e0b; }
        .toast.info { --toast-color: #3b82f6; }
        
        .toast-icon {
            flex-shrink: 0;
            width: 20px;
            height: 20px;
        }
        
        .toast-content {
            flex: 1;
        }
        
        .toast-title {
            font-weight: 600;
            color: var(--text-primary);
            font-size: 14px;
            margin-bottom: 4px;
        }
        
        .toast-message {
            font-size: 13px;
            color: var(--text-secondary);
            line-height: 1.4;
        }
        
        .toast-close {
            flex-shrink: 0;
            width: 20px;
            height: 20px;
            cursor: pointer;
            color: var(--text-muted);
            transition: color 0.2s;
        }
        
        .toast-close:hover {
            color: var(--text-primary);
        }
        
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }
        
        .toast.hiding {
            animation: slideOutRight 0.3s ease-in forwards;
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
<body x-data="{ notificationsOpen: false, sidebarOpen: false, sidebarCollapsed: false, deviceFilterOpen: false }" 
      x-init="
        $watch('sidebarCollapsed', () => { setTimeout(() => { lucide.createIcons(); }, 150) });
        $watch('sidebarOpen', () => { setTimeout(() => { lucide.createIcons(); }, 150) });
      "
      class="h-full overflow-hidden flex">

    <!-- Sidebar -->
    <aside :class="sidebarCollapsed ? 'w-20' : 'w-72'" class="sidebar h-full flex-shrink-0 flex flex-col hidden lg:flex transition-all duration-300 ease-in-out overflow-hidden">
        <!-- Logo -->
        <div class="p-4 border-b border-white/5 flex-shrink-0 relative">
            <div class="flex items-center" :class="sidebarCollapsed ? 'justify-center flex-col gap-3' : 'justify-between'">
                <div class="flex items-center gap-3" :class="sidebarCollapsed ? 'justify-center' : ''">
                    <img src="assets/img/logos (6).png" alt="SafeNode Logo" class="w-8 h-8 object-contain flex-shrink-0">
                    <div x-show="!sidebarCollapsed" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-x-2" x-transition:enter-end="opacity-100 translate-x-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-x-0" x-transition:leave-end="opacity-0 -translate-x-2" class="overflow-hidden whitespace-nowrap">
                        <h1 class="font-bold text-white text-xl tracking-tight">SafeNode</h1>
                        <p class="text-xs text-zinc-500 font-medium">Security Platform</p>
                    </div>
                </div>
                <button @click="sidebarCollapsed = !sidebarCollapsed; setTimeout(() => lucide.createIcons(), 50)" class="text-zinc-600 hover:text-zinc-400 transition-colors flex-shrink-0" :class="sidebarCollapsed ? 'mt-2' : ''">
                    <i :data-lucide="sidebarCollapsed ? 'chevrons-right' : 'chevrons-left'" class="w-5 h-5"></i>
                </button>
            </div>
        </div>
        
        <!-- Navigation -->
        <nav class="flex-1 p-4 space-y-1 overflow-y-auto overflow-x-hidden">
            <p x-show="!sidebarCollapsed" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="text-xs font-semibold text-zinc-600 uppercase tracking-wider mb-3 px-3 whitespace-nowrap">Menu Principal</p>
            
            <a href="dashboard.php" class="nav-item active" :class="sidebarCollapsed ? 'justify-center px-2' : ''" :title="sidebarCollapsed ? 'Home' : ''">
                <i data-lucide="layout-dashboard" class="w-5 h-5 flex-shrink-0"></i>
                <span x-show="!sidebarCollapsed" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-x-2" x-transition:enter-end="opacity-100 translate-x-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-x-0" x-transition:leave-end="opacity-0 -translate-x-2" class="font-medium whitespace-nowrap">Home</span>
            </a>
            <a href="sites.php" class="nav-item" :class="sidebarCollapsed ? 'justify-center px-2' : ''" :title="sidebarCollapsed ? 'Gerenciar Sites' : ''">
                <i data-lucide="globe" class="w-5 h-5 flex-shrink-0"></i>
                <span x-show="!sidebarCollapsed" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-x-2" x-transition:enter-end="opacity-100 translate-x-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-x-0" x-transition:leave-end="opacity-0 -translate-x-2" class="font-medium whitespace-nowrap">Gerenciar Sites</span>
            </a>
            <a href="security-analytics.php" class="nav-item" :class="sidebarCollapsed ? 'justify-center px-2' : ''" :title="sidebarCollapsed ? 'Network' : ''">
                <i data-lucide="activity" class="w-5 h-5 flex-shrink-0"></i>
                <span x-show="!sidebarCollapsed" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-x-2" x-transition:enter-end="opacity-100 translate-x-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-x-0" x-transition:leave-end="opacity-0 -translate-x-2" class="font-medium whitespace-nowrap">Network</span>
            </a>
            <a href="behavior-analysis.php" class="nav-item" :class="sidebarCollapsed ? 'justify-center px-2' : ''" :title="sidebarCollapsed ? 'Kubernetes' : ''">
                <i data-lucide="cpu" class="w-5 h-5 flex-shrink-0"></i>
                <span x-show="!sidebarCollapsed" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-x-2" x-transition:enter-end="opacity-100 translate-x-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-x-0" x-transition:leave-end="opacity-0 -translate-x-2" class="font-medium whitespace-nowrap">Kubernetes</span>
            </a>
            <a href="logs.php" class="nav-item" :class="sidebarCollapsed ? 'justify-center px-2' : ''" :title="sidebarCollapsed ? 'Explorar' : ''">
                <i data-lucide="compass" class="w-5 h-5 flex-shrink-0"></i>
                <span x-show="!sidebarCollapsed" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-x-2" x-transition:enter-end="opacity-100 translate-x-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-x-0" x-transition:leave-end="opacity-0 -translate-x-2" class="font-medium whitespace-nowrap">Explorar</span>
            </a>
            <a href="suspicious-ips.php" class="nav-item" :class="sidebarCollapsed ? 'justify-center px-2' : ''" :title="sidebarCollapsed ? 'Analisar' : ''">
                <i data-lucide="bar-chart-3" class="w-5 h-5 flex-shrink-0"></i>
                <span x-show="!sidebarCollapsed" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-x-2" x-transition:enter-end="opacity-100 translate-x-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-x-0" x-transition:leave-end="opacity-0 -translate-x-2" class="font-medium whitespace-nowrap">Analisar</span>
            </a>
            <a href="attacked-targets.php" class="nav-item" :class="sidebarCollapsed ? 'justify-center px-2' : ''" :title="sidebarCollapsed ? 'Grupos' : ''">
                <i data-lucide="users-2" class="w-5 h-5 flex-shrink-0"></i>
                <span x-show="!sidebarCollapsed" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-x-2" x-transition:enter-end="opacity-100 translate-x-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-x-0" x-transition:leave-end="opacity-0 -translate-x-2" class="font-medium whitespace-nowrap">Grupos</span>
            </a>
            
            <div class="pt-4 mt-4 border-t border-white/5">
                <p x-show="!sidebarCollapsed" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="text-xs font-semibold text-zinc-600 uppercase tracking-wider mb-3 px-3 whitespace-nowrap">Sistema</p>
                <a href="human-verification.php" class="nav-item" :class="sidebarCollapsed ? 'justify-center px-2' : ''" :title="sidebarCollapsed ? 'Verificação Humana' : ''">
                    <i data-lucide="shield-check" class="w-5 h-5 flex-shrink-0"></i>
                    <span x-show="!sidebarCollapsed" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-x-2" x-transition:enter-end="opacity-100 translate-x-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-x-0" x-transition:leave-end="opacity-0 -translate-x-2" class="font-medium whitespace-nowrap">Verificação Humana</span>
                </a>
                <a href="settings.php" class="nav-item" :class="sidebarCollapsed ? 'justify-center px-2' : ''" :title="sidebarCollapsed ? 'Configurações' : ''">
                    <i data-lucide="settings-2" class="w-5 h-5 flex-shrink-0"></i>
                    <span x-show="!sidebarCollapsed" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-x-2" x-transition:enter-end="opacity-100 translate-x-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-x-0" x-transition:leave-end="opacity-0 -translate-x-2" class="font-medium whitespace-nowrap">Configurações</span>
                </a>
                <a href="help.php" class="nav-item" :class="sidebarCollapsed ? 'justify-center px-2' : ''" :title="sidebarCollapsed ? 'Ajuda' : ''">
                    <i data-lucide="life-buoy" class="w-5 h-5 flex-shrink-0"></i>
                    <span x-show="!sidebarCollapsed" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-x-2" x-transition:enter-end="opacity-100 translate-x-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-x-0" x-transition:leave-end="opacity-0 -translate-x-2" class="font-medium whitespace-nowrap">Ajuda</span>
                </a>
                <a href="documentation.php" class="nav-item" :class="sidebarCollapsed ? 'justify-center px-2' : ''" :title="sidebarCollapsed ? 'Documentação' : ''">
                    <i data-lucide="book-open" class="w-5 h-5 flex-shrink-0"></i>
                    <span x-show="!sidebarCollapsed" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-x-2" x-transition:enter-end="opacity-100 translate-x-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-x-0" x-transition:leave-end="opacity-0 -translate-x-2" class="font-medium whitespace-nowrap">Documentação</span>
                </a>
            </div>
        </nav>
        
        <!-- Upgrade Card -->
        <div class="p-4 flex-shrink-0" x-show="!sidebarCollapsed" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-2">
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
            
            <a href="dashboard.php" class="nav-item" @click="sidebarOpen = false">
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
                <a href="settings.php" class="nav-item" @click="sidebarOpen = false">
                    <i data-lucide="settings-2" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="font-medium whitespace-nowrap">Configurações</span>
                </a>
                <a href="help.php" class="nav-item" @click="sidebarOpen = false">
                    <i data-lucide="life-buoy" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="font-medium whitespace-nowrap">Ajuda</span>
                </a>
                <a href="documentation.php" class="nav-item" @click="sidebarOpen = false">
                    <i data-lucide="book-open" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="font-medium whitespace-nowrap">Documentação</span>
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
                    <p class="text-sm text-zinc-500 font-mono mt-0.5"><?php echo htmlspecialchars($selectedSite['domain'] ?? ''); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <!-- Search -->
                <div class="relative hidden md:block">
                    <i data-lucide="search" class="w-4 h-4 absolute left-4 top-1/2 -translate-y-1/2 text-zinc-500"></i>
                    <input type="text" placeholder="Buscar..." class="search-input">
                </div>
                
                <!-- Notifications -->
                <button @click="notificationsOpen = !notificationsOpen" class="relative p-3 text-zinc-400 hover:text-white hover:bg-white/5 rounded-xl transition-all">
                    <i data-lucide="bell" class="w-5 h-5"></i>
                    <span id="notification-badge" class="absolute top-1 right-1 min-w-[18px] h-[18px] bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center px-1.5 border-2 border-dark-900 hidden">0</span>
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
            
            <!-- Development Notice Banner -->
            <div class="mb-8 glass rounded-2xl p-5 border-blue-500/30 bg-gradient-to-r from-blue-500/10 via-blue-500/5 to-transparent flex items-center gap-4">
                <div class="w-10 h-10 rounded-xl bg-blue-500/20 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="code" class="w-5 h-5 text-blue-400"></i>
                </div>
                <div class="flex-1">
                    <p class="font-semibold text-white text-sm mb-1">Sistema em Desenvolvimento Constante</p>
                    <p class="text-xs text-zinc-400">O SafeNode está em evolução contínua. Novas funcionalidades e melhorias são adicionadas regularmente para garantir a melhor experiência e segurança.</p>
                </div>
            </div>
            
            <!-- Stats Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 lg:gap-5 mb-8">
                <!-- Total Requests -->
                <div class="stat-card group">
                    <div class="flex items-center justify-between mb-1">
                        <p class="text-xs sm:text-sm font-medium text-zinc-400">Total de Requisições</p>
                    </div>
                    <div class="flex items-end justify-between mt-3 sm:mt-4">
                        <p id="total-requests" class="text-2xl sm:text-3xl lg:text-4xl font-bold text-white tracking-tight">-</p>
                        <span id="requests-change" class="text-[10px] sm:text-xs font-semibold text-white bg-white/10 px-1.5 sm:px-2.5 py-0.5 sm:py-1 rounded-lg"></span>
                    </div>
                    <p class="text-[10px] sm:text-xs text-zinc-600 mt-2 sm:mt-3">comparado a ontem</p>
            </div>

                <!-- Blocked -->
                <div class="stat-card group">
                    <div class="flex items-center justify-between mb-1">
                        <p class="text-xs sm:text-sm font-medium text-zinc-400">Requisições Bloqueadas</p>
                    </div>
                    <div class="flex items-end justify-between mt-3 sm:mt-4">
                        <p id="blocked-requests" class="text-2xl sm:text-3xl lg:text-4xl font-bold text-white tracking-tight">-</p>
                        <span id="blocked-change" class="text-[10px] sm:text-xs font-semibold text-red-400 bg-red-500/10 px-1.5 sm:px-2.5 py-0.5 sm:py-1 rounded-lg"></span>
                    </div>
                    <p class="text-[10px] sm:text-xs text-zinc-600 mt-2 sm:mt-3">Taxa: <span id="block-rate" class="text-red-400 font-medium">-</span>%</p>
                </div>
                
                <!-- Unique IPs -->
                <div class="stat-card group">
                    <div class="flex items-center justify-between mb-1">
                        <p class="text-xs sm:text-sm font-medium text-zinc-400">Visitantes Únicos</p>
                    </div>
                    <div class="flex items-end justify-between mt-3 sm:mt-4">
                        <p id="unique-ips" class="text-2xl sm:text-3xl lg:text-4xl font-bold text-white tracking-tight">-</p>
                        <span id="ips-change" class="text-[10px] sm:text-xs font-semibold text-white bg-white/10 px-1.5 sm:px-2.5 py-0.5 sm:py-1 rounded-lg"></span>
                    </div>
                    <p class="text-[10px] sm:text-xs text-zinc-600 mt-2 sm:mt-3">últimas 24h</p>
                </div>
                
                <!-- Active Blocks -->
                <div class="stat-card group">
                    <div class="flex items-center justify-between mb-1">
                        <p class="text-xs sm:text-sm font-medium text-zinc-400">IPs Bloqueados</p>
                    </div>
                    <div class="flex items-end justify-between mt-3 sm:mt-4">
                        <p id="active-blocks" class="text-2xl sm:text-3xl lg:text-4xl font-bold text-white tracking-tight">-</p>
                        <span class="text-[10px] sm:text-xs font-semibold text-amber-400 bg-amber-500/10 px-1.5 sm:px-2.5 py-0.5 sm:py-1 rounded-lg">ativos</span>
                    </div>
                    <p class="text-[10px] sm:text-xs text-zinc-600 mt-2 sm:mt-3">últimos 7 dias</p>
                </div>
            </div>
            
            <!-- Charts Row -->
            <div class="grid grid-cols-1 lg:grid-cols-5 gap-6 mb-8">
                <!-- Entities Overview (Donut Chart) -->
                <div class="lg:col-span-2 chart-card">
                    <div class="flex items-center justify-between mb-8">
                        <h3 class="text-lg font-semibold text-white">Visão Geral de Ameaças</h3>
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
                            <span class="text-sm text-zinc-400">Bom</span>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <span class="w-3 h-3 rounded-full bg-amber-500"></span>
                            <span class="text-sm text-zinc-400">Moderado</span>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <span class="w-3 h-3 rounded-full bg-violet-500"></span>
                            <span class="text-sm text-zinc-400">Ruim</span>
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
            <div class="table-card mb-8" style="overflow: visible !important;">
                <div class="table-header p-4 sm:p-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4" style="overflow: visible !important; position: relative;">
                    <h3 class="text-base sm:text-lg font-semibold text-white">Dispositivos de Rede</h3>
                    <div class="flex items-center gap-2 sm:gap-3 w-full sm:w-auto relative">
                        <div class="relative flex-1 sm:flex-initial sm:w-56">
                            <i data-lucide="search" class="w-4 h-4 absolute left-3 sm:left-4 top-1/2 -translate-y-1/2 text-zinc-500"></i>
                            <input type="text" id="device-search" placeholder="Buscar por nome" class="bg-white/5 border border-white/10 rounded-xl py-2 sm:py-2.5 pl-10 sm:pl-11 pr-3 sm:pr-4 text-sm text-white placeholder-zinc-500 focus:outline-none focus:border-white/30 focus:ring-2 focus:ring-white/10 w-full transition-all">
                        </div>
                        <button class="btn-ghost flex items-center justify-center gap-2 text-xs sm:text-sm py-2 sm:py-2.5 px-3 sm:px-4 flex-shrink-0">
                            <span class="hidden sm:inline">Buscar</span>
                            <i data-lucide="search" class="w-4 h-4 sm:hidden"></i>
                        </button>
                        <div class="relative">
                            <button @click="deviceFilterOpen = !deviceFilterOpen" class="btn-ghost flex items-center justify-center gap-2 text-xs sm:text-sm py-2 sm:py-2.5 px-3 sm:px-4 flex-shrink-0">
                                <i data-lucide="sliders-horizontal" class="w-4 h-4"></i>
                                <span class="hidden sm:inline">Filtrar</span>
                            </button>
                            
                            <!-- Filter Modal -->
                            <div x-show="deviceFilterOpen" 
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 scale-95"
                                 x-transition:enter-end="opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-150"
                                 x-transition:leave-start="opacity-100 scale-100"
                                 x-transition:leave-end="opacity-0 scale-95"
                                 @click.away="deviceFilterOpen = false"
                                 @click.stop
                                 class="absolute top-full right-0 mt-2 w-72 bg-dark-900 border border-white/10 rounded-xl shadow-2xl z-[9999] p-4"
                                 x-cloak
                                 style="display: none;">
                                <div class="space-y-4">
                                    <div class="flex items-center justify-between mb-2">
                                        <h4 class="text-sm font-semibold text-white">Filtros</h4>
                                        <button @click="deviceFilterOpen = false" class="text-white hover:text-zinc-300 transition-colors bg-white/10 hover:bg-white/20 rounded-lg p-1.5">
                                            <i data-lucide="x" class="w-4 h-4"></i>
                                        </button>
                                    </div>
                                    
                                    <div class="relative">
                                        <label class="block text-xs text-zinc-400 mb-2">Status de Health</label>
                                        <select id="filter-health" class="w-full bg-zinc-800/80 border-2 border-white/50 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-white/70 appearance-none cursor-pointer pr-8 hover:bg-zinc-700/80">
                                            <option value="">Todos</option>
                                            <option value="good">Bom</option>
                                            <option value="moderate">Moderado</option>
                                            <option value="bad">Ruim</option>
                                            <option value="unavailable">Indisponível</option>
                                        </select>
                                        <i data-lucide="chevron-down" class="w-4 h-4 absolute right-3 top-8 text-white pointer-events-none"></i>
                                    </div>
                                    
                                    <div class="relative">
                                        <label class="block text-xs text-zinc-400 mb-2">Tipo</label>
                                        <select id="filter-type" class="w-full bg-zinc-800/80 border-2 border-white/50 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-white/70 appearance-none cursor-pointer pr-8 hover:bg-zinc-700/80">
                                            <option value="">Todos</option>
                                            <option value="1">1 tipo</option>
                                            <option value="2">2 tipos</option>
                                            <option value="3">3+ tipos</option>
                                        </select>
                                        <i data-lucide="chevron-down" class="w-4 h-4 absolute right-3 top-8 text-white pointer-events-none"></i>
                                    </div>
                                    
                                    <div class="relative">
                                        <label class="block text-xs text-zinc-400 mb-2">Packet Loss</label>
                                        <select id="filter-packet" class="w-full bg-zinc-800/80 border-2 border-white/50 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-white/70 appearance-none cursor-pointer pr-8 hover:bg-zinc-700/80">
                                            <option value="">Todos</option>
                                            <option value="low">Baixo (0-25%)</option>
                                            <option value="medium">Médio (26-50%)</option>
                                            <option value="high">Alto (51%+)</option>
                                        </select>
                                        <i data-lucide="chevron-down" class="w-4 h-4 absolute right-3 top-8 text-white pointer-events-none"></i>
                                    </div>
                                    
                                    <div class="flex gap-2 pt-2">
                                        <button @click="deviceFilterOpen = false; setTimeout(() => applyDeviceFilters(), 100);" class="flex-1 bg-white text-black py-2 text-sm font-semibold rounded-lg hover:bg-white/90 transition-colors">
                                            Aplicar
                                        </button>
                                        <button @click="deviceFilterOpen = false; setTimeout(() => clearDeviceFilters(), 100);" class="flex-1 bg-white/10 text-white border border-white/20 py-2 text-sm font-semibold rounded-lg hover:bg-white/20 transition-colors">
                                            Limpar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Desktop Table -->
                <div class="hidden lg:block overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-xs text-zinc-500 uppercase tracking-wider border-b border-white/5">
                                <th class="px-6 py-4 font-semibold">Health</th>
                                <th class="px-6 py-4 font-semibold">Nome</th>
                                <th class="px-6 py-4 font-semibold">Tipo</th>
                                <th class="px-6 py-4 font-semibold">Origem</th>
                                <th class="px-6 py-4 font-semibold">Response Time</th>
                                <th class="px-6 py-4 font-semibold">Packet Loss</th>
                                <th class="px-6 py-4 font-semibold">Ação</th>
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
                
                <!-- Mobile Cards -->
                <div id="devices-cards" class="lg:hidden p-4 space-y-3">
                    <div class="text-center py-10 text-zinc-500">
                        <div class="w-12 h-12 rounded-2xl bg-white/5 flex items-center justify-center mx-auto mb-3">
                            <i data-lucide="loader-2" class="w-6 h-6 animate-spin"></i>
                        </div>
                        <p class="text-sm font-medium">Carregando dispositivos...</p>
                    </div>
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
            <div class="flex-1 overflow-y-auto p-5" id="notifications-list">
                <div class="text-center py-16" id="notifications-empty">
                    <div class="w-20 h-20 bg-white/5 rounded-3xl flex items-center justify-center mx-auto mb-5">
                        <i data-lucide="bell-off" class="w-10 h-10 text-zinc-600"></i>
                    </div>
                    <p class="text-sm text-zinc-400 font-medium">Nenhuma notificação</p>
                    <p class="text-xs text-zinc-600 mt-1">Você será notificado de novos eventos</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toast-container" class="toast-container"></div>

    <script>
        // Initialize Lucide Icons
        lucide.createIcons();
        
        // Toast Notification System
        function showToast(title, message, type = 'info', duration = 5000) {
            const container = document.getElementById('toast-container');
            if (!container) return;
            
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            
            const icons = {
                success: 'check-circle-2',
                error: 'alert-circle',
                warning: 'alert-triangle',
                info: 'info'
            };
            
            toast.innerHTML = `
                <i data-lucide="${icons[type] || 'info'}" class="toast-icon text-${type === 'success' ? 'green' : type === 'error' ? 'red' : type === 'warning' ? 'amber' : 'blue'}-400"></i>
                <div class="toast-content">
                    <div class="toast-title">${title}</div>
                    <div class="toast-message">${message}</div>
                </div>
                <button class="toast-close" onclick="this.closest('.toast').remove()">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            `;
            
            container.appendChild(toast);
            lucide.createIcons();
            
            if (duration > 0) {
                setTimeout(() => {
                    toast.classList.add('hiding');
                    setTimeout(() => toast.remove(), 300);
                }, duration);
            }
        }
        
        // Notification System
        let unreadNotifications = 0;
        let lastNotificationCheck = Date.now();
        
        function updateNotificationBadge(count) {
            const badge = document.getElementById('notification-badge');
            if (!badge) return;
            
            unreadNotifications = count;
            if (count > 0) {
                badge.textContent = count > 99 ? '99+' : count;
                badge.classList.remove('hidden');
            } else {
                badge.classList.add('hidden');
            }
        }
        
        function fetchNotifications() {
            fetch('api/notifications.php?unread=1')
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.count !== undefined) {
                        const newCount = parseInt(data.count);
                        if (newCount > unreadNotifications) {
                            // Novas notificações
                            const diff = newCount - unreadNotifications;
                            if (diff > 0) {
                                showToast(
                                    'Nova Ameaça Detectada',
                                    `${diff} nova${diff > 1 ? 's' : ''} ameaça${diff > 1 ? 's' : ''} detectada${diff > 1 ? 's' : ''}`,
                                    'warning',
                                    6000
                                );
                            }
                        }
                        updateNotificationBadge(newCount);
                        if (document.getElementById('notifications-list')) {
                            loadNotifications();
                        }
                    }
                })
                .catch(err => console.error('Erro ao buscar notificações:', err));
        }
        
        function loadNotifications() {
            fetch('api/notifications.php?limit=20')
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.notifications) {
                        renderNotifications(data.notifications);
                    }
                })
                .catch(err => console.error('Erro ao carregar notificações:', err));
        }
        
        function renderNotifications(notifications) {
            const container = document.getElementById('notifications-list');
            const empty = document.getElementById('notifications-empty');
            
            if (!container) return;
            
            if (notifications.length === 0) {
                if (empty) empty.style.display = 'block';
                container.innerHTML = '';
                container.appendChild(empty);
                return;
            }
            
            if (empty) empty.style.display = 'none';
            
            container.innerHTML = notifications.map(notif => {
                const icons = {
                    threat: 'shield-alert',
                    blocked: 'ban',
                    warning: 'alert-triangle',
                    info: 'info',
                    success: 'check-circle-2'
                };
                
                const colors = {
                    threat: 'text-red-400',
                    blocked: 'text-red-500',
                    warning: 'text-amber-400',
                    info: 'text-blue-400',
                    success: 'text-green-400'
                };
                
                const timeAgo = getTimeAgo(notif.created_at);
                
                return `
                    <div class="p-4 rounded-xl bg-white/5 border border-white/10 hover:bg-white/10 transition-all mb-3 ${notif.is_read ? 'opacity-60' : ''}">
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center flex-shrink-0">
                                <i data-lucide="${icons[notif.type] || 'info'}" class="w-5 h-5 ${colors[notif.type] || 'text-zinc-400'}"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-2 mb-1">
                                    <h4 class="text-sm font-semibold text-white">${escapeHtml(notif.title)}</h4>
                                    ${!notif.is_read ? '<span class="w-2 h-2 bg-blue-500 rounded-full flex-shrink-0 mt-1"></span>' : ''}
                                </div>
                                <p class="text-xs text-zinc-400 mb-2">${escapeHtml(notif.message)}</p>
                                <p class="text-[10px] text-zinc-600">${timeAgo}</p>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
            
            lucide.createIcons();
        }
        
        function getTimeAgo(dateString) {
            const now = new Date();
            const date = new Date(dateString);
            const diff = Math.floor((now - date) / 1000);
            
            if (diff < 60) return 'Agora';
            if (diff < 3600) return `${Math.floor(diff / 60)}m atrás`;
            if (diff < 86400) return `${Math.floor(diff / 3600)}h atrás`;
            if (diff < 604800) return `${Math.floor(diff / 86400)}d atrás`;
            return date.toLocaleDateString('pt-BR');
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Check for new threats and show alerts
        function checkForThreats() {
            fetch('api/dashboard-stats.php')
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.data) {
                        const criticalThreats = data.data.today?.threat_analysis?.critical_threats || 0;
                        const recentLogs = data.data.event_logs || [];
                        
                        // Verificar se há novas ameaças críticas
                        recentLogs.forEach(log => {
                            if (log.is_critical && !log.is_read) {
                                showToast(
                                    'Ameaça Crítica Detectada',
                                    `IP ${log.ip_address} - ${log.threat_type || 'Ameaça desconhecida'}`,
                                    'error',
                                    8000
                                );
                            }
                        });
                    }
                })
                .catch(err => console.error('Erro ao verificar ameaças:', err));
        }
        
        // Carregar notificações ao abrir o painel
        document.addEventListener('DOMContentLoaded', function() {
            fetchNotifications();
            loadNotifications();
            checkForThreats();
            
            // Atualizar notificações a cada 5 segundos
            setInterval(fetchNotifications, 5000);
            setInterval(checkForThreats, 10000);
        });

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
            
            // Verificar se Chart.js está carregado
            if (typeof Chart === 'undefined') {
                console.error('Chart.js não está carregado. Tentando novamente...');
                setTimeout(initEntitiesChart, 200);
                return;
            }
            
            const ChartLib = Chart;
            
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
                entitiesChart = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Bom', 'Moderado', 'Ruim'],
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
            
            // Dados iniciais vazios - serão preenchidos pela API
            const initialData = [];
            const initialLabels = [];
            
            // Valor padrão para o eixo Y
            const yAxisMax = 100;
            
            // Create gradient for highlighted bars
            const highlightGradient = ctx.createLinearGradient(0, 0, 0, 200);
            highlightGradient.addColorStop(0, '#ffffff');
            highlightGradient.addColorStop(1, '#e5e5e5');
            
            anomaliesChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: initialLabels,
                    datasets: [{
                        data: initialData,
                        backgroundColor: function(context) {
                            const index = context.dataIndex;
                            const value = context.parsed?.y || 0;
                            // Destacar barras com valores altos (acima da média)
                            if (value > 0) {
                                const avg = initialData.length > 0 
                                    ? initialData.reduce((a, b) => a + b, 0) / initialData.length 
                                    : 0;
                                if (value > avg * 1.5) {
                                return highlightGradient;
                                }
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
                try {
                initEntitiesChart();
                    // Aguardar um pouco para garantir que o gráfico foi criado
                    setTimeout(() => {
                        if (entitiesChart) {
                        entitiesChart.data.datasets[0].data = chartData;
                        entitiesChart.update('active');
                        } else {
                            console.error('Falha ao criar entitiesChart após reinicialização');
                        }
                    }, 200);
                } catch (error) {
                    console.error('Erro ao reinicializar entitiesChart:', error);
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
            
            // Update Anomalies Chart with hourly data
            const hourlyStats = dashboardData.hourly_stats || {};
            if (anomaliesChart) {
                const hours = Object.keys(hourlyStats).sort();
                const labels = hours.map(h => h + 'h');
                const data = hours.map(h => hourlyStats[h].blocked || 0);
                
                if (data.length > 0) {
                    anomaliesChart.data.labels = labels;
                    anomaliesChart.data.datasets[0].data = data;
                    
                    // Recalcular max do eixo Y
                    const maxValue = Math.max(...data, 0);
                    const yAxisMax = maxValue > 0 ? Math.ceil((maxValue * 1.2) / 100) * 100 : 100;
                    anomaliesChart.options.scales.y.max = yAxisMax;
                    anomaliesChart.options.scales.y.ticks.stepSize = Math.max(50, Math.ceil(yAxisMax / 5));
                    
                    anomaliesChart.update('active');
                }
            }
            
            // Update Devices Table with real data
            const devicesTable = document.getElementById('devices-table');
            if (devicesTable) {
                // Buscar IPs únicos das últimas 24h como "dispositivos"
                const uniqueIPs = new Set();
                const logsForDevices = dashboardData.event_logs || dashboardData.recent_logs || [];
                const topIPs = dashboardData.top_blocked_ips || [];
                
                // Combinar IPs de logs recentes e top IPs
                logsForDevices.forEach(log => {
                    if (log.ip_address) uniqueIPs.add(log.ip_address);
                });
                topIPs.forEach(ip => {
                    if (ip.ip_address) uniqueIPs.add(ip.ip_address);
                });
                
                const devices = Array.from(uniqueIPs).slice(0, 10).map(ip => {
                    // Encontrar dados do IP nos logs
                    const ipLogs = logsForDevices.filter(log => log.ip_address === ip);
                    const topIPData = topIPs.find(tip => tip.ip_address === ip);
                    
                    const blockedCount = ipLogs.filter(log => log.action_taken === 'blocked').length;
                    const totalRequests = ipLogs.length || (topIPData?.block_count || 0);
                    const threatScore = topIPData?.avg_threat_score || 0;
                    
                    // Determinar health baseado em threat score e bloqueios
                    let health = 'good';
                    let healthColor = 'text-emerald-400';
                    if (threatScore >= 70 || blockedCount > 5) {
                        health = 'critical';
                        healthColor = 'text-red-400';
                    } else if (threatScore >= 50 || blockedCount > 0) {
                        health = 'warning';
                        healthColor = 'text-amber-400';
                    }
                    
                    return {
                        ip: ip,
                        health: health,
                        healthColor: healthColor,
                        totalRequests: totalRequests,
                        blockedCount: blockedCount,
                        threatScore: threatScore
                    };
                });
                
                if (devices.length > 0) {
                    devicesTable.innerHTML = devices.map(device => `
                        <tr class="table-row">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full ${device.healthColor}"></span>
                                    <span class="text-xs font-semibold ${device.healthColor} uppercase">${device.health}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm font-mono text-white">${device.ip}</p>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-xs text-zinc-400">IP Address</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-xs text-zinc-400">Network</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-xs text-zinc-400">-</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-xs ${device.blockedCount > 0 ? 'text-red-400' : 'text-zinc-400'}">${device.blockedCount} bloqueios</span>
                            </td>
                            <td class="px-6 py-4">
                                <button class="text-xs text-zinc-400 hover:text-white transition-colors">Ver</button>
                            </td>
                        </tr>
                    `).join('');
                } else {
                    devicesTable.innerHTML = `
                        <tr class="table-row">
                            <td colspan="7" class="px-6 py-12 text-center text-zinc-500">
                                <div class="flex flex-col items-center">
                                    <div class="w-12 h-12 rounded-2xl bg-white/5 flex items-center justify-center mb-3">
                                        <i data-lucide="network" class="w-6 h-6"></i>
                                    </div>
                                    <p class="text-sm font-medium">Nenhum dispositivo encontrado</p>
                                </div>
                            </td>
                        </tr>
                    `;
                }
                lucide.createIcons();
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
            
            // Update devices table
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
        
        function updateDevicesTable() {
            const topIPs = dashboardData?.top_blocked_ips || [];
            // Apply filters if any are set
            if (deviceFilters.health || deviceFilters.type || deviceFilters.packet || deviceFilters.search) {
                filterDevices();
                return;
            }
            
            const tbody = document.getElementById('devices-table');
            const cardsContainer = document.getElementById('devices-cards');
            
            if (topIPs.length > 0) {
                // Desktop Table
                tbody.innerHTML = topIPs.slice(0, 6).map((ip, index) => {
                    const healthStatus = ip.avg_threat_score > 7 ? 'bad' : ip.avg_threat_score > 4 ? 'moderate' : ip.avg_threat_score > 2 ? 'unavailable' : 'good';
                    const healthLabel = healthStatus === 'good' ? 'Bom' : healthStatus === 'moderate' ? 'Moderado' : healthStatus === 'bad' ? 'Ruim' : 'Indisponível';
                    const packetLoss = Math.min(Math.round((ip.avg_threat_score || 0) * 10), 100);
                    const packetColor = packetLoss > 50 ? 'bg-gradient-to-r from-red-500 to-red-600' : packetLoss > 25 ? 'bg-gradient-to-r from-amber-500 to-amber-600' : 'bg-white';
                    
                    return `
                        <tr class="table-row group">
                            <td class="px-6 py-5">
                                <div class="flex items-center gap-4">
                                    <input type="checkbox" class="cursor-pointer">
                                    <span class="status-dot status-${healthStatus}"></span>
                                    <span class="text-sm text-white font-medium">${healthLabel}</span>
                    </div>
                            </td>
                            <td class="px-6 py-5">
                                <span class="text-sm font-mono text-white">${ip.ip_address}</span>
                            </td>
                            <td class="px-6 py-5">
                                <span class="text-sm text-zinc-400">${ip.threat_types_count || 1} tipos</span>
                            </td>
                            <td class="px-6 py-5">
                                <span class="text-sm text-zinc-400">${ip.country_code || 'Unknown'}</span>
                            </td>
                            <td class="px-6 py-5">
                                <div class="sparkline-container">
                                    <canvas id="sparkline-${index}" width="100" height="35"></canvas>
            </div>
                            </td>
                            <td class="px-6 py-5">
                                <div class="flex items-center gap-4">
                                    <div class="packet-bar w-28">
                                        <div class="packet-fill ${packetColor}" style="width: ${packetLoss}%"></div>
        </div>
                                    <span class="text-sm text-zinc-400 font-medium">${packetLoss}%</span>
    </div>
                            </td>
                        </tr>
                    `;
                }).join('');
                
                // Draw sparklines with improved style
                topIPs.slice(0, 6).forEach((ip, index) => {
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
                
                // Mobile Cards
                if (cardsContainer) {
                    cardsContainer.innerHTML = topIPs.slice(0, 6).map((ip, index) => {
                        const healthStatus = ip.avg_threat_score > 7 ? 'bad' : ip.avg_threat_score > 4 ? 'moderate' : ip.avg_threat_score > 2 ? 'unavailable' : 'good';
                        const healthLabel = healthStatus === 'good' ? 'Bom' : healthStatus === 'moderate' ? 'Moderado' : healthStatus === 'bad' ? 'Ruim' : 'Indisponível';
                        const healthColor = healthStatus === 'good' ? 'text-green-400' : healthStatus === 'moderate' ? 'text-amber-400' : healthStatus === 'bad' ? 'text-red-400' : 'text-zinc-400';
                        const packetLoss = Math.min(Math.round((ip.avg_threat_score || 0) * 10), 100);
                        const packetColor = packetLoss > 50 ? 'bg-red-500' : packetLoss > 25 ? 'bg-amber-500' : 'bg-white';
                        
                        return `
                            <div class="bg-white/5 border border-white/10 rounded-xl p-4 space-y-3">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <span class="status-dot status-${healthStatus}"></span>
                                        <span class="text-sm font-medium ${healthColor}">${healthLabel}</span>
                                    </div>
                                    <input type="checkbox" class="cursor-pointer">
                                </div>
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs text-zinc-500">Nome</span>
                                        <span class="text-sm font-mono text-white">${ip.ip_address}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs text-zinc-500">Tipo</span>
                                        <span class="text-sm text-zinc-400">${ip.threat_types_count || 1} tipos</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs text-zinc-500">Origem</span>
                                        <span class="text-sm text-zinc-400">${ip.country_code || 'Unknown'}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs text-zinc-500">Packet Loss</span>
                                        <div class="flex items-center gap-2">
                                            <div class="packet-bar w-20 h-2">
                                                <div class="packet-fill ${packetColor} h-full rounded" style="width: ${packetLoss}%"></div>
                                            </div>
                                            <span class="text-xs text-zinc-400 font-medium">${packetLoss}%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                    }).join('');
                }
                
                lucide.createIcons();
            } else {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-zinc-500">
                            <div class="flex flex-col items-center">
                                <div class="w-16 h-16 bg-white/5 rounded-2xl flex items-center justify-center mb-4">
                                    <i data-lucide="server-off" class="w-8 h-8"></i>
                                </div>
                                <p class="text-sm font-medium">Nenhum dispositivo encontrado</p>
                            </div>
                        </td>
                    </tr>
                `;
                
                if (cardsContainer) {
                    cardsContainer.innerHTML = `
                        <div class="text-center py-10 text-zinc-500">
                            <div class="w-12 h-12 rounded-2xl bg-white/5 flex items-center justify-center mx-auto mb-3">
                                <i data-lucide="server-off" class="w-6 h-6"></i>
                            </div>
                            <p class="text-sm font-medium">Nenhum dispositivo encontrado</p>
                        </div>
                    `;
                }
                
                lucide.createIcons();
            }
        }
        
        // Device filters
        let deviceFilters = {
            health: '',
            type: '',
            packet: '',
            search: ''
        };
        
        function applyDeviceFilters() {
            deviceFilters.health = document.getElementById('filter-health')?.value || '';
            deviceFilters.type = document.getElementById('filter-type')?.value || '';
            deviceFilters.packet = document.getElementById('filter-packet')?.value || '';
            deviceFilters.search = document.getElementById('device-search')?.value || '';
            
            filterDevices();
            
            // Close modal using Alpine.js
            const event = new Event('close-filter-modal');
            document.dispatchEvent(event);
            
            // Try to close via Alpine if available
            if (window.Alpine && window.Alpine.store) {
                try {
                    const alpineData = Alpine.$data(document.querySelector('[x-data]'));
                    if (alpineData) {
                        alpineData.deviceFilterOpen = false;
                    }
                } catch(e) {}
            }
        }
        
        function clearDeviceFilters() {
            deviceFilters = {
                health: '',
                type: '',
                packet: '',
                search: ''
            };
            
            if (document.getElementById('filter-health')) document.getElementById('filter-health').value = '';
            if (document.getElementById('filter-type')) document.getElementById('filter-type').value = '';
            if (document.getElementById('filter-packet')) document.getElementById('filter-packet').value = '';
            if (document.getElementById('device-search')) document.getElementById('device-search').value = '';
            
            filterDevices();
        }
        
        function filterDevices() {
            const topIPs = dashboardData?.top_blocked_ips || [];
            const tbody = document.getElementById('devices-table');
            const cardsContainer = document.getElementById('devices-cards');
            
            let filtered = topIPs.filter(ip => {
                // Health filter
                if (deviceFilters.health) {
                    const healthStatus = ip.avg_threat_score > 7 ? 'bad' : ip.avg_threat_score > 4 ? 'moderate' : ip.avg_threat_score > 2 ? 'unavailable' : 'good';
                    if (healthStatus !== deviceFilters.health) return false;
                }
                
                // Type filter
                if (deviceFilters.type) {
                    const types = ip.threat_types_count || 1;
                    if (deviceFilters.type === '1' && types !== 1) return false;
                    if (deviceFilters.type === '2' && types !== 2) return false;
                    if (deviceFilters.type === '3' && types < 3) return false;
                }
                
                // Packet Loss filter
                if (deviceFilters.packet) {
                    const packetLoss = Math.min(Math.round((ip.avg_threat_score || 0) * 10), 100);
                    if (deviceFilters.packet === 'low' && packetLoss > 25) return false;
                    if (deviceFilters.packet === 'medium' && (packetLoss <= 25 || packetLoss > 50)) return false;
                    if (deviceFilters.packet === 'high' && packetLoss <= 50) return false;
                }
                
                // Search filter
                if (deviceFilters.search) {
                    const search = deviceFilters.search.toLowerCase();
                    if (!ip.ip_address?.toLowerCase().includes(search) && 
                        !ip.country_code?.toLowerCase().includes(search)) {
                        return false;
                    }
                }
                
                return true;
            });
            
            // Update table and cards with filtered results
            updateDevicesDisplay(filtered);
        }
        
        function updateDevicesDisplay(filteredIPs) {
            const tbody = document.getElementById('devices-table');
            const cardsContainer = document.getElementById('devices-cards');
            
            if (filteredIPs.length > 0) {
                // Desktop Table
                tbody.innerHTML = filteredIPs.slice(0, 6).map((ip, index) => {
                    const healthStatus = ip.avg_threat_score > 7 ? 'bad' : ip.avg_threat_score > 4 ? 'moderate' : ip.avg_threat_score > 2 ? 'unavailable' : 'good';
                    const healthLabel = healthStatus === 'good' ? 'Bom' : healthStatus === 'moderate' ? 'Moderado' : healthStatus === 'bad' ? 'Ruim' : 'Indisponível';
                    const packetLoss = Math.min(Math.round((ip.avg_threat_score || 0) * 10), 100);
                    const packetColor = packetLoss > 50 ? 'bg-gradient-to-r from-red-500 to-red-600' : packetLoss > 25 ? 'bg-gradient-to-r from-amber-500 to-amber-600' : 'bg-white';
                    
                    return `
                        <tr class="table-row group">
                            <td class="px-6 py-5">
                                <div class="flex items-center gap-4">
                                    <input type="checkbox" class="cursor-pointer">
                                    <span class="status-dot status-${healthStatus}"></span>
                                    <span class="text-sm text-white font-medium">${healthLabel}</span>
                    </div>
                            </td>
                            <td class="px-6 py-5">
                                <span class="text-sm font-mono text-white">${ip.ip_address}</span>
                            </td>
                            <td class="px-6 py-5">
                                <span class="text-sm text-zinc-400">${ip.threat_types_count || 1} tipos</span>
                            </td>
                            <td class="px-6 py-5">
                                <span class="text-sm text-zinc-400">${ip.country_code || 'Unknown'}</span>
                            </td>
                            <td class="px-6 py-5">
                                <div class="sparkline-container">
                                    <canvas id="sparkline-${index}" width="100" height="35"></canvas>
            </div>
                            </td>
                            <td class="px-6 py-5">
                                <div class="flex items-center gap-4">
                                    <div class="packet-bar w-28">
                                        <div class="packet-fill ${packetColor}" style="width: ${packetLoss}%"></div>
        </div>
                                    <span class="text-sm text-zinc-400 font-medium">${packetLoss}%</span>
    </div>
                            </td>
                        </tr>
                    `;
                }).join('');
                
                // Mobile Cards
                if (cardsContainer) {
                    cardsContainer.innerHTML = filteredIPs.slice(0, 6).map((ip, index) => {
                        const healthStatus = ip.avg_threat_score > 7 ? 'bad' : ip.avg_threat_score > 4 ? 'moderate' : ip.avg_threat_score > 2 ? 'unavailable' : 'good';
                        const healthLabel = healthStatus === 'good' ? 'Bom' : healthStatus === 'moderate' ? 'Moderado' : healthStatus === 'bad' ? 'Ruim' : 'Indisponível';
                        const healthColor = healthStatus === 'good' ? 'text-green-400' : healthStatus === 'moderate' ? 'text-amber-400' : healthStatus === 'bad' ? 'text-red-400' : 'text-zinc-400';
                        const packetLoss = Math.min(Math.round((ip.avg_threat_score || 0) * 10), 100);
                        const packetColor = packetLoss > 50 ? 'bg-red-500' : packetLoss > 25 ? 'bg-amber-500' : 'bg-white';
                        
                        return `
                            <div class="bg-white/5 border border-white/10 rounded-xl p-4 space-y-3">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <span class="status-dot status-${healthStatus}"></span>
                                        <span class="text-sm font-medium ${healthColor}">${healthLabel}</span>
                                    </div>
                                    <input type="checkbox" class="cursor-pointer">
                                </div>
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs text-zinc-500">Nome</span>
                                        <span class="text-sm font-mono text-white">${ip.ip_address}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs text-zinc-500">Tipo</span>
                                        <span class="text-sm text-zinc-400">${ip.threat_types_count || 1} tipos</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs text-zinc-500">Origem</span>
                                        <span class="text-sm text-zinc-400">${ip.country_code || 'Unknown'}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs text-zinc-500">Packet Loss</span>
                                        <div class="flex items-center gap-2">
                                            <div class="packet-bar w-20 h-2">
                                                <div class="packet-fill ${packetColor} h-full rounded" style="width: ${packetLoss}%"></div>
                                            </div>
                                            <span class="text-xs text-zinc-400 font-medium">${packetLoss}%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                    }).join('');
                }
                
                lucide.createIcons();
            } else {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-zinc-500">
                            <div class="flex flex-col items-center">
                                <div class="w-12 h-12 rounded-2xl bg-white/5 flex items-center justify-center mb-3">
                                    <i data-lucide="search-x" class="w-6 h-6"></i>
                                </div>
                                <p class="text-sm font-medium">Nenhum dispositivo encontrado com os filtros aplicados</p>
                            </div>
                        </td>
                    </tr>
                `;
                
                if (cardsContainer) {
                    cardsContainer.innerHTML = `
                        <div class="text-center py-10 text-zinc-500">
                            <div class="w-12 h-12 rounded-2xl bg-white/5 flex items-center justify-center mx-auto mb-3">
                                <i data-lucide="search-x" class="w-6 h-6"></i>
                            </div>
                            <p class="text-sm font-medium">Nenhum dispositivo encontrado com os filtros aplicados</p>
                        </div>
                    `;
                }
                
                lucide.createIcons();
            }
        }
        
        // Search functionality
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('device-search');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    deviceFilters.search = this.value;
                    filterDevices();
                });
            }
            
            // Enter key to search
            if (searchInput) {
                searchInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        applyDeviceFilters();
                    }
                });
            }
        });
        
        // Period buttons
        document.querySelectorAll('.period-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.period-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
            });
        });
        
        // Initial load
        fetchDashboardStats();
        
        // Auto refresh every 3 seconds
        setInterval(fetchDashboardStats, 3000);
    </script>
    
    <!-- Security Scripts - Previne download de código -->
    <script src="includes/security-scripts.js"></script>
</body>
</html>
