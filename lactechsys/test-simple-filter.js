// Script de Teste Simples para Filtro do Gerente
// Execute no console do navegador para testar

console.log('🔍 Teste simples do filtro do gerente...');

// Função para testar o filtro diretamente
async function testFilterDirectly() {
    console.log('🧪 Testando filtro diretamente...');
    
    try {
        // Verificar se window.currentUser está definido
        if (!window.currentUser) {
            console.error('❌ window.currentUser não está definido');
            return false;
        }
        
        console.log('👤 Usuário atual:', {
            id: window.currentUser.id,
            email: window.currentUser.email
        });
        
        // Buscar funcionários
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
        
        const employees = await getFarmUsers(userData.farm_id);
        console.log('👥 Funcionários encontrados:', employees.length);
        
        // Aplicar o mesmo filtro da função displayEmployees
        const filteredEmployees = employees.filter(employee => {
            const shouldFilter = employee.id === window.currentUser?.id || employee.email === window.currentUser?.email;
            if (shouldFilter) {
                console.log('🚫 Filtrando:', employee.name);
            }
            return !shouldFilter;
        });
        
        console.log('✅ Funcionários após filtro:', filteredEmployees.length);
        console.log('📋 Lista filtrada:', filteredEmployees.map(emp => emp.name));
        
        // Verificar se o gerente foi removido
        const managerStillInList = filteredEmployees.find(emp => 
            emp.id === window.currentUser?.id || emp.email === window.currentUser?.email
        );
        
        if (managerStillInList) {
            console.log('❌ Gerente ainda está na lista após filtro!');
            return false;
        } else {
            console.log('✅ Gerente removido com sucesso da lista!');
            return true;
        }
        
    } catch (error) {
        console.error('❌ Erro no teste:', error);
        return false;
    }
}

// Função para forçar a recarga dos funcionários
async function reloadEmployees() {
    console.log('🔄 Recarregando funcionários...');
    
    try {
        if (typeof loadEmployees === 'function') {
            await loadEmployees();
            console.log('✅ Funcionários recarregados');
            return true;
        } else {
            console.error('❌ Função loadEmployees não encontrada');
            return false;
        }
    } catch (error) {
        console.error('❌ Erro ao recarregar funcionários:', error);
        return false;
    }
}

// Função para abrir o chat e verificar
async function testChatOpen() {
    console.log('🚪 Testando abertura do chat...');
    
    try {
        if (typeof openChatModal === 'function') {
            openChatModal();
            
            // Aguardar um pouco para o modal carregar
            await new Promise(resolve => setTimeout(resolve, 1000));
            
            // Verificar se o modal está aberto
            const modal = document.getElementById('chatModal');
            if (modal && !modal.classList.contains('hidden')) {
                console.log('✅ Modal de chat aberto');
                
                // Verificar quantos funcionários aparecem na lista
                const employeesList = document.getElementById('employeesList');
                if (employeesList) {
                    const employeeItems = employeesList.querySelectorAll('.flex.items-center.space-x-3');
                    console.log('📊 Funcionários na lista:', employeeItems.length);
                    
                    // Verificar se algum item tem o nome do gerente atual
                    let managerFound = false;
                    employeeItems.forEach(item => {
                        const nameElement = item.querySelector('h4');
                        if (nameElement && nameElement.textContent.includes('LacTech')) {
                            managerFound = true;
                            console.log('⚠️ Gerente encontrado na lista:', nameElement.textContent);
                        }
                    });
                    
                    if (managerFound) {
                        console.log('❌ Gerente ainda aparece na lista visual!');
                        return false;
                    } else {
                        console.log('✅ Gerente não aparece na lista visual!');
                        return true;
                    }
                }
            } else {
                console.log('⚠️ Modal não está aberto');
                return false;
            }
        } else {
            console.error('❌ Função openChatModal não encontrada');
            return false;
        }
    } catch (error) {
        console.error('❌ Erro ao testar chat:', error);
        return false;
    }
}

// Executar todos os testes
async function runAllTests() {
    console.log('🚀 Executando testes simples...');
    
    const results = {
        filterDirectly: await testFilterDirectly(),
        reloadEmployees: await reloadEmployees(),
        chatOpen: await testChatOpen()
    };
    
    console.log('📋 Resultados:');
    console.table(results);
    
    const allPassed = Object.values(results).every(result => result === true);
    
    if (allPassed) {
        console.log('🎉 Todos os testes passaram! Filtro funcionando.');
    } else {
        console.log('⚠️ Alguns testes falharam.');
    }
    
    return results;
}

// Exportar funções
window.simpleFilterTest = {
    runAllTests,
    testFilterDirectly,
    reloadEmployees,
    testChatOpen
};

console.log('✅ Script de teste simples carregado! Use simpleFilterTest.runAllTests() para executar todos os testes.');
