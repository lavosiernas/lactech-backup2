/**
 * Offline Sync - Lactech
 * Sincronização de dados offline
 */

class OfflineSync {
    constructor() {
        this.syncQueue = [];
        this.syncInProgress = false;
        this.lastSync = null;
        this.init();
    }

    init() {
        console.log('🔄 Offline Sync inicializado');
        this.setupSyncListeners();
        this.setupPeriodicSync();
        this.loadSyncQueue();
    }

    /**
     * Configurar listeners de sincronização
     */
    setupSyncListeners() {
        // Sincronizar quando voltar online
        window.addEventListener('online', () => {
            this.syncWhenOnline();
        });

        // Sincronizar quando a página ganhar foco
        window.addEventListener('focus', () => {
            this.syncWhenFocused();
        });

        // Sincronizar antes da página ser descarregada
        window.addEventListener('beforeunload', () => {
            this.syncBeforeUnload();
        });
    }

    /**
     * Configurar sincronização periódica
     */
    setupPeriodicSync() {
        // Sincronizar a cada 5 minutos
        setInterval(() => {
            this.syncPeriodically();
        }, 300000); // 5 minutos
    }

    /**
     * Sincronizar quando voltar online
     */
    async syncWhenOnline() {
        if (navigator.onLine && this.syncQueue.length > 0) {
            console.log('🌐 Volta online - iniciando sincronização');
            await this.performSync();
        }
    }

    /**
     * Sincronizar quando a página ganhar foco
     */
    async syncWhenFocused() {
        if (navigator.onLine && this.syncQueue.length > 0) {
            console.log('👁️ Página em foco - iniciando sincronização');
            await this.performSync();
        }
    }

    /**
     * Sincronizar antes da página ser descarregada
     */
    async syncBeforeUnload() {
        if (navigator.onLine && this.syncQueue.length > 0) {
            console.log('🚪 Página sendo descarregada - sincronização final');
            await this.performSync();
        }
    }

    /**
     * Sincronização periódica
     */
    async syncPeriodically() {
        if (navigator.onLine && this.syncQueue.length > 0) {
            console.log('⏰ Sincronização periódica');
            await this.performSync();
        }
    }

    /**
     * Adicionar item à fila de sincronização
     */
    addToSyncQueue(item) {
        const syncItem = {
            id: Date.now() + Math.random(),
            type: item.type,
            data: item.data,
            endpoint: item.endpoint,
            method: item.method || 'POST',
            timestamp: new Date().toISOString(),
            attempts: 0,
            maxAttempts: 3
        };

        this.syncQueue.push(syncItem);
        this.saveSyncQueue();
        
        console.log('📝 Item adicionado à fila de sincronização:', syncItem);
        
        // Tentar sincronizar imediatamente se online
        if (navigator.onLine) {
            this.performSync();
        }
    }

    /**
     * Executar sincronização
     */
    async performSync() {
        if (this.syncInProgress || !navigator.onLine || this.syncQueue.length === 0) {
            return;
        }

        this.syncInProgress = true;
        console.log('🔄 Iniciando sincronização...');

        try {
            const itemsToRemove = [];
            
            for (const item of this.syncQueue) {
                try {
                    await this.syncItem(item);
                    itemsToRemove.push(item.id);
                    console.log('✅ Item sincronizado:', item.id);
                } catch (error) {
                    console.error('❌ Erro ao sincronizar item:', item.id, error);
                    item.attempts++;
                    
                    if (item.attempts >= item.maxAttempts) {
                        console.warn('⚠️ Item removido após máximo de tentativas:', item.id);
                        itemsToRemove.push(item.id);
                    }
                }
            }

            // Remover itens sincronizados
            this.syncQueue = this.syncQueue.filter(item => !itemsToRemove.includes(item.id));
            this.saveSyncQueue();
            
            this.lastSync = new Date().toISOString();
            console.log('✅ Sincronização concluída');
            
        } catch (error) {
            console.error('❌ Erro na sincronização:', error);
        } finally {
            this.syncInProgress = false;
        }
    }

    /**
     * Sincronizar item específico
     */
    async syncItem(item) {
        const { endpoint, method, data } = item;
        
        const response = await fetch(endpoint, {
            method: method,
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const result = await response.json();
        if (!result.success) {
            throw new Error(result.error || 'Erro na resposta da API');
        }
    }

    /**
     * Salvar fila de sincronização
     */
    saveSyncQueue() {
        try {
            localStorage.setItem('lactech_sync_queue', JSON.stringify(this.syncQueue));
        } catch (error) {
            console.error('Erro ao salvar fila de sincronização:', error);
        }
    }

    /**
     * Carregar fila de sincronização
     */
    loadSyncQueue() {
        try {
            const saved = localStorage.getItem('lactech_sync_queue');
            if (saved) {
                this.syncQueue = JSON.parse(saved);
                console.log('📥 Fila de sincronização carregada:', this.syncQueue.length, 'itens');
            }
        } catch (error) {
            console.error('Erro ao carregar fila de sincronização:', error);
            this.syncQueue = [];
        }
    }

    /**
     * Limpar fila de sincronização
     */
    clearSyncQueue() {
        this.syncQueue = [];
        this.saveSyncQueue();
        console.log('🗑️ Fila de sincronização limpa');
    }

    /**
     * Obter estatísticas de sincronização
     */
    getSyncStats() {
        return {
            queueSize: this.syncQueue.length,
            syncInProgress: this.syncInProgress,
            lastSync: this.lastSync,
            isOnline: navigator.onLine
        };
    }

    /**
     * Sincronizar dados de volume
     */
    syncVolumeData(data) {
        this.addToSyncQueue({
            type: 'volume',
            data: data,
            endpoint: 'api/rest.php/volume',
            method: 'POST'
        });
    }

    /**
     * Sincronizar dados de qualidade
     */
    syncQualityData(data) {
        this.addToSyncQueue({
            type: 'quality',
            data: data,
            endpoint: 'api/rest.php/quality',
            method: 'POST'
        });
    }

    /**
     * Sincronizar dados financeiros
     */
    syncFinancialData(data) {
        this.addToSyncQueue({
            type: 'financial',
            data: data,
            endpoint: 'api/rest.php/financial',
            method: 'POST'
        });
    }

    /**
     * Sincronizar dados de usuário
     */
    syncUserData(data) {
        this.addToSyncQueue({
            type: 'user',
            data: data,
            endpoint: 'api/rest.php/users',
            method: 'PUT'
        });
    }

    /**
     * Forçar sincronização
     */
    async forceSync() {
        console.log('🔄 Forçando sincronização...');
        await this.performSync();
    }

    /**
     * Verificar se há itens pendentes
     */
    hasPendingItems() {
        return this.syncQueue.length > 0;
    }

    /**
     * Obter itens pendentes
     */
    getPendingItems() {
        return this.syncQueue;
    }
}

// Inicializar Offline Sync
document.addEventListener('DOMContentLoaded', () => {
    window.offlineSync = new OfflineSync();
});

// Exportar para uso global
window.OfflineSync = OfflineSync;

