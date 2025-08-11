// =====================================================
// COMANDO RÁPIDO - RESOLVER OPACIDADE IMEDIATAMENTE
// =====================================================
// Execute este script no console do navegador para resolver a opacidade

(function() {
    'use strict';
    
    console.log('⚡ COMANDO RÁPIDO ATIVADO - Removendo opacidade...');
    
    // Remover modal de foto imediatamente
    const photoModal = document.getElementById('photoChoiceModal');
    if (photoModal) {
        photoModal.remove();
        console.log('✅ Modal de foto removido');
    }
    
    // Remover modal de exclusão
    const deleteModal = document.getElementById('deleteUserModal');
    if (deleteModal) {
        deleteModal.remove();
        console.log('✅ Modal de exclusão removido');
    }
    
    // Limpar body
    document.body.style.overflow = 'auto';
    document.body.style.position = 'static';
    document.body.style.pointerEvents = 'auto';
    console.log('✅ Body limpo');
    
    // Remover qualquer overlay/backdrop
    const overlays = document.querySelectorAll('[class*="backdrop"], [class*="overlay"], [style*="opacity"]');
    overlays.forEach(overlay => {
        if (overlay.style.position === 'fixed' || overlay.style.position === 'absolute') {
            overlay.remove();
            console.log('✅ Overlay removido:', overlay.className);
        }
    });
    
    // Limpar variáveis
    if (typeof userToDelete !== 'undefined') {
        userToDelete = null;
    }
    
    console.log('🎉 OPACIDADE REMOVIDA COM SUCESSO!');
    
})();


