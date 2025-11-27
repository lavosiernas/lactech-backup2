<?php

session_start();

require_once __DIR__ . '/includes/SecurityHelpers.php';
SecurityHeaders::apply();

if (!isset($_SESSION['safenode_logged_in']) || $_SESSION['safenode_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/Settings.php';
require_once __DIR__ . '/includes/Alert.php';

$db = getSafeNodeDatabase();
$currentSiteId = $_SESSION['view_site_id'] ?? 0;
$selectedSite = null;
$dashboardFlash = $_SESSION['safenode_dashboard_message'] ?? '';
$dashboardFlashType = $_SESSION['safenode_dashboard_message_type'] ?? 'success';
unset($_SESSION['safenode_dashboard_message'], $_SESSION['safenode_dashboard_message_type']);

if ($db && $currentSiteId > 0) {
    try {
        $userId = $_SESSION['safenode_user_id'] ?? null;
        $stmt = $db->prepare("SELECT * FROM safenode_sites WHERE id = ? AND user_id = ?");
        $stmt->execute([$currentSiteId, $userId]);
        $selectedSite = $stmt->fetch();
    } catch (PDOException $e) {
        $selectedSite = null;
    }
}

if ($db && $currentSiteId > 0 && $selectedSite && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_under_attack'])) {
    $newLevel = $selectedSite['security_level'] === 'under_attack' ? 'high' : 'under_attack';
    try {
        $stmt = $db->prepare("UPDATE safenode_sites SET security_level = ? WHERE id = ?");
        $stmt->execute([$newLevel, $currentSiteId]);
        $_SESSION['safenode_dashboard_message'] = $newLevel === 'under_attack'
            ? 'Modo “Sob Ataque” ativado para este site.'
            : 'Modo “Sob Ataque” desativado.';
        $_SESSION['safenode_dashboard_message_type'] = $newLevel === 'under_attack' ? 'warning' : 'success';
    } catch (PDOException $e) {
        $_SESSION['safenode_dashboard_message'] = 'Não foi possível atualizar o modo de proteção.';
        $_SESSION['safenode_dashboard_message_type'] = 'error';
    }
    header('Location: dashboard.php');
    exit;
}

// Verificar se há sites configurados (apenas para exibir mensagem)
$hasSites = false;
if ($db) {
    try {
        $userId = $_SESSION['safenode_user_id'] ?? null;
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM safenode_sites WHERE is_active = 1 AND user_id = ?");
        $stmt->execute([$userId]);
        $sitesResult = $stmt->fetch();
        $hasSites = ($sitesResult['total'] ?? 0) > 0;
        } catch (PDOException $e) {
    $hasSites = false;
}
}
?>
<!DOCTYPE html>
<html lang="pt-BR" class="dark h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | SafeNode</title>
    <link rel="icon" type="image/png" href="assets/img/logos (6).png">
    <link rel="shortcut icon" type="image/png" href="assets/img/logos (6).png">
    <link rel="apple-touch-icon" href="assets/img/logos (6).png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        mono: ['JetBrains Mono', 'monospace'],
                    },
                    colors: {
                        black: '#000000',
                        zinc: {
                            850: '#1f2937',
                            900: '#18181b',
                            950: '#09090b',
                        }
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-out',
                        'slide-up': 'slideUp 0.5s ease-out forwards',
                        'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                    },
                    keyframes: {
                        fadeIn: {
                            '0%': { opacity: '0' },
                            '100%': { opacity: '1' },
                        },
                        slideUp: {
                            '0%': { opacity: '0', transform: 'translateY(20px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' },
                        }
                    }
                }
            }
        }
    </script>
    <style>
        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #3f3f46; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #52525b; }

        /* Glass Components Melhorados */
        .glass-panel {
            background: rgba(24, 24, 27, 0.6);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .glass-card {
            background: linear-gradient(180deg, rgba(39, 39, 42, 0.5) 0%, rgba(24, 24, 27, 0.5) 100%);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.05), inset 0 1px 0 rgba(255, 255, 255, 0.1);
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

        /* Stat Card Hover */
        .stat-card-hover {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .stat-card-hover:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4), 0 0 20px rgba(59, 130, 246, 0.1);
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

        /* Status Pulse */
        .status-pulse {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        /* Alpine.js x-cloak */
        [x-cloak] { display: none !important; }
    </style>
</head>
<body x-data="{ notificationsOpen: false }" class="bg-black text-zinc-200 font-sans h-full overflow-hidden flex selection:bg-blue-500/30">

    <!-- Sidebar com Seletor de Site -->
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col h-full relative overflow-hidden bg-black">
        <!-- Header -->
        <header class="h-16 border-b border-white/5 bg-black/50 backdrop-blur-xl sticky top-0 z-40 px-6 flex items-center justify-between">
                <div class="flex items-center gap-4 md:hidden">
                <button class="text-zinc-400 hover:text-white transition-colors" data-sidebar-toggle>
                    <i data-lucide="menu" class="w-6 h-6"></i>
                </button>
                <span class="font-bold text-lg text-white">SafeNode</span>
            </div>

            <div class="hidden md:flex items-center gap-3">
                <div class="w-0.5 h-6 bg-gradient-to-b from-blue-500 to-purple-500 rounded-full"></div>
                <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg modern-badge text-xs font-bold <?php echo $hasSites ? 'bg-emerald-500/15 text-emerald-400 border-emerald-500/30' : 'bg-amber-500/15 text-amber-400 border-amber-500/30'; ?>">
                    <span class="w-2 h-2 rounded-full <?php echo $hasSites ? 'bg-emerald-500' : 'bg-amber-500'; ?> status-pulse shadow-lg"></span>
                    Sistema <?php echo $hasSites ? 'Ativo' : 'Inativo'; ?>
                </div>
                <div class="h-4 w-px bg-white/10"></div>
                <div class="text-xs text-zinc-400 font-mono font-semibold">
                    <?php echo htmlspecialchars($_SESSION['view_site_name'] ?? 'Visão Global'); ?>
                </div>
                <?php if ($currentSiteId > 0 && $selectedSite): ?>
                <div class="h-4 w-px bg-white/10"></div>
                <form method="POST">
                    <input type="hidden" name="toggle_under_attack" value="1">
                    <?php $underAttack = $selectedSite['security_level'] === 'under_attack'; ?>
                    <button type="submit" class="modern-badge inline-flex items-center gap-2 px-3 py-1.5 text-xs font-bold rounded-lg transition-all <?php echo $underAttack ? 'bg-red-500/15 text-red-400 border-red-500/30 hover:bg-red-500/25' : 'bg-zinc-900/60 text-zinc-300 border-white/10 hover:border-white/20'; ?>">
                        <span class="w-2 h-2 rounded-full <?php echo $underAttack ? 'bg-red-400 status-pulse shadow-lg shadow-red-400/50' : 'bg-zinc-500'; ?>"></span>
                        <?php echo $underAttack ? 'Sob Ataque ATIVO' : 'Sob Ataque DESLIGADO'; ?>
                    </button>
                </form>
                <?php endif; ?>
            </div>

            <div class="flex items-center gap-3">
                <button onclick="location.reload()" class="p-2.5 text-zinc-400 hover:text-white hover:bg-white/5 rounded-lg transition-all border border-transparent hover:border-white/10" title="Atualizar">
                    <i data-lucide="refresh-cw" class="w-5 h-5"></i>
                </button>
                <div class="relative">
                    <button @click="notificationsOpen = !notificationsOpen" class="p-2.5 text-zinc-400 hover:text-white hover:bg-white/5 rounded-lg transition-all relative border border-transparent hover:border-white/10">
                        <i data-lucide="bell" class="w-5 h-5"></i>
                        <span class="absolute top-1.5 right-1.5 w-2.5 h-2.5 bg-red-500 rounded-full border-2 border-black status-pulse shadow-lg shadow-red-500/50"></span>
                    </button>
                </div>
                <button onclick="window.location.href='profile.php'" class="hidden md:flex items-center gap-2 px-3 py-2 text-zinc-400 hover:text-white hover:bg-white/5 rounded-lg transition-all border border-transparent hover:border-white/10">
                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-600 to-purple-600 flex items-center justify-center text-white font-black text-xs shadow-lg border-2 border-white/10">
                        <?php echo strtoupper(substr($_SESSION['safenode_username'] ?? 'U', 0, 1)); ?>
                    </div>
                    <span class="text-sm font-bold hidden lg:block"><?php echo htmlspecialchars($_SESSION['safenode_username'] ?? 'Admin'); ?></span>
                </button>
            </div>
        </header>

        <!-- Scrollable Content -->
        <div class="flex-1 overflow-y-auto p-6 md:p-8 z-10 scroll-smooth">
            <div class="max-w-4xl mx-auto">
                <?php if (!empty($dashboardFlash)): ?>
                <div class="rounded-xl p-4 <?php echo $dashboardFlashType === 'warning' ? 'bg-amber-500/10 border border-amber-500/30 text-amber-200' : ($dashboardFlashType === 'error' ? 'bg-red-500/10 border border-red-500/30 text-red-200' : 'bg-emerald-500/10 border border-emerald-500/30 text-emerald-200'); ?> flex items-center gap-3 animate-fade-in shadow-lg mb-8">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 rounded-lg <?php echo $dashboardFlashType === 'warning' ? 'bg-amber-500/20 border border-amber-500/30' : ($dashboardFlashType === 'error' ? 'bg-red-500/20 border border-red-500/30' : 'bg-emerald-500/20 border border-emerald-500/30'); ?> flex items-center justify-center">
                            <i data-lucide="<?php echo $dashboardFlashType === 'error' ? 'alert-triangle' : ($dashboardFlashType === 'warning' ? 'shield' : 'check-circle'); ?>" class="w-5 h-5"></i>
                    </div>
                    </div>
                    <p class="flex-1 font-bold"><?php echo htmlspecialchars($dashboardFlash); ?></p>
                </div>
                <?php endif; ?>
                
                <!-- Mensagem de Reformulação -->
                <div class="glass-card rounded-2xl p-8 md:p-12 text-center">
                    <div class="max-w-2xl mx-auto">
                        <div class="w-20 h-20 mx-auto mb-6 rounded-full bg-blue-500/10 border border-blue-500/20 flex items-center justify-center">
                            <i data-lucide="refresh-cw" class="w-10 h-10 text-blue-400"></i>
                            </div>
                        <h1 class="text-3xl md:text-4xl font-bold text-white mb-4">Sistema em Reformulação</h1>
                        <p class="text-lg text-zinc-400 mb-6">
                            O SafeNode está passando por uma reformulação completa para oferecer uma experiência ainda melhor.
                        </p>
                        <div class="bg-emerald-500/10 border border-emerald-500/20 rounded-xl p-4 mb-6">
                            <p class="text-emerald-400 font-semibold">
                                ✓ Todos os seus dados estão seguros e preservados
                            </p>
                        </div>
                        <p class="text-sm text-zinc-500">
                            Em breve você terá acesso a todas as novas funcionalidades. Obrigado pela paciência!
                                            </p>
                                            </div>
                                        </div>
            </div>
        </div>
    </main>

    <!-- Modal Lateral de Notificações - Redesign -->
    <div x-show="notificationsOpen" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="translate-x-full opacity-0"
         x-transition:enter-end="translate-x-0 opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="translate-x-0 opacity-100"
         x-transition:leave-end="translate-x-full opacity-0"
         @click.away="notificationsOpen = false"
         class="fixed right-0 top-0 h-full w-96 bg-black/95 backdrop-blur-xl border-l border-white/10 z-50 shadow-2xl relative overflow-hidden"
         x-cloak>
        <!-- Grid pattern -->
        <div class="absolute inset-0 grid-pattern opacity-20"></div>
        
        <!-- Decoração de fundo -->
        <div class="absolute top-0 left-0 w-40 h-40 bg-blue-500/5 rounded-full blur-3xl"></div>
        
        <div class="flex flex-col h-full relative z-10">
            <!-- Header do Modal -->
            <div class="p-6 border-b border-white/10 flex items-center justify-between bg-zinc-900/30">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-500/15 border border-blue-500/30 flex items-center justify-center">
                        <i data-lucide="bell" class="w-5 h-5 text-blue-400"></i>
                    </div>
                <div>
                    <h3 class="text-lg font-bold text-white">Notificações</h3>
                        <p class="text-xs text-zinc-400 mt-0.5 font-medium">Alertas e eventos recentes</p>
                    </div>
                </div>
                <button @click="notificationsOpen = false" class="p-2 text-zinc-400 hover:text-white hover:bg-white/5 rounded-lg transition-all">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Lista de Notificações -->
            <div class="flex-1 overflow-y-auto p-4 space-y-3">
                <!-- Mensagem quando não há notificações -->
                <div class="text-center py-12">
                    <div class="w-16 h-16 bg-zinc-900/60 rounded-xl border border-white/5 flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="bell-off" class="w-8 h-8 text-zinc-500"></i>
                    </div>
                    <p class="text-sm text-zinc-400 font-bold mb-1">Nenhuma notificação nova</p>
                    <p class="text-xs text-zinc-500 font-medium">Você será notificado quando houver novos eventos</p>
                </div>
            </div>

            <!-- Footer -->
            <div class="p-4 border-t border-white/10 bg-zinc-900/30">
                <button class="w-full px-4 py-2.5 text-sm text-blue-400 hover:text-blue-300 font-bold transition-colors rounded-lg hover:bg-blue-500/10 border border-blue-500/20 hover:border-blue-500/30">
                    Ver todas as notificações
                </button>
            </div>
        </div>
    </div>

    <script>
        // Initialize Lucide Icons
        lucide.createIcons();

        // Update time
        setInterval(() => {
            const updateEl = document.getElementById('lastUpdate');
            if (updateEl) {
                updateEl.textContent = new Date().toLocaleTimeString();
            }
        }, 1000);
        
        // Modal de Atualização do Sistema
        (function() {
            const updateModal = document.getElementById('update-modal');
            const shouldShowModal = false;
            
            if (shouldShowModal && updateModal) {
                setTimeout(() => {
                    updateModal.classList.remove('hidden');
                    updateModal.classList.add('flex');
                    lucide.createIcons();
                }, 1000);
            }
            
            const closeModal = () => {
                if (updateModal) {
                    updateModal.classList.add('hidden');
                    updateModal.classList.remove('flex');
                    fetch('api/close-update-modal.php').catch(() => {});
                }
            };
            
            const closeBtn = document.getElementById('update-modal-close');
            if (closeBtn) {
                closeBtn.addEventListener('click', closeModal);
            }
            
            const understoodBtn = document.getElementById('update-modal-understood');
            if (understoodBtn) {
                understoodBtn.addEventListener('click', closeModal);
            }
            
            const seeUpdatesBtn = document.getElementById('update-modal-see-updates');
            if (seeUpdatesBtn) {
                seeUpdatesBtn.addEventListener('click', () => {
                    closeModal();
                    window.location.href = 'updates.php';
                });
            }
            
            if (updateModal) {
                updateModal.addEventListener('click', (e) => {
                    if (e.target === updateModal) {
                        closeModal();
                    }
                });
            }
        })();
    </script>
    
    <!-- Modal de Atualização (desabilitado por padrão) -->
    <div id="update-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/90 backdrop-blur-md">
        <div class="w-full max-w-lg mx-4 rounded-2xl bg-zinc-950 border border-white/10 p-6 shadow-2xl relative">
            <button id="update-modal-close" class="absolute top-4 right-4 p-2 text-zinc-400 hover:text-white hover:bg-white/5 rounded-lg transition-all">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
                <div class="text-center mb-6">
                <h2 class="text-xl font-bold text-white mb-2">Sistema Atualizado</h2>
                <p class="text-zinc-400 text-sm">Novas funcionalidades disponíveis</p>
                    </div>
            <div class="flex gap-3">
                <button id="update-modal-understood" class="flex-1 px-4 py-2 bg-zinc-900 text-zinc-300 hover:bg-zinc-800 rounded-lg text-sm font-semibold transition-all">
                        Entendi
                    </button>
                <button id="update-modal-see-updates" class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-semibold transition-all">
                        Ver Atualizações
                    </button>
            </div>
        </div>
    </div>
</body>
</html>
