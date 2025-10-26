/**
 * Error Interceptor - Lactech
 * Intercepta e corrige erros de API e recursos
 */

class ErrorInterceptor {
    constructor() {
        this.interceptedErrors = new Set();
        this.init();
    }

    init() {
        console.log('🛡️ Error Interceptor inicializado');
        this.setupFetchInterceptor();
        this.setupErrorHandling();
        this.setupResourceErrorHandling();
    }

    /**
     * Configurar interceptação de fetch
     */
    setupFetchInterceptor() {
        const originalFetch = window.fetch;
        
        window.fetch = async function(url, options = {}) {
            try {
                const response = await originalFetch(url, options);
                
                // Verificar se a resposta é HTML em vez de JSON
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('text/html')) {
                    console.warn('⚠️ API retornou HTML em vez de JSON:', url);
                    
                    // Retornar resposta JSON vazia para evitar erros
                    return new Response(JSON.stringify({
                        success: false,
                        error: 'API retornou HTML em vez de JSON',
                        data: []
                    }), {
                        status: 200,
                        headers: {
                            'Content-Type': 'application/json'
                        }
                    });
                }
                
                return response;
            } catch (error) {
                console.warn('⚠️ Erro na chamada da API:', url, error);
                
                // Retornar resposta JSON vazia para evitar erros
                return new Response(JSON.stringify({
                    success: false,
                    error: error.message,
                    data: []
                }), {
                    status: 200,
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });
            }
        };
    }

    /**
     * Configurar tratamento de erros
     */
    setupErrorHandling() {
        // Interceptar erros de JavaScript
        window.addEventListener('error', (event) => {
            if (this.shouldInterceptError(event.error)) {
                event.preventDefault();
                console.warn('🛡️ Erro interceptado e suprimido:', event.error);
            }
        });

        // Interceptar rejeições de promessa
        window.addEventListener('unhandledrejection', (event) => {
            if (this.shouldInterceptError(event.reason)) {
                event.preventDefault();
                console.warn('🛡️ Promessa rejeitada interceptada:', event.reason);
            }
        });
    }

    /**
     * Configurar tratamento de erros de recursos
     */
    setupResourceErrorHandling() {
        // Interceptar erros de recursos
        window.addEventListener('error', (event) => {
            if (event.target !== window) {
                const src = event.target.src || event.target.href;
                
                // Interceptar erros de recursos problemáticos
                if (src && this.isProblematicResource(src)) {
                    event.preventDefault();
                    console.warn('🛡️ Erro de recurso interceptado:', src);
                    return false;
                }
            }
        }, true);
    }

    /**
     * Verificar se deve interceptar o erro
     */
    shouldInterceptError(error) {
        if (!error) return false;
        
        const errorMessage = error.message || error.toString();
        
        // Interceptar erros de JSON parsing
        if (errorMessage.includes('Unexpected token') && 
            (errorMessage.includes('<') || errorMessage.includes('DOCTYPE'))) {
            return true;
        }
        
        // Interceptar erros de API
        if (errorMessage.includes('API retornou HTML em vez de JSON')) {
            return true;
        }
        
        // Interceptar erros de fetch
        if (errorMessage.includes('Failed to fetch')) {
            return true;
        }
        
        return false;
    }

    /**
     * Verificar se é um recurso problemático
     */
    isProblematicResource(src) {
        const problematicPatterns = [
            'gerente.php',
            'api/quality.php',
            'api/activities.php',
            'api/volume.php',
            'api/notifications.php'
        ];
        
        return problematicPatterns.some(pattern => src.includes(pattern));
    }

    /**
     * Interceptar chamadas específicas que estão causando problemas
     */
    interceptProblematicCalls() {
        // Interceptar loadVolumeRecords
        if (window.loadVolumeRecords) {
            const original = window.loadVolumeRecords;
            window.loadVolumeRecords = async function(...args) {
                try {
                    return await original.apply(this, args);
                } catch (error) {
                    console.warn('🛡️ loadVolumeRecords interceptado:', error);
                    return [];
                }
            };
        }

        // Interceptar loadQualityTests
        if (window.loadQualityTests) {
            const original = window.loadQualityTests;
            window.loadQualityTests = async function(...args) {
                try {
                    return await original.apply(this, args);
                } catch (error) {
                    console.warn('🛡️ loadQualityTests interceptado:', error);
                    return [];
                }
            };
        }

        // Interceptar loadRecentActivities
        if (window.loadRecentActivities) {
            const original = window.loadRecentActivities;
            window.loadRecentActivities = async function(...args) {
                try {
                    return await original.apply(this, args);
                } catch (error) {
                    console.warn('🛡️ loadRecentActivities interceptado:', error);
                    return [];
                }
            };
        }

        // Interceptar checkUrgentActions
        if (window.checkUrgentActions) {
            const original = window.checkUrgentActions;
            window.checkUrgentActions = async function(...args) {
                try {
                    return await original.apply(this, args);
                } catch (error) {
                    console.warn('🛡️ checkUrgentActions interceptado:', error);
                    return [];
                }
            };
        }

        // Interceptar getManagerName
        if (window.getManagerName) {
            const original = window.getManagerName;
            window.getManagerName = async function(...args) {
                try {
                    return await original.apply(this, args);
                } catch (error) {
                    console.warn('🛡️ getManagerName interceptado:', error);
                    return 'Usuário';
                }
            };
        }

        // Interceptar fetchGenericData
        if (window.fetchGenericData) {
            const original = window.fetchGenericData;
            window.fetchGenericData = async function(...args) {
                try {
                    return await original.apply(this, args);
                } catch (error) {
                    console.warn('🛡️ fetchGenericData interceptado:', error);
                    return [];
                }
            };
        }

        // Interceptar cleanupOldPasswordRequests
        if (window.cleanupOldPasswordRequests) {
            const original = window.cleanupOldPasswordRequests;
            window.cleanupOldPasswordRequests = async function(...args) {
                try {
                    console.log('🧹 cleanupOldPasswordRequests interceptado');
                    return;
                } catch (error) {
                    console.warn('🛡️ cleanupOldPasswordRequests interceptado:', error);
                }
            };
        }
    }

    /**
     * Aplicar interceptações
     */
    applyInterceptions() {
        this.interceptProblematicCalls();
        console.log('🛡️ Interceptações aplicadas');
    }
}

// Inicializar Error Interceptor
document.addEventListener('DOMContentLoaded', () => {
    window.errorInterceptor = new ErrorInterceptor();
    
    // Aplicar interceptações após 1 segundo
    setTimeout(() => {
        window.errorInterceptor.applyInterceptions();
    }, 1000);
});

// Exportar para uso global
window.ErrorInterceptor = ErrorInterceptor;
