// SCRIPT DE DEBUG PARA MODAL DE EXCLUSÃO
// Execute este script no console do navegador para identificar o problema

console.log('🔍 Iniciando debug do modal de exclusão...');

// 1. Verificar estado inicial do modal
const deleteModal = document.getElementById('deleteUserModal');
if (deleteModal) {
    console.log('✅ Modal encontrado');
    console.log('Estado inicial:', deleteModal.classList.contains('hidden') ? 'FECHADO' : 'ABERTO');
    
    if (!deleteModal.classList.contains('hidden')) {
        console.log('⚠️ PROBLEMA: Modal está aberto no carregamento!');
        deleteModal.classList.add('hidden');
        console.log('✅ Modal fechado forçadamente');
    }
} else {
    console.log('❌ Modal não encontrado!');
}

// 2. Verificar se userToDelete está definido
if (typeof userToDelete !== 'undefined') {
    console.log('userToDelete:', userToDelete);
} else {
    console.log('✅ userToDelete não está definido (correto)');
}

// 3. Interceptar chamadas para deleteUser
if (typeof deleteUser === 'function') {
    const originalDeleteUser = deleteUser;
    window.deleteUser = function(userId, userName) {
        console.log('🚨 deleteUser chamado:', {
            userId,
            userName,
            event: event ? {
                type: event.type,
                target: event.target.tagName,
                isTrusted: event.isTrusted
            } : 'SEM EVENTO',
            stack: new Error().stack.split('\n').slice(0, 3)
        });
        
        // Verificar se é uma chamada legítima
        if (!event || !event.isTrusted) {
            console.log('❌ Chamada bloqueada - não é um evento confiável');
            return;
        }
        
        return originalDeleteUser.call(this, userId, userName);
    };
    console.log('✅ deleteUser interceptado');
}

// 4. Monitorar mudanças no modal
if (deleteModal) {
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                const isHidden = mutation.target.classList.contains('hidden');
                console.log('🔄 Modal mudou estado:', isHidden ? 'FECHADO' : 'ABERTO');
                
                if (!isHidden) {
                    console.log('📍 Stack trace da abertura:', new Error().stack);
                }
            }
        });
    });
    observer.observe(deleteModal, { attributes: true, attributeFilter: ['class'] });
    console.log('✅ Observer configurado');
}

// 5. Verificar se há erros JavaScript
window.addEventListener('error', function(event) {
    console.log('❌ Erro JavaScript detectado:', event.error);
    
    // Fechar modal se estiver aberto devido a erro
    if (deleteModal && !deleteModal.classList.contains('hidden')) {
        deleteModal.classList.add('hidden');
        console.log('✅ Modal fechado devido a erro JavaScript');
    }
});

console.log('🎯 Debug configurado! Monitore o console para identificar quando o modal abre.');

// 6. Função para forçar fechamento do modal
window.forceCloseModal = function() {
    if (deleteModal) {
        deleteModal.classList.add('hidden');
        window.userToDelete = null;
        console.log('✅ Modal fechado forçadamente');
    }
};

console.log('💡 Use forceCloseModal() para fechar o modal manualmente se necessário');