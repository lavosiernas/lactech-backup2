/**
 * Native Features - Funcionalidades Nativas Mobile
 * Pull-to-refresh, gestos, feedback háptico, etc.
 */

class NativeFeatures {
    constructor() {
        this.pullToRefresh = {
            enabled: true,
            threshold: 80,
            startY: 0,
            currentY: 0,
            isPulling: false,
            element: null
        };
        this.swipeGestures = {
            enabled: true,
            threshold: 50,
            startX: 0,
            startY: 0,
            startTime: 0
        };
        this.hapticFeedback = {
            enabled: 'vibrate' in navigator
        };
        this.init();
    }

    init() {
        if (this.isMobile()) {
            this.initPullToRefresh();
            this.initSwipeGestures();
            this.initHapticFeedback();
            this.initAppLikeBehavior();
        }
    }

    isMobile() {
        return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) ||
               (window.innerWidth <= 768);
    }

    // ==================== PULL TO REFRESH ====================
    initPullToRefresh() {
        if (!this.pullToRefresh.enabled) return;

        const mainContent = document.querySelector('main') || document.body;
        if (!mainContent) return;

        let touchStartY = 0;
        let touchCurrentY = 0;
        let isPulling = false;
        let pullIndicator = null;

        // Criar indicador visual
        pullIndicator = document.createElement('div');
        pullIndicator.id = 'pull-to-refresh-indicator';
        pullIndicator.innerHTML = `
            <div class="pull-indicator-content">
                <svg class="pull-indicator-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                <span class="pull-indicator-text">Puxe para atualizar</span>
            </div>
        `;
        pullIndicator.style.cssText = `
            position: fixed;
            top: -60px;
            left: 0;
            right: 0;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(to bottom, #10b981, #059669);
            color: white;
            z-index: 9999;
            transition: transform 0.3s ease;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        `;
        document.body.appendChild(pullIndicator);

        const indicatorContent = pullIndicator.querySelector('.pull-indicator-content');
        indicatorContent.style.cssText = `
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        `;

        const indicatorIcon = pullIndicator.querySelector('.pull-indicator-icon');
        indicatorIcon.style.cssText = `
            width: 24px;
            height: 24px;
            transition: transform 0.3s ease;
        `;

        const indicatorText = pullIndicator.querySelector('.pull-indicator-text');
        indicatorText.style.cssText = `
            font-size: 14px;
            font-weight: 600;
        `;

        // Touch events
        mainContent.addEventListener('touchstart', (e) => {
            if (window.scrollY === 0) {
                touchStartY = e.touches[0].clientY;
                isPulling = false;
            }
        }, { passive: true });

        mainContent.addEventListener('touchmove', (e) => {
            if (touchStartY === 0) return;
            
            touchCurrentY = e.touches[0].clientY;
            const pullDistance = touchCurrentY - touchStartY;

            if (window.scrollY === 0 && pullDistance > 0) {
                isPulling = true;
                e.preventDefault();
                
                const pullProgress = Math.min(pullDistance / this.pullToRefresh.threshold, 1);
                const translateY = Math.min(pullDistance * 0.5, 60);
                
                pullIndicator.style.transform = `translateY(${translateY}px)`;
                indicatorIcon.style.transform = `rotate(${pullProgress * 360}deg)`;
                
                if (pullDistance >= this.pullToRefresh.threshold) {
                    pullIndicator.style.background = 'linear-gradient(to bottom, #059669, #047857)';
                    indicatorText.textContent = 'Solte para atualizar';
                } else {
                    pullIndicator.style.background = 'linear-gradient(to bottom, #10b981, #059669)';
                    indicatorText.textContent = 'Puxe para atualizar';
                }
            }
        }, { passive: false });

        mainContent.addEventListener('touchend', () => {
            if (isPulling && touchCurrentY - touchStartY >= this.pullToRefresh.threshold) {
                this.triggerRefresh();
            }
            
            // Reset
            pullIndicator.style.transform = 'translateY(-60px)';
            indicatorIcon.style.transform = 'rotate(0deg)';
            indicatorText.textContent = 'Puxe para atualizar';
            pullIndicator.style.background = 'linear-gradient(to bottom, #10b981, #059669)';
            
            touchStartY = 0;
            touchCurrentY = 0;
            isPulling = false;
        }, { passive: true });
    }

    async triggerRefresh() {
        this.vibrate([50]);
        
        // Mostrar loading
        const loadingToast = this.showLoadingToast('Atualizando dados...');
        
        try {
            // Atualizar dados em paralelo
            const promises = [];
            
            if (typeof loadDashboardData === 'function') {
                promises.push(loadDashboardData());
            }
            if (typeof loadVolumeData === 'function') {
                promises.push(loadVolumeData());
            }
            if (typeof loadQualityData === 'function') {
                promises.push(loadQualityData());
            }
            if (typeof loadFinancialData === 'function') {
                promises.push(loadFinancialData());
            }
            if (typeof loadUsersData === 'function') {
                promises.push(loadUsersData());
            }
            
            await Promise.allSettled(promises);
            
            // Feedback visual
            this.hideLoadingToast(loadingToast);
            this.vibrate([30, 50, 30]);
            this.showToast('Dados atualizados!', 'success');
        } catch (error) {
            this.hideLoadingToast(loadingToast);
            this.showToast('Erro ao atualizar dados', 'error');
        }
    }
    
    showLoadingToast(message) {
        const toast = document.createElement('div');
        toast.className = 'fixed top-20 left-1/2 transform -translate-x-1/2 bg-gray-800 text-white px-4 py-3 rounded-lg shadow-lg z-50 flex items-center gap-2';
        toast.innerHTML = `
            <svg class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            <span>${message}</span>
        `;
        document.body.appendChild(toast);
        return toast;
    }
    
    hideLoadingToast(toast) {
        if (toast && toast.parentNode) {
            toast.style.opacity = '0';
            toast.style.transition = 'opacity 0.3s';
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 300);
        }
    }

    // ==================== SWIPE GESTURES ====================
    initSwipeGestures() {
        if (!this.swipeGestures.enabled) return;

        let startX, startY, startTime;
        const swipeThreshold = this.swipeGestures.threshold;
        const timeThreshold = 300; // ms

        document.addEventListener('touchstart', (e) => {
            startX = e.touches[0].clientX;
            startY = e.touches[0].clientY;
            startTime = Date.now();
        }, { passive: true });

        document.addEventListener('touchend', (e) => {
            if (!startX || !startY) return;

            const endX = e.changedTouches[0].clientX;
            const endY = e.changedTouches[0].clientY;
            const endTime = Date.now();

            const deltaX = endX - startX;
            const deltaY = endY - startY;
            const deltaTime = endTime - startTime;

            // Verificar se é um swipe rápido
            if (deltaTime > timeThreshold) {
                startX = startY = startTime = null;
                return;
            }

            const absX = Math.abs(deltaX);
            const absY = Math.abs(deltaY);

            // Swipe horizontal
            if (absX > absY && absX > swipeThreshold) {
                if (deltaX > 0) {
                    this.handleSwipeRight();
                } else {
                    this.handleSwipeLeft();
                }
            }
            // Swipe vertical (para baixo já é pull-to-refresh)
            else if (absY > absX && absY > swipeThreshold && deltaY < 0) {
                // Swipe para cima - pode ser usado para fechar modais
                const activeModal = document.querySelector('.fixed.inset-0.z-50:not(.hidden)');
                if (activeModal && activeModal.id) {
                    const closeFunc = window[`close${this.camelCase(activeModal.id)}`] || 
                                    window[`close${activeModal.id.replace(/([A-Z])/g, '$1')}Modal`];
                    if (closeFunc) {
                        closeFunc();
                    }
                }
            }

            startX = startY = startTime = null;
        }, { passive: true });
    }

    handleSwipeLeft() {
        // Navegar para próxima tab
        const tabs = ['dashboard', 'volume', 'quality', 'payments', 'users'];
        const currentTab = document.querySelector('[data-tab].active, .bottom-nav-item.active');
        if (currentTab) {
            const currentTabName = currentTab.getAttribute('data-tab') || 
                                 currentTab.onclick?.toString().match(/switchTab\(['"](.*?)['"]/)?.[1];
            if (currentTabName) {
                const currentIndex = tabs.indexOf(currentTabName);
                if (currentIndex < tabs.length - 1) {
                    this.vibrate([30]);
                    if (typeof switchTab === 'function') {
                        switchTab(tabs[currentIndex + 1]);
                    } else if (typeof switchBottomTab === 'function') {
                        switchBottomTab(tabs[currentIndex + 1]);
                    }
                }
            }
        }
    }

    handleSwipeRight() {
        // Navegar para tab anterior
        const tabs = ['dashboard', 'volume', 'quality', 'payments', 'users'];
        const currentTab = document.querySelector('[data-tab].active, .bottom-nav-item.active');
        if (currentTab) {
            const currentTabName = currentTab.getAttribute('data-tab') || 
                                 currentTab.onclick?.toString().match(/switchTab\(['"](.*?)['"]/)?.[1];
            if (currentTabName) {
                const currentIndex = tabs.indexOf(currentTabName);
                if (currentIndex > 0) {
                    this.vibrate([30]);
                    if (typeof switchTab === 'function') {
                        switchTab(tabs[currentIndex - 1]);
                    } else if (typeof switchBottomTab === 'function') {
                        switchBottomTab(tabs[currentIndex - 1]);
                    }
                }
            }
        }
    }

    camelCase(str) {
        return str.replace(/-([a-z])/g, (g) => g[1].toUpperCase());
    }

    // ==================== HAPTIC FEEDBACK ====================
    initHapticFeedback() {
        // Já inicializado no constructor
    }

    vibrate(pattern) {
        if (!this.hapticFeedback.enabled) return;
        
        try {
            if (navigator.vibrate) {
                navigator.vibrate(pattern);
            }
        } catch (e) {
            // Ignorar erros de vibração
        }
    }

    // ==================== APP-LIKE BEHAVIOR ====================
    initAppLikeBehavior() {
        // Prevenir zoom duplo toque
        let lastTouchEnd = 0;
        document.addEventListener('touchend', (e) => {
            const now = Date.now();
            if (now - lastTouchEnd <= 300) {
                e.preventDefault();
            }
            lastTouchEnd = now;
        }, { passive: false });

        // Status bar color (iOS)
        const metaThemeColor = document.querySelector('meta[name="theme-color"]');
        if (metaThemeColor) {
            metaThemeColor.content = '#10b981'; // Verde LacTech
        }

        // Prevenir pull-to-refresh padrão do navegador
        document.body.style.overscrollBehaviorY = 'contain';

        // Melhorar scroll suave
        document.documentElement.style.scrollBehavior = 'smooth';
    }

    // ==================== UTILITIES ====================
    showToast(message, type = 'info') {
        if (typeof showToast === 'function') {
            showToast(message, type);
        } else {
            console.log(`[${type.toUpperCase()}] ${message}`);
        }
    }
}

// Inicializar quando DOM estiver pronto
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.nativeFeatures = new NativeFeatures();
    });
} else {
    window.nativeFeatures = new NativeFeatures();
}

