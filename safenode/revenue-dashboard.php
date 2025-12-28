<?php
/**
 * SafeNode - Dashboard de Receita/Vendas
 */

require_once __DIR__ . '/includes/config.php';

$pageTitle = 'Dashboard de Receita';

// Dados de receita (valores em reais)
$annualRevenue = 243150.87;
$monthlyRevenue = 20262.57;
$dailyRevenue = 666.52;
$growthRate = 12.5; // Percentual de crescimento
$totalCustomers = 1247;
$activeSubscriptions = 892;

// Custos (hospedagem, servidor e outros serviços)
$monthlyCosts = 11023.47; // Custos mensais
$annualCosts = $monthlyCosts * 12; // Custos anuais (12 meses)

// Lucro líquido (receita - custos)
$annualProfit = $annualRevenue - $annualCosts;
$monthlyProfit = $monthlyRevenue - $monthlyCosts;

// Dados mensais para gráfico (últimos 12 meses)
$monthlyData = [
    ['month' => 'Jan', 'revenue' => 18500.00],
    ['month' => 'Fev', 'revenue' => 19200.00],
    ['month' => 'Mar', 'revenue' => 19800.00],
    ['month' => 'Abr', 'revenue' => 20500.00],
    ['month' => 'Mai', 'revenue' => 21000.00],
    ['month' => 'Jun', 'revenue' => 21800.00],
    ['month' => 'Jul', 'revenue' => 22500.00],
    ['month' => 'Ago', 'revenue' => 19500.00],
    ['month' => 'Set', 'revenue' => 20100.00],
    ['month' => 'Out', 'revenue' => 20800.00],
    ['month' => 'Nov', 'revenue' => 21500.00],
    ['month' => 'Dez', 'revenue' => 20262.57]
];

// Calcular máximo para normalização do gráfico
$maxRevenue = max(array_column($monthlyData, 'revenue'));

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - SafeNode</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/img/logos (6).png">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <style>
        :root {
            --dark-bg: #030303;
            --dark-card: #0a0a0a;
            --dark-border: rgba(255, 255, 255, 0.1);
        }
        
        body {
            background: var(--dark-bg);
            color: #ffffff;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        .glass-card {
            background: rgba(10, 10, 10, 0.8);
            backdrop-filter: blur(10px);
            border: 1px solid var(--dark-border);
            border-radius: 16px;
        }
        
        .stat-card {
            background: rgba(10, 10, 10, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            transition: border-color 0.2s ease;
        }
        
        .stat-card:hover {
            border-color: rgba(255, 255, 255, 0.15);
        }
        
        .stat-card.green {
            border-left: 3px solid rgba(34, 197, 94, 0.5);
        }
        
        .stat-card.orange {
            border-left: 3px solid rgba(251, 191, 36, 0.5);
        }
        
        .stat-card.purple {
            border-left: 3px solid rgba(168, 85, 247, 0.5);
        }
        
        .gradient-text {
            color: #ffffff;
        }
        
        .bar-chart-container {
            position: relative;
            height: 300px;
        }
        
        .animated-gradient {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(139, 92, 246, 0.1) 100%);
            animation: gradient-shift 3s ease infinite;
            background-size: 200% 200%;
        }
        
        @keyframes gradient-shift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
        
        .pulse-dot {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .number-counter {
            font-variant-numeric: tabular-nums;
        }
    </style>
</head>
<body class="min-h-screen">
    <!-- Header -->
    <header class="border-b border-white/10 bg-black/50 backdrop-blur-xl sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-6 py-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <a href="dashboard.php" class="flex items-center gap-3 group">
                        <div class="relative">
                            <div class="absolute inset-0 bg-blue-500/20 blur-lg rounded-full group-hover:bg-blue-500/40 transition-all"></div>
                            <img src="assets/img/logos (6).png" alt="SafeNode" class="h-10 w-auto relative z-10">
                        </div>
                        <div>
                            <span class="font-bold text-xl text-white block">SafeNode</span>
                            <span class="text-xs text-zinc-500">Plataforma de Segurança</span>
                        </div>
                    </a>
                    <div class="h-10 w-px bg-white/20"></div>
                    <div>
                        <h1 class="text-2xl font-semibold text-white mb-1">Controle Financeiro</h1>
                        <p class="text-sm text-zinc-500">Página Administrativa - Dashboard de Receita e Vendas</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <div class="px-3 py-1.5 rounded border border-white/10 bg-white/5">
                        <div class="flex items-center gap-2">
                            <div class="w-1.5 h-1.5 rounded-full bg-zinc-400"></div>
                            <span class="text-xs font-medium text-zinc-400">Sistema Ativo</span>
                        </div>
                    </div>
                    <a href="dashboard.php" class="flex items-center gap-2 px-4 py-2 rounded-lg hover:bg-white/5 transition-colors text-zinc-400 hover:text-white">
                        <i data-lucide="arrow-left" class="w-4 h-4"></i>
                        <span>Voltar</span>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-6 py-8">
        <!-- Breadcrumb -->
        <div class="mb-6 flex items-center gap-2 text-sm text-zinc-500">
            <a href="dashboard.php" class="hover:text-white transition-colors">Dashboard</a>
            <i data-lucide="chevron-right" class="w-4 h-4"></i>
            <span class="text-white">Controle Financeiro</span>
        </div>
        
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Receita Anual -->
            <div class="stat-card p-6">
                <div class="flex items-start justify-between mb-4">
                    <div class="p-2.5 rounded-lg bg-white/5">
                        <i data-lucide="trending-up" class="w-5 h-5 text-zinc-300"></i>
                    </div>
                    <span class="text-xs font-medium text-zinc-400 bg-white/5 px-2.5 py-1 rounded border border-white/10">
                        +<?php echo number_format($growthRate, 1); ?>%
                    </span>
                </div>
                <div>
                    <p class="text-sm text-zinc-500 mb-2">Receita Anual</p>
                    <p class="text-3xl font-semibold text-white mb-2 number-counter">
                        R$ <?php echo number_format($annualRevenue, 2, ',', '.'); ?>
                    </p>
                    <p class="text-xs text-zinc-500">Período 2025</p>
                </div>
                <div class="mt-5 pt-4 border-t border-white/10">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs text-zinc-500">Meta atingida</span>
                        <span class="text-xs font-medium text-white">101.3%</span>
                    </div>
                    <div class="w-full bg-zinc-900 rounded-full h-1">
                        <div class="bg-white/20 h-1 rounded-full" style="width: 101.3%"></div>
                    </div>
                </div>
            </div>

            <!-- Receita Mensal -->
            <div class="stat-card green p-6">
                <div class="flex items-start justify-between mb-4">
                    <div class="p-2.5 rounded-lg bg-white/5">
                        <i data-lucide="calendar" class="w-5 h-5 text-zinc-300"></i>
                    </div>
                </div>
                <div>
                    <p class="text-sm text-zinc-500 mb-2">Receita Mensal</p>
                    <p class="text-3xl font-semibold text-white mb-2 number-counter">
                        R$ <?php echo number_format($monthlyRevenue, 2, ',', '.'); ?>
                    </p>
                    <p class="text-xs text-zinc-500">Dezembro 2025</p>
                </div>
                <div class="mt-5 pt-4 border-t border-white/10">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-zinc-500">vs mês anterior</span>
                        <span class="text-xs font-medium text-white">+3.2%</span>
                    </div>
                </div>
            </div>

            <!-- Receita Diária -->
            <div class="stat-card orange p-6">
                <div class="flex items-start justify-between mb-4">
                    <div class="p-2.5 rounded-lg bg-white/5">
                        <i data-lucide="dollar-sign" class="w-5 h-5 text-zinc-300"></i>
                    </div>
                </div>
                <div>
                    <p class="text-sm text-zinc-500 mb-2">Receita Diária Média</p>
                    <p class="text-3xl font-semibold text-white mb-2 number-counter">
                        R$ <?php echo number_format($dailyRevenue, 2, ',', '.'); ?>
                    </p>
                    <p class="text-xs text-zinc-500">Baseado em 365 dias</p>
                </div>
                <div class="mt-5 pt-4 border-t border-white/10">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-zinc-500">Hoje</span>
                        <span class="text-xs font-medium text-white">R$ 745,30</span>
                    </div>
                </div>
            </div>

            <!-- Total de Clientes -->
            <div class="stat-card purple p-6">
                <div class="flex items-start justify-between mb-4">
                    <div class="p-2.5 rounded-lg bg-white/5">
                        <i data-lucide="users" class="w-5 h-5 text-zinc-300"></i>
                    </div>
                </div>
                <div>
                    <p class="text-sm text-zinc-500 mb-2">Total de Clientes</p>
                    <p class="text-3xl font-semibold text-white mb-2 number-counter">
                        <?php echo number_format($totalCustomers, 0, ',', '.'); ?>
                    </p>
                    <p class="text-xs text-zinc-500"><?php echo number_format($activeSubscriptions, 0, ',', '.'); ?> assinantes ativos</p>
                </div>
                <div class="mt-5 pt-4 border-t border-white/10">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-zinc-500">Novos este mês</span>
                        <span class="text-xs font-medium text-white">+42</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Profit Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <!-- Lucro Líquido Anual -->
            <div class="stat-card p-6">
                <div class="flex items-start justify-between mb-4">
                    <div class="p-2.5 rounded-lg bg-white/5">
                        <i data-lucide="wallet" class="w-5 h-5 text-zinc-300"></i>
                    </div>
                </div>
                <div>
                    <p class="text-sm text-zinc-500 mb-2">Lucro Líquido Anual</p>
                    <p class="text-3xl font-semibold text-white mb-2 number-counter">
                        R$ <?php echo number_format($annualProfit, 2, ',', '.'); ?>
                    </p>
                    <p class="text-xs text-zinc-500">Receita: R$ <?php echo number_format($annualRevenue, 2, ',', '.'); ?> | Custos: R$ <?php echo number_format($annualCosts, 2, ',', '.'); ?></p>
                </div>
                <div class="mt-5 pt-4 border-t border-white/10">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs text-zinc-500">Margem de lucro</span>
                        <span class="text-xs font-medium text-white"><?php echo number_format(($annualProfit / $annualRevenue) * 100, 1); ?>%</span>
                    </div>
                    <div class="w-full bg-zinc-900 rounded-full h-1">
                        <div class="bg-white/30 h-1 rounded-full" style="width: <?php echo ($annualProfit / $annualRevenue) * 100; ?>%"></div>
                    </div>
                </div>
            </div>

            <!-- Lucro Líquido Mensal -->
            <div class="stat-card p-6">
                <div class="flex items-start justify-between mb-4">
                    <div class="p-2.5 rounded-lg bg-white/5">
                        <i data-lucide="credit-card" class="w-5 h-5 text-zinc-300"></i>
                    </div>
                </div>
                <div>
                    <p class="text-sm text-zinc-500 mb-2">Lucro Líquido Mensal</p>
                    <p class="text-3xl font-semibold text-white mb-2 number-counter">
                        R$ <?php echo number_format($monthlyProfit, 2, ',', '.'); ?>
                    </p>
                    <p class="text-xs text-zinc-500">Receita: R$ <?php echo number_format($monthlyRevenue, 2, ',', '.'); ?> | Custos: R$ <?php echo number_format($monthlyCosts, 2, ',', '.'); ?></p>
                </div>
                <div class="mt-5 pt-4 border-t border-white/10">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs text-zinc-500">Margem de lucro</span>
                        <span class="text-xs font-medium text-white"><?php echo number_format(($monthlyProfit / $monthlyRevenue) * 100, 1); ?>%</span>
                    </div>
                    <div class="w-full bg-zinc-900 rounded-full h-1">
                        <div class="bg-white/30 h-1 rounded-full" style="width: <?php echo ($monthlyProfit / $monthlyRevenue) * 100; ?>%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Revenue Chart -->
            <div class="glass-card p-6">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-lg font-semibold text-white mb-1">Receita Mensal</h2>
                        <p class="text-sm text-zinc-500">Últimos 12 meses</p>
                    </div>
                </div>
                <div class="bar-chart-container">
                    <canvas id="revenueChart"></canvas>
                </div>
                <div class="mt-4 pt-4 border-t border-white/10 flex items-center justify-between text-xs">
                    <span class="text-zinc-500">Média móvel (3 meses)</span>
                    <span class="text-white font-medium">R$ 20.450/mês</span>
                </div>
            </div>

            <!-- Growth Metrics -->
            <div class="glass-card p-6">
                <div class="mb-6">
                    <h2 class="text-xl font-bold text-white mb-1">Métricas de Crescimento</h2>
                    <p class="text-sm text-zinc-400">Análise de performance</p>
                </div>
                <div class="space-y-4">
                    <!-- Growth Rate -->
                    <div class="p-4 rounded-lg border border-white/10 bg-white/5">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm text-zinc-400">Taxa de Crescimento</span>
                            <span class="text-base font-semibold text-white">+<?php echo number_format($growthRate, 1); ?>%</span>
                        </div>
                        <div class="w-full bg-zinc-900 rounded-full h-1.5">
                            <div class="bg-white/30 h-1.5 rounded-full" style="width: <?php echo $growthRate; ?>%"></div>
                        </div>
                    </div>

                    <!-- Conversion Rate -->
                    <div class="p-4 rounded-lg border border-white/10 bg-white/5">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm text-zinc-400">Taxa de Conversão</span>
                            <span class="text-base font-semibold text-white">71.5%</span>
                        </div>
                        <div class="w-full bg-zinc-900 rounded-full h-1.5">
                            <div class="bg-white/30 h-1.5 rounded-full" style="width: 71.5%"></div>
                        </div>
                    </div>

                    <!-- Retention Rate -->
                    <div class="p-4 rounded-lg border border-white/10 bg-white/5">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm text-zinc-400">Taxa de Retenção</span>
                            <span class="text-base font-semibold text-white">87.2%</span>
                        </div>
                        <div class="w-full bg-zinc-900 rounded-full h-1.5">
                            <div class="bg-white/30 h-1.5 rounded-full" style="width: 87.2%"></div>
                        </div>
                    </div>

                    <!-- Avg Revenue per User -->
                    <div class="p-4 rounded-lg border border-white/10 bg-white/5">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-zinc-400">Receita Média por Cliente</span>
                            <span class="text-base font-semibold text-white">R$ 195,02</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Additional Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Top Revenue Sources -->
            <div class="glass-card p-6">
                <h3 class="text-lg font-bold text-white mb-4">Principais Fontes</h3>
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg bg-white/5 border border-white/10 flex items-center justify-center">
                                <i data-lucide="shield" class="w-4 h-4 text-zinc-300"></i>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-white">Planos Pro</p>
                                <p class="text-xs text-zinc-500">Assinaturas</p>
                            </div>
                        </div>
                        <span class="text-sm font-medium text-white">65%</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg bg-white/5 border border-white/10 flex items-center justify-center">
                                <i data-lucide="zap" class="w-4 h-4 text-zinc-300"></i>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-white">Add-ons</p>
                                <p class="text-xs text-zinc-500">Extras</p>
                            </div>
                        </div>
                        <span class="text-sm font-medium text-white">25%</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg bg-white/5 border border-white/10 flex items-center justify-center">
                                <i data-lucide="credit-card" class="w-4 h-4 text-zinc-300"></i>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-white">Consumos</p>
                                <p class="text-xs text-zinc-500">Uso adicional</p>
                            </div>
                        </div>
                        <span class="text-sm font-medium text-white">10%</span>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="glass-card p-6">
                <h3 class="text-lg font-bold text-white mb-4">Atividade Recente</h3>
                <div class="space-y-3">
                    <div class="flex items-start gap-3">
                        <div class="w-1.5 h-1.5 rounded-full bg-zinc-400 mt-2"></div>
                        <div class="flex-1">
                            <p class="text-sm text-white">Nova assinatura Pro</p>
                            <p class="text-xs text-zinc-500">Há 2 horas</p>
                        </div>
                        <span class="text-sm font-medium text-white">+R$ 49</span>
                    </div>
                    <div class="flex items-start gap-3">
                        <div class="w-1.5 h-1.5 rounded-full bg-zinc-400 mt-2"></div>
                        <div class="flex-1">
                            <p class="text-sm text-white">Upgrade para Enterprise</p>
                            <p class="text-xs text-zinc-500">Há 5 horas</p>
                        </div>
                        <span class="text-sm font-medium text-white">+R$ 199</span>
                    </div>
                    <div class="flex items-start gap-3">
                        <div class="w-1.5 h-1.5 rounded-full bg-zinc-400 mt-2"></div>
                        <div class="flex-1">
                            <p class="text-sm text-white">Add-on adquirido</p>
                            <p class="text-xs text-zinc-500">Há 1 dia</p>
                        </div>
                        <span class="text-sm font-medium text-white">+R$ 29</span>
                    </div>
                </div>
            </div>

            <!-- Projections -->
            <div class="glass-card p-6">
                <h3 class="text-lg font-bold text-white mb-4">Projeções</h3>
                <div class="space-y-4">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm text-zinc-400">Próximo Mês</span>
                            <span class="text-base font-semibold text-white">R$ 22.850</span>
                        </div>
                        <p class="text-xs text-zinc-500">Baseado na tendência atual</p>
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm text-zinc-400">Próximo Trimestre</span>
                            <span class="text-base font-semibold text-white">R$ 68.400</span>
                        </div>
                        <p class="text-xs text-zinc-500">Estimativa conservadora</p>
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm text-zinc-400">Meta Anual 2025</span>
                            <span class="text-base font-semibold text-white">R$ 275.000</span>
                        </div>
                        <p class="text-xs text-zinc-500">+13% vs 2024 (projeção)</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Inicializar ícones Lucide
        lucide.createIcons();
        
        // Re-inicializar ícones após atualizações
        setInterval(() => {
            lucide.createIcons();
        }, 1000);

        // Chart.js - Revenue Chart
        const ctx = document.getElementById('revenueChart');
        if (ctx) {
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode(array_column($monthlyData, 'month')); ?>,
                    datasets: [{
                        label: 'Receita (R$)',
                        data: <?php echo json_encode(array_column($monthlyData, 'revenue')); ?>,
                        backgroundColor: 'rgba(255, 255, 255, 0.1)',
                        borderColor: 'rgba(255, 255, 255, 0.3)',
                        borderWidth: 1,
                        borderRadius: 8,
                        borderSkipped: false,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(10, 10, 10, 0.9)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            borderColor: 'rgba(59, 130, 246, 0.5)',
                            borderWidth: 1,
                            padding: 12,
                            displayColors: false,
                            callbacks: {
                                label: function(context) {
                                    return 'R$ ' + context.parsed.y.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(255, 255, 255, 0.05)',
                                drawBorder: false
                            },
                            ticks: {
                                color: '#9ca3af',
                                callback: function(value) {
                                    return 'R$ ' + value.toLocaleString('pt-BR');
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false,
                                drawBorder: false
                            },
                            ticks: {
                                color: '#9ca3af'
                            }
                        }
                    }
                }
            });
        }
    </script>
</body>
</html>

