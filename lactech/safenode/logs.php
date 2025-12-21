<?php
/**
 * SafeNode - Logs de Segurança (Explorar)
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

$pageTitle = 'Explorar Logs';
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

// Filtros
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 50;
$offset = ($page - 1) * $limit;

$threatType = $_GET['threat_type'] ?? '';
$actionTaken = $_GET['action'] ?? '';
$ipAddress = $_GET['ip'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

// Construir query
$where = [];
$params = [];

if ($currentSiteId > 0) {
    $where[] = "site_id = ?";
    $params[] = $currentSiteId;
}

if ($threatType) {
    $where[] = "threat_type = ?";
    $params[] = $threatType;
}

if ($actionTaken) {
    $where[] = "action_taken = ?";
    $params[] = $actionTaken;
}

if ($ipAddress) {
    $where[] = "ip_address LIKE ?";
    $params[] = "%$ipAddress%";
}

if ($dateFrom) {
    $where[] = "DATE(created_at) >= ?";
    $params[] = $dateFrom;
}

if ($dateTo) {
    $where[] = "DATE(created_at) <= ?";
    $params[] = $dateTo;
}

$whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Contar total
$totalLogs = 0;
if ($db) {
    try {
        $countStmt = $db->prepare("SELECT COUNT(*) as total FROM safenode_security_logs $whereClause");
        $countStmt->execute($params);
        $totalLogs = (int)$countStmt->fetch()['total'];
    } catch (PDOException $e) {
        error_log("Erro ao contar logs: " . $e->getMessage());
    }
}

$totalPages = ceil($totalLogs / $limit);

// Buscar logs
$logs = [];
if ($db) {
    try {
        $sql = "SELECT * FROM safenode_security_logs $whereClause ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $stmt = $db->prepare($sql);
        $params[] = $limit;
        $params[] = $offset;
        $stmt->execute($params);
        $logs = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Erro ao buscar logs: " . $e->getMessage());
    }
}

// Tipos de ameaça
$threatTypes = [
    'sql_injection' => 'SQL Injection',
    'xss' => 'XSS',
    'brute_force' => 'Brute Force',
    'rate_limit' => 'Rate Limit',
    'path_traversal' => 'Path Traversal',
    'command_injection' => 'Command Injection',
    'ddos' => 'DDoS',
    'suspicious_activity' => 'Atividade Suspeita'
];

// Ações
$actions = [
    'blocked' => 'Bloqueado',
    'allowed' => 'Permitido',
    'challenged' => 'Desafiado',
    'logged' => 'Registrado'
];

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
                    <button data-sidebar-toggle class="lg:hidden text-zinc-400 hover:text-white transition-colors">
                        <i data-lucide="menu" class="w-6 h-6"></i>
                    </button>
                    <div>
                        <h2 class="text-2xl font-bold text-white tracking-tight">Explorar Logs</h2>
                        <?php if ($selectedSite): ?>
                            <p class="text-sm text-zinc-500 font-mono mt-0.5"><?php echo htmlspecialchars($selectedSite['domain'] ?? ''); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <div class="flex-1 overflow-y-auto p-8">
                <!-- Filtros -->
                <div class="glass rounded-2xl p-6 mb-6">
                    <h3 class="text-lg font-semibold text-white mb-4">Filtros</h3>
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4">
                        <div>
                            <label class="block text-sm text-zinc-400 mb-2">Tipo de Ameaça</label>
                            <select name="threat_type" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-white/30">
                                <option value="">Todos</option>
                                <?php foreach ($threatTypes as $key => $label): ?>
                                    <option value="<?php echo $key; ?>" <?php echo $threatType === $key ? 'selected' : ''; ?>><?php echo $label; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm text-zinc-400 mb-2">Status/Ação</label>
                            <select name="action" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-white/30">
                                <option value="">Todos os Status</option>
                                <?php foreach ($actions as $key => $label): ?>
                                    <option value="<?php echo $key; ?>" <?php echo $actionTaken === $key ? 'selected' : ''; ?>><?php echo $label; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm text-zinc-400 mb-2">Buscar por IP</label>
                            <div class="relative">
                                <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-500"></i>
                                <input type="text" name="ip" value="<?php echo htmlspecialchars($ipAddress); ?>" placeholder="Ex: 192.168.1.1" class="w-full bg-white/5 border border-white/10 rounded-xl pl-10 pr-4 py-2.5 text-sm text-white placeholder-zinc-500 focus:outline-none focus:border-white/30">
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm text-zinc-400 mb-2">Data Inicial</label>
                            <input type="date" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-white/30">
                        </div>
                        
                        <div>
                            <label class="block text-sm text-zinc-400 mb-2">Data Final</label>
                            <input type="date" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-white/30">
                        </div>
                        
                        <div class="md:col-span-3 lg:col-span-5 flex gap-2">
                            <button type="submit" class="px-6 py-2.5 bg-white text-black rounded-xl font-semibold hover:bg-white/90 transition-colors">
                                Aplicar Filtros
                            </button>
                            <a href="logs.php" class="px-6 py-2.5 bg-white/10 text-white rounded-xl font-semibold hover:bg-white/20 transition-colors">
                                Limpar
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Estatísticas -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <div class="glass rounded-xl p-4">
                        <p class="text-sm text-zinc-400 mb-1">Total de Logs</p>
                        <p class="text-2xl font-bold text-white"><?php echo number_format($totalLogs); ?></p>
                    </div>
                    <div class="glass rounded-xl p-4">
                        <p class="text-sm text-zinc-400 mb-1">Página Atual</p>
                        <p class="text-2xl font-bold text-white"><?php echo $page; ?> / <?php echo $totalPages; ?></p>
                    </div>
                    <div class="glass rounded-xl p-4">
                        <p class="text-sm text-zinc-400 mb-1">Logs por Página</p>
                        <p class="text-2xl font-bold text-white"><?php echo $limit; ?></p>
                    </div>
                    <div class="glass rounded-xl p-4">
                        <p class="text-sm text-zinc-400 mb-1">Filtros Ativos</p>
                        <p class="text-2xl font-bold text-white"><?php echo count(array_filter([$threatType, $actionTaken, $ipAddress, $dateFrom, $dateTo])); ?></p>
                    </div>
                </div>

                <!-- Tabela de Logs -->
                <div class="glass rounded-2xl p-6">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-white/10">
                                    <th class="text-left py-3 px-4 text-sm font-semibold text-zinc-400">Data/Hora</th>
                                    <th class="text-left py-3 px-4 text-sm font-semibold text-zinc-400">IP Address</th>
                                    <th class="text-left py-3 px-4 text-sm font-semibold text-zinc-400">Tipo de Ameaça</th>
                                    <th class="text-left py-3 px-4 text-sm font-semibold text-zinc-400">Ação</th>
                                    <th class="text-left py-3 px-4 text-sm font-semibold text-zinc-400">Score</th>
                                    <th class="text-left py-3 px-4 text-sm font-semibold text-zinc-400">URI</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($logs)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-12 text-zinc-500">
                                            Nenhum log encontrado
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($logs as $log): ?>
                                        <tr class="border-b border-white/5 hover:bg-white/5 transition-colors">
                                            <td class="py-3 px-4 text-sm text-white font-mono">
                                                <?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?>
                                            </td>
                                            <td class="py-3 px-4 text-sm text-white font-mono">
                                                <?php echo htmlspecialchars($log['ip_address']); ?>
                                            </td>
                                            <td class="py-3 px-4">
                                                <?php if ($log['threat_type']): ?>
                                                    <span class="px-2 py-1 bg-red-500/20 text-red-400 text-xs rounded-lg">
                                                        <?php echo htmlspecialchars($threatTypes[$log['threat_type']] ?? $log['threat_type']); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-zinc-500 text-xs">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-3 px-4">
                                                <?php
                                                $actionColors = [
                                                    'blocked' => 'bg-red-500/20 text-red-400',
                                                    'allowed' => 'bg-green-500/20 text-green-400',
                                                    'challenged' => 'bg-yellow-500/20 text-yellow-400',
                                                    'logged' => 'bg-blue-500/20 text-blue-400'
                                                ];
                                                $actionColor = $actionColors[$log['action_taken']] ?? 'bg-zinc-500/20 text-zinc-400';
                                                ?>
                                                <span class="px-2 py-1 <?php echo $actionColor; ?> text-xs rounded-lg">
                                                    <?php echo htmlspecialchars($actions[$log['action_taken']] ?? $log['action_taken']); ?>
                                                </span>
                                            </td>
                                            <td class="py-3 px-4 text-sm text-white">
                                                <?php echo $log['threat_score'] ?? 0; ?>
                                            </td>
                                            <td class="py-3 px-4 text-sm text-zinc-400 font-mono truncate max-w-xs" title="<?php echo htmlspecialchars($log['request_uri']); ?>">
                                                <?php echo htmlspecialchars(substr($log['request_uri'], 0, 50)); ?>
                                                <?php if (strlen($log['request_uri']) > 50): ?>...<?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginação -->
                    <?php if ($totalPages > 1): ?>
                    <div class="mt-6 flex items-center justify-between">
                        <div class="text-sm text-zinc-400">
                            Mostrando <?php echo $offset + 1; ?> - <?php echo min($offset + $limit, $totalLogs); ?> de <?php echo number_format($totalLogs); ?> logs
                        </div>
                        <div class="flex gap-2">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>&threat_type=<?php echo urlencode($threatType); ?>&action=<?php echo urlencode($actionTaken); ?>&ip=<?php echo urlencode($ipAddress); ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>" class="px-4 py-2 bg-white/10 text-white rounded-lg hover:bg-white/20 transition-colors">
                                    Anterior
                                </a>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <a href="?page=<?php echo $i; ?>&threat_type=<?php echo urlencode($threatType); ?>&action=<?php echo urlencode($actionTaken); ?>&ip=<?php echo urlencode($ipAddress); ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>" class="px-4 py-2 <?php echo $i === $page ? 'bg-white text-black' : 'bg-white/10 text-white hover:bg-white/20'; ?> rounded-lg transition-colors">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?php echo $page + 1; ?>&threat_type=<?php echo urlencode($threatType); ?>&action=<?php echo urlencode($actionTaken); ?>&ip=<?php echo urlencode($ipAddress); ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>" class="px-4 py-2 bg-white/10 text-white rounded-lg hover:bg-white/20 transition-colors">
                                    Próxima
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        lucide.createIcons();
    </script>
    
    <!-- Security Scripts - Previne download de código -->
    <script src="includes/security-scripts.js"></script>
</body>
</html>

