// Script de Teste para Verificar Correções de Erros
// Execute no console do navegador para testar

console.log('🧪 Testando correções de erros...');

// Teste 1: Verificar se as funções estão protegidas contra null
function testNullSafety() {
    console.log('🛡️ Testando proteção contra null...');
    
    // Testar isEmployeeOnline com null
    try {
        const result1 = isEmployeeOnline(null);
        console.log('✅ isEmployeeOnline(null) retornou:', result1);
    } catch (error) {
        console.error('❌ isEmployeeOnline(null) falhou:', error);
        return false;
    }
    
    // Testar isEmployeeOnline com undefined
    try {
        const result2 = isEmployeeOnline(undefined);
        console.log('✅ isEmployeeOnline(undefined) retornou:', result2);
    } catch (error) {
        console.error('❌ isEmployeeOnline(undefined) falhou:', error);
        return false;
    }
    
    // Testar isEmployeeOnline com objeto vazio
    try {
        const result3 = isEmployeeOnline({});
        console.log('✅ isEmployeeOnline({}) retornou:', result3);
    } catch (error) {
        console.error('❌ isEmployeeOnline({}) falhou:', error);
        return false;
    }
    
    // Testar formatLastSeen com null
    try {
        const result4 = formatLastSeen(null);
        console.log('✅ formatLastSeen(null) retornou:', result4);
    } catch (error) {
        console.error('❌ formatLastSeen(null) falhou:', error);
        return false;
    }
    
    // Testar formatLastSeen com data inválida
    try {
        const result5 = formatLastSeen('data-invalida');
        console.log('✅ formatLastSeen("data-invalida") retornou:', result5);
    } catch (error) {
        console.error('❌ formatLastSeen("data-invalida") falhou:', error);
        return false;
    }
    
    return true;
}

// Teste 2: Verificar se selectEmployee está protegido
function testSelectEmployeeSafety() {
    console.log('👤 Testando selectEmployee com dados inválidos...');
    
    // Testar selectEmployee com null
    try {
        selectEmployee(null);
        console.log('✅ selectEmployee(null) executou sem erro');
    } catch (error) {
        console.error('❌ selectEmployee(null) falhou:', error);
        return false;
    }
    
    // Testar selectEmployee com undefined
    try {
        selectEmployee(undefined);
        console.log('✅ selectEmployee(undefined) executou sem erro');
    } catch (error) {
        console.error('❌ selectEmployee(undefined) falhou:', error);
        return false;
    }
    
    // Testar selectEmployee com objeto sem propriedades necessárias
    try {
        selectEmployee({});
        console.log('✅ selectEmployee({}) executou sem erro');
    } catch (error) {
        console.error('❌ selectEmployee({}) falhou:', error);
        return false;
    }
    
    return true;
}

// Teste 3: Verificar se as funções existem
function testFunctionExistence() {
    console.log('🔍 Verificando existência das funções...');
    
    const functions = [
        'isEmployeeOnline',
        'formatLastSeen',
        'selectEmployee',
        'loadEmployees',
        'displayEmployees',
        'loadChatMessages',
        'sendChatMessageLocal'
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

// Teste 4: Verificar se os elementos HTML existem
function testHTMLElements() {
    console.log('🏗️ Verificando elementos HTML...');
    
    const elements = [
        'chatModal',
        'employeesList',
        'onlineEmployees',
        'chatMessages',
        'chatMessageInput',
        'sendMessageBtn',
        'selectedEmployeeName',
        'selectedEmployeeInitial',
        'selectedEmployeeStatus'
    ];
    
    let allExist = true;
    
    elements.forEach(elementId => {
        const element = document.getElementById(elementId);
        if (element) {
            console.log(`✅ Elemento ${elementId} existe`);
        } else {
            console.error(`❌ Elemento ${elementId} não existe`);
            allExist = false;
        }
    });
    
    return allExist;
}

// Teste 5: Verificar se o chat pode ser aberto
function testChatModal() {
    console.log('🚪 Testando abertura do modal de chat...');
    
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
    console.log('🚀 Executando todos os testes de correção...');
    
    const results = {
        nullSafety: testNullSafety(),
        selectEmployeeSafety: testSelectEmployeeSafety(),
        functionExistence: testFunctionExistence(),
        htmlElements: testHTMLElements(),
        chatModal: testChatModal()
    };
    
    console.log('📋 Resultados dos testes:');
    console.table(results);
    
    const allPassed = Object.values(results).every(result => result === true);
    
    if (allPassed) {
        console.log('🎉 Todos os testes passaram! Erros corrigidos com sucesso.');
    } else {
        console.log('⚠️ Alguns testes falharam. Verifique os erros acima.');
    }
    
    return results;
}

// Exportar funções para uso manual
window.errorFixTest = {
    runAllTests,
    testNullSafety,
    testSelectEmployeeSafety,
    testFunctionExistence,
    testHTMLElements,
    testChatModal
};

console.log('✅ Script de teste de correções carregado! Use errorFixTest.runAllTests() para executar todos os testes.');
