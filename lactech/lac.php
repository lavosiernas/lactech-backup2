<!DOCTYPE html>
<html lang="pt-BR" class="scroll-smooth antialiased">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lactech - O Sistema Operacional da Pecuária</title>
    <meta name="description" content="Gestão de fazendas leiteiras com inteligência artificial.">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100;200;300;400;500;600&family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Alpine.js for Interactions -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- GSAP for High-End Animations -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>

    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        display: ['Space Grotesk', 'sans-serif'],
                    },
                    colors: {
                        border: "rgba(255, 255, 255, 0.08)",
                        background: "#030303",
                        surface: "#0A0A0A",
                        brand: {
                            400: '#4ade80',
                            500: '#22c55e',
                            600: '#16a34a',
                        }
                    },
                    backgroundImage: {
                        'gradient-radial': 'radial-gradient(var(--tw-gradient-stops))',
                        'hero-glow': 'conic-gradient(from 180deg at 50% 50%, #16a34a 0deg, #0ea5e9 180deg, #16a34a 360deg)',
                        'mesh': 'radial-gradient(at 0% 0%, rgba(34, 197, 94, 0.15) 0px, transparent 50%), radial-gradient(at 100% 100%, rgba(14, 165, 233, 0.15) 0px, transparent 50%)',
                    },
                    animation: {
                        'marquee': 'marquee 25s linear infinite',
                        'float': 'float 6s ease-in-out infinite',
                        'pulse-glow': 'pulse-glow 4s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                    },
                    keyframes: {
                        marquee: {
                            '0%': { transform: 'translateX(0%)' },
                            '100%': { transform: 'translateX(-100%)' },
                        },
                        float: {
                            '0%, 100%': { transform: 'translateY(0)' },
                            '50%': { transform: 'translateY(-20px)' },
                        },
                        'pulse-glow': {
                            '0%, 100%': { opacity: '0.5', transform: 'scale(1)' },
                            '50%': { opacity: '1', transform: 'scale(1.05)' },
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body {
            background-color: #030303;
            color: #e5e5e5;
            overflow-x: hidden;
        }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #030303;
        }
        ::-webkit-scrollbar-thumb {
            background: #333;
            border-radius: 3px;
        }
        
        /* Glass Utilities */
        .glass {
            background: rgba(255, 255, 255, 0.02);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.06);
        }
        .glass-card {
            background: linear-gradient(180deg, rgba(255,255,255,0.03) 0%, rgba(255,255,255,0.01) 100%);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.06);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
        }
        
        /* Typography Utilities */
        .text-balance {
            text-wrap: balance;
        }
        
        /* Grid Background */
        .bg-grid {
            background-size: 50px 50px;
            background-image: linear-gradient(to right, rgba(255, 255, 255, 0.03) 1px, transparent 1px),
                              linear-gradient(to bottom, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
        }

        /* Utilities */
        .mask-gradient-x {
            mask-image: linear-gradient(to right, transparent, black 20%, black 80%, transparent);
            -webkit-mask-image: linear-gradient(to right, transparent, black 20%, black 80%, transparent);
        }

        .clip-path-slant {
            clip-path: polygon(0 0, 100% 0, 100% 85%, 0 100%);
        }
    </style>
</head>
<body class="bg-background text-white selection:bg-green-500/30 selection:text-green-200">

    <!-- Decorative Background Elements -->
    <div class="fixed inset-0 z-0 pointer-events-none">
        <div class="absolute top-[-20%] left-[-10%] w-[50%] h-[50%] bg-green-500/10 rounded-full blur-[120px] mix-blend-screen"></div>
        <div class="absolute bottom-[-20%] right-[-10%] w-[50%] h-[50%] bg-blue-600/10 rounded-full blur-[120px] mix-blend-screen"></div>
        <div class="absolute inset-0 bg-grid opacity-20"></div>
    </div>

    <!-- Navigation -->
    <nav class="fixed w-full z-50 border-b border-white/5 glass transition-all duration-300" id="navbar">
        <div class="max-w-7xl mx-auto px-6 h-20 flex items-center justify-between">
            <div class="flex items-center gap-3 cursor-pointer group">
                <div class="relative w-8 h-8 flex items-center justify-center overflow-hidden rounded-lg bg-gradient-to-br from-green-400 to-green-600">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-black rotate-45 group-hover:rotate-0 transition-transform duration-500"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/></svg>
                </div>
                <span class="text-lg font-display font-bold tracking-tight text-white">Lactech<span class="text-green-500">.</span></span>
            </div>
            
            <div class="hidden md:flex items-center gap-8">
                <a href="#features" class="text-sm font-medium text-zinc-400 hover:text-white transition-colors">Recursos</a>
                <a href="#analytics" class="text-sm font-medium text-zinc-400 hover:text-white transition-colors">Analytics</a>
                <a href="#pricing" class="text-sm font-medium text-zinc-400 hover:text-white transition-colors">Planos</a>
                <a href="#faq" class="text-sm font-medium text-zinc-400 hover:text-white transition-colors">Dúvidas</a>
            </div>

            <div class="flex items-center gap-4">
                <a href="login.php" class="text-sm font-medium text-zinc-300 hover:text-white transition-colors hidden sm:block">Entrar</a>
                <a href="login.php" class="group relative px-6 py-2.5 bg-white text-black rounded-full text-sm font-semibold hover:bg-zinc-200 transition-all duration-300 overflow-hidden">
                    <span class="relative z-10 flex items-center gap-2">
                        Começar Agora 
                        <i data-lucide="arrow-right" class="w-3.5 h-3.5 stroke-[2.5px] group-hover:translate-x-1 transition-transform"></i>
                    </span>
                    <div class="absolute inset-0 bg-gradient-to-r from-zinc-100 to-white opacity-0 group-hover:opacity-100 transition-opacity"></div>
                </a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="relative pt-32 pb-20 lg:pt-48 lg:pb-32 overflow-hidden min-h-screen flex flex-col justify-center">
        <div class="relative z-10 max-w-7xl mx-auto px-6 text-center">
            
            <!-- Label -->
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full border border-white/10 bg-white/5 backdrop-blur-sm mb-8 opacity-0 animate-fade-in-up" style="animation-delay: 0.1s;">
                <span class="flex h-2 w-2 relative">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                </span>
                <span class="text-xs font-medium text-green-400 tracking-wide uppercase">Nova Versão 3.0</span>
            </div>

            <!-- Heading -->
            <h1 class="text-5xl md:text-7xl lg:text-8xl font-bold tracking-tighter leading-[1.1] mb-8 font-display opacity-0 animate-fade-in-up" style="animation-delay: 0.2s;">
                A inteligência <br>
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-white via-white to-zinc-500">da sua produção.</span>
            </h1>

            <!-- Subtitle -->
            <p class="text-lg md:text-xl text-zinc-400 max-w-2xl mx-auto mb-12 leading-relaxed opacity-0 animate-fade-in-up text-balance font-light" style="animation-delay: 0.3s;">
                Uma plataforma unificada que transforma dados brutos em decisões lucrativas. 
                Monitore saúde, reprodução e finanças com precisão cirúrgica.
            </p>

            <!-- Buttons -->
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4 opacity-0 animate-fade-in-up" style="animation-delay: 0.4s;">
                <button class="w-full sm:w-auto px-8 py-4 bg-[#111] hover:bg-[#1a1a1a] text-white border border-white/10 rounded-full font-medium transition-all duration-300 flex items-center justify-center gap-2 group">
                    <i data-lucide="play-circle" class="w-5 h-5 stroke-[1.5] group-hover:text-green-400 transition-colors"></i>
                    Ver demonstração (2m)
                </button>
                <button class="w-full sm:w-auto px-8 py-4 bg-white text-black rounded-full font-semibold hover:scale-105 transition-transform duration-300 shadow-[0_0_50px_-15px_rgba(255,255,255,0.3)]">
                    Criar conta gratuita
                </button>
            </div>

            <!-- Hero Image / Parallax Container -->
            <div class="mt-24 relative max-w-6xl mx-auto perspective-1000 opacity-0 animate-fade-in-up" style="animation-delay: 0.6s;">
                
                <!-- Glow behind -->
                <div class="absolute inset-0 bg-gradient-to-t from-green-500/20 via-blue-500/10 to-transparent blur-3xl -z-10 opacity-50"></div>

                <!-- Main Interface Mockup -->
                <div class="relative rounded-xl border border-white/10 bg-[#050505]/80 backdrop-blur-xl shadow-2xl overflow-hidden transform rotate-x-12 transition-transform duration-700 hover:rotate-x-0 group">
                    
                    <!-- Fake Browser Header -->
                    <div class="h-10 border-b border-white/5 flex items-center px-4 gap-2 bg-white/5">
                        <div class="flex gap-1.5">
                            <div class="w-3 h-3 rounded-full bg-red-500/20 border border-red-500/50"></div>
                            <div class="w-3 h-3 rounded-full bg-yellow-500/20 border border-yellow-500/50"></div>
                            <div class="w-3 h-3 rounded-full bg-green-500/20 border border-green-500/50"></div>
                        </div>
                        <div class="ml-4 flex-1 max-w-sm h-5 bg-white/5 rounded text-[10px] flex items-center px-3 text-zinc-600 font-mono">lactech.app/dashboard</div>
                    </div>

                    <!-- Dashboard Content -->
                    <div class="p-6 grid grid-cols-12 gap-6 text-left h-[600px] overflow-hidden relative">
                        
                        <!-- Sidebar -->
                        <div class="col-span-2 border-r border-white/5 hidden md:block pr-4">
                            <div class="space-y-6">
                                <div class="space-y-1">
                                    <div class="h-8 w-full bg-green-500/10 rounded flex items-center px-3 text-green-400 text-xs font-medium"><i data-lucide="layout-grid" class="w-4 h-4 mr-2 stroke-1"></i> Visão Geral</div>
                                    <div class="h-8 w-full hover:bg-white/5 rounded flex items-center px-3 text-zinc-500 text-xs transition-colors"><i data-lucide="cow" class="w-4 h-4 mr-2 stroke-1"></i> Rebanho</div>
                                    <div class="h-8 w-full hover:bg-white/5 rounded flex items-center px-3 text-zinc-500 text-xs transition-colors"><i data-lucide="milk" class="w-4 h-4 mr-2 stroke-1"></i> Produção</div>
                                    <div class="h-8 w-full hover:bg-white/5 rounded flex items-center px-3 text-zinc-500 text-xs transition-colors"><i data-lucide="stethoscope" class="w-4 h-4 mr-2 stroke-1"></i> Saúde</div>
                                </div>
                                <div>
                                    <div class="text-[10px] uppercase tracking-wider text-zinc-700 font-bold mb-2 px-3">Finanças</div>
                                    <div class="h-8 w-full hover:bg-white/5 rounded flex items-center px-3 text-zinc-500 text-xs transition-colors"><i data-lucide="dollar-sign" class="w-4 h-4 mr-2 stroke-1"></i> Fluxo de Caixa</div>
                                </div>
                            </div>
                        </div>

                        <!-- Main Content -->
                        <div class="col-span-12 md:col-span-10 space-y-6">
                            <!-- Header -->
                            <div class="flex justify-between items-end">
                                <div>
                                    <div class="text-xs text-zinc-500 mb-1">Terça, 24 Outubro</div>
                                    <div class="text-2xl font-display font-semibold">Fazenda Santa Rita</div>
                                </div>
                                <div class="flex gap-2">
                                    <div class="px-3 py-1.5 rounded border border-white/10 bg-white/5 text-xs text-zinc-400 flex items-center gap-2"><div class="w-2 h-2 rounded-full bg-green-500"></div> Online</div>
                                </div>
                            </div>

                            <!-- Stats Grid -->
                            <div class="grid grid-cols-3 gap-4">
                                <div class="p-4 rounded-lg border border-white/5 bg-white/[0.02]">
                                    <div class="text-xs text-zinc-500 mb-2 flex items-center gap-2"><i data-lucide="milk" class="w-3 h-3 stroke-1"></i> Produção Hoje</div>
                                    <div class="text-2xl font-display font-medium">2.450 L</div>
                                    <div class="text-[10px] text-green-500 mt-1 flex items-center gap-1"><i data-lucide="trending-up" class="w-3 h-3"></i> +4.5% vs ontem</div>
                                </div>
                                <div class="p-4 rounded-lg border border-white/5 bg-white/[0.02]">
                                    <div class="text-xs text-zinc-500 mb-2 flex items-center gap-2"><i data-lucide="activity" class="w-3 h-3 stroke-1"></i> CCS Média</div>
                                    <div class="text-2xl font-display font-medium text-green-400">180k</div>
                                    <div class="text-[10px] text-zinc-500 mt-1">Excelente qualidade</div>
                                </div>
                                <div class="p-4 rounded-lg border border-white/5 bg-white/[0.02]">
                                    <div class="text-xs text-zinc-500 mb-2 flex items-center gap-2"><i data-lucide="alert-circle" class="w-3 h-3 stroke-1"></i> Alertas</div>
                                    <div class="text-2xl font-display font-medium text-orange-400">3</div>
                                    <div class="text-[10px] text-zinc-500 mt-1">Vacinas pendentes</div>
                                </div>
                            </div>

                            <!-- Chart Area (Visual only) -->
                            <div class="h-64 rounded-lg border border-white/5 bg-white/[0.02] p-4 relative overflow-hidden">
                                <div class="flex justify-between mb-4">
                                    <div class="text-xs text-zinc-500">Curva de Lactação (Últimos 30 dias)</div>
                                </div>
                                <!-- SVG Chart Line -->
                                <svg class="w-full h-40 overflow-visible" preserveAspectRatio="none">
                                    <path d="M0,100 Q100,80 200,60 T400,40 T600,50 T800,30 T1000,40" fill="none" stroke="#22c55e" stroke-width="2" class="drop-shadow-[0_0_10px_rgba(34,197,94,0.5)]" />
                                    <defs>
                                        <linearGradient id="gradient" x1="0" x2="0" y1="0" y2="1">
                                            <stop offset="0%" stop-color="rgba(34, 197, 94, 0.2)" />
                                            <stop offset="100%" stop-color="transparent" />
                                        </linearGradient>
                                    </defs>
                                    <path d="M0,100 Q100,80 200,60 T400,40 T600,50 T800,30 T1000,40 V150 H0 Z" fill="url(#gradient)" opacity="0.5" />
                                </svg>
                            </div>

                        </div>
                    </div>
                    
                    <!-- Overlay Gradient for depth -->
                    <div class="absolute inset-0 bg-gradient-to-t from-[#050505] via-transparent to-transparent pointer-events-none h-full w-full z-20"></div>
                </div>
            </div>
        </div>
    </section>

    <!-- Trusted By / Marquee -->
    <section class="py-10 border-y border-white/5 bg-surface/50 overflow-hidden">
        <div class="flex w-full mask-gradient-x">
            <div class="flex animate-marquee whitespace-nowrap gap-16 md:gap-32 items-center">
                <!-- Duplicated content for infinite scroll -->
                <div class="flex gap-16 md:gap-32 items-center opacity-40 grayscale hover:grayscale-0 transition-all duration-500">
                    <span class="text-xl font-display font-bold flex items-center gap-2"><i data-lucide="droplet" class="stroke-1"></i> AgroLeite</span>
                    <span class="text-xl font-display font-bold flex items-center gap-2"><i data-lucide="sun" class="stroke-1"></i> Fazenda Sol</span>
                    <span class="text-xl font-display font-bold flex items-center gap-2"><i data-lucide="mountain" class="stroke-1"></i> Terra Viva</span>
                    <span class="text-xl font-display font-bold flex items-center gap-2"><i data-lucide="wheat" class="stroke-1"></i> Grão de Ouro</span>
                    <span class="text-xl font-display font-bold flex items-center gap-2"><i data-lucide="sprout" class="stroke-1"></i> EcoFarm</span>
                </div>
                 <div class="flex gap-16 md:gap-32 items-center opacity-40 grayscale hover:grayscale-0 transition-all duration-500">
                    <span class="text-xl font-display font-bold flex items-center gap-2"><i data-lucide="droplet" class="stroke-1"></i> AgroLeite</span>
                    <span class="text-xl font-display font-bold flex items-center gap-2"><i data-lucide="sun" class="stroke-1"></i> Fazenda Sol</span>
                    <span class="text-xl font-display font-bold flex items-center gap-2"><i data-lucide="mountain" class="stroke-1"></i> Terra Viva</span>
                    <span class="text-xl font-display font-bold flex items-center gap-2"><i data-lucide="wheat" class="stroke-1"></i> Grão de Ouro</span>
                    <span class="text-xl font-display font-bold flex items-center gap-2"><i data-lucide="sprout" class="stroke-1"></i> EcoFarm</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Grid (Value Prop) -->
    <section id="features" class="py-32 relative z-10">
        <div class="max-w-7xl mx-auto px-6">
            <div class="grid md:grid-cols-3 gap-12">
                <!-- Feature 1 -->
                <div class="group">
                    <div class="w-12 h-12 rounded-full border border-white/10 bg-white/5 flex items-center justify-center mb-6 group-hover:bg-green-500/10 group-hover:border-green-500/50 transition-colors duration-300">
                        <i data-lucide="zap" class="w-6 h-6 text-zinc-400 group-hover:text-green-400 transition-colors stroke-1"></i>
                    </div>
                    <h3 class="text-xl font-display font-semibold mb-3">Automated Insights</h3>
                    <p class="text-zinc-400 text-sm leading-relaxed">
                        Nossa IA analisa padrões de produção e saúde para sugerir correções antes que os problemas aconteçam.
                    </p>
                </div>
                <!-- Feature 2 -->
                <div class="group">
                    <div class="w-12 h-12 rounded-full border border-white/10 bg-white/5 flex items-center justify-center mb-6 group-hover:bg-blue-500/10 group-hover:border-blue-500/50 transition-colors duration-300">
                        <i data-lucide="layers" class="w-6 h-6 text-zinc-400 group-hover:text-blue-400 transition-colors stroke-1"></i>
                    </div>
                    <h3 class="text-xl font-display font-semibold mb-3">Controle Total</h3>
                    <p class="text-zinc-400 text-sm leading-relaxed">
                        Do nascimento à ordenha, cada animal tem um histórico digital completo, acessível em qualquer dispositivo.
                    </p>
                </div>
                <!-- Feature 3 -->
                <div class="group">
                    <div class="w-12 h-12 rounded-full border border-white/10 bg-white/5 flex items-center justify-center mb-6 group-hover:bg-purple-500/10 group-hover:border-purple-500/50 transition-colors duration-300">
                        <i data-lucide="lock" class="w-6 h-6 text-zinc-400 group-hover:text-purple-400 transition-colors stroke-1"></i>
                    </div>
                    <h3 class="text-xl font-display font-semibold mb-3">Segurança Bancária</h3>
                    <p class="text-zinc-400 text-sm leading-relaxed">
                        Seus dados são criptografados de ponta a ponta. Backups automáticos garantem que você nunca perca nada.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Deep Dive Section 1: Analytics (Zig) -->
    <section id="analytics" class="py-32 relative overflow-hidden">
        <div class="max-w-7xl mx-auto px-6 relative z-10">
            <div class="flex flex-col lg:flex-row items-center gap-20">
                <!-- Text Content -->
                <div class="lg:w-1/2">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full border border-green-500/20 bg-green-500/5 text-green-400 text-xs font-medium mb-6">
                        ANÁLISE PREDITIVA
                    </div>
                    <h2 class="text-4xl md:text-5xl font-display font-bold mb-6 leading-tight">
                        Preveja a produção.<br>
                        <span class="text-zinc-500">Não apenas reaja.</span>
                    </h2>
                    <p class="text-zinc-400 text-lg mb-8 leading-relaxed">
                        O Lactech utiliza algoritmos de aprendizado de máquina para projetar sua produção de leite futura com base em dados históricos, clima e nutrição.
                    </p>
                    
                    <ul class="space-y-4 mb-10">
                        <li class="flex items-center gap-3 text-zinc-300">
                            <div class="w-5 h-5 rounded-full bg-green-500/20 flex items-center justify-center text-green-500"><i data-lucide="check" class="w-3 h-3 stroke-[3]"></i></div>
                            Curvas de lactação ajustadas por animal
                        </li>
                        <li class="flex items-center gap-3 text-zinc-300">
                            <div class="w-5 h-5 rounded-full bg-green-500/20 flex items-center justify-center text-green-500"><i data-lucide="check" class="w-3 h-3 stroke-[3]"></i></div>
                            Detecção precoce de queda de produtividade
                        </li>
                        <li class="flex items-center gap-3 text-zinc-300">
                            <div class="w-5 h-5 rounded-full bg-green-500/20 flex items-center justify-center text-green-500"><i data-lucide="check" class="w-3 h-3 stroke-[3]"></i></div>
                            Correlação automática com dieta
                        </li>
                    </ul>

                    <a href="#" class="text-white border-b border-white/30 pb-1 hover:border-white transition-colors inline-flex items-center gap-2">
                        Explorar Analytics <i data-lucide="arrow-right" class="w-4 h-4"></i>
                    </a>
                </div>

                <!-- Visual Content (Parallax) -->
                <div class="lg:w-1/2 w-full perspective-1000">
                    <div class="relative glass-card p-8 rounded-2xl transform hover:rotate-y-[-5deg] transition-transform duration-500">
                        <div class="absolute -top-10 -right-10 w-40 h-40 bg-green-500/20 blur-3xl rounded-full"></div>
                        
                        <!-- Graph Component -->
                        <div class="space-y-6">
                            <div class="flex justify-between items-center">
                                <h4 class="font-medium text-sm text-zinc-400">Projeção Trimestral</h4>
                                <div class="bg-white/5 border border-white/5 rounded-md px-2 py-1 text-[10px] text-zinc-400">Exportar</div>
                            </div>
                            
                            <!-- Custom CSS Bars -->
                            <div class="flex items-end justify-between h-48 gap-2">
                                <div class="w-full bg-white/5 rounded-t hover:bg-green-500/50 transition-all duration-500 h-[40%] relative group"><div class="opacity-0 group-hover:opacity-100 absolute -top-8 left-1/2 -translate-x-1/2 bg-black text-[10px] px-2 py-1 rounded border border-white/10">1.2k</div></div>
                                <div class="w-full bg-white/5 rounded-t hover:bg-green-500/50 transition-all duration-500 h-[60%]"></div>
                                <div class="w-full bg-white/5 rounded-t hover:bg-green-500/50 transition-all duration-500 h-[50%]"></div>
                                <div class="w-full bg-green-500 rounded-t shadow-[0_0_20px_rgba(34,197,94,0.3)] h-[75%] relative">
                                    <div class="absolute top-2 right-2 w-2 h-2 bg-white rounded-full animate-pulse"></div>
                                </div>
                                <div class="w-full bg-white/5 rounded-t border border-dashed border-white/20 bg-transparent h-[85%]"></div>
                                <div class="w-full bg-white/5 rounded-t border border-dashed border-white/20 bg-transparent h-[90%]"></div>
                            </div>
                            
                            <div class="flex justify-between text-[10px] text-zinc-600 font-mono uppercase">
                                <span>Jan</span><span>Fev</span><span>Mar</span><span>Abr</span><span>Mai (Prev)</span><span>Jun (Prev)</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Deep Dive Section 2: Health (Zag) -->
    <section class="py-32 bg-surface/30 border-y border-white/5 relative overflow-hidden">
        <div class="max-w-7xl mx-auto px-6 relative z-10">
            <div class="flex flex-col-reverse lg:flex-row items-center gap-20">
                
                <!-- Visual Content (SVG Composition) -->
                <div class="lg:w-1/2 w-full relative">
                    <!-- Center Circle Pulse -->
                    <div class="relative flex items-center justify-center w-full aspect-square max-w-md mx-auto">
                        <div class="absolute inset-0 bg-blue-500/10 blur-3xl rounded-full animate-pulse-glow"></div>
                        
                        <!-- Orbital Rings -->
                        <div class="absolute w-full h-full border border-white/5 rounded-full animate-[spin_20s_linear_infinite]"></div>
                        <div class="absolute w-[70%] h-[70%] border border-dashed border-white/10 rounded-full animate-[spin_15s_linear_infinite_reverse]"></div>
                        
                        <!-- Center Cow Icon/Card -->
                        <div class="glass-card rounded-full w-32 h-32 flex items-center justify-center relative z-10 shadow-[0_0_50px_rgba(59,130,246,0.2)]">
                            <i data-lucide="heart-pulse" class="w-12 h-12 text-blue-400 stroke-1"></i>
                        </div>

                        <!-- Floating Cards -->
                        <div class="absolute top-10 right-0 glass-card px-4 py-2 rounded-lg flex items-center gap-3 animate-float" style="animation-delay: 0s">
                            <div class="w-2 h-2 bg-red-500 rounded-full animate-pulse"></div>
                            <div class="text-xs">Vaca 104: Febre detectada</div>
                        </div>
                        <div class="absolute bottom-20 left-0 glass-card px-4 py-2 rounded-lg flex items-center gap-3 animate-float" style="animation-delay: 1s">
                            <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                            <div class="text-xs">Lote A: Vacinação OK</div>
                        </div>
                    </div>
                </div>

                <!-- Text Content -->
                <div class="lg:w-1/2">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full border border-blue-500/20 bg-blue-500/5 text-blue-400 text-xs font-medium mb-6">
                        SAÚDE E BEM-ESTAR
                    </div>
                    <h2 class="text-4xl md:text-5xl font-display font-bold mb-6 leading-tight">
                        Monitoramento 24/7.<br>
                        <span class="text-zinc-500">Veterinário digital.</span>
                    </h2>
                    <p class="text-zinc-400 text-lg mb-8 leading-relaxed">
                        Integramos com colares e brincos eletrônicos para monitorar ruminação, temperatura e atividade. O sistema alerta você instantaneamente sobre anomalias.
                    </p>
                    <div class="grid grid-cols-2 gap-6">
                        <div class="border-l border-white/10 pl-4">
                            <div class="text-3xl font-bold font-display text-white mb-1">-15%</div>
                            <div class="text-sm text-zinc-500">Custo veterinário</div>
                        </div>
                        <div class="border-l border-white/10 pl-4">
                            <div class="text-3xl font-bold font-display text-white mb-1">+8%</div>
                            <div class="text-sm text-zinc-500">Taxa de prenhez</div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- Bento Grid Detailed -->
    <section class="py-32">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center max-w-3xl mx-auto mb-20">
                <h2 class="text-4xl md:text-5xl font-display font-bold mb-6">Ecossistema Completo</h2>
                <p class="text-zinc-400 text-lg">Uma suite de ferramentas desenhadas para trabalhar em perfeita harmonia.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 grid-rows-4 md:grid-rows-3 gap-4 h-auto md:h-[800px]">
                
                <!-- Large Item -->
                <div class="md:col-span-2 md:row-span-2 glass-card rounded-3xl p-8 relative overflow-hidden group">
                    <div class="relative z-10 h-full flex flex-col justify-between">
                        <div>
                            <i data-lucide="smartphone" class="w-8 h-8 text-white mb-4 stroke-1"></i>
                            <h3 class="text-2xl font-bold mb-2">App Nativo</h3>
                            <p class="text-zinc-400">Funciona offline. Sincroniza quando reconecta.</p>
                        </div>
                    </div>
                    <div class="absolute right-0 bottom-0 w-2/3 h-full bg-gradient-to-l from-zinc-900 to-transparent opacity-50 z-0"></div>
                    <img src="https://images.unsplash.com/photo-1616348436168-de43ad0db179?q=80&w=1000&auto=format&fit=crop" class="absolute right-[-50px] bottom-[-50px] w-[80%] rounded-xl opacity-40 group-hover:opacity-60 group-hover:scale-105 transition-all duration-500" alt="App">
                </div>

                <!-- Tall Item -->
                <div class="md:col-span-1 md:row-span-2 glass-card rounded-3xl p-6 flex flex-col justify-between group relative overflow-hidden">
                     <div class="absolute inset-0 bg-gradient-to-b from-green-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    <i data-lucide="cloud-rain" class="w-8 h-8 text-blue-400 mb-4 stroke-1"></i>
                    <div>
                        <h3 class="text-xl font-bold mb-2">Clima</h3>
                        <p class="text-zinc-500 text-sm">Alertas de estresse térmico baseados na previsão local.</p>
                    </div>
                </div>

                <!-- Standard Item -->
                <div class="md:col-span-1 md:row-span-1 glass-card rounded-3xl p-6 flex flex-col justify-center items-center text-center hover:bg-white/5 transition-colors cursor-pointer">
                    <div class="w-12 h-12 rounded-full bg-white/5 flex items-center justify-center mb-3"><i data-lucide="users" class="w-6 h-6 text-zinc-300 stroke-1"></i></div>
                    <h3 class="font-bold">Equipe</h3>
                </div>

                <!-- Standard Item -->
                <div class="md:col-span-1 md:row-span-1 glass-card rounded-3xl p-6 flex flex-col justify-center items-center text-center hover:bg-white/5 transition-colors cursor-pointer">
                     <div class="w-12 h-12 rounded-full bg-white/5 flex items-center justify-center mb-3"><i data-lucide="file-text" class="w-6 h-6 text-zinc-300 stroke-1"></i></div>
                    <h3 class="font-bold">Relatórios</h3>
                </div>

                <!-- Wide Item -->
                <div class="md:col-span-2 md:row-span-1 glass-card rounded-3xl p-8 flex items-center justify-between relative overflow-hidden">
                    <div class="relative z-10">
                        <h3 class="text-xl font-bold mb-1">API Aberta</h3>
                        <p class="text-zinc-400 text-sm">Conecte com seu ERP ou sistemas legados.</p>
                    </div>
                    <div class="flex gap-2 font-mono text-[10px] text-green-500 bg-black/50 p-3 rounded border border-white/10">
                        <span>POST /api/v1/milk-production</span>
                    </div>
                </div>

                <!-- Last Item -->
                 <div class="md:col-span-2 md:row-span-1 glass-card rounded-3xl p-8 flex flex-col justify-center bg-gradient-to-r from-green-900/20 to-transparent">
                    <h3 class="text-xl font-bold mb-2">Suporte Premium</h3>
                    <div class="flex items-center gap-4 mt-2">
                        <div class="flex -space-x-3">
                            <img src="https://i.pravatar.cc/100?img=1" class="w-8 h-8 rounded-full border border-black" alt="">
                            <img src="https://i.pravatar.cc/100?img=2" class="w-8 h-8 rounded-full border border-black" alt="">
                            <img src="https://i.pravatar.cc/100?img=3" class="w-8 h-8 rounded-full border border-black" alt="">
                        </div>
                        <p class="text-zinc-400 text-xs">Zootecnistas disponíveis via chat.</p>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- Pricing -->
    <section id="pricing" class="py-32 border-t border-white/5">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-display font-bold mb-4">Planos simples</h2>
                <p class="text-zinc-400">Cresça sem surpresas na fatura.</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8 max-w-5xl mx-auto">
                <!-- Starter -->
                <div class="glass-card rounded-2xl p-8 hover:border-zinc-600 transition-colors">
                    <div class="text-zinc-400 font-medium mb-4">Pequeno Produtor</div>
                    <div class="text-4xl font-bold mb-1">R$ 0</div>
                    <div class="text-sm text-zinc-500 mb-6">Para sempre, até 50 animais.</div>
                    <ul class="space-y-3 mb-8 text-sm text-zinc-300">
                        <li class="flex gap-2"><i data-lucide="check" class="w-4 h-4 text-green-500"></i> Gestão de rebanho</li>
                        <li class="flex gap-2"><i data-lucide="check" class="w-4 h-4 text-green-500"></i> App Mobile</li>
                        <li class="flex gap-2"><i data-lucide="check" class="w-4 h-4 text-green-500"></i> Suporte por e-mail</li>
                    </ul>
                    <a href="#" class="block w-full py-3 rounded-lg border border-white/10 text-center text-sm font-semibold hover:bg-white hover:text-black transition-all">Começar Grátis</a>
                </div>

                <!-- Pro (Highlighted) -->
                <div class="glass-card rounded-2xl p-8 border-green-500/30 bg-green-500/5 relative">
                    <div class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 bg-green-500 text-black text-[10px] font-bold px-3 py-1 rounded-full uppercase tracking-wider">Recomendado</div>
                    <div class="text-green-400 font-medium mb-4">Profissional</div>
                    <div class="text-4xl font-bold mb-1">R$ 299</div>
                    <div class="text-sm text-zinc-500 mb-6">cobrados mensalmente</div>
                    <ul class="space-y-3 mb-8 text-sm text-zinc-200">
                        <li class="flex gap-2"><i data-lucide="check" class="w-4 h-4 text-green-500"></i> Até 500 animais</li>
                        <li class="flex gap-2"><i data-lucide="check" class="w-4 h-4 text-green-500"></i> Analytics Preditivo</li>
                        <li class="flex gap-2"><i data-lucide="check" class="w-4 h-4 text-green-500"></i> Integração com Ordenha</li>
                        <li class="flex gap-2"><i data-lucide="check" class="w-4 h-4 text-green-500"></i> 3 Usuários</li>
                    </ul>
                    <a href="#" class="block w-full py-3 rounded-lg bg-green-600 hover:bg-green-500 text-center text-sm font-semibold text-white shadow-lg shadow-green-900/20 transition-all">Assinar Pro</a>
                </div>

                <!-- Enterprise -->
                <div class="glass-card rounded-2xl p-8 hover:border-zinc-600 transition-colors">
                    <div class="text-zinc-400 font-medium mb-4">Enterprise</div>
                    <div class="text-4xl font-bold mb-1">Sob Consulta</div>
                    <div class="text-sm text-zinc-500 mb-6">Para grandes cooperativas.</div>
                    <ul class="space-y-3 mb-8 text-sm text-zinc-300">
                        <li class="flex gap-2"><i data-lucide="check" class="w-4 h-4 text-green-500"></i> Animais ilimitados</li>
                        <li class="flex gap-2"><i data-lucide="check" class="w-4 h-4 text-green-500"></i> API Dedicada</li>
                        <li class="flex gap-2"><i data-lucide="check" class="w-4 h-4 text-green-500"></i> Gerente de conta</li>
                    </ul>
                    <a href="#" class="block w-full py-3 rounded-lg border border-white/10 text-center text-sm font-semibold hover:bg-white hover:text-black transition-all">Falar com Vendas</a>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ -->
    <section id="faq" class="py-20 max-w-3xl mx-auto px-6">
        <h2 class="text-3xl font-display font-bold mb-10 text-center">Perguntas Frequentes</h2>
        
        <div class="space-y-4" x-data="{ active: null }">
            <!-- Item 1 -->
            <div class="border border-white/10 rounded-lg overflow-hidden">
                <button @click="active = (active === 1 ? null : 1)" class="w-full p-4 text-left flex justify-between items-center hover:bg-white/5 transition-colors">
                    <span class="font-medium">Funciona sem internet?</span>
                    <i data-lucide="chevron-down" class="w-4 h-4 transition-transform" :class="{ 'rotate-180': active === 1 }"></i>
                </button>
                <div x-show="active === 1" x-collapse class="p-4 text-zinc-400 text-sm border-t border-white/5 bg-white/[0.02]">
                    Sim! Nosso aplicativo armazena todos os dados localmente e sincroniza automaticamente com a nuvem assim que uma conexão é detectada.
                </div>
            </div>
            <!-- Item 2 -->
            <div class="border border-white/10 rounded-lg overflow-hidden">
                <button @click="active = (active === 2 ? null : 2)" class="w-full p-4 text-left flex justify-between items-center hover:bg-white/5 transition-colors">
                    <span class="font-medium">Como migrar meus dados antigos?</span>
                    <i data-lucide="chevron-down" class="w-4 h-4 transition-transform" :class="{ 'rotate-180': active === 2 }"></i>
                </button>
                <div x-show="active === 2" x-collapse class="p-4 text-zinc-400 text-sm border-t border-white/5 bg-white/[0.02]">
                    Temos uma ferramenta de importação compatível com Excel e CSV. Nossa equipe de suporte também pode auxiliar na migração de sistemas legados.
                </div>
            </div>
             <!-- Item 3 -->
             <div class="border border-white/10 rounded-lg overflow-hidden">
                <button @click="active = (active === 3 ? null : 3)" class="w-full p-4 text-left flex justify-between items-center hover:bg-white/5 transition-colors">
                    <span class="font-medium">Preciso de equipamentos especiais?</span>
                    <i data-lucide="chevron-down" class="w-4 h-4 transition-transform" :class="{ 'rotate-180': active === 3 }"></i>
                </button>
                <div x-show="active === 3" x-collapse class="p-4 text-zinc-400 text-sm border-t border-white/5 bg-white/[0.02]">
                    Não. O Lactech funciona em qualquer smartphone ou computador. Integrações com sensores (IoT) são opcionais.
                </div>
            </div>
        </div>
    </section>

    <!-- Big CTA Footer -->
    <footer class="relative pt-32 pb-10 overflow-hidden border-t border-white/10">
        <!-- Background Gradient -->
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-green-900/20 via-background to-background pointer-events-none"></div>

        <div class="max-w-7xl mx-auto px-6 relative z-10">
            <div class="flex flex-col items-center text-center mb-20">
                <h2 class="text-5xl md:text-9xl font-bold font-display tracking-tighter text-transparent bg-clip-text bg-gradient-to-b from-white to-zinc-700 mb-8">Lactech.</h2>
                <p class="text-xl text-zinc-400 max-w-xl mb-10">O futuro da sua fazenda começa com um clique. Teste gratuitamente por 14 dias, sem cartão de crédito.</p>
                <a href="login.php" class="px-12 py-5 bg-white text-black rounded-full text-xl font-bold hover:scale-105 transition-transform shadow-[0_0_60px_-15px_rgba(255,255,255,0.5)]">
                    Iniciar Teste Grátis
                </a>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-10 border-t border-white/10 pt-16">
                <div>
                    <h4 class="font-bold mb-4 text-white">Produto</h4>
                    <ul class="space-y-2 text-sm text-zinc-500">
                        <li><a href="#" class="hover:text-green-400 transition-colors">Recursos</a></li>
                        <li><a href="#" class="hover:text-green-400 transition-colors">Integrações</a></li>
                        <li><a href="#" class="hover:text-green-400 transition-colors">Preços</a></li>
                        <li><a href="#" class="hover:text-green-400 transition-colors">Changelog</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-bold mb-4 text-white">Empresa</h4>
                    <ul class="space-y-2 text-sm text-zinc-500">
                        <li><a href="#" class="hover:text-green-400 transition-colors">Sobre</a></li>
                        <li><a href="#" class="hover:text-green-400 transition-colors">Carreiras</a></li>
                        <li><a href="#" class="hover:text-green-400 transition-colors">Blog</a></li>
                        <li><a href="#" class="hover:text-green-400 transition-colors">Contato</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-bold mb-4 text-white">Legal</h4>
                    <ul class="space-y-2 text-sm text-zinc-500">
                        <li><a href="#" class="hover:text-green-400 transition-colors">Privacidade</a></li>
                        <li><a href="#" class="hover:text-green-400 transition-colors">Termos</a></li>
                        <li><a href="#" class="hover:text-green-400 transition-colors">Segurança</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-bold mb-4 text-white">Social</h4>
                    <div class="flex gap-4">
                        <a href="#" class="w-10 h-10 rounded-full bg-white/5 flex items-center justify-center hover:bg-green-500 hover:text-black transition-all"><i data-lucide="instagram" class="w-5 h-5 stroke-1"></i></a>
                        <a href="#" class="w-10 h-10 rounded-full bg-white/5 flex items-center justify-center hover:bg-green-500 hover:text-black transition-all"><i data-lucide="twitter" class="w-5 h-5 stroke-1"></i></a>
                        <a href="#" class="w-10 h-10 rounded-full bg-white/5 flex items-center justify-center hover:bg-green-500 hover:text-black transition-all"><i data-lucide="linkedin" class="w-5 h-5 stroke-1"></i></a>
                    </div>
                </div>
            </div>
            
            <div class="mt-16 flex flex-col md:flex-row justify-between items-center text-xs text-zinc-600 pb-8">
                <p>&copy; 2025 Lactech Systems Ltda. Todos os direitos reservados.</p>
                <div class="flex items-center gap-2 mt-4 md:mt-0">
                    <div class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></div>
                    <span>Todos os sistemas operacionais</span>
                </div>
            </div>
        </div>
    </footer>

    <!-- Init Lucide -->
    <script>
        lucide.createIcons();
        
        // Scroll Animation Logic (Simple IntersectionObserver)
        const observerOptions = {
            threshold: 0.1,
            rootMargin: "0px 0px -50px 0px"
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('opacity-100', 'translate-y-0');
                    entry.target.classList.remove('opacity-0', 'translate-y-10');
                }
            });
        }, observerOptions);

        // Select elements to animate (if you add class 'animate-on-scroll' to them)
        // For now, standard animations are CSS based on load.
        
        // Navbar Blur on Scroll
        window.addEventListener('scroll', () => {
            const nav = document.getElementById('navbar');
            if (window.scrollY > 50) {
                nav.classList.add('bg-black/50', 'backdrop-blur-xl');
            } else {
                nav.classList.remove('bg-black/50', 'backdrop-blur-xl');
            }
        });
    </script>
</body>
</html>
