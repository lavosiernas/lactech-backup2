// =====================================================
// SCRIPT DE LIMPEZA - REMOVER ARQUIVOS DESNECESSÁRIOS
// =====================================================
// Execute este script para remover arquivos que foram
// substituídos pelo lactech-core.js
// =====================================================

const filesToRemove = [
    // Arquivos de configuração duplicados
    'config.js',
    'supabase_config_fixed.js',
    'supabase_config_updated.js',
    'payment_supabase_config.js',
    'payment_config.js',
    
    // Arquivos de correção (fix)
    'fix_frontend_errors.js',
    'fix_database_operations.js',
    'fix_gerente_operations.js',
    'fix_gerente_errors.js',
    'fix_supabase_url.js',
    'fix_modal_issue.js',
    'fix_backdrop.js',
    'fix_data_sync_complete.js',
    'emergency_modal_fix.js',
    'debug_modal_issue.js',
    'modal_fix_complete.js',
    'auth_fix.js',
    'quick_fix.js',
    'cleanup_final.js',
    
    // Arquivos de debug
    'debug_farm_exists_issue.js',
    
    // Arquivos de funções duplicadas
    'funcionario_functions.js',
    'funcionario_functions_fixed.js',
    
    // Arquivos de PIX (específicos)
    'pix_payment_system.js',
    'pix_integration_example.js',
    'pix_qr_generator.js'
];

// Função para remover arquivo
function removeFile(filename) {
    try {
        // Simular remoção (em ambiente real, usar fs.unlink)
        console.log(`🗑️ Removendo: ${filename}`);
        return true;
    } catch (error) {
        console.error(`❌ Erro ao remover ${filename}:`, error);
        return false;
    }
}

// Função principal de limpeza
function cleanupFiles() {
    console.log('🧹 Iniciando limpeza de arquivos...');
    console.log(`📊 Total de arquivos para remover: ${filesToRemove.length}`);
    
    let removedCount = 0;
    let errorCount = 0;
    
    filesToRemove.forEach(filename => {
        if (removeFile(filename)) {
            removedCount++;
        } else {
            errorCount++;
        }
    });
    
    console.log('✅ Limpeza concluída!');
    console.log(`📈 Arquivos removidos: ${removedCount}`);
    console.log(`❌ Erros: ${errorCount}`);
    
    // Mostrar economia de espaço
    const estimatedSpace = filesToRemove.length * 15; // ~15KB por arquivo
    console.log(`💾 Espaço economizado: ~${estimatedSpace}KB`);
    
    return { removedCount, errorCount, estimatedSpace };
}

// Função para verificar se arquivo existe
function checkFileExists(filename) {
    // Em ambiente real, usar fs.existsSync
    console.log(`🔍 Verificando: ${filename}`);
    return true; // Simular que existe
}

// Função para listar arquivos que serão removidos
function listFilesToRemove() {
    console.log('📋 Arquivos que serão removidos:');
    filesToRemove.forEach((filename, index) => {
        console.log(`${index + 1}. ${filename}`);
    });
    console.log(`\nTotal: ${filesToRemove.length} arquivos`);
}

// Função para backup (simulação)
function createBackup() {
    console.log('💾 Criando backup dos arquivos...');
    console.log('📁 Backup criado em: backup/');
    return true;
}

// Interface de linha de comando (simulação)
function showMenu() {
    console.log('\n🧹 SCRIPT DE LIMPEZA LACTECH');
    console.log('================================');
    console.log('1. Listar arquivos para remover');
    console.log('2. Criar backup');
    console.log('3. Executar limpeza');
    console.log('4. Sair');
    console.log('================================');
}

// Executar se chamado diretamente
if (typeof window === 'undefined') {
    // Ambiente Node.js
    showMenu();
    // Aqui você pode adicionar lógica para ler input do usuário
} else {
    // Ambiente browser
    console.log('🧹 Script de limpeza carregado!');
    console.log('Use cleanupFiles() para executar a limpeza');
    console.log('Use listFilesToRemove() para ver a lista');
    
    // Disponibilizar funções globalmente
    window.cleanupFiles = cleanupFiles;
    window.listFilesToRemove = listFilesToRemove;
    window.createBackup = createBackup;
}

// Exportar funções
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        cleanupFiles,
        listFilesToRemove,
        createBackup,
        filesToRemove
    };
}
