<?php
/**
 * SafeNode - Histórico de Atividades
 * Visualização de ações e eventos da conta
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
require_once __DIR__ . '/includes/ActivityLogger.php';

$db = getSafeNodeDatabase();
$activityLogger = new ActivityLogger($db);

$userId = $_SESSION['safenode_user_id'] ?? null;

// Paginação
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Buscar atividades
$activities = $activityLogger->getUserActivities($userId, $perPage, $offset);
$totalActivities = $activityLogger->countUserActivities($userId);
$totalPages = ceil($totalActivities / $perPage);

$pageTitle = 'Histórico de Atividades';
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
        
        .glass {
            background: rgba(10, 10, 10, 0.7);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--border-subtle);
        }
        
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
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px -10px rgba(255, 255, 255, 0.5);
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
        
        .nav-item:hover {
            color: var(--text-primary);
        }
        
        .nav-item.active {
            color: var(--accent);
            background: linear-gradient(90deg, rgba(255, 255, 255, 0.1) 0%, transparent 100%);
        }
        
        .upgrade-card {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 16px;
        }
        
        [x-cloak] { display: none !important; }
    </style>
</head>
<body x-data="{ notificationsOpen: false, sidebarOpen: false, sidebarCollapsed: false }" 
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
                <a href="profile.php" class="nav-item" :class="sidebarCollapsed ? 'justify-center px-2' : ''" :title="sidebarCollapsed ? 'Perfil' : ''">
                    <i data-lucide="user" class="w-5 h-5 flex-shrink-0"></i>
                    <span x-show="!sidebarCollapsed" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-x-2" x-transition:enter-end="opacity-100 translate-x-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-x-0" x-transition:leave-end="opacity-0 -translate-x-2" class="font-medium whitespace-nowrap">Perfil</span>
                </a>
                <a href="activity-log.php" class="nav-item active" :class="sidebarCollapsed ? 'justify-center px-2' : ''" :title="sidebarCollapsed ? 'Histórico de Atividades' : ''">
                    <i data-lucide="file-text" class="w-5 h-5 flex-shrink-0"></i>
                    <span x-show="!sidebarCollapsed" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-x-2" x-transition:enter-end="opacity-100 translate-x-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-x-0" x-transition:leave-end="opacity-0 -translate-x-2" class="font-medium whitespace-nowrap">Histórico de Atividades</span>
                </a>
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
                    <p class="text-sm text-zinc-500 font-mono mt-0.5">Visualize ações recentes em sua conta</p>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <a href="profile.php" class="btn-ghost flex items-center gap-2">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                    <span>Voltar</span>
                </a>
                
                <button onclick="window.location.href='profile.php'" class="flex items-center gap-3 p-2 hover:bg-white/5 rounded-xl transition-all group">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-orange-400 to-rose-500 flex items-center justify-center text-white font-bold text-sm shadow-lg group-hover:scale-105 transition-transform">
                        <?php echo strtoupper(substr($_SESSION['safenode_username'] ?? 'U', 0, 1)); ?>
                    </div>
                </button>
            </div>
        </header>

        <!-- Scrollable Content -->
        <div class="flex-1 overflow-y-auto p-8">
            <div class="max-w-5xl mx-auto space-y-6">
                <!-- Resumo -->
                <div class="glass-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-white mb-1">Registro de Atividades</h3>
                            <p class="text-sm text-zinc-500"><?php echo number_format($totalActivities); ?> evento(s) registrado(s)</p>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-blue-600/20 border border-blue-500/30 flex items-center justify-center">
                            <i data-lucide="activity" class="w-6 h-6 text-blue-400"></i>
                        </div>
                    </div>
                </div>

                <!-- Timeline de Atividades -->
                <div class="space-y-4">
                    <?php if (empty($activities)): ?>
                        <div class="glass-card p-12 text-center">
                            <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-white/5 border border-white/10 flex items-center justify-center">
                                <i data-lucide="inbox" class="w-8 h-8 text-zinc-500"></i>
                            </div>
                            <p class="text-zinc-400 font-medium">Nenhuma atividade registrada ainda</p>
                        </div>
                    <?php else: ?>
                        <?php 
                        $currentDate = '';
                        foreach ($activities as $activity): 
                            $activityDate = date('d/m/Y', strtotime($activity['created_at']));
                            $activityTime = date('H:i', strtotime($activity['created_at']));
                            $showDateHeader = ($currentDate !== $activityDate);
                            $currentDate = $activityDate;
                            
                            $icon = ActivityLogger::getActionIcon($activity['action']);
                            $colorClass = ActivityLogger::getActionColor($activity['status']);
                            $actionLabel = ActivityLogger::translateAction($activity['action']);
                        ?>
                        
                        <?php if ($showDateHeader): ?>
                            <div class="flex items-center gap-3 mt-6 first:mt-0">
                                <div class="h-px flex-1 bg-white/5"></div>
                                <span class="text-xs font-semibold text-zinc-500 uppercase tracking-wider"><?php echo $activityDate; ?></span>
                                <div class="h-px flex-1 bg-white/5"></div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="glass-card p-5">
                            <div class="flex items-start gap-4">
                                <div class="w-10 h-10 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center flex-shrink-0">
                                    <i data-lucide="<?php echo $icon; ?>" class="w-5 h-5 <?php echo $colorClass; ?>"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start justify-between gap-4 mb-2">
                                        <div>
                                            <h4 class="text-sm font-semibold text-white mb-1">
                                                <?php echo htmlspecialchars($actionLabel); ?>
                                            </h4>
                                            <?php if ($activity['description']): ?>
                                                <p class="text-sm text-zinc-400">
                                                    <?php echo htmlspecialchars($activity['description']); ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                        <span class="text-xs text-zinc-500 whitespace-nowrap">
                                            <?php echo $activityTime; ?>
                                        </span>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-3 text-xs text-zinc-500">
                                        <span class="flex items-center gap-1.5">
                                            <i data-lucide="<?php echo $activity['device_type'] === 'mobile' ? 'smartphone' : ($activity['device_type'] === 'tablet' ? 'tablet' : 'monitor'); ?>" class="w-3.5 h-3.5"></i>
                                            <?php echo htmlspecialchars($activity['browser']); ?>
                                        </span>
                                        <span class="flex items-center gap-1.5">
                                            <i data-lucide="map-pin" class="w-3.5 h-3.5"></i>
                                            <?php echo htmlspecialchars($activity['ip_address']); ?>
                                        </span>
                                        <?php if ($activity['status'] === 'failed'): ?>
                                            <span class="px-2 py-0.5 bg-red-500/20 text-red-400 rounded text-xs font-semibold">
                                                Falhou
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Paginação -->
                <?php if ($totalPages > 1): ?>
                    <div class="flex items-center justify-between gap-4 pt-4">
                        <p class="text-sm text-zinc-500">
                            Página <?php echo $page; ?> de <?php echo $totalPages; ?>
                        </p>
                        <div class="flex gap-2">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>" class="btn-ghost flex items-center gap-2">
                                    <i data-lucide="chevron-left" class="w-4 h-4"></i>
                                    <span>Anterior</span>
                                </a>
                            <?php endif; ?>
                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?php echo $page + 1; ?>" class="btn-primary flex items-center gap-2">
                                    <span>Próxima</span>
                                    <i data-lucide="chevron-right" class="w-4 h-4"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Informações -->
                <div class="glass-card">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-xl bg-blue-600/20 border border-blue-500/30 flex items-center justify-center flex-shrink-0">
                            <i data-lucide="info" class="w-6 h-6 text-blue-400"></i>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-white mb-2">Sobre o Histórico</h4>
                            <p class="text-xs text-zinc-400 mb-3">
                                O histórico de atividades registra todas as ações importantes realizadas em sua conta, incluindo logins, alterações de configurações e muito mais.
                            </p>
                            <ul class="space-y-2 text-xs text-zinc-400">
                                <li class="flex items-start gap-2">
                                    <i data-lucide="check" class="w-3.5 h-3.5 text-blue-400 mt-0.5 flex-shrink-0"></i>
                                    <span>Os registros são mantidos por 90 dias</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <i data-lucide="check" class="w-3.5 h-3.5 text-blue-400 mt-0.5 flex-shrink-0"></i>
                                    <span>Revise regularmente para detectar atividades suspeitas</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        lucide.createIcons();
    </script>
    
    <!-- Security Scripts -->
    <script src="includes/security-scripts.js"></script>
</body>
</html>


