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
<html lang="pt-BR" class="h-full">
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
    <link rel="stylesheet" href="includes/theme-styles.css">
    <script src="includes/theme-toggle.js"></script>
    
    <!-- Aplicar tema ANTES da renderização para evitar flash -->
    <script>
        (function() {
            const stored = localStorage.getItem('safenode-theme') || 'auto';
            let actualTheme = stored;
            if (stored === 'auto') {
                if (window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches) {
                    actualTheme = 'light';
                } else {
                    actualTheme = 'dark';
                }
            }
            if (actualTheme === 'light') {
                document.documentElement.classList.remove('dark');
            } else {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
    
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
            /* Modo Escuro (padrão) */
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
        
        :root:not(.dark) {
            /* Modo Claro */
            --bg-primary: #ffffff;
            --bg-secondary: #f8f9fa;
            --bg-tertiary: #f1f3f5;
            --bg-card: #ffffff;
            --bg-hover: #e9ecef;
            --border-subtle: rgba(0,0,0,0.06);
            --border-light: rgba(0,0,0,0.12);
            --accent: #000000;
            --accent-glow: rgba(0, 0, 0, 0.1);
            --text-primary: #000000;
            --text-secondary: #495057;
            --text-muted: #868e96;
        }
        
        body {
            background-color: var(--bg-primary);
            color: var(--text-secondary);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        /* Custom Scrollbar - usa variáveis do theme-styles.css */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { 
            background: var(--scrollbar-thumb, var(--border-light)); 
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb:hover { 
            background: var(--scrollbar-thumb-hover, var(--text-muted)); 
        }
        
        /* Glassmorphism Effect - usa variáveis do theme-styles.css */
        .glass {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--border-subtle);
        }
        
        .glass-light {
            background: var(--glass-light-bg);
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
        
        :root:not(.dark) .nav-item::before {
            background: linear-gradient(90deg, var(--gradient-overlay-light) 0%, transparent 100%);
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
<body x-data="{ notificationsOpen: false, sidebarOpen: false, loading: true }" x-init="setTimeout(() => loading = false, 500)" class="h-full overflow-hidden">
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
    ?>
    <style>
        /* Nav-item styles já estão definidos acima com variáveis CSS - este bloco foi removido para evitar duplicação */
        
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
            background: linear-gradient(180deg, var(--bg-secondary) 0%, var(--bg-primary) 100%);
            border-right: 1px solid var(--border-subtle);
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
        
        <!-- Navigation -->
        <nav class="flex-1 p-4 space-y-1 overflow-y-auto overflow-x-hidden">
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
                <span id="alerts-badge" x-show="!sidebarCollapsed" class="hidden ml-auto px-2 py-0.5 rounded-full text-xs bg-red-500 text-white font-semibold">0</span>
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
                <button @click="sidebarOpen = false; $dispatch('safenode-sidebar-toggle', { isOpen: false })" class="text-zinc-600 hover:text-zinc-400 transition-colors flex-shrink-0">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
        </div>
        
        <!-- Navigation -->
        <nav class="flex-1 p-4 space-y-1 overflow-y-auto overflow-x-hidden">
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
            <a href="<?php echo getSafeNodeUrl('performance'); ?>" class="nav-item <?php echo $currentPage == 'performance' ? 'active' : ''; ?>" @click="sidebarOpen = false">
                <i data-lucide="gauge" class="w-5 h-5 flex-shrink-0"></i>
                <span class="font-medium whitespace-nowrap">Performance</span>
            </a>
            <a href="<?php echo getSafeNodeUrl('alerts'); ?>" class="nav-item <?php echo $currentPage == 'alerts' ? 'active' : ''; ?>" @click="sidebarOpen = false">
                <i data-lucide="bell" class="w-5 h-5 flex-shrink-0"></i>
                <span class="font-medium whitespace-nowrap">Alertas</span>
            </a>
            <a href="<?php echo getSafeNodeUrl('reports'); ?>" class="nav-item <?php echo $currentPage == 'reports' ? 'active' : ''; ?>" @click="sidebarOpen = false">
                <i data-lucide="file-text" class="w-5 h-5 flex-shrink-0"></i>
                <span class="font-medium whitespace-nowrap">Relatórios</span>
            </a>
            
            <div class="pt-4 mt-4 border-t border-gray-200 dark:border-white/5">
                <a href="<?php echo getSafeNodeUrl('help'); ?>" class="nav-item <?php echo $currentPage == 'help' ? 'active' : ''; ?>" @click="sidebarOpen = false">
                    <i data-lucide="life-buoy" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="font-medium whitespace-nowrap">Ajuda</span>
                </a>
            </div>
        </nav>
    </aside>

    <script>
    // Inicializar ícones do Lucide (incluindo o foguinho)
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
    </script>
        
    <!-- Main Content -->
    <main class="flex-1 flex flex-col h-full overflow-hidden bg-dark-950 dark:bg-dark-950 bg-white">
        <!-- Header -->
        <header class="h-20 bg-white/80 dark:bg-dark-900/50 backdrop-blur-xl border-b border-gray-200 dark:border-white/5 px-8 flex items-center justify-between flex-shrink-0">
            <div class="flex items-center gap-6">
                <button data-sidebar-toggle class="lg:hidden text-gray-500 dark:text-zinc-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                    <i data-lucide="menu" class="w-6 h-6"></i>
                </button>
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight">Dashboard</h2>
                    <?php if ($currentSiteId > 0 && $selectedSite): ?>
                    <p class="text-sm text-zinc-500 dark:text-zinc-500 text-gray-600 font-mono mt-0.5"><?php echo htmlspecialchars($selectedSite['name'] ?? ''); ?></p>
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
                <button @click="notificationsOpen = !notificationsOpen" class="relative p-3 text-gray-500 dark:text-zinc-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-white/5 rounded-xl transition-all">
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
                <p class="font-medium <?php echo $dashboardFlashType === 'warning' ? 'text-amber-700 dark:text-amber-200' : ($dashboardFlashType === 'error' ? 'text-red-700 dark:text-red-200' : 'text-gray-900 dark:text-white'); ?>"><?php echo htmlspecialchars($dashboardFlash); ?></p>
            </div>
                <?php endif; ?>
            
            <!-- Stats Cards -->
            <!-- Skeleton para cards de estatísticas -->
            <div x-show="loading" class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-5 mb-8">
                <?php for ($i = 0; $i < 4; $i++): ?>
                <div class="stat-card">
                    <div class="flex items-center justify-between mb-1">
                        <div class="skeleton-line" style="width: 100px; height: 14px;"></div>
                        <div class="skeleton-circle" style="width: 16px; height: 16px;"></div>
                    </div>
                    <div class="flex items-end justify-between mt-2 sm:mt-4">
                        <div class="skeleton-line" style="width: 60px; height: 32px;"></div>
                        <div class="skeleton-line" style="width: 50px; height: 20px;"></div>
                    </div>
                    <div class="skeleton-line" style="width: 80px; height: 12px; margin-top: 8px;"></div>
                </div>
                <?php endfor; ?>
            </div>
            
            <!-- Status Geral -->
            <div class="mb-6 glass rounded-2xl p-5 border border-white/10">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-3 h-3 rounded-full bg-green-500 animate-pulse"></div>
                        <div>
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Status Geral</h3>
                            <p class="text-xs text-zinc-500" id="status-description">Sistema operacional</p>
                        </div>
                    </div>
                    <span id="status-badge" class="px-3 py-1.5 rounded-lg bg-green-500/10 text-green-400 text-xs font-semibold">Operacional</span>
                </div>
            </div>

            <div x-show="!loading" class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-5 mb-8">
                <!-- Tráfego Humano -->
                <div class="stat-card group">
                    <div class="flex items-center justify-between mb-1">
                        <p class="text-xs sm:text-sm font-medium text-zinc-400">Tráfego Humano</p>
                    </div>
                    <div class="flex items-end justify-between mt-2 sm:mt-4">
                        <p id="human-traffic" class="text-2xl sm:text-4xl font-bold text-gray-900 dark:text-white tracking-tight">-</p>
                        <span id="human-change" class="text-xs sm:text-sm font-semibold text-gray-900 dark:text-white bg-green-500/10 px-2 py-0.5 sm:px-2.5 sm:py-1 rounded-lg"></span>
                    </div>
                    <p class="text-xs text-zinc-600 mt-2 sm:mt-3">últimas 24h</p>
                </div>

                <div class="stat-card group">
                    <div class="flex items-center justify-between mb-1">
                        <p class="text-xs sm:text-sm font-medium text-zinc-400">Bots Bloqueados</p>
                    </div>
                    <div class="flex items-end justify-between mt-2 sm:mt-4">
                        <p id="bots-blocked" class="text-2xl sm:text-4xl font-bold text-gray-900 dark:text-white tracking-tight">-</p>
                        <span id="bots-change" class="text-xs sm:text-sm font-semibold text-red-400 bg-red-500/10 px-2 py-0.5 sm:px-2.5 sm:py-1 rounded-lg"></span>
                    </div>
                    <p class="text-xs text-zinc-600 mt-2 sm:mt-3">últimas 24h</p>
                </div>
                
                <div class="stat-card group">
                    <div class="flex items-center justify-between mb-1">
                        <p class="text-xs sm:text-sm font-medium text-zinc-400">Taxa de Bloqueio</p>
                    </div>
                    <div class="flex items-end justify-between mt-2 sm:mt-4">
                        <p id="block-rate-value" class="text-2xl sm:text-4xl font-bold text-gray-900 dark:text-white tracking-tight">-</p>
                        <span class="text-xs sm:text-sm font-semibold text-amber-400 bg-amber-500/10 px-2 py-0.5 sm:px-2.5 sm:py-1 rounded-lg">%</span>
                    </div>
                    <p class="text-xs text-zinc-600 mt-2 sm:mt-3">requisições bloqueadas</p>
                </div>
                
                <div class="stat-card group">
                    <div class="flex items-center justify-between mb-1">
                        <p class="text-xs sm:text-sm font-medium text-zinc-400">Total de Eventos</p>
                    </div>
                    <div class="flex items-end justify-between mt-2 sm:mt-4">
                        <p id="total-events" class="text-2xl sm:text-4xl font-bold text-gray-900 dark:text-white tracking-tight">-</p>
                        <span id="events-change" class="text-xs sm:text-sm font-semibold text-gray-900 dark:text-white bg-gray-100 dark:bg-white/10 px-2 py-0.5 sm:px-2.5 sm:py-1 rounded-lg"></span>
                    </div>
                    <p class="text-xs text-zinc-600 mt-2 sm:mt-3">últimas 24h</p>
                </div>
            </div>
            
            <!-- Último Evento Relevante -->
            <div class="mb-8 glass rounded-2xl p-6 border border-gray-200 dark:border-white/10">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Último Evento Relevante</h3>
                <div id="last-event" class="flex items-center gap-4 p-4 rounded-xl bg-gray-50 dark:bg-white/[0.02] border border-gray-200 dark:border-white/5">
                    <div class="w-12 h-12 rounded-xl bg-gray-100 dark:bg-white/5 flex items-center justify-center">
                        <i data-lucide="loader-2" class="w-6 h-6 animate-spin text-gray-600 dark:text-zinc-400"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm text-gray-700 dark:text-zinc-400">Carregando...</p>
                    </div>
                </div>
            </div>
            
            <!-- Gráfico: Humanos vs Bots -->
            <div class="chart-card mb-8">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                    <div class="min-w-0 flex-1">
                        <h3 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-white break-words">Tráfego: Humanos vs Bots</h3>
                        <p class="text-xs sm:text-sm text-gray-600 dark:text-zinc-500 mt-1">Últimas 24 horas</p>
                    </div>
                    <div class="flex items-center gap-3 sm:gap-4 flex-shrink-0">
                        <div class="flex items-center gap-1.5 sm:gap-2">
                            <div class="w-2.5 h-2.5 sm:w-3 sm:h-3 rounded-full bg-green-500 flex-shrink-0"></div>
                            <span class="text-xs sm:text-sm text-gray-700 dark:text-zinc-400 whitespace-nowrap">Humanos</span>
                        </div>
                        <div class="flex items-center gap-1.5 sm:gap-2">
                            <div class="w-2.5 h-2.5 sm:w-3 sm:h-3 rounded-full bg-red-500 flex-shrink-0"></div>
                            <span class="text-xs sm:text-sm text-gray-700 dark:text-zinc-400 whitespace-nowrap">Bots</span>
                        </div>
                    </div>
                </div>
                <div class="relative w-full overflow-hidden" style="height: 250px; min-height: 200px;">
                    <canvas id="humansVsBotsChart" class="w-full h-full"></canvas>
                </div>
            </div>
            
            <!-- Eventos Recentes -->
            <div class="chart-card mb-8">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                    <div class="min-w-0 flex-1">
                        <h3 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-white break-words">Eventos Recentes</h3>
                        <p class="text-xs sm:text-sm text-gray-600 dark:text-zinc-500 mt-1">Últimos 10 eventos de verificação</p>
                    </div>
                    <a href="logs.php" class="text-xs sm:text-sm text-gray-600 dark:text-zinc-400 hover:text-gray-900 dark:hover:text-white transition-colors flex items-center gap-1.5 sm:gap-2 px-3 py-1.5 sm:px-0 sm:py-0 rounded-lg hover:bg-gray-100 dark:hover:bg-white/5 flex-shrink-0 whitespace-nowrap">
                        <span>Ver todos</span>
                        <i data-lucide="arrow-right" class="w-3.5 h-3.5 sm:w-4 sm:h-4 flex-shrink-0"></i>
                    </a>
                </div>
                <div id="recent-events" class="space-y-3">
                    <div class="text-center py-10 text-gray-600 dark:text-zinc-500">
                        <div class="w-12 h-12 rounded-2xl bg-gray-100 dark:bg-white/5 flex items-center justify-center mx-auto mb-3">
                            <i data-lucide="loader-2" class="w-6 h-6 animate-spin text-gray-600 dark:text-zinc-400"></i>
                        </div>
                        <p class="text-sm font-medium">Carregando eventos...</p>
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
         class="notification-panel fixed right-0 top-0 h-full w-96 bg-white dark:bg-dark-900 z-50 shadow-2xl border-l border-gray-200 dark:border-white/5"
         x-cloak>
        <div class="flex flex-col h-full">
            <div class="p-6 border-b border-gray-200 dark:border-white/5 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-gray-100 dark:bg-white/15 flex items-center justify-center">
                        <i data-lucide="bell" class="w-6 h-6 text-gray-900 dark:text-white"></i>
                    </div>
                <div>
                        <h3 class="font-bold text-gray-900 dark:text-white text-lg">Notificações</h3>
                        <p class="text-xs text-gray-600 dark:text-zinc-500">Alertas e eventos</p>
                    </div>
                </div>
                <button @click="notificationsOpen = false" class="p-2.5 text-gray-600 dark:text-zinc-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-white/5 rounded-xl transition-all">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto p-5">
                <div class="text-center py-16">
                    <div class="w-20 h-20 bg-gray-100 dark:bg-white/5 rounded-3xl flex items-center justify-center mx-auto mb-5">
                        <i data-lucide="bell-off" class="w-10 h-10 text-gray-600 dark:text-zinc-600"></i>
                    </div>
                    <p class="text-sm text-gray-700 dark:text-zinc-400 font-medium">Nenhuma notificação</p>
                    <p class="text-xs text-gray-600 dark:text-zinc-600 mt-1">Você será notificado de novos eventos</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Initialize Lucide Icons
        lucide.createIcons();

        // Charts
        let humansVsBotsChart = null;
        
        // Initialize Humans vs Bots Chart (Line)
        function initHumansVsBotsChart() {
            const canvas = document.getElementById('humansVsBotsChart');
            if (!canvas) {
                console.error('Canvas humansVsBotsChart não encontrado');
                return;
            }
            
            const ChartLib = window.Chart || Chart;
            if (typeof ChartLib === 'undefined' || !ChartLib) {
                console.error('Chart.js não está carregado. Tentando novamente...');
                setTimeout(initHumansVsBotsChart, 200);
                return;
            }
            
            if (humansVsBotsChart) {
                try {
                    humansVsBotsChart.destroy();
                } catch (e) {
                    console.warn('Erro ao destruir gráfico anterior:', e);
                }
                humansVsBotsChart = null;
            }
            
            const ctx = canvas.getContext('2d');
            if (!ctx) {
                console.error('Não foi possível obter contexto 2D do canvas');
                return;
            }
            
            // Gerar labels das últimas 7 horas (dados reais da API)
            const labels = [];
            for (let i = 6; i >= 0; i--) {
                const hour = new Date();
                hour.setHours(hour.getHours() - i);
                labels.push(hour.getHours().toString().padStart(2, '0'));
            }
            
            // Detectar se é mobile
            const isMobile = window.innerWidth < 640;
            
            try {
                humansVsBotsChart = new ChartLib(ctx, {
                    type: 'line',
                    data: {
                        labels: labels.map(h => h + 'h'),
                        datasets: [{
                            label: 'Humanos',
                            data: new Array(7).fill(0), // 7 horas - dados reais da API
                            borderColor: '#22c55e',
                            backgroundColor: 'rgba(34, 197, 94, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4,
                            pointRadius: 3,
                            pointHoverRadius: 5
                        }, {
                            label: 'Bots',
                            data: new Array(7).fill(0), // 7 horas - dados reais da API
                            borderColor: '#ef4444',
                            backgroundColor: 'rgba(239, 68, 68, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4,
                            pointRadius: 3,
                            pointHoverRadius: 5
                        }]
                    },
                    options: {
                        animation: {
                            duration: 0 // Desabilitar animação - apenas dados reais
                        },
                        responsive: true,
                        maintainAspectRatio: false,
                        layout: {
                            padding: {
                                left: isMobile ? 0 : 5,
                                right: isMobile ? 0 : 5,
                                top: 10,
                                bottom: isMobile ? 5 : 10
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0,0,0,0.9)',
                                titleColor: '#fff',
                                bodyColor: '#a1a1aa',
                                borderColor: 'rgba(255,255,255,0.1)',
                                borderWidth: 1,
                                padding: isMobile ? 8 : 12,
                                cornerRadius: 8,
                                displayColors: true,
                                titleFont: {
                                    size: isMobile ? 11 : 12
                                },
                                bodyFont: {
                                    size: isMobile ? 10 : 11
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: {
                                    color: 'rgba(0,0,0,0.05)',
                                    drawBorder: false
                                },
                                ticks: {
                                    color: '#6b7280',
                                    font: { 
                                        size: isMobile ? 9 : 11
                                    },
                                    padding: isMobile ? 4 : 8,
                                    maxRotation: isMobile ? 45 : 0,
                                    minRotation: 0
                                }
                            },
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(0,0,0,0.05)',
                                    drawBorder: false
                                },
                                ticks: {
                                    color: '#6b7280',
                                    font: { 
                                        size: isMobile ? 9 : 11
                                    },
                                    padding: isMobile ? 4 : 8,
                                    maxTicksLimit: isMobile ? 5 : 10
                                }
                            }
                        },
                        animation: {
                            duration: 1000,
                            easing: 'easeOutQuart'
                        }
                    }
                });
                console.log('Gráfico humansVsBotsChart inicializado com sucesso');
            } catch (error) {
                console.error('Erro ao criar gráfico humansVsBotsChart:', error);
            }
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
            const canvas = document.getElementById('humansVsBotsChart');
            if (!canvas) {
                console.error('Canvas humansVsBotsChart não encontrado no DOM');
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
                    initHumansVsBotsChart();
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
        
        async function fetchAlertsCount() {
            try {
                const response = await fetch('api/alerts.php?limit=1&unread=1');
                if (response.ok) {
                    const data = await response.json();
                    if (data.success && data.data.unread_count > 0) {
                        const badge = document.getElementById('alerts-badge');
                        if (badge) {
                            badge.textContent = data.data.unread_count;
                            badge.classList.remove('hidden');
                        }
                    } else {
                        const badge = document.getElementById('alerts-badge');
                        if (badge) badge.classList.add('hidden');
                    }
                }
            } catch (error) {
                // Ignorar erro silenciosamente
            }
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
                    // Se não houver dados, mostrar mensagem de "sem eventos"
                    const recentEventsContainer = document.getElementById('recent-events');
                    if (recentEventsContainer) {
                        recentEventsContainer.innerHTML = `
                            <div class="text-center py-12">
                                <div class="w-16 h-16 rounded-2xl bg-gray-100 dark:bg-white/5 flex items-center justify-center mx-auto mb-4">
                                    <i data-lucide="inbox" class="w-8 h-8 text-gray-400 dark:text-zinc-600"></i>
                                </div>
                                <p class="text-sm font-medium text-gray-700 dark:text-zinc-400 mb-1">Nenhum evento recente</p>
                                <p class="text-xs text-gray-600 dark:text-zinc-600">Os eventos de verificação aparecerão aqui</p>
                            </div>
                        `;
                        lucide.createIcons();
                    }
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
                
                // Se houver erro, mostrar mensagem de "sem eventos" em vez de "carregando"
                const recentEventsContainer = document.getElementById('recent-events');
                if (recentEventsContainer) {
                    recentEventsContainer.innerHTML = `
                        <div class="text-center py-12">
                            <div class="w-16 h-16 rounded-2xl bg-gray-100 dark:bg-white/5 flex items-center justify-center mx-auto mb-4">
                                <i data-lucide="inbox" class="w-8 h-8 text-gray-400 dark:text-zinc-600"></i>
                            </div>
                            <p class="text-sm font-medium text-gray-700 dark:text-zinc-400 mb-1">Nenhum evento recente</p>
                            <p class="text-xs text-gray-600 dark:text-zinc-600">Os eventos de verificação aparecerão aqui</p>
                        </div>
                    `;
                    lucide.createIcons();
                }
            }
        }
        
        function updateDashboard() {
            if (!dashboardData) return;
            
            const today = dashboardData.today || {};
            const last24h = dashboardData.last24h || {};
            const changes = dashboardData.changes || {};
            
            // Calcular métricas de verificação humana
            // Usar dados de hoje ou últimas 24h como fallback
            const totalRequests = today.total_requests || last24h.total_requests || 0;
            const botsBlocked = today.blocked || last24h.blocked || 0;
            const humansValidated = Math.max(totalRequests - botsBlocked, 0);
            const totalEvents = totalRequests;
            
            // Taxa de bloqueio
            const blockRate = totalRequests > 0 
                ? ((botsBlocked / totalRequests) * 100).toFixed(1)
                : '0.0';
            
            // Update stat cards
            animateValue('human-traffic', humansValidated);
            animateValue('bots-blocked', botsBlocked);
            const blockRateEl = document.getElementById('block-rate-value');
            if (blockRateEl) {
                blockRateEl.textContent = blockRate;
            }
            animateValue('total-events', totalEvents);
            
            // Changes - usar dados das últimas 24h comparado com ontem
            const humanChange = changes.requests || 0; // Mudança em requisições totais
            const botsChange = changes.blocked || 0; // Mudança em bots bloqueados
            const eventsChange = changes.requests || 0; // Mudança em eventos totais
            
            // Atualizar mudanças percentuais
            const humanChangeEl = document.getElementById('human-change');
            if (humanChangeEl) {
                humanChangeEl.innerHTML = formatPercent(humanChange);
                humanChangeEl.className = `text-xs sm:text-sm font-semibold ${humanChange >= 0 ? 'text-green-400 bg-green-500/10' : 'text-red-400 bg-red-500/10'} px-2 py-0.5 sm:px-2.5 sm:py-1 rounded-lg`;
            }
            
            const botsChangeEl = document.getElementById('bots-change');
            if (botsChangeEl) {
                botsChangeEl.innerHTML = formatPercent(botsChange);
                botsChangeEl.className = `text-xs sm:text-sm font-semibold ${botsChange >= 0 ? 'text-red-400 bg-red-500/10' : 'text-green-400 bg-green-500/10'} px-2 py-0.5 sm:px-2.5 sm:py-1 rounded-lg`;
            }
            
            const eventsChangeEl = document.getElementById('events-change');
            if (eventsChangeEl) {
                eventsChangeEl.innerHTML = formatPercent(eventsChange);
                eventsChangeEl.className = `text-xs sm:text-sm font-semibold ${eventsChange >= 0 ? 'text-gray-900 dark:text-white bg-gray-100 dark:bg-white/10' : 'text-red-600 dark:text-red-400 bg-red-500/10'} px-2 py-0.5 sm:px-2.5 sm:py-1 rounded-lg`;
            }
            
            // Status Geral
            let status = 'operational';
            let statusText = 'Operacional';
            let statusDesc = 'Sistema funcionando normalmente';
            let statusColor = 'green';
            
            if (parseFloat(blockRate) > 20) {
                status = 'attention';
                statusText = 'Atenção';
                statusDesc = 'Muitos bots detectados';
                statusColor = 'red';
            } else if (parseFloat(blockRate) > 10) {
                status = 'alert';
                statusText = 'Atento';
                statusDesc = 'Atividade suspeita detectada';
                statusColor = 'amber';
            }
            
            // Atualizar status geral
            const statusBadge = document.getElementById('status-badge');
            const statusDescEl = document.getElementById('status-description');
            const statusDot = document.querySelector('#status-general .w-3.h-3');
            
            if (statusBadge) {
                statusBadge.textContent = statusText;
                statusBadge.className = `px-3 py-1.5 rounded-lg bg-${statusColor}-500/10 text-${statusColor}-400 text-xs font-semibold`;
            }
            if (statusDescEl) {
                statusDescEl.textContent = statusDesc;
            }
            if (statusDot) {
                statusDot.className = `w-3 h-3 rounded-full bg-${statusColor}-500 animate-pulse`;
            }
            
            // Último Evento Relevante
            const recentLogs = dashboardData.event_logs || dashboardData.recent_logs || [];
            const lastEventContainer = document.getElementById('last-event');
            if (lastEventContainer) {
                if (recentLogs.length > 0) {
                    const lastEvent = recentLogs[0];
                    const eventType = lastEvent.action_taken || lastEvent.threat_type || 'unknown';
                    let eventIcon = 'check-circle-2';
                    let eventText = 'Acesso Permitido';
                    let eventColor = 'green';
                    
                    if (eventType === 'blocked' || eventType === 'bot_blocked' || eventType === 'bot_detected') {
                        eventIcon = 'shield-off';
                        eventText = 'Bot Bloqueado';
                        eventColor = 'red';
                    } else if (eventType === 'verified' || eventType === 'human_validated' || eventType === 'access_allowed') {
                        eventIcon = 'check-circle-2';
                        eventText = 'Humano Validado';
                        eventColor = 'green';
                    }
                    
                    const eventDate = new Date(lastEvent.created_at || new Date());
                    const timeAgo = getTimeAgo(eventDate);
                    
                    // Extrair domínio da URI se disponível
                    let domain = 'N/A';
                    if (lastEvent.request_uri) {
                        try {
                            const url = new URL(lastEvent.request_uri, window.location.origin);
                            domain = url.hostname;
                        } catch (e) {
                            domain = lastEvent.request_uri.substring(0, 30);
                        }
                    }
                    
                    lastEventContainer.innerHTML = `
                        <div class="w-12 h-12 rounded-xl bg-${eventColor}-500/10 flex items-center justify-center flex-shrink-0">
                            <i data-lucide="${eventIcon}" class="w-6 h-6 text-${eventColor}-400"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">${eventText}</p>
                            <p class="text-xs text-gray-600 dark:text-zinc-500 mt-1">IP: ${lastEvent.ip_address || 'N/A'}${domain !== 'N/A' ? ' | ' + domain : ''}</p>
                        </div>
                        <span class="text-xs text-gray-600 dark:text-zinc-500">${timeAgo}</span>
                    `;
                    lucide.createIcons();
                } else {
                    lastEventContainer.innerHTML = `
                        <div class="w-12 h-12 rounded-xl bg-gray-100 dark:bg-white/5 flex items-center justify-center">
                            <i data-lucide="info" class="w-6 h-6 text-gray-600 dark:text-zinc-400"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm text-gray-700 dark:text-zinc-400">Nenhum evento ainda</p>
                        </div>
                    `;
                    lucide.createIcons();
                }
            }
            
            // Atualizar gráfico Humans vs Bots
            // Atualizar gráfico com dados reais
            const hourlyStats = dashboardData.hourly_stats || {};
            updateHumansVsBotsChart(hourlyStats);
            
            // Eventos Recentes
            const recentEventsContainer = document.getElementById('recent-events');
            if (recentEventsContainer) {
                if (recentLogs.length > 0) {
                    recentEventsContainer.innerHTML = recentLogs.slice(0, 10).map(log => {
                        const eventType = log.action_taken || log.threat_type || 'unknown';
                        let eventIcon = 'check-circle-2';
                        let eventText = 'Acesso Permitido';
                        let eventColor = 'green';
                        
                        if (eventType === 'blocked' || eventType === 'bot_blocked' || eventType === 'bot_detected') {
                            eventIcon = 'shield-off';
                            eventText = 'Bot Bloqueado';
                            eventColor = 'red';
                        } else if (eventType === 'verified' || eventType === 'human_validated' || eventType === 'access_allowed') {
                            eventIcon = 'check-circle-2';
                            eventText = 'Humano Validado';
                            eventColor = 'green';
                        }
                        
                        const eventDate = new Date(log.created_at || new Date());
                        const timeAgo = getTimeAgo(eventDate);
                        
                        // Extrair domínio da URI se disponível
                        let domain = 'N/A';
                        if (log.request_uri) {
                            try {
                                const url = new URL(log.request_uri, window.location.origin);
                                domain = url.hostname;
                            } catch (e) {
                                domain = log.request_uri.substring(0, 30);
                            }
                        } else if (log.domain) {
                            domain = log.domain;
                        }
                        
                        return `
                            <div class="flex items-center gap-3 p-3 rounded-xl bg-gray-50 dark:bg-white/[0.02] border border-gray-200 dark:border-white/5 hover:bg-gray-100 dark:hover:bg-white/[0.04] transition-all">
                                <div class="w-8 h-8 rounded-lg bg-${eventColor}-500/10 flex items-center justify-center flex-shrink-0">
                                    <i data-lucide="${eventIcon}" class="w-4 h-4 text-${eventColor}-400"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">${eventText}</p>
                                    <p class="text-xs text-gray-600 dark:text-zinc-500 truncate">${log.ip_address || 'N/A'}${domain !== 'N/A' ? ' | ' + domain : ''}</p>
                                </div>
                                <span class="text-xs text-gray-600 dark:text-zinc-500 flex-shrink-0">${timeAgo}</span>
                            </div>
                        `;
                    }).join('');
                    lucide.createIcons();
                } else {
                    recentEventsContainer.innerHTML = `
                        <div class="text-center py-12">
                            <div class="w-16 h-16 rounded-2xl bg-gray-100 dark:bg-white/5 flex items-center justify-center mx-auto mb-4">
                                <i data-lucide="inbox" class="w-8 h-8 text-gray-400 dark:text-zinc-600"></i>
                            </div>
                            <p class="text-sm font-medium text-gray-700 dark:text-zinc-400 mb-1">Nenhum evento recente</p>
                            <p class="text-xs text-gray-600 dark:text-zinc-600">Os eventos de verificação aparecerão aqui</p>
                        </div>
                    `;
                    lucide.createIcons();
                }
            }
            
            lucide.createIcons();
        }
        
        function getTimeAgo(date) {
            const now = new Date();
            const diffMs = now - date;
            const diffMins = Math.floor(diffMs / 60000);
            const diffHours = Math.floor(diffMs / 3600000);
            const diffDays = Math.floor(diffMs / 86400000);
            
            if (diffMins < 1) return 'Agora';
            if (diffMins < 60) return `Há ${diffMins} min`;
            if (diffHours < 24) return `Há ${diffHours}h`;
            return `Há ${diffDays} dias`;
        }
        
        function updateHumansVsBotsChart(hourlyStats) {
            if (!humansVsBotsChart) {
                console.warn('Gráfico humansVsBotsChart não está inicializado');
                return;
            }
            
            try {
                // Gerar labels das últimas 7 horas (garantir que sempre temos 7 horas)
                const labels = [];
                const humansData = [];
                const botsData = [];
                
                // Criar array com as últimas 7 horas
                for (let i = 6; i >= 0; i--) {
                    const hour = new Date();
                    hour.setHours(hour.getHours() - i);
                    const hourStr = hour.getHours().toString().padStart(2, '0');
                    labels.push(hourStr);
                    
                    // Buscar dados da API para esta hora
                    const stats = hourlyStats[hourStr] || hourlyStats[parseInt(hourStr)] || { requests: 0, blocked: 0 };
                    const total = stats.requests || 0;
                    const blocked = stats.blocked || 0;
                    const humans = Math.max(0, total - blocked); // Humanos = total - bloqueados
                    
                    humansData.push(humans);
                    botsData.push(blocked);
                }
                
                // Garantir que temos pelo menos 7 pontos de dados
                while (humansData.length < 7) {
                    humansData.push(0);
                    botsData.push(0);
                }
                
                // Atualizar gráfico
                humansVsBotsChart.data.labels = labels.map(h => h + 'h');
                humansVsBotsChart.data.datasets[0].data = humansData.slice(0, 7);
                humansVsBotsChart.data.datasets[1].data = botsData.slice(0, 7);
                humansVsBotsChart.update('none'); // 'none' para evitar animação desnecessária
            } catch (error) {
                console.error('Erro ao atualizar gráfico Humans vs Bots:', error);
                console.error('Dados recebidos:', hourlyStats);
            }
        }
        
        function animateValue(elementId, targetValue) {
            const element = document.getElementById(elementId);
            if (!element) return;
            
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
        
        
        // Initial load
        fetchDashboardStats();
        fetchAlertsCount();
        
        // Auto refresh every 3 seconds
        setInterval(fetchDashboardStats, 3000);
        setInterval(fetchAlertsCount, 30000); // Atualizar alertas a cada 30s
    </script>
    
</body>
</html>
