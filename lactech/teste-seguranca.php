<!DOCTYPE html>
<html lang="pt-BR" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Segurança do Sistema - LacTech</title>
    <meta name="description" content="Conheça as medidas de segurança implementadas no LacTech para proteger seus dados e garantir a privacidade das informações da sua fazenda.">
    <link rel="icon" href="./assets/img/lactech-logo.png" type="image/png">
    <link rel="apple-touch-icon" href="./assets/img/lactech-logo.png">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <style>
        * {
            font-family: 'Inter', sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        body {
            background: #ffffff;
            color: #1a1a1a;
        }
        
        .security-card {
            transition: all 0.3s ease;
        }
        
        .security-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="bg-white shadow-sm sticky top-0 z-50">
        <nav class="max-w-7xl mx-auto px-6 py-4">
            <div class="flex items-center justify-between">
                <a href="index.php" class="flex items-center space-x-3">
                    <img src="./assets/img/lactech-logo.png" alt="LacTech Logo" class="w-10 h-10">
                    <span class="text-xl font-bold text-gray-900">LacTech</span>
                </a>
                <a href="index.php" class="text-gray-600 hover:text-gray-900 transition-colors font-medium">
                    Voltar
                </a>
            </div>
        </nav>
    </header>

    <!-- Hero Section -->
    <section class="gradient-bg text-white py-20">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center">
                <div class="inline-block bg-white/20 rounded-full px-6 py-2 mb-6">
                    <span class="text-sm font-semibold">🔒 Segurança em Primeiro Lugar</span>
                </div>
                <h1 class="text-5xl md:text-6xl font-bold mb-6">
                    Segurança do Sistema LacTech
                </h1>
                <p class="text-xl md:text-2xl text-white/90 max-w-3xl mx-auto">
                    Seus dados protegidos com as melhores tecnologias e práticas de segurança da indústria
                </p>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-6 py-16">
        
        <!-- Cloudflare Protection -->
        <section class="mb-20">
            <div class="text-center mb-12">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-green-100 rounded-2xl mb-6">
                    <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                </div>
                <h2 class="text-4xl font-bold text-gray-900 mb-4">Proteção Cloudflare</h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    O LacTech utiliza Cloudflare, uma das maiores redes de segurança e performance do mundo, 
                    para proteger seu sistema e dados contra ameaças digitais.
                </p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <div class="security-card bg-white border border-gray-200 rounded-2xl p-8 shadow-lg">
                    <div class="w-14 h-14 bg-blue-100 rounded-xl flex items-center justify-center mb-6">
                        <svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Proteção DDoS</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Ataques de negação de serviço são bloqueados automaticamente pela infraestrutura 
                        global do Cloudflare, garantindo que seu sistema permaneça sempre acessível.
                    </p>
                </div>

                <div class="security-card bg-white border border-gray-200 rounded-2xl p-8 shadow-lg">
                    <div class="w-14 h-14 bg-green-100 rounded-xl flex items-center justify-center mb-6">
                        <svg class="w-7 h-7 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">SSL/TLS Automático</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Todas as conexões são criptografadas com certificados SSL/TLS de alta qualidade, 
                        garantindo que suas informações sejam transmitidas de forma segura.
                    </p>
                </div>

                <div class="security-card bg-white border border-gray-200 rounded-2xl p-8 shadow-lg">
                    <div class="w-14 h-14 bg-purple-100 rounded-xl flex items-center justify-center mb-6">
                        <svg class="w-7 h-7 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Firewall Web</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Firewall inteligente que analisa e bloqueia automaticamente tráfego malicioso, 
                        protegendo contra tentativas de invasão e exploração de vulnerabilidades.
                    </p>
                </div>
            </div>
        </section>

        <!-- Security Features -->
        <section class="mb-20">
            <div class="text-center mb-12">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">Recursos de Segurança</h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    Múltiplas camadas de proteção para garantir a segurança e integridade dos seus dados
                </p>
            </div>

            <div class="grid md:grid-cols-2 gap-8">
                <div class="security-card bg-white border border-gray-200 rounded-2xl p-8 shadow-lg">
                    <div class="flex items-start space-x-4">
                        <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 mb-2">Criptografia de Dados</h3>
                            <p class="text-gray-600 leading-relaxed">
                                Todos os dados sensíveis são criptografados tanto em trânsito quanto em repouso, 
                                utilizando padrões de criptografia avançados aceitos pela indústria.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="security-card bg-white border border-gray-200 rounded-2xl p-8 shadow-lg">
                    <div class="flex items-start space-x-4">
                        <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 mb-2">Backup Automático</h3>
                            <p class="text-gray-600 leading-relaxed">
                                Seus dados são copiados automaticamente em intervalos regulares, garantindo que 
                                você nunca perca informações importantes, mesmo em caso de falhas inesperadas.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="security-card bg-white border border-gray-200 rounded-2xl p-8 shadow-lg">
                    <div class="flex items-start space-x-4">
                        <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 mb-2">Controle de Acesso</h3>
                            <p class="text-gray-600 leading-relaxed">
                                Sistema de autenticação robusto com diferentes níveis de permissão, garantindo 
                                que apenas pessoas autorizadas tenham acesso às informações da fazenda.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="security-card bg-white border border-gray-200 rounded-2xl p-8 shadow-lg">
                    <div class="flex items-start space-x-4">
                        <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 mb-2">Monitoramento 24/7</h3>
                            <p class="text-gray-600 leading-relaxed">
                                Sistema monitorado continuamente para detectar e responder rapidamente a qualquer 
                                tentativa de acesso não autorizado ou atividade suspeita.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="security-card bg-white border border-gray-200 rounded-2xl p-8 shadow-lg">
                    <div class="flex items-start space-x-4">
                        <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 mb-2">Proteção contra Malware</h3>
                            <p class="text-gray-600 leading-relaxed">
                                Varreduras automáticas e contínuas para detectar e bloquear malware, vírus e 
                                outros tipos de ameaças digitais antes que possam causar danos.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="security-card bg-white border border-gray-200 rounded-2xl p-8 shadow-lg">
                    <div class="flex items-start space-x-4">
                        <div class="w-12 h-12 bg-teal-100 rounded-xl flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 mb-2">Logs de Auditoria</h3>
                            <p class="text-gray-600 leading-relaxed">
                                Registro completo de todas as ações realizadas no sistema, permitindo rastreabilidade 
                                e auditoria de todas as atividades para fins de segurança e conformidade.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Cloudflare Benefits -->
        <section class="bg-gradient-to-br from-gray-50 to-white rounded-3xl p-12 mb-20">
            <div class="text-center mb-12">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">Por que Cloudflare?</h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    Cloudflare é uma das maiores e mais confiáveis redes de segurança e performance do mundo, 
                    protegendo milhões de sites e aplicações em todo o planeta.
                </p>
            </div>

            <div class="grid md:grid-cols-2 gap-8 max-w-4xl mx-auto">
                <div class="bg-white rounded-2xl p-6 shadow-md">
                    <div class="flex items-center space-x-3 mb-4">
                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900">Rede Global</h3>
                    </div>
                    <p class="text-gray-600 leading-relaxed">
                        Mais de 200 data centers em todo o mundo, garantindo que seu sistema esteja sempre 
                        próximo aos usuários e protegido em tempo real.
                    </p>
                </div>

                <div class="bg-white rounded-2xl p-6 shadow-md">
                    <div class="flex items-center space-x-3 mb-4">
                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900">Performance Otimizada</h3>
                    </div>
                    <p class="text-gray-600 leading-relaxed">
                        Aceleração automática do conteúdo e otimizações avançadas garantem que seu sistema 
                        seja rápido e responsivo, independente da localização do usuário.
                    </p>
                </div>

                <div class="bg-white rounded-2xl p-6 shadow-md">
                    <div class="flex items-center space-x-3 mb-4">
                        <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900">Confiança Mundial</h3>
                    </div>
                    <p class="text-gray-600 leading-relaxed">
                        Utilizado por empresas Fortune 500, governos e milhões de organizações em todo o mundo, 
                        demonstrando a confiança e a qualidade da plataforma.
                    </p>
                </div>

                <div class="bg-white rounded-2xl p-6 shadow-md">
                    <div class="flex items-center space-x-3 mb-4">
                        <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900">Atualizações Automáticas</h3>
                    </div>
                    <p class="text-gray-600 leading-relaxed">
                        Proteções de segurança são atualizadas automaticamente, garantindo que você esteja sempre 
                        protegido contra as ameaças mais recentes, sem necessidade de intervenção manual.
                    </p>
                </div>
            </div>
        </section>

        <!-- Privacy Commitment -->
        <section class="text-center mb-20">
            <div class="max-w-4xl mx-auto bg-white border border-gray-200 rounded-3xl p-12 shadow-lg">
                <div class="w-20 h-20 bg-green-100 rounded-2xl flex items-center justify-center mx-auto mb-6">
                    <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                </div>
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Nossa Promessa de Segurança</h2>
                <p class="text-lg text-gray-600 leading-relaxed mb-6">
                    No LacTech, a segurança dos seus dados é nossa prioridade absoluta. Utilizamos as melhores 
                    tecnologias disponíveis, incluindo proteção Cloudflare, para garantir que suas informações 
                    estejam sempre protegidas. Seus dados nunca são compartilhados com terceiros e são mantidos 
                    com os mais altos padrões de segurança e privacidade.
                </p>
                <div class="flex flex-wrap justify-center gap-4">
                    <span class="px-4 py-2 bg-green-100 text-green-700 rounded-full text-sm font-semibold">
                        ✓ Dados Criptografados
                    </span>
                    <span class="px-4 py-2 bg-blue-100 text-blue-700 rounded-full text-sm font-semibold">
                        ✓ Backup Automático
                    </span>
                    <span class="px-4 py-2 bg-purple-100 text-purple-700 rounded-full text-sm font-semibold">
                        ✓ Proteção 24/7
                    </span>
                    <span class="px-4 py-2 bg-orange-100 text-orange-700 rounded-full text-sm font-semibold">
                        ✓ Privacidade Garantida
                    </span>
                </div>
            </div>
        </section>

    </main>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center">
                <p class="text-gray-400 mb-4">
                    © <?php echo date('Y'); ?> LacTech. Todos os direitos reservados.
                </p>
                <div class="flex justify-center space-x-6">
                    <a href="index.php" class="text-gray-400 hover:text-white transition-colors">
                        Início
                    </a>
                    <a href="politica-privacidade.php" class="text-gray-400 hover:text-white transition-colors">
                        Política de Privacidade
                    </a>
                    <a href="termos-condicoes.php" class="text-gray-400 hover:text-white transition-colors">
                        Termos e Condições
                    </a>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>