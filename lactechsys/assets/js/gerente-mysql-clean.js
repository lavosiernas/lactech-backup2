/**
 * ============================================
 * GERENTE MYSQL - 100% MYSQL - ZERO SUPABASE
 * ============================================
 * Sistema de Gestão Leiteira - Lagoa do Mato
 * Versão: 2.0 MySQL Completo
 */

// ============================================
// CONFIGURAÇÃO
// ============================================

const APP_CONFIG = {
    farmName: 'Lagoa do Mato',
    farmId: 1,
    apiBaseUrl: '/lactechsys/api/',
    debug: true
};

// ============================================
// UTILITÁRIOS
// ============================================

function log(message, type = 'info') {
    if (!APP_CONFIG.debug) return;
    const emoji = { info: 'ℹ️', success: '✅', error: '❌', warning: '⚠️' }[type] || 'ℹ️';
    console.log(`${emoji} ${message}`);
}

// ============================================
// AUTENTICAÇÃO E SESSÃO
// ============================================

function getCurrentUser() {
    const userData = localStorage.getItem('user_data');
    if (!userData) {
        throw new Error('Usuário não autenticado');
    }
    return JSON.parse(userData);
}

function clearUserSession() {
    localStorage.clear();
    sessionStorage.clear();
}

function checkAuthentication() {
    try {
        const user = getCurrentUser();
        if (!user.id || !user.email) {
            throw new Error('Dados de usuário inválidos');
        }
        window.currentUser = user;
        return true;
    } catch (error) {
        log('Usuário não autenticado, redirecionando...', 'warning');
        setTimeout(() => {
            window.location.href = 'login.php';
        }, 1000);
        return false;
    }
}

// ============================================
// API MYSQL
// ============================================

async function apiRequest(endpoint, options = {}) {
    const url = APP_CONFIG.apiBaseUrl + endpoint;
    
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
        
        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Erro na requisição');
        }
        
        return data;
    } catch (error) {
        log(`Erro na API ${endpoint}: ${error.message}`, 'error');
        throw error;
    }
}

// ============================================
// DADOS - ANIMAIS
// ============================================

async function loadAnimals() {
    try {
        const data = await apiRequest('animals/list.php');
        return data.animals || [];
    } catch (error) {
        log('Erro ao carregar animais', 'error');
        return [];
    }
}

async function createAnimal(animalData) {
    try {
        const data = await apiRequest('animals/create.php', {
            method: 'POST',
            body: animalData
        });
        showNotification('Animal cadastrado com sucesso!', 'success');
        return data.animal;
    } catch (error) {
        showNotification('Erro ao cadastrar animal: ' + error.message, 'error');
        throw error;
    }
}

async function updateAnimal(animalId, animalData) {
    try {
        const data = await apiRequest(`animals/update.php?id=${animalId}`, {
            method: 'POST',
            body: animalData
        });
        showNotification('Animal atualizado com sucesso!', 'success');
        return data.animal;
    } catch (error) {
        showNotification('Erro ao atualizar animal: ' + error.message, 'error');
        throw error;
    }
}

async function deleteAnimal(animalId) {
    try {
        await apiRequest(`animals/delete.php?id=${animalId}`, {
            method: 'POST'
        });
        showNotification('Animal removido com sucesso!', 'success');
    } catch (error) {
        showNotification('Erro ao remover animal: ' + error.message, 'error');
        throw error;
    }
}

// ============================================
// DADOS - VOLUME DE LEITE
// ============================================

async function loadVolumeRecords() {
    try {
        const data = await apiRequest('volume/list.php');
        return data.records || [];
    } catch (error) {
        log('Erro ao carregar registros de volume', 'error');
        return [];
    }
}

async function createVolumeRecord(volumeData) {
    try {
        const data = await apiRequest('volume/create.php', {
            method: 'POST',
            body: volumeData
        });
        showNotification('Volume registrado com sucesso!', 'success');
        return data.record;
    } catch (error) {
        showNotification('Erro ao registrar volume: ' + error.message, 'error');
        throw error;
    }
}

// ============================================
// DADOS - QUALIDADE
// ============================================

async function loadQualityTests() {
    try {
        const data = await apiRequest('quality/list.php');
        return data.tests || [];
    } catch (error) {
        log('Erro ao carregar testes de qualidade', 'error');
        return [];
    }
}

async function createQualityTest(testData) {
    try {
        const data = await apiRequest('quality/create.php', {
            method: 'POST',
            body: testData
        });
        showNotification('Teste de qualidade registrado!', 'success');
        return data.test;
    } catch (error) {
        showNotification('Erro ao registrar teste: ' + error.message, 'error');
        throw error;
    }
}

// ============================================
// DADOS - USUÁRIOS
// ============================================

async function loadUsers() {
    try {
        const data = await apiRequest('users/list.php');
        return data.users || [];
    } catch (error) {
        log('Erro ao carregar usuários', 'error');
        return [];
    }
}

async function createUser(userData) {
    try {
        const data = await apiRequest('users/create.php', {
            method: 'POST',
            body: userData
        });
        showNotification('Usuário criado com sucesso!', 'success');
        return data.user;
    } catch (error) {
        showNotification('Erro ao criar usuário: ' + error.message, 'error');
        throw error;
    }
}

// ============================================
// NOTIFICAÇÕES
// ============================================

function showNotification(message, type = 'success') {
    const toast = document.getElementById('notificationToast');
    const toastMessage = document.getElementById('toastMessage');
    
    if (toast && toastMessage) {
        toastMessage.textContent = message;
        
        // Mudar cor baseado no tipo
        if (type === 'error') {
            toast.classList.add('bg-red-500');
            toast.classList.remove('bg-green-500');
        } else {
            toast.classList.add('bg-green-500');
            toast.classList.remove('bg-red-500');
        }
        
        toast.classList.remove('hidden');
        toast.classList.add('show');
        
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                toast.classList.add('hidden');
            }, 300);
        }, 3000);
    }
}

function hideNotification() {
    const toast = document.getElementById('notificationToast');
    if (toast) {
        toast.classList.remove('show');
        setTimeout(() => {
            toast.classList.add('hidden');
        }, 300);
    }
}

// ============================================
// MODAIS
// ============================================

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = 'auto';
    }
}

// ============================================
// NAVEGAÇÃO
// ============================================

function showTab(tabName) {
    // Esconder todas as tabs
    document.querySelectorAll('[data-tab]').forEach(tab => {
        tab.classList.add('hidden');
    });
    
    // Mostrar tab selecionada
    const selectedTab = document.querySelector(`[data-tab="${tabName}"]`);
    if (selectedTab) {
        selectedTab.classList.remove('hidden');
    }
    
    // Atualizar botões de navegação
    document.querySelectorAll('[data-tab-btn]').forEach(btn => {
        btn.classList.remove('active', 'bg-green-50', 'text-green-600');
        if (btn.getAttribute('data-tab-btn') === tabName) {
            btn.classList.add('active', 'bg-green-50', 'text-green-600');
        }
    });
}

// ============================================
// LOGOUT
// ============================================

async function logout() {
    try {
        clearUserSession();
        showNotification('Logout realizado com sucesso!', 'success');
        setTimeout(() => {
            window.location.href = 'login.php';
        }, 1000);
    } catch (error) {
        log('Erro ao fazer logout', 'error');
    }
}

// ============================================
// INICIALIZAÇÃO
// ============================================

async function initializePage() {
    try {
        log('Iniciando sistema...', 'info');
        
        // Verificar autenticação
        if (!checkAuthentication()) {
            return;
        }
        
        const user = getCurrentUser();
        log(`Usuário autenticado: ${user.name || user.email}`, 'success');
        
        // Atualizar UI com dados do usuário
        const userNameElement = document.getElementById('userName');
        if (userNameElement) {
            userNameElement.textContent = user.name || user.email;
        }
        
        const farmNameElement = document.getElementById('farmName');
        if (farmNameElement) {
            farmNameElement.textContent = APP_CONFIG.farmName;
        }
        
        // Carregar dados iniciais
        log('Carregando dados do dashboard...', 'info');
        await loadDashboardData();
        
        log('Sistema inicializado com sucesso!', 'success');
        
    } catch (error) {
        log('Erro ao inicializar página: ' + error.message, 'error');
        showNotification('Erro ao carregar dados do sistema', 'error');
    }
}

async function loadDashboardData() {
    try {
        // Carregar estatísticas gerais
        await updateStatistics();
        
        // Carregar gráficos
        // await loadCharts();
        
    } catch (error) {
        log('Erro ao carregar dashboard', 'error');
    }
}

async function updateStatistics() {
    try {
        const stats = await apiRequest('stats/dashboard.php');
        
        // Atualizar contadores na UI
        if (stats.totalAnimals !== undefined) {
            const el = document.getElementById('totalAnimals');
            if (el) el.textContent = stats.totalAnimals;
        }
        
        if (stats.activeAnimals !== undefined) {
            const el = document.getElementById('activeAnimals');
            if (el) el.textContent = stats.activeAnimals;
        }
        
        if (stats.todayProduction !== undefined) {
            const el = document.getElementById('todayProduction');
            if (el) el.textContent = stats.todayProduction;
        }
        
    } catch (error) {
        log('Erro ao atualizar estatísticas', 'error');
    }
}

// ============================================
// EVENT LISTENERS
// ============================================

document.addEventListener('DOMContentLoaded', async function() {
    log('DOM carregado, inicializando...', 'info');
    
    await initializePage();
    
    // Configurar event listeners para formulários
    setupFormListeners();
});

function setupFormListeners() {
    // Formulário de adicionar animal
    const addAnimalForm = document.getElementById('addAnimalForm');
    if (addAnimalForm) {
        addAnimalForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());
            try {
                await createAnimal(data);
                e.target.reset();
                closeModal('addAnimalModal');
                await loadAnimals();
            } catch (error) {
                // Erro já tratado
            }
        });
    }
    
    // Adicionar mais listeners conforme necessário
}

// ============================================
// EXPOR FUNÇÕES GLOBAIS
// ============================================

window.showTab = showTab;
window.openModal = openModal;
window.closeModal = closeModal;
window.logout = logout;
window.showNotification = showNotification;
window.hideNotification = hideNotification;

log('Gerente MySQL carregado!', 'success');





