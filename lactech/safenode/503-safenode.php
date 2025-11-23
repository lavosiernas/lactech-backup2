<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manutenção do Sistema | SafeNode</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/img/logos (6).png">
    <link rel="shortcut icon" type="image/png" href="assets/img/logos (6).png">
    <link rel="apple-touch-icon" href="assets/img/logos (6).png">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        neutral: {
                            850: '#1f1f1f',
                            900: '#171717',
                            950: '#0a0a0a',
                        }
                    },
                    animation: {
                        'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                    }
                }
            }
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=JetBrains+Mono:wght@400;500&display=swap');

        :root {
            --bg-color: #050505;
            --text-color: #ffffff;
            --grid-color: rgba(255, 255, 255, 0.07);
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-color);
            font-family: 'Inter', sans-serif;
        }

        .font-mono {
            font-family: 'JetBrains Mono', monospace;
        }

        /* Subtle noise texture */
        .noise {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 5;
            opacity: 0.03;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noiseFilter'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.65' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noiseFilter)'/%3E%3C/svg%3E");
        }

        /* Scanline effect for security monitor feel */
        .scanline {
            width: 100%;
            height: 100px;
            z-index: 10;
            background: linear-gradient(0deg, rgba(0,0,0,0) 0%, rgba(255, 255, 255, 0.02) 50%, rgba(0,0,0,0) 100%);
            opacity: 0.05;
            position: absolute;
            bottom: 100%;
            animation: scanline 8s linear infinite;
            pointer-events: none;
        }

        @keyframes scanline {
            0% { bottom: 100%; }
            100% { bottom: -100%; }
        }

        .bg-grid {
            background-image: linear-gradient(var(--grid-color) 1px, transparent 1px),
                            linear-gradient(90deg, var(--grid-color) 1px, transparent 1px);
            background-size: 40px 40px;
            mask-image: radial-gradient(circle at center, black 40%, transparent 100%);
            -webkit-mask-image: radial-gradient(circle at center, black 40%, transparent 100%);
        }

        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0; }
        }
        .cursor-blink {
            animation: blink 1s step-end infinite;
        }
        
        .progress-bar-fill {
            transition: width 1s linear;
        }

        /* Mobile adjustments to prevent overflow on small screens */
        @media (max-width: 640px) {
            .scanline { display: none; } /* Remove expensive animation on mobile */
            h1 { font-size: 1.25rem; }
        }
    </style>
</head>
<body class="antialiased h-screen flex flex-col overflow-hidden bg-black selection:bg-white selection:text-black">
    
    <!-- Background effects -->
    <div class="fixed inset-0 bg-grid pointer-events-none z-0 opacity-40"></div>
    <div class="scanline pointer-events-none"></div>
    <div class="noise pointer-events-none"></div>

    <!-- Top Bar -->
    <header class="w-full border-b border-white/5 z-20 bg-black/40 backdrop-blur-md absolute top-0 left-0">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 h-16 sm:h-18 flex justify-between items-center">
            <div class="flex items-center gap-2 sm:gap-3">
                <!-- SafeNode Logo -->
                <img src="assets/img/logos (6).png" alt="SafeNode" class="w-7 h-7 sm:w-9 sm:h-9 object-contain shrink-0">
                <div class="flex flex-col">
                    <!-- Better text sizing for mobile -->
                    <span class="font-mono text-xs sm:text-base font-semibold tracking-wide text-white">SafeNode</span>
                    <span class="font-mono text-[7px] sm:text-[10px] text-neutral-500 tracking-widest">VER. 2.4.0-RC</span>
                </div>
            </div>
            <!-- Adjusted hidden classes to show more info on tablet, hiding on mobile -->
            <div class="flex items-center gap-6 hidden md:flex">
                <div class="text-right">
                    <p class="text-[10px] font-mono text-neutral-500 uppercase tracking-wider">Server Location</p>
                    <p class="text-xs font-mono text-neutral-300 flex items-center gap-2 justify-end">
                        <span class="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse"></span>
                        FORTALEZA, BR
                    </p>
                </div>
                 <div class="text-right border-l border-white/10 pl-6">
                    <p class="text-[10px] font-mono text-neutral-500 uppercase tracking-wider">Coordinates</p>
                    <p class="text-xs font-mono text-neutral-300">03°43'S / 38°32'W</p>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <!-- Added overflow-y-auto for mobile scrolling support if content is tall, padding adjustment -->
    <main class="flex-1 flex flex-col items-center justify-center relative z-10 px-3 sm:px-6 w-full overflow-y-auto sm:overflow-hidden">
        
        <div class="w-full max-w-3xl lg:max-w-4xl mx-auto py-8 sm:py-4 lg:py-6">
            
            <!-- Main Card -->
            <div class="relative group">
                <!-- Decorative borders -->
                <div class="absolute -inset-0.5 bg-gradient-to-b from-white/10 to-transparent rounded-lg opacity-50 blur-sm"></div>
                
                <!-- Reduced padding for mobile -->
                <div class="relative bg-black border border-white/10 rounded-lg p-4 sm:p-6 md:p-8 overflow-hidden">
                    
                    <!-- Card Header -->
                    <!-- Layout fix for mobile (flex-col) -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4 mb-4 sm:mb-6 border-b border-white/5 pb-3 sm:pb-4">
                        <div class="flex items-start gap-2 sm:gap-3">
                             <div class="p-2 sm:p-2.5 border border-white/10 rounded bg-white/5 shrink-0">
                                <span class="font-mono text-base sm:text-lg font-bold text-white">503</span>
                             </div>
                             <div>
                                 <!-- Font size adjustment -->
                                 <h1 class="text-sm sm:text-base md:text-lg font-medium text-white tracking-tight">Manutenção Programada</h1>
                                 <p class="text-xs sm:text-sm text-neutral-500 mt-0.5">Serviço temporariamente indisponível</p>
                             </div>
                        </div>
                        
                        <div class="flex flex-col sm:items-end gap-0.5 sm:gap-1">
                            <div class="flex items-center gap-2 text-[10px] sm:text-xs font-mono text-neutral-400">
                                <span>NODE_ID:</span>
                                <span class="text-white bg-white/10 px-1.5 py-0.5 rounded text-[10px] sm:text-xs">FOR-01A</span>
                            </div>
                            <div class="text-[9px] sm:text-[10px] font-mono text-neutral-600">
                                RAY_ID: 8a7b6c5d4e3f2g1h
                            </div>
                        </div>
                    </div>

                    <!-- Message Body -->
                    <!-- Spacing adjustment -->
                    <div class="space-y-3 sm:space-y-4 mb-4 sm:mb-6">
                        <!-- Text size adjustment -->
                        <p class="text-base sm:text-lg md:text-xl lg:text-2xl font-light text-neutral-200 leading-snug max-w-xl">
                            O sistema está passando por <span class="text-white font-normal border-b border-white/20 pb-0.5">manutenções críticas</span> de segurança.
                        </p>
                        <p class="text-xs sm:text-sm text-neutral-400 max-w-lg leading-relaxed">
                            Esta operação garante a integridade dos dados e a estabilidade da conexão em nossos servidores de Fortaleza. O acesso será restabelecido automaticamente.
                        </p>
                    </div>

                    <!-- Progress Section -->
                    <div class="bg-neutral-900/30 rounded border border-white/5 p-3 sm:p-4 relative overflow-hidden mb-4 sm:mb-6">
                        <!-- Grid inside progress container -->
                        <div class="absolute inset-0 bg-[url('https://grainy-gradients.vercel.app/noise.svg')] opacity-5 mix-blend-overlay"></div>
                        
                        <!-- Flex wrap to prevent overlapping on very small screens -->
                        <div class="flex justify-between items-end mb-2 sm:mb-2.5 relative z-10 flex-wrap gap-2">
                            <div class="flex flex-col gap-0.5 sm:gap-1">
                                <span class="text-[9px] sm:text-[10px] font-mono text-neutral-500 uppercase tracking-wider">Status da Operação</span>
                                <span class="text-[11px] sm:text-xs font-mono text-white flex items-center gap-2" id="status-text">
                                    <span class="block w-1.5 h-1.5 bg-white animate-pulse shrink-0"></span>
                                    <span class="truncate max-w-[200px] sm:max-w-none">VERIFYING_INTEGRITY...</span>
                                </span>
                            </div>
                            <span class="text-base sm:text-lg md:text-xl font-mono font-light text-white tracking-tighter" id="percentage">00.00%</span>
                        </div>

                        <!-- Bar -->
                        <div class="h-1 w-full bg-white/10 rounded-full overflow-hidden relative z-10">
                            <div id="progress-fill" class="h-full bg-white progress-bar-fill w-0 relative">
                                <div class="absolute right-0 top-0 bottom-0 w-3 sm:w-4 bg-white shadow-[0_0_15px_rgba(255,255,255,0.8)]"></div>
                            </div>
                        </div>

                        <!-- Terminal Output -->
                        <div class="mt-2.5 sm:mt-3 pt-2.5 sm:pt-3 border-t border-white/5 font-mono text-[9px] sm:text-[10px] text-neutral-500 space-y-1 h-10 sm:h-14 overflow-hidden relative z-10" id="terminal-logs">
                            <!-- Logs injected via JS -->
                        </div>
                    </div>

                    <!-- New Email Notification Section -->
                    <div class="flex items-start gap-2 sm:gap-3 bg-white/5 border border-white/10 rounded p-3 sm:p-3.5">
                        <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-neutral-400 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        <div class="flex flex-col gap-0.5">
                            <span class="text-[10px] sm:text-xs font-mono font-medium text-white uppercase tracking-wider">Notificação Automática</span>
                            <p class="text-[11px] sm:text-xs text-neutral-400 leading-relaxed">
                                Você receberá um e-mail no endereço cadastrado assim que o sistema estiver totalmente operacional. Não é necessário atualizar a página.
                            </p>
                        </div>
                    </div>
                    
                    <!-- Botão para voltar ao Index -->
                    <div class="mt-6 sm:mt-8 text-center">
                        <a href="index.php" class="inline-flex items-center gap-2 px-6 py-3 bg-white/10 hover:bg-white/20 border border-white/20 hover:border-white/30 rounded-lg text-white font-medium transition-all text-sm">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            Voltar para Página Inicial
                        </a>
                    </div>

                </div>
            </div>

            <!-- Bottom Info -->
            <!-- Added padding bottom for mobile scrolling -->
            <div class="mt-4 sm:mt-6 pb-6 sm:pb-0 flex flex-col sm:flex-row items-center justify-between gap-2 sm:gap-3 text-[9px] sm:text-[10px] font-mono text-neutral-600 uppercase tracking-wider opacity-60 hover:opacity-100 transition-opacity">
                <div class="flex items-center gap-1.5 sm:gap-2">
                    <svg class="w-2.5 h-2.5 sm:w-3 sm:h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                    <span>Auto-reload Enabled</span>
                </div>
                <div class="text-center sm:text-left">Encrypted Connection • TLS 1.3</div>
            </div>

        </div>
    </main>

    <script>
        // Configuration
        // 2 hours and 30 minutes in milliseconds
        const TOTAL_DURATION = (2 * 60 * 60 * 1000) + (30 * 60 * 1000); 
        const STORAGE_KEY = 'maintenance_start_time_v5';
        
        const logs = [
            "verifying_integrity_hash [OK]...",
            "optimizing_database_indexes...",
            "patching_security_nodes [FOR-01A]...",
            "clearing_edge_cache...",
            "re-calibrating_load_balancers...",
            "syncing_redundant_clusters...",
            "validating_ssl_certificates...",
            "updating_firewall_rules...",
            "running_consistency_checks...",
            "backup_incremental_snapshot...",
            "latency_check_fortaleza_hub...",
            "refreshing_dns_propagation..."
        ];

        function initMaintenanceTimer() {
            let startTime = localStorage.getItem(STORAGE_KEY);
            
            // If no start time or it's invalid, set a new one
            if (!startTime) {
                startTime = Date.now();
                localStorage.setItem(STORAGE_KEY, startTime);
            } else {
                startTime = parseInt(startTime);
            }

            const progressBar = document.getElementById('progress-fill');
            const percentageText = document.getElementById('percentage');
            const statusText = document.getElementById('status-text');
            const terminalContainer = document.getElementById('terminal-logs');

            function updateProgress() {
                const now = Date.now();
                const elapsed = now - startTime;
                let percentage = (elapsed / TOTAL_DURATION) * 100;

                if (percentage > 99.99) percentage = 99.99;
                if (percentage < 0) percentage = 0;

                // Update UI
                progressBar.style.width = `${percentage}%`;
                // Ensure formatting keeps fixed width look (00.00%)
                percentageText.textContent = `${percentage.toFixed(2).padStart(5, '0')}%`; 

                // Add realistic logs occasionally
                if (Math.random() < 0.03) { 
                    addLog();
                }
                
                // Update Status Text occasionally
                if (Math.random() < 0.01) {
                   const randomLog = logs[Math.floor(Math.random() * logs.length)];
                   // Clean log text for status (remove ...)
                   const cleanStatus = randomLog.split(' ')[0].toUpperCase().replace('...', '');
                   statusText.innerHTML = `<span class="block w-1.5 h-1.5 bg-white animate-pulse shrink-0"></span> <span class="truncate max-w-[200px] sm:max-w-none">${cleanStatus}...</span>`;
                }

                requestAnimationFrame(updateProgress);
            }

            function addLog() {
                const log = logs[Math.floor(Math.random() * logs.length)];
                const time = new Date().toLocaleTimeString('pt-BR', { hour12: false, hour: '2-digit', minute:'2-digit', second:'2-digit' });
                
                const p = document.createElement('div');
                const gapClass = window.innerWidth >= 640 ? 'gap-3' : 'gap-2';
                p.className = `flex ${gapClass} opacity-0 animate-[fadeIn_0.2s_ease-out_forwards]`;
                p.innerHTML = `<span class="text-neutral-600 shrink-0">[${time}]</span> <span class="text-neutral-400 truncate">> ${log}</span>`;
                
                // Keep only last 2 logs on mobile, 3 on tablet/desktop
                let maxLogs = window.innerWidth >= 640 ? 3 : 2;
                
                while (terminalContainer.children.length >= maxLogs) {
                    terminalContainer.removeChild(terminalContainer.firstChild);
                }
                
                terminalContainer.appendChild(p);
            }

            // Add fade in animation for logs
            const style = document.createElement('style');
            style.innerHTML = `
                @keyframes fadeIn {
                    from { opacity: 0; transform: translateY(5px); }
                    to { opacity: 1; transform: translateY(0); }
                }
            `;
            document.head.appendChild(style);

            // Start the loop
            updateProgress();
            addLog();
        }

        document.addEventListener('DOMContentLoaded', initMaintenanceTimer);
    </script>
</body>
</html>
