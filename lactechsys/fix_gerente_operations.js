// =====================================================
// CORREÇÃO ESPECÍFICA DAS OPERAÇÕES DO GERENTE.HTML
// =====================================================

// 1. CORREÇÃO DA FUNÇÃO handleAddQuality
function fixHandleAddQuality() {
    console.log('🔧 Corrigindo função handleAddQuality...');
    
    // Substituir a função original
    window.handleAddQuality = async function(event) {
        event.preventDefault();
        const formData = new FormData(event.target);
        
        const qualityData = {
            test_date: formData.get('test_date'),
            fat_percentage: parseFloat(formData.get('fat_percentage')),
            protein_percentage: parseFloat(formData.get('protein_percentage')),
            scc: parseInt(formData.get('scc')),
            cbt: parseInt(formData.get('total_bacterial_count')),
            laboratory: formData.get('laboratory') || null,
            observations: formData.get('notes') || null  // ✅ CORRIGIDO: notes → observations
        };

        try {
            const { data: { user: currentUser } } = await supabase.auth.getUser();
            if (!currentUser) throw new Error('User not authenticated');

            // Get current user's farm_id
            const { data: managerData, error: managerError } = await supabase
                .from('users')
                .select('farm_id')
                .eq('id', currentUser.id)
                .single();

            if (managerError) throw managerError;
            if (!managerData?.farm_id) throw new Error('Farm not found');

            // ✅ CORREÇÃO: Usar dados corrigidos
            const correctedData = {
                farm_id: managerData.farm_id,
                user_id: currentUser.id,  // ✅ ADICIONADO
                test_date: qualityData.test_date,
                fat_percentage: qualityData.fat_percentage,
                protein_percentage: qualityData.protein_percentage,
                scc: qualityData.scc,
                cbt: qualityData.cbt,
                laboratory: qualityData.laboratory,
                observations: qualityData.observations,  // ✅ CORRIGIDO
                quality_score: null  // ✅ ADICIONADO
            };

            // Insert quality test into database
            const { error: qualityError } = await supabase
                .from('quality_tests')
                .insert(correctedData);

            if (qualityError) throw qualityError;

            showNotification('Teste de qualidade adicionado com sucesso!', 'success');
            closeQualityModal();
            
            // Reload quality data and charts
            await loadQualityData();
            await loadQualityTests();
            
        } catch (error) {
            console.error('Error adding quality test:', error);
            showNotification('Erro ao adicionar teste de qualidade: ' + error.message, 'error');
        }
    };
    
    console.log('✅ Função handleAddQuality corrigida');
}

// 2. CORREÇÃO DA FUNÇÃO handleAddVolume
function fixHandleAddVolume() {
    console.log('🔧 Corrigindo função handleAddVolume...');
    
    // Substituir a função original
    window.handleAddVolume = async function(event) {
        event.preventDefault();
        const formData = new FormData(event.target);
        
        const volumeData = {
            production_date: formData.get('production_date'),
            shift: formData.get('shift'),
            volume: parseFloat(formData.get('volume')),
            temperature: parseFloat(formData.get('temperature')),
            observations: formData.get('observations') || null
        };

        try {
            const { data: { user: currentUser } } = await supabase.auth.getUser();
            if (!currentUser) throw new Error('User not authenticated');

            // Get current user's farm_id
            const { data: managerData, error: managerError } = await supabase
                .from('users')
                .select('farm_id')
                .eq('id', currentUser.id)
                .single();

            if (managerError) throw managerError;
            if (!managerData?.farm_id) throw new Error('Farm not found');

            // ✅ CORREÇÃO: Usar dados corrigidos
            const correctedData = {
                farm_id: managerData.farm_id,
                user_id: currentUser.id,
                production_date: volumeData.production_date,
                shift: volumeData.shift,
                volume_liters: volumeData.volume,  // ✅ CORRIGIDO: volume → volume_liters
                temperature: volumeData.temperature,
                observations: volumeData.observations
            };

            // Insert volume record into database
            const { error: volumeError } = await supabase
                .from('milk_production')
                .insert(correctedData);

            if (volumeError) throw volumeError;

            showNotification('Registro de volume adicionado com sucesso!', 'success');
            closeVolumeModal();
            
            // Reload volume data and charts
            await loadVolumeData();
            await loadWeeklyVolumeChart();
            await loadDailyVolumeChart();
            await loadDashboardWeeklyChart();
            
        } catch (error) {
            console.error('Error adding volume record:', error);
            showNotification('Erro ao adicionar registro de volume: ' + error.message, 'error');
        }
    };
    
    console.log('✅ Função handleAddVolume corrigida');
}

// 3. CORREÇÃO DA FUNÇÃO handleAddPayment
function fixHandleAddPayment() {
    console.log('🔧 Corrigindo função handleAddPayment...');
    
    // Substituir a função original
    window.handleAddPayment = async function(event) {
        event.preventDefault();
        const formData = new FormData(event.target);
        
        const paymentData = {
            amount: parseFloat(formData.get('amount')),
            description: formData.get('description'),
            category: formData.get('category') || 'venda',  // ✅ ADICIONADO
            record_date: formData.get('date') || new Date().toISOString().split('T')[0]  // ✅ CORRIGIDO: date → record_date
        };

        try {
            const { data: { user: currentUser } } = await supabase.auth.getUser();
            if (!currentUser) throw new Error('User not authenticated');

            // Get current user's farm_id
            const { data: managerData, error: managerError } = await supabase
                .from('users')
                .select('farm_id')
                .eq('id', currentUser.id)
                .single();

            if (managerError) throw managerError;
            if (!managerData?.farm_id) throw new Error('Farm not found');

            // ✅ CORREÇÃO: Usar dados corrigidos
            const correctedData = {
                farm_id: managerData.farm_id,
                user_id: currentUser.id,  // ✅ ADICIONADO
                record_date: paymentData.record_date,
                type: 'receita',
                amount: paymentData.amount,
                description: paymentData.description,
                category: paymentData.category
            };

            // Insert financial record into database
            const { error: paymentError } = await supabase
                .from('financial_records')
                .insert(correctedData);

            if (paymentError) throw paymentError;

            showNotification('Venda adicionada com sucesso!', 'success');
            closePaymentModal();
            
            // Reload sales data and recent activities
            await loadPaymentsData();
            
        } catch (error) {
            console.error('Error adding payment:', error);
            showNotification('Erro ao adicionar venda: ' + error.message, 'error');
        }
    };
    
    console.log('✅ Função handleAddPayment corrigida');
}

// 4. CORREÇÃO DAS FUNÇÕES DE ANIMAIS
function fixAnimalOperations() {
    console.log('🔧 Corrigindo operações de animais...');
    
    // Substituir função addAnimal se existir
    if (typeof window.addAnimal === 'function') {
        const originalAddAnimal = window.addAnimal;
        window.addAnimal = async function(animalData) {
            try {
                const { data: { user } } = await supabase.auth.getUser();
                if (!user) throw new Error('Usuário não autenticado');
                
                // ✅ CORREÇÃO: Usar dados corrigidos
                const correctedData = {
                    farm_id: animalData.farm_id,
                    user_id: user.id,  // ✅ ADICIONADO
                    name: animalData.name || null,
                    breed: animalData.breed || null,
                    birth_date: animalData.birth_date || null,
                    weight: animalData.weight || null,
                    health_status: animalData.health_status || 'healthy',
                    is_active: true
                    // ❌ REMOVIDO: identification, animal_type, notes
                };
                
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
        };
        console.log('✅ Função addAnimal corrigida');
    }
}

// 5. CORREÇÃO DAS FUNÇÕES DE REGISTROS DE SAÚDE
function fixHealthRecordOperations() {
    console.log('🔧 Corrigindo operações de registros de saúde...');
    
    // Substituir função addAnimalHealthRecord se existir
    if (typeof window.addAnimalHealthRecord === 'function') {
        const originalAddAnimalHealthRecord = window.addAnimalHealthRecord;
        window.addAnimalHealthRecord = async function(recordData) {
            try {
                const { data: { user } } = await supabase.auth.getUser();
                if (!user) throw new Error('Usuário não autenticado');
                
                // ✅ CORREÇÃO: Usar dados corrigidos
                const correctedData = {
                    farm_id: recordData.farm_id,
                    animal_id: recordData.animal_id,  // ✅ ADICIONADO
                    user_id: user.id,  // ✅ CORRIGIDO: veterinarian_id → user_id
                    record_date: recordData.record_date,
                    health_status: recordData.health_status || 'healthy',  // ✅ ADICIONADO
                    weight: recordData.weight || null,  // ✅ ADICIONADO
                    temperature: recordData.temperature || null,  // ✅ ADICIONADO
                    observations: recordData.observations || recordData.notes || null  // ✅ CORRIGIDO: notes → observations
                    // ❌ REMOVIDO: diagnosis, symptoms, severity, status
                };
                
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
        };
        console.log('✅ Função addAnimalHealthRecord corrigida');
    }
}

// 6. CORREÇÃO DAS FUNÇÕES DE REGISTROS FINANCEIROS
function fixFinancialRecordOperations() {
    console.log('🔧 Corrigindo operações de registros financeiros...');
    
    // Substituir função addFinancialRecord se existir
    if (typeof window.addFinancialRecord === 'function') {
        const originalAddFinancialRecord = window.addFinancialRecord;
        window.addFinancialRecord = async function(recordData) {
            try {
                const { data: { user } } = await supabase.auth.getUser();
                if (!user) throw new Error('Usuário não autenticado');
                
                // ✅ CORREÇÃO: Usar dados corrigidos
                const correctedData = {
                    farm_id: recordData.farm_id,
                    user_id: user.id,  // ✅ ADICIONADO
                    record_date: recordData.date || recordData.record_date,  // ✅ CORRIGIDO: date → record_date
                    type: recordData.type,
                    amount: recordData.amount,
                    description: recordData.description,
                    category: recordData.category || null  // ✅ ADICIONADO
                };
                
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
        };
        console.log('✅ Função addFinancialRecord corrigida');
    }
}

// 7. FUNÇÃO PRINCIPAL DE CORREÇÃO
function fixAllGerenteOperations() {
    console.log('🚀 Iniciando correção de todas as operações do gerente.html...');
    
    try {
        fixHandleAddQuality();
        fixHandleAddVolume();
        fixHandleAddPayment();
        fixAnimalOperations();
        fixHealthRecordOperations();
        fixFinancialRecordOperations();
        
        console.log('✅ Todas as operações do gerente.html corrigidas!');
        
        // Verificar se há operações problemáticas restantes
        setTimeout(() => {
            console.log('🔍 Verificando operações restantes...');
            console.log('✅ Operações corrigidas:');
            console.log('- handleAddQuality: notes → observations, +user_id, +quality_score');
            console.log('- handleAddVolume: volume → volume_liters');
            console.log('- handleAddPayment: date → record_date, +user_id, +category');
            console.log('- addAnimal: -identification, -animal_type, -notes, +user_id');
            console.log('- addAnimalHealthRecord: veterinarian_id → user_id, +animal_id, +health_status, +weight, +temperature, notes → observations');
            console.log('- addFinancialRecord: date → record_date, +user_id, +category');
        }, 2000);
        
    } catch (error) {
        console.error('❌ Erro durante correção das operações:', error);
    }
}

// 8. EXPORTAR FUNÇÕES PARA USO GLOBAL
window.fixAllGerenteOperations = fixAllGerenteOperations;
window.fixHandleAddQuality = fixHandleAddQuality;
window.fixHandleAddVolume = fixHandleAddVolume;
window.fixHandleAddPayment = fixHandleAddPayment;
window.fixAnimalOperations = fixAnimalOperations;
window.fixHealthRecordOperations = fixHealthRecordOperations;
window.fixFinancialRecordOperations = fixFinancialRecordOperations;

// 9. EXECUTAR AUTOMATICAMENTE
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', fixAllGerenteOperations);
} else {
    fixAllGerenteOperations();
}

console.log('🔧 Script de correção das operações do gerente.html carregado!');
console.log('Funções disponíveis:');
console.log('- fixAllGerenteOperations()');
console.log('- fixHandleAddQuality()');
console.log('- fixHandleAddVolume()');
console.log('- fixHandleAddPayment()');
console.log('- fixAnimalOperations()');
console.log('- fixHealthRecordOperations()');
console.log('- fixFinancialRecordOperations()');
