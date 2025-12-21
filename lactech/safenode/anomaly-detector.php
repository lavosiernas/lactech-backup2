<?php
/**
 * SafeNode - Anomaly Detector
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
require_once __DIR__ . '/includes/AnomalyDetector.php';

$pageTitle = 'Anomaly Detector';
$currentSiteId = $_SESSION['view_site_id'] ?? 0;
$userId = $_SESSION['safenode_user_id'] ?? null;

$db = getSafeNodeDatabase();
$detector = new AnomalyDetector($db);

// Parâmetros
$searchIP = $_GET['ip'] ?? '';
$timeWindow = isset($_GET['window']) ? (int)$_GET['window'] : 3600;

// Detectar anomalias globais
$globalAnomalies = $detector->detectGlobalAnomalies($currentSiteId, $timeWindow, 50);
$recentAnomalies = $detector->getRecentAnomalies($currentSiteId, 24, 20);
$stats = $detector->getAnomalyStats($currentSiteId, 7);
$anomalyTypes = $detector->getAnomalyTypes($currentSiteId, 7);

// Se IP específico foi pesquisado
$ipAnomalies = null;
$patternAnomalies = [];
if (!empty($searchIP) && filter_var($searchIP, FILTER_VALIDATE_IP)) {
    $ipAnomalies = $detector->detectAnomalies($searchIP, $timeWindow);
    $patternAnomalies = $detector->detectPatternAnomalies($searchIP, $timeWindow);
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
                <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden text-zinc-400 hover:text-white transition-colors">
                    <i data-lucide="menu" class="w-6 h-6"></i>
                </button>
                <div>
                    <h2 class="text-2xl font-bold text-white tracking-tight"><?php echo $pageTitle; ?></h2>
                </div>
            </div>
        </header>

        <!-- Content -->
        <div class="flex-1 overflow-y-auto p-8">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-white mb-2">Anomaly Detector</h1>
                <p class="text-zinc-400">Detecção de anomalias comportamentais usando análise estatística avançada</p>
            </div>
            
            <!-- Estatísticas -->
            <?php if ($stats): ?>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <div class="bg-dark-800 border border-white/10 rounded-xl p-6">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-zinc-400 text-sm">IPs Escaneados</span>
                        <i data-lucide="network" class="w-5 h-5 text-blue-400"></i>
                    </div>
                    <div class="text-2xl font-bold text-white"><?php echo number_format($stats['total_ips_scanned'] ?? 0); ?></div>
                </div>
                <div class="bg-dark-800 border border-white/10 rounded-xl p-6">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-zinc-400 text-sm">Anomalias Detectadas</span>
                        <i data-lucide="alert-triangle" class="w-5 h-5 text-orange-400"></i>
                    </div>
                    <div class="text-2xl font-bold text-white"><?php echo number_format($stats['total_anomalies'] ?? 0); ?></div>
                </div>
                <div class="bg-dark-800 border border-white/10 rounded-xl p-6">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-zinc-400 text-sm">Score Médio</span>
                        <i data-lucide="activity" class="w-5 h-5 text-yellow-400"></i>
                    </div>
                    <div class="text-2xl font-bold text-white"><?php echo round($stats['avg_anomaly_score'] ?? 0, 1); ?></div>
                </div>
                <div class="bg-dark-800 border border-white/10 rounded-xl p-6">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-zinc-400 text-sm">Score Máximo</span>
                        <i data-lucide="trending-up" class="w-5 h-5 text-red-400"></i>
                    </div>
                    <div class="text-2xl font-bold text-white"><?php echo round($stats['max_anomaly_score'] ?? 0, 1); ?></div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Busca de IP -->
            <div class="bg-dark-800 border border-white/10 rounded-xl p-6 mb-6">
                <h2 class="text-lg font-bold text-white mb-4">Verificar IP</h2>
                <form method="GET" class="flex gap-4">
                    <input 
                        type="text" 
                        name="ip" 
                        value="<?php echo htmlspecialchars($searchIP); ?>"
                        placeholder="Digite um endereço IP para analisar..."
                        class="flex-1 px-4 py-3 bg-dark-700 border border-white/10 rounded-lg text-white placeholder-zinc-500 focus:outline-none focus:border-white/30 transition-colors font-mono"
                    >
                    <select name="window" class="px-4 py-3 bg-dark-700 border border-white/10 rounded-lg text-white focus:outline-none focus:border-white/30">
                        <option value="3600" <?php echo $timeWindow === 3600 ? 'selected' : ''; ?>>Última hora</option>
                        <option value="7200" <?php echo $timeWindow === 7200 ? 'selected' : ''; ?>>Últimas 2 horas</option>
                        <option value="21600" <?php echo $timeWindow === 21600 ? 'selected' : ''; ?>>Últimas 6 horas</option>
                        <option value="86400" <?php echo $timeWindow === 86400 ? 'selected' : ''; ?>>Últimas 24 horas</option>
                    </select>
                    <button type="submit" class="px-6 py-3 bg-white text-black rounded-lg font-semibold hover:bg-zinc-200 transition-colors">
                        <i data-lucide="search" class="w-4 h-4 inline-block mr-2"></i>
                        Analisar
                    </button>
                </form>
                
                <?php if ($ipAnomalies): 
                    $score = (int)($ipAnomalies['anomaly_score'] ?? 0);
                    $scoreColor = $score >= 70 ? 'text-red-400' : ($score >= 40 ? 'text-yellow-400' : 'text-green-400');
                    $scoreBg = $score >= 70 ? 'bg-red-500/10 border-red-500/30' : ($score >= 40 ? 'bg-yellow-500/10 border-yellow-500/30' : 'bg-green-500/10 border-green-500/30');
                ?>
                <div class="mt-6 pt-6 border-t border-white/10">
                    <h3 class="text-lg font-bold text-white mb-4">Análise de Anomalias: <?php echo htmlspecialchars($searchIP); ?></h3>
                    
                    <div class="mb-6 <?php echo $scoreBg; ?> border rounded-lg p-6">
                        <div class="flex items-center justify-between mb-2">
                            <div>
                                <div class="text-zinc-400 text-sm mb-1">Score de Anomalia</div>
                                <div class="text-4xl font-bold <?php echo $scoreColor; ?>"><?php echo $score; ?>/100</div>
                            </div>
                            <div class="text-right">
                                <div class="text-zinc-400 text-sm mb-1">Status</div>
                                <div class="text-xl font-bold <?php echo $ipAnomalies['is_anomaly'] ? 'text-red-400' : 'text-green-400'; ?>">
                                    <?php echo $ipAnomalies['is_anomaly'] ? 'Anomalia Detectada' : 'Normal'; ?>
                                </div>
                            </div>
                        </div>
                        <div class="w-full bg-dark-700 rounded-full h-3 mt-4">
                            <div class="<?php echo $score >= 70 ? 'bg-red-500' : ($score >= 40 ? 'bg-yellow-500' : 'bg-green-500'); ?> h-3 rounded-full transition-all duration-500" style="width: <?php echo $score; ?>%"></div>
                        </div>
                    </div>
                    
                    <?php if (!empty($ipAnomalies['anomalies']) || !empty($patternAnomalies)): ?>
                    <div class="space-y-4">
                        <h4 class="text-md font-semibold text-white">Anomalias Detectadas:</h4>
                        <?php foreach (array_merge($ipAnomalies['anomalies'] ?? [], $patternAnomalies) as $anomaly): 
                            $severityBadge = $anomaly['severity'] === 'high' ? 'bg-red-500/20 text-red-400 border-red-500/30' : 
                                            ($anomaly['severity'] === 'medium' ? 'bg-orange-500/20 text-orange-400 border-orange-500/30' : 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30');
                        ?>
                        <div class="p-4 rounded-lg bg-dark-700 border border-white/5">
                            <div class="flex items-start justify-between mb-2">
                                <div class="flex-1">
                                    <div class="text-sm font-medium text-white mb-1 capitalize">
                                        <?php echo str_replace('_', ' ', $anomaly['type'] ?? 'anomalia'); ?>
                                    </div>
                                    <div class="text-xs text-zinc-400">
                                        <?php echo htmlspecialchars($anomaly['description'] ?? ''); ?>
                                    </div>
                                    <?php if (isset($anomaly['z_score'])): ?>
                                    <div class="text-xs text-zinc-500 mt-1">Z-Score: <?php echo $anomaly['z_score']; ?></div>
                                    <?php endif; ?>
                                    <?php if (isset($anomaly['count'])): ?>
                                    <div class="text-xs text-zinc-500 mt-1">Ocorrências: <?php echo $anomaly['count']; ?></div>
                                    <?php endif; ?>
                                </div>
                                <span class="px-2 py-1 text-xs font-bold border rounded <?php echo $severityBadge; ?> ml-3">
                                    <?php echo ucfirst($anomaly['severity'] ?? 'medium'); ?>
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-8">
                        <i data-lucide="check-circle" class="w-16 h-16 mx-auto mb-3 text-green-400 opacity-50"></i>
                        <p class="text-zinc-400">Nenhuma anomalia detectada para este IP</p>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Anomalias Globais -->
                <div class="lg:col-span-2 bg-dark-800 border border-white/10 rounded-xl p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-xl font-bold text-white">Anomalias Detectadas</h2>
                        <span class="text-sm text-zinc-400"><?php echo count($globalAnomalies); ?> anomalia(s)</span>
                    </div>
                    <div class="space-y-3">
                        <?php if (empty($globalAnomalies)): ?>
                        <div class="text-center py-12">
                            <i data-lucide="check-circle" class="w-16 h-16 mx-auto mb-3 text-green-400 opacity-50"></i>
                            <p class="text-zinc-400 font-medium mb-1">Nenhuma anomalia detectada</p>
                            <p class="text-zinc-500 text-sm">O comportamento está dentro dos padrões normais</p>
                        </div>
                        <?php else: ?>
                        <?php foreach ($globalAnomalies as $anomaly): 
                            $score = (int)($anomaly['anomaly_score'] ?? 0);
                            $scoreColor = $score >= 70 ? 'text-red-400' : ($score >= 40 ? 'text-yellow-400' : 'text-zinc-400');
                        ?>
                        <div class="p-4 rounded-lg bg-dark-700 border border-white/5 hover:border-white/10 transition-colors cursor-pointer" onclick="window.location.href='?ip=<?php echo urlencode($anomaly['ip_address']); ?>'">
                            <div class="flex items-start justify-between mb-2">
                                <div class="flex-1">
                                    <div class="font-mono text-sm text-white mb-1"><?php echo htmlspecialchars($anomaly['ip_address']); ?></div>
                                    <div class="text-xs text-zinc-400">
                                        <?php echo count($anomaly['anomalies'] ?? []); ?> tipo(s) de anomalia detectado(s)
                                    </div>
                                    <?php if (!empty($anomaly['anomalies'])): ?>
                                    <div class="mt-2 space-y-1">
                                        <?php foreach (array_slice($anomaly['anomalies'], 0, 2) as $anom): ?>
                                        <div class="text-xs text-zinc-500">
                                            • <?php echo htmlspecialchars($anom['description'] ?? ''); ?>
                                        </div>
                                        <?php endforeach; ?>
                                        <?php if (count($anomaly['anomalies']) > 2): ?>
                                        <div class="text-xs text-zinc-600">... e mais <?php echo count($anomaly['anomalies']) - 2; ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="text-right ml-4">
                                    <div class="text-xl font-bold <?php echo $scoreColor; ?>"><?php echo $score; ?></div>
                                    <div class="text-xs text-zinc-500">score</div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Tipos de Anomalias -->
                    <?php if (!empty($anomalyTypes)): ?>
                    <div class="bg-dark-800 border border-white/10 rounded-xl p-6">
                        <h3 class="text-lg font-bold text-white mb-4">Tipos de Anomalias (7 dias)</h3>
                        <div class="space-y-3">
                            <?php foreach ($anomalyTypes as $type => $count): ?>
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <div class="text-sm text-white font-medium capitalize">
                                        <?php echo str_replace('_', ' ', $type); ?>
                                    </div>
                                </div>
                                <div class="text-lg font-bold text-zinc-300"><?php echo $count; ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Anomalias Recentes -->
                    <?php if (!empty($recentAnomalies)): ?>
                    <div class="bg-dark-800 border border-white/10 rounded-xl p-6">
                        <h3 class="text-lg font-bold text-white mb-4">Anomalias Recentes (24h)</h3>
                        <div class="space-y-3">
                            <?php foreach (array_slice($recentAnomalies, 0, 5) as $anomaly): ?>
                            <div class="p-3 rounded-lg bg-dark-700 border border-white/5">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="font-mono text-sm text-white"><?php echo htmlspecialchars($anomaly['ip_address']); ?></span>
                                    <span class="px-2 py-0.5 text-xs font-bold <?php echo $anomaly['anomaly_score'] >= 70 ? 'bg-red-500/20 text-red-400' : 'bg-yellow-500/20 text-yellow-400'; ?> rounded">
                                        <?php echo round($anomaly['anomaly_score']); ?>
                                    </span>
                                </div>
                                <div class="text-xs text-zinc-400">
                                    <?php echo count($anomaly['anomalies'] ?? []); ?> anomalia(s)
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    </div>
    <script>lucide.createIcons();</script>
</body>
</html>

