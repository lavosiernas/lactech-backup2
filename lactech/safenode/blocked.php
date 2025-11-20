<?php
/**
 * SafeNode - IPs Bloqueados
 */

session_start();

// Verificar se está logado
if (!isset($_SESSION['safenode_logged_in']) || $_SESSION['safenode_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/init.php';

$db = getSafeNodeDatabase();

// Ações
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $ipId = intval($_POST['ip_id'] ?? 0);
    
    if ($db) {
        try {
            if ($action === 'unblock') {
                $stmt = $db->prepare("UPDATE safenode_blocked_ips SET is_active = FALSE WHERE id = ?");
                $stmt->execute([$ipId]);
            } elseif ($action === 'delete') {
                $stmt = $db->prepare("DELETE FROM safenode_blocked_ips WHERE id = ?");
                $stmt->execute([$ipId]);
            }
            header('Location: blocked.php');
            exit;
        } catch (PDOException $e) {
            $error = "Erro ao processar ação: " . $e->getMessage();
        }
    }
}

// Buscar IPs bloqueados
$blockedIPs = [];
$activeBlocks = [];

if ($db) {
    try {
        // IPs bloqueados ativos (buscar da tabela original para ter o ID)
        $stmt = $db->query("SELECT * FROM safenode_blocked_ips WHERE is_active = TRUE AND (expires_at IS NULL OR expires_at > NOW()) ORDER BY blocked_at DESC");
        $activeBlocks = $stmt->fetchAll();
        
        // Todos os IPs bloqueados (incluindo inativos)
        $stmt = $db->query("SELECT * FROM safenode_blocked_ips ORDER BY blocked_at DESC LIMIT 100");
        $blockedIPs = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("SafeNode Blocked IPs Error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR" class="dark h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IPs Bloqueados - SafeNode</title>
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
            <div>
                <h2 class="text-xl font-bold text-white tracking-tight">IPs Bloqueados</h2>
                <p class="text-xs text-zinc-400 mt-0.5"><?php echo count($activeBlocks); ?> bloqueios ativos</p>
            </div>
        </header>
        <div class="flex-1 overflow-y-auto p-6 md:p-8 z-10">
            <div class="max-w-7xl mx-auto space-y-6">
            <div class="glass-card rounded-xl overflow-hidden">
                <div class="p-6 border-b border-white/5 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-white tracking-tight">IPs Bloqueados Ativos</h3>
                        <p class="text-sm text-zinc-400 mt-1.5">Bloqueios em vigor no momento</p>
                    </div>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-500/10 text-red-400 border border-red-500/20">
                        <?php echo count($activeBlocks); ?> ativos
                    </span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-zinc-900/50 text-zinc-400 font-medium uppercase text-xs tracking-wider">
                            <tr>
                                <th class="px-6 py-4">IP Address</th>
                                <th class="px-6 py-4">Motivo</th>
                                <th class="px-6 py-4">Tipo</th>
                                <th class="px-6 py-4">Bloqueado em</th>
                                <th class="px-6 py-4">Expira em</th>
                                <th class="px-6 py-4">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <?php if (empty($activeBlocks)): ?>
                                <tr>
                                    <td colspan="6">
                                        <div class="p-12 text-center">
                                            <div class="w-16 h-16 bg-zinc-900 rounded-xl flex items-center justify-center mx-auto mb-4">
                                                <i data-lucide="check-circle" class="w-8 h-8 text-zinc-500"></i>
                                            </div>
                                            <p class="text-sm text-zinc-400 font-medium">Nenhum IP bloqueado</p>
                                            <p class="text-xs text-zinc-500 mt-1">Tudo tranquilo por aqui</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($activeBlocks as $block): ?>
                                    <tr class="hover:bg-white/[0.02] transition-colors">
                                        <td class="px-6 py-4">
                                            <span class="text-sm font-semibold text-white font-mono"><?php echo htmlspecialchars($block['ip_address']); ?></span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="text-sm text-zinc-300 font-medium"><?php echo htmlspecialchars($block['reason']); ?></span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-500/10 text-red-400 border border-red-500/20">
                                                <?php echo htmlspecialchars($block['threat_type']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="text-sm text-zinc-300 font-mono"><?php echo date('d/m/Y H:i:s', strtotime($block['blocked_at'])); ?></span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php if ($block['expires_at']): ?>
                                                <?php 
                                                $expires = strtotime($block['expires_at']);
                                                $now = time();
                                                $remaining = $expires - $now;
                                                if ($remaining > 0) {
                                                    $hours = floor($remaining / 3600);
                                                    $minutes = floor(($remaining % 3600) / 60);
                                                    echo '<span class="text-sm text-zinc-300 font-semibold">' . date('d/m/Y H:i', $expires) . '</span>';
                                                    echo '<span class="text-xs text-zinc-500 block mt-0.5">' . $hours . 'h ' . $minutes . 'm restantes</span>';
                                                } else {
                                                    echo '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-500/10 text-red-400 border border-red-500/20">Expirado</span>';
                                                }
                                                ?>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-zinc-800 text-zinc-400 border border-white/5">Permanente</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <form method="POST" class="inline" onsubmit="return confirm('Deseja realmente desbloquear este IP?');">
                                                <input type="hidden" name="ip_id" value="<?php echo $block['id'] ?? ''; ?>">
                                                <input type="hidden" name="action" value="unblock">
                                                <button type="submit" class="text-emerald-400 hover:text-emerald-300 text-sm font-semibold transition-colors">Desbloquear</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="glass-card rounded-xl overflow-hidden">
                <div class="p-6 border-b border-white/5">
                    <h3 class="text-lg font-bold text-white tracking-tight">Histórico de Bloqueios</h3>
                    <p class="text-sm text-zinc-400 mt-1.5">Todos os bloqueios registrados</p>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-zinc-900/50 text-zinc-400 font-medium uppercase text-xs tracking-wider">
                            <tr>
                                <th class="px-6 py-4">IP Address</th>
                                <th class="px-6 py-4">Motivo</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4">Bloqueado em</th>
                                <th class="px-6 py-4">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <?php if (empty($blockedIPs)): ?>
                                <tr>
                                    <td colspan="5">
                                        <div class="p-12 text-center">
                                            <div class="w-16 h-16 bg-zinc-900 rounded-xl flex items-center justify-center mx-auto mb-4">
                                                <i data-lucide="file-text" class="w-8 h-8 text-zinc-500"></i>
                                            </div>
                                            <p class="text-sm text-zinc-400 font-medium">Nenhum bloqueio registrado</p>
                                            <p class="text-xs text-zinc-500 mt-1">Os bloqueios aparecerão aqui</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($blockedIPs as $block): ?>
                                    <tr class="hover:bg-white/[0.02] transition-colors">
                                        <td class="px-6 py-4">
                                            <span class="text-sm font-semibold text-white font-mono"><?php echo htmlspecialchars($block['ip_address']); ?></span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="text-sm text-zinc-300 font-medium"><?php echo htmlspecialchars($block['reason']); ?></span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php if ($block['is_active']): ?>
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-500/10 text-red-400 border border-red-500/20">Ativo</span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-zinc-800 text-zinc-400 border border-white/5">Inativo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="text-sm text-zinc-300 font-mono"><?php echo date('d/m/Y H:i:s', strtotime($block['blocked_at'])); ?></span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php if ($block['is_active']): ?>
                                                <form method="POST" class="inline" onsubmit="return confirm('Deseja realmente desbloquear este IP?');">
                                                    <input type="hidden" name="ip_id" value="<?php echo $block['id']; ?>">
                                                    <input type="hidden" name="action" value="unblock">
                                                    <button type="submit" class="text-emerald-400 hover:text-emerald-300 text-sm font-semibold transition-colors">Desbloquear</button>
                                                </form>
                                            <?php else: ?>
                                                <form method="POST" class="inline" onsubmit="return confirm('Deseja realmente excluir este registro?');">
                                                    <input type="hidden" name="ip_id" value="<?php echo $block['id']; ?>">
                                                    <input type="hidden" name="action" value="delete">
                                                    <button type="submit" class="text-red-400 hover:text-red-300 text-sm font-semibold transition-colors">Excluir</button>
                                                </form>
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
        </div>
    </main>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>
