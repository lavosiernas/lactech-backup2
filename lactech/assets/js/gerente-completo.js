/**
 * JavaScript para Dashboard Gerente - Versão Corrigida
 * Sistema completo com todas as funcionalidades originais
 */

// ==================== CONFIGURAÇÕES ====================
const CONFIG = {
    apiBaseUrl: 'api/',
    refreshInterval: 30000, // 30 segundos
    animationDuration: 300
};

// Função auxiliar para obter data local no formato YYYY-MM-DD
function getLocalDateString() {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

// ==================== ESTADO GLOBAL ====================
let currentTab = 'dashboard';
let isLoading = false;
let refreshTimer = null;
let charts = {};

// ==================== TELA DE CARREGAMENTO ====================
// Mensagens motivadoras para o carregamento
const loadingMessages = [
    { text: 'Preparando tudo para você! 🚀', time: 0 },
    { text: 'Organizando seus dados... 📊', time: 600 },
    { text: 'Quase lá! 💪', time: 1200 },
    { text: 'Carregando informações... ⚡', time: 1800 },
    { text: 'Tudo pronto! 🎉', time: 2400 }
];

let currentMessageIndex = 0;
let loadingMessageInterval = null;

// Função para atualizar mensagens de carregamento
function updateLoadingMessage() {
    const messageElement = document.getElementById('loadingMessage');
    if (!messageElement) return;
    
    // Trocar mensagem
    if (currentMessageIndex < loadingMessages.length - 1) {
        currentMessageIndex++;
        const message = loadingMessages[currentMessageIndex];
        
        // Efeito de fade
        messageElement.style.opacity = '0';
        messageElement.style.transform = 'translateY(10px)';
        
        setTimeout(function() {
            messageElement.textContent = message.text;
            messageElement.style.opacity = '1';
            messageElement.style.transform = 'translateY(0)';
        }, 200);
    }
}

// Esconder tela de carregamento após 3 segundos
function hideLoadingScreen() {
    const loadingScreen = document.getElementById('loadingScreen');
    const messageElement = document.getElementById('loadingMessage');
    
    if (!loadingScreen) return;
    
    // Trocar mensagens a cada 600ms
    loadingMessages.forEach(function(message) {
        if (message.time > 0) {
            setTimeout(function() {
                updateLoadingMessage();
            }, message.time);
        }
    });
    
    // Esconder após 3 segundos
    setTimeout(function() {
        // Parar intervalo de mensagens
        if (loadingMessageInterval) {
            clearInterval(loadingMessageInterval);
        }
        
        // Mensagem final
        if (messageElement) {
            messageElement.style.opacity = '0';
            setTimeout(function() {
                messageElement.textContent = 'Bem-vindo de volta! 👋';
                messageElement.style.opacity = '1';
            }, 200);
        }
        
        // Fade out da tela
        setTimeout(function() {
            loadingScreen.style.opacity = '0';
            loadingScreen.style.transition = 'opacity 0.8s ease-out';
            setTimeout(function() {
                loadingScreen.style.display = 'none';
            }, 800);
        }, 400);
    }, 3000); // 3 segundos
}

// ==================== INICIALIZAÇÃO ====================
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Inicializando Dashboard Gerente Completo...');
    
    // Iniciar esconder tela de carregamento
    hideLoadingScreen();
    
    initializeNavigation();
    initializeOverlays();
    
    // Aguardar Chart.js estar carregado antes de carregar dados
    function initializeDashboard() {
        if (typeof Chart !== 'undefined') {
            console.log('✅ Chart.js carregado, inicializando dashboard...');
            loadDashboardData();
            startAutoRefresh();
            updateDateTime();
            registerCurrentSession();
            
            // Atualizar última atividade periodicamente (a cada 5 minutos)
            setInterval(function() {
                updateSessionActivity();
            }, 5 * 60 * 1000); // 5 minutos
            
            console.log('✅ Dashboard Gerente Completo inicializado com sucesso!');
        } else {
            console.log('⏳ Aguardando Chart.js...');
            setTimeout(initializeDashboard, 100);
        }
    }
    
    initializeDashboard();
});

// ==================== NAVEGAÇÃO ====================
function initializeNavigation() {
    const navItems = document.querySelectorAll('.nav-item');
    
    navItems.forEach(item => {
        item.addEventListener('click', function() {
            const tab = this.dataset.tab;
            if (tab) {
                switchTab(tab);
            }
        });
    });
}

function switchTab(tabName) {
    if (isLoading) return;
    
    // Atualizar navegação
    document.querySelectorAll('.nav-item').forEach(item => {
        item.classList.remove('active');
    });
    document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');
    
    // Mostrar conteúdo da aba
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.add('hidden');
    });
    document.getElementById(`${tabName}-tab`).classList.remove('hidden');
    
    currentTab = tabName;
    
    // Carregar dados específicos da aba
    switch(tabName) {
        case 'dashboard':
            loadDashboardData();
            break;
        case 'volume':
            loadVolumeData();
            // Garantir que a tabela seja carregada mesmo se loadVolumeData falhar
            setTimeout(() => {
                const tbody = document.getElementById('volumeRecordsTable');
                if (tbody && tbody.innerHTML.includes('Carregando')) {
                    loadVolumeRecordsTable();
                }
            }, 500);
            break;
        case 'quality':
            loadQualityData();
            break;
        case 'payments':
            loadFinancialData();
            break;
        case 'users':
            loadUsersData();
            break;
    }
}

// ==================== DASHBOARD ====================
async function loadDashboardData() {
    console.log('📊 Carregando dados do dashboard...');
    
    try {
        console.log('🔗 Fazendo requisição para: ./api/endpoints/dashboard.php');
        const response = await fetch('./api/endpoints/dashboard.php');
        console.log('📡 Resposta recebida:', response.status, response.statusText);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        console.log('📊 Dados recebidos:', result);
        
        if (result.success && result.data) {
            const data = result.data;
            
            // Helper numérico seguro
            const n = (v) => {
                const num = typeof v === 'number' ? v : parseFloat(v);
                return Number.isFinite(num) ? num : 0;
            };
            
            // Atualizar métricas principais do dashboard
            const todayVolumeEl = document.getElementById('todayVolume');
            if (todayVolumeEl) {
                const volume = n(data.today_production?.today_volume || 0);
                todayVolumeEl.textContent = volume.toFixed(1) + ' L';
                console.log('✅ Volume hoje (dashboard) atualizado:', volume);
            }
            
            // Atualizar métricas da aba Volume
            const volumeTodayEl = document.getElementById('volumeToday');
            if (volumeTodayEl) {
                const volume = n(data.today_production?.today_volume || 0);
                volumeTodayEl.textContent = volume.toFixed(1) + ' L';
                console.log('✅ Volume hoje (aba Volume) atualizado:', volume);
            }
            
            // Média semanal
            const volumeWeekAvgEl = document.getElementById('volumeWeekAvg');
            if (volumeWeekAvgEl) {
                const weekAvg = n(data.week_production?.avg_daily_volume || 0);
                volumeWeekAvgEl.textContent = weekAvg.toFixed(1) + ' L';
                console.log('✅ Média semanal atualizada:', weekAvg);
            }
            
            // Total do mês
            const volumeMonthTotalEl = document.getElementById('volumeMonthTotal');
            if (volumeMonthTotalEl) {
                const monthTotal = n(data.month_production?.month_volume || 0);
                volumeMonthTotalEl.textContent = monthTotal.toFixed(0) + ' L';
                console.log('✅ Total do mês atualizado:', monthTotal);
            }
            
            const qualityAverageEl = document.getElementById('qualityAverage');
            if (qualityAverageEl) {
                const fat = n(data.quality?.avg_fat || 0);
                qualityAverageEl.textContent = fat.toFixed(1) + '%';
                console.log('✅ Qualidade média atualizada:', fat);
            }
            
            const pendingPaymentsEl = document.getElementById('pendingPayments');
            if (pendingPaymentsEl) {
                const expenses = n(data.expenses?.month_expenses || 0);
                pendingPaymentsEl.textContent = 'R$ ' + expenses.toFixed(2);
                console.log('✅ Pagamentos pendentes atualizados:', expenses);
            }
            
            const activeUsersEl = document.getElementById('activeUsers');
            if (activeUsersEl) {
                try {
                    const usersResp = await fetch('./api/endpoints/users.php');
                    if (usersResp.ok) {
                        const usersJson = await usersResp.json();
                        const usersCount = usersJson?.data?.stats?.active_users ?? 0;
                        activeUsersEl.textContent = String(n(usersCount).toFixed(0));
                        console.log('✅ Usuários ativos atualizados:', usersCount);
                    } else {
                        activeUsersEl.textContent = '0';
                    }
                } catch (e) {
                    console.error('Erro ao carregar usuários:', e);
                    activeUsersEl.textContent = '0';
                }
            }
            
            // Atualizar gráficos
            console.log('📊 Atualizando gráficos...');
            if (data.production_chart && Array.isArray(data.production_chart)) {
                renderMonthlyVolumeChart(data.production_chart);
            } else {
                console.warn('⚠️ Dados do gráfico mensal não disponíveis');
                renderMonthlyVolumeChart([]);
            }
            
            // Aguardar Chart.js estar pronto antes de renderizar
            if (typeof Chart !== 'undefined') {
                renderWeeklyVolumeCharts();
                renderTemperatureChart();
                renderQualityWeeklyChart();
            } else {
                console.error('❌ Chart.js não está carregado!');
                // Tentar carregar novamente após um delay
                setTimeout(() => {
                    if (typeof Chart !== 'undefined') {
                        renderWeeklyVolumeCharts();
                        renderTemperatureChart();
                        renderQualityWeeklyChart();
                    }
                }, 1000);
            }
            
            // Atualizar atividades recentes
            if (data.recent_activities && Array.isArray(data.recent_activities)) {
                updateRecentActivities(data.recent_activities);
            } else {
                console.warn('⚠️ Atividades recentes não disponíveis');
                updateRecentActivities([]);
            }
            
            // Atualizar data/hora
            const lastUpdateEl = document.getElementById('lastUpdate');
            if (lastUpdateEl) {
                lastUpdateEl.textContent = new Date().toLocaleString('pt-BR');
            }
            
            console.log('✅ Dados do dashboard carregados com sucesso!');
        } else {
            console.error('❌ Erro na API:', result.error || 'Dados não retornados');
            // Definir valores padrão em caso de erro
            const todayVolumeEl = document.getElementById('todayVolume');
            if (todayVolumeEl) todayVolumeEl.textContent = '0.0 L';
            
            const qualityAverageEl = document.getElementById('qualityAverage');
            if (qualityAverageEl) qualityAverageEl.textContent = '0.0%';
            
            const pendingPaymentsEl = document.getElementById('pendingPayments');
            if (pendingPaymentsEl) pendingPaymentsEl.textContent = 'R$ 0.00';
            
            const activeUsersEl = document.getElementById('activeUsers');
            if (activeUsersEl) activeUsersEl.textContent = '0';
        }
    } catch (error) {
        console.error('❌ Erro na requisição:', error);
        // Definir valores padrão em caso de erro
        const todayVolumeEl = document.getElementById('todayVolume');
        if (todayVolumeEl) todayVolumeEl.textContent = '0.0 L';
        
        const qualityAverageEl = document.getElementById('qualityAverage');
        if (qualityAverageEl) qualityAverageEl.textContent = '0.0%';
        
        const pendingPaymentsEl = document.getElementById('pendingPayments');
        if (pendingPaymentsEl) pendingPaymentsEl.textContent = 'R$ 0.00';
        
        const activeUsersEl = document.getElementById('activeUsers');
        if (activeUsersEl) activeUsersEl.textContent = '0';
    }
}

// ==================== FUNÇÕES DE GRÁFICOS E ATIVIDADES ====================

// Atualizar gráfico de produção
// ==================== CHART HELPERS (Chart.js) ====================
function createOrUpdateLineChart(canvasId, labels, data, color = '#10B981') {
    const canvas = document.getElementById(canvasId);
    if (!canvas) {
        console.warn(`⚠️ Canvas não encontrado: ${canvasId}`);
        return;
    }
    
    // Verificar se Chart.js está disponível
    if (typeof Chart === 'undefined') {
        console.error('❌ Chart.js não está carregado!');
        return;
    }
    
    // Destruir gráfico anterior se existir
    if (charts[canvasId]) {
        charts[canvasId].destroy();
        delete charts[canvasId];
    }
    
    // Garantir que temos dados válidos
    if (!Array.isArray(labels)) labels = [];
    if (!Array.isArray(data)) data = [];
    
    // Garantir que labels e data tenham o mesmo tamanho
    const minLength = Math.min(labels.length, data.length);
    labels = labels.slice(0, minLength);
    data = data.slice(0, minLength);
    
    // Se não há dados, criar dados vazios para mostrar o gráfico
    if (labels.length === 0) {
        labels = ['Sem dados'];
        data = [0];
    }
    
    try {
        charts[canvasId] = new Chart(canvas.getContext('2d'), {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: '',
                    data: data,
                    borderColor: color,
                    backgroundColor: color + '1A',
                    fill: true,
                    tension: 0.3,
                    borderWidth: 2,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    showLine: true,
                    spanGaps: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { 
                    legend: { display: false },
                    tooltip: {
                        enabled: true,
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: { 
                    x: { 
                        display: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        }
                    }, 
                    y: { 
                        display: true, 
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        }
                    } 
                }
            }
        });
        console.log(`✅ Gráfico ${canvasId} criado/atualizado com sucesso`);
    } catch (error) {
        console.error(`❌ Erro ao criar gráfico ${canvasId}:`, error);
    }
}

function renderMonthlyVolumeChart(chartData) {
    if (!Array.isArray(chartData)) chartData = [];
    const labels = chartData.map(i => i.production_date);
    const data = chartData.map(i => Number(i.daily_volume) || 0);
    // Garantir ao menos 2 pontos para a linha aparecer
    if (data.length === 1) {
        labels.push(labels[0]);
        data.push(data[0]);
    }
    createOrUpdateLineChart('monthlyProductionChart', labels, data, '#10B981');
}

async function renderWeeklyVolumeCharts() {
    try {
        console.log('📊 Carregando dados para gráfico semanal...');
        const res = await fetch('./api/endpoints/volume.php');
        
        if (!res.ok) {
            throw new Error(`HTTP error! status: ${res.status}`);
        }
        
        const json = await res.json();
        console.log('📊 Dados do gráfico semanal recebidos:', json);
        
        // Buscar dados dos últimos 7 dias da semana
        const weekChart = Array.isArray(json?.data?.week?.chart) ? json.data.week.chart : [];
        const series = Array.isArray(json?.data?.chart) ? json.data.chart : [];
        
        // Usar dados da semana se disponível, senão usar do gráfico geral
        const dataSource = weekChart.length > 0 ? weekChart : series;
        
        console.log('📊 Séries encontradas:', dataSource.length);
        
        // Construir faixa dos últimos 7 dias e preencher faltantes com 0
        const dateKey = (d) => d.toISOString().slice(0,10);
        const today = new Date();
        const last7Dates = Array.from({length: 7}, (_, idx) => {
            const d = new Date(today);
            d.setDate(today.getDate() - (6 - idx));
            return dateKey(d);
        });
        
        const map = {};
        dataSource.forEach(i => { 
            const date = i.production_date || i.date || i.record_date;
            const volume = Number(i.daily_volume || i.total_volume || i.volume || 0);
            if (date) {
                map[date] = volume;
            }
        });
        
        // Filtrar apenas os últimos 7 dias
        const labels7 = last7Dates;
        const data7 = labels7.map(d => map[d] ?? 0);
        
        console.log('📊 Labels:', labels7);
        console.log('📊 Dados:', data7);
        
        // Garantir que temos dados válidos
        if (data7.every(v => v === 0)) {
            console.warn('⚠️ Nenhum dado encontrado para os últimos 7 dias');
        }
        
        // Garantir linha
        if (data7.length === 1) { 
            labels7.push(labels7[0]); 
            data7.push(data7[0]); 
        }
        
        createOrUpdateLineChart('volumeChart', labels7, data7, '#3B82F6');
        createOrUpdateLineChart('dashboardWeeklyChart', labels7, data7, '#6366F1');
        
        console.log('✅ Gráficos semanais renderizados com sucesso');
    } catch (e) {
        console.error('❌ Erro ao renderizar gráficos semanais:', e);
        const labels7 = ['Sem dados'];
        const data7 = [0];
        createOrUpdateLineChart('volumeChart', labels7, data7, '#3B82F6');
        createOrUpdateLineChart('dashboardWeeklyChart', labels7, data7, '#6366F1');
    }
}

async function renderQualityWeeklyChart() {
    try {
        console.log('📊 Carregando dados de qualidade dos últimos 7 dias...');
        const res = await fetch('./api/quality.php?action=get_dashboard_data');
        
        if (!res.ok) {
            throw new Error(`HTTP error! status: ${res.status}`);
        }
        
        const json = await res.json();
        console.log('📊 Dados de qualidade recebidos:', json);
        
        if (!json.success || !json.data) {
            throw new Error('Dados não disponíveis');
        }
        
        const trendChart = Array.isArray(json.data.trend_chart) ? json.data.trend_chart : [];
        
        // Construir faixa dos últimos 7 dias
        const dateKey = (d) => d.toISOString().slice(0,10);
        const today = new Date();
        const last7Dates = Array.from({length: 7}, (_, idx) => {
            const d = new Date(today);
            d.setDate(today.getDate() - (6 - idx));
            return dateKey(d);
        });
        
        // Criar mapas para cada métrica
        const fatMap = {};
        const proteinMap = {};
        const ccsMap = {};
        
        trendChart.forEach(item => {
            const date = item.date || item.test_date;
            if (date) {
                fatMap[date] = Number(item.fat || item.fat_content || 0);
                proteinMap[date] = Number(item.protein || item.protein_content || 0);
                ccsMap[date] = Number(item.ccs || item.somatic_cells || 0);
            }
        });
        
        const labels7 = last7Dates;
        const fatData = labels7.map(d => fatMap[d] ?? 0);
        const proteinData = labels7.map(d => proteinMap[d] ?? 0);
        const ccsData = labels7.map(d => ccsMap[d] ?? 0);
        
        console.log('📊 Labels:', labels7);
        console.log('📊 Gordura:', fatData);
        console.log('📊 Proteína:', proteinData);
        console.log('📊 CCS:', ccsData);
        
        // Criar gráfico com múltiplas séries
        const ctx = document.getElementById('qualityWeeklyChart');
        if (!ctx) {
            console.error('❌ Canvas qualityWeeklyChart não encontrado');
            return;
        }
        
        // Destruir gráfico anterior se existir
        if (window.qualityWeeklyChartInstance) {
            window.qualityWeeklyChartInstance.destroy();
        }
        
        window.qualityWeeklyChartInstance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels7,
                datasets: [
                    {
                        label: 'Gordura (%)',
                        data: fatData,
                        borderColor: '#10B981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Proteína (%)',
                        data: proteinData,
                        borderColor: '#3B82F6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'CCS (mil)',
                        data: ccsData.map(v => v / 1000), // Converter para milhares
                        borderColor: '#F59E0B',
                        backgroundColor: 'rgba(245, 158, 11, 0.1)',
                        tension: 0.4,
                        fill: true,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Gordura / Proteína (%)'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'CCS (mil)'
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                }
            }
        });
        
        console.log('✅ Gráfico de qualidade renderizado com sucesso');
    } catch (e) {
        console.error('❌ Erro ao renderizar gráfico de qualidade:', e);
        // Criar gráfico vazio em caso de erro
        const ctx = document.getElementById('qualityWeeklyChart');
        if (ctx) {
            if (window.qualityWeeklyChartInstance) {
                window.qualityWeeklyChartInstance.destroy();
            }
            window.qualityWeeklyChartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Sem dados'],
                    datasets: [{
                        label: 'Gordura (%)',
                        data: [0],
                        borderColor: '#10B981'
                    }]
                }
            });
        }
    }
}

async function renderTemperatureChart() {
    try {
        console.log('🌡️ Carregando dados de temperatura...');
        const res = await fetch('./api/volume.php?action=get_temperature');
        
        if (!res.ok) {
            throw new Error(`HTTP error! status: ${res.status}`);
        }
        
        const json = await res.json();
        console.log('🌡️ Dados de temperatura recebidos:', json);
        
        let srcLabels = [];
        let srcData = [];
        
        // Verificar diferentes formatos de resposta
        if (json?.data?.labels && json?.data?.data) {
            srcLabels = Array.isArray(json.data.labels) ? json.data.labels : [];
            srcData = Array.isArray(json.data.data) ? json.data.data.map(v => Number(v) || 0) : [];
        } else if (json?.data && Array.isArray(json.data)) {
            // Formato alternativo: array de objetos com date e avg_temp
            srcLabels = json.data.map(item => item.date || item.production_date || '');
            srcData = json.data.map(item => Number(item.avg_temp || item.temperature || 0));
        }
        
        console.log('🌡️ Labels:', srcLabels);
        console.log('🌡️ Dados:', srcData);
        
        // Preencher últimos 30 dias
        const dateKey = (d) => d.toISOString().slice(0,10);
        const today = new Date();
        const last30 = Array.from({length: 30}, (_, idx) => {
            const d = new Date(today);
            d.setDate(today.getDate() - (29 - idx));
            return dateKey(d);
        });
        
        const map = {};
        srcLabels.forEach((d, i) => { 
            if (d) {
                map[d] = srcData[i] ?? 0; 
            }
        });
        
        const labels = last30;
        const data = labels.map(d => map[d] ?? 0);
        
        // Se não há dados, mostrar mensagem
        if (data.every(v => v === 0)) {
            console.warn('⚠️ Nenhum dado de temperatura encontrado');
            labels.length = 0;
            data.length = 0;
        }
        
        if (data.length === 1) { 
            labels.push(labels[0]); 
            data.push(data[0]); 
        }
        
        createOrUpdateLineChart('temperatureChart', labels, data, '#F59E0B');
        console.log('✅ Gráfico de temperatura renderizado com sucesso');
    } catch (e) {
        console.error('❌ Erro ao renderizar gráfico de temperatura:', e);
        createOrUpdateLineChart('temperatureChart', ['Sem dados'], [0], '#F59E0B');
    }
}

function renderVolumeTabChart(series) {
    const dateKey = (d) => d.toISOString().slice(0,10);
    const today = new Date();
    const last30 = Array.from({length: 30}, (_, idx) => {
        const d = new Date(today);
        d.setDate(today.getDate() - (29 - idx));
        return dateKey(d);
    });
    const map = {};
    series.forEach(i => { map[i.production_date] = Number(i.daily_volume) || 0; });
    const labels = last30;
    const data = labels.map(d => map[d] ?? 0);
    if (data.length === 1) { labels.push(labels[0]); data.push(data[0]); }
    createOrUpdateLineChart('volumeTabChart', labels, data, '#0EA5E9');
}

async function loadVolumeRecordsTable() {
    const tbody = document.getElementById('volumeRecordsTable');
    if (!tbody) {
        console.warn('Tabela volumeRecordsTable não encontrada');
        return;
    }
    
    // Mostrar loading
    tbody.innerHTML = '<tr><td colspan="5" class="text-center py-8 text-gray-500">Carregando registros...</td></tr>';
    
    try {
        // Adicionar timestamp para evitar cache
        const timestamp = new Date().getTime();
        const res = await fetch(`./api/volume.php?action=get_all&_t=${timestamp}`, {
            cache: 'no-store',
            headers: {
                'Cache-Control': 'no-cache, no-store, must-revalidate',
                'Pragma': 'no-cache'
            }
        });
        
        if (!res.ok) {
            throw new Error(`HTTP error! status: ${res.status}`);
        }
        
        const json = await res.json();
        
        console.log('📦 Resposta da API volume get_all:', json);
        
        // Verificar se há erro na resposta (mas não bloquear se success for false mas data existir)
        if (json.error && !json.data) {
            throw new Error(json.error || 'Erro ao buscar registros');
        }
        
        // O método query() retorna um array diretamente, mas a API pode retornar em json.data
        const rows = Array.isArray(json?.data) ? json.data : (Array.isArray(json) ? json : []);
        
        console.log('📊 Registros processados:', rows.length, rows);
        
        if (rows.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center py-8 text-gray-500">Nenhum registro encontrado</td></tr>';
            return;
        }
        
        // Função para formatar data corretamente (evitar problemas de timezone)
        const formatDate = (dateStr) => {
            if (!dateStr) return '-';
            // Se a data está no formato YYYY-MM-DD, parsear como data local
            if (typeof dateStr === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(dateStr)) {
                const [year, month, day] = dateStr.split('-').map(Number);
                const date = new Date(year, month - 1, day);
                return date.toLocaleDateString('pt-BR', { 
                    day: '2-digit', 
                    month: '2-digit', 
                    year: 'numeric' 
                });
            }
            // Para outros formatos, usar o método padrão
            const date = new Date(dateStr);
            if (isNaN(date.getTime())) return '-';
            return date.toLocaleDateString('pt-BR', { 
                day: '2-digit', 
                month: '2-digit', 
                year: 'numeric' 
            });
        };
        
        // Função para formatar apenas horário
        const formatTime = (dateTimeStr) => {
            if (!dateTimeStr) return '-';
            const date = new Date(dateTimeStr);
            if (isNaN(date.getTime())) return '-';
            return date.toLocaleTimeString('pt-BR', { 
                hour: '2-digit',
                minute: '2-digit'
            });
        };
        
        // Formatar período (shift)
        const formatShift = (shift) => {
            const shifts = {
                'manha': 'Manhã',
                'tarde': 'Tarde',
                'noite': 'Noite'
            };
            return shifts[shift] || shift || '-';
        };
        
        const htmlRows = rows.map((r, index) => {
            // Validar que o ID existe - tentar diferentes formatos
            const recordId = r.id || r.ID || r.Id || 0;
            
            // Log para debug
            if (!recordId || recordId <= 0) {
                console.warn('⚠️ Registro sem ID válido (índice ' + index + '):', r);
            } else {
                console.log('✅ Registro com ID válido:', recordId, r);
            }
            
            // Usar apenas o ID real do banco, não usar fallback de índice
            const finalId = recordId && recordId > 0 ? recordId : null;
            
            const formattedDate = formatDate(r.record_date);
            const formattedTime = formatTime(r.created_at);
            const formattedShift = formatShift(r.shift);
            const formattedVolume = (Number(r.total_volume)||0).toFixed(2);
            const animalsCount = r.total_animals || 0;
            const animalsText = animalsCount == 1 ? 'animal' : 'animais';
            
            // Indicar se é registro individual por vaca
            const isIndividual = r.record_type === 'individual' || r.animal_id;
            const animalInfo = isIndividual && r.animal_name ? 
                `<span class="text-xs text-gray-500 block">Vaca: ${r.animal_name}</span>` : '';
            
            return `
            <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                <td class="py-3 px-4">${formattedDate}</td>
                <td class="py-3 px-4 text-gray-600">${formattedTime}</td>
                <td class="py-3 px-4 capitalize">${formattedShift}</td>
                <td class="py-3 px-4 font-semibold text-blue-600">${formattedVolume} L</td>
                <td class="py-3 px-4 text-gray-600">
                    ${animalsCount} ${animalsText}
                    ${animalInfo}
                </td>
                <td class="py-3 px-4">
                    <div class="flex items-center justify-end gap-3">
                        ${finalId ? `
                        <button onclick="viewVolumeDetails(${finalId})" 
                            class="text-blue-600 hover:text-blue-800 hover:underline font-medium text-sm px-2 py-1 rounded transition-colors" 
                            data-id="${finalId}"
                            data-record-date="${r.record_date}"
                            data-record-shift="${r.shift}"
                            title="Ver detalhes">
                            Detalhes
                        </button>
                        <button onclick="confirmDeleteVolumeRecord(${finalId})" 
                            class="text-red-600 hover:text-red-800 hover:bg-red-50 px-2 py-1 rounded transition-colors flex items-center gap-1" 
                            title="Excluir registro"
                            data-date="${formattedDate}"
                            data-shift="${formattedShift}"
                            data-volume="${formattedVolume}"
                            data-record-type="${r.record_type || 'general'}"
                            data-animal-name="${r.animal_name || ''}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Excluir
                        </button>
                        ` : '<span class="text-xs text-gray-400">Sem ID válido</span>'}
                    </div>
                </td>
            </tr>
        `;
        }).join('');
        
        if (htmlRows === '' || !htmlRows.trim()) {
            console.error('❌ Nenhum registro HTML gerado. Dados recebidos:', rows);
            tbody.innerHTML = '<tr><td colspan="6" class="text-center py-8 text-red-500">Erro: Nenhum registro válido encontrado</td></tr>';
            return;
        }
        
        tbody.innerHTML = htmlRows;
        
        console.log(`✅ ${rows.length} registros de volume carregados e exibidos`);
    } catch (e) {
        console.error('Erro ao carregar registros de volume:', e);
        if (tbody) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center py-8 text-red-500">Erro ao carregar registros: ' + (e.message || 'Erro desconhecido') + '</td></tr>';
        }
    }
}

// Função para visualizar detalhes de um registro
async function viewVolumeDetails(id) {
    try {
        console.log('🔍 Buscando detalhes do registro ID:', id);
        
        // Validar e converter ID
        const recordId = parseInt(id, 10);
        if (!recordId || isNaN(recordId) || recordId <= 0) {
            console.error('❌ ID inválido recebido:', id, typeof id);
            showErrorModal('ID do registro inválido');
            return;
        }
        
        console.log('📡 Fazendo requisição para:', `./api/volume.php?action=get_by_id&id=${recordId}`);
        const response = await fetch(`./api/volume.php?action=get_by_id&id=${recordId}`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        console.log('📦 Resposta da API get_by_id:', result);
        
        if (!result.success || !result.data) {
            console.error('❌ Erro na resposta da API:', result);
            showErrorModal(result.error || 'Erro ao carregar detalhes do registro');
            return;
        }
        
        const record = result.data;
        console.log('✅ Registro encontrado:', record);
        
        // Formatar período
        const formatShift = (shift) => {
            const shifts = {
                'manha': 'Manhã',
                'tarde': 'Tarde',
                'noite': 'Noite'
            };
            return shifts[shift] || shift || '-';
        };
        
        // Função para formatar data corretamente (evitar problemas de timezone)
        const formatDate = (dateStr) => {
            if (!dateStr) return '-';
            // Se a data está no formato YYYY-MM-DD, parsear como data local
            if (typeof dateStr === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(dateStr)) {
                const [year, month, day] = dateStr.split('-').map(Number);
                const date = new Date(year, month - 1, day);
                return date.toLocaleDateString('pt-BR', { 
                    day: '2-digit', 
                    month: '2-digit', 
                    year: 'numeric' 
                });
            }
            // Para outros formatos, usar o método padrão
            const date = new Date(dateStr);
            if (isNaN(date.getTime())) return '-';
            return date.toLocaleDateString('pt-BR', { 
                day: '2-digit', 
                month: '2-digit', 
                year: 'numeric' 
            });
        };
        
        // Função para formatar data e horário
        const formatDateTime = (dateTimeStr) => {
            if (!dateTimeStr) return '-';
            const date = new Date(dateTimeStr);
            if (isNaN(date.getTime())) return '-';
            return date.toLocaleString('pt-BR', { 
                day: '2-digit', 
                month: '2-digit', 
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        };
        
        // Criar modal de detalhes
        const modalHtml = `
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" onclick="closeVolumeDetailsModal()">
                <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto" onclick="event.stopPropagation()">
                    <!-- Header -->
                    <div class="bg-gradient-to-r from-blue-500 to-blue-600 px-6 py-4 flex items-center justify-between sticky top-0">
                        <div>
                            <h3 class="text-xl font-bold text-white">Detalhes do Registro de Volume</h3>
                            <p class="text-sm text-blue-100">ID: #${record.id}</p>
                        </div>
                        <button onclick="closeVolumeDetailsModal()" class="text-white/80 hover:text-white hover:bg-white/20 rounded-lg p-2 transition-all">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    
                    <!-- Content -->
                    <div class="p-6 space-y-6">
                        <!-- Informações Principais -->
                        <div class="bg-gradient-to-br from-slate-50 to-slate-100 border border-slate-200 rounded-xl p-5">
                            <h4 class="text-base font-bold text-slate-800 mb-4 flex items-center gap-2">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Informações Principais
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-slate-600 mb-1">Data da Coleta</label>
                                    <p class="text-base font-medium text-slate-900">${formatDate(record.date)}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-slate-600 mb-1">Período</label>
                                    <p class="text-base font-medium text-slate-900">${formatShift(record.shift)}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-slate-600 mb-1">Horário de Registro</label>
                                    <p class="text-base font-medium text-slate-900">${formatDateTime(record.created_at)}</p>
                                </div>
                            </div>
                        </div>
                        
                        ${record.record_type === 'individual' && record.animal_id ? `
                        <!-- Informações do Animal (Registro Individual) -->
                        <div class="bg-gradient-to-br from-indigo-50 to-indigo-100 border-2 border-indigo-200 rounded-xl p-5">
                            <h4 class="text-base font-bold text-slate-800 mb-4 flex items-center gap-2">
                                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                </svg>
                                Informações do Animal
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                ${record.animal_name ? `
                                <div class="bg-white rounded-lg p-4 border border-indigo-200">
                                    <label class="block text-sm font-semibold text-slate-600 mb-1">Nome</label>
                                    <p class="text-lg font-bold text-indigo-600">${record.animal_name}</p>
                                </div>
                                ` : ''}
                                ${record.animal_number ? `
                                <div class="bg-white rounded-lg p-4 border border-indigo-200">
                                    <label class="block text-sm font-semibold text-slate-600 mb-1">Número/Identificação</label>
                                    <p class="text-lg font-bold text-indigo-600">${record.animal_number}</p>
                                </div>
                                ` : ''}
                                ${record.animal_breed ? `
                                <div class="bg-white rounded-lg p-4 border border-indigo-200">
                                    <label class="block text-sm font-semibold text-slate-600 mb-1">Raça</label>
                                    <p class="text-base font-medium text-slate-900">${record.animal_breed}</p>
                                </div>
                                ` : ''}
                                ${record.animal_status ? `
                                <div class="bg-white rounded-lg p-4 border border-indigo-200">
                                    <label class="block text-sm font-semibold text-slate-600 mb-1">Status</label>
                                    <p class="text-base font-medium text-slate-900">${record.animal_status}</p>
                                </div>
                                ` : ''}
                                ${record.animal_gender ? `
                                <div class="bg-white rounded-lg p-4 border border-indigo-200">
                                    <label class="block text-sm font-semibold text-slate-600 mb-1">Gênero</label>
                                    <p class="text-base font-medium text-slate-900">${record.animal_gender === 'Fêmea' ? 'Fêmea' : record.animal_gender === 'Macho' ? 'Macho' : record.animal_gender}</p>
                                </div>
                                ` : ''}
                                ${record.animal_birth_date ? `
                                <div class="bg-white rounded-lg p-4 border border-indigo-200">
                                    <label class="block text-sm font-semibold text-slate-600 mb-1">Data de Nascimento</label>
                                    <p class="text-base font-medium text-slate-900">${formatDate(record.animal_birth_date)}</p>
                                </div>
                                ` : ''}
                                ${record.animal_age_days !== null && record.animal_age_days !== undefined ? `
                                <div class="bg-white rounded-lg p-4 border border-indigo-200">
                                    <label class="block text-sm font-semibold text-slate-600 mb-1">Idade</label>
                                    <p class="text-base font-medium text-slate-900">
                                        ${Math.floor(record.animal_age_days / 365)} ano(s) e ${Math.floor((record.animal_age_days % 365) / 30)} mês(es)
                                        <span class="text-xs text-slate-500 block mt-1">(${record.animal_age_days} dias)</span>
                                    </p>
                                </div>
                                ` : ''}
                            </div>
                        </div>
                        ` : ''}
                        
                        <!-- Volume e Estatísticas -->
                        <div class="bg-gradient-to-br from-blue-50 to-blue-100 border-2 border-blue-200 rounded-xl p-5">
                            <h4 class="text-base font-bold text-slate-800 mb-4 flex items-center gap-2">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 13l8 0c1.11 0 2.08-.402 2.599-1M21 13l-8 0c-1.11 0-2.08-.402-2.599-1M16 8V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v3m4 6h.01M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                Volume e Estatísticas
                            </h4>
                            <div class="grid grid-cols-1 ${record.record_type === 'individual' ? 'md:grid-cols-2' : 'md:grid-cols-3'} gap-4">
                                <div class="bg-white rounded-lg p-4 border border-blue-200">
                                    <label class="block text-sm font-semibold text-slate-600 mb-1">${record.record_type === 'individual' ? 'Volume Produzido' : 'Volume Total'}</label>
                                    <p class="text-2xl font-bold text-blue-600">${(record.total_volume || 0).toFixed(2)} L</p>
                                </div>
                                ${record.record_type === 'general' ? `
                                <div class="bg-white rounded-lg p-4 border border-blue-200">
                                    <label class="block text-sm font-semibold text-slate-600 mb-1">Total de Animais</label>
                                    <p class="text-2xl font-bold text-blue-600">${record.total_animals || 0}</p>
                                </div>
                                ` : ''}
                                <div class="bg-white rounded-lg p-4 border border-blue-200">
                                    <label class="block text-sm font-semibold text-slate-600 mb-1">${record.record_type === 'individual' ? 'Volume Individual' : 'Média por Animal'}</label>
                                    <p class="text-2xl font-bold text-blue-600">${(record.average_per_animal || 0).toFixed(2)} L</p>
                                </div>
                                ${record.record_type === 'individual' && record.temperature ? `
                                <div class="bg-white rounded-lg p-4 border border-blue-200">
                                    <label class="block text-sm font-semibold text-slate-600 mb-1">Temperatura</label>
                                    <p class="text-2xl font-bold text-orange-600">${record.temperature.toFixed(1)} °C</p>
                                </div>
                                ` : ''}
                            </div>
                        </div>
                        
                        ${record.notes ? `
                        <!-- Observações -->
                        <div class="bg-gradient-to-br from-amber-50 to-amber-100 border border-amber-200 rounded-xl p-5">
                            <h4 class="text-base font-bold text-slate-800 mb-3 flex items-center gap-2">
                                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                                Observações
                            </h4>
                            <p class="text-sm text-slate-700 whitespace-pre-wrap">${record.notes}</p>
                        </div>
                        ` : ''}
                    </div>
                    
                    <!-- Footer -->
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-3">
                        <button onclick="closeVolumeDetailsModal()" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg font-semibold transition-colors">
                            Fechar
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        // Remover modal anterior se existir
        const existingModal = document.getElementById('volumeDetailsModal');
        if (existingModal) {
            existingModal.remove();
        }
        
        // Criar e adicionar novo modal
        const modalDiv = document.createElement('div');
        modalDiv.id = 'volumeDetailsModal';
        modalDiv.innerHTML = modalHtml;
        document.body.appendChild(modalDiv);
        
    } catch (error) {
        console.error('Erro ao carregar detalhes:', error);
        showErrorModal('Erro ao carregar detalhes do registro');
    }
}

function closeVolumeDetailsModal() {
    const modal = document.getElementById('volumeDetailsModal');
    if (modal) {
        modal.remove();
    }
}

// Função para confirmar exclusão de registro
function confirmDeleteVolumeRecord(id) {
    // Buscar dados do botão clicado
    const button = event.target.closest('button');
    const date = button?.getAttribute('data-date') || '-';
    const shift = button?.getAttribute('data-shift') || '-';
    const volume = button?.getAttribute('data-volume') || '-';
    const recordType = button?.getAttribute('data-record-type') || 'general';
    const animalName = button?.getAttribute('data-animal-name') || '';
    const isIndividual = recordType === 'individual';
    
    // Criar modal de confirmação
    const modalHtml = `
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" onclick="closeDeleteVolumeModal()">
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full" onclick="event.stopPropagation()">
                <!-- Header -->
                <div class="bg-gradient-to-r from-red-500 to-red-600 px-6 py-4 flex items-center gap-3">
                    <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-white">Confirmar Exclusão</h3>
                        <p class="text-sm text-red-100">Esta ação não pode ser desfeita</p>
                    </div>
                    <button onclick="closeDeleteVolumeModal()" class="ml-auto text-white/80 hover:text-white hover:bg-white/20 rounded-lg p-2 transition-all">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                
                <!-- Content -->
                <div class="p-6">
                    <p class="text-base text-slate-700 mb-4">
                        Tem certeza que deseja excluir este registro de volume?
                    </p>
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                        <p class="text-sm font-semibold text-red-800 mb-2">Registro a ser excluído:</p>
                        <ul class="text-sm text-red-700 space-y-1">
                            ${isIndividual && animalName ? `<li><strong>Vaca:</strong> ${animalName}</li>` : ''}
                            <li><strong>Data:</strong> ${date}</li>
                            <li><strong>Período:</strong> ${shift}</li>
                            <li><strong>Volume:</strong> ${volume} L</li>
                        </ul>
                    </div>
                    <p class="text-sm text-red-600 font-semibold">⚠️ Esta ação é permanente e não pode ser desfeita!</p>
                </div>
                
                <!-- Footer -->
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-3">
                    <button onclick="closeDeleteVolumeModal()" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg font-semibold transition-colors">
                        Cancelar
                    </button>
                    <button onclick="deleteVolumeRecord(${id})" class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg font-semibold transition-colors flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Sim, Excluir
                    </button>
                </div>
            </div>
        </div>
    `;
    
    // Remover modal anterior se existir
    const existingModal = document.getElementById('deleteVolumeModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // Criar e adicionar novo modal
    const modalDiv = document.createElement('div');
    modalDiv.id = 'deleteVolumeModal';
    modalDiv.innerHTML = modalHtml;
    document.body.appendChild(modalDiv);
}

function closeDeleteVolumeModal() {
    const modal = document.getElementById('deleteVolumeModal');
    if (modal) {
        modal.remove();
    }
}

// Função para excluir registro de volume
async function deleteVolumeRecord(id) {
    try {
        const response = await fetch('./api/volume.php?action=delete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: id })
        });
        
        const result = await response.json();
        
        // Fechar modal de confirmação
        closeDeleteVolumeModal();
        
        if (result.success) {
            showSuccessModal('Registro excluído com sucesso!');
            // Recarregar tabela
            await loadVolumeRecordsTable();
        } else {
            showErrorModal(result.error || 'Erro ao excluir registro');
        }
    } catch (error) {
        console.error('Erro ao excluir registro:', error);
        closeDeleteVolumeModal();
        showErrorModal('Erro ao excluir registro');
    }
}

window.viewVolumeDetails = viewVolumeDetails;
window.closeVolumeDetailsModal = closeVolumeDetailsModal;
window.confirmDeleteVolumeRecord = confirmDeleteVolumeRecord;
window.closeDeleteVolumeModal = closeDeleteVolumeModal;
window.deleteVolumeRecord = deleteVolumeRecord;

// Atualizar atividades recentes
function updateRecentActivities(activities) {
    console.log('📋 Atualizando atividades recentes...', activities);
    
    const container = document.getElementById('recentActivities');
    if (!container) {
        console.warn('Container recentActivities não encontrado');
        return;
    }
    
    if (!activities || activities.length === 0) {
        container.innerHTML = `
            <div class="text-center py-8">
                <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <p class="text-gray-500 text-sm">Nenhuma atividade recente</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = activities.map(activity => `
        <div class="flex items-center space-x-3 p-3 bg-white rounded-lg border border-gray-200">
            <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div class="flex-1">
                <p class="text-sm font-medium text-gray-900">${activity.description}</p>
                <p class="text-xs text-gray-500">${activity.animal} • ${activity.date}</p>
            </div>
        </div>
    `).join('');
    
    console.log('✅ Atividades recentes atualizadas!');
}

// ==================== FUNÇÕES AUXILIARES ====================
function initializeOverlays() {
    // Inicializar overlays se necessário
    console.log('🔧 Inicializando overlays...');
}

function startAutoRefresh() {
    if (refreshTimer) {
        clearInterval(refreshTimer);
    }
    
    refreshTimer = setInterval(() => {
        if (currentTab === 'dashboard') {
            loadDashboardData();
        }
    }, CONFIG.refreshInterval);
}

function updateDateTime() {
    const now = new Date();
    const timeString = now.toLocaleString('pt-BR');
    console.log('🕐 Atualizando data/hora:', timeString);
}

// ==================== VOLUME ====================
async function loadVolumeData() {
    console.log('📊 Carregando dados de volume...');
    
    try {
        const response = await fetch('./api/endpoints/volume.php');
        const result = await response.json();
        
        if (result.success) {
            const data = result.data;
            
            // Atualizar métricas de volume
            const n = (v) => { const num = typeof v === 'number' ? v : parseFloat(v); return Number.isFinite(num) ? num : 0; };
            const volumeTodayEl = document.getElementById('volumeToday');
            if (volumeTodayEl) {
                volumeTodayEl.textContent = n(data.today?.total_volume).toFixed(1) + 'L';
            }
            const volumeWeekAvgEl = document.getElementById('volumeWeekAvg');
            if (volumeWeekAvgEl) {
                volumeWeekAvgEl.textContent = n(data.week?.total_volume).toFixed(0) + 'L';
            }
            const volumeMonthTotalEl = document.getElementById('volumeMonthTotal');
            if (volumeMonthTotalEl) {
                volumeMonthTotalEl.textContent = n(data.month?.total_volume).toFixed(0) + 'L';
            }

            // Gráfico Volume (aba Volume)
            renderVolumeTabChart(Array.isArray(data.chart) ? data.chart : []);

            // Tabela de registros
            await loadVolumeRecordsTable();
            
            console.log('✅ Dados de volume carregados!');
        } else {
            console.error('Erro na API de volume:', result.error);
        }
    } catch (error) {
        console.error('Erro na requisição de volume:', error);
    }
}

// ==================== QUALIDADE ====================
async function loadQualityData() {
    console.log('📊 Carregando dados de qualidade...');
    
    try {
        const response = await fetch('./api/endpoints/quality.php');
        const result = await response.json();
        
        if (result.success) {
            const data = result.data;
            
            // Atualizar métricas de qualidade
            const n = (v) => { const num = typeof v === 'number' ? v : parseFloat(v); return Number.isFinite(num) ? num : 0; };
            const qualityAvgFatEl = document.getElementById('qualityAvgFat');
            if (qualityAvgFatEl) {
                qualityAvgFatEl.textContent = n(data.overall?.avg_fat).toFixed(1) + '%';
            }
            
            const qualityAvgProteinEl = document.getElementById('qualityAvgProtein');
            if (qualityAvgProteinEl) {
                qualityAvgProteinEl.textContent = n(data.overall?.avg_protein).toFixed(1) + '%';
            }
            
            const qualityAvgCCSEl = document.getElementById('qualityAvgCCS');
            if (qualityAvgCCSEl) {
                qualityAvgCCSEl.textContent = n(data.overall?.avg_scc).toFixed(0);
            }
            
            const qualityTestsCountEl = document.getElementById('qualityTestsCount');
            if (qualityTestsCountEl) {
                qualityTestsCountEl.textContent = String(n(data.overall?.total_tests).toFixed(0));
            }

            // Gráfico de qualidade (usar gordura média por dia)
            const chartSeries = Array.isArray(data.chart) ? data.chart : [];
            const labels = chartSeries.map(i => i.production_date);
            const fatSeries = chartSeries.map(i => n(i.avg_fat));
            // Linha mesmo com 1 ponto
            if (fatSeries.length === 1) { labels.push(labels[0]); fatSeries.push(fatSeries[0]); }
            createOrUpdateLineChart('qualityTabChart', labels, fatSeries, '#22C55E');

            // Tabela de registros de qualidade
            await loadQualityRecordsTable();
            
            console.log('✅ Dados de qualidade carregados!');
        } else {
            console.error('Erro na API de qualidade:', result.error);
        }
    } catch (error) {
        console.error('Erro na requisição de qualidade:', error);
    }
}

async function loadQualityRecordsTable() {
    try {
        const res = await fetch('./api/quality.php?action=select');
        const json = await res.json();
        const rows = Array.isArray(json?.data) ? json.data : [];
        const tbody = document.getElementById('qualityRecordsTable');
        if (!tbody) return;
        if (rows.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center py-8 text-gray-500">Nenhum registro</td></tr>';
            return;
        }
        // Formatar data para exibição
        const formatDate = (dateStr) => {
            if (!dateStr) return '-';
            const date = new Date(dateStr);
            return date.toLocaleDateString('pt-BR');
        };
        
        tbody.innerHTML = rows.map(r => {
            const formattedDate = formatDate(r.test_date);
            const fatContent = r.fat_content ? Number(r.fat_content).toFixed(2) : '-';
            const proteinContent = r.protein_content ? Number(r.protein_content).toFixed(2) : '-';
            const somaticCells = r.somatic_cells ?? '-';
            
            return `
            <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                <td class="py-3 px-4">${formattedDate}</td>
                <td class="py-3 px-4">${fatContent}%</td>
                <td class="py-3 px-4">${proteinContent}%</td>
                <td class="py-3 px-4">${somaticCells}</td>
                <td class="py-3 px-4">
                    <div class="flex items-center justify-end gap-3">
                        <button onclick="viewQualityDetails(${r.id})" 
                            class="text-blue-600 hover:text-blue-800 hover:underline font-medium text-sm px-2 py-1 rounded transition-colors" 
                            data-id="${r.id}"
                            title="Ver detalhes">
                            Detalhes
                        </button>
                        <button onclick="confirmDeleteQualityRecord(${r.id})" 
                            class="text-red-600 hover:text-red-800 hover:bg-red-50 px-2 py-1 rounded transition-colors flex items-center gap-1" 
                            title="Excluir registro"
                            data-date="${formattedDate}"
                            data-fat="${fatContent}"
                            data-protein="${proteinContent}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Excluir
                        </button>
                    </div>
                </td>
            </tr>
        `;
        }).join('');
    } catch (e) {
        const tbody = document.getElementById('qualityRecordsTable');
        if (tbody) tbody.innerHTML = '<tr><td colspan="5" class="text-center py-8 text-red-500">Erro ao carregar registros</td></tr>';
    }
}

// ==================== FUNÇÕES DE GESTÃO DE QUALIDADE ====================
async function viewQualityDetails(id) {
    try {
        const response = await fetch(`./api/quality.php?action=get_by_id&id=${id}`);
        const result = await response.json();
        
        if (!result.success || !result.data) {
            showErrorModal('Erro ao carregar detalhes do registro');
            return;
        }
        
        const record = result.data;
        
        // Formatar data
        const formatDate = (dateStr) => {
            if (!dateStr) return '-';
            const date = new Date(dateStr);
            return date.toLocaleDateString('pt-BR', { 
                day: '2-digit', 
                month: '2-digit', 
                year: 'numeric' 
            });
        };
        
        // Criar modal de detalhes
        const modalHtml = `
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" onclick="closeQualityDetailsModal()">
                <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto" onclick="event.stopPropagation()">
                    <!-- Header -->
                    <div class="bg-gradient-to-r from-green-500 to-green-600 px-6 py-4 flex items-center justify-between sticky top-0">
                        <div>
                            <h3 class="text-xl font-bold text-white">Detalhes do Teste de Qualidade</h3>
                            <p class="text-sm text-green-100">ID: #${record.id}</p>
                        </div>
                        <button onclick="closeQualityDetailsModal()" class="text-white/80 hover:text-white hover:bg-white/20 rounded-lg p-2 transition-all">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    
                    <!-- Content -->
                    <div class="p-6 space-y-6">
                        <!-- Informações Principais -->
                        <div class="bg-gradient-to-br from-slate-50 to-slate-100 border border-slate-200 rounded-xl p-5">
                            <h4 class="text-base font-bold text-slate-800 mb-4 flex items-center gap-2">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Informações Principais
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-slate-600 mb-1">Data do Teste</label>
                                    <p class="text-base font-medium text-slate-900">${formatDate(record.test_date)}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-slate-600 mb-1">Tipo de Teste</label>
                                    <p class="text-base font-medium text-slate-900">${record.test_type || '-'}</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Resultados do Teste -->
                        <div class="bg-gradient-to-br from-green-50 to-green-100 border-2 border-green-200 rounded-xl p-5">
                            <h4 class="text-base font-bold text-slate-800 mb-4 flex items-center gap-2">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Resultados do Teste
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="bg-white rounded-lg p-4 border border-green-200">
                                    <label class="block text-sm font-semibold text-slate-600 mb-1">Gordura (%)</label>
                                    <p class="text-2xl font-bold text-green-600">${(record.fat_content || 0).toFixed(2)}%</p>
                                </div>
                                <div class="bg-white rounded-lg p-4 border border-green-200">
                                    <label class="block text-sm font-semibold text-slate-600 mb-1">Proteína (%)</label>
                                    <p class="text-2xl font-bold text-green-600">${(record.protein_content || 0).toFixed(2)}%</p>
                                </div>
                                <div class="bg-white rounded-lg p-4 border border-green-200">
                                    <label class="block text-sm font-semibold text-slate-600 mb-1">CCS</label>
                                    <p class="text-2xl font-bold text-green-600">${record.somatic_cells || '-'}</p>
                                </div>
                                <div class="bg-white rounded-lg p-4 border border-green-200">
                                    <label class="block text-sm font-semibold text-slate-600 mb-1">Contagem de Bactérias</label>
                                    <p class="text-2xl font-bold text-green-600">${record.bacteria_count || '-'}</p>
                                </div>
                            </div>
                        </div>
                        
                        ${record.laboratory ? `
                        <!-- Laboratório -->
                        <div class="bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200 rounded-xl p-5">
                            <h4 class="text-base font-bold text-slate-800 mb-3 flex items-center gap-2">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                                Laboratório
                            </h4>
                            <p class="text-sm text-slate-700">${record.laboratory}</p>
                            ${record.cost ? `<p class="text-sm text-slate-600 mt-2">Custo: R$ ${(record.cost).toFixed(2)}</p>` : ''}
                        </div>
                        ` : ''}
                        
                        ${record.antibiotics ? `
                        <!-- Antibióticos -->
                        <div class="bg-gradient-to-br from-amber-50 to-amber-100 border border-amber-200 rounded-xl p-5">
                            <h4 class="text-base font-bold text-slate-800 mb-3 flex items-center gap-2">
                                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                Antibióticos
                            </h4>
                            <p class="text-sm text-slate-700">${record.antibiotics}</p>
                        </div>
                        ` : ''}
                    </div>
                    
                    <!-- Footer -->
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-3">
                        <button onclick="closeQualityDetailsModal()" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg font-semibold transition-colors">
                            Fechar
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        // Remover modal anterior se existir
        const existingModal = document.getElementById('qualityDetailsModal');
        if (existingModal) {
            existingModal.remove();
        }
        
        // Criar e adicionar novo modal
        const modalDiv = document.createElement('div');
        modalDiv.id = 'qualityDetailsModal';
        modalDiv.innerHTML = modalHtml;
        document.body.appendChild(modalDiv);
        
    } catch (error) {
        console.error('Erro ao carregar detalhes:', error);
        showErrorModal('Erro ao carregar detalhes do registro');
    }
}

function closeQualityDetailsModal() {
    const modal = document.getElementById('qualityDetailsModal');
    if (modal) {
        modal.remove();
    }
}

// Função para confirmar exclusão de registro de qualidade
function confirmDeleteQualityRecord(id) {
    // Buscar dados do botão clicado
    const button = event.target.closest('button');
    const date = button?.getAttribute('data-date') || '-';
    const fat = button?.getAttribute('data-fat') || '-';
    const protein = button?.getAttribute('data-protein') || '-';
    
    // Criar modal de confirmação
    const modalHtml = `
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" onclick="closeDeleteQualityModal()">
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full" onclick="event.stopPropagation()">
                <!-- Header -->
                <div class="bg-gradient-to-r from-red-500 to-red-600 px-6 py-4 flex items-center gap-3">
                    <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-white">Confirmar Exclusão</h3>
                        <p class="text-sm text-red-100">Esta ação não pode ser desfeita</p>
                    </div>
                    <button onclick="closeDeleteQualityModal()" class="ml-auto text-white/80 hover:text-white hover:bg-white/20 rounded-lg p-2 transition-all">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                
                <!-- Content -->
                <div class="p-6">
                    <p class="text-base text-slate-700 mb-4">
                        Tem certeza que deseja excluir este registro de qualidade?
                    </p>
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                        <p class="text-sm font-semibold text-red-800 mb-2">Registro a ser excluído:</p>
                        <ul class="text-sm text-red-700 space-y-1">
                            <li><strong>Data:</strong> ${date}</li>
                            <li><strong>Gordura:</strong> ${fat}%</li>
                            <li><strong>Proteína:</strong> ${protein}%</li>
                        </ul>
                    </div>
                    <p class="text-sm text-red-600 font-semibold">⚠️ Esta ação é permanente e não pode ser desfeita!</p>
                </div>
                
                <!-- Footer -->
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-3">
                    <button onclick="closeDeleteQualityModal()" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg font-semibold transition-colors">
                        Cancelar
                    </button>
                    <button onclick="deleteQualityRecord(${id})" class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg font-semibold transition-colors flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Sim, Excluir
                    </button>
                </div>
            </div>
        </div>
    `;
    
    // Remover modal anterior se existir
    const existingModal = document.getElementById('deleteQualityModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // Criar e adicionar novo modal
    const modalDiv = document.createElement('div');
    modalDiv.id = 'deleteQualityModal';
    modalDiv.innerHTML = modalHtml;
    document.body.appendChild(modalDiv);
}

function closeDeleteQualityModal() {
    const modal = document.getElementById('deleteQualityModal');
    if (modal) {
        modal.remove();
    }
}

// Função para excluir registro de qualidade
async function deleteQualityRecord(id) {
    try {
        const response = await fetch('./api/quality.php?action=delete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: id })
        });
        
        const result = await response.json();
        
        // Fechar modal de confirmação
        closeDeleteQualityModal();
        
        if (result.success) {
            showSuccessModal('Registro excluído com sucesso!');
            // Recarregar tabela
            await loadQualityRecordsTable();
        } else {
            showErrorModal(result.error || 'Erro ao excluir registro');
        }
    } catch (error) {
        console.error('Erro ao excluir registro:', error);
        closeDeleteQualityModal();
        showErrorModal('Erro ao excluir registro');
    }
}

window.viewQualityDetails = viewQualityDetails;
window.closeQualityDetailsModal = closeQualityDetailsModal;
window.confirmDeleteQualityRecord = confirmDeleteQualityRecord;
window.closeDeleteQualityModal = closeDeleteQualityModal;
window.deleteQualityRecord = deleteQualityRecord;

// ==================== FINANCEIRO ====================
async function loadFinancialData() {
    console.log('📊 Carregando dados financeiros...');
    
    try {
        const response = await fetch('./api/endpoints/financial.php');
        const result = await response.json();
        
        if (result.success) {
            const data = result.data;
            
            // Atualizar métricas financeiras
            const n = (v) => { const num = typeof v === 'number' ? v : parseFloat(v); return Number.isFinite(num) ? num : 0; };
            const revenueTodayEl = document.getElementById('revenueToday');
            if (revenueTodayEl) {
                revenueTodayEl.textContent = 'R$ ' + n(data.monthly_summary?.total_revenue).toFixed(2);
            }
            
            const expensesTodayEl = document.getElementById('expensesToday');
            if (expensesTodayEl) {
                expensesTodayEl.textContent = 'R$ ' + n(data.monthly_summary?.total_expenses).toFixed(2);
            }
            
            const profitTodayEl = document.getElementById('profitToday');
            if (profitTodayEl) {
                profitTodayEl.textContent = 'R$ ' + n(data.monthly_summary?.net_profit).toFixed(2);
            }
            
            const revenueMonthEl = document.getElementById('revenueMonth');
            if (revenueMonthEl) {
                revenueMonthEl.textContent = 'R$ ' + n(data.monthly_summary?.total_revenue).toFixed(2);
            }

            // Gráfico Financeiro (receitas x despesas últimos 30 dias)
            renderFinancialChart(Array.isArray(data.cash_flow_chart) ? data.cash_flow_chart : []);

            // Tabela de registros financeiros
            await loadFinancialRecordsTable();
            
            console.log('✅ Dados financeiros carregados!');
        } else {
            console.error('Erro na API financeira:', result.error);
        }
    } catch (error) {
        console.error('Erro na requisição financeira:', error);
    }
}

function renderFinancialChart(series) {
    const labels = series.map(i => i.record_date);
    const revenue = series.map(i => Number(i.daily_revenue) || 0);
    const expenses = series.map(i => Number(i.daily_expenses) || 0);
    // Montar dois datasets
    const canvasId = 'financialTabChart';
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;
    if (charts[canvasId]) charts[canvasId].destroy();
    charts[canvasId] = new Chart(canvas.getContext('2d'), {
        type: 'line',
        data: {
            labels,
            datasets: [
                { label: 'Receitas', data: revenue, borderColor: '#22C55E', backgroundColor: '#22C55E1A', fill: true, tension: 0.3, borderWidth: 2, pointRadius: 2, showLine: true },
                { label: 'Despesas', data: expenses, borderColor: '#EF4444', backgroundColor: '#EF44441A', fill: true, tension: 0.3, borderWidth: 2, pointRadius: 2, showLine: true }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: true } },
            scales: { x: { display: true }, y: { display: true, beginAtZero: true } }
        }
    });
}

async function loadFinancialRecordsTable() {
    try {
        // Reutilizar o endpoint raiz para listar (já implementado em api/volume.php para volume; aqui usamos o próprio financial endpoint recente)
        const res = await fetch('./api/endpoints/financial.php');
        const json = await res.json();
        const rows = Array.isArray(json?.data?.recent_records) ? json.data.recent_records : [];
        const tbody = document.getElementById('financialRecordsTable');
        if (!tbody) return;
        if (rows.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center py-8 text-gray-500">Nenhum registro</td></tr>';
            return;
        }
        // Formatar data para exibição
        const formatDate = (dateStr) => {
            if (!dateStr) return '-';
            const date = new Date(dateStr);
            return date.toLocaleDateString('pt-BR');
        };
        
        // Formatar tipo
        const formatType = (type) => {
            const types = {
                'receita': 'Receita',
                'despesa': 'Despesa',
                'income': 'Receita',
                'expense': 'Despesa'
            };
            return types[type] || type || '-';
        };
        
        tbody.innerHTML = rows.map(r => {
            const formattedDate = formatDate(r.record_date);
            const formattedType = formatType(r.type);
            const description = r.description || '-';
            const amount = (Number(r.amount)||0).toFixed(2);
            const isIncome = r.type === 'receita' || r.type === 'income';
            
            return `
            <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                <td class="py-3 px-4">${formattedDate}</td>
                <td class="py-3 px-4">
                    <span class="px-2 py-1 rounded-full text-xs font-semibold ${isIncome ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}">
                        ${formattedType}
                    </span>
                </td>
                <td class="py-3 px-4">${description}</td>
                <td class="py-3 px-4 font-semibold ${isIncome ? 'text-green-600' : 'text-red-600'}">R$ ${amount}</td>
                <td class="py-3 px-4">
                    <div class="flex items-center justify-end gap-3">
                        <button onclick="viewFinancialDetails(${r.id})" 
                            class="text-blue-600 hover:text-blue-800 hover:underline font-medium text-sm px-2 py-1 rounded transition-colors" 
                            data-id="${r.id}"
                            title="Ver detalhes">
                            Detalhes
                        </button>
                        <button onclick="confirmDeleteFinancialRecord(${r.id})" 
                            class="text-red-600 hover:text-red-800 hover:bg-red-50 px-2 py-1 rounded transition-colors flex items-center gap-1" 
                            title="Excluir registro"
                            data-date="${formattedDate}"
                            data-type="${formattedType}"
                            data-amount="${amount}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Excluir
                        </button>
                    </div>
                </td>
            </tr>
        `;
        }).join('');
    } catch (e) {
        const tbody = document.getElementById('financialRecordsTable');
        if (tbody) tbody.innerHTML = '<tr><td colspan="5" class="text-center py-8 text-red-500">Erro ao carregar registros</td></tr>';
    }
}

// ==================== FUNÇÕES DE GESTÃO FINANCEIRA ====================
async function viewFinancialDetails(id) {
    try {
        const response = await fetch(`./api/financial.php?action=get_by_id&id=${id}`);
        const result = await response.json();
        
        if (!result.success || !result.data) {
            showErrorModal('Erro ao carregar detalhes do registro');
            return;
        }
        
        const record = result.data;
        
        // Formatar data
        const formatDate = (dateStr) => {
            if (!dateStr) return '-';
            const date = new Date(dateStr);
            return date.toLocaleDateString('pt-BR', { 
                day: '2-digit', 
                month: '2-digit', 
                year: 'numeric' 
            });
        };
        
        // Formatar tipo
        const formatType = (type) => {
            const types = {
                'receita': 'Receita',
                'despesa': 'Despesa',
                'income': 'Receita',
                'expense': 'Despesa'
            };
            return types[type] || type || '-';
        };
        
        const isIncome = record.type === 'receita' || record.type === 'income';
        
        // Criar modal de detalhes
        const modalHtml = `
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" onclick="closeFinancialDetailsModal()">
                <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto" onclick="event.stopPropagation()">
                    <!-- Header -->
                    <div class="bg-gradient-to-r ${isIncome ? 'from-green-500 to-green-600' : 'from-red-500 to-red-600'} px-6 py-4 flex items-center justify-between sticky top-0">
                        <div>
                            <h3 class="text-xl font-bold text-white">Detalhes do Registro Financeiro</h3>
                            <p class="text-sm ${isIncome ? 'text-green-100' : 'text-red-100'}">ID: #${record.id}</p>
                        </div>
                        <button onclick="closeFinancialDetailsModal()" class="text-white/80 hover:text-white hover:bg-white/20 rounded-lg p-2 transition-all">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    
                    <!-- Content -->
                    <div class="p-6 space-y-6">
                        <!-- Informações Principais -->
                        <div class="bg-gradient-to-br from-slate-50 to-slate-100 border border-slate-200 rounded-xl p-5">
                            <h4 class="text-base font-bold text-slate-800 mb-4 flex items-center gap-2">
                                <svg class="w-5 h-5 ${isIncome ? 'text-green-600' : 'text-red-600'}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Informações Principais
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-slate-600 mb-1">Data</label>
                                    <p class="text-base font-medium text-slate-900">${formatDate(record.record_date)}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-slate-600 mb-1">Tipo</label>
                                    <p class="text-base font-medium text-slate-900">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold ${isIncome ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}">
                                            ${formatType(record.type)}
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Valor -->
                        <div class="bg-gradient-to-br ${isIncome ? 'from-green-50 to-green-100 border-2 border-green-200' : 'from-red-50 to-red-100 border-2 border-red-200'} rounded-xl p-5">
                            <h4 class="text-base font-bold text-slate-800 mb-4 flex items-center gap-2">
                                <svg class="w-5 h-5 ${isIncome ? 'text-green-600' : 'text-red-600'}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Valor
                            </h4>
                            <div class="bg-white rounded-lg p-4 border ${isIncome ? 'border-green-200' : 'border-red-200'}">
                                <p class="text-4xl font-bold ${isIncome ? 'text-green-600' : 'text-red-600'}">R$ ${(record.amount || 0).toFixed(2)}</p>
                            </div>
                        </div>
                        
                        ${record.description ? `
                        <!-- Descrição -->
                        <div class="bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200 rounded-xl p-5">
                            <h4 class="text-base font-bold text-slate-800 mb-3 flex items-center gap-2">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                                Descrição
                            </h4>
                            <p class="text-sm text-slate-700 whitespace-pre-wrap">${record.description}</p>
                        </div>
                        ` : ''}
                    </div>
                    
                    <!-- Footer -->
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-3">
                        <button onclick="closeFinancialDetailsModal()" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg font-semibold transition-colors">
                            Fechar
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        // Remover modal anterior se existir
        const existingModal = document.getElementById('financialDetailsModal');
        if (existingModal) {
            existingModal.remove();
        }
        
        // Criar e adicionar novo modal
        const modalDiv = document.createElement('div');
        modalDiv.id = 'financialDetailsModal';
        modalDiv.innerHTML = modalHtml;
        document.body.appendChild(modalDiv);
        
    } catch (error) {
        console.error('Erro ao carregar detalhes:', error);
        showErrorModal('Erro ao carregar detalhes do registro');
    }
}

function closeFinancialDetailsModal() {
    const modal = document.getElementById('financialDetailsModal');
    if (modal) {
        modal.remove();
    }
}

// Função para confirmar exclusão de registro financeiro
function confirmDeleteFinancialRecord(id) {
    // Buscar dados do botão clicado
    const button = event.target.closest('button');
    const date = button?.getAttribute('data-date') || '-';
    const type = button?.getAttribute('data-type') || '-';
    const amount = button?.getAttribute('data-amount') || '-';
    
    // Criar modal de confirmação
    const modalHtml = `
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" onclick="closeDeleteFinancialModal()">
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full" onclick="event.stopPropagation()">
                <!-- Header -->
                <div class="bg-gradient-to-r from-red-500 to-red-600 px-6 py-4 flex items-center gap-3">
                    <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-white">Confirmar Exclusão</h3>
                        <p class="text-sm text-red-100">Esta ação não pode ser desfeita</p>
                    </div>
                    <button onclick="closeDeleteFinancialModal()" class="ml-auto text-white/80 hover:text-white hover:bg-white/20 rounded-lg p-2 transition-all">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                
                <!-- Content -->
                <div class="p-6">
                    <p class="text-base text-slate-700 mb-4">
                        Tem certeza que deseja excluir este registro financeiro?
                    </p>
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                        <p class="text-sm font-semibold text-red-800 mb-2">Registro a ser excluído:</p>
                        <ul class="text-sm text-red-700 space-y-1">
                            <li><strong>Data:</strong> ${date}</li>
                            <li><strong>Tipo:</strong> ${type}</li>
                            <li><strong>Valor:</strong> R$ ${amount}</li>
                        </ul>
                    </div>
                    <p class="text-sm text-red-600 font-semibold">⚠️ Esta ação é permanente e não pode ser desfeita!</p>
                </div>
                
                <!-- Footer -->
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-3">
                    <button onclick="closeDeleteFinancialModal()" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg font-semibold transition-colors">
                        Cancelar
                    </button>
                    <button onclick="deleteFinancialRecord(${id})" class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg font-semibold transition-colors flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Sim, Excluir
                    </button>
                </div>
            </div>
        </div>
    `;
    
    // Remover modal anterior se existir
    const existingModal = document.getElementById('deleteFinancialModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // Criar e adicionar novo modal
    const modalDiv = document.createElement('div');
    modalDiv.id = 'deleteFinancialModal';
    modalDiv.innerHTML = modalHtml;
    document.body.appendChild(modalDiv);
}

function closeDeleteFinancialModal() {
    const modal = document.getElementById('deleteFinancialModal');
    if (modal) {
        modal.remove();
    }
}

// Função para excluir registro financeiro
async function deleteFinancialRecord(id) {
    try {
        const response = await fetch('./api/financial.php?action=delete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: id })
        });
        
        const result = await response.json();
        
        // Fechar modal de confirmação
        closeDeleteFinancialModal();
        
        if (result.success) {
            showSuccessModal('Registro excluído com sucesso!');
            // Recarregar tabela
            await loadFinancialRecordsTable();
        } else {
            showErrorModal(result.error || 'Erro ao excluir registro');
        }
    } catch (error) {
        console.error('Erro ao excluir registro:', error);
        closeDeleteFinancialModal();
        showErrorModal('Erro ao excluir registro');
    }
}

window.viewFinancialDetails = viewFinancialDetails;
window.closeFinancialDetailsModal = closeFinancialDetailsModal;
window.confirmDeleteFinancialRecord = confirmDeleteFinancialRecord;
window.closeDeleteFinancialModal = closeDeleteFinancialModal;
window.deleteFinancialRecord = deleteFinancialRecord;

// ==================== USUÁRIOS ====================
async function loadUsersData() {
    console.log('📊 Carregando dados de usuários...');
    
    try {
        const response = await fetch('./api/users.php?action=select');
        const result = await response.json();
        
        if (result.success) {
            const data = result.data;
            
            // Atualizar métricas de usuários
            const n = (v) => { const num = typeof v === 'number' ? v : parseFloat(v); return Number.isFinite(num) ? num : 0; };
            
            // Total de usuários
            const totalUsersEl = document.getElementById('totalUsers');
            if (totalUsersEl) {
                totalUsersEl.textContent = String(n(data.total).toFixed(0));
            }
            
            // Usuários ativos - atualizar todos os elementos com esse ID
            const activeUsersMetrics = document.querySelectorAll('#activeUsers');
            activeUsersMetrics.forEach(el => {
                const activeCount = n(data.active);
                el.textContent = String(activeCount.toFixed(0));
            });
            
            // Debug se necessário
            if (activeUsersMetrics.length === 0) {
                console.warn('Elemento activeUsers não encontrado');
            } else {
                console.log('✅ Usuários Ativos atualizado:', data.active, 'elementos encontrados:', activeUsersMetrics.length);
            }
            
            // Preencher tabela de usuários
            const tbody = document.getElementById('usersTable');
            if (tbody) {
                const rows = Array.isArray(data.users) ? data.users : [];
                if (rows.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-8 text-gray-500">Nenhum usuário</td></tr>';
                } else {
                    tbody.innerHTML = rows.map(u => {
                        // Foto do usuário ou ícone padrão
                        const photoUrl = u.profile_photo ? `./uploads/profiles/${u.profile_photo.split('/').pop()}` : null;
                        const photoHtml = photoUrl ? 
                            `<img src="${photoUrl}?t=${Date.now()}" alt="${u.name}" class="w-10 h-10 rounded-full object-cover" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                             <svg class="w-10 h-10 text-gray-400 hidden" fill="currentColor" viewBox="0 0 24 24">
                                 <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                             </svg>` :
                            `<svg class="w-10 h-10 text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                                 <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                             </svg>`;
                        
                        // Status badge
                        const statusBadge = u.is_active ? 
                            '<span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs font-semibold">Ativo</span>' :
                            '<span class="px-2 py-1 bg-red-100 text-red-700 rounded-full text-xs font-semibold">Bloqueado</span>';
                        
                        // Ações (bloquear/desbloquear e excluir)
                        let actionsHtml = '<div class="flex items-center gap-2">';
                        
                        if (u.can_block) {
                            const blockAction = u.is_active ? 'bloquear' : 'desbloquear';
                            const blockIcon = u.is_active ? 
                                '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>' :
                                '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
                            const blockColor = u.is_active ? 'bg-red-500 hover:bg-red-600' : 'bg-green-500 hover:bg-green-600';
                            const blockText = u.is_active ? 'Bloquear' : 'Desbloquear';
                            
                            actionsHtml += `<button onclick="toggleUserBlock(${u.id}, ${u.is_active ? 0 : 1})" 
                                class="${blockColor} text-white px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors flex items-center gap-1" 
                                title="${blockText} usuário">
                                ${blockIcon}
                                ${blockText}
                            </button>`;
                        }
                        
                        if (u.can_delete) {
                            actionsHtml += `<button onclick="deleteUser(${u.id}, '${u.name.replace(/'/g, "\\'")}')" 
                                class="bg-red-500 hover:bg-red-600 text-white px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors flex items-center gap-1" 
                                title="Excluir usuário">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                                Excluir
                            </button>`;
                        }
                        
                        if (!u.can_block && !u.can_delete) {
                            actionsHtml += '<span class="text-gray-400 text-xs">Sem ações disponíveis</span>';
                        }
                        
                        actionsHtml += '</div>';
                        
                        return `
                            <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors"> 
                                <td class="py-3 px-4">
                                    <div class="flex items-center justify-center">
                                        ${photoHtml}
                                    </div>
                                </td>
                                <td class="py-3 px-4 font-medium">${u.name}</td>
                                <td class="py-3 px-4 text-gray-600">${u.email}</td>
                                <td class="py-3 px-4">
                                    <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-semibold capitalize">${u.role}</span>
                                </td>
                                <td class="py-3 px-4">${statusBadge}</td>
                                <td class="py-3 px-4">${actionsHtml}</td>
                            </tr>
                        `;
                    }).join('');
                }
            }
            
            console.log('✅ Dados de usuários carregados!');
        } else {
            console.error('Erro na API de usuários:', result.error);
        }
    } catch (error) {
        console.error('Erro na requisição de usuários:', error);
    }
}

// ==================== FUNÇÕES DE GESTÃO DE USUÁRIOS ====================
async function toggleUserBlock(userId, newStatus) {
    if (!confirm(`Tem certeza que deseja ${newStatus === 1 ? 'desbloquear' : 'bloquear'} este usuário?`)) {
        return;
    }
    
    try {
        const response = await fetch('./api/users.php?action=toggle_block', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ user_id: userId })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showSuccessModal(result.data.message);
            loadUsersData(); // Recarregar lista
        } else {
            showErrorModal(result.error || 'Erro ao bloquear/desbloquear usuário');
        }
    } catch (error) {
        console.error('Erro ao bloquear/desbloquear usuário:', error);
        showErrorModal('Erro ao bloquear/desbloquear usuário');
    }
}

async function deleteUser(userId, userName) {
    if (!confirm(`Tem certeza que deseja excluir o usuário "${userName}"?\n\nEsta ação não pode ser desfeita!`)) {
        return;
    }
    
    try {
        const response = await fetch('./api/users.php?action=delete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ user_id: userId })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showSuccessModal(result.data.message);
            loadUsersData(); // Recarregar lista
        } else {
            showErrorModal(result.error || 'Erro ao excluir usuário');
        }
    } catch (error) {
        console.error('Erro ao excluir usuário:', error);
        showErrorModal('Erro ao excluir usuário');
    }
}

window.toggleUserBlock = toggleUserBlock;
window.deleteUser = deleteUser;

// ==================== EXPORTAR FUNÇÕES GLOBAIS ====================
window.loadDashboardData = loadDashboardData;
window.loadVolumeData = loadVolumeData;
window.loadQualityData = loadQualityData;
window.loadFinancialData = loadFinancialData;
window.loadUsersData = loadUsersData;
window.switchTab = switchTab;
// Abrir modal de usuário (compatível com onclick="showUserOverlay()")
function showUserOverlay() {
    if (typeof openAddUserModal === 'function') {
        openAddUserModal();
    } else {
        // fallback: exibir modal por id
        const modal = document.getElementById('addUserModal') || document.getElementById('userOverlay');
        if (modal) {
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
    }
}
window.showUserOverlay = showUserOverlay;

// Finance: abrir modal de venda
function showSalesOverlay() {
    const modal = document.getElementById('salesOverlay');
    const form = document.getElementById('salesForm');
    const messageDiv = document.getElementById('salesMessage');
    
    if (modal) {
        // Resetar formulário e mensagens
        if (form) {
            form.reset();
            // Definir data padrão como hoje (usando timezone local)
            const dateInput = form.querySelector('input[name="sale_date"]');
            if (dateInput && !dateInput.value) {
                dateInput.value = getLocalDateString();
            }
        }
        if (messageDiv) {
            messageDiv.classList.add('hidden');
            messageDiv.className = 'hidden p-4 rounded-xl border';
        }
        
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
}
window.showSalesOverlay = showSalesOverlay;

// Finance: exportar CSV
async function exportFinancialReport() {
    try {
        const res = await fetch('./api/endpoints/financial.php');
        const json = await res.json();
        const rows = Array.isArray(json?.data?.recent_records) ? json.data.recent_records : [];
        if (rows.length === 0) {
            console.warn('Sem registros financeiros para exportar');
            return;
        }
        const header = ['Data','Tipo','Descrição','Valor'];
        const csvRows = [header.join(',')].concat(rows.map(r => {
            const cols = [r.record_date, r.type, (r.description||'').replace(/"/g,'""'), Number(r.amount)||0];
            return cols.map(c => typeof c === 'string' ? '"'+c+'"' : c).join(',');
        }));
        const blob = new Blob(["\uFEFF" + csvRows.join('\n')], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `financeiro_${new Date().toISOString().slice(0,10)}.csv`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    } catch (e) {
        console.error('Erro ao exportar financeiro:', e);
    }
}
window.exportFinancialReport = exportFinancialReport;

// Quality: abrir modal
function showQualityOverlay() {
    const modal = document.getElementById('qualityOverlay');
    const form = document.getElementById('qualityForm');
    const messageDiv = document.getElementById('qualityMessage');
    
    if (modal) {
        // Resetar formulário e mensagens
        if (form) {
            form.reset();
            // Definir data padrão como hoje (usando timezone local)
            const dateInput = form.querySelector('input[name="test_date"]');
            if (dateInput && !dateInput.value) {
                dateInput.value = getLocalDateString();
            }
        }
        if (messageDiv) {
            messageDiv.classList.add('hidden');
            messageDiv.className = 'hidden p-4 rounded-xl border';
        }
        
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
}
window.showQualityOverlay = showQualityOverlay;

async function exportQualityReport() {
    try {
        const res = await fetch('./api/quality.php?action=select');
        const json = await res.json();
        const rows = Array.isArray(json?.data) ? json.data : [];
        if (rows.length === 0) {
            console.warn('Sem registros de qualidade para exportar');
            return;
        }
        const header = ['Data','Gordura','Proteína','CCS','Laboratório'];
        const csvRows = [header.join(',')].concat(rows.map(r => {
            const cols = [r.test_date, r.fat_content||'', r.protein_content||'', r.somatic_cells||'', (r.laboratory||'').replace(/"/g,'""')];
            return cols.map(c => typeof c === 'string' ? '"'+c+'"' : c).join(',');
        }));
        const blob = new Blob(["\uFEFF" + csvRows.join('\n')], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `qualidade_${new Date().toISOString().slice(0,10)}.csv`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    } catch (e) {
        console.error('Erro ao exportar qualidade:', e);
    }
}
window.exportQualityReport = exportQualityReport;

document.addEventListener('DOMContentLoaded', () => {
    const generalVolumeForm = document.getElementById('generalVolumeForm');
    if (generalVolumeForm) {
        generalVolumeForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = generalVolumeForm.querySelector('button[type="submit"]');
            const messageDiv = document.getElementById('generalVolumeMessage');
            const originalText = submitBtn.innerHTML;

            // Desabilitar botão e mostrar loading
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<svg class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg> Registrando...';

            // Esconder mensagem anterior
            if (messageDiv) {
                messageDiv.classList.add('hidden');
            }

            const formData = new FormData(generalVolumeForm);
            
            // Validar número de vacas antes de enviar
            const totalAnimals = parseInt(formData.get('total_animals')) || 0;
            if (totalAnimals < 1) {
                if (messageDiv) {
                    messageDiv.className = 'p-4 rounded-xl border-2 border-red-200 bg-red-50 text-red-800 flex items-center gap-2';
                    messageDiv.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg> Informe o número de vacas participantes';
                    messageDiv.classList.remove('hidden');
                }
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                return;
            }
            
            formData.append('action', 'add_volume_general');
            try {
                const result = await offlineFetch('./api/actions.php', formData, 'volume_general');
                if (result.success) {
                    // Mostrar mensagem de sucesso
                    if (messageDiv) {
                        messageDiv.className = 'p-4 rounded-xl border-2 border-green-200 bg-green-50 text-green-800 flex items-center gap-2';
                        if (result.offline) {
                            messageDiv.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg> ' + (result.message || 'Registro salvo offline. Será sincronizado quando a conexão for restaurada.');
                        } else {
                            messageDiv.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Volume registrado com sucesso!';
                        }
                        messageDiv.classList.remove('hidden');
                    }

                    // Resetar formulário
                    generalVolumeForm.reset();

                    // Fechar modal após 1.5s (apenas se estiver online)
                    if (!result.offline) {
                        setTimeout(() => {
                            if (window.closeGeneralVolumeOverlay) window.closeGeneralVolumeOverlay();
                            
                            // Recarregar dados de volume
                            if (typeof loadVolumeData === 'function') {
                                loadVolumeData();
                            }
                            
                            // Recarregar tabela de registros diretamente após um delay
                            setTimeout(() => {
                                if (typeof loadVolumeRecordsTable === 'function') {
                                    loadVolumeRecordsTable();
                                }
                            }, 800);
                        }, 1500);
                    } else {
                        setTimeout(() => {
                            if (window.closeGeneralVolumeOverlay) window.closeGeneralVolumeOverlay();
                        }, 2000);
                    }
                } else {
                    // Mostrar mensagem de erro
                    if (messageDiv) {
                        messageDiv.className = 'p-4 rounded-xl border-2 border-red-200 bg-red-50 text-red-800 flex items-center gap-2';
                        messageDiv.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg> ' + (result.error || 'Erro ao registrar volume');
                        messageDiv.classList.remove('hidden');
                    }
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            } catch (err) {
                console.error('Falha ao registrar volume geral:', err);
                if (messageDiv) {
                    messageDiv.className = 'p-4 rounded-xl border-2 border-red-200 bg-red-50 text-red-800 flex items-center gap-2';
                    messageDiv.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg> Erro ao processar registro. ' + (err.message || 'Tente novamente.');
                    messageDiv.classList.remove('hidden');
                }
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        });
    }

    // Volume por animal
    const volumeForm = document.getElementById('volumeForm');
    if (volumeForm) {
        volumeForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = volumeForm.querySelector('button[type="submit"]');
            const messageDiv = document.getElementById('volumeMessage');
            const originalText = submitBtn.innerHTML;

            // Desabilitar botão e mostrar loading
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<svg class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg> Registrando...';

            // Esconder mensagem anterior
            if (messageDiv) {
                messageDiv.classList.add('hidden');
            }

            const formData = new FormData(volumeForm);
            formData.append('action', 'add_volume_by_animal');
            try {
                const result = await offlineFetch('./api/actions.php', formData, 'volume_animal');
                if (result.success) {
                    // Mostrar mensagem de sucesso
                    if (messageDiv) {
                        messageDiv.className = 'p-4 rounded-xl border-2 border-blue-200 bg-blue-50 text-blue-800 flex items-center gap-2';
                        if (result.offline) {
                            messageDiv.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg> ' + (result.message || 'Registro salvo offline. Será sincronizado quando a conexão for restaurada.');
                        } else {
                            messageDiv.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Volume registrado com sucesso!';
                        }
                        messageDiv.classList.remove('hidden');
                    }

                    // Resetar formulário
                    volumeForm.reset();

                    // Fechar modal após 1.5s (apenas se estiver online)
                    if (!result.offline) {
                        setTimeout(() => {
                            if (window.closeVolumeOverlay) window.closeVolumeOverlay();
                            loadVolumeData();
                        }, 1500);
                    } else {
                        setTimeout(() => {
                            if (window.closeVolumeOverlay) window.closeVolumeOverlay();
                        }, 2000);
                    }
                } else {
                    // Mostrar mensagem de erro
                    if (messageDiv) {
                        messageDiv.className = 'p-4 rounded-xl border-2 border-red-200 bg-red-50 text-red-800 flex items-center gap-2';
                        messageDiv.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg> ' + (result.error || 'Erro ao registrar volume');
                        messageDiv.classList.remove('hidden');
                    }
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            } catch (err) {
                console.error('Falha ao registrar volume por vaca:', err);
                if (messageDiv) {
                    messageDiv.className = 'p-4 rounded-xl border-2 border-red-200 bg-red-50 text-red-800 flex items-center gap-2';
                    messageDiv.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg> Erro de conexão. Tente novamente.';
                    messageDiv.classList.remove('hidden');
                }
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        });
    }

    // Qualidade
    const qualityForm = document.getElementById('qualityForm');
    if (qualityForm) {
        qualityForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = qualityForm.querySelector('button[type="submit"]');
            const messageDiv = document.getElementById('qualityMessage');
            const originalText = submitBtn.innerHTML;

            // Desabilitar botão e mostrar loading
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<svg class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg> Registrando...';

            // Esconder mensagem anterior
            if (messageDiv) {
                messageDiv.classList.add('hidden');
            }

            const formData = new FormData(qualityForm);
            formData.append('action', 'add_quality_test');
            try {
                const result = await offlineFetch('./api/actions.php', formData, 'quality');
                if (result.success) {
                    // Mostrar mensagem de sucesso
                    if (messageDiv) {
                        messageDiv.className = 'p-4 rounded-xl border-2 border-emerald-200 bg-emerald-50 text-emerald-800 flex items-center gap-2';
                        if (result.offline) {
                            messageDiv.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg> ' + (result.message || 'Registro salvo offline. Será sincronizado quando a conexão for restaurada.');
                        } else {
                            messageDiv.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Teste registrado com sucesso!';
                        }
                        messageDiv.classList.remove('hidden');
                    }

                    // Resetar formulário
                    qualityForm.reset();

                    // Fechar modal após 1.5s (apenas se estiver online)
                    if (!result.offline) {
                        setTimeout(() => {
                            if (window.closeQualityOverlay) window.closeQualityOverlay();
                            loadQualityData();
                        }, 1500);
                    } else {
                        setTimeout(() => {
                            if (window.closeQualityOverlay) window.closeQualityOverlay();
                        }, 2000);
                    }
                } else {
                    // Mostrar mensagem de erro
                    if (messageDiv) {
                        messageDiv.className = 'p-4 rounded-xl border-2 border-red-200 bg-red-50 text-red-800 flex items-center gap-2';
                        messageDiv.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg> ' + (result.error || 'Erro ao registrar teste');
                        messageDiv.classList.remove('hidden');
                    }
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            } catch (err) {
                console.error('Falha ao registrar teste de qualidade:', err);
                if (messageDiv) {
                    messageDiv.className = 'p-4 rounded-xl border-2 border-red-200 bg-red-50 text-red-800 flex items-center gap-2';
                    messageDiv.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg> Erro de conexão. Tente novamente.';
                    messageDiv.classList.remove('hidden');
                }
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        });
    }

    // Financeiro
    const salesForm = document.getElementById('salesForm');
    if (salesForm) {
        salesForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = salesForm.querySelector('button[type="submit"]');
            const messageDiv = document.getElementById('salesMessage');
            const originalText = submitBtn.innerHTML;

            // Desabilitar botão e mostrar loading
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<svg class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg> Registrando...';

            // Esconder mensagem anterior
            if (messageDiv) {
                messageDiv.classList.add('hidden');
            }

            const formData = new FormData(salesForm);
            formData.append('action', 'add_financial_record');
            formData.append('type', 'receita');
            formData.append('record_date', formData.get('sale_date'));
            formData.append('description', `Venda para ${formData.get('customer')}`);
            formData.append('amount', formData.get('total_amount'));
            try {
                const result = await offlineFetch('./api/actions.php', formData, 'financial');
                if (result.success) {
                    // Mostrar mensagem de sucesso
                    if (messageDiv) {
                        messageDiv.className = 'p-4 rounded-xl border-2 border-green-200 bg-green-50 text-green-800 flex items-center gap-2';
                        if (result.offline) {
                            messageDiv.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg> ' + (result.message || 'Registro salvo offline. Será sincronizado quando a conexão for restaurada.');
                        } else {
                            messageDiv.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Venda registrada com sucesso!';
                        }
                        messageDiv.classList.remove('hidden');
                    }

                    // Resetar formulário
                    salesForm.reset();

                    // Fechar modal após 1.5s (apenas se estiver online)
                    if (!result.offline) {
                        setTimeout(() => {
                            if (window.closeSalesOverlay) window.closeSalesOverlay();
                            loadFinancialData();
                        }, 1500);
                    } else {
                        setTimeout(() => {
                            if (window.closeSalesOverlay) window.closeSalesOverlay();
                        }, 2000);
                    }
                } else {
                    // Mostrar mensagem de erro
                    if (messageDiv) {
                        messageDiv.className = 'p-4 rounded-xl border-2 border-red-200 bg-red-50 text-red-800 flex items-center gap-2';
                        messageDiv.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg> ' + (result.error || 'Erro ao registrar venda');
                        messageDiv.classList.remove('hidden');
                    }
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            } catch (err) {
                console.error('Falha ao registrar venda:', err);
                if (messageDiv) {
                    messageDiv.className = 'p-4 rounded-xl border-2 border-red-200 bg-red-50 text-red-800 flex items-center gap-2';
                    messageDiv.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg> Erro de conexão. Tente novamente.';
                    messageDiv.classList.remove('hidden');
                }
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        });
    }

    // Usuários
    const addUserForm = document.getElementById('addUserForm');
    if (addUserForm) {
        // Gerar email automaticamente ao submeter, se não informado
        const generateEmailFromName = (fullName) => {
            if (!fullName || typeof fullName !== 'string') return null;
            const firstName = fullName.trim().split(/\s+/)[0] || 'user';
            const slug = firstName
                .normalize('NFD').replace(/\p{Diacritic}/gu, '') // remove acentos
                .toLowerCase().replace(/[^a-z0-9]/g, '');
            const rand = Math.floor(100 + Math.random() * 900); // 3 dígitos
            return `${slug}${rand}@lactech.com`;
        };

        addUserForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = addUserForm.querySelector('button[type="submit"]');
            const messageDiv = document.getElementById('addUserMessage');
            const originalText = submitBtn.innerHTML;

            // Validar senhas
            const formData = new FormData(addUserForm);
            if (formData.get('password') !== formData.get('confirm_password')) {
                if (messageDiv) {
                    messageDiv.className = 'p-4 rounded-xl border-2 border-red-200 bg-red-50 text-red-800 flex items-center gap-2';
                    messageDiv.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg> As senhas não coincidem!';
                    messageDiv.classList.remove('hidden');
                }
                return;
            }

            // Desabilitar botão e mostrar loading
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<svg class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg> Criando...';

            // Esconder mensagem anterior
            if (messageDiv) {
                messageDiv.classList.add('hidden');
            }

            // Forçar criação como funcionário
            formData.set('role', 'funcionario');
            // Gerar email automático se vazio
            const currentEmail = (formData.get('email') || '').toString().trim();
            if (!currentEmail) {
                const name = (formData.get('name') || '').toString();
                const autoEmail = generateEmailFromName(name);
                if (autoEmail) {
                    formData.set('email', autoEmail);
                    // também reflete no input para o usuário ver
                    const emailInput = addUserForm.querySelector('input[name="email"]');
                    if (emailInput) emailInput.value = autoEmail;
                }
            }
            formData.append('action', 'create_user');
            try {
                const result = await offlineFetch('./api/actions.php', formData, 'create_user');
                if (result.success) {
                    // Mostrar mensagem de sucesso ou offline
                    if (messageDiv) {
                        if (result.offline) {
                            messageDiv.className = 'p-4 rounded-xl border-2 border-yellow-200 bg-yellow-50 text-yellow-800 flex items-center gap-2';
                            messageDiv.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg> ' + (result.message || 'Usuário salvo offline. Será sincronizado quando a conexão for restaurada.');
                        } else {
                            messageDiv.className = 'p-4 rounded-xl border-2 border-blue-200 bg-blue-50 text-blue-800 flex items-center gap-2';
                            messageDiv.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Usuário criado com sucesso!';
                        }
                        messageDiv.classList.remove('hidden');
                    }
                    
                    // Se estiver offline, não fechar modal nem recarregar dados
                    if (result.offline) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalText;
                        return;
                    }

                    // Resetar formulário
                    addUserForm.reset();

                    // Fechar modal após 1.5s
                    setTimeout(() => {
                        if (window.closeAddUserModal) window.closeAddUserModal();
                        loadUsersData();
                    }, 1500);
                } else {
                    // Mostrar mensagem de erro
                    if (messageDiv) {
                        messageDiv.className = 'p-4 rounded-xl border-2 border-red-200 bg-red-50 text-red-800 flex items-center gap-2';
                        messageDiv.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg> ' + (result.error || 'Erro ao criar usuário');
                        messageDiv.classList.remove('hidden');
                    }
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            } catch (err) {
                console.error('Falha ao criar usuário:', err);
                if (messageDiv) {
                    messageDiv.className = 'p-4 rounded-xl border-2 border-red-200 bg-red-50 text-red-800 flex items-center gap-2';
                    messageDiv.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg> Erro de conexão. Tente novamente.';
                    messageDiv.classList.remove('hidden');
                }
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        });
    }
});

// Volume: abrir modais
function showGeneralVolumeOverlay() {
    const modal = document.getElementById('generalVolumeOverlay');
    const form = document.getElementById('generalVolumeForm');
    const messageDiv = document.getElementById('generalVolumeMessage');
    
    if (modal) {
        // Resetar formulário e mensagens
        if (form) {
            form.reset();
            // Definir data padrão como hoje (usando timezone local)
            const dateInput = form.querySelector('input[name="collection_date"]');
            if (dateInput && !dateInput.value) {
                dateInput.value = getLocalDateString();
            }
        }
        if (messageDiv) {
            messageDiv.classList.add('hidden');
            messageDiv.className = 'hidden p-4 rounded-xl border';
        }
        
        // Resetar cálculo de média por vaca
        const averageDisplay = document.getElementById('averagePerCowDisplay');
        if (averageDisplay) {
            averageDisplay.textContent = 'Média por vaca: -- L';
            averageDisplay.classList.remove('text-green-600', 'font-semibold');
            averageDisplay.classList.add('text-slate-500');
        }
        
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
}

// Calcular média por vaca em tempo real
function updateAveragePerCow() {
    const totalAnimalsInput = document.getElementById('totalAnimalsInput');
    const totalVolumeInput = document.getElementById('totalVolumeInput');
    const averageDisplay = document.getElementById('averagePerCowDisplay');
    
    if (totalAnimalsInput && totalVolumeInput && averageDisplay) {
        const totalAnimals = parseFloat(totalAnimalsInput.value) || 0;
        const totalVolume = parseFloat(totalVolumeInput.value) || 0;
        
        if (totalAnimals > 0 && totalVolume > 0) {
            const average = totalVolume / totalAnimals;
            averageDisplay.textContent = `Média por vaca: ${average.toFixed(2)} L`;
            averageDisplay.classList.remove('text-slate-500');
            averageDisplay.classList.add('text-green-600', 'font-semibold');
        } else {
            averageDisplay.textContent = 'Média por vaca: -- L';
            averageDisplay.classList.remove('text-green-600', 'font-semibold');
            averageDisplay.classList.add('text-slate-500');
        }
    }
}

// Adicionar event listeners para calcular média em tempo real quando o modal abrir
document.addEventListener('DOMContentLoaded', function() {
    // Usar delegação de eventos para funcionar mesmo quando os elementos são criados dinamicamente
    document.addEventListener('input', function(e) {
        if (e.target.id === 'totalAnimalsInput' || e.target.id === 'totalVolumeInput') {
            updateAveragePerCow();
        }
    });
    
    document.addEventListener('change', function(e) {
        if (e.target.id === 'totalAnimalsInput' || e.target.id === 'totalVolumeInput') {
            updateAveragePerCow();
        }
    });
});
function showVolumeOverlay() {
    const modal = document.getElementById('volumeOverlay');
    const form = document.getElementById('volumeForm');
    const messageDiv = document.getElementById('volumeMessage');
    
    if (modal) {
        // Resetar formulário e mensagens
        if (form) {
            form.reset();
            // Definir data padrão como hoje (usando timezone local)
            const dateInput = form.querySelector('input[name="collection_date"]');
            if (dateInput && !dateInput.value) {
                dateInput.value = getLocalDateString();
            }
        }
        if (messageDiv) {
            messageDiv.classList.add('hidden');
            messageDiv.className = 'hidden p-4 rounded-xl border';
        }
        
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        populateVolumeAnimalSelect();
    }
}
window.showGeneralVolumeOverlay = showGeneralVolumeOverlay;
window.showVolumeOverlay = showVolumeOverlay;

// Variável global para armazenar a chave de backup
let volumeBackupKey = null;

// Função para mostrar modal de exclusão de todos os registros
function showDeleteAllVolumeModal() {
    const modal = document.getElementById('deleteAllVolumeModal');
    if (!modal) return;
    
    // Resetar flag de exclusão
    isDeletingVolume = false;
    
    // Resetar botão de confirmação
    const confirmBtn = modal.querySelector('button[onclick*="confirmDeleteAllVolume"]');
    if (confirmBtn) {
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = `
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
            </svg>
            Sim, Excluir Todos
        `;
    }
    
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    
    // Carregar quantidade de registros
    loadVolumeRecordsCount();
}

// Função para fechar modal de exclusão
function closeDeleteAllVolumeModal() {
    const modal = document.getElementById('deleteAllVolumeModal');
    if (modal) {
        modal.classList.add('hidden');
        document.body.style.overflow = 'auto';
    }
}

// Carregar quantidade de registros de volume
async function loadVolumeRecordsCount() {
    const countElement = document.getElementById('volumeRecordsCount');
    if (!countElement) return;
    
    try {
        const response = await fetch('./api/volume.php?action=get_stats');
        const data = await response.json();
        
        if (data && data.total_records !== undefined) {
            countElement.textContent = `Total de registros: ${data.total_records}`;
        } else {
            // Tentar contar através da tabela
            const response2 = await fetch('./api/volume.php?action=get_all');
            const records = await response2.json();
            if (Array.isArray(records)) {
                countElement.textContent = `Total de registros: ${records.length}`;
            } else {
                countElement.textContent = 'Não foi possível carregar a quantidade';
            }
        }
    } catch (error) {
        console.error('Erro ao carregar quantidade de registros:', error);
        countElement.textContent = 'Erro ao carregar quantidade';
    }
}

// Variável para controlar se já está processando exclusão
let isDeletingVolume = false;

// Confirmar exclusão de todos os registros
async function confirmDeleteAllVolume(event) {
    // Prevenir múltiplas execuções
    if (isDeletingVolume) {
        return;
    }
    
    // Prevenir comportamento padrão se for evento
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    const modal = document.getElementById('deleteAllVolumeModal');
    if (!modal) return;
    
    // Encontrar o botão de confirmação no modal
    const confirmBtn = modal.querySelector('button[onclick*="confirmDeleteAllVolume"]') || event?.target?.closest('button');
    if (!confirmBtn) return;
    
    // Verificar se já está desabilitado
    if (confirmBtn.disabled) {
        return;
    }
    
    isDeletingVolume = true;
    const originalText = confirmBtn.innerHTML;
    
    // Desabilitar botão e mostrar loading
    confirmBtn.disabled = true;
    confirmBtn.innerHTML = '<svg class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg> Excluindo...';
    
    try {
        const formData = new FormData();
        formData.append('action', 'delete_all_volume_records');
        
        // Verificar se está offline antes de fazer requisição
        const forceOffline = localStorage.getItem('lactech_force_offline') === 'true';
        if (forceOffline || !navigator.onLine) {
            alert('Esta ação não pode ser executada no modo offline. Por favor, desative o modo offline primeiro.');
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = originalText;
            return;
        }
        
        const response = await fetch('./api/actions.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Salvar chave de backup para poder desfazer
            volumeBackupKey = result.backup_key;
            
            // Fechar modal
            closeDeleteAllVolumeModal();
            
            // Mostrar notificação de sucesso com botão de desfazer
            showVolumeDeleteNotification(result.message, result.total_deleted, volumeBackupKey);
            
            // Recarregar dados de volume
            if (typeof loadVolumeData === 'function') {
                loadVolumeData();
            }
            
            // Atualizar dashboard
            if (typeof loadDashboardData === 'function') {
                loadDashboardData();
            }
        } else {
            // Mostrar erro
            alert('Erro ao excluir registros: ' + (result.error || 'Erro desconhecido'));
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = originalText;
        }
    } catch (error) {
        console.error('Erro ao excluir todos os registros:', error);
        alert('Erro ao excluir registros: ' + error.message);
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = originalText;
    } finally {
        // Resetar flag após um pequeno delay para garantir que tudo terminou
        setTimeout(() => {
            isDeletingVolume = false;
        }, 1000);
    }
}

// Variável para armazenar o timer de auto-fechamento
let volumeDeleteNotificationTimer = null;
let volumeDeleteNotificationCountdown = 0;

// Mostrar notificação de exclusão com botão de desfazer
function showVolumeDeleteNotification(message, totalDeleted, backupKey) {
    // Remover notificação anterior se existir
    const existingNotification = document.getElementById('volumeDeleteNotification');
    if (existingNotification) {
        existingNotification.remove();
    }
    
    // Limpar timer anterior se existir
    if (volumeDeleteNotificationTimer) {
        clearInterval(volumeDeleteNotificationTimer);
        volumeDeleteNotificationTimer = null;
    }
    
    // Resetar contador
    volumeDeleteNotificationCountdown = 5;
    
    // Criar elemento de notificação
    const notification = document.createElement('div');
    notification.id = 'volumeDeleteNotification';
    notification.className = 'fixed top-4 right-4 z-50 bg-white rounded-xl shadow-2xl border-2 border-green-200 p-4 max-w-md animate-slideDown';
    notification.innerHTML = `
        <div class="flex items-start gap-3">
            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <div class="flex-1">
                <div class="flex items-center justify-between mb-1">
                    <h4 class="font-bold text-gray-900">Registros Excluídos</h4>
                    <span id="volumeDeleteCountdown" class="text-sm font-semibold text-green-600">5s</span>
                </div>
                <p class="text-sm text-gray-700 mb-2">${message}</p>
                <div class="mb-3">
                    <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                        <div id="volumeDeleteProgressBar" class="bg-green-600 h-2 rounded-full transition-all duration-1000" style="width: 100%"></div>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button onclick="restoreVolumeRecords('${backupKey}')" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-all font-semibold text-sm flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                        </svg>
                        Desfazer
                    </button>
                    <button onclick="closeVolumeDeleteNotification()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-all font-semibold text-sm">
                        Fechar
                    </button>
                </div>
            </div>
            <button onclick="closeVolumeDeleteNotification()" class="text-gray-400 hover:text-gray-600 transition-all">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Iniciar contador regressivo
    const countdownElement = document.getElementById('volumeDeleteCountdown');
    const progressBar = document.getElementById('volumeDeleteProgressBar');
    
    volumeDeleteNotificationTimer = setInterval(() => {
        volumeDeleteNotificationCountdown--;
        
        if (countdownElement) {
            countdownElement.textContent = `${volumeDeleteNotificationCountdown}s`;
        }
        
        if (progressBar) {
            const percentage = (volumeDeleteNotificationCountdown / 5) * 100;
            progressBar.style.width = percentage + '%';
        }
        
        if (volumeDeleteNotificationCountdown <= 0) {
            clearInterval(volumeDeleteNotificationTimer);
            volumeDeleteNotificationTimer = null;
            closeVolumeDeleteNotification();
        }
    }, 1000);
}

// Fechar notificação de exclusão
function closeVolumeDeleteNotification() {
    // Limpar timer
    if (volumeDeleteNotificationTimer) {
        clearInterval(volumeDeleteNotificationTimer);
        volumeDeleteNotificationTimer = null;
    }
    
    const notification = document.getElementById('volumeDeleteNotification');
    if (notification) {
        notification.remove();
    }
    
    // Limpar chave de backup quando fechar notificação
    volumeBackupKey = null;
    volumeDeleteNotificationCountdown = 0;
}

// Restaurar registros de volume
async function restoreVolumeRecords(backupKey) {
    if (!backupKey) {
        alert('Chave de backup não encontrada');
        return;
    }
    
    const restoreBtn = event.target.closest('button');
    if (!restoreBtn) return;
    
    const originalText = restoreBtn.innerHTML;
    
    // Desabilitar botão e mostrar loading
    restoreBtn.disabled = true;
    restoreBtn.innerHTML = '<svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg> Restaurando...';
    
    try {
        const formData = new FormData();
        formData.append('action', 'restore_volume_records');
        formData.append('backup_key', backupKey);
        
        // Verificar se está offline antes de fazer requisição
        const forceOffline = localStorage.getItem('lactech_force_offline') === 'true';
        if (forceOffline || !navigator.onLine) {
            alert('Esta ação não pode ser executada no modo offline. Por favor, desative o modo offline primeiro.');
            restoreBtn.disabled = false;
            restoreBtn.innerHTML = originalText;
            return;
        }
        
        const response = await fetch('./api/actions.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Fechar notificação
            closeVolumeDeleteNotification();
            
            // Limpar chave de backup
            volumeBackupKey = null;
            
            // Mostrar modal de sucesso
            showRestoreVolumeSuccessModal(result.message, result.total_restored);
            
            // Recarregar dados de volume
            if (typeof loadVolumeData === 'function') {
                loadVolumeData();
            }
            
            // Atualizar dashboard
            if (typeof loadDashboardData === 'function') {
                loadDashboardData();
            }
        } else {
            // Mostrar modal de erro
            showRestoreVolumeErrorModal(result.error || 'Erro desconhecido');
            restoreBtn.disabled = false;
            restoreBtn.innerHTML = originalText;
        }
    } catch (error) {
        console.error('Erro ao restaurar registros:', error);
        // Mostrar modal de erro
        showRestoreVolumeErrorModal(error.message || 'Erro ao restaurar registros');
        restoreBtn.disabled = false;
        restoreBtn.innerHTML = originalText;
    }
}

// Mostrar modal de sucesso após restauração
function showRestoreVolumeSuccessModal(message, totalRestored) {
    const modal = document.getElementById('restoreVolumeSuccessModal');
    if (!modal) return;
    
    // Atualizar mensagem
    const messageElement = document.getElementById('restoreSuccessMessage');
    if (messageElement) {
        messageElement.textContent = message || 'Todos os registros de volume foram restaurados com sucesso.';
    }
    
    // Atualizar contagem
    const countElement = document.getElementById('restoreSuccessCount');
    if (countElement && totalRestored) {
        countElement.textContent = `${totalRestored} registro(s) restaurado(s) com sucesso.`;
    } else if (countElement) {
        countElement.textContent = 'Registros restaurados com sucesso.';
    }
    
    // Mostrar modal
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    
    // Auto-fechar após 5 segundos
    setTimeout(() => {
        closeRestoreVolumeSuccessModal();
    }, 5000);
}

// Fechar modal de sucesso de restauração
function closeRestoreVolumeSuccessModal() {
    const modal = document.getElementById('restoreVolumeSuccessModal');
    if (modal) {
        modal.classList.add('hidden');
        document.body.style.overflow = 'auto';
    }
}

// Mostrar modal de erro de restauração
function showRestoreVolumeErrorModal(errorMessage) {
    const modal = document.getElementById('restoreVolumeErrorModal');
    if (!modal) return;
    
    // Atualizar mensagem de erro
    const messageElement = document.getElementById('restoreErrorMessage');
    if (messageElement) {
        messageElement.textContent = errorMessage || 'Ocorreu um erro ao tentar restaurar os registros.';
    }
    
    // Mostrar modal
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

// Fechar modal de erro de restauração
function closeRestoreVolumeErrorModal() {
    const modal = document.getElementById('restoreVolumeErrorModal');
    if (modal) {
        modal.classList.add('hidden');
        document.body.style.overflow = 'auto';
    }
}

// Tornar funções globais
window.showDeleteAllVolumeModal = showDeleteAllVolumeModal;
window.closeDeleteAllVolumeModal = closeDeleteAllVolumeModal;
window.confirmDeleteAllVolume = confirmDeleteAllVolume;
window.restoreVolumeRecords = restoreVolumeRecords;
window.closeVolumeDeleteNotification = closeVolumeDeleteNotification;
window.showRestoreVolumeSuccessModal = showRestoreVolumeSuccessModal;
window.closeRestoreVolumeSuccessModal = closeRestoreVolumeSuccessModal;
window.showRestoreVolumeErrorModal = showRestoreVolumeErrorModal;
window.closeRestoreVolumeErrorModal = closeRestoreVolumeErrorModal;

// Helpers para fechar modais (garantir que os onClick funcionem)
function closeGeneralVolumeOverlay() {
    const modal = document.getElementById('generalVolumeOverlay');
    const form = document.getElementById('generalVolumeForm');
    const messageDiv = document.getElementById('generalVolumeMessage');
    
    if (modal) {
        modal.classList.add('hidden');
        document.body.style.overflow = 'auto';
    }
    
    // Limpar formulário e mensagens
    if (form) {
        form.reset();
    }
    if (messageDiv) {
        messageDiv.classList.add('hidden');
        messageDiv.className = 'hidden p-4 rounded-xl border';
    }
}
function closeVolumeOverlay() {
    const modal = document.getElementById('volumeOverlay');
    const form = document.getElementById('volumeForm');
    const messageDiv = document.getElementById('volumeMessage');
    
    if (modal) {
        modal.classList.add('hidden');
        document.body.style.overflow = 'auto';
    }
    
    // Limpar formulário e mensagens
    if (form) {
        form.reset();
    }
    if (messageDiv) {
        messageDiv.classList.add('hidden');
        messageDiv.className = 'hidden p-4 rounded-xl border';
    }
}
function closeQualityOverlay() {
    const modal = document.getElementById('qualityOverlay');
    const form = document.getElementById('qualityForm');
    const messageDiv = document.getElementById('qualityMessage');
    
    if (modal) {
        modal.classList.add('hidden');
        document.body.style.overflow = 'auto';
    }
    
    // Limpar formulário e mensagens
    if (form) {
        form.reset();
    }
    if (messageDiv) {
        messageDiv.classList.add('hidden');
        messageDiv.className = 'hidden p-4 rounded-xl border';
    }
}
function closeSalesOverlay() {
    const modal = document.getElementById('salesOverlay');
    const form = document.getElementById('salesForm');
    const messageDiv = document.getElementById('salesMessage');
    
    if (modal) {
        modal.classList.add('hidden');
        document.body.style.overflow = 'auto';
    }
    
    // Limpar formulário e mensagens
    if (form) {
        form.reset();
    }
    if (messageDiv) {
        messageDiv.classList.add('hidden');
        messageDiv.className = 'hidden p-4 rounded-xl border';
    }
}
window.closeGeneralVolumeOverlay = closeGeneralVolumeOverlay;
window.closeVolumeOverlay = closeVolumeOverlay;

// ==================== FUNÇÕES PARA GESTÃO DE REBANHO ====================

// Função para fechar submodal
window.closeSubModal = function(modalId) {
    const modal = document.getElementById(`modal-${modalId}`);
    if (modal) {
        modal.classList.remove('show');
        document.body.style.overflow = '';
        // Limpar formulário
        const form = modal.querySelector('form');
        if (form) {
            form.reset();
        }
        // Limpar mensagens
        const messageDiv = modal.querySelector('[id*="message"]');
        if (messageDiv) {
            messageDiv.classList.add('hidden');
            messageDiv.innerHTML = '';
        }
    }
};

// Abrir modal de adicionar animal
window.openAddAnimalModal = function() {
    console.log('Abrindo modal de adicionar animal');
    const modal = document.getElementById('modal-add-animal');
    if (!modal) {
        console.error('Modal modal-add-animal não encontrado');
        alert('Erro: Modal não encontrado. Verifique se o HTML está correto.');
        return;
    }
    
    console.log('Modal encontrado, abrindo...');
    modal.classList.add('show');
    
    const form = document.getElementById('addAnimalForm');
    if (form) {
        form.reset();
        // Resetar data para hoje
        const dateInput = form.querySelector('input[type="date"][name="birth_date"]');
        if (dateInput) {
            dateInput.value = '';
            dateInput.max = getLocalDateString();
        }
        
        // Limpar mensagem de erro/sucesso
        const messageDiv = document.getElementById('add-animal-message');
        if (messageDiv) {
            messageDiv.classList.add('hidden');
            messageDiv.innerHTML = '';
        }
    } else {
        console.warn('Formulário addAnimalForm não encontrado no modal');
    }
};

// Submeter formulário de adicionar animal
async function handleAddAnimalSubmit(e) {
    e.preventDefault();
    
    const form = e.target;
    const submitBtn = form.querySelector('button[type="submit"]');
    const messageDiv = document.getElementById('add-animal-message');
    
    const formData = new FormData(form);
    
    // Validar campos obrigatórios
    if (!formData.get('animal_number')) {
        alert('O número do animal é obrigatório');
        return;
    }
    if (!formData.get('breed')) {
        alert('A raça é obrigatória');
        return;
    }
    if (!formData.get('gender')) {
        alert('O sexo é obrigatório');
        return;
    }
    if (!formData.get('birth_date')) {
        alert('A data de nascimento é obrigatória');
        return;
    }
    if (!formData.get('status')) {
        alert('O status é obrigatório');
        return;
    }
    
    const data = {
        action: 'insert',
        animal_number: formData.get('animal_number'),
        name: formData.get('name') || null,
        breed: formData.get('breed'),
        gender: formData.get('gender'),
        birth_date: formData.get('birth_date'),
        status: formData.get('status'),
        weight: formData.get('weight') ? parseFloat(formData.get('weight')) : null,
        rfid_code: formData.get('rfid_code') || null,
        sire: formData.get('sire') || null,
        dam: formData.get('dam') || null,
        notes: formData.get('notes') || null
    };
    
    // Mostrar loading
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<div class="animate-spin rounded-full h-5 w-5 border-b-2 border-white mx-auto"></div>';
    }
    
    try {
        const response = await fetch('api/animals.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            if (messageDiv) {
                messageDiv.className = 'p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg';
                messageDiv.textContent = result.message || 'Animal cadastrado com sucesso!';
                messageDiv.classList.remove('hidden');
            }
            
            form.reset();
            
            // Recarregar lista de animais após 1.5 segundos
            setTimeout(() => {
                closeSubModal('add-animal');
                // Recarregar página ou atualizar lista
                location.reload();
            }, 1500);
        } else {
            throw new Error(result.error || 'Erro ao cadastrar animal');
        }
    } catch (error) {
        console.error('Erro:', error);
        if (messageDiv) {
            messageDiv.className = 'p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg';
            messageDiv.textContent = error.message || 'Erro ao cadastrar animal';
            messageDiv.classList.remove('hidden');
        }
    } finally {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Cadastrar Animal';
        }
    }
}

// Configurar event listener do formulário quando o modal for aberto
document.addEventListener('DOMContentLoaded', function() {
    const addAnimalForm = document.getElementById('addAnimalForm');
    if (addAnimalForm) {
        addAnimalForm.addEventListener('submit', handleAddAnimalSubmit);
        console.log('Formulário de adicionar animal configurado');
    } else {
        console.warn('Formulário addAnimalForm não encontrado');
    }
});

// Funções para modais de animais
window.showPedigreeModal = function(animalId) {
    const modal = document.getElementById('pedigreeModal');
    const content = document.getElementById('pedigreeContent');
    
    if (!modal) {
        console.error('Modal de pedigree não encontrado');
        return;
    }
    
    // Bloquear scroll do body
    document.body.style.overflow = 'hidden';
    
    // Mostrar loading
    if (content) {
        content.innerHTML = '<div class="flex items-center justify-center min-h-[60vh]"><div class="text-center"><div class="animate-spin rounded-full h-16 w-16 border-4 border-blue-600 border-t-transparent mx-auto mb-4"></div><p class="text-gray-600 text-xl font-semibold">Carregando pedigree...</p></div></div>';
    }
    
    modal.classList.remove('hidden');
    
    // Buscar dados do pedigree do animal
    fetch(`api/animals.php?action=get_pedigree&id=${animalId}`)
        .then(async response => {
            // Verificar se a resposta está OK
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            // Verificar se há conteúdo
            const text = await response.text();
            if (!text || text.trim() === '') {
                throw new Error('Resposta vazia da API');
            }
            
            // Tentar parsear JSON
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Erro ao parsear JSON:', text);
                throw new Error('Resposta inválida da API: ' + text.substring(0, 100));
            }
        })
        .then(data => {
            if (data && data.success !== undefined) {
                if (data.success && data.data) {
                    const pedigree = data.data;
                    displayPedigree(animalId, pedigree);
                } else {
                    if (content) {
                        content.innerHTML = `
                            <div class="text-center py-12">
                                <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <p class="text-gray-600 text-lg mb-2">Nenhum pedigree encontrado</p>
                                <p class="text-gray-500 text-sm">${data.error || 'Os dados de pedigree ainda não foram cadastrados para este animal.'}</p>
                            </div>
                        `;
                    }
                }
            } else {
                throw new Error('Formato de resposta inválido');
            }
        })
        .catch(error => {
            console.error('Erro ao carregar pedigree:', error);
            if (content) {
                content.innerHTML = `
                    <div class="text-center py-12">
                        <svg class="w-16 h-16 text-red-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p class="text-red-600 mb-2 font-semibold">Erro ao carregar pedigree</p>
                        <p class="text-gray-500 text-sm">${error.message || 'Erro desconhecido'}</p>
                    </div>
                `;
            }
        });
};

function displayPedigree(animalId, pedigree) {
    const content = document.getElementById('pedigreeContent');
    const title = document.getElementById('pedigreeTitle');
    if (!content) return;
    
    // Buscar dados do animal para exibir no título
    fetch(`api/animals.php?action=get_by_id&id=${animalId}`)
        .then(res => res.json())
        .then(data => {
            if (data && data.success && data.data) {
                const animal = data.data;
                if (title) {
                    title.textContent = `Pedigree - ${animal.name || animal.animal_number || 'Animal ' + animalId}`;
                }
            }
        })
        .catch(err => console.error('Erro ao buscar dados do animal:', err));
    
    // Se não houver dados de pedigree, mostrar mensagem
    if (!pedigree || pedigree.length === 0) {
        content.innerHTML = `
            <div class="flex items-center justify-center min-h-[60vh]">
                <div class="text-center max-w-2xl px-4">
                    <svg class="w-24 h-24 text-gray-300 mx-auto mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <p class="text-gray-600 text-xl mb-2 font-semibold">Nenhum pedigree encontrado</p>
                    <p class="text-gray-500 mb-6">Os dados de pedigree ainda não foram cadastrados para este animal.</p>
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-left">
                        <p class="text-sm font-semibold text-blue-900 mb-2">📌 Instrução:</p>
                        <p class="text-sm text-blue-800 mb-2">Quando um animal não possui dados de pedigree cadastrados, ele aparecerá com um ícone de aviso <span class="inline-flex items-center"><svg class="w-4 h-4 text-gray-400 mx-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg></span> no canto superior esquerdo do card.</p>
                        <p class="text-sm text-blue-700">Clique em qualquer card do pedigree para ver as informações do animal.</p>
                    </div>
                </div>
            </div>
        `;
        return;
    }
    
    // Organizar pedigree por geração e posição
    const organized = {
        generation1: {}, // Pai e Mãe
        generation2: {}   // Avós
    };
    
    pedigree.forEach(record => {
        if (record.generation === 1) {
            if (record.position === 'pai') {
                organized.generation1.father = record;
            } else if (record.position === 'mae') {
                organized.generation1.mother = record;
            }
        } else if (record.generation === 2) {
            if (record.position === 'avo_paterno') {
                organized.generation2.fatherFather = record; // Avô paterno
            } else if (record.position === 'avo_paterno_mae') {
                organized.generation2.fatherMother = record; // Avó paterna
            } else if (record.position === 'avo_materno') {
                organized.generation2.motherFather = record; // Avô materno
            } else if (record.position === 'avo_materno_mae') {
                organized.generation2.motherMother = record; // Avó materna
            }
        }
    });
    
    // Função para obter o símbolo de gênero (tornada global para ser acessível em todos os escopos)
    window.getGenderSymbol = function(gender) {
        if (!gender) return '';
        
        const genderLower = String(gender).toLowerCase();
        if (genderLower === 'macho' || genderLower === 'm') {
            return '<span class="text-blue-600 font-bold" style="color: #2563eb;">♂</span>';
        } else if (genderLower === 'femea' || genderLower === 'f' || genderLower === 'fêmea') {
            return '<span class="text-pink-600 font-bold" style="color: #db2777;">♀</span>';
        }
        return '';
    };
    
    // Alias local para compatibilidade
    function getGenderSymbol(gender) {
        return window.getGenderSymbol(gender);
    }
    
    // Função para determinar o ícone baseado no sexo/gênero
    function getAnimalIcon(gender, position) {
        // Se tem posição específica, usar ela para determinar
        if (position) {
            // Avô paterno ou avô materno = macho
            if (position === 'avo_paterno' || position === 'avo_materno' || position === 'pai') {
                return 'assets/video/touro.png';
            }
            // Avó paterna, avó materna ou mãe = fêmea
            if (position === 'avo_paterno_mae' || position === 'avo_materno_mae' || position === 'mae') {
                return 'assets/video/vaca.png';
            }
        }
        
        // Se tem gender, usar gender
        if (gender === 'macho') {
            return 'assets/video/touro.png';
        } else if (gender === 'femea') {
            return 'assets/video/vaca.png';
        }
        
        // Default: bezerro
        return 'assets/video/bezzero.png';
    }
    
    // Função para renderizar card de animal
    function renderAnimalCard(record, isMain = false, size = 'normal') {
        if (!record) return '';
        
        const name = record.animal_name || record.animal_number || 'Não informado';
        const breed = record.breed || 'Não informado';
        const icon = getAnimalIcon(record.gender, record.position);
        
        // Tamanhos diferentes baseados no nível
        let iconSize, cardPadding, textSize, nameSize, cardWidth;
        
        if (size === 'small') {
            // Avós - menor (todos iguais nesta geração) - Responsivo para desktop
            iconSize = 'w-10 h-10 sm:w-11 sm:h-11 md:w-12 md:h-12 lg:w-14 lg:h-14 xl:w-16 xl:h-16';
            cardPadding = 'p-1 sm:p-1.5 md:p-2 lg:p-2.5 xl:p-3';
            textSize = 'text-xs sm:text-xs md:text-xs lg:text-sm xl:text-sm';
            nameSize = 'text-xs sm:text-xs md:text-xs lg:text-sm xl:text-base';
            cardWidth = 'w-full'; // Usar largura fixa do wrapper
        } else if (size === 'large') {
            // Pais - maior (ambos iguais nesta geração) - Responsivo para desktop
            iconSize = 'w-12 h-12 sm:w-14 sm:h-14 md:w-16 md:h-16 lg:w-18 lg:h-18 xl:w-20 xl:h-20';
            cardPadding = 'p-1.5 sm:p-2 md:p-2.5 lg:p-3 xl:p-4';
            textSize = 'text-xs sm:text-xs md:text-sm lg:text-sm xl:text-base';
            nameSize = 'text-xs sm:text-sm md:text-base lg:text-base xl:text-lg';
            cardWidth = 'w-full'; // Usar largura fixa do wrapper
        } else {
            // Animal principal - tamanho bom (compacto mas visível) - Responsivo para desktop
            iconSize = 'w-16 h-16 sm:w-18 sm:h-18 md:w-20 md:h-20 lg:w-22 lg:h-22 xl:w-24 xl:h-24';
            cardPadding = 'p-2 sm:p-2.5 md:p-3 lg:p-4 xl:p-5';
            textSize = 'text-xs sm:text-xs md:text-sm lg:text-sm xl:text-base';
            nameSize = 'text-sm sm:text-base md:text-lg lg:text-xl xl:text-2xl';
            cardWidth = 'w-[150px] sm:w-[170px] md:w-[190px] lg:w-[210px] xl:w-[230px]';
        }
        
        const animalId = record.animal_id || record.id || null;
        const hasData = !!(animalId && (record.name || record.animal_number || record.animal_name));
        const cardId = `pedigree-card-${animalId || 'empty-' + Math.random().toString(36).substr(2, 9)}`;
        
        // Garantir que animalId seja string vazia se for null/undefined, não 'null'
        const animalIdAttr = animalId ? String(animalId) : '';
        
        return `
            <div class="bg-white rounded-xl shadow-lg border-2 ${isMain ? 'border-blue-500' : 'border-gray-200'} ${cardPadding} ${cardWidth} hover:shadow-xl transition-all cursor-pointer ${isMain ? 'ring-2 sm:ring-4 ring-blue-200' : ''}" 
                 id="${cardId}"
                 style="${size !== 'normal' ? 'width: 100%; flex: 1;' : ''}; pointer-events: auto;" 
                 data-animal-id="${animalIdAttr}"
                 data-has-data="${hasData}"
                 title="${hasData ? 'Clique para ver informações' : 'Informações não disponíveis'}">
                <div class="flex flex-col items-center space-y-1 sm:space-y-1.5 md:space-y-2">
                    <div class="relative">
                        <img src="${icon}" alt="${name}" class="${iconSize} object-contain rounded-full bg-gray-50 p-1 sm:p-1.5 md:p-2 border-2 ${isMain ? 'border-blue-500' : 'border-gray-300'}">
                        ${isMain ? '<div class="absolute -top-1 -right-1 sm:-top-1.5 sm:-right-1.5 md:-top-2 md:-right-2 w-5 h-5 sm:w-6 sm:h-6 md:w-8 md:h-8 bg-yellow-400 rounded-full flex items-center justify-center shadow-lg"><svg class="w-3 h-3 sm:w-4 sm:h-4 md:w-5 md:h-5 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg></div>' : ''}
                        ${!hasData ? '<div class="absolute -top-1 -left-1 sm:-top-1 sm:-left-1 w-4 h-4 sm:w-5 sm:h-5 bg-gray-400 rounded-full flex items-center justify-center"><svg class="w-2.5 h-2.5 sm:w-3 sm:h-3 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg></div>' : ''}
                    </div>
                    <div class="text-center w-full px-0.5">
                        <p class="font-bold ${hasData ? 'text-gray-900' : 'text-gray-400'} ${nameSize} mb-0.5 truncate" title="${name || 'Sem informações'}">
                            ${name || 'Sem informações'} ${window.getGenderSymbol ? window.getGenderSymbol(record.gender) : getGenderSymbol(record.gender)}
                        </p>
                        <p class="${textSize} ${hasData ? 'text-gray-600' : 'text-gray-400'} mb-0.5 truncate" title="${breed || 'Não informado'}">${breed || 'Não informado'}</p>
                        ${record.animal_number && record.animal_number !== name ? `<p class="${textSize} text-gray-500 truncate" title="${record.animal_number}">${record.animal_number}</p>` : ''}
                    </div>
                </div>
            </div>
        `;
    }
    
    // Buscar dados do animal principal
    fetch(`api/animals.php?action=get_by_id&id=${animalId}`)
        .then(res => res.json())
        .then(data => {
            if (data && data.success && data.data) {
                const mainAnimal = data.data;
                
                // Montar HTML da árvore genealógica
                let html = `
                    <style>
                        .pedigree-tree {
                            display: flex;
                            flex-direction: column;
                            align-items: center;
                            gap: 0;
                            padding: 5px 5px;
                            height: calc(100vh - 80px);
                            max-height: calc(100vh - 80px);
                            position: relative;
                            width: 100%;
                            overflow-x: auto;
                            overflow-y: hidden;
                            -webkit-overflow-scrolling: touch;
                            box-sizing: border-box;
                        }
                        .pedigree-tree::-webkit-scrollbar {
                            height: 8px;
                        }
                        .pedigree-tree::-webkit-scrollbar-track {
                            background: rgba(0,0,0,0.05);
                            border-radius: 4px;
                        }
                        .pedigree-tree::-webkit-scrollbar-thumb {
                            background: rgba(0,0,0,0.2);
                            border-radius: 4px;
                        }
                        .pedigree-tree::-webkit-scrollbar-thumb:hover {
                            background: rgba(0,0,0,0.3);
                        }
                        .pedigree-generation {
                            display: flex;
                            justify-content: center;
                            align-items: flex-start;
                            gap: 10px;
                            position: relative;
                            width: 100%;
                            max-width: 1400px;
                            padding: 5px 5px;
                            flex-wrap: wrap;
                        }
                        .pedigree-generation.generation-grandparents {
                            padding: 5px 5px;
                            gap: 8px;
                            flex-shrink: 0;
                        }
                        .pedigree-generation.generation-parents {
                            padding: 8px 5px;
                            gap: 15px;
                            flex-shrink: 0;
                        }
                        .pedigree-generation.generation-main {
                            padding: 8px 5px;
                            gap: 0;
                            flex-shrink: 0;
                        }
                        .pedigree-pair {
                            display: flex;
                            gap: 10px;
                            position: relative;
                            flex-wrap: nowrap;
                        }
                        .pedigree-pair.grandparents-pair {
                            gap: 8px;
                        }
                        .pedigree-pair.parents-pair {
                            gap: 15px;
                        }
                        .pedigree-card-wrapper {
                            position: relative;
                            z-index: 2;
                            display: flex;
                            align-items: stretch;
                        }
                        .pedigree-card-wrapper > div {
                            transform: scale(1);
                            transition: transform 0.2s;
                            width: 100%;
                            display: flex;
                            flex-direction: column;
                        }
                        /* Tamanhos fixos por geração - todos os cards da mesma fila têm o mesmo tamanho */
                        /* Mobile - Tamanhos menores */
                        @media (max-width: 640px) {
                            .generation-grandparents .pedigree-card-wrapper {
                                width: 90px !important;
                                min-width: 90px !important;
                                max-width: 90px !important;
                            }
                            .generation-parents .pedigree-card-wrapper {
                                width: 120px !important;
                                min-width: 120px !important;
                                max-width: 120px !important;
                            }
                        }
                        
                        /* Tablet - Tamanhos médios */
                        @media (min-width: 641px) and (max-width: 1024px) {
                            .generation-grandparents .pedigree-card-wrapper {
                                width: 110px !important;
                                min-width: 110px !important;
                                max-width: 110px !important;
                            }
                            .generation-parents .pedigree-card-wrapper {
                                width: 150px !important;
                                min-width: 150px !important;
                                max-width: 150px !important;
                            }
                        }
                        
                        /* Desktop Pequeno - Tamanhos maiores que tablet */
                        @media (min-width: 1025px) and (max-width: 1366px) {
                            .generation-grandparents .pedigree-card-wrapper {
                                width: 130px !important;
                                min-width: 130px !important;
                                max-width: 130px !important;
                            }
                            .generation-parents .pedigree-card-wrapper {
                                width: 170px !important;
                                min-width: 170px !important;
                                max-width: 170px !important;
                            }
                        }
                        
                        /* Desktop Médio - Tamanhos maiores */
                        @media (min-width: 1367px) and (max-width: 1920px) {
                            .generation-grandparents .pedigree-card-wrapper {
                                width: 150px !important;
                                min-width: 150px !important;
                                max-width: 150px !important;
                            }
                            .generation-parents .pedigree-card-wrapper {
                                width: 190px !important;
                                min-width: 190px !important;
                                max-width: 190px !important;
                            }
                        }
                        
                        /* Desktop Grande / 4K - Tamanhos máximos */
                        @media (min-width: 1921px) {
                            .generation-grandparents .pedigree-card-wrapper {
                                width: 170px !important;
                                min-width: 170px !important;
                                max-width: 170px !important;
                            }
                            .generation-parents .pedigree-card-wrapper {
                                width: 210px !important;
                                min-width: 210px !important;
                                max-width: 210px !important;
                            }
                        }
                        
                        /* Estilos comuns para todas as gerações */
                        .generation-grandparents .pedigree-card-wrapper {
                            height: auto;
                            flex-shrink: 0;
                        }
                        .generation-grandparents .pedigree-card-wrapper > div {
                            width: 100% !important;
                            min-width: 100% !important;
                            max-width: 100% !important;
                            height: 100%;
                            display: flex;
                            flex-direction: column;
                        }
                        .generation-parents .pedigree-card-wrapper {
                            height: auto;
                            flex-shrink: 0;
                        }
                        .generation-parents .pedigree-card-wrapper > div {
                            width: 100% !important;
                            min-width: 100% !important;
                            max-width: 100% !important;
                            height: 100%;
                            display: flex;
                            flex-direction: column;
                        }
                        .pedigree-connection-layer {
                            position: absolute;
                            top: 0;
                            left: 0;
                            right: 0;
                            bottom: 0;
                            pointer-events: none;
                            z-index: 0;
                        }
                        
                        /* Mobile First - Telas pequenas (mantém layout horizontal) */
                        @media (max-width: 640px) {
                            .pedigree-tree {
                                padding: 5px 3px;
                                height: calc(100vh - 80px);
                                max-height: calc(100vh - 80px);
                                overflow-x: auto;
                                overflow-y: hidden;
                            }
                            .pedigree-generation {
                                flex-direction: row;
                                gap: 5px;
                                padding: 5px 3px;
                                width: 100%;
                                justify-content: center;
                                flex-wrap: nowrap;
                                min-width: fit-content;
                            }
                            .pedigree-generation.generation-grandparents {
                                gap: 4px;
                                padding: 6px 3px;
                            }
                            .pedigree-generation.generation-parents {
                                gap: 8px;
                                padding: 8px 3px;
                            }
                            .pedigree-generation.generation-main {
                                gap: 0;
                                padding: 6px 3px;
                            }
                            .pedigree-pair {
                                flex-direction: row;
                                gap: 4px;
                                width: auto;
                                align-items: flex-start;
                                flex-shrink: 0;
                            }
                            .pedigree-pair.grandparents-pair {
                                gap: 4px;
                            }
                            .pedigree-pair.parents-pair {
                                gap: 8px;
                            }
                            .spacer-line {
                                height: 15px !important;
                                min-height: 15px !important;
                                max-height: 15px !important;
                            }
                        }
                        
                        /* Tablets - Responsividade */
                        @media (min-width: 641px) and (max-width: 1024px) {
                            .pedigree-generation {
                                gap: 20px;
                            }
                            .pedigree-generation.generation-grandparents {
                                gap: 15px;
                            }
                            .pedigree-generation.generation-parents {
                                gap: 25px;
                            }
                            .pedigree-pair.grandparents-pair {
                                gap: 12px;
                            }
                            .pedigree-pair.parents-pair {
                                gap: 25px;
                            }
                        }
                        
                        /* Desktop Pequeno - Responsividade */
                        @media (min-width: 1025px) and (max-width: 1366px) {
                            .pedigree-tree {
                                padding: 10px 10px;
                            }
                            .pedigree-generation {
                                gap: 25px;
                            }
                            .pedigree-generation.generation-grandparents {
                                gap: 20px;
                                padding: 8px 5px;
                            }
                            .pedigree-generation.generation-parents {
                                gap: 30px;
                                padding: 10px 5px;
                            }
                            .pedigree-generation.generation-main {
                                padding: 10px 5px;
                            }
                            .pedigree-pair.grandparents-pair {
                                gap: 15px;
                            }
                            .pedigree-pair.parents-pair {
                                gap: 30px;
                            }
                        }
                        
                        /* Desktop Médio - Responsividade */
                        @media (min-width: 1367px) and (max-width: 1920px) {
                            .pedigree-tree {
                                padding: 15px 15px;
                            }
                            .pedigree-generation {
                                gap: 30px;
                            }
                            .pedigree-generation.generation-grandparents {
                                gap: 25px;
                                padding: 10px 5px;
                            }
                            .pedigree-generation.generation-parents {
                                gap: 35px;
                                padding: 12px 5px;
                            }
                            .pedigree-generation.generation-main {
                                padding: 12px 5px;
                            }
                            .pedigree-pair.grandparents-pair {
                                gap: 20px;
                            }
                            .pedigree-pair.parents-pair {
                                gap: 35px;
                            }
                        }
                        
                        /* Desktop Grande / 4K - Responsividade Máxima */
                        @media (min-width: 1921px) {
                            .pedigree-tree {
                                padding: 20px 20px;
                            }
                            .pedigree-generation {
                                gap: 35px;
                            }
                            .pedigree-generation.generation-grandparents {
                                gap: 30px;
                                padding: 12px 5px;
                            }
                            .pedigree-generation.generation-parents {
                                gap: 40px;
                                padding: 15px 5px;
                            }
                            .pedigree-generation.generation-main {
                                padding: 15px 5px;
                            }
                            .pedigree-pair.grandparents-pair {
                                gap: 25px;
                            }
                            .pedigree-pair.parents-pair {
                                gap: 40px;
                            }
                        }
                    </style>
                    <div class="pedigree-tree">
                `;
                
                // SVG para conexões (usa SVG para linhas precisas)
        html += `
                    <svg class="pedigree-connection-layer" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 0;">
                        <defs>
                            <marker id="arrowhead" markerWidth="10" markerHeight="10" refX="5" refY="5" orient="auto">
                                <polygon points="0 0, 10 5, 0 10" fill="#94a3b8" />
                            </marker>
                        </defs>
                `;
                
                // Geração 3 (Avós - Topo) - MENORES - Todos com mesmo tamanho
                html += '<div class="pedigree-generation generation-grandparents" style="z-index: 2;">';
                if (organized.generation2.fatherFather || organized.generation2.fatherMother) {
                    html += '<div class="pedigree-pair grandparents-pair" id="grandparents-paternal">';
                    // Todos os cards da mesma geração terão o mesmo tamanho via CSS
                    // Wrapper não deve ter pointer-events, apenas o card interno
                    html += '<div class="pedigree-card-wrapper" style="pointer-events: none;">' + renderAnimalCard(organized.generation2.fatherFather, false, 'small') + '</div>';
                    html += '<div class="pedigree-card-wrapper" style="pointer-events: none;">' + renderAnimalCard(organized.generation2.fatherMother, false, 'small') + '</div>';
                    html += '</div>';
                } else {
                    html += '<div class="pedigree-pair grandparents-pair" style="visibility: hidden;"><div class="pedigree-card-wrapper" style="width: 110px;"></div><div class="pedigree-card-wrapper" style="width: 110px;"></div></div>';
                }
                
                if (organized.generation2.motherFather || organized.generation2.motherMother) {
                    html += '<div class="pedigree-pair grandparents-pair" id="grandparents-maternal">';
                    // Todos os cards da mesma geração terão o mesmo tamanho via CSS
                    // Wrapper não deve ter pointer-events, apenas o card interno
                    html += '<div class="pedigree-card-wrapper" style="pointer-events: none;">' + renderAnimalCard(organized.generation2.motherFather, false, 'small') + '</div>';
                    html += '<div class="pedigree-card-wrapper" style="pointer-events: none;">' + renderAnimalCard(organized.generation2.motherMother, false, 'small') + '</div>';
                    html += '</div>';
                } else {
                    html += '<div class="pedigree-pair grandparents-pair" style="visibility: hidden;"><div class="pedigree-card-wrapper" style="width: 110px;"></div><div class="pedigree-card-wrapper" style="width: 110px;"></div></div>';
                }
                html += '</div>';
                
                // Espaçador para conexões verticais (mínimo absoluto)
                html += '<div class="spacer-line" style="height: 15px; position: relative; z-index: 1; min-height: 15px; max-height: 15px; flex-shrink: 0;" id="spacer-gen3-to-gen2"></div>';
                
                // Geração 2 (Pais) - MAIORES - Ambos com mesmo tamanho
                html += '<div class="pedigree-generation generation-parents" style="z-index: 2; flex-shrink: 0;">';
                html += '<div class="pedigree-pair parents-pair" id="parents">';
                // Todos os cards da mesma geração terão o mesmo tamanho via CSS
                // Wrapper não deve ter pointer-events, apenas o card interno
                html += '<div class="pedigree-card-wrapper" style="pointer-events: none;">' + renderAnimalCard(organized.generation1.father, false, 'large') + '</div>';
                html += '<div class="pedigree-card-wrapper" style="pointer-events: none;">' + renderAnimalCard(organized.generation1.mother, false, 'large') + '</div>';
                html += '</div>';
                html += '</div>';
                
                // Espaçador para conexão vertical ao animal principal (mínimo absoluto)
                html += '<div class="spacer-line" style="height: 15px; position: relative; z-index: 1; min-height: 15px; max-height: 15px; flex-shrink: 0;" id="spacer-gen2-to-gen1"></div>';
                
                // Geração 1 (Animal Principal) - Tamanho bom (responsivo para desktop)
                html += '<div class="pedigree-generation generation-main" style="z-index: 2;">';
                const mainIcon = getAnimalIcon(mainAnimal.gender || 'femea', null);
                const mainAnimalId = mainAnimal.id || mainAnimal.animal_id || null;
                const mainHasData = !!(mainAnimalId && (mainAnimal.name || mainAnimal.animal_number));
                html += `
                    <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl shadow-2xl border-2 sm:border-3 md:border-4 lg:border-4 xl:border-4 border-blue-400 p-2 sm:p-2.5 md:p-3 lg:p-4 xl:p-5 w-[150px] sm:w-[170px] md:w-[190px] lg:w-[210px] xl:w-[230px] hover:shadow-3xl transition-all ring-2 sm:ring-3 md:ring-6 lg:ring-6 xl:ring-8 ring-blue-200 cursor-pointer" 
                         id="main-animal-card" 
                         data-animal-id="${mainAnimalId || ''}"
                         data-has-data="${mainHasData}"
                         title="${mainHasData ? 'Clique para ver informações' : 'Informações não disponíveis'}">
                        <div class="flex flex-col items-center space-y-1 sm:space-y-1.5 md:space-y-2 lg:space-y-2.5 xl:space-y-3">
                            <div class="relative">
                                <img src="${mainIcon}" alt="${mainAnimal.name || mainAnimal.animal_number}" class="w-16 h-16 sm:w-18 sm:h-18 md:w-20 md:h-20 lg:w-22 lg:h-22 xl:w-24 xl:h-24 object-contain rounded-full bg-white p-1 sm:p-1.5 md:p-2 lg:p-2.5 xl:p-3 border-2 sm:border-3 md:border-4 lg:border-4 xl:border-4 border-white shadow-lg">
                                ${!mainHasData ? '<div class="absolute -top-1 -left-1 sm:-top-1 sm:-left-1 w-4 h-4 sm:w-5 sm:h-5 bg-gray-400 rounded-full flex items-center justify-center"><svg class="w-2.5 h-2.5 sm:w-3 sm:h-3 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg></div>' : ''}
                                <div class="absolute -top-1 -right-1 sm:-top-1.5 sm:-right-1.5 md:-top-2 md:-right-2 w-4 h-4 sm:w-5 sm:h-5 md:w-7 md:h-7 bg-yellow-400 rounded-full flex items-center justify-center shadow-lg">
                                    <svg class="w-2.5 h-2.5 sm:w-3 sm:h-3 md:w-4 md:h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                </div>
                    </div>
                            <div class="text-center w-full px-0.5">
                                <p class="font-bold text-white text-xs sm:text-sm md:text-base mb-0.5 truncate" title="${mainAnimal.name || mainAnimal.animal_number || 'Animal ' + animalId}">
                                    ${mainAnimal.name || mainAnimal.animal_number || 'Animal ' + animalId} ${window.getGenderSymbol ? window.getGenderSymbol(mainAnimal.gender) : getGenderSymbol(mainAnimal.gender)}
                                </p>
                                <p class="text-blue-100 text-xs mb-0.5 truncate" title="${mainAnimal.breed || 'Não informado'}">${mainAnimal.breed || 'Não informado'}</p>
                                ${mainAnimal.animal_number ? `<p class="text-blue-200 text-xs truncate" title="${mainAnimal.animal_number}">${mainAnimal.animal_number}</p>` : ''}
                    </div>
                </div>
            </div>
        `;
                html += '</div>';
    
    html += '</div>';
                
                // Fechar SVG
                html += '</svg>';
                html += '</div>';
                
    content.innerHTML = html;
                
                // Usar event delegation para evitar problemas com múltiplos listeners
                // Remover listeners antigos se existirem
                const treeContainer = content;
                if (treeContainer) {
                    // Remover listener antigo se existir
                    const oldHandler = treeContainer._pedigreeClickHandler;
                    if (oldHandler) {
                        treeContainer.removeEventListener('click', oldHandler);
                    }
                    
                    // Criar novo handler
                    const clickHandler = function(e) {
                        // Prevenir propagação imediatamente
                        e.stopPropagation();
                        e.preventDefault();
                        
                        // Buscar o card mais próximo que tenha data-animal-id
                        // Começar do elemento clicado e subir na hierarquia
                        let card = null;
                        let currentElement = e.target;
                        const mainAnimalId = mainAnimal.id || mainAnimal.animal_id || null;
                        
                        // Procurar no elemento atual e seus pais até encontrar o card correto
                        // IMPORTANTE: Pegar o PRIMEIRO card encontrado (o mais próximo do clique)
                        // Mas IGNORAR o card principal se encontrar outro primeiro
                        while (currentElement && currentElement !== treeContainer && currentElement !== document.body) {
                            // Verificar se o elemento atual é o card (tem data-animal-id diretamente)
                            if (currentElement.hasAttribute && currentElement.hasAttribute('data-animal-id')) {
                                const animalIdAttr = currentElement.getAttribute('data-animal-id');
                                // Se tem um animalId válido, verificar se é um card válido
                                if (animalIdAttr && animalIdAttr !== '' && animalIdAttr !== 'null' && !isNaN(parseInt(animalIdAttr))) {
                                    const clickedAnimalId = parseInt(animalIdAttr);
                                    
                                    // Se é o card principal E já encontramos um card antes, pular
                                    // Se NÃO é o card principal, usar imediatamente
                                    if (currentElement.id === 'main-animal-card') {
                                        // Só usar o card principal se não encontramos nenhum outro antes
                                        if (!card) {
                                            card = currentElement;
                                            break;
                                        }
                                        // Se já encontramos outro card, pular o principal
                                        continue;
                                    } else {
                                        // Qualquer outro card (avô, pai, etc.) - usar imediatamente
                                        card = currentElement;
                                        break; // Pegar o primeiro encontrado (mais próximo do clique)
                                    }
                                }
                            }
                            currentElement = currentElement.parentElement;
                        }
                        
                        // Se não encontrou um card específico, mas clicou no main-animal-card, usar ele
                        if (!card && e.target) {
                            const mainCard = e.target.closest('#main-animal-card');
                            if (mainCard) {
                                card = mainCard;
                            }
                        }
                        
                        if (!card) {
                            console.log('Nenhum card válido encontrado no clique', {
                                clickedElement: e.target,
                                targetId: e.target.id,
                                targetClass: e.target.className
                            });
                            return;
                        }
                        
                        const animalIdAttr = card.getAttribute('data-animal-id');
                        const animalId = animalIdAttr && animalIdAttr !== '' && animalIdAttr !== 'null' ? parseInt(animalIdAttr) : null;
                        const hasDataAttr = card.getAttribute('data-has-data');
                        const hasData = hasDataAttr === 'true';
                        
                        // Verificar também o ID do card para garantir que estamos pegando o correto
                        const cardName = card.querySelector('p.font-bold, p.text-white') ? card.querySelector('p.font-bold, p.text-white').textContent.trim().replace(/[♂♀]/g, '').trim() : '';
                        
                        console.log('Card clicado no pedigree:', { 
                            animalId: animalId,
                            animalIdType: typeof animalId,
                            animalIdAttr: animalIdAttr,
                            cardId: card.id,
                            cardElement: card.tagName,
                            cardName: cardName,
                            mainAnimalId: mainAnimalId,
                            isMainCard: card.id === 'main-animal-card',
                            clickedElement: e.target.tagName + (e.target.id ? '#' + e.target.id : '') + (e.target.className ? '.' + e.target.className.split(' ')[0] : '')
                        });
                        
                        if (animalId && !isNaN(animalId) && animalId > 0) {
                            // Garantir que estamos passando o animalId correto como número
                            const finalAnimalId = parseInt(animalId);
                            console.log('Chamando showAnimalPedigreeInfo com animalId:', finalAnimalId, 'do card:', cardName);
                            showAnimalPedigreeInfo(finalAnimalId, hasData, e);
                        } else {
                            console.log('animalId inválido, não abrindo modal. animalId:', animalId);
                            showAnimalPedigreeInfo(null, false, e);
                        }
                    };
                    
                    // Adicionar novo listener
                    treeContainer.addEventListener('click', clickHandler);
                    treeContainer._pedigreeClickHandler = clickHandler;
                }
                
                // Adicionar event listeners aos cards após renderizar
                setTimeout(function() {
                    // Desenhar linhas SVG após o DOM ser renderizado
                    drawPedigreeLines();
                }, 150);
                
                // Redesenhar linhas ao redimensionar a janela
                let resizeTimeout;
                window.addEventListener('resize', () => {
                    clearTimeout(resizeTimeout);
                    resizeTimeout = setTimeout(() => {
                        drawPedigreeLines();
                    }, 250);
                });
            }
        })
        .catch(err => {
            console.error('Erro ao buscar dados do animal:', err);
            content.innerHTML = '<div class="text-center py-12"><p class="text-red-600">Erro ao carregar dados do animal</p></div>';
        });
}

// Função para desenhar linhas de conexão precisas
function drawPedigreeLines() {
    const tree = document.querySelector('.pedigree-tree');
    if (!tree) return;
    
    // Remover SVG anterior se existir
    const oldSvg = tree.querySelector('svg.pedigree-connections');
    if (oldSvg) oldSvg.remove();
    
    // Criar novo SVG
    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    svg.setAttribute('class', 'pedigree-connections');
    svg.style.position = 'absolute';
    svg.style.top = '0';
    svg.style.left = '0';
    svg.style.width = '100%';
    svg.style.height = '100%';
    svg.style.pointerEvents = 'none';
    svg.style.zIndex = '1';
    svg.style.overflow = 'visible';
    
    // Ajustar largura da linha baseado no tamanho da tela
    const isMobile = window.innerWidth <= 640;
    const isTablet = window.innerWidth > 640 && window.innerWidth <= 1024;
    const lineColor = '#94a3b8';
    const lineWidth = isMobile ? 2 : (isTablet ? 2.5 : 3);
    
    // Obter posições dos elementos
    const treeRect = tree.getBoundingClientRect();
    
    // Função para obter posição central do card
    function getCardCenter(cardId) {
        const card = document.getElementById(cardId);
        if (!card) return null;
        const rect = card.getBoundingClientRect();
        const treeRect = tree.getBoundingClientRect();
        return {
            x: rect.left + rect.width / 2 - treeRect.left,
            y: rect.top + rect.height / 2 - treeRect.top
        };
    }
    
    // Função para obter posição do par de cards
    function getPairCenter(pairId) {
        const pair = document.getElementById(pairId);
        if (!pair) return null;
        const cards = pair.querySelectorAll('.pedigree-card-wrapper > div');
        if (cards.length < 2) return null;
        
        const rect1 = cards[0].getBoundingClientRect();
        const rect2 = cards[1].getBoundingClientRect();
        const treeRect = tree.getBoundingClientRect();
        
        return {
            x: (rect1.left + rect1.width / 2 + rect2.left + rect2.width / 2) / 2 - treeRect.left,
            y: (rect1.top + rect1.height + rect2.top + rect2.height) / 2 - treeRect.top,
            bottom: Math.max(rect1.bottom, rect2.bottom) - treeRect.top
        };
    }
    
    // Linha 1: Avós paternos -> Pai
    const grandparentsPaternal = getPairCenter('grandparents-paternal');
    const fatherCard = document.querySelector('#parents .pedigree-card-wrapper:first-child');
    if (grandparentsPaternal && fatherCard) {
        const fatherRect = fatherCard.getBoundingClientRect();
        const fatherTop = {
            x: fatherRect.left + fatherRect.width / 2 - treeRect.left,
            y: fatherRect.top - treeRect.top
        };
        
        // Linha vertical dos avós até o pai
        const line1 = document.createElementNS('http://www.w3.org/2000/svg', 'line');
        line1.setAttribute('x1', grandparentsPaternal.x);
        line1.setAttribute('y1', grandparentsPaternal.bottom);
        line1.setAttribute('x2', fatherTop.x);
        line1.setAttribute('y2', fatherTop.y);
        line1.setAttribute('stroke', lineColor);
        line1.setAttribute('stroke-width', lineWidth);
        line1.setAttribute('stroke-linecap', 'round');
        svg.appendChild(line1);
    }
    
    // Linha 2: Avós maternos -> Mãe
    const grandparentsMaternal = getPairCenter('grandparents-maternal');
    const motherCard = document.querySelector('#parents .pedigree-card-wrapper:last-child');
    if (grandparentsMaternal && motherCard) {
        const motherRect = motherCard.getBoundingClientRect();
        const motherTop = {
            x: motherRect.left + motherRect.width / 2 - treeRect.left,
            y: motherRect.top - treeRect.top
        };
        
        // Linha vertical dos avós até a mãe
        const line2 = document.createElementNS('http://www.w3.org/2000/svg', 'line');
        line2.setAttribute('x1', grandparentsMaternal.x);
        line2.setAttribute('y1', grandparentsMaternal.bottom);
        line2.setAttribute('x2', motherTop.x);
        line2.setAttribute('y2', motherTop.y);
        line2.setAttribute('stroke', lineColor);
        line2.setAttribute('stroke-width', lineWidth);
        line2.setAttribute('stroke-linecap', 'round');
        svg.appendChild(line2);
    }
    
    // Linha 3: Pai e Mãe -> Animal Principal
    const parents = getPairCenter('parents');
    const mainAnimal = document.getElementById('main-animal-card');
    if (parents && mainAnimal) {
        const mainRect = mainAnimal.getBoundingClientRect();
        const mainTop = {
            x: mainRect.left + mainRect.width / 2 - treeRect.left,
            y: mainRect.top - treeRect.top
        };
        
        const fatherCard = document.querySelector('#parents .pedigree-card-wrapper:first-child');
        const motherCard = document.querySelector('#parents .pedigree-card-wrapper:last-child');
        
        if (fatherCard && motherCard) {
            const fatherRect = fatherCard.getBoundingClientRect();
            const motherRect = motherCard.getBoundingClientRect();
            const fatherCenter = fatherRect.left + fatherRect.width / 2 - treeRect.left;
            const motherCenter = motherRect.left + motherRect.width / 2 - treeRect.left;
            const fatherBottom = fatherRect.bottom - treeRect.top;
            const motherBottom = motherRect.bottom - treeRect.top;
            
            // Layout horizontal sempre (mobile e desktop)
            // Linha vertical do centro dos pais até o animal principal
            const line3 = document.createElementNS('http://www.w3.org/2000/svg', 'line');
            line3.setAttribute('x1', parents.x);
            line3.setAttribute('y1', parents.bottom);
            line3.setAttribute('x2', mainTop.x);
            line3.setAttribute('y2', mainTop.y);
            line3.setAttribute('stroke', lineColor);
            line3.setAttribute('stroke-width', lineWidth);
            line3.setAttribute('stroke-linecap', 'round');
            svg.appendChild(line3);
            
            // Linha horizontal conectando pai e mãe ao centro
            const yPos = parents.bottom;
            
            const horizontalLine = document.createElementNS('http://www.w3.org/2000/svg', 'line');
            horizontalLine.setAttribute('x1', fatherCenter);
            horizontalLine.setAttribute('y1', yPos);
            horizontalLine.setAttribute('x2', motherCenter);
            horizontalLine.setAttribute('y2', yPos);
            horizontalLine.setAttribute('stroke', lineColor);
            horizontalLine.setAttribute('stroke-width', lineWidth);
            horizontalLine.setAttribute('stroke-linecap', 'round');
            svg.appendChild(horizontalLine);
            
            // Linhas verticais do pai e mãe até a linha horizontal
            const lineFather = document.createElementNS('http://www.w3.org/2000/svg', 'line');
            lineFather.setAttribute('x1', fatherCenter);
            lineFather.setAttribute('y1', fatherBottom);
            lineFather.setAttribute('x2', fatherCenter);
            lineFather.setAttribute('y2', yPos);
            lineFather.setAttribute('stroke', lineColor);
            lineFather.setAttribute('stroke-width', lineWidth);
            lineFather.setAttribute('stroke-linecap', 'round');
            svg.appendChild(lineFather);
            
            const lineMother = document.createElementNS('http://www.w3.org/2000/svg', 'line');
            lineMother.setAttribute('x1', motherCenter);
            lineMother.setAttribute('y1', motherBottom);
            lineMother.setAttribute('x2', motherCenter);
            lineMother.setAttribute('y2', yPos);
            lineMother.setAttribute('stroke', lineColor);
            lineMother.setAttribute('stroke-width', lineWidth);
            lineMother.setAttribute('stroke-linecap', 'round');
            svg.appendChild(lineMother);
        }
    }
    
    // Linhas horizontais para os avós
    const paternalPair = document.getElementById('grandparents-paternal');
    const maternalPair = document.getElementById('grandparents-maternal');
    
    if (paternalPair) {
        const cards = paternalPair.querySelectorAll('.pedigree-card-wrapper > div');
        if (cards.length === 2) {
            const rect1 = cards[0].getBoundingClientRect();
            const rect2 = cards[1].getBoundingClientRect();
            const x1 = rect1.left + rect1.width / 2 - treeRect.left;
            const x2 = rect2.left + rect2.width / 2 - treeRect.left;
            const y = rect1.bottom - treeRect.top;
            
            const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
            line.setAttribute('x1', x1);
            line.setAttribute('y1', y);
            line.setAttribute('x2', x2);
            line.setAttribute('y2', y);
            line.setAttribute('stroke', lineColor);
            line.setAttribute('stroke-width', lineWidth);
            line.setAttribute('stroke-linecap', 'round');
            svg.appendChild(line);
        }
    }
    
    if (maternalPair) {
        const cards = maternalPair.querySelectorAll('.pedigree-card-wrapper > div');
        if (cards.length === 2) {
            const rect1 = cards[0].getBoundingClientRect();
            const rect2 = cards[1].getBoundingClientRect();
            const x1 = rect1.left + rect1.width / 2 - treeRect.left;
            const x2 = rect2.left + rect2.width / 2 - treeRect.left;
            const y = rect1.bottom - treeRect.top;
            
            const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
            line.setAttribute('x1', x1);
            line.setAttribute('y1', y);
            line.setAttribute('x2', x2);
            line.setAttribute('y2', y);
            line.setAttribute('stroke', lineColor);
            line.setAttribute('stroke-width', lineWidth);
            line.setAttribute('stroke-linecap', 'round');
            svg.appendChild(line);
        }
    }
    
    tree.appendChild(svg);
}

window.editAnimalModal = function(animalId) {
    const modal = document.getElementById('editAnimalModal');
    const form = document.getElementById('editAnimalForm');
    
    if (!modal || !form) {
        console.error('Modal de edição não encontrado');
        return;
    }
    
    modal.classList.remove('hidden');
    
    // Buscar dados do animal
    fetch(`api/animals.php?action=get_by_id&id=${animalId}`)
        .then(async response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const text = await response.text();
            if (!text || text.trim() === '') {
                throw new Error('Resposta vazia da API');
            }
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Erro ao parsear JSON:', text);
                throw new Error('Resposta inválida da API');
            }
        })
        .then(data => {
            if (data && data.success && data.data) {
                const animal = data.data;
                populateEditForm(animal);
            } else {
                alert('Erro ao carregar dados do animal: ' + (data?.error || 'Animal não encontrado'));
                closeEditAnimalModal();
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao carregar dados do animal: ' + error.message);
            closeEditAnimalModal();
        });
    
    // Remover listener anterior se existir e adicionar novo
    const newForm = form.cloneNode(true);
    form.parentNode.replaceChild(newForm, form);
    
    // Adicionar listener para submit do formulário
    document.getElementById('editAnimalForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<svg class="w-5 h-5 animate-spin inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg> Salvando...';
        
        const formData = new FormData(this);
        const animalId = this.dataset.animalId;
        
        const updateData = {
            action: 'update',
            id: animalId
        };
        
        // Adicionar campos do formulário
        for (const [key, value] of formData.entries()) {
            updateData[key] = value;
        }
        
        try {
            const response = await fetch('api/animals.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(updateData)
            });
            
            const result = await response.json();
            
            if (result.success) {
                alert('Animal atualizado com sucesso!');
                closeEditAnimalModal();
                // Recarregar a página se necessário
                window.location.reload();
            } else {
                alert('Erro ao atualizar animal: ' + (result.error || 'Erro desconhecido'));
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        } catch (error) {
            console.error('Erro:', error);
            alert('Erro ao atualizar animal. Tente novamente.');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    });
};

function populateEditForm(animal) {
    const form = document.getElementById('editAnimalForm');
    if (!form) return;
    
    // Preencher campos do formulário
    if (form.querySelector('[name="name"]')) form.querySelector('[name="name"]').value = animal.name || '';
    if (form.querySelector('[name="animal_number"]')) form.querySelector('[name="animal_number"]').value = animal.animal_number || '';
    if (form.querySelector('[name="breed"]')) form.querySelector('[name="breed"]').value = animal.breed || '';
    if (form.querySelector('[name="gender"]')) form.querySelector('[name="gender"]').value = animal.gender || '';
    if (form.querySelector('[name="status"]')) form.querySelector('[name="status"]').value = animal.status || '';
    if (form.querySelector('[name="birth_date"]')) form.querySelector('[name="birth_date"]').value = animal.birth_date || '';
    if (form.querySelector('[name="notes"]')) form.querySelector('[name="notes"]').value = animal.notes || '';
    
    // Salvar ID do animal no formulário
    form.dataset.animalId = animal.id;
}

window.viewAnimalModal = function(animalId) {
    const modal = document.getElementById('viewAnimalModal');
    const content = document.getElementById('viewAnimalContent');
    
    if (!modal) {
        console.error('Modal de visualização não encontrado');
        return;
    }
    
    // Mostrar loading
    if (content) {
        content.innerHTML = '<div class="text-center py-12"><div class="animate-spin rounded-full h-12 w-12 border-b-2 border-green-600 mx-auto mb-4"></div><p class="text-gray-600">Carregando dados...</p></div>';
    }
    
    modal.classList.remove('hidden');
    
    // Buscar dados detalhados do animal
    fetch(`api/animals.php?action=get_by_id&id=${animalId}`)
        .then(async response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const text = await response.text();
            if (!text || text.trim() === '') {
                throw new Error('Resposta vazia da API');
            }
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Erro ao parsear JSON:', text);
                throw new Error('Resposta inválida da API: ' + text.substring(0, 100));
            }
        })
        .then(data => {
            if (data && data.success && data.data) {
                const animal = data.data;
                displayAnimalDetails(animal);
            } else {
                if (content) {
                    content.innerHTML = `
                        <div class="text-center py-12">
                            <svg class="w-16 h-16 text-red-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p class="text-red-600 mb-2 font-semibold">Erro ao carregar dados do animal</p>
                            <p class="text-gray-500 text-sm">${data?.error || 'Animal não encontrado'}</p>
                        </div>
                    `;
                }
            }
        })
        .catch(error => {
            console.error('Erro ao carregar animal:', error);
            if (content) {
                content.innerHTML = `
                    <div class="text-center py-12">
                        <svg class="w-16 h-16 text-red-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p class="text-red-600 mb-2 font-semibold">Erro ao carregar dados</p>
                        <p class="text-gray-500 text-sm">${error.message || 'Erro desconhecido'}</p>
                    </div>
                `;
            }
        });
};

function displayAnimalDetails(animal) {
    const content = document.getElementById('viewAnimalContent');
    if (!content) return;
    
    const formatDate = (dateStr) => {
        if (!dateStr) return 'N/A';
        const date = new Date(dateStr);
        return date.toLocaleDateString('pt-BR');
    };
    
    const ageDays = animal.age_days || 0;
    const ageMonths = Math.floor(ageDays / 30);
    const ageYears = Math.floor(ageDays / 365);
    
    content.innerHTML = `
        <div class="space-y-6">
            <!-- Informações Básicas -->
            <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl p-6 border border-blue-200">
                <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                    <svg class="w-5 h-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    Informações Básicas
                </h3>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Nome</p>
                        <p class="font-semibold text-gray-900">${animal.name || 'N/A'}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Número do Animal</p>
                        <p class="font-semibold text-gray-900">${animal.animal_number || 'N/A'}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Raça</p>
                        <p class="font-semibold text-gray-900">${animal.breed || 'N/A'}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Sexo</p>
                        <p class="font-semibold text-gray-900">
                            ${animal.gender === 'femea' ? 'Fêmea <span class="text-pink-600 font-bold" style="color: #db2777;">♀</span>' : (animal.gender === 'macho' ? 'Macho <span class="text-blue-600 font-bold" style="color: #2563eb;">♂</span>' : 'N/A')}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Status</p>
                        <span class="inline-block px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs font-semibold">${animal.status || 'N/A'}</span>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Data de Nascimento</p>
                        <p class="font-semibold text-gray-900">${formatDate(animal.birth_date)}</p>
                    </div>
                </div>
            </div>
            
            <!-- Idade -->
            <div class="bg-white rounded-xl p-6 border border-gray-200">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Idade</h3>
                <div class="grid grid-cols-3 gap-4">
                    <div class="text-center">
                        <p class="text-2xl font-bold text-blue-600">${ageYears}</p>
                        <p class="text-xs text-gray-500">Anos</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-green-600">${ageMonths}</p>
                        <p class="text-xs text-gray-500">Meses</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-orange-600">${ageDays}</p>
                        <p class="text-xs text-gray-500">Dias</p>
                    </div>
                </div>
            </div>
            
            <!-- Pedigree -->
            ${animal.father_name || animal.mother_name ? `
            <div class="bg-white rounded-xl p-6 border border-gray-200">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Pedigree</h3>
                <div class="grid grid-cols-2 gap-4">
                    ${animal.father_name ? `
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Pai</p>
                        <p class="font-semibold text-gray-900">${animal.father_name}</p>
                    </div>
                    ` : ''}
                    ${animal.mother_name ? `
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Mãe</p>
                        <p class="font-semibold text-gray-900">${animal.mother_name}</p>
                    </div>
                    ` : ''}
                </div>
            </div>
            ` : ''}
            
            <!-- Observações -->
            ${animal.notes ? `
            <div class="bg-white rounded-xl p-6 border border-gray-200">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Observações</h3>
                <p class="text-gray-700">${animal.notes}</p>
            </div>
            ` : ''}
        </div>
    `;
}

// Função para fechar modal de pedigree
window.closePedigreeModal = function() {
    const modal = document.getElementById('pedigreeModal');
    if (modal) {
        modal.classList.add('hidden');
        // Restaurar scroll do body
        document.body.style.overflow = '';
    }
};

// Função para exibir informações do animal no pedigree
window.showAnimalPedigreeInfo = function(animalId, hasData, event) {
    // Log para debug
    console.log('showAnimalPedigreeInfo chamado:', {
        animalId: animalId,
        hasData: hasData,
        animalIdType: typeof animalId,
        animalIdValue: animalId
    });
    
    // Prevenir propagação do evento
    if (event) {
        event.stopPropagation();
        event.preventDefault();
    }
    
    // Buscar o modal - pode estar em qualquer lugar do DOM
    let modal = document.getElementById('animalPedigreeInfoModal');
    
    // Se não encontrar, pode estar dentro do pedigreeModal, buscar lá
    if (!modal) {
        const pedigreeModal = document.getElementById('pedigreeModal');
        if (pedigreeModal) {
            modal = pedigreeModal.querySelector('#animalPedigreeInfoModal');
        }
    }
    
    // Se ainda não encontrar, procurar em todo o document
    if (!modal) {
        modal = document.querySelector('#animalPedigreeInfoModal');
    }
    
    // Se ainda não encontrar, tentar criar dinamicamente
    if (!modal) {
        console.log('Modal não encontrado, criando dinamicamente...');
        createAnimalInfoModal();
        modal = document.getElementById('animalPedigreeInfoModal');
        if (!modal) {
            console.error('Erro: Não foi possível criar o modal');
            alert('Erro: Não foi possível exibir as informações do animal. Por favor, recarregue a página.');
            return;
        }
    }
    
    // Buscar elementos - primeiro diretamente pelo ID (mais confiável)
    let content = document.getElementById('animalInfoContent');
    let loading = document.getElementById('animalInfoLoading');
    let title = document.getElementById('animalInfoTitle');
    
    // Se não encontrar diretamente, buscar dentro do modal
    if (!content && modal) {
        content = modal.querySelector('#animalInfoContent');
    }
    if (!loading && modal) {
        loading = modal.querySelector('#animalInfoLoading');
    }
    if (!title && modal) {
        title = modal.querySelector('#animalInfoTitle');
    }
    
    // Se ainda não encontrar, buscar por querySelector global
    if (!content) {
        content = document.querySelector('#animalInfoContent');
    }
    if (!loading) {
        loading = document.querySelector('#animalInfoLoading');
    }
    if (!title) {
        title = document.querySelector('#animalInfoTitle');
    }
    
    // Verificar se encontrou todos os elementos necessários
    if (!content || !loading) {
        console.error('Elementos do modal não encontrados:', {
            content: !!content,
            loading: !!loading,
            title: !!title,
            modal: !!modal,
            modalExists: document.getElementById('animalPedigreeInfoModal') !== null,
            contentExists: document.getElementById('animalInfoContent') !== null,
            loadingExists: document.getElementById('animalInfoLoading') !== null
        });
        
        // Verificar se o modal realmente existe no DOM
        const modalCheck = document.getElementById('animalPedigreeInfoModal');
        if (modalCheck && modalCheck !== modal) {
            console.log('Modal encontrado em local diferente, usando o encontrado...');
            modal = modalCheck;
            content = modal.querySelector('#animalInfoContent');
            loading = modal.querySelector('#animalInfoLoading');
            title = modal.querySelector('#animalInfoTitle');
        }
        
        // Se ainda não encontrou, tentar recriar
        if (!content || !loading) {
            console.log('Tentando recriar modal...');
            if (modal) {
                modal.remove();
            }
            createAnimalInfoModal();
            modal = document.getElementById('animalPedigreeInfoModal');
            if (modal) {
                content = document.getElementById('animalInfoContent');
                loading = document.getElementById('animalInfoLoading');
                title = document.getElementById('animalInfoTitle');
            }
        }
        
        if (!modal || !content || !loading) {
            console.error('Não foi possível criar/encontrar o modal ou seus elementos após todas as tentativas');
            alert('Erro: Não foi possível exibir as informações do animal. Por favor, recarregue a página.');
            return;
        }
        
        console.log('Modal e elementos encontrados após correção:', {
            modal: !!modal,
            content: !!content,
            loading: !!loading,
            title: !!title
        });
    } else {
        console.log('Modal e elementos encontrados com sucesso:', {
            modal: !!modal,
            content: !!content,
            loading: !!loading,
            title: !!title,
            animalId: animalId
        });
    }
    
    // Fechar modal anterior se estiver aberto
    if (!modal.classList.contains('hidden')) {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    }
    
    // Pequeno delay para garantir que fechou antes de abrir novo
    setTimeout(function() {
        // Se não tem ID ou dados, mostrar mensagem de vazio
        if (!animalId || !hasData) {
            loading.style.display = 'none';
            if (content) {
                content.innerHTML = `
                    <div class="flex flex-col items-center justify-center py-12 text-center">
                        <div class="w-20 h-20 bg-gray-200 rounded-full flex items-center justify-center mb-4">
                            <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-700 mb-2">Informações não disponíveis</h3>
                        <p class="text-gray-500">Este animal não possui dados cadastrados no sistema.</p>
                    </div>
                `;
            }
            if (title) title.textContent = 'Informações do Animal';
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            return;
        }
        
        // Mostrar loading
        loading.style.display = 'flex';
        if (content) content.innerHTML = '';
        
        // Abrir modal
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    
        // Garantir que animalId seja um número válido
        const validAnimalId = parseInt(animalId);
        if (!validAnimalId || isNaN(validAnimalId)) {
            console.error('animalId inválido:', animalId);
            if (loading) loading.style.display = 'none';
            if (content) {
                content.innerHTML = `
                    <div class="flex flex-col items-center justify-center py-12 text-center">
                        <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mb-4">
                            <svg class="w-10 h-10 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-700 mb-2">ID inválido</h3>
                        <p class="text-gray-500">O ID do animal não é válido: ${animalId}</p>
                    </div>
                `;
            }
            if (title) title.textContent = 'Erro';
            return;
        }
        
        console.log('Buscando informações do animal com ID:', validAnimalId);
        
        // Buscar dados do animal
        console.log('Fazendo requisição para api/animals.php?action=get_by_id&id=' + validAnimalId);
        fetch(`api/animals.php?action=get_by_id&id=${validAnimalId}`)
            .then(function(res) {
                console.log('Resposta recebida, status:', res.status);
                return res.json();
            })
            .then(function(result) {
                console.log('Resposta da API para animal ID', validAnimalId, ':', {
                    success: result ? result.success : null,
                    hasData: result && result.data ? true : false,
                    animalName: result && result.data ? (result.data.name || result.data.animal_number) : null,
                    animalId: result && result.data ? (result.data.id || result.data.animal_id) : null
                });
                
                if (loading) loading.style.display = 'none';
                
                if (!result || !result.success || !result.data) {
                    if (content) {
                        content.innerHTML = `
                            <div class="flex flex-col items-center justify-center py-12 text-center">
                                <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mb-4">
                                    <svg class="w-10 h-10 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <h3 class="text-xl font-bold text-gray-700 mb-2">Animal não encontrado</h3>
                                <p class="text-gray-500">Não foi possível localizar as informações deste animal.</p>
                            </div>
                        `;
                    }
                    if (title) title.textContent = 'Informações do Animal';
                    return;
                }
                
                const animal = result.data;
            
            // Formatar data de nascimento
            const birthDate = animal.birth_date ? new Date(animal.birth_date + 'T00:00:00').toLocaleDateString('pt-BR') : 'Não informado';
            
            // Calcular idade
            let ageText = 'Não informado';
            if (animal.birth_date && animal.age_days !== undefined) {
                const years = Math.floor(animal.age_days / 365);
                const months = Math.floor((animal.age_days % 365) / 30);
                const days = animal.age_days % 30;
                if (years > 0) {
                    ageText = `${years} ano${years > 1 ? 's' : ''}${months > 0 ? ` e ${months} mês${months > 1 ? 'es' : ''}` : ''}`;
                } else if (months > 0) {
                    ageText = `${months} mês${months > 1 ? 'es' : ''}${days > 0 ? ` e ${days} dia${days > 1 ? 's' : ''}` : ''}`;
                } else if (days > 0) {
                    ageText = `${days} dia${days > 1 ? 's' : ''}`;
                } else {
                    ageText = 'Recém-nascido';
                }
            }
            
            // Determinar ícone baseado no gênero
            let animalIcon = 'assets/video/vaca.png';
            if (animal.gender === 'macho' || animal.gender === 'Macho') {
                animalIcon = 'assets/video/touro.png';
            } else if (animal.age_days && animal.age_days < 365) {
                animalIcon = 'assets/video/bezzero.png';
            }
            
            // Renderizar informações
            content.innerHTML = `
                <div class="space-y-6">
                    <!-- Header com foto e nome -->
                    <div class="flex items-center space-x-4 pb-4 border-b border-gray-200">
                        <div class="relative">
                            <img src="${animalIcon}" alt="${animal.name || animal.animal_number || 'Animal'}" class="w-20 h-20 object-contain rounded-full bg-gray-100 p-2 border-2 border-blue-500">
                        </div>
                        <div class="flex-1">
                            <h4 class="text-2xl font-bold text-gray-900">
                                ${animal.name || animal.animal_number || 'Animal não nomeado'} ${window.getGenderSymbol ? window.getGenderSymbol(animal.gender) : (animal.gender === 'macho' || animal.gender === 'Macho' ? '<span class="text-blue-600 font-bold" style="color: #2563eb;">♂</span>' : (animal.gender === 'femea' || animal.gender === 'Fêmea' ? '<span class="text-pink-600 font-bold" style="color: #db2777;">♀</span>' : ''))}
                            </h4>
                            ${animal.animal_number && animal.animal_number !== animal.name ? `<p class="text-sm text-gray-600">Número: ${animal.animal_number}</p>` : ''}
                        </div>
                    </div>
                    
                    <!-- Informações Básicas -->
                    <div class="space-y-4">
                        <h5 class="text-lg font-semibold text-gray-800 border-b border-gray-200 pb-2">Informações Básicas</h5>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Raça</p>
                                <p class="font-medium text-gray-900">${animal.breed || 'Não informado'}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Gênero</p>
                                <p class="font-medium text-gray-900">
                                    ${animal.gender === 'macho' || animal.gender === 'Macho' ? 'Macho <span class="text-blue-600 font-bold" style="color: #2563eb;">♂</span>' : (animal.gender === 'femea' || animal.gender === 'Fêmea' ? 'Fêmea <span class="text-pink-600 font-bold" style="color: #db2777;">♀</span>' : 'Não informado')}
                                </p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Data de Nascimento</p>
                                <p class="font-medium text-gray-900">${birthDate}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Idade</p>
                                <p class="font-medium text-gray-900">${ageText}</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Status e Estado -->
                    <div class="space-y-4">
                        <h5 class="text-lg font-semibold text-gray-800 border-b border-gray-200 pb-2">Status</h5>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Status</p>
                                <span class="inline-flex px-3 py-1 rounded-full text-sm font-medium ${animal.status === 'Ativo' ? 'bg-green-100 text-green-800' : animal.status === 'Inativo' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800'}">${animal.status || 'Não informado'}</span>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Ativo</p>
                                <span class="inline-flex px-3 py-1 rounded-full text-sm font-medium ${animal.is_active == 1 ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}">${animal.is_active == 1 ? 'Sim' : 'Não'}</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Genealogia -->
                    ${(animal.father_name || animal.mother_name) ? `
                    <div class="space-y-4">
                        <h5 class="text-lg font-semibold text-gray-800 border-b border-gray-200 pb-2">Genealogia</h5>
                        <div class="space-y-2">
                            ${animal.father_name ? `
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Pai</p>
                                <p class="font-medium text-gray-900">${animal.father_name}</p>
                            </div>
                            ` : ''}
                            ${animal.mother_name ? `
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Mãe</p>
                                <p class="font-medium text-gray-900">${animal.mother_name}</p>
                            </div>
                            ` : ''}
                        </div>
                    </div>
                    ` : ''}
                    
                    <!-- Observações -->
                    ${animal.observations ? `
                    <div class="space-y-4">
                        <h5 class="text-lg font-semibold text-gray-800 border-b border-gray-200 pb-2">Observações</h5>
                        <p class="text-gray-700 whitespace-pre-wrap">${animal.observations}</p>
                    </div>
                    ` : ''}
                </div>
            `;
            
            if (title) {
                title.textContent = `Informações - ${animal.name || animal.animal_number || 'Animal'}`;
            }
        })
            .catch(function(error) {
                console.error('Erro ao buscar informações do animal:', error);
                if (loading) loading.style.display = 'none';
                if (content) {
                    content.innerHTML = `
                        <div class="flex flex-col items-center justify-center py-12 text-center">
                            <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mb-4">
                                <svg class="w-10 h-10 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-gray-700 mb-2">Erro ao carregar dados</h3>
                            <p class="text-gray-500">Ocorreu um erro ao buscar as informações do animal.</p>
                        </div>
                    `;
                }
                if (title) title.textContent = 'Erro';
            });
    }, 50); // Fechar o setTimeout
};

// Função auxiliar para criar o modal dinamicamente se não existir
function createAnimalInfoModal() {
    let existingModal = document.getElementById('animalPedigreeInfoModal');
    if (existingModal) {
        return; // Já existe
    }
    
    console.log('Criando modal de informações do animal dinamicamente...');
    
    const modalHTML = `
        <div id="animalPedigreeInfoModal" class="fixed inset-0 z-[120] hidden flex items-center justify-center p-4" style="z-index: 9999 !important;">
            <div class="absolute inset-0 bg-black bg-opacity-50 backdrop-blur-sm" onclick="window.closeAnimalPedigreeInfoModal()"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-hidden flex flex-col">
                <div class="flex items-center justify-between p-6 border-b border-gray-200 bg-gradient-to-r from-blue-500 to-blue-600">
                    <div class="flex items-center space-x-3">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <h3 class="text-xl font-bold text-white" id="animalInfoTitle">Informações do Animal</h3>
                    </div>
                    <button onclick="window.closeAnimalPedigreeInfoModal()" class="text-white/80 hover:text-white hover:bg-white/20 rounded-lg p-2 transition-all">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div id="animalInfoContent" class="flex-1 overflow-y-auto p-6">
                    <div id="animalInfoLoading" class="flex items-center justify-center py-8">
                        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    
    console.log('Modal criado com sucesso!');
    
    // Verificar se foi criado corretamente
    const createdModal = document.getElementById('animalPedigreeInfoModal');
    if (!createdModal) {
        console.error('Erro: Modal não foi criado corretamente');
    } else {
        console.log('Modal verificado e existe no DOM');
    }
}

// Função para fechar modal de informações do animal
window.closeAnimalPedigreeInfoModal = function() {
    // Buscar modal em diferentes locais
    let modal = document.getElementById('animalPedigreeInfoModal');
    
    if (!modal) {
        const pedigreeModal = document.getElementById('pedigreeModal');
        if (pedigreeModal) {
            modal = pedigreeModal.querySelector('#animalPedigreeInfoModal');
        }
    }
    
    if (!modal) {
        modal = document.querySelector('#animalPedigreeInfoModal');
    }
    
    if (modal) {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
        
        // Limpar conteúdo para evitar problemas ao reabrir
        const content = modal.querySelector('#animalInfoContent');
        const loading = modal.querySelector('#animalInfoLoading');
        if (content) content.innerHTML = '';
        if (loading) loading.style.display = 'flex';
    }
};

// Função para fechar modal de edição
window.closeEditAnimalModal = function() {
    const modal = document.getElementById('editAnimalModal');
    if (modal) {
        modal.classList.add('hidden');
        const form = document.getElementById('editAnimalForm');
        if (form) {
            form.reset();
        }
    }
};

// Função para fechar modal de visualização
window.closeViewAnimalModal = function() {
    const modal = document.getElementById('viewAnimalModal');
    if (modal) {
        modal.classList.add('hidden');
    }
};

// Sistema de busca e filtros para Gestão de Rebanho
window.animalFiltersInitialized = false;

function initAnimalSearchAndFilters() {
    const searchInput = document.getElementById('searchAnimal');
    const filterStatus = document.getElementById('filterStatus');
    const filterBreed = document.getElementById('filterBreed');
    
    if (!searchInput || !filterStatus || !filterBreed) {
        console.log('⚠️ Elementos de busca/filtro não encontrados');
        return false;
    }
    
    // Se já foi inicializado, não reinicializar (evitar duplicação)
    if (window.animalFiltersInitialized) {
        console.log('✅ Filtros já inicializados');
        return true;
    }
    
    console.log('🔍 Inicializando busca e filtros de animais...');
    
    function filterAnimals() {
        const searchTerm = (searchInput.value || '').toLowerCase().trim();
        const statusFilter = filterStatus.value || '';
        const breedFilter = (filterBreed.value || '').toLowerCase();
        
        console.log('🔎 Filtrando:', { searchTerm, statusFilter, breedFilter });
        
        // Filtrar cards
        const cards = document.querySelectorAll('#animalsListContainer .animal-card');
        let visibleCards = 0;
        cards.forEach(card => {
            const name = (card.dataset.name || '').toLowerCase();
            const number = (card.dataset.number || '').toLowerCase();
            const status = card.dataset.status || '';
            const breed = (card.dataset.breed || '').toLowerCase();
            
            const matchesSearch = !searchTerm || name.includes(searchTerm) || number.includes(searchTerm);
            const matchesStatus = !statusFilter || status === statusFilter;
            const matchesBreed = !breedFilter || breed.includes(breedFilter);
            
            if (matchesSearch && matchesStatus && matchesBreed) {
                card.style.display = '';
                visibleCards++;
            } else {
                card.style.display = 'none';
            }
        });
        
        // Filtrar linhas da tabela
        const rows = document.querySelectorAll('.animal-table-row');
        let visibleRows = 0;
        rows.forEach(row => {
            const name = (row.dataset.name || '').toLowerCase();
            const number = (row.dataset.number || '').toLowerCase();
            const status = row.dataset.status || '';
            const breed = (row.dataset.breed || '').toLowerCase();
            
            const matchesSearch = !searchTerm || name.includes(searchTerm) || number.includes(searchTerm);
            const matchesStatus = !statusFilter || status === statusFilter;
            const matchesBreed = !breedFilter || breed.includes(breedFilter);
            
            if (matchesSearch && matchesStatus && matchesBreed) {
                row.style.display = '';
                visibleRows++;
            } else {
                row.style.display = 'none';
            }
        });
        
        console.log(`✅ Filtro aplicado: ${visibleCards} cards e ${visibleRows} linhas visíveis`);
    }
    
    // Adicionar listeners usando event delegation
    searchInput.addEventListener('input', filterAnimals);
    filterStatus.addEventListener('change', filterAnimals);
    filterBreed.addEventListener('change', filterAnimals);
    
    window.animalFiltersInitialized = true;
    console.log('✅ Busca e filtros inicializados com sucesso!');
    return true;
}

// Disponibilizar função globalmente
window.initAnimalSearchAndFilters = initAnimalSearchAndFilters;

// Função para verificar e inicializar quando o modal abrir
function checkAndInitAnimalFilters() {
    const animalsModal = document.getElementById('modal-animals');
    if (animalsModal && animalsModal.classList.contains('show')) {
        setTimeout(() => {
            initAnimalSearchAndFilters();
        }, 300);
    }
}

// Inicializar busca e filtros quando o modal de animais for aberto
document.addEventListener('DOMContentLoaded', function() {
    // Tentar inicializar imediatamente
    setTimeout(initAnimalSearchAndFilters, 1000);
    
    // Observar quando o submodal de animais for aberto
    const animalsSubModal = document.getElementById('modal-animals');
    if (animalsSubModal) {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    const isVisible = animalsSubModal.classList.contains('show');
                    if (isVisible) {
                        window.animalFiltersInitialized = false; // Reset para reinicializar
                        setTimeout(initAnimalSearchAndFilters, 300);
                    }
                }
            });
        });
        
        observer.observe(animalsSubModal, {
            attributes: true,
            attributeFilter: ['class']
        });
    }
    
    // Também tentar quando clicar no botão que abre o modal
    document.addEventListener('click', function(e) {
        if (e.target && (e.target.onclick && e.target.onclick.toString().includes('animals') || 
            e.target.closest('[onclick*="animals"]'))) {
            setTimeout(() => {
                window.animalFiltersInitialized = false;
                checkAndInitAnimalFilters();
            }, 500);
        }
    });
});
window.closeQualityOverlay = closeQualityOverlay;
window.closeSalesOverlay = closeSalesOverlay;

// Volume: exportar CSV
async function exportVolumeReport() {
    try {
        const res = await fetch('./api/volume.php?action=get_all');
        const json = await res.json();
        const rows = Array.isArray(json?.data) ? json.data : [];
        if (rows.length === 0) {
            console.warn('Sem registros de volume para exportar');
            return;
        }
        const header = ['Data','Período','Volume','Animais','Média'];
        const csvRows = [header.join(',')].concat(rows.map(r => {
            const cols = [r.record_date, r.shift, Number(r.total_volume)||0, Number(r.total_animals)||0, Number(r.average_per_animal)||0];
            return cols.join(',');
        }));
        const blob = new Blob(["\uFEFF" + csvRows.join('\n')], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `volume_${new Date().toISOString().slice(0,10)}.csv`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    } catch (e) {
        console.error('Erro ao exportar volume:', e);
    }
}
window.exportVolumeReport = exportVolumeReport;

async function populateVolumeAnimalSelect() {
    const select = document.getElementById('volumeAnimalSelect');
    if (!select) return;
    
    // Mostrar loading
    select.innerHTML = '<option value="">Carregando vacas...</option>';
    select.disabled = true;
    
    try {
        const res = await fetch('./api/animals.php?action=select');
        
        if (!res.ok) {
            throw new Error(`HTTP error! status: ${res.status}`);
        }
        
        const json = await res.json();
        
        // Verificar se há erro na resposta
        if (!json.success) {
            throw new Error(json.error || 'Erro ao buscar vacas');
        }
        
        // O método query() retorna um array diretamente, mas a API pode retornar em json.data
        const animals = Array.isArray(json?.data) ? json.data : (Array.isArray(json) ? json : []);
        
        // Filtrar apenas fêmeas ativas (vacas)
        // is_active pode vir como número ou string
        const lactatingFemales = animals.filter(a => {
            const isFemale = a.gender === 'femea';
            const isActive = a.is_active == 1 || a.is_active === 1 || a.is_active === '1';
            return isFemale && isActive;
        });
        
        if (lactatingFemales.length === 0) {
            select.innerHTML = '<option value="">Nenhuma vaca encontrada</option>';
            select.disabled = false;
            return;
        }
        
        // Ordenar por número do animal
        lactatingFemales.sort((a, b) => {
            const numA = a.animal_number || '';
            const numB = b.animal_number || '';
            return numA.localeCompare(numB, undefined, { numeric: true, sensitivity: 'base' });
        });
        
        select.innerHTML = ['<option value="">Selecione uma vaca</option>']
            .concat(lactatingFemales.map(a => {
                const number = a.animal_number || '';
                const name = a.name ? ` - ${a.name}` : '';
                return `<option value="${a.id}">${number}${name}</option>`;
            }))
            .join('');
        
        select.disabled = false;
    } catch (e) {
        console.error('Erro ao carregar vacas:', e);
        select.innerHTML = '<option value="">Erro ao carregar vacas</option>';
        select.disabled = false;
    }
}

// ==================== NOTIFICAÇÕES (Drawer Lateral) ====================
window.openNotificationsDrawer = async function openNotificationsDrawer() {
    const drawer = document.getElementById('notificationsDrawer');
    const panel = document.getElementById('notificationsPanel');
    if (!drawer || !panel) return;
    drawer.classList.remove('hidden');
    requestAnimationFrame(() => panel.classList.remove('translate-x-full'));
    try { await loadNotifications(); } catch(e) { console.error('Falha ao carregar notificações', e); }
};

window.closeNotificationsDrawer = function closeNotificationsDrawer() {
    const drawer = document.getElementById('notificationsDrawer');
    const panel = document.getElementById('notificationsPanel');
    if (!drawer || !panel) return;
    panel.classList.add('translate-x-full');
    const onEnd = () => { drawer.classList.add('hidden'); panel.removeEventListener('transitionend', onEnd); };
    panel.addEventListener('transitionend', onEnd);
};

async function loadNotifications() {
    const list = document.getElementById('notificationsList');
    const countEl = document.getElementById('notificationsCount');
    const bellCountEl = document.getElementById('notificationsBellCount');
    if (!list) return;
    list.innerHTML = '<div class="text-sm text-gray-500">Carregando...</div>';
    
    // Usar a nova API de notificações
    const res = await fetch('./api/notifications-api.php?action=get');
    if (!res.ok) { list.innerHTML = '<div class="text-sm text-red-500">Erro ao carregar</div>'; return; }
    const data = await res.json().catch(() => ({}));
    const items = (data.notifications || data.items || data.data || []);
    
    // Usar is_read do banco de dados
    const unreadCount = Array.isArray(items) ? items.filter(i => (i.is_read === 0 || i.is_read === false || i.read === 0 || i.read === false)).length : (data.unread_count || 0);
    
    // Atualizar contador no cabeçalho do drawer
    if (countEl) {
        countEl.textContent = unreadCount > 0 ? String(unreadCount) : '0';
    }
    
    // Atualizar badge do sino - só mostrar se houver notificações não lidas
    if (bellCountEl) {
        if (unreadCount > 0) {
            bellCountEl.textContent = String(unreadCount);
            bellCountEl.classList.remove('hidden');
        } else {
            bellCountEl.textContent = '';
            bellCountEl.classList.add('hidden');
        }
    }
    if (!Array.isArray(items) || items.length === 0) {
        list.innerHTML = '<div class="text-center py-10 text-gray-500 text-sm">\n            <div class="w-10 h-10 mx-auto mb-3 rounded-full bg-gray-100 flex items-center justify-center">\n                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">\n                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M12 18.5a6.5 6.5 0 100-13 6.5 6.5 0 000 13z"/>\n                </svg>\n            </div>\n            Sem notificações.\n        </div>';
        return;
    }
    list.innerHTML = items.map(renderNotificationItem).join('');
}

function renderNotificationItem(item) {
    const title = (item.title || item.tipo || 'Notificação');
    const message = (item.message || item.mensagem || item.descricao || '');
    const dateStr = (item.created_at || item.data || item.date || '');
    // Usar is_read do banco de dados
    const unread = (item.is_read === 0 || item.is_read === false || item.read === 0 || item.lida === 0 || item.unread === true);
    const type = (item.type || item.tipo || '').toString().toLowerCase();
    const color = type.includes('alert') || type.includes('erro') || type.includes('crit') ? 'red' :
                  type.includes('warn') || type.includes('aten') ? 'amber' :
                  type.includes('ok') || type.includes('sucesso') ? 'green' : 'green';
    const colorMap = {
        red:   { bg: 'bg-red-50',   dot: 'bg-red-500',   icon: 'text-red-600',   ring: 'ring-red-100' },
        amber: { bg: 'bg-amber-50', dot: 'bg-amber-500', icon: 'text-amber-600', ring: 'ring-amber-100' },
        green: { bg: 'bg-green-50', dot: 'bg-green-500', icon: 'text-green-600', ring: 'ring-green-100' },
        blue:  { bg: 'bg-blue-50',  dot: 'bg-blue-500',  icon: 'text-blue-600',  ring: 'ring-blue-100' }
    }[color];
    return (
        '<div class="rounded-xl border p-3 ' + (unread ? colorMap.bg + ' ' + colorMap.ring + ' ring-1' : 'bg-white') + '">' +
            '<div class="flex items-start gap-3">' +
                '<div class="mt-0.5 w-2 h-2 rounded-full ' + (unread ? colorMap.dot : 'bg-gray-300') + '"></div>' +
                '<div class="flex-1">' +
                    '<div class="flex items-center gap-2">' +
                        '<svg class="w-4 h-4 ' + colorMap.icon + '" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">' +
                            '<path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M12 18.5a6.5 6.5 0 100-13 6.5 6.5 0 000 13z"/>' +
                        '</svg>' +
                        '<div class="text-sm font-semibold text-gray-900">' + escapeHtml(title) + '</div>' +
                    '</div>' +
                    (message ? '<div class="text-sm text-gray-700 mt-1">' + escapeHtml(message) + '</div>' : '') +
                    (dateStr ? '<div class="text-xs text-gray-400 mt-1">' + escapeHtml(dateStr) + '</div>' : '') +
                '</div>' +
            '</div>' +
        '</div>'
    );
}

window.markAllNotificationsRead = async function markAllNotificationsRead() {
    try {
        await fetch('./api/notifications-api.php?action=mark_all_read', { method: 'POST' });
        await loadNotifications();
    } catch (e) {
        console.error('Erro ao marcar como lidas', e);
    }
};

// Função para verificar e mostrar notificações push
function checkAndShowPushNotifications(notifications) {
    // Verificar se a API de Notificações está disponível
    if (!('Notification' in window)) {
        console.log('Notificações não suportadas neste navegador');
        return;
    }
    
    // Verificar permissão
    if (Notification.permission === 'granted') {
        // Buscar notificações não lidas e recentes (últimos 5 minutos)
        const recentUnread = notifications.filter(n => {
            const isUnread = (n.is_read === 0 || n.is_read === false);
            if (!isUnread) return false;
            
            const createdAt = new Date(n.created_at);
            const now = new Date();
            const diffMinutes = (now - createdAt) / (1000 * 60);
            
            // Mostrar apenas notificações dos últimos 5 minutos
            return diffMinutes <= 5;
        });
        
        // Mostrar push para notificações recentes
        recentUnread.forEach(notification => {
            showPushNotification(notification);
        });
    } else if (Notification.permission === 'default') {
        // Solicitar permissão se ainda não foi solicitada
        Notification.requestPermission().then(permission => {
            if (permission === 'granted') {
                checkAndShowPushNotifications(notifications);
            }
        });
    }
}

// Função para mostrar notificação push
function showPushNotification(notification) {
    const title = notification.title || 'Nova Notificação';
    const message = notification.message || '';
    
    const notificationObj = new Notification(title, {
        body: message,
        icon: '/assets/img/lactech-logo.png',
        badge: '/assets/img/lactech-logo.png',
        tag: 'lactech-notification-' + notification.id,
        requireInteraction: false,
        data: {
            notificationId: notification.id,
            link: notification.link || null
        }
    });
    
    // Ao clicar na notificação, abrir o link ou focar na janela
    notificationObj.onclick = function(event) {
        event.preventDefault();
        window.focus();
        
        if (notification.link) {
            window.location.href = notification.link;
        } else {
            // Abrir drawer de notificações
            if (typeof openNotificationsDrawer === 'function') {
                openNotificationsDrawer();
            }
        }
        
        // Marcar como lida
        if (notification.id) {
            fetch(`./api/notifications-api.php?action=mark_read`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: notification.id })
            });
        }
        
        notificationObj.close();
    };
    
    // Fechar automaticamente após 5 segundos
    setTimeout(() => {
        notificationObj.close();
    }, 5000);
}

// Função para alterar senha com OTP
async function changePasswordWithOTP(newPassword, confirmPassword) {
    try {
        // 1. Gerar OTP
        const otpResponse = await fetch('./api/security.php?action=generate_otp', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ action_type: 'password_change' })
        });
        
        const otpResult = await otpResponse.json();
        
        if (!otpResult.success) {
            showErrorModal(otpResult.error || 'Erro ao gerar código OTP');
            return;
        }
        
        // 2. Solicitar código OTP do usuário via modal
        openOtpPasswordModal('Alterar Senha', 'Digite o código OTP enviado para seu e-mail Google vinculado:', false, async function(otpCode) {
            // 3. Alterar senha com OTP
            const changePasswordResponse = await fetch('./api/security.php?action=change_password', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    new_password: newPassword,
                    confirm_password: confirmPassword,
                    otp_code: otpCode
                })
            });
            
            const changePasswordResult = await changePasswordResponse.json();
            
            if (changePasswordResult.success) {
                showSuccessModal('Senha alterada com sucesso! Todas as sessões foram encerradas por segurança.');
                // Limpar campos
                document.getElementById('profileNewPassword').value = '';
                document.getElementById('profileConfirmPassword').value = '';
                
                if (typeof loadAccountActions === 'function') {
                    loadAccountActions();
                }
            } else {
                showErrorModal(changePasswordResult.error || 'Erro ao alterar senha');
            }
        });
    } catch (e) {
        console.error('Erro ao alterar senha:', e);
        showErrorModal('Erro ao alterar senha. Tente novamente.');
    }
}

function escapeHtml(s){
    if (s == null) return '';
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
}

// ==================== PERFIL DO USUÁRIO ====================
let profileOriginalValues = {};
let profileEditMode = false;
let profilePhotoFile = null;

// Funções de segurança do perfil - ATIVADO
window.loadSecurityStatus = async function loadSecurityStatus() {
    try {
        // Carregar status do Google
        const response = await fetch('./api/google-auth.php?action=get_status');
        const result = await response.json();
        
        if (result.success) {
            const isLinked = result.linked || false;
            const googleData = result.data || null;
            window.googleAccountUserEmail = result.user_email || null;
            
            // Atualizar UI do Google
            updateGoogleUI(isLinked, googleData);
            
            // Sempre habilitar campos de senha (Google não é mais obrigatório)
            togglePasswordFields(isLinked);
        } else {
            console.error('Erro ao carregar status do Google:', result.error);
            // Em caso de erro, mostrar como não vinculado
            updateGoogleUI(false, null);
            togglePasswordFields(false);
        }
    } catch (error) {
        console.error('Erro ao carregar status de segurança:', error);
        // Em caso de erro, mostrar como não vinculado
        updateGoogleUI(false, null);
        togglePasswordFields(false);
    }
};

function updateGoogleUI(isLinked, googleData) {
    const statusEl = document.getElementById('googleAccountStatus');
    const linkBtn = document.getElementById('linkGoogleBtn');
    const unlinkBtn = document.getElementById('unlinkGoogleBtn');
    const googleEmailEl = document.getElementById('googleAccountEmail');
    const googleSettingsBtn = document.getElementById('googleAccountSettingsBtn');
    const googleNotLinkedText = document.getElementById('googleAccountNotLinkedText');
    
    // Armazenar dados globalmente para uso no modal
    window.googleAccountData = googleData || {};
    
    if (statusEl) {
        if (isLinked && googleData) {
            statusEl.textContent = 'Vinculada';
            statusEl.className = 'text-xs px-2 py-1 rounded-lg bg-green-100 text-green-700';
            if (linkBtn) linkBtn.classList.add('hidden');
            if (unlinkBtn) unlinkBtn.classList.remove('hidden');
            if (googleSettingsBtn) googleSettingsBtn.classList.remove('hidden');
            if (googleNotLinkedText) googleNotLinkedText.classList.add('hidden');
            
            // Mostrar email vinculado
            if (googleEmailEl) {
                const emailSpan = googleEmailEl.querySelector('span');
                if (emailSpan) {
                    // Verificar se tem email, caso contrário mostrar mensagem
                    const email = googleData.email || null;
                    const userEmail = window.googleAccountUserEmail || null;
                    
                    if (email && email.trim() !== '' && email !== 'null' && email !== 'undefined') {
                        emailSpan.textContent = email;
                        emailSpan.classList.remove('text-red-600', 'text-yellow-600');
                        emailSpan.classList.add('text-green-600');
                    } else if (userEmail && userEmail.trim() !== '') {
                        // Usar email do sistema como fallback
                        emailSpan.textContent = userEmail + ' (email do sistema)';
                        emailSpan.classList.remove('text-red-600', 'text-green-600');
                        emailSpan.classList.add('text-yellow-600');
                    } else {
                        emailSpan.textContent = 'Email não disponível - Verifique sua conta Google';
                        emailSpan.classList.remove('text-green-600', 'text-yellow-600');
                        emailSpan.classList.add('text-red-600');
                    }
                }
                googleEmailEl.classList.remove('hidden');
            }
        } else {
            statusEl.textContent = 'Não vinculada';
            statusEl.className = 'text-xs px-2 py-1 rounded-lg bg-gray-200 text-gray-700';
            if (linkBtn) linkBtn.classList.remove('hidden');
            if (unlinkBtn) unlinkBtn.classList.add('hidden');
            if (googleSettingsBtn) googleSettingsBtn.classList.add('hidden');
            if (googleEmailEl) googleEmailEl.classList.add('hidden');
            if (googleNotLinkedText) googleNotLinkedText.classList.remove('hidden');
        }
    }
}

// Abrir modal de configurações da conta Google
window.openGoogleAccountSettings = function openGoogleAccountSettings() {
    openGoogleSettingsModal();
};

// Abrir e preencher modal de configurações da conta Google
async function openGoogleSettingsModal() {
    const modal = document.getElementById('googleSettingsModal');
    if (!modal) {
        console.error('Modal de configurações Google não encontrado');
        return;
    }
    
    // Mostrar modal
    modal.classList.remove('hidden');
    
    // Carregar dados do Google
    try {
        const response = await fetch('./api/google-auth.php?action=get_status');
        const result = await response.json();
        
        if (result.success && result.linked && result.data) {
            const googleData = result.data;
            
            // Preencher email
            const emailEl = document.getElementById('googleSettingsEmail');
            if (emailEl) {
                emailEl.textContent = googleData.email || 'N/A';
            }
            
            // Preencher nome
            const nameEl = document.getElementById('googleSettingsName');
            if (nameEl) {
                nameEl.textContent = googleData.name || 'N/A';
            }
            
            // Preencher data de vinculação
            const linkedAtEl = document.getElementById('googleSettingsLinkedAt');
            if (linkedAtEl && googleData.linked_at) {
                const linkedDate = new Date(googleData.linked_at);
                const formattedDate = linkedDate.toLocaleDateString('pt-BR', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
                linkedAtEl.textContent = formattedDate;
            } else if (linkedAtEl) {
                linkedAtEl.textContent = 'N/A';
            }
        } else {
            // Se não estiver vinculado, mostrar mensagem
            const emailEl = document.getElementById('googleSettingsEmail');
            const nameEl = document.getElementById('googleSettingsName');
            const linkedAtEl = document.getElementById('googleSettingsLinkedAt');
            
            if (emailEl) emailEl.textContent = 'Nenhuma conta vinculada';
            if (nameEl) nameEl.textContent = 'N/A';
            if (linkedAtEl) linkedAtEl.textContent = 'N/A';
        }
    } catch (error) {
        console.error('Erro ao carregar dados do Google:', error);
        
        // Mostrar erro nos campos
        const emailEl = document.getElementById('googleSettingsEmail');
        const nameEl = document.getElementById('googleSettingsName');
        const linkedAtEl = document.getElementById('googleSettingsLinkedAt');
        
        if (emailEl) emailEl.textContent = 'Erro ao carregar';
        if (nameEl) nameEl.textContent = 'Erro ao carregar';
        if (linkedAtEl) linkedAtEl.textContent = 'Erro ao carregar';
    }
}

// Fechar modal de configurações da conta Google
window.closeGoogleSettingsModal = function closeGoogleSettingsModal() {
    const modal = document.getElementById('googleSettingsModal');
    if (modal) {
        modal.classList.add('hidden');
    }
};

// FUNÇÃO DESATIVADA - Campos de senha sempre habilitados agora (sem necessidade de Google)
function togglePasswordFields(isGoogleLinked) {
    // DESATIVADO: Sempre habilitar campos de senha (não precisa mais de Google vinculado)
    const newPasswordInput = document.getElementById('profileNewPassword');
    const confirmPasswordInput = document.getElementById('profileConfirmPassword');
    const passwordToggle = document.getElementById('profilePasswordToggle');
    const warningEl = document.getElementById('passwordChangeWarning');
    
    // Sempre habilitar (independente do Google)
    if (newPasswordInput) {
        newPasswordInput.disabled = false;
        newPasswordInput.classList.remove('cursor-not-allowed');
    }
    if (confirmPasswordInput) {
        confirmPasswordInput.disabled = false;
        confirmPasswordInput.classList.remove('cursor-not-allowed');
    }
    if (passwordToggle) {
        passwordToggle.disabled = false;
    }
    if (warningEl) {
        warningEl.classList.add('hidden'); // Esconder aviso
    }
}

// Vincular conta Google via OAuth
window.linkGoogleAccount = async function linkGoogleAccount() {
    try {
        // Obter URL de autorização do Google
        const response = await fetch('./api/google-auth.php?action=get_auth_url');
        const result = await response.json();
        
        if (!result.success) {
            // Se for erro de ambiente local, mostrar mensagem mais detalhada
            if (result.local_detected) {
                let errorMsg = result.error + '\n\n';
                if (result.solutions) {
                    errorMsg += 'Soluções:\n' + result.solutions.join('\n');
                }
                showErrorModal(errorMsg);
            } else {
                showErrorModal(result.error || 'Erro ao iniciar vinculação Google. Verifique se as credenciais estão configuradas.');
            }
            return;
        }
        
        // IMPORTANTE: Verificar se a redirect_uri está correta
        if (result.debug && result.debug.redirect_uri) {
            console.log('🔍 Google OAuth - redirect_uri:', result.debug.redirect_uri);
            console.log('🔍 Google OAuth - auth_url:', result.auth_url);
            
            // Validar que a redirect_uri está correta (sem /lactech/ na raiz do domínio)
            const expectedUri = 'https://lactechsys.com/google-callback.php';
            if (result.debug.redirect_uri !== expectedUri) {
                console.warn('⚠️ AVISO: redirect_uri diferente do esperado');
                console.warn('⚠️ Esperado:', expectedUri);
                console.warn('⚠️ Recebido:', result.debug.redirect_uri);
                // Não bloquear, apenas logar aviso (pode ser ambiente local ou configuração diferente)
                // A validação real será feita pelo Google durante o OAuth
            }
        }
        
        // Abrir popup do Google OAuth
        const width = 500;
        const height = 600;
        const left = (window.innerWidth - width) / 2;
        const top = (window.innerHeight - height) / 2;
        
        const popup = window.open(
            result.auth_url,
            'google-oauth',
            `width=${width},height=${height},left=${left},top=${top},toolbar=no,location=no,menubar=no`
        );
        
        // Usar postMessage em vez de verificar window.closed (evita erro Cross-Origin-Opener-Policy)
        // O callback do Google enviará mensagem para este window quando concluir
        
        // Escutar mensagens do popup (quando callback retorna)
        const messageHandler = async function(event) {
            // Verificar origem por segurança
            if (event.origin !== window.location.origin && event.origin !== 'https://lactechsys.com') {
                return;
            }
            
            // Verificar se tem dados e tipo
            if (!event.data || !event.data.type) {
                return;
            }
            
            if (event.data.type === 'google_oauth_success') {
                console.log('✅ Mensagem de sucesso recebida do callback Google');
                
                // Remover listener
                window.removeEventListener('message', messageHandler);
                
                // Fechar popup se ainda estiver aberto
                if (popup && !popup.closed) {
                    try {
                        popup.close();
                        console.log('✅ Popup fechado');
                    } catch (e) {
                        console.warn('⚠️ Popup já estava fechado ou bloqueado:', e);
                    }
                }
                
                // Mostrar modal de conta vinculada
                if (typeof showGoogleLinkedModal === 'function') {
                    showGoogleLinkedModal(event.data.message || 'Conta Google vinculada com sucesso! Você pode receber códigos OTP por e-mail.');
                    console.log('✅ Modal de sucesso exibido');
                } else {
                    console.error('❌ Função showGoogleLinkedModal não encontrada');
                    // Fallback: mostrar alerta simples
                    alert(event.data.message || 'Conta Google vinculada com sucesso!');
                }
                
                // Recarregar status de segurança
                if (typeof loadSecurityStatus === 'function') {
                    await loadSecurityStatus();
                    console.log('✅ Status de segurança recarregado');
                }
            } else if (event.data.type === 'google_oauth_error') {
                // Remover listener
                window.removeEventListener('message', messageHandler);
                
                // Fechar popup se ainda estiver aberto
                if (popup && !popup.closed) {
                    try {
                        popup.close();
                    } catch (e) {
                        // Ignorar erro se popup já foi fechado ou bloqueado por Cross-Origin
                    }
                }
                
                // Mostrar erro
                showErrorModal(event.data.message || 'Erro ao vincular conta Google.');
            }
        };
        
        window.addEventListener('message', messageHandler);
        
        // Fallback: verificar se popup foi fechado manualmente (com tratamento de erro para Cross-Origin)
        const checkPopupInterval = setInterval(() => {
            try {
                if (popup.closed) {
                    clearInterval(checkPopupInterval);
                    window.removeEventListener('message', messageHandler);
                    // Se fechou manualmente, verificar status
                    setTimeout(async () => {
                        if (typeof loadSecurityStatus === 'function') {
                            await loadSecurityStatus();
                        }
                    }, 1000);
                }
            } catch (e) {
                // Se der erro Cross-Origin-Opener-Policy, ignorar e confiar apenas no postMessage
                // Não limpar o intervalo, apenas ignorar o erro
            }
        }, 1000);
        
    } catch (e) {
        console.error('Erro ao vincular Google:', e);
        showErrorModal('Erro ao iniciar vinculação Google. Tente novamente.');
    }
};

// Abrir modal de confirmação para desvincular Google
window.unlinkGoogleAccount = function unlinkGoogleAccount() {
    openUnlinkGoogleModal();
};

// Confirmar desvincular Google
window.confirmUnlinkGoogle = async function confirmUnlinkGoogle() {
    closeUnlinkGoogleModal();
    
    try {
        // Primeiro, gerar OTP
        const otpResponse = await fetch('./api/security.php?action=generate_otp', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ action_type: 'google_unlink' })
        });
        
        const otpResult = await otpResponse.json();
        
        if (!otpResult.success) {
            showErrorModal(otpResult.error || 'Erro ao gerar código OTP');
            return;
        }
        
        // Abrir modal para inserir OTP e senha
        openOtpPasswordModal('Desvincular Conta Google', 'Digite o código OTP enviado para seu e-mail e sua senha atual:', true, async function(otpCode, currentPassword) {
            try {
                const response = await fetch('./api/google-auth.php?action=unlink_account', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        otp_code: otpCode,
                        current_password: currentPassword
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showSuccessModal('Conta Google desvinculada com sucesso');
                    if (typeof loadSecurityStatus === 'function') {
                        await loadSecurityStatus();
                    }
                } else {
                    showErrorModal(result.error || 'Erro ao desvincular conta Google');
                }
            } catch (e) {
                console.error('Erro ao desvincular Google:', e);
                showErrorModal('Erro ao desvincular conta Google');
            }
        });
    } catch (e) {
        console.error('Erro ao desvincular Google:', e);
        showErrorModal('Erro ao desvincular conta Google');
    }
};

// Configurar 2FA (placeholder)
window.setup2FA = function setup2FA() {
    showErrorModal('Funcionalidade 2FA será implementada em breve');
};

// Desativar 2FA (placeholder)
window.disable2FA = function disable2FA() {
    showErrorModal('Funcionalidade 2FA será implementada em breve');
};

// ==================== MÁSCARAS DE FORMATAÇÃO ====================

/**
 * Formata número de telefone brasileiro
 * Suporta celular (11 dígitos) e fixo (10 dígitos)
 * @param {string} value - Número sem formatação
 * @returns {string} - Número formatado: (XX) XXXXX-XXXX ou (XX) XXXX-XXXX
 */
function formatPhone(value) {
    if (!value) return '';
    
    // Remove tudo que não é dígito
    const numbers = value.replace(/\D/g, '');
    
    // Limita a 11 dígitos (celular) ou 10 dígitos (fixo)
    const limited = numbers.slice(0, 11);
    
    // Aplica máscara
    if (limited.length <= 10) {
        // Telefone fixo: (XX) XXXX-XXXX
        return limited.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
    } else {
        // Celular: (XX) XXXXX-XXXX
        return limited.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
    }
}

/**
 * Formata CNPJ brasileiro
 * @param {string} value - CNPJ sem formatação
 * @returns {string} - CNPJ formatado: XX.XXX.XXX/XXXX-XX
 */
function formatCNPJ(value) {
    if (!value) return '';
    
    // Remove tudo que não é dígito
    const numbers = value.replace(/\D/g, '');
    
    // Limita a 14 dígitos
    const limited = numbers.slice(0, 14);
    
    // Aplica máscara: XX.XXX.XXX/XXXX-XX
    return limited.replace(
        /(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/,
        '$1.$2.$3/$4-$5'
    );
}

/**
 * Configura máscaras nos campos de telefone e CNPJ do perfil
 */
function setupProfileMasks() {
    // Máscara para telefone do usuário
    const profilePhoneEl = document.getElementById('profilePhone');
    if (profilePhoneEl) {
        // Remover listeners antigos
        profilePhoneEl.removeEventListener('input', handlePhoneInput);
        // Adicionar novo listener
        profilePhoneEl.addEventListener('input', handlePhoneInput);
    }
    
    // Máscara para telefone da fazenda
    const farmPhoneEl = document.getElementById('farmPhone');
    if (farmPhoneEl) {
        // Remover listeners antigos
        farmPhoneEl.removeEventListener('input', handlePhoneInput);
        // Adicionar novo listener
        farmPhoneEl.addEventListener('input', handlePhoneInput);
    }
    
    // Máscara para CNPJ
    const farmCNPJEl = document.getElementById('farmCNPJ');
    if (farmCNPJEl) {
        // Remover listeners antigos
        farmCNPJEl.removeEventListener('input', handleCNPJInput);
        // Adicionar novo listener
        farmCNPJEl.addEventListener('input', handleCNPJInput);
    }
}

/**
 * Handler para input de telefone
 */
function handlePhoneInput(e) {
    const cursorPos = e.target.selectionStart;
    const oldValue = e.target.value;
    const formatted = formatPhone(e.target.value);
    
    e.target.value = formatted;
    
    // Ajustar posição do cursor após formatação
    // Contar quantos caracteres não-dígitos foram adicionados antes da posição do cursor
    const digitsBeforeCursor = (oldValue.slice(0, cursorPos).match(/\d/g) || []).length;
    const digitsInFormatted = formatted.match(/\d/g) || [];
    
    // Encontrar a posição do cursor no texto formatado baseado nos dígitos
    let newCursorPos = 0;
    let digitsCount = 0;
    
    for (let i = 0; i < formatted.length; i++) {
        if (/\d/.test(formatted[i])) {
            digitsCount++;
            if (digitsCount === digitsBeforeCursor) {
                newCursorPos = i + 1;
                break;
            }
        }
        if (digitsCount < digitsBeforeCursor) {
            newCursorPos = i + 1;
        }
    }
    
    // Limitar posição ao tamanho do texto
    newCursorPos = Math.min(newCursorPos, formatted.length);
    
    // Restaurar cursor
    setTimeout(() => {
        e.target.setSelectionRange(newCursorPos, newCursorPos);
    }, 0);
    
    // Atualizar atributo value também
    e.target.setAttribute('value', formatted);
}

/**
 * Handler para input de CNPJ
 */
function handleCNPJInput(e) {
    const cursorPos = e.target.selectionStart;
    const oldValue = e.target.value;
    const formatted = formatCNPJ(e.target.value);
    
    e.target.value = formatted;
    
    // Ajustar posição do cursor após formatação
    // Contar quantos caracteres não-dígitos foram adicionados antes da posição do cursor
    const digitsBeforeCursor = (oldValue.slice(0, cursorPos).match(/\d/g) || []).length;
    const digitsInFormatted = formatted.match(/\d/g) || [];
    
    // Encontrar a posição do cursor no texto formatado baseado nos dígitos
    let newCursorPos = 0;
    let digitsCount = 0;
    
    for (let i = 0; i < formatted.length; i++) {
        if (/\d/.test(formatted[i])) {
            digitsCount++;
            if (digitsCount === digitsBeforeCursor) {
                newCursorPos = i + 1;
                break;
            }
        }
        if (digitsCount < digitsBeforeCursor) {
            newCursorPos = i + 1;
        }
    }
    
    // Limitar posição ao tamanho do texto
    newCursorPos = Math.min(newCursorPos, formatted.length);
    
    // Restaurar cursor
    setTimeout(() => {
        e.target.setSelectionRange(newCursorPos, newCursorPos);
    }, 0);
    
    // Atualizar atributo value também
    e.target.setAttribute('value', formatted);
}

window.openProfileOverlay = function openProfileOverlay() {
    const modal = document.getElementById('profileOverlay');
    if (!modal) return;
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    
    // Resetar foto
    profilePhotoFile = null;
    if (document.getElementById('profilePhotoInput')) document.getElementById('profilePhotoInput').value = '';
    
    // Atualizar UI do modo offline quando abrir o perfil
    if (typeof offlineManager !== 'undefined' && offlineManager.updateUI) {
        offlineManager.updateUI();
    }
    
    // IMPORTANTE: Armazenar valores originais ANTES de chamar updateProfileEditMode
    // Ler do atributo value ou do value do input
    const nameEl = document.getElementById('profileName');
    const phoneEl = document.getElementById('profilePhone');
    const farmNameEl = document.getElementById('farmName');
    const farmPhoneEl = document.getElementById('farmPhone');
    const farmCNPJEl = document.getElementById('farmCNPJ');
    const farmAddressEl = document.getElementById('farmAddress');
    const pushNotificationsEl = document.getElementById('pushNotifications');
    
    // Função auxiliar para ler valor corretamente mesmo quando desabilitado
    const getValue = (el) => {
        if (!el) return '';
        // Para inputs, ler do atributo value primeiro (mais confiável quando desabilitado)
        // Depois tentar a propriedade value
        const attrValue = el.getAttribute('value');
        const propValue = el.value;
        
        // Para textarea, verificar também textContent
        if (el.tagName === 'TEXTAREA') {
            const textValue = el.value || el.textContent || el.innerText || '';
            return textValue.trim();
        }
        
        // Retornar o que tiver valor, priorizando atributo
        return attrValue || propValue || '';
    };
    
    // Salvar valores originais imediatamente - garantir que sempre tenham valores
    const nameValue = String(getValue(nameEl)).trim();
    const phoneValue = String(getValue(phoneEl)).trim();
    const farmNameValue = String(getValue(farmNameEl)).trim();
    const farmPhoneValue = String(getValue(farmPhoneEl)).trim();
    const farmCNPJValue = String(getValue(farmCNPJEl)).trim();
    const farmAddressValue = String(getValue(farmAddressEl)).trim();
    
    profileOriginalValues = {
        name: nameValue,
        phone: phoneValue,
        farmName: farmNameValue,
        farmPhone: farmPhoneValue,
        farmCNPJ: farmCNPJValue,
        farmAddress: farmAddressValue,
        pushNotifications: pushNotificationsEl ? (pushNotificationsEl.checked || false) : false,
        newPassword: '',
        confirmPassword: ''
    };
    
    // Log para debug (mostrar valores lidos)
    console.log('Valores originais salvos:', profileOriginalValues);
    console.log('Elementos encontrados:', {
        nameEl: !!nameEl, phoneEl: !!phoneEl, farmNameEl: !!farmNameEl,
        farmPhoneEl: !!farmPhoneEl, farmCNPJEl: !!farmCNPJEl, farmAddressEl: !!farmAddressEl
    });
    
    // AGORA sim, resetar modo edição
    profileEditMode = false;
    updateProfileEditMode();
    
    // Ocultar footer inicialmente
    const footer = document.getElementById('profileFooter');
    if (footer) footer.classList.add('hidden');
    
    // Aplicar máscaras nos campos de telefone e CNPJ
    setupProfileMasks();
    
    // Formatar valores iniciais se já estiverem preenchidos
    if (phoneEl && phoneEl.value) {
        phoneEl.value = formatPhone(phoneEl.value);
        phoneEl.setAttribute('value', phoneEl.value);
    }
    if (farmPhoneEl && farmPhoneEl.value) {
        farmPhoneEl.value = formatPhone(farmPhoneEl.value);
        farmPhoneEl.setAttribute('value', farmPhoneEl.value);
    }
    if (farmCNPJEl && farmCNPJEl.value) {
        farmCNPJEl.value = formatCNPJ(farmCNPJEl.value);
        farmCNPJEl.setAttribute('value', farmCNPJEl.value);
    }
    
    // Adicionar listeners para detectar mudanças (só quando em modo edição)
    setupProfileChangeDetection();
};

window.toggleProfileEdit = function toggleProfileEdit() {
    profileEditMode = !profileEditMode;
    updateProfileEditMode();
};

function updateProfileEditMode() {
    const btn = document.getElementById('editProfileBtn');
    const inputs = document.querySelectorAll('.profile-input');
    
    if (profileEditMode) {
        // Modo edição: habilitar inputs e aplicar borda verde
        inputs.forEach(input => {
            if (input.id !== 'profileEmail') { // Email sempre desabilitado
                input.disabled = false;
                input.classList.remove('border-gray-200', 'bg-gray-50', 'text-gray-500', 'cursor-not-allowed');
                input.classList.add('border-green-300', 'bg-white', 'text-gray-900', 'focus:ring-2', 'focus:ring-green-500', 'focus:border-green-500');
            }
        });
        
        // Habilitar campos de senha e botão de mostrar senha (SEM necessidade de Google)
        const newPasswordInput = document.getElementById('profileNewPassword');
        const confirmPasswordInput = document.getElementById('profileConfirmPassword');
        const passwordToggle = document.getElementById('profilePasswordToggle');
        
        if (newPasswordInput) {
            newPasswordInput.disabled = false;
            newPasswordInput.classList.remove('bg-gray-50', 'text-gray-500', 'cursor-not-allowed');
            newPasswordInput.classList.add('bg-white', 'text-gray-900');
        }
        if (confirmPasswordInput) {
            confirmPasswordInput.disabled = false;
            confirmPasswordInput.classList.remove('bg-gray-50', 'text-gray-500', 'cursor-not-allowed');
            confirmPasswordInput.classList.add('bg-white', 'text-gray-900');
        }
        if (passwordToggle) {
            passwordToggle.disabled = false;
            passwordToggle.classList.remove('opacity-50', 'cursor-not-allowed');
            passwordToggle.classList.add('cursor-pointer');
        }
        
        // Aplicar máscaras quando entrar em modo de edição
        setupProfileMasks();
        
        // Atualizar botão
        if (btn) {
            btn.innerHTML = `
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                Cancelar Edição
            `;
            btn.classList.remove('text-gray-700');
            btn.classList.add('text-red-600');
        }
        
        // Ativar detecção de mudanças
        setupProfileChangeDetection();
    } else {
        // Modo visualização: desabilitar inputs e aplicar estilo cinza
        inputs.forEach(input => {
            input.disabled = true;
            input.classList.remove('border-green-300', 'bg-white', 'text-gray-900', 'focus:ring-2', 'focus:ring-green-500', 'focus:border-green-500');
            input.classList.add('border-gray-200', 'bg-gray-50', 'text-gray-500', 'cursor-not-allowed');
        });
        
        // Desabilitar botão de mostrar senha quando o campo estiver desabilitado
        const passwordToggle = document.getElementById('profilePasswordToggle');
        if (passwordToggle) {
            passwordToggle.disabled = true;
            passwordToggle.classList.add('opacity-50', 'cursor-not-allowed');
            passwordToggle.classList.remove('cursor-pointer');
        }
        
        // NÃO restaurar valores quando em modo visualização
        // Os valores já devem estar corretos nos inputs
        // Só garantir que os atributos value estejam sincronizados com .value
        const nameEl = document.getElementById('profileName');
        const phoneEl = document.getElementById('profilePhone');
        const farmNameEl = document.getElementById('farmName');
        const farmPhoneEl = document.getElementById('farmPhone');
        const farmCNPJEl = document.getElementById('farmCNPJ');
        const farmAddressEl = document.getElementById('farmAddress');
        
        // Sincronizar atributo value com a propriedade value (mantém valores atuais)
        if (nameEl) {
            const currentValue = nameEl.value || '';
            nameEl.setAttribute('value', currentValue);
            nameEl.defaultValue = currentValue;
        }
        if (phoneEl) {
            const currentValue = phoneEl.value || '';
            // Formatar telefone antes de salvar
            const formattedPhone = formatPhone(currentValue);
            phoneEl.value = formattedPhone;
            phoneEl.setAttribute('value', formattedPhone);
            phoneEl.defaultValue = formattedPhone;
        }
        if (farmNameEl) {
            const currentValue = farmNameEl.value || '';
            farmNameEl.setAttribute('value', currentValue);
            farmNameEl.defaultValue = currentValue;
        }
        if (farmPhoneEl) {
            const currentValue = farmPhoneEl.value || '';
            // Formatar telefone antes de salvar
            const formattedFarmPhone = formatPhone(currentValue);
            farmPhoneEl.value = formattedFarmPhone;
            farmPhoneEl.setAttribute('value', formattedFarmPhone);
            farmPhoneEl.defaultValue = formattedFarmPhone;
        }
        if (farmCNPJEl) {
            const currentValue = farmCNPJEl.value || '';
            // Formatar CNPJ antes de salvar
            const formattedCNPJ = formatCNPJ(currentValue);
            farmCNPJEl.value = formattedCNPJ;
            farmCNPJEl.setAttribute('value', formattedCNPJ);
            farmCNPJEl.defaultValue = formattedCNPJ;
        }
        if (farmAddressEl) {
            // Para textarea, apenas garantir que o valor está correto
            // Não há necessidade de sincronizar atributo value
        }
        if (document.getElementById('profileNewPassword')) document.getElementById('profileNewPassword').value = '';
        if (document.getElementById('profileConfirmPassword')) document.getElementById('profileConfirmPassword').value = '';
        
        // Atualizar botão
        if (btn) {
            btn.innerHTML = `
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Editar
            `;
            btn.classList.remove('text-red-600');
            btn.classList.add('text-gray-700');
        }
        
        // Ocultar footer
        const footer = document.getElementById('profileFooter');
        if (footer) footer.classList.add('hidden');
    }
}

window.closeProfileOverlay = function closeProfileOverlay() {
    const modal = document.getElementById('profileOverlay');
    if (!modal) return;
    
    // Fechar câmera se estiver aberta
    closeCamera();
    
    // Resetar modo edição antes de fechar
    if (profileEditMode) {
        profileEditMode = false;
        updateProfileEditMode();
    }
    
    modal.classList.add('hidden');
    document.body.style.overflow = 'auto';
    const footer = document.getElementById('profileFooter');
    if (footer) footer.classList.add('hidden');
};

function setupProfileChangeDetection() {
    const inputs = ['profileName', 'profilePhone', 'farmName', 'farmPhone', 'farmCNPJ', 'farmAddress', 'profileNewPassword', 'profileConfirmPassword'];
    const checkboxes = ['pushNotifications'];
    
    const checkForChanges = () => {
        const hasChanges = 
            (document.getElementById('profileName')?.value !== profileOriginalValues.name) ||
            (document.getElementById('profilePhone')?.value !== profileOriginalValues.phone) ||
            (document.getElementById('farmName')?.value !== profileOriginalValues.farmName) ||
            (document.getElementById('farmPhone')?.value !== profileOriginalValues.farmPhone) ||
            (document.getElementById('farmCNPJ')?.value !== profileOriginalValues.farmCNPJ) ||
            (document.getElementById('farmAddress')?.value !== profileOriginalValues.farmAddress) ||
            (document.getElementById('pushNotifications')?.checked !== profileOriginalValues.pushNotifications) ||
            (document.getElementById('profileNewPassword')?.value !== '') ||
            (document.getElementById('profileConfirmPassword')?.value !== '');
        
        const footer = document.getElementById('profileFooter');
        if (footer) {
            if (hasChanges) footer.classList.remove('hidden');
            else footer.classList.add('hidden');
        }
    };
    
    inputs.forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.removeEventListener('input', checkForChanges);
            el.addEventListener('input', checkForChanges);
        }
    });
    
    checkboxes.forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.removeEventListener('change', checkForChanges);
            el.addEventListener('change', checkForChanges);
        }
    });
}

window.cancelProfileChanges = function cancelProfileChanges() {
    // Log para debug
    console.log('Cancelando alterações. Valores originais:', profileOriginalValues);
    
    // Restaurar valores originais - garantir que sejam sempre strings válidas
    const nameEl = document.getElementById('profileName');
    const phoneEl = document.getElementById('profilePhone');
    const farmNameEl = document.getElementById('farmName');
    const farmPhoneEl = document.getElementById('farmPhone');
    const farmCNPJEl = document.getElementById('farmCNPJ');
    const farmAddressEl = document.getElementById('farmAddress');
    const pushNotificationsEl = document.getElementById('pushNotifications');
    
    // SEMPRE recarregar valores do HTML para garantir que estejam corretos
    // Isso evita problemas se profileOriginalValues foi perdido ou corrompido
    const currentName = nameEl ? (nameEl.getAttribute('value') || nameEl.value || '').trim() : '';
    const currentPhone = phoneEl ? (phoneEl.getAttribute('value') || phoneEl.value || '').trim() : '';
    const currentFarmName = farmNameEl ? (farmNameEl.getAttribute('value') || farmNameEl.value || '').trim() : '';
    const currentFarmPhone = farmPhoneEl ? (farmPhoneEl.getAttribute('value') || farmPhoneEl.value || '').trim() : '';
    const currentFarmCNPJ = farmCNPJEl ? (farmCNPJEl.getAttribute('value') || farmCNPJEl.value || '').trim() : '';
    const currentFarmAddress = farmAddressEl ? (farmAddressEl.value || farmAddressEl.textContent || '').trim() : '';
    
    // Restaurar valores originais (usar profileOriginalValues se disponível, senão usar valores atuais)
    const originalName = (profileOriginalValues.name || currentName || '').trim();
    const originalPhone = (profileOriginalValues.phone || currentPhone || '').trim();
    const originalFarmName = (profileOriginalValues.farmName || currentFarmName || '').trim();
    const originalFarmPhone = (profileOriginalValues.farmPhone || currentFarmPhone || '').trim();
    const originalFarmCNPJ = (profileOriginalValues.farmCNPJ || currentFarmCNPJ || '').trim();
    const originalFarmAddress = (profileOriginalValues.farmAddress || currentFarmAddress || '').trim();
    
    // Formatar telefones e CNPJ antes de restaurar
    const formattedOriginalPhone = formatPhone(originalPhone);
    const formattedOriginalFarmPhone = formatPhone(originalFarmPhone);
    const formattedOriginalCNPJ = formatCNPJ(originalFarmCNPJ);
    
    // Se profileOriginalValues não existir ou estiver vazio, usar valores atuais do HTML
    // Caso contrário, usar os valores originais salvos
    if (!profileOriginalValues || Object.keys(profileOriginalValues).length === 0 || 
        !profileOriginalValues.name || profileOriginalValues.name === '') {
        console.warn('Valores originais não encontrados ou vazios, usando valores atuais do HTML');
        profileOriginalValues = {
            name: currentName || '',
            phone: currentPhone || '',
            farmName: currentFarmName || '',
            farmPhone: currentFarmPhone || '',
            farmCNPJ: currentFarmCNPJ || '',
            farmAddress: currentFarmAddress || '',
            pushNotifications: pushNotificationsEl ? (pushNotificationsEl.checked || false) : false,
            newPassword: '',
            confirmPassword: ''
        };
    }
    
    // Garantir que os valores originais sejam sempre strings válidas e atualizar tanto o value quanto o atributo
    if (nameEl) {
        const val = profileOriginalValues.name !== undefined && profileOriginalValues.name !== null ? String(profileOriginalValues.name) : '';
        nameEl.value = val;
        nameEl.setAttribute('value', val);
        console.log('Restaurado nome:', val);
    }
    if (phoneEl) {
        const val = formattedOriginalPhone || (profileOriginalValues.phone !== undefined && profileOriginalValues.phone !== null ? String(profileOriginalValues.phone) : '');
        const formattedVal = formatPhone(val);
        phoneEl.value = formattedVal;
        phoneEl.setAttribute('value', formattedVal);
        console.log('Restaurado telefone:', formattedVal);
    }
    if (farmNameEl) {
        const val = profileOriginalValues.farmName !== undefined && profileOriginalValues.farmName !== null ? String(profileOriginalValues.farmName) : '';
        farmNameEl.value = val;
        farmNameEl.setAttribute('value', val);
        console.log('Restaurado nome fazenda:', val);
    }
    if (farmPhoneEl) {
        const val = formattedOriginalFarmPhone || (profileOriginalValues.farmPhone !== undefined && profileOriginalValues.farmPhone !== null ? String(profileOriginalValues.farmPhone) : '');
        const formattedVal = formatPhone(val);
        farmPhoneEl.value = formattedVal;
        farmPhoneEl.setAttribute('value', formattedVal);
        console.log('Restaurado telefone fazenda:', formattedVal);
    }
    if (farmCNPJEl) {
        const val = formattedOriginalCNPJ || (profileOriginalValues.farmCNPJ !== undefined && profileOriginalValues.farmCNPJ !== null ? String(profileOriginalValues.farmCNPJ) : '');
        const formattedVal = formatCNPJ(val);
        farmCNPJEl.value = formattedVal;
        farmCNPJEl.setAttribute('value', formattedVal);
        console.log('Restaurado CNPJ:', formattedVal);
    }
    if (farmAddressEl) {
        const val = profileOriginalValues.farmAddress !== undefined && profileOriginalValues.farmAddress !== null ? String(profileOriginalValues.farmAddress) : '';
        farmAddressEl.value = val;
        console.log('Restaurado endereço:', val);
    }
    if (pushNotificationsEl) pushNotificationsEl.checked = profileOriginalValues.pushNotifications || false;
    
    // Limpar campos de senha ao cancelar (sem restaurar valores, pois não têm valores originais)
    const newPasswordEl = document.getElementById('profileNewPassword');
    const confirmPasswordEl = document.getElementById('profileConfirmPassword');
    if (newPasswordEl) {
        newPasswordEl.value = '';
        newPasswordEl.setAttribute('value', '');
    }
    if (confirmPasswordEl) {
        confirmPasswordEl.value = '';
        confirmPasswordEl.setAttribute('value', '');
    }
    
    // Resetar foto
    if (document.getElementById('profilePhotoInput')) document.getElementById('profilePhotoInput').value = '';
    profilePhotoFile = null;
    
    // Restaurar foto original (buscar do servidor se necessário)
    // Por enquanto, apenas limpar o preview - a foto original já está no HTML
    
    // Ocultar footer
    const footer = document.getElementById('profileFooter');
    if (footer) footer.classList.add('hidden');
    
    // Atualizar valores originais
    profileOriginalValues.newPassword = '';
    profileOriginalValues.confirmPassword = '';
    
    // Não sair do modo edição ao cancelar - usuário pode continuar editando
};

let cameraStream = null;
let isFaceCentered = false;
let faceDetectionInterval = null;
let autoCaptureTimer = null;
let captureCountdownInterval = null;
let isCapturing = false;
let faceApiLoaded = false;
let faceApiModelsLoaded = false;

window.openCamera = async function openCamera() {
    // Verificar se o navegador suporta a API de câmera
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        // Fallback: usar input com capture para mobile
        const cameraInput = document.getElementById('profileCameraInput');
        if (cameraInput) {
            cameraInput.click();
            return;
        }
        alert('Seu navegador não suporta acesso à câmera. Por favor, use a opção "Escolher da galeria".');
        return;
    }

    try {
        // Resetar para câmera frontal ao abrir
        currentFacingMode = 'user';
        
        // Solicitar acesso à câmera
        const stream = await navigator.mediaDevices.getUserMedia({ 
            video: { 
                facingMode: currentFacingMode,
                width: { ideal: 640 },
                height: { ideal: 640 }
            } 
        });
        
        cameraStream = stream;
        
        // Criar modal para preview da câmera personalizado
        const cameraModal = document.createElement('div');
        cameraModal.id = 'cameraModal';
        cameraModal.className = 'fixed inset-0 z-[60] bg-black';
        
        cameraModal.innerHTML = `
            <div class="relative w-full h-full">
                <!-- Logo do sistema no topo -->
                <div class="absolute top-6 left-1/2 transform -translate-x-1/2 z-10 flex items-center gap-3">
                    <img src="./assets/img/lactech-logo.png" alt="LacTech" class="h-10 w-auto" onerror="this.onerror=null; this.src='https://i.postimg.cc/vmrkgDcB/lactech.png'; this.onerror=function(){this.style.display='none'; this.nextElementSibling.style.display='flex';};">
                    <div class="w-10 h-10 bg-white bg-opacity-20 rounded-xl flex items-center justify-center backdrop-blur-sm" style="display: none;">
                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M19.5 6c-1.3 0-2.5.8-3 2-.5-1.2-1.7-2-3-2s-2.5.8-3 2c-.5-1.2-1.7-2-3-2C5.5 6 4 7.5 4 9.5c0 1.3.7 2.4 1.7 3.1-.4.6-.7 1.3-.7 2.1 0 2.2 1.8 4 4 4h6c2.2 0 4-1.8 4-4 0-.8-.3-1.5-.7-2.1 1-.7 1.7-1.8 1.7-3.1 0-2-1.5-3.5-3.5-3.5z"/>
                        </svg>
                    </div>
                    <h2 class="text-xl font-bold text-white">LacTech</h2>
                </div>
                
                <!-- Container do vídeo com frame facial - Full Screen -->
                <div class="relative w-full h-full">
                    <video id="cameraPreview" autoplay playsinline class="w-full h-full object-cover"></video>
                    <canvas id="cameraCapture" class="hidden"></canvas>
                    
                    <!-- Overlay com frame facial oval verde -->
                    <div class="absolute inset-0 pointer-events-none flex items-center justify-center">
                        <!-- Frame oval verde para rosto -->
                        <div id="faceFrame" class="relative" style="width: 50%; max-width: 400px; aspect-ratio: 0.75;">
                            <!-- Oval verde - começa pontilhado -->
                            <svg id="faceOval" viewBox="0 0 200 266" class="absolute inset-0 w-full h-full" style="filter: drop-shadow(0 0 10px rgba(34, 197, 94, 0.6)); transition: all 0.3s;">
                                <ellipse id="faceEllipse" cx="100" cy="133" rx="85" ry="115" fill="none" stroke="#22c55e" stroke-width="5" stroke-opacity="1" stroke-dasharray="10,5"/>
                            </svg>
                            
                            <!-- Texto acima do frame -->
                            <div class="absolute -top-12 left-1/2 transform -translate-x-1/2 text-center w-full">
                                <div class="inline-block bg-black bg-opacity-70 px-4 py-2 rounded-lg backdrop-blur-sm border border-white border-opacity-20">
                                    <p class="text-white text-sm font-medium" id="faceStatusText">Posicione seu rosto no oval verde</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Instruções abaixo (mais acima) -->
                    <div class="absolute bottom-32 left-1/2 transform -translate-x-1/2 text-center">
                        <div class="bg-black bg-opacity-60 px-4 py-2 rounded-lg backdrop-blur-sm">
                            <p class="text-white text-sm font-medium mb-1">Mantenha o rosto centralizado</p>
                            <p class="text-white text-xs opacity-75">Olhe diretamente para a câmera</p>
                        </div>
                    </div>
                </div>
                
                <!-- Botões de ação -->
                <div class="absolute bottom-6 left-1/2 transform -translate-x-1/2 flex gap-4 items-center">
                    <button onclick="closeCamera()" class="w-16 h-16 rounded-full bg-gray-700 bg-opacity-70 hover:bg-opacity-90 text-white flex items-center justify-center transition-all backdrop-blur-sm">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                    <button id="captureButton" onclick="startCaptureCountdown()" disabled class="w-20 h-20 rounded-full bg-white hover:bg-gray-100 flex items-center justify-center transition-all shadow-lg border-4 border-green-500 opacity-50 cursor-not-allowed">
                        <div id="captureButtonInner" class="w-16 h-16 rounded-full bg-green-600 hover:bg-green-700 flex items-center justify-center transition-colors">
                            <svg id="captureIcon" class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span id="captureCountdown" class="absolute text-white text-xl font-bold" style="display: none;"></span>
                        </div>
                    </button>
                    <button onclick="switchCamera()" class="w-16 h-16 rounded-full bg-gray-700 bg-opacity-70 hover:bg-opacity-90 text-white flex items-center justify-center transition-all backdrop-blur-sm">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </button>
                </div>
            </div>
        `;
        document.body.appendChild(cameraModal);
        
        // Exibir o stream no vídeo
        const video = document.getElementById('cameraPreview');
        video.srcObject = stream;
        
        // Resetar estados - GARANTIR que comece desabilitado
        isFaceCentered = false;
        isCapturing = false;
        clearInterval(faceDetectionInterval);
        clearTimeout(autoCaptureTimer);
        
        // Garantir que o botão está desabilitado imediatamente
        setTimeout(() => {
            updateFaceFrame(false);
        }, 100);
        
        // Iniciar detecção facial quando vídeo estiver pronto
        video.onloadedmetadata = function() {
            // Resetar novamente antes de iniciar detecção
            isFaceCentered = false;
            updateFaceFrame(false);
            startFaceDetection();
        };
        
    } catch (err) {
        console.error('Erro ao acessar câmera:', err);
        if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
            alert('Permissão de câmera negada. Por favor, permita o acesso à câmera nas configurações do navegador.');
        } else if (err.name === 'NotFoundError' || err.name === 'DevicesNotFoundError') {
            alert('Câmera não encontrada. Por favor, use a opção "Escolher da galeria".');
        } else {
            // Fallback: usar input com capture
            const cameraInput = document.getElementById('profileCameraInput');
            if (cameraInput) {
                cameraInput.click();
            } else {
                alert('Erro ao acessar câmera. Por favor, use a opção "Escolher da galeria".');
            }
        }
    }
};

window.closeCamera = function closeCamera() {
    // Limpar timers
    clearInterval(faceDetectionInterval);
    clearTimeout(autoCaptureTimer);
    clearInterval(captureCountdownInterval);
    
    // Parar o stream da câmera
    if (cameraStream) {
        cameraStream.getTracks().forEach(track => track.stop());
        cameraStream = null;
    }
    
    // Remover modal
    const cameraModal = document.getElementById('cameraModal');
    if (cameraModal) {
        cameraModal.remove();
    }
    
    // Resetar estados
    isFaceCentered = false;
    isCapturing = false;
};

async function loadFaceApiModels() {
    if (faceApiModelsLoaded) return true;
    
    try {
        // Verificar se face-api está disponível
        if (typeof faceapi === 'undefined') {
            console.warn('face-api.js não está disponível, usando detecção simulada');
            return false;
        }
        
        // Carregar modelos (usando CDN público do jsDelivr)
        const modelBaseUrl = 'https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/weights/';
        
        await Promise.all([
            faceapi.nets.tinyFaceDetector.loadFromUri(modelBaseUrl),
            faceapi.nets.faceLandmark68Net.loadFromUri(modelBaseUrl),
        ]);
        
        faceApiModelsLoaded = true;
        faceApiLoaded = true;
        console.log('Modelos face-api carregados com sucesso');
        return true;
    } catch (error) {
        console.error('Erro ao carregar modelos face-api:', error);
        // Tentar usar modelos alternativos se o CDN falhar
        try {
            const alternativeUrl = 'https://raw.githubusercontent.com/justadudewhohacks/face-api.js/master/weights/';
            await Promise.all([
                faceapi.nets.tinyFaceDetector.loadFromUri(alternativeUrl),
                faceapi.nets.faceLandmark68Net.loadFromUri(alternativeUrl),
            ]);
            faceApiModelsLoaded = true;
            faceApiLoaded = true;
            console.log('Modelos face-api carregados do GitHub');
            return true;
        } catch (err2) {
            console.error('Erro ao carregar modelos alternativos:', err2);
            return false;
        }
    }
}

function startFaceDetection() {
    // Resetar estado - SEMPRE começar desabilitado
    isFaceCentered = false;
    updateFaceFrame(false);
    clearTimeout(autoCaptureTimer);
    
    // Verificar se face-api está disponível
    if (typeof faceapi === 'undefined' || !faceapi.nets) {
        console.warn('face-api.js não está disponível - botão permanecerá desabilitado');
        return; // NÃO usar fallback, apenas desabilitar
    }
    
    // Tentar carregar modelos face-api
    loadFaceApiModels().then(modelsLoaded => {
        if (!modelsLoaded) {
            console.warn('Modelos face-api não carregados - botão permanecerá desabilitado');
            isFaceCentered = false;
            updateFaceFrame(false);
            return; // NÃO usar fallback
        }
        
        // Detecção facial real com face-api.js
        const video = document.getElementById('cameraPreview');
        if (!video) {
            isFaceCentered = false;
            updateFaceFrame(false);
            return;
        }
        
        // Aguardar vídeo estar pronto antes de iniciar detecção
        let detectionAttempts = 0;
        const maxAttempts = 20; // 6 segundos máximo
        
        const checkVideoReady = setInterval(() => {
            if (video.readyState === 4 && video.videoWidth > 0) {
                clearInterval(checkVideoReady);
                startRealFaceDetection(video);
            } else {
                detectionAttempts++;
                if (detectionAttempts >= maxAttempts) {
                    clearInterval(checkVideoReady);
                    console.warn('Vídeo não ficou pronto - detecção desabilitada');
                    isFaceCentered = false;
                    updateFaceFrame(false);
                }
            }
        }, 300);
    }).catch(error => {
        console.error('Erro ao iniciar detecção facial:', error);
        isFaceCentered = false;
        updateFaceFrame(false);
    });
}

function startRealFaceDetection(video) {
    clearInterval(faceDetectionInterval);
    
    faceDetectionInterval = setInterval(async function() {
        // Verificações rigorosas
        if (isCapturing || !video || video.readyState !== 4 || video.paused || video.videoWidth === 0) {
            return;
        }
        
        // Verificar se face-api ainda está disponível
        if (typeof faceapi === 'undefined' || !faceapi.nets || !faceApiLoaded) {
            if (isFaceCentered) {
                isFaceCentered = false;
                updateFaceFrame(false);
                clearTimeout(autoCaptureTimer);
            }
            return;
        }
        
        try {
            // Detectar rostos no vídeo com opções mais rigorosas
            const options = new faceapi.TinyFaceDetectorOptions({ 
                inputSize: 320, 
                scoreThreshold: 0.5 // Threshold mais alto = mais rigoroso
            });
            
            const detections = await faceapi
                .detectAllFaces(video, options)
                .withFaceLandmarks();
            
            // Se NÃO houver rosto detectado, SEMPRE desabilitar
            if (!detections || detections.length === 0) {
                if (isFaceCentered) {
                    isFaceCentered = false;
                    updateFaceFrame(false);
                    clearTimeout(autoCaptureTimer);
                }
                return;
            }
            
            // Pegar o primeiro rosto detectado
            const detection = detections[0];
            
            // Validar se a detecção é válida
            if (!detection || !detection.detection || !detection.detection.box) {
                if (isFaceCentered) {
                    isFaceCentered = false;
                    updateFaceFrame(false);
                    clearTimeout(autoCaptureTimer);
                }
                return;
            }
            
            const faceBox = detection.detection.box;
            
            // Validar box do rosto
            if (!faceBox.x || !faceBox.y || !faceBox.width || !faceBox.height || 
                faceBox.width <= 0 || faceBox.height <= 0) {
                if (isFaceCentered) {
                    isFaceCentered = false;
                    updateFaceFrame(false);
                    clearTimeout(autoCaptureTimer);
                }
                return;
            }
            
            // Obter dimensões do vídeo e do frame oval
            const videoRect = video.getBoundingClientRect();
            const videoWidth = video.videoWidth;
            const videoHeight = video.videoHeight;
            
            if (videoWidth === 0 || videoHeight === 0) {
                return;
            }
            
            const faceFrame = document.getElementById('faceFrame');
            if (!faceFrame) {
                return;
            }
            
            const frameRect = faceFrame.getBoundingClientRect();
            if (frameRect.width === 0 || frameRect.height === 0) {
                return;
            }
            
            const frameCenterX = frameRect.left + frameRect.width / 2;
            const frameCenterY = frameRect.top + frameRect.height / 2;
            const frameWidth = frameRect.width;
            const frameHeight = frameRect.height;
            
            // Calcular posição do rosto em relação ao vídeo
            const scaleX = videoRect.width / videoWidth;
            const scaleY = videoRect.height / videoHeight;
            
            const faceCenterX = (faceBox.x * scaleX) + (faceBox.width * scaleX / 2);
            const faceCenterY = (faceBox.y * scaleY) + (faceBox.height * scaleY / 2);
            const faceWidth = faceBox.width * scaleX;
            const faceHeight = faceBox.height * scaleY;
            
            // Verificações MUITO rigorosas para considerar centralizado
            const tolerance = 0.15; // Reduzido para 15% de tolerância (mais rigoroso)
            const minFaceSize = frameWidth * 0.35; // Rosto deve ocupar pelo menos 35% do frame
            const maxFaceSize = frameWidth * 1.0; // Rosto não deve ser maior que o frame
            
            const horizontalDistance = Math.abs(faceCenterX - frameCenterX);
            const verticalDistance = Math.abs(faceCenterY - frameCenterY);
            const maxHorizontalDistance = (frameWidth / 2) * (1 + tolerance);
            const maxVerticalDistance = (frameHeight / 2) * (1 + tolerance);
            
            // Verificar TODAS as condições
            // Validar score (se disponível) - algumas versões não têm score
            const validScore = faceBox.score === undefined || faceBox.score > 0.5;
            
            const faceInFrame = 
                horizontalDistance < maxHorizontalDistance && // Dentro horizontalmente
                verticalDistance < maxVerticalDistance && // Dentro verticalmente
                faceWidth > minFaceSize && // Não muito pequeno
                faceWidth < maxFaceSize && // Não muito grande
                faceHeight > (frameHeight * 0.3) && // Altura mínima
                validScore; // Score mínimo de confiança (se disponível)
            
            // Atualizar estado APENAS se realmente mudou
            const wasCentered = isFaceCentered;
            
            if (faceInFrame !== wasCentered) {
                isFaceCentered = faceInFrame;
                updateFaceFrame(isFaceCentered);
                
                if (isFaceCentered && !wasCentered) {
                    // Rosto acabou de ser centralizado
                    startAutoCapture();
                } else if (!isFaceCentered && wasCentered) {
                    // Rosto saiu do centro
                    clearTimeout(autoCaptureTimer);
                }
            }
            
        } catch (error) {
            // Em caso de ERRO, SEMPRE desabilitar
            console.error('Erro na detecção facial:', error);
            if (isFaceCentered) {
                isFaceCentered = false;
                updateFaceFrame(false);
                clearTimeout(autoCaptureTimer);
            }
        }
    }, 500); // Verificar a cada 500ms (menos frequente para performance)
}


function updateFaceFrame(centered) {
    const faceEllipse = document.getElementById('faceEllipse');
    const faceStatusText = document.getElementById('faceStatusText');
    const captureButton = document.getElementById('captureButton');
    
    if (faceEllipse) {
        if (centered) {
            // Mudar de pontilhado para sólido
            faceEllipse.setAttribute('stroke-dasharray', 'none');
            faceEllipse.setAttribute('stroke-width', '6');
            faceEllipse.style.stroke = '#22c55e'; // Verde mais vibrante quando centralizado
        } else {
            // Voltar para pontilhado
            faceEllipse.setAttribute('stroke-dasharray', '10,5');
            faceEllipse.setAttribute('stroke-width', '5');
            faceEllipse.style.stroke = '#22c55e';
        }
    }
    
    if (faceStatusText) {
        if (centered) {
            faceStatusText.textContent = 'Rosto centralizado! Pronto para tirar foto';
        } else {
            faceStatusText.textContent = 'Posicione seu rosto no oval verde';
        }
    }
    
    if (captureButton) {
        if (centered) {
            captureButton.disabled = false;
            captureButton.classList.remove('opacity-50', 'cursor-not-allowed');
            captureButton.classList.add('opacity-100', 'cursor-pointer');
        } else {
            captureButton.disabled = true;
            captureButton.classList.add('opacity-50', 'cursor-not-allowed');
            captureButton.classList.remove('opacity-100', 'cursor-pointer');
        }
    }
}

function startAutoCapture() {
    // Limpar timer anterior se existir
    clearTimeout(autoCaptureTimer);
    
    // Se rosto está centralizado e usuário não clicou em 3 segundos, capturar automaticamente
    autoCaptureTimer = setTimeout(function() {
        if (isFaceCentered && !isCapturing) {
            startCaptureCountdown();
        }
    }, 3000); // 3 segundos
}

window.startCaptureCountdown = function startCaptureCountdown() {
    if (isCapturing || !isFaceCentered) return;
    
    isCapturing = true;
    clearTimeout(autoCaptureTimer); // Cancelar auto-capture se usuário clicou
    
    // Pausar o vídeo
    const video = document.getElementById('cameraPreview');
    if (video) {
        video.pause();
    }
    
    const captureButton = document.getElementById('captureButton');
    const captureIcon = document.getElementById('captureIcon');
    const captureCountdown = document.getElementById('captureCountdown');
    const captureButtonInner = document.getElementById('captureButtonInner');
    
    if (!captureButton || !captureIcon || !captureCountdown || !captureButtonInner) return;
    
    // Desabilitar botão durante contagem
    captureButton.disabled = true;
    
    // Ocultar ícone e mostrar contagem
    captureIcon.style.display = 'none';
    captureCountdown.style.display = 'block';
    captureButtonInner.style.backgroundColor = '#fbbf24'; // Amarelo durante contagem
    
    let count = 2;
    captureCountdown.textContent = count;
    
    captureCountdownInterval = setInterval(function() {
        count--;
        captureCountdown.textContent = count;
        
        if (count <= 0) {
            clearInterval(captureCountdownInterval);
            captureCountdown.style.display = 'none';
            
            // Capturar foto
            capturePhoto();
        }
    }, 1000);
};

let currentFacingMode = 'user'; // 'user' para frontal, 'environment' para traseira

window.switchCamera = async function switchCamera() {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        return;
    }
    
    // Alternar entre frontal e traseira
    currentFacingMode = currentFacingMode === 'user' ? 'environment' : 'user';
    
    // Parar stream atual
    if (cameraStream) {
        cameraStream.getTracks().forEach(track => track.stop());
    }
    
    try {
        // Obter novo stream com a câmera selecionada
        const stream = await navigator.mediaDevices.getUserMedia({ 
            video: { 
                facingMode: currentFacingMode,
                width: { ideal: 640 },
                height: { ideal: 640 }
            } 
        });
        
        cameraStream = stream;
        
        // Atualizar o vídeo
        const video = document.getElementById('cameraPreview');
        if (video) {
            video.srcObject = stream;
        }
    } catch (err) {
        console.error('Erro ao alternar câmera:', err);
        // Reverter para a anterior se falhar
        currentFacingMode = currentFacingMode === 'user' ? 'environment' : 'user';
    }
};

window.capturePhoto = async function capturePhoto() {
    const video = document.getElementById('cameraPreview');
    const canvas = document.getElementById('cameraCapture');
    
    if (!video || !canvas) {
        closeCamera();
        return;
    }
    
    // Garantir que o vídeo está pausado (travado) durante a captura
    if (!video.paused) {
        video.pause();
    }
    
    // Restaurar visual do botão
    const captureIcon = document.getElementById('captureIcon');
    const captureCountdown = document.getElementById('captureCountdown');
    const captureButtonInner = document.getElementById('captureButtonInner');
    
    if (captureIcon) captureIcon.style.display = 'block';
    if (captureCountdown) captureCountdown.style.display = 'none';
    if (captureButtonInner) captureButtonInner.style.backgroundColor = '#22c55e'; // Voltar para verde
    
    // Configurar canvas com o tamanho do vídeo
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    
    // Capturar frame do vídeo no canvas (vídeo está pausado/travado)
    const ctx = canvas.getContext('2d');
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
    
    // Converter canvas para Blob e salvar automaticamente
    canvas.toBlob(async function(blob) {
        if (blob) {
            // Criar um arquivo a partir do Blob
            const file = new File([blob], 'camera-photo.jpg', { type: 'image/jpeg' });
            
            // Fechar câmera imediatamente
            closeCamera();
            
            // Mostrar loading
            const avatarDisplay = document.getElementById('profileAvatarDisplay');
            if (avatarDisplay) {
                avatarDisplay.innerHTML = `
                    <div class="w-full h-full flex items-center justify-center bg-green-100">
                        <div class="text-center">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-green-600 mx-auto mb-2"></div>
                            <p class="text-xs text-green-700">Salvando...</p>
                        </div>
                    </div>
                `;
            }
            
            // Salvar automaticamente
            try {
                const formData = new FormData();
                formData.append('action', 'update_profile');
                formData.append('profile_photo', file);
                
                const resp = await fetch('./api/actions.php', { method: 'POST', body: formData });
                const result = await resp.json();
                
                if (result.success) {
                    // Exibir preview da foto salva
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const avatarDisplay = document.getElementById('profileAvatarDisplay');
                        const avatarImg = document.getElementById('profileAvatarImg');
                        const avatarIcon = document.getElementById('profileAvatarIcon');
                        
                        if (avatarDisplay) {
                            if (avatarImg) {
                                avatarImg.src = e.target.result;
                                if (avatarIcon) avatarIcon.style.display = 'none';
                            } else {
                                const img = document.createElement('img');
                                img.id = 'profileAvatarImg';
                                img.src = e.target.result;
                                img.alt = 'Foto do perfil';
                                img.className = 'w-full h-full object-cover';
                                avatarDisplay.innerHTML = '';
                                avatarDisplay.appendChild(img);
                            }
                        }
                        
                        // Atualizar foto no servidor se retornou URL
                        if (result.data && result.data.profile_photo) {
                            if (avatarImg) {
                                avatarImg.src = result.data.profile_photo + '?t=' + Date.now();
                            }
                        }
                    };
                    reader.readAsDataURL(blob);
                    
                    // Resetar estado de captura
                    isCapturing = false;
                    
                    // Atualizar foto no DOM com timestamp para evitar cache
                    if (result.data && result.data.profile_photo) {
                        const avatarDisplay = document.getElementById('profileAvatarDisplay');
                        const avatarIcon = document.getElementById('profileAvatarIcon');
                        let avatarImg = document.getElementById('profileAvatarImg');
                        
                        if (!avatarDisplay) return;
                        
                        // Construir URL da foto com timestamp
                        const photoUrl = result.data.profile_photo + '?t=' + Date.now();
                        
                        if (avatarImg) {
                            // Atualizar imagem existente
                            avatarImg.src = photoUrl;
                            avatarImg.style.display = 'block';
                            avatarImg.onerror = function() {
                                this.style.display = 'none';
                                if (avatarIcon) avatarIcon.style.display = 'block';
                            };
                            if (avatarIcon) avatarIcon.style.display = 'none';
                        } else {
                            // Criar imagem se não existir
                            avatarDisplay.innerHTML = '';
                            avatarImg = document.createElement('img');
                            avatarImg.id = 'profileAvatarImg';
                            avatarImg.src = photoUrl;
                            avatarImg.alt = 'Foto do perfil';
                            avatarImg.className = 'w-full h-full object-cover';
                            avatarImg.onerror = function() {
                                avatarDisplay.innerHTML = `
                                    <svg class="w-12 h-12 text-green-600" fill="currentColor" viewBox="0 0 24 24" id="profileAvatarIcon">
                                        <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                                    </svg>
                                `;
                            };
                            avatarDisplay.appendChild(avatarImg);
                        }
                        
                        // Atualizar foto no modal de perfil
                        const profileAvatarImg = document.getElementById('profileAvatarImg');
                        if (profileAvatarImg && result.data.profile_photo) {
                            profileAvatarImg.src = result.data.profile_photo + '?t=' + Date.now();
                            profileAvatarImg.style.display = 'block';
                            const profileAvatarIcon = document.getElementById('profileAvatarIcon');
                            if (profileAvatarIcon) {
                                profileAvatarIcon.style.display = 'none';
                            }
                        }
                        
                        // Atualizar foto no header também
                        const headerProfileImg = document.querySelector('#profileButton img');
                        if (headerProfileImg && result.data.profile_photo) {
                            headerProfileImg.src = result.data.profile_photo + '?t=' + Date.now();
                            headerProfileImg.style.display = 'block';
                            const headerProfileIcon = headerProfileImg.nextElementSibling;
                            if (headerProfileIcon && headerProfileIcon.tagName === 'svg') {
                                headerProfileIcon.style.display = 'none';
                            }
                        }
                    }
                    
                    showSuccessModal('Foto salva com sucesso!');
                } else {
                    // Resetar estado de captura em caso de erro
                    isCapturing = false;
                    
                    showErrorModal('Erro ao salvar foto: ' + (result.error || 'Erro desconhecido'));
                    // Restaurar ícone padrão em caso de erro
                    if (avatarDisplay) {
                        avatarDisplay.innerHTML = `
                            <svg class="w-12 h-12 text-green-600" fill="currentColor" viewBox="0 0 24 24" id="profileAvatarIcon">
                                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                            </svg>
                        `;
                    }
                }
            } catch (err) {
                console.error('Erro ao salvar foto:', err);
                // Resetar estado de captura em caso de erro
                isCapturing = false;
                
                showErrorModal('Erro ao salvar foto. Tente novamente.');
                // Restaurar ícone padrão em caso de erro
                if (avatarDisplay) {
                    avatarDisplay.innerHTML = `
                        <svg class="w-12 h-12 text-green-600" fill="currentColor" viewBox="0 0 24 24" id="profileAvatarIcon">
                            <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                        </svg>
                    `;
                }
            }
        }
    }, 'image/jpeg', 0.9); // Qualidade 90%
};

window.handleProfilePhotoUpload = async function handleProfilePhotoUpload(event) {
    const file = event.target.files[0];
    if (!file) return;
    
    // Validar tipo de arquivo
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    if (!allowedTypes.includes(file.type)) {
        alert('Tipo de arquivo não permitido. Use JPG, PNG ou GIF.');
        event.target.value = '';
        return;
    }
    
    // Validar tamanho (5MB)
    if (file.size > 5 * 1024 * 1024) {
        alert('Arquivo muito grande. Tamanho máximo: 5MB.');
        event.target.value = '';
        return;
    }
    
    // Mostrar loading
    const avatarDisplay = document.getElementById('profileAvatarDisplay');
    if (avatarDisplay) {
        avatarDisplay.innerHTML = `
            <div class="w-full h-full flex items-center justify-center bg-green-100">
                <div class="text-center">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-green-600 mx-auto mb-2"></div>
                    <p class="text-xs text-green-700">Salvando...</p>
                </div>
            </div>
        `;
    }
    
    // Salvar automaticamente
    try {
        const formData = new FormData();
        formData.append('action', 'update_profile');
        formData.append('profile_photo', file);
        
        const resp = await fetch('./api/actions.php', { method: 'POST', body: formData });
        const result = await resp.json();
        
        if (result.success) {
                // Exibir preview da foto salva
                const reader = new FileReader();
                reader.onload = function(e) {
                    const avatarDisplay = document.getElementById('profileAvatarDisplay');
                    const avatarImg = document.getElementById('profileAvatarImg');
                    const avatarIcon = document.getElementById('profileAvatarIcon');
                    
                    if (avatarDisplay) {
                        if (avatarImg) {
                            avatarImg.src = e.target.result;
                            if (avatarIcon) avatarIcon.style.display = 'none';
                        } else {
                            const img = document.createElement('img');
                            img.id = 'profileAvatarImg';
                            img.src = e.target.result;
                            img.alt = 'Foto do perfil';
                            img.className = 'w-full h-full object-cover';
                            avatarDisplay.innerHTML = '';
                            avatarDisplay.appendChild(img);
                        }
                    }
                    
                    // NOVO: Atualizar foto no header imediatamente (preview local)
                    const headerPhotoImg = document.getElementById('headerProfilePhoto');
                    const headerPhotoIcon = document.getElementById('headerProfilePhotoIcon');
                    
                    if (headerPhotoImg) {
                        headerPhotoImg.src = e.target.result;
                        headerPhotoImg.style.display = 'block';
                        if (headerPhotoIcon) headerPhotoIcon.style.display = 'none';
                    } else if (headerPhotoIcon && headerPhotoIcon.parentElement) {
                        // Criar imagem no header se não existir
                        const newImg = document.createElement('img');
                        newImg.id = 'headerProfilePhoto';
                        newImg.src = e.target.result;
                        newImg.alt = 'Foto do perfil';
                        newImg.className = 'w-full h-full object-cover';
                        newImg.onerror = function() {
                            this.style.display = 'none';
                            headerPhotoIcon.style.display = 'flex';
                        };
                        headerPhotoIcon.style.display = 'none';
                        headerPhotoIcon.parentElement.insertBefore(newImg, headerPhotoIcon);
                    }
                    
                    // Atualizar foto no servidor se retornou URL
                    if (result.data && result.data.profile_photo) {
                        const photoUrl = result.data.profile_photo + '?t=' + Date.now();
                        if (avatarImg) {
                            avatarImg.src = photoUrl;
                        }
                        // Atualizar header com URL do servidor
                        if (headerPhotoImg) {
                            headerPhotoImg.src = photoUrl;
                        }
                    }
                };
                reader.readAsDataURL(file);
            
            // Atualizar foto no DOM com timestamp para evitar cache
            if (result.data && result.data.profile_photo) {
                const avatarDisplay = document.getElementById('profileAvatarDisplay');
                const avatarIcon = document.getElementById('profileAvatarIcon');
                let avatarImg = document.getElementById('profileAvatarImg');
                
                if (!avatarDisplay) return;
                        
                // Construir URL da foto com timestamp
                const photoUrl = result.data.profile_photo + '?t=' + Date.now();
                        
                if (avatarImg) {
                    // Atualizar imagem existente
                    avatarImg.src = photoUrl;
                    avatarImg.style.display = 'block';
                    avatarImg.onerror = function() {
                        this.style.display = 'none';
                        if (avatarIcon) avatarIcon.style.display = 'block';
                    };
                    if (avatarIcon) avatarIcon.style.display = 'none';
                } else {
                    // Criar imagem se não existir
                    avatarDisplay.innerHTML = '';
                    avatarImg = document.createElement('img');
                    avatarImg.id = 'profileAvatarImg';
                    avatarImg.src = photoUrl;
                    avatarImg.alt = 'Foto do perfil';
                    avatarImg.className = 'w-full h-full object-cover';
                    avatarImg.onerror = function() {
                        avatarDisplay.innerHTML = `
                            <svg class="w-12 h-12 text-green-600" fill="currentColor" viewBox="0 0 24 24" id="profileAvatarIcon">
                                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                            </svg>
                        `;
                    };
                    avatarDisplay.appendChild(avatarImg);
                }
                
                // Atualizar foto no modal de perfil
                const profileAvatarImg = document.getElementById('profileAvatarImg');
                if (profileAvatarImg && result.data.profile_photo) {
                    const photoUrl = result.data.profile_photo + '?t=' + Date.now();
                    profileAvatarImg.src = photoUrl;
                    profileAvatarImg.style.display = 'block';
                    const profileAvatarIcon = document.getElementById('profileAvatarIcon');
                    if (profileAvatarIcon) {
                        profileAvatarIcon.style.display = 'none';
                    }
                }
                
                // Atualizar foto no header em tempo real
                const headerPhotoImg = document.getElementById('headerProfilePhoto');
                const headerPhotoIcon = document.getElementById('headerProfilePhotoIcon');
                
                if (result.data && result.data.profile_photo) {
                    const photoUrl = result.data.profile_photo + '?t=' + Date.now();
                    
                    if (headerPhotoImg) {
                        // Atualizar imagem existente no header
                        headerPhotoImg.src = photoUrl;
                        headerPhotoImg.style.display = 'block';
                        headerPhotoImg.onerror = function() {
                            this.style.display = 'none';
                            if (headerPhotoIcon) headerPhotoIcon.style.display = 'flex';
                        };
                        if (headerPhotoIcon) headerPhotoIcon.style.display = 'none';
                    } else if (headerPhotoIcon && headerPhotoIcon.parentElement) {
                        // Criar imagem no header se não existir
                        const newImg = document.createElement('img');
                        newImg.id = 'headerProfilePhoto';
                        newImg.src = photoUrl;
                        newImg.alt = 'Foto do perfil';
                        newImg.className = 'w-full h-full object-cover';
                        newImg.onerror = function() {
                            this.style.display = 'none';
                            headerPhotoIcon.style.display = 'flex';
                        };
                        headerPhotoIcon.style.display = 'none';
                        headerPhotoIcon.parentElement.insertBefore(newImg, headerPhotoIcon);
                    }
                }
            }
            
            showSuccessModal('Foto salva com sucesso!');
            
            // Limpar input
            event.target.value = '';
        } else {
            showErrorModal('Erro ao salvar foto: ' + (result.error || 'Erro desconhecido'));
            // Restaurar ícone padrão em caso de erro
            if (avatarDisplay) {
                avatarDisplay.innerHTML = `
                    <svg class="w-12 h-12 text-green-600" fill="currentColor" viewBox="0 0 24 24" id="profileAvatarIcon">
                        <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                    </svg>
                `;
            }
            event.target.value = '';
        }
    } catch (err) {
        console.error('Erro ao salvar foto:', err);
        showErrorModal('Erro ao salvar foto. Tente novamente.');
        // Restaurar ícone padrão em caso de erro
        if (avatarDisplay) {
            avatarDisplay.innerHTML = `
                <svg class="w-12 h-12 text-green-600" fill="currentColor" viewBox="0 0 24 24" id="profileAvatarIcon">
                    <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                </svg>
            `;
        }
        event.target.value = '';
    }
};

window.saveProfile = async function saveProfile() {
    console.log('💾 Função saveProfile chamada!');
    
    const name = document.getElementById('profileName')?.value;
    const phone = document.getElementById('profilePhone')?.value;
    const farmName = document.getElementById('farmName')?.value;
    const farmPhone = document.getElementById('farmPhone')?.value;
    const farmCNPJ = document.getElementById('farmCNPJ')?.value;
    const farmAddress = document.getElementById('farmAddress')?.value;
    const pushNotifications = document.getElementById('pushNotifications')?.checked;
    const newPassword = document.getElementById('profileNewPassword')?.value;
    const confirmPassword = document.getElementById('profileConfirmPassword')?.value;
    
    console.log('📝 Dados coletados:', { name, phone, farmName, farmPhone, farmCNPJ, farmAddress });
    
    // Validar senhas se fornecidas (SIMPLIFICADO - SEM OTP)
    if (newPassword || confirmPassword) {
        // Se preencheu um campo, o outro também deve ser preenchido
        if (newPassword && !confirmPassword) {
            showErrorModal('Por favor, confirme a nova senha');
            return;
        }
        if (confirmPassword && !newPassword) {
            showErrorModal('Por favor, digite a nova senha');
            return;
        }
        // Se ambos foram preenchidos, validar
        if (newPassword && confirmPassword) {
            if (newPassword !== confirmPassword) {
                showErrorModal('As senhas não coincidem');
                return;
            }
            if (newPassword.length < 6) {
                showErrorModal('A senha deve ter pelo menos 6 caracteres');
                return;
            }
            // A senha será enviada junto com os outros dados
            // Não precisa de OTP ou Google vinculado
        }
    }
    
    try {
        console.log('🚀 Enviando dados para API...');
        const formData = new FormData();
        formData.append('action', 'update_profile');
        formData.append('name', name);
        // Remover formatação de telefones e CNPJ antes de enviar para a API (apenas números)
        const cleanPhone = phone ? phone.replace(/\D/g, '') : '';
        const cleanFarmPhone = farmPhone ? farmPhone.replace(/\D/g, '') : '';
        const cleanCNPJ = farmCNPJ ? farmCNPJ.replace(/\D/g, '') : '';
        
        formData.append('phone', cleanPhone);
        formData.append('farm_name', farmName);
        formData.append('farm_phone', cleanFarmPhone);
        formData.append('farm_cnpj', cleanCNPJ);
        formData.append('farm_address', farmAddress);
        formData.append('push_notifications', pushNotifications ? '1' : '0');
        if (newPassword) formData.append('password', newPassword);
        if (profilePhotoFile) formData.append('profile_photo', profilePhotoFile);
        
        const resp = await fetch('./api/actions.php', { method: 'POST', body: formData });
        
        if (!resp.ok) {
            throw new Error('Erro na resposta do servidor: ' + resp.status);
        }
        
        const result = await resp.json();
        
        console.log('📥 Resposta da API:', result);
        
        if (result.success) {
            console.log('✅ Salvamento bem-sucedido! Atualizando página...');
            // Usar dados retornados pela API para atualizar a página (mais confiável que os valores do formulário)
            const savedUserData = result.data?.user || {};
            const savedFarmData = result.data?.farm || {};
            
            // Dados do usuário (usar dados retornados ou fallback para valores do formulário)
            const savedName = savedUserData.name || name || '';
            const savedPhone = savedUserData.phone || phone || '';
            
            // Dados da fazenda (usar dados retornados ou fallback para valores do formulário)
            const savedFarmName = savedFarmData.name || farmName || '';
            const savedFarmPhone = savedFarmData.phone || farmPhone || '';
            const savedFarmCNPJ = savedFarmData.cnpj || farmCNPJ || '';
            const savedFarmAddress = savedFarmData.address || farmAddress || '';
            
            // Atualizar valores originais com os dados salvos
            profileOriginalValues = {
                name: savedName,
                phone: savedPhone,
                farmName: savedFarmName,
                farmPhone: savedFarmPhone,
                farmCNPJ: savedFarmCNPJ,
                farmAddress: savedFarmAddress,
                pushNotifications: pushNotifications || false,
                newPassword: '',
                confirmPassword: ''
            };
            
            // Atualizar os inputs com os dados salvos (em tempo real)
            const nameEl = document.getElementById('profileName');
            const phoneEl = document.getElementById('profilePhone');
            const farmNameEl = document.getElementById('farmName');
            const farmPhoneEl = document.getElementById('farmPhone');
            const farmCNPJEl = document.getElementById('farmCNPJ');
            const farmAddressEl = document.getElementById('farmAddress');
            
            // Função helper para atualizar valores (definida antes de usar)
            const updateInputValue = (element, newValue) => {
                if (!element) return;
                const valueToSet = newValue || '';
                
                // Para inputs, atualizar propriedade, atributo E defaultValue
                if (element.tagName === 'INPUT') {
                    // Atualizar em múltiplas etapas para garantir
                    element.value = valueToSet;
                    element.defaultValue = valueToSet;
                    element.setAttribute('value', valueToSet);
                    
                    // Forçar atualização visual
                    element.blur();
                    element.focus();
                    element.blur();
                    
                    // Log para debug
                    console.log(`  ↳ ${element.id}: "${valueToSet}" (value=${element.value}, attr=${element.getAttribute('value')}, default=${element.defaultValue})`);
                } 
                // Para textarea, apenas propriedade value
                else if (element.tagName === 'TEXTAREA') {
                    element.value = valueToSet;
                    console.log(`  ↳ ${element.id}: "${valueToSet}"`);
                }
            };
            
            // ATUALIZAR OS VALORES NOS INPUTS ANTES DE DESABILITAR
            
            console.log('📋 Atualizando valores nos inputs...');
            console.log('📋 Dados recebidos da API:', {
                user: savedUserData,
                farm: savedFarmData
            });
            
            // Atualizar TODOS os campos (formatar telefones e CNPJ)
            updateInputValue(nameEl, savedName);
            updateInputValue(phoneEl, formatPhone(savedPhone || ''));
            updateInputValue(farmNameEl, savedFarmName);
            updateInputValue(farmPhoneEl, formatPhone(savedFarmPhone || ''));
            updateInputValue(farmCNPJEl, formatCNPJ(savedFarmCNPJ || ''));
            updateInputValue(farmAddressEl, savedFarmAddress);
            
            // Garantir que os valores estão visíveis ANTES de desabilitar
            // Usar getComputedStyle para forçar renderização
            if (nameEl) {
                const computed = window.getComputedStyle(nameEl);
                // Forçar reflow
                nameEl.offsetHeight;
            }
            
            console.log('📋 Valores após atualização:', {
                name: nameEl?.value,
                phone: phoneEl?.value,
                farmName: farmNameEl?.value,
                farmPhone: farmPhoneEl?.value,
                farmCNPJ: farmCNPJEl?.value,
                farmAddress: farmAddressEl?.value
            });
            
            // Limpar senhas e foto após salvar com sucesso
            const newPasswordEl = document.getElementById('profileNewPassword');
            const confirmPasswordEl = document.getElementById('profileConfirmPassword');
            
            if (newPasswordEl) {
                newPasswordEl.value = '';
                newPasswordEl.setAttribute('value', '');
                console.log('✅ Campo de nova senha limpo');
            }
            if (confirmPasswordEl) {
                confirmPasswordEl.value = '';
                confirmPasswordEl.setAttribute('value', '');
                console.log('✅ Campo de confirmação de senha limpo');
            }
            
            // Atualizar valores originais para não considerar senhas como mudança
            profileOriginalValues.newPassword = '';
            profileOriginalValues.confirmPassword = '';
            
            if (document.getElementById('profilePhotoInput')) document.getElementById('profilePhotoInput').value = '';
            profilePhotoFile = null;
            
            // Atualizar foto se foi retornada pela API
            if (savedUserData.profile_photo) {
                const avatarDisplay = document.getElementById('profileAvatarDisplay');
                const avatarImg = document.getElementById('profileAvatarImg');
                const avatarIcon = document.getElementById('profileAvatarIcon');
                
                if (avatarImg) {
                    avatarImg.src = savedUserData.profile_photo + '?t=' + Date.now();
                    avatarImg.style.display = 'block';
                }
                if (avatarIcon) {
                    avatarIcon.style.display = 'none';
                }
                
                // Atualizar foto no header também
                const headerProfileImg = document.querySelector('#profileButton img');
                if (headerProfileImg) {
                    headerProfileImg.src = savedUserData.profile_photo + '?t=' + Date.now();
                    headerProfileImg.style.display = 'block';
                    const headerProfileIcon = headerProfileImg.nextElementSibling;
                    if (headerProfileIcon && headerProfileIcon.tagName === 'svg') {
                        headerProfileIcon.style.display = 'none';
                    }
                }
            }
            
            // IMPORTANTE: Sair do modo edição APÓS atualizar valores
            // Isso garante que updateProfileEditMode() não restaure valores antigos
            profileEditMode = false;
            updateProfileEditMode();
            
            // Atualizar TODOS os lugares onde o nome aparece (header, etc) - EM TEMPO REAL
            
            // 1. Atualizar "Bem-vindo, [nome]!" no dashboard
            const managerWelcome = document.getElementById('managerWelcome');
            if (managerWelcome) {
                managerWelcome.textContent = savedName;
                console.log('✅ Nome atualizado no managerWelcome:', savedName);
            }
            
            // 2. Atualizar nome no header do perfil (ao lado da foto) - IMPORTANTE!
            const headerProfileName = document.getElementById('headerProfileName');
            if (headerProfileName) {
                headerProfileName.textContent = savedName;
                console.log('✅ Nome atualizado no headerProfileName:', savedName);
            } else {
                // Fallback: procurar pelo seletor
                const headerProfileSection = document.querySelector('.text-right');
                if (headerProfileSection) {
                    const nameParagraph = headerProfileSection.querySelector('p.text-sm.font-medium');
                    if (nameParagraph) {
                        nameParagraph.textContent = savedName;
                        console.log('✅ Nome atualizado no header (fallback):', savedName);
                    }
                }
            }
            
            // 3. Atualizar qualquer elemento com data-profile-name
            const profileNameElements = document.querySelectorAll('[data-profile-name]');
            profileNameElements.forEach(el => {
                el.textContent = savedName;
            });
            
            // 4. Procurar por outros lugares onde o nome pode aparecer
            // Buscar elementos que contêm o nome antigo
            const oldName = profileOriginalValues?.name || '';
            if (oldName && oldName !== savedName) {
                // Atualizar todos os elementos de texto que contêm apenas o nome antigo
                const textElements = document.querySelectorAll('p, span, h1, h2, h3, h4, h5, h6, div');
                textElements.forEach(el => {
                    // Verificar se o elemento contém apenas o nome (sem outros textos)
                    const text = el.textContent.trim();
                    if (text === oldName) {
                        el.textContent = savedName;
                        console.log(`✅ Nome atualizado em elemento:`, el.tagName, el.className);
                    }
                });
            }
            
            // Forçar atualização visual novamente após desabilitar usando requestAnimationFrame
            // Isso garante que o browser tenha processado o disabled antes de atualizar
            requestAnimationFrame(() => {
                setTimeout(() => {
                    console.log('🔄 Forçando atualização visual após desabilitar...');
                    
                    // Usar a mesma função helper para inputs (formatar telefones e CNPJ)
                    updateInputValue(nameEl, savedName);
                    updateInputValue(phoneEl, formatPhone(savedPhone || ''));
                    updateInputValue(farmNameEl, savedFarmName);
                    updateInputValue(farmPhoneEl, formatPhone(savedFarmPhone || ''));
                    updateInputValue(farmCNPJEl, formatCNPJ(savedFarmCNPJ || ''));
                    updateInputValue(farmAddressEl, savedFarmAddress);
                    
                    // ATUALIZAR NOME NO HEADER NOVAMENTE (para garantir)
                    const headerProfileNameEl = document.getElementById('headerProfileName');
                    if (headerProfileNameEl) {
                        headerProfileNameEl.textContent = savedName;
                        console.log('🔄 Nome no header atualizado novamente:', savedName);
                    }
                    
                    const managerWelcomeEl = document.getElementById('managerWelcome');
                    if (managerWelcomeEl) {
                        managerWelcomeEl.textContent = savedName;
                        console.log('🔄 Nome no managerWelcome atualizado novamente:', savedName);
                    }
                    
                    // Verificar valores finais
                    const finalValues = {
                        name: nameEl?.value,
                        phone: phoneEl?.value,
                        farmName: farmNameEl?.value,
                        farmPhone: farmPhoneEl?.value,
                        farmCNPJ: farmCNPJEl?.value,
                        farmAddress: farmAddressEl?.value,
                        headerName: headerProfileNameEl?.textContent,
                        welcomeName: managerWelcomeEl?.textContent
                    };
                    console.log('🔄 Valores finais após forçar atualização:', finalValues);
                    
                    // Se ainda não estiver correto, tentar mais uma vez
                    if (nameEl && nameEl.value !== savedName) {
                        console.warn('⚠️ Nome ainda não atualizado, tentando novamente...');
                        nameEl.value = savedName;
                        nameEl.setAttribute('value', savedName);
                        nameEl.defaultValue = savedName;
                    }
                    
                    if (headerProfileNameEl && headerProfileNameEl.textContent !== savedName) {
                        console.warn('⚠️ Nome no header ainda não atualizado, tentando novamente...');
                        headerProfileNameEl.textContent = savedName;
                    }
                }, 50);
            });
            
            // Garantir atualização do header também após mais tempo (caso o modal tenha bloqueado)
            setTimeout(() => {
                const headerProfileNameEl = document.getElementById('headerProfileName');
                if (headerProfileNameEl && headerProfileNameEl.textContent !== savedName) {
                    headerProfileNameEl.textContent = savedName;
                    console.log('⏰ Nome no header atualizado após delay adicional');
                }
                
                const managerWelcomeEl = document.getElementById('managerWelcome');
                if (managerWelcomeEl && managerWelcomeEl.textContent !== savedName) {
                    managerWelcomeEl.textContent = savedName;
                    console.log('⏰ Nome no managerWelcome atualizado após delay adicional');
                }
            }, 500);
            
            // Mostrar modal de sucesso com mensagem personalizada
            let successMessage = 'Perfil salvo com sucesso!';
            if (newPassword && newPassword.trim() !== '') {
                successMessage = 'Perfil e senha atualizados com sucesso!';
            }
            showSuccessModal(successMessage);
        } else {
            showErrorModal('Erro ao salvar perfil: ' + (result.error || 'Erro desconhecido'));
        }
    } catch (err) {
        console.error('Falha ao salvar perfil:', err);
        showErrorModal('Erro ao salvar perfil: ' + err.message);
    }
};

// ==================== MODAL DE SUCESSO ====================
function showSuccessModal(message) {
    const existingModal = document.getElementById('successModalPhoto');
    if (existingModal) {
        existingModal.remove();
    }
    
    const modal = document.createElement('div');
    modal.id = 'successModalPhoto';
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[100] p-4';
    
    modal.innerHTML = `
        <div class="bg-white rounded-2xl p-8 max-w-md w-full mx-4 transform transition-all duration-300 scale-95 opacity-0" id="successModalPhotoContent">
            <!-- Header com gradiente -->
            <div class="text-center mb-6">
                <div class="w-20 h-20 bg-gradient-to-br from-green-500 to-green-600 rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-2">Sucesso!</h3>
                <div class="w-16 h-1 bg-gradient-to-r from-green-500 to-green-600 rounded-full mx-auto"></div>
            </div>
            
            <!-- Mensagem -->
            <div class="text-center mb-8">
                <p class="text-gray-600 leading-relaxed">${message}</p>
            </div>
            
            <!-- Botão -->
            <div class="text-center">
                <button onclick="closeSuccessModalPhoto()" class="bg-gradient-to-r from-green-500 to-green-600 text-white px-8 py-3 rounded-xl font-semibold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200">
                    Entendi
                </button>
            </div>
        </div>
    `;
    
    // Adicionar evento de clique no fundo para fechar
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeSuccessModalPhoto();
        }
    });
    
    document.body.appendChild(modal);
    
    // Animar entrada
    requestAnimationFrame(() => {
        const content = document.getElementById('successModalPhotoContent');
        if (content) {
            content.style.transform = 'scale(1)';
            content.style.opacity = '1';
        }
    });
    
    // Auto-fechar após 3 segundos
    setTimeout(() => {
        closeSuccessModalPhoto();
    }, 3000);
}

function closeSuccessModalPhoto() {
    const modal = document.getElementById('successModalPhoto');
    if (modal) {
        const content = document.getElementById('successModalPhotoContent');
        if (content) {
            content.style.transform = 'scale(0.95)';
            content.style.opacity = '0';
        }
        setTimeout(() => {
            modal.remove();
        }, 300);
    }
}

// Modal de conta Google vinculada
function showGoogleLinkedModal(message) {
    const existingModal = document.getElementById('googleLinkedModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    const modal = document.createElement('div');
    modal.id = 'googleLinkedModal';
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center z-[100] p-4';
    
    modal.innerHTML = `
        <div class="bg-white rounded-2xl p-8 max-w-md w-full mx-4 transform transition-all duration-300 scale-95 opacity-0 shadow-2xl" id="googleLinkedModalContent">
            <!-- Header com ícone do Google -->
            <div class="text-center mb-6">
                <div class="w-20 h-20 bg-gradient-to-br from-green-500 to-green-600 rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-2">Conta Vinculada!</h3>
                <div class="w-16 h-1 bg-gradient-to-r from-green-500 to-green-600 rounded-full mx-auto"></div>
            </div>
            
            <!-- Mensagem -->
            <div class="text-center mb-8">
                <p class="text-gray-600 leading-relaxed text-base">${message}</p>
            </div>
            
            <!-- Informação adicional -->
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                <p class="text-sm text-green-800">
                    <strong>✨ Benefícios:</strong> Agora você pode receber códigos OTP por e-mail e usar recursos de segurança avançados.
                </p>
            </div>
            
            <!-- Botão -->
            <div class="text-center">
                <button onclick="closeGoogleLinkedModal()" class="bg-gradient-to-r from-green-500 to-green-600 text-white px-8 py-3 rounded-xl font-semibold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200 w-full">
                    Entendi
                </button>
            </div>
        </div>
    `;
    
    // Adicionar evento de clique no fundo para fechar
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeGoogleLinkedModal();
        }
    });
    
    document.body.appendChild(modal);
    
    // Animar entrada
    requestAnimationFrame(() => {
        const content = document.getElementById('googleLinkedModalContent');
        if (content) {
            content.style.transform = 'scale(1)';
            content.style.opacity = '1';
        }
    });
}

function closeGoogleLinkedModal() {
    const modal = document.getElementById('googleLinkedModal');
    if (modal) {
        const content = document.getElementById('googleLinkedModalContent');
        if (content) {
            content.style.transform = 'scale(0.95)';
            content.style.opacity = '0';
        }
        setTimeout(() => {
            modal.remove();
        }, 300);
    }
}

// ==================== MODAL DE ERRO ====================
function showErrorModal(message) {
    const existingModal = document.getElementById('errorModalPhoto');
    if (existingModal) {
        existingModal.remove();
    }
    
    const modal = document.createElement('div');
    modal.id = 'errorModalPhoto';
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[100] p-4';
    
    modal.innerHTML = `
        <div class="bg-white rounded-2xl p-8 max-w-md w-full mx-4 transform transition-all duration-300 scale-95 opacity-0" id="errorModalPhotoContent">
            <!-- Header com gradiente -->
            <div class="text-center mb-6">
                <div class="w-20 h-20 bg-gradient-to-br from-red-500 to-red-600 rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-2">Erro</h3>
                <div class="w-16 h-1 bg-gradient-to-r from-red-500 to-red-600 rounded-full mx-auto"></div>
            </div>
            
            <!-- Mensagem -->
            <div class="text-center mb-8">
                <p class="text-gray-600 leading-relaxed">${message}</p>
            </div>
            
            <!-- Botão -->
            <div class="text-center">
                <button onclick="closeErrorModalPhoto()" class="bg-gradient-to-r from-red-500 to-red-600 text-white px-8 py-3 rounded-xl font-semibold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200">
                    Fechar
                </button>
            </div>
        </div>
    `;
    
    // Adicionar evento de clique no fundo para fechar
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeErrorModalPhoto();
        }
    });
    
    document.body.appendChild(modal);
    
    // Animar entrada
    requestAnimationFrame(() => {
        const content = document.getElementById('errorModalPhotoContent');
        if (content) {
            content.style.transform = 'scale(1)';
            content.style.opacity = '1';
        }
    });
    
    // Auto-fechar após 5 segundos
    setTimeout(() => {
        closeErrorModalPhoto();
    }, 5000);
}

function closeErrorModalPhoto() {
    const modal = document.getElementById('errorModalPhoto');
    if (modal) {
        const content = document.getElementById('errorModalPhotoContent');
        if (content) {
            content.style.transform = 'scale(0.95)';
            content.style.opacity = '0';
        }
        setTimeout(() => {
            modal.remove();
        }, 300);
    }
}

window.closeSuccessModalPhoto = closeSuccessModalPhoto;
window.closeErrorModalPhoto = closeErrorModalPhoto;
window.showGoogleLinkedModal = showGoogleLinkedModal;
window.closeGoogleLinkedModal = closeGoogleLinkedModal;

// ==================== GERENCIAR DISPOSITIVOS / SESSÕES ====================
async function registerCurrentSession() {
    try {
        // Primeiro tentar obter IP público via API externa (para ambientes locais)
        let publicIP = null;
        try {
            const ipResp = await fetch('https://api.ipify.org?format=json', {
                method: 'GET',
                timeout: 3000
            }).catch(() => null);
            
            if (ipResp && ipResp.ok) {
                const ipData = await ipResp.json();
                publicIP = ipData.ip;
            }
        } catch (e) {
            // Ignorar erro, usar IP do servidor
        }
        
        const formData = new FormData();
        formData.append('action', 'register_session');
        if (publicIP) {
            formData.append('public_ip', publicIP);
        }
        
        await fetch('./api/actions.php', {
            method: 'POST',
            body: formData
        });
    } catch (error) {
        console.error('Erro ao registrar sessão:', error);
    }
}

async function updateSessionActivity() {
    try {
        // Atualizar última atividade da sessão atual
        await registerCurrentSession();
    } catch (error) {
        console.error('Erro ao atualizar atividade da sessão:', error);
    }
}

window.openDevicesModal = async function openDevicesModal() {
    const modal = document.getElementById('devicesModal');
    if (!modal) return;
    
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    
    // Carregar lista de dispositivos/sessões
    await loadDevicesList();
};

window.closeDevicesModal = function closeDevicesModal() {
    const modal = document.getElementById('devicesModal');
    if (!modal) return;
    
    modal.classList.add('hidden');
    document.body.style.overflow = 'auto';
};

async function loadDevicesList() {
    const devicesList = document.getElementById('devicesList');
    if (!devicesList) return;
    
    try {
        // Mostrar loading
        devicesList.innerHTML = `
            <div class="text-center text-gray-500 py-8">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-green-600 mx-auto mb-4"></div>
                <p>Carregando dispositivos...</p>
            </div>
        `;
        
        // Buscar sessões ativas da API
        const resp = await fetch('./api/actions.php?action=get_active_sessions', {
            method: 'GET',
            credentials: 'same-origin'
        });
        
        const result = await resp.json();
        
        if (!result.success) {
            throw new Error(result.error || 'Erro ao carregar sessões');
        }
        
        const devices = result.sessions || [];
        
        if (devices.length === 0) {
            devicesList.innerHTML = `
                <div class="text-center text-gray-500 py-8">
                    <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    <p class="text-gray-600">Nenhum dispositivo conectado</p>
                </div>
            `;
            return;
        }
        
        devicesList.innerHTML = devices.map(device => {
            // Determinar ícone baseado no tipo de dispositivo
            const isMobile = device.device_type === 'mobile';
            const deviceIcon = isMobile ? `
                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
            ` : `
                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
                </svg>
            `;
            
            return `
                <div class="p-4 border border-gray-200 rounded-xl bg-white hover:shadow-md transition-shadow ${device.current ? 'border-green-500 bg-green-50' : ''}">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                ${deviceIcon}
                                <div>
                                    <h4 class="text-sm font-semibold text-gray-900">${device.device || (isMobile ? 'Dispositivo Móvel' : 'Computador')}</h4>
                                    ${device.current ? '<span class="inline-block px-2 py-0.5 text-xs font-medium text-green-700 bg-green-100 rounded mt-1">Sessão Atual</span>' : ''}
                                </div>
                            </div>
                            <div class="space-y-1 text-xs text-gray-600 ml-8">
                                <p><span class="font-medium">Localização:</span> ${device.location || 'Não identificado'}</p>
                                <p><span class="font-medium">IP:</span> ${device.ip === '127.0.0.1' || device.ip === '::1' ? 'localhost (Ambiente Local)' : (device.ip || 'N/A')}</p>
                                <p><span class="font-medium">Última atividade:</span> ${formatDateTime(device.lastActive)}</p>
                            </div>
                        </div>
                        ${!device.current ? `
                            <button onclick="revokeDevice(${device.id})" class="px-3 py-1.5 text-sm font-medium text-red-600 hover:text-red-700 border border-red-600 rounded-lg hover:bg-red-50 transition-colors ml-4">
                                Encerrar
                            </button>
                        ` : ''}
                    </div>
                </div>
            `;
        }).join('');
        
    } catch (error) {
        console.error('Erro ao carregar dispositivos:', error);
        devicesList.innerHTML = `
            <div class="text-center text-red-500 py-8">
                <svg class="w-16 h-16 mx-auto mb-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-red-600">Erro ao carregar dispositivos</p>
                <button onclick="loadDevicesList()" class="mt-4 px-4 py-2 text-sm font-medium text-red-600 border border-red-600 rounded-lg hover:bg-red-50 transition-colors">
                    Tentar Novamente
                </button>
            </div>
        `;
    }
}

function formatDateTime(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleString('pt-BR', { 
        day: '2-digit', 
        month: '2-digit', 
        year: 'numeric',
        hour: '2-digit', 
        minute: '2-digit' 
    });
}

async function revokeDevice(deviceId) {
    // Mostrar modal de confirmação customizado
    const confirmed = await showConfirmModal('Encerrar Sessão', 'Tem certeza que deseja encerrar esta sessão? Esta ação não pode ser desfeita.');
    
    if (!confirmed) {
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('action', 'revoke_session');
        formData.append('device_id', deviceId);
        
        const resp = await fetch('./api/actions.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await resp.json();
        
        if (result.success) {
            showSuccessModal('Sessão encerrada com sucesso!');
            // Recarregar lista
            await loadDevicesList();
        } else {
            showErrorModal(result.error || 'Erro ao encerrar sessão');
        }
    } catch (error) {
        console.error('Erro ao encerrar sessão:', error);
        showErrorModal('Erro ao encerrar sessão. Tente novamente.');
    }
}

function showConfirmModal(title, message) {
    return new Promise((resolve) => {
        const modal = document.createElement('div');
        modal.id = 'confirmModalDevice';
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[100] p-4';
        
        modal.innerHTML = `
            <div class="bg-white rounded-2xl p-8 max-w-md w-full mx-4 transform transition-all duration-300 scale-95 opacity-0" id="confirmModalDeviceContent">
                <div class="text-center mb-6">
                    <div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">${title}</h3>
                    <p class="text-gray-600 leading-relaxed">${message}</p>
                </div>
                
                <div class="flex gap-3 justify-center">
                    <button onclick="closeConfirmModalDevice(false)" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                        Cancelar
                    </button>
                    <button onclick="closeConfirmModalDevice(true)" class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                        Encerrar
                    </button>
                </div>
            </div>
        `;
        
        // Adicionar evento de clique no fundo para fechar
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeConfirmModalDevice(false);
            }
        });
        
        document.body.appendChild(modal);
        
        // Animar entrada
        requestAnimationFrame(() => {
            const content = document.getElementById('confirmModalDeviceContent');
            if (content) {
                content.style.transform = 'scale(1)';
                content.style.opacity = '1';
            }
        });
        
        // Função para fechar modal
        window.closeConfirmModalDevice = function(result) {
            const modal = document.getElementById('confirmModalDevice');
            if (modal) {
                const content = document.getElementById('confirmModalDeviceContent');
                if (content) {
                    content.style.transform = 'scale(0.95)';
                    content.style.opacity = '0';
                }
                setTimeout(() => {
                    modal.remove();
                    delete window.closeConfirmModalDevice;
                    resolve(result);
                }, 300);
            } else {
                resolve(false);
            }
        };
    });
}

window.revokeDevice = revokeDevice;

// ==================== CONTROLE DE NOVILHAS ====================

// Carregar dashboard de novilhas quando o modal abrir
document.addEventListener('DOMContentLoaded', function() {
    // Observar quando o overlay de novilhas for aberto (substituiu o modal-heifers)
    const heiferOverlay = document.getElementById('heiferOverlay');
    if (heiferOverlay) {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    const isVisible = !heiferOverlay.classList.contains('hidden');
                    if (isVisible && typeof window.loadHeiferDashboard === 'function') {
                        window.loadHeiferDashboard();
                    }
                    if (isVisible && typeof window.loadHeifersTable === 'function') {
                        window.loadHeifersTable();
                    }
                }
            });
        });
        
        observer.observe(heiferOverlay, {
            attributes: true,
            attributeFilter: ['class']
        });
    }

    // Event listeners para formulários de novilhas
    const heiferCostForm = document.getElementById('heiferCostForm');
    if (heiferCostForm) {
        heiferCostForm.addEventListener('submit', handleHeiferCostSubmit);
        
        // Calcular total automaticamente
        const quantityInput = document.getElementById('heifer-cost-quantity');
        const unitPriceInput = document.getElementById('heifer-cost-unit-price');
        const totalInput = document.getElementById('heifer-cost-total');
        
        if (quantityInput && unitPriceInput && totalInput) {
            [quantityInput, unitPriceInput].forEach(input => {
                input.addEventListener('input', calculateHeiferCostTotal);
            });
        }

        // Atualizar tipos de alimento baseado na categoria
        const categorySelect = document.getElementById('heifer-cost-category');
        if (categorySelect) {
            categorySelect.addEventListener('change', updateHeiferFoodTypes);
        }
    }

    const heiferConsumptionForm = document.getElementById('heiferConsumptionForm');
    if (heiferConsumptionForm) {
        heiferConsumptionForm.addEventListener('submit', handleHeiferConsumptionSubmit);
    }

    // Busca de novilhas
    const heiferSearchInput = document.getElementById('heifer-search');
    if (heiferSearchInput) {
        heiferSearchInput.addEventListener('input', filterHeifersList);
    }
});

// Carregar dashboard de novilhas
async function loadHeiferDashboard() {
    try {
        const response = await fetch('api/heifer_management.php?action=get_dashboard');
        const data = await response.json();
        
        if (data.success && data.data) {
            const stats = data.data.statistics || {};
            
            // Atualizar cards de estatísticas
            document.getElementById('heifer-total-count').textContent = stats.total_heifers || 0;
            document.getElementById('heifer-total-cost').textContent = formatCurrency(stats.total_invested || 0);
            document.getElementById('heifer-avg-cost').textContent = formatCurrency(
                stats.total_heifers > 0 ? (stats.total_invested || 0) / stats.total_heifers : 0
            );
            
            // Calcular custo médio mensal (assumindo 26 meses = 780 dias)
            const avgMonthly = stats.total_heifers > 0 ? (stats.total_invested || 0) / (stats.total_heifers * 26) : 0;
            document.getElementById('heifer-avg-monthly').textContent = formatCurrency(avgMonthly);
            
            // Atualizar distribuição por fase
            updateHeiferPhasesStats(stats);
        }
    } catch (error) {
        console.error('Erro ao carregar dashboard de novilhas:', error);
    }
}

// Atualizar estatísticas de fases
function updateHeiferPhasesStats(stats) {
    const phasesContainer = document.getElementById('heifer-phases-stats');
    if (!phasesContainer) return;

    const phases = [
        { name: 'Aleitamento', count: stats.phase_aleitamento || 0, color: 'blue' },
        { name: 'Transição', count: stats.phase_transicao || 0, color: 'yellow' },
        { name: 'Recria Inicial', count: stats.phase_recria1 || 0, color: 'green' },
        { name: 'Recria Intermediária', count: stats.phase_recria2 || 0, color: 'purple' },
        { name: 'Crescimento', count: stats.phase_crescimento || 0, color: 'orange' },
        { name: 'Pré-parto', count: stats.phase_preparto || 0, color: 'pink' }
    ];

    phasesContainer.innerHTML = phases.map(phase => `
        <div class="bg-white rounded-lg p-3 border border-gray-200">
            <div class="text-sm font-medium text-gray-600 mb-1">${phase.name}</div>
            <div class="text-2xl font-bold text-${phase.color}-600">${phase.count}</div>
        </div>
    `).join('');
}

// Carregar lista de novilhas
async function loadHeifersList() {
    const listContainer = document.getElementById('heifers-list');
    if (!listContainer) return;

    listContainer.innerHTML = '<div class="text-center py-8 text-gray-500"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-fuchsia-600 mx-auto mb-2"></div><p>Carregando novilhas...</p></div>';

    try {
        const response = await fetch('api/heifer_management.php?action=get_heifers_list');
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const text = await response.text();
        if (!text || text.trim() === '') {
            throw new Error('Resposta vazia da API');
        }
        
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('Erro ao parsear JSON:', text);
            throw new Error('Resposta inválida da API');
        }
        
        console.log('Resposta da API de novilhas:', data);
        
        if (data.success) {
            // Verificar se há heifers no data ou data.heifers
            const heifers = data.data?.heifers || data.data || [];
            if (Array.isArray(heifers) && heifers.length > 0) {
                displayHeifersList(heifers);
            } else {
                listContainer.innerHTML = '<div class="text-center py-8 text-gray-500">Nenhuma novilha encontrada</div>';
            }
        } else {
            throw new Error(data.message || 'Erro ao carregar novilhas');
        }
    } catch (error) {
        console.error('Erro ao carregar lista de novilhas:', error);
        listContainer.innerHTML = `
            <div class="text-center py-8 text-red-500">
                <svg class="w-12 h-12 mx-auto mb-2 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <p class="font-semibold">Erro ao carregar novilhas</p>
                <p class="text-sm text-gray-600 mt-1">${error.message || 'Erro desconhecido'}</p>
            </div>
        `;
    }
}

// Exibir lista de novilhas
function displayHeifersList(heifers) {
    const listContainer = document.getElementById('heifers-list');
    if (!listContainer) return;

    if (!heifers || heifers.length === 0) {
        listContainer.innerHTML = '<div class="text-center py-8 text-gray-500">Nenhuma novilha encontrada</div>';
        return;
    }

    listContainer.innerHTML = heifers.map(heifer => {
        const ageMonths = heifer.age_months || 0;
        const phase = heifer.current_phase || 'Sem fase definida';
        const totalCost = parseFloat(heifer.total_cost || 0);
        
        // Determinar cor da fase
        let phaseColor = 'gray';
        if (phase.includes('Aleitamento')) phaseColor = 'blue';
        else if (phase.includes('Transição')) phaseColor = 'yellow';
        else if (phase.includes('Recria')) phaseColor = 'green';
        else if (phase.includes('Crescimento')) phaseColor = 'orange';
        else if (phase.includes('Pré-parto')) phaseColor = 'pink';

        return `
            <div class="p-4 bg-gray-50 rounded-xl border border-gray-200 hover:border-fuchsia-300 transition-colors" data-name="${(heifer.name || '').toLowerCase()}" data-number="${(heifer.ear_tag || '').toLowerCase()}">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3 flex-1">
                        <div class="w-10 h-10 bg-fuchsia-500 rounded-lg flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M19.5 6c-1.3 0-2.5.8-3 2-.5-1.2-1.7-2-3-2s-2.5.8-3 2c-.5-1.2-1.7-2-3-2C5.5 6 4 7.5 4 9.5c0 1.3.7 2.4 1.7 3.1-.4.6-.7 1.3-.7 2.1 0 2.2 1.8 4 4 4h6c2.2 0 4-1.8 4-4 0-.8-.3-1.5-.7-2.1 1-.7 1.7-1.8 1.7-3.1 0-2-1.5-3.5-3.5-3.5z"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-gray-900">${heifer.name || 'Sem nome'} (${heifer.ear_tag || 'N/A'})</p>
                            <p class="text-sm text-gray-600">${ageMonths} meses • ${phase}</p>
                            <p class="text-sm font-medium text-green-600 mt-1">Custo total: ${formatCurrency(totalCost)}</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2 ml-4">
                        <span class="px-3 py-1 bg-${phaseColor}-100 text-${phaseColor}-800 text-xs rounded-full font-medium">${phase}</span>
                        <button onclick="viewHeiferDetails(${heifer.id})" class="px-3 py-1 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors text-sm">
                            Detalhes
                        </button>
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

// Filtrar lista de novilhas
function filterHeifersList() {
    const searchTerm = (document.getElementById('heifer-search')?.value || '').toLowerCase().trim();
    const items = document.querySelectorAll('#heifers-list > div[data-name]');
    
    items.forEach(item => {
        const name = item.dataset.name || '';
        const number = item.dataset.number || '';
        const matches = !searchTerm || name.includes(searchTerm) || number.includes(searchTerm);
        item.style.display = matches ? '' : 'none';
    });
}

// Abrir formulário de custo
window.openHeiferCostForm = function() {
    const modal = document.getElementById('modal-heifer-cost');
    if (modal) {
        modal.classList.add('show');
        populateHeiferSelect('heifer-cost-animal');
        const form = document.getElementById('heiferCostForm');
        if (form) {
            form.reset();
            // Resetar campos
            const costDate = document.getElementById('heifer-cost-total');
            if (costDate) {
                costDate.value = '';
            }
        }
        calculateHeiferCostTotal();
    }
};

// Alias para compatibilidade
window.showAddHeiferCostForm = window.openHeiferCostForm;

// Abrir formulário de consumo diário
window.openHeiferDailyConsumptionForm = function() {
    const modal = document.getElementById('modal-heifer-consumption');
    if (modal) {
        modal.classList.add('show');
        populateHeiferSelect('heifer-consumption-animal');
        const form = document.getElementById('heiferConsumptionForm');
        if (form) {
            form.reset();
            // Resetar data para hoje (usando timezone local)
            const dateInput = form.querySelector('input[type="date"][name="consumption_date"]');
            if (dateInput) {
                dateInput.value = getLocalDateString();
            }
        }
    }
};

// Popular select de novilhas
async function populateHeiferSelect(selectId) {
    const select = document.getElementById(selectId);
    if (!select) return;

    try {
        const response = await fetch('api/heifer_management.php?action=get_heifers_list');
        const data = await response.json();
        
        if (data.success && data.data && data.data.heifers) {
            select.innerHTML = '<option value="">Selecione a novilha</option>' +
                data.data.heifers.map(h => 
                    `<option value="${h.id}">${h.name || 'Sem nome'} (${h.ear_tag || 'N/A'}) - ${h.age_months || 0} meses</option>`
                ).join('');
        }
    } catch (error) {
        console.error('Erro ao carregar novilhas:', error);
    }
}

// Atualizar tipos de alimento baseado na categoria
async function updateHeiferFoodTypes() {
    const categorySelect = document.getElementById('heifer-cost-category');
    const itemTypeSelect = document.getElementById('heifer-cost-item-type');
    
    if (!categorySelect || !itemTypeSelect) return;

    const category = categorySelect.value;
    
    if (category !== 'Alimentação') {
        itemTypeSelect.innerHTML = '<option value="">Selecione o tipo</option>';
        itemTypeSelect.disabled = true;
        return;
    }

    itemTypeSelect.disabled = false;

    // Tipos de alimento para categoria Alimentação
    const foodTypes = [
        { id: 1, name: 'Leite Integral' },
        { id: 2, name: 'Sucedâneo' },
        { id: 3, name: 'Concentrado Inicial' },
        { id: 4, name: 'Concentrado Crescimento' },
        { id: 5, name: 'Volumoso (Silagem)' },
        { id: 6, name: 'Volumoso (Feno)' },
        { id: 7, name: 'Pastagem' }
    ];

    itemTypeSelect.innerHTML = '<option value="">Selecione o tipo</option>' +
        foodTypes.map(type => `<option value="${type.id}">${type.name}</option>`).join('');
}

// Calcular total do custo
function calculateHeiferCostTotal() {
    const quantity = parseFloat(document.getElementById('heifer-cost-quantity')?.value || 0);
    const unitPrice = parseFloat(document.getElementById('heifer-cost-unit-price')?.value || 0);
    const totalInput = document.getElementById('heifer-cost-total');
    
    if (totalInput) {
        const total = quantity * unitPrice;
        totalInput.value = total.toFixed(2);
    }
}

// Submeter formulário de custo
async function handleHeiferCostSubmit(e) {
    e.preventDefault();
    
    const form = e.target;
    const submitBtn = form.querySelector('button[type="submit"]');
    const messageDiv = document.getElementById('heifer-cost-message');
    
    const formData = new FormData(form);
    
    // Calcular total se não foi preenchido
    const quantity = parseFloat(formData.get('quantity') || 1);
    const unitPrice = parseFloat(formData.get('unit_price') || 0);
    let totalCost = parseFloat(formData.get('cost_amount') || 0);
    
    // Se total não foi calculado, calcular agora
    if (totalCost == 0 && quantity > 0 && unitPrice > 0) {
        totalCost = quantity * unitPrice;
    }
    
    // Validar campos obrigatórios
    if (!formData.get('animal_id')) {
        alert('Selecione uma novilha');
        return;
    }
    if (!formData.get('cost_date')) {
        alert('Informe a data do custo');
        return;
    }
    if (!formData.get('cost_category')) {
        alert('Selecione a categoria');
        return;
    }
    if (totalCost <= 0) {
        alert('O valor do custo deve ser maior que zero');
        return;
    }
    
    const data = {
        animal_id: formData.get('animal_id'),
        cost_date: formData.get('cost_date'),
        cost_category: formData.get('cost_category'),
        category_id: formData.get('category_id') || null,
        quantity: quantity,
        unit: formData.get('unit'),
        unit_price: unitPrice,
        cost_amount: totalCost,
        description: formData.get('description')
    };

    // Mostrar loading
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<div class="animate-spin rounded-full h-5 w-5 border-b-2 border-white mx-auto"></div>';
    }

    try {
        const response = await fetch('api/heifer_management.php?action=add_cost', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            if (messageDiv) {
                messageDiv.className = 'p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg';
                messageDiv.textContent = result.message || 'Custo registrado com sucesso!';
                messageDiv.classList.remove('hidden');
            }
            
            form.reset();
            calculateHeiferCostTotal();
            
            // Recarregar dashboard e lista
            loadHeiferDashboard();
            loadHeifersList();
            
            setTimeout(() => {
                closeSubModal('heifer-cost');
            }, 1500);
        } else {
            throw new Error(result.message || 'Erro ao registrar custo');
        }
    } catch (error) {
        console.error('Erro:', error);
        if (messageDiv) {
            messageDiv.className = 'p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg';
            messageDiv.textContent = error.message || 'Erro ao registrar custo';
            messageDiv.classList.remove('hidden');
        }
    } finally {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Registrar Custo';
        }
    }
}

// Submeter formulário de consumo diário
async function handleHeiferConsumptionSubmit(e) {
    e.preventDefault();
    
    const form = e.target;
    const submitBtn = form.querySelector('button[type="submit"]');
    const messageDiv = document.getElementById('heifer-consumption-message');
    
    const formData = new FormData(form);
    
    // Validar campos obrigatórios
    if (!formData.get('animal_id')) {
        alert('Selecione uma novilha');
        return;
    }
    if (!formData.get('consumption_date')) {
        alert('Informe a data');
        return;
    }
    
    const data = {
        animal_id: formData.get('animal_id'),
        consumption_date: formData.get('consumption_date'),
        milk_liters: parseFloat(formData.get('milk_liters') || 0),
        concentrate_kg: parseFloat(formData.get('concentrate_kg') || 0),
        roughage_kg: parseFloat(formData.get('roughage_kg') || 0),
        weight_kg: formData.get('weight_kg') ? parseFloat(formData.get('weight_kg')) : null,
        notes: formData.get('notes')
    };

    // Mostrar loading
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<div class="animate-spin rounded-full h-5 w-5 border-b-2 border-white mx-auto"></div>';
    }

    try {
        const response = await fetch('api/heifer_management.php?action=add_daily_consumption', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            if (messageDiv) {
                messageDiv.className = 'p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg';
                messageDiv.textContent = result.message || 'Consumo registrado com sucesso!';
                messageDiv.classList.remove('hidden');
            }
            
            form.reset();
            
            setTimeout(() => {
                closeSubModal('heifer-consumption');
            }, 1500);
        } else {
            throw new Error(result.message || 'Erro ao registrar consumo');
        }
    } catch (error) {
        console.error('Erro:', error);
        if (messageDiv) {
            messageDiv.className = 'p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg';
            messageDiv.textContent = error.message || 'Erro ao registrar consumo';
            messageDiv.classList.remove('hidden');
        }
    } finally {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Registrar Consumo';
        }
    }
}

// Ver detalhes da novilha
window.viewHeiferDetails = async function(animalId) {
    console.log('Carregando detalhes da novilha:', animalId);
    
    const modal = document.getElementById('modal-heifer-details');
    if (!modal) {
        console.error('Modal de detalhes não encontrado');
        alert('Erro: Modal de detalhes não encontrado');
        return;
    }
    
    // Abrir modal
    modal.classList.add('show');
    
    // Mostrar loading
    document.getElementById('heifer-detail-name').textContent = 'Carregando...';
    document.getElementById('heifer-detail-recent-costs').innerHTML = '<tr><td colspan="4" class="px-4 py-8 text-center text-gray-500">Carregando...</td></tr>';
    
    try {
        const response = await fetch(`api/heifer_management.php?action=get_heifer_details&animal_id=${animalId}`);
        const result = await response.json();
        
        if (!result.success || !result.data) {
            throw new Error(result.error || 'Erro ao carregar detalhes');
        }
        
        const data = result.data;
        const animal = data.animal;
        
        // Preencher informações básicas
        document.getElementById('heifer-details-title').textContent = animal.name || `Novilha ${animal.animal_number || animalId}`;
        document.getElementById('heifer-details-subtitle').textContent = `ID: ${animalId} | ${animal.current_phase || 'Sem fase'}`;
        document.getElementById('heifer-detail-name').textContent = animal.name || '-';
        document.getElementById('heifer-detail-ear-tag').textContent = animal.animal_number || '-';
        document.getElementById('heifer-detail-birth-date').textContent = animal.birth_date ? new Date(animal.birth_date).toLocaleDateString('pt-BR') : '-';
        document.getElementById('heifer-detail-age').textContent = `${animal.age_days || 0} dias (${animal.age_months || 0} meses)`;
        document.getElementById('heifer-detail-phase').textContent = animal.current_phase || '-';
        document.getElementById('heifer-detail-status').textContent = animal.status || '-';
        
        // Preencher resumo de custos
        document.getElementById('heifer-detail-total-cost').textContent = formatCurrency(data.total_cost || 0);
        document.getElementById('heifer-detail-total-records').textContent = data.total_records || 0;
        document.getElementById('heifer-detail-avg-daily').textContent = formatCurrency(data.avg_daily_cost || 0);
        
        // Preencher custos por categoria
        const categoriesDiv = document.getElementById('heifer-detail-categories');
        if (data.costs_by_category && data.costs_by_category.length > 0) {
            categoriesDiv.innerHTML = data.costs_by_category.map(cat => `
                <div class="flex items-center justify-between bg-white rounded-lg p-4 border border-gray-200">
                    <div>
                        <p class="font-semibold text-gray-900">${cat.category_name || cat.category_type || 'Sem categoria'}</p>
                        <p class="text-sm text-gray-500">${cat.total_records || 0} registros</p>
                    </div>
                    <p class="text-lg font-bold text-green-600">${formatCurrency(cat.total_cost || 0)}</p>
                </div>
            `).join('');
        } else {
            categoriesDiv.innerHTML = '<p class="text-gray-500 text-center py-4">Nenhum custo registrado por categoria</p>';
        }
        
        // Preencher custos por fase
        const phasesDiv = document.getElementById('heifer-detail-phases');
        if (data.costs_by_phase && data.costs_by_phase.length > 0) {
            phasesDiv.innerHTML = data.costs_by_phase.map(phase => `
                <div class="flex items-center justify-between bg-white rounded-lg p-4 border border-gray-200">
                    <div>
                        <p class="font-semibold text-gray-900">${phase.phase_name || 'Sem fase'}</p>
                        <p class="text-sm text-gray-500">${phase.phase_records || 0} registros</p>
                    </div>
                    <p class="text-lg font-bold text-blue-600">${formatCurrency(phase.phase_total_cost || 0)}</p>
                </div>
            `).join('');
        } else {
            phasesDiv.innerHTML = '<p class="text-gray-500 text-center py-4">Nenhum custo registrado por fase</p>';
        }
        
        // Preencher últimos registros
        const recentCostsTbody = document.getElementById('heifer-detail-recent-costs');
        if (data.recent_costs && data.recent_costs.length > 0) {
            recentCostsTbody.innerHTML = data.recent_costs.map(cost => `
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">${cost.cost_date ? new Date(cost.cost_date).toLocaleDateString('pt-BR') : '-'}</td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">${cost.category_name || cost.cost_category || '-'}</td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm font-semibold text-green-600">${formatCurrency(cost.cost_amount || 0)}</td>
                    <td class="px-4 py-3 text-sm text-gray-500">${cost.description || '-'}</td>
                </tr>
            `).join('');
        } else {
            recentCostsTbody.innerHTML = '<tr><td colspan="4" class="px-4 py-8 text-center text-gray-500">Nenhum registro de custo encontrado</td></tr>';
        }
        
    } catch (error) {
        console.error('Erro ao carregar detalhes da novilha:', error);
        alert('Erro ao carregar detalhes: ' + error.message);
        
        // Fechar modal em caso de erro
        closeSubModal('heifer-details');
    }
};

// Carregar relatórios
function loadHeiferReports() {
    console.log('Carregar relatórios de novilhas');
    // TODO: Implementar relatórios
}

// Formatar moeda
function formatCurrency(value) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(value || 0);
}
