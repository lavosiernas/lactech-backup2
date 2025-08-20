// =====================================================
// CONFIGURAÇÃO ATUALIZADA DO SUPABASE PARA LACTECH
// Sistema de Gestão de Fazendas Leiteiras
// =====================================================

// Substitua pelas suas credenciais do Supabase
const SUPABASE_URL = 'https://kphrwlhoghgnijlijjuz.supabase.co';
const SUPABASE_ANON_KEY = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImtwaHJ3bGhvZ2hnbmlqbGlqanV6Iiwicm9sZSI6ImFub24iLCJpYXQiOjE3NTU2NDEwMTIsImV4cCI6MjA3MTIxNzAxMn0.bxcC2NJPSWQ2yWSRLw9ypV_JwteGci6Rob9TDv93Gvg';

// Inicializar cliente Supabase
const supabase = window.supabase.createClient(SUPABASE_URL, SUPABASE_ANON_KEY);

// =====================================================
// FUNÇÕES DE AUTENTICAÇÃO
// =====================================================

/**
 * Registrar novo usuário e criar fazenda (Primeiro Acesso)
 */
async function registerUserAndFarm(farmData, adminData) {
    try {
        // 1. Verificar se fazenda já existe
        const { data: farmExists, error: farmCheckError } = await supabase
            .rpc('check_farm_exists', { 
                p_name: farmData.name, 
                p_cnpj: farmData.cnpj || null 
            });
        
        if (farmCheckError) throw farmCheckError;
        if (farmExists) throw new Error('Já existe uma fazenda com este nome ou CNPJ');
        
        // 2. Verificar se usuário já existe
        const { data: userExists, error: userCheckError } = await supabase
            .rpc('check_user_exists', { p_email: adminData.email });
        
        if (userCheckError) throw userCheckError;
        if (userExists) throw new Error('Já existe um usuário com este email');
        
        // 3. Criar conta no Supabase Auth
        const { data: authUser, error: authError } = await supabase.auth.signUp({
            email: adminData.email,
            password: adminData.password,
            options: {
                data: {
                    name: adminData.name,
                    role: adminData.role,
                    farm_name: farmData.name
                },
                emailRedirectTo: undefined // Disable email confirmation
            }
        });
        
        if (authError) throw authError;
        if (!authUser.user) throw new Error('Falha ao criar usuário');
        
        // 4. Criar fazenda
        const { data: farmId, error: farmError } = await supabase
            .rpc('create_initial_farm', {
                p_name: farmData.name,
                p_owner_name: farmData.owner_name,
                p_cnpj: farmData.cnpj || '',
                p_city: farmData.city,
                p_state: farmData.state,
                p_phone: farmData.phone || '',
                p_email: farmData.email || '',
                p_address: farmData.address || ''
            });
        
        if (farmError) throw farmError;
        
        // 5. Criar registro do usuário
        const { error: userError } = await supabase
            .rpc('create_initial_user', {
                p_user_id: authUser.user.id,
                p_farm_id: farmId,
                p_name: adminData.name,
                p_email: adminData.email,
                p_role: adminData.role,
                p_whatsapp: adminData.whatsapp || ''
            });
        
        if (userError) throw userError;
        
        // 6. Marcar fazenda como configurada
        const { error: setupError } = await supabase
            .rpc('complete_farm_setup', { p_farm_id: farmId });
        
        if (setupError) throw setupError;
        
        return {
            success: true,
            farmId: farmId,
            userId: authUser.user.id,
            message: 'Fazenda e usuário criados com sucesso!'
        };
        
    } catch (error) {
        console.error('Erro no registro:', error);
        return {
            success: false,
            error: error.message || 'Erro desconhecido no registro'
        };
    }
}

/**
 * Login do usuário
 */
async function loginUser(email, password) {
    try {
        const { data, error } = await supabase.auth.signInWithPassword({
            email: email,
            password: password
        });
        
        if (error) throw error;
        
        return {
            success: true,
            user: data.user,
            session: data.session
        };
        
    } catch (error) {
        console.error('Erro no login:', error);
        return {
            success: false,
            error: error.message || 'Erro no login'
        };
    }
}

/**
 * Logout do usuário
 */
async function logoutUser() {
    try {
        const { error } = await supabase.auth.signOut();
        if (error) throw error;
        
        return { success: true };
        
    } catch (error) {
        console.error('Erro no logout:', error);
        return {
            success: false,
            error: error.message || 'Erro no logout'
        };
    }
}

// =====================================================
// FUNÇÕES DE DADOS DO USUÁRIO
// =====================================================

/**
 * Obter perfil do usuário logado
 */
async function getUserProfile() {
    try {
        const { data, error } = await supabase.rpc('get_user_profile');
        
        if (error) throw error;
        
        return {
            success: true,
            profile: data[0] || null
        };
        
    } catch (error) {
        console.error('Erro ao obter perfil:', error);
        return {
            success: false,
            error: error.message || 'Erro ao obter perfil'
        };
    }
}

/**
 * Obter estatísticas da fazenda
 */
async function getFarmStatistics() {
    try {
        const { data, error } = await supabase.rpc('get_farm_statistics');
        
        if (error) throw error;
        
        return {
            success: true,
            statistics: data
        };
        
    } catch (error) {
        console.error('Erro ao obter estatísticas:', error);
        return {
            success: false,
            error: error.message || 'Erro ao obter estatísticas'
        };
    }
}

// =====================================================
// FUNÇÕES DE PRODUÇÃO DE LEITE
// =====================================================

/**
 * Registrar produção de leite
 */
async function registerMilkProduction(productionData) {
    try {
        const { data, error } = await supabase.rpc('register_milk_production', {
            p_production_date: productionData.date,
            p_shift: productionData.shift,
            p_volume_liters: productionData.volume,
            p_temperature: productionData.temperature || null,
            p_observations: productionData.observations || ''
        });
        
        if (error) throw error;
        
        return {
            success: true,
            productionId: data,
            message: 'Produção registrada com sucesso!'
        };
        
    } catch (error) {
        console.error('Erro ao registrar produção:', error);
        return {
            success: false,
            error: error.message || 'Erro ao registrar produção'
        };
    }
}

/**
 * Obter histórico de produção
 */
async function getMilkProductionHistory(startDate, endDate, limit = 50) {
    try {
        let query = supabase
            .from('milk_production')
            .select(`
                *,
                users(name)
            `)
            .order('production_date', { ascending: false })
            .order('created_at', { ascending: false })
            .limit(limit);
        
        if (startDate) {
            query = query.gte('production_date', startDate);
        }
        
        if (endDate) {
            query = query.lte('production_date', endDate);
        }
        
        const { data, error } = await query;
        
        if (error) throw error;
        
        return {
            success: true,
            productions: data || []
        };
        
    } catch (error) {
        console.error('Erro ao obter histórico:', error);
        return {
            success: false,
            error: error.message || 'Erro ao obter histórico'
        };
    }
}

// =====================================================
// FUNÇÕES DE USUÁRIOS E EQUIPE
// =====================================================

/**
 * Obter lista de usuários da fazenda
 */
async function getFarmUsers() {
    try {
        const { data, error } = await supabase
            .from('users')
            .select('*')
            .eq('is_active', true)
            .order('created_at', { ascending: false });
        
        if (error) throw error;
        
        return {
            success: true,
            users: data || []
        };
        
    } catch (error) {
        console.error('Erro ao obter usuários:', error);
        return {
            success: false,
            error: error.message || 'Erro ao obter usuários'
        };
    }
}

/**
 * Criar novo usuário (apenas para proprietários e gerentes)
 */
async function createUser(userData) {
    try {
        // Verificar se usuário já existe
        const { data: userExists, error: checkError } = await supabase
            .rpc('check_user_exists', { p_email: userData.email });
        
        if (checkError) throw checkError;
        if (userExists) throw new Error('Já existe um usuário com este email');
        
        // Obter farm_id e farm_name do usuário logado
        const { data: profile, error: profileError } = await supabase.rpc('get_user_profile');
        if (profileError || !profile[0]) throw profileError || new Error('Perfil não encontrado');
        const farmId = profile[0].farm_id;
        const farmName = profile[0].farm_name || 'Minha Fazenda';
        
        // Criar conta no Supabase Auth usando signUp
        const { data: authData, error: authError } = await supabase.auth.signUp({
            email: userData.email,
            password: userData.password,
            options: {
                data: {
                    name: userData.name,
                    role: userData.role,
                    whatsapp: userData.whatsapp,
                    farm_name: farmName
                },
                emailRedirectTo: undefined // Disable email confirmation
            }
        });
        
        if (authError) throw authError;
        if (!authData.user) throw new Error('Falha ao criar usuário');
        
        // Criar registro na tabela users
        const { error: insertError } = await supabase
            .from('users')
            .insert({
                id: authData.user.id,
                farm_id: farmId,
                name: userData.name,
                email: userData.email,
                role: userData.role,
                whatsapp: userData.whatsapp || null
            });
        
        if (insertError) throw insertError;
        
        return {
            success: true,
            userId: authData.user.id,
            message: 'Usuário criado com sucesso!'
        };
        
    } catch (error) {
        console.error('Erro ao criar usuário:', error);
        return {
            success: false,
            error: error.message || 'Erro ao criar usuário'
        };
    }
}

// =====================================================
// FUNÇÕES DE ANIMAIS
// =====================================================

/**
 * Obter lista de animais
 */
async function getAnimals() {
    try {
        const { data, error } = await supabase
            .from('animals')
            .select('*')
            .eq('is_active', true)
            .order('created_at', { ascending: false });
        
        if (error) throw error;
        
        return {
            success: true,
            animals: data || []
        };
        
    } catch (error) {
        console.error('Erro ao obter animais:', error);
        return {
            success: false,
            error: error.message || 'Erro ao obter animais'
        };
    }
}

/**
 * Adicionar novo animal
 */
async function addAnimal(animalData) {
    try {
        // Obter farm_id do usuário logado
        const { data: profile } = await supabase.rpc('get_user_profile');
        if (!profile || !profile[0]) throw new Error('Usuário não encontrado');
        
        const { data, error } = await supabase
            .from('animals')
            .insert({
                farm_id: profile[0].farm_id,
                identification: animalData.identification,
                name: animalData.name || null,
                breed: animalData.breed || null,
                birth_date: animalData.birth_date || null,
                weight: animalData.weight || null,
                notes: animalData.notes || null
            })
            .select()
            .single();
        
        if (error) throw error;
        
        return {
            success: true,
            animal: data,
            message: 'Animal adicionado com sucesso!'
        };
        
    } catch (error) {
        console.error('Erro ao adicionar animal:', error);
        return {
            success: false,
            error: error.message || 'Erro ao adicionar animal'
        };
    }
}

// =====================================================
// FUNÇÕES DE NOTIFICAÇÕES
// =====================================================

/**
 * Obter notificações do usuário
 */
async function getNotifications(limit = 20) {
    try {
        const { data, error } = await supabase
            .from('notifications')
            .select('*')
            .order('created_at', { ascending: false })
            .limit(limit);
        
        if (error) throw error;
        
        return {
            success: true,
            notifications: data || []
        };
        
    } catch (error) {
        console.error('Erro ao obter notificações:', error);
        return {
            success: false,
            error: error.message || 'Erro ao obter notificações'
        };
    }
}

/**
 * Marcar notificação como lida
 */
async function markNotificationAsRead(notificationId) {
    try {
        const { error } = await supabase
            .from('notifications')
            .update({ is_read: true })
            .eq('id', notificationId);
        
        if (error) throw error;
        
        return { success: true };
        
    } catch (error) {
        console.error('Erro ao marcar notificação:', error);
        return {
            success: false,
            error: error.message || 'Erro ao marcar notificação'
        };
    }
}

// =====================================================
// FUNÇÕES DE UTILIDADE
// =====================================================

/**
 * Verificar se usuário está autenticado
 */
async function checkAuth() {
    try {
        const { data: { user }, error } = await supabase.auth.getUser();
        
        if (error) throw error;
        
        return {
            isAuthenticated: !!user,
            user: user
        };
        
    } catch (error) {
        console.error('Erro ao verificar autenticação:', error);
        return {
            isAuthenticated: false,
            user: null
        };
    }
}

/**
 * Escutar mudanças de autenticação
 */
function onAuthStateChange(callback) {
    return supabase.auth.onAuthStateChange((event, session) => {
        callback(event, session);
    });
}

/**
 * Formatar data para o padrão brasileiro
 */
function formatDate(date) {
    return new Date(date).toLocaleDateString('pt-BR');
}

/**
 * Formatar valor monetário
 */
function formatCurrency(value) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(value);
}

/**
 * Validar CNPJ
 */
function validateCNPJ(cnpj) {
    cnpj = cnpj.replace(/[^\d]+/g, '');
    if (cnpj.length !== 14) return false;
    if (/^(\d)\1+$/.test(cnpj)) return false;
    return true;
}

/**
 * Validar CPF
 */
function validateCPF(cpf) {
    cpf = cpf.replace(/[^\d]+/g, '');
    if (cpf.length !== 11) return false;
    if (/^(\d)\1+$/.test(cpf)) return false;
    return true;
}

// =====================================================
// EXPORTAR FUNÇÕES GLOBALMENTE
// =====================================================

// Disponibilizar funções globalmente
window.LacTechAPI = {
    // Autenticação
    registerUserAndFarm,
    loginUser,
    logoutUser,
    checkAuth,
    onAuthStateChange,
    
    // Usuário
    getUserProfile,
    getFarmStatistics,
    
    // Produção
    registerMilkProduction,
    getMilkProductionHistory,
    
    // Usuários
    getFarmUsers,
    createUser,
    
    // Animais
    getAnimals,
    addAnimal,
    
    // Notificações
    getNotifications,
    markNotificationAsRead,
    
    // Utilidades
    formatDate,
    formatCurrency,
    validateCNPJ,
    validateCPF,
    
    // Cliente Supabase direto (para operações avançadas)
    supabase
};

// Log de inicialização
console.log('LacTech API inicializada com sucesso!');
console.log('Funções disponíveis em window.LacTechAPI');