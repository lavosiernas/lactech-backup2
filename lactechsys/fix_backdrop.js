// =====================================================
// CORREÇÃO DO BACKDROP/OVERLAY QUE FICOU NA TELA
// =====================================================
// Execute este script no console do navegador para remover a opacidade

(function() {
    'use strict';
    
    console.log('🔧 Removendo backdrop/overlay...');
    
    // Função para remover backdrop
    function removeBackdrop() {
        // Remover modal de exclusão
        const deleteModal = document.getElementById('deleteUserModal');
        if (deleteModal) {
            deleteModal.style.display = 'none';
            deleteModal.style.visibility = 'hidden';
            deleteModal.style.opacity = '0';
            deleteModal.style.pointerEvents = 'none';
            deleteModal.classList.add('hidden');
            console.log('✅ Modal de exclusão fechado');
        }
        
        // Remover outros possíveis modais
        const allModals = document.querySelectorAll('[class*="modal"], [id*="modal"], [class*="overlay"], [id*="overlay"]');
        allModals.forEach(modal => {
            if (modal.style.position === 'fixed' || modal.style.position === 'absolute') {
                modal.style.display = 'none';
                modal.style.visibility = 'hidden';
                modal.style.opacity = '0';
                modal.style.pointerEvents = 'none';
                console.log('✅ Modal/overlay removido:', modal.id || modal.className);
            }
        });
        
        // Remover backdrop do body
        document.body.style.overflow = 'auto';
        document.body.style.position = 'static';
        
        // Limpar variáveis
        if (typeof userToDelete !== 'undefined') {
            userToDelete = null;
        }
    }
    
    // Executar imediatamente
    removeBackdrop();
    
    // Executar periodicamente por 5 segundos
    let count = 0;
    const interval = setInterval(() => {
        removeBackdrop();
        count++;
        if (count >= 25) { // 5 segundos
            clearInterval(interval);
            console.log('✅ Limpeza do backdrop concluída');
        }
    }, 200);
    
    console.log('🛡️ Backdrop será removido automaticamente');
    
})();
