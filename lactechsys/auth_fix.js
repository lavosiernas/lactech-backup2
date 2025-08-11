
async function syncAuthenticationWithSupabase() {
    try {
        // Verificar se há dados de usuário no localStorage
        const userData = localStorage.getItem('userData') || sessionStorage.getItem('userData');
        
        if (!userData) {
            console.log('Nenhum dado de usuário encontrado');
            return false;
        }
        
        const user = JSON.parse(userData);
        
        // Verificar se já existe uma sessão Supabase válida
        const { data: { session }, error: sessionError } = await supabase.auth.getSession();
        
        if (session && !sessionError) {
            console.log('Sessão Supabase já existe');
            return true;
        }
        
        // Se não há sessão Supabase, tentar fazer login com os dados locais
        console.log('Tentando sincronizar autenticação com Supabase...');
        
        // Tentar fazer login com email e senha (se disponível)
        if (user.email && user.password) {
            const { data, error } = await supabase.auth.signInWithPassword({
                email: user.email,
                password: user.password
            });
            
            if (error) {
                console.error('Erro ao fazer login no Supabase:', error);
                return false;
            }
            
            if (data.session) {
                console.log('Autenticação Supabase sincronizada com sucesso');
                return true;
            }
        }
        
        // Se não conseguiu fazer login, tentar usar signInWithOtp (magic link)
        console.log('Tentando autenticação alternativa...');
        
        const { data, error } = await supabase.auth.signInWithOtp({
            email: user.email,
            options: {
                emailRedirectTo: window.location.origin
            }
        });
        
        if (error) {
            console.error('Erro na autenticação alternativa:', error);
            return false;
        }
        
        console.log('Link de autenticação enviado para:', user.email);
        return false;
        
    } catch (error) {
        console.error('Erro ao sincronizar autenticação:', error);
        return false;
    }
}

/**
 * Verificar e corrigir autenticação
 */
async function checkAndFixAuthentication() {
    try {
        // Primeiro verificar se há sessão Supabase válida
        const { data: { user }, error: authError } = await supabase.auth.getUser();
        
        if (!authError && user) {
            console.log('Usuário Supabase autenticado:', user.email);
            // Conferir status de bloqueio na tabela users
            try {
                const { data: dbUser, error: dbErr } = await supabase
                    .from('users')
                    .select('id, email, is_active')
                    .eq('id', user.id)
                    .single();
                if (!dbErr && dbUser && dbUser.is_active === false) {
                    console.log('Usuário bloqueado na base. Redirecionando...');
                    window.location.href = 'acesso-bloqueado.html';
                    return false;
                }
            } catch (_) {}
            return true;
        }
        
        // Se não há sessão Supabase, tentar sincronizar
        console.log('Sessão Supabase não encontrada, tentando sincronizar...');
        const syncResult = await syncAuthenticationWithSupabase();
        
        if (syncResult) {
            console.log('Autenticação sincronizada com sucesso');
            // Após sincronizar, checar bloqueio
            try {
                const { data: { user: syncedUser } } = await supabase.auth.getUser();
                if (syncedUser) {
                    const { data: dbUser } = await supabase
                        .from('users')
                        .select('is_active')
                        .eq('id', syncedUser.id)
                        .single();
                    if (dbUser && dbUser.is_active === false) {
                        window.location.href = 'acesso-bloqueado.html';
                        return false;
                    }
                }
            } catch (_) {}
            return true;
        }
        
        // Se não conseguiu sincronizar, verificar se há dados locais válidos
        const userData = localStorage.getItem('userData') || sessionStorage.getItem('userData');
        
        if (userData) {
            try {
                const user = JSON.parse(userData);
                const loginTime = new Date(user.loginTime);
                const now = new Date();
                const hoursSinceLogin = (now - loginTime) / (1000 * 60 * 60);
                
                // Verificar se a sessão local ainda é válida
                const maxHours = localStorage.getItem('userData') ? 24 : 8;
                
                if (hoursSinceLogin < maxHours && user.is_active !== false) {
                    console.log('Sessão local válida, mas Supabase não sincronizado');
                    console.log('Redirecionando para página de reautenticação...');
                    
                    // Criar uma página temporária para reautenticação
                    showReauthenticationModal();
                    return false;
                }
            } catch (error) {
                console.error('Erro ao verificar dados locais:', error);
            }
        }
        
        console.log('Nenhuma autenticação válida encontrada');
        return false;
        
    } catch (error) {
        console.error('Erro ao verificar autenticação:', error);
        return false;
    }
}

/**
 * Mostrar modal de reautenticação
 */
function showReauthenticationModal() {
    // Criar modal de reautenticação
    const modal = document.createElement('div');
    modal.id = 'reauthModal';
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
    modal.innerHTML = `
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
            <div class="text-center">
                <div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Reautenticação Necessária</h3>
                <p class="text-gray-600 mb-4">Sua sessão expirou. Por favor, faça login novamente para continuar.</p>
                <div class="space-y-3">
                    <button onclick="redirectToLogin()" class="w-full bg-green-600 text-white py-2 px-4 rounded-lg hover:bg-green-700 transition-colors">
                        Fazer Login
                    </button>
                    <button onclick="closeReauthModal()" class="w-full bg-gray-300 text-gray-700 py-2 px-4 rounded-lg hover:bg-gray-400 transition-colors">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
}

/**
 * Fechar modal de reautenticação
 */
function closeReauthModal() {
    const modal = document.getElementById('reauthModal');
    if (modal) {
        modal.remove();
    }
}

/**
 * Redirecionar para login
 */
function redirectToLogin() {
    // Limpar dados locais
    localStorage.removeItem('userData');
    sessionStorage.removeItem('userData');
    
    // Redirecionar para login
    window.location.href = 'login.html';
}

/**
 * Função de verificação de autenticação corrigida
 */
async function checkAuthenticationFixed() {
    try {
        // Verificar autenticação Supabase primeiro
        const authResult = await checkAndFixAuthentication();
        
        if (authResult) {
            return true;
        }
        
        // Se não conseguiu autenticar no Supabase, verificar dados locais
        const userData = localStorage.getItem('userData') || sessionStorage.getItem('userData');
        
        if (!userData) {
            console.log('Nenhum dado de usuário encontrado, redirecionando para login');
            window.location.href = 'login.html';
            return false;
        }
        
        try {
            const user = JSON.parse(userData);
            const loginTime = new Date(user.loginTime);
            const now = new Date();
            const hoursSinceLogin = (now - loginTime) / (1000 * 60 * 60);
            
            // Verificar se a sessão local ainda é válida
            const maxHours = localStorage.getItem('userData') ? 24 : 8;
            
            if (hoursSinceLogin >= maxHours) {
                console.log('Sessão local expirada, redirecionando para login');
                localStorage.removeItem('userData');
                sessionStorage.removeItem('userData');
                window.location.href = 'login.html';
                return false;
            }
            
            // Verificar se o usuário está bloqueado
            if (user.is_active === false) {
                console.log('Usuário bloqueado, redirecionando para página de bloqueio');
                window.location.href = 'acesso-bloqueado.html';
                return false;
            }
            
            console.log('Sessão local válida encontrada para usuário:', user.email);
            return true;
            
        } catch (error) {
            console.error('Erro ao verificar dados locais:', error);
            localStorage.removeItem('userData');
            sessionStorage.removeItem('userData');
            window.location.href = 'login.html';
            return false;
        }
        
    } catch (error) {
        console.error('Erro na verificação de autenticação:', error);
        return false;
    }
}

// Exportar funções globalmente
window.authFix = {
    checkAuthenticationFixed,
    syncAuthenticationWithSupabase,
    checkAndFixAuthentication,
    showReauthenticationModal,
    closeReauthModal,
    redirectToLogin
};

console.log('Correções de autenticação carregadas!'); 