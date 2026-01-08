<?php
/**
 * KRON - Gestão de Comandos
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/KronRBAC.php';
require_once __DIR__ . '/../includes/KronSystemManager.php';
require_once __DIR__ . '/../includes/KronCommandManager.php';

requireAuth();
requirePermission('command.read');

$user = getCurrentUser();
$rbac = new KronRBAC();
$systemManager = new KronSystemManager();
$commandManager = new KronCommandManager();
$pdo = getKronDatabase();

$canCreate = $rbac->hasPermission($user['id'], 'command.create');
$canExecute = $rbac->hasPermission($user['id'], 'command.execute');

// Processar criação de comando
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canCreate && isset($_POST['action']) && $_POST['action'] === 'create') {
    $systemId = $_POST['system_id'] ?? null;
    $type = $_POST['type'] ?? '';
    $parameters = json_decode($_POST['parameters'] ?? '{}', true);
    $priority = $_POST['priority'] ?? 'normal';
    
    if ($systemId && $type) {
        $result = $commandManager->createCommand($systemId, $type, $parameters, $priority, $user['id']);
        if ($result) {
            redirect('dashboard/commands.php?success=created');
        }
    }
}

$systems = $systemManager->listSystems('active');
$commands = $commandManager->getCommandHistory(null, 100);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comandos - KRON</title>
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
        <div class="mb-8 flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold mb-2">Comandos</h1>
                <p class="text-gray-400">Envie e gerencie comandos para sistemas</p>
            </div>
            <?php if ($canCreate): ?>
                <button onclick="document.getElementById('createModal').classList.remove('hidden')"
                    class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg font-medium">
                    + Novo Comando
                </button>
            <?php endif; ?>
        </div>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="bg-green-500/10 border border-green-500/50 text-green-400 px-4 py-3 rounded-lg mb-6">
                Comando criado com sucesso!
            </div>
        <?php endif; ?>
        
        <div class="bg-gray-900 rounded-xl border border-gray-800 overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-800">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-semibold">ID</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold">Sistema</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold">Tipo</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold">Prioridade</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold">Status</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold">Criado</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800">
                    <?php if (empty($commands)): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-400">
                                Nenhum comando encontrado
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($commands as $cmd): ?>
                            <tr class="hover:bg-gray-800/50">
                                <td class="px-6 py-4 text-sm font-mono text-gray-400">
                                    <?= htmlspecialchars(substr($cmd['command_id'], 0, 16)) ?>...
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-medium"><?= htmlspecialchars($cmd['system_display_name'] ?? $cmd['system_name'] ?? 'N/A') ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs rounded bg-gray-700 text-gray-300">
                                        <?= htmlspecialchars($cmd['type']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs rounded <?php
                                        echo match($cmd['priority']) {
                                            'critical' => 'bg-red-500/10 text-red-400',
                                            'high' => 'bg-orange-500/10 text-orange-400',
                                            'normal' => 'bg-blue-500/10 text-blue-400',
                                            'low' => 'bg-gray-500/10 text-gray-400',
                                            default => 'bg-gray-500/10 text-gray-400'
                                        };
                                    ?>">
                                        <?= ucfirst($cmd['priority']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs rounded <?php
                                        echo match($cmd['status']) {
                                            'completed' => 'bg-green-500/10 text-green-400',
                                            'failed' => 'bg-red-500/10 text-red-400',
                                            'executing' => 'bg-yellow-500/10 text-yellow-400',
                                            'pending', 'queued' => 'bg-blue-500/10 text-blue-400',
                                            default => 'bg-gray-500/10 text-gray-400'
                                        };
                                    ?>">
                                        <?= ucfirst($cmd['status']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-400">
                                    <?= date('d/m/Y H:i', strtotime($cmd['created_at'])) ?>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button onclick="viewCommand('<?= htmlspecialchars($cmd['command_id']) ?>')"
                                        class="text-blue-400 hover:text-blue-300 text-sm">Ver</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Create Modal -->
    <?php if ($canCreate): ?>
        <div id="createModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-gray-900 rounded-xl border border-gray-800 p-6 w-full max-w-md">
                <h2 class="text-2xl font-bold mb-4">Novo Comando</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="create">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium mb-2">Sistema</label>
                            <select name="system_id" required
                                class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 focus:outline-none focus:border-blue-500">
                                <option value="">Selecione um sistema</option>
                                <?php foreach ($systems as $sys): ?>
                                    <option value="<?= $sys['id'] ?>"><?= htmlspecialchars($sys['display_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">Tipo</label>
                            <input type="text" name="type" required
                                class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 focus:outline-none focus:border-blue-500"
                                placeholder="sync_data">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">Parâmetros (JSON)</label>
                            <textarea name="parameters"
                                class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 focus:outline-none focus:border-blue-500 font-mono text-sm"
                                rows="4">{"table": "users"}</textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">Prioridade</label>
                            <select name="priority"
                                class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 focus:outline-none focus:border-blue-500">
                                <option value="low">Baixa</option>
                                <option value="normal" selected>Normal</option>
                                <option value="high">Alta</option>
                                <option value="critical">Crítica</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex gap-3 mt-6">
                        <button type="submit" class="flex-1 bg-blue-500 hover:bg-blue-600 text-white py-2 rounded-lg font-medium">
                            Criar
                        </button>
                        <button type="button" onclick="document.getElementById('createModal').classList.add('hidden')"
                            class="flex-1 bg-gray-800 hover:bg-gray-700 text-white py-2 rounded-lg font-medium">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
    
    <script>
        function viewCommand(id) {
            window.location.href = '?action=view&id=' + id;
        }
    </script>
</body>
</html>

