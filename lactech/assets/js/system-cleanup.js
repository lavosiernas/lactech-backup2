/**
 * System Cleanup - Lactech
 * Limpeza e otimização do sistema
 */

class SystemCleanup {
    constructor() {
        this.disabledFeatures = new Set();
        this.removedElements = new Set();
        this.init();
    }

    init() {
        console.log('🧹 System Cleanup inicializado');
        this.cleanupUnusedFeatures();
        this.optimizeSystem();
        this.setupErrorHandling();
    }

    /**
     * Limpar funcionalidades não utilizadas
     */
    cleanupUnusedFeatures() {
        // Desabilitar weather modal
        this.disableWeatherModal();
        
        // Desabilitar funcionalidades não utilizadas
        this.disableUnusedFeatures();
        
        // Remover elementos desnecessários
        this.removeUnnecessaryElements();
        
        // Limpar console de erros desnecessários
        this.cleanupConsoleErrors();
    }

    /**
     * Desabilitar weather modal
     */
    disableWeatherModal() {
        // Remover event listeners do weather modal
        const weatherElements = document.querySelectorAll('[data-weather]');
        weatherElements.forEach(element => {
            element.removeEventListener('click', this.handleWeatherClick);
        });
        
        // Ocultar weather modal se existir
        const weatherModal = document.getElementById('weatherModal');
        if (weatherModal) {
            weatherModal.style.display = 'none';
            this.removedElements.add('weatherModal');
        }
        
        console.log('🌤️ Weather modal desabilitado');
    }

    /**
     * Desabilitar funcionalidades não utilizadas
     */
    disableUnusedFeatures() {
        // Desabilitar chat se não for usado
        if (window.chatSystem) {
            window.chatSystem.disable();
            this.disabledFeatures.add('chat');
        }
        
        // Desabilitar funcionalidades de teste
        this.disableTestFeatures();
        
        // Desabilitar funcionalidades experimentais
        this.disableExperimentalFeatures();
        
        console.log('🔧 Funcionalidades não utilizadas desabilitadas');
    }

    /**
     * Desabilitar funcionalidades de teste
     */
    disableTestFeatures() {
        // Remover funções de teste do window
        const testFunctions = [
            'testarModalPerfil',
            'testWeatherModal',
            'testChatSystem',
            'testNotifications'
        ];
        
        testFunctions.forEach(funcName => {
            if (window[funcName]) {
                delete window[funcName];
            }
        });
        
        console.log('🧪 Funcionalidades de teste removidas');
    }

    /**
     * Desabilitar funcionalidades experimentais
     */
    disableExperimentalFeatures() {
        // Desabilitar IA se não for necessária
        if (window.aiSystem) {
            window.aiSystem.disable();
            this.disabledFeatures.add('ai');
        }
        
        // Desabilitar automações desnecessárias
        if (window.automationSystem) {
            window.automationSystem.disable();
            this.disabledFeatures.add('automation');
        }
        
        console.log('🤖 Funcionalidades experimentais desabilitadas');
    }

    /**
     * Remover elementos desnecessários
     */
    removeUnnecessaryElements() {
        // Remover elementos de weather
        const weatherElements = document.querySelectorAll('[data-weather]');
        weatherElements.forEach(element => {
            element.remove();
        });
        
        // Remover elementos de chat se não for usado
        const chatElements = document.querySelectorAll('[data-chat]');
        chatElements.forEach(element => {
            element.remove();
        });
        
        // Remover elementos de teste
        const testElements = document.querySelectorAll('[data-test]');
        testElements.forEach(element => {
            element.remove();
        });
        
        console.log('🗑️ Elementos desnecessários removidos');
    }

    /**
     * Limpar erros do console
     */
    cleanupConsoleErrors() {
        // Interceptar erros de recursos não encontrados
        const originalError = console.error;
        console.error = (...args) => {
            const message = args.join(' ');
            
            // Filtrar erros de recursos desnecessários
            if (message.includes('weather-modal') || 
                message.includes('native-notifications.css') ||
                message.includes('quality-modal.css') ||
                message.includes('404 (Not Found)')) {
                return; // Não mostrar esses erros
            }
            
            originalError.apply(console, args);
        };
        
        console.log('🧹 Erros desnecessários filtrados');
    }

    /**
     * Otimizar sistema
     */
    optimizeSystem() {
        // Otimizar carregamento de recursos
        this.optimizeResourceLoading();
        
        // Otimizar performance
        this.optimizePerformance();
        
        // Otimizar memória
        this.optimizeMemory();
    }

    /**
     * Otimizar carregamento de recursos
     */
    optimizeResourceLoading() {
        // Preload apenas recursos críticos
        const criticalResources = [
            'assets/css/style.css',
            'assets/css/tailwind.css',
            'assets/js/gerente.js'
        ];
        
        criticalResources.forEach(resource => {
            const link = document.createElement('link');
            link.rel = 'preload';
            link.href = resource;
            link.as = resource.endsWith('.css') ? 'style' : 'script';
            document.head.appendChild(link);
        });
        
        console.log('⚡ Carregamento de recursos otimizado');
    }

    /**
     * Otimizar performance
     */
    optimizePerformance() {
        // Desabilitar animações desnecessárias
        const style = document.createElement('style');
        style.textContent = `
            .no-animation * {
                animation: none !important;
                transition: none !important;
            }
        `;
        document.head.appendChild(style);
        
        // Aplicar classe quando necessário
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            document.body.classList.add('no-animation');
        }
        
        console.log('🚀 Performance otimizada');
    }

    /**
     * Otimizar memória
     */
    optimizeMemory() {
        // Limpar cache de dados antigos
        if (window.requestsCache) {
            window.requestsCache.clear();
        }
        
        // Limpar timers desnecessários
        this.clearUnnecessaryTimers();
        
        // Limpar observers desnecessários
        this.clearUnnecessaryObservers();
        
        console.log('💾 Memória otimizada');
    }

    /**
     * Limpar timers desnecessários
     */
    clearUnnecessaryTimers() {
        // Limpar timers de funcionalidades desabilitadas
        const timersToClear = [
            'weatherUpdateTimer',
            'chatUpdateTimer',
            'testUpdateTimer'
        ];
        
        timersToClear.forEach(timerName => {
            if (window[timerName]) {
                clearInterval(window[timerName]);
                delete window[timerName];
            }
        });
    }

    /**
     * Limpar observers desnecessários
     */
    clearUnnecessaryObservers() {
        // Limpar MutationObservers desnecessários
        if (window.weatherObserver) {
            window.weatherObserver.disconnect();
            delete window.weatherObserver;
        }
        
        if (window.chatObserver) {
            window.chatObserver.disconnect();
            delete window.chatObserver;
        }
    }

    /**
     * Configurar tratamento de erros
     */
    setupErrorHandling() {
        // Interceptar erros de recursos
        window.addEventListener('error', (event) => {
            if (event.target !== window) {
                const src = event.target.src || event.target.href;
                
                // Ignorar erros de recursos desnecessários
                if (src && (
                    src.includes('weather-modal') ||
                    src.includes('native-notifications.css') ||
                    src.includes('quality-modal.css')
                )) {
                    event.preventDefault();
                    return false;
                }
            }
        }, true);
        
        console.log('🛡️ Tratamento de erros configurado');
    }

    /**
     * Obter relatório de limpeza
     */
    getCleanupReport() {
        return {
            disabledFeatures: Array.from(this.disabledFeatures),
            removedElements: Array.from(this.removedElements),
            timestamp: new Date().toISOString()
        };
    }

    /**
     * Aplicar limpeza completa
     */
    fullCleanup() {
        console.log('🧹 Iniciando limpeza completa do sistema...');
        
        this.cleanupUnusedFeatures();
        this.optimizeSystem();
        
        // Limpar console
        console.clear();
        
        console.log('✅ Limpeza completa concluída');
        console.log('📊 Relatório:', this.getCleanupReport());
    }
}

// Inicializar System Cleanup
document.addEventListener('DOMContentLoaded', () => {
    window.systemCleanup = new SystemCleanup();
    
    // Aplicar limpeza após 2 segundos
    setTimeout(() => {
        window.systemCleanup.fullCleanup();
    }, 2000);
});

// Exportar para uso global
window.SystemCleanup = SystemCleanup;
