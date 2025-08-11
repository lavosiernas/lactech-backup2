// =====================================================
// LIMPEZA FINAL - REMOVER COMPLETAMENTE OS MODAIS
// =====================================================
// Execute este script no console do navegador para limpeza final

(function() {
    'use strict';
    
    console.log('🧹 INICIANDO LIMPEZA FINAL...');
    
    // Função para limpar completamente
    function cleanupFinal() {
        // 1. Remover modais se existirem
        const deleteModal = document.getElementById('deleteUserModal');
        if (deleteModal) {
            deleteModal.remove();
            console.log('✅ Modal de exclusão removido do DOM');
        }
        
        const photoModal = document.getElementById('photoChoiceModal');
        if (photoModal) {
            photoModal.remove();
            console.log('✅ Modal de foto removido do DOM');
        }
        
        // 2. Limpar variáveis
        if (typeof userToDelete !== 'undefined') {
            userToDelete = null;
            console.log('✅ Variável userToDelete limpa');
        }
        
        // 3. Remover backdrop do body
        document.body.style.overflow = 'auto';
        document.body.style.position = 'static';
        document.body.style.pointerEvents = 'auto';
        console.log('✅ Body limpo');
        
        // 4. Remover qualquer elemento com backdrop/overlay
        const overlays = document.querySelectorAll('[class*="backdrop"], [class*="overlay"], [style*="opacity"]');
        overlays.forEach(overlay => {
            if (overlay.style.position === 'fixed' || overlay.style.position === 'absolute') {
                overlay.remove();
                console.log('✅ Overlay removido:', overlay.className);
            }
        });
        
        // 5. Substituir funções por versões simples
        window.deleteUser = function(userId, userName) {
            if (!event || !event.isTrusted) {
                console.log('🚫 Chamada não autorizada bloqueada');
                return;
            }
            
            const confirmed = confirm(`Tem certeza que deseja excluir o usuário "${userName}"?\n\nEsta ação não pode ser desfeita.`);
            
            if (confirmed) {
                console.log('Executando exclusão...');
                // Aqui você pode adicionar a lógica de exclusão
                alert('Função de exclusão será implementada');
            }
        };
        
        window.closePhotoChoiceModal = function() {
            console.log('🚫 Função closePhotoChoiceModal bloqueada');
            const photoModal = document.getElementById('photoChoiceModal');
            if (photoModal) {
                photoModal.remove();
            }
        };
        
        window.choosePhotoSource = function(source) {
            console.log('🚫 Função choosePhotoSource bloqueada');
            const photoModal = document.getElementById('photoChoiceModal');
            if (photoModal) {
                photoModal.remove();
            }
        };
        
        console.log('✅ Funções substituídas por versões simples');
    }
    
    // Executar limpeza
    cleanupFinal();
    
    // Executar periodicamente por 10 segundos
    let count = 0;
    const interval = setInterval(() => {
        cleanupFinal();
        count++;
        if (count >= 100) { // 10 segundos
            clearInterval(interval);
            console.log('✅ Limpeza final concluída');
        }
    }, 100);
    
    console.log('🛡️ Limpeza final ativada - Todos os modais completamente removidos');
    
})();
