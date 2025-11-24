<?php
// Iniciar sessão se necessário
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Carregar Router se estiver logado
$useProtectedUrls = false;
if (isset($_SESSION['safenode_logged_in']) && $_SESSION['safenode_logged_in'] === true) {
    require_once __DIR__ . '/Router.php';
    SafeNodeRouter::init();
    $useProtectedUrls = true;
}

// Função helper para gerar URLs
function getSafeNodeUrl($route) {
    // Temporariamente desabilitado - usar URLs diretas
    return $route . '.php';
}

// Detectar página atual
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
if (isset($_GET['route'])) {
    $currentPage = 'dashboard'; // Ajustar conforme necessário
}

// Buscar sites para o dropdown
$db = getSafeNodeDatabase();
$sidebarSites = [];
if ($db) {
    // SEGURANÇA: Mostrar apenas sites do usuário logado
    $userId = $_SESSION['safenode_user_id'] ?? null;
    $stmt = $db->prepare("SELECT id, domain, display_name FROM safenode_sites WHERE user_id = ? ORDER BY display_name ASC");
    $stmt->execute([$userId]);
    $sidebarSites = $stmt->fetchAll();
}

$currentSiteId = $_SESSION['view_site_id'] ?? 0;
$currentSiteName = $_SESSION['view_site_name'] ?? 'Visão Global';
?>
<!-- Sidebar Component -->
<aside id="safenode-sidebar" class="w-72 bg-black border-r border-white/10 flex flex-col h-full z-50 fixed inset-y-0 left-0 transform -translate-x-full md:translate-x-0 md:static md:flex transition-transform duration-200">
    <div class="h-16 flex items-center px-6 border-b border-white/5">
        <div class="flex items-center gap-3 group cursor-pointer" onclick="window.location.href='index.php'">
            <div class="relative">
                <div class="absolute inset-0 bg-blue-500/20 blur-lg rounded-full group-hover:bg-blue-500/40 transition-all"></div>
                <img src="assets/img/logos (6).png" alt="SafeNode" class="h-8 w-auto relative z-10">
            </div>
            <span class="font-bold text-lg text-white tracking-tight group-hover:text-blue-400 transition-colors">SafeNode</span>
        </div>
    </div>
    
    <!-- Site Selector -->
    <div class="px-4 pt-4">
        <div class="relative" x-data="{ open: false }">
            <button @click="open = !open" class="w-full flex items-center justify-between bg-zinc-900 border border-white/10 hover:border-white/20 text-white px-3 py-2.5 rounded-lg transition-all group">
                <div class="flex items-center gap-2 overflow-hidden">
                    <div class="w-2 h-2 rounded-full <?php echo $currentSiteId === 0 ? 'bg-blue-500' : 'bg-emerald-500'; ?>"></div>
                    <span class="text-sm font-medium truncate"><?php echo htmlspecialchars($currentSiteName); ?></span>
                </div>
                <i data-lucide="chevron-down" class="w-4 h-4 text-zinc-500 group-hover:text-white transition-colors" :class="{ 'rotate-180': open }"></i>
            </button>
            
            <div x-show="open" 
                 @click.away="open = false"
                 x-transition:enter="transition ease-out duration-100"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 class="absolute left-0 right-0 mt-2 bg-zinc-900 border border-white/10 rounded-lg shadow-xl z-50 overflow-hidden"
                 style="display: none;">
                <div class="max-h-64 overflow-y-auto py-1">
                    <a href="?view_site=0" class="flex items-center gap-2 px-3 py-2 text-sm text-zinc-300 hover:bg-white/5 hover:text-white transition-colors <?php echo $currentSiteId === 0 ? 'bg-white/5 text-white' : ''; ?>">
                        <div class="w-2 h-2 rounded-full bg-blue-500"></div>
                        Visão Global
                    </a>
                    <div class="h-px bg-white/5 my-1"></div>
                    <?php foreach ($sidebarSites as $site): ?>
                        <a href="?view_site=<?php echo $site['id']; ?>" class="flex items-center gap-2 px-3 py-2 text-sm text-zinc-300 hover:bg-white/5 hover:text-white transition-colors <?php echo $currentSiteId === $site['id'] ? 'bg-white/5 text-white' : ''; ?>">
                            <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
                            <?php echo htmlspecialchars($site['display_name'] ?: $site['domain']); ?>
                        </a>
                    <?php endforeach; ?>
                    <div class="h-px bg-white/5 my-1"></div>
                    <a href="sites.php" class="flex items-center gap-2 px-3 py-2 text-xs text-blue-400 hover:bg-blue-500/10 transition-colors">
                        <i data-lucide="plus" class="w-3 h-3"></i> Adicionar Site
                    </a>
                </div>
            </div>
        </div>
    </div>

    <nav class="flex-1 overflow-y-auto py-4 px-4 space-y-1">
        <div class="px-3 mb-2 text-xs font-medium text-zinc-500 uppercase tracking-wider">Principal</div>
        <a href="<?php echo getSafeNodeUrl('dashboard'); ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium <?php echo $currentPage == 'dashboard' ? 'bg-blue-500/10 text-blue-400 border border-blue-500/20' : 'text-zinc-400 hover:bg-white/5 hover:text-white'; ?> transition-all">
            <i data-lucide="layout-dashboard" class="w-5 h-5"></i> Dashboard
        </a>
        <a href="<?php echo getSafeNodeUrl('sites'); ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium <?php echo $currentPage == 'sites' ? 'bg-blue-500/10 text-blue-400 border border-blue-500/20' : 'text-zinc-400 hover:bg-white/5 hover:text-white'; ?> transition-all group">
            <i data-lucide="globe" class="w-5 h-5 group-hover:text-blue-400 transition-colors"></i> Sites
        </a>
        <a href="<?php echo getSafeNodeUrl('incidents'); ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium <?php echo $currentPage == 'incidents' ? 'bg-blue-500/10 text-blue-400 border border-blue-500/20' : 'text-zinc-400 hover:bg-white/5 hover:text-white'; ?> transition-all group">
            <i data-lucide="alert-triangle" class="w-5 h-5 group-hover:text-amber-400 transition-colors"></i> Incidentes
        </a>
        <a href="<?php echo getSafeNodeUrl('logs'); ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium <?php echo $currentPage == 'logs' ? 'bg-blue-500/10 text-blue-400 border border-blue-500/20' : 'text-zinc-400 hover:bg-white/5 hover:text-white'; ?> transition-all group">
            <i data-lucide="shield-alert" class="w-5 h-5 group-hover:text-red-400 transition-colors"></i> Logs de Segurança
        </a>
        <a href="<?php echo getSafeNodeUrl('blocked'); ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium <?php echo $currentPage == 'blocked' ? 'bg-blue-500/10 text-blue-400 border border-blue-500/20' : 'text-zinc-400 hover:bg-white/5 hover:text-white'; ?> transition-all group">
            <i data-lucide="ban" class="w-5 h-5 group-hover:text-red-400 transition-colors"></i> IPs Bloqueados
        </a>
        <div class="px-3 mt-8 mb-2 text-xs font-medium text-zinc-500 uppercase tracking-wider">Sistema</div>
        <a href="<?php echo getSafeNodeUrl('payments'); ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium <?php echo $currentPage == 'payments' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'text-zinc-400 hover:bg-white/5 hover:text-white'; ?> transition-all group">
            <i data-lucide="credit-card" class="w-5 h-5 group-hover:text-emerald-400 transition-colors"></i> Pagamentos
        </a>
        <a href="<?php echo getSafeNodeUrl('settings'); ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium <?php echo $currentPage == 'settings' ? 'bg-blue-500/10 text-blue-400 border border-blue-500/20' : 'text-zinc-400 hover:bg-white/5 hover:text-white'; ?> transition-all group">
            <i data-lucide="settings" class="w-5 h-5 group-hover:text-zinc-300 transition-colors"></i> Configurações
        </a>
        <a href="<?php echo getSafeNodeUrl('updates'); ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium <?php echo $currentPage == 'updates' ? 'bg-blue-500/10 text-blue-400 border border-blue-500/20' : 'text-zinc-400 hover:bg-white/5 hover:text-white'; ?> transition-all group">
            <i data-lucide="sparkles" class="w-5 h-5 group-hover:text-purple-400 transition-colors"></i> Atualizações
        </a>
    </nav>
    <div class="p-4 border-t border-white/5">
        <button onclick="window.location.href='<?php echo getSafeNodeUrl('profile'); ?>'" class="w-full flex items-center gap-3 hover:bg-white/5 rounded-lg p-2 transition-all group mb-2">
            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-600 to-purple-600 flex items-center justify-center text-white font-bold text-sm shadow-lg shadow-blue-500/20 group-hover:scale-105 transition-transform">
                <?php echo strtoupper(substr($_SESSION['safenode_username'] ?? 'U', 0, 1)); ?>
            </div>
            <div class="flex-1 min-w-0 text-left">
                <p class="text-sm font-medium text-white truncate"><?php echo htmlspecialchars($_SESSION['safenode_username'] ?? 'Admin'); ?></p>
                <p class="text-xs text-zinc-500 truncate">Ver perfil</p>
            </div>
            <i data-lucide="chevron-right" class="w-4 h-4 text-zinc-500 group-hover:text-white transition-colors"></i>
        </button>
        <button type="button" data-logout-trigger class="w-full p-2 text-zinc-400 hover:text-red-400 hover:bg-red-500/10 rounded-lg transition-all text-left flex items-center gap-2" title="Sair">
            <i data-lucide="log-out" class="w-4 h-4"></i>
            <span class="text-sm">Sair</span>
        </button>
    </div>
</aside>

<!-- Backdrop para mobile -->
<div id="safenode-sidebar-backdrop" class="fixed inset-0 bg-black/60 z-40 hidden md:hidden"></div>

<!-- Modal de confirmação de saída -->
<div id="safenode-logout-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 backdrop-blur-sm">
    <div class="w-full max-w-sm mx-4 rounded-2xl bg-zinc-950 border border-white/10 p-6 shadow-2xl">
        <h3 class="text-lg font-bold text-white mb-2">Deseja realmente sair?</h3>
        <p class="text-sm text-zinc-400 mb-6">Você será desconectado do painel SafeNode e precisará fazer login novamente para acessar o sistema.</p>
        <div class="flex gap-3 justify-end">
            <button type="button" data-logout-cancel class="px-4 py-2 rounded-xl bg-zinc-900 text-zinc-300 hover:bg-zinc-800 text-sm font-semibold transition-all">
                Cancelar
            </button>
            <button type="button" data-logout-confirm class="px-4 py-2 rounded-xl bg-red-600 text-white hover:bg-red-700 text-sm font-semibold transition-all">
                Sair do sistema
            </button>
        </div>
    </div>
</div>

<script>
(function() {
    const sidebar = document.getElementById('safenode-sidebar');
    const backdrop = document.getElementById('safenode-sidebar-backdrop');
    const logoutModal = document.getElementById('safenode-logout-modal');
    if (!sidebar) return;

    function openSidebar() {
        sidebar.classList.remove('-translate-x-full');
        if (backdrop) backdrop.classList.remove('hidden');
    }

    function closeSidebar() {
        sidebar.classList.add('-translate-x-full');
        if (backdrop) backdrop.classList.add('hidden');
    }

    function toggleSidebar() {
        if (sidebar.classList.contains('-translate-x-full')) {
            openSidebar();
        } else {
            closeSidebar();
        }
    }

    document.addEventListener('click', function(e) {
        const toggleBtn = e.target.closest('[data-sidebar-toggle]');
        if (toggleBtn) {
            e.preventDefault();
            toggleSidebar();
            return;
        }

        const logoutBtn = e.target.closest('[data-logout-trigger]');
        if (logoutBtn && logoutModal) {
            e.preventDefault();
            logoutModal.classList.remove('hidden');
            logoutModal.classList.add('flex');
        }
    });

    if (logoutModal) {
        const cancelBtn = logoutModal.querySelector('[data-logout-cancel]');
        const confirmBtn = logoutModal.querySelector('[data-logout-confirm]');

        if (cancelBtn) {
            cancelBtn.addEventListener('click', function() {
                logoutModal.classList.add('hidden');
                logoutModal.classList.remove('flex');
            });
        }

        if (confirmBtn) {
            confirmBtn.addEventListener('click', function() {
                window.location.href = 'login.php?logout=1';
            });
        }

        logoutModal.addEventListener('click', function(e) {
            if (e.target === logoutModal) {
                logoutModal.classList.add('hidden');
                logoutModal.classList.remove('flex');
            }
        });
    }

    if (backdrop) {
        backdrop.addEventListener('click', closeSidebar);
    }

    window.addEventListener('resize', function() {
        if (window.innerWidth >= 768) {
            sidebar.classList.remove('-translate-x-full');
            if (backdrop) backdrop.classList.add('hidden');
        } else {
            sidebar.classList.add('-translate-x-full');
        }
    });
})();
</script>
