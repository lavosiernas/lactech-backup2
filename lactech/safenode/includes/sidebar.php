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

// Função helper para gerar URLs com token de segurança
if (!function_exists('getSafeNodeUrl')) {
    function getSafeNodeUrl($route, $siteId = null) {
        $pagePath = strpos($route, '.php') !== false ? $route : $route . '.php';
        
        // Se usuário está logado, adicionar token
        if (isset($_SESSION['safenode_logged_in']) && $_SESSION['safenode_logged_in'] === true) {
            $userId = $_SESSION['safenode_user_id'] ?? null;
            if ($userId) {
                require_once __DIR__ . '/SecurityToken.php';
                $tokenManager = new SecurityToken();
                
                // Usar site_id fornecido ou da sessão
                $currentSiteId = $siteId !== null ? $siteId : ($_SESSION['view_site_id'] ?? 0);
                $token = $tokenManager->generateToken($userId, $currentSiteId);
                
                if ($token) {
                    return $pagePath . '?token=' . $token;
                }
            }
        }
        
        return $pagePath;
    }
}

// Detectar página atual
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
if (isset($_GET['route'])) {
    $currentPage = 'dashboard'; // Ajustar conforme necessário
}

// Buscar sequência de proteção
$protectionStreak = null;
if (isset($_SESSION['safenode_logged_in']) && $_SESSION['safenode_logged_in'] === true) {
    $userId = $_SESSION['safenode_user_id'] ?? null;
    $siteId = $_SESSION['view_site_id'] ?? 0;
    
    if ($userId) {
        require_once __DIR__ . '/ProtectionStreak.php';
        $streakManager = new ProtectionStreak();
        $protectionStreak = $streakManager->getStreak($userId, $siteId);
    }
}
?>
<style>
    .nav-item {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 12px 16px;
        border-radius: 12px;
        color: #52525b;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        cursor: pointer;
        position: relative;
        overflow: hidden;
        text-decoration: none;
    }
    
    .nav-item::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(90deg, rgba(255, 255, 255, 0.2) 0%, transparent 100%);
        opacity: 0;
        transition: opacity 0.3s;
    }
    
    .nav-item:hover {
        color: #ffffff;
    }
    
    .nav-item:hover::before {
        opacity: 0.5;
    }
    
    .nav-item.active {
        color: #ffffff;
        background: linear-gradient(90deg, rgba(255, 255, 255, 0.1) 0%, transparent 100%);
    }
    
    .nav-item.active::before {
        opacity: 1;
    }
    
    .nav-item.active::after {
        content: '';
        position: absolute;
        left: 0;
        top: 50%;
        transform: translateY(-50%);
        width: 3px;
        height: 24px;
        background: #ffffff;
        border-radius: 0 4px 4px 0;
        box-shadow: 0 0 20px rgba(255, 255, 255, 0.2);
    }
    
    .sidebar {
        background: linear-gradient(180deg, #080808 0%, #030303 100%);
        border-right: 1px solid rgba(255,255,255,0.04);
        position: relative;
    }
    
    .sidebar::after {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 1px;
        height: 100%;
        background: linear-gradient(180deg, transparent 0%, rgba(255, 255, 255, 0.2) 50%, transparent 100%);
        opacity: 0.5;
    }
    
    .upgrade-card {
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 12px;
        padding: 16px;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #ffffff 0%, #e5e5e5 100%);
        color: #000;
        font-weight: 600;
        padding: 12px 24px;
        border-radius: 12px;
        border: none;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
        width: 100%;
    }
    
    .btn-primary::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, rgba(255,255,255,0.2) 0%, transparent 50%);
        opacity: 0;
        transition: opacity 0.3s;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 30px rgba(255,255,255,0.2);
    }
    
    .btn-primary:hover::before {
        opacity: 1;
    }
</style>
<!-- Sidebar Component -->
<aside id="safenode-sidebar" x-data="{ sidebarCollapsed: false }" 
       :class="sidebarCollapsed ? 'w-20' : 'w-72'" 
       class="sidebar h-full flex-shrink-0 flex flex-col hidden lg:flex transition-all duration-300 ease-in-out overflow-hidden">
    <!-- Logo -->
    <div class="p-4 border-b border-white/5 flex-shrink-0 relative">
        <div class="flex items-center" :class="sidebarCollapsed ? 'justify-center flex-col gap-3' : 'justify-between'">
            <div class="flex items-center gap-3" :class="sidebarCollapsed ? 'justify-center' : ''">
                <div class="relative">
                    <img src="assets/img/logos (6).png" alt="SafeNode Logo" class="w-8 h-8 object-contain flex-shrink-0">
                    <?php if ($protectionStreak && $protectionStreak['enabled'] && $protectionStreak['is_active']): ?>
                    <!-- Badge de Sequência (Foguinho) -->
                    <div class="absolute -top-1 -right-1 bg-gradient-to-br from-orange-500 to-red-600 rounded-full p-1 shadow-lg border-2 border-dark-900" 
                         x-data="{ showTooltip: false }"
                         @mouseenter="showTooltip = true"
                         @mouseleave="showTooltip = false">
                        <i data-lucide="flame" class="w-3 h-3 text-white"></i>
                        <!-- Tooltip -->
                        <div x-show="showTooltip" 
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             class="absolute left-1/2 -translate-x-1/2 bottom-full mb-2 px-3 py-2 bg-dark-800 border border-white/10 rounded-lg shadow-xl whitespace-nowrap z-50"
                             style="display: none;">
                            <div class="text-xs font-semibold text-white mb-1">Sequência de Proteção</div>
                            <div class="text-sm font-bold text-orange-400"><?php echo $protectionStreak['current_streak']; ?> dias</div>
                            <?php if ($protectionStreak['longest_streak'] > $protectionStreak['current_streak']): ?>
                            <div class="text-xs text-zinc-400 mt-1">Recorde: <?php echo $protectionStreak['longest_streak']; ?> dias</div>
                            <?php endif; ?>
                            <div class="absolute left-1/2 -translate-x-1/2 top-full w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-white/10"></div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <div x-show="!sidebarCollapsed" 
                     x-transition:enter="transition ease-out duration-200" 
                     x-transition:enter-start="opacity-0 -translate-x-2" 
                     x-transition:enter-end="opacity-100 translate-x-0" 
                     x-transition:leave="transition ease-in duration-150" 
                     x-transition:leave-start="opacity-100 translate-x-0" 
                     x-transition:leave-end="opacity-0 -translate-x-2" 
                     class="overflow-hidden whitespace-nowrap">
                    <h1 class="font-bold text-white text-xl tracking-tight">SafeNode</h1>
                    <p class="text-xs text-zinc-500 font-medium">Security Platform</p>
                </div>
            </div>
            <button @click="sidebarCollapsed = !sidebarCollapsed; setTimeout(() => lucide.createIcons(), 50)" 
                    class="text-zinc-600 hover:text-zinc-400 transition-colors flex-shrink-0" 
                    :class="sidebarCollapsed ? 'mt-2' : ''">
                <i :data-lucide="sidebarCollapsed ? 'chevrons-right' : 'chevrons-left'" class="w-5 h-5"></i>
            </button>
        </div>
    </div>
    
    <!-- Navigation -->
    <nav class="flex-1 p-4 space-y-1 overflow-y-auto overflow-x-hidden">
        <p x-show="!sidebarCollapsed" 
           x-transition:enter="transition ease-out duration-200" 
           x-transition:enter-start="opacity-0" 
           x-transition:enter-end="opacity-100" 
           x-transition:leave="transition ease-in duration-150" 
           x-transition:leave-start="opacity-100" 
           x-transition:leave-end="opacity-0" 
           class="text-xs font-semibold text-zinc-600 uppercase tracking-wider mb-3 px-3 whitespace-nowrap">Principal</p>
        
        <a href="<?php echo getSafeNodeUrl('dashboard'); ?>" 
           class="nav-item <?php echo $currentPage == 'dashboard' ? 'active' : ''; ?>" 
           :class="sidebarCollapsed ? 'justify-center px-2' : ''" 
           :title="sidebarCollapsed ? 'Dashboard' : ''">
            <i data-lucide="layout-dashboard" class="w-5 h-5 flex-shrink-0"></i>
            <span x-show="!sidebarCollapsed" 
                  x-transition:enter="transition ease-out duration-200" 
                  x-transition:enter-start="opacity-0 -translate-x-2" 
                  x-transition:enter-end="opacity-100 translate-x-0" 
                  x-transition:leave="transition ease-in duration-150" 
                  x-transition:leave-start="opacity-100 translate-x-0" 
                  x-transition:leave-end="opacity-0 -translate-x-2" 
                  class="font-medium whitespace-nowrap">Dashboard</span>
        </a>
        <a href="<?php echo getSafeNodeUrl('sites'); ?>" 
           class="nav-item <?php echo $currentPage == 'sites' ? 'active' : ''; ?>" 
           :class="sidebarCollapsed ? 'justify-center px-2' : ''" 
           :title="sidebarCollapsed ? 'Gerenciar Sites' : ''">
            <i data-lucide="globe" class="w-5 h-5 flex-shrink-0"></i>
            <span x-show="!sidebarCollapsed" 
                  x-transition:enter="transition ease-out duration-200" 
                  x-transition:enter-start="opacity-0 -translate-x-2" 
                  x-transition:enter-end="opacity-100 translate-x-0" 
                  x-transition:leave="transition ease-in duration-150" 
                  x-transition:leave-start="opacity-100 translate-x-0" 
                  x-transition:leave-end="opacity-0 -translate-x-2" 
                  class="font-medium whitespace-nowrap">Gerenciar Sites</span>
        </a>
        
        <div class="pt-4 mt-4 border-t border-white/5">
            <p x-show="!sidebarCollapsed" 
               x-transition:enter="transition ease-out duration-200" 
               x-transition:enter-start="opacity-0" 
               x-transition:enter-end="opacity-100" 
               x-transition:leave="transition ease-in duration-150" 
               x-transition:leave-start="opacity-100" 
               x-transition:leave-end="opacity-0" 
               class="text-xs font-semibold text-zinc-600 uppercase tracking-wider mb-3 px-3 whitespace-nowrap">Análises</p>
            <a href="<?php echo getSafeNodeUrl('logs'); ?>" 
               class="nav-item <?php echo $currentPage == 'logs' ? 'active' : ''; ?>" 
               :class="sidebarCollapsed ? 'justify-center px-2' : ''" 
               :title="sidebarCollapsed ? 'Explorar Logs' : ''">
                <i data-lucide="file-text" class="w-5 h-5 flex-shrink-0"></i>
                <span x-show="!sidebarCollapsed" 
                      x-transition:enter="transition ease-out duration-200" 
                      x-transition:enter-start="opacity-0 -translate-x-2" 
                      x-transition:enter-end="opacity-100 translate-x-0" 
                      x-transition:leave="transition ease-in duration-150" 
                      x-transition:leave-start="opacity-100 translate-x-0" 
                      x-transition:leave-end="opacity-0 -translate-x-2" 
                      class="font-medium whitespace-nowrap">Explorar Logs</span>
            </a>
            <a href="<?php echo getSafeNodeUrl('behavior-analysis'); ?>" 
               class="nav-item <?php echo $currentPage == 'behavior-analysis' ? 'active' : ''; ?>" 
               :class="sidebarCollapsed ? 'justify-center px-2' : ''" 
               :title="sidebarCollapsed ? 'Comportamental' : ''">
                <i data-lucide="brain" class="w-5 h-5 flex-shrink-0"></i>
                <span x-show="!sidebarCollapsed" 
                      x-transition:enter="transition ease-out duration-200" 
                      x-transition:enter-start="opacity-0 -translate-x-2" 
                      x-transition:enter-end="opacity-100 translate-x-0" 
                      x-transition:leave="transition ease-in duration-150" 
                      x-transition:leave-start="opacity-100 translate-x-0" 
                      x-transition:leave-end="opacity-0 -translate-x-2" 
                      class="font-medium whitespace-nowrap">Comportamental</span>
            </a>
            <a href="<?php echo getSafeNodeUrl('security-analytics'); ?>" 
               class="nav-item <?php echo $currentPage == 'security-analytics' ? 'active' : ''; ?>" 
               :class="sidebarCollapsed ? 'justify-center px-2' : ''" 
               :title="sidebarCollapsed ? 'Analytics' : ''">
                <i data-lucide="lightbulb" class="w-5 h-5 flex-shrink-0"></i>
                <span x-show="!sidebarCollapsed" 
                      x-transition:enter="transition ease-out duration-200" 
                      x-transition:enter-start="opacity-0 -translate-x-2" 
                      x-transition:enter-end="opacity-100 translate-x-0" 
                      x-transition:leave="transition ease-in duration-150" 
                      x-transition:leave-start="opacity-100 translate-x-0" 
                      x-transition:leave-end="opacity-0 -translate-x-2" 
                      class="font-medium whitespace-nowrap">Analytics</span>
            </a>
            <a href="<?php echo getSafeNodeUrl('suspicious-ips'); ?>" 
               class="nav-item <?php echo $currentPage == 'suspicious-ips' ? 'active' : ''; ?>" 
               :class="sidebarCollapsed ? 'justify-center px-2' : ''" 
               :title="sidebarCollapsed ? 'IPs Suspeitos' : ''">
                <i data-lucide="alert-octagon" class="w-5 h-5 flex-shrink-0"></i>
                <span x-show="!sidebarCollapsed" 
                      x-transition:enter="transition ease-out duration-200" 
                      x-transition:enter-start="opacity-0 -translate-x-2" 
                      x-transition:enter-end="opacity-100 translate-x-0" 
                      x-transition:leave="transition ease-in duration-150" 
                      x-transition:leave-start="opacity-100 translate-x-0" 
                      x-transition:leave-end="opacity-0 -translate-x-2" 
                      class="font-medium whitespace-nowrap">IPs Suspeitos</span>
            </a>
            <a href="<?php echo getSafeNodeUrl('attacked-targets'); ?>" 
               class="nav-item <?php echo $currentPage == 'attacked-targets' ? 'active' : ''; ?>" 
               :class="sidebarCollapsed ? 'justify-center px-2' : ''" 
               :title="sidebarCollapsed ? 'Alvos Atacados' : ''">
                <i data-lucide="target" class="w-5 h-5 flex-shrink-0"></i>
                <span x-show="!sidebarCollapsed" 
                      x-transition:enter="transition ease-out duration-200" 
                      x-transition:enter-start="opacity-0 -translate-x-2" 
                      x-transition:enter-end="opacity-100 translate-x-0" 
                      x-transition:leave="transition ease-in duration-150" 
                      x-transition:leave-start="opacity-100 translate-x-0" 
                      x-transition:leave-end="opacity-0 -translate-x-2" 
                      class="font-medium whitespace-nowrap">Alvos Atacados</span>
            </a>
        </div>
        
        <div class="pt-4 mt-4 border-t border-white/5">
            <p x-show="!sidebarCollapsed" 
               x-transition:enter="transition ease-out duration-200" 
               x-transition:enter-start="opacity-0" 
               x-transition:enter-end="opacity-100" 
               x-transition:leave="transition ease-in duration-150" 
               x-transition:leave-start="opacity-100" 
               x-transition:leave-end="opacity-0" 
               class="text-xs font-semibold text-zinc-600 uppercase tracking-wider mb-3 px-3 whitespace-nowrap">Inteligência</p>
            <a href="<?php echo getSafeNodeUrl('threat-intelligence'); ?>" 
               class="nav-item <?php echo $currentPage == 'threat-intelligence' ? 'active' : ''; ?>" 
               :class="sidebarCollapsed ? 'justify-center px-2' : ''" 
               :title="sidebarCollapsed ? 'Threat Intelligence' : ''">
                <i data-lucide="shield-alert" class="w-5 h-5 flex-shrink-0"></i>
                <span x-show="!sidebarCollapsed" 
                      x-transition:enter="transition ease-out duration-200" 
                      x-transition:enter-start="opacity-0 -translate-x-2" 
                      x-transition:enter-end="opacity-100 translate-x-0" 
                      x-transition:leave="transition ease-in duration-150" 
                      x-transition:leave-start="opacity-100 translate-x-0" 
                      x-transition:leave-end="opacity-0 -translate-x-2" 
                      class="font-medium whitespace-nowrap">Threat Intelligence</span>
            </a>
            <a href="<?php echo getSafeNodeUrl('security-advisor'); ?>" 
               class="nav-item <?php echo $currentPage == 'security-advisor' ? 'active' : ''; ?>" 
               :class="sidebarCollapsed ? 'justify-center px-2' : ''" 
               :title="sidebarCollapsed ? 'Security Advisor' : ''">
                <i data-lucide="shield-check" class="w-5 h-5 flex-shrink-0"></i>
                <span x-show="!sidebarCollapsed" 
                      x-transition:enter="transition ease-out duration-200" 
                      x-transition:enter-start="opacity-0 -translate-x-2" 
                      x-transition:enter-end="opacity-100 translate-x-0" 
                      x-transition:leave="transition ease-in duration-150" 
                      x-transition:leave-start="opacity-100 translate-x-0" 
                      x-transition:leave-end="opacity-0 -translate-x-2" 
                      class="font-medium whitespace-nowrap">Security Advisor</span>
            </a>
            <a href="<?php echo getSafeNodeUrl('vulnerability-scanner'); ?>" 
               class="nav-item <?php echo $currentPage == 'vulnerability-scanner' ? 'active' : ''; ?>" 
               :class="sidebarCollapsed ? 'justify-center px-2' : ''" 
               :title="sidebarCollapsed ? 'Vulnerability Scanner' : ''">
                <i data-lucide="scan-search" class="w-5 h-5 flex-shrink-0"></i>
                <span x-show="!sidebarCollapsed" 
                      x-transition:enter="transition ease-out duration-200" 
                      x-transition:enter-start="opacity-0 -translate-x-2" 
                      x-transition:enter-end="opacity-100 translate-x-0" 
                      x-transition:leave="transition ease-in duration-150" 
                      x-transition:leave-start="opacity-100 translate-x-0" 
                      x-transition:leave-end="opacity-0 -translate-x-2" 
                      class="font-medium whitespace-nowrap">Vulnerability Scanner</span>
            </a>
            <a href="<?php echo getSafeNodeUrl('anomaly-detector'); ?>" 
               class="nav-item <?php echo $currentPage == 'anomaly-detector' ? 'active' : ''; ?>" 
               :class="sidebarCollapsed ? 'justify-center px-2' : ''" 
               :title="sidebarCollapsed ? 'Anomaly Detector' : ''">
                <i data-lucide="radar" class="w-5 h-5 flex-shrink-0"></i>
                <span x-show="!sidebarCollapsed" 
                      x-transition:enter="transition ease-out duration-200" 
                      x-transition:enter-start="opacity-0 -translate-x-2" 
                      x-transition:enter-end="opacity-100 translate-x-0" 
                      x-transition:leave="transition ease-in duration-150" 
                      x-transition:leave-start="opacity-100 translate-x-0" 
                      x-transition:leave-end="opacity-0 -translate-x-2" 
                      class="font-medium whitespace-nowrap">Anomaly Detector</span>
            </a>
            <a href="<?php echo getSafeNodeUrl('endpoint-protection'); ?>" 
               class="nav-item <?php echo $currentPage == 'endpoint-protection' ? 'active' : ''; ?>" 
               :class="sidebarCollapsed ? 'justify-center px-2' : ''" 
               :title="sidebarCollapsed ? 'Proteção por Endpoint' : ''">
                <i data-lucide="route" class="w-5 h-5 flex-shrink-0"></i>
                <span x-show="!sidebarCollapsed" 
                      x-transition:enter="transition ease-out duration-200" 
                      x-transition:enter-start="opacity-0 -translate-x-2" 
                      x-transition:enter-end="opacity-100 translate-x-0" 
                      x-transition:leave="transition ease-in duration-150" 
                      x-transition:leave-start="opacity-100 translate-x-0" 
                      x-transition:leave-end="opacity-0 -translate-x-2" 
                      class="font-medium whitespace-nowrap">Proteção por Endpoint</span>
            </a>
            <a href="<?php echo getSafeNodeUrl('security-tests'); ?>" 
               class="nav-item <?php echo $currentPage == 'security-tests' ? 'active' : ''; ?>" 
               :class="sidebarCollapsed ? 'justify-center px-2' : ''" 
               :title="sidebarCollapsed ? 'Testes de Segurança' : ''">
                <i data-lucide="test-tube" class="w-5 h-5 flex-shrink-0"></i>
                <span x-show="!sidebarCollapsed" 
                      x-transition:enter="transition ease-out duration-200" 
                      x-transition:enter-start="opacity-0 -translate-x-2" 
                      x-transition:enter-end="opacity-100 translate-x-0" 
                      x-transition:leave="transition ease-in duration-150" 
                      x-transition:leave-start="opacity-100 translate-x-0" 
                      x-transition:leave-end="opacity-0 -translate-x-2" 
                      class="font-medium whitespace-nowrap">Testes de Segurança</span>
            </a>
        </div>
        
        <div class="pt-4 mt-4 border-t border-white/5">
            <p x-show="!sidebarCollapsed" 
               x-transition:enter="transition ease-out duration-200" 
               x-transition:enter-start="opacity-0" 
               x-transition:enter-end="opacity-100" 
               x-transition:leave="transition ease-in duration-150" 
               x-transition:leave-start="opacity-100" 
               x-transition:leave-end="opacity-0" 
               class="text-xs font-semibold text-zinc-600 uppercase tracking-wider mb-3 px-3 whitespace-nowrap">Sistema</p>
            <a href="<?php echo getSafeNodeUrl('updates'); ?>" 
               class="nav-item <?php echo $currentPage == 'updates' ? 'active' : ''; ?>" 
               :class="sidebarCollapsed ? 'justify-center px-2' : ''" 
               :title="sidebarCollapsed ? 'Atualizações' : ''">
                <i data-lucide="sparkles" class="w-5 h-5 flex-shrink-0"></i>
                <span x-show="!sidebarCollapsed" 
                      x-transition:enter="transition ease-out duration-200" 
                      x-transition:enter-start="opacity-0 -translate-x-2" 
                      x-transition:enter-end="opacity-100 translate-x-0" 
                      x-transition:leave="transition ease-in duration-150" 
                      x-transition:leave-start="opacity-100 translate-x-0" 
                      x-transition:leave-end="opacity-0 -translate-x-2" 
                      class="font-medium whitespace-nowrap">Atualizações</span>
            </a>
            <a href="<?php echo getSafeNodeUrl('documentation'); ?>" 
               class="nav-item <?php echo $currentPage == 'documentation' ? 'active' : ''; ?>" 
               :class="sidebarCollapsed ? 'justify-center px-2' : ''" 
               :title="sidebarCollapsed ? 'Documentação' : ''">
                <i data-lucide="book-open" class="w-5 h-5 flex-shrink-0"></i>
                <span x-show="!sidebarCollapsed" 
                      x-transition:enter="transition ease-out duration-200" 
                      x-transition:enter-start="opacity-0 -translate-x-2" 
                      x-transition:enter-end="opacity-100 translate-x-0" 
                      x-transition:leave="transition ease-in duration-150" 
                      x-transition:leave-start="opacity-100 translate-x-0" 
                      x-transition:leave-end="opacity-0 -translate-x-2" 
                      class="font-medium whitespace-nowrap">Documentação</span>
            </a>
            <a href="<?php echo getSafeNodeUrl('human-verification'); ?>" 
               class="nav-item <?php echo $currentPage == 'human-verification' ? 'active' : ''; ?>" 
               :class="sidebarCollapsed ? 'justify-center px-2' : ''" 
               :title="sidebarCollapsed ? 'Verificação Humana' : ''">
                <i data-lucide="shield-check" class="w-5 h-5 flex-shrink-0"></i>
                <span x-show="!sidebarCollapsed" 
                      x-transition:enter="transition ease-out duration-200" 
                      x-transition:enter-start="opacity-0 -translate-x-2" 
                      x-transition:enter-end="opacity-100 translate-x-0" 
                      x-transition:leave="transition ease-in duration-150" 
                      x-transition:leave-start="opacity-100 translate-x-0" 
                      x-transition:leave-end="opacity-0 -translate-x-2" 
                      class="font-medium whitespace-nowrap">Verificação Humana</span>
            </a>
            <a href="<?php echo getSafeNodeUrl('settings'); ?>" 
               class="nav-item <?php echo $currentPage == 'settings' ? 'active' : ''; ?>" 
               :class="sidebarCollapsed ? 'justify-center px-2' : ''" 
               :title="sidebarCollapsed ? 'Configurações' : ''">
                <i data-lucide="settings-2" class="w-5 h-5 flex-shrink-0"></i>
                <span x-show="!sidebarCollapsed" 
                      x-transition:enter="transition ease-out duration-200" 
                      x-transition:enter-start="opacity-0 -translate-x-2" 
                      x-transition:enter-end="opacity-100 translate-x-0" 
                      x-transition:leave="transition ease-in duration-150" 
                      x-transition:leave-start="opacity-100 translate-x-0" 
                      x-transition:leave-end="opacity-0 -translate-x-2" 
                      class="font-medium whitespace-nowrap">Configurações</span>
            </a>
            <a href="<?php echo getSafeNodeUrl('help'); ?>" 
               class="nav-item <?php echo $currentPage == 'help' ? 'active' : ''; ?>" 
               :class="sidebarCollapsed ? 'justify-center px-2' : ''" 
               :title="sidebarCollapsed ? 'Ajuda' : ''">
                <i data-lucide="life-buoy" class="w-5 h-5 flex-shrink-0"></i>
                <span x-show="!sidebarCollapsed" 
                      x-transition:enter="transition ease-out duration-200" 
                      x-transition:enter-start="opacity-0 -translate-x-2" 
                      x-transition:enter-end="opacity-100 translate-x-0" 
                      x-transition:leave="transition ease-in duration-150" 
                      x-transition:leave-start="opacity-100 translate-x-0" 
                      x-transition:leave-end="opacity-0 -translate-x-2" 
                      class="font-medium whitespace-nowrap">Ajuda</span>
            </a>
        </div>
    </nav>
    
    <!-- Upgrade Card -->
    <div class="p-4 flex-shrink-0" 
         x-show="!sidebarCollapsed" 
         x-transition:enter="transition ease-out duration-200" 
         x-transition:enter-start="opacity-0 translate-y-2" 
         x-transition:enter-end="opacity-100 translate-y-0" 
         x-transition:leave="transition ease-in duration-150" 
         x-transition:leave-start="opacity-100 translate-y-0" 
         x-transition:leave-end="opacity-0 translate-y-2">
        <div class="upgrade-card">
            <h3 class="font-semibold text-white text-sm mb-3">Ativar Pro</h3>
            <button class="w-full btn-primary py-2.5 text-sm">
                Upgrade Agora
            </button>
        </div>
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

<!-- Mobile Sidebar Overlay -->
<div x-show="sidebarOpen" 
     x-transition:enter="transition-opacity ease-linear duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition-opacity ease-linear duration-300"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     @click="sidebarOpen = false"
     class="fixed inset-0 bg-black/80 z-40 lg:hidden"
     x-cloak
     style="display: none;"></div>

<!-- Mobile Sidebar -->
<aside x-show="sidebarOpen"
       x-transition:enter="transition ease-out duration-300 transform"
       x-transition:enter-start="-translate-x-full"
       x-transition:enter-end="translate-x-0"
       x-transition:leave="transition ease-in duration-300 transform"
       x-transition:leave-start="translate-x-0"
       x-transition:leave-end="-translate-x-full"
       @click.away="sidebarOpen = false"
       class="fixed inset-y-0 left-0 w-72 sidebar h-full flex flex-col z-50 lg:hidden overflow-y-auto"
       x-cloak
       style="display: none;">
    <!-- Logo -->
    <div class="p-4 border-b border-white/5 flex-shrink-0 relative">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <img src="assets/img/logos (6).png" alt="SafeNode Logo" class="w-8 h-8 object-contain flex-shrink-0">
                <div class="overflow-hidden whitespace-nowrap">
                    <h1 class="font-bold text-white text-xl tracking-tight">SafeNode</h1>
                    <p class="text-xs text-zinc-500 font-medium">Security Platform</p>
                </div>
            </div>
            <button @click="sidebarOpen = false" class="text-zinc-600 hover:text-zinc-400 transition-colors flex-shrink-0">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
    </div>
    
    <!-- Navigation -->
    <nav class="flex-1 p-4 space-y-1 overflow-y-auto overflow-x-hidden">
        <p class="text-xs font-semibold text-zinc-600 uppercase tracking-wider mb-3 px-3 whitespace-nowrap">Principal</p>
        
        <a href="<?php echo getSafeNodeUrl('dashboard'); ?>" class="nav-item <?php echo $currentPage == 'dashboard' ? 'active' : ''; ?>" @click="sidebarOpen = false">
            <i data-lucide="layout-dashboard" class="w-5 h-5 flex-shrink-0"></i>
            <span class="font-medium whitespace-nowrap">Dashboard</span>
        </a>
        <a href="<?php echo getSafeNodeUrl('sites'); ?>" class="nav-item <?php echo $currentPage == 'sites' ? 'active' : ''; ?>" @click="sidebarOpen = false">
            <i data-lucide="globe" class="w-5 h-5 flex-shrink-0"></i>
            <span class="font-medium whitespace-nowrap">Gerenciar Sites</span>
        </a>
        
        <div class="pt-4 mt-4 border-t border-white/5">
            <p class="text-xs font-semibold text-zinc-600 uppercase tracking-wider mb-3 px-3 whitespace-nowrap">Análises</p>
            <a href="<?php echo getSafeNodeUrl('logs'); ?>" class="nav-item <?php echo $currentPage == 'logs' ? 'active' : ''; ?>" @click="sidebarOpen = false">
                <i data-lucide="file-text" class="w-5 h-5 flex-shrink-0"></i>
                <span class="font-medium whitespace-nowrap">Explorar Logs</span>
            </a>
            <a href="<?php echo getSafeNodeUrl('behavior-analysis'); ?>" class="nav-item <?php echo $currentPage == 'behavior-analysis' ? 'active' : ''; ?>" @click="sidebarOpen = false">
                <i data-lucide="brain" class="w-5 h-5 flex-shrink-0"></i>
                <span class="font-medium whitespace-nowrap">Comportamental</span>
            </a>
            <a href="<?php echo getSafeNodeUrl('security-analytics'); ?>" class="nav-item <?php echo $currentPage == 'security-analytics' ? 'active' : ''; ?>" @click="sidebarOpen = false">
                <i data-lucide="lightbulb" class="w-5 h-5 flex-shrink-0"></i>
                <span class="font-medium whitespace-nowrap">Analytics</span>
            </a>
            <a href="<?php echo getSafeNodeUrl('suspicious-ips'); ?>" class="nav-item <?php echo $currentPage == 'suspicious-ips' ? 'active' : ''; ?>" @click="sidebarOpen = false">
                <i data-lucide="alert-octagon" class="w-5 h-5 flex-shrink-0"></i>
                <span class="font-medium whitespace-nowrap">IPs Suspeitos</span>
            </a>
            <a href="<?php echo getSafeNodeUrl('attacked-targets'); ?>" class="nav-item <?php echo $currentPage == 'attacked-targets' ? 'active' : ''; ?>" @click="sidebarOpen = false">
                <i data-lucide="target" class="w-5 h-5 flex-shrink-0"></i>
                <span class="font-medium whitespace-nowrap">Alvos Atacados</span>
            </a>
        </div>
        
        <div class="pt-4 mt-4 border-t border-white/5">
            <p class="text-xs font-semibold text-zinc-600 uppercase tracking-wider mb-3 px-3 whitespace-nowrap">Inteligência</p>
            <a href="<?php echo getSafeNodeUrl('threat-intelligence'); ?>" class="nav-item <?php echo $currentPage == 'threat-intelligence' ? 'active' : ''; ?>" @click="sidebarOpen = false">
                <i data-lucide="shield-alert" class="w-5 h-5 flex-shrink-0"></i>
                <span class="font-medium whitespace-nowrap">Threat Intelligence</span>
            </a>
            <a href="<?php echo getSafeNodeUrl('security-advisor'); ?>" class="nav-item <?php echo $currentPage == 'security-advisor' ? 'active' : ''; ?>" @click="sidebarOpen = false">
                <i data-lucide="shield-check" class="w-5 h-5 flex-shrink-0"></i>
                <span class="font-medium whitespace-nowrap">Security Advisor</span>
            </a>
            <a href="<?php echo getSafeNodeUrl('vulnerability-scanner'); ?>" class="nav-item <?php echo $currentPage == 'vulnerability-scanner' ? 'active' : ''; ?>" @click="sidebarOpen = false">
                <i data-lucide="scan-search" class="w-5 h-5 flex-shrink-0"></i>
                <span class="font-medium whitespace-nowrap">Vulnerability Scanner</span>
            </a>
            <a href="<?php echo getSafeNodeUrl('anomaly-detector'); ?>" class="nav-item <?php echo $currentPage == 'anomaly-detector' ? 'active' : ''; ?>" @click="sidebarOpen = false">
                <i data-lucide="radar" class="w-5 h-5 flex-shrink-0"></i>
                <span class="font-medium whitespace-nowrap">Anomaly Detector</span>
            </a>
            <a href="<?php echo getSafeNodeUrl('endpoint-protection'); ?>" class="nav-item <?php echo $currentPage == 'endpoint-protection' ? 'active' : ''; ?>" @click="sidebarOpen = false">
                <i data-lucide="route" class="w-5 h-5 flex-shrink-0"></i>
                <span class="font-medium whitespace-nowrap">Proteção por Endpoint</span>
            </a>
            <a href="<?php echo getSafeNodeUrl('security-tests'); ?>" class="nav-item <?php echo $currentPage == 'security-tests' ? 'active' : ''; ?>" @click="sidebarOpen = false">
                <i data-lucide="test-tube" class="w-5 h-5 flex-shrink-0"></i>
                <span class="font-medium whitespace-nowrap">Testes de Segurança</span>
            </a>
        </div>
        
        <div class="pt-4 mt-4 border-t border-white/5">
            <p class="text-xs font-semibold text-zinc-600 uppercase tracking-wider mb-3 px-3 whitespace-nowrap">Sistema</p>
            <a href="<?php echo getSafeNodeUrl('updates'); ?>" class="nav-item <?php echo $currentPage == 'updates' ? 'active' : ''; ?>" @click="sidebarOpen = false">
                <i data-lucide="sparkles" class="w-5 h-5 flex-shrink-0"></i>
                <span class="font-medium whitespace-nowrap">Atualizações</span>
            </a>
            <a href="<?php echo getSafeNodeUrl('documentation'); ?>" class="nav-item <?php echo $currentPage == 'documentation' ? 'active' : ''; ?>" @click="sidebarOpen = false">
                <i data-lucide="book-open" class="w-5 h-5 flex-shrink-0"></i>
                <span class="font-medium whitespace-nowrap">Documentação</span>
            </a>
            <a href="<?php echo getSafeNodeUrl('human-verification'); ?>" class="nav-item <?php echo $currentPage == 'human-verification' ? 'active' : ''; ?>" @click="sidebarOpen = false">
                <i data-lucide="shield-check" class="w-5 h-5 flex-shrink-0"></i>
                <span class="font-medium whitespace-nowrap">Verificação Humana</span>
            </a>
            <a href="<?php echo getSafeNodeUrl('settings'); ?>" class="nav-item <?php echo $currentPage == 'settings' ? 'active' : ''; ?>" @click="sidebarOpen = false">
                <i data-lucide="settings-2" class="w-5 h-5 flex-shrink-0"></i>
                <span class="font-medium whitespace-nowrap">Configurações</span>
            </a>
            <a href="<?php echo getSafeNodeUrl('help'); ?>" class="nav-item <?php echo $currentPage == 'help' ? 'active' : ''; ?>" @click="sidebarOpen = false">
                <i data-lucide="life-buoy" class="w-5 h-5 flex-shrink-0"></i>
                <span class="font-medium whitespace-nowrap">Ajuda</span>
            </a>
        </div>
    </nav>
    
    <!-- Upgrade Card -->
    <div class="p-4 flex-shrink-0">
        <div class="upgrade-card">
            <h3 class="font-semibold text-white text-sm mb-3">Ativar Pro</h3>
            <button class="w-full btn-primary py-2.5 text-sm">
                Upgrade Agora
            </button>
        </div>
    </div>
</aside>

<script>
// Inicializar ícones do Lucide (incluindo o foguinho)
if (typeof lucide !== 'undefined') {
    lucide.createIcons();
}
</script>
