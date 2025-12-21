<?php
/**
 * SafeNode - IPs Suspeitos
 */

session_start();
require_once __DIR__ . '/includes/SecurityHelpers.php';
SecurityHeaders::apply();

if (!isset($_SESSION['safenode_logged_in']) || $_SESSION['safenode_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/init.php';

$pageTitle = 'IPs Suspeitos';
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
        
        .table-card {
            background: var(--bg-card);
            border: 1px solid var(--border-subtle);
            border-radius: 20px;
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
            text-decoration: none;
        }
        
        .nav-item:hover {
            background: rgba(255,255,255,0.05);
            color: var(--text-primary);
        }
        
        .nav-item.active {
            background: rgba(255,255,255,0.1);
            color: var(--text-primary);
        }
        
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
            box-shadow: 0 10px 30px -10px rgba(255, 255, 255, 0.5);
        }
        
        .btn-primary:hover::before {
            opacity: 1;
        }
    </style>
</head>
<body class="h-full" x-data="{ sidebarOpen: false }">
    <div class="flex h-full">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="flex-1 flex flex-col h-full overflow-hidden bg-dark-950">
            <!-- Header -->
            <header class="h-20 bg-dark-900/50 backdrop-blur-xl border-b border-white/5 px-8 flex items-center justify-between flex-shrink-0">
                <div class="flex items-center gap-6">
                    <button data-sidebar-toggle class="lg:hidden text-zinc-400 hover:text-white transition-colors">
                        <i data-lucide="menu" class="w-6 h-6"></i>
                    </button>
                    <div>
                        <h2 class="text-2xl font-bold text-white tracking-tight">IPs Suspeitos</h2>
                        <?php if ($selectedSite): ?>
                            <p class="text-sm text-zinc-500 font-mono mt-0.5"><?php echo htmlspecialchars($selectedSite['domain'] ?? ''); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <div class="flex-1 overflow-y-auto p-8">
                <div id="suspicious-content" class="space-y-6">
                    <div class="glass rounded-2xl p-12 text-center">
                        <i data-lucide="loader" class="w-12 h-12 mx-auto mb-4 animate-spin text-zinc-400"></i>
                        <p class="text-sm text-zinc-500">Carregando IPs suspeitos...</p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        let suspiciousData = null;

        async function fetchSuspiciousIPs() {
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
                    suspiciousData = data.data?.analytics?.suspicious_ips || [];
                    console.log('Suspicious IPs Data:', suspiciousData);
                    updateSuspiciousPage();
                } else {
                    throw new Error(data.error || 'Erro desconhecido');
                }
            } catch (error) {
                console.error('Erro ao buscar IPs suspeitos:', error);
                document.getElementById('suspicious-content').innerHTML = `
                    <div class="glass rounded-2xl p-8 text-center">
                        <i data-lucide="alert-circle" class="w-12 h-12 mx-auto mb-4 text-red-400"></i>
                        <p class="text-red-400 font-bold mb-2">Erro ao carregar dados</p>
                        <p class="text-zinc-500 text-sm mb-4">${error.message}</p>
                        <button onclick="fetchSuspiciousIPs()" class="px-4 py-2 bg-white/10 text-white rounded-xl hover:bg-white/20 transition-colors text-sm">
                            Tentar novamente
                        </button>
                    </div>
                `;
                lucide.createIcons();
            }
        }

        function updateSuspiciousPage() {
            if (!suspiciousData) return;
            
            const container = document.getElementById('suspicious-content');
            const ips = suspiciousData || [];
            
            if (ips.length === 0) {
                container.innerHTML = `
                    <div class="table-card p-8 text-center">
                        <i data-lucide="check-circle" class="w-12 h-12 mx-auto mb-4 text-white"></i>
                        <p class="text-white font-bold mb-2">Nenhum IP suspeito detectado</p>
                        <p class="text-zinc-500 text-sm">Todas as rotas estão seguras</p>
                        <p class="text-zinc-600 text-xs mt-4">IPs suspeitos aparecerão aqui quando detectados pelo sistema</p>
                    </div>
                `;
                lucide.createIcons();
                return;
            }
            
            container.innerHTML = `
                <div class="glass rounded-2xl p-6 mb-6">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-semibold text-white">${ips.length} IP${ips.length !== 1 ? 's' : ''} Suspeito${ips.length !== 1 ? 's' : ''}</h2>
                        <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-red-500/20 text-red-400 text-xs font-semibold border border-red-500/30">
                            <span class="w-2 h-2 rounded-full bg-red-400"></span>
                            Análise Ativa
                        </div>
                    </div>
                </div>
                
                <div class="space-y-4">
                    ${ips.map(ip => {
                        let suspicionLevel = 'Médio';
                        let suspicionColor = 'amber';
                        if (ip.suspicion_score >= 70) {
                            suspicionLevel = 'Crítico';
                            suspicionColor = 'red';
                        } else if (ip.suspicion_score >= 50) {
                            suspicionLevel = 'Alto';
                            suspicionColor = 'orange';
                        }
                        
                        let suspicionBadgeClass = 'bg-amber-500/20 text-amber-400 border-amber-500/30';
                        if (ip.suspicion_score >= 70) {
                            suspicionBadgeClass = 'bg-red-500/20 text-red-400 border-red-500/30';
                        } else if (ip.suspicion_score >= 50) {
                            suspicionBadgeClass = 'bg-orange-500/20 text-orange-400 border-orange-500/30';
                        }
                        
                        return `
                            <div class="glass rounded-xl p-6">
                                <div class="flex items-start justify-between mb-4">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-3 mb-2">
                                            <p class="text-lg font-mono font-bold text-white">${ip.ip_address}</p>
                                            <span class="px-2.5 py-1 rounded-lg text-xs font-semibold ${suspicionBadgeClass}">
                                                ${suspicionLevel}
                                            </span>
                                        </div>
                                        <p class="text-sm text-zinc-400">
                                            Suspicion Score: <span class="text-white font-semibold">${ip.suspicion_score || 0}</span>
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                                    <div class="p-4 rounded-xl bg-white/5 border border-white/10">
                                        <p class="text-xs text-zinc-400 mb-1">Total Ataques</p>
                                        <p class="text-xl font-bold text-red-400">${ip.total_attacks || 0}</p>
                                    </div>
                                    <div class="p-4 rounded-xl bg-white/5 border border-white/10">
                                        <p class="text-xs text-zinc-400 mb-1">Tipos de Ataque</p>
                                        <p class="text-xl font-bold text-orange-400">${ip.attack_types_count || 0}</p>
                                    </div>
                                    <div class="p-4 rounded-xl bg-white/5 border border-white/10">
                                        <p class="text-xs text-zinc-400 mb-1">País</p>
                                        <p class="text-xl font-bold text-white">${ip.country_code || 'N/A'}</p>
                                    </div>
                                </div>
                                
                                ${ip.threat_types ? `
                                    <div class="mt-4 pt-4 border-t border-white/10">
                                        <p class="text-xs text-zinc-400 mb-2 font-semibold">Tipos de Ameaça Detectados</p>
                                        <p class="text-sm text-white font-mono">${ip.threat_types}</p>
                                    </div>
                                ` : ''}
                                
                                <div class="mt-4 pt-4 border-t border-white/10">
                                    <p class="text-xs text-zinc-400 mb-1 font-semibold">Última atividade</p>
                                    <p class="text-sm text-white">${new Date(ip.last_seen).toLocaleString('pt-BR')}</p>
                                </div>
                            </div>
                        `;
                    }).join('')}
                </div>
            `;
            
            lucide.createIcons();
        }

        // Aguardar DOM carregar
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                console.log('DOM carregado, iniciando fetch...');
                fetchSuspiciousIPs();
                setInterval(fetchSuspiciousIPs, 10000);
            });
        } else {
            console.log('DOM já carregado, iniciando fetch...');
            fetchSuspiciousIPs();
            setInterval(fetchSuspiciousIPs, 10000);
        }
        
                lucide.createIcons();
    </script>
</body>
</html>
