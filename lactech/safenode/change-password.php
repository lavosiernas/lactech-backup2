<?php
/**
 * SafeNode - Alterar Senha
 * Página segura para alteração de senha exigindo senha atual
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

$db = getSafeNodeDatabase();

$message = '';
$messageType = '';

// Processar alteração de senha
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    // SEGURANÇA: Validar CSRF token
    if (!CSRFProtection::validate()) {
        $message = 'Token de segurança inválido. Recarregue a página e tente novamente.';
        $messageType = 'error';
    } else {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validações
        if (empty($currentPassword)) {
            $message = 'Por favor, insira sua senha atual.';
            $messageType = 'error';
        } elseif (empty($newPassword) || empty($confirmPassword)) {
            $message = 'Por favor, preencha todos os campos.';
            $messageType = 'error';
        } elseif ($newPassword !== $confirmPassword) {
            $message = 'As senhas não coincidem.';
            $messageType = 'error';
        } elseif (!InputValidator::strongPassword($newPassword)) {
            $message = 'A senha deve ter no mínimo 8 caracteres, incluindo letras maiúsculas, minúsculas, números e símbolos.';
            $messageType = 'error';
        } else {
            // Verificar senha atual
            if ($db) {
                try {
                    $userId = $_SESSION['safenode_user_id'] ?? null;
                    $stmt = $db->prepare("SELECT password_hash FROM safenode_users WHERE id = ?");
                    $stmt->execute([$userId]);
                    $user = $stmt->fetch();
                    
                    if ($user && password_verify($currentPassword, $user['password_hash'])) {
                        // Senha atual correta, atualizar para nova senha
                        $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                        $stmt = $db->prepare("UPDATE safenode_users SET password_hash = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                        $stmt->execute([$newPasswordHash, $userId]);
                        
                        $message = 'Senha alterada com sucesso!';
                        $messageType = 'success';
                    } else {
                        $message = 'Senha atual incorreta.';
                        $messageType = 'error';
                    }
                } catch (PDOException $e) {
                    error_log("SafeNode Change Password Error: " . $e->getMessage());
                    $message = 'Erro ao alterar senha. Tente novamente.';
                    $messageType = 'error';
                }
            }
        }
    }
}

$username = $_SESSION['safenode_username'] ?? 'Usuário';
?>
<!DOCTYPE html>
<html lang="pt-BR" class="dark h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alterar Senha - SafeNode</title>
    <link rel="icon" type="image/png" href="assets/img/logos (6).png">
    <link rel="shortcut icon" type="image/png" href="assets/img/logos (6).png">
    <link rel="apple-touch-icon" href="assets/img/logos (6).png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
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
                    <h2 class="text-xl font-bold text-white tracking-tight">Alterar Senha</h2>
                    <p class="text-xs text-zinc-400 mt-0.5">Atualize sua senha de forma segura</p>
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

                <!-- Formulário de Alteração de Senha -->
                <div class="glass-card rounded-2xl p-6 md:p-8">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-12 h-12 rounded-xl bg-blue-600/20 flex items-center justify-center">
                            <i data-lucide="key" class="w-6 h-6 text-blue-400"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-white">Alterar Senha</h3>
                            <p class="text-sm text-zinc-500">Insira sua senha atual e defina uma nova senha</p>
                        </div>
                    </div>

                    <form method="POST" id="changePasswordForm" class="space-y-5">
                        <?php echo csrf_field(); ?>
                        
                        <div>
                            <label class="block text-sm font-semibold text-zinc-300 mb-2 flex items-center gap-2">
                                <i data-lucide="lock" class="w-4 h-4"></i>
                                Senha Atual
                            </label>
                            <div class="relative">
                                <input type="password" id="current_password" name="current_password" required 
                                       class="w-full px-4 py-3 pr-12 border border-white/10 rounded-xl bg-zinc-900/30 text-white placeholder:text-zinc-600 focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all" 
                                       placeholder="Digite sua senha atual">
                                <button type="button" onclick="togglePassword('current_password')" class="absolute right-3 top-1/2 -translate-y-1/2 text-zinc-500 hover:text-white transition-colors">
                                    <i data-lucide="eye" class="w-5 h-5"></i>
                                </button>
                            </div>
                        </div>

                        <div class="pt-4 border-t border-white/5">
                            <h4 class="text-sm font-bold text-white mb-4 flex items-center gap-2">
                                <i data-lucide="shield-check" class="w-4 h-4 text-blue-400"></i>
                                Nova Senha
                            </h4>
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-semibold text-zinc-300 mb-2">
                                        Nova Senha
                                    </label>
                                    <div class="relative">
                                        <input type="password" id="new_password" name="new_password" required 
                                               class="w-full px-4 py-3 pr-12 border border-white/10 rounded-xl bg-zinc-900/30 text-white placeholder:text-zinc-600 focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all" 
                                               placeholder="Digite sua nova senha">
                                        <button type="button" onclick="togglePassword('new_password')" class="absolute right-3 top-1/2 -translate-y-1/2 text-zinc-500 hover:text-white transition-colors">
                                            <i data-lucide="eye" class="w-5 h-5"></i>
                                        </button>
                                    </div>
                                    <div class="mt-3 space-y-2">
                                        <p class="text-xs text-zinc-500 flex items-center gap-2">
                                            <span id="length-check" class="w-2 h-2 rounded-full bg-zinc-700"></span>
                                            Mínimo de 8 caracteres
                                        </p>
                                        <p class="text-xs text-zinc-500 flex items-center gap-2">
                                            <span id="upper-check" class="w-2 h-2 rounded-full bg-zinc-700"></span>
                                            Letras maiúsculas e minúsculas
                                        </p>
                                        <p class="text-xs text-zinc-500 flex items-center gap-2">
                                            <span id="number-check" class="w-2 h-2 rounded-full bg-zinc-700"></span>
                                            Números e símbolos
                                        </p>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-zinc-300 mb-2">
                                        Confirmar Nova Senha
                                    </label>
                                    <div class="relative">
                                        <input type="password" id="confirm_password" name="confirm_password" required 
                                               class="w-full px-4 py-3 pr-12 border border-white/10 rounded-xl bg-zinc-900/30 text-white placeholder:text-zinc-600 focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all" 
                                               placeholder="Confirme sua nova senha">
                                        <button type="button" onclick="togglePassword('confirm_password')" class="absolute right-3 top-1/2 -translate-y-1/2 text-zinc-500 hover:text-white transition-colors">
                                            <i data-lucide="eye" class="w-5 h-5"></i>
                                        </button>
                                    </div>
                                    <p id="match-message" class="mt-2 text-xs hidden"></p>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col-reverse sm:flex-row justify-between items-center gap-3 pt-6 border-t border-white/10">
                            <a href="forgot-password.php" class="text-zinc-400 hover:text-blue-400 text-sm font-medium flex items-center gap-2 transition-colors">
                                <i data-lucide="help-circle" class="w-4 h-4"></i>
                                Esqueceu sua senha?
                            </a>
                            <div class="flex flex-col-reverse sm:flex-row gap-3 w-full sm:w-auto">
                                <a href="profile.php" class="px-6 py-3 border border-white/10 text-white rounded-xl hover:bg-white/5 font-semibold transition-all text-center">
                                    Cancelar
                                </a>
                                <button type="submit" name="change_password" class="px-6 py-3 bg-blue-600 text-white rounded-xl hover:bg-blue-700 font-semibold transition-all shadow-lg shadow-blue-500/20 flex items-center justify-center gap-2">
                                    <i data-lucide="check" class="w-4 h-4"></i>
                                    Alterar Senha
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Dicas de Segurança -->
                <div class="glass-card rounded-2xl p-6">
                    <div class="flex items-start gap-3 mb-4">
                        <div class="w-10 h-10 rounded-lg bg-blue-600/20 flex items-center justify-center flex-shrink-0">
                            <i data-lucide="shield-alert" class="w-5 h-5 text-blue-400"></i>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-white mb-2">Dicas de Segurança</h4>
                            <ul class="space-y-2 text-xs text-zinc-400">
                                <li class="flex items-start gap-2">
                                    <i data-lucide="check" class="w-3.5 h-3.5 text-blue-400 mt-0.5 flex-shrink-0"></i>
                                    <span>Use uma senha única que você não utiliza em outros sites</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <i data-lucide="check" class="w-3.5 h-3.5 text-blue-400 mt-0.5 flex-shrink-0"></i>
                                    <span>Misture letras maiúsculas, minúsculas, números e símbolos</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <i data-lucide="check" class="w-3.5 h-3.5 text-blue-400 mt-0.5 flex-shrink-0"></i>
                                    <span>Evite informações pessoais como datas de nascimento ou nomes</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <i data-lucide="check" class="w-3.5 h-3.5 text-blue-400 mt-0.5 flex-shrink-0"></i>
                                    <span>Considere usar um gerenciador de senhas</span>
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

        // Toggle Password Visibility
        function togglePassword(fieldId) {
            const input = document.getElementById(fieldId);
            const icon = input.nextElementSibling.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.setAttribute('data-lucide', 'eye-off');
            } else {
                input.type = 'password';
                icon.setAttribute('data-lucide', 'eye');
            }
            
            lucide.createIcons();
        }

        // Password Validation
        const newPassword = document.getElementById('new_password');
        const confirmPassword = document.getElementById('confirm_password');
        const matchMessage = document.getElementById('match-message');
        const lengthCheck = document.getElementById('length-check');
        const upperCheck = document.getElementById('upper-check');
        const numberCheck = document.getElementById('number-check');

        if (newPassword) {
            newPassword.addEventListener('input', function() {
                const password = this.value;
                
                // Length check (min 8)
                if (password.length >= 8) {
                    lengthCheck.classList.remove('bg-zinc-700');
                    lengthCheck.classList.add('bg-emerald-500');
                } else {
                    lengthCheck.classList.remove('bg-emerald-500');
                    lengthCheck.classList.add('bg-zinc-700');
                }
                
                // Upper and lowercase check
                if (/[a-z]/.test(password) && /[A-Z]/.test(password)) {
                    upperCheck.classList.remove('bg-zinc-700');
                    upperCheck.classList.add('bg-emerald-500');
                } else {
                    upperCheck.classList.remove('bg-emerald-500');
                    upperCheck.classList.add('bg-zinc-700');
                }
                
                // Number and symbol check
                if (/\d/.test(password) && /[!@#$%^&*(),.?":{}|<>]/.test(password)) {
                    numberCheck.classList.remove('bg-zinc-700');
                    numberCheck.classList.add('bg-emerald-500');
                } else {
                    numberCheck.classList.remove('bg-emerald-500');
                    numberCheck.classList.add('bg-zinc-700');
                }
                
                checkPasswordMatch();
            });
        }

        if (confirmPassword) {
            confirmPassword.addEventListener('input', checkPasswordMatch);
        }

        function checkPasswordMatch() {
            if (confirmPassword && newPassword) {
                if (confirmPassword.value && newPassword.value) {
                    if (confirmPassword.value === newPassword.value) {
                        matchMessage.textContent = '✓ As senhas coincidem';
                        matchMessage.className = 'mt-2 text-xs text-emerald-400 flex items-center gap-1';
                        matchMessage.classList.remove('hidden');
                    } else {
                        matchMessage.textContent = '✗ As senhas não coincidem';
                        matchMessage.className = 'mt-2 text-xs text-red-400 flex items-center gap-1';
                        matchMessage.classList.remove('hidden');
                    }
                } else {
                    matchMessage.classList.add('hidden');
                }
            }
        }

        // Form Validation
        const changePasswordForm = document.getElementById('changePasswordForm');
        if (changePasswordForm) {
            changePasswordForm.addEventListener('submit', function(e) {
                const newPass = newPassword.value;
                const confirmPass = confirmPassword.value;
                
                if (newPass !== confirmPass) {
                    e.preventDefault();
                    alert('As senhas não coincidem!');
                    return false;
                }
                
                if (newPass.length < 8) {
                    e.preventDefault();
                    alert('A senha deve ter no mínimo 8 caracteres!');
                    return false;
                }
                
                if (!/[a-z]/.test(newPass) || !/[A-Z]/.test(newPass)) {
                    e.preventDefault();
                    alert('A senha deve conter letras maiúsculas e minúsculas!');
                    return false;
                }
                
                if (!/\d/.test(newPass) || !/[!@#$%^&*(),.?":{}|<>]/.test(newPass)) {
                    e.preventDefault();
                    alert('A senha deve conter números e símbolos!');
                    return false;
                }
            });
        }
    </script>
</body>
</html>


