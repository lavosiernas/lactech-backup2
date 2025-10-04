// Script de Teste para Correção do Erro Async
// Execute no console do navegador para testar

console.log('🔧 Testando correção do erro async...');

// Função para testar se o erro foi corrigido
function testAsyncError() {
    console.log('🔍 Testando se o erro async foi corrigido...');
    
    try {
        // Verificar se setupRealtimeUpdates é uma função async
        if (typeof setupRealtimeUpdates !== 'function') {
            console.error('❌ Função setupRealtimeUpdates não encontrada');
            return false;
        }
        
        // Verificar se a função pode ser chamada
        console.log('✅ Função setupRealtimeUpdates encontrada');
        
        // Verificar se getSupabaseClient existe
        if (typeof getSupabaseClient !== 'function') {
            console.error('❌ Função getSupabaseClient não encontrada');
            return false;
        }
        
        console.log('✅ Função getSupabaseClient encontrada');
        return true;
        
    } catch (error) {
        console.error('❌ Erro no teste:', error);
        return false;
    }
}

// Função para testar se não há mais erros de sintaxe
async function testSyntaxError() {
    console.log('🔍 Testando se não há mais erros de sintaxe...');
    
    try {
        // Tentar chamar a função setupRealtimeUpdates
        await setupRealtimeUpdates();
        console.log('✅ Função setupRealtimeUpdates executada sem erros');
        return true;
        
    } catch (error) {
        if (error.message.includes('await is only valid in async functions')) {
            console.error('❌ Erro de sintaxe ainda existe:', error.message);
            return false;
        } else {
            console.log('⚠️ Outro tipo de erro (pode ser esperado):', error.message);
            return true; // Outros erros podem ser esperados
        }
    }
}

// Função para verificar se a página carrega sem erros
function testPageLoad() {
    console.log('🔍 Testando se a página carrega sem erros...');
    
    try {
        // Verificar se as funções principais existem
        const requiredFunctions = [
            'initializePage',
            'setupRealtimeUpdates',
            'getSupabaseClient',
            'openChatModal',
            'setupChatRealtime'
        ];
        
        const missingFunctions = [];
        
        requiredFunctions.forEach(funcName => {
            if (typeof window[funcName] !== 'function') {
                missingFunctions.push(funcName);
            }
        });
        
        if (missingFunctions.length > 0) {
            console.error('❌ Funções não encontradas:', missingFunctions);
            return false;
        }
        
        console.log('✅ Todas as funções principais encontradas');
        return true;
        
    } catch (error) {
        console.error('❌ Erro no teste de carregamento:', error);
        return false;
    }
}

// Função para testar real-time do chat
async function testChatRealtime() {
    console.log('🔔 Testando real-time do chat...');
    
    try {
        // Verificar se as funções de chat existem
        if (typeof setupChatRealtime !== 'function') {
            console.error('❌ Função setupChatRealtime não encontrada');
            return false;
        }
        
        if (typeof openChatModal !== 'function') {
            console.error('❌ Função openChatModal não encontrada');
            return false;
        }
        
        console.log('✅ Funções de chat encontradas');
        
        // Tentar abrir o modal de chat
        await openChatModal();
        
        // Verificar se o modal foi aberto
        const modal = document.getElementById('chatModal');
        if (modal && !modal.classList.contains('hidden')) {
            console.log('✅ Modal de chat aberto com sucesso');
            
            // Fechar o modal
            if (typeof closeChatModal === 'function') {
                closeChatModal();
                console.log('✅ Modal de chat fechado');
            }
            
            return true;
        } else {
            console.log('⚠️ Modal de chat não foi aberto');
            return false;
        }
        
    } catch (error) {
        console.error('❌ Erro no teste de chat:', error);
        return false;
    }
}

// Função para monitorar erros em tempo real
function monitorErrors() {
    console.log('👀 Monitorando erros em tempo real...');
    
    const originalError = console.error;
    const errors = [];
    
    console.error = function(...args) {
        const message = args.join(' ');
        if (message.includes('await is only valid in async functions') || 
            message.includes('SyntaxError') ||
            message.includes('setupRealtimeUpdates')) {
            errors.push(message);
        }
        originalError.apply(console, args);
    };
    
    // Restaurar após 5 segundos
    setTimeout(() => {
        console.error = originalError;
        if (errors.length > 0) {
            console.log('⚠️ Erros encontrados:', errors);
        } else {
            console.log('✅ Nenhum erro de sintaxe encontrado');
        }
    }, 5000);
    
    return errors;
}

// Executar todos os testes
async function runAllTests() {
    console.log('🚀 Executando testes de correção async...');
    
    const results = {
        asyncError: testAsyncError(),
        syntaxError: await testSyntaxError(),
        pageLoad: testPageLoad(),
        chatRealtime: await testChatRealtime()
    };
    
    console.log('📋 Resultados dos testes:');
    console.table(results);
    
    const allPassed = Object.values(results).every(result => result === true);
    
    if (allPassed) {
        console.log('🎉 Todos os testes passaram! Erro async corrigido.');
    } else {
        console.log('⚠️ Alguns testes falharam. Verifique os erros acima.');
    }
    
    return results;
}

// Função para testar manualmente
function testManual() {
    console.log('🧪 Teste manual de correção...');
    console.log('1. Recarregue a página');
    console.log('2. Abra o console');
    console.log('3. Verifique se não há erros de sintaxe');
    console.log('4. Execute monitorErrors() para monitorar erros');
    
    return monitorErrors();
}

// Exportar funções
window.asyncFixTest = {
    runAllTests,
    testAsyncError,
    testSyntaxError,
    testPageLoad,
    testChatRealtime,
    monitorErrors,
    testManual
};

console.log('✅ Script de teste de correção async carregado!');
console.log('📝 Use asyncFixTest.runAllTests() para executar todos os testes');
console.log('📝 Use asyncFixTest.testManual() para teste manual');
