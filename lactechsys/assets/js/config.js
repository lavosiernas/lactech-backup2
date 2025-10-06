// =====================================================
// CONFIGURAÇÃO SUPABASE - LACTECH
// =====================================================
// Arquivo único com todas as funcionalidades
// =====================================================

// Configuração do Supabase
const SUPABASE_URL = 'https://tmaamwuyucaspqcrhuck.supabase.co';
const SUPABASE_ANON_KEY = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InRtYWFtd3V5dWNhc3BxY3JodWNrIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NTY2OTY1MzMsImV4cCI6MjA3MjI3MjUzM30.AdDXp0xrX_xKutFHQrJ47LhFdLTtanTSku7fcK1eTB0';

// =====================================================
// CONFIGURAÇÃO DE FAZENDA ÚNICA - LAGOA DO MATO
// =====================================================
// Sistema configurado para operar apenas com a fazenda Lagoa do Mato
// Para ativar multi-fazendas no futuro, altere SINGLE_FARM_ID para null
const SINGLE_FARM_ID = null; // Será definido automaticamente pela primeira fazenda encontrada
const SINGLE_FARM_NAME = 'Lagoa do Mato'; // Nome fixo da fazenda

// Aguardar Supabase estar disponível
async function waitForSupabase() {
    return new Promise((resolve) => {
        const checkSupabase = () => {
            if (window.supabase) {
                resolve(window.supabase);
            } else {
                setTimeout(checkSupabase, 50);
            }
        };
        checkSupabase();
    });
}

// Inicializar Supabase e criar API
async function initializeSupabase() {
    const supabaseLib = await waitForSupabase();
    const supabaseClient = supabaseLib.createClient(SUPABASE_URL, SUPABASE_ANON_KEY);
    
    // Função para obter o ID da fazenda (single farm ou do usuário)
    const getFarmId = async () => {
        // Se SINGLE_FARM_ID está definido, usar ele
        if (SINGLE_FARM_ID) {
            return SINGLE_FARM_ID;
        }
        
        // Para Lagoa do Mato, buscar fazenda por nome ou criar se não existir
        try {
            // Primeiro, tentar encontrar a fazenda Lagoa do Mato
            const { data: existingFarm } = await supabaseClient
                .from('farms')
                .select('id')
                .ilike('name', '%Lagoa do Mato%')
                .limit(1)
                .single();
            
            if (existingFarm?.id) {
                return existingFarm.id;
            }
            
            // Se não encontrou, buscar fazenda do usuário atual
            const { data: { user } } = await supabaseClient.auth.getUser();
            if (user) {
                const { data: userData } = await supabaseClient
                    .from('users')
                    .select('farm_id')
                    .eq('id', user.id)
                    .single();
                
                if (userData?.farm_id) {
                    return userData.farm_id;
                }
            }
            
            // Se não encontrou nenhuma, buscar a primeira fazenda disponível
            const { data: firstFarm } = await supabaseClient
                .from('farms')
                .select('id')
                .limit(1)
                .single();
            
            return firstFarm?.id;
        } catch (error) {
            console.error('Erro ao obter farm_id:', error);
            throw new Error('Fazenda não encontrada');
        }
    };
    
    // Criar API unificada
    window.LacTechAPI = {
        auth: {
            getUser: () => supabaseClient.auth.getUser(),
            getSession: () => supabaseClient.auth.getSession(),
            signOut: () => supabaseClient.auth.signOut(),
            signIn: (email, password) => supabaseClient.auth.signInWithPassword({ email, password }),
            signUp: (email, password, data) => supabaseClient.auth.signUp({ email, password, data })
        },
        // Função para obter farm_id
        getFarmId: getFarmId,
        
        // Função para obter nome da fazenda
        getFarmName: async () => {
            // Sempre retorna Lagoa do Mato
            return SINGLE_FARM_NAME || 'Lagoa do Mato';
        },
        
        // Função para garantir que a fazenda Lagoa do Mato existe
        ensureLagoaDoMatoFarm: async () => {
            try {
                // Verificar se já existe
                const { data: existingFarm } = await supabaseClient
                    .from('farms')
                    .select('id')
                    .ilike('name', '%Lagoa do Mato%')
                    .limit(1)
                    .single();
                
                if (existingFarm?.id) {
                    return existingFarm.id;
                }
                
                // Criar a fazenda Lagoa do Mato se não existir
                const { data: newFarm, error } = await supabaseClient
                    .from('farms')
                    .insert({
                        name: 'Lagoa do Mato',
                        city: 'São Paulo',
                        state: 'SP'
                    })
                    .select('id')
                    .single();
                
                if (error) throw error;
                return newFarm.id;
            } catch (error) {
                console.error('Erro ao criar/verificar fazenda Lagoa do Mato:', error);
                throw error;
            }
        },
        
        users: {
            getFarmUsers: async () => {
                try {
                    const farmId = await getFarmId();
                    
                    const { data, error } = await supabaseClient
                        .from('users')
                        .select('*')
                        .eq('farm_id', farmId)
                        .eq('is_active', true)
                        .order('name');
                    if (error) throw error;
                    return { success: true, data };
                } catch (error) {
                    return { success: false, error: error.message };
                }
            },
            getProfile: async () => {
                try {
                    const { data, error } = await supabaseClient.rpc('get_user_profile');
                    if (error) throw error;
                    return { success: true, data: data[0] };
                } catch (error) {
                    return { success: false, error: error.message };
                }
            }
        },
        supabase: supabaseClient
    };
    
    // Manter compatibilidade
    window.LacTech = window.LacTechAPI;
    
    window.dispatchEvent(new CustomEvent('lactechapi-ready'));
}

// Aguardar API estar disponível
window.waitForAPI = () => {
    return new Promise((resolve) => {
        if (window.LacTechAPI) {
            resolve(window.LacTechAPI);
        } else {
            window.addEventListener('lactechapi-ready', () => {
                resolve(window.LacTechAPI);
            });
        }
    });
};

// Função global para obter cliente Supabase
window.getSupabaseClient = () => {
    // First try to return the existing client from LacTechAPI
    if (window.LacTechAPI && window.LacTechAPI.supabase) {
        return window.LacTechAPI.supabase;
    }
    
    // Fallback: create new client if Supabase library is loaded
    if (!window.supabase) {
        throw new Error('Supabase library not loaded');
    }
    return window.supabase.createClient(SUPABASE_URL, SUPABASE_ANON_KEY);
};

// Inicializar apenas uma vez
if (!window.LacTechAPI) {
    document.addEventListener('DOMContentLoaded', () => {
        initializeSupabase().catch(console.error);
    });
}
