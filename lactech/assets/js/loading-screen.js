// =====================================================
// GERENCIADOR DE TELA DE CARREGAMENTO - LACTECH SYSTEM
// =====================================================
// DESABILITADO - Usando apenas modal HTML no gerente.php
console.log('⚠️ loading-screen.js DESABILITADO - usando apenas modal HTML');
return; // Parar execução imediatamente

class LoadingScreen {
    constructor() {
        this.isFirstVisit = !localStorage.getItem('lactech_visited');
        this.loadingTime = this.isFirstVisit ? 6000 : 4000; // 6s primeira visita, 4s recarregamento
        this.progressBar = null;
        this.statusText = null;
        this.currentProgress = 0;
        this.loadingSteps = [
            { progress: 20, text: 'Inicializando sistema...' },
            { progress: 40, text: 'Carregando dados...' },
            { progress: 60, text: 'Preparando gráficos...' },
            { progress: 80, text: 'Finalizando...' },
            { progress: 100, text: 'Pronto!' }
        ];
        this.currentStep = 0;
        this.chartsLoaded = false;
        this.dataLoaded = false;
        this.startTime = Date.now();
        
        this.init();
    }

    init() {
        // Marcar que já visitou
        if (this.isFirstVisit) {
            localStorage.setItem('lactech_visited', 'true');
        }

        // Criar e adicionar a tela de carregamento
        this.createLoadingScreen();
        
        // Iniciar o carregamento
        this.startLoading();
        
        // Aguardar carregamento dos gráficos
        this.waitForCharts();
    }

    createLoadingScreen() {
        const loadingHTML = `
            <div id="loadingScreen" class="loading-screen">
                <div class="loading-logo">
                    <img src="assets/img/lactech-logo.png" alt="LacTech Logo">
                </div>
                <div class="loading-text">Carregando LacTech System</div>
                <div class="loading-spinner"></div>
                <div class="loading-progress">
                    <div class="loading-progress-bar" id="loadingProgressBar"></div>
                </div>
                <div class="loading-status" id="loadingStatus">Inicializando...</div>
            </div>
        `;

        // Adicionar ao body
        document.body.insertAdjacentHTML('afterbegin', loadingHTML);
        
        // Ocultar o conteúdo principal
        const mainContent = document.querySelector('main');
        if (mainContent) {
            mainContent.style.opacity = '0';
            mainContent.style.transition = 'opacity 0.5s ease-in';
        }

        // Referências aos elementos
        this.progressBar = document.getElementById('loadingProgressBar');
        this.statusText = document.getElementById('loadingStatus');
    }

    startLoading() {
        const stepDuration = this.loadingTime / this.loadingSteps.length;
        
        this.loadingSteps.forEach((step, index) => {
            setTimeout(() => {
                this.updateProgress(step.progress, step.text);
                this.currentStep = index;
            }, index * stepDuration);
        });

        // Finalizar carregamento após o tempo mínimo
        setTimeout(() => {
            this.checkIfReadyToFinish();
        }, this.loadingTime);
    }

    waitForCharts() {
        // Aguardar carregamento dos gráficos
        const checkCharts = () => {
            // Verificar se os gráficos foram inicializados
            const charts = [
                window.volumeChart,
                window.dashboardWeeklyChart,
                window.temperatureChart,
                window.qualityChart,
                window.weeklyVolumeChart,
                window.dailyVolumeChart,
                window.qualityTrendChart,
                window.qualityDistributionChart,
                window.paymentsChart,
                window.weeklySummaryChart,
                window.monthlyVolumeChart
            ].filter(Boolean);

            if (charts.length > 0) {
                this.chartsLoaded = true;
                console.log('Gráficos carregados:', charts.length);
            }

            // Verificar se os dados foram carregados
            if (document.querySelector('[id*="Volume"], [id*="volume"]')) {
                this.dataLoaded = true;
            }

            // Se ainda não terminou o tempo mínimo, continuar verificando
            if (this.currentProgress < 100) {
                setTimeout(checkCharts, 500);
            }
        };

        // Iniciar verificação após 1 segundo
        setTimeout(checkCharts, 1000);
    }

    checkIfReadyToFinish() {
        // Aguardar pelo menos o tempo mínimo
        const minTime = this.loadingTime;
        const elapsedTime = Date.now() - this.startTime;
        
        if (elapsedTime >= minTime) {
            this.finishLoading();
        } else {
            // Aguardar o tempo restante
            setTimeout(() => {
                this.finishLoading();
            }, minTime - elapsedTime);
        }
    }

    updateProgress(progress, status) {
        if (this.progressBar) {
            this.progressBar.style.width = `${progress}%`;
        }
        
        if (this.statusText) {
            this.statusText.textContent = status;
        }
        
        this.currentProgress = progress;
    }

    finishLoading() {
        const loadingScreen = document.getElementById('loadingScreen');
        const mainContent = document.querySelector('main');
        
        if (loadingScreen) {
            // Adicionar classe de fade-out
            loadingScreen.classList.add('fade-out');
            
            // Remover após a animação
            setTimeout(() => {
                loadingScreen.classList.add('hidden');
                loadingScreen.remove();
            }, 500);
        }
        
        if (mainContent) {
            // Mostrar conteúdo principal
            mainContent.style.opacity = '1';
        }

        // Disparar evento de carregamento concluído
        window.dispatchEvent(new CustomEvent('lactechLoaded'));
        
        console.log('LacTech System carregado com sucesso!');
        console.log('Tempo de carregamento:', this.isFirstVisit ? '6 segundos (primeira visita)' : '4 segundos (recarregamento)');
    }

    // Método para forçar finalização (útil para desenvolvimento)
    forceFinish() {
        this.finishLoading();
    }
}

// =====================================================
// INICIALIZAÇÃO AUTOMÁTICA
// =====================================================

// Aguardar o DOM estar pronto
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        new LoadingScreen();
    });
} else {
    // DOM já está pronto
    new LoadingScreen();
}

// =====================================================
// FUNÇÕES GLOBAIS PARA USO EM OUTROS SCRIPTS
// =====================================================

window.LacTechLoading = {
    // Instância global da tela de carregamento
    instance: null,
    
    // Inicializar
    init() {
        if (!this.instance) {
            this.instance = new LoadingScreen();
        }
        return this.instance;
    },
    
    // Forçar finalização
    finish() {
        if (this.instance) {
            this.instance.forceFinish();
        }
    },
    
    // Verificar se está carregando
    isLoading() {
        return document.getElementById('loadingScreen') !== null;
    }
};

// =====================================================
// EVENTOS GLOBAIS
// =====================================================

// Evento disparado quando o carregamento termina
window.addEventListener('lactechLoaded', () => {
    console.log('LacTech System carregado com sucesso!');
    
    // Aqui você pode adicionar qualquer lógica adicional
    // que deve ser executada após o carregamento
    
    // Forçar atualização dos gráficos se necessário
    if (typeof applyChartTheme === 'function') {
        setTimeout(() => {
            applyChartTheme();
        }, 100);
    }
});

// =====================================================
// INTEGRAÇÃO COM GRÁFICOS EXISTENTES
// =====================================================

// Aguardar carregamento do Chart.js
window.addEventListener('load', () => {
    // Aguardar um pouco mais para garantir que todos os gráficos foram inicializados
    setTimeout(() => {
        if (window.LacTechLoading && window.LacTechLoading.isLoading()) {
            // Se ainda está carregando, aguardar mais um pouco
            setTimeout(() => {
                if (window.LacTechLoading.instance) {
                    window.LacTechLoading.instance.finishLoading();
                }
            }, 1000);
        }
    }, 2000);
});
