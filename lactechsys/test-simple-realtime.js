// Script de Teste Simples para Real-Time
// Execute no console do navegador para testar

console.log('🔔 Teste simples de real-time...');

// Função para testar real-time diretamente
async function testRealtimeDirect() {
    console.log('🧪 Testando real-time diretamente...');
    
    try {
        const supabase = await getSupabaseClient();
        
        // Configurar canal de real-time
        const channel = supabase
            .channel('test_chat_realtime')
            .on('postgres_changes', {
                event: 'INSERT',
                schema: 'public',
                table: 'chat_messages'
            }, (payload) => {
                console.log('🎉 REAL-TIME FUNCIONANDO! Nova mensagem:', payload.new);
            })
            .subscribe();
        
        console.log('✅ Canal de real-time configurado');
        
        // Aguardar 5 segundos para testar
        setTimeout(() => {
            console.log('⏰ Teste de real-time concluído');
            supabase.removeChannel(channel);
        }, 5000);
        
        return channel;
        
    } catch (error) {
        console.error('❌ Erro no teste de real-time:', error);
        return null;
    }
}

// Função para testar se o chat está funcionando
async function testChatFlow() {
    console.log('💬 Testando fluxo do chat...');
    
    try {
        // Verificar se o modal está aberto
        const modal = document.getElementById('chatModal');
        if (!modal || modal.classList.contains('hidden')) {
            console.log('⚠️ Modal de chat não está aberto');
            return false;
        }
        
        // Verificar se há funcionário selecionado
        if (!window.selectedEmployee) {
            console.log('⚠️ Nenhum funcionário selecionado');
            return false;
        }
        
        console.log('✅ Chat configurado corretamente');
        return true;
        
    } catch (error) {
        console.error('❌ Erro no teste do chat:', error);
        return false;
    }
}

// Função para enviar mensagem de teste
async function sendTestMessage() {
    console.log('📤 Enviando mensagem de teste...');
    
    try {
        if (!window.selectedEmployee) {
            console.log('⚠️ Nenhum funcionário selecionado');
            return false;
        }
        
        const messageInput = document.getElementById('chatMessageInput');
        if (!messageInput) {
            console.log('⚠️ Input de mensagem não encontrado');
            return false;
        }
        
        const testMessage = `Teste real-time ${new Date().toLocaleTimeString()}`;
        messageInput.value = testMessage;
        
        // Enviar mensagem
        await sendChatMessageLocal();
        
        console.log('✅ Mensagem de teste enviada');
        return true;
        
    } catch (error) {
        console.error('❌ Erro ao enviar mensagem de teste:', error);
        return false;
    }
}

// Função para verificar logs
function checkLogs() {
    console.log('📋 Verificando logs...');
    
    // Verificar se há logs de real-time
    const logs = [];
    const originalLog = console.log;
    
    console.log = function(...args) {
        const message = args.join(' ');
        if (message.includes('real-time') || message.includes('📨') || message.includes('Nova mensagem')) {
            logs.push(message);
        }
        originalLog.apply(console, args);
    };
    
    // Restaurar após 3 segundos
    setTimeout(() => {
        console.log = originalLog;
        console.log('📊 Logs capturados:', logs);
    }, 3000);
    
    return logs;
}

// Executar teste completo
async function runTest() {
    console.log('🚀 Executando teste completo...');
    
    const results = {
        chatFlow: await testChatFlow(),
        realtimeDirect: await testRealtimeDirect(),
        testMessage: await sendTestMessage()
    };
    
    console.log('📋 Resultados:');
    console.table(results);
    
    return results;
}

// Exportar funções
window.simpleRealtimeTest = {
    runTest,
    testRealtimeDirect,
    testChatFlow,
    sendTestMessage,
    checkLogs
};

console.log('✅ Script de teste simples carregado!');
console.log('📝 Use simpleRealtimeTest.runTest() para executar o teste completo');
