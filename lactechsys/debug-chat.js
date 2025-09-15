// Script de Debug para o Chat
// Execute no console do navegador para diagnosticar problemas

console.log('🔍 Iniciando debug do chat...');

// Função para testar o modal do chat
async function debugChatModal() {
    console.log('🖥️ Testando modal do chat...');
    
    // Verificar se o modal existe
    const modal = document.getElementById('chatModal');
    if (!modal) {
        console.error('❌ Modal do chat não encontrado');
        return false;
    }
    
    console.log('✅ Modal do chat encontrado');
    
    // Verificar elementos internos
    const elements = [
        'employeesList',
        'onlineEmployees',
        'chatMessages',
        'chatMessageInput',
        'sendMessageBtn'
    ];
    
    elements.forEach(elementId => {
        const element = document.getElementById(elementId);
        if (element) {
            console.log(`✅ Elemento ${elementId} encontrado`);
        } else {
            console.error(`❌ Elemento ${elementId} não encontrado`);
        }
    });
    
    return true;
}

// Função para testar as funções do chat
function debugChatFunctions() {
    console.log('🔧 Testando funções do chat...');
    
    const functions = [
        'openChatModal',
        'closeChatModal',
        'loadEmployees',
        'displayEmployees',
        'selectEmployee',
        'loadChatMessages',
        'displayChatMessages',
        'sendChatMessageLocal',
        'handleChatKeyPress',
        'searchEmployees',
        'toggleChatSidebar'
    ];
    
    functions.forEach(funcName => {
        if (typeof window[funcName] === 'function') {
            console.log(`✅ Função ${funcName} encontrada`);
        } else {
            console.error(`❌ Função ${funcName} não encontrada`);
        }
    });
}

// Função para testar a autenticação
async function debugAuthentication() {
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
        console.log('🆔 User ID:', user.id);
        
        return true;
    } catch (error) {
        console.error('❌ Erro no teste de autenticação:', error);
        return false;
    }
}

// Função para testar busca de farm_id
async function debugFarmId() {
    console.log('🏢 Testando busca de farm_id...');
    
    try {
        const supabase = await getSupabaseClient();
        const { data: { user } } = await supabase.auth.getUser();
        
        if (!user) {
            console.error('❌ Usuário não autenticado');
            return false;
        }
        
        const { data: userData, error: userError } = await supabase
            .from('users')
            .select('farm_id')
            .eq('id', user.id)
            .single();
        
        if (userError) {
            console.error('❌ Erro ao buscar farm_id:', userError);
            return false;
        }
        
        if (!userData?.farm_id) {
            console.error('❌ Farm ID não encontrado');
            return false;
        }
        
        console.log('✅ Farm ID encontrado:', userData.farm_id);
        return true;
    } catch (error) {
        console.error('❌ Erro no teste de farm_id:', error);
        return false;
    }
}

// Função para testar sincronização de usuários
async function debugUserSync() {
    console.log('👥 Testando sincronização de usuários...');
    
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
            console.log(`👤 ${index + 1}. ${emp.name} (${emp.role}) - ${emp.email}`);
        });
        
        return true;
    } catch (error) {
        console.error('❌ Erro na sincronização:', error);
        return false;
    }
}

// Função para testar abertura do modal
async function debugOpenModal() {
    console.log('🚪 Testando abertura do modal...');
    
    try {
        // Verificar se a função existe
        if (typeof openChatModal !== 'function') {
            console.error('❌ Função openChatModal não encontrada');
            return false;
        }
        
        // Tentar abrir o modal
        openChatModal();
        console.log('✅ Modal aberto com sucesso');
        
        // Verificar se o modal está visível
        const modal = document.getElementById('chatModal');
        if (modal && !modal.classList.contains('hidden')) {
            console.log('✅ Modal está visível');
        } else {
            console.error('❌ Modal não está visível');
        }
        
        return true;
    } catch (error) {
        console.error('❌ Erro ao abrir modal:', error);
        return false;
    }
}

// Função para testar carregamento de funcionários
async function debugLoadEmployees() {
    console.log('👥 Testando carregamento de funcionários...');
    
    try {
        // Verificar se a função existe
        if (typeof loadEmployees !== 'function') {
            console.error('❌ Função loadEmployees não encontrada');
            return false;
        }
        
        // Tentar carregar funcionários
        await loadEmployees();
        console.log('✅ Função loadEmployees executada');
        
        // Verificar se há funcionários na lista
        const employeesList = document.getElementById('employeesList');
        if (employeesList) {
            const employeeItems = employeesList.querySelectorAll('div');
            console.log(`📊 Funcionários na lista: ${employeeItems.length}`);
        }
        
        return true;
    } catch (error) {
        console.error('❌ Erro ao carregar funcionários:', error);
        return false;
    }
}

// Executar todos os testes de debug
async function runChatDebug() {
    console.log('🚀 Executando debug completo do chat...');
    
    const results = {
        modal: await debugChatModal(),
        functions: debugChatFunctions(),
        authentication: await debugAuthentication(),
        farmId: await debugFarmId(),
        userSync: await debugUserSync(),
        openModal: await debugOpenModal(),
        loadEmployees: await debugLoadEmployees()
    };
    
    console.log('📋 Resultados do debug:');
    console.table(results);
    
    const allPassed = Object.values(results).every(result => result === true);
    
    if (allPassed) {
        console.log('🎉 Todos os testes de debug passaram!');
    } else {
        console.log('⚠️ Alguns testes falharam. Verifique os erros acima.');
    }
    
    return results;
}

// Exportar funções para uso manual
window.chatDebug = {
    runChatDebug,
    debugChatModal,
    debugChatFunctions,
    debugAuthentication,
    debugFarmId,
    debugUserSync,
    debugOpenModal,
    debugLoadEmployees
};

console.log('✅ Script de debug carregado! Use chatDebug.runChatDebug() para executar todos os testes.');
