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

// ==================== ESTADO GLOBAL ====================
let currentTab = 'dashboard';
let isLoading = false;
let refreshTimer = null;
let charts = {};

// ==================== INICIALIZAÇÃO ====================
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Inicializando Dashboard Gerente Completo...');
    
    initializeNavigation();
    initializeOverlays();
    loadDashboardData();
    startAutoRefresh();
    updateDateTime();
    registerCurrentSession();
    
    // Atualizar última atividade periodicamente (a cada 5 minutos)
    setInterval(function() {
        updateSessionActivity();
    }, 5 * 60 * 1000); // 5 minutos
    
    console.log('✅ Dashboard Gerente Completo inicializado com sucesso!');
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
        
        const result = await response.json();
        console.log('📊 Dados recebidos:', result);
        
        if (result.success) {
            const data = result.data;
            
            // Helper numérico seguro
            const n = (v) => {
                const num = typeof v === 'number' ? v : parseFloat(v);
                return Number.isFinite(num) ? num : 0;
            };
            
            // Atualizar métricas principais
            const todayVolumeEl = document.getElementById('todayVolume');
            if (todayVolumeEl) {
                todayVolumeEl.textContent = n(data.today_production?.today_volume).toFixed(1) + 'L';
            }
            
            const qualityAverageEl = document.getElementById('qualityAverage');
            if (qualityAverageEl) {
                qualityAverageEl.textContent = n(data.quality?.avg_fat).toFixed(1) + '%';
            }
            
            const pendingPaymentsEl = document.getElementById('pendingPayments');
            if (pendingPaymentsEl) {
                // Usar despesas do mês, pois não há campo "pendente" no schema
                pendingPaymentsEl.textContent = 'R$ ' + n(data.expenses?.month_expenses).toFixed(2);
            }
            
            const activeUsersEl = document.getElementById('activeUsers');
            if (activeUsersEl) {
                try {
                    const usersResp = await fetch('./api/endpoints/users.php');
                    const usersJson = await usersResp.json();
                    const usersCount = usersJson?.data?.stats?.active_users ?? 0;
                    activeUsersEl.textContent = String(n(usersCount).toFixed(0));
                } catch (e) {
                    activeUsersEl.textContent = '0';
                }
            }
            
            // Atualizar gráficos
            renderMonthlyVolumeChart(data.production_chart);
            renderWeeklyVolumeCharts();
            renderTemperatureChart();
            
            // Atualizar atividades recentes
            updateRecentActivities(data.recent_activities);
            
            // Atualizar data/hora
            const lastUpdateEl = document.getElementById('lastUpdate');
            if (lastUpdateEl) {
                lastUpdateEl.textContent = new Date().toLocaleString('pt-BR');
            }
            
            console.log('✅ Dados do dashboard carregados!');
        } else {
            console.error('Erro na API:', result.error);
        }
    } catch (error) {
        console.error('Erro na requisição:', error);
    }
}

// ==================== FUNÇÕES DE GRÁFICOS E ATIVIDADES ====================

// Atualizar gráfico de produção
// ==================== CHART HELPERS (Chart.js) ====================
function createOrUpdateLineChart(canvasId, labels, data, color = '#10B981') {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;
    if (charts[canvasId]) {
        charts[canvasId].destroy();
    }
    charts[canvasId] = new Chart(canvas.getContext('2d'), {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: '',
                data,
                borderColor: color,
                backgroundColor: color + '1A',
                fill: true,
                tension: 0.3,
                borderWidth: 2,
                pointRadius: 3,
                showLine: true,
                spanGaps: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { x: { display: true }, y: { display: true, beginAtZero: true } }
        }
    });
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
        const res = await fetch('./api/endpoints/volume.php');
        const json = await res.json();
        const series = Array.isArray(json?.data?.chart) ? json.data.chart : [];
        // Construir faixa dos últimos 7 dias e preencher faltantes com 0
        const dateKey = (d) => d.toISOString().slice(0,10);
        const today = new Date();
        const last7Dates = Array.from({length: 7}, (_, idx) => {
            const d = new Date(today);
            d.setDate(today.getDate() - (6 - idx));
            return dateKey(d);
        });
        const map = {};
        series.forEach(i => { map[i.production_date] = Number(i.daily_volume) || 0; });
        const labels7 = last7Dates;
        const data7 = labels7.map(d => map[d] ?? 0);
        // Garantir linha
        if (data7.length === 1) { labels7.push(labels7[0]); data7.push(data7[0]); }
        createOrUpdateLineChart('volumeChart', labels7, data7, '#3B82F6');
        createOrUpdateLineChart('dashboardWeeklyChart', labels7, data7, '#6366F1');
    } catch (e) {
        const labels7 = [];
        const data7 = [];
        createOrUpdateLineChart('volumeChart', labels7, data7, '#3B82F6');
        createOrUpdateLineChart('dashboardWeeklyChart', labels7, data7, '#6366F1');
    }
}

async function renderTemperatureChart() {
    try {
        const res = await fetch('./api/volume.php?action=get_temperature');
        const json = await res.json();
        const srcLabels = Array.isArray(json?.data?.labels) ? json.data.labels : [];
        const srcData = Array.isArray(json?.data?.data) ? json.data.data.map(v => Number(v) || 0) : [];
        // Preencher últimos 30 dias
        const dateKey = (d) => d.toISOString().slice(0,10);
        const today = new Date();
        const last30 = Array.from({length: 30}, (_, idx) => {
            const d = new Date(today);
            d.setDate(today.getDate() - (29 - idx));
            return dateKey(d);
        });
        const map = {};
        srcLabels.forEach((d, i) => { map[d] = srcData[i] ?? 0; });
        const labels = last30;
        const data = labels.map(d => map[d] ?? 0);
        if (data.length === 1) { labels.push(labels[0]); data.push(data[0]); }
        createOrUpdateLineChart('temperatureChart', labels, data, '#F59E0B');
    } catch (e) {
        createOrUpdateLineChart('temperatureChart', [], [], '#F59E0B');
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
    try {
        const res = await fetch('./api/volume.php?action=get_all');
        const json = await res.json();
        const rows = Array.isArray(json?.data) ? json.data : [];
        const tbody = document.getElementById('volumeRecordsTable');
        if (!tbody) return;
        if (rows.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center py-8 text-gray-500">Nenhum registro</td></tr>';
            return;
        }
        tbody.innerHTML = rows.map(r => `
            <tr class="border-b border-gray-100">
                <td class="py-3 px-4">${r.record_date}</td>
                <td class="py-3 px-4">${r.shift}</td>
                <td class="py-3 px-4">${(Number(r.total_volume)||0).toFixed(1)}</td>
                <td class="py-3 px-4">${r.temperature ? (Number(r.temperature).toFixed(1)+'°C') : '-'}</td>
                <td class="py-3 px-4 text-right">
                    <button class="text-blue-600 hover:underline" data-id="${r.id}">Detalhes</button>
                </td>
            </tr>
        `).join('');
    } catch (e) {
        const tbody = document.getElementById('volumeRecordsTable');
        if (tbody) tbody.innerHTML = '<tr><td colspan="5" class="text-center py-8 text-red-500">Erro ao carregar registros</td></tr>';
    }
}

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
        tbody.innerHTML = rows.map(r => `
            <tr class="border-b border-gray-100">
                <td class="py-3 px-4">${r.test_date}</td>
                <td class="py-3 px-4">${r.fat_content ? Number(r.fat_content).toFixed(2) : '-'}</td>
                <td class="py-3 px-4">${r.protein_content ? Number(r.protein_content).toFixed(2) : '-'}</td>
                <td class="py-3 px-4">${r.somatic_cells ?? '-'}</td>
                <td class="py-3 px-4 text-right">
                    <span class="text-gray-500 text-xs">${r.laboratory || ''}</span>
                </td>
            </tr>
        `).join('');
    } catch (e) {
        const tbody = document.getElementById('qualityRecordsTable');
        if (tbody) tbody.innerHTML = '<tr><td colspan="5" class="text-center py-8 text-red-500">Erro ao carregar registros</td></tr>';
    }
}

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
        tbody.innerHTML = rows.map(r => `
            <tr class="border-b border-gray-100">
                <td class="py-3 px-4">${r.record_date}</td>
                <td class="py-3 px-4">${r.type}</td>
                <td class="py-3 px-4">${r.description || ''}</td>
                <td class="py-3 px-4">R$ ${(Number(r.amount)||0).toFixed(2)}</td>
                <td class="py-3 px-4 text-right"><span class="text-gray-500 text-xs">${r.created_at}</span></td>
            </tr>
        `).join('');
    } catch (e) {
        const tbody = document.getElementById('financialRecordsTable');
        if (tbody) tbody.innerHTML = '<tr><td colspan="5" class="text-center py-8 text-red-500">Erro ao carregar registros</td></tr>';
    }
}

// ==================== USUÁRIOS ====================
async function loadUsersData() {
    console.log('📊 Carregando dados de usuários...');
    
    try {
        const response = await fetch('./api/endpoints/users.php');
        const result = await response.json();
        
        if (result.success) {
            const data = result.data;
            
            // Atualizar métricas de usuários
            const n = (v) => { const num = typeof v === 'number' ? v : parseFloat(v); return Number.isFinite(num) ? num : 0; };
            const totalUsersEl = document.getElementById('totalUsers');
            if (totalUsersEl) {
                totalUsersEl.textContent = String(n(data.stats?.total_users).toFixed(0));
            }
            const activeUsersMetric = document.querySelector('#users-tab #activeUsers');
            if (activeUsersMetric) {
                activeUsersMetric.textContent = String(n(data.stats?.active_users).toFixed(0));
            }
            
            // Preencher tabela de usuários
            const tbody = document.getElementById('usersTable');
            if (tbody) {
                const rows = Array.isArray(data.users) ? data.users : [];
                if (rows.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-center py-8 text-gray-500">Nenhum usuário</td></tr>';
                } else {
                    tbody.innerHTML = rows.map(u => `
                        <tr class=\"border-b border-gray-100\"> 
                            <td class=\"py-3 px-4\">${u.name}</td>
                            <td class=\"py-3 px-4\">${u.email}</td>
                            <td class=\"py-3 px-4\">${u.role}</td>
                            <td class=\"py-3 px-4\">${u.is_active == 1 ? 'Ativo' : 'Inativo'}</td>
                            <td class=\"py-3 px-4 text-right\">
                                <span class=\"text-gray-500 text-xs\">${u.last_login ?? ''}</span>
                            </td>
                        </tr>
                    `).join('');
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
    if (modal) {
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
    if (modal) {
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
            const formData = new FormData(generalVolumeForm);
            formData.append('action', 'add_volume_general');
            try {
                const resp = await fetch('./api/actions.php', { method: 'POST', body: formData });
                const result = await resp.json();
                if (result.success) {
                    if (window.closeGeneralVolumeOverlay) window.closeGeneralVolumeOverlay();
                    loadVolumeData();
                } else {
                    console.error('Erro ao registrar volume geral:', result.error);
                }
            } catch (err) {
                console.error('Falha ao registrar volume geral:', err);
            }
        });
    }

    // Volume por animal
    const volumeForm = document.getElementById('volumeForm');
    if (volumeForm) {
        volumeForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(volumeForm);
            formData.append('action', 'add_volume_by_animal');
            try {
                const resp = await fetch('./api/actions.php', { method: 'POST', body: formData });
                const result = await resp.json();
                if (result.success) {
                    if (window.closeVolumeOverlay) window.closeVolumeOverlay();
                    loadVolumeData();
                } else {
                    console.error('Erro ao registrar volume por vaca:', result.error);
                }
            } catch (err) {
                console.error('Falha ao registrar volume por vaca:', err);
            }
        });
    }

    // Qualidade
    const qualityForm = document.getElementById('qualityForm');
    if (qualityForm) {
        qualityForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(qualityForm);
            formData.append('action', 'add_quality_test');
            try {
                const resp = await fetch('./api/actions.php', { method: 'POST', body: formData });
                const result = await resp.json();
                if (result.success) {
                    if (window.closeQualityOverlay) window.closeQualityOverlay();
                    loadQualityData();
                } else {
                    console.error('Erro ao registrar teste de qualidade:', result.error);
                }
            } catch (err) {
                console.error('Falha ao registrar teste de qualidade:', err);
            }
        });
    }

    // Financeiro
    const salesForm = document.getElementById('salesForm');
    if (salesForm) {
        salesForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(salesForm);
            formData.append('action', 'add_financial_record');
            formData.append('type', 'receita');
            formData.append('record_date', formData.get('sale_date'));
            formData.append('description', `Venda para ${formData.get('customer')}`);
            formData.append('amount', formData.get('total_amount'));
            try {
                const resp = await fetch('./api/actions.php', { method: 'POST', body: formData });
                const result = await resp.json();
                if (result.success) {
                    if (window.closeSalesOverlay) window.closeSalesOverlay();
                    loadFinancialData();
                } else {
                    console.error('Erro ao registrar venda:', result.error);
                }
            } catch (err) {
                console.error('Falha ao registrar venda:', err);
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
            const formData = new FormData(addUserForm);
            if (formData.get('password') !== formData.get('confirm_password')) {
                console.error('Senhas não coincidem');
                return;
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
                const resp = await fetch('./api/actions.php', { method: 'POST', body: formData });
                const result = await resp.json();
                if (result.success) {
                    if (window.closeAddUserModal) window.closeAddUserModal();
                    loadUsersData();
                } else {
                    console.error('Erro ao criar usuário:', result.error);
                }
            } catch (err) {
                console.error('Falha ao criar usuário:', err);
            }
        });
    }
});

// Volume: abrir modais
function showGeneralVolumeOverlay() {
    const modal = document.getElementById('generalVolumeOverlay');
    if (modal) {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
}
function showVolumeOverlay() {
    const modal = document.getElementById('volumeOverlay');
    if (modal) {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        populateVolumeAnimalSelect();
    }
}
window.showGeneralVolumeOverlay = showGeneralVolumeOverlay;
window.showVolumeOverlay = showVolumeOverlay;

// Helpers para fechar modais (garantir que os onClick funcionem)
function closeGeneralVolumeOverlay() {
    const modal = document.getElementById('generalVolumeOverlay');
    if (modal) {
        modal.classList.add('hidden');
        document.body.style.overflow = 'auto';
    }
}
function closeVolumeOverlay() {
    const modal = document.getElementById('volumeOverlay');
    if (modal) {
        modal.classList.add('hidden');
        document.body.style.overflow = 'auto';
    }
}
function closeQualityOverlay() {
    const modal = document.getElementById('qualityOverlay');
    if (modal) {
        modal.classList.add('hidden');
        document.body.style.overflow = 'auto';
    }
}
function closeSalesOverlay() {
    const modal = document.getElementById('salesOverlay');
    if (modal) {
        modal.classList.add('hidden');
        document.body.style.overflow = 'auto';
    }
}
window.closeGeneralVolumeOverlay = closeGeneralVolumeOverlay;
window.closeVolumeOverlay = closeVolumeOverlay;
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
    try {
        const res = await fetch('./api/animals.php?action=select');
        const json = await res.json();
        const animals = Array.isArray(json?.data) ? json.data : [];
        const lactatingFemales = animals.filter(a => (a.gender === 'femea') && (a.is_active == 1));
        if (lactatingFemales.length === 0) {
            select.innerHTML = '<option value="">Nenhuma vaca encontrada</option>';
            return;
        }
        select.innerHTML = ['<option value="">Selecione uma vaca</option>']
            .concat(lactatingFemales.map(a => `<option value="${a.id}">${a.animal_number || ''} ${a.name ? '- '+a.name : ''}</option>`))
            .join('');
    } catch (e) {
        select.innerHTML = '<option value="">Erro ao carregar vacas</option>';
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
    const res = await fetch('./api/endpoints/notifications.php');
    if (!res.ok) { list.innerHTML = '<div class="text-sm text-red-500">Erro ao carregar</div>'; return; }
    const data = await res.json().catch(() => ({}));
    const items = (data.notifications || data.items || data.data || []);
    const unreadCount = Array.isArray(items) ? items.filter(i => (i.read === 0 || i.lida === 0 || i.unread === true || i.read === false)).length : 0;
    
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
    const unread = (item.read === 0 || item.lida === 0 || item.unread === true);
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
        await fetch('./api/endpoints/notifications.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'mark_all_read' }) });
        await loadNotifications();
    } catch (e) {
        console.error('Erro ao marcar como lidas', e);
    }
};

function escapeHtml(s){
    if (s == null) return '';
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
}

// ==================== PERFIL DO USUÁRIO ====================
let profileOriginalValues = {};
let profileEditMode = false;
let profilePhotoFile = null;

window.openProfileOverlay = function openProfileOverlay() {
    const modal = document.getElementById('profileOverlay');
    if (!modal) return;
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    
    // Resetar modo edição
    profileEditMode = false;
    updateProfileEditMode();
    
    // Resetar foto
    profilePhotoFile = null;
    if (document.getElementById('profilePhotoInput')) document.getElementById('profilePhotoInput').value = '';
    
    // Armazenar valores originais
    profileOriginalValues = {
        name: document.getElementById('profileName')?.value || '',
        phone: document.getElementById('profilePhone')?.value || '',
        farmName: document.getElementById('farmName')?.value || '',
        farmCNPJ: document.getElementById('farmCNPJ')?.value || '',
        farmAddress: document.getElementById('farmAddress')?.value || '',
        pushNotifications: document.getElementById('pushNotifications')?.checked || false,
        newPassword: '',
        confirmPassword: ''
    };
    
    // Ocultar footer inicialmente
    const footer = document.getElementById('profileFooter');
    if (footer) footer.classList.add('hidden');
    
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
        
        // Restaurar valores originais
        if (document.getElementById('profileName')) document.getElementById('profileName').value = profileOriginalValues.name;
        if (document.getElementById('profilePhone')) document.getElementById('profilePhone').value = profileOriginalValues.phone;
        if (document.getElementById('farmName')) document.getElementById('farmName').value = profileOriginalValues.farmName;
        if (document.getElementById('farmCNPJ')) document.getElementById('farmCNPJ').value = profileOriginalValues.farmCNPJ;
        if (document.getElementById('farmAddress')) document.getElementById('farmAddress').value = profileOriginalValues.farmAddress;
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
    const inputs = ['profileName', 'profilePhone', 'farmName', 'farmCNPJ', 'farmAddress', 'profileNewPassword', 'profileConfirmPassword'];
    const checkboxes = ['pushNotifications'];
    
    const checkForChanges = () => {
        const hasChanges = 
            (document.getElementById('profileName')?.value !== profileOriginalValues.name) ||
            (document.getElementById('profilePhone')?.value !== profileOriginalValues.phone) ||
            (document.getElementById('farmName')?.value !== profileOriginalValues.farmName) ||
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
    // Restaurar valores originais
    if (document.getElementById('profileName')) document.getElementById('profileName').value = profileOriginalValues.name;
    if (document.getElementById('profilePhone')) document.getElementById('profilePhone').value = profileOriginalValues.phone;
    if (document.getElementById('farmName')) document.getElementById('farmName').value = profileOriginalValues.farmName;
    if (document.getElementById('farmCNPJ')) document.getElementById('farmCNPJ').value = profileOriginalValues.farmCNPJ;
    if (document.getElementById('farmAddress')) document.getElementById('farmAddress').value = profileOriginalValues.farmAddress;
    if (document.getElementById('pushNotifications')) document.getElementById('pushNotifications').checked = profileOriginalValues.pushNotifications;
    if (document.getElementById('profileNewPassword')) document.getElementById('profileNewPassword').value = '';
    if (document.getElementById('profileConfirmPassword')) document.getElementById('profileConfirmPassword').value = '';
    
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
                
                // Atualizar foto no servidor se retornou URL
                if (result.data && result.data.profile_photo) {
                    if (avatarImg) {
                        avatarImg.src = result.data.profile_photo + '?t=' + Date.now();
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
    const name = document.getElementById('profileName')?.value;
    const phone = document.getElementById('profilePhone')?.value;
    const farmName = document.getElementById('farmName')?.value;
    const farmCNPJ = document.getElementById('farmCNPJ')?.value;
    const farmAddress = document.getElementById('farmAddress')?.value;
    const pushNotifications = document.getElementById('pushNotifications')?.checked;
    const newPassword = document.getElementById('profileNewPassword')?.value;
    const confirmPassword = document.getElementById('profileConfirmPassword')?.value;
    
    // Validar senhas se fornecidas
    if (newPassword || confirmPassword) {
        if (newPassword !== confirmPassword) {
            showErrorModal('As senhas não coincidem');
            return;
        }
        if (newPassword.length < 6) {
            showErrorModal('A senha deve ter pelo menos 6 caracteres');
            return;
        }
    }
    
    try {
        const formData = new FormData();
        formData.append('action', 'update_profile');
        formData.append('name', name);
        formData.append('phone', phone);
        formData.append('farm_name', farmName);
        formData.append('farm_cnpj', farmCNPJ);
        formData.append('farm_address', farmAddress);
        formData.append('push_notifications', pushNotifications ? '1' : '0');
        if (newPassword) formData.append('password', newPassword);
        if (profilePhotoFile) formData.append('profile_photo', profilePhotoFile);
        
        const resp = await fetch('./api/actions.php', { method: 'POST', body: formData });
        const result = await resp.json();
        
        if (result.success) {
            // Atualizar valores originais
            profileOriginalValues = {
                name, phone, farmName, farmCNPJ, farmAddress,
                pushNotifications,
                newPassword: '', confirmPassword: ''
            };
            
            // Limpar senhas e foto
            if (document.getElementById('profileNewPassword')) document.getElementById('profileNewPassword').value = '';
            if (document.getElementById('profileConfirmPassword')) document.getElementById('profileConfirmPassword').value = '';
            if (document.getElementById('profilePhotoInput')) document.getElementById('profilePhotoInput').value = '';
            profilePhotoFile = null;
            
            // Atualizar foto se foi enviada
            if (result.data && result.data.profile_photo) {
                const avatarDisplay = document.getElementById('profileAvatarDisplay');
                const avatarImg = document.getElementById('profileAvatarImg');
                if (avatarImg) {
                    avatarImg.src = result.data.profile_photo + '?t=' + Date.now();
                }
            }
            
            // Sair do modo edição e voltar para visualização
            profileEditMode = false;
            updateProfileEditMode();
            
            showSuccessModal('Perfil atualizado com sucesso!');
        } else {
            showErrorModal('Erro ao atualizar perfil: ' + (result.error || 'Erro desconhecido'));
        }
    } catch (err) {
        console.error('Falha ao salvar perfil:', err);
        showErrorModal('Erro ao salvar perfil');
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
