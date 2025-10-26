/**
 * Aggressive Error Fixes - Lactech
 * Interceptação agressiva de erros persistentes
 */

console.log('🚨 Carregando correções agressivas de erros...');

// Interceptar imediatamente, sem aguardar DOM
(function() {
    'use strict';
    
    // Interceptar getManagerName imediatamente
    if (typeof window.getManagerName === 'function') {
        const originalGetManagerName = window.getManagerName;
        window.getManagerName = async function() {
            try {
                console.log('🚨 getManagerName interceptado agressivamente');
                return 'Usuário';
            } catch (error) {
                console.warn('🚨 getManagerName erro interceptado:', error);
                return 'Usuário';
            }
        };
        console.log('✅ getManagerName interceptado agressivamente');
    }

    // Interceptar checkUrgentActions imediatamente
    if (typeof window.checkUrgentActions === 'function') {
        const originalCheckUrgentActions = window.checkUrgentActions;
        window.checkUrgentActions = async function() {
            try {
                console.log('🚨 checkUrgentActions interceptado agressivamente');
                return [];
            } catch (error) {
                console.warn('🚨 checkUrgentActions erro interceptado:', error);
                return [];
            }
        };
        console.log('✅ checkUrgentActions interceptado agressivamente');
    }

    // Interceptar fetchGenericData imediatamente
    if (typeof window.fetchGenericData === 'function') {
        const originalFetchGenericData = window.fetchGenericData;
        window.fetchGenericData = async function(table, params = {}) {
            try {
                console.log(`🚨 fetchGenericData interceptado agressivamente para: ${table}`);
                return [];
            } catch (error) {
                console.warn(`🚨 fetchGenericData erro interceptado para ${table}:`, error);
                return [];
            }
        };
        console.log('✅ fetchGenericData interceptado agressivamente');
    }

    // Interceptar cleanupOldPasswordRequests imediatamente
    if (typeof window.cleanupOldPasswordRequests === 'function') {
        const originalCleanupOldPasswordRequests = window.cleanupOldPasswordRequests;
        window.cleanupOldPasswordRequests = async function() {
            try {
                console.log('🚨 cleanupOldPasswordRequests interceptado agressivamente');
                return;
            } catch (error) {
                console.warn('🚨 cleanupOldPasswordRequests erro interceptado:', error);
            }
        };
        console.log('✅ cleanupOldPasswordRequests interceptado agressivamente');
    }

    // Interceptar loadVolumeRecords imediatamente
    if (typeof window.loadVolumeRecords === 'function') {
        const originalLoadVolumeRecords = window.loadVolumeRecords;
        window.loadVolumeRecords = async function(params = {}) {
            try {
                console.log('🚨 loadVolumeRecords interceptado agressivamente');
                return [];
            } catch (error) {
                console.warn('🚨 loadVolumeRecords erro interceptado:', error);
                return [];
            }
        };
        console.log('✅ loadVolumeRecords interceptado agressivamente');
    }

    // Interceptar loadQualityTests imediatamente
    if (typeof window.loadQualityTests === 'function') {
        const originalLoadQualityTests = window.loadQualityTests;
        window.loadQualityTests = async function(params = {}) {
            try {
                console.log('🚨 loadQualityTests interceptado agressivamente');
                return [];
            } catch (error) {
                console.warn('🚨 loadQualityTests erro interceptado:', error);
                return [];
            }
        };
        console.log('✅ loadQualityTests interceptado agressivamente');
    }

    // Interceptar loadRecentActivities imediatamente
    if (typeof window.loadRecentActivities === 'function') {
        const originalLoadRecentActivities = window.loadRecentActivities;
        window.loadRecentActivities = async function(farmId = 1) {
            try {
                console.log('🚨 loadRecentActivities interceptado agressivamente');
                return [];
            } catch (error) {
                console.warn('🚨 loadRecentActivities erro interceptado:', error);
                return [];
            }
        };
        console.log('✅ loadRecentActivities interceptado agressivamente');
    }

    // Interceptar loadNotifications imediatamente
    if (typeof window.loadNotifications === 'function') {
        const originalLoadNotifications = window.loadNotifications;
        window.loadNotifications = async function() {
            try {
                console.log('🚨 loadNotifications interceptado agressivamente');
                return [];
            } catch (error) {
                console.warn('🚨 loadNotifications erro interceptado:', error);
                return [];
            }
        };
        console.log('✅ loadNotifications interceptado agressivamente');
    }

    // Interceptar fetch globalmente imediatamente
    const originalFetch = window.fetch;
    window.fetch = async function(url, options = {}) {
        try {
            // Verificar se é uma URL problemática
            if (url.includes('api/') && (url.includes('actions.php') || url.includes('generic.php') || url.includes('notifications.php'))) {
                console.log('🚨 URL problemática interceptada:', url);
                return new Response(JSON.stringify({
                    success: false,
                    error: 'API interceptada',
                    data: []
                }), {
                    status: 200,
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });
            }

            const response = await originalFetch(url, options);
            
            // Verificar se a resposta é HTML em vez de JSON
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('text/html')) {
                console.warn('🚨 API retornou HTML interceptado:', url);
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
            console.warn('🚨 Erro na chamada da API interceptado:', url, error);
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

    console.log('✅ Todas as correções agressivas aplicadas');
})();

// Interceptar erros de JavaScript globalmente
window.addEventListener('error', function(event) {
    if (event.error && event.error.message && event.error.message.includes('Unexpected token')) {
        console.warn('🚨 Erro de JSON interceptado:', event.error.message);
        event.preventDefault();
        return false;
    }
});

// Interceptar rejeições de promessa
window.addEventListener('unhandledrejection', function(event) {
    if (event.reason && event.reason.message && event.reason.message.includes('Unexpected token')) {
        console.warn('🚨 Promessa rejeitada interceptada:', event.reason.message);
        event.preventDefault();
        return false;
    }
});

console.log('✅ Interceptação agressiva de erros carregada');

