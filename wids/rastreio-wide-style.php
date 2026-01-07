<!DOCTYPE html>
<html lang="pt-BR" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rastreamento de Pedidos - Wide Style</title>
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
    <div id="promo-banner" class="bg-white dark:bg-black text-black dark:text-white py-2 text-center font-medium border-b border-gray-300 dark:border-gray-800 sticky top-0 z-50 transition-transform duration-300">
        Ganhe 10% OFF com cupom - WIDE10
    </div>

    <!-- Header -->
    <header class="sticky top-0 left-0 w-full bg-white/90 dark:bg-black/90 backdrop-blur-md z-40 transition-colors duration-300">
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

    <!-- Conteúdo Principal -->
    <main class="container mx-auto py-16 px-4">
        <!-- Título da página -->
        <div class="mb-10 text-center">
            <h1 class="text-3xl md:text-4xl font-bold mb-3">Rastreamento de Pedidos</h1>
            <p class="text-gray-600 dark:text-gray-400">Acompanhe o status da entrega do seu pedido</p>
        </div>
        
        <!-- Formulário de Rastreamento -->
        <section class="max-w-2xl mx-auto bg-gray-100 dark:bg-zinc-900 rounded-xl shadow p-8 mb-8">
            <form method="GET" class="space-y-6">
                <div class="mb-6">
                    <label for="codigoRastreio" class="block text-sm font-medium mb-2">Código de Rastreio</label>
                    <input type="text" id="codigoRastreio" name="codigoRastreio" 
                        class="w-full bg-white dark:bg-zinc-800 border border-gray-300 dark:border-zinc-700 rounded-lg py-3 px-4 text-black dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500" 
                        placeholder="Digite seu código de rastreio">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Insira o código fornecido no e-mail de confirmação do seu pedido</p>
                </div>
                
                <div>
                    <p class="text-sm font-medium mb-3">Selecione um serviço de rastreamento:</p>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <button type="submit" name="servicoRastreio" value="CORREIOS" 
                            class="rastrear-btn bg-white dark:bg-zinc-800 hover:bg-gray-100 dark:hover:bg-zinc-700 text-black dark:text-white font-medium py-3 px-4 rounded-lg transition-colors border border-blue-500 hover:border-blue-600 flex items-center justify-center">
                            <img src="https://e3ba6e8732e83984.cdn.gocache.net/uploads/image/file/404912/regular_correios-logo-2.png" alt="Correios Logo" class="h-5 mr-2">
                            CORREIOS
                        </button>
                        <button type="submit" name="servicoRastreio" value="MELHOR RASTREIO" 
                            class="rastrear-btn bg-white dark:bg-zinc-800 hover:bg-gray-100 dark:hover:bg-zinc-700 text-black dark:text-white font-medium py-3 px-4 rounded-lg transition-colors border border-green-500 hover:border-green-600 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            MELHOR RASTREIO
                        </button>
                        <button type="submit" name="servicoRastreio" value="17TRACK" 
                            class="rastrear-btn bg-white dark:bg-zinc-800 hover:bg-gray-100 dark:hover:bg-zinc-700 text-black dark:text-white font-medium py-3 px-4 rounded-lg transition-colors border border-purple-500 hover:border-purple-600 flex items-center justify-center">
                            <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRhCXVaDEMx7QX1CMMzNlPDgCKjH_p2qIJWDQ&s" alt="17Track Logo" class="h-5 mr-2">
                            17TRACK
                        </button>
                    </div>
                </div>
                
                <!-- Informações auxiliares -->
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800 rounded-lg p-4 mt-6">
                    <div class="flex items-start">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-500 mt-0.5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div>
                            <p class="text-sm text-gray-800 dark:text-gray-200">Cada serviço de rastreamento pode exibir informações diferentes sobre seu pedido. Recomendamos verificar em mais de um serviço para informações mais completas.</p>
                        </div>
                    </div>
                </div>
            </form>

            <!-- Botão Voltar -->
            <div class="mt-6 flex">
                <a href="javascript:history.back()" class="inline-flex items-center text-blue-500 hover:text-blue-700 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Voltar para a loja
                </a>
            </div>
        </section>
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
                    <a href="/rastreio" class="text-gray-600 dark:text-gray-400 hover:text-black dark:hover:text-white transition-colors">Rastreio</a>
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
                    <a href="termos-legais.html" class="text-gray-600 dark:text-gray-400 text-xs hover:text-black dark:hover:text-white transition-colors">Termos Legais</a>
                </div>
            </div>
        </div>
    </footer>

    <?php
    // Função para redirecionar com segurança
    function redirecionar(string $url): void {
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            header("Location: " . $url, true, 302);
            exit;
        }
        // Caso a URL seja inválida
        echo '<script>alert("URL de redirecionamento inválida.");</script>';
    }

    // Array de serviços de rastreamento
    $servicosRastreamento = [
        'CORREIOS' => 'https://www.linkcorreios.com.br/?id=',
        'MELHOR RASTREIO' => 'https://melhorrastreio.com.br/rastrear/',
        '17TRACK' => 'https://www.17track.net/pt/track?nums='
    ];

    // Verifica se os parâmetros foram enviados
    if (isset($_GET['codigoRastreio'], $_GET['servicoRastreio'])) {
        // Usando FILTER_SANITIZE_FULL_SPECIAL_CHARS para melhor sanitização
        $codigoRastreio = filter_input(INPUT_GET, 'codigoRastreio', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $servicoRastreio = filter_input(INPUT_GET, 'servicoRastreio', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        
        // Verifica se o código de rastreio não está vazio
        if (empty($codigoRastreio)) {
            echo '<script>alert("Por favor, insira um código de rastreio válido.");</script>';
        }
        // Verifica se o serviço de rastreamento é válido
        elseif (array_key_exists($servicoRastreio, $servicosRastreamento)) {
            $urlRedirecionamento = $servicosRastreamento[$servicoRastreio] . urlencode($codigoRastreio);
            redirecionar($urlRedirecionamento);
        } else {
            echo '<script>alert("Serviço de rastreio não reconhecido.");</script>';
        }
    }
    
    // Registra o acesso ao rastreamento (opcional)
    function registrarAcesso($codigo, $servico) {
        // Implementação futura para registrar acessos
        // Ex: log em arquivo, banco de dados, etc.
    }
    ?>

    <!-- Script para alternar modo escuro (opcional) -->
    <script>
        // Forçar modo escuro por padrão
        localStorage.setItem('darkMode', 'true');
        document.documentElement.classList.add('dark');
    </script>
</body>
</html>