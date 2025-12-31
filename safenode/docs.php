<?php
/**
 * SafeNode - Documentação Pública
 * Página de documentação acessível publicamente
 */

session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/init.php';

$pageTitle = 'Documentação';
$activeTab = $_GET['tab'] ?? 'introduction';

$tabs = [
    'introduction' => ['icon' => 'book-open', 'label' => 'Introdução'],
    'integration' => ['icon' => 'plug', 'label' => 'Integração'],
    'api' => ['icon' => 'server', 'label' => 'API Endpoints'],
    'architecture' => ['icon' => 'layers', 'label' => 'Arquitetura'],
    'standards' => ['icon' => 'code', 'label' => 'Padrões de Código'],
    'database' => ['icon' => 'database', 'label' => 'Banco de Dados']
];
?>
<!DOCTYPE html>
<html lang="pt-BR" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> | SafeNode</title>
    <link rel="icon" type="image/png" href="assets/img/logos (6).png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github-dark.min.css">
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        mono: ['JetBrains Mono', 'monospace'],
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
            font-family: 'Inter', sans-serif;
            background: #000000;
            color: #e4e4e7;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        pre code {
            font-family: 'JetBrains Mono', monospace;
            font-size: 14px;
            line-height: 1.6;
        }
        
        .tab-content {
            display: none;
            animation: fadeIn 0.3s ease-in-out;
        }
        
        .tab-content.active {
            display: block;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        
        ::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.15);
        }
        
        /* Sidebar */
        .sidebar-item {
            position: relative;
            transition: all 0.2s ease;
        }
        
        .sidebar-item.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 3px;
            height: 20px;
            background: #ffffff;
            border-radius: 0 2px 2px 0;
        }
        
        
        /* Code blocks */
        pre {
            background: #0a0a0a !important;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 8px;
            padding: 20px;
            overflow-x: auto;
            margin: 24px 0;
        }
        
        code {
            background: rgba(255, 255, 255, 0.05);
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.9em;
        }
        
        pre code {
            background: transparent;
            padding: 0;
        }
        
        /* Links */
        a {
            color: #60a5fa;
            text-decoration: none;
            transition: color 0.2s ease;
        }
        
        a:hover {
            color: #93c5fd;
        }
        
        /* Table */
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        table th {
            text-align: left;
            font-weight: 600;
            color: #ffffff;
            padding: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        table td {
            padding: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            color: #a1a1aa;
        }
        
        table tr:hover {
            background: rgba(255, 255, 255, 0.02);
        }
    </style>
</head>
<body class="min-h-screen">
    
    <!-- Main Layout -->
    <div class="flex w-full">
        
        <!-- Sidebar -->
        <aside class="hidden lg:block w-64 flex-shrink-0 border-r border-zinc-900/50 bg-black/40 sticky top-0 h-screen overflow-y-auto">
            <div class="pl-0 pr-4 py-6">
                <!-- Logo -->
                <div class="mb-8 pl-6 pb-6 border-b border-zinc-900/50">
                    <a href="index.php" class="flex items-center gap-3 group">
                        <img src="assets/img/logos (6).png" alt="SafeNode" class="h-7 w-auto transition-transform group-hover:scale-105">
                        <span class="font-semibold text-lg text-white">SafeNode</span>
                    </a>
                </div>
                
                <div class="mb-8 pl-6">
                    <h1 class="text-2xl font-bold text-white mb-2">Documentação</h1>
                    <p class="text-sm text-zinc-500 leading-relaxed">
                        Guia completo da API, arquitetura e integração do SafeNode
                    </p>
                </div>
                
                <nav class="space-y-1">
                    <?php foreach ($tabs as $tabKey => $tab): ?>
                    <a 
                        href="?tab=<?php echo $tabKey; ?>"
                        class="sidebar-item flex items-center gap-3 pl-6 pr-3 py-2.5 rounded-lg text-sm font-medium transition-all <?php echo $activeTab === $tabKey ? 'active bg-white/5 text-white' : 'text-zinc-400 hover:text-white hover:bg-white/5'; ?>"
                    >
                        <i data-lucide="<?php echo $tab['icon']; ?>" class="w-4 h-4"></i>
                        <span><?php echo $tab['label']; ?></span>
                    </a>
                    <?php endforeach; ?>
                </nav>
                
                <div class="mt-8 pt-8 border-t border-zinc-900/50 pl-6 space-y-3">
                    <a href="index.php" class="flex items-center gap-2 text-sm text-zinc-400 hover:text-white transition-colors">
                        <i data-lucide="arrow-left" class="w-4 h-4"></i>
                        <span>Voltar ao Site</span>
                    </a>
                    <a href="survey.php" class="flex items-center gap-2 text-sm text-zinc-400 hover:text-white transition-colors">
                        <i data-lucide="message-square" class="w-4 h-4"></i>
                        <span>Responder Pesquisa</span>
                    </a>
                </div>
            </div>
        </aside>

        <!-- Mobile Sidebar Toggle -->
        <div class="lg:hidden fixed bottom-6 right-6 z-40">
            <button onclick="toggleMobileMenu()" class="w-14 h-14 rounded-full bg-white text-black flex items-center justify-center shadow-lg hover:scale-105 transition-transform">
                <i data-lucide="menu" id="menu-icon" class="w-6 h-6"></i>
                <i data-lucide="x" id="close-icon" class="w-6 h-6 hidden"></i>
            </button>
        </div>

        <!-- Mobile Sidebar -->
        <div id="mobile-sidebar" class="lg:hidden fixed inset-0 z-30 bg-black/80 backdrop-blur-xl hidden">
            <div class="w-80 h-full bg-black border-r border-zinc-900/50 overflow-y-auto">
                <div class="p-6">
                    <!-- Logo Mobile -->
                    <div class="mb-8 pb-6 border-b border-zinc-900/50">
                        <a href="index.php" class="flex items-center gap-3 group">
                            <img src="assets/img/logos (6).png" alt="SafeNode" class="h-7 w-auto transition-transform group-hover:scale-105">
                            <span class="font-semibold text-lg text-white">SafeNode</span>
                        </a>
                    </div>
                    
                    <div class="mb-8">
                        <h1 class="text-2xl font-bold text-white mb-2">Documentação</h1>
                    </div>
                    
                    <nav class="space-y-1">
                        <?php foreach ($tabs as $tabKey => $tab): ?>
                        <a 
                            href="?tab=<?php echo $tabKey; ?>"
                            onclick="toggleMobileMenu()"
                            class="sidebar-item flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all <?php echo $activeTab === $tabKey ? 'active bg-white/5 text-white' : 'text-zinc-400 hover:text-white hover:bg-white/5'; ?>"
                        >
                            <i data-lucide="<?php echo $tab['icon']; ?>" class="w-4 h-4"></i>
                            <span><?php echo $tab['label']; ?></span>
                        </a>
                        <?php endforeach; ?>
                    </nav>
                    
                    <div class="mt-8 pt-8 border-t border-zinc-900/50 space-y-3">
                        <a href="index.php" onclick="toggleMobileMenu()" class="flex items-center gap-2 text-sm text-zinc-400 hover:text-white transition-colors">
                            <i data-lucide="arrow-left" class="w-4 h-4"></i>
                            <span>Voltar ao Site</span>
                        </a>
                        <a href="survey.php" onclick="toggleMobileMenu()" class="flex items-center gap-2 text-sm text-zinc-400 hover:text-white transition-colors">
                            <i data-lucide="message-square" class="w-4 h-4"></i>
                            <span>Responder Pesquisa</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <main class="flex-1 min-w-0">
            <div class="max-w-4xl mx-auto px-6 py-12 lg:py-16">
                
                <!-- Tab: Introduction -->
                <div id="tab-introduction" class="tab-content <?php echo $activeTab === 'introduction' ? 'active' : ''; ?>">
                    <div class="mb-12">
                        <div class="flex items-center gap-4 mb-6">
                            <div class="w-12 h-12 rounded-xl bg-blue-600/20 border border-blue-500/30 flex items-center justify-center">
                                <i data-lucide="book-open" class="w-6 h-6 text-blue-400"></i>
                            </div>
                            <div>
                                <h2 class="text-4xl font-bold text-white mb-2">Documentação SafeNode</h2>
                                <p class="text-lg text-zinc-400">Guia completo da API e arquitetura do sistema</p>
                            </div>
                        </div>
                        
                        <p class="text-lg text-zinc-300 leading-relaxed mb-8">
                            O SafeNode é uma plataforma de segurança completa que oferece proteção em tempo real contra ameaças,
                            análise comportamental e monitoramento avançado. Esta documentação cobre todos os aspectos da API,
                            estrutura do código e como integrar o sistema.
                        </p>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
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

                    <div class="grid md:grid-cols-3 gap-6 mb-16">
                        <div class="bg-zinc-950/50 border border-zinc-900/50 rounded-xl p-6 hover:border-zinc-800 transition-colors">
                            <div class="w-12 h-12 bg-blue-500/10 rounded-lg flex items-center justify-center mb-4">
                                <i data-lucide="zap" class="w-6 h-6 text-blue-400"></i>
                            </div>
                            <h3 class="text-lg font-semibold text-white mb-2">Setup em 10 minutos</h3>
                            <p class="text-sm text-zinc-500 leading-relaxed">Script automatizado. Docker pronto. Zero configuração manual.</p>
                        </div>
                        <div class="bg-zinc-950/50 border border-zinc-900/50 rounded-xl p-6 hover:border-zinc-800 transition-colors">
                            <div class="w-12 h-12 bg-green-500/10 rounded-lg flex items-center justify-center mb-4">
                                <i data-lucide="mail" class="w-6 h-6 text-green-400"></i>
                            </div>
                            <h3 class="text-lg font-semibold text-white mb-2">API REST simples</h3>
                            <p class="text-sm text-zinc-500 leading-relaxed">Envie e-mails com uma requisição HTTP. Sem SMTP. Sem complexidade.</p>
                        </div>
                        <div class="bg-zinc-950/50 border border-zinc-900/50 rounded-xl p-6 hover:border-zinc-800 transition-colors">
                            <div class="w-12 h-12 bg-purple-500/10 rounded-lg flex items-center justify-center mb-4">
                                <i data-lucide="layers" class="w-6 h-6 text-purple-400"></i>
                            </div>
                            <h3 class="text-lg font-semibold text-white mb-2">Funciona em qualquer VPS</h3>
                            <p class="text-sm text-zinc-500 leading-relaxed">DigitalOcean, AWS, Hostinger, cPanel, Plesk... Qualquer lugar.</p>
                        </div>
                    </div>

                </div>

                <!-- Tab: Integration -->
                <div id="tab-integration" class="tab-content <?php echo $activeTab === 'integration' ? 'active' : ''; ?>">
                    <div class="mb-12">
                        <h2 class="text-4xl font-bold text-white mb-4 flex items-center gap-3">
                            <i data-lucide="plug" class="w-8 h-8 text-green-400"></i>
                            Guia de Integração
                        </h2>
                        <p class="text-lg text-zinc-400">Integre SafeNode Mail em qualquer hospedagem em 10 minutos.</p>
                    </div>

                    <div class="space-y-12">
                        <section>
                            <h3 class="text-2xl font-bold text-white mb-4">Quick Start - 10 minutos</h3>
                            <div class="space-y-6">
                                <div>
                                    <h4 class="text-lg font-semibold text-white mb-3">1. Obtenha seu token da API</h4>
                                    <ol class="space-y-2 text-zinc-300 list-decimal list-inside ml-2">
                                        <li>Acesse <a href="mail.php" class="text-blue-400 hover:underline">https://safenode.cloud/mail</a></li>
                                        <li>Faça login (ou crie uma conta grátis)</li>
                                        <li>Crie um novo projeto de e-mail</li>
                                        <li>Copie o token gerado</li>
                                    </ol>
                                </div>

                                <div>
                                    <h4 class="text-lg font-semibold text-white mb-3">2. Instale via script (Linux/Mac)</h4>
                                    <pre><code class="language-bash">curl -o setup-safenode.sh https://safenode.cloud/integration/setup-safenode.sh
sudo bash setup-safenode.sh</code></pre>
                                </div>

                                <div>
                                    <h4 class="text-lg font-semibold text-white mb-3">3. Configure variáveis de ambiente</h4>
                                    <pre><code class="language-bash">cd /opt/safenode-mail
cp .env.example .env
nano .env  # Cole seu token da API</code></pre>
                                </div>

                                <div>
                                    <h4 class="text-lg font-semibold text-white mb-3">4. Envie seu primeiro e-mail</h4>
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
                            </div>
                        </section>

                        <section>
                            <h3 class="text-2xl font-bold text-white mb-4">SDKs Disponíveis</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="p-4 rounded-xl bg-white/5 border border-white/10">
                                    <div class="flex items-center gap-3 mb-2">
                                        <div class="w-10 h-10 flex items-center justify-center bg-white/10 rounded-lg border border-white/20">
                                            <i data-lucide="code" class="w-6 h-6 text-yellow-400"></i>
                                        </div>
                                        <h4 class="font-semibold text-white">JavaScript (Browser)</h4>
                                    </div>
                                    <p class="text-xs text-zinc-400 mb-2">Para sites web</p>
                                    <code class="text-xs text-zinc-500">sdk/safenode-hv.js</code>
                                </div>
                                
                                <div class="p-4 rounded-xl bg-white/5 border border-white/10">
                                    <div class="flex items-center gap-3 mb-2">
                                        <div class="w-10 h-10 flex items-center justify-center bg-white/10 rounded-lg border border-white/20">
                                            <i data-lucide="code" class="w-6 h-6 text-blue-400"></i>
                                        </div>
                                        <h4 class="font-semibold text-white">PHP</h4>
                                    </div>
                                    <p class="text-xs text-zinc-400 mb-2">Para aplicações PHP</p>
                                    <code class="text-xs text-zinc-500">sdk/php/SafeNodeHV.php</code>
                                </div>
                                
                                <div class="p-4 rounded-xl bg-white/5 border border-white/10">
                                    <div class="flex items-center gap-3 mb-2">
                                        <div class="w-10 h-10 flex items-center justify-center bg-white/10 rounded-lg border border-white/20">
                                            <i data-lucide="code" class="w-6 h-6 text-green-400"></i>
                                        </div>
                                        <h4 class="font-semibold text-white">Python</h4>
                                    </div>
                                    <p class="text-xs text-zinc-400 mb-2">Para aplicações Python</p>
                                    <code class="text-xs text-zinc-500">sdk/python/safenode_hv.py</code>
                                </div>
                                
                                <div class="p-4 rounded-xl bg-white/5 border border-white/10">
                                    <div class="flex items-center gap-3 mb-2">
                                        <div class="w-10 h-10 flex items-center justify-center bg-white/10 rounded-lg border border-white/20">
                                            <i data-lucide="code" class="w-6 h-6 text-green-500"></i>
                                        </div>
                                        <h4 class="font-semibold text-white">Node.js</h4>
                                    </div>
                                    <p class="text-xs text-zinc-400 mb-2">Para aplicações Node.js</p>
                                    <code class="text-xs text-zinc-500">sdk/nodejs/safenode-hv.js</code>
                                </div>
                            </div>
                        </section>

                        <section>
                            <h3 class="text-2xl font-bold text-white mb-4">Exemplos de Integração</h3>
                            <div class="space-y-6">
                                <div>
                                    <h4 class="text-lg font-semibold text-white mb-3">JavaScript (Browser)</h4>
                                    <pre><code class="language-html">&lt;script src="https://safenode.cloud/sdk/safenode-hv.js"&gt;&lt;/script&gt;
&lt;script&gt;
const safenode = new SafeNodeHV('https://safenode.cloud/api/sdk', 'sua-api-key-aqui');

safenode.init().then(() => {
  console.log('SafeNode inicializado');
});

document.getElementById('meuFormulario').addEventListener('submit', async (e) => {
  e.preventDefault();
  const result = await safenode.validate();
  if (result.valid) {
    e.target.submit();
  }
});
&lt;/script&gt;</code></pre>
                                </div>

                                <div>
                                    <h4 class="text-lg font-semibold text-white mb-3">PHP</h4>
                                    <pre><code class="language-php">&lt;?php
require_once 'sdk/php/SafeNodeHV.php';

$safenode = new SafeNodeHV('https://safenode.cloud/api/sdk', 'sua-api-key-aqui');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $safenode->init();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $safenode->validate();
    if ($result['valid']) {
        // Processar formulário
    }
}
?&gt;</code></pre>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>

                <!-- Tab: API Endpoints -->
                <div id="tab-api" class="tab-content <?php echo $activeTab === 'api' ? 'active' : ''; ?>">

                <!-- CTA -->
                <div class="mt-20 pt-12 border-t border-zinc-900/50">
                    <div class="bg-zinc-950/50 border border-zinc-900/50 rounded-2xl p-8 text-center">
                        <h2 class="text-2xl font-bold text-white mb-3">Pronto para começar?</h2>
                        <p class="text-zinc-400 mb-6">Crie sua conta grátis e tenha e-mails funcionando em 10 minutos.</p>
                        <div class="flex flex-col sm:flex-row gap-4 justify-center">
                            <a href="register.php" class="px-6 py-3 bg-white text-black rounded-lg font-semibold hover:bg-zinc-100 transition-all">
                                Criar Conta Grátis
                            </a>
                            <a href="survey.php" class="px-6 py-3 bg-zinc-900 border border-zinc-800 text-white rounded-lg font-semibold hover:bg-zinc-800 transition-all">
                                Responder Pesquisa
                            </a>
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

        function toggleMobileMenu() {
            const sidebar = document.getElementById('mobile-sidebar');
            const menuIcon = document.getElementById('menu-icon');
            const closeIcon = document.getElementById('close-icon');
            
            if (sidebar.classList.contains('hidden')) {
                sidebar.classList.remove('hidden');
                menuIcon.classList.add('hidden');
                closeIcon.classList.remove('hidden');
            } else {
                sidebar.classList.add('hidden');
                menuIcon.classList.remove('hidden');
                closeIcon.classList.add('hidden');
            }
        }

        // Close mobile menu when clicking outside
        document.getElementById('mobile-sidebar')?.addEventListener('click', function(e) {
            if (e.target === this) {
                toggleMobileMenu();
            }
        });
    </script>

</body>
</html>
