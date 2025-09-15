// Script de Teste Final para o Chat
// Execute no console do navegador para testar

console.log('🧪 Testando chat final...');

// Teste 1: Verificar se a tabela chat_messages existe
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
        console.log('💡 Execute o SQL do arquivo create-chat-table.sql no seu Supabase');
        return false;
    }
}

// Teste 2: Verificar colunas is_online e last_login
async function testOnlineColumns() {
    console.log('🟢 Testando colunas de status online...');
    
    try {
        const supabase = await getSupabaseClient();
        const { data: { user } } = await supabase.auth.getUser();
        
        if (!user) {
            console.error('❌ Usuário não autenticado');
            return false;
        }
        
        // Tentar atualizar status online
        const { error } = await supabase
            .from('users')
            .update({ 
                is_online: true,
                last_login: new Date().toISOString()
            })
            .eq('id', user.id);
        
        if (error) {
            console.error('❌ Erro ao atualizar status online:', error);
            console.log('💡 As colunas is_online e last_login podem não existir');
            return false;
        }
        
        console.log('✅ Colunas de status online funcionando');
        return true;
    } catch (error) {
        console.error('❌ Erro no teste de colunas online:', error);
        return false;
    }
}

// Teste 3: Verificar busca de usuários com status online
async function testUsersWithOnlineStatus() {
    console.log('👥 Testando busca de usuários com status online...');
    
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
        
        // Buscar usuários da fazenda
        const employees = await getFarmUsers(userData.farm_id);
        console.log('✅ Usuários encontrados:', employees.length);
        
        // Verificar se têm status online
        employees.forEach((emp, index) => {
            const isOnline = emp.is_online;
            const lastLogin = emp.last_login;
            console.log(`👤 ${index + 1}. ${emp.name} (${emp.role}) - Online: ${isOnline} - Último login: ${lastLogin}`);
        });
        
        return true;
    } catch (error) {
        console.error('❌ Erro na busca de usuários:', error);
        return false;
    }
}

// Teste 4: Testar envio de mensagem
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
        console.log('✅ Mensagens encontradas:', messages.length);
        
        return true;
    } catch (error) {
        console.error('❌ Erro no envio de mensagem:', error);
        return false;
    }
}

// Teste 5: Verificar gerenciador de status online
function testOnlineStatusManager() {
    console.log('🔄 Testando gerenciador de status online...');
    
    if (typeof window.startOnlineStatus === 'function') {
        console.log('✅ Função startOnlineStatus disponível');
    } else {
        console.error('❌ Função startOnlineStatus não disponível');
        return false;
    }
    
    if (typeof window.stopOnlineStatus === 'function') {
        console.log('✅ Função stopOnlineStatus disponível');
    } else {
        console.error('❌ Função stopOnlineStatus não disponível');
        return false;
    }
    
    if (typeof window.updateOnlineStatus === 'function') {
        console.log('✅ Função updateOnlineStatus disponível');
    } else {
        console.error('❌ Função updateOnlineStatus não disponível');
        return false;
    }
    
    return true;
}

// Executar todos os testes
async function runAllTests() {
    console.log('🚀 Executando todos os testes finais...');
    
    const results = {
        chatMessagesTable: await testChatMessagesTable(),
        onlineColumns: await testOnlineColumns(),
        usersWithOnlineStatus: await testUsersWithOnlineStatus(),
        sendMessage: await testSendMessage(),
        onlineStatusManager: testOnlineStatusManager()
    };
    
    console.log('📋 Resultados dos testes:');
    console.table(results);
    
    const allPassed = Object.values(results).every(result => result === true);
    
    if (allPassed) {
        console.log('🎉 Todos os testes passaram! Chat funcionando perfeitamente.');
    } else {
        console.log('⚠️ Alguns testes falharam. Verifique os erros acima.');
        
        if (!results.chatMessagesTable) {
            console.log('💡 Para corrigir, execute o SQL do arquivo create-chat-table.sql no seu Supabase');
        }
        
        if (!results.onlineColumns) {
            console.log('💡 Para corrigir, execute o SQL do arquivo create-chat-table.sql no seu Supabase');
        }
    }
    
    return results;
}

// Exportar funções para uso manual
window.finalChatTest = {
    runAllTests,
    testChatMessagesTable,
    testOnlineColumns,
    testUsersWithOnlineStatus,
    testSendMessage,
    testOnlineStatusManager
};

console.log('✅ Script de teste final carregado! Use finalChatTest.runAllTests() para executar todos os testes.');
