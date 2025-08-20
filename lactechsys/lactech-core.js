// =====================================================
// LACTECH CORE - SISTEMA UNIFICADO
// =====================================================
// Arquivo unificado que consolida todas as funcionalidades
// necessárias para o sistema LacTech
// =====================================================

// =====================================================
// 1. CONFIGURAÇÃO DO SUPABASE
// =====================================================

const SUPABASE_URL = 'https://kphrwlhoghgnijlijjuz.supabase.co';
const SUPABASE_ANON_KEY = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImtwaHJ3bGhvZ2hnbmlqbGlqanV6Iiwicm9sZSI6ImFub24iLCJpYXQiOjE3NTU2NDEwMTIsImV4cCI6MjA3MTIxNzAxMn0.bxcC2NJPSWQ2yWSRLw9ypV_JwteGci6Rob9TDv93Gvg';

let supabase;

// Inicializar Supabase
function initializeSupabase() {
    if (typeof window.supabase !== 'undefined' && window.supabase.createClient) {
        supabase = window.supabase.createClient(SUPABASE_URL, SUPABASE_ANON_KEY);
        window.supabase = supabase;
        console.log('✅ Supabase inicializado');
        return true;
    }
    return false;
}

// =====================================================
// 2. FUNÇÕES DE AUTENTICAÇÃO
// =====================================================

const auth = {
    // Verificar autenticação
    isAuthenticated: async () => {
        const { data: { user } } = await supabase.auth.getUser();
        return !!user;
    },
    
    // Obter usuário atual
    getCurrentUser: async () => {
        const { data: { user } } = await supabase.auth.getUser();
        return user;
    },
    
    // Obter dados do usuário
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
    
    // Fazer logout
    signOut: async () => {
        const { error } = await supabase.auth.signOut();
        if (error) {
            console.error('Erro ao fazer logout:', error);
            return false;
        }
        return true;
    },
    
    // Registrar usuário e fazenda (Primeiro Acesso)
    registerUserAndFarm: async (farmData, adminData) => {
        try {
            // Verificar se fazenda já existe
            const { data: farmExists, error: farmCheckError } = await supabase
                .rpc('check_farm_exists', { 
                    p_name: farmData.name, 
                    p_cnpj: farmData.cnpj || null 
                });
            
            if (farmCheckError) throw farmCheckError;
            if (farmExists) throw new Error('Já existe uma fazenda com este nome ou CNPJ');
            
            // Verificar se usuário já existe
            const { data: userExists, error: userCheckError } = await supabase
                .rpc('check_user_exists', { p_email: adminData.email });
            
            if (userCheckError) throw userCheckError;
            if (userExists) throw new Error('Já existe um usuário com este email');
            
            // Criar conta no Supabase Auth
            const { data: authUser, error: authError } = await supabase.auth.signUp({
                email: adminData.email,
                password: adminData.password,
                options: {
                    data: {
                        name: adminData.name,
                        role: adminData.role,
                        farm_name: farmData.name
                    }
                }
            });
            
            if (authError) throw authError;
            if (!authUser.user) throw new Error('Falha ao criar usuário');
            
            // Criar fazenda
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
            
            // Criar registro do usuário
            const { error: userError } = await supabase
                .rpc('create_initial_user', {
                    p_user_id: authUser.user.id,
                    p_farm_id: farmId,
                    p_name: adminData.name,
                    p_email: adminData.email,
                    p_role: adminData.role,
                    p_whatsapp: adminData.whatsapp || null
                });
            
            if (userError) throw userError;
            
            return { success: true, user: authUser.user, farmId };
            
        } catch (error) {
            console.error('Erro no registro:', error);
            return { success: false, error: error.message };
        }
    }
};

// =====================================================
// 3. FUNÇÕES DE NOTIFICAÇÃO
// =====================================================

const notifications = {
    // Mostrar notificação
    show: (message, type = 'info') => {
        const colors = {
            success: 'bg-green-500',
            error: 'bg-red-500',
            warning: 'bg-yellow-500',
            info: 'bg-blue-500'
        };
        
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg text-white ${colors[type]} shadow-lg`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 5000);
    },
    
    // Obter notificações não lidas
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
    
    // Marcar como lida
    markAsRead: async (notificationId) => {
        const { error } = await supabase
            .from('notifications')
            .update({ is_read: true })
            .eq('id', notificationId);
            
        return !error;
    }
};

// =====================================================
// 4. FUNÇÕES DE OPERAÇÕES DE BANCO (CORRIGIDAS)
// =====================================================

const database = {
    // Inserir animal (CORRIGIDO)
    insertAnimal: async (animalData) => {
        try {
            const { data: { user } } = await supabase.auth.getUser();
            if (!user) throw new Error('Usuário não autenticado');
            
            const correctedData = {
                farm_id: animalData.farm_id,
                user_id: user.id,
                name: animalData.name || null,
                breed: animalData.breed || null,
                birth_date: animalData.birth_date || null,
                weight: animalData.weight || null,
                health_status: animalData.health_status || 'healthy',
                is_active: true
            };
            
            const { data, error } = await supabase
                .from('animals')
                .insert(correctedData)
                .select()
                .single();
            
            if (error) throw error;
            
            console.log('✅ Animal inserido:', data);
            return { success: true, data };
            
        } catch (error) {
            console.error('❌ Erro ao inserir animal:', error);
            return { success: false, error: error.message };
        }
    },
    
    // Inserir teste de qualidade (CORRIGIDO)
    insertQualityTest: async (qualityData) => {
        try {
            const { data: { user } } = await supabase.auth.getUser();
            if (!user) throw new Error('Usuário não autenticado');
            
            const correctedData = {
                farm_id: qualityData.farm_id,
                user_id: user.id,
                test_date: qualityData.test_date,
                fat_percentage: qualityData.fat_percentage,
                protein_percentage: qualityData.protein_percentage,
                scc: qualityData.scc,
                cbt: qualityData.cbt,
                laboratory: qualityData.laboratory,
                observations: qualityData.notes || qualityData.observations || null,
                quality_score: qualityData.quality_score || null
            };
            
            const { data, error } = await supabase
                .from('quality_tests')
                .insert(correctedData)
                .select()
                .single();
            
            if (error) throw error;
            
            console.log('✅ Teste de qualidade inserido:', data);
            return { success: true, data };
            
        } catch (error) {
            console.error('❌ Erro ao inserir teste de qualidade:', error);
            return { success: false, error: error.message };
        }
    },
    
    // Inserir produção de leite (CORRIGIDO)
    insertMilkProduction: async (volumeData) => {
        try {
            const { data: { user } } = await supabase.auth.getUser();
            if (!user) throw new Error('Usuário não autenticado');
            
            const correctedData = {
                farm_id: volumeData.farm_id,
                user_id: user.id,
                production_date: volumeData.production_date,
                shift: volumeData.shift,
                volume_liters: volumeData.volume,
                temperature: volumeData.temperature,
                observations: volumeData.observations
            };
            
            const { data, error } = await supabase
                .from('milk_production')
                .insert(correctedData)
                .select()
                .single();
            
            if (error) throw error;
            
            console.log('✅ Produção de leite inserida:', data);
            return { success: true, data };
            
        } catch (error) {
            console.error('❌ Erro ao inserir produção de leite:', error);
            return { success: false, error: error.message };
        }
    },
    
    // Inserir registro financeiro (CORRIGIDO)
    insertFinancialRecord: async (recordData) => {
        try {
            const { data: { user } } = await supabase.auth.getUser();
            if (!user) throw new Error('Usuário não autenticado');
            
            const correctedData = {
                farm_id: recordData.farm_id,
                user_id: user.id,
                record_date: recordData.date || recordData.record_date,
                type: recordData.type,
                amount: recordData.amount,
                description: recordData.description,
                category: recordData.category || null
            };
            
            const { data, error } = await supabase
                .from('financial_records')
                .insert(correctedData)
                .select()
                .single();
            
            if (error) throw error;
            
            console.log('✅ Registro financeiro inserido:', data);
            return { success: true, data };
            
        } catch (error) {
            console.error('❌ Erro ao inserir registro financeiro:', error);
            return { success: false, error: error.message };
        }
    },
    
    // Inserir registro de saúde animal (CORRIGIDO)
    insertHealthRecord: async (recordData) => {
        try {
            const { data: { user } } = await supabase.auth.getUser();
            if (!user) throw new Error('Usuário não autenticado');
            
            const correctedData = {
                farm_id: recordData.farm_id,
                animal_id: recordData.animal_id,
                user_id: user.id,
                record_date: recordData.record_date,
                health_status: recordData.health_status || 'healthy',
                weight: recordData.weight || null,
                temperature: recordData.temperature || null,
                observations: recordData.observations || recordData.notes || null
            };
            
            const { data, error } = await supabase
                .from('animal_health_records')
                .insert(correctedData)
                .select()
                .single();
            
            if (error) throw error;
            
            console.log('✅ Registro de saúde inserido:', data);
            return { success: true, data };
            
        } catch (error) {
            console.error('❌ Erro ao inserir registro de saúde:', error);
            return { success: false, error: error.message };
        }
    }
};

// =====================================================
// 5. FUNÇÕES DE UTILIDADE
// =====================================================

const utils = {
    // Formatar data
    formatDate: (date) => {
        return new Date(date).toLocaleDateString('pt-BR');
    },
    
    // Formatar moeda
    formatCurrency: (value) => {
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(value);
    },
    
    // Formatar número
    formatNumber: (value, decimals = 2) => {
        return new Intl.NumberFormat('pt-BR', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        }).format(value);
    },
    
    // Validar email
    validateEmail: (email) => {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    },
    
    // Validar CNPJ
    validateCNPJ: (cnpj) => {
        cnpj = cnpj.replace(/[^\d]/g, '');
        if (cnpj.length !== 14) return false;
        
        // Verificar dígitos repetidos
        if (/^(\d)\1+$/.test(cnpj)) return false;
        
        // Validar primeiro dígito verificador
        let sum = 0;
        let weight = 2;
        for (let i = 11; i >= 0; i--) {
            sum += parseInt(cnpj.charAt(i)) * weight;
            weight = weight === 9 ? 2 : weight + 1;
        }
        let digit = 11 - (sum % 11);
        if (digit > 9) digit = 0;
        if (digit !== parseInt(cnpj.charAt(12))) return false;
        
        // Validar segundo dígito verificador
        sum = 0;
        weight = 2;
        for (let i = 12; i >= 0; i--) {
            sum += parseInt(cnpj.charAt(i)) * weight;
            weight = weight === 9 ? 2 : weight + 1;
        }
        digit = 11 - (sum % 11);
        if (digit > 9) digit = 0;
        if (digit !== parseInt(cnpj.charAt(13))) return false;
        
        return true;
    }
};

// =====================================================
// 6. FUNÇÕES DE PWA
// =====================================================

const pwa = {
    // Registrar Service Worker
    registerSW: async () => {
        if ('serviceWorker' in navigator) {
            try {
                const registration = await navigator.serviceWorker.register('/sw.js');
                console.log('✅ Service Worker registrado:', registration);
                return registration;
            } catch (error) {
                console.error('❌ Erro ao registrar Service Worker:', error);
                return null;
            }
        }
        return null;
    },
    
    // Verificar se é PWA
    isPWA: () => {
        return window.matchMedia('(display-mode: standalone)').matches ||
               window.navigator.standalone === true;
    },
    
    // Instalar PWA
    install: async () => {
        const deferredPrompt = window.deferredPrompt;
        if (deferredPrompt) {
            deferredPrompt.prompt();
            const { outcome } = await deferredPrompt.userChoice;
            window.deferredPrompt = null;
            return outcome === 'accepted';
        }
        return false;
    }
};

// =====================================================
// 7. FUNÇÕES DE MODAL
// =====================================================

const modal = {
    // Mostrar modal
    show: (modalId) => {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
    },
    
    // Esconder modal
    hide: (modalId) => {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    },
    
    // Esconder todos os modais
    hideAll: () => {
        const modals = document.querySelectorAll('[id$="Modal"]');
        modals.forEach(modal => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        });
    }
};

// =====================================================
// 8. INICIALIZAÇÃO
// =====================================================

function initializeLacTech() {
    console.log('🚀 Inicializando LacTech Core...');
    
    // Inicializar Supabase
    if (!initializeSupabase()) {
        console.error('❌ Falha ao inicializar Supabase');
        return false;
    }
    
    // Registrar Service Worker
    pwa.registerSW();
    
    // Configurar listeners globais
    setupGlobalListeners();
    
    console.log('✅ LacTech Core inicializado com sucesso!');
    return true;
}

function setupGlobalListeners() {
    // Listener para modais
    document.addEventListener('click', (e) => {
        if (e.target.classList.contains('modal-backdrop')) {
            modal.hideAll();
        }
    });
    
    // Listener para tecla ESC
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            modal.hideAll();
        }
    });
}

// =====================================================
// 9. EXPORTAÇÃO GLOBAL
// =====================================================

window.LacTech = {
    supabase,
    auth,
    notifications,
    database,
    utils,
    pwa,
    modal,
    initialize: initializeLacTech
};

// =====================================================
// 10. AUTO-INICIALIZAÇÃO
// =====================================================

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeLacTech);
} else {
    initializeLacTech();
}

console.log('📦 LacTech Core carregado!');
console.log('Funções disponíveis:');
console.log('- LacTech.auth.*');
console.log('- LacTech.notifications.*');
console.log('- LacTech.database.*');
console.log('- LacTech.utils.*');
console.log('- LacTech.pwa.*');
console.log('- LacTech.modal.*');
