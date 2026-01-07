<?php
/**
 * KRON - Página de Perfil
 */

session_start();

// Verificar se está logado
if (!isset($_SESSION['kron_logged_in']) || $_SESSION['kron_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../includes/config.php';

$kronUserId = $_SESSION['kron_user_id'] ?? null;
$kronUserName = $_SESSION['kron_user_name'] ?? 'Usuário';
$kronUserEmail = $_SESSION['kron_user_email'] ?? '';

// Buscar dados do usuário
$pdo = getKronDatabase();
$userData = null;
if ($pdo && $kronUserId) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM kron_users WHERE id = ?");
        $stmt->execute([$kronUserId]);
        $userData = $stmt->fetch();
    } catch (PDOException $e) {
        error_log("KRON Profile Error: " . $e->getMessage());
    }
}

$userInitial = strtoupper(substr($kronUserName, 0, 1));
$avatarUrl = $userData['avatar_url'] ?? null;
?>
<!DOCTYPE html>
<html lang="pt-BR" class="dark h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil - KRON Ecosystem</title>
    <link rel="icon" type="image/png" href="../asset/kron.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        dark: {
                            950: '#030303',
                            900: '#050505',
                            850: '#080808',
                            800: '#0a0a0a',
                            700: '#0f0f0f',
                        }
                    }
                }
            }
        }
    </script>
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            background: #030303;
            color: #a1a1aa;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { 
            background: rgba(255,255,255,0.1); 
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.15); }
        
        .glass-card {
            background: rgba(10, 10, 10, 0.7);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .nav-item {
            transition: all 0.2s ease;
        }
        
        .nav-item:hover {
            background: rgba(255, 255, 255, 0.05);
        }
        
        .nav-item.active {
            background: rgba(255, 255, 255, 0.08);
            border-left: 3px solid #ffffff;
        }
        
        .profile-card {
            background: linear-gradient(135deg, rgba(10, 10, 10, 0.95) 0%, rgba(15, 15, 15, 0.8) 100%);
            border: 1px solid rgba(255, 255, 255, 0.05);
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #ffffff 0%, #f5f5f5 100%);
            color: #000000;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(255, 255, 255, 0.2);
        }
        
        .btn-secondary {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }
        
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.2);
        }
        
        .btn-danger {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #ef4444;
            transition: all 0.3s ease;
        }
        
        .btn-danger:hover {
            background: rgba(239, 68, 68, 0.2);
            border-color: rgba(239, 68, 68, 0.3);
        }
        
        @keyframes fade-in {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .animate-fade-in {
            animation: fade-in 0.5s ease-out;
        }
        
        .input-field {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }
        
        .input-field:focus {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.2);
            outline: none;
        }
    </style>
</head>
<body class="min-h-screen bg-dark-950 flex">
    <!-- Sidebar -->
    <aside class="w-64 glass-card border-r border-white/5 flex flex-col h-screen sticky top-0">
        <!-- Logo -->
        <div class="p-6 border-b border-white/5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-white/20 to-white/10 border border-white/10 flex items-center justify-center">
                    <img src="../asset/kron.png" alt="KRON" class="w-6 h-6 brightness-0 invert">
                </div>
                <div>
                    <h1 class="text-lg font-bold text-white">KRON</h1>
                    <p class="text-xs text-zinc-500">Ecosystem</p>
                </div>
            </div>
        </div>
        
        <!-- Navigation -->
        <nav class="flex-1 p-4 space-y-1 overflow-y-auto">
            <a href="index.php" class="nav-item flex items-center gap-3 px-4 py-3 rounded-lg text-zinc-400 hover:text-white">
                <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
                <span class="font-medium">Dashboard</span>
            </a>
            <a href="profile.php" class="nav-item active flex items-center gap-3 px-4 py-3 rounded-lg text-white">
                <i data-lucide="user" class="w-5 h-5"></i>
                <span class="font-medium">Perfil</span>
            </a>
            <a href="#" class="nav-item flex items-center gap-3 px-4 py-3 rounded-lg text-zinc-400 hover:text-white">
                <i data-lucide="link" class="w-5 h-5"></i>
                <span class="font-medium">Conexões</span>
            </a>
            <a href="#" class="nav-item flex items-center gap-3 px-4 py-3 rounded-lg text-zinc-400 hover:text-white">
                <i data-lucide="server" class="w-5 h-5"></i>
                <span class="font-medium">Sistemas</span>
            </a>
            <a href="#" class="nav-item flex items-center gap-3 px-4 py-3 rounded-lg text-zinc-400 hover:text-white">
                <i data-lucide="activity" class="w-5 h-5"></i>
                <span class="font-medium">Analytics</span>
            </a>
            <a href="#" class="nav-item flex items-center gap-3 px-4 py-3 rounded-lg text-zinc-400 hover:text-white">
                <i data-lucide="bell" class="w-5 h-5"></i>
                <span class="font-medium">Notificações</span>
                <span class="ml-auto px-2 py-0.5 bg-white/10 text-white text-xs rounded-full">2</span>
            </a>
            <a href="#" class="nav-item flex items-center gap-3 px-4 py-3 rounded-lg text-zinc-400 hover:text-white">
                <i data-lucide="settings" class="w-5 h-5"></i>
                <span class="font-medium">Configurações</span>
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Header -->
        <header class="glass-card border-b border-white/5 px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-bold text-white">Perfil</h2>
                    <p class="text-xs text-zinc-500 mt-1">Gerencie suas informações pessoais</p>
                </div>
                <a href="index.php" class="px-4 py-2 rounded-lg bg-white/5 hover:bg-white/10 border border-white/10 text-sm font-medium text-white transition">
                    Voltar ao Dashboard
                </a>
            </div>
        </header>

        <!-- Content -->
        <main class="flex-1 overflow-y-auto p-6">
            <div class="max-w-4xl mx-auto space-y-6">
                <!-- Profile Header -->
                <div class="profile-card rounded-2xl p-8 relative overflow-hidden animate-fade-in">
                    <div class="absolute top-0 right-0 w-40 h-40 bg-white/5 rounded-full blur-3xl"></div>
                    <div class="relative z-10">
                        <div class="flex flex-col md:flex-row items-center md:items-start gap-6 mb-8">
                            <!-- Avatar -->
                            <div class="relative">
                                <?php if ($avatarUrl): ?>
                                    <div class="w-24 h-24 rounded-2xl overflow-hidden border-2 border-white/10 shadow-lg">
                                        <img src="<?= htmlspecialchars($avatarUrl) ?>" alt="Avatar" class="w-full h-full object-cover">
                                    </div>
                                <?php else: ?>
                                    <div class="w-24 h-24 rounded-2xl bg-gradient-to-br from-white/20 to-white/10 border-2 border-white/10 flex items-center justify-center text-white font-bold text-3xl shadow-lg">
                                        <?= $userInitial ?>
                                    </div>
                                <?php endif; ?>
                                <button class="absolute bottom-0 right-0 w-8 h-8 rounded-lg bg-white/10 hover:bg-white/20 border border-white/10 flex items-center justify-center text-white transition">
                                    <i data-lucide="camera" class="w-4 h-4"></i>
                                </button>
                            </div>
                            
                            <!-- User Info -->
                            <div class="flex-1 text-center md:text-left">
                                <h1 class="text-3xl font-bold text-white mb-2"><?= htmlspecialchars($kronUserName) ?></h1>
                                <p class="text-zinc-400 mb-4"><?= htmlspecialchars($kronUserEmail) ?></p>
                                <div class="flex items-center justify-center md:justify-start gap-4 text-sm">
                                    <div class="flex items-center gap-2">
                                        <i data-lucide="calendar" class="w-4 h-4 text-zinc-500"></i>
                                        <span class="text-zinc-500">Membro desde 
                                            <?php 
                                            if ($userData && !empty($userData['created_at'])) {
                                                try {
                                                    $date = new DateTime($userData['created_at']);
                                                    echo $date->format('d/m/Y');
                                                } catch (Exception $e) {
                                                    echo date('d/m/Y');
                                                }
                                            } else {
                                                echo date('d/m/Y');
                                            }
                                            ?>
                                        </span>
                                    </div>
                                    <?php if ($userData && $userData['email_verified']): ?>
                                        <div class="flex items-center gap-2">
                                            <i data-lucide="check-circle" class="w-4 h-4 text-emerald-400"></i>
                                            <span class="text-emerald-400">Email verificado</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Informações Pessoais -->
                <div class="profile-card rounded-2xl p-6 animate-fade-in">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center">
                            <i data-lucide="user" class="w-5 h-5 text-white"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-white">Informações Pessoais</h3>
                            <p class="text-xs text-zinc-500">Atualize suas informações de perfil</p>
                        </div>
                    </div>

                    <form class="space-y-5">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-medium text-zinc-300 mb-2">Nome Completo</label>
                                <input type="text" value="<?= htmlspecialchars($kronUserName) ?>" 
                                       class="w-full px-4 py-3 rounded-lg input-field text-white placeholder-zinc-500"
                                       placeholder="Seu nome completo">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-zinc-300 mb-2">Email</label>
                                <input type="email" value="<?= htmlspecialchars($kronUserEmail) ?>" 
                                       class="w-full px-4 py-3 rounded-lg input-field text-white placeholder-zinc-500"
                                       placeholder="seu@email.com">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-300 mb-2">Bio</label>
                            <textarea rows="3" 
                                      class="w-full px-4 py-3 rounded-lg input-field text-white placeholder-zinc-500 resize-none"
                                      placeholder="Conte um pouco sobre você..."></textarea>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="px-6 py-3 rounded-lg btn-primary text-sm font-semibold">
                                Salvar Alterações
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Segurança -->
                <div class="profile-card rounded-2xl p-6 animate-fade-in">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center">
                            <i data-lucide="shield" class="w-5 h-5 text-white"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-white">Segurança</h3>
                            <p class="text-xs text-zinc-500">Gerencie sua senha e segurança</p>
                        </div>
                    </div>

                    <form class="space-y-5">
                        <div>
                            <label class="block text-sm font-medium text-zinc-300 mb-2">Senha Atual</label>
                            <input type="password" 
                                   class="w-full px-4 py-3 rounded-lg input-field text-white placeholder-zinc-500"
                                   placeholder="••••••••">
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-medium text-zinc-300 mb-2">Nova Senha</label>
                                <input type="password" 
                                       class="w-full px-4 py-3 rounded-lg input-field text-white placeholder-zinc-500"
                                       placeholder="••••••••">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-zinc-300 mb-2">Confirmar Nova Senha</label>
                                <input type="password" 
                                       class="w-full px-4 py-3 rounded-lg input-field text-white placeholder-zinc-500"
                                       placeholder="••••••••">
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="px-6 py-3 rounded-lg btn-secondary text-white text-sm font-semibold">
                                Alterar Senha
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Sessões Ativas -->
                <div class="profile-card rounded-2xl p-6 animate-fade-in">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center">
                            <i data-lucide="monitor" class="w-5 h-5 text-white"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-white">Sessões Ativas</h3>
                            <p class="text-xs text-zinc-500">Gerencie seus dispositivos conectados</p>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div class="p-4 rounded-xl bg-white/5 border border-white/5 flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-lg bg-white/5 border border-white/10 flex items-center justify-center">
                                    <i data-lucide="monitor" class="w-5 h-5 text-white"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-white">Sessão Atual</p>
                                    <p class="text-xs text-zinc-500"><?= $_SERVER['HTTP_USER_AGENT'] ?? 'Navegador desconhecido' ?></p>
                                </div>
                            </div>
                            <span class="px-3 py-1 bg-emerald-500/10 text-emerald-400 text-xs font-semibold rounded-full border border-emerald-500/20">
                                Ativa
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Zona de Perigo -->
                <div class="profile-card rounded-2xl p-6 border border-red-500/20 animate-fade-in">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 rounded-xl bg-red-500/10 border border-red-500/20 flex items-center justify-center">
                            <i data-lucide="alert-triangle" class="w-5 h-5 text-red-400"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-white">Zona de Perigo</h3>
                            <p class="text-xs text-zinc-500">Ações irreversíveis</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="p-4 rounded-xl bg-red-500/5 border border-red-500/10">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-white mb-1">Sair da Conta</p>
                                    <p class="text-xs text-zinc-400">Encerre sua sessão atual e saia do sistema</p>
                                </div>
                                <a href="../logout.php" class="px-6 py-3 rounded-lg btn-danger text-sm font-semibold">
                                    Sair
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>

