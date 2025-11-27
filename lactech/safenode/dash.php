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

        /* Alpine.js x-cloak */
        [x-cloak] { display: none !important; }

        /* Melhorias de Hover States - Mais Impactantes */
        .card-hover {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }
        .card-hover:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.6), 0 0 30px rgba(59, 130, 246, 0.2);
            border-color: rgba(255, 255, 255, 0.15);
        }
        .card-hover::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 12px;
            padding: 1px;
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.3), rgba(147, 51, 234, 0.3));
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
            opacity: 0;
            transition: opacity 0.4s;
        }
        .card-hover:hover::before {
            opacity: 1;
        }

        /* Animação de contador */
        @keyframes countUp {
            from { opacity: 0; transform: scale(0.8); }
            to { opacity: 1; transform: scale(1); }
        }
        .count-animate {
            animation: countUp 0.5s ease-out;
        }

        /* Hero Metric - Visual Destacado */
        .hero-metric {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(239, 68, 68, 0.05) 100%);
            border: 2px solid rgba(239, 68, 68, 0.3);
            box-shadow: 0 0 40px rgba(239, 68, 68, 0.15), inset 0 0 20px rgba(239, 68, 68, 0.05);
        }
        .hero-metric:hover {
            border-color: rgba(239, 68, 68, 0.5);
            box-shadow: 0 0 60px rgba(239, 68, 68, 0.25), inset 0 0 30px rgba(239, 68, 68, 0.1);
        }

        /* Gradientes sutis nos cards */
        .metric-card-gradient-1 {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.08) 0%, rgba(16, 185, 129, 0.02) 100%);
        }
        .metric-card-gradient-2 {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.08) 0%, rgba(59, 130, 246, 0.02) 100%);
        }

        /* Badge de status animado */
        @keyframes pulse-glow {
            0%, 100% { box-shadow: 0 0 10px currentColor; }
            50% { box-shadow: 0 0 20px currentColor, 0 0 30px currentColor; }
        }
        .status-badge-pulse {
            animation: pulse-glow 2s ease-in-out infinite;
        }

        /* Linha de separação visual melhorada */
        .section-divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            margin: 2rem 0;
        }

        /* Mini gráfico de tendência */
        .sparkline {
            height: 40px;
            width: 100%;
            opacity: 0.6;
            transition: opacity 0.3s;
        }
        .card-hover:hover .sparkline {
            opacity: 1;
        }

        /* Melhorias gerais de design */
        .metric-card {
            background: linear-gradient(180deg, rgba(39, 39, 42, 0.5) 0%, rgba(24, 24, 27, 0.5) 100%);
            backdrop-filter: blur(10px);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .metric-card:hover {
            background: linear-gradient(180deg, rgba(39, 39, 42, 0.7) 0%, rgba(24, 24, 27, 0.7) 100%);
        }

        /* Efeito de shimmer nos cards */
        @keyframes shimmer {
            0% { background-position: -1000px 0; }
            100% { background-position: 1000px 0; }
        }
        .shimmer-effect {
            background: linear-gradient(90deg, transparent 0%, rgba(255, 255, 255, 0.05) 50%, transparent 100%);
            background-size: 1000px 100%;
            animation: shimmer 3s infinite;
        }

        /* Bordas suaves e modernas */
        .card-border-glow {
            position: relative;
        }
        .card-border-glow::after {
            content: '';
            position: absolute;
            inset: -1px;
            border-radius: inherit;
            padding: 1px;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), transparent, rgba(255, 255, 255, 0.1));
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
            opacity: 0;
            transition: opacity 0.3s;
        }
        .card-border-glow:hover::after {
            opacity: 1;
        }

        /* Melhorias tipográficas */
        .metric-value {
            font-feature-settings: 'tnum';
            font-variant-numeric: tabular-nums;
            letter-spacing: -0.02em;
        }

        /* Efeito de profundidade */
        .depth-shadow {
            box-shadow: 
                0 1px 2px 0 rgba(0, 0, 0, 0.3),
                0 4px 8px 0 rgba(0, 0, 0, 0.2),
                0 8px 16px 0 rgba(0, 0, 0, 0.1);
        }
        .depth-shadow:hover {
            box-shadow: 
                0 2px 4px 0 rgba(0, 0, 0, 0.4),
                0 8px 16px 0 rgba(0, 0, 0, 0.3),
                0 16px 32px 0 rgba(0, 0, 0, 0.2);
        }

        /* Badge moderno */
        .modern-badge {
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2), inset 0 1px 0 rgba(255, 255, 255, 0.1);
        }

        /* Grid pattern sutil */
        .grid-pattern {
            background-image: 
                linear-gradient(rgba(255, 255, 255, 0.02) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.02) 1px, transparent 1px);
            background-size: 20px 20px;
        }

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
                <button class="text-zinc-400 hover:text-white" data-sidebar-toggle>
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
                    <?php echo htmlspecialchars($_SESSION['view_site_name'] ?? 'Visão Global'); ?>
                </div>
                <?php if ($currentSiteId > 0 && $selectedSite): ?>
                <div class="h-4 w-px bg-white/10"></div>
                <form method="POST">
                    <input type="hidden" name="toggle_under_attack" value="1">
                    <?php $underAttack = $selectedSite['security_level'] === 'under_attack'; ?>
                    <button type="submit" class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-semibold rounded-full border transition-all <?php echo $underAttack ? 'bg-red-500/10 text-red-300 border-red-500/30 hover:bg-red-500/20' : 'bg-zinc-900/60 text-zinc-300 border-white/10 hover:border-white/30'; ?>">
                        <span class="w-2 h-2 rounded-full <?php echo $underAttack ? 'bg-red-400 animate-pulse' : 'bg-zinc-500'; ?>"></span>
                        <?php echo $underAttack ? 'Sob Ataque ATIVO' : 'Sob Ataque DESLIGADO'; ?>
                    </button>
                </form>
                <?php endif; ?>
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
                <?php if (!empty($dashboardFlash)): ?>
                <div class="rounded-xl p-4 <?php echo $dashboardFlashType === 'warning' ? 'bg-amber-500/10 border border-amber-500/30 text-amber-200' : ($dashboardFlashType === 'error' ? 'bg-red-500/10 border border-red-500/30 text-red-200' : 'bg-emerald-500/10 border border-emerald-500/30 text-emerald-200'); ?>">
                    <div class="flex items-center gap-2 text-sm font-semibold">
                        <i data-lucide="<?php echo $dashboardFlashType === 'error' ? 'alert-triangle' : ($dashboardFlashType === 'warning' ? 'shield' : 'check-circle'); ?>" class="w-4 h-4"></i>
                        <span><?php echo htmlspecialchars($dashboardFlash); ?></span>
                    </div>
                </div>
                <?php endif; ?>
                
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
                
                <!-- Header Melhorado -->
                <div class="mb-6 animate-fade-in">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-4">
                    <div>
                            <div class="flex items-center gap-2 mb-1">
                                <div class="w-0.5 h-5 bg-gradient-to-b from-blue-500 to-purple-500 rounded-full"></div>
                                <h1 class="text-2xl font-bold text-white tracking-tight">
                                    <?php echo $currentSiteId > 0 ? 'Visão Geral' : 'Visão Geral Global'; ?>
                        </h1>
                            </div>
                            <?php if ($currentSiteId > 0): ?>
                            <p class="text-zinc-400 text-sm ml-3 font-medium"><?php echo htmlspecialchars($_SESSION['view_site_name']); ?></p>
                            <?php endif; ?>
                            <p class="text-zinc-500 text-xs ml-3 mt-0.5">Monitoramento de visitas e segurança em tempo real</p>
                    </div>
                    <div class="flex gap-3 relative">
                        <?php if (!$hasSites): ?>
                        <div class="absolute inset-0 -m-2 bg-zinc-900/50 rounded-lg z-20 pointer-events-none"></div>
                        <?php endif; ?>
                            <a href="logs.php" class="flex items-center gap-2 px-4 py-2 bg-white text-black rounded-lg text-sm font-semibold hover:bg-zinc-200 transition-all shadow-lg shadow-white/10 hover:shadow-xl relative <?php echo !$hasSites ? 'opacity-40 blur-sm z-0 pointer-events-none cursor-not-allowed' : 'z-10'; ?>">
                            <i data-lucide="file-text" class="w-4 h-4"></i>
                            Logs
                                <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                        </a>
                    </div>
                </div>

                    <!-- Divider Visual -->
                    <div class="section-divider"></div>
                </div>

                <!-- Widget de Insights Automatizados -->
                <?php if ($hasSites): 
                    // Calcular insights
                    $insights = [];
                    
                    // Insight 1: Ameaças bloqueadas
                    if ($blocked24h > 0) {
                        $blockedYesterday = $yesterdayStats['blocked_requests'] ?? 0;
                        if ($blockedYesterday > 0) {
                            $blockedChangePct = round((($blocked24h - $blockedYesterday) / $blockedYesterday) * 100);
                            if ($blockedChangePct > 20) {
                                $insights[] = [
                                    'type' => 'warning',
                                    'icon' => 'alert-triangle',
                                    'text' => 'Aumento de ' . abs($blockedChangePct) . '% em ameaças bloqueadas nas últimas 24h',
                                    'color' => 'amber'
                                ];
                            } elseif ($blockedChangePct < -20) {
                                $insights[] = [
                                    'type' => 'success',
                                    'icon' => 'shield-check',
                                    'text' => 'Redução de ' . abs($blockedChangePct) . '% em ameaças bloqueadas',
                                    'color' => 'emerald'
                                ];
                            }
                        }
                    }
                    
                    // Insight 2: Tráfego
                    if ($requestsChange > 30) {
                        $insights[] = [
                            'type' => 'info',
                            'icon' => 'trending-up',
                            'text' => 'Aumento significativo de ' . $requestsChange . '% no tráfego vs. ontem',
                            'color' => 'blue'
                        ];
                    } elseif ($requestsChange < -30) {
                        $insights[] = [
                            'type' => 'info',
                            'icon' => 'trending-down',
                            'text' => 'Redução de ' . abs($requestsChange) . '% no tráfego vs. ontem',
                            'color' => 'zinc'
                        ];
                    }
                    
                    // Insight 3: Latência
                    if ($globalLatency !== null && $globalLatency > 200) {
                        $insights[] = [
                            'type' => 'warning',
                            'icon' => 'zap',
                            'text' => 'Latência acima de 200ms - monitorar performance',
                            'color' => 'amber'
                        ];
                    } elseif ($globalLatency !== null && $globalLatency < 50) {
                        $insights[] = [
                            'type' => 'success',
                            'icon' => 'zap',
                            'text' => 'Latência excelente (' . round($globalLatency) . 'ms)',
                            'color' => 'emerald'
                        ];
                    }
                    
                    // Insight 4: Top país
                    if (!empty($topCountries)) {
                        $topCountry = $topCountries[0];
                        $countryPercent = $topCountries[0]['total_requests'] > 0 ? round(($topCountry['total_requests'] / max(1, $requests24h)) * 100) : 0;
                        if ($countryPercent > 40) {
                            $insights[] = [
                                'type' => 'info',
                                'icon' => 'globe',
                                'text' => $topCountry['country_code'] . ' concentra ' . $countryPercent . '% do tráfego',
                                'color' => 'blue'
                            ];
                        }
                    }
                ?>
                <?php if (!empty($insights)): ?>
                <div class="rounded-xl bg-gradient-to-br from-blue-500/10 via-zinc-900/80 to-purple-500/10 border border-blue-500/25 p-4 mb-6 animate-fade-in relative overflow-hidden depth-shadow">
                    <!-- Grid pattern -->
                    <div class="absolute inset-0 grid-pattern opacity-30"></div>
                    
                    <!-- Efeitos de fundo -->
                    <div class="absolute top-0 right-0 w-32 h-32 bg-blue-500/8 rounded-full blur-3xl"></div>
                    <div class="absolute bottom-0 left-0 w-24 h-24 bg-purple-500/8 rounded-full blur-2xl"></div>
                    
                    <div class="relative z-10">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-blue-500/25 to-purple-500/25 border border-blue-500/40 flex items-center justify-center depth-shadow">
                                <i data-lucide="sparkles" class="w-4.5 h-4.5 text-blue-400"></i>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-white">💡 Insights do Dia</h3>
                                <p class="text-[10px] text-zinc-400 font-medium">Análise automática dos dados</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <?php foreach (array_slice($insights, 0, 3) as $insight): ?>
                            <div class="p-3.5 rounded-xl bg-zinc-900/60 border border-<?php echo $insight['color']; ?>-500/25 hover:border-<?php echo $insight['color']; ?>-500/45 transition-all duration-300 hover:bg-zinc-900/70 depth-shadow group">
                                <div class="flex items-start gap-2.5">
                                    <div class="p-2 bg-<?php echo $insight['color']; ?>-500/20 rounded-lg border border-<?php echo $insight['color']; ?>-500/35 flex-shrink-0 group-hover:bg-<?php echo $insight['color']; ?>-500/25 transition-all">
                                        <i data-lucide="<?php echo $insight['icon']; ?>" class="w-4 h-4 text-<?php echo $insight['color']; ?>-400"></i>
                                    </div>
                                    <p class="text-xs text-zinc-200 font-medium leading-relaxed pt-0.5"><?php echo htmlspecialchars($insight['text']); ?></p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                <?php endif; ?>

                <!-- Stats Cards - Design Melhorado -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <!-- Total de Requisições -->
                    <a href="logs.php" class="group relative overflow-hidden rounded-xl metric-card border border-zinc-800/50 hover:border-emerald-500/40 card-hover card-border-glow p-5 depth-shadow <?php echo !$hasSites ? 'pointer-events-none opacity-40 blur-sm' : ''; ?>">
                        <?php if (!$hasSites): ?>
                        <div class="absolute inset-0 bg-zinc-900/50 rounded-xl z-10 pointer-events-none"></div>
                        <?php endif; ?>
                        
                        <!-- Grid pattern sutil -->
                        <div class="absolute inset-0 grid-pattern opacity-50"></div>
                        
                        <!-- Decoração de fundo -->
                        <div class="absolute top-0 right-0 w-28 h-28 bg-emerald-500/8 rounded-full blur-3xl group-hover:bg-emerald-500/12 transition-all duration-500"></div>
                        
                        <!-- Ícone decorativo -->
                        <div class="absolute top-4 right-4 opacity-8 group-hover:opacity-15 transition-opacity duration-300">
                            <i data-lucide="trending-up" class="w-14 h-14 text-emerald-400/60"></i>
                        </div>
                        
                        <div class="relative z-10">
                            <!-- Header do card -->
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-2.5">
                                    <div class="p-2 bg-emerald-500/15 rounded-xl border border-emerald-500/25 group-hover:bg-emerald-500/20 group-hover:border-emerald-500/35 transition-all depth-shadow">
                                        <i data-lucide="activity" class="w-4 h-4 text-emerald-400"></i>
                                    </div>
                                    <span class="text-xs font-bold text-zinc-300 uppercase tracking-wider">Visitas</span>
                                </div>
                                <span class="text-[10px] <?php echo $requestsChange >= 0 ? 'text-emerald-400' : 'text-zinc-500'; ?> modern-badge px-2.5 py-1 rounded-full bg-<?php echo $requestsChange >= 0 ? 'emerald' : 'zinc'; ?>-900/50 flex items-center gap-1.5 font-bold" data-stat="requests-change">
                                    <i data-lucide="<?php echo $requestsChange >= 0 ? 'arrow-up-right' : 'arrow-down-right'; ?>" class="w-3 h-3"></i> 
                                    <?php echo abs($requestsChange); ?>%
                            </span>
                        </div>
                            
                            <!-- Valor principal -->
                            <div class="mb-3">
                                <div class="text-3xl font-black text-white group-hover:text-emerald-400 transition-colors duration-300 mb-1 metric-value" data-stat="total-requests">
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
                                <p class="text-[11px] text-zinc-500 font-medium">últimas 24 horas</p>
                            </div>
                            
                            <!-- Footer com shimmer -->
                            <div class="flex items-center justify-between pt-3 border-t border-white/5 relative overflow-hidden rounded-b-xl">
                                <div class="absolute inset-0 shimmer-effect opacity-0 group-hover:opacity-100 transition-opacity"></div>
                                <span class="text-[10px] text-zinc-500 uppercase tracking-wider font-medium relative z-10">Requisições</span>
                                <i data-lucide="arrow-right" class="w-3.5 h-3.5 text-zinc-600 group-hover:text-emerald-400 group-hover:translate-x-1 transition-all relative z-10"></i>
                            </div>
                        </div>
                    </a>
                    
                    <!-- Hero Metric: Ameaças Mitigadas (Design Melhorado) -->
                    <a href="logs.php?<?php echo http_build_query(['action' => 'blocked']); ?>" class="group relative overflow-hidden rounded-xl hero-metric p-5 card-hover card-border-glow depth-shadow <?php echo !$hasSites ? 'pointer-events-none opacity-40 blur-sm' : ''; ?>">
                        <?php if (!$hasSites): ?>
                        <div class="absolute inset-0 bg-zinc-900/50 rounded-xl z-10 pointer-events-none"></div>
                        <?php endif; ?>
                        
                        <!-- Grid pattern -->
                        <div class="absolute inset-0 grid-pattern opacity-40"></div>
                        
                        <!-- Efeitos de fundo animados -->
                        <div class="absolute top-0 right-0 w-32 h-32 bg-red-500/12 rounded-full blur-3xl animate-pulse"></div>
                        <div class="absolute bottom-0 left-0 w-24 h-24 bg-red-500/6 rounded-full blur-2xl"></div>
                        
                        <!-- Gradiente de brilho -->
                        <div class="absolute inset-0 bg-gradient-to-br from-red-500/5 via-transparent to-red-500/5"></div>
                        
                        <!-- Ícone decorativo -->
                        <div class="absolute top-4 right-4 opacity-10 group-hover:opacity-20 transition-opacity duration-300">
                            <i data-lucide="shield-alert" class="w-14 h-14 text-red-400/60"></i>
                        </div>
                        
                        <!-- Indicador de status crítico pulsante -->
                        <div class="absolute top-5 right-5 z-20">
                            <div class="w-2.5 h-2.5 rounded-full bg-red-500 status-badge-pulse"></div>
                            <div class="absolute inset-0 w-2.5 h-2.5 rounded-full bg-red-500/50 animate-ping"></div>
                        </div>
                        
                        <div class="relative z-10">
                            <!-- Badge de Hero -->
                            <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full modern-badge bg-red-500/25 border border-red-500/40 mb-3 shadow-lg shadow-red-500/20">
                                <i data-lucide="star" class="w-3 h-3 text-red-400"></i>
                                <span class="text-[9px] font-bold text-red-400 uppercase tracking-wider">Métrica Principal</span>
                            </div>
                            
                            <!-- Header do card -->
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-2.5">
                                    <div class="p-2 bg-red-500/20 rounded-xl border border-red-500/35 group-hover:bg-red-500/25 group-hover:border-red-500/45 transition-all depth-shadow">
                                        <i data-lucide="shield-check" class="w-4 h-4 text-red-400"></i>
                                    </div>
                                    <span class="text-xs font-bold text-white uppercase tracking-wider">Ameaças Mitigadas</span>
                                </div>
                                <?php 
                                $blockedYesterday = $yesterdayStats['blocked_requests'] ?? 0;
                                $blockedChangePct = $blockedYesterday > 0 ? round((($blocked24h - $blockedYesterday) / $blockedYesterday) * 100) : ($blocked24h > 0 ? 100 : 0);
                                ?>
                                <span class="text-[10px] <?php echo $blockedChangePct >= 0 ? 'text-red-400' : 'text-emerald-400'; ?> modern-badge px-2.5 py-1 rounded-full bg-<?php echo $blockedChangePct >= 0 ? 'red' : 'emerald'; ?>-900/50 flex items-center gap-1.5 font-bold">
                                    <i data-lucide="<?php echo $blockedChangePct >= 0 ? 'arrow-up-right' : 'arrow-down-right'; ?>" class="w-3 h-3"></i> 
                                    <?php echo abs($blockedChangePct); ?>%
                            </span>
                        </div>
                            
                            <!-- Valor principal -->
                            <div class="mb-3">
                                <div class="text-3xl font-black text-white group-hover:text-red-400 transition-colors duration-300 mb-1 leading-tight metric-value" data-stat="blocked-threats">
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
                                <p class="text-[11px] text-zinc-400 font-medium">bloqueadas automaticamente</p>
                            </div>
                            
                            <!-- Footer com shimmer -->
                            <div class="flex items-center justify-between pt-3 border-t border-red-500/25 relative overflow-hidden rounded-b-xl">
                                <div class="absolute inset-0 shimmer-effect opacity-0 group-hover:opacity-100 transition-opacity"></div>
                                <span class="text-[10px] text-red-400/80 uppercase tracking-wider font-semibold relative z-10">Proteção IA Ativa</span>
                                <i data-lucide="arrow-right" class="w-3.5 h-3.5 text-red-400/70 group-hover:text-red-400 group-hover:translate-x-1 transition-all relative z-10"></i>
                            </div>
                        </div>
                    </a>
                    
                    <?php if ($hasSites): ?>
                    <a href="logs.php" class="group relative overflow-hidden rounded-xl metric-card border border-zinc-800/50 hover:border-blue-500/40 card-hover card-border-glow p-5 depth-shadow">
                        <!-- Grid pattern -->
                        <div class="absolute inset-0 grid-pattern opacity-50"></div>
                        
                        <!-- Decoração de fundo -->
                        <div class="absolute top-0 right-0 w-28 h-28 bg-blue-500/8 rounded-full blur-3xl group-hover:bg-blue-500/12 transition-all duration-500"></div>
                        
                        <!-- Ícone decorativo -->
                        <div class="absolute top-4 right-4 opacity-8 group-hover:opacity-15 transition-opacity duration-300">
                            <i data-lucide="zap" class="w-14 h-14 text-blue-400/60"></i>
                        </div>
                        
                        <div class="relative z-10">
                            <!-- Header do card -->
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-2.5">
                                    <div class="p-2 bg-blue-500/15 rounded-xl border border-blue-500/25 group-hover:bg-blue-500/20 group-hover:border-blue-500/35 transition-all depth-shadow">
                                        <i data-lucide="gauge" class="w-4 h-4 text-blue-400"></i>
                                    </div>
                                    <span class="text-xs font-bold text-zinc-300 uppercase tracking-wider">Latência</span>
                                </div>
                            <?php if ($globalLatency !== null && $avgLatency !== null): 
                                $latencyChange = $avgLatency < ($globalLatency * 0.9) ? -round(($globalLatency - $avgLatency) / $globalLatency * 100) : 0;
                                    $latencyStatus = $globalLatency < 100 ? 'excelente' : ($globalLatency < 200 ? 'boa' : 'alta');
                                    $statusColor = $globalLatency < 100 ? 'emerald' : ($globalLatency < 200 ? 'blue' : 'amber');
                            ?>
                                <span class="text-[10px] text-<?php echo $statusColor; ?>-400 modern-badge px-2.5 py-1 rounded-full bg-<?php echo $statusColor; ?>-900/50 flex items-center gap-1.5 font-bold">
                                    <i data-lucide="check-circle" class="w-3 h-3"></i> 
                                    <?php echo ucfirst($latencyStatus); ?>
                            </span>
                            <?php else: ?>
                                <span class="text-[10px] text-blue-400 modern-badge px-2.5 py-1 rounded-full bg-blue-900/50 flex items-center gap-1.5 font-bold">
                                <i data-lucide="zap" class="w-3 h-3"></i> --
                            </span>
                            <?php endif; ?>
                        </div>
                            
                            <!-- Valor principal -->
                            <div class="mb-3">
                                <div class="text-3xl font-black text-white group-hover:text-blue-400 transition-colors duration-300 mb-1 metric-value" data-stat="latency">
                            <?php echo $globalLatency !== null ? number_format($globalLatency, 0) . 'ms' : '--ms'; ?>
                        </div>
                                <p class="text-[11px] text-zinc-500 font-medium">tempo de resposta P99</p>
                            </div>
                            
                            <!-- Footer com shimmer -->
                            <div class="flex items-center justify-between pt-3 border-t border-white/5 relative overflow-hidden rounded-b-xl">
                                <div class="absolute inset-0 shimmer-effect opacity-0 group-hover:opacity-100 transition-opacity"></div>
                                <span class="text-[10px] text-zinc-500 uppercase tracking-wider font-medium relative z-10">Performance</span>
                                <i data-lucide="arrow-right" class="w-3.5 h-3.5 text-zinc-600 group-hover:text-blue-400 group-hover:translate-x-1 transition-all relative z-10"></i>
                            </div>
                        </div>
                    </a>
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

                <!-- Divider -->
                <div class="section-divider my-8"></div>
                
                <!-- Stats Grid (Cards Adicionais) - Redesign Completo -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <!-- Unique IPs - Design Melhorado -->
                    <div class="group relative overflow-hidden rounded-xl metric-card border border-zinc-800/50 hover:border-purple-500/40 card-hover card-border-glow p-5 depth-shadow animate-slide-up <?php echo !$hasSites ? 'opacity-40 blur-sm' : ''; ?>" style="animation-delay: 0.3s;">
                        <?php if (!$hasSites): ?>
                        <div class="absolute inset-0 bg-zinc-900/50 rounded-xl z-10 pointer-events-none"></div>
                        <?php endif; ?>
                        
                        <!-- Grid pattern -->
                        <div class="absolute inset-0 grid-pattern opacity-50"></div>
                        
                        <!-- Efeitos de fundo -->
                        <div class="absolute -right-8 -top-8 w-32 h-32 bg-purple-500/8 rounded-full blur-3xl group-hover:bg-purple-500/12 transition-all duration-500"></div>
                        
                        <!-- Ícone decorativo -->
                        <div class="absolute top-4 right-4 opacity-8 group-hover:opacity-15 transition-opacity duration-300">
                            <i data-lucide="users" class="w-14 h-14 text-purple-400/60"></i>
                        </div>
                        
                        <div class="relative z-10">
                            <!-- Header -->
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-2.5">
                                    <div class="p-2 bg-purple-500/15 rounded-xl border border-purple-500/25 group-hover:bg-purple-500/20 group-hover:border-purple-500/35 transition-all depth-shadow">
                                        <i data-lucide="globe" class="w-4 h-4 text-purple-400"></i>
                                    </div>
                                    <div>
                                        <h3 class="text-xs font-bold text-white uppercase tracking-wider">Visitantes Únicos</h3>
                                        <p class="text-[10px] text-zinc-500 font-medium">IPs únicos hoje</p>
                                    </div>
                            </div>
                            <?php if ($ipsChange != 0): ?>
                                    <span class="text-[10px] font-bold px-2.5 py-1 rounded-full modern-badge <?php echo $ipsChange > 0 ? 'bg-emerald-500/15 text-emerald-400 border-emerald-500/40' : 'bg-zinc-800/60 text-zinc-400 border-zinc-700/50'; ?> transition-all">
                                    <?php echo $ipsChange > 0 ? '+' : ''; ?><?php echo $ipsChange; ?>%
                                </span>
                                <?php else: ?>
                                    <span class="text-[10px] font-medium px-2 py-1 rounded-full modern-badge bg-zinc-800/60 text-zinc-500 border-zinc-700/50">
                                        →
                                </span>
                            <?php endif; ?>
                        </div>
                            
                            <!-- Valor -->
                            <div class="mb-3">
                                <p class="text-3xl font-black text-white font-mono group-hover:text-purple-400 transition-colors duration-300 leading-none metric-value" data-stat="unique-visitors">
                                    <?php echo number_format($stats['unique_ips_today']); ?>
                                </p>
                            </div>
                            
                            <!-- Mini indicador visual melhorado -->
                            <div class="h-1.5 bg-zinc-800/80 rounded-full overflow-hidden border border-white/5">
                                <div class="h-full bg-gradient-to-r from-purple-500 via-purple-600 to-purple-500 rounded-full shadow-lg shadow-purple-500/30" style="width: <?php echo min(100, ($stats['unique_ips_today'] / max(1, $stats['unique_ips_today'])) * 100); ?>%"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Active Rules - Design Melhorado -->
                    <div class="group relative overflow-hidden rounded-xl metric-card border border-zinc-800/50 hover:border-amber-500/40 card-hover card-border-glow p-5 depth-shadow animate-slide-up <?php echo !$hasSites ? 'opacity-40 blur-sm' : ''; ?>" style="animation-delay: 0.4s;">
                        <?php if (!$hasSites): ?>
                        <div class="absolute inset-0 bg-zinc-900/50 rounded-xl z-10 pointer-events-none"></div>
                        <?php endif; ?>
                        
                        <!-- Grid pattern -->
                        <div class="absolute inset-0 grid-pattern opacity-50"></div>
                        
                        <!-- Efeitos de fundo -->
                        <div class="absolute -right-8 -top-8 w-32 h-32 bg-amber-500/8 rounded-full blur-3xl group-hover:bg-amber-500/12 transition-all duration-500"></div>
                        
                        <!-- Ícone decorativo -->
                        <div class="absolute top-4 right-4 opacity-8 group-hover:opacity-15 transition-opacity duration-300">
                            <i data-lucide="shield" class="w-14 h-14 text-amber-400/60"></i>
                            </div>
                        
                        <div class="relative z-10">
                            <!-- Header -->
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-2.5">
                                    <div class="p-2 bg-amber-500/15 rounded-xl border border-amber-500/25 group-hover:bg-amber-500/20 group-hover:border-amber-500/35 transition-all depth-shadow relative">
                                        <i data-lucide="lock" class="w-4 h-4 text-amber-400"></i>
                                        <!-- Badge pulsante -->
                                        <div class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-amber-400 rounded-full animate-ping"></div>
                                        <div class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-amber-400 rounded-full shadow-lg shadow-amber-400/50"></div>
                        </div>
                                    <div>
                                        <h3 class="text-xs font-bold text-white uppercase tracking-wider">Regras Ativas</h3>
                                        <p class="text-[10px] text-zinc-500 font-medium">IPs na lista negra</p>
                                    </div>
                                </div>
                                <span class="text-[10px] font-bold px-2.5 py-1 rounded-full modern-badge bg-amber-500/20 text-amber-400 border border-amber-500/40 flex items-center gap-1.5 shadow-lg shadow-amber-500/20">
                                    <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse shadow-lg shadow-amber-400/50"></span>
                                    Ativo
                                </span>
                            </div>
                            
                            <!-- Valor -->
                            <div class="mb-3">
                                <p class="text-3xl font-black text-white font-mono group-hover:text-amber-400 transition-colors duration-300 leading-none metric-value" data-stat="active-blocks">
                                    <?php echo number_format($stats['active_blocks']); ?>
                                </p>
                            </div>
                            
                            <!-- Mini indicador visual melhorado -->
                            <div class="h-1.5 bg-zinc-800/80 rounded-full overflow-hidden border border-white/5">
                                <div class="h-full bg-gradient-to-r from-amber-500 via-amber-600 to-amber-500 rounded-full animate-pulse shadow-lg shadow-amber-500/30" style="width: <?php echo min(100, ($stats['active_blocks'] > 0 ? 100 : 0)); ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top Countries -->
                <div class="glass-card rounded-xl p-6 <?php echo empty($topCountries) ? 'opacity-70' : ''; ?>">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="font-bold text-white text-lg">Top Países (7 dias)</h3>
                            <p class="text-xs text-zinc-500">Tráfego classificado por país de origem<?php echo $currentSiteId > 0 ? ' · site selecionado' : ' · todos os sites'; ?></p>
                        </div>
                    </div>
                    <?php if (empty($topCountries)): ?>
                        <p class="text-sm text-zinc-500 py-4 text-center">Sem dados suficientes para exibir.</p>
                    <?php else: ?>
                        <div class="space-y-4" data-stat="top-countries">
                            <?php foreach ($topCountries as $country): 
                                $code = strtoupper($country['country_code'] ?? '??');
                                $blocked = (int)($country['blocked_requests'] ?? 0);
                                $total = (int)($country['total_requests'] ?? 0);
                                $blockedPercent = $total > 0 ? round(($blocked / $total) * 100) : 0;
                            ?>
                            <div>
                                <div class="flex items-center justify-between text-sm mb-1">
                                    <div class="flex items-center gap-2">
                                        <span class="font-semibold text-white"><?php echo $code; ?></span>
                                        <span class="text-xs text-zinc-500"><?php echo $blocked > 0 ? "{$blockedPercent}% bloqueado" : "Seguro"; ?></span>
                                    </div>
                                    <div class="text-xs text-zinc-400 font-mono"><?php echo number_format($total); ?> req</div>
                                </div>
                                <div class="w-full h-1.5 bg-zinc-900 rounded-full overflow-hidden">
                                    <div class="h-full bg-blue-500" style="width: <?php echo min(100, $total > 0 ? ($total / max(1, $topCountries[0]['total_requests'])) * 100 : 0); ?>%"></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
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
                                <h3 class="font-bold text-white text-lg">Análise de Tráfego</h3>
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

                <!-- Mapa de Tráfego, Registro de Eventos e Incidentes -->
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
                        <div class="flex-1 p-3 space-y-3 overflow-y-auto font-mono text-[10px] max-h-[400px] relative <?php echo !$hasSites ? 'z-0' : 'z-10'; ?>" data-stat="event-logs">
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

                <!-- Incidentes Recentes e Top IPs -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Incidentes Recentes -->
                    <div class="lg:col-span-2 glass-card rounded-xl overflow-hidden">
                        <div class="p-6 border-b border-white/5 flex items-center justify-between">
                            <div>
                                <h3 class="font-bold text-white text-lg">Incidentes Recentes</h3>
                                <p class="text-xs text-zinc-500 mt-1">Agrupamento de múltiplos eventos por IP/tipo</p>
                            </div>
                            <a href="incidents.php" class="text-xs text-blue-400 hover:text-blue-300 font-semibold flex items-center gap-1">
                                Ver todos
                                <i data-lucide="arrow-right" class="w-3 h-3"></i>
                            </a>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left">
                                <thead class="bg-zinc-900/50 text-zinc-400 font-medium uppercase text-xs tracking-wider">
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
                                            <td colspan="7" class="px-6 py-6 text-center text-xs text-zinc-500">
                                                Nenhum incidente recente.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($recentIncidents as $incident): ?>
                                            <tr class="hover:bg-white/[0.02] transition-colors">
                                                <td class="px-6 py-4">
                                                    <?php if ($incident['status'] === 'open'): ?>
                                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-medium bg-red-500/10 text-red-400 border border-red-500/20">
                                                            <span class="w-1.5 h-1.5 rounded-full bg-red-400 mr-1 animate-pulse"></span>
                                                            Aberto
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-medium bg-zinc-800 text-zinc-400 border border-white/5">
                                                            Resolvido
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <span class="text-xs font-semibold text-white font-mono"><?php echo htmlspecialchars($incident['ip_address']); ?></span>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-[10px] font-medium bg-zinc-800 text-zinc-300 border border-white/5">
                                                        <?php echo strtoupper(str_replace('_', ' ', htmlspecialchars($incident['threat_type'] ?? 'unknown'))); ?>
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <span class="text-[11px] text-zinc-400 font-mono"><?php echo htmlspecialchars($incident['site_domain'] ?? '-'); ?></span>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <span class="text-sm text-zinc-200 font-semibold"><?php echo (int)$incident['total_events']; ?></span>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <span class="text-sm <?php echo $incident['critical_events'] > 0 ? 'text-red-400 font-semibold' : 'text-zinc-400'; ?>">
                                                        <?php echo (int)$incident['critical_events']; ?>
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <span class="text-[11px] text-zinc-400 font-mono">
                                                        <?php echo date('d/m H:i', strtotime($incident['last_seen'])); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Top IPs Bloqueados -->
                    <div class="glass-card rounded-xl overflow-hidden">
                        <div class="p-6 border-b border-white/5 flex items-center justify-between">
                            <h3 class="font-bold text-white text-lg">Top IPs Bloqueados</h3>
                            <span class="text-xs text-zinc-500">Últimos 7 dias</span>
                        </div>
                        <div class="p-4 space-y-3" data-stat="top-blocked-ips">
                            <?php if (empty($topBlockedIPs)): ?>
                                <p class="text-xs text-zinc-500 text-center py-4">Nenhum IP bloqueado recentemente.</p>
                            <?php else: ?>
                                <?php foreach ($topBlockedIPs as $ip): ?>
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-mono text-white"><?php echo htmlspecialchars($ip['ip_address']); ?></p>
                                            <p class="text-[11px] text-zinc-500">
                                                <?php echo htmlspecialchars($ip['threat_types']); ?>
                                            </p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm font-semibold text-red-400"><?php echo (int)$ip['block_count']; ?>x</p>
                                            <p class="text-[11px] text-zinc-500">
                                                <?php echo date('d/m H:i', strtotime($ip['last_blocked'])); ?>
                                            </p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
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

        // Função para animar contador
        function animateCounter(element, newValue, oldValue) {
            if (!element) return;
            const duration = 500;
            const startTime = Date.now();
            const startValue = oldValue || 0;
            const endValue = parseInt(newValue) || 0;
            
            function updateCounter() {
                const elapsed = Date.now() - startTime;
                const progress = Math.min(elapsed / duration, 1);
                const easeOutQuart = 1 - Math.pow(1 - progress, 4);
                const currentValue = Math.round(startValue + (endValue - startValue) * easeOutQuart);
                
                // Manter formato original se tiver M ou k
                const oldText = element.textContent;
                if (oldText.includes('M') || oldText.includes('k')) {
                    element.textContent = formatNumber(currentValue);
                } else {
                    element.textContent = currentValue.toLocaleString();
                }
                
                // Adicionar classe de animação
                element.classList.add('count-animate');
                setTimeout(() => element.classList.remove('count-animate'), 500);
                
                if (progress < 1) {
                    requestAnimationFrame(updateCounter);
                } else {
                    element.textContent = formatNumber(endValue);
                }
            }
            
            if (startValue !== endValue) {
                updateCounter();
            }
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
                
                // Atualizar card de Requisições/Visitas com animação
                const requests24h = data.last24h.total_requests;
                const requestsChange = data.changes.requests;
                const requestsCard = document.querySelector('[data-stat="total-requests"]');
                const requestsChangeBadge = document.querySelector('[data-stat="requests-change"]');
                if (requestsCard) {
                    const oldValue = parseInt(requestsCard.textContent.replace(/[kM]/g, '').replace(/,/g, '')) || 0;
                    animateCounter(requestsCard, requests24h, oldValue);
                }
                if (requestsChangeBadge) {
                    const arrowIcon = requestsChange >= 0 ? 'arrow-up-right' : 'arrow-down-right';
                    const colorClass = requestsChange >= 0 ? 'text-green-400' : 'text-zinc-500';
                    requestsChangeBadge.innerHTML = `<i data-lucide="${arrowIcon}" class="w-3 h-3"></i> ${Math.abs(requestsChange)}% vs. ontem`;
                    requestsChangeBadge.className = `text-[10px] ${colorClass} bg-${requestsChange >= 0 ? 'green' : 'zinc'}-900/30 px-2 py-1 rounded-full border border-${requestsChange >= 0 ? 'green' : 'zinc'}-900/40 flex items-center gap-1 font-medium`;
                    lucide.createIcons();
                }
                
                // Atualizar card de Ameaças Mitigadas com animação
                const blocked24h = data.last24h.blocked;
                const blockedCard = document.querySelector('[data-stat="blocked-threats"]');
                if (blockedCard) {
                    const oldValue = parseInt(blockedCard.textContent.replace(/[kM]/g, '').replace(/,/g, '')) || 0;
                    animateCounter(blockedCard, blocked24h, oldValue);
                }
                
                // Atualizar card de Latência
                const latencyCard = document.querySelector('[data-stat="latency"]');
                if (latencyCard) {
                    if (data.latency.global !== null) {
                        const newLatency = Math.round(data.latency.global);
                        const oldLatency = parseInt(latencyCard.textContent) || 0;
                        if (oldLatency !== newLatency) {
                            latencyCard.classList.add('count-animate');
                            setTimeout(() => latencyCard.classList.remove('count-animate'), 500);
                        }
                        latencyCard.textContent = newLatency + 'ms';
                    } else {
                        latencyCard.textContent = '--ms';
                    }
                }
                
                // Atualizar card de Visitantes Únicos com animação
                const uniqueIpsToday = data.today.unique_ips;
                const uniqueIpsElement = document.querySelector('[data-stat="unique-visitors"]');
                if (uniqueIpsElement) {
                    const oldValue = parseInt(uniqueIpsElement.textContent.replace(/,/g, '')) || 0;
                    if (oldValue !== uniqueIpsToday) {
                        uniqueIpsElement.classList.add('count-animate');
                        setTimeout(() => uniqueIpsElement.classList.remove('count-animate'), 500);
                    }
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
    
    <!-- Modal de Atualização do Sistema -->
    <div id="update-modal" 
         class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 backdrop-blur-sm"
         x-data="{ open: false }"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div class="w-full max-w-lg mx-4 rounded-2xl bg-zinc-950 border border-white/10 p-6 shadow-2xl relative overflow-hidden"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95">
            
            <!-- Decorative Background -->
            <div class="absolute inset-0 bg-gradient-to-br from-blue-500/5 via-purple-500/5 to-transparent pointer-events-none"></div>
            
            <!-- Close Button -->
            <button id="update-modal-close" class="absolute top-4 right-4 p-2 text-zinc-400 hover:text-white hover:bg-white/5 rounded-lg transition-all z-10">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
            
            <div class="relative z-10">
                <!-- Header -->
                <div class="text-center mb-6">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-blue-500/10 border border-blue-500/20 mb-4">
                        <i data-lucide="sparkles" class="w-8 h-8 text-blue-400"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-white mb-2">Sistema Atualizado! 🎉</h2>
                    <p class="text-zinc-400 text-sm">O SafeNode recebeu novas funcionalidades e melhorias</p>
                </div>
                
                <!-- Content -->
                <div class="mb-6">
                    <p class="text-zinc-300 text-sm text-center mb-4">
                        Estamos sempre trabalhando para melhorar sua experiência. A nova versão inclui:
                    </p>
                    <ul class="space-y-2 text-sm text-zinc-400">
                        <li class="flex items-start gap-2">
                            <i data-lucide="check" class="w-4 h-4 text-emerald-400 mt-0.5 flex-shrink-0"></i>
                            <span>Dashboard em tempo real com atualizações automáticas</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i data-lucide="check" class="w-4 h-4 text-emerald-400 mt-0.5 flex-shrink-0"></i>
                            <span>Novos recursos de segurança e melhorias de performance</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i data-lucide="check" class="w-4 h-4 text-emerald-400 mt-0.5 flex-shrink-0"></i>
                            <span>Interface mais intuitiva e responsiva</span>
                        </li>
                    </ul>
                </div>
                
                <!-- Actions -->
                <div class="flex flex-col sm:flex-row gap-3">
                    <button id="update-modal-understood" class="flex-1 px-4 py-3 rounded-xl bg-zinc-900 text-zinc-300 hover:bg-zinc-800 font-semibold transition-all text-sm">
                        Entendi
                    </button>
                    <button id="update-modal-see-updates" class="flex-1 px-4 py-3 rounded-xl bg-blue-600 text-white hover:bg-blue-700 font-semibold transition-all shadow-lg shadow-blue-500/20 flex items-center justify-center gap-2 text-sm">
                        <i data-lucide="sparkles" class="w-4 h-4"></i>
                        Ver Atualizações
                    </button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
