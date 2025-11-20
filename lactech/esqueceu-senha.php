<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Esqueceu a Senha - LacTech</title>
    
    <!-- Favicon -->
    <link rel="icon" href="./assets/img/lactech-logo.png" type="image/x-icon">
    
    <!-- Tailwind CSS -->
    <?php if (file_exists(__DIR__ . '/assets/css/tailwind.min.css')): ?>
        <link rel="stylesheet" href="assets/css/tailwind.min.css">
    <?php else: ?>
        <script src="https://cdn.tailwindcss.com"></script>
    <?php endif; ?>
    
    <!-- CSS customizado -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <style>
        .error-message {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 16px;
            display: none;
        }

        .success-message {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #166534;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 16px;
            display: none;
        }

        .loading-spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #ffffff;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="gradient-mesh min-h-screen">
    <!-- Mobile Layout -->
    <div class="md:hidden min-h-screen flex flex-col">
        <div class="flex-1 bg-white p-6 pt-12">
            <!-- Logo e título no topo -->
            <div class="text-center mb-8">
                <img src="https://i.postimg.cc/vmrkgDcB/lactech.png" alt="Logo LacTech" class="w-16 h-16 rounded-2xl shadow-lg object-cover mx-auto mb-4">
                <h1 class="text-2xl font-bold text-slate-900 mb-1">LacTech</h1>
                <p class="text-slate-600 text-sm mb-6">Sistema de Gestão Leiteira</p>
                <h2 class="text-2xl font-bold text-slate-900 mb-2">Esqueceu a Senha?</h2>
                <p class="text-slate-600">Digite seu email e uma nova senha</p>
            </div>

            <!-- Error/Success Messages -->
            <div id="errorMessage" class="error-message">
                <div class="flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span id="errorText">Erro ao redefinir senha</span>
                </div>
            </div>

            <div id="successMessage" class="success-message">
                <div class="flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span id="successText">Senha alterada com sucesso!</span>
                </div>
            </div>

            <form id="resetPasswordForm" class="space-y-6">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Email</label>
                    <input type="email" required name="email" id="email" class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none" placeholder="Digite seu email">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Nova Senha</label>
                    <div class="relative">
                        <input type="password" required name="newPassword" id="newPassword" class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none pr-12" placeholder="Digite sua nova senha">
                        <button type="button" onclick="togglePassword('newPassword', 'eyeIcon')" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-slate-400 hover:text-slate-600">
                            <svg id="eyeIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Confirmar Nova Senha</label>
                    <div class="relative">
                        <input type="password" required name="confirmPassword" id="confirmPassword" class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none pr-12" placeholder="Confirme sua nova senha">
                        <button type="button" onclick="togglePassword('confirmPassword', 'eyeIconConfirm')" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-slate-400 hover:text-slate-600">
                            <svg id="eyeIconConfirm" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit" id="resetBtn" class="w-full gradient-forest text-white py-3 px-4 rounded-xl font-semibold hover:shadow-lg transition-all">
                    <span class="loading-spinner" id="loadingSpinner" style="display: none;"></span>
                    <span id="resetText">Alterar Senha</span>
                </button>
            </form>

            <div class="mt-8 text-center">
                <button onclick="window.location.href='inicio-login.php'" class="text-forest-600 hover:text-forest-700 font-semibold">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Voltar ao Login
                </button>
            </div>
        </div>
    </div>

    <!-- Desktop Layout -->
    <div class="hidden md:flex min-h-screen">
        <div class="flex-1 relative bg-cover bg-center" style="background-image: url('https://nutrimosaic.com.br/wp-content/uploads/2024/11/vaca-holandesa-comendo-pasto-verde.jpg');">
            <div class="absolute inset-0 bg-black bg-opacity-40"></div>
            <div class="absolute inset-0 flex items-center justify-center">
                <div class="text-center text-white p-8">
                    <div class="w-16 h-16 bg-white rounded-2xl flex items-center justify-center mx-auto mb-6">
                        <svg class="w-8 h-8 text-forest-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.031 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                    </div>
                    <h1 class="text-4xl font-bold mb-4">LacTech</h1>
                    <p class="text-xl text-white/90">Sistema de Controle Leiteiro</p>
                </div>
            </div>
        </div>

        <!-- Right Side - Form -->
        <div class="flex-1 flex items-center justify-center p-8">
            <div class="w-full max-w-md">
                <!-- Logo acima do título -->
                <div class="text-center mb-8">
                    <img src="https://i.postimg.cc/vmrkgDcB/lactech.png" alt="Logo LacTech" class="w-16 h-16 rounded-2xl shadow-lg object-cover mx-auto mb-4">
                    <h2 class="text-3xl font-bold text-slate-900 mb-2">Esqueceu a Senha?</h2>
                    <p class="text-slate-600">Digite seu email e uma nova senha</p>
                </div>

                <!-- Error/Success Messages Desktop -->
                <div id="errorMessageDesktop" class="error-message">
                    <div class="flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span id="errorTextDesktop">Erro ao redefinir senha</span>
                    </div>
                </div>

                <div id="successMessageDesktop" class="success-message">
                    <div class="flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span id="successTextDesktop">Senha alterada com sucesso!</span>
                    </div>
                </div>

                <form id="resetPasswordFormDesktop" class="space-y-6">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Email</label>
                        <input type="email" required name="email" id="emailDesktop" class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none" placeholder="Digite seu email">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Nova Senha</label>
                        <div class="relative">
                            <input type="password" required name="newPassword" id="newPasswordDesktop" class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none pr-12" placeholder="Digite sua nova senha">
                            <button type="button" onclick="togglePassword('newPasswordDesktop', 'eyeIconDesktop')" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                <svg id="eyeIconDesktop" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Confirmar Nova Senha</label>
                        <div class="relative">
                            <input type="password" required name="confirmPassword" id="confirmPasswordDesktop" class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:border-forest-500 focus:ring-2 focus:ring-forest-100 focus:outline-none pr-12" placeholder="Confirme sua nova senha">
                            <button type="button" onclick="togglePassword('confirmPasswordDesktop', 'eyeIconConfirmDesktop')" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                <svg id="eyeIconConfirmDesktop" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <button type="submit" id="resetBtnDesktop" class="w-full gradient-forest text-white py-3 px-4 rounded-xl font-semibold hover:shadow-lg transition-all">
                        <span class="loading-spinner" id="loadingSpinnerDesktop" style="display: none;"></span>
                        <span id="resetTextDesktop">Alterar Senha</span>
                    </button>
                </form>

                <div class="mt-8 text-center">
                    <button onclick="window.location.href='inicio-login.php'" class="text-forest-600 hover:text-forest-700 font-semibold">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Voltar ao Login
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Toggle password visibility
        function togglePassword(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>';
            } else {
                input.type = 'password';
                icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>';
            }
        }

        // Show error message
        function showError(message, isDesktop = false) {
            const errorDiv = isDesktop ? document.getElementById('errorMessageDesktop') : document.getElementById('errorMessage');
            const errorText = isDesktop ? document.getElementById('errorTextDesktop') : document.getElementById('errorText');
            
            if (errorDiv && errorText) {
                errorText.textContent = message;
                errorDiv.style.display = 'block';
                
                // Hide after 5 seconds
                setTimeout(() => {
                    errorDiv.style.display = 'none';
                }, 5000);
            }
        }

        // Show success message
        function showSuccess(message, isDesktop = false) {
            const successDiv = isDesktop ? document.getElementById('successMessageDesktop') : document.getElementById('successMessage');
            const successText = isDesktop ? document.getElementById('successTextDesktop') : document.getElementById('successText');
            
            if (successDiv && successText) {
                successText.textContent = message;
                successDiv.style.display = 'block';
            }
        }

        // Hide all messages
        function hideMessages(isDesktop = false) {
            const errorDiv = isDesktop ? document.getElementById('errorMessageDesktop') : document.getElementById('errorMessage');
            const successDiv = isDesktop ? document.getElementById('successMessageDesktop') : document.getElementById('successMessage');
            
            if (errorDiv) errorDiv.style.display = 'none';
            if (successDiv) successDiv.style.display = 'none';
        }

        // Set loading state
        function setLoadingState(loading, isDesktop = false) {
            const btn = isDesktop ? document.getElementById('resetBtnDesktop') : document.getElementById('resetBtn');
            const btnText = isDesktop ? document.getElementById('resetTextDesktop') : document.getElementById('resetText');
            const spinner = isDesktop ? document.getElementById('loadingSpinnerDesktop') : document.getElementById('loadingSpinner');
            
            if (btn && btnText && spinner) {
                btn.disabled = loading;
                if (loading) {
                    btnText.textContent = 'Alterando...';
                    spinner.style.display = 'inline-block';
                } else {
                    btnText.textContent = 'Alterar Senha';
                    spinner.style.display = 'none';
                }
            }
        }

        // Handle password reset
        async function handlePasswordReset(e, isDesktop = false) {
            e.preventDefault();
            
            const form = e.target;
            const email = form.querySelector('[name="email"]').value;
            const newPassword = form.querySelector('[name="newPassword"]').value;
            const confirmPassword = form.querySelector('[name="confirmPassword"]').value;
            
            // Validation
            if (!email || !newPassword || !confirmPassword) {
                showError('Por favor, preencha todos os campos.', isDesktop);
                return;
            }
            
            if (newPassword !== confirmPassword) {
                showError('As senhas não coincidem.', isDesktop);
                return;
            }
            
            // Hide previous messages and show loading
            hideMessages(isDesktop);
            setLoadingState(true, isDesktop);
            
            try {
                const response = await fetch('api/actions.php?action=reset_password', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        email: email,
                        new_password: newPassword
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showSuccess('Senha alterada com sucesso! Redirecionando para o login...', isDesktop);
                    setLoadingState(false, isDesktop);
                    
                    // Redirect to login after 2 seconds
                    setTimeout(() => {
                        window.location.href = 'inicio-login.php';
                    }, 2000);
                } else {
                    throw new Error(result.error || 'Erro ao alterar senha');
                }
                
            } catch (error) {
                console.error('Erro ao alterar senha:', error);
                setLoadingState(false, isDesktop);
                showError(error.message || 'Erro ao alterar senha. Tente novamente.', isDesktop);
            }
        }

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            // Setup form listeners
            const mobileForm = document.getElementById('resetPasswordForm');
            const desktopForm = document.getElementById('resetPasswordFormDesktop');
            
            if (mobileForm) {
                mobileForm.addEventListener('submit', (e) => handlePasswordReset(e, false));
            }
            
            if (desktopForm) {
                desktopForm.addEventListener('submit', (e) => handlePasswordReset(e, true));
            }
        });
    </script>
</body>
</html>



