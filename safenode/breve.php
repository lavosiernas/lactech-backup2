<?php
/**
 * SafeNode - System Maintenance
 * Ultimate Edition - Clean, Interactive, Premium
 */
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SafeNode - Expansão de Infraestrutura</title>
    
    <link rel="icon" type="image/png" href="assets/img/logos (6).png">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <!-- Using Inter Tight for a punchier, modern look -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Inter+Tight:wght@500;600;700&display=swap" rel="stylesheet">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        display: ['Inter Tight', 'sans-serif'],
                    },
                    colors: {
                        bg: "#020202", // Not pure black, richer
                        surface: "#0A0A0A",
                        border: "#171717",
                    },
                    backgroundImage: {
                        'gradient-text': 'linear-gradient(to right, #FFFFFF, #A3A3A3)',
                    }
                }
            }
        }
    </script>
    <style>
        body {
            background-color: #020202;
            color: #ffffff;
            -webkit-font-smoothing: antialiased;
        }
        
        /* Subtle Spotlight Effect */
        .spotlight {
            background: radial-gradient(
                800px circle at var(--x) var(--y),
                rgba(255, 255, 255, 0.03),
                transparent 40%
            );
        }

        .text-gradient {
            background: linear-gradient(to bottom right, #ffffff 40%, #666666);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .input-group:focus-within {
            border-color: #404040;
            box-shadow: 0 0 0 4px rgba(255, 255, 255, 0.05);
        }
    </style>
</head>
<body class="min-h-screen flex flex-col relative overflow-hidden" onmousemove="document.querySelector('.spotlight').style.setProperty('--x', event.clientX + 'px'); document.querySelector('.spotlight').style.setProperty('--y', event.clientY + 'px');">

    <!-- Spotlight Overlay -->
    <div class="spotlight pointer-events-none fixed inset-0 z-0 transition-opacity duration-300"></div>

    <!-- Header -->
    <header class="relative z-10 p-8 md:p-12 flex justify-between items-center">
        <div class="flex items-center gap-3 select-none">
            <div class="relative w-8 h-8">
                 <img src="assets/img/logos (6).png" alt="SafeNode" class="w-full h-full object-contain filter brightness-125">
            </div>
            <span class="font-display font-semibold text-lg tracking-tight text-white/90">SafeNode</span>
        </div>
        
        <div class="hidden md:flex items-center gap-2 text-xs font-medium text-emerald-500 bg-emerald-500/5 px-3 py-1.5 rounded-full border border-emerald-500/10">
            <span class="relative flex h-2 w-2">
              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-500 opacity-75"></span>
              <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
            </span>
            <span>Todos os sistemas operacionais</span>
        </div>
    </header>

    <!-- Main Content -->
    <main class="relative z-10 flex-1 flex flex-col justify-center px-8 md:px-12 max-w-7xl mx-auto w-full -mt-20">
        
        <div class="grid md:grid-cols-2 gap-16 items-center">
            
            <!-- Left: Message -->
            <div class="space-y-8">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/5 border border-white/10 w-fit">
                    <i data-lucide="zap" class="w-3.5 h-3.5 text-yellow-500 fill-yellow-500"></i>
                    <span class="text-xs font-medium text-neutral-300">Upgrade v2.4 em andamento</span>
                </div>

                <h1 class="font-display text-5xl md:text-7xl font-bold tracking-tight leading-[1.05]">
                    <span class="text-gradient">Expandindo os limites</span><br>
                    <span class="text-neutral-500">da nossa proteção.</span>
                </h1>
                
                <p class="text-lg text-neutral-400 font-light leading-relaxed max-w-lg">
                    Estamos atualizando nossa arquitetura de servidores para entregar <strong>3x mais velocidade</strong> e proteção WAF aprimorada.
                </p>

                <!-- Functional Email Form -->
                <div class="max-w-md pt-4">
                    <label class="block text-xs font-medium text-neutral-500 mb-2 uppercase tracking-wide">Seja notificado ao retornar</label>
                    <form id="notifyForm" class="flex gap-2">
                        <div class="input-group flex-1 bg-surface border border-border rounded-lg transition-all duration-200 flex items-center px-4 py-3 group focus-within:border-emerald-500/50">
                            <i data-lucide="mail" class="w-4 h-4 text-neutral-500 mr-3 group-focus-within:text-emerald-500 transition-colors"></i>
                            <input type="email" id="emailInput" required placeholder="seu@email.com" class="bg-transparent border-none outline-none text-sm w-full placeholder-neutral-600 text-white">
                        </div>
                        <button type="submit" id="submitBtn" class="bg-white text-black px-6 py-3 rounded-lg font-medium text-sm hover:bg-neutral-200 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                            Ativar alerta
                        </button>
                    </form>
                    <p id="formStatus" class="text-xs text-neutral-600 mt-3 h-4">Acesso liberado automaticamente para clientes Enterprise.</p>
                </div>
            </div>

            <!-- Right: Visual Status Grid -->
            <div class="hidden md:grid grid-cols-1 gap-4 select-none">
                <!-- Card 1 -->
                <div class="bg-surface/50 border border-border p-6 rounded-2xl backdrop-blur-sm">
                    <div class="flex justify-between items-start mb-4">
                        <div class="p-2 bg-white/5 rounded-lg border border-white/5">
                            <i data-lucide="shield-check" class="w-5 h-5 text-white"></i>
                        </div>
                        <i data-lucide="activity" class="w-4 h-4 text-emerald-500"></i>
                    </div>
                    <h3 class="text-sm font-medium text-neutral-200">Proteção Ativa</h3>
                    <p class="text-xs text-neutral-500 mt-1">Nenhum incidente detectado.</p>
                </div>

                <!-- Card 2 -->
                <div class="bg-surface/50 border border-border p-6 rounded-2xl backdrop-blur-sm">
                    <div class="flex justify-between items-start mb-4">
                        <div class="p-2 bg-white/5 rounded-lg border border-white/5">
                            <i data-lucide="server" class="w-5 h-5 text-white"></i>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 bg-yellow-500 rounded-full animate-pulse"></span>
                            <span class="text-xs text-yellow-500 font-medium">Otimizando</span>
                        </div>
                    </div>
                    <h3 class="text-sm font-medium text-neutral-200">Latência Global</h3>
                    <p class="text-xs text-neutral-500 mt-1">Reduzindo tempo de resposta...</p>
                </div>

                <!-- Card 3 -->
                <div class="bg-surface/50 border border-border p-6 rounded-2xl backdrop-blur-sm opacity-50">
                    <div class="flex justify-between items-start mb-4">
                        <div class="p-2 bg-white/5 rounded-lg border border-white/5">
                            <i data-lucide="lock" class="w-5 h-5 text-white"></i>
                        </div>
                    </div>
                    <h3 class="text-sm font-medium text-neutral-200">Dados Criptografados</h3>
                    <p class="text-xs text-neutral-500 mt-1">Segurança em repouso mantida.</p>
                </div>
            </div>
        </div>

    </main>
    
    <!-- Footer -->
    <footer class="relative z-10 px-8 md:px-12 py-8 flex justify-between items-center text-xs text-neutral-600 border-t border-white/5">
        <div class="flex gap-4">
            <span>&copy; <?php echo date('Y'); ?> SafeNode Inc.</span>
            <span class="w-px h-4 bg-neutral-800"></span>
            <span>London • Fortaleza • São Paulo • New York • Santiago</span>
        </div>
        <div class="flex gap-4">
            <a href="#" class="hover:text-neutral-400 transition-colors">Privacy</a>
            <a href="#" class="hover:text-neutral-400 transition-colors">Terms</a>
        </div>
    </footer>

    <script>
        lucide.createIcons();

        // Handle Form Submission
        const form = document.getElementById('notifyForm');
        const emailInput = document.getElementById('emailInput');
        const submitBtn = document.getElementById('submitBtn');
        const statusMsg = document.getElementById('formStatus');

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const email = emailInput.value;
            
            // Loading State
            submitBtn.disabled = true;
            submitBtn.textContent = '...';
            statusMsg.textContent = 'Registrando...';
            statusMsg.className = 'text-xs text-neutral-500 mt-3 h-4 animate-pulse';

            try {
                const response = await fetch('notify.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    statusMsg.textContent = data.message;
                    statusMsg.className = 'text-xs text-emerald-500 mt-3 h-4 font-medium';
                    submitBtn.textContent = 'Registrado';
                    submitBtn.classList.add('bg-emerald-500', 'text-white', 'border-none');
                    emailInput.value = '';
                } else {
                    throw new Error(data.message);
                }
            } catch (err) {
                statusMsg.textContent = err.message || 'Erro ao registrar. Tente novamente.';
                statusMsg.className = 'text-xs text-red-500 mt-3 h-4';
                submitBtn.disabled = false;
                submitBtn.textContent = 'Ativar alerta';
            }
        });
    </script>
</body>
</html>
