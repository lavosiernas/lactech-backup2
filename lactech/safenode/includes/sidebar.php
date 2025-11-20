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
    // TODO: Reativar proteção de URLs após corrigir problema de sessão
    return $route . '.php';
    
    /* Código original (desabilitado temporariamente)
    global $useProtectedUrls;
    if ($useProtectedUrls) {
        return SafeNodeRouter::url($route);
    }
    return $route . '.php';
    */
}

// Detectar página atual
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
if (isset($_GET['route'])) {
    $currentPage = 'dashboard'; // Ajustar conforme necessário
}
?>
<!-- Sidebar Component -->
<aside class="w-72 bg-black border-r border-white/10 flex flex-col h-full z-50 hidden md:flex">
    <div class="h-16 flex items-center px-6 border-b border-white/5">
        <div class="flex items-center gap-3 group cursor-pointer" onclick="window.location.href='index.php'">
            <div class="relative">
                <div class="absolute inset-0 bg-blue-500/20 blur-lg rounded-full group-hover:bg-blue-500/40 transition-all"></div>
                <img src="assets/img/logos (6).png" alt="SafeNode" class="h-8 w-auto relative z-10">
            </div>
            <span class="font-bold text-lg text-white tracking-tight group-hover:text-blue-400 transition-colors">SafeNode</span>
        </div>
    </div>
    <nav class="flex-1 overflow-y-auto py-6 px-4 space-y-1">
        <div class="px-3 mb-2 text-xs font-medium text-zinc-500 uppercase tracking-wider">Principal</div>
        <a href="<?php echo getSafeNodeUrl('dashboard'); ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium <?php echo $currentPage == 'dashboard' ? 'bg-blue-500/10 text-blue-400 border border-blue-500/20' : 'text-zinc-400 hover:bg-white/5 hover:text-white'; ?> transition-all">
            <i data-lucide="layout-dashboard" class="w-5 h-5"></i> Dashboard
        </a>
        <a href="<?php echo getSafeNodeUrl('sites'); ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium <?php echo $currentPage == 'sites' ? 'bg-blue-500/10 text-blue-400 border border-blue-500/20' : 'text-zinc-400 hover:bg-white/5 hover:text-white'; ?> transition-all group">
            <i data-lucide="globe" class="w-5 h-5 group-hover:text-blue-400 transition-colors"></i> Sites
        </a>
        <a href="<?php echo getSafeNodeUrl('logs'); ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium <?php echo $currentPage == 'logs' ? 'bg-blue-500/10 text-blue-400 border border-blue-500/20' : 'text-zinc-400 hover:bg-white/5 hover:text-white'; ?> transition-all group">
            <i data-lucide="shield-alert" class="w-5 h-5 group-hover:text-red-400 transition-colors"></i> Logs de Segurança
        </a>
        <a href="<?php echo getSafeNodeUrl('blocked'); ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium <?php echo $currentPage == 'blocked' ? 'bg-blue-500/10 text-blue-400 border border-blue-500/20' : 'text-zinc-400 hover:bg-white/5 hover:text-white'; ?> transition-all group">
            <i data-lucide="ban" class="w-5 h-5 group-hover:text-red-400 transition-colors"></i> IPs Bloqueados
        </a>
        <div class="px-3 mt-8 mb-2 text-xs font-medium text-zinc-500 uppercase tracking-wider">Sistema</div>
        <a href="<?php echo getSafeNodeUrl('settings'); ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium <?php echo $currentPage == 'settings' ? 'bg-blue-500/10 text-blue-400 border border-blue-500/20' : 'text-zinc-400 hover:bg-white/5 hover:text-white'; ?> transition-all group">
            <i data-lucide="settings" class="w-5 h-5 group-hover:text-zinc-300 transition-colors"></i> Configurações
        </a>
    </nav>
    <div class="p-4 border-t border-white/5 bg-zinc-900/30">
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
        <button onclick="window.location.href='login.php?logout=1'" class="w-full p-2 text-zinc-400 hover:text-red-400 hover:bg-red-500/10 rounded-lg transition-all text-left flex items-center gap-2" title="Sair">
            <i data-lucide="log-out" class="w-4 h-4"></i>
            <span class="text-sm">Sair</span>
        </button>
    </div>
</aside>

