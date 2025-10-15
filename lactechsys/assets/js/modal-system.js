/**
 * Sistema de Modais Modernos para LacTech
 * Substitui todos os window.alert, confirm e prompt por modais bonitos
 */

class ModalSystem {
    constructor() {
        this.activeModal = null;
        this.modalStack = [];
        this.init();
    }

    init() {
        // Criar container de modais
        this.createModalContainer();
        
        // Substituir funções nativas do navegador
        this.overrideNativeFunctions();
    }

    createModalContainer() {
        const container = document.createElement('div');
        container.id = 'modalSystemContainer';
        container.className = 'fixed inset-0 z-[9999] pointer-events-none';
        document.body.appendChild(container);
    }

    overrideNativeFunctions() {
        // Substituir window.alert
        const nativeAlert = window.alert;
        window.alert = (message, options = {}) => {
            this.showAlert(message, options);
        };

        // Substituir window.confirm
        const nativeConfirm = window.confirm;
        window.confirm = (message, options = {}) => {
            return this.showConfirm(message, options);
        };

        // Substituir window.prompt
        const nativePrompt = window.prompt;
        window.prompt = (message, defaultValue = '', options = {}) => {
            return this.showPrompt(message, defaultValue, options);
        };
    }

    // Modal de Alerta (substitui alert)
    async showAlert(message, options = {}) {
        const config = {
            title: options.title || 'Aviso',
            message: message,
            type: options.type || 'info', // info, success, warning, error
            duration: options.duration || 0, // 0 = não fecha automaticamente
            showIcon: options.showIcon !== false,
            ...options
        };

        return new Promise((resolve) => {
            const modal = this.createModal('alert', config);
            this.showModal(modal);
            
            // Configurar botão de fechar
            const closeBtn = modal.querySelector('.modal-close-btn');
            if (closeBtn) {
                closeBtn.onclick = () => {
                    this.hideModal(modal);
                    resolve();
                };
            }

            // Fechar automaticamente se duration > 0
            if (config.duration > 0) {
                setTimeout(() => {
                    this.hideModal(modal);
                    resolve();
                }, config.duration);
            }
        });
    }

    // Modal de Confirmação (substitui confirm)
    async showConfirm(message, options = {}) {
        const config = {
            title: options.title || 'Confirmar Ação',
            message: message,
            type: options.type || 'question',
            confirmText: options.confirmText || 'Confirmar',
            cancelText: options.cancelText || 'Cancelar',
            confirmColor: options.confirmColor || 'blue',
            cancelColor: options.cancelColor || 'gray',
            ...options
        };

        return new Promise((resolve) => {
            const modal = this.createModal('confirm', config);
            this.showModal(modal);
            
            // Botão confirmar
            const confirmBtn = modal.querySelector('.modal-confirm-btn');
            if (confirmBtn) {
                confirmBtn.onclick = () => {
                    this.hideModal(modal);
                    resolve(true);
                };
            }

            // Botão cancelar
            const cancelBtn = modal.querySelector('.modal-cancel-btn');
            if (cancelBtn) {
                cancelBtn.onclick = () => {
                    this.hideModal(modal);
                    resolve(false);
                };
            }

            // Fechar com ESC ou clicando fora
            this.setupModalClose(modal, () => {
                this.hideModal(modal);
                resolve(false);
            });
        });
    }

    // Modal de Input (substitui prompt)
    async showPrompt(message, defaultValue = '', options = {}) {
        const config = {
            title: options.title || 'Digite a Informação',
            message: message,
            placeholder: options.placeholder || 'Digite aqui...',
            defaultValue: defaultValue,
            inputType: options.inputType || 'text',
            required: options.required !== false,
            confirmText: options.confirmText || 'Confirmar',
            cancelText: options.cancelText || 'Cancelar',
            ...options
        };

        return new Promise((resolve) => {
            const modal = this.createModal('prompt', config);
            this.showModal(modal);
            
            const input = modal.querySelector('.modal-input');
            if (input) {
                input.focus();
                input.select();
            }

            // Botão confirmar
            const confirmBtn = modal.querySelector('.modal-confirm-btn');
            if (confirmBtn) {
                confirmBtn.onclick = () => {
                    const value = input ? input.value : '';
                    if (config.required && !value.trim()) {
                        input.classList.add('border-red-500');
                        return;
                    }
                    this.hideModal(modal);
                    resolve(value);
                };
            }

            // Botão cancelar
            const cancelBtn = modal.querySelector('.modal-cancel-btn');
            if (cancelBtn) {
                cancelBtn.onclick = () => {
                    this.hideModal(modal);
                    resolve(null);
                };
            }

            // Enter para confirmar, ESC para cancelar
            if (input) {
                input.onkeydown = (e) => {
                    if (e.key === 'Enter') {
                        confirmBtn.click();
                    } else if (e.key === 'Escape') {
                        cancelBtn.click();
                    }
                };
            }

            this.setupModalClose(modal, () => {
                this.hideModal(modal);
                resolve(null);
            });
        });
    }

    // Modal de Input de Email (específico para recuperação de senha)
    async showEmailPrompt(message, options = {}) {
        const config = {
            title: options.title || 'Recuperar Senha',
            message: message,
            placeholder: 'seu@email.com',
            confirmText: options.confirmText || 'Enviar Email',
            cancelText: options.cancelText || 'Cancelar',
            ...options
        };

        return this.showPrompt(message, '', {
            ...config,
            inputType: 'email',
            required: true,
            placeholder: config.placeholder
        });
    }

    // Criar modal baseado no tipo
    createModal(type, config) {
        const modal = document.createElement('div');
        modal.className = 'modal-overlay fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 pointer-events-auto';
        
        let modalContent = '';
        
        switch (type) {
            case 'alert':
                modalContent = this.createAlertContent(config);
                break;
            case 'confirm':
                modalContent = this.createConfirmContent(config);
                break;
            case 'prompt':
                modalContent = this.createPromptContent(config);
                break;
        }
        
        modal.innerHTML = modalContent;
        return modal;
    }

    // Conteúdo do modal de alerta
    createAlertContent(config) {
        const iconClass = this.getIconClass(config.type);
        const colorClass = this.getColorClass(config.type);
        
        return `
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full transform transition-all">
                <div class="flex items-center justify-between p-6 border-b border-gray-200">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 ${colorClass.bg} rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 ${colorClass.text}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                ${iconClass}
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">${config.title}</h3>
                            <p class="text-sm text-gray-500">${config.message}</p>
                        </div>
                    </div>
                    <button class="modal-close-btn text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="p-6">
                    <div class="flex justify-end">
                        <button class="modal-close-btn px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-xl transition-all">
                            OK
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    // Conteúdo do modal de confirmação
    createConfirmContent(config) {
        const iconClass = this.getIconClass(config.type);
        const colorClass = this.getColorClass(config.type);
        
        return `
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full transform transition-all">
                <div class="flex items-center justify-between p-6 border-b border-gray-200">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 ${colorClass.bg} rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 ${colorClass.text}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                ${iconClass}
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">${config.title}</h3>
                            <p class="text-sm text-gray-500">${config.message}</p>
                        </div>
                    </div>
                </div>
                <div class="p-6">
                    <div class="flex justify-end space-x-3">
                        <button class="modal-cancel-btn px-6 py-3 border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 transition-all font-medium">
                            ${config.cancelText}
                        </button>
                        <button class="modal-confirm-btn px-6 py-3 bg-${config.confirmColor}-600 hover:bg-${config.confirmColor}-700 text-white font-semibold rounded-xl transition-all">
                            ${config.confirmText}
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    // Conteúdo do modal de input
    createPromptContent(config) {
        const iconClass = this.getIconClass(config.type);
        const colorClass = this.getColorClass(config.type);
        
        return `
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full transform transition-all">
                <div class="flex items-center justify-between p-6 border-b border-gray-200">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 ${colorClass.bg} rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 ${colorClass.text}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                ${iconClass}
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">${config.title}</h3>
                            <p class="text-sm text-gray-500">${config.message}</p>
                        </div>
                    </div>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <input type="${config.inputType}" 
                               class="modal-input w-full px-4 py-3 border border-gray-200 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-100 focus:outline-none transition-all" 
                               placeholder="${config.placeholder}"
                               value="${config.defaultValue || ''}"
                               ${config.required ? 'required' : ''}>
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button class="modal-cancel-btn px-6 py-3 border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 transition-all font-medium">
                            ${config.cancelText}
                        </button>
                        <button class="modal-confirm-btn px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition-all">
                            ${config.confirmText}
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    // Obter classe do ícone baseado no tipo
    getIconClass(type) {
        switch (type) {
            case 'success':
                return '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>';
            case 'error':
                return '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>';
            case 'warning':
                return '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"></path>';
            case 'question':
                return '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>';
            default:
                return '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>';
        }
    }

    // Obter classe de cor baseada no tipo
    getColorClass(type) {
        switch (type) {
            case 'success':
                return { bg: 'bg-green-100', text: 'text-green-600' };
            case 'error':
                return { bg: 'bg-red-100', text: 'text-red-600' };
            case 'warning':
                return { bg: 'bg-yellow-100', text: 'text-yellow-600' };
            case 'question':
                return { bg: 'bg-blue-100', text: 'text-blue-600' };
            default:
                return { bg: 'bg-blue-100', text: 'text-blue-600' };
        }
    }

    // Mostrar modal
    showModal(modal) {
        const container = document.getElementById('modalSystemContainer');
        if (container) {
            container.appendChild(modal);
            this.activeModal = modal;
            this.modalStack.push(modal);
            
            // Animar entrada
            setTimeout(() => {
                modal.classList.add('opacity-100', 'scale-100');
            }, 10);
        }
    }

    // Esconder modal
    hideModal(modal) {
        if (modal && modal.parentNode) {
            modal.classList.remove('opacity-100', 'scale-100');
            setTimeout(() => {
                if (modal.parentNode) {
                    modal.parentNode.removeChild(modal);
                }
                this.modalStack = this.modalStack.filter(m => m !== modal);
                this.activeModal = this.modalStack[this.modalStack.length - 1] || null;
            }, 150);
        }
    }

    // Configurar fechamento do modal
    setupModalClose(modal, onClose) {
        // ESC key
        const handleEsc = (e) => {
            if (e.key === 'Escape') {
                onClose();
                document.removeEventListener('keydown', handleEsc);
            }
        };
        document.addEventListener('keydown', handleEsc);

        // Clicar fora
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                onClose();
                document.removeEventListener('keydown', handleEsc);
            }
        });
    }

    // Fechar todos os modais
    closeAll() {
        this.modalStack.forEach(modal => this.hideModal(modal));
        this.modalStack = [];
        this.activeModal = null;
    }
}

// Inicializar sistema de modais quando o DOM estiver pronto
let modalSystem = null;

document.addEventListener('DOMContentLoaded', () => {
    modalSystem = new ModalSystem();
});

// Exportar para uso global
window.ModalSystem = ModalSystem;
window.modalSystem = modalSystem;

// Funções de conveniência para uso direto
window.showAlert = (message, options) => modalSystem?.showAlert(message, options);
window.showConfirm = (message, options) => modalSystem?.showConfirm(message, options);
window.showPrompt = (message, defaultValue, options) => modalSystem?.showPrompt(message, defaultValue, options);
window.showEmailPrompt = (message, options) => modalSystem?.showEmailPrompt(message, options);
