<?php
/**
 * KRON - Central de Logs
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/KronRBAC.php';
require_once __DIR__ . '/../includes/KronSystemManager.php';

requireAuth();
requirePermission('audit.read');

$user = getCurrentUser();
$systemManager = new KronSystemManager();
$pdo = getKronDatabase();

$systems = $systemManager->listSystems('active');
$selectedSystem = $_GET['system'] ?? null;
$selectedLevel = $_GET['level'] ?? null;
$page = (int)($_GET['page'] ?? 1);
$perPage = 50;
$offset = ($page - 1) * $perPage;

// Buscar logs do sistema
$systemLogs = [];
$totalSystemLogs = 0;

if ($pdo) {
    $sql = "SELECT COUNT(*) as total FROM kron_system_logs WHERE 1=1";
    $params = [];
    
    if ($selectedSystem) {
        $sql .= " AND system_id = ?";
        $params[] = $selectedSystem;
    }
    
    if ($selectedLevel) {
        $sql .= " AND level = ?";
        $params[] = $selectedLevel;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $totalSystemLogs = $stmt->fetch()['total'];
    
    $sql = "
        SELECT l.*, s.name as system_name, s.display_name as system_display_name
        FROM kron_system_logs l
        INNER JOIN kron_systems s ON l.system_id = s.id
        WHERE 1=1
    ";
    
    if ($selectedSystem) {
        $sql .= " AND l.system_id = ?";
    }
    
    if ($selectedLevel) {
        $sql .= " AND l.level = ?";
    }
    
    $sql .= " ORDER BY l.received_at DESC LIMIT ? OFFSET ?";
    
    $params[] = $perPage;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $systemLogs = $stmt->fetchAll();
}

// Buscar logs de auditoria
$auditLogs = [];
$totalAuditLogs = 0;

if ($pdo) {
    $sql = "SELECT COUNT(*) as total FROM kron_audit_logs";
    $stmt = $pdo->query($sql);
    $totalAuditLogs = $stmt->fetch()['total'];
    
    $stmt = $pdo->prepare("
        SELECT a.*, u.name as user_name, u.email as user_email
        FROM kron_audit_logs a
        LEFT JOIN kron_users u ON a.user_id = u.id
        ORDER BY a.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$perPage, $offset]);
    $auditLogs = $stmt->fetchAll();
}

$totalPages = ceil(max($totalSystemLogs, $totalAuditLogs) / $perPage);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs - KRON</title>
    <link rel="icon" type="image/png" href="../asset/kron.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #0a0a0a; color: #f5f5f7; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/_sidebar.php'; ?>
    
    <div class="ml-64 p-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold mb-2">Central de Logs</h1>
            <p class="text-gray-400">Visualize logs dos sistemas e auditoria</p>
        </div>
        
        <!-- Filtros -->
        <div class="bg-gray-900 rounded-xl border border-gray-800 p-6 mb-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-2">Sistema</label>
                    <select name="system"
                        class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 focus:outline-none focus:border-blue-500">
                        <option value="">Todos</option>
                        <?php foreach ($systems as $sys): ?>
                            <option value="<?= $sys['id'] ?>" <?= $selectedSystem == $sys['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($sys['display_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">Nível</label>
                    <select name="level"
                        class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 focus:outline-none focus:border-blue-500">
                        <option value="">Todos</option>
                        <option value="debug" <?= $selectedLevel === 'debug' ? 'selected' : '' ?>>Debug</option>
                        <option value="info" <?= $selectedLevel === 'info' ? 'selected' : '' ?>>Info</option>
                        <option value="warning" <?= $selectedLevel === 'warning' ? 'selected' : '' ?>>Warning</option>
                        <option value="error" <?= $selectedLevel === 'error' ? 'selected' : '' ?>>Error</option>
                        <option value="critical" <?= $selectedLevel === 'critical' ? 'selected' : '' ?>>Critical</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full bg-blue-500 hover:bg-blue-600 text-white py-2 rounded-lg font-medium">
                        Filtrar
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Tabs -->
        <div class="mb-6">
            <div class="flex gap-2 border-b border-gray-800">
                <button onclick="showTab('system')" id="tab-system"
                    class="px-4 py-2 border-b-2 border-blue-500 text-blue-400 font-medium">
                    Logs do Sistema (<?= $totalSystemLogs ?>)
                </button>
                <button onclick="showTab('audit')" id="tab-audit"
                    class="px-4 py-2 border-b-2 border-transparent text-gray-400 hover:text-white">
                    Auditoria (<?= $totalAuditLogs ?>)
                </button>
            </div>
        </div>
        
        <!-- System Logs -->
        <div id="system-logs" class="tab-content">
            <div class="bg-gray-900 rounded-xl border border-gray-800 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-800">
                            <tr>
                                <th class="px-6 py-4 text-left text-sm font-semibold">Data/Hora</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold">Sistema</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold">Nível</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold">Mensagem</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-800">
                            <?php if (empty($systemLogs)): ?>
                                <tr>
                                    <td colspan="4" class="px-6 py-12 text-center text-gray-400">
                                        Nenhum log encontrado
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($systemLogs as $log): ?>
                                    <tr class="hover:bg-gray-800/50">
                                        <td class="px-6 py-4 text-sm text-gray-400">
                                            <?= date('d/m/Y H:i:s', strtotime($log['received_at'])) ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="font-medium"><?= htmlspecialchars($log['system_display_name']) ?></div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="px-2 py-1 text-xs rounded <?php
                                                echo match($log['level']) {
                                                    'error', 'critical' => 'bg-red-500/10 text-red-400',
                                                    'warning' => 'bg-yellow-500/10 text-yellow-400',
                                                    'info' => 'bg-blue-500/10 text-blue-400',
                                                    default => 'bg-gray-500/10 text-gray-400'
                                                };
                                            ?>">
                                                <?= strtoupper($log['level']) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="font-medium"><?= htmlspecialchars($log['message']) ?></div>
                                            <?php if ($log['context']): ?>
                                                <div class="text-xs text-gray-400 mt-1 font-mono">
                                                    <?= htmlspecialchars(substr($log['context'], 0, 100)) ?>...
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Audit Logs -->
        <div id="audit-logs" class="tab-content hidden">
            <div class="bg-gray-900 rounded-xl border border-gray-800 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-800">
                            <tr>
                                <th class="px-6 py-4 text-left text-sm font-semibold">Data/Hora</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold">Usuário</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold">Ação</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold">Entidade</th>
                                <th class="px-6 py-4 text-left text-sm font-semibold">IP</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-800">
                            <?php if (empty($auditLogs)): ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-gray-400">
                                        Nenhum log de auditoria encontrado
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($auditLogs as $log): ?>
                                    <tr class="hover:bg-gray-800/50">
                                        <td class="px-6 py-4 text-sm text-gray-400">
                                            <?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php if ($log['user_name']): ?>
                                                <div class="font-medium"><?= htmlspecialchars($log['user_name']) ?></div>
                                                <div class="text-xs text-gray-400"><?= htmlspecialchars($log['user_email']) ?></div>
                                            <?php else: ?>
                                                <span class="text-gray-500">Sistema</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="px-2 py-1 text-xs rounded bg-blue-500/10 text-blue-400">
                                                <?= htmlspecialchars($log['action']) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm">
                                            <?= htmlspecialchars($log['entity_type'] ?? 'N/A') ?>
                                            <?php if ($log['entity_id']): ?>
                                                #<?= $log['entity_id'] ?>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-400 font-mono">
                                            <?= htmlspecialchars($log['ip_address'] ?? 'N/A') ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="mt-6 flex items-center justify-center gap-2">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?><?= $selectedSystem ? '&system=' . $selectedSystem : '' ?><?= $selectedLevel ? '&level=' . $selectedLevel : '' ?>"
                        class="px-4 py-2 bg-gray-800 hover:bg-gray-700 rounded-lg">Anterior</a>
                <?php endif; ?>
                
                <span class="px-4 py-2 text-gray-400">
                    Página <?= $page ?> de <?= $totalPages ?>
                </span>
                
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?><?= $selectedSystem ? '&system=' . $selectedSystem : '' ?><?= $selectedLevel ? '&level=' . $selectedLevel : '' ?>"
                        class="px-4 py-2 bg-gray-800 hover:bg-gray-700 rounded-lg">Próxima</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        function showTab(tab) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
            document.querySelectorAll('[id^="tab-"]').forEach(el => {
                el.classList.remove('border-blue-500', 'text-blue-400');
                el.classList.add('border-transparent', 'text-gray-400');
            });
            
            document.getElementById(tab + '-logs').classList.remove('hidden');
            document.getElementById('tab-' + tab).classList.add('border-blue-500', 'text-blue-400');
            document.getElementById('tab-' + tab).classList.remove('border-transparent', 'text-gray-400');
        }
    </script>
</body>
</html>

