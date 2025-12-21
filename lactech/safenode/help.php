<?php
/**
 * SafeNode - Ajuda e Suporte
 */

session_start();
require_once __DIR__ . '/includes/SecurityHelpers.php';
SecurityHeaders::apply();

if (!isset($_SESSION['safenode_logged_in']) || $_SESSION['safenode_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/init.php';

$pageTitle = 'Ajuda';
$currentSiteId = $_SESSION['view_site_id'] ?? 0;
$userId = $_SESSION['safenode_user_id'] ?? null;
$selectedSite = null;

$db = getSafeNodeDatabase();
if ($db && $currentSiteId > 0) {
    try {
        $stmt = $db->prepare("SELECT * FROM safenode_sites WHERE id = ? AND user_id = ?");
        $stmt->execute([$currentSiteId, $userId]);
        $selectedSite = $stmt->fetch();
    } catch (PDOException $e) {
        $selectedSite = null;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR" class="dark h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> | SafeNode</title>
    <link rel="icon" type="image/png" href="assets/img/logos (6).png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
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
                        dark: {
                            950: '#030303',
                            900: '#050505',
                            850: '#080808',
                            800: '#0a0a0a',
                            700: '#0f0f0f',
                            600: '#141414',
                            500: '#1a1a1a',
                            400: '#222222',
                        }
                    }
                }
            }
        }
    </script>
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        :root {
            --bg-primary: #030303;
            --bg-secondary: #080808;
            --bg-tertiary: #0f0f0f;
            --bg-card: #0a0a0a;
            --bg-hover: #111111;
            --border-subtle: rgba(255,255,255,0.04);
            --border-light: rgba(255,255,255,0.08);
            --accent: #ffffff;
            --accent-glow: rgba(255, 255, 255, 0.2);
            --text-primary: #ffffff;
            --text-secondary: #a1a1aa;
            --text-muted: #52525b;
        }
        
        body {
            background-color: var(--bg-primary);
            color: var(--text-secondary);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            font-size: 0.92em;
        }
        
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { 
            background: rgba(255,255,255,0.1); 
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.15); }
        
        .glass {
            background: rgba(10, 10, 10, 0.7);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--border-subtle);
        }
        
        .sidebar {
            background: linear-gradient(180deg, var(--bg-secondary) 0%, var(--bg-primary) 100%);
            border-right: 1px solid var(--border-subtle);
            position: relative;
        }
        
        .nav-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 12px 16px;
            border-radius: 12px;
            color: var(--text-muted);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            text-decoration: none;
        }
        
        .nav-item:hover {
            background: rgba(255,255,255,0.05);
            color: var(--text-primary);
        }
        
        .nav-item.active {
            background: rgba(255,255,255,0.1);
            color: var(--text-primary);
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
            box-shadow: 0 10px 30px -10px rgba(255, 255, 255, 0.5);
        }
        
        .btn-primary:hover::before {
            opacity: 1;
        }
    </style>
</head>
<body class="h-full" x-data="{ sidebarOpen: false }">
    <div class="flex h-full">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="flex-1 flex flex-col h-full overflow-hidden bg-dark-950">
            <!-- Header -->
            <header class="h-20 bg-dark-900/50 backdrop-blur-xl border-b border-white/5 px-8 flex items-center justify-between flex-shrink-0">
                <div class="flex items-center gap-6">
                    <button data-sidebar-toggle class="lg:hidden text-zinc-400 hover:text-white transition-colors">
                        <i data-lucide="menu" class="w-6 h-6"></i>
                    </button>
                    <div>
                        <h2 class="text-2xl font-bold text-white tracking-tight">Ajuda e Suporte</h2>
                        <?php if ($selectedSite): ?>
                            <p class="text-sm text-zinc-500 font-mono mt-0.5"><?php echo htmlspecialchars($selectedSite['domain'] ?? ''); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <div class="flex-1 overflow-y-auto p-8">
                <div class="max-w-4xl mx-auto space-y-6">
                    <!-- Introdução -->
                    <div class="glass rounded-2xl p-6">
                        <div class="flex items-center gap-4 mb-4">
                            <div class="w-12 h-12 rounded-xl bg-white/10 flex items-center justify-center p-2">
                                <img src="assets/img/logos (6).png" alt="SafeNode Logo" class="w-full h-full object-contain">
                            </div>
                            <div>
                                <h3 class="text-xl font-semibold text-white">Bem-vindo ao SafeNode</h3>
                                <p class="text-sm text-zinc-400">Sistema de segurança web completo</p>
                            </div>
                        </div>
                        <p class="text-zinc-300 leading-relaxed">
                            O SafeNode é uma plataforma de segurança web que protege seus sites contra ameaças, 
                            detecta atividades suspeitas e oferece controle total sobre a segurança da sua aplicação.
                        </p>
                    </div>

                    <!-- Guias Rápidos -->
                    <div class="glass rounded-2xl p-6">
                        <h3 class="text-lg font-semibold text-white mb-4">Guias Rápidos</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <a href="dashboard.php" class="p-4 rounded-xl bg-white/5 border border-white/10 hover:bg-white/10 transition-colors">
                                <div class="flex items-center gap-3 mb-2">
                                    <i data-lucide="layout-dashboard" class="w-5 h-5 text-white"></i>
                                    <span class="font-semibold text-white">Dashboard</span>
                                </div>
                                <p class="text-xs text-zinc-400">Visualize estatísticas e métricas de segurança em tempo real</p>
                            </a>
                            
                            <a href="sites.php" class="p-4 rounded-xl bg-white/5 border border-white/10 hover:bg-white/10 transition-colors">
                                <div class="flex items-center gap-3 mb-2">
                                    <i data-lucide="globe" class="w-5 h-5 text-white"></i>
                                    <span class="font-semibold text-white">Gerenciar Sites</span>
                                </div>
                                <p class="text-xs text-zinc-400">Adicione e configure sites para proteção</p>
                            </a>
                            
                            <a href="human-verification.php" class="p-4 rounded-xl bg-white/5 border border-white/10 hover:bg-white/10 transition-colors">
                                <div class="flex items-center gap-3 mb-2">
                                    <i data-lucide="shield-check" class="w-5 h-5 text-white"></i>
                                    <span class="font-semibold text-white">Verificação Humana</span>
                                </div>
                                <p class="text-xs text-zinc-400">Configure o SDK de verificação humana para seus sites</p>
                            </a>
                            
                            <a href="logs.php" class="p-4 rounded-xl bg-white/5 border border-white/10 hover:bg-white/10 transition-colors">
                                <div class="flex items-center gap-3 mb-2">
                                    <i data-lucide="compass" class="w-5 h-5 text-white"></i>
                                    <span class="font-semibold text-white">Explorar Logs</span>
                                </div>
                                <p class="text-xs text-zinc-400">Analise logs de segurança e eventos detectados</p>
                            </a>
                        </div>
                    </div>

                    <!-- Recursos Principais -->
                    <div class="glass rounded-2xl p-6">
                        <h3 class="text-lg font-semibold text-white mb-4">Recursos Principais</h3>
                        <div class="space-y-4">
                            <div class="flex items-start gap-4 p-4 rounded-xl bg-white/5 border border-white/10">
                                <i data-lucide="shield" class="w-5 h-5 text-white mt-0.5"></i>
                                <div>
                                    <h4 class="font-semibold text-white mb-1">Proteção Automática</h4>
                                    <p class="text-sm text-zinc-400">Bloqueio automático de IPs suspeitos e detecção de ameaças em tempo real</p>
                                </div>
                            </div>
                            
                            <div class="flex items-start gap-4 p-4 rounded-xl bg-white/5 border border-white/10">
                                <i data-lucide="activity" class="w-5 h-5 text-white mt-0.5"></i>
                                <div>
                                    <h4 class="font-semibold text-white mb-1">Análise de Comportamento</h4>
                                    <p class="text-sm text-zinc-400">Sistema inteligente que analisa padrões de comportamento e identifica anomalias</p>
                                </div>
                            </div>
                            
                            <div class="flex items-start gap-4 p-4 rounded-xl bg-white/5 border border-white/10">
                                <i data-lucide="bar-chart-3" class="w-5 h-5 text-white mt-0.5"></i>
                                <div>
                                    <h4 class="font-semibold text-white mb-1">Analytics Avançado</h4>
                                    <p class="text-sm text-zinc-400">Estatísticas detalhadas e relatórios de segurança</p>
                                </div>
                            </div>
                            
                            <div class="flex items-start gap-4 p-4 rounded-xl bg-white/5 border border-white/10">
                                <i data-lucide="cloud" class="w-5 h-5 text-white mt-0.5"></i>
                                <div>
                                    <h4 class="font-semibold text-white mb-1">Integração Cloudflare</h4>
                                    <p class="text-sm text-zinc-400">Funciona standalone ou em conjunto com Cloudflare</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Perguntas Frequentes -->
                    <div class="glass rounded-2xl p-6">
                        <h3 class="text-lg font-semibold text-white mb-4">Perguntas Frequentes</h3>
                        <div class="space-y-4" x-data="{ openFaq: null }">
                            <div class="border-b border-white/10 pb-4 last:border-0 last:pb-0">
                                <button @click="openFaq = openFaq === 1 ? null : 1" class="w-full flex items-center justify-between text-left">
                                    <span class="font-semibold text-white">Como adicionar um site?</span>
                                    <i data-lucide="chevron-down" class="w-5 h-5 text-zinc-400 transition-transform" :class="{ 'rotate-180': openFaq === 1 }"></i>
                                </button>
                                <div x-show="openFaq === 1" class="mt-3 text-sm text-zinc-400">
                                    Acesse "Gerenciar Sites" no menu e clique em "Adicionar Site". Informe o domínio e siga as instruções de verificação.
                                </div>
                            </div>
                            
                            <div class="border-b border-white/10 pb-4 last:border-0 last:pb-0">
                                <button @click="openFaq = openFaq === 2 ? null : 2" class="w-full flex items-center justify-between text-left">
                                    <span class="font-semibold text-white">Como usar a verificação humana?</span>
                                    <i data-lucide="chevron-down" class="w-5 h-5 text-zinc-400 transition-transform" :class="{ 'rotate-180': openFaq === 2 }"></i>
                                </button>
                                <div x-show="openFaq === 2" class="mt-3 text-sm text-zinc-400">
                                    Acesse "Verificação Humana" no menu, gere uma API key e copie o código fornecido. Cole o código no seu site antes do fechamento da tag &lt;/body&gt;.
                                </div>
                            </div>
                            
                            <div class="border-b border-white/10 pb-4 last:border-0 last:pb-0">
                                <button @click="openFaq = openFaq === 3 ? null : 3" class="w-full flex items-center justify-between text-left">
                                    <span class="font-semibold text-white">O SafeNode funciona sem Cloudflare?</span>
                                    <i data-lucide="chevron-down" class="w-5 h-5 text-zinc-400 transition-transform" :class="{ 'rotate-180': openFaq === 3 }"></i>
                                </button>
                                <div x-show="openFaq === 3" class="mt-3 text-sm text-zinc-400">
                                    Sim! O SafeNode pode funcionar de forma independente ou em conjunto com Cloudflare. Configure conforme sua necessidade.
                                </div>
                            </div>
                            
                            <div class="border-b border-white/10 pb-4 last:border-0 last:pb-0">
                                <button @click="openFaq = openFaq === 4 ? null : 4" class="w-full flex items-center justify-between text-left">
                                    <span class="font-semibold text-white">Como visualizar logs de segurança?</span>
                                    <i data-lucide="chevron-down" class="w-5 h-5 text-zinc-400 transition-transform" :class="{ 'rotate-180': openFaq === 4 }"></i>
                                </button>
                                <div x-show="openFaq === 4" class="mt-3 text-sm text-zinc-400">
                                    Acesse "Explorar" no menu para ver todos os logs. Use os filtros para encontrar eventos específicos por tipo, IP ou data.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Contato -->
                    <div class="glass rounded-2xl p-6">
                        <h3 class="text-lg font-semibold text-white mb-4">Precisa de Ajuda?</h3>
                        <p class="text-zinc-400 text-sm mb-4">
                            Se você não encontrou a resposta que procurava, entre em contato com nosso suporte.
                        </p>
                        <div class="flex gap-4">
                            <a href="mailto:support@safenode.cloud" class="px-4 py-2 bg-white/10 text-white rounded-xl hover:bg-white/20 transition-colors text-sm font-semibold">
                                <i data-lucide="mail" class="w-4 h-4 inline mr-2"></i>
                                Email de Suporte
                            </a>
                            <a href="dashboard.php" class="px-4 py-2 bg-white text-black rounded-xl hover:bg-white/90 transition-colors text-sm font-semibold">
                                Voltar ao Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        lucide.createIcons();
    </script>
    
    <!-- Security Scripts - Previne download de código -->
    <script src="includes/security-scripts.js"></script>
</body>
</html>

