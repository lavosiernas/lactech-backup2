<?php
/**
 * SafeNode - Perfil do Usuário
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

$message = '';
$messageType = '';

// Buscar dados do usuário
$username = $_SESSION['safenode_username'] ?? 'Admin';
$userInitial = strtoupper(substr($username, 0, 1));
$email = $_SESSION['safenode_email'] ?? '';

// Salvar alterações no perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    if ($db) {
        try {
            // Atualizar senha se fornecida
            if (!empty($_POST['new_password'])) {
                $newPassword = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE safenode_users SET password = ? WHERE username = ?");
                $stmt->execute([$newPassword, $username]);
                $message = "Senha atualizada com sucesso!";
                $messageType = "success";
            } else {
                $message = "Nenhuma alteração foi feita.";
                $messageType = "info";
            }
        } catch (PDOException $e) {
            $message = "Erro ao atualizar perfil: " . $e->getMessage();
            $messageType = "error";
        }
    }
}

// Buscar estatísticas do usuário
$userStats = [
    'total_sites' => 0,
    'total_logs' => 0,
    'total_blocks' => 0,
    'account_created' => date('Y-m-d')
];

if ($db) {
    try {
        // Contar sites
        $stmt = $db->query("SELECT COUNT(*) as total FROM safenode_sites");
        $result = $stmt->fetch();
        $userStats['total_sites'] = $result['total'] ?? 0;
        
        // Contar logs
        $stmt = $db->query("SELECT COUNT(*) as total FROM safenode_security_logs");
        $result = $stmt->fetch();
        $userStats['total_logs'] = $result['total'] ?? 0;
        
        // Contar bloqueios ativos
        $stmt = $db->query("SELECT COUNT(*) as total FROM v_safenode_active_blocks");
        $result = $stmt->fetch();
        $userStats['total_blocks'] = $result['total'] ?? 0;
    } catch (PDOException $e) {
        error_log("SafeNode Profile Stats Error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR" class="dark h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil - SafeNode</title>
    <link rel="icon" type="image/png" href="assets/img/logos (6).png">
    <link rel="shortcut icon" type="image/png" href="assets/img/logos (6).png">
    <link rel="apple-touch-icon" href="assets/img/logos (6).png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        mono: ['JetBrains Mono', 'monospace'],
                    }
                }
            }
        }
    </script>
    <style>
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #3f3f46; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #52525b; }
        .glass-card {
            background: linear-gradient(180deg, rgba(39, 39, 42, 0.4) 0%, rgba(24, 24, 27, 0.4) 100%);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
    </style>
</head>
<body class="bg-black text-zinc-200 font-sans h-full overflow-hidden flex">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="flex-1 flex flex-col h-full relative overflow-hidden bg-black">
        <header class="h-16 border-b border-white/5 bg-black/50 backdrop-blur-xl sticky top-0 z-40 px-6 flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-white tracking-tight">Perfil do Usuário</h2>
                <p class="text-xs text-zinc-400 mt-0.5">Gerencie suas informações pessoais</p>
            </div>
            <a href="dashboard.php" class="text-zinc-400 hover:text-white transition-colors flex items-center gap-2">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span class="text-sm">Voltar</span>
            </a>
        </header>

        <div class="flex-1 overflow-y-auto p-6 md:p-8 z-10">
            <div class="max-w-4xl mx-auto space-y-6">
                <?php if ($message): ?>
                    <div class="mb-6 p-4 rounded-xl <?php 
                        echo $messageType === 'success' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 
                            ($messageType === 'error' ? 'bg-red-500/10 text-red-400 border border-red-500/20' : 
                            'bg-blue-500/10 text-blue-400 border border-blue-500/20'); 
                    ?> font-semibold">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <!-- Informações do Perfil -->
                <div class="glass-card rounded-xl p-6">
                    <div class="flex items-center gap-6 mb-8">
                        <div class="w-24 h-24 rounded-full bg-gradient-to-br from-blue-600 to-purple-600 flex items-center justify-center text-white font-bold text-3xl shadow-lg shadow-blue-500/20">
                            <?php echo $userInitial; ?>
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold text-white mb-1"><?php echo htmlspecialchars($username); ?></h3>
                            <p class="text-zinc-400 text-sm">Administrador do Sistema</p>
                            <p class="text-zinc-500 text-xs mt-1">Membro desde <?php echo date('d/m/Y', strtotime($userStats['account_created'])); ?></p>
                        </div>
                    </div>

                    <!-- Estatísticas -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-8 pt-8 border-t border-white/10">
                        <div class="text-center">
                            <div class="text-3xl font-bold text-white mb-1"><?php echo number_format($userStats['total_sites']); ?></div>
                            <div class="text-xs text-zinc-500 uppercase tracking-wider">Sites Configurados</div>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl font-bold text-white mb-1"><?php echo number_format($userStats['total_logs']); ?></div>
                            <div class="text-xs text-zinc-500 uppercase tracking-wider">Logs Registrados</div>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl font-bold text-white mb-1"><?php echo number_format($userStats['total_blocks']); ?></div>
                            <div class="text-xs text-zinc-500 uppercase tracking-wider">IPs Bloqueados</div>
                        </div>
                    </div>
                </div>

                <!-- Configurações da Conta -->
                <div class="glass-card rounded-xl p-6">
                    <h3 class="text-lg font-bold text-white mb-6">Configurações da Conta</h3>
                    
                    <form method="POST" class="space-y-6">
                        <div>
                            <label class="block text-sm font-semibold text-zinc-300 mb-2">
                                Nome de Usuário
                            </label>
                            <input type="text" value="<?php echo htmlspecialchars($username); ?>" disabled class="w-full px-4 py-2.5 border border-white/10 rounded-xl bg-zinc-900/50 text-zinc-500 cursor-not-allowed">
                            <p class="mt-2 text-xs text-zinc-500">O nome de usuário não pode ser alterado</p>
                        </div>

                        <?php if ($email): ?>
                        <div>
                            <label class="block text-sm font-semibold text-zinc-300 mb-2">
                                E-mail
                            </label>
                            <input type="email" value="<?php echo htmlspecialchars($email); ?>" disabled class="w-full px-4 py-2.5 border border-white/10 rounded-xl bg-zinc-900/50 text-zinc-500 cursor-not-allowed">
                        </div>
                        <?php endif; ?>

                        <div>
                            <label class="block text-sm font-semibold text-zinc-300 mb-2">
                                Nova Senha
                            </label>
                            <input type="password" name="new_password" placeholder="Deixe em branco para não alterar" class="w-full px-4 py-2.5 border border-white/10 rounded-xl bg-zinc-900/50 text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                            <p class="mt-2 text-xs text-zinc-500">Mínimo de 8 caracteres</p>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-zinc-300 mb-2">
                                Confirmar Nova Senha
                            </label>
                            <input type="password" name="confirm_password" placeholder="Confirme a nova senha" class="w-full px-4 py-2.5 border border-white/10 rounded-xl bg-zinc-900/50 text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                        </div>

                        <div class="flex justify-end gap-4 pt-4 border-t border-white/10">
                            <a href="dashboard.php" class="px-6 py-2.5 border border-white/10 text-white rounded-xl hover:bg-white/5 font-semibold transition-all">
                                Cancelar
                            </a>
                            <button type="submit" name="update_profile" class="px-6 py-2.5 bg-blue-600 text-white rounded-xl hover:bg-blue-700 font-semibold transition-all shadow-lg shadow-blue-500/20">
                                Salvar Alterações
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Segurança -->
                <div class="glass-card rounded-xl p-6">
                    <h3 class="text-lg font-bold text-white mb-6">Segurança</h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between p-4 rounded-lg bg-zinc-900/50 border border-white/5">
                            <div>
                                <p class="text-sm font-semibold text-white">Autenticação de Dois Fatores</p>
                                <p class="text-xs text-zinc-500 mt-1">Adicione uma camada extra de segurança</p>
                            </div>
                            <button class="px-4 py-2 bg-zinc-800 text-zinc-400 rounded-lg text-sm font-medium hover:bg-zinc-700 transition-all">
                                Em breve
                            </button>
                        </div>
                        
                        <div class="flex items-center justify-between p-4 rounded-lg bg-zinc-900/50 border border-white/5">
                            <div>
                                <p class="text-sm font-semibold text-white">Sessões Ativas</p>
                                <p class="text-xs text-zinc-500 mt-1">Gerencie seus dispositivos conectados</p>
                            </div>
                            <button class="px-4 py-2 bg-zinc-800 text-zinc-400 rounded-lg text-sm font-medium hover:bg-zinc-700 transition-all">
                                Ver sessões
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Ações Perigosas -->
                <div class="glass-card rounded-xl p-6 border border-red-500/20">
                    <h3 class="text-lg font-bold text-red-400 mb-4">Zona Perigosa</h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between p-4 rounded-lg bg-red-500/10 border border-red-500/20">
                            <div>
                                <p class="text-sm font-semibold text-white">Encerrar Todas as Sessões</p>
                                <p class="text-xs text-zinc-500 mt-1">Desconecte-se de todos os dispositivos</p>
                            </div>
                            <button class="px-4 py-2 bg-red-500/20 text-red-400 rounded-lg text-sm font-medium hover:bg-red-500/30 transition-all border border-red-500/30">
                                Encerrar
                            </button>
                        </div>
                        
                        <div class="flex items-center justify-between p-4 rounded-lg bg-red-500/10 border border-red-500/20">
                            <div>
                                <p class="text-sm font-semibold text-white">Excluir Conta</p>
                                <p class="text-xs text-zinc-500 mt-1">Esta ação não pode ser desfeita</p>
                            </div>
                            <button class="px-4 py-2 bg-red-500/20 text-red-400 rounded-lg text-sm font-medium hover:bg-red-500/30 transition-all border border-red-500/30">
                                Excluir
                            </button>
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

