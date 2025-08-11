// =====================================================
// CONFIGURAÇÃO DO SUPABASE PARA SISTEMA DE PAGAMENTOS
// =====================================================

// Configurações do Supabase
const SUPABASE_URL = 'https://njnusdzwvxpsxhcspsop.supabase.co';
const SUPABASE_ANON_KEY = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Im5qbnVzZHp3dnhwc3hoY3Nwc29wIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NTQ4NTY2MjIsImV4cCI6MjA3MDQzMjYyMn0.fmOK6xVEECzQmjTQAUm3Ct0UkNXirabMLlM96wnAQOk';

// Inicializar cliente Supabase
let paymentSupabase;

// Função para inicializar Supabase
function initializeSupabase() {
    try {
        // Verificar se o Supabase está disponível
        if (typeof window.supabase !== 'undefined' && window.supabase.createClient) {
            paymentSupabase = window.supabase.createClient(SUPABASE_URL, SUPABASE_ANON_KEY);
            console.log('✅ Supabase configurado com sucesso para pagamentos');
            return true;
        } else if (typeof createClient !== 'undefined') {
            paymentSupabase = createClient(SUPABASE_URL, SUPABASE_ANON_KEY);
            console.log('✅ Supabase configurado com sucesso para pagamentos');
            return true;
        } else {
            console.warn('⚠️ Supabase não disponível ainda, aguardando...');
            return false;
        }
    } catch (error) {
        console.error('❌ Erro ao configurar Supabase:', error);
        return false;
    }
}

// Tentar inicializar imediatamente
if (!initializeSupabase()) {
    // Se falhou, aguardar e tentar novamente
    document.addEventListener('DOMContentLoaded', function() {
        if (!initializeSupabase()) {
            // Tentar novamente após um delay
            setTimeout(() => {
                initializeSupabase();
            }, 1000);
        }
    });
}

// Função para obter instância do Supabase
function getPaymentSupabase() {
    if (!paymentSupabase) {
        console.warn('⚠️ Supabase não inicializado, tentando novamente...');
        if (!initializeSupabase()) {
            console.error('❌ Não foi possível inicializar Supabase');
            return null;
        }
    }
    return paymentSupabase;
}

// Função para verificar conexão
async function testSupabaseConnection() {
    try {
        const supabase = getPaymentSupabase();
        if (!supabase) {
            throw new Error('Supabase não inicializado');
        }

        // Testar conexão fazendo uma consulta simples
        const { data, error } = await supabase
            .from('pix_payments')
            .select('count')
            .limit(1);

        if (error) {
            console.warn('⚠️ Tabela pix_payments pode não existir ainda:', error.message);
            return { connected: true, tablesExist: false };
        }

        console.log('✅ Conexão Supabase OK - Tabelas existem');
        return { connected: true, tablesExist: true };
    } catch (error) {
        console.error('❌ Erro ao testar conexão Supabase:', error);
        return { connected: false, tablesExist: false };
    }
}

// Função para criar pagamento no banco
async function createPaymentInDatabase(paymentData) {
    try {
        const supabase = getPaymentSupabase();
        if (!supabase) {
            throw new Error('Supabase não inicializado');
        }

        const { data, error } = await supabase
            .from('pix_payments')
            .insert([paymentData])
            .select()
            .single();

        if (error) {
            console.error('❌ Erro ao criar pagamento:', error);
            throw error;
        }

        console.log('✅ Pagamento criado no banco:', data);
        return data;
    } catch (error) {
        console.error('❌ Erro ao criar pagamento no banco:', error);
        throw error;
    }
}

// Função para verificar status do pagamento
async function checkPaymentStatus(txid) {
    try {
        const supabase = getPaymentSupabase();
        if (!supabase) {
            throw new Error('Supabase não inicializado');
        }

        const { data, error } = await supabase
            .from('pix_payments')
            .select('*')
            .eq('txid', txid)
            .single();

        if (error) {
            console.error('❌ Erro ao buscar pagamento:', error);
            return null;
        }

        return data;
    } catch (error) {
        console.error('❌ Erro ao verificar status do pagamento:', error);
        return null;
    }
}

// Função para atualizar status do pagamento
async function updatePaymentStatus(txid, status) {
    try {
        const supabase = getPaymentSupabase();
        if (!supabase) {
            throw new Error('Supabase não inicializado');
        }

        const { data, error } = await supabase
            .from('pix_payments')
            .update({ status: status })
            .eq('txid', txid)
            .select()
            .single();

        if (error) {
            console.error('❌ Erro ao atualizar pagamento:', error);
            throw error;
        }

        console.log('✅ Status do pagamento atualizado:', data);
        return data;
    } catch (error) {
        console.error('❌ Erro ao atualizar status do pagamento:', error);
        throw error;
    }
}

// Função para criar assinatura
async function createSubscription(subscriptionData) {
    try {
        const supabase = getPaymentSupabase();
        if (!supabase) {
            throw new Error('Supabase não inicializado');
        }

        const { data, error } = await supabase
            .from('subscriptions')
            .insert([subscriptionData])
            .select()
            .single();

        if (error) {
            console.error('❌ Erro ao criar assinatura:', error);
            throw error;
        }

        console.log('✅ Assinatura criada:', data);
        return data;
    } catch (error) {
        console.error('❌ Erro ao criar assinatura:', error);
        throw error;
    }
}

// Função para buscar assinaturas do usuário
async function getUserSubscriptions() {
    try {
        const supabase = getPaymentSupabase();
        if (!supabase) {
            throw new Error('Supabase não inicializado');
        }

        const { data, error } = await supabase
            .rpc('get_user_subscriptions');

        if (error) {
            console.error('❌ Erro ao buscar assinaturas:', error);
            return [];
        }

        return data || [];
    } catch (error) {
        console.error('❌ Erro ao buscar assinaturas do usuário:', error);
        return [];
    }
}

// Função para buscar pagamentos do usuário
async function getUserPayments() {
    try {
        const supabase = getPaymentSupabase();
        if (!supabase) {
            throw new Error('Supabase não inicializado');
        }

        const { data, error } = await supabase
            .rpc('get_user_payments');

        if (error) {
            console.error('❌ Erro ao buscar pagamentos:', error);
            return [];
        }

        return data || [];
    } catch (error) {
        console.error('❌ Erro ao buscar pagamentos do usuário:', error);
        return [];
    }
}

// Exportar para uso global
window.paymentSupabase = paymentSupabase;
window.getPaymentSupabase = getPaymentSupabase;
window.testSupabaseConnection = testSupabaseConnection;
window.createPaymentInDatabase = createPaymentInDatabase;
window.checkPaymentStatus = checkPaymentStatus;
window.updatePaymentStatus = updatePaymentStatus;
window.createSubscription = createSubscription;
window.getUserSubscriptions = getUserSubscriptions;
window.getUserPayments = getUserPayments;

// Testar conexão automaticamente
document.addEventListener('DOMContentLoaded', async function() {
    console.log('🔧 Testando conexão com Supabase...');
    const connectionStatus = await testSupabaseConnection();
    
    if (connectionStatus.connected) {
        console.log('✅ Supabase conectado com sucesso');
        if (!connectionStatus.tablesExist) {
            console.warn('⚠️ Tabelas não existem - execute o SQL de configuração primeiro');
        }
    } else {
        console.error('❌ Falha na conexão com Supabase');
    }
});

console.log('📦 Configuração do Supabase para pagamentos carregada');