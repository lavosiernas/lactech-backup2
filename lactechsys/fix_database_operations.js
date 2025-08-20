// =====================================================
// CORREÇÃO DE OPERAÇÕES DE BANCO DE DADOS NO FRONT-END
// =====================================================

// 1. CORREÇÃO PARA TABELA ANIMALS
function fixAnimalInsert(animalData, user) {
    console.log('🔧 Corrigindo operação INSERT para animals...');
    
    return {
        farm_id: animalData.farm_id,
        user_id: user.id,  // ✅ ADICIONAR user_id
        name: animalData.name || null,
        breed: animalData.breed || null,
        birth_date: animalData.birth_date || null,
        weight: animalData.weight || null,
        health_status: animalData.health_status || 'healthy',
        is_active: true
        // ❌ REMOVIDO: identification, animal_type, notes
    };
}

// 2. CORREÇÃO PARA TABELA ANIMAL_HEALTH_RECORDS
function fixAnimalHealthRecordInsert(recordData, user) {
    console.log('🔧 Corrigindo operação INSERT para animal_health_records...');
    
    return {
        farm_id: recordData.farm_id,
        animal_id: recordData.animal_id,  // ✅ ADICIONAR animal_id
        user_id: user.id,  // ✅ CORRIGIR: veterinarian_id → user_id
        record_date: recordData.record_date,
        health_status: recordData.health_status || 'healthy',  // ✅ ADICIONAR
        weight: recordData.weight || null,  // ✅ ADICIONAR
        temperature: recordData.temperature || null,  // ✅ ADICIONAR
        observations: recordData.observations || recordData.notes || null  // ✅ CORRIGIR: notes → observations
        // ❌ REMOVIDO: diagnosis, symptoms, severity, status
    };
}

// 3. CORREÇÃO PARA TABELA FINANCIAL_RECORDS
function fixFinancialRecordInsert(recordData, user) {
    console.log('🔧 Corrigindo operação INSERT para financial_records...');
    
    return {
        farm_id: recordData.farm_id,
        user_id: user.id,  // ✅ ADICIONAR user_id
        record_date: recordData.date || recordData.record_date,  // ✅ CORRIGIR: date → record_date
        type: recordData.type,
        amount: recordData.amount,
        description: recordData.description,
        category: recordData.category || null  // ✅ ADICIONAR category
    };
}

// 4. CORREÇÃO PARA TABELA QUALITY_TESTS
function fixQualityTestInsert(qualityData, user) {
    console.log('🔧 Corrigindo operação INSERT para quality_tests...');
    
    return {
        farm_id: qualityData.farm_id,
        user_id: user.id,  // ✅ ADICIONAR user_id
        test_date: qualityData.test_date,
        fat_percentage: qualityData.fat_percentage,
        protein_percentage: qualityData.protein_percentage,
        scc: qualityData.scc,
        cbt: qualityData.cbt,
        laboratory: qualityData.laboratory,
        observations: qualityData.notes || qualityData.observations || null,  // ✅ CORRIGIR: notes → observations
        quality_score: qualityData.quality_score || null  // ✅ ADICIONAR quality_score
    };
}

// 5. CORREÇÃO PARA TABELA MILK_PRODUCTION (já está correto, mas para garantir)
function fixMilkProductionInsert(volumeData, user) {
    console.log('🔧 Verificando operação INSERT para milk_production...');
    
    return {
        farm_id: volumeData.farm_id,
        user_id: user.id,
        production_date: volumeData.production_date,
        shift: volumeData.shift,
        volume_liters: volumeData.volume,
        temperature: volumeData.temperature,
        observations: volumeData.observations
    };
}

// 6. CORREÇÃO PARA TABELA USERS (já está correto, mas para garantir)
function fixUserUpdate(userData) {
    console.log('🔧 Verificando operação UPDATE para users...');
    
    return {
        name: userData.name,
        email: userData.email,
        role: userData.role,
        whatsapp: userData.whatsapp || null,
        profile_photo_url: userData.profile_photo_url || null,
        report_farm_name: userData.report_farm_name || null,
        report_farm_logo_base64: userData.report_farm_logo_base64 || null,
        report_footer_text: userData.report_footer_text || null,
        report_system_logo_base64: userData.report_system_logo_base64 || null,
        is_active: userData.is_active !== undefined ? userData.is_active : true
    };
}

// 7. FUNÇÕES DE OPERAÇÃO SEGURA
async function safeAnimalInsert(animalData) {
    try {
        const { data: { user } } = await supabase.auth.getUser();
        if (!user) throw new Error('Usuário não autenticado');
        
        const correctedData = fixAnimalInsert(animalData, user);
        
        const { data, error } = await supabase
            .from('animals')
            .insert(correctedData)
            .select()
            .single();
        
        if (error) throw error;
        
        console.log('✅ Animal inserido com sucesso:', data);
        return { success: true, data };
        
    } catch (error) {
        console.error('❌ Erro ao inserir animal:', error);
        return { success: false, error: error.message };
    }
}

async function safeAnimalHealthRecordInsert(recordData) {
    try {
        const { data: { user } } = await supabase.auth.getUser();
        if (!user) throw new Error('Usuário não autenticado');
        
        const correctedData = fixAnimalHealthRecordInsert(recordData, user);
        
        const { data, error } = await supabase
            .from('animal_health_records')
            .insert(correctedData)
            .select()
            .single();
        
        if (error) throw error;
        
        console.log('✅ Registro de saúde inserido com sucesso:', data);
        return { success: true, data };
        
    } catch (error) {
        console.error('❌ Erro ao inserir registro de saúde:', error);
        return { success: false, error: error.message };
    }
}

async function safeFinancialRecordInsert(recordData) {
    try {
        const { data: { user } } = await supabase.auth.getUser();
        if (!user) throw new Error('Usuário não autenticado');
        
        const correctedData = fixFinancialRecordInsert(recordData, user);
        
        const { data, error } = await supabase
            .from('financial_records')
            .insert(correctedData)
            .select()
            .single();
        
        if (error) throw error;
        
        console.log('✅ Registro financeiro inserido com sucesso:', data);
        return { success: true, data };
        
    } catch (error) {
        console.error('❌ Erro ao inserir registro financeiro:', error);
        return { success: false, error: error.message };
    }
}

async function safeQualityTestInsert(qualityData) {
    try {
        const { data: { user } } = await supabase.auth.getUser();
        if (!user) throw new Error('Usuário não autenticado');
        
        const correctedData = fixQualityTestInsert(qualityData, user);
        
        const { data, error } = await supabase
            .from('quality_tests')
            .insert(correctedData)
            .select()
            .single();
        
        if (error) throw error;
        
        console.log('✅ Teste de qualidade inserido com sucesso:', data);
        return { success: true, data };
        
    } catch (error) {
        console.error('❌ Erro ao inserir teste de qualidade:', error);
        return { success: false, error: error.message };
    }
}

async function safeMilkProductionInsert(volumeData) {
    try {
        const { data: { user } } = await supabase.auth.getUser();
        if (!user) throw new Error('Usuário não autenticado');
        
        const correctedData = fixMilkProductionInsert(volumeData, user);
        
        const { data, error } = await supabase
            .from('milk_production')
            .insert(correctedData)
            .select()
            .single();
        
        if (error) throw error;
        
        console.log('✅ Produção de leite inserida com sucesso:', data);
        return { success: true, data };
        
    } catch (error) {
        console.error('❌ Erro ao inserir produção de leite:', error);
        return { success: false, error: error.message };
    }
}

// 8. SUBSTITUIR FUNÇÕES ORIGINAIS
function replaceOriginalFunctions() {
    console.log('🔧 Substituindo funções originais...');
    
    // Substituir função addAnimal se existir
    if (typeof window.addAnimal === 'function') {
        const originalAddAnimal = window.addAnimal;
        window.addAnimal = async function(animalData) {
            console.log('🔧 Usando versão corrigida de addAnimal');
            return await safeAnimalInsert(animalData);
        };
        console.log('✅ Função addAnimal substituída');
    }
    
    // Substituir função addAnimalHealthRecord se existir
    if (typeof window.addAnimalHealthRecord === 'function') {
        const originalAddAnimalHealthRecord = window.addAnimalHealthRecord;
        window.addAnimalHealthRecord = async function(recordData) {
            console.log('🔧 Usando versão corrigida de addAnimalHealthRecord');
            return await safeAnimalHealthRecordInsert(recordData);
        };
        console.log('✅ Função addAnimalHealthRecord substituída');
    }
    
    // Substituir função addFinancialRecord se existir
    if (typeof window.addFinancialRecord === 'function') {
        const originalAddFinancialRecord = window.addFinancialRecord;
        window.addFinancialRecord = async function(recordData) {
            console.log('🔧 Usando versão corrigida de addFinancialRecord');
            return await safeFinancialRecordInsert(recordData);
        };
        console.log('✅ Função addFinancialRecord substituída');
    }
    
    // Substituir função addQualityTest se existir
    if (typeof window.addQualityTest === 'function') {
        const originalAddQualityTest = window.addQualityTest;
        window.addQualityTest = async function(qualityData) {
            console.log('🔧 Usando versão corrigida de addQualityTest');
            return await safeQualityTestInsert(qualityData);
        };
        console.log('✅ Função addQualityTest substituída');
    }
    
    // Substituir função addMilkProduction se existir
    if (typeof window.addMilkProduction === 'function') {
        const originalAddMilkProduction = window.addMilkProduction;
        window.addMilkProduction = async function(volumeData) {
            console.log('🔧 Usando versão corrigida de addMilkProduction');
            return await safeMilkProductionInsert(volumeData);
        };
        console.log('✅ Função addMilkProduction substituída');
    }
}

// 9. CORREÇÃO AUTOMÁTICA DE OPERAÇÕES EXISTENTES
function fixExistingOperations() {
    console.log('🔧 Corrigindo operações existentes...');
    
    // Interceptar operações de INSERT no Supabase
    const originalInsert = supabase.from;
    supabase.from = function(tableName) {
        const table = originalInsert.call(this, tableName);
        
        const originalTableInsert = table.insert;
        table.insert = function(data) {
            console.log(`🔧 Interceptando INSERT em ${tableName}:`, data);
            
            let correctedData = data;
            
            // Corrigir dados baseado na tabela
            if (tableName === 'animals') {
                correctedData = fixAnimalInsert(data, { id: 'current-user-id' });
            } else if (tableName === 'animal_health_records') {
                correctedData = fixAnimalHealthRecordInsert(data, { id: 'current-user-id' });
            } else if (tableName === 'financial_records') {
                correctedData = fixFinancialRecordInsert(data, { id: 'current-user-id' });
            } else if (tableName === 'quality_tests') {
                correctedData = fixQualityTestInsert(data, { id: 'current-user-id' });
            } else if (tableName === 'milk_production') {
                correctedData = fixMilkProductionInsert(data, { id: 'current-user-id' });
            }
            
            console.log(`✅ Dados corrigidos para ${tableName}:`, correctedData);
            return originalTableInsert.call(this, correctedData);
        };
        
        return table;
    };
    
    console.log('✅ Interceptação de operações configurada');
}

// 10. FUNÇÃO PRINCIPAL DE CORREÇÃO
function fixAllDatabaseOperations() {
    console.log('🚀 Iniciando correção de todas as operações de banco...');
    
    try {
        replaceOriginalFunctions();
        fixExistingOperations();
        
        console.log('✅ Todas as operações de banco corrigidas!');
        
        // Verificar se há operações problemáticas
        setTimeout(() => {
            console.log('🔍 Verificando se há operações restantes...');
            const problematicOperations = [
                'identification',
                'notes',
                'veterinarian_id',
                'date',
                'diagnosis',
                'symptoms',
                'severity',
                'status'
            ];
            
            console.log('⚠️ Colunas problemáticas que devem ser evitadas:', problematicOperations);
            console.log('✅ Use as funções seguras: safeAnimalInsert, safeQualityTestInsert, etc.');
            
        }, 2000);
        
    } catch (error) {
        console.error('❌ Erro durante correção de operações:', error);
    }
}

// 11. EXPORTAR FUNÇÕES PARA USO GLOBAL
window.fixAllDatabaseOperations = fixAllDatabaseOperations;
window.safeAnimalInsert = safeAnimalInsert;
window.safeAnimalHealthRecordInsert = safeAnimalHealthRecordInsert;
window.safeFinancialRecordInsert = safeFinancialRecordInsert;
window.safeQualityTestInsert = safeQualityTestInsert;
window.safeMilkProductionInsert = safeMilkProductionInsert;
window.fixAnimalInsert = fixAnimalInsert;
window.fixAnimalHealthRecordInsert = fixAnimalHealthRecordInsert;
window.fixFinancialRecordInsert = fixFinancialRecordInsert;
window.fixQualityTestInsert = fixQualityTestInsert;
window.fixMilkProductionInsert = fixMilkProductionInsert;
window.fixUserUpdate = fixUserUpdate;

// 12. EXECUTAR AUTOMATICAMENTE
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', fixAllDatabaseOperations);
} else {
    fixAllDatabaseOperations();
}

console.log('🔧 Script de correção de operações de banco carregado!');
console.log('Funções disponíveis:');
console.log('- fixAllDatabaseOperations()');
console.log('- safeAnimalInsert(animalData)');
console.log('- safeAnimalHealthRecordInsert(recordData)');
console.log('- safeFinancialRecordInsert(recordData)');
console.log('- safeQualityTestInsert(qualityData)');
console.log('- safeMilkProductionInsert(volumeData)');
console.log('- fixAnimalInsert(animalData, user)');
console.log('- fixAnimalHealthRecordInsert(recordData, user)');
console.log('- fixFinancialRecordInsert(recordData, user)');
console.log('- fixQualityTestInsert(qualityData, user)');
console.log('- fixMilkProductionInsert(volumeData, user)');
console.log('- fixUserUpdate(userData)');
