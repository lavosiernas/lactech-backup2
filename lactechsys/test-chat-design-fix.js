// Script de Teste para Correções de Design do Chat
// Execute no console do navegador para testar

console.log('🎨 Testando correções de design do chat...');

// Teste 1: Verificar se o gerente não aparece na própria lista
async function testManagerNotInList() {
    console.log('👤 Testando se o gerente não aparece na própria lista...');
    
    try {
        const supabase = await getSupabaseClient();
        const { data: { user } } = await supabase.auth.getUser();
        
        if (!user) {
            console.error('❌ Usuário não autenticado');
            return false;
        }
        
        // Verificar se window.currentUser está definido
        if (!window.currentUser) {
            console.error('❌ window.currentUser não está definido');
            return false;
        }
        
        console.log('✅ window.currentUser definido:', window.currentUser.id);
        
        // Buscar funcionários
        const { data: userData } = await supabase
            .from('users')
            .select('farm_id')
            .eq('id', user.id)
            .single();
        
        if (!userData?.farm_id) {
            console.error('❌ Farm ID não encontrado');
            return false;
        }
        
        const employees = await getFarmUsers(userData.farm_id);
        console.log('👥 Total de funcionários encontrados:', employees.length);
        
        // Verificar se o gerente está na lista
        const managerInList = employees.find(emp => emp.id === user.id);
        
        if (managerInList) {
            console.log('⚠️ Gerente ainda aparece na lista:', managerInList.name);
            return false;
        } else {
            console.log('✅ Gerente não aparece na lista (correto)');
            return true;
        }
        
    } catch (error) {
        console.error('❌ Erro no teste:', error);
        return false;
    }
}

// Teste 2: Verificar se as mensagens têm o design correto
function testMessageDesign() {
    console.log('💬 Testando design das mensagens...');
    
    try {
        // Verificar se a função displayChatMessages existe
        if (typeof displayChatMessages !== 'function') {
            console.error('❌ Função displayChatMessages não existe');
            return false;
        }
        
        // Criar mensagens de teste
        const testMessages = [
            {
                id: '1',
                message: 'Mensagem de teste 1',
                sender_id: 'user1',
                sender_name: 'João',
                created_at: new Date().toISOString()
            },
            {
                id: '2',
                message: 'Mensagem de teste 2',
                sender_id: window.currentUser?.id || 'current',
                sender_name: 'Gerente',
                created_at: new Date().toISOString()
            }
        ];
        
        // Testar exibição das mensagens
        displayChatMessages(testMessages);
        
        // Verificar se as mensagens foram renderizadas
        const chatContainer = document.getElementById('chatMessages');
        if (!chatContainer) {
            console.error('❌ Container de mensagens não encontrado');
            return false;
        }
        
        const messageElements = chatContainer.querySelectorAll('.flex.justify-end, .flex.justify-start');
        console.log('📨 Mensagens renderizadas:', messageElements.length);
        
        if (messageElements.length === 2) {
            console.log('✅ Design das mensagens funcionando');
            return true;
        } else {
            console.log('⚠️ Número incorreto de mensagens renderizadas');
            return false;
        }
        
    } catch (error) {
        console.error('❌ Erro no teste de design:', error);
        return false;
    }
}

// Teste 3: Verificar se o modal do chat pode ser aberto
function testChatModalOpen() {
    console.log('🚪 Testando abertura do modal de chat...');
    
    try {
        // Verificar se a função existe
        if (typeof openChatModal !== 'function') {
            console.error('❌ Função openChatModal não existe');
            return false;
        }
        
        // Tentar abrir o modal
        openChatModal();
        
        // Verificar se o modal está visível
        const modal = document.getElementById('chatModal');
        if (!modal) {
            console.error('❌ Modal de chat não encontrado');
            return false;
        }
        
        if (modal.classList.contains('hidden')) {
            console.log('⚠️ Modal ainda está oculto');
            return false;
        } else {
            console.log('✅ Modal de chat aberto com sucesso');
            return true;
        }
        
    } catch (error) {
        console.error('❌ Erro ao abrir modal:', error);
        return false;
    }
}

// Teste 4: Verificar se as funções de chat existem
function testChatFunctions() {
    console.log('🔧 Testando funções do chat...');
    
    const requiredFunctions = [
        'loadEmployees',
        'displayEmployees',
        'selectEmployee',
        'loadChatMessages',
        'displayChatMessages',
        'sendChatMessageLocal',
        'isEmployeeOnline',
        'formatLastSeen'
    ];
    
    let allExist = true;
    
    requiredFunctions.forEach(funcName => {
        if (typeof window[funcName] === 'function') {
            console.log(`✅ ${funcName} existe`);
        } else {
            console.error(`❌ ${funcName} não existe`);
            allExist = false;
        }
    });
    
    return allExist;
}

// Executar todos os testes
async function runAllTests() {
    console.log('🚀 Executando todos os testes de design...');
    
    const results = {
        managerNotInList: await testManagerNotInList(),
        messageDesign: testMessageDesign(),
        chatModalOpen: testChatModalOpen(),
        chatFunctions: testChatFunctions()
    };
    
    console.log('📋 Resultados dos testes:');
    console.table(results);
    
    const allPassed = Object.values(results).every(result => result === true);
    
    if (allPassed) {
        console.log('🎉 Todos os testes passaram! Design do chat corrigido.');
    } else {
        console.log('⚠️ Alguns testes falharam. Verifique os erros acima.');
    }
    
    return results;
}

// Exportar funções para uso manual
window.chatDesignTest = {
    runAllTests,
    testManagerNotInList,
    testMessageDesign,
    testChatModalOpen,
    testChatFunctions
};

console.log('✅ Script de teste de design carregado! Use chatDesignTest.runAllTests() para executar todos os testes.');
