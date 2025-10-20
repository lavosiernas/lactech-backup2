// Configuração dos Bancos de Dados
// Sistema Principal + Chat Separado

// Configuração dos bancos
const DATABASE_CONFIG = {
    // Banco Principal do Sistema
    SYSTEM: {
        url: 'https://tmaamwuyucaspqcrhuck.supabase.co',
        key: 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InRtYWFtd3V5dWNhc3BxY3JodWNrIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NTY2OTY1MzMsImV4cCI6MjA3MjI3MjUzM30.AdDXp0xrX_xKutFHQrJ47LhFdLTtanTSku7fcK1eTB0'
    },
    
    // Banco do Chat (usando o mesmo banco por enquanto)
    CHAT: {
        url: 'https://tmaamwuyucaspqcrhuck.supabase.co',
        key: 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InRtYWFtd3V5dWNhc3BxY3JodWNrIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NTY2OTY1MzMsImV4cCI6MjA3MjI3MjUzM30.AdDXp0xrX_xKutFHQrJ47LhFdLTtanTSku7fcK1eTB0'
    }
};

// Cliente do Sistema Principal
const systemSupabase = supabase.createClient(
    DATABASE_CONFIG.SYSTEM.url,
    DATABASE_CONFIG.SYSTEM.key
);

// Cliente do Chat
const chatSupabase = supabase.createClient(
    DATABASE_CONFIG.CHAT.url,
    DATABASE_CONFIG.CHAT.key
);

// Função para obter cliente do sistema (compatibilidade)
async function getSupabaseClient() {
    return systemSupabase;
}

// Função para obter cliente do chat
async function getChatClient() {
    return chatSupabase;
}

// Expor funções globalmente
window.DATABASE_CONFIG = DATABASE_CONFIG;
window.systemSupabase = systemSupabase;
window.chatSupabase = chatSupabase;
window.getSupabaseClient = getSupabaseClient;
window.getChatClient = getChatClient;
