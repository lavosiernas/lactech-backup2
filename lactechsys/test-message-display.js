// Script de Teste para Exibição de Mensagens
// Execute no console do navegador para testar

console.log('💬 Testando exibição automática de mensagens...');

// Função para testar se as mensagens aparecem automaticamente
async function testMessageDisplay() {
    console.log('🔍 Testando exibição automática de mensagens...');
    
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
        
        // Verificar se as funções existem
        if (typeof loadChatMessages !== 'function') {
            console.error('❌ Função loadChatMessages não encontrada');
            return false;
        }
        
        if (typeof displayChatMessages !== 'function') {
            console.error('❌ Função displayChatMessages não encontrada');
            return false;
        }
        
        console.log('✅ Funções de mensagem encontradas');
        return true;
        
    } catch (error) {
        console.error('❌ Erro no teste de exibição:', error);
        return false;
    }
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
        
        // Contar mensagens antes do envio
        const chatContainer = document.getElementById('chatMessages');
        const initialMessageCount = chatContainer ? chatContainer.children.length : 0;
        console.log('📊 Mensagens antes do envio:', initialMessageCount);
        
        const testMessage = `Teste auto-display ${new Date().toLocaleTimeString()}`;
        messageInput.value = testMessage;
        
        // Enviar mensagem
        await sendChatMessageLocal();
        
        // Aguardar um pouco para o real-time processar
        await new Promise(resolve => setTimeout(resolve, 2000));
        
        // Contar mensagens após o envio
        const finalMessageCount = chatContainer ? chatContainer.children.length : 0;
        console.log('📊 Mensagens após o envio:', finalMessageCount);
        
        if (finalMessageCount > initialMessageCount) {
            console.log('✅ Mensagem apareceu automaticamente!');
            return true;
        } else {
            console.log('⚠️ Mensagem não apareceu automaticamente');
            return false;
        }
        
    } catch (error) {
        console.error('❌ Erro ao testar envio:', error);
        return false;
    }
}

// Função para testar recebimento de mensagem
async function testReceiveMessage() {
    console.log('📥 Testando recebimento de mensagem...');
    
    try {
        if (!window.selectedEmployee) {
            console.log('⚠️ Nenhum funcionário selecionado');
            return false;
        }
        
        const chatContainer = document.getElementById('chatMessages');
        if (!chatContainer) {
            console.log('⚠️ Container de mensagens não encontrado');
            return false;
        }
        
        const initialMessageCount = chatContainer.children.length;
        console.log('📊 Mensagens iniciais:', initialMessageCount);
        
        // Aguardar um pouco para ver se há mensagens novas
        await new Promise(resolve => setTimeout(resolve, 3000));
        
        const finalMessageCount = chatContainer.children.length;
        console.log('📊 Mensagens finais:', finalMessageCount);
        
        if (finalMessageCount > initialMessageCount) {
            console.log('✅ Novas mensagens recebidas automaticamente!');
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

// Função para testar real-time
async function testRealtimeFlow() {
    console.log('🔔 Testando fluxo de real-time...');
    
    try {
        // Verificar se o real-time está configurado
        if (typeof chatRealtimeChannel === 'undefined' || !chatRealtimeChannel) {
            console.log('⚠️ Canal de real-time não configurado');
            return false;
        }
        
        console.log('✅ Canal de real-time configurado');
        
        // Verificar se as funções de real-time existem
        if (typeof setupChatRealtime !== 'function') {
            console.error('❌ Função setupChatRealtime não encontrada');
            return false;
        }
        
        if (typeof setupRealtimeChat !== 'function') {
            console.error('❌ Função setupRealtimeChat não encontrada');
            return false;
        }
        
        console.log('✅ Funções de real-time encontradas');
        return true;
        
    } catch (error) {
        console.error('❌ Erro no teste de real-time:', error);
        return false;
    }
}

// Função para monitorar logs de mensagens
function monitorMessageLogs() {
    console.log('👀 Monitorando logs de mensagens...');
    
    const originalLog = console.log;
    const messageLogs = [];
    
    console.log = function(...args) {
        const message = args.join(' ');
        if (message.includes('📨') || message.includes('Nova mensagem') || message.includes('Buscando mensagens') || message.includes('Mensagens encontradas')) {
            messageLogs.push(message);
        }
        originalLog.apply(console, args);
    };
    
    // Restaurar após 10 segundos
    setTimeout(() => {
        console.log = originalLog;
        console.log('📊 Logs de mensagem capturados:', messageLogs);
    }, 10000);
    
    return messageLogs;
}

// Função para testar fluxo completo
async function testCompleteFlow() {
    console.log('🚀 Testando fluxo completo de mensagens...');
    
    const results = {
        messageDisplay: await testMessageDisplay(),
        realtimeFlow: await testRealtimeFlow(),
        sendMessage: await testSendMessage(),
        receiveMessage: await testReceiveMessage()
    };
    
    console.log('📋 Resultados do fluxo completo:');
    console.table(results);
    
    const allPassed = Object.values(results).every(result => result === true);
    
    if (allPassed) {
        console.log('🎉 Fluxo completo funcionando! Mensagens aparecem automaticamente.');
    } else {
        console.log('⚠️ Alguns testes falharam. Verifique os erros acima.');
    }
    
    return results;
}

// Função para testar manualmente
function testManual() {
    console.log('🧪 Teste manual de exibição de mensagens...');
    console.log('1. Abra o chat em duas abas diferentes');
    console.log('2. Selecione um funcionário em uma aba');
    console.log('3. Envie uma mensagem');
    console.log('4. Verifique se aparece automaticamente na mesma aba');
    console.log('5. Verifique se aparece na outra aba');
    console.log('6. Execute monitorMessageLogs() para ver logs');
    
    return monitorMessageLogs();
}

// Exportar funções
window.messageDisplayTest = {
    testCompleteFlow,
    testMessageDisplay,
    testSendMessage,
    testReceiveMessage,
    testRealtimeFlow,
    monitorMessageLogs,
    testManual
};

console.log('✅ Script de teste de exibição de mensagens carregado!');
console.log('📝 Use messageDisplayTest.testCompleteFlow() para executar todos os testes');
console.log('📝 Use messageDisplayTest.testManual() para teste manual');
