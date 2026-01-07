document.addEventListener('DOMContentLoaded', function() {
    // Usar API PHP ao invés de Supabase
    
    // Função para atualizar senha
    async function updatePassword(newPassword) {
        try {
            if (!window.API) {
                throw new Error('API não carregada');
            }
            
            // A função changePassword requer a senha atual também
            // Mas como não temos aqui, vamos usar uma abordagem diferente
            // Primeiro verificar se está autenticado
            const userResult = await window.API.getCurrentUser();
            
            if (!userResult.success || !userResult.user) {
                throw new Error('Usuário não autenticado');
            }
            
            // Para alterar senha sem a senha atual, precisamos do token de reset
            // Mas neste contexto, vamos assumir que já validamos a senha atual no formulário
            // Então vamos usar a função changePassword que requer ambas as senhas
            // Esta função será chamada após validar a senha atual no handlePasswordChange
            return { success: true, requiresCurrentPassword: true };
        } catch (error) {
            console.error('Erro ao atualizar senha:', error.message);
            return { success: false, error: error.message };
        }
    }
    
    // Get form elements
    const passwordChangeForm = document.getElementById('password-change-form');
    const currentPasswordInput = document.getElementById('current-password');
    const newPasswordInput = document.getElementById('new-password');
    const confirmPasswordInput = document.getElementById('confirm-password');
    const passwordRequirements = document.querySelector('.password-requirements');
    const loadingScreen = document.getElementById('loading-screen');
    const notLoggedMessage = document.getElementById('not-logged-message');
    
    // Verificar estado de login e mostrar formulário apenas se estiver logado
    async function checkLoginState() {
        try {
            if (!window.API) {
                throw new Error('API não carregada');
            }
            
            const userResult = await window.API.getCurrentUser();
            
            if (!userResult.success || !userResult.user) {
                // Usuário não está logado
                if (passwordChangeForm) passwordChangeForm.classList.add('hidden');
                if (notLoggedMessage) notLoggedMessage.classList.remove('hidden');
                return false;
            }
            
            // Usuário está logado
            if (passwordChangeForm) passwordChangeForm.classList.remove('hidden');
            if (notLoggedMessage) notLoggedMessage.classList.add('hidden');
            
            // Preencher informações do usuário
            const userName = document.getElementById('user-name');
            const userEmail = document.getElementById('user-email');
            const userInitial = document.getElementById('user-initial');
            
            if (userName) userName.textContent = userResult.user.name || 'Usuário';
            if (userEmail) userEmail.textContent = userResult.user.email;
            if (userInitial) userInitial.textContent = (userResult.user.name || 'U').charAt(0).toUpperCase();
            
            return true;
        } catch (error) {
            console.error('Erro ao verificar estado de login:', error);
            if (passwordChangeForm) passwordChangeForm.classList.add('hidden');
            if (notLoggedMessage) notLoggedMessage.classList.remove('hidden');
            return false;
        }
    }
    
    // Verificar login ao carregar a página
    checkLoginState();
    
    // Event listener for form submission
    if (passwordChangeForm) {
        passwordChangeForm.addEventListener('submit', handlePasswordChange);
    }
    
    // Event listener for password input to show requirements
    if (newPasswordInput && passwordRequirements) {
        newPasswordInput.addEventListener('focus', function() {
            passwordRequirements.classList.remove('hidden');
        });
        
        newPasswordInput.addEventListener('blur', function() {
            if (!newPasswordInput.value) {
                passwordRequirements.classList.add('hidden');
            }
        });
    }
    
    // Function to handle password change
    async function handlePasswordChange(event) {
        event.preventDefault();
        
        // Get form values
        const currentPassword = currentPasswordInput.value.trim();
        const newPassword = newPasswordInput.value.trim();
        const confirmPassword = confirmPasswordInput.value.trim();
        
        // Validate inputs
        if (!currentPassword || !newPassword || !confirmPassword) {
            showNotification('Todos os campos devem ser preenchidos', 'error');
            return;
        }
        
        // Validate password match
        if (newPassword !== confirmPassword) {
            showNotification('As senhas não coincidem', 'error');
            return;
        }
        
        // Validate password requirements
        if (newPassword.length < 6) {
            showNotification('A nova senha deve ter pelo menos 6 caracteres', 'error');
            return;
        }
        
        // Show loading
        toggleLoading(true);
        
        try {
            if (!window.API) {
                throw new Error('API não carregada');
            }
            
            // Verificar se está autenticado
            const userResult = await window.API.getCurrentUser();
            
            if (!userResult.success || !userResult.user) {
                toggleLoading(false);
                showNotification('Usuário não está autenticado', 'error');
                return;
            }
            
            // Usar a função changePassword que valida a senha atual
            const result = await window.API.changePassword(currentPassword, newPassword);
            
            if (result.success) {
                toggleLoading(false);
                showNotification('Senha alterada com sucesso!', 'success');
                
                // Limpar formulário
                passwordChangeForm.reset();
                
                // Redirecionar para a página de conta após delay
                setTimeout(() => {
                    window.location.href = 'account.html';
                }, 2000);
            } else {
                toggleLoading(false);
                showNotification(result.error || 'Erro ao alterar senha', 'error');
            }
        } catch (error) {
            console.error('Erro ao processar alteração de senha:', error);
            toggleLoading(false);
            showNotification('Erro ao processar solicitação', 'error');
        }
    }
    
    // Function to toggle loading screen
    function toggleLoading(show) {
        if (loadingScreen) {
            if (show) {
                loadingScreen.classList.remove('hidden');
            } else {
                loadingScreen.classList.add('hidden');
            }
        }
    }
    
    // Function to show notification
    function showNotification(message, type) {
        const notificationContainer = document.getElementById('notification-container');
        
        if (!notificationContainer) return;
        
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        
        // Add to container
        notificationContainer.appendChild(notification);
        
        // Auto-remove after delay
        setTimeout(() => {
            notification.classList.add('fade-out');
            setTimeout(() => {
                notification.remove();
            }, 500);
        }, 3000);
    }
}); 