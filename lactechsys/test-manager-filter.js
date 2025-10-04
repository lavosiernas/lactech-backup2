// Script de Teste para Filtro do Gerente
// Execute no console do navegador para testar

console.log('🔍 Testando filtro do gerente na lista...');

// Teste 1: Verificar se window.currentUser está definido
function testCurrentUserDefined() {
    console.log('👤 Testando se window.currentUser está definido...');
    
    if (window.currentUser) {
        console.log('✅ window.currentUser definido:', {
            id: window.currentUser.id,
            email: window.currentUser.email
        });
        return true;
    } else {
        console.error('❌ window.currentUser não está definido');
        return false;
    }
}

// Teste 2: Verificar se o gerente aparece na lista
async function testManagerInList() {
    console.log('📋 Testando se o gerente aparece na lista...');
    
    try {
        const supabase = await getSupabaseClient();
        const { data: { user } } = await supabase.auth.getUser();
        
        if (!user) {
            console.error('❌ Usuário não autenticado');
            return false;
        }
        
        // Buscar farm_id do usuário
        const { data: userData } = await supabase
            .from('users')
            .select('farm_id')
            .eq('id', user.id)
            .single();
        
        if (!userData?.farm_id) {
            console.error('❌ Farm ID não encontrado');
            return false;
        }
        
        // Buscar todos os funcionários
        const employees = await getFarmUsers(userData.farm_id);
        console.log('👥 Total de funcionários encontrados:', employees.length);
        
        // Verificar se o gerente está na lista
        const managerInList = employees.find(emp => 
            emp.id === user.id || emp.email === user.email
        );
        
        if (managerInList) {
            console.log('⚠️ Gerente ainda aparece na lista:', {
                name: managerInList.name,
                id: managerInList.id,
                email: managerInList.email
            });
            return false;
        } else {
            console.log('✅ Gerente não aparece na lista (correto)');
            return true;
        }
        
    } catch (error) {
        console.error('❌ Erro no teste:', error);
        return false;
    }
}

// Teste 3: Verificar se a função displayEmployees está filtrando corretamente
function testDisplayEmployeesFilter() {
    console.log('🎨 Testando filtro na função displayEmployees...');
    
    try {
        // Verificar se a função existe
        if (typeof displayEmployees !== 'function') {
            console.error('❌ Função displayEmployees não existe');
            return false;
        }
        
        // Criar dados de teste
        const testEmployees = [
            {
                id: '1',
                name: 'Funcionário 1',
                email: 'func1@test.com',
                role: 'funcionario'
            },
            {
                id: window.currentUser?.id || 'current',
                name: 'Gerente Atual',
                email: window.currentUser?.email || 'gerente@test.com',
                role: 'gerente'
            },
            {
                id: '3',
                name: 'Funcionário 2',
                email: 'func2@test.com',
                role: 'funcionario'
            }
        ];
        
        console.log('📋 Dados de teste:', testEmployees);
        
        // Testar a função
        displayEmployees(testEmployees);
        
        // Verificar se o gerente foi filtrado
        const employeesList = document.getElementById('employeesList');
        if (!employeesList) {
            console.error('❌ Lista de funcionários não encontrada');
            return false;
        }
        
        const employeeItems = employeesList.querySelectorAll('.flex.items-center.space-x-3');
        console.log('📊 Itens na lista:', employeeItems.length);
        
        // Verificar se o número de itens é correto (deve ser 2, não 3)
        if (employeeItems.length === 2) {
            console.log('✅ Filtro funcionando - gerente removido da lista');
            return true;
        } else {
            console.log('⚠️ Filtro não funcionando - número incorreto de itens');
            return false;
        }
        
    } catch (error) {
        console.error('❌ Erro no teste de filtro:', error);
        return false;
    }
}

// Teste 4: Verificar se o modal do chat pode ser aberto
function testChatModal() {
    console.log('🚪 Testando modal do chat...');
    
    try {
        if (typeof openChatModal !== 'function') {
            console.error('❌ Função openChatModal não existe');
            return false;
        }
        
        openChatModal();
        
        const modal = document.getElementById('chatModal');
        if (!modal) {
            console.error('❌ Modal de chat não encontrado');
            return false;
        }
        
        if (modal.classList.contains('hidden')) {
            console.log('⚠️ Modal ainda está oculto');
            return false;
        } else {
            console.log('✅ Modal de chat aberto com sucesso');
            return true;
        }
        
    } catch (error) {
        console.error('❌ Erro ao abrir modal:', error);
        return false;
    }
}

// Executar todos os testes
async function runAllTests() {
    console.log('🚀 Executando todos os testes de filtro...');
    
    const results = {
        currentUserDefined: testCurrentUserDefined(),
        managerInList: await testManagerInList(),
        displayEmployeesFilter: testDisplayEmployeesFilter(),
        chatModal: testChatModal()
    };
    
    console.log('📋 Resultados dos testes:');
    console.table(results);
    
    const allPassed = Object.values(results).every(result => result === true);
    
    if (allPassed) {
        console.log('🎉 Todos os testes passaram! Filtro do gerente funcionando.');
    } else {
        console.log('⚠️ Alguns testes falharam. Verifique os erros acima.');
    }
    
    return results;
}

// Exportar funções para uso manual
window.managerFilterTest = {
    runAllTests,
    testCurrentUserDefined,
    testManagerInList,
    testDisplayEmployeesFilter,
    testChatModal
};

console.log('✅ Script de teste de filtro carregado! Use managerFilterTest.runAllTests() para executar todos os testes.');
