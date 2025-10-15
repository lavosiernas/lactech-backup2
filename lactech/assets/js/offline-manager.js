/**
 * OFFLINE MANAGER - LacTech
 * Sistema completo de cache offline e sincronização
 * Versão: 2.0.1
 */

class OfflineManager {
    constructor() {
        this.isOnline = navigator.onLine;
        this.syncQueue = [];
        this.cachePrefix = 'lactech_offline_';
        this.syncPrefix = 'lactech_sync_';
        this.userData = null;
        this.farmData = null;
        
        // Inicializar
        this.init();
    }

    /**
     * Inicializar o Offline Manager
     */
    async init() {
        console.log('🔄 Offline Manager inicializando...');
        
        // Event listeners para conexão
        window.addEventListener('online', () => this.handleOnline());
        window.addEventListener('offline', () => this.handleOffline());
        
        // Carregar dados do cache
        await this.loadCachedData();
        
        // Se estiver online, tentar sincronizar
        if (this.isOnline) {
            await this.syncPendingData();
        }
        
        console.log('✅ Offline Manager inicializado');
        this.showConnectionStatus();
    }

    /**
     * Carregar dados do cache local
     */
    async loadCachedData() {
        try {
            // Carregar dados do usuário
            const cachedUser = localStorage.getItem(this.cachePrefix + 'user');
            if (cachedUser) {
                this.userData = JSON.parse(cachedUser);
                console.log('👤 Dados do usuário carregados do cache');
            }

            // Carregar dados da fazenda
            const cachedFarm = localStorage.getItem(this.cachePrefix + 'farm');
            if (cachedFarm) {
                this.farmData = JSON.parse(cachedFarm);
                console.log('🏡 Dados da fazenda carregados do cache');
            }

            // Carregar dados de produção
            const cachedProduction = localStorage.getItem(this.cachePrefix + 'production');
            if (cachedProduction) {
                console.log('📊 Dados de produção carregados do cache');
                return JSON.parse(cachedProduction);
            }

            // Carregar dados de consultas (veterinário)
            const cachedConsultations = localStorage.getItem(this.cachePrefix + 'consultations');
            if (cachedConsultations) {
                console.log('🩺 Dados de consultas carregados do cache');
                return JSON.parse(cachedConsultations);
            }

            return null;
        } catch (error) {
            console.error('❌ Erro ao carregar dados do cache:', error);
            return null;
        }
    }

    /**
     * Salvar dados no cache local
     */
    saveToCache(key, data) {
        try {
            const cacheKey = this.cachePrefix + key;
            localStorage.setItem(cacheKey, JSON.stringify(data));
            console.log(`💾 Dados salvos no cache: ${key}`);
            return true;
        } catch (error) {
            console.error('❌ Erro ao salvar no cache:', error);
            return false;
        }
    }

    /**
     * Obter dados do cache local
     */
    getFromCache(key) {
        try {
            const cacheKey = this.cachePrefix + key;
            const data = localStorage.getItem(cacheKey);
            return data ? JSON.parse(data) : null;
        } catch (error) {
            console.error('❌ Erro ao obter do cache:', error);
            return null;
        }
    }

    /**
     * Adicionar dados à fila de sincronização
     */
    addToSyncQueue(operation, data) {
        const syncItem = {
            id: Date.now() + Math.random(),
            operation,
            data,
            timestamp: Date.now(),
            retries: 0
        };

        this.syncQueue.push(syncItem);
        this.saveSyncQueue();
        console.log('📝 Item adicionado à fila de sincronização:', operation);
    }

    /**
     * Salvar fila de sincronização
     */
    saveSyncQueue() {
        try {
            localStorage.setItem(this.syncPrefix + 'queue', JSON.stringify(this.syncQueue));
        } catch (error) {
            console.error('❌ Erro ao salvar fila de sincronização:', error);
        }
    }

    /**
     * Carregar fila de sincronização
     */
    loadSyncQueue() {
        try {
            const queue = localStorage.getItem(this.syncPrefix + 'queue');
            this.syncQueue = queue ? JSON.parse(queue) : [];
        } catch (error) {
            console.error('❌ Erro ao carregar fila de sincronização:', error);
            this.syncQueue = [];
        }
    }

    /**
     * Sincronizar dados pendentes
     */
    async syncPendingData() {
        if (!this.isOnline || this.syncQueue.length === 0) {
            return;
        }

        console.log('🔄 Iniciando sincronização de dados pendentes...');
        this.loadSyncQueue();

        const itemsToSync = [...this.syncQueue];
        const successfulSyncs = [];

        for (const item of itemsToSync) {
            try {
                const success = await this.syncItem(item);
                if (success) {
                    successfulSyncs.push(item.id);
                } else {
                    item.retries++;
                    if (item.retries < 3) {
                        console.log(`⚠️ Tentativa ${item.retries} falhou para item:`, item.operation);
                    } else {
                        console.error('❌ Item falhou após 3 tentativas:', item.operation);
                        successfulSyncs.push(item.id); // Remove da fila mesmo falhando
                    }
                }
            } catch (error) {
                console.error('❌ Erro na sincronização:', error);
                item.retries++;
            }
        }

        // Remover itens sincronizados com sucesso
        this.syncQueue = this.syncQueue.filter(item => !successfulSyncs.includes(item.id));
        this.saveSyncQueue();

        if (successfulSyncs.length > 0) {
            console.log(`✅ ${successfulSyncs.length} itens sincronizados com sucesso`);
            this.showNotification('Dados sincronizados com sucesso!', 'success');
        }
    }

    /**
     * Sincronizar item individual
     */
    async syncItem(item) {
        try {
            // Aqui você implementaria a lógica específica para cada tipo de operação
            switch (item.operation) {
                case 'volume_record':
                    return await this.syncVolumeRecord(item.data);
                case 'consultation':
                    return await this.syncConsultation(item.data);
                case 'user_update':
                    return await this.syncUserUpdate(item.data);
                default:
                    console.log('Operação não reconhecida:', item.operation);
                    return true; // Remove da fila
            }
        } catch (error) {
            console.error('❌ Erro ao sincronizar item:', error);
            return false;
        }
    }

    /**
     * Sincronizar registro de volume
     */
    async syncVolumeRecord(data) {
        try {
            // Implementar chamada para Supabase
            const supabase = createSupabaseClient();
            const { error } = await supabase
                .from('volume_records')
                .insert(data);

            if (error) throw error;
            return true;
        } catch (error) {
            console.error('❌ Erro ao sincronizar volume record:', error);
            return false;
        }
    }

    /**
     * Sincronizar consulta
     */
    async syncConsultation(data) {
        try {
            // Implementar chamada para Supabase
            const supabase = createSupabaseClient();
            const { error } = await supabase
                .from('consultations')
                .insert(data);

            if (error) throw error;
            return true;
        } catch (error) {
            console.error('❌ Erro ao sincronizar consulta:', error);
            return false;
        }
    }

    /**
     * Sincronizar atualização de usuário
     */
    async syncUserUpdate(data) {
        try {
            // Implementar chamada para Supabase
            const supabase = createSupabaseClient();
            const { error } = await supabase
                .from('users')
                .update(data)
                .eq('id', data.id);

            if (error) throw error;
            return true;
        } catch (error) {
            console.error('❌ Erro ao sincronizar usuário:', error);
            return false;
        }
    }

    /**
     * Obter dados com fallback offline
     */
    async getData(key, fetchFunction) {
        if (this.isOnline) {
            try {
                // Tentar buscar dados online
                const data = await fetchFunction();
                this.saveToCache(key, data);
                return data;
            } catch (error) {
                console.log('⚠️ Erro ao buscar dados online, usando cache:', error);
                return this.getFromCache(key);
            }
        } else {
            // Usar dados do cache
            console.log('📱 Modo offline - usando dados do cache');
            return this.getFromCache(key);
        }
    }

    /**
     * Salvar dados com sincronização
     */
    async saveData(operation, data, immediateSave = false) {
        if (this.isOnline && immediateSave) {
            try {
                // Tentar salvar imediatamente
                const success = await this.syncItem({ operation, data });
                if (success) {
                    console.log('✅ Dados salvos online imediatamente');
                    return true;
                }
            } catch (error) {
                console.log('⚠️ Erro ao salvar online, adicionando à fila:', error);
            }
        }

        // Adicionar à fila de sincronização
        this.addToSyncQueue(operation, data);
        
        // Salvar no cache local também
        this.saveToCache(operation, data);
        
        return true;
    }

    /**
     * Limpar cache local
     */
    clearCache() {
        try {
            const keys = Object.keys(localStorage);
            keys.forEach(key => {
                if (key.startsWith(this.cachePrefix)) {
                    localStorage.removeItem(key);
                }
            });
            console.log('🗑️ Cache local limpo');
        } catch (error) {
            console.error('❌ Erro ao limpar cache:', error);
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
     * Manipular conexão online
     */
    async handleOnline() {
        console.log('🌐 Conexão restaurada!');
        this.isOnline = true;
        this.showConnectionStatus();
        
        // Sincronizar dados pendentes sem loading visual
        await this.syncPendingData();
        
        // Não mostrar notificação - reconexão silenciosa
    }

    /**
     * Manipular conexão offline
     */
    handleOffline() {
        console.log('📱 Modo offline ativado');
        this.isOnline = false;
        this.showConnectionStatus();
        this.showNotification('Modo offline ativado. Dados serão sincronizados quando a conexão for restaurada.', 'info');
    }

    /**
     * Mostrar status da conexão
     */
    showConnectionStatus() {
        // Status agora é gerenciado pelo OfflineLoadingSystem
        if (window.offlineLoadingSystem) {
            if (this.isOnline) {
                window.offlineLoadingSystem.updateConnectionStatus('online', 'Online');
            } else {
                window.offlineLoadingSystem.updateConnectionStatus('offline', 'Offline');
            }
        }
    }

    /**
     * Mostrar notificação
     */
    showNotification(message, type = 'info') {
        // Usar o sistema de notificação existente se disponível
        if (typeof showNotification === 'function') {
            showNotification(message, type);
        } else {
            console.log(`📢 ${type.toUpperCase()}: ${message}`);
        }
    }

    /**
     * Obter estatísticas do cache
     */
    getCacheStats() {
        const keys = Object.keys(localStorage);
        const cacheKeys = keys.filter(key => key.startsWith(this.cachePrefix));
        const syncKeys = keys.filter(key => key.startsWith(this.syncPrefix));
        
        return {
            cacheItems: cacheKeys.length,
            syncQueueItems: this.syncQueue.length,
            isOnline: this.isOnline,
            lastSync: localStorage.getItem(this.syncPrefix + 'lastSync')
        };
    }
}

// Instância global do Offline Manager
window.offlineManager = new OfflineManager();

// Exportar para uso em outros arquivos
if (typeof module !== 'undefined' && module.exports) {
    module.exports = OfflineManager;
}
