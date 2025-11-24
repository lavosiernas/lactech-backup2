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
    <title>IPs Bloqueados | SafeNode</title>
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
        
        /* Glass Components Melhorados */
        .glass-card { 
            background: linear-gradient(180deg, rgba(39, 39, 42, 0.5) 0%, rgba(24, 24, 27, 0.5) 100%); 
            backdrop-filter: blur(10px); 
            border: 1px solid rgba(255, 255, 255, 0.08); 
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .glass-card:hover {
            background: linear-gradient(180deg, rgba(39, 39, 42, 0.7) 0%, rgba(24, 24, 27, 0.7) 100%);
            border-color: rgba(255, 255, 255, 0.12);
        }

        /* Grid Pattern */
        .grid-pattern {
            background-image: 
                linear-gradient(rgba(255, 255, 255, 0.02) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.02) 1px, transparent 1px);
            background-size: 20px 20px;
        }

        /* Badge Moderno */
        .modern-badge {
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2), inset 0 1px 0 rgba(255, 255, 255, 0.1);
            transition: all 0.3s;
        }
        .modern-badge:hover {
            border-color: rgba(255, 255, 255, 0.15);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.15);
        }

        /* Tabela Melhorada */
        .table-row {
            transition: all 0.2s;
        }
        .table-row:hover {
            background: rgba(255, 255, 255, 0.03) !important;
            transform: translateX(2px);
        }

        /* Animações */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in {
            animation: fadeIn 0.5s ease-out;
        }

        /* Depth Shadow */
        .depth-shadow {
            box-shadow: 
                0 10px 30px rgba(0, 0, 0, 0.5),
                0 0 0 1px rgba(255, 255, 255, 0.05),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
        }
    </style>
</head>
<body class="bg-black text-zinc-200 font-sans h-full overflow-hidden flex">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="flex-1 flex flex-col h-full relative overflow-hidden bg-black">
        <header class="h-16 border-b border-white/5 bg-black/50 backdrop-blur-xl sticky top-0 z-40 px-6 flex items-center justify-between">
            <div class="hidden md:flex md:items-center md:gap-3">
                <div class="w-0.5 h-6 bg-gradient-to-b from-red-500 to-orange-500 rounded-full"></div>
                <div>
                    <h2 class="text-xl font-bold text-white tracking-tight">IPs Bloqueados</h2>
                    <p class="text-xs text-zinc-400 mt-0.5 font-medium"><?php echo count($activeBlocks); ?> bloqueios ativos</p>
                </div>
            </div>
            <div class="flex items-center gap-4 md:hidden">
                <button class="text-zinc-400 hover:text-white transition-colors" data-sidebar-toggle>
                    <i data-lucide="menu" class="w-6 h-6"></i>
                </button>
                <span class="font-bold text-lg text-white">SafeNode</span>
            </div>
        </header>
        <div class="flex-1 overflow-y-auto p-6 md:p-8 z-10">
            <div class="max-w-7xl mx-auto space-y-6">
            <!-- IPs Bloqueados Ativos - Redesign -->
            <div class="glass-card rounded-xl overflow-hidden relative animate-fade-in depth-shadow">
                <!-- Grid pattern -->
                <div class="absolute inset-0 grid-pattern opacity-20"></div>
                
                <!-- Decoração de fundo -->
                <div class="absolute top-0 right-0 w-40 h-40 bg-red-500/5 rounded-full blur-3xl"></div>
                
                <div class="relative z-10">
                    <div class="p-6 border-b border-white/5 flex items-center justify-between bg-zinc-900/30">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl bg-red-500/15 border border-red-500/30 flex items-center justify-center">
                                <i data-lucide="shield-alert" class="w-6 h-6 text-red-400"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-white tracking-tight">IPs Bloqueados Ativos</h3>
                                <p class="text-sm text-zinc-400 mt-1 font-medium">Bloqueios em vigor no momento</p>
                            </div>
                        </div>
                        <span class="modern-badge inline-flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-bold bg-red-500/15 text-red-400 border border-red-500/30">
                            <i data-lucide="alert-circle" class="w-4 h-4"></i>
                            <?php echo count($activeBlocks); ?> ativos
                        </span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-zinc-900/50 text-zinc-400 font-bold uppercase text-xs tracking-wider border-b border-white/5">
                                <tr>
                                    <th class="px-6 py-4">IP Address</th>
                                    <th class="px-6 py-4">Motivo</th>
                                    <th class="px-6 py-4">Tipo</th>
                                    <th class="px-6 py-4">Bloqueado em</th>
                                    <th class="px-6 py-4">Expira em</th>
                                    <th class="px-6 py-4 text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <?php if (empty($activeBlocks)): ?>
                                    <tr>
                                        <td colspan="6">
                                            <div class="p-12 text-center">
                                                <div class="w-16 h-16 bg-zinc-900/60 rounded-xl border border-white/5 flex items-center justify-center mx-auto mb-4">
                                                    <i data-lucide="check-circle" class="w-8 h-8 text-emerald-400"></i>
                                                </div>
                                                <p class="text-sm text-zinc-300 font-bold mb-1">Nenhum IP bloqueado</p>
                                                <p class="text-xs text-zinc-500 font-medium">Tudo tranquilo por aqui</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($activeBlocks as $block): ?>
                                        <tr class="table-row group">
                                            <td class="px-6 py-4">
                                                <div class="flex items-center gap-2">
                                                    <i data-lucide="network" class="w-4 h-4 text-zinc-500"></i>
                                                    <span class="text-sm font-bold text-white font-mono"><?php echo htmlspecialchars($block['ip_address']); ?></span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="text-sm text-zinc-300 font-semibold"><?php echo htmlspecialchars($block['reason']); ?></span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="modern-badge inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold bg-red-500/15 text-red-400 border border-red-500/30">
                                                    <i data-lucide="shield-alert" class="w-3.5 h-3.5"></i>
                                                    <?php echo htmlspecialchars($block['threat_type']); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="flex items-center gap-2">
                                                    <i data-lucide="clock" class="w-4 h-4 text-zinc-500"></i>
                                                    <span class="text-sm text-zinc-300 font-mono font-semibold"><?php echo date('d/m/Y H:i:s', strtotime($block['blocked_at'])); ?></span>
                                                </div>
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
                                                        echo '<div class="flex items-center gap-2 mb-1">';
                                                        echo '<i data-lucide="timer" class="w-4 h-4 text-amber-400"></i>';
                                                        echo '<span class="text-sm text-zinc-300 font-bold">' . date('d/m/Y H:i', $expires) . '</span>';
                                                        echo '</div>';
                                                        echo '<span class="text-xs text-amber-400 font-medium ml-6">' . $hours . 'h ' . $minutes . 'm restantes</span>';
                                                    } else {
                                                        echo '<span class="modern-badge inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold bg-red-500/15 text-red-400 border border-red-500/30">Expirado</span>';
                                                    }
                                                    ?>
                                                <?php else: ?>
                                                    <span class="modern-badge inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold bg-zinc-800/60 text-zinc-400 border border-white/10">
                                                        <i data-lucide="infinity" class="w-3.5 h-3.5"></i>
                                                        Permanente
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 text-right">
                                                <form method="POST" class="inline" onsubmit="return confirm('Deseja realmente desbloquear este IP?');">
                                                    <input type="hidden" name="ip_id" value="<?php echo $block['id'] ?? ''; ?>">
                                                    <input type="hidden" name="action" value="unblock">
                                                    <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-bold text-emerald-400 hover:text-emerald-300 hover:bg-emerald-500/10 border border-emerald-500/20 hover:border-emerald-500/30 transition-all">
                                                        <i data-lucide="unlock" class="w-4 h-4"></i>
                                                        Desbloquear
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Histórico de Bloqueios - Redesign -->
            <div class="glass-card rounded-xl overflow-hidden relative animate-fade-in depth-shadow" style="animation-delay: 0.1s">
                <!-- Grid pattern -->
                <div class="absolute inset-0 grid-pattern opacity-20"></div>
                
                <!-- Decoração de fundo -->
                <div class="absolute top-0 right-0 w-40 h-40 bg-zinc-500/5 rounded-full blur-3xl"></div>
                
                <div class="relative z-10">
                    <div class="p-6 border-b border-white/5 bg-zinc-900/30">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl bg-zinc-800/60 border border-white/5 flex items-center justify-center">
                                <i data-lucide="history" class="w-6 h-6 text-zinc-400"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-white tracking-tight">Histórico de Bloqueios</h3>
                                <p class="text-sm text-zinc-400 mt-1 font-medium">Todos os bloqueios registrados</p>
                            </div>
                        </div>
                    </div>
                
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-zinc-900/50 text-zinc-400 font-bold uppercase text-xs tracking-wider border-b border-white/5">
                                <tr>
                                    <th class="px-6 py-4">IP Address</th>
                                    <th class="px-6 py-4">Motivo</th>
                                    <th class="px-6 py-4">Status</th>
                                    <th class="px-6 py-4">Bloqueado em</th>
                                    <th class="px-6 py-4 text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <?php if (empty($blockedIPs)): ?>
                                    <tr>
                                        <td colspan="5">
                                            <div class="p-12 text-center">
                                                <div class="w-16 h-16 bg-zinc-900/60 rounded-xl border border-white/5 flex items-center justify-center mx-auto mb-4">
                                                    <i data-lucide="file-text" class="w-8 h-8 text-zinc-500"></i>
                                                </div>
                                                <p class="text-sm text-zinc-300 font-bold mb-1">Nenhum bloqueio registrado</p>
                                                <p class="text-xs text-zinc-500 font-medium">Os bloqueios aparecerão aqui</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($blockedIPs as $block): ?>
                                        <tr class="table-row group">
                                            <td class="px-6 py-4">
                                                <div class="flex items-center gap-2">
                                                    <i data-lucide="network" class="w-4 h-4 text-zinc-500"></i>
                                                    <span class="text-sm font-bold text-white font-mono"><?php echo htmlspecialchars($block['ip_address']); ?></span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="text-sm text-zinc-300 font-semibold"><?php echo htmlspecialchars($block['reason']); ?></span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <?php if ($block['is_active']): ?>
                                                    <span class="modern-badge inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold bg-red-500/15 text-red-400 border border-red-500/30">
                                                        <i data-lucide="shield-alert" class="w-3.5 h-3.5"></i>
                                                        Ativo
                                                    </span>
                                                <?php else: ?>
                                                    <span class="modern-badge inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold bg-zinc-800/60 text-zinc-400 border border-white/10">
                                                        <i data-lucide="shield-off" class="w-3.5 h-3.5"></i>
                                                        Inativo
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="flex items-center gap-2">
                                                    <i data-lucide="clock" class="w-4 h-4 text-zinc-500"></i>
                                                    <span class="text-sm text-zinc-300 font-mono font-semibold"><?php echo date('d/m/Y H:i:s', strtotime($block['blocked_at'])); ?></span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 text-right">
                                                <?php if ($block['is_active']): ?>
                                                    <form method="POST" class="inline" onsubmit="return confirm('Deseja realmente desbloquear este IP?');">
                                                        <input type="hidden" name="ip_id" value="<?php echo $block['id']; ?>">
                                                        <input type="hidden" name="action" value="unblock">
                                                        <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-bold text-emerald-400 hover:text-emerald-300 hover:bg-emerald-500/10 border border-emerald-500/20 hover:border-emerald-500/30 transition-all">
                                                            <i data-lucide="unlock" class="w-4 h-4"></i>
                                                            Desbloquear
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <form method="POST" class="inline" onsubmit="return confirm('Deseja realmente excluir este registro?');">
                                                        <input type="hidden" name="ip_id" value="<?php echo $block['id']; ?>">
                                                        <input type="hidden" name="action" value="delete">
                                                        <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-bold text-red-400 hover:text-red-300 hover:bg-red-500/10 border border-red-500/20 hover:border-red-500/30 transition-all">
                                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                            Excluir
                                                        </button>
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
        </div>
    </main>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>
