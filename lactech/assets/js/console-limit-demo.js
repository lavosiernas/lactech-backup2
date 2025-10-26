/**
 * Console Limit Demo - Lactech
 * Demonstração do sistema de limite de console
 */

// Aguardar o Console Guard carregar
document.addEventListener('DOMContentLoaded', function() {
    // Aguardar um pouco para garantir que o console-guard.js carregou
    setTimeout(() => {
        if (window.consoleGuard) {
            console.log('🎯 Demo do Console Limit iniciado');
            
            // Configurar limites personalizados para demonstração
            window.consoleGuard.setLimits({
                maxRepeats: 3,           // Máximo 3 repetições
                rateLimitWindow: 5000,   // Janela de 5 segundos
                maxLogsPerWindow: 10     // Máximo 10 logs por janela
            });
            
            // Função para testar repetições
            function testRepeats() {
                console.log('🔄 Testando repetições...');
                
                // Fazer o mesmo log várias vezes
                for (let i = 0; i < 10; i++) {
                    console.log('Mensagem repetida:', i);
                }
                
                // Fazer logs similares
                for (let i = 0; i < 5; i++) {
                    console.warn('Aviso similar:', i);
                }
            }
            
            // Função para testar rate limiting
            function testRateLimit() {
                console.log('⚡ Testando rate limiting...');
                
                // Fazer muitos logs rapidamente
                for (let i = 0; i < 20; i++) {
                    console.log('Log rápido:', i);
                }
            }
            
            // Função para mostrar estatísticas
            function showStats() {
                console.log('📊 Estatísticas do console:');
                window.consoleGuard.getLogStats();
            }
            
            // Função para resetar contadores
            function resetCounters() {
                console.log('🔄 Resetando contadores...');
                window.consoleGuard.resetLimits();
            }
            
            // Adicionar botões de teste ao console
            console.log('🎮 Comandos disponíveis:');
            console.log('testRepeats() - Testar repetições');
            console.log('testRateLimit() - Testar rate limiting');
            console.log('showStats() - Mostrar estatísticas');
            console.log('resetCounters() - Resetar contadores');
            
            // Expor funções globalmente para teste
            window.testRepeats = testRepeats;
            window.testRateLimit = testRateLimit;
            window.showStats = showStats;
            window.resetCounters = resetCounters;
            
            // Teste automático após 2 segundos
            setTimeout(() => {
                console.log('🚀 Iniciando teste automático...');
                testRepeats();
                
                setTimeout(() => {
                    testRateLimit();
                    
                    setTimeout(() => {
                        showStats();
                    }, 2000);
                }, 2000);
            }, 2000);
            
        } else {
            console.error('❌ Console Guard não encontrado!');
        }
    }, 1000);
});
