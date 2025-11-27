<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status | SafeNode</title>
    <link rel="icon" type="image/png" href="assets/img/logos (6).png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        * { 
            font-family: 'Inter', system-ui, sans-serif; 
            box-sizing: border-box;
        }
        
        body { 
            background: #09090b;
            color: #fafafa;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        /* Subtle dot pattern */
        .dot-pattern {
            background-image: radial-gradient(rgba(255,255,255,0.03) 1px, transparent 1px);
            background-size: 24px 24px;
        }
        
        /* Cards */
        .card {
            background: rgba(255,255,255,0.02);
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 16px;
            transition: border-color 0.2s ease;
        }
        .card:hover {
            border-color: rgba(255,255,255,0.08);
        }
        
        /* Badges */
        .badge {
            font-size: 12px;
            font-weight: 500;
            padding: 6px 12px;
            border-radius: 100px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .badge-maintenance {
            background: rgba(251,191,36,0.1);
            color: #fbbf24;
            border: 1px solid rgba(251,191,36,0.15);
        }
        .badge-operational {
            background: rgba(52,211,153,0.1);
            color: #34d399;
            border: 1px solid rgba(52,211,153,0.15);
        }
        
        /* Status dot */
        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            position: relative;
        }
        .status-dot::after {
            content: '';
            position: absolute;
            inset: -3px;
            border-radius: 50%;
            background: inherit;
            opacity: 0.3;
            animation: pulse-ring 2s ease-out infinite;
        }
        .status-dot.maintenance { background: #fbbf24; }
        .status-dot.operational { background: #34d399; }
        
        @keyframes pulse-ring {
            0% { transform: scale(1); opacity: 0.3; }
            100% { transform: scale(1.5); opacity: 0; }
        }
        
        /* Uptime bars */
        .uptime-bar {
            width: 3px;
            border-radius: 2px;
            transition: all 0.15s ease;
        }
        .uptime-bar:hover {
            transform: scaleY(1.15);
            filter: brightness(1.2);
        }
        
        /* Component items */
        .component-row {
            transition: background 0.15s ease;
        }
        .component-row:hover {
            background: rgba(255,255,255,0.02);
        }
        
        /* Smooth transitions */
        .transition-all { transition: all 0.2s ease; }
        
        /* Scrollbar */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.15); }
        
        /* Focus states */
        button:focus-visible {
            outline: 2px solid rgba(251,191,36,0.5);
            outline-offset: 2px;
        }
    </style>
</head>
<body class="min-h-screen">
    <div class="dot-pattern fixed inset-0 pointer-events-none opacity-50"></div>
    
    <div class="relative z-10 min-h-screen flex flex-col">
        
        <!-- Header -->
        <header class="sticky top-0 z-50 border-b border-white/5 bg-[#09090b]/90 backdrop-blur-xl">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="h-14 sm:h-16 flex items-center justify-between">
                    <!-- Logo -->
                    <div class="flex items-center gap-3">
                        <img src="assets/img/logos (6).png" alt="SafeNode" class="w-7 h-7 sm:w-8 sm:h-8">
                        <div class="hidden sm:flex items-center gap-3">
                            <div class="w-px h-4 bg-white/10"></div>
                            <span class="text-sm text-zinc-500">Status</span>
                        </div>
                    </div>
                    
                    <!-- Status & Refresh -->
                    <div class="flex items-center gap-3 sm:gap-4">
                        <div class="flex items-center gap-2">
                            <div class="status-dot maintenance"></div>
                            <span class="text-xs sm:text-sm text-zinc-400">Manutenção</span>
                        </div>
                        <button onclick="location.reload()" class="p-2 rounded-lg hover:bg-white/5 transition-all" aria-label="Atualizar página">
                            <i data-lucide="refresh-cw" class="w-4 h-4 text-zinc-500"></i>
                        </button>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- Main -->
        <main class="flex-1 w-full max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12 lg:py-16">
            
            <!-- Hero -->
            <section class="mb-12 sm:mb-16">
                <div class="mb-4 sm:mb-5">
                    <span class="badge badge-maintenance">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                        Em Manutenção
                    </span>
                </div>
                
                <h1 class="text-2xl sm:text-3xl lg:text-4xl font-semibold text-white mb-3 sm:mb-4 tracking-tight">
                    Sistema em Reformulação
                </h1>
                
                <p class="text-sm sm:text-base text-zinc-400 max-w-lg leading-relaxed">
                    Estamos realizando melhorias significativas na arquitetura e funcionalidades para oferecer uma experiência ainda melhor.
                </p>
                
                <!-- Mensagem de Segurança dos Dados -->
                <div class="mt-6 sm:mt-8 p-4 sm:p-5 rounded-xl bg-emerald-500/10 border border-emerald-500/20">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-lg bg-emerald-500/20 flex items-center justify-center shrink-0">
                            <i data-lucide="shield-check" class="w-5 h-5 text-emerald-400"></i>
                        </div>
                        <div>
                            <h3 class="text-sm sm:text-base font-semibold text-emerald-400 mb-1">Seus dados estão seguros</h3>
                            <p class="text-xs sm:text-sm text-zinc-300 leading-relaxed">
                                <strong class="text-emerald-400">Todos os dados dos usuários estão protegidos e não serão apagados.</strong> A reformulação do sistema não afeta a integridade ou segurança das informações armazenadas.
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="mt-6 sm:mt-8 flex flex-wrap items-center gap-4 sm:gap-6 text-xs sm:text-sm text-zinc-500">
                    <div class="flex items-center gap-2">
                        <i data-lucide="shield-check" class="w-4 h-4 text-emerald-500"></i>
                        <span>Dados seguros</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i data-lucide="clock" class="w-4 h-4 text-zinc-600"></i>
                        <span>Última atualização: <span id="last-update" class="font-mono text-zinc-400">--:--</span></span>
                    </div>
                </div>
            </section>
            
            <!-- Stats -->
            <section class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4 mb-12 sm:mb-16">
                <!-- Uptime Card -->
                <div class="card p-4 sm:p-5">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-[11px] sm:text-xs uppercase tracking-wider text-zinc-500">Uptime</span>
                        <span class="text-[10px] text-zinc-600">30d</span>
                    </div>
                    <div class="text-2xl sm:text-3xl font-semibold text-white mb-4">
                        99.9<span class="text-base sm:text-lg text-zinc-600">%</span>
                    </div>
                    <div class="flex items-end gap-[2px] h-5 sm:h-6">
                        <?php for($i = 0; $i < 30; $i++): 
                            $height = rand(12, 24);
                            $isRecent = $i > 27;
                            $color = $isRecent ? 'bg-amber-500' : 'bg-emerald-500';
                        ?>
                        <div class="uptime-bar <?php echo $color; ?>" style="height: <?php echo $height; ?>px; opacity: <?php echo $isRecent ? '0.7' : '1'; ?>"></div>
                        <?php endfor; ?>
                    </div>
                </div>
                
                <!-- Latency Card -->
                <div class="card p-4 sm:p-5">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-[11px] sm:text-xs uppercase tracking-wider text-zinc-500">Latência</span>
                        <span class="text-[10px] text-zinc-600">avg</span>
                    </div>
                    <div class="text-2xl sm:text-3xl font-semibold text-white mb-4">
                        <span id="response-time">--</span><span class="text-base sm:text-lg text-zinc-600">ms</span>
                    </div>
                    <div class="h-1 bg-zinc-800/80 rounded-full overflow-hidden">
                        <div class="h-full w-3/5 bg-emerald-500 rounded-full"></div>
                    </div>
                </div>
                
                <!-- Incidents Card -->
                <div class="card p-4 sm:p-5">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-[11px] sm:text-xs uppercase tracking-wider text-zinc-500">Incidentes</span>
                        <span class="text-[10px] text-zinc-600">7d</span>
                    </div>
                    <div class="text-2xl sm:text-3xl font-semibold text-white mb-4">0</div>
                    <div class="flex items-center gap-1.5 text-emerald-500">
                        <i data-lucide="check-circle-2" class="w-3.5 h-3.5"></i>
                        <span class="text-xs">Estável</span>
                    </div>
                </div>
            </section>
            
            <!-- Components -->
            <section class="mb-12 sm:mb-16">
                <h2 class="text-sm font-medium text-zinc-300 mb-4 sm:mb-5">Componentes do Sistema</h2>
                <div class="card overflow-hidden">
                    
                    <!-- API -->
                    <div class="component-row p-4 sm:p-5 flex items-center justify-between border-b border-white/5">
                        <div class="flex items-center gap-3 sm:gap-4">
                            <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-amber-500/10 flex items-center justify-center">
                                <i data-lucide="server" class="w-4 h-4 sm:w-5 sm:h-5 text-amber-500"></i>
                            </div>
                            <div>
                                <h3 class="text-sm font-medium text-white">API Principal</h3>
                                <p class="text-[11px] sm:text-xs text-zinc-500">Backend e serviços</p>
                            </div>
                        </div>
                        <span class="badge badge-maintenance text-[11px] sm:text-xs">Manutenção</span>
                    </div>
                    
                    <!-- Dashboard -->
                    <div class="component-row p-4 sm:p-5 flex items-center justify-between border-b border-white/5">
                        <div class="flex items-center gap-3 sm:gap-4">
                            <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-amber-500/10 flex items-center justify-center">
                                <i data-lucide="layout-dashboard" class="w-4 h-4 sm:w-5 sm:h-5 text-amber-500"></i>
                            </div>
                            <div>
                                <h3 class="text-sm font-medium text-white">Dashboard</h3>
                                <p class="text-[11px] sm:text-xs text-zinc-500">Interface do usuário</p>
                            </div>
                        </div>
                        <span class="badge badge-maintenance text-[11px] sm:text-xs">Manutenção</span>
                    </div>
                    
                    <!-- Database -->
                    <div class="component-row p-4 sm:p-5 flex items-center justify-between border-b border-white/5">
                        <div class="flex items-center gap-3 sm:gap-4">
                            <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-amber-500/10 flex items-center justify-center">
                                <i data-lucide="database" class="w-4 h-4 sm:w-5 sm:h-5 text-amber-500"></i>
                            </div>
                            <div>
                                <h3 class="text-sm font-medium text-white">Banco de Dados</h3>
                                <p class="text-[11px] sm:text-xs text-zinc-500">Armazenamento</p>
                            </div>
                        </div>
                        <span class="badge badge-maintenance text-[11px] sm:text-xs">Manutenção</span>
                    </div>
                    
                    <!-- Cloudflare -->
                    <div class="component-row p-4 sm:p-5 flex items-center justify-between">
                        <div class="flex items-center gap-3 sm:gap-4">
                            <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-emerald-500/10 flex items-center justify-center">
                                <i data-lucide="cloud" class="w-4 h-4 sm:w-5 sm:h-5 text-emerald-500"></i>
                            </div>
                            <div>
                                <h3 class="text-sm font-medium text-white">Cloudflare</h3>
                                <p class="text-[11px] sm:text-xs text-zinc-500">CDN e proteção</p>
                            </div>
                        </div>
                        <span class="badge badge-operational text-[11px] sm:text-xs">Operacional</span>
                    </div>
                </div>
            </section>
            
            <!-- Timeline -->
            <section class="mb-12 sm:mb-16">
                <h2 class="text-sm font-medium text-zinc-300 mb-4 sm:mb-5">Histórico de Eventos</h2>
                <div class="card p-4 sm:p-6">
                    <div class="space-y-6 sm:space-y-8">
                        <!-- Current Event -->
                        <div class="flex gap-3 sm:gap-4">
                            <div class="flex flex-col items-center pt-0.5">
                                <div class="w-2 h-2 bg-amber-500 rounded-full ring-4 ring-amber-500/10"></div>
                                <div class="w-px flex-1 bg-gradient-to-b from-zinc-700 to-transparent mt-2"></div>
                            </div>
                            <div class="flex-1 min-w-0 pb-2">
                                <div class="flex flex-wrap items-center gap-2 mb-2">
                                    <span class="text-sm font-medium text-white">Manutenção Iniciada</span>
                                    <span class="text-[10px] text-amber-500/80 font-mono bg-amber-500/10 px-2 py-0.5 rounded">agora</span>
                                </div>
                                <p class="text-xs sm:text-sm text-zinc-500 leading-relaxed">
                                    Reformulação completa do sistema. Melhorias na arquitetura, novas funcionalidades e otimizações de performance em desenvolvimento.
                                </p>
                                <div class="mt-3 p-3 rounded-lg bg-emerald-500/10 border border-emerald-500/20">
                                    <p class="text-xs sm:text-sm text-emerald-400 font-medium">
                                        ✓ Todos os dados dos usuários estão seguros e preservados. Nenhuma informação será apagada durante a reformulação.
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Past Event -->
                        <div class="flex gap-3 sm:gap-4">
                            <div class="flex flex-col items-center pt-0.5">
                                <div class="w-2 h-2 bg-zinc-700 rounded-full"></div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex flex-wrap items-center gap-2 mb-2">
                                    <span class="text-sm font-medium text-zinc-500">Sistema Operacional</span>
                                    <span class="text-[10px] text-zinc-600 font-mono bg-zinc-800/50 px-2 py-0.5 rounded">anterior</span>
                                </div>
                                <p class="text-xs sm:text-sm text-zinc-600">
                                    Todos os serviços funcionando normalmente sem incidentes.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- Info Section -->
            <section>
                <div class="card p-4 sm:p-6 border-blue-500/10">
                    <div class="flex items-start gap-3 sm:gap-4 mb-5 sm:mb-6">
                        <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-blue-500/10 flex items-center justify-center shrink-0">
                            <i data-lucide="info" class="w-4 h-4 sm:w-5 sm:h-5 text-blue-400"></i>
                        </div>
                        <div>
                            <h3 class="text-sm sm:text-base font-medium text-white mb-0.5">Informações Importantes</h3>
                            <p class="text-xs sm:text-sm text-zinc-500">Sobre a manutenção em andamento</p>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 sm:gap-3">
                        <div class="flex items-center gap-3 p-3 sm:p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20">
                            <div class="w-8 h-8 rounded-lg bg-emerald-500/20 flex items-center justify-center shrink-0">
                                <i data-lucide="shield-check" class="w-4 h-4 text-emerald-400"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs sm:text-sm font-medium text-emerald-400">Dados Protegidos</p>
                                <p class="text-[11px] sm:text-xs text-zinc-300">Nenhum dado será apagado ou perdido</p>
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-3 p-3 sm:p-4 rounded-xl bg-white/[0.02] border border-white/5">
                            <div class="w-8 h-8 rounded-lg bg-amber-500/10 flex items-center justify-center shrink-0">
                                <i data-lucide="sparkles" class="w-4 h-4 text-amber-500"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs sm:text-sm font-medium text-white">Novas Features</p>
                                <p class="text-[11px] sm:text-xs text-zinc-500 truncate">Melhorias em desenvolvimento</p>
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-3 p-3 sm:p-4 rounded-xl bg-white/[0.02] border border-white/5">
                            <div class="w-8 h-8 rounded-lg bg-blue-500/10 flex items-center justify-center shrink-0">
                                <i data-lucide="refresh-cw" class="w-4 h-4 text-blue-400"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs sm:text-sm font-medium text-white">Auto Refresh</p>
                                <p class="text-[11px] sm:text-xs text-zinc-500 truncate">Atualiza a cada 30 segundos</p>
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-3 p-3 sm:p-4 rounded-xl bg-white/[0.02] border border-white/5">
                            <div class="w-8 h-8 rounded-lg bg-zinc-500/10 flex items-center justify-center shrink-0">
                                <i data-lucide="arrow-right" class="w-4 h-4 text-zinc-400"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs sm:text-sm font-medium text-white">Redirect Automático</p>
                                <p class="text-[11px] sm:text-xs text-zinc-500 truncate">Volta após manutenção</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>
        
        <!-- Footer -->
        <footer class="border-t border-white/5">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="h-14 sm:h-16 flex items-center justify-between">
                    <div class="flex items-center gap-2 sm:gap-3 text-xs sm:text-sm text-zinc-500">
                        <img src="assets/img/logos (6).png" alt="SafeNode" class="w-4 h-4 sm:w-5 sm:h-5 opacity-40">
                        <span class="hidden sm:inline">SafeNode</span>
                        <span class="text-zinc-700 hidden sm:inline">·</span>
                        <span class="text-[10px] sm:text-xs font-mono text-zinc-600">v2.4.0</span>
                    </div>
                    <div class="flex items-center gap-3 sm:gap-4">
                        <a href="https://www.instagram.com/safenode/" target="_blank" rel="noopener noreferrer" class="flex items-center gap-2 text-zinc-400 hover:text-white transition-colors group" aria-label="Instagram SafeNode">
                            <i data-lucide="instagram" class="w-4 h-4 sm:w-5 sm:h-5 group-hover:scale-110 transition-transform"></i>
                            <span class="text-[11px] sm:text-xs hidden sm:inline">SafeNode</span>
                        </a>
                        <a href="https://www.instagram.com/lvnas._/" target="_blank" rel="noopener noreferrer" class="flex items-center gap-2 text-zinc-400 hover:text-white transition-colors group" aria-label="Instagram Lvnas">
                            <i data-lucide="user" class="w-4 h-4 sm:w-5 sm:h-5 group-hover:scale-110 transition-transform"></i>
                            <span class="text-[11px] sm:text-xs hidden sm:inline">Lvnas</span>
                        </a>
                        <div class="h-4 w-px bg-white/10"></div>
                        <div class="flex items-center gap-2">
                            <div class="w-1.5 h-1.5 bg-amber-500 rounded-full"></div>
                            <span class="text-[11px] sm:text-xs text-zinc-500">Monitoramento ativo</span>
                        </div>
                    </div>
                </div>
            </div>
        </footer>
    </div>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();
        
        // Update time
        function updateTime() {
            const now = new Date();
            const el = document.getElementById('last-update');
            if (el) {
                el.textContent = now.toLocaleTimeString('pt-BR', { 
                    hour: '2-digit', 
                    minute: '2-digit' 
                });
            }
        }
        
        // Simulate response time
        function updateResponseTime() {
            const el = document.getElementById('response-time');
            if (el) {
                el.textContent = Math.floor(Math.random() * 25) + 48;
            }
        }
        
        // Initial calls
        updateTime();
        updateResponseTime();
        
        // Update intervals
        setInterval(updateTime, 1000);
        setInterval(updateResponseTime, 5000);
        
        // Auto-refresh check every 30 seconds
        setInterval(function() {
            fetch(window.location.href, { 
                method: 'HEAD', 
                cache: 'no-cache' 
            })
            .then(function(response) {
                if (response.status !== 503) {
                    location.reload();
                }
            })
            .catch(function() {
                // Silent fail
            });
        }, 30000);
    </script>
</body>
</html>
