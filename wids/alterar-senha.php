<!DOCTYPE html>
<html lang="pt-BR" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alterar Senha - Wide Style</title>
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
            <h1 class="text-3xl md:text-4xl font-bold mb-3">Alterar Senha</h1>
            <p class="text-gray-600 dark:text-gray-400">Atualize sua senha de acesso</p>
        </div>
        
        <!-- Formulário de alteração de senha -->
        <div class="max-w-md mx-auto bg-gray-100 dark:bg-zinc-900 rounded-xl shadow p-8 mb-8">
            <div id="not-logged-message" class="hidden text-center py-8">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
                <h2 class="text-xl font-semibold mb-2">Você não está conectado</h2>
                <p class="text-gray-600 dark:text-gray-400 mb-6">Faça login para alterar sua senha</p>
                <a href="/" class="inline-block bg-black dark:bg-white text-white dark:text-black px-6 py-3 rounded-full font-medium hover:bg-gray-800 dark:hover:bg-gray-200 transition-colors">
                    Ir para a página inicial
                </a>
            </div>
            
            <form id="change-password-form" class="space-y-6">
                <div class="user-info mb-6 pb-6 border-b border-gray-200 dark:border-zinc-800">
                    <div class="flex items-center">
                        <div class="w-12 h-12 rounded-full bg-gray-800 dark:bg-gray-200 text-white dark:text-black flex items-center justify-center text-xl font-bold mr-4" id="user-initial">
                            A
                        </div>
                        <div>
                            <h3 class="font-semibold text-lg" id="user-name">Usuário</h3>
                            <p class="text-gray-600 dark:text-gray-400 text-sm" id="user-email">usuario@exemplo.com</p>
                        </div>
                    </div>
                </div>
                
                <div>
                    <label for="current-password" class="block text-sm font-medium mb-2">Senha Atual</label>
                    <input type="password" id="current-password" name="current-password" required 
                        class="w-full px-4 py-3 rounded-lg bg-white dark:bg-zinc-800 border border-gray-300 dark:border-zinc-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label for="new-password" class="block text-sm font-medium mb-2">Nova Senha</label>
                    <input type="password" id="new-password" name="new-password" required
                        class="w-full px-4 py-3 rounded-lg bg-white dark:bg-zinc-800 border border-gray-300 dark:border-zinc-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label for="confirm-password" class="block text-sm font-medium mb-2">Confirmar Nova Senha</label>
                    <input type="password" id="confirm-password" name="confirm-password" required
                        class="w-full px-4 py-3 rounded-lg bg-white dark:bg-zinc-800 border border-gray-300 dark:border-zinc-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="pt-4">
                    <button type="submit" 
                        class="w-full bg-black dark:bg-white text-white dark:text-black px-6 py-3 rounded-full font-medium hover:bg-gray-800 dark:hover:bg-gray-200 transition-colors">
                        Alterar Senha
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Dicas de segurança -->
        <div class="max-w-md mx-auto">
            <h3 class="text-lg font-semibold mb-4">Dicas para uma senha segura:</h3>
            <ul class="list-disc pl-5 space-y-2 text-gray-600 dark:text-gray-400">
                <li>Use pelo menos 8 caracteres</li>
                <li>Combine letras maiúsculas e minúsculas</li>
                <li>Inclua números e caracteres especiais</li>
                <li>Evite informações pessoais óbvias</li>
                <li>Não reutilize senhas de outros serviços</li>
            </ul>
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
                    <a href="/termos-legais.html" class="text-gray-600 dark:text-gray-400 text-xs hover:text-black dark:hover:text-white transition-colors">Termos Legais</a>
                    <a href="/privacidade.html" class="text-gray-600 dark:text-gray-400 text-xs hover:text-black dark:hover:text-white transition-colors">Política de Privacidade</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Notification Container -->
    <div id="notification-container" class="fixed top-4 right-4 z-50 flex flex-col items-end space-y-2"></div>

    <!-- Loading Screen -->
    <div id="loading-screen" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 hidden">
        <div class="bg-white dark:bg-zinc-900 p-4 rounded-full">
            <svg class="animate-spin h-8 w-8 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>
    </div>

    <!-- Script para alternar modo escuro -->
    <script>
        // Forçar modo escuro por padrão
        localStorage.setItem('darkMode', 'true');
        document.documentElement.classList.add('dark');
    </script>
    
    <!-- Script para alterar senha -->
    <script src="password-change.js"></script>
</body>
</html> 