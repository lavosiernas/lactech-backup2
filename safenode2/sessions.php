<?php
/**
 * SafeNode - Sessões Ativas
 * Gerenciamento de dispositivos e sessões ativas
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
require_once __DIR__ . '/includes/SessionManager.php';
require_once __DIR__ . '/includes/ActivityLogger.php';

$db = getSafeNodeDatabase();
$sessionManager = new SessionManager($db);
$activityLogger = new ActivityLogger($db);

$userId = $_SESSION['safenode_user_id'] ?? null;
$currentSessionToken = $_SESSION['safenode_session_token'] ?? null;

$message = '';
$messageType = '';

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRFProtection::validate()) {
        $message = 'Token de segurança inválido.';
        $messageType = 'error';
    } else {
        if (isset($_POST['terminate_session']) && isset($_POST['session_token'])) {
            // Encerrar sessão específica
            $sessionToken = $_POST['session_token'];
            if ($sessionManager->terminateSession($userId, $sessionToken)) {
                $activityLogger->logSessionTerminated($userId, $sessionToken, false);
                $message = 'Sessão encerrada com sucesso!';
                $messageType = 'success';
            } else {
                $message = 'Erro ao encerrar sessão.';
                $messageType = 'error';
            }
        } elseif (isset($_POST['terminate_all'])) {
            // Encerrar todas as sessões exceto a atual
            if ($sessionManager->terminateAllSessions($userId, $currentSessionToken)) {
                $activityLogger->logSessionTerminated($userId, null, true);
                $message = 'Todas as outras sessões foram encerradas!';
                $messageType = 'success';
            } else {
                $message = 'Erro ao encerrar sessões.';
                $messageType = 'error';
            }
        }
    }
}

// Buscar sessões ativas
$sessions = $sessionManager->getUserSessions($userId);
?>
<!DOCTYPE html>
<html lang="pt-BR" class="dark h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sessões Ativas - SafeNode</title>
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
                    <h2 class="text-xl font-bold text-white tracking-tight">Sessões Ativas</h2>
                    <p class="text-xs text-zinc-400 mt-0.5">Gerencie dispositivos conectados à sua conta</p>
                </div>
                <a href="profile.php" class="text-zinc-400 hover:text-white transition-colors flex items-center gap-2">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                    <span class="text-sm">Voltar</span>
                </a>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-4 md:p-8 z-10">
            <div class="max-w-4xl mx-auto space-y-6">
                <?php if ($message): ?>
                    <div class="p-4 rounded-xl <?php 
                        echo $messageType === 'success' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 
                            'bg-red-500/10 text-red-400 border border-red-500/20'; 
                    ?> font-medium flex items-start gap-3">
                        <i data-lucide="<?php echo $messageType === 'success' ? 'check-circle' : 'alert-circle'; ?>" class="w-5 h-5 mt-0.5 flex-shrink-0"></i>
                        <span><?php echo htmlspecialchars($message); ?></span>
                    </div>
                <?php endif; ?>

                <!-- Resumo -->
                <div class="glass-card rounded-2xl p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-white mb-1">Dispositivos Conectados</h3>
                            <p class="text-sm text-zinc-500"><?php echo count($sessions); ?> sessão(ões) ativa(s)</p>
                        </div>
                        <?php if (count($sessions) > 1): ?>
                            <form method="POST" onsubmit="return confirm('Tem certeza que deseja encerrar todas as outras sessões? Você precisará fazer login novamente nesses dispositivos.');">
                                <?php echo csrf_field(); ?>
                                <button type="submit" name="terminate_all" class="px-4 py-2 bg-red-500/20 text-red-400 rounded-lg text-sm font-medium hover:bg-red-500/30 transition-all border border-red-500/30 flex items-center gap-2">
                                    <i data-lucide="log-out" class="w-4 h-4"></i>
                                    Encerrar Todas
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Lista de Sessões -->
                <div class="space-y-4">
                    <?php if (empty($sessions)): ?>
                        <div class="glass-card rounded-2xl p-8 text-center">
                            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-zinc-800/50 flex items-center justify-center">
                                <i data-lucide="monitor-off" class="w-8 h-8 text-zinc-500"></i>
                            </div>
                            <p class="text-zinc-400">Nenhuma sessão ativa encontrada</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($sessions as $session): ?>
                            <?php
                            $isCurrent = $session['session_token'] === $currentSessionToken;
                            $deviceIcon = $session['device_type'] === 'mobile' ? 'smartphone' : ($session['device_type'] === 'tablet' ? 'tablet' : 'monitor');
                            $lastActivity = new DateTime($session['last_activity']);
                            $now = new DateTime();
                            $diff = $now->diff($lastActivity);
                            
                            if ($diff->days > 0) {
                                $timeAgo = $diff->days . ' dia(s) atrás';
                            } elseif ($diff->h > 0) {
                                $timeAgo = $diff->h . ' hora(s) atrás';
                            } elseif ($diff->i > 0) {
                                $timeAgo = $diff->i . ' minuto(s) atrás';
                            } else {
                                $timeAgo = 'Agora';
                            }
                            ?>
                            <div class="glass-card rounded-2xl p-6 <?php echo $isCurrent ? 'border-2 border-blue-500/30' : ''; ?>">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex items-start gap-4 flex-1">
                                        <div class="w-12 h-12 rounded-xl bg-blue-600/20 flex items-center justify-center flex-shrink-0">
                                            <i data-lucide="<?php echo $deviceIcon; ?>" class="w-6 h-6 text-blue-400"></i>
                                        </div>
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2 mb-1">
                                                <h4 class="text-base font-semibold text-white">
                                                    <?php echo htmlspecialchars($session['browser']); ?> em <?php echo htmlspecialchars($session['os']); ?>
                                                </h4>
                                                <?php if ($isCurrent): ?>
                                                    <span class="px-2 py-0.5 bg-blue-600/20 text-blue-400 text-xs font-semibold rounded border border-blue-600/30">
                                                        Atual
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="space-y-1.5 text-sm text-zinc-400">
                                                <p class="flex items-center gap-2">
                                                    <i data-lucide="map-pin" class="w-3.5 h-3.5"></i>
                                                    IP: <?php echo htmlspecialchars($session['ip_address']); ?>
                                                </p>
                                                <p class="flex items-center gap-2">
                                                    <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                                                    Última atividade: <?php echo $timeAgo; ?>
                                                </p>
                                                <p class="flex items-center gap-2">
                                                    <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
                                                    Conectado em: <?php echo date('d/m/Y H:i', strtotime($session['created_at'])); ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <?php if (!$isCurrent): ?>
                                        <form method="POST" onsubmit="return confirm('Encerrar esta sessão?');">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="session_token" value="<?php echo htmlspecialchars($session['session_token']); ?>">
                                            <button type="submit" name="terminate_session" class="px-4 py-2 bg-red-500/20 text-red-400 rounded-lg text-sm font-medium hover:bg-red-500/30 transition-all border border-red-500/30 flex items-center gap-2">
                                                <i data-lucide="x-circle" class="w-4 h-4"></i>
                                                Encerrar
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Informações de Segurança -->
                <div class="glass-card rounded-2xl p-6">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-lg bg-blue-600/20 flex items-center justify-center flex-shrink-0">
                            <i data-lucide="shield-check" class="w-5 h-5 text-blue-400"></i>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-white mb-2">Dicas de Segurança</h4>
                            <ul class="space-y-2 text-xs text-zinc-400">
                                <li class="flex items-start gap-2">
                                    <i data-lucide="check" class="w-3.5 h-3.5 text-blue-400 mt-0.5 flex-shrink-0"></i>
                                    <span>Encerre sessões de dispositivos que você não reconhece</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <i data-lucide="check" class="w-3.5 h-3.5 text-blue-400 mt-0.5 flex-shrink-0"></i>
                                    <span>Use sempre redes seguras ao acessar sua conta</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <i data-lucide="check" class="w-3.5 h-3.5 text-blue-400 mt-0.5 flex-shrink-0"></i>
                                    <span>Ative notificações de login para ser alertado sobre novos acessos</span>
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


