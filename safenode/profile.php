<?php
/**
 * SafeNode - Perfil do Usuário
 */

session_start();

// SEGURANÇA: Carregar helpers e aplicar headers
require_once __DIR__ . '/includes/SecurityHelpers.php';
SecurityHeaders::apply();

// Verificar se está logado
if (!isset($_SESSION['safenode_logged_in']) || $_SESSION['safenode_logged_in'] !== true) {
        header('Location: login.php');
    exit;
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/init.php';
// 2FA removido - require_once __DIR__ . '/includes/TwoFactorAuth.php';

$db = getSafeNodeDatabase();

$pageTitle = 'Perfil';
$message = '';
$messageType = '';

// 2FA removido - Verificar status do 2FA
// $twoFactor = new TwoFactorAuth($db);
// $twoFactorStatus = $twoFactor->getStatus($_SESSION['safenode_user_id'] ?? null);
$twoFactorStatus = ['enabled' => false];

// Buscar dados do usuário
$username = $_SESSION['safenode_username'] ?? 'Admin';
$userInitial = strtoupper(substr($username, 0, 1));
$email = $_SESSION['safenode_email'] ?? '';
$fullName = $_SESSION['safenode_full_name'] ?? '';
$userId = $_SESSION['safenode_user_id'] ?? null;

// Buscar dados atualizados do banco
$avatarUrl = null;
if ($db && $userId) {
    try {
        $stmt = $db->prepare("SELECT username, full_name, email, created_at, avatar_url FROM safenode_users WHERE id = ?");
        $stmt->execute([$userId]);
        $userData = $stmt->fetch();
        if ($userData) {
            $username = $userData['username'] ?? $username;
            $fullName = $userData['full_name'] ?? $fullName;
            $email = $userData['email'] ?? $email;
            $avatarUrl = $userData['avatar_url'] ?? null;
            $userStats['account_created'] = $userData['created_at'] ?? date('Y-m-d');
            $userInitial = strtoupper(substr($username, 0, 1));
        }
    } catch (PDOException $e) {
        error_log("SafeNode Profile Error: " . $e->getMessage());
    }
}

// Salvar alterações no perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // SEGURANÇA: Validar CSRF token
    if (!CSRFProtection::validate()) {
        $message = "Token de segurança inválido. Recarregue a página e tente novamente.";
        $messageType = "error";
    } else {
        if ($db) {
            try {
                $userId = $_SESSION['safenode_user_id'] ?? null;
                $newUsername = XSSProtection::sanitize($_POST['username'] ?? '');
                $newFullName = XSSProtection::sanitize($_POST['full_name'] ?? '');
                
                // Validação do username
                if (empty($newUsername)) {
                    $message = "O nome de usuário não pode estar vazio.";
                    $messageType = "error";
                } elseif (!InputValidator::username($newUsername)) {
                    $message = "Nome de usuário inválido. Use apenas letras, números e _ (mín. 3 caracteres).";
                    $messageType = "error";
                } elseif (empty($newFullName)) {
                    $message = "O nome completo não pode estar vazio.";
                    $messageType = "error";
                } else {
                    // Username pode ser duplicado, então não precisa verificar
                        // Atualizar username e nome completo
                        $stmt = $db->prepare("UPDATE safenode_users SET username = ?, full_name = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                        $stmt->execute([$newUsername, $newFullName, $userId]);
                        
                        // Atualizar sessão
                        $_SESSION['safenode_username'] = $newUsername;
                        $_SESSION['safenode_full_name'] = $newFullName;
                        
                        // Atualizar variável local para exibição
                        $username = $newUsername;
                        $userInitial = strtoupper(substr($username, 0, 1));
                        
                        $message = "Perfil atualizado com sucesso!";
                        $messageType = "success";
                }
            } catch (PDOException $e) {
                error_log("SafeNode Profile Update Error: " . $e->getMessage());
                $message = "Erro ao atualizar perfil. Tente novamente.";
                $messageType = "error";
            }
        }
    }
}

// Buscar estatísticas do usuário
$userStats = [
    'total_sites' => 0,
    'total_logs' => 0,
    'total_blocks' => 0,
    'account_created' => date('Y-m-d')
];

if ($db) {
    try {
        // Contar sites do usuário logado
        $userId = $_SESSION['safenode_user_id'] ?? null;
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM safenode_sites WHERE user_id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        $userStats['total_sites'] = $result['total'] ?? 0;
        
        // Contar logs
        $stmt = $db->query("SELECT COUNT(*) as total FROM safenode_security_logs");
        $result = $stmt->fetch();
        $userStats['total_logs'] = $result['total'] ?? 0;
        
        // Contar bloqueios ativos
        $stmt = $db->query("SELECT COUNT(*) as total FROM v_safenode_active_blocks");
        $result = $stmt->fetch();
        $userStats['total_blocks'] = $result['total'] ?? 0;
    } catch (PDOException $e) {
        error_log("SafeNode Profile Stats Error: " . $e->getMessage());
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
    <link rel="shortcut icon" type="image/png" href="assets/img/logos (6).png">
    <link rel="apple-touch-icon" href="assets/img/logos (6).png">
    <script src="https://cdn.tailwindcss.com"></script>
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
            font-size: 0.92em;
        }
        
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { 
            background: var(--border-light); 
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb:hover { 
            background: var(--text-muted); 
        }
        
        .glass {
            background: var(--bg-card);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--border-subtle);
            transition: background-color 0.3s ease, border-color 0.3s ease;
        }
        
        .dark .glass {
            background: rgba(10, 10, 10, 0.7);
        }
        
        :root:not(.dark) .glass {
            background: rgba(255, 255, 255, 0.8);
        }
        
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
        
        /* Hover adaptável - modo claro precisa de mais contraste */
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
        
        .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border-subtle);
            border-radius: 16px;
            padding: 24px;
            position: relative;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent 0%, var(--border-light) 50%, transparent 100%);
        }
        
        .stat-card:hover {
            border-color: var(--border-light);
            transform: translateY(-4px);
            box-shadow: 0 20px 40px -20px rgba(0,0,0,0.5), 0 0 60px -30px var(--accent-glow);
        }
        
        .table-card {
            background: var(--bg-card);
            border: 1px solid var(--border-subtle);
            border-radius: 20px;
            overflow: hidden;
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
        
        .search-input {
            background: var(--bg-card);
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
            border-color: var(--border-light);
            box-shadow: 0 0 0 4px var(--accent-glow);
            width: 280px;
        }
        
        .upgrade-card {
            background: var(--bg-card);
            border: 1px solid var(--border-subtle);
            border-radius: 12px;
            padding: 16px;
        }
        
        [x-cloak] { display: none !important; }
        
        /* Profile specific styles */
        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--border-subtle);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .glass-card:hover {
            border-color: var(--border-light);
        }

        .avatar-glow {
            box-shadow: 0 0 30px var(--shadow-color);
            transition: all 0.3s;
        }
        .avatar-glow:hover {
            box-shadow: 0 0 40px var(--shadow-color);
            transform: scale(1.05);
        }

        .form-input {
            background: var(--bg-card);
            border: 1px solid var(--border-subtle);
            color: var(--text-primary);
            transition: all 0.3s;
        }
        .form-input:focus {
            background: var(--bg-hover);
            border-color: var(--border-light);
            box-shadow: 0 0 0 3px var(--accent-glow);
            outline: none;
        }

        .grid-pattern {
            background-image: 
                linear-gradient(var(--border-subtle) 1px, transparent 1px),
                linear-gradient(90deg, var(--border-subtle) 1px, transparent 1px);
            background-size: 20px 20px;
        }

        .modern-badge {
            backdrop-filter: blur(8px);
            border: 1px solid var(--border-subtle);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2), inset 0 1px 0 rgba(255, 255, 255, 0.1);
            transition: all 0.3s;
        }
        .modern-badge:hover {
            border-color: var(--border-light);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.15);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in {
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes scale-in {
            from {
                opacity: 0;
                transform: scale(0.95);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
        .animate-scale-in {
            animation: scale-in 0.2s ease-out;
        }

        .depth-shadow {
            box-shadow: 
                0 10px 30px var(--shadow-color),
                0 0 0 1px var(--border-subtle),
                inset 0 1px 0 var(--accent-glow);
        }

        .security-card {
            transition: all 0.3s;
        }
        .security-card:hover {
            transform: translateX(4px);
            border-color: var(--border-light);
        }
    </style>
</head>
<body class="h-full" x-data="{ sidebarOpen: false }">
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

    ?>
    <style>
        /* Nav-item styles já estão definidos acima com variáveis CSS - este bloco foi removido para evitar duplicação */
        
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
            background: var(--bg-card);
            border: 1px solid var(--border-subtle);
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
                        class="text-gray-500 dark:text-zinc-600 hover:text-gray-700 dark:hover:text-zinc-400 transition-colors flex-shrink-0" 
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
               class="text-xs font-semibold text-gray-500 dark:text-zinc-600 uppercase tracking-wider mb-3 px-3 whitespace-nowrap">Principal</p>
            
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
            
            <div class="pt-4 mt-4 border-t border-gray-200 dark:border-white/5">
                <p x-show="!sidebarCollapsed" 
                   x-transition:enter="transition ease-out duration-200" 
                   x-transition:enter-start="opacity-0" 
                   x-transition:enter-end="opacity-100" 
                   x-transition:leave="transition ease-in duration-150" 
                   x-transition:leave-start="opacity-100" 
                   x-transition:leave-end="opacity-0" 
                   class="text-xs font-semibold text-gray-500 dark:text-zinc-600 uppercase tracking-wider mb-3 px-3 whitespace-nowrap">Análises</p>
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
                <h3 class="font-semibold text-gray-900 dark:text-white text-sm mb-3">Ativar Pro</h3>
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
        <div class="w-full max-w-sm mx-4 rounded-2xl bg-white dark:bg-zinc-950 border border-gray-200 dark:border-white/10 p-6 shadow-2xl">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Deseja realmente sair?</h3>
            <p class="text-sm text-gray-600 dark:text-zinc-400 mb-6">Você será desconectado do painel SafeNode e precisará fazer login novamente para acessar o sistema.</p>
            <div class="flex gap-3 justify-end">
                <button type="button" data-logout-cancel class="px-4 py-2 rounded-xl bg-gray-100 dark:bg-zinc-900 text-gray-700 dark:text-zinc-300 hover:bg-gray-200 dark:hover:bg-zinc-800 text-sm font-semibold transition-all">
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
                    <img src="assets/img/logos (6).png" alt="SafeNode Logo" class="w-8 h-8 object-contain flex-shrink-0">
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
            
            <div class="pt-4 mt-4 border-t border-gray-200 dark:border-white/5">
                <p class="text-xs font-semibold text-zinc-600 uppercase tracking-wider mb-3 px-3 whitespace-nowrap">Análises</p>
                <a href="<?php echo getSafeNodeUrl('logs'); ?>" class="nav-item <?php echo $currentPage == 'logs' ? 'active' : ''; ?>" @click="sidebarOpen = false">
                    <i data-lucide="file-text" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="font-medium whitespace-nowrap">Explorar Logs</span>
                </a>
                <a href="<?php echo getSafeNodeUrl('suspicious-ips'); ?>" class="nav-item <?php echo $currentPage == 'suspicious-ips' ? 'active' : ''; ?>" @click="sidebarOpen = false">
                    <i data-lucide="alert-octagon" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="font-medium whitespace-nowrap">IPs Suspeitos</span>
                </a>
            </div>
        </nav>
        
        <!-- Upgrade Card -->
        <div class="p-4 flex-shrink-0">
            <div class="upgrade-card">
                <h3 class="font-semibold text-gray-900 dark:text-white text-sm mb-3">Ativar Pro</h3>
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
    <main class="flex-1 flex flex-col h-full overflow-hidden bg-white dark:bg-dark-950">
        <!-- Header -->
        <header class="h-20 bg-white/80 dark:bg-dark-900/50 backdrop-blur-xl border-b border-gray-200 dark:border-white/5 px-8 flex items-center justify-between flex-shrink-0">
            <div class="flex items-center gap-6">
                <button data-sidebar-toggle class="lg:hidden text-gray-500 dark:text-zinc-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                    <i data-lucide="menu" class="w-6 h-6"></i>
                </button>
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight"><?php echo $pageTitle; ?></h2>
                    <p class="text-sm text-gray-600 dark:text-zinc-500 mt-0.5">Gerencie suas informações pessoais</p>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <div class="relative hidden md:block">
                    <i data-lucide="search" class="w-4 h-4 absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 dark:text-zinc-500"></i>
                    <input type="text" placeholder="Buscar..." class="search-input">
                </div>
            </div>
        </header>

        <!-- Scrollable Content -->
        <div class="flex-1 overflow-y-auto p-8">
            <div class="max-w-5xl mx-auto space-y-6">
                <?php if ($message): ?>
                    <div class="mb-6 p-4 rounded-xl <?php 
                        echo $messageType === 'success' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30' : 
                            ($messageType === 'error' ? 'bg-red-500/10 text-red-400 border border-red-500/30' : 
                            'bg-blue-500/10 text-blue-400 border border-blue-500/30'); 
                    ?> font-semibold flex items-center gap-3 animate-fade-in shadow-lg">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 rounded-lg <?php 
                                echo $messageType === 'success' ? 'bg-emerald-500/20 border border-emerald-500/30' : 
                                    ($messageType === 'error' ? 'bg-red-500/20 border border-red-500/30' : 
                                    'bg-blue-500/20 border border-blue-500/30'); 
                            ?> flex items-center justify-center">
                                <i data-lucide="<?php echo $messageType === 'success' ? 'check-circle' : ($messageType === 'error' ? 'alert-circle' : 'info'); ?>" class="w-5 h-5"></i>
                            </div>
                        </div>
                        <p class="flex-1"><?php echo htmlspecialchars($message); ?></p>
                    </div>
                <?php endif; ?>

                <!-- Header do Perfil - Redesign -->
                <div class="glass-card rounded-2xl p-6 md:p-8 overflow-hidden relative animate-fade-in depth-shadow">
                    <!-- Grid pattern -->
                    <div class="absolute inset-0 grid-pattern opacity-20"></div>
                    
                    <!-- Background Pattern -->
                    <div class="absolute top-0 right-0 w-64 h-64 bg-gradient-to-br from-gray-200/20 dark:from-white/5 to-gray-100/10 dark:to-white/2 rounded-full blur-3xl"></div>
                    
                    <div class="relative z-10">
                        <div class="flex flex-col md:flex-row items-start md:items-center gap-6 mb-8">
                            <?php if ($avatarUrl): ?>
                                <div class="relative w-28 h-28 rounded-2xl overflow-hidden shadow-2xl avatar-glow border-2 border-gray-300 dark:border-white/20">
                                    <img src="<?php echo htmlspecialchars($avatarUrl); ?>" alt="Avatar" class="w-full h-full object-cover">
                                    <div class="absolute bottom-1 right-1 w-6 h-6 bg-white rounded-full flex items-center justify-center border-2 border-black shadow-lg">
                                        <i data-lucide="check" class="w-3.5 h-3.5 text-black"></i>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="w-28 h-28 rounded-2xl bg-gradient-to-br from-gray-200 dark:from-white to-gray-300 dark:to-zinc-300 flex items-center justify-center text-gray-900 dark:text-black font-black text-4xl shadow-2xl avatar-glow border-2 border-gray-300 dark:border-white/20">
                                    <?php echo $userInitial; ?>
                                </div>
                            <?php endif; ?>
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-3">
                                    <h3 class="text-3xl font-black text-gray-900 dark:text-white"><?php echo htmlspecialchars($username); ?></h3>
                                    <span class="modern-badge px-3 py-1.5 bg-gray-100 dark:bg-white/10 text-gray-900 dark:text-white rounded-lg text-xs font-bold border border-gray-300 dark:border-white/20">
                                        <i data-lucide="check-circle" class="w-3.5 h-3.5 inline mr-1"></i>
                                        Ativo
                                    </span>
                                </div>
                                <p class="text-gray-600 dark:text-zinc-400 text-sm flex items-center gap-2 mb-2 font-semibold">
                                    <i data-lucide="shield-check" class="w-4 h-4 text-gray-900 dark:text-white"></i>
                                    Administrador do Sistema
                                </p>
                                <p class="text-gray-500 dark:text-zinc-500 text-xs flex items-center gap-2 font-medium">
                                    <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
                                    Membro desde <?php echo date('d/m/Y', strtotime($userStats['account_created'])); ?>
                                </p>
                            </div>
                        </div>

                        <!-- Estatísticas - Redesign -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="stat-card rounded-xl p-5 relative overflow-hidden">
                                <div class="absolute top-0 right-0 w-20 h-20 bg-gray-200/20 dark:bg-white/5 rounded-full blur-2xl"></div>
                                <div class="relative z-10">
                                <div class="flex items-center justify-between mb-3">
                                        <div class="w-12 h-12 rounded-xl bg-gray-100 dark:bg-white/10 border border-gray-300 dark:border-white/20 flex items-center justify-center">
                                        <i data-lucide="globe" class="w-6 h-6 text-gray-900 dark:text-white"></i>
                                    </div>
                                        <span class="text-xs text-gray-500 dark:text-zinc-500 uppercase tracking-wider font-bold">Sites</span>
                                </div>
                                    <div class="text-3xl font-black text-gray-900 dark:text-white mb-1"><?php echo number_format($userStats['total_sites']); ?></div>
                                    <div class="text-xs text-gray-600 dark:text-zinc-400 font-medium">Configurados</div>
                                </div>
                            </div>
                            
                            <div class="stat-card rounded-xl p-5 relative overflow-hidden">
                                <div class="absolute top-0 right-0 w-20 h-20 bg-purple-500/5 rounded-full blur-2xl"></div>
                                <div class="relative z-10">
                                <div class="flex items-center justify-between mb-3">
                                        <div class="w-12 h-12 rounded-xl bg-purple-600/20 border border-purple-500/30 flex items-center justify-center">
                                            <i data-lucide="activity" class="w-6 h-6 text-purple-400"></i>
                                    </div>
                                        <span class="text-xs text-gray-500 dark:text-zinc-500 uppercase tracking-wider font-bold">Logs</span>
                                </div>
                                    <div class="text-3xl font-black text-gray-900 dark:text-white mb-1"><?php echo number_format($userStats['total_logs']); ?></div>
                                    <div class="text-xs text-gray-600 dark:text-zinc-400 font-medium">Registrados</div>
                                </div>
                            </div>
                            
                            <div class="stat-card rounded-xl p-5 relative overflow-hidden">
                                <div class="absolute top-0 right-0 w-20 h-20 bg-red-500/5 rounded-full blur-2xl"></div>
                                <div class="relative z-10">
                                <div class="flex items-center justify-between mb-3">
                                        <div class="w-12 h-12 rounded-xl bg-red-600/20 border border-red-500/30 flex items-center justify-center">
                                            <i data-lucide="shield-alert" class="w-6 h-6 text-red-400"></i>
                                    </div>
                                        <span class="text-xs text-gray-500 dark:text-zinc-500 uppercase tracking-wider font-bold">Bloqueios</span>
                                </div>
                                    <div class="text-3xl font-black text-gray-900 dark:text-white mb-1"><?php echo number_format($userStats['total_blocks']); ?></div>
                                    <div class="text-xs text-gray-600 dark:text-zinc-400 font-medium">Ativos</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Configurações da Conta - Redesign -->
                <div class="glass-card rounded-2xl p-6 md:p-8 relative overflow-hidden animate-fade-in depth-shadow" style="animation-delay: 0.1s">
                    <!-- Grid pattern -->
                    <div class="absolute inset-0 grid-pattern opacity-20"></div>
                    
                    <!-- Decoração de fundo -->
                    <div class="absolute top-0 right-0 w-40 h-40 bg-blue-500/5 rounded-full blur-3xl"></div>
                    
                    <div class="relative z-10">
                    <div class="flex items-center gap-3 mb-6">
                            <div class="w-12 h-12 rounded-xl bg-blue-500/15 border border-blue-500/30 flex items-center justify-center">
                                <i data-lucide="settings" class="w-6 h-6 text-blue-400"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Configurações da Conta</h3>
                                <p class="text-xs text-gray-600 dark:text-zinc-400 mt-0.5 font-medium">Gerencie suas credenciais de acesso</p>
                        </div>
                    </div>
                    
                    <form method="POST" id="profileForm" class="space-y-5">
                        <?php echo csrf_field(); ?>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 dark:text-zinc-300 mb-2 flex items-center gap-2">
                                    <i data-lucide="user" class="w-4 h-4 text-blue-400"></i>
                                    Nome de Usuário
                                </label>
                                <div class="relative">
                                    <i data-lucide="user" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-500"></i>
                                <input type="text" name="username" id="inputUsername" value="<?php echo htmlspecialchars($username); ?>" required
                                       pattern="[a-zA-Z0-9_]{3,50}" 
                                       disabled
                                           class="profile-input w-full pl-10 pr-4 py-3 border border-gray-300 dark:border-white/10 rounded-xl bg-gray-50 dark:bg-zinc-900/30 text-gray-500 dark:text-zinc-500 cursor-not-allowed transition-all" 
                                       placeholder="seu_usuario">
                                </div>
                                <p class="mt-1.5 text-xs text-zinc-500 flex items-center gap-1 font-medium">
                                    <i data-lucide="info" class="w-3 h-3"></i>
                                    Use apenas letras, números e _ (mín. 3 caracteres)
                                </p>
                            </div>

                            <?php if ($email): ?>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 dark:text-zinc-300 mb-2 flex items-center gap-2">
                                    <i data-lucide="mail" class="w-4 h-4 text-blue-400"></i>
                                    E-mail
                                </label>
                                <div class="relative">
                                    <i data-lucide="mail" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-500"></i>
                                    <input type="email" value="<?php echo htmlspecialchars($email); ?>" disabled class="w-full pl-10 pr-4 py-3 border border-gray-300 dark:border-white/10 rounded-xl bg-gray-50 dark:bg-zinc-900/30 text-gray-500 dark:text-zinc-500 cursor-not-allowed">
                                </div>
                                <p class="mt-1.5 text-xs text-zinc-500 flex items-center gap-1 font-medium">
                                    <i data-lucide="shield-check" class="w-3 h-3 text-emerald-400"></i>
                                    E-mail verificado e protegido
                                </p>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-zinc-300 mb-2 flex items-center gap-2">
                                <i data-lucide="user-circle" class="w-4 h-4 text-blue-400"></i>
                                Nome Completo
                            </label>
                            <div class="relative">
                                <i data-lucide="user-circle" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-500"></i>
                            <input type="text" name="full_name" id="inputFullName" value="<?php echo htmlspecialchars($fullName); ?>" required disabled
                                       class="profile-input w-full pl-10 pr-4 py-3 border border-white/10 rounded-xl bg-zinc-900/30 text-zinc-500 cursor-not-allowed transition-all" 
                                   placeholder="Seu nome completo">
                            </div>
                            <p class="mt-1.5 text-xs text-zinc-500 flex items-center gap-1 font-medium">
                                <i data-lucide="info" class="w-3 h-3"></i>
                                Este nome será exibido em seu perfil
                            </p>
                        </div>

                        <div class="flex flex-col sm:flex-row justify-between items-center gap-4 pt-6 border-t border-gray-200 dark:border-white/10">
                            <a href="change-password.php" class="text-blue-400 hover:text-blue-300 text-sm font-bold flex items-center gap-2 transition-colors order-2 sm:order-1 hover:scale-105">
                                <i data-lucide="key" class="w-4 h-4"></i>
                                Alterar Senha
                            </a>
                            
                            <!-- Botão Editar (modo visualização) -->
                            <div id="editButtonContainer" class="flex justify-end w-full sm:w-auto order-1 sm:order-2">
                                <button type="button" onclick="enableEditMode()" class="btn-primary w-full sm:w-auto px-6 py-3 text-black rounded-xl font-bold transition-all flex items-center justify-center gap-2">
                                    <i data-lucide="edit-2" class="w-4 h-4"></i>
                                    Editar Perfil
                                </button>
                            </div>
                            
                            <!-- Botões Cancelar e Salvar (modo edição) -->
                            <div id="actionButtonsContainer" class="hidden flex flex-col sm:flex-row gap-3 w-full sm:w-auto order-1 sm:order-2">
                                <button type="button" onclick="cancelEditMode()" class="w-full sm:w-auto px-6 py-3 border border-gray-300 dark:border-white/10 text-gray-700 dark:text-white rounded-xl hover:bg-gray-100 dark:hover:bg-white/5 hover:border-gray-400 dark:hover:border-white/20 font-bold transition-all text-center">
                                    Cancelar
                                </button>
                                <button type="submit" name="update_profile" class="btn-primary w-full sm:w-auto px-6 py-3 text-black rounded-xl font-bold transition-all flex items-center justify-center gap-2">
                                    <i data-lucide="save" class="w-4 h-4"></i>
                                    Salvar Alterações
                                </button>
                            </div>
                        </div>
                    </form>
                    </div>
                </div>

                <!-- Segurança Avançada - Redesign -->
                <div class="glass-card rounded-2xl p-6 md:p-8 relative overflow-hidden animate-fade-in depth-shadow" style="animation-delay: 0.2s">
                    <!-- Grid pattern -->
                    <div class="absolute inset-0 grid-pattern opacity-20"></div>
                    
                    <!-- Decoração de fundo -->
                    <div class="absolute top-0 right-0 w-40 h-40 bg-purple-500/5 rounded-full blur-3xl"></div>
                    
                    <div class="relative z-10">
                    <div class="flex items-center gap-3 mb-6">
                            <div class="w-12 h-12 rounded-xl bg-purple-500/15 border border-purple-500/30 flex items-center justify-center">
                                <i data-lucide="shield" class="w-6 h-6 text-purple-400"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Segurança Avançada</h3>
                                <p class="text-xs text-gray-600 dark:text-zinc-400 mt-0.5 font-medium">Proteja ainda mais sua conta</p>
                        </div>
                    </div>
                    
                    <div class="space-y-3">
                        <!-- 2FA removido -->
                        
                        <!-- Logout Simples -->
                        <div class="security-card flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-5 rounded-xl bg-gray-50 dark:bg-zinc-900/30 border border-gray-200 dark:border-white/5 hover:border-blue-500/30 transition-all group">
                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 rounded-lg bg-blue-600/15 border border-blue-500/25 flex items-center justify-center flex-shrink-0 group-hover:bg-blue-600/25 transition-colors">
                                    <i data-lucide="log-out" class="w-6 h-6 text-blue-400"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-gray-900 dark:text-white mb-1">Sair da Conta</p>
                                    <p class="text-xs text-gray-600 dark:text-zinc-500 font-medium">Fazer logout da sua conta</p>
                                </div>
                            </div>
                            <a href="logout.php" class="modern-badge px-5 py-2.5 bg-blue-600/15 text-blue-400 rounded-lg text-sm font-bold hover:bg-blue-600/25 transition-all whitespace-nowrap border border-blue-600/30 text-center">
                                <i data-lucide="log-out" class="w-4 h-4 inline mr-1"></i>
                                Sair
                            </a>
                        </div>
                        
                            <div class="security-card flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-5 rounded-xl bg-gray-50 dark:bg-zinc-900/30 border border-gray-200 dark:border-white/5 hover:border-blue-500/30 transition-all group">
                            <div class="flex items-start gap-4">
                                    <div class="w-12 h-12 rounded-lg bg-blue-600/15 border border-blue-500/25 flex items-center justify-center flex-shrink-0 group-hover:bg-blue-600/25 transition-colors">
                                    <i data-lucide="monitor" class="w-6 h-6 text-blue-400"></i>
                                </div>
                                <div>
                                        <p class="text-sm font-bold text-gray-900 dark:text-white mb-1">Sessões Ativas</p>
                                        <p class="text-xs text-gray-600 dark:text-zinc-500 font-medium">Gerencie dispositivos conectados à sua conta</p>
                                </div>
                            </div>
                                <a href="sessions.php" class="modern-badge px-5 py-2.5 bg-blue-600/15 text-blue-400 rounded-lg text-sm font-bold hover:bg-blue-600/25 transition-all whitespace-nowrap border border-blue-600/30 text-center">
                                    <i data-lucide="arrow-right" class="w-4 h-4 inline mr-1"></i>
                                Ver sessões
                            </a>
                        </div>

                            <div class="security-card flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-5 rounded-xl bg-gray-50 dark:bg-zinc-900/30 border border-gray-200 dark:border-white/5 hover:border-blue-500/30 transition-all group">
                            <div class="flex items-start gap-4">
                                    <div class="w-12 h-12 rounded-lg bg-blue-600/15 border border-blue-500/25 flex items-center justify-center flex-shrink-0 group-hover:bg-blue-600/25 transition-colors">
                                    <i data-lucide="clock" class="w-6 h-6 text-blue-400"></i>
                                </div>
                                <div>
                                        <p class="text-sm font-bold text-gray-900 dark:text-white mb-1">Histórico de Atividades</p>
                                        <p class="text-xs text-gray-600 dark:text-zinc-500 font-medium">Visualize ações recentes em sua conta</p>
                                </div>
                            </div>
                                <a href="activity-log.php" class="modern-badge px-5 py-2.5 bg-blue-600/15 text-blue-400 rounded-lg text-sm font-bold hover:bg-blue-600/25 transition-all whitespace-nowrap border border-blue-600/30 text-center">
                                    <i data-lucide="arrow-right" class="w-4 h-4 inline mr-1"></i>
                                Ver histórico
                            </a>
                        </div>
                    </div>
                    </div>
                </div>

                <!-- Aparência - Redesign -->
                <div class="glass-card rounded-2xl p-6 md:p-8 relative overflow-hidden animate-fade-in depth-shadow" style="animation-delay: 0.3s">
                    <!-- Grid pattern -->
                    <div class="absolute inset-0 grid-pattern opacity-20"></div>
                    
                    <!-- Decoração de fundo -->
                    <div class="absolute top-0 right-0 w-40 h-40 bg-amber-500/5 rounded-full blur-3xl"></div>
                    
                    <div class="relative z-10">
                    <div class="flex items-center gap-3 mb-6">
                            <div class="w-12 h-12 rounded-xl bg-amber-500/15 border border-amber-500/30 flex items-center justify-center">
                                <i data-lucide="palette" class="w-6 h-6 text-amber-400"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-white">Aparência</h3>
                                <p class="text-xs text-zinc-400 mt-0.5 font-medium">Personalize a interface do painel</p>
                        </div>
                    </div>
                    
                    <div class="space-y-3" x-data="{ currentTheme: '<?php echo isset($_COOKIE['safenode-theme']) ? htmlspecialchars($_COOKIE['safenode-theme']) : 'auto'; ?>' }" 
                         x-init="
                             currentTheme = localStorage.getItem('safenode-theme') || 'auto';
                             window.addEventListener('theme-changed', (e) => {
                                 currentTheme = e.detail.theme;
                             });
                         ">
                        <!-- Modo Escuro -->
                        <button @click="SafeNodeTheme.set('dark'); currentTheme = 'dark'; if(typeof lucide !== 'undefined') lucide.createIcons();" 
                                class="security-card w-full flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-5 rounded-xl bg-gray-50 dark:bg-zinc-900/30 border border-gray-200 dark:border-white/5 hover:border-amber-500/30 transition-all group text-left" 
                                :class="currentTheme === 'dark' ? 'border-amber-500/50 bg-amber-500/5' : ''">
                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 rounded-lg bg-amber-600/15 border border-amber-500/25 flex items-center justify-center flex-shrink-0 group-hover:bg-amber-600/25 transition-colors">
                                    <i data-lucide="moon" class="w-6 h-6 text-amber-400"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-gray-900 dark:text-white mb-1">Modo Escuro</p>
                                    <p class="text-xs text-gray-600 dark:text-zinc-500 font-medium">Interface com tema escuro</p>
                                </div>
                            </div>
                            <i x-show="currentTheme === 'dark'" data-lucide="check" class="w-5 h-5 text-amber-400 flex-shrink-0"></i>
                        </button>
                        
                        <!-- Modo Claro -->
                        <button @click="SafeNodeTheme.set('light'); currentTheme = 'light'; if(typeof lucide !== 'undefined') lucide.createIcons();" 
                                class="security-card w-full flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-5 rounded-xl bg-gray-50 dark:bg-zinc-900/30 border border-gray-200 dark:border-white/5 hover:border-amber-500/30 transition-all group text-left" 
                                :class="currentTheme === 'light' ? 'border-amber-500/50 bg-amber-500/5' : ''">
                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 rounded-lg bg-amber-600/15 border border-amber-500/25 flex items-center justify-center flex-shrink-0 group-hover:bg-amber-600/25 transition-colors">
                                    <i data-lucide="sun" class="w-6 h-6 text-amber-400"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-gray-900 dark:text-white mb-1">Modo Claro</p>
                                    <p class="text-xs text-gray-600 dark:text-zinc-500 font-medium">Interface com tema claro</p>
                                </div>
                            </div>
                            <i x-show="currentTheme === 'light'" data-lucide="check" class="w-5 h-5 text-amber-400 flex-shrink-0"></i>
                        </button>
                        
                        <!-- Seguir Dispositivo -->
                        <button @click="SafeNodeTheme.set('auto'); currentTheme = 'auto'; if(typeof lucide !== 'undefined') lucide.createIcons();" 
                                class="security-card w-full flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-5 rounded-xl bg-gray-50 dark:bg-zinc-900/30 border border-gray-200 dark:border-white/5 hover:border-amber-500/30 transition-all group text-left" 
                                :class="currentTheme === 'auto' ? 'border-amber-500/50 bg-amber-500/5' : ''">
                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 rounded-lg bg-amber-600/15 border border-amber-500/25 flex items-center justify-center flex-shrink-0 group-hover:bg-amber-600/25 transition-colors">
                                    <i data-lucide="monitor" class="w-6 h-6 text-amber-400"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-gray-900 dark:text-white mb-1">Seguir Dispositivo</p>
                                    <p class="text-xs text-gray-600 dark:text-zinc-500 font-medium">Usar preferência do sistema</p>
                                </div>
                            </div>
                            <i x-show="currentTheme === 'auto'" data-lucide="check" class="w-5 h-5 text-amber-400 flex-shrink-0"></i>
                        </button>
                    </div>
                    </div>
                </div>

                <!-- Zona Perigosa - Redesign -->
                <div class="glass-card rounded-2xl p-6 md:p-8 border border-red-500/30 relative overflow-hidden animate-fade-in depth-shadow" style="animation-delay: 0.3s">
                    <!-- Grid pattern -->
                    <div class="absolute inset-0 grid-pattern opacity-20"></div>
                    
                    <!-- Decoração de fundo -->
                    <div class="absolute top-0 right-0 w-40 h-40 bg-red-500/5 rounded-full blur-3xl"></div>
                    
                    <div class="relative z-10">
                    <div class="flex items-center gap-3 mb-6">
                            <div class="w-12 h-12 rounded-xl bg-red-600/15 border border-red-500/30 flex items-center justify-center">
                                <i data-lucide="alert-triangle" class="w-6 h-6 text-red-400"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-red-600 dark:text-red-400">Zona Perigosa</h3>
                                <p class="text-xs text-gray-600 dark:text-zinc-400 mt-0.5 font-medium">Ações irreversíveis - proceda com cautela</p>
                        </div>
                    </div>
                    
                    <div class="space-y-3">
                            <div class="security-card flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-5 rounded-xl bg-red-500/5 border border-red-500/25 hover:bg-red-500/10 transition-all">
                            <div class="flex items-start gap-4">
                                    <div class="w-12 h-12 rounded-lg bg-red-600/15 border border-red-500/25 flex items-center justify-center flex-shrink-0">
                                    <i data-lucide="log-out" class="w-6 h-6 text-red-400"></i>
                                </div>
                                <div>
                                        <p class="text-sm font-bold text-gray-900 dark:text-white mb-1">Encerrar Todas as Sessões</p>
                                        <p class="text-xs text-gray-600 dark:text-zinc-500 font-medium">Desconecte-se de todos os dispositivos imediatamente</p>
                                </div>
                            </div>
                                <button onclick="openTerminateSessionsModal()" class="modern-badge px-5 py-2.5 bg-red-500/15 text-red-400 rounded-lg text-sm font-bold hover:bg-red-500/25 transition-all border border-red-500/30 whitespace-nowrap">
                                    <i data-lucide="log-out" class="w-4 h-4 inline mr-1"></i>
                                Encerrar Tudo
                            </button>
                        </div>
                        
                            <div class="security-card flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-5 rounded-xl bg-red-500/5 border border-red-500/25 hover:bg-red-500/10 transition-all">
                            <div class="flex items-start gap-4">
                                    <div class="w-12 h-12 rounded-lg bg-red-600/15 border border-red-500/25 flex items-center justify-center flex-shrink-0">
                                    <i data-lucide="trash-2" class="w-6 h-6 text-red-400"></i>
                                </div>
                                <div>
                                        <p class="text-sm font-bold text-gray-900 dark:text-white mb-1">Excluir Conta Permanentemente</p>
                                        <p class="text-xs text-gray-600 dark:text-zinc-500 font-medium">Esta ação não pode ser desfeita. Todos os dados serão perdidos</p>
                                </div>
                            </div>
                                <button onclick="openDeleteAccountModal()" class="modern-badge px-5 py-2.5 bg-red-500/15 text-red-400 rounded-lg text-sm font-bold hover:bg-red-500/25 transition-all border border-red-500/30 whitespace-nowrap">
                                    <i data-lucide="trash-2" class="w-4 h-4 inline mr-1"></i>
                                Excluir Conta
                            </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php $csrfToken = CSRFProtection::generateToken(); ?>

    <!-- Modal de Confirmação Customizado - Redesign -->
    <div id="confirmModal" class="hidden fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/90 backdrop-blur-md">
        <div class="glass-card rounded-2xl p-6 md:p-8 max-w-md w-full border border-gray-200 dark:border-white/10 relative overflow-hidden depth-shadow animate-scale-in">
            <!-- Grid pattern -->
            <div class="absolute inset-0 grid-pattern opacity-20"></div>
            
            <!-- Decoração de fundo -->
            <div class="absolute top-0 right-0 w-32 h-32 bg-yellow-500/5 rounded-full blur-3xl"></div>
            
            <div class="relative z-10">
            <div class="flex items-center gap-3 mb-6">
                    <div class="w-12 h-12 rounded-xl bg-yellow-600/15 border border-yellow-500/30 flex items-center justify-center">
                    <i data-lucide="alert-circle" class="w-6 h-6 text-yellow-400"></i>
                </div>
                <div class="flex-1">
                    <h3 id="confirmTitle" class="text-xl font-bold text-white">Confirmar Ação</h3>
                </div>
                    <button onclick="closeConfirmModal()" class="p-2 text-gray-500 dark:text-zinc-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-white/5 rounded-lg transition-all">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

                <p id="confirmMessage" class="text-sm text-zinc-400 mb-6 leading-relaxed font-medium"></p>

            <div class="flex gap-3">
                    <button id="confirmCancelBtn" onclick="closeConfirmModal()" class="flex-1 px-5 py-3 border border-gray-300 dark:border-white/10 text-gray-700 dark:text-white rounded-xl hover:bg-gray-100 dark:hover:bg-white/5 hover:border-gray-400 dark:hover:border-white/20 font-bold transition-all">
                    Cancelar
                </button>
                    <button id="confirmOkBtn" class="btn-primary flex-1 px-5 py-3 text-white rounded-xl font-bold transition-all">
                    Continuar
                </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Sucesso Customizado - Redesign -->
    <div id="successModal" class="hidden fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/90 backdrop-blur-md">
        <div class="glass-card rounded-2xl p-6 md:p-8 max-w-md w-full border border-emerald-500/30 relative overflow-hidden depth-shadow animate-scale-in">
            <!-- Grid pattern -->
            <div class="absolute inset-0 grid-pattern opacity-20"></div>
            
            <!-- Decoração de fundo -->
            <div class="absolute top-0 right-0 w-32 h-32 bg-emerald-500/5 rounded-full blur-3xl"></div>
            
            <div class="relative z-10">
            <div class="flex items-center gap-3 mb-6">
                    <div class="w-12 h-12 rounded-xl bg-emerald-600/15 border border-emerald-500/30 flex items-center justify-center">
                    <i data-lucide="check-circle" class="w-6 h-6 text-emerald-400"></i>
                </div>
                <div class="flex-1">
                    <h3 class="text-xl font-bold text-white">Sucesso</h3>
                </div>
            </div>

                <p id="successMessage" class="text-sm text-zinc-400 mb-6 leading-relaxed font-medium"></p>

                <button id="successOkBtn" class="w-full px-5 py-3 bg-gradient-to-r from-emerald-600 to-emerald-700 text-white rounded-xl hover:from-emerald-700 hover:to-emerald-800 font-bold transition-all shadow-lg shadow-emerald-500/30">
                OK
            </button>
            </div>
        </div>
    </div>

    <!-- Modal: Encerrar Todas as Sessões - Redesign -->
    <div id="terminateSessionsModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/90 backdrop-blur-md">
        <div class="glass-card rounded-2xl p-6 md:p-8 max-w-md w-full border border-red-500/30 relative overflow-hidden depth-shadow animate-scale-in">
            <!-- Grid pattern -->
            <div class="absolute inset-0 grid-pattern opacity-20"></div>
            
            <!-- Decoração de fundo -->
            <div class="absolute top-0 right-0 w-32 h-32 bg-red-500/5 rounded-full blur-3xl"></div>
            
            <div class="relative z-10">
            <div class="flex items-center gap-3 mb-6">
                    <div class="w-12 h-12 rounded-xl bg-red-600/15 border border-red-500/30 flex items-center justify-center">
                    <i data-lucide="log-out" class="w-6 h-6 text-red-400"></i>
                </div>
                <div class="flex-1">
                    <h3 class="text-xl font-bold text-white">Encerrar Todas as Sessões</h3>
                        <p class="text-xs text-zinc-400 mt-0.5 font-medium">Confirmação de segurança em 2 etapas</p>
                </div>
                    <button onclick="closeTerminateSessionsModal()" class="p-2 text-zinc-400 hover:text-white hover:bg-white/5 rounded-lg transition-all">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <div id="terminateStep1" class="space-y-4">
                <div class="p-4 rounded-xl bg-red-500/10 border border-red-500/20">
                    <p class="text-sm text-red-400 flex items-start gap-2">
                        <i data-lucide="alert-triangle" class="w-4 h-4 mt-0.5 flex-shrink-0"></i>
                        <span>Você será desconectado de todos os dispositivos e precisará fazer login novamente.</span>
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-zinc-300 mb-2">Sua Senha</label>
                    <div class="relative">
                        <input type="password" id="terminatePassword" 
                               class="form-input w-full px-4 py-3 pr-12 rounded-xl text-white placeholder:text-zinc-600 text-sm font-medium" 
                               placeholder="Digite sua senha">
                        <button type="button" onclick="togglePasswordVisibility('terminatePassword', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-zinc-500 hover:text-white transition-colors">
                            <i data-lucide="eye" id="eye-terminatePassword" class="w-5 h-5"></i>
                        </button>
                    </div>
                </div>

                <div id="terminateError" class="hidden p-3 rounded-lg bg-red-500/10 border border-red-500/20 text-sm text-red-400"></div>

                <div class="flex gap-3 pt-2">
                    <button onclick="closeTerminateSessionsModal()" class="flex-1 px-5 py-3 border border-gray-300 dark:border-white/10 text-gray-700 dark:text-white rounded-xl hover:bg-gray-100 dark:hover:bg-white/5 hover:border-gray-400 dark:hover:border-white/20 font-bold transition-all">
                        Cancelar
                    </button>
                    <button onclick="requestTerminateCode()" id="terminateSubmitBtn" class="flex-1 px-5 py-3 bg-gradient-to-r from-red-600 to-red-700 text-white rounded-xl hover:from-red-700 hover:to-red-800 font-bold transition-all shadow-lg shadow-red-500/30 flex items-center justify-center gap-2">
                        <span>Enviar Código</span>
                    </button>
                </div>
            </div>

            <div id="terminateStep2" class="hidden space-y-4">
                <div class="p-4 rounded-xl bg-blue-500/10 border border-blue-500/20">
                    <p class="text-sm text-blue-400 flex items-start gap-2">
                        <i data-lucide="mail" class="w-4 h-4 mt-0.5 flex-shrink-0"></i>
                        <span>Enviamos um código de 6 dígitos para seu e-mail. Verifique sua caixa de entrada.</span>
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-bold text-zinc-300 mb-2">Código de Segurança</label>
                    <input type="text" id="terminateCode" maxlength="6" pattern="[0-9]{6}"
                           class="form-input w-full px-4 py-3 rounded-xl text-white text-center text-2xl tracking-widest font-mono placeholder:text-zinc-600 text-sm font-medium" 
                           placeholder="000000">
                </div>

                <div id="terminateError2" class="hidden p-3 rounded-lg bg-red-500/10 border border-red-500/20 text-sm text-red-400"></div>

                <div class="flex gap-3 pt-2">
                    <button onclick="backToTerminateStep1()" class="flex-1 px-5 py-3 border border-gray-300 dark:border-white/10 text-gray-700 dark:text-white rounded-xl hover:bg-gray-100 dark:hover:bg-white/5 hover:border-gray-400 dark:hover:border-white/20 font-bold transition-all">
                        Voltar
                    </button>
                    <button onclick="verifyTerminateCode()" id="terminateVerifyBtn" class="flex-1 px-5 py-3 bg-gradient-to-r from-red-600 to-red-700 text-white rounded-xl hover:from-red-700 hover:to-red-800 font-bold transition-all shadow-lg shadow-red-500/30 flex items-center justify-center gap-2">
                        <span>Confirmar</span>
                    </button>
                </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Excluir Conta - Redesign -->
    <div id="deleteAccountModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/90 backdrop-blur-md">
        <div class="glass-card rounded-2xl p-6 md:p-8 max-w-md w-full border border-red-500/30 relative overflow-hidden depth-shadow animate-scale-in">
            <!-- Grid pattern -->
            <div class="absolute inset-0 grid-pattern opacity-20"></div>
            
            <!-- Decoração de fundo -->
            <div class="absolute top-0 right-0 w-32 h-32 bg-red-500/5 rounded-full blur-3xl"></div>
            
            <div class="relative z-10">
            <div class="flex items-center gap-3 mb-6">
                    <div class="w-12 h-12 rounded-xl bg-red-600/15 border border-red-500/30 flex items-center justify-center">
                    <i data-lucide="trash-2" class="w-6 h-6 text-red-400"></i>
                </div>
                <div class="flex-1">
                    <h3 class="text-xl font-bold text-red-400">Excluir Conta</h3>
                        <p class="text-xs text-zinc-400 mt-0.5 font-medium">Ação irreversível - confirmação dupla</p>
                </div>
                    <button onclick="closeDeleteAccountModal()" class="p-2 text-zinc-400 hover:text-white hover:bg-white/5 rounded-lg transition-all">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <div id="deleteStep1" class="space-y-4">
                <div class="p-4 rounded-xl bg-red-500/10 border border-red-500/20">
                    <p class="text-sm text-red-400 flex items-start gap-2 mb-2">
                        <i data-lucide="alert-triangle" class="w-4 h-4 mt-0.5 flex-shrink-0"></i>
                        <span><strong>Atenção:</strong> Esta ação é PERMANENTE e IRREVERSÍVEL.</span>
                    </p>
                    <ul class="text-xs text-red-400/80 space-y-1 ml-6">
                        <li>• Todos os seus dados serão apagados</li>
                        <li>• Todos os seus sites serão removidos</li>
                        <li>• Não será possível recuperar sua conta</li>
                    </ul>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-zinc-300 mb-2">Sua Senha</label>
                    <div class="relative">
                        <input type="password" id="deletePassword" 
                               class="form-input w-full px-4 py-3 pr-12 rounded-xl text-white placeholder:text-zinc-600 text-sm font-medium" 
                               placeholder="Digite sua senha">
                        <button type="button" onclick="togglePasswordVisibility('deletePassword', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-zinc-500 hover:text-white transition-colors">
                            <i data-lucide="eye" id="eye-deletePassword" class="w-5 h-5"></i>
                        </button>
                    </div>
                </div>

                <div id="deleteError" class="hidden p-3 rounded-lg bg-red-500/10 border border-red-500/20 text-sm text-red-400"></div>

                <div class="flex gap-3 pt-2">
                    <button onclick="closeDeleteAccountModal()" class="flex-1 px-5 py-3 border border-gray-300 dark:border-white/10 text-gray-700 dark:text-white rounded-xl hover:bg-gray-100 dark:hover:bg-white/5 hover:border-gray-400 dark:hover:border-white/20 font-bold transition-all">
                        Cancelar
                    </button>
                    <button onclick="requestDeleteCode()" id="deleteSubmitBtn" class="flex-1 px-5 py-3 bg-gradient-to-r from-red-600 to-red-700 text-white rounded-xl hover:from-red-700 hover:to-red-800 font-bold transition-all shadow-lg shadow-red-500/30 flex items-center justify-center gap-2">
                        <span>Enviar Código</span>
                    </button>
                </div>
            </div>

            <div id="deleteStep2" class="hidden space-y-4">
                <div class="p-4 rounded-xl bg-red-500/10 border border-red-500/20">
                    <p class="text-sm text-red-400 flex items-start gap-2 mb-2">
                        <i data-lucide="mail" class="w-4 h-4 mt-0.5 flex-shrink-0"></i>
                        <span>Enviamos um código de 6 dígitos para seu e-mail. Este é o último passo antes de excluir sua conta permanentemente.</span>
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-bold text-zinc-300 mb-2">Código de Segurança</label>
                    <input type="text" id="deleteCode" maxlength="6" pattern="[0-9]{6}"
                           class="form-input w-full px-4 py-3 rounded-xl text-white text-center text-2xl tracking-widest font-mono placeholder:text-zinc-600 text-sm font-medium" 
                           placeholder="000000">
                </div>

                <div id="deleteError2" class="hidden p-3 rounded-lg bg-red-500/10 border border-red-500/20 text-sm text-red-400"></div>

                <div class="flex gap-3 pt-2">
                    <button onclick="backToDeleteStep1()" class="flex-1 px-5 py-3 border border-gray-300 dark:border-white/10 text-gray-700 dark:text-white rounded-xl hover:bg-gray-100 dark:hover:bg-white/5 hover:border-gray-400 dark:hover:border-white/20 font-bold transition-all">
                        Voltar
                    </button>
                    <button onclick="verifyDeleteCode()" id="deleteVerifyBtn" class="flex-1 px-5 py-3 bg-gradient-to-r from-red-600 to-red-700 text-white rounded-xl hover:from-red-700 hover:to-red-800 font-bold transition-all shadow-lg shadow-red-500/30 flex items-center justify-center gap-2">
                        <span>Excluir Permanentemente</span>
                    </button>
                </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();

        // ========== MODO DE EDIÇÃO DO PERFIL ==========
        let originalUsername = '';
        let originalFullName = '';
        
        // Inicializar valores originais ao carregar a página
        document.addEventListener('DOMContentLoaded', function() {
            originalUsername = document.getElementById('inputUsername').value;
            originalFullName = document.getElementById('inputFullName').value;
            
            // Se houver mensagem de erro, ativar modo de edição automaticamente
            <?php if ($message && $messageType === 'error'): ?>
            enableEditMode();
            <?php endif; ?>
        });
        
        function enableEditMode() {
            // Salvar valores originais
            originalUsername = document.getElementById('inputUsername').value;
            originalFullName = document.getElementById('inputFullName').value;
            
            // Habilitar campos
            const usernameInput = document.getElementById('inputUsername');
            const fullNameInput = document.getElementById('inputFullName');
            
            usernameInput.disabled = false;
            fullNameInput.disabled = false;
            
            // Atualizar classes para modo editável
            usernameInput.classList.remove('text-zinc-500', 'cursor-not-allowed');
            usernameInput.classList.add('text-white', 'cursor-text', 'focus:ring-2', 'focus:ring-white/50', 'focus:border-white/50');
            
            fullNameInput.classList.remove('text-zinc-500', 'cursor-not-allowed');
            fullNameInput.classList.add('text-white', 'cursor-text', 'focus:ring-2', 'focus:ring-white/50', 'focus:border-white/50');
            
            // Mostrar/esconder botões
            document.getElementById('editButtonContainer').classList.add('hidden');
            document.getElementById('actionButtonsContainer').classList.remove('hidden');
            
            // Focar no primeiro campo
            usernameInput.focus();
            
            lucide.createIcons();
        }
        
        function cancelEditMode() {
            // Restaurar valores originais
            document.getElementById('inputUsername').value = originalUsername;
            document.getElementById('inputFullName').value = originalFullName;
            
            // Desabilitar campos
            const usernameInput = document.getElementById('inputUsername');
            const fullNameInput = document.getElementById('inputFullName');
            
            usernameInput.disabled = true;
            fullNameInput.disabled = true;
            
            // Restaurar classes para modo visualização
            usernameInput.classList.remove('text-white', 'cursor-text', 'focus:ring-2', 'focus:ring-white/50', 'focus:border-white/50');
            usernameInput.classList.add('text-zinc-500', 'cursor-not-allowed');
            
            fullNameInput.classList.remove('text-white', 'cursor-text', 'focus:ring-2', 'focus:ring-white/50', 'focus:border-white/50');
            fullNameInput.classList.add('text-zinc-500', 'cursor-not-allowed');
            
            // Mostrar/esconder botões
            document.getElementById('editButtonContainer').classList.remove('hidden');
            document.getElementById('actionButtonsContainer').classList.add('hidden');
            
            lucide.createIcons();
        }

        // ========== MODAL DE CONFIRMAÇÃO ==========
        let confirmCallback = null;
        let successCallback = null;

        function showConfirmModal(title, message, cancelText, okText, callback) {
            document.getElementById('confirmTitle').textContent = title;
            document.getElementById('confirmMessage').textContent = message;
            document.getElementById('confirmCancelBtn').textContent = cancelText || 'Cancelar';
            document.getElementById('confirmOkBtn').textContent = okText || 'Continuar';
            confirmCallback = callback;
            document.getElementById('confirmModal').classList.remove('hidden');
            lucide.createIcons();
        }

        function closeConfirmModal() {
            document.getElementById('confirmModal').classList.add('hidden');
            confirmCallback = null;
        }

        document.getElementById('confirmOkBtn').addEventListener('click', function() {
            if (confirmCallback) {
                confirmCallback();
                closeConfirmModal();
            }
        });

        // ========== MODAL DE SUCESSO ==========
        function showSuccessModal(message, callback) {
            document.getElementById('successMessage').textContent = message;
            successCallback = callback;
            document.getElementById('successModal').classList.remove('hidden');
            lucide.createIcons();
        }

        function closeSuccessModal() {
            document.getElementById('successModal').classList.add('hidden');
            if (successCallback) {
                successCallback();
                successCallback = null;
            }
        }

        document.getElementById('successOkBtn').addEventListener('click', function() {
            closeSuccessModal();
        });

        // ========== ENCERRAR SESSÕES ==========
        function openTerminateSessionsModal() {
            document.getElementById('terminateSessionsModal').classList.remove('hidden');
            document.getElementById('terminateStep1').classList.remove('hidden');
            document.getElementById('terminateStep2').classList.add('hidden');
            document.getElementById('terminatePassword').value = '';
            document.getElementById('terminateCode').value = '';
            document.getElementById('terminateError').classList.add('hidden');
            document.getElementById('terminateError2').classList.add('hidden');
        }

        function closeTerminateSessionsModal() {
            document.getElementById('terminateSessionsModal').classList.add('hidden');
        }

        function backToTerminateStep1() {
            document.getElementById('terminateStep1').classList.remove('hidden');
            document.getElementById('terminateStep2').classList.add('hidden');
            document.getElementById('terminateCode').value = '';
            document.getElementById('terminateError2').classList.add('hidden');
        }

        function requestTerminateCode() {
            const password = document.getElementById('terminatePassword').value;
            const errorDiv = document.getElementById('terminateError');
            const submitBtn = document.getElementById('terminateSubmitBtn');

            if (!password) {
                errorDiv.textContent = 'Por favor, digite sua senha';
                errorDiv.classList.remove('hidden');
                return;
            }

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i>';

            const formData = new FormData();
            formData.append('action', 'terminate_sessions');
            formData.append('step', 'request_code');
            formData.append('password', password);
            formData.append('csrf_token', '<?php echo $csrfToken; ?>');

            fetch('api/dangerous-action.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('terminateStep1').classList.add('hidden');
                    document.getElementById('terminateStep2').classList.remove('hidden');
                    errorDiv.classList.add('hidden');
                } else {
                    errorDiv.textContent = data.error || 'Erro ao enviar código';
                    errorDiv.classList.remove('hidden');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<span>Enviar Código</span>';
                }
                lucide.createIcons();
            })
            .catch(error => {
                errorDiv.textContent = 'Erro ao processar solicitação';
                errorDiv.classList.remove('hidden');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<span>Enviar Código</span>';
            });
        }

        function verifyTerminateCode() {
            const code = document.getElementById('terminateCode').value;
            const errorDiv = document.getElementById('terminateError2');
            const verifyBtn = document.getElementById('terminateVerifyBtn');

            if (!code || code.length !== 6) {
                errorDiv.textContent = 'Por favor, digite o código de 6 dígitos';
                errorDiv.classList.remove('hidden');
                return;
            }

            verifyBtn.disabled = true;
            verifyBtn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i>';

            const formData = new FormData();
            formData.append('action', 'terminate_sessions');
            formData.append('step', 'verify_code');
            formData.append('otp_code', code);
            formData.append('csrf_token', '<?php echo $csrfToken; ?>');

            fetch('api/dangerous-action.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    } else {
                        showSuccessModal(data.message || 'Sessões encerradas com sucesso!', () => {
                            window.location.reload();
                        });
                    }
                } else {
                    errorDiv.textContent = data.error || 'Código inválido';
                    errorDiv.classList.remove('hidden');
                    verifyBtn.disabled = false;
                    verifyBtn.innerHTML = '<span>Confirmar</span>';
                }
                lucide.createIcons();
            })
            .catch(error => {
                errorDiv.textContent = 'Erro ao processar solicitação';
                errorDiv.classList.remove('hidden');
                verifyBtn.disabled = false;
                verifyBtn.innerHTML = '<span>Confirmar</span>';
            });
        }

        // ========== EXCLUIR CONTA ==========
        function openDeleteAccountModal() {
            document.getElementById('deleteAccountModal').classList.remove('hidden');
            document.getElementById('deleteStep1').classList.remove('hidden');
            document.getElementById('deleteStep2').classList.add('hidden');
            document.getElementById('deletePassword').value = '';
            document.getElementById('deleteCode').value = '';
            document.getElementById('deleteError').classList.add('hidden');
            document.getElementById('deleteError2').classList.add('hidden');
        }

        function closeDeleteAccountModal() {
            showConfirmModal(
                'Cancelar Exclusão?',
                'Tem certeza que deseja cancelar? Todos os progressos serão perdidos.',
                'Cancelar',
                'Continuar',
                () => {
                    document.getElementById('deleteAccountModal').classList.add('hidden');
                }
            );
        }

        function backToDeleteStep1() {
            document.getElementById('deleteStep1').classList.remove('hidden');
            document.getElementById('deleteStep2').classList.add('hidden');
            document.getElementById('deleteCode').value = '';
            document.getElementById('deleteError2').classList.add('hidden');
        }

        function requestDeleteCode() {
            const password = document.getElementById('deletePassword').value;
            const errorDiv = document.getElementById('deleteError');
            const submitBtn = document.getElementById('deleteSubmitBtn');

            if (!password) {
                errorDiv.textContent = 'Por favor, digite sua senha';
                errorDiv.classList.remove('hidden');
                return;
            }

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i>';

            const formData = new FormData();
            formData.append('action', 'delete_account');
            formData.append('step', 'request_code');
            formData.append('password', password);
            formData.append('csrf_token', '<?php echo $csrfToken; ?>');

            fetch('api/dangerous-action.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('deleteStep1').classList.add('hidden');
                    document.getElementById('deleteStep2').classList.remove('hidden');
                    errorDiv.classList.add('hidden');
                } else {
                    errorDiv.textContent = data.error || 'Erro ao enviar código';
                    errorDiv.classList.remove('hidden');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<span>Enviar Código</span>';
                }
                lucide.createIcons();
            })
            .catch(error => {
                errorDiv.textContent = 'Erro ao processar solicitação';
                errorDiv.classList.remove('hidden');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<span>Enviar Código</span>';
            });
        }

        function verifyDeleteCode() {
            showConfirmModal(
                'ÚLTIMA CONFIRMAÇÃO',
                'Tem certeza que deseja excluir sua conta permanentemente? Esta ação NÃO pode ser desfeita.',
                'Cancelar',
                'Excluir Permanentemente',
                () => {
                    proceedWithDelete();
                }
            );
        }

        function proceedWithDelete() {

            const code = document.getElementById('deleteCode').value;
            const errorDiv = document.getElementById('deleteError2');
            const verifyBtn = document.getElementById('deleteVerifyBtn');

            if (!code || code.length !== 6) {
                errorDiv.textContent = 'Por favor, digite o código de 6 dígitos';
                errorDiv.classList.remove('hidden');
                return;
            }

            verifyBtn.disabled = true;
            verifyBtn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> Excluindo...';

            const formData = new FormData();
            formData.append('action', 'delete_account');
            formData.append('step', 'verify_code');
            formData.append('otp_code', code);
            formData.append('csrf_token', '<?php echo $csrfToken; ?>');

            fetch('api/dangerous-action.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccessModal(data.message || 'Conta excluída permanentemente.', () => {
                        if (data.redirect) {
                            window.location.href = data.redirect;
                        } else {
                            window.location.href = 'index.php';
                        }
                    });
                } else {
                    errorDiv.textContent = data.error || 'Código inválido';
                    errorDiv.classList.remove('hidden');
                    verifyBtn.disabled = false;
                    verifyBtn.innerHTML = '<span>Excluir Permanentemente</span>';
                }
                lucide.createIcons();
            })
            .catch(error => {
                errorDiv.textContent = 'Erro ao processar solicitação';
                errorDiv.classList.remove('hidden');
                verifyBtn.disabled = false;
                verifyBtn.innerHTML = '<span>Excluir Permanentemente</span>';
            });
        }

        // ========== UTILS ==========
        function togglePasswordVisibility(fieldId, buttonElement) {
            const input = document.getElementById(fieldId);
            if (!input) {
                console.error('Campo não encontrado:', fieldId);
                return;
            }
            
            // Se o botão foi passado como parâmetro, usar ele, senão procurar
            const button = buttonElement || input.parentElement.querySelector('button');
            if (!button) {
                console.error('Botão não encontrado para:', fieldId);
                return;
            }
            
            // Tentar encontrar o ícone pelo ID primeiro, senão procurar no botão
            let icon = document.getElementById('eye-' + fieldId);
            if (!icon) {
                icon = button.querySelector('i');
            }
            
            if (!icon) {
                console.error('Ícone não encontrado para:', fieldId);
                return;
            }
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.setAttribute('data-lucide', 'eye-off');
            } else {
                input.type = 'password';
                icon.setAttribute('data-lucide', 'eye');
            }
            
            // Atualizar o ícone do Lucide
            lucide.createIcons();
        }

        // Auto-focus no código quando aparecer
        document.getElementById('terminateCode')?.addEventListener('input', function(e) {
            this.value = this.value.replace(/\D/g, '').slice(0, 6);
        });

        document.getElementById('deleteCode')?.addEventListener('input', function(e) {
            this.value = this.value.replace(/\D/g, '').slice(0, 6);
        });
    </script>
    
    <!-- Security Scripts - Previne download de código -->
    <script src="includes/security-scripts.js"></script>
</body>
</html>

