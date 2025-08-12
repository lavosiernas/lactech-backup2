// PWA Manager - Gerencia funcionalidades da Progressive Web App
class PWAManager {
    constructor() {
        this.deferredPrompt = null;
        this.installButton = null;
        this.isInstalled = false;
        this.init();
    }

    init() {
        this.checkInstallation();
        this.setupEventListeners();
        this.registerServiceWorker();
        this.checkForUpdates();
    }

    // Verifica se o app já está instalado
    checkInstallation() {
        if (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches) {
            this.isInstalled = true;
            console.log('PWA: App já está instalado');
            this.hideInstallButton();
        }
    }

    // Configura listeners de eventos
    setupEventListeners() {
        // Captura o evento beforeinstallprompt
        window.addEventListener('beforeinstallprompt', (e) => {
            console.log('PWA: Evento beforeinstallprompt capturado');
            e.preventDefault();
            this.deferredPrompt = e;
            this.showInstallButton();
        });

        // Detecta quando o app é instalado
        window.addEventListener('appinstalled', (evt) => {
            console.log('PWA: App instalado com sucesso');
            this.isInstalled = true;
            this.hideInstallButton();
            this.showInstallationSuccess();
        });

        // Detecta mudanças na conectividade
        window.addEventListener('online', () => {
            this.updateOnlineStatus(true);
        });

        window.addEventListener('offline', () => {
            this.updateOnlineStatus(false);
        });
    }

    // Registra o service worker
    async registerServiceWorker() {
        if ('serviceWorker' in navigator) {
            try {
                const registration = await navigator.serviceWorker.register('/sw.js');
                console.log('PWA: Service Worker registrado com sucesso:', registration);

                // Verifica atualizações
                registration.addEventListener('updatefound', () => {
                    const newWorker = registration.installing;
                    newWorker.addEventListener('statechange', () => {
                        if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                            this.showUpdateAvailable();
                        }
                    });
                });

            } catch (error) {
                console.error('PWA: Erro ao registrar Service Worker:', error);
            }
        } else {
            console.log('PWA: Service Worker não suportado');
        }
    }

    // Mostra botão de instalação
    showInstallButton() {
        // Cria botão de instalação se não existir
        if (!this.installButton) {
            this.installButton = this.createInstallButton();
            document.body.appendChild(this.installButton);
        }
        this.installButton.style.display = 'block';
    }

    // Esconde botão de instalação
    hideInstallButton() {
        if (this.installButton) {
            this.installButton.style.display = 'none';
        }
    }

    // Cria botão de instalação
    createInstallButton() {
        const button = document.createElement('div');
        button.id = 'pwa-install-button';
        button.innerHTML = `
            <div class="fixed bottom-4 right-4 z-50">
                <button class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg shadow-lg flex items-center gap-2 transition-all duration-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                    </svg>
                    Instalar App
                </button>
            </div>
        `;

        button.querySelector('button').addEventListener('click', () => {
            this.installApp();
        });

        return button;
    }

    // Instala o app
    async installApp() {
        if (this.deferredPrompt) {
            this.deferredPrompt.prompt();
            const { outcome } = await this.deferredPrompt.userChoice;
            
            if (outcome === 'accepted') {
                console.log('PWA: Usuário aceitou a instalação');
            } else {
                console.log('PWA: Usuário recusou a instalação');
            }
            
            this.deferredPrompt = null;
            this.hideInstallButton();
        }
    }

    // Mostra mensagem de sucesso na instalação
    showInstallationSuccess() {
        this.showNotification('LacTech instalado com sucesso! 🎉', 'success');
    }

    // Mostra notificação de atualização disponível
    showUpdateAvailable() {
        this.showNotification('Nova versão disponível! Clique para atualizar.', 'info', () => {
            this.updateApp();
        });
    }

    // Atualiza o app
    updateApp() {
        if (navigator.serviceWorker.controller) {
            navigator.serviceWorker.controller.postMessage({ type: 'SKIP_WAITING' });
            window.location.reload();
        }
    }

    // Verifica atualizações
    checkForUpdates() {
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.getRegistration().then(registration => {
                if (registration) {
                    registration.update();
                }
            });
        }
    }

    // Atualiza status online/offline
    updateOnlineStatus(isOnline) {
        if (isOnline) {
            this.showNotification('Conexão restaurada', 'success');
        } else {
            this.showNotification('Modo offline ativo', 'warning');
        }
    }

    // Mostra notificação
    showNotification(message, type = 'info', callback = null) {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg transition-all duration-300 transform translate-x-full`;
        
        const bgColor = {
            'success': 'bg-green-500',
            'error': 'bg-red-500',
            'warning': 'bg-yellow-500',
            'info': 'bg-blue-500'
        }[type] || 'bg-blue-500';

        notification.className += ` ${bgColor} text-white`;
        notification.innerHTML = `
            <div class="flex items-center gap-2">
                <span>${message}</span>
                <button class="ml-2 hover:opacity-75">×</button>
            </div>
        `;

        document.body.appendChild(notification);

        // Anima entrada
        setTimeout(() => {
            notification.classList.remove('translate-x-full');
        }, 100);

        // Remove após 5 segundos
        setTimeout(() => {
            notification.classList.add('translate-x-full');
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 5000);

        // Botão fechar
        notification.querySelector('button').addEventListener('click', () => {
            notification.classList.add('translate-x-full');
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        });

        // Callback se fornecido
        if (callback) {
            notification.addEventListener('click', callback);
        }
    }

    // Solicita permissão para notificações
    async requestNotificationPermission() {
        if ('Notification' in window) {
            const permission = await Notification.requestPermission();
            if (permission === 'granted') {
                console.log('PWA: Permissão de notificação concedida');
                return true;
            } else {
                console.log('PWA: Permissão de notificação negada');
                return false;
            }
        }
        return false;
    }

    // Envia notificação push
    sendNotification(title, options = {}) {
        if ('Notification' in window && Notification.permission === 'granted') {
            const notification = new Notification(title, {
                icon: 'https://i.postimg.cc/vmrkgDcB/lactech.png',
                badge: 'https://i.postimg.cc/vmrkgDcB/lactech.png',
                ...options
            });

            notification.onclick = () => {
                window.focus();
                notification.close();
            };

            return notification;
        }
    }

    // Adiciona à tela inicial (iOS)
    addToHomeScreen() {
        if (navigator.standalone === false) {
            this.showNotification('Toque no ícone de compartilhar e selecione "Adicionar à Tela Inicial"', 'info');
        }
    }
}

// Inicializa o PWA Manager quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    window.pwaManager = new PWAManager();
});

// Exporta para uso global
window.PWAManager = PWAManager;
