// =====================================================
// CORREÇÃO DO MODAL QUE ABRE AUTOMATICAMENTE
// =====================================================
// Execute este script no console do navegador para corrigir o modal

// 1. Fechar o modal de exclusão se estiver aberto
function fixModalIssue() {
    const deleteModal = document.getElementById('deleteUserModal');
    if (deleteModal && !deleteModal.classList.contains('hidden')) {
        deleteModal.classList.add('hidden');
        console.log('✅ Modal de exclusão fechado');
    }
    
    // 2. Limpar variável userToDelete se existir
    if (typeof userToDelete !== 'undefined' && userToDelete !== null) {
        userToDelete = null;
        console.log('✅ Variável userToDelete limpa');
    }
    
    // 3. Verificar se há algum código executando automaticamente
    console.log('🔍 Verificando se há chamadas automáticas...');
    
    // 4. Adicionar listener para fechar modal com ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const deleteModal = document.getElementById('deleteUserModal');
            if (deleteModal && !deleteModal.classList.contains('hidden')) {
                deleteModal.classList.add('hidden');
                userToDelete = null;
                console.log('✅ Modal fechado com ESC');
            }
        }
    });
    
    console.log('✅ Script de correção executado');
}

// Executar correção
fixModalIssue();

// Instruções para o usuário:
console.log(`
📋 INSTRUÇÕES:
1. Se o modal ainda abrir automaticamente, recarregue a página (F5)
2. Se persistir, verifique se há algum código executando deleteUser() automaticamente
3. Use ESC para fechar o modal se necessário
`);
