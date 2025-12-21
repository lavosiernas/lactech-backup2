<?php
/**
 * SafeNode - Threat Intelligence
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
require_once __DIR__ . '/includes/ThreatIntelligenceNetwork.php';
require_once __DIR__ . '/includes/ThreatIntelligence.php';

$pageTitle = 'Threat Intelligence';
$currentSiteId = $_SESSION['view_site_id'] ?? 0;
$userId = $_SESSION['safenode_user_id'] ?? null;

$db = getSafeNodeDatabase();
$threatNetwork = new ThreatIntelligenceNetwork($db);
$threatIntel = new ThreatIntelligence($db);

// Parâmetros
$searchIP = $_GET['ip'] ?? '';
$threatType = $_GET['type'] ?? '';
$minSeverity = isset($_GET['severity']) ? (int)$_GET['severity'] : 70;

// Obter estatísticas
$stats = $threatNetwork->getNetworkStats();
$globalThreats = $threatNetwork->getGlobalThreats(100, $minSeverity, 60);
$recentThreats = $threatNetwork->getRecentThreats(24, 20);
$threatsByType = $threatNetwork->getThreatsByType();
$topPatterns = $threatNetwork->getTopAttackPatterns(10);

// Se IP específico foi pesquisado
$ipDetails = null;
if (!empty($searchIP) && filter_var($searchIP, FILTER_VALIDATE_IP)) {
    $ipDetails = $threatIntel->checkIP($searchIP);
}

// Filtrar ameaças por tipo se especificado
if (!empty($threatType)) {
    $globalThreats = array_filter($globalThreats, function($threat) use ($threatType) {
        return $threat['threat_type'] === $threatType;
    });
}

// Verificar status das APIs
$apiStatus = $threatIntel->isConfigured();

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
                    <h1 class="text-3xl font-bold text-white mb-2">Threat Intelligence</h1>
                    <p class="text-zinc-400">Rede colaborativa de inteligência de ameaças e integração com fontes externas</p>
                </div>
                
                <!-- Status das APIs -->
                <div class="bg-dark-800 border border-white/10 rounded-xl p-4 mb-6">
                    <div class="flex items-center justify-between flex-wrap gap-4">
                        <div class="flex items-center gap-6">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full <?php echo $apiStatus['safenode_network'] ? 'bg-green-500' : 'bg-red-500'; ?>"></span>
                                <span class="text-sm text-zinc-300">SafeNode Network</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full <?php echo $apiStatus['abuseipdb'] ? 'bg-green-500' : 'bg-zinc-500'; ?>"></span>
                                <span class="text-sm text-zinc-300">AbuseIPDB <?php echo $apiStatus['abuseipdb'] ? '(Ativo)' : '(Não configurado)'; ?></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full <?php echo $apiStatus['virustotal'] ? 'bg-green-500' : 'bg-zinc-500'; ?>"></span>
                                <span class="text-sm text-zinc-300">VirusTotal <?php echo $apiStatus['virustotal'] ? '(Ativo)' : '(Não configurado)'; ?></span>
                            </div>
                        </div>
                        <div class="text-xs text-zinc-500">
                            <span><?php echo $apiStatus['source_count'] ?? 0; ?> fonte(s) ativa(s)</span>
                        </div>
                    </div>
                </div>
                
                <!-- Busca de IP -->
                <div class="bg-dark-800 border border-white/10 rounded-xl p-6 mb-6">
                    <h2 class="text-lg font-bold text-white mb-4">Verificar IP</h2>
                    <form method="GET" class="flex gap-4">
                        <input 
                            type="text" 
                            name="ip" 
                            value="<?php echo htmlspecialchars($searchIP); ?>"
                            placeholder="Digite um endereço IP para verificar..."
                            class="flex-1 px-4 py-3 bg-dark-700 border border-white/10 rounded-lg text-white placeholder-zinc-500 focus:outline-none focus:border-white/30 transition-colors font-mono"
                        >
                        <button type="submit" class="px-6 py-3 btn-primary">
                            <i data-lucide="search" class="w-4 h-4 inline-block mr-2"></i>
                            Verificar
                        </button>
                    </form>
                    
                    <?php if ($ipDetails): ?>
                    <div class="mt-6 pt-6 border-t border-white/10">
                        <h3 class="text-lg font-bold text-white mb-4">Resultado da Verificação: <?php echo htmlspecialchars($searchIP); ?></h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div class="p-4 rounded-lg <?php echo $ipDetails['is_malicious'] ? 'bg-red-500/10 border border-red-500/30' : 'bg-green-500/10 border border-green-500/30'; ?>">
                                <div class="text-sm text-zinc-400 mb-1">Status</div>
                                <div class="text-xl font-bold <?php echo $ipDetails['is_malicious'] ? 'text-red-400' : 'text-green-400'; ?>">
                                    <?php echo $ipDetails['is_malicious'] ? 'Malicioso' : 'Limpo'; ?>
                                </div>
                            </div>
                            <div class="p-4 rounded-lg bg-dark-700 border border-white/5">
                                <div class="text-sm text-zinc-400 mb-1">Confiança</div>
                                <div class="text-xl font-bold text-white"><?php echo $ipDetails['confidence']; ?>%</div>
                            </div>
                            <div class="p-4 rounded-lg bg-dark-700 border border-white/5">
                                <div class="text-sm text-zinc-400 mb-1">Reputação</div>
                                <div class="text-xl font-bold text-white"><?php echo $ipDetails['reputation_score']; ?>/100</div>
                            </div>
                            <div class="p-4 rounded-lg bg-dark-700 border border-white/5">
                                <div class="text-sm text-zinc-400 mb-1">Fontes Consultadas</div>
                                <div class="text-xl font-bold text-white"><?php echo count($ipDetails['sources'] ?? []); ?></div>
                            </div>
                        </div>
                        <?php if (!empty($ipDetails['sources'])): ?>
                        <div class="mt-4">
                            <h4 class="text-sm font-semibold text-white mb-2">Detalhes por Fonte:</h4>
                            <div class="space-y-2">
                                <?php foreach ($ipDetails['sources'] as $sourceName => $sourceData): ?>
                                <div class="p-3 rounded bg-dark-700 border border-white/5">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-sm font-medium text-white capitalize"><?php echo str_replace('_', ' ', $sourceName); ?></span>
                                        <?php if (isset($sourceData['is_malicious']) && $sourceData['is_malicious']): ?>
                                        <span class="px-2 py-1 text-xs font-bold bg-red-500/20 text-red-400 rounded">Malicioso</span>
                                        <?php else: ?>
                                        <span class="px-2 py-1 text-xs font-bold bg-green-500/20 text-green-400 rounded">Limpo</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (isset($sourceData['confidence'])): ?>
                                    <div class="text-xs text-zinc-400">Confiança: <?php echo $sourceData['confidence']; ?>%</div>
                                    <?php endif; ?>
                                    <?php if (isset($sourceData['description'])): ?>
                                    <div class="text-xs text-zinc-500 mt-1"><?php echo htmlspecialchars($sourceData['description']); ?></div>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($stats): ?>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                    <div class="bg-dark-800 border border-white/10 rounded-xl p-6">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-zinc-400 text-sm">IPs Únicos</span>
                            <i data-lucide="shield-alert" class="w-5 h-5 text-red-400"></i>
                        </div>
                        <div class="text-2xl font-bold text-white"><?php echo number_format($stats['overall']['total_ips'] ?? 0); ?></div>
                    </div>
                    <div class="bg-dark-800 border border-white/10 rounded-xl p-6">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-zinc-400 text-sm">Total de Ameaças</span>
                            <i data-lucide="alert-triangle" class="w-5 h-5 text-orange-400"></i>
                        </div>
                        <div class="text-2xl font-bold text-white"><?php echo number_format($stats['overall']['total_threats'] ?? 0); ?></div>
                    </div>
                    <div class="bg-dark-800 border border-white/10 rounded-xl p-6">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-zinc-400 text-sm">Ocorrências</span>
                            <i data-lucide="activity" class="w-5 h-5 text-blue-400"></i>
                        </div>
                        <div class="text-2xl font-bold text-white"><?php echo number_format($stats['overall']['total_occurrences'] ?? 0); ?></div>
                    </div>
                    <div class="bg-dark-800 border border-white/10 rounded-xl p-6">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-zinc-400 text-sm">Sites Afetados</span>
                            <i data-lucide="globe" class="w-5 h-5 text-green-400"></i>
                        </div>
                        <div class="text-2xl font-bold text-white"><?php echo number_format($stats['overall']['total_affected_sites'] ?? 0); ?></div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Filtros -->
                <div class="flex gap-4 mb-6">
                    <form method="GET" class="flex gap-4 flex-1">
                        <select name="type" class="px-4 py-2.5 bg-dark-800 border border-white/10 rounded-lg text-white focus:outline-none focus:border-white/30">
                            <option value="">Todos os tipos</option>
                            <?php if (!empty($stats['by_type'])): ?>
                                <?php foreach ($stats['by_type'] as $type): ?>
                                <option value="<?php echo htmlspecialchars($type['threat_type']); ?>" <?php echo $threatType === $type['threat_type'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($type['threat_type']); ?> (<?php echo $type['count']; ?>)
                                </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <select name="severity" class="px-4 py-2.5 bg-dark-800 border border-white/10 rounded-lg text-white focus:outline-none focus:border-white/30">
                            <option value="0" <?php echo $minSeverity === 0 ? 'selected' : ''; ?>>Todas severidades</option>
                            <option value="50" <?php echo $minSeverity === 50 ? 'selected' : ''; ?>>Severidade ≥ 50</option>
                            <option value="70" <?php echo $minSeverity === 70 ? 'selected' : ''; ?>>Severidade ≥ 70</option>
                            <option value="80" <?php echo $minSeverity === 80 ? 'selected' : ''; ?>>Severidade ≥ 80</option>
                            <option value="90" <?php echo $minSeverity === 90 ? 'selected' : ''; ?>>Severidade ≥ 90</option>
                        </select>
                        <button type="submit" class="px-4 py-2.5 bg-white text-black rounded-lg font-semibold hover:bg-zinc-200 transition-colors">
                            Filtrar
                        </button>
                        <?php if ($searchIP): ?>
                        <input type="hidden" name="ip" value="<?php echo htmlspecialchars($searchIP); ?>">
                        <?php endif; ?>
                    </form>
                </div>
                
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                    <!-- Ameaças Globais -->
                    <div class="lg:col-span-2 bg-dark-800 border border-white/10 rounded-xl p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-xl font-bold text-white">Ameaças Globais</h2>
                            <span class="text-sm text-zinc-400"><?php echo count($globalThreats); ?> ameaça(s)</span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="border-b border-white/10">
                                        <th class="text-left py-3 px-4 text-zinc-400 text-sm font-medium">IP</th>
                                        <th class="text-left py-3 px-4 text-zinc-400 text-sm font-medium">Tipo</th>
                                        <th class="text-left py-3 px-4 text-zinc-400 text-sm font-medium">Severidade</th>
                                        <th class="text-left py-3 px-4 text-zinc-400 text-sm font-medium">Ocorrências</th>
                                        <th class="text-left py-3 px-4 text-zinc-400 text-sm font-medium">Sites</th>
                                        <th class="text-left py-3 px-4 text-zinc-400 text-sm font-medium">Última Visto</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($globalThreats)): ?>
                                    <tr>
                                        <td colspan="6" class="py-8 text-center text-zinc-500">
                                            <i data-lucide="check-circle" class="w-12 h-12 mx-auto mb-3 text-green-400 opacity-50"></i>
                                            <p>Nenhuma ameaça global encontrada</p>
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($globalThreats as $threat): ?>
                                    <tr class="border-b border-white/5 hover:bg-white/5 cursor-pointer" onclick="window.location.href='?ip=<?php echo urlencode($threat['ip_address']); ?>'">
                                        <td class="py-3 px-4 font-mono text-sm text-white"><?php echo htmlspecialchars($threat['ip_address']); ?></td>
                                        <td class="py-3 px-4 text-sm text-zinc-300"><?php echo htmlspecialchars($threat['threat_type']); ?></td>
                                        <td class="py-3 px-4">
                                            <div class="flex items-center gap-2">
                                                <span class="px-2 py-1 rounded text-xs font-medium <?php 
                                                    echo $threat['severity'] >= 80 ? 'bg-red-500/20 text-red-400 border border-red-500/30' : 
                                                        ($threat['severity'] >= 60 ? 'bg-orange-500/20 text-orange-400 border border-orange-500/30' : 'bg-yellow-500/20 text-yellow-400 border border-yellow-500/30'); 
                                                ?>">
                                                    <?php echo $threat['severity']; ?>%
                                                </span>
                                                <div class="w-16 bg-dark-700 rounded-full h-1.5">
                                                    <div class="<?php echo $threat['severity'] >= 80 ? 'bg-red-500' : ($threat['severity'] >= 60 ? 'bg-orange-500' : 'bg-yellow-500'); ?> h-1.5 rounded-full" style="width: <?php echo $threat['severity']; ?>%"></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4 text-sm text-zinc-300"><?php echo number_format($threat['total_occurrences']); ?></td>
                                        <td class="py-3 px-4 text-sm text-zinc-300"><?php echo number_format($threat['affected_sites_count']); ?></td>
                                        <td class="py-3 px-4 text-sm text-zinc-400"><?php echo date('d/m/Y H:i', strtotime($threat['last_seen'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Sidebar com informações adicionais -->
                    <div class="space-y-6">
                        <!-- Ameaças Recentes -->
                        <?php if (!empty($recentThreats)): ?>
                        <div class="bg-dark-800 border border-white/10 rounded-xl p-6">
                            <h3 class="text-lg font-bold text-white mb-4">Ameaças Recentes (24h)</h3>
                            <div class="space-y-3">
                                <?php foreach (array_slice($recentThreats, 0, 5) as $threat): ?>
                                <div class="p-3 rounded-lg bg-dark-700 border border-white/5">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="font-mono text-sm text-white"><?php echo htmlspecialchars($threat['ip_address']); ?></span>
                                        <span class="px-2 py-0.5 text-xs font-bold <?php echo $threat['severity'] >= 80 ? 'bg-red-500/20 text-red-400' : 'bg-yellow-500/20 text-yellow-400'; ?> rounded">
                                            <?php echo $threat['severity']; ?>%
                                        </span>
                                    </div>
                                    <div class="text-xs text-zinc-400"><?php echo htmlspecialchars($threat['threat_type']); ?></div>
                                    <div class="text-xs text-zinc-500 mt-1"><?php echo date('H:i', strtotime($threat['last_seen'])); ?></div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Ameaças por Tipo -->
                        <?php if (!empty($stats['by_type'])): ?>
                        <div class="bg-dark-800 border border-white/10 rounded-xl p-6">
                            <h3 class="text-lg font-bold text-white mb-4">Ameaças por Tipo</h3>
                            <div class="space-y-3">
                                <?php foreach (array_slice($stats['by_type'], 0, 8) as $type): ?>
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <div class="text-sm text-white font-medium"><?php echo htmlspecialchars($type['threat_type']); ?></div>
                                        <div class="text-xs text-zinc-400">Severidade média: <?php echo round($type['avg_severity'], 1); ?>%</div>
                                    </div>
                                    <div class="text-lg font-bold text-zinc-300"><?php echo $type['count']; ?></div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Top Padrões de Ataque -->
                        <?php if (!empty($topPatterns)): ?>
                        <div class="bg-dark-800 border border-white/10 rounded-xl p-6">
                            <h3 class="text-lg font-bold text-white mb-4">Padrões de Ataque</h3>
                            <div class="space-y-2">
                                <?php foreach ($topPatterns as $pattern): ?>
                                <div class="p-2 rounded bg-dark-700 border border-white/5">
                                    <div class="text-sm text-white font-medium"><?php echo htmlspecialchars($pattern['pattern_name'] ?? 'Padrão Desconhecido'); ?></div>
                                    <div class="text-xs text-zinc-400 mt-1">
                                        <?php echo $pattern['detection_count'] ?? 0; ?> detecções
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

