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
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

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

