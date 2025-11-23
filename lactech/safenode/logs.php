<?php
/**
 * SafeNode - Logs de Segurança
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

$db = getSafeNodeDatabase();

// Contexto do Site
$currentSiteId = $_SESSION['view_site_id'] ?? 0;
$siteFilterWhere = $currentSiteId > 0 ? " site_id = $currentSiteId " : " 1=1 ";

// Filtros
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 50;
$offset = ($page - 1) * $perPage;

$filters = [
    'ip' => $_GET['ip'] ?? '',
    'threat_type' => $_GET['threat_type'] ?? '',
    'action' => $_GET['action'] ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? ''
];

// Construir query
$where = [$siteFilterWhere]; // Adiciona filtro de site base
$params = [];

if (!empty($filters['ip'])) {
    $where[] = "ip_address LIKE ?";
    $params[] = '%' . $filters['ip'] . '%';
}

if (!empty($filters['threat_type'])) {
    $where[] = "threat_type = ?";
    $params[] = $filters['threat_type'];
}

if (!empty($filters['action'])) {
    $where[] = "action_taken = ?";
    $params[] = $filters['action'];
}

if (!empty($filters['date_from'])) {
    $where[] = "DATE(created_at) >= ?";
    $params[] = $filters['date_from'];
}

if (!empty($filters['date_to'])) {
    $where[] = "DATE(created_at) <= ?";
    $params[] = $filters['date_to'];
}

$whereClause = 'WHERE ' . implode(' AND ', $where);

// Exportação CSV
if ($db && isset($_GET['export']) && $_GET['export'] === 'csv') {
    try {
        $sql = "SELECT created_at, ip_address, request_uri, request_method, threat_type, threat_score, action_taken, user_agent 
                FROM safenode_security_logs $whereClause 
                ORDER BY created_at DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="safenode_logs_' . date('Ymd_His') . '.csv"');

        $output = fopen('php://output', 'w');
        if (!empty($rows)) {
            fputcsv($output, array_keys($rows[0]));
            foreach ($rows as $row) {
                fputcsv($output, $row);
            }
        } else {
            fputcsv($output, ['Nenhum registro para os filtros atuais']);
        }
        fclose($output);
        exit;
    } catch (PDOException $e) {
        error_log("SafeNode Logs CSV Export Error: " . $e->getMessage());
    }
}

// Total de registros
$totalLogs = 0;
$logs = [];

if ($db) {
    try {
        $countSql = "SELECT COUNT(*) as total FROM safenode_security_logs $whereClause";
        $countStmt = $db->prepare($countSql);
        $countStmt->execute($params);
        $totalLogs = $countStmt->fetch()['total'];
        
        $sql = "SELECT * FROM safenode_security_logs $whereClause ORDER BY created_at DESC LIMIT $perPage OFFSET $offset";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $logs = $stmt->fetchAll();
        
        $totalPages = ceil($totalLogs / $perPage);
    } catch (PDOException $e) {
        error_log("SafeNode Logs Error: " . $e->getMessage());
    }
}

// Tipos de ameaça para filtro
$threatTypes = ['sql_injection', 'xss', 'brute_force', 'rate_limit', 'suspicious_pattern', 'path_traversal', 'command_injection'];
$actions = ['blocked', 'allowed', 'rate_limited', 'logged'];
?>
<!DOCTYPE html>
<html lang="pt-BR" class="dark h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs | SafeNode</title>
    <link rel="icon" type="image/png" href="assets/img/logos (6).png">
    <link rel="shortcut icon" type="image/png" href="assets/img/logos (6).png">
    <link rel="apple-touch-icon" href="assets/img/logos (6).png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <script>
        tailwind.config = { darkMode: 'class', theme: { extend: { fontFamily: { sans: ['Inter', 'sans-serif'], mono: ['JetBrains Mono', 'monospace'] } } } }
    </script>
    <style>
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #3f3f46; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #52525b; }
        .glass-card { background: linear-gradient(180deg, rgba(39, 39, 42, 0.4) 0%, rgba(24, 24, 27, 0.4) 100%); backdrop-filter: blur(8px); border: 1px solid rgba(255, 255, 255, 0.05); }
    </style>
</head>
<body class="bg-black text-zinc-200 font-sans h-full overflow-hidden flex">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="flex-1 flex flex-col h-full relative overflow-hidden bg-black">
        <header class="h-16 border-b border-white/5 bg-black/50 backdrop-blur-xl sticky top-0 z-40 px-6 flex items-center justify-between">
            <div class="flex items-center gap-4 md:hidden">
                <button class="text-zinc-400 hover:text-white" data-sidebar-toggle>
                    <i data-lucide="menu" class="w-6 h-6"></i>
                </button>
                <span class="font-bold text-lg text-white">SafeNode</span>
            </div>
            <div class="hidden md:block">
                <h2 class="text-xl font-bold text-white tracking-tight">Logs de Segurança</h2>
                <p class="text-xs text-zinc-400 mt-0.5">
                    <?php echo $currentSiteId > 0 ? htmlspecialchars($_SESSION['view_site_name']) . ' • ' : 'Visão Global • '; ?>
                    <?php echo number_format($totalLogs); ?> registros encontrados
                </p>
            </div>
        </header>
        <div class="flex-1 overflow-y-auto p-6 md:p-8 z-10">
            <div class="max-w-7xl mx-auto space-y-6">
            <div class="glass-card rounded-xl p-6 mb-6">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-lg font-bold text-white tracking-tight">Filtros</h3>
                        <p class="text-xs text-zinc-500 mt-1">Refine os eventos e exporte para análise externa</p>
                    </div>
                    <div class="flex gap-2">
                        <a href="?<?php echo http_build_query(array_merge($filters, ['export' => 'csv'])); ?>" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-zinc-900 text-zinc-300 border border-white/10 hover:bg-zinc-800 hover:text-white text-xs font-semibold transition-all">
                            <i data-lucide="download" class="w-3 h-3"></i>
                            Exportar CSV
                        </a>
                    </div>
                </div>
                <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-zinc-300 mb-2">IP Address</label>
                        <input type="text" name="ip" value="<?php echo htmlspecialchars($filters['ip']); ?>" placeholder="192.168.1.1" class="w-full px-4 py-2.5 border border-white/10 rounded-xl bg-zinc-900/50 text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-zinc-300 mb-2">Tipo de Ameaça</label>
                        <select name="threat_type" class="w-full px-4 py-2.5 border border-white/10 rounded-xl bg-zinc-900/50 text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                            <option value="">Todos</option>
                            <?php foreach ($threatTypes as $type): ?>
                                <option value="<?php echo $type; ?>" <?php echo $filters['threat_type'] === $type ? 'selected' : ''; ?>>
                                    <?php echo ucfirst(str_replace('_', ' ', $type)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-zinc-300 mb-2">Ação</label>
                        <select name="action" class="w-full px-4 py-2.5 border border-white/10 rounded-xl bg-zinc-900/50 text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                            <option value="">Todas</option>
                            <?php foreach ($actions as $action): ?>
                                <option value="<?php echo $action; ?>" <?php echo $filters['action'] === $action ? 'selected' : ''; ?>>
                                    <?php echo ucfirst($action); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-zinc-300 mb-2">Data Inicial</label>
                        <input type="date" name="date_from" value="<?php echo htmlspecialchars($filters['date_from']); ?>" class="w-full px-4 py-2.5 border border-white/10 rounded-xl bg-zinc-900/50 text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-zinc-300 mb-2">Data Final</label>
                        <input type="date" name="date_to" value="<?php echo htmlspecialchars($filters['date_to']); ?>" class="w-full px-4 py-2.5 border border-white/10 rounded-xl bg-zinc-900/50 text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                    </div>
                    <div class="md:col-span-5 flex gap-3">
                        <button type="submit" class="px-6 py-2.5 bg-blue-600 text-white rounded-xl hover:bg-blue-700 font-semibold transition-all shadow-lg shadow-blue-500/20">Filtrar</button>
                        <a href="logs.php" class="px-6 py-2.5 bg-zinc-800 text-zinc-300 rounded-xl hover:bg-zinc-700 font-semibold transition-all">Limpar</a>
                    </div>
                </form>
            </div>

            <div class="glass-card rounded-xl overflow-hidden">
                <div class="p-6 border-b border-white/5">
                    <h3 class="font-bold text-white text-lg">Registros de Segurança</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-zinc-900/50 text-zinc-400 font-medium uppercase text-xs tracking-wider">
                            <tr>
                                <th class="px-6 py-4">Data/Hora</th>
                                <th class="px-6 py-4">IP</th>
                                <th class="px-6 py-4">Endpoint</th>
                                <th class="px-6 py-4">Método</th>
                                <th class="px-6 py-4">Ameaça</th>
                                <th class="px-6 py-4">Score</th>
                                <th class="px-6 py-4">Ação</th>
                                <th class="px-6 py-4">Detalhes</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="8">
                                        <div class="p-12 text-center">
                                            <div class="w-16 h-16 bg-zinc-900 rounded-xl flex items-center justify-center mx-auto mb-4">
                                                <i data-lucide="file-text" class="w-8 h-8 text-zinc-500"></i>
                                            </div>
                                            <p class="text-sm text-zinc-400 font-medium">Nenhum log encontrado</p>
                                            <p class="text-xs text-zinc-500 mt-1">Ajuste os filtros para ver mais resultados</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                    <tr class="hover:bg-white/[0.02] transition-colors">
                                        <td class="px-6 py-4">
                                            <span class="text-sm text-zinc-300 font-mono"><?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?></span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="text-sm font-semibold text-white font-mono"><?php echo htmlspecialchars($log['ip_address']); ?></span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="max-w-xs truncate" title="<?php echo htmlspecialchars($log['request_uri']); ?>">
                                                <span class="text-sm text-zinc-400 font-mono text-xs"><?php echo htmlspecialchars($log['request_uri']); ?></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-zinc-800 text-zinc-300 border border-white/5"><?php echo htmlspecialchars($log['request_method']); ?></span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium <?php 
                                                echo $log['threat_type'] === 'sql_injection' ? 'bg-red-500/10 text-red-400 border border-red-500/20' : 
                                                    ($log['threat_type'] === 'xss' ? 'bg-orange-500/10 text-orange-400 border border-orange-500/20' : 
                                                    ($log['threat_type'] === 'brute_force' ? 'bg-amber-500/10 text-amber-400 border border-amber-500/20' : 'bg-zinc-800 text-zinc-400 border border-white/5'));
                                            ?>">
                                                <?php echo strtoupper(str_replace('_', ' ', htmlspecialchars($log['threat_type']))); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-2">
                                                <div class="w-16 h-2 bg-zinc-800 rounded-full overflow-hidden">
                                                    <div class="h-full <?php 
                                                        echo $log['threat_score'] >= 70 ? 'bg-red-500' : 
                                                            ($log['threat_score'] >= 40 ? 'bg-amber-500' : 'bg-emerald-500');
                                                    ?>" style="width: <?php echo min(100, $log['threat_score']); ?>%"></div>
                                                </div>
                                                <span class="text-xs text-zinc-400 font-semibold"><?php echo $log['threat_score']; ?></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium <?php 
                                                echo $log['action_taken'] === 'blocked' ? 'bg-red-500/10 text-red-400 border border-red-500/20' : 
                                                    ($log['action_taken'] === 'allowed' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-amber-500/10 text-amber-400 border border-amber-500/20');
                                            ?>">
                                                <?php if ($log['action_taken'] === 'blocked'): ?>
                                                    <i data-lucide="ban" class="w-3 h-3"></i>
                                                <?php else: ?>
                                                    <i data-lucide="check-circle" class="w-3 h-3"></i>
                                                <?php endif; ?>
                                                <?php echo strtoupper(htmlspecialchars($log['action_taken'])); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php if (!empty($log['threat_details'])): ?>
                                                <button onclick="showDetails('<?php echo htmlspecialchars(addslashes($log['threat_details'])); ?>')" class="text-blue-400 hover:text-blue-300 text-sm font-semibold transition-colors">
                                                    Ver
                                                </button>
                                            <?php else: ?>
                                                <span class="text-zinc-500 text-sm">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalPages > 1): ?>
                    <div class="p-6 border-t border-white/5 flex items-center justify-between">
                        <p class="text-sm text-zinc-400 font-medium">
                            Página <?php echo $page; ?> de <?php echo $totalPages; ?>
                        </p>
                        <div class="flex gap-2">
                            <?php if ($page > 1): ?>
                                <a href="?<?php echo http_build_query(array_merge($filters, ['page' => $page - 1])); ?>" class="px-4 py-2 bg-zinc-800 text-zinc-300 rounded-xl hover:bg-zinc-700 font-semibold transition-all">Anterior</a>
                            <?php endif; ?>
                            <?php if ($page < $totalPages): ?>
                                <a href="?<?php echo http_build_query(array_merge($filters, ['page' => $page + 1])); ?>" class="px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 font-semibold transition-all">Próxima</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            </div>
        </div>
    </main>

    <div id="detailsModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 backdrop-blur-sm">
        <div class="glass-card rounded-2xl p-6 max-w-2xl w-full mx-4 border border-white/10">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-white">Detalhes da Ameaça</h3>
                <button onclick="closeDetails()" class="text-zinc-400 hover:text-white transition-colors">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>
            <div id="detailsContent" class="text-sm text-zinc-300 whitespace-pre-wrap bg-zinc-900/50 p-4 rounded-xl max-h-96 overflow-y-auto font-mono border border-white/5"></div>
        </div>
    </div>

    <script>
        lucide.createIcons();
        function showDetails(details) {
            document.getElementById('detailsContent').textContent = details;
            document.getElementById('detailsModal').classList.remove('hidden');
            document.getElementById('detailsModal').classList.add('flex');
        }
        function closeDetails() {
            document.getElementById('detailsModal').classList.add('hidden');
            document.getElementById('detailsModal').classList.remove('flex');
        }
    </script>
</body>
</html>
