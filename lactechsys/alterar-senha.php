<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alterar Senha - LacTech</title>
    
    <!-- PWA Meta Tags -->
    <meta name="description" content="Alterar senha - Sistema LacTech">
    <meta name="theme-color" content="#166534">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="LacTech">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="msapplication-TileColor" content="#166534">
    
    <!-- PWA Icons -->
    <link rel="icon" href="https://i.postimg.cc/vmrkgDcB/lactech.png" type="image/x-icon">
    <link rel="apple-touch-icon" href="https://i.postimg.cc/vmrkgDcB/lactech.png">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="assets/js/config_mysql.js"></script>
    
    <!-- CSS Files -->
    <link href="assets/css/dark-theme-fixes.css?v=2.0" rel="stylesheet">
    <link href="assets/css/loading-screen.css" rel="stylesheet">
    
    <style>
        /* Background gradient */
        .gradient-mesh {
            background: linear-gradient(135deg, #f0f9f0 0%, #dcf2dc 25%, #bce5bc 50%, #8dd18d 75%, #5bb85b 100%);
            min-height: 100vh;
        }
        
        .gradient-forest {
            background: linear-gradient(135deg, #166534 0%, #15803d 50%, #16a34a 100%);
        }
        
        /* Card styles */
        .card-shadow {
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        /* Button styles */
        .btn-primary {
            background: linear-gradient(135deg, #166534 0%, #15803d 100%);
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(22, 101, 52, 0.3);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
            transition: all 0.3s ease;
        }
        
        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(107, 114, 128, 0.3);
        }
        
        /* Input focus styles */
        .input-focus:focus {
            border-color: #166534;
            box-shadow: 0 0 0 3px rgba(22, 101, 52, 0.1);
        }
        
        /* Responsive adjustments */
        @media (max-width: 640px) {
            .container-mobile {
                padding: 1rem;
                margin: 0;
            }
        }
        
        /* Dark mode support */
        .dark .gradient-mesh {
            background: linear-gradient(135deg, #000000 0%, #1a1a1a 25%, #2d2d2d 50%, #404040 75%, #525252 100%);
        }
        
        .dark .card-bg {
            background-color: #1f2937;
            border-color: #374151;
        }
        
        .dark .text-primary {
            color: #f9fafb;
        }
        
        .dark .text-secondary {
            color: #d1d5db;
        }
    </style>
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'forest': {
                            50: '#f0f9f0', 100: '#dcf2dc', 200: '#bce5bc', 300: '#8dd18d',
                            400: '#5bb85b', 500: '#369e36', 600: '#2a7f2a', 700: '#236523',
                            800: '#1f511f', 900: '#1a431a',
                        }
                    }
                }
            }
        }
    </script>
</head>

<body class="gradient-mesh antialiased transition-colors duration-300">
    <!-- Header -->
    <header class="gradient-forest shadow-xl">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                        <img src="assets/img/lactech-logo.png" alt="LacTech" class="w-6 h-6">
                    </div>
                    <div>
                        <h1 class="text-lg font-bold text-white">Alterar Senha</h1>
                        <p class="text-xs text-forest-200">Sistema LacTech</p>
                    </div>
                </div>
                
                <button onclick="goBack()" class="flex items-center space-x-2 text-white hover:text-forest-200 p-2 rounded-lg transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    <span class="hidden sm:inline">Voltar</span>
                </button>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white dark:bg-gray-800 rounded-2xl card-shadow p-6 sm:p-8">
            <!-- Header -->
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-forest-100 dark:bg-forest-900 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-forest-600 dark:text-forest-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Alterar Senha</h2>
                <p class="text-gray-600 dark:text-gray-400">Digite sua senha atual e a nova senha desejada</p>
            </div>

            <!-- Form -->
            <form id="changePasswordForm" class="space-y-6">
                <!-- Senha Atual -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Senha Atual
                    </label>
                    <div class="relative">
                        <input 
                            type="password" 
                            id="currentPassword"
                            name="current_password" 
                            required 
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl input-focus focus:outline-none bg-white dark:bg-gray-700 text-gray-900 dark:text-white transition-all"
                            placeholder="Digite sua senha atual"
                        >
                        <button 
                            type="button" 
                            onclick="togglePasswordVisibility('currentPassword')"
                            class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                        >
                            <svg id="currentPasswordIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Nova Senha -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Nova Senha
                    </label>
                    <div class="relative">
                        <input 
                            type="password" 
                            id="newPassword"
                            name="new_password" 
                            required 
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl input-focus focus:outline-none bg-white dark:bg-gray-700 text-gray-900 dark:text-white transition-all"
                            placeholder="Digite a nova senha"
                            minlength="6"
                        >
                        <button 
                            type="button" 
                            onclick="togglePasswordVisibility('newPassword')"
                            class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                        >
                            <svg id="newPasswordIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="mt-2">
                        <div class="flex items-center space-x-2 text-xs text-gray-500 dark:text-gray-400">
                            <div id="passwordStrength" class="flex-1 h-2 bg-gray-200 dark:bg-gray-600 rounded-full overflow-hidden">
                                <div id="passwordStrengthBar" class="h-full bg-red-500 transition-all duration-300" style="width: 0%"></div>
                            </div>
                            <span id="passwordStrengthText">Fraca</span>
                        </div>
                    </div>
                </div>

                <!-- Confirmar Nova Senha -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Confirmar Nova Senha
                    </label>
                    <div class="relative">
                        <input 
                            type="password" 
                            id="confirmPassword"
                            name="confirm_password" 
                            required 
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl input-focus focus:outline-none bg-white dark:bg-gray-700 text-gray-900 dark:text-white transition-all"
                            placeholder="Confirme a nova senha"
                        >
                        <button 
                            type="button" 
                            onclick="togglePasswordVisibility('confirmPassword')"
                            class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                        >
                            <svg id="confirmPasswordIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </button>
                    </div>
                    <div id="passwordMatch" class="mt-2 text-xs hidden">
                        <span class="text-green-600 dark:text-green-400">✓ Senhas coincidem</span>
                    </div>
                </div>

                <!-- Botões -->
                <div class="flex flex-col sm:flex-row gap-3 pt-4">
                    <button 
                        type="button" 
                        onclick="goBack()" 
                        class="flex-1 px-6 py-3 btn-secondary text-white font-semibold rounded-xl transition-all"
                    >
                        Cancelar
                    </button>
                    <button 
                        type="submit" 
                        class="flex-1 px-6 py-3 btn-primary text-white font-semibold rounded-xl transition-all"
                    >
                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Alterar Senha
                    </button>
                </div>
            </form>

            <!-- Informações de Segurança -->
            <div class="mt-8 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div>
                        <h4 class="text-sm font-semibold text-blue-800 dark:text-blue-200 mb-2">Dicas de Segurança</h4>
                        <ul class="text-sm text-blue-700 dark:text-blue-300 space-y-1">
                            <li>• Use pelo menos 6 caracteres</li>
                            <li>• Combine letras, números e símbolos</li>
                            <li>• Evite informações pessoais óbvias</li>
                            <li>• Não use a mesma senha em outros sites</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Notification Container -->
    <div id="notificationContainer" class="fixed top-4 right-4 z-50 space-y-2"></div>

    <script>
        // Configuração do Supabase
        const SUPABASE_URL = 'https://qwxkqwxkqwxkqwxkqwxk.supabase.co';
        const SUPABASE_ANON_KEY = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InF3eGtxd3hrcXd4a3F3eGtxd3hrIiwicm9sZSI6ImFub24iLCJpYXQiOjE3MzQ5NzI5NzQsImV4cCI6MjA1MDU0ODk3NH0.qwxkqwxkqwxkqwxkqwxkqwxkqwxkqwxkqwxkqwxkqwxk';

        // Inicializar Supabase
        const supabase = window.supabase.createClient(SUPABASE_URL, SUPABASE_ANON_KEY);

        // Verificar autenticação
        async function checkAuth() {
            const { data: { user } } = await supabase.auth.getUser();
            if (!user) {
                window.location.href = 'login.php';
            }
        }

        // Função para voltar
        function goBack() {
            const referrer = document.referrer;
            if (referrer && referrer.includes('gerente.php')) {
                window.location.href = 'gerente.php';
            } else {
                window.location.href = 'index.php';
            }
        }

        // Toggle password visibility
        function togglePasswordVisibility(inputId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(inputId + 'Icon');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path>
                `;
            } else {
                input.type = 'password';
                icon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                `;
            }
        }

        // Verificar força da senha
        function checkPasswordStrength(password) {
            let strength = 0;
            let text = 'Fraca';
            let color = 'bg-red-500';

            if (password.length >= 6) strength++;
            if (password.length >= 8) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;

            if (strength >= 5) {
                text = 'Forte';
                color = 'bg-green-500';
            } else if (strength >= 3) {
                text = 'Média';
                color = 'bg-yellow-500';
            }

            document.getElementById('passwordStrengthBar').className = `h-full ${color} transition-all duration-300`;
            document.getElementById('passwordStrengthBar').style.width = `${(strength / 6) * 100}%`;
            document.getElementById('passwordStrengthText').textContent = text;
        }

        // Verificar se as senhas coincidem
        function checkPasswordMatch() {
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            const matchDiv = document.getElementById('passwordMatch');

            if (confirmPassword && newPassword === confirmPassword) {
                matchDiv.classList.remove('hidden');
                matchDiv.querySelector('span').className = 'text-green-600 dark:text-green-400';
                matchDiv.querySelector('span').textContent = '✓ Senhas coincidem';
            } else if (confirmPassword) {
                matchDiv.classList.remove('hidden');
                matchDiv.querySelector('span').className = 'text-red-600 dark:text-red-400';
                matchDiv.querySelector('span').textContent = '✗ Senhas não coincidem';
            } else {
                matchDiv.classList.add('hidden');
            }
        }

        // Mostrar notificação
        function showNotification(message, type = 'info') {
            const container = document.getElementById('notificationContainer');
            const notification = document.createElement('div');
            
            const colors = {
                success: 'bg-green-500',
                error: 'bg-red-500',
                warning: 'bg-yellow-500',
                info: 'bg-blue-500'
            };

            notification.className = `${colors[type]} text-white px-6 py-4 rounded-lg shadow-lg transform transition-all duration-300 translate-x-full`;
            notification.textContent = message;

            container.appendChild(notification);

            // Animar entrada
            setTimeout(() => {
                notification.classList.remove('translate-x-full');
            }, 100);

            // Remover após 5 segundos
            setTimeout(() => {
                notification.classList.add('translate-x-full');
                setTimeout(() => {
                    container.removeChild(notification);
                }, 300);
            }, 5000);
        }

        // Alterar senha
        async function changePassword(currentPassword, newPassword) {
            try {
                const { error } = await supabase.auth.updateUser({
                    password: newPassword
                });

                if (error) {
                    // Se o erro for de senha atual incorreta, tentar reautenticar
                    if (error.message.includes('password')) {
                        const { error: reauthError } = await supabase.auth.signInWithPassword({
                            email: (await supabase.auth.getUser()).data.user.email,
                            password: currentPassword
                        });

                        if (reauthError) {
                            throw new Error('Senha atual incorreta');
                        }

                        // Tentar novamente após reautenticação
                        const { error: updateError } = await supabase.auth.updateUser({
                            password: newPassword
                        });

                        if (updateError) {
                            throw updateError;
                        }
                    } else {
                        throw error;
                    }
                }

                return true;
            } catch (error) {
                throw error;
            }
        }

        // Event listeners
        document.addEventListener('DOMContentLoaded', function() {
            checkAuth();

            // Verificar força da senha
            document.getElementById('newPassword').addEventListener('input', function() {
                checkPasswordStrength(this.value);
                checkPasswordMatch();
            });

            // Verificar se as senhas coincidem
            document.getElementById('confirmPassword').addEventListener('input', checkPasswordMatch);

            // Form submit
            document.getElementById('changePasswordForm').addEventListener('submit', async function(e) {
                e.preventDefault();

                const currentPassword = document.getElementById('currentPassword').value;
                const newPassword = document.getElementById('newPassword').value;
                const confirmPassword = document.getElementById('confirmPassword').value;

                // Validações
                if (newPassword.length < 6) {
                    showNotification('A nova senha deve ter pelo menos 6 caracteres', 'error');
                    return;
                }

                if (newPassword !== confirmPassword) {
                    showNotification('As senhas não coincidem', 'error');
                    return;
                }

                if (newPassword === currentPassword) {
                    showNotification('A nova senha deve ser diferente da senha atual', 'error');
                    return;
                }

                // Desabilitar botão
                const submitButton = this.querySelector('button[type="submit"]');
                const originalText = submitButton.innerHTML;
                submitButton.disabled = true;
                submitButton.innerHTML = `
                    <svg class="animate-spin w-5 h-5 inline mr-2" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Alterando...
                `;

                try {
                    await changePassword(currentPassword, newPassword);
                    showNotification('Senha alterada com sucesso!', 'success');
                    
                    // Limpar formulário
                    this.reset();
                    document.getElementById('passwordStrengthBar').style.width = '0%';
                    document.getElementById('passwordStrengthText').textContent = 'Fraca';
                    document.getElementById('passwordMatch').classList.add('hidden');
                    
                    // Redirecionar após 2 segundos
                    setTimeout(() => {
                        goBack();
                    }, 2000);
                } catch (error) {
                    console.error('Erro ao alterar senha:', error);
                    showNotification(error.message || 'Erro ao alterar senha', 'error');
                } finally {
                    // Reabilitar botão
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalText;
                }
            });
        });
    </script>
</body>
</html>
