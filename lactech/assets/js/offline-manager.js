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
        this.lastSyncTime = 0;
        this.syncProgress = { current: 0, total: 0 };
        this.maxRetries = 5; // Aumentado de 3 para 5
        this.batchSize = 5; // Sincronizar até 5 registros por vez
        this.latencyHistory = []; // Histórico de latências para cálculo adaptativo
        this.priorityTypes = {
            'delete_all_volume': 1,
            'restore_volume': 1,
            'create_user': 2,
            'update_user': 2,
            'delete_user': 2,
            'volume_general': 3,
            'volume_animal': 3,
            'quality': 4,
            'financial': 4
        };
        
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
        
        // Verificar conexão periodicamente com intervalo adaptativo
        this.startAdaptiveSyncInterval();
        
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
    
    startAdaptiveSyncInterval() {
        // Limpar intervalo anterior se existir
        if (this.syncInterval) {
            clearInterval(this.syncInterval);
        }
        
        // Calcular intervalo baseado na qualidade da conexão e quantidade de registros
        let interval = 30000; // Padrão: 30 segundos
        
        if (this.connectionQuality === 'good' && this.queue.length > 0) {
            // Conexão boa: sincronizar mais frequentemente
            interval = 10000; // 10 segundos
        } else if (this.connectionQuality === 'poor' && this.queue.length > 0) {
            // Conexão ruim: sincronizar menos frequentemente
            interval = 60000; // 60 segundos
        } else if (this.queue.length === 0) {
            // Sem registros: verificar menos frequentemente
            interval = 120000; // 2 minutos
        }
        
        this.syncInterval = setInterval(() => {
            if (this.isOnline && !this.forceOffline && this.queue.length > 0) {
                this.sync();
            }
        }, interval);
    }
    
    calculateBackoffDelay(retries) {
        // Backoff exponencial: 2^retries segundos, máximo 60 segundos
        const delay = Math.min(Math.pow(2, retries) * 1000, 60000);
        // Adicionar jitter aleatório para evitar sincronização simultânea
        const jitter = Math.random() * 1000;
        return delay + jitter;
    }
    
    sortQueueByPriority() {
        // Ordenar fila por prioridade (menor número = maior prioridade)
        this.queue.sort((a, b) => {
            const priorityA = this.priorityTypes[a.type] || 5;
            const priorityB = this.priorityTypes[b.type] || 5;
            
            if (priorityA !== priorityB) {
                return priorityA - priorityB;
            }
            
            // Se mesma prioridade, ordenar por timestamp (mais antigo primeiro)
            return new Date(a.timestamp) - new Date(b.timestamp);
        });
    }
    
    validateRecord(record) {
        // Validar se o registro ainda é válido antes de sincronizar
        if (!record || !record.data || !record.type) {
            return false;
        }
        
        // Verificar se o registro não é muito antigo (opcional: máximo 30 dias)
        const recordAge = Date.now() - new Date(record.timestamp).getTime();
        const maxAge = 30 * 24 * 60 * 60 * 1000; // 30 dias em milissegundos
        
        if (recordAge > maxAge) {
            console.warn(`Registro ${record.id} muito antigo, removendo da fila`);
            return false;
        }
        
        return true;
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
            
            // Adicionar latência ao histórico (manter últimas 10 medições)
            this.latencyHistory.push(latency);
            if (this.latencyHistory.length > 10) {
                this.latencyHistory.shift();
            }
            
            // Calcular latência média
            const avgLatency = this.latencyHistory.reduce((a, b) => a + b, 0) / this.latencyHistory.length;
            
            if (response.ok) {
                // Usar latência média para decisão mais estável
                if (avgLatency > 3000 || latency > 5000) {
                    this.connectionQuality = 'poor';
                    // Ativar modo offline automaticamente se conexão estiver muito lenta
                    // Mas só se realmente estiver lenta repetidamente (não na primeira verificação)
                    if (!this.forceOffline && this.connectionCheckInterval && this.latencyHistory.length >= 3) {
                        // Só ativar se já tiver pelo menos 3 medições ruins
                        const recentPoor = this.latencyHistory.slice(-3).filter(l => l > 3000).length;
                        if (recentPoor >= 2) {
                            this.forceOffline = true;
                            localStorage.setItem('lactech_force_offline', 'true');
                            this.showNotification('Conexão lenta detectada. Modo offline ativado automaticamente.', 'warning');
                            this.updateUI();
                            this.startAdaptiveSyncInterval(); // Atualizar intervalo
                        }
                    }
                } else {
                    this.connectionQuality = 'good';
                    // Se estava forçado offline mas agora a conexão melhorou, desativar modo offline
                    // Mas só se não houver registros pendentes (para não interromper sincronização)
                    if (this.forceOffline && avgLatency < 1000 && this.queue.length === 0) {
                        this.toggleOfflineMode(false);
                    }
                    // Atualizar intervalo de sincronização
                    this.startAdaptiveSyncInterval();
                }
            } else {
                this.connectionQuality = 'poor';
                this.startAdaptiveSyncInterval();
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
                // Aguardar um pouco para garantir que a conexão está estável
                setTimeout(() => {
                    this.sync();
                }, 1000);
            }
            // Atualizar intervalo de sincronização
            this.startAdaptiveSyncInterval();
        }
    }
    
    loadQueue() {
        try {
            const stored = localStorage.getItem(this.storageKey);
            if (stored) {
                this.queue = JSON.parse(stored);
                // Garantir que todos os registros tenham nextRetryTime
                const now = Date.now();
                this.queue.forEach(record => {
                    if (!record.nextRetryTime || record.nextRetryTime < now) {
                        record.nextRetryTime = now;
                    }
                    if (!record.priority) {
                        record.priority = this.priorityTypes[record.type] || 5;
                    }
                });
                this.sortQueueByPriority();
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
        
        // Aguardar um pouco para garantir que a conexão está estável
        setTimeout(() => {
            // Verificar novamente se ainda está online
            if (!navigator.onLine) {
                this.isOnline = false;
                this.updateUI();
                return;
            }
            
            // Se não estiver forçado offline, tentar sincronizar
            if (!this.forceOffline) {
                this.updateUI();
                // Aguardar mais um pouco antes de sincronizar para garantir estabilidade
                setTimeout(() => {
                    if (this.queue.length > 0 && navigator.onLine && !this.forceOffline) {
                        this.sync();
                    }
                }, 2000);
                // Atualizar intervalo de sincronização
                this.startAdaptiveSyncInterval();
            } else {
                // Mesmo online, se estiver forçado offline, manter modo offline e iniciar timer
                this.updateUI();
                this.startOfflineNotificationTimer();
            }
        }, 1000);
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
            type: type, // 'volume_general', 'volume_animal', 'quality', 'financial', 'delete_all_volume', 'restore_volume', 'create_user'
            data: dataObj,
            endpoint: endpoint || './api/actions.php',
            timestamp: new Date().toISOString(),
            retries: 0,
            hasFiles: hasFiles,
            nextRetryTime: Date.now(), // Tempo para próxima tentativa
            priority: this.priorityTypes[type] || 5
        };
        
        this.queue.push(record);
        this.sortQueueByPriority(); // Ordenar por prioridade
        this.saveQueue();
        
        if (hasFiles) {
            this.showNotification('Nota: Arquivos não podem ser salvos offline. O registro será salvo sem arquivos.', 'warning');
        }
        
        // Se estiver online, tentar sincronizar imediatamente
        if (this.isOnline && !this.forceOffline) {
            await this.sync();
        }
        
        return record;
    }
    
    async sync() {
        if (this.syncInProgress || this.queue.length === 0 || !this.isOnline || this.forceOffline) {
            return;
        }
        
        // Filtrar registros que ainda não podem ser tentados (backoff)
        const now = Date.now();
        const readyRecords = this.queue.filter(r => r.nextRetryTime <= now);
        
        if (readyRecords.length === 0) {
            return; // Nenhum registro pronto para sincronizar
        }
        
        this.syncInProgress = true;
        
        // Ordenar por prioridade
        this.sortQueueByPriority();
        
        // Pegar apenas registros prontos e limitar ao batch size
        const recordsToSync = readyRecords.slice(0, this.batchSize);
        this.syncProgress = { current: 0, total: recordsToSync.length };
        
        this.updateUI();
        
        console.log(`Iniciando sincronização de ${recordsToSync.length} registro(s) de ${this.queue.length} total...`);
        
        const failedRecords = [];
        let successCount = 0;
        
        for (let i = 0; i < recordsToSync.length; i++) {
            const record = recordsToSync[i];
            this.syncProgress.current = i + 1;
            this.updateUI();
            
            // Validar registro antes de sincronizar
            if (!this.validateRecord(record)) {
                console.warn(`Registro ${record.id} inválido, removendo da fila`);
                this.queue = this.queue.filter(r => r.id !== record.id);
                continue;
            }
            
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
                    const actionMap = {
                        'volume_general': 'add_volume_general',
                        'volume_animal': 'add_volume_by_animal',
                        'quality': 'add_quality_test',
                        'financial': 'add_financial_record',
                        'delete_all_volume': 'delete_all_volume_records',
                        'restore_volume': 'restore_volume_records',
                        'create_user': 'create_user',
                        'update_user': 'update_user',
                        'delete_user': 'delete_user'
                    };
                    formData.append('action', actionMap[record.type] || record.type);
                }
                
                // Verificar se ainda está online antes de tentar sincronizar
                if (!navigator.onLine) {
                    throw new Error('Sem conexão');
                }
                
                // Timeout adaptativo baseado na qualidade da conexão
                const timeout = this.connectionQuality === 'good' ? 15000 : 30000;
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), timeout);
                
                const startTime = performance.now();
                const response = await fetch(record.endpoint, {
                    method: 'POST',
                    body: formData,
                    signal: controller.signal
                });
                const endTime = performance.now();
                
                clearTimeout(timeoutId);
                
                // Atualizar histórico de latência
                this.latencyHistory.push(endTime - startTime);
                if (this.latencyHistory.length > 10) {
                    this.latencyHistory.shift();
                }
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                
                const result = await response.json();
                
                if (result.success) {
                    // Remover da fila
                    this.queue = this.queue.filter(r => r.id !== record.id);
                    successCount++;
                    console.log(`Registro ${record.id} sincronizado com sucesso`);
                } else {
                    throw new Error(result.error || 'Erro desconhecido');
                }
            } catch (error) {
                console.error(`Erro ao sincronizar registro ${record.id}:`, error);
                record.retries++;
                
                // Se já tentou o máximo de vezes, marcar como falha permanente
                if (record.retries >= this.maxRetries) {
                    console.warn(`Registro ${record.id} falhou após ${record.retries} tentativas, removendo da fila`);
                    // Remover da fila após muitas tentativas
                    this.queue = this.queue.filter(r => r.id !== record.id);
                    this.showNotification(`Registro falhou após ${this.maxRetries} tentativas e foi removido da fila`, 'error');
                } else {
                    // Calcular próximo tempo de retry com backoff exponencial
                    record.nextRetryTime = Date.now() + this.calculateBackoffDelay(record.retries);
                    failedRecords.push(record);
                    console.log(`Registro ${record.id} será tentado novamente em ${Math.round((record.nextRetryTime - Date.now()) / 1000)} segundos`);
                }
                
                // Se perder conexão durante sincronização, parar
                if (!navigator.onLine) {
                    console.log('Conexão perdida durante sincronização. Parando...');
                    // Atualizar próximos tempos de retry para os registros restantes
                    failedRecords.forEach(r => {
                        if (!r.nextRetryTime || r.nextRetryTime <= Date.now()) {
                            r.nextRetryTime = Date.now() + this.calculateBackoffDelay(r.retries);
                        }
                    });
                    this.queue = [...this.queue.filter(r => !recordsToSync.find(rs => rs.id === r.id)), ...failedRecords];
                    this.saveQueue();
                    this.syncInProgress = false;
                    this.syncProgress = { current: 0, total: 0 };
                    this.updateUI();
                    this.showNotification('Conexão perdida. Sincronização será retomada quando a conexão for restaurada.', 'warning');
                    return;
                }
            }
        }
        
        // Atualizar fila com registros que falharam
        const remainingRecords = this.queue.filter(r => !recordsToSync.find(rs => rs.id === r.id));
        this.queue = [...remainingRecords, ...failedRecords];
        this.sortQueueByPriority();
        this.saveQueue();
        
        this.syncInProgress = false;
        this.syncProgress = { current: 0, total: 0 };
        this.lastSyncTime = Date.now();
        this.updateUI();
        
        // Atualizar intervalo de sincronização baseado na qualidade da conexão
        this.startAdaptiveSyncInterval();
        
        if (successCount > 0) {
            console.log(`${successCount} registro(s) sincronizado(s) com sucesso`);
            this.showNotification(`${successCount} registro(s) sincronizado(s) com sucesso`, 'success');
            
            // Recarregar dados após sincronização bem-sucedida
            setTimeout(() => {
                if (typeof loadVolumeData === 'function') loadVolumeData();
                if (typeof loadQualityData === 'function') loadQualityData();
                if (typeof loadFinancialData === 'function') loadFinancialData();
                if (typeof loadUsersData === 'function') loadUsersData();
                if (typeof loadDashboardData === 'function') loadDashboardData();
            }, 500);
        }
        
        if (failedRecords.length > 0) {
            console.warn(`${failedRecords.length} registro(s) falharam e serão tentados novamente`);
        }
        
        // Se ainda houver registros prontos, tentar sincronizar novamente após um pequeno delay
        if (this.queue.length > 0 && this.isOnline && !this.forceOffline) {
            const nextReady = this.queue.find(r => r.nextRetryTime <= Date.now());
            if (nextReady) {
                setTimeout(() => {
                    if (!this.syncInProgress) {
                        this.sync();
                    }
                }, 2000);
            }
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
            // Sincronizando - mostrar progresso detalhado
            const progressPercent = this.syncProgress.total > 0 
                ? Math.round((this.syncProgress.current / this.syncProgress.total) * 100)
                : 0;
            indicator.className = 'fixed top-4 right-4 z-50 bg-yellow-500 text-white px-4 py-2 rounded-lg shadow-lg flex items-center space-x-2 min-w-[280px]';
            indicator.innerHTML = `
                <svg class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                <div class="flex-1">
                    <div class="text-sm font-medium">Sincronizando ${this.syncProgress.current}/${this.syncProgress.total}...</div>
                    <div class="w-full bg-yellow-600 rounded-full h-1.5 mt-1">
                        <div class="bg-white h-1.5 rounded-full transition-all duration-300" style="width: ${progressPercent}%"></div>
                    </div>
                </div>
            `;
            indicator.classList.remove('hidden');
        } else if (this.queue.length > 0) {
            // Há registros pendentes - mostrar indicador com informações detalhadas
            const modeInfo = this.forceOffline || !this.isOnline ? 'Offline' : 'Pendente';
            const readyCount = this.queue.filter(r => r.nextRetryTime <= Date.now()).length;
            const waitingCount = this.queue.length - readyCount;
            
            indicator.className = this.forceOffline || !this.isOnline 
                ? 'fixed top-4 right-4 z-50 bg-red-500 text-white px-4 py-2 rounded-lg shadow-lg flex items-center space-x-2 min-w-[280px]'
                : 'fixed top-4 right-4 z-50 bg-blue-500 text-white px-4 py-2 rounded-lg shadow-lg flex items-center space-x-2 min-w-[280px]';
            
            let statusText = `${modeInfo} - ${this.queue.length} registro(s)`;
            if (waitingCount > 0) {
                statusText += ` (${readyCount} pronto${readyCount !== 1 ? 's' : ''}, ${waitingCount} aguardando)`;
            }
            
            indicator.innerHTML = `
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span class="text-sm">${statusText}</span>
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
    
    // Verificar se realmente está offline
    const isOffline = !navigator.onLine || forceOffline;
    
    if (isOffline) {
        // Modo offline - adicionar diretamente à fila
        console.log('Modo offline - Adicionando à fila...', type);
        
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
    
    try {
        // Tentar fazer requisição real
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 10000); // Timeout de 10 segundos
        
        const response = await fetch(endpoint, {
            method: 'POST',
            body: formData,
            signal: controller.signal
        });
        
        clearTimeout(timeoutId);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        
        return await response.json();
    } catch (error) {
        // Se falhar, verificar se é erro de conexão
        const isConnectionError = !navigator.onLine || 
                                  error.name === 'AbortError' || 
                                  error.message.includes('Failed to fetch') ||
                                  error.message.includes('network') ||
                                  error.message.includes('timeout');
        
        if (isConnectionError) {
            console.log('Erro de conexão - Adicionando à fila offline...', error);
            
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
        
        // Re-throw erros que não são de conexão
        throw error;
    }
}

// Tornar offlineFetch disponível globalmente
window.offlineFetch = offlineFetch;

