// =====================================================
// CORREÇÃO DA URL INCORRETA DO SUPABASE
// Problema: URL njnusdzwvxpsxhcspsop está sendo usada em vez de kphrwlhoghgnijlijjuz
// =====================================================

// Configurações corretas do Supabase
const CORRECT_SUPABASE_URL = 'https://kphrwlhoghgnijlijjuz.supabase.co';
const CORRECT_SUPABASE_KEY = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImtwaHJ3bGhvZ2hnbmlqbGlqanV6Iiwicm9sZSI6ImFub24iLCJpYXQiOjE3NTU2NDEwMTIsImV4cCI6MjA3MTIxNzAxMn0.bxcC2NJPSWQ2yWSRLw9ypV_JwteGci6Rob9TDv93Gvg';

// Função para corrigir a URL do Supabase
function fixSupabaseUrl() {
    console.log('🔧 Corrigindo URL do Supabase...');
    
    try {
        // Verificar se o Supabase está disponível
        if (typeof window.supabase === 'undefined') {
            console.error('❌ Supabase não está disponível');
            return false;
        }
        
        // Verificar se a URL atual está incorreta
        const currentUrl = window.supabase.supabaseUrl || 
                          window.supabase.url || 
                          window.supabase.supabaseURL;
        
        console.log('📊 URL atual:', currentUrl);
        console.log('📊 URL correta:', CORRECT_SUPABASE_URL);
        
        if (currentUrl && currentUrl.includes('njnusdzwvxpsxhcspsop')) {
            console.warn('⚠️ URL incorreta detectada, corrigindo...');
            
            // Recriar cliente Supabase com URL correta
            try {
                // Remover cliente atual
                delete window.supabase;
                
                // Recriar com URL correta
                window.supabase = window.supabase.createClient(CORRECT_SUPABASE_URL, CORRECT_SUPABASE_KEY);
                
                console.log('✅ Cliente Supabase recriado com URL correta');
                return true;
            } catch (error) {
                console.error('❌ Erro ao recriar cliente Supabase:', error);
                return false;
            }
        } else if (currentUrl && currentUrl.includes('kphrwlhoghgnijlijjuz')) {
            console.log('✅ URL já está correta');
            return true;
        } else {
            console.warn('⚠️ URL não reconhecida, recriando cliente...');
            
            try {
                window.supabase = window.supabase.createClient(CORRECT_SUPABASE_URL, CORRECT_SUPABASE_KEY);
                console.log('✅ Cliente Supabase criado com URL correta');
                return true;
            } catch (error) {
                console.error('❌ Erro ao criar cliente Supabase:', error);
                return false;
            }
        }
    } catch (error) {
        console.error('❌ Erro ao corrigir URL do Supabase:', error);
        return false;
    }
}

// Função para interceptar requisições HTTP incorretas
function interceptIncorrectRequests() {
    console.log('🔧 Configurando interceptação de requisições...');
    
    // Interceptar fetch
    const originalFetch = window.fetch;
    window.fetch = function(url, options = {}) {
        // Se a URL contém a URL incorreta, substituir
        if (typeof url === 'string' && url.includes('njnusdzwvxpsxhcspsop')) {
            const correctedUrl = url.replace('njnusdzwvxpsxhcspsop', 'kphrwlhoghgnijlijjuz');
            console.log('🔧 URL corrigida:', url, '→', correctedUrl);
            return originalFetch(correctedUrl, options);
        }
        
        return originalFetch(url, options);
    };
    
    // Interceptar XMLHttpRequest
    const originalXHROpen = XMLHttpRequest.prototype.open;
    XMLHttpRequest.prototype.open = function(method, url, ...args) {
        if (typeof url === 'string' && url.includes('njnusdzwvxpsxhcspsop')) {
            const correctedUrl = url.replace('njnusdzwvxpsxhcspsop', 'kphrwlhoghgnijlijjuz');
            console.log('🔧 XHR URL corrigida:', url, '→', correctedUrl);
            return originalXHROpen.call(this, method, correctedUrl, ...args);
        }
        
        return originalXHROpen.call(this, method, url, ...args);
    };
    
    console.log('✅ Interceptação de requisições configurada');
}

// Função para testar a conexão
async function testSupabaseConnection() {
    console.log('🔍 Testando conexão com Supabase...');
    
    try {
        if (!window.supabase) {
            console.error('❌ Supabase não está disponível');
            return false;
        }
        
        // Testar conexão fazendo uma consulta simples
        const { data, error } = await window.supabase
            .from('farms')
            .select('count')
            .limit(1);
        
        if (error) {
            console.error('❌ Erro na conexão:', error);
            return false;
        }
        
        console.log('✅ Conexão com Supabase funcionando');
        return true;
    } catch (error) {
        console.error('❌ Erro ao testar conexão:', error);
        return false;
    }
}

// Função para corrigir erros 406
function fix406Errors() {
    console.log('🔧 Corrigindo erros 406...');
    
    // Interceptar requisições que podem causar erro 406
    const originalFetch = window.fetch;
    window.fetch = function(url, options = {}) {
        // Se for uma requisição para Supabase com users
        if (typeof url === 'string' && url.includes('supabase') && url.includes('users')) {
            console.log('🔧 Interceptando requisição para users:', url);
            
            // Adicionar headers corretos
            options.headers = {
                ...options.headers,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'Prefer': 'return=representation'
            };
            
            // Garantir que a URL está correta
            if (url.includes('njnusdzwvxpsxhcspsop')) {
                url = url.replace('njnusdzwvxpsxhcspsop', 'kphrwlhoghgnijlijjuz');
            }
        }
        
        return originalFetch(url, options);
    };
    
    console.log('✅ Erros 406 corrigidos');
}

// Função principal de correção
function fixSupabaseIssues() {
    console.log('🚀 Iniciando correção de problemas do Supabase...');
    
    // Aguardar o DOM estar pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(runSupabaseFixes, 1000);
        });
    } else {
        setTimeout(runSupabaseFixes, 1000);
    }
}

async function runSupabaseFixes() {
    console.log('🔧 Aplicando correções do Supabase...');
    
    try {
        // 1. Corrigir URL
        const urlFixed = fixSupabaseUrl();
        
        // 2. Interceptar requisições incorretas
        interceptIncorrectRequests();
        
        // 3. Corrigir erros 406
        fix406Errors();
        
        // 4. Testar conexão
        if (urlFixed) {
            const connectionOk = await testSupabaseConnection();
            if (connectionOk) {
                console.log('✅ Todos os problemas do Supabase corrigidos!');
            } else {
                console.warn('⚠️ Conexão com Supabase ainda com problemas');
            }
        }
        
        // 5. Configurar proteção contínua
        setInterval(() => {
            try {
                // Verificar se há requisições para URL incorreta
                const performanceEntries = window.performance.getEntriesByType('resource');
                const incorrectRequests = performanceEntries.filter(entry => 
                    entry.name.includes('njnusdzwvxpsxhcspsop')
                );
                
                if (incorrectRequests.length > 0) {
                    console.warn('⚠️ Detectadas requisições para URL incorreta, aplicando correção...');
                    fixSupabaseUrl();
                }
            } catch (error) {
                console.warn('⚠️ Erro na verificação contínua:', error);
            }
        }, 10000); // Verificar a cada 10 segundos
        
    } catch (error) {
        console.error('❌ Erro durante correção do Supabase:', error);
    }
}

// Exportar funções para uso global
window.fixSupabaseUrl = fixSupabaseUrl;
window.fixSupabaseIssues = fixSupabaseIssues;
window.testSupabaseConnection = testSupabaseConnection;

// Executar automaticamente
fixSupabaseIssues();

console.log('🔧 Script de correção do Supabase carregado!');
console.log('Funções disponíveis:');
console.log('- fixSupabaseUrl()');
console.log('- fixSupabaseIssues()');
console.log('- testSupabaseConnection()');
