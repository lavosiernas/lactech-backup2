<?php
/**
 * SafeNode - Documentação da API e Sistema
 * Página de documentação completa do SafeNode
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

$pageTitle = 'Documentação';
?>
<!DOCTYPE html>
<html lang="pt-BR" class="dark h-full">
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
        
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { 
            background: rgba(255,255,255,0.1); 
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.15); }
        
        .glass-card {
            background: var(--bg-card);
            border: 1px solid var(--border-subtle);
            border-radius: 16px;
            padding: 24px;
            position: relative;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .glass-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent 0%, rgba(255,255,255,0.1) 50%, transparent 100%);
        }
        
        .glass-card:hover {
            border-color: var(--border-light);
            transform: translateY(-2px);
            box-shadow: 0 20px 40px -20px rgba(0,0,0,0.5), 0 0 60px -30px var(--accent-glow);
        }
        
        .code-block {
            background: #000000;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 20px;
            overflow-x: auto;
            font-family: 'JetBrains Mono', monospace;
            font-size: 13px;
            line-height: 1.6;
            position: relative;
        }
        
        .code-block code {
            color: #a1a1aa;
        }
        
        .code-block .keyword { color: #c792ea; }
        .code-block .string { color: #c3e88d; }
        .code-block .function { color: #82aaff; }
        .code-block .comment { color: #546e7a; font-style: italic; }
        .code-block .number { color: #f78c6c; }
        
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
        
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .badge-get { background: rgba(34, 197, 94, 0.2); color: #22c55e; }
        .badge-post { background: rgba(59, 130, 246, 0.2); color: #3b82f6; }
        .badge-put { background: rgba(251, 191, 36, 0.2); color: #fbbf24; }
        .badge-delete { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
        
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
            box-shadow: 0 10px 30px rgba(255,255,255,0.2);
        }
        
        .btn-primary:hover::before {
            opacity: 1;
        }
        
        [x-cloak] { display: none !important; }
    </style>
</head>
<body x-data="{ notificationsOpen: false, sidebarOpen: false, sidebarCollapsed: false, activeSection: 'introduction' }" 
      x-init="$watch('sidebarCollapsed', () => { setTimeout(() => { lucide.createIcons(); }, 150) })"
      class="h-full overflow-hidden flex">

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
            
            <a href="dashboard.php" class="nav-item" :class="sidebarCollapsed ? 'justify-center px-2' : ''" :title="sidebarCollapsed ? 'Home' : ''">
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
                <a href="documentation.php" class="nav-item active" :class="sidebarCollapsed ? 'justify-center px-2' : ''" :title="sidebarCollapsed ? 'Documentação' : ''">
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

    <!-- Main Content -->
    <main class="flex-1 flex flex-col h-full overflow-hidden bg-dark-950">
        <!-- Header -->
        <header class="h-20 bg-dark-900/50 backdrop-blur-xl border-b border-white/5 px-8 flex items-center justify-between flex-shrink-0">
            <div class="flex items-center gap-6">
                <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden text-zinc-400 hover:text-white transition-colors">
                    <i data-lucide="menu" class="w-6 h-6"></i>
                </button>
                <div>
                    <h2 class="text-2xl font-bold text-white tracking-tight"><?php echo $pageTitle; ?></h2>
                    <p class="text-sm text-zinc-500 font-mono mt-0.5">Documentação completa da API e sistema</p>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <button onclick="window.location.href='profile.php'" class="flex items-center gap-3 p-2 hover:bg-white/5 rounded-xl transition-all group">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-orange-400 to-rose-500 flex items-center justify-center text-white font-bold text-sm shadow-lg group-hover:scale-105 transition-transform">
                        <?php echo strtoupper(substr($_SESSION['safenode_username'] ?? 'U', 0, 1)); ?>
                    </div>
                </button>
            </div>
        </header>

        <!-- Scrollable Content -->
        <div class="flex-1 overflow-y-auto p-8">
            <div class="max-w-6xl mx-auto">
                <!-- Navigation Tabs -->
                <div class="glass-card mb-6 p-4">
                    <div class="flex flex-wrap gap-2">
                        <button @click="activeSection = 'introduction'" 
                                :class="activeSection === 'introduction' ? 'bg-white/10 text-white' : 'bg-white/5 text-zinc-400 hover:bg-white/10'"
                                class="px-4 py-2 rounded-lg text-sm font-medium transition-all flex items-center gap-2">
                            <i data-lucide="book-open" class="w-4 h-4"></i>
                            Introdução
                        </button>
                        <button @click="activeSection = 'api'" 
                                :class="activeSection === 'api' ? 'bg-white/10 text-white' : 'bg-white/5 text-zinc-400 hover:bg-white/10'"
                                class="px-4 py-2 rounded-lg text-sm font-medium transition-all flex items-center gap-2">
                            <i data-lucide="server" class="w-4 h-4"></i>
                            API Endpoints
                        </button>
                        <button @click="activeSection = 'architecture'" 
                                :class="activeSection === 'architecture' ? 'bg-white/10 text-white' : 'bg-white/5 text-zinc-400 hover:bg-white/10'"
                                class="px-4 py-2 rounded-lg text-sm font-medium transition-all flex items-center gap-2">
                            <i data-lucide="layers" class="w-4 h-4"></i>
                            Arquitetura
                        </button>
                        <button @click="activeSection = 'integration'" 
                                :class="activeSection === 'integration' ? 'bg-white/10 text-white' : 'bg-white/5 text-zinc-400 hover:bg-white/10'"
                                class="px-4 py-2 rounded-lg text-sm font-medium transition-all flex items-center gap-2">
                            <i data-lucide="plug" class="w-4 h-4"></i>
                            Integração
                        </button>
                        <button @click="activeSection = 'standards'" 
                                :class="activeSection === 'standards' ? 'bg-white/10 text-white' : 'bg-white/5 text-zinc-400 hover:bg-white/10'"
                                class="px-4 py-2 rounded-lg text-sm font-medium transition-all flex items-center gap-2">
                            <i data-lucide="code" class="w-4 h-4"></i>
                            Padrões
                        </button>
                        <button @click="activeSection = 'database'" 
                                :class="activeSection === 'database' ? 'bg-white/10 text-white' : 'bg-white/5 text-zinc-400 hover:bg-white/10'"
                                class="px-4 py-2 rounded-lg text-sm font-medium transition-all flex items-center gap-2">
                            <i data-lucide="database" class="w-4 h-4"></i>
                            Banco de Dados
                        </button>
                    </div>
                </div>
                
                <!-- Content Sections -->
                <div class="space-y-6">
                <!-- Introduction -->
                <section id="introduction" x-show="activeSection === 'introduction'" class="space-y-6">
                    <div class="glass-card">
                        <div class="flex items-center gap-4 mb-6">
                            <div class="w-12 h-12 rounded-xl bg-blue-600/20 border border-blue-500/30 flex items-center justify-center">
                                <i data-lucide="book-open" class="w-6 h-6 text-blue-400"></i>
                            </div>
                            <div>
                                <h1 class="text-3xl font-bold text-white mb-2">Documentação SafeNode</h1>
                                <p class="text-zinc-400">Guia completo da API e arquitetura do sistema</p>
                            </div>
                        </div>
                        
                        <div class="prose prose-invert max-w-none">
                            <p class="text-zinc-300 leading-relaxed mb-4">
                                O SafeNode é uma plataforma de segurança completa que oferece proteção em tempo real contra ameaças,
                                análise comportamental e monitoramento avançado. Esta documentação cobre todos os aspectos da API,
                                estrutura do código e como integrar o sistema.
                            </p>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
                                <div class="p-4 rounded-xl bg-white/5 border border-white/10">
                                    <div class="flex items-center gap-2 mb-2">
                                        <i data-lucide="shield" class="w-5 h-5 text-green-400"></i>
                                        <h3 class="font-semibold text-white">Segurança</h3>
                                    </div>
                                    <p class="text-sm text-zinc-400">Proteção em tempo real contra ameaças</p>
                                </div>
                                
                                <div class="p-4 rounded-xl bg-white/5 border border-white/10">
                                    <div class="flex items-center gap-2 mb-2">
                                        <i data-lucide="activity" class="w-5 h-5 text-blue-400"></i>
                                        <h3 class="font-semibold text-white">Monitoramento</h3>
                                    </div>
                                    <p class="text-sm text-zinc-400">Análise e logs detalhados</p>
                                </div>
                                
                                <div class="p-4 rounded-xl bg-white/5 border border-white/10">
                                    <div class="flex items-center gap-2 mb-2">
                                        <i data-lucide="code" class="w-5 h-5 text-purple-400"></i>
                                        <h3 class="font-semibold text-white">API REST</h3>
                                    </div>
                                    <p class="text-sm text-zinc-400">Integração fácil e completa</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- API Endpoints -->
                <section id="api-endpoints" x-show="activeSection === 'api'" x-cloak class="space-y-6" style="display: none;">
                    <div class="glass-card">
                        <h2 class="text-2xl font-bold text-white mb-6 flex items-center gap-3">
                            <i data-lucide="server" class="w-6 h-6 text-blue-400"></i>
                            Endpoints da API
                        </h2>
                        
                        <!-- Dashboard Stats -->
                        <div class="mb-8 pb-8 border-b border-white/10">
                            <div class="flex items-center gap-3 mb-4">
                                <span class="badge badge-get">GET</span>
                                <code class="text-white font-mono">/api/dashboard-stats.php</code>
                            </div>
                            <p class="text-zinc-400 mb-4">Retorna estatísticas em tempo real do dashboard</p>
                            
                            <div class="code-block mb-4">
                                <code>
<span class="comment">// Exemplo de requisição</span>
<span class="keyword">fetch</span>(<span class="string">'api/dashboard-stats.php'</span>)
  .<span class="function">then</span>(<span class="keyword">res</span> => <span class="keyword">res</span>.<span class="function">json</span>())
  .<span class="function">then</span>(<span class="keyword">data</span> => {
    <span class="comment">// data.today.total_requests</span>
    <span class="comment">// data.today.blocked</span>
    <span class="comment">// data.top_blocked_ips</span>
  });
                                </code>
                            </div>
                            
                            <div class="p-4 rounded-xl bg-white/5 border border-white/10">
                                <h4 class="text-sm font-semibold text-white mb-2">Resposta:</h4>
                                <pre class="text-xs text-zinc-400 font-mono overflow-x-auto">{
  "success": true,
  "data": {
    "today": {
      "total_requests": 1250,
      "blocked": 45,
      "unique_ips": 320
    },
    "top_blocked_ips": [...],
    "recent_incidents": [...]
  }
}</pre>
                            </div>
                        </div>
                        
                        <!-- Notifications -->
                        <div class="mb-8 pb-8 border-b border-white/10">
                            <div class="flex items-center gap-3 mb-4">
                                <span class="badge badge-get">GET</span>
                                <code class="text-white font-mono">/api/notifications.php</code>
                            </div>
                            <p class="text-zinc-400 mb-4">Retorna notificações e alertas do sistema</p>
                            
                            <div class="code-block mb-4">
                                <code>
<span class="comment">// Buscar notificações não lidas</span>
<span class="keyword">fetch</span>(<span class="string">'api/notifications.php?unread=1'</span>)
  .<span class="function">then</span>(<span class="keyword">res</span> => <span class="keyword">res</span>.<span class="function">json</span>())
  .<span class="function">then</span>(<span class="keyword">data</span> => {
    <span class="comment">// data.count - número de notificações não lidas</span>
  });

<span class="comment">// Listar notificações</span>
<span class="keyword">fetch</span>(<span class="string">'api/notifications.php?limit=20'</span>)
  .<span class="function">then</span>(<span class="keyword">res</span> => <span class="keyword">res</span>.<span class="function">json</span>())
  .<span class="function">then</span>(<span class="keyword">data</span> => {
    <span class="comment">// data.notifications - array de notificações</span>
  });
                                </code>
                            </div>
                        </div>
                        
                        <!-- Dangerous Actions -->
                        <div class="mb-8 pb-8 border-b border-white/10">
                            <div class="flex items-center gap-3 mb-4">
                                <span class="badge badge-post">POST</span>
                                <code class="text-white font-mono">/api/dangerous-action.php</code>
                            </div>
                            <p class="text-zinc-400 mb-4">Executa ações perigosas (requer autenticação adicional)</p>
                            
                            <div class="code-block mb-4">
                                <code>
<span class="comment">// Encerrar todas as sessões</span>
<span class="keyword">const</span> <span class="function">formData</span> = <span class="keyword">new</span> <span class="function">FormData</span>();
<span class="function">formData</span>.<span class="function">append</span>(<span class="string">'action'</span>, <span class="string">'terminate_all_sessions'</span>);
<span class="function">formData</span>.<span class="function">append</span>(<span class="string">'password'</span>, <span class="string">'senha_do_usuario'</span>);
<span class="function">formData</span>.<span class="function">append</span>(<span class="string">'otp_code'</span>, <span class="string">'123456'</span>);

<span class="keyword">fetch</span>(<span class="string">'api/dangerous-action.php'</span>, {
  <span class="keyword">method</span>: <span class="string">'POST'</span>,
  <span class="keyword">body</span>: <span class="function">formData</span>
});
                                </code>
                            </div>
                        </div>
                        
                        <!-- SDK Endpoints -->
                        <div class="mb-8">
                            <h3 class="text-lg font-semibold text-white mb-4">SDK Endpoints</h3>
                            
                            <div class="space-y-4">
                                <div class="p-4 rounded-xl bg-white/5 border border-white/10">
                                    <div class="flex items-center gap-3 mb-2">
                                        <span class="badge badge-post">POST</span>
                                        <code class="text-white font-mono text-sm">/api/sdk/validate.php</code>
                                    </div>
                                    <p class="text-xs text-zinc-400">Valida requisições através do SDK SafeNode</p>
                                </div>
                                
                                <div class="p-4 rounded-xl bg-white/5 border border-white/10">
                                    <div class="flex items-center gap-3 mb-2">
                                        <span class="badge badge-get">GET</span>
                                        <code class="text-white font-mono text-sm">/api/sdk/init.php</code>
                                    </div>
                                    <p class="text-xs text-zinc-400">Inicializa o SDK e retorna configurações</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Architecture -->
                <section id="architecture" x-show="activeSection === 'architecture'" x-cloak class="space-y-6" style="display: none;">
                    <div class="glass-card">
                        <h2 class="text-2xl font-bold text-white mb-6 flex items-center gap-3">
                            <i data-lucide="layers" class="w-6 h-6 text-purple-400"></i>
                            Arquitetura do Sistema
                        </h2>
                        
                        <div class="space-y-6">
                            <div>
                                <h3 class="text-lg font-semibold text-white mb-3">Estrutura de Diretórios</h3>
                                <div class="code-block">
                                    <code>
safenode/
├── api/                    <span class="comment"># Endpoints da API</span>
│   ├── dashboard-stats.php
│   ├── notifications.php
│   └── sdk/
├── includes/               <span class="comment"># Classes e helpers</span>
│   ├── SecurityLogger.php
│   ├── ThreatDetector.php
│   ├── BehaviorAnalyzer.php
│   └── SecurityHelpers.php
├── assets/                 <span class="comment"># Recursos estáticos</span>
└── sdk/                    <span class="comment"># SDK JavaScript</span>
                                    </code>
                                </div>
                            </div>
                            
                            <div>
                                <h3 class="text-lg font-semibold text-white mb-3">Classes Principais</h3>
                                <div class="space-y-3">
                                    <div class="p-4 rounded-xl bg-white/5 border border-white/10">
                                        <h4 class="font-semibold text-white mb-2">SecurityLogger</h4>
                                        <p class="text-sm text-zinc-400 mb-2">Registra eventos de segurança no banco de dados</p>
                                        <code class="text-xs text-zinc-500">includes/SecurityLogger.php</code>
                                    </div>
                                    
                                    <div class="p-4 rounded-xl bg-white/5 border border-white/10">
                                        <h4 class="font-semibold text-white mb-2">ThreatDetector</h4>
                                        <p class="text-sm text-zinc-400 mb-2">Detecta e classifica ameaças em requisições</p>
                                        <code class="text-xs text-zinc-500">includes/ThreatDetector.php</code>
                                    </div>
                                    
                                    <div class="p-4 rounded-xl bg-white/5 border border-white/10">
                                        <h4 class="font-semibold text-white mb-2">BehaviorAnalyzer</h4>
                                        <p class="text-sm text-zinc-400 mb-2">Analisa padrões comportamentais suspeitos</p>
                                        <code class="text-xs text-zinc-500">includes/BehaviorAnalyzer.php</code>
                                    </div>
                                    
                                    <div class="p-4 rounded-xl bg-white/5 border border-white/10">
                                        <h4 class="font-semibold text-white mb-2">SecurityAnalytics</h4>
                                        <p class="text-sm text-zinc-400 mb-2">Gera insights e análises avançadas</p>
                                        <code class="text-xs text-zinc-500">includes/SecurityAnalytics.php</code>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Integration Guide -->
                <section id="integration" x-show="activeSection === 'integration'" x-cloak class="space-y-6" style="display: none;">
                    <div class="glass-card">
                        <h2 class="text-2xl font-bold text-white mb-6 flex items-center gap-3">
                            <i data-lucide="plug" class="w-6 h-6 text-green-400"></i>
                            Guia de Integração
                        </h2>
                        
                        <div class="space-y-6">
                            <div>
                                <h3 class="text-lg font-semibold text-white mb-3">1. Instalação do SDK</h3>
                                <div class="code-block">
                                    <code>
<span class="comment">&lt;!-- Adicione o SDK no seu HTML --&gt;</span>
<span class="keyword">&lt;script</span> <span class="function">src</span>=<span class="string">"https://safenode.cloud/sdk/safenode-hv.js"</span><span class="keyword">&gt;&lt;/script&gt;</span>

<span class="comment">// Ou via npm (futuro)</span>
<span class="function">npm</span> <span class="keyword">install</span> <span class="string">@safenode/sdk</span>
                                    </code>
                                </div>
                            </div>
                            
                            <div>
                                <h3 class="text-lg font-semibold text-white mb-3">2. Inicialização</h3>
                                <div class="code-block">
                                    <code>
<span class="keyword">const</span> <span class="function">safenode</span> = <span class="keyword">new</span> <span class="function">SafeNode</span>({
  <span class="keyword">apiKey</span>: <span class="string">'sua-api-key'</span>,
  <span class="keyword">siteId</span>: <span class="number">123</span>
});

<span class="comment">// Validar requisição</span>
<span class="function">safenode</span>.<span class="function">validate</span>(<span class="keyword">request</span>)
  .<span class="function">then</span>(<span class="keyword">result</span> => {
    <span class="keyword">if</span> (<span class="keyword">result</span>.<span class="function">isValid</span>) {
      <span class="comment">// Processar requisição</span>
    } <span class="keyword">else</span> {
      <span class="comment">// Bloquear requisição</span>
    }
  });
                                    </code>
                                </div>
                            </div>
                            
                            <div>
                                <h3 class="text-lg font-semibold text-white mb-3">3. Exemplo Completo (PHP)</h3>
                                <div class="code-block">
                                    <code>
<span class="comment">&lt;?php</span>
<span class="keyword">require_once</span> <span class="string">'includes/SafeNodeMiddleware.php'</span>;

<span class="comment">// Aplicar middleware em todas as requisições</span>
<span class="function">SafeNodeMiddleware</span>::<span class="function">protect</span>();

<span class="comment">// Seu código continua normalmente</span>
<span class="keyword">echo</span> <span class="string">"Página protegida!"</span>;
                                    </code>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Code Standards -->
                <section id="standards" x-show="activeSection === 'standards'" x-cloak class="space-y-6" style="display: none;">
                    <div class="glass-card">
                        <h2 class="text-2xl font-bold text-white mb-6 flex items-center gap-3">
                            <i data-lucide="code" class="w-6 h-6 text-amber-400"></i>
                            Padrões de Código
                        </h2>
                        
                        <div class="space-y-6">
                            <div>
                                <h3 class="text-lg font-semibold text-white mb-3">PSR-12 Coding Standard</h3>
                                <p class="text-zinc-400 mb-4">
                                    O SafeNode segue o padrão PSR-12 para garantir consistência e legibilidade do código.
                                </p>
                                
                                <div class="p-4 rounded-xl bg-white/5 border border-white/10">
                                    <h4 class="text-sm font-semibold text-white mb-2">Principais Regras:</h4>
                                    <ul class="text-sm text-zinc-400 space-y-2 list-disc list-inside">
                                        <li>Indentação: 4 espaços (não tabs)</li>
                                        <li>Linhas: máximo 120 caracteres</li>
                                        <li>Nomes de classes: PascalCase</li>
                                        <li>Nomes de métodos: camelCase</li>
                                        <li>Constantes: UPPER_SNAKE_CASE</li>
                                    </ul>
                                </div>
                            </div>
                            
                            <div>
                                <h3 class="text-lg font-semibold text-white mb-3">Estrutura de Classes</h3>
                                <div class="code-block">
                                    <code>
<span class="comment">&lt;?php</span>
<span class="comment">/**
 * SafeNode - Nome da Classe
 * Descrição breve da funcionalidade
 */</span>

<span class="keyword">class</span> <span class="function">ClassName</span> {
    <span class="comment">// Propriedades privadas</span>
    <span class="keyword">private</span> <span class="function">$property</span>;
    
    <span class="comment">// Construtor</span>
    <span class="keyword">public function</span> <span class="function">__construct</span>(<span class="function">$param</span>) {
        <span class="keyword">this</span>-><span class="function">property</span> = <span class="function">$param</span>;
    }
    
    <span class="comment">// Métodos públicos</span>
    <span class="keyword">public function</span> <span class="function">publicMethod</span>() {
        <span class="comment">// Implementação</span>
    }
}
                                    </code>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Database Schema -->
                <section id="database" x-show="activeSection === 'database'" x-cloak class="space-y-6" style="display: none;">
                    <div class="glass-card">
                        <h2 class="text-2xl font-bold text-white mb-6 flex items-center gap-3">
                            <i data-lucide="database" class="w-6 h-6 text-cyan-400"></i>
                            Estrutura do Banco de Dados
                        </h2>
                        
                        <div class="space-y-4">
                            <div class="p-4 rounded-xl bg-white/5 border border-white/10">
                                <h4 class="font-semibold text-white mb-2">safenode_security_logs</h4>
                                <p class="text-sm text-zinc-400 mb-2">Armazena todos os eventos de segurança</p>
                                <code class="text-xs text-zinc-500">Campos principais: ip_address, threat_type, threat_score, action_taken, created_at</code>
                            </div>
                            
                            <div class="p-4 rounded-xl bg-white/5 border border-white/10">
                                <h4 class="font-semibold text-white mb-2">safenode_sites</h4>
                                <p class="text-sm text-zinc-400 mb-2">Sites protegidos pelo sistema</p>
                                <code class="text-xs text-zinc-500">Campos principais: domain, display_name, security_level, is_active</code>
                            </div>
                            
                            <div class="p-4 rounded-xl bg-white/5 border border-white/10">
                                <h4 class="font-semibold text-white mb-2">safenode_users</h4>
                                <p class="text-sm text-zinc-400 mb-2">Usuários do sistema</p>
                                <code class="text-xs text-zinc-500">Campos principais: email, username, password_hash, role</code>
                            </div>
                        </div>
                    </div>
                </section>
                </div>
            </div>
        </div>
    </main>

    <script>
        lucide.createIcons();
        
        // Inicializar primeira seção visível
        document.addEventListener('DOMContentLoaded', function() {
            const firstSection = document.querySelector('#introduction');
            if (firstSection) {
                firstSection.style.display = 'block';
            }
        });
    </script>
    
    <script src="includes/security-scripts.js"></script>
</body>
</html>

