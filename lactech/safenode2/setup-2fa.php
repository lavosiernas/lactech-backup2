<?php
/**
 * SafeNode - Configuração de 2FA
 * Página para ativar/desativar autenticação de dois fatores
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
require_once __DIR__ . '/includes/TwoFactorAuth.php';
require_once __DIR__ . '/includes/ActivityLogger.php';

$db = getSafeNodeDatabase();
$userId = $_SESSION['safenode_user_id'] ?? null;
$username = $_SESSION['safenode_username'] ?? 'Usuário';

$twoFactor = new TwoFactorAuth($db);
$activityLogger = new ActivityLogger($db);

$message = '';
$messageType = '';
$step = 'status'; // status, setup, verify, backup
$qrCodeUrl = '';
$secretKey = '';
$backupCodes = [];

// Buscar status atual
$status = $twoFactor->getStatus($userId);

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRFProtection::validate()) {
        $message = 'Token de segurança inválido.';
        $messageType = 'error';
    } else {
        if (isset($_POST['start_setup'])) {
            // Iniciar configuração
            $result = $twoFactor->startSetup($userId, $username);
            if ($result['success']) {
                $qrCodeUrl = $result['qr_code_url'];
                $secretKey = $result['secret_key'];
                $backupCodes = $result['backup_codes'];
                $step = 'setup';
            } else {
                $message = $result['error'] ?? 'Erro ao iniciar configuração';
                $messageType = 'error';
            }
        } elseif (isset($_POST['verify_code'])) {
            // Verificar código e ativar
            $code = $_POST['code'] ?? '';
            if (empty($code) || strlen($code) !== 6) {
                $message = 'Por favor, digite um código de 6 dígitos.';
                $messageType = 'error';
            } else {
                $result = $twoFactor->verifyAndActivate($userId, $code);
                if ($result['success']) {
                    $backupCodes = $result['backup_codes'];
                    $step = 'backup';
                    $activityLogger->log($userId, '2fa_enabled', 'Autenticação de dois fatores ativada', 'success');
                    $message = '2FA ativado com sucesso!';
                    $messageType = 'success';
                } else {
                    $message = $result['error'] ?? 'Código inválido';
                    $messageType = 'error';
                    // Manter step em setup para tentar novamente
                    $setupResult = $twoFactor->startSetup($userId, $username);
                    if ($setupResult['success']) {
                        $qrCodeUrl = $setupResult['qr_code_url'];
                        $secretKey = $setupResult['secret_key'];
                        $step = 'setup';
                    }
                }
            }
        } elseif (isset($_POST['disable_2fa'])) {
            // Desativar 2FA
            if ($twoFactor->disable($userId)) {
                $activityLogger->log($userId, '2fa_disabled', 'Autenticação de dois fatores desativada', 'success');
                $message = '2FA desativado com sucesso.';
                $messageType = 'success';
                $status = $twoFactor->getStatus($userId);
                $step = 'status';
            } else {
                $message = 'Erro ao desativar 2FA.';
                $messageType = 'error';
            }
        } elseif (isset($_POST['regenerate_backup'])) {
            // Regenerar códigos de backup
            $newCodes = $twoFactor->regenerateBackupCodes($userId);
            if (!empty($newCodes)) {
                $backupCodes = $newCodes;
                $step = 'backup';
                $message = 'Novos códigos de backup gerados!';
                $messageType = 'success';
            } else {
                $message = 'Erro ao gerar novos códigos.';
                $messageType = 'error';
            }
        }
    }
}

// Buscar backup codes se já estiver ativado
if ($status['enabled']) {
    $backupCodes = $twoFactor->getBackupCodes($userId);
}
?>
<!DOCTYPE html>
<html lang="pt-BR" class="dark h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Autenticação de Dois Fatores - SafeNode</title>
    <link rel="icon" type="image/png" href="assets/img/logos (6).png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #3f3f46; border-radius: 3px; }
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
                    <h2 class="text-xl font-bold text-white tracking-tight">Autenticação de Dois Fatores</h2>
                    <p class="text-xs text-zinc-400 mt-0.5">Proteja sua conta com 2FA</p>
                </div>
                <a href="profile.php" class="text-zinc-400 hover:text-white transition-colors flex items-center gap-2">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                    <span class="text-sm">Voltar</span>
                </a>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-4 md:p-8 z-10">
            <div class="max-w-2xl mx-auto space-y-6">
                <?php if ($message): ?>
                    <div class="p-4 rounded-xl <?php 
                        echo $messageType === 'success' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 
                            'bg-red-500/10 text-red-400 border border-red-500/20'; 
                    ?> font-medium flex items-start gap-3">
                        <i data-lucide="<?php echo $messageType === 'success' ? 'check-circle' : 'alert-circle'; ?>" class="w-5 h-5 mt-0.5 flex-shrink-0"></i>
                        <span><?php echo htmlspecialchars($message); ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($step === 'status'): ?>
                    <!-- Status do 2FA -->
                    <div class="glass-card rounded-2xl p-6 md:p-8">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="w-12 h-12 rounded-xl <?php echo $status['enabled'] ? 'bg-emerald-600/20' : 'bg-zinc-800/50'; ?> flex items-center justify-center">
                                <i data-lucide="<?php echo $status['enabled'] ? 'shield-check' : 'shield-off'; ?>" class="w-6 h-6 <?php echo $status['enabled'] ? 'text-emerald-400' : 'text-zinc-400'; ?>"></i>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-xl font-bold text-white">Status do 2FA</h3>
                                <p class="text-sm text-zinc-500">
                                    <?php echo $status['enabled'] ? '2FA está ativo na sua conta' : '2FA não está configurado'; ?>
                                </p>
                            </div>
                        </div>

                        <?php if ($status['enabled']): ?>
                            <div class="space-y-4">
                                <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20">
                                    <p class="text-sm text-emerald-400 flex items-start gap-2">
                                        <i data-lucide="check-circle" class="w-4 h-4 mt-0.5 flex-shrink-0"></i>
                                        <span>Sua conta está protegida com autenticação de dois fatores. Você precisará inserir um código do seu aplicativo autenticador toda vez que fizer login.</span>
                                    </p>
                                </div>

                                <?php if ($status['last_used_at']): ?>
                                    <div class="text-sm text-zinc-400">
                                        <p>Último uso: <?php echo date('d/m/Y H:i', strtotime($status['last_used_at'])); ?></p>
                                    </div>
                                <?php endif; ?>

                                <div class="flex gap-3 pt-4">
                                    <form method="POST" onsubmit="return confirm('Tem certeza que deseja desativar o 2FA? Sua conta ficará menos segura.');" class="flex-1">
                                        <?php echo csrf_field(); ?>
                                        <button type="submit" name="disable_2fa" class="w-full px-6 py-3 border border-red-500/30 text-red-400 rounded-xl hover:bg-red-500/10 font-semibold transition-all">
                                            Desativar 2FA
                                        </button>
                                    </form>
                                    <form method="POST" class="flex-1">
                                        <?php echo csrf_field(); ?>
                                        <button type="submit" name="regenerate_backup" class="w-full px-6 py-3 bg-blue-600 text-white rounded-xl hover:bg-blue-700 font-semibold transition-all shadow-lg shadow-blue-500/20">
                                            Ver Códigos de Backup
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="space-y-4">
                                <div class="p-4 rounded-xl bg-blue-500/10 border border-blue-500/20">
                                    <h4 class="text-sm font-bold text-white mb-2">O que é 2FA?</h4>
                                    <p class="text-xs text-zinc-400 mb-3">
                                        A autenticação de dois fatores adiciona uma camada extra de segurança à sua conta. Além da senha, você precisará inserir um código gerado pelo seu aplicativo autenticador (como Google Authenticator ou Authy).
                                    </p>
                                    <ul class="text-xs text-zinc-400 space-y-1">
                                        <li class="flex items-start gap-2">
                                            <i data-lucide="check" class="w-3.5 h-3.5 text-blue-400 mt-0.5 flex-shrink-0"></i>
                                            <span>Proteção contra acesso não autorizado</span>
                                        </li>
                                        <li class="flex items-start gap-2">
                                            <i data-lucide="check" class="w-3.5 h-3.5 text-blue-400 mt-0.5 flex-shrink-0"></i>
                                            <span>Códigos que mudam a cada 30 segundos</span>
                                        </li>
                                        <li class="flex items-start gap-2">
                                            <i data-lucide="check" class="w-3.5 h-3.5 text-blue-400 mt-0.5 flex-shrink-0"></i>
                                            <span>Códigos de backup caso perca acesso ao app</span>
                                        </li>
                                    </ul>
                                </div>

                                <form method="POST">
                                    <?php echo csrf_field(); ?>
                                    <button type="submit" name="start_setup" class="w-full px-6 py-3 bg-blue-600 text-white rounded-xl hover:bg-blue-700 font-semibold transition-all shadow-lg shadow-blue-500/20 flex items-center justify-center gap-2">
                                        <i data-lucide="shield-check" class="w-5 h-5"></i>
                                        Ativar 2FA
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>

                <?php elseif ($step === 'setup'): ?>
                    <!-- Configuração do 2FA -->
                    <div class="glass-card rounded-2xl p-6 md:p-8">
                        <div class="mb-6">
                            <h3 class="text-xl font-bold text-white mb-2">Configurar 2FA</h3>
                            <p class="text-sm text-zinc-400">Escaneie o QR Code com seu aplicativo autenticador</p>
                        </div>

                        <div class="space-y-6">
                            <!-- QR Code -->
                            <div class="flex flex-col items-center">
                                <div class="mb-4 p-4 bg-white rounded-xl">
                                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=256x256&data=<?php echo urlencode($qrCodeUrl); ?>" 
                                         alt="QR Code para 2FA" 
                                         class="w-64 h-64"
                                         onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'256\' height=\'256\'%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\' fill=\'%23999\'%3EErro ao carregar QR Code%3C/text%3E%3C/svg%3E';">
                                </div>
                                <p class="text-xs text-zinc-500 text-center mb-4">
                                    Escaneie este código com:<br>
                                    <strong class="text-white">Google Authenticator</strong>, <strong class="text-white">Authy</strong>, ou outro app compatível
                                </p>
                                <div class="p-3 rounded-lg bg-zinc-900/50 border border-white/5">
                                    <p class="text-xs text-zinc-500 mb-1">Ou digite esta chave manualmente:</p>
                                    <code class="text-sm text-white font-mono break-all"><?php echo htmlspecialchars($secretKey); ?></code>
                                </div>
                            </div>

                            <!-- Verificar código -->
                            <form method="POST" class="space-y-4">
                                <?php echo csrf_field(); ?>
                                <div>
                                    <label class="block text-sm font-semibold text-zinc-300 mb-2">
                                        Código de Verificação
                                    </label>
                                    <input type="text" name="code" maxlength="6" pattern="[0-9]{6}" required
                                           class="w-full px-4 py-3 border border-white/10 rounded-xl bg-zinc-900/30 text-white text-center text-2xl tracking-widest font-mono placeholder:text-zinc-600 focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all" 
                                           placeholder="000000">
                                    <p class="mt-2 text-xs text-zinc-500 text-center">
                                        Digite o código de 6 dígitos do seu aplicativo autenticador
                                    </p>
                                </div>

                                <div class="flex gap-3">
                                    <a href="setup-2fa.php" class="flex-1 px-4 py-3 border border-white/10 text-white rounded-xl hover:bg-white/5 font-semibold transition-all text-center">
                                        Cancelar
                                    </a>
                                    <button type="submit" name="verify_code" class="flex-1 px-4 py-3 bg-blue-600 text-white rounded-xl hover:bg-blue-700 font-semibold transition-all shadow-lg shadow-blue-500/20">
                                        Verificar e Ativar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                <?php elseif ($step === 'backup'): ?>
                    <!-- Mostrar códigos de backup -->
                    <div class="glass-card rounded-2xl p-6 md:p-8 border border-yellow-500/30">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="w-12 h-12 rounded-xl bg-yellow-600/20 flex items-center justify-center">
                                <i data-lucide="key" class="w-6 h-6 text-yellow-400"></i>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-xl font-bold text-white">Códigos de Backup</h3>
                                <p class="text-sm text-zinc-500">Salve estes códigos em um lugar seguro</p>
                            </div>
                        </div>

                        <div class="p-4 rounded-xl bg-yellow-500/10 border border-yellow-500/20 mb-6">
                            <p class="text-sm text-yellow-400 flex items-start gap-2 mb-3">
                                <i data-lucide="alert-triangle" class="w-4 h-4 mt-0.5 flex-shrink-0"></i>
                                <span><strong>Importante:</strong> Estes códigos permitem acesso à sua conta caso você perca acesso ao seu aplicativo autenticador. Salve-os em um lugar seguro e não compartilhe com ninguém.</span>
                            </p>
                            <p class="text-xs text-zinc-400">
                                Cada código pode ser usado apenas uma vez. Após usar, ele será removido automaticamente.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-6">
                            <?php foreach ($backupCodes as $code): ?>
                                <div class="p-4 rounded-xl bg-zinc-900/50 border border-white/5">
                                    <code class="text-lg text-white font-mono tracking-wider"><?php echo htmlspecialchars($code); ?></code>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="flex gap-3">
                            <a href="profile.php" class="flex-1 px-4 py-3 border border-white/10 text-white rounded-xl hover:bg-white/5 font-semibold transition-all text-center">
                                Entendi, Salvei os Códigos
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        lucide.createIcons();

        // Auto-formatar código
        document.querySelector('input[name="code"]')?.addEventListener('input', function(e) {
            this.value = this.value.replace(/\D/g, '').slice(0, 6);
        });
    </script>
</body>
</html>

