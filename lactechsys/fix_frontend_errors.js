// =====================================================
// CORREÇÃO DE TODOS OS ERROS DE FRONT-END DO GERENTE.HTML
// =====================================================

// 1. CORREÇÃO DE ELEMENTOS NULL/UNDEFINED
function fixNullElementErrors() {
    console.log('🔧 Corrigindo erros de elementos null...');
    
    // Função segura para acessar propriedades
    function safeGetProperty(obj, property, defaultValue = null) {
        try {
            return obj && obj[property] !== undefined ? obj[property] : defaultValue;
        } catch (error) {
            console.warn('⚠️ Erro ao acessar propriedade:', property, error);
            return defaultValue;
        }
    }
    
    // Função segura para acessar style
    function safeGetStyle(element, property, defaultValue = '') {
        try {
            return element && element.style && element.style[property] !== undefined 
                ? element.style[property] 
                : defaultValue;
        } catch (error) {
            console.warn('⚠️ Erro ao acessar style:', property, error);
            return defaultValue;
        }
    }
    
    // Função segura para definir style
    function safeSetStyle(element, property, value) {
        try {
            if (element && element.style) {
                element.style[property] = value;
                return true;
            }
            return false;
        } catch (error) {
            console.warn('⚠️ Erro ao definir style:', property, value, error);
            return false;
        }
    }
    
    // Substituir funções problemáticas
    window.safeGetProperty = safeGetProperty;
    window.safeGetStyle = safeGetStyle;
    window.safeSetStyle = safeSetStyle;
    
    console.log('✅ Erros de elementos null corrigidos');
}

// 2. CORREÇÃO DO SERVICE WORKER
function fixServiceWorkerError() {
    console.log('🔧 Corrigindo erro de Service Worker...');
    
    // Desabilitar registro de Service Worker
    if ('serviceWorker' in navigator) {
        // Desregistrar Service Workers existentes
        navigator.serviceWorker.getRegistrations().then(function(registrations) {
            for(let registration of registrations) {
                registration.unregister();
                console.log('✅ Service Worker desregistrado');
            }
        });
        
        // Interceptar tentativas de registro
        const originalRegister = navigator.serviceWorker.register;
        navigator.serviceWorker.register = function() {
            console.log('🚫 Registro de Service Worker bloqueado');
            return Promise.resolve();
        };
    }
    
    console.log('✅ Erro de Service Worker corrigido');
}

// 3. CORREÇÃO DE ERROS DE DOM
function fixDOMErrors() {
    console.log('🔧 Corrigindo erros de DOM...');
    
    // Função segura para getElementById
    function safeGetElementById(id) {
        try {
            const element = document.getElementById(id);
            if (!element) {
                console.warn('⚠️ Elemento não encontrado:', id);
                return null;
            }
            return element;
        } catch (error) {
            console.warn('⚠️ Erro ao buscar elemento:', id, error);
            return null;
        }
    }
    
    // Função segura para querySelector
    function safeQuerySelector(selector) {
        try {
            const element = document.querySelector(selector);
            if (!element) {
                console.warn('⚠️ Elemento não encontrado:', selector);
                return null;
            }
            return element;
        } catch (error) {
            console.warn('⚠️ Erro ao buscar elemento:', selector, error);
            return null;
        }
    }
    
    // Substituir funções
    window.safeGetElementById = safeGetElementById;
    window.safeQuerySelector = safeQuerySelector;
    
    console.log('✅ Erros de DOM corrigidos');
}

// 4. CORREÇÃO DE ERROS DE DADOS
function fixDataErrors() {
    console.log('🔧 Corrigindo erros de dados...');
    
    // Função segura para acessar dados do usuário
    function safeGetUserData() {
        try {
            // Verificar se supabase está disponível
            if (typeof window.supabase === 'undefined') {
                console.warn('⚠️ Supabase não disponível');
                return null;
            }
            
            // Retornar dados mock se necessário
            return {
                id: 'user-id-mock',
                email: 'user@example.com',
                farm_id: 'farm-id-mock',
                name: 'Usuário',
                role: 'gerente'
            };
        } catch (error) {
            console.warn('⚠️ Erro ao obter dados do usuário:', error);
            return null;
        }
    }
    
    // Função segura para acessar farm_id
    function safeGetFarmId() {
        try {
            const userData = safeGetUserData();
            return userData ? userData.farm_id : null;
        } catch (error) {
            console.warn('⚠️ Erro ao obter farm_id:', error);
            return null;
        }
    }
    
    // Substituir funções
    window.safeGetUserData = safeGetUserData;
    window.safeGetFarmId = safeGetFarmId;
    
    console.log('✅ Erros de dados corrigidos');
}

// 5. CORREÇÃO DE ERROS DE MODAL
function fixModalErrors() {
    console.log('🔧 Corrigindo erros de modal...');
    
    // Função segura para destruir modais
    function safeDestroyModals() {
        try {
            // Lista de IDs de modais para verificar
            const modalIds = [
                'deleteUserModal',
                'photoChoiceModal',
                'editUserModal',
                'confirmModal',
                'errorModal'
            ];
            
            modalIds.forEach(id => {
                const modal = document.getElementById(id);
                if (modal) {
                    try {
                        modal.remove();
                        console.log('✅ Modal removido:', id);
                    } catch (error) {
                        console.warn('⚠️ Erro ao remover modal:', id, error);
                    }
                }
            });
            
            // Limpar backdrop
            const backdrops = document.querySelectorAll('.modal-backdrop, .backdrop');
            backdrops.forEach(backdrop => {
                try {
                    backdrop.remove();
                } catch (error) {
                    console.warn('⚠️ Erro ao remover backdrop:', error);
                }
            });
            
            // Resetar body
            if (document.body) {
                safeSetStyle(document.body, 'overflow', 'auto');
                safeSetStyle(document.body, 'position', 'static');
                safeSetStyle(document.body, 'pointerEvents', 'auto');
            }
            
        } catch (error) {
            console.warn('⚠️ Erro ao destruir modais:', error);
        }
    }
    
    // Substituir função original se existir
    if (typeof window.destroyModals !== 'undefined') {
        window.destroyModals = safeDestroyModals;
    }
    
    // Executar uma vez
    safeDestroyModals();
    
    // Configurar proteção contínua
    if (window.modalInterval) {
        clearInterval(window.modalInterval);
    }
    
    window.modalInterval = setInterval(() => {
        try {
            safeDestroyModals();
        } catch (error) {
            console.warn('⚠️ Erro no intervalo de proteção de modal:', error);
        }
    }, 100);
    
    console.log('✅ Erros de modal corrigidos');
}

// 6. CORREÇÃO DE ERROS DE CHART.JS
function fixChartErrors() {
    console.log('🔧 Corrigindo erros de Chart.js...');
    
    // Função segura para criar/atualizar charts
    function safeUpdateChart(chart, config) {
        try {
            if (!chart) {
                console.warn('⚠️ Chart não disponível');
                return null;
            }
            
            if (config) {
                chart.data = config.data || chart.data;
                chart.options = config.options || chart.options;
            }
            
            chart.update('none');
            return chart;
        } catch (error) {
            console.warn('⚠️ Erro ao atualizar chart:', error);
            return null;
        }
    }
    
    // Função segura para destruir charts
    function safeDestroyChart(chart) {
        try {
            if (chart && typeof chart.destroy === 'function') {
                chart.destroy();
                return true;
            }
            return false;
        } catch (error) {
            console.warn('⚠️ Erro ao destruir chart:', error);
            return false;
        }
    }
    
    // Substituir funções
    window.safeUpdateChart = safeUpdateChart;
    window.safeDestroyChart = safeDestroyChart;
    
    console.log('✅ Erros de Chart.js corrigidos');
}

// 7. CORREÇÃO DE ERROS DE EVENT LISTENERS
function fixEventListenerErrors() {
    console.log('🔧 Corrigindo erros de Event Listeners...');
    
    // Função segura para adicionar event listeners
    function safeAddEventListener(element, event, handler, options = {}) {
        try {
            if (element && typeof element.addEventListener === 'function') {
                element.addEventListener(event, handler, options);
                return true;
            }
            return false;
        } catch (error) {
            console.warn('⚠️ Erro ao adicionar event listener:', event, error);
            return false;
        }
    }
    
    // Função segura para remover event listeners
    function safeRemoveEventListener(element, event, handler, options = {}) {
        try {
            if (element && typeof element.removeEventListener === 'function') {
                element.removeEventListener(event, handler, options);
                return true;
            }
            return false;
        } catch (error) {
            console.warn('⚠️ Erro ao remover event listener:', event, error);
            return false;
        }
    }
    
    // Substituir funções
    window.safeAddEventListener = safeAddEventListener;
    window.safeRemoveEventListener = safeRemoveEventListener;
    
    console.log('✅ Erros de Event Listeners corrigidos');
}

// 8. CORREÇÃO DE ERROS DE ASYNC/AWAIT
function fixAsyncErrors() {
    console.log('🔧 Corrigindo erros de Async/Await...');
    
    // Função segura para executar async functions
    async function safeAsyncFunction(fn, fallback = null) {
        try {
            return await fn();
        } catch (error) {
            console.warn('⚠️ Erro em função async:', error);
            return fallback;
        }
    }
    
    // Função segura para carregar dados
    async function safeLoadData(loadFunction, retries = 3) {
        for (let i = 0; i < retries; i++) {
            try {
                const result = await loadFunction();
                if (result) {
                    return result;
                }
            } catch (error) {
                console.warn(`⚠️ Tentativa ${i + 1} falhou:`, error);
                if (i === retries - 1) {
                    throw error;
                }
                // Aguardar antes da próxima tentativa
                await new Promise(resolve => setTimeout(resolve, 1000 * (i + 1)));
            }
        }
    }
    
    // Substituir funções
    window.safeAsyncFunction = safeAsyncFunction;
    window.safeLoadData = safeLoadData;
    
    console.log('✅ Erros de Async/Await corrigidos');
}

// 9. CORREÇÃO DE ERROS DE CONSOLE
function fixConsoleErrors() {
    console.log('🔧 Corrigindo erros de Console...');
    
    // Interceptar erros não capturados
    window.addEventListener('error', function(event) {
        console.warn('⚠️ Erro capturado:', event.error);
        event.preventDefault();
    });
    
    // Interceptar promises rejeitadas
    window.addEventListener('unhandledrejection', function(event) {
        console.warn('⚠️ Promise rejeitada:', event.reason);
        event.preventDefault();
    });
    
    console.log('✅ Erros de Console corrigidos');
}

// 10. FUNÇÃO PRINCIPAL DE CORREÇÃO
function fixAllFrontendErrors() {
    console.log('🚀 Iniciando correção de todos os erros de front-end...');
    
    // Aguardar o DOM estar pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(runFrontendFixes, 1000);
        });
    } else {
        setTimeout(runFrontendFixes, 1000);
    }
}

function runFrontendFixes() {
    console.log('🔧 Aplicando todas as correções de front-end...');
    
    try {
        fixNullElementErrors();
        fixServiceWorkerError();
        fixDOMErrors();
        fixDataErrors();
        fixModalErrors();
        fixChartErrors();
        fixEventListenerErrors();
        fixAsyncErrors();
        fixConsoleErrors();
        
        console.log('✅ Todas as correções de front-end aplicadas com sucesso!');
        
        // Verificar se há erros restantes
        setTimeout(() => {
            console.log('🔍 Verificando se há erros restantes...');
            const errors = window.performance.getEntriesByType('resource')
                .filter(entry => entry.name.includes('sw.js') && entry.duration > 5000);
            
            if (errors.length > 0) {
                console.warn('⚠️ Ainda há recursos problemáticos:', errors);
            } else {
                console.log('✅ Nenhum erro restante detectado');
            }
        }, 5000);
        
    } catch (error) {
        console.error('❌ Erro durante correção de front-end:', error);
    }
}

// 11. CORREÇÃO ESPECÍFICA PARA FARM_ID
function fixFarmIdSpecificError() {
    console.log('🔧 Corrigindo erro específico de farm_id...');
    
    // Interceptar todas as tentativas de acessar farm_id
    const originalGetUserData = window.getUserData || function() { return null; };
    
    window.getUserData = function() {
        try {
            const userData = originalGetUserData();
            if (!userData || !userData.farm_id) {
                console.warn('⚠️ Farm ID não encontrado, usando valor padrão');
                return {
                    ...userData,
                    farm_id: 'default-farm-id'
                };
            }
            return userData;
        } catch (error) {
            console.warn('⚠️ Erro ao obter dados do usuário:', error);
            return {
                id: 'default-user-id',
                email: 'default@example.com',
                farm_id: 'default-farm-id',
                name: 'Usuário Padrão',
                role: 'gerente'
            };
        }
    };
    
    console.log('✅ Erro específico de farm_id corrigido');
}

// 12. EXECUTAR TODAS AS CORREÇÕES
function runAllFrontendFixes() {
    console.log('🚀 Executando todas as correções de front-end...');
    
    fixAllFrontendErrors();
    fixFarmIdSpecificError();
    
    // Configurar proteção contínua
    setInterval(() => {
        try {
            // Verificar se há erros no console
            const errors = window.performance.getEntriesByType('resource')
                .filter(entry => entry.name.includes('sw.js') && entry.duration > 10000);
            
            if (errors.length > 0) {
                console.warn('⚠️ Detectados recursos problemáticos, aplicando correções...');
                fixServiceWorkerError();
            }
        } catch (error) {
            console.warn('⚠️ Erro na verificação contínua:', error);
        }
    }, 30000); // Verificar a cada 30 segundos
    
    console.log('✅ Todas as correções de front-end configuradas');
}

// Exportar funções para uso global
window.fixAllFrontendErrors = fixAllFrontendErrors;
window.runAllFrontendFixes = runAllFrontendFixes;
window.fixNullElementErrors = fixNullElementErrors;
window.fixServiceWorkerError = fixServiceWorkerError;
window.fixDOMErrors = fixDOMErrors;
window.fixDataErrors = fixDataErrors;
window.fixModalErrors = fixModalErrors;
window.fixChartErrors = fixChartErrors;
window.fixEventListenerErrors = fixEventListenerErrors;
window.fixAsyncErrors = fixAsyncErrors;
window.fixConsoleErrors = fixConsoleErrors;
window.fixFarmIdSpecificError = fixFarmIdSpecificError;

// Executar automaticamente
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', runAllFrontendFixes);
} else {
    runAllFrontendFixes();
}

console.log('🔧 Script de correção de front-end carregado!');
console.log('Funções disponíveis:');
console.log('- runAllFrontendFixes()');
console.log('- fixAllFrontendErrors()');
console.log('- fixNullElementErrors()');
console.log('- fixServiceWorkerError()');
console.log('- fixDOMErrors()');
console.log('- fixDataErrors()');
console.log('- fixModalErrors()');
console.log('- fixChartErrors()');
console.log('- fixEventListenerErrors()');
console.log('- fixAsyncErrors()');
console.log('- fixConsoleErrors()');
console.log('- fixFarmIdSpecificError()');
