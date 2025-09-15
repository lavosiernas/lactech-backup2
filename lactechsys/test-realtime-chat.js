// Script de Teste para Real-Time do Chat
// Execute no console do navegador para testar

console.log('🔔 Testando real-time do chat...');

// Função para testar se o real-time está configurado
function testRealtimeSetup() {
    console.log('🔍 Testando configuração do real-time...');
    
    try {
        // Verificar se as funções existem
        if (typeof setupRealtimeChat !== 'function') {
            console.error('❌ Função setupRealtimeChat não encontrada');
            return false;
        }
        
        if (typeof disconnectRealtime !== 'function') {
            console.error('❌ Função disconnectRealtime não encontrada');
            return false;
        }
        
        console.log('✅ Funções de real-time encontradas');
        
        // Verificar se chatRealtimeChannel está definido
        if (typeof chatRealtimeChannel !== 'undefined') {
            console.log('📡 Canal de real-time:', chatRealtimeChannel ? 'Conectado' : 'Desconectado');
            return true;
        } else {
            console.log('⚠️ Variável chatRealtimeChannel não encontrada');
            return false;
        }
        
    } catch (error) {
        console.error('❌ Erro no teste de configuração:', error);
        return false;
    }
}

// Função para testar envio de mensagem
async function testMessageSending() {
    console.log('📤 Testando envio de mensagem...');
    
    try {
        // Verificar se há funcionário selecionado
        if (!window.selectedEmployee) {
            console.log('⚠️ Nenhum funcionário selecionado para teste');
            return false;
        }
        
        console.log('👤 Funcionário selecionado:', window.selectedEmployee.name);
        
        // Verificar se a função de envio existe
        if (typeof sendChatMessageLocal !== 'function') {
            console.error('❌ Função sendChatMessageLocal não encontrada');
            return false;
        }
        
        // Simular envio de mensagem
        const testMessage = `Teste real-time ${new Date().toLocaleTimeString()}`;
        console.log('📝 Mensagem de teste:', testMessage);
        
        // Definir mensagem no input
        const messageInput = document.getElementById('chatMessageInput');
        if (messageInput) {
            messageInput.value = testMessage;
            console.log('✅ Mensagem definida no input');
            
            // Enviar mensagem
            await sendChatMessageLocal();
            console.log('✅ Mensagem enviada');
            
            return true;
        } else {
            console.error('❌ Input de mensagem não encontrado');
            return false;
        }
        
    } catch (error) {
        console.error('❌ Erro no teste de envio:', error);
        return false;
    }
}

// Função para testar recebimento de mensagem
async function testMessageReceiving() {
    console.log('📥 Testando recebimento de mensagem...');
    
    try {
        // Verificar se há funcionário selecionado
        if (!window.selectedEmployee) {
            console.log('⚠️ Nenhum funcionário selecionado para teste');
            return false;
        }
        
        // Verificar se a função de carregar mensagens existe
        if (typeof loadChatMessages !== 'function') {
            console.error('❌ Função loadChatMessages não encontrada');
            return false;
        }
        
        // Carregar mensagens atuais
        const messagesContainer = document.getElementById('chatMessages');
        if (!messagesContainer) {
            console.error('❌ Container de mensagens não encontrado');
            return false;
        }
        
        const initialMessageCount = messagesContainer.children.length;
        console.log('📊 Mensagens iniciais:', initialMessageCount);
        
        // Aguardar um pouco para ver se há atualizações
        await new Promise(resolve => setTimeout(resolve, 2000));
        
        const finalMessageCount = messagesContainer.children.length;
        console.log('📊 Mensagens finais:', finalMessageCount);
        
        if (finalMessageCount > initialMessageCount) {
            console.log('✅ Novas mensagens recebidas via real-time!');
            return true;
        } else {
            console.log('⚠️ Nenhuma nova mensagem recebida');
            return false;
        }
        
    } catch (error) {
        console.error('❌ Erro no teste de recebimento:', error);
        return false;
    }
}

// Função para testar conexão com Supabase
async function testSupabaseConnection() {
    console.log('🔌 Testando conexão com Supabase...');
    
    try {
        const supabase = await getSupabaseClient();
        const { data: { user } } = await supabase.auth.getUser();
        
        if (!user) {
            console.error('❌ Usuário não autenticado');
            return false;
        }
        
        console.log('✅ Usuário autenticado:', user.email);
        
        // Testar query simples
        const { data, error } = await supabase
            .from('users')
            .select('id, name')
            .eq('id', user.id)
            .single();
        
        if (error) {
            console.error('❌ Erro na query:', error);
            return false;
        }
        
        console.log('✅ Conexão com Supabase funcionando');
        return true;
        
    } catch (error) {
        console.error('❌ Erro na conexão:', error);
        return false;
    }
}

// Função para monitorar logs de real-time
function monitorRealtimeLogs() {
    console.log('👀 Monitorando logs de real-time...');
    
    // Interceptar console.log para capturar logs de real-time
    const originalLog = console.log;
    const realtimeLogs = [];
    
    console.log = function(...args) {
        const message = args.join(' ');
        if (message.includes('real-time') || message.includes('Nova mensagem') || message.includes('📨')) {
            realtimeLogs.push(message);
            console.log('🔔 LOG REAL-TIME:', ...args);
        } else {
            originalLog.apply(console, args);
        }
    };
    
    // Restaurar console.log após 10 segundos
    setTimeout(() => {
        console.log = originalLog;
        console.log('📋 Logs de real-time capturados:', realtimeLogs);
    }, 10000);
    
    return realtimeLogs;
}

// Executar todos os testes
async function runAllTests() {
    console.log('🚀 Executando testes de real-time...');
    
    const results = {
        realtimeSetup: testRealtimeSetup(),
        supabaseConnection: await testSupabaseConnection(),
        messageSending: await testMessageSending(),
        messageReceiving: await testMessageReceiving()
    };
    
    console.log('📋 Resultados dos testes:');
    console.table(results);
    
    const allPassed = Object.values(results).every(result => result === true);
    
    if (allPassed) {
        console.log('🎉 Todos os testes passaram! Real-time funcionando.');
    } else {
        console.log('⚠️ Alguns testes falharam. Verifique os erros acima.');
    }
    
    return results;
}

// Função para testar manualmente
function testManual() {
    console.log('🧪 Teste manual de real-time...');
    console.log('1. Abra o chat em duas abas diferentes');
    console.log('2. Envie uma mensagem de uma aba');
    console.log('3. Verifique se aparece na outra aba automaticamente');
    console.log('4. Execute monitorRealtimeLogs() para ver logs');
    
    return monitorRealtimeLogs();
}

// Exportar funções
window.realtimeChatTest = {
    runAllTests,
    testRealtimeSetup,
    testMessageSending,
    testMessageReceiving,
    testSupabaseConnection,
    monitorRealtimeLogs,
    testManual
};

console.log('✅ Script de teste de real-time carregado!');
console.log('📝 Use realtimeChatTest.runAllTests() para executar todos os testes');
console.log('📝 Use realtimeChatTest.testManual() para teste manual');
