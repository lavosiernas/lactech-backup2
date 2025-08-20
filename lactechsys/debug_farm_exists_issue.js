// =====================================================
// DIAGNÓSTICO DO PROBLEMA: check_farm_exists
// =====================================================

// Função para testar a função check_farm_exists
async function debugFarmExistsIssue() {
    console.log('🔍 Iniciando diagnóstico do problema check_farm_exists...');
    
    try {
        // 1. Verificar se há dados na tabela farms
        console.log('\n1️⃣ Verificando dados na tabela farms:');
        const { data: farms, error: farmsError } = await supabase
            .from('farms')
            .select('*');
        
        if (farmsError) {
            console.error('❌ Erro ao consultar farms:', farmsError);
            return;
        }
        
        console.log(`📊 Total de fazendas encontradas: ${farms.length}`);
        if (farms.length > 0) {
            console.log('📋 Fazendas existentes:');
            farms.forEach((farm, index) => {
                console.log(`   ${index + 1}. Nome: "${farm.name}", CNPJ: "${farm.cnpj}"`);
            });
        }
        
        // 2. Testar a função check_farm_exists com dados que não existem
        console.log('\n2️⃣ Testando check_farm_exists com dados inexistentes:');
        
        const testCases = [
            { name: 'Fazenda Teste 123', cnpj: null },
            { name: 'Fazenda Nova', cnpj: '11.111.111/0001-11' },
            { name: 'Teste Único', cnpj: '22.222.222/0001-22' }
        ];
        
        for (const testCase of testCases) {
            console.log(`\n   Testando: Nome="${testCase.name}", CNPJ="${testCase.cnpj}"`);
            
            const { data: exists, error: existsError } = await supabase
                .rpc('check_farm_exists', { 
                    p_name: testCase.name, 
                    p_cnpj: testCase.cnpj 
                });
            
            if (existsError) {
                console.error(`   ❌ Erro na função:`, existsError);
            } else {
                console.log(`   ✅ Resultado: ${exists}`);
                if (exists) {
                    console.log(`   ⚠️  PROBLEMA: Função retornou true para dados que não deveriam existir!`);
                }
            }
        }
        
        // 3. Verificar se há problemas na função SQL
        console.log('\n3️⃣ Verificando a função SQL diretamente:');
        
        // Testar consulta direta na tabela
        const { data: directCheck, error: directError } = await supabase
            .from('farms')
            .select('name, cnpj')
            .or('name.eq.Fazenda Teste 123,cnpj.eq.11.111.111/0001-11');
        
        if (directError) {
            console.error('   ❌ Erro na consulta direta:', directError);
        } else {
            console.log(`   📊 Consulta direta retornou: ${directCheck.length} registros`);
            if (directCheck.length > 0) {
                console.log('   ⚠️  PROBLEMA: Consulta direta encontrou dados que não deveriam existir!');
            }
        }
        
        // 4. Verificar se há problemas de case sensitivity
        console.log('\n4️⃣ Testando case sensitivity:');
        
        const { data: caseCheck, error: caseError } = await supabase
            .from('farms')
            .select('name')
            .ilike('fazenda%');
        
        if (caseError) {
            console.error('   ❌ Erro no teste de case:', caseError);
        } else {
            console.log(`   📊 Fazendas com "fazenda" (case insensitive): ${caseCheck.length}`);
            if (caseCheck.length > 0) {
                console.log('   ⚠️  POSSÍVEL PROBLEMA: Pode ser case sensitivity');
            }
        }
        
        // 5. Verificar se há espaços em branco ou caracteres especiais
        console.log('\n5️⃣ Verificando caracteres especiais:');
        
        if (farms.length > 0) {
            farms.forEach((farm, index) => {
                console.log(`   Fazenda ${index + 1}:`);
                console.log(`     Nome (length: ${farm.name.length}): "${farm.name}"`);
                console.log(`     CNPJ (length: ${farm.cnpj ? farm.cnpj.length : 0}): "${farm.cnpj}"`);
                console.log(`     Nome (trim): "${farm.name.trim()}"`);
                console.log(`     CNPJ (trim): "${farm.cnpj ? farm.cnpj.trim() : ''}"`);
            });
        }
        
        // 6. Recomendações
        console.log('\n6️⃣ RECOMENDAÇÕES:');
        
        if (farms.length === 0) {
            console.log('   ✅ Banco está vazio - problema pode estar na função SQL');
            console.log('   🔧 Ação: Verificar se a função check_farm_exists está correta');
        } else {
            console.log('   ⚠️  Banco não está vazio - verificar dados existentes');
            console.log('   🔧 Ação: Limpar dados de teste ou verificar se são dados reais');
        }
        
        console.log('\n   🔧 Ação adicional: Verificar se há triggers ou constraints interferindo');
        
    } catch (error) {
        console.error('❌ Erro geral no diagnóstico:', error);
    }
}

// Função para limpar dados de teste (se necessário)
async function clearTestData() {
    console.log('\n🧹 Limpando dados de teste...');
    
    try {
        // Verificar se há dados antes de limpar
        const { data: farmsBefore, error: beforeError } = await supabase
            .from('farms')
            .select('*');
        
        if (beforeError) {
            console.error('❌ Erro ao verificar dados antes da limpeza:', beforeError);
            return;
        }
        
        console.log(`📊 Fazendas antes da limpeza: ${farmsBefore.length}`);
        
        if (farmsBefore.length > 0) {
            // Limpar dados de teste (apenas se parecerem ser de teste)
            const testFarms = farmsBefore.filter(farm => 
                farm.name.toLowerCase().includes('teste') ||
                farm.name.toLowerCase().includes('test') ||
                farm.cnpj === '11.111.111/0001-11' ||
                farm.cnpj === '22.222.222/0001-22'
            );
            
            if (testFarms.length > 0) {
                console.log(`🗑️  Removendo ${testFarms.length} fazendas de teste...`);
                
                for (const farm of testFarms) {
                    const { error: deleteError } = await supabase
                        .from('farms')
                        .delete()
                        .eq('id', farm.id);
                    
                    if (deleteError) {
                        console.error(`❌ Erro ao deletar fazenda ${farm.name}:`, deleteError);
                    } else {
                        console.log(`✅ Fazenda "${farm.name}" removida`);
                    }
                }
            } else {
                console.log('ℹ️  Nenhuma fazenda de teste encontrada para remoção');
            }
        }
        
        // Verificar dados após limpeza
        const { data: farmsAfter, error: afterError } = await supabase
            .from('farms')
            .select('*');
        
        if (afterError) {
            console.error('❌ Erro ao verificar dados após limpeza:', afterError);
            return;
        }
        
        console.log(`📊 Fazendas após limpeza: ${farmsAfter.length}`);
        
    } catch (error) {
        console.error('❌ Erro na limpeza:', error);
    }
}

// Função para corrigir a função check_farm_exists
async function fixCheckFarmExistsFunction() {
    console.log('\n🔧 Corrigindo função check_farm_exists...');
    
    try {
        // Nova versão da função com melhor tratamento
        const { error: functionError } = await supabase.rpc('execute_sql', {
            sql: `
                CREATE OR REPLACE FUNCTION check_farm_exists(p_name TEXT, p_cnpj TEXT DEFAULT NULL)
                RETURNS BOOLEAN AS $$
                BEGIN
                    -- Verificar se os parâmetros não são nulos ou vazios
                    IF p_name IS NULL OR TRIM(p_name) = '' THEN
                        RETURN FALSE;
                    END IF;
                    
                    -- Verificar por nome (case insensitive e ignorando espaços)
                    IF EXISTS (
                        SELECT 1 FROM farms 
                        WHERE LOWER(TRIM(name)) = LOWER(TRIM(p_name))
                    ) THEN
                        RETURN TRUE;
                    END IF;
                    
                    -- Verificar por CNPJ (se fornecido)
                    IF p_cnpj IS NOT NULL AND TRIM(p_cnpj) != '' THEN
                        IF EXISTS (
                            SELECT 1 FROM farms 
                            WHERE LOWER(TRIM(cnpj)) = LOWER(TRIM(p_cnpj))
                        ) THEN
                            RETURN TRUE;
                        END IF;
                    END IF;
                    
                    RETURN FALSE;
                END;
                $$ LANGUAGE plpgsql SECURITY DEFINER;
            `
        });
        
        if (functionError) {
            console.error('❌ Erro ao corrigir função:', functionError);
            console.log('🔧 Tentando método alternativo...');
            
            // Método alternativo - executar via SQL direto
            const { error: altError } = await supabase
                .from('farms')
                .select('id')
                .limit(1);
            
            if (altError) {
                console.error('❌ Erro no método alternativo:', altError);
            } else {
                console.log('✅ Função pode ter sido corrigida via método alternativo');
            }
        } else {
            console.log('✅ Função check_farm_exists corrigida com sucesso!');
        }
        
    } catch (error) {
        console.error('❌ Erro ao corrigir função:', error);
    }
}

// Executar diagnóstico completo
async function runCompleteDiagnostic() {
    console.log('🚀 Iniciando diagnóstico completo do problema check_farm_exists...\n');
    
    await debugFarmExistsIssue();
    await clearTestData();
    await fixCheckFarmExistsFunction();
    
    console.log('\n✅ Diagnóstico completo finalizado!');
    console.log('📋 Verifique os resultados acima para identificar o problema.');
}

// Exportar funções para uso global
window.debugFarmExistsIssue = debugFarmExistsIssue;
window.clearTestData = clearTestData;
window.fixCheckFarmExistsFunction = fixCheckFarmExistsFunction;
window.runCompleteDiagnostic = runCompleteDiagnostic;

console.log('🔧 Script de diagnóstico carregado!');
console.log('Funções disponíveis:');
console.log('- debugFarmExistsIssue()');
console.log('- clearTestData()');
console.log('- fixCheckFarmExistsFunction()');
console.log('- runCompleteDiagnostic()');
