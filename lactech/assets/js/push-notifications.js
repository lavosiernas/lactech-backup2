/**
 * Push Notifications - Lactech
 * Sistema de notificações push
 */

class PushNotifications {
    constructor() {
        this.registration = null;
        this.subscription = null;
        this.isSupported = 'serviceWorker' in navigator && 'PushManager' in window;
        this.init();
    }

    init() {
        if (!this.isSupported) {
            console.warn('Push notifications não suportadas neste navegador');
            return;
        }

        console.log('🔔 Push Notifications inicializado');
        this.setupServiceWorker();
        this.setupPushManager();
    }

    /**
     * Configurar Service Worker
     */
    async setupServiceWorker() {
        try {
            this.registration = await navigator.serviceWorker.register('/sw.js');
            console.log('Service Worker registrado:', this.registration);
        } catch (error) {
            console.error('Erro ao registrar Service Worker:', error);
        }
    }

    /**
     * Configurar Push Manager
     */
    async setupPushManager() {
        if (!this.registration) {
            console.warn('Service Worker não registrado');
            return;
        }

        try {
            this.subscription = await this.registration.pushManager.getSubscription();
            
            if (this.subscription) {
                console.log('Push subscription encontrada:', this.subscription);
            } else {
                console.log('Nenhuma push subscription encontrada');
            }
        } catch (error) {
            console.error('Erro ao obter push subscription:', error);
        }
    }

    /**
     * Solicitar permissão para push notifications
     */
    async requestPermission() {
        if (!this.isSupported) {
            throw new Error('Push notifications não suportadas');
        }

        try {
            const permission = await Notification.requestPermission();
            
            if (permission === 'granted') {
                console.log('Permissão para push notifications concedida');
                return true;
            } else {
                console.log('Permissão para push notifications negada');
                return false;
            }
        } catch (error) {
            console.error('Erro ao solicitar permissão:', error);
            return false;
        }
    }

    /**
     * Subscrever para push notifications
     */
    async subscribe() {
        if (!this.registration) {
            throw new Error('Service Worker não registrado');
        }

        try {
            const subscription = await this.registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: this.urlBase64ToUint8Array(this.getVapidPublicKey())
            });

            this.subscription = subscription;
            console.log('Push subscription criada:', subscription);
            
            // Enviar subscription para o servidor
            await this.sendSubscriptionToServer(subscription);
            
            return subscription;
        } catch (error) {
            console.error('Erro ao criar push subscription:', error);
            throw error;
        }
    }

    /**
     * Desinscrever de push notifications
     */
    async unsubscribe() {
        if (!this.subscription) {
            console.log('Nenhuma subscription para remover');
            return;
        }

        try {
            const result = await this.subscription.unsubscribe();
            
            if (result) {
                console.log('Push subscription removida');
                this.subscription = null;
                
                // Notificar servidor sobre a remoção
                await this.removeSubscriptionFromServer();
            }
            
            return result;
        } catch (error) {
            console.error('Erro ao remover push subscription:', error);
            throw error;
        }
    }

    /**
     * Enviar subscription para o servidor
     */
    async sendSubscriptionToServer(subscription) {
        try {
            const response = await fetch('api/push-subscription.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    subscription: subscription,
                    user_id: this.getCurrentUserId()
                })
            });

            if (response.ok) {
                console.log('Subscription enviada para o servidor');
            } else {
                console.error('Erro ao enviar subscription para o servidor');
            }
        } catch (error) {
            console.error('Erro ao enviar subscription:', error);
        }
    }

    /**
     * Remover subscription do servidor
     */
    async removeSubscriptionFromServer() {
        try {
            const response = await fetch('api/push-subscription.php', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    user_id: this.getCurrentUserId()
                })
            });

            if (response.ok) {
                console.log('Subscription removida do servidor');
            } else {
                console.error('Erro ao remover subscription do servidor');
            }
        } catch (error) {
            console.error('Erro ao remover subscription:', error);
        }
    }

    /**
     * Obter chave pública VAPID
     */
    getVapidPublicKey() {
        // Esta chave deve ser configurada no servidor
        return 'BEl62iUYgUivxIkv69yViEuiBIa40HI0QYyXpDxQ0YgLcFf4U8cWtF1Q2f3g4h5i6j7k8l9m0n1o2p3q4r5s6t7u8v9w0x1y2z3';
    }

    /**
     * Converter chave base64 para Uint8Array
     */
    urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding)
            .replace(/-/g, '+')
            .replace(/_/g, '/');

        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);

        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }

    /**
     * Obter ID do usuário atual
     */
    getCurrentUserId() {
        // Implementar lógica para obter ID do usuário
        return window.currentUserId || null;
    }

    /**
     * Verificar se está inscrito
     */
    isSubscribed() {
        return this.subscription !== null;
    }

    /**
     * Obter status da subscription
     */
    getSubscriptionStatus() {
        return {
            isSupported: this.isSupported,
            isSubscribed: this.isSubscribed(),
            hasPermission: Notification.permission === 'granted',
            subscription: this.subscription
        };
    }

    /**
     * Configurar listeners de push
     */
    setupPushListeners() {
        if (!this.registration) {
            return;
        }

        // Listener para mensagens push
        this.registration.addEventListener('message', (event) => {
            console.log('Mensagem push recebida:', event.data);
            this.handlePushMessage(event.data);
        });

        // Listener para atualizações do service worker
        this.registration.addEventListener('updatefound', () => {
            console.log('Service Worker atualizado');
        });
    }

    /**
     * Tratar mensagem push
     */
    handlePushMessage(data) {
        try {
            const message = typeof data === 'string' ? JSON.parse(data) : data;
            
            // Mostrar notificação
            if (message.title && message.body) {
                this.showNotification(message.title, {
                    body: message.body,
                    icon: message.icon || 'assets/img/lactech-logo.png',
                    badge: message.badge || 'assets/img/lactech-logo.png',
                    tag: message.tag || 'lactech-push',
                    data: message.data
                });
            }
        } catch (error) {
            console.error('Erro ao processar mensagem push:', error);
        }
    }

    /**
     * Mostrar notificação
     */
    showNotification(title, options = {}) {
        if (Notification.permission !== 'granted') {
            console.warn('Permissão para notificações não concedida');
            return;
        }

        const notification = new Notification(title, {
            body: options.body || '',
            icon: options.icon || 'assets/img/lactech-logo.png',
            badge: options.badge || 'assets/img/lactech-logo.png',
            tag: options.tag || 'lactech-notification',
            data: options.data || {}
        });

        // Configurar eventos
        notification.onclick = (event) => {
            event.preventDefault();
            this.handleNotificationClick(notification, options.data);
        };

        notification.onclose = () => {
            console.log('Notificação fechada');
        };

        notification.onerror = (error) => {
            console.error('Erro na notificação:', error);
        };

        return notification;
    }

    /**
     * Tratar clique na notificação
     */
    handleNotificationClick(notification, data) {
        console.log('Notificação clicada:', data);
        
        // Focar na janela
        window.focus();
        
        // Fechar a notificação
        notification.close();
        
        // Executar ação baseada nos dados
        if (data && data.action) {
            this.executeNotificationAction(data.action, data);
        }
    }

    /**
     * Executar ação da notificação
     */
    executeNotificationAction(action, data) {
        switch (action) {
            case 'open_dashboard':
                window.location.href = 'gerente.php';
                break;
            case 'open_volume':
                window.location.href = 'gerente.php#volume';
                break;
            case 'open_quality':
                window.location.href = 'gerente.php#quality';
                break;
            case 'open_financial':
                window.location.href = 'gerente.php#financial';
                break;
            default:
                console.log('Ação não reconhecida:', action);
        }
    }

    /**
     * Testar push notifications
     */
    async test() {
        try {
            console.log('🧪 Testando push notifications...');
            
            const status = this.getSubscriptionStatus();
            console.log('Status:', status);
            
            if (!status.isSubscribed) {
                console.log('Solicitando permissão...');
                const permission = await this.requestPermission();
                
                if (permission) {
                    console.log('Criando subscription...');
                    await this.subscribe();
                }
            }
            
            console.log('✅ Teste de push notifications concluído');
        } catch (error) {
            console.error('❌ Erro no teste de push notifications:', error);
        }
    }
}

// Inicializar Push Notifications
document.addEventListener('DOMContentLoaded', () => {
    window.pushNotifications = new PushNotifications();
});

// Exportar para uso global
window.PushNotifications = PushNotifications;

