<?php
/**
 * SafeNode - Documentação Pública
 * Página de documentação acessível publicamente
 */

// Não requer login - página pública
?>
<!DOCTYPE html>
<html lang="pt-BR" class="dark h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentação | SafeNode</title>
    <meta name="description" content="Documentação completa da API e sistema SafeNode - Plataforma de segurança em tempo real">
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
            --bg-card: #0a0a0a;
            --border-subtle: rgba(255,255,255,0.04);
            --border-light: rgba(255,255,255,0.08);
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
        }
        
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { 
            background: rgba(255,255,255,0.1); 
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.15); }
        
        .glass-card {
            background: var(--bg-card);
            border: 1px solid var(--border-subtle);
            border-radius: 16px;
            padding: 24px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .glass-card:hover {
            border-color: var(--border-light);
            transform: translateY(-2px);
            box-shadow: 0 20px 40px -20px rgba(0,0,0,0.5);
        }
        
        .code-block {
            background: #000000;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 20px;
            overflow-x: auto;
            font-family: 'JetBrains Mono', monospace;
            font-size: 13px;
            line-height: 1.6;
        }
        
        .code-block code {
            color: #a1a1aa;
        }
        
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .badge-get { background: rgba(34, 197, 94, 0.2); color: #22c55e; }
        .badge-post { background: rgba(59, 130, 246, 0.2); color: #3b82f6; }
        .badge-put { background: rgba(251, 191, 36, 0.2); color: #fbbf24; }
        .badge-delete { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
        
        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 16px;
            border-radius: 10px;
            color: var(--text-muted);
            text-decoration: none;
            transition: all 0.2s;
        }
        
        .nav-link:hover {
            background: rgba(255,255,255,0.05);
            color: var(--text-primary);
        }
        
        .nav-link.active {
            background: rgba(255,255,255,0.1);
            color: var(--text-primary);
        }
        
        [x-cloak] { display: none !important; }
    </style>
</head>
<body x-data="{ activeSection: 'introduction', sidebarOpen: false }" class="min-h-screen">
    <!-- Header -->
    <header class="bg-dark-900/50 backdrop-blur-xl border-b border-white/5 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="index.php" class="flex items-center gap-3 group">
                    <div class="relative">
                        <div class="absolute inset-0 bg-blue-500/20 blur-lg rounded-full group-hover:bg-blue-500/40 transition-all"></div>
                        <img src="assets/img/logos (6).png" alt="SafeNode" class="h-8 w-auto relative z-10">
                    </div>
                    <span class="font-bold text-lg text-white tracking-tight group-hover:text-blue-400 transition-colors">SafeNode</span>
                </a>
            </div>
            
            <div class="flex items-center gap-4">
                <a href="index.php" class="text-zinc-400 hover:text-white transition-colors text-sm font-medium">
                    Voltar ao Site
                </a>
                <a href="login.php" class="px-4 py-2 bg-white text-black rounded-lg text-sm font-semibold hover:bg-white/90 transition-colors">
                    Entrar
                </a>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-6 py-8">
        <div class="flex gap-8">
            <!-- Sidebar Navigation -->
            <aside class="w-64 flex-shrink-0 hidden lg:block">
                <div class="sticky top-24">
                    <div class="glass-card p-4">
                        <h3 class="text-sm font-semibold text-zinc-400 uppercase tracking-wider mb-4">Navegação</h3>
                        <nav class="space-y-1">
                            <a @click="activeSection = 'introduction'" 
                               :class="activeSection === 'introduction' ? 'nav-link active' : 'nav-link'"
                               class="cursor-pointer">
                                <i data-lucide="book-open" class="w-4 h-4"></i>
                                <span class="text-sm">Introdução</span>
                            </a>
                            <a @click="activeSection = 'api'" 
                               :class="activeSection === 'api' ? 'nav-link active' : 'nav-link'"
                               class="cursor-pointer">
                                <i data-lucide="server" class="w-4 h-4"></i>
                                <span class="text-sm">API Endpoints</span>
                            </a>
                            <a @click="activeSection = 'architecture'" 
                               :class="activeSection === 'architecture' ? 'nav-link active' : 'nav-link'"
                               class="cursor-pointer">
                                <i data-lucide="layers" class="w-4 h-4"></i>
                                <span class="text-sm">Arquitetura</span>
                            </a>
                            <a @click="activeSection = 'integration'" 
                               :class="activeSection === 'integration' ? 'nav-link active' : 'nav-link'"
                               class="cursor-pointer">
                                <i data-lucide="plug" class="w-4 h-4"></i>
                                <span class="text-sm">Integração</span>
                            </a>
                            <a @click="activeSection = 'standards'" 
                               :class="activeSection === 'standards' ? 'nav-link active' : 'nav-link'"
                               class="cursor-pointer">
                                <i data-lucide="code" class="w-4 h-4"></i>
                                <span class="text-sm">Padrões de Código</span>
                            </a>
                            <a @click="activeSection = 'database'" 
                               :class="activeSection === 'database' ? 'nav-link active' : 'nav-link'"
                               class="cursor-pointer">
                                <i data-lucide="database" class="w-4 h-4"></i>
                                <span class="text-sm">Banco de Dados</span>
                            </a>
                        </nav>
                    </div>
                </div>
            </aside>
            
            <!-- Main Content -->
            <main class="flex-1 min-w-0">
                <!-- Introduction -->
                <section id="introduction" x-show="activeSection === 'introduction'" class="space-y-6">
                    <div class="glass-card">
                        <div class="flex items-center gap-4 mb-6">
                            <div class="w-12 h-12 rounded-xl bg-blue-600/20 border border-blue-500/30 flex items-center justify-center">
                                <i data-lucide="book-open" class="w-6 h-6 text-blue-400"></i>
                            </div>
                            <div>
                                <h1 class="text-3xl font-bold text-white mb-2">Documentação SafeNode</h1>
                                <p class="text-zinc-400">Guia completo da API e arquitetura do sistema</p>
                            </div>
                        </div>
                        
                        <div class="prose prose-invert max-w-none">
                            <p class="text-zinc-300 leading-relaxed mb-6">
                                O SafeNode é uma plataforma de segurança completa que oferece proteção em tempo real contra ameaças,
                                análise comportamental e monitoramento avançado. Esta documentação cobre todos os aspectos da API,
                                estrutura do código e como integrar o sistema.
                            </p>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
                                <div class="p-4 rounded-xl bg-white/5 border border-white/10">
                                    <div class="flex items-center gap-2 mb-2">
                                        <i data-lucide="shield" class="w-5 h-5 text-green-400"></i>
                                        <h3 class="font-semibold text-white">Segurança</h3>
                                    </div>
                                    <p class="text-sm text-zinc-400">Proteção em tempo real contra ameaças</p>
                                </div>
                                
                                <div class="p-4 rounded-xl bg-white/5 border border-white/10">
                                    <div class="flex items-center gap-2 mb-2">
                                        <i data-lucide="activity" class="w-5 h-5 text-blue-400"></i>
                                        <h3 class="font-semibold text-white">Monitoramento</h3>
                                    </div>
                                    <p class="text-sm text-zinc-400">Análise e logs detalhados</p>
                                </div>
                                
                                <div class="p-4 rounded-xl bg-white/5 border border-white/10">
                                    <div class="flex items-center gap-2 mb-2">
                                        <i data-lucide="code" class="w-5 h-5 text-purple-400"></i>
                                        <h3 class="font-semibold text-white">API REST</h3>
                                    </div>
                                    <p class="text-sm text-zinc-400">Integração fácil e completa</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- API Endpoints -->
                <section id="api-endpoints" x-show="activeSection === 'api'" x-cloak class="space-y-6" style="display: none;">
                    <div class="glass-card">
                        <h2 class="text-2xl font-bold text-white mb-6 flex items-center gap-3">
                            <i data-lucide="server" class="w-6 h-6 text-blue-400"></i>
                            Endpoints da API
                        </h2>
                        
                        <!-- Dashboard Stats -->
                        <div class="mb-8 pb-8 border-b border-white/10">
                            <div class="flex items-center gap-3 mb-4">
                                <span class="badge badge-get">GET</span>
                                <code class="text-white font-mono">/api/dashboard-stats.php</code>
                            </div>
                            <p class="text-zinc-400 mb-4">Retorna estatísticas em tempo real do dashboard</p>
                            
                            <div class="code-block mb-4">
                                <code>
<span style="color: #546e7a;">// Exemplo de requisição</span>
<span style="color: #c792ea;">fetch</span>(<span style="color: #c3e88d;">'api/dashboard-stats.php'</span>)
  .<span style="color: #82aaff;">then</span>(<span style="color: #c792ea;">res</span> => <span style="color: #c792ea;">res</span>.<span style="color: #82aaff;">json</span>())
  .<span style="color: #82aaff;">then</span>(<span style="color: #c792ea;">data</span> => {
    <span style="color: #546e7a;">// data.today.total_requests</span>
    <span style="color: #546e7a;">// data.today.blocked</span>
    <span style="color: #546e7a;">// data.top_blocked_ips</span>
  });
                                </code>
                            </div>
                            
                            <div class="p-4 rounded-xl bg-white/5 border border-white/10">
                                <h4 class="text-sm font-semibold text-white mb-2">Resposta:</h4>
                                <pre class="text-xs text-zinc-400 font-mono overflow-x-auto">{
  "success": true,
  "data": {
    "today": {
      "total_requests": 1250,
      "blocked": 45,
      "unique_ips": 320
    },
    "top_blocked_ips": [...],
    "recent_incidents": [...]
  }
}</pre>
                            </div>
                        </div>
                        
                        <!-- Notifications -->
                        <div class="mb-8 pb-8 border-b border-white/10">
                            <div class="flex items-center gap-3 mb-4">
                                <span class="badge badge-get">GET</span>
                                <code class="text-white font-mono">/api/notifications.php</code>
                            </div>
                            <p class="text-zinc-400 mb-4">Retorna notificações e alertas do sistema</p>
                            
                            <div class="code-block mb-4">
                                <code>
<span style="color: #546e7a;">// Buscar notificações não lidas</span>
<span style="color: #c792ea;">fetch</span>(<span style="color: #c3e88d;">'api/notifications.php?unread=1'</span>)
  .<span style="color: #82aaff;">then</span>(<span style="color: #c792ea;">res</span> => <span style="color: #c792ea;">res</span>.<span style="color: #82aaff;">json</span>())
  .<span style="color: #82aaff;">then</span>(<span style="color: #c792ea;">data</span> => {
    <span style="color: #546e7a;">// data.count - número de notificações não lidas</span>
  });

<span style="color: #546e7a;">// Listar notificações</span>
<span style="color: #c792ea;">fetch</span>(<span style="color: #c3e88d;">'api/notifications.php?limit=20'</span>)
  .<span style="color: #82aaff;">then</span>(<span style="color: #c792ea;">res</span> => <span style="color: #c792ea;">res</span>.<span style="color: #82aaff;">json</span>())
  .<span style="color: #82aaff;">then</span>(<span style="color: #c792ea;">data</span> => {
    <span style="color: #546e7a;">// data.notifications - array de notificações</span>
  });
                                </code>
                            </div>
                        </div>
                        
                        <!-- Dangerous Actions -->
                        <div class="mb-8 pb-8 border-b border-white/10">
                            <div class="flex items-center gap-3 mb-4">
                                <span class="badge badge-post">POST</span>
                                <code class="text-white font-mono">/api/dangerous-action.php</code>
                            </div>
                            <p class="text-zinc-400 mb-4">Executa ações perigosas (requer autenticação adicional)</p>
                            
                            <div class="code-block mb-4">
                                <code>
<span style="color: #546e7a;">// Encerrar todas as sessões</span>
<span style="color: #c792ea;">const</span> <span style="color: #82aaff;">formData</span> = <span style="color: #c792ea;">new</span> <span style="color: #82aaff;">FormData</span>();
<span style="color: #82aaff;">formData</span>.<span style="color: #82aaff;">append</span>(<span style="color: #c3e88d;">'action'</span>, <span style="color: #c3e88d;">'terminate_all_sessions'</span>);
<span style="color: #82aaff;">formData</span>.<span style="color: #82aaff;">append</span>(<span style="color: #c3e88d;">'password'</span>, <span style="color: #c3e88d;">'senha_do_usuario'</span>);
<span style="color: #82aaff;">formData</span>.<span style="color: #82aaff;">append</span>(<span style="color: #c3e88d;">'otp_code'</span>, <span style="color: #c3e88d;">'123456'</span>);

<span style="color: #c792ea;">fetch</span>(<span style="color: #c3e88d;">'api/dangerous-action.php'</span>, {
  <span style="color: #c792ea;">method</span>: <span style="color: #c3e88d;">'POST'</span>,
  <span style="color: #c792ea;">body</span>: <span style="color: #82aaff;">formData</span>
});
                                </code>
                            </div>
                        </div>
                        
                        <!-- SDK Endpoints -->
                        <div class="mb-8">
                            <h3 class="text-lg font-semibold text-white mb-4">SDK Endpoints</h3>
                            
                            <div class="space-y-4">
                                <div class="p-4 rounded-xl bg-white/5 border border-white/10">
                                    <div class="flex items-center gap-3 mb-2">
                                        <span class="badge badge-post">POST</span>
                                        <code class="text-white font-mono text-sm">/api/sdk/validate.php</code>
                                    </div>
                                    <p class="text-xs text-zinc-400">Valida requisições através do SDK SafeNode</p>
                                </div>
                                
                                <div class="p-4 rounded-xl bg-white/5 border border-white/10">
                                    <div class="flex items-center gap-3 mb-2">
                                        <span class="badge badge-get">GET</span>
                                        <code class="text-white font-mono text-sm">/api/sdk/init.php</code>
                                    </div>
                                    <p class="text-xs text-zinc-400">Inicializa o SDK e retorna configurações</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Architecture -->
                <section id="architecture" x-show="activeSection === 'architecture'" x-cloak class="space-y-6" style="display: none;">
                    <div class="glass-card">
                        <h2 class="text-2xl font-bold text-white mb-6 flex items-center gap-3">
                            <i data-lucide="layers" class="w-6 h-6 text-purple-400"></i>
                            Arquitetura do Sistema
                        </h2>
                        
                        <div class="space-y-6">
                            <div>
                                <h3 class="text-lg font-semibold text-white mb-3">Estrutura de Diretórios</h3>
                                <div class="code-block">
                                    <code>
safenode/
├── api/                    <span style="color: #546e7a;"># Endpoints da API</span>
│   ├── dashboard-stats.php
│   ├── notifications.php
│   └── sdk/
├── includes/               <span style="color: #546e7a;"># Classes e helpers</span>
│   ├── SecurityLogger.php
│   ├── ThreatDetector.php
│   ├── BehaviorAnalyzer.php
│   └── SecurityHelpers.php
├── assets/                 <span style="color: #546e7a;"># Recursos estáticos</span>
└── sdk/                    <span style="color: #546e7a;"># SDK JavaScript</span>
                                    </code>
                                </div>
                            </div>
                            
                            <div>
                                <h3 class="text-lg font-semibold text-white mb-3">Classes Principais</h3>
                                <div class="space-y-3">
                                    <div class="p-4 rounded-xl bg-white/5 border border-white/10">
                                        <h4 class="font-semibold text-white mb-2">SecurityLogger</h4>
                                        <p class="text-sm text-zinc-400 mb-2">Registra eventos de segurança no banco de dados</p>
                                        <code class="text-xs text-zinc-500">includes/SecurityLogger.php</code>
                                    </div>
                                    
                                    <div class="p-4 rounded-xl bg-white/5 border border-white/10">
                                        <h4 class="font-semibold text-white mb-2">ThreatDetector</h4>
                                        <p class="text-sm text-zinc-400 mb-2">Detecta e classifica ameaças em requisições</p>
                                        <code class="text-xs text-zinc-500">includes/ThreatDetector.php</code>
                                    </div>
                                    
                                    <div class="p-4 rounded-xl bg-white/5 border border-white/10">
                                        <h4 class="font-semibold text-white mb-2">BehaviorAnalyzer</h4>
                                        <p class="text-sm text-zinc-400 mb-2">Analisa padrões comportamentais suspeitos</p>
                                        <code class="text-xs text-zinc-500">includes/BehaviorAnalyzer.php</code>
                                    </div>
                                    
                                    <div class="p-4 rounded-xl bg-white/5 border border-white/10">
                                        <h4 class="font-semibold text-white mb-2">SecurityAnalytics</h4>
                                        <p class="text-sm text-zinc-400 mb-2">Gera insights e análises avançadas</p>
                                        <code class="text-xs text-zinc-500">includes/SecurityAnalytics.php</code>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Integration Guide -->
                <section id="integration" x-show="activeSection === 'integration'" x-cloak class="space-y-6" style="display: none;">
                    <div class="glass-card">
                        <h2 class="text-2xl font-bold text-white mb-6 flex items-center gap-3">
                            <i data-lucide="plug" class="w-6 h-6 text-green-400"></i>
                            Guia de Integração
                        </h2>
                        
                        <div class="space-y-6">
                            <div>
                                <h3 class="text-lg font-semibold text-white mb-3">1. Instalação do SDK</h3>
                                <div class="code-block">
                                    <code>
<span style="color: #546e7a;">&lt;!-- Adicione o SDK no seu HTML --&gt;</span>
<span style="color: #c792ea;">&lt;script</span> <span style="color: #82aaff;">src</span>=<span style="color: #c3e88d;">"https://safenode.cloud/sdk/safenode-hv.js"</span><span style="color: #c792ea;">&gt;&lt;/script&gt;</span>

<span style="color: #546e7a;">// Ou via npm (futuro)</span>
<span style="color: #82aaff;">npm</span> <span style="color: #c792ea;">install</span> <span style="color: #c3e88d;">@safenode/sdk</span>
                                    </code>
                                </div>
                            </div>
                            
                            <div>
                                <h3 class="text-lg font-semibold text-white mb-3">2. Inicialização</h3>
                                <div class="code-block">
                                    <code>
<span style="color: #c792ea;">const</span> <span style="color: #82aaff;">safenode</span> = <span style="color: #c792ea;">new</span> <span style="color: #82aaff;">SafeNode</span>({
  <span style="color: #c792ea;">apiKey</span>: <span style="color: #c3e88d;">'sua-api-key'</span>,
  <span style="color: #c792ea;">siteId</span>: <span style="color: #f78c6c;">123</span>
});

<span style="color: #546e7a;">// Validar requisição</span>
<span style="color: #82aaff;">safenode</span>.<span style="color: #82aaff;">validate</span>(<span style="color: #c792ea;">request</span>)
  .<span style="color: #82aaff;">then</span>(<span style="color: #c792ea;">result</span> => {
    <span style="color: #c792ea;">if</span> (<span style="color: #c792ea;">result</span>.<span style="color: #82aaff;">isValid</span>) {
      <span style="color: #546e7a;">// Processar requisição</span>
    } <span style="color: #c792ea;">else</span> {
      <span style="color: #546e7a;">// Bloquear requisição</span>
    }
  });
                                    </code>
                                </div>
                            </div>
                            
                            <div>
                                <h3 class="text-lg font-semibold text-white mb-3">3. Exemplo Completo (PHP)</h3>
                                <div class="code-block">
                                    <code>
<span style="color: #546e7a;">&lt;?php</span>
<span style="color: #c792ea;">require_once</span> <span style="color: #c3e88d;">'includes/SafeNodeMiddleware.php'</span>;

<span style="color: #546e7a;">// Aplicar middleware em todas as requisições</span>
<span style="color: #82aaff;">SafeNodeMiddleware</span>::<span style="color: #82aaff;">protect</span>();

<span style="color: #546e7a;">// Seu código continua normalmente</span>
<span style="color: #c792ea;">echo</span> <span style="color: #c3e88d;">"Página protegida!"</span>;
                                    </code>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Code Standards -->
                <section id="standards" x-show="activeSection === 'standards'" x-cloak class="space-y-6" style="display: none;">
                    <div class="glass-card">
                        <h2 class="text-2xl font-bold text-white mb-6 flex items-center gap-3">
                            <i data-lucide="code" class="w-6 h-6 text-amber-400"></i>
                            Padrões de Código
                        </h2>
                        
                        <div class="space-y-6">
                            <div>
                                <h3 class="text-lg font-semibold text-white mb-3">PSR-12 Coding Standard</h3>
                                <p class="text-zinc-400 mb-4">
                                    O SafeNode segue o padrão PSR-12 para garantir consistência e legibilidade do código.
                                </p>
                                
                                <div class="p-4 rounded-xl bg-white/5 border border-white/10">
                                    <h4 class="text-sm font-semibold text-white mb-2">Principais Regras:</h4>
                                    <ul class="text-sm text-zinc-400 space-y-2 list-disc list-inside">
                                        <li>Indentação: 4 espaços (não tabs)</li>
                                        <li>Linhas: máximo 120 caracteres</li>
                                        <li>Nomes de classes: PascalCase</li>
                                        <li>Nomes de métodos: camelCase</li>
                                        <li>Constantes: UPPER_SNAKE_CASE</li>
                                    </ul>
                                </div>
                            </div>
                            
                            <div>
                                <h3 class="text-lg font-semibold text-white mb-3">Estrutura de Classes</h3>
                                <div class="code-block">
                                    <code>
<span style="color: #546e7a;">&lt;?php</span>
<span style="color: #546e7a;">/**
 * SafeNode - Nome da Classe
 * Descrição breve da funcionalidade
 */</span>

<span style="color: #c792ea;">class</span> <span style="color: #82aaff;">ClassName</span> {
    <span style="color: #546e7a;">// Propriedades privadas</span>
    <span style="color: #c792ea;">private</span> <span style="color: #c792ea;">$property</span>;
    
    <span style="color: #546e7a;">// Construtor</span>
    <span style="color: #c792ea;">public function</span> <span style="color: #82aaff;">__construct</span>(<span style="color: #c792ea;">$param</span>) {
        <span style="color: #c792ea;">$this</span>-><span style="color: #c792ea;">property</span> = <span style="color: #c792ea;">$param</span>;
    }
    
    <span style="color: #546e7a;">// Métodos públicos</span>
    <span style="color: #c792ea;">public function</span> <span style="color: #82aaff;">publicMethod</span>() {
        <span style="color: #546e7a;">// Implementação</span>
    }
}
                                    </code>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Database Schema -->
                <section id="database" x-show="activeSection === 'database'" x-cloak class="space-y-6" style="display: none;">
                    <div class="glass-card">
                        <h2 class="text-2xl font-bold text-white mb-6 flex items-center gap-3">
                            <i data-lucide="database" class="w-6 h-6 text-cyan-400"></i>
                            Estrutura do Banco de Dados
                        </h2>
                        
                        <div class="space-y-4">
                            <div class="p-4 rounded-xl bg-white/5 border border-white/10">
                                <h4 class="font-semibold text-white mb-2">safenode_security_logs</h4>
                                <p class="text-sm text-zinc-400 mb-2">Armazena todos os eventos de segurança</p>
                                <code class="text-xs text-zinc-500">Campos principais: ip_address, threat_type, threat_score, action_taken, created_at</code>
                            </div>
                            
                            <div class="p-4 rounded-xl bg-white/5 border border-white/10">
                                <h4 class="font-semibold text-white mb-2">safenode_sites</h4>
                                <p class="text-sm text-zinc-400 mb-2">Sites protegidos pelo sistema</p>
                                <code class="text-xs text-zinc-500">Campos principais: domain, display_name, security_level, is_active</code>
                            </div>
                            
                            <div class="p-4 rounded-xl bg-white/5 border border-white/10">
                                <h4 class="font-semibold text-white mb-2">safenode_users</h4>
                                <p class="text-sm text-zinc-400 mb-2">Usuários do sistema</p>
                                <code class="text-xs text-zinc-500">Campos principais: email, username, password_hash, role</code>
                            </div>
                        </div>
                    </div>
                </section>
            </main>
        </div>
    </div>

    <!-- Footer -->
    <footer class="border-t border-white/5 mt-16 py-8">
        <div class="max-w-7xl mx-auto px-6">
            <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <img src="assets/img/logos (6).png" alt="SafeNode" class="h-6 w-auto">
                    <span class="text-sm text-zinc-400">SafeNode © 2024. Todos os direitos reservados.</span>
                </div>
                <div class="flex items-center gap-6 text-sm text-zinc-400">
                    <a href="index.php" class="hover:text-white transition-colors">Início</a>
                    <a href="login.php" class="hover:text-white transition-colors">Login</a>
                    <a href="register.php" class="hover:text-white transition-colors">Cadastro</a>
                </div>
            </div>
        </div>
    </footer>

    <script>
        lucide.createIcons();
        
        // Inicializar primeira seção visível
        document.addEventListener('DOMContentLoaded', function() {
            const firstSection = document.querySelector('#introduction');
            if (firstSection) {
                firstSection.style.display = 'block';
            }
        });
    </script>
</body>
</html>

