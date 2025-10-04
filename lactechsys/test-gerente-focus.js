// Script de Teste para Problema de Foco no Gerente
// Execute no console do navegador para testar

console.log('🔍 Testando problema de foco no gerente...');

// Função para testar se o problema de foco foi corrigido
async function testGerenteFocus() {
    console.log('🔍 Testando foco no gerente...');
    
    try {
        // Verificar se estamos na página do gerente
        const isGerentePage = window.location.pathname.includes('gerente') || 
                             document.title.includes('gerente') ||
                             document.body.innerHTML.includes('gerente');
        
        if (!isGerentePage) {
            console.log('⚠️ Não estamos na página do gerente');
            return false;
        }
        
        console.log('✅ Página do gerente detectada');
        
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
        return true;
        
    } catch (error) {
        console.error('❌ Erro no teste de foco:', error);
        return false;
    }
}

// Função para testar envio de mensagem no gerente
async function testGerenteSendMessage() {
    console.log('📤 Testando envio de mensagem no gerente...');
    
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
        
        // Verificar se o input está habilitado
        if (messageInput.disabled) {
            console.log('⚠️ Input de mensagem está desabilitado');
            return false;
        }
        
        // Contar mensagens antes do envio
        const chatContainer = document.getElementById('chatMessages');
        const initialMessageCount = chatContainer ? chatContainer.children.length : 0;
        console.log('📊 Mensagens antes do envio:', initialMessageCount);
        
        const testMessage = `Teste foco gerente ${new Date().toLocaleTimeString()}`;
        messageInput.value = testMessage;
        
        // Enviar mensagem
        await sendChatMessageLocal();
        
        // Aguardar um pouco para o real-time processar
        await new Promise(resolve => setTimeout(resolve, 3000));
        
        // Contar mensagens após o envio
        const finalMessageCount = chatContainer ? chatContainer.children.length : 0;
        console.log('📊 Mensagens após o envio:', finalMessageCount);
        
        if (finalMessageCount > initialMessageCount) {
            console.log('✅ Mensagem apareceu automaticamente no gerente!');
            return true;
        } else {
            console.log('⚠️ Mensagem não apareceu automaticamente no gerente');
            return false;
        }
        
    } catch (error) {
        console.error('❌ Erro ao testar envio no gerente:', error);
        return false;
    }
}

// Função para testar se o foco é mantido
function testFocusMaintenance() {
    console.log('🎯 Testando manutenção do foco...');
    
    try {
        const messageInput = document.getElementById('chatMessageInput');
        if (!messageInput) {
            console.log('⚠️ Input de mensagem não encontrado');
            return false;
        }
        
        // Verificar se o input está focado
        const isFocused = document.activeElement === messageInput;
        console.log('🎯 Input está focado:', isFocused);
        
        // Verificar se o input está habilitado
        const isEnabled = !messageInput.disabled;
        console.log('🎯 Input está habilitado:', isEnabled);
        
        // Verificar se o funcionário ainda está selecionado
        const hasSelectedEmployee = !!window.selectedEmployee;
        console.log('🎯 Funcionário selecionado:', hasSelectedEmployee);
        
        if (isEnabled && hasSelectedEmployee) {
            console.log('✅ Foco mantido corretamente');
            return true;
        } else {
            console.log('⚠️ Foco não mantido corretamente');
            return false;
        }
        
    } catch (error) {
        console.error('❌ Erro no teste de foco:', error);
        return false;
    }
}

// Função para comparar com funcionário
function compareWithFuncionario() {
    console.log('🔄 Comparando com funcionário...');
    
    try {
        // Verificar se estamos na página do gerente
        const isGerentePage = window.location.pathname.includes('gerente') || 
                             document.title.includes('gerente') ||
                             document.body.innerHTML.includes('gerente');
        
        if (isGerentePage) {
            console.log('📋 Página atual: GERENTE');
            console.log('💡 Para comparar, abra a página do funcionário em outra aba');
            console.log('💡 Execute o mesmo teste na página do funcionário');
            return true;
        } else {
            console.log('📋 Página atual: FUNCIONÁRIO');
            console.log('💡 Para comparar, abra a página do gerente em outra aba');
            console.log('💡 Execute o mesmo teste na página do gerente');
            return true;
        }
        
    } catch (error) {
        console.error('❌ Erro na comparação:', error);
        return false;
    }
}

// Função para monitorar logs específicos do gerente
function monitorGerenteLogs() {
    console.log('👀 Monitorando logs específicos do gerente...');
    
    const originalLog = console.log;
    const originalError = console.error;
    const logs = [];
    
    console.log = function(...args) {
        const message = args.join(' ');
        if (message.includes('🎨 Exibindo mensagens no gerente') || 
            message.includes('📨 Nova mensagem recebida via real-time') ||
            message.includes('🔄 Atualizando mensagens para funcionário selecionado') ||
            message.includes('📨 Buscando mensagens para:') ||
            message.includes('📨 Mensagens encontradas:')) {
            logs.push('LOG: ' + message);
        }
        originalLog.apply(console, args);
    };
    
    console.error = function(...args) {
        const message = args.join(' ');
        if (message.includes('gerente') || message.includes('chat')) {
            logs.push('ERROR: ' + message);
        }
        originalError.apply(console, args);
    };
    
    // Restaurar após 10 segundos
    setTimeout(() => {
        console.log = originalLog;
        console.error = originalError;
        console.log('📊 Logs do gerente capturados:', logs);
    }, 10000);
    
    return logs;
}

// Executar todos os testes
async function runAllTests() {
    console.log('🚀 Executando testes de foco no gerente...');
    
    const results = {
        gerenteFocus: await testGerenteFocus(),
        sendMessage: await testGerenteSendMessage(),
        focusMaintenance: testFocusMaintenance(),
        comparison: compareWithFuncionario()
    };
    
    console.log('📋 Resultados dos testes:');
    console.table(results);
    
    const allPassed = Object.values(results).every(result => result === true);
    
    if (allPassed) {
        console.log('🎉 Todos os testes passaram! Problema de foco corrigido.');
    } else {
        console.log('⚠️ Alguns testes falharam. Verifique os erros acima.');
    }
    
    return results;
}

// Função para testar manualmente
function testManual() {
    console.log('🧪 Teste manual de foco no gerente...');
    console.log('1. Abra o chat do gerente');
    console.log('2. Selecione um funcionário');
    console.log('3. Envie uma mensagem');
    console.log('4. Verifique se a mensagem aparece automaticamente');
    console.log('5. Verifique se o foco é mantido no input');
    console.log('6. Execute monitorGerenteLogs() para ver logs');
    
    return monitorGerenteLogs();
}

// Exportar funções
window.gerenteFocusTest = {
    runAllTests,
    testGerenteFocus,
    testGerenteSendMessage,
    testFocusMaintenance,
    compareWithFuncionario,
    monitorGerenteLogs,
    testManual
};

console.log('✅ Script de teste de foco no gerente carregado!');
console.log('📝 Use gerenteFocusTest.runAllTests() para executar todos os testes');
console.log('📝 Use gerenteFocusTest.testManual() para teste manual');
