// =====================================================
// CONFIGURAÇÃO CORRIGIDA DO SUPABASE PARA LACTECH
// Sistema de Gestão de Fazendas Leiteiras
// =====================================================

// Credenciais do Supabase
const SUPABASE_URL = 'https://ydqbsqoualowimaupoxt.supabase.co';
const SUPABASE_ANON_KEY = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InlkcWJzcW91YWxvd2ltYXVwb3h0Iiwicm9sZSI6ImFub24iLCJpYXQiOjE3NTUxMjIxNzMsImV4cCI6MjA3MDY5ODE3M30.05eyQPRjyk5DtNjWBZ7W6A5XArqmNFH_8CLwCV0xW8I';

// Aguardar carregamento da biblioteca Supabase e inicializar cliente
let supabase;

// Função para inicializar o Supabase quando a biblioteca estiver disponível
function initializeSupabase() {
    if (typeof window.supabase !== 'undefined' && window.supabase.createClient) {
        supabase = window.supabase.createClient(SUPABASE_URL, SUPABASE_ANON_KEY);
        window.supabase = supabase; // Disponibilizar globalmente
        console.log('Cliente Supabase inicializado com sucesso!');
        return true;
    }
    return false;
}

// Tentar inicializar imediatamente
if (!initializeSupabase()) {
    // Se não conseguir, aguardar o DOM estar pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeSupabase);
    } else {
        // Tentar novamente após um pequeno delay
        setTimeout(initializeSupabase, 100);
    }
}

// =====================================================
// FUNÇÕES DE AUTENTICAÇÃO CORRIGIDAS
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
                p_city: farmData.city,
                p_state: farmData.state,
                p_cnpj: farmData.cnpj || '',
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
// FUNÇÕES DE DADOS DO USUÁRIO CORRIGIDAS
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
// FUNÇÕES DE PRODUÇÃO DE LEITE CORRIGIDAS
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
 * Obter histórico de produção (CORRIGIDO - filtra por farm_id)
 */
async function getMilkProductionHistory(startDate, endDate, limit = 50) {
    try {
        // Primeiro obter farm_id do usuário logado
        const { data: { user } } = await supabase.auth.getUser();
        if (!user) throw new Error('Usuário não autenticado');

        const { data: userData, error: userError } = await supabase
            .from('users')
            .select('farm_id')
            .eq('id', user.id)
            .single();

        if (userError) throw userError;

        let query = supabase
            .from('milk_production')
            .select(`
                *,
                users(name)
            `)
            .eq('farm_id', userData.farm_id)  // CORREÇÃO: filtrar por farm_id
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
// FUNÇÕES DE USUÁRIOS E EQUIPE CORRIGIDAS
// =====================================================

/**
 * Obter lista de usuários da fazenda
 */
async function getFarmUsers() {
    try {
        // Primeiro obter farm_id do usuário logado
        const { data: { user } } = await supabase.auth.getUser();
        if (!user) throw new Error('Usuário não autenticado');

        const { data: userData, error: userError } = await supabase
            .from('users')
            .select('farm_id')
            .eq('id', user.id)
            .single();

        if (userError) throw userError;

        const { data, error } = await supabase
            .from('users')
            .select('*')
            .eq('farm_id', userData.farm_id)  // CORREÇÃO: filtrar por farm_id
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
                whatsapp: userData.whatsapp || null,
                profile_photo_url: userData.profile_photo_url || null,
                // Campos de relatório adicionados
                report_farm_name: userData.report_farm_name || null,
                report_farm_logo_base64: userData.report_farm_logo_base64 || null,
                report_footer_text: userData.report_footer_text || null,
                report_system_logo_base64: userData.report_system_logo_base64 || null,
                is_active: true // Adicionado campo is_active
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
// FUNÇÕES DE ANIMAIS CORRIGIDAS
// =====================================================

/**
 * Obter lista de animais
 */
async function getAnimals() {
    try {
        // Primeiro obter farm_id do usuário logado
        const { data: { user } } = await supabase.auth.getUser();
        if (!user) throw new Error('Usuário não autenticado');

        const { data: userData, error: userError } = await supabase
            .from('users')
            .select('farm_id')
            .eq('id', user.id)
            .single();

        if (userError) throw userError;

        const { data, error } = await supabase
            .from('animals')
            .select('*')
            .eq('farm_id', userData.farm_id)  // CORREÇÃO: filtrar por farm_id
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
                animal_type: animalData.animal_type || 'vaca', // Adicionado campo animal_type
                breed: animalData.breed || null,
                birth_date: animalData.birth_date || null,
                weight: animalData.weight || null,
                health_status: animalData.health_status || 'healthy', // Adicionado campo health_status
                notes: animalData.notes || null,
                is_active: true // Adicionado campo is_active
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
// FUNÇÕES DE NOTIFICAÇÕES CORRIGIDAS
// =====================================================

/**
 * Obter notificações do usuário
 */
async function getNotifications(limit = 20) {
    try {
        // Primeiro obter farm_id do usuário logado
        const { data: { user } } = await supabase.auth.getUser();
        if (!user) throw new Error('Usuário não autenticado');

        const { data: userData, error: userError } = await supabase
            .from('users')
            .select('farm_id')
            .eq('id', user.id)
            .single();

        if (userError) throw userError;

        const { data, error } = await supabase
            .from('notifications')
            .select('*')
            .eq('farm_id', userData.farm_id)  // CORREÇÃO: filtrar por farm_id
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
// FUNÇÕES DE UTILIDADE CORRIGIDAS
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
// FUNÇÕES DE SAÚDE ANIMAL (animal_health_records)
// =====================================================

/**
 * Obter registros de saúde dos animais
 */
async function getAnimalHealthRecords() {
    try {
        const { data: { user } } = await supabase.auth.getUser();
        if (!user) throw new Error('Usuário não autenticado');

        const { data: userData, error: userError } = await supabase
            .from('users')
            .select('farm_id')
            .eq('id', user.id)
            .single();

        if (userError) throw userError;

        const { data, error } = await supabase
            .from('animal_health_records')
            .select(`
                *,
                animals(name, breed, identification),
                users(name)
            `)
            .eq('farm_id', userData.farm_id)
            .order('record_date', { ascending: false });
        
        if (error) throw error;
        
        return {
            success: true,
            healthRecords: data || []
        };
        
    } catch (error) {
        console.error('Erro ao obter registros de saúde:', error);
        return {
            success: false,
            error: error.message || 'Erro ao obter registros de saúde'
        };
    }
}

/**
 * Adicionar registro de saúde animal
 */
async function addAnimalHealthRecord(recordData) {
    try {
        const { data: { user } } = await supabase.auth.getUser();
        if (!user) throw new Error('Usuário não autenticado');

        const { data: userData, error: userError } = await supabase
            .from('users')
            .select('farm_id')
            .eq('id', user.id)
            .single();

        if (userError) throw userError;

        const { data, error } = await supabase
            .from('animal_health_records')
            .insert({
                farm_id: userData.farm_id,
                veterinarian_id: user.id,
                record_date: recordData.record_date,
                diagnosis: recordData.diagnosis,
                symptoms: recordData.symptoms,
                severity: recordData.severity,
                status: recordData.status,
                notes: recordData.notes || null
            })
            .select()
            .single();
        
        if (error) throw error;
        
        return {
            success: true,
            healthRecord: data,
            message: 'Registro de saúde adicionado com sucesso!'
        };
        
    } catch (error) {
        console.error('Erro ao adicionar registro de saúde:', error);
        return {
            success: false,
            error: error.message || 'Erro ao adicionar registro de saúde'
        };
    }
}

// =====================================================
// FUNÇÕES DE REGISTROS FINANCEIROS (financial_records)
// =====================================================

/**
 * Obter registros financeiros
 */
async function getFinancialRecords() {
    try {
        const { data: { user } } = await supabase.auth.getUser();
        if (!user) throw new Error('Usuário não autenticado');

        const { data: userData, error: userError } = await supabase
            .from('users')
            .select('farm_id')
            .eq('id', user.id)
            .single();

        if (userError) throw userError;

        const { data, error } = await supabase
            .from('financial_records')
            .select('*')
            .eq('farm_id', userData.farm_id)
            .order('date', { ascending: false });
        
        if (error) throw error;
        
        return {
            success: true,
            financialRecords: data || []
        };
        
    } catch (error) {
        console.error('Erro ao obter registros financeiros:', error);
        return {
            success: false,
            error: error.message || 'Erro ao obter registros financeiros'
        };
    }
}

/**
 * Adicionar registro financeiro
 */
async function addFinancialRecord(recordData) {
    try {
        const { data: { user } } = await supabase.auth.getUser();
        if (!user) throw new Error('Usuário não autenticado');

        const { data: userData, error: userError } = await supabase
            .from('users')
            .select('farm_id')
            .eq('id', user.id)
            .single();

        if (userError) throw userError;

        const { data, error } = await supabase
            .from('financial_records')
            .insert({
                farm_id: userData.farm_id,
                description: recordData.description,
                amount: recordData.amount,
                type: recordData.type,
                date: recordData.date
            })
            .select()
            .single();
        
        if (error) throw error;
        
        return {
            success: true,
            financialRecord: data,
            message: 'Registro financeiro adicionado com sucesso!'
        };
        
    } catch (error) {
        console.error('Erro ao adicionar registro financeiro:', error);
        return {
            success: false,
            error: error.message || 'Erro ao adicionar registro financeiro'
        };
    }
}

// =====================================================
// FUNÇÕES DE CONFIGURAÇÕES DA FAZENDA (farm_settings)
// =====================================================

/**
 * Obter configurações da fazenda
 */
async function getFarmSettings() {
    try {
        const { data: { user } } = await supabase.auth.getUser();
        if (!user) throw new Error('Usuário não autenticado');

        const { data: userData, error: userError } = await supabase
            .from('users')
            .select('farm_id')
            .eq('id', user.id)
            .single();

        if (userError) throw userError;

        const { data, error } = await supabase
            .from('farm_settings')
            .select('*')
            .eq('farm_id', userData.farm_id);
        
        if (error) throw error;
        
        return {
            success: true,
            settings: data || []
        };
        
    } catch (error) {
        console.error('Erro ao obter configurações da fazenda:', error);
        return {
            success: false,
            error: error.message || 'Erro ao obter configurações da fazenda'
        };
    }
}

/**
 * Adicionar ou atualizar configuração da fazenda
 */
async function setFarmSetting(settingKey, settingValue) {
    try {
        const { data: { user } } = await supabase.auth.getUser();
        if (!user) throw new Error('Usuário não autenticado');

        const { data: userData, error: userError } = await supabase
            .from('users')
            .select('farm_id')
            .eq('id', user.id)
            .single();

        if (userError) throw userError;

        const { data, error } = await supabase
            .from('farm_settings')
            .upsert({
                farm_id: userData.farm_id,
                setting_key: settingKey,
                setting_value: settingValue
            }, {
                onConflict: 'farm_id,setting_key'
            })
            .select()
            .single();
        
        if (error) throw error;
        
        return {
            success: true,
            setting: data,
            message: 'Configuração salva com sucesso!'
        };
        
    } catch (error) {
        console.error('Erro ao salvar configuração:', error);
        return {
            success: false,
            error: error.message || 'Erro ao salvar configuração'
        };
    }
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
    
    // Saúde Animal
    getAnimalHealthRecords,
    addAnimalHealthRecord,

    // Registros Financeiros
    getFinancialRecords,
    addFinancialRecord,

    // Configurações da Fazenda
    getFarmSettings,
    setFarmSetting,
    
    // Cliente Supabase direto (para operações avançadas)
    supabase
};

// Log de inicialização
console.log('LacTech API corrigida inicializada com sucesso!');
console.log('Funções disponíveis em window.LacTechAPI');