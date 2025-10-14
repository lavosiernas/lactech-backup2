/**
 * OFFLINE SYNC MANAGER - LacTech
 * Sistema robusto de sincronização offline/online
 * Versão: 2.0.2
 */

if (typeof OfflineSyncManager === 'undefined') {
class OfflineSyncManager {
    constructor() {
        this.isOnline = navigator.onLine;
        this.syncQueue = [];
        this.localData = new Map();
        this.syncInProgress = false;
        this.retryCount = 0;
        this.maxRetries = 3;
        
        // Prefixos para localStorage
        this.prefixes = {
            volume: 'lactech_volume_',
            quality: 'lactech_quality_',
            sales: 'lactech_sales_',
            users: 'lactech_users_',
            sync: 'lactech_sync_',
            pending: 'lactech_pending_'
        };
        
        this.init();
    }

    /**
     * Inicializar o sistema de sincronização
     */
    async init() {
        console.log('🔄 Offline Sync Manager inicializando...');
        
        // Event listeners para conexão
        window.addEventListener('online', () => this.handleOnline());
        window.addEventListener('offline', () => this.handleOffline());
        
        // Carregar dados locais
        await this.loadLocalData();
        
        // Se estiver online, sincronizar dados pendentes
        if (this.isOnline) {
            await this.syncPendingData();
        }
        
        // Configurar sincronização automática
        this.setupAutoSync();
        
        console.log('✅ Offline Sync Manager inicializado');
    }

    /**
     * Carregar dados do localStorage
     */
    async loadLocalData() {
        try {
            // Carregar dados de volume
            const volumeData = this.getLocalData('volume');
            if (volumeData.length > 0) {
                this.localData.set('volume', volumeData);
                console.log(`📊 ${volumeData.length} registros de volume carregados localmente`);
            }

            // Carregar dados de qualidade
            const qualityData = this.getLocalData('quality');
            if (qualityData.length > 0) {
                this.localData.set('quality', qualityData);
                console.log(`🔬 ${qualityData.length} registros de qualidade carregados localmente`);
            }

            // Carregar dados de vendas
            const salesData = this.getLocalData('sales');
            if (salesData.length > 0) {
                this.localData.set('sales', salesData);
                console.log(`💰 ${salesData.length} registros de vendas carregados localmente`);
            }

            // Carregar fila de sincronização
            const syncQueue = this.getLocalData('sync');
            if (syncQueue.length > 0) {
                this.syncQueue = syncQueue;
                console.log(`⏳ ${syncQueue.length} itens na fila de sincronização`);
            }

        } catch (error) {
            console.error('❌ Erro ao carregar dados locais:', error);
        }
    }

    /**
     * Obter dados locais por tipo
     */
    getLocalData(type) {
        try {
            const data = localStorage.getItem(this.prefixes[type]);
            return data ? JSON.parse(data) : [];
        } catch (error) {
            console.error(`❌ Erro ao obter dados locais (${type}):`, error);
            return [];
        }
    }

    /**
     * Salvar dados locais por tipo
     */
    saveLocalData(type, data) {
        try {
            localStorage.setItem(this.prefixes[type], JSON.stringify(data));
            this.localData.set(type, data);
            console.log(`💾 Dados salvos localmente (${type}):`, data.length, 'itens');
        } catch (error) {
            console.error(`❌ Erro ao salvar dados locais (${type}):`, error);
        }
    }

    /**
     * Adicionar registro de volume offline
     */
    async addVolumeRecord(volumeData) {
        try {
            // Gerar ID temporário
            const tempId = 'temp_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            
            const record = {
                id: tempId,
                ...volumeData,
                created_at: new Date().toISOString(),
                synced: false,
                offline: true
            };

            // Adicionar aos dados locais
            const localVolumes = this.getLocalData('volume');
            localVolumes.push(record);
            this.saveLocalData('volume', localVolumes);

            // Adicionar à fila de sincronização
            this.addToSyncQueue('volume', 'create', record);

            // Atualizar interface imediatamente
            this.updateVolumeDisplay(record);

            // Notificação de sucesso
            if (window.nativeNotifications) {
                window.nativeNotifications.showRealDeviceNotification(
                    'Volume Registrado (Offline)',
                    `${record.volume_liters}L registrado localmente`,
                    'production'
                );
            }

            console.log('✅ Volume registrado offline:', record);
            return { success: true, data: record };

        } catch (error) {
            console.error('❌ Erro ao registrar volume offline:', error);
            return { success: false, error: error.message };
        }
    }

    /**
     * Adicionar registro de qualidade offline
     */
    async addQualityRecord(qualityData) {
        try {
            const tempId = 'temp_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            
            const record = {
                id: tempId,
                ...qualityData,
                created_at: new Date().toISOString(),
                synced: false,
                offline: true
            };

            const localQualities = this.getLocalData('quality');
            localQualities.push(record);
            this.saveLocalData('quality', localQualities);

            this.addToSyncQueue('quality', 'create', record);
            this.updateQualityDisplay(record);

            console.log('✅ Qualidade registrada offline:', record);
            return { success: true, data: record };

        } catch (error) {
            console.error('❌ Erro ao registrar qualidade offline:', error);
            return { success: false, error: error.message };
        }
    }

    /**
     * Adicionar registro de venda offline
     */
    async addSalesRecord(salesData) {
        try {
            const tempId = 'temp_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            
            const record = {
                id: tempId,
                ...salesData,
                created_at: new Date().toISOString(),
                synced: false,
                offline: true
            };

            const localSales = this.getLocalData('sales');
            localSales.push(record);
            this.saveLocalData('sales', localSales);

            this.addToSyncQueue('sales', 'create', record);
            this.updateSalesDisplay(record);

            console.log('✅ Venda registrada offline:', record);
            return { success: true, data: record };

        } catch (error) {
            console.error('❌ Erro ao registrar venda offline:', error);
            return { success: false, error: error.message };
        }
    }

    /**
     * Adicionar à fila de sincronização
     */
    addToSyncQueue(type, action, data) {
        const syncItem = {
            id: Date.now() + '_' + Math.random().toString(36).substr(2, 9),
            type: type,
            action: action,
            data: data,
            timestamp: new Date().toISOString(),
            retries: 0
        };

        this.syncQueue.push(syncItem);
        this.saveLocalData('sync', this.syncQueue);

        console.log(`⏳ Item adicionado à fila de sincronização: ${type} - ${action}`);
    }

    /**
     * Atualizar display de volume
     */
    updateVolumeDisplay(record) {
        // Atualizar gráficos em tempo real
        if (window.updateVolumeCharts) {
            window.updateVolumeCharts();
        }

        // Atualizar lista de registros
        if (window.loadVolumeData) {
            window.loadVolumeData();
        }

        // Atualizar estatísticas
        if (window.updateVolumeStats) {
            window.updateVolumeStats();
        }
    }

    /**
     * Atualizar display de qualidade
     */
    updateQualityDisplay(record) {
        if (window.updateQualityCharts) {
            window.updateQualityCharts();
        }

        if (window.loadQualityData) {
            window.loadQualityData();
        }

        if (window.updateQualityStats) {
            window.updateQualityStats();
        }
    }

    /**
     * Atualizar display de vendas
     */
    updateSalesDisplay(record) {
        if (window.updateSalesCharts) {
            window.updateSalesCharts();
        }

        if (window.loadSalesData) {
            window.loadSalesData();
        }

        if (window.updateSalesStats) {
            window.updateSalesStats();
        }
    }

    /**
     * Sincronizar dados pendentes
     */
    async syncPendingData() {
        if (this.syncInProgress || this.syncQueue.length === 0) {
            return;
        }

        this.syncInProgress = true;
        console.log(`🔄 Iniciando sincronização de ${this.syncQueue.length} itens...`);

        const successfulSyncs = [];
        const failedSyncs = [];

        for (const item of [...this.syncQueue]) {
            try {
                const result = await this.syncItem(item);
                if (result.success) {
                    successfulSyncs.push(item);
                    this.removeFromSyncQueue(item.id);
                } else {
                    failedSyncs.push(item);
                    item.retries++;
                }
            } catch (error) {
                console.error('❌ Erro na sincronização:', error);
                item.retries++;
                failedSyncs.push(item);
            }
        }

        // Atualizar fila com itens que falharam
        this.syncQueue = failedSyncs;
        this.saveLocalData('sync', this.syncQueue);

        this.syncInProgress = false;

        if (successfulSyncs.length > 0) {
            console.log(`✅ ${successfulSyncs.length} itens sincronizados com sucesso`);
            
            // Notificação de sincronização
            if (window.nativeNotifications) {
                window.nativeNotifications.showRealDeviceNotification(
                    'Sincronização Concluída',
                    `${successfulSyncs.length} registros sincronizados`,
                    'pending_sync'
                );
            }

            // Recarregar dados para atualizar IDs
            await this.reloadDataAfterSync();
        }

        if (failedSyncs.length > 0) {
            console.log(`⚠️ ${failedSyncs.length} itens falharam na sincronização`);
        }
    }

    /**
     * Sincronizar item individual
     */
    async syncItem(item) {
        try {
            const supabase = await getSupabaseClient();
            let result;

            switch (item.type) {
                case 'volume':
                    result = await this.syncVolumeItem(supabase, item);
                    break;
                case 'quality':
                    result = await this.syncQualityItem(supabase, item);
                    break;
                case 'sales':
                    result = await this.syncSalesItem(supabase, item);
                    break;
                default:
                    throw new Error(`Tipo de item não suportado: ${item.type}`);
            }

            return result;
        } catch (error) {
            console.error('❌ Erro ao sincronizar item:', error);
            return { success: false, error: error.message };
        }
    }

    /**
     * Sincronizar item de volume
     */
    async syncVolumeItem(supabase, item) {
        const { data, error } = await supabase
            .from('volume_records')
            .insert([{
                farm_id: item.data.farm_id,
                user_id: item.data.user_id,
                employee_name: item.data.employee_name, // Incluir nome do funcionário
                production_date: item.data.production_date,
                milking_type: item.data.milking_type,
                volume_liters: item.data.volume_liters,
                temperature: item.data.temperature,
                notes: item.data.notes,
                created_at: item.data.created_at
            }])
            .select();

        if (error) throw error;

        // Atualizar dados locais com ID real
        const localVolumes = this.getLocalData('volume');
        const localIndex = localVolumes.findIndex(v => v.id === item.data.id);
        if (localIndex !== -1) {
            localVolumes[localIndex] = {
                ...localVolumes[localIndex],
                id: data[0].id,
                synced: true,
                offline: false
            };
            this.saveLocalData('volume', localVolumes);
        }

        return { success: true, data: data[0] };
    }

    /**
     * Sincronizar item de qualidade
     */
    async syncQualityItem(supabase, item) {
        const { data, error } = await supabase
            .from('quality_tests')
            .insert([{
                farm_id: item.data.farm_id,
                test_date: item.data.test_date,
                fat_content: item.data.fat_content,
                protein_content: item.data.protein_content,
                bacteria_count: item.data.bacteria_count,
                temperature: item.data.temperature,
                notes: item.data.notes,
                created_at: item.data.created_at
            }])
            .select();

        if (error) throw error;

        const localQualities = this.getLocalData('quality');
        const localIndex = localQualities.findIndex(q => q.id === item.data.id);
        if (localIndex !== -1) {
            localQualities[localIndex] = {
                ...localQualities[localIndex],
                id: data[0].id,
                synced: true,
                offline: false
            };
            this.saveLocalData('quality', localQualities);
        }

        return { success: true, data: data[0] };
    }

    /**
     * Sincronizar item de vendas
     */
    async syncSalesItem(supabase, item) {
        const { data, error } = await supabase
            .from('sales_records')
            .insert([{
                farm_id: item.data.farm_id,
                sale_date: item.data.sale_date,
                volume_sold: item.data.volume_sold,
                price_per_liter: item.data.price_per_liter,
                total_amount: item.data.total_amount,
                buyer_name: item.data.buyer_name,
                notes: item.data.notes,
                created_at: item.data.created_at
            }])
            .select();

        if (error) throw error;

        const localSales = this.getLocalData('sales');
        const localIndex = localSales.findIndex(s => s.id === item.data.id);
        if (localIndex !== -1) {
            localSales[localIndex] = {
                ...localSales[localIndex],
                id: data[0].id,
                synced: true,
                offline: false
            };
            this.saveLocalData('sales', localSales);
        }

        return { success: true, data: data[0] };
    }

    /**
     * Remover da fila de sincronização
     */
    removeFromSyncQueue(itemId) {
        this.syncQueue = this.syncQueue.filter(item => item.id !== itemId);
        this.saveLocalData('sync', this.syncQueue);
    }

    /**
     * Recarregar dados após sincronização
     */
    async reloadDataAfterSync() {
        try {
            // Recarregar dados do servidor
            if (window.loadVolumeData) {
                await window.loadVolumeData();
            }
            if (window.loadQualityData) {
                await window.loadQualityData();
            }
            if (window.loadSalesData) {
                await window.loadSalesData();
            }

            console.log('✅ Dados recarregados após sincronização');
        } catch (error) {
            console.error('❌ Erro ao recarregar dados:', error);
        }
    }

    /**
     * Configurar sincronização automática
     */
    setupAutoSync() {
        // Sincronizar a cada 30 segundos quando online
        setInterval(() => {
            if (this.isOnline && this.syncQueue.length > 0) {
                this.syncPendingData();
            }
        }, 30000);

        // Sincronizar quando a página ganha foco
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden && this.isOnline && this.syncQueue.length > 0) {
                this.syncPendingData();
            }
        });
    }

    /**
     * Manipular conexão online
     */
    async handleOnline() {
        this.isOnline = true;
        console.log('🌐 Conexão restaurada - iniciando sincronização...');
        
        // Notificação de conexão
        if (window.nativeNotifications) {
            window.nativeNotifications.showRealDeviceNotification(
                'Conexão Restaurada',
                'Sincronizando dados pendentes...',
                'pending_sync'
            );
        }

        // Sincronizar dados pendentes
        await this.syncPendingData();
    }

    /**
     * Manipular conexão offline
     */
    handleOffline() {
        this.isOnline = false;
        console.log('📴 Modo offline ativado');
        
        // Notificação de modo offline
        if (window.nativeNotifications) {
            window.nativeNotifications.showRealDeviceNotification(
                'Modo Offline',
                'Dados serão sincronizados quando voltar online',
                'pending_sync'
            );
        }
    }

    /**
     * Obter status da sincronização
     */
    getSyncStatus() {
        return {
            isOnline: this.isOnline,
            pendingItems: this.syncQueue.length,
            syncInProgress: this.syncInProgress,
            localDataCounts: {
                volume: this.getLocalData('volume').length,
                quality: this.getLocalData('quality').length,
                sales: this.getLocalData('sales').length
            }
        };
    }

    /**
     * Limpar dados locais (para debug)
     */
    clearLocalData() {
        Object.values(this.prefixes).forEach(prefix => {
            localStorage.removeItem(prefix);
        });
        this.localData.clear();
        this.syncQueue = [];
        console.log('🗑️ Dados locais limpos');
    }
}

// Instância global
let offlineSyncManager = null;

// Inicializar quando DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    offlineSyncManager = new OfflineSyncManager();
    window.offlineSyncManager = offlineSyncManager;
});

// Exportar para uso global
window.OfflineSyncManager = OfflineSyncManager;
} // Fechar a verificação de undefined
