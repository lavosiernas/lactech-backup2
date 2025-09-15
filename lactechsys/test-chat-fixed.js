// Script de Teste para o Chat Corrigido
// Execute no console do navegador para testar

console.log('🧪 Testando chat corrigido...');

// Teste 1: Verificar se as funções estão disponíveis
function testChatFunctions() {
    console.log('🔧 Testando funções do chat...');
    
    const functions = [
        'getFarmUsers',
        'sendChatMessage',
        'getChatMessages',
        'setupRealtimeChat',
        'disconnectAllRealtime'
    ];
    
    functions.forEach(funcName => {
        if (typeof window[funcName] === 'function') {
            console.log(`✅ ${funcName} disponível`);
        } else {
            console.error(`❌ ${funcName} não disponível`);
        }
    });
}

// Teste 2: Verificar clientes Supabase
async function testSupabaseClients() {
    console.log('🔌 Testando clientes Supabase...');
    
    try {
        // Testar cliente do sistema
        const systemClient = await getSupabaseClient();
        if (systemClient) {
            console.log('✅ Cliente do sistema funcionando');
        } else {
            console.error('❌ Cliente do sistema não funcionando');
        }
        
        return true;
    } catch (error) {
        console.error('❌ Erro nos clientes:', error);
        return false;
    }
}

// Teste 3: Verificar autenticação
async function testAuthentication() {
    console.log('🔐 Testando autenticação...');
    
    try {
        const supabase = await getSupabaseClient();
        const { data: { user }, error } = await supabase.auth.getUser();
        
        if (error) {
            console.error('❌ Erro de autenticação:', error);
            return false;
        }
        
        if (!user) {
            console.error('❌ Usuário não autenticado');
            return false;
        }
        
        console.log('✅ Usuário autenticado:', user.email);
        return true;
    } catch (error) {
        console.error('❌ Erro no teste de autenticação:', error);
        return false;
    }
}

// Teste 4: Verificar busca de usuários
async function testGetFarmUsers() {
    console.log('👥 Testando busca de usuários...');
    
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
        
        console.log('🔄 Buscando usuários da fazenda...');
        const employees = await getFarmUsers(userData.farm_id);
        console.log('✅ Usuários encontrados:', employees.length);
        
        employees.forEach((emp, index) => {
            console.log(`👤 ${index + 1}. ${emp.name} (${emp.role})`);
        });
        
        return true;
    } catch (error) {
        console.error('❌ Erro na busca de usuários:', error);
        return false;
    }
}

// Teste 5: Verificar se a tabela chat_messages existe
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
        console.log('💡 A tabela chat_messages pode não existir. Execute o SQL do CHAT_DATABASE_SETUP.md');
        return false;
    }
}

// Teste 6: Testar envio de mensagem
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
        
        return true;
    } catch (error) {
        console.error('❌ Erro no envio de mensagem:', error);
        return false;
    }
}

// Executar todos os testes
async function runAllTests() {
    console.log('🚀 Executando todos os testes...');
    
    const results = {
        chatFunctions: testChatFunctions(),
        supabaseClients: await testSupabaseClients(),
        authentication: await testAuthentication(),
        getFarmUsers: await testGetFarmUsers(),
        chatMessagesTable: await testChatMessagesTable(),
        sendMessage: await testSendMessage()
    };
    
    console.log('📋 Resultados dos testes:');
    console.table(results);
    
    const allPassed = Object.values(results).every(result => result === true);
    
    if (allPassed) {
        console.log('🎉 Todos os testes passaram! Chat funcionando.');
    } else {
        console.log('⚠️ Alguns testes falharam. Verifique os erros acima.');
        
        if (!results.chatMessagesTable) {
            console.log('💡 Para corrigir o chat, execute o SQL do arquivo CHAT_DATABASE_SETUP.md no seu Supabase');
        }
    }
    
    return results;
}

// Exportar funções para uso manual
window.chatTest = {
    runAllTests,
    testChatFunctions,
    testSupabaseClients,
    testAuthentication,
    testGetFarmUsers,
    testChatMessagesTable,
    testSendMessage
};

console.log('✅ Script de teste carregado! Use chatTest.runAllTests() para executar todos os testes.');
