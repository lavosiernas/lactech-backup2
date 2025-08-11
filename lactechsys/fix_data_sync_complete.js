// =====================================================
// CORREÇÃO DE SINCRONIZAÇÃO DE DADOS - LACTECH
// Resolve problemas de autenticação e permissões
// =====================================================

/**
 * Verificar e corrigir problemas de autenticação
 */
async function checkAndFixAuthIssues() {
    try {
        console.log('Verificando problemas de autenticação...');
        
        // Aguardar Supabase estar configurado com verificação melhorada
        let attempts = 0;
        const maxAttempts = 5; // Reduzido para 5 tentativas
        
        while (!window.supabase || typeof window.supabase.createClient === 'undefined') {
            if (attempts >= maxAttempts) {
                console.error('Cliente Supabase não está configurado. Verifique se a biblioteca foi carregada corretamente.');
                return false;
            }
            
            console.log(`Aguardando Supabase... tentativa ${attempts + 1}/${maxAttempts}`);
            await new Promise(resolve => setTimeout(resolve, 1000)); // Aumentado para 1 segundo
            attempts++;
        }
        
        // Verificar autenticação atual
        const { data: { user }, error } = await window.supabase.auth.getUser();
        
        if (error) {
            console.error('Erro ao verificar usuário:', error);
            
            // Tentar recuperar dados locais
            const localUserData = localStorage.getItem('userData') || sessionStorage.getItem('userData');
            
            if (localUserData) {
                try {
                    const userData = JSON.parse(localUserData);
                    console.log('Dados locais encontrados, tentando sincronizar...');
                    
                    // Tentar fazer login com dados locais
                    const { data: authData, error: loginError } = await window.supabase.auth.signInWithPassword({
                        email: userData.email,
                        password: userData.password || 'temp_password'
                    });
                    
                    if (loginError) {
                        console.log('Não foi possível fazer login automático, redirecionando...');
                        window.location.href = 'login.html';
                        return false;
                    }
                    
                    console.log('Login automático realizado com sucesso');
                    return true;
                    
                } catch (parseError) {
                    console.error('Erro ao parsear dados locais:', parseError);
                    localStorage.removeItem('userData');
                    sessionStorage.removeItem('userData');
                    window.location.href = 'login.html';
                    return false;
                }
            } else {
                console.log('Nenhum dado de usuário encontrado, redirecionando para login');
                window.location.href = 'login.html';
                return false;
            }
        }
        
        if (!user) {
            console.log('Usuário não autenticado, redirecionando para login');
            window.location.href = 'login.html';
            return false;
        }
        
        console.log('Usuário autenticado:', user.email);
        return true;
        
    } catch (error) {
        console.error('Erro na verificação de autenticação:', error);
        return false;
    }
}

/**
 * Interceptar e corrigir erros 403 automaticamente
 */
function interceptAndFix403Errors() {
    try {
        // Interceptar erros globais
        window.addEventListener('unhandledrejection', function(event) {
            const error = event.reason;
            
            // Verificar se é um erro 403 ou erro do Supabase
            if (error && (
                error.code === 403 || 
                error.message?.includes('403') || 
                error.name === 'i' ||
                error.httpStatus === 403 ||
                error.httpError === false && error.httpStatus === 200 && error.code === 403
            )) {
                console.log('Erro 403 interceptado, tentando corrigir...', error);
                event.preventDefault();
                
                // Tentar corrigir o erro
                setTimeout(async () => {
                    try {
                        await checkAndFixSupabaseIssues();
                        await checkAndFixAuthIssues();
                        await checkAndFixRLSIssues();
                    } catch (fixError) {
                        console.error('Erro ao corrigir problema 403:', fixError);
                    }
                }, 100);
            }
        });
        
        // Interceptar erros de fetch
        const originalFetch = window.fetch;
        window.fetch = async function(...args) {
            try {
                const response = await originalFetch(...args);
                
                // Se for erro 403, tentar corrigir
                if (response.status === 403) {
                    console.log('Erro 403 detectado em fetch, tentando corrigir...');
                    setTimeout(async () => {
                        try {
                            await checkAndFixSupabaseIssues();
                            await checkAndFixAuthIssues();
                            await checkAndFixRLSIssues();
                        } catch (fixError) {
                            console.error('Erro ao corrigir problema 403:', fixError);
                        }
                    }, 100);
                }
                
                return response;
            } catch (error) {
                if (error.message?.includes('403')) {
                    console.log('Erro 403 detectado em fetch, tentando corrigir...');
                    setTimeout(async () => {
                        try {
                            await checkAndFixSupabaseIssues();
                            await checkAndFixAuthIssues();
                            await checkAndFixRLSIssues();
                        } catch (fixError) {
                            console.error('Erro ao corrigir problema 403:', fixError);
                        }
                    }, 100);
                }
                throw error;
            }
        };
        
        // Interceptar erros específicos do Supabase
        if (window.supabase && window.supabase.auth) {
            const originalAuth = window.supabase.auth;
            
            // Interceptar chamadas de autenticação
            const originalGetUser = originalAuth.getUser;
            originalAuth.getUser = async function() {
                try {
                    const result = await originalGetUser.call(this);
                    return result;
                } catch (error) {
                    if (error.code === 403 || error.message?.includes('403')) {
                        console.log('Erro 403 no Supabase Auth, tentando corrigir...');
                        await checkAndFixSupabaseIssues();
                        return await originalGetUser.call(this);
                    }
                    throw error;
                }
            };
        }
        
        console.log('Interceptador de erros 403 configurado');
        
    } catch (error) {
        console.error('Erro ao configurar interceptador:', error);
    }
}

/**
 * Verificar e corrigir problemas de permissões RLS
 */
async function checkAndFixRLSIssues() {
    try {
        console.log('Verificando problemas de permissões RLS...');
        
        // Testar acesso básico às tabelas
        const { data: { user } } = await window.supabase.auth.getUser();
        if (!user) {
            console.error('Usuário não autenticado para verificar RLS');
            return false;
        }
        
        // Testar acesso à tabela users
        const { data: userData, error: userError } = await window.supabase
            .from('users')
            .select('id, name, email, role, farm_id')
            .eq('id', user.id)
            .single();
        
        if (userError) {
            console.error('Erro ao acessar tabela users:', userError);
            
            // Se for erro 403, pode ser problema de RLS
            if (userError.code === '403' || userError.message.includes('403')) {
                console.log('Problema de permissão RLS detectado, tentando corrigir...');
                
                // Tentar recarregar a sessão
                const { data: sessionData, error: sessionError } = await window.supabase.auth.getSession();
                
                if (sessionError) {
                    console.error('Erro ao obter sessão:', sessionError);
                    return false;
                }
                
                if (!sessionData.session) {
                    console.log('Sessão inválida, redirecionando para login');
                    window.location.href = 'login.html';
                    return false;
                }
                
                console.log('Sessão válida encontrada, tentando novamente...');
                
                // Tentar novamente após verificar sessão
                const { data: retryData, error: retryError } = await window.supabase
                    .from('users')
                    .select('id, name, email, role, farm_id')
                    .eq('id', user.id)
                    .single();
                
                if (retryError) {
                    console.error('Erro persistente ao acessar tabela users:', retryError);
                    return false;
                }
                
                console.log('Acesso à tabela users restaurado');
                return true;
            }
            
            return false;
        }
        
        console.log('Acesso às tabelas verificado com sucesso');
        return true;
        
    } catch (error) {
        console.error('Erro na verificação de RLS:', error);
        return false;
    }
}

/**
 * Verificar e corrigir problemas específicos do Supabase
 */
async function checkAndFixSupabaseIssues() {
    try {
        console.log('Verificando problemas específicos do Supabase...');
        
        // Aguardar até que o Supabase esteja configurado
        let attempts = 0;
        const maxAttempts = 10;
        
        while (!window.supabase || !window.supabase.auth) {
            attempts++;
            console.log(`Aguardando configuração do Supabase... Tentativa ${attempts}/${maxAttempts}`);
            
            if (attempts >= maxAttempts) {
                console.error('Cliente Supabase não está configurado corretamente após várias tentativas');
                return false;
            }
            
            // Aguardar 500ms antes da próxima tentativa
            await new Promise(resolve => setTimeout(resolve, 500));
        }
        
        console.log('Supabase configurado corretamente');
        
        // Verificar se há uma sessão ativa
        const { data: sessionData, error: sessionError } = await window.supabase.auth.getSession();
        
        if (sessionError) {
            console.error('Erro ao obter sessão:', sessionError);
            return false;
        }
        
        if (!sessionData.session) {
            console.log('Nenhuma sessão ativa encontrada');
            
            // Tentar recuperar sessão do localStorage
            const storedSession = localStorage.getItem('supabase.auth.token');
            if (storedSession) {
                try {
                    const session = JSON.parse(storedSession);
                    console.log('Sessão encontrada no localStorage, tentando restaurar...');
                    
                    // Tentar restaurar a sessão
                    const { data: restoreData, error: restoreError } = await window.supabase.auth.setSession(session);
                    
                    if (restoreError) {
                        console.error('Erro ao restaurar sessão:', restoreError);
                        localStorage.removeItem('supabase.auth.token');
                        return false;
                    }
                    
                    console.log('Sessão restaurada com sucesso');
                    return true;
                    
                } catch (parseError) {
                    console.error('Erro ao parsear sessão:', parseError);
                    localStorage.removeItem('supabase.auth.token');
                    return false;
                }
            }
            
            return false;
        }
        
        console.log('Sessão Supabase válida encontrada');
        return true;
        
    } catch (error) {
        console.error('Erro na verificação do Supabase:', error);
        return false;
    }
}

/**
 * Sincronizar dados locais com Supabase
 */
async function syncLocalDataWithSupabase() {
    try {
        console.log('Sincronizando dados locais...');
        
        const localUserData = localStorage.getItem('userData') || sessionStorage.getItem('userData');
        
        if (!localUserData) {
            console.log('Nenhum dado local para sincronizar');
            return true;
        }
        
        const userData = JSON.parse(localUserData);
        
        // Verificar se os dados locais são válidos
        if (!userData.email || !userData.id) {
            console.log('Dados locais inválidos, removendo...');
            localStorage.removeItem('userData');
            sessionStorage.removeItem('userData');
            return true;
        }
        
        // Verificar se o usuário está autenticado no Supabase
        const { data: { user } } = await window.supabase.auth.getUser();
        
        if (!user || user.email !== userData.email) {
            console.log('Usuário não autenticado ou diferente, removendo dados locais');
            localStorage.removeItem('userData');
            sessionStorage.removeItem('userData');
            return true;
        }
        
        console.log('Dados locais sincronizados com sucesso');
        return true;
        
    } catch (error) {
        console.error('Erro na sincronização de dados:', error);
        return false;
    }
}

/**
 * Função principal de correção
 */
async function fixDataSyncIssues() {
    try {
        console.log('Iniciando correção de problemas de sincronização...');
        
        // 1. Verificar e corrigir problemas específicos do Supabase
        const supabaseFixed = await checkAndFixSupabaseIssues();
        if (!supabaseFixed) {
            console.log('Problemas do Supabase não puderam ser corrigidos');
            return false;
        }
        
        // 2. Verificar e corrigir autenticação
        const authFixed = await checkAndFixAuthIssues();
        if (!authFixed) {
            console.log('Problemas de autenticação não puderam ser corrigidos');
            return false;
        }
        
        // 3. Verificar e corrigir problemas de RLS
        const rlsFixed = await checkAndFixRLSIssues();
        if (!rlsFixed) {
            console.log('Problemas de RLS não puderam ser corrigidos');
            return false;
        }
        
        // 4. Sincronizar dados locais
        const syncFixed = await syncLocalDataWithSupabase();
        if (!syncFixed) {
            console.log('Problemas de sincronização não puderam ser corrigidos');
            return false;
        }
        
        console.log('Todos os problemas de sincronização foram corrigidos!');
        return true;
        
    } catch (error) {
        console.error('Erro na correção de problemas:', error);
        return false;
    }
}

/**
 * Inicializar correções automaticamente
 */
async function initializeDataSyncFix() {
    try {
        console.log('Inicializando correções de sincronização...');
        
        // Configurar interceptador de erros 403 imediatamente
        interceptAndFix403Errors();
        
        // Aguardar até que o Supabase esteja configurado
        let attempts = 0;
        const maxAttempts = 5; // Reduzido para 5 tentativas
        
        while (!window.supabase || !window.supabase.auth || typeof window.supabase.createClient === 'undefined') {
            if (attempts >= maxAttempts) {
                console.error('Cliente Supabase não está configurado. Verifique se supabase_config_fixed.js foi carregado corretamente.');
                return false;
            }
            
            console.log(`Aguardando inicialização do Supabase... tentativa ${attempts + 1}/${maxAttempts}`);
            await new Promise(resolve => setTimeout(resolve, 1000)); // Aumentado para 1 segundo
            attempts++;
        }
        
        console.log('Supabase configurado, executando correções...');
        
        const fixed = await fixDataSyncIssues();
        
        if (fixed) {
            console.log('Correções aplicadas com sucesso!');
        } else {
            console.log('Alguns problemas não puderam ser corrigidos automaticamente');
        }
        
        return fixed;
        
    } catch (error) {
        console.error('Erro na inicialização das correções:', error);
        return false;
    }
}

// Exportar funções globalmente
window.DataSyncFix = {
    fixDataSyncIssues,
    checkAndFixAuthIssues,
    checkAndFixRLSIssues,
    checkAndFixSupabaseIssues,
    syncLocalDataWithSupabase,
    initializeDataSyncFix,
    interceptAndFix403Errors
};

// Executar correções automaticamente quando o script for carregado
document.addEventListener('DOMContentLoaded', function() {
    initializeDataSyncFix();
});

console.log('Correções de sincronização de dados carregadas!');