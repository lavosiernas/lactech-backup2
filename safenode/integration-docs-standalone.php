<?php
/**
 * SafeNode - Documentação de Integração com Hospedagem (Standalone)
 * Guia completo para integrar SafeNode Mail em qualquer hospedagem
 * Versão sem tabs - todo conteúdo em uma única página
 */

session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/init.php';

$pageTitle = 'Integração com Hospedagem';
?>
<!DOCTYPE html>
<html lang="pt-BR" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> | SafeNode</title>
    <link rel="icon" type="image/png" href="assets/img/logos (6).png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github-dark.min.css">
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                        mono: ['JetBrains Mono', 'monospace'],
                    },
                    colors: {
                        vercel: {
                            bg: '#000000',
                            surface: '#0a0a0a',
                            border: 'rgba(255, 255, 255, 0.1)',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #000000;
            color: #ededed;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            font-feature-settings: 'rlig' 1, 'calt' 1;
        }
        
        /* Subtle gradient background Vercel style */
        .hero-gradient {
            background: radial-gradient(ellipse 80% 50% at 50% -20%, rgba(120, 119, 198, 0.15), transparent);
        }
        
        .card-gradient-hover {
            position: relative;
            overflow: hidden;
        }
        
        .card-gradient-hover::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .card-gradient-hover:hover::before {
            opacity: 1;
        }
        
        pre code {
            font-family: 'JetBrains Mono', 'Consolas', 'Monaco', monospace;
            font-size: 13px;
            line-height: 1.7;
            font-weight: 400;
        }
        
        /* Better scrollbar styling */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        
        ::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.15);
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.25);
        }
        
        /* Better code blocks with Vercel style */
        pre {
            background: rgba(255, 255, 255, 0.03) !important;
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 12px;
            padding: 20px;
            overflow-x: auto;
            margin: 24px 0;
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.02);
        }
        
        code {
            background: rgba(255, 255, 255, 0.06);
            padding: 3px 6px;
            border-radius: 6px;
            font-size: 0.875em;
            font-weight: 500;
            border: 1px solid rgba(255, 255, 255, 0.04);
        }
        
        pre code {
            background: transparent;
            padding: 0;
            border: none;
            font-weight: 400;
        }
        
        /* Vercel-style link hover */
        a {
            color: #3b82f6;
            text-decoration: none;
            transition: color 0.15s ease;
        }
        
        a:hover {
            color: #60a5fa;
        }
        
        /* Better table styling */
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 12px;
            overflow: hidden;
        }
        
        table th {
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            color: #ffffff;
            padding: 14px 16px;
            background: rgba(255, 255, 255, 0.03);
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        table td {
            padding: 14px 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
            color: #a1a1aa;
            font-size: 14px;
        }
        
        table tbody tr:last-child td {
            border-bottom: none;
        }
        
        table tr:hover {
            background: rgba(255, 255, 255, 0.02);
        }
        
        table td code {
            font-size: 12px;
        }
        
        /* Gradient text effect */
        .gradient-text {
            background: linear-gradient(to right, #ffffff, #a1a1aa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        /* Button hover effects */
        .btn-primary {
            background: #ffffff;
            color: #000000;
            transition: all 0.15s ease;
            box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.1);
        }
        
        .btn-primary:hover {
            background: #fafafa;
            box-shadow: 0 0 0 4px rgba(255, 255, 255, 0.1);
            transform: translateY(-1px);
        }
        
        .btn-secondary {
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.15s ease;
        }
        
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.2);
        }
        
        /* Feature card enhancements */
        .feature-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.06);
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .feature-card:hover {
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
        }
        
        /* Badge styling */
        .badge {
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        /* Radial integration diagram specific styles */
        .integration-node {
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Improved connection lines with animated light pulses */
        .connection-line {
            stroke-dasharray: 300;
            stroke-dashoffset: 300;
            animation: drawLine 2s ease-in-out forwards;
        }
        
        @keyframes drawLine {
            to {
                stroke-dashoffset: 0;
            }
        }
        
        /* Animated data pulse effect on connection lines */
        .data-pulse {
            animation: pulse 2.5s ease-in-out infinite;
            opacity: 0;
        }
        
        @keyframes pulse {
            0%, 100% {
                opacity: 0;
                r: 2;
            }
            20% {
                opacity: 0.8;
                r: 3;
            }
            50% {
            opacity: 1;
                r: 4;
            }
            80% {
                opacity: 0.8;
                r: 3;
            }
        }
        
        /* Stagger animations for different data pulses */
        .data-pulse:nth-child(2) { animation-delay: 0.3s; }
        .data-pulse:nth-child(3) { animation-delay: 0.6s; }
        .data-pulse:nth-child(4) { animation-delay: 0.9s; }
        .data-pulse:nth-child(5) { animation-delay: 1.2s; }
        .data-pulse:nth-child(6) { animation-delay: 1.5s; }
        .data-pulse:nth-child(7) { animation-delay: 1.8s; }
        
        /* Light flash effect traveling along lines */
        .light-flash {
            stroke: rgba(168, 85, 247, 0.8);
            stroke-width: 2;
            stroke-linecap: round;
            stroke-dasharray: 10 300;
            stroke-dashoffset: 0;
            animation: travelLine 3s ease-in-out infinite;
            filter: blur(1px);
        }
        
        @keyframes travelLine {
            0% {
                stroke-dashoffset: 310;
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                stroke-dashoffset: 0;
                opacity: 0;
            }
        }
        
        .light-flash:nth-child(odd) {
            animation-delay: 0.5s;
        }
        
        .light-flash:nth-child(even) {
            animation-delay: 1.5s;
        }

        /* Section divider */
        .section-divider {
            margin: 80px 0;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
        }
    </style>
</head>
<body class="min-h-screen">
    
    <!-- Hero gradient background -->
    <div class="hero-gradient fixed inset-0 pointer-events-none"></div>
    
    <!-- Main Layout -->
    <div class="flex w-full relative">
        
        <!-- Simple Sidebar (sem tabs, apenas links) -->
        <aside class="hidden lg:block w-64 flex-shrink-0 border-r border-white/[0.08] sticky top-0 h-screen overflow-y-auto">
            <div class="px-6 py-8">
                <!-- Logo -->
                <div class="mb-10">
                    <a href="index.php" class="flex items-center gap-3 group">
                        <img src="assets/img/logos (6).png" alt="SafeNode" class="h-8 w-auto transition-transform group-hover:scale-105">
                        <span class="font-semibold text-[17px] text-white">SafeNode</span>
                    </a>
                </div>
                
                <!-- Title section -->
                <div class="mb-8">
                    <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-md bg-white/[0.06] border border-white/[0.08] mb-4">
                        <i data-lucide="plug" class="w-3.5 h-3.5 text-zinc-400"></i>
                        <span class="text-[11px] font-semibold text-zinc-400 uppercase tracking-wider">Integration</span>
                    </div>
                    <h1 class="text-[26px] font-bold text-white leading-tight mb-2 tracking-tight">Integração</h1>
                    <p class="text-[14px] text-zinc-500 leading-relaxed">
                        Guia completo para integrar SafeNode Mail em qualquer hospedagem
                    </p>
                </div>
                
                <!-- Bottom links -->
                <div class="pt-6 border-t border-white/[0.06] space-y-1">
                    <a href="docs.php" class="flex items-center gap-2.5 px-3 py-2 text-[14px] text-zinc-400 hover:text-white transition-colors rounded-lg hover:bg-white/[0.03]">
                        <i data-lucide="book" class="w-4 h-4"></i>
                        <span>Documentação</span>
                    </a>
                    <a href="index.php" class="flex items-center gap-2.5 px-3 py-2 text-[14px] text-zinc-400 hover:text-white transition-colors rounded-lg hover:bg-white/[0.03]">
                        <i data-lucide="arrow-left" class="w-4 h-4"></i>
                        <span>Voltar</span>
                    </a>
                    <a href="survey.php" class="flex items-center gap-2.5 px-3 py-2 text-[14px] text-zinc-400 hover:text-white transition-colors rounded-lg hover:bg-white/[0.03]">
                        <i data-lucide="message-square" class="w-4 h-4"></i>
                        <span>Pesquisa</span>
                    </a>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 min-w-0 relative">
            <div class="max-w-4xl mx-auto px-6 lg:px-16 py-12 lg:py-20">
                
                <!-- Overview Section -->
                <div class="mb-20">
                    <!-- Enhanced hero section -->
                    <div class="mb-20">
                        <h2 class="text-5xl lg:text-6xl font-bold text-white mb-6 leading-[1.1] tracking-tight">
                            O que é SafeNode<br/>Hosting Integration?
                        </h2>
                        <p class="text-xl text-zinc-400 leading-relaxed max-w-3xl">
                            SafeNode não é só um produto. É uma <span class="text-white font-medium">camada que fica entre o código e a infraestrutura</span>. 
                            Resolvemos a dor de configurar SMTP, DNS, templates e rastreamento de e-mails.
                        </p>
                    </div>

                    <!-- Improved feature cards -->
                    <div class="grid md:grid-cols-3 gap-4 mb-20">
                        <div class="feature-card rounded-2xl p-7">
                            <div class="w-11 h-11 bg-blue-500/10 rounded-xl flex items-center justify-center mb-5">
                                <i data-lucide="zap" class="w-5 h-5 text-blue-400"></i>
                            </div>
                            <h3 class="text-lg font-semibold text-white mb-2.5">Setup em 10 minutos</h3>
                            <p class="text-[14px] text-zinc-500 leading-relaxed">Script automatizado. Docker pronto. Zero configuração manual.</p>
                        </div>
                        <div class="feature-card rounded-2xl p-7">
                            <div class="w-11 h-11 bg-green-500/10 rounded-xl flex items-center justify-center mb-5">
                                <i data-lucide="mail" class="w-5 h-5 text-green-400"></i>
                            </div>
                            <h3 class="text-lg font-semibold text-white mb-2.5">API REST simples</h3>
                            <p class="text-[14px] text-zinc-500 leading-relaxed">Envie e-mails com uma requisição HTTP. Sem SMTP. Sem complexidade.</p>
                        </div>
                        <div class="feature-card rounded-2xl p-7">
                            <div class="w-11 h-11 bg-purple-500/10 rounded-xl flex items-center justify-center mb-5">
                                <i data-lucide="layers" class="w-5 h-5 text-purple-400"></i>
                            </div>
                            <h3 class="text-lg font-semibold text-white mb-2.5">Funciona em qualquer VPS</h3>
                            <p class="text-[14px] text-zinc-500 leading-relaxed">DigitalOcean, AWS, Hostinger, cPanel, Plesk... Qualquer lugar.</p>
                        </div>
                    </div>

                    <!-- Better list styling -->
                    <div class="mb-16">
                        <h3 class="text-3xl font-bold text-white mb-8 tracking-tight">Por que usar SafeNode?</h3>
                        <ul class="space-y-5">
                            <li class="flex items-start gap-4">
                                <div class="w-5 h-5 rounded-full bg-green-500/10 flex items-center justify-center flex-shrink-0 mt-1">
                                    <i data-lucide="check" class="w-3.5 h-3.5 text-green-400"></i>
                                </div>
                                <div class="text-[15px] leading-relaxed">
                                    <strong class="text-white font-semibold">Zero configuração de SMTP/DNS:</strong>
                                    <span class="text-zinc-400"> Não precisa configurar SPF, DKIM, DMARC manualmente</span>
                                </div>
                            </li>
                            <li class="flex items-start gap-4">
                                <div class="w-5 h-5 rounded-full bg-green-500/10 flex items-center justify-center flex-shrink-0 mt-1">
                                    <i data-lucide="check" class="w-3.5 h-3.5 text-green-400"></i>
                                </div>
                                <div class="text-[15px] leading-relaxed">
                                    <strong class="text-white font-semibold">Templates versionados:</strong>
                                    <span class="text-zinc-400"> Crie templates no Relay visual e use na API</span>
                                </div>
                            </li>
                            <li class="flex items-start gap-4">
                                <div class="w-5 h-5 rounded-full bg-green-500/10 flex items-center justify-center flex-shrink-0 mt-1">
                                    <i data-lucide="check" class="w-3.5 h-3.5 text-green-400"></i>
                                </div>
                                <div class="text-[15px] leading-relaxed">
                                    <strong class="text-white font-semibold">Analytics em tempo real:</strong>
                                    <span class="text-zinc-400"> Veja entregas, aberturas, cliques no dashboard</span>
                                </div>
                            </li>
                            <li class="flex items-start gap-4">
                                <div class="w-5 h-5 rounded-full bg-green-500/10 flex items-center justify-center flex-shrink-0 mt-1">
                                    <i data-lucide="check" class="w-3.5 h-3.5 text-green-400"></i>
                                </div>
                                <div class="text-[15px] leading-relaxed">
                                    <strong class="text-white font-semibold">Webhooks:</strong>
                                    <span class="text-zinc-400"> Receba eventos de entrega, abertura, clique em tempo real</span>
                                </div>
                            </li>
                            <li class="flex items-start gap-4">
                                <div class="w-5 h-5 rounded-full bg-green-500/10 flex items-center justify-center flex-shrink-0 mt-1">
                                    <i data-lucide="check" class="w-3.5 h-3.5 text-green-400"></i>
                                </div>
                                <div class="text-[15px] leading-relaxed">
                                    <strong class="text-white font-semibold">Escalável:</strong>
                                    <span class="text-zinc-400"> De 1 e-mail a milhões sem se preocupar com infraestrutura</span>
                                </div>
                            </li>
                        </ul>
                    </div>

                    <!-- Minimalist info section -->
                    <div class="pt-8 border-t border-white/[0.06]">
                        <p class="text-base text-zinc-400 leading-relaxed max-w-2xl">
                            Enquanto outros produtos focam em "e-mail transacional", SafeNode é uma <span class="text-white">camada completa</span>: Mail + Relay (editor visual) + IDE SafeCode + Integração pronta com hospedagem.
                        </p>
                    </div>

                    <!-- Integrations Section -->
                    <div class="mt-24">
                        <div class="text-center mb-14">
                            <h2 class="text-4xl lg:text-5xl font-bold text-white mb-5 tracking-tight">
                                Integre com suas hospedagens favoritas
                            </h2>
                            <p class="text-zinc-400 text-lg max-w-2xl mx-auto leading-relaxed">
                                Conecte-se perfeitamente com plataformas populares e serviços para melhorar seu fluxo de trabalho.
                            </p>
                        </div>

                        <!-- Radial Integration Diagram -->
                        <div class="relative w-full max-w-4xl mx-auto py-20">
                            <!-- Connection lines with animated pulses -->
                            <svg class="absolute inset-0 w-full h-full pointer-events-none" viewBox="0 0 700 500" preserveAspectRatio="xMidYMid meet">
                                <defs>
                                    <!-- Gradient for connection lines -->
                                    <linearGradient id="lineGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                                        <stop offset="0%" style="stop-color:rgba(255,255,255,0.1);stop-opacity:1" />
                                        <stop offset="50%" style="stop-color:rgba(255,255,255,0.3);stop-opacity:1" />
                                        <stop offset="100%" style="stop-color:rgba(255,255,255,0.1);stop-opacity:1" />
                                    </linearGradient>
                                </defs>
                                
                                <!-- Base connection lines - properly positioned to connect to icons -->
                                <!-- DigitalOcean (top-left) - moved up -->
                                <line x1="350" y1="250" x2="160" y2="120" stroke="url(#lineGradient)" stroke-width="2.5" class="connection-line"/>
                                <!-- cPanel (middle-left) -->
                                <line x1="350" y1="250" x2="160" y2="255" stroke="url(#lineGradient)" stroke-width="1.5" class="connection-line"/>
                                <!-- Hostinger (bottom-left) -->
                                <line x1="350" y1="250" x2="165" y2="360" stroke="url(#lineGradient)" stroke-width="1.5" class="connection-line"/>
                                <!-- AWS (top-right) - moved up -->
                                <line x1="350" y1="250" x2="540" y2="120" stroke="url(#lineGradient)" stroke-width="2.5" class="connection-line"/>
                                <!-- Plesk (middle-right) -->
                                <line x1="350" y1="250" x2="540" y2="255" stroke="url(#lineGradient)" stroke-width="1.5" class="connection-line"/>
                                <!-- Docker (bottom-right) -->
                                <line x1="350" y1="250" x2="535" y2="360" stroke="url(#lineGradient)" stroke-width="3.5" class="connection-line"/>
                                
                                <!-- Animated light flashes traveling along connections -->
                                <line x1="350" y1="250" x2="160" y2="120" class="light-flash" style="animation-delay: 0s;"/>
                                <line x1="350" y1="250" x2="160" y2="255" class="light-flash" style="animation-delay: 0.4s;"/>
                                <line x1="350" y1="250" x2="165" y2="360" class="light-flash" style="animation-delay: 0.8s;"/>
                                <line x1="350" y1="250" x2="540" y2="120" class="light-flash" style="animation-delay: 1.2s;"/>
                                <line x1="350" y1="250" x2="540" y2="255" class="light-flash" style="animation-delay: 1.6s;"/>
                                <line x1="350" y1="250" x2="535" y2="360" class="light-flash" style="animation-delay: 2s;"/>
                                
                                <!-- Data pulse circles traveling along lines -->
                                <!-- DigitalOcean -->
                                <circle cx="350" cy="250" r="3" fill="rgba(168,85,247,0.8)" class="data-pulse">
                                    <animate attributeName="cx" values="350;160" dur="2.5s" repeatCount="indefinite"/>
                                    <animate attributeName="cy" values="250;120" dur="2.5s" repeatCount="indefinite"/>
                                </circle>
                                <!-- cPanel -->
                                <circle cx="350" cy="250" r="3" fill="rgba(96,165,250,0.8)" class="data-pulse">
                                    <animate attributeName="cx" values="350;160" dur="2.5s" repeatCount="indefinite"/>
                                    <animate attributeName="cy" values="250;255" dur="2.5s" repeatCount="indefinite"/>
                                </circle>
                                <!-- Hostinger -->
                                <circle cx="350" cy="250" r="3" fill="rgba(168,85,247,0.8)" class="data-pulse">
                                    <animate attributeName="cx" values="350;165" dur="2.5s" repeatCount="indefinite"/>
                                    <animate attributeName="cy" values="250;360" dur="2.5s" repeatCount="indefinite"/>
                                </circle>
                                <!-- AWS -->
                                <circle cx="350" cy="250" r="3" fill="rgba(96,165,250,0.8)" class="data-pulse">
                                    <animate attributeName="cx" values="350;540" dur="2.5s" repeatCount="indefinite"/>
                                    <animate attributeName="cy" values="250;120" dur="2.5s" repeatCount="indefinite"/>
                                </circle>
                                <!-- Plesk -->
                                <circle cx="350" cy="250" r="3" fill="rgba(168,85,247,0.8)" class="data-pulse">
                                    <animate attributeName="cx" values="350;540" dur="2.5s" repeatCount="indefinite"/>
                                    <animate attributeName="cy" values="250;255" dur="2.5s" repeatCount="indefinite"/>
                                </circle>
                                <!-- Docker -->
                                <circle cx="350" cy="250" r="3" fill="rgba(96,165,250,0.8)" class="data-pulse">
                                    <animate attributeName="cx" values="350;535" dur="2.5s" repeatCount="indefinite"/>
                                    <animate attributeName="cy" values="250;360" dur="2.5s" repeatCount="indefinite"/>
                                </circle>
                                
                                <!-- Return data pulses (smaller, different colors) -->
                                <!-- DigitalOcean -->
                                <circle cx="160" cy="120" r="2" fill="rgba(34,197,94,0.8)" class="data-pulse" style="animation-delay: 1.2s;">
                                    <animate attributeName="cx" values="160;350" dur="2.5s" repeatCount="indefinite"/>
                                    <animate attributeName="cy" values="120;250" dur="2.5s" repeatCount="indefinite"/>
                                </circle>
                                <!-- cPanel -->
                                <circle cx="160" cy="255" r="2" fill="rgba(34,197,94,0.8)" class="data-pulse" style="animation-delay: 1.5s;">
                                    <animate attributeName="cx" values="160;350" dur="2.5s" repeatCount="indefinite"/>
                                    <animate attributeName="cy" values="255;250" dur="2.5s" repeatCount="indefinite"/>
                                </circle>
                                <!-- Hostinger -->
                                <circle cx="165" cy="360" r="2" fill="rgba(34,197,94,0.8)" class="data-pulse" style="animation-delay: 1.8s;">
                                    <animate attributeName="cx" values="165;350" dur="2.5s" repeatCount="indefinite"/>
                                    <animate attributeName="cy" values="360;250" dur="2.5s" repeatCount="indefinite"/>
                                </circle>
                                <!-- AWS -->
                                <circle cx="540" cy="120" r="2" fill="rgba(34,197,94,0.8)" class="data-pulse" style="animation-delay: 2.1s;">
                                    <animate attributeName="cx" values="540;350" dur="2.5s" repeatCount="indefinite"/>
                                    <animate attributeName="cy" values="120;250" dur="2.5s" repeatCount="indefinite"/>
                                </circle>
                                <!-- Plesk -->
                                <circle cx="540" cy="255" r="2" fill="rgba(34,197,94,0.8)" class="data-pulse" style="animation-delay: 2.4s;">
                                    <animate attributeName="cx" values="540;350" dur="2.5s" repeatCount="indefinite"/>
                                    <animate attributeName="cy" values="255;250" dur="2.5s" repeatCount="indefinite"/>
                                </circle>
                                <!-- Docker -->
                                <circle cx="535" cy="360" r="2" fill="rgba(34,197,94,0.8)" class="data-pulse" style="animation-delay: 2.7s;">
                                    <animate attributeName="cx" values="535;350" dur="2.5s" repeatCount="indefinite"/>
                                    <animate attributeName="cy" values="360;250" dur="2.5s" repeatCount="indefinite"/>
                                </circle>
                            </svg>

                            <div class="relative grid grid-cols-3 gap-y-24 items-center">
                                <!-- Left Column - Top -->
                                <div class="flex justify-center items-center">
                                    <div class="integration-node w-20 h-20 rounded-2xl bg-zinc-950 border border-zinc-800 hover:border-zinc-600 flex items-center justify-center transition-all hover:scale-110 cursor-pointer group shadow-xl">
                                        <img src="assets/img/digitalocean.png" alt="DigitalOcean" class="w-12 h-12 object-contain group-hover:scale-110 transition-transform">
                                    </div>
                                </div>

                                <!-- Center Column - Top (empty for spacing) -->
                                <div></div>

                                <!-- Right Column - Top -->
                                <div class="flex justify-center items-center">
                                    <div class="integration-node w-20 h-20 rounded-2xl bg-zinc-950 border border-zinc-800 hover:border-zinc-600 flex items-center justify-center transition-all hover:scale-110 cursor-pointer group shadow-xl">
                                        <img src="assets/img/aws.png" alt="AWS" class="w-10 h-10 object-contain group-hover:scale-110 transition-transform">
                                    </div>
                                </div>

                                <!-- Left Column - Middle -->
                                <div class="flex justify-center items-center">
                                    <div class="integration-node w-20 h-20 rounded-2xl bg-zinc-950 border border-zinc-800 hover:border-zinc-600 flex items-center justify-center transition-all hover:scale-110 cursor-pointer group shadow-xl">
                                        <img src="assets/img/cpanel.png" alt="cPanel" class="w-12 h-12 object-contain group-hover:scale-110 transition-transform">
                                    </div>
                                </div>

                                <!-- Center Column - Middle (SafeNode Logo) -->
                                <div class="flex justify-center items-center">
                                    <div class="relative z-10">
                                        <div class="absolute inset-0 bg-white/20 blur-2xl rounded-full"></div>
                                        <div class="relative w-24 h-24 rounded-3xl bg-gradient-to-br from-zinc-900 to-black border-2 border-zinc-800 flex items-center justify-center shadow-2xl">
                                            <img src="assets/img/logos (6).png" alt="SafeNode" class="w-16 h-16 object-contain">
                                        </div>
                                    </div>
                                </div>

                                <!-- Right Column - Middle -->
                                <div class="flex justify-center items-center">
                                    <div class="integration-node w-20 h-20 rounded-2xl bg-zinc-950 border border-zinc-800 hover:border-zinc-600 flex items-center justify-center transition-all hover:scale-110 cursor-pointer group shadow-xl">
                                        <img src="assets/img/plesk.png" alt="Plesk" class="w-12 h-12 object-contain group-hover:scale-110 transition-transform">
                                    </div>
                                </div>

                                <!-- Left Column - Bottom -->
                                <!-- Hostinger with only purple border, not full background -->
                                <div class="flex justify-center items-center">
                                    <div class="integration-node w-20 h-20 rounded-2xl bg-zinc-950 border-2 border-purple-500/60 hover:border-purple-400 flex items-center justify-center transition-all hover:scale-110 cursor-pointer group relative shadow-xl shadow-purple-500/20">
                                        <div class="absolute -top-3 -right-3 z-10">
                                            <span class="flex w-6 h-6 bg-purple-500 rounded-full items-center justify-center shadow-lg shadow-purple-500/50 animate-pulse">
                                                <i data-lucide="star" class="w-3 h-3 text-white fill-white"></i>
                                            </span>
                                        </div>
                                        <img src="assets/img/hostinger.png" alt="Hostinger" class="w-14 h-14 object-contain group-hover:scale-110 transition-transform">
                                    </div>
                                </div>

                                <!-- Center Column - Bottom (empty for spacing) -->
                                <div></div>

                                <!-- Right Column - Bottom -->
                                <div class="flex justify-center items-center">
                                    <div class="integration-node w-20 h-20 rounded-2xl bg-zinc-950 border border-zinc-800 hover:border-zinc-600 flex items-center justify-center transition-all hover:scale-110 cursor-pointer group shadow-xl">
                                        <img src="assets/img/docker.png" alt="Docker" class="w-12 h-12 object-contain group-hover:scale-110 transition-transform">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section Divider -->
                <div class="section-divider"></div>

                <!-- Quick Start Section -->
                <div class="mb-20">
                    <div class="mb-16">
                        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-green-500/10 border border-green-500/20 mb-6">
                            <i data-lucide="zap" class="w-4 h-4 text-green-400"></i>
                            <span class="text-sm font-semibold text-green-400 uppercase tracking-wide">Quick Start</span>
                        </div>
                        <h2 class="text-5xl font-bold text-white mb-5 tracking-tight">Comece em minutos</h2>
                        <p class="text-lg text-zinc-400 leading-relaxed">Tenha e-mails funcionando em qualquer VPS em poucos minutos.</p>
                    </div>

                    <div class="space-y-16">
                        <section>
                            <!-- Better step indicators with gradient accents -->
                            <div class="flex items-center gap-4 mb-6">
                                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500/20 to-purple-500/20 border border-blue-500/30 flex items-center justify-center shadow-lg">
                                    <span class="text-base font-bold text-white">1</span>
                                </div>
                                <h3 class="text-2xl font-bold text-white tracking-tight">Obtenha seu token da API</h3>
                            </div>
                            <ol class="space-y-3 text-zinc-300 list-decimal list-inside ml-14 text-[15px]">
                                <li>Acesse <a href="mail.php" class="text-blue-400 hover:text-blue-300 underline-offset-2 font-medium">https://safenode.cloud/mail</a></li>
                                <li>Faça login (ou crie uma conta grátis)</li>
                                <li>Crie um novo projeto de e-mail</li>
                                <li>Copie o token gerado</li>
                            </ol>
                        </section>

                        <section>
                            <div class="flex items-center gap-4 mb-6">
                                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500/20 to-purple-500/20 border border-blue-500/30 flex items-center justify-center shadow-lg">
                                    <span class="text-base font-bold text-white">2</span>
                                </div>
                                <h3 class="text-2xl font-bold text-white tracking-tight">Instale via script (Linux/Mac/Windows)</h3>
                            </div>
                            <pre><code class="language-bash">curl -o setup-safenode.sh https://safenode.cloud/integration/setup-safenode.sh
sudo bash setup-safenode.sh</code></pre>
                        </section>

                        <section>
                            <div class="flex items-center gap-4 mb-6">
                                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500/20 to-purple-500/20 border border-blue-500/30 flex items-center justify-center shadow-lg">
                                    <span class="text-base font-bold text-white">3</span>
                                </div>
                                <h3 class="text-2xl font-bold text-white tracking-tight">Configure variáveis de ambiente</h3>
                            </div>
                            <pre><code class="language-bash">cd /opt/safenode-mail
cp .env.example .env
nano .env  # Cole seu token da API</code></pre>
                        </section>

                        <section>
                            <div class="flex items-center gap-4 mb-6">
                                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500/20 to-purple-500/20 border border-blue-500/30 flex items-center justify-center shadow-lg">
                                    <span class="text-base font-bold text-white">4</span>
                                </div>
                                <h3 class="text-2xl font-bold text-white tracking-tight">Envie seu primeiro e-mail</h3>
                            </div>
                            
                            <div class="space-y-8">
                                <div>
                                    <!-- Improved code example headers with better badges -->
                                    <h4 class="text-base font-semibold text-white mb-4 flex items-center gap-3">
                                        <span class="badge px-3 py-1.5 bg-gradient-to-r from-yellow-500/20 to-orange-500/20 border border-yellow-500/30 text-yellow-300 rounded-lg shadow-lg">Node.js</span>
                                    </h4>
                                    <pre><code class="language-javascript">const axios = require('axios');

const response = await axios.post(
  'https://safenode.cloud/api/mail/send',
  {
    to: 'usuario@exemplo.com',
    subject: 'Olá do SafeNode!',
    html: '<h1>Seu primeiro e-mail!</h1><p>Funciona! 🎉</p>'
  },
  {
    headers: {
      'Authorization': 'Bearer SEU_TOKEN_AQUI',
      'Content-Type': 'application/json'
    }
  }
);

console.log(response.data);</code></pre>
                                </div>

                                <div>
                                    <h4 class="text-base font-semibold text-white mb-4 flex items-center gap-3">
                                        <span class="badge px-3 py-1.5 bg-gradient-to-r from-purple-500/20 to-pink-500/20 border border-purple-500/30 text-purple-300 rounded-lg shadow-lg">PHP</span>
                                    </h4>
                                    <pre><code class="language-php">$token = 'SEU_TOKEN_AQUI';
$data = [
    'to' => 'usuario@exemplo.com',
    'subject' => 'Olá do SafeNode!',
    'html' => '<h1>Seu primeiro e-mail!</h1><p>Funciona! 🎉</p>'
];

$ch = curl_init('https://safenode.cloud/api/mail/send');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$result = json_decode($response, true);
echo json_encode($result, JSON_PRETTY_PRINT);</code></pre>
                                </div>
                            </div>
                        </section>

                        <div class="bg-green-500/[0.06] border border-green-500/20 rounded-2xl p-6">
                            <div class="flex items-start gap-4">
                                <div class="w-10 h-10 rounded-xl bg-green-500/10 flex items-center justify-center flex-shrink-0">
                                    <i data-lucide="check-circle" class="w-5 h-5 text-green-400"></i>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-white mb-2 text-[15px]">Pronto! 🎉</h4>
                                    <p class="text-[14px] text-zinc-400 leading-relaxed">
                                        Seu primeiro e-mail foi enviado. Veja os logs e analytics no dashboard.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section Divider -->
                <div class="section-divider"></div>

                <!-- API Reference Section -->
                <div class="mb-20">
                    <div class="mb-16">
                        <h2 class="text-5xl font-bold text-white mb-5 tracking-tight">API Reference</h2>
                        <p class="text-lg text-zinc-400 leading-relaxed">Documentação completa da API SafeNode Mail.</p>
                    </div>

                    <div class="space-y-16">
                        <section>
                            <h3 class="text-2xl font-bold text-white mb-6 tracking-tight">Base URL</h3>
                            <pre><code>https://safenode.cloud/api/mail</code></pre>
                        </section>

                        <section>
                            <h3 class="text-2xl font-bold text-white mb-6 tracking-tight">Autenticação</h3>
                            <p class="text-zinc-400 mb-6 leading-relaxed text-[15px]">
                                Todas as requisições devem incluir o token no header <code class="text-zinc-300">Authorization</code>:
                            </p>
                            <pre><code>Authorization: Bearer seu_token_aqui</code></pre>
                        </section>

                        <section>
                            <h3 class="text-2xl font-bold text-white mb-6 tracking-tight">
                                <span class="badge px-2 py-1 bg-green-500/10 text-green-400 rounded mr-3">POST</span>
                                /send
                            </h3>
                            <p class="text-zinc-400 mb-6 leading-relaxed text-[15px]">Envia um e-mail.</p>

                            <div class="space-y-8">
                                <div>
                                    <h4 class="text-lg font-semibold text-white mb-4">Request Body</h4>
                                    <pre><code class="language-json">{
  "to": "destinatario@email.com",        // Obrigatório
  "subject": "Assunto do e-mail",        // Obrigatório
  "html": "<h1>Conteúdo HTML</h1>",     // Obrigatório (ou use template)
  "text": "Versão texto (opcional)",     // Opcional
  "template": "nome-do-template",        // Opcional
  "variables": {                         // Opcional (para templates)
    "nome": "João",
    "codigo": "123456"
  }
}</code></pre>
                                </div>

                                <div>
                                    <h4 class="text-lg font-semibold text-white mb-4">Response (Sucesso)</h4>
                                    <pre><code class="language-json">{
  "success": true,
  "message_id": "msg_abc123",
  "status": "sent"
}</code></pre>
                                </div>

                                <div>
                                    <h4 class="text-lg font-semibold text-white mb-4">Response (Erro)</h4>
                                    <pre><code class="language-json">{
  "success": false,
  "error": "Mensagem de erro",
  "error_code": "ERROR_CODE"
}</code></pre>
                                </div>
                            </div>
                        </section>

                        <section>
                            <h3 class="text-2xl font-bold text-white mb-6 tracking-tight">Usando Templates</h3>
                            <p class="text-zinc-400 mb-6 leading-relaxed text-[15px]">
                                Templates são criados no <a href="safefig.php" class="text-blue-400 hover:text-blue-300 underline-offset-2">Relay visual</a> e podem ser reutilizados:
                            </p>
                            <pre><code class="language-json">{
  "to": "usuario@exemplo.com",
  "template": "confirmar-cadastro",
  "variables": {
    "nome": "João",
    "codigo": "123456",
    "link": "https://exemplo.com/confirmar?token=abc"
  }
}</code></pre>
                        </section>

                        <section>
                            <h3 class="text-2xl font-bold text-white mb-6 tracking-tight">Códigos de Erro Comuns</h3>
                            <div class="overflow-x-auto">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Código</th>
                                            <th>Descrição</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><code>MISSING_TOKEN</code></td>
                                            <td>Token não fornecido</td>
                                        </tr>
                                        <tr>
                                            <td><code>INVALID_TOKEN</code></td>
                                            <td>Token inválido ou expirado</td>
                                        </tr>
                                        <tr>
                                            <td><code>MISSING_FIELDS</code></td>
                                            <td>Campos obrigatórios ausentes</td>
                                        </tr>
                                        <tr>
                                            <td><code>INVALID_EMAIL</code></td>
                                            <td>E-mail inválido</td>
                                        </tr>
                                        <tr>
                                            <td><code>LIMIT_EXCEEDED</code></td>
                                            <td>Limite de envios excedido</td>
                                        </tr>
                                        <tr>
                                            <td><code>TEMPLATE_NOT_FOUND</code></td>
                                            <td>Template não encontrado</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    </div>
                </div>

                <!-- Section Divider -->
                <div class="section-divider"></div>

                <!-- Examples Section -->
                <div class="mb-20">
                    <div class="mb-16">
                        <h2 class="text-5xl font-bold text-white mb-5 tracking-tight">Exemplos de Uso</h2>
                        <p class="text-lg text-zinc-400 leading-relaxed">Casos de uso comuns e exemplos práticos.</p>
                    </div>

                    <div class="space-y-16">
                        <section>
                            <h3 class="text-2xl font-bold text-white mb-6 tracking-tight">Confirmação de Cadastro</h3>
                            <pre><code class="language-javascript">// Node.js
const axios = require('axios');

async function sendConfirmationEmail(userEmail, userName, confirmToken) {
  const response = await axios.post(
    'https://safenode.cloud/api/mail/send',
    {
      to: userEmail,
      subject: 'Confirme seu cadastro',
      html: `
        <h1>Olá, ${userName}!</h1>
        <p>Clique no link abaixo para confirmar seu cadastro:</p>
        <p><a href="https://meusite.com/confirmar?token=${confirmToken}">Confirmar Cadastro</a></p>
        <p>Este link expira em 24 horas.</p>
      `,
      text: `Olá, ${userName}!\n\nClique no link para confirmar: https://meusite.com/confirmar?token=${confirmToken}`
    },
    {
      headers: {
        'Authorization': 'Bearer ' + process.env.SAFENODE_TOKEN,
        'Content-Type': 'application/json'
      }
    }
  );
  
  return response.data;
}</code></pre>
                        </section>

                        <section>
                            <h3 class="text-2xl font-bold text-white mb-6 tracking-tight">Reset de Senha</h3>
                            <pre><code class="language-php">// PHP
function sendPasswordReset($email, $name, $resetToken) {
    $token = getenv('SAFENODE_TOKEN');
    
    $data = [
        'to' => $email,
        'template' => 'reset-password',
        'variables' => [
            'nome' => $name,
            'link' => "https://meusite.com/reset?token={$resetToken}",
            'expira_em' => '1 hora'
        ]
    ];
    
    $ch = curl_init('https://safenode.cloud/api/mail/send');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'success' => $httpCode === 200,
        'data' => json_decode($response, true)
    ];
}</code></pre>
                        </section>

                        <section>
                            <h3 class="text-2xl font-bold text-white mb-6 tracking-tight">Notificação de Pedido</h3>
                            <pre><code class="language-javascript">// Node.js
async function sendOrderConfirmation(order) {
  const itemsHTML = order.items.map(item => 
    `<tr>
      <td>${item.name}</td>
      <td>R$ ${item.price.toFixed(2)}</td>
      <td>${item.quantity}</td>
    </tr>`
  ).join('');

  return await axios.post(
    'https://safenode.cloud/api/mail/send',
    {
      to: order.customerEmail,
      subject: `Pedido #${order.id} confirmado`,
      html: `
        <h1>Pedido Confirmado!</h1>
        <p>Olá, ${order.customerName}!</p>
        <p>Seu pedido #${order.id} foi confirmado.</p>
        <table border="1" cellpadding="10">
          <thead>
            <tr><th>Produto</th><th>Preço</th><th>Qtd</th></tr>
          </thead>
          <tbody>
            ${itemsHTML}
          </tbody>
        </table>
        <p><strong>Total: R$ ${order.total.toFixed(2)}</strong></p>
      `
    },
    {
      headers: {
        'Authorization': 'Bearer ' + process.env.SAFENODE_TOKEN,
        'Content-Type': 'application/json'
      }
    }
  );
}</code></pre>
                        </section>
                    </div>
                </div>

                <!-- Section Divider -->
                <div class="section-divider"></div>

                <!-- Docker Section -->
                <div class="mb-20">
                    <div class="mb-16">
                        <h2 class="text-5xl font-bold text-white mb-5 tracking-tight">Docker Setup</h2>
                        <p class="text-lg text-zinc-400 leading-relaxed">Use nosso template Docker ou integre em sua stack existente.</p>
                    </div>

                    <div class="space-y-16">
                        <section>
                            <h3 class="text-2xl font-bold text-white mb-6 tracking-tight">docker-compose.yml</h3>
                            <pre><code class="language-yaml">version: '3.8'

services:
  app:
    image: php:8.1-fpm-alpine
    volumes:
      - ./app:/var/www/html
    environment:
      - SAFENODE_API_TOKEN=${SAFENODE_API_TOKEN}
    depends_on:
      - mysql

  nginx:
    image: nginx:alpine
    ports:
      - "80:80"
    volumes:
      - ./app:/var/www/html:ro
    depends_on:
      - app

  mysql:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASS}
      MYSQL_DATABASE: ${DB_NAME}
      MYSQL_USER: ${DB_USER}
      MYSQL_PASSWORD: ${DB_PASS}
    volumes:
      - mysql-data:/var/lib/mysql

volumes:
  mysql-data:</code></pre>
                        </section>

                        <section>
                            <h3 class="text-2xl font-bold text-white mb-6 tracking-tight">.env</h3>
                            <pre><code>SAFENODE_API_TOKEN=seu_token_aqui
DB_ROOT_PASS=senha_forte
DB_NAME=safenode_mail
DB_USER=safenode
DB_PASS=senha_forte</code></pre>
                        </section>

                        <section>
                            <h3 class="text-2xl font-bold text-white mb-6 tracking-tight">Uso</h3>
                            <pre><code class="language-bash"># Iniciar
docker-compose up -d

# Ver logs
docker-compose logs -f

# Parar
docker-compose down

# Parar e remover volumes
docker-compose down -v</code></pre>
                        </section>

                        <div class="bg-blue-500/[0.06] border border-blue-500/20 rounded-2xl p-6">
                            <div class="flex items-start gap-4">
                                <div class="w-10 h-10 rounded-xl bg-blue-500/10 flex items-center justify-center flex-shrink-0">
                                    <i data-lucide="info" class="w-5 h-5 text-blue-400"></i>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-white mb-2 text-[15px]">Template completo</h4>
                                    <p class="text-[14px] text-zinc-400 leading-relaxed">
                                        Baixe o template completo com nginx, PHP e MySQL configurados em: 
                                        <a href="integration/docker-compose.mail.yml" class="text-blue-400 hover:text-blue-300 underline-offset-2">docker-compose.mail.yml</a>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Enhanced CTA section -->
                <div class="mt-24 pt-16 border-t border-white/[0.08]">
                    <div class="feature-card rounded-3xl p-10 text-center relative overflow-hidden">
                        <div class="absolute inset-0 bg-gradient-to-br from-blue-500/5 via-purple-500/5 to-transparent pointer-events-none"></div>
                        <div class="relative">
                            <h2 class="text-3xl font-bold text-white mb-4 tracking-tight">Pronto para começar?</h2>
                            <p class="text-zinc-400 mb-8 text-[15px] leading-relaxed max-w-xl mx-auto">
                                Crie sua conta grátis e tenha e-mails funcionando em 10 minutos.
                            </p>
                            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                                <a href="register.php" class="btn-primary inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl font-semibold text-[14px]">
                                    Criar Conta Grátis
                                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                                </a>
                                <a href="survey.php" class="btn-secondary inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl font-semibold text-[14px] text-white">
                                    Responder Pesquisa
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <!-- Initialize -->
    <script>
        lucide.createIcons();
        hljs.highlightAll();
    </script>

</body>
</html>


