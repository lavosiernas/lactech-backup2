<?php
/**
 * SafeNode - Perfil do Usuário
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
require_once __DIR__ . '/includes/init.php';
// 2FA removido - require_once __DIR__ . '/includes/TwoFactorAuth.php';

$db = getSafeNodeDatabase();

$message = '';
$messageType = '';

// 2FA removido - Verificar status do 2FA
// $twoFactor = new TwoFactorAuth($db);
// $twoFactorStatus = $twoFactor->getStatus($_SESSION['safenode_user_id'] ?? null);
$twoFactorStatus = ['enabled' => false];

// Buscar dados do usuário
$username = $_SESSION['safenode_username'] ?? 'Admin';
$userInitial = strtoupper(substr($username, 0, 1));
$email = $_SESSION['safenode_email'] ?? '';
$fullName = $_SESSION['safenode_full_name'] ?? '';
$userId = $_SESSION['safenode_user_id'] ?? null;

// Buscar dados atualizados do banco
$avatarUrl = null;
if ($db && $userId) {
    try {
        $stmt = $db->prepare("SELECT username, full_name, email, created_at, avatar_url FROM safenode_users WHERE id = ?");
        $stmt->execute([$userId]);
        $userData = $stmt->fetch();
        if ($userData) {
            $username = $userData['username'] ?? $username;
            $fullName = $userData['full_name'] ?? $fullName;
            $email = $userData['email'] ?? $email;
            $avatarUrl = $userData['avatar_url'] ?? null;
            $userStats['account_created'] = $userData['created_at'] ?? date('Y-m-d');
            $userInitial = strtoupper(substr($username, 0, 1));
        }
    } catch (PDOException $e) {
        error_log("SafeNode Profile Error: " . $e->getMessage());
    }
}

// Salvar alterações no perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // SEGURANÇA: Validar CSRF token
    if (!CSRFProtection::validate()) {
        $message = "Token de segurança inválido. Recarregue a página e tente novamente.";
        $messageType = "error";
    } else {
        if ($db) {
            try {
                $userId = $_SESSION['safenode_user_id'] ?? null;
                $newUsername = XSSProtection::sanitize($_POST['username'] ?? '');
                $newFullName = XSSProtection::sanitize($_POST['full_name'] ?? '');
                
                // Validação do username
                if (empty($newUsername)) {
                    $message = "O nome de usuário não pode estar vazio.";
                    $messageType = "error";
                } elseif (!InputValidator::username($newUsername)) {
                    $message = "Nome de usuário inválido. Use apenas letras, números e _ (mín. 3 caracteres).";
                    $messageType = "error";
                } elseif (empty($newFullName)) {
                    $message = "O nome completo não pode estar vazio.";
                    $messageType = "error";
                } else {
                    // Username pode ser duplicado, então não precisa verificar
                        // Atualizar username e nome completo
                        $stmt = $db->prepare("UPDATE safenode_users SET username = ?, full_name = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                        $stmt->execute([$newUsername, $newFullName, $userId]);
                        
                        // Atualizar sessão
                        $_SESSION['safenode_username'] = $newUsername;
                        $_SESSION['safenode_full_name'] = $newFullName;
                        
                        // Atualizar variável local para exibição
                        $username = $newUsername;
                        $userInitial = strtoupper(substr($username, 0, 1));
                        
                        $message = "Perfil atualizado com sucesso!";
                        $messageType = "success";
                }
            } catch (PDOException $e) {
                error_log("SafeNode Profile Update Error: " . $e->getMessage());
                $message = "Erro ao atualizar perfil. Tente novamente.";
                $messageType = "error";
            }
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
        // Contar sites do usuário logado
        $userId = $_SESSION['safenode_user_id'] ?? null;
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM safenode_sites WHERE user_id = ?");
        $stmt->execute([$userId]);
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
    <title>Perfil | SafeNode</title>
    <link rel="icon" type="image/png" href="assets/img/logos (6).png">
    <link rel="shortcut icon" type="image/png" href="assets/img/logos (6).png">
    <link rel="apple-touch-icon" href="assets/img/logos (6).png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
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

        /* Stat Card Melhorado */
        .stat-card {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.08) 0%, rgba(0, 0, 0, 0.6) 100%);
            border: 1px solid rgba(59, 130, 246, 0.15);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .stat-card:hover {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.12) 0%, rgba(0, 0, 0, 0.7) 100%);
            border-color: rgba(59, 130, 246, 0.25);
            transform: translateY(-4px);
            box-shadow: 0 10px 30px rgba(59, 130, 246, 0.2);
        }

        /* Avatar Glow */
        .avatar-glow {
            box-shadow: 0 0 30px rgba(59, 130, 246, 0.3);
            transition: all 0.3s;
        }
        .avatar-glow:hover {
            box-shadow: 0 0 40px rgba(59, 130, 246, 0.5);
            transform: scale(1.05);
        }

        /* Form Inputs Melhorados */
        .form-input {
            background: rgba(39, 39, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s;
        }
        .form-input:focus {
            background: rgba(39, 39, 42, 0.8);
            border-color: rgba(59, 130, 246, 0.5);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1), 0 0 20px rgba(59, 130, 246, 0.1);
            outline: none;
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

        /* Botões Melhorados */
        .btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            box-shadow: 0 4px 14px rgba(59, 130, 246, 0.3);
            transition: all 0.3s;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
            transform: translateY(-1px);
        }

        /* Animações */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in {
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes scale-in {
            from {
                opacity: 0;
                transform: scale(0.95);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
        .animate-scale-in {
            animation: scale-in 0.2s ease-out;
        }

        /* Depth Shadow */
        .depth-shadow {
            box-shadow: 
                0 10px 30px rgba(0, 0, 0, 0.5),
                0 0 0 1px rgba(255, 255, 255, 0.05),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
        }

        /* Security Card */
        .security-card {
            transition: all 0.3s;
        }
        .security-card:hover {
            transform: translateX(4px);
            border-color: rgba(59, 130, 246, 0.3);
        }
    </style>
</head>
<body class="bg-black text-zinc-200 font-sans h-full overflow-hidden flex">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="flex-1 flex flex-col h-full relative overflow-hidden bg-black">
        <header class="h-16 border-b border-white/5 bg-black/50 backdrop-blur-xl sticky top-0 z-40 px-6 flex items-center justify-between">
            <div class="hidden md:flex md:items-center md:gap-3 md:flex-1">
                <div class="w-0.5 h-6 bg-gradient-to-b from-blue-500 to-purple-500 rounded-full"></div>
                <div class="flex-1">
                    <h2 class="text-xl font-bold text-white tracking-tight">Perfil do Usuário</h2>
                    <p class="text-xs text-zinc-400 mt-0.5 font-medium">Gerencie suas informações pessoais</p>
                </div>
                <a href="dashboard.php" class="p-2 text-zinc-400 hover:text-white hover:bg-white/5 rounded-lg transition-all flex items-center gap-2">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                    <span class="text-sm font-medium">Voltar</span>
                </a>
            </div>
            <div class="flex items-center gap-4 md:hidden">
                <button class="text-zinc-400 hover:text-white transition-colors" data-sidebar-toggle>
                    <i data-lucide="menu" class="w-6 h-6"></i>
                </button>
                <span class="font-bold text-lg text-white">SafeNode</span>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-4 md:p-8 z-10">
            <div class="max-w-5xl mx-auto space-y-6">
                <?php if ($message): ?>
                    <div class="mb-6 p-4 rounded-xl <?php 
                        echo $messageType === 'success' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30' : 
                            ($messageType === 'error' ? 'bg-red-500/10 text-red-400 border border-red-500/30' : 
                            'bg-blue-500/10 text-blue-400 border border-blue-500/30'); 
                    ?> font-semibold flex items-center gap-3 animate-fade-in shadow-lg">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 rounded-lg <?php 
                                echo $messageType === 'success' ? 'bg-emerald-500/20 border border-emerald-500/30' : 
                                    ($messageType === 'error' ? 'bg-red-500/20 border border-red-500/30' : 
                                    'bg-blue-500/20 border border-blue-500/30'); 
                            ?> flex items-center justify-center">
                                <i data-lucide="<?php echo $messageType === 'success' ? 'check-circle' : ($messageType === 'error' ? 'alert-circle' : 'info'); ?>" class="w-5 h-5"></i>
                            </div>
                        </div>
                        <p class="flex-1"><?php echo htmlspecialchars($message); ?></p>
                    </div>
                <?php endif; ?>

                <!-- Header do Perfil - Redesign -->
                <div class="glass-card rounded-2xl p-6 md:p-8 overflow-hidden relative animate-fade-in depth-shadow">
                    <!-- Grid pattern -->
                    <div class="absolute inset-0 grid-pattern opacity-20"></div>
                    
                    <!-- Background Pattern -->
                    <div class="absolute top-0 right-0 w-64 h-64 bg-gradient-to-br from-blue-600/10 to-purple-600/5 rounded-full blur-3xl"></div>
                    
                    <div class="relative z-10">
                        <div class="flex flex-col md:flex-row items-start md:items-center gap-6 mb-8">
                            <?php if ($avatarUrl): ?>
                                <div class="relative w-28 h-28 rounded-2xl overflow-hidden shadow-2xl avatar-glow border-2 border-blue-500/30">
                                    <img src="<?php echo htmlspecialchars($avatarUrl); ?>" alt="Avatar" class="w-full h-full object-cover">
                                    <div class="absolute bottom-1 right-1 w-6 h-6 bg-blue-600 rounded-full flex items-center justify-center border-2 border-black shadow-lg">
                                        <i data-lucide="check" class="w-3.5 h-3.5 text-white"></i>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="w-28 h-28 rounded-2xl bg-gradient-to-br from-blue-600 to-blue-500 flex items-center justify-center text-white font-black text-4xl shadow-2xl avatar-glow border-2 border-blue-500/30">
                                    <?php echo $userInitial; ?>
                                </div>
                            <?php endif; ?>
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-3">
                                    <h3 class="text-3xl font-black text-white"><?php echo htmlspecialchars($username); ?></h3>
                                    <span class="modern-badge px-3 py-1.5 bg-blue-600/20 text-blue-400 rounded-lg text-xs font-bold border border-blue-600/30">
                                        <i data-lucide="check-circle" class="w-3.5 h-3.5 inline mr-1"></i>
                                        Ativo
                                    </span>
                                </div>
                                <p class="text-zinc-400 text-sm flex items-center gap-2 mb-2 font-semibold">
                                    <i data-lucide="shield-check" class="w-4 h-4 text-blue-400"></i>
                                    Administrador do Sistema
                                </p>
                                <p class="text-zinc-500 text-xs flex items-center gap-2 font-medium">
                                    <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
                                    Membro desde <?php echo date('d/m/Y', strtotime($userStats['account_created'])); ?>
                                </p>
                            </div>
                        </div>

                        <!-- Estatísticas - Redesign -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="stat-card rounded-xl p-5 relative overflow-hidden">
                                <div class="absolute top-0 right-0 w-20 h-20 bg-blue-500/5 rounded-full blur-2xl"></div>
                                <div class="relative z-10">
                                <div class="flex items-center justify-between mb-3">
                                        <div class="w-12 h-12 rounded-xl bg-blue-600/20 border border-blue-500/30 flex items-center justify-center">
                                        <i data-lucide="globe" class="w-6 h-6 text-blue-400"></i>
                                    </div>
                                        <span class="text-xs text-zinc-500 uppercase tracking-wider font-bold">Sites</span>
                                </div>
                                    <div class="text-3xl font-black text-white mb-1"><?php echo number_format($userStats['total_sites']); ?></div>
                                    <div class="text-xs text-zinc-400 font-medium">Configurados</div>
                                </div>
                            </div>
                            
                            <div class="stat-card rounded-xl p-5 relative overflow-hidden">
                                <div class="absolute top-0 right-0 w-20 h-20 bg-purple-500/5 rounded-full blur-2xl"></div>
                                <div class="relative z-10">
                                <div class="flex items-center justify-between mb-3">
                                        <div class="w-12 h-12 rounded-xl bg-purple-600/20 border border-purple-500/30 flex items-center justify-center">
                                            <i data-lucide="activity" class="w-6 h-6 text-purple-400"></i>
                                    </div>
                                        <span class="text-xs text-zinc-500 uppercase tracking-wider font-bold">Logs</span>
                                </div>
                                    <div class="text-3xl font-black text-white mb-1"><?php echo number_format($userStats['total_logs']); ?></div>
                                    <div class="text-xs text-zinc-400 font-medium">Registrados</div>
                                </div>
                            </div>
                            
                            <div class="stat-card rounded-xl p-5 relative overflow-hidden">
                                <div class="absolute top-0 right-0 w-20 h-20 bg-red-500/5 rounded-full blur-2xl"></div>
                                <div class="relative z-10">
                                <div class="flex items-center justify-between mb-3">
                                        <div class="w-12 h-12 rounded-xl bg-red-600/20 border border-red-500/30 flex items-center justify-center">
                                            <i data-lucide="shield-alert" class="w-6 h-6 text-red-400"></i>
                                    </div>
                                        <span class="text-xs text-zinc-500 uppercase tracking-wider font-bold">Bloqueios</span>
                                </div>
                                    <div class="text-3xl font-black text-white mb-1"><?php echo number_format($userStats['total_blocks']); ?></div>
                                    <div class="text-xs text-zinc-400 font-medium">Ativos</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Configurações da Conta - Redesign -->
                <div class="glass-card rounded-2xl p-6 md:p-8 relative overflow-hidden animate-fade-in depth-shadow" style="animation-delay: 0.1s">
                    <!-- Grid pattern -->
                    <div class="absolute inset-0 grid-pattern opacity-20"></div>
                    
                    <!-- Decoração de fundo -->
                    <div class="absolute top-0 right-0 w-40 h-40 bg-blue-500/5 rounded-full blur-3xl"></div>
                    
                    <div class="relative z-10">
                    <div class="flex items-center gap-3 mb-6">
                            <div class="w-12 h-12 rounded-xl bg-blue-500/15 border border-blue-500/30 flex items-center justify-center">
                                <i data-lucide="settings" class="w-6 h-6 text-blue-400"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-white">Configurações da Conta</h3>
                                <p class="text-xs text-zinc-400 mt-0.5 font-medium">Gerencie suas credenciais de acesso</p>
                        </div>
                    </div>
                    
                    <form method="POST" id="profileForm" class="space-y-5">
                        <?php echo csrf_field(); ?>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-bold text-zinc-300 mb-2 flex items-center gap-2">
                                    <i data-lucide="user" class="w-4 h-4 text-blue-400"></i>
                                    Nome de Usuário
                                </label>
                                <div class="relative">
                                    <i data-lucide="user" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-500"></i>
                                <input type="text" name="username" id="inputUsername" value="<?php echo htmlspecialchars($username); ?>" required
                                       pattern="[a-zA-Z0-9_]{3,50}" 
                                       disabled
                                           class="profile-input w-full pl-10 pr-4 py-3 border border-white/10 rounded-xl bg-zinc-900/30 text-zinc-500 cursor-not-allowed transition-all" 
                                       placeholder="seu_usuario">
                                </div>
                                <p class="mt-1.5 text-xs text-zinc-500 flex items-center gap-1 font-medium">
                                    <i data-lucide="info" class="w-3 h-3"></i>
                                    Use apenas letras, números e _ (mín. 3 caracteres)
                                </p>
                            </div>

                            <?php if ($email): ?>
                            <div>
                                <label class="block text-sm font-bold text-zinc-300 mb-2 flex items-center gap-2">
                                    <i data-lucide="mail" class="w-4 h-4 text-blue-400"></i>
                                    E-mail
                                </label>
                                <div class="relative">
                                    <i data-lucide="mail" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-500"></i>
                                    <input type="email" value="<?php echo htmlspecialchars($email); ?>" disabled class="w-full pl-10 pr-4 py-3 border border-white/10 rounded-xl bg-zinc-900/30 text-zinc-500 cursor-not-allowed">
                                </div>
                                <p class="mt-1.5 text-xs text-zinc-500 flex items-center gap-1 font-medium">
                                    <i data-lucide="shield-check" class="w-3 h-3 text-emerald-400"></i>
                                    E-mail verificado e protegido
                                </p>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-zinc-300 mb-2 flex items-center gap-2">
                                <i data-lucide="user-circle" class="w-4 h-4 text-blue-400"></i>
                                Nome Completo
                            </label>
                            <div class="relative">
                                <i data-lucide="user-circle" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-500"></i>
                            <input type="text" name="full_name" id="inputFullName" value="<?php echo htmlspecialchars($fullName); ?>" required disabled
                                       class="profile-input w-full pl-10 pr-4 py-3 border border-white/10 rounded-xl bg-zinc-900/30 text-zinc-500 cursor-not-allowed transition-all" 
                                   placeholder="Seu nome completo">
                            </div>
                            <p class="mt-1.5 text-xs text-zinc-500 flex items-center gap-1 font-medium">
                                <i data-lucide="info" class="w-3 h-3"></i>
                                Este nome será exibido em seu perfil
                            </p>
                        </div>

                        <div class="flex flex-col sm:flex-row justify-between items-center gap-4 pt-6 border-t border-white/10">
                            <a href="change-password.php" class="text-blue-400 hover:text-blue-300 text-sm font-bold flex items-center gap-2 transition-colors order-2 sm:order-1 hover:scale-105">
                                <i data-lucide="key" class="w-4 h-4"></i>
                                Alterar Senha
                            </a>
                            
                            <!-- Botão Editar (modo visualização) -->
                            <div id="editButtonContainer" class="flex justify-end w-full sm:w-auto order-1 sm:order-2">
                                <button type="button" onclick="enableEditMode()" class="btn-primary w-full sm:w-auto px-6 py-3 text-white rounded-xl font-bold transition-all flex items-center justify-center gap-2">
                                    <i data-lucide="edit-2" class="w-4 h-4"></i>
                                    Editar Perfil
                                </button>
                            </div>
                            
                            <!-- Botões Cancelar e Salvar (modo edição) -->
                            <div id="actionButtonsContainer" class="hidden flex flex-col sm:flex-row gap-3 w-full sm:w-auto order-1 sm:order-2">
                                <button type="button" onclick="cancelEditMode()" class="w-full sm:w-auto px-6 py-3 border border-white/10 text-white rounded-xl hover:bg-white/5 hover:border-white/20 font-bold transition-all text-center">
                                    Cancelar
                                </button>
                                <button type="submit" name="update_profile" class="btn-primary w-full sm:w-auto px-6 py-3 text-white rounded-xl font-bold transition-all flex items-center justify-center gap-2">
                                    <i data-lucide="save" class="w-4 h-4"></i>
                                    Salvar Alterações
                                </button>
                            </div>
                        </div>
                    </form>
                    </div>
                </div>

                <!-- Segurança Avançada - Redesign -->
                <div class="glass-card rounded-2xl p-6 md:p-8 relative overflow-hidden animate-fade-in depth-shadow" style="animation-delay: 0.2s">
                    <!-- Grid pattern -->
                    <div class="absolute inset-0 grid-pattern opacity-20"></div>
                    
                    <!-- Decoração de fundo -->
                    <div class="absolute top-0 right-0 w-40 h-40 bg-purple-500/5 rounded-full blur-3xl"></div>
                    
                    <div class="relative z-10">
                    <div class="flex items-center gap-3 mb-6">
                            <div class="w-12 h-12 rounded-xl bg-purple-500/15 border border-purple-500/30 flex items-center justify-center">
                                <i data-lucide="shield" class="w-6 h-6 text-purple-400"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-white">Segurança Avançada</h3>
                                <p class="text-xs text-zinc-400 mt-0.5 font-medium">Proteja ainda mais sua conta</p>
                        </div>
                    </div>
                    
                    <div class="space-y-3">
                        <!-- 2FA removido -->
                        
                            <div class="security-card flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-5 rounded-xl bg-zinc-900/30 border border-white/5 hover:border-blue-500/30 transition-all group">
                            <div class="flex items-start gap-4">
                                    <div class="w-12 h-12 rounded-lg bg-blue-600/15 border border-blue-500/25 flex items-center justify-center flex-shrink-0 group-hover:bg-blue-600/25 transition-colors">
                                    <i data-lucide="monitor" class="w-6 h-6 text-blue-400"></i>
                                </div>
                                <div>
                                        <p class="text-sm font-bold text-white mb-1">Sessões Ativas</p>
                                        <p class="text-xs text-zinc-500 font-medium">Gerencie dispositivos conectados à sua conta</p>
                                </div>
                            </div>
                                <a href="sessions.php" class="modern-badge px-5 py-2.5 bg-blue-600/15 text-blue-400 rounded-lg text-sm font-bold hover:bg-blue-600/25 transition-all whitespace-nowrap border border-blue-600/30 text-center">
                                    <i data-lucide="arrow-right" class="w-4 h-4 inline mr-1"></i>
                                Ver sessões
                            </a>
                        </div>

                            <div class="security-card flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-5 rounded-xl bg-zinc-900/30 border border-white/5 hover:border-blue-500/30 transition-all group">
                            <div class="flex items-start gap-4">
                                    <div class="w-12 h-12 rounded-lg bg-blue-600/15 border border-blue-500/25 flex items-center justify-center flex-shrink-0 group-hover:bg-blue-600/25 transition-colors">
                                    <i data-lucide="clock" class="w-6 h-6 text-blue-400"></i>
                                </div>
                                <div>
                                        <p class="text-sm font-bold text-white mb-1">Histórico de Atividades</p>
                                        <p class="text-xs text-zinc-500 font-medium">Visualize ações recentes em sua conta</p>
                                </div>
                            </div>
                                <a href="activity-log.php" class="modern-badge px-5 py-2.5 bg-blue-600/15 text-blue-400 rounded-lg text-sm font-bold hover:bg-blue-600/25 transition-all whitespace-nowrap border border-blue-600/30 text-center">
                                    <i data-lucide="arrow-right" class="w-4 h-4 inline mr-1"></i>
                                Ver histórico
                            </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Zona Perigosa - Redesign -->
                <div class="glass-card rounded-2xl p-6 md:p-8 border border-red-500/30 relative overflow-hidden animate-fade-in depth-shadow" style="animation-delay: 0.3s">
                    <!-- Grid pattern -->
                    <div class="absolute inset-0 grid-pattern opacity-20"></div>
                    
                    <!-- Decoração de fundo -->
                    <div class="absolute top-0 right-0 w-40 h-40 bg-red-500/5 rounded-full blur-3xl"></div>
                    
                    <div class="relative z-10">
                    <div class="flex items-center gap-3 mb-6">
                            <div class="w-12 h-12 rounded-xl bg-red-600/15 border border-red-500/30 flex items-center justify-center">
                                <i data-lucide="alert-triangle" class="w-6 h-6 text-red-400"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-red-400">Zona Perigosa</h3>
                                <p class="text-xs text-zinc-400 mt-0.5 font-medium">Ações irreversíveis - proceda com cautela</p>
                        </div>
                    </div>
                    
                    <div class="space-y-3">
                            <div class="security-card flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-5 rounded-xl bg-red-500/5 border border-red-500/25 hover:bg-red-500/10 transition-all">
                            <div class="flex items-start gap-4">
                                    <div class="w-12 h-12 rounded-lg bg-red-600/15 border border-red-500/25 flex items-center justify-center flex-shrink-0">
                                    <i data-lucide="log-out" class="w-6 h-6 text-red-400"></i>
                                </div>
                                <div>
                                        <p class="text-sm font-bold text-white mb-1">Encerrar Todas as Sessões</p>
                                        <p class="text-xs text-zinc-500 font-medium">Desconecte-se de todos os dispositivos imediatamente</p>
                                </div>
                            </div>
                                <button onclick="openTerminateSessionsModal()" class="modern-badge px-5 py-2.5 bg-red-500/15 text-red-400 rounded-lg text-sm font-bold hover:bg-red-500/25 transition-all border border-red-500/30 whitespace-nowrap">
                                    <i data-lucide="log-out" class="w-4 h-4 inline mr-1"></i>
                                Encerrar Tudo
                            </button>
                        </div>
                        
                            <div class="security-card flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-5 rounded-xl bg-red-500/5 border border-red-500/25 hover:bg-red-500/10 transition-all">
                            <div class="flex items-start gap-4">
                                    <div class="w-12 h-12 rounded-lg bg-red-600/15 border border-red-500/25 flex items-center justify-center flex-shrink-0">
                                    <i data-lucide="trash-2" class="w-6 h-6 text-red-400"></i>
                                </div>
                                <div>
                                        <p class="text-sm font-bold text-white mb-1">Excluir Conta Permanentemente</p>
                                        <p class="text-xs text-zinc-500 font-medium">Esta ação não pode ser desfeita. Todos os dados serão perdidos</p>
                                </div>
                            </div>
                                <button onclick="openDeleteAccountModal()" class="modern-badge px-5 py-2.5 bg-red-500/15 text-red-400 rounded-lg text-sm font-bold hover:bg-red-500/25 transition-all border border-red-500/30 whitespace-nowrap">
                                    <i data-lucide="trash-2" class="w-4 h-4 inline mr-1"></i>
                                Excluir Conta
                            </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php $csrfToken = CSRFProtection::generateToken(); ?>

    <!-- Modal de Confirmação Customizado - Redesign -->
    <div id="confirmModal" class="hidden fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/90 backdrop-blur-md">
        <div class="glass-card rounded-2xl p-6 md:p-8 max-w-md w-full border border-white/10 relative overflow-hidden depth-shadow animate-scale-in">
            <!-- Grid pattern -->
            <div class="absolute inset-0 grid-pattern opacity-20"></div>
            
            <!-- Decoração de fundo -->
            <div class="absolute top-0 right-0 w-32 h-32 bg-yellow-500/5 rounded-full blur-3xl"></div>
            
            <div class="relative z-10">
            <div class="flex items-center gap-3 mb-6">
                    <div class="w-12 h-12 rounded-xl bg-yellow-600/15 border border-yellow-500/30 flex items-center justify-center">
                    <i data-lucide="alert-circle" class="w-6 h-6 text-yellow-400"></i>
                </div>
                <div class="flex-1">
                    <h3 id="confirmTitle" class="text-xl font-bold text-white">Confirmar Ação</h3>
                </div>
                    <button onclick="closeConfirmModal()" class="p-2 text-zinc-400 hover:text-white hover:bg-white/5 rounded-lg transition-all">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

                <p id="confirmMessage" class="text-sm text-zinc-400 mb-6 leading-relaxed font-medium"></p>

            <div class="flex gap-3">
                    <button id="confirmCancelBtn" onclick="closeConfirmModal()" class="flex-1 px-5 py-3 border border-white/10 text-white rounded-xl hover:bg-white/5 hover:border-white/20 font-bold transition-all">
                    Cancelar
                </button>
                    <button id="confirmOkBtn" class="btn-primary flex-1 px-5 py-3 text-white rounded-xl font-bold transition-all">
                    Continuar
                </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Sucesso Customizado - Redesign -->
    <div id="successModal" class="hidden fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/90 backdrop-blur-md">
        <div class="glass-card rounded-2xl p-6 md:p-8 max-w-md w-full border border-emerald-500/30 relative overflow-hidden depth-shadow animate-scale-in">
            <!-- Grid pattern -->
            <div class="absolute inset-0 grid-pattern opacity-20"></div>
            
            <!-- Decoração de fundo -->
            <div class="absolute top-0 right-0 w-32 h-32 bg-emerald-500/5 rounded-full blur-3xl"></div>
            
            <div class="relative z-10">
            <div class="flex items-center gap-3 mb-6">
                    <div class="w-12 h-12 rounded-xl bg-emerald-600/15 border border-emerald-500/30 flex items-center justify-center">
                    <i data-lucide="check-circle" class="w-6 h-6 text-emerald-400"></i>
                </div>
                <div class="flex-1">
                    <h3 class="text-xl font-bold text-white">Sucesso</h3>
                </div>
            </div>

                <p id="successMessage" class="text-sm text-zinc-400 mb-6 leading-relaxed font-medium"></p>

                <button id="successOkBtn" class="w-full px-5 py-3 bg-gradient-to-r from-emerald-600 to-emerald-700 text-white rounded-xl hover:from-emerald-700 hover:to-emerald-800 font-bold transition-all shadow-lg shadow-emerald-500/30">
                OK
            </button>
            </div>
        </div>
    </div>

    <!-- Modal: Encerrar Todas as Sessões - Redesign -->
    <div id="terminateSessionsModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/90 backdrop-blur-md">
        <div class="glass-card rounded-2xl p-6 md:p-8 max-w-md w-full border border-red-500/30 relative overflow-hidden depth-shadow animate-scale-in">
            <!-- Grid pattern -->
            <div class="absolute inset-0 grid-pattern opacity-20"></div>
            
            <!-- Decoração de fundo -->
            <div class="absolute top-0 right-0 w-32 h-32 bg-red-500/5 rounded-full blur-3xl"></div>
            
            <div class="relative z-10">
            <div class="flex items-center gap-3 mb-6">
                    <div class="w-12 h-12 rounded-xl bg-red-600/15 border border-red-500/30 flex items-center justify-center">
                    <i data-lucide="log-out" class="w-6 h-6 text-red-400"></i>
                </div>
                <div class="flex-1">
                    <h3 class="text-xl font-bold text-white">Encerrar Todas as Sessões</h3>
                        <p class="text-xs text-zinc-400 mt-0.5 font-medium">Confirmação de segurança em 2 etapas</p>
                </div>
                    <button onclick="closeTerminateSessionsModal()" class="p-2 text-zinc-400 hover:text-white hover:bg-white/5 rounded-lg transition-all">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <div id="terminateStep1" class="space-y-4">
                <div class="p-4 rounded-xl bg-red-500/10 border border-red-500/20">
                    <p class="text-sm text-red-400 flex items-start gap-2">
                        <i data-lucide="alert-triangle" class="w-4 h-4 mt-0.5 flex-shrink-0"></i>
                        <span>Você será desconectado de todos os dispositivos e precisará fazer login novamente.</span>
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-bold text-zinc-300 mb-2">Sua Senha</label>
                    <div class="relative">
                        <input type="password" id="terminatePassword" 
                               class="form-input w-full px-4 py-3 pr-12 rounded-xl text-white placeholder:text-zinc-600 text-sm font-medium" 
                               placeholder="Digite sua senha">
                        <button type="button" onclick="togglePasswordVisibility('terminatePassword')" class="absolute right-3 top-1/2 -translate-y-1/2 text-zinc-500 hover:text-white transition-colors">
                            <i data-lucide="eye" class="w-5 h-5"></i>
                        </button>
                    </div>
                </div>

                <div id="terminateError" class="hidden p-3 rounded-lg bg-red-500/10 border border-red-500/20 text-sm text-red-400"></div>

                <div class="flex gap-3 pt-2">
                    <button onclick="closeTerminateSessionsModal()" class="flex-1 px-5 py-3 border border-white/10 text-white rounded-xl hover:bg-white/5 hover:border-white/20 font-bold transition-all">
                        Cancelar
                    </button>
                    <button onclick="requestTerminateCode()" id="terminateSubmitBtn" class="flex-1 px-5 py-3 bg-gradient-to-r from-red-600 to-red-700 text-white rounded-xl hover:from-red-700 hover:to-red-800 font-bold transition-all shadow-lg shadow-red-500/30 flex items-center justify-center gap-2">
                        <span>Enviar Código</span>
                    </button>
                </div>
            </div>

            <div id="terminateStep2" class="hidden space-y-4">
                <div class="p-4 rounded-xl bg-blue-500/10 border border-blue-500/20">
                    <p class="text-sm text-blue-400 flex items-start gap-2">
                        <i data-lucide="mail" class="w-4 h-4 mt-0.5 flex-shrink-0"></i>
                        <span>Enviamos um código de 6 dígitos para seu e-mail. Verifique sua caixa de entrada.</span>
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-bold text-zinc-300 mb-2">Código de Segurança</label>
                    <input type="text" id="terminateCode" maxlength="6" pattern="[0-9]{6}"
                           class="form-input w-full px-4 py-3 rounded-xl text-white text-center text-2xl tracking-widest font-mono placeholder:text-zinc-600 text-sm font-medium" 
                           placeholder="000000">
                </div>

                <div id="terminateError2" class="hidden p-3 rounded-lg bg-red-500/10 border border-red-500/20 text-sm text-red-400"></div>

                <div class="flex gap-3 pt-2">
                    <button onclick="backToTerminateStep1()" class="flex-1 px-5 py-3 border border-white/10 text-white rounded-xl hover:bg-white/5 hover:border-white/20 font-bold transition-all">
                        Voltar
                    </button>
                    <button onclick="verifyTerminateCode()" id="terminateVerifyBtn" class="flex-1 px-5 py-3 bg-gradient-to-r from-red-600 to-red-700 text-white rounded-xl hover:from-red-700 hover:to-red-800 font-bold transition-all shadow-lg shadow-red-500/30 flex items-center justify-center gap-2">
                        <span>Confirmar</span>
                    </button>
                </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Excluir Conta - Redesign -->
    <div id="deleteAccountModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/90 backdrop-blur-md">
        <div class="glass-card rounded-2xl p-6 md:p-8 max-w-md w-full border border-red-500/30 relative overflow-hidden depth-shadow animate-scale-in">
            <!-- Grid pattern -->
            <div class="absolute inset-0 grid-pattern opacity-20"></div>
            
            <!-- Decoração de fundo -->
            <div class="absolute top-0 right-0 w-32 h-32 bg-red-500/5 rounded-full blur-3xl"></div>
            
            <div class="relative z-10">
            <div class="flex items-center gap-3 mb-6">
                    <div class="w-12 h-12 rounded-xl bg-red-600/15 border border-red-500/30 flex items-center justify-center">
                    <i data-lucide="trash-2" class="w-6 h-6 text-red-400"></i>
                </div>
                <div class="flex-1">
                    <h3 class="text-xl font-bold text-red-400">Excluir Conta</h3>
                        <p class="text-xs text-zinc-400 mt-0.5 font-medium">Ação irreversível - confirmação dupla</p>
                </div>
                    <button onclick="closeDeleteAccountModal()" class="p-2 text-zinc-400 hover:text-white hover:bg-white/5 rounded-lg transition-all">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <div id="deleteStep1" class="space-y-4">
                <div class="p-4 rounded-xl bg-red-500/10 border border-red-500/20">
                    <p class="text-sm text-red-400 flex items-start gap-2 mb-2">
                        <i data-lucide="alert-triangle" class="w-4 h-4 mt-0.5 flex-shrink-0"></i>
                        <span><strong>Atenção:</strong> Esta ação é PERMANENTE e IRREVERSÍVEL.</span>
                    </p>
                    <ul class="text-xs text-red-400/80 space-y-1 ml-6">
                        <li>• Todos os seus dados serão apagados</li>
                        <li>• Todos os seus sites serão removidos</li>
                        <li>• Não será possível recuperar sua conta</li>
                    </ul>
                </div>

                <div>
                    <label class="block text-sm font-bold text-zinc-300 mb-2">Sua Senha</label>
                    <div class="relative">
                        <input type="password" id="deletePassword" 
                               class="form-input w-full px-4 py-3 pr-12 rounded-xl text-white placeholder:text-zinc-600 text-sm font-medium" 
                               placeholder="Digite sua senha">
                        <button type="button" onclick="togglePasswordVisibility('deletePassword')" class="absolute right-3 top-1/2 -translate-y-1/2 text-zinc-500 hover:text-white transition-colors">
                            <i data-lucide="eye" class="w-5 h-5"></i>
                        </button>
                    </div>
                </div>

                <div id="deleteError" class="hidden p-3 rounded-lg bg-red-500/10 border border-red-500/20 text-sm text-red-400"></div>

                <div class="flex gap-3 pt-2">
                    <button onclick="closeDeleteAccountModal()" class="flex-1 px-5 py-3 border border-white/10 text-white rounded-xl hover:bg-white/5 hover:border-white/20 font-bold transition-all">
                        Cancelar
                    </button>
                    <button onclick="requestDeleteCode()" id="deleteSubmitBtn" class="flex-1 px-5 py-3 bg-gradient-to-r from-red-600 to-red-700 text-white rounded-xl hover:from-red-700 hover:to-red-800 font-bold transition-all shadow-lg shadow-red-500/30 flex items-center justify-center gap-2">
                        <span>Enviar Código</span>
                    </button>
                </div>
            </div>

            <div id="deleteStep2" class="hidden space-y-4">
                <div class="p-4 rounded-xl bg-red-500/10 border border-red-500/20">
                    <p class="text-sm text-red-400 flex items-start gap-2 mb-2">
                        <i data-lucide="mail" class="w-4 h-4 mt-0.5 flex-shrink-0"></i>
                        <span>Enviamos um código de 6 dígitos para seu e-mail. Este é o último passo antes de excluir sua conta permanentemente.</span>
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-bold text-zinc-300 mb-2">Código de Segurança</label>
                    <input type="text" id="deleteCode" maxlength="6" pattern="[0-9]{6}"
                           class="form-input w-full px-4 py-3 rounded-xl text-white text-center text-2xl tracking-widest font-mono placeholder:text-zinc-600 text-sm font-medium" 
                           placeholder="000000">
                </div>

                <div id="deleteError2" class="hidden p-3 rounded-lg bg-red-500/10 border border-red-500/20 text-sm text-red-400"></div>

                <div class="flex gap-3 pt-2">
                    <button onclick="backToDeleteStep1()" class="flex-1 px-5 py-3 border border-white/10 text-white rounded-xl hover:bg-white/5 hover:border-white/20 font-bold transition-all">
                        Voltar
                    </button>
                    <button onclick="verifyDeleteCode()" id="deleteVerifyBtn" class="flex-1 px-5 py-3 bg-gradient-to-r from-red-600 to-red-700 text-white rounded-xl hover:from-red-700 hover:to-red-800 font-bold transition-all shadow-lg shadow-red-500/30 flex items-center justify-center gap-2">
                        <span>Excluir Permanentemente</span>
                    </button>
                </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();

        // ========== MODO DE EDIÇÃO DO PERFIL ==========
        let originalUsername = '';
        let originalFullName = '';
        
        // Inicializar valores originais ao carregar a página
        document.addEventListener('DOMContentLoaded', function() {
            originalUsername = document.getElementById('inputUsername').value;
            originalFullName = document.getElementById('inputFullName').value;
            
            // Se houver mensagem de erro, ativar modo de edição automaticamente
            <?php if ($message && $messageType === 'error'): ?>
            enableEditMode();
            <?php endif; ?>
        });
        
        function enableEditMode() {
            // Salvar valores originais
            originalUsername = document.getElementById('inputUsername').value;
            originalFullName = document.getElementById('inputFullName').value;
            
            // Habilitar campos
            const usernameInput = document.getElementById('inputUsername');
            const fullNameInput = document.getElementById('inputFullName');
            
            usernameInput.disabled = false;
            fullNameInput.disabled = false;
            
            // Atualizar classes para modo editável
            usernameInput.classList.remove('text-zinc-500', 'cursor-not-allowed');
            usernameInput.classList.add('text-white', 'cursor-text', 'focus:ring-2', 'focus:ring-blue-500/50', 'focus:border-blue-500/50');
            
            fullNameInput.classList.remove('text-zinc-500', 'cursor-not-allowed');
            fullNameInput.classList.add('text-white', 'cursor-text', 'focus:ring-2', 'focus:ring-blue-500/50', 'focus:border-blue-500/50');
            
            // Mostrar/esconder botões
            document.getElementById('editButtonContainer').classList.add('hidden');
            document.getElementById('actionButtonsContainer').classList.remove('hidden');
            
            // Focar no primeiro campo
            usernameInput.focus();
            
            lucide.createIcons();
        }
        
        function cancelEditMode() {
            // Restaurar valores originais
            document.getElementById('inputUsername').value = originalUsername;
            document.getElementById('inputFullName').value = originalFullName;
            
            // Desabilitar campos
            const usernameInput = document.getElementById('inputUsername');
            const fullNameInput = document.getElementById('inputFullName');
            
            usernameInput.disabled = true;
            fullNameInput.disabled = true;
            
            // Restaurar classes para modo visualização
            usernameInput.classList.remove('text-white', 'cursor-text', 'focus:ring-2', 'focus:ring-blue-500/50', 'focus:border-blue-500/50');
            usernameInput.classList.add('text-zinc-500', 'cursor-not-allowed');
            
            fullNameInput.classList.remove('text-white', 'cursor-text', 'focus:ring-2', 'focus:ring-blue-500/50', 'focus:border-blue-500/50');
            fullNameInput.classList.add('text-zinc-500', 'cursor-not-allowed');
            
            // Mostrar/esconder botões
            document.getElementById('editButtonContainer').classList.remove('hidden');
            document.getElementById('actionButtonsContainer').classList.add('hidden');
            
            lucide.createIcons();
        }

        // ========== MODAL DE CONFIRMAÇÃO ==========
        let confirmCallback = null;
        let successCallback = null;

        function showConfirmModal(title, message, cancelText, okText, callback) {
            document.getElementById('confirmTitle').textContent = title;
            document.getElementById('confirmMessage').textContent = message;
            document.getElementById('confirmCancelBtn').textContent = cancelText || 'Cancelar';
            document.getElementById('confirmOkBtn').textContent = okText || 'Continuar';
            confirmCallback = callback;
            document.getElementById('confirmModal').classList.remove('hidden');
            lucide.createIcons();
        }

        function closeConfirmModal() {
            document.getElementById('confirmModal').classList.add('hidden');
            confirmCallback = null;
        }

        document.getElementById('confirmOkBtn').addEventListener('click', function() {
            if (confirmCallback) {
                confirmCallback();
                closeConfirmModal();
            }
        });

        // ========== MODAL DE SUCESSO ==========
        function showSuccessModal(message, callback) {
            document.getElementById('successMessage').textContent = message;
            successCallback = callback;
            document.getElementById('successModal').classList.remove('hidden');
            lucide.createIcons();
        }

        function closeSuccessModal() {
            document.getElementById('successModal').classList.add('hidden');
            if (successCallback) {
                successCallback();
                successCallback = null;
            }
        }

        document.getElementById('successOkBtn').addEventListener('click', function() {
            closeSuccessModal();
        });

        // ========== ENCERRAR SESSÕES ==========
        function openTerminateSessionsModal() {
            document.getElementById('terminateSessionsModal').classList.remove('hidden');
            document.getElementById('terminateStep1').classList.remove('hidden');
            document.getElementById('terminateStep2').classList.add('hidden');
            document.getElementById('terminatePassword').value = '';
            document.getElementById('terminateCode').value = '';
            document.getElementById('terminateError').classList.add('hidden');
            document.getElementById('terminateError2').classList.add('hidden');
        }

        function closeTerminateSessionsModal() {
            document.getElementById('terminateSessionsModal').classList.add('hidden');
        }

        function backToTerminateStep1() {
            document.getElementById('terminateStep1').classList.remove('hidden');
            document.getElementById('terminateStep2').classList.add('hidden');
            document.getElementById('terminateCode').value = '';
            document.getElementById('terminateError2').classList.add('hidden');
        }

        function requestTerminateCode() {
            const password = document.getElementById('terminatePassword').value;
            const errorDiv = document.getElementById('terminateError');
            const submitBtn = document.getElementById('terminateSubmitBtn');

            if (!password) {
                errorDiv.textContent = 'Por favor, digite sua senha';
                errorDiv.classList.remove('hidden');
                return;
            }

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i>';

            const formData = new FormData();
            formData.append('action', 'terminate_sessions');
            formData.append('step', 'request_code');
            formData.append('password', password);
            formData.append('csrf_token', '<?php echo $csrfToken; ?>');

            fetch('api/dangerous-action.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('terminateStep1').classList.add('hidden');
                    document.getElementById('terminateStep2').classList.remove('hidden');
                    errorDiv.classList.add('hidden');
                } else {
                    errorDiv.textContent = data.error || 'Erro ao enviar código';
                    errorDiv.classList.remove('hidden');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<span>Enviar Código</span>';
                }
                lucide.createIcons();
            })
            .catch(error => {
                errorDiv.textContent = 'Erro ao processar solicitação';
                errorDiv.classList.remove('hidden');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<span>Enviar Código</span>';
            });
        }

        function verifyTerminateCode() {
            const code = document.getElementById('terminateCode').value;
            const errorDiv = document.getElementById('terminateError2');
            const verifyBtn = document.getElementById('terminateVerifyBtn');

            if (!code || code.length !== 6) {
                errorDiv.textContent = 'Por favor, digite o código de 6 dígitos';
                errorDiv.classList.remove('hidden');
                return;
            }

            verifyBtn.disabled = true;
            verifyBtn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i>';

            const formData = new FormData();
            formData.append('action', 'terminate_sessions');
            formData.append('step', 'verify_code');
            formData.append('otp_code', code);
            formData.append('csrf_token', '<?php echo $csrfToken; ?>');

            fetch('api/dangerous-action.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    } else {
                        showSuccessModal(data.message || 'Sessões encerradas com sucesso!', () => {
                            window.location.reload();
                        });
                    }
                } else {
                    errorDiv.textContent = data.error || 'Código inválido';
                    errorDiv.classList.remove('hidden');
                    verifyBtn.disabled = false;
                    verifyBtn.innerHTML = '<span>Confirmar</span>';
                }
                lucide.createIcons();
            })
            .catch(error => {
                errorDiv.textContent = 'Erro ao processar solicitação';
                errorDiv.classList.remove('hidden');
                verifyBtn.disabled = false;
                verifyBtn.innerHTML = '<span>Confirmar</span>';
            });
        }

        // ========== EXCLUIR CONTA ==========
        function openDeleteAccountModal() {
            document.getElementById('deleteAccountModal').classList.remove('hidden');
            document.getElementById('deleteStep1').classList.remove('hidden');
            document.getElementById('deleteStep2').classList.add('hidden');
            document.getElementById('deletePassword').value = '';
            document.getElementById('deleteCode').value = '';
            document.getElementById('deleteError').classList.add('hidden');
            document.getElementById('deleteError2').classList.add('hidden');
        }

        function closeDeleteAccountModal() {
            showConfirmModal(
                'Cancelar Exclusão?',
                'Tem certeza que deseja cancelar? Todos os progressos serão perdidos.',
                'Cancelar',
                'Continuar',
                () => {
                    document.getElementById('deleteAccountModal').classList.add('hidden');
                }
            );
        }

        function backToDeleteStep1() {
            document.getElementById('deleteStep1').classList.remove('hidden');
            document.getElementById('deleteStep2').classList.add('hidden');
            document.getElementById('deleteCode').value = '';
            document.getElementById('deleteError2').classList.add('hidden');
        }

        function requestDeleteCode() {
            const password = document.getElementById('deletePassword').value;
            const errorDiv = document.getElementById('deleteError');
            const submitBtn = document.getElementById('deleteSubmitBtn');

            if (!password) {
                errorDiv.textContent = 'Por favor, digite sua senha';
                errorDiv.classList.remove('hidden');
                return;
            }

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i>';

            const formData = new FormData();
            formData.append('action', 'delete_account');
            formData.append('step', 'request_code');
            formData.append('password', password);
            formData.append('csrf_token', '<?php echo $csrfToken; ?>');

            fetch('api/dangerous-action.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('deleteStep1').classList.add('hidden');
                    document.getElementById('deleteStep2').classList.remove('hidden');
                    errorDiv.classList.add('hidden');
                } else {
                    errorDiv.textContent = data.error || 'Erro ao enviar código';
                    errorDiv.classList.remove('hidden');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<span>Enviar Código</span>';
                }
                lucide.createIcons();
            })
            .catch(error => {
                errorDiv.textContent = 'Erro ao processar solicitação';
                errorDiv.classList.remove('hidden');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<span>Enviar Código</span>';
            });
        }

        function verifyDeleteCode() {
            showConfirmModal(
                'ÚLTIMA CONFIRMAÇÃO',
                'Tem certeza que deseja excluir sua conta permanentemente? Esta ação NÃO pode ser desfeita.',
                'Cancelar',
                'Excluir Permanentemente',
                () => {
                    proceedWithDelete();
                }
            );
        }

        function proceedWithDelete() {

            const code = document.getElementById('deleteCode').value;
            const errorDiv = document.getElementById('deleteError2');
            const verifyBtn = document.getElementById('deleteVerifyBtn');

            if (!code || code.length !== 6) {
                errorDiv.textContent = 'Por favor, digite o código de 6 dígitos';
                errorDiv.classList.remove('hidden');
                return;
            }

            verifyBtn.disabled = true;
            verifyBtn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> Excluindo...';

            const formData = new FormData();
            formData.append('action', 'delete_account');
            formData.append('step', 'verify_code');
            formData.append('otp_code', code);
            formData.append('csrf_token', '<?php echo $csrfToken; ?>');

            fetch('api/dangerous-action.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccessModal(data.message || 'Conta excluída permanentemente.', () => {
                        if (data.redirect) {
                            window.location.href = data.redirect;
                        } else {
                            window.location.href = 'index.php';
                        }
                    });
                } else {
                    errorDiv.textContent = data.error || 'Código inválido';
                    errorDiv.classList.remove('hidden');
                    verifyBtn.disabled = false;
                    verifyBtn.innerHTML = '<span>Excluir Permanentemente</span>';
                }
                lucide.createIcons();
            })
            .catch(error => {
                errorDiv.textContent = 'Erro ao processar solicitação';
                errorDiv.classList.remove('hidden');
                verifyBtn.disabled = false;
                verifyBtn.innerHTML = '<span>Excluir Permanentemente</span>';
            });
        }

        // ========== UTILS ==========
        function togglePasswordVisibility(fieldId) {
            const input = document.getElementById(fieldId);
            const button = input.parentElement.querySelector('button');
            const icon = button ? button.querySelector('i') : null;
            
            if (icon) {
            if (input.type === 'password') {
                input.type = 'text';
                icon.setAttribute('data-lucide', 'eye-off');
            } else {
                input.type = 'password';
                icon.setAttribute('data-lucide', 'eye');
            }
            lucide.createIcons();
            }
        }

        // Auto-focus no código quando aparecer
        document.getElementById('terminateCode')?.addEventListener('input', function(e) {
            this.value = this.value.replace(/\D/g, '').slice(0, 6);
        });

        document.getElementById('deleteCode')?.addEventListener('input', function(e) {
            this.value = this.value.replace(/\D/g, '').slice(0, 6);
        });
    </script>
</body>
</html>

