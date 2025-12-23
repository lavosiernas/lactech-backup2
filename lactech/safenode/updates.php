<?php
/**
 * SafeNode - Atualizações do Sistema
 * Página de changelog e novidades do SafeNode
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

// Array de atualizações (ordenado do mais recente para o mais antigo)
$updates = [
    [
        'version' => '2.5.0',
        'date' => '2025-11-22',
        'type' => 'major',
        'title' => 'Dashboard em Tempo Real',
        'description' => 'Todas as estatísticas da dashboard agora atualizam automaticamente em tempo real sem precisar recarregar a página.',
        'features' => [
            'Atualização automática a cada 3 segundos',
            'Visitantes únicos em tempo real',
            'Requisições e ameaças em tempo real',
            'Gráficos atualizados automaticamente',
            'Top países e IPs bloqueados em tempo real',
            'Registro de eventos e incidentes atualizados automaticamente'
        ],
        'improvements' => [
            'Performance otimizada para polling de dados',
            'Interface mais responsiva',
            'Melhor experiência do usuário'
        ]
    ],
    [
        'version' => '2.3.0',
        'date' => '2025-11-22',
        'type' => 'major',
        'title' => 'Gestão de Sessões e Atividade',
        'description' => 'Sistema completo para gerenciar sessões ativas e visualizar histórico de atividades da sua conta.',
        'features' => [
            'Visualização de todas as sessões ativas',
            'Detecção automática de dispositivo, navegador e SO',
            'Encerramento de sessões individuais ou todas',
            'Histórico completo de atividades',
            'Log de ações importantes (login, logout, mudanças)'
        ],
        'improvements' => [
            'Melhor controle de segurança',
            'Rastreamento completo de atividades'
        ]
    ],
    [
        'version' => '2.2.0',
        'date' => '2025-11-22',
        'type' => 'major',
        'title' => 'Segurança Avançada',
        'description' => 'Melhorias significativas na segurança do sistema com proteções contra CSRF, XSS e validação robusta.',
        'features' => [
            'Proteção CSRF em todos os formulários',
            'Sanitização XSS em todas as saídas',
            'Validação robusta de entradas',
            'Headers de segurança HTTP',
            'Isolamento de dados por usuário'
        ],
        'improvements' => [
            'Sistema mais seguro e robusto',
            'Proteção contra vulnerabilidades comuns'
        ]
    ],
    [
        'version' => '2.1.0',
        'date' => '2025-11-22',
        'type' => 'major',
        'title' => 'Perfil do Usuário Redesenhado',
        'description' => 'Interface completamente nova para o perfil do usuário com recursos avançados de personalização.',
        'features' => [
            'Edição de username e nome completo',
            'Integração com Google para foto de perfil',
            'Página dedicada para mudança de senha',
            'Recuperação de senha via OTP por email',
            'Modo de edição intuitivo',
            'Zona perigosa com verificações em 2 etapas'
        ],
        'improvements' => [
            'Interface mais moderna e intuitiva',
            'Melhor experiência do usuário'
        ]
    ],
    [
        'version' => '2.0.0',
        'date' => '2025-11-22',
        'type' => 'major',
        'title' => 'Login com Google',
        'description' => 'Agora você pode fazer login e registro usando sua conta Google de forma rápida e segura.',
        'features' => [
            'Autenticação OAuth 2.0 com Google',
            'Login rápido com um clique',
            'Registro automático com dados do Google',
            'Sincronização de foto de perfil'
        ],
        'improvements' => [
            'Experiência de login simplificada',
            'Maior facilidade para novos usuários'
        ]
    ],
    [
        'version' => '1.5.0',
        'date' => '2025-11-22',
        'type' => 'minor',
        'title' => 'Melhorias na Dashboard',
        'description' => 'Várias melhorias visuais e funcionais na dashboard principal.',
        'features' => [
            'Novos cards de estatísticas',
            'Gráficos aprimorados',
            'Visualização de incidentes melhorada',
            'Top países e IPs bloqueados'
        ],
        'improvements' => [
            'Interface mais informativa',
            'Melhor organização dos dados'
        ]
    ],
    [
        'version' => '1.4.0',
        'date' => '2025-11-22',
        'type' => 'minor',
        'title' => 'Sistema de Email',
        'description' => 'Sistema completo de envio de emails com templates personalizados.',
        'features' => [
            'Templates de email HTML',
            'Notificações de segurança',
            'Recuperação de senha via email',
            'Códigos de verificação OTP'
        ],
        'improvements' => [
            'Comunicação com usuários aprimorada'
        ]
    ],
    [
        'version' => '1.0.0',
        'date' => '2025-11-20',
        'type' => 'major',
        'title' => 'Lançamento Inicial',
        'description' => 'Primeira versão do SafeNode com todas as funcionalidades básicas de segurança.',
        'features' => [
            'Proteção WAF (Web Application Firewall)',
            'Detecção de ameaças em tempo real',
            'Bloqueio automático de IPs maliciosos',
            'Dashboard de monitoramento',
            'Logs de segurança',
            'Gerenciamento de sites'
        ],
        'improvements' => []
    ]
];

// Função para formatar a data
function formatUpdateDate($date) {
    $dateObj = new DateTime($date);
    $months = [
        '01' => 'Janeiro', '02' => 'Fevereiro', '03' => 'Março',
        '04' => 'Abril', '05' => 'Maio', '06' => 'Junho',
        '07' => 'Julho', '08' => 'Agosto', '09' => 'Setembro',
        '10' => 'Outubro', '11' => 'Novembro', '12' => 'Dezembro'
    ];
    return $dateObj->format('d') . ' de ' . $months[$dateObj->format('m')] . ' de ' . $dateObj->format('Y');
}

// Função para obter cor do tipo de versão
function getVersionColor($type) {
    switch ($type) {
        case 'major':
            return 'bg-blue-500/10 text-blue-400 border-blue-500/20';
        case 'minor':
            return 'bg-purple-500/10 text-purple-400 border-purple-500/20';
        case 'patch':
            return 'bg-green-500/10 text-green-400 border-green-500/20';
        default:
            return 'bg-zinc-500/10 text-zinc-400 border-zinc-500/20';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR" class="dark h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atualizações | SafeNode</title>
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
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #3f3f46; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #52525b; }

        .glass-card {
            background: linear-gradient(180deg, rgba(39, 39, 42, 0.4) 0%, rgba(24, 24, 27, 0.4) 100%);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-black text-zinc-200 font-sans h-full overflow-hidden flex selection:bg-blue-500/30">

    <!-- Sidebar -->
        <?php
    // Código da Sidebar (copiado de includes/sidebar.php)
    // Iniciar sessão se necessário
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Carregar Router se estiver logado
    $useProtectedUrls = false;
    if (isset($_SESSION['safenode_logged_in']) && $_SESSION['safenode_logged_in'] === true) {
        require_once __DIR__ . '/includes/Router.php';
        SafeNodeRouter::init();
        $useProtectedUrls = true;
    }

    // Função helper para gerar URLs (sem token)
    if (!function_exists('getSafeNodeUrl')) {
        function getSafeNodeUrl($route, $siteId = null) {
            $pagePath = strpos($route, '.php') !== false ? $route : $route . '.php';
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
            require_once __DIR__ . '/includes/ProtectionStreak.php';
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
        
        /* Garantir que sidebar mobile sobreponha completamente sem comprimir interface */
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
            
            /* Garantir que o overlay também sobreponha */
            div[x-show*="sidebarOpen"].fixed {
                position: fixed !important;
                z-index: 60 !important;
                pointer-events: auto !important;
            }
            
            /* Garantir que o conteúdo principal não seja afetado */
            main.flex-1,
            main[class*="flex-1"] {
                width: 100% !important;
                max-width: 100% !important;
                margin-left: 0 !important;
                padding-left: 0 !important;
                transition: none !important;
            }
            
            /* Container principal não deve ser afetado */
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
        
        /* CSS para x-cloak - esconder elementos antes do Alpine.js carregar */
        [x-cloak] { 
            display: none !important; 
        }
        
        /* Garantir que sidebar mobile apareça quando sidebarOpen for true */
        /* Alpine.js remove x-cloak quando x-show é true, mas garantimos com CSS também */
        aside[x-show*="sidebarOpen"]:not([x-cloak]),
        aside[x-show*="sidebarOpen"][style*="display: flex"] {
            display: flex !important;
        }
        
        /* Override x-cloak quando sidebar está aberta via JavaScript */
        aside.mobile-sidebar-open {
            display: flex !important;
            x-cloak: none;
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

    <!-- Backdrop para mobile (controlado por JavaScript antigo - manter para compatibilidade) -->
    <div id="safenode-sidebar-backdrop" class="fixed inset-0 bg-black/60 z-40 hidden lg:hidden"></div>

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
        
        // Variáveis para gesto de swipe
        let touchStartX = 0;
        let touchStartY = 0;
        let touchEndX = 0;
        let touchEndY = 0;
        let isSwiping = false;
        let sidebarElement = null;
        
        // Função para atualizar sidebarOpen no Alpine.js
        function updateAlpineSidebarState(isOpen) {
            // Buscar o elemento body que geralmente tem x-data com sidebarOpen
            const bodyElement = document.body;
            if (bodyElement && window.Alpine && typeof Alpine !== 'undefined') {
                try {
                    // Aguardar Alpine estar inicializado
                    if (document.readyState === 'loading') {
                        document.addEventListener('DOMContentLoaded', function() {
                            setTimeout(() => updateAlpineSidebarState(isOpen), 100);
                        });
                        return;
                    }
                    
                    // Tentar acessar o estado do Alpine.js
                    const bodyData = Alpine.$data(bodyElement);
                    if (bodyData && typeof bodyData.sidebarOpen !== 'undefined') {
                        bodyData.sidebarOpen = isOpen;
                        return;
                    }
                } catch (e) {
                    console.warn('Não foi possível atualizar estado Alpine diretamente:', e);
                }
            }
            
            // Fallback: disparar evento customizado que o Alpine.js pode escutar
            window.dispatchEvent(new CustomEvent('safenode-sidebar-toggle', { 
                detail: { isOpen: isOpen } 
            }));
        }

        function openSidebar() {
            if (sidebar) {
                sidebar.classList.remove('-translate-x-full');
            }
            if (backdrop) {
                backdrop.classList.remove('hidden');
            }
            
            // Encontrar e mostrar sidebar mobile
            const mobileSidebar = document.querySelector('aside.mobile-sidebar, aside[x-show*="sidebarOpen"]');
            if (mobileSidebar) {
                mobileSidebar.style.display = 'flex';
                mobileSidebar.style.transform = 'translateX(0)';
                mobileSidebar.classList.add('mobile-sidebar-open');
                mobileSidebar.removeAttribute('x-cloak');
            }
            
            updateAlpineSidebarState(true);
        }

        function closeSidebar() {
            if (sidebar) {
                sidebar.classList.add('-translate-x-full');
            }
            if (backdrop) {
                backdrop.classList.add('hidden');
            }
            
            // Esconder sidebar mobile
            const mobileSidebar = document.querySelector('aside.mobile-sidebar, aside[x-show*="sidebarOpen"]');
            if (mobileSidebar) {
                mobileSidebar.style.display = 'none';
                mobileSidebar.style.transform = 'translateX(-100%)';
                mobileSidebar.classList.remove('mobile-sidebar-open');
                mobileSidebar.setAttribute('x-cloak', '');
            }
            
            updateAlpineSidebarState(false);
        }

        function toggleSidebar() {
            // Verificar estado atual via Alpine.js primeiro
            let isCurrentlyOpen = false;
            
            // Tentar encontrar o estado atual
            const bodyElement = document.body;
            if (bodyElement && window.Alpine) {
                try {
                    const bodyData = Alpine.$data(bodyElement);
                    if (bodyData && typeof bodyData.sidebarOpen !== 'undefined') {
                        isCurrentlyOpen = bodyData.sidebarOpen;
                    }
                } catch (e) {
                    // Se não conseguir, verificar via classe CSS
                    if (sidebar) {
                        isCurrentlyOpen = !sidebar.classList.contains('-translate-x-full');
                    }
                }
            } else if (sidebar) {
                isCurrentlyOpen = !sidebar.classList.contains('-translate-x-full');
            }
            
            if (isCurrentlyOpen) {
                closeSidebar();
            } else {
                openSidebar();
            }
        }

        document.addEventListener('click', function(e) {
            const toggleBtn = e.target.closest('[data-sidebar-toggle]');
            if (toggleBtn) {
                e.preventDefault();
                e.stopPropagation();
                // Aguardar um tick para garantir que Alpine.js está pronto
                setTimeout(() => {
                    toggleSidebar();
                }, 10);
                return;
            }

            const logoutBtn = e.target.closest('[data-logout-trigger]');
            if (logoutBtn && logoutModal) {
                e.preventDefault();
                logoutModal.classList.remove('hidden');
                logoutModal.classList.add('flex');
            }
        });
        
        // Escutar eventos customizados para sincronizar com Alpine.js
        window.addEventListener('safenode-sidebar-toggle', function(e) {
            const isOpen = e.detail.isOpen;
            if (sidebar) {
                if (isOpen) {
                    sidebar.classList.remove('-translate-x-full');
                } else {
                    sidebar.classList.add('-translate-x-full');
                }
            }
            // Sincronizar backdrop antigo também
            if (backdrop) {
                if (isOpen) {
                    backdrop.classList.remove('hidden');
                } else {
                    backdrop.classList.add('hidden');
                }
            }
            
            // Sincronizar sidebar mobile
            const mobileSidebar = document.querySelector('aside.mobile-sidebar, aside[x-show*="sidebarOpen"]');
            if (mobileSidebar) {
                if (isOpen) {
                    mobileSidebar.style.display = 'flex';
                    mobileSidebar.style.transform = 'translateX(0)';
                    mobileSidebar.classList.add('mobile-sidebar-open');
                    mobileSidebar.removeAttribute('x-cloak');
                } else {
                    mobileSidebar.style.display = 'none';
                    mobileSidebar.style.transform = 'translateX(-100%)';
                    mobileSidebar.classList.remove('mobile-sidebar-open');
                    mobileSidebar.setAttribute('x-cloak', '');
                }
            }
        });
        
        // Observar mudanças no estado do Alpine.js para sincronizar backdrop
        function syncBackdropWithAlpine() {
            const bodyElement = document.body;
            if (bodyElement && window.Alpine && typeof Alpine !== 'undefined') {
                try {
                    const bodyData = Alpine.$data(bodyElement);
                    if (bodyData && typeof bodyData.sidebarOpen !== 'undefined') {
                        const isOpen = bodyData.sidebarOpen;
                        if (backdrop) {
                            if (isOpen) {
                                backdrop.classList.remove('hidden');
                            } else {
                                backdrop.classList.add('hidden');
                            }
                        }
                    }
                } catch (e) {
                    // Ignorar erros
                }
            }
        }
        
        // Verificar estado periodicamente e quando Alpine inicializar
        if (window.Alpine && typeof Alpine !== 'undefined') {
            // Aguardar Alpine estar pronto
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() {
                    setTimeout(function() {
                        setInterval(syncBackdropWithAlpine, 100);
                        syncBackdropWithAlpine();
                    }, 200);
                });
            } else {
                setTimeout(function() {
                    setInterval(syncBackdropWithAlpine, 100);
                    syncBackdropWithAlpine();
                }, 200);
            }
        }
        
        // Garantir que backdrop seja escondido quando sidebar fechar via qualquer método
        function ensureBackdropHidden() {
            if (backdrop) {
                const bodyElement = document.body;
                let shouldBeVisible = false;
                
                // Verificar estado do Alpine.js
                if (bodyElement && window.Alpine && typeof Alpine !== 'undefined') {
                    try {
                        const bodyData = Alpine.$data(bodyElement);
                        if (bodyData && typeof bodyData.sidebarOpen !== 'undefined') {
                            shouldBeVisible = bodyData.sidebarOpen;
                        }
                    } catch (e) {
                        // Ignorar
                    }
                }
                
                // Se não deveria estar visível, esconder
                if (!shouldBeVisible) {
                    backdrop.classList.add('hidden');
                }
            }
        }
        
        // Executar verificação periodicamente
        setInterval(ensureBackdropHidden, 200);
        
        // Escutar mudanças do Alpine.js também
        if (window.Alpine && typeof Alpine !== 'undefined') {
            // Aguardar Alpine estar pronto
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() {
                    setupAlpineListener();
                });
            } else {
                setupAlpineListener();
            }
        }
        
        function setupAlpineListener() {
            // Observar mudanças no body quando sidebarOpen mudar
            const bodyElement = document.body;
            if (bodyElement && window.Alpine) {
                try {
                    // Usar MutationObserver para detectar mudanças no atributo x-data
                    // ou simplesmente escutar eventos do Alpine
                    const observer = new MutationObserver(function() {
                        // Verificar se sidebar precisa ser atualizada
                        try {
                            const bodyData = Alpine.$data(bodyElement);
                            if (bodyData && typeof bodyData.sidebarOpen !== 'undefined') {
                                const isOpen = bodyData.sidebarOpen;
                                if (sidebar) {
                                    if (isOpen && sidebar.classList.contains('-translate-x-full')) {
                                        sidebar.classList.remove('-translate-x-full');
                                    } else if (!isOpen && !sidebar.classList.contains('-translate-x-full')) {
                                        sidebar.classList.add('-translate-x-full');
                                    }
                                }
                            }
                        } catch (e) {
                            // Ignorar erros
                        }
                    });
                    
                    // Observar mudanças no body
                    observer.observe(bodyElement, {
                        attributes: true,
                        attributeFilter: ['x-data']
                    });
                } catch (e) {
                    console.warn('Não foi possível configurar observer Alpine:', e);
                }
            }
        }

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
        
        // Detecção de gesto swipe para abrir sidebar
        function initSwipeGesture() {
            const bodyElement = document.body;
            let swipeThreshold = 50; // Distância mínima para considerar swipe
            let minSwipeDistance = 100; // Distância mínima para abrir sidebar
            let maxVerticalDistance = 50; // Máxima distância vertical permitida
            
            bodyElement.addEventListener('touchstart', function(e) {
                // Só detectar swipe se estiver na borda esquerda da tela (primeiros 20px)
                if (e.touches[0].clientX <= 20 && window.innerWidth < 1024) {
                    touchStartX = e.touches[0].clientX;
                    touchStartY = e.touches[0].clientY;
                    isSwiping = true;
                    
                    // Encontrar elemento da sidebar mobile
                    sidebarElement = document.querySelector('aside[x-show*="sidebarOpen"]');
                }
            }, { passive: true });
            
            bodyElement.addEventListener('touchmove', function(e) {
                if (!isSwiping) return;
                
                touchEndX = e.touches[0].clientX;
                touchEndY = e.touches[0].clientY;
                
                const deltaX = touchEndX - touchStartX;
                const deltaY = Math.abs(touchEndY - touchStartY);
                
                // Se o movimento vertical for muito grande, cancelar swipe
                if (deltaY > maxVerticalDistance) {
                    isSwiping = false;
                    return;
                }
                
                // Se estiver arrastando para a direita, mostrar preview da sidebar
                if (deltaX > 0 && sidebarElement) {
                    const progress = Math.min(deltaX / 288, 1); // 288 = largura da sidebar (w-72)
                    sidebarElement.style.transform = `translateX(${-100 + (progress * 100)}%)`;
                    sidebarElement.style.transition = 'none';
                    
                    // Mostrar backdrop com opacidade proporcional
                    if (backdrop) {
                        backdrop.style.opacity = (progress * 0.8).toString();
                        backdrop.classList.remove('hidden');
                    }
                }
            }, { passive: true });
            
            bodyElement.addEventListener('touchend', function(e) {
                if (!isSwiping) return;
                
                const deltaX = touchEndX - touchStartX;
                const deltaY = Math.abs(touchEndY - touchStartY);
                
                // Verificar se é um swipe válido
                if (deltaX > minSwipeDistance && deltaY < maxVerticalDistance) {
                    // Abrir sidebar
                    openSidebar();
                } else if (deltaX > swipeThreshold) {
                    // Se arrastou um pouco mas não o suficiente, ainda abrir
                    openSidebar();
                } else {
                    // Fechar sidebar se não arrastou o suficiente
                    if (sidebarElement) {
                        sidebarElement.style.transition = '';
                        sidebarElement.style.transform = 'translateX(-100%) !important';
                    }
                    if (backdrop) {
                        backdrop.style.opacity = '';
                        backdrop.classList.add('hidden');
                    }
                }
                
                isSwiping = false;
                touchStartX = 0;
                touchStartY = 0;
                touchEndX = 0;
                touchEndY = 0;
            }, { passive: true });
            
            bodyElement.addEventListener('touchcancel', function(e) {
                // Resetar estado se o toque for cancelado
                if (sidebarElement) {
                    sidebarElement.style.transition = '';
                    sidebarElement.style.transform = 'translateX(-100%) !important';
                }
                if (backdrop) {
                    backdrop.style.opacity = '';
                    backdrop.classList.add('hidden');
                }
                isSwiping = false;
            }, { passive: true });
        }
        
        // Inicializar gesto de swipe quando DOM estiver pronto
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(initSwipeGesture, 100);
            });
        } else {
            setTimeout(initSwipeGesture, 100);
        }
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
         @click="sidebarOpen = false; $dispatch('safenode-sidebar-toggle', { isOpen: false })"
         @safenode-sidebar-toggle.window="sidebarOpen = $event.detail.isOpen"
         class="fixed inset-0 bg-black/80 z-[60] lg:hidden"
         style="pointer-events: auto;"
         x-cloak></div>

    <!-- Mobile Sidebar -->
    <aside x-show="sidebarOpen"
           x-init="$watch('sidebarOpen', value => { 
               if (value) { 
                   $el.style.display = 'flex'; 
                   $el.removeAttribute('x-cloak');
               } else {
                   $el.style.display = 'none';
               }
           })"
           x-transition:enter="transition ease-out duration-300 transform"
           x-transition:enter-start="-translate-x-full"
           x-transition:enter-end="translate-x-0"
           x-transition:leave="transition ease-in duration-300 transform"
           x-transition:leave-start="translate-x-0"
           x-transition:leave-end="-translate-x-full"
           @click.away="sidebarOpen = false; $dispatch('safenode-sidebar-toggle', { isOpen: false })"
           @safenode-sidebar-toggle.window="sidebarOpen = $event.detail.isOpen"
           class="fixed inset-y-0 left-0 w-72 sidebar h-full flex flex-col z-[70] lg:hidden overflow-y-auto mobile-sidebar"
           style="position: fixed !important; transform: translateX(-100%); will-change: transform;"
           x-cloak>
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
                <button @click="sidebarOpen = false; $dispatch('safenode-sidebar-toggle', { isOpen: false })" class="text-zinc-600 hover:text-zinc-400 transition-colors flex-shrink-0">
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
            <a href="<?php echo getSafeNodeUrl('mail'); ?>" class="nav-item <?php echo $currentPage == 'mail' ? 'active' : ''; ?>" @click="sidebarOpen = false">
                <i data-lucide="mail" class="w-5 h-5 flex-shrink-0"></i>
                <span class="font-medium whitespace-nowrap">Mail</span>
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


    <!-- Main Content -->
    <main class="flex-1 flex flex-col h-full relative overflow-hidden bg-black">
        <!-- Header -->
        <header class="h-16 border-b border-white/5 bg-black/50 backdrop-blur-xl sticky top-0 z-40 px-6 flex items-center justify-between">
            <div class="flex items-center gap-4 md:hidden">
                <button class="text-zinc-400 hover:text-white" data-sidebar-toggle>
                    <i data-lucide="menu" class="w-6 h-6"></i>
                </button>
                <span class="font-bold text-lg text-white">SafeNode</span>
            </div>

            <div class="hidden md:flex items-center gap-4">
                <div class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-zinc-900/50 border border-white/5 text-xs font-medium text-zinc-400">
                    <i data-lucide="git-branch" class="w-3 h-3"></i>
                    Atualizações do Sistema
                </div>
            </div>

            <div class="flex items-center gap-4">
                <button onclick="window.location.href='dashboard.php'" class="p-2 text-zinc-400 hover:text-white hover:bg-white/5 rounded-lg transition-all" title="Voltar">
                    <i data-lucide="arrow-left" class="w-5 h-5"></i>
                </button>
            </div>
        </header>

        <!-- Scrollable Content -->
        <div class="flex-1 overflow-y-auto p-6 md:p-8 z-10 scroll-smooth">
            <div class="max-w-4xl mx-auto space-y-8">
                
                <!-- Header Section -->
                <div class="animate-fade-in">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="p-3 rounded-xl bg-blue-500/10 border border-blue-500/20">
                            <i data-lucide="sparkles" class="w-6 h-6 text-blue-400"></i>
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-white tracking-tight mb-1">Atualizações do SafeNode</h1>
                            <p class="text-zinc-400">Fique por dentro de todas as melhorias e novidades do sistema</p>
                        </div>
                    </div>
                </div>

                <!-- Updates Timeline -->
                <div class="space-y-6">
                    <?php foreach ($updates as $index => $update): ?>
                    <div class="glass-card rounded-xl p-6 relative overflow-hidden animate-slide-up group hover:border-white/10 transition-all" style="animation-delay: <?php echo $index * 0.1; ?>s;">
                        <!-- Version Badge -->
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold border <?php echo getVersionColor($update['type']); ?>">
                                    <i data-lucide="<?php echo $update['type'] === 'major' ? 'zap' : ($update['type'] === 'minor' ? 'star' : 'check'); ?>" class="w-3 h-3"></i>
                                    v<?php echo $update['version']; ?>
                                </span>
                                <span class="text-xs text-zinc-500 font-mono"><?php echo formatUpdateDate($update['date']); ?></span>
                            </div>
                            <?php if ($index === 0): ?>
                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-[10px] font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                Última Versão
                            </span>
                            <?php endif; ?>
                        </div>

                        <!-- Title and Description -->
                        <h2 class="text-xl font-bold text-white mb-2 group-hover:text-blue-400 transition-colors">
                            <?php echo htmlspecialchars($update['title']); ?>
                        </h2>
                        <p class="text-zinc-400 text-sm mb-6">
                            <?php echo htmlspecialchars($update['description']); ?>
                        </p>

                        <!-- Features -->
                        <?php if (!empty($update['features'])): ?>
                        <div class="mb-6">
                            <h3 class="text-sm font-semibold text-white mb-3 flex items-center gap-2">
                                <i data-lucide="plus-circle" class="w-4 h-4 text-blue-400"></i>
                                Novos Recursos
                            </h3>
                            <ul class="space-y-2">
                                <?php foreach ($update['features'] as $feature): ?>
                                <li class="flex items-start gap-3 text-sm text-zinc-300">
                                    <i data-lucide="check" class="w-4 h-4 text-emerald-400 mt-0.5 flex-shrink-0"></i>
                                    <span><?php echo htmlspecialchars($feature); ?></span>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>

                        <!-- Improvements -->
                        <?php if (!empty($update['improvements'])): ?>
                        <div>
                            <h3 class="text-sm font-semibold text-white mb-3 flex items-center gap-2">
                                <i data-lucide="arrow-up-circle" class="w-4 h-4 text-purple-400"></i>
                                Melhorias
                            </h3>
                            <ul class="space-y-2">
                                <?php foreach ($update['improvements'] as $improvement): ?>
                                <li class="flex items-start gap-3 text-sm text-zinc-300">
                                    <i data-lucide="sparkles" class="w-4 h-4 text-purple-400 mt-0.5 flex-shrink-0"></i>
                                    <span><?php echo htmlspecialchars($improvement); ?></span>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>

                        <!-- Decorative Line -->
                        <?php if ($index < count($updates) - 1): ?>
                        <div class="absolute bottom-0 left-8 w-px h-full bg-gradient-to-b from-transparent via-blue-500/20 to-transparent opacity-50"></div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Footer Info -->
                <div class="glass-card rounded-xl p-6 text-center animate-fade-in">
                    <div class="flex items-center justify-center gap-2 mb-3">
                        <i data-lucide="info" class="w-5 h-5 text-blue-400"></i>
                        <h3 class="text-sm font-semibold text-white">Sobre as Atualizações</h3>
                    </div>
                    <p class="text-xs text-zinc-400 mb-4">
                        O SafeNode está em constante evolução. Nesta página você encontra todas as atualizações importantes do sistema, incluindo novos recursos, melhorias e correções.
                    </p>
                    <p class="text-xs text-zinc-500">
                        Versão atual: <span class="font-mono text-zinc-400"><?php echo $updates[0]['version']; ?></span> · 
                        Última atualização: <?php echo formatUpdateDate($updates[0]['date']); ?>
                    </p>
                </div>

            </div>
        </div>
    </main>

    <script>
        // Initialize Lucide Icons
        lucide.createIcons();
    </script>
</body>
</html>

