// =====================================================
// CONFIGURAÇÃO DO SISTEMA DE PAGAMENTO PIX
// =====================================================

const PAYMENT_CONFIG = {
    // Configurações da chave Pix
    PIX_KEY: 'slavosier298@gmail.com', // Substitua pela sua chave Pix
    PIX_KEY_TYPE: 'email', // email, cpf, telefone, aleatoria
    
    // Configurações de preço
    MONTHLY_PRICE: 1.00,
    YEARLY_PRICE: 2.00,
    
    // Configurações de tempo
    PAYMENT_TIMEOUT: 30 * 60 * 1000, // 30 minutos em millisegundos
    SUBSCRIPTION_DURATION: 30, // dias
    
    // Configurações de verificação
    PAYMENT_CHECK_INTERVAL: 10000, // 10 segundos
    AUTO_CHECK_ENABLED: true,
    
    // Configurações de notificação
    NOTIFICATION_DURATION: 5000, // 5 segundos
    
    // Configurações de UI
    SHOW_QR_CODE: true,
    SHOW_COPY_BUTTON: true,
    SHOW_COUNTDOWN: true,
    
    // Configurações de segurança
    MAX_PAYMENT_ATTEMPTS: 5,
    BLOCK_DURATION: 60 * 60 * 1000, // 1 hora em millisegundos
    
    // Configurações de integração (para futuro)
    WEBHOOK_URL: null, // URL para webhook de confirmação
    API_KEY: null, // Chave da API bancária
    
    // Configurações de ambiente
    ENVIRONMENT: 'development', // development, staging, production
    DEBUG_MODE: true,
    
    // Configurações de backup
    BACKUP_PAYMENT_METHODS: [
        {
            type: 'pix',
            key: 'backup-chave-pix@email.com',
            keyType: 'email'
        }
    ]
};

// =====================================================
// FUNÇÕES DE UTILIDADE
// =====================================================

class PaymentUtils {
    static formatCurrency(amount) {
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(amount);
    }
    
    static formatDate(date) {
        return new Intl.DateTimeFormat('pt-BR', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        }).format(new Date(date));
    }
    
    static calculateTimeLeft(expiresAt) {
        const now = new Date();
        const expires = new Date(expiresAt);
        const diff = expires - now;
        
        if (diff <= 0) return 'Expirado';
        
        const minutes = Math.floor(diff / 60000);
        const seconds = Math.floor((diff % 60000) / 1000);
        
        return `${minutes}:${seconds.toString().padStart(2, '0')}`;
    }
    
    static generateTxId(userId) {
        const timestamp = Date.now();
        const random = Math.random().toString(36).substring(2, 15);
        return `${userId}_${timestamp}_${random}`;
    }
    
    static validatePixKey(key, type) {
        const validTypes = ['email', 'cpf', 'telefone', 'aleatoria'];
        
        if (!validTypes.includes(type)) {
            return { valid: false, error: 'Tipo de chave inválido' };
        }
        
        switch (type) {
            case 'email':
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return { valid: emailRegex.test(key), error: 'E-mail inválido' };
                
            case 'cpf':
                const cpfRegex = /^\d{11}$/;
                return { valid: cpfRegex.test(key), error: 'CPF inválido' };
                
            case 'telefone':
                const phoneRegex = /^\+55\d{10,11}$/;
                return { valid: phoneRegex.test(key), error: 'Telefone inválido' };
                
            case 'aleatoria':
                const randomRegex = /^[a-fA-F0-9]{8}-[a-fA-F0-9]{4}-[a-fA-F0-9]{4}-[a-fA-F0-9]{4}-[a-fA-F0-9]{12}$/;
                return { valid: randomRegex.test(key), error: 'Chave aleatória inválida' };
                
            default:
                return { valid: false, error: 'Tipo de chave não suportado' };
        }
    }
    
    static log(message, type = 'info') {
        if (PAYMENT_CONFIG.DEBUG_MODE) {
            const timestamp = new Date().toISOString();
            const prefix = type.toUpperCase();
            console.log(`[${timestamp}] [${prefix}] ${message}`);
        }
    }
    
    static showError(message) {
        PaymentUtils.log(message, 'error');
        if (typeof showNotification === 'function') {
            showNotification(message, 'error');
        }
    }
    
    static showSuccess(message) {
        PaymentUtils.log(message, 'success');
        if (typeof showNotification === 'function') {
            showNotification(message, 'success');
        }
    }
    
    static showWarning(message) {
        PaymentUtils.log(message, 'warning');
        if (typeof showNotification === 'function') {
            showNotification(message, 'warning');
        }
    }
    
    static showInfo(message) {
        PaymentUtils.log(message, 'info');
        if (typeof showNotification === 'function') {
            showNotification(message, 'info');
        }
    }
}

// =====================================================
// VALIDAÇÕES DE SEGURANÇA
// =====================================================

class PaymentSecurity {
    static validatePaymentRequest(userId, amount) {
        // Verificar se o usuário não está bloqueado
        const blockedUsers = JSON.parse(localStorage.getItem('blockedUsers') || '{}');
        const userBlock = blockedUsers[userId];
        
        if (userBlock && userBlock.until > Date.now()) {
            const remainingTime = Math.ceil((userBlock.until - Date.now()) / 60000);
            throw new Error(`Usuário bloqueado por ${remainingTime} minutos`);
        }
        
        // Verificar tentativas de pagamento
        const attempts = JSON.parse(localStorage.getItem(`paymentAttempts_${userId}`) || '0');
        if (attempts >= PAYMENT_CONFIG.MAX_PAYMENT_ATTEMPTS) {
            // Bloquear usuário
            blockedUsers[userId] = {
                until: Date.now() + PAYMENT_CONFIG.BLOCK_DURATION,
                reason: 'Muitas tentativas de pagamento'
            };
            localStorage.setItem('blockedUsers', JSON.stringify(blockedUsers));
            localStorage.setItem(`paymentAttempts_${userId}`, '0');
            
            throw new Error('Muitas tentativas de pagamento. Tente novamente em 1 hora.');
        }
        
        // Validar valor
        if (amount !== PAYMENT_CONFIG.MONTHLY_PRICE && amount !== PAYMENT_CONFIG.YEARLY_PRICE) {
            throw new Error('Valor de pagamento inválido');
        }
        
        return true;
    }
    
    static recordPaymentAttempt(userId) {
        const attempts = JSON.parse(localStorage.getItem(`paymentAttempts_${userId}`) || '0');
        localStorage.setItem(`paymentAttempts_${userId}`, JSON.stringify(attempts + 1));
    }
    
    static clearPaymentAttempts(userId) {
        localStorage.removeItem(`paymentAttempts_${userId}`);
    }
    
    static isUserBlocked(userId) {
        const blockedUsers = JSON.parse(localStorage.getItem('blockedUsers') || '{}');
        const userBlock = blockedUsers[userId];
        
        if (userBlock && userBlock.until > Date.now()) {
            return true;
        }
        
        // Remover bloqueio se expirou
        if (userBlock && userBlock.until <= Date.now()) {
            delete blockedUsers[userId];
            localStorage.setItem('blockedUsers', JSON.stringify(blockedUsers));
        }
        
        return false;
    }
}

// =====================================================
// CONFIGURAÇÕES DE AMBIENTE
// =====================================================

const ENVIRONMENT_CONFIG = {
    development: {
        PIX_KEY: 'slavosier298@gmail.com',
        DEBUG_MODE: true,
        PAYMENT_CHECK_INTERVAL: 5000, // 5 segundos para desenvolvimento
        AUTO_CHECK_ENABLED: true
    },
    staging: {
        PIX_KEY: 'staging-chave-pix@email.com',
        DEBUG_MODE: true,
        PAYMENT_CHECK_INTERVAL: 10000,
        AUTO_CHECK_ENABLED: true
    },
    production: {
        PIX_KEY: PAYMENT_CONFIG.PIX_KEY,
        DEBUG_MODE: false,
        PAYMENT_CHECK_INTERVAL: 15000, // 15 segundos para produção
        AUTO_CHECK_ENABLED: true
    }
};

// Aplicar configurações de ambiente
const currentEnv = ENVIRONMENT_CONFIG[PAYMENT_CONFIG.ENVIRONMENT] || ENVIRONMENT_CONFIG.development;
Object.assign(PAYMENT_CONFIG, currentEnv);

// =====================================================
// EXPORTAÇÃO
// =====================================================

if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        PAYMENT_CONFIG,
        PaymentUtils,
        PaymentSecurity
    };
} else {
    window.PAYMENT_CONFIG = PAYMENT_CONFIG;
    window.PaymentUtils = PaymentUtils;
    window.PaymentSecurity = PaymentSecurity;
} 