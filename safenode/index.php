<?php
/**
 * SafeNode - Página Inicial com Estatísticas Reais
 * Busca dados reais do banco de dados para exibir na landing page
 */

// Iniciar sessão se não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Carregar configuração do banco de dados
require_once __DIR__ . '/includes/config.php';

/**
 * Buscar estatísticas reais do sistema
 */
function getIndexStats() {
    $db = getSafeNodeDatabase();
    
    if (!$db) {
        // Retornar valores padrão se não conseguir conectar
        return [
            'total_requests_24h' => 0,
            'threats_blocked_24h' => 0,
            'avg_latency' => null,
            'total_requests_all' => 0,
            'threats_blocked_all' => 0,
            'uptime_percent' => 99.99
        ];
    }
    
    $stats = [
        'total_requests_24h' => 0,
        'threats_blocked_24h' => 0,
        'avg_latency' => null,
        'total_requests_all' => 0,
        'threats_blocked_all' => 0,
        'uptime_percent' => 99.99
    ];
    
    try {
        // Total de requisições nas últimas 24 horas
        $stmt = $db->query("
            SELECT 
                COUNT(*) as total_requests,
                SUM(CASE WHEN action_taken = 'blocked' THEN 1 ELSE 0 END) as blocked_requests
            FROM safenode_security_logs 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $stats['total_requests_24h'] = (int)($result['total_requests'] ?? 0);
            $stats['threats_blocked_24h'] = (int)($result['blocked_requests'] ?? 0);
        }
        
        // Total de requisições de todos os tempos (para estatísticas gerais)
        $stmt = $db->query("
            SELECT 
                COUNT(*) as total_requests,
                SUM(CASE WHEN action_taken = 'blocked' THEN 1 ELSE 0 END) as blocked_requests
            FROM safenode_security_logs
        ");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $stats['total_requests_all'] = (int)($result['total_requests'] ?? 0);
            $stats['threats_blocked_all'] = (int)($result['blocked_requests'] ?? 0);
        }
        
        // Latência média (últimas 24 horas)
        $stmt = $db->query("
            SELECT AVG(response_time) as avg_latency
            FROM safenode_security_logs 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            AND response_time IS NOT NULL
            AND response_time > 0
        ");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result && $result['avg_latency']) {
            $stats['avg_latency'] = round((float)$result['avg_latency'], 0);
        }
        
    } catch (PDOException $e) {
        error_log("SafeNode Index Stats Error: " . $e->getMessage());
    }
    
    return $stats;
}

/**
 * Buscar últimos logs de eventos para exibir no preview
 */
function getRecentEventLogs($limit = 5) {
    $db = getSafeNodeDatabase();
    
    if (!$db) {
        return [];
    }
    
    try {
        $stmt = $db->prepare("
            SELECT 
                created_at,
                action_taken,
                ip_address,
                threat_type,
                request_uri
            FROM safenode_security_logs 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("SafeNode Recent Logs Error: " . $e->getMessage());
        return [];
    }
}

// Buscar estatísticas
$indexStats = getIndexStats();

// Buscar logs recentes
$recentLogs = getRecentEventLogs(5);

/**
 * Buscar estatísticas de integração reais
 */
function getIntegrationStats() {
    $db = getSafeNodeDatabase();
    
    if (!$db) {
        return [
            'total_sites' => 0,
            'total_users' => 0,
            'total_api_keys' => 0,
            'active_sites' => 0
        ];
    }
    
    $stats = [
        'total_sites' => 0,
        'total_users' => 0,
        'total_api_keys' => 0,
        'active_sites' => 0
    ];
    
    try {
        // Total de sites
        $stmt = $db->query("SELECT COUNT(*) as total FROM safenode_sites");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $stats['total_sites'] = (int)($result['total'] ?? 0);
        }
        
        // Sites ativos
        $stmt = $db->query("SELECT COUNT(*) as total FROM safenode_sites WHERE is_active = 1");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $stats['active_sites'] = (int)($result['total'] ?? 0);
        }
        
        // Total de usuários
        $stmt = $db->query("SELECT COUNT(*) as total FROM safenode_users WHERE is_active = 1");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $stats['total_users'] = (int)($result['total'] ?? 0);
        }
        
        // Total de API keys (verificação humana)
        $stmt = $db->query("SELECT COUNT(*) as total FROM safenode_hv_api_keys WHERE is_active = 1");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $stats['total_api_keys'] = (int)($result['total'] ?? 0);
        }
    } catch (PDOException $e) {
        error_log("SafeNode Integration Stats Error: " . $e->getMessage());
    }
    
    return $stats;
}

// Buscar estatísticas de integração
$integrationStats = getIntegrationStats();

// Formatar números para exibição
function formatNumber($number) {
    if ($number >= 1000000) {
        return number_format($number / 1000000, 1) . 'M';
    } elseif ($number >= 1000) {
        return number_format($number / 1000, 1) . 'k';
    }
    return number_format($number);
}

// Formatar requisições totais
$totalRequests24h = formatNumber($indexStats['total_requests_24h']);
$threatsBlocked24h = formatNumber($indexStats['threats_blocked_24h']);
$avgLatency = $indexStats['avg_latency'] ? $indexStats['avg_latency'] . 'ms' : '34ms';

// Estatísticas gerais (para a seção de stats)
$totalRequestsAll = formatNumber($indexStats['total_requests_all']);
$threatsBlockedAll = formatNumber($indexStats['threats_blocked_all']);
$globalLatency = $indexStats['avg_latency'] ? $indexStats['avg_latency'] . 'ms' : '120ms';
$threatsPerDay = formatNumber($indexStats['threats_blocked_24h']);
?>
<!DOCTYPE html>
<html lang="pt-BR" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SafeNode - Plataforma Avançada de Segurança Cibernética e Proteção Web</title>
    <meta name="description" content="SafeNode é a plataforma mais completa para segurança cibernética. Proteção DDoS, WAF, monitoramento de ameaças em tempo real, firewall avançado e muito mais. Proteja sua infraestrutura digital com tecnologia de ponta.">
    <meta name="keywords" content="safenode, segurança cibernética, proteção DDoS, WAF, firewall web, segurança digital, proteção contra ataques, monitoramento de ameaças, segurança de aplicações, proteção de infraestrutura, segurança cloud, proteção de borda">
    <meta name="author" content="SafeNode">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://safenode.cloud/index.php">
    <meta property="og:title" content="SafeNode - Plataforma Avançada de Segurança Cibernética">
    <meta property="og:description" content="SafeNode oferece proteção avançada contra ameaças cibernéticas. Detecção de ameaças em tempo real, proteção contra ataques e monitoramento contínuo.">
    <meta property="og:image" content="https://safenode.cloud/assets/img/logos (5).png">
    <meta property="og:url" content="https://safenode.cloud/index.php">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="SafeNode">
    <meta property="og:locale" content="pt_BR">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="SafeNode - Plataforma Avançada de Segurança Cibernética">
    <meta name="twitter:description" content="SafeNode oferece proteção avançada contra ameaças cibernéticas. Detecção de ameaças em tempo real e proteção contra ataques.">
    <meta name="twitter:image" content="https://safenode.cloud/assets/img/logos (5).png">
    <meta name="apple-mobile-web-app-title" content="SafeNode">
    <meta name="application-name" content="SafeNode">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/img/logos (6).png">
    <link rel="shortcut icon" type="image/png" href="assets/img/logos (6).png">
    <link rel="apple-touch-icon" href="assets/img/logos (6).png">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <!-- Chart.js -->
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
                        border: "hsl(var(--border))",
                        input: "hsl(var(--input))",
                        ring: "hsl(var(--ring))",
                        background: "hsl(var(--background))",
                        foreground: "hsl(var(--foreground))",
                    },
                    backgroundImage: {
                        'grid-white': "linear-gradient(to right, rgba(255, 255, 255, 0.05) 1px, transparent 1px), linear-gradient(to bottom, rgba(255, 255, 255, 0.05) 1px, transparent 1px)",
                        'radial-fade': "radial-gradient(circle at center, rgba(0,0,0,0) 0%, #000000 100%)",
                    }
                }
            }
        }
    </script>

    <style>
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
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        ::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(255,255,255,0.15);
        }
        
        .glass-nav {
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .text-gradient {
            background: linear-gradient(to bottom right, #ffffff 0%, #9ca3af 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            color: transparent;
        }
        
        /* Stat Card */
        .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border-subtle);
            border-radius: 16px;
            padding: 20px;
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
            background: linear-gradient(90deg, transparent 0%, rgba(255,255,255,0.1) 50%, transparent 100%);
        }
        
        .stat-card:hover {
            border-color: var(--border-light);
            transform: translateY(-4px);
            box-shadow: 0 20px 40px -20px rgba(0,0,0,0.5), 0 0 60px -30px var(--accent-glow);
        }
        
        /* Chart Card */
        .chart-card {
            background: var(--bg-card);
            border: 1px solid var(--border-subtle);
            border-radius: 20px;
            padding: 20px;
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
        
        /* Table Card */
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
        
        /* Navigation Item */
        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
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
            height: 20px;
            background: var(--accent);
            border-radius: 0 4px 4px 0;
            box-shadow: 0 0 20px var(--accent-glow);
        }
        
        /* Upgrade Card */
        .upgrade-card {
            background: var(--bg-card);
            border: 1px solid var(--border-subtle);
            border-radius: 16px;
            padding: 16px;
        }
        
        /* Button Primary */
        .btn-primary {
            background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0.05) 100%);
            border: 1px solid rgba(255,255,255,0.1);
            color: white;
            font-weight: 600;
            border-radius: 12px;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .btn-primary::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.2) 0%, transparent 100%);
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .btn-primary:hover::before {
            opacity: 1;
        }
        
        .btn-primary:hover {
            border-color: rgba(255,255,255,0.2);
            transform: translateY(-2px);
            box-shadow: 0 10px 30px -10px rgba(255,255,255,0.2);
        }
        
        /* Button Ghost */
        .btn-ghost {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            color: var(--text-secondary);
            border-radius: 8px;
            transition: all 0.2s;
        }
        
        .btn-ghost:hover {
            background: rgba(255,255,255,0.1);
            border-color: rgba(255,255,255,0.2);
            color: var(--text-primary);
        }
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        /* Grid Background Animation */
        .bg-grid-pattern {
            background-size: 40px 40px;
            mask-image: linear-gradient(to bottom, transparent, 10%, white, 90%, transparent);
        }

        /* Added advanced animations for premium feel */
        @keyframes pulse-slow {
            0%, 100% { opacity: 0.4; }
            50% { opacity: 0.7; }
        }
        .animate-pulse-slow {
            animation: pulse-slow 4s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        .animate-float {
            animation: float 6s ease-in-out infinite;
        }

        @keyframes scan {
            0% { top: 0%; opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { top: 100%; opacity: 0; }
        }
        .animate-scan {
            animation: scan 3s linear infinite;
        }

        /* Added parallax and scroll-triggered animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .fade-in-up {
            animation: fadeInUp 0.8s ease-out forwards;
        }
        
        .parallax-slow {
            transition: transform 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }

        /* 3D Globe Animation */
        @keyframes rotate-globe {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .globe-container {
            perspective: 1000px;
        }
        
        .globe {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: radial-gradient(circle at 30% 30%, rgba(255,255,255,0.1), transparent 50%),
                        radial-gradient(circle at center, #0a0a0a, #000);
            border: 1px solid rgba(255,255,255,0.1);
            position: relative;
            transform-style: preserve-3d;
            box-shadow: 
                inset 0 0 40px rgba(0,0,0,0.8),
                0 0 60px rgba(255,255,255,0.05);
        }
        
        .globe::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 50%;
            background: 
                repeating-linear-gradient(
                    0deg,
                    transparent,
                    transparent 19px,
                    rgba(255,255,255,0.03) 19px,
                    rgba(255,255,255,0.03) 20px
                ),
                repeating-linear-gradient(
                    90deg,
                    transparent,
                    transparent 19px,
                    rgba(255,255,255,0.03) 19px,
                    rgba(255,255,255,0.03) 20px
                );
            animation: rotate-globe 60s linear infinite;
        }
        
        .globe::after {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 50%;
            background: radial-gradient(circle at 70% 70%, transparent 40%, rgba(0,0,0,0.6));
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .glass-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 40px rgba(0, 0, 0, 0.2);
        }
        
        .glow-text {
            text-shadow: 0 0 20px rgba(255, 255, 255, 0.3);
        }
        
        .code-syntax-keyword { color: #c678dd; }
        .code-syntax-string { color: #98c379; }
        .code-syntax-function { color: #61afef; }
        .code-syntax-comment { color: #5c6370; font-style: italic; }
        
        /* Added smooth hover transitions for all interactive elements */
        a, button {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Custom keyframes for network grid */
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }

        @keyframes dash {
            to { stroke-dashoffset: -1000; }
        }

        @keyframes networkFloat {
            0%, 100% { transform: translateY(0) scale(0.8); }
            50% { transform: translateY(-20px) scale(0.9); }
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* Added new animations for the traffic flow */
        @keyframes traffic-flow {
            0% { transform: translateX(0); opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { transform: translateX(100%); opacity: 0; }
        }
        
        @keyframes traffic-blocked {
            0% { transform: translateX(0); opacity: 0; }
            10% { opacity: 1; }
            50% { opacity: 1; }
            100% { transform: translateX(50%); opacity: 0; } /* Stops halfway */
        }

        .traffic-dot {
            position: absolute;
            width: 6px;
            height: 6px;
            border-radius: 50%;
            filter: blur(1px);
        }
        /* Added smooth scroll behavior for header */
        .glass-nav-scrolled {
            background: rgba(0, 0, 0, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.15);
        }

        /* Added logo marquee animation for social proof */
        @keyframes marquee {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }
        
        .animate-marquee {
            animation: marquee 30s linear infinite;
        }
        
        .animate-marquee:hover {
            animation-play-state: paused;
        }
        
        /* Logo Carousel - Desktop */
        .logos-carousel-infinite {
            overflow: hidden;
            width: 100%;
            position: relative;
            mask-image: linear-gradient(to right, transparent 0%, black 5%, black 95%, transparent 100%);
            -webkit-mask-image: linear-gradient(to right, transparent 0%, black 5%, black 95%, transparent 100%);
        }
        
        .logos-track {
            display: flex;
            gap: 3rem;
            width: fit-content;
            animation: scroll-logos 30s linear infinite;
            opacity: 0.4;
            will-change: transform;
        }
        
        .logos-track:hover {
            animation-play-state: paused;
            opacity: 0.7;
        }
        
        .logo-item {
            flex-shrink: 0;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .logo-item > div {
            cursor: pointer;
        }
        
        .logo-item > div:hover {
            border-color: rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.05);
        }
        
        @keyframes scroll-logos {
            0% {
                transform: translateX(0);
            }
            100% {
                transform: translateX(calc(-100% / 2));
            }
        }
        
        /* Logo Carousel - Mobile */
        .logos-carousel-infinite-mobile {
            overflow: hidden;
            width: 100%;
            position: relative;
            mask-image: linear-gradient(to right, transparent 0%, black 5%, black 95%, transparent 100%);
            -webkit-mask-image: linear-gradient(to right, transparent 0%, black 5%, black 95%, transparent 100%);
        }
        
        .logos-track-mobile {
            display: flex;
            gap: 1.5rem;
            width: fit-content;
            animation: scroll-logos-mobile 25s linear infinite;
            opacity: 0.4;
            will-change: transform;
        }
        
        .logos-track-mobile:hover {
            animation-play-state: paused;
            opacity: 0.7;
        }
        
        .logo-item-mobile {
            flex-shrink: 0;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .logo-item-mobile > div {
            cursor: pointer;
        }
        
        .logo-item-mobile > div:hover {
            border-color: rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.05);
        }
        
        @keyframes scroll-logos-mobile {
            0% {
                transform: translateX(0);
            }
            100% {
                transform: translateX(calc(-100% / 2));
            }
        }
        
        /* Flocos de Neve Natalinos */
        .snowflake {
            position: fixed;
            top: -10px;
            color: #ffffff;
            font-size: 1em;
            font-family: Arial, sans-serif;
            text-shadow: 0 0 5px rgba(255, 255, 255, 0.8);
            user-select: none;
            pointer-events: none;
            z-index: 9999;
            animation: snowfall linear infinite;
            opacity: 0.8;
        }
        
        @keyframes snowfall {
            0% {
                transform: translateY(0) translateX(0) rotate(0deg);
                opacity: 0.8;
            }
            100% {
                transform: translateY(100vh) translateX(50px) rotate(360deg);
                opacity: 0;
            }
        }
    </style>
</head>
<body class="bg-black text-white font-sans antialiased selection:bg-white selection:text-black">

    <!-- Enhanced Navigation with scroll detection and better styling -->
    <nav x-data="{ mobileMenuOpen: false, scrolled: false }" 
         @scroll.window="scrolled = (window.pageYOffset > 50)"
         class="fixed w-full z-50 glass-nav transition-all duration-500"
         :class="{ 'glass-nav-scrolled': scrolled }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <!-- Logo -->
                <a href="index.php" class="flex-shrink-0 flex items-center gap-3 cursor-pointer group">
                    <div class="relative">
                        <img src="assets/img/kron (1).png" alt="SafeNode" class="h-9 w-auto transition-transform duration-300 group-hover:scale-110">
                        <div class="absolute inset-0 bg-white/20 blur-xl opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    </div>
                    <span class="font-bold text-xl tracking-tight group-hover:text-white transition-colors">SafeNode</span>
                </a>

                <!-- Desktop Menu -->
                <div class="hidden md:flex items-center space-x-1">
                    <a href="#features" class="px-4 py-2 text-sm text-zinc-400 hover:text-white hover:bg-white/5 rounded-lg transition-all">Recursos</a>
                    <a href="#network" class="px-4 py-2 text-sm text-zinc-400 hover:text-white hover:bg-white/5 rounded-lg transition-all">Rede Global</a>
                    <a href="#pricing" class="px-4 py-2 text-sm text-zinc-400 hover:text-white hover:bg-white/5 rounded-lg transition-all">Planos</a>
                    <a href="#contact" class="px-4 py-2 text-sm text-zinc-400 hover:text-white hover:bg-white/5 rounded-lg transition-all">Contato</a>
                    <a href="docs.php" class="px-4 py-2 text-sm text-zinc-400 hover:text-white hover:bg-white/5 rounded-lg transition-all">Documentação</a>
                </div>

                <!-- CTA Button -->
                <div class="hidden md:flex items-center gap-3">
                    <a href="login.php" class="text-sm text-zinc-400 hover:text-white transition-colors">Login</a>
                    <a href="register.php" class="group relative inline-flex h-11 items-center justify-center overflow-hidden rounded-full bg-white px-7 font-semibold text-black transition-all duration-300 hover:bg-zinc-100 hover:shadow-[0_0_25px_rgba(255,255,255,0.3)]">
                        <span class="mr-2">Começar Grátis</span>
                        <i data-lucide="arrow-right" class="w-4 h-4 transition-transform group-hover:translate-x-1"></i>
                    </a>
                </div>

                <!-- Mobile Menu Button -->
                <div class="md:hidden flex items-center">
                    <button @click="mobileMenuOpen = !mobileMenuOpen" class="text-zinc-400 hover:text-white focus:outline-none transition-colors">
                        <i data-lucide="menu" class="w-6 h-6" x-show="!mobileMenuOpen"></i>
                        <i data-lucide="x" class="w-6 h-6" x-show="mobileMenuOpen" x-cloak></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div x-show="mobileMenuOpen" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-2"
             class="md:hidden bg-black/95 backdrop-blur-xl border-b border-zinc-800" x-cloak>
            <div class="px-4 pt-2 pb-6 space-y-2">
                <a href="#features" class="block px-4 py-3 rounded-xl text-base font-medium text-zinc-300 hover:text-white hover:bg-zinc-900 transition-all">Recursos</a>
                <a href="#network" class="block px-4 py-3 rounded-xl text-base font-medium text-zinc-300 hover:text-white hover:bg-zinc-900 transition-all">Rede Global</a>
                <a href="#pricing" class="block px-4 py-3 rounded-xl text-base font-medium text-zinc-300 hover:text-white hover:bg-zinc-900 transition-all">Planos</a>
                <a href="#contact" class="block px-4 py-3 rounded-xl text-base font-medium text-zinc-300 hover:text-white hover:bg-zinc-900 transition-all">Contato</a>
                <a href="docs.php" class="block px-4 py-3 rounded-xl text-base font-medium text-zinc-300 hover:text-white hover:bg-zinc-900 transition-all">Documentação</a>
                <a href="login.php" class="block px-4 py-3 rounded-xl text-base font-medium text-zinc-300 hover:text-white hover:bg-zinc-900 transition-all">Login</a>
                <a href="register.php" class="block px-4 py-3 mt-4 text-center rounded-full bg-white text-black font-bold hover:bg-zinc-100 transition-all">Começar Grátis</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="relative pt-32 pb-20 lg:pt-48 lg:pb-32 overflow-hidden">
        <!-- Background Image -->
        <div class="absolute inset-0 z-0">
            <img src="assets/img/fundoo.png" alt="Background" class="w-full h-full object-cover opacity-30">
            <div class="absolute inset-0 bg-gradient-to-b from-transparent via-black/70 to-black"></div>
        </div>
        
        <!-- Background Grid -->
        <div class="absolute inset-0 z-0 bg-grid-pattern bg-grid-white opacity-10 pointer-events-none"></div>
        
        <!-- Added ambient glow effects -->
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[800px] h-[500px] bg-white/5 rounded-full blur-[120px] pointer-events-none z-0"></div>

        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <!-- Improved status badge with 3D globe -->
            <div class="inline-flex items-center gap-3 px-4 py-2 rounded-full bg-zinc-900/50 border border-zinc-800 mb-8 backdrop-blur-sm hover:border-zinc-600 transition-colors cursor-default">
                <div class="globe-container">
                    <div class="globe" style="width: 24px; height: 24px;">
                        <div class="absolute inset-0 rounded-full">
                            <div class="absolute top-1/4 left-1/4 w-1 h-1 bg-green-400 rounded-full animate-pulse"></div>
                            <div class="absolute top-1/2 right-1/3 w-1 h-1 bg-green-400 rounded-full animate-pulse" style="animation-delay: 0.5s"></div>
                            <div class="absolute bottom-1/3 left-1/2 w-1 h-1 bg-green-400 rounded-full animate-pulse" style="animation-delay: 1s"></div>
                        </div>
                    </div>
                </div>
                <span class="text-xs font-medium text-zinc-300">Sistemas Operacionais: 100%</span>
            </div>
            
            <h1 class="text-5xl md:text-7xl font-bold tracking-tight mb-6 text-gradient max-w-5xl mx-auto leading-tight glow-text relative z-10" style="text-shadow: 0 0 30px rgba(255, 255, 255, 0.3);">
                A camada que conecta<br class="hidden md:block" /> código e infraestrutura.
            </h1>
            
            <p class="mt-6 text-xl text-zinc-400 max-w-2xl mx-auto mb-10 font-light leading-relaxed">
                SafeNode é a camada de comunicação e automação que se conecta direto à sua hospedagem e aplicação. 
                Sem complicação. Sem configurar SMTP manualmente. E-mails funcionando em 10 minutos.
            </p>
            
            <div class="flex flex-col sm:flex-row flex-wrap items-center justify-center gap-4">
                <a href="register.php" class="w-full sm:w-auto px-8 py-4 bg-white text-black rounded-full font-semibold hover:bg-zinc-200 transition-all transform hover:scale-105 flex items-center justify-center gap-2 shadow-[0_0_20px_rgba(255,255,255,0.3)] hover:shadow-[0_0_30px_rgba(255,255,255,0.5)]">
                    Começar Gratuitamente
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </a>
                <a href="safecode/landing/app" class="w-full sm:w-auto px-8 py-4 bg-zinc-900/80 text-white border border-white/10 rounded-full font-medium hover:bg-white hover:text-black transition-all flex items-center justify-center gap-2 backdrop-blur-sm group shadow-[0_0_20px_rgba(255,255,255,0.05)] hover:shadow-[0_0_30px_rgba(255,255,255,0.3)]">
                    <i data-lucide="code-2" class="w-4 h-4 transition-transform group-hover:scale-110"></i>
                    Instalar SafeCode IDE
                </a>
                <a href="#features" class="w-full sm:w-auto px-8 py-4 bg-zinc-900/50 text-white border border-zinc-800 rounded-full font-medium hover:bg-zinc-800 transition-all flex items-center justify-center gap-2 backdrop-blur-sm hover:border-zinc-600">
                    <i data-lucide="play-circle" class="w-4 h-4"></i>
                    Ver Demo
                </a>
            </div>

            <!-- Dashboard Preview (mobile simplificado + desktop completo) -->
            <!-- Mobile: versão compacta em coluna única -->
            <div class="mt-16 max-w-md mx-auto w-full md:hidden">
                <div class="relative rounded-3xl border border-zinc-800 bg-black/90 backdrop-blur-xl shadow-2xl overflow-hidden p-4 space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></div>
                            <span class="text-[10px] uppercase tracking-wider text-zinc-500 font-semibold">Conectado</span>
                        </div>
                        <span class="text-[10px] text-zinc-500 font-mono">safenode.cloud</span>
                    </div>
                    <div class="grid grid-cols-3 gap-3 text-left">
                        <div class="p-3 rounded-xl bg-zinc-900/60 border border-zinc-800">
                            <p class="text-[10px] text-zinc-500 mb-1">TOTAL DE REQUISIÇÕES</p>
                            <p class="text-xl font-bold"><?php echo htmlspecialchars($totalRequests24h); ?></p>
                            <p class="text-[10px] text-zinc-500 mt-1">Últimas 24h</p>
                        </div>
                        <div class="p-3 rounded-xl bg-zinc-900/60 border border-zinc-800">
                            <p class="text-[10px] text-zinc-500 mb-1">AMEAÇAS MITIGADAS</p>
                            <p class="text-xl font-bold text-red-400"><?php echo htmlspecialchars($threatsBlocked24h); ?></p>
                            <p class="text-[10px] text-zinc-500 mt-1">Bloqueado por IA</p>
                        </div>
                        <div class="p-3 rounded-xl bg-zinc-900/60 border border-zinc-800">
                            <p class="text-[10px] text-zinc-500 mb-1">LATÊNCIA GLOBAL</p>
                            <p class="text-xl font-bold"><?php echo htmlspecialchars($avgLatency); ?></p>
                            <p class="text-[10px] text-zinc-500 mt-1">P99</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 gap-3">
                        <div class="rounded-xl bg-[#050505] border border-zinc-800/60 p-3">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-[11px] text-zinc-300">Mapa de Tráfego</span>
                                <span class="text-[10px] text-zinc-500">1H</span>
                            </div>
                            <div class="relative h-32 bg-[linear-gradient(to_right,#80808008_1px,transparent_1px),linear-gradient(to_bottom,#80808008_1px,transparent_1px)] bg-[size:20px_20px] rounded-lg flex items-center justify-center overflow-hidden">
                                <div class="relative">
                                    <div class="w-10 h-10 rounded-full bg-zinc-900 border border-zinc-700 flex items-center justify-center shadow-[0_0_20px_rgba(16,185,129,0.4)]">
                                        <i data-lucide="server" class="w-4 h-4 text-green-500"></i>
                                    </div>
                                    <div class="absolute inset-0 -m-4 border border-green-500/10 rounded-full animate-[ping_3s_linear_infinite]"></div>
                                </div>
                            </div>
                        </div>
                        <div class="rounded-xl bg-zinc-950 border border-zinc-800/60 p-3">
                            <p class="text-[11px] text-zinc-300 mb-2">Registro de Eventos</p>
                            <div class="space-y-1 font-mono text-[10px] max-h-24 overflow-hidden">
                                <?php if (empty($recentLogs)): ?>
                                <div class="flex gap-2 opacity-60">
                                        <span class="text-zinc-500">--:--:--</span>
                                        <span class="text-zinc-400">Nenhum evento recente</span>
                                </div>
                                <?php else: ?>
                                    <?php foreach (array_slice($recentLogs, 0, 3) as $log): ?>
                                        <?php
                                        $time = date('H:i:s', strtotime($log['created_at']));
                                        $action = strtoupper($log['action_taken'] ?? 'allowed');
                                        $isBlocked = $action === 'BLOCKED';
                                        $isCritical = ($log['threat_type'] ?? '') && (stripos($log['threat_type'], 'ddos') !== false || stripos($log['threat_type'], 'rce') !== false);
                                        $displayText = $log['threat_type'] ?: $log['ip_address'];
                                        ?>
                                        <div class="flex gap-2 <?php echo $isCritical ? 'border-l-2 border-red-500 pl-2 bg-red-500/5' : ($isBlocked ? 'opacity-80' : 'opacity-60'); ?>">
                                            <span class="text-zinc-500"><?php echo htmlspecialchars($time); ?></span>
                                            <span class="<?php echo $isBlocked ? 'text-red-400' : ($isCritical ? 'text-red-500 font-bold' : 'text-emerald-400'); ?>">
                                                <?php echo $isCritical ? 'MITIGADO' : $action; ?>
                                            </span>
                                            <span class="text-zinc-400"><?php echo htmlspecialchars(substr($displayText, 0, 20)); ?></span>
                                </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Desktop: preview completo -->
            <div class="mt-24 relative max-w-6xl mx-auto hidden md:block">
                <div class="absolute -inset-1 bg-gradient-to-r from-zinc-700 via-zinc-500 to-zinc-700 rounded-2xl blur opacity-20 animate-pulse-slow"></div>
                <div class="relative rounded-2xl border border-zinc-800 bg-black/90 backdrop-blur-xl shadow-2xl overflow-hidden">
                    <!-- Window Controls -->
                    <div class="h-10 border-b border-zinc-800 flex items-center px-4 gap-2 bg-zinc-900/80">
                        <div class="flex gap-2">
                            <div class="w-3 h-3 rounded-full bg-red-500/20 border border-red-500/50 hover:bg-red-500 transition-colors"></div>
                            <div class="w-3 h-3 rounded-full bg-yellow-500/20 border border-yellow-500/50 hover:bg-yellow-500 transition-colors"></div>
                            <div class="w-3 h-3 rounded-full bg-green-500/20 border border-green-500/50 hover:bg-green-500 transition-colors"></div>
                        </div>
                        <div class="ml-auto flex items-center gap-2 px-3 py-1 rounded-full bg-black/50 border border-zinc-800">
                            <div class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></div>
                            <span class="text-[10px] text-zinc-400 font-mono tracking-wider">CONECTADO</span>
                        </div>
                    </div>
                    
                    <!-- Dashboard Content -->
                    <div class="flex flex-col" style="min-height: 500px; max-height: 550px;">
                        <div class="flex flex-1 overflow-hidden">
                        <!-- Sidebar -->
                            <aside class="w-72 flex-shrink-0 flex flex-col border-r border-white/5 bg-gradient-to-b from-[#080808] to-[#030303]">
                                <!-- Logo -->
                                <div class="p-4 border-b border-white/5">
                                    <div class="flex items-center gap-3">
                                        <div class="flex items-center gap-2">
                                            <img src="assets/img/logos (6).png" alt="SafeNode Logo" class="w-6 h-6 object-contain">
                                            <div>
                                                <h1 class="font-bold text-white text-base tracking-tight">SafeNode</h1>
                                                <p class="text-[10px] text-zinc-500 font-medium">Security Platform</p>
                                </div>
                                        </div>
                                        <button class="ml-auto text-zinc-600 hover:text-zinc-400 transition-colors">
                                            <i data-lucide="chevrons-left" class="w-4 h-4"></i>
                                        </button>
                                    </div>
                            </div>
                            
                                <!-- Navigation -->
                                <nav class="flex-1 p-5 space-y-2 overflow-y-auto">
                                    <p class="text-xs font-semibold text-zinc-600 uppercase tracking-wider mb-4 px-3">Menu Principal</p>
                                    
                                    <a href="#" class="nav-item active">
                                        <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
                                        <span class="font-medium">Home</span>
                                    </a>
                                    <a href="#" class="nav-item">
                                        <i data-lucide="globe" class="w-5 h-5"></i>
                                        <span class="font-medium">Gerenciar Sites</span>
                                    </a>
                                    <a href="#" class="nav-item">
                                        <i data-lucide="activity" class="w-5 h-5"></i>
                                        <span class="font-medium">Network</span>
                                    </a>
                                    <a href="#" class="nav-item">
                                        <i data-lucide="cpu" class="w-5 h-5"></i>
                                        <span class="font-medium">Kubernetes</span>
                                    </a>
                                    <a href="#" class="nav-item">
                                        <i data-lucide="compass" class="w-5 h-5"></i>
                                        <span class="font-medium">Explorar</span>
                                    </a>
                                    <a href="#" class="nav-item">
                                        <i data-lucide="bar-chart-3" class="w-5 h-5"></i>
                                        <span class="font-medium">Analisar</span>
                                    </a>
                                    <a href="#" class="nav-item">
                                        <i data-lucide="users-2" class="w-5 h-5"></i>
                                        <span class="font-medium">Grupos</span>
                                    </a>
                                    
                                    <div class="pt-6 mt-6 border-t border-white/5">
                                        <p class="text-xs font-semibold text-zinc-600 uppercase tracking-wider mb-4 px-3">Sistema</p>
                                        <a href="#" class="nav-item">
                                            <i data-lucide="shield-check" class="w-5 h-5"></i>
                                            <span class="font-medium">Verificação Humana</span>
                                        </a>
                                        <a href="#" class="nav-item">
                                            <i data-lucide="settings-2" class="w-5 h-5"></i>
                                            <span class="font-medium">Configurações</span>
                                        </a>
                                        <a href="#" class="nav-item">
                                            <i data-lucide="life-buoy" class="w-5 h-5"></i>
                                            <span class="font-medium">Ajuda</span>
                                        </a>
                                </div>
                                </nav>
                            </aside>
                            
                            <!-- Main Content -->
                            <main class="flex-1 flex flex-col overflow-hidden bg-dark-950">
                                <!-- Header -->
                                <header class="h-16 bg-dark-900/50 backdrop-blur-xl border-b border-white/5 px-5 flex items-center justify-between flex-shrink-0">
                                    <div class="flex items-center gap-4">
                                        <div>
                                            <h2 class="text-lg font-bold text-white tracking-tight">Dashboard</h2>
                                </div>
                                </div>

                                    <div class="flex items-center gap-3">
                                        <!-- Search -->
                                        <div class="relative hidden md:block">
                                            <i data-lucide="search" class="w-3.5 h-3.5 absolute left-3 top-1/2 -translate-y-1/2 text-zinc-500"></i>
                                            <input type="text" placeholder="Buscar..." class="bg-white/5 border border-white/10 rounded-lg py-1.5 pl-9 pr-3 text-xs text-white placeholder-zinc-500 focus:outline-none focus:border-white/30 w-32">
                                </div>
                                        
                                        <!-- Notifications -->
                                        <button class="relative p-2 text-zinc-400 hover:text-white hover:bg-white/5 rounded-lg transition-all">
                                            <i data-lucide="bell" class="w-4 h-4"></i>
                                            <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-white rounded-full border-2 border-dark-900 animate-pulse"></span>
                                        </button>
                                        
                                        <!-- Profile -->
                                        <button class="flex items-center gap-2 p-1.5 hover:bg-white/5 rounded-lg transition-all group">
                                            <div class="w-8 h-8 rounded-lg bg-white/5 flex items-center justify-center shadow-lg group-hover:scale-105 transition-transform overflow-hidden">
                                                <img src="assets/img/logos (6).png" alt="SafeNode" class="w-full h-full object-contain p-0.5">
                                            </div>
                                        </button>
                            </div>
                                </header>
                                
                                <!-- Main Area -->
                                <div class="flex-1 p-5 overflow-y-auto flex flex-col gap-5 bg-[#030303]">
                                    <!-- Stats Cards -->
                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                                <!-- Total Requests -->
                                <div class="stat-card group">
                                    <div class="flex items-center justify-between mb-1">
                                        <p class="text-xs font-medium text-zinc-400">Total de Requisições</p>
                                        <button class="text-zinc-600 hover:text-zinc-400 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <i data-lucide="more-vertical" class="w-3 h-3"></i>
                                        </button>
                                    </div>
                                    <div class="flex items-end justify-between mt-3">
                                        <p class="text-3xl font-bold text-white tracking-tight"><?php echo htmlspecialchars($totalRequests24h); ?></p>
                                        <span class="text-xs font-semibold text-white bg-white/10 px-2 py-0.5 rounded-lg">+0.0%</span>
                                    </div>
                                    <p class="text-[10px] text-zinc-600 mt-2">comparado a ontem</p>
                                </div>

                                <!-- Blocked -->
                                <div class="stat-card group">
                                    <div class="flex items-center justify-between mb-1">
                                        <p class="text-xs font-medium text-zinc-400">Requisições Bloqueadas</p>
                                        <button class="text-zinc-600 hover:text-zinc-400 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <i data-lucide="more-vertical" class="w-3 h-3"></i>
                                        </button>
                            </div>
                                    <div class="flex items-end justify-between mt-3">
                                        <p class="text-3xl font-bold text-white tracking-tight"><?php echo htmlspecialchars($threatsBlocked24h); ?></p>
                                        <span class="text-xs font-semibold text-red-400 bg-red-500/10 px-2 py-0.5 rounded-lg">+0.0%</span>
                                    </div>
                                    <p class="text-[10px] text-zinc-600 mt-2">Taxa: <span class="text-red-400 font-medium"><?php echo $indexStats['total_requests_24h'] > 0 ? round(($indexStats['threats_blocked_24h'] / $indexStats['total_requests_24h']) * 100, 1) : 0; ?>%</span></p>
                        </div>
                        
                                <!-- Unique IPs -->
                                <div class="stat-card group">
                                    <div class="flex items-center justify-between mb-1">
                                        <p class="text-xs font-medium text-zinc-400">Visitantes Únicos</p>
                                        <button class="text-zinc-600 hover:text-zinc-400 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <i data-lucide="more-vertical" class="w-3 h-3"></i>
                                        </button>
                                    </div>
                                    <div class="flex items-end justify-between mt-3">
                                        <p class="text-3xl font-bold text-white tracking-tight"><?php 
                                            $db = getSafeNodeDatabase();
                                            $uniqueIps = 0;
                                            if ($db) {
                                                try {
                                                    $stmt = $db->query("SELECT COUNT(DISTINCT ip_address) as total FROM safenode_security_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
                                                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                                                    $uniqueIps = (int)($result['total'] ?? 0);
                                                } catch (PDOException $e) {}
                                            }
                                            echo formatNumber($uniqueIps);
                                        ?></p>
                                        <span class="text-xs font-semibold text-white bg-white/10 px-2 py-0.5 rounded-lg">+0.0%</span>
                                </div>
                                    <p class="text-[10px] text-zinc-600 mt-2">últimas 24h</p>
                                    </div>
                                
                                <!-- Active Blocks -->
                                <div class="stat-card group">
                                    <div class="flex items-center justify-between mb-1">
                                        <p class="text-xs font-medium text-zinc-400">IPs Bloqueados</p>
                                        <button class="text-zinc-600 hover:text-zinc-400 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <i data-lucide="more-vertical" class="w-3 h-3"></i>
                                        </button>
                                </div>
                                    <div class="flex items-end justify-between mt-3">
                                        <p class="text-3xl font-bold text-white tracking-tight"><?php 
                                            $activeBlocks = 0;
                                            if ($db) {
                                                try {
                                                    $stmt = $db->query("SELECT COUNT(DISTINCT ip_address) as total FROM safenode_security_logs WHERE action_taken = 'blocked' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
                                                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                                                    $activeBlocks = (int)($result['total'] ?? 0);
                                                } catch (PDOException $e) {}
                                            }
                                            echo formatNumber($activeBlocks);
                                        ?></p>
                                        <span class="text-xs font-semibold text-amber-400 bg-amber-500/10 px-2 py-0.5 rounded-lg">ativos</span>
                                    </div>
                                    <p class="text-[10px] text-zinc-600 mt-2">últimos 7 dias</p>
                                </div>
                            </div>
                            
                                    <!-- Charts Row -->
                                    <div class="grid grid-cols-1 lg:grid-cols-5 gap-5">
                                <!-- Entities Overview (Donut Chart) -->
                                <div class="lg:col-span-2 chart-card">
                                    <div class="flex items-center justify-between mb-6">
                                        <h3 class="text-base font-semibold text-white">Visão Geral de Ameaças</h3>
                                        <button class="text-zinc-600 hover:text-zinc-400 transition-colors">
                                            <i data-lucide="more-vertical" class="w-3 h-3"></i>
                                        </button>
                                    </div>
                                    <div class="flex items-center justify-center">
                                        <div class="relative" style="width: 150px; height: 150px;">
                                            <canvas id="previewEntitiesChart" width="150" height="150"></canvas>
                                            <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                                                <span id="previewTotalScore" class="text-3xl font-bold text-white"><?php echo $indexStats['total_requests_24h'] > 0 ? round((($indexStats['total_requests_24h'] - $indexStats['threats_blocked_24h']) / $indexStats['total_requests_24h']) * 100) : 100; ?></span>
                                                <span class="text-[10px] text-zinc-500 font-medium mt-1">Total Score</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-center gap-6 mt-6">
                                        <div class="flex items-center gap-2">
                                            <span class="w-2.5 h-2.5 rounded-full bg-white"></span>
                                            <span class="text-xs text-zinc-400">Good</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                                            <span class="text-xs text-zinc-400">Moderate</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="w-2.5 h-2.5 rounded-full bg-violet-500"></span>
                                            <span class="text-xs text-zinc-400">Bad</span>
                                    </div>
                                            </div>
                                        </div>

                                <!-- Network Anomalies (Bar Chart) -->
                                <div class="lg:col-span-3 chart-card">
                                    <div class="flex items-center justify-between mb-6">
                                        <h3 class="text-base font-semibold text-white">Anomalias de Rede</h3>
                                        <div class="flex items-center gap-1 bg-white/5 rounded-xl p-1">
                                            <button class="px-2 py-0.5 text-[10px] rounded-lg bg-white/10 text-white">1S</button>
                                            <button class="px-2 py-0.5 text-[10px] rounded-lg text-zinc-500 hover:text-zinc-400">1M</button>
                                            <button class="px-2 py-0.5 text-[10px] rounded-lg text-zinc-500 hover:text-zinc-400">1A</button>
                                        </div>
                                    </div>
                                    <div class="relative" style="height: 150px;">
                                        <div class="absolute inset-0 flex items-end justify-center gap-1.5 px-3">
                                            <div class="flex-1 h-3/4 bg-white/10 rounded-t"></div>
                                            <div class="flex-1 h-2/3 bg-white/10 rounded-t"></div>
                                            <div class="flex-1 h-4/5 bg-white/10 rounded-t"></div>
                                            <div class="flex-1 h-1/2 bg-white/10 rounded-t"></div>
                                            <div class="flex-1 h-3/4 bg-white/10 rounded-t"></div>
                                            <div class="flex-1 h-2/3 bg-white/10 rounded-t"></div>
                                            <div class="flex-1 h-4/5 bg-white/10 rounded-t"></div>
                                </div>
                                    </div>
                                        </div>
                                        </div>
                                    
                                    <!-- Network Devices Table -->
                                    <div class="table-card">
                                <div class="table-header p-4 flex items-center justify-between">
                                    <h3 class="text-base font-semibold text-white">Dispositivos de Rede</h3>
                                    <div class="flex items-center gap-2">
                                        <div class="relative">
                                            <i data-lucide="search" class="w-3 h-3 absolute left-3 top-1/2 -translate-y-1/2 text-zinc-500"></i>
                                            <input type="text" placeholder="Buscar por nome" class="bg-white/5 border border-white/10 rounded-lg py-1.5 pl-8 pr-3 text-xs text-white placeholder-zinc-500 focus:outline-none focus:border-white/30 w-40">
                                        </div>
                                        <button class="btn-ghost flex items-center gap-1.5 text-xs px-3 py-1.5">
                                            <span>Buscar</span>
                                        </button>
                                        <button class="btn-ghost flex items-center gap-1.5 text-xs px-3 py-1.5">
                                            <i data-lucide="sliders-horizontal" class="w-3 h-3"></i>
                                            <span>Filtrar</span>
                                        </button>
                                        </div>
                                        </div>
                                <div class="overflow-x-auto">
                                    <table class="w-full">
                                        <thead>
                                            <tr class="text-left text-[10px] text-zinc-500 uppercase tracking-wider border-b border-white/5">
                                                <th class="px-4 py-3 font-semibold">Health</th>
                                                <th class="px-4 py-3 font-semibold">Nome</th>
                                                <th class="px-4 py-3 font-semibold">Tipo</th>
                                                <th class="px-4 py-3 font-semibold">Origem</th>
                                                <th class="px-4 py-3 font-semibold">Response Time</th>
                                                <th class="px-4 py-3 font-semibold">Packet Loss</th>
                                                <th class="px-4 py-3 font-semibold">Ação</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr class="table-row">
                                                <td colspan="7" class="px-4 py-8 text-center text-zinc-500">
                                                    <div class="flex flex-col items-center">
                                                        <div class="w-10 h-10 rounded-xl bg-white/5 flex items-center justify-center mb-2">
                                                            <i data-lucide="loader-2" class="w-5 h-5 animate-spin"></i>
                                    </div>
                                                        <p class="text-xs font-medium">Carregando dispositivos...</p>
                                </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                            </div>
                                    </div>
                                </div>
                            </main>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Social Proof -->
    <section class="py-16 border-y border-zinc-900 bg-black relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-r from-black via-transparent to-black pointer-events-none z-10"></div>
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <p class="text-center text-xs text-zinc-500 mb-10 font-semibold uppercase tracking-[0.2em]">Confiado por empresas inovadoras</p>
            
            <div class="relative overflow-hidden">
                <!-- Desktop Carousel -->
                <div class="hidden md:block">
                    <div class="logos-carousel-infinite">
                        <div class="logos-track">
                            <!-- Primeira série de logos -->
                            <div class="logo-item">
                                <div class="px-8 py-4 rounded-xl border border-zinc-800/50 bg-zinc-900/20 backdrop-blur-sm transition-all duration-300">
                                    <span class="text-2xl font-bold text-white tracking-tight">ACME</span>
                                </div>
                            </div>
                            <div class="logo-item">
                                <div class="px-8 py-4 rounded-xl border border-zinc-800/50 bg-zinc-900/20 backdrop-blur-sm transition-all duration-300">
                                    <span class="text-2xl font-bold text-white tracking-tight">Globex</span>
                                </div>
                            </div>
                            <div class="logo-item">
                                <div class="px-8 py-4 rounded-xl border border-zinc-800/50 bg-zinc-900/20 backdrop-blur-sm transition-all duration-300">
                                    <span class="text-2xl font-bold text-white tracking-tight">Soylent</span>
                                </div>
                            </div>
                            <div class="logo-item">
                                <div class="px-8 py-4 rounded-xl border border-zinc-800/50 bg-zinc-900/20 backdrop-blur-sm transition-all duration-300">
                                    <span class="text-2xl font-bold text-white tracking-tight">Lactech</span>
                                </div>
                            </div>
                            <div class="logo-item">
                                <div class="px-8 py-4 rounded-xl border border-zinc-800/50 bg-zinc-900/20 backdrop-blur-sm transition-all duration-300">
                                    <span class="text-2xl font-bold text-white tracking-tight">Umbrella</span>
                                </div>
                            </div>
                            <div class="logo-item">
                                <div class="px-8 py-4 rounded-xl border border-zinc-800/50 bg-zinc-900/20 backdrop-blur-sm transition-all duration-300">
                                    <span class="text-2xl font-bold text-white tracking-tight">Nordpetro</span>
                                </div>
                            </div>
                            <div class="logo-item">
                                <div class="px-8 py-4 rounded-xl border border-zinc-800/50 bg-zinc-900/20 backdrop-blur-sm transition-all duration-300">
                                    <span class="text-2xl font-bold text-white tracking-tight">Denfy</span>
                                </div>
                            </div>
                            <!-- Duplicar para scroll infinito -->
                            <div class="logo-item">
                                <div class="px-8 py-4 rounded-xl border border-zinc-800/50 bg-zinc-900/20 backdrop-blur-sm transition-all duration-300">
                                    <span class="text-2xl font-bold text-white tracking-tight">ACME</span>
                                </div>
                            </div>
                            <div class="logo-item">
                                <div class="px-8 py-4 rounded-xl border border-zinc-800/50 bg-zinc-900/20 backdrop-blur-sm transition-all duration-300">
                                    <span class="text-2xl font-bold text-white tracking-tight">Globex</span>
                                </div>
                            </div>
                            <div class="logo-item">
                                <div class="px-8 py-4 rounded-xl border border-zinc-800/50 bg-zinc-900/20 backdrop-blur-sm transition-all duration-300">
                                    <span class="text-2xl font-bold text-white tracking-tight">Soylent</span>
                                </div>
                            </div>
                            <div class="logo-item">
                                <div class="px-8 py-4 rounded-xl border border-zinc-800/50 bg-zinc-900/20 backdrop-blur-sm transition-all duration-300">
                                    <span class="text-2xl font-bold text-white tracking-tight">Lactech</span>
                                </div>
                            </div>
                            <div class="logo-item">
                                <div class="px-8 py-4 rounded-xl border border-zinc-800/50 bg-zinc-900/20 backdrop-blur-sm transition-all duration-300">
                                    <span class="text-2xl font-bold text-white tracking-tight">Umbrella</span>
                                </div>
                            </div>
                            <div class="logo-item">
                                <div class="px-8 py-4 rounded-xl border border-zinc-800/50 bg-zinc-900/20 backdrop-blur-sm transition-all duration-300">
                                    <span class="text-2xl font-bold text-white tracking-tight">Nordpetro</span>
                                </div>
                            </div>
                            <div class="logo-item">
                                <div class="px-8 py-4 rounded-xl border border-zinc-800/50 bg-zinc-900/20 backdrop-blur-sm transition-all duration-300">
                                    <span class="text-2xl font-bold text-white tracking-tight">Denfy</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Mobile Carousel -->
                <div class="md:hidden">
                    <div class="logos-carousel-infinite-mobile">
                        <div class="logos-track-mobile">
                            <!-- Primeira série de logos (mobile) -->
                            <div class="logo-item-mobile">
                                <div class="px-6 py-3 rounded-xl border border-zinc-800/50 bg-zinc-900/20 backdrop-blur-sm transition-all duration-300">
                                    <span class="text-xl font-bold text-white tracking-tight">ACME</span>
                                </div>
                            </div>
                            <div class="logo-item-mobile">
                                <div class="px-6 py-3 rounded-xl border border-zinc-800/50 bg-zinc-900/20 backdrop-blur-sm transition-all duration-300">
                                    <span class="text-xl font-bold text-white tracking-tight">Globex</span>
                                </div>
                            </div>
                            <div class="logo-item-mobile">
                                <div class="px-6 py-3 rounded-xl border border-zinc-800/50 bg-zinc-900/20 backdrop-blur-sm transition-all duration-300">
                                    <span class="text-xl font-bold text-white tracking-tight">Soylent</span>
                                </div>
                            </div>
                            <div class="logo-item-mobile">
                                <div class="px-6 py-3 rounded-xl border border-zinc-800/50 bg-zinc-900/20 backdrop-blur-sm transition-all duration-300">
                                    <span class="text-xl font-bold text-white tracking-tight">Lactech</span>
                                </div>
                            </div>
                            <div class="logo-item-mobile">
                                <div class="px-6 py-3 rounded-xl border border-zinc-800/50 bg-zinc-900/20 backdrop-blur-sm transition-all duration-300">
                                    <span class="text-xl font-bold text-white tracking-tight">Umbrella</span>
                                </div>
                            </div>
                            <div class="logo-item-mobile">
                                <div class="px-6 py-3 rounded-xl border border-zinc-800/50 bg-zinc-900/20 backdrop-blur-sm transition-all duration-300">
                                    <span class="text-xl font-bold text-white tracking-tight">Nordpetro</span>
                                </div>
                            </div>
                            <div class="logo-item-mobile">
                                <div class="px-6 py-3 rounded-xl border border-zinc-800/50 bg-zinc-900/20 backdrop-blur-sm transition-all duration-300">
                                    <span class="text-xl font-bold text-white tracking-tight">Denfy</span>
                                </div>
                            </div>
                            <!-- Duplicar para scroll infinito (mobile) -->
                            <div class="logo-item-mobile">
                                <div class="px-6 py-3 rounded-xl border border-zinc-800/50 bg-zinc-900/20 backdrop-blur-sm transition-all duration-300">
                                    <span class="text-xl font-bold text-white tracking-tight">ACME</span>
                                </div>
                            </div>
                            <div class="logo-item-mobile">
                                <div class="px-6 py-3 rounded-xl border border-zinc-800/50 bg-zinc-900/20 backdrop-blur-sm transition-all duration-300">
                                    <span class="text-xl font-bold text-white tracking-tight">Globex</span>
                                </div>
                            </div>
                            <div class="logo-item-mobile">
                                <div class="px-6 py-3 rounded-xl border border-zinc-800/50 bg-zinc-900/20 backdrop-blur-sm transition-all duration-300">
                                    <span class="text-xl font-bold text-white tracking-tight">Soylent</span>
                                </div>
                            </div>
                            <div class="logo-item-mobile">
                                <div class="px-6 py-3 rounded-xl border border-zinc-800/50 bg-zinc-900/20 backdrop-blur-sm transition-all duration-300">
                                    <span class="text-xl font-bold text-white tracking-tight">Lactech</span>
                                </div>
                            </div>
                            <div class="logo-item-mobile">
                                <div class="px-6 py-3 rounded-xl border border-zinc-800/50 bg-zinc-900/20 backdrop-blur-sm transition-all duration-300">
                                    <span class="text-xl font-bold text-white tracking-tight">Umbrella</span>
                                </div>
                            </div>
                            <div class="logo-item-mobile">
                                <div class="px-6 py-3 rounded-xl border border-zinc-800/50 bg-zinc-900/20 backdrop-blur-sm transition-all duration-300">
                                    <span class="text-xl font-bold text-white tracking-tight">Nordpetro</span>
                                </div>
                            </div>
                            <div class="logo-item-mobile">
                                <div class="px-6 py-3 rounded-xl border border-zinc-800/50 bg-zinc-900/20 backdrop-blur-sm transition-all duration-300">
                                    <span class="text-xl font-bold text-white tracking-tight">Denfy</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Enhanced Architecture Visualization with better animations and depth -->
    <section class="py-32 bg-black relative overflow-hidden" x-data="{ inView: false }" x-intersect="inView = true">
        <!-- Added a subtle background gradient for depth -->
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_center,_var(--tw-gradient-stops))] from-blue-900/10 via-black to-black pointer-events-none"></div>
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="text-center mb-20" :class="{ 'fade-in-up': inView }">
                <h2 class="text-3xl md:text-5xl font-bold mb-6 tracking-tight flex items-center justify-center gap-3 flex-wrap">
                    Como o <span class="flex items-center gap-2"><img src="assets/img/logos (6).png" alt="SafeNode" class="h-12 md:h-16 w-auto">SafeNode</span> protege você
                </h2>
                <p class="text-zinc-400 max-w-2xl mx-auto text-lg">Nossa arquitetura de proxy reverso intercepta todo o tráfego antes que ele chegue ao seu servidor.</p>
            </div>

            <div class="relative max-w-5xl mx-auto">
                <!-- Redesigned the flow to be a clean, organized linear process with explicit connecting lines </CHANGE> -->
                <div class="flex flex-col md:flex-row items-center justify-between relative z-10 gap-12 md:gap-0">
                    
                    <!-- Step 1: Visitors -->
                    <div class="flex flex-col items-center text-center group relative z-20 w-full md:w-auto" :class="{ 'fade-in-up': inView }" style="animation-delay: 0.1s">
                        <div class="w-24 h-24 rounded-2xl bg-zinc-900 border border-zinc-800 flex items-center justify-center mb-4 shadow-lg group-hover:border-zinc-600 transition-all duration-300 relative">
                            <i data-lucide="users" class="w-10 h-10 text-zinc-400 group-hover:text-white transition-colors"></i>
                            <!-- Badge -->
                            <div class="absolute -top-3 -right-3 bg-zinc-800 border border-zinc-700 text-zinc-400 text-[10px] px-2 py-1 rounded-full">
                                Internet
                            </div>
                        </div>
                        <h3 class="text-lg font-bold text-white">Visitantes</h3>
                        <p class="text-sm text-zinc-500 mt-1">Tráfego Misto</p>
                    </div>

                    <!-- Connector 1 (Visitors -> SafeNode) -->
                    <div class="hidden md:flex flex-1 items-center justify-center relative px-4 h-24">
                        <!-- Line -->
                        <div class="w-full h-[2px] bg-zinc-800 relative overflow-hidden rounded-full">
                            <!-- Moving particles (Red/Green mix representing mixed traffic) -->
                            <div class="absolute top-0 left-0 w-1/3 h-full bg-gradient-to-r from-transparent via-zinc-500 to-transparent animate-[traffic-flow_1.5s_linear_infinite]"></div>
                        </div>
                        <!-- Arrow Head -->
                        <i data-lucide="chevron-right" class="absolute right-4 text-zinc-600 w-6 h-6"></i>
                    </div>

                    <!-- Mobile Connector (Down Arrow) -->
                    <div class="md:hidden flex flex-col items-center justify-center h-16 -my-4">
                        <div class="h-full w-[2px] bg-zinc-800 relative overflow-hidden">
                             <div class="absolute top-0 left-0 w-full h-1/3 bg-gradient-to-b from-transparent via-zinc-500 to-transparent animate-[scan_1.5s_linear_infinite]"></div>
                        </div>
                        <i data-lucide="chevron-down" class="text-zinc-600 w-6 h-6 -mt-1"></i>
                    </div>

                    <!-- Step 2: SafeNode Edge (Centerpiece) -->
                    <div class="flex flex-col items-center text-center relative z-20 w-full md:w-auto" :class="{ 'fade-in-up': inView }" style="animation-delay: 0.3s">
                        <!-- Glow effect behind -->
                        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-40 h-40 bg-blue-500/20 rounded-full blur-3xl animate-pulse-slow"></div>
                        
                        <div class="w-32 h-32 rounded-full bg-black border-2 border-blue-500/50 flex items-center justify-center mb-4 shadow-[0_0_30px_rgba(59,130,246,0.3)] relative z-10">
                            <!-- Rotating ring -->
                            <div class="absolute inset-1 rounded-full border border-dashed border-blue-500/30 animate-[spin_10s_linear_infinite]"></div>
                            
                            <div class="bg-zinc-900 p-4 rounded-xl border border-zinc-800">
                                <img src="assets/img/logos (6).png" alt="SafeNode" class="w-12 h-12 object-contain">
                            </div>

                            <!-- Shield Badge -->
                            <div class="absolute -bottom-2 bg-blue-600 text-white text-[10px] font-bold px-3 py-1 rounded-full shadow-lg border border-blue-400">
                                FILTERING
                            </div>
                        </div>
                        <h3 class="text-xl font-bold text-white mt-2">SafeNode Edge</h3>
                        <p class="text-sm text-blue-400 mt-1">Ameaças Bloqueadas</p>
                    </div>

                    <!-- Connector 2 (SafeNode -> Server) -->
                    <div class="hidden md:flex flex-1 items-center justify-center relative px-4 h-24">
                        <!-- Line -->
                        <div class="w-full h-[2px] bg-zinc-800 relative overflow-hidden rounded-full">
                            <!-- Moving particles (Green only representing clean traffic) -->
                            <div class="absolute top-0 left-0 w-1/3 h-full bg-gradient-to-r from-transparent via-green-500 to-transparent animate-[traffic-flow_1.5s_linear_infinite]"></div>
                        </div>
                        <!-- Arrow Head -->
                        <i data-lucide="chevron-right" class="absolute right-4 text-green-500 w-6 h-6"></i>
                    </div>

                    <!-- Mobile Connector (Down Arrow) -->
                    <div class="md:hidden flex flex-col items-center justify-center h-16 -my-4">
                        <div class="h-full w-[2px] bg-zinc-800 relative overflow-hidden">
                             <div class="absolute top-0 left-0 w-full h-1/3 bg-gradient-to-b from-transparent via-green-500 to-transparent animate-[scan_1.5s_linear_infinite]"></div>
                        </div>
                        <i data-lucide="chevron-down" class="text-green-500 w-6 h-6 -mt-1"></i>
                    </div>

                    <!-- Step 3: Your Server -->
                    <div class="flex flex-col items-center text-center group relative z-20 w-full md:w-auto" :class="{ 'fade-in-up': inView }" style="animation-delay: 0.5s">
                        <div class="w-24 h-24 rounded-2xl bg-zinc-900 border border-zinc-800 flex items-center justify-center mb-4 shadow-lg group-hover:border-green-500/50 transition-all duration-300 relative">
                            <i data-lucide="server" class="w-10 h-10 text-zinc-400 group-hover:text-green-500 transition-colors"></i>
                            <!-- Badge -->
                            <div class="absolute -top-3 -right-3 bg-green-900/20 border border-green-900/50 text-green-400 text-[10px] px-2 py-1 rounded-full flex items-center gap-1">
                                <i data-lucide="check" class="w-3 h-3"></i> Clean
                            </div>
                        </div>
                        <h3 class="text-lg font-bold text-white">Seu Servidor</h3>
                        <p class="text-sm text-zinc-500 mt-1">Seguro & Otimizado</p>
                    </div>

                </div>
            </div>
        </div>
    </section>

    <!-- Global Network Map Section -->
    <section id="network" class="py-24 bg-black relative overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-5xl font-bold mb-6 tracking-tight text-white">
                    Rede Global
                </h2>
                <p class="text-sm md:text-lg text-zinc-400 max-w-2xl mx-auto">
                    Conecte-se com equipes e clientes em todo o mundo. Nossa plataforma permite colaboração perfeita entre continentes, trazendo o mundo para o seu workspace.
                </p>
            </div>

            <div class="relative w-full" style="aspect-ratio: 2/1; min-height: 400px; max-height: 600px;">
                <!-- World Map Container -->
                <div id="world-map-container" class="w-full h-full bg-black rounded-2xl border border-zinc-800 relative overflow-hidden" style="position: relative;">
                    <!-- Map Background (SVG with dots pattern) -->
                    <svg id="world-map-svg" class="w-full h-full" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;" viewBox="0 0 800 400" preserveAspectRatio="xMidYMid meet">
                        <defs>
                            <pattern id="map-dots" x="0" y="0" width="20" height="20" patternUnits="userSpaceOnUse">
                                <circle cx="10" cy="10" r="0.5" fill="rgba(255,255,255,0.15)" />
                            </pattern>
                            <linearGradient id="path-gradient" x1="0%" y1="0%" x2="100%" y2="0%">
                                <stop offset="0%" stop-color="#0ea5e9" stop-opacity="0" />
                                <stop offset="10%" stop-color="#0ea5e9" stop-opacity="1" />
                                <stop offset="90%" stop-color="#0ea5e9" stop-opacity="1" />
                                <stop offset="100%" stop-color="#0ea5e9" stop-opacity="0" />
                            </linearGradient>
                            <filter id="glow">
                                <feGaussianBlur stdDeviation="1.5" result="coloredBlur"/>
                                <feMerge>
                                    <feMergeNode in="coloredBlur"/>
                                    <feMergeNode in="SourceGraphic"/>
                                </feMerge>
                            </filter>
                        </defs>
                        <rect width="800" height="400" fill="#000000" />
                        <rect width="800" height="400" fill="url(#map-dots)" />
                        
                        <!-- Connection paths will be added here by JavaScript -->
                        <g id="connections-group"></g>
                        
                        <!-- City markers will be added here by JavaScript -->
                        <g id="cities-group"></g>
                    </svg>

                    <!-- Tooltip for mobile -->
                    <div id="map-tooltip" class="absolute bottom-4 left-4 bg-black/90 text-white px-3 py-2 rounded-lg text-sm font-medium backdrop-blur-sm sm:hidden border border-zinc-700 opacity-0 transition-opacity pointer-events-none z-50">
                        <span id="tooltip-text"></span>
                    </div>
                </div>
            </div>
        </div>

        <style>
            @keyframes pathDraw {
                0% {
                    stroke-dashoffset: 1000;
                }
                100% {
                    stroke-dashoffset: 0;
                }
            }

            .map-path {
                stroke-dasharray: 1000;
                stroke-dashoffset: 1000;
                animation: pathDraw 2s ease-in-out forwards;
            }
        </style>

        <script>
            (function() {
                // World Map Configuration
                const mapConfig = {
                    width: 800,
                    height: 400,
                    lineColor: '#0ea5e9',
                    animationDuration: 2000, // ms
                    staggerDelay: 400, // ms
                    pauseTime: 2500 // ms
                };

                // Cities/Locations data
                const connections = [
                    {
                        start: { lat: 64.2008, lng: -149.4937, label: "Fairbanks" },
                        end: { lat: 34.0522, lng: -118.2437, label: "Los Angeles" }
                    },
                    {
                        start: { lat: 64.2008, lng: -149.4937, label: "Fairbanks" },
                        end: { lat: -15.7975, lng: -47.8919, label: "Brasília" }
                    },
                    {
                        start: { lat: -15.7975, lng: -47.8919, label: "Brasília" },
                        end: { lat: 38.7223, lng: -9.1393, label: "Lisboa" }
                    },
                    {
                        start: { lat: 51.5074, lng: -0.1278, label: "Londres" },
                        end: { lat: 28.6139, lng: 77.209, label: "Nova Delhi" }
                    },
                    {
                        start: { lat: 28.6139, lng: 77.209, label: "Nova Delhi" },
                        end: { lat: 43.1332, lng: 131.9113, label: "Vladivostok" }
                    },
                    {
                        start: { lat: 28.6139, lng: 77.209, label: "Nova Delhi" },
                        end: { lat: -1.2921, lng: 36.8219, label: "Nairóbi" }
                    }
                ];

                // Convert lat/lng to SVG coordinates
                function projectPoint(lat, lng) {
                    const x = ((lng + 180) / 360) * mapConfig.width;
                    const y = ((90 - lat) / 180) * mapConfig.height;
                    return { x, y };
                }

                // Create curved path between two points
                function createCurvedPath(start, end) {
                    const midX = (start.x + end.x) / 2;
                    const midY = Math.min(start.y, end.y) - 50;
                    return `M ${start.x} ${start.y} Q ${midX} ${midY} ${end.x} ${end.y}`;
                }

                // Get path length
                function getPathLength(pathElement) {
                    return pathElement.getTotalLength();
                }

                // Initialize map
                function initWorldMap() {
                    console.log('Initializing world map...');
                    const svg = document.getElementById('world-map-svg');
                    const connectionsGroup = document.getElementById('connections-group');
                    const citiesGroup = document.getElementById('cities-group');
                    const tooltip = document.getElementById('map-tooltip');
                    const tooltipText = document.getElementById('tooltip-text');

                    if (!svg || !connectionsGroup || !citiesGroup) {
                        console.error('World map elements not found', { svg: !!svg, connectionsGroup: !!connectionsGroup, citiesGroup: !!citiesGroup });
                        return;
                    }
                    
                    console.log('World map elements found, creating connections...');

                    // Create connections
                    connections.forEach((conn, index) => {
                        const startPoint = projectPoint(conn.start.lat, conn.start.lng);
                        const endPoint = projectPoint(conn.end.lat, conn.end.lng);
                        const pathData = createCurvedPath(startPoint, endPoint);

                        // Create path element
                        const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                        path.setAttribute('d', pathData);
                        path.setAttribute('fill', 'none');
                        path.setAttribute('stroke', mapConfig.lineColor);
                        path.setAttribute('stroke-width', '1.5');
                        path.setAttribute('filter', 'url(#glow)');
                        path.setAttribute('opacity', '0.8');
                        
                        connectionsGroup.appendChild(path);

                        // Calculate path length after DOM update
                        setTimeout(() => {
                            const pathLength = getPathLength(path);
                            if (pathLength > 0 && !isNaN(pathLength)) {
                                path.style.strokeDasharray = pathLength;
                                path.style.strokeDashoffset = pathLength;

                                // Initial animation with stagger
                                setTimeout(() => {
                                    path.style.transition = `stroke-dashoffset ${mapConfig.animationDuration}ms ease-in-out`;
                                    path.style.strokeDashoffset = '0';
                                }, index * mapConfig.staggerDelay + 100);

                                // Loop animation
                                const totalAnimationTime = connections.length * mapConfig.staggerDelay + mapConfig.animationDuration;
                                const cycleTime = totalAnimationTime + mapConfig.pauseTime;

                                function resetAndAnimate() {
                                    path.style.transition = 'none';
                                    path.style.strokeDashoffset = pathLength;
                                    setTimeout(() => {
                                        path.style.transition = `stroke-dashoffset ${mapConfig.animationDuration}ms ease-in-out`;
                                        setTimeout(() => {
                                            path.style.strokeDashoffset = '0';
                                        }, index * mapConfig.staggerDelay);
                                    }, 10);
                                }

                                // Start loop after initial animation completes
                                setTimeout(() => {
                                    setInterval(resetAndAnimate, cycleTime);
                                }, totalAnimationTime + 500);
                            } else {
                                console.warn('Path length invalid for connection', index, pathLength);
                            }
                        }, 50);
                    });

                    // Create city markers
                    const allCities = new Map();
                    connections.forEach(conn => {
                        if (!allCities.has(conn.start.label)) {
                            allCities.set(conn.start.label, { lat: conn.start.lat, lng: conn.start.lng });
                        }
                        if (!allCities.has(conn.end.label)) {
                            allCities.set(conn.end.label, { lat: conn.end.lat, lng: conn.end.lng });
                        }
                    });

                    allCities.forEach((coords, label) => {
                        const point = projectPoint(coords.lat, coords.lng);
                        const cityGroup = document.createElementNS('http://www.w3.org/2000/svg', 'g');
                        cityGroup.setAttribute('data-city', label);

                        // Outer pulsing circle
                        const pulseCircle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
                        pulseCircle.setAttribute('cx', point.x);
                        pulseCircle.setAttribute('cy', point.y);
                        pulseCircle.setAttribute('r', '3');
                        pulseCircle.setAttribute('fill', mapConfig.lineColor);
                        pulseCircle.setAttribute('opacity', '0.4');

                        const animateRadius = document.createElementNS('http://www.w3.org/2000/svg', 'animate');
                        animateRadius.setAttribute('attributeName', 'r');
                        animateRadius.setAttribute('from', '3');
                        animateRadius.setAttribute('to', '14');
                        animateRadius.setAttribute('dur', '2.5s');
                        animateRadius.setAttribute('repeatCount', 'indefinite');

                        const animateOpacity = document.createElementNS('http://www.w3.org/2000/svg', 'animate');
                        animateOpacity.setAttribute('attributeName', 'opacity');
                        animateOpacity.setAttribute('from', '0.6');
                        animateOpacity.setAttribute('to', '0');
                        animateOpacity.setAttribute('dur', '2.5s');
                        animateOpacity.setAttribute('repeatCount', 'indefinite');

                        pulseCircle.appendChild(animateRadius);
                        pulseCircle.appendChild(animateOpacity);
                        cityGroup.appendChild(pulseCircle);

                        // Main circle
                        const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
                        circle.setAttribute('cx', point.x);
                        circle.setAttribute('cy', point.y);
                        circle.setAttribute('r', '4');
                        circle.setAttribute('fill', mapConfig.lineColor);
                        circle.setAttribute('filter', 'url(#glow)');
                        circle.style.cursor = 'pointer';
                        circle.style.transition = 'r 0.2s ease, opacity 0.2s ease';

                        circle.addEventListener('mouseenter', () => {
                            circle.setAttribute('r', '6');
                            circle.setAttribute('opacity', '1');
                            if (tooltip && tooltipText) {
                                tooltipText.textContent = label;
                                tooltip.style.opacity = '1';
                            }
                        });

                        circle.addEventListener('mouseleave', () => {
                            circle.setAttribute('r', '4');
                            circle.setAttribute('opacity', '0.9');
                            if (tooltip) {
                                tooltip.style.opacity = '0';
                            }
                        });

                        cityGroup.appendChild(circle);
                        citiesGroup.appendChild(cityGroup);

                        // Label
                        const labelGroup = document.createElementNS('http://www.w3.org/2000/svg', 'g');
                        labelGroup.setAttribute('class', 'city-label');
                        const foreignObject = document.createElementNS('http://www.w3.org/2000/svg', 'foreignObject');
                        foreignObject.setAttribute('x', point.x - 50);
                        foreignObject.setAttribute('y', point.y - 35);
                        foreignObject.setAttribute('width', '100');
                        foreignObject.setAttribute('height', '30');

                        // Use a text element instead of foreignObject for better compatibility
                        const textElement = document.createElementNS('http://www.w3.org/2000/svg', 'text');
                        textElement.setAttribute('x', point.x);
                        textElement.setAttribute('y', point.y - 12);
                        textElement.setAttribute('text-anchor', 'middle');
                        textElement.setAttribute('fill', 'white');
                        textElement.setAttribute('font-size', '11');
                        textElement.setAttribute('font-weight', '500');
                        textElement.setAttribute('class', 'city-label-text');
                        textElement.style.pointerEvents = 'none';
                        textElement.textContent = label;
                        
                        // Background rectangle for text
                        const textBg = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
                        const textBBox = { width: label.length * 6.5, height: 18 };
                        textBg.setAttribute('x', point.x - textBBox.width / 2 - 4);
                        textBg.setAttribute('y', point.y - 26);
                        textBg.setAttribute('width', textBBox.width + 8);
                        textBg.setAttribute('height', textBBox.height);
                        textBg.setAttribute('rx', '4');
                        textBg.setAttribute('fill', 'rgba(0,0,0,0.85)');
                        textBg.setAttribute('stroke', 'rgba(63,63,70,1)');
                        textBg.setAttribute('stroke-width', '1');
                        textBg.style.pointerEvents = 'none';
                        
                        labelGroup.appendChild(textBg);
                        labelGroup.appendChild(textElement);
                        citiesGroup.appendChild(labelGroup);
                    });
                }

                // Initialize when DOM is ready
                function startMap() {
                    // Wait a bit to ensure SVG is rendered
                    setTimeout(() => {
                        try {
                            initWorldMap();
                        } catch (error) {
                            console.error('Error initializing world map:', error);
                        }
                    }, 200);
                }
                
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', startMap);
                } else {
                    startMap();
                }
            })();
        </script>
    </section>

    <!-- Hosting Integration CTA Section -->
    <section class="py-16 bg-gradient-to-b from-black via-zinc-900 to-black border-y border-zinc-800 relative overflow-hidden">
        <div class="absolute inset-0 bg-grid-pattern bg-grid-white opacity-5 pointer-events-none"></div>
        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-zinc-900/50 border border-zinc-800 rounded-2xl p-8 md:p-12 backdrop-blur-sm">
                <div class="grid md:grid-cols-2 gap-8 items-center">
                    <div>
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/20 mb-6">
                            <i data-lucide="plug" class="w-4 h-4 text-white"></i>
                            <span class="text-xs font-medium text-zinc-300">SafeNode Hosting Integration</span>
                        </div>
                        <h2 class="text-3xl md:text-4xl font-bold mb-4">E-mails funcionando em qualquer VPS em 10 minutos</h2>
                        <p class="text-lg text-zinc-400 mb-6">
                            Script automatizado. Docker pronto. Zero configuração de SMTP/DNS. 
                            Integre SafeNode Mail direto na sua hospedagem.
                        </p>
                        <div class="flex flex-col sm:flex-row gap-4">
                            <a href="integration-docs.php" class="px-6 py-3 bg-white text-black rounded-lg font-semibold hover:bg-zinc-100 transition-all flex items-center justify-center gap-2">
                                <span>Ver Documentação</span>
                                <i data-lucide="arrow-right" class="w-4 h-4"></i>
                            </a>
                            <a href="survey.php" class="px-6 py-3 bg-zinc-800 text-white border border-zinc-700 rounded-lg font-semibold hover:bg-zinc-700 transition-all flex items-center justify-center gap-2">
                                <i data-lucide="message-square" class="w-4 h-4"></i>
                                <span>Responder Pesquisa</span>
                            </a>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div class="bg-zinc-950 border border-zinc-800 rounded-xl p-4">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="w-8 h-8 bg-green-500/20 rounded-lg flex items-center justify-center">
                                    <i data-lucide="check" class="w-4 h-4 text-green-400"></i>
                                </div>
                                <span class="text-sm font-medium text-zinc-300">Funciona em qualquer hospedagem</span>
                            </div>
                            <p class="text-xs text-zinc-500 ml-11">DigitalOcean, AWS, Hostinger, cPanel, Plesk...</p>
                        </div>
                        <div class="bg-zinc-950 border border-zinc-800 rounded-xl p-4">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="w-8 h-8 bg-blue-500/20 rounded-lg flex items-center justify-center">
                                    <i data-lucide="zap" class="w-4 h-4 text-blue-400"></i>
                                </div>
                                <span class="text-sm font-medium text-zinc-300">Setup automatizado</span>
                            </div>
                            <p class="text-xs text-zinc-500 ml-11">Script bash + Docker compose pronto para usar</p>
                        </div>
                        <div class="bg-zinc-950 border border-zinc-800 rounded-xl p-4">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="w-8 h-8 bg-purple-500/20 rounded-lg flex items-center justify-center">
                                    <i data-lucide="mail" class="w-4 h-4 text-purple-400"></i>
                                </div>
                                <span class="text-sm font-medium text-zinc-300">API REST simples</span>
                            </div>
                            <p class="text-xs text-zinc-500 ml-11">Envie e-mails com uma requisição HTTP</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features (Bento Grid) -->
    <section id="features" class="py-24 bg-black relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-5xl font-bold mb-4">Segurança em camadas.</h2>
                <p class="text-zinc-400 max-w-2xl mx-auto">Uma plataforma unificada para proteger, acelerar e construir na web.</p>
                <div class="mt-6 inline-flex items-center gap-2 px-4 py-2 rounded-full bg-zinc-900/50 border border-zinc-800 backdrop-blur-sm">
                    <i data-lucide="link" class="w-4 h-4 text-blue-400 self-center"></i>
                    <span class="text-sm text-zinc-300 inline-flex items-center gap-1.5">Integração nativa com <span class="text-white font-semibold inline-flex items-center gap-1.5"><img src="assets/img/cloudflare_icon_130969-removebg-preview.png" alt="Cloudflare" class="w-4 h-4 object-contain self-center">Cloudflare</span> para proteção em camadas</span>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Large Card -->
                <div class="md:col-span-2 glass-card rounded-3xl p-8 hover:border-zinc-600 transition-colors group relative overflow-hidden">
                    <div class="absolute top-0 right-0 p-8 opacity-10 group-hover:opacity-20 transition-opacity">
                        <i data-lucide="globe" class="w-64 h-64 text-white"></i>
                    </div>
                    <div class="relative z-10">
                        <div class="w-12 h-12 bg-zinc-800 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform border border-zinc-700">
                            <i data-lucide="shield-check" class="w-6 h-6 text-white"></i>
                        </div>
                        <h3 class="text-2xl font-bold mb-3">Proteção DDoS Global</h3>
                        <p class="text-zinc-400 max-w-md">Mitigação instantânea de ataques em qualquer camada. Nossa rede de 100Tbps absorve as maiores ameaças sem afetar sua performance.</p>
                        
                        <div class="mt-8 flex gap-2">
                            <span class="px-3 py-1 rounded-full bg-zinc-800/50 text-xs text-zinc-300 border border-zinc-700 backdrop-blur-sm">L3/L4</span>
                            <span class="px-3 py-1 rounded-full bg-zinc-800/50 text-xs text-zinc-300 border border-zinc-700 backdrop-blur-sm">L7 Application</span>
                        </div>
                    </div>
                </div>

                <!-- Tall Card -->
                <div class="glass-card rounded-3xl p-8 hover:border-zinc-600 transition-colors group">
                    <div class="w-12 h-12 bg-zinc-800 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform border border-zinc-700">
                        <i data-lucide="zap" class="w-6 h-6 text-white"></i>
                    </div>
                    <h3 class="text-2xl font-bold mb-3">Edge Compute</h3>
                    <p class="text-zinc-400 mb-6">Execute código em milissegundos de seus usuários. Sem servidores para gerenciar.</p>
                    <div class="space-y-3">
                        <div class="flex items-center gap-3 text-sm text-zinc-300">
                            <div class="w-5 h-5 rounded-full bg-green-500/20 flex items-center justify-center">
                                <i data-lucide="check" class="w-3 h-3 text-green-500"></i>
                            </div>
                            0ms Cold Starts
                        </div>
                        <div class="flex items-center gap-3 text-sm text-zinc-300">
                            <div class="w-5 h-5 rounded-full bg-green-500/20 flex items-center justify-center">
                                <i data-lucide="check" class="w-3 h-3 text-green-500"></i>
                            </div>
                            V8 Isolate
                        </div>
                        <div class="flex items-center gap-3 text-sm text-zinc-300">
                            <div class="w-5 h-5 rounded-full bg-green-500/20 flex items-center justify-center">
                                <i data-lucide="check" class="w-3 h-3 text-green-500"></i>
                            </div>
                            Global Deploy
                        </div>
                    </div>
                </div>

                <!-- Wide Card -->
                <div class="md:col-span-3 glass-card rounded-3xl p-8 hover:border-zinc-600 transition-colors flex flex-col md:flex-row items-center gap-8 group">
                    <div class="flex-1">
                        <div class="w-12 h-12 bg-zinc-800 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform border border-zinc-700">
                            <i data-lucide="lock" class="w-6 h-6 text-white"></i>
                        </div>
                        <h3 class="text-2xl font-bold mb-3">Zero Trust Access</h3>
                        <p class="text-zinc-400">Substitua VPNs corporativas por políticas de acesso baseadas em identidade. Conecte usuários a recursos privados de forma segura e rápida.</p>
                    </div>
                    <div class="flex-1 w-full bg-black/50 rounded-xl border border-zinc-800 p-4 backdrop-blur-md">
                        <div class="flex items-center gap-2 mb-4 border-b border-zinc-800 pb-2">
                            <div class="w-2 h-2 rounded-full bg-red-500"></div>
                            <div class="w-2 h-2 rounded-full bg-yellow-500"></div>
                            <div class="w-2 h-2 rounded-full bg-green-500"></div>
                            <span class="text-xs text-zinc-500 ml-2">Access Policy</span>
                        </div>
                        <div class="space-y-2 font-mono text-xs">
                            <div class="flex justify-between text-zinc-400 p-2 hover:bg-zinc-800/50 rounded transition-colors">
                                <span>Rule: <span class="text-white">Engineering Team</span></span>
                                <span class="text-green-400 bg-green-900/20 px-2 py-0.5 rounded border border-green-900/30">Allow</span>
                            </div>
                            <div class="flex justify-between text-zinc-400 p-2 hover:bg-zinc-800/50 rounded transition-colors">
                                <span>Auth: <span class="text-white">SSO / MFA</span></span>
                                <span class="text-green-400 bg-green-900/20 px-2 py-0.5 rounded border border-green-900/30">Verified</span>
                            </div>
                            <div class="flex justify-between text-zinc-400 p-2 hover:bg-zinc-800/50 rounded transition-colors">
                                <span>Device: <span class="text-white">Managed</span></span>
                                <span class="text-green-400 bg-green-900/20 px-2 py-0.5 rounded border border-green-900/30">Compliant</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Developer Experience Section with Code Block -->
    <section class="py-24 bg-black relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                <div>
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-zinc-900 border border-zinc-800 mb-6">
                        <i data-lucide="code-2" class="w-4 h-4 text-white"></i>
                        <span class="text-xs font-medium text-zinc-300">Developer First</span>
                    </div>
                    <h2 class="text-3xl md:text-5xl font-bold mb-6 tracking-tight">Integração em minutos, <br/>não meses.</h2>
                    <p class="text-zinc-400 text-lg mb-8 leading-relaxed">
                        Nossa API robusta e SDK intuitivo permitem que você configure regras de firewall, gerencie sites e proteja sua aplicação diretamente do seu fluxo de trabalho existente. Integração nativa com <span class="text-white font-semibold inline-flex items-center gap-1.5"><img src="assets/img/cloudflare_icon_130969-removebg-preview.png" alt="Cloudflare" class="w-4 h-4 object-contain self-center">Cloudflare</span> para sincronização automática de bloqueios e regras de segurança.
                    </p>
                    
                    <div class="space-y-6">
                        <div class="flex gap-4">
                            <div class="w-10 h-10 rounded-full bg-zinc-900 flex items-center justify-center border border-zinc-800 shrink-0">
                                <span class="font-bold text-white">1</span>
                            </div>
                            <div>
                                <h4 class="text-white font-bold mb-1">Adicione seu site</h4>
                                <p class="text-zinc-500 text-sm">Configure seu domínio em "Gerenciar Sites" no dashboard.</p>
                            </div>
                        </div>
                        <div class="flex gap-4">
                            <div class="w-10 h-10 rounded-full bg-zinc-900 flex items-center justify-center border border-zinc-800 shrink-0">
                                <span class="font-bold text-white">2</span>
                            </div>
                            <div>
                                <h4 class="text-white font-bold mb-1">Gere sua API Key</h4>
                                <p class="text-zinc-500 text-sm">Acesse "Verificação Humana" e copie o código de integração.</p>
                            </div>
                        </div>
                        <div class="flex gap-4">
                            <div class="w-10 h-10 rounded-full bg-zinc-900 flex items-center justify-center border border-zinc-800 shrink-0">
                                <span class="font-bold text-white">3</span>
                            </div>
                            <div>
                                <h4 class="text-white font-bold mb-1">Proteção Ativa</h4>
                                <p class="text-zinc-500 text-sm">Cole o código no seu site e a proteção estará ativa instantaneamente.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Code Window -->
                <div class="relative group">
                    <div class="absolute -inset-1 bg-gradient-to-r from-zinc-700 to-zinc-800 rounded-2xl blur opacity-20 group-hover:opacity-40 transition duration-1000 group-hover:duration-200"></div>
                    <div class="relative rounded-2xl bg-[#0d1117] border border-zinc-800 shadow-2xl overflow-hidden">
                        <div class="flex items-center px-4 py-3 border-b border-zinc-800 bg-zinc-900/50">
                            <div class="flex space-x-2">
                                <div class="w-3 h-3 rounded-full bg-red-500/20 border border-red-500/50"></div>
                                <div class="w-3 h-3 rounded-full bg-yellow-500/20 border border-yellow-500/50"></div>
                                <div class="w-3 h-3 rounded-full bg-green-500/20 border border-green-500/50"></div>
                            </div>
                            <div class="ml-4 text-xs text-zinc-500 font-mono">index.html</div>
                        </div>
                        <div class="p-6">
                            <pre class="font-mono text-xs leading-relaxed break-words whitespace-pre-wrap"><code><span class="code-syntax-comment">&lt;!-- SafeNode Human Verification SDK --&gt;</span>
<span class="code-syntax-keyword">&lt;script</span> <span class="code-syntax-function">src</span>=<span class="code-syntax-string">"https://safenode.cloud/sdk/safenode-hv.js"</span><span class="code-syntax-keyword">&gt;&lt;/script&gt;</span>
<span class="code-syntax-keyword">&lt;script&gt;</span>
<span class="code-syntax-comment">// Inicializar verificação humana</span>
<span class="code-syntax-keyword">const</span> apiKey = <span class="code-syntax-string">'sua-api-key-aqui'</span>;
<span class="code-syntax-keyword">const</span> apiUrl = <span class="code-syntax-string">'https://safenode.cloud/api/sdk'</span>;
<span class="code-syntax-keyword">const</span> hv = <span class="code-syntax-keyword">new</span> <span class="code-syntax-function">SafeNodeHV</span>(apiUrl, apiKey);

<span class="code-syntax-comment">// Inicializar e anexar ao formulário</span>
hv.<span class="code-syntax-function">init</span>().<span class="code-syntax-function">then</span>(() => {
  hv.<span class="code-syntax-function">attachToForm</span>(<span class="code-syntax-string">'#meuFormulario'</span>);
  <span class="code-syntax-function">console</span>.<span class="code-syntax-function">log</span>(<span class="code-syntax-string">'Verificação humana ativa 🛡️'</span>);
});
<span class="code-syntax-keyword">&lt;/script&gt;</span></code></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="py-20 border-y border-zinc-900 bg-black">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-center divide-y md:divide-y-0 md:divide-x divide-zinc-800">
                <div class="p-4">
                    <div class="text-4xl md:text-5xl font-bold text-white mb-2"><?php echo htmlspecialchars($globalLatency); ?></div>
                    <div class="text-zinc-500 uppercase tracking-wider text-sm">Latência Global Média</div>
                </div>
                <div class="p-4">
                    <div class="text-4xl md:text-5xl font-bold text-white mb-2"><?php echo htmlspecialchars($threatsPerDay); ?>+</div>
                    <div class="text-zinc-500 uppercase tracking-wider text-sm">Ameaças Bloqueadas/Dia</div>
                </div>
                <div class="p-4">
                    <div class="text-4xl md:text-5xl font-bold text-white mb-2"><?php echo htmlspecialchars($indexStats['uptime_percent']); ?>%</div>
                    <div class="text-zinc-500 uppercase tracking-wider text-sm">Uptime Garantido</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing -->
    <section id="pricing" class="py-24 bg-black">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-5xl font-bold mb-4">Preços transparentes.</h2>
                <p class="text-zinc-400">Comece pequeno, escale globalmente.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-5xl mx-auto">
                <!-- Free -->
                <div class="bg-zinc-900/10 border border-zinc-800 rounded-3xl p-8 flex flex-col">
                    <div class="mb-4">
                        <span class="text-lg font-medium text-zinc-300">Hobby</span>
                        <div class="text-4xl font-bold mt-2 text-white">R$0<span class="text-lg font-normal text-zinc-500">/mês</span></div>
                    </div>
                    <p class="text-zinc-400 text-sm mb-8">Para projetos pessoais e testes.</p>
                    <ul class="space-y-4 mb-8 flex-1">
                        <li class="flex items-center gap-3 text-sm text-zinc-300"><i data-lucide="check" class="w-4 h-4 text-white"></i> Proteção DDoS Básica</li>
                        <li class="flex items-center gap-3 text-sm text-zinc-300"><i data-lucide="check" class="w-4 h-4 text-white"></i> CDN Global</li>
                        <li class="flex items-center gap-3 text-sm text-zinc-300"><i data-lucide="check" class="w-4 h-4 text-white"></i> SSL Gratuito</li>
                    </ul>
                    <?php if (isset($_SESSION['safenode_logged_in']) && $_SESSION['safenode_logged_in'] === true): ?>
                        <a href="checkout.php?plan=hobby" class="w-full py-3 rounded-full border border-zinc-700 text-white font-medium hover:bg-zinc-800 transition-colors text-center">Ativar Plano</a>
                    <?php else: ?>
                    <a href="register.php" class="w-full py-3 rounded-full border border-zinc-700 text-white font-medium hover:bg-zinc-800 transition-colors text-center">Começar</a>
                    <?php endif; ?>
                </div>

                <!-- Pro (Featured) -->
                <div class="bg-zinc-900/30 border border-white/20 rounded-3xl p-8 flex flex-col relative transform md:-translate-y-4 shadow-2xl shadow-white/5">
                    <div class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 bg-white text-black px-4 py-1 rounded-full text-xs font-bold uppercase tracking-wide">Mais Popular</div>
                    <div class="mb-4">
                        <span class="text-lg font-medium text-white">Pro</span>
                        <div class="text-4xl font-bold mt-2 text-white">R$99<span class="text-lg font-normal text-zinc-500">/mês</span></div>
                    </div>
                    <p class="text-zinc-400 text-sm mb-8">Para aplicações em produção.</p>
                    <ul class="space-y-4 mb-8 flex-1">
                        <li class="flex items-center gap-3 text-sm text-white"><i data-lucide="check" class="w-4 h-4 text-white"></i> Tudo do Hobby</li>
                        <li class="flex items-center gap-3 text-sm text-white"><i data-lucide="check" class="w-4 h-4 text-white"></i> WAF Avançado</li>
                        <li class="flex items-center gap-3 text-sm text-white"><i data-lucide="check" class="w-4 h-4 text-white"></i> Otimização de Imagens</li>
                        <li class="flex items-center gap-3 text-sm text-white"><i data-lucide="check" class="w-4 h-4 text-white"></i> Analytics em Tempo Real</li>
                    </ul>
                    <?php if (isset($_SESSION['safenode_logged_in']) && $_SESSION['safenode_logged_in'] === true): ?>
                        <a href="checkout.php?plan=pro" class="w-full py-3 rounded-full bg-white text-black font-bold hover:bg-zinc-200 transition-colors text-center">Assinar Pro</a>
                    <?php else: ?>
                        <a href="register.php?plan=pro" class="w-full py-3 rounded-full bg-white text-black font-bold hover:bg-zinc-200 transition-colors text-center">Assinar Pro</a>
                    <?php endif; ?>
                </div>

                <!-- Enterprise -->
                <div class="bg-zinc-900/10 border border-zinc-800 rounded-3xl p-8 flex flex-col">
                    <div class="mb-4">
                        <span class="text-lg font-medium text-zinc-300">Enterprise</span>
                        <div class="text-4xl font-bold mt-2 text-white">Custom</div>
                    </div>
                    <p class="text-zinc-400 text-sm mb-8">Para missão crítica e escala massiva.</p>
                    <ul class="space-y-4 mb-8 flex-1">
                        <li class="flex items-center gap-3 text-sm text-zinc-300"><i data-lucide="check" class="w-4 h-4 text-white"></i> SLA de 100%</li>
                        <li class="flex items-center gap-3 text-sm text-zinc-300"><i data-lucide="check" class="w-4 h-4 text-white"></i> Suporte 24/7 Dedicado</li>
                        <li class="flex items-center gap-3 text-sm text-zinc-300"><i data-lucide="check" class="w-4 h-4 text-white"></i> Logs Raw</li>
                        <li class="flex items-center gap-3 text-sm text-zinc-300"><i data-lucide="check" class="w-4 h-4 text-white"></i> Single Sign-On (SSO)</li>
                    </ul>
                    <a href="#" class="w-full py-3 rounded-full border border-zinc-700 text-white font-medium hover:bg-zinc-800 transition-colors text-center">Falar com Vendas</a>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="py-24 bg-black border-t border-zinc-900">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold mb-12 text-center">Perguntas Frequentes</h2>
            
            <div class="space-y-4" x-data="{ active: null }">
                <!-- FAQ Item 1 -->
                <div class="border border-zinc-800 rounded-2xl bg-zinc-900/20 overflow-hidden">
                    <button @click="active = active === 1 ? null : 1" class="w-full px-6 py-4 text-left flex justify-between items-center focus:outline-none">
                        <span class="font-medium text-zinc-200">Como funciona a proteção DDoS?</span>
                        <i data-lucide="chevron-down" class="w-5 h-5 text-zinc-500 transition-transform duration-300" :class="{ 'rotate-180': active === 1 }"></i>
                    </button>
                    <div x-show="active === 1" x-collapse class="px-6 pb-4 text-zinc-400 text-sm leading-relaxed">
                        Nossa rede global analisa o tráfego em tempo real e filtra requisições maliciosas na borda, antes que elas atinjam seu servidor. Utilize machine learning para identificar padrões de ataque complexos.
                    </div>
                </div>

                <!-- FAQ Item 2 -->
                <div class="border border-zinc-800 rounded-2xl bg-zinc-900/20 overflow-hidden">
                    <button @click="active = active === 2 ? null : 2" class="w-full px-6 py-4 text-left flex justify-between items-center focus:outline-none">
                        <span class="font-medium text-zinc-200">Posso usar com qualquer provedor de hospedagem?</span>
                        <i data-lucide="chevron-down" class="w-5 h-5 text-zinc-500 transition-transform duration-300" :class="{ 'rotate-180': active === 2 }"></i>
                    </button>
                    <div x-show="active === 2" x-collapse class="px-6 pb-4 text-zinc-400 text-sm leading-relaxed">
                        Sim! O SafeNode funciona como um proxy reverso. Você só precisa alterar seus apontamentos DNS para nossa rede, e nós cuidamos do resto, independente de onde seu servidor esteja (AWS, DigitalOcean, On-premise, etc).
                    </div>
                </div>

                <!-- FAQ Item 3 -->
                <div class="border border-zinc-800 rounded-2xl bg-zinc-900/20 overflow-hidden">
                    <button @click="active = active === 3 ? null : 3" class="w-full px-6 py-4 text-left flex justify-between items-center focus:outline-none">
                        <span class="font-medium text-zinc-200">O SSL é realmente gratuito?</span>
                        <i data-lucide="chevron-down" class="w-5 h-5 text-zinc-500 transition-transform duration-300" :class="{ 'rotate-180': active === 3 }"></i>
                    </button>
                    <div x-show="active === 3" x-collapse class="px-6 pb-4 text-zinc-400 text-sm leading-relaxed">
                        Sim, emitimos e renovamos automaticamente certificados SSL universais para todos os domínios ativos em nossa plataforma, garantindo criptografia de ponta a ponta sem custo extra.
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Final -->
    <section class="py-24 relative overflow-hidden">
        <div class="absolute inset-0 bg-zinc-900/50"></div>
        <div class="absolute inset-0 bg-gradient-to-r from-black via-transparent to-black"></div>
        
        <div class="relative z-10 max-w-4xl mx-auto px-4 text-center">
            <h2 class="text-4xl md:text-5xl font-bold mb-6 tracking-tight">Pronto para proteger sua infraestrutura?</h2>
            <p class="text-xl text-zinc-400 mb-10 flex items-center justify-center gap-2 flex-wrap">
                Junte-se a milhares de desenvolvedores que confiam no <span class="flex items-center gap-2"><img src="assets/img/logos (6).png" alt="SafeNode" class="h-6 w-auto">SafeNode</span>.
            </p>
            <div class="flex flex-col sm:flex-row justify-center gap-4">
                <a href="register.php" class="px-8 py-4 bg-white text-black rounded-full font-bold hover:bg-zinc-200 transition-all transform hover:scale-105">Criar Conta Grátis</a>
                <a href="#" class="px-8 py-4 bg-transparent border border-zinc-700 text-white rounded-full font-medium hover:bg-zinc-900 transition-all">Falar com Especialista</a>
            </div>
        </div>
    </section>

    <!-- Redesigned professional footer with better organization -->
    <footer class="bg-black border-t border-zinc-900 pt-20 pb-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Newsletter Section -->
            <div class="mb-16 pb-16 border-b border-zinc-900">
                <div class="max-w-2xl mx-auto text-center">
                    <h3 class="text-2xl md:text-3xl font-bold mb-3">Fique por dentro das novidades</h3>
                    <p class="text-zinc-400 mb-8">Receba atualizações sobre novos recursos, dicas de segurança e insights do setor.</p>
                    <form class="flex flex-col sm:flex-row gap-3 max-w-md mx-auto">
                        <input 
                            type="email" 
                            placeholder="seu@email.com" 
                            class="flex-1 px-5 py-3 bg-zinc-900 border border-zinc-800 rounded-full text-white placeholder:text-zinc-500 focus:outline-none focus:border-zinc-600 transition-colors"
                        >
                        <button 
                            type="submit" 
                            class="px-8 py-3 bg-white text-black rounded-full font-semibold hover:bg-zinc-100 transition-all hover:shadow-[0_0_20px_rgba(255,255,255,0.2)] whitespace-nowrap"
                        >
                            Inscrever-se
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Main Footer Content -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-12 mb-16">
                <!-- Brand Column (Spans 2 columns) -->
                <div class="lg:col-span-2">
                    <div class="flex items-center gap-3 mb-6 group cursor-pointer">
                        <img src="assets/img/logos (6).png" alt="SafeNode" class="h-10 w-auto group-hover:scale-110 transition-transform">
                        <span class="font-bold text-xl">SafeNode</span>
                    </div>
                    <p class="text-zinc-400 text-sm leading-relaxed mb-6 max-w-sm">
                        Tornando a internet mais segura, rápida e confiável para todos. Protegendo milhões de aplicações em todo o mundo.
                    </p>
                    <div class="flex gap-3">
                        <a href="#" class="w-10 h-10 rounded-full bg-zinc-900 border border-zinc-800 flex items-center justify-center text-zinc-400 hover:text-white hover:border-zinc-600 hover:bg-zinc-800 transition-all">
                            <i data-lucide="twitter" class="w-4 h-4"></i>
                        </a>
                        <a href="#" class="w-10 h-10 rounded-full bg-zinc-900 border border-zinc-800 flex items-center justify-center text-zinc-400 hover:text-white hover:border-zinc-600 hover:bg-zinc-800 transition-all">
                            <i data-lucide="github" class="w-4 h-4"></i>
                        </a>
                        <a href="#" class="w-10 h-10 rounded-full bg-zinc-900 border border-zinc-800 flex items-center justify-center text-zinc-400 hover:text-white hover:border-zinc-600 hover:bg-zinc-800 transition-all">
                            <i data-lucide="linkedin" class="w-4 h-4"></i>
                        </a>
                        <a href="#" class="w-10 h-10 rounded-full bg-zinc-900 border border-zinc-800 flex items-center justify-center text-zinc-400 hover:text-white hover:border-zinc-600 hover:bg-zinc-800 transition-all">
                            <i data-lucide="youtube" class="w-4 h-4"></i>
                        </a>
                    </div>
                </div>
                
                <!-- Product Column -->
                <div>
                    <h4 class="font-semibold text-white mb-5 text-sm uppercase tracking-wider">Produto</h4>
                    <ul class="space-y-3">
                        <li><a href="#" class="text-sm text-zinc-400 hover:text-white transition-colors flex items-center gap-2 group">
                            <i data-lucide="shield" class="w-3 h-3 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                            DDoS Protection
                        </a></li>
                        <li><a href="#" class="text-sm text-zinc-400 hover:text-white transition-colors flex items-center gap-2 group">
                            <i data-lucide="shield" class="w-3 h-3 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                            WAF
                        </a></li>
                        <li><a href="#" class="text-sm text-zinc-400 hover:text-white transition-colors flex items-center gap-2 group">
                            <i data-lucide="shield" class="w-3 h-3 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                            CDN
                        </a></li>
                        <li><a href="#" class="text-sm text-zinc-400 hover:text-white transition-colors flex items-center gap-2 group">
                            <i data-lucide="shield" class="w-3 h-3 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                            Edge Compute
                        </a></li>
                        <li><a href="#" class="text-sm text-zinc-400 hover:text-white transition-colors flex items-center gap-2 group">
                            <i data-lucide="shield" class="w-3 h-3 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                            Zero Trust
                        </a></li>
                    </ul>
                </div>
                
                <!-- Developers Column -->
                <div>
                    <h4 class="font-semibold text-white mb-5 text-sm uppercase tracking-wider">Desenvolvedores</h4>
                    <ul class="space-y-3">
                        <li><a href="#" class="text-sm text-zinc-400 hover:text-white transition-colors flex items-center gap-2 group">
                            <i data-lucide="book-open" class="w-3 h-3 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                            Documentação
                        </a></li>
                        <li><a href="#" class="text-sm text-zinc-400 hover:text-white transition-colors flex items-center gap-2 group">
                            <i data-lucide="book-open" class="w-3 h-3 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                            API Reference
                        </a></li>
                        <li><a href="#" class="text-sm text-zinc-400 hover:text-white transition-colors flex items-center gap-2 group">
                            <i data-lucide="book-open" class="w-3 h-3 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                            CLI
                        </a></li>
                        <li><a href="#" class="text-sm text-zinc-400 hover:text-white transition-colors flex items-center gap-2 group">
                            <i data-lucide="book-open" class="w-3 h-3 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                            SDKs
                        </a></li>
                        <li><a href="#" class="text-sm text-zinc-400 hover:text-white transition-colors flex items-center gap-2 group">
                            <i data-lucide="book-open" class="w-3 h-3 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                            Status
                        </a></li>
                    </ul>
                </div>
                
                <!-- Company Column -->
                <div>
                    <h4 class="font-semibold text-white mb-5 text-sm uppercase tracking-wider">Empresa</h4>
                    <ul class="space-y-3">
                        <li><a href="#" class="text-sm text-zinc-400 hover:text-white transition-colors flex items-center gap-2 group">
                            <i data-lucide="building" class="w-3 h-3 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                            Sobre
                        </a></li>
                        <li><a href="#" class="text-sm text-zinc-400 hover:text-white transition-colors flex items-center gap-2 group">
                            <i data-lucide="building" class="w-3 h-3 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                            Carreiras
                        </a></li>
                        <li><a href="#" class="text-sm text-zinc-400 hover:text-white transition-colors flex items-center gap-2 group">
                            <i data-lucide="building" class="w-3 h-3 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                            Blog
                        </a></li>
                        <li><a href="#" class="text-sm text-zinc-400 hover:text-white transition-colors flex items-center gap-2 group">
                            <i data-lucide="building" class="w-3 h-3 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                            Imprensa
                        </a></li>
                        <li><a href="#" class="text-sm text-zinc-400 hover:text-white transition-colors flex items-center gap-2 group">
                            <i data-lucide="building" class="w-3 h-3 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                            Contato
                        </a></li>
                    </ul>
                </div>
                
                <!-- Resources Column -->
                <div>
                    <h4 class="font-semibold text-white mb-5 text-sm uppercase tracking-wider">Recursos</h4>
                    <ul class="space-y-3">
                        <li><a href="ANALISE_FUNCIONALIDADES.html" class="text-sm text-zinc-400 hover:text-white transition-colors flex items-center gap-2 group">
                            <i data-lucide="file-text" class="w-3 h-3 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                            Análise de Funcionalidades
                        </a></li>
                        <li><a href="#" class="text-sm text-zinc-400 hover:text-white transition-colors flex items-center gap-2 group">
                            <i data-lucide="users" class="w-3 h-3 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                            Comunidade
                        </a></li>
                        <li><a href="#" class="text-sm text-zinc-400 hover:text-white transition-colors flex items-center gap-2 group">
                            <i data-lucide="users" class="w-3 h-3 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                            Suporte
                        </a></li>
                        <li><a href="#" class="text-sm text-zinc-400 hover:text-white transition-colors flex items-center gap-2 group">
                            <i data-lucide="users" class="w-3 h-3 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                            Webinars
                        </a></li>
                        <li><a href="#" class="text-sm text-zinc-400 hover:text-white transition-colors flex items-center gap-2 group">
                            <i data-lucide="users" class="w-3 h-3 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                            Case Studies
                        </a></li>
                        <li><a href="#" class="text-sm text-zinc-400 hover:text-white transition-colors flex items-center gap-2 group">
                            <i data-lucide="users" class="w-3 h-3 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                            Whitepapers
                        </a></li>
                    </ul>
                </div>
            </div>
            
            <!-- Bottom Bar -->
            <div class="border-t border-zinc-900 pt-8 flex flex-col md:flex-row justify-between items-center gap-4">
                <div class="flex flex-col md:flex-row items-center gap-4 text-sm text-zinc-600">
                    <p class="flex items-center gap-2">© 2025 <span class="flex items-center gap-1.5"><img src="assets/img/logos (6).png" alt="SafeNode" class="h-5 w-auto">SafeNode</span>. Todos os direitos reservados.</p>
                    <div class="flex gap-6">
                        <a href="#" class="hover:text-zinc-400 transition-colors">Privacidade</a>
                        <a href="#" class="hover:text-zinc-400 transition-colors">Termos</a>
                        <a href="#" class="hover:text-zinc-400 transition-colors">Cookies</a>
                        <a href="#" class="hover:text-zinc-400 transition-colors">Segurança</a>
                        <a href="survey-admin.php" class="hover:text-zinc-400 transition-colors opacity-50 hover:opacity-100">Admin</a>
                    </div>
                </div>
                <div class="flex items-center gap-2 text-xs text-zinc-600">
                    <i data-lucide="globe" class="w-4 h-4"></i>
                    <select class="bg-transparent border border-zinc-800 rounded-lg px-3 py-1.5 text-zinc-400 hover:border-zinc-600 transition-colors focus:outline-none focus:border-zinc-500 cursor-pointer">
                        <option>Português (BR)</option>
                        <option>English (US)</option>
                        <option>Español</option>
                    </select>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scroll to Top Button -->
    <div x-data="{ show: false }" 
         @scroll.window="show = (window.pageYOffset > 300)" 
         class="fixed bottom-8 right-8 z-50">
        <button 
            x-show="show" 
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-300"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 translate-y-4"
            @click="window.scrollTo({top: 0, behavior: 'smooth'})" 
            class="bg-white text-black p-3 rounded-full shadow-lg hover:bg-zinc-200 transition-colors focus:outline-none"
        >
            <i data-lucide="arrow-up" class="w-5 h-5"></i>
        </button>
    </div>

    <script>
        // Initialize Lucide Icons
        lucide.createIcons();
        
        // Initialize Preview Chart
        document.addEventListener('DOMContentLoaded', function() {
            const canvas = document.getElementById('previewEntitiesChart');
            if (!canvas || typeof Chart === 'undefined') return;
            
            const ctx = canvas.getContext('2d');
            if (!ctx) return;
            
            // Calcular dados baseados nas estatísticas reais
            const totalRequests = <?php echo (int)$indexStats['total_requests_24h']; ?>;
            const blockedRequests = <?php echo (int)$indexStats['threats_blocked_24h']; ?>;
            const allowedRequests = totalRequests - blockedRequests;
            
            // Calcular percentuais
            const goodPercent = totalRequests > 0 ? Math.round((allowedRequests / totalRequests) * 100) : 100;
            const moderatePercent = totalRequests > 0 ? Math.round((blockedRequests * 0.3 / totalRequests) * 100) : 0;
            const badPercent = totalRequests > 0 ? Math.round((blockedRequests * 0.7 / totalRequests) * 100) : 0;
            
            // Ajustar para somar 100%
            const total = goodPercent + moderatePercent + badPercent;
            const adjustedGood = total > 0 ? Math.round((goodPercent / total) * 100) : 100;
            const adjustedModerate = total > 0 ? Math.round((moderatePercent / total) * 100) : 0;
            const adjustedBad = total > 0 ? Math.round((badPercent / total) * 100) : 0;
            
            // Create gradients
            const gradient1 = ctx.createLinearGradient(0, 0, 0, 150);
            gradient1.addColorStop(0, '#ffffff');
            gradient1.addColorStop(1, '#e5e5e5');
            
            const gradient2 = ctx.createLinearGradient(0, 0, 0, 150);
            gradient2.addColorStop(0, '#f59e0b');
            gradient2.addColorStop(1, '#d97706');
            
            const gradient3 = ctx.createLinearGradient(0, 0, 0, 150);
            gradient3.addColorStop(0, '#a855f7');
            gradient3.addColorStop(1, '#7c3aed');
            
            try {
                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Good', 'Moderate', 'Bad'],
                        datasets: [{
                            data: [adjustedGood, adjustedModerate, adjustedBad],
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
                            legend: { display: false },
                            tooltip: { enabled: false }
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
            } catch (error) {
                console.error('Erro ao criar gráfico preview:', error);
            }
        });
    </script>
    
    <!-- Security Scripts - Previne download de código -->
    <script src="includes/security-scripts.js"></script>
    
    <!-- Flocos de Neve Natalinos -->
    <script>
        (function() {
            const snowflakes = ['❄', '❅', '❆'];
            const maxSnowflakes = 50;
            let snowflakeCount = 0;
            
            function createSnowflake() {
                if (snowflakeCount >= maxSnowflakes) return;
                
                const snowflake = document.createElement('div');
                snowflake.className = 'snowflake';
                snowflake.textContent = snowflakes[Math.floor(Math.random() * snowflakes.length)];
                
                // Posição horizontal aleatória
                snowflake.style.left = Math.random() * 100 + '%';
                
                // Duração da animação aleatória (entre 3 e 8 segundos)
                const duration = 3 + Math.random() * 5;
                snowflake.style.animationDuration = duration + 's';
                
                // Delay aleatório para começar
                snowflake.style.animationDelay = Math.random() * 2 + 's';
                
                // Tamanho aleatório
                const size = 0.5 + Math.random() * 1.5;
                snowflake.style.fontSize = size + 'em';
                
                document.body.appendChild(snowflake);
                snowflakeCount++;
                
                // Remover após a animação
                setTimeout(() => {
                    snowflake.remove();
                    snowflakeCount--;
                }, (duration + 2) * 1000);
            }
            
            // Criar flocos de neve continuamente
            function startSnowfall() {
                createSnowflake();
                const interval = 200 + Math.random() * 300; // Entre 200ms e 500ms
                setTimeout(startSnowfall, interval);
            }
            
            // Iniciar quando a página carregar
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', startSnowfall);
            } else {
                startSnowfall();
            }
        })();
    </script>
</body>
</html>