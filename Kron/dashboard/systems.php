<?php
/**
 * KRON - Gestão de Sistemas
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/KronRBAC.php';
require_once __DIR__ . '/../includes/KronSystemManager.php';

requireAuth();
requirePermission('system.read');

$user = getCurrentUser();
$rbac = new KronRBAC();
$systemManager = new KronSystemManager();
$pdo = getKronDatabase();

$isCEO = $rbac->isCEO($user['id']);
$canCreate = $rbac->hasPermission($user['id'], 'system.create');
$canUpdate = $rbac->hasPermission($user['id'], 'system.update');
$canDelete = $rbac->hasPermission($user['id'], 'system.delete');

// Processar ações
$action = $_GET['action'] ?? '';
$systemId = $_GET['id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create' && $canCreate) {
        $name = $_POST['name'] ?? '';
        $displayName = $_POST['display_name'] ?? '';
        $description = $_POST['description'] ?? '';
        $apiUrl = $_POST['api_url'] ?? '';
        
        if ($name && $displayName) {
            $id = $systemManager->createSystem($name, $displayName, $description, $apiUrl);
            if ($id) {
                redirect('dashboard/systems.php?success=created');
            }
        }
    } elseif ($action === 'update' && $canUpdate && $systemId) {
        $data = [];
        if (isset($_POST['display_name'])) $data['display_name'] = $_POST['display_name'];
        if (isset($_POST['description'])) $data['description'] = $_POST['description'];
        if (isset($_POST['api_url'])) $data['api_url'] = $_POST['api_url'];
        if (isset($_POST['status'])) $data['status'] = $_POST['status'];
        if (isset($_POST['version'])) $data['version'] = $_POST['version'];
        
        if ($systemManager->updateSystem($systemId, $data)) {
            redirect('dashboard/systems.php?success=updated');
        }
    } elseif ($action === 'generate_token' && $canUpdate && $systemId) {
        $scopes = $_POST['scopes'] ?? ['*'];
        if (is_string($scopes)) {
            $scopes = explode(',', $scopes);
            $scopes = array_map('trim', $scopes);
        }
        
        $token = $systemManager->generateSystemToken($systemId, $scopes);
        if ($token) {
            redirect('dashboard/systems.php?action=view_token&id=' . $systemId . '&token=' . urlencode($token));
        }
    }
}

$systems = $systemManager->listSystems();
$selectedSystem = $systemId ? $systemManager->getSystemById($systemId) : null;
?>
<?php $pageTitle = 'Sistemas - KRON'; include __DIR__ . '/_head.php'; ?>
<body>
    <?php include __DIR__ . '/_sidebar.php'; ?>
    
    <div class="ml-64 p-8 min-h-screen">
        <div class="mb-8 flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold mb-2 text-gray-900 dark:text-white">Sistemas Governados</h1>
                <p class="text-gray-500 dark:text-gray-400">Gerencie sistemas conectados ao KRON</p>
            </div>
            <?php if ($canCreate): ?>
                <button onclick="document.getElementById('createModal').classList.remove('hidden')"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors shadow-sm">
                    + Novo Sistema
                </button>
            <?php endif; ?>
        </div>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="bg-green-50 dark:bg-green-500/10 border border-green-200 dark:border-green-500/20 text-green-700 dark:text-green-400 px-4 py-3 rounded-xl mb-6 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                Sistema <?= $_GET['success'] === 'created' ? 'criado' : 'atualizado' ?> com sucesso!
            </div>
        <?php endif; ?>
        
        <div class="bg-white dark:bg-[#111111] rounded-2xl border border-gray-200 dark:border-neutral-800 overflow-hidden shadow-sm">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-neutral-900/50 border-b border-gray-200 dark:border-neutral-800">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-neutral-400 uppercase tracking-wider">Sistema</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-neutral-400 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-neutral-400 uppercase tracking-wider">Versão</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-neutral-400 uppercase tracking-wider">API URL</th>
                        <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 dark:text-neutral-400 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-neutral-800">
                    <?php if (empty($systems)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-neutral-500">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <svg class="w-8 h-8 text-gray-400 dark:text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                    Nenhum sistema cadastrado
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($systems as $system): ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-neutral-900/30 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded bg-gray-100 dark:bg-neutral-800 flex items-center justify-center text-sm font-bold text-gray-700 dark:text-neutral-300">
                                            <?= strtoupper(substr($system['display_name'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div class="font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($system['display_name']) ?></div>
                                            <div class="text-xs text-gray-500 dark:text-neutral-500"><?= htmlspecialchars($system['name']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium <?= $system['status'] === 'active' ? 'bg-green-50 text-green-700 dark:bg-green-500/10 dark:text-green-400 border border-green-100 dark:border-green-500/20' : 'bg-gray-100 text-gray-600 dark:bg-neutral-800 dark:text-neutral-400 border border-gray-200 dark:border-neutral-700' ?>">
                                        <span class="w-1.5 h-1.5 rounded-full <?= $system['status'] === 'active' ? 'bg-green-500' : 'bg-gray-400' ?>"></span>
                                        <?= ucfirst($system['status']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-neutral-400 font-mono">
                                    <?= htmlspecialchars($system['version'] ?? '-') ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-neutral-500 max-w-xs truncate">
                                    <?= htmlspecialchars($system['api_url'] ?? '-') ?>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-3">
                                        <?php if ($canUpdate): ?>
                                            <button onclick="editSystem(<?= $system['id'] ?>)"
                                                class="text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 text-sm font-medium transition-colors">Editar</button>
                                            <button onclick="generateToken(<?= $system['id'] ?>)"
                                                class="text-violet-600 hover:text-violet-700 dark:text-violet-400 dark:hover:text-violet-300 text-sm font-medium transition-colors">Token</button>
                                        <?php endif; ?>
                                    </div>
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
                <h2 class="text-2xl font-bold mb-4">Novo Sistema</h2>
                <form method="POST" action="?action=create">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium mb-2">Nome (código)</label>
                            <input type="text" name="name" required
                                class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 focus:outline-none focus:border-blue-500"
                                placeholder="safenode">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">Nome de Exibição</label>
                            <input type="text" name="display_name" required
                                class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 focus:outline-none focus:border-blue-500"
                                placeholder="SafeNode">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">Descrição</label>
                            <textarea name="description"
                                class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 focus:outline-none focus:border-blue-500"
                                rows="3"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">API URL</label>
                            <input type="url" name="api_url"
                                class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 focus:outline-none focus:border-blue-500"
                                placeholder="https://api.safenode.com/kron">
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
        function editSystem(id) {
            window.location.href = '?action=edit&id=' + id;
        }
        
        function generateToken(id) {
            window.location.href = '?action=generate_token&id=' + id;
        }
    </script>
</body>
</html>

