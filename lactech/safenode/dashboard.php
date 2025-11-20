<?php
/**
 * SafeNode - Dashboard Principal
 * Sistema de Segurança Integrado
 */

session_start();

// Verificar se está logado
if (!isset($_SESSION['safenode_logged_in']) || $_SESSION['safenode_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/init.php';

$db = getSafeNodeDatabase();

// Buscar estatísticas gerais
$stats = [
    'total_requests_today' => 0,
    'blocked_today' => 0,
    'allowed_today' => 0,
    'unique_ips_today' => 0,
    'active_blocks' => 0,
    'threats_today' => [
        'sql_injection' => 0,
        'xss' => 0,
        'brute_force' => 0,
        'rate_limit' => 0
    ]
];

if ($db) {
    try {
        // Estatísticas do dia
        $stmt = $db->query("SELECT * FROM v_safenode_today_stats");
        $todayStats = $stmt->fetch();
        if ($todayStats) {
            $stats['total_requests_today'] = $todayStats['total_requests'] ?? 0;
            $stats['blocked_today'] = $todayStats['blocked_requests'] ?? 0;
            $stats['allowed_today'] = $todayStats['allowed_requests'] ?? 0;
            $stats['unique_ips_today'] = $todayStats['unique_ips'] ?? 0;
            $stats['threats_today']['sql_injection'] = $todayStats['sql_injection_count'] ?? 0;
            $stats['threats_today']['xss'] = $todayStats['xss_count'] ?? 0;
            $stats['threats_today']['brute_force'] = $todayStats['brute_force_count'] ?? 0;
            $stats['threats_today']['rate_limit'] = $todayStats['rate_limit_count'] ?? 0;
        }
        
        // Estatísticas das últimas 24 horas
        $stmt = $db->query("SELECT 
            COUNT(*) as total_requests,
            SUM(CASE WHEN action_taken = 'blocked' THEN 1 ELSE 0 END) as blocked_requests,
            COUNT(DISTINCT ip_address) as unique_ips
            FROM safenode_security_logs 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        $last24hStats = $stmt->fetch();
        
        // Estatísticas de ontem para comparação
        $stmt = $db->query("SELECT 
            COUNT(*) as total_requests,
            SUM(CASE WHEN action_taken = 'blocked' THEN 1 ELSE 0 END) as blocked_requests,
            COUNT(DISTINCT ip_address) as unique_ips
            FROM safenode_security_logs 
            WHERE DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)");
        $yesterdayStats = $stmt->fetch();
        
        // Calcular mudanças percentuais
        $requests24h = $last24hStats['total_requests'] ?? 0;
        $blocked24h = $last24hStats['blocked_requests'] ?? 0;
        $yesterdayRequests = $yesterdayStats['total_requests'] ?? 0;
        $requestsChange = $yesterdayRequests > 0 ? round((($requests24h - $yesterdayRequests) / $yesterdayRequests) * 100) : 0;
        
        // Calcular latência global (P99)
        require_once __DIR__ . '/includes/SecurityLogger.php';
        $securityLogger = new SecurityLogger($db);
        $latencyData = $securityLogger->calculateLatency(null, 3600); // Última hora
        $globalLatency = $latencyData ? $latencyData['p99'] : null;
        $avgLatency = $latencyData ? $latencyData['avg'] : null;
        
        // IPs bloqueados ativos
        $stmt = $db->query("SELECT COUNT(*) as total FROM v_safenode_active_blocks");
        $activeBlocks = $stmt->fetch();
        $stats['active_blocks'] = $activeBlocks['total'] ?? 0;
        
        // Verificar se há sites configurados
        $stmt = $db->query("SELECT COUNT(*) as total FROM safenode_sites WHERE is_active = 1");
        $sitesResult = $stmt->fetch();
        $hasSites = ($sitesResult['total'] ?? 0) > 0;
        
        // Últimos logs
        $stmt = $db->query("SELECT * FROM safenode_security_logs ORDER BY created_at DESC LIMIT 10");
        $recentLogs = $stmt->fetchAll();
        
        // Top IPs bloqueados
        $stmt = $db->query("SELECT * FROM v_safenode_top_blocked_ips LIMIT 5");
        $topBlockedIPs = $stmt->fetchAll();
        
        // Dados para gráfico de linha (últimas 24 horas por hora)
        $stmt = $db->query("SELECT 
            HOUR(created_at) as hour,
            COUNT(*) as requests,
            SUM(CASE WHEN action_taken = 'blocked' THEN 1 ELSE 0 END) as blocked
            FROM safenode_security_logs 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            GROUP BY HOUR(created_at)
            ORDER BY hour");
        $hourlyData = $stmt->fetchAll();
        
        // Estatísticas por hora (últimas 7 horas)
        $hourlyStats = [];
        for ($i = 6; $i >= 0; $i--) {
            $hour = date('H', strtotime("-$i hours"));
            $hourlyStats[$hour] = ['requests' => 0, 'blocked' => 0];
        }
        foreach ($hourlyData as $data) {
            $hour = str_pad($data['hour'], 2, '0', STR_PAD_LEFT);
            if (isset($hourlyStats[$hour])) {
                $hourlyStats[$hour]['requests'] = $data['requests'];
                $hourlyStats[$hour]['blocked'] = $data['blocked'];
            }
        }
        
    } catch (PDOException $e) {
        $recentLogs = [];
        $topBlockedIPs = [];
        $yesterdayStats = ['total_requests' => 0, 'blocked_requests' => 0, 'unique_ips' => 0];
        $hourlyStats = [];
        error_log("SafeNode Stats Error: " . $e->getMessage());
    }
} else {
    $recentLogs = [];
    $topBlockedIPs = [];
    $yesterdayStats = ['total_requests' => 0, 'blocked_requests' => 0, 'unique_ips' => 0];
    $hourlyStats = [];
    $hasSites = false;
}

// Se não houver sites, definir hasSites como false
if (!isset($hasSites)) {
    $hasSites = false;
}

// Calcular taxa de bloqueio
$blockRate = $stats['total_requests_today'] > 0 
    ? round(($stats['blocked_today'] / $stats['total_requests_today']) * 100, 1) 
    : 0;

// Calcular variações percentuais
$requestsChange = $yesterdayStats['total_requests'] > 0 
    ? round((($stats['total_requests_today'] - $yesterdayStats['total_requests']) / $yesterdayStats['total_requests']) * 100, 1)
    : ($stats['total_requests_today'] > 0 ? 100 : 0);

$blockedChange = $yesterdayStats['blocked_requests'] > 0 
    ? round((($stats['blocked_today'] - $yesterdayStats['blocked_requests']) / $yesterdayStats['blocked_requests']) * 100, 1)
    : ($stats['blocked_today'] > 0 ? 100 : 0);

$ipsChange = $yesterdayStats['unique_ips'] > 0 
    ? round((($stats['unique_ips_today'] - $yesterdayStats['unique_ips']) / $yesterdayStats['unique_ips']) * 100, 1)
    : ($stats['unique_ips_today'] > 0 ? 100 : 0);
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                        black: '#000000',
                        zinc: {
                            850: '#1f2937',
                            900: '#18181b',
                            950: '#09090b',
                        }
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-out',
                        'slide-up': 'slideUp 0.5s ease-out forwards',
                        'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                    },
                    keyframes: {
                        fadeIn: {
                            '0%': { opacity: '0' },
                            '100%': { opacity: '1' },
                        },
                        slideUp: {
                            '0%': { opacity: '0', transform: 'translateY(20px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' },
                        }
                    }
                }
            }
        }
    </script>
    <style>
        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #3f3f46; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #52525b; }

        /* Glass Components */
        .glass-panel {
            background: rgba(24, 24, 27, 0.6);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        
        .glass-card {
            background: linear-gradient(180deg, rgba(39, 39, 42, 0.4) 0%, rgba(24, 24, 27, 0.4) 100%);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

    </style>
</head>
<body class="bg-black text-zinc-200 font-sans h-full overflow-hidden flex selection:bg-blue-500/30">

    <!-- Sidebar -->
    <aside class="w-72 bg-black border-r border-white/10 flex flex-col h-full z-50 hidden md:flex">
        <!-- Logo -->
        <div class="h-16 flex items-center px-6 border-b border-white/5">
            <div class="flex items-center gap-3 group cursor-pointer" onclick="window.location.href='index.php'">
                <div class="relative">
                    <div class="absolute inset-0 bg-blue-500/20 blur-lg rounded-full group-hover:bg-blue-500/40 transition-all"></div>
                    <img src="assets/img/logos (6).png" alt="SafeNode" class="h-8 w-auto relative z-10">
                </div>
                <span class="font-bold text-lg text-white tracking-tight group-hover:text-blue-400 transition-colors">SafeNode</span>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 overflow-y-auto py-6 px-4 space-y-1">
            <div class="px-3 mb-2 text-xs font-medium text-zinc-500 uppercase tracking-wider">Principal</div>
            
            <a href="dashboard.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium bg-blue-500/10 text-blue-400 border border-blue-500/20 shadow-[0_0_15px_rgba(59,130,246,0.1)] transition-all">
                <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
                Dashboard
            </a>
            
            <a href="sites.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-zinc-400 hover:bg-white/5 hover:text-white transition-all group">
                <i data-lucide="globe" class="w-5 h-5 group-hover:text-blue-400 transition-colors"></i>
                Sites
            </a>

            <a href="logs.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-zinc-400 hover:bg-white/5 hover:text-white transition-all group">
                <i data-lucide="shield-alert" class="w-5 h-5 group-hover:text-red-400 transition-colors"></i>
                Logs de Segurança
            </a>

            <a href="blocked.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-zinc-400 hover:bg-white/5 hover:text-white transition-all group">
                <i data-lucide="ban" class="w-5 h-5 group-hover:text-red-400 transition-colors"></i>
                IPs Bloqueados
            </a>

            <div class="px-3 mt-8 mb-2 text-xs font-medium text-zinc-500 uppercase tracking-wider">Sistema</div>

            <a href="settings.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-zinc-400 hover:bg-white/5 hover:text-white transition-all group">
                <i data-lucide="settings" class="w-5 h-5 group-hover:text-zinc-300 transition-colors"></i>
                Configurações
            </a>
        </nav>

        <!-- User Profile (Bottom) -->
        <div class="p-4 border-t border-white/5 bg-zinc-900/30">
            <button onclick="window.location.href='profile.php'" class="w-full flex items-center gap-3 hover:bg-white/5 rounded-lg p-2 transition-all group">
                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-600 to-purple-600 flex items-center justify-center text-white font-bold text-sm shadow-lg shadow-blue-500/20 group-hover:scale-105 transition-transform">
                    <?php echo strtoupper(substr($_SESSION['safenode_username'] ?? 'U', 0, 1)); ?>
                </div>
                <div class="flex-1 min-w-0 text-left">
                    <p class="text-sm font-medium text-white truncate"><?php echo htmlspecialchars($_SESSION['safenode_username'] ?? 'Admin'); ?></p>
                    <p class="text-xs text-zinc-500 truncate">Ver perfil</p>
                </div>
                <i data-lucide="chevron-right" class="w-4 h-4 text-zinc-500 group-hover:text-white transition-colors"></i>
            </button>
            <button onclick="window.location.href='login.php?logout=1'" class="w-full mt-2 p-2 text-zinc-400 hover:text-red-400 hover:bg-red-500/10 rounded-lg transition-all text-left flex items-center gap-2" title="Sair">
                <i data-lucide="log-out" class="w-4 h-4"></i>
                <span class="text-sm">Sair</span>
            </button>
        </div>
    </aside>

    <!-- Main Content -->
    <main x-data="{ notificationsOpen: false }" class="flex-1 flex flex-col h-full relative overflow-hidden bg-black">
        <!-- Header -->
        <header class="h-16 border-b border-white/5 bg-black/50 backdrop-blur-xl sticky top-0 z-40 px-6 flex items-center justify-between">
                <div class="flex items-center gap-4 md:hidden">
                <button class="text-zinc-400 hover:text-white">
                    <i data-lucide="menu" class="w-6 h-6"></i>
                </button>
                <span class="font-bold text-lg text-white">SafeNode</span>
            </div>

            <div class="hidden md:flex items-center gap-4">
                <div class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-zinc-900/50 border border-white/5 text-xs font-medium <?php echo $hasSites ? 'text-zinc-400' : 'text-amber-400'; ?>">
                    <span class="w-2 h-2 rounded-full <?php echo $hasSites ? 'bg-emerald-500' : 'bg-amber-500'; ?> animate-pulse"></span>
                    Sistema <?php echo $hasSites ? 'Ativo' : 'Inativo'; ?>
                </div>
                <div class="h-4 w-px bg-white/10"></div>
                <div class="text-xs text-zinc-500 font-mono">
                    UPDATED: <span id="lastUpdate" class="text-zinc-300"><?php echo date('H:i:s'); ?></span>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <button onclick="location.reload()" class="p-2 text-zinc-400 hover:text-white hover:bg-white/5 rounded-lg transition-all" title="Atualizar">
                    <i data-lucide="refresh-cw" class="w-5 h-5"></i>
                </button>
                <div class="relative">
                    <button @click="notificationsOpen = !notificationsOpen" class="p-2 text-zinc-400 hover:text-white hover:bg-white/5 rounded-lg transition-all relative">
                        <i data-lucide="bell" class="w-5 h-5"></i>
                        <span class="absolute top-2 right-2 w-2 h-2 bg-red-500 rounded-full border-2 border-black animate-pulse"></span>
                    </button>
                </div>
                <button onclick="window.location.href='profile.php'" class="hidden md:flex items-center gap-2 px-3 py-2 text-zinc-400 hover:text-white hover:bg-white/5 rounded-lg transition-all">
                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-600 to-purple-600 flex items-center justify-center text-white font-bold text-xs">
                        <?php echo strtoupper(substr($_SESSION['safenode_username'] ?? 'U', 0, 1)); ?>
                    </div>
                    <span class="text-sm font-medium hidden lg:block"><?php echo htmlspecialchars($_SESSION['safenode_username'] ?? 'Admin'); ?></span>
                </button>
            </div>
        </header>

        <!-- Scrollable Content -->
        <div class="flex-1 overflow-y-auto p-6 md:p-8 z-10 scroll-smooth">
            <div class="max-w-7xl mx-auto space-y-8">
                
                <!-- Banner de Configuração (se não houver sites) -->
                <?php if (!$hasSites): ?>
                <div class="rounded-xl bg-gradient-to-r from-amber-500/10 to-orange-500/10 border border-amber-500/20 p-6 animate-fade-in">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 rounded-full bg-amber-500/20 flex items-center justify-center border border-amber-500/30">
                                <i data-lucide="alert-circle" class="w-6 h-6 text-amber-400"></i>
                            </div>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-bold text-white mb-2 flex items-center gap-2">
                                Configure seu primeiro site
                            </h3>
                            <p class="text-zinc-400 text-sm mb-4">
                                Para ativar todas as funcionalidades do SafeNode, você precisa configurar pelo menos um site. 
                                Configure seu site agora para começar a monitorar e proteger seu tráfego.
                            </p>
                            <a href="sites.php" class="inline-flex items-center gap-2 px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-lg text-sm font-semibold transition-all shadow-lg shadow-amber-500/20">
                                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                                Configurar Site Agora
                            </a>
                        </div>
                        <button onclick="this.parentElement.parentElement.remove()" class="flex-shrink-0 text-zinc-400 hover:text-white transition-colors">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Welcome & Actions -->
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 animate-fade-in">
                    <div>
                        <h1 class="text-2xl font-bold text-white tracking-tight mb-1">Visão Geral</h1>
                        <p class="text-zinc-400 text-sm">Monitoramento e análise de segurança em tempo real.</p>
                    </div>
                    <div class="flex gap-3 relative">
                        <?php if (!$hasSites): ?>
                        <div class="absolute inset-0 -m-2 bg-zinc-900/50 rounded-lg z-20 pointer-events-none"></div>
                        <?php endif; ?>
                        <a href="logs.php" class="flex items-center gap-2 px-4 py-2 bg-white text-black rounded-lg text-sm font-semibold hover:bg-zinc-200 transition-all shadow-lg shadow-white/5 relative <?php echo !$hasSites ? 'opacity-40 blur-sm z-0 pointer-events-none cursor-not-allowed' : 'z-10'; ?>">
                            <i data-lucide="file-text" class="w-4 h-4"></i>
                            Relatório
                        </a>
                    </div>
                </div>

                <!-- Novos Stats (Preview Style) -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Total de Requisições -->
                    <div class="p-4 rounded-xl bg-zinc-900/40 border border-zinc-800/50 hover:border-zinc-700 transition-colors group <?php echo !$hasSites ? 'opacity-40 blur-sm relative' : ''; ?>">
                        <?php if (!$hasSites): ?>
                        <div class="absolute inset-0 bg-zinc-900/50 rounded-xl z-10 pointer-events-none"></div>
                        <?php endif; ?>
                        <div class="flex justify-between items-start mb-2 relative <?php echo !$hasSites ? 'z-0' : ''; ?>">
                            <div class="text-xs text-zinc-500 uppercase font-medium">Total de Requisições</div>
                            <span class="text-[10px] text-green-400 bg-green-900/20 px-1.5 py-0.5 rounded border border-green-900/30 flex items-center gap-1">
                                <i data-lucide="arrow-up-right" class="w-3 h-3"></i> <?php echo abs($requestsChange); ?>%
                            </span>
                        </div>
                        <div class="text-2xl font-bold text-white group-hover:text-green-400 transition-colors relative <?php echo !$hasSites ? 'z-0' : ''; ?>">
                            <?php 
                            if ($requests24h >= 1000000) {
                                echo number_format($requests24h / 1000000, 1) . 'M';
                            } elseif ($requests24h >= 1000) {
                                echo number_format($requests24h / 1000, 1) . 'k';
                            } else {
                                echo number_format($requests24h);
                            }
                            ?>
                        </div>
                        <div class="text-xs text-zinc-600 mt-1 relative <?php echo !$hasSites ? 'z-0' : ''; ?>">Últimas 24 horas</div>
                    </div>
                    
                    <div class="p-4 rounded-xl bg-zinc-900/40 border border-zinc-800/50 hover:border-zinc-700 transition-colors group <?php echo !$hasSites ? 'opacity-40 blur-sm relative' : ''; ?>">
                        <?php if (!$hasSites): ?>
                        <div class="absolute inset-0 bg-zinc-900/50 rounded-xl z-10 pointer-events-none"></div>
                        <?php endif; ?>
                        <div class="flex justify-between items-start mb-2 relative <?php echo !$hasSites ? 'z-0' : ''; ?>">
                            <div class="text-xs text-zinc-500 uppercase font-medium">Ameaças Mitigadas</div>
                            <span class="text-[10px] text-red-400 bg-red-900/20 px-1.5 py-0.5 rounded border border-red-900/30 flex items-center gap-1">
                                <i data-lucide="shield-alert" class="w-3 h-3"></i> <?php echo number_format($blocked24h); ?>
                            </span>
                        </div>
                        <div class="text-2xl font-bold text-white group-hover:text-red-400 transition-colors relative <?php echo !$hasSites ? 'z-0' : ''; ?>">
                            <?php 
                            if ($blocked24h >= 1000000) {
                                echo number_format($blocked24h / 1000000, 1) . 'M';
                            } elseif ($blocked24h >= 1000) {
                                echo number_format($blocked24h / 1000, 1) . 'k';
                            } else {
                                echo number_format($blocked24h);
                            }
                            ?>
                        </div>
                        <div class="text-xs text-zinc-600 mt-1 relative <?php echo !$hasSites ? 'z-0' : ''; ?>">Bloqueado automaticamente por IA</div>
                    </div>
                    
                    <?php if ($hasSites): ?>
                    <div class="p-4 rounded-xl bg-zinc-900/40 border border-zinc-800/50 hover:border-zinc-700 transition-colors group">
                        <div class="flex justify-between items-start mb-2">
                            <div class="text-xs text-zinc-500 uppercase font-medium">Latência Global</div>
                            <?php if ($globalLatency !== null && $avgLatency !== null): 
                                $latencyChange = $avgLatency < ($globalLatency * 0.9) ? -round(($globalLatency - $avgLatency) / $globalLatency * 100) : 0;
                            ?>
                            <span class="text-[10px] <?php echo $latencyChange < 0 ? 'text-green-400' : 'text-blue-400'; ?> bg-<?php echo $latencyChange < 0 ? 'green' : 'blue'; ?>-900/20 px-1.5 py-0.5 rounded border border-<?php echo $latencyChange < 0 ? 'green' : 'blue'; ?>-900/30 flex items-center gap-1">
                                <i data-lucide="zap" class="w-3 h-3"></i> <?php echo $latencyChange < 0 ? $latencyChange . 'ms' : '--ms'; ?>
                            </span>
                            <?php else: ?>
                            <span class="text-[10px] text-blue-400 bg-blue-900/20 px-1.5 py-0.5 rounded border border-blue-900/30 flex items-center gap-1">
                                <i data-lucide="zap" class="w-3 h-3"></i> --
                            </span>
                            <?php endif; ?>
                        </div>
                        <div class="text-2xl font-bold text-white group-hover:text-blue-400 transition-colors">
                            <?php echo $globalLatency !== null ? number_format($globalLatency, 0) . 'ms' : '--ms'; ?>
                        </div>
                        <div class="text-xs text-zinc-600 mt-1">Tempo de Resposta P99</div>
                    </div>
                    <?php else: ?>
                    <div class="p-4 rounded-xl bg-zinc-900/40 border border-zinc-800/50 hover:border-zinc-700 transition-colors group opacity-40 blur-sm relative">
                        <div class="absolute inset-0 bg-zinc-900/50 rounded-xl z-10 pointer-events-none"></div>
                        <div class="flex justify-between items-start mb-2 relative z-0">
                            <div class="text-xs text-zinc-500 uppercase font-medium">Latência Global</div>
                            <span class="text-[10px] text-blue-400 bg-blue-900/20 px-1.5 py-0.5 rounded border border-blue-900/30 flex items-center gap-1">
                                <i data-lucide="zap" class="w-3 h-3"></i> --
                            </span>
                        </div>
                        <div class="text-2xl font-bold text-white group-hover:text-blue-400 transition-colors relative z-0">--ms</div>
                        <div class="text-xs text-zinc-600 mt-1 relative z-0">Tempo de Resposta P99</div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Stats Grid (Cards Adicionais) -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Unique IPs -->
                    <div class="glass-card p-6 rounded-xl relative overflow-hidden group hover:border-purple-500/30 transition-all duration-300 animate-slide-up <?php echo !$hasSites ? 'opacity-40 blur-sm' : ''; ?>" style="animation-delay: 0.3s;">
                        <?php if (!$hasSites): ?>
                        <div class="absolute inset-0 bg-zinc-900/50 rounded-xl z-10 pointer-events-none"></div>
                        <?php endif; ?>
                        <div class="absolute -right-10 -top-10 w-32 h-32 bg-purple-500/10 rounded-full blur-3xl group-hover:bg-purple-500/20 transition-all"></div>
                        <div class="flex justify-between items-start mb-4 relative z-10">
                            <div class="p-2.5 bg-purple-500/10 rounded-lg text-purple-400 border border-purple-500/20">
                                <i data-lucide="globe" class="w-5 h-5"></i>
                            </div>
                            <?php if ($ipsChange != 0): ?>
                                <span class="text-xs font-bold px-2 py-1 rounded-full border <?php echo $ipsChange > 0 ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : 'bg-zinc-800 text-zinc-400 border-zinc-700'; ?>">
                                    <?php echo $ipsChange > 0 ? '+' : ''; ?><?php echo $ipsChange; ?>%
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="relative <?php echo !$hasSites ? 'z-0' : 'z-10'; ?>">
                            <h3 class="text-zinc-400 text-xs font-medium uppercase tracking-wider mb-1">Visitantes Únicos</h3>
                            <p class="text-2xl font-bold text-white font-mono"><?php echo number_format($stats['unique_ips_today']); ?></p>
                        </div>
                    </div>

                    <!-- Active Rules -->
                    <div class="glass-card p-6 rounded-xl relative overflow-hidden group hover:border-amber-500/30 transition-all duration-300 animate-slide-up <?php echo !$hasSites ? 'opacity-40 blur-sm' : ''; ?>" style="animation-delay: 0.4s;">
                        <?php if (!$hasSites): ?>
                        <div class="absolute inset-0 bg-zinc-900/50 rounded-xl z-10 pointer-events-none"></div>
                        <?php endif; ?>
                        <div class="absolute -right-10 -top-10 w-32 h-32 bg-amber-500/10 rounded-full blur-3xl group-hover:bg-amber-500/20 transition-all"></div>
                        <div class="flex justify-between items-start mb-4 relative <?php echo !$hasSites ? 'z-0' : 'z-10'; ?>">
                            <div class="p-2.5 bg-amber-500/10 rounded-lg text-amber-400 border border-amber-500/20">
                                <i data-lucide="lock" class="w-5 h-5"></i>
                            </div>
                            <span class="text-xs font-bold px-2 py-1 rounded-full bg-amber-500/10 text-amber-400 border border-amber-500/20">Ativo</span>
                        </div>
                        <div class="relative <?php echo !$hasSites ? 'z-0' : 'z-10'; ?>">
                            <h3 class="text-zinc-400 text-xs font-medium uppercase tracking-wider mb-1">Regras Ativas</h3>
                            <p class="text-2xl font-bold text-white font-mono"><?php echo number_format($stats['active_blocks']); ?></p>
                            <p class="text-xs text-zinc-500 mt-1">IPs na lista negra</p>
                        </div>
                    </div>
                </div>

                <!-- Charts Area -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 animate-slide-up" style="animation-delay: 0.5s;">
                    <!-- Traffic Chart with World Map -->
                    <div class="lg:col-span-2 glass-card p-6 rounded-xl relative overflow-hidden <?php echo !$hasSites ? 'opacity-40 blur-sm' : ''; ?>">
                        <?php if (!$hasSites): ?>
                        <div class="absolute inset-0 bg-zinc-900/50 rounded-xl z-20 pointer-events-none"></div>
                        <?php endif; ?>
                        <div class="flex items-center justify-between mb-6 relative <?php echo !$hasSites ? 'z-0' : 'z-10'; ?>">
                            <div>
                                <h3 class="font-bold text-white text-lg">Análise de Tráfego Global</h3>
                                <p class="text-sm text-zinc-500">Últimas 24 horas</p>
                            </div>
                            <div class="flex gap-4 text-xs font-medium">
                                <div class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                                    <span class="text-zinc-400">Total</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-red-500"></span>
                                    <span class="text-zinc-400">Bloqueado</span>
                                </div>
                            </div>
                        </div>
                        <!-- World Map Background -->
                        <div class="absolute inset-0 opacity-10 pointer-events-none">
                            <svg viewBox="0 0 1000 500" class="w-full h-full" preserveAspectRatio="xMidYMid meet">
                                <path d="M200,200 Q300,150 400,200 T600,200" fill="none" stroke="currentColor" stroke-width="0.5" class="text-white"/>
                                <circle cx="300" cy="150" r="3" fill="currentColor" class="text-blue-400 animate-pulse"/>
                                <circle cx="500" cy="200" r="3" fill="currentColor" class="text-red-400 animate-pulse"/>
                                <circle cx="700" cy="180" r="3" fill="currentColor" class="text-purple-400 animate-pulse"/>
                                <path d="M300,150 L500,200 M500,200 L700,180" stroke="currentColor" stroke-width="0.3" stroke-dasharray="2,2" class="text-white/30"/>
                            </svg>
                        </div>
                        <div class="h-[300px] w-full relative <?php echo !$hasSites ? 'z-0' : 'z-10'; ?>">
                            <canvas id="activityChart"></canvas>
                        </div>
                    </div>

                    <!-- Threats Donut Enhanced -->
                    <div class="glass-card p-6 rounded-xl flex flex-col relative overflow-hidden <?php echo !$hasSites ? 'opacity-40 blur-sm' : ''; ?>">
                        <?php if (!$hasSites): ?>
                        <div class="absolute inset-0 bg-zinc-900/50 rounded-xl z-20 pointer-events-none"></div>
                        <?php endif; ?>
                        <div class="absolute -right-10 -top-10 w-32 h-32 bg-gradient-to-br from-red-500/10 to-purple-500/10 rounded-full blur-3xl"></div>
                        <h3 class="font-bold text-white text-lg mb-1 relative <?php echo !$hasSites ? 'z-0' : 'z-10'; ?>">Tipos de Ameaça</h3>
                        <p class="text-sm text-zinc-500 mb-6 relative <?php echo !$hasSites ? 'z-0' : 'z-10'; ?>">Distribuição por categoria</p>
                        
                        <div class="flex-1 relative min-h-[200px] flex items-center justify-center <?php echo !$hasSites ? 'z-0' : 'z-10'; ?>">
                            <canvas id="threatsChart"></canvas>
                            <!-- Center Stat -->
                            <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                                <span class="text-3xl font-bold text-white tracking-tight"><?php echo number_format($stats['blocked_today']); ?></span>
                                <span class="text-xs text-zinc-500 font-medium uppercase tracking-wider">Total</span>
                            </div>
                        </div>
                        <!-- Legend -->
                        <div class="grid grid-cols-2 gap-2 mt-4 text-xs relative <?php echo !$hasSites ? 'z-0' : 'z-10'; ?>">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-red-500"></span>
                                <span class="text-zinc-400">SQL</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-orange-500"></span>
                                <span class="text-zinc-400">XSS</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                <span class="text-zinc-400">Brute</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-green-500"></span>
                                <span class="text-zinc-400">Rate</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mapa de Tráfego e Registro de Eventos (Preview Style) -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Mapa de Tráfego em Tempo Real -->
                    <div class="lg:col-span-2 rounded-xl bg-[#050505] border border-zinc-800/50 relative overflow-hidden flex flex-col <?php echo !$hasSites ? 'opacity-40 blur-sm' : ''; ?>">
                        <?php if (!$hasSites): ?>
                        <div class="absolute inset-0 bg-zinc-900/50 rounded-xl z-20 pointer-events-none"></div>
                        <?php endif; ?>
                        <div class="absolute inset-0 bg-[linear-gradient(to_right,#80808008_1px,transparent_1px),linear-gradient(to_bottom,#80808008_1px,transparent_1px)] bg-[size:24px_24px]"></div>
                        
                        <div class="p-4 border-b border-zinc-800/50 flex justify-between items-center relative <?php echo !$hasSites ? 'z-0' : 'z-10'; ?> bg-black/20 backdrop-blur-sm">
                            <div class="flex items-center gap-2">
                                <div class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></div>
                                <span class="text-xs font-medium text-zinc-300">Mapa de Tráfego em Tempo Real</span>
                            </div>
                            <div class="flex gap-2">
                                <span class="text-[10px] px-2 py-1 rounded bg-zinc-800 text-zinc-400">1H</span>
                                <span class="text-[10px] px-2 py-1 rounded bg-zinc-900 text-zinc-600 hover:text-zinc-400 cursor-pointer">24H</span>
                            </div>
                        </div>

                        <div class="flex-1 relative flex items-center justify-center min-h-[300px]">
                            <!-- Central Node -->
                            <div class="relative <?php echo !$hasSites ? 'z-0' : 'z-10'; ?>">
                                <div class="w-16 h-16 rounded-full bg-zinc-900 border border-zinc-700 flex items-center justify-center shadow-[0_0_30px_rgba(16,185,129,0.1)]">
                                    <i data-lucide="server" class="w-6 h-6 text-green-500"></i>
                                </div>
                                <!-- Ripples -->
                                <div class="absolute inset-0 -m-8 border border-green-500/10 rounded-full animate-[ping_3s_linear_infinite]"></div>
                                <div class="absolute inset-0 -m-16 border border-green-500/5 rounded-full animate-[ping_3s_linear_infinite]" style="animation-delay: 1s"></div>
                            </div>

                            <!-- Incoming Traffic Particles -->
                            <div class="absolute inset-0 overflow-hidden">
                                <div class="absolute top-1/4 left-10 w-1 h-1 bg-white rounded-full animate-[traffic-flow_2s_linear_infinite]"></div>
                                <div class="absolute bottom-1/3 right-10 w-1 h-1 bg-white rounded-full animate-[traffic-flow_3s_linear_infinite_reverse]"></div>
                                <div class="absolute top-10 right-1/3 w-1 h-1 bg-red-500 rounded-full animate-[traffic-blocked_2s_linear_infinite]"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Registro de Eventos -->
                    <div class="lg:col-span-1 rounded-xl bg-zinc-950 border border-zinc-800/50 flex flex-col overflow-hidden relative <?php echo !$hasSites ? 'opacity-40 blur-sm' : ''; ?>">
                        <?php if (!$hasSites): ?>
                        <div class="absolute inset-0 bg-zinc-900/50 rounded-xl z-20 pointer-events-none"></div>
                        <?php endif; ?>
                        <div class="p-3 border-b border-zinc-800/50 bg-zinc-900/30 relative <?php echo !$hasSites ? 'z-0' : 'z-10'; ?>">
                            <span class="text-xs font-medium text-zinc-400">Registro de Eventos</span>
                        </div>
                        <div class="flex-1 p-3 space-y-3 overflow-y-auto font-mono text-[10px] max-h-[400px] relative <?php echo !$hasSites ? 'z-0' : 'z-10'; ?>">
                            <?php 
                            $eventLogs = array_slice($recentLogs, 0, 5);
                            if (empty($eventLogs)): ?>
                                <div class="text-zinc-500 text-center py-4">Nenhum evento recente</div>
                            <?php else: ?>
                                <?php 
                                $opacity = ['opacity-50', 'opacity-70', '', 'opacity-80', ''];
                                $i = 0;
                                foreach ($eventLogs as $log): 
                                    $isCritical = ($log['threat_score'] ?? 0) >= 70 || stripos($log['threat_type'] ?? '', 'ddos') !== false;
                                    $showMitigated = $isCritical && $log['action_taken'] === 'blocked';
                                ?>
                                    <div class="flex gap-2 <?php echo $opacity[$i] ?? ''; ?> <?php echo $showMitigated ? 'border-l-2 border-red-500 pl-2 bg-red-500/5 py-1' : ''; ?>">
                                        <span class="text-zinc-500"><?php echo date('H:i:s', strtotime($log['created_at'])); ?></span>
                                        <span class="<?php echo $showMitigated ? 'text-red-500 font-bold' : ($log['action_taken'] === 'blocked' ? 'text-red-500' : 'text-green-500'); ?>">
                                            <?php echo $showMitigated ? 'MITIGADO' : ($log['action_taken'] === 'blocked' ? 'BLOQUEAR' : 'PERMITIR'); ?>
                                        </span>
                                        <span class="<?php echo $showMitigated ? 'text-white' : 'text-zinc-400'; ?>">
                                            <?php 
                                            if ($log['action_taken'] === 'blocked' && !empty($log['threat_type'])) {
                                                $threatNames = [
                                                    'sql_injection' => 'Injeção SQL',
                                                    'xss' => 'XSS',
                                                    'brute_force' => 'Força Bruta',
                                                    'rate_limit' => 'Rate Limit',
                                                    'path_traversal' => 'Path Traversal',
                                                    'command_injection' => 'Command Injection',
                                                    'ddos' => 'Ataque DDoS L7'
                                                ];
                                                echo $threatNames[$log['threat_type']] ?? ucfirst(str_replace('_', ' ', $log['threat_type']));
                                            } else {
                                                echo htmlspecialchars($log['ip_address']);
                                            }
                                            ?>
                                        </span>
                                    </div>
                                <?php 
                                    $i++;
                                endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Recent Logs -->
                <div class="glass-card rounded-xl overflow-hidden animate-slide-up relative <?php echo !$hasSites ? 'opacity-40 blur-sm' : ''; ?>" style="animation-delay: 0.6s;">
                    <?php if (!$hasSites): ?>
                    <div class="absolute inset-0 bg-zinc-900/50 rounded-xl  z-20 pointer-events-none"></div>
                    <?php endif; ?>
                    <div class="p-6 border-b border-white/5 flex items-center justify-between relative <?php echo !$hasSites ? 'z-0' : 'z-10'; ?>">
                        <h3 class="font-bold text-white text-lg">Atividade Recente</h3>
                        <a href="logs.php" class="text-sm text-blue-400 hover:text-blue-300 font-medium transition-colors flex items-center gap-1">
                            Ver histórico
                            <i data-lucide="arrow-right" class="w-3 h-3"></i>
                        </a>
                    </div>
                    <div class="overflow-x-auto relative <?php echo !$hasSites ? 'z-0' : 'z-10'; ?>">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-zinc-900/50 text-zinc-400 font-medium uppercase text-xs tracking-wider">
                                <tr>
                                    <th class="px-6 py-4">IP / Origem</th>
                                    <th class="px-6 py-4">Request URI</th>
                                    <th class="px-6 py-4">Status</th>
                                    <th class="px-6 py-4 text-right">Horário</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <?php if (empty($recentLogs)): ?>
                                    <tr>
                                        <td colspan="4" class="px-6 py-8 text-center text-zinc-500">
                                            Nenhuma atividade registrada recentemente.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recentLogs as $log): ?>
                                        <tr class="hover:bg-white/[0.02] transition-colors">
                                            <td class="px-6 py-4">
                                                <div class="flex items-center gap-3">
                                                    <div class="p-2 rounded bg-zinc-900 border border-white/5">
                                                        <i data-lucide="monitor" class="w-4 h-4 text-zinc-500"></i>
                                                    </div>
                                                    <div class="font-mono text-zinc-300"><?php echo htmlspecialchars($log['ip_address']); ?></div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-zinc-400 truncate max-w-[250px] font-mono text-xs" title="<?php echo htmlspecialchars($log['request_uri']); ?>">
                                                    <?php echo htmlspecialchars($log['request_uri']); ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <?php if ($log['action_taken'] === 'blocked'): ?>
                                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-red-500/10 text-red-400 border border-red-500/20">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                                        Bloqueado
                                                    </span>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                        Permitido
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 text-right text-zinc-500 font-mono text-xs">
                                                <?php echo date('H:i:s', strtotime($log['created_at'])); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <!-- Modal Lateral de Notificações -->
    <div x-show="notificationsOpen" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="translate-x-full opacity-0"
         x-transition:enter-end="translate-x-0 opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="translate-x-0 opacity-100"
         x-transition:leave-end="translate-x-full opacity-0"
         @click.away="notificationsOpen = false"
         class="fixed right-0 top-0 h-full w-96 bg-black/95 backdrop-blur-xl border-l border-white/10 z-50 shadow-2xl"
         x-cloak>
        <div class="flex flex-col h-full">
            <!-- Header do Modal -->
            <div class="p-6 border-b border-white/10 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-bold text-white">Notificações</h3>
                    <p class="text-xs text-zinc-400 mt-0.5">Alertas e eventos recentes</p>
                </div>
                <button @click="notificationsOpen = false" class="p-2 text-zinc-400 hover:text-white hover:bg-white/5 rounded-lg transition-all">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Lista de Notificações -->
            <div class="flex-1 overflow-y-auto p-4 space-y-3">
                <!-- Exemplo de Notificações -->
                <div class="p-4 rounded-xl bg-red-500/10 border border-red-500/20">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-full bg-red-500/20 flex items-center justify-center flex-shrink-0">
                            <i data-lucide="shield-alert" class="w-5 h-5 text-red-400"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-white">Ameaça Detectada</p>
                            <p class="text-xs text-zinc-400 mt-1">Tentativa de SQL Injection bloqueada de 192.168.1.100</p>
                            <p class="text-xs text-zinc-500 mt-2">Há 5 minutos</p>
                        </div>
                    </div>
                </div>

                <div class="p-4 rounded-xl bg-amber-500/10 border border-amber-500/20">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-full bg-amber-500/20 flex items-center justify-center flex-shrink-0">
                            <i data-lucide="alert-triangle" class="w-5 h-5 text-amber-400"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-white">Tráfego Alto</p>
                            <p class="text-xs text-zinc-400 mt-1">Pico de requisições detectado: 1,234/min</p>
                            <p class="text-xs text-zinc-500 mt-2">Há 15 minutos</p>
                        </div>
                    </div>
                </div>

                <div class="p-4 rounded-xl bg-blue-500/10 border border-blue-500/20">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-full bg-blue-500/20 flex items-center justify-center flex-shrink-0">
                            <i data-lucide="check-circle" class="w-5 h-5 text-blue-400"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-white">Sistema Atualizado</p>
                            <p class="text-xs text-zinc-400 mt-1">Regras de firewall sincronizadas com sucesso</p>
                            <p class="text-xs text-zinc-500 mt-2">Há 1 hora</p>
                        </div>
                    </div>
                </div>

                <!-- Mensagem quando não há notificações -->
                <div class="text-center py-12 text-zinc-500">
                    <i data-lucide="bell-off" class="w-12 h-12 mx-auto mb-3 opacity-50"></i>
                    <p class="text-sm">Nenhuma notificação nova</p>
                </div>
            </div>

            <!-- Footer -->
            <div class="p-4 border-t border-white/10">
                <button class="w-full px-4 py-2 text-sm text-blue-400 hover:text-blue-300 font-medium transition-colors">
                    Ver todas as notificações
                </button>
            </div>
        </div>
    </div>

    <script>
        // Initialize Lucide Icons
        lucide.createIcons();


        // Update time
        setInterval(() => {
            const updateEl = document.getElementById('lastUpdate');
            if (updateEl) {
                updateEl.textContent = new Date().toLocaleTimeString();
            }
        }, 1000);

        // Chart Configuration
        Chart.defaults.color = '#71717a';
        Chart.defaults.borderColor = 'rgba(255, 255, 255, 0.05)';
        
        // Activity Chart
        const activityCtx = document.getElementById('activityChart');
        if (activityCtx) {
            const hourlyData = <?php echo json_encode(array_values($hourlyStats)); ?>;
            const hours = <?php echo json_encode(array_keys($hourlyStats)); ?>;
            
            new Chart(activityCtx, {
                type: 'line',
                data: {
                    labels: hours.map(h => h + ':00'),
                    datasets: [{
                        label: 'Total',
                        data: hourlyData.map(d => d.requests),
                        borderColor: '#3b82f6',
                        backgroundColor: (context) => {
                            const ctx = context.chart.ctx;
                            const gradient = ctx.createLinearGradient(0, 0, 0, 300);
                            gradient.addColorStop(0, 'rgba(59, 130, 246, 0.2)');
                            gradient.addColorStop(1, 'rgba(59, 130, 246, 0)');
                            return gradient;
                        },
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 0,
                        pointHoverRadius: 4
                    }, {
                        label: 'Bloqueado',
                        data: hourlyData.map(d => d.blocked),
                        borderColor: '#ef4444',
                        backgroundColor: 'transparent',
                        borderWidth: 2,
                        borderDash: [4, 4],
                        tension: 0.4,
                        pointRadius: 0,
                        pointHoverRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: 'rgba(9, 9, 11, 0.9)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            borderColor: 'rgba(255, 255, 255, 0.1)',
                            borderWidth: 1,
                            padding: 10,
                            displayColors: true,
                            cornerRadius: 8
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(255, 255, 255, 0.05)' },
                            border: { display: false }
                        },
                        x: {
                            grid: { display: false },
                            border: { display: false }
                        }
                    }
                }
            });
        }
        
        // Threats Chart Enhanced
        const threatsCtx = document.getElementById('threatsChart');
        if (threatsCtx) {
            new Chart(threatsCtx, {
                type: 'doughnut',
                data: {
                    labels: ['SQL Injection', 'XSS', 'Brute Force', 'Rate Limit', 'Outros'],
                    datasets: [{
                        data: [
                            <?php echo $stats['threats_today']['sql_injection']; ?>,
                            <?php echo $stats['threats_today']['xss']; ?>,
                            <?php echo $stats['threats_today']['brute_force']; ?>,
                            <?php echo $stats['threats_today']['rate_limit']; ?>,
                            <?php echo max(0, $stats['blocked_today'] - array_sum($stats['threats_today'])); ?>
                        ],
                        backgroundColor: [
                            'rgba(239, 68, 68, 0.8)',
                            'rgba(249, 115, 22, 0.8)',
                            'rgba(245, 158, 11, 0.8)',
                            'rgba(132, 204, 22, 0.8)',
                            'rgba(63, 63, 70, 0.8)'
                        ],
                        borderColor: [
                            '#ef4444',
                            '#f97316',
                            '#f59e0b',
                            '#84cc16',
                            '#3f3f46'
                        ],
                        borderWidth: 2,
                        hoverOffset: 8,
                        hoverBorderWidth: 3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '75%',
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: 'rgba(9, 9, 11, 0.9)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            borderColor: 'rgba(255, 255, 255, 0.1)',
                            borderWidth: 1,
                            padding: 12,
                            displayColors: true,
                            callbacks: {
                                label: function(context) {
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((context.parsed / total) * 100).toFixed(1);
                                    return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                                }
                            }
                        }
                    }
                }
            });
        }

        // Animações para o mapa de tráfego
        const style = document.createElement('style');
        style.textContent = `
            @keyframes traffic-flow {
                0% { transform: translate(0, 0) scale(0); opacity: 0; }
                50% { opacity: 1; }
                100% { transform: translate(var(--tx, 100px), var(--ty, -50px)) scale(1); opacity: 0; }
            }
            @keyframes traffic-blocked {
                0% { transform: translate(0, 0) scale(0); opacity: 0; }
                50% { opacity: 1; }
                100% { transform: translate(var(--tx, -80px), var(--ty, 60px)) scale(1); opacity: 0; }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>

