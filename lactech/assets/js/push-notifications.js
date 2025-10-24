// ============================================================
// PUSH NOTIFICATIONS - LACTECH PWA
// Sistema de notificações push em tempo real
// Superior ao FarmTell Milk
// ============================================================

const PushNotificationManager = {
    // Estado
    permission: 'default',
    subscription: null,
    
    // Inicializar
    async init() {
        console.log('🔔 Inicializando Push Notifications...');
        
        // Verificar suporte
        if (!('serviceWorker' in navigator)) {
            console.warn('⚠️ Service Worker não suportado');
            return false;
        }
        
        if (!('PushManager' in window)) {
            console.warn('⚠️ Push API não suportada');
            return false;
        }
        
        // Verificar permissão atual
        this.permission = Notification.permission;
        console.log('🔔 Permissão atual:', this.permission);
        
        // Se já tem permissão, registrar
        if (this.permission === 'granted') {
            await this.subscribe();
        }
        
        return true;
    },
    
    // Solicitar permissão
    async requestPermission() {
        if (this.permission === 'granted') {
            console.log('✅ Permissão já concedida');
            return true;
        }
        
        try {
            const permission = await Notification.requestPermission();
            this.permission = permission;
            
            if (permission === 'granted') {
                console.log('✅ Permissão concedida!');
                await this.subscribe();
                
                // Mostrar notificação de boas-vindas
                this.showLocalNotification(
                    'Notificações Ativadas!',
                    'Você receberá alertas importantes sobre o rebanho.',
                    'success'
                );
                
                return true;
            } else if (permission === 'denied') {
                console.log('❌ Permissão negada');
                return false;
            } else {
                console.log('⚠️ Permissão ignorada');
                return false;
            }
        } catch (error) {
            console.error('❌ Erro ao solicitar permissão:', error);
            return false;
        }
    },
    
    // Inscrever para push notifications
    async subscribe() {
        try {
            const registration = await navigator.serviceWorker.ready;
            
            // Verificar se já está inscrito
            const existingSubscription = await registration.pushManager.getSubscription();
            if (existingSubscription) {
                this.subscription = existingSubscription;
                console.log('✅ Já inscrito para push');
                return existingSubscription;
            }
            
            // VAPID public key (você precisará gerar uma)
            // Por enquanto, usar placeholder
            const vapidPublicKey = 'BP4o6OLxNBPdnAjKmY2yO5VD9g2xTLPcNOLdBZEJvKLpPVDqHXQKj4_nLqCmZvKcH6N5BsC5p9QvGhJmPcVJ0J4';
            
            // Criar nova inscrição
            const subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: this.urlBase64ToUint8Array(vapidPublicKey)
            });
            
            this.subscription = subscription;
            console.log('✅ Inscrito para push!', subscription);
            
            // Salvar no servidor
            await this.saveSubscription(subscription);
            
            return subscription;
        } catch (error) {
            console.error('❌ Erro ao inscrever:', error);
            return null;
        }
    },
    
    // Salvar inscrição no servidor
    async saveSubscription(subscription) {
        try {
            const response = await fetch('api/preferences.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'set',
                    preference_key: 'push_subscription',
                    preference_value: JSON.stringify(subscription),
                    data_type: 'json',
                    category: 'notifications'
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                console.log('✅ Inscrição salva no servidor');
            }
        } catch (error) {
            console.error('❌ Erro ao salvar inscrição:', error);
        }
    },
    
    // Cancelar inscrição
    async unsubscribe() {
        if (!this.subscription) {
            console.log('⚠️ Não há inscrição para cancelar');
            return;
        }
        
        try {
            await this.subscription.unsubscribe();
            this.subscription = null;
            console.log('✅ Inscrição cancelada');
            
            // Remover do servidor
            await fetch('api/preferences.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'set',
                    preference_key: 'push_subscription',
                    preference_value: 'null',
                    data_type: 'string',
                    category: 'notifications'
                })
            });
        } catch (error) {
            console.error('❌ Erro ao cancelar inscrição:', error);
        }
    },
    
    // Mostrar notificação local (sem servidor)
    showLocalNotification(title, body, type = 'info') {
        if (this.permission !== 'granted') {
            console.warn('⚠️ Sem permissão para notificações');
            return;
        }
        
        const icons = {
            'success': '✅',
            'error': '❌',
            'warning': '⚠️',
            'info': 'ℹ️',
            'urgent': '🚨'
        };
        
        const options = {
            body: body,
            icon: '/assets/img/lactech-logo.png',
            badge: '/assets/img/lactech-logo.png',
            tag: 'lactech-' + Date.now(),
            requireInteraction: type === 'urgent',
            vibrate: type === 'urgent' ? [300, 100, 300, 100, 300] : [200, 100, 200],
            data: { type: type, timestamp: Date.now() }
        };
        
        navigator.serviceWorker.ready.then((registration) => {
            registration.showNotification(icons[type] + ' ' + title, options);
        });
    },
    
    // Converter VAPID key
    urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding)
            .replace(/\-/g, '+')
            .replace(/_/g, '/');
        
        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);
        
        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    },
    
    // Testar notificação
    async test() {
        this.showLocalNotification(
            'Teste de Notificação',
            'Se você viu isso, as notificações estão funcionando! 🎉',
            'success'
        );
    }
};

// Exportar globalmente
window.PushNotificationManager = PushNotificationManager;

// Auto-inicializar
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        PushNotificationManager.init();
    });
} else {
    PushNotificationManager.init();
}

console.log('✅ Push Notification Manager carregado');

