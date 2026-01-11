<?php
/**
 * KRON - Gestão de Usuários
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/KronRBAC.php';

requireAuth();
requirePermission('user.read');

$user = getCurrentUser();
$rbac = new KronRBAC();
$pdo = getKronDatabase();

$canCreate = $rbac->hasPermission($user['id'], 'user.create');
$canUpdate = $rbac->hasPermission($user['id'], 'user.update');
$canDelete = $rbac->hasPermission($user['id'], 'user.delete');
$isCEO = $rbac->isCEO($user['id']);

// Buscar usuários
$users = [];
if ($pdo) {
    $stmt = $pdo->query("
        SELECT u.*, 
               GROUP_CONCAT(DISTINCT r.name ORDER BY r.level SEPARATOR ', ') as roles
        FROM kron_users u
        LEFT JOIN kron_user_roles ur ON u.id = ur.user_id
        LEFT JOIN kron_roles r ON ur.role_id = r.id
        GROUP BY u.id
        ORDER BY u.created_at DESC
    ");
    $users = $stmt->fetchAll();
}

// Buscar roles
$roles = [];
if ($pdo) {
    $stmt = $pdo->query("SELECT * FROM kron_roles ORDER BY level ASC");
    $roles = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuários - KRON</title>
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
                <h1 class="text-3xl font-bold mb-2">Usuários</h1>
                <p class="text-gray-400">Gerencie usuários e permissões</p>
            </div>
        </div>
        
        <div class="bg-gray-900 rounded-xl border border-gray-800 overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-800">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-semibold">Usuário</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold">Email</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold">Roles</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold">Status</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold">Último Login</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800">
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                                Nenhum usuário cadastrado
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $usr): ?>
                            <tr class="hover:bg-gray-800/50">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <?php if ($usr['avatar_url']): ?>
                                            <img src="<?= htmlspecialchars($usr['avatar_url']) ?>" alt="" class="w-10 h-10 rounded-full">
                                        <?php else: ?>
                                            <div class="w-10 h-10 rounded-full bg-gray-700 flex items-center justify-center text-sm font-semibold">
                                                <?= strtoupper(substr($usr['name'], 0, 1)) ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="font-medium"><?= htmlspecialchars($usr['name']) ?></div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-400">
                                    <?= htmlspecialchars($usr['email']) ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-1">
                                        <?php if ($usr['roles']): ?>
                                            <?php foreach (explode(', ', $usr['roles']) as $role): ?>
                                                <span class="px-2 py-1 text-xs rounded bg-blue-500/10 text-blue-400">
                                                    <?= htmlspecialchars($role) ?>
                                                </span>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <span class="text-gray-500 text-xs">Sem roles</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs rounded <?= $usr['is_active'] ? 'bg-green-500/10 text-green-400' : 'bg-red-500/10 text-red-400' ?>">
                                        <?= $usr['is_active'] ? 'Ativo' : 'Inativo' ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-400">
                                    <?= $usr['last_login'] ? date('d/m/Y H:i', strtotime($usr['last_login'])) : 'Nunca' ?>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <?php if ($canUpdate): ?>
                                        <button onclick="editUser(<?= $usr['id'] ?>)"
                                            class="text-blue-400 hover:text-blue-300 text-sm">Editar</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script>
        function editUser(id) {
            window.location.href = '?action=edit&id=' + id;
        }
    </script>
</body>
</html>



