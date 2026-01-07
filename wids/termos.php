<!DOCTYPE html>
<html lang="pt-BR" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Termos de Uso - Wide Style</title>
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
        <div class="mb-10 text-center">
            <h1 class="text-3xl md:text-4xl font-bold mb-3">Termos de Uso</h1>
            <p class="text-gray-600 dark:text-gray-400">Última atualização: Julho de 2024</p>
        </div>
        
        <!-- Conteúdo dos Termos de Uso -->
        <div class="max-w-4xl mx-auto bg-gray-100 dark:bg-zinc-900 rounded-xl shadow p-8 mb-8">
            <div class="prose prose-lg dark:prose-invert max-w-none">
                
                <p class="mb-6">Bem-vindo à Wide Style. Ao acessar e usar nosso site, você concorda com os seguintes termos e condições. Por favor, leia-os atentamente.</p>
                
                <h2 class="text-xl font-bold mt-8 mb-4">1. Aceitação dos Termos</h2>
                <p>Ao acessar e usar o site Wide Style, você concorda em cumprir e estar sujeito a estes Termos de Uso. Se você não concordar com qualquer parte destes termos, por favor, não use nosso site.</p>
                
                <h2 class="text-xl font-bold mt-8 mb-4">2. Alterações nos Termos</h2>
                <p>Reservamo-nos o direito de modificar estes termos a qualquer momento. Alterações entrarão em vigor assim que forem publicadas no site. Seu uso contínuo do site após a publicação de mudanças constitui sua aceitação dessas mudanças.</p>
                
                <h2 class="text-xl font-bold mt-8 mb-4">3. Uso do Site</h2>
                <p>Ao usar nosso site, você concorda em:</p>
                <ul class="list-disc pl-6 mb-4 space-y-2">
                    <li>Não violar quaisquer leis aplicáveis</li>
                    <li>Não distribuir vírus ou outros códigos maliciosos</li>
                    <li>Não tentar acessar áreas restritas do site</li>
                    <li>Não interferir no funcionamento adequado do site</li>
                    <li>Fornecer informações precisas e atualizadas em formulários de compra</li>
                </ul>
                
                <h2 class="text-xl font-bold mt-8 mb-4">4. Contas de Usuário</h2>
                <p>Para fazer compras em nosso site, você pode precisar criar uma conta. Você é responsável por manter a confidencialidade de sua senha e por todas as atividades que ocorrem em sua conta. Notifique-nos imediatamente sobre qualquer uso não autorizado de sua conta.</p>
                
                <h2 class="text-xl font-bold mt-8 mb-4">5. Produtos e Preços</h2>
                <p>Nos esforçamos para exibir descrições precisas de produtos e preços atualizados. No entanto, reservamo-nos o direito de:</p>
                <ul class="list-disc pl-6 mb-4 space-y-2">
                    <li>Modificar ou descontinuar produtos sem aviso prévio</li>
                    <li>Limitar quantidades de produtos disponíveis para compra</li>
                    <li>Corrigir erros de preços ou descrições de produtos</li>
                    <li>Recusar ou cancelar pedidos em casos de erro de preço ou indisponibilidade</li>
                </ul>
                
                <h2 class="text-xl font-bold mt-8 mb-4">6. Pedidos e Pagamentos</h2>
                <p>Ao fazer um pedido, você concorda em fornecer informações de pagamento válidas e atualizadas. Todos os pagamentos são processados de forma segura por nossos parceiros de pagamento. Reservamo-nos o direito de recusar ou cancelar um pedido por qualquer motivo, incluindo suspeita de fraude.</p>
                
                <h2 class="text-xl font-bold mt-8 mb-4">7. Envio e Entrega</h2>
                <p>Os prazos de entrega são estimados e podem variar dependendo da sua localização e disponibilidade de produtos. Não nos responsabilizamos por atrasos causados por serviços de terceiros. Taxas de envio e detalhes serão apresentados no checkout.</p>
                
                <h2 class="text-xl font-bold mt-8 mb-4">8. Política de Devolução</h2>
                <p>Se você não estiver satisfeito com sua compra, pode devolvê-la dentro de 30 dias após o recebimento. Os produtos devem estar em sua condição original, não usados, com etiquetas e embalagem original. Consulte nossa política de devolução completa para mais detalhes.</p>
                
                <h2 class="text-xl font-bold mt-8 mb-4">9. Propriedade Intelectual</h2>
                <p>Todo o conteúdo do site, incluindo textos, gráficos, logos, imagens, e software é propriedade da Wide Style e está protegido por leis de direitos autorais. Você não pode reproduzir, distribuir, modificar ou criar trabalhos derivados baseados nesse conteúdo sem nossa permissão expressa por escrito.</p>
                
                <h2 class="text-xl font-bold mt-8 mb-4">10. Links para Terceiros</h2>
                <p>Nosso site pode conter links para sites de terceiros que não são de nossa propriedade ou controle. Não somos responsáveis pelo conteúdo ou práticas de privacidade desses sites. O acesso a sites de terceiros é por sua própria conta e risco.</p>
                
                <h2 class="text-xl font-bold mt-8 mb-4">11. Limitação de Responsabilidade</h2>
                <p>A Wide Style não será responsável por quaisquer danos diretos, indiretos, incidentais, consequenciais ou punitivos decorrentes do uso ou incapacidade de usar nosso site ou produtos. O uso do site é por sua própria conta e risco.</p>
                
                <h2 class="text-xl font-bold mt-8 mb-4">12. Lei Aplicável</h2>
                <p>Estes termos serão regidos e interpretados de acordo com as leis do Brasil, sem considerar conflitos de disposições legais.</p>
                
                <h2 class="text-xl font-bold mt-8 mb-4">13. Entre em Contato</h2>
                <p>Se você tiver dúvidas sobre estes Termos de Uso, entre em contato conosco:</p>
                <ul class="list-none mb-6 mt-4">
                    <li><strong>E-mail:</strong> contato@widestyle.com.br</li>
                    <li><strong>Telefone:</strong> (XX) XXXX-XXXX</li>
                    <li><strong>Endereço:</strong> Rua Exemplo, 123 - Cidade - Estado - CEP XXXXX-XXX</li>
                </ul>
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
                    <a href="/termos-legais.html" class="text-gray-600 dark:text-gray-400 text-xs hover:text-black dark:hover:text-white transition-colors font-semibold">Termos Legais</a>
                    <a href="/privacidade.html" class="text-gray-600 dark:text-gray-400 text-xs hover:text-black dark:hover:text-white transition-colors">Política de Privacidade</a>
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