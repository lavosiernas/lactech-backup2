// LacTech - Configuração do Supabase

// Credenciais do Supabase
const SUPABASE_URL = 'https://kphrwlhoghgnijlijjuz.supabase.co';
const SUPABASE_KEY = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImtwaHJ3bGhvZ2hnbmlqbGlqanV6Iiwicm9sZSI6ImFub24iLCJpYXQiOjE3NTU2NDEwMTIsImV4cCI6MjA3MTIxNzAxMn0.bxcC2NJPSWQ2yWSRLw9ypV_JwteGci6Rob9TDv93Gvg';

// Inicialização do cliente Supabase
const supabase = supabaseJs.createClient(SUPABASE_URL, SUPABASE_KEY);

// Funções auxiliares para autenticação
const auth = {
    // Verificar se o usuário está autenticado
    isAuthenticated: async () => {
        const { data: { user } } = await supabase.auth.getUser();
        return !!user;
    },
    
    getCurrentUser: async () => {
        const { data: { user } } = await supabase.auth.getUser();
        return user;
    },
    
    getUserData: async () => {
        const { data: { user } } = await supabase.auth.getUser();
        if (!user) return null;
        
        const { data, error } = await supabase
            .from('users')
            .select('*')
            .eq('id', user.id)
            .single();
            
        if (error) {
            console.error('Erro ao obter dados do usuário:', error);
            return null;
        }
        
        return data;
    },
    
    signOut: async () => {
        const { error } = await supabase.auth.signOut();
        if (error) {
            console.error('Erro ao fazer logout:', error);
            return false;
        }
        return true;
    }
};

// Funções auxiliares para notificações
const notifications = {
    // Obter notificações não lidas para o usuário atual
    getUnread: async () => {
        const { data: { user } } = await supabase.auth.getUser();
        if (!user) return [];
        
        const { data, error } = await supabase
            .from('notifications')
            .select('*')
            .eq('user_id', user.id)
            .eq('is_read', false)
            .order('created_at', { ascending: false });
            
        if (error) {
            console.error('Erro ao obter notificações:', error);
            return [];
        }
        
        return data;
    },
    
    // Marcar notificação como lida
    markAsRead: async (notificationId) => {
        const { error } = await supabase
            .from('notifications')
            .update({ is_read: true })
            .eq('id', notificationId);
            
        return !error;
    }
};

// Exportar as funções e variáveis
window.lactech = {
    supabase,
    auth,
    notifications,
    SUPABASE_URL,
    SUPABASE_KEY
};