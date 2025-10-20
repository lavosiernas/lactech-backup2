// NOVO MODAL DE PERFIL - JAVASCRIPT SIMPLES

// Função para abrir o modal
function openProfileModal() {
    console.log('🔵 ABRINDO NOVO MODAL DE PERFIL...');
    
    const modal = document.getElementById('profileModal');
    if (!modal) {
        console.error('❌ Modal não encontrado!');
        return;
    }
    
    // Mostrar modal
    modal.classList.remove('hidden');
    modal.style.display = 'flex';
    
    // Bloquear scroll do body
    document.body.style.overflow = 'hidden';
    
    // Carregar dados do usuário
    loadUserData();
    
    console.log('✅ Modal aberto com sucesso!');
}

// Função para fechar o modal
function closeProfileModal() {
    console.log('🔴 FECHANDO MODAL DE PERFIL...');
    
    const modal = document.getElementById('profileModal');
    if (modal) {
        modal.classList.add('hidden');
        modal.style.display = 'none';
        
        // Restaurar scroll do body
        document.body.style.overflow = '';
        
        console.log('✅ Modal fechado com sucesso!');
    }
}

// Função para carregar dados do usuário
function loadUserData() {
    try {
        console.log('📊 Carregando dados do usuário...');
        
        // Buscar dados do usuário
        const userData = localStorage.getItem('user_data') || 
                        sessionStorage.getItem('user_data') || 
                        localStorage.getItem('userData') || 
                        sessionStorage.getItem('userData');
        
        if (userData) {
            const user = JSON.parse(userData);
            console.log('👤 Dados encontrados:', user);
            
            // Atualizar nome
            const nameElement = document.getElementById('profileName');
            if (nameElement) {
                nameElement.textContent = user.name || user.nome || 'Usuário';
            }
            
            // Atualizar cargo
            const roleElement = document.getElementById('profileRole');
            if (roleElement) {
                roleElement.textContent = user.role || user.cargo || 'Gerente';
            }
            
            // Atualizar fazenda
            const farmElement = document.getElementById('profileFarmName');
            if (farmElement) {
                farmElement.textContent = user.farm_name || user.fazenda || 'Fazenda';
            }
            
            // Atualizar nome completo
            const fullNameElement = document.getElementById('profileFullName');
            if (fullNameElement) {
                fullNameElement.textContent = user.name || user.nome || 'Usuário';
            }
            
            // Atualizar email
            const emailElement = document.getElementById('profileEmail');
            if (emailElement) {
                emailElement.textContent = user.email || 'Não informado';
            }
            
            // Atualizar WhatsApp
            const whatsappElement = document.getElementById('profileWhatsApp');
            if (whatsappElement) {
                whatsappElement.textContent = user.whatsapp || user.phone || 'Não informado';
            }
            
            console.log('✅ Dados carregados com sucesso!');
        } else {
            console.log('⚠️ Nenhum dado de usuário encontrado');
        }
        
    } catch (error) {
        console.error('❌ Erro ao carregar dados:', error);
    }
}

// Função para fechar modal ao clicar fora
function setupModalClickOutside() {
    const modal = document.getElementById('profileModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeProfileModal();
            }
        });
    }
}

// Função para fechar modal com ESC
function setupModalEscapeKey() {
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const modal = document.getElementById('profileModal');
            if (modal && !modal.classList.contains('hidden')) {
                closeProfileModal();
            }
        }
    });
}

// Inicializar quando a página carregar
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Inicializando novo modal de perfil...');
    
    // Configurar eventos
    setupModalClickOutside();
    setupModalEscapeKey();
    
    // Exportar funções para uso global
    window.openProfileModal = openProfileModal;
    window.closeProfileModal = closeProfileModal;
    window.loadUserData = loadUserData;
    
    console.log('✅ Modal de perfil inicializado!');
});

// Função de teste
window.testarNovoModal = function() {
    console.log('🧪 TESTANDO NOVO MODAL...');
    openProfileModal();
};
