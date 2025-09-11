/**
 * OFFLINE LOADING SYSTEM - LacTech
 * Sistema de loading com animação do servidor
 */

class OfflineLoadingSystem {
    constructor() {
        this.loadingContainer = null;
        this.connectionStatus = null;
        this.isLoading = false;
        this.loadingDuration = 6000; // 6 segundos
        this.progressInterval = null;
        this.timerInterval = null;
        
        this.init();
    }

    init() {
        this.createLoadingContainer();
        this.createConnectionStatus();
        this.setupEventListeners();
    }

    createLoadingContainer() {
        // Criar container do loading
        this.loadingContainer = document.createElement('div');
        this.loadingContainer.id = 'offlineLoadingContainer';
        this.loadingContainer.className = 'offline-loading-container hidden';
        
        // SVG do servidor (seu código)
        const svgContent = `
            <svg id="svg_svg" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 477 578" height="578" width="477">
                <!-- Seu SVG completo aqui - truncado para brevidade -->
                <g filter="url(#filter0_i_163_1030)">
                    <path fill="#E9E9E9" d="M235.036 304.223C236.949 303.118 240.051 303.118 241.964 304.223L470.072 435.921C473.898 438.13 473.898 441.712 470.072 443.921L247.16 572.619C242.377 575.38 234.623 575.38 229.84 572.619L6.92817 443.921C3.10183 441.712 3.10184 438.13 6.92817 435.921L235.036 304.223Z"></path>
                </g>
                <!-- Mais elementos do SVG... -->
            </svg>
        `;
        
        this.loadingContainer.innerHTML = `
            ${svgContent}
            <div class="offline-loading-text">Reconectando com o servidor online</div>
            <div class="offline-loading-subtitle">Sincronizando dados...</div>
            <div class="offline-progress-container">
                <div class="offline-progress-bar" id="offlineProgressBar"></div>
            </div>
            <div class="offline-timer" id="offlineTimer">6s</div>
        `;
        
        document.body.appendChild(this.loadingContainer);
    }

    createConnectionStatus() {
        this.connectionStatus = document.createElement('div');
        this.connectionStatus.id = 'connectionStatusIndicator';
        this.connectionStatus.className = 'connection-status offline';
        this.connectionStatus.innerHTML = `
            <span class="connection-status-icon"></span>
            <span class="connection-status-text">Offline</span>
        `;
        
        document.body.appendChild(this.connectionStatus);
    }

    setupEventListeners() {
        // Listener para mudanças de conexão
        window.addEventListener('online', () => {
            this.handleOnline();
        });
        
        window.addEventListener('offline', () => {
            this.handleOffline();
        });
    }

    showLoading(message = 'Reconectando com o servidor online') {
        if (this.isLoading) return;
        
        this.isLoading = true;
        this.loadingContainer.querySelector('.offline-loading-text').textContent = message;
        this.loadingContainer.classList.remove('hidden');
        this.loadingContainer.classList.add('fade-in');
        
        this.startProgressAnimation();
        this.startTimer();
    }

    hideLoading() {
        if (!this.isLoading) return;
        
        this.isLoading = false;
        this.loadingContainer.classList.add('fade-out');
        
        setTimeout(() => {
            this.loadingContainer.classList.add('hidden');
            this.loadingContainer.classList.remove('fade-in', 'fade-out');
        }, 500);
        
        this.stopProgressAnimation();
        this.stopTimer();
    }

    startProgressAnimation() {
        const progressBar = document.getElementById('offlineProgressBar');
        let progress = 0;
        
        this.progressInterval = setInterval(() => {
            progress += (100 / (this.loadingDuration / 100));
            if (progress >= 100) {
                progress = 100;
                this.stopProgressAnimation();
            }
            progressBar.style.width = progress + '%';
        }, 100);
    }

    stopProgressAnimation() {
        if (this.progressInterval) {
            clearInterval(this.progressInterval);
            this.progressInterval = null;
        }
    }

    startTimer() {
        let timeLeft = this.loadingDuration / 1000;
        const timerElement = document.getElementById('offlineTimer');
        
        this.timerInterval = setInterval(() => {
            timerElement.textContent = timeLeft + 's';
            timeLeft--;
            
            if (timeLeft < 0) {
                this.stopTimer();
            }
        }, 1000);
    }

    stopTimer() {
        if (this.timerInterval) {
            clearInterval(this.timerInterval);
            this.timerInterval = null;
        }
    }

    updateConnectionStatus(status, text) {
        const statusElement = this.connectionStatus;
        const textElement = statusElement.querySelector('.connection-status-text');
        
        statusElement.className = `connection-status ${status}`;
        textElement.textContent = text;
    }

    handleOffline() {
        this.updateConnectionStatus('offline', 'Offline');
        console.log('📱 Modo offline detectado');
    }

    handleOnline() {
        this.updateConnectionStatus('syncing', 'Sincronizando...');
        this.showLoading('Reconectando com o servidor online');
        
        // Simular sincronização por 6 segundos
        setTimeout(() => {
            this.hideLoading();
            this.updateConnectionStatus('online', 'Online');
            console.log('🌐 Conexão restaurada e dados sincronizados');
        }, this.loadingDuration);
    }

    // Método para forçar sincronização
    forceSync() {
        if (navigator.onLine) {
            this.handleOnline();
        } else {
            console.log('⚠️ Sem conexão para sincronizar');
        }
    }
}

// Instância global
window.offlineLoadingSystem = new OfflineLoadingSystem();
