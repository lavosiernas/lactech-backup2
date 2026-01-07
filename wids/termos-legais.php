<!DOCTYPE html>
<html lang="pt-BR" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Termos Legais - Wide Style</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" href="https://i.postimg.cc/CKWyJ3tH/uchoas-2.png" type="image/x-icon">
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        dark: '#0A0A0A',
                        darkgray: '#121212',
                        lightgray: '#A0A0A0',
                    },
                    fontFamily: {
                        sans: ['Montserrat', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        main {
            flex: 1;
        }
        footer {
            margin-top: auto;
        }
        .card-hover:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body class="bg-white dark:bg-black text-black dark:text-white font-sans">
    <!-- Remover o banner de promoção -->
    
    <!-- Header -->
    <header class="fixed top-0 left-0 w-full bg-white/95 dark:bg-black/95 backdrop-blur-md z-50 transition-colors duration-300 border-b border-gray-200 dark:border-gray-800">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center h-20">
                <div class="flex items-center">
                    <a href="/" class="flex items-center mr-10">
                        <img src="https://i.postimg.cc/tgYF14JZ/WIDE-STYLE-SITE-BRANCO.png" alt="WIDE STYLE" class="h-14 dark:hidden">
                        <img src="https://i.postimg.cc/QCMM1Byn/WIDE-STYLE-SITE.png" alt="WIDE STYLE" class="h-14 hidden dark:block">
                    </a>
                </div>
                
                <div class="hidden md:flex items-center space-x-8">
                    <a href="/" class="hover:text-gray-300 transition">Home</a>
                    <a href="/#products" class="hover:text-gray-300 transition">Produtos</a>
                    <a href="/#collections" class="hover:text-gray-300 transition">Coleções</a>
                    <a href="/#about" class="hover:text-gray-300 transition">Sobre</a>
                </div>
                
                <div class="flex items-center space-x-6">
                    <div class="flex items-center space-x-4">
                        <a href="javascript:history.back()" class="hidden md:block text-black dark:text-white hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Conteúdo Principal - Adicionar margem superior para compensar o header fixo -->
    <main class="container mx-auto pt-32 pb-16 px-4">
        <!-- Título da página -->
        <div class="mb-12 text-center">
            <h1 class="text-3xl md:text-5xl font-bold mb-4">Termos Legais</h1>
            <p class="text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">Aqui você encontra todos os documentos legais relacionados ao uso do nosso site e serviços. Temos o compromisso com a transparência e proteção dos seus direitos.</p>
        </div>
        
        <!-- Cards de documentos legais -->
        <div class="max-w-5xl mx-auto grid grid-cols-1 md:grid-cols-2 gap-8 mb-16">
            <!-- Card Termos de Uso -->
            <a href="termos.html" class="card-hover block bg-gray-100 dark:bg-zinc-900 rounded-xl shadow p-6 transition-transform duration-300">
                <div class="flex items-center space-x-4 mb-4">
                    <div class="bg-blue-100 dark:bg-blue-900/30 p-3 rounded-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <h2 class="text-xl font-bold">Termos de Uso</h2>
                </div>
                <p class="text-gray-600 dark:text-gray-400 mb-4">Este documento estabelece as regras para uso do nosso site, responsabilidades dos usuários, e outras condições importantes para a navegação e compras.</p>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-500 dark:text-gray-500">Atualizado: Julho 2024</span>
                    <span class="text-blue-500 font-medium flex items-center">
                        Ler mais
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </span>
                </div>
            </a>
            
            <!-- Card Política de Privacidade -->
            <a href="privacidade.html" class="card-hover block bg-gray-100 dark:bg-zinc-900 rounded-xl shadow p-6 transition-transform duration-300">
                <div class="flex items-center space-x-4 mb-4">
                    <div class="bg-green-100 dark:bg-green-900/30 p-3 rounded-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <h2 class="text-xl font-bold">Política de Privacidade</h2>
                </div>
                <p class="text-gray-600 dark:text-gray-400 mb-4">Explica como coletamos, usamos e protegemos suas informações pessoais, bem como seus direitos relacionados aos seus dados.</p>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-500 dark:text-gray-500">Atualizado: Julho 2024</span>
                    <span class="text-green-500 font-medium flex items-center">
                        Ler mais
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </span>
                </div>
            </a>
            
            <!-- Card Política de Cookies -->
            <a href="termos-legais.html#politica-cookies" class="card-hover block bg-gray-100 dark:bg-zinc-900 rounded-xl shadow p-6 transition-transform duration-300 hover:opacity-100">
                <div class="flex items-center space-x-4 mb-4">
                    <div class="bg-purple-100 dark:bg-purple-900/30 p-3 rounded-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                        </svg>
                    </div>
                    <h2 class="text-xl font-bold">Política de Cookies</h2>
                </div>
                <p class="text-gray-600 dark:text-gray-400 mb-4">Detalha como utilizamos cookies e tecnologias semelhantes em nosso site, incluindo opções para gerenciá-los.</p>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-500 dark:text-gray-500">Atualizado: Julho 2024</span>
                    <span class="text-purple-500 font-medium flex items-center">
                        Ler mais
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </span>
                </div>
            </a>
            
            <!-- Card Política de Trocas e Devoluções -->
            <a href="#" class="card-hover block bg-gray-100 dark:bg-zinc-900 rounded-xl shadow p-6 transition-transform duration-300 opacity-60 hover:opacity-100">
                <div class="flex items-center space-x-4 mb-4">
                    <div class="bg-orange-100 dark:bg-orange-900/30 p-3 rounded-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3m9 14V5a2 2 0 00-2-2H6a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z" />
                        </svg>
                    </div>
                    <h2 class="text-xl font-bold">Política de Trocas e Devoluções</h2>
                </div>
                <p class="text-gray-600 dark:text-gray-400 mb-4">Estabelece as diretrizes para troca ou devolução de produtos, incluindo prazos, condições e procedimentos.</p>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-500 dark:text-gray-500">Em breve</span>
                    <span class="text-orange-500 font-medium flex items-center">
                        Em desenvolvimento
                    </span>
                </div>
            </a>
        </div>
        
        <!-- Seção de perguntas frequentes -->
        <div class="max-w-4xl mx-auto bg-gray-100 dark:bg-zinc-900 rounded-xl shadow p-8 mb-8">
            <h2 class="text-2xl font-bold mb-6 text-center">Perguntas Frequentes</h2>
            
            <div class="space-y-6">
                <div>
                    <h3 class="text-lg font-semibold mb-2">Como posso solicitar a exclusão dos meus dados?</h3>
                    <p class="text-gray-600 dark:text-gray-400">Você pode solicitar a exclusão de seus dados pessoais enviando um e-mail para privacidade@widestyle.com.br com o assunto "Solicitação de Exclusão de Dados". Processaremos sua solicitação em até 15 dias úteis.</p>
                </div>
                
                <div>
                    <h3 class="text-lg font-semibold mb-2">Quais são meus direitos em relação aos meus dados pessoais?</h3>
                    <p class="text-gray-600 dark:text-gray-400">De acordo com a LGPD (Lei Geral de Proteção de Dados), você tem direito a acessar, corrigir, portar, deletar seus dados, além de ser informado sobre como eles são utilizados. Para mais detalhes, consulte nossa Política de Privacidade.</p>
                </div>
                
                <div>
                    <h3 class="text-lg font-semibold mb-2">Quanto tempo vocês mantêm minhas informações?</h3>
                    <p class="text-gray-600 dark:text-gray-400">Mantemos suas informações pessoais pelo tempo necessário para cumprir as finalidades para as quais foram coletadas, incluindo obrigações legais, contábeis ou de relatórios. Detalhes específicos estão em nossa Política de Privacidade.</p>
                </div>
            </div>
            
            <!-- Botão Voltar -->
            <div class="mt-10 pt-6 border-t border-gray-200 dark:border-zinc-800">
                <a href="javascript:history.back()" class="inline-flex items-center text-blue-500 hover:text-blue-700 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Voltar para a loja
                </a>
            </div>
        </div>
    </main>

    <!-- Seção de Política de Cookies -->
    <div id="politica-cookies" class="container mx-auto px-4 mb-16 max-w-4xl">
        <div class="bg-gray-100 dark:bg-zinc-900 rounded-xl shadow p-8">
            <h2 class="text-2xl font-bold mb-6 text-center">Política de Cookies</h2>
            
            <div class="prose prose-lg dark:prose-invert max-w-none">
                <p class="mb-4">Última atualização: Julho 2024</p>
                
                <h3 class="text-xl font-semibold mt-6 mb-3">1. O que são cookies?</h3>
                <p>Cookies são pequenos arquivos de texto que são armazenados no seu dispositivo (computador, tablet ou celular) quando você navega em websites. Eles permitem que o site reconheça seu dispositivo e lembre suas preferências e ações durante um período determinado.</p>
                
                <h3 class="text-xl font-semibold mt-6 mb-3">2. Como utilizamos cookies</h3>
                <p>A Wide Style utiliza cookies para diversas finalidades, incluindo:</p>
                <ul class="list-disc pl-6 mb-4 space-y-2">
                    <li>Funcionamento essencial do site: cookies necessários para o funcionamento básico do website;</li>
                    <li>Personalização: para lembrar suas preferências e escolhas, como o idioma ou a região;</li>
                    <li>Experiência do usuário: para melhorar a sua experiência de navegação;</li>
                    <li>Analytics: para entender como os visitantes interagem com nosso site;</li>
                    <li>Marketing: para fornecer conteúdo personalizado e relevante com base em seus interesses.</li>
                </ul>
                
                <h3 class="text-xl font-semibold mt-6 mb-3">3. Tipos de cookies que utilizamos</h3>
                
                <h4 class="text-lg font-medium mt-4 mb-2">3.1. Cookies essenciais</h4>
                <p>Estes cookies são necessários para o funcionamento do site e não podem ser desativados. Geralmente, eles são definidos apenas em resposta a ações feitas por você, como definir suas preferências de privacidade, fazer login ou preencher formulários.</p>
                
                <h4 class="text-lg font-medium mt-4 mb-2">3.2. Cookies de preferência</h4>
                <p>Estes cookies permitem que o site forneça funcionalidades e personalização aprimoradas, como lembrar suas preferências de tema claro/escuro e configurações de conta.</p>
                
                <h4 class="text-lg font-medium mt-4 mb-2">3.3. Cookies analíticos</h4>
                <p>Utilizamos cookies analíticos, como os fornecidos pelo Google Analytics, para compreender como os visitantes interagem com o site. Eles ajudam a melhorar o desempenho e a usabilidade do site.</p>
                
                <h4 class="text-lg font-medium mt-4 mb-2">3.4. Cookies de marketing</h4>
                <p>Estes cookies são usados para rastrear visitantes em websites. A intenção é exibir anúncios relevantes e envolventes para o usuário individual.</p>
                
                <h3 class="text-xl font-semibold mt-6 mb-3">4. Como gerenciar cookies</h3>
                <p>A maioria dos navegadores permite controle sobre cookies através das configurações de preferências. Você pode:</p>
                <ul class="list-disc pl-6 mb-4 space-y-2">
                    <li>Excluir cookies existentes;</li>
                    <li>Bloquear cookies;</li>
                    <li>Permitir cookies de sites específicos.</li>
                </ul>
                <p>Observe que restringir cookies pode impactar sua experiência no nosso site, impedindo que certas funcionalidades operem corretamente.</p>
                
                <h3 class="text-xl font-semibold mt-6 mb-3">5. Política de consentimento</h3>
                <p>Quando você visita nosso site pela primeira vez, solicitamos seu consentimento para utilizar cookies não essenciais. Você pode alterar suas preferências a qualquer momento utilizando nosso banner de cookies ou entrando em contato conosco.</p>
                
                <h3 class="text-xl font-semibold mt-6 mb-3">6. Atualizações da política</h3>
                <p>Podemos atualizar esta Política de Cookies periodicamente para refletir, por exemplo, alterações nos cookies que utilizamos ou por outros motivos operacionais, legais ou regulatórios. Recomendamos que você visite regularmente esta página para se manter informado sobre o uso de cookies.</p>
                
                <h3 class="text-xl font-semibold mt-6 mb-3">7. Contato</h3>
                <p>Se você tiver dúvidas sobre como utilizamos cookies, entre em contato conosco pelo e-mail: <a href="mailto:privacidade@widestyle.com.br" class="text-blue-500 dark:text-blue-400 hover:underline">privacidade@widestyle.com.br</a>.</p>
            </div>
            
            <!-- Botão Voltar ao Topo -->
            <div class="mt-10 pt-6 border-t border-gray-200 dark:border-zinc-800 flex justify-between">
                <a href="javascript:history.back()" class="inline-flex items-center text-blue-500 hover:text-blue-700 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Voltar para termos legais
                </a>
                <a href="#" class="inline-flex items-center text-blue-500 hover:text-blue-700 transition-colors">
                    Voltar ao topo
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                    </svg>
                </a>
            </div>
        </div>
    </div>

    <!-- Rodapé -->
    <footer class="bg-gray-100 dark:bg-zinc-950 py-12 mt-auto">
        <div class="container mx-auto px-4">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="mb-6 md:mb-0">
                    <img src="https://i.postimg.cc/tgYF14JZ/WIDE-STYLE-SITE-BRANCO.png" alt="WIDE STYLE" class="h-10 dark:hidden">
                    <img src="https://i.postimg.cc/QCMM1Byn/WIDE-STYLE-SITE.png" alt="WIDE STYLE" class="h-10 hidden dark:block">
                </div>
                
                <div class="flex flex-wrap gap-x-8 gap-y-4 mb-6 md:mb-0 justify-center">
                    <a href="/" class="text-gray-600 dark:text-gray-400 hover:text-black dark:hover:text-white transition-colors">Home</a>
                    <a href="/produtos" class="text-gray-600 dark:text-gray-400 hover:text-black dark:hover:text-white transition-colors">Produtos</a>
                    <a href="/rastreio-wide-style.php" class="text-gray-600 dark:text-gray-400 hover:text-black dark:hover:text-white transition-colors">Rastreio</a>
                    <a href="/contato" class="text-gray-600 dark:text-gray-400 hover:text-black dark:hover:text-white transition-colors">Contato</a>
                </div>
                
                <div class="flex space-x-4">
                    <a href="#" class="text-gray-600 dark:text-gray-400 hover:text-black dark:hover:text-white transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                        </svg>
                    </a>
                    <a href="#" class="text-gray-600 dark:text-gray-400 hover:text-black dark:hover:text-white transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M22.675 0h-21.35c-.732 0-1.325.593-1.325 1.325v21.351c0 .731.593 1.324 1.325 1.324h11.495v-9.294h-3.128v-3.622h3.128v-2.671c0-3.1 1.893-4.788 4.659-4.788 1.325 0 2.463.099 2.795.143v3.24l-1.918.001c-1.504 0-1.795.715-1.795 1.763v2.313h3.587l-.467 3.622h-3.12v9.293h6.116c.73 0 1.323-.593 1.323-1.325v-21.35c0-.732-.593-1.325-1.325-1.325z"/>
                        </svg>
                    </a>
                </div>
            </div>
            
            <hr class="border-gray-200 dark:border-zinc-800 my-8">
            
            <div class="text-center">
                <p class="text-gray-600 dark:text-gray-400 text-sm mb-4">© 2024 Wide Style. Todos os direitos reservados.</p>
                <div class="flex justify-center space-x-6">
                    <a href="/termos-legais.html" class="text-gray-600 dark:text-gray-400 text-xs hover:text-black dark:hover:text-white transition-colors">Termos Legais</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Script para alternar modo escuro (opcional) -->
    <script>
        // Forçar modo escuro por padrão
        localStorage.setItem('darkMode', 'true');
        document.documentElement.classList.add('dark');
    </script>
</body>
</html> 