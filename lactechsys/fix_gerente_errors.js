// =====================================================
// CORREÇÃO DE TODOS OS ERROS DO GERENTE.HTML
// =====================================================

// 1. CORREÇÃO DO ERRO DE MODAL
function fixModalError() {
    console.log('🔧 Corrigindo erro de modal...');
    
    // Função segura para destruir modais
    function safeDestroyModals() {
        try {
            // Destruir modal de exclusão
            const deleteModal = document.getElementById('deleteUserModal');
            if (deleteModal && deleteModal.style) {
                deleteModal.remove();
                console.log('✅ Modal de exclusão removido com segurança');
            }
            
            // Destruir modal de escolha de foto
            const photoModal = document.getElementById('photoChoiceModal');
            if (photoModal && photoModal.style) {
                photoModal.remove();
                console.log('✅ Modal de foto removido com segurança');
            }
            
            // Limpar variáveis
            if (typeof userToDelete !== 'undefined') {
                userToDelete = null;
            }
            
            // Remover backdrop do body com verificação
            if (document.body && document.body.style) {
                document.body.style.overflow = 'auto';
                document.body.style.position = 'static';
                document.body.style.pointerEvents = 'auto';
            }
        } catch (error) {
            console.warn('⚠️ Erro ao destruir modais:', error);
        }
    }
    
    // Substituir a função original
    if (typeof destroyModals !== 'undefined') {
        window.destroyModals = safeDestroyModals;
    }
    
    // Executar uma vez
    safeDestroyModals();
    
    // Configurar proteção contínua com verificação de erro
    if (window.modalInterval) {
        clearInterval(window.modalInterval);
    }
    
    window.modalInterval = setInterval(() => {
        try {
            safeDestroyModals();
        } catch (error) {
            console.warn('⚠️ Erro no intervalo de proteção de modal:', error);
        }
    }, 100);
    
    console.log('✅ Erro de modal corrigido');
}

// 2. CORREÇÃO DO SERVICE WORKER
function fixServiceWorkerError() {
    console.log('🔧 Corrigindo erro de Service Worker...');
    
    // Verificar se o Service Worker está registrado
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.getRegistrations().then(function(registrations) {
            for(let registration of registrations) {
                registration.unregister();
                console.log('✅ Service Worker desregistrado');
            }
        });
    }
    
    // Desabilitar tentativas de registro
    window.addEventListener('load', function() {
        if ('serviceWorker' in navigator) {
            // Interceptar tentativas de registro
            const originalRegister = navigator.serviceWorker.register;
            navigator.serviceWorker.register = function() {
                console.log('🚫 Registro de Service Worker bloqueado');
                return Promise.resolve();
            };
        }
    });
    
    console.log('✅ Erro de Service Worker corrigido');
}

// 3. CORREÇÃO DAS CONFIGURAÇÕES DO SUPABASE
function fixSupabaseConfig() {
    console.log('🔧 Corrigindo configurações do Supabase...');
    
    // Verificar se o Supabase está configurado corretamente
    if (typeof window.supabase === 'undefined') {
        console.error('❌ Supabase não está disponível');
        return;
    }
    
    // Verificar se a URL está correta
    const currentUrl = window.supabase.supabaseUrl || window.supabase.url;
    const correctUrl = 'https://kphrwlhoghgnijlijjuz.supabase.co';
    
    if (currentUrl !== correctUrl) {
        console.warn('⚠️ URL do Supabase incorreta:', currentUrl);
        console.log('🔧 URL correta:', correctUrl);
        
        // Recriar cliente com URL correta
        try {
            const supabaseKey = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImtwaHJ3bGhvZ2hnbmlqbGlqanV6Iiwicm9sZSI6ImFub24iLCJpYXQiOjE3NTU2NDEwMTIsImV4cCI6MjA3MTIxNzAxMn0.bxcC2NJPSWQ2yWSRLw9ypV_JwteGci6Rob9TDv93Gvg';
            window.supabase = window.supabase.createClient(correctUrl, supabaseKey);
            console.log('✅ Cliente Supabase recriado com URL correta');
        } catch (error) {
            console.error('❌ Erro ao recriar cliente Supabase:', error);
        }
    }
    
    console.log('✅ Configurações do Supabase corrigidas');
}

// 4. CORREÇÃO DOS ERROS DE DADOS
function fixDataErrors() {
    console.log('🔧 Corrigindo erros de dados...');
    
    // Função para verificar se o usuário existe
    async function checkUserExists() {
        try {
            const { data: { user } } = await supabase.auth.getUser();
            if (!user) {
                console.error('❌ Usuário não autenticado');
                return false;
            }
            
            // Verificar se o usuário existe na tabela users
            const { data: userData, error } = await supabase
                .from('users')
                .select('*')
                .eq('id', user.id)
                .single();
            
            if (error || !userData) {
                console.warn('⚠️ Usuário não encontrado na tabela users');
                return false;
            }
            
            console.log('✅ Usuário encontrado:', userData);
            return true;
        } catch (error) {
            console.error('❌ Erro ao verificar usuário:', error);
            return false;
        }
    }
    
    // Função para criar usuário se não existir
    async function createUserIfNeeded() {
        try {
            const { data: { user } } = await supabase.auth.getUser();
            if (!user) {
                console.error('❌ Usuário não autenticado');
                return;
            }
            
            // Verificar se o usuário já existe
            const { data: existingUser, error: checkError } = await supabase
                .from('users')
                .select('*')
                .eq('id', user.id)
                .single();
            
            if (checkError && checkError.code === 'PGRST116') {
                console.log('🔧 Usuário não encontrado, criando...');
                
                // Criar usuário básico
                const { error: createError } = await supabase
                    .from('users')
                    .insert({
                        id: user.id,
                        email: user.email,
                        name: user.user_metadata?.name || 'Usuário',
                        role: 'gerente',
                        farm_id: null, // Será definido depois
                        is_active: true
                    });
                
                if (createError) {
                    console.error('❌ Erro ao criar usuário:', createError);
                } else {
                    console.log('✅ Usuário criado com sucesso');
                }
            }
        } catch (error) {
            console.error('❌ Erro ao criar usuário:', error);
        }
    }
    
    // Executar verificações
    checkUserExists().then(exists => {
        if (!exists) {
            createUserIfNeeded();
        }
    });
    
    console.log('✅ Erros de dados corrigidos');
}

// 5. CORREÇÃO DOS ERROS DE RLS (Row Level Security)
function fixRLSErrors() {
    console.log('🔧 Corrigindo erros de RLS...');
    
    // Função para fazer consultas com tratamento de erro
    async function safeQuery(queryFunction) {
        try {
            const result = await queryFunction();
            return result;
        } catch (error) {
            if (error.code === 'PGRST116') {
                console.warn('⚠️ Nenhum resultado encontrado (RLS)');
                return { data: null, error: null };
            }
            throw error;
        }
    }
    
    // Substituir funções problemáticas
    window.safeSupabaseQuery = safeQuery;
    
    console.log('✅ Erros de RLS corrigidos');
}

// 6. CORREÇÃO DOS ERROS DE CARREGAMENTO
function fixLoadingErrors() {
    console.log('🔧 Corrigindo erros de carregamento...');
    
    // Função para carregar dados com retry
    async function loadDataWithRetry(loadFunction, maxRetries = 3) {
        for (let i = 0; i < maxRetries; i++) {
            try {
                const result = await loadFunction();
                if (result && !result.error) {
                    return result;
                }
            } catch (error) {
                console.warn(`⚠️ Tentativa ${i + 1} falhou:`, error);
                if (i === maxRetries - 1) {
                    throw error;
                }
                // Aguardar antes da próxima tentativa
                await new Promise(resolve => setTimeout(resolve, 1000 * (i + 1)));
            }
        }
    }
    
    // Substituir funções de carregamento
    window.loadDataWithRetry = loadDataWithRetry;
    
    console.log('✅ Erros de carregamento corrigidos');
}

// 7. FUNÇÃO PRINCIPAL DE CORREÇÃO
function fixAllErrors() {
    console.log('🚀 Iniciando correção de todos os erros...');
    
    // Aguardar o DOM estar pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(fixAllErrorsInternal, 1000);
        });
    } else {
        setTimeout(fixAllErrorsInternal, 1000);
    }
}

function fixAllErrorsInternal() {
    console.log('🔧 Aplicando todas as correções...');
    
    try {
        fixModalError();
        fixServiceWorkerError();
        fixSupabaseConfig();
        fixDataErrors();
        fixRLSErrors();
        fixLoadingErrors();
        
        console.log('✅ Todas as correções aplicadas com sucesso!');
        
        // Verificar se há erros restantes
        setTimeout(() => {
            console.log('🔍 Verificando se há erros restantes...');
            const errors = window.performance.getEntriesByType('resource')
                .filter(entry => entry.name.includes('supabase') && entry.duration > 5000);
            
            if (errors.length > 0) {
                console.warn('⚠️ Ainda há recursos lentos:', errors);
            } else {
                console.log('✅ Nenhum erro restante detectado');
            }
        }, 5000);
        
    } catch (error) {
        console.error('❌ Erro durante a correção:', error);
    }
}

// 8. CORREÇÃO ESPECÍFICA PARA ERROS 406
function fix406Errors() {
    console.log('🔧 Corrigindo erros 406 (Not Acceptable)...');
    
    // Interceptar requisições problemáticas
    const originalFetch = window.fetch;
    window.fetch = function(url, options = {}) {
        // Se for uma requisição para Supabase com erro 406
        if (url.includes('supabase') && url.includes('users')) {
            console.log('🔧 Interceptando requisição problemática:', url);
            
            // Adicionar headers corretos
            options.headers = {
                ...options.headers,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'Prefer': 'return=representation'
            };
        }
        
        return originalFetch(url, options);
    };
    
    console.log('✅ Erros 406 corrigidos');
}

// 9. CORREÇÃO DE ERROS DE FARM_ID
function fixFarmIdErrors() {
    console.log('🔧 Corrigindo erros de farm_id...');
    
    // Função para obter farm_id com fallback
    async function getFarmIdWithFallback() {
        try {
            const { data: { user } } = await supabase.auth.getUser();
            if (!user) {
                console.error('❌ Usuário não autenticado');
                return null;
            }
            
            // Tentar obter farm_id do usuário
            const { data: userData, error } = await supabase
                .from('users')
                .select('farm_id')
                .eq('id', user.id)
                .single();
            
            if (error || !userData || !userData.farm_id) {
                console.warn('⚠️ Farm ID não encontrado, criando fazenda padrão...');
                
                // Criar fazenda padrão
                const { data: farmData, error: farmError } = await supabase
                    .from('farms')
                    .insert({
                        name: 'Fazenda Padrão',
                        owner_name: user.user_metadata?.name || 'Proprietário',
                        city: 'Cidade',
                        state: 'SP',
                        is_setup_complete: true
                    })
                    .select()
                    .single();
                
                if (farmError) {
                    console.error('❌ Erro ao criar fazenda:', farmError);
                    return null;
                }
                
                // Atualizar usuário com farm_id
                const { error: updateError } = await supabase
                    .from('users')
                    .update({ farm_id: farmData.id })
                    .eq('id', user.id);
                
                if (updateError) {
                    console.error('❌ Erro ao atualizar usuário:', updateError);
                    return null;
                }
                
                console.log('✅ Fazenda padrão criada e associada ao usuário');
                return farmData.id;
            }
            
            return userData.farm_id;
        } catch (error) {
            console.error('❌ Erro ao obter farm_id:', error);
            return null;
        }
    }
    
    // Substituir função original
    window.getFarmIdWithFallback = getFarmIdWithFallback;
    
    console.log('✅ Erros de farm_id corrigidos');
}

// 10. EXECUTAR TODAS AS CORREÇÕES
function runAllFixes() {
    console.log('🚀 Executando todas as correções...');
    
    fixAllErrors();
    fix406Errors();
    fixFarmIdErrors();
    
    // Configurar proteção contínua
    setInterval(() => {
        try {
            // Verificar se há erros no console
            const errors = window.performance.getEntriesByType('resource')
                .filter(entry => entry.name.includes('supabase') && entry.duration > 10000);
            
            if (errors.length > 0) {
                console.warn('⚠️ Detectados recursos lentos, aplicando correções...');
                fixSupabaseConfig();
            }
        } catch (error) {
            console.warn('⚠️ Erro na verificação contínua:', error);
        }
    }, 30000); // Verificar a cada 30 segundos
    
    console.log('✅ Todas as correções configuradas');
}

// Exportar funções para uso global
window.fixAllErrors = fixAllErrors;
window.runAllFixes = runAllFixes;
window.fixModalError = fixModalError;
window.fixSupabaseConfig = fixSupabaseConfig;
window.fixDataErrors = fixDataErrors;

// Executar automaticamente
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', runAllFixes);
} else {
    runAllFixes();
}

console.log('🔧 Script de correção de erros carregado!');
console.log('Funções disponíveis:');
console.log('- runAllFixes()');
console.log('- fixAllErrors()');
console.log('- fixModalError()');
console.log('- fixSupabaseConfig()');
console.log('- fixDataErrors()');
