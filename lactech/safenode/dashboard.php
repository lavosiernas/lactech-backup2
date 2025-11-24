<?php
/**
 * SafeNode - Dashboard Principal
 * Sistema de Segurança Integrado
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
        // SEGURANÇA: Verificar que o site pertence ao usuário logado
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
            ? 'Modo “Sob Ataque” ativado para este site.'
            : 'Modo “Sob Ataque” desativado.';
        $_SESSION['safenode_dashboard_message_type'] = $newLevel === 'under_attack' ? 'warning' : 'success';
    } catch (PDOException $e) {
        $_SESSION['safenode_dashboard_message'] = 'Não foi possível atualizar o modo de proteção.';
        $_SESSION['safenode_dashboard_message_type'] = 'error';
    }
    header('Location: dashboard.php');
    exit;
}

// Contexto do Site
$siteFilter = $currentSiteId > 0 ? " AND site_id = $currentSiteId " : "";
$siteFilterWhere = $currentSiteId > 0 ? " WHERE site_id = $currentSiteId " : "";
$siteFilterAnd = $currentSiteId > 0 ? " AND site_id = $currentSiteId " : "";

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
$topCountries = [];

if ($db) {
    try {
        // Estatísticas do dia (Recalculadas dinamicamente para suportar filtro)
        // Evitando usar a view global se tiver filtro
        $sqlToday = "SELECT 
            COUNT(*) as total_requests,
            SUM(CASE WHEN action_taken = 'blocked' THEN 1 ELSE 0 END) as blocked_requests,
            SUM(CASE WHEN action_taken = 'allowed' THEN 1 ELSE 0 END) as allowed_requests,
            COUNT(DISTINCT ip_address) as unique_ips,
            SUM(CASE WHEN threat_type = 'sql_injection' THEN 1 ELSE 0 END) as sql_injection_count,
            SUM(CASE WHEN threat_type = 'xss' THEN 1 ELSE 0 END) as xss_count,
            SUM(CASE WHEN threat_type = 'brute_force' THEN 1 ELSE 0 END) as brute_force_count,
            SUM(CASE WHEN threat_type = 'rate_limit' THEN 1 ELSE 0 END) as rate_limit_count
            FROM safenode_security_logs 
            WHERE DATE(created_at) = CURDATE() $siteFilterAnd";
            
        $stmt = $db->query($sqlToday);
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
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) $siteFilterAnd");
        $last24hStats = $stmt->fetch();
        
        // Estatísticas de ontem para comparação
        $stmt = $db->query("SELECT 
            COUNT(*) as total_requests,
            SUM(CASE WHEN action_taken = 'blocked' THEN 1 ELSE 0 END) as blocked_requests,
            COUNT(DISTINCT ip_address) as unique_ips
            FROM safenode_security_logs 
            WHERE DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY) $siteFilterAnd");
        $yesterdayStats = $stmt->fetch();
        
        // Calcular mudanças percentuais
        $requests24h = $last24hStats['total_requests'] ?? 0;
        $blocked24h = $last24hStats['blocked_requests'] ?? 0;
        $yesterdayRequests = $yesterdayStats['total_requests'] ?? 0;
        $requestsChange = $yesterdayRequests > 0 ? round((($requests24h - $yesterdayRequests) / $yesterdayRequests) * 100) : 0;
        
        // Calcular latência global (P99)
        require_once __DIR__ . '/includes/SecurityLogger.php';
        $securityLogger = new SecurityLogger($db);
        // TODO: Update calculateLatency to accept siteId
        $latencyData = $securityLogger->calculateLatency(null, 3600); // Última hora
        $globalLatency = $latencyData ? $latencyData['p99'] : null;
        $avgLatency = $latencyData ? $latencyData['avg'] : null;
        
        // IPs bloqueados ativos (Global ou Filtrado?)
        // Bloqueios são geralmente globais, mas podemos filtrar se o bloqueio foi originado de um site específico
        // A tabela blocked_ips não tem site_id nativamente no schema original, assumindo global por enquanto
        // Se quiser filtrar, precisaria adicionar site_id em blocked_ips
        $stmt = $db->query("SELECT COUNT(*) as total FROM v_safenode_active_blocks");
        $activeBlocks = $stmt->fetch();
        $stats['active_blocks'] = $activeBlocks['total'] ?? 0;
        
        // Verificar se há sites configurados do usuário logado
        $userId = $_SESSION['safenode_user_id'] ?? null;
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM safenode_sites WHERE is_active = 1 AND user_id = ?");
        $stmt->execute([$userId]);
        $sitesResult = $stmt->fetch();
        $hasSites = ($sitesResult['total'] ?? 0) > 0;
        
        // Últimos logs
        $stmt = $db->query("SELECT * FROM safenode_security_logs WHERE 1=1 $siteFilterAnd ORDER BY created_at DESC LIMIT 10");
        $recentLogs = $stmt->fetchAll();
        
        // Top IPs bloqueados
        // Recalculando view para filtrar
        $sqlTopIPs = "SELECT ip_address, count(0) AS block_count, max(created_at) AS last_blocked, 
                      substring_index(group_concat(distinct threat_type order by threat_type ASC separator ','),',',10) AS threat_types 
                      FROM safenode_security_logs 
                      WHERE action_taken = 'blocked' 
                      AND created_at >= current_timestamp() - interval 7 day 
                      $siteFilterAnd
                      GROUP BY ip_address 
                      ORDER BY count(0) DESC LIMIT 5";
        $stmt = $db->query($sqlTopIPs);
$topBlockedIPs = $stmt->fetchAll();

        $stmt = $db->prepare("SELECT 
            COALESCE(country_code, '??') as country_code,
            COUNT(*) as total_requests,
            SUM(CASE WHEN action_taken = 'blocked' THEN 1 ELSE 0 END) as blocked_requests
            FROM safenode_security_logs
            WHERE country_code IS NOT NULL $siteFilterAnd
              AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY country_code
            ORDER BY total_requests DESC
            LIMIT 5");
        $stmt->execute();
        $topCountries = $stmt->fetchAll();

        // Incidentes recentes
        $stmt = $db->query("SELECT i.*, s.domain AS site_domain 
                            FROM safenode_incidents i
                            LEFT JOIN safenode_sites s ON s.id = i.site_id
                            WHERE 1=1 " . ($currentSiteId > 0 ? " AND i.site_id = $currentSiteId " : "") . "
                            ORDER BY i.last_seen DESC
                            LIMIT 5");
        $recentIncidents = $stmt->fetchAll();

        // Alertas recentes (Alertas geralmente não tem site_id na tabela base, ver schema)
        // Assumindo global
        $stmt = $db->query("SELECT * FROM safenode_alerts ORDER BY created_at DESC LIMIT 5");
        $recentAlerts = $stmt->fetchAll();
        
        // Dados para gráfico de linha (últimas 24 horas por hora)
        $stmt = $db->query("SELECT 
            HOUR(created_at) as hour,
            COUNT(*) as requests,
            SUM(CASE WHEN action_taken = 'blocked' THEN 1 ELSE 0 END) as blocked
            FROM safenode_security_logs 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) $siteFilterAnd
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
        
        // Checar se é necessário enviar alerta de volume de ameaças
        SafeNodeAlert::checkThreshold($db);

    } catch (PDOException $e) {
        $recentLogs = [];
        $topBlockedIPs = [];
        $recentIncidents = [];
        $recentAlerts = [];
        $yesterdayStats = ['total_requests' => 0, 'blocked_requests' => 0, 'unique_ips' => 0];
        $hourlyStats = [];
        error_log("SafeNode Stats Error: " . $e->getMessage());
    }
} else {
    $recentLogs = [];
    $topBlockedIPs = [];
    $recentIncidents = [];
    $recentAlerts = [];
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

        /* Glass Components Melhorados */
        .glass-panel {
            background: rgba(24, 24, 27, 0.6);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .glass-card {
            background: linear-gradient(180deg, rgba(39, 39, 42, 0.5) 0%, rgba(24, 24, 27, 0.5) 100%);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.05), inset 0 1px 0 rgba(255, 255, 255, 0.1);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .glass-card:hover {
            background: linear-gradient(180deg, rgba(39, 39, 42, 0.7) 0%, rgba(24, 24, 27, 0.7) 100%);
            border-color: rgba(255, 255, 255, 0.12);
        }

        /* Grid Pattern */
        .grid-pattern {
            background-image: 
                linear-gradient(rgba(255, 255, 255, 0.02) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.02) 1px, transparent 1px);
            background-size: 20px 20px;
        }

        /* Badge Moderno */
        .modern-badge {
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2), inset 0 1px 0 rgba(255, 255, 255, 0.1);
            transition: all 0.3s;
        }
        .modern-badge:hover {
            border-color: rgba(255, 255, 255, 0.15);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.15);
        }

        /* Stat Card Hover */
        .stat-card-hover {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .stat-card-hover:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4), 0 0 20px rgba(59, 130, 246, 0.1);
        }

        /* Tabela Melhorada */
        .table-row {
            transition: all 0.2s;
        }
        .table-row:hover {
            background: rgba(255, 255, 255, 0.03) !important;
            transform: translateX(2px);
        }

        /* Animações */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in {
            animation: fadeIn 0.5s ease-out;
        }

        /* Depth Shadow */
        .depth-shadow {
            box-shadow: 
                0 10px 30px rgba(0, 0, 0, 0.5),
                0 0 0 1px rgba(255, 255, 255, 0.05),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
        }

        /* Status Pulse */
        .status-pulse {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        /* Alpine.js x-cloak */
        [x-cloak] { display: none !important; }
    </style>
</head>
<body x-data="{ notificationsOpen: false }" class="bg-black text-zinc-200 font-sans h-full overflow-hidden flex selection:bg-blue-500/30">

    <!-- Sidebar com Seletor de Site -->
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col h-full relative overflow-hidden bg-black">
        <!-- Header -->
        <header class="h-16 border-b border-white/5 bg-black/50 backdrop-blur-xl sticky top-0 z-40 px-6 flex items-center justify-between">
                <div class="flex items-center gap-4 md:hidden">
                <button class="text-zinc-400 hover:text-white transition-colors" data-sidebar-toggle>
                    <i data-lucide="menu" class="w-6 h-6"></i>
                </button>
                <span class="font-bold text-lg text-white">SafeNode</span>
            </div>

            <div class="hidden md:flex items-center gap-3">
                <div class="w-0.5 h-6 bg-gradient-to-b from-blue-500 to-purple-500 rounded-full"></div>
                <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg modern-badge text-xs font-bold <?php echo $hasSites ? 'bg-emerald-500/15 text-emerald-400 border-emerald-500/30' : 'bg-amber-500/15 text-amber-400 border-amber-500/30'; ?>">
                    <span class="w-2 h-2 rounded-full <?php echo $hasSites ? 'bg-emerald-500' : 'bg-amber-500'; ?> status-pulse shadow-lg"></span>
                    Sistema <?php echo $hasSites ? 'Ativo' : 'Inativo'; ?>
                </div>
                <div class="h-4 w-px bg-white/10"></div>
                <div class="text-xs text-zinc-400 font-mono font-semibold">
                    <?php echo htmlspecialchars($_SESSION['view_site_name'] ?? 'Visão Global'); ?>
                </div>
                <?php if ($currentSiteId > 0 && $selectedSite): ?>
                <div class="h-4 w-px bg-white/10"></div>
                <form method="POST">
                    <input type="hidden" name="toggle_under_attack" value="1">
                    <?php $underAttack = $selectedSite['security_level'] === 'under_attack'; ?>
                    <button type="submit" class="modern-badge inline-flex items-center gap-2 px-3 py-1.5 text-xs font-bold rounded-lg transition-all <?php echo $underAttack ? 'bg-red-500/15 text-red-400 border-red-500/30 hover:bg-red-500/25' : 'bg-zinc-900/60 text-zinc-300 border-white/10 hover:border-white/20'; ?>">
                        <span class="w-2 h-2 rounded-full <?php echo $underAttack ? 'bg-red-400 status-pulse shadow-lg shadow-red-400/50' : 'bg-zinc-500'; ?>"></span>
                        <?php echo $underAttack ? 'Sob Ataque ATIVO' : 'Sob Ataque DESLIGADO'; ?>
                    </button>
                </form>
                <?php endif; ?>
            </div>

            <div class="flex items-center gap-3">
                <button onclick="location.reload()" class="p-2.5 text-zinc-400 hover:text-white hover:bg-white/5 rounded-lg transition-all border border-transparent hover:border-white/10" title="Atualizar">
                    <i data-lucide="refresh-cw" class="w-5 h-5"></i>
                </button>
                <div class="relative">
                    <button @click="notificationsOpen = !notificationsOpen" class="p-2.5 text-zinc-400 hover:text-white hover:bg-white/5 rounded-lg transition-all relative border border-transparent hover:border-white/10">
                        <i data-lucide="bell" class="w-5 h-5"></i>
                        <span class="absolute top-1.5 right-1.5 w-2.5 h-2.5 bg-red-500 rounded-full border-2 border-black status-pulse shadow-lg shadow-red-500/50"></span>
                    </button>
                </div>
                <button onclick="window.location.href='profile.php'" class="hidden md:flex items-center gap-2 px-3 py-2 text-zinc-400 hover:text-white hover:bg-white/5 rounded-lg transition-all border border-transparent hover:border-white/10">
                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-600 to-purple-600 flex items-center justify-center text-white font-black text-xs shadow-lg border-2 border-white/10">
                        <?php echo strtoupper(substr($_SESSION['safenode_username'] ?? 'U', 0, 1)); ?>
                    </div>
                    <span class="text-sm font-bold hidden lg:block"><?php echo htmlspecialchars($_SESSION['safenode_username'] ?? 'Admin'); ?></span>
                </button>
            </div>
        </header>

        <!-- Scrollable Content -->
        <div class="flex-1 overflow-y-auto p-6 md:p-8 z-10 scroll-smooth">
            <div class="max-w-7xl mx-auto space-y-8">
                <?php if (!empty($dashboardFlash)): ?>
                <div class="rounded-xl p-4 <?php echo $dashboardFlashType === 'warning' ? 'bg-amber-500/10 border border-amber-500/30 text-amber-200' : ($dashboardFlashType === 'error' ? 'bg-red-500/10 border border-red-500/30 text-red-200' : 'bg-emerald-500/10 border border-emerald-500/30 text-emerald-200'); ?> flex items-center gap-3 animate-fade-in shadow-lg">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 rounded-lg <?php echo $dashboardFlashType === 'warning' ? 'bg-amber-500/20 border border-amber-500/30' : ($dashboardFlashType === 'error' ? 'bg-red-500/20 border border-red-500/30' : 'bg-emerald-500/20 border border-emerald-500/30'); ?> flex items-center justify-center">
                            <i data-lucide="<?php echo $dashboardFlashType === 'error' ? 'alert-triangle' : ($dashboardFlashType === 'warning' ? 'shield' : 'check-circle'); ?>" class="w-5 h-5"></i>
                    </div>
                    </div>
                    <p class="flex-1 font-bold"><?php echo htmlspecialchars($dashboardFlash); ?></p>
                </div>
                <?php endif; ?>
                
                <!-- Banner de Configuração (se não houver sites) - Redesign -->
                <?php if (!$hasSites): ?>
                <div class="rounded-xl bg-gradient-to-r from-amber-500/10 to-orange-500/10 border border-amber-500/30 p-6 animate-fade-in relative overflow-hidden depth-shadow">
                    <!-- Grid pattern -->
                    <div class="absolute inset-0 grid-pattern opacity-20"></div>
                    
                    <!-- Decoração de fundo -->
                    <div class="absolute top-0 right-0 w-40 h-40 bg-amber-500/5 rounded-full blur-3xl"></div>
                    
                    <div class="relative z-10">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0">
                                <div class="w-12 h-12 rounded-xl bg-amber-500/20 border border-amber-500/30 flex items-center justify-center">
                                <i data-lucide="alert-circle" class="w-6 h-6 text-amber-400"></i>
                            </div>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-bold text-white mb-2 flex items-center gap-2">
                                Configure seu primeiro site
                            </h3>
                                <p class="text-zinc-400 text-sm mb-4 font-medium">
                                Para ativar todas as funcionalidades do SafeNode, você precisa configurar pelo menos um site. 
                                Configure seu site agora para começar a monitorar e proteger seu tráfego.
                            </p>
                                <a href="sites.php" class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 text-white rounded-xl text-sm font-bold transition-all shadow-lg shadow-amber-500/30">
                                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                                Configurar Site Agora
                            </a>
                        </div>
                            <button onclick="this.parentElement.parentElement.remove()" class="flex-shrink-0 p-2 text-zinc-400 hover:text-white hover:bg-white/5 rounded-lg transition-all">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Welcome & Actions - Redesign -->
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 animate-fade-in">
                    <div class="flex items-center gap-3">
                        <div class="w-0.5 h-8 bg-gradient-to-b from-blue-500 to-purple-500 rounded-full"></div>
                    <div>
                        <h1 class="text-2xl font-bold text-white tracking-tight mb-1">
                            <?php echo $currentSiteId > 0 ? 'Visão Geral: ' . htmlspecialchars($_SESSION['view_site_name']) : 'Visão Geral Global'; ?>
                        </h1>
                            <p class="text-zinc-400 text-sm font-medium">Monitoramento de visitas e segurança em tempo real.</p>
                        </div>
                    </div>
                    <div class="flex gap-3 relative">
                        <?php if (!$hasSites): ?>
                        <div class="absolute inset-0 -m-2 bg-zinc-900/50 rounded-lg z-20 pointer-events-none"></div>
                        <?php endif; ?>
                        <a href="logs.php" class="flex items-center gap-2 px-5 py-2.5 bg-white text-black rounded-xl text-sm font-bold hover:bg-zinc-200 transition-all shadow-lg shadow-white/10 relative <?php echo !$hasSites ? 'opacity-40 blur-sm z-0 pointer-events-none cursor-not-allowed' : 'z-10'; ?>">
                            <i data-lucide="file-text" class="w-4 h-4"></i>
                            Logs
                        </a>
                    </div>
                </div>

                <!-- Novos Stats (Preview Style) - Redesign -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Total de Requisições -->
                    <a href="logs.php" class="block p-5 rounded-xl bg-zinc-900/40 border border-zinc-800/50 hover:border-emerald-500/30 hover:bg-zinc-900/60 transition-all stat-card-hover group relative overflow-hidden <?php echo !$hasSites ? 'pointer-events-none opacity-40 blur-sm' : ''; ?>">
                        <?php if (!$hasSites): ?>
                        <div class="absolute inset-0 bg-zinc-900/50 rounded-xl z-10 pointer-events-none"></div>
                        <?php endif; ?>
                        <!-- Grid pattern -->
                        <div class="absolute inset-0 grid-pattern opacity-10"></div>
                        <!-- Decoração de fundo -->
                        <div class="absolute top-0 right-0 w-32 h-32 bg-emerald-500/5 rounded-full blur-3xl"></div>
                        <div class="relative z-10">
                            <div class="flex justify-between items-start mb-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-10 h-10 rounded-xl bg-emerald-500/15 border border-emerald-500/30 flex items-center justify-center">
                                        <i data-lucide="activity" class="w-5 h-5 text-emerald-400"></i>
                                    </div>
                                    <div class="text-xs text-zinc-400 uppercase font-bold">Visitas / Requisições</div>
                                </div>
                                <span class="modern-badge text-[10px] text-emerald-400 bg-emerald-900/20 px-2 py-1 rounded-lg border border-emerald-900/30 flex items-center gap-1 font-bold" data-stat="requests-change">
                                <i data-lucide="arrow-up-right" class="w-3 h-3"></i> <?php echo abs($requestsChange); ?>%
                            </span>
                        </div>
                            <div class="text-3xl font-black text-white group-hover:text-emerald-400 transition-colors mb-2" data-stat="total-requests">
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
                            <div class="text-xs text-zinc-500 mt-1 font-medium">Últimas 24 horas · clique para ver detalhes</div>
                        </div>
                    </a>
                    
                    <a href="logs.php?<?php echo http_build_query(['action' => 'blocked']); ?>" class="block p-5 rounded-xl bg-zinc-900/40 border border-zinc-800/50 hover:border-red-500/30 hover:bg-zinc-900/60 transition-all stat-card-hover group relative overflow-hidden <?php echo !$hasSites ? 'pointer-events-none opacity-40 blur-sm' : ''; ?>">
                        <?php if (!$hasSites): ?>
                        <div class="absolute inset-0 bg-zinc-900/50 rounded-xl z-10 pointer-events-none"></div>
                        <?php endif; ?>
                        <!-- Grid pattern -->
                        <div class="absolute inset-0 grid-pattern opacity-10"></div>
                        <!-- Decoração de fundo -->
                        <div class="absolute top-0 right-0 w-32 h-32 bg-red-500/5 rounded-full blur-3xl"></div>
                        <div class="relative z-10">
                            <div class="flex justify-between items-start mb-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-10 h-10 rounded-xl bg-red-500/15 border border-red-500/30 flex items-center justify-center">
                                        <i data-lucide="shield-alert" class="w-5 h-5 text-red-400"></i>
                                    </div>
                                    <div class="text-xs text-zinc-400 uppercase font-bold">Ameaças Mitigadas</div>
                                </div>
                                <span class="modern-badge text-[10px] text-red-400 bg-red-900/20 px-2 py-1 rounded-lg border border-red-900/30 flex items-center gap-1 font-bold">
                                <i data-lucide="shield-alert" class="w-3 h-3"></i> <?php echo number_format($blocked24h); ?>
                            </span>
                        </div>
                            <div class="text-3xl font-black text-white group-hover:text-red-400 transition-colors mb-2" data-stat="blocked-threats">
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
                            <div class="text-xs text-zinc-500 mt-1 font-medium">Bloqueado automaticamente por IA · clique para ver logs</div>
                        </div>
                    </a>
                    
                    <?php if ($hasSites): ?>
                    <a href="logs.php" class="block p-5 rounded-xl bg-zinc-900/40 border border-zinc-800/50 hover:border-blue-500/30 hover:bg-zinc-900/60 transition-all stat-card-hover group relative overflow-hidden">
                        <!-- Grid pattern -->
                        <div class="absolute inset-0 grid-pattern opacity-10"></div>
                        <!-- Decoração de fundo -->
                        <div class="absolute top-0 right-0 w-32 h-32 bg-blue-500/5 rounded-full blur-3xl"></div>
                        <div class="relative z-10">
                            <div class="flex justify-between items-start mb-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-10 h-10 rounded-xl bg-blue-500/15 border border-blue-500/30 flex items-center justify-center">
                                        <i data-lucide="zap" class="w-5 h-5 text-blue-400"></i>
                                    </div>
                                    <div class="text-xs text-zinc-400 uppercase font-bold">Latência Global</div>
                                </div>
                            <?php if ($globalLatency !== null && $avgLatency !== null): 
                                $latencyChange = $avgLatency < ($globalLatency * 0.9) ? -round(($globalLatency - $avgLatency) / $globalLatency * 100) : 0;
                            ?>
                                <span class="modern-badge text-[10px] <?php echo $latencyChange < 0 ? 'text-green-400 bg-green-900/20 border-green-900/30' : 'text-blue-400 bg-blue-900/20 border-blue-900/30'; ?> px-2 py-1 rounded-lg border flex items-center gap-1 font-bold">
                                <i data-lucide="zap" class="w-3 h-3"></i> <?php echo $latencyChange < 0 ? $latencyChange . 'ms' : '--ms'; ?>
                            </span>
                            <?php else: ?>
                                <span class="modern-badge text-[10px] text-blue-400 bg-blue-900/20 px-2 py-1 rounded-lg border border-blue-900/30 flex items-center gap-1 font-bold">
                                <i data-lucide="zap" class="w-3 h-3"></i> --
                            </span>
                            <?php endif; ?>
                        </div>
                            <div class="text-3xl font-black text-white group-hover:text-blue-400 transition-colors mb-2" data-stat="latency">
                            <?php echo $globalLatency !== null ? number_format($globalLatency, 0) . 'ms' : '--ms'; ?>
                        </div>
                            <div class="text-xs text-zinc-500 mt-1 font-medium">Tempo de Resposta P99 · clique para ver requisições recentes</div>
                        </div>
                    </a>
                    <?php else: ?>
                    <div class="p-5 rounded-xl bg-zinc-900/40 border border-zinc-800/50 transition-colors group opacity-40 blur-sm relative">
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

                <!-- Stats Grid (Cards Adicionais) - Redesign -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Unique IPs -->
                    <div class="glass-card p-6 rounded-xl relative overflow-hidden group hover:border-purple-500/30 transition-all duration-300 animate-fade-in stat-card-hover <?php echo !$hasSites ? 'opacity-40 blur-sm' : ''; ?>" style="animation-delay: 0.1s;">
                        <?php if (!$hasSites): ?>
                        <div class="absolute inset-0 bg-zinc-900/50 rounded-xl z-10 pointer-events-none"></div>
                        <?php endif; ?>
                        <!-- Grid pattern -->
                        <div class="absolute inset-0 grid-pattern opacity-10"></div>
                        <!-- Decoração de fundo -->
                        <div class="absolute -right-10 -top-10 w-32 h-32 bg-purple-500/10 rounded-full blur-3xl group-hover:bg-purple-500/20 transition-all"></div>
                        <div class="flex justify-between items-start mb-4 relative z-10">
                            <div class="w-12 h-12 rounded-xl bg-purple-500/15 border border-purple-500/30 flex items-center justify-center">
                                <i data-lucide="globe" class="w-6 h-6 text-purple-400"></i>
                            </div>
                            <?php if ($ipsChange != 0): ?>
                                <span class="modern-badge text-xs font-bold px-3 py-1.5 rounded-lg border <?php echo $ipsChange > 0 ? 'bg-emerald-500/15 text-emerald-400 border-emerald-500/30' : 'bg-zinc-800/60 text-zinc-400 border-white/10'; ?>">
                                    <?php echo $ipsChange > 0 ? '+' : ''; ?><?php echo $ipsChange; ?>%
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="relative z-10">
                            <h3 class="text-zinc-400 text-xs font-bold uppercase tracking-wider mb-2">Visitantes Únicos</h3>
                            <p class="text-3xl font-black text-white font-mono mb-1" data-stat="unique-visitors"><?php echo number_format($stats['unique_ips_today']); ?></p>
                            <p class="text-xs text-zinc-500 font-medium">IPs únicos hoje</p>
                        </div>
                    </div>

                    <!-- Active Rules -->
                    <div class="glass-card p-6 rounded-xl relative overflow-hidden group hover:border-amber-500/30 transition-all duration-300 animate-fade-in stat-card-hover <?php echo !$hasSites ? 'opacity-40 blur-sm' : ''; ?>" style="animation-delay: 0.2s;">
                        <?php if (!$hasSites): ?>
                        <div class="absolute inset-0 bg-zinc-900/50 rounded-xl z-10 pointer-events-none"></div>
                        <?php endif; ?>
                        <!-- Grid pattern -->
                        <div class="absolute inset-0 grid-pattern opacity-10"></div>
                        <!-- Decoração de fundo -->
                        <div class="absolute -right-10 -top-10 w-32 h-32 bg-amber-500/10 rounded-full blur-3xl group-hover:bg-amber-500/20 transition-all"></div>
                        <div class="flex justify-between items-start mb-4 relative z-10">
                            <div class="w-12 h-12 rounded-xl bg-amber-500/15 border border-amber-500/30 flex items-center justify-center">
                                <i data-lucide="lock" class="w-6 h-6 text-amber-400"></i>
                            </div>
                            <span class="modern-badge text-xs font-bold px-3 py-1.5 rounded-lg bg-amber-500/15 text-amber-400 border border-amber-500/30">
                                <i data-lucide="check-circle" class="w-3.5 h-3.5 inline mr-1"></i>
                                Ativo
                            </span>
                        </div>
                        <div class="relative z-10">
                            <h3 class="text-zinc-400 text-xs font-bold uppercase tracking-wider mb-2">Regras Ativas</h3>
                            <p class="text-3xl font-black text-white font-mono mb-1" data-stat="active-blocks"><?php echo number_format($stats['active_blocks']); ?></p>
                            <p class="text-xs text-zinc-500 font-medium">IPs na lista negra</p>
                        </div>
                    </div>
                </div>

                <!-- Top Countries - Redesign -->
                <div class="glass-card rounded-xl p-6 relative overflow-hidden animate-fade-in depth-shadow <?php echo empty($topCountries) ? 'opacity-70' : ''; ?>" style="animation-delay: 0.3s">
                    <!-- Grid pattern -->
                    <div class="absolute inset-0 grid-pattern opacity-20"></div>
                    
                    <!-- Decoração de fundo -->
                    <div class="absolute top-0 right-0 w-40 h-40 bg-blue-500/5 rounded-full blur-3xl"></div>
                    
                    <div class="relative z-10">
                    <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-xl bg-blue-500/15 border border-blue-500/30 flex items-center justify-center">
                                    <i data-lucide="map" class="w-6 h-6 text-blue-400"></i>
                                </div>
                        <div>
                            <h3 class="font-bold text-white text-lg">Top Países (7 dias)</h3>
                                    <p class="text-xs text-zinc-400 mt-0.5 font-medium">Tráfego classificado por país de origem<?php echo $currentSiteId > 0 ? ' · site selecionado' : ' · todos os sites'; ?></p>
                                </div>
                        </div>
                    </div>
                    <?php if (empty($topCountries)): ?>
                            <div class="text-center py-8">
                                <div class="w-16 h-16 bg-zinc-900/60 rounded-xl border border-white/5 flex items-center justify-center mx-auto mb-4">
                                    <i data-lucide="map" class="w-8 h-8 text-zinc-500"></i>
                                </div>
                                <p class="text-sm text-zinc-400 font-bold mb-1">Sem dados suficientes</p>
                                <p class="text-xs text-zinc-500 font-medium">Os dados aparecerão aqui quando houver tráfego</p>
                            </div>
                    <?php else: ?>
                        <div class="space-y-4" data-stat="top-countries">
                            <?php foreach ($topCountries as $country): 
                                $code = strtoupper($country['country_code'] ?? '??');
                                $blocked = (int)($country['blocked_requests'] ?? 0);
                                $total = (int)($country['total_requests'] ?? 0);
                                $blockedPercent = $total > 0 ? round(($blocked / $total) * 100) : 0;
                            ?>
                                <div class="p-3 rounded-xl bg-zinc-900/30 border border-white/5 hover:border-blue-500/30 transition-all">
                                    <div class="flex items-center justify-between text-sm mb-2">
                                    <div class="flex items-center gap-2">
                                            <i data-lucide="flag" class="w-4 h-4 text-blue-400"></i>
                                            <span class="font-bold text-white"><?php echo $code; ?></span>
                                            <span class="modern-badge text-xs px-2 py-0.5 rounded-lg <?php echo $blocked > 0 ? 'bg-red-500/15 text-red-400 border-red-500/30' : 'bg-emerald-500/15 text-emerald-400 border-emerald-500/30'; ?> font-bold">
                                                <?php echo $blocked > 0 ? "{$blockedPercent}% bloqueado" : "Seguro"; ?>
                                            </span>
                                    </div>
                                        <div class="text-xs text-zinc-400 font-mono font-bold"><?php echo number_format($total); ?> req</div>
                                </div>
                                    <div class="w-full h-2 bg-zinc-900 rounded-full overflow-hidden border border-white/5">
                                        <div class="h-full bg-gradient-to-r from-blue-500 to-blue-600 rounded-full shadow-lg" style="width: <?php echo min(100, $total > 0 ? ($total / max(1, $topCountries[0]['total_requests'])) * 100 : 0); ?>%"></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    </div>
                </div>

                <!-- Charts Area - Redesign -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 animate-fade-in" style="animation-delay: 0.4s;">
                    <!-- Traffic Chart with World Map -->
                    <div class="lg:col-span-2 glass-card p-6 rounded-xl relative overflow-hidden depth-shadow <?php echo !$hasSites ? 'opacity-40 blur-sm' : ''; ?>">
                        <?php if (!$hasSites): ?>
                        <div class="absolute inset-0 bg-zinc-900/50 rounded-xl z-20 pointer-events-none"></div>
                        <?php endif; ?>
                        <!-- Grid pattern -->
                        <div class="absolute inset-0 grid-pattern opacity-20"></div>
                        
                        <!-- Decoração de fundo -->
                        <div class="absolute top-0 right-0 w-40 h-40 bg-blue-500/5 rounded-full blur-3xl"></div>
                        
                        <div class="flex items-center justify-between mb-6 relative z-10">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-blue-500/15 border border-blue-500/30 flex items-center justify-center">
                                    <i data-lucide="trending-up" class="w-5 h-5 text-blue-400"></i>
                                </div>
                            <div>
                                <h3 class="font-bold text-white text-lg">Análise de Tráfego</h3>
                                    <p class="text-sm text-zinc-400 mt-0.5 font-medium">Últimas 24 horas</p>
                            </div>
                            </div>
                            <div class="flex gap-4 text-xs font-bold">
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full bg-blue-500 shadow-lg shadow-blue-500/50"></span>
                                    <span class="text-zinc-400">Total</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full bg-red-500 shadow-lg shadow-red-500/50"></span>
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

                    <!-- Threats Donut Enhanced - Redesign -->
                    <div class="glass-card p-6 rounded-xl flex flex-col relative overflow-hidden depth-shadow <?php echo !$hasSites ? 'opacity-40 blur-sm' : ''; ?>">
                        <?php if (!$hasSites): ?>
                        <div class="absolute inset-0 bg-zinc-900/50 rounded-xl z-20 pointer-events-none"></div>
                        <?php endif; ?>
                        <!-- Grid pattern -->
                        <div class="absolute inset-0 grid-pattern opacity-20"></div>
                        
                        <!-- Decoração de fundo -->
                        <div class="absolute -right-10 -top-10 w-32 h-32 bg-gradient-to-br from-red-500/10 to-purple-500/10 rounded-full blur-3xl"></div>
                        <div class="flex items-center gap-3 mb-4 relative z-10">
                            <div class="w-10 h-10 rounded-xl bg-red-500/15 border border-red-500/30 flex items-center justify-center">
                                <i data-lucide="shield-alert" class="w-5 h-5 text-red-400"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-white text-lg">Tipos de Ameaça</h3>
                                <p class="text-xs text-zinc-400 mt-0.5 font-medium">Distribuição por categoria</p>
                            </div>
                        </div>
                        
                        <div class="flex-1 relative min-h-[200px] flex items-center justify-center <?php echo !$hasSites ? 'z-0' : 'z-10'; ?>">
                            <canvas id="threatsChart"></canvas>
                            <!-- Center Stat -->
                            <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                                <span class="text-3xl font-bold text-white tracking-tight"><?php echo number_format($stats['blocked_today']); ?></span>
                                <span class="text-xs text-zinc-500 font-medium uppercase tracking-wider">Total</span>
                            </div>
                        </div>
                        <!-- Legend -->
                        <div class="grid grid-cols-2 gap-2 mt-4 text-xs relative z-10">
                            <div class="flex items-center gap-2 p-2 rounded-lg bg-zinc-900/30 border border-white/5">
                                <span class="w-2.5 h-2.5 rounded-full bg-red-500 shadow-lg shadow-red-500/50"></span>
                                <span class="text-zinc-400 font-bold">SQL</span>
                            </div>
                            <div class="flex items-center gap-2 p-2 rounded-lg bg-zinc-900/30 border border-white/5">
                                <span class="w-2.5 h-2.5 rounded-full bg-orange-500 shadow-lg shadow-orange-500/50"></span>
                                <span class="text-zinc-400 font-bold">XSS</span>
                            </div>
                            <div class="flex items-center gap-2 p-2 rounded-lg bg-zinc-900/30 border border-white/5">
                                <span class="w-2.5 h-2.5 rounded-full bg-amber-500 shadow-lg shadow-amber-500/50"></span>
                                <span class="text-zinc-400 font-bold">Brute</span>
                            </div>
                            <div class="flex items-center gap-2 p-2 rounded-lg bg-zinc-900/30 border border-white/5">
                                <span class="w-2.5 h-2.5 rounded-full bg-green-500 shadow-lg shadow-green-500/50"></span>
                                <span class="text-zinc-400 font-bold">Rate</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mapa de Tráfego, Registro de Eventos e Incidentes - Redesign -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 animate-fade-in" style="animation-delay: 0.5s">
                    <!-- Mapa de Tráfego em Tempo Real -->
                    <div class="lg:col-span-2 rounded-xl bg-[#050505] border border-zinc-800/50 relative overflow-hidden flex flex-col depth-shadow <?php echo !$hasSites ? 'opacity-40 blur-sm' : ''; ?>">
                        <?php if (!$hasSites): ?>
                        <div class="absolute inset-0 bg-zinc-900/50 rounded-xl z-20 pointer-events-none"></div>
                        <?php endif; ?>
                        <div class="absolute inset-0 grid-pattern opacity-30"></div>
                        
                        <div class="p-4 border-b border-zinc-800/50 flex justify-between items-center relative z-10 bg-black/20 backdrop-blur-sm">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-green-500/15 border border-green-500/30 flex items-center justify-center">
                                    <i data-lucide="network" class="w-5 h-5 text-green-400"></i>
                                </div>
                                <div>
                                    <span class="text-xs font-bold text-zinc-300">Mapa de Tráfego em Tempo Real</span>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <div class="w-2 h-2 rounded-full bg-green-500 status-pulse shadow-lg shadow-green-500/50"></div>
                                        <span class="text-[10px] text-zinc-500 font-medium">Ativo</span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <span class="modern-badge text-[10px] px-3 py-1.5 rounded-lg bg-blue-500/15 text-blue-400 border border-blue-500/30 font-bold">1H</span>
                                <span class="text-[10px] px-3 py-1.5 rounded-lg bg-zinc-900/60 text-zinc-600 hover:text-zinc-400 hover:bg-zinc-800/60 cursor-pointer border border-white/5 transition-all font-bold">24H</span>
                            </div>
                        </div>

                        <div class="flex-1 relative flex items-center justify-center min-h-[300px]">
                            <!-- Central Node -->
                            <div class="relative z-10">
                                <div class="w-20 h-20 rounded-full bg-zinc-900 border-2 border-green-500/30 flex items-center justify-center shadow-[0_0_40px_rgba(16,185,129,0.2)]">
                                    <div class="w-14 h-14 rounded-full bg-green-500/10 border border-green-500/20 flex items-center justify-center">
                                        <i data-lucide="server" class="w-8 h-8 text-green-400"></i>
                                    </div>
                                </div>
                                <!-- Ripples -->
                                <div class="absolute inset-0 -m-8 border-2 border-green-500/20 rounded-full animate-[ping_3s_linear_infinite]"></div>
                                <div class="absolute inset-0 -m-16 border border-green-500/10 rounded-full animate-[ping_3s_linear_infinite]" style="animation-delay: 1s"></div>
                                <div class="absolute inset-0 -m-24 border border-green-500/5 rounded-full animate-[ping_3s_linear_infinite]" style="animation-delay: 2s"></div>
                            </div>

                            <!-- Incoming Traffic Particles -->
                            <div class="absolute inset-0 overflow-hidden z-0">
                                <div class="absolute top-1/4 left-10 w-2 h-2 bg-white rounded-full shadow-lg shadow-white/50 animate-[traffic-flow_2s_linear_infinite]"></div>
                                <div class="absolute bottom-1/3 right-10 w-2 h-2 bg-white rounded-full shadow-lg shadow-white/50 animate-[traffic-flow_3s_linear_infinite_reverse]"></div>
                                <div class="absolute top-10 right-1/3 w-2 h-2 bg-red-500 rounded-full shadow-lg shadow-red-500/50 animate-[traffic-blocked_2s_linear_infinite]"></div>
                                <div class="absolute top-1/2 left-1/4 w-1.5 h-1.5 bg-blue-500 rounded-full shadow-lg shadow-blue-500/50 animate-[traffic-flow_2.5s_linear_infinite]"></div>
                                <div class="absolute bottom-1/4 left-1/3 w-1.5 h-1.5 bg-purple-500 rounded-full shadow-lg shadow-purple-500/50 animate-[traffic-flow_3.5s_linear_infinite_reverse]"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Registro de Eventos - Redesign -->
                    <div class="lg:col-span-1 rounded-xl bg-zinc-950 border border-zinc-800/50 flex flex-col overflow-hidden relative depth-shadow <?php echo !$hasSites ? 'opacity-40 blur-sm' : ''; ?>">
                        <?php if (!$hasSites): ?>
                        <div class="absolute inset-0 bg-zinc-900/50 rounded-xl z-20 pointer-events-none"></div>
                        <?php endif; ?>
                        <!-- Grid pattern -->
                        <div class="absolute inset-0 grid-pattern opacity-20"></div>
                        
                        <div class="p-4 border-b border-zinc-800/50 bg-zinc-900/30 relative z-10">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-lg bg-purple-500/15 border border-purple-500/30 flex items-center justify-center">
                                    <i data-lucide="activity" class="w-4 h-4 text-purple-400"></i>
                        </div>
                                <span class="text-xs font-bold text-zinc-300">Registro de Eventos</span>
                            </div>
                        </div>
                        <div class="flex-1 p-4 space-y-2 overflow-y-auto font-mono text-[10px] max-h-[400px] relative z-10" data-stat="event-logs">
                            <?php 
                            $eventLogs = array_slice($recentLogs, 0, 5);
                            if (empty($eventLogs)): ?>
                                <div class="text-center py-8">
                                    <div class="w-12 h-12 bg-zinc-900/60 rounded-xl border border-white/5 flex items-center justify-center mx-auto mb-4">
                                        <i data-lucide="activity" class="w-6 h-6 text-zinc-500"></i>
                                    </div>
                                    <p class="text-xs text-zinc-400 font-bold mb-1">Nenhum evento recente</p>
                                    <p class="text-[10px] text-zinc-500 font-medium">Os eventos aparecerão aqui</p>
                                </div>
                            <?php else: ?>
                                <?php 
                                $opacity = ['opacity-50', 'opacity-70', '', 'opacity-80', ''];
                                $i = 0;
                                foreach ($eventLogs as $log): 
                                    $isCritical = ($log['threat_score'] ?? 0) >= 70 || stripos($log['threat_type'] ?? '', 'ddos') !== false;
                                    $showMitigated = $isCritical && $log['action_taken'] === 'blocked';
                                ?>
                                    <div class="flex gap-2 p-2 rounded-lg bg-zinc-900/30 border border-white/5 hover:border-<?php echo $showMitigated ? 'red' : ($log['action_taken'] === 'blocked' ? 'red' : 'green'); ?>-500/30 transition-all <?php echo $opacity[$i] ?? ''; ?> <?php echo $showMitigated ? 'bg-red-500/5' : ''; ?>">
                                        <div class="flex items-center gap-1.5 flex-shrink-0">
                                            <i data-lucide="clock" class="w-3 h-3 text-zinc-500"></i>
                                            <span class="text-zinc-500 font-bold"><?php echo date('H:i:s', strtotime($log['created_at'])); ?></span>
                                        </div>
                                        <span class="modern-badge px-2 py-0.5 rounded-lg text-[9px] font-bold <?php echo $showMitigated ? 'bg-red-500/15 text-red-400 border-red-500/30' : ($log['action_taken'] === 'blocked' ? 'bg-red-500/15 text-red-400 border-red-500/30' : 'bg-emerald-500/15 text-emerald-400 border-emerald-500/30'); ?>">
                                            <?php echo $showMitigated ? 'MITIGADO' : ($log['action_taken'] === 'blocked' ? 'BLOQUEAR' : 'PERMITIR'); ?>
                                        </span>
                                        <span class="<?php echo $showMitigated ? 'text-white font-bold' : 'text-zinc-400 font-medium'; ?> truncate">
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

                <!-- Incidentes Recentes e Top IPs - Redesign -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 animate-fade-in" style="animation-delay: 0.6s">
                    <!-- Incidentes Recentes -->
                    <div class="lg:col-span-2 glass-card rounded-xl overflow-hidden relative depth-shadow">
                        <!-- Grid pattern -->
                        <div class="absolute inset-0 grid-pattern opacity-20"></div>
                        
                        <!-- Decoração de fundo -->
                        <div class="absolute top-0 right-0 w-40 h-40 bg-orange-500/5 rounded-full blur-3xl"></div>
                        
                        <div class="p-6 border-b border-white/5 bg-zinc-900/30 flex items-center justify-between relative z-10">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-xl bg-orange-500/15 border border-orange-500/30 flex items-center justify-center">
                                    <i data-lucide="alert-triangle" class="w-6 h-6 text-orange-400"></i>
                                </div>
                            <div>
                                <h3 class="font-bold text-white text-lg">Incidentes Recentes</h3>
                                    <p class="text-xs text-zinc-400 mt-0.5 font-medium">Agrupamento de múltiplos eventos por IP/tipo</p>
                            </div>
                            </div>
                            <a href="incidents.php" class="modern-badge text-xs text-blue-400 hover:text-blue-300 font-bold flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-500/15 border border-blue-500/30 hover:bg-blue-500/25 transition-all">
                                Ver todos
                                <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                            </a>
                        </div>
                        <div class="overflow-x-auto relative z-10">
                            <table class="w-full text-sm text-left">
                                <thead class="bg-zinc-900/50 text-zinc-400 font-bold uppercase text-xs tracking-wider border-b border-white/5">
                                    <tr>
                                        <th class="px-6 py-4">Status</th>
                                        <th class="px-6 py-4">IP</th>
                                        <th class="px-6 py-4">Tipo</th>
                                        <th class="px-6 py-4">Site</th>
                                        <th class="px-6 py-4">Eventos</th>
                                        <th class="px-6 py-4">Críticos</th>
                                        <th class="px-6 py-4">Último</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-white/5" data-stat="recent-incidents">
                                    <?php if (empty($recentIncidents)): ?>
                                        <tr>
                                            <td colspan="7" class="px-6 py-8 text-center">
                                                <div class="flex flex-col items-center gap-3">
                                                    <div class="w-12 h-12 bg-zinc-900/60 rounded-xl border border-white/5 flex items-center justify-center">
                                                        <i data-lucide="shield" class="w-6 h-6 text-emerald-400"></i>
                                                    </div>
                                                    <p class="text-xs text-zinc-400 font-bold">Nenhum incidente recente</p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($recentIncidents as $incident): ?>
                                            <tr class="table-row group">
                                                <td class="px-6 py-4">
                                                    <?php if ($incident['status'] === 'open'): ?>
                                                        <span class="modern-badge inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[10px] font-bold bg-red-500/15 text-red-400 border border-red-500/30">
                                                            <span class="w-2 h-2 rounded-full bg-red-400 status-pulse shadow-lg shadow-red-400/50"></span>
                                                            Aberto
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="modern-badge inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[10px] font-bold bg-zinc-800/60 text-zinc-400 border border-white/10">
                                                            <i data-lucide="check-circle" class="w-3.5 h-3.5"></i>
                                                            Resolvido
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <div class="flex items-center gap-2">
                                                        <i data-lucide="network" class="w-4 h-4 text-zinc-500"></i>
                                                        <span class="text-xs font-bold text-white font-mono"><?php echo htmlspecialchars($incident['ip_address']); ?></span>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <span class="modern-badge inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[10px] font-bold bg-zinc-800/60 text-zinc-300 border border-white/10">
                                                        <i data-lucide="shield-alert" class="w-3.5 h-3.5"></i>
                                                        <?php echo strtoupper(str_replace('_', ' ', htmlspecialchars($incident['threat_type'] ?? 'unknown'))); ?>
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <div class="flex items-center gap-2">
                                                        <i data-lucide="globe" class="w-4 h-4 text-zinc-500"></i>
                                                        <span class="text-[11px] text-zinc-400 font-mono font-medium"><?php echo htmlspecialchars($incident['site_domain'] ?? '-'); ?></span>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <div class="flex items-center gap-2">
                                                        <i data-lucide="activity" class="w-4 h-4 text-blue-400"></i>
                                                        <span class="text-sm text-zinc-200 font-bold"><?php echo (int)$incident['total_events']; ?></span>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <div class="flex items-center gap-2">
                                                        <i data-lucide="alert-triangle" class="w-4 h-4 <?php echo $incident['critical_events'] > 0 ? 'text-red-400' : 'text-zinc-500'; ?>"></i>
                                                        <span class="text-sm <?php echo $incident['critical_events'] > 0 ? 'text-red-400 font-bold' : 'text-zinc-400 font-semibold'; ?>">
                                                        <?php echo (int)$incident['critical_events']; ?>
                                                    </span>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <div class="flex items-center gap-2">
                                                        <i data-lucide="clock" class="w-4 h-4 text-zinc-500"></i>
                                                        <span class="text-[11px] text-zinc-400 font-mono font-semibold">
                                                        <?php echo date('d/m H:i', strtotime($incident['last_seen'])); ?>
                                                    </span>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Top IPs Bloqueados - Redesign -->
                    <div class="glass-card rounded-xl overflow-hidden relative depth-shadow">
                        <!-- Grid pattern -->
                        <div class="absolute inset-0 grid-pattern opacity-20"></div>
                        
                        <!-- Decoração de fundo -->
                        <div class="absolute top-0 right-0 w-40 h-40 bg-red-500/5 rounded-full blur-3xl"></div>
                        
                        <div class="p-6 border-b border-white/5 bg-zinc-900/30 flex items-center justify-between relative z-10">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-xl bg-red-500/15 border border-red-500/30 flex items-center justify-center">
                                    <i data-lucide="shield-x" class="w-6 h-6 text-red-400"></i>
                                </div>
                                <div>
                            <h3 class="font-bold text-white text-lg">Top IPs Bloqueados</h3>
                                    <p class="text-xs text-zinc-400 mt-0.5 font-medium">Últimos 7 dias</p>
                        </div>
                            </div>
                        </div>
                        <div class="p-4 space-y-3 relative z-10" data-stat="top-blocked-ips">
                            <?php if (empty($topBlockedIPs)): ?>
                                <div class="text-center py-8">
                                    <div class="w-12 h-12 bg-zinc-900/60 rounded-xl border border-white/5 flex items-center justify-center mx-auto mb-4">
                                        <i data-lucide="shield-check" class="w-6 h-6 text-emerald-400"></i>
                                    </div>
                                    <p class="text-xs text-zinc-400 font-bold mb-1">Nenhum IP bloqueado</p>
                                    <p class="text-[10px] text-zinc-500 font-medium">Os bloqueios aparecerão aqui</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($topBlockedIPs as $ip): ?>
                                    <div class="p-3 rounded-xl bg-zinc-900/30 border border-white/5 hover:border-red-500/30 transition-all">
                                        <div class="flex items-center justify-between mb-2">
                                            <div class="flex items-center gap-2">
                                                <i data-lucide="network" class="w-4 h-4 text-red-400"></i>
                                                <p class="text-sm font-mono text-white font-bold"><?php echo htmlspecialchars($ip['ip_address']); ?></p>
                                            </div>
                                            <p class="text-sm font-bold text-red-400"><?php echo (int)$ip['block_count']; ?>x</p>
                                        </div>
                                    <div class="flex items-center justify-between">
                                            <p class="text-[11px] text-zinc-500 font-medium">
                                                <?php echo htmlspecialchars($ip['threat_types']); ?>
                                            </p>
                                            <div class="flex items-center gap-1.5">
                                                <i data-lucide="clock" class="w-3 h-3 text-zinc-500"></i>
                                                <p class="text-[11px] text-zinc-500 font-medium">
                                                <?php echo date('d/m H:i', strtotime($ip['last_blocked'])); ?>
                                            </p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Recent Logs - Redesign -->
                <div class="glass-card rounded-xl overflow-hidden animate-fade-in relative depth-shadow <?php echo !$hasSites ? 'opacity-40 blur-sm' : ''; ?>" style="animation-delay: 0.7s">
                    <?php if (!$hasSites): ?>
                    <div class="absolute inset-0 bg-zinc-900/50 rounded-xl z-20 pointer-events-none"></div>
                    <?php endif; ?>
                    <!-- Grid pattern -->
                    <div class="absolute inset-0 grid-pattern opacity-20"></div>
                    
                    <!-- Decoração de fundo -->
                    <div class="absolute top-0 right-0 w-40 h-40 bg-blue-500/5 rounded-full blur-3xl"></div>
                    
                    <div class="p-6 border-b border-white/5 bg-zinc-900/30 flex items-center justify-between relative z-10">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-xl bg-blue-500/15 border border-blue-500/30 flex items-center justify-center">
                                <i data-lucide="file-text" class="w-6 h-6 text-blue-400"></i>
                            </div>
                            <div>
                        <h3 class="font-bold text-white text-lg">Atividade Recente</h3>
                                <p class="text-xs text-zinc-400 mt-0.5 font-medium">Últimas requisições registradas</p>
                            </div>
                        </div>
                        <a href="logs.php" class="modern-badge text-sm text-blue-400 hover:text-blue-300 font-bold transition-all flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-500/15 border border-blue-500/30 hover:bg-blue-500/25">
                            Ver histórico
                            <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                        </a>
                    </div>
                    <div class="overflow-x-auto relative z-10">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-zinc-900/50 text-zinc-400 font-bold uppercase text-xs tracking-wider border-b border-white/5">
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
                                        <td colspan="4" class="px-6 py-12 text-center">
                                            <div class="flex flex-col items-center gap-3">
                                                <div class="w-12 h-12 bg-zinc-900/60 rounded-xl border border-white/5 flex items-center justify-center">
                                                    <i data-lucide="file-text" class="w-6 h-6 text-zinc-500"></i>
                                                </div>
                                                <p class="text-xs text-zinc-400 font-bold">Nenhuma atividade registrada</p>
                                                <p class="text-[10px] text-zinc-500 font-medium">Os logs aparecerão aqui</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recentLogs as $log): ?>
                                        <tr class="table-row group">
                                            <td class="px-6 py-4">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-10 h-10 rounded-lg bg-zinc-900/60 border border-white/5 flex items-center justify-center">
                                                        <i data-lucide="monitor" class="w-4 h-4 text-zinc-500"></i>
                                                    </div>
                                                    <div class="font-mono text-zinc-300 font-bold"><?php echo htmlspecialchars($log['ip_address']); ?></div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="flex items-center gap-2 max-w-[250px]">
                                                    <i data-lucide="link" class="w-4 h-4 text-zinc-500 flex-shrink-0"></i>
                                                    <div class="text-zinc-400 truncate font-mono text-xs font-medium" title="<?php echo htmlspecialchars($log['request_uri']); ?>">
                                                    <?php echo htmlspecialchars($log['request_uri']); ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <?php if ($log['action_taken'] === 'blocked'): ?>
                                                    <span class="modern-badge inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold bg-red-500/15 text-red-400 border border-red-500/30">
                                                        <span class="w-2 h-2 rounded-full bg-red-500 shadow-lg shadow-red-500/50"></span>
                                                        Bloqueado
                                                    </span>
                                                <?php else: ?>
                                                    <span class="modern-badge inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold bg-emerald-500/15 text-emerald-400 border border-emerald-500/30">
                                                        <span class="w-2 h-2 rounded-full bg-emerald-500 shadow-lg shadow-emerald-500/50"></span>
                                                        Permitido
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 text-right">
                                                <div class="flex items-center justify-end gap-2">
                                                    <i data-lucide="clock" class="w-4 h-4 text-zinc-500"></i>
                                                    <span class="text-zinc-500 font-mono text-xs font-semibold">
                                                <?php echo date('H:i:s', strtotime($log['created_at'])); ?>
                                                    </span>
                                                </div>
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

    <!-- Modal Lateral de Notificações - Redesign -->
    <div x-show="notificationsOpen" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="translate-x-full opacity-0"
         x-transition:enter-end="translate-x-0 opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="translate-x-0 opacity-100"
         x-transition:leave-end="translate-x-full opacity-0"
         @click.away="notificationsOpen = false"
         class="fixed right-0 top-0 h-full w-96 bg-black/95 backdrop-blur-xl border-l border-white/10 z-50 shadow-2xl relative overflow-hidden"
         x-cloak>
        <!-- Grid pattern -->
        <div class="absolute inset-0 grid-pattern opacity-20"></div>
        
        <!-- Decoração de fundo -->
        <div class="absolute top-0 left-0 w-40 h-40 bg-blue-500/5 rounded-full blur-3xl"></div>
        
        <div class="flex flex-col h-full relative z-10">
            <!-- Header do Modal -->
            <div class="p-6 border-b border-white/10 flex items-center justify-between bg-zinc-900/30">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-500/15 border border-blue-500/30 flex items-center justify-center">
                        <i data-lucide="bell" class="w-5 h-5 text-blue-400"></i>
                    </div>
                <div>
                    <h3 class="text-lg font-bold text-white">Notificações</h3>
                        <p class="text-xs text-zinc-400 mt-0.5 font-medium">Alertas e eventos recentes</p>
                    </div>
                </div>
                <button @click="notificationsOpen = false" class="p-2 text-zinc-400 hover:text-white hover:bg-white/5 rounded-lg transition-all">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Lista de Notificações -->
            <div class="flex-1 overflow-y-auto p-4 space-y-3">
                <!-- Mensagem quando não há notificações -->
                <div class="text-center py-12">
                    <div class="w-16 h-16 bg-zinc-900/60 rounded-xl border border-white/5 flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="bell-off" class="w-8 h-8 text-zinc-500"></i>
                    </div>
                    <p class="text-sm text-zinc-400 font-bold mb-1">Nenhuma notificação nova</p>
                    <p class="text-xs text-zinc-500 font-medium">Você será notificado quando houver novos eventos</p>
                </div>
            </div>

            <!-- Footer -->
            <div class="p-4 border-t border-white/10 bg-zinc-900/30">
                <button class="w-full px-4 py-2.5 text-sm text-blue-400 hover:text-blue-300 font-bold transition-colors rounded-lg hover:bg-blue-500/10 border border-blue-500/20 hover:border-blue-500/30">
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
        
        // Variáveis globais para os gráficos
        let activityChart = null;
        let threatsChart = null;
        
        // Função para formatar números
        function formatNumber(num) {
            if (num >= 1000000) {
                return (num / 1000000).toFixed(1) + 'M';
            } else if (num >= 1000) {
                return (num / 1000).toFixed(1) + 'k';
            }
            return num.toString();
        }
        
        // Função para atualizar a dashboard em tempo real
        async function updateDashboardStats() {
            try {
                const response = await fetch('api/dashboard-stats.php', {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    credentials: 'same-origin'
                });
                
                if (!response.ok) {
                    // Se a resposta não for OK, não fazer nada (evitar logs excessivos)
                    return;
                }
                
                const result = await response.json();
                
                if (!result || !result.success) {
                    // Silenciosamente falhar se não houver sucesso
                    return;
                }
                
                const data = result.data;
                
                // Atualizar card de Requisições/Visitas
                const requests24h = data.last24h.total_requests;
                const requestsChange = data.changes.requests;
                const requestsCard = document.querySelector('[data-stat="total-requests"]');
                const requestsChangeBadge = document.querySelector('[data-stat="requests-change"]');
                if (requestsCard) {
                    requestsCard.textContent = formatNumber(requests24h);
                }
                if (requestsChangeBadge) {
                    requestsChangeBadge.innerHTML = `<i data-lucide="arrow-up-right" class="w-3 h-3"></i> ${Math.abs(requestsChange)}%`;
                    lucide.createIcons();
                }
                
                // Atualizar card de Ameaças Mitigadas
                const blocked24h = data.last24h.blocked;
                const blockedCard = document.querySelector('[data-stat="blocked-threats"]');
                if (blockedCard) {
                    blockedCard.textContent = formatNumber(blocked24h);
                }
                
                // Atualizar card de Latência
                const latencyCard = document.querySelector('[data-stat="latency"]');
                if (latencyCard) {
                    if (data.latency.global !== null) {
                        latencyCard.textContent = Math.round(data.latency.global) + 'ms';
                    } else {
                        latencyCard.textContent = '--ms';
                    }
                }
                
                // Atualizar card de Visitantes Únicos
                const uniqueIpsToday = data.today.unique_ips;
                const uniqueIpsElement = document.querySelector('[data-stat="unique-visitors"]');
                if (uniqueIpsElement) {
                    uniqueIpsElement.textContent = uniqueIpsToday.toLocaleString();
                }
                
                // Atualizar gráfico de atividade
                if (activityChart && data.hourly_stats) {
                    const hours = Object.keys(data.hourly_stats);
                    const hourlyData = Object.values(data.hourly_stats);
                    
                    activityChart.data.labels = hours.map(h => h + ':00');
                    activityChart.data.datasets[0].data = hourlyData.map(d => d.requests);
                    activityChart.data.datasets[1].data = hourlyData.map(d => d.blocked);
                    activityChart.update('none'); // Atualizar sem animação para performance
                }
                
                // Atualizar gráfico de ameaças
                if (threatsChart && data.today.threats) {
                    threatsChart.data.datasets[0].data = [
                        data.today.threats.sql_injection,
                        data.today.threats.xss,
                        data.today.threats.brute_force,
                        data.today.threats.rate_limit,
                        Math.max(0, data.today.blocked - (
                            data.today.threats.sql_injection +
                            data.today.threats.xss +
                            data.today.threats.brute_force +
                            data.today.threats.rate_limit
                        ))
                    ];
                    // Atualizar texto central do gráfico
                    const centerText = document.querySelector('#threatsChart').parentElement.querySelector('.absolute span.text-3xl');
                    if (centerText) {
                        centerText.textContent = data.today.blocked.toLocaleString();
                    }
                    threatsChart.update('none');
                }
                
                // Atualizar card de Regras Ativas
                const activeBlocksElement = document.querySelector('[data-stat="active-blocks"]');
                if (activeBlocksElement) {
                    activeBlocksElement.textContent = data.active_blocks.toLocaleString();
                }
                
                // Atualizar logs recentes (tabela de atividade recente)
                if (data.recent_logs && data.recent_logs.length > 0) {
                    updateRecentLogs(data.recent_logs);
                }
                
                // Atualizar Top Países
                if (data.top_countries) {
                    updateTopCountries(data.top_countries);
                }
                
                // Atualizar Incidentes Recentes
                if (data.recent_incidents) {
                    updateRecentIncidents(data.recent_incidents);
                }
                
                // Atualizar Top IPs Bloqueados
                if (data.top_blocked_ips) {
                    updateTopBlockedIPs(data.top_blocked_ips);
                }
                
                // Atualizar Registro de Eventos
                if (data.event_logs) {
                    updateEventLogs(data.event_logs);
                }
                
                // Atualizar ícones do Lucide
                lucide.createIcons();
                
            } catch (error) {
                // Silenciosamente ignorar erros de rede/timeout
                // Apenas logar em modo debug
                if (window.DEBUG_MODE) {
                    console.error('Erro ao atualizar dashboard:', error);
                }
            }
        }
        
        // Função para atualizar a tabela de logs recentes
        function updateRecentLogs(logs) {
            const tbody = document.querySelector('.glass-card table tbody');
            if (!tbody) return;
            
            // Limpar logs antigos (manter apenas os últimos 5 visíveis)
            const existingRows = tbody.querySelectorAll('tr');
            existingRows.forEach(row => {
                if (!row.querySelector('.sticky-row')) {
                    row.remove();
                }
            });
            
            // Adicionar novos logs no topo
            logs.slice(0, 5).forEach(log => {
                const row = document.createElement('tr');
                row.className = 'hover:bg-white/[0.02] transition-colors';
                
                const threatNames = {
                    'sql_injection': 'Injeção SQL',
                    'xss': 'XSS',
                    'brute_force': 'Força Bruta',
                    'rate_limit': 'Rate Limit',
                    'path_traversal': 'Path Traversal',
                    'command_injection': 'Command Injection',
                    'ddos': 'Ataque DDoS L7'
                };
                
                const isBlocked = log.action_taken === 'blocked';
                const displayText = isBlocked && log.threat_type 
                    ? threatNames[log.threat_type] || log.threat_type
                    : log.ip_address;
                
                const time = new Date(log.created_at).toLocaleTimeString('pt-BR', {
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit'
                });
                
                row.innerHTML = `
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="p-2 rounded bg-zinc-900 border border-white/5">
                                <i data-lucide="monitor" class="w-4 h-4 text-zinc-500"></i>
                            </div>
                            <div class="font-mono text-zinc-300">${log.ip_address}</div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-zinc-400 truncate max-w-[250px] font-mono text-xs" title="${log.request_uri}">
                            ${log.request_uri}
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        ${isBlocked ? `
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-red-500/10 text-red-400 border border-red-500/20">
                                <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                Bloqueado
                            </span>
                        ` : `
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                Permitido
                            </span>
                        `}
                    </td>
                    <td class="px-6 py-4 text-right text-zinc-500 font-mono text-xs">
                        ${time}
                    </td>
                `;
                
                tbody.insertBefore(row, tbody.firstChild);
            });
            
            lucide.createIcons();
        }
        
        // Função para atualizar Top Países
        function updateTopCountries(countries) {
            const container = document.querySelector('[data-stat="top-countries"]');
            if (!container) return;
            
            if (!countries || countries.length === 0) {
                container.innerHTML = '<p class="text-sm text-zinc-500 py-4 text-center">Sem dados suficientes para exibir.</p>';
                return;
            }
            
            const maxRequests = Math.max(...countries.map(c => c.total_requests));
            
            let html = '';
            countries.forEach(country => {
                const width = maxRequests > 0 ? (country.total_requests / maxRequests) * 100 : 0;
                html += `
                    <div>
                        <div class="flex items-center justify-between text-sm mb-1">
                            <div class="flex items-center gap-2">
                                <span class="font-semibold text-white">${country.country_code}</span>
                                <span class="text-xs text-zinc-500">${country.blocked_percent > 0 ? country.blocked_percent + '% bloqueado' : 'Seguro'}</span>
                            </div>
                            <div class="text-xs text-zinc-400 font-mono">${country.total_requests.toLocaleString()} req</div>
                        </div>
                        <div class="w-full h-1.5 bg-zinc-900 rounded-full overflow-hidden">
                            <div class="h-full bg-blue-500" style="width: ${Math.min(100, width)}%"></div>
                        </div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }
        
        // Função para atualizar Incidentes Recentes
        function updateRecentIncidents(incidents) {
            const tbody = document.querySelector('[data-stat="recent-incidents"]');
            if (!tbody) return;
            
            if (!incidents || incidents.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" class="px-6 py-6 text-center text-xs text-zinc-500">
                            Nenhum incidente recente.
                        </td>
                    </tr>
                `;
                return;
            }
            
            let html = '';
            incidents.forEach(incident => {
                const isOpen = incident.status === 'open';
                const lastSeen = new Date(incident.last_seen).toLocaleString('pt-BR', {
                    day: '2-digit',
                    month: '2-digit',
                    hour: '2-digit',
                    minute: '2-digit'
                });
                
                html += `
                    <tr class="hover:bg-white/[0.02] transition-colors">
                        <td class="px-6 py-4">
                            ${isOpen ? `
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-medium bg-red-500/10 text-red-400 border border-red-500/20">
                                    <span class="w-1.5 h-1.5 rounded-full bg-red-400 mr-1 animate-pulse"></span>
                                    Aberto
                                </span>
                            ` : `
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-medium bg-zinc-800 text-zinc-400 border border-white/5">
                                    Resolvido
                                </span>
                            `}
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-xs font-semibold text-white font-mono">${incident.ip_address}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-[10px] font-medium bg-zinc-800 text-zinc-300 border border-white/5">
                                ${incident.threat_type.replace(/_/g, ' ').toUpperCase()}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-[11px] text-zinc-400 font-mono">${incident.site_domain}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-zinc-200 font-semibold">${incident.total_events}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm ${incident.critical_events > 0 ? 'text-red-400 font-semibold' : 'text-zinc-400'}">
                                ${incident.critical_events}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-[11px] text-zinc-400 font-mono">${lastSeen}</span>
                        </td>
                    </tr>
                `;
            });
            
            tbody.innerHTML = html;
        }
        
        // Função para atualizar Top IPs Bloqueados
        function updateTopBlockedIPs(ips) {
            const container = document.querySelector('[data-stat="top-blocked-ips"]');
            if (!container) return;
            
            if (!ips || ips.length === 0) {
                container.innerHTML = '<p class="text-xs text-zinc-500 text-center py-4">Nenhum IP bloqueado recentemente.</p>';
                return;
            }
            
            let html = '';
            ips.forEach(ip => {
                const lastBlocked = new Date(ip.last_blocked).toLocaleString('pt-BR', {
                    day: '2-digit',
                    month: '2-digit',
                    hour: '2-digit',
                    minute: '2-digit'
                });
                
                html += `
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-mono text-white">${ip.ip_address}</p>
                            <p class="text-[11px] text-zinc-500">${ip.threat_types || ''}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold text-red-400">${ip.block_count}x</p>
                            <p class="text-[11px] text-zinc-500">${lastBlocked}</p>
                        </div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }
        
        // Função para atualizar Registro de Eventos
        function updateEventLogs(logs) {
            const container = document.querySelector('[data-stat="event-logs"]');
            if (!container) return;
            
            if (!logs || logs.length === 0) {
                container.innerHTML = '<div class="text-zinc-500 text-center py-4">Nenhum evento recente</div>';
                return;
            }
            
            const threatNames = {
                'sql_injection': 'Injeção SQL',
                'xss': 'XSS',
                'brute_force': 'Força Bruta',
                'rate_limit': 'Rate Limit',
                'path_traversal': 'Path Traversal',
                'command_injection': 'Command Injection',
                'ddos': 'Ataque DDoS L7'
            };
            
            const opacity = ['opacity-50', 'opacity-70', '', 'opacity-80', ''];
            
            let html = '';
            logs.forEach((log, i) => {
                const time = new Date(log.created_at).toLocaleTimeString('pt-BR', {
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit'
                });
                
                const actionText = log.show_mitigated 
                    ? 'MITIGADO' 
                    : (log.action_taken === 'blocked' ? 'BLOQUEAR' : 'PERMITIR');
                
                const actionClass = log.show_mitigated 
                    ? 'text-red-500 font-bold' 
                    : (log.action_taken === 'blocked' ? 'text-red-500' : 'text-green-500');
                
                const displayText = log.action_taken === 'blocked' && log.threat_type
                    ? threatNames[log.threat_type] || log.threat_type.replace(/_/g, ' ')
                    : log.ip_address;
                
                const textClass = log.show_mitigated ? 'text-white' : 'text-zinc-400';
                const borderClass = log.show_mitigated ? 'border-l-2 border-red-500 pl-2 bg-red-500/5 py-1' : '';
                
                html += `
                    <div class="flex gap-2 ${opacity[i] || ''} ${borderClass}">
                        <span class="text-zinc-500">${time}</span>
                        <span class="${actionClass}">${actionText}</span>
                        <span class="${textClass}">${displayText}</span>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }
        
        // Iniciar polling a cada 3 segundos
        setInterval(updateDashboardStats, 3000);
        
        // Atualizar imediatamente quando a página carregar
        setTimeout(updateDashboardStats, 1000);
        
        // Activity Chart
        const activityCtx = document.getElementById('activityChart');
        if (activityCtx) {
            const hourlyData = <?php echo json_encode(array_values($hourlyStats)); ?>;
            const hours = <?php echo json_encode(array_keys($hourlyStats)); ?>;
            
            activityChart = new Chart(activityCtx, {
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
            threatsChart = new Chart(threatsCtx, {
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
        
        // Modal de Atualização do Sistema
        (function() {
            const updateModal = document.getElementById('update-modal');
            const shouldShowModal = <?php echo isset($_SESSION['show_update_modal']) && $_SESSION['show_update_modal'] ? 'true' : 'false'; ?>;
            
            // Verificar se o modal deve ser mostrado (via sessão PHP)
            if (shouldShowModal && updateModal) {
                // Mostrar modal após um pequeno delay
                setTimeout(() => {
                    updateModal.classList.remove('hidden');
                    updateModal.classList.add('flex');
                    // Reinicializar ícones do Lucide
                    lucide.createIcons();
                }, 1000);
            }
            
            // Fechar modal
            const closeModal = () => {
                if (updateModal) {
                    updateModal.classList.add('hidden');
                    updateModal.classList.remove('flex');
                    // Remover flag da sessão via AJAX
                    fetch('api/close-update-modal.php').catch(() => {});
                }
            };
            
            // Botão de fechar
            const closeBtn = document.getElementById('update-modal-close');
            if (closeBtn) {
                closeBtn.addEventListener('click', closeModal);
            }
            
            // Botão "Entendi"
            const understoodBtn = document.getElementById('update-modal-understood');
            if (understoodBtn) {
                understoodBtn.addEventListener('click', closeModal);
            }
            
            // Botão "Ver Atualizações"
            const seeUpdatesBtn = document.getElementById('update-modal-see-updates');
            if (seeUpdatesBtn) {
                seeUpdatesBtn.addEventListener('click', () => {
                    closeModal();
                    window.location.href = 'updates.php';
                });
            }
            
            // Fechar ao clicar no backdrop
            if (updateModal) {
                updateModal.addEventListener('click', (e) => {
                    if (e.target === updateModal) {
                        closeModal();
                    }
                });
            }
        })();
    </script>
    
    <!-- Modal de Atualização do Sistema - Redesign -->
    <div id="update-modal" 
         class="fixed inset-0 z-50 hidden items-center justify-center bg-black/90 backdrop-blur-md"
         x-data="{ open: false }"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div class="w-full max-w-lg mx-4 rounded-2xl bg-zinc-950 border border-white/10 p-6 shadow-2xl relative overflow-hidden depth-shadow animate-scale-in"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95">
            
            <!-- Grid pattern -->
            <div class="absolute inset-0 grid-pattern opacity-20"></div>
            
            <!-- Decorative Background -->
            <div class="absolute inset-0 bg-gradient-to-br from-blue-500/5 via-purple-500/5 to-transparent pointer-events-none"></div>
            
            <!-- Decoração de fundo -->
            <div class="absolute top-0 right-0 w-32 h-32 bg-blue-500/5 rounded-full blur-3xl"></div>
            
            <!-- Close Button -->
            <button id="update-modal-close" class="absolute top-4 right-4 p-2 text-zinc-400 hover:text-white hover:bg-white/5 rounded-lg transition-all z-10">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
            
            <div class="relative z-10">
                <!-- Header -->
                <div class="text-center mb-6">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-xl bg-blue-500/15 border border-blue-500/30 mb-4">
                        <i data-lucide="sparkles" class="w-8 h-8 text-blue-400"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-white mb-2">Sistema Atualizado! 🎉</h2>
                    <p class="text-zinc-400 text-sm font-medium">O SafeNode recebeu novas funcionalidades e melhorias</p>
                </div>
                
                <!-- Content -->
                <div class="mb-6">
                    <p class="text-zinc-300 text-sm text-center mb-4 font-medium">
                        Estamos sempre trabalhando para melhorar sua experiência. A nova versão inclui:
                    </p>
                    <ul class="space-y-3 text-sm text-zinc-400">
                        <li class="flex items-start gap-3 p-3 rounded-xl bg-zinc-900/30 border border-white/5">
                            <div class="w-6 h-6 rounded-lg bg-emerald-500/15 border border-emerald-500/30 flex items-center justify-center flex-shrink-0">
                                <i data-lucide="check" class="w-4 h-4 text-emerald-400"></i>
                            </div>
                            <span class="font-medium">Dashboard em tempo real com atualizações automáticas</span>
                        </li>
                        <li class="flex items-start gap-3 p-3 rounded-xl bg-zinc-900/30 border border-white/5">
                            <div class="w-6 h-6 rounded-lg bg-emerald-500/15 border border-emerald-500/30 flex items-center justify-center flex-shrink-0">
                                <i data-lucide="check" class="w-4 h-4 text-emerald-400"></i>
                            </div>
                            <span class="font-medium">Novos recursos de segurança e melhorias de performance</span>
                        </li>
                        <li class="flex items-start gap-3 p-3 rounded-xl bg-zinc-900/30 border border-white/5">
                            <div class="w-6 h-6 rounded-lg bg-emerald-500/15 border border-emerald-500/30 flex items-center justify-center flex-shrink-0">
                                <i data-lucide="check" class="w-4 h-4 text-emerald-400"></i>
                            </div>
                            <span class="font-medium">Interface mais intuitiva e responsiva</span>
                        </li>
                    </ul>
                </div>
                
                <!-- Actions -->
                <div class="flex flex-col sm:flex-row gap-3">
                    <button id="update-modal-understood" class="flex-1 px-5 py-3 rounded-xl bg-zinc-900/80 text-zinc-300 hover:bg-zinc-800 hover:border-white/10 border border-white/10 font-bold transition-all text-sm">
                        Entendi
                    </button>
                    <button id="update-modal-see-updates" class="btn-primary flex-1 px-5 py-3 rounded-xl text-white font-bold transition-all flex items-center justify-center gap-2 text-sm">
                        <i data-lucide="sparkles" class="w-4 h-4"></i>
                        Ver Atualizações
                    </button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
