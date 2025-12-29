<?php
/**
 * Página: Dashboard Analítico
 * Sistema completo de análises e métricas com gráficos interativos
 */

require_once __DIR__ . '/../includes/config_login.php';
require_once __DIR__ . '/../includes/Database.class.php';

if (!isLoggedIn() || ($_SESSION['user_role'] !== 'gerente' && $_SESSION['user_role'] !== 'manager')) {
    http_response_code(403);
    die('Acesso negado');
}

$v = time();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Analítico - LacTech</title>
    <?php if (file_exists(__DIR__ . '/../assets/css/tailwind.min.css')): ?>
        <link rel="stylesheet" href="../assets/css/tailwind.min.css">
    <?php else: ?>
        <script src="https://cdn.tailwindcss.com"></script>
    <?php endif; ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen bg-white">
        <!-- Header -->
        <div class="bg-white border-b border-gray-200 sticky top-0 z-20 shadow-sm">
            <div class="container mx-auto px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <button onclick="window.parent.postMessage({type: 'closeModal'}, '*')" class="w-10 h-10 flex items-center justify-center hover:bg-gray-100 rounded-lg transition-colors">
                            <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                        </button>
                        <div class="flex items-center space-x-3">
                            <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-xl flex items-center justify-center shadow-lg">
                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                            <div>
                                <h1 class="text-2xl font-bold text-gray-900">Dashboard Analítico</h1>
                                <p class="text-sm text-gray-600">Métricas e indicadores de performance</p>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center space-x-3">
                        <select id="period-select" onchange="loadDashboard()" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                            <option value="7">Últimos 7 dias</option>
                            <option value="30" selected>Últimos 30 dias</option>
                            <option value="90">Últimos 90 dias</option>
                            <option value="365">Último ano</option>
                        </select>
                        <button onclick="loadDashboard()" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors flex items-center space-x-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            <span>Atualizar</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="container mx-auto px-6 py-6">
            <!-- KPIs Principais -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-sm opacity-90">Produção Hoje</p>
                        <svg class="w-6 h-6 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <p id="kpi-production-today" class="text-3xl font-bold">-</p>
                    <p class="text-sm opacity-75 mt-1" id="kpi-production-animals">- animais</p>
                </div>
                
                <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-sm opacity-90">Média por Animal</p>
                        <svg class="w-6 h-6 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                    </div>
                    <p id="kpi-avg-production" class="text-3xl font-bold">-</p>
                    <p class="text-sm opacity-75 mt-1">Litros/dia</p>
                </div>
                
                <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl shadow-lg p-6 text-white">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-sm opacity-90">Taxa de Prenhez</p>
                        <svg class="w-6 h-6 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <p id="kpi-pregnancy-rate" class="text-3xl font-bold">-</p>
                    <p class="text-sm opacity-75 mt-1">% de concepção</p>
                </div>
                
                <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-sm opacity-90">Total de Animais</p>
                        <svg class="w-6 h-6 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                    </div>
                    <p id="kpi-total-animals" class="text-3xl font-bold">-</p>
                    <p class="text-sm opacity-75 mt-1">animais ativos</p>
                </div>
            </div>

            <!-- Gráficos -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Produção ao Longo do Tempo -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Produção de Leite</h3>
                    <div style="position: relative; height: 300px;">
                        <canvas id="production-chart"></canvas>
                    </div>
                </div>
                
                <!-- Qualidade do Leite -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Qualidade do Leite</h3>
                    <div style="position: relative; height: 300px;">
                        <canvas id="quality-chart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Análises Detalhadas -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Qualidade Detalhada -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Indicadores de Qualidade</h3>
                    <div class="space-y-4">
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-gray-700">Gordura</span>
                                <span id="quality-fat" class="text-lg font-bold text-green-600">-</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-3">
                                <div id="quality-fat-bar" class="bg-green-500 h-3 rounded-full transition-all" style="width: 0%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-gray-700">Proteína</span>
                                <span id="quality-protein" class="text-lg font-bold text-blue-600">-</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-3">
                                <div id="quality-protein-bar" class="bg-blue-500 h-3 rounded-full transition-all" style="width: 0%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-gray-700">Células Somáticas</span>
                                <span id="quality-somatic" class="text-lg font-bold text-orange-600">-</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-3">
                                <div id="quality-somatic-bar" class="bg-orange-500 h-3 rounded-full transition-all" style="width: 0%"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Distribuição por Raça -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Distribuição por Raça</h3>
                    <div style="position: relative; height: 250px;">
                        <canvas id="breed-chart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Top Produtores e Métricas -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Top Produtores -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Top Produtores</h3>
                    <div id="top-producers-list" class="space-y-3">
                        <p class="text-gray-500 text-center py-4">Carregando...</p>
                    </div>
                </div>
                
                <!-- Métricas de Eficiência -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Métricas de Eficiência</h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between p-4 bg-gradient-to-r from-green-50 to-emerald-50 rounded-lg border border-green-200">
                            <div>
                                <p class="text-sm text-gray-600">Taxa de Concepção</p>
                                <p id="metric-conception-rate" class="text-2xl font-bold text-green-600">-</p>
                            </div>
                            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="flex items-center justify-between p-4 bg-gradient-to-r from-blue-50 to-cyan-50 rounded-lg border border-blue-200">
                            <div>
                                <p class="text-sm text-gray-600">Intervalo Entre Partos (IEP)</p>
                                <p id="metric-iep" class="text-2xl font-bold text-blue-600">-</p>
                            </div>
                            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toastContainer" class="fixed bottom-4 right-4 z-[99999]"></div>

    <script src="../assets/js/toast-notifications.js?v=<?php echo $v; ?>"></script>
    <script>
        const API_BASE = '../api/analytics.php';
        let productionChart = null;
        let qualityChart = null;
        let breedChart = null;

        // Carregar dados iniciais
        document.addEventListener('DOMContentLoaded', () => {
            // Verificar se Chart.js está disponível
            if (typeof Chart === 'undefined') {
                console.error('Chart.js não está carregado!');
                showErrorToast('Erro: Chart.js não está disponível. Recarregue a página.');
                return;
            }
            
            loadDashboard();
        });

        async function loadDashboard() {
            const period = document.getElementById('period-select').value;
            
            try {
                // Carregar KPIs
                const dashboardResponse = await fetch(`${API_BASE}?action=dashboard&period=${period}`);
                
                if (!dashboardResponse.ok) {
                    throw new Error(`HTTP error! status: ${dashboardResponse.status}`);
                }
                
                const dashboardResult = await dashboardResponse.json();
                
                if (dashboardResult.success) {
                    updateKPIs(dashboardResult.data);
                } else {
                    console.error('Erro na API:', dashboardResult.message);
                    showErrorToast('Erro ao carregar dados: ' + (dashboardResult.message || 'Erro desconhecido'));
                }
                
                // Carregar gráficos em paralelo
                await Promise.all([
                    loadProductionChart(period),
                    loadQualityChart(period),
                    loadBreedChart(),
                    loadTopProducers(period),
                    loadEfficiencyMetrics(period)
                ]);
                
            } catch (error) {
                console.error('Erro ao carregar dashboard:', error);
                showErrorToast('Erro ao carregar dados do dashboard: ' + error.message);
            }
        }

        function updateKPIs(data) {
            // Produção hoje
            document.getElementById('kpi-production-today').textContent = 
                formatNumber(data.production_today?.total || 0) + 'L';
            document.getElementById('kpi-production-animals').textContent = 
                (data.production_today?.animals || 0) + ' animais';
            
            // Média por animal
            document.getElementById('kpi-avg-production').textContent = 
                formatNumber(data.production_period?.average || 0) + 'L';
            
            // Taxa de prenhez
            document.getElementById('kpi-pregnancy-rate').textContent = 
                (data.pregnancy_rate || 0) + '%';
            
            // Total de animais
            document.getElementById('kpi-total-animals').textContent = 
                data.total_animals || 0;
            
            // Qualidade
            const fat = data.quality?.fat || 0;
            const protein = data.quality?.protein || 0;
            const somatic = data.quality?.somatic_cells || 0;
            
            document.getElementById('quality-fat').textContent = fat + '%';
            document.getElementById('quality-protein').textContent = protein + '%';
            document.getElementById('quality-somatic').textContent = formatNumber(somatic) + 'K';
            
            // Barras de progresso (normalizadas)
            document.getElementById('quality-fat-bar').style.width = Math.min((fat / 5) * 100, 100) + '%';
            document.getElementById('quality-protein-bar').style.width = Math.min((protein / 4) * 100, 100) + '%';
            document.getElementById('quality-somatic-bar').style.width = Math.min((somatic / 500) * 100, 100) + '%';
        }

        async function loadProductionChart(period) {
            try {
                const response = await fetch(`${API_BASE}?action=production_chart&period=${period}&group_by=day`);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const result = await response.json();
                
                if (!result.success) {
                    console.error('API Error:', result.message);
                    showErrorToast('Erro ao carregar dados: ' + (result.message || 'Erro desconhecido'));
                    return;
                }
                
                const ctx = document.getElementById('production-chart');
                if (!ctx) {
                    console.error('Canvas production-chart não encontrado');
                    return;
                }
                
                const ctx2d = ctx.getContext('2d');
                
                if (productionChart) {
                    productionChart.destroy();
                }
                
                // Verificar se há dados
                if (!result.data || result.data.length === 0) {
                    // Criar gráfico vazio com mensagem
                    productionChart = new Chart(ctx2d, {
                        type: 'line',
                        data: {
                            labels: ['Sem dados'],
                            datasets: [{
                                label: 'Volume Total (L)',
                                data: [0],
                                borderColor: 'rgba(200, 200, 200, 0.5)',
                                backgroundColor: 'rgba(200, 200, 200, 0.1)'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: true,
                            aspectRatio: 2,
                            plugins: {
                                legend: { display: false },
                                tooltip: { enabled: false },
                                title: {
                                    display: true,
                                    text: 'Nenhum dado disponível para o período selecionado',
                                    font: { size: 14 },
                                    color: '#999'
                                }
                            }
                        }
                    });
                    return;
                }
                
                productionChart = new Chart(ctx2d, {
                    type: 'line',
                    data: {
                        labels: result.data.map(d => {
                            // Formatar período corretamente
                            if (typeof d.period === 'string' && d.period.includes('-')) {
                                return formatDate(d.period);
                            }
                            return d.period;
                        }),
                        datasets: [{
                            label: 'Volume Total (L)',
                            data: result.data.map(d => parseFloat(d.total_volume || 0)),
                            borderColor: 'rgb(59, 130, 246)',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            tension: 0.4,
                            fill: true,
                            borderWidth: 2
                        }, {
                            label: 'Média por Animal (L)',
                            data: result.data.map(d => parseFloat(d.avg_volume || 0)),
                            borderColor: 'rgb(34, 197, 94)',
                            backgroundColor: 'rgba(34, 197, 94, 0.1)',
                            tension: 0.4,
                            fill: true,
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        aspectRatio: 2,
                        plugins: {
                            legend: {
                                position: 'top',
                            },
                            tooltip: {
                                mode: 'index',
                                intersect: false,
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            } catch (error) {
                console.error('Erro ao carregar gráfico de produção:', error);
                showErrorToast('Erro ao carregar gráfico de produção');
            }
        }

        async function loadQualityChart(period) {
            try {
                const response = await fetch(`${API_BASE}?action=quality_chart&period=${period}`);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const result = await response.json();
                
                if (!result.success) {
                    console.error('API Error:', result.message);
                    return;
                }
                
                const ctx = document.getElementById('quality-chart');
                if (!ctx) {
                    console.error('Canvas quality-chart não encontrado');
                    return;
                }
                
                const ctx2d = ctx.getContext('2d');
                
                if (qualityChart) {
                    qualityChart.destroy();
                }
                
                // Verificar se há dados
                if (!result.data || result.data.length === 0) {
                    // Criar gráfico vazio com mensagem
                    qualityChart = new Chart(ctx2d, {
                        type: 'line',
                        data: {
                            labels: ['Sem dados'],
                            datasets: [{
                                label: 'Gordura (%)',
                                data: [0],
                                borderColor: 'rgba(200, 200, 200, 0.5)',
                                backgroundColor: 'rgba(200, 200, 200, 0.1)'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: true,
                            aspectRatio: 2,
                            plugins: {
                                legend: { display: false },
                                tooltip: { enabled: false },
                                title: {
                                    display: true,
                                    text: 'Nenhum dado de qualidade disponível',
                                    font: { size: 14 },
                                    color: '#999'
                                }
                            }
                        }
                    });
                    return;
                }
                
                    qualityChart = new Chart(ctx2d, {
                        type: 'line',
                        data: {
                            labels: result.data.map(d => formatDate(d.date)),
                            datasets: [{
                                label: 'Gordura (%)',
                                data: result.data.map(d => parseFloat(d.avg_fat || 0)),
                                borderColor: 'rgb(34, 197, 94)',
                                backgroundColor: 'rgba(34, 197, 94, 0.1)',
                                tension: 0.4,
                                fill: true,
                                borderWidth: 2
                            }, {
                                label: 'Proteína (%)',
                                data: result.data.map(d => parseFloat(d.avg_protein || 0)),
                                borderColor: 'rgb(59, 130, 246)',
                                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                tension: 0.4,
                                fill: true,
                                borderWidth: 2
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: true,
                            aspectRatio: 2,
                            plugins: {
                                legend: {
                                    position: 'top',
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            }
                        }
                    });
            } catch (error) {
                console.error('Erro ao carregar gráfico de qualidade:', error);
                showErrorToast('Erro ao carregar gráfico de qualidade');
            }
        }

        async function loadBreedChart() {
            try {
                const response = await fetch(`${API_BASE}?action=breed_distribution`);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const result = await response.json();
                
                if (!result.success) {
                    console.error('API Error:', result.message);
                    return;
                }
                
                const ctx = document.getElementById('breed-chart');
                if (!ctx) {
                    console.error('Canvas breed-chart não encontrado');
                    return;
                }
                
                const ctx2d = ctx.getContext('2d');
                
                if (breedChart) {
                    breedChart.destroy();
                }
                
                // Verificar se há dados
                if (!result.data || result.data.length === 0) {
                    // Criar gráfico vazio com mensagem
                    breedChart = new Chart(ctx2d, {
                        type: 'doughnut',
                        data: {
                            labels: ['Sem dados'],
                            datasets: [{
                                data: [1],
                                backgroundColor: ['rgba(200, 200, 200, 0.5)']
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: true,
                            aspectRatio: 1.5,
                            plugins: {
                                legend: { display: false },
                                tooltip: { enabled: false },
                                title: {
                                    display: true,
                                    text: 'Nenhum dado de raças disponível',
                                    font: { size: 14 },
                                    color: '#999'
                                }
                            }
                        }
                    });
                    return;
                }
                
                    breedChart = new Chart(ctx2d, {
                        type: 'doughnut',
                        data: {
                            labels: result.data.map(d => d.breed || 'Não informado'),
                            datasets: [{
                                data: result.data.map(d => parseInt(d.count || 0)),
                                backgroundColor: [
                                    'rgba(59, 130, 246, 0.8)',
                                    'rgba(34, 197, 94, 0.8)',
                                    'rgba(251, 146, 60, 0.8)',
                                    'rgba(168, 85, 247, 0.8)',
                                    'rgba(236, 72, 153, 0.8)',
                                    'rgba(14, 165, 233, 0.8)',
                                    'rgba(245, 158, 11, 0.8)',
                                    'rgba(239, 68, 68, 0.8)'
                                ]
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: true,
                            aspectRatio: 1.5,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                }
                            }
                        }
                    });
            } catch (error) {
                console.error('Erro ao carregar gráfico de raças:', error);
                showErrorToast('Erro ao carregar gráfico de raças');
            }
        }

        async function loadTopProducers(period) {
            try {
                const response = await fetch(`${API_BASE}?action=efficiency_metrics&period=${period}`);
                const result = await response.json();
                
                if (result.success && result.data?.top_producers) {
                    const container = document.getElementById('top-producers-list');
                    
                    if (result.data.top_producers.length === 0) {
                        container.innerHTML = '<p class="text-gray-500 text-center py-4">Nenhum dado disponível</p>';
                        return;
                    }
                    
                    container.innerHTML = result.data.top_producers.map((animal, index) => `
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-green-400 to-green-600 flex items-center justify-center text-white font-bold text-sm">
                                    ${index + 1}
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900">${animal.animal_number || '-'} ${animal.name ? '- ' + animal.name : ''}</p>
                                    <p class="text-xs text-gray-500">${animal.production_days || 0} dias de produção</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-green-600">${formatNumber(animal.avg_volume || 0)}L</p>
                                <p class="text-xs text-gray-500">média/dia</p>
                            </div>
                        </div>
                    `).join('');
                }
            } catch (error) {
                console.error('Erro ao carregar top produtores:', error);
            }
        }

        async function loadEfficiencyMetrics(period) {
            try {
                const response = await fetch(`${API_BASE}?action=efficiency_metrics&period=${period}`);
                const result = await response.json();
                
                if (result.success && result.data) {
                    document.getElementById('metric-conception-rate').textContent = 
                        (result.data.conception_rate || 0) + '%';
                    document.getElementById('metric-iep').textContent = 
                        (result.data.avg_iep || 0) + ' dias';
                }
            } catch (error) {
                console.error('Erro ao carregar métricas de eficiência:', error);
            }
        }

        function formatNumber(num) {
            return parseFloat(num).toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function formatDate(dateString) {
            if (!dateString) return '';
            
            // Se for um número (YEARWEEK), retornar como está
            if (typeof dateString === 'number') {
                return dateString.toString();
            }
            
            // Tentar parsear como data
            try {
                // Formato YYYY-MM-DD
                if (dateString.match(/^\d{4}-\d{2}-\d{2}/)) {
                    const parts = dateString.split(' ')[0].split('-');
                    return `${parts[2]}/${parts[1]}`;
                }
                
                // Tentar Date object
                const date = new Date(dateString);
                if (!isNaN(date.getTime())) {
                    return date.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' });
                }
            } catch (e) {
                console.warn('Erro ao formatar data:', dateString, e);
            }
            
            return dateString;
        }
    </script>
</body>
</html>
