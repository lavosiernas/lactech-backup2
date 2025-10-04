// Script de Teste para o Sistema Dual de Bancos
// Execute no console do navegador para testar

console.log('🧪 Testando sistema dual de bancos...');

// Teste 1: Verificar se as variáveis globais estão disponíveis
function testGlobalVariables() {
    console.log('📊 Testando variáveis globais...');
    
    const variables = [
        'DATABASE_CONFIG',
        'systemSupabase',
        'chatSupabase',
        'getSupabaseClient',
        'getChatClient',
        'getFarmUsers',
        'sendChatMessage',
        'getChatMessages',
        'setupRealtimeChat',
        'disconnectAllRealtime'
    ];
    
    variables.forEach(varName => {
        if (typeof window[varName] !== 'undefined') {
            console.log(`✅ ${varName} disponível`);
        } else {
            console.error(`❌ ${varName} não disponível`);
        }
    });
}

// Teste 2: Verificar clientes Supabase
async function testSupabaseClients() {
    console.log('🔌 Testando clientes Supabase...');
    
    try {
        // Testar cliente do sistema
        const systemClient = await getSupabaseClient();
        if (systemClient) {
            console.log('✅ Cliente do sistema funcionando');
        } else {
            console.error('❌ Cliente do sistema não funcionando');
        }
        
        // Testar cliente do chat
        const chatClient = await getChatClient();
        if (chatClient) {
            console.log('✅ Cliente do chat funcionando');
        } else {
            console.error('❌ Cliente do chat não funcionando');
        }
        
        return true;
    } catch (error) {
        console.error('❌ Erro nos clientes:', error);
        return false;
    }
}

// Teste 3: Verificar autenticação
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

// Teste 4: Verificar busca de usuários
async function testGetFarmUsers() {
    console.log('👥 Testando busca de usuários...');
    
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
            console.log(`👤 ${index + 1}. ${emp.name} (${emp.role})`);
        });
        
        return true;
    } catch (error) {
        console.error('❌ Erro na busca de usuários:', error);
        return false;
    }
}

// Teste 5: Verificar configuração dos bancos
function testDatabaseConfig() {
    console.log('⚙️ Testando configuração dos bancos...');
    
    try {
        const config = window.DATABASE_CONFIG;
        
        if (!config) {
            console.error('❌ DATABASE_CONFIG não encontrado');
            return false;
        }
        
        if (!config.SYSTEM || !config.SYSTEM.url || !config.SYSTEM.key) {
            console.error('❌ Configuração do sistema inválida');
            return false;
        }
        
        if (!config.CHAT || !config.CHAT.url || !config.CHAT.key) {
            console.error('❌ Configuração do chat inválida');
            return false;
        }
        
        console.log('✅ Configuração do sistema:', config.SYSTEM.url);
        console.log('✅ Configuração do chat:', config.CHAT.url);
        
        return true;
    } catch (error) {
        console.error('❌ Erro na configuração:', error);
        return false;
    }
}

// Executar todos os testes
async function runAllTests() {
    console.log('🚀 Executando todos os testes...');
    
    const results = {
        globalVariables: testGlobalVariables(),
        databaseConfig: testDatabaseConfig(),
        supabaseClients: await testSupabaseClients(),
        authentication: await testAuthentication(),
        getFarmUsers: await testGetFarmUsers()
    };
    
    console.log('📋 Resultados dos testes:');
    console.table(results);
    
    const allPassed = Object.values(results).every(result => result === true);
    
    if (allPassed) {
        console.log('🎉 Todos os testes passaram! Sistema dual funcionando.');
    } else {
        console.log('⚠️ Alguns testes falharam. Verifique os erros acima.');
    }
    
    return results;
}

// Exportar funções para uso manual
window.dualDatabaseTest = {
    runAllTests,
    testGlobalVariables,
    testDatabaseConfig,
    testSupabaseClients,
    testAuthentication,
    testGetFarmUsers
};

console.log('✅ Script de teste carregado! Use dualDatabaseTest.runAllTests() para executar todos os testes.');
