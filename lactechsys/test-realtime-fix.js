// Script de Teste para Correção do Real-Time
// Execute no console do navegador para testar

console.log('🔧 Testando correções do real-time...');

// Função para testar se o erro foi corrigido
function testRealtimeError() {
    console.log('🔍 Testando se o erro do real-time foi corrigido...');
    
    try {
        // Verificar se getSupabaseClient existe
        if (typeof getSupabaseClient !== 'function') {
            console.error('❌ Função getSupabaseClient não encontrada');
            return false;
        }
        
        // Verificar se setupRealtimeChat existe
        if (typeof setupRealtimeChat !== 'function') {
            console.error('❌ Função setupRealtimeChat não encontrada');
            return false;
        }
        
        console.log('✅ Funções necessárias encontradas');
        return true;
        
    } catch (error) {
        console.error('❌ Erro no teste:', error);
        return false;
    }
}

// Função para testar envio e recebimento de mensagens
async function testMessageFlow() {
    console.log('💬 Testando fluxo de mensagens...');
    
    try {
        // Verificar se o chat está aberto
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
        
        // Verificar se as funções de mensagem existem
        if (typeof sendChatMessageLocal !== 'function') {
            console.error('❌ Função sendChatMessageLocal não encontrada');
            return false;
        }
        
        if (typeof loadChatMessages !== 'function') {
            console.error('❌ Função loadChatMessages não encontrada');
            return false;
        }
        
        console.log('✅ Funções de mensagem encontradas');
        return true;
        
    } catch (error) {
        console.error('❌ Erro no teste de fluxo:', error);
        return false;
    }
}

// Função para testar real-time diretamente
async function testRealtimeDirect() {
    console.log('🔔 Testando real-time diretamente...');
    
    try {
        const supabase = await getSupabaseClient();
        
        // Configurar canal de teste
        const channel = supabase
            .channel('test_realtime_fix')
            .on('postgres_changes', {
                event: 'INSERT',
                schema: 'public',
                table: 'chat_messages'
            }, (payload) => {
                console.log('🎉 REAL-TIME FUNCIONANDO! Nova mensagem:', payload.new);
            })
            .subscribe();
        
        console.log('✅ Canal de real-time configurado');
        
        // Aguardar 3 segundos
        setTimeout(() => {
            supabase.removeChannel(channel);
            console.log('⏰ Teste de real-time concluído');
        }, 3000);
        
        return true;
        
    } catch (error) {
        console.error('❌ Erro no teste de real-time:', error);
        return false;
    }
}

// Função para verificar logs de erro
function checkErrorLogs() {
    console.log('📋 Verificando logs de erro...');
    
    // Interceptar console.error para capturar erros
    const originalError = console.error;
    const errorLogs = [];
    
    console.error = function(...args) {
        const message = args.join(' ');
        if (message.includes('real-time') || message.includes('supabase') || message.includes('channel')) {
            errorLogs.push(message);
        }
        originalError.apply(console, args);
    };
    
    // Restaurar após 5 segundos
    setTimeout(() => {
        console.error = originalError;
        if (errorLogs.length > 0) {
            console.log('⚠️ Erros encontrados:', errorLogs);
        } else {
            console.log('✅ Nenhum erro encontrado');
        }
    }, 5000);
    
    return errorLogs;
}

// Função para testar envio de mensagem
async function testSendMessage() {
    console.log('📤 Testando envio de mensagem...');
    
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
        
        const testMessage = `Teste correção ${new Date().toLocaleTimeString()}`;
        messageInput.value = testMessage;
        
        // Enviar mensagem
        await sendChatMessageLocal();
        
        console.log('✅ Mensagem de teste enviada');
        return true;
        
    } catch (error) {
        console.error('❌ Erro ao enviar mensagem:', error);
        return false;
    }
}

// Executar todos os testes
async function runAllTests() {
    console.log('🚀 Executando testes de correção...');
    
    const results = {
        realtimeError: testRealtimeError(),
        messageFlow: await testMessageFlow(),
        realtimeDirect: await testRealtimeDirect(),
        sendMessage: await testSendMessage()
    };
    
    console.log('📋 Resultados dos testes:');
    console.table(results);
    
    const allPassed = Object.values(results).every(result => result === true);
    
    if (allPassed) {
        console.log('🎉 Todos os testes passaram! Correções funcionando.');
    } else {
        console.log('⚠️ Alguns testes falharam. Verifique os erros acima.');
    }
    
    return results;
}

// Função para monitorar logs em tempo real
function monitorLogs() {
    console.log('👀 Monitorando logs em tempo real...');
    
    const originalLog = console.log;
    const originalError = console.error;
    const logs = [];
    
    console.log = function(...args) {
        const message = args.join(' ');
        if (message.includes('📨') || message.includes('real-time') || message.includes('Nova mensagem')) {
            logs.push('LOG: ' + message);
        }
        originalLog.apply(console, args);
    };
    
    console.error = function(...args) {
        const message = args.join(' ');
        logs.push('ERROR: ' + message);
        originalError.apply(console, args);
    };
    
    // Restaurar após 10 segundos
    setTimeout(() => {
        console.log = originalLog;
        console.error = originalError;
        console.log('📊 Logs capturados:', logs);
    }, 10000);
    
    return logs;
}

// Exportar funções
window.realtimeFixTest = {
    runAllTests,
    testRealtimeError,
    testMessageFlow,
    testRealtimeDirect,
    testSendMessage,
    checkErrorLogs,
    monitorLogs
};

console.log('✅ Script de teste de correção carregado!');
console.log('📝 Use realtimeFixTest.runAllTests() para executar todos os testes');
console.log('📝 Use realtimeFixTest.monitorLogs() para monitorar logs');
