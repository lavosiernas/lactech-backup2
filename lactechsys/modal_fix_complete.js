// =====================================================
// CORREÇÃO DEFINITIVA DO MODAL AUTOMÁTICO
// =====================================================

(function() {
    'use strict';
    
    console.log('🛡️ Iniciando proteção contra modal automático...');
    
    // Função para fechar modal de exclusão
    function closeDeleteModal() {
        const deleteModal = document.getElementById('deleteUserModal');
        if (deleteModal && !deleteModal.classList.contains('hidden')) {
            deleteModal.classList.add('hidden');
            console.log('✅ Modal de exclusão fechado');
        }
        
        // Limpar variável userToDelete
        if (typeof userToDelete !== 'undefined') {
            userToDelete = null;
        }
    }
    
    // Função para verificar se o modal foi aberto legitimamente
    function isModalLegitimatelyOpened() {
        return userToDelete && userToDelete.id && userToDelete.name;
    }
    
    // Fechar modal imediatamente se estiver aberto
    closeDeleteModal();
    
    // Proteção contra abertura automática
    let modalOpenTime = null;
    const originalRemoveHidden = Element.prototype.remove;
    
    // Interceptar remoção da classe 'hidden' do modal
    Element.prototype.remove = function(className) {
        if (this.id === 'deleteUserModal' && className === 'hidden') {
            // Verificar se foi aberto legitimamente
            if (!isModalLegitimatelyOpened()) {
                console.log('⚠️ Tentativa de abertura não autorizada do modal, bloqueando...');
                return;
            }
            modalOpenTime = Date.now();
        }
        return originalRemoveHidden.call(this, className);
    };
    
    // Listener para ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeDeleteModal();
        }
    });
    
    // Interceptar erros JavaScript
    window.addEventListener('error', function(event) {
        console.log('Erro JavaScript detectado:', event.error);
        closeDeleteModal();
    });
    
    // Interceptar promises rejeitadas
    window.addEventListener('unhandledrejection', function(event) {
        console.log('Promise rejeitada detectada:', event.reason);
        closeDeleteModal();
    });
    
    // Verificação periódica
    setInterval(function() {
        const deleteModal = document.getElementById('deleteUserModal');
        if (deleteModal && !deleteModal.classList.contains('hidden')) {
            // Se o modal está aberto há mais de 5 segundos sem ação legítima
            if (modalOpenTime && (Date.now() - modalOpenTime > 5000) && !isModalLegitimatelyOpened()) {
                console.log('⚠️ Modal aberto por muito tempo sem ação, fechando...');
                closeDeleteModal();
                modalOpenTime = null;
            }
        }
    }, 1000);
    
    // Proteção adicional no carregamento da página
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', closeDeleteModal);
    } else {
        closeDeleteModal();
    }
    
    // Proteção no evento load
    window.addEventListener('load', closeDeleteModal);
    
    console.log('✅ Proteção contra modal automático ativada com sucesso');
    
    // Expor função para uso manual se necessário
    window.fixModalIssue = closeDeleteModal;
    
})();
