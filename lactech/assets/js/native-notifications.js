/**
 * Native Notifications - Lactech
 * Sistema de notificações nativas do navegador
 */

class NativeNotifications {
    constructor() {
        this.permission = Notification.permission;
        this.notifications = new Map();
        this.init();
    }

    init() {
        console.log('🔔 Native Notifications inicializado');
        this.setupPermissionHandling();
        this.setupNotificationEvents();
        this.setupServiceWorker();
    }

    /**
     * Configurar tratamento de permissões
     */
    setupPermissionHandling() {
        // Verificar permissão atual
        if (this.permission === 'default') {
            this.requestPermission();
        }
    }

    /**
     * Solicitar permissão para notificações
     */
    async requestPermission() {
        try {
            const permission = await Notification.requestPermission();
            this.permission = permission;
            
            if (permission === 'granted') {
                console.log('✅ Permissão para notificações concedida');
                this.showWelcomeNotification();
            } else {
                console.log('❌ Permissão para notificações negada');
            }
            
            return permission;
        } catch (error) {
            console.error('Erro ao solicitar permissão:', error);
            return 'denied';
        }
    }

    /**
     * Solicitar permissão para notificações (alias)
     */
    async requestNotificationPermission() {
        return this.requestPermission();
    }

    /**
     * Mostrar notificação de boas-vindas
     */
    showWelcomeNotification() {
        this.show('Bem-vindo ao Lactech!', {
            body: 'Notificações ativadas com sucesso',
            icon: 'assets/img/lactech-logo.png',
            tag: 'welcome'
        });
    }

    /**
     * Mostrar notificação
     */
    show(title, options = {}) {
        if (this.permission !== 'granted') {
            console.warn('Notificações não permitidas');
            return null;
        }

        const defaultOptions = {
            body: '',
            icon: 'assets/img/lactech-logo.png',
            badge: 'assets/img/lactech-logo.png',
            tag: 'lactech-notification',
            requireInteraction: false,
            silent: false,
            timestamp: Date.now()
        };

        const notificationOptions = { ...defaultOptions, ...options };
        
        try {
            const notification = new Notification(title, notificationOptions);
            
            // Armazenar referência
            const id = notificationOptions.tag || Date.now().toString();
            this.notifications.set(id, notification);
            
            // Configurar eventos
            this.setupNotificationEventHandlers(notification, id);
            
            // Auto-remover após 5 segundos se não for persistente
            if (!notificationOptions.requireInteraction) {
                setTimeout(() => {
                    notification.close();
                }, 5000);
            }
            
            return notification;
        } catch (error) {
            console.error('Erro ao criar notificação:', error);
            return null;
        }
    }

    /**
     * Configurar eventos da notificação
     */
    setupNotificationEventHandlers(notification, id) {
        notification.onclick = (event) => {
            event.preventDefault();
            this.handleNotificationClick(id);
        };

        notification.onclose = () => {
            this.handleNotificationClose(id);
        };

        notification.onerror = (error) => {
            console.error('Erro na notificação:', error);
        };
    }

    /**
     * Tratar clique na notificação
     */
    handleNotificationClick(id) {
        console.log('Notificação clicada:', id);
        
        // Focar na janela
        window.focus();
        
        // Fechar a notificação
        const notification = this.notifications.get(id);
        if (notification) {
            notification.close();
        }
        
        // Executar ação específica baseada no ID
        this.executeNotificationAction(id);
    }

    /**
     * Tratar fechamento da notificação
     */
    handleNotificationClose(id) {
        console.log('Notificação fechada:', id);
        this.notifications.delete(id);
    }

    /**
     * Executar ação da notificação
     */
    executeNotificationAction(id) {
        // Mapear IDs para ações específicas
        const actions = {
            'welcome': () => {
                console.log('Ação: Boas-vindas');
            },
            'alert': () => {
                console.log('Ação: Alerta');
                // Focar em elemento específico se necessário
            },
            'reminder': () => {
                console.log('Ação: Lembrete');
                // Abrir modal ou seção específica
            }
        };
        
        const action = actions[id];
        if (action) {
            action();
        }
    }

    /**
     * Configurar eventos de notificação
     */
    setupNotificationEvents() {
        // Escutar mudanças de permissão
        if ('permissions' in navigator) {
            navigator.permissions.query({ name: 'notifications' }).then(result => {
                result.onchange = () => {
                    this.permission = result.state;
                    console.log('Permissão de notificações alterada:', result.state);
                };
            });
        }
    }

    /**
     * Configurar Service Worker
     */
    setupServiceWorker() {
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.ready.then(registration => {
                console.log('Service Worker pronto para notificações');
            });
        }
    }

    /**
     * Notificação de alerta
     */
    alert(title, message) {
        return this.show(title, {
            body: message,
            tag: 'alert',
            requireInteraction: true,
            icon: 'assets/img/alert-icon.png'
        });
    }

    /**
     * Notificação de sucesso
     */
    success(title, message) {
        return this.show(title, {
            body: message,
            tag: 'success',
            icon: 'assets/img/success-icon.png'
        });
    }

    /**
     * Notificação de erro
     */
    error(title, message) {
        return this.show(title, {
            body: message,
            tag: 'error',
            requireInteraction: true,
            icon: 'assets/img/error-icon.png'
        });
    }

    /**
     * Notificação de lembrete
     */
    reminder(title, message, delay = 0) {
        setTimeout(() => {
            return this.show(title, {
                body: message,
                tag: 'reminder',
                requireInteraction: true,
                icon: 'assets/img/reminder-icon.png'
            });
        }, delay);
    }

    /**
     * Notificação de coleta de leite
     */
    milkCollection(volume, producer) {
        return this.show('Coleta de Leite Registrada', {
            body: `${volume}L coletados de ${producer}`,
            tag: 'milk-collection',
            icon: 'assets/img/milk-icon.png'
        });
    }

    /**
     * Notificação de teste de qualidade
     */
    qualityTest(result) {
        return this.show('Teste de Qualidade Concluído', {
            body: `Gordura: ${result.fat}% | Proteína: ${result.protein}%`,
            tag: 'quality-test',
            icon: 'assets/img/quality-icon.png'
        });
    }

    /**
     * Fechar todas as notificações
     */
    closeAll() {
        this.notifications.forEach((notification, id) => {
            notification.close();
        });
        this.notifications.clear();
    }

    /**
     * Fechar notificação específica
     */
    close(id) {
        const notification = this.notifications.get(id);
        if (notification) {
            notification.close();
        }
    }

    /**
     * Verificar se notificações são suportadas
     */
    isSupported() {
        return 'Notification' in window;
    }

    /**
     * Verificar se tem permissão
     */
    hasPermission() {
        return this.permission === 'granted';
    }

    /**
     * Obter estatísticas
     */
    getStats() {
        return {
            permission: this.permission,
            activeNotifications: this.notifications.size,
            supported: this.isSupported()
        };
    }
}

// Inicializar Native Notifications
document.addEventListener('DOMContentLoaded', () => {
    window.nativeNotifications = new NativeNotifications();
});

// Exportar para uso global
window.NativeNotifications = NativeNotifications;
