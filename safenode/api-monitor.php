<?php
/**
 * SafeNode - Monitoramento de API (Verificação Humana)
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
require_once __DIR__ . '/includes/HVAPIKeyManager.php';

$db = getSafeNodeDatabase();
$userId = $_SESSION['safenode_user_id'] ?? null;

if (!$userId) {
    header('Location: login.php');
    exit;
}

// Obter dados da API Key selecionada
$keyId = isset($_GET['key_id']) ? (int)$_GET['key_id'] : null;
$period = $_GET['period'] ?? '24h';
$apiKeyData = null;

$userKeys = HVAPIKeyManager::getUserKeys($userId);
if ($keyId) {
    foreach ($userKeys as $key) {
        if ($key['id'] == $keyId) {
            $apiKeyData = $key;
            break;
        }
    }
} else {
    if (!empty($userKeys)) {
        $apiKeyData = $userKeys[0];
        $keyId = $apiKeyData['id'];
    }
}

if (!$apiKeyData) {
    header('Location: human-verification.php');
    exit;
}

$stats = HVAPIKeyManager::getAllStats($keyId, $userId, $period);
$pageTitle = 'Monitoramento de API';
$currentPage = 'human-verification';

// Mapeamento de códigos de países para nomes (Simplificado)
$countryNames = [
    'BR' => 'Brasil', 'US' => 'Estados Unidos', 'CN' => 'China', 'RU' => 'Rússia',
    'DE' => 'Alemanha', 'FR' => 'França', 'GB' => 'Reino Unido', 'IT' => 'Itália',
    'JP' => 'Japão', 'CA' => 'Canadá', 'AR' => 'Argentina', 'PT' => 'Portugal',
    'ES' => 'Espanha', 'MX' => 'México', 'IN' => 'Índia', 'SG' => 'Singapura'
];

/**
 * Retorna HTML com imagem da bandeira do país usando flagcdn.com
 */
function getCountryFlag($code) {
    if (empty($code) || strlen($code) !== 2) {
        return '<span class="text-gray-400">🌐</span>';
    }
    
    $code = strtolower(trim($code));
    
    // Validar que são letras válidas
    if (!preg_match('/^[a-z]{2}$/', $code)) {
        return '<span class="text-gray-400">🌐</span>';
    }
    
    // Usar flagcdn.com para exibir bandeiras como imagens SVG
    // Isso funciona em todos os sistemas operacionais e navegadores
    return '<img src="https://flagcdn.com/w20/' . htmlspecialchars($code) . '.png" 
                 srcset="https://flagcdn.com/w40/' . htmlspecialchars($code) . '.png 2x"
                 width="20" 
                 height="15" 
                 alt="' . htmlspecialchars(strtoupper($code)) . '"
                 class="inline-block align-middle rounded-sm"
                 style="object-fit: cover; border: 1px solid rgba(0,0,0,0.1);">';
}
?>
<!DOCTYPE html>
<html lang="pt-BR" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> | SafeNode</title>
    <link rel="icon" type="image/png" href="assets/img/logos (6).png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- jsVectorMap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/jsvectormap/dist/css/jsvectormap.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/jsvectormap"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsvectormap/dist/maps/world.js"></script>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="includes/theme-styles.css">
    <script src="includes/theme-toggle.js"></script>
    
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
        
        body {
            background-color: var(--bg-primary);
            color: var(--text-primary);
            font-family: 'Inter', sans-serif;
            font-size: 0.92em;
            -webkit-font-smoothing: antialiased;
        }

        .glass {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--border-subtle);
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
            background: var(--bg-hover);
        }

        .nav-item.active {
            color: var(--accent);
            background: linear-gradient(90deg, var(--gradient-overlay) 0%, transparent 100%);
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

        @media (max-width: 1023px) {
            aside[x-show*="sidebarOpen"] {
                position: fixed !important;
                z-index: 70 !important;
                transform: translateX(-100%) !important;
            }
            aside[x-show*="sidebarOpen"]:not([style*="translateX(-100%)"]) {
                transform: translateX(0) !important;
            }
        }
        
        .stat-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .stat-card:hover {
            background: var(--bg-hover);
            transform: translateY(-2px);
        }

        /* jsVectorMap Customization */
        .jvm-container { background-color: transparent !important; }
        .jvm-tooltip {
            background: rgba(0,0,0,0.8);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            padding: 8px 12px;
            font-family: 'Inter', sans-serif;
            font-size: 12px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.5);
        }
    </style>
</head>
<body class="h-full" x-data="{ sidebarOpen: false, sidebarCollapsed: false }">
    <div class="flex h-full">
        <!-- Sidebar Desktop -->
        <aside class="hidden lg:flex flex-col sidebar h-full flex-shrink-0 transition-all duration-300" 
               :class="sidebarCollapsed ? 'w-20' : 'w-72'">
            <div class="p-4 border-b border-gray-200 dark:border-white/5 flex-shrink-0 relative">
                <div class="flex items-center" :class="sidebarCollapsed ? 'justify-center flex-col gap-3' : 'justify-between'">
                    <div class="flex items-center gap-3">
                        <img src="assets/img/logos (6).png" alt="SafeNode Logo" class="w-8 h-8 object-contain flex-shrink-0">
                        <div x-show="!sidebarCollapsed" class="overflow-hidden whitespace-nowrap">
                            <h1 class="font-bold text-gray-900 dark:text-white text-xl tracking-tight">SafeNode</h1>
                            <p class="text-xs text-gray-500 dark:text-zinc-500 font-medium">Security Platform</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <nav class="flex-1 overflow-y-auto p-4 space-y-1">
                <a href="dashboard.php" class="nav-item">
                    <i data-lucide="layout-dashboard" class="w-5 h-5 flex-shrink-0"></i>
                    <span x-show="!sidebarCollapsed" class="font-medium">Dashboard</span>
                </a>
                <a href="sites.php" class="nav-item">
                    <i data-lucide="globe" class="w-5 h-5 flex-shrink-0"></i>
                    <span x-show="!sidebarCollapsed" class="font-medium">Sites</span>
                </a>
                <a href="human-verification.php" class="nav-item active">
                    <i data-lucide="shield-check" class="w-5 h-5 flex-shrink-0"></i>
                    <span x-show="!sidebarCollapsed" class="font-medium">Verificação Humana</span>
                </a>
                <a href="logs.php" class="nav-item">
                    <i data-lucide="file-text" class="w-5 h-5 flex-shrink-0"></i>
                    <span x-show="!sidebarCollapsed" class="font-medium">Logs</span>
                </a>
                <a href="suspicious-ips.php" class="nav-item">
                    <i data-lucide="alert-octagon" class="w-5 h-5 flex-shrink-0"></i>
                    <span x-show="!sidebarCollapsed" class="font-medium">IPs Suspeitos</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 flex flex-col h-full overflow-hidden bg-white dark:bg-dark-950">
            <!-- Header -->
            <header class="h-20 bg-white/80 dark:bg-dark-900/50 backdrop-blur-xl border-b border-gray-200 dark:border-white/5 px-4 md:px-8 flex items-center justify-between flex-shrink-0">
                <div class="flex items-center gap-6">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight"><?php echo $pageTitle; ?></h2>
                        <p class="text-sm text-gray-700 dark:text-zinc-500 mt-0.5">Estatísticas detalhadas para <?php echo htmlspecialchars($apiKeyData['name']); ?></p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <a href="human-verification.php" class="px-4 py-2 bg-gray-100 dark:bg-white/5 text-gray-900 dark:text-white rounded-xl hover:bg-gray-200 dark:hover:bg-white/10 transition-colors text-sm font-semibold flex items-center gap-2">
                        <i data-lucide="arrow-left" class="w-4 h-4"></i>
                        Voltar
                    </a>
                </div>
            </header>

            <!-- Scrollable Content -->
            <div class="flex-1 overflow-y-auto p-4 md:p-8">
                <div class="max-w-6xl mx-auto space-y-8">
                    
                    <!-- Filter & Selector -->
                    <div class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <select onchange="window.location.href='api-monitor.php?key_id='+this.value+'&period=<?php echo $period; ?>'" 
                                    class="bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 text-gray-900 dark:text-white rounded-xl px-4 py-2 focus:ring-2 focus:ring-blue-500 outline-none font-semibold">
                                <?php foreach ($userKeys as $key): ?>
                                    <option value="<?php echo $key['id']; ?>" <?php echo $key['id'] == $keyId ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($key['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="flex p-1 bg-gray-100 dark:bg-white/5 rounded-xl border border-gray-200 dark:border-white/10">
                            <?php 
                            $periods = ['1h' => '1 Hora', '24h' => '24 Horas', '7d' => '7 Dias', '30d' => '30 Dias'];
                            foreach ($periods as $p => $label): ?>
                                <a href="api-monitor.php?key_id=<?php echo $keyId; ?>&period=<?php echo $p; ?>" 
                                   class="px-4 py-1.5 rounded-lg text-sm font-medium transition-all <?php echo $period === $p ? 'bg-white dark:bg-white/10 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-zinc-500 hover:text-gray-900 dark:hover:text-white'; ?>">
                                    <?php echo $label; ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Stats Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="glass p-6 rounded-2xl stat-card">
                            <p class="text-xs font-bold text-gray-500 dark:text-zinc-500 uppercase tracking-widest mb-1">Total Requisições</p>
                            <h3 class="text-3xl font-bold text-gray-900 dark:text-white tracking-tight"><?php echo number_format($stats['usage']['total']); ?></h3>
                            <div class="mt-4 flex items-center text-blue-500 gap-1 text-xs font-bold">
                                <i data-lucide="trending-up" class="w-3 h-3"></i>
                                <span>Ativo</span>
                            </div>
                        </div>
                        <div class="glass p-6 rounded-2xl stat-card">
                            <p class="text-xs font-bold text-gray-500 dark:text-zinc-500 uppercase tracking-widest mb-1">Taxa de Sucesso</p>
                            <h3 class="text-3xl font-bold text-gray-900 dark:text-white tracking-tight"><?php echo $stats['usage']['success_rate']; ?>%</h3>
                            <div class="mt-4 flex items-center text-green-500 gap-1 text-xs font-bold">
                                <i data-lucide="check" class="w-3 h-3"></i>
                                <span>Excelente</span>
                            </div>
                        </div>
                        <div class="glass p-6 rounded-2xl stat-card">
                            <p class="text-xs font-bold text-gray-500 dark:text-zinc-500 uppercase tracking-widest mb-1">Pico por Minuto</p>
                            <h3 class="text-3xl font-bold text-gray-900 dark:text-white tracking-tight"><?php echo number_format($stats['performance']['peak_requests_per_minute']); ?></h3>
                            <div class="mt-4 flex items-center text-amber-500 gap-1 text-xs font-bold">
                                <i data-lucide="activity" class="w-3 h-3"></i>
                                <span>Processado</span>
                            </div>
                        </div>
                        <div class="glass p-6 rounded-2xl stat-card">
                            <p class="text-xs font-bold text-gray-500 dark:text-zinc-500 uppercase tracking-widest mb-1">Países Ativos</p>
                            <h3 class="text-3xl font-bold text-gray-900 dark:text-white tracking-tight"><?php echo count($stats['geo'] ?? []); ?></h3>
                            <div class="mt-4 flex items-center text-red-500 gap-1 text-xs font-bold">
                                <i data-lucide="globe" class="w-3 h-3"></i>
                                <span>Global</span>
                            </div>
                        </div>
                    </div>

                    <!-- World Map Section -->
                    <div class="glass rounded-2xl p-8 overflow-hidden relative">
                        <div class="flex flex-col md:flex-row items-start md:items-center justify-between mb-8 gap-4">
                            <div>
                                <h3 class="text-xl font-bold text-gray-900 dark:text-white leading-tight">Distribuição Geográfica</h3>
                                <p class="text-sm text-gray-500 dark:text-zinc-400">Origem do tráfego mundial da sua API Key</p>
                            </div>
                            <div class="px-4 py-2 bg-blue-500/10 rounded-xl border border-blue-500/20 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
                                <span class="text-xs font-bold text-blue-500">Live Traffic</span>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-center">
                            <!-- Map -->
                            <div class="lg:col-span-2 h-[400px] w-full relative">
                                <div id="world-map" class="w-full h-full"></div>
                            </div>
                            
                            <!-- Country List -->
                            <div class="space-y-4">
                                <h4 class="text-xs font-bold text-gray-400 dark:text-zinc-600 uppercase tracking-widest mb-2">Principais Países</h4>
                                <?php if (empty($stats['geo'] ?? [])): ?>
                                    <div class="p-8 text-center glass rounded-xl border-dashed border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                                        <i data-lucide="map-pin" class="w-12 h-12 mx-auto mb-4 text-gray-400 dark:text-gray-600"></i>
                                        <p class="text-sm font-semibold text-gray-500 dark:text-gray-400">
                                            Nenhum dado geográfico disponível ainda
                                        </p>
                                    </div>
                                <?php else: ?>
                                    <?php 
                                    $topGeo = array_slice($stats['geo'] ?? [], 0, 5, true);
                                    foreach ($topGeo as $code => $data): 
                                        $name = $countryNames[$code] ?? $code;
                                        $percent = ($stats['usage']['total'] ?? 0) > 0 ? round(($data['count'] / $stats['usage']['total']) * 100, 1) : 0;
                                    ?>
                                    <div class="p-4 rounded-xl bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/5">
                                        <div class="flex items-center justify-between mb-2">
                                            <div class="flex items-center gap-3">
                                                <?php echo getCountryFlag($code); ?>
                                                <span class="text-sm font-bold text-gray-900 dark:text-white"><?php echo $name; ?></span>
                                            </div>
                                            <span class="text-xs font-bold text-blue-500"><?php echo $percent; ?>%</span>
                                        </div>
                                        <div class="w-full bg-gray-200 dark:bg-white/5 rounded-full h-1.5">
                                            <div class="bg-blue-500 h-1.5 rounded-full transition-all duration-1000" style="width: <?php echo $percent; ?>%"></div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Grid -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <div class="lg:col-span-2 glass rounded-2xl p-8">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-8">Tráfego da API (24h)</h3>
                            <div class="h-80 w-full">
                                <canvas id="usageChart"></canvas>
                            </div>
                        </div>
                        <div class="glass rounded-2xl p-8">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-8">Distribuição de Status</h3>
                            <div class="h-64 w-full mb-8">
                                <canvas id="typeDistributionChart"></canvas>
                            </div>
                            <div class="space-y-3">
                                <?php if (empty($stats['performance']['distribution'] ?? [])): ?>
                                    <div class="p-4 text-center text-sm text-zinc-500">
                                        Nenhum dado disponível
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($stats['performance']['distribution'] ?? [] as $item): ?>
                                    <div class="flex items-center justify-between p-3 rounded-xl bg-gray-50 dark:bg-white/5">
                                        <span class="text-sm font-semibold capitalize text-gray-700 dark:text-zinc-400"><?php echo htmlspecialchars($item['type'] ?? 'unknown'); ?></span>
                                        <span class="text-sm font-bold text-gray-900 dark:text-white"><?php echo number_format($item['percentage'] ?? 0, 1); ?>%</span>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            lucide.createIcons();
            const isDark = document.documentElement.classList.contains('dark');
            

            // --- WORLD MAP ---
            const geoData = <?php echo json_encode($stats['geo'] ?? []); ?>;
            const mapValues = {};
            if (geoData && Object.keys(geoData).length > 0) {
                Object.keys(geoData).forEach(code => {
                    mapValues[code] = geoData[code].count || 0;
                });
            }

            const map = new jsVectorMap({
                selector: '#world-map',
                map: 'world',
                backgroundColor: 'transparent',
                draggable: true,
                zoomButtons: false,
                zoomOnScroll: false,
                regionStyle: {
                    initial: {
                        fill: isDark ? 'rgba(255, 255, 255, 0.05)' : '#f1f1f1',
                        stroke: isDark ? 'rgba(255, 255, 255, 0.1)' : '#e0e0e0',
                        strokeWidth: 0.5,
                        fillOpacity: 1
                    },
                    hover: {
                        fill: '#3b82f6',
                        fillOpacity: 0.8
                    }
                },
                series: {
                    regions: [{
                        values: mapValues,
                        scale: ['#3b82f6', '#1d4ed8'],
                        normalizeFunction: 'polynomial'
                    }]
                },
                onRegionTooltipShow(event, tooltip, code) {
                    const count = mapValues[code] || 0;
                    tooltip.text(
                        `<div class="font-bold mb-1">${tooltip.text()}</div>
                         <div class="text-blue-400">${count.toLocaleString()} requisições</div>`,
                        true
                    );
                }
            });

            // --- USAGE CHART ---
            const usageData = <?php echo json_encode($stats['usage']['hourly'] ?? []); ?>;
            const usageCtx = document.getElementById('usageChart').getContext('2d');
            
            new Chart(usageCtx, {
                type: 'line',
                data: {
                    labels: usageData.length > 0 ? usageData.map(d => d.hour ? d.hour.split(' ')[1].substr(0, 5) : '') : [],
                    datasets: [
                        {
                            label: 'Total',
                            data: usageData.length > 0 ? usageData.map(d => d.total || 0) : [0],
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            fill: true,
                            tension: 0.4
                        },
                        {
                            label: 'Sucesso',
                            data: usageData.length > 0 ? usageData.map(d => d.success || 0) : [0],
                            borderColor: '#22c55e',
                            backgroundColor: 'transparent',
                            tension: 0.4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { 
                            beginAtZero: true, 
                            grid: { 
                                color: isDark ? 'rgba(255, 255, 255, 0.05)' : 'rgba(0, 0, 0, 0.05)',
                                drawBorder: true,
                                borderColor: isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)'
                            },
                            ticks: { color: '#71717a' }
                        },
                        x: { 
                            grid: { 
                                display: true,
                                color: isDark ? 'rgba(255, 255, 255, 0.05)' : 'rgba(0, 0, 0, 0.05)',
                                drawBorder: true,
                                borderColor: isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)'
                            },
                            ticks: { color: '#71717a' }
                        }
                    }
                }
            });

            // --- DISTRIBUTION CHART ---
            const typeData = <?php echo json_encode($stats['performance']['distribution'] ?? []); ?>;
            const typeCtx = document.getElementById('typeDistributionChart').getContext('2d');
            
            if (typeData.length > 0) {
                new Chart(typeCtx, {
                    type: 'doughnut',
                    data: {
                        labels: typeData.map(d => (d.type || 'unknown').toUpperCase()),
                        datasets: [{
                            data: typeData.map(d => d.count || 0),
                            backgroundColor: ['#3b82f6', '#22c55e', '#ef4444', '#f59e0b', '#8b5cf6'],
                            borderWidth: 0,
                            hoverOffset: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '80%',
                        plugins: { legend: { display: false } }
                    }
                });
            } else {
                // Mostrar mensagem quando não há dados
                typeCtx.fillStyle = '#71717a';
                typeCtx.font = '14px Inter';
                typeCtx.textAlign = 'center';
                typeCtx.fillText('Nenhum dado disponível', typeCtx.canvas.width / 2, typeCtx.canvas.height / 2);
            }
        });
    </script>
</body>
</html>
