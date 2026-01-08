<?php
/**
 * KRON - Notificações
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

requireAuth();

$user = getCurrentUser();
$pdo = getKronDatabase();

// Marcar como lida
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_read') {
    $notificationId = $_POST['id'] ?? null;
    if ($notificationId && $pdo) {
        $stmt = $pdo->prepare("UPDATE kron_notifications SET is_read = 1, read_at = NOW() WHERE id = ? AND kron_user_id = ?");
        $stmt->execute([$notificationId, $user['id']]);
        redirect('dashboard/notifications.php');
    }
}

// Marcar todas como lidas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_all_read') {
    if ($pdo) {
        $stmt = $pdo->prepare("UPDATE kron_notifications SET is_read = 1, read_at = NOW() WHERE kron_user_id = ? AND is_read = 0");
        $stmt->execute([$user['id']]);
        redirect('dashboard/notifications.php');
    }
}

// Buscar notificações
$notifications = [];
$unreadCount = 0;

if ($pdo) {
    $filter = $_GET['filter'] ?? 'all';
    
    $sql = "SELECT * FROM kron_notifications WHERE kron_user_id = ?";
    $params = [$user['id']];
    
    if ($filter === 'unread') {
        $sql .= " AND is_read = 0";
    } elseif ($filter === 'read') {
        $sql .= " AND is_read = 1";
    }
    
    $sql .= " ORDER BY created_at DESC LIMIT 100";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $notifications = $stmt->fetchAll();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM kron_notifications WHERE kron_user_id = ? AND is_read = 0");
    $stmt->execute([$user['id']]);
    $unreadCount = $stmt->fetch()['total'];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificações - KRON</title>
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
                <h1 class="text-3xl font-bold mb-2">Notificações</h1>
                <p class="text-gray-400">Central de notificações do sistema</p>
            </div>
            <?php if ($unreadCount > 0): ?>
                <form method="POST" class="inline">
                    <input type="hidden" name="action" value="mark_all_read">
                    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg font-medium">
                        Marcar todas como lidas
                    </button>
                </form>
            <?php endif; ?>
        </div>
        
        <!-- Filtros -->
        <div class="mb-6 flex gap-2">
            <a href="?filter=all" class="px-4 py-2 rounded-lg <?= ($_GET['filter'] ?? 'all') === 'all' ? 'bg-blue-500 text-white' : 'bg-gray-800 text-gray-400 hover:bg-gray-700' ?>">
                Todas
            </a>
            <a href="?filter=unread" class="px-4 py-2 rounded-lg <?= ($_GET['filter'] ?? '') === 'unread' ? 'bg-blue-500 text-white' : 'bg-gray-800 text-gray-400 hover:bg-gray-700' ?>">
                Não lidas (<?= $unreadCount ?>)
            </a>
            <a href="?filter=read" class="px-4 py-2 rounded-lg <?= ($_GET['filter'] ?? '') === 'read' ? 'bg-blue-500 text-white' : 'bg-gray-800 text-gray-400 hover:bg-gray-700' ?>">
                Lidas
            </a>
        </div>
        
        <!-- Notificações -->
        <div class="space-y-3">
            <?php if (empty($notifications)): ?>
                <div class="bg-gray-900 rounded-xl border border-gray-800 p-12 text-center">
                    <div class="text-gray-400 text-lg mb-2">Nenhuma notificação</div>
                    <div class="text-gray-500 text-sm">Você está em dia!</div>
                </div>
            <?php else: ?>
                <?php foreach ($notifications as $notif): ?>
                    <div class="bg-gray-900 rounded-xl border border-gray-800 p-6 <?= !$notif['is_read'] ? 'border-blue-500/50 bg-blue-500/5' : '' ?>">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <h3 class="font-semibold text-lg"><?= htmlspecialchars($notif['title']) ?></h3>
                                    <?php if (!$notif['is_read']): ?>
                                        <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
                                    <?php endif; ?>
                                    <span class="px-2 py-1 text-xs rounded bg-gray-700 text-gray-300">
                                        <?= htmlspecialchars($notif['system_name'] ?? 'KRON') ?>
                                    </span>
                                </div>
                                <p class="text-gray-400 mb-3"><?= htmlspecialchars($notif['message']) ?></p>
                                <div class="flex items-center gap-4 text-sm text-gray-500">
                                    <span><?= date('d/m/Y H:i', strtotime($notif['created_at'])) ?></span>
                                    <?php if ($notif['action_url']): ?>
                                        <a href="<?= htmlspecialchars($notif['action_url']) ?>" class="text-blue-400 hover:text-blue-300">
                                            Ver detalhes →
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if (!$notif['is_read']): ?>
                                <form method="POST" class="ml-4">
                                    <input type="hidden" name="action" value="mark_read">
                                    <input type="hidden" name="id" value="<?= $notif['id'] ?>">
                                    <button type="submit" class="text-gray-400 hover:text-white text-sm">
                                        Marcar como lida
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

