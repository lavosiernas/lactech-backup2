// Script de Teste para o Sistema de Chat
// Execute este script no console do navegador para testar

console.log('🧪 Iniciando testes do sistema de chat...');

// Teste 1: Verificar se os clientes estão configurados
async function testDatabaseClients() {
    console.log('📊 Testando clientes de banco...');
    
    try {
        // Testar cliente do sistema
        const systemClient = await getSupabaseClient();
        console.log('✅ Cliente do sistema:', systemClient ? 'OK' : 'ERRO');
        
        // Testar cliente do chat
        const chatClient = await getChatClient();
        console.log('✅ Cliente do chat:', chatClient ? 'OK' : 'ERRO');
        
        return true;
    } catch (error) {
        console.error('❌ Erro nos clientes:', error);
        return false;
    }
}

// Teste 2: Verificar autenticação
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

// Teste 3: Verificar sincronização de usuários
async function testUserSync() {
    console.log('👥 Testando sincronização de usuários...');
    
    try {
        const supabase = await getSupabaseClient();
        const { data: { user } } = await supabase.auth.getUser();
        
        if (!user) {
            console.error('❌ Usuário não autenticado');
            return false;
        }
        
        // Buscar farm_id
        const { data: userData } = await supabase
            .from('users')
            .select('farm_id')
            .eq('id', user.id)
            .single();
        
        if (!userData?.farm_id) {
            console.error('❌ Farm ID não encontrado');
            return false;
        }
        
        // Testar sincronização
        const employees = await getFarmUsers(userData.farm_id);
        console.log('✅ Usuários sincronizados:', employees.length);
        
        return true;
    } catch (error) {
        console.error('❌ Erro na sincronização:', error);
        return false;
    }
}

// Teste 4: Verificar envio de mensagem
async function testMessageSending() {
    console.log('💬 Testando envio de mensagem...');
    
    try {
        const supabase = await getSupabaseClient();
        const { data: { user } } = await supabase.auth.getUser();
        
        if (!user) {
            console.error('❌ Usuário não autenticado');
            return false;
        }
        
        // Buscar farm_id
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

// Teste 5: Verificar busca de mensagens
async function testMessageRetrieval() {
    console.log('📨 Testando busca de mensagens...');
    
    try {
        const supabase = await getSupabaseClient();
        const { data: { user } } = await supabase.auth.getUser();
        
        if (!user) {
            console.error('❌ Usuário não autenticado');
            return false;
        }
        
        // Buscar farm_id
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
        const messages = await getChatMessages(userData.farm_id, user.id);
        console.log('✅ Mensagens encontradas:', messages.length);
        
        return true;
    } catch (error) {
        console.error('❌ Erro na busca de mensagens:', error);
        return false;
    }
}

// Executar todos os testes
async function runAllTests() {
    console.log('🚀 Executando todos os testes...');
    
    const results = {
        databaseClients: await testDatabaseClients(),
        authentication: await testAuthentication(),
        userSync: await testUserSync(),
        messageSending: await testMessageSending(),
        messageRetrieval: await testMessageRetrieval()
    };
    
    console.log('📋 Resultados dos testes:');
    console.table(results);
    
    const allPassed = Object.values(results).every(result => result === true);
    
    if (allPassed) {
        console.log('🎉 Todos os testes passaram! Sistema de chat funcionando corretamente.');
    } else {
        console.log('⚠️ Alguns testes falharam. Verifique os erros acima.');
    }
    
    return results;
}

// Função para testar o modal do chat
function testChatModal() {
    console.log('🖥️ Testando modal do chat...');
    
    // Verificar se o modal existe
    const modal = document.getElementById('chatModal');
    if (!modal) {
        console.error('❌ Modal do chat não encontrado');
        return false;
    }
    
    console.log('✅ Modal do chat encontrado');
    
    // Verificar se as funções existem
    const functions = [
        'openChatModal',
        'closeChatModal',
        'loadEmployees',
        'sendChatMessageLocal',
        'displayChatMessages'
    ];
    
    functions.forEach(funcName => {
        if (typeof window[funcName] === 'function') {
            console.log(`✅ Função ${funcName} encontrada`);
        } else {
            console.error(`❌ Função ${funcName} não encontrada`);
        }
    });
    
    return true;
}

// Exportar funções para uso manual
window.testChatSystem = {
    runAllTests,
    testDatabaseClients,
    testAuthentication,
    testUserSync,
    testMessageSending,
    testMessageRetrieval,
    testChatModal
};

console.log('✅ Script de teste carregado! Use testChatSystem.runAllTests() para executar todos os testes.');
