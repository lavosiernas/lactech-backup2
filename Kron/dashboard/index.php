<?php
/**
 * KRON - Dashboard Principal
 */

session_start();

// Verificar se está logado
if (!isset($_SESSION['kron_logged_in']) || $_SESSION['kron_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/KronConnectionManager.php';

$kronUserId = $_SESSION['kron_user_id'] ?? null;
$kronUserName = $_SESSION['kron_user_name'] ?? 'Usuário';
$kronUserEmail = $_SESSION['kron_user_email'] ?? '';

// Conectar ao banco de dados
$pdo = getKronDatabase();

// Buscar foto de perfil do usuário
$kronUserAvatar = null;
if ($kronUserId && $pdo) {
    $stmt = $pdo->prepare("SELECT avatar_url FROM kron_users WHERE id = ?");
    $stmt->execute([$kronUserId]);
    $userData = $stmt->fetch();
    if ($userData && !empty($userData['avatar_url'])) {
        $kronUserAvatar = $userData['avatar_url'];
    }
}

// Buscar conexões
$connectionManager = new KronConnectionManager();
$connections = $connectionManager->getUserConnections($kronUserId);

// Estatísticas
$stats = [
    'total_connections' => count($connections),
    'active_connections' => count(array_filter($connections, fn($c) => $c['is_active'] == 1)),
    'safenode_connected' => false,
    'lactech_connected' => false
];

foreach ($connections as $conn) {
    if ($conn['is_active'] == 1) {
        if ($conn['system_name'] === 'safenode') $stats['safenode_connected'] = true;
        if ($conn['system_name'] === 'lactech') $stats['lactech_connected'] = true;
    }
}

// Buscar dados adicionais para estatísticas
$safenodeConn = null;
$lactechConn = null;
if ($stats['safenode_connected']) {
    $safenodeConn = array_filter($connections, fn($c) => $c['system_name'] === 'safenode' && $c['is_active'] == 1);
    $safenodeConn = reset($safenodeConn);
}
if ($stats['lactech_connected']) {
    $lactechConn = array_filter($connections, fn($c) => $c['system_name'] === 'lactech' && $c['is_active'] == 1);
    $lactechConn = reset($lactechConn);
}

// Calcular tempo desde última conexão
$lastConnectionTime = null;
if ($stats['active_connections'] > 0) {
    $lastConn = array_filter($connections, fn($c) => $c['is_active'] == 1);
    if ($lastConn) {
        $lastConn = reset($lastConn);
        $lastConnectionTime = strtotime($lastConn['connected_at']);
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR" class="dark h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - KRON Ecosystem</title>
    <link rel="icon" type="image/png" href="../asset/kron.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        dark: {
                            950: '#030303',
                            900: '#050505',
                            850: '#080808',
                            800: '#0a0a0a',
                            700: '#0f0f0f',
                        }
                    }
                }
            }
        }
    </script>
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            background: #030303;
            color: #a1a1aa;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { 
            background: rgba(255,255,255,0.1); 
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.15); }
        
        .glass-card {
            background: rgba(10, 10, 10, 0.7);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .nav-item {
            transition: all 0.2s ease;
        }
        
        .nav-item:hover {
            background: rgba(255, 255, 255, 0.05);
        }
        
        .nav-item.active {
            background: rgba(255, 255, 255, 0.08);
            border-left: 3px solid #ffffff;
        }
        
        .stat-card {
            background: linear-gradient(135deg, rgba(10, 10, 10, 0.9) 0%, rgba(15, 15, 15, 0.7) 100%);
            border: 1px solid rgba(255, 255, 255, 0.05);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .stat-card:hover::before {
            opacity: 1;
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
            border-color: rgba(255, 255, 255, 0.1);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
        }
        
        .system-card {
            background: linear-gradient(135deg, rgba(10, 10, 10, 0.95) 0%, rgba(15, 15, 15, 0.8) 100%);
            border: 1px solid rgba(255, 255, 255, 0.05);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .system-card:hover {
            transform: translateY(-4px);
            border-color: rgba(255, 255, 255, 0.15);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #ffffff 0%, #f5f5f5 100%);
            color: #000000;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        .btn-primary::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(0, 0, 0, 0.1);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        
        .btn-primary:hover::before {
            width: 300px;
            height: 300px;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(255, 255, 255, 0.2);
        }
        
        .btn-secondary {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }
        
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.2);
        }
        
        .btn-connect {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0.05) 100%);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }
        
        .btn-connect:hover {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.15) 0%, rgba(255, 255, 255, 0.1) 100%);
            border-color: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
        }
        
        @keyframes fade-in {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes slide-in {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        .animate-fade-in {
            animation: fade-in 0.5s ease-out;
        }
        
        .animate-slide-in {
            animation: slide-in 0.5s ease-out;
        }
        
        .chart-container {
            position: relative;
            height: 80px;
        }
        
        .system-option {
            transition: all 0.2s ease;
        }
        
        .system-option:hover {
            background: rgba(255, 255, 255, 0.05);
            transform: translateX(4px);
        }
        
        .pulse-dot {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        
        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: .5;
            }
        }
        
        .gradient-border {
            position: relative;
        }
        
        .gradient-border::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: inherit;
            padding: 1px;
            background: linear-gradient(135deg, rgba(255,255,255,0.1), rgba(255,255,255,0.05));
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
        }
    </style>
</head>
<body class="min-h-screen bg-dark-950 flex">
    <!-- Mobile Menu Overlay -->
    <div id="mobile-menu-overlay" class="hidden fixed inset-0 bg-black/80 backdrop-blur-sm z-40 lg:hidden"></div>
    
    <!-- Sidebar -->
    <aside id="sidebar" class="fixed lg:sticky top-0 left-0 w-64 h-screen glass-card border-r border-white/5 flex flex-col z-50 transform -translate-x-full lg:translate-x-0 transition-transform duration-300">
        <!-- Logo -->
        <div class="p-6 border-b border-white/5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-white/20 to-white/10 border border-white/10 flex items-center justify-center">
                    <img src="../asset/kron.png" alt="KRON" class="w-6 h-6 brightness-0 invert">
                </div>
                <div>
                    <h1 class="text-lg font-bold text-white">KRON</h1>
                    <p class="text-xs text-zinc-500">Ecosystem</p>
                </div>
            </div>
        </div>
        
        <!-- Navigation -->
        <nav class="flex-1 p-4 space-y-1 overflow-y-auto">
            <a href="#" class="nav-item active flex items-center gap-3 px-4 py-3 rounded-lg text-white">
                <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
                <span class="font-medium">Dashboard</span>
            </a>
            <a href="profile.php" class="nav-item flex items-center gap-3 px-4 py-3 rounded-lg text-zinc-400 hover:text-white">
                <i data-lucide="user" class="w-5 h-5"></i>
                <span class="font-medium">Perfil</span>
            </a>
            <a href="#" class="nav-item flex items-center gap-3 px-4 py-3 rounded-lg text-zinc-400 hover:text-white">
                <i data-lucide="link" class="w-5 h-5"></i>
                <span class="font-medium">Conexões</span>
            </a>
            <a href="#" class="nav-item flex items-center gap-3 px-4 py-3 rounded-lg text-zinc-400 hover:text-white">
                <i data-lucide="server" class="w-5 h-5"></i>
                <span class="font-medium">Sistemas</span>
            </a>
            <a href="#" class="nav-item flex items-center gap-3 px-4 py-3 rounded-lg text-zinc-400 hover:text-white">
                <i data-lucide="activity" class="w-5 h-5"></i>
                <span class="font-medium">Analytics</span>
            </a>
            <a href="#" class="nav-item flex items-center gap-3 px-4 py-3 rounded-lg text-zinc-400 hover:text-white">
                <i data-lucide="bell" class="w-5 h-5"></i>
                <span class="font-medium">Notificações</span>
                <span class="ml-auto px-2 py-0.5 bg-white/10 text-white text-xs rounded-full">2</span>
            </a>
            <a href="#" class="nav-item flex items-center gap-3 px-4 py-3 rounded-lg text-zinc-400 hover:text-white">
                <i data-lucide="settings" class="w-5 h-5"></i>
                <span class="font-medium">Configurações</span>
            </a>
        </nav>
        
        <!-- Systems Summary (Só aparece se houver sistemas conectados) -->
        <?php if ($stats['safenode_connected'] || $stats['lactech_connected']): ?>
            <div class="p-4 border-t border-white/5 space-y-3">
                <p class="text-xs font-semibold text-zinc-500 uppercase tracking-wider px-4">Sistemas Conectados</p>
                <div class="space-y-2">
                    <?php if ($stats['safenode_connected']): ?>
                        <div class="px-4 py-2 rounded-lg bg-white/5 border border-white/5">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-white font-medium">SafeNode</span>
                                <span class="w-2 h-2 rounded-full bg-emerald-400 pulse-dot"></span>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($stats['lactech_connected']): ?>
                        <div class="px-4 py-2 rounded-lg bg-white/5 border border-white/5">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-white font-medium">LacTech</span>
                                <span class="w-2 h-2 rounded-full bg-emerald-400 pulse-dot"></span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Header -->
        <header class="glass-card border-b border-white/5 px-4 md:px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <!-- Mobile Menu Button -->
                    <button id="mobile-menu-btn" class="lg:hidden w-10 h-10 rounded-lg bg-white/5 hover:bg-white/10 border border-white/10 flex items-center justify-center text-zinc-400 hover:text-white transition">
                        <i data-lucide="menu" class="w-5 h-5"></i>
                    </button>
                    <div>
                        <h2 class="text-lg md:text-xl font-bold text-white">Dashboard</h2>
                        <p class="text-xs text-zinc-500 mt-1 hidden md:block">Visão geral do ecossistema KRON</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 md:gap-4">
                    <button class="w-9 h-9 md:w-10 md:h-10 rounded-lg bg-white/5 hover:bg-white/10 border border-white/10 flex items-center justify-center text-zinc-400 hover:text-white transition">
                        <i data-lucide="search" class="w-4 h-4 md:w-5 md:h-5"></i>
                    </button>
                    <button class="w-9 h-9 md:w-10 md:h-10 rounded-lg bg-white/5 hover:bg-white/10 border border-white/10 flex items-center justify-center text-zinc-400 hover:text-white transition relative">
                        <i data-lucide="bell" class="w-4 h-4 md:w-5 md:h-5"></i>
                        <span class="absolute top-1.5 right-1.5 md:top-2 md:right-2 w-2 h-2 bg-white rounded-full"></span>
                    </button>
                    <a href="profile.php" class="w-9 h-9 md:w-10 md:h-10 rounded-lg bg-white/5 hover:bg-white/10 border border-white/10 flex items-center justify-center text-white transition relative group overflow-hidden">
                        <?php if ($kronUserAvatar): ?>
                            <img src="<?= htmlspecialchars($kronUserAvatar) ?>" alt="<?= htmlspecialchars($kronUserName) ?>" class="w-full h-full object-cover rounded-lg" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <div class="w-full h-full rounded-lg bg-gradient-to-br from-white/20 to-white/10 flex items-center justify-center text-white font-semibold text-xs md:text-sm hidden">
                                <?= strtoupper(substr($kronUserName, 0, 1)) ?>
                            </div>
                        <?php else: ?>
                            <div class="w-7 h-7 md:w-8 md:h-8 rounded-full bg-gradient-to-br from-white/20 to-white/10 flex items-center justify-center text-white font-semibold text-xs md:text-sm">
                                <?= strtoupper(substr($kronUserName, 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                        <span class="absolute bottom-full mb-2 px-2 py-1 bg-zinc-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap hidden md:block">
                            Perfil
                        </span>
                    </a>
                </div>
            </div>
        </header>

        <!-- Content -->
        <main class="flex-1 overflow-y-auto p-4 md:p-6">
            <!-- Welcome Section -->
            <div class="mb-6 md:mb-8 animate-fade-in">
                <h1 class="text-2xl md:text-3xl font-bold text-white mb-2">Bem-vindo, <?= htmlspecialchars(explode(' ', $kronUserName)[0]) ?></h1>
                <p class="text-sm md:text-base text-zinc-400">Gerencie seu ecossistema de sistemas integrados</p>
            </div>

            <!-- Estatísticas Principais -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6 mb-6 md:mb-8">
                <div class="stat-card rounded-2xl p-4 md:p-6 relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-blue-500/5 rounded-full blur-3xl"></div>
                    <div class="relative z-10">
                        <div class="flex items-center justify-between mb-3 md:mb-4">
                            <div class="w-10 h-10 md:w-12 md:h-12 rounded-xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center">
                                <i data-lucide="link" class="w-5 h-5 md:w-6 md:h-6 text-blue-400"></i>
                            </div>
                            <span class="text-xs font-semibold text-zinc-500 uppercase">Conexões Ativas</span>
                        </div>
                        <div class="text-3xl md:text-4xl font-bold text-white mb-2"><?= $stats['active_connections'] ?></div>
                        <div class="flex items-center gap-2 mb-4">
                            <span class="text-sm text-zinc-500">de <?= $stats['total_connections'] ?> total</span>
                            <?php if ($stats['active_connections'] > 0): ?>
                                <span class="px-2 py-0.5 bg-emerald-500/10 text-emerald-400 text-xs rounded-full border border-emerald-500/20">Ativo</span>
                            <?php endif; ?>
                        </div>
                        <div class="chart-container">
                            <canvas id="connectionsChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="stat-card rounded-2xl p-4 md:p-6 relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-emerald-500/5 rounded-full blur-3xl"></div>
                    <div class="relative z-10">
                        <div class="flex items-center justify-between mb-3 md:mb-4">
                            <div class="w-10 h-10 md:w-12 md:h-12 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center">
                                <i data-lucide="network" class="w-5 h-5 md:w-6 md:h-6 text-emerald-400"></i>
                            </div>
                            <span class="text-xs font-semibold text-zinc-500 uppercase">Sistemas</span>
                        </div>
                        <div class="text-3xl md:text-4xl font-bold text-white mb-2">2</div>
                        <div class="flex items-center gap-2 mb-4">
                            <span class="text-sm text-emerald-400"><?= ($stats['safenode_connected'] ? 1 : 0) + ($stats['lactech_connected'] ? 1 : 0) ?> conectado(s)</span>
                        </div>
                        <div class="flex gap-2">
                            <div class="flex-1 h-2 rounded-full bg-white/5 overflow-hidden">
                                <div class="h-full bg-emerald-500 rounded-full transition-all duration-500" style="width: <?= (($stats['safenode_connected'] ? 1 : 0) + ($stats['lactech_connected'] ? 1 : 0)) * 50 ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="stat-card rounded-2xl p-4 md:p-6 relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-amber-500/5 rounded-full blur-3xl"></div>
                    <div class="relative z-10">
                        <div class="flex items-center justify-between mb-3 md:mb-4">
                            <div class="w-10 h-10 md:w-12 md:h-12 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center">
                                <i data-lucide="activity" class="w-5 h-5 md:w-6 md:h-6 text-amber-400"></i>
                            </div>
                            <span class="text-xs font-semibold text-zinc-500 uppercase">Status</span>
                        </div>
                        <div class="text-2xl md:text-4xl font-bold text-white mb-2"><?= $stats['active_connections'] > 0 ? 'Online' : 'Offline' ?></div>
                        <div class="flex items-center gap-2 mb-4">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 pulse-dot"></span>
                            <span class="text-sm text-zinc-500">Sistema operacional</span>
                        </div>
                        <div class="flex items-center gap-4 text-xs">
                            <div>
                                <span class="text-zinc-500">Uptime</span>
                                <p class="text-white font-semibold">99.9%</p>
                            </div>
                            <div>
                                <span class="text-zinc-500">Latência</span>
                                <p class="text-white font-semibold">< 50ms</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sistemas Conectados (Só aparece se houver conexões) -->
            <?php if ($stats['safenode_connected'] || $stats['lactech_connected']): ?>
                <div class="mb-8 animate-slide-in">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-base md:text-lg font-bold text-white">Sistemas Conectados</h3>
                            <p class="text-xs text-zinc-500 mt-1 hidden md:block">Gerencie suas conexões ativas</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-6">
                        <?php if ($stats['safenode_connected'] && $safenodeConn): ?>
                            <!-- SafeNode Connected -->
                            <div class="system-card rounded-2xl p-4 md:p-6 relative overflow-hidden">
                                <div class="absolute top-0 right-0 w-40 h-40 bg-blue-500/5 rounded-full blur-3xl"></div>
                                <div class="relative z-10">
                                    <div class="flex items-start justify-between mb-6">
                                        <div class="flex items-center gap-4">
                                            <div class="w-12 h-12 md:w-16 md:h-16 rounded-2xl bg-gradient-to-br from-blue-500/20 to-blue-600/20 border border-blue-500/30 flex items-center justify-center">
                                                <i data-lucide="shield" class="w-8 h-8 text-blue-400"></i>
                                            </div>
                                            <div>
                                                <h4 class="text-lg md:text-xl font-bold text-white mb-1">SafeNode</h4>
                                                <p class="text-xs text-zinc-500">Segurança e Proteção</p>
                                            </div>
                                        </div>
                                        <span class="px-3 py-1 bg-emerald-500/10 text-emerald-400 text-xs font-semibold rounded-full border border-emerald-500/20 flex items-center gap-1.5">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 pulse-dot"></span>
                                            Conectado
                                        </span>
                                    </div>

                                    <div class="space-y-4 mb-6">
                                        <div class="p-4 rounded-xl bg-white/5 border border-white/5">
                                            <div class="flex items-center justify-between mb-3">
                                                <span class="text-xs text-zinc-500">Status da Conexão</span>
                                                <span class="text-xs text-emerald-400 font-semibold">Ativa</span>
                                            </div>
                                            <div class="flex items-center justify-between mb-2">
                                                <span class="text-xs text-zinc-500">Conectado em</span>
                                                <span class="text-xs text-white font-medium"><?= date('d/m/Y H:i', strtotime($safenodeConn['connected_at'])) ?></span>
                                            </div>
                                            <div class="flex items-center justify-between">
                                                <span class="text-xs text-zinc-500">Email vinculado</span>
                                                <span class="text-xs text-white font-medium truncate ml-2"><?= htmlspecialchars($safenodeConn['system_user_email']) ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <button onclick="disconnectSystem('safenode')" class="w-full py-3 px-4 rounded-lg btn-secondary text-white text-sm font-semibold">
                                        Desconectar SafeNode
                                    </button>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($stats['lactech_connected'] && $lactechConn): ?>
                            <!-- LacTech Connected -->
                            <div class="system-card rounded-2xl p-4 md:p-6 relative overflow-hidden">
                                <div class="absolute top-0 right-0 w-40 h-40 bg-emerald-500/5 rounded-full blur-3xl"></div>
                                <div class="relative z-10">
                                    <div class="flex items-start justify-between mb-6">
                                        <div class="flex items-center gap-4">
                                            <div class="w-12 h-12 md:w-16 md:h-16 rounded-2xl bg-gradient-to-br from-emerald-500/20 to-emerald-600/20 border border-emerald-500/30 flex items-center justify-center">
                                                <i data-lucide="leaf" class="w-8 h-8 text-emerald-400"></i>
                                            </div>
                                            <div>
                                                <h4 class="text-lg md:text-xl font-bold text-white mb-1">LacTech</h4>
                                                <p class="text-xs text-zinc-500">Gestão Agropecuária</p>
                                            </div>
                                        </div>
                                        <span class="px-3 py-1 bg-emerald-500/10 text-emerald-400 text-xs font-semibold rounded-full border border-emerald-500/20 flex items-center gap-1.5">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 pulse-dot"></span>
                                            Conectado
                                        </span>
                                    </div>

                                    <div class="space-y-4 mb-6">
                                        <div class="p-4 rounded-xl bg-white/5 border border-white/5">
                                            <div class="flex items-center justify-between mb-3">
                                                <span class="text-xs text-zinc-500">Status da Conexão</span>
                                                <span class="text-xs text-emerald-400 font-semibold">Ativa</span>
                                            </div>
                                            <div class="flex items-center justify-between mb-2">
                                                <span class="text-xs text-zinc-500">Conectado em</span>
                                                <span class="text-xs text-white font-medium"><?= date('d/m/Y H:i', strtotime($lactechConn['connected_at'])) ?></span>
                                            </div>
                                            <div class="flex items-center justify-between">
                                                <span class="text-xs text-zinc-500">Email vinculado</span>
                                                <span class="text-xs text-white font-medium truncate ml-2"><?= htmlspecialchars($lactechConn['system_user_email']) ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <button onclick="disconnectSystem('lactech')" class="w-full py-3 px-4 rounded-lg btn-secondary text-white text-sm font-semibold">
                                        Desconectar LacTech
                                    </button>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Conectar Novo Sistema -->
            <div class="mb-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-bold text-white">Conectar Sistema</h3>
                        <p class="text-xs text-zinc-500 mt-1">Adicione novos sistemas ao seu ecossistema</p>
                    </div>
                </div>

                <div class="system-card rounded-2xl p-6 md:p-8 relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-40 h-40 bg-white/5 rounded-full blur-3xl"></div>
                    <div class="absolute inset-0 bg-gradient-to-br from-white/5 via-transparent to-transparent opacity-50"></div>
                    <div class="relative z-10">
                        <div id="connectCard" class="text-center py-8">
                            <div class="w-16 h-16 md:w-20 md:h-20 rounded-2xl bg-gradient-to-br from-white/10 to-white/5 border border-white/10 flex items-center justify-center mx-auto mb-4 md:mb-6 shadow-lg">
                                <i data-lucide="plus" class="w-8 h-8 md:w-10 md:h-10 text-white"></i>
                            </div>
                            <h4 class="text-lg md:text-xl font-bold text-white mb-2">Conectar Novo Sistema</h4>
                            <p class="text-xs md:text-sm text-zinc-400 mb-6 md:mb-8 max-w-md mx-auto">Conecte seus sistemas ao ecossistema KRON e tenha tudo em um só lugar. Gerencie todas as suas plataformas de forma unificada.</p>
                            <button onclick="showSystemOptions()" class="px-6 md:px-8 py-2.5 md:py-3 rounded-lg btn-connect text-white text-sm font-semibold inline-flex items-center gap-2">
                                <i data-lucide="link" class="w-5 h-5"></i>
                                Conectar Sistema
                            </button>
                        </div>

                        <div id="systemOptions" class="hidden">
                            <div class="space-y-4">
                                <div class="flex items-center justify-between mb-6">
                                    <h4 class="text-lg font-bold text-white">Selecione o Sistema</h4>
                                    <button onclick="hideSystemOptions()" class="w-8 h-8 rounded-lg bg-white/5 hover:bg-white/10 flex items-center justify-center text-zinc-400 hover:text-white transition">
                                        <i data-lucide="x" class="w-5 h-5"></i>
                                    </button>
                                </div>

                                <!-- SafeNode Option -->
                                <?php if (!$stats['safenode_connected']): ?>
                                    <div onclick="connectSystem('safenode')" class="system-option p-5 rounded-xl bg-white/5 border border-white/10 cursor-pointer">
                                        <div class="flex items-center gap-4">
                                            <div class="w-12 h-12 md:w-14 md:h-14 rounded-xl bg-gradient-to-br from-blue-500/20 to-blue-600/20 border border-blue-500/30 flex items-center justify-center">
                                                <i data-lucide="shield" class="w-7 h-7 text-blue-400"></i>
                                            </div>
                                            <div class="flex-1">
                                                <h5 class="text-base font-bold text-white mb-1">SafeNode</h5>
                                                <p class="text-xs text-zinc-400">Sistema de segurança e proteção de sites</p>
                                            </div>
                                            <i data-lucide="chevron-right" class="w-5 h-5 text-zinc-400"></i>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- LacTech Option -->
                                <?php if (!$stats['lactech_connected']): ?>
                                    <div onclick="connectSystem('lactech')" class="system-option p-5 rounded-xl bg-white/5 border border-white/10 cursor-pointer">
                                        <div class="flex items-center gap-4">
                                            <div class="w-12 h-12 md:w-14 md:h-14 rounded-xl bg-gradient-to-br from-emerald-500/20 to-emerald-600/20 border border-emerald-500/30 flex items-center justify-center">
                                                <i data-lucide="leaf" class="w-7 h-7 text-emerald-400"></i>
                                            </div>
                                            <div class="flex-1">
                                                <h5 class="text-base font-bold text-white mb-1">LacTech</h5>
                                                <p class="text-xs text-zinc-400">Sistema de gestão agropecuária</p>
                                            </div>
                                            <i data-lucide="chevron-right" class="w-5 h-5 text-zinc-400"></i>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <?php if ($stats['safenode_connected'] && $stats['lactech_connected']): ?>
                                    <div class="text-center py-8">
                                        <div class="w-16 h-16 rounded-full bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center mx-auto mb-4 animate-fade-in">
                                            <i data-lucide="check-circle" class="w-8 h-8 text-emerald-400"></i>
                                        </div>
                                        <p class="text-white font-semibold mb-2">Todos os sistemas conectados!</p>
                                        <p class="text-sm text-zinc-400">Você já possui todos os sistemas disponíveis conectados ao seu ecossistema.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal de Conexão -->
    <div id="connectionModal" class="hidden fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="glass-card rounded-2xl max-w-md w-full p-4 md:p-6 border border-white/10 animate-fade-in max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-white" id="modalTitle">Conectar Sistema</h3>
                <button onclick="closeModal()" class="w-8 h-8 rounded-lg bg-white/5 hover:bg-white/10 flex items-center justify-center text-zinc-400 hover:text-white transition">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <div id="modalContent">
                <!-- Conteúdo será preenchido via JavaScript -->
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();

        // Mobile Menu Toggle
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const sidebar = document.getElementById('sidebar');
        const mobileMenuOverlay = document.getElementById('mobile-menu-overlay');
        
        function toggleMobileMenu() {
            sidebar.classList.toggle('-translate-x-full');
            mobileMenuOverlay.classList.toggle('hidden');
            document.body.style.overflow = sidebar.classList.contains('-translate-x-full') ? '' : 'hidden';
        }
        
        mobileMenuBtn?.addEventListener('click', toggleMobileMenu);
        mobileMenuOverlay?.addEventListener('click', toggleMobileMenu);
        
        // Fechar menu ao clicar em link
        sidebar?.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth < 1024) {
                    toggleMobileMenu();
                }
            });
        });

        // Gráfico de conexões
        const ctx = document.getElementById('connectionsChart');
        if (ctx) {
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'],
                    datasets: [{
                        label: 'Conexões',
                        data: [0, 0, 0, <?= $stats['active_connections'] ?>, <?= $stats['active_connections'] ?>, <?= $stats['active_connections'] ?>, <?= $stats['active_connections'] ?>],
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 0,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        x: {
                            display: false
                        },
                        y: {
                            display: false
                        }
                    }
                }
            });
        }

        function showSystemOptions() {
            document.getElementById('connectCard').classList.add('hidden');
            document.getElementById('systemOptions').classList.remove('hidden');
            document.getElementById('systemOptions').classList.add('animate-slide-in');
        }

        function hideSystemOptions() {
            document.getElementById('connectCard').classList.remove('hidden');
            document.getElementById('systemOptions').classList.add('hidden');
        }

        function connectSystem(systemName) {
            const systemNames = {
                'safenode': 'SafeNode',
                'lactech': 'LacTech'
            };

            document.getElementById('modalTitle').textContent = `Conectar ${systemNames[systemName]}`;
            document.getElementById('modalContent').innerHTML = '<div class="text-center py-8"><div class="inline-block w-8 h-8 border-4 border-white/20 border-t-white rounded-full animate-spin"></div><p class="text-zinc-400 mt-4">Carregando...</p></div>';
            document.getElementById('connectionModal').classList.remove('hidden');

            // Verificar se há token pendente
            fetch(`../api/get-pending-token.php?system_name=${systemName}`)
            .then(response => response.json())
            .then(pendingData => {
                if (pendingData.success && pendingData.has_pending) {
                    // Usar token pendente existente
                    loadTokenData(systemName, pendingData.token, pendingData.expires_in);
                } else {
                    // Gerar novo token
                    generateNewToken(systemName);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // Em caso de erro, tentar gerar novo token
                generateNewToken(systemName);
            });
        }
        
        function generateNewToken(systemName) {
            const systemNames = {
                'safenode': 'SafeNode',
                'lactech': 'LacTech'
            };
            
            // Gerar apenas o token (sem QR Code)
            fetch('../api/generate-connection-token.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `system_name=${systemName}&generate_qr=0`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const expiresIn = Math.floor(data.expires_in / 60);
                    let currentTab = 'token'; // Começar com Token
                    let tokenData = data.token;
                    let expiresInSeconds = data.expires_in;
                    let qrCodeUrl = null;
                    let isLoadingQR = false;
                    
                    // Armazenar token pendente globalmente
                    pendingToken = tokenData;
                    pendingSystemName = systemName;
                    pendingExpiresIn = expiresInSeconds;
                    
                    function renderContent() {
                        if (currentTab === 'token') {
                            return `
                                <div class="space-y-6">
                                    <!-- Tabs -->
                                    <div class="flex gap-2 p-1 rounded-xl bg-white/5 border border-white/10">
                                        <button onclick="switchTab('qr')" class="flex-1 py-2 px-4 rounded-lg text-zinc-400 hover:text-white text-sm font-medium transition">
                                            QR Code
                                        </button>
                                        <button onclick="switchTab('token')" class="flex-1 py-2 px-4 rounded-lg bg-white/10 text-white text-sm font-semibold transition">
                                            Token
                                        </button>
                                    </div>
                                    
                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-sm font-medium text-zinc-300 mb-2">Token de Conexão</label>
                                            <div class="flex gap-2">
                                                <input type="text" id="manualToken" value="${tokenData}" readonly 
                                                       class="flex-1 px-4 py-3 rounded-lg bg-white/5 border border-white/10 text-white text-xs font-mono">
                                                <button onclick="copyToken()" class="px-4 py-3 rounded-lg bg-white/5 hover:bg-white/10 border border-white/10 text-white text-sm font-semibold transition">
                                                    Copiar
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <p class="text-xs text-zinc-500 mt-2">Expira em: <span id="countdown" class="text-white font-semibold">${expiresIn}:00</span></p>
                                    </div>

                                    <div class="bg-blue-500/10 border border-blue-500/20 rounded-xl p-4">
                                        <p class="text-xs text-blue-400 font-semibold mb-2">Instruções:</p>
                                        <ol class="text-xs text-blue-300 space-y-1 list-decimal list-inside">
                                            <li>Acesse o ${systemNames[systemName]}</li>
                                            <li>Vá em Perfil → Conexão KRON</li>
                                            <li>Cole este token no campo de conexão</li>
                                            <li>Clique em "Conectar"</li>
                                        </ol>
                                    </div>
                                    
                                    <div class="border-t border-white/10 pt-4">
                                        <button onclick="cancelTokenFromModal()" class="w-full px-4 py-2 rounded-lg bg-red-500/10 hover:bg-red-500/20 border border-red-500/20 text-red-400 text-sm font-medium transition">
                                            <i data-lucide="x-circle" class="w-4 h-4 inline mr-2"></i>
                                            Cancelar Token
                                        </button>
                                    </div>
                                </div>
                            `;
                        } else {
                            // Aba QR Code
                            if (isLoadingQR) {
                                return `
                                    <div class="space-y-6">
                                        <!-- Tabs -->
                                        <div class="flex gap-2 p-1 rounded-xl bg-white/5 border border-white/10">
                                            <button onclick="switchTab('qr')" class="flex-1 py-2 px-4 rounded-lg bg-white/10 text-white text-sm font-semibold transition">
                                                QR Code
                                            </button>
                                            <button onclick="switchTab('token')" class="flex-1 py-2 px-4 rounded-lg text-zinc-400 hover:text-white text-sm font-medium transition">
                                                Token
                                            </button>
                                        </div>
                                        
                                        <div class="text-center py-12">
                                            <div class="inline-block w-12 h-12 border-4 border-white/20 border-t-white rounded-full animate-spin mb-4"></div>
                                            <p class="text-zinc-400">Gerando QR Code...</p>
                                        </div>
                                    </div>
                                `;
                            }
                            
                            if (!qrCodeUrl) {
                                return `
                                    <div class="space-y-6">
                                        <!-- Tabs -->
                                        <div class="flex gap-2 p-1 rounded-xl bg-white/5 border border-white/10">
                                            <button onclick="switchTab('qr')" class="flex-1 py-2 px-4 rounded-lg bg-white/10 text-white text-sm font-semibold transition">
                                                QR Code
                                            </button>
                                            <button onclick="switchTab('token')" class="flex-1 py-2 px-4 rounded-lg text-zinc-400 hover:text-white text-sm font-medium transition">
                                                Token
                                            </button>
                                        </div>
                                        
                                        <div class="text-center py-8">
                                            <p class="text-zinc-400">Clique em "Gerar QR Code" para continuar</p>
                                        </div>
                                    </div>
                                `;
                            }
                            
                            return `
                                <div class="space-y-6">
                                    <!-- Tabs -->
                                    <div class="flex gap-2 p-1 rounded-xl bg-white/5 border border-white/10">
                                        <button onclick="switchTab('qr')" class="flex-1 py-2 px-4 rounded-lg bg-white/10 text-white text-sm font-semibold transition">
                                            QR Code
                                        </button>
                                        <button onclick="switchTab('token')" class="flex-1 py-2 px-4 rounded-lg text-zinc-400 hover:text-white text-sm font-medium transition">
                                            Token
                                        </button>
                                    </div>
                                    
                                    <div class="text-center">
                                        <div class="inline-block p-4 md:p-8 rounded-2xl bg-white border-2 border-white/30 shadow-2xl">
                                            <img src="${qrCodeUrl}" alt="QR Code" class="w-64 h-64 md:w-80 md:h-80 rounded-lg">
                                        </div>
                                        <p class="text-sm text-zinc-400 mt-6 font-medium">Escaneie este código no ${systemNames[systemName]}</p>
                                        <p class="text-xs text-zinc-500 mt-2">Expira em: <span id="countdown" class="text-white font-semibold">${expiresIn}:00</span></p>
                                    </div>

                                    <div class="bg-blue-500/10 border border-blue-500/20 rounded-xl p-4">
                                        <p class="text-xs text-blue-400 font-semibold mb-2">Instruções:</p>
                                        <ol class="text-xs text-blue-300 space-y-1 list-decimal list-inside">
                                            <li>Acesse o ${systemNames[systemName]}</li>
                                            <li>Vá em Perfil → Conexão KRON</li>
                                            <li>Clique em "Escanear QR Code"</li>
                                            <li>Aponte a câmera para este código</li>
                                        </ol>
                                    </div>
                                </div>
                            `;
                        }
                    }
                    
                    // Função para gerar QR Code
                    function generateQRCode() {
                        isLoadingQR = true;
                        document.getElementById('modalContent').innerHTML = renderContent();
                        
                        fetch('../api/generate-connection-token.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `system_name=${systemName}&generate_qr=1&token=${tokenData}`
                        })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                            }
                            return response.json();
                        })
                        .then(qrData => {
                            isLoadingQR = false;
                            if (qrData.success && qrData.qr_code_url) {
                                qrCodeUrl = qrData.qr_code_url;
                                document.getElementById('modalContent').innerHTML = renderContent();
                                lucide.createIcons();
                                if (window.startCountdown) {
                                    window.startCountdown(expiresInSeconds);
                                }
                            } else {
                                const errorMsg = qrData.error || 'Erro ao gerar QR Code';
                                document.getElementById('modalContent').innerHTML = `
                                    <div class="space-y-6">
                                        <div class="flex gap-2 p-1 rounded-xl bg-white/5 border border-white/10">
                                            <button onclick="switchTab('qr')" class="flex-1 py-2 px-4 rounded-lg bg-white/10 text-white text-sm font-semibold transition">
                                                QR Code
                                            </button>
                                            <button onclick="switchTab('token')" class="flex-1 py-2 px-4 rounded-lg text-zinc-400 hover:text-white text-sm font-medium transition">
                                                Token
                                            </button>
                                        </div>
                                        <div class="text-center py-8">
                                            <p class="text-red-400 mb-2">${errorMsg}</p>
                                            <button onclick="generateQRCode()" class="mt-4 px-6 py-2 rounded-lg btn-primary text-sm font-semibold">
                                                Tentar Novamente
                                            </button>
                                        </div>
                                    </div>
                                `;
                                lucide.createIcons();
                            }
                        })
                        .catch(error => {
                            isLoadingQR = false;
                            console.error('Erro ao gerar QR Code:', error);
                            document.getElementById('modalContent').innerHTML = `
                                <div class="space-y-6">
                                    <div class="flex gap-2 p-1 rounded-xl bg-white/5 border border-white/10">
                                        <button onclick="switchTab('qr')" class="flex-1 py-2 px-4 rounded-lg bg-white/10 text-white text-sm font-semibold transition">
                                            QR Code
                                        </button>
                                        <button onclick="switchTab('token')" class="flex-1 py-2 px-4 rounded-lg text-zinc-400 hover:text-white text-sm font-medium transition">
                                            Token
                                        </button>
                                    </div>
                                    <div class="text-center py-8">
                                        <p class="text-red-400 mb-2">Erro ao gerar QR Code: ${error.message || 'Erro desconhecido'}</p>
                                        <button onclick="generateQRCode()" class="mt-4 px-6 py-2 rounded-lg btn-primary text-sm font-semibold">
                                            Tentar Novamente
                                        </button>
                                    </div>
                                </div>
                            `;
                            lucide.createIcons();
                        });
                    }
                    
                    // Função para trocar de aba
                    window.switchTab = function(tab) {
                        currentTab = tab;
                        
                        // Se mudou para QR Code e ainda não foi gerado, gerar agora
                        if (tab === 'qr' && !qrCodeUrl && !isLoadingQR) {
                            generateQRCode();
                        } else {
                            document.getElementById('modalContent').innerHTML = renderContent();
                            lucide.createIcons();
                            
                            // Reiniciar countdown
                            if (window.startCountdown) {
                                window.startCountdown(expiresInSeconds);
                            }
                        }
                    };
                    
                    // Função global para countdown
                    let countdownInterval = null;
                    function startCountdown(initialTime) {
                        if (countdownInterval) clearInterval(countdownInterval);
                        let timeLeft = initialTime;
                        const update = () => {
                            const countdownEl = document.getElementById('countdown');
                            if (countdownEl) {
                                const minutes = Math.floor(timeLeft / 60);
                                const seconds = timeLeft % 60;
                                countdownEl.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
                                
                                if (timeLeft <= 0) {
                                    clearInterval(countdownInterval);
                                    countdownEl.textContent = 'Expirado';
                                    countdownEl.classList.add('text-red-400');
                                } else {
                                    timeLeft--;
                                }
                            }
                        };
                        update();
                        countdownInterval = setInterval(update, 1000);
                    }
                    
                    window.startCountdown = startCountdown;
                    window.generateQRCode = generateQRCode;
                    
                    // Função para cancelar token do modal
                    window.cancelTokenFromModal = function() {
                        if (confirm('Deseja realmente cancelar este token de conexão?')) {
                            cancelToken();
                        }
                    };
                    
                    document.getElementById('modalContent').innerHTML = renderContent();
                    lucide.createIcons();
                    startCountdown(expiresInSeconds);
                } else {
                    document.getElementById('modalContent').innerHTML = `
                        <div class="text-center py-8">
                            <div class="w-12 h-12 rounded-full bg-red-500/10 border border-red-500/20 flex items-center justify-center mx-auto mb-4">
                                <i data-lucide="alert-circle" class="w-6 h-6 text-red-400"></i>
                            </div>
                            <p class="text-red-400 font-semibold mb-4">${data.error || 'Erro ao gerar token'}</p>
                            <button onclick="closeModal()" class="px-6 py-2 rounded-lg btn-primary text-sm font-semibold">
                                Fechar
                            </button>
                        </div>
                    `;
                    lucide.createIcons();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('modalContent').innerHTML = `
                    <div class="text-center py-8">
                        <div class="w-12 h-12 rounded-full bg-red-500/10 border border-red-500/20 flex items-center justify-center mx-auto mb-4">
                            <i data-lucide="alert-circle" class="w-6 h-6 text-red-400"></i>
                        </div>
                        <p class="text-red-400 font-semibold mb-4">Erro ao gerar token</p>
                        <button onclick="closeModal()" class="px-6 py-2 rounded-lg btn-primary text-sm font-semibold">
                            Fechar
                        </button>
                    </div>
                `;
                lucide.createIcons();
            });
        }

        // Variáveis globais para gerenciar token pendente
        let pendingToken = null;
        let pendingSystemName = null;
        let pendingExpiresIn = null;
        
        function loadTokenData(systemName, tokenData, expiresInSeconds) {
            const systemNames = {
                'safenode': 'SafeNode',
                'lactech': 'LacTech'
            };
            
            if (!tokenData || !expiresInSeconds) {
                document.getElementById('modalContent').innerHTML = `
                    <div class="text-center py-8">
                        <p class="text-red-400 font-semibold mb-4">Erro ao carregar token</p>
                        <button onclick="closeModal()" class="px-6 py-2 rounded-lg btn-primary text-sm font-semibold">
                            Fechar
                        </button>
                    </div>
                `;
                lucide.createIcons();
                return;
            }
            
            const expiresIn = Math.floor(expiresInSeconds / 60);
            let currentTab = 'token';
            let qrCodeUrl = null;
            let isLoadingQR = false;
            
            // Armazenar token pendente globalmente
            pendingToken = tokenData;
            pendingSystemName = systemName;
            pendingExpiresIn = expiresInSeconds;
            
            function renderContent() {
                if (currentTab === 'token') {
                    return `
                        <div class="space-y-6">
                            <div class="flex gap-2 p-1 rounded-xl bg-white/5 border border-white/10">
                                <button onclick="switchTab('qr')" class="flex-1 py-2 px-4 rounded-lg text-zinc-400 hover:text-white text-sm font-medium transition">
                                    QR Code
                                </button>
                                <button onclick="switchTab('token')" class="flex-1 py-2 px-4 rounded-lg bg-white/10 text-white text-sm font-semibold transition">
                                    Token
                                </button>
                            </div>
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-zinc-300 mb-2">Token de Conexão</label>
                                    <div class="flex gap-2">
                                        <input type="text" id="manualToken" value="${tokenData}" readonly 
                                               class="flex-1 px-4 py-3 rounded-lg bg-white/5 border border-white/10 text-white text-xs font-mono">
                                        <button onclick="copyToken()" class="px-4 py-3 rounded-lg bg-white/5 hover:bg-white/10 border border-white/10 text-white text-sm font-semibold transition">
                                            Copiar
                                        </button>
                                    </div>
                                </div>
                                
                                <p class="text-xs text-zinc-500 mt-2">Expira em: <span id="countdown" class="text-white font-semibold">${expiresIn}:00</span></p>
                            </div>

                            <div class="bg-blue-500/10 border border-blue-500/20 rounded-xl p-4">
                                <p class="text-xs text-blue-400 font-semibold mb-2">Instruções:</p>
                                <ol class="text-xs text-blue-300 space-y-1 list-decimal list-inside">
                                    <li>Acesse o ${systemNames[systemName]}</li>
                                    <li>Vá em Perfil → Conexão KRON</li>
                                    <li>Cole este token no campo de conexão</li>
                                    <li>Clique em "Conectar"</li>
                                </ol>
                            </div>
                            
                            <div class="border-t border-white/10 pt-4">
                                <button onclick="cancelTokenFromModal()" class="w-full px-4 py-2 rounded-lg bg-red-500/10 hover:bg-red-500/20 border border-red-500/20 text-red-400 text-sm font-medium transition">
                                    <i data-lucide="x-circle" class="w-4 h-4 inline mr-2"></i>
                                    Cancelar Token
                                </button>
                            </div>
                        </div>
                    `;
                } else {
                    if (isLoadingQR) {
                        return `
                            <div class="space-y-6">
                                <div class="flex gap-2 p-1 rounded-xl bg-white/5 border border-white/10">
                                    <button onclick="switchTab('qr')" class="flex-1 py-2 px-4 rounded-lg bg-white/10 text-white text-sm font-semibold transition">
                                        QR Code
                                    </button>
                                    <button onclick="switchTab('token')" class="flex-1 py-2 px-4 rounded-lg text-zinc-400 hover:text-white text-sm font-medium transition">
                                        Token
                                    </button>
                                </div>
                                <div class="text-center py-12">
                                    <div class="inline-block w-12 h-12 border-4 border-white/20 border-t-white rounded-full animate-spin mb-4"></div>
                                    <p class="text-zinc-400">Gerando QR Code...</p>
                                </div>
                            </div>
                        `;
                    }
                    
                    if (!qrCodeUrl) {
                        return `
                            <div class="space-y-6">
                                <div class="flex gap-2 p-1 rounded-xl bg-white/5 border border-white/10">
                                    <button onclick="switchTab('qr')" class="flex-1 py-2 px-4 rounded-lg bg-white/10 text-white text-sm font-semibold transition">
                                        QR Code
                                    </button>
                                    <button onclick="switchTab('token')" class="flex-1 py-2 px-4 rounded-lg text-zinc-400 hover:text-white text-sm font-medium transition">
                                        Token
                                    </button>
                                </div>
                                <div class="text-center py-8">
                                    <p class="text-zinc-400">Gerando QR Code...</p>
                                </div>
                            </div>
                        `;
                    }
                    
                    return `
                        <div class="space-y-6">
                            <div class="flex gap-2 p-1 rounded-xl bg-white/5 border border-white/10">
                                <button onclick="switchTab('qr')" class="flex-1 py-2 px-4 rounded-lg bg-white/10 text-white text-sm font-semibold transition">
                                    QR Code
                                </button>
                                <button onclick="switchTab('token')" class="flex-1 py-2 px-4 rounded-lg text-zinc-400 hover:text-white text-sm font-medium transition">
                                    Token
                                </button>
                            </div>
                            <div class="text-center">
                                <div class="inline-block p-8 rounded-2xl bg-white border-2 border-white/30 shadow-2xl">
                                    <img src="${qrCodeUrl}" alt="QR Code" class="w-80 h-80 rounded-lg">
                                </div>
                                <p class="text-sm text-zinc-400 mt-6 font-medium">Escaneie este código no ${systemNames[systemName]}</p>
                                <p class="text-xs text-zinc-500 mt-2">Expira em: <span id="countdown" class="text-white font-semibold">${expiresIn}:00</span></p>
                            </div>
                            <div class="bg-blue-500/10 border border-blue-500/20 rounded-xl p-4">
                                <p class="text-xs text-blue-400 font-semibold mb-2">Instruções:</p>
                                <ol class="text-xs text-blue-300 space-y-1 list-decimal list-inside">
                                    <li>Acesse o ${systemNames[systemName]}</li>
                                    <li>Vá em Perfil → Conexão KRON</li>
                                    <li>Clique em "Escanear QR Code"</li>
                                    <li>Aponte a câmera para este código</li>
                                </ol>
                            </div>
                        </div>
                    `;
                }
            }
            
            function generateQRCode() {
                isLoadingQR = true;
                document.getElementById('modalContent').innerHTML = renderContent();
                
                fetch('../api/generate-connection-token.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `system_name=${systemName}&generate_qr=1&token=${tokenData}`
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    return response.json();
                })
                .then(qrData => {
                    isLoadingQR = false;
                    if (qrData.success && qrData.qr_code_url) {
                        qrCodeUrl = qrData.qr_code_url;
                        document.getElementById('modalContent').innerHTML = renderContent();
                        lucide.createIcons();
                        if (window.startCountdown) {
                            window.startCountdown(expiresInSeconds);
                        }
                    } else {
                        const errorMsg = qrData.error || 'Erro ao gerar QR Code';
                        document.getElementById('modalContent').innerHTML = `
                            <div class="space-y-6">
                                <div class="flex gap-2 p-1 rounded-xl bg-white/5 border border-white/10">
                                    <button onclick="switchTab('qr')" class="flex-1 py-2 px-4 rounded-lg bg-white/10 text-white text-sm font-semibold transition">
                                        QR Code
                                    </button>
                                    <button onclick="switchTab('token')" class="flex-1 py-2 px-4 rounded-lg text-zinc-400 hover:text-white text-sm font-medium transition">
                                        Token
                                    </button>
                                </div>
                                <div class="text-center py-8">
                                    <p class="text-red-400 mb-2">${errorMsg}</p>
                                    <button onclick="generateQRCode()" class="mt-4 px-6 py-2 rounded-lg btn-primary text-sm font-semibold">
                                        Tentar Novamente
                                    </button>
                                </div>
                            </div>
                        `;
                        lucide.createIcons();
                    }
                })
                .catch(error => {
                    isLoadingQR = false;
                    console.error('Erro ao gerar QR Code:', error);
                    document.getElementById('modalContent').innerHTML = `
                        <div class="space-y-6">
                            <div class="flex gap-2 p-1 rounded-xl bg-white/5 border border-white/10">
                                <button onclick="switchTab('qr')" class="flex-1 py-2 px-4 rounded-lg bg-white/10 text-white text-sm font-semibold transition">
                                    QR Code
                                </button>
                                <button onclick="switchTab('token')" class="flex-1 py-2 px-4 rounded-lg text-zinc-400 hover:text-white text-sm font-medium transition">
                                    Token
                                </button>
                            </div>
                            <div class="text-center py-8">
                                <p class="text-red-400 mb-2">Erro ao gerar QR Code: ${error.message || 'Erro desconhecido'}</p>
                                <button onclick="generateQRCode()" class="mt-4 px-6 py-2 rounded-lg btn-primary text-sm font-semibold">
                                    Tentar Novamente
                                </button>
                            </div>
                        </div>
                    `;
                    lucide.createIcons();
                });
            }
            
            window.switchTab = function(tab) {
                currentTab = tab;
                if (tab === 'qr' && !qrCodeUrl && !isLoadingQR) {
                    generateQRCode();
                } else {
                    document.getElementById('modalContent').innerHTML = renderContent();
                    lucide.createIcons();
                    if (window.startCountdown) {
                        window.startCountdown(expiresInSeconds);
                    }
                }
            };
            
            let countdownInterval = null;
            function startCountdown(initialTime) {
                if (countdownInterval) clearInterval(countdownInterval);
                let timeLeft = initialTime;
                const update = () => {
                    const countdownEl = document.getElementById('countdown');
                    if (countdownEl) {
                        const minutes = Math.floor(timeLeft / 60);
                        const seconds = timeLeft % 60;
                        countdownEl.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
                        if (timeLeft <= 0) {
                            clearInterval(countdownInterval);
                            countdownEl.textContent = 'Expirado';
                            countdownEl.classList.add('text-red-400');
                        } else {
                            timeLeft--;
                        }
                    }
                };
                update();
                countdownInterval = setInterval(update, 1000);
            }
            
            window.startCountdown = startCountdown;
            window.generateQRCode = generateQRCode;
            window.cancelTokenFromModal = function() {
                if (confirm('Deseja realmente cancelar este token de conexão?')) {
                    cancelToken();
                }
            };
            
            document.getElementById('modalContent').innerHTML = renderContent();
            lucide.createIcons();
            startCountdown(expiresInSeconds);
        }
        
        function closeModal() {
            // Se não há token pendente, fechar normalmente
            if (!pendingToken) {
                document.getElementById('connectionModal').classList.add('hidden');
                return;
            }
            
            // Mostrar confirmação
            const confirmHTML = `
                <div class="space-y-4">
                    <div class="text-center">
                        <div class="w-16 h-16 rounded-full bg-amber-500/10 border border-amber-500/20 flex items-center justify-center mx-auto mb-4">
                            <i data-lucide="alert-circle" class="w-8 h-8 text-amber-400"></i>
                        </div>
                        <h4 class="text-lg font-bold text-white mb-2">Token em Aguardo</h4>
                        <p class="text-sm text-zinc-400 mb-6">Você tem um token de conexão ativo. O que deseja fazer?</p>
                    </div>
                    
                    <div class="space-y-3">
                        <button onclick="keepTokenPending()" class="w-full px-4 py-3 rounded-lg bg-white/5 hover:bg-white/10 border border-white/10 text-white text-sm font-semibold transition">
                            <i data-lucide="clock" class="w-4 h-4 inline mr-2"></i>
                            Deixar em Aguardo
                        </button>
                        <button onclick="cancelToken()" class="w-full px-4 py-3 rounded-lg bg-red-500/10 hover:bg-red-500/20 border border-red-500/20 text-red-400 text-sm font-semibold transition">
                            <i data-lucide="x-circle" class="w-4 h-4 inline mr-2"></i>
                            Cancelar Token
                        </button>
                        <button onclick="showTokenModal()" class="w-full px-4 py-3 rounded-lg bg-white/5 hover:bg-white/10 border border-white/10 text-zinc-400 hover:text-white text-sm font-medium transition">
                            Voltar
                        </button>
                    </div>
                </div>
            `;
            
            document.getElementById('modalContent').innerHTML = confirmHTML;
            lucide.createIcons();
        }
        
        function keepTokenPending() {
            // Fechar modal sem cancelar o token
            document.getElementById('connectionModal').classList.add('hidden');
        }
        
        function cancelToken() {
            if (!pendingToken) {
                document.getElementById('connectionModal').classList.add('hidden');
                return;
            }
            
            // Mostrar loading
            document.getElementById('modalContent').innerHTML = `
                <div class="text-center py-8">
                    <div class="inline-block w-8 h-8 border-4 border-white/20 border-t-white rounded-full animate-spin"></div>
                    <p class="text-zinc-400 mt-4">Cancelando token...</p>
                </div>
            `;
            
            // Cancelar token via API
            fetch('../api/cancel-connection-token.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `token=${pendingToken}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Limpar token pendente
                    pendingToken = null;
                    pendingSystemName = null;
                    pendingExpiresIn = null;
                    
                    // Fechar modal
                    document.getElementById('connectionModal').classList.add('hidden');
                    
                    // Mostrar notificação de sucesso (opcional)
                    // Você pode adicionar um toast aqui
                } else {
                    document.getElementById('modalContent').innerHTML = `
                        <div class="text-center py-8">
                            <div class="w-12 h-12 rounded-full bg-red-500/10 border border-red-500/20 flex items-center justify-center mx-auto mb-4">
                                <i data-lucide="alert-circle" class="w-6 h-6 text-red-400"></i>
                            </div>
                            <p class="text-red-400 font-semibold mb-4">${data.error || 'Erro ao cancelar token'}</p>
                            <button onclick="showTokenModal()" class="px-6 py-2 rounded-lg btn-primary text-sm font-semibold">
                                Voltar
                            </button>
                        </div>
                    `;
                    lucide.createIcons();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('modalContent').innerHTML = `
                    <div class="text-center py-8">
                        <div class="w-12 h-12 rounded-full bg-red-500/10 border border-red-500/20 flex items-center justify-center mx-auto mb-4">
                            <i data-lucide="alert-circle" class="w-6 h-6 text-red-400"></i>
                        </div>
                        <p class="text-red-400 font-semibold mb-4">Erro ao cancelar token</p>
                        <button onclick="showTokenModal()" class="px-6 py-2 rounded-lg btn-primary text-sm font-semibold">
                            Voltar
                        </button>
                    </div>
                `;
                lucide.createIcons();
            });
        }
        
        function showTokenModal() {
            // Restaurar conteúdo do token
            if (pendingToken && pendingSystemName && pendingExpiresIn) {
                loadTokenData(pendingSystemName, pendingToken, pendingExpiresIn);
            } else {
                document.getElementById('connectionModal').classList.add('hidden');
            }
        }

        function copyToken() {
            const tokenInput = document.getElementById('manualToken');
            tokenInput.select();
            document.execCommand('copy');
            
            const btn = event.target;
            const originalText = btn.textContent;
            btn.textContent = 'Copiado!';
            btn.classList.add('bg-emerald-500/10', 'border-emerald-500/20', 'text-emerald-400');
            
            setTimeout(() => {
                btn.textContent = originalText;
                btn.classList.remove('bg-emerald-500/10', 'border-emerald-500/20', 'text-emerald-400');
            }, 2000);
        }

        function disconnectSystem(systemName) {
            if (confirm('Deseja realmente desconectar este sistema?')) {
                alert('Funcionalidade de desconexão será implementada');
            }
        }
    </script>
</body>
</html>
