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

$pageTitle = 'Análise Comportamental';
$currentSiteId = $_SESSION['view_site_id'] ?? 0;
$userId = $_SESSION['safenode_user_id'] ?? null;
$selectedSite = null;

$db = getSafeNodeDatabase();
if ($db && $currentSiteId > 0) {
    try {
        $stmt = $db->prepare("SELECT * FROM safenode_sites WHERE id = ? AND user_id = ?");
        $stmt->execute([$currentSiteId, $userId]);
        $selectedSite = $stmt->fetch();
    } catch (PDOException $e) {
        $selectedSite = null;
    }
}
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
            background: linear-gradient(90deg, transparent 0%, rgba(255,255,255,0.1) 50%, transparent 100%);
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
        
        
        .upgrade-card {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 16px;
        }
        
        [x-cloak] { display: none !important; }
    </style>
</head>
<body x-data="{ notificationsOpen: false, sidebarOpen: false }" class="h-full overflow-hidden flex">
    <!-- Sidebar -->
    <aside class="sidebar w-72 h-full flex-shrink-0 flex flex-col hidden lg:flex">
        <div class="p-6 border-b border-white/5">
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2">
                    <img src="assets/img/logos (6).png" alt="SafeNode Logo" class="w-8 h-8 object-contain">
                    <div>
                        <h1 class="font-bold text-white text-xl tracking-tight">SafeNode</h1>
                        <p class="text-xs text-zinc-500 font-medium">Security Platform</p>
                    </div>
                </div>
                <button class="ml-auto text-zinc-600 hover:text-zinc-400 transition-colors">
                    <i data-lucide="chevrons-left" class="w-5 h-5"></i>
                </button>
            </div>
        </div>
        
        <nav class="flex-1 p-5 space-y-2 overflow-y-auto">
            <p class="text-xs font-semibold text-zinc-600 uppercase tracking-wider mb-4 px-3">Menu Principal</p>
            
            <a href="dashboard.php" class="nav-item">
                <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
                <span class="font-medium">Home</span>
            </a>
            <a href="sites.php" class="nav-item">
                <i data-lucide="globe" class="w-5 h-5"></i>
                <span class="font-medium">Gerenciar Sites</span>
            </a>
            <a href="security-analytics.php" class="nav-item">
                <i data-lucide="activity" class="w-5 h-5"></i>
                <span class="font-medium">Network</span>
            </a>
            <a href="behavior-analysis.php" class="nav-item active">
                <i data-lucide="cpu" class="w-5 h-5"></i>
                <span class="font-medium">Kubernetes</span>
            </a>
            <a href="logs.php" class="nav-item">
                <i data-lucide="compass" class="w-5 h-5"></i>
                <span class="font-medium">Explorar</span>
            </a>
            <a href="suspicious-ips.php" class="nav-item">
                <i data-lucide="bar-chart-3" class="w-5 h-5"></i>
                <span class="font-medium">Analisar</span>
            </a>
            <a href="attacked-targets.php" class="nav-item">
                <i data-lucide="users-2" class="w-5 h-5"></i>
                <span class="font-medium">Grupos</span>
            </a>
            
            <div class="pt-6 mt-6 border-t border-white/5">
                <p class="text-xs font-semibold text-zinc-600 uppercase tracking-wider mb-4 px-3">Sistema</p>
                <a href="human-verification.php" class="nav-item">
                    <i data-lucide="shield-check" class="w-5 h-5"></i>
                    <span class="font-medium">Verificação Humana</span>
                </a>
                <a href="settings.php" class="nav-item">
                    <i data-lucide="settings-2" class="w-5 h-5"></i>
                    <span class="font-medium">Configurações</span>
                </a>
                <a href="help.php" class="nav-item">
                    <i data-lucide="life-buoy" class="w-5 h-5"></i>
                    <span class="font-medium">Ajuda</span>
                </a>
            </div>
        </nav>
        
        <div class="p-5">
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
                    <?php if ($currentSiteId > 0 && $selectedSite): ?>
                    <p class="text-sm text-zinc-500 font-mono mt-0.5"><?php echo htmlspecialchars($selectedSite['domain'] ?? ''); ?></p>
                    <?php endif; ?>
                </div>
            </div>

        </header>

        <!-- Scrollable Content -->
        <div class="flex-1 overflow-y-auto p-8">
            <div id="behavior-content" class="space-y-6">
                <div class="text-center py-12 text-zinc-500">
                    <i data-lucide="loader" class="w-12 h-12 mx-auto mb-4 animate-spin"></i>
                    <p class="text-sm">Carregando análise comportamental...</p>
                </div>
            </div>
        </div>
    </main>

    <script>
        let behaviorData = null;

        async function fetchBehaviorAnalysis() {
            try {
                const response = await fetch('api/dashboard-stats.php');
                if (!response.ok) {
                    const errorText = await response.text();
                    console.error('HTTP Error:', response.status, errorText);
                    throw new Error(`HTTP ${response.status}: ${errorText.substring(0, 100)}`);
                }
                
                const data = await response.json();
                console.log('API Response:', data);
                
                if (data.success) {
                    behaviorData = data.data?.behavior_analysis || [];
                    console.log('Behavior Data:', behaviorData);
                    updateBehaviorPage();
                } else {
                    throw new Error(data.error || 'Erro desconhecido');
                }
            } catch (error) {
                console.error('Erro ao buscar análise comportamental:', error);
                document.getElementById('behavior-content').innerHTML = `
                    <div class="table-card p-8 text-center">
                        <i data-lucide="alert-circle" class="w-12 h-12 mx-auto mb-4 text-red-400"></i>
                        <p class="text-red-400 font-bold mb-2">Erro ao carregar dados</p>
                        <p class="text-zinc-500 text-sm">${error.message}</p>
                        <button onclick="fetchBehaviorAnalysis()" class="mt-4 px-4 py-2 btn-primary text-sm">Tentar novamente</button>
                    </div>
                `;
                lucide.createIcons();
            }
        }

        function updateBehaviorPage() {
            const container = document.getElementById('behavior-content');
            if (!container) {
                console.error('Container behavior-content não encontrado!');
                return;
            }
            
            const ips = behaviorData || [];
            console.log('Atualizando página com', ips.length, 'IPs');
            
            if (ips.length === 0) {
                container.innerHTML = `
                    <div class="table-card p-8 text-center">
                        <i data-lucide="check-circle" class="w-12 h-12 mx-auto mb-4 text-white"></i>
                        <p class="text-white font-bold mb-2">Nenhum comportamento suspeito detectado</p>
                        <p class="text-zinc-500 text-sm">Todos os IPs estão com comportamento normal</p>
                        <p class="text-zinc-600 text-xs mt-4">Os dados aparecerão aqui quando houver atividade suspeita detectada pelo sistema</p>
                    </div>
                `;
                lucide.createIcons();
                return;
            }
            
            container.innerHTML = `
                <div class="table-card p-6 mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-xl font-bold text-white">IPs Analisados</h2>
                        <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white/10 border border-white/20">
                            <span class="w-2 h-2 rounded-full bg-white"></span>
                            <span class="text-xs font-bold text-white">Sistema Próprio</span>
                        </div>
                    </div>
                    <p class="text-sm text-zinc-400">${ips.length} IP${ips.length !== 1 ? 's' : ''} com comportamento suspeito detectado</p>
                </div>
                
                <div class="space-y-4">
                    ${ips.map(ip => {
                        let riskBadge = '';
                        let riskColor = 'zinc';
                        if (ip.behavior_risk_level === 'critical') {
                            riskBadge = 'Crítico';
                            riskColor = 'red';
                        } else if (ip.behavior_risk_level === 'high') {
                            riskBadge = 'Alto';
                            riskColor = 'orange';
                        } else if (ip.behavior_risk_level === 'medium') {
                            riskBadge = 'Médio';
                            riskColor = 'amber';
                        } else {
                            riskBadge = 'Baixo';
                            riskColor = 'zinc';
                        }
                        
                        let riskBadgeClass = 'bg-zinc-500/10 text-zinc-400 border-zinc-500/20';
                        if (ip.behavior_risk_level === 'critical') {
                            riskBadgeClass = 'bg-red-500/10 text-red-400 border-red-500/20';
                        } else if (ip.behavior_risk_level === 'high') {
                            riskBadgeClass = 'bg-orange-500/10 text-orange-400 border-orange-500/20';
                        } else if (ip.behavior_risk_level === 'medium') {
                            riskBadgeClass = 'bg-amber-500/10 text-amber-400 border-amber-500/20';
                        }
                        
                        return `
                            <div class="stat-card">
                                <div class="flex items-start justify-between mb-4">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-3 mb-2">
                                            <p class="text-lg font-mono font-bold text-white">${ip.ip_address}</p>
                                            <div class="px-2 py-1 rounded text-xs font-bold ${riskBadgeClass}">
                                                ${riskBadge}
                                            </div>
                                        </div>
                                        <p class="text-sm text-zinc-400">
                                            Risk Score: <span class="text-white font-bold">${ip.behavior_risk_score || 0}</span> • 
                                            Anomalias: <span class="text-white font-bold">${ip.anomaly_count || 0}</span>
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                                    <div class="p-3 rounded-lg bg-dark-700 border border-white/5">
                                        <p class="text-xs text-zinc-400 mb-1">Total Requisições</p>
                                        <p class="text-lg font-bold text-white">${ip.total_requests || 0}</p>
                                    </div>
                                    <div class="p-3 rounded-lg bg-dark-700 border border-white/5">
                                        <p class="text-xs text-zinc-400 mb-1">Bloqueios</p>
                                        <p class="text-lg font-bold text-red-400">${ip.blocked_count || 0}</p>
                                    </div>
                                    <div class="p-3 rounded-lg bg-dark-700 border border-white/5">
                                        <p class="text-xs text-zinc-400 mb-1">Threat Score Médio</p>
                                        <p class="text-lg font-bold text-amber-400">${(ip.avg_threat_score || 0).toFixed(1)}</p>
                                    </div>
                                </div>
                                
                                <div class="mt-4 pt-4 border-t border-white/5">
                                    <p class="text-xs text-zinc-400 mb-2">Última atividade</p>
                                    <p class="text-sm text-white">${new Date(ip.last_seen).toLocaleString('pt-BR')}</p>
                                </div>
                            </div>
                        `;
                    }).join('')}
                </div>
            `;
            
            lucide.createIcons();
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                lucide.createIcons();
                console.log('DOM carregado, iniciando fetch...');
                fetchBehaviorAnalysis();
                setInterval(fetchBehaviorAnalysis, 10000);
            });
        } else {
            lucide.createIcons();
            console.log('DOM já carregado, iniciando fetch...');
            fetchBehaviorAnalysis();
            setInterval(fetchBehaviorAnalysis, 10000);
        }
    </script>
</body>
</html>
