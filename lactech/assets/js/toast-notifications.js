/**
 * Sistema de Notificações Toast
 * Gerencia notificações toast com agrupamento e limitação
 */
(function() {
    'use strict';
    
    const toastContainer = document.getElementById('toastContainer');
    const toastGroups = new Map(); // Para agrupamento de notificações similares
    const MAX_TOASTS = 5; // Máximo de toasts visíveis
    const GROUP_TIMEOUT = 1000; // Tempo para agrupar notificações similares
    
    function showToast(message, type = 'info', title = null, duration = 5000, groupKey = null) {
        if (!toastContainer) return;
        
        // Limitar número de toasts
        const existingToasts = toastContainer.querySelectorAll('.toast');
        if (existingToasts.length >= MAX_TOASTS) {
            // Remover o mais antigo
            const oldest = existingToasts[0];
            oldest.classList.remove('show');
            setTimeout(() => oldest.remove(), 400);
        }
        
        // Agrupamento de notificações similares
        if (groupKey) {
            const existingGroup = toastGroups.get(groupKey);
            if (existingGroup && Date.now() - existingGroup.timestamp < GROUP_TIMEOUT) {
                // Atualizar toast existente no grupo
                existingGroup.count++;
                existingGroup.toast.querySelector('.toast-message').textContent = 
                    `${message} (${existingGroup.count}x)`;
                existingGroup.timestamp = Date.now();
                return existingGroup.toast;
            }
        }
        
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', type === 'error' ? 'assertive' : 'polite');
        toast.setAttribute('aria-atomic', 'true');
        
        // Ícones por tipo
        const icons = {
            success: '<svg class="toast-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
            error: '<svg class="toast-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
            warning: '<svg class="toast-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>',
            info: '<svg class="toast-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>'
        };
        
        // Títulos padrão por tipo
        const defaultTitles = {
            success: 'Sucesso',
            error: 'Erro',
            warning: 'Atenção',
            info: 'Informação'
        };
        
        const displayTitle = title || defaultTitles[type] || 'Notificação';
        
        toast.innerHTML = `
            ${icons[type] || icons.info}
            <div class="toast-content">
                <div class="toast-title">${displayTitle}</div>
                <div class="toast-message">${message}</div>
            </div>
            <button class="toast-close" onclick="this.parentElement.remove()" aria-label="Fechar notificação">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 16px; height: 16px;" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        `;
        
        toastContainer.appendChild(toast);
        
        // Trigger animation
        setTimeout(() => toast.classList.add('show'), 10);
        
        // Registrar no grupo se necessário
        if (groupKey) {
            toastGroups.set(groupKey, {
                toast: toast,
                count: 1,
                timestamp: Date.now()
            });
        }
        
        // Auto remove
        if (duration > 0) {
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => {
                    toast.remove();
                    if (groupKey) {
                        toastGroups.delete(groupKey);
                    }
                }, 400);
            }, duration);
        }
        
        return toast;
    }
    
    // Exportar funções globais
    window.showToast = showToast;
    window.showSuccessToast = (message, title, groupKey) => showToast(message, 'success', title, 5000, groupKey);
    window.showErrorToast = (message, title, groupKey) => showToast(message, 'error', title, 7000, groupKey);
    window.showWarningToast = (message, title, groupKey) => showToast(message, 'warning', title, 6000, groupKey);
    window.showInfoToast = (message, title, groupKey) => showToast(message, 'info', title, 5000, groupKey);
})();

