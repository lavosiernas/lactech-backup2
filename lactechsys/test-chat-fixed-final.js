// Script de Teste Final para o Chat Corrigido
// Execute no console do navegador para testar

console.log('🧪 Testando chat corrigido (versão final)...');

// Teste 1: Verificar se a tabela chat_messages existe e funciona
async function testChatMessagesTable() {
    console.log('📨 Testando tabela chat_messages...');
    
    try {
        const supabase = await getSupabaseClient();
        const { data: { user } } = await supabase.auth.getUser();
        
        if (!user) {
            console.error('❌ Usuário não autenticado');
            return false;
        }
        
        const { data: userData } = await supabase
            .from('users')
            .select('farm_id')
            .eq('id', user.id)
            .single();
        
        if (!userData?.farm_id) {
            console.error('❌ Farm ID não encontrado');
            return false;
        }
        
        // Tentar buscar mensagens (mesmo que vazio)
        const messages = await getChatMessages(userData.farm_id);
        console.log('✅ Tabela chat_messages acessível, mensagens:', messages.length);
        
        return true;
    } catch (error) {
        console.error('❌ Erro ao acessar tabela chat_messages:', error);
        return false;
    }
}

// Teste 2: Verificar se as mensagens são buscadas corretamente
async function testGetChatMessages() {
    console.log('📨 Testando busca de mensagens...');
    
    try {
        const supabase = await getSupabaseClient();
        const { data: { user } } = await supabase.auth.getUser();
        
        if (!user) {
            console.error('❌ Usuário não autenticado');
            return false;
        }
        
        const { data: userData } = await supabase
            .from('users')
            .select('farm_id')
            .eq('id', user.id)
            .single();
        
        if (!userData?.farm_id) {
            console.error('❌ Farm ID não encontrado');
            return false;
        }
        
        // Buscar mensagens
        const messages = await getChatMessages(userData.farm_id, user.id, user.id);
        console.log('✅ Mensagens buscadas com sucesso:', messages.length);
        
        // Verificar se as mensagens têm as propriedades corretas
        if (messages.length > 0) {
            const firstMessage = messages[0];
            console.log('📋 Propriedades da primeira mensagem:', {
                id: firstMessage.id,
                message: firstMessage.message,
                sender_id: firstMessage.sender_id,
                sender_name: firstMessage.sender_name,
                sender_role: firstMessage.sender_role,
                created_at: firstMessage.created_at
            });
        }
        
        return true;
    } catch (error) {
        console.error('❌ Erro na busca de mensagens:', error);
        return false;
    }
}

// Teste 3: Verificar se o envio de mensagens funciona
async function testSendMessage() {
    console.log('💬 Testando envio de mensagem...');
    
    try {
        const supabase = await getSupabaseClient();
        const { data: { user } } = await supabase.auth.getUser();
        
        if (!user) {
            console.error('❌ Usuário não autenticado');
            return false;
        }
        
        const { data: userData } = await supabase
            .from('users')
            .select('farm_id')
            .eq('id', user.id)
            .single();
        
        if (!userData?.farm_id) {
            console.error('❌ Farm ID não encontrado');
            return false;
        }
        
        // Buscar outro usuário para testar
        const employees = await getFarmUsers(userData.farm_id);
        const otherUser = employees.find(emp => emp.id !== user.id);
        
        if (!otherUser) {
            console.log('⚠️ Nenhum outro usuário encontrado para teste');
            return true;
        }
        
        // Testar envio de mensagem
        const testMessage = {
            farm_id: userData.farm_id,
            sender_id: user.id,
            receiver_id: otherUser.id,
            message: 'Mensagem de teste - ' + new Date().toISOString()
        };
        
        await sendChatMessage(testMessage);
        console.log('✅ Mensagem de teste enviada');
        
        // Verificar se a mensagem foi salva
        const messages = await getChatMessages(userData.farm_id, user.id, otherUser.id);
        console.log('✅ Mensagens encontradas após envio:', messages.length);
        
        return true;
    } catch (error) {
        console.error('❌ Erro no envio de mensagem:', error);
        return false;
    }
}

// Teste 4: Verificar se as funções do chat existem
function testChatFunctions() {
    console.log('🔧 Testando funções do chat...');
    
    const functions = [
        'getFarmUsers',
        'sendChatMessage',
        'getChatMessages',
        'displayChatMessages',
        'loadChatMessages',
        'sendChatMessageLocal',
        'selectEmployee',
        'isEmployeeOnline',
        'formatLastSeen'
    ];
    
    let allExist = true;
    
    functions.forEach(funcName => {
        if (typeof window[funcName] === 'function') {
            console.log(`✅ ${funcName} existe`);
        } else {
            console.error(`❌ ${funcName} não existe`);
            allExist = false;
        }
    });
    
    return allExist;
}

// Teste 5: Verificar se o modal do chat pode ser aberto
function testChatModal() {
    console.log('🚪 Testando modal do chat...');
    
    try {
        // Verificar se a função existe
        if (typeof openChatModal !== 'function') {
            console.error('❌ Função openChatModal não existe');
            return false;
        }
        
        // Tentar abrir o modal
        openChatModal();
        console.log('✅ Modal de chat aberto com sucesso');
        
        // Verificar se o modal está visível
        const modal = document.getElementById('chatModal');
        if (modal && !modal.classList.contains('hidden')) {
            console.log('✅ Modal está visível');
        } else {
            console.log('⚠️ Modal pode não estar visível');
        }
        
        return true;
    } catch (error) {
        console.error('❌ Erro ao abrir modal de chat:', error);
        return false;
    }
}

// Executar todos os testes
async function runAllTests() {
    console.log('🚀 Executando todos os testes finais...');
    
    const results = {
        chatMessagesTable: await testChatMessagesTable(),
        getChatMessages: await testGetChatMessages(),
        sendMessage: await testSendMessage(),
        chatFunctions: testChatFunctions(),
        chatModal: testChatModal()
    };
    
    console.log('📋 Resultados dos testes:');
    console.table(results);
    
    const allPassed = Object.values(results).every(result => result === true);
    
    if (allPassed) {
        console.log('🎉 Todos os testes passaram! Chat funcionando perfeitamente.');
    } else {
        console.log('⚠️ Alguns testes falharam. Verifique os erros acima.');
    }
    
    return results;
}

// Exportar funções para uso manual
window.finalChatTest = {
    runAllTests,
    testChatMessagesTable,
    testGetChatMessages,
    testSendMessage,
    testChatFunctions,
    testChatModal
};

console.log('✅ Script de teste final carregado! Use finalChatTest.runAllTests() para executar todos os testes.');
