/**
 * NATIVE NOTIFICATIONS - Sistema de Notificações Nativas
 * Notificações que aparecem na tela de bloqueio e como cards
 */

class NativeNotifications {
    constructor() {
        this.notifications = [];
        this.container = null;
        this.permission = 'default';
        this.init();
    }

    async init() {
        // Criar container de notificações
        this.createContainer();
        
        // Solicitar permissões
        await this.requestPermission();
        
        // Configurar Service Worker para notificações push
        this.setupServiceWorker();
        
        // Configurar eventos de visibilidade
        this.setupVisibilityEvents();
        
        // Configurar notificações automáticas
        this.setupAutomaticNotifications();
    }

    createContainer() {
        this.container = document.createElement('div');
        this.container.className = 'notification-container';
        this.container.id = 'notificationContainer';
        document.body.appendChild(this.container);
    }

    async requestPermission() {
        if ('Notification' in window) {
            this.permission = await Notification.requestPermission();
            console.log('Permissão de notificação:', this.permission);
        }
    }

    setupServiceWorker() {
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js').then(registration => {
                console.log('Service Worker registrado para notificações');
            });
        }
    }

    setupVisibilityEvents() {
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.showLockScreenNotification();
            }
        });
    }

    setupAutomaticNotifications() {
        // Configurar apenas notificações importantes do sistema
        this.setupImportantNotifications();
    }

    setupImportantNotifications() {
        // Interceptar apenas funções CRÍTICAS do sistema
        this.interceptCriticalFunctions();
        
        // Configurar notificações de background apenas para eventos importantes
        this.setupCriticalBackgroundNotifications();
    }

    interceptCriticalFunctions() {
        // Interceptar apenas registro de produção (CRÍTICO)
        this.interceptProductionRegistration();
        
        // Interceptar solicitações de senha (CRÍTICO)
        this.interceptPasswordRequests();
        
        // Interceptar criação de usuários (CRÍTICO)
        this.interceptUserCreation();
    }

    interceptProductionRegistration() {
        // Interceptar registro de produção - APENAS ESTE É CRÍTICO
        const originalRegister = window.handleModernProductionSubmit;
        if (originalRegister) {
            window.handleModernProductionSubmit = async (...args) => {
                const result = await originalRegister.apply(this, args);
                if (result && result.success) {
                    this.showRealDeviceNotification(
                        'Nova Produção Registrada',
                        `Volume: ${result.volume || 'N/A'}L registrado com sucesso!`,
                        'production'
                    );
                }
                return result;
            };
        }
    }

    interceptPasswordRequests() {
        // Interceptar solicitações de senha - CRÍTICO
        const originalRequest = window.requestPasswordChange;
        if (originalRequest) {
            window.requestPasswordChange = async (...args) => {
                const result = await originalRequest.apply(this, args);
                if (result && result.success) {
                    this.showRealDeviceNotification(
                        'Solicitação de Senha',
                        'Nova solicitação de alteração de senha recebida',
                        'password_request'
                    );
                }
                return result;
            };
        }
    }

    interceptUserCreation() {
        // Interceptar criação de usuários - CRÍTICO
        const originalCreate = window.createUser;
        if (originalCreate) {
            window.createUser = async (...args) => {
                const result = await originalCreate.apply(this, args);
                if (result && result.success) {
                    this.showRealDeviceNotification(
                        'Novo Usuário Criado',
                        `Usuário ${result.userName || 'novo'} foi criado no sistema`,
                        'user_created'
                    );
                }
                return result;
            };
        }
    }

    setupCriticalBackgroundNotifications() {
        // Notificar apenas quando app vai para background E há dados pendentes
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                // Verificar se há dados pendentes de sincronização
                this.checkPendingData();
            }
        });
    }

    checkPendingData() {
        // Verificar se há dados pendentes de sincronização
        const pendingData = localStorage.getItem('pendingSyncData');
        if (pendingData) {
            this.showRealDeviceNotification(
                'Dados Pendentes',
                'Há dados aguardando sincronização',
                'pending_sync'
            );
        }
    }

    showNotification(options) {
        // REMOVIDO: Não mostrar mais cards dentro da página
        // Apenas notificações reais do dispositivo
        console.log('Notificação de card removida:', options.title);
        return null;
    }

    createNotificationElement(notification) {
        const element = document.createElement('div');
        element.className = `notification-card ${notification.type}-notification`;
        element.id = `notification-${notification.id}`;
        
        if (notification.persistent) {
            element.classList.add('persistent');
        }
        
        if (notification.emergency) {
            element.classList.add('emergency');
        }

        const timeStr = notification.timestamp.toLocaleTimeString('pt-BR', {
            hour: '2-digit',
            minute: '2-digit'
        });

        let actionsHTML = '';
        if (notification.actions.length > 0) {
            actionsHTML = `
                <div class="notification-actions">
                    ${notification.actions.map(action => `
                        <button class="notification-btn ${action.type}" onclick="nativeNotifications.handleAction('${notification.id}', '${action.action}')">
                            ${action.text}
                        </button>
                    `).join('')}
                </div>
            `;
        }

        let progressHTML = '';
        if (notification.progress !== null) {
            progressHTML = `
                <div class="notification-progress">
                    <div class="notification-progress-bar" style="width: ${notification.progress}%"></div>
                </div>
            `;
        }

        let imageHTML = '';
        if (notification.image) {
            imageHTML = `<img src="${notification.image}" class="notification-image" alt="Notification">`;
        }

        element.innerHTML = `
            <div class="notification-header">
                <div class="notification-icon ${notification.type}">
                    ${notification.icon}
                </div>
                <div class="notification-title">${notification.title}</div>
                <div class="notification-time">${timeStr}</div>
            </div>
            <div class="notification-content">
                ${imageHTML ? `<div class="notification-with-image">${imageHTML}<div class="notification-content">` : ''}
                <div class="notification-message">${notification.message}</div>
                ${notification.details ? `<div class="notification-details">${notification.details}</div>` : ''}
                ${imageHTML ? '</div></div>' : ''}
            </div>
            ${actionsHTML}
            ${progressHTML}
        `;

        this.container.appendChild(element);

        // Animar entrada
        setTimeout(() => {
            element.classList.add('show');
        }, 100);

        // Auto-remover após 5 segundos (exceto persistentes)
        if (!notification.persistent) {
            setTimeout(() => {
                this.removeNotification(notification.id);
            }, 5000);
        }
    }

    showNativeNotification(notification) {
        if ('Notification' in window && this.permission === 'granted') {
            const nativeNotification = new Notification(notification.title, {
                body: notification.message,
                icon: 'https://i.postimg.cc/vmrkgDcB/lactech.png',
                badge: 'https://i.postimg.cc/vmrkgDcB/lactech.png',
                tag: notification.id,
                requireInteraction: notification.persistent,
                silent: !notification.sound,
                data: {
                    id: notification.id,
                    type: notification.type
                }
            });

            nativeNotification.onclick = () => {
                window.focus();
                this.removeNotification(notification.id);
                nativeNotification.close();
            };

            // Auto-remover após 5 segundos
            setTimeout(() => {
                nativeNotification.close();
            }, 5000);
        }
    }

    showLockScreenNotification() {
        // Notificação especial para tela de bloqueio
        this.showNotification({
            type: 'system',
            title: 'LacTech Ativo',
            message: 'Sistema rodando em segundo plano',
            details: 'Você receberá notificações importantes mesmo com a tela bloqueada.',
            icon: '🔒',
            persistent: true,
            sound: true
        });
    }

    showWeatherNotification() {
        console.log('Notificação de clima removida');
        return null;
    }

    removeNotification(id) {
        const element = document.getElementById(`notification-${id}`);
        if (element) {
            element.classList.add('hide');
            setTimeout(() => {
                element.remove();
            }, 400);
        }
        
        // Remover da lista
        this.notifications = this.notifications.filter(n => n.id !== id);
    }

    handleAction(notificationId, action) {
        console.log(`Ação executada: ${action} para notificação ${notificationId}`);
        
        switch (action) {
            case 'openWeather':
                if (typeof openWeatherModal === 'function') {
                    openWeatherModal();
                }
                break;
            case 'dismiss':
                this.removeNotification(notificationId);
                break;
            default:
                console.log('Ação não reconhecida:', action);
        }
        
        this.removeNotification(notificationId);
    }

    playNotificationSound() {
        // Criar som de notificação
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();
        
        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);
        
        oscillator.frequency.setValueAtTime(800, audioContext.currentTime);
        oscillator.frequency.setValueAtTime(600, audioContext.currentTime + 0.1);
        
        gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.3);
        
        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + 0.3);
    }

    // Métodos públicos para diferentes tipos de notificação
    showSuccess(title, message, details = '') {
        console.log('Notificação de sucesso removida:', title);
        return null;
    }

    showError(title, message, details = '') {
        console.log('Notificação de erro removida:', title);
        return null;
    }

    showWarning(title, message, details = '') {
        console.log('Notificação de aviso removida:', title);
        return null;
    }

    showInfo(title, message, details = '') {
        console.log('Notificação de info removida:', title);
        return null;
    }

    showSystem(title, message, details = '') {
        console.log('Notificação de sistema removida:', title);
        return null;
    }

    showEmergency(title, message, details = '') {
        console.log('Notificação de emergência removida:', title);
        return null;
    }

    // Notificação com progresso - REMOVIDA
    showProgress(title, message, progress) {
        console.log('Notificação de progresso removida:', title);
        return null;
    }

    // Notificação com imagem - REMOVIDA
    showImageNotification(title, message, imageUrl) {
        console.log('Notificação com imagem removida:', title);
        return null;
    }

    // Notificações específicas do sistema - REMOVIDAS (usar apenas notificações reais)
    showLoginNotification(result) {
        console.log('Notificação de login removida');
        return null;
    }

    showLogoutNotification() {
        console.log('Notificação de logout removida');
        return null;
    }

    showProductionNotification(result) {
        console.log('Notificação de produção removida');
        return null;
    }

    showDataSaveNotification(result) {
        console.log('Notificação de dados salvos removida');
        return null;
    }

    showBackgroundNotification() {
        console.log('Notificação de background removida');
        return null;
    }

    showForegroundNotification() {
        console.log('Notificação de foreground removida');
        return null;
    }

    showDataChangeNotification(key, value) {
        console.log('Notificação de mudança de dados removida');
        return null;
    }

    showErrorNotification(title, message) {
        console.log('Notificação de erro removida');
        return null;
    }

    // Notificações para ações específicas do LacTech - REMOVIDAS (usar apenas notificações reais)
    showVolumeNotification(volume, date) {
        console.log('Notificação de volume removida');
        return null;
    }

    showQualityNotification(quality, notes) {
        console.log('Notificação de qualidade removida');
        return null;
    }

    showSyncNotification(syncResult) {
        console.log('Notificação de sincronização removida');
        return null;
    }

    showOfflineNotification() {
        console.log('Notificação offline removida');
        return null;
    }

    showOnlineNotification() {
        console.log('Notificação online removida');
        return null;
    }

    showMaintenanceNotification() {
        console.log('Notificação de manutenção removida');
        return null;
    }

    showUpdateNotification(version) {
        console.log('Notificação de atualização removida');
        return null;
    }

    showSecurityNotification() {
        console.log('Notificação de segurança removida');
        return null;
    }

    showBackupNotification() {
        console.log('Notificação de backup removida');
        return null;
    }

    showReportNotification(reportType) {
        console.log('Notificação de relatório removida');
        return null;
    }

    showUserActivityNotification(activity) {
        console.log('Notificação de atividade removida');
        return null;
    }

    showDataExportNotification() {
        console.log('Notificação de exportação removida');
        return null;
    }

    showDataImportNotification() {
        console.log('Notificação de importação removida');
        return null;
    }

    showSystemHealthNotification() {
        console.log('Notificação de saúde do sistema removida');
        return null;
    }

    showAlertNotification(alertType, message) {
        console.log('Notificação de alerta removida');
        return null;
    }

    // ==================== NOTIFICAÇÕES REAIS DO DISPOSITIVO ====================
    
    showRealDeviceNotification(title, message, type = 'info') {
        // Verificar se notificações são suportadas
        if (!('Notification' in window)) {
            console.log('Notificações não suportadas neste navegador');
            return;
        }

        // Verificar se já temos permissão
        if (Notification.permission === 'granted') {
            this.createRealNotification(title, message, type);
        } else if (Notification.permission !== 'denied') {
            // Solicitar permissão
            Notification.requestPermission().then(permission => {
                if (permission === 'granted') {
                    this.createRealNotification(title, message, type);
                }
            });
        }
    }

    createRealNotification(title, message, type) {
        // Configurações baseadas no tipo
        const notificationConfig = this.getNotificationConfig(type);
        
        // Criar notificação real do dispositivo
        const notification = new Notification(title, {
            body: message,
            icon: notificationConfig.icon,
            badge: notificationConfig.badge,
            tag: type, // Para agrupar notificações do mesmo tipo
            requireInteraction: notificationConfig.requireInteraction,
            silent: notificationConfig.silent,
            vibrate: notificationConfig.vibrate,
            data: {
                type: type,
                timestamp: Date.now(),
                url: window.location.href
            }
        });

        // Configurar ações da notificação
        notification.onclick = () => {
            window.focus();
            notification.close();
        };

        // Auto-fechar após tempo específico
        setTimeout(() => {
            notification.close();
        }, notificationConfig.autoClose);

        // Salvar notificação para histórico
        this.saveNotificationToHistory(title, message, type);
    }

    getNotificationConfig(type) {
        const configs = {
            'production': {
                icon: '/assets/img/lactech-logo.png',
                badge: '/assets/img/lactech-logo.png',
                requireInteraction: true,
                silent: false,
                vibrate: [200, 100, 200],
                autoClose: 8000
            },
            'password_request': {
                icon: '/assets/img/lactech-logo.png',
                badge: '/assets/img/lactech-logo.png',
                requireInteraction: true,
                silent: false,
                vibrate: [300, 100, 300],
                autoClose: 10000
            },
            'user_created': {
                icon: '/assets/img/lactech-logo.png',
                badge: '/assets/img/lactech-logo.png',
                requireInteraction: false,
                silent: false,
                vibrate: [200, 100, 200],
                autoClose: 6000
            },
            'pending_sync': {
                icon: '/assets/img/lactech-logo.png',
                badge: '/assets/img/lactech-logo.png',
                requireInteraction: false,
                silent: true,
                vibrate: [100, 100, 100],
                autoClose: 5000
            },
            'default': {
                icon: '/assets/img/lactech-logo.png',
                badge: '/assets/img/lactech-logo.png',
                requireInteraction: false,
                silent: false,
                vibrate: [200],
                autoClose: 5000
            }
        };

        return configs[type] || configs['default'];
    }

    saveNotificationToHistory(title, message, type) {
        // Salvar no localStorage para histórico
        const notificationHistory = JSON.parse(localStorage.getItem('notificationHistory') || '[]');
        
        notificationHistory.push({
            title,
            message,
            type,
            timestamp: Date.now(),
            read: false
        });

        // Manter apenas as últimas 50 notificações
        if (notificationHistory.length > 50) {
            notificationHistory.splice(0, notificationHistory.length - 50);
        }

        localStorage.setItem('notificationHistory', JSON.stringify(notificationHistory));
    }

    // Função para solicitar permissão de notificações
    async requestNotificationPermission() {
        if (!('Notification' in window)) {
            console.log('Notificações não suportadas neste navegador');
            return false;
        }

        if (Notification.permission === 'granted') {
            return true;
        }

        if (Notification.permission === 'denied') {
            console.log('Permissão de notificações negada pelo usuário');
            return false;
        }

        const permission = await Notification.requestPermission();
        return permission === 'granted';
    }

    // Função para verificar se notificações estão habilitadas
    isNotificationEnabled() {
        return 'Notification' in window && Notification.permission === 'granted';
    }

    // Função para mostrar notificação de teste
    showTestNotification() {
        this.showRealDeviceNotification(
            'LacTech - Teste',
            'Notificações estão funcionando corretamente!',
            'default'
        );
    }
}

// Instância global
let nativeNotifications = null;

// Inicializar quando DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    nativeNotifications = new NativeNotifications();
    window.nativeNotifications = nativeNotifications;
});

// Exportar para uso global
window.NativeNotifications = NativeNotifications;
