// =====================================================
// CONFIGURAÇÃO SUPABASE - LACTECH
// =====================================================
// Arquivo único com todas as funcionalidades
// =====================================================

// Configuração do Supabase
const SUPABASE_URL = 'https://tmaamwuyucaspqcrhuck.supabase.co';
const SUPABASE_ANON_KEY = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InRtYWFtd3V5dWNhc3BxY3JodWNrIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NTY2OTY1MzMsImV4cCI6MjA3MjI3MjUzM30.AdDXp0xrX_xKutFHQrJ47LhFdLTtanTSku7fcK1eTB0';

// Configuração da Fazenda Fixa
const FARM_ID = '550e8400-e29b-41d4-a716-446655440000'; // UUID válido para Lagoa do Mato
const FARM_NAME = 'Lagoa do Mato';

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
    // Evitar múltiplas instâncias
    if (window.LacTechAPI && window.LacTechAPI.supabase) {
        return window.LacTechAPI;
    }
    
    const supabaseLib = await waitForSupabase();
    const supabaseClient = supabaseLib.createClient(SUPABASE_URL, SUPABASE_ANON_KEY, {
        realtime: {
            enabled: true,
            params: {
                eventsPerSecond: 10
            }
        }
    });
    
    // Criar API unificada
    window.LacTechAPI = {
        auth: {
            getUser: () => supabaseClient.auth.getUser(),
            getSession: () => supabaseClient.auth.getSession(),
            signOut: () => supabaseClient.auth.signOut(),
            signIn: (email, password) => supabaseClient.auth.signInWithPassword({ email, password }),
            signUp: (email, password, data) => supabaseClient.auth.signUp({ email, password, data })
        },
        users: {
            getFarmUsers: async () => {
                try {
                    const { data: { user } } = await supabaseClient.auth.getUser();
                    if (!user) throw new Error('Usuário não autenticado');
                    const { data, error } = await supabaseClient
                        .from('users')
                        .select('*')
                        .eq('farm_id', FARM_ID)
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
        farm: {
            getId: () => FARM_ID,
            getName: () => FARM_NAME
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
} else {
    // Se já existe, disparar evento para compatibilidade
    window.dispatchEvent(new CustomEvent('lactechapi-ready'));
}
