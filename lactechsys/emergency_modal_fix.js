// =====================================================
// SCRIPT DE EMERGÊNCIA - CORREÇÃO DO MODAL AUTOMÁTICO
// =====================================================
// Execute este script no console do navegador se o modal ainda abrir automaticamente

(function() {
    'use strict';
    
    console.log('🚨 SCRIPT DE EMERGÊNCIA ATIVADO');
    
    // Função para fechar modal
    function emergencyCloseModal() {
        const modal = document.getElementById('deleteUserModal');
        if (modal) {
            modal.classList.add('hidden');
            // Remover backdrop/overlay
            modal.style.display = 'none';
            modal.style.visibility = 'hidden';
            modal.style.opacity = '0';
            modal.style.pointerEvents = 'none';
            console.log('✅ Modal fechado em emergência');
        }
        
        // Limpar variável
        if (typeof userToDelete !== 'undefined') {
            userToDelete = null;
        }
    }
    
    // Fechar modal imediatamente
    emergencyCloseModal();
    
    // Interceptar TODAS as tentativas de abrir o modal
    const originalClassListRemove = DOMTokenList.prototype.remove;
    DOMTokenList.prototype.remove = function(...tokens) {
        const element = this.value;
        if (element && element.id === 'deleteUserModal' && tokens.includes('hidden')) {
            console.log('🚫 Tentativa de abrir modal interceptada e bloqueada');
            return;
        }
        return originalClassListRemove.apply(this, tokens);
    };
    
    // Interceptar classList.add também
    const originalClassListAdd = DOMTokenList.prototype.add;
    DOMTokenList.prototype.add = function(...tokens) {
        const element = this.value;
        if (element && element.id === 'deleteUserModal' && tokens.includes('show')) {
            console.log('🚫 Tentativa de mostrar modal interceptada e bloqueada');
            return;
        }
        return originalClassListAdd.apply(this, tokens);
    };
    
    // Interceptar style.display
    const originalStyleSetter = Object.getOwnPropertyDescriptor(HTMLElement.prototype, 'style').set;
    Object.defineProperty(HTMLElement.prototype, 'style', {
        set: function(value) {
            if (this.id === 'deleteUserModal' && value && value.display === 'block') {
                console.log('🚫 Tentativa de mostrar modal via style interceptada');
                return;
            }
            return originalStyleSetter.call(this, value);
        },
        get: function() {
            return originalStyleSetter.call(this);
        }
    });
    
    // Proteção contínua
    setInterval(emergencyCloseModal, 100);
    
    // Listener para ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            emergencyCloseModal();
        }
    });
    
    // Substituir a função deleteUser completamente
    window.deleteUser = function(userId, userName) {
        console.log('🚫 Função deleteUser bloqueada em emergência');
        return false;
    };
    
    console.log('🛡️ Proteção de emergência ativada - Modal completamente bloqueado');
    console.log('Para usar a função de exclusão, recarregue a página (F5)');
    
})();
