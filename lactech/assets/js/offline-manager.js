/**
 * Offline Manager - Sistema de Modo Offline
 * Gerencia registros offline e sincronização automática
 */

class OfflineManager {
    constructor() {
        this.queue = [];
        this.isOnline = navigator.onLine;
        this.forceOffline = false; // Modo offline forçado manualmente
        this.syncInProgress = false;
        this.storageKey = 'lactech_offline_queue';
        this.syncInterval = null;
        this.connectionQuality = 'unknown'; // 'good', 'poor', 'unknown'
        this.connectionCheckInterval = null;
        this.offlineNotificationInterval = null; // Timer para notificações offline
        
        this.init();
    }
    
    init() {
        // Carregar fila do localStorage
        this.loadQueue();
        
        // Só carregar estado de modo offline forçado se realmente estiver offline
        // Se estiver online, resetar para modo online (exceto se houver registros pendentes)
        if (!navigator.onLine) {
            // Se realmente está offline, manter modo offline
            const savedForceOffline = localStorage.getItem('lactech_force_offline');
            if (savedForceOffline === 'true') {
                this.forceOffline = true;
            } else {
                // Offline real, ativar automaticamente
                this.forceOffline = true;
                localStorage.setItem('lactech_force_offline', 'true');
            }
        } else {
            // Está online - verificar se deve manter modo offline
            // Só manter modo offline forçado se houver registros pendentes
            const savedForceOffline = localStorage.getItem('lactech_force_offline');
            if (savedForceOffline === 'true' && this.queue.length === 0) {
                // Está online, não há registros pendentes, desativar modo offline
                this.forceOffline = false;
                localStorage.setItem('lactech_force_offline', 'false');
            } else if (savedForceOffline === 'true' && this.queue.length > 0) {
                // Há registros pendentes, manter modo offline para sincronizar
                this.forceOffline = true;
            } else {
                // Começar em modo online
                this.forceOffline = false;
            }
        }
        
        // Event listeners para mudanças de conexão
        window.addEventListener('online', () => this.handleOnline());
        window.addEventListener('offline', () => this.handleOffline());
        
        // Verificar qualidade de conexão periodicamente (após um pequeno delay)
        // Não verificar imediatamente para não ativar modo offline na primeira carga
        setTimeout(() => {
            this.checkConnectionQuality();
            this.connectionCheckInterval = setInterval(() => {
                this.checkConnectionQuality();
            }, 10000); // A cada 10 segundos
        }, 2000); // Aguardar 2 segundos antes da primeira verificação
        
        // Mostrar status inicial
        this.updateUI();
        
        // Tentar sincronizar ao carregar (se estiver online e não forçado offline)
        if (this.isOnline && !this.forceOffline) {
            this.sync();
        }
        
        // Verificar conexão periodicamente
        this.syncInterval = setInterval(() => {
            if (this.isOnline && !this.forceOffline && this.queue.length > 0) {
                this.sync();
            }
        }, 30000); // A cada 30 segundos
        
        // Iniciar timer de notificações offline (a cada 5 minutos)
        this.startOfflineNotificationTimer();
    }
    
    startOfflineNotificationTimer() {
        // Limpar timer anterior se existir
        if (this.offlineNotificationInterval) {
            clearInterval(this.offlineNotificationInterval);
        }
        
        // Verificar se está offline e iniciar timer
        if (!this.isOnline || this.forceOffline) {
            // Mostrar primeira notificação imediatamente se estiver offline
            this.showOfflineNotification();
            
            // Configurar timer para mostrar a cada 5 minutos (300000ms)
            this.offlineNotificationInterval = setInterval(() => {
                if (!this.isOnline || this.forceOffline) {
                    this.showOfflineNotification();
                } else {
                    // Se voltar a ficar online, parar o timer
                    this.stopOfflineNotificationTimer();
                }
            }, 300000); // 5 minutos
        }
    }
    
    stopOfflineNotificationTimer() {
        if (this.offlineNotificationInterval) {
            clearInterval(this.offlineNotificationInterval);
            this.offlineNotificationInterval = null;
        }
    }
    
    showOfflineNotification() {
        const statusText = this.queue.length > 0 
            ? `Você está offline. ${this.queue.length} registro(s) aguardando sincronização.`
            : 'Você está offline. Os registros serão salvos localmente e sincronizados quando a conexão for restaurada.';
        
        this.showNotification(statusText, 'warning');
    }
    
    async checkConnectionQuality() {
        if (this.forceOffline || !navigator.onLine) {
            this.connectionQuality = 'poor';
            if (!this.forceOffline && navigator.onLine === false) {
                // Se a conexão estiver realmente offline, ativar modo offline automaticamente
                this.forceOffline = true;
                localStorage.setItem('lactech_force_offline', 'true');
                this.updateUI();
            }
            return;
        }
        
        try {
            const startTime = performance.now();
            // Usar um endpoint simples para verificar conexão
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 3000); // Timeout de 3 segundos
            
            const response = await fetch('/api/actions.php', {
                method: 'HEAD',
                cache: 'no-cache',
                signal: controller.signal
            });
            
            clearTimeout(timeoutId);
            const endTime = performance.now();
            const latency = endTime - startTime;
            
            if (response.ok) {
                // Se a latência for maior que 3 segundos (aumentado o threshold), considerar conexão ruim
                if (latency > 3000) {
                    this.connectionQuality = 'poor';
                    // Ativar modo offline automaticamente se conexão estiver muito lenta
                    // Mas só se realmente estiver lenta repetidamente (não na primeira verificação)
                    if (!this.forceOffline && this.connectionCheckInterval) {
                        // Só ativar se já estiver verificando há um tempo (não na primeira carga)
                        this.forceOffline = true;
                        localStorage.setItem('lactech_force_offline', 'true');
                        this.showNotification('Conexão lenta detectada. Modo offline ativado automaticamente.', 'warning');
                        this.updateUI();
                    }
                } else {
                    this.connectionQuality = 'good';
                    // Se estava forçado offline mas agora a conexão melhorou, desativar modo offline
                    // Mas só se não houver registros pendentes (para não interromper sincronização)
                    if (this.forceOffline && latency < 1000 && this.queue.length === 0) {
                        this.toggleOfflineMode(false);
                    }
                }
            } else {
                this.connectionQuality = 'poor';
            }
        } catch (error) {
            // Erro na verificação = conexão ruim ou offline (incluindo timeout)
            if (error.name === 'AbortError' || error.message.includes('timeout')) {
                // Timeout = conexão muito lenta
                // Mas não ativar automaticamente na primeira verificação
                this.connectionQuality = 'poor';
                // Só ativar se já estiver verificando há um tempo (não na primeira carga)
                if (!this.forceOffline && this.connectionCheckInterval && !navigator.onLine) {
                    this.forceOffline = true;
                    localStorage.setItem('lactech_force_offline', 'true');
                    this.showNotification('Conexão lenta detectada. Modo offline ativado automaticamente.', 'warning');
                    this.updateUI();
                }
            } else {
                // Outros erros = conexão ruim ou offline
                this.connectionQuality = 'poor';
                // Só ativar se realmente estiver offline
                if (!this.forceOffline && !navigator.onLine) {
                    this.forceOffline = true;
                    localStorage.setItem('lactech_force_offline', 'true');
                    this.updateUI();
                }
            }
        }
    }
    
    toggleOfflineMode(force = null) {
        if (force === null) {
            this.forceOffline = !this.forceOffline;
        } else {
            this.forceOffline = force;
        }
        
        localStorage.setItem('lactech_force_offline', this.forceOffline ? 'true' : 'false');
        
        // Informar Service Worker
        if ('serviceWorker' in navigator && navigator.serviceWorker.controller) {
            navigator.serviceWorker.controller.postMessage({
                type: this.forceOffline ? 'FORCE_OFFLINE' : 'FORCE_ONLINE'
            });
        }
        
        this.updateUI();
        
        if (this.forceOffline) {
            this.showNotification('Modo offline ativado manualmente', 'info');
            // Iniciar timer de notificações offline
            this.startOfflineNotificationTimer();
        } else {
            this.showNotification('Modo online restaurado', 'success');
            // Parar timer de notificações offline
            this.stopOfflineNotificationTimer();
            // Tentar sincronizar imediatamente
            if (this.queue.length > 0) {
                this.sync();
            }
        }
    }
    
    loadQueue() {
        try {
            const stored = localStorage.getItem(this.storageKey);
            if (stored) {
                this.queue = JSON.parse(stored);
            }
        } catch (e) {
            console.error('Erro ao carregar fila offline:', e);
            this.queue = [];
        }
    }
    
    saveQueue() {
        try {
            localStorage.setItem(this.storageKey, JSON.stringify(this.queue));
            this.updateUI();
        } catch (e) {
            console.error('Erro ao salvar fila offline:', e);
        }
    }
    
    handleOnline() {
        console.log('Conexão restaurada - Iniciando sincronização...');
        this.isOnline = true;
        // Parar timer de notificações offline
        this.stopOfflineNotificationTimer();
        
        // Se não estiver forçado offline, tentar sincronizar
        if (!this.forceOffline) {
            this.updateUI();
            this.sync();
        } else {
            // Mesmo online, se estiver forçado offline, manter modo offline e iniciar timer
            this.updateUI();
            this.startOfflineNotificationTimer();
        }
    }
    
    handleOffline() {
        console.log('Modo offline ativado automaticamente');
        this.isOnline = false;
        this.forceOffline = true; // Ativar modo offline automaticamente
        localStorage.setItem('lactech_force_offline', 'true');
        this.updateUI();
        // Iniciar timer de notificações offline
        this.startOfflineNotificationTimer();
    }
    
    async addToQueue(type, data, endpoint) {
        // Converter FormData para objeto simples (sem arquivos)
        const dataObj = {};
        let hasFiles = false;
        
        if (data instanceof FormData) {
            for (const [key, value] of data.entries()) {
                if (value instanceof File) {
                    // Arquivos não podem ser armazenados offline facilmente
                    // Ignorar e alertar o usuário
                    hasFiles = true;
                    console.warn(`Arquivo ignorado no modo offline: ${key} - ${value.name}`);
                } else {
                    dataObj[key] = value;
                }
            }
        } else {
            Object.assign(dataObj, data);
        }
        
        const record = {
            id: this.generateId(),
            type: type, // 'volume_general', 'volume_animal', 'quality', 'financial'
            data: dataObj,
            endpoint: endpoint,
            timestamp: new Date().toISOString(),
            retries: 0,
            hasFiles: hasFiles
        };
        
        this.queue.push(record);
        this.saveQueue();
        
        if (hasFiles) {
            this.showNotification('Nota: Arquivos não podem ser salvos offline. O registro será salvo sem arquivos.', 'warning');
        }
        
        // Se estiver online, tentar sincronizar imediatamente
        if (this.isOnline) {
            await this.sync();
        }
        
        return record;
    }
    
    async sync() {
        if (this.syncInProgress || this.queue.length === 0 || !this.isOnline || this.forceOffline) {
            return;
        }
        
        this.syncInProgress = true;
        this.updateUI();
        
        console.log(`Iniciando sincronização de ${this.queue.length} registro(s)...`);
        
        const recordsToSync = [...this.queue];
        const failedRecords = [];
        
        for (const record of recordsToSync) {
            try {
                const formData = new FormData();
                
                // Converter dados para FormData
                Object.keys(record.data).forEach(key => {
                    const value = record.data[key];
                    if (value !== null && value !== undefined && value !== '') {
                        formData.append(key, String(value));
                    }
                });
                
                // Adicionar action se não estiver presente
                if (!record.data.action) {
                    formData.append('action', record.type === 'volume_general' ? 'add_volume_general' : 
                                                   record.type === 'volume_animal' ? 'add_volume_by_animal' :
                                                   record.type === 'quality' ? 'add_quality_test' :
                                                   record.type === 'financial' ? 'add_financial_record' : record.type);
                }
                
                const response = await fetch(record.endpoint, {
                    method: 'POST',
                    body: formData
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                
                const result = await response.json();
                
                if (result.success) {
                    // Remover da fila
                    this.queue = this.queue.filter(r => r.id !== record.id);
                    console.log(`Registro ${record.id} sincronizado com sucesso`);
                } else {
                    throw new Error(result.error || 'Erro desconhecido');
                }
            } catch (error) {
                console.error(`Erro ao sincronizar registro ${record.id}:`, error);
                record.retries++;
                
                // Se já tentou 3 vezes, marcar como falha permanente
                if (record.retries >= 3) {
                    console.warn(`Registro ${record.id} falhou após ${record.retries} tentativas`);
                    failedRecords.push(record);
                } else {
                    // Manter na fila para tentar novamente
                    failedRecords.push(record);
                }
            }
        }
        
        this.queue = failedRecords;
        this.saveQueue();
        this.syncInProgress = false;
        this.updateUI();
        
        if (recordsToSync.length - failedRecords.length > 0) {
            console.log(`${recordsToSync.length - failedRecords.length} registro(s) sincronizado(s) com sucesso`);
            this.showNotification(`${recordsToSync.length - failedRecords.length} registro(s) sincronizado(s)`, 'success');
        }
        
        // Recarregar dados se houver sincronizações bem-sucedidas
        if (recordsToSync.length - failedRecords.length > 0) {
            if (typeof loadVolumeData === 'function') loadVolumeData();
            if (typeof loadQualityData === 'function') loadQualityData();
            if (typeof loadFinancialData === 'function') loadFinancialData();
        }
    }
    
    updateUI() {
        const indicator = document.getElementById('offline-indicator');
        const badge = document.getElementById('offline-badge');
        const offlineToggleBtn = document.getElementById('offline-toggle-btn');
        
        if (!indicator) {
            // Criar indicador se não existir
            this.createIndicator();
            return;
        }
        
        // Atualizar botão de toggle (switch)
        if (offlineToggleBtn) {
            const thumb = document.getElementById('offline-toggle-thumb');
            const iconOnline = document.getElementById('offline-icon-online');
            const iconOffline = document.getElementById('offline-icon-offline');
            const statusText = document.getElementById('offline-status-text');
            const pendingBadge = document.getElementById('offline-pending-badge');
            
            if (this.forceOffline) {
                // Modo offline ativo
                offlineToggleBtn.classList.remove('bg-gray-300');
                offlineToggleBtn.classList.add('bg-yellow-500');
                if (thumb) {
                    thumb.classList.remove('translate-x-1');
                    thumb.classList.add('translate-x-7');
                }
                offlineToggleBtn.setAttribute('aria-checked', 'true');
                
                if (iconOnline) iconOnline.classList.add('hidden');
                if (iconOffline) iconOffline.classList.remove('hidden');
                if (statusText) {
                    statusText.textContent = this.queue.length > 0 
                        ? `${this.queue.length} registro(s) aguardando sincronização`
                        : 'Modo offline ativo';
                    statusText.classList.remove('text-gray-600');
                    statusText.classList.add('text-yellow-600');
                }
                
                if (pendingBadge) {
                    if (this.queue.length > 0) {
                        pendingBadge.textContent = this.queue.length;
                        pendingBadge.classList.remove('hidden');
                    } else {
                        pendingBadge.classList.add('hidden');
                    }
                }
            } else {
                // Modo online
                offlineToggleBtn.classList.remove('bg-yellow-500');
                offlineToggleBtn.classList.add('bg-gray-300');
                if (thumb) {
                    thumb.classList.remove('translate-x-7');
                    thumb.classList.add('translate-x-1');
                }
                offlineToggleBtn.setAttribute('aria-checked', 'false');
                
                if (iconOnline) iconOnline.classList.remove('hidden');
                if (iconOffline) iconOffline.classList.add('hidden');
                if (statusText) {
                    statusText.textContent = this.queue.length > 0 
                        ? `${this.queue.length} registro(s) na fila`
                        : 'Sincronização automática ativada';
                    statusText.classList.remove('text-yellow-600');
                    statusText.classList.add('text-gray-600');
                }
                
                if (pendingBadge) {
                    if (this.queue.length > 0) {
                        pendingBadge.textContent = this.queue.length;
                        pendingBadge.classList.remove('hidden');
                    } else {
                        pendingBadge.classList.add('hidden');
                    }
                }
            }
        }
        
        // Atualizar indicador no topo (apenas quando há registros pendentes ou sincronizando)
        if (this.syncInProgress && this.queue.length > 0) {
            // Sincronizando - mostrar apenas se houver registros
            indicator.className = 'fixed top-4 right-4 z-50 bg-yellow-500 text-white px-4 py-2 rounded-lg shadow-lg flex items-center space-x-2';
            indicator.innerHTML = `
                <svg class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                <span>Sincronizando ${this.queue.length} registro(s)...</span>
            `;
            indicator.classList.remove('hidden');
        } else if (this.queue.length > 0) {
            // Há registros pendentes - mostrar indicador
            const modeInfo = this.forceOffline || !this.isOnline ? 'Offline' : 'Pendente';
            indicator.className = this.forceOffline || !this.isOnline 
                ? 'fixed top-4 right-4 z-50 bg-red-500 text-white px-4 py-2 rounded-lg shadow-lg flex items-center space-x-2'
                : 'fixed top-4 right-4 z-50 bg-blue-500 text-white px-4 py-2 rounded-lg shadow-lg flex items-center space-x-2';
            indicator.innerHTML = `
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span>${modeInfo} - ${this.queue.length} registro(s) na fila</span>
            `;
            indicator.classList.remove('hidden');
        } else {
            // Sem registros pendentes - esconder indicador
            indicator.classList.add('hidden');
        }
        
        // Atualizar badge (não usado mais, mas mantido para compatibilidade)
        if (badge) {
            if (this.queue.length > 0) {
                badge.classList.remove('hidden');
                badge.textContent = `${this.queue.length}`;
            } else {
                badge.classList.add('hidden');
            }
        }
    }
    
    createIndicator() {
        const indicator = document.createElement('div');
        indicator.id = 'offline-indicator';
        document.body.appendChild(indicator);
        this.updateUI();
    }
    
    generateId() {
        return 'offline_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }
    
    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        const bgColor = type === 'success' ? 'bg-green-500' : 
                         type === 'error' ? 'bg-red-500' : 
                         type === 'warning' ? 'bg-yellow-500' : 'bg-blue-500';
        notification.className = `fixed top-20 right-4 z-50 ${bgColor} text-white px-4 py-2 rounded-lg shadow-lg flex items-center space-x-2`;
        notification.innerHTML = `
            <span>${message}</span>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.opacity = '0';
            notification.style.transition = 'opacity 0.3s';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
    
    getQueueCount() {
        return this.queue.length;
    }
    
    clearQueue() {
        this.queue = [];
        this.saveQueue();
        this.updateUI();
    }
}

// Criar instância global
const offlineManager = new OfflineManager();

// Função auxiliar para fazer requisições com suporte offline
async function offlineFetch(endpoint, formData, type) {
    // Verificar se está em modo offline forçado
    const forceOffline = localStorage.getItem('lactech_force_offline') === 'true';
    
    if (forceOffline) {
        // Modo offline forçado - adicionar diretamente à fila
        console.log('Modo offline forçado - Adicionando à fila...');
        
        // Converter FormData para objeto
        const data = {};
        for (const [key, value] of formData.entries()) {
            data[key] = value;
        }
        
        await offlineManager.addToQueue(type, data, endpoint);
        
        return {
            success: true,
            offline: true,
            message: 'Registro salvo localmente. Será sincronizado quando o modo offline for desativado.'
        };
    }
    
    try {
        const response = await fetch(endpoint, {
            method: 'POST',
            body: formData
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        
        return await response.json();
    } catch (error) {
        // Se falhar e não estiver online, adicionar à fila
        if (!navigator.onLine || error.message.includes('Failed to fetch')) {
            console.log('Adicionando à fila offline...');
            
            // Converter FormData para objeto
            const data = {};
            for (const [key, value] of formData.entries()) {
                data[key] = value;
            }
            
            await offlineManager.addToQueue(type, data, endpoint);
            
            return {
                success: true,
                offline: true,
                message: 'Registro salvo localmente. Será sincronizado quando a conexão for restaurada.'
            };
        }
        
        throw error;
    }
}

