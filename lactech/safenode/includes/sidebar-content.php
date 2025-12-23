<?php
/**
 * Sidebar Content - Reutilizável para todas as páginas
 * Este arquivo contém o HTML completo da sidebar
 */

// Este arquivo será incluído em cada página que precisa da sidebar
// A variável $currentPage deve estar definida antes de incluir este arquivo
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
    
    @media (max-width: 1023px) {
        aside[x-show*="sidebarOpen"] {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: auto !important;
            bottom: 0 !important;
            width: 18rem !important;
            max-width: 18rem !important;
            min-width: 18rem !important;
            z-index: 70 !important;
            transform: translateX(-100%) !important;
            will-change: transform;
            margin: 0 !important;
            padding: 0 !important;
        }
        
        aside[x-show*="sidebarOpen"][x-show="true"],
        aside[x-show*="sidebarOpen"]:not([style*="translateX(-100%)"]) {
            transform: translateX(0) !important;
        }
        
        div[x-show*="sidebarOpen"].fixed {
            position: fixed !important;
            z-index: 60 !important;
            pointer-events: auto !important;
        }
        
        main.flex-1,
        main[class*="flex-1"] {
            width: 100% !important;
            max-width: 100% !important;
            margin-left: 0 !important;
            padding-left: 0 !important;
            transition: none !important;
        }
        
        div.flex.h-full > main {
            width: 100% !important;
            flex: 1 1 100% !important;
            min-width: 0 !important;
        }
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
    
    [x-cloak] { 
        display: none !important; 
    }
    
    aside[x-show*="sidebarOpen"]:not([x-cloak]),
    aside[x-show*="sidebarOpen"][style*="display: flex"] {
        display: flex !important;
    }
    
    aside.mobile-sidebar-open {
        display: flex !important;
        x-cloak: none;
    }
    
    /* Esconder scrollbar mas manter funcionalidade de scroll */
    .sidebar nav,
    .sidebar,
    aside.mobile-sidebar nav,
    aside.mobile-sidebar {
        scrollbar-width: none; /* Firefox */
        -ms-overflow-style: none; /* IE e Edge */
    }
    
    .sidebar nav::-webkit-scrollbar,
    .sidebar::-webkit-scrollbar,
    aside.mobile-sidebar nav::-webkit-scrollbar,
    aside.mobile-sidebar::-webkit-scrollbar {
        display: none; /* Chrome, Safari, Opera */
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
                    <div class="absolute -top-1 -right-1 bg-gradient-to-br from-orange-500 to-red-600 rounded-full p-1 shadow-lg border-2 border-dark-900" 
                         x-data="{ showTooltip: false }"
                         @mouseenter="showTooltip = true"
                         @mouseleave="showTooltip = false">
                        <i data-lucide="flame" class="w-3 h-3 text-white"></i>
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
        <a href="<?php echo getSafeNodeUrl('mail'); ?>" 
           class="nav-item <?php echo $currentPage == 'mail' ? 'active' : ''; ?>" 
           :class="sidebarCollapsed ? 'justify-center px-2' : ''" 
           :title="sidebarCollapsed ? 'Mail' : ''">
            <i data-lucide="mail" class="w-5 h-5 flex-shrink-0"></i>
            <span x-show="!sidebarCollapsed" 
                  x-transition:enter="transition ease-out duration-200" 
                  x-transition:enter-start="opacity-0 -translate-x-2" 
                  x-transition:enter-end="opacity-100 translate-x-0" 
                  x-transition:leave="transition ease-in duration-150" 
                  x-transition:leave-start="opacity-100 translate-x-0" 
                  x-transition:leave-end="opacity-0 -translate-x-2" 
                  class="font-medium whitespace-nowrap">Mail</span>
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

<!-- Backdrop e Mobile Sidebar serão incluídos via JavaScript inline no final do arquivo -->


