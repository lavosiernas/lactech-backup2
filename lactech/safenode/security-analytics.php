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

$pageTitle = 'Security Analytics';
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
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px -10px rgba(255, 255, 255, 0.5);
        }
        
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
        
        .upgrade-card {
            background: linear-gradient(135deg, rgba(255,255,255,0.15) 0%, rgba(255,255,255,0.1) 50%, rgba(0,0,0,0.3) 100%);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 20px;
            padding: 24px;
            position: relative;
            overflow: hidden;
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
            <a href="security-analytics.php" class="nav-item active">
                <i data-lucide="activity" class="w-5 h-5"></i>
                <span class="font-medium">Network</span>
            </a>
            <a href="behavior-analysis.php" class="nav-item">
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
                <div class="relative z-10">
                    <div class="w-14 h-14 rounded-2xl bg-white/10 flex items-center justify-center mb-5">
                        <i data-lucide="zap" class="w-7 h-7 text-white"></i>
                    </div>
                    <h3 class="font-bold text-white text-lg mb-1">Ativar Pro</h3>
                    <p class="text-sm text-white/60 mb-5 leading-relaxed">Desbloqueie recursos avançados de proteção</p>
                    <button class="w-full btn-primary py-3 text-sm">
                        Upgrade Agora
                    </button>
                </div>
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

            <div class="flex items-center gap-4">
                <div class="relative hidden md:block">
                    <i data-lucide="search" class="w-4 h-4 absolute left-4 top-1/2 -translate-y-1/2 text-zinc-500"></i>
                    <input type="text" placeholder="Buscar..." class="search-input">
                </div>
                
                <button @click="notificationsOpen = !notificationsOpen" class="relative p-3 text-zinc-400 hover:text-white hover:bg-white/5 rounded-xl transition-all">
                    <i data-lucide="bell" class="w-5 h-5"></i>
                    <span class="absolute top-2 right-2 w-2.5 h-2.5 bg-white rounded-full border-2 border-dark-900 animate-pulse"></span>
                </button>
                
                <button onclick="window.location.href='profile.php'" class="flex items-center gap-3 p-2 hover:bg-white/5 rounded-xl transition-all group">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-orange-400 to-rose-500 flex items-center justify-center text-white font-bold text-sm shadow-lg group-hover:scale-105 transition-transform">
                        <?php echo strtoupper(substr($_SESSION['safenode_username'] ?? 'U', 0, 1)); ?>
                    </div>
                </button>
            </div>
        </header>

        <!-- Scrollable Content -->
        <div class="flex-1 overflow-y-auto p-8">
            <div id="analytics-content" class="space-y-6">
                <div class="text-center py-12 text-zinc-500">
                    <i data-lucide="loader" class="w-12 h-12 mx-auto mb-4 animate-spin"></i>
                    <p class="text-sm">Carregando analytics...</p>
                </div>
            </div>
        </div>
    </main>

    <script>
        let analyticsData = null;

        async function fetchAnalytics() {
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
                    analyticsData = data.data?.analytics || {};
                    console.log('Analytics Data:', analyticsData);
                    updateAnalyticsPage();
                } else {
                    throw new Error(data.error || 'Erro desconhecido');
                }
            } catch (error) {
                console.error('Erro ao buscar analytics:', error);
                document.getElementById('analytics-content').innerHTML = `
                    <div class="table-card p-8 text-center">
                        <i data-lucide="alert-circle" class="w-12 h-12 mx-auto mb-4 text-red-400"></i>
                        <p class="text-red-400 font-bold mb-2">Erro ao carregar dados</p>
                        <p class="text-zinc-500 text-sm">${error.message}</p>
                        <button onclick="fetchAnalytics()" class="mt-4 px-4 py-2 btn-primary text-sm">Tentar novamente</button>
                    </div>
                `;
                lucide.createIcons();
            }
        }

        function updateAnalyticsPage() {
            if (!analyticsData) return;
            
            const container = document.getElementById('analytics-content');
            const insights = analyticsData.insights || [];
            const timePatterns = analyticsData.time_patterns || [];
            
            let html = '';
            
            // Insights
            if (insights.length > 0) {
                html += `
                    <div class="table-card p-6 mb-6">
                        <h2 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
                            <i data-lucide="lightbulb" class="w-6 h-6 text-white"></i>
                            Insights Automáticos
                        </h2>
                        <div class="space-y-3">
                            ${insights.map(insight => {
                                let severityClass = 'bg-white/5 border-white/10';
                                let iconName = 'info';
                                if (insight.severity === 'high') {
                                    severityClass = 'bg-red-500/10 border-red-500/20';
                                    iconName = 'alert-triangle';
                                } else if (insight.severity === 'warning') {
                                    severityClass = 'bg-amber-500/10 border-amber-500/20';
                                    iconName = 'trending-up';
                                } else if (insight.severity === 'medium') {
                                    severityClass = 'bg-orange-500/10 border-orange-500/20';
                                    iconName = 'alert-circle';
                                }
                                
                                if (insight.type === 'peak_time') iconName = 'clock';
                                else if (insight.type === 'suspicious_ip') iconName = 'alert-triangle';
                                else if (insight.type === 'trend') iconName = 'trending-up';
                                else if (insight.type === 'target') iconName = 'target';
                                
                                return `
                                    <div class="p-4 rounded-lg ${severityClass} border">
                                        <div class="flex items-start gap-3">
                                            <i data-lucide="${iconName}" class="w-5 h-5 text-white flex-shrink-0 mt-0.5"></i>
                                            <div class="flex-1">
                                                <h4 class="text-sm font-bold text-white mb-1">${insight.title}</h4>
                                                <p class="text-xs text-zinc-400">${insight.message}</p>
                                            </div>
                                        </div>
                                    </div>
                                `;
                            }).join('')}
                        </div>
                    </div>
                `;
            }
            
            // Padrões por Horário
            if (timePatterns.length > 0) {
                html += `
                    <div class="table-card p-6">
                        <h2 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
                            <i data-lucide="clock" class="w-6 h-6 text-white"></i>
                            Padrões de Ataque por Horário
                        </h2>
                        <div class="space-y-2">
                            ${timePatterns.slice(0, 10).map(pattern => {
                                const hour = pattern.hour;
                                const count = pattern.attack_count;
                                const maxCount = Math.max(...timePatterns.map(p => p.attack_count));
                                const percentage = maxCount > 0 ? (count / maxCount) * 100 : 0;
                                
                                return `
                                    <div class="flex items-center gap-4">
                                        <div class="w-16 text-sm text-zinc-400">${String(hour).padStart(2, '0')}:00</div>
                                        <div class="flex-1">
                                            <div class="h-6 rounded bg-dark-700 border border-white/5 relative overflow-hidden">
                                                <div class="h-full bg-white/20 rounded" style="width: ${percentage}%"></div>
                                                <div class="absolute inset-0 flex items-center px-2">
                                                    <span class="text-xs font-bold text-white">${count} ataques</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                `;
                            }).join('')}
                        </div>
                    </div>
                `;
            }
            
            if (html === '') {
                html = `
                    <div class="table-card p-8 text-center">
                        <i data-lucide="info" class="w-12 h-12 mx-auto mb-4 text-white"></i>
                        <p class="text-white font-bold mb-2">Nenhum dado disponível no momento</p>
                        <p class="text-zinc-500 text-sm">Os analytics serão exibidos quando houver dados suficientes de segurança</p>
                        <p class="text-zinc-600 text-xs mt-4">Aguardando eventos de segurança para análise...</p>
                    </div>
                `;
            }
            
            container.innerHTML = html;
            lucide.createIcons();
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                lucide.createIcons();
                console.log('DOM carregado, iniciando fetch...');
                fetchAnalytics();
                setInterval(fetchAnalytics, 15000);
            });
        } else {
            lucide.createIcons();
            console.log('DOM já carregado, iniciando fetch...');
            fetchAnalytics();
            setInterval(fetchAnalytics, 15000);
        }
    </script>
</body>
</html>
