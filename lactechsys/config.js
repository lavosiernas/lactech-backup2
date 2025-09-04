// =====================================================
// CONFIGURAÇÃO SUPABASE - LACTECH
// =====================================================
// Arquivo único com todas as funcionalidades
// =====================================================

// Configuração do Supabase
const SUPABASE_URL = 'https://tmaamwuyucaspqcrhuck.supabase.co';
const SUPABASE_ANON_KEY = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InRtYWFtd3V5dWNhc3BxY3JodWNrIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NTY2OTY1MzMsImV4cCI6MjA3MjI3MjUzM30.AdDXp0xrX_xKutFHQrJ47LhFdLTtanTSku7fcK1eTB0';

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
                    const { data: userData } = await supabaseClient
                        .from('users')
                        .select('farm_id')
                        .eq('id', user.id)
                        .single();
                    if (!userData?.farm_id) throw new Error('Fazenda não encontrada');
                    const { data, error } = await supabaseClient
                        .from('users')
                        .select('*')
                        .eq('farm_id', userData.farm_id)
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
