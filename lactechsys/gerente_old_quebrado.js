// ==================== SISTEMA MYSQL - SEM SUPABASE ====================

// Configuração da aplicação
const APP_CONFIG = {
    farmName: 'Lagoa do Mato',
    farmId: 1,
    apiUrl: '/lactechsys/api/'
};

// Obter usuário atual do localStorage
function getCurrentUser() {
    const userData = localStorage.getItem('user_data');
    if (!userData) return null;
    try {
        return JSON.parse(userData);
    } catch (e) {
        return null;
    }
}

// Fazer requisição para API MySQL
async function apiRequest(endpoint, options = {}) {
    const url = APP_CONFIG.apiUrl + endpoint;
    const defaultOptions = {
        method: options.method || 'GET',
        headers: {
            'Content-Type': 'application/json',
            ...options.headers
        }
    };
    
    if (options.body) {
        defaultOptions.body = JSON.stringify(options.body);
    }
    
    try {
        const response = await fetch(url, defaultOptions);
        const data = await response.json();
        return data;
    } catch (error) {
        console.error('Erro na API:', error);
        throw error;
    }
}

// ==================== CONTROLE DE TELA DE CARREGAMENTO DE CONEXÃO ====================
function showServerConnectionLoading() {
    const loadingModal = document.getElementById('serverConnectionLoading');
    if (loadingModal) {
        loadingModal.classList.remove('hidden');
        console.log('🔄 Mostrando tela de carregamento de conexão com servidor');
    }
}

function hideServerConnectionLoading() {
    const loadingModal = document.getElementById('serverConnectionLoading');
    if (loadingModal) {
        loadingModal.classList.add('hidden');
        console.log('✅ Escondendo tela de carregamento de conexão com servidor');
    }
}

// ==================== CACHE SYSTEM ====================
const CacheManager = {
    cache: new Map(),
    userData: null,
    farmData: null,
    lastUserFetch: 0,
    lastFarmFetch: 0,
    forceRefresh: false, // Flag para forçar atualização
    CACHE_DURATION: 5 * 60 * 1000, // 5 minutos
    
    // Limpar cache após registros
    clearCache() {
        console.log('🗑️ Limpando cache...');
        this.cache.clear();
        this.userData = null;
        this.farmData = null;
        this.lastUserFetch = 0;
        this.lastFarmFetch = 0;
        this.forceRefresh = true;
        console.log('✅ Cache limpo');
    },

    // Forçar atualização do cache
    forceCacheRefresh() {
        console.log('🔄 Forçando atualização do cache...');
        this.forceRefresh = true;
        this.cache.clear();
    },
    
    // Cache de dados do usuário
    async getUserData(forceRefresh = false) {
        const now = Date.now();
        if (!forceRefresh && this.userData && (now - this.lastUserFetch) < this.CACHE_DURATION) {
            return this.userData;
        }
        
        const userDataLS = localStorage.getItem('user_data');
        if (userDataLS) {
            this.userData = JSON.parse(userDataLS);
            this.lastUserFetch = now;
        }
        
        return this.userData;
    },
    
    // Cache de dados da fazenda
    async getFarmData(forceRefresh = false) {
        const now = Date.now();
        if (!forceRefresh && this.farmData && (now - this.lastFarmFetch) < this.CACHE_DURATION) {
            return this.farmData;
        }
        
        this.farmData = {
            id: 1,
            name: 'Lagoa do Mato',
            location: 'Lagoa do Mato'
        };
        this.lastFarmFetch = now;
        
        return this.farmData;
    },
    
    // Cache genérico
    set(key, data, ttl = this.CACHE_DURATION) {
        this.cache.set(key, {
            data,
            timestamp: Date.now(),
            ttl
        });
    },
    
    get(key) {
        const item = this.cache.get(key);
        if (!item) return null;
        
        const now = Date.now();
        if (now - item.timestamp > item.ttl) {
            this.cache.delete(key);
            return null;
        }
        
        return item.data;
    },
    
    // Limpar cache específico
    clear(key) {
        if (key) {
            this.cache.delete(key);
        } else {
            this.cache.clear();
            this.userData = null;
            this.farmData = null;
        }
    },
    
    // Invalidar cache de dados críticos
    invalidateUserData() {
        this.userData = null;
        this.farmData = null;
        this.lastUserFetch = 0;
        this.lastFarmFetch = 0;
    },
    
    // Cache para dados de volume (MySQL - stub)
    async getVolumeData(farmId, dateRange, forceRefresh = false) {
        return null; // MySQL: implementar API se necessário
    }
};

// Função para verificar autenticação MySQL
async function checkAuthentication() {
try {
    const userData = localStorage.getItem('user_data');
    
    if (!userData) {
        clearUserSession();
        showNotification('Sessão expirada. Redirecionando para login...', 'error');
        setTimeout(() => {
            safeRedirect('login.php');
        }, 2000);
        return false;
    }
    
    let user;
    try {
        user = JSON.parse(userData);
    } catch (e) {
        clearUserSession();
        safeRedirect('login.php');
        return false;
    }
    
    if (!user.id || !user.email || !user.role) {
        clearUserSession();
        safeRedirect('login.php');
        return false;
    }
    
    window.currentUser = user;
    return true;
    
} catch (error) {
    clearUserSession();
    setTimeout(() => {
        safeRedirect('login.php');
    }, 2000);
    return false;
}
}

// Função para limpar completamente a sessão MySQL
function clearUserSession() {
localStorage.removeItem('user_data');
localStorage.removeItem('user_token');
localStorage.removeItem('userData');
localStorage.removeItem('userSession');
localStorage.removeItem('farmData');
localStorage.removeItem('setupCompleted');

sessionStorage.removeItem('user_data');
sessionStorage.removeItem('user_token');
sessionStorage.removeItem('userData');
sessionStorage.removeItem('userSession');
sessionStorage.removeItem('farmData');
sessionStorage.removeItem('setupCompleted');
sessionStorage.removeItem('redirectCount');

if (window.currentUser) {
delete window.currentUser;
}
}

// Função para gerenciar redirecionamentos
function safeRedirect(url) {
const currentCount = parseInt(sessionStorage.getItem('redirectCount') || '0');
sessionStorage.setItem('redirectCount', (currentCount + 1).toString());

window.location.replace(url);
}

// Função para monitorar bloqueio de usuário (otimizada)
let blockWatcherInterval = null;
let lastBlockCheck = 0;
const BLOCK_CHECK_INTERVAL = 60000; // 1 minuto em vez de 15 segundos

function startBlockWatcher() {
// Evitar múltiplos intervalos
if (blockWatcherInterval) {
    clearInterval(blockWatcherInterval);
}

blockWatcherInterval = setInterval(async () => {
    try {
        const now = Date.now();
        // Evitar verificações muito frequentes
        if (now - lastBlockCheck < BLOCK_CHECK_INTERVAL) {
            return;
        }
        lastBlockCheck = now;
        
        // Verificação MySQL removida - não precisa
    } catch (error) {
        // Em caso de erro persistente, limpar sessão
        clearUserSession();
        clearInterval(blockWatcherInterval);
        safeRedirect('login.php');
    }
}, BLOCK_CHECK_INTERVAL);
}

function stopBlockWatcher() {
if (blockWatcherInterval) {
    clearInterval(blockWatcherInterval);
    blockWatcherInterval = null;
}
}

document.addEventListener('DOMContentLoaded', async function() {
// Flag para evitar múltiplas inicializações
if (window.pageInitialized) {
    return;
}

window.pageInitialized = true;

// Verificar se há dados de sessão válidos (MySQL)
const userData = localStorage.getItem('user_data') || sessionStorage.getItem('user_data');
if (!userData) {
    safeRedirect('login.php');
    return;
}

// Verificar se não estamos em um loop de redirecionamento
const redirectCount = sessionStorage.getItem('redirectCount') || 0;
if (redirectCount > 3) {
    clearUserSession();
    sessionStorage.removeItem('redirectCount');
    safeRedirect('login.php');
    return;
}

try {
    const parsedUserData = JSON.parse(userData);
    if (!parsedUserData || !parsedUserData.id) {
        clearUserSession();
        safeRedirect('login.php');
        return;
    }
} catch (error) {
    clearUserSession();
    safeRedirect('login.php');
    return;
}

// Check authentication first
const isAuthenticated = await checkAuthentication();
if (!isAuthenticated) {
    return; // Stop execution if not authenticated
}


initializeCharts(); // Initialize charts before loading data
await initializePage();
setupEventListeners();

// Carregar foto do header
await loadHeaderPhoto();

// Garantir que o modal de foto esteja fechado na inicialização
const photoModal = document.getElementById('photoChoiceModal');
if (photoModal) {
    photoModal.classList.remove('show');
    photoModal.classList.add('hidden');
    photoModal.classList.remove('flex');
    photoModal.style.display = 'none';
    photoModal.style.visibility = 'hidden';
    photoModal.style.opacity = '0';
    photoModal.style.pointerEvents = 'none';
}

// Garantir que as telas de processamento estejam ocultas
const photoProcessingScreen = document.getElementById('photoProcessingScreen');
if (photoProcessingScreen) {
    photoProcessingScreen.classList.add('hidden');
    photoProcessingScreen.style.display = 'none';
    photoProcessingScreen.style.visibility = 'hidden';
    photoProcessingScreen.style.opacity = '0';
    photoProcessingScreen.style.pointerEvents = 'none';
}

const managerPhotoProcessingScreen = document.getElementById('managerPhotoProcessingScreen');
if (managerPhotoProcessingScreen) {
    managerPhotoProcessingScreen.classList.add('hidden');
    managerPhotoProcessingScreen.style.display = 'none';
    managerPhotoProcessingScreen.style.visibility = 'hidden';
    managerPhotoProcessingScreen.style.opacity = '0';
    managerPhotoProcessingScreen.style.pointerEvents = 'none';
}

updateDateTime();
setInterval(updateDateTime, 60000); // Update every minute
// Iniciar verificação de bloqueio durante a sessão (otimizada)
startBlockWatcher();

});

// Função para limpar todos os event listeners (útil para debugging)
window.clearAllEventListeners = function() {

// Limpar listeners de formulários
const forms = [
    'addUserFormModal', 
    'updateProfileForm',
    'editUserForm',
    'createSecondaryAccountForm'
];

forms.forEach(formId => {
    const form = document.getElementById(formId);
    if (form) {
        const newForm = form.cloneNode(true);
        form.parentNode.replaceChild(newForm, form);
    }
});

};

/**
* Inicializa a página do gerente
async function getCurrentUser() {
const userData = localStorage.getItem('user_data');
if (!userData) {
throw new Error('Usuário não autenticado');
}
return JSON.parse(userData);
}

async function mysqlRequest(endpoint, data = null) {
const options = {
method: data ? 'POST' : 'GET',
headers: {
    'Content-Type': 'application/json',
}
};

if (data) {
options.body = JSON.stringify(data);
}

const response = await fetch(`api/${endpoint}`, options);
const result = await response.json();

if (!result.success) {
throw new Error(result.message || 'Erro na requisição');
}

return result;
}

async function initializePage() {
try {
const user = await getCurrentUser();

window.currentUser = {
    id: user.id,
    email: user.email,
    name: user.name,
    role: user.role,
    farm_id: user.farm_id
};



// Execute each function independently to avoid blocking
try {
    await setFarmName();
} catch (error) {
}

try {
    await setManagerName();
} catch (error) {
}



try {
    await loadUserProfile();
} catch (error) {
}

try {
    if (typeof loadHeaderProfilePhoto === 'function') {
        await loadHeaderProfilePhoto();
    }
} catch (error) {
}

try {

    await loadDashboardData();
} catch (error) {
}

try {
    await loadVolumeData();
    await loadVolumeRecords();
    // Forçar atualização da lista de registros para garantir dados corretos
    setTimeout(async () => {
        await updateVolumeRecordsList();
        // Corrigir registros existentes sem nome do funcionário
        await fixVolumeRecordsEmployeeNames();
    }, 1000);
} catch (error) {
    console.error('Error loading volume data:', error);
}

try {
    await loadQualityData();
    await loadQualityTests();
} catch (error) {
}

try {
    await loadTemperatureChart();
} catch (error) {
}

try {
    await loadPaymentsData();
} catch (error) {
}

// Carregar gráficos em paralelo para otimizar performance
try {
    const chartPromises = [
        loadDashboardVolumeChart(),
        loadWeeklyVolumeChart(),
        loadDailyVolumeChart(),
        loadDashboardWeeklyChart(),
        loadMonthlyProductionChart()
    ];
    
    // Executar todos os gráficos em paralelo
    await Promise.allSettled(chartPromises);
    console.log('✅ Todos os gráficos carregados');
} catch (error) {
    console.error('Erro ao carregar gráficos:', error);
}

try {
    // Carregar atividades recentes (MySQL)
    if (window.currentUser?.farm_id) {
        await loadRecentActivities(window.currentUser.farm_id);
    }
} catch (error) {
    console.log('⚠️ Erro ao carregar atividades recentes:', error);
}

try {
    await loadUsersData();
} catch (error) {
}

// Configurar atualizações em tempo real
try {
    await setupRealtimeUpdates();
} catch (error) {
}
} catch (error) {
// Show user-friendly message
showNotification('Algumas informações não puderam ser carregadas. Verifique sua conexão.', 'warning');
}
}
// Function to create user if not exists - MySQL stub
async function createUserIfNotExists(authUser) {
    // MySQL: usuários são criados via API, não automaticamente
    return;
}

if (userCheckError) {
    throw userCheckError;
}

if (existingUser) {

    return;
}

// MySQL: Não precisa verificar fazenda - é única (Lagoa do Mato)
const farmName = 'Lagoa do Mato';

let farmId;

if (existingFarm) {

    farmId = existingFarm.id;
} else {
    // Try to create new farm, but handle duplicate name error

    const { data: newFarmId, error: farmError } = await supabase
        .rpc('create_initial_farm', {
            p_name: farmName,
            p_owner_name: authUser.user_metadata?.name || authUser.email?.split('@')[0] || 'Proprietário',
            p_city: authUser.user_metadata?.city || 'Cidade',
            p_state: authUser.user_metadata?.state || 'MG'
        });
    
    if (farmError) {
        // If it's a duplicate name error, try to find the existing farm
        if (farmError.code === '23505' || farmError.message?.includes('duplicate') || farmError.message?.includes('already exists')) {
    
            
            // Wait a bit and try again
            await new Promise(resolve => setTimeout(resolve, 500));
            
            const { data: retryFarm, error: retryError } = await supabase
                .from('farms')
                .select('id')
                .eq('name', farmName)
                .maybeSingle();
            
            if (retryError) {
                throw retryError;
            }
            
            if (retryFarm) {
                farmId = retryFarm.id;
        
            } else {
                throw new Error('Failed to create or find farm');
            }
        } else {
            throw farmError;
        }
    } else {
        farmId = newFarmId;

    }
}

if (!farmId) {
    throw new Error('No farm ID available for user creation');
}

// Create user with the farm_id

const { data: userResult, error: userError } = await supabase
    .rpc('create_initial_user', {
        p_user_id: authUser.id,
        p_farm_id: farmId,
        p_name: authUser.user_metadata?.name || authUser.email?.split('@')[0] || 'Usuário',
        p_email: authUser.email,
        p_role: authUser.user_metadata?.role || 'funcionario',
        p_whatsapp: authUser.user_metadata?.whatsapp || authUser.user_metadata?.phone || ''
    });

if (userError) {
    throw userError;
} else {

}
} catch (error) {
throw error; // Re-throw the error so it can be handled by the caller
}
}

// Function to get farm name from session or Supabase
async function getFarmName() {
try {


// First try to get from local session
const userData = localStorage.getItem('userData') || sessionStorage.getItem('userData');
if (userData) {
    try {
        const user = JSON.parse(userData);
        if (user.farm_name) {
    
            return user.farm_name;
        }
    } catch (error) {
    }
}

// Get current user and their farm
// // const supabase = await getSupabaseClient(); // REMOVIDO // REMOVIDO - usando MySQL
const { data: { user } } = await supabase.auth.getUser();
if (!user) {
    return 'Minha Fazenda';
}

// Get user's farm_id from users table - SEMPRE USAR CONTA PRIMÁRIA
const { data: userDbData, error: userError } = await supabase
    .from('users')
    .select('farm_id')
    .eq('id', user.id)
    .single();
    
if (userError) {
    throw userError;
}

if (!userDbData?.farm_id) {
    // Fallback to auth metadata
    if (user.user_metadata?.farm_name) {
        return user.user_metadata.farm_name;
    }
    return 'Minha Fazenda';
}

// Get farm name from farms table
const { data: farmData, error: farmError } = await supabase
    .from('farms')
    .select('name')
    .eq('id', userDbData.farm_id)
    .single();

if (farmError || !farmData?.name) {
    return 'Minha Fazenda';
}

return farmData.name;

} catch (error) {
return 'Minha Fazenda';
}
}


// Função utilitária para sempre buscar a conta primária
async function getPrimaryUserAccount(email) {
try {
// // const supabase = await getSupabaseClient(); // REMOVIDO // REMOVIDO - usando MySQL
const { data: usersData, error } = await supabase
    .from('users')
    .select('*')
    .eq('email', email)
    .eq('is_active', true)
    .order('created_at', { ascending: true }); // Primeira conta = primária

if (error) {
    console.error('Erro ao buscar conta primária:', error);
    return null;
}

return usersData?.[0] || null; // Sempre retorna a primeira conta
} catch (error) {
console.error('Erro na função getPrimaryUserAccount:', error);
return null;
}
}


// Chamar a inicialização quando a página carregar
document.addEventListener('DOMContentLoaded', function() {
// Inicializar logo da Xandria Store
setTimeout(updateXandriaStoreIcon, 200);
});

// Show notification function melhorada
function showNotification(message, type = 'info') {
// Verificar se é uma mensagem especial que merece modal
if (message.includes('Logo carregada') || message.includes('Configurações salvas com sucesso')) {
showSuccessModal(message, type);
return;
}

// Create notification element com design melhorado
const notification = document.createElement('div');
notification.className = `fixed top-4 right-4 p-4 rounded-xl shadow-2xl z-50 max-w-sm transform transition-all duration-300 border-l-4`;

// Definir cores e ícones baseados no tipo
let bgColor, textColor, borderColor, icon;
switch(type) {
case 'error':
    bgColor = 'bg-red-50';
    textColor = 'text-red-800';
    borderColor = 'border-red-500';
    icon = '🚨';
    break;
case 'warning':
    bgColor = 'bg-yellow-50';
    textColor = 'text-yellow-800';
    borderColor = 'border-yellow-500';
    icon = '⚠️';
    break;
case 'success':
    bgColor = 'bg-green-50';
    textColor = 'text-green-800';
    borderColor = 'border-green-500';
    icon = '✅';
    break;
default:
    bgColor = 'bg-blue-50';
    textColor = 'text-blue-800';
    borderColor = 'border-blue-500';
    icon = 'ℹ️';
}

notification.className += ` ${bgColor} ${textColor} ${borderColor}`;

// Criar conteúdo com ícone
notification.innerHTML = `
<div class="flex items-start space-x-3">
    <span class="text-lg flex-shrink-0">${icon}</span>
    <div class="flex-1">
        <p class="font-medium text-sm">${message}</p>
    </div>
    <button onclick="this.parentElement.parentElement.remove()" class="text-gray-400 hover:text-gray-600 transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
        </svg>
    </button>
</div>
`;

// Adicionar animação de entrada
notification.style.transform = 'translateX(100%)';
notification.style.opacity = '0';

// Add to page
document.body.appendChild(notification);

// Animar entrada
requestAnimationFrame(() => {
notification.style.transform = 'translateX(0)';
notification.style.opacity = '1';
});

// Remove after 5 seconds with animation
setTimeout(() => {
if (notification.parentNode) {
    notification.style.transform = 'translateX(100%)';
    notification.style.opacity = '0';
setTimeout(() => {
if (notification.parentNode) {
    notification.parentNode.removeChild(notification);
        }
    }, 300);
}
}, 5000);
}
// Modal de sucesso especial para mensagens importantes
function showSuccessModal(message, type = 'success') {
// Verificar se já existe um modal
const existingModal = document.getElementById('successModal');
if (existingModal) {
existingModal.remove();
}

// Criar modal
const modal = document.createElement('div');
modal.id = 'successModal';
modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4';

// Determinar ícone e cores baseado na mensagem
let icon, title, bgColor;
if (message.includes('Logo carregada')) {
icon = '🖼️';
title = 'Logo Carregada!';
bgColor = 'from-blue-500 to-blue-600';
} else if (message.includes('Configurações salvas')) {
icon = '⚙️';
title = 'Configurações Salvas!';
bgColor = 'from-green-500 to-green-600';
} else {
icon = '✅';
title = 'Sucesso!';
bgColor = 'from-green-500 to-green-600';
}

modal.innerHTML = `
<div class="bg-white rounded-2xl p-8 max-w-md w-full mx-4 transform transition-all duration-300 scale-95 opacity-0" id="modalContent">
    <!-- Header com gradiente -->
    <div class="text-center mb-6">
        <div class="w-20 h-20 bg-gradient-to-br ${bgColor} rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg">
            <span class="text-3xl">${icon}</span>
        </div>
        <h3 class="text-2xl font-bold text-gray-900 mb-2">${title}</h3>
        <div class="w-16 h-1 bg-gradient-to-r ${bgColor} rounded-full mx-auto"></div>
    </div>
    
    <!-- Mensagem -->
    <div class="text-center mb-8">
        <p class="text-gray-600 leading-relaxed">${message}</p>
    </div>
    
    <!-- Botão -->
    <div class="text-center">
        <button onclick="closeSuccessModal()" class="bg-gradient-to-r ${bgColor} text-white px-8 py-3 rounded-xl font-semibold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200">
            Entendi
        </button>
    </div>
</div>
`;

// Adicionar evento de clique no fundo para fechar
modal.addEventListener('click', function(e) {
if (e.target === modal) {
    closeSuccessModal();
}
});

document.body.appendChild(modal);

// Animar entrada
requestAnimationFrame(() => {
const content = document.getElementById('modalContent');
if (content) {
    content.style.transform = 'scale(1)';
    content.style.opacity = '1';
}
});

// Auto-fechar após 4 segundos
setTimeout(() => {
closeSuccessModal();
}, 4000);
}

// Função para fechar modal de sucesso
function closeSuccessModal() {
const modal = document.getElementById('successModal');
if (modal) {
const content = document.getElementById('modalContent');
if (content) {
    content.style.transform = 'scale(0.95)';
    content.style.opacity = '0';
}
setTimeout(() => {
    modal.remove();
}, 300);
}
}

// Function to get manager name from session or Supabase
async function getManagerName() {
try {


// First try to get from local session
const userData = localStorage.getItem('userData') || sessionStorage.getItem('userData');
if (userData) {
    try {
        const user = JSON.parse(userData);
        if (user.name) {
    
            return user.name;
        }
    } catch (error) {
    }
}

// Fallback to Supabase Auth
const { data: { user } } = await supabase.auth.getUser();

if (!user) {

    return authUser.user_metadata?.name || authUser.email?.split('@')[0] || 'Gerente';
}

// First try to get user from database
const { data, error } = await supabase
    .from('users')
    .select('name')
    .eq('id', user.id)
    .single();



// If user not found in database, return fallback
if (error && error.code === 'PGRST116') {
    return user.user_metadata?.name || user.email?.split('@')[0] || 'Gerente';
}

if (error) {
    console.error('Error in getManagerName:', error);
    return 'Gerente';
}
return data?.name || 'Gerente';
} catch (error) {
console.error('Error fetching manager name:', error);
return 'Gerente';
}
}

// Function to set farm name in header
async function setFarmName() {
const farmName = await getFarmName();
document.getElementById('farmNameHeader').textContent = farmName;
}

// Function to extract formal name (second name)
function extractFormalName(fullName) {
if (!fullName || typeof fullName !== 'string') {
return 'Gerente';
}

// Remove extra spaces and split
const names = fullName.trim().split(/\s+/);

// If only one name, return it
if (names.length === 1) {
return names[0];
}

// If two names, return the second
if (names.length === 2) {
return names[1];
}

// For 3 or more names, try to find the most formal name
// Skip common prefixes and find the second meaningful name
const skipWords = ['da', 'de', 'do', 'das', 'dos', 'di', 'del', 'della', 'delle', 'delli'];

let formalName = '';
let nameCount = 0;

for (let i = 0; i < names.length; i++) {
const name = names[i].toLowerCase();

// Skip common prefixes
if (skipWords.includes(name)) {
    continue;
}

// Count meaningful names
nameCount++;

// Get the second meaningful name
if (nameCount === 2) {
    formalName = names[i];
    break;
}
}

// If we didn't find a second meaningful name, use the second name overall
if (!formalName && names.length >= 2) {
formalName = names[1];
}

// If still no formal name, use the first name
if (!formalName) {
formalName = names[0];
}

// Capitalize first letter
return formalName.charAt(0).toUpperCase() + formalName.slice(1).toLowerCase();
}

// Function to set manager name in profile
async function setManagerName() {
const managerName = await getManagerName();
const farmName = await getFarmName();

// Set with fallback values if empty
const finalManagerName = managerName || 'Gerente';
const finalFarmName = farmName || 'Minha Fazenda';

// Extract formal name for welcome message
const formalName = extractFormalName(finalManagerName);

const elements = [
'profileName',
'profileFullName'
];

elements.forEach(id => {
const element = document.getElementById(id);
if (element) {
    element.textContent = finalManagerName;
}
});

// Set header and welcome message with formal name
const headerElement = document.getElementById('managerName');
const welcomeElement = document.getElementById('managerWelcome');
if (headerElement) {
headerElement.textContent = formalName;
}
if (welcomeElement) {
welcomeElement.textContent = formalName;
}

document.getElementById('profileFarmName').textContent = finalFarmName;
}

// Function to load user profile data
async function loadUserProfile() {
try {
const whatsappElement = document.getElementById('profileWhatsApp');

if (!whatsappElement) {
    return;
}

// First try to get from local session
const sessionData = localStorage.getItem('userData') || sessionStorage.getItem('userData');

if (sessionData) {
    try {
        const user = JSON.parse(sessionData);
        
        // Set profile data from session
        document.getElementById('profileEmail2').textContent = user.email || '';
        const whatsappValue = user.whatsapp || user.phone || 'Não informado';
        document.getElementById('profileWhatsApp').textContent = whatsappValue;
        return;
    } catch (error) {
        // Continue to fallback
    }
}

// Fallback to Supabase Auth
const { data: { user } } = await supabase.auth.getUser();

if (!user) {
    document.getElementById('profileEmail2').textContent = 'Não logado';
    document.getElementById('profileWhatsApp').textContent = 'Não informado';
    return;
}

const { data: userData, error } = await supabase
    .from('users')
    .select('name, email, whatsapp')
    .eq('id', user.id)
    .single();

// If user not found, just show error - don't create automatically
if (error && error.code === 'PGRST116') {
    document.getElementById('profileEmail2').textContent = user.email || '';
    document.getElementById('profileWhatsApp').textContent = 'Usuário não encontrado';
    return;
}

if (error) {
    document.getElementById('profileEmail2').textContent = user.email || '';
    document.getElementById('profileWhatsApp').textContent = 'Erro ao carregar';
    return;
}

// Update profile elements
if (userData) {
    const email = userData.email || user.email || '';
    const whatsapp = userData.whatsapp || 'Não informado';
    
    document.getElementById('profileEmail2').textContent = email;
    document.getElementById('profileWhatsApp').textContent = whatsapp;
    
    // Armazenar email no sessionStorage para identificação de contas secundárias
    sessionStorage.setItem('userEmail', email);
} else {
    const email = user.email || '';
    document.getElementById('profileEmail2').textContent = email;
    document.getElementById('profileWhatsApp').textContent = 'Não informado';
    
    // Armazenar email no sessionStorage mesmo se não encontrar dados completos
    sessionStorage.setItem('userEmail', email);
}
} catch (error) {
document.getElementById('profileEmail2').textContent = 'Erro';
document.getElementById('profileWhatsApp').textContent = 'Erro';
}
}


// Update date and time
function updateDateTime() {
const now = new Date();
const timeString = now.toLocaleTimeString('pt-BR', { 
hour: '2-digit', 
minute: '2-digit' 
});
document.getElementById('lastUpdate').textContent = timeString;
}
// Load dashboard data from Supabase
/**
* Carrega dados do dashboard principal
* Inclui métricas de volume, qualidade, pagamentos e atividades recentes
*/
async function loadDashboardData() {
// Aguardar LacTech estar disponível
if (!window.LacTech || !window.LacTech.supabase) {

await new Promise(resolve => setTimeout(resolve, 1000));
if (!window.supabase) {
    console.error('❌ Supabase não disponível');
    return;
}
}

// // const supabase = await getSupabaseClient(); // REMOVIDO // REMOVIDO - usando MySQL

try {
const { data: { user } } = await supabase.auth.getUser();
if (!user) {
    return;
}

// Get user's farm_id first
const { data: userData, error: userError } = await supabase
    .from('users')
    .select('farm_id')
    .eq('id', user.id)
    .maybeSingle();

if (userError || !userData) {
    return;
}

// Load volume data - handle if table doesn't exist
try {
    const { data: volumeData, error: volumeError } = await supabase
        .from('volume_records')
        .select('volume_liters')
        .eq('farm_id', userData.farm_id)
        .gte('production_date', new Date().toISOString().split('T')[0]);

    let todayVolume = 0;
    if (!volumeError && volumeData && volumeData.length > 0) {
        todayVolume = volumeData.reduce((sum, record) => sum + (record.volume_liters || 0), 0);
    }

    // Adicionar dados locais de hoje
    if (window.offlineSyncManager) {
        const localVolumeData = window.offlineSyncManager.getLocalData('volume');
        const today = new Date().toISOString().split('T')[0];
        const todayLocalData = localVolumeData.filter(record => 
            record.production_date === today && record.farm_id === userData.farm_id
        );
        const todayLocalVolume = todayLocalData.reduce((sum, record) => sum + (record.volume_liters || 0), 0);
        todayVolume += todayLocalVolume;
        console.log(`📊 Volume hoje (online + offline): ${todayVolume}L`);
    }

        document.getElementById('todayVolume').textContent = `${todayVolume} L`;
        
        // Salvar no localStorage para persistir entre sessões
        localStorage.setItem('todayVolume', todayVolume.toString());
        localStorage.setItem('todayVolumeDate', new Date().toISOString().split('T')[0]);
} catch (error) {
    console.error('❌ Erro ao carregar volume de hoje:', error);
    document.getElementById('todayVolume').textContent = '0 L';
    localStorage.setItem('todayVolume', '0');
    localStorage.setItem('todayVolumeDate', new Date().toISOString().split('T')[0]);
}

// Load quality data - handle if table doesn't exist
try {
    // Buscar dados dos últimos 30 dias ao invés de apenas hoje
    const thirtyDaysAgo = new Date();
    thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
    
    
    
    const { data: qualityData, error: qualityError } = await supabase
        .from('quality_tests')
        .select('fat_percentage, protein_percentage')
        .eq('farm_id', userData.farm_id)
        .gte('test_date', thirtyDaysAgo.toISOString().split('T')[0]);
        
    

    if (!qualityError && qualityData && qualityData.length > 0) {
        // Calculate quality score based on fat and protein percentages
        let totalScore = 0;
        let validTests = 0;
        
        qualityData.forEach(record => {
            if (record.fat_percentage && record.protein_percentage) {
                // Quality score: fat (3.0-4.5% ideal) + protein (3.0-3.8% ideal)
                const fatScore = Math.min(100, Math.max(0, (record.fat_percentage / 4.0) * 100));
                const proteinScore = Math.min(100, Math.max(0, (record.protein_percentage / 3.5) * 100));
                const testScore = (fatScore + proteinScore) / 2;
                totalScore += testScore;
                validTests++;
            }
        });
        
        if (validTests > 0) {
            const avgQuality = totalScore / validTests;
            document.getElementById('qualityAverage').textContent = `${Math.round(avgQuality)}%`;
        } else {
            document.getElementById('qualityAverage').textContent = '--%';
        }
    } else {
        document.getElementById('qualityAverage').textContent = '--%';
    }
} catch (error) {

    document.getElementById('qualityAverage').textContent = '--%';
}

// Load financial data - handle if table doesn't exist
try {
    const { data: financialData, error: financialError } = await supabase
        .from('financial_records')
        .select('amount, type')
        .eq('farm_id', userData.farm_id)
        .eq('type', 'expense')
        .gte('created_at', new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString());

    if (!financialError && financialData && financialData.length > 0) {
        const pendingAmount = financialData.reduce((sum, record) => sum + (record.amount || 0), 0);
        document.getElementById('pendingPayments').textContent = `R$ ${pendingAmount.toLocaleString('pt-BR')}`;
    } else {
        document.getElementById('pendingPayments').textContent = 'R$ 0';
    }
} catch (error) {

    document.getElementById('pendingPayments').textContent = 'R$ 0';
}

// Load users data
try {
    const { data: usersData, error: usersError } = await supabase
        .from('users')
        .select('id')
        .eq('farm_id', userData.farm_id)
        .eq('is_active', true);

    if (!usersError && usersData) {
        document.getElementById('activeUsers').textContent = usersData.length;
    } else {
        document.getElementById('activeUsers').textContent = '0';
    }
} catch (error) {

    document.getElementById('activeUsers').textContent = '0';
}

// Load recent activities
await loadRecentActivities(userData.farm_id);

// Restaurar volume salvo se for do mesmo dia
restoreSavedVolume();



} catch (error) {
console.error('❌ ERRO GERAL em loadDashboardData:', error);
console.error('Stack trace:', error.stack);
}
}

// Função para restaurar volume salvo do localStorage
function restoreSavedVolume() {
try {
const savedVolume = localStorage.getItem('todayVolume');
const savedDate = localStorage.getItem('todayVolumeDate');
const today = new Date().toISOString().split('T')[0];

if (savedVolume && savedDate === today) {
    const volumeElement = document.getElementById('todayVolume');
    if (volumeElement) {
        volumeElement.textContent = `${savedVolume} L`;
    }
} else if (savedDate !== today) {
    // Se a data mudou, limpar dados antigos
    localStorage.removeItem('todayVolume');
    localStorage.removeItem('todayVolumeDate');
}
} catch (error) {
console.error('❌ Erro ao restaurar volume salvo:', error);
}
}

// Load volume data from database and local storage
async function loadVolumeData() {
// Aguardar Supabase estar disponível
if (!window.supabase) {
await new Promise(resolve => setTimeout(resolve, 1000));
if (!window.supabase) {
    console.error('❌ Supabase não disponível para volume');
    return;
}
}

// // const supabase = await getSupabaseClient(); // REMOVIDO // REMOVIDO - usando MySQL

try {
const { data: { user } } = await supabase.auth.getUser();
if (!user) throw new Error('User not authenticated');

const { data: volumeUserData, error: userError } = await supabase
    .from('users')
    .select('farm_id')
    .eq('id', user.id)
    .single();

if (userError) {
    throw userError;
}

// Carregar dados locais se disponível
let localVolumeData = [];
if (window.offlineSyncManager) {
    localVolumeData = window.offlineSyncManager.getLocalData('volume');
    console.log(`📊 ${localVolumeData.length} registros locais de volume carregados`);
}

// Forçar atualização se cache foi limpo
if (CacheManager.forceRefresh) {
    console.log('🔄 Cache foi limpo, forçando atualização...');
    CacheManager.forceRefresh = false;
}

// Get today's production data
const today = new Date().toISOString().split('T')[0];
const { data: todayData, error: todayError } = await supabase
    .from('volume_records')
    .select('volume_liters')
    .eq('farm_id', volumeUserData.farm_id)
    .gte('production_date', today)
    .lte('production_date', today);

if (todayError) {
}

// Get week average
const weekAgo = new Date();
weekAgo.setDate(weekAgo.getDate() - 7);
const { data: weekData, error: weekError } = await supabase
    .from('volume_records')
    .select('volume_liters')
    .eq('farm_id', volumeUserData.farm_id)
    .gte('production_date', weekAgo.toISOString().split('T')[0]);

if (weekError) {
}

// Calcular volume de hoje incluindo dados locais
let todayVolume = todayData?.reduce((sum, record) => sum + (record.volume_liters || 0), 0) || 0;

// Adicionar dados locais de hoje
const todayLocalData = localVolumeData.filter(record => 
    record.production_date === today && record.farm_id === volumeUserData.farm_id
);
const todayLocalVolume = todayLocalData.reduce((sum, record) => sum + (record.volume_liters || 0), 0);
todayVolume += todayLocalVolume;

// Calcular média semanal incluindo dados locais
let weekAvg = weekData?.length > 0 ? 
    weekData.reduce((sum, record) => sum + (record.volume_liters || 0), 0) / weekData.length : 0;

// Adicionar dados locais da semana
const weekLocalData = localVolumeData.filter(record => {
    const recordDate = new Date(record.production_date);
    return recordDate >= weekAgo && record.farm_id === volumeUserData.farm_id;
});
const weekLocalVolume = weekLocalData.reduce((sum, record) => sum + (record.volume_liters || 0), 0);
const totalWeekData = (weekData?.length || 0) + weekLocalData.length;
if (totalWeekData > 0) {
    const totalWeekVolume = (weekData?.reduce((sum, record) => sum + (record.volume_liters || 0), 0) || 0) + weekLocalVolume;
    weekAvg = totalWeekVolume / totalWeekData;
}

const growth = weekAvg > 0 ? ((todayVolume - weekAvg) / weekAvg * 100) : 0;

// Atualizar elementos da interface
const volumeTodayElement = document.getElementById('volumeToday');
const volumeWeekAvgElement = document.getElementById('volumeWeekAvg');
const volumeGrowthElement = document.getElementById('volumeGrowth');

if (volumeTodayElement) {
    volumeTodayElement.textContent = `${todayVolume.toFixed(0)} L`;
    console.log(`📊 Volume hoje atualizado: ${todayVolume.toFixed(0)} L`);
} else {
    console.error('❌ Elemento volumeToday não encontrado');
}

if (volumeWeekAvgElement) {
    volumeWeekAvgElement.textContent = `${weekAvg.toFixed(0)} L`;
    console.log(`📊 Média semanal atualizada: ${weekAvg.toFixed(0)} L`);
} else {
    console.error('❌ Elemento volumeWeekAvg não encontrado');
}

if (volumeGrowthElement) {
    volumeGrowthElement.textContent = `${growth > 0 ? '+' : ''}${growth.toFixed(1)}%`;
    console.log(`📊 Crescimento atualizado: ${growth > 0 ? '+' : ''}${growth.toFixed(1)}%`);
} else {
    console.error('❌ Elemento volumeGrowth não encontrado');
}

// Buscar a última coleta real do banco de dados
const { data: lastCollectionData, error: lastCollectionError } = await supabase
    .from('volume_records')
    .select('created_at')
    .eq('farm_id', volumeUserData.farm_id)
    .order('created_at', { ascending: false })
    .limit(1);

if (!lastCollectionError && lastCollectionData && lastCollectionData.length > 0) {
    const lastCollectionTime = new Date(lastCollectionData[0].created_at);
    const dateStr = lastCollectionTime.toLocaleDateString('pt-BR', { 
        day: '2-digit', 
        month: '2-digit', 
        year: 'numeric' 
    });
    const timeStr = lastCollectionTime.toLocaleTimeString('pt-BR', { 
        hour: '2-digit', 
        minute: '2-digit' 
    });
    document.getElementById('lastCollection').textContent = `${dateStr} - ${timeStr}`;
} else {
    document.getElementById('lastCollection').textContent = '--/--/---- - --:--';
}

} catch (error) {
console.error('Error loading volume data:', error);
// Set default values on error
document.getElementById('volumeToday').textContent = '0 L';
document.getElementById('volumeWeekAvg').textContent = '0 L';
document.getElementById('volumeGrowth').textContent = '0%';
document.getElementById('lastCollection').textContent = '--:--';
}
}

// Load quality data from database
async function loadQualityData() {
try {
// Aguardar Supabase estar disponível
if (!window.supabase) {
    await new Promise(resolve => setTimeout(resolve, 1000));
    if (!window.supabase) {
        console.error('❌ Supabase não disponível para qualidade');
        throw new Error('Supabase not available');
    }
}

// // const supabase = await getSupabaseClient(); // REMOVIDO // REMOVIDO - usando MySQL

const { data: { user } } = await supabase.auth.getUser();
if (!user) {
    throw new Error('User not authenticated');
}



const { data: qualityUserData, error: userError } = await supabase
    .from('users')
    .select('farm_id')
    .eq('id', user.id)
    .single();

if (userError) {
    console.error('❌ Erro ao buscar dados do usuário:', userError);
    throw userError;
}

if (!qualityUserData || !qualityUserData.farm_id) {
    console.error('❌ Farm ID não encontrado para o usuário');
    throw new Error('Farm ID not found');
}



// Get latest quality data


const { data: qualityData, error } = await supabase
    .from('quality_tests')
    .select('fat_percentage, protein_percentage, scc, cbt, test_date')
    .eq('farm_id', qualityUserData.farm_id)
    .not('fat_percentage', 'is', null)
    .not('protein_percentage', 'is', null)
    .order('test_date', { ascending: false })
    .limit(30);

if (error) {
    console.error('❌ Erro ao buscar dados de qualidade:', error);
    throw error;
}


if (qualityData && qualityData.length > 0) {

}

if (qualityData && qualityData.length > 0) {
    // Filter out records with invalid data
    const validQualityData = qualityData.filter(record => 
        record.fat_percentage && 
        record.protein_percentage && 
        !isNaN(record.fat_percentage) && 
        !isNaN(record.protein_percentage)
    );
    

    
    if (validQualityData.length === 0) {

        updateQualityDisplay('--', '--', '--', '--', '--%');
        updateQualityCharts([]);
        return;
    }
    
    const avgFat = validQualityData.reduce((sum, record) => sum + (record.fat_percentage || 0), 0) / validQualityData.length;
    const avgProtein = validQualityData.reduce((sum, record) => sum + (record.protein_percentage || 0), 0) / validQualityData.length;
                    const avgSCC = validQualityData.reduce((sum, record) => sum + (record.scc || 0), 0) / validQualityData.length;
                    const avgTBC = validQualityData.reduce((sum, record) => sum + (record.cbt || 0), 0) / validQualityData.length;
    

    
    // Calculate quality score based on industry standards
    let totalScore = 0;
    let validTests = 0;
    
    validQualityData.forEach(record => {
        if (record.fat_percentage && record.protein_percentage) {
            // Quality scoring based on industry standards
            // Fat: 3.0-4.5% is excellent, 2.5-3.0% is good, <2.5% is poor
            // Protein: 3.0-3.8% is excellent, 2.7-3.0% is good, <2.7% is poor
            
            let fatScore = 0;
            if (record.fat_percentage >= 3.0 && record.fat_percentage <= 4.5) {
                fatScore = 100; // Excellent range
            } else if (record.fat_percentage >= 2.5 && record.fat_percentage < 3.0) {
                fatScore = 70; // Good range
            } else if (record.fat_percentage > 4.5) {
                fatScore = 80; // Above excellent, still good
            } else {
                fatScore = Math.max(0, (record.fat_percentage / 2.5) * 70); // Poor range
            }
            
            let proteinScore = 0;
            if (record.protein_percentage >= 3.0 && record.protein_percentage <= 3.8) {
                proteinScore = 100; // Excellent range
            } else if (record.protein_percentage >= 2.7 && record.protein_percentage < 3.0) {
                proteinScore = 70; // Good range
            } else if (record.protein_percentage > 3.8) {
                proteinScore = 80; // Above excellent, still good
            } else {
                proteinScore = Math.max(0, (record.protein_percentage / 2.7) * 70); // Poor range
            }
            
            const testScore = (fatScore + proteinScore) / 2;
            totalScore += testScore;
            validTests++;
            

        }
    });
    
    const avgQuality = validTests > 0 ? totalScore / validTests : 0;

    
    // Atualizar elementos do DOM com verificação de existência
    const fatContent = `${avgFat.toFixed(1)}%`;
    const proteinContent = `${avgProtein.toFixed(1)}%`;
    const sccContent = avgSCC > 0 ? `${Math.round(avgSCC / 1000)}k` : '--';
    const tbcContent = avgTBC > 0 ? `${Math.round(avgTBC / 1000)}k` : '--';
    const qualityContent = `${Math.round(avgQuality)}%`;
    
    updateQualityDisplay(fatContent, proteinContent, sccContent, tbcContent, qualityContent);
    

    
    // Update quality charts
    updateQualityCharts(validQualityData);
} else {

    updateQualityDisplay('--', '--', '--', '--', '--%');
    updateQualityCharts([]);
}

} catch (error) {
console.error('❌ Erro ao carregar dados de qualidade:', error);
updateQualityDisplay('--', '--', '--', '--', '--%');
updateQualityCharts([]);
}
}

// Função auxiliar para atualizar elementos de qualidade com verificação de existência
function updateQualityDisplay(fat, protein, scc, tbc, quality) {
const elements = {
'fatContent': fat,
'proteinContent': protein,
'sccCount': scc,
'tbc': tbc,
'qualityAverage': quality
};

Object.entries(elements).forEach(([id, value]) => {
const element = document.getElementById(id);
if (element) {
    element.textContent = value;

} else {
}
});
}
// Load sales data from database
async function loadPaymentsData() {
// Aguardar Supabase estar disponível
if (!window.supabase) {
await new Promise(resolve => setTimeout(resolve, 1000));
if (!window.supabase) {
    console.error('❌ Supabase não disponível para pagamentos');
    return;
}
}

// // const supabase = await getSupabaseClient(); // REMOVIDO // REMOVIDO - usando MySQL

try {
const { data: { user } } = await supabase.auth.getUser();
if (!user) throw new Error('User not authenticated');

const { data: userData, error: userError } = await supabase
    .from('users')
    .select('farm_id')
    .eq('id', user.id)
    .maybeSingle();

if (userError) {
    throw userError;
}

// Get sales data from financial records table
const { data: salesData, error: salesError } = await supabase
    .from('financial_records')
    .select('amount, type, record_date, created_at, description')
    .eq('farm_id', userData.farm_id)
    .eq('type', 'income')
    .order('created_at', { ascending: false });

if (salesError) {
    throw salesError;
}

let completedAmount = 0;
let pendingAmount = 0;
let overdueAmount = 0;

if (salesData && salesData.length > 0) {
    // Para financial_records, consideramos todas as receitas como "pagas"
    // pois são registros de transações já realizadas
    salesData.forEach(sale => {
        const amount = parseFloat(sale.amount) || 0;
        completedAmount += amount;
    });
}

// Update UI elements
document.getElementById('paidAmount').textContent = `R$ ${completedAmount.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}`;
document.getElementById('pendingAmount').textContent = `R$ ${pendingAmount.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}`;
document.getElementById('overdueAmount').textContent = `R$ ${overdueAmount.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}`;

// Update sales chart if it exists
if (window.paymentsChart && window.paymentsChart.data && window.paymentsChart.data.datasets) {
    window.paymentsChart.data.labels = ['Pagos', 'Pendentes', 'Atrasados'];
    window.paymentsChart.data.datasets[0].data = [completedAmount, pendingAmount, overdueAmount];
    window.paymentsChart.update();
}

} catch (error) {
console.error('Error loading sales data:', error);
// Set default values on error
document.getElementById('paidAmount').textContent = 'R$ 0,00';
document.getElementById('pendingAmount').textContent = 'R$ 0,00';
document.getElementById('overdueAmount').textContent = 'R$ 0,00';
}
}

// Load users data from database
async function loadUsersData() {
try {
// Aguardar Supabase estar disponível
if (!window.supabase) {
    await new Promise(resolve => setTimeout(resolve, 1000));
    if (!window.supabase) {
        console.error('❌ Supabase não disponível para usuários');
        throw new Error('Supabase not available');
    }
}

// // const supabase = await getSupabaseClient(); // REMOVIDO // REMOVIDO - usando MySQL

const { data: { user } } = await supabase.auth.getUser();
if (!user) throw new Error('User not authenticated');

let { data: usersUserData, error: userError } = await supabase
    .from('users')
    .select('farm_id')
    .eq('id', user.id)
    .maybeSingle();

if (userError) {
    console.error('❌ Erro ao buscar dados do usuário:', userError);
    throw userError;
}
if (!usersUserData) {
    console.error('❌ Dados do usuário não encontrados para ID:', user.id);
    throw new Error('User data not found');
}

const { data: usersData, error } = await supabase
    .from('users')
    .select('id, name, email, role, whatsapp, is_active, created_at, profile_photo_url')
    .eq('farm_id', usersUserData.farm_id)
    .order('created_at', { ascending: false });

if (usersData) {
    const employeesCount = usersData.filter(u => u.role === 'funcionario').length;
    const veterinariansCount = usersData.filter(u => u.role === 'veterinario').length;
    const managersCount = usersData.filter(u => u.role === 'gerente').length;
    const totalUsers = usersData.length;
    
    document.getElementById('totalUsers').textContent = totalUsers;
    document.getElementById('employeesCount').textContent = employeesCount;
    document.getElementById('veterinariansCount').textContent = veterinariansCount;
    document.getElementById('managersCount').textContent = managersCount;
    
setTimeout(() => {
    displayUsersList(usersData);
    
    document.querySelectorAll('.action-button.permissions').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const userId = this.getAttribute('data-user-id');
            const currentStatus = this.getAttribute('data-current-status') === 'true';
            toggleUserAccess(userId, currentStatus);
        });
    });
}, 100);
} else {
    document.getElementById('totalUsers').textContent = '1';
    document.getElementById('employeesCount').textContent = '0';
    document.getElementById('veterinariansCount').textContent = '0';
    document.getElementById('managersCount').textContent = '1';
    displayUsersList([]);
}

} catch (error) {
console.error('Error loading users data:', error);
// Set default values on error
document.getElementById('totalUsers').textContent = '1';
document.getElementById('employeesCount').textContent = '0';
document.getElementById('veterinariansCount').textContent = '0';
document.getElementById('managersCount').textContent = '1';
displayUsersList([]);
}
}

// Load weekly volume chart data
async function loadWeeklyVolumeChart() {
try {
// Aguardar Supabase estar disponível
if (!window.supabase) {
    await new Promise(resolve => setTimeout(resolve, 1000));
    if (!window.supabase) {
        console.error('❌ Supabase não disponível para gráfico semanal');
        return;
    }
}

// // const supabase = await getSupabaseClient(); // REMOVIDO // REMOVIDO - usando MySQL
const { data: { user } } = await supabase.auth.getUser();
if (!user) return;

const { data: userData, error: userError } = await supabase
    .from('users')
    .select('farm_id')
    .eq('id', user.id)
    .maybeSingle();

if (userError) {
    return;
}

// Get last 7 days of data
const endDate = new Date();
const startDate = new Date();
startDate.setDate(startDate.getDate() - 6);

const { data: volumeData, error } = await supabase
    .from('volume_records')
    .select('production_date, volume_liters')
    .eq('farm_id', userData.farm_id)
    .gte('production_date', startDate.toISOString().split('T')[0])
    .lte('production_date', endDate.toISOString().split('T')[0])
    .order('production_date', { ascending: true });

if (error) {
    return;
}

// Group by date and sum volumes
const dailyVolumes = {};
const labels = [];

// Initialize all days with 0
for (let i = 0; i < 7; i++) {
    const date = new Date(startDate);
    date.setDate(date.getDate() + i);
    const dateStr = date.toISOString().split('T')[0];
    const dayName = date.toLocaleDateString('pt-BR', { weekday: 'short' });
    labels.push(dayName);
    dailyVolumes[dateStr] = 0;
}

// Sum volumes by date
if (volumeData) {
    volumeData.forEach(record => {
        if (dailyVolumes.hasOwnProperty(record.production_date)) {
            dailyVolumes[record.production_date] += record.volume_liters || 0;
        }
    });
}

const data = Object.values(dailyVolumes);
const hasRealData = data.some(value => value > 0);

// Update chart
if (window.weeklyVolumeChart && hasRealData) {
    window.weeklyVolumeChart.data.labels = labels;
    window.weeklyVolumeChart.data.datasets[0].data = data;
    window.weeklyVolumeChart.update();
}

} catch (error) {
console.error('Error loading weekly volume chart:', error);
}
}

// Load daily volume chart data
async function loadDailyVolumeChart() {
try {
// // const supabase = await getSupabaseClient(); // REMOVIDO // REMOVIDO - usando MySQL
const { data: { user } } = await supabase.auth.getUser();
if (!user) return;

const { data: userData, error: userError } = await supabase
    .from('users')
    .select('farm_id')
    .eq('id', user.id)
    .single();

if (userError) {
    return;
}

// Get today's data by shift
const today = new Date().toISOString().split('T')[0];
const { data: volumeData, error } = await supabase
    .from('volume_records')
    .select('milking_type, volume_liters')
    .eq('farm_id', userData.farm_id)
    .eq('production_date', today)
    .order('milking_type', { ascending: true });

if (error) {
    return;
}

// Group by shift
const shiftVolumes = {
    'morning': 0,
    'afternoon': 0,
    'evening': 0,
    'night': 0
};

if (volumeData) {
    volumeData.forEach(record => {
        if (shiftVolumes.hasOwnProperty(record.milking_type)) {
            shiftVolumes[record.milking_type] += record.volume_liters || 0;
        }
    });
}

const labels = ['Manhã', 'Tarde', 'Noite', 'Madrugada'];
const data = [shiftVolumes.morning, shiftVolumes.afternoon, shiftVolumes.evening, shiftVolumes.night];

// Update chart
if (window.dailyVolumeChart) {
    window.dailyVolumeChart.data.labels = labels;
    window.dailyVolumeChart.data.datasets[0].data = data;
    window.dailyVolumeChart.update();
}

} catch (error) {
console.error('Error loading daily volume chart:', error);
}
}

// Load monthly volume chart data (only shows on last day of month)
async function loadMonthlyVolumeChart() {
try {
const now = new Date();
const lastDayOfMonth = new Date(now.getFullYear(), now.getMonth() + 1, 0);

// Only show monthly chart on the last day of the month
if (now.getDate() !== lastDayOfMonth.getDate()) {
    return;
}

const { data: { user } } = await supabase.auth.getUser();
if (!user) return;

const { data: userData, error: userError } = await supabase
    .from('users')
    .select('farm_id')
    .eq('id', user.id)
    .single();

if (userError) {
    return;
}

// Get last 12 months of data
const endDate = new Date();
const startDate = new Date();
startDate.setMonth(startDate.getMonth() - 11);
startDate.setDate(1);

const { data: volumeData, error } = await supabase
    .from('volume_records')
    .select('production_date, volume_liters')
    .eq('farm_id', userData.farm_id)
    .gte('production_date', startDate.toISOString().split('T')[0])
    .lte('production_date', endDate.toISOString().split('T')[0])
    .order('production_date', { ascending: true });

if (error) {
    return;
}

// Group by month and sum volumes
const monthlyVolumes = {};
const labels = [];

// Initialize all months with 0
for (let i = 0; i < 12; i++) {
    const date = new Date(startDate);
    date.setMonth(date.getMonth() + i);
    const monthKey = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
    const monthName = date.toLocaleDateString('pt-BR', { month: 'short', year: '2-digit' });
    labels.push(monthName);
    monthlyVolumes[monthKey] = 0;
}

// Sum volumes by month
if (volumeData) {
    volumeData.forEach(record => {
        const date = new Date(record.production_date);
        const monthKey = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
        if (monthlyVolumes.hasOwnProperty(monthKey)) {
            monthlyVolumes[monthKey] += record.volume_liters || 0;
        }
    });
}
const data = Object.values(monthlyVolumes);

// Show monthly chart container and update chart
const monthlyChartContainer = document.getElementById('monthlyVolumeChartContainer');
if (monthlyChartContainer) {
    monthlyChartContainer.style.display = 'block';
    
    if (window.monthlyVolumeChart) {
        window.monthlyVolumeChart.data.labels = labels;
        window.monthlyVolumeChart.data.datasets[0].data = data;
        window.monthlyVolumeChart.update();
    }
}

} catch (error) {
console.error('Error loading monthly volume chart:', error);
}
}

// Load weekly summary chart (only shows on Sundays)
async function loadWeeklySummaryChart() {
try {
const now = new Date();

// Only show weekly summary on Sundays (0 = Sunday)
if (now.getDay() !== 0) {
    return;
}

const { data: { user } } = await supabase.auth.getUser();
if (!user) return;

const { data: userData, error: userError } = await supabase
    .from('users')
    .select('farm_id')
    .eq('id', user.id)
    .single();

if (userError) {
    return;
}

// Get last 8 weeks of data
const endDate = new Date();
const startDate = new Date();
startDate.setDate(startDate.getDate() - 56); // 8 weeks

const { data: volumeData, error } = await supabase
    .from('volume_records')
    .select('production_date, volume_liters')
    .eq('farm_id', userData.farm_id)
    .gte('production_date', startDate.toISOString().split('T')[0])
    .lte('production_date', endDate.toISOString().split('T')[0])
    .order('production_date', { ascending: true });

if (error) {
    return;
}

// Group by week and sum volumes
const weeklyVolumes = {};
const labels = [];

// Initialize all weeks with 0
for (let i = 0; i < 8; i++) {
    const weekStart = new Date(startDate);
    weekStart.setDate(weekStart.getDate() + (i * 7));
    const weekKey = getWeekKey(weekStart);
    const weekLabel = `Sem ${i + 1}`;
    labels.push(weekLabel);
    weeklyVolumes[weekKey] = 0;
}

// Sum volumes by week
if (volumeData) {
    volumeData.forEach(record => {
        const date = new Date(record.production_date);
        const weekKey = getWeekKey(date);
        if (weeklyVolumes.hasOwnProperty(weekKey)) {
            weeklyVolumes[weekKey] += record.volume_liters || 0;
        }
    });
}

const data = Object.values(weeklyVolumes);

// Show weekly summary chart container and update chart
const weeklySummaryContainer = document.getElementById('weeklySummaryChartContainer');
if (weeklySummaryContainer) {
    weeklySummaryContainer.style.display = 'block';
    
    if (window.weeklySummaryChart) {
        window.weeklySummaryChart.data.labels = labels;
        window.weeklySummaryChart.data.datasets[0].data = data;
        window.weeklySummaryChart.update();
    }
}

} catch (error) {
console.error('Error loading weekly summary chart:', error);
}
}

// Helper function to get week key
function getWeekKey(date) {
const startOfWeek = new Date(date);
startOfWeek.setDate(date.getDate() - date.getDay());
return startOfWeek.toISOString().split('T')[0];
}

// Load dashboard volume chart (last 7 days)
async function loadDashboardVolumeChart() {
try {
console.log('🔄 Carregando gráfico Volume Semanal...');

// // const supabase = await getSupabaseClient(); // REMOVIDO // REMOVIDO - usando MySQL
if (!supabase) {
    console.error('❌ Supabase não disponível para Volume Semanal');
    return;
}

// Usar cache para dados do usuário
const userData = await CacheManager.getUserData();

if (!userData?.farm_id) {
    console.error('❌ Farm ID não encontrado para Volume Semanal');
    return;
}

console.log('🏡 Farm ID para Volume Semanal:', userData.farm_id);

// Get last 7 days of data
const endDate = new Date();
const startDate = new Date();
startDate.setDate(startDate.getDate() - 6);

console.log('📅 Período Volume Semanal:', startDate.toISOString().split('T')[0], 'até', endDate.toISOString().split('T')[0]);

const { data: volumeData, error } = await supabase
    .from('volume_records')
    .select('production_date, volume_liters')
    .eq('farm_id', userData.farm_id)
    .gte('production_date', startDate.toISOString().split('T')[0])
    .lte('production_date', endDate.toISOString().split('T')[0])
    .order('production_date', { ascending: true });

if (error) {
    console.error('❌ Erro ao buscar dados de volume:', error);
    return;
}

console.log('📊 Dados de volume encontrados:', volumeData?.length || 0, 'registros');

// Group by date and sum volumes
const dailyVolumes = {};
const labels = [];

// Initialize all days with 0
for (let i = 0; i < 7; i++) {
    const date = new Date(startDate);
    date.setDate(date.getDate() + i);
    const dateStr = date.toISOString().split('T')[0];
    const dayName = date.toLocaleDateString('pt-BR', { weekday: 'short' });
    labels.push(dayName);
    dailyVolumes[dateStr] = 0;
}

// Sum volumes by date
if (volumeData && volumeData.length > 0) {
    volumeData.forEach(record => {
        if (dailyVolumes.hasOwnProperty(record.production_date)) {
            dailyVolumes[record.production_date] += record.volume_liters || 0;
        }
    });
}

const data = Object.values(dailyVolumes);
const hasRealData = data.some(value => value > 0);

console.log('📈 Dados processados Volume Semanal:', { labels, data, hasRealData });

// Update chart - sempre mostrar o gráfico, mesmo sem dados
if (window.volumeChart) {
    console.log('✅ Atualizando gráfico Volume Semanal...');
    window.volumeChart.data.labels = labels;
    window.volumeChart.data.datasets[0].data = data;
    window.volumeChart.update();
    console.log('✅ Gráfico Volume Semanal atualizado com sucesso');
} else {
    console.error('❌ Gráfico volumeChart não encontrado, tentando reinicializar...');
    // Tentar reinicializar o gráfico
        const volumeCtx = document.getElementById('volumeChart');
        if (volumeCtx) {
            window.volumeChart = new Chart(volumeCtx, {
                type: 'line',
                data: {
                labels: labels,
                    datasets: [{
                        label: 'Volume (L)',
                    data: data,
                        borderColor: '#369e36',
                        backgroundColor: 'rgba(54, 158, 54, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        console.log('✅ Gráfico Volume Semanal reinicializado com sucesso');
    } else {
        console.error('❌ Elemento volumeChart não encontrado no DOM');
    }
}

} catch (error) {
console.error('❌ Erro ao carregar gráfico Volume Semanal:', error);
}
}

// Load dashboard weekly production chart (last 7 days)
async function loadDashboardWeeklyChart() {
try {
console.log('🔄 Carregando gráfico de produção semanal...');

// // const supabase = await getSupabaseClient(); // REMOVIDO // REMOVIDO - usando MySQL
if (!supabase) {
    console.error('❌ Supabase não disponível');
    return;
}

// Usar cache para dados do usuário
const userData = await CacheManager.getUserData();

if (!userData?.farm_id) {
    console.error('❌ Farm ID não encontrado');
    return;
}

console.log('🏡 Farm ID:', userData.farm_id);

// Get last 7 days of data
const endDate = new Date();
const startDate = new Date();
startDate.setDate(startDate.getDate() - 6);

console.log('📅 Período:', startDate.toISOString().split('T')[0], 'até', endDate.toISOString().split('T')[0]);

// Usar cache para dados de volume semanal (forçar atualização se necessário)
const productionData = await CacheManager.getVolumeData(userData.farm_id, 'week', CacheManager.forceRefresh);

if (!productionData) {
    console.error('❌ Erro ao buscar dados de produção');
    return;
}

console.log('📊 Dados de produção encontrados:', productionData?.length || 0, 'registros');

// Carregar dados locais se disponível
let localVolumeData = [];
if (window.offlineSyncManager) {
    localVolumeData = window.offlineSyncManager.getLocalData('volume');
    console.log(`📊 ${localVolumeData.length} registros locais de volume carregados`);
}

// Group by date and sum volumes
const dailyProduction = {};
const labels = [];

// Initialize all days with 0
for (let i = 0; i < 7; i++) {
    const date = new Date(startDate);
    date.setDate(date.getDate() + i);
    const dateStr = date.toISOString().split('T')[0];
    const dayName = date.toLocaleDateString('pt-BR', { weekday: 'short' });
    labels.push(dayName);
    dailyProduction[dateStr] = 0;
}

// Sum production by date (dados online)
if (productionData && productionData.length > 0) {
    productionData.forEach(record => {
        if (dailyProduction.hasOwnProperty(record.production_date)) {
            dailyProduction[record.production_date] += record.volume_liters || 0;
        }
    });
}

// Adicionar dados locais (offline)
if (localVolumeData && localVolumeData.length > 0) {
    localVolumeData.forEach(record => {
        if (dailyProduction.hasOwnProperty(record.production_date) && record.farm_id === userData.farm_id) {
            dailyProduction[record.production_date] += record.volume_liters || 0;
            console.log(`📊 Adicionando volume local: ${record.volume_liters}L para ${record.production_date}`);
        }
    });
}

const data = Object.values(dailyProduction);
console.log('📈 Dados processados (online + offline):', { labels, data });
console.log('📈 Produção diária:', dailyProduction);

// Update chart
if (window.dashboardWeeklyChart) {
    console.log('✅ Atualizando gráfico...');
    window.dashboardWeeklyChart.data.labels = labels;
    window.dashboardWeeklyChart.data.datasets[0].data = data;
    window.dashboardWeeklyChart.update();
    console.log('✅ Gráfico atualizado com sucesso');
} else {
    console.error('❌ Gráfico dashboardWeeklyChart não encontrado, tentando reinicializar...');
    // Tentar reinicializar o gráfico
    const dashboardWeeklyCtx = document.getElementById('dashboardWeeklyChart');
    if (dashboardWeeklyCtx) {
        window.dashboardWeeklyChart = new Chart(dashboardWeeklyCtx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Produção (L)',
                    data: data,
                    backgroundColor: '#5bb85b',
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        // Escala dinâmica - sem limite máximo
                        ticks: {
                            callback: function(value) {
                                return value + 'L';
                            }
                        }
                    }
                }
            }
        });
        console.log('✅ Gráfico reinicializado com sucesso');
    } else {
        console.error('❌ Elemento dashboardWeeklyChart não encontrado no DOM');
    }
}

} catch (error) {
console.error('❌ Erro ao carregar gráfico de produção semanal:', error);
}
}

// Load monthly production chart
async function loadMonthlyProductionChart() {
try {
console.log('🔄 Carregando gráfico de produção mensal...');

// // const supabase = await getSupabaseClient(); // REMOVIDO // REMOVIDO - usando MySQL
if (!supabase) {
    console.error('❌ Cliente Supabase não disponível');
    return;
}

// Usar cache para dados do usuário
const userData = await CacheManager.getUserData();

if (!userData?.farm_id) {
    console.error('❌ Farm ID não encontrado');
    return;
}

console.log('✅ Usuário autenticado, farm_id:', userData.farm_id);

// Get first day of current month
const hoje = new Date();
const primeiroDiaMes = new Date(hoje.getFullYear(), hoje.getMonth(), 1);

console.log('📅 Buscando dados de:', primeiroDiaMes.toISOString().split('T')[0], 'até hoje');

// Usar cache para dados de volume mensal
const dadosGrafico = await CacheManager.getVolumeData(userData.farm_id, 'month');

if (!dadosGrafico) {
    console.error('❌ Erro ao buscar dados do gráfico mensal');
    return;
}

console.log('📊 Dados mensais encontrados:', dadosGrafico?.length || 0, 'registros');

// Process monthly data
const dadosPorDia = {};

if (dadosGrafico && dadosGrafico.length > 0) {
    dadosGrafico.forEach(registro => {
        const data = registro.production_date;
        if (!dadosPorDia[data]) {
            dadosPorDia[data] = 0;
        }
        dadosPorDia[data] += parseFloat(registro.volume_liters || 0);
    });
}

// Create array with all days of the month
const diasDoMes = [];
const volumesDoMes = [];

for (let dia = 1; dia <= hoje.getDate(); dia++) {
    const data = new Date(hoje.getFullYear(), hoje.getMonth(), dia);
    const dataStr = data.toISOString().split('T')[0];

    diasDoMes.push(dia.toString());
    volumesDoMes.push(dadosPorDia[dataStr] || 0);
}

console.log('📊 Dados processados:', { dias: diasDoMes.length, volumes: volumesDoMes });

// Destroy previous chart if exists
if (window.monthlyProductionChartInstance) {
    window.monthlyProductionChartInstance.destroy();
}

const ctx = document.getElementById('monthlyProductionChart');
if (ctx) {
    window.monthlyProductionChartInstance = new Chart(ctx, {
        type: 'line',
        data: {
            labels: diasDoMes,
            datasets: [{
                label: 'Volume (L)',
                data: volumesDoMes,
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointHoverRadius: 6,
                pointBackgroundColor: '#10b981',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#ffffff',
                    bodyColor: '#ffffff',
                    borderColor: '#10b981',
                    borderWidth: 1,
                    callbacks: {
                        label: function(context) {
                            return `Volume: ${context.parsed.y.toFixed(1)}L`;
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        color: '#6b7280',
                        font: {
                            size: 12
                        }
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(107, 114, 128, 0.1)'
                    },
                    ticks: {
                        color: '#6b7280',
                        font: {
                            size: 12
                        },
                        callback: function(value) {
                            return value.toFixed(0) + 'L';
                        }
                    }
                }
            },
            animation: {
                duration: 1000,
                easing: 'easeInOutQuart'
            }
        }
    });

    console.log('✅ Gráfico mensal criado com sucesso!');
} else {
    console.error('❌ Elemento monthlyProductionChart não encontrado');
}

} catch (error) {
console.error('❌ Erro ao carregar gráfico mensal:', error);
}
}

// Helper function to get time ago text
function getTimeAgo(minutesAgo) {
if (minutesAgo < 60) {
return `${minutesAgo}min atrás`;
} else if (minutesAgo < 1440) {
const hours = Math.floor(minutesAgo / 60);
return `${hours}h atrás`;
} else {
const days = Math.floor(minutesAgo / 1440);
return `${days}d atrás`;
}
}

// Helper function to get time ago from date
function getTimeAgoFromDate(date) {
const now = new Date();
const diffMs = now - date;
const diffMinutes = Math.floor(diffMs / (1000 * 60));

if (diffMinutes < 60) {
return `${diffMinutes}min atrás`;
} else if (diffMinutes < 1440) {
const hours = Math.floor(diffMinutes / 60);
return `${hours}h atrás`;
} else {
const days = Math.floor(diffMinutes / 1440);
return `${days}d atrás`;
}
}

// Function to display users list
/**
* Exibe a lista de usuários na interface
* Renderiza cards com informações e ações para cada usuário
*/
function displayUsersList(users) {
const usersList = document.getElementById('usersList');

if (!users || users.length === 0) {
usersList.innerHTML = `
    <div class="text-center py-12">
        <div class="w-20 h-20 bg-gray-100    rounded-full flex items-center justify-center mx-auto mb-6">
            <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
            </svg>
        </div>
        <h3 class="text-lg font-semibold text-gray-900    mb-2">Nenhum Usuário Cadastrado</h3>
        <p class="text-gray-600    mb-4">Adicione usuários para gerenciar sua equipe</p>
        <button onclick="addUser()" class="px-6 py-3 gradient-forest text-white font-semibold rounded-xl hover:shadow-lg transition-all">
            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Adicionar Primeiro Usuário
        </button>
    </div>
`;
return;
}

const usersHtml = users.map(user => {

// Verificar se é conta secundária (mesmo email do gerente atual, mas role diferente)
// Obter email do usuário atual do Supabase se não estiver no sessionStorage
let currentUserEmail = sessionStorage.getItem('userEmail');
if (!currentUserEmail) {
    // Tentar obter do localStorage ou sessionStorage como userData
    const userData = localStorage.getItem('userData') || sessionStorage.getItem('userData');
    if (userData) {
        try {
            const parsedUserData = JSON.parse(userData);
            currentUserEmail = parsedUserData.email;
            // Armazenar no sessionStorage para uso futuro
            sessionStorage.setItem('userEmail', currentUserEmail);
        } catch (e) {
        }
    }
}

const isSecondaryAccount = user.email === currentUserEmail && user.role !== 'gerente';

const roleText = {
    'gerente': 'Gerente',
    'funcionario': 'Funcionário',
    'veterinario': 'Veterinário',
    'proprietario': 'Proprietário'
}[user.role] || user.role;

const roleColor = {
    'gerente': 'bg-blue-100 text-blue-800',
    'funcionario': 'bg-green-100 text-green-800',
    'veterinario': 'bg-purple-100 text-purple-800',
    'proprietario': 'bg-yellow-100 text-yellow-800'
}[user.role] || 'bg-gray-100 text-gray-800';

// Definir cores inline para garantir que sejam aplicadas
const roleColorInline = {
    'gerente': 'background-color: #dbeafe !important; color: #1e40af !important;',
    'funcionario': 'background-color: #dcfce7 !important; color: #166534 !important;',
    'veterinario': 'background-color: #f3e8ff !important; color: #7c3aed !important;',
    'proprietario': 'background-color: #fef3c7 !important; color: #d97706 !important;'
}[user.role] || 'background-color: #f1f5f9 !important; color: #64748b !important;';

const statusColor = user.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
const statusText = user.is_active ? 'Ativo' : 'Inativo';

const showPhoto = user.profile_photo_url && user.profile_photo_url.trim() !== '';

return `
    <div class="user-card">
        <div class="user-card-header">
            <div class="user-info">
                <div class="user-avatar relative">
                    ${showPhoto ? 
                        `<img id="user-photo-${user.id}" src="${user.profile_photo_url}?t=${Date.now()}" alt="Foto de ${user.name}" class="w-full h-full object-cover rounded-full" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';" onload="this.nextElementSibling.style.display='none';">
                        <div id="user-icon-${user.id}" class="w-full h-full bg-gradient-to-br from-forest-500 to-forest-600 flex items-center justify-center absolute inset-0 rounded-full" style="display: flex;">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>` :
                        `<div id="user-icon-${user.id}" class="w-full h-full bg-gradient-to-br from-forest-500 to-forest-600 flex items-center justify-center rounded-full">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>`
                    }
                </div>
                <div class="user-details">
                    <div class="user-name">
                        ${user.name}
                        ${isSecondaryAccount ? '<span class="ml-2 text-orange-600"><svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg></span>' : ''}
                    </div>
                    <div class="user-email">${user.email}</div>
                    <div class="user-phone">${user.whatsapp || 'WhatsApp não informado'}</div>
                </div>
            </div>
            <div class="user-status">
                <span class="status-badge role" style="${roleColorInline}">${roleText}</span>
                <span class="status-badge active ${statusColor}">${statusText}</span>
                ${isSecondaryAccount ? '<span class="status-badge secondary" style="background-color: #fed7aa !important; color: #ea580c !important;"><svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>Conta Secundária</span>' : ''}
            </div>
        </div>
        <div class="user-card-footer">
            <div class="user-created">Criado em: ${new Date(user.created_at).toLocaleDateString('pt-BR')}</div>
            <div class="user-actions">
                <button onclick="editUser('${user.id}');" class="action-button edit" title="Editar usuário">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                </button>
                ${user.role !== 'gerente' ? `
                <button onclick="toggleUserAccess('${user.id}', '${user.is_active}');" class="action-button permissions ${!user.is_active ? 'blocked' : ''}" title="${user.is_active ? 'Bloquear acesso' : 'Desbloquear acesso'}" data-user-id="${user.id}" data-current-status="${user.is_active}">
                    ${user.is_active ? 
                        '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"></path></svg>' :
                        '<svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 11V7a4 4 0 018 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"></path></svg>'
                    }
                </button>
                ` : ''}
                ${user.role !== 'gerente' ? `
                <button onclick="deleteUser('${user.id}', '${user.name}');" class="action-button delete" title="Excluir usuário">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                </button>
                ` : ''}
            </div>
        </div>
    </div>
`;
}).join('');

usersList.innerHTML = usersHtml;
}

// Function to edit user
async function editUser(userId) {
try {
// // const supabase = await getSupabaseClient(); // REMOVIDO // REMOVIDO - usando MySQL
// Buscar dados do funcionário pelo ID selecionado
const { data: user, error } = await supabase
    .from('users')
    .select('*')
    .eq('id', userId)
    .single();
if (error) throw error;
// Sempre criar uma cópia do objeto
const funcionario = { ...user };
// Preencher campos do modal de edição
document.getElementById('editUserId').value = funcionario.id;
document.getElementById('editUserName').value = funcionario.name;
document.getElementById('editUserEmail').value = funcionario.email;
document.getElementById('editUserWhatsapp').value = funcionario.whatsapp || '';
document.getElementById('editUserRole').value = funcionario.role;
// Atualizar preview da foto baseado no role
const editPreview = document.getElementById('editProfilePreview');
const editPlaceholder = document.getElementById('editProfilePlaceholder');
const photoSection = document.getElementById('editPhotoSection');

if (photoSection) {
    if (funcionario.role === 'funcionario') {
        photoSection.classList.remove('hidden');
        
        if (funcionario.profile_photo_url && editPreview && editPlaceholder) {
            const uniqueTimestamp = Date.now() + '_' + funcionario.id + '_' + Math.random().toString(36).substr(2, 9);
            editPreview.src = funcionario.profile_photo_url + '?t=' + uniqueTimestamp;
            editPreview.classList.remove('hidden');
            editPlaceholder.classList.add('hidden');
        } else if (editPreview && editPlaceholder) {
            editPreview.classList.add('hidden');
            editPlaceholder.classList.remove('hidden');
            editPreview.src = '';
        }
    } else {
        photoSection.classList.add('hidden');
    }
}
openEditUserModal();
} catch (error) {
console.error('Error loading user data:', error);
showNotification('Erro ao carregar dados do usuário', 'error');
}
}

// Function to toggle user access
async function toggleUserAccess(userId, currentStatus) {
try {
// // const supabase = await getSupabaseClient(); // REMOVIDO // REMOVIDO - usando MySQL

// Validate inputs
if (!userId) {
    throw new Error('User ID is required');
}

const newStatus = !currentStatus;
const action = newStatus ? 'desbloqueado' : 'bloqueado';

// First, let's check if the user exists and get current data
const { data: currentUser, error: fetchError } = await supabase
    .from('users')
    .select('*')
    .eq('id', userId)
    .single();
    
if (fetchError) {
    throw new Error('Usuário não encontrado');
}

// Now update the user status
const { data, error } = await supabase
    .from('users')
    .update({ is_active: newStatus })
    .eq('id', userId)
    .select();
    
if (error) {
    throw error;
}

showNotification(`Acesso do usuário ${action} com sucesso!`, 'success');

// Reload users list with a small delay to ensure the update is processed
setTimeout(async () => {
    await loadUsersData();
}, 500);

} catch (error) {
showNotification('Erro ao alterar acesso do usuário: ' + (error.message || error), 'error');
}
}

// Test function for debugging user blocking
async function testUserBlocking() {
try {


// // const supabase = await getSupabaseClient(); // REMOVIDO // REMOVIDO - usando MySQL
// Get current user data
const { data: { user } } = await supabase.auth.getUser();
if (!user) {
    console.error('No authenticated user');
    return;
}



// Get farm users
const { data: usersData, error } = await supabase
    .from('users')
    .select('*')
    .eq('id', user.id)
    .single();
    
if (error) {
    console.error('Error fetching current user data:', error);
    return;
}



// Get all users from the same farm
const { data: farmUsers, error: farmUsersError } = await supabase
    .from('users')
    .select('*')
    .eq('farm_id', usersData.farm_id);
    
if (farmUsersError) {
    console.error('Error fetching farm users:', farmUsersError);
    return;
}



// Test blocking the first non-manager user
const testUser = farmUsers.find(u => u.role !== 'gerente' && u.id !== usersData.id);

if (testUser) {

    await toggleUserAccess(testUser.id, testUser.is_active);
} else {

}

} catch (error) {
console.error('Test error:', error);
}
}

// Make test function available globally
window.testUserBlocking = testUserBlocking;

// Variável para armazenar dados do usuário a ser excluído
let userToDelete = null;

// Function to delete user com modal estilizado
function deleteUser(userId, userName) {
// Verificar se os parâmetros são válidos
if (!userId || !userName) {
console.error('deleteUser: Parâmetros inválidos', { userId, userName });
return;
}

// Armazenar dados do usuário para exclusão
userToDelete = { id: userId, name: userName };

// Mostrar modal de confirmação
showDeleteConfirmationModal(userName);
}

// Função para executar a exclusão
async function executeDeleteUser(userId, userName) {
try {
// // const supabase = await getSupabaseClient(); // REMOVIDO // REMOVIDO - usando MySQL

// Primeiro, buscar dados completos do usuário antes de excluir
const { data: userData, error: fetchError } = await supabase
    .from('users')
    .select('*')
    .eq('id', userId)
    .single();
    
if (fetchError) {
    showNotification('Erro ao buscar dados do usuário: ' + fetchError.message, 'error');
    return;
}

// Armazenar dados completos para possível restauração
userToDelete = {
    id: userData.id,
    name: userData.name,
    email: userData.email,
    whatsapp: userData.whatsapp,
    role: userData.role,
    farm_id: userData.farm_id,
    profile_photo_url: userData.profile_photo_url,
    is_active: userData.is_active,
    created_at: userData.created_at
};

// Agora excluir o usuário
const { data, error } = await supabase
    .from('users')
    .delete()
    .eq('id', userId);
    
if (error) {
    showNotification('Erro ao excluir usuário: ' + error.message, 'error');
    return;
}

showNotification(`Usuário "${userName}" excluído com sucesso!`, 'success');
await loadUsersData(); // Reload users list

} catch (error) {
showNotification('Erro ao excluir usuário: ' + error.message, 'error');
}
}

// Modal de confirmação de exclusão
function showDeleteConfirmationModal(userName) {
// Criar modal dinamicamente
const modalHTML = `
<div id="deleteConfirmationModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl p-8 max-w-md w-full mx-4 transform transition-all duration-300 scale-95 opacity-0" id="deleteModalContent">
        <div class="text-center">
            <!-- Ícone de aviso -->
            <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
            </div>
            
            <!-- Título -->
            <h3 class="text-xl font-bold text-gray-900 mb-4">Confirmar Exclusão</h3>
            
            <!-- Mensagem -->
            <p class="text-gray-600 mb-6">
                Tem certeza que deseja excluir o usuário <strong>"${userName}"</strong>?
            </p>
            
            <!-- Botões -->
            <div class="flex space-x-3">
                <button onclick="cancelDelete()" class="flex-1 px-4 py-3 border border-gray-300 text-gray-700 font-semibold rounded-xl hover:bg-gray-50 transition-all">
                    Cancelar
                </button>
                <button onclick="confirmDelete()" class="flex-1 px-4 py-3 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-xl transition-all">
                    Excluir
                </button>
            </div>
        </div>
    </div>
</div>
`;

// Adicionar modal ao DOM
document.body.insertAdjacentHTML('beforeend', modalHTML);

// Animar entrada do modal
setTimeout(() => {
const modalContent = document.getElementById('deleteModalContent');
modalContent.classList.remove('scale-95', 'opacity-0');
modalContent.classList.add('scale-100', 'opacity-100');
}, 10);
}

// Função para iniciar timer de desfazer
function startUndoTimer() {
let timeLeft = 3;
const timerElement = document.getElementById('undoTimer');

const timer = setInterval(() => {
timeLeft--;
if (timerElement) {
    timerElement.textContent = timeLeft;
}

if (timeLeft <= 0) {
    clearInterval(timer);
    closeUndoModal();
}
}, 1000);

// Armazenar timer para poder cancelar
window.undoTimer = timer;
}

// Função para desfazer exclusão
async function undoDelete() {
// Limpar timer
if (window.undoTimer) {
clearInterval(window.undoTimer);
window.undoTimer = null;
}

if (userToDelete) {
try {
    // // const supabase = await getSupabaseClient(); // REMOVIDO // REMOVIDO - usando MySQL
    
    // Restaurar usuário usando RPC
    const { data: result, error } = await supabase.rpc('restore_deleted_user', {
        p_user_id: userToDelete.id,
        p_name: userToDelete.name,
        p_email: userToDelete.email,
        p_whatsapp: userToDelete.whatsapp,
        p_role: userToDelete.role,
        p_farm_id: userToDelete.farm_id,
        p_profile_photo_url: userToDelete.profile_photo_url
    });
    
    if (error) {
        throw error;
    }
    
    if (result.success) {
        showNotification(`Usuário "${userToDelete.name}" restaurado com sucesso!`, 'success');
        
        // Recarregar lista de usuários
        setTimeout(() => {
            loadUsersData();
        }, 500);
    } else {
        throw new Error(result.error || 'Falha ao restaurar usuário');
    }
    
} catch (error) {
    console.error('Erro ao desfazer exclusão:', error);
    showNotification('Erro ao desfazer exclusão: ' + error.message, 'error');
}
}

// Fechar modal
closeUndoModal();

// Limpar dados do usuário
userToDelete = null;
}

// Função para fechar modal de desfazer
function closeUndoModal() {
const modal = document.getElementById('undoModal');
if (modal) {
const modalContent = document.getElementById('undoModalContent');
modalContent.classList.add('scale-95', 'opacity-0');

setTimeout(() => {
    modal.remove();
}, 300);
}

// Limpar dados do usuário
userToDelete = null;
}

// Função para cancelar exclusão
function cancelDelete() {
// Fechar modal
closeDeleteConfirmationModal();

// Limpar dados do usuário
userToDelete = null;
}

// Função para confirmar exclusão
function confirmDelete() {
// Executar exclusão imediatamente
if (userToDelete) {
executeDeleteUser(userToDelete.id, userToDelete.name);
}

// Fechar modal de confirmação
closeDeleteConfirmationModal();

// Mostrar modal de desfazer com timer
showUndoModal();
}

// Modal para desfazer exclusão
function showUndoModal() {
const modalHTML = `
<div id="undoModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl p-8 max-w-md w-full mx-4 transform transition-all duration-300 scale-95 opacity-0" id="undoModalContent">
        <div class="text-center">
            <!-- Ícone de sucesso -->
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            
            <!-- Título -->
            <h3 class="text-xl font-bold text-gray-900 mb-4">Usuário Excluído</h3>
            
            <!-- Mensagem -->
            <p class="text-gray-600 mb-6">
                O usuário <strong>"${userToDelete?.name}"</strong> foi excluído com sucesso!
            </p>
            
            <!-- Timer para desfazer -->
            <div class="mb-6">
                <div class="text-sm text-gray-500 mb-2">Tempo para desfazer:</div>
                <div class="flex items-center justify-center space-x-2">
                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                        <span id="undoTimer" class="text-blue-600 font-bold text-lg">3</span>
                    </div>
                    <span class="text-gray-500">segundos</span>
                </div>
            </div>
            
            <!-- Botão para desfazer -->
            <button onclick="undoDelete()" class="w-full px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition-all">
                Desfazer Exclusão
            </button>
        </div>
    </div>
</div>
`;

// Adicionar modal ao DOM
document.body.insertAdjacentHTML('beforeend', modalHTML);

// Animar entrada do modal
setTimeout(() => {
const modalContent = document.getElementById('undoModalContent');
modalContent.classList.remove('scale-95', 'opacity-0');
modalContent.classList.add('scale-100', 'opacity-100');
}, 10);

// Iniciar timer de 3 segundos para desfazer
startUndoTimer();
}

// Função para fechar modal de confirmação
function closeDeleteConfirmationModal() {
const modal = document.getElementById('deleteConfirmationModal');
if (modal) {
const modalContent = document.getElementById('deleteModalContent');
modalContent.classList.add('scale-95', 'opacity-0');

setTimeout(() => {
    modal.remove();
}, 300);
}
}

// Adicionar listener para fechar modal com ESC
document.addEventListener('keydown', function(event) {
if (event.key === 'Escape') {
cancelDelete();
}
});
// Function to handle edit user form submission
async function handleEditUser(e) {
e.preventDefault();

const formData = new FormData(e.target);
const userId = formData.get('id'); // Corrigido para 'id' conforme o input hidden
const name = formData.get('name');
const whatsapp = formData.get('whatsapp');
const role = formData.get('role');
const password = formData.get('password');

try {
// Preparar dados para atualização
const updateData = {
    name: name,
    whatsapp: whatsapp || null,
    role: role
};

// Nota: Senhas são gerenciadas pelo Supabase Auth, não pela tabela users
// Se uma nova senha foi fornecida, ela deve ser atualizada via supabase.auth.updateUser
if (password && password.trim() !== '') {
    // TODO: Implementar atualização de senha via Supabase Auth se necessário

}

// Handle profile photo upload if provided and role is funcionario
if (role === 'funcionario') {
    const profilePhotoFile = formData.get('profilePhoto');
    if (profilePhotoFile && profilePhotoFile.size > 0) {
        try {
            const profilePhotoUrl = await uploadProfilePhoto(profilePhotoFile, userId);
            updateData.profile_photo_url = profilePhotoUrl;
        } catch (photoError) {
            console.error('Error uploading profile photo:', photoError);
            // Don't show error notification for photo upload - user update is more important
            // showNotification('Erro ao fazer upload da foto de perfil, mas outros dados serão atualizados', 'warning');
        }
    }
} else {
    updateData.profile_photo_url = null;
}

// // const supabase = await getSupabaseClient(); // REMOVIDO // REMOVIDO - usando MySQL
const { error } = await supabase
    .from('users')
    .update(updateData)
    .eq('id', userId);
    
if (error) throw error;

showNotification('Usuário atualizado com sucesso!', 'success');
closeEditUserModal();

// Update users list without reloading photos to prevent conflicts
setTimeout(async () => {
    await refreshUsersListOnly();
}, 500);

// Atualizar foto na lista se foi alterada
if (updateData.profile_photo_url) {
    setTimeout(async () => {
        await updateUserPhotoInList(userId, updateData.profile_photo_url);
    }, 600);
}

} catch (error) {
console.error('Error updating user:', error);
showNotification('Erro ao atualizar usuário', 'error');
}
}

// Function to open edit user modal
function openEditUserModal() {
document.getElementById('editUserModal').classList.add('show');
}

// Function to close edit user modal
function closeEditUserModal() {
document.getElementById('editUserModal').classList.remove('show');
document.getElementById('editUserForm').reset();
}

// Função para preview de foto no add user
function previewProfilePhoto(input) {

let file = null;

// Verificar se é um input com files ou um arquivo direto
if (input.files && input.files[0]) {
file = input.files[0];
} else if (input instanceof File) {
file = input;
} else {
return;
}

// Validar tamanho do arquivo (5MB máximo)
if (file.size > 5 * 1024 * 1024) {
showNotification('A foto deve ter no máximo 5MB', 'error');
if (input.files) input.value = '';
return;
}

// Validar tipo do arquivo
if (!file.type.startsWith('image/')) {
showNotification('Por favor, selecione apenas arquivos de imagem', 'error');
if (input.files) input.value = '';
return;
}

const reader = new FileReader();
reader.onload = function(e) {
const preview = document.getElementById('profilePreview');
const placeholder = document.getElementById('profilePlaceholder');


if (preview && placeholder) {
    preview.src = e.target.result;
    preview.style.display = 'block';
    preview.classList.remove('hidden');
    placeholder.style.display = 'none';
    placeholder.classList.add('hidden');
} else {
    console.error('❌ Elementos de preview não encontrados');
}
};
reader.readAsDataURL(file);
}

// Função para preview de foto no edit user
function previewEditProfilePhoto(input) {
let file = null;

// Verificar se é um input com files ou um arquivo direto
if (input.files && input.files[0]) {
file = input.files[0];
} else if (input instanceof File) {
file = input;
} else {
return;
}

// Validar tamanho do arquivo (5MB máximo)
if (file.size > 5 * 1024 * 1024) {
showNotification('A foto deve ter no máximo 5MB', 'error');
if (input.files) input.value = '';
return;
}

// Validar tipo do arquivo
if (!file.type.startsWith('image/')) {
showNotification('Por favor, selecione apenas arquivos de imagem', 'error');
if (input.files) input.value = '';
return;
}

const reader = new FileReader();
reader.onload = function(e) {
const preview = document.getElementById('editProfilePreview');
const placeholder = document.getElementById('editProfilePlaceholder');

if (preview && placeholder) {
    preview.src = e.target.result;
    preview.style.display = 'block';
    preview.classList.remove('hidden');
    placeholder.style.display = 'none';
    placeholder.classList.add('hidden');
}
};
reader.readAsDataURL(file);
}



// Variáveis globais para câmera
let currentPhotoMode = '';





// Funções da Câmera - REFORMULADAS
let cameraStream = null;
let isCameraOpen = false;

async function openCamera() {

// Verificar se já está aberta
if (isCameraOpen) {
return;
}

try {
const modal = document.getElementById('cameraModal');
const video = document.getElementById('cameraVideo');
const processingScreen = document.getElementById('photoProcessingScreen');

if (!modal || !video) {
    console.error('❌ Modal ou vídeo não encontrado');
    return;
}


// Fechar modal de escolha de foto
closePhotoChoiceModal();

// Garantir que a tela de processamento esteja oculta
if (processingScreen) {
    processingScreen.classList.add('hidden');
    processingScreen.style.display = 'none';
    processingScreen.style.visibility = 'hidden';
    processingScreen.style.opacity = '0';
    processingScreen.style.pointerEvents = 'none';
}

// Abrir modal da câmera
modal.classList.remove('hidden');
modal.style.display = 'flex';
isCameraOpen = true;


// Resetar estado da verificação facial
resetFaceVerification();

// Iniciar câmera (funciona no desktop também)
const stream = await navigator.mediaDevices.getUserMedia({ 
    video: { 
        facingMode: 'user',
        width: { ideal: 1280 },
        height: { ideal: 720 }
    } 
});


cameraStream = stream;
video.srcObject = stream;


} catch (error) {
console.error('❌ Erro ao acessar câmera:', error);
alert('Não foi possível acessar a câmera. Verifique as permissões.');
closeCamera();
}
}

function closeCamera() {

const modal = document.getElementById('cameraModal');

if (!modal) {
console.error('❌ Modal não encontrado');
return;
}

// Parar stream da câmera
if (cameraStream) {
cameraStream.getTracks().forEach(track => track.stop());
cameraStream = null;
}

// Fechar modal
modal.classList.add('hidden');
modal.style.display = 'none';
isCameraOpen = false;

// Limpar currentPhotoMode apenas agora
currentPhotoMode = '';

}

async function switchCamera() {

if (!cameraStream) {
console.error('❌ Nenhum stream ativo');
return;
}

try {
const video = document.getElementById('cameraVideo');
const currentFacingMode = cameraStream.getVideoTracks()[0]?.getSettings().facingMode;
const newFacingMode = currentFacingMode === 'user' ? 'environment' : 'user';


// Parar stream atual
cameraStream.getTracks().forEach(track => track.stop());

// Iniciar nova câmera
const newStream = await navigator.mediaDevices.getUserMedia({ 
    video: { 
        facingMode: newFacingMode,
        width: { ideal: 1280 },
        height: { ideal: 720 }
    } 
});

cameraStream = newStream;
video.srcObject = newStream;


} catch (error) {
console.error('❌ Erro ao trocar câmera:', error);
}
}

// Função para resetar verificação facial
function resetFaceVerification() {
const focusText = document.getElementById('focusText');
const focusTimer = document.getElementById('focusTimer');
const focusIndicator = document.getElementById('focusIndicator');
const captureBtn = document.getElementById('captureBtn');

if (focusText) focusText.textContent = 'Posicione o rosto no centro';
if (focusTimer) focusTimer.classList.add('hidden');
if (focusIndicator) {
focusIndicator.classList.add('opacity-0');
focusIndicator.classList.remove('focus-success');
}
if (captureBtn) {
captureBtn.disabled = false;
captureBtn.style.opacity = '1';
}
}

// Função para iniciar verificação facial
function startFaceVerification() {

const focusText = document.getElementById('focusText');
const focusTimer = document.getElementById('focusTimer');
const timerCount = document.getElementById('timerCount');
const focusIndicator = document.getElementById('focusIndicator');
const captureBtn = document.getElementById('captureBtn');

if (!focusText || !focusTimer || !timerCount || !focusIndicator || !captureBtn) {
console.error('❌ Elementos de foco não encontrados');
return;
}

// Desabilitar botão durante verificação
captureBtn.disabled = true;
captureBtn.style.opacity = '0.5';

// Mostrar timer
focusText.textContent = 'Mantenha o rosto no centro';
focusTimer.classList.remove('hidden');

// Timer de 3 segundos
let countdown = 3;
timerCount.textContent = countdown;

const timer = setInterval(() => {
countdown--;
timerCount.textContent = countdown;

if (countdown <= 0) {
    clearInterval(timer);
    
    // Mostrar indicador de foco
    focusIndicator.classList.remove('opacity-0');
    focusIndicator.classList.add('focus-success');
    
    // Capturar foto após 0.5s
    setTimeout(() => {
        capturePhoto();
    }, 500);
}
}, 1000);
}

function capturePhoto() {

const video = document.getElementById('cameraVideo');
const canvas = document.getElementById('cameraCanvas');

if (!video || !canvas) {
console.error('❌ Vídeo ou canvas não encontrado');
return;
}

const context = canvas.getContext('2d');
canvas.width = video.videoWidth || 640;
canvas.height = video.videoHeight || 480;

try {
// Desenhar frame do vídeo no canvas
context.drawImage(video, 0, 0, canvas.width, canvas.height);

// Converter para blob e processar
canvas.toBlob((blob) => {
    if (!blob) {
        console.error('❌ Erro ao criar blob');
        return;
    }
    
    
    // Criar URL da imagem
    const imageUrl = URL.createObjectURL(blob);
    
    // Mostrar preview diretamente
    if (currentPhotoMode === 'add') {
        const preview = document.getElementById('profilePreview');
        const placeholder = document.getElementById('profilePlaceholder');
        
        
        if (preview && placeholder) {
            preview.src = imageUrl;
            preview.style.display = 'block';
            preview.classList.remove('hidden');
            placeholder.style.display = 'none';
            placeholder.classList.add('hidden');
} else {
            console.error('❌ Elementos de preview não encontrados para novo usuário');
        }
    } else if (currentPhotoMode === 'edit') {
        const preview = document.getElementById('editProfilePreview');
        const placeholder = document.getElementById('editProfilePlaceholder');
        
        
        if (preview && placeholder) {
            preview.src = imageUrl;
            preview.style.display = 'block';
            preview.classList.remove('hidden');
            placeholder.style.display = 'none';
            placeholder.classList.add('hidden');
        } else {
            console.error('❌ Elementos de preview não encontrados para edição');
        }
    } else {
        console.error('❌ currentPhotoMode inválido:', currentPhotoMode);
    }
    
    // Fechar câmera
    closeCamera();
    
}, 'image/jpeg', 0.8);

} catch (error) {
console.error('❌ Erro ao capturar foto:', error);
closeCamera();
}
}

// Função para abrir galeria
function openGallery() {
const inputId = currentPhotoMode === 'add' ? 'profilePhotoInput' : 'editProfilePhoto';
const input = document.getElementById(inputId);

if (input) {
input.removeAttribute('capture');
input.click();
}

closePhotoChoiceModal();
}

// Funções para adicionar foto
function addPhotoToNewUser() {
openPhotoChoiceModal('add');
}

function addPhotoToEditUser() {
openPhotoChoiceModal('edit');
}

// Funções do novo modal de foto
function openPhotoChoiceModal(mode) {
currentPhotoMode = mode;

const modal = document.getElementById('photoChoiceModal');

if (modal) {

modal.classList.remove('hidden');
modal.classList.add('flex');
modal.style.display = 'flex';
modal.style.visibility = 'visible';
modal.style.opacity = '1';
modal.style.pointerEvents = 'auto';

// Verificar se realmente está visível
setTimeout(() => {
    const rect = modal.getBoundingClientRect();
}, 100);
} else {
console.error('❌ Modal não encontrado');
}
}

function closePhotoChoiceModal() {
const modal = document.getElementById('photoChoiceModal');
if (modal) {
modal.classList.add('hidden');
modal.classList.remove('flex');
modal.style.display = 'none';
modal.style.visibility = 'hidden';
modal.style.opacity = '0';
modal.style.pointerEvents = 'none';
// NÃO limpar currentPhotoMode aqui, pois precisamos dele na câmera
console.log('✅ Modal fechado, currentPhotoMode mantido:', currentPhotoMode);
}
}

function selectFromGallery() {
const input = document.createElement('input');
input.type = 'file';
input.accept = 'image/*';
input.onchange = function(e) {
const file = e.target.files[0];
if (file) {
    const reader = new FileReader();
    reader.onload = function(e) {
        if (currentPhotoMode === 'add') {
            processPhotoForNewUser(e.target.result);
        } else if (currentPhotoMode === 'edit') {
            processPhotoForEditUser(e.target.result);
        }
        closePhotoChoiceModal();
    };
    reader.readAsDataURL(file);
}
};
input.click();
}

function processPhotoForNewUser(photoData) {
// Simular o input file para o novo usuário
const input = document.getElementById('profilePhotoInput');
if (input) {
// Criar um arquivo a partir da foto
fetch(photoData)
    .then(res => res.blob())
    .then(blob => {
        const file = new File([blob], 'photo.jpg', { type: 'image/jpeg' });
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        input.files = dataTransfer.files;
        
        // Disparar evento change
        const event = new Event('change', { bubbles: true });
        input.dispatchEvent(event);
    });
}
}

function processPhotoForEditUser(photoData) {
// Simular o input file para edição de usuário
const input = document.getElementById('editProfilePhoto');
if (input) {
// Criar um arquivo a partir da foto
fetch(photoData)
    .then(res => res.blob())
    .then(blob => {
        const file = new File([blob], 'photo.jpg', { type: 'image/jpeg' });
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        input.files = dataTransfer.files;
        
        // Disparar evento change
        const event = new Event('change', { bubbles: true });
        input.dispatchEvent(event);
    });
}
}

function takePhoto() {
console.log('📸 takePhoto chamado, currentPhotoMode:', currentPhotoMode);
closePhotoChoiceModal();
openCamera();
}

// Setup event listeners
function setupEventListeners() {
console.log('🔧 Configurando event listeners...');

// Função para limpar listeners existentes
function clearExistingListeners(element, eventType, handler) {
if (element) {
    element.removeEventListener(eventType, handler);
}
}
// Listener para role no add user
const userRoleSelect = document.getElementById('userRole');
if (userRoleSelect) {
userRoleSelect.addEventListener('change', function() {
    const addPhotoSection = document.getElementById('addPhotoSection');
    console.log('🔍 Role selecionado:', this.value);
    console.log('🔍 Seção de foto:', addPhotoSection ? 'encontrada' : 'não encontrada');
    
    if (this.value === 'funcionario') {
        addPhotoSection.classList.remove('hidden');
        console.log('✅ Seção de foto mostrada');
    } else {
        addPhotoSection.classList.add('hidden');
        console.log('✅ Seção de foto ocultada');
    }
});
}

// Listener para role no edit user
const editUserRoleSelect = document.getElementById('editUserRole');
if (editUserRoleSelect) {
editUserRoleSelect.addEventListener('change', function() {
    const editPhotoSection = document.getElementById('editPhotoSection');
    if (this.value === 'funcionario') {
        editPhotoSection.classList.remove('hidden');
    } else {
        editPhotoSection.classList.add('hidden');
    }
});
}

// Tab switching for both desktop and mobile
const navItems = document.querySelectorAll('.nav-item, .mobile-nav-item');

navItems.forEach(item => {
item.addEventListener('click', function(e) {
    e.preventDefault();
    const targetTab = this.getAttribute('data-tab');
    
    if (targetTab) {
        // Remove active class from all nav items
        navItems.forEach(nav => nav.classList.remove('active'));
        
        // Add active class to clicked item
        this.classList.add('active');
        
        // Hide all tab contents
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.add('hidden');
        });
        
        // Show target tab content
        const targetContent = document.getElementById(targetTab + '-tab');
        if (targetContent) {
            targetContent.classList.remove('hidden');
        }
    }
});
});

// Form submissions (password form removed - now uses separate page)

// Add user form submission
const addUserForm = document.getElementById('addUserFormModal');
if (addUserForm) {
console.log('🔍 Formulário encontrado, adicionando listener');
clearExistingListeners(addUserForm, 'submit', handleAddUser);
addUserForm.addEventListener('submit', handleAddUser);
} else {
console.error('❌ Formulário addUserFormModal não encontrado!');
}



// Update profile form submission
const updateProfileForm = document.getElementById('updateProfileForm');
if (updateProfileForm) {
clearExistingListeners(updateProfileForm, 'submit', handleUpdateProfile);
updateProfileForm.addEventListener('submit', handleUpdateProfile);
}

// Edit user form submission
const editUserForm = document.getElementById('editUserForm');
if (editUserForm) {
clearExistingListeners(editUserForm, 'submit', handleEditUser);
editUserForm.addEventListener('submit', handleEditUser);
}

// Email preview update
const userNameInput = document.getElementById('userName');
if (userNameInput) {
userNameInput.addEventListener('input', function() {
    updateEmailPreview(this.value);
});
}
}

// Password change now handled in separate page (alterar-senha.html)

// Global chart instances
let qualityTrendChart = null;
let qualityDistributionChart = null;

// Load volume records table
async function loadVolumeRecords() {
try {
// // const supabase = await getSupabaseClient(); // REMOVIDO // REMOVIDO - usando MySQL

const { data: { user } } = await supabase.auth.getUser();
if (!user) throw new Error('User not authenticated');

const { data: userData, error: userError } = await supabase
    .from('users')
    .select('farm_id')
    .eq('id', user.id)
    .single();

if (userError) {
    throw userError;
}

// Get volume records data - buscar dados mais recentes primeiro
const { data: volumeRecords, error } = await supabase
    .from('volume_records')
    .select(`
        *,
        users(name)
    `)
    .eq('farm_id', userData.farm_id)
    .order('created_at', { ascending: false })
    .limit(20);

if (error) {
}

displayVolumeRecords(volumeRecords || []);

} catch (error) {
console.error('Error loading volume records:', error);
displayVolumeRecords([]);
}
}
// Display volume records in table
function displayVolumeRecords(records) {
const tbody = document.getElementById('volumeRecords');

if (!records || records.length === 0) {
tbody.innerHTML = `
    <tr>
        <td colspan="5" class="px-6 py-12 text-center text-gray-500">
            <div class="flex flex-col items-center">
                <svg class="w-12 h-12 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <p class="text-lg font-medium mb-2">Nenhum registro de volume encontrado</p>
                <p class="text-sm">Adicione registros de produção para monitorar o volume</p>
            </div>
        </td>
    </tr>
`;
return;
}

tbody.innerHTML = records.map(record => {
// Corrigir data - adicionar timezone para evitar problema de 1 dia
const recordDate = new Date(record.production_date + 'T00:00:00').toLocaleDateString('pt-BR');
const recordTime = new Date(record.created_at).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
const volume = record.volume_liters ? `${record.volume_liters.toFixed(1)}L` : '--';
// Obter nome do funcionário via relacionamento
const userName = record.users?.name || record.user_name || 'N/A';
const notes = record.notes || '-';

return `
    <tr class="hover:bg-gray-50">
        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${recordDate} ${recordTime}</td>
        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${volume}</td>
        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${userName}</td>
        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${notes}</td>
        <td class="px-6 py-4 whitespace-nowrap text-sm">
            <button onclick="deleteVolumeRecord('${record.id}');" class="text-red-600 hover:text-red-800 font-medium">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
            </button>
        </td>
    </tr>
`;
}).join('');
}

// Load quality tests table
async function loadQualityTests() {
try {
// // const supabase = await getSupabaseClient(); // REMOVIDO // REMOVIDO - usando MySQL

const { data: { user } } = await supabase.auth.getUser();
if (!user) throw new Error('User not authenticated');

const { data: qualityUserData, error: userError } = await supabase
    .from('users')
    .select('farm_id')
    .eq('id', user.id)
    .single();

if (userError) {
    throw userError;
}

// Get quality tests data
const { data: qualityTests, error } = await supabase
    .from('quality_tests')
    .select('*')
    .eq('farm_id', qualityUserData.farm_id)
    .order('test_date', { ascending: false })
    .limit(20);

if (error) {
}

displayQualityTests(qualityTests || []);

} catch (error) {
console.error('Error loading quality tests:', error);
displayQualityTests([]);
}
}

// Display quality tests in table
function displayQualityTests(tests) {
const tbody = document.getElementById('qualityTests');

if (!tests || tests.length === 0) {
tbody.innerHTML = `
    <tr>
        <td colspan="9" class="px-6 py-12 text-center text-gray-500">
            <div class="flex flex-col items-center">
                <svg class="w-12 h-12 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <p class="text-lg font-medium mb-2">Nenhum teste de qualidade encontrado</p>
                <p class="text-sm">Adicione testes de qualidade para monitorar a qualidade do leite</p>
            </div>
        </td>
    </tr>
`;
return;
}

tbody.innerHTML = tests.map(test => {
const testDate = new Date(test.test_date).toLocaleDateString('pt-BR');
const fatPercentage = test.fat_percentage ? `${test.fat_percentage.toFixed(1)}%` : '--';
const proteinPercentage = test.protein_percentage ? `${test.protein_percentage.toFixed(1)}%` : '--';
                const sccCount = test.scc ? `${Math.round(test.scc / 1000)}k` : '--';
                const tbcCount = test.cbt ? `${Math.round(test.cbt / 1000)}k` : '--';
const laboratory = test.laboratory || '--';

// Determine quality grade based on values
let qualityGrade = 'A';
let gradeColor = 'bg-green-100 text-green-800';

if (test.fat_percentage < 3.0 || test.protein_percentage < 2.9 || 
                    (test.scc && test.scc > 400000) ||
                    (test.cbt && test.cbt > 100000)) {
    qualityGrade = 'C';
    gradeColor = 'bg-red-100 text-red-800';
} else if (test.fat_percentage < 3.5 || test.protein_percentage < 3.2 ||
           (test.scc && test.scc > 200000) ||
           (test.cbt && test.cbt > 50000)) {
    qualityGrade = 'B';
    gradeColor = 'bg-yellow-100 text-yellow-800';
}

return `
    <tr class="hover:bg-gray-50">
        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${testDate}</td>
        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${fatPercentage}</td>
        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${proteinPercentage}</td>
        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${sccCount}</td>
        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${tbcCount}</td>
        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${laboratory}</td>
        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 max-w-xs truncate" title="${test.observations || '--'}">${test.observations || '--'}</td>
        <td class="px-6 py-4 whitespace-nowrap">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${gradeColor}">
                Nota ${qualityGrade}
            </span>
        </td>
        <td class="px-6 py-4 whitespace-nowrap text-sm">
            <button onclick="deleteQualityTest('${test.id}');" class="text-red-600 hover:text-red-800 font-medium">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
            </button>
        </td>
    </tr>
`;
}).join('');
}

// Delete volume record function
async function deleteVolumeRecord(recordId) {
if (!confirm('Tem certeza que deseja excluir este registro de volume?')) {
return;
}

try {
const { data: { user } } = await supabase.auth.getUser();
if (!user) {
    showNotification('Usuário não autenticado', 'error');
    return;
}

// Delete the record
const { error } = await supabase
    .from('volume_records')
    .delete()
    .eq('id', recordId);

if (error) {
    console.error('Error deleting volume record:', error);
    showNotification('Erro ao excluir registro: ' + error.message, 'error');
    return;
}

showNotification('Registro de volume excluído com sucesso!', 'success');

// Reload data
await loadVolumeData();
await loadVolumeRecords();
await loadWeeklyVolumeChart();
await loadDailyVolumeChart();
await loadDashboardWeeklyChart();
await loadWeeklySummaryChart();
await loadMonthlyVolumeChart();
await loadQualityChart();
await loadTemperatureChart();
// Get user's farm_id for recent activities
if (user) {
    const { data: userData } = await supabase
        .from('users')
        .select('farm_id')
        .eq('id', user.id)
        .single();
    
    if (userData?.farm_id) {
        await loadRecentActivities(userData.farm_id);
    }
}

} catch (error) {
console.error('Error deleting volume record:', error);
showNotification('Erro ao excluir registro: ' + error.message, 'error');
}
}

// Delete quality test function
async function deleteQualityTest(testId) {
if (!confirm('Tem certeza que deseja excluir este teste de qualidade?')) {
return;
}

try {
// // const supabase = await getSupabaseClient(); // REMOVIDO // REMOVIDO - usando MySQL
const { data: { user } } = await supabase.auth.getUser();
if (!user) {
    showNotification('Usuário não autenticado', 'error');
    return;
}

// Delete the test
const { error } = await supabase
    .from('quality_tests')
    .delete()
    .eq('id', testId);

if (error) {
    console.error('Error deleting quality test:', error);
    showNotification('Erro ao excluir teste: ' + error.message, 'error');
    return;
}

showNotification('Teste de qualidade excluído com sucesso!', 'success');

// Reload data
await loadQualityData();
await loadQualityTests();

} catch (error) {
console.error('Error deleting quality test:', error);
showNotification('Erro ao excluir teste: ' + error.message, 'error');
}
}

// Load quality chart data
async function loadQualityChart() {
try {
// // const supabase = await getSupabaseClient(); // REMOVIDO // REMOVIDO - usando MySQL
const { data: { user } } = await supabase.auth.getUser();
if (!user) return;

const { data: userProfile } = await supabase
    .from('users')
    .select('farm_id')
    .eq('id', user.id)
    .single();

if (!userProfile?.farm_id) return;

const { data: qualityData, error } = await supabase
    .from('quality_tests')
    .select('*')
    .eq('farm_id', userProfile.farm_id)
    .order('test_date', { ascending: false })
    .limit(7);

if (error) {
    return;
}

if (window.qualityChart && qualityData && qualityData.length > 0) {
    const labels = qualityData.reverse().map(record => 
        new Date(record.test_date).toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' })
    );
    const qualityScores = qualityData.map(record => {
        // Calculate quality score based on fat and protein percentages
        const fatScore = Math.min((record.fat_percentage || 0) / 4 * 100, 100);
        const proteinScore = Math.min((record.protein_percentage || 0) / 3.5 * 100, 100);
        return Math.round((fatScore + proteinScore) / 2);
    });

    window.qualityChart.data.labels = labels;
    window.qualityChart.data.datasets[0].data = qualityScores;
    window.qualityChart.update();
}
} catch (error) {
console.error('Error loading quality chart:', error);
}
}

// Load temperature chart data
async function loadTemperatureChart() {
try {
console.log('🌡️ Iniciando carregamento do gráfico de temperatura...');
// // const supabase = await getSupabaseClient(); // REMOVIDO // REMOVIDO - usando MySQL

const { data: { user } } = await supabase.auth.getUser();
if (!user) {
    console.log('❌ Usuário não autenticado');
    return;
}

const { data: userData } = await supabase
    .from('users')
    .select('farm_id')
    .eq('id', user.id)
    .single();

if (!userData?.farm_id) {
    console.log('❌ Farm ID não encontrado');
    return;
}

console.log('✅ Usuário autenticado, farm_id:', userData.farm_id);



// First, let's check if there are any records with temperature
const { data: allRecords, error: checkError } = await supabase
    .from('volume_records')
    .select('production_date, temperature')
    .eq('farm_id', userData.farm_id);
    


const { data: temperatureData, error } = await supabase
    .from('volume_records')
    .select('production_date, temperature')
    .eq('farm_id', userData.farm_id)
    .not('temperature', 'is', null)
    .order('production_date', { ascending: false })
    .limit(7);

if (error) {
    console.error('❌ Erro ao carregar dados de temperatura:', error);
    return;
}

console.log('📊 Dados de temperatura encontrados:', temperatureData?.length || 0, 'registros');
console.log('📊 Dados brutos:', temperatureData);


if (window.temperatureChart) {
    if (temperatureData && temperatureData.length > 0) {
        // Restore canvas if it was replaced by message
        const chartContainer = document.querySelector('#temperatureChart').parentElement;
        if (chartContainer && !chartContainer.querySelector('canvas')) {
            chartContainer.innerHTML = '<canvas id="temperatureChart"></canvas>';
            // Reinitialize chart
            const temperatureCtx = document.getElementById('temperatureChart');
            if (temperatureCtx) {
                window.temperatureChart = new Chart(temperatureCtx, {
                    type: 'line',
                    data: {
                        labels: [],
                        datasets: [{
                            label: 'Temperatura (°C)',
                            data: [],
                            borderColor: '#f59e0b',
                            backgroundColor: 'rgba(245, 158, 11, 0.1)',
                            borderWidth: 3,
                            pointRadius: 6,
                            pointHoverRadius: 8,
                            pointBackgroundColor: '#f59e0b',
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 2,
                            tension: 0.4,
                            fill: true,
                            showLine: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            intersect: false,
                            mode: 'index'
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                titleColor: 'white',
                                bodyColor: 'white',
                                borderColor: 'rgba(255, 255, 255, 0.1)',
                                borderWidth: 1,
                                callbacks: {
                                    label: function(context) {
                                        return 'Temperatura: ' + context.parsed.y + '°C';
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 10,
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.1)'
                                },
                                ticks: {
                                    callback: function(value) {
                                        return value + '°C';
                                    }
                                }
                            },
                            x: {
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.1)'
                                },
                                ticks: {
                                    maxTicksLimit: 7, // Limitar número de ticks no eixo X
                                    callback: function(value, index, ticks) {
                                        // Garantir que não haja labels duplicados
                                        const label = this.getLabelForValue(value);
                                        return label;
                                    }
                                }
                            }
                        },
                        elements: {
                            line: {
                                tension: 0.4
                            },
                            point: {
                                radius: 6,
                                hoverRadius: 8
                            }
                        },
                        animation: {
                            duration: 1000,
                            easing: 'easeInOutQuart'
                        }
                    }
                });
            }
        }
        
        // Processar dados de temperatura corretamente
        const labels = [];
        const temperatures = [];
        const processedDates = new Set(); // Para evitar datas duplicadas
        
        // Ordenar dados por data (mais antigo primeiro)
        const sortedData = temperatureData.sort((a, b) => 
            new Date(a.production_date) - new Date(b.production_date)
        );
        
        sortedData.forEach(record => {
            const dateStr = new Date(record.production_date).toLocaleDateString('pt-BR', { 
                day: '2-digit', 
                month: '2-digit' 
            });
            
            // Evitar duplicatas
            if (!processedDates.has(dateStr)) {
                labels.push(dateStr);
                temperatures.push(record.temperature || 0);
                processedDates.add(dateStr);
            }
        });
        
        console.log('📊 Dados de temperatura processados:', { labels, temperatures });

        window.temperatureChart.data.labels = labels;
        window.temperatureChart.data.datasets[0].data = temperatures;
        
        // Show success message

    } else {

        // Show message in chart area
        const chartContainer = document.querySelector('#temperatureChart').parentElement;

        if (chartContainer) {
            const totalRecords = allRecords?.length || 0;
            const recordsWithTemp = allRecords?.filter(r => r.temperature !== null).length || 0;
            
            chartContainer.innerHTML = `
                <div class="flex flex-col items-center justify-center h-64 text-center">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Sem Dados de Temperatura</h3>
                    <p class="text-gray-500 text-sm">Nenhum registro com temperatura foi encontrado nos últimos 7 dias.</p>
                    <p class="text-gray-400 text-xs mt-1">
                        Total de registros: ${totalRecords} | Com temperatura: ${recordsWithTemp}
                    </p>
                    <p class="text-gray-400 text-xs mt-1">Os dados aparecerão aqui quando houver registros com temperatura.</p>
                </div>
            `;
        }
        
        // Clear chart when no data
        window.temperatureChart.data.labels = [];
        window.temperatureChart.data.datasets[0].data = [];
    }
    window.temperatureChart.update();
} else {
    console.error('Temperature chart not initialized');
}
} catch (error) {
console.error('Error loading temperature chart:', error);
}
}

// Update quality charts with data
function updateQualityCharts(qualityData) {


// Update trend chart
if (qualityTrendChart) {
try {
    const labels = qualityData.slice(0, 10).reverse().map(record => 
        new Date(record.test_date).toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' })
    );
    const fatData = qualityData.slice(0, 10).reverse().map(record => record.fat_percentage || 0);
    const proteinData = qualityData.slice(0, 10).reverse().map(record => record.protein_percentage || 0);
    

    
    qualityTrendChart.data.labels = labels;
    qualityTrendChart.data.datasets[0].data = fatData;
    qualityTrendChart.data.datasets[1].data = proteinData;
    qualityTrendChart.update();
    

} catch (error) {
    console.error('Error updating trend chart:', error);
}
} else {
}

// Update distribution chart
if (qualityDistributionChart && qualityData.length > 0) {
const avgFat = qualityData.reduce((sum, record) => sum + (record.fat_percentage || 0), 0) / qualityData.length;
const avgProtein = qualityData.reduce((sum, record) => sum + (record.protein_percentage || 0), 0) / qualityData.length;
                const avgSCC = qualityData.reduce((sum, record) => sum + (record.scc || 0), 0) / qualityData.length;

// Classify quality based on standards
const fatQuality = avgFat >= 3.5 ? 'Excelente' : avgFat >= 3.0 ? 'Bom' : 'Regular';
const proteinQuality = avgProtein >= 3.2 ? 'Excelente' : avgProtein >= 2.9 ? 'Bom' : 'Regular';
const sccQuality = avgSCC <= 200000 ? 'Excelente' : avgSCC <= 400000 ? 'Bom' : 'Regular';

const excellent = [fatQuality, proteinQuality, sccQuality].filter(q => q === 'Excelente').length;
const good = [fatQuality, proteinQuality, sccQuality].filter(q => q === 'Bom').length;
const regular = [fatQuality, proteinQuality, sccQuality].filter(q => q === 'Regular').length;

qualityDistributionChart.data.labels = ['Excelente', 'Bom', 'Regular'];
qualityDistributionChart.data.datasets[0].data = [excellent, good, regular];
qualityDistributionChart.update();
}
}

// Load recent activities
async function loadRecentActivities(farmId) {
try {
console.log('🔄 Carregando atividades recentes para fazenda:', farmId);

// Aguardar Supabase estar disponível
if (!window.supabase) {
    await new Promise(resolve => setTimeout(resolve, 1000));
    if (!window.supabase) {
        console.error('❌ Supabase não disponível para atividades recentes');
        return;
    }
}

// // const supabase = await getSupabaseClient(); // REMOVIDO // REMOVIDO - usando MySQL

const { data: activities, error } = await supabase
    .from('volume_records')
    .select(`
        id,
        volume_liters,
        production_date,
        milking_type,
        created_at,
        users!inner(name)
    `)
    .eq('farm_id', farmId)
    .order('created_at', { ascending: false })
    .limit(5);

if (error) {
    console.error('❌ Erro ao carregar atividades recentes:', error);
    return;
}

console.log('📊 Atividades encontradas:', activities?.length || 0);

const activitiesContainer = document.getElementById('recentActivities');

if (!activities || activities.length === 0) {
    console.log('ℹ️ Nenhuma atividade encontrada, mostrando mensagem padrão');
    activitiesContainer.innerHTML = `
        <div class="text-center py-8">
            <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <p class="text-gray-500 text-sm">Nenhuma atividade recente</p>
            <p class="text-gray-400 text-xs">Registros aparecerão aqui</p>
        </div>
    `;
    return;
}

console.log('✅ Renderizando atividades recentes...');

activitiesContainer.innerHTML = activities.map(activity => {
    const timeAgo = getTimeAgoFromDate(new Date(activity.created_at));
    const userName = activity.users?.name || 'Usuário';
    
    return `
        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
            <div class="flex items-center space-x-3">
                <div class="w-8 h-8 bg-forest-500 rounded-full flex items-center justify-center">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-900">${activity.volume_liters}L - ${getMilkingTypeInPortuguese(activity.milking_type)}</p>
                    <p class="text-xs text-gray-500">${timeAgo} • por ${userName}</p>
                </div>
            </div>
            <div class="text-xs text-gray-400">
                ${timeAgo}
            </div>
        </div>
    `;
}).join('');

} catch (error) {
console.error('❌ Erro ao carregar atividades recentes:', error);
}

    console.log('✅ Função loadRecentActivities concluída');
}

// ==================== REAL-TIME UPDATES ====================
let realtimeSubscriptions = [];

// Função para configurar atualizações em tempo real
async function setupRealtimeUpdates() {
try {
console.log('🔌 Configurando atualizações em tempo real...');

// Obter farm_id do usuário atual
const userData = localStorage.getItem('userData') || sessionStorage.getItem('userData');
if (!userData) {
    console.log('❌ Dados de usuário não encontrados para tempo real');
    return;
}

const parsedUserData = JSON.parse(userData);
const farmId = parsedUserData.farm_id;

// Obter cliente Supabase
// // const supabase = await getSupabaseClient(); // REMOVIDO // REMOVIDO - usando MySQL
if (!supabase) {
    console.log('❌ Supabase não disponível para tempo real');
    return;
}

// 1. Escutar mudanças na tabela volume_records (produção)
const volumeSubscription = supabase
    .channel('volume_records_changes')
    .on(
        'postgres_changes',
        {
            event: '*', // INSERT, UPDATE, DELETE
            schema: 'public',
            table: 'volume_records',
            filter: `farm_id=eq.${farmId}`
        },
        async (payload) => {
            console.log('📊 Mudança detectada em volume_records:', payload.eventType);
            
            // Atualizar apenas os componentes necessários
            switch (payload.eventType) {
                case 'INSERT':
                    await handleNewProduction(payload.new);
                    break;
                case 'UPDATE':
                    await handleProductionUpdate(payload.new, payload.old);
                    break;
                case 'DELETE':
                    await handleProductionDelete(payload.old);
                    break;
            }
        }
    )
    .subscribe();

// Armazenar referência da subscription
realtimeSubscriptions = [volumeSubscription];

console.log('✅ Atualizações em tempo real configuradas com sucesso!');

// Mostrar indicador visual
const indicator = document.getElementById('realtimeIndicator');
if (indicator) {
    indicator.classList.remove('hidden');
}

} catch (error) {
console.error('❌ Erro ao configurar atualizações em tempo real:', error.message);
}
}

// Função para limpar todas as subscriptions
function cleanupRealtimeUpdates() {
try {
console.log('🧹 Limpando atualizações em tempo real...');

realtimeSubscriptions.forEach(subscription => {
    if (subscription && subscription.unsubscribe) {
        subscription.unsubscribe();
    }
});

realtimeSubscriptions = [];

// Esconder indicador visual
const indicator = document.getElementById('realtimeIndicator');
if (indicator) {
    indicator.classList.add('hidden');
}

console.log('✅ Atualizações em tempo real limpas');

} catch (error) {
console.error('❌ Erro ao limpar atualizações em tempo real:', error.message);
}
}

// Handlers para mudanças em tempo real
async function handleNewProduction(newProduction) {
try {
console.log('🆕 Nova produção detectada:', newProduction);

// Atualizar volume de hoje
await updateTodayVolume();

// Atualizar atividades recentes
const userData = localStorage.getItem('userData') || sessionStorage.getItem('userData');
if (userData) {
    const parsedUserData = JSON.parse(userData);
    await loadRecentActivities(parsedUserData.farm_id);
}

// Mostrar notificação
showNotification(`Nova produção registrada: ${newProduction.volume_liters}L`, 'success');

// Notificação REAL do dispositivo para registro de produção
if (window.nativeNotifications) {
    window.nativeNotifications.showRealDeviceNotification(
        'Nova Produção Registrada',
        `Volume: ${newProduction.volume_liters}L registrado com sucesso!`,
        'production'
    );
}

} catch (error) {
console.error('❌ Erro ao processar nova produção:', error.message);
}
}

async function handleProductionUpdate(newProduction, oldProduction) {
try {
console.log('🔄 Produção atualizada:', { old: oldProduction, new: newProduction });

// Atualizar volume de hoje
await updateTodayVolume();

// Atualizar atividades recentes
const userData = localStorage.getItem('userData') || sessionStorage.getItem('userData');
if (userData) {
    const parsedUserData = JSON.parse(userData);
    await loadRecentActivities(parsedUserData.farm_id);
}

// Mostrar notificação
showNotification('Produção atualizada com sucesso!', 'info');

} catch (error) {
console.error('❌ Erro ao processar atualização de produção:', error.message);
}
}

async function handleProductionDelete(deletedProduction) {
try {
console.log('🗑️ Produção deletada:', deletedProduction);

// Atualizar volume de hoje
await updateTodayVolume();

// Atualizar atividades recentes
const userData = localStorage.getItem('userData') || sessionStorage.getItem('userData');
if (userData) {
    const parsedUserData = JSON.parse(userData);
    await loadRecentActivities(parsedUserData.farm_id);
}

// Mostrar notificação
showNotification('Produção removida com sucesso!', 'info');

} catch (error) {
console.error('❌ Erro ao processar remoção de produção:', error.message);
}
}

// Função para atualizar volume de hoje
async function updateTodayVolume() {
try {
const userData = localStorage.getItem('userData') || sessionStorage.getItem('userData');
if (!userData) return;

const parsedUserData = JSON.parse(userData);
// // const supabase = await getSupabaseClient(); // REMOVIDO // REMOVIDO - usando MySQL

const { data: volumeData, error: volumeError } = await supabase
    .from('volume_records')
    .select('volume_liters')
    .eq('farm_id', parsedUserData.farm_id)
    .gte('production_date', new Date().toISOString().split('T')[0]);

if (!volumeError && volumeData && volumeData.length > 0) {
    const todayVolume = volumeData.reduce((sum, record) => sum + (record.volume_liters || 0), 0);
    const volumeElement = document.getElementById('todayVolume');
    if (volumeElement) {
        volumeElement.textContent = `${todayVolume} L`;
        
        // Salvar no localStorage
        localStorage.setItem('todayVolume', todayVolume.toString());
        localStorage.setItem('todayVolumeDate', new Date().toISOString().split('T')[0]);
        
        console.log('✅ Volume de hoje atualizado em tempo real:', todayVolume, 'L');
    }
}
} catch (error) {
console.error('❌ Erro ao atualizar volume em tempo real:', error.message);
}
}



// Initialize charts
function initializeCharts() {
// Volume Chart
const volumeCtx = document.getElementById('volumeChart');
if (volumeCtx) {
console.log('✅ Inicializando gráfico volumeChart (Volume Semanal)...');
window.volumeChart = new Chart(volumeCtx, {
    type: 'line',
    data: {
        labels: [],
        datasets: [{
            label: 'Volume (L)',
            data: [],
            borderColor: '#369e36',
            backgroundColor: 'rgba(54, 158, 54, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});
console.log('✅ Gráfico volumeChart (Volume Semanal) inicializado com sucesso');
} else {
console.error('❌ Elemento volumeChart não encontrado no DOM');
}

// Quality Chart
const qualityCtx = document.getElementById('qualityChart');
if (qualityCtx) {
window.qualityChart = new Chart(qualityCtx, {
    type: 'bar',
    data: {
        labels: [],
        datasets: [{
            label: 'Qualidade (%)',
            data: [],
            backgroundColor: '#5bb85b',
            borderRadius: 8
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                max: 100
            }
        }
    }
});
}
// Temperature Chart
const temperatureCtx = document.getElementById('temperatureChart');

if (temperatureCtx) {
window.temperatureChart = new Chart(temperatureCtx, {
    type: 'line',
    data: {
        labels: [],
        datasets: [{
            label: 'Temperatura (°C)',
            data: [],
            borderColor: '#f59e0b',
            backgroundColor: 'rgba(245, 158, 11, 0.1)',
            borderWidth: 3,
            pointRadius: 6,
            pointHoverRadius: 8,
            pointBackgroundColor: '#f59e0b',
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
            tension: 0.4,
            fill: true,
            showLine: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
            intersect: false,
            mode: 'index'
        },
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                titleColor: 'white',
                bodyColor: 'white',
                borderColor: 'rgba(255, 255, 255, 0.1)',
                borderWidth: 1,
                callbacks: {
                    label: function(context) {
                        return 'Temperatura: ' + context.parsed.y + '°C';
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                max: 10,
                grid: {
                    color: 'rgba(0, 0, 0, 0.1)'
                },
                ticks: {
                    callback: function(value) {
                        return value + '°C';
                    }
                }
            },
            x: {
                grid: {
                    color: 'rgba(0, 0, 0, 0.1)'
                }
            }
        },
        elements: {
            line: {
                tension: 0.4
            },
            point: {
                radius: 6,
                hoverRadius: 8
            }
        }
    }
});
}

// Dashboard Weekly Production Chart
const dashboardWeeklyCtx = document.getElementById('dashboardWeeklyChart');
if (dashboardWeeklyCtx) {
console.log('✅ Inicializando gráfico dashboardWeeklyChart...');
window.dashboardWeeklyChart = new Chart(dashboardWeeklyCtx, {
    type: 'bar',
    data: {
        labels: [],
        datasets: [{
            label: 'Produção (L)',
            data: [],
            backgroundColor: '#5bb85b',
            borderRadius: 8
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                // Escala dinâmica - sem limite máximo
                ticks: {
                    callback: function(value) {
                        return value + 'L';
                    }
                }
            }
        }
    }
});
console.log('✅ Gráfico dashboardWeeklyChart inicializado com sucesso');
} else {
console.error('❌ Elemento dashboardWeeklyChart não encontrado no DOM');
}

// Weekly Volume Chart
const weeklyVolumeCtx = document.getElementById('weeklyVolumeChart');
if (weeklyVolumeCtx) {
window.weeklyVolumeChart = new Chart(weeklyVolumeCtx, {
    type: 'line',
    data: {
        labels: [],
        datasets: [{
            label: 'Volume Semanal (L)',
            data: [],
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            borderWidth: 3,
            pointRadius: 6,
            pointHoverRadius: 8,
            pointBackgroundColor: '#3b82f6',
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
            intersect: false,
            mode: 'index'
        },
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                titleColor: 'white',
                bodyColor: 'white',
                borderColor: 'rgba(255, 255, 255, 0.1)',
                borderWidth: 1,
                callbacks: {
                    label: function(context) {
                        return 'Volume: ' + context.parsed.y + 'L';
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: 'rgba(0, 0, 0, 0.1)'
                },
                ticks: {
                    callback: function(value) {
                        return value + 'L';
                    }
                }
            },
            x: {
                grid: {
                    color: 'rgba(0, 0, 0, 0.1)'
                }
            }
        }
    }
});
}

// Daily Volume Chart
const dailyVolumeCtx = document.getElementById('dailyVolumeChart');
if (dailyVolumeCtx) {
window.dailyVolumeChart = new Chart(dailyVolumeCtx, {
    type: 'line',
    data: {
        labels: [],
        datasets: [{
            label: 'Volume por Horário (L)',
            data: [],
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                // Escala dinâmica - sem limite máximo
                ticks: {
                    callback: function(value) {
                        return value + 'L';
                    }
                }
            }
        }
    }
});
}

// Quality Trend Chart
const qualityTrendCtx = document.getElementById('qualityTrendChart');
if (qualityTrendCtx) {
qualityTrendChart = new Chart(qualityTrendCtx, {
    type: 'line',
    data: {
        labels: [],
        datasets: [
            {
                label: 'Gordura (%)',
                data: [],
                borderColor: '#f59e0b',
                backgroundColor: 'rgba(245, 158, 11, 0.1)',
                borderWidth: 3,
                pointRadius: 5,
                pointHoverRadius: 7,
                pointBackgroundColor: '#f59e0b',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                tension: 0.4,
                fill: false
            },
            {
                label: 'Proteína (%)',
                data: [],
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                borderWidth: 3,
                pointRadius: 5,
                pointHoverRadius: 7,
                pointBackgroundColor: '#10b981',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                tension: 0.4,
                fill: false
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
            intersect: false,
            mode: 'index'
        },
        scales: {
            y: {
                beginAtZero: true,
                max: 6,
                grid: {
                    color: 'rgba(0, 0, 0, 0.1)'
                },
                ticks: {
                    callback: function(value) {
                        return value + '%';
                    }
                }
            },
            x: {
                grid: {
                    color: 'rgba(0, 0, 0, 0.1)'
                }
            }
        },
        plugins: {
            legend: {
                display: true,
                position: 'top',
                labels: {
                    usePointStyle: true,
                    padding: 20
                }
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                titleColor: 'white',
                bodyColor: 'white',
                borderColor: 'rgba(255, 255, 255, 0.1)',
                borderWidth: 1
            }
        }
    }
});
}

// Quality Distribution Chart
const qualityDistCtx = document.getElementById('qualityDistributionChart');
if (qualityDistCtx) {
qualityDistributionChart = new Chart(qualityDistCtx, {
    type: 'doughnut',
    data: {
        labels: ['Excelente', 'Bom', 'Regular'],
        datasets: [{
            data: [0, 0, 0],
            backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});
}

// Payments Chart
const paymentsCtx = document.getElementById('paymentsChart');
if (paymentsCtx) {
window.paymentsChart = new Chart(paymentsCtx, {
    type: 'bar',
    data: {
        labels: ['Pagos', 'Pendentes', 'Atrasados'],
        datasets: [{
            label: 'Vendas (R$)',
            data: [0, 0, 0],
            backgroundColor: ['#10b981', '#f59e0b', '#ef4444']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});
}
// Weekly Summary Chart
const weeklySummaryCtx = document.getElementById('weeklySummaryChart');
if (weeklySummaryCtx) {
window.weeklySummaryChart = new Chart(weeklySummaryCtx, {
    type: 'bar',
    data: {
        labels: [],
        datasets: [{
            label: 'Volume Semanal (L)',
            data: [],
            backgroundColor: '#3b82f6',
            borderColor: '#1d4ed8',
            borderWidth: 1,
            borderRadius: 8
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return value + 'L';
                    }
                }
            }
        }
    }
});
}

// Monthly Volume Chart
const monthlyVolumeCtx = document.getElementById('monthlyVolumeChart');
if (monthlyVolumeCtx) {
window.monthlyVolumeChart = new Chart(monthlyVolumeCtx, {
    type: 'line',
    data: {
        labels: [],
        datasets: [{
            label: 'Volume Mensal (L)',
            data: [],
            borderColor: '#10b981',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            borderWidth: 3,
            pointRadius: 6,
            pointHoverRadius: 8,
            pointBackgroundColor: '#10b981',
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return value + 'L';
                    }
                }
            }
        }
    }
});
}
}



// Hide notification
function hideNotification() {
document.getElementById('notificationToast').classList.remove('show');
}

// Profile modal functions
function openProfileModal() {
document.getElementById('profileModal').classList.add('show');
// Configurar header dinâmico quando o modal for aberto
setTimeout(() => {
setupProfileModalHeader();
}, 100);
}

function closeProfileModal() {
document.getElementById('profileModal').classList.remove('show');
}

// Profile edit functions
function toggleProfileEdit() {
const viewMode = document.getElementById('profileViewMode');
const editMode = document.getElementById('profileEditMode');
const editBtn = document.getElementById('editProfileBtn');
const editButtons = document.getElementById('profileEditButtons');

if (editMode.classList.contains('hidden')) {
// Switch to edit mode
viewMode.classList.add('hidden');
editMode.classList.remove('hidden');
// NÃO mostrar botões automaticamente - só quando há mudanças
editButtons.classList.add('hidden');
editBtn.innerHTML = `
    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
    </svg>
    Cancelar
`;
editBtn.onclick = cancelProfileEdit;

// Populate edit form with current values
populateEditForm();
} else {
// Switch back to view mode
cancelProfileEdit();
}
}

function cancelProfileEdit() {
console.log('❌ Cancelando edição do perfil...');

const viewMode = document.getElementById('profileViewMode');
const editMode = document.getElementById('profileEditMode');
const editBtn = document.getElementById('editProfileBtn');
const editButtons = document.getElementById('profileEditButtons');

viewMode.classList.remove('hidden');
editMode.classList.add('hidden');

// Garantir que os botões sejam ocultados completamente
editButtons.classList.add('hidden');
editButtons.style.display = 'none';
editButtons.style.visibility = 'hidden';
editButtons.style.opacity = '0';
editButtons.style.pointerEvents = 'none';

editBtn.innerHTML = `
<svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
</svg>
Editar
`;
editBtn.onclick = toggleProfileEdit;

console.log('✅ Edição cancelada, botões ocultados');
}

function populateEditForm() {
console.log('📝 Preenchendo formulário de edição...');

// Get current values from view mode
const currentName = document.getElementById('profileFullName').textContent;
const currentEmail = document.getElementById('profileEmail2').textContent;
const currentWhatsApp = document.getElementById('profileWhatsApp').textContent;

// Populate edit form
document.getElementById('editProfileName').value = currentName === 'Carregando...' ? '' : currentName;
document.getElementById('editProfileEmail').value = currentEmail === 'Carregando...' ? '' : currentEmail;
document.getElementById('editProfileWhatsApp').value = currentWhatsApp === 'Carregando...' || currentWhatsApp === 'Não informado' ? '' : currentWhatsApp;

// Store original values for comparison
window.originalProfileValues = {
name: currentName === 'Carregando...' ? '' : currentName,
email: currentEmail === 'Carregando...' ? '' : currentEmail,
whatsapp: currentWhatsApp === 'Carregando...' || currentWhatsApp === 'Não informado' ? '' : currentWhatsApp
};

console.log('📋 Valores originais armazenados:', window.originalProfileValues);

// Add change listeners to show/hide edit buttons
const editFields = ['editProfileName', 'editProfileEmail', 'editProfileWhatsApp'];
editFields.forEach(fieldId => {
const field = document.getElementById(fieldId);
if (field) {
    // Remover listener anterior se existir
    field.removeEventListener('input', checkForChanges);
    field.addEventListener('input', checkForChanges);
    console.log(`✅ Listener adicionado ao campo: ${fieldId}`);
}
});

    // Verificar mudanças iniciais
setTimeout(() => {
    checkForChanges();
}, 100);

// Garantir que os botões estejam ocultos inicialmente
const editButtons = document.getElementById('profileEditButtons');
if (editButtons) {
    editButtons.classList.add('hidden');
    editButtons.style.display = 'none';
    editButtons.style.visibility = 'hidden';
    editButtons.style.opacity = '0';
    editButtons.style.pointerEvents = 'none';
    console.log('✅ Botões de edição ocultados inicialmente');
}
}

function checkForChanges() {
const editButtons = document.getElementById('profileEditButtons');
const currentName = document.getElementById('editProfileName').value;
const currentEmail = document.getElementById('editProfileEmail').value;
const currentWhatsApp = document.getElementById('editProfileWhatsApp').value;

// Verificar se os valores originais existem
if (!window.originalProfileValues) {
console.log('⚠️ Valores originais não encontrados, ocultando botões');
editButtons.classList.add('hidden');
editButtons.style.display = 'none';
editButtons.style.visibility = 'hidden';
editButtons.style.opacity = '0';
editButtons.style.pointerEvents = 'none';
return;
}

const hasChanges = 
currentName !== window.originalProfileValues.name ||
currentEmail !== window.originalProfileValues.email ||
currentWhatsApp !== window.originalProfileValues.whatsapp;

console.log('🔍 Verificando mudanças:', {
current: { name: currentName, email: currentEmail, whatsapp: currentWhatsApp },
original: window.originalProfileValues,
hasChanges: hasChanges
});

if (hasChanges) {
editButtons.classList.remove('hidden');
editButtons.style.display = 'flex';
editButtons.style.visibility = 'visible';
editButtons.style.opacity = '1';
editButtons.style.pointerEvents = 'auto';
console.log('✅ Mudanças detectadas, mostrando botões');
} else {
editButtons.classList.add('hidden');
editButtons.style.display = 'none';
editButtons.style.visibility = 'hidden';
editButtons.style.opacity = '0';
editButtons.style.pointerEvents = 'none';
console.log('✅ Sem mudanças, ocultando botões');
}
}

// Handle profile update form submission
async function handleUpdateProfile(event) {
event.preventDefault();

try {
const formData = new FormData(event.target);
const { data: { user } } = await supabase.auth.getUser();

if (!user) {
    throw new Error('Usuário não autenticado');
}

const updateData = {
    name: formData.get('name'),
    whatsapp: formData.get('whatsapp') || null,
    // Campos de relatório adicionados
    report_farm_name: formData.get('report_farm_name') || null,
    report_farm_logo_base64: formData.get('report_farm_logo_base64') || null,
    report_footer_text: formData.get('report_footer_text') || null,
    report_system_logo_base64: formData.get('report_system_logo_base64') || null
};

// Handle profile photo upload if provided
const profilePhotoFile = formData.get('profilePhoto');
if (profilePhotoFile && profilePhotoFile.size > 0) {
    try {
        const profilePhotoUrl = await uploadProfilePhoto(profilePhotoFile, user.id);
        updateData.profile_photo_url = profilePhotoUrl;
    } catch (photoError) {
        console.error('Erro ao fazer upload da foto de perfil:', photoError);
        // Don't show error notification for photo upload - profile update is more important
        // showNotification('Erro ao fazer upload da foto de perfil, mas outros dados foram atualizados', 'warning');
    }
}

// First, check if user exists in users table
const { data: existingUser, error: checkError } = await supabase
    .from('users')
    .select('*')
    .eq('id', user.id)
    .single();

// Update user table in database
const { data, error } = await supabase
    .from('users')
    .update(updateData)
    .eq('id', user.id)
    .select();

if (error) {
    throw error;
}

// Also update user metadata in Supabase Auth
const { error: authError } = await supabase.auth.updateUser({
    data: {
        name: updateData.name,
        whatsapp: updateData.whatsapp
    }
});

if (authError) {
}

// Update the view mode with new values
document.getElementById('profileFullName').textContent = updateData.name || 'Não informado';
document.getElementById('profileName').textContent = updateData.name || 'Não informado';

// Extract formal name for header and welcome message
const formalName = extractFormalName(updateData.name);
document.getElementById('managerName').textContent = formalName || 'Gerente';
document.getElementById('managerWelcome').textContent = formalName || 'Gerente';

document.getElementById('profileWhatsApp').textContent = updateData.whatsapp || 'Não informado';

// Update localStorage/sessionStorage if exists (excluding profile_photo_url to prevent cross-page sharing)
const sessionData = localStorage.getItem('userData') || sessionStorage.getItem('userData');
if (sessionData) {
    try {
        const userData = JSON.parse(sessionData);
        userData.name = updateData.name;
        userData.whatsapp = updateData.whatsapp;
        // DO NOT update profile_photo_url in localStorage to prevent sharing between pages
        // Each page should load profile photo directly from database
        
        if (localStorage.getItem('userData')) {
            localStorage.setItem('userData', JSON.stringify(userData));
        }
        if (sessionStorage.getItem('userData')) {
            sessionStorage.setItem('userData', JSON.stringify(userData));
        }
    } catch (e) {
    }
}

// Switch back to view mode
cancelProfileEdit();

showNotification('Perfil atualizado com sucesso!', 'success');

} catch (error) {
console.error('Erro ao atualizar perfil:', error);
showNotification('Erro ao atualizar perfil: ' + error.message, 'error');
}
}

// Action functions
function addVolumeRecord() {
// Criar modal para adicionar novo registro de volume
const modal = document.createElement('div');
modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
modal.innerHTML = `
<div class="bg-white    rounded-2xl p-6 w-full max-w-md mx-4">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-xl font-bold text-slate-900   ">Novo Registro de Volume</h3>
        <button onclick="closeVolumeModal()" class="text-gray-400 hover:text-gray-600   :text-gray-300">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>
    <form id="volumeForm" onsubmit="handleAddVolume(event)">
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700    mb-2">Data</label>
                <input type="date" name="production_date" id="volumeDateInput" required class="w-full px-3 py-2 border border-gray-300    rounded-lg focus:ring-2 focus:ring-forest-500 focus:border-transparent      ">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700    mb-2">Turno</label>
                <select name="shift" required class="w-full px-3 py-2 border border-gray-300    rounded-lg focus:ring-2 focus:ring-forest-500 focus:border-transparent      ">
                    <option value="">Selecione o turno</option>
                    <option value="manha">Manhã</option>
                    <option value="tarde">Tarde</option>
                    <option value="noite">Noite</option>
                    <option value="madrugada">Madrugada</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700    mb-2">Volume (Litros)</label>
                <input type="number" name="volume" step="0.1" min="0" required class="w-full px-3 py-2 border border-gray-300    rounded-lg focus:ring-2 focus:ring-forest-500 focus:border-transparent      " placeholder="0.0">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700    mb-2">Temperatura (°C)</label>
                <input type="number" name="temperature" step="0.1" class="w-full px-3 py-2 border border-gray-300    rounded-lg focus:ring-2 focus:ring-forest-500 focus:border-transparent      " placeholder="4.0">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700    mb-2">Observações</label>
                <textarea name="observations" rows="3" class="w-full px-3 py-2 border border-gray-300    rounded-lg focus:ring-2 focus:ring-forest-500 focus:border-transparent      " placeholder="Observações adicionais (opcional)"></textarea>
            </div>
        </div>
        <div class="flex gap-3 mt-6">
            <button type="button" onclick="closeVolumeModal()" class="flex-1 px-4 py-2 border border-gray-300    text-gray-700    rounded-lg hover:bg-gray-50   :bg-slate-700 transition-colors">
                Cancelar
            </button>
            <button type="submit" class="flex-1 px-4 py-2 bg-forest-600 text-white rounded-lg hover:bg-forest-700 transition-colors">
                Registrar
            </button>
        </div>
    </form>
</div>
`;

modal.id = 'volumeModal';
document.body.appendChild(modal);

// Set default date to today
const today = new Date().toISOString().split('T')[0];
modal.querySelector('input[name="production_date"]').value = today;
}
function addQualityTest() {
// Criar modal para adicionar novo teste de qualidade
const modal = document.createElement('div');
modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 quality-modal-backdrop py-8 px-4';
modal.innerHTML = `
<div class="bg-white rounded-3xl p-8 w-full max-w-lg mx-4 shadow-2xl transform transition-all duration-300 scale-100">
    <!-- Header com ícone -->
    <div class="flex items-center justify-between mb-8 quality-modal-header p-6 -m-8 mb-8 rounded-t-3xl">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div>
                <h3 class="text-xl font-bold text-gray-900">Novo Teste de Qualidade</h3>
                <p class="text-sm text-gray-500">Registre os parâmetros de qualidade do leite</p>
            </div>
        </div>
        <button onclick="closeQualityModal()" class="quality-close-btn text-gray-400 hover:text-gray-600 transition-colors p-2 hover:bg-gray-100 rounded-lg">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>

    <form id="qualityForm" onsubmit="handleAddQuality(event)" class="quality-form space-y-6">
        <!-- Data do Teste -->
        <div class="relative">
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                Data do Teste
            </label>
            <input type="date" name="test_date" required 
                   class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200 bg-gray-50 focus:bg-white">
        </div>

        <!-- Parâmetros de Qualidade em Grid -->
        <div class="quality-modal-grid grid grid-cols-2 gap-4">
            <!-- Gordura -->
            <div class="relative">
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <div class="w-3 h-3 bg-orange-400 rounded-full inline-block mr-2"></div>
                    Gordura (%)
                </label>
                <div class="relative">
                    <input type="number" name="fat_percentage" step="0.01" min="0" max="100" required 
                           class="w-full px-4 py-3 pr-16 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all duration-200 bg-gray-50 focus:bg-white" 
                           placeholder="3.50">
                    <div class="absolute inset-y-0 right-0 flex flex-col items-center pr-3">
                        <button type="button" onclick="adjustValue('fat_percentage', 0.1)" class="text-gray-400 hover:text-orange-600 p-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                            </svg>
                        </button>
                        <button type="button" onclick="adjustValue('fat_percentage', -0.1)" class="text-gray-400 hover:text-orange-600 p-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="quality-standards">Padrão: 3.0 - 6.0%</div>
            </div>

            <!-- Proteína -->
            <div class="relative">
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <div class="w-3 h-3 bg-blue-400 rounded-full inline-block mr-2"></div>
                    Proteína (%)
                </label>
                <div class="relative">
                    <input type="number" name="protein_percentage" step="0.01" min="0" max="100" required 
                           class="w-full px-4 py-3 pr-16 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 bg-gray-50 focus:bg-white" 
                           placeholder="3.20">
                    <div class="absolute inset-y-0 right-0 flex flex-col items-center pr-3">
                        <button type="button" onclick="adjustValue('protein_percentage', 0.1)" class="text-gray-400 hover:text-blue-600 p-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                            </svg>
                        </button>
                        <button type="button" onclick="adjustValue('protein_percentage', -0.1)" class="text-gray-400 hover:text-blue-600 p-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="quality-standards">Padrão: 2.8 - 4.0%</div>
            </div>

            <!-- CCS -->
            <div class="relative">
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <div class="w-3 h-3 bg-red-400 rounded-full inline-block mr-2"></div>
                    CCS (mil/mL)
                </label>
                <div class="relative">
                    <input type="number" name="scc" min="0" max="1000" required 
                           class="w-full px-4 py-3 pr-16 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all duration-200 bg-gray-50 focus:bg-white" 
                           placeholder="200">
                    <div class="absolute inset-y-0 right-0 flex flex-col items-center pr-3">
                        <button type="button" onclick="adjustValue('scc', 10)" class="text-gray-400 hover:text-red-600 p-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                            </svg>
                        </button>
                        <button type="button" onclick="adjustValue('scc', -10)" class="text-gray-400 hover:text-red-600 p-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="quality-standards">Máximo: 400 mil/mL</div>
            </div>

            <!-- CBT -->
            <div class="relative">
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <div class="w-3 h-3 bg-purple-400 rounded-full inline-block mr-2"></div>
                    CBT (mil/mL)
                </label>
                <div class="relative">
                    <input type="number" name="total_bacterial_count" min="0" max="1000" required 
                           class="w-full px-4 py-3 pr-16 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all duration-200 bg-gray-50 focus:bg-white" 
                           placeholder="50">
                    <div class="absolute inset-y-0 right-0 flex flex-col items-center pr-3">
                        <button type="button" onclick="adjustValue('total_bacterial_count', 5)" class="text-gray-400 hover:text-purple-600 p-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                            </svg>
                        </button>
                        <button type="button" onclick="adjustValue('total_bacterial_count', -5)" class="text-gray-400 hover:text-purple-600 p-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="quality-standards">Máximo: 100 mil/mL</div>
            </div>
        </div>

        <!-- Laboratório -->
        <div class="relative">
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
                </svg>
                Laboratório
            </label>
            <input type="text" name="laboratory" 
                   class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200 bg-gray-50 focus:bg-white" 
                   placeholder="Nome do laboratório">
        </div>

        <!-- Observações -->
        <div class="relative">
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
                Observações
            </label>
            <textarea name="notes" rows="3" 
                      class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200 bg-gray-50 focus:bg-white resize-none" 
                      placeholder="Observações adicionais (opcional)"></textarea>
        </div>

        <!-- Botões -->
        <div class="flex gap-4 pt-4">
            <button type="button" onclick="closeQualityModal()" 
                    class="flex-1 px-6 py-3 border-2 border-gray-300 text-gray-700 font-semibold rounded-xl hover:bg-gray-50 hover:border-gray-400 transition-all duration-200">
                Cancelar
            </button>
            <button type="submit" 
                    class="quality-btn-primary flex-1 px-6 py-3 bg-gradient-to-r from-green-600 to-green-700 text-white font-semibold rounded-xl hover:from-green-700 hover:to-green-800 transition-all duration-200 shadow-lg hover:shadow-xl">
                <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                Registrar Teste
            </button>
        </div>
    </form>
</div>
`;

modal.id = 'qualityModal';
document.body.appendChild(modal);

// Set default date to today
const today = new Date().toISOString().split('T')[0];
modal.querySelector('input[name="test_date"]').value = today;

// Garantir posicionamento correto
modal.style.position = 'fixed';
modal.style.top = '0';
modal.style.left = '0';
modal.style.width = '100%';
modal.style.height = '100%';
modal.style.display = 'flex';
modal.style.alignItems = 'center';
modal.style.justifyContent = 'center';
modal.style.zIndex = '9999';

// Adicionar animação de entrada
setTimeout(() => {
modal.querySelector('.bg-white').classList.add('scale-100');
}, 10);

// Ativar validação em tempo real
setTimeout(() => {
validateQualityInputs();
addQualitySummary();
}, 100);
}

function addPayment() {
// Criar modal para adicionar novo pagamento
const modal = document.createElement('div');
modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
modal.innerHTML = `
<div class="bg-white    rounded-2xl p-6 w-full max-w-md mx-4">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-xl font-bold text-slate-900   ">Nova Venda de Leite</h3>
        <button onclick="closePaymentModal()" class="text-gray-400 hover:text-gray-600   :text-gray-300">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>
    <form id="paymentForm" onsubmit="handleAddPayment(event)">
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700    mb-2">Descrição</label>
                <input type="text" name="description" required class="w-full px-3 py-2 border border-gray-300    rounded-lg focus:ring-2 focus:ring-forest-500 focus:border-transparent      " placeholder="Ex: Venda para Laticínio ABC">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700    mb-2">Valor (R$)</label>
                <input type="number" name="amount" step="0.01" required class="w-full px-3 py-2 border border-gray-300    rounded-lg focus:ring-2 focus:ring-forest-500 focus:border-transparent      " placeholder="0,00">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700    mb-2">Tipo</label>
                <select name="payment_type" required class="w-full px-3 py-2 border border-gray-300    rounded-lg focus:ring-2 focus:ring-forest-500 focus:border-transparent      ">
                    <option value="">Selecione o tipo</option>
                    <option value="laticinio">Laticínio</option>
                    <option value="cooperativa">Cooperativa</option>
                    <option value="distribuidor">Distribuidor</option>
                    <option value="consumidor_final">Consumidor Final</option>
                    <option value="exportacao">Exportação</option>
                    <option value="outros">Outros</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700    mb-2">Data da Venda</label>
                <input type="date" name="due_date" required class="w-full px-3 py-2 border border-gray-300    rounded-lg focus:ring-2 focus:ring-forest-500 focus:border-transparent      ">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700    mb-2">Status</label>
                <select name="status" required class="w-full px-3 py-2 border border-gray-300    rounded-lg focus:ring-2 focus:ring-forest-500 focus:border-transparent      ">
                    <option value="pending">Pendente</option>
                    <option value="completed">Concluída</option>
                    <option value="overdue">Atrasada</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700    mb-2">Observações</label>
                <textarea name="notes" rows="3" class="w-full px-3 py-2 border border-gray-300    rounded-lg focus:ring-2 focus:ring-forest-500 focus:border-transparent      " placeholder="Observações adicionais (opcional)"></textarea>
            </div>
        </div>
        <div class="flex gap-3 mt-6">
            <button type="button" onclick="closePaymentModal()" class="flex-1 px-4 py-2 border border-gray-300    text-gray-700    rounded-lg hover:bg-gray-50   :bg-slate-700 transition-colors">
                Cancelar
            </button>
            <button type="submit" class="flex-1 px-4 py-2 bg-forest-600 text-white rounded-lg hover:bg-forest-700 transition-colors">
                Adicionar
            </button>
        </div>
    </form>
</div>
`;

modal.id = 'paymentModal';
document.body.appendChild(modal);

// Set default due date to today
const today = new Date().toISOString().split('T')[0];
modal.querySelector('input[name="due_date"]').value = today;
}

function closePaymentModal() {
const modal = document.getElementById('paymentModal');
if (modal) {
modal.remove();
}
}

function closeVolumeModal() {
const modal = document.getElementById('volumeModal');
if (modal) {
modal.remove();
}
}

function closeQualityModal() {
const modal = document.getElementById('qualityModal');
if (modal) {
// Adicionar animação de saída
modal.querySelector('.bg-white').classList.add('scale-95', 'opacity-0');
setTimeout(() => {
    modal.remove();
}, 200);
}
}

// Função para ajustar valores dos campos numéricos
function adjustValue(fieldName, increment) {
const field = document.querySelector(`input[name="${fieldName}"]`);
if (field) {
const currentValue = parseFloat(field.value) || 0;
const newValue = Math.max(0, currentValue + increment);

// Determinar número de casas decimais baseado no campo
let decimalPlaces = 0;
if (fieldName === 'fat_percentage' || fieldName === 'protein_percentage') {
    decimalPlaces = 2;
}

field.value = newValue.toFixed(decimalPlaces);

// Adicionar feedback visual
const color = increment > 0 ? '#fef3c7' : '#fef2f2';
field.style.backgroundColor = color;
setTimeout(() => {
    field.style.backgroundColor = '';
}, 300);

// Disparar evento de input para validação
field.dispatchEvent(new Event('input'));
}
}

// Função para validar valores em tempo real
function validateQualityInputs() {
const inputs = document.querySelectorAll('#qualityForm input[type="number"]');
inputs.forEach(input => {
input.addEventListener('input', function() {
    const value = parseFloat(this.value);
    const fieldName = this.name;
    
    // Validações específicas por campo
    switch(fieldName) {
        case 'fat_percentage':
            if (value < 0 || value > 100) {
                this.style.borderColor = '#ef4444';
                this.style.backgroundColor = '#fef2f2';
            } else if (value < 3.0 || value > 6.0) {
                this.style.borderColor = '#f59e0b';
                this.style.backgroundColor = '#fffbeb';
            } else {
                this.style.borderColor = '#10b981';
                this.style.backgroundColor = '#f0fdf4';
            }
            break;
            
        case 'protein_percentage':
            if (value < 0 || value > 100) {
                this.style.borderColor = '#ef4444';
                this.style.backgroundColor = '#fef2f2';
            } else if (value < 2.8 || value > 4.0) {
                this.style.borderColor = '#f59e0b';
                this.style.backgroundColor = '#fffbeb';
            } else {
                this.style.borderColor = '#10b981';
                this.style.backgroundColor = '#f0fdf4';
            }
            break;
            
        case 'scc':
            if (value < 0) {
                this.style.borderColor = '#ef4444';
                this.style.backgroundColor = '#fef2f2';
            } else if (value > 400) {
                this.style.borderColor = '#ef4444';
                this.style.backgroundColor = '#fef2f2';
            } else if (value > 300) {
                this.style.borderColor = '#f59e0b';
                this.style.backgroundColor = '#fffbeb';
            } else {
                this.style.borderColor = '#10b981';
                this.style.backgroundColor = '#f0fdf4';
            }
            break;
            
        case 'total_bacterial_count':
            if (value < 0) {
                this.style.borderColor = '#ef4444';
                this.style.backgroundColor = '#fef2f2';
            } else if (value > 100) {
                this.style.borderColor = '#ef4444';
                this.style.backgroundColor = '#fef2f2';
            } else if (value > 50) {
                this.style.borderColor = '#f59e0b';
                this.style.backgroundColor = '#fffbeb';
            } else {
                this.style.borderColor = '#10b981';
                this.style.backgroundColor = '#f0fdf4';
            }
            break;
    }
    
    // Reset após 2 segundos
    setTimeout(() => {
        this.style.borderColor = '';
        this.style.backgroundColor = '';
    }, 2000);
});
});
}

// Função para adicionar resumo visual dos valores
function addQualitySummary() {
const inputs = document.querySelectorAll('#qualityForm input[type="number"]');
const summaryContainer = document.createElement('div');
summaryContainer.className = 'quality-summary mt-4 p-4 bg-gradient-to-r from-blue-50 to-green-50 rounded-xl border border-blue-200';
summaryContainer.innerHTML = `
<div class="flex items-center justify-between mb-2">
    <h4 class="text-sm font-semibold text-gray-700">Resumo da Qualidade</h4>
    <div class="quality-overall-status w-3 h-3 rounded-full bg-gray-300"></div>
</div>
<div class="grid grid-cols-2 gap-2 text-xs">
    <div class="quality-summary-item">
        <span class="text-gray-600">Gordura:</span>
        <span class="quality-fat-status font-semibold">--%</span>
    </div>
    <div class="quality-summary-item">
        <span class="text-gray-600">Proteína:</span>
        <span class="quality-protein-status font-semibold">--%</span>
    </div>
    <div class="quality-summary-item">
        <span class="text-gray-600">CCS:</span>
        <span class="quality-scc-status font-semibold">--</span>
    </div>
    <div class="quality-summary-item">
        <span class="text-gray-600">CBT:</span>
        <span class="quality-cbt-status font-semibold">--</span>
    </div>
</div>
`;

// Inserir o resumo após o grid de parâmetros
const gridContainer = document.querySelector('.quality-modal-grid').parentElement;
gridContainer.insertBefore(summaryContainer, gridContainer.querySelector('div:last-child'));

// Atualizar resumo quando valores mudarem
inputs.forEach(input => {
input.addEventListener('input', updateQualitySummary);
});
}

// Função para atualizar o resumo
function updateQualitySummary() {
const fatValue = parseFloat(document.querySelector('input[name="fat_percentage"]')?.value || 0);
const proteinValue = parseFloat(document.querySelector('input[name="protein_percentage"]')?.value || 0);
const sccValue = parseFloat(document.querySelector('input[name="scc"]')?.value || 0);
const cbtValue = parseFloat(document.querySelector('input[name="total_bacterial_count"]')?.value || 0);

// Atualizar valores
const fatStatus = document.querySelector('.quality-fat-status');
const proteinStatus = document.querySelector('.quality-protein-status');
const sccStatus = document.querySelector('.quality-scc-status');
const cbtStatus = document.querySelector('.quality-cbt-status');
const overallStatus = document.querySelector('.quality-overall-status');

if (fatStatus) {
fatStatus.textContent = fatValue > 0 ? fatValue.toFixed(2) + '%' : '--%';
fatStatus.className = getQualityStatusClass('fat', fatValue);
}

if (proteinStatus) {
proteinStatus.textContent = proteinValue > 0 ? proteinValue.toFixed(2) + '%' : '--%';
proteinStatus.className = getQualityStatusClass('protein', proteinValue);
}

if (sccStatus) {
sccStatus.textContent = sccValue > 0 ? sccValue.toFixed(0) : '--';
sccStatus.className = getQualityStatusClass('scc', sccValue);
}

if (cbtStatus) {
cbtStatus.textContent = cbtValue > 0 ? cbtValue.toFixed(0) : '--';
cbtStatus.className = getQualityStatusClass('cbt', cbtValue);
}

// Status geral
if (overallStatus) {
const overallScore = calculateOverallQuality(fatValue, proteinValue, sccValue, cbtValue);
overallStatus.className = `quality-overall-status w-3 h-3 rounded-full ${getOverallStatusClass(overallScore)}`;
}
}

// Função para calcular status de qualidade
function getQualityStatusClass(type, value) {
const baseClass = 'font-semibold';

switch(type) {
case 'fat':
    if (value >= 3.0 && value <= 6.0) return `${baseClass} text-green-600`;
    if (value >= 2.5 && value < 3.0 || value > 6.0 && value <= 7.0) return `${baseClass} text-yellow-600`;
    return `${baseClass} text-red-600`;
    
case 'protein':
    if (value >= 2.8 && value <= 4.0) return `${baseClass} text-green-600`;
    if (value >= 2.5 && value < 2.8 || value > 4.0 && value <= 4.5) return `${baseClass} text-yellow-600`;
    return `${baseClass} text-red-600`;
    
case 'scc':
    if (value <= 200) return `${baseClass} text-green-600`;
    if (value > 200 && value <= 400) return `${baseClass} text-yellow-600`;
    return `${baseClass} text-red-600`;
    
case 'cbt':
    if (value <= 50) return `${baseClass} text-green-600`;
    if (value > 50 && value <= 100) return `${baseClass} text-yellow-600`;
    return `${baseClass} text-red-600`;
    
default:
    return baseClass;
}
}

// Função para calcular qualidade geral
function calculateOverallQuality(fat, protein, scc, cbt) {
let score = 0;
let count = 0;

if (fat > 0) {
if (fat >= 3.0 && fat <= 6.0) score += 100;
else if (fat >= 2.5 && fat < 3.0 || fat > 6.0 && fat <= 7.0) score += 60;
else score += 20;
count++;
}

if (protein > 0) {
if (protein >= 2.8 && protein <= 4.0) score += 100;
else if (protein >= 2.5 && protein < 2.8 || protein > 4.0 && protein <= 4.5) score += 60;
else score += 20;
count++;
}

if (scc > 0) {
if (scc <= 200) score += 100;
else if (scc > 200 && scc <= 400) score += 60;
else score += 20;
count++;
}

if (cbt > 0) {
if (cbt <= 50) score += 100;
else if (cbt > 50 && cbt <= 100) score += 60;
else score += 20;
count++;
}

return count > 0 ? score / count : 0;
}

// Função para obter classe do status geral
function getOverallStatusClass(score) {
if (score >= 80) return 'bg-green-500';
if (score >= 60) return 'bg-yellow-500';
return 'bg-red-500';
}

async function handleAddVolume(event) {
event.preventDefault();
const formData = new FormData(event.target);

// Mapear valores do turno para inglês (conforme constraint da tabela)
const shiftMapping = {
'manha': 'morning',
'tarde': 'afternoon', 
'noite': 'evening',
'madrugada': 'night'
};

const volumeData = {
production_date: formData.get('production_date'),
shift: shiftMapping[formData.get('shift')] || formData.get('shift'),
volume: parseFloat(formData.get('volume')),
temperature: formData.get('temperature') ? parseFloat(formData.get('temperature')) : null,
observations: formData.get('observations') || null
};

console.log('📅 Data do formulário:', formData.get('production_date'));
console.log('📅 Data processada:', volumeData.production_date);

try {
// // const supabase = await getSupabaseClient(); // REMOVIDO // REMOVIDO - usando MySQL
const { data: { user: currentUser } } = await supabase.auth.getUser();
if (!currentUser) throw new Error('User not authenticated');

// Get current user's farm_id and name
const { data: managerData, error: managerError } = await supabase
    .from('users')
    .select('farm_id, name')
    .eq('id', currentUser.id)
    .single();

if (managerError) throw managerError;
if (!managerData?.farm_id) throw new Error('Farm not found');

// Preparar dados para inserção
const insertData = {
        farm_id: managerData.farm_id,
        user_id: currentUser.id,
        // Nome do funcionário será obtido via relacionamento com users
        production_date: volumeData.production_date,
        milking_type: volumeData.shift,
        volume_liters: volumeData.volume,
        temperature: volumeData.temperature,
        notes: volumeData.observations,
        created_at: new Date().toISOString()
};

// Verificar se está online ou offline
if (navigator.onLine && window.offlineSyncManager) {
    // Tentar salvar online primeiro
    try {
        const { error: volumeError } = await supabase
            .from('volume_records')
            .insert(insertData);

if (volumeError) throw volumeError;

showNotification('Registro de volume adicionado com sucesso!', 'success');
        
        // Notificação real do dispositivo
        if (window.nativeNotifications) {
            window.nativeNotifications.showRealDeviceNotification(
                'Nova Produção Registrada',
                `${volumeData.volume}L registrado com sucesso!`,
                'production'
            );
        }

    } catch (onlineError) {
        console.log('Erro online, salvando offline:', onlineError);
        // Se falhar online, salvar offline
        const result = await window.offlineSyncManager.addVolumeRecord(insertData);
        if (result.success) {
            showNotification('Registro salvo offline - será sincronizado quando voltar online!', 'success');
        } else {
            throw new Error(result.error);
        }
    }
} else {
    // Modo offline - usar sistema de sincronização
    if (window.offlineSyncManager) {
        const result = await window.offlineSyncManager.addVolumeRecord(insertData);
        if (result.success) {
            showNotification('Registro salvo offline - será sincronizado quando voltar online!', 'success');
        } else {
            throw new Error(result.error);
        }
    } else {
        throw new Error('Sistema offline não disponível');
    }
}
closeVolumeModal();

// Limpar cache para forçar atualização
CacheManager.clearCache();

// Reload volume data and charts - FORÇAR ATUALIZAÇÃO
console.log('🔄 Atualizando dados após registro de volume...');

// Atualizar dados de volume
await loadVolumeData();
console.log('✅ Dados de volume carregados');

// Atualizar todos os gráficos
await loadWeeklyVolumeChart();
console.log('✅ Gráfico semanal atualizado');

await loadDailyVolumeChart();
console.log('✅ Gráfico diário atualizado');

await loadDashboardWeeklyChart();
console.log('✅ Gráfico do dashboard atualizado');

// Atualizar estatísticas do dashboard
await updateDashboardStats();
console.log('✅ Estatísticas do dashboard atualizadas');

// Atualizar lista de registros de volume
await updateVolumeRecordsList();
console.log('✅ Lista de registros de volume atualizada');

// Forçar atualização da interface
setTimeout(() => {
    console.log('🔄 Forçando atualização final...');
    loadVolumeData();
    loadWeeklyVolumeChart();
    loadDailyVolumeChart();
    loadDashboardWeeklyChart();
}, 1000);

// Forçar atualização adicional após 2 segundos
setTimeout(() => {
    console.log('🔄 Forçando atualização adicional...');
    loadVolumeData();
    loadDashboardWeeklyChart();
}, 2000);
// Get user's farm_id for recent activities
const { data: { user } } = await supabase.auth.getUser();
if (user) {
    const { data: userData } = await supabase
        .from('users')
        .select('farm_id')
        .eq('id', user.id)
        .single();
    
    if (userData?.farm_id) {
        await loadRecentActivities(userData.farm_id);
    }
}

} catch (error) {
console.error('Error adding volume record:', error);
showNotification('Erro ao adicionar registro de volume: ' + error.message, 'error');
}
}

async function handleAddQuality(event) {
event.preventDefault();
const formData = new FormData(event.target);

const qualityData = {
test_date: formData.get('test_date'),
fat_percentage: parseFloat(formData.get('fat_percentage')),
protein_percentage: parseFloat(formData.get('protein_percentage')),
                scc: parseInt(formData.get('scc')),
                cbt: parseInt(formData.get('total_bacterial_count')),
laboratory: formData.get('laboratory') || null,
observations: formData.get('notes') || null
};

try {
// // const supabase = await getSupabaseClient(); // REMOVIDO // REMOVIDO - usando MySQL
// Usar a nova função RPC para registrar o teste de qualidade
const { data, error } = await supabase.rpc('register_quality_test', {
    p_test_date: qualityData.test_date,
    p_fat_percentage: qualityData.fat_percentage,
    p_protein_percentage: qualityData.protein_percentage,
    p_scc: qualityData.scc,
    p_cbt: qualityData.cbt,
    p_laboratory: qualityData.laboratory,
    p_observations: qualityData.observations
});

if (error) throw error;

showNotification('Teste de qualidade adicionado com sucesso!', 'success');
closeQualityModal();

// Reload quality data and charts
await loadQualityData();
await loadQualityTests();

// Get user's farm_id for recent activities
const { data: { user } } = await supabase.auth.getUser();
if (user) {
    const { data: userData } = await supabase
        .from('users')
        .select('farm_id')
        .eq('id', user.id)
        .single();
    
    if (userData?.farm_id) {
        await loadRecentActivities(userData.farm_id);
    }
}

} catch (error) {
console.error('Error adding quality test:', error);
showNotification('Erro ao adicionar teste de qualidade: ' + error.message, 'error');
}
}
async function handleAddPayment(event) {
event.preventDefault();
const formData = new FormData(event.target);

const paymentData = {
record_date: formData.get('due_date'), // Data do registro
type: 'income', // Tipo de registro financeiro (income = receita)
amount: parseFloat(formData.get('amount')), // Valor
description: `${formData.get('description')} - Tipo: ${formData.get('payment_type')}${formData.get('notes') ? ' - ' + formData.get('notes') : ''}`, // Descrição
category: formData.get('payment_type') || 'venda_leite' // Categoria
};

try {
// // const supabase = await getSupabaseClient(); // REMOVIDO // REMOVIDO - usando MySQL
const { data: { user: currentUser } } = await supabase.auth.getUser();
if (!currentUser) throw new Error('User not authenticated');

// Get current user's farm_id
const { data: managerData, error: managerError } = await supabase
    .from('users')
    .select('farm_id')
    .eq('id', currentUser.id)
    .single();

if (managerError) throw managerError;
if (!managerData?.farm_id) throw new Error('Farm not found');

// Insert financial record into database
const { error: paymentError } = await supabase
    .from('financial_records')
    .insert({
        ...paymentData,
        farm_id: managerData.farm_id,
        type: 'income'
    });

if (paymentError) throw paymentError;

showNotification('Venda adicionada com sucesso!', 'success');
closePaymentModal();

// Reload sales data and recent activities
await loadPaymentsData();

// Get user's farm_id for recent activities
if (currentUser) {
    const { data: userData } = await supabase
        .from('users')
        .select('farm_id')
        .eq('email', currentUser.email)
        .single();
    
    if (userData?.farm_id) {
        await loadRecentActivities(userData.farm_id);
    }
}

} catch (error) {
console.error('Error adding payment:', error);
showNotification('Erro ao adicionar venda: ' + error.message, 'error');
}
}

// Modal functions
function openAddUserModal() {
document.getElementById('addUserModal').classList.add('show');

// Reset do select para "Selecione o cargo" e esconder a seção de foto
const userRoleSelect = document.getElementById('userRole');
const addPhotoSection = document.getElementById('addPhotoSection');

if (userRoleSelect && addPhotoSection) {
// Definir como vazio para mostrar "Selecione o cargo"
userRoleSelect.value = '';

// Esconder a seção de foto por padrão
addPhotoSection.style.display = 'none';
addPhotoSection.style.visibility = 'hidden';
addPhotoSection.style.opacity = '0';
}
}

function closeAddUserModal() {
document.getElementById('addUserModal').classList.remove('show');
document.getElementById('addUserFormModal').reset();

// Reset profile photo preview
const preview = document.getElementById('profilePreview');
const placeholder = document.getElementById('profilePlaceholder');
if (preview && placeholder) {
preview.classList.add('hidden');
placeholder.classList.remove('hidden');
preview.src = '';
}

// Reset email preview
const emailPreview = document.getElementById('emailPreview');
if (emailPreview) {
emailPreview.textContent = 'Digite o nome para ver o email';
}
}

// Generate email based on name and farm
async function generateUserEmail(name, farmId) {
try {
// Validate input parameters
if (!name || typeof name !== 'string' || name.trim() === '') {
    throw new Error('Nome do usuário é obrigatório');
}

if (!farmId) {
    throw new Error('ID da fazenda é obrigatório');
}

// // const supabase = await getSupabaseClient(); // REMOVIDO // REMOVIDO - usando MySQL
// Get farm name
const { data: farmData, error: farmError } = await supabase
    .from('farms')
    .select('name')
    .eq('id', farmId)
    .single();

if (farmError) throw farmError;

if (!farmData || !farmData.name || typeof farmData.name !== 'string' || farmData.name.trim() === '') {
    throw new Error('Nome da fazenda não encontrado ou inválido');
}

// Sanitizar o nome da fazenda (tudo minúsculo, sem espaço, sem acento)
const farmName = farmData.name
    .trim()
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '') // Remove acentos
    .replace(/\s+/g, '') // Remove espaços
    .replace(/[^a-z0-9]/g, ''); // Remove caracteres especiais

// Extrair o primeiro nome do usuário
const firstName = name.trim().split(' ')[0]
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '') // Remove acentos
    .replace(/[^a-z0-9]/g, ''); // Remove caracteres especiais
    
// Validar se o primeiro nome não está vazio após sanitização
if (!firstName) {
    throw new Error('Nome do usuário inválido após sanitização');
}

let finalEmail;
let attempts = 0;
const maxAttempts = 50; // Evitar loop infinito

// Verificar se o e-mail já existe no banco
while (attempts < maxAttempts) {
    // Gerar dois números aleatórios entre 10 e 99
    const num1 = Math.floor(Math.random() * 90) + 10; // 10-99
    const num2 = Math.floor(Math.random() * 90) + 10; // 10-99
    
    finalEmail = `${firstName}${num1}${num2}@${farmName}.lactech.com`;
    
    // Verificar se email já existe
    const { data: existingUser, error } = await supabase
        .from('users')
        .select('id')
        .eq('email', finalEmail)
        .maybeSingle(); // Use maybeSingle() em vez de single()
    
    if (error) {
        console.error('Error checking email:', error);
        throw error;
    }
    
    if (!existingUser) {
        // Email disponível, sair do loop
        break;
    }
    
    attempts++;
}

if (attempts >= maxAttempts) {
    throw new Error('Não foi possível gerar um email único após várias tentativas');
}

return finalEmail;
} catch (error) {
console.error('Error generating email:', error);
throw error;
}
}

// Update email preview
async function updateEmailPreview(name) {
const emailPreview = document.getElementById('emailPreview');

if (!emailPreview) {
console.error('Email preview element not found');
return;
}

if (!name || typeof name !== 'string' || name.trim() === '') {
emailPreview.textContent = 'Digite o nome para ver o email';
return;
}

try {
// // const supabase = await getSupabaseClient(); // REMOVIDO // REMOVIDO - usando MySQL
const { data: { user: currentUser } } = await supabase.auth.getUser();
if (!currentUser) {
    emailPreview.textContent = 'Usuário não autenticado';
    return;
}

const { data: managerData, error: managerError } = await supabase
    .from('users')
    .select('farm_id')
    .eq('id', currentUser.id)
    .single();

if (managerError) throw managerError;

if (!managerData || !managerData.farm_id) {
    emailPreview.textContent = 'Fazenda não encontrada';
    return;
}

const email = await generateUserEmail(name, managerData.farm_id);
emailPreview.textContent = email;
} catch (error) {
console.error('Error in updateEmailPreview:', error);
emailPreview.textContent = 'Erro ao gerar email';
}
}

async function hashPassword(password) {
const encoder = new TextEncoder();
const data = encoder.encode(password);
const hash = await crypto.subtle.digest('SHA-256', data);
const hashArray = Array.from(new Uint8Array(hash));
const hashHex = hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
return hashHex;
}

async function sendWhatsAppCredentials(whatsapp, name, email, password) {
try {
let formattedNumber = whatsapp.replace(/\D/g, '');
if (!formattedNumber.startsWith('55')) {
    formattedNumber = '55' + formattedNumber;
}

const message = `🌱 *LACTECH - Sistema de Gestão Leiteira* 🥛\n\n` +
    `🎉 *Olá ${name}!*\n\n` +
    `Suas credenciais de acesso foram criadas com sucesso:\n\n` +
    `📧 *Email:* ${email}\n` +
    `🔑 *Senha:* ${password}\n\n` +
    `⚠️ *INSTRUÇÕES IMPORTANTES:*\n` +
    `✅ Mantenha suas credenciais seguras\n` +
    `✅ Não compartilhe com terceiros\n\n` +
    `🌐 *Acesse o sistema:*\n` +
    `https://lacteste.netlify.app/\n\n` +
    `📱 *Suporte técnico disponível*\n` +
    `Em caso de dúvidas, entre em contato\n\n` +
    `🚀 *Bem-vindo(a) à equipe LacTech!*\n` +
    `Juntos, vamos revolucionar a gestão leiteira! 🐄💚`;

// Copiar mensagem para área de transferência
try {
    await navigator.clipboard.writeText(message);
    
    // Mostrar modal com instruções
    showWhatsAppInstructions(formattedNumber, name, message);
    
    return true;
} catch (clipboardError) {
    console.error('Erro ao copiar para área de transferência:', clipboardError);
    // Fallback: mostrar modal mesmo sem copiar
    showWhatsAppInstructions(formattedNumber, name, message);
    return true;
}

} catch (error) {
console.error('Error sending WhatsApp message:', error);
return false;
}
}

// Mostrar modal com instruções para envio manual
function showWhatsAppInstructions(phoneNumber, userName, message) {
// Criar modal se não existir
let modal = document.getElementById('whatsappInstructionsModal');
if (!modal) {
modal = document.createElement('div');
modal.id = 'whatsappInstructionsModal';
modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
modal.innerHTML = `
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4" onclick="event.stopPropagation()">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-900">📱 Enviar Credenciais via WhatsApp</h3>
            <button onclick="closeWhatsAppInstructions()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <div class="space-y-4">
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <p class="text-sm text-green-800">
                    ✅ <strong>Mensagem copiada!</strong><br>
                    As credenciais foram copiadas para sua área de transferência.
                </p>
            </div>
            
            <div class="space-y-2">
                <p class="text-sm font-medium text-gray-700">Para enviar as credenciais:</p>
                <ol class="text-sm text-gray-600 space-y-1 list-decimal list-inside">
                    <li>Abra o WhatsApp no seu celular ou computador</li>
                    <li>Procure pelo contato: <strong>${phoneNumber}</strong></li>
                    <li>Cole a mensagem (Ctrl+V) e envie</li>
                </ol>
            </div>
            
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                <p class="text-xs text-blue-800">
                    💡 <strong>Dica:</strong> Você também pode clicar no botão abaixo para abrir o WhatsApp Web automaticamente.
                </p>
            </div>
            
            <div class="flex space-x-3">
                <button onclick="openWhatsAppWeb('${phoneNumber}', '${encodeURIComponent(message)}')" 
                        class="flex-1 bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors text-sm">
                    🌐 Abrir WhatsApp Web
                </button>
                <button onclick="copyMessageAgain('${encodeURIComponent(message)}')" 
                        class="flex-1 bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors text-sm">
                    📋 Copiar Novamente
                </button>
            </div>
            
            <button onclick="closeWhatsAppInstructions()" 
                    class="w-full bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors text-sm">
                Fechar
            </button>
        </div>
    </div>
`;
document.body.appendChild(modal);
}

// Adicionar evento de clique no overlay para fechar
modal.addEventListener('click', function(e) {
if (e.target === modal) {
    closeWhatsAppInstructions();
}
});

// Atualizar conteúdo do modal
const phoneElement = modal.querySelector('strong');
if (phoneElement) {
phoneElement.textContent = phoneNumber;
}

// Mostrar modal
modal.style.display = 'flex';

// Adicionar evento de tecla ESC para fechar
const handleEscape = function(e) {
if (e.key === 'Escape') {
    closeWhatsAppInstructions();
    document.removeEventListener('keydown', handleEscape);
}
};
document.addEventListener('keydown', handleEscape);
}

// Fechar modal de instruções
function closeWhatsAppInstructions() {
const modal = document.getElementById('whatsappInstructionsModal');
if (modal) {
modal.style.display = 'none';
// Remover modal do DOM para evitar conflitos
modal.remove();
}
}

// Abrir WhatsApp Web (opção alternativa)
function openWhatsAppWeb(phoneNumber, encodedMessage) {
const whatsappUrl = `https://wa.me/${phoneNumber}?text=${encodedMessage}`;
window.open(whatsappUrl, '_blank');
closeWhatsAppInstructions();
}

// Copiar mensagem novamente
async function copyMessageAgain(encodedMessage) {
try {
const message = decodeURIComponent(encodedMessage);
await navigator.clipboard.writeText(message);
showNotification('Mensagem copiada novamente!', 'success');
} catch (error) {
console.error('Erro ao copiar mensagem:', error);
showNotification('Erro ao copiar mensagem', 'error');
}
}

// Handle add user - VERSÃO SIMPLES
/**
* Manipula a criação de novos usuários
* Valida dados, gera email automático e envia credenciais via WhatsApp
*/
async function handleAddUser(e) {
console.log('🔍 handleAddUser chamada!');
e.preventDefault();
console.log('🔍 Evento prevenido');


const formData = new FormData(e.target);

const userData = {
name: formData.get('name'),
whatsapp: formData.get('whatsapp'),
password: formData.get('password'),
role: formData.get('role'),
photo_url: null
};

try {
console.log('🔍 Iniciando criação do usuário...');
// // const supabase = await getSupabaseClient(); // REMOVIDO // REMOVIDO - usando MySQL
console.log('🔍 Cliente Supabase obtido');
const { data: { user: currentUser } } = await supabase.auth.getUser();
console.log('🔍 Usuário atual:', currentUser);
if (!currentUser) throw new Error('Usuário não autenticado');

// Get current user's farm_id
const { data: managerData, error: managerError } = await supabase
    .from('users')
    .select('farm_id')
    .eq('id', currentUser.id)
    .single();

if (managerError) throw managerError;
if (!managerData?.farm_id) throw new Error('Fazenda não encontrada');

// Gerar email automaticamente
const email = await generateEmailFromName(userData.name);

// Usar a senha fornecida pelo usuário ou gerar uma temporária
const password = userData.password || generateTempPassword();

// Criar usuário no Supabase Auth primeiro
const { data: authData, error: authError } = await supabase.auth.signUp({
    email: email,
    password: password,
    options: {
        data: {
            name: userData.name,
            role: userData.role,
            whatsapp: userData.whatsapp
        }
    }
});

if (authError) {
    console.error('Erro ao criar usuário no Auth:', authError);
    throw new Error('Erro ao criar conta de usuário: ' + authError.message);
}

if (!authData.user) {
    throw new Error('Falha ao criar usuário no Auth');
}

// Criar perfil do usuário na tabela users usando o ID do Auth
const { data: result, error: profileError } = await supabase.rpc('create_farm_user', {
    p_user_id: authData.user.id,
    p_email: email,
    p_name: userData.name,
    p_whatsapp: userData.whatsapp,
    p_role: userData.role,
    p_farm_id: managerData.farm_id,
    p_profile_photo_url: null
});

if (profileError) throw profileError;

if (!result.success) {
    throw new Error(result.error || 'Falha ao criar usuário');
}

// Fazer upload da foto se for funcionário
const profilePhotoFile = formData.get('profilePhoto');
if (userData.role === 'funcionario' && profilePhotoFile && profilePhotoFile.size > 0) {
    console.log('🔍 Foto detectada, fazendo upload...');
    try {
        // Fazer upload da foto com o ID do usuário
        const profilePhotoUrl = await uploadProfilePhoto(profilePhotoFile, authData.user.id);
        
        // Atualizar usuário com a URL da foto
        const { error: updateError } = await supabase
            .from('users')
            .update({ profile_photo_url: profilePhotoUrl })
            .eq('id', authData.user.id);
            
        if (updateError) throw updateError;
        
        console.log('✅ Foto enviada com sucesso:', profilePhotoUrl);
        
    } catch (error) {
        console.error('❌ Erro ao fazer upload da foto:', error);
        // Continuar sem a foto se houver erro
    }
}

// Enviar WhatsApp com credenciais
const whatsappSent = await sendWhatsAppCredentials(
    userData.whatsapp, 
    userData.name, 
    email, 
    password
);

const successMessage = whatsappSent ? 
    `Usuário ${userData.name} criado com sucesso! Credenciais enviadas via WhatsApp.` : 
    `Usuário ${userData.name} criado com sucesso! Senha: ${password}`;
    
showNotification(successMessage, 'success');

// Notificação REAL do dispositivo para criação de usuário
if (window.nativeNotifications) {
    window.nativeNotifications.showRealDeviceNotification(
        'Novo Usuário Criado',
        `Usuário ${userData.name} (${userData.role}) foi criado no sistema`,
        'user_created'
    );
}

closeAddUserModal();

// Atualizar lista de usuários
setTimeout(() => {
    loadUsersData();
}, 1000);

} catch (error) {
console.error('Erro ao criar usuário:', error);
showNotification('Erro ao criar usuário: ' + error.message, 'error');
}
}

function addUser() {
openAddUserModal();
}

async function exportVolumeReport() {
try {
// // const supabase = await getSupabaseClient(); // REMOVIDO // REMOVIDO - usando MySQL
// Buscar dados de volume de leite
const { data: volumeData, error } = await supabase
    .from('volume_records')
    .select(`
        *,
        users(name, email)
    `)
    .order('created_at', { ascending: false });

if (error) throw error;

// Gerar relatório em formato PDF
await generateVolumePDF(volumeData);

showNotification('Relatório de Volume exportado com sucesso!', 'success');

// Notificação de exportação de relatório - REMOVIDA (não é crítica)
} catch (error) {
showNotification('Erro ao exportar relatório de volume', 'error');
}
}

async function exportQualityReport() {
try {
// // const supabase = await getSupabaseClient(); // REMOVIDO // REMOVIDO - usando MySQL
// Buscar dados de qualidade
const { data: qualityData, error } = await supabase
    .from('quality_records')
    .select('*')
    .order('created_at', { ascending: false });

if (error) throw error;

// Gerar relatório em formato PDF
await generateQualityPDF(qualityData);

showNotification('Relatório de Qualidade exportado com sucesso!', 'success');
} catch (error) {
showNotification('Erro ao exportar relatório de qualidade', 'error');
}
}

// Função para gerar relatório de vendas
async function generatePaymentsReport() {
try {
// // const supabase = await getSupabaseClient(); // REMOVIDO // REMOVIDO - usando MySQL
const { data: { user } } = await supabase.auth.getUser();
if (!user) throw new Error('User not authenticated');

const { data: userData, error: userError } = await supabase
    .from('users')
    .select('farm_id')
    .eq('id', user.id)
    .single();

if (userError) throw userError;

// Buscar dados de vendas
const { data: salesData, error } = await supabase
    .from('financial_records')
    .select('*')
    .eq('farm_id', userData.farm_id)
    .eq('type', 'income')
    .order('created_at', { ascending: false });

if (error) throw error;

// Gerar relatório em formato PDF
await generatePaymentsPDF(salesData);

showNotification('Relatório de Vendas gerado com sucesso!', 'success');
} catch (error) {
showNotification('Erro ao gerar relatório de vendas', 'error');
}
}

// Helper functions for PDF generation
// Definição da logo do sistema (Base64 ou URL)
// Logo do sistema em SVG Base64 para uso nos relatórios
const systemLogo = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjQwIiBoZWlnaHQ9IjQwIiByeD0iOCIgZmlsbD0iIzJhN2YyYSIvPgo8cGF0aCBkPSJNMTIgMjhIMjhWMjRIMTJWMjhaIiBmaWxsPSJ3aGl0ZSIvPgo8cGF0aCBkPSJNMTYgMjBIMjRWMTZIMTZWMjBaIiBmaWxsPSJ3aGl0ZSIvPgo8cGF0aCBkPSJNMjAgMTJWMzJNMTIgMjBIMjhNMTYgMTZIMjQiIHN0cm9rZT0id2hpdGUiIHN0cm9rZS13aWR0aD0iMiIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIi8+Cjwvc3ZnPgo=';

// Configurações de Relatórios - variável global
window.reportSettings = {
farmName: 'Fazenda',
farmLogo: null
};

// Carregar configurações salvas
async function loadReportSettings() {
try {
window.reportSettings.farmName = 'Lagoa do Mato';
window.reportSettings.farmLogo = null;
} catch (error) {
window.reportSettings.farmName = 'Lagoa do Mato';
}
}

// Função para lidar com upload da logo da fazenda
async function handleFarmLogoUpload(event) {
const file = event.target.files[0];
if (!file) return;

// Validar tipo de arquivo
if (!file.type.startsWith('image/')) {
showNotification('Por favor, selecione um arquivo de imagem válido', 'error');
return;
}

// Validar tamanho do arquivo (máx. 2MB)
if (file.size > 2 * 1024 * 1024) {
showNotification('A imagem deve ter no máximo 2MB', 'error');
return;
}

try {
// Converter para base64
const base64 = await fileToBase64(file);
window.reportSettings.farmLogo = base64;

// Atualizar preview
updateFarmLogoPreview(base64);

showNotification('Logo carregada com sucesso! Clique em "Salvar Configurações" para aplicar', 'success');
} catch (error) {
console.error('Erro ao processar logo:', error);
showNotification('Erro ao processar a imagem', 'error');
}
}

// Função para converter arquivo para base64
function fileToBase64(file) {
return new Promise((resolve, reject) => {
const reader = new FileReader();
reader.onload = () => resolve(reader.result);
reader.onerror = reject;
reader.readAsDataURL(file);
});
}

// Função para atualizar preview da logo (compatibilidade com elementos que podem não existir)
function updateFarmLogoPreview(base64Logo) {
const preview = document.getElementById('farmLogoPreview');
const placeholder = document.getElementById('farmLogoPlaceholder');
const image = document.getElementById('farmLogoImage');
const removeBtn = document.getElementById('removeFarmLogo');

// Verificar se os elementos existem antes de tentar atualizá-los
if (!preview || !placeholder || !image || !removeBtn) {
// Elementos não existem (modal foi removido), não fazer nada
return;
}

if (base64Logo) {
image.src = base64Logo;
preview.classList.remove('hidden');
placeholder.classList.add('hidden');
removeBtn.classList.remove('hidden');
} else {
image.src = '';
preview.classList.add('hidden');
placeholder.classList.remove('hidden');
removeBtn.classList.add('hidden');
}
}
// Função para remover logo da fazenda (compatibilidade)
function removeFarmLogo() {
window.reportSettings.farmLogo = null;
updateFarmLogoPreview(null);

// Limpar input file se existir
const fileInput = document.getElementById('farmLogoUpload');
if (fileInput) {
fileInput.value = '';
}

// Só mostrar notificação se a função existir
if (typeof showNotification === 'function') {
showNotification('Logo removida! Clique em "Salvar Configurações" para aplicar', 'info');
}
}

async function saveReportSettings() {
try {
// // const supabase = await getSupabaseClient(); // REMOVIDO // REMOVIDO - usando MySQL

// Usar o nome da fazenda das configurações globais ou padrão
const farmName = window.reportSettings.farmName || 'Fazenda';

const { error } = await supabase.rpc('update_user_report_settings', {
    p_report_farm_name: farmName,
    p_report_farm_logo_base64: window.reportSettings.farmLogo,
    p_report_footer_text: null,
    p_report_system_logo_base64: null
});

if (error) throw error;

if (error) throw error;

window.reportSettings.farmName = farmName;

showNotification('Configurações salvas com sucesso!', 'success');
} catch (error) {
console.error('Error saving report settings:', error);
showNotification('Erro ao salvar configurações', 'error');
}
}

// Carregar configurações ao inicializar
document.addEventListener('DOMContentLoaded', function() {
loadReportSettings();
});

// Função para traduzir milking_type de inglês para português
function getMilkingTypeInPortuguese(milkingType) {
const translation = {
'morning': 'Manhã',
'afternoon': 'Tarde',
'evening': 'Noite',
'night': 'Madrugada'
};
return translation[milkingType] || milkingType;
}

// Função para traduzir type de financial_records de inglês para português
function getFinancialTypeInPortuguese(type) {
const translation = {
'income': 'Receita',
'expense': 'Despesa'
};
return translation[type] || type;
}

// Função para gerar email a partir do nome
async function generateEmailFromName(name) {
try {
const cleanName = name.toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '') // Remove acentos
    .replace(/[^a-z0-9]/g, '') // Remove caracteres especiais
    .replace(/\s+/g, '.'); // Substitui espaços por pontos

// Obter nome da fazenda
const farmName = await getFarmName();
const cleanFarmName = farmName.toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '') // Remove acentos
    .replace(/[^a-z0-9]/g, '') // Remove caracteres especiais
    .replace(/\s+/g, ''); // Remove espaços

// Obter próximo número sequencial para esta fazenda
const nextNumber = await getNextUserNumber(farmName);

return `${cleanName}${nextNumber}@${cleanFarmName}.lactech.com`;
} catch (error) {
console.error('Erro ao gerar email:', error);
// Fallback simples
const cleanName = name.toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/[^a-z0-9]/g, '')
    .replace(/\s+/g, '.');
return `${cleanName}@lactech.com`;
}
}

// Função para obter próximo número sequencial de usuário
async function getNextUserNumber(farmName) {
try {
// // const supabase = await getSupabaseClient(); // REMOVIDO // REMOVIDO - usando MySQL
const { data: { user } } = await supabase.auth.getUser();
if (!user) return '001';

// Obter farm_id do usuário atual
const { data: userData, error: userError } = await supabase
    .from('users')
    .select('farm_id')
    .eq('id', user.id)
    .order('created_at', { ascending: true })
    .single();

if (userError || !userData?.farm_id) return '001';

// Contar usuários existentes na mesma fazenda
const { data: existingUsers, error: countError } = await supabase
    .from('users')
    .select('id')
    .eq('farm_id', userData.farm_id);

if (countError) return '001';

// Próximo número será o total + 1
const nextNumber = (existingUsers?.length || 0) + 1;
return nextNumber.toString().padStart(3, '0');

} catch (error) {
console.error('Erro ao obter próximo número:', error);
return '001';
}
}

// Função para gerar senha temporária
function generateTempPassword() {
const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
let password = '';
for (let i = 0; i < 8; i++) {
password += chars.charAt(Math.floor(Math.random() * chars.length));
}
return password;
}

// Função para prévia do relatório
function previewReport() {
// Gerar um relatório de exemplo com as configurações atuais
const sampleData = [
{
    production_date: new Date().toISOString(),
    volume_liters: 150.5,
    milking_type: 'morning',
    notes: 'Registro de exemplo',
    users: { name: 'Funcionário Exemplo' }
}
];

generateVolumePDF(sampleData, true); // true indica que é uma prévia
}


// Toggle password visibility function for user forms
function toggleUserPasswordVisibility(inputId, buttonId) {
const passwordInput = document.getElementById(inputId);
const toggleButton = document.getElementById(buttonId);

if (passwordInput && toggleButton) {
if (passwordInput.type === 'password') {
    passwordInput.type = 'text';
    toggleButton.innerHTML = `
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path>
        </svg>
    `;
} else {
    passwordInput.type = 'password';
    toggleButton.innerHTML = `
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
        </svg>
    `;
}
}
}



// Upload profile photo to Supabase Storage
async function uploadProfilePhoto(file, userId) {
try {
// // const supabase = await getSupabaseClient(); // REMOVIDO // REMOVIDO - usando MySQL

// Get current authenticated user
const { data: { user }, error: authError } = await supabase.auth.getUser();

if (authError || !user) {
    console.error('DEBUG: Falha na autenticação:', authError);
    throw new Error('Usuário não autenticado');
}

// Get current user's farm_id for organizing photos by farm

const { data: managerData, error: managerError } = await supabase
    .from('users')
    .select('farm_id')
    .eq('id', user.id)
    .single();



if (managerError || !managerData?.farm_id) {
    console.error('DEBUG: Fazenda não encontrada:', managerError);
    throw new Error('Farm not found');
}

const fileExt = file.name.split('.').pop();
// Ensure we always use the target userId, never fallback to current user
if (!userId) {
    throw new Error('userId é obrigatório para upload de foto');
}

// Create unique filename with more specific naming
const timestamp = Date.now();
const randomId = Math.random().toString(36).substr(2, 9);
const fileName = `user_${userId}_${timestamp}_${randomId}.${fileExt}`;
const filePath = `farm_${managerData.farm_id}/${fileName}`;

const { data, error } = await supabase.storage
    .from('profile-photos')
    .upload(filePath, file, {
        cacheControl: '3600',
        upsert: false
    });



if (error) {
    console.error('DEBUG: Erro no upload:', {
        message: error.message,
        statusCode: error.statusCode,
        error: error
    });
    throw error;
}



const { data: { publicUrl } } = supabase.storage
    .from('profile-photos')
    .getPublicUrl(filePath);


return publicUrl;
} catch (error) {
console.error('DEBUG: Erro final:', {
    message: error.message,
    stack: error.stack,
    error: error
});
throw error;
}
}

// Function to refresh users list without reloading photos
async function refreshUsersListOnly() {

try {
// // const supabase = await getSupabaseClient(); // REMOVIDO // REMOVIDO - usando MySQL
const { data: { user } } = await supabase.auth.getUser();
if (!user) {
    return;
}

// Get current user's farm_id
const { data: userData, error: userError } = await supabase
    .from('users')
    .select('farm_id')
    .eq('id', user.id)
    .single();

if (userError || !userData?.farm_id) {
    return;
}

// Get all users from the same farm (without photos to avoid cache issues)
const { data: allUsers, error } = await supabase
    .from('users')
    .select('id, name, email, role, whatsapp, is_active, created_at')
    .eq('farm_id', userData.farm_id)
    .order('created_at', { ascending: false });

if (error) {
    return;
}

// Update counts only
if (allUsers) {
    const employeesCount = allUsers.filter(u => u.role === 'funcionario').length;
    const veterinariansCount = allUsers.filter(u => u.role === 'veterinario').length;
    const managersCount = allUsers.filter(u => u.role === 'gerente').length;
    const totalUsers = allUsers.length;
    
    document.getElementById('totalUsers').textContent = totalUsers;
    document.getElementById('employeesCount').textContent = employeesCount;
    document.getElementById('veterinariansCount').textContent = veterinariansCount;
    document.getElementById('managersCount').textContent = managersCount;
}

} catch (error) {
console.error('DEBUG: Erro no refreshUsersListOnly:', error);
}
}

// Function to update a specific user's photo in the list
async function updateUserPhotoInList(userId, newPhotoUrl) {


try {
const photoElement = document.getElementById(`user-photo-${userId}`);
const iconElement = document.getElementById(`user-icon-${userId}`);

if (photoElement && newPhotoUrl) {
    // Update the photo with cache buster
    const cacheBuster = Date.now();
    photoElement.src = newPhotoUrl + '?cb=' + cacheBuster;
    photoElement.style.display = 'block';
    
    if (iconElement) {
        iconElement.style.display = 'none';
    }
    

} else if (iconElement && !newPhotoUrl) {
    // Show icon if no photo
    if (photoElement) {
        photoElement.style.display = 'none';
    }
    iconElement.style.display = 'flex';
    

}

} catch (error) {
console.error('DEBUG: Erro ao atualizar foto na lista:', error);
}
}
// Debug function to check all users photos
async function debugCheckAllPhotos() {

try {
const { data: { user } } = await supabase.auth.getUser();
if (!user) {
    return;
}

// Get current user's farm_id
const { data: userData, error: userError } = await supabase
    .from('users')
    .select('farm_id')
    .eq('id', user.id)
    .single();

if (userError || !userData?.farm_id) {
    return;
}

// Get all users from the same farm
const { data: allUsers, error } = await supabase
    .from('users')
    .select('id, name, email, role, profile_photo_url, created_at')
    .eq('farm_id', userData.farm_id)
    .order('created_at', { ascending: false });

if (error) {
    return;
}



} catch (error) {
console.error('DEBUG: Erro na verificação:', error);
}
}

// Sign out function
async function signOut() {
// Notificação de logout iniciado - REMOVIDA (não é crítica)

showLogoutConfirmationModal();
}

// Função para mostrar modal de confirmação de logout
function showLogoutConfirmationModal() {
// Criar modal se não existir
let modal = document.getElementById('logoutConfirmationModal');
if (!modal) {
modal = document.createElement('div');
modal.id = 'logoutConfirmationModal';
modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[9999]';
modal.innerHTML = `
    <div class="bg-white  rounded-2xl p-6 max-w-md w-full mx-4 shadow-2xl" onclick="event.stopPropagation()">
        <div class="text-center">
            <!-- Ícone de logout -->
            <div class="w-16 h-16 bg-red-100 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-red-600 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                </svg>
            </div>
            
            <!-- Título -->
            <h3 class="text-xl font-bold text-gray-900  mb-2">
                Confirmar Saída
            </h3>
            
            <!-- Mensagem -->
            <p class="text-gray-600  mb-6">
                Tem certeza que deseja sair do sistema?
            </p>
            
            <!-- Botões -->
            <div class="flex space-x-3">
                <button onclick="closeLogoutModal()" 
                        class="flex-1 px-4 py-3 border border-gray-300 border-gray-300 text-gray-700  font-medium rounded-xl hover:bg-gray-50 hover:bg-gray-50 transition-all">
                    Cancelar
                </button>
                <button onclick="confirmLogout()" 
                        class="flex-1 px-4 py-3 bg-red-600 hover:bg-red-700 text-white font-medium rounded-xl transition-all">
                    Sair
                </button>
            </div>
        </div>
    </div>
`;
document.body.appendChild(modal);
}

// Adicionar evento de clique no overlay para fechar
modal.addEventListener('click', function(e) {
if (e.target === modal) {
    closeLogoutModal();
}
});

// Adicionar evento de tecla ESC para fechar
const escHandler = function(e) {
if (e.key === 'Escape') {
    closeLogoutModal();
    document.removeEventListener('keydown', escHandler);
}
};
document.addEventListener('keydown', escHandler);

// Mostrar modal
modal.style.display = 'flex';
}

// Função para fechar modal de logout
function closeLogoutModal() {
console.log('🔒 Fechando modal de logout...');
const modal = document.getElementById('logoutConfirmationModal');
if (modal) {
modal.style.display = 'none';
modal.style.visibility = 'hidden';
modal.style.opacity = '0';
modal.style.pointerEvents = 'none';
console.log('✅ Modal de logout fechado');
} else {
console.error('❌ Modal de logout não encontrado');
}
}

// Função para confirmar logout
async function confirmLogout() {
try {
console.log('🚪 Iniciando logout...');
closeLogoutModal();

// Mostrar loading
showNotification('Saindo do sistema...', 'info');

// Notificação de logout em progresso - REMOVIDA (não é crítica)

// Limpar atualizações em tempo real
cleanupRealtimeUpdates();

clearUserSession(); // Use new clearUserSession function
// // const supabase = await getSupabaseClient(); // REMOVIDO // REMOVIDO - usando MySQL
await supabase.auth.signOut();
console.log('✅ Logout realizado com sucesso');

// Notificação de logout concluído - REMOVIDA (não é crítica)

safeRedirect('index.php'); // Use new safeRedirect function
} catch (error) {
console.error('❌ Erro no logout:', error);
clearUserSession();
safeRedirect('index.php');
}
}

// Função para carregar dados da conta secundária existente
async function loadSecondaryAccountData() {
try {
// // const supabase = await getSupabaseClient(); // REMOVIDO // REMOVIDO - usando MySQL
// Get current user data
const { data: { user } } = await supabase.auth.getUser();
if (!user) {
    console.error('Usuário não autenticado');
    return;
}

// Get user details from users table
const { data: userData, error: userError } = await supabase
    .from('users')
    .select('*')
    .eq('id', user.id)
    .single();
    
if (userError) {
    console.error('Erro ao buscar dados do usuário:', userError);
    return;
}

// Verificar se existe uma conta secundária usando a tabela secondary_accounts
let secondaryAccountRelation = null;
try {
    const { data: relationData, error: relationError } = await supabase
        .from('secondary_accounts')
        .select('secondary_account_id')
        .eq('primary_account_id', user.id)
        .maybeSingle();
        
    if (relationError) {
        console.error('Erro ao verificar relação de conta secundária:', relationError);
    } else {
        secondaryAccountRelation = relationData;
    }
} catch (error) {
    console.error('Erro ao acessar tabela secondary_accounts:', error);
}

// Se não encontrou na tabela de relações, tenta o método antigo
if (!secondaryAccountRelation) {
    try {
        // Check if secondary account exists by email
        const { data: secondaryAccount, error: secondaryError } = await supabase
            .from('users')
            .select('*')
            .eq('email', userData.email)
            .eq('farm_id', userData.farm_id)
            .neq('id', userData.id)
            .maybeSingle();
        
        if (secondaryError) {
            console.error('Erro ao verificar conta secundária:', secondaryError);
        }
        
        if (secondaryAccount) {
            // Preencher o formulário com os dados da conta secundária
            const nameField = document.getElementById('secondaryAccountName');
            const roleField = document.getElementById('secondaryAccountRole');
            const activeField = document.getElementById('secondaryAccountActive');
            
            if (nameField) nameField.value = secondaryAccount.name;
            if (roleField) roleField.value = secondaryAccount.role;
            if (activeField) activeField.checked = secondaryAccount.is_active;
            
            // Atualizar o status da conta secundária
            const noAccountDiv = document.getElementById('noSecondaryAccount');
            const hasAccountDiv = document.getElementById('hasSecondaryAccount');
            const nameDisplay = document.getElementById('secondaryAccountNameDisplay');
            const switchBtn = document.getElementById('switchAccountBtn');
            
            if (noAccountDiv) noAccountDiv.style.display = 'none';
            if (hasAccountDiv) hasAccountDiv.style.display = 'block';
            if (nameDisplay) nameDisplay.textContent = secondaryAccount.name;
            if (switchBtn) switchBtn.disabled = false;
            
            // Criar a relação na tabela secondary_accounts se não existir
            try {
                const { error: insertError } = await supabase
                    .from('secondary_accounts')
                    .insert([
                        {
                            primary_account_id: user.id,
                            secondary_account_id: secondaryAccount.id
                        }
                    ]);
                    
                if (insertError && !insertError.message.includes('duplicate key')) {
                    console.error('Erro ao criar relação de conta secundária:', insertError);
                }
            } catch (error) {
                console.error('Erro ao criar relação:', error);
            }
        } else {
            // Limpar o formulário
            const nameField = document.getElementById('secondaryAccountName');
            const roleField = document.getElementById('secondaryAccountRole');
            const activeField = document.getElementById('secondaryAccountActive');
            
            if (nameField) nameField.value = '';
            if (roleField) roleField.value = 'funcionario';
            if (activeField) activeField.checked = true;
            
            // Atualizar o status da conta secundária
            const noAccountDiv = document.getElementById('noSecondaryAccount');
            const hasAccountDiv = document.getElementById('hasSecondaryAccount');
            const switchBtn = document.getElementById('switchAccountBtn');
            
            if (noAccountDiv) noAccountDiv.style.display = 'block';
            if (hasAccountDiv) hasAccountDiv.style.display = 'none';
            if (switchBtn) switchBtn.disabled = true;
        }
    } catch (error) {
        console.error('Erro ao verificar conta secundária:', error);
    }
} else {
    try {
        // Buscar os dados da conta secundária usando o ID da relação
        const { data: secondaryAccount, error: accountError } = await supabase
            .from('users')
            .select('*')
            .eq('id', secondaryAccountRelation.secondary_account_id)
            .single();
            
        if (accountError) {
            console.error('Erro ao buscar dados da conta secundária:', accountError);
            return;
        }
        
        // Preencher o formulário com os dados da conta secundária
        const nameField = document.getElementById('secondaryAccountName');
        const roleField = document.getElementById('secondaryAccountRole');
        const activeField = document.getElementById('secondaryAccountActive');
        
        if (nameField) nameField.value = secondaryAccount.name;
        if (roleField) roleField.value = secondaryAccount.role;
        if (activeField) activeField.checked = secondaryAccount.is_active;
        
        // Atualizar o status da conta secundária
        const noAccountDiv = document.getElementById('noSecondaryAccount');
        const hasAccountDiv = document.getElementById('hasSecondaryAccount');
        const nameDisplay = document.getElementById('secondaryAccountNameDisplay');
        const switchBtn = document.getElementById('switchAccountBtn');
        
        if (noAccountDiv) noAccountDiv.style.display = 'none';
        if (hasAccountDiv) hasAccountDiv.style.display = 'block';
        if (nameDisplay) nameDisplay.textContent = secondaryAccount.name;
        if (switchBtn) switchBtn.disabled = false;
    } catch (error) {
        console.error('Erro ao buscar dados da conta secundária:', error);
    }
}
} catch (error) {
console.error('Erro ao carregar dados da conta secundária:', error);
}
}

// Função para salvar a conta secundária
async function saveSecondaryAccount(event) {
event.preventDefault();

try {
// Mostrar indicador de carregamento
const submitBtn = event.target.querySelector('button[type="submit"]');
const originalBtnText = submitBtn.innerHTML;
submitBtn.innerHTML = '<svg class="animate-spin h-5 w-5 mr-2" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Salvando...';
submitBtn.disabled = true;

// Get current user data
const { data: { user } } = await supabase.auth.getUser();
if (!user) {
    console.error('Usuário não autenticado');
    return;
}

// Get user details from users table
const { data: userData, error: userError } = await supabase
    .from('users')
    .select('*')
    .eq('id', user.id)
    .single();
    
if (userError) {
    console.error('Erro ao buscar dados do usuário:', userError);
    return;
}

// Get form data
const secondaryRole = document.getElementById('secondaryAccountRole').value;
const roleSuffix = secondaryRole === 'veterinario' ? ' (Veterinário)' : ' (Funcionário)';
const secondaryName = document.getElementById('secondaryAccountName').value.trim() || userData.name + roleSuffix;
const isActive = document.getElementById('secondaryAccountActive').checked;

// Gerar email único para a conta secundária
const secondaryEmail = userData.email + (secondaryRole === 'veterinario' ? '.vet' : '.func');

// Verificar se já existe uma conta secundária com o mesmo email modificado
const { data: existingAccount, error: checkError } = await supabase
    .from('users')
    .select('*')
    .eq('email', secondaryEmail)
    .eq('farm_id', userData.farm_id)
    .maybeSingle();

let secondaryAccount;

if (!checkError && existingAccount) {
    // Update existing account
    const { data: updatedAccount, error: updateError } = await supabase
        .from('users')
        .update({
            name: secondaryName,
            role: secondaryRole,
            is_active: isActive
        })
        .eq('id', existingAccount.id)
        .select()
        .single();
        
    if (updateError) {
        console.error('Erro ao atualizar conta secundária:', updateError);
        showNotification('Erro ao atualizar conta secundária. Por favor, tente novamente.', 'error');
        return;
    }
    
    secondaryAccount = updatedAccount;
    showNotification('Conta secundária atualizada com sucesso!', 'success');
    
    // Verificar se já existe uma relação na tabela secondary_accounts
    const { data: existingRelation, error: relationError } = await supabase
        .from('secondary_accounts')
        .select('*')
        .eq('primary_account_id', user.id)
        .eq('secondary_account_id', secondaryAccount.id)
        .single();
        
    if (relationError && relationError.code !== 'PGRST116') {
        console.error('Erro ao verificar relação de conta secundária:', relationError);
    }
    
    // Se não existir relação, criar uma
    if (!existingRelation) {
        const { error: insertError } = await supabase
            .from('secondary_accounts')
            .insert([
                {
                    primary_account_id: user.id,
                    secondary_account_id: secondaryAccount.id
                }
            ]);
            
        if (insertError) {
            console.error('Erro ao criar relação de conta secundária:', insertError);
        }
    }
} else {
    // Create new secondary account
    
    // Verificar se já existe um usuário com o mesmo email e farm_id
    const { data: existingUsers, error: existingError } = await supabase
        .from('users')
        .select('*')
        .eq('email', secondaryEmail)
        .eq('farm_id', userData.farm_id)
        .neq('id', userData.id);
        
    if (existingError) {
        console.error('Erro ao verificar usuários existentes:', existingError);
    } else {

        
        // Se já existir um usuário secundário, atualizar em vez de criar
        if (existingUsers && existingUsers.length > 0) {
            const { data: updatedAccount, error: updateError } = await supabase
                .from('users')
                .update({
                    name: secondaryName,
                    role: secondaryRole,
                    is_active: isActive
                })
                .eq('id', existingUsers[0].id)
                .select()
                .single();
                
            if (updateError) {
                console.error('Erro ao atualizar conta secundária existente:', updateError);
                showNotification('Erro ao atualizar conta secundária. Por favor, tente novamente.', 'error');
                return;
            }
            
            secondaryAccount = updatedAccount;
            showNotification('Conta secundária atualizada com sucesso!', 'success');
            
            // Verificar se já existe uma relação na tabela secondary_accounts
            const { data: existingRelation, error: relationError } = await supabase
                .from('secondary_accounts')
                .select('*')
                .eq('primary_account_id', user.id)
                .eq('secondary_account_id', secondaryAccount.id)
                .single();
                
            if (relationError && relationError.code !== 'PGRST116') {
                console.error('Erro ao verificar relação de conta secundária:', relationError);
            }
            
            // Se não existir relação, criar uma
            if (!existingRelation) {
                const { error: insertError } = await supabase
                    .from('secondary_accounts')
                    .insert([
                        {
                            primary_account_id: user.id,
                            secondary_account_id: secondaryAccount.id
                        }
                    ]);
                    
                if (insertError) {
                    console.error('Erro ao criar relação de conta secundária:', insertError);
                }
            }
        } else {
            // Create new secondary account
            const { data: newAccount, error: createError } = await supabase
                .from('users')
                .insert([
                    {
                        farm_id: userData.farm_id,
                        name: secondaryName,
                        email: secondaryEmail,
                        role: secondaryRole,
                        whatsapp: userData.whatsapp,
                        is_active: isActive,
                        profile_photo_url: userData.profile_photo_url
                    }
                ])
                .select()
                .single();
                
            if (createError) {
                console.error('Erro ao criar nova conta secundária:', createError);
                showNotification('Erro ao criar nova conta secundária. Por favor, tente novamente.', 'error');
                return;
            }
            
            secondaryAccount = newAccount;
            showNotification('Nova conta secundária criada com sucesso!', 'success');
            
            // Create relation in secondary_accounts table
            const { error: insertError } = await supabase
                .from('secondary_accounts')
                .insert([
                    {
                        primary_account_id: user.id,
                        secondary_account_id: secondaryAccount.id
                    }
                ]);
                
            if (insertError) {
                console.error('Erro ao criar relação de conta secundária:', insertError);
            }
        }
    }
}
} catch (error) {
console.error('Erro ao salvar conta secundária:', error);
showNotification('Erro ao salvar conta secundária. Por favor, tente novamente.', 'error');
} finally {
// Restaurar botão de salvar
const submitBtn = event.target.querySelector('button[type="submit"]');
submitBtn.innerHTML = originalBtnText;
submitBtn.disabled = false;
}
}

// Função para alternar a visibilidade do painel de contas
function toggleAccountsPanel() {
const panel = document.getElementById('accountsPanel');
if (panel.classList.contains('hidden')) {
panel.classList.remove('hidden');
loadAccountCards();
} else {
panel.classList.add('hidden');
}
}

// Função para carregar os cards de contas
async function loadAccountCards() {
try {
// Get current user data
const { data: { user } } = await supabase.auth.getUser();
if (!user) {
    console.error('Usuário não autenticado');
    return;
}

// Get user details from users table
const { data: userData, error: userError } = await supabase
    .from('users')
    .select('*')
    .eq('id', user.id)
    .single();
    
if (userError) {
    console.error('Erro ao buscar dados do usuário:', userError);
    return;
}

// Limpar o container de cards
const cardsContainer = document.getElementById('accountCards');
cardsContainer.innerHTML = '';

// Adicionar card da conta principal
const primaryCard = document.createElement('div');
primaryCard.className = 'bg-white border border-blue-100 rounded-xl p-4 shadow-sm hover:shadow-md transition-all';
primaryCard.innerHTML = `
    <div class="flex items-center space-x-3">
        <div class="flex-shrink-0">
            <div class="w-12 h-12 rounded-full bg-forest-100 flex items-center justify-center text-forest-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
            </div>
        </div>
        <div class="flex-1">
            <h5 class="font-medium text-blue-900">${userData.name}</h5>
            <p class="text-sm text-blue-600 capitalize">${userData.role}</p>
        </div>
        <div>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-forest-100 text-forest-800">
                Atual
            </span>
        </div>
    </div>
`;
cardsContainer.appendChild(primaryCard);

// Buscar contas secundárias na tabela secondary_accounts
const { data: secondaryRelations, error: relError } = await supabase
    .from('secondary_accounts')
    .select('secondary_account_id')
    .eq('primary_account_id', user.id);
    
if (relError) {
    console.error('Erro ao buscar relações de contas secundárias:', relError);
    return;
}

if (secondaryRelations && secondaryRelations.length > 0) {
    // Buscar detalhes de cada conta secundária
    for (const relation of secondaryRelations) {
        const { data: secondaryAccount, error: accountError } = await supabase
            .from('users')
            .select('*')
            .eq('id', relation.secondary_account_id)
            .single();
            
        if (accountError) {
            console.error('Erro ao buscar detalhes da conta secundária:', accountError);
            continue;
        }
        
        // Criar card para a conta secundária
        const secondaryCard = document.createElement('div');
        secondaryCard.className = 'bg-white border border-blue-100 rounded-xl p-4 shadow-sm hover:shadow-md transition-all';
        secondaryCard.innerHTML = `
            <div class="flex items-center space-x-3">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                </div>
                <div class="flex-1">
                    <h5 class="font-medium text-blue-900">${secondaryAccount.name}</h5>
                    <p class="text-sm text-blue-600 capitalize">${secondaryAccount.role}</p>
                </div>
                <div>
                    <button onclick="switchToAccount('${secondaryAccount.id}');" class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-all">
                        Alternar
                    </button>
                </div>
            </div>
        `;
        cardsContainer.appendChild(secondaryCard);
    }
} else {
    // Se não encontrou contas secundárias, mostrar mensagem
    const noAccountsMessage = document.createElement('div');
    noAccountsMessage.className = 'text-center p-4 text-blue-600';
    noAccountsMessage.innerHTML = `
        <p>Você ainda não possui contas secundárias configuradas.</p>
        <button onclick="showSecondaryAccountForm();" class="mt-2 px-3 py-1 bg-forest-500 hover:bg-forest-600 text-white text-sm font-medium rounded-lg transition-all">
            Configurar Conta
        </button>
    `;
    cardsContainer.appendChild(noAccountsMessage);
}
} catch (error) {
console.error('Erro ao carregar cards de contas:', error);
}
}

// Função para alternar para uma conta específica
async function switchToAccount(accountId) {
try {
// Get current user data
const { data: { user } } = await supabase.auth.getUser();
if (!user) {
    console.error('Usuário não autenticado');
    return;
}

// Get user details from users table
const { data: userData, error: userError } = await supabase
    .from('users')
    .select('*')
    .eq('id', user.id)
    .single();
    
if (userError) {
    console.error('Erro ao buscar dados do usuário:', userError);
    return;
}

// Store current role in session storage
sessionStorage.setItem('previous_role', userData.role);

// Get secondary account details
const { data: secondaryAccount, error: secondaryError } = await supabase
    .from('users')
    .select('*')
    .eq('id', accountId)
    .single();

if (secondaryError) {
    console.error('Erro ao buscar conta secundária:', secondaryError);
    return;
}

if (!secondaryAccount.is_active) {
    showNotification('Esta conta está desativada. Por favor, ative-a nas configurações.', 'warning');
    showSecondaryAccountForm();
    return;
}

// Secondary account exists and is active, switch to it


// Store current user session data
const currentSession = {
    id: userData.id,
    email: userData.email,
    name: userData.name,
    user_type: userData.role,
    farm_id: userData.farm_id,
    farm_name: sessionStorage.getItem('farm_name') || ''
};

// Store secondary account session data
const secondarySession = {
    id: secondaryAccount.id,
    email: secondaryAccount.email,
    name: secondaryAccount.name,
    user_type: secondaryAccount.role,
    farm_id: secondaryAccount.farm_id,
    farm_name: sessionStorage.getItem('farm_name') || ''
};
// Save current session for later
sessionStorage.setItem('primary_account', JSON.stringify(currentSession));

// Set new session
sessionStorage.setItem('user', JSON.stringify(secondarySession));

// Redirect to appropriate page based on role
if (secondaryAccount.role === 'funcionario') {
    window.location.href = 'funcionario.php';
} else if (secondaryAccount.role === 'veterinario') {
    window.location.href = 'veterinario.php';
} else {
    showNotification('Conta secundária encontrada, mas o tipo não é reconhecido.', 'warning');
}
} catch (error) {
console.error('Erro ao alternar conta:', error);
showNotification('Ocorreu um erro ao alternar para a conta secundária.', 'error');
}
}

// Function to switch between manager and inseminator accounts (mantido para compatibilidade)
async function switchToSecondaryAccount() {
try {
// Get current user data
const { data: { user } } = await supabase.auth.getUser();
if (!user) {
    console.error('Usuário não autenticado');
    return;
}

const { data: secondaryRelations, error: relError } = await supabase
    .from('secondary_accounts')
    .select('secondary_account_id')
    .eq('primary_account_id', user.id);
    
if (relError || !secondaryRelations || secondaryRelations.length === 0) {
    showNotification('Você precisa configurar uma conta secundária primeiro.', 'warning');
    showSecondaryAccountForm();
    return;
}

if (secondaryRelations.length > 1) {
    toggleAccountsPanel();
    return;
}

switchToAccount(secondaryRelations[0].secondary_account_id);
} catch (error) {
console.error('Erro ao alternar conta:', error);
showNotification('Ocorreu um erro ao alternar para a conta secundária.', 'error');
}
}


async function checkIfSecondaryAccount() {
try {
const { data: { user } } = await supabase.auth.getUser();
if (!user) return false;

const { data: relation, error } = await supabase
    .from('secondary_accounts')
    .select('primary_account_id')
    .eq('secondary_account_id', user.id)
    .single();

if (error || !relation) {
    return false;
}

return true;
} catch (error) {
console.error('Erro ao verificar se é conta secundária:', error);
return false;
}
}

async function showAlterSecondaryAccountSection() {
const isSecondary = await checkIfSecondaryAccount();
const alterSection = document.getElementById('alterSecondaryAccountSection');

if (alterSection) {
if (isSecondary) {
    alterSection.style.display = 'block';

} else {
    alterSection.style.display = 'none';

}
}
}

// Função para mostrar formulário de alteração de conta secundária
function showAlterSecondaryAccountForm() {
const form = document.getElementById('secondaryAccountForm');
const alterBtn = document.getElementById('alterSecondaryAccountBtn');

if (form) {
form.style.display = 'block';
// Mudar o texto do botão para indicar que é uma alteração
const submitBtn = form.querySelector('button[type="submit"]');
if (submitBtn) {
    submitBtn.innerHTML = 'Alterar Conta Secundária';
}

// Carregar dados atuais da conta
loadCurrentSecondaryAccountData();
}

if (alterBtn) {
alterBtn.style.display = 'none';
}
}

// Função para carregar dados atuais da conta secundária
async function loadCurrentSecondaryAccountData() {
try {
const { data: { user } } = await supabase.auth.getUser();
if (!user) return;

// Buscar dados atuais do usuário
const { data: userData, error } = await supabase
    .from('users')
    .select('name, email, role, is_active')
    .eq('id', user.id)
    .single();

if (error) {
    console.error('Erro ao carregar dados atuais:', error);
    return;
}

// Preencher formulário com dados atuais
const nameField = document.getElementById('secondaryAccountName');
const roleField = document.getElementById('secondaryAccountRole');
const activeField = document.getElementById('secondaryAccountActive');

if (nameField) nameField.value = userData.name || '';
if (roleField) roleField.value = userData.role || 'funcionario';
if (activeField) activeField.checked = userData.is_active || false;



} catch (error) {
console.error('Erro ao carregar dados atuais:', error);
}
}

// Função para salvar alterações da conta secundária
async function saveSecondaryAccountAlteration(event) {
event.preventDefault();

try {
const { data: { user } } = await supabase.auth.getUser();
if (!user) {
    showNotification('Usuário não autenticado', 'error');
    return;
}

// Obter dados do formulário
const name = document.getElementById('secondaryAccountName').value.trim();
const role = document.getElementById('secondaryAccountRole').value;
const isActive = document.getElementById('secondaryAccountActive').checked;

if (!name) {
    showNotification('Por favor, informe o nome da conta.', 'warning');
    return;
}

// Atualizar dados do usuário
const { data: updatedUser, error: updateError } = await supabase
    .from('users')
    .update({
        name: name,
        role: role,
        is_active: isActive
    })
    .eq('id', user.id)
    .select()
    .single();

if (updateError) {
    console.error('Erro ao atualizar conta:', updateError);
    showNotification('Erro ao atualizar a conta secundária.', 'error');
    return;
}

// Atualizar dados da sessão
const sessionData = {
    id: updatedUser.id,
    email: updatedUser.email,
    name: updatedUser.name,
    role: updatedUser.role,
    farm_id: updatedUser.farm_id,
    is_active: updatedUser.is_active
};

localStorage.setItem('userData', JSON.stringify(sessionData));

// Esconder formulário
hideSecondaryAccountForm();

// Mostrar modal de sucesso
showSecondaryAccountSuccessModal(updatedUser, 'alteracao');

// Recarregar dados da página
await loadUserProfile();
await setManagerName();



} catch (error) {
console.error('Erro ao alterar conta secundária:', error);
showNotification('Ocorreu um erro ao alterar a conta secundária.', 'error');
}
}

// Modificar a função showSecondaryAccountSuccessModal para suportar alterações
function showSecondaryAccountSuccessModal(account, action = 'criacao') {
const modal = document.getElementById('secondaryAccountSuccessModal');
const title = document.getElementById('successModalTitle');
const message = document.getElementById('successModalMessage');
const name = document.getElementById('successAccountName');
const role = document.getElementById('successAccountRole');
const email = document.getElementById('successAccountEmail');

if (modal && title && message && name && role && email) {
if (action === 'alteracao') {
    title.textContent = 'Conta Secundária Alterada!';
    message.textContent = 'Suas informações foram atualizadas com sucesso.';
} else {
    title.textContent = 'Conta Secundária Criada!';
    message.textContent = 'Sua conta secundária foi configurada com sucesso.';
}

name.textContent = account.name || 'Não informado';
role.textContent = account.role || 'Não informado';
email.textContent = account.email || 'Não informado';

modal.classList.remove('hidden');

// Auto-close após 5 segundos
setTimeout(() => {
    closeSecondaryAccountSuccessModal();
}, 5000);
}
}

// Função para lidar com submissão do formulário (criação ou alteração)
async function handleSecondaryAccountSubmit(event) {
event.preventDefault();

try {
// Verificar se é uma conta secundária
const isSecondary = await checkIfSecondaryAccount();

if (isSecondary) {
    // Se é conta secundária, usar função de alteração
    await saveSecondaryAccountAlteration(event);
} else {
    // Se não é conta secundária, usar função de criação
    await saveSecondaryAccount(event);
}

} catch (error) {
console.error('Erro ao processar submissão do formulário:', error);
showNotification('Ocorreu um erro ao processar a solicitação.', 'error');
}
}





// LIMPEZA DAS FUNÇÕES DE CARREGAMENTO
// Remove console.logs e corrige problemas de carregamento

// Função limpa para carregar dados do usuário
async function loadUserProfileClean() {
try {
const whatsappElement = document.getElementById('profileWhatsApp');

if (!whatsappElement) {
    return;
}

// Primeiro tentar obter da sessão local
const sessionData = localStorage.getItem('userData') || sessionStorage.getItem('userData');

if (sessionData) {
    try {
        const user = JSON.parse(sessionData);
        
        // Definir dados do perfil da sessão
        document.getElementById('profileEmail2').textContent = user.email || '';
        const whatsappValue = user.whatsapp || user.phone || 'Não informado';
        document.getElementById('profileWhatsApp').textContent = whatsappValue;
        return;
    } catch (error) {
        // Continuar para fallback
    }
}

// Fallback para Supabase Auth
const { data: { user } } = await supabase.auth.getUser();

if (!user) {
    document.getElementById('profileEmail2').textContent = 'Não logado';
    document.getElementById('profileWhatsApp').textContent = 'Não informado';
    return;
}

// Buscar dados do usuário no banco
const { data: userData, error } = await supabase
    .from('users')
    .select('name, email, whatsapp')
    .eq('id', user.id)
    .single();

// Se usuário não encontrado, mostrar erro
if (error && error.code === 'PGRST116') {
    document.getElementById('profileEmail2').textContent = user.email || '';
    document.getElementById('profileWhatsApp').textContent = 'Usuário não encontrado';
    return;
}

if (error) {
    document.getElementById('profileEmail2').textContent = user.email || '';
    document.getElementById('profileWhatsApp').textContent = 'Erro ao carregar';
    return;
}

// Atualizar elementos do perfil
if (userData) {
    const email = userData.email || user.email || '';
    const whatsapp = userData.whatsapp || 'Não informado';
    
    document.getElementById('profileEmail2').textContent = email;
    document.getElementById('profileWhatsApp').textContent = whatsapp;
} else {
    document.getElementById('profileEmail2').textContent = user.email || '';
    document.getElementById('profileWhatsApp').textContent = 'Não informado';
}

// Atualizar foto do perfil se disponível
if (userData?.profile_photo_url) {
    updateProfilePhotoDisplay(userData.profile_photo_url + '?t=' + Date.now());
}
} catch (error) {
document.getElementById('profileEmail2').textContent = 'Erro';
document.getElementById('profileWhatsApp').textContent = 'Erro';
}
}

// Função limpa para definir nome da fazenda
async function setFarmNameClean() {
try {
const farmName = await getFarmName();
const farmNameElement = document.getElementById('farmNameHeader');
if (farmNameElement) {
    farmNameElement.textContent = farmName;
}
} catch (error) {
const farmNameElement = document.getElementById('farmNameHeader');
if (farmNameElement) {
    farmNameElement.textContent = 'Minha Fazenda';
}
}
}

// Função limpa para definir nome do gerente
async function setManagerNameClean() {
try {
const managerName = await getManagerName();
const farmName = await getFarmName();

const finalManagerName = managerName || 'Gerente';
const finalFarmName = farmName || 'Minha Fazenda';

// Extract formal name for welcome message
const formalName = extractFormalName(finalManagerName);

const elements = [
    'profileName',
    'profileFullName'
];

elements.forEach(id => {
    const element = document.getElementById(id);
    if (element) {
        element.textContent = finalManagerName;
    }
});

// Set header and welcome message with formal name
const headerElement = document.getElementById('managerName');
const welcomeElement = document.getElementById('managerWelcome');
if (headerElement) {
    headerElement.textContent = formalName;
}
if (welcomeElement) {
    welcomeElement.textContent = formalName;
}

const farmElement = document.getElementById('profileFarmName');
if (farmElement) {
    farmElement.textContent = finalFarmName;
}
} catch (error) {
// Definir valores padrão em caso de erro
const defaultName = 'Gerente';
const defaultFarm = 'Minha Fazenda';

const elements = [
    'profileName', 
    'profileFullName'
];

elements.forEach(id => {
    const element = document.getElementById(id);
    if (element) {
        element.textContent = defaultName;
    }
});

// Set header and welcome message with formal name
const headerElement = document.getElementById('managerName');
const welcomeElement = document.getElementById('managerWelcome');
if (headerElement) {
    headerElement.textContent = defaultName;
}
if (welcomeElement) {
    welcomeElement.textContent = defaultName;
}

const farmElement = document.getElementById('profileFarmName');
if (farmElement) {
    farmElement.textContent = defaultFarm;
}
}
}

// Substituir funções originais pelas versões limpas
window.loadUserProfile = loadUserProfileClean;
window.setFarmName = setFarmNameClean;
window.setManagerName = setManagerNameClean;

// Funções do gerente substituídas pelas versões limpas

// REMOÇÃO COMPLETA DE TODOS OS CONSOLE.LOGS PARA PROTEGER O BANCO
// Substituir console.log por função vazia para evitar sobrecarga
const originalConsoleLog = console.log;
const originalConsoleError = console.error;

// TEMPORARIAMENTE HABILITADO PARA DEBUG
// console.log = function() {
//     // Não fazer nada - silenciar completamente
// };

// console.error = function() {
//     // Não fazer nada - silenciar completamente
// };

window.restoreConsoleLogs = function() {

console.error = originalConsoleError;
};

// Função para verificar se há muitas requisições
window.checkDatabaseRequests = function() {
// Monitorar requisições ao banco
const originalFetch = window.fetch;
let requestCount = 0;

window.fetch = function(...args) {
requestCount++;
if (requestCount > 100) {
}
return originalFetch.apply(this, args);
};

// Reset contador a cada 5 segundos
setInterval(() => {
requestCount = 0;
}, 5000);
};

// Ativar proteção contra muitas requisições
checkDatabaseRequests();



// ===== FUNÇÕES PARA FOTO DO GERENTE =====

// Variável global para armazenar a foto selecionada
let selectedManagerPhoto = null;

// Função para alternar modo de edição da foto do gerente
function toggleManagerPhotoEdit() {
const viewMode = document.getElementById('managerPhotoViewMode');
const editMode = document.getElementById('managerPhotoEditMode');
const editBtn = document.getElementById('editManagerPhotoBtn');

if (viewMode.classList.contains('hidden')) {
// Voltar para modo visualização
viewMode.classList.remove('hidden');
editMode.classList.add('hidden');
editBtn.textContent = 'Alterar Foto';
selectedManagerPhoto = null;
} else {
// Ir para modo edição
viewMode.classList.add('hidden');
editMode.classList.remove('hidden');
editBtn.textContent = 'Cancelar';
}
}

// Função para cancelar edição da foto do gerente
function cancelManagerPhotoEdit() {
toggleManagerPhotoEdit();
// Limpar preview
const previewImage = document.getElementById('managerPhotoPreviewImage');
const previewPlaceholder = document.getElementById('managerPhotoPreviewPlaceholder');
if (previewImage) previewImage.classList.add('hidden');
if (previewPlaceholder) previewPlaceholder.classList.remove('hidden');
selectedManagerPhoto = null;
}

// Função para lidar com upload da foto do gerente
function handleManagerPhotoUpload(event) {
const file = event.target.files[0];
if (!file) return;

// Validar tipo de arquivo
const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
if (!allowedTypes.includes(file.type)) {
showNotification('Formato de arquivo não suportado. Use PNG, JPG, JPEG, GIF ou WEBP.', 'error');
return;
}

// Validar tamanho (5MB)
const maxSize = 5 * 1024 * 1024; // 5MB
if (file.size > maxSize) {
showNotification('Arquivo muito grande. Tamanho máximo: 5MB.', 'error');
return;
}

// Armazenar arquivo selecionado
selectedManagerPhoto = file;

// Mostrar preview
const reader = new FileReader();
reader.onload = function(e) {
const previewImage = document.getElementById('managerPhotoPreviewImage');
const previewPlaceholder = document.getElementById('managerPhotoPreviewPlaceholder');

if (previewImage && previewPlaceholder) {
    previewImage.src = e.target.result;
    previewImage.classList.remove('hidden');
    previewPlaceholder.classList.add('hidden');
}
};
reader.readAsDataURL(file);
}

// Função para salvar foto do gerente
async function saveManagerPhoto() {
if (!selectedManagerPhoto) {
showNotification('Selecione uma foto primeiro.', 'error');
return;
}

try {
// Obter dados do usuário atual
const { data: { user } } = await supabase.auth.getUser();
if (!user) {
    showNotification('Usuário não autenticado.', 'error');
    return;
}

// Upload da foto usando função específica para gerente
const photoUrl = await uploadManagerProfilePhoto(selectedManagerPhoto, user.id);

if (photoUrl) {
    // Atualizar foto no banco de dados
    const { error } = await supabase
        .from('users')
        .update({ profile_photo_url: photoUrl })
        .eq('id', user.id);
    
    if (error) throw error;
    
    // Atualizar interface
    updateManagerPhotoDisplay(photoUrl);
    
    // Atualizar lista de usuários
    await loadUsersData();
    
    // Voltar para modo visualização
    toggleManagerPhotoEdit();
    
    showNotification('Foto de perfil atualizada com sucesso!', 'success');
}

} catch (error) {
console.error('Erro ao salvar foto do gerente:', error);
showNotification('Erro ao salvar foto de perfil.', 'error');
}
}

// Função específica para upload de foto do gerente
async function uploadManagerProfilePhoto(file, userId) {
try {
// Gerar nome único para o arquivo
const timestamp = Date.now();
const randomId = Math.random().toString(36).substr(2, 9);
const fileExtension = file.name.split('.').pop();
const fileName = `manager_${userId}_${timestamp}_${randomId}.${fileExtension}`;

// Upload para Supabase Storage
const { data, error } = await supabase.storage
    .from('profile-photos')
    .upload(fileName, file);

if (error) throw error;

// Obter URL pública
const { data: urlData } = supabase.storage
    .from('profile-photos')
    .getPublicUrl(fileName);

return urlData.publicUrl;

} catch (error) {
console.error('Erro no upload da foto do gerente:', error);
throw new Error('Erro ao fazer upload da foto.');
}
}

// Função para atualizar exibição da foto do gerente
function updateManagerPhotoDisplay(photoUrl) {
const photoImage = document.getElementById('managerPhotoImage');
const photoPlaceholder = document.getElementById('managerPhotoPlaceholder');

if (photoUrl && photoImage && photoPlaceholder) {
// Adicionar timestamp para evitar cache
photoImage.src = photoUrl + '?t=' + Date.now();
photoImage.classList.remove('hidden');
photoPlaceholder.classList.add('hidden');
} else if (photoPlaceholder) {
// Mostrar placeholder se não há foto
if (photoImage) photoImage.classList.add('hidden');
photoPlaceholder.classList.remove('hidden');
}

// Atualizar também a foto no header
updateHeaderProfilePhoto(photoUrl);

// Atualizar também a foto no modal de perfil
updateModalProfilePhoto(photoUrl);
}

// Função para atualizar foto no header
function updateHeaderProfilePhoto(photoUrl) {
console.log('🖼️ Atualizando foto do header:', photoUrl);

const headerPhoto = document.getElementById('headerProfilePhoto');
const headerIcon = document.getElementById('headerProfileIcon');

if (headerPhoto && headerIcon) {
if (photoUrl) {
    // Adicionar timestamp para evitar cache
    const photoUrlWithTimestamp = photoUrl + '?t=' + Date.now();
    headerPhoto.src = photoUrlWithTimestamp;
    headerPhoto.style.display = 'block';
    headerPhoto.style.visibility = 'visible';
    headerPhoto.classList.remove('hidden');
    headerIcon.style.display = 'none';
    headerIcon.style.visibility = 'hidden';
    headerIcon.classList.add('hidden');
    console.log('✅ Foto do header atualizada com sucesso');
} else {
    // Mostrar ícone padrão se não há foto
    headerPhoto.style.display = 'none';
    headerPhoto.style.visibility = 'hidden';
    headerPhoto.classList.add('hidden');
    headerIcon.style.display = 'block';
    headerIcon.style.visibility = 'visible';
    headerIcon.classList.remove('hidden');
    console.log('✅ Ícone padrão do header exibido');
}
} else {
console.error('❌ Elementos do header não encontrados');
}
}

// Função para atualizar foto no modal de perfil
function updateModalProfilePhoto(photoUrl) {
const modalPhoto = document.getElementById('modalProfilePhoto');
const modalIcon = document.getElementById('modalProfileIcon');

if (modalPhoto && modalIcon) {
if (photoUrl) {
    // Adicionar timestamp para evitar cache
    const photoUrlWithTimestamp = photoUrl + '?t=' + Date.now();
    modalPhoto.src = photoUrlWithTimestamp;
    modalPhoto.classList.remove('hidden');
    modalIcon.classList.add('hidden');
} else {
    // Mostrar ícone padrão se não há foto
    modalPhoto.classList.add('hidden');
    modalIcon.classList.remove('hidden');
}
}
}
// Função para carregar foto do gerente ao abrir o modal
async function loadManagerPhoto() {
try {
// // const supabase = await getSupabaseClient(); // REMOVIDO // REMOVIDO - usando MySQL
const { data: { user } } = await supabase.auth.getUser();
if (!user) return;

// Buscar dados do usuário incluindo foto
const { data: userData, error } = await supabase
    .from('users')
    .select('profile_photo_url')
    .eq('id', user.id)
    .single();

if (error) {
    console.error('Erro ao carregar foto do gerente:', error);
    return;
}

// Atualizar exibição da foto
if (userData && userData.profile_photo_url) {
updateManagerPhotoDisplay(userData.profile_photo_url);
}

} catch (error) {
console.error('Erro ao carregar foto do gerente:', error);
}
}

// Modificar função openProfileModal para carregar foto
const originalOpenProfileModal = window.openProfileModal;
window.openProfileModal = function() {
originalOpenProfileModal();
// Carregar foto do gerente apenas quando o modal for aberto manualmente
setTimeout(() => {
loadManagerPhoto();
}, 100);
};

// Função para carregar foto no header ao inicializar a página
async function loadHeaderPhoto() {
try {
console.log('🖼️ Carregando foto do header...');

// // const supabase = await getSupabaseClient(); // REMOVIDO // REMOVIDO - usando MySQL
const { data: { user } } = await supabase.auth.getUser();
if (!user) {
    console.log('❌ Usuário não autenticado');
    return;
}

// Buscar dados do usuário incluindo foto
const { data: userData, error } = await supabase
    .from('users')
    .select('profile_photo_url')
    .eq('id', user.id)
    .single();

if (error) {
    console.error('❌ Erro ao carregar foto do header:', error);
    return;
}

console.log('📋 Dados do usuário carregados:', userData);

// Atualizar foto no header
updateHeaderProfilePhoto(userData.profile_photo_url);

} catch (error) {
console.error('❌ Erro ao carregar foto do header:', error);
}
}

// Função já é chamada no DOMContentLoaded principal

// ==================== FUNÇÕES DA ABA DE RELATÓRIOS ====================

// Variáveis globais para a aba de relatórios
let reportTabSettings = {
farmName: '',
farmLogo: null
};

// Função para carregar configurações na aba de relatórios
async function loadReportTabSettings() {
try {
// // const supabase = await getSupabaseClient(); // REMOVIDO // REMOVIDO - usando MySQL
const { data: { user } } = await supabase.auth.getUser();
if (!user) return;

// Buscar dados do usuário incluindo farm_id
const { data: userData, error: userError } = await supabase
    .from('users')
    .select('report_farm_name, report_farm_logo_base64, farm_id')
    .eq('id', user.id)
    .single();

if (userError) throw userError;

// Se não tem nome da fazenda configurado, buscar do banco
let farmName = userData.report_farm_name;
if (!farmName && userData.farm_id) {
    const { data: farmData, error: farmError } = await supabase
        .from('farms')
        .select('name')
        .eq('id', userData.farm_id)
        .single();

    if (!farmError && farmData) {
        farmName = farmData.name;
    }
}

// Se ainda não tem nome, usar padrão
if (!farmName) {
    farmName = 'Fazenda';
}

reportTabSettings.farmName = farmName;
reportTabSettings.farmLogo = userData.report_farm_logo_base64;

// Atualizar campos
document.getElementById('reportFarmNameTab').value = farmName;
updateFarmLogoPreviewTab(reportTabSettings.farmLogo);

// Corrigir duplicação da logo
setTimeout(() => {
    fixLogoDuplication();
}, 100);


} catch (error) {
console.error('Erro ao carregar configurações:', error);
// Em caso de erro, usar nome padrão
document.getElementById('reportFarmNameTab').value = 'Fazenda';
}
}

// Função para upload da logo na aba
async function handleFarmLogoUploadTab(event) {
const file = event.target.files[0];
if (!file) return;

if (!file.type.startsWith('image/')) {
showNotification('Por favor, selecione um arquivo de imagem válido', 'error');
return;
}

if (file.size > 2 * 1024 * 1024) {
showNotification('A imagem deve ter no máximo 2MB', 'error');
return;
}

try {
const base64 = await fileToBase64(file);
reportTabSettings.farmLogo = base64;
updateFarmLogoPreviewTab(base64);
showNotification('Logo carregada com sucesso! Clique em "Salvar Configurações" para aplicar', 'success');
} catch (error) {
console.error('Erro ao processar logo:', error);
showNotification('Erro ao processar a imagem', 'error');
}
}

// Função para atualizar preview da logo na aba
function updateFarmLogoPreviewTab(base64Logo) {
const preview = document.getElementById('farmLogoPreviewTab');
const placeholder = document.getElementById('farmLogoPlaceholderTab');
const image = document.getElementById('farmLogoImageTab');
const removeBtn = document.getElementById('removeFarmLogoTab');

if (base64Logo) {
image.src = base64Logo;
preview.classList.remove('hidden');
placeholder.classList.add('hidden');
removeBtn.classList.remove('hidden');

// Forçar ocultação do placeholder via CSS
placeholder.style.display = 'none';
placeholder.style.visibility = 'hidden';
placeholder.style.opacity = '0';
placeholder.style.position = 'absolute';
placeholder.style.zIndex = '-1';
placeholder.style.pointerEvents = 'none';
placeholder.style.width = '0';
placeholder.style.height = '0';
placeholder.style.overflow = 'hidden';

// Garantir que o botão remover seja visível
removeBtn.style.display = 'flex';
removeBtn.style.visibility = 'visible';
removeBtn.style.opacity = '1';
removeBtn.style.position = 'relative';
removeBtn.style.zIndex = 'auto';
removeBtn.style.pointerEvents = 'auto';
removeBtn.style.width = 'auto';
removeBtn.style.height = 'auto';
removeBtn.style.overflow = 'visible';
} else {
image.src = '';
preview.classList.add('hidden');
placeholder.classList.remove('hidden');
removeBtn.classList.add('hidden');

// Forçar exibição do placeholder via CSS
placeholder.style.display = 'flex';
placeholder.style.visibility = 'visible';
placeholder.style.opacity = '1';
placeholder.style.position = 'relative';
placeholder.style.zIndex = 'auto';
placeholder.style.pointerEvents = 'auto';
placeholder.style.width = 'auto';
placeholder.style.height = 'auto';
placeholder.style.overflow = 'visible';

// Forçar ocultação completa do botão remover via CSS
removeBtn.style.display = 'none';
removeBtn.style.visibility = 'hidden';
removeBtn.style.opacity = '0';
removeBtn.style.position = 'absolute';
removeBtn.style.zIndex = '-1';
removeBtn.style.pointerEvents = 'none';
removeBtn.style.width = '0';
removeBtn.style.height = '0';
removeBtn.style.overflow = 'hidden';
}
}

// Função para corrigir duplicação da logo na inicialização
function fixLogoDuplication() {
const preview = document.getElementById('farmLogoPreviewTab');
const placeholder = document.getElementById('farmLogoPlaceholderTab');
const removeBtn = document.getElementById('removeFarmLogoTab');

if (preview && placeholder && removeBtn) {
if (preview.classList.contains('hidden')) {
    // Se preview está oculta, mostrar placeholder e ocultar botão remover
    placeholder.style.display = 'flex';
    placeholder.style.visibility = 'visible';
    placeholder.style.opacity = '1';
    placeholder.style.position = 'relative';
    placeholder.style.zIndex = 'auto';
    placeholder.style.pointerEvents = 'auto';
    placeholder.style.width = 'auto';
    placeholder.style.height = 'auto';
    placeholder.style.overflow = 'visible';
    
    // Ocultar botão remover completamente
    removeBtn.style.display = 'none';
    removeBtn.style.visibility = 'hidden';
    removeBtn.style.opacity = '0';
    removeBtn.style.position = 'absolute';
    removeBtn.style.zIndex = '-1';
    removeBtn.style.pointerEvents = 'none';
    removeBtn.style.width = '0';
    removeBtn.style.height = '0';
    removeBtn.style.overflow = 'hidden';
} else {
    // Se preview está visível, ocultar placeholder e mostrar botão remover
    placeholder.style.display = 'none';
    placeholder.style.visibility = 'hidden';
    placeholder.style.opacity = '0';
    placeholder.style.position = 'absolute';
    placeholder.style.zIndex = '-1';
    placeholder.style.pointerEvents = 'none';
    placeholder.style.width = '0';
    placeholder.style.height = '0';
    placeholder.style.overflow = 'hidden';
    
    // Mostrar botão remover
    removeBtn.style.display = 'flex';
    removeBtn.style.visibility = 'visible';
    removeBtn.style.opacity = '1';
    removeBtn.style.position = 'relative';
    removeBtn.style.zIndex = 'auto';
    removeBtn.style.pointerEvents = 'auto';
    removeBtn.style.width = 'auto';
    removeBtn.style.height = 'auto';
    removeBtn.style.overflow = 'visible';
}
}
}

// Função para remover logo da aba
function removeFarmLogoTab() {
reportTabSettings.farmLogo = null;
updateFarmLogoPreviewTab(null);
document.getElementById('farmLogoUploadTab').value = '';
showNotification('Logo removida! Clique em "Salvar Configurações" para aplicar', 'info');
}

// Função para salvar configurações da aba
async function saveReportSettingsTab() {
try {
const farmName = document.getElementById('reportFarmNameTab').value || 'Fazenda';

const { error } = await supabase.rpc('update_user_report_settings', {
    p_report_farm_name: farmName,
    p_report_farm_logo_base64: reportTabSettings.farmLogo,
    p_report_footer_text: null,
    p_report_system_logo_base64: null
});

if (error) throw error;

reportTabSettings.farmName = farmName;
showNotification('Configurações salvas com sucesso!', 'success');

// Sincronizar com as configurações do modal
if (window.reportSettings) {
    window.reportSettings.farmName = farmName;
    window.reportSettings.farmLogo = reportTabSettings.farmLogo;
}

} catch (error) {
console.error('Erro ao salvar configurações:', error);
showNotification('Erro ao salvar configurações', 'error');
}
}

// Função para carregar estatísticas dos relatórios
async function loadReportStats() {
try {
const { data: { user } } = await supabase.auth.getUser();
if (!user) return;

const { data: userData } = await supabase
    .from('users')
    .select('farm_id')
    .eq('id', user.id)
    .single();

if (!userData?.farm_id) return;

const hoje = new Date().toISOString().split('T')[0];
const inicioMes = new Date();
inicioMes.setDate(1);
const seteDiasAtras = new Date();
seteDiasAtras.setDate(seteDiasAtras.getDate() - 6);

// Produção de hoje
const { data: producaoHoje } = await supabase
    .from('volume_records')
    .select('volume_liters')
    .eq('farm_id', userData.farm_id)
    .eq('production_date', hoje);

let volumeHoje = 0;
if (producaoHoje) {
    volumeHoje = producaoHoje.reduce((sum, item) => sum + parseFloat(item.volume_liters || 0), 0);
}

// Média semanal
const { data: producaoSemana } = await supabase
    .from('volume_records')
    .select('volume_liters, production_date')
    .eq('farm_id', userData.farm_id)
    .gte('production_date', seteDiasAtras.toISOString().split('T')[0]);

let mediaSemana = 0;
if (producaoSemana?.length > 0) {
    const volumesPorDia = {};
    producaoSemana.forEach(item => {
        if (!volumesPorDia[item.production_date]) {
            volumesPorDia[item.production_date] = 0;
        }
        volumesPorDia[item.production_date] += parseFloat(item.volume_liters || 0);
    });
    
    const totalDias = Object.keys(volumesPorDia).length;
    const totalVolume = Object.values(volumesPorDia).reduce((sum, vol) => sum + vol, 0);
    mediaSemana = totalDias > 0 ? totalVolume / totalDias : 0;
}

// Total do mês
const { data: producaoMes } = await supabase
    .from('volume_records')
    .select('volume_liters')
    .eq('farm_id', userData.farm_id)
    .gte('production_date', inicioMes.toISOString().split('T')[0]);

let totalMes = 0;
let registrosMes = 0;
if (producaoMes) {
    totalMes = producaoMes.reduce((sum, item) => sum + parseFloat(item.volume_liters || 0), 0);
    registrosMes = producaoMes.length;
}

// Funcionários ativos
const { data: funcionarios } = await supabase
    .from('users')
    .select('id')
    .eq('farm_id', userData.farm_id)
    .eq('role', 'funcionario');

const funcionariosAtivos = funcionarios?.length || 0;

// Atualizar elementos
document.getElementById('reportTodayVolume').textContent = volumeHoje.toFixed(1) + ' L';
document.getElementById('reportWeekAverage').textContent = mediaSemana.toFixed(1) + ' L';
document.getElementById('reportMonthTotal').textContent = totalMes.toFixed(1) + ' L';
document.getElementById('reportMonthRecords').textContent = registrosMes.toString();
document.getElementById('reportActiveEmployees').textContent = funcionariosAtivos.toString();

// Carregar lista de funcionários no select
const selectEmployee = document.getElementById('reportEmployee');
if (selectEmployee && funcionarios) {
    selectEmployee.innerHTML = '<option value="">Todos os funcionários</option>';
    
    for (const func of funcionarios) {
        const { data: userData } = await supabase
            .from('users')
            .select('name')
            .eq('id', func.id)
            .single();
        
        if (userData?.name) {
            const option = document.createElement('option');
            option.value = func.id;
            option.textContent = userData.name;
            selectEmployee.appendChild(option);
        }
    }
}

} catch (error) {
console.error('Erro ao carregar estatísticas:', error);
}
}

// Função para exportar Excel
async function exportExcelReport() {
try {
const startDate = document.getElementById('reportStartDate').value;
const endDate = document.getElementById('reportEndDate').value;
const employeeId = document.getElementById('reportEmployee').value;

if (!startDate || !endDate) {
    showNotification('Por favor, selecione as datas inicial e final', 'warning');
    return;
}

const { data: { user } } = await supabase.auth.getUser();
if (!user) return;

const { data: userData } = await supabase
    .from('users')
    .select('farm_id')
    .eq('id', user.id)
    .single();

if (!userData?.farm_id) return;

// Buscar dados
let query = supabase
    .from('volume_records')
    .select(`
        production_date,
        shift,
        volume_liters,
        temperature,
        observations,
        created_at,
        users!inner(name)
    `)
    .eq('farm_id', userData.farm_id)
    .gte('production_date', startDate)
    .lte('production_date', endDate)
    .order('production_date', { ascending: true })
    .order('created_at', { ascending: true });

if (employeeId) {
    query = query.eq('user_id', employeeId);
}

const { data: dadosExcel, error } = await query;

if (error) {
    throw error;
}

if (!dadosExcel || dadosExcel.length === 0) {
    showNotification('Nenhum dado encontrado para o período selecionado', 'info');
    return;
}
// Obter informações da fazenda para o cabeçalho
const farmName = reportTabSettings.farmName || 'Fazenda';
const dataInicio = new Date(startDate).toLocaleDateString('pt-BR');
const dataFim = new Date(endDate).toLocaleDateString('pt-BR');
const dataGeracao = new Date().toLocaleString('pt-BR');

// Calcular estatísticas
const totalVolume = dadosExcel.reduce((sum, item) => sum + (parseFloat(item.volume_liters) || 0), 0);
const mediaVolume = dadosExcel.length > 0 ? totalVolume / dadosExcel.length : 0;
const totalRegistros = dadosExcel.length;

// Criar dados para Excel com design limpo
const excelData = [
    // Cabeçalho Principal
    [`RELATÓRIO DE PRODUÇÃO DE LEITE - ${farmName.toUpperCase()}`],
    [''],
    ['INFORMAÇÕES DO RELATÓRIO'],
    ['Período:', `${dataInicio} até ${dataFim}`],
    ['Data de Geração:', dataGeracao],
    ['Total de Registros:', totalRegistros],
    ['Volume Total Produzido:', `${totalVolume.toFixed(2)} L`],
    ['Média por Registro:', `${mediaVolume.toFixed(2)} L`],
    [''],
    // Cabeçalho da Tabela
    ['Data', 'Funcionário', 'Turno', 'Volume (L)', 'Temperatura (°C)', 'Observações', 'Data/Hora Registro']
];

dadosExcel.forEach(item => {
    const data = new Date(item.production_date).toLocaleDateString('pt-BR');
    const turno = {
        'manha': 'Manhã',
        'tarde': 'Tarde', 
        'noite': 'Noite'
    }[item.shift] || item.shift;
    const dataHora = new Date(item.created_at).toLocaleString('pt-BR');

    excelData.push([
        data,
        item.users?.name || 'N/A',
        turno,
        parseFloat(item.volume_liters) || 0,
        item.temperature ? `${item.temperature}°C` : '',
        item.observations || '',
        dataHora
    ]);
});

// Criar workbook e worksheet
const ws = XLSX.utils.aoa_to_sheet(excelData);
const wb = XLSX.utils.book_new();
XLSX.utils.book_append_sheet(wb, ws, 'Produção de Leite');

// Definir larguras das colunas melhoradas
ws['!cols'] = [
    { width: 15 }, // Data
    { width: 25 }, // Funcionário
    { width: 15 }, // Turno
    { width: 15 }, // Volume
    { width: 18 }, // Temperatura
    { width: 35 }, // Observações
    { width: 22 }  // Data/Hora Registro
];

// Estilizar cabeçalho principal
if (ws['A1']) {
    ws['A1'].s = {
        font: { bold: true, color: { rgb: "FFFFFF" } },
        fill: { fgColor: { rgb: "2563EB" } },
        alignment: { horizontal: "center" }
    };
}

// Merge das células do título
ws['!merges'] = [
    { s: { r: 0, c: 0 }, e: { r: 0, c: 6 } }, // Título principal
    { s: { r: 2, c: 0 }, e: { r: 2, c: 6 } }  // Subtítulo informações
];

// Estilizar linha de informações
for (let i = 3; i <= 8; i++) {
    const cellA = `A${i}`;
    const cellB = `B${i}`;
    if (ws[cellA]) {
        ws[cellA].s = {
            font: { bold: true, color: { rgb: "1F2937" } },
            fill: { fgColor: { rgb: "F3F4F6" } }
        };
    }
    if (ws[cellB]) {
        ws[cellB].s = {
            font: { color: { rgb: "374151" } },
            fill: { fgColor: { rgb: "F9FAFB" } }
        };
    }
}

// Estilizar cabeçalho da tabela
for (let col = 0; col < 7; col++) {
    const cell = ws[XLSX.utils.encode_cell({ r: 9, c: col })];
    if (cell) {
        cell.s = {
            font: { bold: true, color: { rgb: "FFFFFF" } },
            fill: { fgColor: { rgb: "059669" } },
            alignment: { horizontal: "center" },
            border: {
                top: { style: "thin", color: { rgb: "000000" } },
                bottom: { style: "thin", color: { rgb: "000000" } },
                left: { style: "thin", color: { rgb: "000000" } },
                right: { style: "thin", color: { rgb: "000000" } }
            }
        };
    }
}

// Estilizar dados da tabela
for (let row = 10; row < excelData.length; row++) {
    for (let col = 0; col < 7; col++) {
        const cell = ws[XLSX.utils.encode_cell({ r: row, c: col })];
        if (cell) {
            const isEvenRow = (row - 10) % 2 === 0;
            cell.s = {
                fill: { fgColor: { rgb: isEvenRow ? "F9FAFB" : "FFFFFF" } },
                border: {
                    top: { style: "thin", color: { rgb: "E5E7EB" } },
                    bottom: { style: "thin", color: { rgb: "E5E7EB" } },
                    left: { style: "thin", color: { rgb: "E5E7EB" } },
                    right: { style: "thin", color: { rgb: "E5E7EB" } }
                },
                alignment: { 
                    horizontal: col === 3 ? "right" : "left", // Volume alinhado à direita
                    vertical: "center"
                }
            };
            
            // Destacar volumes acima da média
            if (col === 3 && parseFloat(cell.v) > mediaVolume) {
                cell.s.font = { bold: true, color: { rgb: "059669" } };
            }
        }
    }
}

// Download
const fileName = `Relatório_${farmName}_${startDate}_${endDate}.xlsx`;
XLSX.writeFile(wb, fileName);

showNotification('Arquivo Excel exportado com sucesso!', 'success');

} catch (error) {
console.error('Erro ao exportar Excel:', error);

showNotification('Erro ao exportar relatório: ' + error.message, 'error');
}
}

// Função para exportar PDF
async function exportPDFReport() {
try {
const startDate = document.getElementById('reportStartDate').value;
const endDate = document.getElementById('reportEndDate').value;
const employeeId = document.getElementById('reportEmployee').value;

if (!startDate || !endDate) {
    showNotification('Por favor, selecione as datas inicial e final', 'warning');
    return;
}

const { data: { user } } = await supabase.auth.getUser();
if (!user) return;

const { data: userData } = await supabase
    .from('users')
    .select('farm_id')
    .eq('id', user.id)
    .single();

if (!userData?.farm_id) return;

let query = supabase
    .from('volume_records')
    .select(`
        production_date,
        shift,
        volume_liters,
        temperature,
        observations,
        created_at,
        users!inner(name)
    `)
    .eq('farm_id', userData.farm_id)
    .gte('production_date', startDate)
    .lte('production_date', endDate)
    .order('production_date', { ascending: true });

if (employeeId) {
    query = query.eq('user_id', employeeId);
}

const { data: dadosPDF, error } = await query;

if (error) throw error;

if (!dadosPDF || dadosPDF.length === 0) {
    showNotification('Nenhum dado encontrado para o período selecionado', 'info');
    return;
}

// Gerar PDF usando a função existente
generateVolumePDF(dadosPDF, false);

} catch (error) {
console.error('Erro ao exportar PDF:', error);
showNotification('Erro ao exportar PDF: ' + error.message, 'error');
}
}

// Resetar senha da conta
async function resetAccountPassword(userId, userName) {
if (!confirm(`Tem certeza que deseja resetar a senha de ${userName}?`)) {
return;
}

try {
const newPassword = generateTempPassword();

// Atualizar apenas a senha temporária na tabela
// Nota: O usuário precisará usar a função de recuperação de senha do Supabase
// ou o administrador do sistema precisará resetar via painel admin
const { error: updateError } = await supabase
    .from('users')
    .update({ temp_password: newPassword })
    .eq('id', userId);

if (updateError) throw updateError;

showNotification('Nova senha temporária gerada! O usuário deve usar a recuperação de senha do sistema.', 'warning');
showTempPasswordModal(userName, '', newPassword);

} catch (error) {
console.error('Erro ao resetar senha:', error);
showNotification('Erro ao resetar senha: ' + error.message, 'error');
}
}

// Event listener para formulário de conta secundária
const createSecondaryAccountForm = document.getElementById('createSecondaryAccountForm');
if (createSecondaryAccountForm) {
// Função para lidar com o submit
const handleSecondaryAccountSubmit = async function(e) {
e.preventDefault();
const formData = new FormData(this);
await createSecondaryAccount(formData);
};

// Remover listener anterior se existir
createSecondaryAccountForm.removeEventListener('submit', handleSecondaryAccountSubmit);
createSecondaryAccountForm.addEventListener('submit', handleSecondaryAccountSubmit);
}

// Funções para gerenciar contas secundárias
function toggleSecondaryAccountForm() {
const form = document.getElementById('secondaryAccountForm');
if (form) {
form.classList.toggle('hidden');

if (!form.classList.contains('hidden')) {
    // Preencher dados automaticamente
    fillSecondaryAccountForm();
}
}
}

function cancelSecondaryAccountForm() {
const form = document.getElementById('secondaryAccountForm');
if (form) {
form.classList.add('hidden');
}
}

// Preencher formulário com dados do gerente atual
async function fillSecondaryAccountForm() {
try {
// // const supabase = await getSupabaseClient(); // REMOVIDO // REMOVIDO - usando MySQL
const { data: { user } } = await supabase.auth.getUser();

if (user) {
    // Buscar dados do usuário atual (gerente principal)
    const { data: userData, error } = await supabase
        .from('users')
        .select('name, whatsapp, role')
        .eq('id', user.id)
        .eq('role', 'gerente') // Garantir que é o gerente principal
        .single();
        
    if (!error && userData) {
        // Preencher campos ocultos
        document.getElementById('secondaryAccountName').value = userData.name;
        document.getElementById('secondaryAccountWhatsApp').value = userData.whatsapp || '';
        document.getElementById('secondaryAccountEmail').value = user.email;
        
        // Preencher campos de exibição
        document.getElementById('displayName').textContent = userData.name;
        document.getElementById('displayWhatsApp').textContent = userData.whatsapp || 'Não informado';
        document.getElementById('displayEmail').textContent = user.email;
        
        console.log('✅ Dados do gerente principal carregados:', {
            name: userData.name,
            email: user.email,
            whatsapp: userData.whatsapp,
            role: userData.role
        });
    } else {
        console.error('Erro ao buscar dados do gerente principal:', error);
        showNotification('Erro ao carregar dados da conta principal', 'error');
    }
}
} catch (error) {
console.error('Erro ao preencher formulário:', error);
}
}

// Criar conta secundária
async function createSecondaryAccount(formData) {
try {
// // const supabase = await getSupabaseClient(); // REMOVIDO // REMOVIDO - usando MySQL
const { data: { user } } = await supabase.auth.getUser();

if (!user) throw new Error('Usuário não autenticado');

// Obter dados do gerente atual
const { data: managerData, error: managerError } = await supabase
    .from('users')
    .select('farm_id, name, whatsapp')
    .eq('id', user.id)
    .single();
    
if (managerError) throw managerError;

const accountType = formData.get('account_type');
const email = user.email; // Sempre usar o email do gerente
const name = formData.get('name') || managerData.name;
const whatsapp = formData.get('whatsapp') || managerData.whatsapp;

// Verificar se já existe uma conta secundária deste tipo
const { data: existingAccounts, error: checkError } = await supabase
    .from('users')
    .select('id, role')
    .eq('email', email)
    .eq('farm_id', managerData.farm_id);
    
if (checkError) throw checkError;

// Verificar se já existe uma conta com o mesmo role
const hasAccountType = existingAccounts && existingAccounts.some(account => account.role === accountType);

if (hasAccountType) {
    const roleText = accountType === 'funcionario' ? 'de funcionário' : 'de veterinário';
    throw new Error(`Você já possui uma conta ${roleText}`);
}

// Criar conta secundária usando RPC
const { data: result, error } = await supabase.rpc('create_farm_user', {
    p_user_id: authData.user.id,
    p_email: email,
    p_name: name,
    p_whatsapp: whatsapp,
    p_role: accountType,
    p_farm_id: managerData.farm_id,
    p_profile_photo_url: null
});

if (error) throw error;

if (!result.success) {
    throw new Error(result.error || 'Falha ao criar conta secundária');
}

showNotification(`Conta secundária ${accountType === 'funcionario' ? 'de funcionário' : 'de veterinário'} criada com sucesso!`, 'success');

// Fechar formulário e recarregar lista
cancelSecondaryAccountForm();
loadSecondaryAccounts();

} catch (error) {
console.error('Erro ao criar conta secundária:', error);
showNotification('Erro ao criar conta secundária: ' + error.message, 'error');
}
}

// Verificar se já existe conta secundária do tipo selecionado
async function checkExistingSecondaryAccount(accountType) {
if (!accountType) {
const messageDiv = document.getElementById('existingAccountMessage');
if (messageDiv) {
    messageDiv.classList.add('hidden');
}
return;
}

try {
// // const supabase = await getSupabaseClient(); // REMOVIDO // REMOVIDO - usando MySQL
const { data: { user } } = await supabase.auth.getUser();

if (!user) return;

// Buscar contas existentes com o mesmo email e role específico
const { data: existingAccounts, error } = await supabase
    .from('users')
    .select('role')
    .eq('email', user.email)
    .eq('role', accountType);
    
if (error) throw error;

const hasAccountType = existingAccounts && existingAccounts.length > 0;

const messageDiv = document.getElementById('existingAccountMessage');
if (messageDiv) {
    if (hasAccountType) {
        const roleText = accountType === 'funcionario' ? 'de funcionário' : 'de veterinário';
        messageDiv.innerHTML = `<span class="text-red-600">⚠️ Você já possui uma conta ${roleText}</span>`;
        messageDiv.classList.remove('hidden');
    } else {
        messageDiv.classList.add('hidden');
    }
}

} catch (error) {
console.error('Erro ao verificar conta existente:', error);
}
}

// Carregar contas secundárias
async function loadSecondaryAccounts() {
try {
// // const supabase = await getSupabaseClient(); // REMOVIDO // REMOVIDO - usando MySQL
const { data: { user } } = await supabase.auth.getUser();

if (!user) return;

// Buscar contas secundárias (mesmo email, diferentes roles)
const { data: secondaryAccounts, error } = await supabase
    .from('users')
    .select('id, name, email, role, whatsapp, is_active, created_at')
    .eq('email', user.email) // Mesmo email do gerente
    .neq('role', 'gerente') // Excluir a conta principal (gerente)
    .order('created_at', { ascending: false });
    
if (error) throw error;

displaySecondaryAccounts(secondaryAccounts || []);

} catch (error) {
console.error('Erro ao carregar contas secundárias:', error);
showNotification('Erro ao carregar contas secundárias', 'error');
}
}

// Exibir contas secundárias
function displaySecondaryAccounts(accounts) {
const container = document.getElementById('secondaryAccountsList');

if (!accounts || accounts.length === 0) {
container.innerHTML = `
    <div class="text-center py-8">
        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
            </svg>
        </div>
        <p class="text-gray-500">Nenhuma conta secundária encontrada</p>
    </div>
`;
return;
}

const accountsHtml = accounts.map(account => {
const roleText = {
    'funcionario': 'Funcionário',
    'veterinario': 'Veterinário',
    'gerente': 'Gerente'
}[account.role] || account.role;

const roleColor = {
    'funcionario': 'bg-green-100 text-green-800',
    'veterinario': 'bg-purple-100 text-purple-800',
    'gerente': 'bg-blue-100 text-blue-800'
}[account.role] || 'bg-gray-100 text-gray-800';

const statusColor = account.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
const statusText = account.is_active ? 'Ativo' : 'Inativo';

return `
    <div class="bg-gray-50 rounded-xl p-4 border border-gray-200 hover:bg-gray-100 cursor-pointer transition-all duration-200 w-full" 
         onclick="accessSecondaryAccount('${account.id}', '${account.name}', '${account.role}')">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex-1 min-w-0">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-forest-100 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-forest-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h5 class="font-semibold text-gray-900 truncate">${account.name}</h5>
                        <p class="text-sm text-gray-600 truncate">${account.email}</p>
                        <p class="text-xs text-gray-500 truncate">${account.whatsapp || 'WhatsApp não informado'}</p>
                    </div>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2 flex-shrink-0">
                <span class="px-2 py-1 text-xs font-medium rounded-full ${roleColor} whitespace-nowrap">${roleText}</span>
                <span class="px-2 py-1 text-xs font-medium rounded-full ${statusColor} whitespace-nowrap">${statusText}</span>
                <span class="px-2 py-1 text-xs font-medium rounded-full bg-orange-100 text-orange-800 whitespace-nowrap">
                    <svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>Secundária
                </span>
            </div>
        </div>
        <div class="mt-3 pt-3 border-t border-gray-200">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 text-xs text-gray-500">
                <span class="truncate">Criado em: ${new Date(account.created_at).toLocaleDateString('pt-BR')}</span>
                <div class="flex items-center space-x-1">
                    <span class="text-blue-600 font-medium whitespace-nowrap">Clique para acessar →</span>
                </div>
            </div>
        </div>
    </div>
`;
}).join('');

container.innerHTML = accountsHtml;
}

// Obter farm_id do usuário atual
async function getCurrentUserFarmId() {
try {
// // const supabase = await getSupabaseClient(); // REMOVIDO // REMOVIDO - usando MySQL
const { data: { user } } = await supabase.auth.getUser();

if (!user) return null;

const { data: userData, error } = await supabase
    .from('users')
    .select('farm_id')
    .eq('id', user.id)
    .single();
    
if (error) return null;

return userData.farm_id;
} catch (error) {
console.error('Erro ao obter farm_id:', error);
return null;
}
}

// Função removida - não mais necessária após remoção dos botões de bloquear/excluir

// Acessar conta secundária
async function accessSecondaryAccount(userId, userName, userRole) {
const confirmed = confirm(`Deseja acessar a conta "${userName}" (${userRole})?\n\nVocê será redirecionado para esta conta.`);

if (confirmed) {
try {
    // Armazenar informações da conta atual
    const currentUser = {
        id: userId,
        name: userName,
        role: userRole,
        isSecondary: true
    };
    
    // Salvar no sessionStorage para poder voltar
    sessionStorage.setItem('currentSecondaryAccount', JSON.stringify(currentUser));
    
    // Redirecionar para a página apropriada baseada no role
    if (userRole === 'veterinario') {
        window.location.href = 'veterinario.php';
    } else if (userRole === 'funcionario') {
        window.location.href = 'funcionario.php';
    } else {
        showNotification('Tipo de conta não suportado', 'error');
    }
    
} catch (error) {
    showNotification('Erro ao acessar conta secundária: ' + error.message, 'error');
}
}
}

// Função removida - não mais necessária após remoção dos botões de bloquear/excluir

// Mostrar/ocultar seção de foto baseado no cargo selecionado
function togglePhotoSection() {
console.log('🔍 togglePhotoSection() chamada');

const roleSelect = document.getElementById('userRole');
const photoSection = document.getElementById('addPhotoSection');

console.log('📋 Role selecionado:', roleSelect?.value);
console.log('📸 Seção de foto encontrada:', !!photoSection);

if (roleSelect && photoSection) {
if (roleSelect.value === 'veterinario' || roleSelect.value === 'funcionario') {
    console.log('✅ Mostrando seção de foto para', roleSelect.value);
    photoSection.classList.remove('hidden');
    photoSection.style.display = 'block';
    photoSection.style.visibility = 'visible';
    photoSection.style.opacity = '1';
} else {
    console.log('❌ Ocultando seção de foto');
    photoSection.classList.add('hidden');
    photoSection.style.display = 'none';
    
    // Limpar foto se não for veterinário
    const profilePhotoInput = document.getElementById('profilePhotoInput');
    const profilePreview = document.getElementById('profilePreview');
    const profilePlaceholder = document.getElementById('profilePlaceholder');
    
    if (profilePhotoInput) profilePhotoInput.value = '';
    if (profilePreview) {
        profilePreview.src = '';
        profilePreview.style.display = 'none';
    }
    if (profilePlaceholder) profilePlaceholder.style.display = 'flex';
}
} else {
console.error('❌ Elementos não encontrados:', {
    roleSelect: !!roleSelect,
    photoSection: !!photoSection
});
}
}

// Carregar contas secundárias quando o modal for aberto
const currentOpenProfileModal = window.openProfileModal;
window.openProfileModal = function() {
currentOpenProfileModal();
setTimeout(() => {
loadSecondaryAccounts();
}, 100);
};

// Função removida - conflito resolvido na função openAddUserModal original

// Função para prévia do relatório na aba
function previewReportTab() {
const startDate = document.getElementById('reportStartDate').value;
const endDate = document.getElementById('reportEndDate').value;

if (!startDate || !endDate) {
showNotification('Por favor, selecione as datas inicial e final para a prévia', 'warning');
return;
}

// Gerar relatório de exemplo
const sampleData = [
{
    production_date: startDate,
    volume_liters: 150.5,
    shift: 'manha',
    temperature: 4.2,
    observations: 'Exemplo de registro para prévia',
    users: { name: 'Funcionário Exemplo' },
    created_at: new Date().toISOString()
}
];

generateVolumePDF(sampleData, true);
}

// Inicializar datas padrão (último mês)
function initializeDateFilters() {
const today = new Date();
const lastMonth = new Date();
lastMonth.setMonth(today.getMonth() - 1);

document.getElementById('reportStartDate').value = lastMonth.toISOString().split('T')[0];
document.getElementById('reportEndDate').value = today.toISOString().split('T')[0];
}

// ===== FUNÇÕES ESPECÍFICAS PARA FOTO DO GERENTE =====

// Abrir modal de escolha de foto do gerente
function openManagerPhotoModal() {
console.log('🔍 openManagerPhotoModal() chamada');

const modal = document.getElementById('managerPhotoChoiceModal');
console.log('📋 Modal encontrado:', !!modal);

if (modal) {
// Garantir que outros modais estejam fechados
const otherModals = ['photoChoiceModal', 'cameraModal', 'managerCameraModal'];
otherModals.forEach(modalId => {
    const otherModal = document.getElementById(modalId);
    if (otherModal) {
        otherModal.style.display = 'none';
        otherModal.style.visibility = 'hidden';
        otherModal.style.opacity = '0';
        otherModal.style.pointerEvents = 'none';
    }
});

// Forçar abertura do modal com múltiplas propriedades
modal.style.display = 'flex';
modal.style.visibility = 'visible';
modal.style.opacity = '1';
modal.style.pointerEvents = 'auto';
modal.style.position = 'fixed';
modal.style.zIndex = '999999';
modal.classList.remove('hidden');
modal.classList.add('flex');

console.log('✅ Modal aberto com sucesso');
} else {
console.error('❌ Modal não encontrado');
}
}

// Fechar modal de escolha de foto do gerente
function closeManagerPhotoModal() {
const modal = document.getElementById('managerPhotoChoiceModal');
if (modal) {
// Remover todas as classes que podem estar causando problemas
modal.classList.remove('show', 'flex', 'block');
modal.classList.add('hidden');

// Forçar ocultação com múltiplas propriedades
modal.style.display = 'none';
modal.style.visibility = 'hidden';
modal.style.opacity = '0';
modal.style.pointerEvents = 'none';
modal.style.position = 'fixed';
modal.style.zIndex = '-1';

console.log('✅ Modal de foto do gerente fechado');
} else {
console.log('❌ Modal de foto do gerente não encontrado');
}
}

// Abrir câmera do gerente com verificação facial
async function openManagerCamera() {
try {
console.log('🔍 Abrindo câmera do gerente...');

closeManagerPhotoModal();

const modal = document.getElementById('managerCameraModal');
const video = document.getElementById('managerCameraVideo');
const processingScreen = document.getElementById('managerPhotoProcessingScreen');

// Garantir que a tela de processamento esteja oculta
if (processingScreen) {
    processingScreen.classList.add('hidden');
    processingScreen.style.display = 'none';
    processingScreen.style.visibility = 'hidden';
    processingScreen.style.opacity = '0';
    processingScreen.style.pointerEvents = 'none';
    console.log('✅ Tela de processamento do gerente ocultada');
}

modal.classList.add('show');

// Verificar câmeras disponíveis
const devices = await navigator.mediaDevices.enumerateDevices();
const videoDevices = devices.filter(device => device.kind === 'videoinput');

console.log('📹 Câmeras disponíveis:', videoDevices.length);

let stream;
if (videoDevices.length >= 2) {
    // Se tem duas câmeras, usar a traseira primeiro
    try {
        stream = await navigator.mediaDevices.getUserMedia({ 
            video: { 
                facingMode: 'environment',
                width: { ideal: 1920 },
                height: { ideal: 1080 }
            } 
        });
        console.log('✅ Usando câmera traseira');
    } catch (error) {
        // Fallback para câmera frontal
        stream = await navigator.mediaDevices.getUserMedia({ 
            video: { 
                facingMode: 'user',
                width: { ideal: 1920 },
                height: { ideal: 1080 }
            } 
        });
        console.log('✅ Usando câmera frontal (fallback)');
    }
} else {
    // Se tem apenas uma câmera, usar ela
    stream = await navigator.mediaDevices.getUserMedia({ 
        video: { 
            facingMode: 'user',
            width: { ideal: 1920 },
            height: { ideal: 1080 }
        } 
    });
    console.log('✅ Usando única câmera disponível');
}

video.srcObject = stream;
window.managerCameraStream = stream;
window.managerCurrentFacingMode = stream.getVideoTracks()[0].getSettings().facingMode;

// Inicializar verificação facial
initializeFaceDetection();

console.log('✅ Câmera do gerente aberta com sucesso');

} catch (error) {
console.error('❌ Erro ao abrir câmera:', error);
showNotification('Erro ao acessar câmera: ' + error.message, 'error');
}
}

// Fechar câmera do gerente
function closeManagerCamera() {
const modal = document.getElementById('managerCameraModal');
if (modal) {
modal.classList.remove('show');
modal.style.display = 'none';
modal.style.visibility = 'hidden';
modal.style.opacity = '0';
modal.style.pointerEvents = 'none';
}

// Parar stream da câmera de forma segura
if (window.managerCameraStream) {
try {
    window.managerCameraStream.getTracks().forEach(track => {
        track.stop();
        console.log('✅ Track da câmera parada:', track.kind);
    });
} catch (error) {
    console.log('⚠️ Erro ao parar tracks da câmera:', error);
}
window.managerCameraStream = null;
}

// Resetar vídeo
const video = document.getElementById('managerCameraVideo');
if (video) {
try {
    video.pause();
    video.srcObject = null;
    video.load();
} catch (error) {
    console.log('⚠️ Erro ao resetar vídeo:', error);
}
}

// Resetar indicadores
resetManagerFaceVerification();

// Limpar detecção facial
if (faceDetectionInterval) {
clearInterval(faceDetectionInterval);
faceDetectionInterval = null;
}

// Resetar estados
isFaceDetected = false;
faceCentered = false;

console.log('✅ Câmera do gerente fechada');
}

// Trocar câmera do gerente
async function switchManagerCamera() {
try {
if (window.managerCameraStream) {
    window.managerCameraStream.getTracks().forEach(track => track.stop());
}

const video = document.getElementById('managerCameraVideo');
const stream = await navigator.mediaDevices.getUserMedia({ 
    video: { 
        facingMode: window.managerCameraFacingMode === 'user' ? 'environment' : 'user',
        width: { ideal: 1920 },
        height: { ideal: 1080 }
    } 
});

video.srcObject = stream;
window.managerCameraStream = stream;
window.managerCameraFacingMode = window.managerCameraFacingMode === 'user' ? 'environment' : 'user';

} catch (error) {
console.error('Erro ao trocar câmera:', error);
showNotification('Erro ao trocar câmera: ' + error.message, 'error');
}
}

// Variáveis globais para verificação facial
let faceDetectionInterval;
let isFaceDetected = false;
let faceCentered = false;

// Inicializar detecção facial
function initializeFaceDetection() {
console.log('🔍 Inicializando detecção facial...');

// Resetar estados
isFaceDetected = false;
faceCentered = false;
updateFaceUI();

// Iniciar detecção facial
startFaceDetection();
}

// Iniciar detecção facial
function startFaceDetection() {
const video = document.getElementById('managerCameraVideo');

if (faceDetectionInterval) {
clearInterval(faceDetectionInterval);
}

// Simular detecção facial (em produção, usar uma biblioteca como face-api.js)
faceDetectionInterval = setInterval(() => {
detectFace(video);
}, 100); // Verificar a cada 100ms
}

// Detectar rosto (simulação)
function detectFace(video) {
// Esta é uma simulação. Em produção, você usaria uma biblioteca real de detecção facial
const rect = video.getBoundingClientRect();
const centerX = rect.width / 2;
const centerY = rect.height / 2;

// Simular detecção baseada em movimento ou outras heurísticas
const hasMovement = Math.random() > 0.3; // Simular que há movimento/detecção
const isCentered = Math.random() > 0.4; // Simular centralização

if (hasMovement) {
isFaceDetected = true;
if (isCentered) {
    faceCentered = true;
    updateFaceUI();
} else {
    faceCentered = false;
    updateFaceUI();
}
} else {
isFaceDetected = false;
faceCentered = false;
updateFaceUI();
}
}

// Atualizar interface baseada na detecção
function updateFaceUI() {
const faceCircle = document.getElementById('managerFaceCircle');
const captureBtn = document.getElementById('managerCaptureBtn');
const faceStatus = document.getElementById('managerFaceStatus');
const faceWarning = document.getElementById('managerFaceWarning');

if (!isFaceDetected) {
// Rosto não detectado
faceCircle.style.borderColor = 'rgb(239, 68, 68)'; // Vermelho
faceStatus.textContent = 'Centralizando rosto...';
faceStatus.style.color = 'rgba(255, 255, 255, 0.7)';

// Desabilitar botão
captureBtn.disabled = true;
captureBtn.classList.add('opacity-50', 'cursor-not-allowed');
captureBtn.classList.remove('hover:scale-105', 'hover:bg-gray-100');
captureBtn.style.backgroundColor = 'rgba(255, 255, 255, 0.5)';

} else if (!faceCentered) {
// Rosto detectado mas não centralizado
faceCircle.style.borderColor = 'rgb(239, 68, 68)'; // Vermelho
faceStatus.textContent = 'Centralize o rosto no círculo';
faceStatus.style.color = 'rgba(255, 255, 255, 0.7)';

// Desabilitar botão
captureBtn.disabled = true;
captureBtn.classList.add('opacity-50', 'cursor-not-allowed');
captureBtn.classList.remove('hover:scale-105', 'hover:bg-gray-100');
captureBtn.style.backgroundColor = 'rgba(255, 255, 255, 0.5)';

} else {
// Rosto centralizado
faceCircle.style.borderColor = 'rgb(34, 197, 94)'; // Verde
faceStatus.textContent = 'Rosto centralizado!';
faceStatus.style.color = 'rgb(34, 197, 94)';

// Habilitar botão
captureBtn.disabled = false;
captureBtn.classList.remove('opacity-50', 'cursor-not-allowed');
captureBtn.classList.add('hover:scale-105', 'hover:bg-gray-100');
captureBtn.style.backgroundColor = 'white';

// Ocultar aviso se estiver visível
if (faceWarning) {
    faceWarning.style.opacity = '0';
}
}
}

// Capturar foto do gerente com verificação facial
async function captureManagerPhoto() {
// Verificar se o rosto está centralizado
if (!faceCentered) {
const faceWarning = document.getElementById('managerFaceWarning');
if (faceWarning) {
    faceWarning.style.opacity = '1';
    setTimeout(() => {
        faceWarning.style.opacity = '0';
    }, 3000);
}
return;
}

try {
console.log('📸 Capturando foto do gerente...');

const video = document.getElementById('managerCameraVideo');
const canvas = document.getElementById('managerCameraCanvas');
const processingScreen = document.getElementById('managerPhotoProcessingScreen');

if (!video || !canvas) {
    throw new Error('Elementos de vídeo ou canvas não encontrados');
}

// Verificar se o vídeo está pronto
if (video.readyState < 2) {
    throw new Error('Vídeo não está pronto');
}

// Mostrar tela de processamento
if (processingScreen) {
    processingScreen.classList.remove('hidden');
}

// Configurar canvas
canvas.width = video.videoWidth;
canvas.height = video.videoHeight;

// Desenhar frame do vídeo no canvas
const ctx = canvas.getContext('2d');
ctx.drawImage(video, 0, 0);

// Converter para blob
canvas.toBlob(async (blob) => {
    try {
        if (!blob) {
            throw new Error('Falha ao criar blob da imagem');
        }
        
        const file = new File([blob], 'manager-photo.jpg', { type: 'image/jpeg' });
        
        // Fechar câmera primeiro
        closeManagerCamera();
        
        // Processar foto
        await processManagerPhoto(file);
        
    } catch (error) {
        console.error('Erro ao processar foto:', error);
        showNotification('Erro ao processar foto: ' + error.message, 'error');
    } finally {
        if (processingScreen) {
            processingScreen.classList.add('hidden');
        }
    }
}, 'image/jpeg', 0.8);

} catch (error) {
console.error('Erro ao capturar foto:', error);
showNotification('Erro ao capturar foto: ' + error.message, 'error');

// Ocultar tela de processamento em caso de erro
const processingScreen = document.getElementById('managerPhotoProcessingScreen');
if (processingScreen) {
    processingScreen.classList.add('hidden');
}
}
}

// Processar foto do gerente
async function processManagerPhoto(file) {
try {
// Validar arquivo
if (!file || file.size === 0) {
    throw new Error('Arquivo inválido');
}

if (file.size > 2 * 1024 * 1024) {
    throw new Error('Arquivo muito grande (máximo 2MB)');
}

// Mostrar preview
previewManagerProfilePhoto(file);

// Upload da foto
// // const supabase = await getSupabaseClient(); // REMOVIDO // REMOVIDO - usando MySQL
const { data: { user } } = await supabase.auth.getUser();

if (!user) throw new Error('Usuário não autenticado');

const photoUrl = await uploadManagerProfilePhoto(file, user.id);

// Atualizar perfil
const { error: updateError } = await supabase
    .from('users')
    .update({ profile_photo_url: photoUrl })
    .eq('id', user.id);
    
if (updateError) throw updateError;

// Atualizar exibição
updateManagerPhotoDisplay(photoUrl);

// Fechar modal automaticamente
closeManagerPhotoModal();

// Fechar câmera se estiver aberta
closeManagerCamera();

// Aguardar um pouco antes de mostrar notificação
setTimeout(() => {
    showNotification('Foto de perfil atualizada com sucesso!', 'success');
}, 200);

} catch (error) {
console.error('Erro ao processar foto do gerente:', error);
showNotification('Erro ao processar foto: ' + error.message, 'error');
}
}

// Preview da foto do gerente
function previewManagerProfilePhoto(file) {
const preview = document.getElementById('managerProfilePreview');
const placeholder = document.getElementById('managerProfilePlaceholder');

if (preview && placeholder) {
const url = URL.createObjectURL(file);
preview.src = url;
preview.style.display = 'block';
placeholder.style.display = 'none';
}
}

// Selecionar da galeria do gerente
function selectManagerFromGallery() {
const input = document.getElementById('managerProfilePhotoInput');
if (input) {
input.click();
}
}

// Preview da foto da galeria do gerente
function handleManagerGallerySelection(input) {
if (input.files && input.files[0]) {
const file = input.files[0];
console.log('📸 Arquivo selecionado da galeria:', file.name);

// Fechar modal de escolha primeiro
closeManagerPhotoModal();

// Aguardar um pouco antes de processar
setTimeout(() => {
    processManagerPhoto(file);
}, 100);
}
}

// Verificação facial do gerente
function startManagerFaceVerification() {
const focusText = document.getElementById('managerFocusText');
const focusTimer = document.getElementById('managerFocusTimer');
const focusIndicator = document.getElementById('managerFocusIndicator');
const timerCount = document.getElementById('managerTimerCount');

if (focusText) focusText.textContent = 'Focando...';
if (focusTimer) focusTimer.classList.remove('hidden');
if (focusIndicator) focusIndicator.classList.remove('opacity-0');

let count = 3;
const timer = setInterval(() => {
if (timerCount) timerCount.textContent = count;

if (count <= 0) {
    clearInterval(timer);
    captureManagerPhoto();
}
count--;
}, 1000);

window.managerFaceVerificationTimer = timer;
}

// Resetar verificação facial do gerente
function resetManagerFaceVerification() {
const focusText = document.getElementById('managerFocusText');
const focusTimer = document.getElementById('managerFocusTimer');
const focusIndicator = document.getElementById('managerFocusIndicator');

if (focusText) focusText.textContent = 'Posicione o rosto no centro';
if (focusTimer) focusTimer.classList.add('hidden');
if (focusIndicator) focusIndicator.classList.add('opacity-0');

if (window.managerFaceVerificationTimer) {
clearInterval(window.managerFaceVerificationTimer);
window.managerFaceVerificationTimer = null;
}
}

// Atualizar exibição da foto do gerente
function updateManagerPhotoDisplay(photoUrl) {
console.log('🖼️ Atualizando exibição da foto:', photoUrl);

if (!photoUrl) {
console.log('❌ URL da foto não fornecida');
return;
}

// Atualizar foto no header
const headerPhoto = document.getElementById('headerProfilePhoto');
const headerPlaceholder = document.getElementById('headerProfileIcon');
if (headerPhoto && headerPlaceholder) {
headerPhoto.src = photoUrl + '?t=' + Date.now();
headerPhoto.style.display = 'block';
headerPhoto.style.visibility = 'visible';
headerPhoto.classList.remove('hidden');
headerPhoto.classList.add('block');

headerPlaceholder.style.display = 'none';
headerPlaceholder.style.visibility = 'hidden';
headerPlaceholder.classList.add('hidden');
headerPlaceholder.classList.remove('block');

console.log('✅ Foto do header atualizada');
}

// Atualizar foto no modal de perfil
const modalPhoto = document.getElementById('modalProfilePhoto');
const modalPlaceholder = document.getElementById('modalProfileIcon');
if (modalPhoto && modalPlaceholder) {
modalPhoto.src = photoUrl + '?t=' + Date.now();
modalPhoto.style.display = 'block';
modalPhoto.style.visibility = 'visible';
modalPhoto.classList.remove('hidden');
modalPhoto.classList.add('block');

modalPlaceholder.style.display = 'none';
modalPlaceholder.style.visibility = 'hidden';
modalPlaceholder.classList.add('hidden');
modalPlaceholder.classList.remove('block');

console.log('✅ Foto do modal atualizada');
}

// Atualizar preview no formulário de edição
const preview = document.getElementById('managerProfilePreview');
const placeholder = document.getElementById('managerProfilePlaceholder');
if (preview && placeholder) {
preview.src = photoUrl + '?t=' + Date.now();
preview.style.display = 'block';
preview.style.visibility = 'visible';
preview.classList.remove('hidden');
preview.classList.add('block');

placeholder.style.display = 'none';
placeholder.style.visibility = 'hidden';
placeholder.classList.add('hidden');
placeholder.classList.remove('block');

console.log('✅ Preview atualizado');
}
}

// Upload da foto do gerente para o Supabase
async function uploadManagerProfilePhoto(file, userId) {
try {
// // const supabase = await getSupabaseClient(); // REMOVIDO // REMOVIDO - usando MySQL

// Obter farm_id do usuário atual
const { data: { user } } = await supabase.auth.getUser();
if (!user) throw new Error('Usuário não autenticado');

const { data: userData, error: userError } = await supabase
    .from('users')
    .select('farm_id')
    .eq('id', user.id)
    .single();
    
if (userError || !userData?.farm_id) {
    throw new Error('Farm ID não encontrado');
}

// Criar nome único para o arquivo
const timestamp = Date.now();
const randomId = Math.random().toString(36).substr(2, 9);
const fileExt = file.name.split('.').pop() || 'jpg';
const fileName = `manager_${userId}_${timestamp}_${randomId}.${fileExt}`;
const filePath = `farm_${userData.farm_id}/${fileName}`;

// Upload do arquivo
const { data, error } = await supabase.storage
    .from('profile-photos')
    .upload(filePath, file, {
        cacheControl: '3600',
        upsert: false
    });
    
if (error) {
    console.error('Erro no upload:', error);
    throw error;
}

// Obter URL pública
const { data: { publicUrl } } = supabase.storage
    .from('profile-photos')
    .getPublicUrl(filePath);
    
return publicUrl;

} catch (error) {
console.error('Erro no upload da foto do gerente:', error);
throw error;
}
}

// Carregar biblioteca Excel dinamicamente
function loadExcelLibrary() {
if (typeof XLSX === 'undefined') {
const script = document.createElement('script');
script.src = 'https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js';
script.onload = function() {

};
script.onerror = function() {
};
document.head.appendChild(script);
}
}

// Evento para carregar dados quando a aba de relatórios for aberta
document.addEventListener('DOMContentLoaded', function() {
loadExcelLibrary();

// Carregar dados quando a aba for aberta
const observer = new MutationObserver(function(mutations) {
mutations.forEach(function(mutation) {
    if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
        const reportsTab = document.getElementById('reports-tab');
        if (reportsTab && !reportsTab.classList.contains('hidden')) {
            loadReportTabSettings();
            loadReportStats();
            initializeDateFilters();
        }
    }
});
});

const reportsTab = document.getElementById('reports-tab');
if (reportsTab) {
observer.observe(reportsTab, { attributes: true });
}
});

    // App Version Display
document.addEventListener('DOMContentLoaded', function() {
    // Adiciona versão do app no perfil do usuário
    const appVersion = '1.0.0';
    
    // Função para adicionar versão em elementos de perfil
    function addVersionToProfile() {
        const profileElements = document.querySelectorAll('.user-profile, .profile-info, .user-info');
        profileElements.forEach(element => {
            if (!element.querySelector('.app-version')) {
                const versionDiv = document.createElement('div');
                versionDiv.className = 'app-version text-xs text-gray-500 mt-2';
                versionDiv.innerHTML = `App v${appVersion}`;
                element.appendChild(versionDiv);
            }
        });
        
        // Adicionar no footer se existir
        const footer = document.querySelector('footer, .footer');
        if (footer && !footer.querySelector('.app-version')) {
            const versionDiv = document.createElement('div');
            versionDiv.className = 'app-version text-xs text-gray-500 text-center mt-4';
            versionDiv.innerHTML = `LacTech v${appVersion}`;
            footer.appendChild(versionDiv);
        }
    }
    
    // Função para adicionar versão no modal de perfil
    function addVersionToProfileModal() {
        const profileModal = document.getElementById('profileModal');
        if (profileModal && !profileModal.querySelector('.app-version')) {
            const versionDiv = document.createElement('div');
            versionDiv.className = 'app-version text-xs text-gray-500 text-center mt-4 p-4 border-t border-gray-200';
            versionDiv.innerHTML = `LacTech v${appVersion}`;
            profileModal.querySelector('.modal-content').appendChild(versionDiv);
        }
    }
    
    // Executar após carregamento
    setTimeout(addVersionToProfile, 1000);
    
    // Adicionar versão quando o modal de perfil for aberto
    const originalOpenProfileModal = window.openProfileModal;
    window.openProfileModal = function() {
        if (originalOpenProfileModal) {
            originalOpenProfileModal();
        }
        setTimeout(addVersionToProfileModal, 100);
    };
});

// Sistema de Carregamento
const loadingSteps = [
    { message: 'Inicializando sistema...', subMessage: 'Preparando ambiente...', progress: 10 },
    { message: 'Conectando ao banco de dados...', subMessage: 'Estabelecendo conexão...', progress: 25 },
    { message: 'Carregando dados da fazenda...', subMessage: 'Buscando informações...', progress: 40 },
    { message: 'Configurando interface...', subMessage: 'Preparando componentes...', progress: 60 },
    { message: 'Carregando gráficos...', subMessage: 'Preparando visualizações...', progress: 80 },
    { message: 'Finalizando carregamento...', subMessage: 'Quase pronto...', progress: 95 },
    { message: 'Sistema pronto!', subMessage: 'Bem-vindo ao LacTech', progress: 100 }
];

let currentStep = 0;
let loadingInterval;

function updateLoadingScreen() {
    const loadingMessage = document.getElementById('loadingMessage');
    const loadingSubMessage = document.getElementById('loadingSubMessage');
    const loadingProgress = document.getElementById('loadingProgress');
    const loadingPercentage = document.getElementById('loadingPercentage');
    const farmNameLoading = document.getElementById('farmNameLoading');

    if (currentStep < loadingSteps.length) {
        const step = loadingSteps[currentStep];
        
        if (loadingMessage) loadingMessage.textContent = step.message;
        if (loadingSubMessage) loadingSubMessage.textContent = step.subMessage;
        if (loadingProgress) loadingProgress.style.width = step.progress + '%';
        if (loadingPercentage) loadingPercentage.textContent = step.progress + '%';
        
        // Atualizar nome da fazenda quando disponível
        if (currentStep === 2 && farmNameLoading) {
            // Tentar pegar o nome da fazenda do localStorage ou de outro lugar
            const farmName = localStorage.getItem('farmName') || 'Sistema de Gestão';
            farmNameLoading.textContent = farmName;
        }
        
        currentStep++;
    } else {
        // Carregamento completo
        clearInterval(loadingInterval);
        setTimeout(() => {
            const loadingScreen = document.getElementById('loadingScreen');
            if (loadingScreen) {
                loadingScreen.style.opacity = '0';
                loadingScreen.style.transition = 'opacity 0.5s ease-out';
                setTimeout(() => {
                    loadingScreen.style.display = 'none';
                    loadingScreen.style.visibility = 'hidden';
                    loadingScreen.style.pointerEvents = 'none';
                }, 500);
            }
        }, 500);
    }
}

// Iniciar carregamento
document.addEventListener('DOMContentLoaded', function() {
            // Forçar fechamento do modal de foto do gerente
const managerPhotoChoiceModal = document.getElementById('managerPhotoChoiceModal');
if (managerPhotoChoiceModal) {
    managerPhotoChoiceModal.style.display = 'none';
    managerPhotoChoiceModal.style.visibility = 'hidden';
    managerPhotoChoiceModal.style.opacity = '0';
    managerPhotoChoiceModal.style.pointerEvents = 'none';
    managerPhotoChoiceModal.classList.add('hidden');
    managerPhotoChoiceModal.classList.remove('flex');
}

// Garantir que o modal MAIS esteja fechado
const moreModal = document.getElementById('moreModal');
if (moreModal) {
    moreModal.style.display = 'none';
    moreModal.style.visibility = 'hidden';
    moreModal.style.opacity = '0';
    moreModal.style.pointerEvents = 'none';
    moreModal.classList.add('hidden');
}

// Garantir que o modal de foto do gerente esteja fechado
const managerPhotoModal = document.getElementById('managerPhotoChoiceModal');
if (managerPhotoModal) {
    managerPhotoModal.classList.remove('show', 'flex', 'block');
    managerPhotoModal.classList.add('hidden');
    managerPhotoModal.style.display = 'none';
    managerPhotoModal.style.visibility = 'hidden';
    managerPhotoModal.style.opacity = '0';
    managerPhotoModal.style.pointerEvents = 'none';
    managerPhotoModal.style.position = 'fixed';
    managerPhotoModal.style.zIndex = '-1';
    console.log('✅ Modal de foto do gerente fechado na inicialização');
}
    
    // Iniciar sequência de carregamento
    loadingInterval = setInterval(updateLoadingScreen, 1000);
    
            // Garantir que os modais fiquem fechados na inicialização
const photoChoiceModal = document.getElementById('photoChoiceModal');
const cameraModal = document.getElementById('cameraModal');
const managerCameraModal = document.getElementById('managerCameraModal');

if (photoChoiceModal) {
    photoChoiceModal.classList.add('hidden');
    photoChoiceModal.classList.remove('flex');
    photoChoiceModal.style.display = 'none';
}

if (cameraModal) {
    cameraModal.classList.add('hidden');
    cameraModal.style.display = 'none';
}

if (managerCameraModal) {
    managerCameraModal.style.display = 'none';
}

// Garantir que as variáveis estejam limpas
isCameraOpen = false;
cameraStream = null;
currentPhotoMode = '';

// Configurar header dinâmico do modal de perfil
setupProfileModalHeader();
});

// Remover foto do gerente
async function removeManagerPhoto() {
    try {
        const confirmed = confirm('Tem certeza que deseja remover sua foto de perfil?');
        if (!confirmed) return;
        
        // // const supabase = await getSupabaseClient(); // REMOVIDO // REMOVIDO - usando MySQL
        const { data: { user } } = await supabase.auth.getUser();
        
        if (!user) {
            showNotification('Usuário não autenticado', 'error');
            return;
        }
        
        // Remover foto do storage se existir
        const { data: userData } = await supabase
            .from('users')
            .select('profile_photo_url')
            .eq('id', user.id)
            .single();
        
        if (userData && userData.profile_photo_url) {
            // Extrair o caminho do arquivo da URL
            const photoPath = userData.profile_photo_url.split('/').pop();
            if (photoPath) {
                await supabase.storage
                    .from('profile-photos')
                    .remove([photoPath]);
            }
        }
        
        // Atualizar o banco de dados removendo a referência da foto
        const { error: updateError } = await supabase
            .from('users')
            .update({ 
                profile_photo_url: null,
                updated_at: new Date().toISOString()
            })
            .eq('id', user.id);
        
        if (updateError) {
            throw updateError;
        }
        
        // Atualizar a interface
        const headerPhoto = document.getElementById('headerProfilePhoto');
        const modalPhoto = document.getElementById('modalProfilePhoto');
        const modalIcon = document.getElementById('modalProfileIcon');
        
        if (headerPhoto) {
            headerPhoto.classList.add('hidden');
        }
        
        if (modalPhoto) {
            modalPhoto.classList.add('hidden');
        }
        
        if (modalIcon) {
            modalIcon.classList.remove('hidden');
        }
        
        showNotification('Foto de perfil removida com sucesso!', 'success');
        
    } catch (error) {
        console.error('Erro ao remover foto do gerente:', error);
        showNotification('Erro ao remover foto de perfil', 'error');
    }
}

// Configurar header dinâmico do modal de perfil (apenas mobile)
function setupProfileModalHeader() {
    const profileModal = document.querySelector('#profileModal .modal-content');
    const header = document.getElementById('profileModalHeader');
    let lastScrollTop = 0;
    let isScrolling = false;
    
    // Verificar se é mobile
    const isMobile = window.innerWidth <= 768;
    
    if (profileModal && header && isMobile) {
        // Função para controlar a visibilidade do header
        function handleScroll() {
            if (!isScrolling) {
                isScrolling = true;
                requestAnimationFrame(function() {
                    const scrollTop = profileModal.scrollTop;
                    
                    // Detectar direção do scroll
                    if (scrollTop > lastScrollTop && scrollTop > 50) {
                        // Scroll para baixo - esconder header completamente
                        header.style.transform = 'translateY(-110%)';
                        header.style.opacity = '0';
                    } else if (scrollTop < lastScrollTop) {
                        // Scroll para cima - mostrar header
                        header.style.transform = 'translateY(0)';
                        header.style.opacity = '1';
                    }
                    
                    // Mostrar header quando estiver no topo
                    if (scrollTop <= 10) {
                        header.style.transform = 'translateY(0)';
                        header.style.opacity = '1';
                    }
                    
                    lastScrollTop = scrollTop;
                    isScrolling = false;
                });
            }
        }
        
        // Remover listener anterior se existir
        profileModal.removeEventListener('scroll', handleScroll);
        
        // Adicionar listener de scroll
        profileModal.addEventListener('scroll', handleScroll);
        
        // Inicializar estado do header
        header.style.transform = 'translateY(0)';
    } else if (header && !isMobile) {
        // No desktop, sempre mostrar o header
        header.style.transform = 'translateY(0)';
    }
}

// ==================== FUNÇÕES DO MODAL MAIS ====================

// Abrir modal MAIS
function openMoreModal() {
    const modal = document.getElementById('moreModal');
    if (modal) {
    
        modal.style.display = 'block';
        modal.style.visibility = 'visible';
        modal.style.opacity = '1';
        modal.style.pointerEvents = 'auto';
        modal.classList.remove('hidden');
    
    // Forçar reflow
    modal.offsetHeight;
    
    // Atualizar logo da Xandria Store
    setTimeout(updateXandriaStoreIcon, 100);
    }
}

// Fechar modal MAIS
function closeMoreModal() {
    const modal = document.getElementById('moreModal');
    if (modal) {
        modal.style.display = 'none';
        modal.style.visibility = 'hidden';
        modal.style.opacity = '0';
        modal.style.pointerEvents = 'none';
        modal.classList.add('hidden');
    }
}

// ==================== FUNÇÕES REMOVIDAS - CHAT ====================
// Sistema de chat removido para simplificar o sistema da Lagoa do Mato
// Todas as funcionalidades de chat foram removidas
console.log('ℹ️ Sistema de chat desabilitado - Lagoa do Mato');

async function openChatModal() {
    
    const modal = document.getElementById('chatModal');
    if (modal) {
        modal.classList.remove('hidden');
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        
        // Configurar listener de scroll do chat
        setTimeout(() => {
            setupChatScrollListener();
        }, 100);
        
        // Atualizar status online do usuário atual
        try {
            // // const supabase = await getSupabaseClient(); // REMOVIDO // REMOVIDO - usando MySQL
            const { data: { user } } = await supabase.auth.getUser();
            if (user) {
                await updateUserLastLogin(user.id);
                
                // Buscar farm_id para configurar real-time
                const { data: userData } = await supabase
                    .from('users')
                    .select('farm_id')
                    .eq('id', user.id)
                    .single();
                
                if (userData?.farm_id) {
                    // Configurar real-time para chat
                    setupChatRealtime(userData.farm_id);
                }
            }
        } catch (error) {
            console.error('Erro ao atualizar status online:', error);
        }
        
        loadEmployees();
    }
}

// Fechar modal de chat
function closeChatModal() {
    const modal = document.getElementById('chatModal');
    if (modal) {
        modal.classList.add('hidden');
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
        
        // Desconectar real-time do chat
        if (chatRealtimeChannel) {
            disconnectRealtime(chatRealtimeChannel);
            chatRealtimeChannel = null;
            console.log('🔌 Real-time do chat desconectado');
        }
        
        // Parar polling
        if (chatPollingInterval) {
            clearInterval(chatPollingInterval);
            chatPollingInterval = null;
            console.log('🔌 Polling do chat parado');
        }
    }
}

// Carregar funcionários da fazenda
async function loadEmployees() {
    try {
        console.log('🔄 Carregando funcionários...');
        
        // // const supabase = await getSupabaseClient(); // REMOVIDO // REMOVIDO - usando MySQL
        const { data: { user } } = await supabase.auth.getUser();
        
        if (!user) {
            console.error('❌ Usuário não autenticado');
            return;
        }

        console.log('👤 Usuário autenticado:', user.email);
        
        // Definir currentUser globalmente
        window.currentUser = user;

        // Buscar farm_id do usuário
        const { data: userData, error: userError } = await supabase
            .from('users')
            .select('farm_id')
            .eq('id', user.id)
            .single();

        if (userError) {
            console.error('❌ Erro ao buscar farm_id:', userError);
            return;
        }

        if (!userData?.farm_id) {
            console.error('❌ Farm ID não encontrado');
            return;
        }

        console.log('🏢 Farm ID:', userData.farm_id);

        // Usar o serviço de sincronização para buscar funcionários
        const employees = await getFarmUsers(userData.farm_id);
        console.log('👥 Funcionários encontrados:', employees.length);
        
        // Incluir todos os usuários (gerente + funcionários)
        displayEmployees(employees);
    } catch (error) {
        console.error('❌ Erro ao carregar funcionários:', error);
        showNotification('Erro ao carregar funcionários: ' + error.message, 'error');
    }
}

// Exibir funcionários na lista
function displayEmployees(employees) {
    console.log('📋 Exibindo funcionários:', employees);
    
    const employeesList = document.getElementById('employeesList');
    const onlineEmployees = document.getElementById('onlineEmployees');
    
    if (!employeesList) {
        console.error('❌ Elemento employeesList não encontrado');
        return;
    }
    
    if (!onlineEmployees) {
        console.error('❌ Elemento onlineEmployees não encontrado');
        return;
    }

    console.log('✅ Elementos encontrados, limpando listas...');
    employeesList.innerHTML = '';
    onlineEmployees.innerHTML = '';

    employees.forEach(employee => {
        // Debug: verificar IDs
        console.log('🔍 Comparando:', {
            employeeId: employee.id,
            currentUserId: window.currentUser?.id,
            employeeEmail: employee.email,
            currentUserEmail: window.currentUser?.email,
            employeeName: employee.name
        });
        
        // Filtrar o próprio gerente da lista
        if (employee.id === window.currentUser?.id || employee.email === window.currentUser?.email) {
            console.log('🚫 Filtrando próprio usuário da lista:', employee.name);
            return; // Pular o próprio usuário
        }
        
        const isOnline = isEmployeeOnline(employee);
        const initial = employee.name.charAt(0).toUpperCase();
        const userColor = generateUserColor(employee.name);
        
        // Verificar se tem foto de perfil
        const hasPhoto = employee.profile_photo_url && employee.profile_photo_url.trim() !== '';
        
        // Gerar avatar (foto ou letra) para lista principal
        let mainAvatarHtml;
        if (hasPhoto) {
            mainAvatarHtml = `
                <img src="${employee.profile_photo_url}?t=${Date.now()}" 
                     alt="Foto de ${employee.name}" 
                     class="w-10 h-10 rounded-full object-cover"
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                     onload="this.nextElementSibling.style.display='none';">
                <div class="w-10 h-10 bg-gradient-to-br ${userColor} rounded-full flex items-center justify-center" style="display: flex;">
                    <span class="text-white font-semibold text-sm">${initial}</span>
                </div>
            `;
        } else {
            mainAvatarHtml = `
                <div class="w-10 h-10 bg-gradient-to-br ${userColor} rounded-full flex items-center justify-center">
                    <span class="text-white font-semibold text-sm">${initial}</span>
                </div>
            `;
        }
        
        // Item da lista principal
        const employeeItem = document.createElement('div');
        employeeItem.className = 'flex items-center space-x-3 p-2.5 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors';
        employeeItem.onclick = () => selectEmployee(employee);
        
        employeeItem.innerHTML = `
            <div class="relative">
                ${mainAvatarHtml}
                ${isOnline ? '<div class="absolute -bottom-0.5 -right-0.5 w-3 h-3 bg-green-500 border-2 border-white rounded-full"></div>' : ''}
            </div>
            <div class="flex-1 min-w-0">
                <h4 class="font-medium text-gray-900 truncate text-sm">${employee.name}</h4>
                <p class="text-xs text-gray-500 truncate">${employee.role}</p>
            </div>
            <div class="text-xs text-gray-400">
                ${isOnline ? 'Online' : formatLastSeen(employee.last_login)}
            </div>
        `;
        
        employeesList.appendChild(employeeItem);

        // Funcionário online (se estiver online)
        if (isOnline) {
            const onlineItem = document.createElement('div');
            onlineItem.className = 'flex flex-col items-center space-y-1 cursor-pointer';
            onlineItem.onclick = () => selectEmployee(employee);
            
            // Gerar avatar (foto ou letra) para seção online
            let onlineAvatarHtml;
            if (hasPhoto) {
                onlineAvatarHtml = `
                    <img src="${employee.profile_photo_url}?t=${Date.now()}" 
                         alt="Foto de ${employee.name}" 
                         class="w-10 h-10 rounded-full object-cover"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                         onload="this.nextElementSibling.style.display='none';">
                    <div class="w-10 h-10 bg-gradient-to-br ${userColor} rounded-full flex items-center justify-center" style="display: flex;">
                        <span class="text-white font-semibold text-xs">${initial}</span>
                    </div>
                `;
            } else {
                onlineAvatarHtml = `
                    <div class="w-10 h-10 bg-gradient-to-br ${userColor} rounded-full flex items-center justify-center">
                        <span class="text-white font-semibold text-xs">${initial}</span>
                    </div>
                `;
            }
            
            onlineItem.innerHTML = `
                <div class="relative">
                    ${onlineAvatarHtml}
                    <div class="absolute -bottom-0.5 -right-0.5 w-3 h-3 bg-green-500 border-2 border-white rounded-full"></div>
                </div>
                <span class="text-xs text-gray-600 text-center max-w-16 truncate">${employee.name}</span>
            `;
            
            onlineEmployees.appendChild(onlineItem);
        }
    });
    
    console.log('✅ Funcionários exibidos com sucesso!');
    console.log('📊 Total de funcionários:', employees.length);
    console.log('🟢 Funcionários online:', document.querySelectorAll('#onlineEmployees > div').length);
}

// Verificar se funcionário está online
function isEmployeeOnline(employee) {
    // Verificar se o objeto employee existe
    if (!employee) {
        console.warn('⚠️ Employee object is null or undefined');
        return false;
    }
    
    // Usar a coluna is_online se disponível, senão usar last_login
    if (employee.is_online !== undefined && employee.is_online !== null) {
        return employee.is_online;
    }
    
    // Fallback para last_login
    if (!employee.last_login) return false;
    const now = new Date();
    const loginTime = new Date(employee.last_login);
    const diffMinutes = (now - loginTime) / (1000 * 60);
    return diffMinutes < 15; // Considera online se fez login nos últimos 15 minutos
}

// Formatar última vez visto
function formatLastSeen(lastLogin) {
    if (!lastLogin) return 'Nunca';
    
    try {
        const now = new Date();
        const loginTime = new Date(lastLogin);
        
        // Verificar se a data é válida
        if (isNaN(loginTime.getTime())) {
            return 'Data inválida';
        }
        
        const diffMinutes = (now - loginTime) / (1000 * 60);
        
        if (diffMinutes < 60) return 'Há ' + Math.floor(diffMinutes) + 'min';
        if (diffMinutes < 1440) return 'Há ' + Math.floor(diffMinutes / 60) + 'h';
        return 'Há ' + Math.floor(diffMinutes / 1440) + ' dias';
    } catch (error) {
        console.error('Erro ao formatar lastLogin:', error);
        return 'Erro';
    }
}

// Selecionar funcionário para conversa
function selectEmployee(employee) {
    // Verificar se o employee existe
    if (!employee) {
        console.error('❌ Employee object is null or undefined');
        return;
    }
    
    
    window.selectedEmployee = employee;
    
    // Verificar se os elementos existem antes de usar
    const nameElement = document.getElementById('selectedEmployeeName');
    const initialElement = document.getElementById('selectedEmployeeInitial');
    const statusElement = document.getElementById('selectedEmployeeStatus');
    const messageInput = document.getElementById('chatMessageInput');
    const sendBtn = document.getElementById('sendMessageBtn');
    
    if (nameElement) nameElement.textContent = employee.name || 'Nome não disponível';
    if (statusElement) statusElement.textContent = isEmployeeOnline(employee) ? 'Online' : 'Offline';
    
    // Atualizar avatar no header com foto de perfil ou inicial colorida
    if (initialElement) {
        const avatarContainer = initialElement.parentElement;
        if (avatarContainer) {
            // Limpar conteúdo anterior
            avatarContainer.innerHTML = '';
            
            if (employee.profile_photo_url) {
                // Usar foto de perfil
                const img = document.createElement('img');
                img.src = employee.profile_photo_url;
                img.alt = employee.name || 'Avatar';
                img.className = 'w-10 h-10 rounded-full object-cover';
                img.onerror = () => {
                    // Fallback para inicial colorida se a imagem falhar
                    const userColor = generateUserColor(employee.name);
                    const senderInitial = (employee.name || '?').charAt(0).toUpperCase();
                    avatarContainer.className = `w-10 h-10 bg-gradient-to-br ${userColor} rounded-full flex items-center justify-center`;
                    avatarContainer.innerHTML = `<span class="text-white font-semibold text-sm">${senderInitial}</span>`;
                };
                avatarContainer.appendChild(img);
            } else {
                // Usar inicial colorida
                const userColor = generateUserColor(employee.name);
                const senderInitial = (employee.name || '?').charAt(0).toUpperCase();
                avatarContainer.className = `w-10 h-10 bg-gradient-to-br ${userColor} rounded-full flex items-center justify-center`;
                avatarContainer.innerHTML = `<span class="text-white font-semibold text-sm">${senderInitial}</span>`;
            }
        }
    }
    
    if (messageInput) messageInput.disabled = false;
    if (sendBtn) sendBtn.disabled = false;
    
    // Mostrar interface do chat e ocultar UI inicial
    const initialUI = document.getElementById('initialChatUI');
    const chatMessages = document.getElementById('chatMessages');
    const chatInputArea = document.getElementById('chatInputArea');
    const chatHeader = document.getElementById('chatHeader');
    
    if (initialUI) initialUI.classList.add('hidden');
    if (chatMessages) chatMessages.classList.remove('hidden');
    if (chatInputArea) chatInputArea.classList.remove('hidden');
    if (chatHeader) chatHeader.classList.remove('hidden');
    
    // Alternar entre sidebar e área principal no mobile
    const sidebar = document.getElementById('chatSidebar');
    const mainArea = document.getElementById('chatMainArea');
    
    if (window.innerWidth < 1024) {
        // Mobile: ocultar sidebar e mostrar área principal
        if (sidebar) {
            sidebar.classList.add('hidden');
            sidebar.classList.remove('flex');
        }
        if (mainArea) {
            mainArea.classList.remove('hidden');
            mainArea.classList.add('flex');
        }
    }
    
    // Carregar mensagens com este funcionário
    if (employee.id) {
        loadChatMessages(employee.id);
    } else {
        console.error('❌ Employee ID is missing');
    }
}

// Sair do chat e voltar para UI inicial
function exitChat() {
    // Limpar funcionário selecionado
    window.selectedEmployee = null;
    
    // Ocultar interface do chat e mostrar UI inicial
    const initialUI = document.getElementById('initialChatUI');
    const chatMessages = document.getElementById('chatMessages');
    const chatInputArea = document.getElementById('chatInputArea');
    const chatHeader = document.getElementById('chatHeader');
    
    if (initialUI) initialUI.classList.remove('hidden');
    if (chatMessages) chatMessages.classList.add('hidden');
    if (chatInputArea) chatInputArea.classList.add('hidden');
    if (chatHeader) chatHeader.classList.add('hidden');
    
    // Voltar para sidebar no mobile
    const sidebar = document.getElementById('chatSidebar');
    const mainArea = document.getElementById('chatMainArea');
    
    if (window.innerWidth < 1024) {
        // Mobile: mostrar sidebar e ocultar área principal
        if (sidebar) {
            sidebar.classList.remove('hidden');
            sidebar.classList.add('flex');
        }
        if (mainArea) {
            mainArea.classList.add('hidden');
            mainArea.classList.remove('flex');
        }
    }
    
    // Limpar mensagens do chat
    if (chatMessages) {
        chatMessages.innerHTML = '';
    }
    
    // Limpar input de mensagem
    const messageInput = document.getElementById('chatMessageInput');
    if (messageInput) {
        messageInput.value = '';
        messageInput.disabled = true;
    }
    
    // Desabilitar botão de envio
    const sendBtn = document.getElementById('sendMessageBtn');
    if (sendBtn) {
        sendBtn.disabled = true;
    }
    
    // Ocultar picker de emojis se estiver aberto
    const emojiPicker = document.getElementById('emojiPicker');
    if (emojiPicker) {
        emojiPicker.classList.add('hidden');
    }
    
    // Resetar avatar e nome no header (se ainda estiver visível)
    const nameElement = document.getElementById('selectedEmployeeName');
    const initialElement = document.getElementById('selectedEmployeeInitial');
    const statusElement = document.getElementById('selectedEmployeeStatus');
    
    if (nameElement) nameElement.textContent = 'Selecione um funcionário';
    if (statusElement) statusElement.textContent = 'Para começar uma conversa';
    if (initialElement) {
        const avatarContainer = initialElement.parentElement;
        if (avatarContainer) {
            avatarContainer.className = 'w-10 h-10 bg-gradient-to-br from-green-500 to-green-600 rounded-full flex items-center justify-center';
            avatarContainer.innerHTML = '<span class="text-white font-semibold text-sm">?</span>';
        }
    }
}

// Limpar chat (apenas para gerente)
function clearChat() {
    console.log('=== INICIANDO LIMPEZA DO CHAT ===');
    
    if (!window.selectedEmployee) {
        showNotification('Nenhum usuário selecionado para limpar chat', 'warning');
        return;
    }

    console.log('Usuário selecionado:', window.selectedEmployee);
    
    // Mostrar modal de confirmação diretamente
    showClearChatConfirmation();
}

// Função para mostrar modal de confirmação de limpeza do chat
function showClearChatConfirmation() {
    console.log('Mostrando modal de confirmação de limpeza');
    
    // Remover modal existente se houver
    const existingModal = document.getElementById('clearChatModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    const modalHtml = `
        <div id="clearChatModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[99999]">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
                <div class="p-6">
                    <div class="flex items-center justify-center w-12 h-12 mx-auto mb-4 bg-red-100 rounded-full">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 19.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 text-center mb-2">
                        Limpar Conversa
                    </h3>
                    <p class="text-sm text-gray-500 text-center mb-6">
                        Tem certeza que deseja limpar todo o histórico de mensagens desta conversa? Esta ação não pode ser desfeita.
                    </p>
                    <div class="flex space-x-3">
                        <button onclick="closeClearChatModal()" class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md transition-colors">
                            Cancelar
                        </button>
                        <button onclick="confirmClearChat()" class="flex-1 px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md transition-colors">
                            Limpar Chat
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    console.log('Modal de confirmação adicionado ao DOM');
}

// Função para fechar o modal de confirmação
function closeClearChatModal() {
    const modal = document.getElementById('clearChatModal');
    if (modal) {
        modal.remove();
    }
}

// Função para confirmar a limpeza do chat
async function confirmClearChat() {
    console.log('=== CONFIRMANDO LIMPEZA DO CHAT ===');
    closeClearChatModal();

    // Mostrar loading
    showNotification('Limpando conversa...', 'info');

    try {
        // // const supabase = await getSupabaseClient(); // REMOVIDO // REMOVIDO - usando MySQL
        const { data: { user } } = await supabase.auth.getUser();
        
        if (!user) {
            console.error('Usuário não autenticado');
            showNotification('Usuário não autenticado', 'error');
            return;
        }

        console.log('Usuário autenticado:', user.id);

        const { data: userData } = await supabase
            .from('users')
            .select('farm_id')
            .eq('id', user.id)
            .single();

        if (!userData?.farm_id) {
            console.error('Farm ID não encontrado');
            showNotification('Erro ao obter dados da fazenda', 'error');
            return;
        }

        console.log('Farm ID:', userData.farm_id);
        console.log('Funcionário selecionado:', window.selectedEmployee.id);

        // Deletar todas as mensagens entre os usuários
        console.log('Deletando mensagens do banco...');
        
        // Usar uma única query com OR para deletar todas as mensagens entre os dois usuários
        const { error: deleteError } = await supabase
            .from('chat_messages')
            .delete()
            .eq('farm_id', userData.farm_id)
            .or(`and(sender_id.eq.${user.id},receiver_id.eq.${window.selectedEmployee.id}),and(sender_id.eq.${window.selectedEmployee.id},receiver_id.eq.${user.id})`);

        if (deleteError) {
            console.error('Erro ao deletar mensagens:', deleteError);
            showNotification('Erro ao limpar o chat. Tente novamente.', 'error');
            return;
        } else {
            console.log('Todas as mensagens entre os usuários deletadas');
        
        // Verificar se as mensagens foram realmente deletadas
        const { data: remainingMessages, error: checkError } = await supabase
            .from('chat_messages')
            .select('id')
            .eq('farm_id', userData.farm_id)
            .or(`and(sender_id.eq.${user.id},receiver_id.eq.${window.selectedEmployee.id}),and(sender_id.eq.${window.selectedEmployee.id},receiver_id.eq.${user.id})`);

        if (checkError) {
            console.error('Erro ao verificar mensagens restantes:', checkError);
        } else {
            console.log('Mensagens restantes após delete:', remainingMessages?.length || 0);
            if (remainingMessages && remainingMessages.length > 0) {
                console.warn('Ainda existem mensagens no banco!');
                showNotification('Algumas mensagens não foram deletadas. Tente novamente.', 'warning');
            }
        }
        }

        // Limpar interface
        const chatMessages = document.getElementById('chatMessages');
        if (chatMessages) {
            chatMessages.innerHTML = '';
            console.log('Interface do chat limpa');
        }

        // Limpar cache
        if (window.chatMessagesCache) {
            window.chatMessagesCache.clear();
            console.log('Cache de mensagens limpo');
        }

        // Limpar cache de mensagens em tempo real
        if (window.chatMessages) {
            window.chatMessages = [];
            console.log('Array de mensagens limpo');
        }

        // Limpar timestamp da última mensagem
        lastMessageTimestamp = null;
        lastMessageCount = 0;

        // Forçar recarga das mensagens para garantir que não há mensagens restantes
        if (window.selectedEmployee && window.selectedEmployee.id) {
            setTimeout(async () => {
                console.log('Forçando recarga das mensagens...');
                await loadChatMessages(window.selectedEmployee.id, false);
            }, 500);
        }

        console.log('Chat limpo com sucesso');
        showNotification('Conversa limpa com sucesso!', 'success');

    } catch (error) {
        console.error('Erro ao limpar chat:', error);
        showNotification('Erro ao limpar o chat. Tente novamente.', 'error');
    }
}

// Toggle sidebar em mobile
function toggleChatSidebar() {
    const sidebar = document.getElementById('chatSidebar');
    const mainArea = document.getElementById('chatMainArea');
    
    if (window.innerWidth < 1024) {
        // Mobile: alternar entre sidebar e área principal
        if (sidebar && mainArea) {
            const isSidebarVisible = !sidebar.classList.contains('hidden');
            
            if (isSidebarVisible) {
                // Ocultar sidebar e mostrar área principal
                sidebar.classList.add('hidden');
                sidebar.classList.remove('flex');
                mainArea.classList.remove('hidden');
                mainArea.classList.add('flex');
            } else {
                // Mostrar sidebar e ocultar área principal
                sidebar.classList.remove('hidden');
                sidebar.classList.add('flex');
                mainArea.classList.add('hidden');
                mainArea.classList.remove('flex');
            }
        }
    }
}

// Função para reproduzir áudio
function playAudio(audioUrl) {
    console.log('Reproduzindo áudio:', audioUrl);
    const audio = new Audio(audioUrl);
    audio.play().catch(error => {
        console.error('Erro ao reproduzir áudio:', error);
        showNotification('Erro ao reproduzir áudio', 'error');
    });
}

// Função para formatar tamanho de arquivo
function formatFileSize(bytes) {
    if (!bytes) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
}

// Carregar mensagens do chat com funcionário específico
// Cache para mensagens do chat
let chatMessagesCache = new Map();
let lastMessageTimestamp = null;

async function loadChatMessages(employeeId = null, isPolling = false) {
    try {
        // // const supabase = await getSupabaseClient(); // REMOVIDO // REMOVIDO - usando MySQL
        const { data: { user } } = await supabase.auth.getUser();
        
        if (!user) return;

        // Definir currentUser globalmente
        window.currentUser = user;

        // Buscar farm_id do usuário
        const { data: userData } = await supabase
            .from('users')
            .select('farm_id')
            .eq('id', user.id)
            .single();

        if (!userData?.farm_id) return;

        // Verificar cache apenas para polling (evitar recarregamento desnecessário)
        if (isPolling) {
            const cacheKey = `${userData.farm_id}_${user.id}_${employeeId}`;
            const cachedData = chatMessagesCache.get(cacheKey);
            
            if (cachedData && (Date.now() - cachedData.timestamp < 2000)) { // Cache reduzido para 2 segundos
                console.log('📨 Usando cache para polling, evitando recarregamento');
                return;
            }
        }

        // Usar o serviço de sincronização para buscar mensagens
        console.log('📨 Buscando mensagens para:', { farmId: userData.farm_id, userId: user.id, employeeId, isPolling });
        const messages = await getChatMessages(userData.farm_id, user.id, employeeId);
        console.log('📨 Mensagens encontradas:', messages?.length || 0);
        
        // Atualizar cache
        if (messages && messages.length > 0) {
            const cacheKey = `${userData.farm_id}_${user.id}_${employeeId}`;
            chatMessagesCache.set(cacheKey, {
                messages: messages,
                timestamp: Date.now()
            });
            
            // Verificar se há mensagens novas
            const latestMessage = messages[messages.length - 1];
            if (lastMessageTimestamp && latestMessage.created_at > lastMessageTimestamp) {
                console.log('📨 Nova mensagem detectada, atualizando display');
        displayChatMessages(messages);
                lastMessageTimestamp = latestMessage.created_at;
            } else if (!lastMessageTimestamp) {
                // Primeira carga
                displayChatMessages(messages);
                lastMessageTimestamp = latestMessage.created_at;
            } else {
                console.log('📨 Nenhuma mensagem nova, mantendo display atual');
            }
        } else {
            displayChatMessages(messages);
        }
    } catch (error) {
        console.error('Erro ao carregar chat:', error);
    }
}

// Função para gerar cor baseada no nome do usuário
function generateUserColor(name) {
    if (!name) return 'from-gray-500 to-gray-600';
    
    // Array de cores disponíveis
    const colors = [
        'from-green-500 to-green-600',
        'from-blue-500 to-blue-600', 
        'from-purple-500 to-purple-600',
        'from-pink-500 to-pink-600',
        'from-red-500 to-red-600',
        'from-yellow-500 to-yellow-600',
        'from-indigo-500 to-indigo-600',
        'from-teal-500 to-teal-600',
        'from-orange-500 to-orange-600',
        'from-cyan-500 to-cyan-600'
    ];
    
    // Gerar índice baseado no nome
    let hash = 0;
    for (let i = 0; i < name.length; i++) {
        hash = name.charCodeAt(i) + ((hash << 5) - hash);
    }
    
    return colors[Math.abs(hash) % colors.length];
}

// Cache para elementos de mensagem
let lastMessageCount = 0;
let isUserAtBottom = true;
let newMessageIndicator = null;

// Função para verificar se usuário está no final do chat
function checkIfUserAtBottom() {
    const chatContainer = document.getElementById('chatMessages');
    if (!chatContainer) return true;
    
    const threshold = 100; // pixels do final
    const isAtBottom = chatContainer.scrollTop + chatContainer.clientHeight >= chatContainer.scrollHeight - threshold;
    isUserAtBottom = isAtBottom;
    return isAtBottom;
}

// Função para scroll suave para o final
function scrollToBottom(smooth = true) {
    const chatContainer = document.getElementById('chatMessages');
    if (!chatContainer) {
        console.log('❌ Container de chat não encontrado para scroll');
        return;
    }
    
    console.log('📜 Fazendo scroll para o final:', {
        scrollHeight: chatContainer.scrollHeight,
        clientHeight: chatContainer.clientHeight,
        scrollTop: chatContainer.scrollTop
    });
    
    // Forçar scroll imediato primeiro
    chatContainer.scrollTop = chatContainer.scrollHeight;
    
    // Depois aplicar scroll suave se solicitado
    if (smooth) {
        setTimeout(() => {
            chatContainer.scrollTo({
                top: chatContainer.scrollHeight,
                behavior: 'smooth'
            });
        }, 50);
    }
    
    // Atualizar status de posição
    setTimeout(() => {
        isUserAtBottom = true;
        hideNewMessageIndicator();
    }, 100);
}

// Função para mostrar indicador de nova mensagem
function showNewMessageIndicator() {
    if (newMessageIndicator) return; // Já existe
    
    const chatContainer = document.getElementById('chatMessages');
    if (!chatContainer) return;
    
    newMessageIndicator = document.createElement('div');
    newMessageIndicator.className = 'fixed bottom-20 right-4 bg-green-500 text-white px-4 py-2 rounded-full shadow-lg cursor-pointer z-50 flex items-center space-x-2 animate-bounce';
    newMessageIndicator.innerHTML = `
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
        </svg>
        <span class="text-sm font-medium">Nova mensagem</span>
    `;
    
    newMessageIndicator.onclick = () => {
        scrollToBottom();
        hideNewMessageIndicator();
    };
    
    document.body.appendChild(newMessageIndicator);
    
    // Auto-hide após 5 segundos
    setTimeout(() => {
        hideNewMessageIndicator();
    }, 5000);
}

// Função para esconder indicador de nova mensagem
function hideNewMessageIndicator() {
    if (newMessageIndicator) {
        newMessageIndicator.remove();
        newMessageIndicator = null;
    }
}

// Função para mostrar indicador de digitando
function showTypingIndicator(senderName) {
    const chatContainer = document.getElementById('chatMessages');
    if (!chatContainer) return;
    
    // Remover indicador anterior se existir
    hideTypingIndicator();
    
    const typingDiv = document.createElement('div');
    typingDiv.id = 'typingIndicator';
    typingDiv.className = 'flex justify-start mb-4';
    
    const userColor = generateUserColor(senderName);
    const senderInitial = senderName.charAt(0).toUpperCase();
    
    typingDiv.innerHTML = `
        <div class="max-w-xs lg:max-w-md">
            <div class="flex items-end space-x-2">
                <div class="w-8 h-8 bg-gradient-to-br ${userColor} rounded-full flex items-center justify-center flex-shrink-0">
                    <span class="text-white font-semibold text-xs">${senderInitial}</span>
                </div>
                <div class="flex flex-col items-start">
                    <div class="px-4 py-2 rounded-2xl bg-white text-gray-900 shadow-sm">
                        <div class="flex items-center space-x-1">
                            <div class="typing-dot"></div>
                            <div class="typing-dot"></div>
                            <div class="typing-dot"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    chatContainer.appendChild(typingDiv);
    
    // Scroll para o final quando mostrar indicador
    setTimeout(() => {
        scrollToBottom(true);
    }, 100);
}

// Função para esconder indicador de digitando
function hideTypingIndicator() {
    const typingIndicator = document.getElementById('typingIndicator');
    if (typingIndicator) {
        typingIndicator.remove();
    }
}

// Cache para status de leitura das mensagens
let messageReadStatus = new Map();

// Função para determinar status de leitura da mensagem
function getReadStatus(message) {
    const messageId = message.id || `${message.created_at}_${message.sender_id}`;
    
    // Verificar se já temos status armazenado
    if (messageReadStatus.has(messageId)) {
        return messageReadStatus.get(messageId);
    }
    
    // Verificar se o destinatário está online
    const isRecipientOnline = window.selectedEmployee && isEmployeeOnline(window.selectedEmployee);
    
    // Simular status baseado no tempo da mensagem e se destinatário está online
    const messageTime = new Date(message.created_at);
    const now = new Date();
    const timeDiff = (now - messageTime) / 1000; // diferença em segundos
    
    let statusHtml;
    
    if (timeDiff < 1) {
        // Mensagem muito recente - apenas enviada (um verificado cinza)
        statusHtml = '<svg class="w-4 h-3 text-gray-400" fill="currentColor" viewBox="0 0 16 12"><path d="M6.5 9.5L3 6l1.4-1.4L6.5 6.7l5.1-5.1L13 2.8l-6.5 6.7z"></path></svg>';
    } else if (isRecipientOnline && timeDiff > 2) {
        // Destinatário online e tempo suficiente - mensagem lida (dois verificados azuis)
        statusHtml = `
            <div class="relative w-5 h-3">
                <svg class="absolute w-4 h-3 text-blue-500" fill="currentColor" viewBox="0 0 16 12">
                    <path d="M6.5 9.5L3 6l1.4-1.4L6.5 6.7l5.1-5.1L13 2.8l-6.5 6.7z"></path>
                </svg>
                <svg class="absolute w-4 h-3 text-blue-500" fill="currentColor" viewBox="0 0 16 12" style="left: 4px;">
                    <path d="M6.5 9.5L3 6l1.4-1.4L6.5 6.7l5.1-5.1L13 2.8l-6.5 6.7z"></path>
                </svg>
            </div>
        `;
    } else if (isRecipientOnline) {
        // Destinatário online - mensagem entregue (dois verificados cinza)
        statusHtml = `
            <div class="relative w-5 h-3">
                <svg class="absolute w-4 h-3 text-gray-400" fill="currentColor" viewBox="0 0 16 12">
                    <path d="M6.5 9.5L3 6l1.4-1.4L6.5 6.7l5.1-5.1L13 2.8l-6.5 6.7z"></path>
                </svg>
                <svg class="absolute w-4 h-3 text-gray-400" fill="currentColor" viewBox="0 0 16 12" style="left: 4px;">
                    <path d="M6.5 9.5L3 6l1.4-1.4L6.5 6.7l5.1-5.1L13 2.8l-6.5 6.7z"></path>
                </svg>
            </div>
        `;
    } else {
        // Destinatário offline - apenas enviada (um verificado cinza)
        statusHtml = '<svg class="w-4 h-3 text-gray-400" fill="currentColor" viewBox="0 0 16 12"><path d="M6.5 9.5L3 6l1.4-1.4L6.5 6.7l5.1-5.1L13 2.8l-6.5 6.7z"></path></svg>';
    }
    
    // Armazenar status para evitar recálculo
    messageReadStatus.set(messageId, statusHtml);
    
    // Simular progressão do status ao longo do tempo
    if (timeDiff < 2 && isRecipientOnline) {
        setTimeout(() => {
            updateMessageReadStatus(messageId, 'delivered');
        }, 2000 - (timeDiff * 1000));
    }
    
    if (timeDiff < 5 && isRecipientOnline) {
        setTimeout(() => {
            updateMessageReadStatus(messageId, 'read');
        }, 5000 - (timeDiff * 1000));
    }
    
    return statusHtml;
}

// Função para atualizar status de leitura de uma mensagem específica
function updateMessageReadStatus(messageId, status) {
    let statusHtml;
    
    if (status === 'delivered') {
        // Dois verificados cinza
        statusHtml = `
            <div class="relative w-5 h-3">
                <svg class="absolute w-4 h-3 text-gray-400" fill="currentColor" viewBox="0 0 16 12">
                    <path d="M6.5 9.5L3 6l1.4-1.4L6.5 6.7l5.1-5.1L13 2.8l-6.5 6.7z"></path>
                </svg>
                <svg class="absolute w-4 h-3 text-gray-400" fill="currentColor" viewBox="0 0 16 12" style="left: 4px;">
                    <path d="M6.5 9.5L3 6l1.4-1.4L6.5 6.7l5.1-5.1L13 2.8l-6.5 6.7z"></path>
                </svg>
            </div>
        `;
    } else if (status === 'read') {
        // Dois verificados azuis
        statusHtml = `
            <div class="relative w-5 h-3">
                <svg class="absolute w-4 h-3 text-blue-500" fill="currentColor" viewBox="0 0 16 12">
                    <path d="M6.5 9.5L3 6l1.4-1.4L6.5 6.7l5.1-5.1L13 2.8l-6.5 6.7z"></path>
                </svg>
                <svg class="absolute w-4 h-3 text-blue-500" fill="currentColor" viewBox="0 0 16 12" style="left: 4px;">
                    <path d="M6.5 9.5L3 6l1.4-1.4L6.5 6.7l5.1-5.1L13 2.8l-6.5 6.7z"></path>
                </svg>
            </div>
        `;
    }
    
    if (statusHtml) {
        messageReadStatus.set(messageId, statusHtml);
        // Atualizar visualmente no chat
        updateReadStatusInChat(messageId, statusHtml);
    }
}

// Função para atualizar status de leitura visualmente no chat
function updateReadStatusInChat(messageId, statusHtml) {
    const chatContainer = document.getElementById('chatMessages');
    if (!chatContainer) return;
    
    // Encontrar a mensagem específica e atualizar seu status
    const messages = chatContainer.querySelectorAll('[data-message-id]');
    messages.forEach(messageElement => {
        if (messageElement.getAttribute('data-message-id') === messageId) {
            const readStatusElement = messageElement.querySelector('.read-status');
            if (readStatusElement) {
                readStatusElement.innerHTML = statusHtml;
            }
        }
    });
}

// Exibir mensagens no chat
function displayChatMessages(messages) {
    console.log('🎨 Exibindo mensagens no gerente:', messages?.length || 0);
    const chatContainer = document.getElementById('chatMessages');
    if (!chatContainer) {
        console.error('❌ Container de mensagens não encontrado no gerente');
        return;
    }

    // Esconder indicador de digitando quando exibir mensagens
    hideTypingIndicator();

    // Verificar se usuário está no final antes de atualizar
    const wasAtBottom = checkIfUserAtBottom();
    const hadMessages = lastMessageCount > 0;
    const hasNewMessages = messages.length > lastMessageCount;

    // Verificar se precisa atualizar (evitar recarregamento desnecessário)
    if (messages.length === lastMessageCount && messages.length > 0 && !hasNewMessages) {
        console.log('📨 Mesmo número de mensagens, evitando recarregamento');
        return;
    }

    chatContainer.innerHTML = '';
    lastMessageCount = messages.length;

    if (messages.length === 0) {
        chatContainer.innerHTML = `
            <div class="text-center py-12">
                <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                </svg>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Nenhuma mensagem ainda</h3>
                <p class="text-gray-500">Seja o primeiro a enviar uma mensagem!</p>
            </div>
        `;
        return;
    }

    messages.forEach(message => {
        // Verificar se é uma mensagem de chamada
        if (message.call_data) {
            console.log('=== MENSAGEM DE CHAMADA DETECTADA ===');
            console.log('Message:', message);
            console.log('Call data:', message.call_data);
            
            // Verificar se a mensagem é muito antiga (mais de 5 minutos)
            const messageTime = new Date(message.created_at);
            const now = new Date();
            const timeDiff = now - messageTime;
            const fiveMinutes = 5 * 60 * 1000;
            
            if (timeDiff > fiveMinutes) {
                console.log('Mensagem de chamada muito antiga, ignorando');
                return;
            }
            
            handleCallMessage(message);
            return; // Não exibir mensagem de chamada no chat
        }
        
        // Não exibir mensagens vazias (exceto se tiver file_data)
        if ((!message.message || message.message.trim() === '') && !message.file_data) {
            return;
        }
        
        const isCurrentUser = message.sender_id === (window.currentUser?.id || '');
        const messageDiv = document.createElement('div');
        messageDiv.className = `flex ${isCurrentUser ? 'justify-end' : 'justify-start'} mb-4`;
        
        // Usar sender_name se disponível, senão usar 'Usuário'
        const senderName = message.sender_name || 'Usuário';
        
        // Usar sender_name se disponível, senão usar '?'
        const senderInitial = senderName.charAt(0).toUpperCase();
        const messageTime = new Date(message.created_at).toLocaleTimeString('pt-BR', { 
            hour: '2-digit', 
            minute: '2-digit' 
        });
        
        // Verificar se tem foto de perfil
        const hasPhoto = message.sender_photo && message.sender_photo.trim() !== '';
        const userColor = generateUserColor(senderName);
        
        // Gerar avatar (foto ou letra) - sem timestamp para evitar recarregamento
        let avatarHtml;
        if (hasPhoto) {
            avatarHtml = `
                <img src="${message.sender_photo}" 
                     alt="Foto de ${senderName}" 
                     class="w-8 h-8 rounded-full object-cover flex-shrink-0"
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                     onload="this.nextElementSibling.style.display='none';">
                <div class="w-8 h-8 bg-gradient-to-br ${userColor} rounded-full flex items-center justify-center flex-shrink-0" style="display: flex;">
                    <span class="text-white font-semibold text-xs">${senderInitial}</span>
                </div>
            `;
        } else {
            avatarHtml = `
                <div class="w-8 h-8 bg-gradient-to-br ${userColor} rounded-full flex items-center justify-center flex-shrink-0">
                    <span class="text-white font-semibold text-xs">${senderInitial}</span>
                </div>
            `;
        }
        
        // Gerar ícones de verificação para mensagens do usuário atual
        let readReceiptHtml = '';
        if (isCurrentUser) {
            const readStatus = getReadStatus(message);
            readReceiptHtml = `
                <div class="flex items-center space-x-1 mt-1">
                    ${readStatus}
                </div>
            `;
        }

        const messageId = message.id || `${message.created_at}_${message.sender_id}`;
        
        messageDiv.setAttribute('data-message-id', messageId);
        messageDiv.innerHTML = `
            <div class="max-w-xs lg:max-w-md">
                <div class="flex items-end space-x-2 ${isCurrentUser ? 'flex-row-reverse space-x-reverse' : ''}">
                    <div class="relative">
                        ${avatarHtml}
                    </div>
                    <div class="flex flex-col ${isCurrentUser ? 'items-end' : 'items-start'}">
                        <div class="px-4 py-2 rounded-2xl ${isCurrentUser ? 'bg-green-500 text-white' : 'bg-white text-gray-900'} shadow-sm">
                            ${message.file_data && message.file_data.type === 'audio' ? 
                                `<div class="flex items-center space-x-3">
                                    <button onclick="playAudio('${message.file_data.url}')" class="flex items-center justify-center w-10 h-10 rounded-full ${isCurrentUser ? 'bg-white bg-opacity-20' : 'bg-green-500'} hover:bg-opacity-30 transition-colors">
                                        <svg class="w-5 h-5 ${isCurrentUser ? 'text-white' : 'text-white'}" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M8 5v14l11-7z"/>
                                        </svg>
                                    </button>
                                    <div class="flex flex-col">
                                        <span class="text-sm font-medium">${message.file_data.name || 'Mensagem de voz'}</span>
                                        <span class="text-xs opacity-75">${formatFileSize(message.file_data.size)}</span>
                                    </div>
                                </div>` :
                                `<p class="text-sm">${message.message}</p>`
                            }
                        </div>
                        <div class="flex items-center space-x-1 mt-1">
                            <span class="text-xs text-gray-500">${messageTime}</span>
                            <div class="read-status">${readReceiptHtml}</div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        chatContainer.appendChild(messageDiv);
    });

    // Lógica de scroll inteligente
    setTimeout(() => {
        console.log('📨 Verificando scroll:', { 
            wasAtBottom, 
            hadMessages, 
            hasNewMessages,
            messageCount: messages.length,
            lastCount: lastMessageCount
        });
        
        if (wasAtBottom || !hadMessages || hasNewMessages) {
            // Usuário estava no final, é primeira carga, ou há mensagens novas - scroll automático
            console.log('📨 Fazendo scroll automático');
            scrollToBottom(true);
        } else if (hasNewMessages && !wasAtBottom) {
            // Há mensagens novas e usuário não está no final - mostrar indicador
            console.log('📨 Mostrando indicador de nova mensagem');
            showNewMessageIndicator();
        }
    }, 200); // Aumentado para 200ms para garantir que o DOM foi atualizado
}

// Enviar mensagem
async function sendChatMessageLocal() {
    const messageInput = document.getElementById('chatMessageInput');
    const message = messageInput.value.trim();
    
    if (!message || !window.selectedEmployee) return;

    // Mostrar indicador de digitando
    showTypingIndicator(window.currentUser?.name || 'Você');

    try {
        // // const supabase = await getSupabaseClient(); // REMOVIDO // REMOVIDO - usando MySQL
        const { data: { user } } = await supabase.auth.getUser();
        
        if (!user) return;

        // Buscar farm_id do usuário
        const { data: userData } = await supabase
            .from('users')
            .select('farm_id')
            .eq('id', user.id)
            .single();

        if (!userData?.farm_id) return;

        // Usar o serviço de sincronização para enviar mensagem
        await sendChatMessage({
            farm_id: userData.farm_id,
            sender_id: user.id,
            receiver_id: window.selectedEmployee.id,
            message: message
        });

        // Limpar input IMEDIATAMENTE após enviar
        messageInput.value = '';
        
        // As mensagens serão atualizadas automaticamente via real-time
        console.log('✅ Mensagem enviada, aguardando atualização via real-time...');
        
        // Fazer scroll para o final após enviar mensagem
        setTimeout(() => {
            scrollToBottom(true);
        }, 100);
                
                // Manter foco no input
                    messageInput.focus();
        
    } catch (error) {
        console.error('Erro ao enviar mensagem:', error);
        showNotification('Erro ao enviar mensagem', 'error');
        // Esconder indicador de digitando em caso de erro
        hideTypingIndicator();
    }
}

// Enviar mensagem com Enter
function handleChatKeyPress(event) {
    if (event.key === 'Enter') {
        sendChatMessageLocal();
    }
}

// ==================== FUNÇÕES DE EMOJI E CLIPES ====================

// Toggle do picker de emojis
function toggleEmojiPicker() {
    const emojiPicker = document.getElementById('emojiPicker');
    if (emojiPicker) {
        emojiPicker.classList.toggle('hidden');
    }
}

// Inserir emoji no input
function insertEmoji(emoji) {
    const messageInput = document.getElementById('chatMessageInput');
    if (messageInput) {
        const currentValue = messageInput.value;
        const cursorPos = messageInput.selectionStart;
        const newValue = currentValue.slice(0, cursorPos) + emoji + currentValue.slice(cursorPos);
        messageInput.value = newValue;
        
        // Reposicionar cursor após o emoji
        messageInput.setSelectionRange(cursorPos + emoji.length, cursorPos + emoji.length);
        messageInput.focus();
        
        // Esconder picker de emojis
        toggleEmojiPicker();
    }
}

// Toggle do input de arquivo
function toggleFileInput() {
    const fileInput = document.getElementById('fileInput');
    if (fileInput) {
        fileInput.click();
    }
}

// Lidar com seleção de arquivo
function handleFileSelect(event) {
    const file = event.target.files[0];
    if (!file) return;

    // Verificar tamanho do arquivo (máximo 10MB)
    const maxSize = 10 * 1024 * 1024; // 10MB
    if (file.size > maxSize) {
        showNotification('Arquivo muito grande. Máximo permitido: 10MB', 'error');
        return;
    }

    // Verificar tipo de arquivo
    const allowedTypes = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        'video/mp4', 'video/webm', 'video/ogg',
        'audio/mp3', 'audio/wav', 'audio/ogg',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'text/plain'
    ];

    if (!allowedTypes.includes(file.type)) {
        showNotification('Tipo de arquivo não suportado', 'error');
        return;
    }

    // Enviar arquivo
    sendFileMessage(file);
}

// Enviar mensagem com arquivo
async function sendFileMessage(file) {
    if (!window.selectedEmployee) {
        showNotification('Selecione um funcionário primeiro', 'error');
        return;
    }

    try {
        // // const supabase = await getSupabaseClient(); // REMOVIDO // REMOVIDO - usando MySQL
        const { data: { user } } = await supabase.auth.getUser();
        
        if (!user) return;

        // Buscar farm_id do usuário
        const { data: userData } = await supabase
            .from('users')
            .select('farm_id')
            .eq('id', user.id)
            .single();

        if (!userData?.farm_id) return;

        // Mostrar loading
        showNotification('Enviando arquivo...', 'info');

        // Upload do arquivo para Supabase Storage
        const fileExt = file.name.split('.').pop();
        const fileName = `${Date.now()}_${Math.random().toString(36).substring(2)}.${fileExt}`;
        const filePath = `chat-files/${userData.farm_id}/${fileName}`;

        const { data: uploadData, error: uploadError } = await supabase.storage
            .from('chat-files')
            .upload(filePath, file);

        if (uploadError) {
            console.error('Erro no upload:', uploadError);
            showNotification('Erro ao enviar arquivo', 'error');
            return;
        }

        // Obter URL pública do arquivo
        const { data: { publicUrl } } = supabase.storage
            .from('chat-files')
            .getPublicUrl(filePath);

        // Criar mensagem com arquivo
        const fileMessage = {
            type: getFileType(file.type),
            name: file.name,
            size: file.size,
            url: publicUrl
        };

        // Enviar mensagem
        await sendChatMessage({
            farm_id: userData.farm_id,
            sender_id: user.id,
            receiver_id: window.selectedEmployee.id,
            message: `📎 ${file.name}`,
            file_data: fileMessage
        });

        showNotification('Arquivo enviado com sucesso!', 'success');
        
        // Limpar input de arquivo
        const fileInput = document.getElementById('fileInput');
        if (fileInput) {
            fileInput.value = '';
        }

    } catch (error) {
        console.error('Erro ao enviar arquivo:', error);
        showNotification('Erro ao enviar arquivo', 'error');
    }
}

// Determinar tipo de arquivo
function getFileType(mimeType) {
    if (mimeType.startsWith('image/')) return 'image';
    if (mimeType.startsWith('video/')) return 'video';
    if (mimeType.startsWith('audio/')) return 'audio';
    if (mimeType === 'application/pdf') return 'pdf';
    if (mimeType.includes('word') || mimeType.includes('document')) return 'document';
    return 'file';
}

// Esconder picker de emojis ao clicar fora
document.addEventListener('click', function(event) {
    const emojiPicker = document.getElementById('emojiPicker');
    const emojiButton = event.target.closest('[onclick="toggleEmojiPicker()"]');
    
    if (emojiPicker && !emojiPicker.contains(event.target) && !emojiButton) {
        emojiPicker.classList.add('hidden');
    }
});

// ==================== FUNÇÕES DE CATEGORIAS DE EMOJIS ====================

// Mostrar categoria de emojis
function showEmojiCategory(category) {
    // Esconder todas as categorias
    const categories = document.querySelectorAll('.emoji-category');
    categories.forEach(cat => cat.classList.add('hidden'));
    
    // Mostrar categoria selecionada
    const selectedCategory = document.getElementById('emoji' + category.charAt(0).toUpperCase() + category.slice(1));
    if (selectedCategory) {
        selectedCategory.classList.remove('hidden');
        
        // Carregar emojis se ainda não foram carregados
        if (selectedCategory.children.length === 0) {
            loadEmojiCategory(category);
        }
    }
    
    // Atualizar botões de categoria
    const categoryBtns = document.querySelectorAll('.emoji-category-btn');
    categoryBtns.forEach(btn => {
        btn.classList.remove('bg-green-100', 'text-green-700');
        btn.classList.add('hover:bg-gray-200');
    });
    
    // Destacar botão selecionado
    const selectedBtn = event.target;
    selectedBtn.classList.add('bg-green-100', 'text-green-700');
    selectedBtn.classList.remove('hover:bg-gray-200');
}

// Carregar emojis por categoria
function loadEmojiCategory(category) {
    const container = document.getElementById('emoji' + category.charAt(0).toUpperCase() + category.slice(1));
    if (!container) return;

    const emojis = {
        gestures: ['👋', '🤚', '🖐️', '✋', '🖖', '👌', '🤏', '✌️', '🤞', '🤟', '🤘', '🤙', '👈', '👉', '👆', '🖕', '👇', '☝️', '👍', '👎', '👊', '✊', '🤛', '🤜', '👏', '🙌', '👐', '🤲', '🤝', '🙏', '✍️', '💅', '🤳', '💪', '🦾', '🦿', '🦵', '🦶', '👂', '🦻', '👃', '🧠', '🦷', '🦴', '👀', '👁️', '👅', '👄', '💋', '🩸'],
        objects: ['📱', '📲', '☎️', '📞', '📟', '📠', '🔋', '🔌', '💻', '🖥️', '🖨️', '⌨️', '🖱️', '🖲️', '💽', '💾', '💿', '📀', '🧮', '🎥', '📷', '📸', '📹', '📼', '🔍', '🔎', '🕯️', '💡', '🔦', '🏮', '🪔', '📔', '📕', '📖', '📗', '📘', '📙', '📚', '📓', '📒', '📃', '📜', '📄', '📰', '🗞️', '📑', '🔖', '🏷️', '💰', '💴', '💵', '💶', '💷', '💸', '💳', '🧾', '💎', '⚖️', '🧰', '🔧', '🔨', '⚒️', '🛠️', '⛏️', '🔩', '⚙️', '🧱', '⛓️', '🧲', '🔫', '💣', '🧨', '🪓', '🔪', '🗡️', '⚔️', '🛡️', '🚬', '⚰️', '🪦', '⚱️', '🏺', '🔮', '📿', '🧿', '💈', '⚗️', '🔭', '🔬', '🕳️', '🩹', '🩺', '💊', '💉', '🧬', '🦠', '🧫', '🧪', '🌡️', '🧹', '🧺', '🧻', '🚽', '🚰', '🚿', '🛁', '🛀', '🧴', '🧷', '🧸', '🧵', '🧶', '🪡', '🪢', '🪣', '🪤', '🪥', '🪦', '🪧', '🪨', '🪩', '🪪', '🪫', '🪬', '🪭', '🪮', '🪯', '🪰', '🪱', '🪲', '🪳', '🪴', '🪵', '🪶', '🪷', '🪸', '🪹', '🪺', '🪻', '🪼', '🪽', '🪾', '🪿', '🫀', '🫁', '🫂', '🫃', '🫄', '🫅', '🫆', '🫇', '🫈', '🫉', '🫊', '🫋', '🫌', '🫍', '🫎', '🫏', '🫐', '🫑', '🫒', '🫓', '🫔', '🫕', '🫖', '🫗', '🫘', '🫙', '🫚', '🫛', '🫜', '🫝', '🫞', '🫟', '🫠', '🫡', '🫢', '🫣', '🫤', '🫥', '🫦', '🫧', '🫨', '🫩', '🫪', '🫫', '🫬', '🫭', '🫮', '🫯', '🫰', '🫱', '🫲', '🫳', '🫴', '🫵', '🫶', '🫷', '🫸', '🫹', '🫺', '🫻', '🫼', '🫽', '🫾', '🫿', '🬀', '🬁', '🬂', '🬃', '🬄', '🬅', '🬆', '🬇', '🬈', '🬉', '🬊', '🬋', '🬌', '🬍', '🬎', '🬏', '🬐', '🬑', '🬒', '🬓', '🬔', '🬕', '🬖', '🬗', '🬘', '🬙', '🬚', '🬛', '🬜', '🬝', '🬞', '🬟', '🬠', '🬡', '🬢', '🬣', '🬤', '🬥', '🬦', '🬧', '🬨', '🬩', '🬪', '🬫', '🬬', '🬭', '🬮', '🬯', '🬰', '🬱', '🬲', '🬳', '🬴', '🬵', '🬶', '🬷', '🬸', '🬹', '🬺', '🬻', '🬼', '🬽', '🬾', '🬿', '🭀', '🭁', '🭂', '🭃', '🭄', '🭅', '🭆', '🭇', '🭈', '🭉', '🭊', '🭋', '🭌', '🭍', '🭎', '🭏', '🭐', '🭑', '🭒', '🭓', '🭔', '🭕', '🭖', '🭗', '🭘', '🭙', '🭚', '🭛', '🭜', '🭝', '🭞', '🭟', '🭠', '🭡', '🭢', '🭣', '🭤', '🭥', '🭦', '🭧', '🭨', '🭩', '🭪', '🭫', '🭬', '🭭', '🭮', '🭯', '🭰', '🭱', '🭲', '🭳', '🭴', '🭵', '🭶', '🭷', '🭸', '🭹', '🭺', '🭻', '🭼', '🭽', '🭾', '🭿', '🮀', '🮁', '🮂', '🮃', '🮄', '🮅', '🮆', '🮇', '🮈', '🮉', '🮊', '🮋', '🮌', '🮍', '🮎', '🮏', '🮐', '🮑', '🮒', '🮓', '🮔', '🮕', '🮖', '🮗', '🮘', '🮙', '🮚', '🮛', '🮜', '🮝', '🮞', '🮟', '🮠', '🮡', '🮢', '🮣', '🮤', '🮥', '🮦', '🮧', '🮨', '🮩', '🮪', '🮫', '🮬', '🮭', '🮮', '🮯', '🮰', '🮱', '🮲', '🮳', '🮴', '🮵', '🮶', '🮷', '🮸', '🮹', '🮺', '🮻', '🮼', '🮽', '🮾', '🮿', '🯀', '🯁', '🯂', '🯃', '🯄', '🯅', '🯆', '🯇', '🯈', '🯉', '🯊', '🯋', '🯌', '🯍', '🯎', '🯏', '🯐', '🯑', '🯒', '🯓', '🯔', '🯕', '🯖', '🯗', '🯘', '🯙', '🯚', '🯛', '🯜', '🯝', '🯞', '🯟', '🯠', '🯡', '🯢', '🯣', '🯤', '🯥', '🯦', '🯧', '🯨', '🯩', '🯪', '🯫', '🯬', '🯭', '🯮', '🯯', '🯰', '🯱', '🯲', '🯳', '🯴', '🯵', '🯶', '🯷', '🯸', '🯹', '🯺', '🯻', '🯼', '🯽', '🯾', '🯿', '🰀', '🰁', '🰂', '🰃', '🰄', '🰅', '🰆', '🰇', '🰈', '🰉', '🰊', '🰋', '🰌', '🰍', '🰎', '🰏', '🰐', '🰑', '🰒', '🰓', '🰔', '🰕', '🰖', '🰗', '🰘', '🰙', '🰚', '🰛', '🰜', '🰝', '🰞', '🰟', '🰠', '🰡', '🰢', '🰣', '🰤', '🰥', '🰦', '🰧', '🰨', '🰩', '🰪', '🰫', '🰬', '🰭', '🰮', '🰯', '🰰', '🰱', '🰲', '🰳', '🰴', '🰵', '🰶', '🰷', '🰸', '🰹', '🰺', '🰻', '🰼', '🰽', '🰾', '🰿', '🱀', '🱁', '🱂', '🱃', '🱄', '🱅', '🱆', '🱇', '🱈', '🱉', '🱊', '🱋', '🱌', '🱍', '🱎', '🱏', '🱐', '🱑', '🱒', '🱓', '🱔', '🱕', '🱖', '🱗', '🱘', '🱙', '🱚', '🱛', '🱜', '🱝', '🱞', '🱟', '🱠', '🱡', '🱢', '🱣', '🱤', '🱥', '🱦', '🱧', '🱨', '🱩', '🱪', '🱫', '🱬', '🱭', '🱮', '🱯', '🱰', '🱱', '🱲', '🱳', '🱴', '🱵', '🱶', '🱷', '🱸', '🱹', '🱺', '🱻', '🱼', '🱽', '🱾', '🱿', '🲀', '🲁', '🲂', '🲃', '🲄', '🲅', '🲆', '🲇', '🲈', '🲉', '🲊', '🲋', '🲌', '🲍', '🲎', '🲏', '🲐', '🲑', '🲒', '🲓', '🲔', '🲕', '🲖', '🲗', '🲘', '🲙', '🲚', '🲛', '🲜', '🲝', '🲞', '🲟', '🲠', '🲡', '🲢', '🲣', '🲤', '🲥', '🲦', '🲧', '🲨', '🲩', '🲪', '🲫', '🲬', '🲭', '🲮', '🲯', '🲰', '🲱', '🲲', '🲳', '🲴', '🲵', '🲶', '🲷', '🲸', '🲹', '🲺', '🲻', '🲼', '🲽', '🲾', '🲿', '🳀', '🳁', '🳂', '🳃', '🳄', '🳅', '🳆', '🳇', '🳈', '🳉', '🳊', '🳋', '🳌', '🳍', '🳎', '🳏', '🳐', '🳑', '🳒', '🳓', '🳔', '🳕', '🳖', '🳗', '🳘', '🳙', '🳚', '🳛', '🳜', '🳝', '🳞', '🳟', '🳠', '🳡', '🳢', '🳣', '🳤', '🳥', '🳦', '🳧', '🳨', '🳩', '🳪', '🳫', '🳬', '🳭', '🳮', '🳯', '🳰', '🳱', '🳲', '🳳', '🳴', '🳵', '🳶', '🳷', '🳸', '🳹', '🳺', '🳻', '🳼', '🳽', '🳾', '🳿', '🴀', '🴁', '🴂', '🴃', '🴄', '🴅', '🴆', '🴇', '🴈', '🴉', '🴊', '🴋', '🴌', '🴍', '🴎', '🴏', '🴐', '🴑', '🴒', '🴓', '🴔', '🴕', '🴖', '🴗', '🴘', '🴙', '🴚', '🴛', '🴜', '🴝', '🴞', '🴟', '🴠', '🴡', '🴢', '🴣', '🴤', '🴥', '🴦', '🴧', '🴨', '🴩', '🴪', '🴫', '🴬', '🴭', '🴮', '🴯', '🴰', '🴱', '🴲', '🴳', '🴴', '🴵', '🴶', '🴷', '🴸', '🴹', '🴺', '🴻', '🴼', '🴽', '🴾', '🴿', '🵀', '🵁', '🵂', '🵃', '🵄', '🵅', '🵆', '🵇', '🵈', '🵉', '🵊', '🵋', '🵌', '🵍', '🵎', '🵏', '🵐', '🵑', '🵒', '🵓', '🵔', '🵕', '🵖', '🵗', '🵘', '🵙', '🵚', '🵛', '🵜', '🵝', '🵞', '🵟', '🵠', '🵡', '🵢', '🵣', '🵤', '🵥', '🵦', '🵧', '🵨', '🵩', '🵪', '🵫', '🵬', '🵭', '🵮', '🵯', '🵰', '🵱', '🵲', '🵳', '🵴', '🵵', '🵶', '🵷', '🵸', '🵹', '🵺', '🵻', '🵼', '🵽', '🵾', '🵿', '🶀', '🶁', '🶂', '🶃', '🶄', '🶅', '🶆', '🶇', '🶈', '🶉', '🶊', '🶋', '🶌', '🶍', '🶎', '🶏', '🶐', '🶑', '🶒', '🶓', '🶔', '🶕', '🶖', '🶗', '🶘', '🶙', '🶚', '🶛', '🶜', '🶝', '🶞', '🶟', '🶠', '🶡', '🶢', '🶣', '🶤', '🶥', '🶦', '🶧', '🶨', '🶩', '🶪', '🶫', '🶬', '🶭', '🶮', '🶯', '🶰', '🶱', '🶲', '🶳', '🶴', '🶵', '🶶', '🶷', '🶸', '🶹', '🶺', '🶻', '🶼', '🶽', '🶾', '🶿', '🷀', '🷁', '🷂', '🷃', '🷄', '🷅', '🷆', '🷇', '🷈', '🷉', '🷊', '🷋', '🷌', '🷍', '🷎', '🷏', '🷐', '🷑', '🷒', '🷓', '🷔', '🷕', '🷖', '🷗', '🷘', '🷙', '🷚', '🷛', '🷜', '🷝', '🷞', '🷟', '🷠', '🷡', '🷢', '🷣', '🷤', '🷥', '🷦', '🷧', '🷨', '🷩', '🷪', '🷫', '🷬', '🷭', '🷮', '🷯', '🷰', '🷱', '🷲', '🷳', '🷴', '🷵', '🷶', '🷷', '🷸', '🷹', '🷺', '🷻', '🷼', '🷽', '🷾', '🷿', '🸀', '🸁', '🸂', '🸃', '🸄', '🸅', '🸆', '🸇', '🸈', '🸉', '🸊', '🸋', '🸌', '🸍', '🸎', '🸏', '🸐', '🸑', '🸒', '🸓', '🸔', '🸕', '🸖', '🸗', '🸘', '🸙', '🸚', '🸛', '🸜', '🸝', '🸞', '🸟', '🸠', '🸡', '🸢', '🸣', '🸤', '🸥', '🸦', '🸧', '🸨', '🸩', '🸪', '🸫', '🸬', '🸭', '🸮', '🸯', '🸰', '🸱', '🸲', '🸳', '🸴', '🸵', '🸶', '🸷', '🸸', '🸹', '🸺', '🸻', '🸼', '🸽', '🸾', '🸿', '🹀', '🹁', '🹂', '🹃', '🹄', '🹅', '🹆', '🹇', '🹈', '🹉', '🹊', '🹋', '🹌', '🹍', '🹎', '🹏', '🹐', '🹑', '🹒', '🹓', '🹔', '🹕', '🹖', '🹗', '🹘', '🹙', '🹚', '🹛', '🹜', '🹝', '🹞', '🹟', '🹠', '🹡', '🹢', '🹣', '🹤', '🹥', '🹦', '🹧', '🹨', '🹩', '🹪', '🹫', '🹬', '🹭', '🹮', '🹯', '🹰', '🹱', '🹲', '🹳', '🹴', '🹵', '🹶', '🹷', '🹸', '🹹', '🹺', '🹻', '🹼', '🹽', '🹾', '🹿', '🺀', '🺁', '🺂', '🺃', '🺄', '🺅', '🺆', '🺇', '🺈', '🺉', '🺊', '🺋', '🺌', '🺍', '🺎', '🺏', '🺐', '🺑', '🺒', '🺓', '🺔', '🺕', '🺖', '🺗', '🺘', '🺙', '🺚', '🺛', '🺜', '🺝', '🺞', '🺟', '🺠', '🺡', '🺢', '🺣', '🺤', '🺥', '🺦', '🺧', '🺨', '🺩', '🺪', '🺫', '🺬', '🺭', '🺮', '🺯', '🺰', '🺱', '🺲', '🺳', '🺴', '🺵', '🺶', '🺷', '🺸', '🺹', '🺺', '🺻', '🺼', '🺽', '🺾', '🺿', '🻀', '🻁', '🻂', '🻃', '🻄', '🻅', '🻆', '🻇', '🻈', '🻉', '🻊', '🻋', '🻌', '🻍', '🻎', '🻏', '🻐', '🻑', '🻒', '🻓', '🻔', '🻕', '🻖', '🻗', '🻘', '🻙', '🻚', '🻛', '🻜', '🻝', '🻞', '🻟', '🻠', '🻡', '🻢', '🻣', '🻤', '🻥', '🻦', '🻧', '🻨', '🻩', '🻪', '🻫', '🻬', '🻭', '🻮', '🻯', '🻰', '🻱', '🻲', '🻳', '🻴', '🻵', '🻶', '🻷', '🻸', '🻹', '🻺', '🻻', '🻼', '🻽', '🻾', '🻿', '🼀', '🼁', '🼂', '🼃', '🼄', '🼅', '🼆', '🼇', '🼈', '🼉', '🼊', '🼋', '🼌', '🼍', '🼎', '🼏', '🼐', '🼑', '🼒', '🼓', '🼔', '🼕', '🼖', '🼗', '🼘', '🼙', '🼚', '🼛', '🼜', '🼝', '🼞', '🼟', '🼠', '🼡', '🼢', '🼣', '🼤', '🼥', '🼦', '🼧', '🼨', '🼩', '🼪', '🼫', '🼬', '🼭', '🼮', '🼯', '🼰', '🼱', '🼲', '🼳', '🼴', '🼵', '🼶', '🼷', '🼸', '🼹', '🼺', '🼻', '🼼', '🼽', '🼾', '🼿', '🽀', '🽁', '🽂', '🽃', '🽄', '🽅', '🽆', '🽇', '🽈', '🽉', '🽊', '🽋', '🽌', '🽍', '🽎', '🽏', '🽐', '🽑', '🽒', '🽓', '🽔', '🽕', '🽖', '🽗', '🽘', '🽙', '🽚', '🽛', '🽜', '🽝', '🽞', '🽟', '🽠', '🽡', '🽢', '🽣', '🽤', '🽥', '🽦', '🽧', '🽨', '🽩', '🽪', '🽫', '🽬', '🽭', '🽮', '🽯', '🽰', '🽱', '🽲', '🽳', '🽴', '🽵', '🽶', '🽷', '🽸', '🽹', '🽺', '🽻', '🽼', '🽽', '🽾', '🽿', '🾀', '🾁', '🾂', '🾃', '🾄', '🾅', '🾆', '🾇', '🾈', '🾉', '🾊', '🾋', '🾌', '🾍', '🾎', '🾏', '🾐', '🾑', '🾒', '🾓', '🾔', '🾕', '🾖', '🾗', '🾘', '🾙', '🾚', '🾛', '🾜', '🾝', '🾞', '🾟', '🾠', '🾡', '🾢', '🾣', '🾤', '🾥', '🾦', '🾧', '🾨', '🾩', '🾪', '🾫', '🾬', '🾭', '🾮', '🾯', '🾰', '🾱', '🾲', '🾳', '🾴', '🾵', '🾶', '🾷', '🾸', '🾹', '🾺', '🾻', '🾼', '🾽', '🾾', '🾿', '🿀', '🿁', '🿂', '🿃', '🿄', '🿅', '🿆', '🿇', '🿈', '🿉', '🿊', '🿋', '🿌', '🿍', '🿎', '🿏', '🿐', '🿑', '🿒', '🿓', '🿔', '🿕', '🿖', '🿗', '🿘', '🿙', '🿚', '🿛', '🿜', '🿝', '🿞', '🿟', '🿠', '🿡', '🿢', '🿣', '🿤', '🿥', '🿦', '🿧', '🿨', '🿩', '🿪', '🿫', '🿬', '🿭', '🿮', '🿯', '🿰', '🿱', '🿲', '🿳', '🿴', '🿵', '🿶', '🿷', '🿸', '🿹', '🿺', '🿻', '🿼', '🿽', '🿾', '🿿']
    };

    if (emojis[category]) {
        emojis[category].forEach(emoji => {
            const button = document.createElement('button');
            button.className = 'emoji-btn p-2 hover:bg-gray-200 rounded text-lg';
            button.textContent = emoji;
            button.onclick = () => insertEmoji(emoji);
            container.appendChild(button);
        });
    }
}

// ==================== FUNÇÕES DE GRAVAÇÃO DE ÁUDIO ====================

let mediaRecorder = null;
let audioChunks = [];
let isRecording = false;

// Toggle gravação de áudio
async function toggleAudioRecording() {
    if (!isRecording) {
        await startAudioRecording();
    } else {
        stopAudioRecording();
    }
}

// Iniciar gravação de áudio
async function startAudioRecording() {
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        mediaRecorder = new MediaRecorder(stream);
        audioChunks = [];

        mediaRecorder.ondataavailable = event => {
            audioChunks.push(event.data);
        };

        mediaRecorder.onstop = () => {
            const audioBlob = new Blob(audioChunks, { type: 'audio/wav' });
            sendAudioMessage(audioBlob);
            stream.getTracks().forEach(track => track.stop());
        };

        mediaRecorder.start();
        isRecording = true;
        
        // Atualizar botão
        const btn = document.getElementById('audioRecordBtn');
        btn.classList.add('bg-red-500', 'text-white', 'animate-pulse');
        btn.classList.remove('text-gray-400');
        btn.innerHTML = `
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2C13.1 2 14 2.9 14 4V12C14 13.1 13.1 14 12 14C10.9 14 10 13.1 10 12V4C10 2.9 10.9 2 12 2M19 10V12C19 15.9 15.9 19 12 19S5 15.9 5 12V10H7V12C7 14.8 9.2 17 12 17S17 14.8 17 12V10H19Z"/>
            </svg>
        `;
        btn.title = 'Parar gravação';
        
        showNotification('🎤 Gravando áudio...', 'info');

    } catch (error) {
        console.error('Erro ao acessar microfone:', error);
        showNotification('Erro ao acessar microfone', 'error');
    }
}

// Parar gravação de áudio
function stopAudioRecording() {
    if (mediaRecorder && isRecording) {
        mediaRecorder.stop();
        isRecording = false;
        
        // Atualizar botão
        const btn = document.getElementById('audioRecordBtn');
        btn.classList.remove('bg-red-500', 'text-white', 'animate-pulse');
        btn.classList.add('text-gray-400');
        btn.innerHTML = `
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path>
            </svg>
        `;
        btn.title = 'Gravar áudio';
        
        showNotification('🎵 Processando áudio...', 'info');
    }
}

// Enviar mensagem de áudio
async function sendAudioMessage(audioBlob) {
    if (!window.selectedEmployee) {
        showNotification('Selecione um funcionário primeiro', 'error');
        return;
    }

    try {
        // // const supabase = await getSupabaseClient(); // REMOVIDO // REMOVIDO - usando MySQL
        const { data: { user } } = await supabase.auth.getUser();
        
        if (!user) return;

        // Buscar farm_id do usuário
        const { data: userData } = await supabase
            .from('users')
            .select('farm_id')
            .eq('id', user.id)
            .single();

        if (!userData?.farm_id) return;

        // Upload do áudio para Supabase Storage
        const fileName = `audio_${Date.now()}_${Math.random().toString(36).substring(2)}.wav`;
        const filePath = `chat-files/${userData.farm_id}/${fileName}`;

        const { data: uploadData, error: uploadError } = await supabase.storage
            .from('chat-files')
            .upload(filePath, audioBlob);

        if (uploadError) {
            console.error('Erro no upload:', uploadError);
            showNotification('Erro ao enviar áudio', 'error');
            return;
        }

        // Obter URL pública do áudio
        const { data: { publicUrl } } = supabase.storage
            .from('chat-files')
            .getPublicUrl(filePath);

        // Criar mensagem com áudio
        const audioMessage = {
            type: 'audio',
            name: 'Mensagem de voz',
            size: audioBlob.size,
            url: publicUrl
        };

        // Enviar mensagem
        await sendChatMessage({
            farm_id: userData.farm_id,
            sender_id: user.id,
            receiver_id: window.selectedEmployee.id,
            message: '🎵 Mensagem de voz',
            file_data: audioMessage
        });

        showNotification('Áudio enviado com sucesso!', 'success');

    } catch (error) {
        console.error('Erro ao enviar áudio:', error);
        showNotification('Erro ao enviar áudio', 'error');
    }
}



// ==================== FUNÇÕES DOS CONTATOS ====================

// Abrir modal de contatos
async function openContactsModal() {
    try {
        console.log('Abrindo modal de contatos...');
        
        const modal = document.getElementById('contactsModal');
        if (modal) {
            modal.classList.remove('hidden');
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            
            // Carregar contatos
            await loadContacts();
        }
    } catch (error) {
        console.error('Erro ao abrir modal de contatos:', error);
        showNotification('Erro ao abrir contatos', 'error');
    }
}

// Fechar modal de contatos
function closeContactsModal() {
    const modal = document.getElementById('contactsModal');
    if (modal) {
        modal.classList.add('hidden');
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

// Aprovar solicitação de senha
async function approvePasswordRequest(requestId) {
    if (!requestId) {
        requestId = window.currentPasswordRequestId;
    }
    
    if (!requestId) {
        showNotification('ID da solicitação não encontrado', 'error');
        return;
    }
    
    try {
        // // const supabase = await getSupabaseClient(); // REMOVIDO // REMOVIDO - usando MySQL
        
        const { error: updateError } = await supabase
            .from('password_requests')
            .update({ 
                status: 'approved',
                approved_at: new Date().toISOString(),
                approved_by: (await supabase.auth.getUser()).data.user.id
            })
            .eq('id', requestId);
        
        if (updateError) throw updateError;
        
        const { data: request, error: fetchError } = await supabase
            .from('password_requests')
            .select('*')
            .eq('id', requestId)
            .single();
        
        if (!fetchError && request) {
            // Enviar notificação para o usuário (implementar conforme necessário)
            showNotification('Solicitação aprovada com sucesso!', 'success');
        }
        
        // Fechar modal de detalhes se estiver aberto
        closePasswordRequestDetailsModal();
        
        // Recarregar lista de solicitações
        loadPasswordRequests();
        
    } catch (error) {
        console.error('Erro ao aprovar solicitação:', error);
        showNotification('Erro ao aprovar solicitação', 'error');
    }
}

// Rejeitar solicitação de senha
async function rejectPasswordRequest(requestId) {
    if (!requestId) {
        requestId = window.currentPasswordRequestId;
    }
    
    if (!requestId) {
        requestId = window.currentPasswordRequestId;
    }
    
    if (!requestId) {
        showNotification('ID da solicitação não encontrado', 'error');
        return;
    }
    
    try {
        // // const supabase = await getSupabaseClient(); // REMOVIDO // REMOVIDO - usando MySQL
        
        // Atualizar status da solicitação
        const { error: updateError } = await supabase
            .from('password_requests')
            .update({ 
                status: 'rejected',
                rejected_at: new Date().toISOString(),
                rejected_by: (await supabase.auth.getUser()).data.user.id
            })
            .eq('id', requestId);
        
        if (updateError) throw updateError;
        
        // Buscar dados da solicitação para notificar o usuário
        const { data: request, error: fetchError } = await supabase
            .from('password_requests')
            .select(`
                *,
                users!inner(name, email, role)
            `)
            .eq('id', requestId)
            .single();
        
        if (!fetchError && request) {
            // Enviar notificação para o usuário (implementar conforme necessário)
            showNotification(`Solicitação de ${request.users.name} rejeitada.`, 'warning');
        }
        
        // Fechar modal de detalhes se estiver aberto
        closePasswordRequestDetailsModal();
        
        // Recarregar lista de solicitações
        loadPasswordRequests();
        
    } catch (error) {
        console.error('Erro ao rejeitar solicitação:', error);
        showNotification('Erro ao rejeitar solicitação', 'error');
    }
}

// Atualizar lista de solicitações
function refreshPasswordRequests() {
    loadPasswordRequests();
}

// Aplicar filtro de solicitações
document.addEventListener('DOMContentLoaded', function() {
    const filterSelect = document.getElementById('passwordRequestFilter');
    if (filterSelect) {
        filterSelect.addEventListener('change', function() {
            const filterValue = this.value;
            // Implementar filtro conforme necessário
            loadPasswordRequests();
        });
    }
    
    // Garantir que ambos os modais estejam fechados ao carregar a página
    const detailsModal = document.getElementById('passwordRequestDetailsModal');
    const requestsModal = document.getElementById('passwordRequestsModal');
    
    if (detailsModal) {
        detailsModal.classList.add('hidden');
        detailsModal.style.display = 'none';
        detailsModal.style.visibility = 'hidden';
        detailsModal.style.opacity = '0';
        detailsModal.style.pointerEvents = 'none';
        detailsModal.style.zIndex = '-1';
    }
    
    if (requestsModal) {
        requestsModal.classList.add('hidden');
        requestsModal.style.display = 'none';
        requestsModal.style.visibility = 'hidden';
        requestsModal.style.opacity = '0';
        requestsModal.style.pointerEvents = 'none';
        requestsModal.style.zIndex = '-1';
    }
});

// Atualizar logo da Xandria Store baseada no tema
function updateXandriaStoreIcon() {
    const icon = document.getElementById('xandriaStoreIcon');
    if (icon) {
        icon.src = 'https://i.postimg.cc/W17q41wM/lactechpreta.png'; // Logo preta para tema claro
    }
}




// ==================== FUNÇÕES DE SOLICITAÇÕES DE SENHA (ESCOPO GLOBAL) ====================

// Abrir modal de solicitações de senha
function openPasswordRequests() {
    closeMoreModal();
    const modal = document.getElementById('passwordRequestsModal');
    if (modal) {
        modal.classList.remove('hidden');
        modal.style.display = 'flex';
        modal.style.visibility = 'visible';
        modal.style.opacity = '1';
        modal.style.pointerEvents = 'auto';
        modal.style.zIndex = '99999';
        
        // Carregar solicitações com refresh forçado
        console.log('🔄 Abrindo modal e carregando solicitações...');
        loadPasswordRequestsWithCache(true);
    }
}

// Fechar modal de solicitações de senha
function closePasswordRequestsModal() {
    const modal = document.getElementById('passwordRequestsModal');
    if (modal) {
        modal.classList.add('hidden');
        modal.style.display = 'none';
        modal.style.visibility = 'hidden';
        modal.style.opacity = '0';
        modal.style.pointerEvents = 'none';
        modal.style.zIndex = '-1';
    }
}

// Fechar modal de detalhes da solicitação
function closePasswordRequestDetailsModal() {
    const modal = document.getElementById('passwordRequestDetailsModal');
    if (modal) {
        modal.classList.add('hidden');
        modal.style.display = 'none';
        modal.style.visibility = 'hidden';
        modal.style.opacity = '0';
        modal.style.pointerEvents = 'none';
        modal.style.zIndex = '-1';
    }
}

// Carregar solicitações de senha
async function loadPasswordRequests() {
    try {
        // // const supabase = await getSupabaseClient(); // REMOVIDO // REMOVIDO - usando MySQL
        const { data: { user } } = await supabase.auth.getUser();
        
        if (!user) {
            showNotification('Usuário não autenticado', 'error');
            return;
        }
        
        // Buscar dados do usuário atual para obter farm_id
        const { data: userData, error: userError } = await supabase
            .from('users')
            .select('farm_id')
            .eq('id', user.id)
            .single();
        
        if (userError || !userData?.farm_id) {
            showNotification('Erro ao buscar dados da fazenda', 'error');
            return;
        }
        
        // Buscar solicitações de senha da fazenda (abordagem corrigida)
        // Primeiro, buscar usuários da fazenda
        const { data: farmUsers, error: usersError } = await supabase
            .from('users')
            .select('id, name, email, role, profile_photo_url')
            .eq('farm_id', userData.farm_id);
        
        if (usersError) {
            console.error('❌ Erro ao buscar usuários da fazenda:', usersError);
            showNotification('Erro ao buscar usuários da fazenda', 'error');
            return;
        }
        
        if (!farmUsers || farmUsers.length === 0) {
            console.log('📝 Nenhum usuário encontrado na fazenda');
            displayPasswordRequests([]);
            return;
        }
        
        const userIds = farmUsers.map(user => user.id);
        
        // Depois, buscar solicitações desses usuários
        const { data: requests, error } = await supabase
            .from('password_requests')
            .select('*')
            .in('user_id', userIds)
            .order('created_at', { ascending: false });
        
        if (error) {
            console.error('Erro ao buscar solicitações:', error);
            showNotification('Erro ao carregar solicitações', 'error');
            return;
        }
        
        displayPasswordRequests(requests || []);
        
    } catch (error) {
        console.error('Erro ao carregar solicitações:', error);
        showNotification('Erro ao carregar solicitações', 'error');
    }
}

// Exibir solicitações de senha
function displayPasswordRequests(requests) {
    const container = document.getElementById('passwordRequestsList');
    const emptyState = document.getElementById('emptyPasswordRequests');
    
    if (!container || !emptyState) return;
    
    // Atualizar contadores
    updateRequestCounters(requests || []);
    
    if (!requests || requests.length === 0) {
        container.innerHTML = '';
        emptyState.classList.remove('hidden');
        return;
    }
    
    emptyState.classList.add('hidden');
    container.innerHTML = '';
    
    requests.forEach(request => {
        const requestCard = createPasswordRequestCard(request);
        container.appendChild(requestCard);
    });
}

// Atualizar contadores de solicitações
function updateRequestCounters(requests) {
    const pendingCount = requests.filter(r => r.status === 'pending').length;
    const approvedCount = requests.filter(r => r.status === 'approved').length;
    const rejectedCount = requests.filter(r => r.status === 'rejected').length;
    
    const pendingElement = document.getElementById('pendingCount');
    const approvedElement = document.getElementById('approvedCount');
    const rejectedElement = document.getElementById('rejectedCount');
    
    if (pendingElement) pendingElement.textContent = pendingCount;
    if (approvedElement) approvedElement.textContent = approvedCount;
    if (rejectedElement) rejectedElement.textContent = rejectedCount;
}

// Criar card de solicitação de senha
function createPasswordRequestCard(request) {
    const card = document.createElement('div');
    card.className = 'bg-white bg-white rounded-xl border border-gray-200 border-gray-200 p-4 hover:shadow-md transition-all duration-200';
    
    const statusColors = {
        'pending': 'bg-yellow-100 text-yellow-800 bg-yellow-100/20 text-yellow-800',
        'approved': 'bg-green-100 text-green-800 bg-green-100/20 text-green-800',
        'rejected': 'bg-red-100 text-red-800 bg-red-100/20 text-red-800'
    };
    
    const statusTexts = {
        'pending': 'Pendente',
        'approved': 'Aprovada',
        'rejected': 'Rejeitada'
    };
    
    const typeTexts = {
        'change': 'Alteração de Senha',
        'reset': 'Redefinição de Senha'
    };
    
    const reasonTexts = {
        'forgot': 'Esqueci a senha',
        'security': 'Questões de segurança',
        'update': 'Atualização regular',
        'other': 'Outro motivo'
    };
    
    const formattedDate = new Date(request.created_at).toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
    
    card.innerHTML = `
        <div class="flex items-start justify-between mb-3">
            <div class="flex items-center space-x-3">
                <div class="w-8 h-8 bg-gray-300 bg-gray-300 rounded-full flex items-center justify-center">
                    <svg class="w-4 h-4 text-gray-600 " fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
                <div>
                    <h4 class="font-bold text-gray-900 ">${request.users?.name || 'Usuário'}</h4>
                    <p class="text-sm text-gray-600 ">${request.users?.email || 'Email não informado'}</p>
                </div>
            </div>
        </div>
        
        <div class="mb-3">
            <p class="font-bold text-gray-900 ">Motivo: ${reasonTexts[request.reason] || request.reason || 'Não especificado'}</p>
        </div>
        
        ${request.new_password || (request.notes && request.notes.includes('NOVA SENHA:')) ? `
            <div class="mb-3">
                <p class="text-gray-900  mb-1">Nova Senha:</p>
                <div class="relative">
                    <input type="password" value="${request.new_password || (request.notes ? request.notes.split('NOVA SENHA: ')[1]?.split('\n')[0] : '')}" readonly class="w-full px-3 py-2 border border-gray-300 border-gray-300 rounded-lg bg-gray-50 bg-white text-gray-900  pr-10">
                    <button type="button" onclick="togglePasswordVisibility(this)" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700  hover:text-gray-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                    </button>
                </div>
            </div>
        ` : ''}
        
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-2 text-xs text-gray-500 ">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>${formattedDate}</span>
            </div>
            
            <div class="flex items-center space-x-2">
                <span class="px-3 py-1 text-xs font-medium rounded ${statusColors[request.status]}">
                    ${statusTexts[request.status]}
                </span>
                
                <button onclick="viewPasswordRequestDetails('${request.id}')" class="px-3 py-1 bg-blue-500 hover:bg-blue-600 text-white text-xs rounded transition-colors duration-200">
                    Ver Detalhes
                </button>
                
                ${request.status === 'pending' ? `
                    <button onclick="approvePasswordRequestDirect('${request.id}')" class="px-3 py-1 bg-green-500 hover:bg-green-600 text-white text-xs rounded transition-colors duration-200">
                        Aprovar
                    </button>
                    
                    <button onclick="rejectPasswordRequestDirect('${request.id}')" class="px-3 py-1 bg-red-500 hover:bg-red-600 text-white text-xs rounded transition-colors duration-200">
                        Rejeitar
                    </button>
                ` : ''}
                
                <button onclick="deletePasswordRequest('${request.id}')" class="p-1.5 bg-gray-500 hover:bg-gray-600 text-white rounded transition-colors duration-200" title="Excluir solicitação">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                </button>
            </div>
        </div>
    `;
    
    return card;
}

// Visualizar detalhes da solicitação (versão corrigida)
async function viewPasswordRequestDetailsFixed(requestId) {
    try {
        // // const supabase = await getSupabaseClient(); // REMOVIDO // REMOVIDO - usando MySQL
        
        // Buscar solicitação sem JOIN
        const { data: request, error } = await supabase
            .from('password_requests')
            .select('*')
            .eq('id', requestId)
            .single();
        
        if (error || !request) {
            showNotification('Erro ao carregar detalhes da solicitação', 'error');
            return;
        }
        
        // Buscar dados do usuário separadamente
        let userData = { name: 'Usuário não encontrado', email: 'Email não encontrado', role: 'N/A' };
        try {
            const { data: user } = await supabase
                .from('users')
                .select('name, email, role')
                .eq('id', request.user_id)
                .single();
            
            if (user) {
                userData = user;
            }
        } catch (userError) {
        }
        
        // Função para truncar texto
        function truncateText(text, maxLength = 30) {
            if (!text) return 'Não informado';
            if (text.length <= maxLength) return text;
            return text.substring(0, maxLength) + '...';
        }
        
        // Traduzir motivo
        function translateReason(reason) {
            const translations = {
                'forgot_password': 'Esqueci minha senha',
                'change_password': 'Alterar senha',
                'reset_password': 'Redefinir senha',
                'security_concern': 'Preocupação de segurança',
                'account_compromised': 'Conta comprometida',
                'regular_update': 'Atualização regular'
            };
            return translations[reason] || reason;
        }
        
        // Preencher modal de detalhes
        document.getElementById('requestUserName').textContent = truncateText(userData.name);
        document.getElementById('requestUserEmail').textContent = truncateText(userData.email, 40);
        document.getElementById('requestUserRole').textContent = userData.role || 'N/A';
        document.getElementById('requestDate').textContent = new Date(request.created_at).toLocaleDateString('pt-BR');
        document.getElementById('requestType').textContent = request.type === 'change' ? 'Alteração de Senha' : 'Redefinição de Senha';
        document.getElementById('requestReason').textContent = translateReason(request.reason) || 'N/A';
        document.getElementById('requestNotes').textContent = request.notes || 'N/A';
        
        // Armazenar ID da solicitação atual
        window.currentPasswordRequestId = requestId;
        
        // Abrir modal de detalhes
        const modal = document.getElementById('passwordRequestDetailsModal');
        if (modal) {
            modal.classList.remove('hidden');
            modal.style.display = 'flex';
            modal.style.visibility = 'visible';
            modal.style.opacity = '1';
            modal.style.pointerEvents = 'auto';
            modal.style.zIndex = '99999';
        }
        
    } catch (error) {
        console.error('Erro ao visualizar detalhes:', error);
        showNotification('Erro ao carregar detalhes', 'error');
    }
}

// Aprovar solicitação de senha
async function approvePasswordRequest() {
    try {
        if (!window.currentPasswordRequestId) {
            showNotification('ID da solicitação não encontrado', 'error');
            return;
        }
        
        // // const supabase = await getSupabaseClient(); // REMOVIDO // REMOVIDO - usando MySQL
        const { data: { user } } = await supabase.auth.getUser();
        
        if (!user) {
            showNotification('Usuário não autenticado', 'error');
            return;
        }
        
        // Atualizar status da solicitação
        const { error } = await supabase
            .from('password_requests')
            .update({
                status: 'approved',
                approved_at: new Date().toISOString(),
                approved_by: user.id
            })
            .eq('id', window.currentPasswordRequestId);
        
        if (error) throw error;
        
        showNotification('Solicitação aprovada com sucesso!', 'success');
        
        // Fechar modal e recarregar lista
        closePasswordRequestDetailsModal();
        loadPasswordRequests();
        
    } catch (error) {
        console.error('Erro ao aprovar solicitação:', error);
        showNotification('Erro ao aprovar solicitação: ' + error.message, 'error');
    }
}

// Rejeitar solicitação de senha
async function rejectPasswordRequest() {
    try {
        if (!window.currentPasswordRequestId) {
            showNotification('ID da solicitação não encontrado', 'error');
            return;
        }
        
        // // const supabase = await getSupabaseClient(); // REMOVIDO // REMOVIDO - usando MySQL
        const { data: { user } } = await supabase.auth.getUser();
        
        if (!user) {
            showNotification('Usuário não autenticado', 'error');
            return;
        }
        
        // Atualizar status da solicitação
        const { error } = await supabase
            .from('password_requests')
            .update({
                status: 'rejected',
                rejected_at: new Date().toISOString(),
                rejected_by: user.id
            })
            .eq('id', window.currentPasswordRequestId);
        
        if (error) throw error;
        
        // Log de auditoria
        logAuditEvent('password_request_rejected', requestId, {
            managerId: user.id,
            reason: 'manager_rejection'
        });
        
        // Limpar cache para forçar atualização
        clearCache();
        
        showNotification('Solicitação rejeitada com sucesso!', 'success');
        
        // Fechar modal e recarregar lista
        closePasswordRequestDetailsModal();
        loadPasswordRequests();
        
    } catch (error) {
        console.error('Erro ao rejeitar solicitação:', error);
        showNotification('Erro ao rejeitar solicitação: ' + error.message, 'error');
    }
}

// Atualizar solicitações de senha
async function refreshPasswordRequests() {
    const btn = document.getElementById('refreshPasswordRequestsBtn');
    if (btn) {
        // Adicionar animação de loading
        btn.disabled = true;
        btn.innerHTML = `
            <svg class="w-4 h-4 inline mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            Atualizando...
        `;
    }
    
    try {
        await loadPasswordRequestsWithCache(true);
    } finally {
        if (btn) {
            // Restaurar botão
            btn.disabled = false;
            btn.innerHTML = `
                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Atualizar
            `;
        }
    }
}

// Event listener para filtro de solicitações
document.addEventListener('DOMContentLoaded', function() {
    const filterSelect = document.getElementById('passwordRequestFilter');
    if (filterSelect) {
        filterSelect.addEventListener('change', function() {
            const filterValue = this.value;
            // Implementar filtro conforme necessário
            loadPasswordRequests();
        });
    }
    
    // Garantir que ambos os modais estejam fechados ao carregar a página
    const detailsModal = document.getElementById('passwordRequestDetailsModal');
    const requestsModal = document.getElementById('passwordRequestsModal');
    
    if (detailsModal) {
        detailsModal.classList.add('hidden');
        detailsModal.style.display = 'none';
        detailsModal.style.visibility = 'hidden';
        detailsModal.style.opacity = '0';
        detailsModal.style.pointerEvents = 'none';
        detailsModal.style.zIndex = '-1';
    }
    
    if (requestsModal) {
        requestsModal.classList.add('hidden');
        requestsModal.style.display = 'none';
        requestsModal.style.visibility = 'hidden';
        requestsModal.style.opacity = '0';
        requestsModal.style.pointerEvents = 'none';
        requestsModal.style.zIndex = '-1';
    }
});


// ==================== HTML REMOVIDO ====================

// Inicializar notificações nativas
if (window.nativeNotifications) {
    window.nativeNotifications.init();
    
    // Solicitar permissão de notificações automaticamente
    setTimeout(() => {
        window.nativeNotifications.requestNotificationPermission();
    }, 3000);
}

// Inicializar sistema de sincronização offline
if (window.offlineSyncManager) {
    console.log('🔄 Sistema de sincronização offline inicializado');
}

// Funções para atualização em tempo real dos gráficos
window.updateVolumeCharts = async function() {
    try {
        await loadWeeklyVolumeChart();
        await loadDailyVolumeChart();
        await loadDashboardWeeklyChart();
        console.log('📊 Gráficos de volume atualizados');
    } catch (error) {
        console.error('❌ Erro ao atualizar gráficos de volume:', error);
    }
};

window.updateVolumeStats = async function() {
    try {
        await loadVolumeData();
        console.log('📈 Estatísticas de volume atualizadas');
    } catch (error) {
        console.error('❌ Erro ao atualizar estatísticas de volume:', error);
    }
};

window.updateVolumeRecordsList = async function() {
    try {
        console.log('🔄 Atualizando lista de registros de volume...');
        
        // // const supabase = await getSupabaseClient(); // REMOVIDO // REMOVIDO - usando MySQL
        const { data: { user } } = await supabase.auth.getUser();
        if (!user) return;

        const { data: userData } = await supabase
            .from('users')
            .select('farm_id')
            .eq('id', user.id)
            .single();

        if (!userData?.farm_id) return;

        // Buscar registros do servidor
        const { data: volumeRecords, error } = await supabase
            .from('volume_records')
            .select('*')
            .eq('farm_id', userData.farm_id)
            .order('production_date', { ascending: false })
            .limit(10);

        if (error) {
            console.error('❌ Erro ao buscar registros:', error);
            return;
        }

        // Adicionar registros locais
        let allRecords = [...(volumeRecords || [])];
        if (window.offlineSyncManager) {
            const localRecords = window.offlineSyncManager.getLocalData('volume');
            const localFiltered = localRecords.filter(record => 
                record.farm_id === userData.farm_id && !record.synced
            );
            allRecords = [...localFiltered, ...allRecords];
        }

        // Atualizar interface
        const tbody = document.getElementById('volumeRecords');
        if (tbody) {
            if (allRecords.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center py-8 text-gray-500">
                            Nenhum registro encontrado
                        </td>
                    </tr>
                `;
            } else {
                tbody.innerHTML = allRecords.map(record => `
                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                        <td class="py-3 px-4 text-sm text-gray-900">
                            ${new Date(record.production_date + 'T00:00:00').toLocaleDateString('pt-BR')}
                        </td>
                        <td class="py-3 px-4 text-sm text-gray-900 font-medium">
                            ${record.volume_liters}L
                        </td>
                        <td class="py-3 px-4 text-sm text-gray-600">
                            ${record.users?.name || record.user_name || 'N/A'}
                        </td>
                        <td class="py-3 px-4 text-sm text-gray-600">
                            ${record.notes || '-'}
                        </td>
                        <td class="py-3 px-4 text-sm">
                            <button onclick="deleteVolumeRecord('${record.id}');" class="text-red-600 hover:text-red-800 font-medium">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </td>
                    </tr>
                `).join('');
            }
        }

        console.log(`✅ Lista de registros atualizada: ${allRecords.length} registros`);
    } catch (error) {
        console.error('❌ Erro ao atualizar lista de registros:', error);
    }
};

window.updateDashboardStats = async function() {
    try {
        console.log('🔄 Atualizando estatísticas do dashboard...');
        
        // Atualizar volume de hoje
        await loadVolumeData();
        
        // Atualizar gráfico semanal do dashboard
        await loadDashboardWeeklyChart();
        
        // Atualizar estatísticas gerais
        await loadDashboardData();
        
        console.log('✅ Estatísticas do dashboard atualizadas');
    } catch (error) {
        console.error('❌ Erro ao atualizar estatísticas do dashboard:', error);
    }
};

// Função para forçar atualização completa dos registros de volume
window.forceRefreshVolumeRecords = async function() {
    try {
        console.log('🔄 Forçando atualização completa dos registros de volume...');
        
        // Limpar cache
        if (CacheManager) {
            CacheManager.clearCache();
            CacheManager.forceRefresh = true;
        }
        
        // Recarregar dados
        await loadVolumeRecords();
        await updateVolumeRecordsList();
        
        console.log('✅ Registros de volume atualizados com sucesso');
    } catch (error) {
        console.error('❌ Erro ao forçar atualização dos registros:', error);
    }
};

// Função para corrigir registros existentes sem nome do funcionário
window.fixVolumeRecordsEmployeeNames = async function() {
    try {
        console.log('🔧 Corrigindo nomes de funcionários nos registros existentes...');
        
        // // const supabase = await getSupabaseClient(); // REMOVIDO // REMOVIDO - usando MySQL
        const { data: { user } } = await supabase.auth.getUser();
        if (!user) return;

        const { data: userData } = await supabase
            .from('users')
            .select('farm_id')
            .eq('id', user.id)
            .single();

        if (!userData?.farm_id) return;

        // Buscar registros para verificar se têm nomes de funcionários
        const { data: recordsWithoutName, error: fetchError } = await supabase
            .from('volume_records')
            .select(`
                id,
                user_id,
                users(name)
            `)
            .eq('farm_id', userData.farm_id)
            .limit(10);

        if (fetchError) {
            console.error('❌ Erro ao buscar registros:', fetchError);
            return;
        }

        if (recordsWithoutName && recordsWithoutName.length > 0) {
            console.log(`✅ Encontrados ${recordsWithoutName.length} registros com dados de funcionários`);
            console.log('ℹ️ Os nomes dos funcionários são obtidos automaticamente via relacionamento com a tabela users');
        } else {
            console.log('ℹ️ Nenhum registro encontrado para verificação');
        }
        
    } catch (error) {
        console.error('❌ Erro ao corrigir nomes de funcionários:', error);
    }
};

// ==================== RELATÓRIOS MODAL ====================
function openReportsModal() {
    const modal = document.getElementById('reportsModal');
    if (modal) {
        modal.classList.remove('hidden');
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
}

function closeReportsModal() {
    const modal = document.getElementById('reportsModal');
    if (modal) {
        modal.classList.add('hidden');
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

// Funções de geração de relatórios
async function generateVolumeReport() {
    try {
        showCustomLoading('Gerando relatório de volume...', 'generate');
        
        // // const supabase = await getSupabaseClient(); // REMOVIDO // REMOVIDO - usando MySQL
        const { data: { user } } = await supabase.auth.getUser();
        
        if (!user) {
            throw new Error('Usuário não autenticado');
        }

        // Buscar dados do usuário para obter farm_id
        const { data: userData } = await supabase
            .from('users')
            .select('farm_id')
            .eq('id', user.id)
            .single();

        if (!userData?.farm_id) {
            throw new Error('Farm ID não encontrado');
        }

        // Buscar dados de volume
        const { data: volumeData, error } = await supabase
                    .from('volume_records')
            .select('*')
            .eq('farm_id', userData.farm_id)
            .order('production_date', { ascending: false })
            .limit(100);

        if (error) {
            throw error;
        }

        // Gerar PDF
        await generateVolumePDF(volumeData || []);
        
        hideCustomLoading();
        showNotification('Relatório de volume gerado com sucesso!', 'success');
        
    } catch (error) {
        hideCustomLoading();
        console.error('Erro ao gerar relatório de volume:', error);
        showNotification('Erro ao gerar relatório: ' + error.message, 'error');
    }
}

async function generateQualityReport() {
    try {
        showCustomLoading('Gerando relatório de qualidade...', 'generate');
        
        // // const supabase = await getSupabaseClient(); // REMOVIDO // REMOVIDO - usando MySQL
        const { data: { user } } = await supabase.auth.getUser();
        
        if (!user) {
            throw new Error('Usuário não autenticado');
        }

        // Buscar dados do usuário para obter farm_id
        const { data: userData } = await supabase
            .from('users')
            .select('farm_id')
            .eq('id', user.id)
            .single();

        if (!userData?.farm_id) {
            throw new Error('Farm ID não encontrado');
        }

        // Buscar dados de qualidade
        const { data: qualityData, error } = await supabase
            .from('quality_tests')
            .select('*')
            .eq('farm_id', userData.farm_id)
            .order('test_date', { ascending: false })
            .limit(100);

        if (error) {
            throw error;
        }

        // Gerar PDF
        await generateQualityPDF(qualityData || []);
        
        hideCustomLoading();
        showNotification('Relatório de qualidade gerado com sucesso!', 'success');
        
    } catch (error) {
        hideCustomLoading();
        console.error('Erro ao gerar relatório de qualidade:', error);
        showNotification('Erro ao gerar relatório: ' + error.message, 'error');
    }
}

async function generateFinancialReport() {
    try {
        showCustomLoading('Gerando relatório financeiro...', 'generate');
        
        // // const supabase = await getSupabaseClient(); // REMOVIDO // REMOVIDO - usando MySQL
        const { data: { user } } = await supabase.auth.getUser();
        
        if (!user) {
            throw new Error('Usuário não autenticado');
        }

        // Buscar dados do usuário para obter farm_id
        const { data: userData } = await supabase
            .from('users')
            .select('farm_id')
            .eq('id', user.id)
            .single();

        if (!userData?.farm_id) {
            throw new Error('Farm ID não encontrado');
        }

        // Buscar dados financeiros
        const { data: financialData, error } = await supabase
            .from('financial_records')
            .select('*')
            .eq('farm_id', userData.farm_id)
            .order('record_date', { ascending: false })
            .limit(100);

        if (error) {
            throw error;
        }

        // Gerar PDF
        await generatePaymentsPDF(financialData || []);
        
        hideCustomLoading();
        showNotification('Relatório financeiro gerado com sucesso!', 'success');
        
    } catch (error) {
        hideCustomLoading();
        console.error('Erro ao gerar relatório financeiro:', error);
        showNotification('Erro ao gerar relatório: ' + error.message, 'error');
    }
}


// ==================== RELATÓRIO PERSONALIZADO ====================
function openCustomReportModal() {
    const modal = document.getElementById('customReportModal');
    if (modal) {
        modal.classList.remove('hidden');
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        loadCustomReportSettings();
    }
}

function closeCustomReportModal() {
    const modal = document.getElementById('customReportModal');
    if (modal) {
        modal.classList.add('hidden');
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

async function loadCustomReportSettings() {
    try {
        // // const supabase = await getSupabaseClient(); // REMOVIDO // REMOVIDO - usando MySQL
        const { data: { user } } = await supabase.auth.getUser();
        
        if (!user) return;

        // Buscar dados do usuário
        const { data: userData, error: userError } = await supabase
            .from('users')
            .select('report_farm_name, report_farm_logo_base64, farm_id')
            .eq('id', user.id)
            .single();

        if (userError) {
            console.error('Erro ao buscar dados do usuário:', userError);
            return;
        }

        if (userData) {
            // Usar o nome personalizado salvo ou buscar o nome da fazenda
            let farmName = userData.report_farm_name;
            
            if (!farmName && userData.farm_id) {
                // Buscar nome da fazenda se não tiver personalizado
                const { data: farmData, error: farmError } = await supabase
                    .from('farms')
                    .select('name')
                    .eq('id', userData.farm_id)
                    .single();
                
                if (!farmError && farmData) {
                    farmName = farmData.name;
                }
            }
            
            // Fallback para nome padrão
            if (!farmName) {
                farmName = await getFarmName() || 'Minha Fazenda';
            }
            
            document.getElementById('customReportFarmName').value = farmName;
            
            if (userData.report_farm_logo_base64) {
                updateCustomReportLogoPreview(userData.report_farm_logo_base64);
            }
        }
    } catch (error) {
        console.error('Erro ao carregar configurações:', error);
    }
}

async function handleCustomReportLogoUpload(event) {
    const file = event.target.files[0];
    if (!file) return;
    
    if (!file.type.startsWith('image/')) {
        showNotification('Por favor, selecione um arquivo de imagem válido', 'error');
        return;
    }
    
    if (file.size > 2 * 1024 * 1024) {
        showNotification('A imagem deve ter no máximo 2MB', 'error');
        return;
    }
    
    try {
        const base64 = await fileToBase64(file);
        updateCustomReportLogoPreview(base64);
        showNotification('Logo carregada com sucesso!', 'success');
    } catch (error) {
        console.error('Erro ao processar logo:', error);
        showNotification('Erro ao processar a imagem', 'error');
    }
}

function updateCustomReportLogoPreview(base64Logo) {
    const preview = document.getElementById('customReportLogoPreview');
    const placeholder = document.getElementById('customReportLogoPlaceholder');
    const image = document.getElementById('customReportLogoImage');
    const removeBtn = document.getElementById('removeCustomReportLogo');
    
    if (base64Logo) {
        image.src = base64Logo;
        preview.classList.remove('hidden');
        placeholder.classList.add('hidden');
        removeBtn.classList.remove('hidden');
        } else {
        preview.classList.add('hidden');
        placeholder.classList.remove('hidden');
        removeBtn.classList.add('hidden');
    }
}

function removeCustomReportLogo() {
    updateCustomReportLogoPreview(null);
    document.getElementById('customReportLogoUpload').value = '';
    showNotification('Logo removida!', 'info');
}

async function saveCustomReportSettings() {
    try {
        showCustomLoading('Salvando configurações...', 'save');
        
        // // const supabase = await getSupabaseClient(); // REMOVIDO // REMOVIDO - usando MySQL
        const { data: { user } } = await supabase.auth.getUser();
        
        if (!user) {
            throw new Error('Usuário não autenticado');
        }

        const farmName = document.getElementById('customReportFarmName').value;
        const logoImage = document.getElementById('customReportLogoImage');
        const farmLogo = logoImage.src.includes('data:image') ? logoImage.src : null;

        // Atualizar diretamente na tabela users
        const { error } = await supabase
            .from('users')
            .update({
                report_farm_name: farmName,
                report_farm_logo_base64: farmLogo
            })
            .eq('id', user.id);

        if (error) {
            throw error;
        }

        // Atualizar configurações globais
        if (window.reportSettings) {
            window.reportSettings.farmName = farmName;
            window.reportSettings.farmLogo = farmLogo;
        }

        hideCustomLoading();
        showNotification('Configurações salvas com sucesso!', 'success');
        closeCustomReportModal();
        
    } catch (error) {
        hideCustomLoading();
        console.error('Erro ao salvar configurações:', error);
        showNotification('Erro ao salvar configurações: ' + error.message, 'error');
    }
}

async function generateCustomReport() {
    try {
        showCustomLoading('Gerando relatório personalizado...', 'generate');
        
        // // const supabase = await getSupabaseClient(); // REMOVIDO // REMOVIDO - usando MySQL
        const { data: { user } } = await supabase.auth.getUser();
        
        if (!user) {
            throw new Error('Usuário não autenticado');
        }

        // Buscar dados do usuário para obter farm_id
        const { data: userData } = await supabase
            .from('users')
            .select('farm_id')
            .eq('id', user.id)
            .single();

        if (!userData?.farm_id) {
            throw new Error('Farm ID não encontrado');
        }

        // Obter configurações do modal
        const farmName = document.getElementById('customReportFarmName').value;
        const logoImage = document.getElementById('customReportLogoImage');
        const farmLogo = logoImage.src.includes('data:image') ? logoImage.src : null;

        // Configurar window.reportSettings com as configurações do modal
        if (!window.reportSettings) {
            window.reportSettings = {};
        }
        window.reportSettings.farmName = farmName;
        window.reportSettings.farmLogo = farmLogo;

        console.log('Configurações do relatório personalizado:');
        console.log('- Nome da fazenda:', farmName);
        console.log('- Logo da fazenda:', farmLogo ? 'Carregada' : 'Não carregada');

        // Buscar dados de volume (relatório personalizado combina volume + qualidade)
        const { data: volumeData, error: volumeError } = await supabase
            .from('volume_records')
            .select('*')
            .eq('farm_id', userData.farm_id)
            .order('production_date', { ascending: false })
            .limit(50);

        if (volumeError) {
            throw volumeError;
        }

        // Gerar PDF personalizado (usando função de volume como base)
        await generateVolumePDF(volumeData || []);
        
        hideCustomLoading();
        showNotification('Relatório personalizado gerado com sucesso!', 'success');
        closeCustomReportModal();
        
    } catch (error) {
        hideCustomLoading();
        console.error('Erro ao gerar relatório personalizado:', error);
        showNotification('Erro ao gerar relatório: ' + error.message, 'error');
    }
}

// ==================== LOADING PERSONALIZADO ====================
function showCustomLoading(message, type) {
    const loadingModal = document.getElementById('customLoadingModal');
    const loadingMessage = document.getElementById('customLoadingMessage');
    const loadingIcon = document.getElementById('customLoadingIcon');
    
    if (loadingModal && loadingMessage && loadingIcon) {
        loadingMessage.textContent = message;
        
        // Definir ícone baseado no tipo
        if (type === 'save') {
            loadingIcon.innerHTML = `
                <svg class="w-8 h-8 text-indigo-600 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
            `;
        } else if (type === 'generate') {
            loadingIcon.innerHTML = `
                <svg class="w-8 h-8 text-green-600 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
            `;
        }
        
        loadingModal.classList.remove('hidden');
        loadingModal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
}

function hideCustomLoading() {
    const loadingModal = document.getElementById('customLoadingModal');
    if (loadingModal) {
        loadingModal.classList.add('hidden');
        loadingModal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}
