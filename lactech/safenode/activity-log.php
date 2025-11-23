<?php
/**
 * SafeNode - Histórico de Atividades
 * Visualização de ações e eventos da conta
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
require_once __DIR__ . '/includes/ActivityLogger.php';

$db = getSafeNodeDatabase();
$activityLogger = new ActivityLogger($db);

$userId = $_SESSION['safenode_user_id'] ?? null;

// Paginação
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Buscar atividades
$activities = $activityLogger->getUserActivities($userId, $perPage, $offset);
$totalActivities = $activityLogger->countUserActivities($userId);
$totalPages = ceil($totalActivities / $perPage);
?>
<!DOCTYPE html>
<html lang="pt-BR" class="dark h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico de Atividades - SafeNode</title>
    <link rel="icon" type="image/png" href="assets/img/logos (6).png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #3f3f46; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #52525b; }
        .glass-card {
            background: #000000;
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
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
            <div class="hidden md:flex items-center justify-between w-full">
                <div>
                    <h2 class="text-xl font-bold text-white tracking-tight">Histórico de Atividades</h2>
                    <p class="text-xs text-zinc-400 mt-0.5">Visualize ações recentes em sua conta</p>
                </div>
                <a href="profile.php" class="text-zinc-400 hover:text-white transition-colors flex items-center gap-2">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                    <span class="text-sm">Voltar</span>
                </a>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-4 md:p-8 z-10">
            <div class="max-w-4xl mx-auto space-y-6">
                <!-- Resumo -->
                <div class="glass-card rounded-2xl p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-white mb-1">Registro de Atividades</h3>
                            <p class="text-sm text-zinc-500"><?php echo $totalActivities; ?> evento(s) registrado(s)</p>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-blue-600/20 flex items-center justify-center">
                            <i data-lucide="activity" class="w-6 h-6 text-blue-400"></i>
                        </div>
                    </div>
                </div>

                <!-- Timeline de Atividades -->
                <div class="space-y-4">
                    <?php if (empty($activities)): ?>
                        <div class="glass-card rounded-2xl p-8 text-center">
                            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-zinc-800/50 flex items-center justify-center">
                                <i data-lucide="inbox" class="w-8 h-8 text-zinc-500"></i>
                            </div>
                            <p class="text-zinc-400">Nenhuma atividade registrada ainda</p>
                        </div>
                    <?php else: ?>
                        <?php 
                        $currentDate = '';
                        foreach ($activities as $activity): 
                            $activityDate = date('d/m/Y', strtotime($activity['created_at']));
                            $activityTime = date('H:i', strtotime($activity['created_at']));
                            $showDateHeader = ($currentDate !== $activityDate);
                            $currentDate = $activityDate;
                            
                            $icon = ActivityLogger::getActionIcon($activity['action']);
                            $colorClass = ActivityLogger::getActionColor($activity['status']);
                            $actionLabel = ActivityLogger::translateAction($activity['action']);
                        ?>
                        
                        <?php if ($showDateHeader): ?>
                            <div class="flex items-center gap-3 mt-6 first:mt-0">
                                <div class="h-px flex-1 bg-white/5"></div>
                                <span class="text-xs font-semibold text-zinc-500 uppercase tracking-wider"><?php echo $activityDate; ?></span>
                                <div class="h-px flex-1 bg-white/5"></div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="glass-card rounded-xl p-5 hover:border-white/10 transition-colors">
                            <div class="flex items-start gap-4">
                                <div class="w-10 h-10 rounded-lg bg-zinc-900/50 flex items-center justify-center flex-shrink-0">
                                    <i data-lucide="<?php echo $icon; ?>" class="w-5 h-5 <?php echo $colorClass; ?>"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start justify-between gap-4 mb-2">
                                        <div>
                                            <h4 class="text-sm font-semibold text-white mb-1">
                                                <?php echo htmlspecialchars($actionLabel); ?>
                                            </h4>
                                            <?php if ($activity['description']): ?>
                                                <p class="text-sm text-zinc-400">
                                                    <?php echo htmlspecialchars($activity['description']); ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                        <span class="text-xs text-zinc-500 whitespace-nowrap">
                                            <?php echo $activityTime; ?>
                                        </span>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-3 text-xs text-zinc-500">
                                        <span class="flex items-center gap-1.5">
                                            <i data-lucide="<?php echo $activity['device_type'] === 'mobile' ? 'smartphone' : ($activity['device_type'] === 'tablet' ? 'tablet' : 'monitor'); ?>" class="w-3.5 h-3.5"></i>
                                            <?php echo htmlspecialchars($activity['browser']); ?>
                                        </span>
                                        <span class="flex items-center gap-1.5">
                                            <i data-lucide="map-pin" class="w-3.5 h-3.5"></i>
                                            <?php echo htmlspecialchars($activity['ip_address']); ?>
                                        </span>
                                        <?php if ($activity['status'] === 'failed'): ?>
                                            <span class="px-2 py-0.5 bg-red-500/20 text-red-400 rounded text-xs font-semibold">
                                                Falhou
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Paginação -->
                <?php if ($totalPages > 1): ?>
                    <div class="flex items-center justify-between gap-4 pt-4">
                        <p class="text-sm text-zinc-500">
                            Página <?php echo $page; ?> de <?php echo $totalPages; ?>
                        </p>
                        <div class="flex gap-2">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>" class="px-4 py-2 bg-zinc-900/50 hover:bg-zinc-800 text-white rounded-lg text-sm font-medium transition-all border border-white/10 flex items-center gap-2">
                                    <i data-lucide="chevron-left" class="w-4 h-4"></i>
                                    Anterior
                                </a>
                            <?php endif; ?>
                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?php echo $page + 1; ?>" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-all shadow-lg shadow-blue-500/20 flex items-center gap-2">
                                    Próxima
                                    <i data-lucide="chevron-right" class="w-4 h-4"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Informações -->
                <div class="glass-card rounded-2xl p-6">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-lg bg-blue-600/20 flex items-center justify-center flex-shrink-0">
                            <i data-lucide="info" class="w-5 h-5 text-blue-400"></i>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-white mb-2">Sobre o Histórico</h4>
                            <p class="text-xs text-zinc-400 mb-3">
                                O histórico de atividades registra todas as ações importantes realizadas em sua conta, incluindo logins, alterações de configurações e muito mais.
                            </p>
                            <ul class="space-y-2 text-xs text-zinc-400">
                                <li class="flex items-start gap-2">
                                    <i data-lucide="check" class="w-3.5 h-3.5 text-blue-400 mt-0.5 flex-shrink-0"></i>
                                    <span>Os registros são mantidos por 90 dias</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <i data-lucide="check" class="w-3.5 h-3.5 text-blue-400 mt-0.5 flex-shrink-0"></i>
                                    <span>Revise regularmente para detectar atividades suspeitas</span>
                                </li>
                            </ul>
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


