<?php
/**
 * SafeNode - Painel Administrativo de Envio de E-mails em Massa
 */

session_start();

// Autenticação básica - você pode melhorar isso depois
// Por segurança, apenas usuário admin pode acessar
if (!isset($_SESSION['safenode_admin_auth'])) {
    // Se não estiver autenticado, redirecionar para login
    // Por enquanto, vou usar uma senha simples
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_password'])) {
        // TODO: Coloque sua senha de admin aqui
        $adminPassword = 'safenode2024admin'; // MUDE ISSO!
        
        if ($_POST['admin_password'] === $adminPassword) {
            $_SESSION['safenode_admin_auth'] = true;
        } else {
            $errorAuth = 'Senha incorreta!';
        }
    }
    
    if (!isset($_SESSION['safenode_admin_auth'])) {
        ?>
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Admin - SafeNode</title>
            <script src="https://cdn.tailwindcss.com"></script>
            <script src="https://unpkg.com/lucide@latest"></script>
        </head>
        <body class="bg-black min-h-screen flex items-center justify-center p-4">
            <div class="w-full max-w-md">
                <div class="bg-zinc-900 rounded-2xl shadow-2xl border border-zinc-800 p-8">
                    <div class="text-center mb-6">
                        <i data-lucide="shield-check" class="w-16 h-16 mx-auto mb-4 text-white"></i>
                        <h1 class="text-2xl font-bold text-white mb-2">Admin SafeNode</h1>
                        <p class="text-slate-400 text-sm">Digite a senha de administrador</p>
                    </div>
                    
                    <?php if (isset($errorAuth)): ?>
                    <div class="mb-4 p-3 bg-red-500/10 border border-red-500/50 rounded-lg">
                        <p class="text-red-400 text-sm text-center"><?php echo $errorAuth; ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="mb-6">
                            <input type="password" name="admin_password" required
                                class="w-full px-4 py-3 bg-black border border-zinc-700 rounded-lg text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-white/20 focus:border-white"
                                placeholder="Senha de admin">
                        </div>
                        
                        <button type="submit" 
                            class="w-full py-3 bg-white text-black font-semibold rounded-lg hover:bg-slate-200 transition-colors">
                            Entrar
                        </button>
                    </form>
                </div>
            </div>
            
            <script>
                lucide.createIcons();
            </script>
        </body>
        </html>
        <?php
        exit;
    }
}

require_once __DIR__ . '/includes/config.php';

// Buscar total de usuários
try {
    $pdo = getSafeNodeDatabase();
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM safenode_users WHERE email_verified = 1");
    $result = $stmt->fetch();
    $totalUsers = $result['total'] ?? 0;
} catch (Exception $e) {
    $totalUsers = 0;
    error_log("Erro ao buscar total de usuários: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Envio de E-mails em Massa - SafeNode</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .pulse-animation {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: .5; }
        }
    </style>
</head>
<body class="bg-black min-h-screen text-white">
    <div class="max-w-4xl mx-auto p-6 md:p-8">
        <!-- Header -->
        <div class="flex items-center justify-between mb-8">
            <div class="flex items-center gap-3">
                <i data-lucide="mail" class="w-8 h-8 text-white"></i>
                <h1 class="text-2xl md:text-3xl font-bold">Envio de E-mails em Massa</h1>
            </div>
            <div class="flex items-center gap-3">
                <a href="email-preview.php" target="_blank"
                   class="px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors text-sm flex items-center gap-2">
                    <i data-lucide="eye" class="w-4 h-4"></i>
                    Visualizar E-mails
                </a>
                <a href="?logout=1" onclick="return confirm('Sair do painel admin?')" 
                   class="px-4 py-2 bg-zinc-800 hover:bg-zinc-700 rounded-lg transition-colors text-sm">
                    Sair
                </a>
            </div>
        </div>
        
        <?php if (isset($_GET['logout'])): session_destroy(); header('Location: admin-emails.php'); exit; endif; ?>
        
        <!-- Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-6">
                <div class="flex items-center gap-3 mb-2">
                    <i data-lucide="users" class="w-5 h-5 text-blue-400"></i>
                    <p class="text-slate-400 text-sm">Total de Usuários</p>
                </div>
                <p class="text-3xl font-bold"><?php echo number_format($totalUsers, 0, ',', '.'); ?></p>
            </div>
            
            <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-6">
                <div class="flex items-center gap-3 mb-2">
                    <i data-lucide="check-circle" class="w-5 h-5 text-green-400"></i>
                    <p class="text-slate-400 text-sm">E-mails Verificados</p>
                </div>
                <p class="text-3xl font-bold"><?php echo number_format($totalUsers, 0, ',', '.'); ?></p>
            </div>
            
            <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-6">
                <div class="flex items-center gap-3 mb-2">
                    <i data-lucide="send" class="w-5 h-5 text-purple-400"></i>
                    <p class="text-slate-400 text-sm">Prontos para Envio</p>
                </div>
                <p class="text-3xl font-bold"><?php echo number_format($totalUsers, 0, ',', '.'); ?></p>
            </div>
        </div>
        
        <!-- Área de Mensagens -->
        <div id="messageArea" class="mb-6 hidden">
            <!-- Mensagens serão exibidas aqui via JavaScript -->
        </div>
        
        <!-- Cards de Envio -->
        <div class="space-y-6">
            <!-- Card 1: Notificação de Manutenção -->
            <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-6 md:p-8">
                <div class="flex items-start gap-4">
                    <div class="p-3 bg-orange-500/10 rounded-xl">
                        <i data-lucide="alert-triangle" class="w-8 h-8 text-orange-400"></i>
                    </div>
                    
                    <div class="flex-1">
                        <h2 class="text-xl font-bold mb-2">Notificação de Manutenção</h2>
                        <p class="text-slate-400 mb-4">
                            Envia um e-mail informando que o sistema está em manutenção e ficará temporariamente indisponível.
                        </p>
                        
                        <div class="bg-black/50 border border-zinc-700 rounded-lg p-4 mb-4">
                            <p class="text-sm text-slate-300 mb-2"><strong>Assunto:</strong> Sistema em Manutenção - SafeNode</p>
                            <p class="text-sm text-slate-300"><strong>Conteúdo:</strong> Notifica sobre manutenção programada para melhorias de segurança</p>
                        </div>
                        
                        <button onclick="sendMassEmail('maintenance')" 
                                id="btnMaintenance"
                                class="px-6 py-3 bg-orange-500 hover:bg-orange-600 text-white font-semibold rounded-lg transition-all flex items-center gap-2">
                            <i data-lucide="send" class="w-4 h-4"></i>
                            Enviar para <?php echo number_format($totalUsers, 0, ',', '.'); ?> usuários
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Card 2: Sistema Reativado -->
            <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-6 md:p-8">
                <div class="flex items-start gap-4">
                    <div class="p-3 bg-green-500/10 rounded-xl">
                        <i data-lucide="check-circle-2" class="w-8 h-8 text-green-400"></i>
                    </div>
                    
                    <div class="flex-1">
                        <h2 class="text-xl font-bold mb-2">Sistema Reativado</h2>
                        <p class="text-slate-400 mb-4">
                            Envia um e-mail informando que a manutenção foi concluída e o sistema está novamente operacional.
                        </p>
                        
                        <div class="bg-black/50 border border-zinc-700 rounded-lg p-4 mb-4">
                            <p class="text-sm text-slate-300 mb-2"><strong>Assunto:</strong> Sistema Online - SafeNode</p>
                            <p class="text-sm text-slate-300"><strong>Conteúdo:</strong> Informa que as melhorias foram aplicadas e o sistema está disponível</p>
                        </div>
                        
                        <button onclick="sendMassEmail('online')" 
                                id="btnOnline"
                                class="px-6 py-3 bg-green-500 hover:bg-green-600 text-white font-semibold rounded-lg transition-all flex items-center gap-2">
                            <i data-lucide="send" class="w-4 h-4"></i>
                            Enviar para <?php echo number_format($totalUsers, 0, ',', '.'); ?> usuários
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Progresso de Envio -->
        <div id="progressContainer" class="hidden mt-8 bg-zinc-900 border border-zinc-800 rounded-xl p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-3 h-3 bg-blue-400 rounded-full pulse-animation"></div>
                <h3 class="text-lg font-semibold">Enviando e-mails...</h3>
            </div>
            
            <div class="mb-4">
                <div class="flex justify-between text-sm mb-2">
                    <span id="progressText" class="text-slate-400">Preparando envio...</span>
                    <span id="progressPercent" class="text-white font-semibold">0%</span>
                </div>
                <div class="w-full bg-zinc-800 rounded-full h-2">
                    <div id="progressBar" class="bg-blue-500 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                </div>
            </div>
            
            <p class="text-xs text-slate-500">
                <i data-lucide="info" class="w-3 h-3 inline"></i>
                Este processo pode levar alguns minutos. Não feche esta página.
            </p>
        </div>
    </div>
    
    <script>
        lucide.createIcons();
        
        function showMessage(message, type = 'success') {
            const messageArea = document.getElementById('messageArea');
            const bgColor = type === 'success' ? 'bg-green-500/10 border-green-500/50 text-green-400' : 'bg-red-500/10 border-red-500/50 text-red-400';
            const icon = type === 'success' ? 'check-circle' : 'alert-circle';
            
            messageArea.className = `mb-6 p-4 ${bgColor} border rounded-xl flex items-center gap-3`;
            messageArea.innerHTML = `
                <i data-lucide="${icon}" class="w-5 h-5"></i>
                <p class="flex-1">${message}</p>
                <button onclick="this.parentElement.classList.add('hidden')" class="text-current opacity-70 hover:opacity-100">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            `;
            messageArea.classList.remove('hidden');
            lucide.createIcons();
            
            // Auto-hide após 10 segundos se for sucesso
            if (type === 'success') {
                setTimeout(() => {
                    messageArea.classList.add('hidden');
                }, 10000);
            }
        }
        
        function updateProgress(current, total, text) {
            const percent = Math.round((current / total) * 100);
            document.getElementById('progressBar').style.width = percent + '%';
            document.getElementById('progressPercent').textContent = percent + '%';
            document.getElementById('progressText').textContent = text;
        }
        
        async function sendMassEmail(type) {
            const btnMaintenance = document.getElementById('btnMaintenance');
            const btnOnline = document.getElementById('btnOnline');
            const progressContainer = document.getElementById('progressContainer');
            
            // Confirmação
            const typeName = type === 'maintenance' ? 'Manutenção' : 'Sistema Reativado';
            if (!confirm(`Tem certeza que deseja enviar o e-mail "${typeName}" para todos os usuários?`)) {
                return;
            }
            
            // Desabilitar botões
            btnMaintenance.disabled = true;
            btnOnline.disabled = true;
            btnMaintenance.classList.add('opacity-50', 'cursor-not-allowed');
            btnOnline.classList.add('opacity-50', 'cursor-not-allowed');
            
            // Mostrar progresso
            progressContainer.classList.remove('hidden');
            updateProgress(0, 100, 'Iniciando envio...');
            
            try {
                // Fazer requisição AJAX
                const formData = new FormData();
                formData.append('type', type);
                
                const response = await fetch('send-mass-email.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    updateProgress(100, 100, 'Envio concluído!');
                    showMessage(`✅ E-mails enviados com sucesso! ${result.sent} de ${result.total} enviados.`, 'success');
                    
                    // Ocultar progresso após 3 segundos
                    setTimeout(() => {
                        progressContainer.classList.add('hidden');
                    }, 3000);
                } else {
                    throw new Error(result.error || 'Erro desconhecido ao enviar e-mails');
                }
            } catch (error) {
                console.error('Erro:', error);
                showMessage(`❌ Erro ao enviar e-mails: ${error.message}`, 'error');
                progressContainer.classList.add('hidden');
            } finally {
                // Reabilitar botões
                btnMaintenance.disabled = false;
                btnOnline.disabled = false;
                btnMaintenance.classList.remove('opacity-50', 'cursor-not-allowed');
                btnOnline.classList.remove('opacity-50', 'cursor-not-allowed');
            }
        }
    </script>
</body>
</html>

