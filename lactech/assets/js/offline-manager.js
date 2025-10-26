/**
 * Offline Manager - Lactech
 * Gerenciamento de funcionalidades offline
 */

class OfflineManager {
    constructor() {
        this.isOnline = navigator.onLine;
        this.offlineQueue = [];
        this.syncInProgress = false;
        this.init();
    }

    init() {
        console.log('📱 Offline Manager inicializado');
        this.setupOnlineOfflineListeners();
        this.setupServiceWorker();
        this.setupOfflineStorage();
        this.setupSyncQueue();
    }

    /**
     * Configurar listeners de online/offline
     */
    setupOnlineOfflineListeners() {
        window.addEventListener('online', () => {
            this.handleOnline();
        });

        window.addEventListener('offline', () => {
            this.handleOffline();
        });

        // Verificar status inicial
        this.updateOnlineStatus();
    }

    /**
     * Tratar quando ficar online
     */
    handleOnline() {
        this.isOnline = true;
        console.log('🌐 Conectado à internet');
        
        // Mostrar notificação
        if (window.nativeNotifications) {
            window.nativeNotifications.success('Conexão Restaurada', 'Você está online novamente');
        }
        
        // Sincronizar dados pendentes
        this.syncPendingData();
    }

    /**
     * Tratar quando ficar offline
     */
    handleOffline() {
        this.isOnline = false;
        console.log('📴 Desconectado da internet');
        
        // Mostrar notificação
        if (window.nativeNotifications) {
            window.nativeNotifications.alert('Conexão Perdida', 'Você está offline. Os dados serão sincronizados quando a conexão for restaurada');
        }
    }

    /**
     * Atualizar status online
     */
    updateOnlineStatus() {
        this.isOnline = navigator.onLine;
        
        // Atualizar UI
        this.updateOfflineIndicator();
    }

    /**
     * Atualizar indicador offline
     */
    updateOfflineIndicator() {
        const indicator = document.getElementById('offline-indicator');
        if (indicator) {
            indicator.style.display = this.isOnline ? 'none' : 'block';
        }
    }

    /**
     * Configurar Service Worker
     */
    setupServiceWorker() {
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js')
                .then(registration => {
                    console.log('Service Worker registrado:', registration);
                })
                .catch(error => {
                    console.error('Erro ao registrar Service Worker:', error);
                });
        }
    }

    /**
     * Configurar armazenamento offline
     */
    setupOfflineStorage() {
        // Verificar suporte ao IndexedDB
        if ('indexedDB' in window) {
            this.setupIndexedDB();
        } else {
            console.warn('IndexedDB não suportado, usando localStorage');
            this.setupLocalStorage();
        }
    }

    /**
     * Configurar IndexedDB
     */
    setupIndexedDB() {
        const request = indexedDB.open('LactechOffline', 1);
        
        request.onerror = () => {
            console.error('Erro ao abrir IndexedDB');
        };
        
        request.onsuccess = (event) => {
            this.db = event.target.result;
            console.log('IndexedDB configurado');
        };
        
        request.onupgradeneeded = (event) => {
            const db = event.target.result;
            
            // Criar store para dados offline
            if (!db.objectStoreNames.contains('offlineData')) {
                const store = db.createObjectStore('offlineData', { keyPath: 'id', autoIncrement: true });
                store.createIndex('type', 'type', { unique: false });
                store.createIndex('timestamp', 'timestamp', { unique: false });
            }
        };
    }

    /**
     * Configurar localStorage
     */
    setupLocalStorage() {
        this.storage = {
            get: (key) => {
                try {
                    return JSON.parse(localStorage.getItem(key));
                } catch (error) {
                    return null;
                }
            },
            set: (key, value) => {
                try {
                    localStorage.setItem(key, JSON.stringify(value));
                    return true;
                } catch (error) {
                    return false;
                }
            },
            remove: (key) => {
                localStorage.removeItem(key);
            }
        };
    }

    /**
     * Configurar fila de sincronização
     */
    setupSyncQueue() {
        // Sincronizar a cada 30 segundos quando online
        setInterval(() => {
            if (this.isOnline && !this.syncInProgress) {
                this.syncPendingData();
            }
        }, 30000);
    }

    /**
     * Adicionar dados à fila offline
     */
    addToOfflineQueue(data) {
        const offlineItem = {
            id: Date.now(),
            type: data.type || 'unknown',
            data: data,
            timestamp: new Date().toISOString(),
            attempts: 0
        };

        this.offlineQueue.push(offlineItem);
        this.saveOfflineQueue();
        
        console.log('Item adicionado à fila offline:', offlineItem);
    }

    /**
     * Salvar fila offline
     */
    saveOfflineQueue() {
        if (this.storage) {
            this.storage.set('offlineQueue', this.offlineQueue);
        }
    }

    /**
     * Carregar fila offline
     */
    loadOfflineQueue() {
        if (this.storage) {
            const queue = this.storage.get('offlineQueue');
            if (queue) {
                this.offlineQueue = queue;
            }
        }
    }

    /**
     * Sincronizar dados pendentes
     */
    async syncPendingData() {
        if (this.syncInProgress || !this.isOnline) {
            return;
        }

        this.syncInProgress = true;
        console.log('🔄 Iniciando sincronização offline...');

        try {
            // Carregar fila offline
            this.loadOfflineQueue();

            // Processar cada item da fila
            const itemsToRemove = [];
            
            for (const item of this.offlineQueue) {
                try {
                    await this.syncItem(item);
                    itemsToRemove.push(item.id);
                } catch (error) {
                    console.error('Erro ao sincronizar item:', error);
                    item.attempts++;
                    
                    // Remover após 3 tentativas
                    if (item.attempts >= 3) {
                        itemsToRemove.push(item.id);
                    }
                }
            }

            // Remover itens sincronizados
            this.offlineQueue = this.offlineQueue.filter(item => !itemsToRemove.includes(item.id));
            this.saveOfflineQueue();

            console.log('✅ Sincronização offline concluída');
            
        } catch (error) {
            console.error('Erro na sincronização offline:', error);
        } finally {
            this.syncInProgress = false;
        }
    }

    /**
     * Sincronizar item específico
     */
    async syncItem(item) {
        const { type, data } = item;
        
        switch (type) {
            case 'volume_record':
                await this.syncVolumeRecord(data);
                break;
            case 'quality_test':
                await this.syncQualityTest(data);
                break;
            case 'financial_record':
                await this.syncFinancialRecord(data);
                break;
            case 'user_update':
                await this.syncUserUpdate(data);
                break;
            default:
                console.warn('Tipo de sincronização não reconhecido:', type);
        }
    }

    /**
     * Sincronizar registro de volume
     */
    async syncVolumeRecord(data) {
        const response = await fetch('api/rest.php/volume', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        if (!response.ok) {
            throw new Error('Erro ao sincronizar registro de volume');
        }
    }

    /**
     * Sincronizar teste de qualidade
     */
    async syncQualityTest(data) {
        const response = await fetch('api/rest.php/quality', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        if (!response.ok) {
            throw new Error('Erro ao sincronizar teste de qualidade');
        }
    }

    /**
     * Sincronizar registro financeiro
     */
    async syncFinancialRecord(data) {
        const response = await fetch('api/rest.php/financial', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        if (!response.ok) {
            throw new Error('Erro ao sincronizar registro financeiro');
        }
    }

    /**
     * Sincronizar atualização de usuário
     */
    async syncUserUpdate(data) {
        const response = await fetch('api/rest.php/users', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        if (!response.ok) {
            throw new Error('Erro ao sincronizar atualização de usuário');
        }
    }

    /**
     * Verificar se está online
     */
    isOnline() {
        return this.isOnline;
    }

    /**
     * Verificar se está offline
     */
    isOffline() {
        return !this.isOnline;
    }

    /**
     * Obter estatísticas offline
     */
    getOfflineStats() {
        return {
            isOnline: this.isOnline,
            queueSize: this.offlineQueue.length,
            syncInProgress: this.syncInProgress
        };
    }
}

// Inicializar Offline Manager
document.addEventListener('DOMContentLoaded', () => {
    window.offlineManager = new OfflineManager();
});

// Exportar para uso global
window.OfflineManager = OfflineManager;

