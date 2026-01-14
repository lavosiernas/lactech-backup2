<!DOCTYPE html>
<html lang="pt-BR" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SafeCode - Security for Developers | by SafeNode</title>
    <meta name="description" content="Professional security tools for modern developers. Real-time vulnerability detection.">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        
        :root {
            --background: 13 13 13;
            --foreground: 250 250 250;
            --card: 20 20 20;
            --card-foreground: 250 250 250;
            --border: 41 41 41;
            --muted: 36 36 36;
            --muted-foreground: 148 148 148;
            --primary: 250 250 250;
            --primary-foreground: 13 13 13;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: rgb(var(--background));
            color: rgb(var(--foreground));
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        .backdrop-blur-xl {
            backdrop-filter: blur(24px);
        }
        
        .transition-all {
            transition: all 0.2s ease;
        }
        
        .faq-answer {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }
        
        .faq-answer.open {
            max-height: 500px;
        }
        
        .chevron {
            transition: transform 0.3s ease;
        }
        
        .chevron.rotate {
            transform: rotate(180deg);
        }
    </style>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        background: 'rgb(var(--background))',
                        foreground: 'rgb(var(--foreground))',
                        card: 'rgb(var(--card))',
                        border: 'rgb(var(--border))',
                        muted: 'rgb(var(--muted))',
                        'muted-foreground': 'rgb(var(--muted-foreground))',
                    }
                }
            }
        }
    </script>
</head>
<body>
    <!-- Navigation -->
    <nav class="fixed top-0 w-full z-50 border-b border-border/30 bg-background/80 backdrop-blur-xl">
        <div class="max-w-6xl mx-auto px-6">
            <div class="flex items-center justify-between h-14">
                <div class="flex items-center gap-2">
                    <img src="../public/logos (6).png" alt="SafeCode Logo" class="h-8 w-8 object-contain">
                    <span class="font-medium text-sm tracking-tight">SafeCode</span>
                    <span class="text-xs text-muted-foreground/50 ml-1">by SafeNode</span>
                </div>
                <div class="hidden md:flex items-center gap-6">
                    <a href="#features" class="text-sm text-muted-foreground hover:text-foreground transition-colors">Features</a>
                    <a href="#faq" class="text-sm text-muted-foreground hover:text-foreground transition-colors">FAQ</a>
                    <a href="https://github.com" class="flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground transition-colors">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                        </svg>
                        GitHub
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="pt-32 pb-24 px-6">
        <div class="max-w-6xl mx-auto">
            <div class="grid lg:grid-cols-2 gap-16 items-center">
                <div>
                    <h1 class="text-6xl md:text-7xl font-semibold mb-6 tracking-tight leading-none">
                        Secure code,<br>
                        <span class="text-muted-foreground">ship faster</span>
                    </h1>

                    <p class="text-base text-muted-foreground/80 mb-10 leading-relaxed max-w-xl">
                        Professional security for developers. Real-time vulnerability detection integrated into your workflow.
                    </p>

                    <div class="mb-12">
                        <div class="text-xs font-medium text-muted-foreground/60 mb-3 uppercase tracking-wider">
                            Get Started
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            <a href="https://safenode.cloud/safecode" target="_blank" class="h-11 px-6 bg-foreground text-background rounded-md text-sm font-medium hover:bg-foreground/90 transition-colors inline-flex items-center gap-2">
                                Abrir IDE Online
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                </svg>
                            </a>
                            <a href="/lp/docs.html" class="h-11 px-6 border border-border/30 bg-transparent rounded-md text-sm font-medium hover:bg-muted/50 transition-colors inline-flex items-center gap-2">
                                IDE Documentation
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                </svg>
                            </a>
                            <a href="https://github.com" class="h-11 px-6 border border-border/30 bg-transparent rounded-md text-sm font-medium hover:bg-muted/50 transition-colors inline-flex items-center gap-2">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                                </svg>
                                GitHub
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Code Example Image -->
                <div class="relative">
                    <div class="rounded-lg border border-border/30 bg-card/50 overflow-hidden backdrop-blur-sm">
                        <div class="flex items-center justify-between px-4 py-2.5 border-b border-border/30 bg-background/50">
                            <span class="text-xs text-muted-foreground font-mono">security-check.ts</span>
                            <div class="flex items-center gap-1.5 text-xs text-green-500">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                <span>No issues</span>
                            </div>
                        </div>
                        <div class="p-6 font-mono text-sm leading-relaxed overflow-x-auto">
                            <pre class="text-muted-foreground/80"><span style="color: #a78bfa">import</span><span style="color: #f5f5f5"> { SecurityScanner } </span><span style="color: #a78bfa">from</span><span style="color: #4ade80"> '@safecode/core'</span>

<span style="color: #a78bfa">const</span><span style="color: #60a5fa"> scanner </span><span style="color: #d4d4d8">= </span><span style="color: #a78bfa">new</span><span style="color: #60a5fa"> SecurityScanner</span><span style="color: #d4d4d8">({</span>
<span style="color: #a1a1aa">  realtime: </span><span style="color: #fb923c">true</span><span style="color: #d4d4d8">,</span>
<span style="color: #a1a1aa">  autofix: </span><span style="color: #fb923c">true</span><span style="color: #d4d4d8">,</span>
<span style="color: #a1a1aa">  threats: [</span><span style="color: #4ade80">'xss'</span><span style="color: #d4d4d8">, </span><span style="color: #4ade80">'sql'</span><span style="color: #d4d4d8">, </span><span style="color: #4ade80">'csrf'</span><span style="color: #a1a1aa">]</span>
<span style="color: #d4d4d8">})</span>

<span style="color: #60a5fa">scanner</span><span style="color: #d4d4d8">.</span><span style="color: #fbbf24">watch</span><span style="color: #d4d4d8">(</span><span style="color: #4ade80">'./src'</span><span style="color: #d4d4d8">)</span></pre>
                        </div>
                    </div>
                    <div class="absolute -bottom-8 -right-8 w-64 h-64 bg-foreground/5 rounded-full blur-3xl -z-10"></div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="py-20 px-6">
        <div class="max-w-5xl mx-auto">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-12">
                <div>
                    <div class="text-3xl font-semibold mb-1 tracking-tight">Real-time</div>
                    <div class="text-sm text-muted-foreground/70">Detection</div>
                </div>
                <div>
                    <div class="text-3xl font-semibold mb-1 tracking-tight">500+</div>
                    <div class="text-sm text-muted-foreground/70">Security Rules</div>
                </div>
                <div>
                    <div class="text-3xl font-semibold mb-1 tracking-tight">Zero</div>
                    <div class="text-sm text-muted-foreground/70">Config</div>
                </div>
                <div>
                    <div class="text-3xl font-semibold mb-1 tracking-tight">Open</div>
                    <div class="text-sm text-muted-foreground/70">Source</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-24 px-6">
        <div class="max-w-5xl mx-auto">
            <div class="max-w-xl mb-16">
                <h2 class="text-4xl font-semibold mb-4 tracking-tight">Security-first</h2>
                <p class="text-base text-muted-foreground/70 leading-relaxed">
                    Built with security at the core. Every feature designed to help you write safer code.
                </p>
            </div>

            <div class="grid md:grid-cols-2 gap-12">
                <div>
                    <h3 class="text-base font-medium mb-2 tracking-tight">Real-time Scanning</h3>
                    <p class="text-sm text-muted-foreground/70 leading-relaxed">
                        Continuous security analysis as you type, catching vulnerabilities before production.
                    </p>
                </div>
                <div>
                    <h3 class="text-base font-medium mb-2 tracking-tight">Dependency Auditing</h3>
                    <p class="text-sm text-muted-foreground/70 leading-relaxed">
                        Automatic scanning of all dependencies for known vulnerabilities and outdated packages.
                    </p>
                </div>
                <div>
                    <h3 class="text-base font-medium mb-2 tracking-tight">Code Analysis</h3>
                    <p class="text-sm text-muted-foreground/70 leading-relaxed">
                        Deep static analysis to identify security flaws and potential attack vectors.
                    </p>
                </div>
                <div>
                    <h3 class="text-base font-medium mb-2 tracking-tight">Secret Detection</h3>
                    <p class="text-sm text-muted-foreground/70 leading-relaxed">
                        Automatically prevent accidental commits of API keys, tokens, and credentials.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section id="faq" class="py-24 px-6">
        <div class="max-w-2xl mx-auto">
            <div class="mb-12">
                <h2 class="text-4xl font-semibold mb-4 tracking-tight">FAQ</h2>
                <p class="text-base text-muted-foreground/70">Everything you need to know</p>
            </div>
            <div class="space-y-2">
                <div class="border-b border-border/20">
                    <button onclick="toggleFaq(0)" class="w-full flex items-center justify-between py-5 text-left">
                        <h3 class="text-sm font-medium pr-4">What makes SafeCode different?</h3>
                        <svg class="w-4 h-4 text-muted-foreground/50 transition-transform chevron flex-shrink-0" id="chevron-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div class="faq-answer pb-5" id="answer-0">
                        <p class="text-sm text-muted-foreground/70 leading-relaxed">
                            SafeCode is built specifically for security-conscious developers. We've integrated security scanning and vulnerability detection directly into the core experience.
                        </p>
                    </div>
                </div>

                <div class="border-b border-border/20">
                    <button onclick="toggleFaq(1)" class="w-full flex items-center justify-between py-5 text-left">
                        <h3 class="text-sm font-medium pr-4">Does it work with existing projects?</h3>
                        <svg class="w-4 h-4 text-muted-foreground/50 transition-transform chevron flex-shrink-0" id="chevron-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div class="faq-answer pb-5" id="answer-1">
                        <p class="text-sm text-muted-foreground/70 leading-relaxed">
                            Yes. SafeCode is compatible with all major programming languages and frameworks. Simply open your project and start coding.
                        </p>
                    </div>
                </div>

                <div class="border-b border-border/20">
                    <button onclick="toggleFaq(2)" class="w-full flex items-center justify-between py-5 text-left">
                        <h3 class="text-sm font-medium pr-4">Is SafeCode free?</h3>
                        <svg class="w-4 h-4 text-muted-foreground/50 transition-transform chevron flex-shrink-0" id="chevron-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div class="faq-answer pb-5" id="answer-2">
                        <p class="text-sm text-muted-foreground/70 leading-relaxed">
                            SafeCode is open source and free for individual developers. We offer enterprise plans with team management and priority support.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="border-t border-border/20 py-16 px-6">
        <div class="max-w-6xl mx-auto">
            <div class="grid md:grid-cols-4 gap-12 mb-12">
                <div class="md:col-span-2">
                    <div class="flex items-center gap-2 mb-4">
                        <img src="../public/logos (6).png" alt="SafeCode Logo" class="h-8 w-8 object-contain">
                        <span class="font-medium text-sm tracking-tight">SafeCode</span>
                    </div>
                    <p class="text-sm text-muted-foreground/60 leading-relaxed max-w-sm">
                        Professional security tools for modern developers. Built by SafeNode.
                    </p>
                </div>
                <div>
                    <div class="text-xs font-medium text-muted-foreground/60 mb-4 uppercase tracking-wider">Product</div>
                    <div class="space-y-3">
                        <a href="#features" class="block text-sm text-muted-foreground/70 hover:text-foreground transition-colors">Features</a>
                        <a href="/lp/docs.html" class="block text-sm text-muted-foreground/70 hover:text-foreground transition-colors">Documentation</a>
                        <a href="#" class="block text-sm text-muted-foreground/70 hover:text-foreground transition-colors">Pricing</a>
                    </div>
                </div>
                <div>
                    <div class="text-xs font-medium text-muted-foreground/60 mb-4 uppercase tracking-wider">Resources</div>
                    <div class="space-y-3">
                        <a href="#" class="block text-sm text-muted-foreground/70 hover:text-foreground transition-colors">GitHub</a>
                        <a href="#" class="block text-sm text-muted-foreground/70 hover:text-foreground transition-colors">Support</a>
                        <a href="#" class="block text-sm text-muted-foreground/70 hover:text-foreground transition-colors">Community</a>
                    </div>
                </div>
            </div>
            <div class="pt-8 border-t border-border/10">
                <div class="flex flex-col md:flex-row items-center justify-between gap-4 text-xs text-muted-foreground/50">
                    <div>© 2026 SafeNode. All rights reserved.</div>
                    <div class="flex items-center gap-6">
                        <a href="#" class="hover:text-foreground transition-colors">Privacy</a>
                        <a href="#" class="hover:text-foreground transition-colors">Terms</a>
                        <a href="#" class="hover:text-foreground transition-colors">Security</a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // FAQ toggle functionality
        function toggleFaq(index) {
            const answer = document.getElementById(`answer-${index}`);
            const chevron = document.getElementById(`chevron-${index}`);
            
            if (answer.classList.contains('open')) {
                answer.classList.remove('open');
                chevron.classList.remove('rotate');
            } else {
                // Close all other FAQs
                document.querySelectorAll('.faq-answer').forEach(a => a.classList.remove('open'));
                document.querySelectorAll('.chevron').forEach(c => c.classList.remove('rotate'));
                
                // Open clicked FAQ
                answer.classList.add('open');
                chevron.classList.add('rotate');
            }
        }

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>
