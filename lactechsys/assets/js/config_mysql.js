// =====================================================
// CONFIGURAÇÃO MYSQL - LACTECH LAGOA DO MATO
// =====================================================
// Sistema simplificado para MySQL/PHPMyAdmin
// =====================================================

// Configurações da aplicação
const APP_CONFIG = {
    FARM_NAME: 'Lagoa do Mato',
    FARM_ID: 'farm-lagoa-mato-001',
    BASE_URL: window.location.origin + '/lactechsys/',
    API_URL: window.location.origin + '/lactechsys/api/'
};

// =====================================================
// API SIMULADA PARA MYSQL
// =====================================================

// Simular API do Supabase para compatibilidade
const LacTechAPI = {
    // Configurações
    config: APP_CONFIG,
    
    // Função para obter ID da fazenda
    getFarmId: async () => {
        return APP_CONFIG.FARM_ID;
    },
    
    // Função para obter nome da fazenda
    getFarmName: async () => {
        return APP_CONFIG.FARM_NAME;
    },
    
    // Função para fazer requisições AJAX para PHP
    async request(endpoint, options = {}) {
        const url = APP_CONFIG.API_URL + endpoint;
        const defaultOptions = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
            },
        };
        
        const response = await fetch(url, { ...defaultOptions, ...options });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return await response.json();
    },
    
    // Função para login
    async signIn(email, password) {
        try {
            const response = await fetch(APP_CONFIG.BASE_URL + 'api/auth.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ email, password })
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Armazenar dados do usuário
                localStorage.setItem('user_data', JSON.stringify(data.user));
                localStorage.setItem('user_token', data.token || 'mysql_token');
                return { data: data.user, error: null };
            } else {
                return { data: null, error: { message: data.message || 'Login falhou' } };
            }
        } catch (error) {
            return { data: null, error };
        }
    },
    
    // Função para logout
    async signOut() {
        localStorage.removeItem('user_data');
        localStorage.removeItem('user_token');
        window.location.href = APP_CONFIG.BASE_URL + 'login.php';
    },
    
    // Função para obter usuário atual
    getCurrentUser: () => {
        const userData = localStorage.getItem('user_data');
        return userData ? JSON.parse(userData) : null;
    },
    
    // Simular tabelas do Supabase
    from: (table) => {
        return {
            select: (columns) => ({
                eq: (column, value) => ({
                    single: async () => {
                        return await LacTechAPI.request(`${table}/single.php?${column}=${value}`);
                    },
                    then: async (callback) => {
                        const result = await LacTechAPI.request(`${table}/list.php?${column}=${value}`);
                        return callback ? callback(result) : result;
                    }
                }),
                limit: (count) => ({
                    single: async () => {
                        return await LacTechAPI.request(`${table}/single.php?limit=${count}`);
                    }
                }),
                then: async (callback) => {
                    const result = await LacTechAPI.request(`${table}/list.php`);
                    return callback ? callback(result) : result;
                }
            }),
            insert: (data) => ({
                then: async (callback) => {
                    const result = await LacTechAPI.request(`${table}/insert.php`, {
                        method: 'POST',
                        body: JSON.stringify(data)
                    });
                    return callback ? callback(result) : result;
                }
            }),
            update: (data) => ({
                eq: (column, value) => ({
                    then: async (callback) => {
                        const result = await LacTechAPI.request(`${table}/update.php?${column}=${value}`, {
                            method: 'PUT',
                            body: JSON.stringify(data)
                        });
                        return callback ? callback(result) : result;
                    }
                })
            }),
            delete: () => ({
                eq: (column, value) => ({
                    then: async (callback) => {
                        const result = await LacTechAPI.request(`${table}/delete.php?${column}=${value}`, {
                            method: 'DELETE'
                        });
                        return callback ? callback(result) : result;
                    }
                })
            })
        };
    },
    
    // Função para obter dados de estatísticas
    async getFarmStats() {
        try {
            const response = await fetch(APP_CONFIG.BASE_URL + 'api/stats.php');
            const data = await response.json();
            return { data, error: null };
        } catch (error) {
            return { data: null, error };
        }
    },
    
    // Função para obter animais
    async getAnimals() {
        try {
            const response = await fetch(APP_CONFIG.BASE_URL + 'api/animals.php');
            const data = await response.json();
            return { data, error: null };
        } catch (error) {
            return { data: null, error };
        }
    },
    
    // Função para obter produção
    async getProduction(date = null) {
        try {
            const url = date ? 
                `${APP_CONFIG.BASE_URL}api/production.php?date=${date}` :
                `${APP_CONFIG.BASE_URL}api/production.php`;
            const response = await fetch(url);
            const data = await response.json();
            return { data, error: null };
        } catch (error) {
            return { data: null, error };
        }
    }
};

// =====================================================
// INICIALIZAÇÃO
// =====================================================

// Disponibilizar globalmente
window.LacTechAPI = LacTechAPI;
window.supabase = LacTechAPI; // Para compatibilidade

// Função de inicialização
async function initializeMySQL() {
    console.log('🚀 Inicializando sistema MySQL - Lagoa do Mato');
    
    // Verificar se usuário está logado
    const user = LacTechAPI.getCurrentUser();
    if (user) {
        console.log('✅ Usuário logado:', user.name);
    } else {
        console.log('ℹ️ Usuário não logado');
    }
    
    return true;
}

// Inicializar quando DOM estiver pronto
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeMySQL);
} else {
    initializeMySQL();
}

// Exportar para uso global
window.initializeMySQL = initializeMySQL;
