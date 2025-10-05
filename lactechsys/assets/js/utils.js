// =====================================================
// UTILITÁRIOS - LACTECH
// =====================================================
// Arquivo 3 de 5 - Funções auxiliares
// =====================================================

// Aguardar API estar disponível
window.waitForAPI = function() {
    return new Promise((resolve) => {
        const checkAPI = () => {
            if (window.LacTechAPI) {
                resolve(window.LacTechAPI);
            } else {
                setTimeout(checkAPI, 100);
            }
        };
        checkAPI();
    });
};

// Formatar data
window.formatDate = function(date) {
    if (!date) return '';
    const d = new Date(date);
    return d.toLocaleDateString('pt-BR');
};

// Formatar moeda
window.formatCurrency = function(value) {
    if (!value) return 'R$ 0,00';
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(value);
};

// Formatar volume
window.formatVolume = function(value) {
    if (!value) return '0 L';
    return `${parseFloat(value).toFixed(2)} L`;
};

// Formatar temperatura
window.formatTemperature = function(value) {
    if (!value) return '--';
    return `${parseFloat(value).toFixed(1)}°C`;
};

// Formatar porcentagem
window.formatPercentage = function(value) {
    if (!value) return '--';
    return `${parseFloat(value).toFixed(2)}%`;
};

// Gerar email a partir do nome
window.generateEmail = function(name) {
    if (!name) return '';
    const cleanName = name.toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9]/g, '');
    return `${cleanName}@lactech.com`;
};

// Validar email
window.validateEmail = function(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
};

// Validar CNPJ
window.validateCNPJ = function(cnpj) {
    cnpj = cnpj.replace(/[^\d]/g, '');
    if (cnpj.length !== 14) return false;
    
    // Verificar dígitos repetidos
    if (/^(\d)\1+$/.test(cnpj)) return false;
    
    // Validar dígitos verificadores
    let sum = 0;
    let weight = 2;
    
    for (let i = 11; i >= 0; i--) {
        sum += parseInt(cnpj.charAt(i)) * weight;
        weight = weight === 9 ? 2 : weight + 1;
    }
    
    let digit = 11 - (sum % 11);
    if (digit > 9) digit = 0;
    
    if (parseInt(cnpj.charAt(12)) !== digit) return false;
    
    sum = 0;
    weight = 2;
    
    for (let i = 12; i >= 0; i--) {
        sum += parseInt(cnpj.charAt(i)) * weight;
        weight = weight === 9 ? 2 : weight + 1;
    }
    
    digit = 11 - (sum % 11);
    if (digit > 9) digit = 0;
    
    return parseInt(cnpj.charAt(13)) === digit;
};

// Mostrar notificação
window.showNotification = function(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <span>${message}</span>
            <button onclick="this.parentElement.parentElement.remove()">×</button>
        </div>
    `;
    
    // Estilos básicos
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? '#4CAF50' : type === 'error' ? '#f44336' : '#2196F3'};
        color: white;
        padding: 15px 20px;
        border-radius: 5px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        z-index: 10000;
        max-width: 300px;
        animation: slideIn 0.3s ease;
    `;
    
    document.body.appendChild(notification);
    
    // Auto-remover após 5 segundos
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
};

// Carregar dados do usuário
window.loadUserData = async function() {
    try {
        const api = await window.waitForAPI();
        const { data: { user } } = await api.auth.getUser();
        
        if (!user) {
            window.location.href = 'login.php';
            return null;
        }
        
        const { data: userData } = await api.supabase
            .from('users')
            .select('*')
            .eq('id', user.id)
            .single();
        
        if (!userData) {
            window.location.href = 'login.php';
            return null;
        }
        
        return userData;
    } catch (error) {
        console.error('Erro ao carregar dados do usuário:', error);
        window.location.href = 'login.php';
        return null;
    }
};

// Verificar permissões
window.hasPermission = function(requiredRole) {
    const userRole = window.currentUser?.role;
    if (!userRole) return false;
    
    const roleHierarchy = {
        'proprietario': 4,
        'gerente': 3,
        'veterinario': 2,
        'funcionario': 1
    };
    
    return roleHierarchy[userRole] >= roleHierarchy[requiredRole];
};

// Carregar foto do usuário
window.loadUserPhoto = async function(userId, elementId) {
    try {
        const api = await window.waitForAPI();
        const { data: userData } = await api.supabase
            .from('users')
            .select('profile_photo_url')
            .eq('id', userId)
            .single();
        
        const element = document.getElementById(elementId);
        if (element && userData?.profile_photo_url) {
            element.src = userData.profile_photo_url;
        } else if (element) {
            element.src = 'assets/default-avatar.png';
        }
    } catch (error) {
        console.error('Erro ao carregar foto:', error);
        const element = document.getElementById(elementId);
        if (element) {
            element.src = 'assets/default-avatar.png';
        }
    }
};

// Logout
window.logout = async function() {
    try {
        const api = await window.waitForAPI();
        await api.auth.signOut();
        window.location.href = 'login.php';
    } catch (error) {
        console.error('Erro ao fazer logout:', error);
        window.location.href = 'login.php';
    }
};

console.log('✅ Utilitários carregados');
